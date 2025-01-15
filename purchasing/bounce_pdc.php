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

$page_security = 'SA_SUPP_BOUNCE_PDC';	

$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/db/inventory_db.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");

include($path_to_root . "/includes/ui.inc");
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
simple_page_mode(true);
page(_($help_context = "Supplier PDC Bounce Entry"));
//-----------------------------------------------------------------------------------

if(isset($_GET['PdcNumber']))
{
$_POST['PdcNumber'] = $_GET['PdcNumber'];
}

if(isset($_POST['Submit'])){
	
	$input_error = 0;
	if(($_POST['pdc_date']) > date2sql($_POST['bounce_date'])){
		$input_error = 1;
		display_error(_("Entered bounce date is greater than pdc entry date"));
	}
	if($input_error == 0){
		
		
		$pdc_number = $_POST['PdcNumber'];
		$bounce_date = $_POST['bounce_date'];
		
		void_supplier_pdc_bounce(ST_SUPPPDC, $pdc_number, $bounce_date);
		
		meta_forward($_SERVER['PHP_SELF'],  "AddedID=$return_no" );
   }
}


function void_supplier_pdc_bounce($type, $type_no ,$bounce_date)
{
		
	begin_transaction();

	hook_db_prevoid($type, $type_no);
	void_supp_allocations($type, $type_no);
	void_supp_pdc_trans($type, $type_no);
	bounce_pdc_supplier_trans($type, $type_no, $bounce_date);
	commit_transaction();
}


if (isset($_GET['AddedID'])) {
	$payment_no = $_GET['AddedID'];

	display_notification_centered(_("The Supplier PDC Bounce has been successfully entered."));

	display_footer_exit();
}
//---------------------------------------------------------------------------------------
start_form(true);
$receipt = get_supp_trans($_POST['PdcNumber'], ST_SUPPPDC);

$company_currency = get_company_currency();

$show_currencies = false;
$show_both_amounts = false;

if (($receipt['bank_curr_code'] != $company_currency) || ($receipt['curr_code'] != $company_currency))
	$show_currencies = true;

if ($receipt['bank_curr_code'] != $receipt['curr_code']) 
{
	$show_currencies = true;
	$show_both_amounts = true;
}

echo "<center>";

display_heading(sprintf(_("Supplier PDC  #%d"),$_POST['PdcNumber']));

echo "<br>";
start_table(TABLESTYLE2, "width='80%'");
global $mode_payment_types;
start_row();
label_cells(_("To Supplier"), $receipt['supplier_name'], "class='tableheader2'");
$bank_details=get_supp_bank_details($receipt['bank_account']);
label_cells(_("From Bank Account"), $bank_details['bank_account_name'].' ['.$bank_details['bank_curr_code'].']', "class='tableheader2'");
label_cells(_("Date Paid"), sql2date($receipt['tran_date']), "class='tableheader2'");
date_cells(_("Bounce Date :"), 'bounce_date', '', null);
end_row();
start_row();
if ($show_currencies)
	label_cells(_("Payment Currency"), $bank_details['bank_curr_code'], "class='tableheader2'");
label_cells(_("Bank Amount"), number_format2(-$receipt['bank_amount'], user_price_dec()), "class='tableheader2'");
if ($receipt['ov_discount'] != 0)
	label_cells(_("Discount"), number_format2(-$receipt['ov_discount']*$receipt['rate'], user_price_dec()), "class='tableheader2'");
else
	label_cells(_("Payment Type"), "Cheque", "class='tableheader2'");
	label_cells(_("PDC  No"), $receipt['pdc_cheque_no'], "class='tableheader2'");
end_row();

start_row();
if ($show_currencies) 
{
	label_cells(_("Supplier's Currency"), $receipt['curr_code'], "class='tableheader2'");
}
if ($show_both_amounts)
	label_cells(_("Amount"), number_format2(-$receipt['Total'], user_price_dec()), "class='tableheader2'");
label_cells(_("PDC Reference"), $receipt['reference'], "class='tableheader2'");
label_cells(_("PDC  Date"), sql2date($receipt['pdc_cheque_date']), "class='tableheader2'");
if($receipt['recall_status'] == '0'){
		label_cells(_("PDC  Recall Status"),'No',"class='tableheader2'");
	}else if($receipt['recall_status'] == '1'){
		label_cells(_("PDC Recall Status"),'Yes',"class='tableheader2'");
	}

end_row();

if ($receipt['ov_discount'] != 0)
{
	start_row();
	label_cells(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']], "class='tableheader2'");
	end_row();
}

comments_display_row(ST_SUPPPDC, $selected_id);

$voided = is_voided_display(ST_SUPPPDC, $selected_id, _("This supplier return pdc has been voided."));

if (!$voided)
{
	display_allocations_from(PT_SUPPLIER, $receipt['supplier_id'], ST_SUPPPDC, $_POST['PdcNumber'], $receipt['Total']);
}


//start_outer_table(TABLESTYLE2, "width='70%'");
hidden('pdc_date',$receipt['tran_date']);
hidden('supplier_id',$receipt['supplier_id']);
hidden('dimension_id',0);
hidden('dimension2_id',0);
hidden('bank_account',$receipt['bank_account']);
hidden('rate',$receipt['rate']);
hidden('pdc_cheque_no',$receipt['pdc_cheque_no']);
hidden('our_ref_no',$receipt['our_ref_no']);
hidden('pdc_cheque_date',$receipt['pdc_cheque_date']);
hidden('reference',$receipt['reference']);
hidden('PdcNumber',$_GET['PdcNumber']);
hidden('recall_status',$receipt['recall_status']);



end_outer_table(1); // outer table
submit_center_first('Submit', _("Bounce PDC "), '', 'default');
end_form();
//--------------------------------------------------------------------------------------------------
end_page();
