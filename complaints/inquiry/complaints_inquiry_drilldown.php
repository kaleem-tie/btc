<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$page_security = 'SA_COMPLAINT_INQUIRY';
$path_to_root="../..";
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include($path_to_root . "/complaints/includes/db/complaint_raise_db.inc");
include_once($path_to_root . "/admin/db/users_db.inc");



if (isset($_GET['dl']))
	$download_id = $_GET['dl'];
else
	$download_id = find_submit('download');

if ($download_id!= -1)
{
	
	$row = get_complaint_attachment_for_download($download_id);
	if ($row['filename'] != "")
	{
		
		
		if(in_ajax()) {
			$Ajax->redirect($_SERVER['PHP_SELF'].'?dl='.$download_id);
		} else {
			$type = ($row['filetype']) ? $row['filetype'] : 'application/octet-stream';	
			
    		header("Content-type: ".$type);
	    	header('Content-Length: '.$row['filesize']);
    		header('Content-Disposition: attachment; filename='.$row['filename']);
    		echo file_get_contents($path_to_root."/complaints/attachments/".$row['unique_name']);
	    	exit();
		}
	}	
}


$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Complaints Drilldown (Item Wise)"), false, false, "", $js);


//---------------------------------------------------------------------------------

if(isset($_GET['StockId']) && $_GET['StockId']!= '' ){
	$_POST['StockId'] = $_GET['StockId'];
}


$sess=$_SESSION["wa_current_user"]->loginname;
//display_error($sess);
function sess_name(){
	$sess=$_SESSION["wa_current_user"]->loginname;
	return $sess;
}

//---------------------------------------------------------------------

function get_all_customer_complaints($stock_id,$customer_id=0,$status=0,
$complaint_number='')
{
 
	$sql = "SELECT cc.complaint_number,cc.id,debtor.name as customer_name,cc.subject,cc.date,
	cc.inactive AS status,user.real_name as user_name,
	cc.complaint_against,cc.mobile_number
	FROM ".TB_PREF."proj_customer_complaint cc 
	LEFT JOIN ".TB_PREF."proj_complaint_replies AS creplies ON cc.id =creplies.complaint_id 
	LEFT JOIN ".TB_PREF."users AS user ON  cc.current_user_id = user.id
	,".TB_PREF."debtors_master debtor 
	WHERE cc.customer_id=debtor.debtor_no AND cc.stock_id=".db_escape($stock_id)."";
	
	if ($customer_id != "")
	{
		$sql .= " AND cc.customer_id = ".db_escape($customer_id) ;
	}
	
	if ($complaint_number != "")
	{
		$sql .= " AND cc.complaint_number LIKE ".db_escape('%' . $complaint_number . '%');
	}
	
	if ($status == '1')
	{
		$sql .= " AND cc.inactive= '0'" ;
	}elseif($status == '2'){
		$sql .= " AND cc.inactive = '2'" ;
	}
	elseif($status == '3'){
		$sql .= " AND cc.inactive = '1'" ;
	}
	
   $sql .=" GROUP BY  cc.id ORDER BY  cc.date DESC, cc.date DESC";
   
	
  return $sql;
	
}


function get_complaint_attachment_by_complaint_id($complaint_id)
{
	$sql = "SELECT * FROM ".TB_PREF."complaints_attachments WHERE complaint_id=".db_escape($complaint_id);
	
	$result = db_query($sql, "could not Project");

	return db_fetch($result);
}


function get_complaint_attachment_for_download($id)
{
	$sql = "SELECT * FROM ".TB_PREF."complaints_attachments WHERE id=".db_escape($id);

	$result = db_query($sql, "could not Project");

	return db_fetch($result);
}

function get_replies_count($complaint_id)
{
	$sql = "SELECT COUNT(*)as replay_count FROM ".TB_PREF."proj_complaint_replies WHERE complaint_id=".db_escape($complaint_id)." GROUP BY complaint_id";

	$result = db_query($sql, "could not Project replaies");

	$res = db_fetch_row($result);
	return  $res["0"];
}

function posted_by($row)
{
	$user_name = "<b '> ".$row['user_name']."  </b>";
	$comp_date="<br><span style='font-size:10px;'> ".sql2date($row['date']).' '.date('h:i a', strtotime($row['date']))."  </span>";
    return $user_name. ' ' . $comp_date;
}


function complaints_link($row)
{
	
	$role="<br><span style='font-size:10px;'> Posted To :- ".$row['customer_name']."  </span>";
	
	if($row["status"]==0){
	return pager_link( $row["subject"].$role,	"/complaints/manage/complaint_replies.php?complaint_id=" . $row["id"]);
	}	
	else{
	return pager_link( $row["subject"].$role. "(".$row["project_name"]." ) " ,	"/complaints/manage/complaint_replies.php?complaint_id=" . $row["id"]);
	}
	
}

function replies_link($row)
{
 $replies = get_replies_count($row['id']);
  return $replies;
}

function status_link($row)
{
	
	if($row["status"]==0){
		return "Open"; 
	}
	else if($row["status"]==1){
		return "Closed"; 
	}
	else if($row["status"]==2){
		return "In Progress"; 
	}
  
}


function download_link($row)
{
	
	$complaint_attchment = get_complaint_attachment_by_complaint_id($row['id']);
	if($complaint_attchment["filename"]!=''){
  	return button('download'.$complaint_attchment["id"], _("Download"), _("Download"), ICON_DOWN);
	}
	else{
	return "#";	
	}
}

//-----------------------------------------------------------------------------------

start_form(true);

if (!isset($_POST['customer_id']))
	$_POST['customer_id'] = get_global_customer();


if (isset($_GET["StockId"])){
	$_POST["StockId"] = $_GET["StockId"];	
}

start_outer_table(TABLESTYLE2, "");
table_section(1);
start_row();
if (!$page_nested)
	customer_list_cells(_("Select a Customer: "), 'customer_id', null, true, true, false, true);
end_row();

table_section(2);
start_row(); 
ref_cells(_("Complaint Number"), 'complaint_number', '',null, '', true);
end_row();

table_section(3);
start_row();  
customer_complaint_status_list_cells("Status","status",null, true, true); 
end_row();

end_outer_table(1);

set_global_customer($_POST['customer_id']);


if (get_post('customer_id') || get_post('complaint_number'))
{
	$Ajax->activate('TicketDetails');
	$Ajax->activate('trans_tbl');
}


//--------------------------------------------------------------------

hidden('StockId',$_POST["StockId"]);

	
div_start("TicketDetails");


$sql = get_all_customer_complaints($_POST["StockId"],$_POST['customer_id'],
$_POST['status'],$_POST['complaint_number']);
$cols = array(
	    _("Complaint Number") => array('name'=>'complaint_number', 'align'=>'center'),
	    _("Posted By") => array('fun'=>'posted_by', 'align'=>'center'),
	    _("Complaints") => array('fun'=>'complaints_link', 'align'=>'center'),
	    _("Replies") => array('fun'=>'replies_link', 'align'=>'center'),
	    _("Status") => array('fun'=>'status_link', 'align'=>'center'),
		array('insert'=>true, 'fun'=>'download_link'),
	    	
	    );	
$table =& new_db_pager('trans_tbl', $sql, $cols);
$table->width = "60%";
display_db_pager($table);

div_end();
div_end();

end_form();
end_page();
?>
<style>
tr.opened td:nth-child(4){  color: #ff0000 !important;}
tr.Replied td:nth-child(4){  color: #3854d1 !important;}
p {
    margin: 0;
    line-height: 20px;
}
</style>
