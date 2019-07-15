<?php

// create buffer
ob_start();

class Patient_management extends MY_Controller {

    var $api;
    var $patient_module;
    var $dispense_module;
    var $appointment_module;

    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('PHPExcel');
        ini_set("max_execution_time", "100000");
        ini_set('memory_limit', '512M');
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

    public function get_api_values() {
        $this->init_api_values();

        echo "api: " . $this->api . "<br/>";
        echo "patient_module: " . $this->patient_module . "<br/>";
        echo "dispense_module: " . $this->dispense_module . "<br/>";
        echo "appointments_module: " . $this->appointment_module . "<br/>";
        echo "api_adt_url: " . $this->api_adt_url . "<br/>";
    }

    public function index() {
        $source = $this->session->userdata('facility');
        $sql = "SELECT * FROM facilities where facilitycode='$source'";
        $query = $this->db->query($sql);
        $facility_settings = $query->result_array()[0];

        $data['medical_number'] = $facility_settings['medical_number'];
        $data['pill_count'] = $facility_settings['pill_count'];
        $data['content_view'] = "patients/listing_view";
        $this->base_params($data);
    }

    public function merge_list() {
        $data['quick_link'] = "merging";
        $data['title'] = "Patient Merging";
        $data['page_title'] = "Patient Merging Management";
        $data['link'] = "settings_management";
        $data['banner_text'] = "Patient Merging Listing";

        $this->session->set_userdata("link_id", "merge_list");
        $this->session->set_userdata("linkSub", "patient_management/merge_list");

        $this->load->view("patient_merging_v", $data);
    }

    public function get_Last_vl_result($patient_no) {
        //Validate patient_no when use of / to separate mflcode and ccc_no
        $mflcode = $this->uri->segment(3);
        $ccc_no = $this->uri->segment(4);
        if ($ccc_no) {
            $patient_no = $mflcode . '/' . $ccc_no;
        }

        $sql = "SELECT * FROM patient_viral_load WHERE patient_ccc_number = ? ORDER BY test_date DESC LIMIT 1";
        $results = $this->db->query($sql, array($patient_no))->result_array();
        echo json_encode($results);
    }

    public function details() {
        $data['content_view'] = "patient_details_v";
        $data['hide_side_menu'] = 1;
        $this->base_params($data);
    }

    public function addpatient_show() {
        $data = array();
        $data['facility_code'] = $this->session->userdata('facility');
        $sql = "SELECT * FROM facilities where facilitycode=".$data['facility_code'];
        $query = $this->db->query($sql);
        $data['cs'] = $query->result_array()[0]['ccc_separator'];
        $data['districts'] = District::getPOB();
        $data['genders'] = Gender::getAll();
        $data['statuses'] = Patient_Status::getStatus();
        $data['sources'] = Patient_Source::getSources();
        $data['drug_prophylaxis'] = Drug_Prophylaxis::getAll();
        $data['service_types'] = Regimen_Service_Type::getHydratedAll();
        $data['facilities'] = Facilities::getAll();
        $data['family_planning'] = Family_Planning::getAll();
        $data['other_illnesses'] = Other_Illnesses::getAll();
        $data['pep_reasons'] = Pep_Reason::getActive();
        $data['prep_reasons'] = Prep_Reason::getActive();
        $data['drugs'] = Drugcode::getAllEnabled();
        $data['who_stages'] = Who_Stage::getAllHydrated();
        $data['hide_side_menu'] = '1';
        $data['content_view'] = "add_patient_v";
        $this->base_params($data);
    }

    public function checkpatient_no($patient_no) {
        //Variables
        $facility_code = $this->session->userdata('facility');
        $sql = "select * from patient where facility_code='$facility_code' and patient_number_ccc='$patient_no'";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        if ($results) {
            echo json_decode("1");
        } else {
            echo json_decode("0");
        }
    }

    public function merge_spouse($patient_no, $spouse_no) {
        $spousedata = array('primary_spouse' => $patient_no, 'secondary_spouse' => $spouse_no);
        $this->db->insert('spouses', $spousedata);
    }

    public function unmerge_spouse($patient_no) {
        $sql = "DELETE FROM spouses WHERE primary_spouse='$patient_no'";
        $this->db->query($sql);
    }

    public function merge_parent($patient_no, $parent_no) {
        $childdata = array('child' => $patient_no, 'parent' => $parent_no);
        $this->db->insert('dependants', $childdata);
    }

    public function unmerge_parent($patient_no) {
        $sql = "DELETE FROM dependants WHERE child='$patient_no'";
        $this->db->query($sql);
    }

    public function listing() {
        $access_level = $this->session->userdata('user_indicator');
        $facility_code = $this->session->userdata('facility');
        $link = "";
        //Testing, don't judge
        $data = array();
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $aColumns = array('Patient_Number_CCC', 'First_Name', 'Last_Name', 'Other_Name', 'NextAppointment', 'Phone', 'Regimen_Desc', 'Name');

        $iDisplayStart = $this->input->get_post('iDisplayStart', true);
        $iDisplayLength = $this->input->get_post('iDisplayLength', true);
        $iSortCol_0 = $this->input->get_post('iSortCol_0', true);
        $iSortingCols = $this->input->get_post('iSortingCols', true);
        $sSearch = $this->input->get_post('sSearch', true);
        $sEcho = $this->input->get_post('sEcho', true);

        // Paging
        if (isset($iDisplayStart) && $iDisplayLength != '-1') {
            $this->db->limit($this->db->escape_str($iDisplayLength), $this->db->escape_str($iDisplayStart));
        }

        // Ordering
        if (isset($iSortCol_0)) {
            for ($i = 0; $i < intval($iSortingCols); $i++) {
                $iSortCol = $this->input->get_post('iSortCol_' . $i, true);
                $bSortable = $this->input->get_post('bSortable_' . intval($iSortCol), true);
                $sSortDir = $this->input->get_post('sSortDir_' . $i, true);

                if ($bSortable == 'true') {
                    $this->db->order_by($aColumns[intval($this->db->escape_str($iSortCol))], $this->db->escape_str($sSortDir));
                }
            }
        }

        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */

        $j = 0;
        for ($i = 0; $i < count($aColumns); $i++) {
            if ($i >= 1) {
                $j++;
            }
            $bSearchable = $this->input->get_post('bSearchable_' . $j, true);
            $sSearch_ = $this->input->get_post('sSearch_' . $j, true);
            // Individual column filtering
            if (isset($bSearchable) && $bSearchable == 'true' && !empty($sSearch_)) {
                if ($i >= 3 and $i < 5) {
                    $i = $i + 2;
                }
                $col = $aColumns[$i];
                if ($col == 'First_Name') {
                    $value = $this->db->escape_like_str($sSearch_);
                    $where = "(First_Name LIKE '%$value%' OR Last_Name LIKE '%$value%' OR Last_Name LIKE '%$value%')";
                    $this->db->where($where);
                } else {
                    $this->db->like($col, $this->db->escape_like_str($sSearch_));
                }
            }
            if (isset($sSearch) && !empty($sSearch)) {
                $this->db->or_like($aColumns[$i], $this->db->escape_like_str($sSearch));
            }
        }



        // Select Data
        $this->db->select('SQL_CALC_FOUND_ROWS ' . str_replace(' , ', ' ', implode(', ', $aColumns)), false);

        $this->db->select("p.id,p.Patient_Number_CCC,p.First_Name,p.Last_Name,p.Other_Name,p.NextAppointment,p.phone as Phone,r.Regimen_Desc,s.Name,p.Active");
        $this->db->from("patient p");
        $this->db->where("p.Facility_Code", $facility_code);
        $this->db->join("regimen r", "r.id=p.Current_Regimen", "left");
        $this->db->join("patient_status s", "s.id=p.current_status", "left");

        $rResult = $this->db->get();
        //echo $this->db->last_query();die();
        // Data set length after filtering
        $this->db->select('FOUND_ROWS() AS found_rows');
        $iFilteredTotal = $this->db->get()->row()->found_rows;

        // Total data set length
        $this->db->select("p.*");
        $this->db->from("patient p");
        $this->db->where("p.Facility_Code", $facility_code);
        $this->db->join("regimen r", "r.id=p.Current_Regimen", "left");
        $this->db->join("patient_status s", "s.id=p.current_status", "left");
        $tot_patients = $this->db->get();
        $iTotal = count($tot_patients->result_array());

        // Output
        $msg = array('sEcho' => intval($sEcho), 'iTotalRecords' => $iTotal, 'iTotalDisplayRecords' => $iFilteredTotal, 'aaData' => array());

        foreach ($rResult->result_array() as $aRow) {
            $row = array();
            $col = 0;
            $name = "";
            $id = "";
            foreach ($aColumns as $col) {
                if ($col == "First_Name" or $col == "Last_Name" or $col == "Other_Name") {
                    if ($col == "First_Name") {
                        $name = $aRow[$col] . " ";
                        $name = strtoupper($name);
                        continue;
                    } else {
                        if ($col == "Last_Name") {
                            $name .= $aRow[$col] . " ";
                            $name = strtoupper($name);
                            continue;
                        } else if ($col == "Other_Name") {
                            $name .= $aRow[$col];
                            $name = strtoupper($name);
                            $name = "<span style='white-space:nowrap;'>" . $name . "</span>";
                        }
                    }
                } else if ($col == "Date_Enrolled") {
                    $name = date('d-M-Y', strtotime($aRow[$col]));
                } else if ($col == "NextAppointment") {
                    if ($aRow[$col]) {
                        $name = date('d-M-Y', strtotime($aRow[$col]));
                    } else {
                        $name = "N/A";
                    }
                }
                //Check if phone No does not exist
                else if ($col == "Phone") {
                    $name = str_replace(" ", "", $aRow['Phone']);
                    if ($aRow[$col] == "") {
                        //$name = str_replace(" ", "", $aRow['Physical']);
                    } else {
                        //$name = str_replace(" ", "", $aRow['Phone']);
                    }
                } else if ($col == "Regimen_Desc") {
                    $name = "<b style='white-space:nowrap;'>" . $aRow[$col] . "</b>";
                } else if ($col == "Name") {
                    $name = "<b>" . $aRow[$col] . "</b>";
                } else {
                    $name = $aRow[$col];
                    $name = strtoupper($name);
                }

                $row[] = $name;
            }
            $id = $aRow['id'];
            $link = "";
            if ($access_level == "facility_administrator") {
                if ($aRow['Active'] == 1) {
                    $link = '| <a href="' . base_url() . 'patient_management/disable/' . $id . '" class="red actual">Disable</a>';
                } else {
                    $link = '| <a href="' . base_url() . 'patient_management/enable/' . $id . '" class="green actual">Enable</a>';
                }
            }

            if ($aRow['Active'] == 1) {
                $row[] = '<a href="' . base_url() . 'patient_management/viewDetails/' . $id . '">Detail</a> | <a href="' . base_url() . 'patient_management/edit/' . $id . '">Edit</a> ' . $link;
            } else {
                $link = str_replace("|", "", $link);
                $link .= '| <a href="' . base_url() . 'patient_management/delete/' . $id . '" class="red actual">Delete</a>';
                $row[] = $link;
            }

            $msg['aaData'][] = $row;
        }
        echo json_encode($msg, JSON_PRETTY_PRINT);
    }

    public function extract_illness($illness_list = "") {
        $illness_array = explode(",", $illness_list);
        $new_array = array();
        foreach ($illness_array as $index => $illness) {
            if ($illness == null) {
                unset($illness_array[$index]);
            } else {
                $illness = str_replace("\n", "", $illness);
                $new_array[] = trim($illness);
            }
        }
        return json_encode($new_array);
    }

