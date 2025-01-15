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
page(_($help_context = "View Inventory GRN"), true, false, "", $js);

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");

if (!isset($_GET['trans_no']))
{
	die ("<BR>" . _("This page must be called with a GRN number to review."));
}

$purchase_order = new purch_order;
read_inv_grn($_GET["trans_no"], $purchase_order);

$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

display_heading(_("GRN") . " #" . $_GET['trans_no']);
echo "<BR>";
display_grn_summary($purchase_order);

display_heading2(_("Line Details"));

start_table(TABLESTYLE, "width='90%'");
$th = array(_("S.No."), _("Item Code"), _("Item Description"),  _("Quantity"),
	_("Unit"));

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
	end_row();
	$total_qty += $stock_item->qty_received;
	$sl_no++;
}

label_cells(_("Total"), number_format2($total_qty,3),
"colspan=3 align=right", "nowrap align=right");	


end_table(1);
br();
br();
?>

 <table  align="center"    width="90%" cellpadding="" cellspacing="0">
<tr>
<td style="text-align:left"><b>Prepared By</b></td>
  
  <td style="text-align:center"><b>Approved By</b></td>
  

 </tr>
  </table>

<?php
if ($overdue_items)
	display_note(_("Marked items were delivered overdue."), 0, 0, "class='overduefg'");

is_voided_display(ST_INVSUPPRECEIVE, $_GET['trans_no'], _("This delivery has been voided."));

end_page(true, false, false, ST_INVSUPPRECEIVE, $_GET['trans_no']);

