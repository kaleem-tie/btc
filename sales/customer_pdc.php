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
$page_security = 'SA_SALESPDC';
$path_to_root = "..";
include_once($path_to_root . "/sales/customer_pdc_payment.php");
include_once($path_to_root . "/includes/ui/allocation_cart.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/sales/includes/db/sm_collection_db.inc");



$js = "";
$amount = 0;
if (isset($_SESSION['pdc_items'])) {
	$amount = price_format($_SESSION['pdc_items']->getTotalAmount());
}
if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}
if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}
add_js_file('payalloc.js');

page(_($help_context = "Customer PDC Entry"), false, false, "", $js);

//----------------------------------------------------------------------------------------------

check_db_has_customers(_("There are no customers defined in the system."));

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------
//display_error(http_build_query($_SESSION['pdc_items']->items));
if (!isset($_POST['bank_account'])) { // first page call
	$_SESSION['pdc_items'] = new CustomerPDCPayment();
	$_SESSION['alloc'] = new allocation(ST_CUSTPDC, 0, get_post('customer_id'));
	
	if (isset($_GET['SInvoice'])) {
		//  get date and supplier
		
		$inv = write_customer_pdc($_GET['SInvoice'], ST_SALESINVOICE);
		$dflt_act = get_default_bank_account($inv['curr_code']);
		$_POST['bank_account'] = $dflt_act['id'];
		if ($inv) {
			$_POST['customer_id'] = $inv['debtor_no'];
			$_SESSION['alloc']->set_person($inv['debtor_no'], PT_CUSTOMER);
			$_SESSION['alloc']->read();
			$_POST['branch_id'] = $inv['branch_code'];
			$_POST['DateBanked'] = sql2date($inv['tran_date']);
			foreach($_SESSION['alloc']->allocs as $line => $trans) {
				if ($trans->type == ST_SALESINVOICE && $trans->type_no == $_GET['SInvoice']) {
					$un_allocated = $trans->amount - $trans->amount_allocated;
					if ($un_allocated){
						$_SESSION['alloc']->allocs[$line]->current_allocated = $un_allocated;
						$_POST['amount'] = $_POST['amount'.$line] = price_format($un_allocated);
					}
					break;
				}
			}
			unset($inv);
		} else
			display_error(_("Invalid sales invoice number."));
	}
}

if (list_updated('branch_id')) {
	// when branch is selected via external editor also customer can change
	$br = get_branch(get_post('branch_id'));
	$_POST['customer_id'] = $br['debtor_no'];
	$_SESSION['alloc']->person_id = $br['debtor_no'];
	$Ajax->activate('customer_id');
}

if (!isset($_POST['customer_id'])) {
	$_POST['customer_id'] = get_global_customer(false);
	$_SESSION['alloc']->set_person($_POST['customer_id'], PT_CUSTOMER);
	$_SESSION['alloc']->read();
	//$dflt_act = get_default_bank_account($_SESSION['alloc']->person_curr);
	//$_POST['bank_account'] = $dflt_act['id'];
}
if (!isset($_POST['DateBanked'])) {
	$_POST['DateBanked'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['DateBanked'])) {
		$_POST['DateBanked'] = end_fiscalyear();
	}
}