    public function viewDetails($record_no) {
        $this->session->set_userdata('record_no', $record_no);
        $patient = "";
        $facility = "";
        $sql = "SELECT p.*,
                       rst.Name as service_name,
                       dp.child,
                       s.secondary_spouse 
                FROM patient p 
                LEFT JOIN regimen_service_type rst ON rst.id=p.service 
                LEFT JOIN dependants dp ON p.patient_number_ccc=dp.parent  
                LEFT JOIN spouses s ON p.patient_number_ccc=s.primary_spouse 
                WHERE p.id='$record_no'
                GROUP BY p.id";
        $query = $this->db->query($sql);
        $results = $query->result_array();

        $depdendant_msg = "";
        if ($results) {
            $results[0]['other_illnesses'] = $this->extract_illness($results[0]['other_illnesses']);
            $data['results'] = $results;
            $patient = $results[0]['patient_number_ccc'];
            $facility = $this->session->userdata("facility");
            //Check dependedants/spouse status
            $child = $results[0]['child'];
            $spouse = $results[0]['secondary_spouse'];
            $patient_name = strtoupper($results[0]['first_name'] . ' ' . $results[0]['last_name']);
            if ($child != NULL) {

                $pat = $this->getDependentStatus($child);
                if ($pat != '') {
                    $depdendant_msg .= "Patient $patient_name\'s dependant " . $pat . " is lost to follow up ";
                }
            }
            if ($spouse != NULL) {
                $pat = $this->getDependentStatus($spouse);
                if ($pat != '') {
                    $depdendant_msg .= "Patient $patient_name\'s spouse " . $pat . " is lost to follow up ";
                }
            }
        }
        //Patient History
        $sql = "SELECT pv.dispensing_date,
                         v.name AS visit, 
                         u.Name AS unit, 
                         pv.dose, 
                         pv.duration, 
                         pv.indication, 
                         pv.patient_visit_id AS record, 
                         d.drug, 
                         pv.quantity, 
                         pv.current_weight, 
                         pv.current_height, 
                         r1.regimen_desc as last_regimen, 
                         r.regimen_desc, 
                         pv.batch_number, 
                         pv.pill_count, 
                         pv.adherence, 
                         pv.user, 
                         rcp.name as regimen_change_reason 
                FROM v_patient_visits pv 
                    LEFT JOIN drugcode d ON pv.drug_id = d.id 
                    LEFT JOIN drug_unit u ON d.unit = u.id 
                    LEFT JOIN regimen r ON pv.regimen_id = r.id 
                    LEFT JOIN regimen r1 ON pv.last_regimen = r1.id 
                    LEFT JOIN visit_purpose v ON pv.visit_purpose_id = v.id 
                    LEFT JOIN regimen_change_purpose rcp ON rcp.id=pv.regimen_change_reason
                WHERE pv.patient_id = '$patient' 
                AND pv.facility =  '$facility' 
                AND pv.active='1' AND pv.pv_active='1'
                GROUP BY d.drug,pv.dispensing_date
                ORDER BY  pv.patient_visit_id DESC";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        if ($results) {
            $data['history_logs'] = $results;
        } else {
            $data['history_logs'] = "";
        }
        $data['dependant_msg'] = $depdendant_msg;
        $data['districts'] = District::getPOB();
        $data['genders'] = Gender::getAll();
        $data['statuses'] = Patient_Status::getStatus();
        $data['sources'] = Patient_Source::getSources();
        $data['drug_prophylaxis'] = Drug_Prophylaxis::getAll();
        $data['service_types'] = Regimen_Service_Type::getHydratedAll();
        $data['facilities'] = Facilities::getAll();
        $data['family_planning'] = Family_Planning::getAll();
        $data['other_illnesses'] = Other_Illnesses::getAll();
        $data['pep_reasons'] = Pep_Reason::getActive();
        $data['drugs'] = Drugcode::getEnabledDrugs();
        $data['regimens'] = Regimen::getRegimens();
        $data['who_stages'] = Who_Stage::getAllHydrated();
        $data['content_view'] = 'patient_details_v';
        //Hide side menus
        $data['hide_side_menu'] = '1';
        $this->base_params($data);
    }

    public function getFacililtyAge() {
        $facility_code = $this->session->userdata('facility');
        $get_adult_age_sql = $this->db->query("SELECT adult_age FROM facilities where facilitycode='$facility_code'");
        $get_adult_age_array = $get_adult_age_sql->row_array();

        return $get_adult_age_array['adult_age'];
    }

    public function edit($record_no) {
        $sql = "SELECT p.*,
                       rst.Name as service_name,
                       dp.parent,
                       s.secondary_spouse,
                       t.* 
                       FROM patient p 
                       LEFT JOIN regimen_service_type rst ON rst.id=p.service 
                       LEFT JOIN dependants dp ON p.patient_number_ccc=dp.child  
                       LEFT JOIN spouses s ON p.patient_number_ccc=s.primary_spouse
                       LEFT JOIN (
                            SELECT 
                                patient_id, prep_reason_id AS prep_reason, is_tested AS prep_test_answer, test_date AS prep_test_date, test_result AS prep_test_result 
                            FROM patient_prep_test 
                            WHERE patient_id = ?
                            ORDER BY test_date DESC
                            LIMIT 1
                        ) t ON t.patient_id = p.id
                       WHERE p.id = ?
                       GROUP BY p.id";
        $query = $this->db->query($sql, array($record_no, $record_no));
        $results = $query->result_array();


        if ($results) {
            $results[0]['other_illnesses'] = $this->extract_illness($results[0]['other_illnesses']);
            $data['results'] = $results;
        }

        $data['facility_code'] = $this->session->userdata('facility');
                $data['facility_code'] = $this->session->userdata('facility');
        $sql = "SELECT * FROM Facilities where facilitycode=".$data['facility_code'];
        $query = $this->db->query($sql);
        $data['cs'] = $query->result_array()[0]['ccc_separator'];

        
        $data['record_no'] = $record_no;
        $data['facility_adult_age'] = $this->getFacililtyAge();
        $data['districts'] = District::getPOB();
        $data['genders'] = Gender::getAll();
        $data['statuses'] = Patient_Status::getStatus();
        $data['sources'] = Patient_Source::getSources();
        $data['drug_prophylaxis'] = Drug_Prophylaxis::getAll();
        $data['service_types'] = Regimen_Service_Type::getHydratedAll();
        $data['facilities'] = Facilities::getAll();
        $data['family_planning'] = Family_Planning::getAll();
        $data['other_illnesses'] = Other_Illnesses::getAll();
        $data['pep_reasons'] = Pep_Reason::getActive();
        $data['prep_reasons'] = Prep_Reason::getActive();
        $data['regimens'] = Regimen::getRegimens();
        $data['drugs'] = Drugcode::getAllEnabled();
        $data['who_stages'] = Who_Stage::getAllHydrated();
        $data['content_view'] = 'edit_patients_v';
        //Hide side menus
        $data['hide_side_menu'] = '1';
        $this->base_params($data);
    }

    function requiredFields($ccid) {
        $required = '';
        $status = 0;
        $result = $this->db->where('id', $ccid)->get('patient')->result();
        $mandatory = [
            'patient_number_ccc', 'first_name', 'dob', 'gender', 'pregnant',
            'height', 'weight', 'date_enrolled', 'start_regimen', 'source', 'service'
        ];
        $label = [
            'Patient CCC No.', 'First Name', 'Date of Birth', 'Gender', 'Pregnancy Status', 
            'Height', 'Weight', 'Enrollment Date', 'Date Regimen Started', 'Source of patient', 'Service'
        ];
        $i = 0;
        foreach ($mandatory as $r) {
            // echo $r ." => ".$result[0]->$r ."<br>";
            if (trim($result[0]->$r) == '') {
                $required .= $label[$i] . ", ";
                $status = 1;
            }
            $i++;
        }

        echo json_encode(['status' => $status, 'fields' => rtrim($required, ',')]);
    }

    public function report() {

        $content_view = 'patient_report_v';
        $data['transfered'] = $this->loadChoices('patient_source','name');
        $data['service'] = $this->loadChoices('regimen_service_type','name');
        $data['startreg'] = $this->loadChoices('regimen','regimen_desc');
        $data['currstat'] = $this->loadChoices('patient_status','Name');

        //$data['hide_side_menu'] = 1;  
        $data['content_view'] = $content_view;
        $this->base_params($data);
    }

    public function save() {
        $this->init_api_values();
        $family_planning = "";
        $other_illness_listing = "";
        $other_allergies_listing = "";
        $patient = "";

        $family_planning = $this->input->post('family_planning_holder', TRUE);
        if ($family_planning == null) {
            $family_planning = "";
        }
        $drug_prophylaxis = $this->input->post('drug_prophylaxis_holder', TRUE);
        if ($drug_prophylaxis == null) {
            $drug_prophylaxis = "";
        }
        $other_illness_listing = $this->input->post('other_illnesses_holder', TRUE);
        if ($other_illness_listing == null) {
            $other_illness_listing = "";
        }
        $other_chronic = $this->input->post('other_chronic', TRUE);
        if ($other_chronic != "") {
            if ($other_illness_listing) {
                $other_illness_listing = $other_illness_listing . "," . $other_chronic;
            } else {
                $other_illness_listing = $other_chronic;
            }
        }
        //Other allergies
        $other_allergies_list = $this->input->post('other_allergies_listing', TRUE);
        //List of drug allergies.
        $drug_allergies = $this->input->post('drug_allergies_holder', TRUE);
        if ($drug_allergies == null) {
            $drug_allergies = "";
        }

        if ($drug_allergies != "") {
            if ($other_allergies_list) {
                $other_allergies_listing = $other_allergies_list . "," . $drug_allergies;
            } else {
                $other_allergies_listing = $drug_allergies;
            }
        } else {
            $other_allergies_listing = $other_allergies_list;
        }

        //Patient Information & Demographics
        $new_patient = new Patient();
        $new_patient->Medical_Record_Number = $this->input->post('medical_record_number', TRUE);
        $new_patient->Patient_Number_CCC = $this->input->post('patient_number', TRUE);
        $new_patient->First_Name = $this->input->post('first_name', TRUE);
        $new_patient->Last_Name = $this->input->post('last_name', TRUE);
        $new_patient->Other_Name = $this->input->post('other_name', TRUE);
        $new_patient->Dob = $this->input->post('dob', TRUE);
        $new_patient->Pob = $this->input->post('pob', TRUE);
        $new_patient->Gender = $this->input->post('gender', TRUE);
        $new_patient->Pregnant = $this->input->post('pregnant', TRUE);
        $new_patient->Breastfeeding = $this->input->post('breastfeeding', TRUE);
        $new_patient->Start_Weight = $this->input->post('weight', TRUE);
        $new_patient->Start_Height = $this->input->post('height', TRUE);
        $new_patient->Start_Bsa = $this->input->post('surface_area', TRUE);
        $new_patient->Start_Bmi = $this->input->post('start_bmi', TRUE);
        $new_patient->Weight = $this->input->post('weight', TRUE);
        $new_patient->Height = $this->input->post('height', TRUE);
        $new_patient->Sa = $this->input->post('surface_area', TRUE);
        $new_patient->Bmi = $this->input->post('start_bmi', TRUE);
        $new_patient->Phone = $this->input->post('phone', TRUE);
        $new_patient->SMS_Consent = $this->input->post('sms_consent', TRUE);
        $new_patient->Physical = $this->input->post('physical', TRUE);
        $new_patient->Alternate = $this->input->post('alternate', TRUE);
        $new_patient->differentiated_care = $this->input->post('differentiated_care', TRUE);


        //Patient History
        $new_patient->Partner_Status = $this->input->post('partner_status', TRUE);
        $new_patient->Disclosure = $this->input->post('disclosure', TRUE);
        $new_patient->Fplan = $family_planning;
        $new_patient->Other_Illnesses = $other_illness_listing;
        $new_patient->Other_Drugs = $this->input->post('other_drugs', TRUE);
        $new_patient->Adr = $other_allergies_listing;
        //other drug allergies
        $new_patient->Support_Group = $this->input->post('support_group_listing', TRUE);
        $new_patient->Smoke = $this->input->post('smoke', TRUE);
        $new_patient->Alcohol = $this->input->post('alcohol', TRUE);
        $new_patient->Tb = $this->input->post('tb', TRUE);
        $new_patient->tb_category = $this->input->post('tbcategory', TRUE);
        $new_patient->Tbphase = $this->input->post('tbphase', TRUE);
        $new_patient->Startphase = $this->input->post('fromphase', TRUE);
        $new_patient->Endphase = $this->input->post('tophase', TRUE);

        //Program Information
        $new_patient->Date_Enrolled = $this->input->post('enrolled', TRUE);
        $new_patient->Current_Status = $this->input->post('current_status', TRUE);
        $new_patient ->Status_Change_Date = $this -> input -> post('service_started', TRUE);
        $new_patient->Source = $this->input->post('source', TRUE);
        $new_patient->Transfer_From = $this->input->post('transfer_source', TRUE);
        $new_patient->drug_prophylaxis = $this->input->post('drug_prophylaxis', TRUE);
        $new_patient->Facility_Code = $this->session->userdata('facility');
        $new_patient->Service = $this->input->post('service', TRUE);
        $new_patient->Start_Regimen = $this->input->post('regimen', TRUE);
        $new_patient->Current_Regimen = $this->input->post('regimen', TRUE);
        $new_patient->Start_Regimen_Date = $this->input->post('service_started', TRUE);
        $new_patient->Tb_Test = $this->input->post('tested_tb', TRUE);
        $new_patient->Pep_Reason = $this->input->post('pep_reason', TRUE);
        $new_patient->who_stage = $this->input->post('who_stage', TRUE);
        $new_patient->drug_prophylaxis = $drug_prophylaxis;
        $new_patient->isoniazid_start_date = $this->input->post('iso_start_date', TRUE);
        $new_patient->isoniazid_end_date = $this->input->post('iso_end_date', TRUE);

        $spouse_no = $this->input->post('match_spouse');
        $patient_no = $this->input->post('patient_number');
        $child_no = $this->input->post('match_parent');

        $new_patient->save();
        //Map patient to spouse
        if ($spouse_no != NULL) {
            $this->merge_spouse($patient_no, $spouse_no);
        }
        //Map child to parent/guardian
        if ($child_no != NULL) {
            $this->merge_parent($patient_no, $child_no);
        }

        $sql = "SELECT MAX(id) as id FROM patient";
        $query = $this->db->query($sql);
        $result = $query->result_array();
        $auto_id = $result[0]['id'];

        //Add Prep Data
        $is_tested = $this->input->post('prep_test_answer', TRUE);
        $prep_test_data = array(
            'patient_id' => $auto_id,
            'prep_reason_id' => $this->input->post('prep_reason', TRUE),
            'is_tested' => $is_tested,
            'test_date' => $this->input->post('prep_test_date', TRUE),
            'test_result' => $this->input->post('prep_test_result', TRUE),
        );
        //Only 'Save' for those tested
        if ($is_tested) {
            $this->db->insert('patient_prep_test', $prep_test_data);
        }

        $patient = $this->input->post('patient_number', TRUE);
        $direction = $this->input->post('direction', TRUE);

        if ($this->api && $this->patient_module) {
            // post to IL via API
            file_get_contents(base_url() . 'tools/api/getPatient/' . $auto_id . '/ADD');
            // /> POST TO IL VIA API
        }

        if ($direction == 0) {
            $this->session->set_userdata('msg_save_transaction', 'success');
            $this->session->set_flashdata('dispense_updated', 'Patient: ' . $this->input->post('first_name', TRUE) . " " . $this->input->post('last_name', TRUE) . ' was Saved');
            redirect("patient_management");
        } else if ($direction == 1) {
            redirect("dispensement_management/dispense/$auto_id");
        }
    }

    public function getDependentStatus($patient_number_ccc) {
        $sql = "SELECT ps.name,p.patient_number_ccc,p.first_name,p.last_name,p.other_name FROM patient p
                INNER JOIN patient_status ps ON ps.id = p.current_status
                AND p.patient_number_ccc='$patient_number_ccc'
                AND ps.name LIKE '%lost%'";
        $query = $this->db->query($sql);
        $result = $query->result_array();
        if (count($result) > 0) {
            $patient = '<b>' . strtoupper($result[0]['first_name'] . ' ' . $result[0]['last_name'] . ' ' . $result[0]['other_name']) . '</b> ( CCC Number:' . $result[0]['patient_number_ccc'] . ')';
            return $patient;
        } else {
            return '';
        }
    }

    public function update($record_id) {
        $this->init_api_values();
        $family_planning = "";
        $other_illness_listing = "";
        $other_allergies_listing = "";
        $prev_appointment = "";
        $facility = "";
        $patient = "";

        //Check if appointment exists
        $prev_appointment = $this->input->post('prev_appointment_date', TRUE);
        $appointment = $this->input->post('next_appointment_date', TRUE);
        $prev_clinicalappointment = $this->input->post('prev_clinical_appointment_date', TRUE);
        $clinicalappointment = $this->input->post('next_clinical_appointment_date', TRUE);



        $facility = $this->session->userdata('facility');
        $patient = $this->input->post('patient_number', TRUE);

        if ($appointment) {
            $sql = "select * from patient_appointment where patient='$patient' and appointment='$prev_appointment' and facility='$facility'";
            $query = $this->db->query($sql);
            $results = $query->result_array();

            if ($results) {
                $record_no = $results[0]['id'];
                //If exisiting appointment(Update new Record)
                $sql = "update patient_appointment set appointment='$appointment',patient='$patient',facility='$facility' where id='$record_no'";
            } else {
                //If no appointment(Insert new record)
                $sql = "insert patient_appointment(patient,appointment,facility)VALUES('$patient','$appointment','$facility')";
            }
            $this->db->query($sql);
        }

        if ($clinicalappointment) {
            $sql = "select * from clinic_appointment where patient='$patient' and appointment='$prev_clinicalappointment' and facility='$facility'";
            $query = $this->db->query($sql);
            $results = $query->result_array();

            if ($results) {
                $record_no = $results[0]['id'];

                //If exisiting appointment(Update new Record)
                $sql = "update clinic_appointment set appointment='$clinicalappointment' where id='$record_no'";
            } else {
                //If no appointment(Insert new record)
                $sql = "insert clinic_appointment(patient,appointment,facility)VALUES('$patient','$appointment','$facility')";
            }
            $this->db->query($sql);
        }

        $family_planning = $this->input->post('family_planning_holder', TRUE);
        if ($family_planning == null) {
            $family_planning = "";
        }
        $drug_prophylaxis = $this->input->post('drug_prophylaxis_holder', TRUE);
        if ($drug_prophylaxis == null) {
            $drug_prophylaxis = "";
        }
        $other_illness_listing = $this->input->post('other_illnesses_holder', TRUE);
        if ($other_illness_listing == null) {
            $other_illness_listing = "";
        }
        $other_chronic = $this->input->post('other_chronic', TRUE);
        if ($other_chronic != "") {
            if ($other_illness_listing) {
                $other_illness_listing = $other_illness_listing . "," . $other_chronic;
            } else {
                $other_illness_listing = $other_chronic;
            }
        }
        //Other allergies
        $other_allergies_list = $this->input->post('other_allergies_listing', TRUE);
        //List of drug allergies.
        $drug_allergies = $this->input->post('drug_allergies_holder', TRUE);
        if ($drug_allergies == null) {
            $drug_allergies = "";
        }

        if ($drug_allergies != "") {
            if ($other_allergies_list) {
                $other_allergies_listing = $other_allergies_list . "," . $drug_allergies;
            } else {
                $other_allergies_listing = $drug_allergies;
            }
        } else {
            $other_allergies_listing = $other_allergies_list;
        }

        $other_drugs = $this->input->post('other_drugs', TRUE);
        if (!$other_drugs) {
            $other_drugs = "";
        }


        $data = array(
            'drug_prophylaxis' => $drug_prophylaxis,
            'isoniazid_start_date' => $this->input->post('iso_start_date', TRUE),
            'isoniazid_end_date' => $this->input->post('iso_end_date', TRUE),
            'tb_test' => $this->input->post('tested_tb', TRUE),
            'who_stage' => $this->input->post('who_stage', TRUE),
            'pep_reason' => $this->input->post('pep_reason', TRUE),
            'Medical_Record_Number' => $this->input->post('medical_record_number', TRUE),
            'Patient_Number_CCC' => $this->input->post('patient_number', TRUE),
            'First_Name' => $this->input->post('first_name', TRUE),
            'Last_Name' => $this->input->post('last_name', TRUE),
            'Other_Name' => $this->input->post('other_name', TRUE),
            'Dob' => $this->input->post('dob', TRUE),
            'Pob' => $this->input->post('pob', TRUE),
            'Gender' => $this->input->post('gender', TRUE),
            'pregnant' => $this->input->post('pregnant', TRUE),
            'breastfeeding' => $this->input->post('breastfeeding', TRUE),
            'Start_Weight' => $this->input->post('start_weight', TRUE),
            'Start_Height' => $this->input->post('start_height', TRUE),
            'Start_Bsa' => $this->input->post('start_bsa', TRUE),
            'Weight' => $this->input->post('current_weight', TRUE),
            'Height' => $this->input->post('current_height', TRUE),
            'Sa' => $this->input->post('bsa', TRUE),
            'Bmi' => $this->input->post('bmi', TRUE),
            'Phone' => $this->input->post('phone', TRUE),
            'SMS_Consent' => $this->input->post('sms_consent', TRUE),
            'Physical' => $this->input->post('physical', TRUE),
            'Alternate' => $this->input->post('alternate', TRUE),
            'Partner_Status' => $this->input->post('partner_status', TRUE),
            'Disclosure' => $this->input->post('disclosure', TRUE),
            'Fplan' => $family_planning,
            'differentiated_care' => $this->input->post('differentiated_care', TRUE),
            'clinicalappointment' => $this->input->post('next_clinical_appointment_date', TRUE),
            'breastfeeding' => $this->input->post('breastfeeding', TRUE),
            'Other_Illnesses' => $other_illness_listing,
            'Other_Drugs' => $other_drugs,
            'Adr' => $other_allergies_listing,
            'Smoke' => $this->input->post('smoke', TRUE),
            'Alcohol' => $this->input->post('alcohol', TRUE),
            'Tb' => $this->input->post('tb', TRUE),
            'tb_category' => $this->input->post('tbcategory', TRUE),
            'Tbphase' => $this->input->post('tbphase', TRUE),
            'Startphase' => $this->input->post('fromphase', TRUE),
            'Endphase' => $this->input->post('tophase', TRUE),
            'Date_Enrolled' => $this->input->post('enrolled', TRUE),
            'Current_Status' => $this->input->post('current_status', TRUE),
            'status_change_date' => $this->input->post('status_started', TRUE),
            'Source' => $this->input->post('source', TRUE),
            'Transfer_From' => $this->input->post('transfer_source', TRUE),
            'Supported_By' => $this->input->post('support', TRUE),
            'Facility_Code' => $this->session->userdata('facility'),
            'Service' => $this->input->post('service', TRUE),
            'Start_Regimen' => $this->input->post('regimen', TRUE),
            'Start_Regimen_Date' => $this->input->post('service_started', TRUE),
            'Current_Regimen' => $this->input->post('current_regimen', TRUE),
            'Nextappointment' => $this->input->post('next_appointment_date', TRUE));
        // echo "<pre>";
        $status_change_query = " insert into change_log (old_value,new_value,facility,patient,change_type)
        select current_status
        ,'" . $this->input->post('current_status', TRUE) . "' 
        ,'" . $this->session->userdata('facility') . "'
        ,'" . $this->input->post('patient_number', TRUE) . "',
        'status' from patient where patient_number_ccc  = '"
                . $this->input->post('patient_number', TRUE) . "' and current_status != '"
                . $this->input->post('current_status', TRUE) . "'";

        $service_change_query = " insert into change_log (old_value,new_value,facility,patient,change_type)
        select service
        ,'" . $this->input->post('service', TRUE) . "' 
        ,'" . $this->session->userdata('facility') . "'
        ,'" . $this->input->post('patient_number', TRUE) . "',
        'service' from patient where patient_number_ccc  = '"
                . $this->input->post('patient_number', TRUE) . "' and service !='"
                . $this->input->post('service', TRUE) . "'";

        $this->db->query($service_change_query);
        $this->db->query($status_change_query);

        $this->db->update('patients');
        $this->db->where('id', $record_id);
        $this->db->update('patient', $data);


        $spouse_no = $this->input->post('match_spouse');
        $patient_no = $this->input->post('patient_number');
        $child_no = $this->input->post('match_parent');
        //Map patient to spouse but unmap all for this patient to remove duplicates
        if ($spouse_no != NULL) {
            $this->unmerge_spouse($patient_no);
            $this->merge_spouse($patient_no, $spouse_no);
        }
        //Map child to parent/guardian but unmap all for this patient to remove duplicates
        if ($child_no != NULL) {
            $this->unmerge_parent($patient_no);
            $this->merge_parent($patient_no, $child_no);
        }
        //Update/Insert Test Data
        $is_tested = $this->input->post('prep_test_answer', TRUE);
        $test_data = array(
            'patient_id' => $record_id,
            'prep_reason_id' => $this->input->post('prep_reason', TRUE),
            'is_tested' => $is_tested,
            'test_date' => $this->input->post('prep_test_date', TRUE),
            'test_result' => $this->input->post('prep_test_result', TRUE)
        );

        if ($is_tested) {
            $this->updateTestData($test_data);
        }

        //Set session for notications
        $this->session->set_userdata('msg_save_transaction', 'success');
        $this->session->set_userdata('user_updated', $this->input->post('first_name'));

        if ($this->api && $this->patient_module) {
            // post to IL via API
            file_get_contents(base_url() . 'tools/api/getPatient/' . $record_id . '/EDIT');
            // /> POST TO IL VIA API
        }

        redirect("patient_management/load_view/details/$record_id");
    }

    public function updateTestData($test_data = array()) {
        $prev_test_data = $this->db->get_where('patient_prep_test', $test_data)->row_array();
        if (empty($prev_test_data)) {
            $this->db->insert('patient_prep_test', $test_data);
        }
    }

    public function update_visit() {
        $original_patient_number = $this->input->post("original_patient_number", TRUE);
        $patient_number = $this->input->post("patient_number", TRUE);
        //update patient visits
        $this->db->where('patient_id', $original_patient_number);
        $this->db->update('patient_visit', array("patient_id" => $patient_number));
        //update spouses
        $this->unmerge_spouse($original_patient_number);
        //update dependants
        $this->unmerge_parent($original_patient_number);
    }

    public function base_params($data) {
        $data['title'] = "webADT | Patients";
        $data['banner_text'] = "Facility Patients";
        $data['link'] = "patients";
        $this->load->view('template', $data);
    }

    public function create_timestamps() {
        $visits = Patient_Visit::getAll();
        foreach ($visits as $visit) {
            $current_date = $visit->Dispensing_Date;
            $changed_date = strtotime($current_date);
            $visit->Dispensing_Date_Timestamp = $changed_date;
            $visit->save();
        }
    }

    public function regimen_breakdown() {
        $selected_facility = $this->input->post('facility');
        if (isset($selected_facility)) {
            $facility = $this->input->post('facility');
        }
        $data = array();
        $data['current'] = "patient_management";
        $data['title'] = "webADT | Patient Regimen Breakdown";
        $data['content_view'] = "patient_regimen_breakdown_v";
        $data['banner_text'] = "Patient Regimen Breakdown";
        $data['facilities'] = Reporting_Facility::getAll();
        //Get the regimen data
        $data['optimal_regimens'] = Regimen::getOptimalityRegimens("1");
        $data['sub_optimal_regimens'] = Regimen::getOptimalityRegimens("2");
        $months = 12;
        $months_previous = 11;
        $regimen_data = array();
        for ($current_month = 1; $current_month <= $months; $current_month++) {
            $start_date = date("Y-m-01", strtotime("-$months_previous months"));
            $end_date = date("Y-m-t", strtotime("-$months_previous months"));
            //echo $start_date." to ".$end_date."</br>";
            if ($facility) {
                $get_month_statistics_sql = "SELECT regimen,count(patient_id) as patient_numbers,sum(months_of_stock) as months_of_stock FROM (select  distinct patient_id,months_of_stock,regimen,dispensing_date from `patient_visit` where facility = '" . $facility . "' and  dispensing_date between str_to_date('" . $start_date . "','%Y-%m-%d') and str_to_date('" . $end_date . "','%Y-%m-%d')) patient_visits group by regimen";
            } else {
                $get_month_statistics_sql = "SELECT regimen,count(patient_id) as patient_numbers,sum(months_of_stock) as months_of_stock FROM (select  distinct patient_id,months_of_stock,regimen,dispensing_date from `patient_visit` where dispensing_date between str_to_date('" . $start_date . "','%Y-%m-%d') and str_to_date('" . $end_date . "','%Y-%m-%d')) patient_visits group by regimen";
            }
            $month_statistics_query = $this->db->query($get_month_statistics_sql);
            foreach ($month_statistics_query->result_array() as $month_data) {
                $regimen_data[$month_data['regimen']][$start_date] = array("patient_numbers" => $month_data['patient_numbers'], "mos" => $month_data['months_of_stock']);
            }
            //echo $get_month_statistics_sql . "<br>";
            $months_previous--;
        }
        $data['regimen_data'] = $regimen_data;
        $this->load->view("platform_template", $data);
    }

    public function create_appointment_timestamps() {
        /* $appointments = Patient_Appointment::getAll();
          foreach($appointments as $appointment){
          $app_date = $appointment->Appointment;
          $changed_date = strtotime($app_date);
          //echo $app_date." currently becomes ".$changed_date." which was initially ".date("m/d/Y",$changed_date)."<br>";
          $appointment->Appointment = $changed_date;
          $appointment->save();
          } */
    }

    public function export() {
        $facility_code = $this->session->userdata('facility');
        $sql = "SELECT medical_record_number,patient_number_ccc,first_name,last_name,other_name,dob,pob,IF(gender=1,'MALE','FEMALE')as gender,IF(pregnant=1,'YES','NO')as pregnant,weight as Current_Weight,height as Current_height,sa as Current_BSA,p.phone,physical as Physical_Address,alternate as Alternate_Address,other_illnesses,other_drugs,adr as Drug_Allergies,IF(tb=1,'YES','NO')as TB,IF(smoke=1,'YES','NO')as smoke,IF(alcohol=1,'YES','NO')as alcohol,date_enrolled,ps.name as Patient_source,s.Name as supported_by,timestamp,facility_code,rst.name as Service,r1.regimen_desc as Start_Regimen,start_regimen_date,pst.Name as Current_status,migration_id,machine_code,IF(sms_consent=1,'YES','NO') as SMS_Consent,fplan as Family_Planning,tbphase,startphase,endphase,IF(partner_status=1,'Concordant',IF(partner_status=2,'Discordant','')) as partner_status,status_change_date,IF(partner_type=1,'YES','NO') as Disclosure,support_group,r.regimen_desc as Current_Regimen,nextappointment,start_height,start_weight,start_bsa,IF(p.transfer_from !='',f.name,'N/A') as Transfer_From,DATEDIFF(nextappointment,CURDATE()) AS Days_to_NextAppointment,dp.name as prophylaxis
                FROM patient p
                left join regimen r on r.id=p.current_regimen
                left join regimen r1 on r1.id=p.start_regimen
                left join patient_source ps on ps.id=p.source
                left join supporter s on s.id=p.supported_by
                left join regimen_service_type rst on rst.id=p.service
                left join patient_status pst on pst.id=p.current_status
                left join facilities f on f.facilitycode=p.transfer_from
                left join drug_prophylaxis dp on dp.id=p.drug_prophylaxis
                WHERE facility_code='$facility_code'
                ORDER BY p.patient_number_ccc ASC";
        // echo $sql;die();
        $query = $this->db->query($sql);
        $results = $query->result_array();

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $i = 1;

        $objPHPExcel->getActiveSheet()->SetCellValue('A' . $i, "medical_record_number");
        $objPHPExcel->getActiveSheet()->SetCellValue('B' . $i, "patient_number_ccc");
        $objPHPExcel->getActiveSheet()->SetCellValue('C' . $i, "first_name");
        $objPHPExcel->getActiveSheet()->SetCellValue('D' . $i, "last_name");
        $objPHPExcel->getActiveSheet()->SetCellValue('E' . $i, "other_name");
        $objPHPExcel->getActiveSheet()->SetCellValue('F' . $i, "dob");
        $objPHPExcel->getActiveSheet()->SetCellValue('G' . $i, "pob");
        $objPHPExcel->getActiveSheet()->SetCellValue('H' . $i, "gender");
        $objPHPExcel->getActiveSheet()->SetCellValue('I' . $i, "pregnant");
        $objPHPExcel->getActiveSheet()->SetCellValue('J' . $i, "Current_Weight");
        $objPHPExcel->getActiveSheet()->SetCellValue('K' . $i, "Current_height");
        $objPHPExcel->getActiveSheet()->SetCellValue('L' . $i, "Current_BSA");
        $objPHPExcel->getActiveSheet()->SetCellValue('M' . $i, "phone");
        $objPHPExcel->getActiveSheet()->SetCellValue('N' . $i, "Physical_Address");
        $objPHPExcel->getActiveSheet()->SetCellValue('O' . $i, "Alternate_Address");
        $objPHPExcel->getActiveSheet()->SetCellValue('P' . $i, "other_illnesses");
        $objPHPExcel->getActiveSheet()->SetCellValue('Q' . $i, "other_drugs");
        $objPHPExcel->getActiveSheet()->SetCellValue('R' . $i, "Drug_Allergies");
        $objPHPExcel->getActiveSheet()->SetCellValue('S' . $i, "TB");
        $objPHPExcel->getActiveSheet()->SetCellValue('T' . $i, "smoke");
        $objPHPExcel->getActiveSheet()->SetCellValue('U' . $i, "alcohol");
        $objPHPExcel->getActiveSheet()->SetCellValue('V' . $i, "date_enrolled");
        $objPHPExcel->getActiveSheet()->SetCellValue('W' . $i, "Patient_source");
        $objPHPExcel->getActiveSheet()->SetCellValue('X' . $i, "supported_by");
        $objPHPExcel->getActiveSheet()->SetCellValue('Y' . $i, "timestamp");
        $objPHPExcel->getActiveSheet()->SetCellValue('Z' . $i, "facility_code");
        $objPHPExcel->getActiveSheet()->SetCellValue('AA' . $i, "pob");
        $objPHPExcel->getActiveSheet()->SetCellValue('AB' . $i, "Service");
        $objPHPExcel->getActiveSheet()->SetCellValue('AC' . $i, "Start_Regimen");
        $objPHPExcel->getActiveSheet()->SetCellValue('AD' . $i, "start_regimen_date");
        $objPHPExcel->getActiveSheet()->SetCellValue('AE' . $i, "Current_status");
        $objPHPExcel->getActiveSheet()->SetCellValue('AF' . $i, "migration_id");
        $objPHPExcel->getActiveSheet()->SetCellValue('AG' . $i, "machine_code");
        $objPHPExcel->getActiveSheet()->SetCellValue('AH' . $i, "SMS_Consent");
        $objPHPExcel->getActiveSheet()->SetCellValue('AI' . $i, "Family_Planning");
        $objPHPExcel->getActiveSheet()->SetCellValue('AJ' . $i, "tbphase");
        $objPHPExcel->getActiveSheet()->SetCellValue('AK' . $i, "startphase");
        $objPHPExcel->getActiveSheet()->SetCellValue('AL' . $i, "endphase");
        $objPHPExcel->getActiveSheet()->SetCellValue('AM' . $i, "partner_status");
        $objPHPExcel->getActiveSheet()->SetCellValue('AN' . $i, "status_change_date");
        $objPHPExcel->getActiveSheet()->SetCellValue('AO' . $i, "Disclosure");
        $objPHPExcel->getActiveSheet()->SetCellValue('AP' . $i, "support_group");
        $objPHPExcel->getActiveSheet()->SetCellValue('AQ' . $i, "Current_Regimen");
        $objPHPExcel->getActiveSheet()->SetCellValue('AR' . $i, "nextappointment");
        $objPHPExcel->getActiveSheet()->SetCellValue('AS' . $i, "start_height");
        $objPHPExcel->getActiveSheet()->SetCellValue('AT' . $i, "start_weight");
        $objPHPExcel->getActiveSheet()->SetCellValue('AU' . $i, "start_bsa");
        $objPHPExcel->getActiveSheet()->SetCellValue('AV' . $i, "Transfer_From");
        $objPHPExcel->getActiveSheet()->SetCellValue('AW' . $i, "Days_To_NextAppointment");
        $objPHPExcel->getActiveSheet()->SetCellValue('AY' . $i, "Drug_Prophylaxis");

        foreach ($results as $result) {
            $i++;
            $objPHPExcel->getActiveSheet()->SetCellValue('A' . $i, $result["medical_record_number"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $i, $result["patient_number_ccc"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('C' . $i, $result["first_name"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('D' . $i, $result["last_name"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('E' . $i, $result["other_name"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('F' . $i, $result["dob"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('G' . $i, $result["pob"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('H' . $i, $result["gender"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('I' . $i, $result["pregnant"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('J' . $i, $result["Current_Weight"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('K' . $i, $result["Current_height"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('L' . $i, $result["Current_BSA"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('M' . $i, $result["phone"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('N' . $i, $result["Physical_Address"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('O' . $i, $result["Alternate_Address"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('P' . $i, $result["other_illnesses"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('Q' . $i, $result["other_drugs"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('R' . $i, $result["Drug_Allergies"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('S' . $i, $result["TB"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('T' . $i, $result["smoke"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('U' . $i, $result["alcohol"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('V' . $i, $result["date_enrolled"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('W' . $i, $result["Patient_source"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('X' . $i, $result["supported_by"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('Y' . $i, $result["timestamp"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('Z' . $i, $result["facility_code"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AA' . $i, $result["pob"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AB' . $i, $result["Service"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AC' . $i, $result["Start_Regimen"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AD' . $i, $result["start_regimen_date"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AE' . $i, $result["Current_status"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AF' . $i, $result["migration_id"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AG' . $i, $result["machine_code"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AH' . $i, $result["SMS_Consent"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AI' . $i, $result["Family_Planning"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AJ' . $i, $result["tbphase"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AK' . $i, $result["startphase"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AL' . $i, $result["endphase"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AM' . $i, $result["partner_status"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AN' . $i, $result["status_change_date"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AO' . $i, $result["Disclosure"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AP' . $i, $result["support_group"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AQ' . $i, $result["Current_Regimen"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AR' . $i, $result["nextappointment"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AS' . $i, $result["start_height"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AT' . $i, $result["start_weight"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AU' . $i, $result["start_bsa"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AV' . $i, $result["Transfer_From"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AW' . $i, $result["Days_to_NextAppointment"]);
            $objPHPExcel->getActiveSheet()->SetCellValue('AY' . $i, $result["prophylaxis"]);
        }

        if (ob_get_contents())
            ob_end_clean();
        $filename = "Patient Master List For " . $facility_code . ".csv";
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename=' . $filename);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');

        $objWriter->save('php://output');

        $objPHPExcel->disconnectWorksheets();
        unset($objPHPExcel);
    }

    public function enable($id) {
        $sql = "update patient set active='1' where id='$id'";
        $this->db->query($sql);
        $get_user = "select first_name FROM patient WHERE id='$id' LIMIT 1";
        $user_sql = $this->db->query($get_user);
        $user_array = $user_sql->result_array();
        $first_name = "";
        foreach ($user_array as $value) {
            $first_name = $value['first_name'];
        }
        //Set session for notications
        $this->session->set_userdata('msg_save_transaction', 'success');
        $this->session->set_userdata('user_enabled', $first_name . " was enabled!");
        redirect("patient_management");
    }

    public function disable($id) {
        $sql = "update patient set active='0' where id='$id'";
        $this->db->query($sql);
        $get_user = "select first_name FROM patient WHERE id='$id' LIMIT 1";
        $user_sql = $this->db->query($get_user);
        $user_array = $user_sql->result_array();
        $first_name = "";
        foreach ($user_array as $value) {
            $first_name = $value['first_name'];
        }
        //Set session for notications
        $this->session->set_userdata('msg_save_transaction', 'success');
        $this->session->set_userdata('user_disabled', $first_name . " was disabled!");
        redirect("patient_management");
    }

    public function delete($id) {
        $sql = "DELETE FROM patient where id='$id' and active='0'";
        $this->db->query($sql);
        //Set session for notications
        $this->session->set_userdata('msg_save_transaction', 'success');
        $this->session->set_userdata('user_disabled', "User Deleted");
        redirect("patient_management");
    }

    public function getAppointments($appointment = "") {
        $results = "";
        $sql = "select count(distinct(patient)) as total_appointments,weekend_max,weekday_max from patient_appointment pa,facilities f  where pa.appointment = '$appointment' and f.facilitycode=pa.facility";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        echo json_encode($results);
    }

    public function getSixMonthsDispensing($patient_no) {
        //Validate patient_no when use of / to separate mflcode and ccc_no
        $mflcode = $this->uri->segment(3);
        $ccc_no = $this->uri->segment(4);
        if ($ccc_no) {
            $patient_no = $mflcode . '/' . $ccc_no;
        }

        $dyn_table = "";
        $facility = $this->session->userdata("facility");

        $sql = "SELECT
                    DATE_FORMAT(pv.dispensing_date,'%d-%b-%Y') as dispensing_date,
                    UPPER(dc.Drug) as drug,
                    pv.quantity,
                    pv.pill_count,
                    pv.missed_pills,
                    round(((pv.quantity-(pv.pill_count-pv.months_of_stock))/pv.quantity)*100,2) as pill_adh,
                    round(((pv.quantity-pv.missed_pills)/pv.quantity)*100,2) as missed_adh,
                    pv.adherence
                FROM patient_visit pv
                LEFT JOIN patient p ON p.patient_number_ccc=pv.patient_id
                LEFT JOIN drugcode dc ON dc.id=pv.drug_id
                WHERE pv.patient_id = ?
                AND pv.facility = ?
                GROUP BY dispensing_date,pv.patient_id, pv.drug_id
                ORDER BY pv.dispensing_date DESC";
        $query = $this->db->query($sql, array($patient_no, $facility));
        $results = $query->result_array();

        if ($results) {
            foreach ($results as $result) {
                $dyn_table .= "<tbody><tr>";
                $dyn_table .= "<td>" . $result['dispensing_date'] . "</td>";
                $dyn_table .= "<td>" . $result['drug'] . "</td>";
                $dyn_table .= "<td>" . $result['quantity'] . "</td>";
                $dyn_table .= "<td>" . $result['pill_count'] . "</td>";
                $dyn_table .= "<td>" . $result['missed_pills'] . "</td>";
                $dyn_table .= "<td>" . $result['pill_adh'] . "%</td>";
                $dyn_table .= "<td>" . $result['missed_adh'] . "%</td>";

                $adherence = doubleval(str_replace(array("%", "<", ">", "="), "", $result['adherence']));
                $average_adherence = (( doubleval($result['pill_adh']) + doubleval($result['missed_adh']) + $adherence) / 3);
                $dyn_table .= "<td>" . $adherence . "%</td>";
                $dyn_table .= "<td>" . number_format($average_adherence, 2) . "%</td>";
                $dyn_table .= "</tr></tbody>";
            }
        }
        echo $dyn_table;
    }

    public function old_getSixMonthsDispensing($patient_no) {
        $facility = $this->session->userdata("facility");
        $dyn_table = "";
        $sql = "SELECT pv.pill_count,"
                . "pv.missed_pills,"
                . "ds.frequency,"
                . "ds.value,"
                . "pv.months_of_stock,"
                . "pv.adherence,"
                . "pv.dispensing_date,"
                . "d.drug,"
                . "pv.quantity"
                . " from patient_visit pv"
                . " left join drugcode d on d.id=pv.drug_id "
                . "left join dose ds on ds.Name=pv.dose "
                . "where patient_id = '$patient_no' "
                . "and datediff(curdate(),dispensing_date)<=360 "
                . "and datediff(curdate(),dispensing_date)>=0 "
                . "and pv.facility='$facility'"
                . "and pv.active='1'"
                . "order by pv.dispensing_date desc";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        if ($results) {
            foreach ($results as $result) {
                if ($result['pill_count'] == "") {
                    $result['pill_count'] = "-";
                }
                if ($result['missed_pills'] == "") {
                    $result['missed_pills'] = "-";
                }
                //Calculate Adherence for Missed Pills
                if ($result['frequency'] == 1) {
                    if ($result['missed_pills'] <= 0) {
                        $self_reporting = "100%";
                    } else if ($result['missed_pills'] < 2 && $result['missed_pills'] > 0) {
                        $self_reporting = "≥95%";
                    } else if ($result['missed_pills'] >= 2 && $result['missed_pills'] <= 4) {
                        $self_reporting = "84-94%";
                    } else if ($result['missed_pills'] >= 5) {
                        $self_reporting = "<85%";
                    }
                } else if ($result['frequency'] == 2) {
                    if ($result['missed_pills'] <= 0) {
                        $self_reporting = "100%";
                    } else if ($result['missed_pills'] <= 3 && $result['missed_pills'] > 0) {
                        $self_reporting = "≥95%";
                    } else if ($result['missed_pills'] >= 4 && $result['missed_pills'] <= 8) {
                        $self_reporting = "84-94%";
                    } else if ($result['missed_pills'] >= 9) {
                        $self_reporting = "<85%";
                    }
                } else {
                    $self_reporting = "-";
                }

                //Calculate Adherence for Pill Count(formula)
                $dosage_frequency = ($result['frequency'] * $result['value']);
                $actual_pill_count = $result['pill_count'];
                $expected_pill_count = $result['months_of_stock'];

                $numerator = ($expected_pill_count - $actual_pill_count);
                $denominator = ($dosage_frequency * 30);
                //$denominator=$expected_pill_count;
                if ($denominator > 0) {
                    $pill_count_reporting = ($numerator / $denominator) * 100;
                    $pill_count_reporting = number_format($pill_count_reporting, 2) . "%";
                } else {
                    $pill_count_reporting = "-";
                }

                if ($result['adherence'] == " ") {
                    $result['adherence'] = "-";
                }

                $dyn_table .= "<tbody><tr><td>" . date('d-M-Y', strtotime($result['dispensing_date'])) . "</td><td>" . $result['drug'] . "</td><td align='center'>" . $result['quantity'] . "</td><td align='center'>" . $result['pill_count'] . "</td><td align='center'>" . $result['missed_pills'] . "</td><td align='center'>" . $pill_count_reporting . "</td><td align='center'>" . $self_reporting . "</td><td align='center'>" . $result['adherence'] . "</td></tr></tbody>";
            }
        }
        echo $dyn_table;
    }

    public function getRegimenChange($patient_no) {
        //Validate patient_no when use of / to separate mflcode and ccc_no
        $mflcode = $this->uri->segment(3);
        $ccc_no = $this->uri->segment(4);
        if ($ccc_no) {
            $patient_no = $mflcode . '/' . $ccc_no;
        }

        $dyn_table = "";
        $facility = $this->session->userdata("facility");
        $sql = "select dispensing_date, r1.regimen_desc as current_regimen, r2.regimen_desc as previous_regimen, if(rc.name is null,pv.regimen_change_reason,rc.name) as reason "
                . "from patient_visit pv "
                . "left join regimen r1 on pv.regimen = r1.id"
                . " left join regimen r2 on pv.last_regimen = r2.id"
                . " left join regimen_change_purpose rc on pv.regimen_change_reason = rc.id "
                . "where pv.patient_id = ? "
                . "and pv.facility = ? "
                . "and pv.regimen != pv.last_regimen "
                . "group by dispensing_date,pv.regimen "
                . "order by pv.dispensing_date desc";
        $query = $this->db->query($sql, array($patient_no, $facility));
        $results = $query->result_array();
        if ($results) {
            foreach ($results as $result) {
                if ($result['current_regimen'] == "") {
                    $result['current_regimen'] = "-";
                }
                if ($result['previous_regimen'] == "") {
                    $result['previous_regimen'] = "-";
                }
                if ($result['reason'] == "") {
                    $result['reason'] = "-";
                } elseif ($result['reason'] == "undefined") {
                    $result['reason'] = "-";
                } elseif ($result['reason'] == "null") {
                    $result['reason'] = "-";
                }
                //if ($result['current_regimen'] == "-") {
                $dyn_table .= "<tbody><tr><td>" . date('d-M-Y', strtotime($result['dispensing_date'])) . "</td><td>" . $result['current_regimen'] . "</td><td align='center'>" . $result['previous_regimen'] . "</td><td align='center'>" . $result['reason'] . "</td></tr></tbody>";
                //}
            }
        }
        echo $dyn_table;
    }

    public function getAppointmentHistory($patient_no) {
        //Validate patient_no when use of / to separate mflcode and ccc_no
        $mflcode = $this->uri->segment(3);
        $ccc_no = $this->uri->segment(4);
        if ($ccc_no) {
            $patient_no = $mflcode . '/' . $ccc_no;
        }

        $dyn_table = "";
        $status = "";
        $facility = $this->session->userdata("facility");
        $sql = "SELECT pa.appointment,IF(pa.appointment=pv.dispensing_date,'Visited',DATEDIFF(pa.appointment,curdate()))as Days_To 
                FROM(SELECT patient,appointment FROM patient_appointment pa WHERE patient = ? AND facility = ?) as pa,(SELECT patient_id,dispensing_date FROM patient_visit WHERE patient_id = ? AND facility = ?) as pv GROUP BY pa.appointment ORDER BY pa.appointment desc";
        $query = $this->db->query($sql, array($patient_no, $facility, $patient_no, $facility));
        $results = $query->result_array();
        if ($results) {
            foreach ($results as $result) {

                if ($result['Days_To'] > 0) {
                    $status = "<td align='center'>" . $result['Days_To'] . " Days To</td>";
                } else if ($result['Days_To'] < 0) {
                    $mysql = "select dispensing_date,DATEDIFF(dispensing_date,'" . @$result['appointment'] . "')as days from patient_visit where patient_id='$patient_no' and dispensing_date>'" . @$result['appointment'] . "' and facility='$facility' ORDER BY dispensing_date asc LIMIT 1";
                    $myquery = $this->db->query($mysql);
                    $myresults = $myquery->result_array();
                    $result['dispensing_date'] = date('Y-m-d');
                    if ($myresults) {
                        $result['dispensing_date'] = $myresults[0]['dispensing_date'];
                        $result['Days_To'] = $myresults[0]['days'];
                    }
                    $result['Days_To'] = str_replace("-", "", $result['Days_To']);
                    $status = "<td align='center'> Late By " . $result['Days_To'] . " Days (" . date('d-M-Y', strtotime($result['dispensing_date'])) . ")</td>";
                } else {
                    $status = "<td align='center' class='green'>" . $result['Days_To'] . "</td>";
                }

                $dyn_table .= "<tbody><tr><td>" . date('d-M-Y', strtotime($result['appointment'])) . "</td>$status</tr></tbody>";
            }
        }
        echo $dyn_table;
    }

    public function updateLastRegimen() {

        //Get list of patients who changed regimen
        $sql_patient = "SELECT DISTINCT(p.id) as patient_id FROM patient p
                        LEFT JOIN patient_visit pv ON pv.patient_id=p.id
                        WHERE pv.regimen_change_reason IS NOT NULL";
        $query_exec = $this->db->query($sql_patient);
        $patients = $query_exec->result_array();
        foreach ($patients as $patient) {
            $patient_id = $patient["patient_id"];
            $sql = "SELECT * FROM patient_visit WHERE regimen_change_reason IS NOT NULL AND patient_id =" . $patient_id . " ORDER BY dispensing_date ASC";

            $query = $this->db->query($sql);
            $result = $query->result_array();
            foreach ($result as $key => $value) {

                if ($key == 0) {//For the first in the list, get the previous regimen under which the patient was
                    $curr_disp = $result[$key]["dispensing_date"];
                    $s = "SELECT * from patient_visit WHERE dispensing_date <'" . $curr_disp . "' AND patient_id =" . $patient_id . " ORDER BY dispensing_date DESC LIMIT 1";

                    $q = $this->db->query($s);
                    $res = $q->result_array();

                    if (count($res) > 0) {
                        //echo (count($res))."<br>";
                        $regimen = $res[0]["regimen"];
                        $sql = "UPDATE patient_visit SET last_regimen =" . $regimen . " WHERE id =" . $result[$key]["id"];
                        $q = $this->db->query($sql);
                    }
                } else {
                    $x = $key - 1;
                    //Get last regimen
                    //Check if patients was not dispensed under same regimen
                    if ($result[$x]["regimen"] != $result[$key]["regimen"]) {
                        //Update current_patient visit last regimen column
                        $sql = "UPDATE patient_visit SET last_regimen =" . $result[$x]["regimen"] . " WHERE id =" . $result[$key]["id"];
                        $query = $this->db->query($sql);
                        $count = $this->db->affected_rows();
                    }
                }
            }
        }
    }

    public function updatePregnancyStatus() {
        $patient_ccc = $this->input->post("patient_ccc");
        //Check if patient is on PMTCT and change them to ART
        $sql = "SELECT rst.name FROM patient p
                LEFT JOIN regimen_service_type rst ON p.service = rst.id
                WHERE p.patient_number_ccc ='$patient_ccc'";
        $query = $this->db->query($sql);
        $result = $query->result_array();
        $service = $result[0]['name'];
        $extra = '';
        if (stripos($service, "pmtct") === 0) {
            $sql_get_art = "SELECT id FROM regimen_service_type WHERE name LIKE '%art%'";
            $query = $this->db->query($sql_get_art);
            $result = $query->result_array();
            $art_service_id = $result[0]['id'];
            $extra = ", service = '$art_service_id' ";
        }
        $sql = "UPDATE patient SET pregnant = '0', breastfeeding = '0' $extra WHERE patient_number_ccc ='$patient_ccc'";
        $this->db->query($sql);
        $count = $this->db->affected_rows();
    }

    public function update_tb_status() {
        $patient_ccc = $this->input->post("patient_ccc");
        $tb_sql = "UPDATE patient SET tb = '0' WHERE patient_number_ccc ='$patient_ccc'";
        $this->db->query($tb_sql);
        $count = $this->db->affected_rows();
    }

    public function getWhoStage() {
        $patient_ccc = $this->input->post("patient_ccc");
        $sql = "SELECT who_stage FROM patient WHERE patient_number_ccc ='$patient_ccc' LIMIT 1";
        $res = $this->db->query($sql);
        $result = $res->result_array();
        $data['patient_who'] = trim($result[0]['who_stage']);
        $sql = "SELECT * FROM who_stage";
        $res = $this->db->query($sql);
        $result = $res->result_array();
        $data['who_stage'] = $result;
        echo json_encode($data);
    }

    public function updateWhoStage() {
        $patient_ccc = $this->input->post("patient_ccc");
        $who_stage = $this->input->post("who_stage");
        $sql = "UPDATE patient SET who_stage = '$who_stage' WHERE patient_number_ccc ='$patient_ccc'";
        $this->db->query($sql);
        $count = $this->db->affected_rows();
    } 

    function generateReport() {

        $query = "";
        $from = $this->input->post('from');
        $to = $this->input->post('to');
        //$dateEnrolled = $this->input->post('dateEnrolled');
        $gender = $this->input->post('gender');
        $patientsource = $this->input->post('patientSource');
        $maturity = $this->input->post('agegroup');
        $service = $this->input->post('service');
        $startRegimen = $this->input->post('startRegimen');
        $currRegimen = $this->input->post('currentRegimen');
        $currStatus = $this->input->post('currentStatus');
        $smokes = $this->input->post('smokes');
        $drink = $this->input->post('alcohol');
        $pregnant = $this->input->post('pregnant');
        $tb = $this->input->post('tb');
        $disclosure = $this->input->post('disclosure');
        $differentiated = $this->input->post('diffCare');

        if (!empty($from) && empty($to)) {
            $query .= " AND date_enrolled ='$from'";
        }
        if (!empty($to)) {
            $query .= " AND date_enrolled BETWEEN '$from' AND '$to' ";
        }

        $query .= (!empty($gender)) ? " AND gender ='$gender'" : '' ;
        $query .= (!empty($maturity)) ? " AND maturity ='$maturity'" : '' ;
        $query .= (!empty($service)) ? " AND service ='$service'" : '';
        $query .= (!empty($startRegimen)) ? " AND start_regimen ='$startRegimen'" : '';
        $query .= (!empty($currRegimen)) ? " AND current_regimen ='$currRegimen'" : '';
        $query .= (!empty($smokes)) ? " AND smoke ='$smokes'" : '';
        $query .= (!empty($drink)) ? " AND alcohol ='$drink'" : '';
        $query .= (!empty($pregnant)) ? " AND pregnant ='$pregnant'" : '';
        $query .= (!empty($tb)) ? " AND tb ='$tb'" : '';
        $query .= (!empty($differentiated)) ? " AND differentiated_care_status ='$differentiated'" : '';
        $query .= (!empty($currStatus)) ? " AND current_status ='$currStatus'" : '';
        $query .= (!empty($disclosure)) ? " AND disclosure ='$disclosure'" : '';
        $query .= (!empty($patientsource)) ? " AND patient_source ='$patientsource'" : '';


        $raw = preg_replace('/AND/', '', $query, 1); // remove first appearance of AND

        if (!empty($query)) {
            $results = $this->query(' WHERE '.$raw);
            $this->getPatientMasterList($results);
        } else {
            $results = $this->query();
            $this->getPatientMasterList($results);
        }
    }

    function query($raw='') {
    	$query_str = "SELECT ccc_number,first_name,other_name,last_name,date_of_birth,age,maturity,pob,gender,pregnant,adherence,current_weight,current_height,current_bsa,current_bmi,phone_number,physical_address,alternate_address,other_illnesses,other_drugs,drug_allergies,tb,smoke,alcohol,date_enrolled,patient_source,supported_by,service,start_regimen,start_regimen_date,last_regimen,current_status,sms_consent,family_planning,tbphase,startphase,endphase,partner_status,status_change_date,disclosure,support_group,current_regimen,nextappointment,days_to_nextappointment,clinicalappointment,start_height,start_weight,start_bsa,start_bmi,transfer_from,prophylaxis,isoniazid_start_date,isoniazid_end_date,pep_reason,differentiated_care_status,viral_load_test_results,viral_load_test_date
        	from vw_patient_list $raw";
        return $this->db->query($query_str);
    }

    public function getPatientMasterList($results) {

        ini_set("memory_limit", '2048M');
        $this->load->dbutil();
        $this->load->helper('file');
        $this->load->helper('download');
        $delimiter = ",";
        $newline = "\r\n";
        $filename = "patient_master_list.csv";
        $data = $this->dbutil->csv_from_result($results, $delimiter, $newline);
        ob_clean(); //Removes spaces
        force_download($filename, $data);
    }

    public function getPatientMergeList() {
        $iDisplayStart = $this->input->get_post('iDisplayStart', true);
        $iDisplayLength = $this->input->get_post('iDisplayLength', true);
        $iSortCol_0 = $this->input->get_post('iSortCol_0', false);
        $iSortingCols = $this->input->get_post('iSortingCols', true);
        $sSearch = $this->input->get_post('sSearch', true);
        $sEcho = $this->input->get_post('sEcho', true);
        $where = "";
        $facility_code = $this->session->userdata("facility");

        //columns
        $aColumns = array('id',
            'patient_number_ccc',
            'first_name',
            'other_name',
            'last_name',
            'active');

        $count = 0;

        // Paging
        if (isset($iDisplayStart) && $iDisplayLength != '-1') {
            $this->db->limit($this->db->escape_str($iDisplayLength), $this->db->escape_str($iDisplayStart));
        }

        // Ordering
        if (isset($iSortCol_0)) {
            for ($i = 0; $i < intval($iSortingCols); $i++) {
                $iSortCol = $this->input->get_post('iSortCol_' . $i, true);
                $bSortable = $this->input->get_post('bSortable_' . intval($iSortCol), true);
                $sSortDir = $this->input->get_post('sSortDir_' . $i, true);

                if ($bSortable == 'true') {
                    $this->db->order_by($aColumns[intval($this->db->escape_str($iSortCol))], $this->db->escape_str($sSortDir));
                }
            }
        }
        //Filtering
        if (isset($sSearch) && !empty($sSearch)) {
            $column_count = 0;
            for ($i = 0; $i < count($aColumns); $i++) {
                $bSearchable = $this->input->get_post('bSearchable_' . $i, true);

                // Individual column filtering
                if (isset($bSearchable) && $bSearchable == 'true') {
                    if ($column_count == 0) {
                        $where .= "(";
                    } else {
                        $where .= " OR ";
                    }
                    $where .= $aColumns[$i] . " LIKE '%" . $this->db->escape_like_str($sSearch) . "%'";
                    $column_count++;
                }
            }
        }

        //data
        $this->db->select('SQL_CALC_FOUND_ROWS ' . str_replace(' , ', ' ', implode(', ', $aColumns)), false);
        $this->db->from("patient p");
        $this->db->where("p.facility_code", $facility_code);
        //search sql clause
        if ($where != "") {
            $where .= ")";
            $this->db->where($where);
        }
        $rResult = $this->db->get();

        // Data set length after filtering
        $this->db->select('FOUND_ROWS() AS found_rows');
        $iFilteredTotal = $this->db->get()->row()->found_rows;

        // Total data set length
        $this->db->select("p.*");
        $this->db->from("patient p");
        $this->db->where("p.facility_code", $facility_code);
        $total = $this->db->get();
        $iTotal = count($total->result_array());

        // Output
        $msg = array('sEcho' => intval($sEcho),
            'iTotalRecords' => $iTotal,
            'iTotalDisplayRecords' => $iFilteredTotal,
            'aaData' => array());

        //loop through data to parse to josn array
        foreach ($rResult->result() as $patient) {
            $row = array();
            //options
            $links = "<a href='#' class='btn btn-danger btn-mini unmerge_patient' id='" . $patient->id . "'>unmerge</a>";
            $checkbox = "<input type='checkbox' name='patients' class='patients' value='" . $patient->id . "' disabled/>";
            if ($patient->active == 1) {
                $links = "<a href='#' class='btn btn-success btn-mini merge_patient' id='" . $patient->id . "'>Merge</a>";
                $checkbox = "<input type='checkbox' name='patients' class='patients' value='" . $patient->id . "'/>";
            }
            $row[] = $checkbox . " " . $patient->patient_number_ccc;
            $patient_name = $patient->first_name . " " . $patient->other_name . " " . $patient->last_name;
            $row[] = str_replace("  ", " ", $patient_name);
            $row[] = $links;
            $msg['aaData'][] = $row;
        }
        echo json_encode($msg, JSON_PRETTY_PRINT);
    }

    public function merge() {
        //Handle the array with all patients that are to be merged
        $target_patient_id = $this->input->post('target_ccc');
        $patients = $this->input->post('patients');
        $patients = array_diff($patients, array($target_patient_id));

        //Get Target CCC_NO
        $sql = "SELECT patient_number_ccc FROM patient WHERE id='" . $target_patient_id . "'";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        if ($results) {
            $target_patient_ccc = $results[0]['patient_number_ccc'];
        }
        //loop through merged patients
        foreach ($patients as $patient) {
            //Merging patients involves disabling the patients being merged.
            $sql = "UPDATE patient SET active='0' WHERE id='" . $patient . "'";
            $this->db->query($sql);
            //Get CCC_NO
            $sql = "SELECT patient_number_ccc FROM patient WHERE id='" . $patient . "'";
            $query = $this->db->query($sql);
            $results = $query->result_array();
            if ($results) {
                $ccc_no = $results[0]['patient_number_ccc'];
            }
            //Transfer appointments to target patient
            $sql = "UPDATE patient_appointment pa 
                            SET pa.merge='" . $ccc_no . "',
                                pa.patient='" . $target_patient_ccc . "'
                  WHERE pa.patient='" . $ccc_no . "'";
            $this->db->query($sql);
            //Transfer visits to target patient
            $sql = "UPDATE patient_visit pv 
                            SET pv.migration_id='" . $ccc_no . "',
                                pv.patient_id='" . $target_patient_ccc . "'
                  WHERE pv.patient_id='" . $ccc_no . "'";
            $this->db->query($sql);
            $patient_no[] = $ccc_no;
        }

        $patients_to_remove = implode(",", $patient_no);

        $this->session->set_userdata('message_counter', '1');
        $this->session->set_userdata('msg_success', '[' . $patients_to_remove . '] was Merged to [' . $target_patient_ccc . '] !');
        $this->session->set_userdata("link_id", "merge_list");
        $this->session->set_userdata("linkSub", "patient_management/merge_list");
    }

    public function unmerge() {
        //Handle the array with all patients that are to be unmerged
        $target_patient_id = $this->input->post('target_ccc');

        //Merging patients involves disabling the patients being merged.
        $sql = "UPDATE patient SET active='1' WHERE id='" . $target_patient_id . "'";
        $this->db->query($sql);
        //Get Target CCC_NO
        $sql = "SELECT patient_number_ccc FROM patient WHERE id='" . $target_patient_id . "'";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        if ($results) {
            $target_patient_ccc = $results[0]['patient_number_ccc'];
        }
        //Transfer appointments to original patient
        $sql = "UPDATE patient_appointment pa 
                        SET pa.merge='',
                            pa.patient='" . $target_patient_ccc . "'
              WHERE pa.merge='" . $target_patient_ccc . "'";
        $this->db->query($sql);
        //Transfer visits and visits to original patient
        $sql = "UPDATE patient_visit pv 
                        SET pv.migration_id='',
                            pv.patient_id='" . $target_patient_ccc . "',
              WHERE pv.migration_id='" . $target_patient_ccc . "'";
        $this->db->query($sql);

        $this->session->set_userdata('message_counter', '1');
        $this->session->set_userdata('msg_success', '[' . $target_patient_ccc . '] was unmerged!');
        $this->session->set_userdata("link_id", "merge_list");
        $this->session->set_userdata("linkSub", "patient_management/merge_list");
    }

    public function load_view($page_id = NULL, $id = NULL) {
        $this->init_api_values();
        $config['details'] = array(
            'api' => $this->api,
            'patient_module' => $this->patient_module,
            'patient_id' => $id,
            'content_view' => 'patients/details_v',
            'hide_side_menu' => '1',
            'patient_msg' => $this->get_patient_relations($id)
        );
        $this->base_params($config[$page_id]);
    }

    public function get_patient_relations($patient_id = NULL) {

        $this->db->select("p.first_name,p.last_name,LOWER(ps.name) as status,dp.child,s.secondary_spouse");
        $this->db->from("patient p");
        $this->db->join("patient_status ps", "ps.id=p.current_status", "left");
        $this->db->join("dependants dp", "p.patient_number_ccc=dp.parent", "left");
        $this->db->join("spouses s", "p.patient_number_ccc=s.primary_spouse", "left");
        $this->db->where("p.id", $patient_id);
        $query = $this->db->get();
        $results = $query->result_array();

        $dependant_msg = "";
        if ($results) {
            $status = $results[0]['status'];
            //Check dependedants/spouse status
            $child = $results[0]['child'];
            $spouse = $results[0]['secondary_spouse'];
            $patient_name = strtoupper($results[0]['first_name'] . ' ' . $results[0]['last_name']);
            if ($child != NULL) {
                $pat = $this->getDependentStatus($child);
                if ($pat != '') {
                    $dependant_msg .= "Patient $patient_name\'s dependant " . $pat . " is lost to follow up ";
                }
            }
            if ($spouse != NULL) {
                $pat = $this->getDependentStatus($spouse);
                if ($pat != '') {
                    $dependant_msg .= "Patient $patient_name\'s spouse " . $pat . " is lost to follow up ";
                }
            }
        }

        return array('status' => $status, 'message' => $dependant_msg);
    }

    public function load_form($form_id = NULL) {
        $data = array();
        if ($form_id == "patient_details") {
            $data['pob'] = District::getItems();
            $data['gender'] = Gender::getItems();
            $data['current_status'] = Patient_Status::getItems();
            $data['source'] = Patient_Source::getItems();
            $data['drug_prophylaxis'] = Drug_Prophylaxis::getItems();
            $data['service'] = Regimen_Service_Type::getItems();
            $data['fplan'] = Family_Planning::getItems();
            $data['other_illnesses'] = Other_Illnesses::getItems();
            $data['pep_reason'] = Pep_Reason::getItems();
            $data['prep_reason'] = Prep_Reason::getItems();
            $data['who_stage'] = Who_Stage::getItems();
            $regimens = Regimen::getItems();
            $data['start_regimen'] = $regimens;
            $data['current_regimen'] = $regimens;

            //Get facilities beacuse of UTF-8 encoding
            $this->db->select('facilitycode AS id, name AS Name');
            $query = $this->db->get('facilities');
            $facilities = $query->result_array();
            foreach ($facilities as $facility) {
                $facility_list[] = array('id' => $facility['id'], 'Name' => utf8_encode($facility['Name']));
            }
            $data['transfer_from'] = $facility_list;

            //Get drug allergies
            $allergies = $this->db->query("SELECT id, Drug FROM drugcode WHERE Enabled = ? ORDER BY Drug ASC", array(1))->result_array();
            foreach ($allergies as $allergy) {
                $data['drug_allergies'][] = array('id' => $allergy['id'], 'Name' => utf8_encode($allergy['Drug']));
            }
        } else if ($form_id == "dispensing_frm") {
            $data['ccc_store_sp'] = CCC_store_service_point::getActive();
            $data['visit_purpose'] = Visit_Purpose::getActive();
            $data['regimen_change_reason'] = Regimen_Change_Purpose::getAllHydrated();
            $data['non_adherence_reason'] = Non_Adherence_Reasons::getAllHydrated();
            $regimens = Regimen::getItems();
            $data['last_regimen'] = $regimens;
            $data['current_regimen'] = $regimens;
        }

        echo json_encode($data);
    }

    public function load_patient($id = NULL) {
        $columns = array(
            'p.Medical_Record_Number AS medical_record_number',
            'p.Patient_Number_CCC AS patient_number_ccc',
            'p.First_Name AS first_name',
            'p.Last_Name AS last_name',
            'p.Other_Name AS other_name',
            'DATE_FORMAT(p.Dob,"%Y-%m-%d") AS dob',
            'p.Pob AS pob',
            'p.dependant.parent as parent',
            'p.Gender AS gender',
            'p.Pregnant AS pregnant',
            'p.breastfeeding AS breastfeeding',
            'FLOOR(DATEDIFF(p.Date_Enrolled,p.Dob)/365) AS start_age',
            'FLOOR(DATEDIFF(CURDATE(),p.Dob)/365) AS age',
            'p.Start_Weight AS start_weight',
            'p.Weight AS weight',
            'p.Start_Height AS start_height',
            'p.Height AS height',
            'p.Start_Bsa AS start_bsa',
            'p.Start_Bmi AS start_bmi',
            'p.Sa AS sa',
            'p.Bmi AS bmi',
            'p.Phone AS phone',
            'IF(p.SMS_Consent ="1","sms_yes","sms_no") AS sms_consent',
            'p.Physical AS physical',
            'p.Alternate AS alternate',
            'p.Support_Group AS support_group',
            'p.Partner_Status AS partner_status',
            'IF(p.Disclosure ="1","disclosure_yes","disclosure_no") AS disclosure',
            'p.Spouse.secondary_spouse AS secondary_spouse',
            'Fplan AS fplan',
            'Other_Illnesses AS other_illnesses',
            'Drug_Allergies AS drug_allergies',
            'p.Other_Drugs AS other_drugs',
            'p.Adr AS adr',
            'p.Smoke AS smoke',
            'p.Alcohol AS alcohol',
            'p.Tb_Test AS tb_test',
            'p.Tb AS tb',
            'p.tb_category AS tb_category',
            'p.Tbphase AS tbphase',
            'p.Startphase AS startphase',
            'p.Endphase AS endphase',
            'p.NextAppointment AS nextappointment',
            'p.clinicalappointment AS nextappointment_clinical',
            'IF(p.differentiated_care ="1","differentiated_care_yes","differentiated_care_no") AS differentiated_care',
            'DATEDIFF(p.clinicalappointment,CURDATE()) AS days_to_next_clinical',
            'DATEDIFF(p.NextAppointment,CURDATE()) AS days_to_next',
            'p.Date_Enrolled AS date_enrolled',
            'p.Current_Status AS current_status',
            'p.Status_Change_Date AS status_change_date',
            'p.Source AS source',
            'p.Transfer_From AS transfer_from',
            'p.Service AS service',
            'p.Pep_Reason AS pep_reason',
            'p.Start_Regimen AS start_regimen',
            'p.Start_Regimen_Date AS start_regimen_date',
            'p.Current_Regimen AS current_regimen',
            'p.who_stage',
            'p.drug_prophylaxis AS drug_prophylaxis',
            'p.isoniazid_start_date',
            'p.isoniazid_end_date');

        $details = Patient::get_patient($id, implode(",", $columns));

        //Sanitize data
        foreach ($details as $index => $detail) {
            if ($index == "other_illnesses") {
                $illnesses = explode(",", $detail);
                $others = $this->get_other_chronic($illnesses);
                $data[$index] = $others[$index];
                $data["other_chronic"] = $others['other_chronic'];
            } else {
                $data[$index] = utf8_encode($detail);
            }
        }

        //Add Latest Test
        $data = array_merge($data, $this->get_latest_test($id));

        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    public function get_latest_test($patient_id = NULL) {
        $prep_test_data = array(
            'prep_reason' => 0,
            'prep_test_answer' => 0,
            'prep_test_date' => '',
            'prep_test_result' => 0
        );

        $sql = "SELECT 
                    prep_reason_id AS prep_reason, is_tested AS prep_test_answer, test_date AS prep_test_date, test_result AS prep_test_result 
                FROM patient_prep_test 
                WHERE patient_id = ? 
                ORDER BY test_date DESC
                LIMIT 1";
        $result = $this->db->query($sql, array($patient_id))->row_array();
        if (!empty($result)) {
            $prep_test_data = $result;
        }
        return $prep_test_data;
    }

    public function get_visits($patient_id = NULL) {
        $facility_code = $this->session->userdata("facility");

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
                        v_v.user, 
                        IF(v_v.differentiated_care = '1','YES','NO') AS differentiated_care, 
                        v_v.regimen_change_reason AS regimen_change_reason 
                from v_patient_visits as v_v
                INNER JOIN regimen as R ON R.id = v_v.regimen_id
                INNER JOIN drugcode as D ON D.id = v_v.drug_id
                WHERE v_v.id = $patient_id
                AND v_v.pv_active = 1
                GROUP BY v_v.drug_id,v_v.dispensing_date
                ORDER BY v_v.dispensing_date DESC";

        $query = $this->db->query($sql);
        $visits = $query->result_array();
        $temp = array();

        foreach ($visits as $counter => $visit) {
            foreach ($visit as $key => $value) {
                if ($key == "record_id") {
                    $link = base_url() . 'dispensement_management/edit/' . $value;
                    $value = "<a href='" . $link . "' class='btn btn-small btn-warning'>Edit</a>";
                }

                $temp[$counter][] = $value;
            }
        }

        $data['aaData'] = $temp;

        //echo "<pre>";

        echo json_encode($data, JSON_PRETTY_PRINT);

        //echo "</pre>";
    }

    public function load_visits($patient_id = NULL) {

        $iDisplayStart = $this->input->get_post('iDisplayStart', true);
        $iDisplayLength = $this->input->get_post('iDisplayLength', true);
        $iSortCol_0 = $this->input->get_post('iSortCol_0', true);
        $iSortingCols = $this->input->get_post('iSortingCols', true);
        $sSearch = $this->input->get_post('sSearch', true);
        $sEcho = $this->input->get_post('sEcho', true);
        $facility_code = $this->session->userdata("facility");

        //Selected columns
        $aColumns = array(
            'pv.dispensing_date',
            'v.name AS visit',
            'pv.dose',
            'pv.duration',
            'pv.id AS record_id',
            'd.drug',
            'pv.quantity',
            'pv.current_weight',
            'r1.regimen_desc',
            'pv.batch_number',
            'pv.pill_count',
            'pv.adherence',
            'pv.user',
            'rcp.name AS regimen_change_reason'
        );

        // Paging
        if (isset($iDisplayStart) && $iDisplayLength != '-1') {
            $this->db->limit($this->db->escape_str($iDisplayLength), $this->db->escape_str($iDisplayStart));
        }

        // Ordering
        if (isset($iSortCol_0)) {
            for ($i = 0; $i < intval($iSortingCols); $i++) {
                $iSortCol = $this->input->get_post('iSortCol_' . $i, true);
                $bSortable = $this->input->get_post('bSortable_' . intval($iSortCol), true);
                $sSortDir = $this->input->get_post('sSortDir_' . $i, true);

                if ($bSortable == 'true') {
                    $this->db->order_by($aColumns[intval($this->db->escape_str($iSortCol))], $this->db->escape_str($sSortDir));
                }
            }
        }

        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $sWhere = "";
        if (isset($sSearch) && !empty($sSearch)) {
            for ($i = 0; $i < count($aColumns); $i++) {
                $bSearchable = $this->input->get_post('bSearchable_' . $i, true);

                // Individual column filtering
                if (isset($bSearchable) && $bSearchable == 'true') {
                    //If 'AS' is found remove it
                    $col = $aColumns[$i];
                    $pos = strpos($col, "AS");

                    if ($pos !== FALSE) {
                        $col = trim($col = substr($col, 0, $pos));
                    }

                    $sSearch = mysql_real_escape_string($sSearch);

                    if ($i != 0) {
                        $sWhere .= " OR " . $col . " LIKE '%" . $sSearch . "%'";
                    } else {
                        $sWhere .= "( " . $col . " LIKE '%" . $sSearch . "%'";
                    }
                }
            }
            $sWhere .= ")";
        }

        // Select Data
        $this->db->select('SQL_CALC_FOUND_ROWS ' . str_replace(' , ', ' ', implode(', ', $aColumns)), false);
        $this->db->from("patient_visit pv");
        $this->db->join("patient p", "pv.patient_id = p.patient_number_ccc", "left");
        $this->db->join("drugcode d", "pv.drug_id = d.id", "left");
        $this->db->join("regimen r", "pv.regimen", "left");
        $this->db->join("regimen r1", "pv.last_regimen = r1.id", "left");
        $this->db->join("visit_purpose v", "pv.visit_purpose = v.id", "left");
        $this->db->join("regimen_change_purpose rcp", "rcp.id=pv.regimen_change_reason", "left");
        $this->db->where("p.id", $patient_id);
        $this->db->where("pv.facility", $facility_code);
        $this->db->where("pv.active", 1);
        if ($sWhere) {
            $this->db->where($sWhere);
        }
        $this->db->group_by(array("d.drug,pv.dispensing_date"));

        $rResult = $this->db->get();

        echo $this->db->last_query();
        die();

        // Data set length after filtering
        $this->db->select('FOUND_ROWS() AS found_rows');
        $iFilteredTotal = $this->db->get()->row()->found_rows;

        // Total data set length
        $this->db->select("pv.*");
        $this->db->from("patient_visit pv");
        $this->db->join("patient p", "pv.patient_id = p.patient_number_ccc", "left");
        $this->db->join("drugcode d", "pv.drug_id = d.id", "left");
        $this->db->join("regimen r", "pv.regimen", "left");
        $this->db->join("regimen r1", "pv.last_regimen = r1.id", "left");
        $this->db->join("visit_purpose v", "pv.visit_purpose = v.id", "left");
        $this->db->join("regimen_change_purpose rcp", "rcp.id=pv.regimen_change_reason", "left");
        $this->db->where("p.id", $patient_id);
        $this->db->where("pv.facility", $facility_code);
        $this->db->where("pv.active", 1);
        if ($sWhere) {
            $this->db->where($sWhere);
        }
        $this->db->group_by(array("d.drug,pv.dispensing_date"));
        $total = $this->db->get();
        $iTotal = count($total->result_array());

        // Output
        $msg = array(
            'sEcho' => intval($sEcho),
            'iTotalRecords' => $iTotal,
            'iTotalDisplayRecords' => $iFilteredTotal,
            'aaData' => array()
        );

        foreach ($rResult->result_array() as $count => $aRow) {
            $data = array();
            foreach ($aRow as $col => $value) {
                if ($col == "record_id") {
                    $link = base_url() . 'dispensement_management/edit/' . $value;
                    $data[] = "<a href='" . $link . "' class='btn btn-small btn-warning'>Edit</a>";
                } else {
                    $data[] = $value;
                }
            }
            $msg['aaData'][] = $data;
        }

        echo json_encode($msg, JSON_PRETTY_PRINT);
    }

    public function get_other_chronic($illnesses) {
        $illness_list = array('other_illnesses' => '', 'other_chronic' => '');
        $other_chronic = array();
        $chronic = array();
        if ($illnesses) {
            $indicators = Other_Illnesses::getIndicators();
            foreach ($illnesses as $illness) {
                if (in_array($illness, $indicators)) {
                    $chronic[] = $illness;
                } else {
                    $other_chronic[] = $illness;
                }
            }

            $illness_list['other_illnesses'] = implode(",", $chronic);
            $illness_list['other_chronic'] = implode(",", $other_chronic);
        }
        return $illness_list;
    }

    public function load_summary($patient_id = NULL) {
        //procedure
    }

    public function get_patients($status = null) {
        $filter = "";
        if ($status != NULL) {
            if ($status == 'inactive') {
                $filter .= "AND ps.Name NOT LIKE '%active%'";
            }
        } else {
            $filter .= "AND ps.Name LIKE '%active%'";
        }
        $facility_code = $this->session->userdata("facility");
        $access_level = $this->session->userdata('user_indicator');

        $sql = "SELECT * FROM facilities where facilitycode='$facility_code'";
        $query = $this->db->query($sql);
        $facility_settings = $query->result_array()[0];

        $medical_number = $facility_settings['medical_number'];

        $contact_sql = "IF(p.phone='',p.alternate,p.phone) as phone_number,";
        $medical_cond = 'p.patient_number_ccc as ccc_no,';

        if ($medical_number == '1') {
            $medical_cond = 'p.medical_record_number,p.patient_number_ccc as ccc_no,';
            $contact_sql = "";
        }
        $store = $this->session->userdata('ccc_store_id');

        $sql = "SELECT 
        $medical_cond
                    UPPER(CONCAT_WS(' ',CONCAT_WS(' ',p.first_name,p.other_name),p.last_name)) as patient_name,
                    DATE_FORMAT(p.nextappointment,'%b %D, %Y') as appointment,
                    $contact_sql
                    CONCAT_WS(' | ',r.regimen_code,r.regimen_desc) as regimen,
                    ps.name as status,
                    p.active,
                    p.id,
                    p.current_status
                FROM patient p  
                LEFT JOIN regimen r ON r.id=p.current_regimen
                LEFT JOIN patient_status ps ON ps.id=p.current_status
                WHERE p.facility_code = '$facility_code'
                AND p.patient_number_ccc != '' $filter ";

        $query = $this->db->query($sql);
        $patients = $query->result_array();
        $temp = array();

        foreach ($patients as $counter => $patient) {
            foreach ($patient as $key => $value) {
                if ($key == "active") {
                    $id = $patient['id'];
                    $link = "";
                    //Active Patient
                    if ($access_level == "facility_administrator") {
                        if ($value == 1) {
                            $link = '| <a href="' . base_url() . 'patient_management/disable/' . $id . '" class="red actual">Disable</a>';
                        } else {
                            $link = '| <a href="' . base_url() . 'patient_management/enable/' . $id . '" class="green actual">Enable</a>';
                        }
                    }
                    if ($value == 1) {
                        if (strtolower($patient['status']) != 'active') {
                            $link = '<a href="' . base_url() . 'patient_management/load_view/details/' . $id . '">Detail</a> | <a href="' . base_url() . 'patient_management/edit/' . $id . '">Edit</a> ' . $link;
                        } else {
                            $link = '<a href="' . base_url() . 'dispensement_management/dispense/' . $id . '">Dispense</a>|<a href="' . base_url() . 'patient_management/load_view/details/' . $id . '">Detail</a> | <a href="' . base_url() . 'patient_management/edit/' . $id . '">Edit</a> ' . $link;
                        }
                    } else {
                        $link = str_replace("|", "", $link);
                        $link .= '| <a href="' . base_url() . 'patient_management/delete/' . $id . '" class="red actual">Delete</a>';
                    }

                    $value = $link;
                    unset($patient['id']);
                }
                $temp [$counter][] = $value;
            }
        }

        $data['aaData'] = $temp;

        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    public function get_patient_details($patient_id = null, $type = null) {
        if (!isset($patient_id)) {
            $patient_id = $this->input->post('patient_id');
        }

        $query = patient::get_patient_details($patient_id);
        if (isset($type)) {
            return $query;
        } else {
            echo json_encode($query);
        }
    }

    //to get the dose for a child patient
    public function get_peadiatric_dose() {
        $weight = $this->input->post('weight');
        $drug_id = $this->input->post('drug_id');
        $sql = "select do.id,Name,value,frequency from dossing_chart d
              inner join dose do on do.id=d.dose_id
              where min_weight <= '$weight' and max_weight >= '$weight' and drug_id='$drug_id' and is_active = '1'";
        $query = $this->db->query($sql);
        $data = $query->row();
        echo json_encode($data);
    }

    //get the viral _load_information
    public function get_viral_load_info($patient_id) {
        $patient_details = $this->get_patient_details($patient_id, 'array');

        $patient_ccc = $patient_details['Patient_Number_CCC'];

        $msg = 0;
        $max_days_from_enrolled = 180;
        $max_days_to_notification = 10;
        $max_days_to_LDL_test = 365;
        $max_days_for_greater_1000_test = 90;
        $sql = "SELECT p.patient_number_ccc,pv.result,pv.test_date,DATEDIFF(NOW(), test_date) AS test_date_diff, DATEDIFF(NOW(), start_regimen_date) AS start_regimen_date_diff FROM patient p left JOIN  patient_viral_load pv ON p.patient_number_ccc = pv.patient_ccc_number  and p.patient_number_ccc = '$patient_ccc' 
            Where p.patient_number_ccc = '$patient_ccc' group by p.patient_number_ccc order by test_date desc";
        $query = $this->db->query($sql);
        $datas = $query->result_array();
        foreach ($datas as $data) {
            $viral_load_test_date = $data['test_date'];
            //if patient has no viral_load_test date
            if (empty($viral_load_test_date)) {   //check the viral load test date 
                $start_regimen_date_diff = $data['start_regimen_date_diff'];
                //if patient is enrolled in care and there is  ten or less days to viral load test date
                if ($start_regimen_date_diff < $max_days_from_enrolled && (($max_days_from_enrolled - $start_regimen_date_diff) <= $max_days_to_notification)) {
                    $msg = "This patient needs to do viral Load test before " + $start_regimen_date_diff + " days from today";
                }
                // no patient_viral load info and 180 days has passed.
                else if ($start_regimen_date_diff > $max_days_from_enrolled) {
                    $msg = "This patient requires a viral load test as there is no viral load Information and 6 months has passed from the date of start regimen";
                }
            }
            //if patient has viral load test date
            else {
                $result = $data['result'];
                $test_date_diff = $data['test_date_diff'];
                //if LDL
                if ($result == '< LDL copies/ml') {
                    if ($test_date_diff < $max_days_to_LDL_test && (($max_days_to_LDL_test - $test_date_diff) <= $max_days_to_notification )) {
                        $msg = "This patient needs to do viral Load test before " . $max_days_to_LDL_test - $test_date_diff . " days from today";
                    } else if ($test_date_diff > $max_days_to_LDL_test) {
                        $msg = "This patient needs to do viral Load test urgently as one year has elapsed from the last test";
                    }
                }
                //else viral is more than 1000
                else if ($result > 1000) {
                    if ($test_date_diff < $max_days_for_greater_1000_test && (($max_days_for_greater_1000_test - $test_date_diff) <= $max_days_to_notification)) {
                        $diff = $max_days_for_greater_1000_test - $test_date_diff;
                        $msg = "This patient needs to do viral Load test  " . $diff . " days from today";
                    } else if ($test_date_diff > $max_days_for_greater_1000_test) {
                        $msg = "This patient needs to do viral Load test as 90 days has passed";
                    }
                }
            }
        }
        echo json_encode($msg);
    }

}

ob_get_clean();
?>