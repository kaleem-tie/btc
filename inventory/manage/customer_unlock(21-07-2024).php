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

if ($_POST['debtor_no'] != -1 && $_POST['debtor_no'] != "") 
{
	update_customer_unlock($_POST['debtor_no']);
    display_notification(_('Changes updated'));
} 
if ($Mode == 'Delete')
{
    update_customer_lock($selected_id);
	display_notification(_('Changes updated'));
}


$result = get_all_customer_lock(check_value('show_inactive'));

/* function copy_item_sub_category($category_id)
{
	
	while ($myrow = db_fetch($result))
	{
		
		$_POST['item_name'] = $myrow["item_name"];
		$_POST['category_id'] = $myrow["category_id"];
				
		//on_submit($category_id, -1);
	}
 } */
start_form();
//-----------------------------------------------------------------------------------

start_table(TABLESTYLE2);


//customer_lock_list_row(_("Customer:<b style='color:red;'>*</b>"), 'debtor_no', null,false);
customer_list_cells("Customer:", 'debtor_no', null, false, true, false, true);



end_table(1);
start_table(TABLESTYLE, "width='50%'");
$th = array(_("Debtor_no"), _("Name"),"");
inactive_control_column($th);
table_header($th);
submit_add_or_update_center($selected_id == $_POST['debtor_no'], '', 'both');
echo '<br>';
end_form();

$k = 0;
while ($myrow = db_fetch($result)) 
{
	
	
	alt_table_row_color($k);	

	
	//label_cell($myrow["debtor_no"]);
    label_cell($myrow["name"]);
	//inactive_control_cell($myrow["id"], $myrow["inactive"], 'item_sub_category', 'id');
 	//edit_button_cell("Edit".$myrow['id'], _("Edit"));
 	delete_button_cell("Delete".$myrow['debtor_no'], _("Delete"));
	end_row();
}

inactive_control_row($th);
end_table(1);




start_table(TABLESTYLE2);

$th_r = array(_("Customer Code"), _("Customer Name"),_("Credit Limit"),_("Current Credit"));
table_header($th_r);

$credit_customers = get_all_customer_credit();
$s = 0;
while ($myrow1 = db_fetch($credit_customers)) 
{
	if($myrow1["cur_credit"]<0){
	start_row();
	alt_table_row_color($s);	
	
	label_cell($myrow1["cust_code"], "align='center'");
	label_cell($myrow1["name"]);
	label_cell(price_format($myrow1["credit_limit"],3), "align='center'");
	label_cell(price_format($myrow1["cur_credit"],3), "align='center'");
	end_row();
	}
}


end_table();



//------------------------------------------------------------------------------------

end_page();

