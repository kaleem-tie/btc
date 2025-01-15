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
include($path_to_root . "/purchasing/includes/po_trans_class.inc");

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

//$purchase_order = new purch_order;

//read_grn_multiple_po($_GET["trans_no"]);

$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

display_heading(_("GRN") . " #" . $_GET['trans_no']);
echo "<BR>";


$grn = get_multiple_po_receive_view($_GET['trans_no']);

 start_table(TABLESTYLE2, "width='90%'");

    start_row();
	label_cells(_("Supplier"), $grn['supplier_name'], "class='tableheader2'");
	label_cells(_("Delivery Date"), sql2date($grn["delivery_date"]), "class='tableheader2'"); 
    label_cells(_("Reference"), $grn["reference"], "class='tableheader2'");
	end_row();

   	start_row();
   	label_cells(_("Credit Period"), $grn["credit_period"], "class='tableheader2'");
   	label_cells(_("LPO No."), $grn["lpo_no"], "class='tableheader2'");
	label_cells(_("LPO Date"), sql2date($grn["lpo_date"]), "class='tableheader2'");
    end_row();
  
    start_row();
	label_cells(_("Deliver Into Location"), get_location_name($grn["loc_code"]), "class='tableheader2'");
   	label_cells(_("Consignment Received Date"), sql2date($grn["consgn_recv_date"]), "class='tableheader2'");
   	label_cells(_("Cleared Date"),  sql2date($grn["cleared_date"]), "class='tableheader2'");
	
$preared_user = get_transaction_prepared_by(ST_SUPPRECEIVE, $_GET['trans_no']);
label_row(_("Prepared By"), $preared_user, "class='tableheader2'", "colspan=3");
end_table(1);
	

$grn_items = get_multiple_po_receive_items_view($_GET['trans_no']);

display_heading2(_("Line Details"));

start_table(TABLESTYLE, "width='90%'");
$th = array(_("SAP No"),_("Our Order No"),_("Item Code"), _("Item Description"), _("Quantity"),
	_("Unit"), _("Price"),_("Net Amount"),_("Discount(%)"),_("Discount Amount"), _("Line Total"), _("Quantity Invoiced"));

table_header($th);

$total = 0;
$k = 0;  //row colour counter
$overdue_items = false;

$tax_group_array = get_tax_group_items_as_array($grn["tax_group_id"]);

$tax_ids = get_gst_taxes_ids($grn["tax_group_id"]);
	//display_error($_SESSION['View']->tax_group_id);
	while($tax_id_array =db_fetch($tax_ids))
	{
		
		$tax_id[]['tax_type_id']=$tax_id_array['tax_type_id'];
		//display_error($tax_id_array['tax_type_id']); die;
	}
	$total_gst=0;
    $gitems = array();
    $prices = array();

while ($items = db_fetch($grn_items))
{
	
	$gitems[] = $items['item_code'];
	
	$net_amount = $items['qty_recd'] * $items['unit_price'];
	
	$line_total = ($items['qty_recd']* $items['unit_price'])-($items['qty_recd']* $items['unit_price']) * (($items['discount_percent']/100));
	
	$discount_amount = ($items['qty_recd']* $items['unit_price'])*$items['discount_percent']/100 ; 
	label_cell($items['sap_no']);
	label_cell($items['our_ord_no']);
	label_cell($items['item_code']);
	label_cell($items['description']);
	$dec = get_qty_dec($items['item_code']);
	qty_cell($items['qty_recd'], false, $dec);
	label_cell($items['units']);
	amount_decimal_cell($items['unit_price']);
	amount_cell($net_amount);
	amount_decimal_cell($items['discount_percent']);
	amount_cell($discount_amount);
	amount_cell($line_total);
	qty_cell($items['quantity_inv'], false, $dec);
	end_row();

	$total += $line_total;
	
	$prices[] = $line_total;
	
	
	
}	
	


$display_sub_tot = number_format2($total,user_price_dec());
label_row(_("Sub Total"), $display_sub_tot,
	"align=right colspan=8", "nowrap align=right", 1);
	
if($grn["freight_cost"]!=0)
{	
label_row(_("Freight Charges"),price_format($grn["freight_cost"]),
	"align=right colspan=8", "nowrap align=right",2);
}		

if($grn["additional_charges"]!=0)
{	
label_row(_("Wire Charges"),price_format($grn["additional_charges"]),
	"align=right colspan=8", "nowrap align=right",2);
}	

if($grn["packing_charges"]!=0)
{	
label_row(_("Packing Charges"),price_format($grn["packing_charges"]),
	"align=right colspan=8", "nowrap align=right",2);
}	

if($grn["other_charges"]!=0)
{	
label_row(_("Other Charges"),price_format($grn["other_charges"]),
	"align=right colspan=8", "nowrap align=right",2);
	
	
}	


$ret_tax_array = get_tax_for_items($gitems, $prices, $grn["freight_cost"], $grn["tax_group_id"], $grn["tax_included"], $tax_group_array, '', $grn["additional_charges"], $grn["packing_charges"], $grn["other_charges"]);
	
//$taxes = $purchase_order->get_taxes($grn["freight_cost"]);
$tax_total = display_edit_tax_items($ret_tax_array, 8, $grn["tax_included"], 1);

$display_total = price_format(($total + $tax_total + $grn["freight_cost"] + $grn["additional_charges"] + $grn["packing_charges"] + $grn["other_charges"]));

start_row();
label_cells(_("Amount Total"), $display_total, "colspan=8 align='right'","align='right'");
label_cell('');
end_row();


end_table(1);

if ($overdue_items)
	display_note(_("Marked items were delivered overdue."), 0, 0, "class='overduefg'");

is_voided_display(ST_SUPPRECEIVE, $_GET['trans_no'], _("This delivery has been voided."));

end_page(true, false, false, ST_SUPPRECEIVE, $_GET['trans_no']);

