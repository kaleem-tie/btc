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

$page_security = 'SA_BOUNCE_PDC';	

$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/db/inventory_db.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/sales/includes/sales_db.inc");

include($path_to_root . "/includes/ui.inc");
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
simple_page_mode(true);
page(_($help_context = "PDC Bounce Entry"));
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
			
		void_customer_pdc_bounce(ST_CUSTPDC, $pdc_number, $bounce_date);
		
		meta_forward($_SERVER['PHP_SELF'],  "AddedID=$return_no" );
}
}

function void_customer_pdc_bounce($type, $type_no ,$bounce_date)
{
		
	begin_transaction();

	hook_db_prevoid($type, $type_no);
	// void_bank_trans($type, $type_no, true); // need to check 
	// void_gl_trans($type, $type_no, true);  // need to check 
	void_cust_allocations($type, $type_no);
	void_pdc_customer_trans($type, $type_no);
	bounce_pdc_customer_trans($type, $type_no, $bounce_date);
	commit_transaction();
}
if (isset($_GET['AddedID'])) {
	$payment_no = $_GET['AddedID'];

	display_notification_centered(_("The customer PDC bounce has been successfully entered."));

	

	// submenu_view(_("&View this Customer return PDC"), ST_CUSTBNPDC, $payment_no);

	
	// display_note(get_gl_view_str(ST_CUSTBNPDC, $payment_no, _("&View the GL Journal Entries for this Customer PDC")));

	display_footer_exit();
}
//-------------------------------------------------------------------------------------
start_form(true);
//$receipt = get_customer_trans($_POST['PdcNumber'], ST_CUSTPDC);

$receipt = get_customer_trans_pdc($_POST['PdcNumber'], ST_CUSTPDC);

//display_error(json_encode($receipt));
//$_POST['pdc_number'] = $_
display_heading(sprintf(_("Customer PDC  #%d"),$_POST['PdcNumber']));
global $mode_payment_types;
echo "<br>";
start_table(TABLESTYLE, "width='80%'");
start_row();
label_cells(_("Reference"), $receipt['reference'], "class='tableheader2'");
label_cells(_("Date of Deposit"), sql2date($receipt['tran_date']), "class='tableheader2'");
date_cells(_("Bounce Date :"), 'bounce_date', '', null);
end_row();
start_row();
label_cells(_("Customer Currency"), $receipt['curr_code'], "class='tableheader2'");
label_cells(_("Amount"), price_format($receipt['Total'] - $receipt['ov_discount']), "class='tableheader2'");
label_cells(_("Discount"), price_format($receipt['ov_discount']), "class='tableheader2'");
// ref_cells(_("Reference"), 'ref', '', null, "class='tableheader2'", false, ST_CUSTBNPDC,null);
end_row();
start_row();

$bank_details=get_bank_details($receipt['bank_account']);
label_cells(_("Into Bank Account"), $bank_details['bank_account_name'].' ['.$bank_details['bank_curr_code'].']', "class='tableheader2'");
label_cells(_("Bank Amount"), price_format($receipt['bank_amount']), "class='tableheader2'");
label_cells(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']], "class='tableheader2'");
if($receipt['recall_status'] == '0'){
		label_cells(_("PDC  Recall Status"),'No',"class='tableheader2'");
	}else if($receipt['recall_status'] == '1'){
		label_cells(_("PDC Recall Status"),'Yes',"class='tableheader2'");
	}

end_row();


start_row();
label_cells(_("Mode Of Payment"), 'Cheque', "class='tableheader2'");
if($receipt['mode_of_payment']=='cheque')
{
	label_cells(_("Cheque No"), $receipt['cheque_no'], "class='tableheader2'");
	label_cells(_("Date Of Issue"), $receipt['date_of_issue'], "class='tableheader2'");
}else if($receipt['mode_of_payment']=='dd'){
	label_cells(_("DD No"), $receipt['dd_no'], "class='tableheader2'");
	label_cells(_("Date Of Issue"), $receipt['dd_date_of_issue'], "class='tableheader2'");
}

 else if($receipt['mode_of_payment'] == 'ot' || $receipt['mode_of_payment'] == 'neft' || $receipt['mode_of_payment'] == 'card' || $receipt['mode_of_payment'] == 'rtgs'){
	label_cells(_("Payment Reference No."), $receipt['pymt_ref'], "class='tableheader2'");
}
label_cells(_("PDC Cheque No"), $receipt['pdc_cheque_no'], "class='tableheader2'");
	label_cells(_("PDC Cheque Date"), sql2date($receipt['pdc_cheque_date']), "class='tableheader2'");
	// textarea_cells(_("Remarks"),'remarks',null,20,2);
	
comments_display_row(ST_CUSTPDC, $selected_id);

end_outer_table(1); // outer table

$voided = is_voided_display(ST_CUSTPDC, $selected_id, _("This customer payment has been voided."));

br(); br();

if (!$voided)
{

	display_pdc_allocations_from(PT_CUSTOMER, ST_CUSTPDC, $_POST['PdcNumber'], $receipt['Total']);
}


//start_outer_table(TABLESTYLE2, "width='70%'");
hidden('pdc_date',$receipt['tran_date']);
hidden('customer_id',$receipt['debtor_no']);
hidden('branch_id',$receipt['branch_code']);
hidden('dimension_id',$receipt['dimension_id']);
hidden('dimension2_id',$receipt['dimension2_id']);
hidden('bank_account',$receipt['bank_account']);
hidden('rate',$receipt['rate']);
hidden('pdc_cheque_no',$receipt['pdc_cheque_no']);
hidden('our_ref_no',$receipt['our_ref_no']);
hidden('pdc_cheque_date',$receipt['pdc_cheque_date']);
hidden('reference',$receipt['reference']);
hidden('PdcNumber',$_GET['PdcNumber']);
hidden('recall_status',$receipt['recall_status']);

//date_row(_("Return Date :"), 'return_date', '', true);
//ref_row(_("Reference:"), 'ref','' , null, '', ST_CUSTRTPDC);


submit_center_first('Submit', _("Bounce PDC "), '', 'default');
end_form();
//--------------------------------------------------------------------------------------
end_page();
