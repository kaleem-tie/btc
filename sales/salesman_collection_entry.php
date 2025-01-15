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
$page_security = 'SA_SALESMAN_COLLECTION_ENTRY';
$path_to_root = "..";

include_once($path_to_root . "/sales/includes/sm_collection_cart.inc");
include($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/ui/salesman_collection_ui.inc");
include_once($path_to_root . "/sales/includes/db/sm_collection_db.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/includes/references.inc");
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();



if (isset($_GET['NewCollection']) || isset($_GET['ModifyCollection'])) {
	
		$_SESSION['page_title'] = _($help_context = "SalesMan Collection Entry");
		$_SESSION['sm_collection_items'] = new sm_collection_cart(ST_CUSTPAYMENT);
				
		if (isset($_GET['NewCollection']))
        {
		 
		 $_POST['NewCollection'] = $_GET['NewCollection'];
        }
		else {
			$_POST['ModifyCollection'] = $_GET['ModifyCollection'];
		}
		
}
else if (isset($_GET['PdcNumber'])) {
	$_SESSION['page_title'] = _($help_context = "PDC Recall Entry");
	$_SESSION['sm_collection_items'] = new sm_collection_cart(ST_CUSTPAYMENT);
	
}

page($_SESSION['page_title'], false, false, "", $js);

check_db_has_customers(_("There are no customers defined in the system."));

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//-----------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_CUSTPAYMENT;
    display_notification_centered(_("SalesMan Collection has been processed"));
	// display_note(get_project_trans_view_str($trans_type, $trans_no, _("&View this SalesMan Collection")));
	display_note(get_customer_trans_view_str($trans_type, $trans_no, _("&View this SalesMan Collection"), false, '', '', $_GET['ref_no']));
	
	br();
	
	display_note(print_document_link($trans_no."-".ST_CUSTPAYMENT, _("&Print This SalesMan Collection"), true, ST_CUSTPAYMENT, false, 'printlink','', 0, 0,$_GET['ref_no']), 0, 1);
	
	 hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another SalesMan Collection Entry"), "NewCollection=1");
	 br();
	 $sales_person_ref = get_cust_payment_sales_person_ref($trans_no);
	 submenu_option(_("Allocate Customer Payments (SalesManwise)"),
		"/sales/allocations/salesman_customer_allocation_main.php?sales_person_ref=$sales_person_ref");
	hyperlink_params("$path_to_root/admin/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$trans_no");
	display_footer_exit();
}
// kadar start
if (isset($_GET['UpdatedID']))
{
	$trans_no = $_GET['UpdatedID'];
	$trans_type = ST_CUSTPAYMENT;

   	display_notification_centered(sprintf(_("SalesMan Collection %d has been modified"), $trans_no));

	display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Postings for this Payment")));

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another SalesMan Collection Entry"), "NewPayment=yes");

	display_footer_exit();
}

// To support Edit feature
if (isset($_GET['ref_no']) && $_GET['ref_no'] > 0 )
{
	$new = 0;
	$myrow = get_selsmancollection_header($_GET['ref_no'], ST_CUSTPAYMENT);
	
	$_POST['bank_account'] = $myrow["bank_account"];
	$_POST['date_'] = implode('/', array_reverse(explode('-', $myrow["tran_date"])));
	$_POST['sales_person_id'] = $myrow["sales_person_id"];
	$_POST['sales_person_ref'] = $myrow["sales_person_ref"];
	$_POST['ref_no'] =  $myrow["ref_no"];
	
	$trans_id = get_trans_no_cust_pay($_GET['ref_no'], ST_CUSTPAYMENT);
	
	//display_error($trans_id);
	
	$myrow1 = get_comments_selsmancollection(ST_CUSTPAYMENT, $trans_id);
	
	//display_error($myrow1);
	$_POST['memo_'] = $myrow1;
	
	$result = get_selsmancollection_line_item($_GET['ref_no'], ST_CUSTPAYMENT);
	
	$_SESSION['sm_collection_items']->add_all_cart_items($result);
	
}


