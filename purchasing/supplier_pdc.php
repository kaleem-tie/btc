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
$page_security = 'SA_SUPPLIERPDC';
$path_to_root = "..";
include_once($path_to_root . "/includes/ui/allocation_cart.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

add_js_file('payalloc.js');

page(_($help_context = "Supplier PDC Entry"), false, false, "", $js);

if (isset($_GET['supplier_id']))
{
	$_POST['supplier_id'] = $_GET['supplier_id'];
}

//----------------------------------------------------------------------------------------

check_db_has_suppliers(_("There are no suppliers defined in the system."));

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------

if (!isset($_POST['supplier_id']))
	$_POST['supplier_id'] = get_global_supplier(false);

if (!isset($_POST['DatePaid']))
{
	$_POST['DatePaid'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['DatePaid']))
		$_POST['DatePaid'] = end_fiscalyear();
}

if (isset($_POST['_DatePaid_changed'])) {
  $Ajax->activate('_ex_rate');
}

//----------------------------------------------------------------------------------------

if (!isset($_POST['bank_account'])) { // first page call
	$_SESSION['alloc'] = new allocation(ST_SUPPPDC, 0, get_post('supplier_id'));

	if (isset($_GET['PInvoice'])) {
		//  get date and supplier
		$inv = get_supp_trans($_GET['PInvoice'], $_GET['trans_type']);
		if ($inv) {
			$_SESSION['alloc']->person_id = $_POST['supplier_id'] = $inv['supplier_id'];
			$_SESSION['alloc']->read();
			$_POST['DatePaid'] = sql2date($inv['tran_date']);
			$_POST['memo_'] = $inv['supp_reference'];
			foreach($_SESSION['alloc']->allocs as $line => $trans) {
				if ($trans->type == $_GET['trans_type'] && $trans->type_no == $_GET['PInvoice']) {
					$un_allocated = abs($trans->amount) - $trans->amount_allocated;
					$_SESSION['alloc']->amount = $_SESSION['alloc']->allocs[$line]->current_allocated = $un_allocated;
					$_POST['amount'] = $_POST['amount'.$line] = price_format($un_allocated);
					break;
				}
			}
			unset($inv);
		} else
			display_error(_("Invalid purchase invoice number."));
	}
}

if (isset($_GET['AddedID'])) {
	$payment_id = $_GET['AddedID'];

   	display_notification_centered( _("Payment has been sucessfully entered"));

	//submenu_print(_("&Print This Remittance"), ST_SUPPPDC, $payment_id."-".ST_SUPPPDC, 'prtopt');
	//submenu_print(_("&Email This Remittance"), ST_SUPPPDC, $payment_id."-".ST_SUPPPDC, null, 1);

	submenu_view(_("View this PDC"), ST_SUPPPDC, $payment_id);

	submenu_option(_("Enter another PDC"), "/purchasing/supplier_pdc.php?supplier_id=".$_POST['supplier_id']);
	
	submenu_print(_("&Print This PDC Cheque Report"), ST_SUPPPDC_REP, $payment_id."-".ST_SUPPPDC, 'prtopt');
	
	submenu_option(_("PDC Cheque Transaction Update"), "/purchasing/inquiry/cheque_transaction_update.php?trans_type=".ST_SUPPPDC."&trans_no=".$payment_id);
	
	submenu_option(_("Add an Attachment"),	"admin/attachments.php?filterType=".ST_SUPPPDC."&trans_no=$payment_id");	

	display_footer_exit();
}
if(get_post('update')){
	
foreach($_SESSION['alloc']->allocs as $line => $trans) {
	$_POST['amount_1'.$line] = input_num('amount'.$line); 
	
	$Ajax->activate('amt'.$line);
	//$Ajax->activate('alloc_tbl');
}
}
//----------------------------------------------------------------------------------------

function get_default_supplier_payment_bank_account($supplier_id, $date)
{
	$previous_payment = get_supp_payment_before($supplier_id, date2sql($date));
	if ($previous_payment)
	{
		return $previous_payment['bank_id'];
	}
	return get_default_supplier_bank_account($supplier_id);
}
//----------------------------------------------------------------------------------------

