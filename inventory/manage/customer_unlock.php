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
$page_security = 'SA_CUSTOMER_UNLOCK';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Customer Unlock"));

include($path_to_root . "/sales/includes/db/customer_unlock_db.inc");

include($path_to_root . "/includes/ui.inc");

simple_page_mode(true);
//-----------------------------------------------------------------------------------

if (isset($_POST['Unlock'])) 
{
	update_customer_unlock($_POST['debtor_no']);
    display_notification(_('Customer Unlocked!'));
		$Ajax->activate('_page_body');
} 

if ($Mode == 'Delete')
{
    update_customer_lock($selected_id);
	display_notification(_('Customer Locked'));
}


$result = get_all_customer_requests();

start_form();
//-----------------------------------------------------------------------------------

start_table(TABLESTYLE2);
customer_lock_list_row(_("Customer:<b style='color:red;'>*</b>"), 'debtor_no', null,true);
end_table(1);
submit_center('Unlock', _('Unlock'), true, false, 'default');
end_form();
echo "<br>";

start_table(TABLESTYLE, "width='50%'");
$th = array(_("Customer Code"), _("Customer Name"),"");
inactive_control_column($th);
table_header($th);

echo '<br>';


$k = 0;
while ($myrow = db_fetch($result)) 
{
	alt_table_row_color($k);	
	label_cell($myrow["cust_code"]);
	label_cell($myrow["name"]);
	end_row();
}

inactive_control_row($th);
end_table(1);

end_page();

