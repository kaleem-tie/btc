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
$page_security = 'SA_BANKTRANSFER';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

if (isset($_GET['ModifyTransfer'])) {
	$_SESSION['page_title'] = _($help_context = "Modify Bank Account Transfer");
} else {
	$_SESSION['page_title'] = _($help_context = "Bank Account Transfer Entry");
}

page($_SESSION['page_title'], false, false, "", $js);

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_BANKTRANSFER;

   	display_notification_centered( _("Transfer has been entered"));
	submenu_view(_("&View This Transfer"), ST_BANKTRANSFER, $trans_no);

	display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries for this Transfer")));
	br();
	
	$bt_payment_mode = get_cheque_print_gl($trans_no, ST_BANKTRANSFER);
	if($bt_payment_mode == "cheque"){
	 display_note(print_document_link($trans_no, _("&Print Cheque"), true, ST_BANKTRANSFER_REP), 0, 1);
	}
	else{
	display_note(print_document_link($trans_no, _("&Print Transfer"), true, ST_BANKTRANSFER), 0, 1);
    }

   	hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another Transfer"));

	display_footer_exit();
}

if (isset($_POST['_DatePaid_changed'])) {
	$Ajax->activate('_ex_rate');
}

//----------------------------------------------------------------------------------------

function gl_payment_controls($trans_no)
{
	global $Refs;
	
	if (!in_ajax()) {
		if ($trans_no) {
			$result = get_bank_trans(ST_BANKTRANSFER, $trans_no);

			if (db_num_rows($result) != 2)
				display_db_error("Bank transfer does not contain two records");

			$trans1 = db_fetch($result);
			$trans2 = db_fetch($result);
			if ($trans1["amount"] < 0) {
				$from_trans = $trans1; // from trans is the negative one
				$to_trans = $trans2;
			} else {
			$from_trans = $trans2;
				$to_trans = $trans1;
			}
			$_POST['DatePaid'] = sql2date($to_trans['trans_date']);
			$_POST['ref'] = $to_trans['ref'];
			$_POST['memo_'] = get_comments_string($to_trans['type'], $trans_no);
			$_POST['FromBankAccount'] = $from_trans['bank_act'];
			$_POST['ToBankAccount'] = $to_trans['bank_act'];
			$_POST['target_amount'] = price_format($to_trans['amount']);
			$_POST['amount'] = price_format(-$from_trans['amount']);
			$_POST['dimension_id'] = $to_trans['dimension_id'];
			$_POST['dimension2_id'] = $to_trans['dimension2_id'];
			$_POST['cheque_no'] = $to_trans['cheque_no'];
			$_POST['date_of_issue'] = sql2date($to_trans['date_of_issue']);
			
			$_POST['pymt_ref'] = $to_trans['pymt_ref'];
			$_POST['mode_of_payment'] = $to_trans['mode_of_payment'];
			
			//$_POST['interest_act'] = $from_trans['interest_act'];
			//$_POST['vat_act'] = $to_trans['vat_act'];
			
			$_POST['our_ref_no'] = $to_trans['our_ref_no'];
		} else {
			//$_POST['ref'] = $Refs->get_next(ST_BANKTRANSFER, null, get_post('DatePaid'));
			$_POST['memo_'] = '';
			$_POST['FromBankAccount'] = 0;
			$_POST['ToBankAccount'] = 0;
			$_POST['amount'] = 0;
			$_POST['dimension_id'] = 0;
			$_POST['dimension2_id'] = 0;
		}
	}

	start_form();
	div_start('payments_mode_div');
	start_outer_table(TABLESTYLE2);

	table_section(1);

	//bank_accounts_list_row(_("From Account:"), 'FromBankAccount', null, true);
	bank_accounts_list_trans_row(_("From Account:"), 'FromBankAccount', null, true, _("Select a Bank"), true, true);

	bank_balance_row($_POST['FromBankAccount']);

    //bank_accounts_list_row(_("To Account:"), 'ToBankAccount', null, true, _("Select a Bank"));
	bank_accounts_list_trans_row(_("To Account:"), 'ToBankAccount', null, true, _("Select a Bank"), true, true);

	if (!isset($_POST['DatePaid'])) { // init page
		$_POST['DatePaid'] = new_doc_date();
		if (!is_date_in_fiscalyear($_POST['DatePaid']))
			$_POST['DatePaid'] = end_fiscalyear();
	}
    date_row(_("Transfer Date:"), 'DatePaid', '', true, 0, 0, 0, null, true);

    ref_row(_("Reference:"), 'ref', '', $Refs->get_next(ST_BANKTRANSFER, null, get_post('DatePaid')), false, ST_BANKTRANSFER,
    	array('date' => get_post('DatePaid')));
		
		
		
	$dim = get_company_pref('use_dimension');
	if ($dim > 0)
		dimensions_list_row(_("Dimension").":", 'dimension_id', 
			null, true, ' ', false, 1, false);
	else
		hidden('dimension_id', 0);
	
	text_row(_("Our Reference No."),'our_ref_no',$_POST['our_ref_no']);
	
	// Payment Mode ## Ramesh
	global $Ajax;
	mode_of_payment_list_row(_("Mode of Payment:"), 'mode_of_payment', $_POST['mode_of_payment'], true);
	
	if(list_updated('mode_of_payment') || $_POST['mode_of_payment']){
	$Ajax->activate('payments_mode_div');

		if($_POST['mode_of_payment'] == 'cheque'){
			text_row(_("Cheque No."), 'cheque_no', null, 16, 15);
			date_row(_("Date of Issue:"), 'date_of_issue', '', true, 0, 0, 0, null, true);
		}
		if($_POST['mode_of_payment'] == 'dd'){
			text_row(_("DD No."), 'dd_no', null, 16, 15);
			date_row(_("Date of Issue:"), 'dd_date_of_issue', '', true, 0, 0, 0, null, true);
		}
		if($_POST['mode_of_payment'] == 'ot' || $_POST['mode_of_payment'] == 'rtgs' || $_POST['mode_of_payment'] == 'neft' || $_POST['mode_of_payment'] == 'card'){
		
			if($_POST['mode_of_payment'] == 'card'){
			text_row(_("Card Last 4 Digits."), 'pymt_ref', null, 16, 15);
			}
		}
	}	
	//End
	
	table_section(2);

	$from_currency = get_bank_account_currency($_POST['FromBankAccount']);
	$to_currency = get_bank_account_currency($_POST['ToBankAccount']);
	if ($from_currency != "" && $to_currency != "" && $from_currency != $to_currency) 
	{
		amount_row(_("Amount:"), 'amount', null, null, $from_currency);
		amount_row(_("Bank Charge:"), 'charge', null, null, $from_currency);

		dimensions_list_row(_("Dimension").":", 'bank_charge_dimension_id', null, false, ' ', false, 1, false);
		amount_row(_("Incoming Amount:"), 'target_amount', null, '', $to_currency, 2);
		amount_row(_("GL Amount:"), 'interest_charge');
		dimensions_list_row(_("Dimension").":", 'bank_interest_dimension_id', null, false, ' ', false, 1, false);
		gl_all_accounts_list_row(_("GL Account:"), 'interest_act',null, false, false,  _("Select a GL Account"));
		amount_row(_("VAT Charge:"), 'vat_charge', null, null, $from_currency);
		dimensions_list_row(_("Dimension").":", 'vat_charge_dimension_id', null, false, ' ', false, 1, false);
		gl_all_accounts_list_row(_("VAT Account:"), 'vat_act',null, false, false,  _("Select a VAT Account"));
	} 
	else 
	{
		amount_row(_("Amount:"), 'amount');
		amount_row(_("Bank Charge:"), 'charge');
		dimensions_list_row(_("Dimension").":", 'bank_charge_dimension_id', null, false, ' ', false, 1, false);
		/* amount_row(_("GL Amount:"), 'interest_charge');
		dimensions_list_row(_("Dimension").":", 'bank_interest_dimension_id', null, false, ' ', false, 1, false);
		gl_all_accounts_list_row(_("GL Account:"), 'interest_act');
		amount_row(_("VAT Charge:"), 'vat_charge');
		dimensions_list_row(_("Dimension").":", 'vat_charge_dimension_id', null, false, ' ', false, 1, false);
		gl_all_accounts_list_row(_("VAT Account:"), 'vat_act'); */
		amount_row(_("VAT Charge:"), 'vat_charge');
	}
	if ($dim > 1)
		dimensions_list_row(_("Dimension")." 2:", 'dimension2_id', 
			null, true, ' ', false, 2, false);
	else
		hidden('dimension2_id', 0);

    textarea_row(_("Memo:"), 'memo_', null, 40,4);

	end_outer_table(1); // outer table

	if ($trans_no) {
		hidden('_trans_no', $trans_no);
		submit_center('submit', _("Modify Transfer"), true, '', 'default');
	} else {
		submit_center('submit', _("Enter Transfer"), true, '', 'default');
	}
	div_end();
	end_form();
}

