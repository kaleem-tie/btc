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
include($path_to_root . "/purchasing/includes/po_class.inc");

include($path_to_root . "/includes/session.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "View GRN"), true, false, "", $js);

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");

if (!isset($_GET['trans_no']))
{
	die ("<BR>" . _("This page must be called with a GRN number to review."));
}

$purchase_order = new purch_order;
read_grn($_GET["trans_no"], $purchase_order);

$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

display_heading(_("GRN") . " #" . $_GET['trans_no']);
echo "<BR>";
display_grn_summary($purchase_order);

display_heading2(_("Line Details"));

start_table(TABLESTYLE, "width='90%'");
$th = array(_("S.No."), _("Item Code"), _("Item Description"),  _("Quantity"),
	_("Unit"), _("Price"), _("Net Amount"),_("Discount(%)"),_("Discount Amount"), _("Line Total"), _("Quantity Invoiced"));

table_header($th);

$total = 0;
$k = 0;  //row colour counter
$overdue_items = false;
$total_qty = 0;
$sl_no=1;
foreach ($purchase_order->line_items as $stock_item)
{

	$net_amount = $stock_item->qty_received * $stock_item->price;
	
	$line_total = ($stock_item->quantity * $stock_item->price)-($stock_item->quantity * $stock_item->price) * (($stock_item->discount_percent/100));
	
	$discount_amount = ($stock_item->price*$stock_item->quantity)*$stock_item->discount_percent/100 ;  

	// if overdue and outstanding quantities, then highlight as so
	if (date1_greater_date2($purchase_order->orig_order_date, $stock_item->req_del_date))
	{
    	start_row("class='overduebg'");
    	$overdue_items = true;
	}
	else
	{
		alt_table_row_color($k);
	}

    label_cell($sl_no);
	label_cell($stock_item->stock_id);
	label_cell($stock_item->item_description);
	$dec = get_qty_dec($stock_item->stock_id);
	qty_cell($stock_item->qty_received, false, $dec);
	label_cell($stock_item->units);
	amount_decimal_cell($stock_item->price);
	amount_cell($net_amount);
	
	amount_decimal_cell(($stock_item->discount_percent));
	amount_cell($discount_amount);
	amount_cell($line_total);
	qty_cell($stock_item->qty_inv, false, $dec);
	end_row();

	$total += $line_total;
	$total_qty += $stock_item->qty_received;
	
	$sl_no++;
}

label_cells(_("Total"), number_format2($total_qty,3),
"colspan=3 align=right", "nowrap align=right");	

$display_sub_tot = number_format2($total,user_price_dec());
label_row(_("Sub Total"), $display_sub_tot,
	"align=right colspan=9", "nowrap align=right", 1);
if($purchase_order->freight_cost!=0)
{	
label_row(_("Freight Charges"),price_format($purchase_order->freight_cost),
	"align=right colspan=9", "nowrap align=right",2);
}	

if($purchase_order->additional_charges!=0)
{	
label_row(_("Additional Charges"),price_format($purchase_order->additional_charges),
	"align=right colspan=9", "nowrap align=right",2);
}	

if($purchase_order->packing_charges!=0)
{	
label_row(_("Packing Charges"),price_format($purchase_order->packing_charges),
	"align=right colspan=9", "nowrap align=right",2);
}	

if($purchase_order->other_charges!=0)
{	
label_row(_("Other Charges"),price_format($purchase_order->other_charges),
	"align=right colspan=9", "nowrap align=right",2);
}		
$taxes = $purchase_order->get_taxes();
$tax_total = display_edit_tax_items($taxes, 9, $purchase_order->tax_included, 1);

$display_total = price_format(($total + $tax_total+ $purchase_order->freight_cost +$purchase_order->additional_charges+$purchase_order->packing_charges +$purchase_order->other_charges));

start_row();
label_cells(_("Amount Total"), $display_total, "colspan=9 align='right'","align='right'");
label_cell('');
end_row();


end_table(1);

if ($overdue_items)
	display_note(_("Marked items were delivered overdue."), 0, 0, "class='overduefg'");

is_voided_display(ST_SUPPRECEIVE, $_GET['trans_no'], _("This delivery has been voided."));

end_page(true, false, false, ST_SUPPRECEIVE, $_GET['trans_no']);

