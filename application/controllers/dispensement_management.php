<?php

ob_start();

class Dispensement_management extends MY_Controller {

    var $api;
    var $patient_module;
    var $dispense_module;
    var $appointment_module;

    function __construct() {
        parent::__construct();

        $this->load->database();
        //$this->output->enable_profiler(TRUE);
    }

    public function index() {
        //$this -> listing();
    }

    public function init_api_values() {
        $sql = "SELECT * FROM api_config";
        $query = $this->db->query($sql);
        $api_config = $query->result_array();

        $conf = array();
        foreach ($api_config as $ob) {
            $conf[$ob['config']] = $ob['value'];
        }

        $this->api = ($conf['api_status'] == 'on') ? TRUE : FALSE;
        $this->patient_module = ($conf['api_patients_module'] == 'on') ? TRUE : FALSE;
        $this->dispense_module = ($conf['api_dispense_module'] == 'on') ? TRUE : FALSE;
        $this->appointment_module = ($conf['api_appointments_module'] == 'on') ? TRUE : FALSE;
        $this->api_adt_url = (strlen($conf['api_adt_url']) > 2) ? $conf['api_adt_url'] : FALSE;
    }

    public function get_patient_details() {
        $record_no = $this->input->post('record_no');
        $facility_code = $this->session->userdata('facility');
        $sql = "select ps.name as patient_source,p.patient_number_ccc,FLOOR(DATEDIFF(CURDATE(),p.dob)/365) as age from patient p 
		LEFT JOIN patient_source ps ON ps.id = p.source
		where p.id='$record_no' and facility_code='$facility_code'
		";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        echo json_encode($results);
    }

    public function get_patient_data($patient_id = NULL) {
        $data = array();
        /* Dispensing information */
        $sql = "SELECT 
		p.ccc_store_sp,
		p.patient_number_ccc AS patient_id,
		UPPER(CONCAT_WS(' ', CONCAT_WS(' ', p.first_name, p.other_name), p.last_name)) AS patient_name,
		UPPER(CONCAT_WS(' ', CONCAT_WS(' ', p.first_name, p.other_name), p.last_name)) AS patient_name_link,
		CURDATE() AS dispensing_date,
		p.height AS current_height,
		p.weight AS current_weight,
		p.nextappointment AS appointment_date
		FROM patient p
		WHERE p.id = ?";
        $query = $this->db->query($sql, array($patient_id));
        $data = $query->row_array();
        if (!empty($data)) {
            /* Visit information */
            $sql = "SELECT 
			v.dispensing_date AS prev_visit_date,
			v.last_regimen AS prev_regimen_id,
			d.drug AS prev_drug_name,
			v.quantity AS prev_drug_qty,
			v.drug_id AS prev_drug_id,
			v.dose AS prev_drug_dose,
			v.duration AS prev_duration
			FROM patient_visit v
			LEFT JOIN drugcode d ON d.id = v.drug_id
			WHERE v.patient_id = ?
			AND v.dispensing_date IN (SELECT MAX(dispensing_date) FROM patient_visit WHERE patient_id = ?)";
            $query = $this->db->query($sql, array($data['patient_id'], $data['patient_id']));
            $visits = $query->result_array();
            $data['prev_visit_data'] = "";
            if (!empty($visits)) {
                foreach ($visits as $visit) {
                    $data['prev_visit_date'] = $visit['prev_visit_date'];
                    $data['last_regimen'] = $visit['prev_regimen_id'];
                    $data['prev_visit_data'] .= "<tr><td>" . $visit['prev_drug_name'] . "</td><td>" . $visit['prev_drug_qty'] . "</td></tr>";
                }
            }
        }
        echo json_encode($data);
    }

    public function dispense($record_no) {
        $this->init_api_values();

        $facility_code = $this->session->userdata('facility');

        $dispensing_date = "";
        $data = array();
        $data['api'] = $this->api;
        $data['dispense_module'] = $this->dispense_module;
        $data['appointment_module'] = $this->appointment_module;
        $data['patient_module'] = $this->patient_module;

        $data['last_regimens'] = "";
        $data['visits'] = "";
        $data['appointments'] = "";
        $dispensing_date = date('Y-m-d');

        $sql = "SELECT * FROM facilities where facilitycode='$facility_code'";
        $query = $this->db->query($sql);
        $facility_settings = $query->result_array()[0];

        $data['pill_count'] = @$facility_settings['pill_count'];


        $sql = "select ps.name as patient_source,p.patient_number_ccc,FLOOR(DATEDIFF(CURDATE(),p.dob)/365) as age, LOWER(rst.name) as service_name , p.clinicalappointment from patient p 
		LEFT JOIN patient_source ps ON ps.id = p.source
		LEFT JOIN regimen_service_type rst ON rst.id = p.service
		where p.id='$record_no' and facility_code='$facility_code'
		";
        $query = $this->db->query($sql);
        $results = $query->result_array();

        if ($results) {
            $patient_no = $results[0]['patient_number_ccc'];
            $age = @$results[0]['age'];
            $data['age']  = @$results[0]['age']+0;
            $service_name = $results[0]['service_name'];
            $data['results'] = $results;
        }


        /*         * ********** */
        $data['differentiated_care'] = 0;
        $sql1 = "SELECT dispensing_date,differentiated_care FROM patient_visit pv WHERE pv.patient_id =  '" . $patient_no . "' AND pv.active=1 ORDER BY dispensing_date DESC LIMIT 1";
        $query = $this->db->query($sql1);
        $results1 = $query->row_array();
        $dated = '';
        $results = array();
        if ($results1) {
            $dated = $results1['dispensing_date'];
            $data['differentiated_care'] = $results1['differentiated_care'];
            $sql = "SELECT d.id as drug_id,d.drug,d.dose,d.duration, pv.quantity,pv.dispensing_date,pv.pill_count,r.id as regimen_id,r.regimen_desc,r.regimen_code,pv.months_of_stock as mos,ds.value,ds.frequency
			FROM patient_visit pv
			LEFT JOIN drugcode d ON d.id = pv.drug_id
			LEFT JOIN dose ds ON ds.Name=d.dose
			LEFT JOIN regimen r ON r.id = pv.regimen
			WHERE pv.patient_id =  '$patient_no'
			AND pv.active=1
			AND pv.dispensing_date = '$dated'
			ORDER BY dispensing_date DESC";
            $query = $this->db->query($sql);
            $results = $query->result_array();
        }


        // /*************/
        $data['prescription'] = array();
        $pid = (isset($_GET['pid'])) ? $_GET['pid'] : null;
        if ($pid && $this->api && $this->dispense_module) {
            $ps_sql = "SELECT dp.*,dpd.*,
			CASE WHEN dpdv.id IS NULL THEN 'not dispensed' ELSE 'dispensed' END as dispense_status
			FROM drug_prescription dp,drug_prescription_details dpd 
			left outer join drug_prescription_details_visit  dpdv on dpdv.drug_prescription_details_id  =dpd.id
			left outer join patient_visit on dpdv.visit_id  = patient_visit.id
			where
			dp.id = dpd.drug_prescriptionid and dp.id = $pid";
            $query = $this->db->query($ps_sql);
            $ps = $query->result_array();
            $data['prescription'] = $ps;
            // find if possible regimen from prescription
            foreach ($ps as $key => $p) {
                $drugname = $p['drug_name'];
                $regimen_sql = "SELECT  * FROM regimen where regimen_code like '%$drugname%'";
                $r_query = $this->db->query($regimen_sql);
                $rs = $r_query->result_array();
                if ($rs) {
                    $data['prescription_regimen_id'] = $rs[0]['id'];
                }
            }
        }
        // var_dump($data['prescription']);die;
        //// dispense prescription from EMR



        $data['non_adherence_reasons'] = Non_Adherence_Reasons::getAllHydrated();
        $data['regimen_changes'] = Regimen_Change_Purpose::getAllHydrated();
        $data['purposes'] = Visit_Purpose::getAll();
        $data['dated'] = $dated;
        $data['patient_id'] = $record_no;
        $data['service_name'] = $service_name;
        $data['purposes'] = Visit_Purpose::getAll();
        $data['patient_appointment'] = $results;
        $data['hide_side_menu'] = 1;
        $data['content_view'] = "patients/dispense_v";
        $this->base_params($data);
    }

