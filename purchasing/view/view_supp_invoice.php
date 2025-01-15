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

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "View Supplier Invoice"), true, false, "", $js);

if (isset($_GET["trans_no"]))
{
	$trans_no = $_GET["trans_no"];
} 
elseif (isset($_POST["trans_no"]))
{
	$trans_no = $_POST["trans_no"];
}
function get_inv_reference($inv_id){
	
	$sql = "SELECT reference FROM ".TB_PREF."supp_trans WHERE trans_no=".db_escape($inv_id)." AND type='20'";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
}

$supp_trans = new supp_trans(ST_SUPPINVOICE);

read_supp_invoice($trans_no, ST_SUPPINVOICE, $supp_trans);

$supplier_curr_code = get_supplier_currency($supp_trans->supplier_id);


$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

display_heading(_("SUPPLIER INVOICE") . " # " . $trans_no);
echo "<br>";

start_table(TABLESTYLE, "width='95%'");
start_row();
label_cells(_("Supplier"), $supp_trans->supplier_name, "class='tableheader2'");
label_cells(_("Reference"), $supp_trans->reference, "class='tableheader2'");
label_cells(_("Booking Date"), $supp_trans->tran_date, "class='tableheader2'");

end_row();
start_row();
label_cells(_("Supplier's Bill No."), $supp_trans->supp_reference, "class='tableheader2'");
label_cells(_("Bill Date"), $supp_trans->bill_date, "class='tableheader2'");
label_cells(_("Due Date"), $supp_trans->due_date, "class='tableheader2'");
if (!is_company_currency($supplier_curr_code))
	label_cells(_("Currency"), $supplier_curr_code, "class='tableheader2'");
end_row();



start_row();
label_cells(_("Declaration No."), $supp_trans->declaration_no, "class='tableheader2'");
label_cells(_("CIF Value"), price_format($supp_trans->cif_value), "class='tableheader2'");
label_cells(_("VAT Import Value"),price_format($supp_trans->vat_import_value), "class='tableheader2'");
end_row();

start_row();
label_cells(_("Custom Duty"),price_format($supp_trans->custom_duties), "class='tableheader2'");
label_cells(_("Container Number"), $supp_trans->container_number, "class='tableheader2'");
label_cells(_("BL No."), $supp_trans->bl_no, "class='tableheader2'");
end_row();

start_row();
if($supp_trans->reverse_charge == '1'){
label_cells(_("Against Reverse Charge"), 'Yes', "class='tableheader2'");
}else{
	label_cells(_("Against Reverse Charge"), 'No', "class='tableheader2'");
}
$purch_inco_terms = get_purchasing_inco_terms($supp_trans->purch_inco_terms);
	label_cells(_("Inco Terms"), $purch_inco_terms['shipper_name'], "class='tableheader2'");
	global $purchase_types;
label_cells(_("Purchase Type"), $purchase_types[$supp_trans->purch_type], "class='tableheader2'");
end_row();
end_row();


$vc_supplier_name = get_supplier_name($supp_trans->rc_supplier_id);
$vc_supp_reference = get_inv_reference($supp_trans->rc_supplier_invoice_id);



start_row();
if($supp_trans->reverse_charge == '1'){
label_cells(_("RC Supplier Name"), $vc_supplier_name, "class='tableheader2'");
label_cells(_("RC Purchase Ref"), $vc_supp_reference, "class='tableheader2'");
end_row();

start_row();
label_cells(_("RC Invoice Date"), sql2date($supp_trans->rc_invoice_date), "class='tableheader2'");
label_cells(_("RC Invoice Amount"), $supp_trans->rc_invoice_amount, "class='tableheader2'");
label_cells(_("RC Bill No"), $supp_trans->rc_bill_no, "class='tableheader2'");
end_row();
}

$preared_user = get_transaction_prepared_by(ST_SUPPINVOICE, $trans_no);
label_row(_("Prepared By"), $preared_user, "class='tableheader2'", "colspan=3");
comments_display_row(ST_SUPPINVOICE, $trans_no);

end_table(1);

 $total_gl = display_gl_items($supp_trans, 2);
$total_grn = display_grn_items($supp_trans, 2);

// $display_sub_tot = number_format2($total_gl+$total_grn,user_price_dec());
$display_sub_tot = number_format2($total_grn,user_price_dec());

start_table(TABLESTYLE, "width='95%'");
label_row(_("Sub Total"), $display_sub_tot, "align=right", "nowrap align=right width='15%'");

if($supp_trans->freight_cost!=0)
{	
label_row(_("Freight Charges"),price_format($supp_trans->freight_cost),
	"align=right ", "nowrap align=right");
}

if($supp_trans->additional_charges!=0)
{	
label_row(_("Additional Charges"),price_format($supp_trans->additional_charges),
	"align=right", "nowrap align=right");
}

if($supp_trans->packing_charges!=0)
{	
label_row(_("Packing Charges"),price_format($supp_trans->packing_charges),
	"align=right ", "nowrap align=right");
}

if($supp_trans->other_charges!=0)
{	
label_row(_("Other Charges"),price_format($supp_trans->other_charges),
	"align=right ", "nowrap align=right");
}

$tax_items = get_trans_tax_details(ST_SUPPINVOICE, $trans_no);
display_supp_trans_tax_details($tax_items, 1);

if($supp_trans->ov_roundoff!=0)
{	
label_row(_("Round Off"),price_format($supp_trans->ov_roundoff),
	"align=right ", "nowrap align=right");
}


$display_total = number_format2($supp_trans->ov_amount + $supp_trans->ov_gst + $supp_trans->freight_cost 
+ $supp_trans->additional_charges + $supp_trans->packing_charges + $supp_trans->other_charges 
+ $supp_trans->freight_tax + $supp_trans->additional_tax + $supp_trans->packing_tax + $supp_trans->other_tax + $supp_trans->ov_roundoff,user_price_dec());

//$display_total = number_format2($supp_trans->ov_amount + $supp_trans->ov_gst,user_price_dec());

label_row(_("TOTAL INVOICE").' ('.$supplier_curr_code.')', $display_total, "colspan=1 align=right", "nowrap align=right");

end_table(1);

$voided = is_voided_display(ST_SUPPINVOICE, $trans_no, _("This invoice has been voided."));

if (!$voided) 
{
	display_allocations_to(PT_SUPPLIER, $supp_trans->supplier_id, ST_SUPPINVOICE, $trans_no, 
		($supp_trans->ov_amount + $supp_trans->ov_gst));
}

end_page(true, false, false, ST_SUPPINVOICE, $trans_no);

