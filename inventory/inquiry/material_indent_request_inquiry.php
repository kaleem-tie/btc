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
$page_security = 'SA_MATERIAL_INDENT_INQUIRY';
$path_to_root="../..";
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Material Indent Request Inquiry"), false, false, "", $js);

//---------------------------------------------------------------------------------------------
function trans_view($trans)
{
	
	return get_trans_view_str(ST_MATERIAL_INDENT, $trans["indent_num"]);
}

function edit_link($row) 
{
	global $page_nested;

	return trans_editor_link(ST_MATERIAL_INDENT, $row["indent_num"]);
}

function receive_link($row) 
{
	global $page_nested;
	
	return $page_nested || !$row['OverDue'] ? '' :
		pager_link( _("Receive"),
			"/purchasing/po_receive_items.php?PONumber=" . $row["order_no"], ICON_RECEIVE);
}

function prt_link($row)
{
	return print_document_link($row['order_no'], _("Print"), true, ST_PURCHORDER, ICON_PRINT);
}
function status_view($row)
{
	$trans=get_indent_status($row["indent_num"]);
	if($trans["total_requested"]==$trans["total_revived"])
	{
		return "Completed";
	}
	else
	{
		return "Pending";
	}

}

if (isset($_GET['order_number']))
{
	$_POST['order_number'] = $_GET['order_number'];
}

//-----------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('SearchOrders')) 
{
	$Ajax->activate('orders_tbl');
} elseif (get_post('_order_number_changed')) 
{
	$disable = get_post('order_number') !== '';


	$Ajax->activate('orders_tbl');
}
//---------------------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
ref_cells(_("#:"), 'indent_number', '',null, '', true);

date_cells(_("from:"), 'IndentAfterDate', '', null, -user_transaction_days());
date_cells(_("to:"), 'IndentToDate');
end_row();
start_row();
locations_list_cells(_("Indent From location:"), 'IndentFromLocation', null, true);
locations_list_cells(_("Indent To location:"), 'IndentToLocation', null, true,false,false,true);

end_row();

start_row();

stock_items_list_cells(_("for item:"), 'SelectStockFromList', null, true);



submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');
end_row();
end_table(1);

//---------------------------------------------------------------------------------------------


$sql = get_sql_for_material_indent_request_inquiry(get_post('IndentAfterDate'), get_post('IndentToDate'),get_post('indent_number'),get_post('IndentFromLocation'),get_post('IndentToLocation'),get_post('SelectStockFromList'));

$cols = array(
		_("#") => array('fun'=>'trans_view', 'ord'=>'', 'align'=>'right'), 
		_("Reference"), 
		_("Date") => array('name'=>'ord_date', 'type'=>'date', 'ord'=>'desc'),
		_("Item Code"),
		_("Item Description"),
		_("Indent Request From Location"),
		_("Indent Request To Location"),
		_("Quantity"),
		_("Requested By"),
		_("Status") => array('fun'=>'status_view')
		
);

if (get_post('StockLocation') != ALL_TEXT) {
	$cols[_("Location")] = 'skip';
}

//---------------------------------------------------------------------------------------------------

$table =& new_db_pager('orders_tbl', $sql, $cols);

$table->width = "80%";

display_db_pager($table);

end_form();
end_page();
