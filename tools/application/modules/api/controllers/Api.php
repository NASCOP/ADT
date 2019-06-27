<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Api extends MX_Controller {
	var $backup_dir = "./backup_db";

	var $api = '';        
	var $patient_module = '';            
	var $dispense_module = '';            
	var $appointment_module = ''; 
	var $logging = '';
	var $il_port = '';
	var $il_ip = '';
	var $adt_port = '';
	var $adt_url = '';



	function __construct() {
		parent::__construct();
		$this->load->library('ftp');
		$this->load->model('api_model');
		date_default_timezone_set('Africa/Nairobi');
	}

	public function index() {
		// "PATIENT_SOURCE":string"HBCT/VCT/OPD/MCH/TB-CLINIC/IPD-CHILD/IPD-ADULT/CCC/SELF-TEST"

// @marital status non existent in ADT... but needed in IQCare
		// Patient source -- HB
		if ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
	// API workflow 

			$input = file_get_contents('php://input');
			$input = str_replace("|~~", "", $input);
			$input = str_replace("~~|", "", $input);
			$message = json_decode($input);
			$message_type = $message->MESSAGE_HEADER->MESSAGE_TYPE;
			$this->writeLog('Incoming Message ',json_encode($input));

			switch ($message_type) {
				case 'ADT^A04':
				$this->processPatientRegistration($message);
				break;
				case 'ADT^A08':
				$this->processPatientUpdate($message);
				break;

				case 'ORU^R01':
				$this->processObservation($message);

				case 'SIU^S12':
				$this->processAppointment($message);
				break;
				case 'RDE^001':
				$this->processDrugOrder($message);
				break;
				default:
			# code...
				break;
			}

// 			API Process
// 1. Get message
// 2. decode message to PHP Array
// 3. Validate Message origin & type of message
// 4. Parse parameters
// 5. Action | [save,update]
// 6. Response

			die;
		}
		$api_messages = ['ADT^Â08','ADT^Â04','SIU^S12','ORU^R01','RDE^001'];
		$api_arr = array(
			'API Name'=> 'ADT PIS/EMR Interoperability API ',
			'API vesion' => 1.0,
			'API Messages' => $api_messages
		);
		header('Content-Type: application/json');

		echo json_encode($api_arr) ;

	}

	function processPatientRegistration($patient){
// internal & external patient ID matching

// 		$EXTERNAL_PATIENT_ID = $patient->PATIENT_IDENTIFICATION->EXTERNAL_PATIENT_ID->ID;
// 		$ASSIGNING_AUTHORITY = $patient->PATIENT_IDENTIFICATION->EXTERNAL_PATIENT_ID->ASSIGNING_AUTHORITY;
// 		$internal_patient = $this->api_model->getPatient(null,$EXTERNAL_PATIENT_ID);
// // getPatientInternalID($external_id,$ASSIGNING_AUTHORITY)
// 		if ($internal_patient){
// 			echo "Patient already exists";

// 			die;
// 		}
// internal identification is an array of objects
		$identification = array();
		foreach ($patient->PATIENT_IDENTIFICATION->INTERNAL_PATIENT_ID as $id) {
			$identification[$id->IDENTIFIER_TYPE] = $id->ID;
		}
		$ccc_no = ($identification['CCC_NUMBER']);
		$SENDING_FACILITY = $patient->MESSAGE_HEADER->SENDING_FACILITY;
		// $ccc_no = $this->parseCCC($ccc_no,$SENDING_FACILITY);

		$FIRST_NAME = $patient->PATIENT_IDENTIFICATION->PATIENT_NAME->FIRST_NAME;
		$MIDDLE_NAME = $patient->PATIENT_IDENTIFICATION->PATIENT_NAME->MIDDLE_NAME;
		$LAST_NAME = $patient->PATIENT_IDENTIFICATION->PATIENT_NAME->LAST_NAME;


		$MOTHER_NAME = $patient->PATIENT_IDENTIFICATION->MOTHER_NAME; 
		$DATE_OF_BIRTH = $patient->PATIENT_IDENTIFICATION->DATE_OF_BIRTH; 
		$SEX =  ( $patient->PATIENT_IDENTIFICATION->SEX  == 'M') ? 1 : 2 ;
		$VILLAGE = $patient->PATIENT_IDENTIFICATION->PATIENT_ADDRESS->PHYSICAL_ADDRESS->VILLAGE;
		// var_dump($patient);die;
		$WARD = $patient->PATIENT_IDENTIFICATION->PATIENT_ADDRESS->PHYSICAL_ADDRESS->WARD;
		$SUB_COUNTY = $patient->PATIENT_IDENTIFICATION->PATIENT_ADDRESS->PHYSICAL_ADDRESS->SUB_COUNTY;
		$COUNTY = $patient->PATIENT_IDENTIFICATION->PATIENT_ADDRESS->PHYSICAL_ADDRESS->COUNTY;
		$POSTAL_ADDRESS = $patient->PATIENT_IDENTIFICATION->PATIENT_ADDRESS->POSTAL_ADDRESS;
		$PHONE_NUMBER = $patient->PATIENT_IDENTIFICATION->PHONE_NUMBER;
		$MARITAL_STATUS = $patient->PATIENT_IDENTIFICATION->MARITAL_STATUS;
		$DEATH_DATE = $patient->PATIENT_IDENTIFICATION->DEATH_DATE;
		$DEATH_INDICATOR = $patient->PATIENT_IDENTIFICATION->DEATH_INDICATOR;

		// $patient_matching = $EXTERNAL_PATIENT_ID = $patient->PATIENT_IDENTIFICATION->EXTERNAL_PATIENT_ID;
		$ENROLLMENT_DATE =  $patient->PATIENT_VISIT->HIV_CARE_ENROLLMENT_DATE;

// $patient->PATIENT_VISIT->VISIT_DATE;
// $patient->PATIENT_VISIT->PATIENT_TYPE;
// $patient->PATIENT_VISIT->PATIENT_SOURCE;
		
		$patient = array(
			'facility_code'=>$SENDING_FACILITY,
			// 'dob'=>$DATE_OF_BIRTH,
			'dob'=> substr($DATE_OF_BIRTH,0, 4).'-'.substr($DATE_OF_BIRTH,4, 2).'-'.substr($DATE_OF_BIRTH, -2),
			'first_name'=>$FIRST_NAME,
			'gender'=>$SEX,
			'last_name'=>$LAST_NAME,
			'other_name'=>$MIDDLE_NAME,
			'patient_number_ccc'=>$ccc_no,
			'phone'=>$PHONE_NUMBER,
			'physical'=>$POSTAL_ADDRESS,
			'pob'=>$VILLAGE,
			'pob'=>$WARD,
			'pob'=>$SUB_COUNTY,
			'pob'=>$COUNTY,
			'alcohol'=> ' ',
			'current_regimen'=>' ',
			'height'=>' ',
			'pregnant'=>' ',
			'smoke'=>' ',
			'start_height'=>' ',
			'start_regimen'=>' ',
			'start_weight'=>' ',
			'active' => 1,
			'date_enrolled' => substr($ENROLLMENT_DATE,0, 4).'-'.substr($ENROLLMENT_DATE,4, 2).'-'.substr($ENROLLMENT_DATE, -2),
			'current_status' => $this->api_model->getActivePatientStatus()->id,
			'weight'=>' '
		);
		$this->writeLog('msg',json_encode($patient));
		$internal_patient_id = $this->api_model->savePatient($patient,$EXTERNAL_PATIENT_ID);
		$this->writeLog('internal_patient_id ',json_encode($internal_patient_id));
		// $patient_matching = $EXTERNAL_PATIENT_ID = $patient->PATIENT_IDENTIFICATION->EXTERNAL_PATIENT_ID->ID;

		$patient_matching = array(
			'internal_id'=>$internal_patient_id,
			'external_id'=>	$patient->PATIENT_IDENTIFICATION->EXTERNAL_PATIENT_ID->ID,
			'identifier_type'=>	$patient->PATIENT_IDENTIFICATION->EXTERNAL_PATIENT_ID->IDENTIFIER_TYPE,
			'assigning_authority'=> $patient->PATIENT_IDENTIFICATION->EXTERNAL_PATIENT_ID->ASSIGNING_AUTHORITY
		);
		$this->api_model->savePatientMatching($patient_matching);
	}

	function processPatientUpdate($patient){

		$identification = array();
		foreach ($patient->PATIENT_IDENTIFICATION->INTERNAL_PATIENT_ID as $id) {
			$identification[$id->IDENTIFIER_TYPE] = $id->ID;
		}
		$ccc_no = ($identification['CCC_NUMBER']);

		$internal_patient = $this->api_model->getPatient($ccc_no);
		if (!$internal_patient){
			$this->processPatientRegistration($patient);
			die;
			// registration successful exit(0)
		}

		$internal_patient_id = $internal_patient->id;
		$FIRST_NAME = $patient->PATIENT_IDENTIFICATION->PATIENT_NAME->FIRST_NAME;
		$MIDDLE_NAME = $patient->PATIENT_IDENTIFICATION->PATIENT_NAME->MIDDLE_NAME;
		$LAST_NAME = $patient->PATIENT_IDENTIFICATION->PATIENT_NAME->LAST_NAME;

		$MOTHER_NAME = $patient->PATIENT_IDENTIFICATION->MOTHER_NAME; 
		$DATE_OF_BIRTH = $patient->PATIENT_IDENTIFICATION->DATE_OF_BIRTH; 
		$SEX =  ( $patient->PATIENT_IDENTIFICATION->SEX  == 'M') ? 1 : 2 ;
		$VILLAGE = $patient->PATIENT_IDENTIFICATION->PATIENT_ADDRESS->PHYSICAL_ADDRESS->VILLAGE;
		// var_dump($patient);die;
		$WARD = $patient->PATIENT_IDENTIFICATION->PATIENT_ADDRESS->PHYSICAL_ADDRESS->WARD;
		$SUB_COUNTY = $patient->PATIENT_IDENTIFICATION->PATIENT_ADDRESS->PHYSICAL_ADDRESS->SUB_COUNTY;
		$COUNTY = $patient->PATIENT_IDENTIFICATION->PATIENT_ADDRESS->PHYSICAL_ADDRESS->COUNTY;
		$POSTAL_ADDRESS = $patient->PATIENT_IDENTIFICATION->PATIENT_ADDRESS->POSTAL_ADDRESS;
		$PHONE_NUMBER = $patient->PATIENT_IDENTIFICATION->PHONE_NUMBER;
		$MARITAL_STATUS = $patient->PATIENT_IDENTIFICATION->MARITAL_STATUS;
		$DEATH_DATE = $patient->PATIENT_IDENTIFICATION->DEATH_DATE;
		$DEATH_INDICATOR = $patient->PATIENT_IDENTIFICATION->DEATH_INDICATOR;

		$ENROLLMENT_DATE =  $patient->PATIENT_VISIT->HIV_CARE_ENROLLMENT_DATE;
		
		$patient = array(
			'dob'=> substr($DATE_OF_BIRTH,0, 4).'-'.substr($DATE_OF_BIRTH,4, 2).'-'.substr($DATE_OF_BIRTH, -2),
			'first_name'=>$FIRST_NAME,
			'gender'=>$SEX,
			'last_name'=>$LAST_NAME,
			'other_name'=>$MIDDLE_NAME,
			'patient_number_ccc'=>$ccc_no,
			'phone'=>$PHONE_NUMBER,
			'physical'=>$POSTAL_ADDRESS,
			'pob'=>$VILLAGE,
			'pob'=>$WARD,
			'pob'=>$SUB_COUNTY,
			'pob'=>$COUNTY,
			'alcohol'=> ' ',
			'current_regimen'=>' ',
			'height'=>' ',
			'pregnant'=>' ',
			'smoke'=>' ',
			'start_height'=>' ',
			'start_regimen'=>' ',
			'start_weight'=>' ',
			'active' => 1,
			'date_enrolled' => substr($ENROLLMENT_DATE,0, 4).'-'.substr($ENROLLMENT_DATE,4, 2).'-'.substr($ENROLLMENT_DATE, -2),
			// 'current_status' => 1,
			'current_status' => $this->api_model->getActivePatientStatus()->id,
			'weight'=>' '
		);

		$result = $this->api_model->updatePatient($patient,$internal_patient_id);
		var_dump($result);

	}

	function processObservation($obx){
		$identification = array();
		
		foreach ($obx->PATIENT_IDENTIFICATION->INTERNAL_PATIENT_ID as $id) {
			$identification[$id->IDENTIFIER_TYPE] = $id->ID;
		}
		$ccc_no = ($identification['CCC_NUMBER']);
		$internal_patient = $this->api_model->getPatient($ccc_no);

		if (!$internal_patient){
			writeLog('ORU Error ',"patient does not exist. Can't process observation");
			die;
		}

		$internal_patient_id = $internal_patient->id;
		$SENDING_FACILITY = $obx->MESSAGE_HEADER->SENDING_FACILITY;

 // Observation Result(s) - Array of Objects
		$observations = array();
		foreach ($obx->OBSERVATION_RESULT as $ob) {
			$observations[$ob->OBSERVATION_IDENTIFIER] = $ob->OBSERVATION_VALUE;
		}
		$START_HEIGHT = (isset($observations['START_HEIGHT'])) ? $observations['START_HEIGHT'] : false ;
		$START_WEIGHT = (isset($observations['START_WEIGHT'])) ? $observations['START_WEIGHT'] : false ;

		$IS_PREGNANT = (isset($observations['IS_PREGNANT'])) ? $observations['IS_PREGNANT'] : false ;
		$PRENGANT_EDD = (isset($observations['PRENGANT_EDD'])) ? $observations['PRENGANT_EDD'] : false ;
		$CURRENT_REGIMEN = (isset($observations['CURRENT_REGIMEN'])) ? $observations['CURRENT_REGIMEN'] : false ;		
		$IS_SMOKER = (isset($observations['IS_SMOKER'])) ? $observations['IS_SMOKER'] : false ;
		$IS_ALCOHOLIC = (isset($observations['IS_ALCOHOLIC'])) ? $observations['IS_ALCOHOLIC'] : false ;	
		$REGIMEN_CHANGE_REASON = (isset($observations['REGIMEN_CHANGE_REASON'])) ? $observations['REGIMEN_CHANGE_REASON'] : false ;
		if ($REGIMEN_CHANGE_REASON){ 
		// do regimen change/ drug stop
			// var_dump($REGIMEN_CHANGE_REASON);die;
		}

		$observation = array('facility_code'=>$SENDING_FACILITY,
			'patient_number_ccc'=>$ccc_no,
			'pregnant'=>$IS_PREGNANT,
			'smoke'=>$IS_SMOKER,
			'height'=>$START_HEIGHT,
			'start_height'=>$START_HEIGHT,
			'start_regimen'=>$CURRENT_REGIMEN,
			'start_weight'=>$START_WEIGHT,
			'weight'=>$START_HEIGHT);
		$result = $this->api_model->updatePatient($observation,$internal_patient_id);
	}

	function processAppointment($appointment){
		$identification = array();
		foreach ($appointment->PATIENT_IDENTIFICATION->INTERNAL_PATIENT_ID as $id) {
			$identification[$id->IDENTIFIER_TYPE] = $id->ID;
		}
		$ccc_no = ($identification['CCC_NUMBER']);
		$internal_patient_ccc = $this->api_model->getPatient($ccc_no);

		if (!$internal_patient_ccc){$this->writeLog('Patient not found ',$internal_patient_ccc);die;}

		$SENDING_APPLICATION = $appointment->MESSAGE_HEADER->SENDING_APPLICATION;
		$SENDING_FACILITY = $appointment->MESSAGE_HEADER->SENDING_FACILITY;
		$RECEIVING_APPLICATION = $appointment->MESSAGE_HEADER->RECEIVING_APPLICATION;
		$RECEIVING_FACILITY = $appointment->MESSAGE_HEADER->RECEIVING_FACILITY;
		$MESSAGE_DATETIME = $appointment->MESSAGE_HEADER->MESSAGE_DATETIME;
		$SECURITY = $appointment->MESSAGE_HEADER->SECURITY;
		$MESSAGE_TYPE = $appointment->MESSAGE_HEADER->MESSAGE_TYPE;
		$PROCESSING_ID = $appointment->MESSAGE_HEADER->PROCESSING_ID;



		$EXTERNAL_PATIENT_ID = $appointment->PATIENT_IDENTIFICATION->EXTERNAL_PATIENT_ID->ID;
		$INTERNAL_PATIENT_ID = $appointment->PATIENT_IDENTIFICATION->INTERNAL_PATIENT_ID[0]->ID;

		$FIRST_NAME = $appointment->PATIENT_IDENTIFICATION->PATIENT_NAME->FIRST_NAME;
		$MIDDLE_NAME = $appointment->PATIENT_IDENTIFICATION->PATIENT_NAME->MIDDLE_NAME;
		$LAST_NAME = $appointment->PATIENT_IDENTIFICATION->PATIENT_NAME->LAST_NAME;
		$PAN_NUMBER = $appointment->APPOINTMENT_INFORMATION[0]->PLACER_APPOINTMENT_NUMBER->NUMBER;
		$PAN_ENTITY = $appointment->APPOINTMENT_INFORMATION[0]->PLACER_APPOINTMENT_NUMBER->ENTITY;
		$APPOINTMENT_REASON = $appointment->APPOINTMENT_INFORMATION[0]->APPOINTMENT_REASON;
		$APPOINTMENT_TYPE = $appointment->APPOINTMENT_INFORMATION[0]->APPOINTMENT_TYPE;
		$APPOINTMENT_DATE = $appointment->APPOINTMENT_INFORMATION[0]->APPOINTMENT_DATE;
		$APPOINTMENT_DATE = substr($APPOINTMENT_DATE,0, 4).'-'.substr($APPOINTMENT_DATE,4, 2).'-'.substr($APPOINTMENT_DATE, -2);
		$APPOINTMENT_PLACING_ENTITY = $appointment->APPOINTMENT_INFORMATION[0]->APPOINTMENT_PLACING_ENTITY;
		$APPOINTMENT_LOCATION = $appointment->APPOINTMENT_INFORMATION[0]->APPOINTMENT_LOCATION;
		$ACTION_CODE = $appointment->APPOINTMENT_INFORMATION[0]->ACTION_CODE;
		$APPOINTMENT_NOTE = $appointment->APPOINTMENT_INFORMATION[0]->APPOINTMENT_NOTE;
		$APPINTMENT_HONORED = $appointment->APPOINTMENT_INFORMATION[0]->APPINTMENT_HONORED;

		$patient_appointment = array(
			'patient'=>	$internal_patient_ccc->patient_number_ccc,
			'facility' => $SENDING_FACILITY,
			'appointment'=>	$APPOINTMENT_DATE
		);
		$this->writeLog('saving patient appointment ',json_encode($patient_appointment));
		$this->writeLog('appointment type ',json_encode($APPOINTMENT_TYPE));

		$res = $this->api_model->saveAppointment($patient_appointment, $APPOINTMENT_TYPE);
		$this->writeLog('saved  patient appointment ',json_encode($res));

	}
	function processDrugOrder($order){
		$identification = array();
		foreach ($order->PATIENT_IDENTIFICATION->INTERNAL_PATIENT_ID as $id) {
			$identification[$id->IDENTIFIER_TYPE] = $id->ID;
		}
		$ccc_no = ($identification['CCC_NUMBER']);	

		$SENDING_FACILITY = $order->MESSAGE_HEADER->SENDING_FACILITY;
		$internal_patient_ccc = $this->api_model->getPatient($ccc_no);	
		// $internal_patient_ccc = $this->parseCCC($internal_patient_ccc,$SENDING_FACILITY);

		if (!$internal_patient_ccc){$this->writeLog('Patient not found ',$internal_patient_ccc);die;}


		$SENDING_APPLICATION = $order->MESSAGE_HEADER->SENDING_APPLICATION;
		$RECEIVING_APPLICATION = $order->MESSAGE_HEADER->RECEIVING_APPLICATION;
		$RECEIVING_FACILITY = $order->MESSAGE_HEADER->RECEIVING_FACILITY;
		$MESSAGE_DATETIME = $order->MESSAGE_HEADER->MESSAGE_DATETIME;
		$SECURITY = $order->MESSAGE_HEADER->SECURITY;
		$MESSAGE_TYPE = $order->MESSAGE_HEADER->MESSAGE_TYPE;
		$PROCESSING_ID = $order->MESSAGE_HEADER->PROCESSING_ID;
		$EXTERNAL_PATIENT_ID = $order->PATIENT_IDENTIFICATION->EXTERNAL_PATIENT_ID->ID;
		$INTERNAL_PATIENT_ID = $order->PATIENT_IDENTIFICATION->INTERNAL_PATIENT_ID[0]->ID;

		$FIRST_NAME = $order->PATIENT_IDENTIFICATION->PATIENT_NAME->FIRST_NAME;
		$MIDDLE_NAME = $order->PATIENT_IDENTIFICATION->PATIENT_NAME->MIDDLE_NAME;
		$LAST_NAME = $order->PATIENT_IDENTIFICATION->PATIENT_NAME->LAST_NAME;

		$ORDER_CONTROL = $order->COMMON_ORDER_DETAILS->ORDER_CONTROL;
		$PLACER_ORDER_NUMBER = $order->COMMON_ORDER_DETAILS->PLACER_ORDER_NUMBER->NUMBER;
		$ORDER_STATUS = $order->COMMON_ORDER_DETAILS->ORDER_STATUS;
		$OP_FIRST_NAME = $order->COMMON_ORDER_DETAILS->ORDERING_PHYSICIAN->FIRST_NAME;
		$OP_MIDDLE_NAME = $order->COMMON_ORDER_DETAILS->ORDERING_PHYSICIAN->MIDDLE_NAME;
		$OP_LAST_NAME = $order->COMMON_ORDER_DETAILS->ORDERING_PHYSICIAN->LAST_NAME;
		$OP_PREFIX = $order->COMMON_ORDER_DETAILS->ORDERING_PHYSICIAN->PREFIX;
		$TRANSACTION_DATETIME = $order->COMMON_ORDER_DETAILS->TRANSACTION_DATETIME;
		$NOTES = $order->COMMON_ORDER_DETAILS->NOTES;
		$pe = array();


// PHARMACY_ENCODED_ORDER

		$pe_order = array();
		foreach ($order->PHARMACY_ENCODED_ORDER as $eo) {
			array_push($pe_order, $eo);
		}


		$pe = array(
			'order_number' => $PLACER_ORDER_NUMBER,
			'order_status' => $ORDER_STATUS,
			'patient' => $internal_patient_ccc->patient_number_ccc,
			'order_physician' => $OP_FIRST_NAME.' '.$OP_MIDDLE_NAME.' '.$OP_LAST_NAME,
			'notes' => $NOTES
		);

		$this->writeLog('prescription ',json_encode($pe));
		$this->writeLog('prescription order ',json_encode($pe_order));

		// var_dump($pe);
		$res  = $this->api_model->saveDrugPrescription($pe,$pe_order);
		$this->writeLog('res ',json_encode($res));

# @todo check if order exists
# if doesn't exist, create new order .
# else update 

	}

	public function getAppointment($appointment_id)
	{
		$pat = $this->api_model->getPatientAppointment($appointment_id);
		$message_type = 'SIU^S12';

		$appoint['MESSAGE_HEADER'] = array( 
			'SENDING_APPLICATION'   =>       "ADT",
			'SENDING_FACILITY'      =>       $pat->facility_code,
			'RECEIVING_APPLICATION' =>       "IL",
			'RECEIVING_FACILITY'    =>       $pat->facility_code,
			'MESSAGE_DATETIME'      =>       date('Ymdhis'),
			'SECURITY'              =>       "",
			'MESSAGE_TYPE'          =>       $message_type,
			'PROCESSING_ID'         =>       "P"
		);
		$appoint['PATIENT_IDENTIFICATION'] = array(
			'EXTERNAL_PATIENT_ID' => array('ID'=>$pat->external_id, 'IDENTIFIER_TYPE' =>"GODS_NUMBER",'ASSIGNING_AUTHORITY' =>"MPI"),
			'INTERNAL_PATIENT_ID' => [
				array('ID'=>$this->constructCCC($pat->patient_number_ccc,$pat->facility_code,true), 'IDENTIFIER_TYPE' =>"CCC_NUMBER",'ASSIGNING_AUTHORITY' =>"CCC")
			],
			'PATIENT_NAME' => array('FIRST_NAME'=>$pat->first_name, 'MIDDLE_NAME' =>$pat->last_name,'LAST_NAME' =>$pat->other_name)
		);
		$appoint['APPOINTMENT_INFORMATION'] = [array(
			'PLACER_APPOINTMENT_NUMBER' => array('NUMBER' => $appointment_id, 'ENTITY' =>"ADT"),
			'APPOINTMENT_REASON' => "REGIMEN REFILL",
			'APPOINTMENT_TYPE' => "PHARMACY APPOINTMENT",
			'APPOINTMENT_DATE' => $pat->appointment,
			'APPOINTMENT_PLACING_ENTITY' => "ADT",
			'APPOINTMENT_LOCATION' => "PHARMACY",
			'ACTION_CODE' => "A",
			'APPOINTMENT_NOTE' => "TO COME BACK FOR A REFILL",
			'APPOINTMENT_HONORED' => "N"
		)];

		echo "<pre>";
		echo(json_encode($appoint, JSON_PRETTY_PRINT));
		echo "</pre>";
		$this->writeLog('APPOINTMENT SCHEDULE SIU^S12 ',json_encode($appoint));
		$this->tcpILRequest(null, json_encode($appoint));
	}

	public function getPatient($patient_id, $msg_type){
		$pat =   $this->api_model->getPatientbyID($patient_id);

		$message_type = ($msg_type == 'ADD') ? 'ADT^A04' : 'ADT^A08' ;
		$patient['MESSAGE_HEADER'] = array( 
			'SENDING_APPLICATION'   =>       "ADT",
			'SENDING_FACILITY'      =>       $pat->facility_code,
			'RECEIVING_APPLICATION' =>       "IL",
			'RECEIVING_FACILITY'    =>       $pat->facility_code,
			'MESSAGE_DATETIME'      =>       date('Ymdhis'),
			'SECURITY'              =>       "",
			'MESSAGE_TYPE'          =>       $message_type,
			'PROCESSING_ID'         =>       "P"
		);
		$patient['PATIENT_IDENTIFICATION'] = array(
			'EXTERNAL_PATIENT_ID' => $this->api_model->getPatientExternalID($patient_id),
			 // array('ID'=>$pat->external_id, 'IDENTIFIER_TYPE' =>"GODS_NUMBER",'ASSIGNING_AUTHORITY' =>"MPI"),

			// fetch external identifications
			'INTERNAL_PATIENT_ID' => [
				array('ID'=>$pat->id, 'IDENTIFIER_TYPE' =>"SOURCE_SYSTEM_ID",'ASSIGNING_AUTHORITY' =>"ADT"),
				array('ID'=>$this->constructCCC($pat->patient_number_ccc,$pat->facility_code,true), 'IDENTIFIER_TYPE' =>"CCC_NUMBER",'ASSIGNING_AUTHORITY' =>"CCC")
			],
			'PATIENT_NAME' => array('FIRST_NAME'=>$pat->first_name, 'MIDDLE_NAME' =>$pat->other_name,'LAST_NAME' =>$pat->last_name),
			'DATE_OF_BIRTH' => date('Ymd',strtotime($pat->dob)),
			'DATE_OF_BIRTH_PRECISION' => 'EXACT', 
			'SEX' => substr($pat->patient_gender, 0,1),
			'PATIENT_ADDRESS' => array('PHYSICAL_ADDRESS'=>array('VILLAGE' => '','WARD' => '','SUB_COUNTY' => '','COUNTY' => ''),'POSTAL_ADDRESS' =>$pat->pob),
			'PHONE_NUMBER' => $pat->phone,
			'MARITAL_STATUS' => $pat->partner_status, // if partner the nyes other wise unknown
			'DEATH_DATE' => '',
			'DEATH_INDICATOR' => ''
		);
		$patient['NEXT_OF_KIN'] = array();

		$patient['PATIENT_VISIT'] = array(
			'VISIT_DATE'=> date('Ymd',strtotime($pat->date_enrolled)),
			'PATIENT_TYPE'=> 'NEW', // TRANSFER IN, NEW = active, TRANSIT, 
			'PATIENT_SOURCE'=> 'CCC',
			'HIV_CARE_ENROLLMENT_DATE'=> date('Ymd',strtotime($pat->date_enrolled))

		);
		echo "<pre>";
		echo(json_encode($patient,JSON_PRETTY_PRINT));
		$this->writeLog('PATIENT '.$msg_type.' ' .$message_type ,json_encode($patient));
		$this->tcpILRequest(null, json_encode($patient));
		$this->getObservation($pat->patient_number_ccc);

	}

	public function getObservation($patient_id){
		echo "sending observations";
		$pat =   $this->api_model->getPatient($patient_id);

		$message_type = 'ORU^R01' ;
		$observations['MESSAGE_HEADER'] = array( 
			'SENDING_APPLICATION'   =>       "ADT",
			'SENDING_FACILITY'      =>       $pat->facility_code,
			'RECEIVING_APPLICATION' =>       "IL",
			'RECEIVING_FACILITY'    =>       $pat->facility_code,
			'MESSAGE_DATETIME'      =>       date('Ymdhis'),
			'SECURITY'              =>       "",
			'MESSAGE_TYPE'          =>       $message_type,
			'PROCESSING_ID'         =>       "P"
		);
		$observations['PATIENT_IDENTIFICATION'] = array(
			'EXTERNAL_PATIENT_ID' => $this->api_model->getPatientExternalID($patient_id),
			 // array('ID'=>$pat->external_id, 'IDENTIFIER_TYPE' =>"GODS_NUMBER",'ASSIGNING_AUTHORITY' =>"MPI"),

			// fetch external identifications
			'INTERNAL_PATIENT_ID' => [
				array('ID'=>$pat->id, 'IDENTIFIER_TYPE' =>"SOURCE_SYSTEM_ID",'ASSIGNING_AUTHORITY' =>"ADT"),
				array('ID'=>$this->constructCCC($pat->patient_number_ccc,$pat->facility_code,true), 'IDENTIFIER_TYPE' =>"CCC_NUMBER",'ASSIGNING_AUTHORITY' =>"CCC")
			],
			'PATIENT_NAME' => array('FIRST_NAME'=>$pat->first_name, 'MIDDLE_NAME' =>$pat->last_name,'LAST_NAME' =>$pat->other_name)
		);

// construct & send observation (obx ) message
		$observations['OBSERVATION_RESULT'] = array(
			array(
				'SET_ID' => 1,
				'OBSERVATION_IDENTIFIER' => 'START_HEIGHT',
				'CODING_SYSTEM' => 1,
				'VALUE_TYPE' => "NM",
				'OBSERVATION_VALUE' => $pat->start_height,
				'UNITS' => "CM",
				'OBSERVATION_RESULT_STATUS' => "F",
				'OBSERVATION_DATETIME' => date('Ymdhis',strtotime($pat->date_enrolled)),
				'ABNORMAL_FLAGS' => "N"),
			array(
				'SET_ID' => "2",
				'OBSERVATION_IDENTIFIER' => "START_WEIGHT",
				'CODING_SYSTEM' => "",
				'VALUE_TYPE' => "NM",
				'OBSERVATION_VALUE' => $pat->start_weight,
				'UNITS' => "KG",
				'OBSERVATION_RESULT_STATUS' => "F",
				'OBSERVATION_DATETIME' => date('Ymdhis',strtotime($pat->date_enrolled)),
				'ABNORMAL_FLAGS' => "N"
			),
			array(
				'SET_ID' =>"3",
				'OBSERVATION_IDENTIFIER' => 'IS_PREGNANT',
				'CODING_SYSTEM' =>"",
				'VALUE_TYPE' =>"CE",
				'OBSERVATION_VALUE' =>($pat->pregnant=='0') ? 'NO' :'Yes',
				'UNITS' =>"YES/NO",
				'OBSERVATION_RESULT_STATUS' =>"F",
				'OBSERVATION_DATETIME' =>date('Ymdhis',strtotime($pat->date_enrolled)),
				'ABNORMAL_FLAGS' =>"N"
			),
			array(
				'SET_ID' => "4",
				'OBSERVATION_IDENTIFIER' => "PRENGANT_EDD",
				'CODING_SYSTEM' => "",
				'VALUE_TYPE' => "D",
				'OBSERVATION_VALUE' => "20170713110000",
				'UNITS' => "DATE",
				'OBSERVATION_RESULT_STATUS' => "F",
				'OBSERVATION_DATETIME' => date('Ymdhis',strtotime($pat->date_enrolled)),
				'ABNORMAL_FLAGS' => "N"
			),
			array(
				'SET_ID' => "5",
				'OBSERVATION_IDENTIFIER' => "CURRENT_REGIMEN",
				'CODING_SYSTEM' => "NASCOP_CODES",
				'VALUE_TYPE' => "CE",
				'OBSERVATION_VALUE' => $pat->current_regimen,
				'UNITS' => "",
				'OBSERVATION_RESULT_STATUS' => "F",
				'OBSERVATION_DATETIME' => date('Ymdhis',strtotime($pat->date_enrolled)),
				'ABNORMAL_FLAGS' => "N"
			),
			array(
				'SET_ID' => "6",
				'OBSERVATION_IDENTIFIER' => "IS_SMOKER",
				'CODING_SYSTEM' => "",
				'VALUE_TYPE' => "CE",
				'OBSERVATION_VALUE' => ($pat->smoke=='0') ? 'NO' :'Yes',
				'UNITS' => "YES/NO",
				'OBSERVATION_RESULT_STATUS' => "F",
				'OBSERVATION_DATETIME' => date('Ymdhis',strtotime($pat->date_enrolled)),
				'ABNORMAL_FLAGS' => "N"
			),
			array(
				'SET_ID' =>"6",
				'OBSERVATION_IDENTIFIER' =>"IS_ALCOHOLIC",
				'CODING_SYSTEM' =>"",
				'VALUE_TYPE' =>"CE",
				'OBSERVATION_VALUE' =>($pat->alcohol=='0') ? 'NO' :'Yes',
				'UNITS' =>"YES/NO",
				'OBSERVATION_RESULT_STATUS' =>"F",
				'OBSERVATION_DATETIME' =>date('Ymdhis',strtotime($pat->date_enrolled)),
				'ABNORMAL_FLAGS' =>"N"
			)

		);

		echo "<pre>";
		echo(json_encode($observations,JSON_PRETTY_PRINT));
		$this->writeLog('PATIENT '.$msg_type.' ' .$message_type ,json_encode($observations));
		$this->tcpILRequest(null, json_encode($observations));
	}

	public function getDispensing($order_id){
		$pats =   $this->api_model->getDispensing($order_id);
		$message_type = 'RDS^O13';

		$dispense['MESSAGE_HEADER'] = array( 
			'SENDING_APPLICATION'   =>       "ADT",
			'SENDING_FACILITY'      =>       $pats[0]->facility_code,
			'RECEIVING_APPLICATION' =>       "IL",
			'RECEIVING_FACILITY'    =>       $pats[0]->facility_code,
			'MESSAGE_DATETIME'      =>       date('Ymdhis'),
			'SECURITY'              =>       "",
			'MESSAGE_TYPE'          =>       $message_type,
			'PROCESSING_ID'         =>       "P"
		);
		$dispense['PATIENT_IDENTIFICATION'] = array(
			'EXTERNAL_PATIENT_ID' => array('ID'=>$pats[0]->external_id, 'IDENTIFIER_TYPE' =>"GODS_NUMBER",'ASSIGNING_AUTHORITY' =>"MPI"),
			'INTERNAL_PATIENT_ID' => [
				array('ID'=> $pats[0]->patient_number_ccc, 'IDENTIFIER_TYPE' =>"CCC_NUMBER",'ASSIGNING_AUTHORITY' =>"CCC")
			],
			'PATIENT_NAME' => array('FIRST_NAME'=>$pats[0]->first_name, 'MIDDLE_NAME' =>$pats[0]->other_name,'LAST_NAME' =>$pats[0]->last_name)
		);
		$dispense['COMMON_ORDER_DETAILS'] = array(
			'ORDER_CONTROL' => "NW",
			'PLACER_ORDER_NUMBER' => array('NUMBER'=>$pats[0]->order_number, 'ENTITY' =>"IQCARE"),
			'FILLER_ORDER_NUMBER' => array('NUMBER'=>$pats[0]->visit_id, 'ENTITY' =>"ADT"),
			'ORDER_STATUS' => "NW",
			'ORDERING_PHYSICIAN' => array('FIRST_NAME'=>$pats[0]->order_physician, 'MIDDLE_NAME' =>"", 'LAST_NAME' =>"", 'PREFIX' => "DR"),
			'TRANSACTION_DATETIME' => $pats[0]->timecreated,
			'NOTES' => $pats[0]->notes
		);

		/*Loop Drugs*/
		foreach ($pats as $key => $pat) {
			$dispense['PHARMACY_ENCODED_ORDER'][$key] = array(
				'DRUG_NAME' => $pat->drug_name, 
				'CODING_SYSTEM' => "NASCOP_CODES", 
				'STRENGTH' => $pat->drug_strength, 
				'DOSAGE' => $pat->dosage, 
				'FREQUENCY' => $pat->frequency, 
				'DURATION' => $pat->disp_duration, 
				'QUANTITY_PRESCRIBED' => $pat->quantity_prescribed,
				'PRESCRIPTION_NOTES' => $pat->prescription_notes
			);
			$dispense['PHARMACY_DISPENSE'][$key] = array(
				'DRUG_NAME' => $pat->drug_name, 
				'CODING_SYSTEM' => "NASCOP_CODES", 
				'ACTUAL_DRUGS' => $pat->drugcode,
				'STRENGTH' => $pat->drug_strength, 
				'DOSAGE' => $pat->disp_dose,
				'FREQUENCY' => $pat->frequency,
				'DURATION' => $pat->disp_duration,
				'QUANTITY_DISPENSED' => $pat->disp_quantity,
				'DISPENSING_NOTES' => $pat->comment
			);
		}

		echo "<pre>";
		echo(json_encode($dispense, JSON_PRETTY_PRINT));
		echo "</pre>";
		$this->writeLog('PHARMACY DISPENSE RDS^O13 ',json_encode($dispense));
		$this->tcpILRequest(null, json_encode($dispense));
	}


	function postILRequest($request){
		// echo $request;
		$this->init_api_values();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->il_ip .':'.$this->il_port);

		curl_setopt_array($ch, array(
			CURLOPT_POST => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			),
			CURLOPT_POSTFIELDS => $request
		));


		$json_data = curl_exec($ch); 
		if (empty($json_data)) {
			$message = "cURL Error: " . curl_error($ch)."<br/>";
		} else {
			// message sent successfully
		}
	}

	function tcpILRequest($request_type, $request){
		var_dump($this->il_ip);
		$this->init_api_values();
		// echo "sending tcp request";
		$fp = fsockopen($this->il_ip, $this->il_port, $errno, $errstr, 30);
		if (!$fp) {
			echo "$errstr ($errno)<br />\n";
		} else {
			fwrite($fp, '|~~'.$request.'~~|');
			fclose($fp);
			print date('H:i:s');
		}
	}
	function writeLog($logtype,$msg){

		$fp = fopen('IL-api.log', (file_exists('IL-api.log')) ? 'a' : 'w');

		fwrite($fp, date('Y-m-d H:i:s'). ' '.$logtype.' : '. $msg."\r\n");
		// fwrite($fp,'\n');

		fclose($fp);

	}
	public function init_api_values(){
		$CI = &get_instance();
		$CI -> load -> database();

		$sql="SELECT * FROM api_config";
		$query = $CI-> db -> query($sql);
		$api_config = $query -> result_array();

		$conf = array();
		foreach ($api_config as $ob) {
			$conf[$ob['config']] = $ob['value'];
		}

		$this->api = ($conf['api_status'] =='on') ? TRUE : FALSE ;
		$this->patient_module = ($conf['api_patients_module'] =='on') ? TRUE : FALSE ;
		$this->dispense_module = ($conf['api_dispense_module'] =='on') ? TRUE : FALSE ;
		$this->appointment_module = ($conf['api_appointments_module'] =='on') ? TRUE : FALSE ;
		$this->adt_url = (strlen($conf['api_adt_url'])> 2) ? $conf['api_adt_url'] : FALSE ;
		$this->adt_port = (strlen($conf['api_adt_port']) > 1) ? $conf['api_adt_port'] : FALSE;
		$this->il_ip = (strlen($conf['api_il_ip']) > 1) ? $conf['api_il_ip'] : FALSE;
		$this->il_port = (strlen($conf['api_il_port']) > 1) ? $conf['api_il_port'] : FALSE;
		$this->logging = $conf['api_logging'] == 'on'? TRUE : FALSE;

		return $api_config;
	}

	public function parseCCC($ccc,$mfl_code = false){

		/*
			Strip CCC of any apprearance of mfl code & special characters and return padded ccc number
		*/
			$ccc = str_replace($mfl_code,"", $ccc);
			$ccc = str_replace(" ","", $ccc);
			$ccc = str_replace("-","", $ccc);

			return  $ccc;
		}
		public function constructCCC($ccc,$mfl_code = false,$hyphen = false){
			$hyphen = ($hyphen) ? '-' : '' ;

			for ($i=strlen($ccc); $i < 5 ; $i++) { 
				$ccc='0'.$ccc;
			}
			for ($i=strlen($mfl_code); $i < 5 ; $i++) { 
				$ccc='0'.$mfl_code;
			}
			// return ($mfl_code.$hyphen.$ccc);
			return ($ccc);
		}

		public function settings(){
			if ($_POST){
				if ($this->api_model->saveAPIConfig($_POST)){
					$data['message'] = 'Settings Saved successfully';
				}

			}

			$data['api_config'] = $this->init_api_values();
			$data['active_menu'] = 8;
			$data['content_view'] = "api/settings_view";
			$data['title'] = "Dashboard | API Settings";
			$this -> template($data);
		}

		public function template($data) {
			$data['show_menu'] = 0;
			$data['show_sidemenu'] = 0;
			$this -> load -> module('template');
			$this -> template -> index($data);
		}

	}
