<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Updater Class
 *
 * This class is only for self-updating WebADT based on a library I found at PHPClasses:
 * @author		William Nguru
 * @link		http://bitbucket.org/Codeklerk
 * @license
 * @version     1.0.0
 */
class Updater {

	private $ADT_file = 'ADT.zip';

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access    Public
	 * @param     string
	 * @return    none
	 */
	function __construct() {
		// log_message('debug', '');
	}

	// --------------------------------------------------------------------


/* 
	Function for checking if connection to the internet exists
*/
	function check_connection(){
		$connected = fopen("http://www.google.com:80/","r");
		if($connected)
		{
			return true;
		} else {
			return false;
		}
	}

/* 
	Function for reading latest ADT release details
*/
	function check_ADTrelease(){
		// if new release exists then download release file first then keep checking locally
		// if release already installed delete release file 

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://adt.nascop.org/updateinfo.txt");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if(curl_exec($ch) === FALSE) {
         // echo "Error: " . curl_error($ch);
			$response =  false;
		} else {
			$response =  curl_exec($ch);
		}

		curl_close($ch);
		return $response;
	}

	function download_ADTRelease(){
		$rs = $this->check_ADTrelease();
        $rs = (json_decode($rs));
    	$returnable = false;

        if (file_put_contents(FCPATH.$this->ADT_file, fopen($rs->releaseURL, 'r'))){
        	$returnable = true;
        }
        return $returnable;
	}

		
	function check_ADTRelease_downloaded(){
		$rs = $this->check_ADTrelease();
        $rs = (json_decode($rs));
    	$returnable = false;

        if(md5_file(FCPATH.$this->ADT_file) == $rs->releaseChecksum && filesize(FCPATH.$this->ADT_file) == $rs->releaseSize ){
        	$returnable = true;
        }
        return $returnable;
	}

	function update_ADT(){
		$path = pathinfo(realpath($this->ADT_file), PATHINFO_DIRNAME);


		$zip = new ZipArchive;
		$res = $zip->open($this->ADT_file);
		if ($res === TRUE) {
			// extract it to the path we determined above
			$zip->extractTo($path);
			$zip->close();
			echo "Success: $this->ADT_file extracted to $path";
		} else {
			echo "Error! I couldn't open $this->ADT_file";
		}

	}

// function to inform remote site of new update activity


}

/* End of file Updater.php */
/* Location: ./system/libraries/Updater.php */
