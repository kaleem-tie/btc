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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/sales/includes/cart_class.inc");

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 600);



if ($_GET['trans_type'] == ST_SALESQUOTE)
{
	page(_($help_context = "View Sales Quotation"), true, false, "", $js);
	
}
else if ($_GET['trans_type'] == ST_SALESENQ)
{
	page(_($help_context = "View Sales Enquiry"), true, false, "", $js);
	
}		
else
{
	page(_($help_context = "View Sales Order"), true, false, "", $js);
	
}

$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

if ($_GET['trans_type'] == ST_SALESQUOTE)
{
	display_heading(sprintf(_("Sales Quotation #%d"),$_GET['trans_no']));
	
}
else if ($_GET['trans_type'] == ST_SALESENQ)
{
	display_heading(sprintf(_("Sales Enquiry #%d"),$_GET['trans_no']));
	
}		
else
{
	display_heading(sprintf(_("Sales Order #%d"),$_GET['trans_no']));
	
}

//display_heading(sprintf(_("Sales Order #%d"),$_GET['trans_no']));


echo "<br>";

if (isset($_SESSION['View']))
{
	unset ($_SESSION['View']);
}

$_SESSION['View'] = new Cart($_GET['trans_type'], $_GET['trans_no']);

start_table(TABLESTYLE2, "width='95%'", 5);

if ($_GET['trans_type'] != ST_SALESQUOTE && $_GET['trans_type'] != ST_SALESENQ)
{
	echo "<tr valign=top><td>";
	display_heading2(_("Order Information"));
	echo "</td><td>";
	display_heading2(_("Deliveries"));
	echo "</td><td>";
	display_heading2(_("Invoices/Credits"));
	echo "</td></tr>";
}	

echo "<tr valign=top><td>";

start_table(TABLESTYLE, "width='95%'");
label_row(_("Customer Name"), $_SESSION['View']->customer_name, "class='tableheader2'",
	"colspan=3");
	
if ($_GET['trans_type'] == ST_SALESQUOTE)
{
 if( $_SESSION['View']->cash_cust_name !=''){
   start_row();
   label_cells(_("Cash Customer Name"), $_SESSION['View']->cash_cust_name, "class='tableheader2'");
   label_cells(_("Cash Customer Mobile Number"), $_SESSION['View']->cash_cust_mobile, "class='tableheader2'");
   end_row();
 }
}	
	
	
start_row();
label_cells(_("Customer Order Ref."), $_SESSION['View']->cust_ref, "class='tableheader2'");
label_cells(_("Deliver To Branch"), $_SESSION['View']->deliver_to, "class='tableheader2'");
end_row();
start_row();
label_cells(_("Ordered On"), $_SESSION['View']->document_date, "class='tableheader2'");
if ($_GET['trans_type'] == ST_SALESQUOTE)
	label_cells(_("Valid until"), $_SESSION['View']->due_date, "class='tableheader2'");
elseif ($_SESSION['View']->reference == "auto")
	label_cells(_("Due Date"), $_SESSION['View']->due_date, "class='tableheader2'");
else
	label_cells(_("Preferred Delivery Date"), $_SESSION['View']->due_date, "class='tableheader2'");
end_row();
start_row();
label_cells(_("Order Currency"), $_SESSION['View']->customer_currency, "class='tableheader2'");
label_cells(_("Deliver From Location"), $_SESSION['View']->location_name, "class='tableheader2'");
end_row();


if ($_SESSION['View']->payment_terms['days_before_due']<0)
{
start_row();
label_cells(_("Payment Terms"), $_SESSION['View']->payment_terms['terms'], "class='tableheader2'");
label_cells(_("Required Pre-Payment"), price_format($_SESSION['View']->prep_amount), "class='tableheader2'");
end_row();
start_row();
label_cells(_("Non-Invoiced Prepayments"), price_format($_SESSION['View']->alloc), "class='tableheader2'");
label_cells(_("All Payments Allocated"), price_format($_SESSION['View']->sum_paid), "class='tableheader2'");
end_row();
} else
	label_row(_("Payment Terms"), $_SESSION['View']->payment_terms['terms'], "class='tableheader2'", "colspan=3");

if ($_GET['trans_type'] == ST_SALESORDER){
global $delivery_times;	
label_row(_("Preferred Delivery Time"), $delivery_times[$_SESSION['View']->delivery_time], "class='tableheader2'", "colspan=3");
}

label_row(_("Delivery Address"), nl2br($_SESSION['View']->delivery_address),
	"class='tableheader2'", "colspan=3");
label_row(_("Reference"), $_SESSION['View']->reference, "class='tableheader2'", "colspan=3");
label_row(_("Telephone"), $_SESSION['View']->phone, "class='tableheader2'", "colspan=3");
label_row(_("E-mail"), "<a href='mailto:" . $_SESSION['View']->email . "'>" . $_SESSION['View']->email . "</a>",
	"class='tableheader2'", "colspan=3");
	
	
