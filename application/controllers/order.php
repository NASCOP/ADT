<?php
ob_start();
class Order extends MY_Controller {
	var $facility_code = '';
	var $facility_type = '';
	var $user_id = '';
	var $facility_dhis = '';
	var $dhis_url = '';
	function __construct() {
		parent::__construct();

		$this -> load -> library('Curl');
		$this->facility_code = $this -> session -> userdata('facility');
		$this->facility_type = Facilities::getType($this->facility_code);
		$this->user_id = $this -> session -> userdata('user_id');
		$this->facility_dhis = $this->session->userdata('facility_dhis');
		$this->dhis_url = 'https://test.hiskenya.org/kenya/';
		$this->dhis_url = 'https://hiskenya.org/';
	}

	private function setFacilityType($type){
		$this->facility_type = $type;
	}
	private function getFacilityType(){
		return $this->facility_type;
	}

	public function index() {
		if ($this->session->userdata("facility_dhis")==1 && !$this->session->userdata("dhis_id")){
			$data['page_title'] = "DHiS Login";
			$data['banner_text'] = "DHIS Login";
			$data['content_view'] = "orders/dhis_login_v";
		}else{
			$data['dhis_data'] =$this->check_dhis_data_exists();
			$data['cdrr_buttons'] = $this -> get_buttons("cdrr");
			$data['cdrr_filter'] = $this -> get_filter("cdrr");
			$data['fmap_buttons'] = $this -> get_buttons("maps");
			$data['maps_filter'] = $this -> get_filter("maps");
			$data['cdrr_table'] = $this -> get_orders("cdrr");
			$data['map_table'] = $this -> get_orders("maps");
			$data['facilities'] = Facilities::getSatellites($this->facility_code);
			$data['page_title'] = "my Orders";
			$data['banner_text'] = "Facility Orders";
			$data['content_view'] = "orders/order_v";
		}
		$this -> base_params($data);
	}

	public function authenticate_user() {
		$curl = new Curl();
		$username = $this -> input -> post("username");
		$password = $this -> input -> post("password");
		$curl -> setBasicAuthentication($username, $password);
		$curl -> setOpt(CURLOPT_RETURNTRANSFER, TRUE);
		$curl -> setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);
		$auth_url = $this -> dhis_url . 'api/me';
		$curl -> get($auth_url);

