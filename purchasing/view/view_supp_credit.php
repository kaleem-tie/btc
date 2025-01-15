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
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "View Supplier Credit Note"), true, false, "", $js);

if (isset($_GET["trans_no"]))
{
	$trans_no = $_GET["trans_no"];
}
elseif (isset($_POST["trans_no"]))
{
	$trans_no = $_POST["trans_no"];
}

$supp_trans = new supp_trans(ST_SUPPCREDIT);

read_supp_invoice($trans_no, ST_SUPPCREDIT, $supp_trans);

$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

display_heading("<font color=red>" . _("SUPPLIER CREDIT NOTE") . " # " . $trans_no . "</font>");
echo "<br>";
start_table(TABLESTYLE, "width='95%'");
start_row();
label_cells(_("Supplier"), $supp_trans->supplier_name, "class='tableheader2'");
label_cells(_("Reference"), $supp_trans->reference, "class='tableheader2'");
label_cells(_("Supplier's Reference"), $supp_trans->supp_reference, "class='tableheader2'");
end_row();
start_row();
label_cells(_("Credit Note Date"), $supp_trans->tran_date, "class='tableheader2'");
label_cells(_("Due Date"), $supp_trans->due_date, "class='tableheader2'");
label_cells(_("Currency"), get_supplier_currency($supp_trans->supplier_id), "class='tableheader2'");
end_row();

start_row();
label_cells(_("Bill Date"), $supp_trans->bill_date, "class='tableheader2'");
end_row();

$preared_user = get_transaction_prepared_by(ST_SUPPCREDIT, $trans_no);
label_row(_("Prepared By"), $preared_user, "class='tableheader2'", "colspan=3");	

comments_display_row(ST_SUPPCREDIT, $trans_no);
end_table(1);

$total_gl = display_gl_items($supp_trans, 3);
$total_grn = display_grn_creditnote_items($supp_trans, 2);

$display_sub_tot = number_format2($total_gl+$total_grn,user_price_dec());

start_table(TABLESTYLE, "width='95%'");
label_row(_("Sub Total"), $display_sub_tot, "align=right", "nowrap align=right width='17%'");

if($supp_trans->freight_cost!=0)
{	
label_row(_("Freight Charges"),price_format(-$supp_trans->freight_cost),
	"align=right colspan=1", "nowrap align=right");
}

if($supp_trans->additional_charges!=0)
{	
label_row(_("Additional Charges"),price_format(-$supp_trans->additional_charges),
	"align=right colspan=1", "nowrap align=right");
}

if($supp_trans->packing_charges!=0)
{	
label_row(_("Packing Charges"),price_format(-$supp_trans->packing_charges),
	"align=right colspan=1", "nowrap align=right");
}

if($supp_trans->other_charges!=0)
{	
label_row(_("Other Charges"),price_format(-$supp_trans->other_charges),
	"align=right colspan=1", "nowrap align=right");
}

$tax_items = get_trans_tax_details(ST_SUPPCREDIT, $trans_no);
display_supp_trans_tax_details($tax_items, 1);

if($supp_trans->ov_roundoff!=0)
{	
label_row(_("Round Off"),price_format(-$supp_trans->ov_roundoff),
	"align=right colspan=1", "nowrap align=right");
}

$display_total = number_format2(-($supp_trans->ov_amount + $supp_trans->ov_gst + $supp_trans->freight_cost + $supp_trans->additional_charges + $supp_trans->packing_charges + $supp_trans->other_charges+ 
$supp_trans->freight_tax + $supp_trans->additional_tax + $supp_trans->packing_tax + $supp_trans->other_tax + $supp_trans->ov_roundoff),user_price_dec());


label_row("<font color=red>" . _("TOTAL CREDIT NOTE") . "</font", "<font color=red>$display_total</font>", "colspan=1 align=right", "nowrap align=right");

	$company_currency=get_company_currency();
	
	$total_company_currency = number_format2((-($supp_trans->ov_amount + $supp_trans->ov_gst + $supp_trans->freight_cost + $supp_trans->additional_charges + $supp_trans->packing_charges + $supp_trans->other_charges+ 
    $supp_trans->freight_tax + $supp_trans->additional_tax + $supp_trans->packing_tax + 
	$supp_trans->other_tax + $supp_trans->ov_roundoff)*$supp_trans->rate),user_price_dec());

label_row("<font color=red>" . _("TOTAL CREDIT NOTE ( ".$company_currency." )") . "</font", "<font color=red>$total_company_currency</font>", 
	"colspan=1 align=right", "nowrap align=right");
end_table(1);

$voided = is_voided_display(ST_SUPPCREDIT, $trans_no, _("This credit note has been voided."));

if (!$voided)
{
	display_allocations_from(PT_SUPPLIER, $supp_trans->supplier_id, ST_SUPPCREDIT, $trans_no, -($supp_trans->ov_amount + $supp_trans->ov_gst));
}

br();
br();	
br();	
br();	
?>
  <table  align="center"    width="95%" cellpadding="" cellspacing="0">
  <tr>
  <td  style="text-align:left"><b>Prepared By</b></td>
  <td  style="text-align:right"><b>Approved By</b></td>
  </tr>
  </table>
	
<?php	

end_page(true, false, false, ST_SUPPCREDIT, $trans_no);

