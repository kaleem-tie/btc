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
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");

include_once($path_to_root . "/sales/includes/sales_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 600);
page(_($help_context = "View Sales Dispatch"), true, false, "", $js);


if (isset($_GET["trans_no"]))
{
	$trans_id = $_GET["trans_no"];
}
elseif (isset($_POST["trans_no"]))
{
	$trans_id = $_POST["trans_no"];
}

// 3 different queries to get the information - what a JOKE !!!!

$myrow = get_customer_trans($trans_id, ST_CUSTDELIVERY);

$branch = get_branch($myrow["branch_code"]);

$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);

$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

display_heading(sprintf(_("DISPATCH NOTE #%d"),$trans_id));
echo "<br>";


start_table(TABLESTYLE2, "width='95%'");
echo "<tr valign=top><td>"; // outer table

/*Now the customer charged to details in a sub table*/
start_table(TABLESTYLE, "width='100%'");
$th = array(_("Charge To"));
table_header($th);

label_row(null, $myrow["DebtorName"] . "<br>" . nl2br($myrow["address"]), "nowrap");

end_table();

/*end of the small table showing charge to account details */

echo "</td><td>"; // outer table

/*end of the main table showing the company name and charge to details */

start_table(TABLESTYLE, "width='100%'");
$th = array(_("Charge Branch"));
table_header($th);

label_row(null, $branch["br_name"] . "<br>" . nl2br($branch["br_address"]), "nowrap");
end_table();

echo "</td><td>"; // outer table

start_table(TABLESTYLE, "width='100%'");
$th = array(_("Delivered To"));
table_header($th);

label_row(null, $sales_order["deliver_to"] . "<br>" . nl2br($sales_order["delivery_address"]),
	"nowrap");
end_table();

echo "</td><td>"; // outer table

start_table(TABLESTYLE, "width='100%'");
start_row();
label_cells(_("Reference"), $myrow["reference"], "class='tableheader2'");
label_cells(_("Currency"), $sales_order["curr_code"], "class='tableheader2'");
label_cells(_("Our Order No"),
	get_customer_trans_view_str(ST_SALESORDER,$sales_order["order_no"]), "class='tableheader2'");
end_row();
start_row();
label_cells(_("Customer Order Ref."), $sales_order["customer_ref"], 
"class='tableheader2'");
label_cells(_("Shipping Company"), $myrow["shipper_name"], "class='tableheader2'");
label_cells(_("Sales Type"), $myrow["sales_type"], "class='tableheader2'");
end_row();
start_row();
label_cells(_("Dispatch Date"), sql2date($myrow["tran_date"]), "class='tableheader2'", "nowrap");
label_cells(_("Due Date"), sql2date($myrow["due_date"]), "class='tableheader2'", 
"nowrap");
end_row();

start_row();

if($myrow["sales_person_id"]!=0){
label_cells(_("Sales Person"), get_salesman_name($myrow["sales_person_id"]), "class='tableheader2'");
}

$preared_user = get_transaction_prepared_by(ST_CUSTDELIVERY, $trans_id);
label_cells(_("Prepared By"), $preared_user, "class='tableheader2'");

end_row();



start_row();
if (get_company_pref('use_dimension')) 
	label_cells(_("Dimension"), get_user_dimension_name($myrow["dimension_id"]), "class='tableheader2'", "colspan=3");
if (get_company_pref('use_dimension') == 2)
	label_cells(_("Dimension"), get_user_dimension_name($myrow["dimension2_id"]), "class='tableheader2'", "colspan=3");
end_row();


start_row();
label_cells(_("LPO No."), $myrow["lpo_no"], "class='tableheader2'", "nowrap");
label_cells(_("LPO Date"), sql2date($myrow["lpo_date"]), "class='tableheader2'", 
"nowrap");
end_row();

comments_display_row(ST_CUSTDELIVERY, $trans_id);
end_table();

echo "</td></tr>";
end_table(1); // outer table


$result = get_customer_trans_details(ST_CUSTDELIVERY, $trans_id);

start_table(TABLESTYLE, "width='95%'");

if (db_num_rows($result) > 0)
{
	$th = array( _("S.No."), _("Item Code"), _("Item Description"), _("Quantity"), _("FOC Quantity"),
		_("Unit"), _("Price"), _("Discount %"), _("Discount Amt."), _("Total"), _("Invoiced"));
	table_header($th);

	$k = 0;	//row colour counter
	$sub_total = 0;
	$total_qty =0;
	$sl_no = 1; 
	while ($myrow2 = db_fetch($result))
	{
		if($myrow2['quantity']==0) continue;
		alt_table_row_color($k);

		$value = round2(((1 - $myrow2["discount_percent"]/100) * $myrow2["unit_price"] * $myrow2["quantity"]),
		   user_price_dec());
		$sub_total += $value;

	    if ($myrow2["discount_percent"] == 0)
	    {
		  	$display_discount = "";
	    }
	    else
	    {
		  	$display_discount = percent_format($myrow2["discount_percent"]) . "%";
	    }
		
		
        label_cell($sl_no);	
		label_cell($myrow2["stock_id"]);
		label_cell($myrow2["StockDescription"]);
        qty_cell($myrow2["quantity"], false, get_qty_dec($myrow2["stock_id"]));
		qty_cell($myrow2["foc_quantity"], false, get_qty_dec($myrow2["stock_id"]));
		if($myrow2["unit"]==1){
        $item_info = get_item_edit_info($myrow2["stock_id"]);
		label_cell($item_info["units"], "align=right");
        }
        else if($myrow2["unit"]==2){
		$sec_unit_info = get_item_sec_unit_info($myrow2["stock_id"]);
	    label_cell($sec_unit_info["sec_unit_name"], "align=right");
        }	
		
        //label_cell($myrow2["units"], "align=right");
		
        amount_cell($myrow2["unit_price"]);
        label_cell($display_discount, "nowrap align=right");
		amount_cell($myrow2["disc_amount"]);
        amount_cell($value);
		qty_cell($myrow2["qty_done"], false, get_qty_dec($myrow2["stock_id"]));
	end_row();
	
	$total_qty += $myrow2["quantity"];
	$sl_no++;
	
	} //end while there are line items to print out
	
	
	label_cell("<b>Total</b>","colspan=3 align='right'");
    label_cell(number_format2($total_qty,3),"colspan=1 align='right'");
	
	$display_sub_tot = price_format($sub_total);
	label_row(_("Sub-total"), $display_sub_tot, "colspan=9 align=right",
		"nowrap align=right width='15%'");

}
else
	display_note(_("There are no line items on this dispatch."), 1, 2);
if ($myrow['ov_freight'] != 0.0)
{
	$display_freight = price_format($myrow["ov_freight"]);
	label_row(_("Shipping"), $display_freight, "colspan=9 align=right", "nowrap align=right");
}

$tax_items = get_trans_tax_details(ST_CUSTDELIVERY, $trans_id);
display_customer_trans_tax_details($tax_items, 9);

$display_total = price_format($myrow["ov_freight"]+$myrow["ov_amount"]+$myrow["ov_freight_tax"]+$myrow["ov_gst"]);

label_row(_("TOTAL VALUE"), $display_total, "colspan=9 align=right",
	"nowrap align=right");
end_table(1);

is_voided_display(ST_CUSTDELIVERY, $trans_id, _("This dispatch has been voided."));

end_page(true, false, false, ST_CUSTDELIVERY, $trans_id);