		//Check for error(s)
		if ($curl -> error) {
			if ($curl -> error_code == 6 || $curl -> error_code == 7) {//Internet Connection error
				$this -> session -> set_flashdata('login_message', "<span class='error'>Problem while connecting to the Server! ".$curl -> error_message."</span>");
			} else {
				$this -> session -> set_flashdata('login_message', "<span class='error'>Error " . $curl -> error_code . ": Login Failed! Incorrect credentials</span>");
			}
		}else{
			$auth_response = json_decode($curl -> response, TRUE);

			//Get user organization_units 
			$kenya_code = 'HfVjCurKxh2';
			$dhis_orgs = array();
			foreach ($auth_response['organisationUnits'] as $orgs) {
				$dhis_orgs[] = $orgs['id'];
			}

			//Get dhiscodes for all related sites without
			$missing_dhiscode = array();
			$query = $this->db->get_where('sync_facility', array(
				'parent_id' => Sync_Facility::getId($this->facility_code, $this->facility_type)['id'])
			);
			foreach ($query->result_array() as $facility) {
				if(!$facility['dhiscode']){
					$missing_dhiscode[] = $facility['code'];
				}
			};

			if(!empty($missing_dhiscode)){
				$org_unit_url = $this -> dhis_url . 'api/organisationUnits.json?level=5&paging=false&fields=:id,code,name&filter=code:in:['.implode(',', $missing_dhiscode).']';
				$curl -> get($org_unit_url);
				if (!$curl -> error) {
					$org_response = json_decode($curl -> response, TRUE);
					foreach ($org_response['organisationUnits'] as $org) {
						if(isset($org['code'])){
							$this->db->where('code', $org['code']);
							$this->db->update('sync_facility', array('dhiscode' => $org['id']));
						}
					}
				}
			}
			$county_url = $this -> dhis_url ."api/organisationUnits.json?level=2&paging=false&fields=:id,code,name&filter=name:ilike:".$this->session->userdata('facility_county');
			$subcounty_access = false;
			$curl -> get($county_url);
			if (!$curl -> error) {
				$county_response = json_decode($curl -> response, TRUE);
				$subcounty_access = true;
			}
			
			$subcounty_url = $this -> dhis_url ."api/organisationUnits.json?level=3&paging=false&fields=:id,code,name&filter=name:ilike:".$this->session->userdata('facility_subcounty');
			$curl -> get($subcounty_url);
			if (!$curl -> error) {
				$subcounty_response = json_decode($curl -> response, TRUE);
				$subcounty_access = true;
			}

			$parent_id = $this->get_dhis_orgs($username.':'.$password);
			//Ensure user has access to facility dhis data
			$user_dhis_orgs = array();
			$query = $this->db->get_where('sync_facility', array(
				'parent_id' =>  $parent_id
			));
			foreach ($query->result_array() as $facility) {
				$user_dhis_orgs[] = $facility['dhiscode'];
			};
			$user_dhis_orgs = array_unique($user_dhis_orgs);
			$dhis_orgs_intersect = array_intersect($user_dhis_orgs, $dhis_orgs);

			if(!empty($dhis_orgs_intersect) || in_array($kenya_code, $dhis_orgs) || $subcounty_access){
				//Save user data
				$sync_user = array(
					'username' => $username,
					'password' => md5($password),
					'email' => (isset($auth_response['email'])) ? $auth_response['email'] : '',
					'name' => $auth_response['name'],
					'role' => (isset($auth_response['employer'])) ? $auth_response['employer'] : '',
					'status' => 'A',
					'user_id' => $this->session->userdata('user_id'),
					'profile_id' => $auth_response['id'],
					'organization_id' => json_encode($dhis_orgs_intersect)
				);

				//Link User to facilities
				$conditions = array('user_id' => $this->session->userdata('user_id'),'profile_id' => $auth_response['id']);
				$users = $this->db->get_where('sync_user', $conditions)->num_rows();
				if($users > 0){
					$this->db->update('sync_user', $sync_user, $conditions);
				}else{
					$this->db->insert('sync_user', $sync_user);
				}
				//Set session
				$this->session->set_userdata("dhis_org", $this->facility_dhis());
				$this->session->set_userdata("dhis_id", $auth_response['id']);
				$this->session->set_userdata("dhis_name",  $auth_response['name']);
				$this->session->set_userdata("dhis_user", $username);
				$this->session->set_userdata("dhis_pass", $password);
				$this->session->set_userdata("dhis_orgs", $dhis_orgs_intersect);
			}else{
				$this -> session -> set_flashdata('login_message', "<span class='error'>You are not authorized in this Facility!</span>");
			}
		}
		redirect("order");
	}

	public function logout() {
		$this -> session -> unset_userdata("dhis_id");
		$this -> session -> unset_userdata("dhis_name");
		$this -> session -> unset_userdata("dhis_user");
		$this -> session -> unset_userdata("dhis_pass");
		$this -> session -> unset_userdata("dhis_orgs");
		redirect("order");
	}
	function facility_dhis(){
		$code = $this->session->userdata('facility');
		$sql = "select dhiscode from sync_facility where code = $code ";
		$dhiscode = ($this->db->query($sql)->result_array()) ? $this->db->query($sql)->result_array()[0]['dhiscode'] : null ;
		return $dhiscode;
	}
	function check_dhis_data_exists(){
		$last_month_date = date('Y-m',strtotime("-1 month")).'-01';
		$query_str = "select id from cdrr where period_begin = '$last_month_date'
						UNION
					select id from maps where period_begin = '$last_month_date'";
					$returnable = false;
		$query = $this->db->query($query_str);
		if ($query->result_array())
			{$returnable = true;}
		return $returnable;

	}

	public function get_dhis_data($period_filter){
		$message = '';
		if ($this->facility_type == 0) { //Satellite Site
			$message .= $this->get_dhis('fcdrr', $period_filter, 'F-CDRR_units')['fcdrr']['message'];
			$message .= $this->get_dhis('fmaps', $period_filter, 'F-MAPS')['fmaps']['message'];
		}else if ($this->facility_type == 1) { //Standalone Site
			$message .= $this->get_dhis('fcdrr', $period_filter, 'F-CDRR_packs')['fcdrr']['message'];
			$message .= $this->get_dhis('fmaps', $period_filter, 'F-MAPS')['fmaps']['message'];
		}else if ($this->facility_type > 1) { //Central Site
			$message .= $this->get_dhis('fcdrr', $period_filter, 'F-CDRR_units')['fcdrr']['message'];
			$message .= $this->get_dhis('fmaps', $period_filter, 'F-MAPS')['fmaps']['message'];
			$message .= $this->get_dhis_central('dcdrr', $period_filter, 'D-CDRR')['dcdrr']['message'];
			$message .= $this->get_dhis_central('dmaps', $period_filter, 'D-MAPS')['dmaps']['message'];
		}
		echo json_encode($message);
	}
	public function get_dhis_orgs($dhis_auth){
		$parent_id = 0;
		$query_str = "SELECT sf.id
			FROM facilities f , sync_facility sf
			where  sf.code = f.facilitycode
			and f.facilitycode = $this->facility_code
			and sf.category in ('central')";

					$query = $this -> db -> query($query_str);
					$result = $query -> result_array();
					if ($result) {
						$parent_id = $result[0]['id'];
					}

				$resource = "api/me";
				$response = json_decode($this->sendRequest($resource, 'GET', null, $dhis_auth));
				$str  = '';
				
				foreach ($response->organisationUnits as $rs) {
					$str .=$rs->id.',';
				}
				$resource = "api/organisationUnits.json?level=5&paging=false&fields=:all&filter=id:in:[$str]";

				$response = json_decode($this->sendRequest($resource, 'GET', null, $dhis_auth));
				foreach ($response->organisationUnits as $key => $rs ) {
				$str = "insert into sync_facility (name,category ,parent_id,code,ordering,service_point,dhiscode,Active) Values('$rs->name','satellite',$parent_id,'$rs->code',0,1,'$rs->id',1) ON DUPLICATE KEY UPDATE name = '$rs->name', parent_id = $parent_id, ordering = 0, service_point = 1, dhiscode = '$rs->id', Active = 1 ;";
				$this->db->query($str);
				$this->setFacilityType($key);
				}

				$upd_user = ($parent_id > 0) ? $this->update_user_facilities($parent_id): false;
				return $parent_id;
	}
	
	function update_user_facilities($parent_id){
		$query_str = "SELECT id FROM sync_facility WHERE parent_id = $parent_id AND category = 'satellite'";
		$query = $this -> db -> query($query_str);
		$result = $query -> result_array();
		$str = '';
		if ($result) {
			foreach ($result as $key => $id) {
				$str .= '"'.$id['id'].'",';
			}
			$str.='"'.$parent_id.'",';

			 $str = "[".substr($str,0, -1)."]";
			 $user_id = $this->session->userdata('user_id');
			 $sql =  "update user_facilities set facility = '$str' where user_id = $user_id;";
			 $this->db->query($sql);
			}

	}

	public function verify_user_access(){
		$has_access = FALSE;
		$sync_facility = Sync_Facility::getId($this->facility_code, $this->facility_type);
		$user_facilities = User_Facilities::getHydratedFacilityList($this -> user_id);
		if(!empty($user_facilities)){
			$facility_ids = json_decode($user_facilities['facility'], TRUE);
			if(in_array($sync_facility['id'], $facility_ids)){
				$has_access = TRUE;
			}
		}
		return $has_access;
	}

	public function get_filter($type = "cdrr") {
		$filter = "";
		if($this->verify_user_access()){
			$filter .= "<span><b>Filter Period:</b></span><select class='" . $type . "_filter'>";
			$filter .= "<option value='0'>All</option>";
			if ($type == "cdrr") {
				$periods = Cdrr::getPeriods();
				foreach ($periods as $period) {
					$filter .= "<option value='" . $period['periods'] . "'>" . date('F-Y', strtotime($period['periods'])) . "</option>";
				}
			} else if ($type == "maps") {
				$periods = Maps::getPeriods();
				foreach ($periods as $period) {
					$filter .= "<option value='" . $period['periods'] . "'>" . date('F-Y', strtotime($period['periods'])) . "</option>";
				}
			}
			$filter .= "</select>";
		}
		return $filter;
	}

	public function get_buttons($type = "cdrr") {
		$buttons = "";
		$set_type = "order/create_order/" . $type;
		$satellite_type = 'btn_new_' . $type . '_satellite';
		// if($this->verify_user_access()){
			if ($this->facility_type == 0) {
				$buttons .= "<a href='" . base_url() . $set_type . "/0' class='btn check_net'>New Satellite $type</a>";
			} else if ($this->facility_type == 1) {
				$buttons .= "<a href='" . base_url() . $set_type . "/1' class='btn'>New Stand-Alone $type</a>";
			} else if ($this->facility_type > 1) {
				// if(!$this->session->userdata("dhis_id")){}
				$buttons .= "<a href='" . base_url() . $set_type . "/3' class='btn'>New Aggregate $type</a>";
				$buttons .= "<a href='" . base_url() . $set_type . "/2' class='btn'>New Central $type</a>";
				$buttons .= "<a data-toggle='modal' href='#select_satellite' class='btn check_net btn_satellite' id='$satellite_type'>New Satellite $type</a>";
			}
		// }
		return $buttons;
	}

	public function get_orders($type = "cdrr", $period_begin = "") {
		$columns = array('#', '#ID', 'Period Beginning', 'Status-Online', 'Facility Name', 'Options');
		$facility_table = 'sync_facility';
		$facility_name = 'f.name';
		$conditions = '';
		$facilities = '';
		$results = array();
		$user_facilities = User_Facilities::getHydratedFacilityList($this -> session -> userdata("user_id"));
		if(!empty($user_facilities)){
			$facilities = implode(',',json_decode($user_facilities['facility'], TRUE));
		}
		
		if ($period_begin != "" && $type == "cdrr") {
			$conditions = "AND c.period_begin='$period_begin'";
		}
		if ($period_begin != "" && $type == "maps") {
			$conditions = "AND m.period_begin='$period_begin'";
		}
		if ($period_begin == 0 && $type == "cdrr") {
			$conditions = "";
		}
		if ($period_begin == 0 && $type == "maps") {
			$conditions = "";
		}

		if($facilities){
			if ($type == "cdrr") {
				$sql = "SELECT c.id,IF(c.code='D-CDRR',CONCAT('D-CDRR#',c.id),CONCAT('F-CDRR#',c.id)) as cdrr_id,c.period_begin, concat (LCASE(c.status),'-',c.issynched) as status_name,$facility_name as facility_name
				FROM cdrr c
				LEFT JOIN $facility_table f ON f.id=c.facility_id
				WHERE facility_id IN($facilities)
				AND c.status NOT LIKE '%deleted%'
				$conditions
				ORDER BY c.period_begin desc";
			} else if ($type == "maps") {
				$sql = "SELECT m.id,IF(m.code='D-MAPS',CONCAT('D-MAPS#',m.id),CONCAT('F-MAPS#',m.id)) as maps_id,m.period_begin,
				concat (LCASE(m.status),'-',m.issynched) as status_name,$facility_name as facility_name
				FROM maps m
				LEFT JOIN $facility_table f ON f.id=m.facility_id
				WHERE facility_id IN($facilities)
				AND m.status NOT LIKE '%deleted%'
				$conditions
				ORDER BY m.period_begin desc";
			} 
			$query = $this -> db -> query($sql);
			$results = $query -> result_array();
		}

		if ($period_begin != "") {
			echo $this -> generate_table($columns, $results, $type);
		} else {
			if ($period_begin != 0) {
				echo $this -> generate_table($columns, $results, $type);
			} else {
				return $this -> generate_table($columns, $results, $type);
			}
		}
	}

	public function generate_table($columns, $data = array(), $table = "cdrr") {
		$this -> load -> library('table');
		$tmpl = array('table_open' => '<table class="table table-bordered table-hover table-condensed" id="order_listing_' . $table . '">');
		$this -> table -> set_template($tmpl);
		$this -> table -> set_heading($columns);
		$link_values = "";

		foreach ($data as $mydata) {
			$status_name = explode('-', strtolower(@$mydata['status_name']))[0];
			$issynched = explode('-', strtolower(@$mydata['status_name']))[1];

			if ($status_name == "prepared" || $status_name == "review") {
				$links = array("order/view_order/" . $table => "view", "order/update_order/" . $table => "update", "order/read_order/" . $table => "delete", "order/download_order/" . $table => "download");
			} else {
				$links = array("order/view_order/" . $table => "view", "order/download_order/" . $table => "download");
				if ($table == "aggregate") {
					$links = array("order/aggregate_download" => "download");
				}
			}

			//Set Up links
			foreach ($links as $i => $link) {
				if ($link == "delete") {
					$link_values .= "<a href='" . site_url($i . '/' . $mydata['id']) . "' class='delete_order'>$link</a> | ";
				} else {
					if ($table == "aggregate") {
						$link_values .= "<a href='" . site_url($i . '/' . $mydata['id'].'/'.$mydata['facility_id'].'/'.$mydata['cdrr_id'].'/'.$mydata['maps_id'].'/'.$mydata['facility_code']) . "'>$link</a> | ";
						unset($mydata['facility_code']);
						unset($mydata['facility_id']);
						unset($mydata['cdrr_id']);
						unset($mydata['maps_id']);
					}else{
						$link_values .= "<a href='" . site_url($i . '/' . $mydata['id']) . "'>$link</a> | ";
					}
				}
			}
			$mydata['Options'] = rtrim($link_values, " | ");
			$link_values = "";
			unset($mydata['id']);
			$this -> table -> add_row($mydata);
		}
		return $this -> table -> generate();
	}

	public function create_order($type = "cdrr", $order_type, $content_array = array()) {
		$data['hide_generate'] = 0;
		$data['hide_save'] = 0;
		$data['hide_btn'] = 0;
		$data['stand_alone'] = 0;
		if ($type == "cdrr") {
			$this -> session -> set_userdata("order_go_back", "cdrr");
			$data['hide_side_menu'] = 0;
			$data['options'] = "none";

			if ($order_type == 0){ //satellite
				$data['page_title'] = "Satellite Facility(F-CDRR)";
				$data['banner_text'] = "Satellite Facility(F-CDRR)";
				$facility = $this -> input -> post("satellite_facility", TRUE);
				if ($facility == null) {
					$facility = $this -> session -> userdata("facility");

				} else {
					$data['hide_generate'] = 1;
				}
			}
			else if ($order_type == 1){ //standalone
				$data['page_title'] = "Stand-alone(F-CDRR)";
				$data['banner_text'] = "Stand-alone(F-CDRR)";
				$facility = $this -> session -> userdata("facility");
				$data['stand_alone'] = 1;
			}  
			else if ($order_type == 2){ //dispensing_point
				$data['page_title'] = "Central Dispensing Point(F-CDRR)";
				$data['banner_text'] = "Central Dispensing Point(F-CDRR)";
				$facility = $this -> session -> userdata("facility");
				$order_type = 0;
			} 
			else{ //aggregate
				$data['page_title'] = "Central Aggregate(D-CDRR)";
				$data['banner_text'] = "Central Aggregate(D-CDRR)";
				$data['hide_generate'] = 2;
				$facility = $this -> session -> userdata("facility");
			}

			if (!empty($content_array)) {
				$cdrr_array = $content_array;
				$data['cdrr_array'] = $cdrr_array['cdrr_array'];
				$data['status_name'] = strtolower($cdrr_array['cdrr_array'][0]['status_name']);
				$facility_id = $cdrr_array['cdrr_array'][0]['facility_id'];
				$data['facility_id'] = $facility_id;
				$facilities = Sync_Facility::getCode($facility_id, $order_type);
				$facility = $facilities['code'];
				$code = $cdrr_array['cdrr_array'][0]['code'];
				$code = $this -> getDummyCode($code, $order_type);
				$data['options'] = $cdrr_array['options'];
				if ($data['options'] == "view") {
					$data['hide_save'] = 1;
				}
				$data['hide_btn'] = 1;
				$cdrr_id = $cdrr_array['cdrr_array'][0]['cdrr_id'];
				$data['cdrr_id'] = $cdrr_id;
				$data['logs'] = Cdrr_Log::getLogs($cdrr_id);
				if ($data['options'] == "view" || $data['options'] == "update") {
					if ($data['status_name'] == "prepared" || $data['status_name'] == "review") {
						$data['option_links'] = "<li class='active'><a href='" . site_url("order/view_order/cdrr/" . $cdrr_id) . "'>view</a></li><li><a href='" . site_url("order/update_order/cdrr/" . $cdrr_id) . "'>update</a></li><li><a class='delete' href='" . site_url("order/delete_order/cdrr/" . $cdrr_id) . "'>delete</a></li>";
					} else {
						$data['option_links'] = "<li class='active'><a href='" . site_url("order/view_order/cdrr/" . $cdrr_id) . "'>view</a></li>";
					}
				}

				if ($code == 0) {
					$and = "";
				} else {
					$and = "AND ci.resupply !='0'";
				}
				if ($cdrr_array['options'] == "update") {
					$data['commodities'] = Sync_Drug::getActiveList();
				} else {
					$sql = "SELECT sd.id,CONCAT_WS('] ',CONCAT_WS(' [',name,abbreviation),CONCAT_WS(' ',strength,formulation)) as Drug,unit as Unit_Name,packsize as Pack_Size,category_id as Category
					FROM cdrr_item ci
					LEFT JOIN sync_drug sd ON sd.id=ci.drug_id
					WHERE ci.cdrr_id='$cdrr_id'
					AND(sd.category_id='1' OR sd.category_id='2' OR sd.category_id='3' OR sd.category_id='4')
					AND Active = '1'";
					$query = $this -> db -> query($sql);
					$data['commodities'] = $query -> result();
				}
			} else {
				$period_start = date('Y-m-01', strtotime(date('Y-m-d') . "-1 month"));
				$period_end = date('Y-m-t', strtotime(date('Y-m-d') . "-1 month"));
				$code = $this -> getActualCode($order_type, $type);
				$facilities = Sync_Facility::getId($facility, $order_type);
				$duplicate = $this -> check_duplicate($code, $period_start, $period_end, $facilities['id'], $type);
				$data['commodities'] = Sync_Drug::getActiveList();
				$data['duplicate'] = $duplicate;
			}

			$facilities = Sync_Facility::getId($facility, $order_type);
			$data['facility_id'] = $facilities['id'];
			$data['facility_object'] = Facilities::getCodeFacility($facility);
			$data['content_view'] = "orders/cdrr_template";
			$data['report_type'] = $order_type;
			$data['stores']=CCC_store_service_point::getStoreGroups();
			$this -> base_params($data);

		} else if ($type == "maps") {
			$this -> session -> set_userdata("order_go_back", "fmaps");
			$data['o_type'] = "FMAP";
			$data['options'] = "none";
			$data["is_update"] = 0;
			$data["is_view"] = 0;

			if($order_type == 0){ //satellite
				$facility_code = $this -> input -> post("satellite_facility", TRUE);
				$data['page_title'] = "Satellite Facility(F-MAPS)";
				$data['banner_text'] = "Satellite Facility(F-MAPS)";

				if ($facility_code == null) {
					$facility_code = $this -> session -> userdata("facility");
				} else {
					$data['hide_generate'] = 1;
				}
			}
			else if($order_type == 1){ //standalone
				$facility_code = $this -> session -> userdata('facility');
				$facility_id = $this -> session -> userdata('facility_id');
				$data['commodities'] = Sync_Drug::getActiveList();
				$data['page_title'] = "Stand-Alone MAPS";
				$data['banner_text'] = "Maps Form";
			} 
			else if($order_type == 2){ //dispensing_point
				$facility_code = $this -> session -> userdata('facility');
				$facility_id = $this -> session -> userdata('facility_id');
				$data['commodities'] = Sync_Drug::getActiveList();
				$data['page_title'] = "Central Dispensing Point";
				$data['banner_text'] = "Maps Form";
				$order_type = 0;
			}  
			else{ //aggregate
				$facility_code = $this -> session -> userdata('facility');
				$data['page_title'] = "Aggregate Maps List";
				$facility = Facilities::getParent($facility_code);
				$parent_code = $facility['parent'];
				if ($parent_code == $facility_code) {//Check if button was clicked to start new aggregate order
					$data['hide_generate'] = 2;
				}
				$data['banner_text'] = "Aggregate Maps List";
			}

			if (!empty($content_array)) {
				$fmaps_array = $content_array;
				$data['fmaps_array'] = $fmaps_array['fmaps_array'];
				$facility_id = $fmaps_array['fmaps_array'][0]['facility_id'];
				$data['facility_id'] = $facility_id;
				$facilities = Sync_Facility::getCode($facility_id, $order_type);
				$facility_code = $facilities['code'];
				$code = $fmaps_array['fmaps_array'][0]['code'];
				$code = $this -> getDummyCode($code, $order_type);
				//Central or Satellite or Aggregate
				$data['status'] = strtolower($fmaps_array['fmaps_array'][0]['status_name']);
				$data['created'] = $fmaps_array['fmaps_array'][0]['created'];
				// Pending, Approved, ...
				$data['options'] = $fmaps_array['options'];
				$data['hide_btn'] = 1;
				$maps_id = $fmaps_array['fmaps_array'][0]['maps_id'];
				//Complet id with #
				$map_id = $fmaps_array['fmaps_array'][0]['map_id'];
				//Id from DB
				$data['maps_id'] = $maps_id;
				$data['map_id'] = $map_id;
				$data['logs'] = Maps_Log::getMapLogs($map_id);

				if ($data['options'] == "view") {
					$data['hide_save'] = 1;	
					$regimen_table = 'sync_regimen';
					$regimen_cat_table = 'sync_regimen_category';
					$regimen_code = 'r.code';
					$regimen_desc = 'r.name as description';
					$regimen_cat_join = 'r.category_id';
					$regimen_join = 'mi.regimen_id=r.id';

					$sql_regimen = "SELECT rc.id,r.id as reg_id,rc.Name as name,$regimen_code,$regimen_desc,$regimen_cat_join,mi.total
					FROM $regimen_table r
					LEFT JOIN $regimen_cat_table rc ON rc.id = $regimen_cat_join
					LEFT JOIN maps_item mi ON $regimen_join
					WHERE maps_id='$map_id'";

					$query_regimen = $this -> db -> query($sql_regimen);
					$regimen_array = $query_regimen -> result_array();
					$regimen_categories = array();
					foreach ($regimen_array as $value) {
						$regimen_categories[] = $value['name'];
					}
					$regimen_categories = array_unique($regimen_categories);
					$data['regimen_categories'] = $regimen_categories;
					$data['regimen_array'] = $regimen_array;

				}
				if ($data['options'] == "update") {
					$data["is_update"] = 1;
					$data['regimen_categories'] = Sync_Regimen_Category::getAll();
				} else {
					$data["is_view"] = 1;
					$data['regimens'] = Maps_Item::getOrderItems($maps_id);
				}

			} else {
				$data['regimen_categories'] = Sync_Regimen_Category::getAll();
				$period_start = date('Y-m-01', strtotime(date('Y-m-d') . "-1 month"));
				$period_end = date('Y-m-t', strtotime(date('Y-m-d') . "-1 month"));

				$code = $this -> getActualCode($order_type, $type);
				$facilities = Sync_Facility::getId($facility_code, $order_type);
				$duplicate = $this -> check_duplicate($code, $period_start, $period_end, $facilities['id'], $type);
				$data['duplicate'] = $duplicate;
			}
			$facilities = Sync_Facility::getId($facility_code, $order_type);
			$data['facility_id'] = $facilities['id'];
			$data['content_view'] = "orders/fmap_template";
			$data['report_type'] = $order_type;
			$data['facility_object'] = Facilities::getCodeFacility($facility_code);
			$this -> base_params($data);
		}

	}

	public function check_duplicate($code, $period_start, $period_end, $facility, $table = "cdrr") {
		$response = false;
		$sql = "select * from $table where period_begin='$period_start' and period_end='$period_end' and code='$code' and facility_id = '$facility' and status !='deleted'";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if ($results) {
			$response = true;
			$this -> session -> set_flashdata('order_message', strtoupper($table) . ' report already exists for this month !');

		}
		return $response;
	}

	public function save($type = "cdrr", $status = "prepared", $id = "") {
		$main_array = array();
		$updated = "";
		$created = date('Y-m-d H:i:s'); 
		
		if ($id != "") {
			$status = $this -> input -> post("status");
			$created = $this -> input -> post("created");
			$item_id = $this -> input -> post("item_id");
			$log_id = $this -> input -> post("log_id");
			$updated = date('Y-m-d H:i:s');
			if ($this -> input -> post("status_change")) {
				$status = $this -> input -> post("status_change");
			}
		}
		
		if ($type == "cdrr") {
			$save = $this -> input -> post("save");
			if ($save) {
				$facility_id = $this -> input -> post("facility_id");
				$facility_code = $this -> input -> post("facility_code");
				$code = $this -> input -> post("report_type");
				$code = $this -> getActualCode($code, $type);
				$period_begin = $this -> input -> post("period_start");
				$period_end = $this -> input -> post("period_end");
				$comments = $this -> input -> post("comments");
				//trim comments tabs
				$comments = preg_replace('/[ ]{2,}|[\t]/', ' ', trim($comments));
				$services = $this -> input -> post("type_of_service");
				$sponsors = $this -> input -> post("sponsor");
				$none_arv = $this -> input -> post("non_arv");
				$commodities = $this -> input -> post('commodity');

				$pack_size = $this -> input -> post('pack_size');
				$opening_balances = $this -> input -> post('opening_balance');
				$quantities_received = $this -> input -> post('quantity_received');
				$quantities_dispensed = $this -> input -> post('quantity_dispensed');
				if ($code == "F-CDRR_packs") {
					$quantities_dispensed_packs = $this -> input -> post('quantity_dispensed_packs');
				}
				$losses = $this -> input -> post('losses');
				$adjustments = $this -> input -> post('adjustments');
				$adjustments_neg = $this -> input -> post('adjustments_neg');
				$physical_count = $this -> input -> post('physical_count');
				$expiry_quantity = $this -> input -> post('expire_qty');
				$expiry_date = $this -> input -> post('expire_period');
				$out_of_stock = $this -> input -> post('out_of_stock');
				$resupply = $this -> input -> post('resupply');
				if ($code == "D-CDRR") {
					$aggr_consumed = $this -> input -> post('aggregated_qty');
					$aggr_on_hand = $this -> input -> post('aggregated_physical_qty');
				}
				//insert cdrr
				$main_array['id'] = $id;
				$main_array['status'] = strtolower($status);
				$main_array['created'] = $created;
				$main_array['updated'] = $updated;
				$main_array['code'] = $code;
				$main_array['period_begin'] = $period_begin;
				$main_array['period_end'] = $period_end;
				$main_array['comments'] = $comments;
				$main_array['reports_expected'] = null;
				$main_array['reports_actual'] = null;
				if ($code == "D-CDRR") {//Aggregated
					$reports_expected = $this -> input -> post('central_rate');
					$reports_actual = $this -> input -> post('actual_report');
					$main_array['reports_expected'] = $reports_expected;
					$main_array['reports_actual'] = $reports_actual;
				}
				$main_array['services'] = $services;
				$main_array['sponsors'] = $sponsors;
				$main_array['non_arv'] = $none_arv;
				$main_array['delivery_note'] = null;
				$main_array['order_id'] = 0;
				$main_array['facility_id'] = $facility_id;

				//insert cdrr_items
				$commodity_counter = 0;
				$cdrr_array = array();

				foreach ($commodities as $commodity) {
					if (trim($resupply[$commodity_counter]) != '') {
						if ($id == "") {
							$cdrr_array[$commodity_counter]['id'] = "";
						} else {
							$cdrr_array[$commodity_counter]['id'] = $item_id[$commodity_counter];
						}
						$cdrr_array[$commodity_counter]['balance'] = $opening_balances[$commodity_counter];
						$cdrr_array[$commodity_counter]['received'] = $quantities_received[$commodity_counter];
						if ($code == "F-CDRR_units") {
							$cdrr_array[$commodity_counter]['dispensed_units'] = $quantities_dispensed[$commodity_counter];
							$cdrr_array[$commodity_counter]['dispensed_packs'] = $quantities_dispensed[$commodity_counter];
						}
						else if ($code == "F-CDRR_packs") {
							$cdrr_array[$commodity_counter]['dispensed_units'] = (@$quantities_dispensed[$commodity_counter]);
							$cdrr_array[$commodity_counter]['dispensed_packs'] = (@$quantities_dispensed[$commodity_counter]);
						} 
						else if ($code == "D-CDRR") {
							$cdrr_array[$commodity_counter]['dispensed_units'] = (@$quantities_dispensed[$commodity_counter] * @$pack_size[$commodity_counter]);
							$cdrr_array[$commodity_counter]['dispensed_packs'] = $quantities_dispensed[$commodity_counter];
						}
						$cdrr_array[$commodity_counter]['losses'] = $losses[$commodity_counter];
						$cdrr_array[$commodity_counter]['adjustments'] = $adjustments[$commodity_counter];
						$cdrr_array[$commodity_counter]['adjustments_neg'] = $adjustments_neg[$commodity_counter];
						$cdrr_array[$commodity_counter]['count'] = $physical_count[$commodity_counter];
						$cdrr_array[$commodity_counter]['expiry_quant'] = $expiry_quantity[$commodity_counter];
						if ($expiry_date[$commodity_counter] != "-" && $expiry_date[$commodity_counter] != "" && $expiry_date[$commodity_counter] !=null && $expiry_date[$commodity_counter] != "NULL" && $expiry_date[$commodity_counter] != "1970-01-01" && $expiry_date[$commodity_counter] != "0000-00-00") {
							$cdrr_array[$commodity_counter]['expiry_date'] = date('Y-m-d', strtotime($expiry_date[$commodity_counter]));
						} else {
							$cdrr_array[$commodity_counter]['expiry_date'] = null;
						}
						$cdrr_array[$commodity_counter]['out_of_stock'] = $out_of_stock[$commodity_counter];
						$cdrr_array[$commodity_counter]['resupply'] = $resupply[$commodity_counter];
						$cdrr_array[$commodity_counter]['aggr_consumed'] = null;
						$cdrr_array[$commodity_counter]['aggr_on_hand'] = null;
						$cdrr_array[$commodity_counter]['publish'] = 0;
						if ($code == "D-CDRR") {
							$cdrr_array[$commodity_counter]['aggr_consumed'] = $aggr_consumed[$commodity_counter];
							$cdrr_array[$commodity_counter]['aggr_on_hand'] = $aggr_on_hand[$commodity_counter];
						}
						$cdrr_array[$commodity_counter]['cdrr_id'] = $id;
						$cdrr_array[$commodity_counter]['drug_id'] = $commodity;
					}
					$commodity_counter++;
				}
				$main_array['ownCdrr_item'] = $cdrr_array;
				//Insert Logs
				$log_array = array();
				if ($id != "") {
					$status = "updated";
					if ($this -> input -> post("status_change")) {
						$status = $this -> input -> post("status_change");
						if($status =='approved'){$this->upload_dhis('cdrr',$id);}
					}
					$logs = Cdrr_Log::getHydratedLogs($id);

					$log_array['id'] = "";
					$log_array['description'] = $status;
					$log_array['created'] = date('Y-m-d H:i:s');
					$log_array['user_id'] = $this -> session -> userdata("user_id");
					$log_array['cdrr_id'] = $id;

					$logs[]=$log_array;
					
					$main_array['ownCdrr_log'] = $logs;
				} else {
					$log_array['id'] = "";
					$log_array['description'] = $status;
					$log_array['created'] = date('Y-m-d H:i:s');
					$log_array['user_id'] = $this -> session -> userdata("user_id");
					$log_array['cdrr_id'] = $id;
					$main_array['ownCdrr_log'] = array($log_array);
				}
			}

		}

		if ($type == "maps") {

			$save = $this->input->post("save_maps");
			if ($save) {
				$code = $this->input->post("report_type");
				$code = $this -> getActualCode($code, $type);
				$reporting_period = $this->input->post('reporting_period');
				$period_begin =date('Y-m-01', strtotime($reporting_period));
				$period_end = date('Y-m-t', strtotime($reporting_period));
				$reporting_period = date('Y-m', strtotime($reporting_period));
				$reports_expected = $this->input->post("reports_expected");
				$reports_actual = $this->input->post("reports_actual");
				$services = $this->input->post("services");
				$sponsors = $this->input->post("sponsor");
				$art_adult = $this->input->post("art_adult");
				$art_child = $this->input->post("art_child");
				$new_male = $this->input->post("new_male");
				$new_female = $this->input->post("new_female");
				$revisit_male = $this->input->post("revisit_male");
				$revisit_female = $this->input->post("revisit_female");
				$new_pmtct = $this->input->post("new_pmtct");
				$revisit_pmtct = $this->input->post("revisit_pmtct");
				$total_infant = $this->input->post("total_infant");
				$pep_adult = $this->input->post("pep_adult");
				$pep_child = $this->input->post("pep_child");
				$total_adult = $this->input->post("tot_cotr_adult");
				$total_child = $this->input->post("tot_cotr_child");
				$diflucan_adult = $this->input->post("diflucan_adult");
				$diflucan_child = $this->input->post("diflucan_child");
				$new_cm = $this->input->post("new_cm");
				$revisit_cm = $this->input->post("revisit_cm");
				$new_oc = $this->input->post("new_oc");
				$revisit_oc = $this->input->post("revisit_oc");
				$comments = $this->input->post("other_regimen");
				//trim comments tabs
				$comments = preg_replace('/[ ]{2,}|[\t]/', ' ', trim($comments));

				$report_id = $this->input->post("report_id");
				$facility_id = $this->input->post("facility_id");
				$regimens = $this->input->post('patient_regimens');
				$patient_numbers = $this->input->post('patient_numbers');
				//insert map
				$main_array['id'] = $id;
				$main_array['status'] = $status;
				$main_array['created'] = $created;
				$main_array['updated'] = $updated;
				$main_array['code'] = $code;
				$main_array['period_begin'] = $period_begin;
				$main_array['period_end'] = $period_end;
				$main_array['reports_expected'] = $reports_expected;
				$main_array['reports_actual'] = $reports_actual;
				$main_array['services'] = $services;
				$main_array['sponsors'] = $sponsors;
				$main_array['art_adult'] = $art_adult;
				$main_array['art_child'] = $art_child;
				$main_array['new_male'] = $new_male;
				$main_array['revisit_male'] = $revisit_male;
				$main_array['new_female'] = $new_female;
				$main_array['revisit_female'] = $revisit_female;
				$main_array['new_pmtct'] = $new_pmtct;
				$main_array['revisit_pmtct'] = $revisit_pmtct;
				$main_array['total_infant'] = $total_infant;
				$main_array['pep_adult'] = $pep_adult;
				$main_array['pep_child'] = $pep_child;
				$main_array['total_adult'] = $total_adult;
				$main_array['total_child'] = $total_child;
				$main_array['diflucan_adult'] = $diflucan_adult;
				$main_array['diflucan_child'] = $diflucan_child;
				$main_array['new_cm'] = $new_cm;
				$main_array['revisit_cm'] = $revisit_cm;
				$main_array['new_oc'] = $new_oc;
				$main_array['revisit_oc'] = $revisit_oc;
				$main_array['comments'] = $comments;
				$main_array['report_id'] = $report_id;
				$main_array['facility_id'] = $facility_id;
				//Insert maps_item
				$maps_item = array();
				$regimen_counter = 0;
				
				if ($regimens != null) {
					foreach ($regimens as $regimen) {
						//Check if any patient numbers have been reported for this regimen
						if ($patient_numbers[$regimen_counter] > 0 && $regimens[$regimen_counter] != 0 && trim($regimens[$regimen_counter]) != '') {
							if ($id == "") {
								$maps_item[$regimen_counter]['id'] = "";
							} else {
								$maps_item[$regimen_counter]['id'] = $item_id[$regimen_counter];
							}
							$maps_item[$regimen_counter]['total'] = $patient_numbers[$regimen_counter];
							$maps_item[$regimen_counter]['regimen_id'] = $regimens[$regimen_counter];
							$maps_item[$regimen_counter]['maps_id'] = $id;
						}
						$regimen_counter++;
					}
				}
				$main_array['ownMaps_item'] = $maps_item;
				//Insert Logs
				$log_array = array();
				if ($id != "") {
					$status = "updated";
					if ($this -> input -> post("status_change")) {
						$status = $this -> input -> post("status_change");
					}
					$logs = Maps_Log::getHydratedLogs($id);

					$log_array['id'] = "";
					$log_array['description'] = $status;
					$log_array['created'] = date('Y-m-d H:i:s');
					$log_array['user_id'] = $this -> session -> userdata("user_id");
					$log_array['maps_id'] = $id;

					$logs[] = $log_array;
					
					$main_array['ownMaps_log'] = $logs;
				} else {
					$log_array['id'] = "";
					$log_array['description'] = $status;
					$log_array['created'] = date('Y-m-d H:i:s');
					$log_array['user_id'] = $this -> session -> userdata("user_id");
					$log_array['maps_id'] = $id;
					$main_array['ownMaps_log'] = array($log_array);
				}
			}
		}
		$main_array = array($main_array);
		if ($status == "prepared") {
			$id = $this -> extract_order($type, $main_array);
			$this -> session -> set_flashdata('order_message', "Your " . strtoupper($type) . " data was successfully saved !");
			redirect("order");
		}else if ($status != "prepared") {
			$id = $this -> extract_order($type, $main_array, $id);
			$this -> session -> set_flashdata('order_message', "Your " . strtoupper($type) . " data was successfully ".$status." !");

			if($status == "approved" || $status == "archived"){
				if($status =='approved'){$this->upload_dhis('maps',$id);}
				redirect("order/view_order/" . $type . "/" . $id);
			}else{
				redirect("order/update_order/" . $type . "/" . $id);
			}
		}
	}

	public function extract_order($type = "cdrr", $responses = array(), $id = "") {
		//Setup parameters
		$params = array(
			'cdrr' => array(
				'id_column' => 'cdrr_id',
				'items_table' => 'cdrr_item',
				'items_column' => 'ownCdrr_item',
				'logs_table' => 'cdrr_log',
				'logs_column' => 'ownCdrr_log'
			),
			'maps' => array(
				'id_column' => 'maps_id',
				'items_table' => 'maps_item',
				'items_column' => 'ownMaps_item',
				'logs_table' => 'maps_log',
				'logs_column' => 'ownMaps_log'
			)
		);

		//Delete existing order
		if ($id != "") {
			$this -> delete_order($type, $id, 1);
		}

		//Save reponses
		foreach ($responses as $response) {
			$items = $response[$params[$type]['items_column']];
			$logs = $response[$params[$type]['logs_column']];
			unset($response[$params[$type]['items_column']]);
			unset($response[$params[$type]['logs_column']]);

			//Get id
			$response['id'] = $id;		
			$this -> db -> insert($type, $response);
			$id = $this->db->insert_id();

			$response = array($params[$type]['items_column'] => $items, $params[$type]['logs_column'] => $logs);
			foreach ($response as $index => $main) {
				if ($index == $params[$type]['items_column']) {
					foreach ($main as $data) {
						$data[$params[$type]['id_column']] = $id;
						$this -> db -> insert($params[$type]['items_table'], $data);
					}
				} else if ($index == $params[$type]['logs_column']) {
					foreach ($main as $data) {
						$data[$params[$type]['id_column']] = $id;
						$this -> db -> insert($params[$type]['logs_table'], $data);
					}
				} 
			}
		}

		return $id;
	}

	public function delete_order($type = "cdrr", $id, $mission = 0) {
		$sql = "SELECT status FROM $type WHERE id='$id'";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if ($results) {
			$status = $results[0]['status'];
			if (($status != "approved" || $mission == 1)) {
				$sql_array = array();
				if ($type == "cdrr") {
					$this -> session -> set_userdata("order_go_back", "cdrr");
					$sql_array[] = "DELETE FROM cdrr where id='$id'";
					$sql_array[] = "DELETE FROM cdrr_item where cdrr_id='$id'";
					$sql_array[] = "DELETE FROM cdrr_log where cdrr_id='$id'";
				} else if ($type == "maps") {
					$this -> session -> set_userdata("order_go_back", "maps");
					$sql_array[] = "DELETE FROM maps where id='$id'";
					$sql_array[] = "DELETE FROM maps_item where maps_id='$id'";
					$sql_array[] = "DELETE FROM maps_log where maps_id='$id'";
				}
				foreach ($sql_array as $sql) {
					$query = $this -> db -> query($sql);
				}
				if ($mission == 0) {
					$this -> session -> set_flashdata("order_delete", $type . " was deleted successfully.");
				}
			} else {
				if ($mission == 0) {
					$this -> session -> set_flashdata("order_delete", $type . " delete failed!");
				}
			}
		} else {
			if ($mission == 0) {
				$this -> session -> set_flashdata("order_delete", $type . " not found!");
			}
		}
		if ($mission == 0) {
			redirect("order");
		}
	}

	public function view_order($type = "cdrr", $id) {
		if ($type == "cdrr") {
			$cdrr_array = array();
			$sql = "SELECT c.*,ci.*,f.*,co.county as county_name,d.name as district_name,IF(c.code='D-CDRR',CONCAT('D-CDRR#',c.id),CONCAT('F-CDRR#',c.id)) as cdrr_label,c.status as status_name,sf.name as facility_name,ci.id as item_id,sf.code as facility_code
			FROM cdrr c
			LEFT JOIN cdrr_item ci ON ci.cdrr_id = c.id
			LEFT JOIN sync_facility sf ON sf.id = c.facility_id
			LEFT JOIN facilities f ON f.facilitycode = sf.code
			LEFT JOIN counties co ON co.id = f.county
			LEFT JOIN district d ON d.id = f.district
			WHERE c.id = '$id'";
			$query = $this -> db -> query($sql);
			$cdrr_array = $query -> result_array();
			$data['cdrr_array'] = $cdrr_array;
			$data['options'] = "view";

			if ($cdrr_array[0]['code'] == "D-CDRR") {
				$code = 3;
			} else if ($cdrr_array[0]['code'] == "F-CDRR_units") {
				if ($this->facility_type > 0) {
					$code = 2;
				} else {
					$code = 0;
				}
			} else if ($cdrr_array[0]['code'] == "F-CDRR_packs") {
				$code = 1;
			}
			$this -> create_order($type, $code, $data);
		} else if ($type == "maps") {//
			$facility_table = 'sync_facility';
			$fmaps_array = array();
			$sql = "SELECT m.*,mi.*,ml.*,f.*,co.county as county_name,d.name as district_name,IF(m.code='D-MAPS',CONCAT('D-MAPS#',m.id),CONCAT('F-MAPS#',m.id)) as maps_id,m.status as status_name,sf.name as facility_name,m.id as map_id,sf.code as facility_code
			FROM maps m
			LEFT JOIN maps_item mi ON mi.maps_id=m.id
			LEFT JOIN maps_log ml ON ml.maps_id=m.id
			LEFT JOIN $facility_table sf ON sf.id=m.facility_id
			LEFT JOIN facilities f ON f.facilitycode=sf.code
			LEFT JOIN counties co ON co.id=f.county
			LEFT JOIN district d ON d.id=f.district
			WHERE m.id='$id'";
			$query = $this -> db -> query($sql);
			$fmaps_array = $query -> result_array();
			$data['fmaps_array'] = $fmaps_array;
			$data['options'] = "view";
			$code = (Facilities::getType($this->facility_code));
			// if ($fmaps_array[0]['code'] == "D-MAPS") {
			// 	$code = 3;
			// } else if ($fmaps_array[0]['code'] == "F-MAPS") {
			// 	$facility_type = Facilities::getType($this->facility_code);
			// 	if ($facility_type == 1) {
			// 		$code = 1;
			// 	} else if ($facility_type == 0) {
			// 		$code = 0;
			// 	} else {
			// 		$code = 2;
			// 	}
			// }
			$this -> create_order($type, $code, $data);
		}
	}

	public function update_order($type = "cdrr", $id) {
		if ($type == "cdrr") {
			$cdrr_array = array();
			$sql = "SELECT c.*,ci.*,f.*,co.county as county_name,d.name as district_name,IF(c.code='D-CDRR',CONCAT('D-CDRR#',c.id),CONCAT('F-CDRR#',c.id)) as cdrr_label,c.status as status_name,sf.name as facility_name,ci.id as item_id,sf.code as facility_code
			FROM cdrr c
			LEFT JOIN cdrr_item ci ON ci.cdrr_id=c.id
			LEFT JOIN sync_facility sf ON sf.id=c.facility_id
			LEFT JOIN facilities f ON f.facilitycode=sf.code
			LEFT JOIN counties co ON co.id=f.county
			LEFT JOIN district d ON d.id=f.district
			WHERE c.id = '$id'";
			$query = $this -> db -> query($sql);
			$cdrr_array = $query -> result_array();
			$data['cdrr_array'] = $cdrr_array;
			$data['options'] = "update";
			if ($cdrr_array[0]['code'] == "D-CDRR") {
				$code = 3;
			} else if ($cdrr_array[0]['code'] == "F-CDRR_units") {
				$facility_code = $this -> session -> userdata("facility");
				if ($this->facility_type > 0) {
					$code = 2;
				} else {
					$code = 0;
				}
			} else if ($cdrr_array[0]['code'] == "F-CDRR_packs") {
				$code = 1;
			}
			$this -> create_order($type, $code, $data);
		} else if ($type == "maps") {
			$fmaps_array = array();
			$sql = "SELECT m.*,mi.*,ml.*,f.*,co.county as county_name,d.name as district_name,IF(m.code='D-MAPS',CONCAT('D-MAPS#',m.id),CONCAT('F-MAPS#',m.id)) as maps_id,m.status as status_name,sf.name as facility_name,m.id as map_id,mi.id as item_id,sf.code as facility_code
			FROM maps m
			LEFT JOIN maps_item mi ON mi.maps_id=m.id
			LEFT JOIN maps_log ml ON ml.maps_id=m.id
			LEFT JOIN sync_facility sf ON sf.id=m.facility_id
			LEFT JOIN facilities f ON f.facilitycode=sf.code
			LEFT JOIN counties co ON co.id=f.county
			LEFT JOIN district d ON d.id=f.district
			WHERE m.id='$id'";
			$query = $this -> db -> query($sql);
			$fmaps_array = $query -> result_array();
			$data['fmaps_array'] = $fmaps_array;
			$data['options'] = "update";
			if ($fmaps_array[0]['code'] == "D-MAPS") {
				$code = 3;
			} else if ($fmaps_array[0]['code'] == "F-MAPS") {
				$facility_code = $this -> session -> userdata("facility");
				$facility_type = Facilities::getType($facility_code);
				if ($facility_type == 1) {
					$code = 1;
				} else if ($facility_type == 0) {
					$code = 0;
				} else {
					$code = 2;
				}
			}
			$this -> create_order($type, $code, $data);

		}
	}

	public function read_order($type = "cdrr", $id) {
		$main_array = array();
		$status='deleted';
		$log_array=array();
		if ($type == "cdrr") {
			$results = Cdrr::getCdrr($id);
			$main_array = $results[0];
			$main_array["ownCdrr_item"] = Cdrr_Item::getItems($id);

			$logs = Cdrr_Log::getHydratedLogs($id);

			$log_array['id'] = "";
			$log_array['description'] = $status;
			$log_array['created'] = date('Y-m-d H:i:s');
			$log_array['user_id'] = $this -> session -> userdata("user_id");
			$log_array['cdrr_id'] = $id;
			
			$logs[] = $log_array;

			$main_array['ownCdrr_log'] = $logs;

		} else if ($type == "maps") {
			$results = Maps::getMap($id);
			$main_array = $results[0];
			$main_array["ownMaps_item"] = Maps_Item::getItems($id);

			$logs = Maps_Log::getHydratedLogs($id);

			$log_array['id'] = "";
			$log_array['description'] = $status;
			$log_array['created'] = date('Y-m-d H:i:s');
			$log_array['user_id'] = $this -> session -> userdata("user_id");
			$log_array['maps_id'] = $id;

			$logs[] = $log_array;

			$main_array['ownMaps_log'] = $logs;
		}
		$main_array['status'] = $status;
		$main_array = array($main_array);

		$id = $this -> extract_order($type, $main_array, $id);
		$this -> session -> set_flashdata('order_delete', "Your " . strtoupper($type) . " data was successfully ".$status." !");

		redirect("order");
	}

	public function download_order($type = "cdrr", $id) {
		$this -> load -> library('PHPExcel');
		if ($type == "cdrr") {
			$cdrr_id = $id;
			$cdrr_array = array();
			$dir = "assets/download";
			$drug_name = "CONCAT_WS('] ',CONCAT_WS(' [',sd.name,sd.abbreviation),CONCAT_WS(' ',sd.strength,sd.formulation)) as drug_map";

			$sql = "SELECT c.*,ci.*,cl.*,f.*,co.county as county_name,d.name as district_name,u.*,al.level_name,IF(c.code='D-CDRR',CONCAT('D-CDRR#',c.id),CONCAT('F-CDRR#',c.id)) as cdrr_label,c.status as status_name,sf.name as facility_name,$drug_name
			FROM cdrr c
			LEFT JOIN cdrr_item ci ON ci.cdrr_id=c.id
			LEFT JOIN cdrr_log cl ON cl.cdrr_id=c.id
			LEFT JOIN sync_facility sf ON sf.id=c.facility_id
			LEFT JOIN facilities f ON f.facilitycode=sf.code
			LEFT JOIN counties co ON co.id=f.county
			LEFT JOIN district d ON d.id=f.district
			LEFT JOIN users u ON u.id = cl.user_id
			LEFT JOIN access_level al ON al.id=u.Access_Level
			LEFT JOIN sync_drug sd ON sd.id=ci.drug_id
			LEFT JOIN drugcode dc ON dc.map=sd.id
			WHERE c.id = '$cdrr_id'";
			$query = $this -> db -> query($sql);
			$cdrr_array = $query -> result_array();
			$report_type = $cdrr_array[0]['code'];

			//Load download template
			$template = "";
			if ($report_type == "D-CDRR") {
				$template = "cdrr_aggregate.xls";
			} else if($report_type == "F-CDRR_units"){
				$template = "cdrr_satellite.xls";
			}else{
				$template = "cdrr_standalone.xls";
			}
			$inputFileName = $_SERVER['DOCUMENT_ROOT'] . '/ADT/assets/templates/orders/v2/' . $template;
			$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objPHPExcel = $objReader -> load($inputFileName);

			/*Delete all files in export folder*/
			if (is_dir($dir)) {
				$files = scandir($dir);
				foreach ($files as $object) {
					if (!in_array($object, array('.','..','.gitkeep'))) {
						unlink($dir . "/" . $object);
					}
				}
			} else {
				mkdir($dir);
			}

			$objPHPExcel -> getActiveSheet() -> SetCellValue('C4', $cdrr_array[0]['name']);
			$objPHPExcel -> getActiveSheet() -> SetCellValue('C5', ucwords($cdrr_array[0]['county_name']));
			$objPHPExcel -> getActiveSheet() -> SetCellValue('E7', date('d/m/Y', strtotime($cdrr_array[0]['period_begin'])));
			
			if ($report_type == "D-CDRR") {
				$objPHPExcel -> getActiveSheet() -> SetCellValue('L4', $cdrr_array[0]['facilitycode']);
				$objPHPExcel -> getActiveSheet() -> SetCellValue('L5', $cdrr_array[0]['district_name']); //Sub_county
				$objPHPExcel -> getActiveSheet() -> SetCellValue('L7', date('d/m/Y', strtotime($cdrr_array[0]['period_end'])));
				$objPHPExcel -> getActiveSheet() -> SetCellValue('B76', $cdrr_array[0]['comments']);
				$drug_start = 16;
				$drug_end = 72;
			}else{
				$objPHPExcel -> getActiveSheet() -> SetCellValue('K4', $cdrr_array[0]['facilitycode']);
				$objPHPExcel -> getActiveSheet() -> SetCellValue('K5', $cdrr_array[0]['district_name']); //Sub_county
				$objPHPExcel -> getActiveSheet() -> SetCellValue('K7', date('d/m/Y', strtotime($cdrr_array[0]['period_end'])));
				$objPHPExcel -> getActiveSheet() -> SetCellValue('B75', $cdrr_array[0]['comments']);
				$drug_start = 15;
				$drug_end = 71;
			}

			
			$arr = $objPHPExcel -> getActiveSheet() -> toArray(null, true, true, true);
			for ($i = $drug_start; $i <= $drug_end; $i++) {
				$drug = $arr[$i]['B'];
				$pack_size = $arr[$i]['C'];
				if ($drug) {
					$key = $this -> getMappedDrug($drug, $pack_size);
					if ($key !== null) {
						foreach ($cdrr_array as $cdrr_item) {
							if ($key == $cdrr_item['drug_id']) {
								$objPHPExcel -> getActiveSheet() -> SetCellValue('D' . $i, $cdrr_item['balance']);
								$objPHPExcel -> getActiveSheet() -> SetCellValue('E' . $i, $cdrr_item['received']);
								$objPHPExcel -> getActiveSheet() -> SetCellValue('F' . $i, $cdrr_item['dispensed_packs']);
								$objPHPExcel -> getActiveSheet() -> SetCellValue('G' . $i, $cdrr_item['losses']);
								$objPHPExcel -> getActiveSheet() -> SetCellValue('H' . $i, $cdrr_item['adjustments']);
								$objPHPExcel -> getActiveSheet() -> SetCellValue('I' . $i, $cdrr_item['adjustments_neg']);
								$objPHPExcel -> getActiveSheet() -> SetCellValue('J' . $i, $cdrr_item['count']);
								if ($cdrr_array[0]['code'] == "D-CDRR") {
									$objPHPExcel -> getActiveSheet() -> SetCellValue('L' . $i, $cdrr_item['aggr_consumed']);
									$objPHPExcel -> getActiveSheet() -> SetCellValue('M' . $i, $cdrr_item['aggr_on_hand']);
									$objPHPExcel -> getActiveSheet() -> SetCellValue('O' . $i, $cdrr_item['expiry_quant']);
									$objPHPExcel -> getActiveSheet() -> SetCellValue('P' . $i, $cdrr_item['expiry_date']);
									$objPHPExcel -> getActiveSheet() -> SetCellValue('Q' . $i, $cdrr_item['out_of_stock']);
									$objPHPExcel -> getActiveSheet() -> SetCellValue('R' . $i, $cdrr_item['resupply']);
								} else {
									$objPHPExcel -> getActiveSheet() -> SetCellValue('K' . $i, $cdrr_item['expiry_quant']);
									$objPHPExcel -> getActiveSheet() -> SetCellValue('L' . $i, $cdrr_item['expiry_date']);
									$objPHPExcel -> getActiveSheet() -> SetCellValue('M' . $i, $cdrr_item['out_of_stock']);
									$objPHPExcel -> getActiveSheet() -> SetCellValue('N' . $i, $cdrr_item['resupply']);
								}
							} //End of key match to cdrr_id
						} //End of foreach
					} //End of key
				} //End of drug
			} //End of for loop

			if ($cdrr_array[0]['code'] == 'D-CDRR') {
				$objPHPExcel -> getActiveSheet() -> SetCellValue('D83', $cdrr_array[0]['reports_expected']);
				$objPHPExcel -> getActiveSheet() -> SetCellValue('L83', $cdrr_array[0]['reports_actual']);

				$logs = Cdrr_Log::getLogs($cdrr_id);
				foreach ($logs as $log) {
					if ($log -> description == "prepared") {
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C95', $log -> user -> Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C97', $log -> user -> Phone_Number);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('O95', $log -> user -> Access -> Level_Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('H97', $log -> created);
					} else if ($log -> description == "approved") {
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C100', $log -> s_user -> name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C103', $log -> user -> Phone_Number);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('O100', $log -> user -> Access -> Level_Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('H103', $log -> created);
					}
				}

			} else {
				$logs = Cdrr_Log::getLogs($cdrr_id);
				foreach ($logs as $log) {
					if ($log -> description == "prepared") {
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C89', $log -> user -> Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C91', $log -> user -> Phone_Number);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('M89', $log -> user -> Access -> Level_Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('H91', $log -> created);
					} else if ($log -> description == "approved") {
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C93', $log -> user -> Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C96', $log -> user -> Phone_Number);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('M93', $log -> user -> Access -> Level_Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('H96', $log -> created);
					}
				}

			}

			//Generate file
			ob_start();
			$facility_name=str_replace(array("/","'")," ", $cdrr_array[0]['facility_name']);
			$original_filename = $cdrr_array[0]['cdrr_label'] . " " . $facility_name . " " . $cdrr_array[0]['period_begin'] . " to " . $cdrr_array[0]['period_end'] . ".xls";
			$filename = $dir . "/" . urldecode($original_filename);
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			$objWriter -> save($filename);
			$objPHPExcel -> disconnectWorksheets();
			unset($objPHPExcel);
			if (file_exists($filename)) {
				$filename = str_replace("#", "%23", $filename);
				redirect($filename);
			}

		} else if ($type == "maps") {
			$fmaps_id = $id;
			$fmaps_array = array();
			$dir = "assets/download";

			$sql = "SELECT m.*,mi.*,ml.*,f.*,co.county as county_name,d.name as district_name,u.*,al.level_name,IF(m.code='D-MAPS',CONCAT('D-MAPS#',m.id),CONCAT('F-MAPS#',m.id)) as maps_id,m.status as status_name,sf.name as facility_name,m.id as map_id
			FROM maps m
			LEFT JOIN maps_item mi ON mi.maps_id=m.id
			LEFT JOIN maps_log ml ON ml.maps_id=m.id
			LEFT JOIN sync_facility sf ON sf.id=m.facility_id
			LEFT JOIN facilities f ON f.facilitycode=sf.code	
			LEFT JOIN counties co ON co.id=f.county
			LEFT JOIN district d ON d.id=f.district
			LEFT JOIN users u ON u.id=ml.user_id
			LEFT JOIN access_level al ON al.id=u.Access_Level
			WHERE m.id = '$fmaps_id'";
			$query = $this -> db -> query($sql);
			$fmaps_array = $query -> result_array();
			$report_type = $fmaps_array[0]['code'];

			//Load download template
			$template = "";
			if ($report_type == "D-MAPS") {
				$template = "maps_aggregate.xls";
			}else{
				$template = "maps_standalone.xls";
			}
			$inputFileName = $_SERVER['DOCUMENT_ROOT'] . '/ADT/assets/templates/orders/v2/' . $template;
			$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objPHPExcel = $objReader -> load($inputFileName);

			/*Delete all files in export folder*/
			if (is_dir($dir)) {
				$files = scandir($dir);
				foreach ($files as $object) {
					if (!in_array($object, array('.','..','.gitkeep'))) {
						unlink($dir . "/" . $object);
					}
				}
			} else {
				mkdir($dir);
			}

			//Top menu
			$objPHPExcel -> getActiveSheet() -> SetCellValue('C4', $fmaps_array[0]['facility_name']);
			$objPHPExcel -> getActiveSheet() -> SetCellValue('C5', ucwords($fmaps_array[0]['county_name']));
			$objPHPExcel -> getActiveSheet() -> SetCellValue('D7', date('d/m/Y', strtotime($fmaps_array[0]['period_begin'])));
			$objPHPExcel -> getActiveSheet() -> SetCellValue('G4', $fmaps_array[0]['facilitycode']);
			$objPHPExcel -> getActiveSheet() -> SetCellValue('G5', $fmaps_array[0]['district_name']);//Sub_county			
			$objPHPExcel -> getActiveSheet() -> SetCellValue('G7', date('d/m/Y', strtotime($fmaps_array[0]['period_end'])));

			//Regimen columns
			$arr = $objPHPExcel -> getActiveSheet() -> toArray(null, true, true, true);

			//First column
			for ($i = 14; $i <= 84; $i++) {
				if (!in_array($i, array(28, 36, 49, 50, 66, 72))) {
					$regimen_code = $arr[$i]['B'];
					$regimen_desc = $arr[$i]['C'];
					$key = $this -> getMappedRegimen($regimen_code, $regimen_desc);
					if ($key !== null) {
						foreach ($fmaps_array as $fmaps_item) {
							if ($key == $fmaps_item['regimen_id']) {
								$objPHPExcel -> getActiveSheet() -> SetCellValue('D' . $i, $fmaps_item['total']);
							}
						}
					}
				}
			}

			//Second column
			for ($i = 14; $i <= 56; $i++) {
				if (!in_array($i, array(23, 29, 33, 34, 38, 42, 45, 46, 51, 54))) {
					$regimen_code = $arr[$i]['F'];
					$regimen_desc = $arr[$i]['G'];
					$key = $this -> getMappedRegimen($regimen_code, $regimen_desc);
					if ($key !== null) {
						foreach ($fmaps_array as $fmaps_item) {
							if ($key == $fmaps_item['regimen_id']) {
								$objPHPExcel -> getActiveSheet() -> SetCellValue('H' . $i, $fmaps_item['total']);
							}
						}
					}
				}
			}

			//If order has changed status, check who prepared the order
			$logs = Maps_Log::getMapLogs($fmaps_id);
			if ($report_type == "D-MAPS") {
				$objPHPExcel -> getActiveSheet() -> SetCellValue('D101', $fmaps_array[0]['reports_expected']);
				$objPHPExcel -> getActiveSheet() -> SetCellValue('H101', $fmaps_array[0]['reports_actual']);
				foreach ($logs as $log) {
					if ($log -> description == "prepared") {
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C87', $log -> user -> Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C90', $log -> created);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C91', $log -> user -> Access -> Level_Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C92', $log -> user -> Phone_Number);
					} else if ($log -> description == "approved") {
						$objPHPExcel -> getActiveSheet() -> SetCellValue('G74', $log -> user -> Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('G77', $log -> created);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('G78', $log -> user -> Access -> Level_Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('G79', $log -> user -> Phone_Number);
					}
				}
			}else{
				foreach ($logs as $log) {
					if ($log -> description == "prepared") {
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C87', $log -> user -> Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C90', $log -> created);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C91', $log -> user -> Access -> Level_Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('C92', $log -> user -> Phone_Number);
					} else if ($log -> description == "approved") {
						$objPHPExcel -> getActiveSheet() -> SetCellValue('G74', $log -> user -> Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('G77', $log -> created);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('G78', $log -> user -> Access -> Level_Name);
						$objPHPExcel -> getActiveSheet() -> SetCellValue('G79', $log -> user -> Phone_Number);
					}
				}
			}

			//Generate file
			ob_start();
			$facility_name=str_replace(array("/","'")," ", $fmaps_array[0]['facility_name']);
			$original_filename = $fmaps_array[0]['maps_id'] . " " . $facility_name . " " . $fmaps_array[0]['period_begin'] . " to " . $fmaps_array[0]['period_end'] . ".xls";
			$original_filename = str_replace('/','-', $original_filename);
			$filename = $dir . "/" . urldecode($original_filename);
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			$objWriter -> save($filename);
			$objPHPExcel -> disconnectWorksheets();
			unset($objPHPExcel);
			if (file_exists($filename)) {
				$filename = str_replace("#", "%23", $filename);
				redirect($filename);
			}
		}
	}

	public function clean_date($base_date) {
		$formatted_date = '';
		if($base_date){
			//Split date elements
			$pos = strpos($base_date, '-');
			if ($pos !== FALSE) {
				$date_array = explode('-', $base_date);
				$year = '20'.$date_array[2];

			}else{
				$date_array = explode('/', $base_date);
				$year = @$date_array[2];
			}	

			$day = $date_array[0];
			$month = $date_array[1];

			//Create and format date
			$date = new DateTime();
			$date->setDate($year, $month, $day);
			$formatted_date = $date->format('Y-m-d');

		}

		return $formatted_date;
	}

	public function checkFileType($type, $text) {

		if ($type == "D-CDRR") {
			$match = trim("CENTRAL SITE  / SUB-COUNTY STORE CONSUMPTION DATA REPORT and REQUEST (CS-CDRR) for ANTIRETROVIRAL and OPPORTUNISTIC INFECTION MEDICINES");
		} else if ($type == "D-MAPS") {
			$match = trim("FACILITY MONTHLY ARV PATIENT SUMMARY (F-MAPS) Report (MoH 729B)");
		} else if ($type == "F-CDRR_packs" || $type == "F-CDRR_units") {
			$match = trim("FACILITY CONSUMPTION DATA REPORT and REQUEST (F-CDRR) for ANTIRETROVIRAL and OPPORTUNISTIC INFECTION MEDICINES");
		} else if ($type == "F-MAPS") {
			$match = trim("FACILITY MONTHLY ARV PATIENT SUMMARY (F-MAPS) Report (MoH 729B)");
		}

		//Test
		if (trim($text) === $match) {
			return true;
		} else {
			return false;
		}
	}

	public function getMappedDrug($drug_name = "", $packsize = "") {
		if ($drug_name != "") {
			$drugs = explode(" ", trim($drug_name));
			$drug_list = array();
			foreach ($drugs as $drug) {
				$drug = str_ireplace(array("(", ")"), array("", ""), $drug);
				if ($drug != null) {
					$sql = "SELECT sd.id 
					FROM sync_drug sd
					WHERE (sd.name like '%$drug%'
					OR sd.abbreviation like '%$drug%'
					OR sd.strength = '$drug'
					OR sd.formulation = '$drug'
					OR sd.unit='$drug')
					AND sd.packsize='$packsize'";
					$query = $this -> db -> query($sql);
					$results = $query -> result_array();
					if ($results) {
						foreach ($results as $result) {
							$drug_list[] = $result['id'];
						}
					}
				}
			}
			$list_array = array_count_values($drug_list);
			if (is_array($list_array)) {
				if (!empty($list_array)) {
					return $key = array_search(max(array_count_values($drug_list)), array_count_values($drug_list));
				}
			}
		}
		return null;
	}

	public function getMappedRegimen($regimen_code = "", $regimen_desc = "") {
		if ($regimen_code != "") {
			$sql = "SELECT r.id as map
			FROM sync_regimen r
			WHERE r.code='$regimen_code'";
			$query = $this -> db -> query($sql);
			$results = $query -> result_array();
			if ($results) {
				return $results[0]['map'];
			} else {
				return null;
			}
		}
		return null;
	}

	public function import_order($type = "cdrr") {
		$ret = array();
		$this -> load -> library('PHPExcel');

		if (isset($_FILES["file"])) {
			$fileCount = count($_FILES["file"]["tmp_name"]);
			for ($i = 0; $i < $fileCount; $i++) {
				$filename = $_FILES["file"]["name"][$i];
				$inputFileType = PHPExcel_IOFactory::identify($_FILES["file"]["tmp_name"][$i]);
				$objReader = PHPExcel_IOFactory::createReader($inputFileType);
				$objPHPExcel = $objReader -> load($_FILES["file"]["tmp_name"][$i]);
				$status = "prepared";
				$arr = $objPHPExcel -> getActiveSheet() -> toArray(null, true, true, true);
				$highestColumm = $objPHPExcel -> setActiveSheetIndex(0) -> getHighestColumn();
				$highestRow = $objPHPExcel -> setActiveSheetIndex(0) -> getHighestRow();
				if ($type == "cdrr") {
					$this -> session -> set_userdata("order_go_back", "cdrr");

					$first_row = 4;
					$facility_name = trim($arr[$first_row]['C'] . $arr[$first_row]['D'] . $arr[$first_row]['E']);
					$facility_code = trim($arr[$first_row]['K'] . $arr[$first_row]['L'] . $arr[$first_row]['M']);

					$second_row = 5;
					$county = trim($arr[$second_row]['C'] . $arr[$second_row]['D'] . $arr[$second_row]['E']);
					$sub_county = trim($arr[$second_row]['K'] . $arr[$second_row]['L'] . $arr[$second_row]['M']);

					$third_row = 7;
					$period_begin = $this -> clean_date($objPHPExcel->getActiveSheet()->getCell('E' . $third_row)->getFormattedValue());
					$period_end = $this -> clean_date($objPHPExcel->getActiveSheet()->getCell('K' . $third_row)->getFormattedValue());

					$code = "F-CDRR_units";
					$text = $arr[2]['B'];

					$file_type = $this -> checkFileType($code, $text);
					$facilities = Sync_Facility::getId($facility_code, 0);

					$facility_id= $facilities['id'];
					$duplicate = $this -> check_duplicate($code, $period_begin, $period_end, $facilities['id']);

					if ($period_begin != date('Y-m-01', strtotime(date('Y-m-d') . "-1 month")) || $period_end != date('Y-m-t', strtotime(date('Y-m-d') . "-1 month"))) {
						$ret[] = "You can only report for current month. Kindly check the period fields !-" . $_FILES["file"]["name"][$i];
					} else if ($file_type == false) {
						$ret[] = "Incorrect File Selected-" . $_FILES["file"]["name"][$i];
					} else if ($duplicate == true) {
						$ret[] = "A cdrr report already exists for this month !-" . $_FILES["file"]["name"][$i];
					} else if ($facility_id == null) {
						$ret[] = "No facility found associated with this user!<br>
						- Make sure that you have updated your settings
						- Check that you have entered the correct facility code for the file being uploaded!";
					} else {
						$seventh_row = 75;
						$comments = trim($arr[$seventh_row]['B']);
						$comments .= trim($arr[$seventh_row]['C']);
						$comments .= trim($arr[$seventh_row]['D']);
						$comments .= trim($arr[$seventh_row]['E']);
						$comments .= trim($arr[$seventh_row]['F']);
						$comments .= trim($arr[$seventh_row]['G']);
						$comments .= trim($arr[$seventh_row]['H']);
						$comments .= trim($arr[$seventh_row]['I']);
						$comments .= trim($arr[$seventh_row]['J']);
						$comments .= trim($arr[$seventh_row]['K']);
						$comments .= trim($arr[$seventh_row]['L']);
						$comments .= trim($arr[$seventh_row]['M']);
						$comments .= trim($arr[$seventh_row]['N']);

						//Save Import Values
						$created = date('Y-m-d H:i:s');

						$main_array = array();
						$main_array['id'] = "";
						$main_array['status'] = $status;
						$main_array['created'] = date('Y-m-d H:i:s');
						$main_array['updated'] = "";
						$main_array['code'] = $code;
						$main_array['period_begin'] = $period_begin;
						$main_array['period_end'] = $period_end;
						$main_array['comments'] = $comments;
						$main_array['reports_expected'] = null;
						$main_array['reports_actual'] = null;
						$main_array['services'] = 'ART,PEP,PMTCT';
						$main_array['sponsors'] = 'GOK';
						$main_array['non_arv'] = 0;
						$main_array['delivery_note'] = null;
						$main_array['order_id'] = 0;
						$main_array['facility_id'] = $facility_id;

						$sixth_row = 15;
						$cdrr_array = array();
						$commodity_counter = 0;

						for ($i = $sixth_row; $sixth_row, $i <= 71; $i++) {
							if (!in_array($i, array(35, 55, 68))) {
								$drug_name = trim($arr[$i]['B']);
								$pack_size = trim($arr[$i]['C']);
								$pack_size = 1;
								$commodity = $this -> getMappedDrug($drug_name, $pack_size);
								if ($commodity != null) {
									$cdrr_array[$commodity_counter]['id'] = "";
									$cdrr_array[$commodity_counter]['balance'] = str_replace(',', '', trim($arr[$i]['D']));
									$cdrr_array[$commodity_counter]['received'] = str_replace(',', '', trim($arr[$i]['E']));
									$cdrr_array[$commodity_counter]['dispensed_units'] = str_replace(',', '', trim($arr[$i]['F']));
									$cdrr_array[$commodity_counter]['dispensed_packs'] = ceil(str_replace(',', '', @trim($arr[$i]['F']) / @$pack_size));
									$cdrr_array[$commodity_counter]['losses'] = str_replace(',', '', trim($arr[$i]['G']));
									$cdrr_array[$commodity_counter]['adjustments'] = str_replace(',', '', trim($arr[$i]['H']));
									$cdrr_array[$commodity_counter]['adjustments_neg'] = str_replace(',', '', trim($arr[$i]['I']));
									$cdrr_array[$commodity_counter]['count'] = str_replace(',', '', trim($arr[$i]['K']));
									$cdrr_array[$commodity_counter]['expiry_quant'] = str_replace(',', '', trim($arr[$i]['L']));

									$expiry_date = $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getFormattedValue();

									if (!in_array($expiry_date, array("-", "", null, "1970-01-01", "0000-00-00"))) {
										$cdrr_array[$commodity_counter]['expiry_date'] = $this -> clean_date($expiry_date);
									} else {
										$cdrr_array[$commodity_counter]['expiry_date'] = null;
									}
									$cdrr_array[$commodity_counter]['out_of_stock'] = str_replace(',', '', trim($arr[$i]['M']));
									$cdrr_array[$commodity_counter]['resupply'] = str_replace(',', '', trim($objPHPExcel->getActiveSheet()->getCell('N' . $i)->getOldCalculatedValue())); 
									$cdrr_array[$commodity_counter]['aggr_consumed'] = null;
									$cdrr_array[$commodity_counter]['aggr_on_hand'] = null;
									$cdrr_array[$commodity_counter]['publish'] = 0;
									$cdrr_array[$commodity_counter]['cdrr_id'] = "";
									$cdrr_array[$commodity_counter]['drug_id'] = $commodity;
									$commodity_counter++;
								}
							}
						}
						$main_array['ownCdrr_item'] = $cdrr_array;

						$log_array = array();
						$log_array['id'] = "";
						$log_array['description'] = $status;
						$log_array['created'] = date('Y-m-d H:i:s');
						$log_array['user_id'] = $this -> session -> userdata("user_id");
						$log_array['cdrr_id'] = "";

						$main_array['ownCdrr_log'] = array($log_array);
						$main_array = array($main_array);

						//Save order
						$id = $this -> extract_order($type, $main_array);
						$ret[] = "Your " . strtoupper($type) . " data was successfully saved !-" . $filename;
					}

				} else if ($type == "maps") {
					$this -> session -> set_userdata("order_go_back", "fmaps");

					$first_row = 4;
					$facility_name = trim($arr[$first_row]['C']);
					$facility_code = trim($arr[$first_row]['G']);
					$second_row = 5;
					$county = trim($arr[$first_row]['C']);
					$sub_county = trim($arr[$first_row]['G']);

					$third_row = 7;
					$period_begin = $this -> clean_date($objPHPExcel->getActiveSheet()->getCell('D' . $third_row)->getFormattedValue());
					$period_end = $this -> clean_date($objPHPExcel->getActiveSheet()->getCell('G' . $third_row)->getFormattedValue());

					$code = "F-MAPS";
					$text = $arr[2]['B'];

					$facilities = Sync_Facility::getId($facility_code, 0);
					$facility_id= $facilities['id'];
					$duplicate = $this -> check_duplicate($code, $period_begin, $period_end, $facilities['id'], "maps");

					$file_type = $this -> checkFileType($code, $text);

					if ($period_begin != date('Y-m-01', strtotime(date('Y-m-d') . "-1 month")) || $period_end != date('Y-m-t', strtotime(date('Y-m-d') . "-1 month"))) {
						$ret[] = "You can only report for current month. Kindly check the period fields !-" . $_FILES["file"]["name"][$i];
					} else if ($duplicate == true) {
						$ret[] = "An fmap report already exists for this month !-" . $_FILES["file"]["name"][$i];
					} else if ($file_type == false) {
						$ret[] = "Incorrect File Selected-" . $_FILES["file"]["name"][$i];
					} else if ($facility_id == null) {
						$ret[] = "No facility found associated with this user!<br>
						- Make sure that you have updated your settings
						- Check that you have entered the correct facility code for the file being uploaded!";
					} else {	
						//Save Import Values
						$created = date('Y-m-d H:i:s');
						$main_array = array();
						$main_array['id'] = "";
						$main_array['status'] = $status;
						$main_array['created'] = $created;
						$main_array['updated'] = "";
						$main_array['code'] = $code;
						$main_array['period_begin'] = $period_begin;
						$main_array['period_end'] = $period_end;
						$main_array['reports_expected'] = null;
						$main_array['reports_actual'] = null;
						$main_array['services'] = 'ART,PEP,PMTCT';
						$main_array['sponsors'] = 'GOK';
						$main_array['comments'] = "";
						$main_array['report_id'] = "";
						$main_array['facility_id'] = $facility_id;

						//Insert Maps items
						$sixth_row = 14;
						$maps_array = array();
						$regimen_counter = 0;
						$other_regimens = "";

						//First column
						for ($i = $sixth_row; $sixth_row, $i <= 84; $i++) {
							if (!in_array($i, array(28, 36, 49, 50, 66, 72))) {
								//Ensure value is > 0
								$total = $arr[$i]['D'];
								if ($total > 0) {	
									$regimen_code = $arr[$i]['B'];
									$regimen_desc = $arr[$i]['C'];
									$regimen_id = $this -> getMappedRegimen($regimen_code, $regimen_desc);
									if ($regimen_id != null && $total != null) {
										$maps_array[$regimen_counter]["id"] = "";
										$maps_array[$regimen_counter]["regimen_id"] = $regimen_id;
										$maps_array[$regimen_counter]["total"] = $total;
										$maps_array[$regimen_counter]["maps_id"] = "";
									}
									$regimen_counter++;
								}
							}
						}

						//Second column
						for ($i = $sixth_row; $sixth_row, $i <= 56; $i++) {
							if (!in_array($i, array(23, 29, 33, 34, 38, 42, 45, 46, 51, 54))) {
								//Ensure value is > 0
								$total = $arr[$i]['H'];
								if ($total > 0) {
									$regimen_code = $arr[$i]['F'];
									$regimen_desc = $arr[$i]['G'];
									$regimen_id = $this -> getMappedRegimen($regimen_code, $regimen_desc);
									if ($regimen_id != null && $total != null) {
										$maps_array[$regimen_counter]["id"] = "";
										$maps_array[$regimen_counter]["regimen_id"] = $regimen_id;
										$maps_array[$regimen_counter]["total"] = $total;
										$maps_array[$regimen_counter]["maps_id"] = "";
									}
									$regimen_counter++;
								}
							}
						}
						$main_array['ownMaps_item'] = $maps_array;

						//Insert logs
						$log_array = array();
						$log_array['id'] = "";
						$log_array['description'] = $status;
						$log_array['created'] = $created;
						$log_array['user_id'] = $this -> session -> userdata("user_id");
						$log_array['maps_id'] = '';

						$main_array['ownMaps_log'] = array($log_array);

						$main_array = array($main_array);
						$id = $this -> extract_order($type, $main_array);
						$ret[] = "Your " . strtoupper($type) . " data was successfully saved !-". $filename;
					}
				}
				
			}
		}
		$ret = implode("<br/>", $ret);
		$this -> session -> set_flashdata('order_message', $ret);
		redirect("order");
	}

	public function getMainRegimen($regimen_code = "", $regimen_desc = "") {
		if ($regimen_code != "") {
			$sql = "SELECT sr.id
			FROM sync_regimen sr
			WHERE(sr.code='$regimen_code'
			OR sr.name='$regimen_desc')";
			$query = $this -> db -> query($sql);
			$results = $query -> result_array();
			if ($results) {
				return $results[0]['id'];
			} else {
				return null;
			}
		}
		return null;
	}

	public function get_aggregated_fmaps($period_start, $period_end) {//Generate aggregated fmaps
		$map_id = '"NOTTHERE"';
		$facility_code = $this -> session -> userdata("facility");

		//Get only F-MAPS
		$sql_maps = "
		SELECT m.id, m.code, m.status, m.period_begin,m.period_end,m.reports_expected,m.reports_actual,m.services,m.sponsors,m.art_adult, m.art_child,m.new_male,m.revisit_male,m.new_female,m.revisit_female,m.new_pmtct,m.revisit_pmtct,m.total_infant,m.pep_adult,m.pep_child,m.total_adult,m.total_child, m.diflucan_adult,m.diflucan_child,m.new_cm,m.revisit_cm,m.new_oc,m.revisit_oc,m.comments 
		FROM maps m LEFT JOIN sync_facility sf ON sf.id=m.facility_id 
		WHERE  m.status ='prepared' 
		AND m.code='F-MAPS'
		
		AND m.period_begin='$period_start'  ORDER BY m.code DESC
		";
		
		$query = $this -> db -> query($sql_maps);
		$results = $query -> result_array();
		$maps_array = array();
		$maps_items_array = array();
		$maps_array['reports_expected'] = $this -> expectedReports($facility_code);
		$maps_array['reports_actual'] = $this ->actualReports($facility_code,$period_start,'maps');
		$maps_array['art_adult'] = 0;
		$maps_array['art_child'] = 0;
		$maps_array['new_male'] = 0;
		$maps_array['revisit_male'] = 0;
		$maps_array['new_female'] = 0;
		$maps_array['revisit_female'] = 0;
		$maps_array['new_pmtct'] = 0;
		$maps_array['revisit_pmtct'] = 0;
		$maps_array['total_infant'] = 0;
		$maps_array['pep_adult'] = 0;
		$maps_array['pep_child'] = 0;
		$maps_array['total_adult'] = 0;
		$maps_array['total_child'] = 0;
		$maps_array['diflucan_adult'] = 0;
		$maps_array['diflucan_child'] = 0;
		$maps_array['new_cm'] = 0;
		$maps_array['revisit_cm'] = 0;
		$maps_array['new_oc'] = 0;
		$maps_array['revisit_oc'] = 0;
		$maps_array['comments'] = '';
		$x = 0;
		foreach ($results as $value) {
			if ($x == 0) {
				$map_id = $value['id'];
				$x++;
			} else {
				$map_id .= ' OR maps_id = ' . $value['id'];
			}

			$maps_array['status'] = $value['status'];
			$maps_array['period_begin'] = $value['period_begin'];
			$maps_array['period_end'] = $value['period_end'];
			$maps_array['services'] = $value['services'];
			$maps_array['sponsors'] = $value['sponsors'];
			$maps_array['reports_actual'] = count($results);
			$maps_array['art_adult'] = $maps_array['art_adult'] + $value['art_adult'];
			$maps_array['art_child'] = $maps_array['art_child'] + $value['art_child'];
			$maps_array['new_male'] = $maps_array['new_male'] + $value['new_male'];
			$maps_array['revisit_male'] = $maps_array['revisit_male'] + $value['revisit_male'];
			$maps_array['new_female'] = $maps_array['new_female'] + $value['new_female'];
			$maps_array['revisit_female'] = $maps_array['revisit_female'] + $value['revisit_female'];
			$maps_array['new_pmtct'] = $maps_array['new_pmtct'] + $value['new_pmtct'];
			$maps_array['revisit_pmtct'] = $maps_array['revisit_pmtct'] + $value['revisit_pmtct'];
			$maps_array['total_infant'] = $maps_array['total_infant'] + $value['total_infant'];
			$maps_array['pep_adult'] = $maps_array['pep_adult'] + $value['pep_adult'];
			$maps_array['pep_child'] = $maps_array['pep_child'] + $value['pep_child'];
			$maps_array['total_adult'] = $maps_array['total_adult'] + $value['total_adult'];
			$maps_array['total_child'] = $maps_array['total_child'] + $value['total_child'];
			$maps_array['diflucan_adult'] = $maps_array['diflucan_adult'] + $value['diflucan_adult'];
			$maps_array['diflucan_child'] = $maps_array['diflucan_child'] + $value['diflucan_child'];
			$maps_array['new_cm'] = $maps_array['new_cm'] + $value['new_cm'];
			$maps_array['revisit_cm'] = $maps_array['revisit_cm'] + $value['revisit_cm'];
			$maps_array['new_oc'] = $maps_array['new_oc'] + $value['new_oc'];
			$maps_array['revisit_oc'] = $maps_array['revisit_oc'] + $value['revisit_oc'];
			$maps_array['comments'] = $maps_array['comments'] . ' - ' . $value['comments'];

		}
		
		//Get maps items
		$sql_items = '
		SELECT temp.regimen_id,temp.maps_id,SUM(temp.total) as total FROM
		(
		SELECT DISTINCT regimen_id,maps_id,total FROM maps_item WHERE (maps_id=' . $map_id . ')
	) as temp  GROUP BY temp.regimen_id';
	
	$query_items = $this -> db -> query($sql_items);
	$maps_items_array = $query_items -> result_array();

	$data['maps_array'] = $maps_array;
	$data['maps_items_array'] = $maps_items_array;
	
	echo json_encode($data);
	
}

