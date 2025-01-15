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

$page_security = 'SA_RETURN_PDC';	

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
page(_($help_context = "PDC Return Entry"));
//-----------------------------------------------------------------------------------

if(isset($_GET['PdcNumber']))
{
$_POST['PdcNumber'] = $_GET['PdcNumber'];
}
if(isset($_POST['Submit'])){
	
	$input_error = 0;
	if(($_POST['pdc_date']) > date2sql($_POST['return_date'])){
		$input_error = 1;
		display_error(_("Please select return date greater than pdc date"));
	}
	if($input_error == 0){
		$pdc_number = $_POST['PdcNumber'];
			$company_record = get_company_prefs();
			$customer_id = $_POST['customer_id'];
			$branch_id = $_POST['branch_id'];
			$dim1 = $_POST['dimension_id'];
			$dim2 = $_POST['dimension2_id'];
			$bank_account = $_POST['bank_account'];
			$rate = $_POST['rate'];
			$our_ref_no = $_POST['our_ref_no'];
			$pdc_cheque_no = $_POST['pdc_cheque_no'];
			$pdc_cheque_date = sql2date($_POST['pdc_cheque_date']);
			$ref = $_POST['ref'];
			$recall_status = $_POST['recall_status'];
			$bank_name = $_POST['bank_name'];
			
			$pdc_amount = get_pdc_amount($pdc_number);
			 $trans_no = get_next_trans_no(ST_CUSTRTPDC);
			 
			 $sql = "UPDATE ".TB_PREF."gl_trans SET is_pdc = '0' WHERE type='".ST_CUSTPDC."' AND type_no=".db_escape($pdc_number)."";
		$res = db_query($sql);
			 
			 $dsql = "UPDATE ".TB_PREF."debtor_trans SET return_status='1',return_date =".db_escape(date2sql($_POST['return_date'])).",current_pdc_status = '2' WHERE type='".ST_CUSTPDC."' AND trans_no=".db_escape($pdc_number)."";
			 
		$dres = db_query($dsql);
			
			$return_no = write_customer_trans(ST_CUSTRTPDC, $trans_no, $customer_id, $branch_id, $_POST['return_date'], $ref, $pdc_amount, 0,0,0,0,0,0,0,"",0,0, $dim1, $dim2,"",0,0,0,"",0,0,"",0,0,0,0,0,"","",0,"",0,0,"",0,0,$our_ref_no, $pdc_cheque_no,$pdc_cheque_date, $bank_name ,$bank_account);
			
			add_comments(ST_CUSTRTPDC, $return_no, $_POST['return_date'],'PDC No:'.$pdc_cheque_no.' PDC DATE:'.$pdc_cheque_date.' Remarks: '.$_POST['remarks']);

		$Refs->save(ST_CUSTRTPDC, $return_no, $ref);

			
		$company_record = get_company_prefs();
	
		if ($branch_id != ANY_NUMERIC) {

		$branch_data = get_branch_accounts($branch_id);

		$debtors_account = $branch_data["receivables_account"];
		$discount_account = $branch_data["payment_discount_account"];

	} else {
		$debtors_account = $company_record["debtors_act"];
		$discount_account = $company_record["default_prompt_payment_act"];
	}
		
		$total = 0;
		$bank = get_bank_account($bank_account);
		
		
		if($recall_status == '0'){
		$total += add_gl_trans(ST_CUSTRTPDC, $return_no, $_POST['return_date'],
		$company_record['pdc_act'], $dim1, $dim2, '', -$pdc_amount,  $bank['bank_curr_code'], PT_CUSTOMER, $customer_id,"",$bank['bank_curr_code']==get_company_currency()?1:$rate); 
		
		$total += add_gl_trans(ST_CUSTRTPDC, $return_no, $_POST['return_date'],
		$debtors_account, $dim1, $dim2, '', $pdc_amount,  $bank['bank_curr_code'], PT_CUSTOMER, $customer_id,"",$bank['bank_curr_code']==get_company_currency()?1:$rate);
		}else{
			
			$total += add_gl_trans(ST_CUSTRTPDC, $return_no, $_POST['return_date'],
		$bank['account_code'], $dim1, $dim2, '', -$pdc_amount,  $bank['bank_curr_code'], PT_CUSTOMER, $customer_id,"",$bank['bank_curr_code']==get_company_currency()?1:$rate); 
		
		$total += add_gl_trans(ST_CUSTRTPDC, $return_no, $_POST['return_date'],
		$debtors_account, $dim1, $dim2, '', $pdc_amount,  $bank['bank_curr_code'], PT_CUSTOMER, $customer_id,"",$bank['bank_curr_code']==get_company_currency()?1:$rate);
			
		}
		if($recall_status == '0'){
			
		clear_return_before_recall_pdc_cust_alloctions(ST_CUSTPDC, $pdc_number,$customer_id, $_POST['return_date']);
		$pdcsql = "UPDATE ".TB_PREF."debtor_trans SET alloc=".db_escape($pdc_amount).",pdc_amt =0 WHERE trans_no = ".db_escape($pdc_number)." AND type = '".ST_CUSTPDC."' ";
			
			db_query($pdcsql);
		add_cust_allocation($pdc_amount,
						ST_CUSTRTPDC, $return_no,
 			     		ST_CUSTPDC, $pdc_number, $customer_id, $_POST['return_date']);
		}else{
			
			clear_return_pdc_cust_alloctions(ST_CUSTPDC, $pdc_number,$customer_id, $_POST['return_date']);
			add_cust_allocation($pdc_amount,
						ST_CUSTRTPDC, $return_no,
 			     		ST_CUSTPDC, $pdc_number, $customer_id, $_POST['return_date']);
		}
					
		
		update_debtor_trans_allocation_with_pdc_return_bounce(ST_CUSTRTPDC, $return_no, $customer_id);
		
		if($_POST['recall_status']=='1'){
			
			//void_bank_trans(ST_CUSTPDC,$pdc_number);
			add_bank_trans(ST_CUSTRTPDC, $return_no, $bank_account, $ref,
		$_POST['return_date'], -$pdc_amount, PT_CUSTOMER, $customer_id,$bank['bank_curr_code'],"",$rate,'',0,'',0,'','','','','');
		}
		
		
		meta_forward($_SERVER['PHP_SELF'],  "AddedID=$return_no" );
}
}

