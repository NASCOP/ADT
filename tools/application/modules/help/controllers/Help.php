<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Help extends MX_Controller {
	var $backup_dir = "./backup_db";

	var $config = array (
		'hostname' => 'commodities.nascop.org',
		'username' => 'ftpuser',
		'password' => 'ftpuser',
		'debug'	=> FALSE);

	function __construct() {
		parent::__construct();
		$this->load->library('ftp');
	}

	public function index() {
		$dir = "../assets/manuals";
		// $data['backup_files'] = $this -> checkdir();
		$data['active_menu'] = 6;
		$data['content_view'] = "help/help_v";
		$data['title'] = "Dashboard | System Recovery";

		$data['ftp_status'] = '';
		$this -> load -> library('table');

$this->load->helper('directory');

		$dir = realpath($_SERVER['DOCUMENT_ROOT']);
		$files = directory_map($dir.'/ADT/assets/manuals/');

		$columns=array('#','File Name','Action');
		$tmpl = array('table_open' => '<table class="table table-bordered table-hover table-condensed table-striped dataTables" >');
		$this -> table -> set_template($tmpl);
		$this -> table -> set_heading($columns);

		foreach($files as $file){

			$links = "<a href='".str_replace("tools/", "", base_url())."assets/manuals/".$file."'target='_blank'>View</a>";


			$this -> table -> add_row("",$file, $links);    
		}
		$data['guidelines_list'] = $this -> table -> generate();
		$data['hide_side_menu'] = 1;
		$data['selected_report_type_link'] = "guidelines_report_row";
		$data['selected_report_type'] = "List of Guidelines";
		$data['report_title'] = "List of Guidelines";
		$data['facility_name'] = $this -> session -> userdata('facility_name');
		// $data['content_view']='guidelines_listing_v';
		// $this -> base_params($data);





		// $table = '<table id="dyn_table" class="table table-striped table-condensed table-bordered" cellspacing="0" width="100%">';
		// $table .= '<thead><th>Manual</th>		<th>action</th>		<th>local</th>		<th>remote</th>		</thead>';
		// $table .= '<tbody>';
		



		// $table .='</tbody>';
		// $table .='</table>';
		// // echo $table;die;
		// $data['backup_files'] = $table;
		$this -> template($data);
	}

		
		public function template($data) {
			$data['show_menu'] = 0;
			$data['show_sidemenu'] = 0;
			$this -> load -> module('template');
			$this -> template -> index($data);
		}

	}
