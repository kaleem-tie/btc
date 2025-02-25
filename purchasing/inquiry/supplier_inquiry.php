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
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Supplier Inquiry"), isset($_GET['supplier_id']), false, "", $js);

if (isset($_GET['supplier_id'])){
	$_POST['supplier_id'] = $_GET['supplier_id'];
}

if (isset($_GET['FromDate'])){
	$_POST['TransAfterDate'] = $_GET['FromDate'];
}
if (isset($_GET['ToDate'])){
	$_POST['TransToDate'] = $_GET['ToDate'];
}

//------------------------------------------------------------------------------------------------

function display_supplier_summary($supplier_record)
{
	$past1 = get_company_pref('past_due_days');
	$past2 = 2 * $past1;
	$past3 = 3 * $past1;
	$nowdue = "1-" . $past1 . " " . _('Days');
	$pastdue1 = $past1 + 1 . "-" . $past2 . " " . _('Days');
	$pastdue2 = _('Over') . " " . $past2 . " " . _('Days');
	$pastdue3 = _('Over') . " " . $past3 . " " . _('Days');
	

    start_table(TABLESTYLE, "width='80%'");
    $th = array(_("Currency"), _("Terms"), _("Current"), $nowdue,
    	$pastdue1, $pastdue2, $pastdue3, _("Total Balance"));

	table_header($th);
    start_row();
	label_cell($supplier_record["curr_code"]);
    label_cell($supplier_record["terms"]);
    amount_cell($supplier_record["Balance"] - $supplier_record["Due"]);
    amount_cell($supplier_record["Due"] - $supplier_record["Overdue1"]);
    amount_cell($supplier_record["Overdue1"] - $supplier_record["Overdue2"]);
    amount_cell($supplier_record["Overdue2"] - $supplier_record["Overdue3"]);
    amount_cell($supplier_record["Overdue3"]);
    amount_cell($supplier_record["Balance"]);
    end_row();
    end_table(1);
}
//------------------------------------------------------------------------------------------------
function systype_name($dummy, $type)
{
	global $systypes_array;
	return $systypes_array[$type];
}

function trans_view($trans)
{
	return get_trans_view_str($trans["type"], $trans["trans_no"]);
}

function due_date($row)
{
	return ($row["type"]== ST_SUPPINVOICE) || ($row["type"]== ST_SUPPCREDIT) ? $row["due_date"] : '';
}

function gl_view($row)
{
	return get_gl_view_str($row["type"], $row["trans_no"]);
}

function credit_link($row)
{
	global $page_nested;

	if ($page_nested)
		return '';
	
	if($row['type'] != ST_SUPPPDC){
	return $row['type'] == ST_SUPPINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
		pager_link(_("Credit This"),
			"/purchasing/supplier_credit.php?New=1&invoice_no=".
			$row['trans_no'], ICON_CREDIT)
			: '';
	}		
	else{
			if( $row['current_pdc_status']==0)
			return pager_link(_("Recall PDC") ,
			"/purchasing/supplier_payment.php?PdcNumber=". $row['trans_no'], ICON_DOC);	
	}			
}


function bounce_link($row){
	if($row['type'] == ST_SUPPPDC){
	if($row['current_pdc_status']==0)
			return pager_link(_("PDC Bounce") ,
			"/purchasing/bounce_pdc.php?PdcNumber=". $row['trans_no'], ICON_DOC);
	}else{
		return '';
	}
	
}

function fmt_amount($row)
{
	$value = $row["TotalAmount"];
	return price_format($value);
}

function prt_link($row)
{
  	if ($row['type'] == ST_SUPPAYMENT) { 
		return print_document_link($row['trans_no']."-".$row['type'], _("Print Remittance Format"), true, ST_SUPPAYMENT_REP, ICON_PRINT);
	}elseif($row['type'] == ST_SUPPCREDIT){
		return print_document_link($row['trans_no']."-".$row['type'], _("Print Remittance"), true, ST_SUPPAYMENT, ICON_PRINT);
	}elseif($row['type'] == ST_SUPPPDC){
		return print_document_link($row['trans_no']."-".$row['type'], _("Print Remittance"), true, ST_SUPPPDC, ICON_PRINT);
	}elseif($row['type'] == ST_BANKPAYMENT){
	return print_document_link($row['trans_no']."-".$row['trans_type'], _("Print Remittance Format"), true, ST_BANKPAYMENT, ICON_PRINT);
	}
}

function rep_prt_link($row)
{
  	if ($row['type'] == ST_SUPPAYMENT ){ 
 		return print_document_link($row['trans_no']."-".$row['type'], _("Print Remittance Format"), true, ST_SUPPAYMENT_REP, ICON_PRINT);
}
/*elseif($row['type'] == ST_BANKPAYMENT){
	return print_document_link($row['trans_no']."-".$row['trans_type'], _("Print Remittance Format"), true, ST_BANKPAYMENT, ICON_PRINT);
	}*/
	else{
		return "";
	}
}

