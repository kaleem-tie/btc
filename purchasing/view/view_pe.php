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
include($path_to_root . "/purchasing/includes/purchasing_ui.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "View Purchase Enquiry"), true, false, "", $js);


if (!isset($_GET['trans_no']))
{
	die ("<br>" . _("This page must be called with a purchase enquiry number to review."));
}

$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

display_heading(_("Request For Enquiry") . " #" . $_GET['trans_no']);



$purchase_order = new purch_order;

read_po($_GET['trans_no'], $purchase_order,false,ST_PURCHENQ);
echo "<br>";
display_po_summary($purchase_order, true,false,ST_PURCHENQ);

start_table(TABLESTYLE, "width='90%'", 6);
echo "<tr><td valign=top>"; // outer table

display_heading2(_("Line Details"));

start_table(TABLESTYLE, "width='100%'");

$th = array(_("S.No."), _("Item Code"), _("Item Description"), _("Quantity"), _("Unit"));
table_header($th);
$total = $k = 0;
$overdue_items = false;
$total_qty =0;
$sl_no=1;
foreach ($purchase_order->line_items as $stock_item)
{

	$net_amount = $stock_item->quantity * $stock_item->price;
	
	$line_total = round2(($stock_item->quantity * $stock_item->price)-($stock_item->quantity * $stock_item->price * ($stock_item->discount_percent/100)),
	   user_price_dec());
	   
	$discount_amount = ($stock_item->price*$stock_item->quantity)*$stock_item->discount_percent/100 ;  

	// if overdue and outstanding quantities, then highlight as so
	if (($stock_item->quantity - $stock_item->qty_received > 0)	&&
		date1_greater_date2(Today(), $stock_item->req_del_date))
	{
    	//start_row("class='overduebg'");
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
	qty_cell($stock_item->quantity, false, $dec);
	label_cell($stock_item->units);
	
    end_row();
   $total += $line_total;
   
   $total_qty += $stock_item->quantity;
   $sl_no++;
}

label_cell("<b>Total</b>","colspan=3 align='right'");
label_cell(number_format2($total_qty,3),"colspan=1 align='right'");

end_table();

if ($overdue_items)
	display_note(_("Marked items are overdue."), 0, 0, "class='overduefg'");



//----------------------------------------------------------------------------------------------------

end_page(true, false, false, ST_PURCHENQ, $_GET['trans_no']);