if (isset($_GET['AddedID'])) {
	$payment_no = $_GET['AddedID'];

	display_notification_centered(_("The customer PDC return has been successfully entered."));

	

	submenu_view(_("&View this Customer return PDC"), ST_CUSTRTPDC, $payment_no);

	
	display_note(get_gl_view_str(ST_CUSTRTPDC, $payment_no, _("&View the GL Journal Entries for this Customer PDC")));

	display_footer_exit();
}
//---------------------------------------------------------------------------------------
start_form(true);
$receipt = get_customer_trans($_POST['PdcNumber'], ST_CUSTPDC);
//display_error(json_encode($receipt));
//$_POST['pdc_number'] = $_
display_heading(sprintf(_("Customer PDC  #%d"),$_POST['PdcNumber']));
global $mode_payment_types;
echo "<br>";
start_table(TABLESTYLE, "width='80%'");
start_row();
label_cells(_("From Customer"), $receipt['DebtorName'], "class='tableheader2'");
label_cells(_("Reference"), $receipt['reference'], "class='tableheader2'");
label_cells(_("Date of Deposit"), sql2date($receipt['tran_date']), "class='tableheader2'");
date_cells(_("Return Date :"), 'return_date', '', null);
end_row();
start_row();
label_cells(_("Customer Currency"), $receipt['curr_code'], "class='tableheader2'");
label_cells(_("Amount"), price_format($receipt['Total'] - $receipt['ov_discount']), "class='tableheader2'");
label_cells(_("Discount"), price_format($receipt['ov_discount']), "class='tableheader2'");
ref_cells(_("Reference"), 'ref', '', null, "class='tableheader2'", false, ST_CUSTRTPDC,null);
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
textarea_cells(_("Remarks"),'remarks',null,20,2);
end_row();
	

comments_display_row(ST_CUSTPDC, $selected_id);

 
$voided = is_voided_display(ST_CUSTPDC, $selected_id, _("This customer payment has been voided."));

if (!$voided)
{
	display_allocations_from(PT_CUSTOMER, $receipt['debtor_no'], ST_CUSTPDC, $_POST['PdcNumber'], $receipt['Total']);
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

end_outer_table(1); // outer table
echo "<tr><td>";

submit_center_first('Submit', _("Return PDC "), '', 'default');
echo "</td></tr>";
end_form();
//--------------------------------------------------------------------------------------------------
end_page();
