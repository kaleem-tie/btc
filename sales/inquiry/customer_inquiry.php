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
$page_security = 'SA_TRANS_INQ_SALES_REP';
$path_to_root = "../..";
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Customer Transactions"), isset($_GET['customer_id']), false, "", $js);

//------------------------------------------------------------------------------------------------

function systype_name($dummy, $type)
{
	global $systypes_array;

	return $systypes_array[$type];
}

function order_view($row)
{
	return $row['order_']>0 ?
		get_customer_trans_view_str(ST_SALESORDER, $row['order_'])
		: "";
}

function trans_view($trans)
{
	return get_trans_view_str($trans["type"], $trans["trans_no"],"", false,"","", $trans["ref_no"]);
}

function due_date($row)
{
	return	$row["type"] == ST_SALESINVOICE	? $row["due_date"] : '';
}

function gl_view($row)
{
	return get_gl_view_str($row["type"], $row["trans_no"]);
}

function fmt_amount($row)
{
	$value =
	    $row['type']==ST_CUSTCREDIT || $row['type']==ST_CUSTPAYMENT || $row['type']==ST_BANKDEPOSIT || $row['type']==ST_CUSTPDC  ? -$row["TotalAmount"] : $row["TotalAmount"];
    return price_format($value);
}

function credit_link($row)
{
	global $page_nested;

	if ($page_nested)
		return '';
	if($row['type'] != ST_CUSTPDC){
		if ($row["Outstanding"] > 0)
		{
			if ($row['type'] == ST_CUSTDELIVERY)
				return pager_link(_('Invoice'), "/sales/customer_invoice.php?DeliveryNumber=" 
					.$row['trans_no'], ICON_DOC);
			else if ($row['type'] == ST_SALESINVOICE)
				return pager_link(_("Credit This") ,
				"/sales/customer_credit_invoice.php?InvoiceNumber=". $row['trans_no'], ICON_CREDIT);
		}	
	}
	else
	{
			if( $row['current_pdc_status']==0)
				return pager_link(_("Recall PDC") , "/sales/salesman_collection_entry.php?PdcNumber=". $row['trans_no'], ICON_DOC);	
			//"/sales/customer_payments.php?PdcNumber=". $row['trans_no'], ICON_DOC);	
 // return pager_link(_("Recall PDC") ,"/sales/recall_pdc.php?PdcNumber=".$row['trans_no'], ICON_DOC);
			
		}	
}

function return_link($row){
	if($row['type'] == ST_CUSTPDC){
	if($row['current_pdc_status']==0 || $row['current_pdc_status']==1)
			return pager_link(_("PDC Return") ,
			"/sales/return_pdc.php?PdcNumber=". $row['trans_no'], ICON_DOC);
	}else{
		return '';
	}	
}
function bounce_link($row){
	if($row['type'] == ST_CUSTPDC){
	if( $row['current_pdc_status']==0)
			return pager_link(_("PDC Bounce") ,
			"/sales/bounce_pdc.php?PdcNumber=". $row['trans_no'], ICON_DOC);
	}else{
		return '';
	}	
}

function edit_link($row)
{
	global $page_nested;

	$str = '';
	if ($page_nested)
		return '';
	
	if ($row['type'] == ST_SALESINVOICE){
		if (user_check_access('SA_SALES_INV_EDIT')) {
			return $row['type'] == ST_CUSTCREDIT && $row['order_'] ? '' : trans_editor_link($row['type'], $row['trans_no'], $row['ref_no']);
		}		 
		else{	
			if($row['signed_collection_status'] == 0) 	
			return $row['type'] == ST_CUSTCREDIT && $row['order_'] ? '' : trans_editor_link($row['type'], $row['trans_no'], $row['ref_no']);
		}
	}
	/*else if ($row['type'] == ST_CUSTDELIVERY){
		return "";
	}*/
	else if ($row['type'] == ST_CUSTCREDIT){
		return "";
	}
	else
	{
		return $row['type'] == ST_CUSTCREDIT && $row['order_'] ? '' : trans_editor_link($row['type'], $row['trans_no'], $row['ref_no']);
	}
}