if (isset($_GET['PdcNumber']) && $_GET['PdcNumber'] > 0 )
{
	$new = 0;
	
	$trans_no = $_GET['PdcNumber']; 
	$myrow = get_selsmancollection_header_pdc($trans_no, ST_CUSTPDC);
	
	$_POST['bank_account'] = $myrow["bank_account"];
	$_POST['date_'] = implode('/', array_reverse(explode('-', $myrow["tran_date"])));
	$_POST['sales_person_id'] = $myrow["sales_person_id"];
	$_POST['sales_person_ref'] = $myrow["sales_person_ref"];
	$_POST['ref_no'] =  $myrow["ref_no"];
	
	$result = get_selsmancollection_line_item_pdc($trans_no, ST_CUSTPDC);
	$_SESSION['sm_collection_items']->add_all_cart_items($result);
}


//----------------------------------------------------------------------------------------------
// kadar end
//--------------------------------------------------------------------------------

function line_start_focus() {
  global 	$Ajax;

  $Ajax->activate('items_table');
  set_focus('_stock_id_edit');
  unset($_POST['customer_id']);
}


//----------------------------------------------------------------------------------------



function handle_new_order()
{

	if (isset($_SESSION['sm_collection_items']))
	{
		$_SESSION['sm_collection_items']->clear_items();
		unset ($_SESSION['sm_collection_items']);
	}

    $_SESSION['sm_collection_items'] = new sm_collection_cart(ST_CUSTPAYMENT);

	$_POST['date_'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['date_']))
		$_POST['date_'] = end_fiscalyear();
	$_SESSION['sm_collection_items']->tran_date = $_POST['date_'];	
	unset($_POST['customer_id']);
	$_POST["ref_no"] = 0;
}

//---------------------------------------------------------------------------------


function can_process()
{
	global $SysPrefs, $Refs;
	
	$adj = &$_SESSION['sm_collection_items'];
	$input_error = 0;

	if (count($adj->line_items) == 0)	{
		display_error(_("You must enter at least one non empty line."));
		set_focus('stock_id');
		return false;
	}
	
	/* if ($_POST['ref_no'] ==0 ){
		if (!check_reference($_POST['ref'], ST_CUSTPAYMENT, @$_POST['trans_no'])) {
			set_focus('ref');
			return false;
		}
	} */
    //if (isset($_GET['NewCollection'])) {
	 /*	
	 if (!check_reference($_POST['reference'], ST_CUSTPAYMENT))
	 {
		set_focus('reference');
		return false;
	 }*/
	 
	
	if (!is_date($_POST['date_'])) 
	{
		display_error(_("The entered date is invalid."));
		set_focus('AdjDate');
		return false;
	} 
	elseif (!is_date_in_fiscalyear($_POST['date_'])) 
	{
		display_error(_("The entered date is out of fiscal year or is closed for further data entry."));
		set_focus('date_');
		return false;
	}
	
	
	if($_POST['sales_person_ref'] ==''){ 
		
			display_error(_("Sales person ref no. must be entered."));
			set_focus('sales_person_ref');
			return false;
	}
	
	
	/*
	if($_POST['bank_account']==1){
		$sales_person_ref_cash_exist = check_exists_cash_sales_person_ref_no(trim($_POST['sales_person_ref']), $_POST['ref_no']);
	   if($sales_person_ref_cash_exist!=0   && $_POST['trans_no']==0){
		   
		   $sp_cash_ref = get_cash_sales_payemnt_reference_by_sales_person_ref($_POST['sales_person_ref']);
		   
		   $input_error = 1;
		   display_error(_("Sales person ref no. should be unique.It is already entered for sales payment - ".$sp_cash_ref));
		   set_focus('sales_person_ref');
		   return false;
	   }
	}
	else{
		$sales_person_ref_credit_exist = check_exists_credit_sales_person_ref_no(trim($_POST['sales_person_ref']), $_POST['ref_no']);
	   if($sales_person_ref_credit_exist!=0   && $_POST['trans_no']==0 ){
		   $sp_credit_ref = get_credit_sales_payemnt_reference_by_sales_person_ref($_POST['sales_person_ref']);
		   $input_error = 1;
		    display_error(_("Sales person ref no. should be unique.It is already entered for sales payment - ".$sp_credit_ref));
		   set_focus('sales_person_ref');
		   return false;
	   }
	}
	*/
	
	$sales_person_ref_exist = check_exists_sales_person_ref_no(trim($_POST['sales_person_ref']), $_POST['ref_no']);
	   if($sales_person_ref_exist!=0   && $_POST['trans_no']==0){
		   $salesman_ref = get_salesman_person_ref_of_payment($_POST['sales_person_ref']);
		   $input_error = 1;
		   display_error(_("Sales person ref no. should be unique.It is already entered for sales payment - ".$salesman_ref));
		   set_focus('sales_person_ref');
		   return false;
	   }
	
	
	return true;
}

