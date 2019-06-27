<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Template extends MX_Controller {

	function __construct() {
		parent::__construct();
	    $this->load->library('session');
	}


	public function index($data) {
		// $user_session = $this -> check_session();
		// if ($user_session) {
			$data['banner_title'] = $this -> config -> item('banner_title');
			$data['banner_subtitle'] = $this -> config -> item('banner_subtitle');
			$data['firm_name'] = $this -> config -> item('firm_name');
			$data['default_home_controller'] = $this -> config -> item('default_home_controller');
			$this -> load -> view('template_v', $data);
		// } else {
		// 	redirect("login");
		// }
	}

	public function check_session() {
		$current_url = $this -> router -> class;
		if ($current_url == "recover"|| $current_url == "github") {
			return true;
		} else {
			if ($current_url != "login" && $this -> session -> userdata("id") == null) {
				return false;
			} else if ($current_url == "login" && $this -> session -> userdata("id") != null) {
				redirect($this -> config -> item('module_after_login'));
			} else {
				return true;
			}
		}
	}

}
