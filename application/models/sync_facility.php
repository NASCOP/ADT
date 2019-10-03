<?php
class Sync_facility extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('name', 'varchar', 255);
		$this -> hasColumn('code', 'varchar', 15);
		$this -> hasColumn('category', 'varchar', 15);
		$this -> hasColumn('sponsors', 'varchar', 255);
		$this -> hasColumn('services', 'varchar', 255);
		$this -> hasColumn('manager_id', 'int', 11);
		$this -> hasColumn('district_id', 'int', 11);
		$this -> hasColumn('address_id', 'int', 11);
		$this -> hasColumn('parent_id', 'int', 11);
		$this -> hasColumn('ordering', 'tinyint', 1);
		$this -> hasColumn('affiliation', 'varchar', 255);
		$this -> hasColumn('service_point', 'tinyint', 1);
		$this -> hasColumn('county_id', 'int', 11);
		$this -> hasColumn('hcsm_id', 'int', 11);
		$this -> hasColumn('keph_level', 'varchar', 25);
		$this -> hasColumn('location', 'varchar', 255);
		$this -> hasColumn('affiliate_organization_id', 'int', 11);

	}

	public function setUp() {
		$this -> setTableName('sync_facility');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("sync_facility");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_facility;
	}

	public function getId($facility_code, $parent_sites = 0) {
		if($parent_sites == 0){
			$conditions = "code='$facility_code' and category like '%satellite%' and ordering = '0' and service_point = '1'";
		}else if($parent_sites == 1){
			$conditions = "code='$facility_code' and category like '%standalone%' and ordering = '1' and service_point = '1'";
		}else if($parent_sites > 1){
			$conditions = "code='$facility_code' and category like '%central%' and ordering = '1' and service_point = '0'";
		}
		$query = Doctrine_Query::create() -> select("id") -> from("sync_facility") -> where("$conditions");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return @$sync_facility[0];
	}

	public function getCode($facility_id, $parent_sites = 0) {
		if($parent_sites == 0){
			$conditions = "id='$facility_id' and category like '%satellite%' and ordering = '0' and service_point = '1'";
		}else if($parent_sites == 1){
			$conditions = "id='$facility_id' and category like '%standalone%' and ordering = '1' and service_point = '1'";
		}else{
			$conditions = "id='$facility_id' and category like '%central%' and ordering = '1' and service_point = '0'";
		}
		$query = Doctrine_Query::create() -> select("code") -> from("sync_facility") -> where("$conditions");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return @$sync_facility[0];
	}

	public function getSatellites($central_site) {//Include CUrrent facility
		$query = Doctrine_Query::create() -> select("id") -> from("sync_facility") -> where("parent_id='$central_site'");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_facility;
	}

	public function getOtherSatellites($central_site, $facility_code) { //Only get satellites
		$query = Doctrine_Query::create() -> select("id") -> from("sync_facility") -> where("parent_id='$central_site' and code !='$facility_code'");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_facility;
	}

	public function getSatellitesDetails($central_site) {
		$query = Doctrine_Query::create() -> select("*") -> from("sync_facility") -> where("parent_id='$central_site'");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_facility;
	}

	public function get_facility_category($code = NULL) 
	{
		$query = Doctrine_Query::create() -> select("category") -> from("sync_facility") -> where("code='$code'");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_facility[0]['category'];
	}

	public function get_active() {
		$query = Doctrine_Query::create() -> select("*") -> from("sync_facility") -> where("Active='1'");
		$sync_facility = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $sync_facility;
	}

}
?>

