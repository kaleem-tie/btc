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

include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "IMPORT PURCHASE VOUCHER"), true, false, "", $js);

if (isset($_GET["trans_no"]))
{
	$trans_no = $_GET["trans_no"];
} 
elseif (isset($_POST["trans_no"]))
{
	$trans_no = $_POST["trans_no"];
}

$supp_trans = new supp_trans(ST_SUPPINVOICE);

read_supp_invoice($trans_no, ST_SUPPINVOICE, $supp_trans);

$supplier_curr_code = get_supplier_currency($supp_trans->supplier_id);

$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";
if (!is_company_currency($supplier_curr_code)){
display_heading(_("IMPORT PURCHASE VOUCHER") . " # " . $trans_no);
}else 
{
	display_heading(_("LOCAL PURCHASE VOUCHER") . " # " . $trans_no);
}
echo "<br>";

start_table(TABLESTYLE, "width='95%'");
start_row();
if (!is_company_currency($supplier_curr_code)){
label_cells(_("Import Invoice "), $supp_trans->reference, "class='tableheader2'");
label_cells(_("Import Invoice Date "), $supp_trans->tran_date, "class='tableheader2'");
}else 
{
	label_cells(_("Local Invoice "), $supp_trans->reference, "class='tableheader2'");
	label_cells(_("Local Invoice Date "), $supp_trans->tran_date, "class='tableheader2'");
}
label_cells(_("Supplier Name"), $supp_trans->supplier_name, "class='tableheader2'");



end_row();
start_row();
label_cells(_("Supplier Invoice "), $supp_trans->supp_reference, "class='tableheader2'");
label_cells(_("Supplier Invoice Date"), $supp_trans->bill_date, "class='tableheader2'");
label_cells(_("Payment Due Date "), $supp_trans->due_date, "class='tableheader2'");
end_row();
start_row();
if (!is_company_currency($supplier_curr_code)){
	label_cells(_("Currency"), $supplier_curr_code, "class='tableheader2'");
	label_cells(_("FC EX Rate"), $supp_trans->rate, "class='tableheader2'");
}
end_row();
if (!is_company_currency($supplier_curr_code)){
start_row();
label_cells(_("Declaration No."), $supp_trans->declaration_no, "class='tableheader2'");
label_cells(_("CIF Value"), price_format($supp_trans->cif_value), "class='tableheader2'");
label_cells(_("VAT Import Value"),price_format($supp_trans->vat_import_value), "class='tableheader2'");
end_row();

start_row();
$invoice_po_info = get_po_order_no_date_from_purchase_invoice($trans_no);

label_cells(_("Custom Duty"),price_format($supp_trans->custom_duties), "class='tableheader2'");
label_cells(_("Container Number"), $supp_trans->container_number, "class='tableheader2'");
label_cells(_("BL No."), $supp_trans->bl_no, "class='tableheader2'");
end_row();
}


start_row();
label_cells(_("PO Number"),$invoice_po_info['order_no'], "class='tableheader2'");
label_cells(_("PO Date"),sql2date($invoice_po_info['ord_date']), "class='tableheader2'");
end_row();

$purch_inco_terms = get_purchasing_inco_terms($supp_trans->purch_inco_terms);
start_row();
label_cells(_("Inco Terms"),$purch_inco_terms['shipper_name'], "class='tableheader2'");
label_cells(_("Purchase Type"),$purchase_types[$supp_trans->purch_type], "class='tableheader2'");
end_row();


start_row();
$invoice_grn_info = get_grn_no_date_from_purchase_invoice($trans_no);
$preared_user = get_transaction_prepared_by(ST_SUPPINVOICE, $trans_no);
label_cells(_("GRN Number"),$invoice_grn_info['grn_id'], "class='tableheader2'");
label_cells(_("GRN Date"),sql2date($invoice_grn_info['delivery_date']), "class='tableheader2'");
label_cells(_("Prepared By"), $preared_user,"class='tableheader2'");
end_row();
comments_display_row(ST_SUPPINVOICE, $trans_no);

end_table(1);

$total_gl = display_gl_items($supp_trans, 2);
$total_grn = display_grn_items_for_pv($supp_trans, 2,$total_gl);

$display_sub_tot = number_format2($total_grn,user_price_dec());

$display_sub_tot_lc = number_format2((($total_grn*$supp_trans->rate)),user_price_dec());

start_table(TABLESTYLE, "width='95%'");

if (!is_company_currency($supplier_curr_code)){
start_row();
label_cells(_("GROSS INVOICE FC VALUE :"), $display_sub_tot, "align=right", "nowrap align=right width='15%'");
label_cells(_("IMPORT INVOICE LC VALUE :"), $display_sub_tot_lc, "align=right", "nowrap align=right width='15%'");
end_row();
}
else{
start_row();
label_cells(_("GROSS INVOICE VALUE :"), $display_sub_tot, "align=right", "nowrap align=right width='15%'");
end_row();	
	
}	

