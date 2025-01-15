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
$page_security = 'SA_MATERIAL_INDENT_INQUIRY';
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");

page(_($help_context = "View Material Indent"), true);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/service/includes/order_db.inc");

if (isset($_GET["trans_no"]))
{
	$trans_no = $_GET["trans_no"];
}

$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

display_heading($systypes_array[ST_MATERIAL_INDENT] . " #$trans_no");

br(1);


$indent_items = get_material_indent_header($trans_no);
$k = 0;

global $material_indent_request_type;

$header_shown = false;
while ($indent_items_array = db_fetch($indent_items))
{

	if (!$header_shown)
	{

		start_table(TABLESTYLE2, "width='90%'");
		start_row();
		label_cells(_("Requested Location"), $indent_items_array['loc1'], "class='tableheader2'");
		label_cells(_("From Location"), $indent_items_array['loc2'], "class='tableheader2'");
		label_cells(_("Reference"), $indent_items_array['reference'], "class='tableheader2'");
		end_row();
		
		start_row();
		label_cells(_("Date"), sql2date($indent_items_array['tran_date']), "class='tableheader2'");
		
		label_cells(_("Requested By"), $indent_items_array['requested_by'], "class='tableheader2'");
		
		
		label_cells(_("Indent Request Type"), $material_indent_request_type[$indent_items_array['ind_req_type_id']], "class='tableheader2'");
		
		end_row();
		
		start_row();
		$preared_user = get_transaction_prepared_by(ST_MATERIAL_INDENT, $trans_no);
label_row(_("Prepared By"), $preared_user, "class='tableheader2'");
		end_row();

		
		comments_display_row(ST_MATERIAL_INDENT, $trans_no);

		end_table();
		$header_shown = true;

		
	}
	
	
}
$indent_details = get_material_indent_items($trans_no);
$total_qty =0;
echo "<br>";
		start_table(TABLESTYLE, "width='90%'");

    	$th = array(_("Item Code"), _("Description"), _("Quantity"),_("Status"));
    	table_header($th);

    alt_table_row_color($k);
while ($indent_details_array = db_fetch($indent_details))
{
		start_row();
    label_cell($indent_details_array['stock_id']);
    label_cell($indent_details_array['item_description']);
    qty_cell($indent_details_array['quantity'], false, get_qty_dec($indent_details_array['stock_id']));
	if($indent_details_array['quantity']==$indent_details_array['qty_received'])
	{
		$status = "Completed";
	}
	else
	{
        $status = "Pending";
		
	}
	label_cell($status);
    end_row();
	
	$total_qty += $indent_details_array['quantity'];
}

label_cell("<b>Total</b>","colspan=2 align='right'");
label_cell(number_format2($total_qty,3),"colspan=1 align='right'");
end_table(1);

is_voided_display(ST_MATERIAL_INDENT, $trans_no, _("This adjustment has been voided."));

br();
br();	
br();	
br();	
?>
  <table  align="center"    width="90%" cellpadding="" cellspacing="0">
  <tr>
  <td  style="text-align:left"><b>Prepared By</b></td>
  <td  style="text-align:right"><b>Approved By</b></td>
  </tr>
  </table>
	
<?php

end_page(true, false, false, ST_MATERIAL_INDENT, $trans_no);
