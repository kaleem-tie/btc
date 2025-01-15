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
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include($path_to_root . "/complaints/includes/db/complaint_replies_db.inc");
//include($path_to_root . "/modules/ExtendedHRM/includes/Payroll.inc" );
include($path_to_root . "/includes/ui.inc");
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
//simple_page_mode(true);
page(_($help_context = "Complaints Updates"));
//-----------------------------------------------------------------------------------
if(isset($_GET['complaint_id']) && $_GET['complaint_id'] > 0 ){
	$_POST['complaint_id'] = $_GET['complaint_id'];
	
}

simple_page_mode_complaint(true);
function simple_page_mode_complaint($numeric_id = true)
{
	global $Ajax, $Mode, $selected_id;

	$default = $numeric_id ? -1 : '';
	$selected_id = get_post('selected_id', $default);
	foreach (array('ADD_ITEM', 'UPDATE_ITEM', 'RESET', 'CLONE') as $m) {
		if (isset($_POST[$m])) {
			$Ajax->activate('_page_body');
			if ($m == 'RESET'  || $m == 'CLONE') 
				$selected_id = $default;
			unset($_POST['_focus']);
			$Mode = $m; return;
		}
	}
	foreach (array('Edit', 'Delete', 'Reply') as $m) {
		foreach ($_POST as $p => $pvar) {
			if (strpos($p, $m) === 0) {
				unset($_POST['_focus']); // focus on first form entry
				$selected_id = quoted_printable_decode(substr($p, strlen($m)));
				$Ajax->activate('_page_body');
				$Mode = $m;
				return;
			}
		}
	}
	$Mode = '';
}


if(isset($_POST['closed']) && can_process()){
	
	
	$complaint_attch_id = $_POST["complaint_id"];
	
	$current_user_id =$_SESSION['wa_current_user']->user;

	ComplaintUpdate1("proj_customer_complaint",array("id"=>$_POST["complaint_id"]),array("inactive"=>1));

	if(trim($_POST['reply']) != ''){
		InsertComplaint("proj_complaint_replies",array("complaint_id"=>$_POST["complaint_id"],"comment"=>$_POST['reply'],"current_user_id"=>$_POST["current_user_id"],"technician_name"=>$_POST['technician_name']));
	}

 
	$_POST['reply'] ="";
	display_notification(_('Complaint has been closed'));
	
	meta_forward( $path_to_root.'/complaints/manage/complaints_attachments.php', "complaint_id=$complaint_attch_id");
	
}



if(isset($_POST['reopen'])){

	
	ComplaintUpdate1("proj_customer_complaint",array("id"=>$_POST["complaint_id"]),array("inactive"=>0));
	display_notification(_('Complaint has been reopened'));
	
}



if (isset($_POST["replied"]) && can_process()) {
	
	$current_user_id =$_SESSION['wa_current_user']->user;
		
		
	$reply_id =InsertComplaint("proj_complaint_replies",array("complaint_id"=>$_POST["complaint_id"],"comment"=>$_POST['reply'],"current_user_id"=>$current_user_id,'replies'=>$_POST['reply_id'],"technician_name"=>$_POST['technician_name']));

    

	// if($_SESSION['wa_current_user']->user_type == ST_EMPLOYEE){
	ComplaintUpdate1("proj_customer_complaint",array("id"=>$_POST["complaint_id"]),array("inactive"=>2));
	// }	
	unset($_POST['reply']);
	unset($_POST['technician_name']);
	$Ajax->activate('_page_body');
	display_notification(_('New Reply has been added'));
}


if(isset($_POST['cancel'])){

	meta_forward($_SERVER['PHP_SELF'], "complaint_id=".$_POST['complaint_id']);
}


if (isset($_POST['update']))
{

	ComplaintUpdate1("proj_complaint_replies", array('id' => $selected_id) , array("comment" =>$_POST['reply'],"technician_name"=>$_POST['technician_name']));
	display_notification(_('Selected reply has been updated'));
	$Mode = 'RESET';
	$_POST['reply'] ="";
	unset($_POST['technician_name']);
}



if ($Mode == 'Delete'){
if($selected_id =='complaint'){
	DeleteComplaint('proj_customer_complaint',array('id'=>$_POST['complaint_id']),'0_');
	meta_forward( $path_to_root.'/complaints/inquiry/complaints_inquiry.php?/');
}else{
	DeleteComplaint("proj_complaint_replies",array('id'=>$selected_id));

}

display_notification(_('Selected reply has been deleted'));
$Mode = 'RESET';
}


function can_process()
{
	$return = CheckEmpty_nicEdit('reply');
	return $return;
}


if(isset($_POST['submit_reply']) && CheckEmpty_nicEdit('complaint_reply')){

   $current_user_id =$_SESSION['wa_current_user']->user;
		
	$id =InsertComplaint("proj_complaint_replies",array("complaint_id"=>$_POST["complaint_id"],"comment"=>$_POST['complaint_reply'],"current_user_id"=>$current_user_id, 'replies' => $_POST['reply_id']));


}


