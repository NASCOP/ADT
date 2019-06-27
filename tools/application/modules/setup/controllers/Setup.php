<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Setup extends MX_Controller {

	function __construct() {
		parent::__construct();
	}

	public function index($message = '') {
		$data['active_menu'] = 7;
		$data['content_view'] = "setup/setup_view";
		$data['title'] = "Dashboard | System Setup";
				$CI = &get_instance();
		$CI -> load -> database();

		$sql = "SELECT * from users";
		$result = $CI->db->query($sql);
		$data['usercount'] = count($result->result_array());
		$this -> template($data);
	}

	public function initialize(){
		// @todo find old code from users table

		//Get mflcode
		$mflcode = $this->input->post('facility', TRUE);
		//Get database config
		$CI = &get_instance();
		$CI -> load -> database();

		$sql = "SELECT Facility_Code from users limit 1";
		$result = $CI->db->query($sql);
		$old_facility_code = $result->result_array()[0]['Facility_Code'];

		//Update all users to mflcode
		$sql = "
		INSERT INTO users (id, Name, Username, Password, Access_Level, Facility_Code, Created_By, Time_Created, Phone_Number, Email_Address, Active, Signature, map, ccc_store_sp) VALUES(2,	'Demo User',	'user',	'1a7a4c2f236042c4f8cfd79ec9ff2094',	'3',	'$mflcode',	'1',	'2014-09-24',	' 736262781',	'kevomarete@gmail.com',	'1',	'1',	0,	2);

			UPDATE users SET Facility_Code = $mflcode  WHERE Facility_Code = $old_facility_code;
			UPDATE drug_stock_movement SET facility = $mflcode  WHERE facility = $old_facility_code;
			UPDATE drug_stock_movement SET source = $mflcode  WHERE source = $old_facility_code;
			UPDATE drug_stock_movement SET destination = $mflcode  WHERE destination = $old_facility_code;
			UPDATE patient SET facility_code = $mflcode  WHERE facility_code = $old_facility_code;
			UPDATE patient_visit SET facility = $mflcode  WHERE facility = $old_facility_code;
			UPDATE patient_appointment SET facility = $mflcode  WHERE facility = $old_facility_code;
			UPDATE clinic_appointment SET facility = $mflcode  WHERE facility = $old_facility_code;
			UPDATE drug_cons_balance set facility = $mflcode where facility = $old_facility_code ;
			UPDATE drug_stock_balance set facility_code = $mflcode where facility_code = $old_facility_code; 
		";

		foreach (explode(';', $sql) as $q) {
		$CI->db->query($q);
		}
		//Redirect with message
		$message = '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Success!</strong> Facility initialized to MFLCODE: '.$mflcode.' <br /> User Login user:user</div>';
		$this->session->set_flashdata('init_msg', $message);
		redirect("setup");
	}

	public function template($data) {
		$data['show_menu'] = 0;
		$data['show_sidemenu'] = 0;
		$this -> load -> module('template');
		$this -> template -> index($data);
	}

}