function chq_prt_link($row)
{
	$cheq = get_cheque_print_purchase($row['trans_no'], $row['type']);
	if($cheq == "cheque"){
		
		if ($row['type'] == ST_SUPPAYMENT) {
		return print_document_link($row['trans_no']."-".$row['type'], _("Print Remittance Format"), true, ST_SUPPAYMENT_REP_TWO, ICON_PRINT);
		}elseif ($row['type'] == ST_BANKPAYMENT){
		
 		return print_document_link($row['trans_no']."-".$row['type'], _("Print Remittance Format"), true, ST_BANKPAYMENT_REP, ICON_PRINT);
	}else{
			return"";
		}
	}else{
		if($row['type'] == ST_SUPPPDC){
 		return print_document_link($row['trans_no']."-".$row['type'], _("Print Transfer Format"), true, 
		ST_SUPPPDC_REP, ICON_PRINT);
        }else{
		return "-";
        }
	}
}


function check_overdue($row)
{
	return $row['OverDue'] == 1
		&& (abs($row["TotalAmount"]) - $row["Allocated"] != 0);
}

function edit_link($row)
{
	global $page_nested;

	if ($page_nested)
		return '';
	//display_error($row['type']);
	if ($row['type'] == ST_SUPPAYMENT)
	{
		 if (user_check_access('SA_SALES_INV_EDIT')) {
		 	return $row['type'] == ST_SUPPAYMENT && $row['order_'] ? '' : trans_editor_link($row['type'], $row['trans_no'], $row['ref_no']);
		 }
		 if($row['signed_collection_status'] == 0) 	
		 return $row['type'] == ST_SUPPAYMENT && $row['order_'] ? '' : trans_editor_link($row['type'], $row['trans_no'], $row['ref_no']);
	}
	
			
	return trans_editor_link($row['type'], $row['trans_no']);
}

function purch_voucher_link($row)
{
	if($row['type'] == ST_SUPPINVOICE)
	{
		return get_supplier_purch_voucher_view_str($row["type"], $row["trans_no"]);
	}
}

function barcode_print_link($row)
{
	if($row['type'] == ST_SUPPINVOICE)
	{
		return "<a href='bulk_barcode_print.php?trans_no=".$row["trans_no"]."'  target='_blank'>Click Here</a>";
	}
}


//------------------------------------------------------------------------------------------------

start_form();

if (!isset($_POST['supplier_id']))
	$_POST['supplier_id'] = get_global_supplier();

start_table(TABLESTYLE_NOBORDER);
start_row();

purchase_types_list_cells(_("Purchase Type:"), 'purch_type',null, true, true);

if (!$page_nested)
	supplier_list_cells(_("Select a supplier:"), 'supplier_id', null, true, true, false, true);

supp_transactions_list_cell("filterType", null, true);

if ($_POST['filterType'] != '2')
{
	date_cells(_("From:"), 'TransAfterDate', '', null, -user_transaction_days());
	date_cells(_("To:"), 'TransToDate');
}

submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');

end_row();
end_table();
set_global_supplier($_POST['supplier_id']);

//------------------------------------------------------------------------------------------------

div_start('totals_tbl');

if ($_POST['supplier_id'] != "" && $_POST['supplier_id'] != ALL_TEXT)
{
	$supplier_record = get_supplier_details(get_post('supplier_id'), get_post('TransToDate'));
    display_supplier_summary($supplier_record);
}
div_end();

if (get_post('RefreshInquiry') || list_updated('filterType'))
{
	$Ajax->activate('_page_body');
}

//------------------------------------------------------------------------------------------------

$sql = get_sql_for_supplier_inquiry(get_post('filterType'), get_post('TransAfterDate'), get_post('TransToDate'), get_post('supplier_id'), get_post('purch_type'));

$cols = array(
			_("Type") => array('fun'=>'systype_name', 'ord'=>''), 
			_("#") => array('fun'=>'trans_view', 'ord'=>'', 'align'=>'right'), 
			_("Reference"), 
			_("Supplier"),
			_("Supplier's Reference"), 
			_("Date") => array('name'=>'tran_date', 'type'=>'date', 'ord'=>'desc'), 
			_("Due Date") => array('type'=>'date', 'fun'=>'due_date'), 
			_("Currency") => array('align'=>'center'),
			_("Amount") => array('align'=>'right', 'fun'=>'fmt_amount'), 
			_("Balance") => array('align'=>'right', 'type'=>'amount'),
			array('insert'=>true, 'fun'=>'gl_view'),
			array('insert'=>true, 'fun'=>'edit_link'),
			array('insert'=>true, 'fun'=>'credit_link'),
			array('insert'=>true, 'fun'=>'bounce_link'),
			array('insert'=>true, 'fun'=>'prt_link'),
		//	array('insert'=>true, 'fun'=>'rep_prt_link'),
			_("Purchase Voucher") => array('fun'=>'purch_voucher_link', 'ord'=>'', 'align'=>'right','align'=>'center'),
			_("Cheque Print") => array('insert'=>true, 'fun'=>'chq_prt_link','align'=>'center'),
			
			// _("Barcode Print") => array('fun'=>'barcode_print_link', 'ord'=>'', 'align'=>'right','align'=>'center')
			);

if ($_POST['supplier_id'] != ALL_TEXT)
{
	$cols[_("Supplier")] = 'skip';
	$cols[_("Currency")] = 'skip';
}
if ($_POST['filterType'] != '2')
	$cols[_("Balance")] = 'skip';

/*show a table of the transactions returned by the sql */
$table =& new_db_pager('trans_tbl', $sql, $cols);
$table->set_marker('check_overdue', _("Marked items are overdue."));

$table->width = "85%";

display_db_pager($table);

end_form();
end_page();