//----------------------------------------------------------------------------------------

function check_valid_entries($trans_no)
{
	global $Refs, $systypes_array;
	
		
	if ($_POST['FromBankAccount'] == '') {
		display_error(_("From Bank Account cannot be empty."));
		set_focus('FromBankAccount');
		return false;
	}
	if ($_POST['ToBankAccount'] == '') {
		display_error(_("To Bank Account cannot be empty."));
		set_focus('ToBankAccount');
		return false;
	}
	
	/* if ($_POST['interest_act'] == '') {
		display_error(_("GL Account cannot be empty."));
		set_focus('interest_act');
		return false;
	}
	
	if ($_POST['vat_act'] == '') {
		display_error(_("VAT Account cannot be empty."));
		set_focus('vat_act');
		return false;
	} */
	
	if (!is_date($_POST['DatePaid'])) 
	{
		display_error(_("The entered date is invalid."));
		set_focus('DatePaid');
		return false;
	}
	if (!is_date_in_fiscalyear($_POST['DatePaid']))
	{
		display_error(_("The entered date is out of fiscal year or is closed for further data entry."));
		set_focus('DatePaid');
		return false;
	}

	if (!check_num('amount', 0)) 
	{
		display_error(_("The entered amount is invalid or less than zero."));
		set_focus('amount');
		return false;
	}
	if (input_num('amount') == 0) {
		display_error(_("The total bank amount cannot be 0."));
		set_focus('amount');
		return false;
	}


	$limit = get_bank_account_limit($_POST['FromBankAccount'], $_POST['DatePaid']);

	//$amnt_tr = input_num('charge') + input_num('amount');
	$amnt_tr = input_num('charge') + input_num('amount') + input_num('vat_charge')+ input_num('interest_charge');

	$problemTransaction = null;
	if ($trans_no) {
		$problemTransaction = check_bank_transfer( $trans_no, $_POST['FromBankAccount'], $_POST['ToBankAccount'], $_POST['DatePaid'],
			$amnt_tr, input_num('target_amount', $amnt_tr));

	if ($problemTransaction != null	) {
		if (!array_key_exists('trans_no', $problemTransaction)) {
			display_error(sprintf(
				_("This bank transfer change would result in exceeding authorized overdraft limit (%s) of the account '%s'"),
				price_format(-$problemTransaction['amount']), $problemTransaction['bank_account_name']
			));
		} else {
			display_error(sprintf(
				_("This bank transfer change would result in exceeding authorized overdraft limit on '%s' for transaction: %s #%s on %s."),
				$problemTransaction['bank_account_name'], $systypes_array[$problemTransaction['type']],
				$problemTransaction['trans_no'], sql2date($problemTransaction['trans_date'])
			));
		}
		set_focus('amount');
		return false;
		}
	} else {
		if (null != ($problemTransaction = check_bank_account_history(-$amnt_tr, $_POST['FromBankAccount'], $_POST['DatePaid']))) {
			if (!array_key_exists('trans_no', $problemTransaction)) {
				display_error(sprintf(
					_("This bank transfer would result in exceeding authorized overdraft limit of the account (%s)"),
					price_format(-$problemTransaction['amount'])
				));
			} else {
				display_error(sprintf(
					_("This bank transfer would result in exceeding authorized overdraft limit for transaction: %s #%s on %s."),
					$systypes_array[$problemTransaction['type']], $problemTransaction['trans_no'], sql2date($problemTransaction['trans_date'])
				));
			}
			set_focus('amount');
			return false;
		}
	}

	if (isset($_POST['charge']) && !check_num('charge', 0)) 
	{
		display_error(_("The entered amount is invalid or less than zero."));
		set_focus('charge');
		return false;
	}
	if (isset($_POST['charge']) && input_num('charge') > 0 && get_bank_charge_account($_POST['FromBankAccount']) == '') {
		display_error(_("The Bank Charge Account has not been set in System and General GL Setup."));
		set_focus('charge');
		return false;
	}
	
	if (isset($_POST['vat_charge']) && !check_num('vat_charge', 0)) 
	{
		display_error(_("The entered amount is invalid or less than zero."));
		set_focus('vat_charge');
		return false;
	}

	if (!check_reference($_POST['ref'], ST_BANKTRANSFER, $trans_no)) {
		set_focus('ref');
		return false;
	}
	
	if (!get_post('our_ref_no')) 
	{
		display_error(_("Our Reference No cannot be empty."));
		set_focus('our_ref_no');
		$input_error = 1;
	}

	if ($_POST['FromBankAccount'] == $_POST['ToBankAccount']) 
	{
		display_error(_("The source and destination bank accouts cannot be the same."));
		set_focus('ToBankAccount');
		return false;
	}

	if (isset($_POST['target_amount']) && !check_num('target_amount', 0)) 
	{
		display_error(_("The entered amount is invalid or less than zero."));
		set_focus('target_amount');
		return false;
	}
	if (isset($_POST['target_amount']) && input_num('target_amount') == 0) {
		display_error(_("The incomming bank amount cannot be 0."));
		set_focus('target_amount');
		return false;
	}
	// mode of payment validations  # Ramesh  
	if($_POST['mode_of_payment'] == 'cheque')
	{
		if($_POST['cheque_no'] ==''){ 
		display_error(_("Cheque no. should be entered."));
		set_focus('cheque_no');
		$input_error = 1;
		}
		
		if($_SESSION['pay_items']->trans_type==ST_BANKTRANSFER){
		$cheque_no_exist = check_exists_bank_payment_cheque_no(trim($_POST['cheque_no']), $trans_no, ST_BANKTRANSFER);
	      if($cheque_no_exist!=0 && $trans_no==0){
		   
		   $cust_cheque_no = get_exists_bank_payment_cheque_no(trim($_POST['cheque_no']), ST_BANKTRANSFER);
		   
		   display_error(_("Cheque no. should be unique.It is already entered for customer payment - ".$cust_cheque_no));
		   set_focus('cheque_no');
		   $input_error = 1;
	     }
		}
		
	}	
	
	if($_POST['mode_of_payment'] == 'card')
	{
		if($_POST['pymt_ref'] ==''){ 
		display_error(_("Card Last 4 Digits should be entered."));
		set_focus('pymt_ref');
		$input_error = 1;
		}
		
		if (strlen($_POST['pymt_ref']) < 4)
    	{
  		display_error( _("The Card entered must be 4 digits."));
		set_focus('pymt_ref');
   		$input_error = 1;
   	    }
	}	
	// End

	if (!db_has_currency_rates(get_bank_account_currency($_POST['FromBankAccount']), $_POST['DatePaid']))
		return false;

	if (!db_has_currency_rates(get_bank_account_currency($_POST['ToBankAccount']), $_POST['DatePaid']))
		return false;

    return true;
}

