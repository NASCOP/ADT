<?php
error_reporting(0);
class Notification_management extends MY_Controller {
	function __construct() {
		parent::__construct();

		ini_set("max_execution_time", "1000000");
		ini_set("memory_limit", '2048M');
		ini_set("allow_url_fopen", '1');
	}

	public function password_notification() {
		$user_id = $this -> session -> userdata("user_id");
		$days_before_pwdchange = 90;
		$notification_start = 10;
		$temp = "";

		$stmt = "SELECT $days_before_pwdchange-DATEDIFF(CURDATE(),u.Time_Created) as days_to_go
		         FROM users u
		         WHERE id='$user_id'";
		$q = $this -> db -> query($stmt);
		$rs = $q -> result_array();
		$days_before_pwdchange = $rs[0]['days_to_go'];
		if ($days_before_pwdchange > $notification_start) {
			$days_before_pwdchange = "";
			$temp = $days_before_pwdchange;
		} else {
			$temp = "<li><a href='#user_change_pass' data-toggle='modal'><i class='icon-th'></i>Password expiry <div class='badge badge-important'>" . $days_before_pwdchange . " Days </div></a><li>";
		}

		echo $temp;
	}

	public function reporting_notification() {
		$deadline = date('Y-m-10');
		$today = date('Y-m-d');
		$notification_days = 10;
		$notification = "";
		$message = "";
		$notification_link = site_url('order/satellites_reported');
        
        //get reporting satellites
		$start_date = date('Y-m-01', strtotime("-1 month"));
		$facility_code = $this -> session -> userdata("facility");
		$central_site = Sync_Facility::getId($facility_code, 0);
		$central_site = $central_site['id'];

		$sql = "SELECT 
		            sf.name as facility_name,
		            sf.code as facility_code,
		            IF(c.id,'reported','not reported') as status
		        FROM sync_facility sf
		        LEFT JOIN cdrr c ON c.facility_id=sf.id AND c.period_begin='$start_date' 
		        WHERE sf.parent_id='$central_site'
		        AND sf.category LIKE '%satellite%'
		        AND sf.name NOT LIKE '%dispensing%'
		        GROUP BY sf.id";
		$query = $this -> db -> query($sql);
		$satellites = $query -> result_array();
		if ($satellites) 
		{
	        if ($deadline > $today) 
	        {
				$diff = abs(strtotime($deadline) - strtotime($today));
				$years = floor($diff / (365 * 60 * 60 * 24));
				$months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
				$period = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));
				if ($notification_days >= $period) {
					$notification = "<li><a href='" . $notification_link . "'><i class='icon-th'></i>Reporting Deadline<div class='badge badge-important'>" . $period . " days</div></a><li>";
				}
			}
		}

		echo $notification;

	}

	public function update_notification() {
		$changelog_link=base_url().'README.md';
		echo "<li><a href='$changelog_link' ><i class='icon-th'></i>New System Changes</a></li>";
	}

	public function error_notification($display_array=false) {
		$temp="";
		$overall_total = 0;
		$error_array = array();

        $data['facility_code'] = $this->session->userdata('facility');
        $query = "SELECT * FROM Facilities where facilitycode=".$data['facility_code'];
        $query = $this->db->query($query);
        $cs = $query->result_array()[0]['ccc_separator'];

		/*Patients without Gender*/
		$sql['Patients without Gender'] = "SELECT p.patient_number_ccc,
		                                          p.gender,
		                                          p.id
										   FROM patient p 
										   LEFT JOIN gender g on g.id=p.gender
										   LEFT JOIN patient_status ps ON ps.id = p.current_status
										   WHERE (p.gender=' ' 
										   OR p.gender='' 
										   OR p.gender='null' 
										   OR p.gender is null)
										   AND p.active='1'
										   AND ps.Name LIKE '%active%'
										   GROUP BY p.patient_number_ccc;";

		/*Patients without DOB*/
		$sql['Patients without DOB'] = "SELECT p.patient_number_ccc,
											   p.dob,
		                                       p.id
										FROM patient p 
										LEFT JOIN patient_status ps ON ps.id = p.current_status
										WHERE (p.dob=' ' 
										OR p.dob='' 
										OR p.dob='null' 
										OR p.dob is null)
										AND p.active='1'
										AND ps.Name LIKE '%active%'
										GROUP BY p.patient_number_ccc;";

		/*Patients without Appointment*/
		$sql['Patients without Appointment'] = "SELECT  p.patient_number_ccc, 
														p.nextappointment, 
														ps.Name AS current_status,
														p.id
											    FROM patient p
											    LEFT JOIN patient_status ps ON ps.id = p.current_status
											    WHERE(p.nextappointment = ' '
											    OR p.nextappointment =  ''
											    OR p.nextappointment =  'null'
											    OR p.nextappointment IS NULL)
											    AND p.active = '1'
											    AND ps.Name LIKE '%active%'
											    AND p.active='1'
											    GROUP BY p.patient_number_ccc;";
		/*Patients without Current Regimen*/
		$sql['Patients without Current Regimen'] = "SELECT  p.patient_number_ccc,
															p.current_regimen,
															CONCAT_WS(' | ',r.regimen_code,r.regimen_desc) as regimen,
															p.id
													FROM patient p 
													LEFT JOIN regimen r ON r.id=p.current_regimen
													LEFT JOIN patient_status ps ON ps.id=p.current_status
													LEFT JOIN regimen_service_type rs ON rs.id=p.service
													WHERE (p.current_regimen=' '
													OR p.current_regimen=''
													OR p.current_regimen is null
													OR p.current_regimen='null')
													AND p.active='1'
												   	AND p.current_status = '1'
													AND rs.name NOT LIKE '%pmtct%'
													AND ps.Name LIKE '%active%'
													GROUP BY p.patient_number_ccc;";
		/*Patients without Start Regimen*/
		$sql['Patients without Start Regimen'] =   "SELECT p.patient_number_ccc, 
														 p.start_regimen, 
													     CONCAT_WS(  ' | ', r.regimen_code, r.regimen_desc ) AS regimen,
														 p.id
													FROM patient p
													LEFT JOIN regimen r ON r.id = p.start_regimen
													LEFT JOIN patient_status ps ON ps.id=p.current_status
													WHERE (p.start_regimen =  ' '
													OR p.start_regimen =  ''
													OR p.start_regimen IS NULL 
													OR p.start_regimen =  'null')
													AND p.active='1'
												   	AND ps.Name LIKE '%active%'
													GROUP BY p.patient_number_ccc;";
		/*Patients without Current Status*/
		$sql['Patients without Current Status'] =  "SELECT p.patient_number_ccc,
														  p.current_status,
														  ps.Name as status,
														  p.id
													FROM patient p
													LEFT JOIN patient_status ps ON ps.id=p.current_status
													WHERE(p.current_status=' '
													OR p.current_status=''
													OR p.current_status is null
													OR p.current_status='null')
													AND p.active='1'
												   	AND ps.Name LIKE '%active%'
													GROUP BY p.patient_number_ccc;";

		/*Patients without Service Line*/
		$sql['Patients without Service Line'] = "SELECT p.patient_number_ccc,
		                                                  p.service,
		                                                  rst.name as status,
		                                                  p.id
													FROM patient p
													LEFT JOIN regimen_service_type rst ON rst.id=p.service
													LEFT JOIN patient_status ps ON ps.id=p.current_status
													WHERE(p.service=' '
													OR p.service=''
													OR p.service is null
													OR p.service='null')
													AND p.active='1'
												   	AND ps.Name LIKE '%active%'
													GROUP BY p.patient_number_ccc;";

		/*Duplicate Patient Numbers*/
		$sql['Duplicate Patient Numbers'] ="SELECT p.patient_number_ccc,
		                                            count(p.patient_number_ccc) as total,
		                                            p.id
											FROM patient p
											LEFT JOIN patient_status ps ON ps.id=p.current_status
											WHERE p.active='1'
											AND ps.Name LIKE '%active%'
											GROUP by p.patient_number_ccc
											HAVING(total >1);";

		/*Patients without Enrollment date*/
		$sql['Patients without Enrollment date'] = "SELECT p.patient_number_ccc,
		                                                   p.id,
		                                                   p.date_enrolled
													FROM patient p
													LEFT JOIN patient_status ps ON ps.id=p.current_status
													WHERE char_length(p.date_enrolled)<10
													AND p.active='1'
													AND ps.Name LIKE '%active%'
													GROUP BY p.patient_number_ccc;";

		/*Patients without Status Change date*/
		$sql['Patients without Status Change date'] =  "SELECT p.patient_number_ccc,
		                                                      p.id,
		                                                      p.status_change_date
														FROM patient p 
														LEFT JOIN patient_status ps ON ps.id=p.current_status
														LEFT JOIN regimen r ON r.id=p.current_regimen
														LEFT JOIN regimen_service_type rst ON rst.id=p.service
														WHERE char_length(p.status_change_date)<10
														AND p.active='1'
														AND rst.Name NOT LIKE '%pep%'
														AND ps.Name LIKE '%active%'
														AND ( r.regimen_desc NOT LIKE '%pmtct%' OR ROUND( DATEDIFF( curdate( ) , p.dob ) /360 ) >2)
														GROUP BY p.patient_number_ccc;";
		/*Patients with the wrong Regimen*/
		$sql['Patients with the wrong Regimen'] =  "SELECT p.patient_number_ccc,
														p.current_regimen, 
														CONCAT_WS(' | ',r.regimen_code,r.regimen_desc) AS regimen,
														p.id FROM patient p
														LEFT JOIN regimen r ON r.id=p.current_regimen
														LEFT JOIN patient_status ps ON ps.id=p.current_status
														LEFT JOIN regimen_service_type rs ON rs.id=p.service
														WHERE p.current_regimen=r.id 
														AND p.active='1' 
														AND p.current_status = '1' 
														AND ((DATEDIFF(CURDATE(), p.dob) /360 >15 AND regimen_code LIKE '%CF%') OR (DATEDIFF(CURDATE(), p.dob) /360 <15 AND regimen_code LIKE '%AF%'))
														GROUP BY p.patient_number_ccc;";
		/*Patients without Start Regimen date*/
		$sql['Patients without Start Regimen date'] =  "SELECT p.patient_number_ccc,
					                                          p.id,
					                                          p.start_regimen_date
														FROM patient p
		                                                LEFT JOIN regimen_service_type rst ON rst.id=p.service
		                                                LEFT JOIN patient_status ps ON ps.id=p.current_status
														WHERE char_length(p.start_regimen_date)<10
														AND p.active='1'
		                                                AND rst.name NOT LIKE '%oi%'
		                                                AND ps.Name LIKE '%active%'
														GROUP BY p.patient_number_ccc;";

		/*Patients With Incorrect Current Regimens*/
		$sql['Patients with Incorrect Current Regimens'] = "SELECT p.id,
		                                                           p.patient_number_ccc, 
		                                                           p.first_name, 
		                                                           p.last_name, 
		                                                           p.service, 
		                                                           p.current_regimen, 
		                                                           r.regimen_desc, 
		                                                           rst1.Name AS FIRST,
		                                                           rst2.Name AS SECOND 
															FROM patient p
															LEFT JOIN regimen r ON r.id = p.current_regimen
															LEFT JOIN regimen_service_type rst1 ON rst1.id = p.service
															LEFT JOIN regimen_service_type rst2 ON rst2.id = r.type_of_service
															LEFT JOIN patient_status ps ON ps.id=p.current_status
															WHERE rst1.id != rst2.id
															AND rst2.Name NOT LIKE '%oi%'
															AND ps.Name LIKE '%active%'
															GROUP BY p.patient_number_ccc;";
															/*Patients With Incorrect Current Regimens*/
		$sql['Patients with malformed CCC numbers'] = "SELECT p.id,
					p.patient_number_ccc as ccc_no,
					UPPER(CONCAT_WS(' ',CONCAT_WS(' ',p.first_name,p.other_name),p.last_name)) as patient_name,
					p.phone as contact,
					DATE_FORMAT(p.date_enrolled,'%d-%b-%Y') as enrollment_date,
					DATE_FORMAT(p.nextappointment,'%d-%b-%Y') as next_appointment,
					UPPER(r.regimen_desc) as regimen_name,
					UPPER(ps.Name) as status_name
					FROM patient p 
					LEFT JOIN patient_status ps ON ps.id = p.current_status 
					LEFT JOIN regimen r ON r.id=p.current_regimen
					WHERE p.active = '1' 
					AND ps.Name LIKE '%active%'
					AND  p.patient_number_ccc NOT REGEXP  '(^[0-9]{5}".$cs."[0-9]{5})'
					AND p.patient_number_ccc NOT REGEXP '(^[0-9]{5}".$cs."[0-9]{4}".$cs."[0-9]{5})'
					AND p.patient_number_ccc NOT REGEXP '(^[a-zA-Z]{3}".$cs."[0-9]{4}".$cs."[0-9]{5})'
					AND p.patient_number_ccc NOT REGEXP '(^[a-zA-Z]{3}".$cs."[0-9]{5}".$cs."[0-9]{5})'
					AND p.patient_number_ccc NOT REGEXP '(^[a-zA-Z]{4}".$cs."[0-9]{5}".$cs."[0-9]{5})'					
					";

		if($display_array==true){
			foreach ($sql as $i => $q) {
				$q = $this -> db -> query($q);
				if ($this -> db -> affected_rows() > 0) {
					$overall_total += $this -> db -> affected_rows();
					$rs = $q -> result_array();
					$error_array[$i . "(" . $this -> db -> affected_rows() . ")"] = $rs;
				}
			}
		    return $error_array;
		}else{
			foreach ($sql as $i => $q) {
				$q = $this -> db -> query($q);
				if ($this -> db -> affected_rows() > 0) {
					$overall_total += $this -> db -> affected_rows();
				}
			}
			if ($overall_total > 1) {
				$temp_link = $order_link = site_url('notification_management/load_error_view');
				$temp = "<li><a href='" . $temp_link . "'><i class='icon-th'></i>Data Errors <div class='badge badge-important'>" . $overall_total . "</div></a><li>";
			}
			echo $temp;
		}	
													
	}

	public function load_error_view() {
		$data['errors'] = $this -> error_notification(true);
		$data['report_title'] = 'error_report';
		foreach ($data['errors'] as $error => $error_array) {
			$data['first_error'] = $error;
			break;
		}
		$data['content_view'] = "error_listing_v";
		$this -> base_params($data);
	}

	public function error_generator() {
		$array_text = '';
		$array_text = $this -> input -> post("array_text", true);
		$error_list = $this -> error_notification(true);
		$id_list = "";
		$access_level = $this -> session -> userdata('user_indicator');
		if (!empty($error_list)) {
			foreach ($error_list[$array_text] as $error_array) {
				$id_list .= "'" . $error_array['id'] . "',";

			}

			$id_list = substr($id_list, 0, -1);

			$stmt = "SELECT p.id,p.patient_number_ccc,p.first_name,p.other_name,p.last_name,p.phone,p.date_enrolled,p.nextappointment,r.regimen_desc,ps.Name,ps.Active
		         FROM patient p 
		         LEFT JOIN regimen r ON r.id=p.current_regimen
		         LEFT JOIN patient_status ps ON ps.id=p.current_status
		         WHERE p.id IN($id_list) and ps.Name LIKE '%active%' and p.active='1' 
		         GROUP BY p.patient_number_ccc";
			$q = $this -> db -> query($stmt);
			$rs = $q -> result_array();

			$dyn_table = '<table class="dataTables" id="patient_listing" border="1" >';
			$dyn_table .= '<thead><tr><th style="width:60px">CCC No</th><th>Patient Name</th><th>Contact</th><th style="width: 100px">Date Enrolled</th><th style="width: 100px">Next Appointment</th><th>Current Regimen</th><th style="width:150px">Status</th><th style="width:20%">Action</th></tr></thead><tbody>';
			foreach ($rs as $r) {
				$patient_name = strtoupper(trim($r['first_name'] . " " . $r['other_name'] . " " . $r['last_name']));
				$id = $r['id'];
				$link = "";
				$link = '<a href="' . base_url() . 'patient_management/viewDetails/' . $id . '">Detail</a> | <a href="' . base_url() . 'patient_management/edit/' . $id . '">Edit</a> ' . $link;
				if ($access_level == "facility_administrator") {
					if ($r['Active'] == 1) {
						$link .= '| <a href="' . base_url() . 'patient_management/disable/' . $id . '" class="red">Disable</a>';

					} else {
						$link .= '| <a href="' . base_url() . 'patient_management/enable/' . $id . '" class="green">Enable</a>';
					}
				}
				$appointment = "";
				$date_enrolled = "";
				$appointment = $r['nextappointment'];
				if ($appointment) {
					$appointment = date('d-M-Y', strtotime($r['nextappointment']));
				}
				$date_enrolled = $r['date_enrolled'];
				if ($date_enrolled) {
					$date_enrolled = date('d-M-Y', strtotime($r['date_enrolled']));
				}

				$dyn_table .= "<tr><td>" . strtoupper($r['patient_number_ccc']) . "</td><td>" . $patient_name . "</td><td>" . $r['phone'] . "</td><td>" . $date_enrolled . "</td><td>" . $appointment . "</td><td><b>" . strtoupper($r['regimen_desc']) . "</b></td><td><b>" . $r['Name'] . "</b></td><td>" . $link . "</td></tr>";
			}
			$dyn_table .= "</tbody></table>";
			echo $dyn_table;
		}
	}

	public function startRegimen_Error() {
		$sql = $this -> db -> query("SELECT p.patient_number_ccc, p.start_regimen, CONCAT_WS(  ' | ', r.regimen_code, r.regimen_desc ) AS regimen,p.id FROM patient p
			LEFT JOIN regimen r ON r.id = p.start_regimen
			WHERE (p.start_regimen =  ' '
			OR p.start_regimen =  ''
			OR p.start_regimen IS NULL 
			OR p.start_regimen =  'null')
			AND p.active='1'
			GROUP BY p.patient_number_ccc;");

		if ($sql -> num_rows() > 0) {
			foreach ($sql->result() as $rows) {
				$patient_ccc = $rows -> patient_number_ccc;
				$sql_get_first_regimen = "SELECT pv.last_regimen " . " FROM patient_visit pv WHERE pv.patient_id='$patient_ccc' AND pv.last_regimen!='' " . "  ORDER BY pv.dispensing_date ASC LIMIT 1";

				$result = $this -> db -> query($sql_get_first_regimen);
				$res = $result -> result_array();
				$first_regimen = $res[0]['last_regimen'];
				//echo $sql_get_first_regimen.'<br>';

				$sql = "UPDATE patient p " . "SET p.start_regimen='$first_regimen'" . " WHERE p.patient_number_ccc='" . $patient_ccc . "'";
				$result = $this -> db -> query($sql);
				//$res = $result->result_array();
				$this -> session -> set_userdata('msg_save_transaction', 'success');

				echo $this -> db -> affected_rows();
			}

		}
	}
        
        public function start_regimen_date_error() {
            $sql=  $this->db->query("SELECT p.patient_number_ccc, p.id, p.start_regimen_date
                                     FROM patient p 
                                     LEFT JOIN regimen_service_type rst ON rst.id=p.service
                                     WHERE char_length(p.start_regimen_date)<10
                                     AND p.active='1'
				     AND rst.name NOT LIKE '%oi%'
				     GROUP BY p.patient_number_ccc;");
            
            if ($sql -> num_rows() > 0) {
			foreach ($sql->result() as $rows) {
				$patient_ccc = $rows -> patient_number_ccc;
				$sql_get_date_enrolled = "SELECT date_enrolled FROM patient WHERE patient_number_ccc='$patient_ccc' AND date_enrolled!='' ";

				$result = $this -> db -> query($sql_get_date_enrolled);
				$res = $result -> result_array();
				$first_regimen_date = $res[0]['date_enrolled'];
				

				$sql = "UPDATE patient p " . "SET p.start_regimen_date='$first_regimen_date'" . " WHERE p.patient_number_ccc='" . $patient_ccc . "'";
				$result = $this -> db -> query($sql);
				//$res = $result->result_array();
				$this -> session -> set_userdata('msg_save_transaction', 'success');

				echo $this -> db -> affected_rows();
			}

		}
        }

	public function lost_to_followup() {
		$sql = $this -> db -> query("SELECT p.patient_number_ccc,
			                                p.current_regimen,
			                                CONCAT_WS(' | ',r.regimen_code,r.regimen_desc) as regimen,
			                                p.id
									FROM patient p 
									LEFT JOIN regimen r ON r.id=p.current_regimen
									LEFT JOIN patient_status ps ON ps.id=p.current_status
									LEFT JOIN regimen_service_type rs ON rs.id=p.service
									WHERE (p.current_regimen=' '
									OR p.current_regimen=''
									OR p.current_regimen is null
									OR p.current_regimen='null')
									AND p.active='1'
                                    AND rs.name NOT LIKE '%pmtct%'
									AND ps.Name NOT LIKE '%transit%'
									AND ps.Name  LIKE '%follow-up%' 
									GROUP BY p.patient_number_ccc;");

		if ($sql -> num_rows() > 0) {
			foreach ($sql->result() as $rows) {
				$patient_CCC = $rows -> patient_number_ccc;
				$sql_get_latest_regimen = "SELECT pv.last_regimen " . " FROM patient_visit pv WHERE pv.patient_id='$patient_CCC' AND pv.last_regimen!='' " . " ORDER BY pv.dispensing_date DESC LIMIT 1";

				$result = $this -> db -> query($sql_get_latest_regimen);
				$res = $result -> result_array();
				$latest_regimen = $res[0]['last_regimen'];

				$sql = "UPDATE patient p " . "SET p.current_regimen='$latest_regimen'" . " WHERE p.patient_number_ccc='" . $patient_CCC . "'";
				$result = $this -> db -> query($sql);
				$this -> session -> set_userdata('msg_save_transaction', 'success');

				echo $this -> db -> affected_rows();
			}

		}
	}

	public function ontime_notification($display_array=false){
		$sql = "SELECT 
					p.id,
					p.patient_number_ccc as ccc_no,
					UPPER(CONCAT_WS(' ',CONCAT_WS(' ',p.first_name,p.other_name),p.last_name)) as patient_name,
					p.phone as contact,
					DATE_FORMAT(p.date_enrolled,'%d-%b-%Y') as enrollment_date,
					DATE_FORMAT(p.nextappointment,'%d-%b-%Y') as next_appointment,
					UPPER(r.regimen_desc) as regimen_name,
					UPPER(ps.Name) as status_name
				FROM patient p 
				LEFT JOIN patient_status ps ON ps.id = p.current_status 
				LEFT JOIN regimen r ON r.id=p.current_regimen
				WHERE DATEDIFF(CURDATE(), p.nextappointment) >= 0 AND DATEDIFF(CURDATE(), p.nextappointment) <= 2 
				AND p.active = '1' 
				AND p.nextappointment >= DATE_SUB(curdate(), INTERVAL 4 WEEK)
				AND ps.Name LIKE '%active%'";
		$results = $this->db->query($sql)->result_array();
		if($display_array==true){
            return $results;
		}else{
			$total=$this -> db -> affected_rows();
			echo "<li><a href='".base_url()."notification_management/load_ontime_view'><i class='icon-th'></i>On Time Appointments <div class='badge badge-important'>" . $total . "</div></a></li>";
		}
	}

	public function defaulter_notification($display_array=false){
		$sql = "SELECT 
					p.id,
					p.patient_number_ccc as ccc_no,
					UPPER(CONCAT_WS(' ',CONCAT_WS(' ',p.first_name,p.other_name),p.last_name)) as patient_name,
					p.phone as contact,
					DATE_FORMAT(p.date_enrolled,'%d-%b-%Y') as enrollment_date,
					DATE_FORMAT(p.nextappointment,'%d-%b-%Y') as next_appointment,
					UPPER(r.regimen_desc) as regimen_name,
					UPPER(ps.Name) as status_name
				FROM patient p 
				LEFT JOIN patient_status ps ON ps.id = p.current_status 
				LEFT JOIN regimen r ON r.id=p.current_regimen
				WHERE DATEDIFF(CURDATE(), p.nextappointment) > 14 AND DATEDIFF(CURDATE(), p.nextappointment) < 90 
				AND p.active = '1' 
				AND ps.Name LIKE '%active%'";
		$results = $this->db->query($sql)->result_array();
		if($display_array==true){
            return $results;
		}else{
			$total=$this -> db -> affected_rows();
			echo "<li><a href='".base_url()."notification_management/load_defaulter_view'><i class='icon-th'></i>Defaulters <div class='badge badge-important'>" . $total . "</div></a></li>";
		}
	}

public function missed_appointments_notification($display_array=false){
		$sql = "SELECT 
					p.id,
					p.patient_number_ccc as ccc_no,
					UPPER(CONCAT_WS(' ',CONCAT_WS(' ',p.first_name,p.other_name),p.last_name)) as patient_name,
					p.phone as contact,
					DATE_FORMAT(p.date_enrolled,'%d-%b-%Y') as enrollment_date,
					DATE_FORMAT(p.nextappointment,'%d-%b-%Y') as next_appointment,
					UPPER(r.regimen_desc) as regimen_name,
					UPPER(ps.Name) as status_name
				FROM patient p 
				LEFT JOIN patient_status ps ON ps.id = p.current_status 
				LEFT JOIN regimen r ON r.id=p.current_regimen
				WHERE DATEDIFF(CURDATE(), p.nextappointment) > 2 AND DATEDIFF(CURDATE(), p.nextappointment) <= 14 
				AND p.active = '1' 
				AND p.nextappointment >= DATE_SUB(curdate(), INTERVAL 2 WEEK)
				AND ps.Name LIKE '%active%'";
		$results = $this->db->query($sql)->result_array();
		if($display_array==true){
            return $results;
		}else{
			$total=$this -> db -> affected_rows();
			echo "<li><a href='".base_url()."notification_management/load_missed_appointments_view'><i class='icon-th'></i>Missed Appointments <div class='badge badge-important'>" . $total . "</div></a></li>";
		}
	}
        
        
        
        public function ontime_appointments_notification($display_array=false){
		$sql = "SELECT 
					p.id,
					p.patient_number_ccc as ccc_no,
					UPPER(CONCAT_WS(' ',CONCAT_WS(' ',p.first_name,p.other_name),p.last_name)) as patient_name,
					p.phone as contact,
					DATE_FORMAT(p.date_enrolled,'%d-%b-%Y') as enrollment_date,
					DATE_FORMAT(p.nextappointment,'%d-%b-%Y') as next_appointment,
					UPPER(r.regimen_desc) as regimen_name,
					UPPER(ps.Name) as status_name
				FROM patient p 
				LEFT JOIN patient_status ps ON ps.id = p.current_status 
				LEFT JOIN regimen r ON r.id=p.current_regimen
				WHERE DATEDIFF(CURDATE(), p.nextappointment) > 14 AND DATEDIFF(CURDATE(), p.nextappointment) < 90 
				AND p.active = '1' 
				AND ps.Name LIKE '%active%'";
		$results = $this->db->query($sql)->result_array();
		if($display_array==true){
            return $results;
		}else{
			$total=$this -> db -> affected_rows();
			echo "<li><a href='".base_url()."notification_management/load_ontime_appointments_view'><i class='icon-th'></i>On-time Appointments <div class='badge badge-important'>" . $total . "</div></a></li>";
		}
	}

	public function followup_notification($display_array=false){
		//get lost to followup patients whose appointment is 90 days from today
            
            $appointment_90=date('Y-m-d',strtotime("-90 days"));
            $from_sunday_90=date('Y-m-d',strtotime("last sunday",strtotime($appointment_90)));
            $to_saturday_90=date('Y-m-d',strtotime("next saturday",strtotime($appointment_90)));
           
		$sql="SELECT p.id,
		      p.patient_number_ccc as ccc_no,
		      UPPER(CONCAT_WS(' ',CONCAT_WS(' ',p.first_name,p.other_name),p.last_name)) as patient_name,
		      p.phone as contact,
		      DATE_FORMAT(p.date_enrolled,'%d-%b-%Y') as enrollment_date,
		      DATE_FORMAT(p.nextappointment,'%d-%b-%Y') as next_appointment,
		      UPPER(r.regimen_desc) as regimen_name,
		      UPPER(ps.Name) as status_name
		      FROM patient p
		      LEFT JOIN patient_status ps ON ps.id=p.current_status
		      LEFT JOIN regimen r ON r.id=p.current_regimen
		      WHERE  p.active='1'
		      and p.nextappointment  = '".$appointment_90."'";
		$query=$this->db->query($sql);
		$results=$query->result_array();

		if($display_array==true){
            return $results;
		}else{
			$total=$this -> db -> affected_rows();
			echo "<li><a href='".base_url()."notification_management/load_followup_view'><i class='icon-th'></i>Lost to Followup (Last Day Alert)<div class='badge badge-important'>" . $total . "</div></a></li>";
		}
	}

	public function prescriptions_notification_view($display_array=false){
		//get lost to followup patients whose appointment is 90 days from today
           
           
		$sql="SELECT a.id, UPPER(CONCAT_WS(' ',CONCAT_WS(' ',a.first_name,a.other_name),a.last_name)) as patient_name,b.id as drug_prescriptionid,
		b.order_number,b.order_physician,b.notes,b.timecreated
		 from patient a
		 INNER JOIN
		(SELECT dp.* FROM drug_prescription dp ,drug_prescription_details dpd 
				left join drug_prescription_details_visit  dpdv on dpdv.drug_prescription_details_id  = dpd.id
				WHERE  dp.id = dpd.drug_prescriptionid
and dp.id not in (SELECT dp.id
FROM drug_prescription_details_visit dpdv, drug_prescription_details dpd ,drug_prescription dp
where 
dpd.id = dpdv.drug_prescription_details_id
and dpd.drug_prescriptionid = dp.id
and dpdv.visit_id > 0
group by id) GROUP BY patient, order_number ORDER BY timecreated DESC ) b on a.patient_number_ccc = b.patient
		group by order_number order by timecreated desc
		";
		
		$query=$this->db->query($sql);
		$results=$query->result_array();

		if($display_array==true){
            return $results;
		}else{
			$total=$this -> db -> affected_rows();
			echo "<li><a href='".base_url()."notification_management/load_prescriptions_view'><i class='icon-th'></i>Pending Prescriptions<div class='badge badge-important'>" . $total . "</div></a></li>";
		}
	}


	public function load_ontime_view(){
		$patients=$this->ontime_notification(true);
		//columns for dataTables
		$columns=array("#","CCC NO","Patient Name","Contact","Date Enrolled","Next Appointment","Current Regimen","Status","Action");
		//if patients is null create empty array
        if(!$patients){
        	$patients=array();
        }
        //use table library to generate table
		$this -> load -> library('table');
		$tmpl = array('table_open' => '<table class="table table-bordered table-hover table-condensed table-striped defaulter_table" >');
		$this -> table -> set_template($tmpl);
		$this -> table -> set_heading($columns);

		//loop  through patients adding the rows
        foreach($patients as $patient){
        	$detail_link="<a href='".base_url()."patient_management/viewDetails/".$patient['id']."'>Detail</a>";
        	$edit_link="<a href='".base_url()."patient_management/edit/".$patient['id']."'>Edit</a>";
        	$disable_link="<a href='".base_url()."patient_management/disable/".$patient['id']."' class='red'>Disable</a>";
            $patient['links']=$detail_link." |  ".$edit_link." | ".$disable_link;
        	unset($patient['id']);
        	$this -> table -> add_row($patient);
        }
		$data['followup_patients']=$this -> table -> generate();
		$data['report_title'] = 'ontime_notification';
		$data['content_view'] = "followup_listing_v";
		$this -> base_params($data);
	}

	public function load_defaulter_view(){
		$patients=$this->defaulter_notification(true);
		//columns for dataTables
		$columns=array("#","CCC NO","Patient Name","Contact","Date Enrolled","Next Appointment","Current Regimen","Status","Action");
		//if patients is null create empty array
        if(!$patients){
        	$patients=array();
        }
        //use table library to generate table
		$this -> load -> library('table');
		$tmpl = array('table_open' => '<table class="table table-bordered table-hover table-condensed table-striped defaulter_table" >');
		$this -> table -> set_template($tmpl);
		$this -> table -> set_heading($columns);

		//loop  through patients adding the rows
        foreach($patients as $patient){
        	$detail_link="<a href='".base_url()."patient_management/viewDetails/".$patient['id']."'>Detail</a>";
        	$edit_link="<a href='".base_url()."patient_management/edit/".$patient['id']."'>Edit</a>";
        	$disable_link="<a href='".base_url()."patient_management/disable/".$patient['id']."' class='red'>Disable</a>";
            $patient['links']=$detail_link." |  ".$edit_link." | ".$disable_link;
        	unset($patient['id']);
        	$this -> table -> add_row($patient);
        }
		$data['followup_patients']=$this -> table -> generate();
		$data['report_title'] = 'defaulter_notification';
		$data['content_view'] = "followup_listing_v";
		$this -> base_params($data);
	}
	public function load_missed_appointments_view(){
		$patients=$this->missed_appointments_notification(true);
		//columns for dataTables
		$columns=array("#","CCC NO","Patient Name","Contact","Date Enrolled","Next Appointment","Current Regimen","Status","Action");
		//if patients is null create empty array
        if(!$patients){
        	$patients=array();
        }
        //use table library to generate table
		$this -> load -> library('table');
		$tmpl = array('table_open' => '<table class="table table-bordered table-hover table-condensed table-striped defaulter_table" >');
		$this -> table -> set_template($tmpl);
		$this -> table -> set_heading($columns);

		//loop  through patients adding the rows
        foreach($patients as $patient){
        	$detail_link="<a href='".base_url()."patient_management/viewDetails/".$patient['id']."'>Detail</a>";
        	$edit_link="<a href='".base_url()."patient_management/edit/".$patient['id']."'>Edit</a>";
        	$disable_link="<a href='".base_url()."patient_management/disable/".$patient['id']."' class='red'>Disable</a>";
            $patient['links']=$detail_link." |  ".$edit_link." | ".$disable_link;
        	unset($patient['id']);
        	$this -> table -> add_row($patient);
        }
		$data['followup_patients']=$this -> table -> generate();
		$data['report_title'] = 'defaulter_notification';
		$data['content_view'] = "followup_listing_v";
		$this -> base_params($data);
	}

	public function load_followup_view(){
		$patients=$this->followup_notification(true);
//columns for dataTables
		$columns=array("#","CCC NO","Patient Name","Contact","Date Enrolled","Next Appointment","Current Regimen","Status","Action");
		//if patients is null create empty array
        if(!$patients){
        	$patients=array();
        }
        //use table library to generate table
		$this -> load -> library('table');
		$tmpl = array('table_open' => '<table class="table table-bordered table-hover table-condensed table-striped defaulter_table" >');
		$this -> table -> set_template($tmpl);
		$this -> table -> set_heading($columns);

		//loop  through patients adding the rows
        foreach($patients as $patient){
        	$detail_link="<a href='".base_url()."patient_management/viewDetails/".$patient['id']."'>Detail</a>";
        	$edit_link="<a href='".base_url()."patient_management/edit/".$patient['id']."'>Edit</a>";
        	$disable_link="<a href='".base_url()."patient_management/disable/".$patient['id']."' class='red'>Disable</a>";
            $patient['links']=$detail_link." |  ".$edit_link." | ".$disable_link;
        	unset($patient['id']);
        	$this -> table -> add_row($patient);
        }
		$data['followup_patients']=$this -> table -> generate();
		$data['report_title'] = 'defaulter_notification';
		$data['content_view'] = "followup_listing_v";
		$this -> base_params($data);
	}

	public function load_prescriptions_view(){
		$patients=$this->prescriptions_notification_view(true);
		//columns for dataTables
		$columns=array("#","patient","order_number",	"order_physician","notes","timecreated" ,'action');
		//if patients is null create empty array
        if(!$patients){
        	$patients=array();
        }
        //use table library to generate table
		$this -> load -> library('table');
		$tmpl = array('table_open' => '<table class="table table-bordered table-hover table-condensed table-striped dataTables" >');
		$this -> table -> set_template($tmpl);
		$this -> table -> set_heading($columns);
		//loop  through patients adding the rows
        foreach($patients as $patient){
        	// http://localhost:81/ADT/dispensement_management/dispense/2821?pid=1255
        	$dispense_link="<a href='".base_url()."dispensement_management/dispense/".$patient['id']."?pid=".$patient['drug_prescriptionid']."'>Dispense</a>";
        	$edit_link="<a href='".base_url()."patient_management/edit/".$patient['id']."'>Edit</a>";
        	// $disable_link="<a href='".base_url()."patient_management/disable/".$patient['id']."' class='red'>Disable</a>";
            $patient['links']= $dispense_link;
        	unset($patient['id']);
        	unset($patient['drug_prescriptionid']);
        	$this -> table -> add_row($patient);
        }
		$data['followup_patients']=$this -> table -> generate();
		$data['content_view'] = "followup_listing_v";
		$this -> base_params($data);
	}


	public function base_params($data) {
		$data['title'] = "webADT | Notifications";
		$data['banner_text'] = "System Notifications";
		$data['link'] = "notifcations";
		$this -> load -> view('template', $data);
	}

}