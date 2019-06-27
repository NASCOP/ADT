<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Api_model extends CI_Model {

	function __construct() {
		parent::__construct();
		// $CI = &get_instance();
		// $CI -> load -> database();
	}

	function saveAPIConfig($conf){
		$CI = &get_instance();
		$CI -> load -> database();
		$CI->db->query("update api_config set value = 'off' where type='toggle'");

		foreach ($conf as $key => $val) {
		$CI->db->where('config' , $key);
		$CI->db->update('api_config', array('value'=>$val));
		}

		return true;
	}
	function savePatientMatching($patient){
		$CI = &get_instance();
		$CI -> load -> database();
		$CI->db->insert('api_patient_matching', $patient);
		return true;
	}

	function savePatient($patient,$external_id){

		$CI = &get_instance();
		$CI -> load -> database();
		$CI->db->insert('patient', $patient);
		$insert_id = $CI->db->insert_id();
		// $this->savePatientMatching(array('internal_id'=>$insert_id, 'external_id'=>$external_id));
		return $insert_id;
	}


	function updatePatient($patient,$internal_patient_id){

		$CI = &get_instance();
		$CI -> load -> database();

		$this->db->where('id' , $internal_patient_id);
		$this->db->update('patient', $patient);

		if ($this->db->affected_rows() > 0) {
			$resultable = true;
		} else {
			$resultable = false;
		}

		return true;

	}

	function getPatient($internal_id = null){

		$CI = &get_instance();
		$CI -> load -> database();
		$cond = '';
		$query_str = "SELECT p.*,ps.name as patient_status,pso.name as patient_source ,g.name as patient_gender FROM patient p
		left join patient_status ps on p.current_status = ps.id 
		left join patient_source pso on p.source = pso.id
		left join gender g on g.id = p.gender
		WHERE p.patient_number_ccc   = '$internal_id' ";

		// do left join in the case of patient created on adt and not already on IL


		$query = $CI->db->query($query_str);

		if (count($query->result()) > 0) {
			$returnable = $query->result()[0];
		} else {
			$returnable = false;
		}
		return $returnable;
	}


function getPatientbyID($internal_id = null){

		$CI = &get_instance();
		$CI -> load -> database();
		$cond = '';
		$query_str = "SELECT p.*,ps.name as patient_status,pso.name as patient_source ,g.name as patient_gender FROM patient p
		left join patient_status ps on p.current_status = ps.id 
		left join patient_source pso on p.source = pso.id
		left join gender g on g.id = p.gender

		WHERE p.id   = '$internal_id' ";

		// do left join in the case of patient created on adt and not already on IL


		$query = $CI->db->query($query_str);

		if (count($query->result()) > 0) {
			$returnable = $query->result()[0];
		} else {
			$returnable = false;
		}
		return $returnable;
	}

	function getPatientExternalID($internal_id = null,$assigning_authority = null){

		$CI = &get_instance();
		$CI -> load -> database();
		$cond = (isset($assigning_authority)) ?  "and assigning_authority = '$assigning_authority' " : null ;
		$query_str = "SELECT external_id as ID, identifier_type as IDENTIFIER_TYPE, assigning_authority as ASSIGNING_AUTHORITY FROM api_patient_matching WHERE internal_id   = '$internal_id' and external_id IS NOT NULL".$cond;
		$query = $CI->db->query($query_str);

		if (count($query->result()) > 0) {
			$returnable = $query->result()[0];
		} else {
			$returnable = false;
			$returnable =  array('ID' => '','IDENTIFIER_TYPE' => '','ASSIGNING_AUTHORITY' => '');
		}
		return $returnable;
	}

	function getPatientInternalID($external_id = null,$assigning_authority = null){

		$CI = &get_instance();
		$CI -> load -> database();
		$cond = (isset($assigning_authority)) ?  "and assigning_authority = '$assigning_authority' " : null ;
		$query_str = "SELECT id FROM api_patient_matching WHERE internal_id   = '$internal_id' and external_id IS NOT NULL".$cond;
		$query = $CI->db->query($query_str);

		if (count($query->result()) > 0) {
			$returnable = $query->result()[0];
		} else {
			$returnable = false;
			$returnable =  array('ID' => '','IDENTIFIER_TYPE' => '','ASSIGNING_AUTHORITY' => '');
		}
		return $returnable;
	}

	function getPatientAppointment($appointment_id = null){
		$CI = &get_instance();
		$CI -> load -> database();

		$sql = "SELECT DATE_FORMAT(MIN(pa.appointment), '%Y%m%d') appointment, pa.facility facility_code, p.*
				FROM patient_appointment pa 
				LEFT JOIN patient p ON p.patient_number_ccc = pa.patient
				WHERE pa.id = '$appointment_id'";
		$query = $CI->db->query($sql);

		if (count($query->result()) > 0) {
			$returnable = $query->result()[0];
		} else {
			$returnable = false;
		}
		return $returnable;
	}

	function getDispensing($order_id = null){
		$CI = &get_instance();
		$CI -> load -> database();

		$sql = "SELECT *, dpd.strength as drug_strength, pv.id visit_id, DATE_FORMAT(timecreated, '%Y%m%d%h%i%s') timecreated, pv.duration disp_duration, TRIM(d.drug) drugcode, pv.quantity disp_quantity, pv.dose disp_dose
				FROM patient_visit pv 
				INNER JOIN drug_prescription_details_visit dpdv ON dpdv.visit_id = pv.id
				INNER JOIN drug_prescription_details dpd ON dpd.id = dpdv.drug_prescription_details_id
				INNER JOIN drug_prescription dp ON dp.id = dpd.drug_prescriptionid AND pv.patient_id = dp.patient
				INNER JOIN patient p ON p.patient_number_ccc = pv.patient_id
				INNER JOIN drugcode d ON d.id = pv.drug_id
				WHERE dp.id = '$order_id'";
		$query = $CI->db->query($sql);

		if (count($query->result()) > 0) {
			$returnable = $query->result();
		} else {
			$returnable = false;
		}
		return $returnable;
	}

	function getUsers($merchantemail = null){
		$CI = &get_instance();
		$CI -> load -> database();

		$query = $CI->db->query("SELECT * FROM user");

		if (count($query->result()) > 0) {
			$returnable = $query->result();
		} else {
			$returnable = false;
		}
		return $returnable;

	}  


	function saveAppointment($appointment,$appointment_type){
		$appointment_tbl = ($appointment_type == 'CLINICAL') ? 'clinic_appointment' : 'patient_appointment' ; // appointment table
		$appointment_col = ($appointment_type == 'CLINICAL') ? 'clinicalappointment' : 'nextappointment' ; // appointment column
		$patient = $appointment['patient'];
		$appointment = $appointment['appointment'];

		$CI = &get_instance();
		$CI -> load -> database();
		$query = $CI->db->query("update patient set $appointment_col = '$appointment' where patient_number_ccc = '$patient'");
		$CI->db->insert("$appointment_tbl", $appointment);
		$insert_id = $CI->db->insert_id();				
		return $insert_id ;

	}

	function saveDrugPrescription($prescription,$prescription_details){
		$pe_details = array();
		$CI = &get_instance();
		$CI -> load -> database();
		$CI->db->insert('drug_prescription', $prescription);

		$insert_id = $CI->db->insert_id();
		foreach ($prescription_details as $details) {
	# code...
			$pe_details = array(

				'drug_prescriptionid' => $insert_id ,
				'drug_name' => $details->DRUG_NAME ,
				'coding_system' => $details->CODING_SYSTEM ,
				'strength' => $details->STRENGTH ,
				'dosage' => $details->DOSAGE ,
				'frequency' => $details->FREQUENCY ,
				'duration' => $details->DURATION,
				'quantity_prescribed' => $details->QUANTITY_PRESCRIBED ,
				'prescription_notes' => $details->PRESCRIPTION_NOTES 

			);
			$CI->db->insert('drug_prescription_details', $pe_details);
		}
		return $pe_details;

	}

	function getRegimen($regimenCode){
		$CI = &get_instance();
		$CI -> load -> database();

		$query = $CI->db->query("SELECT * FROM regimen WHERE lower(regimen_code) = lower('$regimenCode')");

		if (count($query->result()) > 0) {
			$returnable = $query->result()[0];
		} else {
			$returnable = false;
		}
		return $returnable;

	}


		function getActivePatientStatus(){
		$CI = &get_instance();
		$CI -> load -> database();

		$query = $CI->db->query("SELECT * FROM patient_status WHERE lower(Name) = 'active'");

		if (count($query->result()) > 0) {
			$returnable = $query->result()[0];
		} else {
			$returnable = false;
		}
		return $returnable;

	}



}