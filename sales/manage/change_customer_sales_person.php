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
$page_security = 'SA_CHANGE_CUSTOMER_SP';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Change Customer Sales Person"));

include($path_to_root . "/includes/ui.inc");

simple_page_mode(true);

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{
	$input_error = 0;

	if (strlen($_POST['from_salesman_id']) == 0) 
	{
		$input_error = 1;
		display_error(_("From sales person cannot be empty."));
		set_focus('from_salesman_id');
	}
	
	if (strlen($_POST['to_salesman_id']) == 0) 
	{
		$input_error = 1;
		display_error(_("To sales person cannot be empty."));
		set_focus('to_salesman_id');
	}

	if ($input_error != 1)
	{
    	
			$to_salesman_id = $_POST['to_salesman_id'];
			$from_salesman_id = $_POST['from_salesman_id'];
			$sql = "UPDATE ".TB_PREF."cust_branch SET salesman=" . db_escape($to_salesman_id) . " WHERE salesman =" . db_escape($from_salesman_id) . "";
			if($_POST['customer_id']!='' && $_POST['customer_id']>0)
			{
				$customer_id = $_POST['customer_id'];
				$sql .=" AND debtor_no=" . db_escape($customer_id) . "";
			}
 			// $note = _('Selected sales area has been updated');
			db_query($sql,"The customer sales person could not be updated");
		display_notification(_('Selected customer sales person has been updated'));
    	    
		display_notification($note);    	
		$Mode = 'RESET';
	}
} 



if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}

//-------------------------------------------------------------------------------------------------

start_table(TABLESTYLE2);
start_form();
sales_persons_list_row( _("From Sales Person:"), 'from_salesman_id', null);
customer_list_row(_("Customer:"), 'customer_id', null, false, true);
sales_persons_list_row( _("To Sales Person:"), 'to_salesman_id', null); 

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