if($_SESSION['View']->sales_person_id!=0){
label_row(_("Sales Person"), get_salesman_name($_SESSION['View']->sales_person_id), "class='tableheader2'", "colspan=3");
}	
	
$preared_user = get_transaction_prepared_by($_GET['trans_type'], $_GET['trans_no']);
label_row(_("Prepared By"), $preared_user, "class='tableheader2'", "colspan=3");	

start_row();
if (get_company_pref('use_dimension')) {
label_row(_("Dimension"), get_user_dimension_name($_SESSION['View']->dimension_id), "class='tableheader2'", "colspan=3");
}
if (get_company_pref('use_dimension') == 2){
	label_row(_("Dimension"), get_user_dimension_name($_SESSION['View']->dimension2_id), "class='tableheader2'", "colspan=3");
}


start_row();	
label_cells(_("LPO No."), $_SESSION['View']->lpo_no, "class='tableheader2'");
label_cells(_("LPO Date"), $_SESSION['View']->lpo_date, "class='tableheader2'");
end_row();	

if ($_GET['trans_type'] == ST_SALESQUOTE){
label_row(_("Delivery Terms"), nl2br($_SESSION['View']->delivery_terms), "class='tableheader2'", "colspan=3");
}
	
label_row(_("Comments / Partial Delivery Details"), nl2br($_SESSION['View']->Comments), "class='tableheader2'", "colspan=3");
end_table();

if ($_GET['trans_type'] != ST_SALESQUOTE && $_GET['trans_type'] != ST_SALESENQ)
{
	echo "</td><td valign='top'>";

	start_table(TABLESTYLE);
	display_heading2(_("Delivery Notes"));


	$th = array(_("#"), _("Ref"), _("Date"), _("Total"));
	table_header($th);

	$dn_numbers = array();
	$delivery_total = 0;

	if ($result = get_sales_child_documents(ST_SALESORDER, $_GET['trans_no'])) {

		$k = 0;
		while ($del_row = db_fetch($result))
		{

			alt_table_row_color($k);
			$dn_numbers[] = $del_row["trans_no"];
			$this_total = $del_row["ov_freight"]+ $del_row["ov_amount"] + $del_row["ov_freight_tax"]  + $del_row["ov_gst"] ;
			$delivery_total += $this_total;

			label_cell(get_customer_trans_view_str($del_row["type"], $del_row["trans_no"]));
			label_cell($del_row["reference"]);
			label_cell(sql2date($del_row["tran_date"]));
			amount_cell($this_total);
			end_row();
		}
	}

	label_row(null, price_format($delivery_total), " ", "colspan=4 align=right");

	end_table();
	echo "</td><td valign='top'>";

	start_table(TABLESTYLE);
	display_heading2(_("Sales Invoices"));

	$th = array(_("#"), _("Ref"), _("Date"), _("Total"));
	table_header($th);
	
	$inv_numbers = array();
	$invoices_total = 0;

	if ($_SESSION['View']->prepaid)
		$result = get_sales_order_invoices($_GET['trans_no']);
	else
		$result = get_sales_child_documents(ST_CUSTDELIVERY, $dn_numbers);

	if ($result) {
		$k = 0;

		while ($inv_row = db_fetch($result))
		{
			alt_table_row_color($k);

			$this_total = $_SESSION['View']->prepaid ? $inv_row["prep_amount"] : 
				$inv_row["ov_freight"] + $inv_row["ov_freight_tax"]  + $inv_row["ov_gst"] + $inv_row["ov_amount"];
			$invoices_total += $this_total;

			$inv_numbers[] = $inv_row["trans_no"];
			label_cell(get_customer_trans_view_str($inv_row["type"], $inv_row["trans_no"]));
			label_cell($inv_row["reference"]);
			label_cell(sql2date($inv_row["tran_date"]));
			amount_cell($this_total);
			end_row();
		}
	}
	label_row(null, price_format($invoices_total), " ", "colspan=4 align=right");

	end_table();

	display_heading2(_("Credit Notes"));

	start_table(TABLESTYLE);
	$th = array(_("#"), _("Ref"), _("Date"), _("Total"));
	table_header($th);

	$credits_total = 0;

	if ($result = get_sales_child_documents(ST_SALESINVOICE, $inv_numbers)) {
		$k = 0;

		while ($credits_row = db_fetch($result))
		{

			alt_table_row_color($k);

			$this_total = $credits_row["ov_freight"] + $credits_row["ov_freight_tax"]  + $credits_row["ov_gst"] + $credits_row["ov_amount"];
			$credits_total += $this_total;

			label_cell(get_customer_trans_view_str($credits_row["type"], $credits_row["trans_no"]));
			label_cell($credits_row["reference"]);
			label_cell(sql2date($credits_row["tran_date"]));
			amount_cell(-$this_total);
			end_row();

		}

	}
	label_row(null, "<font color=red>" . price_format(-$credits_total) . "</font>",
		" ", "colspan=4 align=right");

	end_table();

	echo "</td></tr>";

	end_table();
}
echo "<center>";
if ($_SESSION['View']->so_type == 1)
	display_note(_("This Sales Order is used as a Template."), 0, 0, "class='currentfg'");
