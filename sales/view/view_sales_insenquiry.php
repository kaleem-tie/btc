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


$company = get_company_prefs();

if ($_GET['trans_type'] == ST_INSSALESENQ)
{
	page(_($help_context = "View Sales Enquiry"), true, false, "", $js);
}

display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

display_heading(sprintf(_("Sales Enquiry #%d"),$_GET['trans_no']));
echo "<br>";



if (isset($_SESSION['View']))
{
	unset ($_SESSION['View']);
}

$_SESSION['View'] = new Cart($_GET['trans_type'], $_GET['trans_no']);

global $customer_order_type;

$trans_no=$_GET['trans_no'];

start_table(TABLESTYLE2, "width='95%'", 5);


echo "<tr valign=top><td>";

start_table(TABLESTYLE, "width='95%'");
label_row(_("Customer Name"), $_SESSION['View']->customer_name, "class='tableheader2'",
	"colspan=3");
// label_row(_("Type"), $customer_order_type[$_SESSION['View']->customer_order_type], "class='tableheader2'","colspan=3");
start_row();
label_cells(_("Customer Ref."), $_SESSION['View']->cust_ref, "class='tableheader2'");
label_cells(_("Deliver To Branch"), $_SESSION['View']->deliver_to, "class='tableheader2'");
end_row();

start_row();
if($_GET['trans_type'] == ST_INSSALESENQ){
label_cells(_("Enquired On"), $_SESSION['View']->document_date, "class='tableheader2'");
}else{
label_cells(_("Quoted On"), $_SESSION['View']->document_date, "class='tableheader2'");
}
if ($_GET['trans_type'] == ST_INSSALESQUOTE && $_GET['trans_type'] == ST_INSSALESENQ)
	label_cells(_("Valid until"), $_SESSION['View']->due_date, "class='tableheader2'");
else
	label_cells(_("Requested Delivery"), $_SESSION['View']->due_date, "class='tableheader2'");
end_row();
start_row();
if($_GET['trans_type'] == ST_INSSALESENQ){
label_cells(_("Enquiry Currency"), $_SESSION['View']->customer_currency, "class='tableheader2'");
}else{
label_cells(_("Quotation Currency"), $_SESSION['View']->customer_currency, "class='tableheader2'");
}
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

label_row(_("Delivery Address"), nl2br($_SESSION['View']->delivery_address),
	"class='tableheader2'", "colspan=3");
label_row(_("Reference"), $_SESSION['View']->reference, "class='tableheader2'", "colspan=3");
label_row(_("Telephone"), $_SESSION['View']->phone, "class='tableheader2'", "colspan=3");
label_row(_("E-mail"), "<a href='mailto:" . $_SESSION['View']->email . "'>" . $_SESSION['View']->email . "</a>","class='tableheader2'", "colspan=3");

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
end_row();	

start_row();	
label_cells(_("LPO No."), $_SESSION['View']->lpo_no, "class='tableheader2'");
label_cells(_("LPO Date"), $_SESSION['View']->lpo_date, "class='tableheader2'");
end_row();	
	
label_row(_("Comments"), nl2br($_SESSION['View']->Comments), "class='tableheader2'", "colspan=3");
end_table();

echo "<center>";
if ($_SESSION['View']->so_type == 1)
	display_note(_("This Sales Order is used as a Template."), 0, 0, "class='currentfg'");
display_heading2(_("Line Details"));

start_table(TABLESTYLE, "width='95%'");
$th = array( _("S.No."), _("Item Code"),_("Description"),_("Qty"),_("Unit"));
table_header($th);

$k = 0;  //row colour counter
$line_items = get_sales_insquote_details($_GET['trans_no'],$_GET['trans_type']);

$total_qty =0;
$sl_no = 1; 
while($stock_item = db_fetch($line_items)) {

	

	alt_table_row_color($k);
    label_cell($sl_no);	
	label_cell($stock_item['item_number']);
	label_cell($stock_item['description']);
	qty_cell($stock_item['quantity']);
	label_cell($stock_item['unit']);
	end_row();
	
	$total_qty += $stock_item["quantity"];
	$sl_no++;
}

label_cell("<b>Total</b>","colspan=3 align='right'");
label_cell(number_format2($total_qty,3),"colspan=1 align='right'");

end_table();

display_allocations_to(PT_CUSTOMER, $_SESSION['View']->customer_id, $_GET['trans_type'], $_GET['trans_no'], $sub_tot + $tax_total);


if($_GET['trans_type']==37)
hyperlink_params($path_to_root . "/purchasing/po_entry_items.php", _("&Convert to Purchase Enquiry"), "NewEnquiry=Yes&se=$trans_no");


end_page(true, false, false, $_GET['trans_type'], $_GET['trans_no']);

