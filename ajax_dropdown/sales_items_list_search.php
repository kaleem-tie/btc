<?php
//include 'config.php';
//include '../config_db.php';
$path_to_root = '..';
include_once($path_to_root . "/includes/session.inc");
$_SESSION['StockChkID'] = '';
$type = $_REQUEST['type'];
$dataval = $_REQUEST['q'];//?$_REQUEST['q']:$_REQUEST['page'];

if(!isset($dataval)){
	$fetchData = '';//mysql_query("select * from 0_loc_stock order by loc_code limit 500");
}else{
	$search = $dataval;
	
	 $user_dim=$_SESSION["wa_current_user"]->user_dimensions;
	 
$sql = "SELECT i.item_code, CONCAT(i.item_code,'       ', i.description) as item_desc, c.description, count(*)>1 as kit,
			 i.inactive, if(count(*)>1, '0', s.editable) as editable
			FROM
			".TB_PREF."stock_master s,
			".TB_PREF."item_codes i
			LEFT JOIN
			".TB_PREF."stock_category c
			ON i.category_id=c.category_id
			WHERE i.stock_id=s.stock_id
      AND s.mb_flag != 'F' 
	  AND (i.item_code LIKE '%".$search."%'  
	  OR  i.description LIKE '%".$search."%' 
	  OR s.supplier_item_code LIKE '%".$search."%' ) 
	  AND  (FIND_IN_SET(dimension_id,'".$user_dim."') OR dimension_id=0 )";

	
	if ($type == 'local')	{ // exclude foreign codes
		$sql .=	" AND !i.is_foreign"; 
	} elseif ($type == 'kits') { // sales kits
		$sql .=	" AND !i.is_foreign AND i.item_code!=i.stock_id";
	}
	$sql .= " AND !i.inactive AND !s.inactive AND !s.no_sale";
	$sql .= " GROUP BY i.item_code";
	$sql .= " LIMIT 0,200";
	
	// echo $sql; 
	
	$fetchData = db_query($sql, 'Not connected');
}




$data = array();

while ($row = db_fetch($fetchData)) {
     $data[] = array("id"=>$row['item_code'], "text"=>preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&-]/s', '', $row['item_desc']));
}
echo json_encode($data);
