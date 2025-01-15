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
$page_security = 'SA_RECALL_PDC';	

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
page(_($help_context = "Recall Customer PDC Entry"));
//-----------------------------------------------------------------------------------
if(isset($_GET['PdcNumber']))
{
$_POST['PdcNumber'] = $_GET['PdcNumber'];
}
if(isset($_POST['Submit'])){
	
	$input_error = 0;
	if(($_POST['pdc_date']) > date2sql($_POST['recall_date'])){
		$input_error = 1;
		display_error(_("Please select recall date greater than pdc date"));
	}
	if($input_error == 0){
			$pdc_number = $_POST['PdcNumber'];
			$company_record = get_company_prefs();
			$customer_id = $_POST['customer_id'];
			$dim1 = $_POST['dimension_id'];
			$dim2 = $_POST['dimension2_id'];
			$bank_account = $_POST['bank_account'];
			$rate = $_POST['rate'];
			$our_ref_no = $_POST['our_ref_no'];
			$pdc_cheque_no = $_POST['pdc_cheque_no'];
			$pdc_cheque_date = $_POST['pdc_cheque_date'];
			$ref = $_POST['reference'];
			$recall_remarks = $_POST['recall_remarks'];
			
		$pdc_amount = get_pdc_amount($pdc_number);
		$total = 0;
		$bank = get_bank_account($bank_account);
		
		/* $sql = "UPDATE ".TB_PREF."gl_trans SET is_pdc = '0' WHERE type='".ST_CUSTPDC."' AND type_no=".db_escape($pdc_number)."";
		$res = db_query($sql); */
		$recall_date = date2sql($_POST['recall_date']);
		
		$dsql = "UPDATE ".TB_PREF."debtor_trans SET recall_status='1',current_pdc_status='1', recall_date='$recall_date', recall_remarks='$recall_remarks'  WHERE type='".ST_CUSTPDC."' AND trans_no=".db_escape($pdc_number)."";
		$dres = db_query($dsql);
		
		/* $total += add_gl_trans(ST_CUSTPDC, $pdc_number, $_POST['recall_date'],
		$company_record['pdc_act'], $dim1, $dim2, '', -$pdc_amount,  $bank['bank_curr_code'], PT_CUSTOMER, $customer_id,"",$bank['bank_curr_code']==get_company_currency()?1:$rate);  */
		$debtors_account = $company_record["debtors_act"];
		$total += add_gl_trans_customer(ST_CUSTPDC, $pdc_number,  $_POST['recall_date'],
		$debtors_account, $dim1, $dim2, -$pdc_amount, $customer_id,"Cannot insert a GL transaction for the debtors account credit");
		
		$total += add_gl_trans(ST_CUSTPDC, $pdc_number, $_POST['recall_date'],
		$bank['account_code'], $dim1, $dim2, '', $pdc_amount,  $bank['bank_curr_code'], PT_CUSTOMER, $customer_id,"",$bank['bank_curr_code']==get_company_currency()?1:$rate);
		
		
		add_bank_trans(ST_CUSTPDC, $pdc_number, $bank_account, $ref,
		$_POST['recall_date'], $pdc_amount, PT_CUSTOMER, $customer_id,$bank['bank_curr_code'],"",$rate,'',0,'',0,'','','','','',$our_ref_no,$pdc_cheque_no,$pdc_cheque_date);
		
		$cus_pdcsql = "SELECT * FROM ".TB_PREF."cust_pdc_allocations WHERE trans_type_from='".ST_CUSTPDC."' AND trans_no_from = ".db_escape($pdc_number)." AND person_id=".db_escape($customer_id)."";
		$cust_pdcres = db_query($cus_pdcsql);
		$cust_pdc_results = db_fetch($cust_pdcres);
		
		$sql = "INSERT INTO ".TB_PREF."cust_allocations (amt, date_alloc,trans_type_from, trans_no_from, trans_no_to, trans_type_to, person_id)
		VALUES (".db_escape($pdc_amount).",".db_escape($recall_date).",".ST_CUSTPDC.", ".db_escape($pdc_number).", ".db_escape($cust_pdc_results['trans_no_to']).", ".db_escape($cust_pdc_results['trans_type_to']).", ".db_escape($customer_id).")";
		db_query($sql, "A customer allocation could not be added to the database");
		
		
		$cussql = "SELECT * FROM ".TB_PREF."cust_allocations WHERE trans_type_from='".ST_CUSTPDC."' AND trans_no_from = ".db_escape($pdc_number)." AND person_id=".db_escape($customer_id)."";
		
		
		
		$custres = db_query($cussql);
		
		while($custresult = db_fetch($custres)){
			
			$debtsql = "UPDATE ".TB_PREF."debtor_trans SET pdc_amt = pdc_amt - "
        	.db_escape($custresult['amt'])." WHERE trans_no = ".db_escape($custresult['trans_no_to'])." AND type = ".db_escape($custresult['trans_type_to'])." AND debtor_no=".db_escape($customer_id)." ";			
			db_query($debtsql);			
		}
		$pdcsql = "UPDATE ".TB_PREF."debtor_trans SET ov_amount = '0',alloc='0' WHERE trans_no = ".db_escape($pdc_number)." AND type = '".ST_CUSTPDC."' ";			
			db_query($pdcsql);
		meta_forward($path_to_root . '/sales/inquiry/customer_inquiry.php?');
}
}
//---------------------------------------------------------------------------------------
start_form(true);
$receipt = get_customer_trans($_POST['PdcNumber'], ST_CUSTPDC);
//display_error(json_encode($receipt));

