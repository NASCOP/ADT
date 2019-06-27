<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Recover extends MX_Controller {
	var $backup_dir = "./backup_db";
	function __construct() {
		parent::__construct();
			ini_set("max_execution_time", "100000");
		ini_set("memory_limit", '2048M');

	}


	public function index() {
		$this->recovery_tasks();
		$data['backup_files'] = $this -> checkdir();
		$data['active_menu'] = 1;
		$data['content_view'] = "recover/recovery_v";
		$data['title'] = "Dashboard | System Recovery";
		$CI = &get_instance();
		$CI -> load -> database();
		$data['sys_hostname'] = explode(':', $CI->db->hostname)[0];
		$data['sys_hostport']  = (isset($CI->db->port)) ? $CI->db->port : 3306 ;
		$data['sys_username'] = $CI->db->username;
		$data['sys_password'] = $CI->db->password;


		$this -> template($data);
	}

	public function check_server() {
		$host_name = ($this -> input -> post("inputHost")!= null) ? $this -> input -> post("inputHost") : 'localhost';
		$host_user = $this -> input -> post("inputUser");
		$host_password = $this -> input -> post("inputPassword");
		$host_port = $this -> input -> post("inputPort");

		$link = @mysql_connect($host_name.':'.$host_port, $host_user, $host_password);
		if ($link == false) {
			$status = 0;
		} else {
			$status = 1;
			$this -> session -> set_userdata("db_host", $host_name);
			$this -> session -> set_userdata("db_user", $host_user);
			$this -> session -> set_userdata("db_pass", $host_password);
			$this -> session -> set_userdata("db_port", $host_port);

		}
		echo $status;
	}

	public function check_database() {
		$host_name = $this -> session -> userdata("db_host");
		$host_user = $this -> session -> userdata("db_user");
		$host_password = $this -> session -> userdata("db_pass");
		$database_port =$this -> session -> userdata("db_port");
		$database_name = $this -> input -> post("inputDb");

		$link = @mysql_connect($host_name.':'.$database_port, $host_user, $host_password);
		$db_selected = @mysql_select_db($database_name, $link);
		if (!$db_selected) {
			$status = "\nConnection Success!\nDatabase does not exist!";
			$sql = "CREATE DATABASE $database_name";
			if (@mysql_query($sql, $link)) {
				$status .= "\nDatabase created successfully!";
				$this -> session -> set_userdata("db_name", $database_name);
			} else {
				$status = 0;
			}
		} else {
			$status = "\nConnection Success!\nDatabase Exists!";
			$this -> session -> set_userdata("db_name", $database_name);
		}			
		echo $status;
	}

	public function start_database() {
		error_reporting(E_ALL | E_STRICT);
		$this->load->library('UploadHandler');
		$upload_handler = new UploadHandler();
	}

	public function checkdir() {
		$dir = $this -> backup_dir;
		$backup_files = array();
		$backup_headings = array('Filename', 'Options');
		$options = '<button class="btn btn-primary btn-sm recover" >Recover</button>';

		if (is_dir($dir)) {
			$files = scandir($dir, 1);
			foreach ($files as $object) {
				if ($object != "." && $object != ".." && $object !=".gitkeep"  && $object !="downloads") {
					$backup_files[] = $object;
				}
			}
		} else {
			mkdir($dir);
		}
		$this -> load -> module('tables');
		return $this -> tables -> load_table($backup_headings, $backup_files, $options);
	}

	public function showdir() {
		$dir = $this -> backup_dir;
		$backup_files = array();
		$backup_headings = array('Filename', 'Options');
		$options = '<button class="btn btn-primary btn-sm recover" >Recover</button>';

		if (is_dir($dir)) {
			$files = scandir($dir, 1);
			foreach ($files as $object) {
				if ($object != "." && $object != ".." && $object !=".gitkeep"  && $object !="downloads") {
					$backup_files[] = $object;
				}
			}
		} else {
			mkdir($dir);
		}
		$this -> load -> module('tables');
		echo $this -> tables -> load_table($backup_headings, $backup_files, $options);
	}

	public function start_recovery() {
		ini_set('memory_limit', '-1'); 
		$file_name =$_POST['file_name'];
		$file_path =  FCPATH.'backup_db/'.$file_name;
		$unzip = $this -> uncompress_zip($file_path);
		$file_path = str_replace(".zip", "", $file_path);
		$file_path = (strpos($file_path, '.sql') !== false) ? $file_path : $file_path.'.sql' ;
		$file_path =  '"'.realpath($file_path).'"';

		$CI = &get_instance();
		$CI -> load -> database();
		$hostname = $this -> session -> userdata("db_host");
		$port = $this -> session -> userdata("db_port");
		$username = $this -> session -> userdata("db_user");
		$password = $this -> session -> userdata("db_pass");
		$current_db = $this -> session -> userdata("db_name");
		$recovery_status = false;

		$this -> load -> dbutil();
		if ($this -> dbutil -> database_exists($current_db)) {

			$link = @mysql_connect($hostname, $username, $password);
			$sql = "SHOW TABLES FROM $current_db";
			$result = @mysql_query($sql, $link);
			$count = mysql_num_rows($result);
			if ($count==0) {
				$mysql_home = realpath($_SERVER['MYSQL_HOME']) . "\mysql";
				$mysql_bin = str_replace("\\", "\\\\", $mysql_home);
				$mysql_con = $mysql_bin . ' -u ' . $username . ' -p' . $password .  ' -P' . $port . ' -h ' . $hostname . ' ' . $current_db . ' < ' . $file_path;
				exec($mysql_con);
				$recovery_status = true;
				
				$db_config_file =  str_replace('\tools', '', FCPATH).'application/config/db_conf.php';

				if(file_exists($db_config_file)){
					$file = fopen($db_config_file,"w");
					fwrite($file,"". "\r\n");
					fwrite($file,"<?php ". "\r\n");
					fwrite($file,"\$db['default']['hostname'] = '$hostname';". "\r\n");
					fwrite($file,"\$db['default']['username'] = '$username';". "\r\n");
					fwrite($file,"\$db['default']['password'] = '$password';". "\r\n");
					fwrite($file,"\$db['default']['database'] = '$current_db';". "\r\n");
					fwrite($file,"\$db['default']['port'] = $port;". "\r\n");
					fclose($file);
				}
			}
		}
		// $recovery_status = $this->delete_file($file_path);
		echo $recovery_status;
	}

	public function recovery_tasks(){
			// find sql files on root folder, zip & save zipped files to backup_db
		$files = scandir(FCPATH.'backup_db');
		foreach ($files as $key => $f) {
			if(!(strpos($f, '.sql'))){ 	continue;}
			if((strpos($f, '.sql.zip'))){ 	continue;}
			$this->delete_file('backup_db/'.$f);
		}
	}


	public function delete_file($file_path) {
		if(unlink($file_path)) {
			return true;
		}
		else {
			return false;
		}
	}

	public function uncompress_zip($file_path = null) {
		$this -> load -> library('unzip');
		$return_status = FALSE;
		$destination_path = realpath($file_path);
		$zip = new ZipArchive;
		if ($zip->open($destination_path) === TRUE) 
		{
			$zip->extractTo($this->backup_dir);
			$zip->close();
			$this->deleteDirectory($this->backup_dir.'/xampp');
			$return_status = TRUE;

		}
		return $return_status;
	}

	function deleteDirectory($dir) {
		if (!file_exists($dir)) {
			return true;
		}

		if (!is_dir($dir)) {
			return unlink($dir);
		}

		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..') {
				continue;
			}

			if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
				return false;
			}

		}

		return rmdir($dir);
	}
	public function template($data) {
		$data['show_menu'] = 0;
		$data['show_sidemenu'] = 0;
		$this -> load -> module('template');
		$this -> template -> index($data);
	}

}
