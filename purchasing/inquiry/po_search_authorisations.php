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
$page_security = 'SA_PURCHASEORDER_AUTH';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Search Outstanding Purchase Order Authorizations"), false, false, "", $js);

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

date_cells(_("From:"), 'OrdersAfterDate', '', null, -user_transaction_days()-7300);
date_cells(_("To:"), 'OrdersToDate');

locations_list_cells(_("Location:"), 'StockLocation', null, true);
end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

stock_items_list_cells(_("Item:"), 'SelectStockFromList', null, true);

supplier_list_cells(_("Select a supplier: "), 'supplier_id', null, true, true);

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');
end_row();
end_table(1);
//---------------------------------------------------------------------------------------------
function trans_view($trans)
{
	return user_check_access('SA_PURCHASEORDER_AUTH_VIEW') ? get_trans_view_str(ST_PURCHORDER, $trans["order_no"]) :  $trans["order_no"];
}

function edit_link($row) 
{
	return trans_editor_link(ST_PURCHORDER, $row["order_no"]);
}

function prt_link($row)
{
	return  user_check_access('SA_PURCHASEORDER_AUTH_PRINT') ? print_document_link($row['order_no'], _("Print"), true, ST_PURCHORDER, ICON_PRINT) : '';
}

function authorise_link($row) 
{
  return user_check_access('SA_PURCHASEORDER_AUTH_ACCEPT') ? pager_link( _("Accept"),	"/purchasing/inquiry/po_authorise_items.php?PONumber=" . $row["order_no"]) : '';
}

function reject_link($row) 
{
	
  return user_check_access('SA_PURCHASEORDER_AUTH_REJECT') ? pager_link( _("Reject"), "/purchasing/inquiry/po_reject_items.php?PONumber=" . $row["order_no"]) : '';
}

function check_overdue($row)
{
	return $row['OverDue']==1;
}

function auth_checkbox($row)
{
	
	$name = "Sel_" .$row['order_no'];
	return $row['Done'] ? '' :
		"<input type='checkbox' name='$name' value='1' >"
// add also trans_no => branch code for checking after 'Batch' submit
	 ."<input name='Sel_[".$row['order_no']."]' type='hidden' value='"
	 .$row['branch_code']."'>".
	 "<input name='Type_[".$row['order_no']."]' type='hidden' value='"
	 .$row['type']."'> \n";
	
}

if (isset($_POST['AuthPO']))
{
	
	// checking batch integrity
	 $del_count = 0;
    foreach($_POST['Sel_'] as $delivery => $branch) {
	  	$checkbox = 'Sel_'.$delivery;
	  	if (check_value($checkbox))	{
	    	if (!$del_count) {
				$del_branch = $branch;
	    	}
	    	else {
				if ($del_branch != $branch)	{
		    		$del_count=0;
		    		break;
				}
	    	}	
	    	$selected[] = $delivery;
			$_POST['date_'] = new_doc_date();
			// display_error($_POST['date_']);die;
			// here 2 for accept
			update_purchase_order_authorise_items($delivery,2,$_POST['date_']);
	    	$del_count++;
	  	}
    }
		
    // display_error(json_encode($selected));die;
		//$_SESSION['DeliveryBatch'] = $selected;
		// meta_forward($path_to_root . '/sales/customer_invoice.php','BatchInvoice=Yes&trans_type='.$trans_type);
    $path="../inquiry/po_search_authorisations.php?";
		meta_forward($path);
}

//---------------------------------------------------------------------------------------------

//figure out the sql required from the inputs available
$sql = get_sql_for_po_search_authorisations(get_post('OrdersAfterDate'), get_post('OrdersToDate'), get_post('supplier_id'), get_post('StockLocation'),
	$_POST['order_number'], get_post('SelectStockFromList'),18);

//$result = db_query($sql,"No orders were returned");

/*show a table of the orders returned by the sql */
$cols = array(
		_("#") => array('fun'=>'trans_view', 'ord'=>''), 
		_("Reference"), 
		_("Supplier") => array('ord'=>''),
		_("Location"),
		_("Supplier's Reference"), 
		_("Order Date") => array('name'=>'ord_date', 'type'=>'date', 'ord'=>'desc'),
		_("Currency") => array('align'=>'center'), 
		_("Order Total") => 'amount',
		array('insert'=>true, 'fun'=>'authorise_link'),
		array('insert'=>true, 'fun'=>'reject_link'),
		array('insert'=>true, 'fun'=>'prt_link'),
		submit('AuthPO',_("Authorize"), false, _("Multiple Authorize"))
		=> array('insert'=>true, 'fun'=>'auth_checkbox', 'align'=>'center')
		
);

if (get_post('StockLocation') != ALL_TEXT) {
	$cols[_("Location")] = 'skip';
}

$table =& new_db_pager('orders_tbl', $sql, $cols);
$table->set_marker('check_overdue', _("Marked orders have overdue items."));

$table->width = "80%";

display_db_pager($table);

end_form();
end_page();
