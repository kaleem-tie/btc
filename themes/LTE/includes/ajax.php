<?php 

$path_to_root ="../../.."; 
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once("../kvcodes.inc");

if(isset($_GET['Line_chart'])){
	 $top_selling_items =  class_balances($_GET['Line_chart']); 
	 $area_chart =  array(); 
	 foreach($top_selling_items as $top) { 
	 	$area_chart[] =   array("class" => $top['class_name'] , "value" => abs($top['total']));  
	 } 
	 echo json_encode($area_chart); exit; 
}

if(isset($_GET['Area_chart'])){
	 $top_selling_items =  Top_selling_items($_GET['Area_chart']); 
	 $area_chart =  array(); 
	 foreach($top_selling_items as $top) { $area_chart[] =   array("y" => $top['description'] , "a" => round($top['total'], 2), "b" => round($top['costs'], 2));  } echo json_encode($area_chart); exit; 
}

if(isset($_GET['Customer_chart'])){
	$cutomers = get_top_customers($_GET['Customer_chart']);
	$donut_chart =  array(); 
	 foreach($cutomers as $top) { 
	 	$donut_chart[] =   array("label" => $top['name'] , "value" => round($top['total'], 2));  
	 } 
	 echo json_encode($donut_chart); exit;
}

if(isset($_GET['Supplier_chart'])){
	$suppliers = get_top_suppliers($_GET['Supplier_chart']);
	$donut_chart =  array(); 
	 foreach($suppliers as $top) { 
	 	$donut_chart[] =   array("label" => $top['supp_name'] , "value" => round($top['total'], 2));  
	 } 
	 echo json_encode($donut_chart); exit;
}

if(isset($_GET['Expense_chart'])){
	$cutomers = Expenses($_GET['Expense_chart']);
	$bar_chart =  array(); 
	 foreach($cutomers as $top) { 
	 	$bar_chart[] =   array("y" => htmlspecialchars_decode($top['name']) , "a" => round($top['balance'], 2));  
	 } 
	 if(empty($bar_chart)){
		$bar_chart[] = array("y" => "nothing" , "a" => 0);
	}
	 echo json_encode($bar_chart); exit;
}
if(isset($_GET['Tax_chart'])){
	$suppliers = get_tax_reports($_GET['Tax_chart']);
	$donut_chart =  array(); 
	 foreach($suppliers as $top) { 
	 	$donut_chart[] =   array("label" => $top['name'] , "value" => abs(round($top['total'], 2)));  
	 } 
	// $donut_chart['grandtotal'] = abs(round($suppliers['grandtotal'],2));
	 echo json_encode($donut_chart); exit;
}

if(isset($_GET['ChangeCompany'])) {
	if(isset($db_connections[$_GET['ChangeCompany']])){
		$_SESSION['wa_current_user']->company = $_GET['ChangeCompany'];
		$db_table_name = $db_connections[$_SESSION['wa_current_user']->company]['dbname'].'.'.$db_connections[$_SESSION['wa_current_user']->company]['tbpref'];
		$sql = "SELECT id FROM ".$db_table_name."users WHERE user_id =".db_escape($_SESSION['wa_current_user']->loginname)." LIMIT 1";
		$res = db_query($sql, "Can't get user Account");
		if(db_num_rows($res) == 1){
			if($row = db_fetch($res)){
				$current_user_id = $row['id'];
			} else {
				$current_user_id = -1;
			}
		} else 
			$current_user_id = -1;

		if($current_user_id == -1){
			$sql = "SELECT id FROM ".$db_table_name."users ORDER BY id LIMIT 1";
				$res = db_query($sql, "Can't get user Account");
				$row = db_fetch($res);
				$current_user_id = $row['id'];
		}

		echo $_SESSION['wa_current_user']->user = $current_user_id;
	}

	echo $_SESSION['wa_current_user']->company;
} elseif(isset($_GET['GetCompany']) && $_GET['GetCompany'] == 'yes') {
	$filtered =[];
	include_once($path_to_root."/themes/LTE/includes/users.php");
	$row = get_master_login($_SESSION['wa_current_user']->loginname);
	$companies = unserialize(base64_decode($row['companies']));

	if(isset($_GET['term']) && $_GET['term'] != ''){		
		foreach($db_connections as $cid => $data){
			if (strpos($_GET['term'], $data['name']) !== FALSE && (isset($companies) && in_array($cid, $companies))) { // Yoshi version
			        $filtered[$cid] = $data['name'];
   			}
		}
	}
	if(!empty($filtered))
		echo json_encode($filtered);
	else
		echo false;
}
?>