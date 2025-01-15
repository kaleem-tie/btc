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
	
	
	if ($selected_component != -1)
	{
				
		display_notification(_('Selected customer complaint has been registered'));
		$Mode = 'RESET';
				
	}
	else
	{
		add_customer_complaint($selected_parent,$_POST['subject'],$_POST['description'],$_POST['complaint_number'],$_POST['complaint_against'],$_POST['reference'],
		$_POST['contact_person'],$_POST['mobile_number'],$_POST['stock_id']);
		display_notification(_("A new customer complaint has been registered."));
		$path="../inquiry/complaints_inquiry.php?";
		meta_forward($path);
	}
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

start_form();

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
	if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM')
		on_submit($selected_parent, $selected_id);
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
textarea_row(_("Description : <b style='color:red;'>*</b>"), 'description', null, 60, 8);

ref_row(_("Complaint Number:"), 'complaint_number', '', $Refs->get_next(ST_COMPLAINT_REGISTER), false, ST_COMPLAINT_REGISTER);

complaint_against_types_list_row(_("Complaint Against :"), 'complaint_against',null,false);

text_row(_("Reference: <b style='color:red;'>*</b>"), 'reference', null, 60, 50);

sales_local_items_list_row(_("Item:"),'stock_id', null, false, true);

text_row(_("Contact Person Name:"), 'contact_person', null, 60, 50);

text_row(_("Mobile Number :"), 'mobile_number', null, 60, 50);

end_table(0);

br();br();
	
	submit_add_or_update_center($selected_id == -1, '', 'both');
	echo '<br>';
	//display_bom_items($selected_parent);
	
	end_form();
}
// ----------------------------------------------------------------------------------

end_page();