if (isset($_GET['AddedID'])) {
	$payment_no = $_GET['AddedID'];

	display_notification_centered(_("The customer PDC has been successfully entered."));

	submenu_print(_("&Print This Customer PDC"), ST_CUSTPDC, $payment_no."-".ST_CUSTPDC, 'prtopt');
	submenu_print(_("&Email This Customer PDC"), ST_CUSTPDC, $payment_no."-".ST_CUSTPDC, null, 1);

	submenu_view(_("&View this Customer PDC"), ST_CUSTPDC, $payment_no);
	
	
	submenu_option(_("Recall PDC"),
		"/sales/salesman_collection_entry.php?PdcNumber=$payment_no");
		
	//submenu_option(_("Allocate Customer Payments"),
		//"/sales/allocations/customer_allocation_main.php?");	
		
		
	 $pdc_sales_person_ref = get_cust_pdc_sales_person_ref($payment_no);
	 submenu_option(_("Allocate Customer PDC"),
		"/sales/allocations/salesman_customer_allocation_main.php?sales_person_ref=$pdc_sales_person_ref");	
		

	submenu_option(_("Enter Another &Customer PDC"), "/sales/customer_pdc.php");
	
	submenu_option(_("Add an Attachment"),	"admin/attachments.php?filterType=".ST_CUSTPDC."&trans_no=$payment_no");	
	

	display_footer_exit();
}
elseif (isset($_GET['UpdatedID'])) {
	$payment_no = $_GET['UpdatedID'];

	display_notification_centered(_("The customer PDC has been successfully updated."));
	submenu_view(_("&View this Customer PDC"), ST_CUSTPDC, $payment_no);
	submenu_print(_("&Print This Receipt"), ST_CUSTPDC, $payment_no."-".ST_CUSTPDC, 'prtopt');
	
	submenu_option(_("Recall PDC"),
		"/sales/salesman_collection_entry.php?PdcNumber=$payment_no");
		
	$pdc_sales_person_ref = get_cust_pdc_sales_person_ref($payment_no);
	 submenu_option(_("Allocate Customer PDC"),
		"/sales/allocations/salesman_customer_allocation_main.php?sales_person_ref=$pdc_sales_person_ref");		

	display_note(get_gl_view_str(ST_CUSTPDC, $payment_no, _("&View the GL Journal Entries for this Customer PDC")));

	//hyperlink_params($path_to_root . "/sales/allocations/customer_allocate.php", _("&Allocate this Customer Payment"), "trans_no=$payment_no&trans_type=12");

	hyperlink_no_params($path_to_root . "/sales/inquiry/customer_inquiry.php?", _("Select Another Customer PDC for &Edition"));

	hyperlink_no_params($path_to_root . "/sales/customer_pdc.php", _("Enter Another &Customer PDC"));

	display_footer_exit();
}

//----------------------------------------------------------------------------------------------