function check_inputs()
{
	global $Refs;

	if (!get_post('supplier_id')) 
	{
		display_error(_("There is no supplier selected."));
		set_focus('supplier_id');
		return false;
	} 
	
	if (@$_POST['amount'] == "") 
	{
		$_POST['amount'] = price_format(0);
	}

	if (!check_num('amount', 0))
	{
		display_error(_("The entered amount is invalid or less than zero."));
		set_focus('amount');
		return false;
	}

	if (isset($_POST['charge']) && !check_num('charge', 0)) {
		display_error(_("The entered amount is invalid or less than zero."));
		set_focus('charge');
		return false;
	}

	if (isset($_POST['charge']) && input_num('charge') > 0) {
		$charge_acct = get_bank_charge_account($_POST['bank_account']);
		if (get_gl_account($charge_acct) == false) {
			display_error(_("The Bank Charge Account has not been set in System and General GL Setup."));
			set_focus('charge');
			return false;
		}	
	}

	if (@$_POST['discount'] == "") 
	{
		$_POST['discount'] = 0;
	}

	if (!check_num('discount', 0))
	{
		display_error(_("The entered discount is invalid or less than zero."));
		set_focus('amount');
		return false;
	}

	//if (input_num('amount') - input_num('discount') <= 0) 
	if (input_num('amount') <= 0) 
	{
		display_error(_("The total of the amount and the discount is zero or negative. Please enter positive values."));
		set_focus('amount');
		return false;
	}

	if (isset($_POST['bank_amount']) && input_num('bank_amount')<=0)
	{
		display_error(_("The entered bank amount is zero or negative."));
		set_focus('bank_amount');
		return false;
	}


   	if (!is_date($_POST['DatePaid']))
   	{
		display_error(_("The entered date is invalid."));
		set_focus('DatePaid');
		return false;
	} 
	elseif (!is_date_in_fiscalyear($_POST['DatePaid'])) 
	{
		display_error(_("The entered date is out of fiscal year or is closed for further data entry."));
		set_focus('DatePaid');
		return false;
	}

	$limit = get_bank_account_limit($_POST['bank_account'], $_POST['DatePaid']);

	if (($limit !== null) && (floatcmp($limit, input_num('amount')) < 0))
	{
		display_error(sprintf(_("The total bank amount exceeds allowed limit (%s)."), price_format($limit)));
		set_focus('amount');
		return false;
	}

	if (!check_reference($_POST['ref'], ST_SUPPPDC))
	{
		set_focus('ref');
		return false;
	}
	
	/*
	if($_POST['our_ref_no'] ==''){ 
		
			display_error(_("Our Reference must be entered."));
			set_focus('our_ref_no');
			return false;
	}
	*/
	
	if($_POST['pdc_cheque_no'] ==''){ 
		
			display_error(_("You have to enter PDC No and PDC Date."));
			set_focus('pdc_cheque_no');

			return false;
	}
	
	$pdc_cheque_no_exist = check_exists_supplier_pdc_cheque_no(trim($_POST['pdc_cheque_no']));
	   if($pdc_cheque_no_exist!=0){
		   
		   $supp_pdc_cheque_no = get_exists_supplier_pdc_cheque_no(trim($_POST['pdc_cheque_no']));
		   
		   $input_error = 1;
		   display_error(_("PDC cheque no. should be unique.It is already entered for supplier PDC - ".$supp_pdc_cheque_no));
		   set_focus('pdc_cheque_no');
		   return false;
	   }
	

	if (!db_has_currency_rates(get_supplier_currency($_POST['supplier_id']), $_POST['DatePaid'], true))
		return false;

	$_SESSION['alloc']->amount = -input_num('amount');

	if (isset($_POST["TotalNumberOfAllocs"]))
		return check_allocations();
	else
		return true;
}

//----------------------------------------------------------------------------------------

function handle_add_payment()
{
	
	if($_POST['_ex_rate'])
	$ex_rate=input_num('_ex_rate');
	else
	$ex_rate=1;  // ravi
	
	$payment_id = write_supp_pdc(0, $_POST['supplier_id'], $_POST['bank_account'],
		$_POST['DatePaid'], $_POST['ref'], input_num('amount'),	input_num('discount'), $_POST['memo_'], $ex_rate,input_num('charge'), input_num('bank_amount', input_num('amount')), 
		$_POST['dimension_id'], $_POST['dimension2_id'],$_POST['our_ref_no'],$_POST['pdc_cheque_no'],
		$_POST['pdc_cheque_date'],$_POST['cheque_bank_name']); //ravi
		
	new_doc_date($_POST['DatePaid']);

	$_SESSION['alloc']->trans_no = $payment_id;
	$_SESSION['alloc']->date_ = $_POST['DatePaid'];
	$_SESSION['alloc']->write();

   	unset($_POST['bank_account']);
   	unset($_POST['DatePaid']);
   	unset($_POST['currency']);
   	unset($_POST['memo_']);
   	unset($_POST['amount']);
   	unset($_POST['discount']);
   	unset($_POST['ProcessSuppPayment']);

	meta_forward($_SERVER['PHP_SELF'], "AddedID=$payment_id&supplier_id=".$_POST['supplier_id']);
}

//----------------------------------------------------------------------------------------

if (isset($_POST['AddPDC']))
{
	 /*First off  check for valid inputs */
    if (check_inputs() == true) 
    {
    	handle_add_payment();
    	end_page();
     	exit;
    }
}