    public function adr($record_no = null) {
        $dated = '';
        $id = $this->db->select_max('id')->get('adr_form')->result();
        $newid =(int) $id[0]->id + 1;
        if ($_POST) {
            $adr = array(
                'id'=>$newid,
                'report_title' => $this->_p('report_title'),
                'institution_name' => $this->_p('institution'),
                'institution_code' => $this->_p('institutioncode'),
                'county' => $this->_p('county_id'),
                'sub_county' => $this->_p('sub_county_id'),
                'address' => $this->_p('address'),
                'contact' => $this->_p('contact'),
                'patient_name' => $this->_p('patientname'),
                'ip_no' => $this->_p('ip_no'),
                'dob' => $this->_p('dob'),
                'patient_address' => $this->_p('patientaddress'),
                'ward_clinic' => $this->_p('clinic'),
                'gender' => $this->_p('gender'),
                'is_alergy' => $this->_p('allergy'),
                'alergy_desc' => $this->_p('allergydesc'),
                'is_pregnant' => $this->_p('pregnancystatus'),
                'weight' => $this->_p('patientweight'),
                'height' => $this->_p('patientheight'),
                'diagnosis' => $this->_p('diagnosis'),
                'reaction_description' => $this->_p('reaction'),
                // 'severity' => (isset($this->_p('severity'))) ? $this->_p('severity') : false,
                'severity' => $this->_p('severity'),
                'action_taken' => $this->_p('action'),
                'outcome' => $this->_p('outcome'),
                'reaction_casualty' => $this->_p('casuality'),
                'other_comment' => $this->_p('othercomment'),
                'reporting_officer' => $this->_p('officername'),
                'reporting_officer' => $this->_p('reportingdate'),
                'email_address' => $this->_p('officeremail'),
                'office_phone' => $this->_p('officerphone'),
                'designation' => $this->_p('designation_id'),
                'signature' => $this->_p('officersignature')
            );
            

            $this->db->insert('adr_form', $adr);
            //$adr_id = $this->db->insert_id();
            if (count($_POST['drug_name']) > 0) {

                foreach ($_POST['drug_name'] as $key => $drug) {
                    $adr_details = array(
                        'adr_id' => $newid,
                        'drug' => $_POST['drug_name'][$key],
                        'brand' => $_POST['brand_name'][$key],
                        'dose_id' => $_POST['dose_id'][$key],
                        'route' => $_POST['route_id'][$key],
                        'dose' => $_POST['dose'][$key],
                        'route_freq' => $_POST['frequency_id'][$key],
                        'date_started' => $_POST['dispensing_date'][$key],
                        'date_stopped' => $_POST['date_stopped'][$key],
                        'indication' => $_POST['indication'][$key],
                        'suspecteddrug' => (isset($_POST['suspecteddrug'][$key])) ? $_POST['suspecteddrug'][$key] : false,
                        'visitid' => $_POST['visitid'][$key]
                    );
                    $this->db->insert('adr_form_details', $adr_details);
                }
                redirect('inventory_management/adr/');
                
            } else {
                echo "No drugs selected";
                // no drugs selected
                // Form saved successfully
                die;
            }

            die;
        }


        $facility_code = $this->session->userdata('facility');

        $data = array();
        $dispensing_date = "";
        $data['last_regimens'] = "";
        $data['visits'] = "";
        $data['appointments'] = "";
        $data['uniqueid']=$newid;
        $data['user_full_name'] = $this->session->userdata('full_name');
        $data['user_email'] = $this->session->userdata('Email_Address');
        $data['user_phone'] = $this->session->userdata('Phone_Number');
// last visit id by patient
        $sql = "select dispensing_date from vw_patient_list vpv,patient_visit pv WHERE pv.patient_id = vpv.ccc_number and vpv.patient_id = $record_no order by dispensing_date desc  limit 1";
        $query = $this->db->query($sql);
        if ($query->result_array()) {
            $dispense_date = $query->result_array()[0]['dispensing_date'];
        }

        // Facility Details
        $sql = "select * from facilities WHERE facilitycode = $facility_code";
        $query = $this->db->query($sql);
        if ($query->result_array()) {
            $data['facility_details'] = $query->result_array()[0];
        }

        $sql = "select * from vw_patient_list WHERE patient_id = $record_no";
        $query = $this->db->query($sql);
        if ($query->result_array()) {
            $data['patient_details'] = $query->result_array()[0];
        }

        //Patient History

        $sql = "select  v_v.dispensing_date,
		v_v.visit_purpose_name AS visit, 
		v_v.dose, 
		v_v.duration, 
		v_v.patient_visit_id AS record_id, 
		D.drug, 
		v_v.quantity, 
		v_v.current_weight, 
		R.regimen_desc, 
		v_v.batch_number, 
		v_v.pill_count, 
		v_v.adherence, 
		v_v.indication, 
		v_v.frequency, 
		v_v.user,
                do.value,
		v_v.regimen_change_reason AS regimen_change_reason 
		from v_patient_visits as v_v
		INNER JOIN regimen as R ON R.id = v_v.current_regimen
		INNER JOIN drugcode as D ON D.id = v_v.drug_id
                LEFT JOIN dose as do ON do.id = D.unit
		WHERE v_v.id = $record_no
		AND v_v.pv_active = 1
		AND dispensing_date = '$dispense_date'
		GROUP BY v_v.drug_id,v_v.dispensing_date
		ORDER BY v_v.dispensing_date DESC";

        $query = $this->db->query($sql);
        $results = $query->result_array();
        if ($results) {
            $data['patient_visits'] = $results;
        } else {
            $data['patient_visits'] = "";
        }

        $dispensing_date = date('Y-m-d');

        $sql = "select ps.name as patient_source,p.patient_number_ccc,FLOOR(DATEDIFF(CURDATE(),p.dob)/365) as age, LOWER(rst.name) as service_name , p.clinicalappointment from patient p 
		LEFT JOIN patient_source ps ON ps.id = p.source
		LEFT JOIN regimen_service_type rst ON rst.id = p.service
		where p.id='$record_no' and facility_code='$facility_code'
		";
        $query = $this->db->query($sql);
        $results = $query->result_array();

        if ($results) {
            $patient_no = $results[0]['patient_number_ccc'];
            $age = @$results[0]['age'];
            $service_name = $results[0]['service_name'];
            $data['results'] = $results;
        }


        $sql = "SELECT *
		FROM patient_visit pv
		left join dose d on pv.dose = d.name
		left join drugcode dc on pv.drug_id = dc.id
		WHERE patient_id = '$patient_no'
		ORDER BY dispensing_date DESC";

        $query = $this->db->query($sql);
        $results = $query->result_array();

        $username = ($this->session->userdata('username'));
        $sql = "select ccc_store_sp from users where Username = '$username'";
        $query = $this->db->query($sql);
        $store_results = $query->result_array();
        if ($store_results) {
            $data['ccc_store'] = $store_results[0]['ccc_store_sp'];
            // $data['ccc_store'] = $this -> session -> userdata('ccc_store')[0]['id'];
        }
        $data['diagnosis']= $this->db->get('drug_classification')->result();
        $data['non_adherence_reasons'] = Non_Adherence_Reasons::getAllHydrated();
        $data['regimen_changes'] = Regimen_Change_Purpose::getAllHydrated();
        $data['purposes'] = Visit_Purpose::getAll();
        $data['dated'] = $dated;
        $data['patient_id'] = $record_no;
        $data['service_name'] = $service_name;
        $data['purposes'] = Visit_Purpose::getAll();
        $data['patient_appointment'] = $results;
        $data['hide_side_menu'] = 1;
        $data['content_view'] = "patients/dispense_adr_v";
        $this->base_params($data);
    }