public function get_fmaps_details($map_id) {
	$facility_code = $this -> session -> userdata('facility');
		//Get maps
	$sql_maps = 'SELECT m.* FROM maps m WHERE m.id="' . $map_id . '" ORDER BY m.code DESC';
	$query = $this->db->query($sql_maps);
	$results = $query->result_array();
	$maps_array = array();
	$maps_items_array = array();
	$maps_array['art_adult'] = 0;
	$maps_array['art_child'] = 0;
	$maps_array['new_male']  = 0;
	$maps_array['revisit_male'] = 0;
	$maps_array['new_female']   = 0;
	$maps_array['revisit_female'] = 0;
	$maps_array['new_pmtct'] = 0;
	$maps_array['revisit_pmtct'] = 0;
	$maps_array['total_infant']  = 0;
	$maps_array['pep_adult'] = 0;
	$maps_array['pep_child'] = 0;
		// Reusable variables
	$maps_array['total_adult'] 	= 0;
	$maps_array['total_child'] 	= 0;
	$maps_array['diflucan_adult'] = 0;
	$maps_array['diflucan_child'] = 0;
		// Used in the new template. NEW ADDED******
	$maps_array['cm&oc_adult'] 	= 0;
	$maps_array['cm&oc_child'] 	= 0;
	$maps_array['new_cm&oc'] 	= 0;
	$maps_array['revisit_cm&oc']= 0;

		// not used in the new tenplate. Discard****
	$maps_array['new_cm'] = 0;
	$maps_array['revisit_cm'] = 0;
	$maps_array['new_oc'] = 0;
	$maps_array['revisit_oc'] = 0;
	$maps_array['comments'] = '';
	foreach ($results as $value) {
		$maps_array['status'] = $value['status'];
		$maps_array['period_begin'] = $value['period_begin'];
		$maps_array['period_end'] = $value['period_end'];
		$maps_array['services'] = $value['services'];
		$maps_array['sponsors'] = $value['sponsors'];
		$maps_array['reports_actual'] = count($results);
		$maps_array['art_adult'] = $maps_array['art_adult'] + $value['art_adult'];
		$maps_array['art_child'] = $maps_array['art_child'] + $value['art_child'];
		$maps_array['new_male'] = $maps_array['new_male'] + $value['new_male'];
		$maps_array['revisit_male'] = $maps_array['revisit_male'] + $value['revisit_male'];
		$maps_array['new_female'] = $maps_array['new_female'] + $value['new_female'];
		$maps_array['revisit_female'] = $maps_array['revisit_female'] + $value['revisit_female'];
		$maps_array['new_pmtct'] = $maps_array['new_pmtct'] + $value['new_pmtct'];
		$maps_array['revisit_pmtct'] = $maps_array['revisit_pmtct'] + $value['revisit_pmtct'];
		$maps_array['total_infant'] = $maps_array['total_infant'] + $value['total_infant'];
		$maps_array['pep_adult'] = $maps_array['pep_adult'] + $value['pep_adult'];
		$maps_array['pep_child'] = $maps_array['pep_child'] + $value['pep_child'];
		$maps_array['total_adult'] = $maps_array['total_adult'] + $value['total_adult'];
		$maps_array['total_child'] = $maps_array['total_child'] + $value['total_child'];
		$maps_array['diflucan_adult'] = $maps_array['diflucan_adult'] + $value['diflucan_adult'];
		$maps_array['diflucan_child'] = $maps_array['diflucan_child'] + $value['diflucan_child'];
		$maps_array['new_cm'] = $maps_array['new_cm'] + $value['new_cm'];
		$maps_array['revisit_cm'] = $maps_array['revisit_cm'] + $value['revisit_cm'];
		$maps_array['new_oc'] = $maps_array['new_oc'] + $value['new_oc'];
		$maps_array['revisit_oc'] = $maps_array['revisit_oc'] + $value['revisit_oc'];
		$maps_array['comments'] = $value['comments'];
	}
	
	$maps_array['reports_expected'] = $this->expectedReports($facility_code);
	$maps_array['reports_actual'] = $this ->actualReports($facility_code,$maps_array['period_begin'],'maps');

		//Get maps items
	$sql_items = 'SELECT id as item_id,regimen_id,maps_id, total FROM maps_item WHERE maps_id=' . $map_id . ' GROUP BY regimen_id';
	$query_items = $this -> db -> query($sql_items);
	$maps_items_array = $query_items -> result_array();

	$data['maps_array'] = $maps_array;
	$data['maps_items_array'] = $maps_items_array;
	echo json_encode($data);
}
/******oi*/
public function getoiPatients() {
	$facility_code = $this -> session -> userdata("facility");
	
	$sql = "SELECT ROUND(DATEDIFF(CURRENT_DATE, dob)/365) AS age, drug_prophylaxis
	FROM patient p
	LEFT JOIN patient_status ps ON ps.id = p.current_status
	WHERE ps.Name LIKE '%active%'
	AND start_regimen_date <= DATE_FORMAT(LAST_DAY(CURDATE()-INTERVAL 1 MONTH),'%Y-%m-%d ')";
	$query = $this ->db->query($sql);
	$results = $query->result_array();
	$a=0;$b=0;$c=0;$d=0;$e=0;$f=0;$g=0;$h=0;
	foreach($results as $oipatient)
	{
		$age=$oipatient['age'];
		$drugprophilaxis=$oipatient['drug_prophylaxis'];

			//cotrimoxazole ctx
		if(strpos($drugprophilaxis, '1')!== false AND $age >= 15){
			$a=$a+1;
			
		}
		if(strpos($drugprophilaxis, '1') !== false AND $age < 15){
			$b=$b+1;
			
		}

			//Dapsone
		if(strpos($drugprophilaxis, '2') !== false AND $age >= 15){
			$c=$c+1;
			
		}
		if(strpos($drugprophilaxis, '2') !== false AND $age < 15){
			$d=$d+1;
			
		}
	
	
	}

	$sql = "SELECT ROUND(DATEDIFF(CURRENT_DATE, dob)/365) AS age, drug_prophylaxis
	FROM patient p
	LEFT JOIN patient_status ps ON ps.id = p.current_status
	WHERE ps.Name LIKE '%active%'
	and isoniazid_start_date >= DATE_FORMAT(LAST_DAY(CURDATE()-INTERVAL 1 MONTH),'%Y-%m-%01 ')
	and isoniazid_start_date <= DATE_FORMAT(LAST_DAY(CURDATE()-INTERVAL 1 MONTH),'%Y-%m-%d ');
	";
	$query = $this ->db->query($sql);
	$results = $query->result_array();
	foreach($results as $oipatient)
	{
		$age=$oipatient['age'];
		$drugprophilaxis=$oipatient['drug_prophylaxis'];
		//Isoniazid 
			// if has isoniazid
		if(strpos($drugprophilaxis, '3') !== false AND $age >= 15){
			$e=$e+1;
			
		}
		if(strpos($drugprophilaxis, '3') !== false AND $age < 15){
			$f=$f+1;
			
		}
	}

	$sql = "SELECT IF(ROUND(DATEDIFF(CURDATE(), p.dob)/365) >= 15 , COUNT(DISTINCT pv.patient_id), 0) adult_patients,
		       IF(ROUND(DATEDIFF(CURDATE(), p.dob)/365) < 15 , COUNT(DISTINCT pv.patient_id), 0) paed_patients
		FROM patient_visit pv 
		INNER JOIN patient p ON p.patient_number_ccc = pv.patient_id
		INNER JOIN drugcode dc ON dc.id = pv.drug_id
		WHERE pv.dispensing_date >=  DATE_FORMAT(LAST_DAY(CURDATE()-INTERVAL 1 MONTH),'%Y-%m-%01') 
		AND pv.dispensing_date <= DATE_FORMAT(LAST_DAY(CURDATE()-INTERVAL 1 MONTH),'%Y-%m-%d ')
		AND dc.drug LIKE '%fluconazole%'
		AND pv.indication IN ('IND01', 'IND02')";
	$query = $this ->db->query($sql);
	$results = $query->result_array()[0];
	$g = $results['adult_patients'];
	$h = $results['paed_patients'];


		//get the data and convert it to an array that corresponds to the regimens
	$oi_patients[] = array('OI1A'=>$a,'OI1C'=>$b,'OI2A'=>$c,'OI2C'=>$d,'OI4AN'=>$e,'OI4CN'=>$f,'OI5A'=>$g,'OI5C'=>$h);
	echo json_encode($oi_patients);		
}
public function getPeriodRegimenPatients($from, $to) {
	$regimen_column = "r.map";
	$sql = "SELECT count(DISTINCT(p.id)) as patients,rc.name as regimen_category,r.id as regimen_id, r.regimen_desc,r.regimen_code,$regimen_column as regimen 
	FROM patient p
	INNER JOIN regimen r ON r.id=p.current_regimen
	INNER JOIN patient_status ps ON ps.id=p.current_status
	INNER JOIN regimen_category rc ON rc.id=r.category
		        -- INNER JOIN sync_regimen_category rc ON rc.id=r.category
		        WHERE p.date_enrolled<='$to' 
		        AND ps.name LIKE '%active%' 
		        AND r.id=p.current_regimen 
		        AND p.facility_code='$this->facility_code'
		        GROUP BY $regimen_column 
		        ORDER BY r.regimen_code ASC";
		        $query = $this ->db->query($sql);
		        $results = $query->result_array();
		        echo json_encode($results);
		    }

		    public function getNotMappedRegimenPatients($from,$to){
		    	$regimen_column = "r.map";
		    	$sql = "SELECT count(DISTINCT(p.id)) as patients, r.id as regimen_id, r.regimen_desc,r.regimen_code FROM regimen r
		    	INNER JOIN patient p ON p.current_regimen = r.id
		    	INNER JOIN patient_status ps ON ps.id=p.current_status
		    	WHERE p.date_enrolled<='$to' 
		    	AND ps.name LIKE '%active%' 
		    	AND p.facility_code='$this->facility_code' 
		    	AND r.enabled='1'
		    	AND (r.map='' OR r.map='0')
		    	GROUP BY r.id
		    	ORDER BY r.regimen_code ASC";
		    	$query = $this -> db -> query($sql);
		    	$results = $query -> result_array();
		    	echo json_encode($results);
		    }

	public function getCentralDataMaps($start_date, $end_date, $data_type ='') {//Get data when generating reports for central site
		$data = array();
		$facility_code = $this->session->userdata("facility");
		if (isset($facility_code)) {
			//Defines which data to get
			$counter = $this->input->post('counter'); 
			if($data_type=='new_patient'){
				//Males,females, revisit and new patients
				//New , only get ART
				$sql_clients = 'SELECT COUNT(DISTINCT(pv.id)) as total,IF(pv.gender=1,"new_male","new_female") as gender 
				FROM v_patient_visits pv
				INNER JOIN patient_status ps ON ps.id=pv.current_status
				WHERE pv.date_enrolled >= "' . $start_date . '" AND pv.date_enrolled <= "' . $end_date . '"  
				AND pv.dispensing_date>= "' . $start_date . '"
				AND pv.dispensing_date <= "' . $end_date . '"
				AND ps.name LIKE "%active%"
				GROUP BY pv.gender';
				$query = $this -> db -> query($sql_clients);
				$results = $query -> result_array();
				$data['new_patient'] = $results;
			}else if($data_type=='revisit_patient'){
				//revisit
				$sql_clients = "SELECT COUNT(DISTINCT(p.id)) as total,IF(p.gender=1,'revisit_male','revisit_female') as  gender 
				FROM patient p
				LEFT JOIN patient_visit pv ON pv.patient_id = p.patient_number_ccc
				INNER JOIN patient_status ps ON ps.id=p.current_status
				WHERE p.date_enrolled < '$start_date' 
				AND ( pv.dispensing_date BETWEEN '$start_date' AND '$end_date')
				AND ps.name LIKE '%active%'
				GROUP BY p.gender;
				";
				$query = $this -> db -> query($sql_clients);
				$results = $query -> result_array();
				$data['revisit_patient'] = $results;
			}else if($data_type=='revisit_pmtct'){
				//PMTCT clients, New and revisit
				$sql_clients = 'SELECT COUNT(DISTINCT(p.id)) as total
				FROM patient p
				LEFT JOIN regimen r ON r.id = p.current_regimen
				LEFT JOIN regimen_category rc ON rc.id = r.category
				LEFT JOIN patient_status ps ON ps.id=p.current_status
				WHERE (p.date_enrolled <  STR_TO_DATE("' . $start_date . '", "%Y-%m-%d")) 
				AND rc.name = "PMTCT Mother"
				AND ps.name LIKE "%active%"';
				//echo $sql_clients;
				$query = $this -> db -> query($sql_clients);
				$results = $query -> result_array();
				$data['revisit_pmtct'] = $results;
			}else if($data_type=='new_pmtct'){
				//New
				$sql_clients = 'SELECT COUNT(DISTINCT(p.id)) as total FROM patient p
				LEFT JOIN regimen r ON r.id = p.current_regimen
				LEFT JOIN regimen_category rc ON rc.id = r.category
				LEFT JOIN patient_status ps ON ps.id=p.current_status
				WHERE (p.date_enrolled BETWEEN "' . $start_date . '" AND "' . $end_date . '") 
				AND rc.name = "PMTCT Mother"
				AND ps.name LIKE "%active%"';

				$query = $this -> db -> query($sql_clients);
				$results = $query -> result_array();
				$data['new_pmtct'] = $results;
			}else if($data_type=='prophylaxis'){
				//Total No. of Infants receiving ARV prophylaxis for PMTCT
				$sql_clients = 'SELECT COUNT(DISTINCT(p.id)) as total FROM patient p 
				LEFT JOIN regimen r ON r.id = p.current_regimen
				LEFT JOIN regimen_category rc ON rc.id = r.category
				LEFT JOIN patient_status ps ON ps.id=p.current_status
				WHERE rc.name = "PMTCT Child" 
				AND p.date_enrolled<="'.$end_date.'"
				AND ps.name LIKE "%active%" 
				AND p.drug_prophylaxis !=0';
				$query = $this -> db -> query($sql_clients);
				$results = $query -> result_array();
				$data['prophylaxis'] = $results;
			}else if($data_type=='pep'){
				//Totals for PEP Clients ONLY
				$sql_clients = 'SELECT IF(round(datediff(CURDATE(),p.dob)/360)>15,"pep_adult","pep_child") as age,COUNT(DISTINCT(p.id)) as total FROM patient p 
				LEFT JOIN regimen_service_type rs ON rs.id=p.service
				LEFT JOIN patient_status ps ON ps.id=p.current_status
				WHERE rs.name LIKE "%pep%" 
				AND ps.name LIKE "%active%" GROUP BY age';
				;
				$query = $this -> db -> query($sql_clients);
				$results = $query -> result_array();
				$data['pep'] = $results;
			}else if($data_type=='cotrimo_dapsone'){
				//Totals for Patients / Clients (ART plus Non-ART) on Cotrimoxazole/Dapsone prophylaxis
				$sql_clients = 'SELECT IF(round(datediff(CURDATE(),p.dob)/360)>15,"total_adult","total_child") as age,COUNT(DISTINCT(p.id)) as total
				FROM  patient p 
				LEFT JOIN drug_prophylaxis dp ON dp.id = p.drug_prophylaxis
				INNER JOIN patient_status ps ON ps.id=p.current_status
				WHERE (dp.name LIKE "%cotrimo%" OR dp.name LIKE "%dapsone%")
				AND ps.name LIKE "%active%" 
				GROUP BY age
				';
				//echo $sql_clients;
				$query = $this -> db -> query($sql_clients);
				$results = $query -> result_array();
				$data['cotrimo_dapsone'] = $results;
			}else if($data_type=='diflucan'){
				//Totals for Patients / Clients on Diflucan (For Diflucan Donation Program ONLY):
				$sql_clients ='SELECT IF(round(datediff(CURDATE(),p.dob)/360)>15,"diflucan_adult","diflucan_child") as age,COUNT(DISTINCT(p.id)) as total
				FROM  patient p 
				LEFT JOIN drug_prophylaxis dp ON dp.id = p.drug_prophylaxis
				INNER JOIN patient_status ps ON ps.id=p.current_status
				WHERE (dp.name LIKE "%flucona%")
				AND ps.name LIKE "%active%" 
				GROUP BY age';
				$query = $this -> db -> query($sql_clients);
				$results = $query -> result_array();
				$data['diflucan'] = $results;
			}else if($data_type=='new_cm_oc'){
				//New and revisit CM/OM
				//New
				$sql_clients = "SELECT IF(p.other_illnesses LIKE '%cryptococcal%','new_cm',
				IF(oi.name LIKE '%oesophageal%','new_oc','')) as OI, COUNT(DISTINCT(p.patient_number_ccc)) as total 
				FROM patient p
				LEFT JOIN patient_visit pv ON pv.patient_id = p.patient_number_ccc
				LEFT JOIN opportunistic_infection oi ON oi.indication = pv.indication
				INNER JOIN patient_status ps ON ps.id=p.current_status
				WHERE (p.other_illnesses LIKE '%cryptococcal%' OR oi.name LIKE '%oesophageal%')
				AND p.date_enrolled BETWEEN '$start_date' AND '$end_date'
				AND ps.name LIKE '%active%'
				GROUP BY OI " ;	
				$query = $this -> db -> query($sql_clients);
				$results = $query -> result_array();
				$data['new_cm_oc'] = $results;
			}else if($data_type=='revisit_cm_oc'){
				//Revisit
				$sql_clients="SELECT IF(temp2.other_illnesses LIKE '%cryptococcal%','revisit_cm','revisit_oc') as OI,COUNT(temp2.ccc_number) as total
				FROM (SELECT DISTINCT(pv.patient_id) as ccc_number,oi.name as opportunistic_infection FROM patient_visit pv
				INNER JOIN  opportunistic_infection oi ON oi.indication = pv.indication
				) as temp1
				INNER JOIN (
				SELECT DISTINCT(p.patient_number_ccc) as ccc_number,other_illnesses FROM patient p
				INNER JOIN patient_status ps ON ps.id = p.current_status
				WHERE p.date_enrolled < '$start_date'
				AND ps.name LIKE '%active%'
				) as temp2 ON temp2.ccc_number = temp1.ccc_number
				WHERE temp2.other_illnesses LIKE '%cryptococcal%' OR temp1.opportunistic_infection LIKE '%oesophageal%';";			
				$query = $this -> db -> query($sql_clients);
				$results = $query -> result_array();
				$data['revisit_cm_oc'] = $results;
			}
			echo json_encode($data);
		}
	}

	public function expectedReports($facility_code) {//Get number of total expected reports
		if($facility_code != ''){
			$sql = "SELECT COUNT(DISTINCT code) total
					FROM sync_facility 
					WHERE parent_id IN (
						SELECT id 
						FROM sync_facility 
						WHERE code = '$facility_code' 
						AND category = 'central') 
					AND category = 'satellite'";
			$query = $this -> db -> query($sql);
			$results = $query -> result_array();
			if($results){
				return $results[0]['total'];
			}else{
				return 0;
			}
		}else{
			return 0;
		}	
	}
	public function actualReports($facility_code, $period_begin, $type){
		if($facility_code!=''){
			$filter = "";
			if($type == "cdrr"){
				$filter = "F-CDRR_units";
			}else if($type == "maps"){
				$filter = "F-MAPS";
			} 
			$sql = "SELECT COUNT(t.id) as total 
					FROM $type t 
					INNER JOIN sync_facility sf ON sf.id = t.facility_id 
					WHERE t.status = 'approved' 
					AND t.code = '$filter'
					AND sf.category = 'satellite'
					AND t.period_begin = '$period_begin'
					AND sf.parent_id IN (SELECT id FROM sync_facility WHERE code = '$facility_code' AND category = 'central')";
			$query = $this -> db -> query($sql);
			$results = $query -> result_array();
			if($results){
				return $results[0]['total'];
			}else{
				return 0;
			}
		}else{
			return 0;
		}
	}
	
	public function base_params($data) {
		$data['title'] = "Order Reporting";
		$data['link'] = "order_management";
		$this -> load -> view('template', $data);
	}

	public function getActualCode($code, $type) {
		if ($type == "cdrr") {
			if ($code == 0) {
				$code = "F-CDRR_units";
			} else if ($code == 1) {
				$code = "F-CDRR_packs";
			} else {
				$code = "D-CDRR";
			}
		} else if ($type == "maps") {
			if ($code == 0) {
				$code = "F-MAPS";
			} else {
				$code = "D-MAPS";
			}
		}
		return $code;
	}

	public function getDummyCode($code, $order_type) {
		if ($code == "DCDRR") {
			$code = 3;
		} else {
			$code = $order_type;
		}
		return $code;
	}

	public function map_process() {
		//Clear all regimen mappings
		$sql = "update regimen SET map='0'";
		$this -> db -> query($sql);

		//Map Regimens
		$regimens = Regimen::getRegimens();
		foreach ($regimens as $regimen) {
			$regimen_id = $regimen['id'];
			$code = $regimen['Regimen_Code'];
			$name = $regimen['Regimen_Desc'];
			$map_id = $this -> getMainRegimen($code, $name);
			if ($map_id != null) {
				$new_array = array('map' => $map_id);
				$this -> db -> where('id', $regimen_id);
				$this -> db -> update('regimen', $new_array);
				unset($new_array);
			}
		}
	}

	public function satellites_reported() {
		$start_date = date('Y-m-01', strtotime("-1 month"));
		$facility_code = $this -> session -> userdata("facility");
		$central_site = Sync_Facility::getId($facility_code, $this->facility_type);
		$central_site = $central_site['id'];
		$notification = "";

		$sql = "SELECT sf.name as facility_name,sf.code as facility_code,IF(c.id,'reported','not reported') as status
		FROM sync_facility sf
		LEFT JOIN cdrr c ON c.facility_id=sf.id AND c.period_begin='$start_date' 
		WHERE sf.parent_id='$central_site'
		AND sf.category LIKE '%satellite%'
		AND sf.name NOT LIKE '%dispensing%'
		GROUP BY sf.id";
		$query = $this -> db -> query($sql);

		$satellites = $query -> result_array();
		

		$notification .= "<table class='dataTables table table-bordered table-hover'>";
		$notification .= "<thead><tr><th>Name</th><th>Code</th><th>Status</th></tr></thead><tbody>";
		if ($satellites) {
			foreach ($satellites as $satellite) {
				if ($satellite['status'] == "reported") {
					$satellite['status'] = "<div class='alert-success'>" . $satellite['status'] . "</div>";
				} else {
					$satellite['status'] = "<div class='alert-danger'>" . $satellite['status'] . "</div>";
				}
				$notification .= "<tr><td>" . $satellite['facility_name'] . "</td><td>" . $satellite['facility_code'] . "</td><td>" . $satellite['status'] . "</td></tr>";
			}
		}
		$notification .= "</tbody></table>";
		$data['notification_table'] = $notification;
		$data['content_view'] = "satellite_reported_v";
		$data['page_title'] = "my Orders";
		$data['banner_text'] = "Satellites Reported";
		$this -> base_params($data);
	}

	public function getResupply($drug_id = "", $period_begin = "", $facility_id = "") {
		$first = date('Y-m-01', strtotime($period_begin . "- 1 month"));
		$second = date('Y-m-01', strtotime($period_begin . "- 2 month"));
		$third = date('Y-m-01', strtotime($period_begin . "- 3 month"));
		$amc = 0;

		$sql = "SELECT SUM(ci.dispensed_packs) as dispensed_packs,SUM(ci.dispensed_units) as dispensed_units,SUM(ci.aggr_consumed) as aggr_consumed,SUM(ci.aggr_on_hand) as aggr_on_hand,SUM(ci.count) as count,c.code
		FROM cdrr_item ci 
		INNER JOIN (SELECT max(id) as id,period_begin,code
		FROM cdrr 
		WHERE (period_begin='$first' OR period_begin='$second' OR period_begin='$third')
		AND facility_id='$facility_id'
		AND status NOT LIKE '%prepared%'
		AND status NOT LIKE '%deleted%'
		GROUP BY period_begin) as c ON ci.cdrr_id=c.id
		AND ci.drug_id='$drug_id'
		GROUP BY ci.drug_id";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if ($results) {
			foreach ($results as $result) {
				$code = trim($result['code']);
				if ($code == "D-CDRR") {
					$amc = ($result['dispensed_packs'] + $result['aggr_consumed']) - ($result['aggr_on_hand'] + $result['count']);
				} else if ($code == "F-CDRR_packs") {
					$amc = $result['dispensed_packs'] - $result['count'];
				} else if ($code == "F-CDRR_units") {
					$amc = $result['dispensed_units'] - $result['count'];
				}
			}
		}
		return $amc;
	}

	public function getItems() {
    	//Default row values
		$row = array(
			'beginning_balance' => 0,
			'received_from' => 0,
			'dispensed_to_patients' => 0,
			'losses' => 0,
			'adjustments' => 0,
			'adjustments_neg' => 0,
			'physical_stock' => 0,
			'expiry_qty' => 0,
			'expiry_month' => "--",
			'stock_out' => 0,
			'resupply' => 0
		);

        //Set parameters
		$param=array(
			"drug_id" => $this->input->post("drug_id"),
			"period_begin" => $this->input->post("period_begin"),
			"facility_id" => $this->input->post("facility_id"),
			"code" => $this->input->post("code"),
			"stores" => $this->input->post("stores")
		);

		$code=$param['code'];
		$facility_id=$param['facility_id'];
		$period_begin=date('Y-m-01',strtotime($param['period_begin']));
		$period_end=date('Y-m-t',strtotime($param['period_begin'])); 
		$stores=$param['stores'];
		$stores=implode(",",$stores);
		$stores =str_replace("multiselect-all,","",$stores);
		$drug_id=$param['drug_id'];

        //get packsize
		$drug = Sync_Drug::getPackSize($drug_id);
		$pack_size = $drug['packsize'];

		//check whether a satellite,standalone or central site
		$facility_code = $this -> session -> userdata("facility");


		$row['beginning_balance']=$this->getBeginningBalance($param);
		$row['pack_size']=intval($pack_size);

		$row=$this->getOtherTransactions($param,$row);

		
		if($row['stock_out']==null){
			$row['stock_out']=0;
		}

		if ($this->facility_type > 1) {
			//central site
			if ($code == "D-CDRR") {
				//reported_consumed & reported_stock_on_hand
				$reported_consumed = 0;
				$reported_count = 0;
				$satellites = Sync_Facility::getSatellites($facility_id);
				foreach ($satellites as $satellite) {
					$satellite_site = $satellite['id'];
					$sql = "SELECT ci.drug_id,SUM(ci.dispensed_units) as consumed,SUM(ci.count) as phy_count
					FROM cdrr c
					LEFT JOIN cdrr_item ci ON ci.cdrr_id=c.id
					WHERE c.period_begin='$period_begin' 
					AND c.period_end='$period_end'
					AND ci.drug_id='$drug_id'
					AND c.status LIKE '%approved%'
					AND c.facility_id='$satellite_site'
					GROUP BY ci.drug_id";
					$query = $this -> db -> query($sql);
					$results = $query -> result_array();
					if (!$results) {
						//if satellite did not report use previous period
						$start_date = date('Y-m-01', strtotime($period_begin . "-1 month"));
						$end_date = date('Y-m-t', strtotime($period_end . "-1 month"));
						$sql = "SELECT ci.drug_id,SUM(ci.dispensed_units) as consumed,SUM(ci.count) as phy_count
						FROM cdrr c
						LEFT JOIN cdrr_item ci ON ci.cdrr_id=c.id
						WHERE c.period_begin='$start_date' 
						AND c.period_end='$end_date'
						AND ci.drug_id='$drug_id'
						AND c.facility_id='$satellite_site'
						GROUP BY ci.drug_id";
						$query = $this -> db -> query($sql);
						$results = $query -> result_array();
					}
					if ($results) {
						$reported_consumed += @$results[0]['consumed'];
						$reported_count += @$results[0]['phy_count'];
					}
				}
				//append to json array
				$row['reported_consumed'] = $reported_consumed;
				$row['reported_physical_stock'] = $reported_count;
				
                //get issued to satellites as dispensed_to patients
				$sql="SELECT SUM(dsm.quantity_out) AS total 
				FROM drug_stock_movement dsm
				LEFT JOIN drugcode d ON d.id=dsm.drug
				LEFT JOIN sync_drug sd ON d.map=sd.id
				LEFT JOIN transaction_type t ON t.id=dsm.transaction_type
				WHERE dsm.transaction_date 
				BETWEEN  '$period_begin' 
				AND  '$period_end' 
				AND sd.id =  '$drug_id'
				AND t.name LIKE '%issue%'
				AND dsm.ccc_store_sp IN($stores)";
				$query = $this -> db -> query($sql);
				$results = $query -> result_array();
				$row['dispensed_to_patients'] = 0;
				if ($results) {
					if($results[0]['total'] !=null){
						$row['dispensed_to_patients']=$results[0]['total'];
					}
				}
			} 
		}

		//Convert all items from units to packs
        $exempted_columns = array('expiry_month','beginning_balance','pack_size');
		foreach ($row as $i => $v) {
			if (!in_array($i,$exempted_columns)) {
				$row[$i] = round(@$v / @$pack_size);
			}
		}

		// Changes made on DCDRR
		if ($code == "D-CDRR") 
		{	
			foreach ($row as $i => $v) {
				$exempted_columns = array('expiry_month','beginning_balance','reported_consumed','reported_physical_stock', 'pack_size');
				if (!in_array($i,$exempted_columns)) {
					$row[$i] = @$v;
				}
			}

			//Get Physical Count
			$row['physical_stock'] = $row['beginning_balance'] + $row['received_from'] - $row['dispensed_to_patients'] - $row['losses'] + $row['adjustments'] - $row['adjustments_neg'];
		    //Get Resupply
			$row['resupply'] = ($row['reported_consumed'] * 3) - $row['physical_stock'];
		}
		else
		{
			$row['physical_stock'] = $row['beginning_balance'] + $row['received_from'] - $row['dispensed_to_patients'] - $row['losses'] + $row['adjustments'] - $row['adjustments_neg'];
			$row['resupply'] = ($row['dispensed_to_patients'] * 2) - $row['physical_stock'];
		}
		
		if($code == "F-CDRR_packs"){
			$row['dispensed_packs']=0;
			if($row['dispensed_to_patients'] >0){
				$row['dispensed_packs']=round(@$row['dispensed_to_patients'] / @$pack_size);
			}
		}

		//Fix for physical count
		/*if($row['physical_stock'] < 0){
			$row['physical_stock'] = 0;
		}
		//Fix for resupply
		if($row['resupply'] < 0){
			$row['resupply'] = 0;
		}*/
		echo json_encode($row);
	}

	// public function getBeginningBalance($param=null,$month=0){
	public function getBeginningBalance($param=array(),$month=0){
		// $param = array('period_begin'=>'2017-05-01','drug_id'=>7,'facility_id'=>2408);
		//we are checking for the physical count of this drug month before reporting period
		$param['period_begin']=date('Y-m-d',strtotime($param['period_begin']."-$month month"));
		$balance=Cdrr_Item::getLastPhysicalStock($param['period_begin'], $param['drug_id'], $param['facility_id'], $param['code']);

		if(!$balance && $month<3){

			$date = date('Y-m-d',strtotime($param['period_begin']."-3 month"));
			$sql  = " ( SELECT  count FROM cdrr_item ci, cdrr c WHERE drug_id = ".$param['drug_id']." and ci.cdrr_id = c.id 
			and c.facility_id = ".$param['facility_id']." and period_begin >= '$date' and c.code = '".$param['code']."' and c.status != 'deleted' 
			and c.status != 'prepared' ORDER BY `cdrr_id` desc limit 1) union (select 0 count) limit 1;";

			$query = $this -> db -> query($sql);
			$balance= $query -> result_array()[0]['count'];
		}
		
		if($balance < 0){
			$balance = 0;
		}

		return intval($balance);

	}

	public function getOtherTransactions($param=array(),$row=array()){
		$period_begin=date('Y-m-01',strtotime($param['period_begin']));
		$period_end=date('Y-m-t',strtotime($param['period_begin']));
		$stores=$param['stores'];
		$stores=implode(",",$stores);
		$stores =str_replace("multiselect-all,","",$stores);
		$drug_id=$param['drug_id'];

        //execute query to get all other transactions
		$sql = "SELECT trans.name, trans.id, trans.effect, dsm.in_total, dsm.out_total 
		FROM (SELECT id, name, effect 
		FROM transaction_type 
		WHERE name LIKE  '%received%' 
		OR name LIKE  '%dispense%' 
		OR name LIKE  '%loss%' 
		OR name LIKE  '%adjustment%' 
		) AS trans 
		LEFT JOIN (SELECT dsm.transaction_type, SUM( dsm.quantity ) AS in_total, SUM( dsm.quantity_out ) AS out_total 
		FROM drug_stock_movement dsm
		LEFT JOIN drugcode d ON d.id=dsm.drug
		LEFT JOIN sync_drug sd ON d.map=sd.id
		WHERE dsm.transaction_date 
		BETWEEN  '$period_begin' 
		AND  '$period_end' 
		AND sd.id =  '$drug_id'
		AND dsm.ccc_store_sp IN($stores)
		GROUP BY transaction_type) AS dsm ON trans.id = dsm.transaction_type 
		GROUP BY trans.name";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		$total = 0;
		if ($results) {
			foreach ($results as $result) {
				$effect = $result['effect'];
				$trans_name = strtolower(str_replace(array(" ", "(-)", "(+)", "/"), array("_", "_", "plus", "_"), $result['name']));
				if ($effect == 1) {
					if ($result['in_total'] != null) {
						$total = (int)$result['in_total'];
					} else {
						$total = 0;
					}
				} else {
					if ($result['out_total'] != null) {
						$total = (int)$result['out_total'];
					} else {
						$total = 0;
					}
				}
				$row[$trans_name] = $total;
			}
		}
		
		$row['losses'] = @$row['losses_'];
		$row['adjustments'] = @$row['adjustment_plus'];
		$row['adjustments_neg'] = @$row['adjustment__'];

		unset($row['losses_']);
		unset($row['adjustment_plus']);
		unset($row['adjustment__']);

		//Drugs with less than 6 months to expiry
		$row['expiry_qty'] = 0;
		$row['expiry_month'] = "-";

		$sql = "SELECT SUM(dsb.balance) AS expiry_qty,DATE_FORMAT(MIN(dsb.expiry_date),'%M-%Y') as expiry_month
		FROM drugcode d
		LEFT JOIN sync_drug sd ON sd.id=d.map
		LEFT JOIN drug_unit u ON d.unit = u.id
		LEFT JOIN drug_stock_balance dsb ON d.id = dsb.drug_id
		WHERE DATEDIFF( dsb.expiry_date,'$period_end') <=180
		AND DATEDIFF( dsb.expiry_date,'$period_end') >=0
		AND d.enabled =1
		AND sd.id='$drug_id'
		AND dsb.ccc_store_sp IN ($stores)
		AND dsb.balance >0
		GROUP BY d.drug";

		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if ($results) {
			$row['expiry_qty'] = $results[0]['expiry_qty'];
			$row['expiry_month'] = $results[0]['expiry_month'];
		}

		//Days out of stock this month
		$sql = "SELECT DATEDIFF('$period_end',MAX(dsm.transaction_date)) AS last_update
		FROM drug_stock_movement dsm
		LEFT JOIN drugcode d ON d.id = dsm.drug
		LEFT JOIN sync_drug sd ON sd.id = d.map
		WHERE dsm.transaction_date
		BETWEEN  '$period_begin'
		AND  '$period_end'
		AND dsm.ccc_store_sp IN($stores)
		AND sd.id =  '$drug_id'
		AND dsm.machine_code='0'";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		$row['stock_out'] = 0;
		if ($results) {
			if($results[0]['last_update'] !=null){
				$row['stock_out'] = $results[0]['last_update'];
			}
		}
		return $row;
	}
	
	public function getExpectedActualReport(){
		$data =  array();
		$facility_code = $this ->input ->post("facility_code");
		$period_begin = $this ->input ->post("period_begin");
		$type = $this ->input ->post("type");
		$data["expected"] = $this ->expectedReports($facility_code);
		$data["actual"] =  $this ->actualReports($facility_code,$period_begin,$type);
		echo json_encode($data);
	}

	public function upload_dhis($order_type,$order_id){
		// Creation of MAPS dhis message
		if ($order_type == 'maps') {
			# code...
			$results = Maps::getMap($order_id)[0];
				$code = '';
				$query = $this->db->get_where('sync_facility', array(
				'id' =>  $results['facility_id']
			));

			$dhis_org = $query->result_array()[0]['dhiscode'];

			switch ($this->facility_type) {
				case 0:
				$code = 'MoH 729a';

				case  1:
				$code = 'MoH 729a';
				
				default:
				$code = 'MoH 729B';
				break;
			}
			$results['item'] = Maps_Item::getDhisItem($order_id,$code);
			$dataValues = array();
			foreach ($results['item'] as $key => $item) {
				if ($item['dhis_code'] ==NULL){continue;}
				if ($item['total']!==null){
					array_push($dataValues, array('dataElement' =>$item['dhis_code'],'categoryOptionCombo' =>'NhSoXUMPK2K', 'value' =>$item['total']));
				}
			}
			$dhismessage = array(
				'dataSet'=> 'mgaZyW3Xzyf',
				'completeDate' => date('Y-m-d',strtotime($results['updated'])),
				'period' => date('Ym',strtotime($results['period_begin'])),
				'orgUnit'=> $dhis_org,
				// 'attributeOptionCombo'=> "NhSoXUMPK2K",
				'dataValues' => $dataValues
			);
			$dhis_auth = $this->session->userdata('dhis_user').':'.$this->session->userdata('dhis_pass');
			$resource = 'api/27/dataValueSets?dataElementIdScheme=UID&orgUnitIdScheme=UID&importStrategy=CREATE_AND_UPDATE&dryRun=false&datasetAllowsPeriods=true&strictOrganisationUnits=true&strictPeriods=true&skipExistingCheck=false';
			$reports = $this->sendRequest($resource,'POST',$dhismessage,$dhis_auth);
			// echo json_encode($dhismessage, JSON_PRETTY_PRINT).'<br />';echo $resource.'<br/>';var_dump($reports);die;
		}
		else if ($order_type = 'cdrr'){
			$results = Cdrr::getCdrr($order_id)[0];
			$code = '';

			switch ($this->facility_type) {
				case 0:
				$code = 'MoH 729a';

				case  1:
				$code = 'MoH 730a';
				
				default:
				$code = 'MoH 730B';
				break;
			}

			$results['item'] = Cdrr_Item::getDhisItem($order_id,$code);
			$dhis_org = $this->session->userdata('dhis_org');


			$query = $this->db->get_where('sync_facility', array(
				'id' =>  $results['facility_id']
			));

			$dhis_org = $query->result_array()[0]['dhiscode'];

			// facility_id
			$dataValues = array();
			foreach ($results['item'] as $key => $item) {
				if ($item['dhis_code'] ==NULL){continue;}
				if ($item['balance']!==null){
					array_push($dataValues, array('dataElement' =>$item['dhis_code'],'categoryOptionCombo' =>'jWmWT3Nvq1P', 'value' =>$item['balance']));
				}
				if ($item['received']!==null){
					array_push($dataValues, array('dataElement' =>$item['dhis_code'],'categoryOptionCombo' =>'XmKrTgYAPoi', 'value' =>$item['received']));
				}
				if ($item['dispensed_packs']!==null){
					array_push($dataValues, array('dataElement' =>$item['dhis_code'],'categoryOptionCombo' =>'yP6vevc91WZ', 'value' =>$item['dispensed_packs']));
				}
				if ($item['losses']!==null){
					array_push($dataValues, array('dataElement' =>$item['dhis_code'],'categoryOptionCombo' =>'b11dZBeBzRE', 'value' =>$item['losses']));
				}
				if ($item['adjustments']!==null){
					array_push($dataValues, array('dataElement' =>$item['dhis_code'],'categoryOptionCombo' =>'LeyPc0LYjLg', 'value' =>$item['adjustments']));
				}
				if ($item['adjustments_neg']!==null){
					array_push($dataValues, array('dataElement' =>$item['dhis_code'],'categoryOptionCombo' =>'O9yaDegYywr', 'value' =>$item['adjustments_neg']));
				}
				if ($item['count']!==null){
					array_push($dataValues, array('dataElement' =>$item['dhis_code'],'categoryOptionCombo' =>'GvjV9gy3OOc', 'value' =>$item['count']));
				}
				if ($item['expiry_quant']!==null){
					array_push($dataValues, array('dataElement' =>$item['dhis_code'],'categoryOptionCombo' =>'r9aTy1gRXUC', 'value' =>$item['expiry_quant']));
				}
				if ($item['expiry_date']!==null){
					array_push($dataValues, array('dataElement' =>$item['dhis_code'],'categoryOptionCombo' =>'hOMc7AVsdRk', 'value' =>$item['expiry_date']));
				}
				if ($item['out_of_stock']!==null){
					array_push($dataValues, array('dataElement' =>$item['dhis_code'],'categoryOptionCombo' =>'aDZLiIaG8gC', 'value' =>$item['out_of_stock']));
				}
				if ($item['resupply']!==null){
					array_push($dataValues, array('dataElement' =>$item['dhis_code'],'categoryOptionCombo' =>'R4B7KIT1mch', 'value' =>$item['resupply']));
				}
				if ($item['count']!==null){
					array_push($dataValues, array('dataElement' =>$item['dhis_code'],'categoryOptionCombo' =>'NhSoXUMPK2K', 'value' =>$item['count']));
				}

			}
			$dhismessage = array(
				'dataSet'=> 'OSulH5zPHPw',
				'completeDate' => date('Y-m-d',strtotime($results['updated'])),
				'period' => date('Ym',strtotime($results['period_begin'])),
				'orgUnit'=> $dhis_org,
				'attributeOptionCombo'=> "NhSoXUMPK2K",
				'dataValues' => $dataValues
			);
			$dhis_auth = $this->session->userdata('dhis_user').':'.$this->session->userdata('dhis_pass');
			// $dhis_auth = 'Thikalevel5ccc:Judy-2018';
			$resource = 'api/27/dataValueSets?dataElementIdScheme=UID&orgUnitIdScheme=UID&importStrategy=CREATE_AND_UPDATE&dryRun=false&datasetAllowsPeriods=true&strictOrganisationUnits=true&strictPeriods=true&skipExistingCheck=false';
			$result = $this->sendRequest($resource,'POST',$dhismessage,$dhis_auth);
			// echo json_encode($dhismessage, JSON_PRETTY_PRINT).'<br />';echo $resource.'<br/>';var_dump($result);die;

		}
	}
	public function get_dhis($ds = null, $period_filter = null, $code = null){
		//Default messages
		$response[$ds] = array('status' => false, 'message' => '<div class="alert alert-error"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Error!</strong> No '.strtoupper($ds).' Data was Retrieved!</div>');
		$dataset_url = "api/dataValueSets";
		$dataset =  $this->config->config['dhiscode'][$ds.'_code']; //pick dataset code from config
		//Set dates for report retrieving
		$i = 1;
		$period_dates = array();
		while ($i <= $period_filter) {
			$period_dates[] = date('Y-m-01', strtotime(date('Y-m')." -".$i." month"));
			$i++;
		}
		$dhis_auth = $this->session->userdata('dhis_user').':'.$this->session->userdata('dhis_pass');
		$dhiscode = $this->session->userdata('dhis_id'); //DHIS USER ID
		//Loop through user dhis facilities and get cdrr/maps
		$dhis_orgs = $this->session->userdata('dhis_orgs');
		foreach ($period_dates as $period_date) {
			foreach ($dhis_orgs as $dhis_org) {
				$period = date('Ym', strtotime($period_date));
				$resource = $dataset_url."?dataSet=$dataset&period=$period&orgUnit=".$dhis_org; // get cdrr
				$report = json_decode($this->sendRequest($resource, 'GET', null, $dhis_auth));

				if(!empty($report->dataValues)){
					$start_date = $period_date;
					$end_date =  date('Y-m-t', strtotime($period_date));
					//cdrr
					if(in_array($ds, array('fcdrr')) && isset($report->dataValues)){
						$facility_id = $this->get_sync_facility_id($dhis_org, $code);
						//Add cdrr
						$cdrr = array(
							'status' => 'approved',
							'created' => str_replace('T', ' ', $report->dataValues[0]->created),
							'updated' => str_replace('T', ' ', $report->dataValues[0]->lastUpdated),
							'code' => $code,
							'period_begin' => $start_date,
							'period_end' => $end_date,
							'comments' => '',
							'reports_expected' => $this -> expectedReports($this->facility_code),
							'reports_actual' =>$this -> actualReports($this->facility_code,$start_date,'cdrr'),
							'services' => '',
							'sponsors' => '',
							'non_arv' => 0,
							'delivery_note' => '',
							'order_id' => 0,
							'facility_id' => $facility_id,
							'issynched' => 'Y');
						//Check if cdrr exists
						$row = $this->db->select('id')->get_where('cdrr', array(
								'facility_id' => $facility_id, 
								'period_begin' => $start_date,
								'code' => $code))->row_array();
						if(count($row)>0){
							$cdrr_id = $row['id'];
							$this->db->where('id', $cdrr_id);
							$this->db->update('cdrr', $cdrr);
						}else{
							$this->db->insert('cdrr', $cdrr);		
							$cdrr_id = $this->db->insert_id();
						}

						//Build formatted cdrr_item object
						$cdrr_item = array();
						foreach ($report->dataValues as $key => $value) {
							$drug_id = $this->dhisLookup($value->dataElement, 'drug');
							$column = $this->dhisLookup($value->categoryOptionCombo);
							$cdrr_item[$cdrr_id][$drug_id][$column] = $value->value;
						}

						//Add cdrr_item
						foreach ($cdrr_item as $cdrr_id => $items) {
							foreach ($items as $drug_id => $cdrr_item_tmp) {
								//Add cdrr_id and drug_id
								$cdrr_item_tmp['cdrr_id'] = $cdrr_id;
								$cdrr_item_tmp['drug_id'] = $drug_id;
								//Check if value exists
								$row = $this->db->select('id')->get_where('cdrr_item', array(
								'cdrr_id' => $cdrr_id, 
								'drug_id' => $drug_id))->row_array();
								if(!empty($row)){
									$cdrr_item_id = $row['id'];
									$this->db->where('id', $cdrr_item_id);
									$this->db->update('cdrr_item', $cdrr_item_tmp);
								}else{
									$this->db->insert('cdrr_item', $cdrr_item_tmp);		
								}
							}	
						}

						//Add cdrr_log
						$last_index = (sizeof($report->dataValues) - 1);
						$logs = array('prepared' => $report->dataValues[$last_index]->created, 'approved' => $report->dataValues[$last_index]->lastUpdated);
						foreach ($logs as $log => $timeline) {
							//cdrr_log Object 
							$cdrr_log_tmp = array(
								'description' => $log,
								'created' => $timeline,
								'user_id' => $this->db->select('user_id')->get_where('sync_user', array('username' => $report->dataValues[$last_index]->storedBy))->row_array()['user_id'],
								'cdrr_id' => $cdrr_id
							);

							//Check if value exists
							$row = $this->db->select('id')->get_where('cdrr_log', array(
							'cdrr_id' => $cdrr_id, 
							'description' => $log))->row_array();
							if(!empty($row)){
								$cdrr_log_id = $row['id'];
								$this->db->where('id', $cdrr_log_id);
								$this->db->update('cdrr_log', $cdrr_log_tmp);
							}else{
								$this->db->insert('cdrr_log', $cdrr_log_tmp);		
							}
						}

						//Set success response
						$response[$ds] = array('status' => true, 'message' => '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Success!</strong> '.strtoupper($ds).' Reports were retrieved successfully!</div>');
					}	
					//maps
					else if(in_array($ds, array('fmaps')) && isset($report->dataValues)){
						$facility_id = $this->get_sync_facility_id($dhis_org, $code);
						$maps = array(
							'status' => 'approved',
							'created' => str_replace('T', ' ', $report->dataValues[0]->created),
							'updated' => str_replace('T', ' ', $report->dataValues[0]->lastUpdated),
							'code'  => $code,
							'period_begin' => $start_date,
							'period_end' => $end_date,
							'reports_expected' => $this -> expectedReports($this->facility_code),
							'reports_actual' =>$this -> actualReports($this->facility_code,$start_date,'maps'),
							'art_adult'  => '',
							'art_child'  => '',
							'new_male'  => '',
							'revisit_male'  => '',
							'new_female'  => '',
							'revisit_female'  => '',
							'new_pmtct'  => '',
							'revisit_pmtct'  => '',
							'total_infant'  => '',
							'pep_adult'  => '',
							'pep_child'  => '',
							'total_adult'  => '',
							'total_child'  => '',
							'diflucan_adult'  => '',
							'diflucan_child'  => '',
							'new_cm'  => '',
							'revisit_cm'  => '',
							'new_oc'  => '',
							'revisit_oc'  => '',
							'comments'  => '',
							'report_id'  => '',
							'facility_id' => $facility_id,
							'issynched'  => 'Y'
						);

						//Check if maps exists
						$row = $this->db->select('id')->get_where('maps', array(
								'facility_id' => $facility_id, 
								'period_begin' => $start_date,
								'code' => 'F-MAPS'))->row_array();
						if(!empty($row)){
							$maps_id = $row['id'];
							$this->db->where('id', $maps_id);
							$this->db->update('maps', $maps);
						}else{
							$this->db->insert('maps', $maps);		
							$maps_id = $this->db->insert_id();
						}

						//Build formatted maps_item object
						$maps_item = array();
						foreach ($report->dataValues as $key => $value) {
							$regimen_id = $this->dhisLookup($value->dataElement, 'regimen');
							$column = $this->dhisLookup($value->categoryOptionCombo);
							$maps_item[$maps_id][$regimen_id][$column] = $value->value;
						}

						//Add maps_item
						foreach ($maps_item as $maps_id => $items) {
							foreach ($items as $regimen_id => $maps_item_tmp) {
								//Add maps_id and regimen_id
								$maps_item_tmp['maps_id'] = $maps_id;
								$maps_item_tmp['regimen_id'] = $regimen_id;
								//Check if value exists
								$row = $this->db->select('id')->get_where('maps_item', array(
								'maps_id' => $maps_id, 
								'regimen_id' => $regimen_id))->row_array();
								if(!empty($row)){
									$maps_item_id = $row['id'];
									$this->db->where('id', $maps_item_id);
									$this->db->update('maps_item', $maps_item_tmp);
								}else{
									$this->db->insert('maps_item', $maps_item_tmp);		
								}
							}	
						}

						//Add maps_log
						$last_index = (sizeof($report->dataValues) - 1);
						$logs = array('prepared' => $report->dataValues[$last_index]->created, 'approved' => $report->dataValues[$last_index]->lastUpdated);
						foreach ($logs as $log => $timeline) {
							//maps_log Object 
							$maps_log_tmp = array(
								'description' => $log,
								'created' => $timeline,
								'user_id' => $this->db->select('user_id')->get_where('sync_user', array('username' => $report->dataValues[$last_index]->storedBy))->row_array()['user_id'],
								'maps_id' => $maps_id
							);

							//Check if value exists
							$row = $this->db->select('id')->get_where('maps_log', array(
							'maps_id' => $maps_id, 
							'description' => $log))->row_array();
							if(!empty($row)){
								$maps_log_id = $row['id'];
								$this->db->where('id', $maps_log_id);
								$this->db->update('maps_log', $maps_log_tmp);
							}else{
								$this->db->insert('maps_log', $maps_log_tmp);		
							}
						}

						//Set success response
						$response[$ds] = array('status' => true, 'message' => '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Success!</strong> '.strtoupper($ds).' Reports were retrieved successfully!</div>');
					}
				}
			}
		}

		return $response;
	}

	public function get_dhis_central($ds = null, $period_filter = null, $code = null){
		//Default messages
		$response[$ds] = array(
			'status' => false, 
			'message' => '<div class="alert alert-error">
							<button type="button" class="close" data-dismiss="alert">&times;</button>
							<strong>Error!</strong> No '.strtoupper($ds).' Data was Retrieved!</div>');
		$dataset_url = "api/dataValueSets";
		$central_sites_url = "api/organisationUnitGroupSets/tjVYz9cY7I3/organisationUnitGroups?fields=:id&paging=false";
		$facility_dhis_url = "api/organisationUnits.json?level=5&paging=false&fields=:all&filter=code:eq:".$this->facility_code;
		$dataset =  $this->config->config['dhiscode'][$ds.'_code'];
		$dhis_auth = $this->session->userdata('dhis_user').':'.$this->session->userdata('dhis_pass');
		$dhiscode = $this->session->userdata('dhis_id'); //DHIS user_id
		//Get ordering sites dhiscode
		$dhis_central_ids = array();
		$centralsite_orgs = json_decode($this->sendRequest($central_sites_url, 'GET', null, $dhis_auth), TRUE)['organisationUnitGroups'];
		$facility_org_ids = array();
		$facility_org_groups = json_decode($this->sendRequest($facility_dhis_url, 'GET', null, $dhis_auth), TRUE)['organisationUnits'][0];
		foreach (array_values($centralsite_orgs) as $centralsite) {
			$dhis_central_ids[] = $centralsite['id'];
		}
		foreach (array_values($facility_org_groups['organisationUnitGroups']) as $facility_org) {
			$facility_org_ids[] = $facility_org['id'];
		}
		$central_grp_arr = array();
		$central_grp_arr = array_intersect($facility_org_ids, $dhis_central_ids);
		if(!empty($central_grp_arr)){
			$dhis_org = $facility_org_groups['id'];
			$central_grp = array_values($central_grp_arr)[0];
			//Set dates for report retrieving
			$i = 1;
			$period_dates = array();
			while ($i <= $period_filter) {
				$period_dates[] = date('Y-m-01', strtotime(date('Y-m')." -".$i." month"));
				$i++;
			}
			//Loop through report monthly periods and get cdrr/maps
			foreach ($period_dates as $period_date) {
				$period = date('Ym', strtotime($period_date));
				$resource = $dataset_url."?dataSet=$dataset&period=$period&orgUnitGroup=".$central_grp; // get cdrr
				$report = json_decode($this->sendRequest($resource, 'GET', null, $dhis_auth));

				if(count($report->dataValues)>0){
					$start_date = $period_date;
					$end_date =  date('Y-m-t', strtotime($period_date));
					//cdrr
					if(in_array($ds, array('dcdrr')) && isset($report->dataValues)){
						$facility_id = $this->get_sync_facility_id($dhis_org, $code);
						//Add cdrr
						$cdrr = array(
							'status' => 'approved',
							'created' => str_replace('T', ' ', $report->dataValues[0]->created),
							'updated' => str_replace('T', ' ', $report->dataValues[0]->lastUpdated),
							'code' => $code,
							'period_begin' => $start_date,
							'period_end' => $end_date,
							'comments' => '',
							'reports_expected' => $this -> expectedReports($this->facility_code),
							'reports_actual' => $this -> actualReports($this->facility_code, $start_date, 'cdrr'),
							'services' => '',
							'sponsors' => '',
							'non_arv' => 0,
							'delivery_note' => '',
							'order_id' => 0,
							'facility_id' => $facility_id,
							'issynched' => 'Y');
						//Check if cdrr exists
						$row = $this->db->select('id')->get_where('cdrr', array(
								'facility_id' => $facility_id, 
								'period_begin' => $start_date,
								'code' => 'D-CDRR'))->row_array();
						if(count($row)>0){
							$cdrr_id = $row['id'];
							$this->db->where('id', $cdrr_id);
							$this->db->update('cdrr', $cdrr);
						}else{
							$this->db->insert('cdrr', $cdrr);		
							$cdrr_id = $this->db->insert_id();
						}

						// run aggregate function

						//Build formatted cdrr_item object
						$cdrr_item = array();
						foreach ($report->dataValues as $key => $value) {
							$drug_id = $this->dhisLookup($value->dataElement, 'drug');
							$column = $this->dhisLookup($value->categoryOptionCombo);
							if($column == 'expiry_date' && $value->value != 0){
								$cdrr_item[$cdrr_id][$drug_id][$column] = date('Y-m-01', strtotime(substr($value->value, -4).'-'.substr($value->value, 0, -4).'-01'));
							}else{
								$cdrr_item[$cdrr_id][$drug_id][$column] = $value->value;
							}
						}

						//Add cdrr_item
						foreach ($cdrr_item as $cdrr_id => $items) {
							foreach ($items as $drug_id => $cdrr_item_tmp) {
								//Add cdrr_id and drug_id
								$cdrr_item_tmp['cdrr_id'] = $cdrr_id;
								$cdrr_item_tmp['drug_id'] = $drug_id;
								//Check if value exists
								$row = $this->db->select('id')->get_where('cdrr_item', array(
								'cdrr_id' => $cdrr_id, 
								'drug_id' => $drug_id))->row_array();
								if(count($row)>0){
									$cdrr_item_id = $row['id'];
									$this->db->where('id', $cdrr_item_id);
									$this->db->update('cdrr_item', $cdrr_item_tmp);
								}else{
									$this->db->insert('cdrr_item', $cdrr_item_tmp);		
								}
							}	
						}

						//Add cdrr_log
						$last_index = (sizeof($report->dataValues) - 1);
						$logs = array('prepared' => $report->dataValues[$last_index]->created, 'approved' => $report->dataValues[$last_index]->lastUpdated);
						foreach ($logs as $log => $timeline) {
							$user_id = $this->session->userdata('user_id');
							$user = $this->db->select('user_id')->get_where('sync_user', array('username' => $report->dataValues[$last_index]->storedBy))->row_array();
							if(!empty($user)){
								$user_id = $user['user_id'];
							}
							//cdrr_log Object 
							$cdrr_log_tmp = array(
								'description' => $log,
								'created' => $timeline,
								'user_id' => $user_id,
								'cdrr_id' => $cdrr_id
							);
							//Check if value exists
							$row = $this->db->select('id')->get_where('cdrr_log', array(
							'cdrr_id' => $cdrr_id, 
							'description' => $log))->row_array();
							if(count($row)>0){
								$cdrr_log_id = $row['id'];
								$this->db->where('id', $cdrr_log_id);
								$this->db->update('cdrr_log', $cdrr_log_tmp);
							}else{
								$this->db->insert('cdrr_log', $cdrr_log_tmp);		
							}
						}
						$this->aggregate_dcdrr($start_date,$facility_id);
					
						//Set success response
						$response[$ds] = array('status' => true, 'message' => '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Success!</strong> '.strtoupper($ds).' Reports were retrieved successfully!</div>');
					}	
					//maps
					else if(in_array($ds, array('dmaps')) && isset($report->dataValues)){
						$facility_id = $this->get_sync_facility_id($dhis_org, $code);
						$maps = array(
							'status' => 'approved',
							'created' => str_replace('T', ' ', $report->dataValues[0]->created),
							'updated' => str_replace('T', ' ', $report->dataValues[0]->lastUpdated),
							'code'  => $code,
							'period_begin' => $start_date,
							'period_end' => $end_date,
							'reports_expected' => $this -> expectedReports($this->facility_code),
							'reports_actual' =>$this -> actualReports($this->facility_code, $start_date, 'maps'),
							'art_adult'  => '',
							'art_child'  => '',
							'new_male'  => '',
							'revisit_male'  => '',
							'new_female'  => '',
							'revisit_female'  => '',
							'new_pmtct'  => '',
							'revisit_pmtct'  => '',
							'total_infant'  => '',
							'pep_adult'  => '',
							'pep_child'  => '',
							'total_adult'  => '',
							'total_child'  => '',
							'diflucan_adult'  => '',
							'diflucan_child'  => '',
							'new_cm'  => '',
							'revisit_cm'  => '',
							'new_oc'  => '',
							'revisit_oc'  => '',
							'comments'  => '',
							'report_id'  => '',
							'facility_id' => $facility_id,
							'issynched'  => 'Y'
						);

						//Check if maps exists
						$row = $this->db->select('id')->get_where('maps', array(
								'facility_id' => $facility_id, 
								'period_begin' => $start_date,
								'code' => 'D-MAPS'))->row_array();
						if(!empty($row)){
							$maps_id = $row['id'];
							$this->db->where('id', $maps_id);
							$this->db->update('maps', $maps);
						}else{
							$this->db->insert('maps', $maps);		
							$maps_id = $this->db->insert_id();
						}

						//Build formatted maps_item object
						$maps_item = array();
						foreach ($report->dataValues as $key => $value) {
							$regimen_id = $this->dhisLookup($value->dataElement, 'regimen');
							$column = $this->dhisLookup($value->categoryOptionCombo);
							$maps_item[$maps_id][$regimen_id][$column] += $value->value;
						}

						//Add maps_item
						foreach ($maps_item as $maps_id => $items) {
							foreach ($items as $regimen_id => $maps_item_tmp) {
								//Add maps_id and regimen_id
								$maps_item_tmp['maps_id'] = $maps_id;
								$maps_item_tmp['regimen_id'] = $regimen_id;
								$maps_item_tmp['issynched'] = 'Y';

								//Check if value exists
								$row = $this->db->select('id')->get_where('maps_item', array(
								'maps_id' => $maps_id, 
								'regimen_id' => $regimen_id))->row_array();
								if(!empty($row)){
									$maps_item_id = $row['id'];
									$this->db->where('id', $maps_item_id);
									$this->db->update('maps_item', $maps_item_tmp);
								}else{
									$this->db->insert('maps_item', $maps_item_tmp);		
								}
							}	
						}

						//Add maps_log
						$last_index = (sizeof($report->dataValues) - 1);
						$logs = array('prepared' => $report->dataValues[$last_index]->created, 'approved' => $report->dataValues[$last_index]->lastUpdated);
						foreach ($logs as $log => $timeline) {
							$user_id = $this->session->userdata('user_id');
							$user = $this->db->select('user_id')->get_where('sync_user', array('username' => $report->dataValues[$last_index]->storedBy))->row_array();
							if(!empty($user)){
								$user_id = $user['user_id'];
							}
							//maps_log Object 
							$maps_log_tmp = array(
								'description' => $log,
								'created' => $timeline,
								'user_id' => $user_id,
								'maps_id' => $maps_id
							);

							//Check if value exists
							$row = $this->db->select('id')->get_where('maps_log', array(
							'maps_id' => $maps_id, 
							'description' => $log))->row_array();
							if(!empty($row)){
								$maps_log_id = $row['id'];
								$this->db->where('id', $maps_log_id);
								$this->db->update('maps_log', $maps_log_tmp);
							}else{
								$this->db->insert('maps_log', $maps_log_tmp);		
							}
						}
						//Set success response
						$response[$ds] = array('status' => true, 'message' => '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Success!</strong> '.strtoupper($ds).' Reports were retrieved successfully!</div>');
					}
				}
			}
		}
		
		return $response;
	}


	public function dhisLookup($dhiscode, $object = null){
		// return category option names in the adt_config
		if($object ==null){
			$key = array_search ($dhiscode, $this->config->config['dhiscode']);
			$result = $key;
		}	

		// return ADT object whether regimen,drug,facility, 
		if ($object == 'facility'){
			$sql = "SELECT * FROM sync_facility where id = '$dhiscode'";
			$result = $this->db->query($sql)->result_array()[0]['id'];
		}

		else if ($object == 'drug'){
			$sql = "SELECT * FROM dhis_elements de inner join sync_drug d on de.target_id = d.id WHERE de.dhis_code = '$dhiscode'";
			$result = $this->db->query($sql)->result_array()[0]['id'];
		}
		else if ($object == 'regimen'){
			$sql = "SELECT * FROM dhis_elements de inner join sync_regimen r on de.target_id = r.id WHERE de.dhis_code = '$dhiscode'";		
			$result = $this->db->query($sql)->result_array()[0]['id'];
		}
		return $result;

	}

	private function sendRequest($resource, $method, $payload = null, $authorization = null){
			//  Initiate cURL.
		$ch = curl_init($this->dhis_url.$resource);
			//  The JSON data.
			//  Encode the array into JSON.
		$jsonDataEncoded = json_encode($payload);
			//  Tell cURL that we want to send a POST request.

		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			//  Attach our encoded JSON string to the POST fields.
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

		// Escape SSL Certificate errors
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		// Set the content type to application/json
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
		curl_setopt($ch, CURLOPT_USERPWD, "$authorization");

		$response = curl_exec($ch);
		$status = curl_getinfo($ch);
		curl_close($ch);
		return $response;

	}
	public function aggregate_dcdrr($period = null , $facility_id){
		// select fcdrr id for period
		$sql = "select id from cdrr c where code = 'D-CDRR' and  period_begin = '$period' limit 1";
		$query = $this->db->query($sql);
		$result = $query->result_array($query);
		if($result){
			$dcdrr_id = $result[0]['id'];
			$sql = "SELECT concat ('update cdrr_item set aggr_on_hand= ', sum(count),' ,aggr_consumed =', sum(dispensed_packs) ,' where drug_id = ',drug_id,' and cdrr_id = $dcdrr_id;' ) as q
			FROM cdrr c 
			INNER JOIN cdrr_item ci ON ci.cdrr_id  = c.id
			INNER JOIN sync_drug dc ON dc.id = ci.drug_id
			INNER JOIN sync_facility sf ON sf.id = c.facility_id
			WHERE c.period_begin = '$period' 
			AND c.code = 'F-CDRR_units'
			AND sf.category = 'satellite'
			AND sf.parent_id = '$facility_id'
			group by drug_id";

			$query = $this->db->query($sql);
			$result = $query->result_array();
			if ($result){
				foreach ($result as $value) {
					if ($value['q'] !==NULL) {
						$query = $this->db->query($value['q']);
					}
				}
			}
		}
	}

	public function get_sync_facility_id($dhis_code, $code){
		$facility_id = NULL;

		if(in_array($code, array('F-CDRR_units', 'F-MAPS'))  && $this->facility_type != 1){ //Satellite
			$result = $this->db->select('id')->get_where('sync_facility', array(
				'dhiscode' =>  $dhis_code, 
				'category' => 'satellite', 
				'ordering' => '0',
				'service_point' => '1'
			))->row_array();
		}else if(in_array($code, array('F-CDRR_packs', 'F-MAPS'))  &&  $this->facility_type == 1){ //Standalone
			$result = $this->db->select('id')->get_where('sync_facility', array(
				'dhiscode' =>  $dhis_code, 
				'category' => 'standalone', 
				'ordering' => '1',
				'service_point' => '1'
			))->row_array();
		}else if(in_array($code, array('D-CDRR', 'D-MAPS')) &&  $this->facility_type > 1){ //Central
			$result = $this->db->select('id')->get_where('sync_facility', array(
				'dhiscode' =>  $dhis_code, 
				'category' => 'central', 
				'ordering' => '1',
				'service_point' => '0'
			))->row_array();
		}

		if(!empty($result)){
			$facility_id = $result['id'];
		}
		return $facility_id;
	}

}
// end of buffer: Exit and Clear
ob_get_clean();
?>
