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
$page_security = 'SA_ITEMSTRANSVIEW';
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");

page(_($help_context = "View Inventory Transfer"), true);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

if (isset($_GET["trans_no"]))
{
	$trans_no = $_GET["trans_no"];
}

$intransit=0;
if (isset($_GET["intransit"]))
{
	$intransit = $_GET["intransit"];
}


$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

$trans = get_stock_transfer($trans_no,$intransit);


display_heading($systypes_array[ST_LOCTRANSFER] . " #$trans_no");

echo "<br>";
start_table(TABLESTYLE2, "width='90%'");

start_row();
label_cells(_("Reference"), $trans['reference'], "class='tableheader2'");
label_cells(_("Date"), sql2date($trans['tran_date']), "class='tableheader2'");
end_row();
start_row();
label_cells(_("From Location"), $trans['from_name'], "class='tableheader2'");
if($intransit)
label_cells(_("To Location"), 'Intransit', "class='tableheader2'");	
else
label_cells(_("To Location"), $trans['to_name'], "class='tableheader2'");
end_row();

$preared_user = get_transaction_prepared_by(ST_LOCTRANSFER, $trans_no);
label_row(_("Prepared By"), $preared_user, "class='tableheader2'");

comments_display_row(ST_LOCTRANSFER, $trans_no);

end_table(2);

start_table(TABLESTYLE, "width='90%'");

$th = array(_("Item Code"), _("Description"), _("Quantity"), _("Units"));
table_header($th);
$transfer_items = get_stock_moves(ST_LOCTRANSFER, $trans_no);
$k = 0;
$total_qty =0;

if($intransit==0)
{
while ($item = db_fetch($transfer_items))
{
	if ($item['loc_code'] == $trans['to_loc'])
	{
        alt_table_row_color($k);

        label_cell($item['stock_id']);
        label_cell($item['description']);
        qty_cell($item['qty'], false, get_qty_dec($item['stock_id']));
        label_cell($item['units']);
        end_row();
		
		$total_qty += $item['qty'];
	}
}
}
else
{
while ($item = db_fetch($transfer_items))
{
	    alt_table_row_color($k);

        label_cell($item['stock_id']);
        label_cell($item['description']);
        qty_cell(abs($item['qty']), false, get_qty_dec($item['stock_id']));
        label_cell($item['units']);
        end_row();
		
		$total_qty += abs($item['qty']);
	
}	
}

label_cell("<b>Total</b>","colspan=2 align='right'");
label_cell(number_format2($total_qty,3),"colspan=1 align='right'");

end_table(1);

is_voided_display(ST_LOCTRANSFER, $trans_no, _("This transfer has been voided."));

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

end_page(true, false, false, ST_LOCTRANSFER, $trans_no);