    public function get_prep_reasons() {
        $data = array();
        $reasons = Prep_Reason::getActive();
        foreach ($reasons as $reason) {
            $data[] = array('text' => $reason['name'], 'value' => $reason['id']);
        }
        echo json_encode($data);
    }


    public function update_prep_test($patient_id, $prep_reason_id, $is_tested, $test_date, $test_result) {
        $message = '';
        $test_data = array(
            'patient_id' => $patient_id,
            'prep_reason_id' => $prep_reason_id,
            'is_tested' => $is_tested,
            'test_date' => $test_date,
            'test_result' => $test_result
        );
        $prev_test_data = $this->db->get_where('patient_prep_test', $test_data)->row_array();
        if (empty($prev_test_data)) {
            $this->db->insert('patient_prep_test', $test_data);
            $message .= 'Test Result Updated Successfully!<br/>';
        } else {
            $message .= 'Test Result Already Exist!<br/>';
        }
        if ($test_result == TRUE) {
            $message .= 'Switch Patient from PREP to ART service!<br/>';
        }
        echo $message;
    }

    public function get_other_dispensing_details() {
        $data = array();
        $patient_ccc = $this->input->post("patient_ccc");
        $data['non_adherence_reasons'] = Non_Adherence_Reasons::getAllHydrated();
        $data['regimen_changes'] = Regimen_Change_Purpose::getAllHydrated();
        $data['dcm_exit_reasons'] = DCM_exit_reason::getAllHydrated();
        $data['patient_appointment'] = Patient_appointment::getAppointmentDate($patient_ccc);

        echo json_encode($data);
    }

    public function getPreviouslyDispensedDrugs() {
        $patient_ccc = $this->input->post("patient_ccc");
        $ccc_id = $this->input->post("ccc_store");
        $sql = "SELECT d.id as drug_id,d.drug,d.dose,pv.duration, pv.quantity,pv.dispensing_date,pv.pill_count,r.id as regimen_id,r.regimen_desc,r.regimen_code,pv.months_of_stock as mos,ds.value,ds.frequency
		FROM patient_visit pv
		LEFT JOIN drugcode d ON d.id = pv.drug_id
		LEFT JOIN dose ds ON ds.Name=d.dose
		LEFT JOIN regimen r ON r.id = pv.regimen
		WHERE pv.patient_id =  '$patient_ccc'
		AND pv.active = 1
		AND pv.ccc_store_sp = '$ccc_id'
		AND pv.dispensing_date = (SELECT MAX(dispensing_date) dispensing_date FROM patient_visit pv WHERE pv.patient_id =  '$patient_ccc' AND pv.active=1)
		GROUP BY pv.drug_id,pv.dispensing_date,pv.patient_id 
		ORDER BY dispensing_date DESC";
        // echo $sql;
        $query = $this->db->query($sql);
        $results = $query->result_array();
        echo json_encode($results);
    }

    //Get list of drugs for a specific regimen
    public function getDrugsRegimens() {
        $regimen_id = $this->input->post('selected_regimen');
        $and_stocktype = "";
        if ($this->input->post('stock_type')) {
            $stock_type = $this->input->post('stock_type');
            $and_stocktype = "AND dsb.stock_type = '$stock_type' ";
        }
        $sql = "SELECT DISTINCT(d.id),UPPER(d.drug) as drug,IF(none_arv = 1, FALSE, TRUE) as is_arv
		FROM regimen_drug rd
		LEFT JOIN regimen r ON r.id = rd.regimen 
		LEFT JOIN drugcode d ON d.id=rd.drugcode 
		WHERE d.enabled='1'
		AND rd.regimen='$regimen_id'  
and rd.active= 1
UNION 

SELECT DISTINCT(d.id),UPPER(d.drug) as drug,IF(none_arv = 1, FALSE, TRUE) as is_arv
		FROM regimen_drug rd
		LEFT JOIN regimen r ON r.id = rd.regimen 
		LEFT JOIN drugcode d ON d.id=rd.drugcode 
		WHERE d.enabled='1'
AND  r.regimen_code LIKE '%oi%' 
 		ORDER BY drug asc";
        $get_drugs_sql = $this->db->query($sql);
        $get_drugs_array = $get_drugs_sql->result_array();
        echo json_encode($get_drugs_array);

//die();
    }

    public function getBrands() {
        $drug_id = $this->input->post("selected_drug");
        $get_drugs_sql = $this->db->query("SELECT DISTINCT id,brand FROM brand WHERE drug_id='" . $drug_id . "' AND brand!=''");
        $get_drugs_array = $get_drugs_sql->result_array();
        echo json_encode($get_drugs_array);
    }

    public function getDoses() {
        $get_doses_sql = $this->db->query("SELECT id,Name,value,frequency FROM dose");
        $get_doses_array = $get_doses_sql->result_array();
        echo json_encode($get_doses_array);
    }

    public function getDrugDose($drug_id) {
        $dose_array = array();
        $facility_code = $this->session->userdata('facility');
        $weight = $this->input->post("weight");
        $age = $this->input->post("age");
        $drug_id = $this->input->post("drug_id");

        $get_adult_age_sql = $this->db->query("SELECT adult_age FROM facilities where facilitycode='$facility_code'");
        $adult_age = $get_adult_age_sql->result_array()[0]['adult_age'];

        if ($age < $adult_age) {
            $weight_cond = (isset($weight)) ? "and min_weight <= $weight and max_weight >= $weight" : "";
            $sql = "select drug_id as id,Name as dose,frequency as freq,value from dossing_chart d  inner join dose do on do.id=d.dose_id 
			where drug_id=$drug_id
			$weight_cond
			and is_active = 1";
            $get_dose_sql = $this->db->query($sql);
            $dose_array = $get_dose_sql->result_array();
        }
        if (empty($dose_array) || $age > $adult_age) {
            $get_doses_sql = $this->db->query("SELECT d.id,d.dose,do.frequency as freq,value FROM drugcode d ,dose do where do.Name = d.dose  and d.id='$drug_id'");
            // echo( "SELECT d.id,d.dose,do.frequency as freq,value FROM drugcode d ,dose do where do.Name = d.dose  and d.id='$drug_id'");die;
            $doses_array = $get_doses_sql->result_array();
            $dose_array = $doses_array;
        }
        echo json_encode($dose_array);
    }

