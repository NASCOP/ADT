<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Github extends MX_Controller {
	var $nascop_url = "";
	function __construct() {
		parent::__construct();
		ini_set("max_execution_time", "100000");
		ini_set("allow_url_fopen", '1');

		$dir = realpath($_SERVER['DOCUMENT_ROOT']);
	    // $link = $dir . "\\ADT\\assets\\nascop.txt";
		// $this -> nascop_url = file_get_contents($link);
	}

	public function index($facility = "") {
		$data['git_releases'] = json_decode($this->fetch_url_contents('https://api.github.com/repos/nascop/ADT/releases?client_id=b576d4860bc586d89868&client_secret=282a631c881b9f4a307826713283df887a9f892d'));
		$data['active_menu'] = 4;
		$data['content_view'] = "github/github_v";
		$data['title'] = "Dashboard | System Update";
		$this -> template($data);
	}


 
	public function fetch_url_contents($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, "Web ADT");
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

	public function template($data) {
		$data['show_menu'] = 0;
		$data['show_sidemenu'] = 0;
		$this -> load -> module('template');
		$this -> template -> index($data);
	}

}