function prt_link($row)
{
  	if ($row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_BANKDEPOSIT) 
		return print_document_link($row['trans_no']."-".$row['type'], _("Print Receipt"), true, ST_CUSTPAYMENT, ICON_PRINT,'printlink', '', 0, 0, $row['ref_no']);
  	elseif ($row['type'] == ST_BANKPAYMENT) // bank payment printout not defined yet.
		return '';
	elseif ($row['type'] == ST_CUSTPDC) // bank payment printout not defined yet.
	  return print_document_link($row['trans_no']."-".$row['type'], _("PDC Print"), true, ST_CUSTPDC, ICON_PRINT);
 	else
 		return print_document_link($row['trans_no']."-".$row['type'], _("Print"), true, $row['type'], ICON_PRINT, 'printlink', '', 0, 0, $row['ref_no']);
}




function check_overdue($row)
{
	return $row['OverDue'] == 1
		&& floatcmp(ABS($row["TotalAmount"]), $row["Allocated"]) != 0;
}

/*  Sales invoice Edit 23-05-2024 
function invoice_edit_link($row){
	
	$so_ref = get_inv_sales_order_reference($row['order_']);
	
	if($so_ref=='auto'){
			return pager_link(_("Invoice Edit") ,
			"/sales/sales_order_entry.php?NewInvoice=0&ModifyInvNumber=". $row['trans_no'], ICON_DOC);
	}else{
		return '';
	}	
}
*/

//Invoice and Delivery Edit link   23-05-2024     
function invoice_edit_link($row){
	
  	
	if($row['type'] == ST_SALESINVOICE){
	 $so_ref = get_inv_sales_order_reference($row['order_']);
	 if($so_ref=='auto')
	 {
			return pager_link(_("Invoice Edit") ,
			"/sales/sales_order_entry.php?NewInvoice=0&ModifyInvNumber=". $row['trans_no'], ICON_DOC);
	 }else{
		return '';
	 }
	}
	else if ($row['type'] == ST_CUSTDELIVERY){
		
	   $invoicedStatus = isDoInvoiced($row['order_']);
	   $so_ref = get_inv_sales_order_reference($row['order_']);
	   
	   if($invoicedStatus == 0 && $row['reference']!= 'auto' && $so_ref == 'auto') {
		  return pager_link(_("Delivery Edit") ,
			"/sales/sales_order_entry.php?NewDelivery=0&ModifyDelNumber=". $row['trans_no'], ICON_DOC);
	   }
	   else{
		return '';
	   }
	}
	
    else{
		return '';
	 }	
}

//------------------------------------------------------------------------------------------------

function display_customer_summary($customer_record)
{
	$past1 = get_company_pref('past_due_days');
	$past2 = 2 * $past1;
	$past3 = 3 * $past1;
    if ($customer_record["dissallow_invoices"] != 0)
    {
    	echo "<center><font color=red size=4><b>" . _("CUSTOMER ACCOUNT IS ON HOLD") . "</font></b></center>";
    }

	$nowdue = "1-" . $past1 . " " . _('Days');
	$pastdue1 = $past1 + 1 . "-" . $past2 . " " . _('Days');
	$pastdue2 = $past2 + 1 . "-" . $past3 . " " . _('Days');
	$pastdue3 = _('Over') . " " . $past3 . " " . _('Days');

    start_table(TABLESTYLE, "width='80%'");
    $th = array(_("Currency"), _("Terms"), _("Current"), $nowdue,
    	$pastdue1, $pastdue2,$pastdue3, _("Total Balance"));
    table_header($th);

	start_row();
    label_cell($customer_record["curr_code"]);
    label_cell($customer_record["terms"]);
	amount_cell($customer_record["Balance"] - $customer_record["Due"]);
	amount_cell($customer_record["Due"] - $customer_record["Overdue1"]);
	amount_cell($customer_record["Overdue1"] - $customer_record["Overdue2"]);
	amount_cell($customer_record["Overdue2"] - $customer_record["Overdue3"]);
	amount_cell($customer_record["Overdue3"]);
	amount_cell($customer_record["Balance"]);
	end_row();

	end_table();
}

if (isset($_GET['customer_id']))
{
	$_POST['customer_id'] = $_GET['customer_id'];
}


if (isset($_GET['filterType']))
{
	$_POST['filterType'] = $_GET['filterType'];
}

//------------------------------------------------------------------------------------------------

start_form();

$dim = get_company_pref('use_dimension');

if (!isset($_POST['customer_id']))
	$_POST['customer_id'] = get_global_customer();

start_table(TABLESTYLE_NOBORDER);

start_row();