//----------------------------------------------------------------------------------------
$new = !$_SESSION['alloc']->trans_no;
start_form();

 div_start('payments_mode_div');

	start_outer_table(TABLESTYLE2, "width='60%'", 5);

	table_section(1);

    supplier_list_row(_("Payment To:"), 'supplier_id', null, _("Select a Supplier"), true);

	if (list_updated('supplier_id')) {
		$_POST['amount'] = price_format(0);
		$_SESSION['alloc']->person_id = get_post('supplier_id');
		$Ajax->activate('amount');
	} elseif (list_updated('bank_account'))
		$Ajax->activate('alloc_tbl');

	if (list_updated('supplier_id') || list_updated('bank_account')) {
	  $_SESSION['alloc']->read();
	  $_POST['memo_'] = $_POST['amount'] = '';
	  $Ajax->activate('alloc_tbl');
	}
	

	set_global_supplier($_POST['supplier_id']);

	if (!list_updated('bank_account') && !get_post('__ex_rate_changed'))
	{
	//	$_POST['bank_account'] = get_default_supplier_payment_bank_account($_POST['supplier_id'], $_POST['DatePaid']);
	} else
	{
		$_POST['amount'] = price_format(0);
	}

    //bank_accounts_list_row(_("From Bank Account:"), 'bank_account', null, true);
	bank_accounts_list_trans_row(_("From Bank Account:"), 'bank_account', null, true, _("Select a Bank"), true);

	bank_balance_row($_POST['bank_account']);

	table_section(2);

    date_row(_("Date Paid") . ":", 'DatePaid', '', true, 0, 0, 0, null, true);

    ref_row(_("Reference:"), 'ref', '', $Refs->get_next(ST_SUPPPDC, null, 
    	array('supplier'=>get_post('supplier_id'), 'date'=>get_post('DatePaid'))), false, ST_SUPPAYMENT);

	//ravi
	text_row(_("Our Ref No."), 'our_ref_no', null, 16, 15);

	table_section(3);

	$comp_currency = get_company_currency();
	$supplier_currency = $_SESSION['alloc']->set_person($_POST['supplier_id'], PT_SUPPLIER);
	if (!$supplier_currency)
			$supplier_currency = $comp_currency;
	$_SESSION['alloc']->currency = $bank_currency = get_bank_account_currency($_POST['bank_account']);

	if ($bank_currency != $supplier_currency) 
	{
		amount_row(_("Bank Amount:"), 'bank_amount', null, '', $bank_currency);
	}

	amount_row(_("Bank Charge:"), 'charge', null, '', $bank_currency);
	
	//ravi
  exchange_rate_display($comp_currency,  $_SESSION['alloc']->set_person($_POST['supplier_id'], PT_SUPPLIER),$_POST['DatePaid']);  //ravi
			

	$row = get_supplier($_POST['supplier_id']);
	$_POST['dimension_id'] = @$row['dimension_id'];
	$_POST['dimension2_id'] = @$row['dimension2_id'];
	$dim = get_company_pref('use_dimension');
	if ($dim > 0)
		dimensions_list_row(_("Dimension").":", 'dimension_id',
			null, true, ' ', false, 1, false);
	else
		hidden('dimension_id', 0);
	if ($dim > 1)
		dimensions_list_row(_("Dimension")." 2:", 'dimension2_id',
			null, true, ' ', false, 2, false);
	else
		hidden('dimension2_id', 0);
text_row(_("Cheque Bank Name"), 'cheque_bank_name', null, 25, 100);
table_section(4);
text_row(_("Cheque No.<b style='color:red;'>*</b>"), 'pdc_cheque_no', null, 16, 15);
date_row(_("Cheque Date:"), 'pdc_cheque_date', '', true, 0, 0, 0, null, true);
	end_outer_table(1);
	div_end();

	div_start('alloc_tbl');
	show_allocatable(false);
	div_end();
		echo "<div style =' margin-left: 86%;'>";
	 submit_cells('update', _("Refresh"), " ", _("Refresh"), true);
	 echo "</div>";
 
	start_table(TABLESTYLE, "width='70%'");
	amount_row(_("Amount of Discount:"), 'discount', null, '', $supplier_currency);
	amount_row(_("Amount of Payment:"), 'amount', null, '', $supplier_currency);
	textarea_row(_("Memo:"), 'memo_', null, 22, 4);
	

	end_table(1);

	
	 
	 if ($new)
	 user_check_access('SA_SUPPLIERPDC_ADDNEW') ? submit_center('AddPDC', _("Add PDC"), true, '', 'default') : '';
	else
	 user_check_access('SA_SUPPLIERPDC_EDITNEW') ? submit_center('UpdatePDC', _("Update PDC"), true, '', 'default') : '';
	 

end_form();

end_page();
