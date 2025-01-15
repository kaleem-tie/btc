<?php

//include 'config.php';

//include '../config_db.php';

$path_to_root = '..';

include_once($path_to_root . "/includes/session.inc");



$type = $_REQUEST['type'];

$dataval = $_REQUEST['q'];//?$_REQUEST['q']:$_REQUEST['page'];



if(!isset($dataval)){

	$fetchData = '';//mysql_query("select * from 0_loc_stock order by loc_code limit 500");

}else{

	$search = $dataval;

	

	// $sql = "SELECT stock_id, s.description as stock_description, c.description as category_description, s.inactive, s.editable FROM ".TB_PREF."stock_master s, ".TB_PREF."stock_category c WHERE s.category_id=c.category_id  AND mb_flag!='F' AND (stock_id LIKE '%".$search."%' or  s.description LIKE '%".$search."%'  or  c.description LIKE '%".$search."%') order by c.description, stock_id";

	 

	 $sql = "SELECT s.stock_id, CONCAT(s.stock_id,'       ',s.description) as item_desc, c.description as category_description, s.inactive, s.editable 
	 FROM ".TB_PREF."stock_master s,
	 ".TB_PREF."stock_category c WHERE s.category_id=c.category_id  AND mb_flag!='F' 
	 AND (s.stock_id LIKE '%".$search."%' or  s.description LIKE '%".$search."%'
	 OR s.supplier_item_code LIKE '%".$search."%') order by c.description, s.stock_id";

	 

	if (isset($opts['fixed_asset']) && $opts['fixed_asset']){

		$sql .= " AND mb_flag='F'";

	}else{

		$sql .= " AND mb_flag!='F'";

	}

	$sql .= " LIMIT 0,200";

	

	$fetchData = db_query($sql, 'Not connected');

}









$data = array();



while ($row = db_fetch($fetchData)) {

    //$data[] = array("id"=>$row['stock_id'], "text"=>$row['stock_id'].' '.$row['stock_description']);

    $data[] = array("id"=>$row['stock_id'], "text"=>$row['item_desc']);

}





//display_error(json_encode($data));

echo json_encode($data);