if (!$page_nested)
	customer_list_cells(_("Select a customer: "), 'customer_id', null, true, true, false, true);

//ref_cells(_("Ref / Invoice No.:"), 'trans_no'); 

ref_cells(_("Ref / Invoice No.:"), 'ref_invoice_no', '',null, '', true);

cust_allocations_list_cells(null, 'filterType', null, true, true);

if ($dim >= 1)
	dimensions_list_cells(_("Dimension")." 1:", 'Dimension', null, true, 
    _("All Dimensions"), false, 1,true);
if ($dim > 1)
	dimensions_list_cells(_("Dimension")." 2:", 'Dimension2', null, true, 
   _("All Dimensions"), false, 2,true);

if ($_POST['filterType'] != '2')
{
	
	$fy_res=get_current_fiscalyear();
	$end_day = sql2date($fy_res["end"]);
	$start_day = sql2date($fy_res["begin"]);
	$today_date =  sql2date(date('Y-m-d'));
	$start_no_days = date_diff2($start_day,$today_date, "d");
	$end_no_days = date_diff2($today_date, $end_day,"d");
	date_cells(_("From:"), 'TransAfterDate', '', null, ($start_no_days+0));
	date_cells(_("To:"), 'TransToDate', '', null,(-$end_no_days+0));
	
	// date_cells(_("From:"), 'TransAfterDate', '', null, -user_transaction_days());
	
}
check_cells(_("Zero values"), 'show_voided');

submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');
end_row();
end_table();

set_global_customer($_POST['customer_id']);

//------------------------------------------------------------------------------------------------

div_start('totals_tbl');
if ($_POST['customer_id'] != "" && $_POST['customer_id'] != ALL_TEXT)
{
	$customer_record = get_customer_details(get_post('customer_id'), get_post('TransToDate'),'',$_POST['Dimension'], $_POST['trans_no']);
    display_customer_summary($customer_record);
    echo "<br>";
}
div_end();

if (get_post('RefreshInquiry') || list_updated('filterType'))
{
	$Ajax->activate('_page_body');
}
//------------------------------------------------------------------------------------------------

if (!isset($_POST['Dimension']))
    $_POST['Dimension'] = 0;
if (!isset($_POST['Dimension2']))
    $_POST['Dimension2'] = 0;


$sql = get_sql_for_customer_inquiry(get_post('TransAfterDate'), get_post('TransToDate'),
	get_post('customer_id'), get_post('filterType'), check_value('show_voided'),$_POST['Dimension'], $_POST['Dimension2'], $_POST['ref_invoice_no']);
//$sql = '';
//------------------------------------------------------------------------------------------------
//db_query("set @bal:=0");

$cols = array(
	_("Type") => array('fun'=>'systype_name', 'ord'=>''),
	_("#") => array('fun'=>'trans_view', 'ord'=>'', 'align'=>'right'),
	_("Order") => array('fun'=>'order_view', 'align'=>'right'), 
	_("Reference"), 
	_("Date") => array('name'=>'tran_date', 'type'=>'date', 'ord'=>'desc'),
	_("Due Date") => array('type'=>'date', 'fun'=>'due_date'),
	_("Customer") => array('ord'=>''), 
	_("Branch") => array('ord'=>''), 
	_("Currency") => array('align'=>'center'),
	_("Amount") => array('align'=>'right', 'fun'=>'fmt_amount'), 
	_("Balance") => array('align'=>'right', 'type'=>'amount'),
		array('insert'=>true, 'fun'=>'gl_view'),
	_("") =>	array('insert'=>true, 'fun'=>'edit_link'),
		array('insert'=>true, 'fun'=>'credit_link'),
		//array('insert'=>true, 'fun'=>'return_link'),
		array('insert'=>true, 'fun'=>'bounce_link'),
		array('insert'=>true, 'fun'=>'prt_link'),
		array('insert'=>true, 'fun'=>'invoice_edit_link')
	);


if ($_POST['customer_id'] != ALL_TEXT) {
	$cols[_("Customer")] = 'skip';
	$cols[_("Currency")] = 'skip';
}
if ($_POST['filterType'] != '2')
	$cols[_("Balance")] = 'skip';

$table =& new_db_pager('trans_tbl', $sql, $cols);
$table->set_marker('check_overdue', _("Marked items are overdue."));

$table->width = "85%";

display_db_pager($table);

end_form();
end_page();