function can_process()
{
	global $Refs;

	// if (!get_post('customer_id'))
	// {
	// 	display_error(_("There is no customer selected."));
	// 	set_focus('customer_id');
	// 	return false;
	// } 
	
	// if (!get_post('BranchID'))
	// {
	// 	display_error(_("This customer has no branch defined."));
	// 	set_focus('BranchID');
	// 	return false;
	// } 
	
	
	/*
	if($_POST['sales_person_ref'] ==''){ 
		
			display_error(_("Ref No.(For Allocation Purpose) must be entered."));
			set_focus('sales_person_ref');
			return false;
	}
	*/
	
	if($_POST['trans_no'] ==''){
	$sales_person_ref_pdc_exist = check_exists_pdc_ref_no_for_allocation(trim($_POST['sales_person_ref']));
	   if($sales_person_ref_pdc_exist!=0){
		   
		   $pdc_alloc_ref = get_pdc_ref_no_for_allocation($_POST['sales_person_ref']);
		   
		   $input_error = 1;
		   display_error(_("Ref No.(For Allocation Purpose) should be unique.It is already entered for sales payment - ".$pdc_alloc_ref));
		   set_focus('sales_person_ref');
		   return false;
	   }
	}
	   
	
	
	if($_POST['pdc_cheque_no'] ==''){ 
		
			display_error(_("You have to enter PDC Cheque No and PDC Date."));
			set_focus('pdc_cheque_no');
			return false;
	}
	
	if($_POST['trans_no'] ==''){
	$pdc_cheque_no_exist = check_exists_customer_pdc_cheque_no(trim($_POST['pdc_cheque_no']));
	   if($pdc_cheque_no_exist!=0){
		   
		   $cust_pdc_cheque_no = get_exists_customer_pdc_cheque_no(trim($_POST['pdc_cheque_no']));
		   
		   $input_error = 1;
		   display_error(_("PDC cheque no. should be unique.It is already entered for customer PDC - ".$cust_pdc_cheque_no));
		   set_focus('pdc_cheque_no');
		   return false;
	   }
	}
	
	
	if (!isset($_POST['DateBanked']) || !is_date($_POST['DateBanked'])) {
		display_error(_("The entered date is invalid. Please enter a valid date for the payment."));
		set_focus('DateBanked');
		return false;
	} elseif (!is_date_in_fiscalyear($_POST['DateBanked'])) {
		display_error(_("The entered date is out of fiscal year or is closed for further data entry."));
		set_focus('DateBanked');
		return false;
	}
	if (!check_reference($_POST['ref'], ST_CUSTPDC, @$_POST['trans_no'])) {
		
		$ref = $Refs->get_next($_SESSION['alloc']->trans_type, null, array('date' => Today()));
		if ($ref != $_SESSION['alloc']->reference)
		{
			unset($_POST['ref']); // force refresh reference
			display_error(_("The reference number field has been increased. Please save the document again."));
		}
		return false;
	}

	if (!check_num('amount', 0)) {
		display_error(_("The entered amount is invalid or negative and cannot be processed."));
		set_focus('amount');
		return false;
	}
	/*
	if($_POST['our_ref_no'] ==''){ 
		
			display_error(_("Our Reference must be entered."));
			set_focus('our_ref_no');
			return false;
	}
	*/

	if (isset($_POST['charge']) && (!check_num('charge', 0) || $_POST['charge'] == $_POST['amount'])) {
		display_error(_("The entered amount is invalid or negative and cannot be processed."));
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

	if (!check_num('discount')) {
		display_error(_("The entered discount is not a valid number."));
		set_focus('discount');
		return false;
	}

	if (input_num('amount') <= 0) {
		display_error(_("The balance of the amount and discount is zero or negative. Please enter valid amounts."));
		set_focus('discount');
		return false;
	}

	if (isset($_POST['bank_amount']) && input_num('bank_amount')<=0)
	{
		display_error(_("The entered payment amount is zero or negative."));
		set_focus('bank_amount');
		return false;
	}
	// Customer & Bank currency  should be sames ##Ramesh
	$cust_currency = $_SESSION['alloc']->set_person($_POST['customer_id'], PT_CUSTOMER);
	$bank_currency = get_bank_account_currency($_POST['bank_account']);
	if($cust_currency != $bank_currency)
	{
		display_error(_("Customer currency and bank currency are not same."));
		set_focus('bank_amount');
		return false;
	}
	
	if (!db_has_currency_rates(get_customer_currency($_POST['customer_id']), $_POST['DateBanked'], true))
		return false;

	$_SESSION['alloc']->amount = input_num('amount');

	if (isset($_POST["TotalNumberOfAllocs"]))
		return check_allocations();
	else
		return true;
}

//----------------------------------------------------------------------------------------------

if (isset($_POST['_customer_id_button'])) {
	//unset($_POST['branch_id']);
	$Ajax->activate('branch_id');
}

//----------------------------------------------------------------------------------------------

if (get_post('AddPaymentItem') && can_process()) {

	new_doc_date($_POST['DateBanked']);
	
	// if($_POST['_ex_rate'])
	// $ex_rate=input_num('_ex_rate');
	// else
	$ex_rate=0;  // ravi	

	$new_pmt = !$_SESSION['alloc']->trans_no;
	//Chaitanya : 13-OCT-2011 - To support Edit feature

	$payment_no = write_customer_pdc($_POST['trans_no'], $_POST['customer_id'], $_POST['branch_id'], $_POST['bank_account'], $_POST['DateBanked'], $_POST['ref'], input_num('amount'), input_num('discount'), $_POST['memo_'], $ex_rate, input_num('charge'), input_num('bank_amount', input_num('amount')), $_POST['dimension_id'], $_POST['dimension2_id'],$_POST['our_ref_no'],$_POST['pdc_cheque_no'],$_POST['pdc_cheque_date'],$_POST['cheque_bank_name'], $_SESSION['pdc_items']->items,$_POST['sales_person_ref']); //ravi

	$_SESSION['pdc_items']->person_type = PT_CUSTOMER;
	$_SESSION['pdc_items']->trans_no = $payment_no;
	$_SESSION['pdc_items']->date = $_POST['DateBanked'];
	$_SESSION['pdc_items']->write();

	unset($_SESSION['pdc_items']);
	// $_SESSION['alloc']->person_type = $payment_no;
	// $_SESSION['alloc']->trans_no = $payment_no;
	// $_SESSION['alloc']->date_ = $_POST['DateBanked'];
	// $_SESSION['alloc']->write();

	//unset($_SESSION['alloc']);
	meta_forward($_SERVER['PHP_SELF'], $new_pmt ? "AddedID=$payment_no" : "UpdatedID=$payment_no");
}

//----------------------------------------------------------------------------------------------

function read_customer_data()
{
	global $Refs;

	$myrow = get_customer_habit($_POST['customer_id']);

	$_POST['HoldAccount'] = $myrow["dissallow_invoices"];
	$_POST['pymt_discount'] = $myrow["pymt_discount"];
	// To support Edit feature
	// If page is called first time and New entry fetch the nex reference number
	if (!$_SESSION['alloc']->trans_no && !isset($_POST['charge'])) 
		$_POST['ref'] = $Refs->get_next(ST_CUSTPDC, null, array(
			'customer' => get_post('customer_id'), 'date' => get_post('DateBanked')));
}

function check_item_data()
{
	if ($_POST['customer_id'] < 1) {
		display_error(_('Please select a customer.'));
		set_focus('customer_id');
		return false;
	}

	if ($_POST['branch_id'] < 1) {
		display_error(_('Please select a branch'));
		set_focus('branch_id');
		return false;
	}

	if (!check_num('line_amount', 0)) {
		display_error(_('The amount entered is not a valid number or is less than zero.'));
		set_focus('line_amount');
		return false;
	}
	

	return true;
	
}

function handle_new_item()
{
	global $Ajax;
	if (!check_item_data()) {
		return;
	}

	$customer_id = $_POST['customer_id'];
	$branch_id = $_POST['branch_id'];
	$invoice_no = $_POST['invoice_no'];
	
	$invoice_os_amount = $_POST['invoice_os_amt'];
	$line_amount = $_POST['line_amount'];
	$customer_name = get_customer_name($customer_id);
	$branch_name = get_branch_name($branch_id);
	
	if ($_SESSION['pdc_items']->find_cart_customer_branch($customer_id,$branch_id))
         display_error(_("For Customer : ") . $customer_name . " " . "This Branch ". $branch_name ." is already on this document. You can change the Branch on the existing line if necessary.");
	else
	$_SESSION['pdc_items']->addItem(
		$customer_id,
		$customer_name,
		$branch_id,
		$branch_name,
		$invoice_no,
		$invoice_os_amount,
		$line_amount
	  );
	  
	unset($_POST['customer_id']); 
	
	line_start_focus();
	$_POST['amount'] = price_format($_SESSION['pdc_items']->getTotalAmount());
	$Ajax->activate('amount');
	
}

function handle_update_item()
{
	global $Ajax;
	$id = $_POST['LineNo'];
	$customer_id = $_POST['customer_id'];
	$branch_id = $_POST['branch_id'];
	$invoice_no = $_POST['invoice_no'];
	$invoice_os_amount = $_POST['invoice_os_amt'];
	$line_amount = price_format($_POST['line_amount']);
	$customer_name = get_customer_name($customer_id);
	$branch_name = get_branch_name($branch_id);

    //if ($_SESSION['pdc_items']->find_cart_customer_branch($customer_id,$branch_id))
         //display_error(_("For Customer : ") . $customer_name . " " . "This Branch ". $branch_name ." is already on this document. You can change the Branch on the existing line if necessary.");
	//else
	$_SESSION['pdc_items']->updateItem(
		$id,
		$customer_id,
		$customer_name,
		$branch_id,
		$branch_name,
		$invoice_no,
		$invoice_os_amount,
		$line_amount
	);
	line_start_focus();
	$_POST['amount'] = price_format($_SESSION['pdc_items']->getTotalAmount());
	$Ajax->activate('amount');
}

function handle_delete_item($id)
{
	global $Ajax;
	$_SESSION['pdc_items']->removeItem($id);
	line_start_focus();
	$_POST['amount'] = price_format($_SESSION['pdc_items']->getTotalAmount());
	$Ajax->activate('amount');
}

function line_start_focus() {
	global $Ajax;

	unset($_POST['customer_id']);
	unset($_POST['branch_id']);
	unset($_POST['line_amount']);
	
	$Ajax->activate('items_table');
	$Ajax->activate('footer');
	set_focus('_code_id_edit');
}

function edit_item_controls($customer_id, $branch_id, $invoice_no, $amount, $line_no=-1)
{
	global $Ajax;
	start_row();

	$dec2 = 0;
    $id = find_submit('Edit');
	
	if ($line_no != -1 && $line_no == $id)
	{
	    $_POST['customer_id']     = $customer_id;
		$_POST['branch_id']       = $branch_id;
		$_POST['line_amount']          = price_format($amount);
		if ($_POST['invoice_no'] == 0) {
			$_POST['invoice_no'] = $invoice_no;
		}
	    $Ajax->activate('items_table');
	}
	// else
	// {
		
		customer_list_cells(null, 'customer_id', null, false, true, false, true);

		if ($customer_id != get_post('customer_id', -1))
		{
			// customer has changed
			$Ajax->activate('branch_id');
			$Ajax->activate('line_amount');
			$Ajax->activate('items_table');
  		}
		
		customer_branches_list_cells(null,
	  	  $_POST['customer_id'], 'branch_id', null, false, true, true, true);
		
		slales_invoice_list_cells(null,'invoice_no', $_POST['invoice_no'], _("Select a invoice"), true, $_POST['customer_id'],$_POST['branch_id']);
		
		$act_amount = get_sales_invoice_amt($_POST['invoice_no']);
		
		label_cell(number_format2($act_amount, user_price_dec()), '', 'invoice_act_amt');
		
		if (get_post('invoice_no')!='')
		{
			$Ajax->activate('invoice_os_amt');
			$Ajax->activate('items_table');
  		}
		
		$invoice_os_amt = 0;
		if ($line_no === -1) {
			  $invoice_os_amt = get_sales_invoice_number($_POST['invoice_no']);
		}
		else {
			$master = $_SESSION['pdc_items'];
			$line_itm = $master->items[$line_no];
			$invoice_os_amt = get_sales_invoice_trans_number(
				$_POST['invoice_no'],
				$line_itm['type'],
				$_POST['trans_no'],
				$line_itm['customer_id']
			);
		}

		label_cell(number_format2($invoice_os_amt, user_price_dec()), '', 'invoice_os_amt');

	    amount_cells(null, 'line_amount');
		$Ajax->activate('line_amount');
		

	if ($line_no != -1)
	{
		//button_cell('UpdateItem', _("Update"),
				//_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
		hidden('LineNo', $line_no);
		//set_focus('qty');
	}
	else
	{
		submit_cells('AddItem', _("Add Item"), "colspan=2",
		    _('Add new item to document'), true);
	}

	end_row();
}

function display_line_items() {

	$id = -1;
	$comp_currency = get_company_currency();
	$cust_currency = $_SESSION['alloc']->set_person($_POST['customer_id'], PT_CUSTOMER);
	if (!$cust_currency)
		$cust_currency = $comp_currency;
	$_SESSION['alloc']->currency = $bank_currency = get_bank_account_currency($_POST['bank_account']);

	div_start('items_table');
	start_table(TABLESTYLE, "width=95%");
	$th = array(
		_("Customer"),
		_("Branch"),
		_("Invoice Ref"),
		_("Actual Amt"),
		_("O/S Amt"),
		_("Amount"),
		"",
	);
	table_header($th);

	$id = get_post('selected_id', find_submit('Edit'));
	//show inserted items
	$amount = 0;
	foreach ($_SESSION['pdc_items']->items as $index => $pdc_item) {
		$amount += $pdc_item['amount'];
		if ($id != $index) {
			start_row();
			label_cell($pdc_item['customer_name']);
			label_cell($pdc_item['branch_name']);
			$invoice_os_amt = get_sales_invoice_trans_number($pdc_item['invoice_no'], ST_CUSTPDC, $_POST['trans_no'], $pdc_item['customer_id']);
			
			label_cell(get_sales_invoice_amt($pdc_item['invoice_no']));
			
			if($pdc_item['invoice_no'] != 0)
			{
				label_cell($pdc_item['invoice_no']." (".get_sales_invoice_amt($pdc_item['invoice_no']).")");
				amount_cell($invoice_os_amt);
			}
			else
			{
				label_cell("");
				label_cell("");
			}
		
			amount_cell($pdc_item['amount']);
			//edit_button_cell("Edit$index", _("Edit"), _('Edit document line'));
			delete_button_cell("Delete$index", _("Delete"), _('Remove line from document'));
			end_row();
		}
		else {
			edit_item_controls($pdc_item['customer_id'], $pdc_item['branch_id'], $pdc_item['invoice_no'], $pdc_item['amount'], $index);
		}
	}
	if ($id == -1) {
		edit_item_controls($_POST['customer_id'], $_POST['branch_id'], 0, $_POST['line_amount'], $id);
	}
	//end of show inserted items
	//$Ajax->activate('amount');
	
	end_table();
	if ($id != -1)
	    hidden('selected_id', $id);
	div_end();
}


$id = find_submit('Delete');
if ($id != -1)
	handle_delete_item($id);
if (isset($_POST['AddItem'])) {
	handle_new_item();
	unset($_POST['selected_id']);
	$_POST['customer_id']='';
}
if (isset($_POST['UpdateItem'])) {
	handle_update_item();
	unset($_POST['selected_id']);
	$_POST['customer_id']='';
}
if (isset($_POST['CancelItemChanges'])) {
	unset($_POST['selected_id']);
	line_start_focus();
}

//----------------------------------------------------------------------------------------------
$new = 1;

// To support Edit feature
if (isset($_GET['trans_no']) && $_GET['trans_no'] > 0 )
{
	$_POST['trans_no'] = $_GET['trans_no'];
	$new = 0;
	$myrow = get_customer_trans_pdc($_POST['trans_no'], ST_CUSTPDC);
	$_POST['customer_id'] = $myrow["debtor_no"];
	$_POST['customer_name'] = $myrow["DebtorName"];
	$_POST['branch_id'] = $myrow["branch_code"];
	$_POST['bank_account'] = $myrow["bank_account"];
	$_POST['our_ref_no'] = $myrow["our_ref_no"];
	$_POST['ref'] =  $myrow["reference"];
	$charge = get_cust_bank_charge(ST_CUSTPDC, $_POST['trans_no']);
	$_POST['charge'] =  price_format($charge);
	$_POST['DateBanked'] =  sql2date($myrow['tran_date']);
	$_POST["amount"] = price_format($myrow['Total'] - $myrow['ov_discount']);
	$_POST["bank_amount"] = price_format($myrow['bank_amount']+$charge);
	$_POST["discount"] = price_format($myrow['ov_discount']);
	$_POST["memo_"] = get_comments_string(ST_CUSTPDC,$_POST['trans_no']);
	/* $_POST['mode_of_payment'] =  $myrow["mode_of_payment"];
	$_POST['cheque_no'] =  $myrow["cheque_no"];
	$_POST['date_of_issue'] =  sql2date($myrow["date_of_issue"]);
	$_POST['dd_no'] =  $myrow["dd_no"];
	$_POST['dd_date_of_issue'] =  sql2date($myrow["dd_date_of_issue"]); 
	$_POST['pymt_ref'] = $myrow["pymt_ref"]; */
	$_POST['cheque_bank_name'] = $myrow["bank_name"];
	$_POST['pdc_cheque_no'] = $myrow["pdc_cheque_no"];
	$_POST['pdc_cheque_date'] = sql2date($myrow["pdc_cheque_date"]);
	
	$_POST['sales_person_ref'] = $myrow["sales_person_ref"];

	$result = get_customer_trans_pdc_line_item($_POST['trans_no'], ST_CUSTPDC);
	
	$_SESSION['pdc_items']->addAllItems($result);

	$_POST['amount'] = $_SESSION['pdc_items']->getTotalAmount();
	unset($_POST['customer_id']); 

	//Prepare allocation cart 
	// if (isset($_POST['trans_no']) && $_POST['trans_no'] > 0 )
	// 	$_SESSION['alloc'] = new allocation(ST_CUSTPDC,$_POST['trans_no']);
	// else
	// {
	// 	$_SESSION['alloc'] = new allocation(ST_CUSTPDC, $_POST['trans_no']);
	// 	$Ajax->activate('alloc_tbl');
	// }
	
}



//----------------------------------------------------------------------------------------------
$new = !$_SESSION['alloc']->trans_no;
start_form();

hidden('trans_no',$_GET['trans_no']);

div_start('payment_mode');

start_outer_table(TABLESTYLE2, "width='60%'", 5);

table_section(1);

// if ($new)
// 	customer_list_row(_("From Customer:"), 'customer_id', null, false, true);
// else {
// 	label_cells(_("From Customer:"), $_SESSION['alloc']->person_name, "class='label'");
// 	hidden('customer_id', $_POST['customer_id']);
// }

// if (db_customer_has_branches($_POST['customer_id'])) {
// 	customer_branches_list_row(_("Branch:"), $_POST['customer_id'], 'BranchID', null, false, true, true);
// } else {
// 	hidden('BranchID', ANY_NUMERIC);
// }

if (list_updated('customer_id') || ($new && list_updated('bank_account'))) {
	$_SESSION['alloc']->set_person($_POST['customer_id'], PT_CUSTOMER);
	$_SESSION['alloc']->read();
	$_POST['memo_'] = $_POST['amount'] = $_POST['discount'] = '';
	if (list_updated('customer_id')) {
		$dflt_act = get_default_bank_account($_SESSION['alloc']->person_curr);
		//$_POST['bank_account'] = $dflt_act['id'];
	}
	$Ajax->activate('_page_body');
}


bank_accounts_list_trans_row(_("Into Bank Account:"), 'bank_account', null, true, _("Select a Bank"), true);



if (isset($_GET['trans_no']) && $_GET['trans_no'] > 0 )
{
	//label_row(_("Ref No.(For Allocation Purpose EDIT):"),$_POST['sales_person_ref']);
	$last_sales_person_ref = get_last_pdc_ref_no_for_allocation(ST_CUSTPDC);
	//display_error($last_sales_person_ref." - REF");
	hidden('sales_person_ref', $last_sales_person_ref);
}
else{
//label_row(_("Last Ref No.(For Allocation Purpose):"),get_last_pdc_ref_no_for_allocation(ST_CUSTPDC));
//text_row(_("Ref No.<b style='color:red;'>*</b>(For Allocation Purpose)"), 'sales_person_ref', null, 20, 20);

$last_sales_person_ref = get_last_pdc_ref_no_for_allocation(ST_CUSTPDC);
$_POST['sales_person_ref'] = $last_sales_person_ref+1;
//label_row(_("Ref No.(For Allocation Purpose):"),$_POST['sales_person_ref']);
hidden('sales_person_ref', $_POST['sales_person_ref']);

}

read_customer_data();

set_global_customer($_POST['customer_id']);
if (isset($_POST['HoldAccount']) && $_POST['HoldAccount'] != 0)	
	display_warning(_("This customer account is on hold."));
$display_discount_percent = percent_format($_POST['pymt_discount']*100) . "%";

table_section(2);

date_row(_("Date of Received:"), 'DateBanked', '', true, 0, 0, 0, null, true);

ref_row(_("Reference:"), 'ref','' , null, '', ST_CUSTPDC);

text_row(_("Our Ref No."), 'our_ref_no', null, 16, 15);

table_section(3);

$comp_currency = get_company_currency();
$cust_currency = $_SESSION['alloc']->set_person($_POST['customer_id'], PT_CUSTOMER);
if (!$cust_currency)
	$cust_currency = $comp_currency;
$_SESSION['alloc']->currency = $bank_currency = get_bank_account_currency($_POST['bank_account']);

if ($cust_currency != $bank_currency)
{
	amount_row(_("Payment Amount:"), 'bank_amount', null, '', $bank_currency);
}

amount_row(_("Bank Charge:"), 'charge', null, '', $bank_currency);
//ravi
exchange_rate_display($comp_currency,  $_SESSION['alloc']->set_person($_POST['customer_id'], PT_CUSTOMER),
			$_POST['DateBanked']);  //ravi

$row = get_customer($_POST['customer_id']);
if ($row) {
	$_POST['dimension_id'] = $row['dimension_id'];
	$_POST['dimension2_id'] = $row['dimension2_id'];
}

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

//line items
display_line_items();
//end of line items

// div_start('alloc_tbl');
// show_allocatable(false);
// div_end();

start_table(TABLESTYLE, "width='40%'");
start_row();

echo '<h5 align="center" style="color:green; font-size: 11px;"><a style="color:green; font-size: 11px;" target="_blank" href="./inquiry/customer_inquiry.php?filterType=6"  >Previous Transactions</a> </h5>';


end_row();
end_table();

start_table(TABLESTYLE, "width='60%'");

label_row(_("Customer prompt payment discount :"), $display_discount_percent);

amount_row(_("Amount of Discount:"), 'discount', null, '', $cust_currency);

// amount_row(_("Amount:"), 'amount', null, '', $cust_currency);
amount_row(_("Amount:"), 'amount', $amount, '', $cust_currency);

textarea_row(_("Memo:"), 'memo_', null, 22, 4);
end_table(1);

if ($_GET['trans_no'] == 0)
	submit_center('AddPaymentItem', _("Add PDC"), true, '', 'default');
else
	submit_center('AddPaymentItem', _("Update PDC"), true, '', 'default');

br();

end_form();
end_page();
