<?php
class Maps_item extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('total', 'int', 11);
		$this -> hasColumn('regimen_id', 'int', 11);
		$this -> hasColumn('maps_id', 'int', 11);
	}

	public function setUp() {
		$this -> setTableName('maps_item');
		$this -> hasOne('Maps as Maps', array('local' => 'maps_id', 'foreign' => 'id'));
		$this -> hasOne('Sync_Regimen as S_Regimen', array('local' => 'regimen_id', 'foreign' => 'id'));
	}

	public static function getOrderItems($map) {
		$query = Doctrine_Query::create() -> select("*") -> from("maps_item") -> where("maps_id = '$map'");
		$items = $query -> execute();
		return $items;
	}

	public static function getItem($item) {
		$query = Doctrine_Query::create() -> select("*") -> from("maps_item") -> where("id = '$item'");
		$items = $query -> execute();
		return $items[0];
	}
	public function getDhisItem($item,$code = '') {
		$sql = "SELECT * FROM maps_item mi 
		LEFT JOIN dhis_elements de on mi.regimen_id = de.target_id 
		WHERE  maps_id = $item
		AND dhis_report ='MoH 729b' AND target_report !='unknown'";
		
		$query=$this->db->query($sql);
		$items=$query->result_array();
		return $items;
		
	}
	
	public function getItems($map) {
		$query = Doctrine_Query::create() -> select("*") -> from("maps_item") -> where("maps_id = '$map'");
		$items = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $items;
	}


}
?>