//-----------------------------------------------------------------------------------

function CheckEmpty_nicEdit($name){
	
	if( $Mode != 'Reply' &&  $selected_id != $row['id']){
	 if (trim($_POST['technician_name']) == '')
	 {
		display_error(_("The Handled by cannot be empty."));
		set_focus('technician_name');
		return false;
	 }
	}
	
	if (trim($_POST[$name]) == '')
	{
		display_error(_("The $name cannot be empty."));
		set_focus($name);
		return false;
	}
	
	return true;
}


if (isset($_POST['complaint_id'])) {    // open complaint_id
	
	
	
start_form();
div_start('Complaint_Details');
start_table(TABLESTYLE_NOBORDER, "width='80%'");

$result = GetComplaintDataJoin1("proj_customer_complaint AS complaints",array(0=>array("join"=>"LEFT","table_name"=>"users AS users","conditions"=>"complaints.current_user_id = users.id")),array("complaints.*","users.real_name AS username"),array("complaints.id"=>$_POST['complaint_id']));


$replies = GetComplaintDataJoin1("proj_complaint_replies AS creplies",array(
	0=>array("join"=>"LEFT","table_name"=>"proj_customer_complaint AS complaints","conditions"=>"complaints.id=creplies.complaint_id"),
	1=>array("join"=>"LEFT","table_name"=>"users AS users","conditions"=>"users.id=creplies.current_user_id")),
array("creplies.*","users.real_name AS real_name"),
array("creplies.complaint_id"=>$_POST['complaint_id'],"replies"=>0),
array("creplies.date"=>"ASC"));

$sub_replies  = GetComplaintDataJoin1("proj_complaint_replies AS creplies",array(
	0=>array("join"=>"LEFT","table_name"=>"proj_customer_complaint AS complaints","conditions"=>"complaints.id=creplies.complaint_id"),
	1=>array("join"=>"LEFT","table_name"=>"users AS users","conditions"=>"users.id=creplies.current_user_id")),
array("creplies.*","users.real_name AS real_name"),
array("creplies.complaint_id"=>$_POST['complaint_id'],"replies"=>['0', '', '!=']),
array("creplies.date"=>"ASC"));



$k=0;
$is_closed=false;
if(! empty($result)){
	
	$row = $result[0]; //foreach ($result as $row) {
	if($row['inactive'] ==1){
			$is_closed=true;
		}
	//display_error(json_encode($row));	
		
	table_section_title("Replies", 5);
	$title =$row["subject"];
	
	start_row();
	alt_table_row_color($k,"");	
	
		
	label_cell('<p><b>'.$row["username"]."</b> ".'</p><p>'.sql2date($row['date']).' '.date('h:i a', strtotime($row['date']))."</p>","width = 20% align= center");
	
	
	label_cell("Topic : <b>".$row["subject"].'</b>
    <br><P>'.htmlspecialchars_decode($row["description"])."</P>
	Customer : <b>".get_complaint_customer_name($row["customer_id"])."</b> 
    <br>	
    Against : <b>".$complaint_against_types[$row["complaint_against"]]."</b> 
    <br> Reference : <b>".$row["reference"]."</b><br> Reference Date : <b>".sql2date($row["ref_date"])."</b>
	<br> Do Date : <b>".sql2date($row["do_date"])."</b>");
	
	
		if( !$is_closed && !GetComplaintSingleValue('proj_complaint_replies','complaint_id',array('complaint_id'=>$row['id']))){	
		
				label_cell('');
				edit_button_cell("Edit".'complaint', _("Edit"));
				if(!GetComplaintSingleValue('proj_complaint_replies','complaint_id',array('complaint_id'=>$row['id']))){
					delete_button_cell("Delete".'complaint', _("Delete"));
				}else{
					label_cell('');
				}
			}else{
				
				label_cell('');label_cell('');label_cell('');
			}
			end_row();
			
}

$k=3;
if(! empty($replies)){
	foreach ($replies as $row) {
		$hr ="<hr>";
		$thr="";
		if(!next($replies)){
			$hr="";
			$thr="<hr >";
		}
	if (true) {
		if($row["id"]>0){
			start_row("id ='".$row['id']."'");
				alt_table_row_color($k,"c_replies");
				
				
			
			
			label_cell('<p><b>'.$row["real_name"]."</b> ".'</p><p>'.sql2date($row['date']).' '.date('h:i a', strtotime($row['date']))."</p>","width = 20% align= center");
			
			
			label_cell("Reply : <b>".$title."</b> 
			<P>Handled By : <b>".$row["technician_name"]."</P></b>
			<P>".htmlspecialchars_decode($row["comment"])."</P>".$hr,"width=60%");
			
			
			
			if( $Mode == 'Reply' &&  $selected_id == $row['id'] ){
					label_cell('');
				} else if(!$is_closed){
					button_cell("Reply".$row['id'],"Reply");
				}else{
					label_cell('');
				}
				
				if(!$is_closed){
					edit_button_cell("Edit".$row['id'], _("Edit"));
					if(GetComplaintSingleValue('proj_complaint_replies','complaint_id',array('complaint_id'=>$_POST['complaint_id'],'replies'=>'0'))){
						delete_button_cell("Delete".$row['id'], _("Delete"));
					}else{
						label_cell('');
					}
		 				
		 		} else {
		 			label_cell('');
		 			label_cell('');
		 		}
				end_row();
			

			
			  //for sub replies
				if(!empty($sub_replies)){
					
					foreach ($sub_replies as $sub_row) {
						if($row['id'] == $sub_row['replies']){							
							start_row();
							alt_table_row_color($k,"t_sub_replies");

								$contact_type=null;
								$debtor_name='';
								
							
							
							label_cell('<p><b>'.$row["real_name"]."</b> ".'</p><p>'.sql2date($row['date']).' '.date('h:i a', strtotime($row['date']))."</p>","width = 20% align= center");

							label_cell($thr."<P>".htmlspecialchars_decode($sub_row["comment"])."</P>".$hr,"width=60%");

							if( !$is_closed){
								label_cell('');
								edit_button_cell("Edit".$sub_row['id'], _("Edit"));
					 			delete_button_cell("Delete".$sub_row['id'], _("Delete"));	
					 		} else {
					 			label_cell('');
					 			label_cell('');
					 			label_cell('');
					 		}
							end_row();
						}
					}	
				}
				
				
			if( $Mode == 'Reply' &&  $selected_id == $row['id']){
					
					$sub_reply_id=$selected_id;
					
					
					start_row();
					textarea_cells(_("Reply :"),'complaint_reply',null,50,4,100);
					echo '<td colspan="3">' .submit("submit_reply","Submit Reply", false).submit("cancel_reply","Cancel", false).'</td>';
					end_row();
				}	
			
		}	

    }		
		
		
	}
}


if ($Mode == 'Edit') {
	if($selected_id =='complaint'){
		meta_forward( $path_to_root.'/complaints/manage/complaint_raise.php?','complaint_id='.$_POST['complaint_id']);
	}
	$where = array('c_replies.id' => $selected_id);
	
	$myrow = GetRow('proj_complaint_replies AS c_replies',$where);
	if($myrow && is_array($myrow)){
		$_POST['reply']  = $myrow["comment"];
		$_POST['technician_name']  = $myrow["technician_name"];
	} else 
		display_warning(_("You can't edit this comment(reply)"));
		
	$Ajax->activate('Complaint_Details');
}
if ($Mode == 'Reply') {
	$Ajax->activate('Complaint_Details');
}

hidden('reply_id', (isset($sub_reply_id)? $sub_reply_id  : 0));
	hidden('selected_id', $selected_id);
	hidden('complaint_id', $_POST['complaint_id']);
	
	
if (!$is_closed) {
	label_row('', '&nbsp;');
	text_row(_("Handled By:"), 'technician_name', null, 50,60);
	
	if( $Mode != 'Reply' &&  $selected_id != $row['id']){
	textarea_row(_("Reply :"),'reply',null,50,4,100);
	}
	
	
	
	end_table(1);
	div_end();
	
	
	if ($Mode == 'Edit') {
		submit_center_first("update","Update");
		submit_center_last("cancel","Cancel");	
	} else {
		submit_center_first("replied","Reply");
		submit_center_last("closed","Close");	
	}
}else{
	end_table(1);
	div_end();
	submit_center("reopen","Reopen");
}



}   // close complaint_id


end_form();
end_page();
?>
<style>
	tr.t_sub_replies td:nth-child(1)	p {line-height: 12px;}
	tr.t_replies td:nth-child(1)	p{line-height: 12px;}

	tr.t_sub_replies td:nth-child(2)	p {line-height: 1.5em;text-align: justify;}
	tr.t_replies td:nth-child(2)	p{line-height: 1.5em;text-align: justify;}

	tr.t_sub_replies td:nth-child(2){ background-color: #edf1f5 !important;padding-left: 7%;}
	tr.t_sub_replies td:nth-child(3){ background-color: #edf1f5 !important;}
	tr.t_sub_replies td:nth-child(4){ background-color: #edf1f5 !important;}
	tr.t_sub_replies td:nth-child(5){ background-color: #edf1f5 !important;}		

	tr.t_replies td:nth-child(2){ background-color: #edf1f5 !important;padding-left: 3%;}
	tr.t_replies td:nth-child(3){ background-color: #edf1f5 !important;}
	tr.t_replies td:nth-child(4){ background-color: #edf1f5 !important;}
	tr.t_replies td:nth-child(5){ background-color: #edf1f5 !important;}	
}	
</style>
