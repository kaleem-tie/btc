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
$page_security = 'SA_CHEQUE_TRANS_INQ';
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
page(_($help_context = "Cheque Transactions Inquiry"), isset($_GET['supplier_id']), false, "", $js);

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



function update_link($row){
	if($row['type'] == ST_SUPPPDC || $row['type'] == ST_SUPPAYMENT){
			return pager_link(_("Update") ,
			"/purchasing/inquiry/cheque_transaction_update.php?trans_type=".$row["type"]."&trans_no=". $row['trans_no']);
	}else if($row['type'] == ST_BANKPAYMENT){
		return pager_link(_("Update") ,
			"/purchasing/inquiry/bank_cheque_transaction_update.php?trans_type=".$row["type"]."&trans_no=". $row['trans_no']);
	}
	
}

function fmt_amount($row)
{
	$value = $row["TotalAmount"];
	return price_format($value);
}


function chq_prt_link($row)
{
    // Determine if the transaction type is ST_JOURNAL
	
	
	if ($row['type'] == ST_BANKPAYMENT)
	{
		
 		return print_document_link($row['trans_no']."-".$row['trans_type'], _("Print Remittance Format"), true, ST_BANKPAYMENT_REP, ICON_PRINT);
	}
		
	if ($row['type'] == ST_SUPPPDC)
	{
 		return print_document_link($row['trans_no']."-".$row['type'], _("Print Transfer Format"), true, 
		ST_SUPPPDC_REP, ICON_PRINT);
	}
	if ($row['type'] == ST_SUPPAYMENT) {
		return print_document_link($row['trans_no']."-".$row['type'], _("Print Remittance Format"), true, ST_SUPPAYMENT_REP_TWO, ICON_PRINT);
	}
}



function check_overdue($row)
{
	return $row['OverDue'] == 1
		&& (abs($row["TotalAmount"]) - $row["Allocated"] != 0);
}

//------------------------------------------------------------------------------------------------

start_form();

if (!isset($_POST['supplier_id']))
	$_POST['supplier_id'] = get_global_supplier();

start_table(TABLESTYLE_NOBORDER);
start_row();

//purchase_types_list_cells(_("Purchase Type:"), 'purch_type',null, true, true);

if (!$page_nested)
	supplier_list_cells(_("Select a supplier:"), 'supplier_id', null, true, true, false, true);

supp_cheque_transactions_list_cell("filterType", null, true);

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


div_end();

if (get_post('RefreshInquiry') || list_updated('filterType'))
{
	$Ajax->activate('_page_body');
}

//------------------------------------------------------------------------------------------

$sql = get_sql_for_supplier_cheques_inquiry(get_post('filterType'), get_post('TransAfterDate'),
 get_post('TransToDate'), get_post('supplier_id'));

$cols = array(
			_("Type") => array('fun'=>'systype_name', 'ord'=>''), 
			_("#") => array('fun'=>'trans_view', 'ord'=>'', 'align'=>'right'), 
			_("Reference"), 
			_("Supplier"),
			_("Date") => array('name'=>'tran_date', 'type'=>'date', 'ord'=>'desc'), 
			_("Currency") => array('align'=>'center'),
			//_("Amount") => array('align'=>'right', 'fun'=>'fmt_amount'), 
			_("Cheque No."),
			_("Date of Isuue") => array('name'=>'tran_date','type'=>'date'), 
			_("Action") => array('fun'=>'update_link', 'ord'=>'','align'=>'center'),
			_("Cheque Print") => array('insert'=>true, 'fun'=>'chq_prt_link','align'=>'center')
			
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

