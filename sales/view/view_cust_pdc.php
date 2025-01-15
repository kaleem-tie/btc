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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 600);

page(_($help_context = "View Customer PDC"), true, false, "", $js);

if (isset($_GET["trans_no"]))
{
	$trans_id = $_GET["trans_no"];
}
//display_error($trans_id);
$receipts = get_multiple_customer_trans($trans_id, ST_CUSTPDC);
//$receipt = get_customer_trans($trans_id, ST_CUSTPDC);

$totalAmount = 0;
$discountAmount = 0;
$reference = '';
$transDate = '';
$currencyCode = '';
$discount = 0;
$bankAccount = '';
$bankAccountName = '';
$bankAmount = 0;
$bankTransType = '';
$modeOfPayment = '';
$chequeNo = '';
$dateOfIssue = '';
$ddNo = '';
$ddDateOfIssue = '';
$paymentReference = '';
$pdcChequeNo = '';
$pdcChequeDate = '';


//display_error("recept type is ".gettype($receipts)." receipts count: ".count($receipts).""." - ".implode(',', $receipts));
foreach ($receipts as $idx => $receipt) {
	$totalAmount += ($receipt['ov_amount'] - $receipt['ov_discount']);
	$discountAmount+=$receipt['ov_discount'];
	if ($idx == 0) {
		$reference = $receipt['reference'];
		$transDate = $receipt['tran_date'];
		$currencyCode = $receipt['curr_code'];
		$discount = $receipt['ov_discount'];
		$bankAccount = $receipt['bank_account'];
		$bankTransType = $receipt['BankTransType'];
		$modeOfPayment = $receipt['mode_of_payment'];
		$chequeNo = $receipt['cheque_no'];
		$dateOfIssue = $receipt['date_of_issue'];
		$ddNo = $receipt['dd_no'];
		$ddDateOfIssue = $receipt['dd_date_of_issue'];
		$paymentReference = $receipt['pymt_ref'];
		$pdcChequeNo = $receipt['pdc_cheque_no'];
		$pdcChequeDate = $receipt['pdc_cheque_date'];
		$bank_details=get_bank_details($receipt['bank_account']);
		$bankAccountName = $bank_details['bank_account_name'].' ['.$bank_details['bank_curr_code'].']';
		$bankAmount = $receipt['bank_amount'];
	}
}
$totalAmount = price_format($totalAmount);

display_heading(sprintf(_("Customer PDC #%d"),$trans_id));
global $mode_payment_types;
echo "<br>";

start_table(TABLESTYLE, "width='80%'");
start_row();
//label_cells(_("From Customer"), $receipt['DebtorName'], "class='tableheader2'");
label_cells(_("Reference"), $receipt['reference'], "class='tableheader2'");
label_cells(_("Date of Deposit"), sql2date($transDate), "class='tableheader2'");
end_row();
start_row();
label_cells(_("Customer Currency"), $currencyCode, "class='tableheader2'");
label_cells(_("Amount"), price_format($totalAmount), "class='tableheader2'");
label_cells(_("Discount"), price_format($discountAmount), "class='tableheader2'");
end_row();
start_row();

$bank_details=get_bank_details($receipt['bank_account']);
label_cells(_("Into Bank Account"), $bankAccountName, "class='tableheader2'");
label_cells(_("Bank Amount"), price_format($bankAmount), "class='tableheader2'");
label_cells(_("Payment Type"), $bank_transfer_types[$bankTransType], "class='tableheader2'");
end_row();


