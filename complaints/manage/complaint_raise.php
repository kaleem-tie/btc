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
$page_security = 'SA_COMPLAINT';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include($path_to_root . "/complaints/includes/db/complaint_raise_db.inc");
//include($path_to_root . "sales/includes/sales_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Register a Complaint"), false, false, "", $js);

check_db_has_customers(_("There are no customers defined in the system. Please define a customer to add customer complaint."));

simple_page_mode(true);
$selected_component = $selected_id;
//--------------------------------------------------------------------------------------------------

if (isset($_GET['customer_id']))
{
	$_POST['customer_id'] = $_GET['customer_id'];
	$selected_parent =  $_GET['customer_id'];
}

//--------------------------------------------------------------------------------------------------
function on_submit($selected_parent, $selected_component=-1)
{
	
	
	
	if(empty($_POST['subject'])) 
	{
		display_error(_("Please Enter the Subject!."));
		set_focus('subject');
		return;
	}
	
	if(empty($_POST['description'])) 
	{
		display_error(_("Please Enter the Description."));
		set_focus('description');
		return;
	}
	
	if (!check_reference($_POST['complaint_number'], ST_COMPLAINT_REGISTER))
    {
			set_focus('complaint_number');
    		return false;
    }
	
	if(empty($_POST['reference'])) 
	{
		display_error(_("Please Enter the reference!."));
		set_focus('reference');
		return;
	}
	
	//File Upload start
	$filename = basename($_FILES['filename']['name']);
	$tmpname = $_FILES['filename']['tmp_name'];
		//$dir =   $path_to_root ."/complaints/register_attachments";
        $dir =  company_path()."/register_attachments";
		
		if (!file_exists($dir))
		{
			mkdir ($dir,0777);
			$index_file = "<?php\nheader(\"Location: ../index.php\");\n";
			$fp = fopen($dir."/index.php", "w");
			fwrite($fp, $index_file);
			fclose($fp);
		}
		$filesize = $_FILES['filename']['size'];
		$filetype = $_FILES['filename']['type'];
	
		$unique_name = random_id();
		
		//save the file
		move_uploaded_file($tmpname, $dir."/".$unique_name);
		
		
	   $filename2 = basename($_FILES['filename2']['name']);
	   $tmpname2 = $_FILES['filename2']['tmp_name'];
		//$dir =   $path_to_root ."/complaints/register_attachments";
        $dir =  company_path()."/register_attachments";
		
		if (!file_exists($dir))
		{
			mkdir ($dir,0777);
			$index_file = "<?php\nheader(\"Location: ../index.php\");\n";
			$fp = fopen($dir."/index.php", "w");
			fwrite($fp, $index_file);
			fclose($fp);
		}
		$filesize2 = $_FILES['filename2']['size'];
		$filetype2 = $_FILES['filename2']['type'];
		$unique_name2 = random_id();
		//save the file
		move_uploaded_file($tmpname2, $dir."/".$unique_name2);
		
		$filename3 = basename($_FILES['filename3']['name']);
	    $tmpname3 = $_FILES['filename3']['tmp_name'];
		//$dir =   $path_to_root ."/complaints/register_attachments";
        $dir =  company_path()."/register_attachments";
		
		if (!file_exists($dir))
		{
			mkdir ($dir,0777);
			$index_file = "<?php\nheader(\"Location: ../index.php\");\n";
			$fp = fopen($dir."/index.php", "w");
			fwrite($fp, $index_file);
			fclose($fp);
		}
		$filesize3 = $_FILES['filename3']['size'];
		$filetype3 = $_FILES['filename3']['type'];
	
		$unique_name3 = random_id();
		
		//save the file
		move_uploaded_file($tmpname3, $dir."/".$unique_name3);
	//File Upload end
	
	   if ($selected_component != -1)
	   {
				
		display_notification(_('Selected customer complaint has been registered'));
		$Mode = 'RESET';
				
	  }
	  else
	  {
		add_customer_complaint($selected_parent,$_POST['subject'],$_POST['description'],$_POST['complaint_number'],$_POST['complaint_against'],$_POST['reference'],
		$_POST['contact_person'],$_POST['mobile_number'],$_POST['stock_id'],
		$filename, $unique_name, $filesize, $filetype,
		$filename2, $unique_name2, $filesize2, $filetype2,
		$filename3, $unique_name3, $filesize3, $filetype3,$_POST['ref_date'], $_POST['do_date']);
		
		display_notification(_("A new customer complaint has been registered."));
		$path="../inquiry/complaints_inquiry.php?";
		meta_forward($path);
	  }
	
	//}
}

