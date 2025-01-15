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
$page_security = 'SA_SUPPLIER_BANK_DETAILS';

$path_to_root = "../..";

	
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/purchasing/includes/db/supplier_bank_details_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows && $SysPrefs->use_popup_search)
	$js .= get_js_open_window(900, 500);//
page(_($help_context = "Supplier Bank Details"), false, false, "", $js);
//---------------------------------------------------------------------------------------------------

simple_page_mode(true);
//---------------------------------------------------------------------------------------------------
$input_error = 0;

if (isset($_GET['supplier_id']))
{
	$_POST['supplier_id'] = $_GET['supplier_id'];
}
//---------------------------------------------------------------------------------------------------
$action = $_SERVER['PHP_SELF'];
if ($page_nested)
	$action .= "?customer_id=".get_post('customer_id');
start_form(false, false, $action);
//----------------------------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{
		if (strlen($_POST['supp_bank_name']) == 0) 
	{
		display_error(_("The Bank name must be entered."));
		set_focus('supp_bank_name');
		return false;
	}
		if (strlen($_POST['supp_bank_account_no']) == 0) 
	{
		display_error(_("The Bank Account Number must be entered."));
		set_focus('supp_bank_account_no');
		return false;
	}
 $input_error =0;
   	
	if ($input_error != 1)
	{

    	if ($selected_id != -1) 
		{
			//editing an existing price
			update_supplier_bank_account($selected_id, $_POST['supp_bank_name'],$_POST['supp_bank_account_no'],$_POST['supplier_id'],$_POST['supp_bank_branch'],$_POST['supp_iban'],$_POST['supp_swift']);

			$msg = _("Bank Details have been updated.");
		}
		else
		{

			add_supplier_bank_account($_POST['supp_bank_name'], $_POST['supp_bank_account_no'],$_POST['supplier_id'],$_POST['supp_bank_branch'],$_POST['supp_iban'],$_POST['supp_swift']);

			$msg = _("Bank Details have been has been added.");
		}
		display_notification($msg);
		$Mode = 'RESET';
	}

}

//------------------------------------------------------------------------------------------------------

if ($Mode == 'Delete')
{
	//the link to delete a selected record was clicked
	delete_supplier_bank_account($selected_id);
	display_notification(_("The selected Bank Details has been deleted."));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
}

if (list_updated('supplier_id')) {
	$Ajax->activate('bank_table');
	$Ajax->activate('bank_details');
	unset($_POST);
}

//---------------------------------------------------------------------------------------------------
br();
br();
 $debtor_list = get_all_supplier_bank_account($_GET['supplier_id']);

div_start('bank_table');
start_table(TABLESTYLE, "width='60%'");

$th = array(_("#"),_("Bank Name"), _("Account Number"),  _("Bank Branch"),  _("IBAN"), _("Swift"),"","");
table_header($th);
$k = 0; //row colour counter
$i=1;
while ($myrow = db_fetch($debtor_list))
{

	alt_table_row_color($k);
	label_cell($i);

    label_cell($myrow["supp_bank_name"]);
    label_cell($myrow["supp_bank_account_no"]);
    
	label_cell($myrow["supp_bank_branch"]);
    label_cell($myrow["supp_iban"]);
    label_cell($myrow["supp_swift"]);
 	
	edit_button_cell("Edit".$myrow['id'], _("Edit"));
 	delete_button_cell("Delete".$myrow['id'], _("Delete"));
    end_row();
$i++;
}
end_table();
if (db_num_rows($debtor_list) == 0)
{
	if (get_company_pref('add_pct') != -1)
		$calculated = true;
	display_note(_("There are no Bank Details set up for this customer."), 1);
}
div_end();
//------------------------------------------------------------------------------------------------

echo "<br>";

if ($Mode == 'Edit')
{
	$myrow = get_supplier_bank_account_edit($selected_id);
	$_POST['supp_bank_name'] = $myrow["supp_bank_name"];
	$_POST['supp_bank_account_no'] = $myrow["supp_bank_account_no"];
	
	$_POST['supp_bank_branch'] = $myrow["supp_bank_branch"];
	$_POST['supp_iban'] = $myrow["supp_iban"];
	$_POST['supp_swift'] = $myrow["supp_swift"];
	
}
if($Mode != 'Edit')
{	
unset($_POST);
}
hidden('selected_id', $selected_id);

div_start('bank_details');
start_table(TABLESTYLE2);
text_row(_("Bank Name:"), 'supp_bank_name',null,40,60);
text_row(_("Account Number:"), 'supp_bank_account_no',null,30,60);
text_row(_("Bank Branch:"), 'supp_bank_branch', null, 30, 60);
text_row(_("IBAN:"), 'supp_iban', null, 30, 60);
text_row(_("Swift:"), 'supp_swift', null, 30, 60);

end_table(1);
submit_add_or_update_center($selected_id == -1, '', 'both');
div_end();

end_form();
end_page();