    public function getFacililtyAge() {
        $facility_code = $this->session->userdata('facility');
        $get_adult_age_sql = $this->db->query("SELECT adult_age FROM facilities where facilitycode='$facility_code'");
        $get_adult_age_array = $get_adult_age_sql->result_array();
        //echo $facility_code;
        echo json_encode($get_adult_age_array);
    }

//function to return drugs on the sync_drugs
    public function getMappedDrugCode() {
        $drug_id = $this->input->post("selected_drug");
        $get_drugcode_sql = $this->db->query("SELECT map FROM drugcode WHERE id='" . $drug_id . "' ");
        $get_drugcode_array = $get_drugcode_sql->result_array();
        echo json_encode($get_drugcode_array);
    }

    public function getIndications() {
        $drug_id = $this->input->post("drug_id");
        $get_indication_array = array();
        $sql = "SELECT * 
		FROM regimen_drug rd
		LEFT JOIN regimen r ON r.id=rd.regimen
		WHERE rd.drugcode='$drug_id'
		AND r.regimen_code LIKE '%oi%'";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        //if drug is an OI show indications
        if ($results) {
            $get_indication_sql = $this->db->query("SELECT id,Name,Indication FROM opportunistic_infection where active='1'");
            $get_indication_array = $get_indication_sql->result_array();
        }
        echo json_encode($get_indication_array);
    }

    public function edit($record_no) {
        $facility_code = $this->session->userdata('facility');
        $ccc_id = '2';
        $sql = "select pv.*,p.first_name,p.other_name,p.last_name,p.id as p_id "
                . "from patient_visit pv,"
                . "patient p "
                . "where pv.id='$record_no' "
                . "and pv.patient_id=p.patient_number_ccc "
                . "and facility='$facility_code'";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        //print_r($results);
        if ($results) {
            $data['results'] = $results;
            //Get expriry date the batch
            foreach ($results as $value) {
                $batch_number = $value['batch_number'];
                $drug_ig = $value['drug_id'];
                $ccc_id = $value['ccc_store_sp'];
                $sql = "select expiry_date FROM drug_stock_balance WHERE batch_number='$batch_number' AND drug_id='$drug_ig' AND stock_type='$ccc_id' AND facility_code='$facility_code' LIMIT 1";
                $expiry_sql = $this->db->query($sql);

                $expiry_array = $expiry_sql->result_array();
                $expiry_date = "";
                $data['expiries'] = $expiry_array;
                foreach ($expiry_array as $row) {
                    $expiry_date = $row['expiry_date'];
                    //print_r($expiry_date);
                    $data['original_expiry_date'] = $expiry_date;
                }
            }
        } else {
            $data['results'] = "";
        }
        $data['purposes'] = Visit_Purpose::getAll();
        $data['record'] = $record_no;
        $data['ccc_id'] = $ccc_id;
        $data['regimens'] = Regimen::getRegimens();
        $data['non_adherence_reasons'] = Non_Adherence_Reasons::getAllHydrated();
        $data['regimen_changes'] = Regimen_Change_Purpose::getAllHydrated();
        $data['doses'] = Dose::getAllActive();
        $data['indications'] = Opportunistic_Infection::getAllHydrated();
        $data['content_view'] = 'edit_dispensing_v';
        $data['hide_side_menu'] = 1;
        $this->base_params($data);
    }

