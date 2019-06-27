<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Update extends MX_Controller {
	var $nascop_url = "";
	function __construct() {
		parent::__construct();
		ini_set("max_execution_time", "100000");
		ini_set("allow_url_fopen", '1');
		// $this -> load -> library('github_updater');
		// $this -> load -> library('Unzip');

		$dir = realpath($_SERVER['DOCUMENT_ROOT']);
	    $link = $dir . "\\ADT\\assets\\nascop.txt";
		$this -> nascop_url = file_get_contents($link);
	}

	public function index($facility = "") {
		$this -> session -> set_userdata("facility", $facility);
		$sql = "SELECT hash_value,update_time FROM git_log ORDER BY id desc";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();

		$headings = array('Hash value', 'Timestamp');
		$options = '';
		$this -> load -> module('table');
		$data['update_log'] = $this -> table -> load_table($headings, $results, $options, 0);

		if (file_exists($this -> session -> userdata("temp_file"))) {
			unlink($this -> session -> userdata("temp_file"));
			$this -> session -> unset_userdata("temp_file");
		}
		$data['update_status'] = $this -> checkUpdate();
		$data['content_view'] = "github/github_v";
		$data['title'] = "Dashboard | System Update";
		$this -> template($data);
	}

	public function checkUpdate() {
		$update_status = 0;
		$hasUpdate = $this -> github_updater -> has_update();
		if ($hasUpdate > 0) {
			$update_status = 1;
		}
		return $update_status;
	}

	public function checkJsonUpdate() {
		$update_status = 0;
		$hasUpdate = $this -> github_updater -> has_update();
		if ($hasUpdate > 0) {
			$update_status = 1;
		}
		echo json_encode($update_status);
	}

	public function setLog($hash) {
		$sql = "INSERT INTO git_log(hash_value) VALUES ('" . $hash . "')";
		$query = $this -> db -> query($sql);
	}

	public function runGithubUpdater() {
		$hasUpdate = $this -> github_updater -> has_update();
		$hash = $this -> github_updater -> get_hash();
		$original_hash = $hash;
		$hash = $hash . ".zip";
		if ($hasUpdate > 0) {
			$this -> github_updater -> update();
			$first_dir = $this -> unzip_update($hash);
			$this -> copy_files($original_hash);
			$message = 'Done updating System';
			//$this -> runSQL();
			$this -> setLog($original_hash);
			$this -> set_config_hash($original_hash);
			$message .= $this -> send_log($original_hash);
			$this -> session -> set_userdata('msg_success', $message);
		} else {
			$message = "No Update Available";
			$this -> session -> set_userdata('msg_error', $message);
		}
		$this -> session -> set_userdata("temp_file", $hash);
		redirect('github');
	}

	public function unzip_update($hash) {
		$destination_path = $_SERVER['DOCUMENT_ROOT'];
		$unzip = new Unzip();
		return $this -> unzip -> extract($hash, $destination_path);
	}

	public function copy_files($hash) {
		$github_user = $this -> config -> item("github_user");
		$github_repo = $this -> config -> item("github_repo");
		$this -> config -> set_item('current_commit', $hash);
		$target_file = "ADT";

		$hash_5 = substr($hash, 0, 7);
		$first_dir = $github_user . "-" . $github_repo . "-" . $hash_5;

		$dir = realpath($_SERVER['DOCUMENT_ROOT']);
		$dir = $dir . "\\" . $target_file;
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
		}

		$tempdir = realpath($_SERVER['DOCUMENT_ROOT'] . "\\" . $first_dir) . "\\*";
		$enddir = realpath($_SERVER['DOCUMENT_ROOT'] . "\\" . $target_file);
		$command = "xcopy " . "\"" . $tempdir . "\"" . " " . "\"" . $enddir . "\"" . " " . "/E/Y";
		exec($command, $output, $status = true);

		if ($status) {
			$tempdir = "\"" . realpath($_SERVER['DOCUMENT_ROOT'] . "\\" . $first_dir) . "\"";
			$command = "rd /S " . $tempdir . "  /Q";
			exec($command, $output, $status = true);
		}

	}

	public function runSQL() {
	    $dir = realpath($_SERVER['DOCUMENT_ROOT']);
	    $link = $dir . "\\ADT\\assets\sql.txt";
		$results = file_get_contents($link);
		if ($results != null) {
			$results = explode(";", $results);
			foreach ($results as $i => $result) {
				if ($result != null) {
				    $db_debug = $this->db->db_debug;
					$this->db->db_debug = false;
					$this -> db -> query($result);
					$this->db->db_debug = $db_debug;
				}
			}
		}
	}

	public function set_config_hash($hash) {
		$config_file = 'application/config/github_updater.php';
		$lines = file($config_file, FILE_IGNORE_NEW_LINES);
		$count = count($lines);
		for ($i = 0; $i < $count; $i++) {
			$configline = '$config[\'current_commit\']';
			if (strstr($lines[$i], $configline)) {
				$lines[$i] = $configline . ' = \'' . $hash . '\';';
				$file = implode(PHP_EOL, $lines);
				$handle = @fopen($config_file, 'w');
				fwrite($handle, $file);
				fclose($handle);
				return true;
			}
		}
		return false;
	}

	public function send_log($original_hash="") {
		$url = $this -> nascop_url . "sync/gitlog";
		$facility_code = $this -> session -> userdata("facility");
		$results = array("facility" => $facility_code, "hash_value" => $original_hash);
		$json_data = json_encode($results, JSON_PRETTY_PRINT);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('json_data' => $json_data));
		$json_data = curl_exec($ch);
		if (empty($json_data)) {
			$message = "cURL Error: " . curl_error($ch);
		} else {
			$messages = json_decode($json_data, TRUE);
			$message = $messages[0];
		}
		curl_close($ch);
		return $message;
	}

	public function template($data) {
		$data['show_menu'] = 0;
		$data['show_sidemenu'] = 0;
		$this -> load -> module('template');
		$this -> template -> index($data);
	}

}
