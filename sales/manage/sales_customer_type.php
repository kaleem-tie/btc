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
$page_security = 'SA_CUST_TYPE';
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");

page(_($help_context = "Customer Type"));

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/db/sales_customer_type_db.inc");


simple_page_mode(true);
//-----------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	//initialise no input errors assumed initially before we test
	$input_error = 0;

	if (strlen($_POST['cust_type_name']) == 0) 
	{
		$input_error = 1;
		display_error(_("The Customer Type cannot be empty."));
		set_focus('cust_type_name');
		return false;
	}

    
	if($Mode=='ADD_ITEM')
     {
	$valid_cust_type_name = getvalid_sales_cust_type_masters(trim($_POST['cust_type_name']));
		
		 if(!empty(trim($valid_cust_type_name))){
		   $input_error = 1;
		   display_error(_("Customer Type should be unique."));
		   set_focus('cust_type_name');
		   return false;
		}
     }
	 
	 if($Mode=='UPDATE_ITEM')
     {
	$valid_cust_type = getvalid_sales_cust_type_masters_edit(trim($_POST['cust_type_name']),$selected_id);
		
		 if(!empty(trim($valid_cust_type))){
		   $input_error = 1;
		   display_error(_("Customer Type should be unique."));
		   set_focus('cust_type_name');
		   return false;
		}
     }
	 

	if ($input_error != 1) 
	{
		
    	if ($selected_id != -1) 
    	{
    		update_sales_cust_type_masters($selected_id, ucfirst($_POST['cust_type_name']), $_POST['description']);
			display_notification(_('Selected Customer Type has been updated'));
    	} 
    	else 
    	{
    		add_sales_cust_type_masters(ucfirst($_POST['cust_type_name']), $_POST['description']);
			
			display_notification(_('New Customer Type has been added'));
    	}
		$Mode = 'RESET';
	}
} 




//-----------------------------------------------------------------------------------



if ($Mode == 'Delete')
{
    if (key_in_foreign_table($selected_id, 'debtors_master', 'sale_cust_type_id'))
	{
		display_error(_("Cannot delete this customer type because Customers have been created using this customer type."));
	} 
	else
	{
		delete_sales_cust_type_masters($selected_id);
		display_notification(_('Selected Customer Type has been deleted'));
	}
	
	$Mode = 'RESET';
}


if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}



//-----------------------------------------------------------------------------------


start_form();

//------------------------------------------------------------------------------------
start_table(TABLESTYLE2);

if ($selected_id != -1) 
{
 	if ($Mode == 'Edit') {
		//editing an existing status code
		$myrow = get_sales_cust_type_masters($selected_id);
		
		$_POST['cust_type_name']  = $myrow["cust_type_name"];
		$_POST['description']  = $myrow["description"];
	}
	hidden('selected_id', $selected_id);
} 
start_row();
text_row_ex(_("Customer Type Name:<b style='color:red;'>*</b>"), 'cust_type_name', 50);

textarea_row(_("Description:"), 'description',"",40,5);
echo'<br>';echo'<br>';
end_row();
start_table(TABLESTYLE, "width='50%'");
$th = array(_("S.No"),_("Customer Type"),_("Description"), "", "");
inactive_control_column($th);
table_header($th);
echo'<br>';
submit_add_or_update_center($selected_id == -1, '', 'both');
echo'<br>';
end_form();
$result = get_all_sales_cust_type_masters(check_value('show_inactive'));
$k = 0;
$i = 1;
while ($myrow = db_fetch($result)) 
{
	
	alt_table_row_color($k);	

	label_cell($i,"align='center'");
	label_cell($myrow["cust_type_name"]);
	label_cell($myrow["description"]);
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'sales_cust_type', 'id');
 	edit_button_cell("Edit".$myrow["id"], _("Edit"));
 	delete_button_cell("Delete".$myrow["id"], _("Delete"));
	$i++;
	end_row();
}
inactive_control_row($th);
end_table();

end_table();

//------------------------------------------------------------------------------------

end_page();