    public function save() {
        $appointment_id = 0;
        $period = date("M-Y");
        $ccc_id = $this->input->post("ccc_store_id");
        $this->session->set_userdata('ccc_store_id', $ccc_id);
        $record_no = $this->session->userdata('record_no');
        $patient_name = $this->input->post("patient_details");
        $next_appointment_date = $this->input->post("next_appointment_date");
        $differentiated_care = ($this->input->post("differentiated_care")) ? 1 : 0;
        $next_clinical_appointment_date = $this->input->post("next_clinical_appointment_date");
        $next_clinical_appointment = $this->input->post("next_clinical_appointment");
        $prescription = $this->input->post("prescription") + 0;

        $last_appointment_date = $this->input->post("last_appointment_date");
        $last_appointment_date = date('Y-m-d', strtotime($last_appointment_date));
        $dispensing_date = $this->input->post("dispensing_date");
        $dispensing_date_timestamp = date('U', strtotime($dispensing_date));
        $facility = $this->session->userdata("facility");
        $patient = $this->input->post("patient");
        $height = $this->input->post("height");
        $current_regimen = $this->input->post("current_regimen");
        $drugs = $this->input->post("drug");
        $unit = $this->input->post("unit");
        $batch = $this->input->post("batch");
        $expiry = $this->input->post("expiry");
        $dose = $this->input->post("dose");
        $duration = $this->input->post("duration");
        $quantity = $this->input->post("qty_disp");
        $qty_available = $this->input->post("soh");
        $brand = $this->input->post("brand");

        $soh = $this->input->post("soh");
        $indication = $this->input->post("indication");
        $mos = $this->input->post("next_pill_count");
        //Actual Pill Count
        $pill_count = $this->input->post("pill_count");
        $comment = $this->input->post("comment");
        $missed_pill = $this->input->post("missed_pills");
        $purpose = $this->input->post("purpose");
        $purpose_refill_text = $this->input->post('purpose_refill_text');
        $weight = $this->input->post("weight");
        $last_regimen = $this->input->post("last_regimen");
        $regimen_change_reason = $this->input->post("regimen_change_reason");
        $dcm_exit_reason = $this->input->post("dcm_exit_reason");

        $non_adherence_reasons = $this->input->post("non_adherence_reasons");
        $patient_source = strtolower($this->input->post("patient_source"));
        $timestamp = date('U');
        $period = date("Y-m-01");
        $user = $this->session->userdata("username");
        $adherence = $this->input->post("adherence");

        $stock_type_text = $this->input->post("stock_type_text");

        //update service type
        $sql_get_service = "SELECT type_of_service FROM regimen WHERE id='$current_regimen'";
        $results = $this->db->query($sql_get_service);
        $res = $results->result_array();
        $service = $res[0]['type_of_service'];
        $sql_get_patient_service = "SELECT service FROM patient WHERE patient_number_ccc='$patient'";
        $service_results = $this->db->query($sql_get_patient_service);
        $dcm_change_id = '';
        $dcm_status ='';
        
        $sql_get_patient_dcm = "SELECT id,status,patient FROM dcm_change_log WHERE patient = '$patient' ORDER BY id desc LIMIT 1";
        $patient_dcm = $this->db->query($sql_get_patient_dcm);
        if($patient_dcm->result_array()){

            $dcm_change_id  = $patient_dcm->result_array()[0]['id'];
            $dcm_status  = $patient_dcm->result_array()[0]['status'];
        }


        $service_res = $service_results->result_array();
        $patient_service = $service_res[0]['service'];

        if ($patient_service != $service) {
            $sql = "UPDATE patient SET service='$service' WHERE service='$patient_service' AND patient_number_ccc='$patient';";
            $this->db->query($sql);
        }

        if (!$differentiated_care) {
            $next_clinical_appointment_date = $next_appointment_date;
            $sql = "UPDATE patient SET differentiated_care=0 ,adherence = '$adherence' WHERE patient_number_ccc='$patient';";
            $this->db->query($sql);
            if($dcm_status == '1'){
                $sql = "UPDATE  dcm_change_log SET status=0 ,end_date = CURDATE(),exit_reason = '$dcm_exit_reason' WHERE id='$dcm_change_id';";
            $this->db->query($sql);
            }
        }

        if ($differentiated_care == 1) {
            $sql = "UPDATE patient SET differentiated_care=1 ,adherence = '$adherence' WHERE patient_number_ccc='$patient';";
            $this->db->query($sql);

            $sql = "insert into dcm_change_log (status,start_date,patient) values (1,CURDATE(),'$patient');";
            $this->db->query($sql);
        }

        //end update service type
        //Get transaction type
        $transaction_type = transaction_type::getTransactionType("dispense", "0");
        $transaction_type = $transaction_type->id;
        //Source destination
        $source = '';
        $destination = '';
        //Source and destination depending on the stock type
        if (stripos($stock_type_text, 'store')) {
            $source = $facility;
            $destination = '0';
        } elseif (stripos($stock_type_text, 'pharmacy')) {
            $source = $facility;
            $destination = $facility;
        }

        /*
         * Update Appointment Info
         */
        $sql = "";
        $add_query = "";
        //If purpose of refill is start ART, update start regimen and start regimen date
        if ($purpose_refill_text == "start art") {
            $add_query = " , start_regimen = '$current_regimen',start_regimen_date = '$dispensing_date' ";
        }

        $trans_id = '';
        $status_add = ' ';
        if (stripos($patient_source, 'transit') === 0) {//If patient is on transit, change his status
            $get_status = "SELECT id,name FROM patient_status WHERE name LIKE '%transit%' LIMIT 1";
            $q = $this->db->query($get_status);
            $result = $q->result_array();
            $trans_id = $result[0]['id'];
            $add_query .= ", current_status = '$trans_id' ";
        }

        /// save clinical appointment $ return clinical appointment id then tie it to appointment date
        // if ($next_clinical_appointment_date !== $next_clinical_appointment) {
        $q = "SELECT id FROM clinic_appointment WHERE patient = '$patient' AND appointment = '$next_clinical_appointment' LIMIT 1";
        $query = $this->db->query($q);
        $result = $query->result_array();
        $clinical_appointment_id = $result[0]['id'];
        $sql_str = ($clinical_appointment_id > 0) ? "UPDATE clinic_appointment set appointment='$next_clinical_appointment_date', differentiated_care = '$differentiated_care' where id = $clinical_appointment_id " : "insert into clinic_appointment (patient,appointment,facility,differentiated_care) values ('$patient', '$next_clinical_appointment_date', '$facility','$differentiated_care'); ";
        $query = $this->db->query($sql_str);

        $q = "SELECT id FROM clinic_appointment WHERE patient = '$patient' AND appointment = '$next_clinical_appointment_date' LIMIT 1";
        $query = $this->db->query($q);
        $result = $query->result_array();
        $clinical_appointment_id = $result[0]['id'];

        // }
        // <!-- save clinical appointment



        if ($last_appointment_date) {
            if ($last_appointment_date > $dispensing_date) {
                //come early for appointment
                $sql .= "delete from patient_appointment where patient='$patient' and appointment='$last_appointment_date';";
            }
        }
        $sql .= "insert into patient_appointment (patient,appointment,facility,clinical_appointment) values ('$patient','$next_appointment_date','$facility','$clinical_appointment_id');";

        /*
         * Update patient Info
         */

        $sql .= "update patient SET weight='$weight',height='$height',current_regimen='$current_regimen',nextappointment='$next_appointment_date',clinicalappointment = '$next_clinical_appointment_date' $add_query where patient_number_ccc ='$patient' and facility_code='$facility';";

        /*
         * Update Visit and Drug Info
         */

        for ($i = 0; $i < sizeof($drugs); $i++) {
            //Get running balance in drug stock movement
            $sql_run_balance = $this->db->query("SELECT machine_code as balance FROM drug_stock_movement WHERE drug ='$drugs[$i]' AND ccc_store_sp ='$ccc_id' AND expiry_date >=CURDATE() ORDER BY id DESC  LIMIT 1");
            $run_balance_array = $sql_run_balance->result_array();
            if (count($run_balance_array) > 0) {
                $prev_run_balance = $run_balance_array[0]["balance"];
            } else {
                //If drug does not exist, initialise the balance to zero
                $prev_run_balance = 0;
            }
            $act_run_balance = $prev_run_balance - $quantity[$i];
            //Get running balance in drug stock movement end ---------

            $remaining_balance = $soh[$i] - $quantity[$i];
            if ($pill_count[$i] == '') {
                $pill_count[$i] = $mos[$i];
            }
            /* if ($mos != "") {//If transaction has actual pill count, actual pill count will pill count + amount dispensed
              $mos[$i] = $quantity[$i] + (int)$mos[$i];
              } */

            //Add visit
            $visit_sql = "insert into patient_visit (patient_id, visit_purpose, current_height, current_weight, regimen, regimen_change_reason,last_regimen, drug_id, batch_number, brand, indication, pill_count, comment, `timestamp`, user, facility, dose, dispensing_date, dispensing_date_timestamp,quantity,duration,adherence,missed_pills,non_adherence_reason,months_of_stock,ccc_store_sp,differentiated_care) VALUES ('$patient','$purpose', '$height', '$weight', '$current_regimen', '$regimen_change_reason','$last_regimen' ,'$drugs[$i]', '$batch[$i]', '$brand[$i]', '$indication[$i]', '$pill_count[$i]','$comment[$i]', '$timestamp', '$user','$facility', '$dose[$i]','$dispensing_date', '$dispensing_date_timestamp','$quantity[$i]','$duration[$i]','$adherence','$missed_pill[$i]','$non_adherence_reasons','$mos[$i]','$ccc_id','$differentiated_care');";
            $this->db->query($visit_sql);

            $visit_id = $this->db->insert_id();
            $regimen_change_query = " insert into change_log (old_value,new_value,facility,patient,change_purpose,change_type)
        select '" . $last_regimen . "' 
        ,'" . $current_regimen . "'
        ,'" . $facility . "'
        ,'" . $patient . "'
        ,'" . $regimen_change_reason . "','regimen' where '" . $current_regimen . "' != '" . $last_regimen . "'";

            $this->db->query($regimen_change_query);


            if ($prescription > 0) {
                //Check Regimen Drug Table to figure out which drug is ART/OI
                $chk_reg_drug_sql = "SELECT 1 FROM regimen_drug WHERE regimen = '$current_regimen' AND drugcode = '$drugs[$i]'";
                $chk_result = $this->db->query($chk_reg_drug_sql)->row_array();
                if ($chk_result) {
                    //Is an ARV
                    $this->db->insert('drug_prescription_details_visit', array('drug_prescription_details_id' => $this->getPrescription($prescription)['arv_prescription'], 'visit_id' => $visit_id));
                } else {
                    //Is an OI
                    $this->db->insert('drug_prescription_details_visit', array('drug_prescription_details_id' => $this->getPrescription($prescription)['oi_prescription'], 'visit_id' => $visit_id));
                }
            }


            $sql .= "insert into drug_stock_movement (drug, transaction_date, batch_number, transaction_type,source,destination,expiry_date,quantity, quantity_out,balance, facility,`timestamp`,machine_code,ccc_store_sp) VALUES ('$drugs[$i]','$dispensing_date','$batch[$i]','$transaction_type','$source','$destination','$expiry[$i]',0,'$quantity[$i]',$remaining_balance,'$facility','$dispensing_date_timestamp','$act_run_balance','$ccc_id');";
            $sql .= "update drug_stock_balance SET balance=balance - '$quantity[$i]' WHERE drug_id='$drugs[$i]' AND batch_number='$batch[$i]' AND expiry_date='$expiry[$i]' AND stock_type='$ccc_id' AND facility_code='$facility';";
            $sql .= "INSERT INTO drug_cons_balance(drug_id,stock_type,period,facility,amount,ccc_store_sp) VALUES('$drugs[$i]','$ccc_id','$period','$facility','$quantity[$i]','$ccc_id') ON DUPLICATE KEY UPDATE amount=amount+'$quantity[$i]';";
            $sql .= "UPDATE patient p JOIN patient_visit pv on p.patient_number_ccc = pv.patient_id JOIN drugcode dc on  pv.drug_id = dc.id SET p.isoniazid_start_date  = pv.dispensing_date , p.isoniazid_end_date = pv.dispensing_date + INTERVAL 168 DAY, drug_prophylaxis = concat(drug_prophylaxis ,',',(select id from drug_prophylaxis where  name like '%iso%')) WHERE dc.drug LIKE '%iso%'  and p.isoniazid_start_date IS NULL AND pv.patient_id  = '$patient';";
            $sql .="UPDATE patient p  JOIN patient_visit pv on p.patient_number_ccc = pv.patient_id  JOIN drugcode dc on  pv.drug_id = dc.id SET drug_prophylaxis = concat(drug_prophylaxis ,',',(select id from drug_prophylaxis where  lower(name) like '%cotri%')) WHERE lower(dc.drug) LIKE '%cotri%'   AND pv.patient_id  = '$patient';";
        }

        $queries = explode(";", $sql);
        $count = count($queries);
        $c = 0;
        foreach ($queries as $query) {
            //$c++;
            //if (strlen($query) > 0) {
            $this->db->query($query);
            //}
        }


        if (isset($prescription)) {
            // fetch appointment_id
            $q = "SELECT id FROM patient_appointment WHERE patient = '$patient' AND appointment = '$next_appointment_date' LIMIT 1";
            $query = $this->db->query($q);
            $result = $query->result_array();
            $appointment_id = $result[0]['id'];

            //file_get_contents(base_url() . 'tools/api/getdispensing/' . $prescription);
            //file_get_contents(base_url() . 'tools/api/getappointment/' . $appointment_id);
        }

        $this->session->set_userdata('msg_save_transaction', 'success');
        $this->session->set_flashdata('dispense_updated', 'Dispensing to patient No. ' . $patient . ' successfully completed!');
        redirect("patient_management");
    }

