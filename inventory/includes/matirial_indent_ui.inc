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
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");


//--------------------------------------------------------------------------------

function add_to_order(&$order, $new_item, $new_item_qty, $standard_cost,$qoh=0)
{
	$kit = get_item_kit($new_item);
	foreach($kit as $item) {
		if ($order->find_cart_item($item['stock_id']))
			display_error(_("For Part :") . $item['stock_id'] . " " . "This item is already on this document. You can change the quantity on the existing line if necessary.");
		else
			$order->add_to_cart (count($order->Indent_line_item), $item['stock_id'], $new_item_qty * $item['quantity'], $standard_cost,null,$qoh);
	}
}

//--------------------------------------------------------------------------------

function display_indent_header(&$order)
{
	global $Refs,$Ajax;

	start_outer_table(TABLESTYLE, "width='70%'");

	table_section(1);
	locations_list_row(_("Requested From Location:"), 'from_loc', null,false, true, false,false);
	
	locations_list_row(_("To Location:"), 'indent_req_loc', null, false, true, $order->fixed_asset,true);
	
if (list_updated('indent_req_loc')) {
	$Ajax->activate('_page_body');
}
			
	

    date_row(_("Date:"), 'AdjDate', '', true);
	//label_row(_("Voucher Date:"),new_doc_date());
	//hidden('AdjDate',new_doc_date());

	ref_row(_("Reference:"), 'ref', '', $Refs->get_next(ST_MATERIAL_INDENT, null, array('date'=>get_post('AdjDate'), 'location'=> get_post('from_loc'))), false, ST_MATERIAL_INDENT, array('date'=> get_post('AdjDate'),'location'=> @$_POST['from_loc']));
	
	table_section(2, "50%");
	
	label_row(_("Indent Request By: "),$_SESSION["wa_current_user"]->username);
	
	material_indent_request_types_list_row(_("Indent Request Type:"),'ind_req_type_id',null,true);
	

	end_outer_table(1); // outer table

	
}

//---------------------------------------------------------------------------------

function display_indent_items($title, &$order)
{
	global $path_to_root;

	display_heading($title);
    div_start('items_table');
	start_table(TABLESTYLE, "width='80%'");
	$th = array(_("Item Code"), _("Item Description"), _("Quantity"),_("QOH"),_("Unit"), '');
	if (!$order->fixed_asset && count($order->Indent_line_item)) $th[] = '';
	table_header($th);
	$k = 0;  //row colour counter

	$low_stock = $order->check_qoh($_POST['indent_req_loc'], $_POST['AdjDate'], true);
	$id = find_submit('Edit');
	foreach ($order->Indent_line_item as $line_no=>$stock_item)
	{

		if ($id != $line_no)
		{
			
				alt_table_row_color($k);

			if ($order->fixed_asset)
				label_cell($stock_item->stock_id);
			else
				view_stock_status_cell($stock_item->stock_id);
    		label_cell($stock_item->item_description);
    		qty_cell($stock_item->quantity, false, get_qty_dec($stock_item->stock_id));
						$dec = get_qty_dec($stock_item->stock_id);

			if (!$order->fixed_asset)
			{
				$qoh = get_qoh_on_date($stock_item->stock_id, get_post('from_loc'));
				qty_cell($qoh, false, $dec);
			}
    		label_cell($stock_item->units);

	     	if (!$order->fixed_asset)
				edit_button_cell("Edit$line_no", _("Edit"),	_('Edit document line'));
			delete_button_cell("Delete$line_no", _("Delete"), _('Remove line from document'));
    		end_row();
		}
		else
			transfer_edit_item_controls($order, $line_no);
	}

	if ($id == -1)
		transfer_edit_item_controls($order);

    end_table();
	//if ($low_stock)
		//display_note(_("Marked items have insufficient quantities in stock as on day of transfer."), 0, 1, "class='stockmankofg'");
	div_end();
}

//---------------------------------------------------------------------------------

function transfer_edit_item_controls(&$order, $line_no=-1)
{
	global $Ajax;
	start_row();

	$id = find_submit('Edit');
	if ($line_no != -1 && $line_no == $id)
	{
		$_POST['stock_id'] = $order->Indent_line_item[$id]->stock_id;
		$_POST['qty'] = qty_format($order->Indent_line_item[$id]->quantity, $order->Indent_line_item[$id]->stock_id, $dec);
		$_POST['units'] = $order->Indent_line_item[$id]->units;

		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['stock_id']);
		label_cell($order->Indent_line_item[$id]->item_description);
	    $Ajax->activate('items_table');
	}
	else
	{
	
	   if ($order->fixed_asset)
			stock_disposable_fa_list_cells(null, 'stock_id', null,  _("Select Item"), true, $order->Indent_line_item);
		else
			stock_costable_items_list_cells(null, 'stock_id', null,  _("Select Item"), true);
		if (list_updated('stock_id')) {
			$Ajax->activate('items_table');
		}
		

		if (list_updated('stock_id')) {
			$Ajax->activate('items_table');
	        $Ajax->activate('units');
			$Ajax->activate('qty');
		}

    	$item_info = get_item_edit_info($_POST['stock_id']);

		$dec = $item_info['decimals'];
   		$_POST['qty'] = number_format2(0, $dec);
		$_POST['units'] = $item_info["units"];
	}

	if ($order->fixed_asset) {
		hidden('qty', 1);
		qty_cell(1, false, 0);
	} else
		small_qty_cells(null, 'qty', $_POST['qty'], null, null, $dec);
		if ($order->fixed_asset) {
		hidden('qty', -1);
		qty_cell(1, false, 0);
	} else {
	$qoh = get_qoh_on_date(get_post('stock_id'), get_post('from_loc'));
		qty_cell($qoh);
	}

	label_cell($_POST['units'], '', 'units');

	if ($id != -1)
	{
		button_cell('UpdateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
		hidden('LineNo', $line_no);
 		set_focus('qty');
	}
	else
		submit_cells('AddItem', _("Add Item"), "colspan=2", _('Add new item to document'), true);

	end_row();
}


//---------------------------------------------------------------------------------

function transfer_options_controls()
{
    echo "<br>";
    start_table();

	textarea_row(_("Memo"), 'memo_', null, 50, 3);

	end_table(1);
}


//---------------------------------------------------------------------------------

