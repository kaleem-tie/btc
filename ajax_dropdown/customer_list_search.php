<?php
//include 'config.php';
//include '../config_db.php';
$path_to_root = '..';
include_once($path_to_root . "/includes/session.inc");
$_SESSION['debtornoChkID'] = '';


$dataval = $_REQUEST['q'];//?$_REQUEST['q']:$_REQUEST['page'];
// $smid = $_REQUEST['saleman_id'];

if($_SESSION['wa_current_user']->access_all_customers==1)
{
	$salesman_id = 0;
}else {
	$salesman_id = $_SESSION['wa_current_user']->salesman_id; 
}

if(!isset($dataval)){
	$fetchData = '';//mysql_query("select * from 0_loc_stock order by loc_code limit 500");
}else{
	$search = $dataval;
	// $salesman_id = $smid;
	
	 if($salesman_id!='' && $salesman_id>0)
	 {
		 $sql = "SELECT m.debtor_no,CONCAT(IFNULL(m.debtor_ref,''),' (',IFNULL(m.cust_code,''),')') as debtor_ref , m.curr_code, m.inactive
			 FROM ".TB_PREF."debtors_master m,".TB_PREF."cust_branch c
			WHERE  m.debtor_no=c.debtor_no AND c.salesman=".db_escape($salesman_id)." AND (m.debtor_ref LIKE '%".$search."%'  OR m.cust_code LIKE '%".$search."%')";

	$sql .= " GROUP BY debtor_no";
	
	 }else {
	  $sql = "SELECT debtor_no,CONCAT(IFNULL(m.debtor_ref,''),' (',IFNULL(m.cust_code,''),')') as debtor_ref , m.curr_code, m.inactive
			 FROM ".TB_PREF."debtors_master m
			WHERE  (debtor_ref LIKE '%".$search."%'  OR cust_code LIKE '%".$search."%')";

	$sql .= " GROUP BY debtor_no";
	 }
	 	  // echo $sql;
	$fetchData = db_query($sql, 'Not connected');
}




$data = array();
$data[] = array("id"=>'', "text"=>preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&-]/s', '', 'All Customers'));
while ($row = db_fetch($fetchData)) {
     $data[] = array("id"=>$row['debtor_no'], "text"=>preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&-]/s', '', $row['debtor_ref']));
}
echo json_encode($data);