    public function save_edit() {
        $timestamp = "";
        $patient = "";
        $facility = "";
        $user = "";
        $record_no = "";
        $soh = $this->input->post("soh");
        //Get transaction type
        $transaction_type = transaction_type::getTransactionType("dispense", "0");
        $transaction_type = $transaction_type->id;
        $transaction_type1 = transaction_type::getTransactionType("returns", "1");
        $transaction_type1 = $transaction_type1->id;
        $original_qty = @$_POST["qty_hidden"];
        $facility = $this->session->userdata("facility");
        $user = $this->session->userdata("full_name");
        $timestamp = date('Y-m-d H:i:s');
        $patient = @$_POST['patient'];
        $expiry_date = @$_POST['expiry'];
        $ccc_id = @$_POST["ccc_id"];
        $differentiated_care = ($_POST["differentiated_care"] =='on') ? 1 : 0 ;

        //Define source and destination
        $source = $facility;
        $destination = $facility;

        //Get ccc_store_name 
        $ccc_store = CCC_store_service_point::getCCC($ccc_id);
        $ccc_name = $ccc_store->Name;

        if (stripos($ccc_name, 'store')) {
            $source = $facility;
            $destination = '';
        }

        //Get running balance in drug stock movement
        $sql_run_balance = $this->db->query("SELECT machine_code as balance FROM drug_stock_movement WHERE drug ='" . @$_POST['original_drug'] . "' AND ccc_store_sp ='$ccc_id' AND expiry_date >=CURDATE() ORDER BY id DESC  LIMIT 1");
        $run_balance_array = $sql_run_balance->result_array();
        if (count($run_balance_array) > 0) {
            $prev_run_balance = $run_balance_array[0]["balance"];
        } else {
            //If drug does not exist, initialise the balance to zero
            $prev_run_balance = 0;
        }

        //Get running balance in drug stock movement end ---------
        //If record is to be deleted
        if (@$_POST['delete_trigger'] == 1) {
            $sql = "update patient_visit set active='0' WHERE id='" . @$_POST["dispensing_id"] . "';";
            $this->db->query($sql);
            $bal = $soh + @$_POST["qty_disp"];

            $act_run_balance = $prev_run_balance + @$_POST["qty_disp"]; //Actual running balance		
            //If deleting previous transaction, check if batch has not expired, if not, insert in drug stock balance table
            $today = strtotime(date("Y-m-d"));
            $original_expiry = strtotime(@$_POST["original_expiry_date"]);
            if ($today <= $original_expiry) {
                //If balance for this batch is greater than zero, update stock, otherwise, insert in drug stock balance
                $sql_batch_balance = "SELECT balance FROM drug_stock_balance WHERE drug_id='" . @$_POST["original_drug"] . "' AND batch_number='" . @$_POST["batch"] . "' AND expiry_date='" . @$_POST["original_expiry_date"] . "' AND stock_type='$ccc_id' AND facility_code='$facility'";
                $query = $this->db->query($sql_batch_balance);
                $res = $query->result_array();
                $prev_batch_balance = "";
                if ($res) {
                    $prev_batch_balance = $res[0]['balance'];
                }
                if ($prev_batch_balance > 0) {
                    //Update drug_stock_balance
                    $sql = "UPDATE drug_stock_balance SET balance=balance+" . @$_POST["qty_disp"] . " WHERE drug_id='" . @$_POST["original_drug"] . "' AND batch_number='" . @$_POST["batch"] . "' AND expiry_date='" . @$_POST["original_expiry_date"] . "' AND stock_type='$ccc_id' AND facility_code='$facility'";
                    $this->db->query($sql);
                } else {

                    $sql = "INSERT INTO drug_stock_balance (balance,dug_id,batch_number,expiry_date,stock_type,facility_code) VALUES('" . @$_POST["qty_disp"] . "','" . @$_POST["original_drug"] . "','" . @$_POST["batch"] . "','" . @$_POST["original_expiry_date"] . "','$ccc_id','$facility')";
                    $this->db->query($sql);
                }
            }


            //Insert in drug stock movement
            //Get balance after update
            $sql = "SELECT balance FROM drug_stock_balance WHERE drug_id='" . @$_POST["original_drug"] . "' AND batch_number='" . @$_POST["batch"] . "' AND expiry_date='" . @$_POST["original_expiry_date"] . "' AND stock_type='$ccc_id' AND facility_code='$facility'";
            $query = $this->db->query($sql);
            $results = $query->result_array();
            $actual_balance = $results[0]['balance'];
            $sql = "INSERT INTO drug_stock_movement (drug, transaction_date, batch_number, transaction_type,source,destination,source_destination,expiry_date, quantity, balance, facility, machine_code,timestamp,ccc_store_sp) SELECT '" . @$_POST["original_drug"] . "','" . @$_POST["original_dispensing_date"] . "', '" . @$_POST["batch"] . "','$transaction_type1','$source','$destination','Dispensed To Patients','$expiry_date','" . @$_POST["qty_disp"] . "','" . @$actual_balance . "','$facility','$act_run_balance','$timestamp','$ccc_id' from drug_stock_movement WHERE batch_number= '" . @$_POST["batch"] . "' AND drug='" . @$_POST["original_drug"] . "' LIMIT 1;";
            $this->db->query($sql);

            //Update drug consumption
            $period = date('Y-m-01');
            $sql = "UPDATE drug_cons_balance SET amount=amount-" . $original_qty . " WHERE drug_id='" . @$_POST["original_drug"] . "' AND stock_type='$ccc_id' AND period='$period' AND facility='$facility'";
            $this->db->query($sql);

            $this->session->set_userdata('dispense_deleted', 'success');
        } else {//If record is edited
            $period = date('Y-m-01');
            $sql = "UPDATE patient_visit SET dispensing_date = '" . @$_POST["dispensing_date"] . "', visit_purpose = '" . @$_POST["purpose"] . "', current_weight='" . @$_POST["weight"] . "', current_height='" . @$_POST["height"] . "', regimen='" . @$_POST["current_regimen"] . "', drug_id='" . @$_POST["drug"] . "', batch_number='" . @$_POST["batch"] . "', dose='" . @$_POST["dose"] . "', duration='" . @$_POST["duration"] . "', quantity='" . @$_POST["qty_disp"] . "', brand='" . @$_POST["brand"] . "', indication='" . @$_POST["indication"] . "', pill_count='" . @$_POST["pill_count"] . "', missed_pills='" . @$_POST["missed_pills"] . "', comment='" . @$_POST["comment"] . "',non_adherence_reason='" . @$_POST["non_adherence_reasons"] . "',adherence='" . @$_POST["adherence"] . "',differentiated_care='" . @$differentiated_care . "' WHERE id='" . @$_POST["dispensing_id"] . "';";
            $this->db->query($sql);
            if (@$_POST["batch"] != @$_POST["batch_hidden"] || @$_POST["qty_disp"] != @$_POST["qty_hidden"]) {
                //Update drug_stock_balance
                //Balance=balance+(previous_qty_disp-actual_qty_dispense)
                $bal = $soh;
                //New qty dispensed=old qty - actual qty dispensed
                $new_qty_dispensed = $_POST["qty_hidden"] - $_POST["qty_disp"];
                $act_run_balance = $prev_run_balance - $_POST["qty_disp"];
                //If new quantity dispensed is less than qty previously dispensed
                //echo $new_qty_dispensed;die();
                if ($new_qty_dispensed > 0) {
                    $bal = $soh + $new_qty_dispensed;
                    $sql = "UPDATE drug_stock_balance SET balance=balance+" . @$new_qty_dispensed . " WHERE drug_id='" . @$_POST["original_drug"] . "' AND batch_number='" . @$_POST["batch"] . "' AND expiry_date='" . @$_POST["original_expiry_date"] . "' AND stock_type='$ccc_id' AND facility_code='$facility'";
                    $this->db->query($sql);

                    //Update drug consumption
                    $sql = "UPDATE drug_cons_balance SET amount=amount-" . $new_qty_dispensed . " WHERE drug_id='" . @$_POST["original_drug"] . "' AND stock_type='$ccc_id' AND period='$period' AND facility='$facility'";
                    $this->db->query($sql);
                } else if ($new_qty_dispensed < 0) {
                    $bal = $soh - $new_qty_dispensed;
                    $new_qty_dispensed = abs($new_qty_dispensed);
                    $sql = "UPDATE drug_stock_balance SET balance=balance-" . @$new_qty_dispensed . " WHERE drug_id='" . @$_POST["original_drug"] . "' AND batch_number='" . @$_POST["batch"] . "' AND expiry_date='" . @$_POST["original_expiry_date"] . "' AND stock_type='$ccc_id' AND facility_code='$facility'";
                    $this->db->query($sql);

                    //Update drug consumption
                    $sql = "UPDATE drug_cons_balance SET amount=amount+" . $new_qty_dispensed . " WHERE drug_id='" . @$_POST["original_drug"] . "' AND stock_type='$ccc_id' AND period='$period' AND facility='$facility'";
                    $this->db->query($sql);
                }
                //Balance after returns
                $bal1 = $soh + $original_qty;
                $act_run_balance1 = $prev_run_balance + $original_qty; //Actual running balance
                $act_run_balance = $act_run_balance + $original_qty;
                //Returns transaction
                $sql = "INSERT INTO drug_stock_movement (drug, transaction_date, batch_number, transaction_type,source,destination,source_destination,expiry_date, quantity,balance, facility, machine_code,timestamp,ccc_store_sp) SELECT '" . @$_POST["original_drug"] . "','" . @$_POST["original_dispensing_date"] . "', '" . @$_POST["batch_hidden"] . "','$transaction_type1','$source','$destination','Dispensed To Patients',expiry_date,'" . @$_POST["qty_hidden"] . "','$bal1','$facility','$act_run_balance1','$timestamp','$ccc_id' from drug_stock_movement WHERE batch_number= '" . @$_POST["batch_hidden"] . "' AND drug='" . @$_POST["original_drug"] . "' LIMIT 1;";
                $this->db->query($sql);
                //Dispense transaction
                $sql = "INSERT INTO drug_stock_movement (drug, transaction_date, batch_number, transaction_type,source,destination,expiry_date, quantity_out,balance, facility, machine_code,timestamp,ccc_store_sp) SELECT '" . @$_POST["drug"] . "','" . @$_POST["original_dispensing_date"] . "', '" . @$_POST["batch"] . "','$transaction_type','$source','$destination',expiry_date,'" . @$_POST["qty_disp"] . "','$bal','$facility','$act_run_balance','$timestamp','$ccc_id' from drug_stock_movement WHERE batch_number= '" . @$_POST["batch"] . "' AND drug='" . @$_POST["drug"] . "' LIMIT 1;";
                $this->db->query($sql);
            }
            $this->session->set_userdata('dispense_updated', 'success');
        }
        $sql = "select * from patient where patient_number_ccc='$patient' and facility_code='$facility'";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        $record_no = $results[0]['id'];
        $this->session->set_userdata('msg_save_transaction', 'success');
        redirect("patient_management/load_view/details/$record_no");
    }

