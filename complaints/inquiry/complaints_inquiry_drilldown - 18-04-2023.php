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

if (isset($_GET["StockId"]))
	$_POST["StockId"] = $_GET["StockId"];	


if (isset($_GET['id']))
{	

	$_POST['id'] = $_GET['id'];
	
}


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
page(_($help_context = "View or Update Complaints"), false, false, "", $js);


//---------------------------------------------------------------------------------------------

$sess=$_SESSION["wa_current_user"]->loginname;
//display_error($sess);
function sess_name(){
	$sess=$_SESSION["wa_current_user"]->loginname;
	return $sess;
}


function get_all_customer_complaints($stock_id)
{
 
	$sql = "select cc.id,debtor.name as customer_name,cc.subject,cc.date,
	cc.inactive AS status,user.real_name as user_name,
	cc.complaint_number,cc.complaint_against,cc.mobile_number
	FROM ".TB_PREF."proj_customer_complaint cc 
	LEFT JOIN ".TB_PREF."proj_complaint_replies AS creplies ON cc.id =creplies.complaint_id 
	LEFT JOIN ".TB_PREF."users AS user ON  cc.current_user_id = user.id
	,".TB_PREF."debtors_master debtor 
	WHERE cc.customer_id=debtor.debtor_no AND cc.stock_id=".db_escape($stock_id)."";
   
   $sql .=" GROUP BY  cc.id ORDER BY  cc.date DESC, cc.date DESC LIMIT 100";
   
   
	
	$result = db_query($sql, "Could not get data!");
        $data = array();
        if(db_num_rows($result) > 0 ) {
            while($row = db_fetch($result)) {
                $data[] = $row;
            }
            return $data;
        } else {

            return false;
        }
	
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

//-----------------------------------------------------------------------------------


start_form(true);


set_global_customer($_POST['customer_id']);


if (get_post('customer_id') || get_post('mobile_number'))
{
	$Ajax->activate('_page_body');
}


//--------------------------------------------------------------------
	
div_start("TicketDetails");
start_table(TABLESTYLE, "width='80%'");
$k=0;
$th = array (_('Complaint Number'),_('Posted By'),_('Complaints'), _('Replies'),
_('Status'),_('Attachment'));

table_header($th);
$result=get_all_customer_complaints($_POST["StockId"]);

if(! empty($result)){
	
	
	foreach ($result as $row) {
			
		
	start_row();
	if($row['status'] == 0 )
		$bg = 'Open';
	else
	if($row['status'] ==1 )
		$bg = 'Closed';
	else
		if($row['status'] ==2 )
		$bg = 'In Progress';
	else
		$bg = null;
	alt_table_row_color($k, $bg);	
	
	$role="<br><span style='font-size:10px;'> Posted To :- ".$row['customer_name']."  </span>";
	
	label_cell($row["complaint_number"],'align=center');
		
	label_cell('<p><b>'.$row['user_name']."</b> ".'</p><p>'.sql2date($row['date']).' '.date('h:i a', strtotime($row['date']))."</p>","width = 20% align= center");
	
	if($row["status"]==0){
			hyperlink_params_td($path_to_root."/complaints/manage/complaint_replies.php","<b>".$row["subject"].$role."</b>","complaint_id=".$row['id']);
	}else{
	hyperlink_params_td($path_to_root."/complaints/manage/complaint_replies.php",$row["subject"].$role. "(".$row["project_name"]." ) "  ,"complaint_id=".$row['id']);
	}
	$replies = get_replies_count($row['id']);
	label_cell($replies,'align=center');
	
	if($row["status"]==0) label_cell(_('Open'), 'align=center');
	if($row["status"]==1) label_cell(_('Closed'), 'align=center');
	if($row["status"]==2) label_cell(_('In Progress'), 'align=center');	
	
	$complaint_attchment = get_complaint_attachment_by_complaint_id($row['id']);
	
	if($complaint_attchment["filename"]!='')
    button_cell('download'.$complaint_attchment["id"], _("Download"), _("Download"), ICON_DOWN);
    else
	label_cell("#");
	
	end_row();		
}	
	
}	


end_table(1);
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
