<?php
$path_to_root = '..';
include_once($path_to_root . "/includes/session.inc");
$_SESSION['StockChkID'] = '';
$type = $_REQUEST['type'];
$dataval = $_REQUEST['q'];

if(!isset($dataval)){
	$fetchData = '';
}else{
	$search = $dataval;
	
	
	  $sql = "SELECT stock_id, CONCAT(stock_id,'       ',s.description) as item_desc, c.description as category_description, s.inactive, s.editable FROM ".TB_PREF."stock_master s, ".TB_PREF."stock_category c WHERE s.category_id=c.category_id  AND mb_flag!='F' AND (stock_id LIKE '%".$search."%' or  s.description LIKE '%".$search."%' OR s.supplier_item_code LIKE '%".$search."%') order by c.description, stock_id  LIMIT 0,200"; 
	 
	 
	   
	$fetchData = db_query($sql, 'Not connected');
	
}
$data = array();
$data[] = array("id"=>'', "text"=>'Select');
while ($row = db_fetch($fetchData)) {
	 $data[] = array("id"=>$row['stock_id'], "text"=>preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&-]/s', '', $row['item_desc']));
}
echo json_encode($data);