//-------------------------------------------------------------------------------

if (isset($_POST['Process']) && can_process())
{
	$output = add_salesman_collection_entry($_SESSION['sm_collection_items']->line_items,
		$_POST['bank_account'],  $_POST['date_'],$_POST['sales_person_id'],
		$_POST['sales_person_ref'],$_POST['memo_'],$_POST["ref_no"], $_POST['PdcNumber'], $_POST['ref']);	

	$x_trans = $output['trans_no'];
	$ref_no = $output['ref_no'];
	new_doc_date($_POST['date_']);
	$_SESSION['sm_collection_items']->clear_items();
	unset($_SESSION['sm_collection_items']);

	if ($fixed_asset)
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$x_trans&FixedAsset=1");
	else
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$x_trans&ref_no=$ref_no");

} 


//-----------------------------------------------------------------------------------------------

function check_item_data()
{
	
	if (!check_num('amount', 0)) {
			display_error( _("Amount for line item must be entered and can not be less than 0"));
			set_focus('amount');
			return false;
	}
		
	
	if(get_post('invoice_no')!=0){
	  //$invoice_os_amt = get_sales_invoice_number(get_post('invoice_no'));
	  $invoice_os_amt = 0;
	  if (isset($_GET['PdcNumber']) && $_GET['PdcNumber'] > 0 )
	  {
		$invoice_os_amt = get_sales_invoice_trans_number(get_post('invoice_no'), ST_CUSTPDC, get_post('trans_no'));
	  }
	  else
	  {
		$invoice_os_amt = get_sales_invoice_trans_number(get_post('invoice_no'), ST_CUSTPAYMENT, get_post('trans_no'));
	  }

	  if (input_num('amount') > $invoice_os_amt) {
		//display_error("Entered amount cannot be greater than invoice outstanding amount.");
		set_focus('invoice_no');
		return false;
	 }
	} 
	 
   	return true;
}

//-----------------------------------------------------------------------------------------------

function handle_update_item()
{
	$id = $_POST['LineNo'];
   	$_SESSION['sm_collection_items']->update_cart_item($id,$_POST['customer_id'],
	$_POST['branch_id'],$_POST['invoice_no'],input_num('amount'));
	
	unset($_POST['sm_collection_items'],$_POST['customer_id'],$_POST['branch_id'],
	$_POST['invoice_no'],$_POST['amount']);
		
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_delete_item($id)
{
	$_SESSION['sm_collection_items']->remove_from_cart($id);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_new_item()
{
	
	add_to_order($_SESSION['sm_collection_items'], $_POST['customer_id'],
	$_POST['branch_id'],$_POST['invoice_no'],input_num('amount'));
	
	unset($_POST['sm_collection_items'],$_POST['customer_id'],$_POST['branch_id'],
	$_POST['invoice_no'],$_POST['amount']);
    //unset($_POST['customer_id']);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------
$id = find_submit('Delete');
if ($id != -1)
	handle_delete_item($id);

if (isset($_POST['AddItem']) && check_item_data()) {
	handle_new_item();
	unset($_POST['selected_id']);
	$_POST['customer_id']='';
}
if (isset($_POST['UpdateItem']) && check_item_data()) {
	handle_update_item();
	unset($_POST['selected_id']);
	$_POST['customer_id']='';
}
if (isset($_POST['CancelItemChanges'])) {
	unset($_POST['selected_id']);
	line_start_focus();
}

//----------------------------------------------------------------------------------------------

	
//-----------------------------------------------------------------------------------------------

if (!isset($_SESSION['sm_collection_items']) || isset($_GET['NewCollection']))
{
	handle_new_order();
}

//-----------------------------------------------------------------------------------------------


start_form();


$items_title = _("Collection Details");
$button_title = _("Process Collection");

display_sm_collection_header($_SESSION['sm_collection_items']);
start_outer_table(TABLESTYLE, "width='70%'", 10);
display_sm_collection_items($items_title, $_SESSION['sm_collection_items']);
sm_collection_controls($_SESSION['sm_collection_items']);


end_outer_table(1, false);

hidden('PdcNumber', $_GET['PdcNumber']);
hidden('NewCollection',$_GET['NewCollection']);


submit_center_first('Process', $button_title, '', 'default');


end_form();
end_page();