display_heading2(_("Line Details"));

start_table(TABLESTYLE, "width='95%'");

if ($_GET['trans_type'] == ST_SALESORDER){
	$th = array( _("S.No."), _("Item Code"), _("Item Description"), _("Quantity"), _("FOC Quantity"), _("Unit"),
	       _("Price"), _("Discount"), _("Discount Amt."), _("Total"), _("Quantity Delivered"));
}
else{	
   $th = array( _("S.No."), _("Item Code"), _("Item Description"), _("Quantity"), _("Unit"),
	     _("Price"), _("Discount"), _("Discount Amt."), _("Total"), _("Quantity Delivered"));
}	
	
	
table_header($th);

$k = 0;  //row colour counter
$total_qty =0;

$sl_no = 1; 
foreach ($_SESSION['View']->line_items as $stock_item) {

	/* $line_total = round2($stock_item->quantity * $stock_item->price * (1 - $stock_item->discount_percent),
	   user_price_dec()); */
	$line_total = round2(($stock_item->quantity * $stock_item->price)-($stock_item->quantity * $stock_item->price * ($stock_item->discount_percent/100)), user_price_dec());

	alt_table_row_color($k);

    label_cell($sl_no);	
	label_cell($stock_item->stock_id);
	label_cell($stock_item->item_description);
	$dec = get_qty_dec($stock_item->stock_id);
	qty_cell($stock_item->quantity, false, $dec);
	if ($_GET['trans_type'] == ST_SALESORDER){
	qty_cell($stock_item->foc_quantity, false, $dec);
	}
	
	if($stock_item->units==1){
              $item_info = get_item_edit_info($stock_item->stock_id);
			  label_cell($item_info["units"], "align=right");
    }
    else if($stock_item->units==2){
			  $sec_unit_info = get_item_sec_unit_info($stock_item->stock_id);
			  label_cell($sec_unit_info["sec_unit_name"], "align=right");
    }	
	
	//label_cell($stock_item->units);
	
	
	amount_cell($stock_item->price);
	amount_cell($stock_item->discount_percent);
	amount_cell($stock_item->disc_amount);
	amount_cell($line_total);

	qty_cell($stock_item->qty_done, false, $dec);
	end_row();
	
	$total_qty += $stock_item->quantity;
	$sl_no++;
}

label_cell("<b>Total</b>","colspan=3 align='right'");
label_cell(number_format2($total_qty,3),"colspan=1 align='right'");

if ($_GET['trans_type'] == ST_SALESORDER){
	$colspan =9;
}
else{
    $colspan =8;
}	
	

if ($_SESSION['View']->freight_cost != 0.0)
	label_row(_("Shipping"), price_format($_SESSION['View']->freight_cost),
		"align=right colspan=$colspan", "nowrap align=right", 1);

$sub_tot = $_SESSION['View']->get_items_total() + $_SESSION['View']->freight_cost;

$display_sub_tot = price_format($sub_tot);

label_row(_("Sub Total"), $display_sub_tot, "align=right colspan=$colspan",
	"nowrap align=right", 1);

$taxes = $_SESSION['View']->get_taxes();

if ($_GET['trans_type'] == ST_SALESORDER){
    $tax_total = display_edit_tax_items($taxes, 9, $_SESSION['View']->tax_included,2);
}
else{
	$tax_total = display_edit_tax_items($taxes, 8, $_SESSION['View']->tax_included,2);
}

$display_total = price_format($sub_tot + $tax_total);

start_row();
label_cells(_("Amount Total"), $display_total, "colspan=$colspan align='right'","align='right'");
label_cell('', "colspan=2");
end_row();

if(get_sales_advances_amount($_GET['trans_no'])!=0 &&  $_GET['trans_type']==30)
{
start_row();
label_cells(_("Advance Paid"), price_format(-get_sales_advances_amount($_GET['trans_no'])), "colspan=$colspan align='right'","align='right'");
label_cell('', "colspan=2");
end_row();
start_row();
label_cells(_("Balance Amount"), price_format($sub_tot + $tax_total-get_sales_advances_amount($_GET['trans_no'])), "colspan=$colspan align='right'","align='right'");
label_cell('', "colspan=2");
end_row();
}

end_table();

display_allocations_to(PT_CUSTOMER, $_SESSION['View']->customer_id, $_GET['trans_type'], $_GET['trans_no'], $sub_tot + $tax_total);

if($_GET['trans_type'] == ST_SALESENQ){
	hyperlink_params($path_to_root . "/purchasing/po_entry_items.php", _("&Convert to Purchase Enquiry"), "NewEnq=Yes&se=$trans_no");
}

end_page(true, false, false, $_GET['trans_type'], $_GET['trans_no']);

