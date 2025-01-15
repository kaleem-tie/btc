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
$page_security = 'SA_LOC_DIM_CASHACT';
$path_to_root = "..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Linking Location with Dimension and Cash Account"));

include($path_to_root . "/includes/db/loc_dim_cashact_db.inc");

include($path_to_root . "/includes/ui.inc");

simple_page_mode(true);

if ($Mode=='ADD_ITEM') 
{

	//initialise no input errors assumed initially before we test
	$input_error = 0;

	if ($input_error != 1) 
	{
		
    		add_loc_dim_cashact($_POST['loc_code'], $_POST['dimension_id'], $_POST['cash_account_id']);
			display_notification(_('Location is linked with Dimension and Cash Account'));
		$Mode = 'RESET';
	}
} 
//-----------------------------------------------------------------------------------

if ($Mode == 'Delete')
{
	//display_error($selected_id);
		delete_loc_dim_cashact($selected_id);
		display_notification(_('Removed Location linking with Dimension and Cash Account'));
		$selected_id = '-1';
	$Mode = 'RESET';
}

//-----------------------------------------------------------------------------------

$result = get_all_loc_dim_cashact_details();

start_form();
//-----------------------------------------------------------------------------------

start_table(TABLESTYLE2);

locations_list_row(_("Location:"), 'loc_code', null, false, false);
$dim = get_company_pref('use_dimension');
if ($dim > 0)
dimensions_list_row(_("Dimension").":", 'dimension_id', null, false, ' ', false, 1, false);
else
hidden('dimension_id', 0);
cash_accounts_list_row(_("Cash Account:"), 'cash_account_id', null, false);
end_table(1);
 //user_check_access('SA_LOC_DIM_CASHACT_ADDNEW') ? submit_center('ADD_ITEM', _("Add New"), true, '', 'default') : '';
//submit_add_or_update_center($id == 1, '', 'both');
submit_add_or_update_center($selected_id == -1, '', 'both');
start_table(TABLESTYLE, "width='80%'");
$th = array( _("Location"), _("Dimension"),_("Cash Account"), "");
inactive_control_column($th);
table_header($th);
echo '<br>';
end_form();
$k = 0;
 
while ($myrow = db_fetch($result)) 
{	
	alt_table_row_color($k);	
	label_cell($myrow["location"]);
	label_cell($myrow["dimension"]);
	label_cell($myrow["bank_name"]);
	//user_check_access('SA_LOC_DIM_CASHACT_DELETE') ? delete_button_cell("Delete".$myrow['id'], _("Delete")) : '';
	delete_button_cell("Delete".$myrow['id'], _("Delete"));
	end_row();
}

end_table(1);
//------------------------------------------------------------------------------------

end_page();