//----------------------------------------------------------------------------------------

function bank_transfer_handle_submit()
{
	$trans_no = array_key_exists('_trans_no', $_POST) ?  $_POST['_trans_no'] : null;
	if ($trans_no) {
		$trans_no = update_bank_transfer($trans_no, $_POST['FromBankAccount'], $_POST['ToBankAccount'], $_POST['DatePaid'],	input_num('amount'), 
			$_POST['ref'], $_POST['memo_'], $_POST['dimension_id'], $_POST['dimension2_id'], input_num('charge'), input_num('target_amount'),$_POST['our_ref_no'],input_num('_ex_rate'),input_num('vat_charge'),$_POST['vat_act'],$_POST['bank_charge_dimension_id'],input_num('interest_charge'),$_POST['interest_act'],$_POST['bank_interest_dimension_id'],$_POST['vat_charge_dimension_id'],$_POST['mode_of_payment'],$_POST['cheque_no'],$_POST['dd_no'],$_POST['date_of_issue'],$_POST['dd_date_of_issue'],$_POST['pymt_ref']);
	} else {
		new_doc_date($_POST['DatePaid']);
		$trans_no = add_bank_transfer($_POST['FromBankAccount'], $_POST['ToBankAccount'], $_POST['DatePaid'], input_num('amount'), $_POST['ref'], 
			$_POST['memo_'], $_POST['dimension_id'], $_POST['dimension2_id'], input_num('charge'), input_num('target_amount'),$_POST['our_ref_no'],input_num('_ex_rate'),input_num('vat_charge'),$_POST['vat_act'],$_POST['bank_charge_dimension_id'],input_num('interest_charge'),$_POST['interest_act'],$_POST['bank_interest_dimension_id'],$_POST['vat_charge_dimension_id'],$_POST['mode_of_payment'],$_POST['cheque_no'],$_POST['dd_no'],$_POST['date_of_issue'],$_POST['dd_date_of_issue'],$_POST['pymt_ref']);
	}

	meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
}

//----------------------------------------------------------------------------------------

$trans_no = '';
if (!$trans_no && isset($_POST['_trans_no'])) {
	$trans_no = $_POST['_trans_no'];
}
if (!$trans_no && isset($_GET['trans_no'])) {
	$trans_no = $_GET["trans_no"];
}

if (isset($_POST['submit'])) {
    if (check_valid_entries($trans_no) == true) {
        bank_transfer_handle_submit();
	}
}

gl_payment_controls($trans_no);

end_page();
