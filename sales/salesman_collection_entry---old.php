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
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/ui/salesman_collection_ui.inc");
include_once($path_to_root . "/sales/includes/db/sm_collection_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

$_SESSION['page_title'] = _($help_context = "Salesman Collection Entry");
		$_SESSION['sm_collection_items'] = new sm_collection_items_cart(ST_CUSTPAYMENT);

page($_SESSION['page_title'], false, false, "", $js);

//--------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_SERVICE_VENDOR_QUOTE;
	
 
    display_notification_centered(_("Vendor Service Quotation has been processed"));
    display_note(get_service_trans_view_str($trans_type, $trans_no, _("&View this Vendor Service Quotation")));
	
	br();
	submenu_print(_("&Print This Vendor Service Quotation"), ST_SERVICE_VENDOR_QUOTE, $trans_no, 'prtopt');

	 hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Vendor Service Quotation"), "NewVendorQuote=1");
  

	hyperlink_params("$path_to_root/admin/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$trans_no");

	display_footer_exit();
}



else if(isset($_GET['UpdatedID'])){
	$trans_no = $_GET['UpdatedID'];
	$trans_type = ST_SERVICE_VENDOR_QUOTE;

   display_notification_centered(_("Vendor Service Quotation has been updated"));

  display_note(get_service_trans_view_str($trans_type, $trans_no, _("&View this Vendor Service Quotation")));
  
  br();
  submenu_print(_("&Print This Vendor Service Quotation"), ST_SERVICE_VENDOR_QUOTE, $trans_no, 'prtopt');

  hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Another Vendor Service Quotation"), "NewVendorQuote=1");

  hyperlink_params("$path_to_root/admin/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$trans_no");

	display_footer_exit();
}
//--------------------------------------------------------------------------------------------------

function line_start_focus() {
  global 	$Ajax;

  $Ajax->activate('items_table');
  set_focus('_stock_id_edit');
}
//-----------------------------------------------------------------------------------------------

function handle_new_order()
{

	if (isset($_SESSION['sm_collection_items']))
	{
		$_SESSION['sm_collection_items']->clear_items();
		unset ($_SESSION['sm_collection_items']);
	}

    $_SESSION['sm_collection_items'] = new sm_collection_items_cart(ST_CUSTPAYMENT);

	$_POST['date_'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['date_']))
		$_POST['date_'] = end_fiscalyear();
	$_SESSION['sm_collection_items']->tran_date = $_POST['date_'];	
}

//-----------------------------------------------------------------------------------------------

function can_process()
{
	global $SysPrefs;

	$adj = &$_SESSION['sm_collection_items'];

	if (count($adj->line_items) == 0)	{
		display_error(_("You must enter at least one non empty item line."));
		set_focus('stock_id');
		return false;
	}

    if (isset($_GET['NewVendorQuote'])) {
	if (!check_reference($_POST['reference'], ST_SERVICE_VENDOR_QUOTE))
	{
		set_focus('reference');
		return false;
	}
	}

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
	
	return true;
}

//-------------------------------------------------------------------------------

if (isset($_POST['Process']) && can_process()){
	
	$trans_no = add_vendor_service_quotation_entry($_SESSION['sm_collection_items']->line_items,
		$_POST['supplier_id'], $_POST['date_'],	$_POST['reference'],$_POST['memo_']);
	new_doc_date($_POST['date_']);
	$_SESSION['sm_collection_items']->clear_items();
	unset($_SESSION['sm_collection_items']);

  if ($fixed_asset)
   	meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no&FixedAsset=1");
  else
   	meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");

} 


if(isset($_POST['Update']) && can_process()){
	
	
	$trans_no = update_vendor_service_quotation_entry($_SESSION['sm_collection_items'],
		$_POST['supplier_id'],$_POST['date_'],$_POST['memo_']);
	
	//display_error(json_encode($_SESSION['adj_items'])); die;
	$_SESSION['sm_collection_items']->clear_items();
	unset($_SESSION['sm_collection_items']);

  if ($fixed_asset)
   	meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$indent_no&FixedAsset=1");
  else
   	meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$trans_no");
}


//-----------------------------------------------------------------------------------------------

function check_item_data()
{
	
	
	
   	return true;
}

//--------------------------------------------------------------------------------------

function handle_update_item()
{
	
	$id = $_POST['LineNo'];
   	$_SESSION['sm_collection_items']->update_cart_item($id, $_POST['tool_serial_number'],$_POST['tool_description'],input_num('price'));
	
	unset($_POST['sm_collection_items'],$_POST['tool_serial_number'],$_POST['tool_description'],$_POST['price']);
		
	line_start_focus();
}

//-------------------------------------------------------------------------------------

function handle_delete_item($id)
{
	$_SESSION['sm_collection_items']->remove_from_cart($id);
	line_start_focus();
}

//---------------------------------------------------------------------------------------

function handle_new_item()
{
	add_to_order($_SESSION['sm_collection_items'], $_POST['customer_id'], $_POST['branch_id'], $_POST['invoice_ref'], input_num('invoice_amt'), input_num('amount'));
	
	unset($_POST['invoice_ref'],$_POST['invoice_amt'],$_POST['amount']);
	line_start_focus();
}

//--------------------------------------------------------------------------------------
$id = find_submit('Delete');



if ($id != -1)
	handle_delete_item($id);

if (isset($_POST['AddItem']) && check_item_data()) {
	
	
	
	handle_new_item();
	unset($_POST['selected_id']);
	
	
}
if (isset($_POST['UpdateItem']) && check_item_data()) {
	handle_update_item();
	unset($_POST['selected_id']);
}
if (isset($_POST['CancelItemChanges'])) {
	unset($_POST['selected_id']);
	line_start_focus();
}
//---------------------------------------------------------------------------------------

if (!isset($_SESSION['sm_collection_items']))
{
	handle_new_order();
}

//--------------------------------------------------------------------------------------
start_form();


$items_title = _("Customer Invoices");
$button_title = _("Submit");

display_salesman_collection_header($_SESSION['sm_collection_items']);

start_outer_table(TABLESTYLE, "width='70%'", 10);

display_salesman_collection_items($items_title, $_SESSION['sm_collection_items']);
delivery_options_controls();

end_outer_table(1, false);

submit_center_first('Process', $button_title, '', 'default');

end_form();
end_page();