start_row();
label_cells(_("Mode Of Payment"), "cheque", "class='tableheader2'");
if($modeOfPayment=='cheque')
{
	label_cells(_("Cheque No"), $chequeNo, "class='tableheader2'");
	label_cells(_("Date Of Issue"), $dateOfIssue, "class='tableheader2'");
}else if($modeOfPayment=='dd'){
	label_cells(_("DD No"), $ddNo, "class='tableheader2'");
	label_cells(_("Date Of Issue"), $ddDateOfIssue, "class='tableheader2'");
}

 else if($modeOfPayment == 'ot' || $modeOfPayment == 'neft' || $modeOfPayment == 'card' || $modeOfPayment == 'rtgs'){
	label_cells(_("Payment Reference No."), $paymentReference, "class='tableheader2'");
}
label_cells(_("PDC  No"), $pdcChequeNo, "class='tableheader2'");
label_cells(_("PDC  Date"), sql2date($pdcChequeDate), "class='tableheader2'");
end_row();

comments_display_row(ST_CUSTPDC, $trans_id);

end_table(1);

start_table(TABLESTYLE, "width='80%'");
$th = array(_("From Customer"), _("Discount"), _("Amount"));
table_header($th);
$k = 0; //row colour counter
foreach ($receipts as $idx => $receipt)
{

	alt_table_row_color($k);
	
	
	label_cell($receipt["DebtorName"]);
	amount_cell($receipt["ov_discount"]);
	amount_cell($receipt["ov_amount"]);
	end_row();
}
	//END WHILE LIST LOOP
end_table();





// start_table(TABLESTYLE, "width='80%'");
// start_row();
// label_cells(_("From Customer"), $receipt['DebtorName'], "class='tableheader2'");
// label_cells(_("Reference"), $receipt['reference'], "class='tableheader2'");
// label_cells(_("Date of Deposit"), sql2date($receipt['tran_date']), "class='tableheader2'");
// end_row();
// start_row();
// label_cells(_("Customer Currency"), $receipt['curr_code'], "class='tableheader2'");
// label_cells(_("Amount"), price_format($receipt['Total'] - $receipt['ov_discount']), "class='tableheader2'");
// label_cells(_("Discount"), price_format($receipt['ov_discount']), "class='tableheader2'");
// end_row();
// start_row();

// $bank_details=get_bank_details($receipt['bank_account']);
// label_cells(_("Into Bank Account"), $bank_details['bank_account_name'].' ['.$bank_details['bank_curr_code'].']', "class='tableheader2'");
// label_cells(_("Bank Amount"), price_format($receipt['bank_amount']), "class='tableheader2'");
// label_cells(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']], "class='tableheader2'");
// end_row();


// start_row();
// label_cells(_("Mode Of Payment"), $mode_payment_types[$receipt['mode_of_payment']], "class='tableheader2'");
// if($receipt['mode_of_payment']=='cheque')
// {
// 	label_cells(_("Cheque No"), $receipt['cheque_no'], "class='tableheader2'");
// 	label_cells(_("Date Of Issue"), $receipt['date_of_issue'], "class='tableheader2'");
// }else if($receipt['mode_of_payment']=='dd'){
// 	label_cells(_("DD No"), $receipt['dd_no'], "class='tableheader2'");
// 	label_cells(_("Date Of Issue"), $receipt['dd_date_of_issue'], "class='tableheader2'");
// }

//  else if($receipt['mode_of_payment'] == 'ot' || $receipt['mode_of_payment'] == 'neft' || $receipt['mode_of_payment'] == 'card' || $receipt['mode_of_payment'] == 'rtgs'){
// 	label_cells(_("Payment Reference No."), $receipt['pymt_ref'], "class='tableheader2'");
// }
// label_cells(_("PDC  No"), $receipt['pdc_cheque_no'], "class='tableheader2'");
// 	label_cells(_("PDC  Date"), sql2date($receipt['pdc_cheque_date']), "class='tableheader2'");

// comments_display_row(ST_CUSTPDC, $trans_id);

// end_table(1);

$voided = is_voided_display(ST_CUSTPDC, $trans_id, _("This customer payment has been voided."));

if (!$voided)
{
	display_allocations_from(PT_CUSTOMER, $receipt['debtor_no'], ST_CUSTPDC, $trans_id, $receipt['Total']);
}

end_page(true, false, false, ST_CUSTPDC, $trans_id);
