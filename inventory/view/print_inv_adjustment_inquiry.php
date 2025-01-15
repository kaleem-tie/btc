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
function get_address()
{

	$sql = "SELECT value FROM ".TB_PREF."sys_prefs where name='postal_address'";

	$result = db_query($sql, "could not get address");
$myrow = db_fetch_row($result);
	return $myrow[0];
}

if (isset($_GET["id"]))
{
	$trans_no = $_GET["id"];
}

$transfer_items = get_inv_adjustment_inquiry($trans_no);

$sql ="SELECT value FROM ".TB_PREF."sys_prefs WHERE name='coy_name'";
$res=db_query($sql,'Could Not get the Logo!'); 
$result=db_fetch($res);
$company_name=$result['value'];

//logo 

$sql ="SELECT value FROM ".TB_PREF."sys_prefs WHERE name='coy_logo'";
$res=db_query($sql,'Could Not get the Logo!'); 
$result=db_fetch($res);
$company_logo=$result['value'];


// comapny address
//display_heading($systypes_array[ST_LOCTRANSFER] . " #$trans_no");

echo "<br>";
?>
<center>
<table border="0" width="90%" style="border-collapse:collapse;">
<tr>

<h1><center> <?php echo $company_name; ?>   </center></h1>

</tr>
</table>
</center>
<br><br>

<?php

display_heading($systypes_array[ST_INVADJUST] . " #$trans_no");

start_table(TABLESTYLE2, "width='90%'");

global $inv_adjustment_types; 

start_row();
label_cells(_("Date"), sql2date($transfer_items['tran_date']), "class='tableheader2'");
label_cells(_("Reference"), $transfer_items['reference'], "class='tableheader2'");
end_row();

start_row();
label_cells(_("Location"), $transfer_items['from_loc']." ".$transfer_items['location_name'], "class='tableheader2'");

if($transfer_items['adj_type']!=0)
label_cells(_("Adjustment Type"), $inv_adjustment_types[$transfer_items['adj_type']], "class='tableheader2'");
end_row();

comments_display_row(ST_INVADJUST, $trans_no);

end_table(2);

start_table(TABLESTYLE, "width='90%'");

$th = array(_("S. No"),_("Item"), _("Description"),  _("Quantity"), _("Units"),_("Unit Cost"),_("Total Cost"));
table_header($th);

$transfer_items1 = get_stock_moves(ST_INVADJUST, $trans_no);

$k = 0;
$total = 0;
$l = 1;
$tot = 0;
while ($item = db_fetch($transfer_items1))
{        

		$line_total = $item['qty']*$item['standard_cost'];
 
        alt_table_row_color($k);
		label_cell($l);
        label_cell($item['stock_id']);
        label_cell($item['description']);
        qty_cell($item['qty'], false, get_qty_dec($item['stock_id']));
        label_cell($item['units'],'align="right"' );
		label_cell($item['standard_cost'],'align="right"');
		label_cell($line_total,'align="right"');
        end_row();
		$l++;
		
		$final +=$line_total;
        $total_qty += $item['qty'];
}

label_cell("<b>Total</b>","colspan=3 align='right'");
label_cell(number_format2($total_qty,3),"colspan=1 align='right'");
label_cell("","colspan=2 align='right'");
label_cell(number_format2($final,3),"colspan=1 align='right'");
	//label_row(_("Total Cost"), number_format($final,3),
	//"align=right colspan=6", "nowrap align=right");
	
//echo "<tr><td colspan='5'></td><td class='tableheader2' align='center'>Total</td><td>$tot</td></tr>";

end_table(1);
?>


 <style>
.sub{
	 border:1px solid black;
	 border-collapse:collapse;
	 padding:10px;
 }
 @media print
{    
    .no-print, .no-print *
    {
        display: none !important;
    }
}

 </style>
<?php
is_voided_display(ST_INVADJUST, $trans_no, _("This transfer has been voided."));

//end_page(true, false, false, ST_LOCTRANSFER, $trans_no);

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

<center>
<input type="button" value="Print" id="tab" class="no-print" onclick="window.print();" style="background-color:#9ec4c2;"> 
</center>