if (!is_company_currency($supplier_curr_code)){

if($supp_trans->freight_cost!=0)
{	
start_row();
label_cells(_("Freight Charges(FC)"),price_format($supp_trans->freight_cost),"align=right ", "nowrap align=right");
label_cells(_("Freight Charges(LC)"),price_format($supp_trans->freight_cost*$supp_trans->rate),
	"align=right ", "nowrap align=right");
end_row();	
}


if($supp_trans->additional_charges!=0)
{
start_row();	
label_cells(_("Additional Charges(FC)"),price_format($supp_trans->additional_charges),"align=right", "nowrap align=right");
label_cells(_("Additional Charges(LC)"),price_format($supp_trans->additional_charges*$supp_trans->rate),
	"align=right", "nowrap align=right");
end_row();	
}


if($supp_trans->packing_charges!=0)
{	
start_row();
label_cells(_("Packing Charges(FC)"),price_format($supp_trans->packing_charges),"align=right", "nowrap align=right");
label_cells(_("Packing Charges(VC)"),price_format($supp_trans->packing_charges*$supp_trans->rate),
	"align=right ", "nowrap align=right");
end_row();	
}


if($supp_trans->other_charges!=0)
{
start_row();	
label_cells(_("Other Charges(FC)"),price_format($supp_trans->other_charges),"align=right", "nowrap align=right");
label_cells(_("Other Charges(LC)"),price_format($supp_trans->other_charges*$supp_trans->rate),
	"align=right ", "nowrap align=right");
end_row();	
}


$tax_items = get_trans_tax_details(ST_SUPPINVOICE, $trans_no);
display_supp_trans_tax_details($tax_items, 1);


if($supp_trans->ov_roundoff!=0)
{
start_row();	
label_row(_("Round Off(FC)"),price_format($supp_trans->ov_roundoff),"align=right ", "nowrap align=right");
label_row(_("Round Off(LC)"),price_format($supp_trans->ov_roundoff *$supp_trans->rate),"align=right ", "nowrap align=right");
end_row();
}

}else{
	
	if($supp_trans->freight_cost!=0)
{	
start_row();
label_cells(_("Freight Charges"),price_format($supp_trans->freight_cost),"align=right ", "nowrap align=right");
end_row();	
}


if($supp_trans->additional_charges!=0)
{
start_row();	
label_cells(_("Additional Charges"),price_format($supp_trans->additional_charges),"align=right", "nowrap align=right");
end_row();	
}


if($supp_trans->packing_charges!=0)
{	
start_row();
label_cells(_("Packing Charges"),price_format($supp_trans->packing_charges),"align=right", "nowrap align=right");
end_row();	
}


if($supp_trans->other_charges!=0)
{
start_row();	
label_cells(_("Other Charges"),price_format($supp_trans->other_charges),"align=right", "nowrap align=right");
end_row();	
}

$tax_items = get_trans_tax_details(ST_SUPPINVOICE, $trans_no);
display_supp_trans_tax_details($tax_items, 1);

if($supp_trans->ov_roundoff!=0)
{
start_row();	
label_row(_("Round Off"),price_format($supp_trans->ov_roundoff),"align=right ", "nowrap align=right");
end_row();
}
	
}	


$display_total = number_format2($supp_trans->ov_amount + $supp_trans->ov_gst + $supp_trans->freight_cost 
+ $supp_trans->additional_charges + $supp_trans->packing_charges + $supp_trans->other_charges 
+ $supp_trans->freight_tax + $supp_trans->additional_tax + $supp_trans->packing_tax + $supp_trans->other_tax + $supp_trans->ov_roundoff,user_price_dec());

$display_total_lc =($supp_trans->ov_amount + $supp_trans->ov_gst + $supp_trans->freight_cost 
+ $supp_trans->additional_charges + $supp_trans->packing_charges + $supp_trans->other_charges 
+ $supp_trans->freight_tax + $supp_trans->additional_tax + $supp_trans->packing_tax + $supp_trans->other_tax + $supp_trans->ov_roundoff)*$supp_trans->rate;
//$display_total = number_format2($supp_trans->ov_amount + $supp_trans->ov_gst,user_price_dec());

if (!is_company_currency($supplier_curr_code)){
start_row();	
label_cells(_("NET IMPORT INVOICE FC VALUE :").' ('.$supplier_curr_code.')', $display_total, "colspan=1 align=right", "nowrap align=right");
label_cells(_("NET IMPORT INVOICE LC VALUE :"), number_format2($display_total_lc,3), "colspan=1 align=right", "nowrap align=right");
end_row();

start_row();	
label_cells(".", '.', "colspan=1 align=right", "nowrap align=right");
label_cells(_("NET IMPORT INVOICE LC VALUE (Incl Local Expenses) :"), number_format2($display_total_lc+$total_gl,3), "colspan=1 align=right", "nowrap align=right");
end_row();

}else{
label_cells(_("INVOICE VALUE :").' ('.$supplier_curr_code.')', $display_total, "colspan=1 align=right", "nowrap align=right");
}	
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


$inv_trans_type = ST_SUPPINVOICE_EXCEL;
	
display_note(print_document_link($trans_no, _("&Download Excel This Purchase Voucher"), true, $inv_trans_type), 0, 1);

end_page(true, false, false, ST_SUPPINVOICE, $trans_no);