display_heading(sprintf(_("Customer PDC #%d"),$_POST['PdcNumber']));
global $mode_payment_types;
echo "<br>";
start_table(TABLESTYLE, "width='80%'");
start_row();
label_cells(_("From Customer"), $receipt['DebtorName'], "class='tableheader2'");
label_cells(_("Reference"), $receipt['reference'], "class='tableheader2'");
label_cells(_("Date of Deposit"), sql2date($receipt['tran_date']), "class='tableheader2'");

end_row();
start_row();
label_cells(_("Customer Currency"), $receipt['curr_code'], "class='tableheader2'");
label_cells(_("Amount"), price_format($receipt['Total'] - $receipt['ov_discount']), "class='tableheader2'");
label_cells(_("Discount"), price_format($receipt['ov_discount']), "class='tableheader2'");
end_row();
start_row();

$bank_details=get_bank_details($receipt['bank_account']);
label_cells(_("Into Bank Account"), $bank_details['bank_account_name'].' ['.$bank_details['bank_curr_code'].']', "class='tableheader2'");
label_cells(_("Bank Amount"), price_format($receipt['bank_amount']), "class='tableheader2'");
label_cells(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']], "class='tableheader2'");
end_row();


start_row();
label_cells(_("Mode Of Payment"), $mode_payment_types[$receipt['mode_of_payment']], "class='tableheader2'");
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
label_cells(_("PDC  No"), $receipt['pdc_cheque_no'], "class='tableheader2'");
	label_cells(_("PDC  Date"), sql2date($receipt['pdc_cheque_date']), "class='tableheader2'");

comments_display_row(ST_CUSTPDC, $selected_id);

$voided = is_voided_display(ST_CUSTPDC, $selected_id, _("This customer payment has been voided."));

if (!$voided)
{
	display_allocations_from(PT_CUSTOMER, $receipt['debtor_no'], ST_CUSTPDC, $_POST['PdcNumber'], $receipt['Total']);
}


start_outer_table(TABLESTYLE2, "width='70%'");
hidden('pdc_date',$receipt['tran_date']);
hidden('customer_id',$receipt['debtor_no']);
hidden('dimension_id',$receipt['dimension_id']);
hidden('dimension2_id',$receipt['dimension2_id']);
hidden('bank_account',$receipt['bank_account']);
hidden('rate',$receipt['rate']);
hidden('pdc_cheque_no',$receipt['pdc_cheque_no']);
hidden('our_ref_no',$receipt['our_ref_no']);
hidden('pdc_cheque_date',$receipt['pdc_cheque_date']);
hidden('reference',$receipt['reference']);
hidden('PdcNumber',$_GET['PdcNumber']);

date_row(_("Clear Date :"), 'recall_date', '', true);
textarea_row(_("Remarks: "), 'recall_remarks', $_POST['recall_remarks'], 35, 5);
end_outer_table(1); // outer table
submit_center_first('Submit', _("Process "), '', 'default');
end_form();
//--------------------------------------------------------------------------------------
end_page();
