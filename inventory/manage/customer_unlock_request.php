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
$page_security = 'SA_CUSTOMER_UNLOCK_REQUEST';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Customer Unlock Request"));

include($path_to_root . "/sales/includes/db/customer_unlock_db.inc");

include($path_to_root . "/includes/ui.inc");

simple_page_mode(true);
//-----------------------------------------------------------------------------------

if ($_GET['debtor_no'] != -1 && $_GET['debtor_no'] != "") 
{
	$_POST['debtor_no']=$_GET['debtor_no'];
} 

hidden('debtor_no',$_POST['debtor_no']);

if(isset($_POST['Request']))
{
	request_customer_unlock($_POST['debtor_no']);
	display_notification(_('Request has been Sent'));
}

start_form();
//-----------------------------------------------------------------------------------

start_table(TABLESTYLE2);
$result = get_customer_info($_POST['debtor_no']);
label_row(_("Customer Code:"), $result['cust_code']);
label_row(_("Customer Name:"), $result['name']);
end_table();
echo '<br>';
submit_center('Request', _('Request'), true, false, 'default');
end_form();

//------------------------------------------------------------------------------------

end_page();