//--------------------------------------------------------------------------------------------------

if ($Mode == 'RESET')
{   $selected_id = '';
	$sav = get_post('show_inactive');
	$_POST['show_inactive'] = $sav;
	unset($_POST['subject']);
	unset($_POST['description']);
	unset($_POST);
}

//--------------------------------------------------------------------------------------------------

start_form(true);

start_form(false, true);
start_table(TABLESTYLE_NOBORDER);
start_row();

customer_list_cells(_("Select a customer: "), 'customer_id',null, false,true);

end_row();

if (list_updated('customer_id'))
{
	$selected_id = -1;
	$Ajax->activate('_page_body');
}
end_table();
br();

end_form();
//--------------------------------------------------------------------------------------------------

if (get_post('customer_id') != '')
{ //Parent Item selected so display bom or edit component
	$selected_parent = $_POST['customer_id'];
	if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM'){
		on_submit($selected_parent, $selected_id);
	}
	//--------------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE2);

	if ($selected_id != -1)
	{
 		if ($Mode == 'Edit') {
		
			
			$_POST['subject'] = $myrow["subject"];
			$_POST['description'] = $myrow["description"];
		}
		hidden('selected_id', $selected_id);
	}
	
	else
	{
	   // unset($_POST['subject']);
	    //unset($_POST['description']);
	}

	
start_table();	
table_section_title("Post Complaint");

text_row(_("Subject : <b style='color:red;'>*</b>"), 'subject', null, 60, 50);
label_row('', '&nbsp;');
textarea_row(_("Description : <b style='color:red;'>*</b>"), 'description', null, 50, 4, 100);

ref_row(_("Complaint Number:"), 'complaint_number', '', $Refs->get_next(ST_COMPLAINT_REGISTER), false, ST_COMPLAINT_REGISTER);

complaint_against_types_list_row(_("Complaint Against:"), 'complaint_against',null,true);

if (list_updated('complaint_against'))
{
	$Ajax->activate('_page_body');
}

if($_POST['complaint_against']==1){
complaint_sales_orders_list_row(_("Order Reference: <b style='color:red;'>*</b>:"),'reference', null, false, true,$_POST['customer_id']);	

}else if($_POST['complaint_against']==2){
complaint_sales_invoices_list_row(_("Invoice Reference: <b style='color:red;'>*</b>:"),'reference', null, false, true,$_POST['customer_id']);

}
else if($_POST['complaint_against']==3){
complaint_sales_payments_list_row(_("Receipt Reference: <b style='color:red;'>*</b>:"),'reference', null, false, true,$_POST['customer_id']);	

}
else{
text_row(_("Reference: <b style='color:red;'>*</b>"), 'reference', null, 60, 50);

}
date_row(_("Reference Date:"), 'ref_date');
date_row(_("DO Date:"), 'do_date');
sales_local_items_list_row(_("Item:"),'stock_id', null, false, true);

text_row(_("Contact Person Name:"), 'contact_person', null, 60, 50);

text_row(_("Mobile Number :"), 'mobile_number', null, 60, 50);

file_row(_("Attached File 1") . ":", 'filename', 'filename');
file_row(_("Attached File 2") . ":", 'filename2', 'filename2');
file_row(_("Attached File 3") . ":", 'filename3', 'filename3');

end_table(0);

br();br();
	
	submit_add_or_update_center($selected_id == -1, '', 'both');
	echo '<br>';
	//display_bom_items($selected_parent);
	
	end_form();
}
// ----------------------------------------------------------------------------------

end_page();

