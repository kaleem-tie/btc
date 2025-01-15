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
$page_security = 'SA_SALES_DELIVERY_CALENDAR_ORDERWISE';
$path_to_root = "../..";
include_once($path_to_root . "/sales/includes/cart_class.inc");

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 600);

	
	$start_date = strtotime(date2sql($_GET['delivery_date']));
    $end_date = strtotime(date('Y-m-d'));


	$diff=($start_date - $end_date)/60/60/24;
	
echo "<br>";
    if($_GET['type']=='do')
	display_heading("<font color=black>Delivered Items Details</font>");
    else if($_GET['type']=='so' && $diff<1)
	display_heading("<font color=black>Delayed Items Details</font>");
    else if($_GET['type']=='so' && $diff>=1)
	display_heading("<font color=black>Delivery Items Details</font>");
    else if($_GET['type']=='sd' && $diff<1)
	display_heading("<font color=black>Delayed Items Details</font>");
    else if($_GET['type']=='sd' && $diff>=1)
	display_heading("<font color=black>Delivery Items Details</font>");
    else if($_GET['type']=='co')
	display_heading("<font color=black>Cancelled Order Items Details</font>");
		

display_heading(sprintf(_("Sales Order #%d"),$_GET['order_no']));
echo "<br>";

if (isset($_SESSION['View']))
{
	unset ($_SESSION['View']);
}

if($_GET['type']!='co')
{
$_SESSION['View'] = new Cart(30, $_GET['order_no']);
}
else
{
		
	$_SESSION['View'] = new Cart(30);
	
	read_cancel_sales_order($_GET['order_no'], $_SESSION['View'], 30);
}

start_table(TABLESTYLE2, "width='95%'", 5);

	echo "<tr valign=top><td>";
	display_heading2(_("Order Information"));
	echo "</td></tr>";


echo "<tr valign=top><td>";

start_table(TABLESTYLE, "width='95%'");
label_row(_("Customer Name"), $_SESSION['View']->customer_name, "class='tableheader2'",
	"colspan=3");
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


global $delivery_times;	
label_row(_("Preferred Delivery Time"), $delivery_times[$_SESSION['View']->delivery_time], "class='tableheader2'", "colspan=3");

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
label_row(_("E-mail"), "<a href='mailto:" . $_SESSION['View']->email . "'>" . $_SESSION['View']->email . "</a>",
	"class='tableheader2'", "colspan=3");
	
$preared_user = get_transaction_prepared_by($_GET['trans_type'], $_GET['trans_no']);
label_row(_("Prepared By"), $preared_user, "class='tableheader2'", "colspan=3");	

start_row();
if (get_company_pref('use_dimension')) {
label_row(_("Dimension"), get_user_dimension_name($_SESSION['View']->dimension_id), "class='tableheader2'", "colspan=3");
}
if (get_company_pref('use_dimension') == 2){
	label_row(_("Dimension"), get_user_dimension_name($_SESSION['View']->dimension2_id), "class='tableheader2'", "colspan=3");
}
	
label_row(_("Comments"), nl2br($_SESSION['View']->Comments), "class='tableheader2'", "colspan=3");
end_table();

echo "<center>";
if ($_SESSION['View']->so_type == 1)
	display_note(_("This Sales Order is used as a Template."), 0, 0, "class='currentfg'");
display_heading2(_("Line Details"));

start_table(TABLESTYLE, "width='100%'");


$k = 0;  //row colour counter


    if($_GET['type']=='do')
	{
	  $items_details=read_sales_delivery_items($_GET['order_no'],$_GET['delivery_date']);
	  $th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"));
		table_header($th);
	}
    else if($_GET['type']=='so')
	{
	  $items_details=read_sales_order_items($_GET['order_no'],$_GET['delivery_date']);
	  $th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"),
	   _("Quantity Delivered"));
		table_header($th);
	}
	 else if($_GET['type']=='co')
	{
	  $items_details=read_cancel_sales_order_items($_GET['order_no'],$_GET['delivery_date']);
	  $th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"),
	   _("Quantity Delivered"));
		table_header($th);
	}
	else if($_GET['type']=='sd')
	{
	  $items_details=read_sales_rescheduled_items($_GET['order_no'],$_GET['delivery_date']);
	  $th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"),
	   _("Quantity Delivered"),_("Planned Delivery Date"),_("Planned Delivery Time"));
		table_header($th);
	}


while($item_details=db_fetch($items_details)) {

	if($item_details['quantity'])
	{
	alt_table_row_color($k);

	label_cell($item_details['stk_code']);
	label_cell($item_details['description']);
	$dec = get_qty_dec($item_details['stock_id']);
	qty_cell($item_details['quantity'], false, $dec);
	label_cell($item_details['units']);
	if($_GET['type']!='do')
	qty_cell($item_details['qty_sent'], false, $dec);
  
    if($_GET['type']=='sd'){
	label_cell(sql2date($item_details['planned_date']),"align=center");
	label_cell($delivery_times[$item_details['planned_delivery_time']],"align=center");
	}	

	end_row();
	}
	
	}

end_table();

br(); br(); br(); br();