    public function drugAllergies() {
        $drug = $this->input->post("selected_drug");
        $patient_no = $this->input->post("patient_no");
        $allergies = Patient::getPatientAllergies($patient_no);
        @$drug_list = explode(",", @$allergies['Adr']);
        $is_allergic = 0;
        foreach ($drug_list as $value) {
            if ($value != '') {
                $value = str_ireplace("-", "", $value);
                if ($drug == $value) {
                    $is_allergic = 1;
                }
            }
        }
        echo $is_allergic;
    }

    public function print_test() {
        $check_if_print = @$this->input->post("print_check");
        $no_to_print = $this->input->post("print_count");
        $drug_name = $this->input->post("print_drug_name");
        $qty = $this->input->post("print_qty");
        $drug_unit = $this->input->post("print_drug_unit");
        $dose_value = $this->input->post("print_dose_value");
        $dose_frequency = $this->input->post("print_dose_frequency");
        $dose_hours = $this->input->post("print_dose_hours");
        $drug_instructions = $this->input->post("print_drug_info");
        $patient_name = $this->input->post("print_patient_name");
        $pharmacy_name = $this->input->post("print_pharmacy");
        $dispensing_date = $this->input->post("print_date");
        $facility_name = $this->input->post("print_facility_name");
        $facility_phone = $this->input->post("print_facility_phone");
        $str = "";
        $this->load->library('mpdf');

        //MPDF Config
        $mode = 'utf-8';
        $format = array(88.9, 38.1);
        $default_font_size = '9';
        $default_font = 'Segoe UI';
        $margin_left = '2';
        $margin_right = '2';
        $margin_top = '4';
        $margin_bottom = '2';
        $margin_header = '';
        $margin_footer = '';
        $orientation = 'P';

        $this->mpdf = new mPDF($mode, $format, $default_font_size, $default_font, $margin_left, $margin_right, $margin_top, $margin_bottom, $margin_header, $margin_footer, $orientation);

        if ($check_if_print) {
            //loop through checkboxes check if they are selected to print
            foreach ($check_if_print as $counter => $check_print) {
                //selected to print
                if ($check_print) {
                    //count no. to print
                    $count = 1;
                    while ($count <= $no_to_print[$counter]) {
                        $this->mpdf->addPage();
                        $str = '<table border="1"  style="border-collapse:collapse;font-size:9px;">';
                        $str .= '<tr>';
                        $str .= '<td colspan="2">Drugname: <b>' . strtoupper($drug_name[$counter]) . '</b></td>';
                        $str .= '<td>Qty: <b>' . $qty[$counter] . '</b></td>';
                        $str .= '</tr>';
                        $str .= '<tr>';
                        $str .= '<td colspan="3">';
                        $str .= '<b>' . $dose_value[$counter] . ' ' . $drug_unit[$counter] . '</b> to be taken <b>' . $dose_frequency[$counter] . '</b> a day after every <b>' . $dose_hours[$counter] . '</b> hours</td>';
                        $str .= '</tr>';
                        $str .= '<tr>';
                        $str .= '<td colspan="3">Before/After Meals: ';
                        $str .= '<b>' . $drug_instructions[$counter] . '</b></td>';
                        $str .= '</tr>';
                        $str .= '<tr>';
                        $str .= '<td>Patient Name: <b>' . $patient_name . '</b> </td><td> Pharmacy :<b>' . $pharmacy_name[$counter] . '</b> </td> <td>Date:<b>' . $dispensing_date . '</b></td>';
                        $str .= '</tr>';
                        $str .= '<tr>';
                        $str .= '<td colspan="3" style="text-align:center;">Keep all medicines in a cold dry place out of reach of children.</td></tr>';
                        $str .= '<tr><td colspan="2">Facility Name: <b>' . $this->session->userdata("facility_name") . '</b></td><td> Facility Phone: <b>' . $this->session->userdata("facility_phone") . '</b>';
                        $str .= '</td>';
                        $str .= '</tr>';
                        $str .= '</table>';
                        //write to page
                        $this->mpdf->WriteHTML($str);
                        $count++;
                    } //end while
                }//end if
            } //end foreach
            $file_name = 'assets/download/' . $patient_name . '(Labels).pdf';
            $this->mpdf->Output($file_name, 'F');
            echo base_url() . $file_name;
        } else {
            echo 0;
        }
    }

