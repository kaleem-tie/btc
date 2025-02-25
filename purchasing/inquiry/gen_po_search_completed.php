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
$path_to_root="../..";
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

if (isset($_GET['type']))
{
  if($_GET['type']==ST_GEN_PURCHORDER)
  $trans_type= ST_GEN_PURCHORDER;
$_SESSION["tran_type"] =$trans_type;
}

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

//page(_($help_context = "Search Purchase Orders"), false, false, "", $js);
if($_GET['type']==ST_GEN_PURCHORDER)
  page(_($help_context = "Search General Purchase Orders"), false, false, "", $js);

//---------------------------------------------------------------------------------------------
function trans_view($trans)
{
	return get_trans_view_str($_SESSION["tran_type"], $trans["order_no"]);
}

function edit_link($row) 
{
	global $page_nested;

	if($_SESSION["tran_type"] == ST_GEN_PURCHORDER){	 
	 return  trans_editor_link($_SESSION["tran_type"], $row["order_no"]);
 }
}


function prt_link($row)
{
	return print_document_link($row['order_no'], _("Print"), true, $_SESSION["tran_type"], ICON_PRINT);
}

function quote_link($row)
{
	
  return pager_link( _("Purchase Quotation"),
	"/purchasing/po_entry_items.php?NewEnqToPurchQuote=" .$row['order_no'], ICON_DOC);
}

if (isset($_GET['order_number']))
{
	$_POST['order_number'] = $_GET['order_number'];
}

function status($row)
{
	if($row['po_auth_req']==0||$row['reference']=='auto')
	{
		return "Approved";
	}
	else if($row['po_auth_req']==1)
	{
		return "Pending";

	}
	else if($row['po_auth_req']==2)
	{
		return "Approved";

	}
	else if($row['po_auth_req']==3)
	{
		return "Rejected";

	}
}

function order_link($row)
{
  return pager_link( _("Purchase Order"),
	"/purchasing/po_entry_items.php?NewQuoteToPurchOrder=" .$row['order_no'], ICON_DOC);
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

	$Ajax->addDisable(true, 'OrdersAfterDate', $disable);
	$Ajax->addDisable(true, 'OrdersToDate', $disable);
	$Ajax->addDisable(true, 'StockLocation', $disable);
	$Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
	$Ajax->addDisable(true, 'SelectStockFromList', $disable);

	if ($disable) {
		$Ajax->addFocus(true, 'order_number');
	} else
		$Ajax->addFocus(true, 'OrdersAfterDate');

	$Ajax->activate('orders_tbl');
}
//---------------------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
ref_cells(_("#:"), 'order_number', '',null, '', true);

date_cells(_("from:"), 'OrdersAfterDate', '', null, -user_transaction_days());
date_cells(_("to:"), 'OrdersToDate');

locations_list_cells(_("into location:"), 'StockLocation', null, true);
submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');
end_row();
end_table();

//---------------------------------------------------------------------------------------------

$sql = get_sql_for_gen_po_search_completed(get_post('OrdersAfterDate'), get_post('OrdersToDate'),
	get_post('StockLocation'), get_post('order_number'));


if($_SESSION["tran_type"] == ST_GEN_PURCHORDER){

$cols = array(
		_("#") => array('fun'=>'trans_view', 'ord'=>'', 'align'=>'right'), 
		_("Reference"), 
		_("Supplier") => array('ord'=>''),
		_("Location"),
		_("Supplier's Reference"), 
		_("Order Date") => array('name'=>'ord_date', 'type'=>'date', 'ord'=>'desc'), 
		_("Order Total") => 'amount',
		array('insert'=>true, 'fun'=>'edit_link'),
		array('insert'=>true, 'fun'=>'prt_link'),
);
}


if (get_post('StockLocation') != ALL_TEXT) {
	$cols[_("Location")] = 'skip';
}

//---------------------------------------------------------------------------------------------------

$table =& new_db_pager('orders_tbl', $sql, $cols);

$table->width = "80%";

display_db_pager($table);

end_form();
end_page();