    public function getInstructions($drug_id) {
        $instructions = "";
        $sql = "SELECT instructions FROM drugcode WHERE id='$drug_id'";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        if ($results) {
            //get values
            $values = $results[0]['instructions'];
            //get instruction names
            if ($values != "") {
                $values = explode(",", $values);
                foreach ($values as $value) {
                    $sql = "SELECT name FROM drug_instructions WHERE id='$value'";
                    $query = $this->db->query($sql);
                    $results = $query->result_array();
                    if ($results) {
                        foreach ($results as $result) {
                            $instructions .= $result['name'] . "\n";
                        }
                    }
                }
            }
        }
        echo ($instructions);
    }

    public function getPrescriptions($patient_ccc = nuull) {
        $prescription = array();
        $sql = "SELECT * FROM drug_prescription WHERE patient_ccc = '$patient_ccc' ORDER BY id DESC LIMIT 1";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        if ($results) {
            $sql = "SELECT * FROM drug_prescription_details WHERE drug_prescriptionid =" . $results[0]['id'];
            $query = $this->db->query($sql);
            $res = $query->result_array();
            if ($res) {
                $prescription = $results[0];
                $prescription['prescription_details'] = $res;
            }
        }
        header('Content-Type: application/json');
        echo json_encode($prescription);
        die;
    }

    public function getPrescription($pid) {
        $data = array();
        $ps_sql = "SELECT dpd.id,drug_prescriptionid,drug_name from drug_prescription dp,drug_prescription_details dpd where
		dp.id = dpd.drug_prescriptionid and dp.id = $pid";
        $query = $this->db->query($ps_sql);
        $ps = $query->result_array();
        $data = $ps;
        // find if possible regimen from prescription
        foreach ($ps as $key => $p) {
            $drugname = $p['drug_name'];
            $regimen_sql = "SELECT  * FROM regimen where regimen_code like '%$drugname%'";
            $r_query = $this->db->query($regimen_sql);
            $rs = $r_query->result_array();
            if ($rs) {
                $data[$key]['prescription_regimen_id'] = $rs[0]['id'];
                $arv_prescription = $p['id'];
                $data['arv_prescription'] = $arv_prescription;
                //Get oi_prescription(s)
                $sql = "SELECT dpd.id from drug_prescription dp,drug_prescription_details dpd where
				dp.id = dpd.drug_prescriptionid and dp.id = $pid and dpd.id != '$arv_prescription'";
                $query = $this->db->query($sql);
                $data['oi_prescription'] = $query->row_array()['id'];
            }
        }
        return $data;
    }

    public function base_params($data) {
        $data['title'] = "webADT | Drug Dispensing";
        $data['banner_text'] = "Facility Dispensing";
        $data['link'] = "dispensements";
        $this->load->view('template', $data);
    }

    public function save_session() {
        $session_name = $this->input->post("session_name", TRUE);
        $session_value = $this->input->post("session_value", TRUE);
        $this->session->set_userdata($session_name, $session_value);

        echo $this->session->userdata($session_name);
    }

}

ob_get_clean();
