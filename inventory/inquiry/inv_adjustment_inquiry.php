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
$page_security = 'SA_INVENTORY_ADJUSTMENT_INQUIRY'; //'SA_ITEMSTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

include_once($path_to_root . "/includes/ui.inc");


if (!@$_GET['popup']){
	$js = "";
	if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
	if (user_use_date_picker())
	$js .= get_js_date_picker();
	page(_($help_context = "Inventory Adjustment Inquiry"), @$_GET['popup'], false, "", $js);
}	


function get_stock_trans_num($location,$fromdate,$todate,$adj_type=0)
{
	
     $from=date2sql($fromdate);
    $to=date2sql($todate);

	$sql="SELECT * FROM ".TB_PREF."stock_moves as sm 
	WHERE sm.type=17 AND sm.tran_date>='$from' AND sm.tran_date<='$to'";

	 if($location!='')
	 {
	  $sql.=" AND sm.loc_code='$location'";
	 }
	 
	 
	 if($adj_type!=0)
	 { 
	    $sql.=" AND sm.adj_type=".db_escape($adj_type);
	 }
	
		$sql .=" GROUP BY sm.trans_no";
	//display_error($sql);
	 return db_query($sql, "could not query stock moves");
	
}


function get_stock_trans_date($trans_no)
{
	$sql="SELECT DISTINCT tran_date  from ".TB_PREF."stock_moves where type=17 
	and trans_no=".db_escape($trans_no);
	$result = db_query($sql, "could not query stock moves");
	$myrow = db_fetch_row($result);
	return $myrow[0]; 
}

function get_stock_loc_from($trans_no)
{
	$sql="SELECT  distinct sm.loc_code,loc.location_name  from ".TB_PREF."stock_moves sm,".TB_PREF."locations loc where sm.loc_code=loc.loc_code and sm.type=17 and sm.qty<0 and sm.trans_no=".db_escape($trans_no);
	$result = db_query($sql, "could not query stock moves");
	$myrow = db_fetch_row($result);
	return $myrow[1]; 
}

function get_stock_loc_to($trans_no)
{
	$sql="SELECT  distinct sm.loc_code,loc.location_name  from ".TB_PREF."stock_moves sm,".TB_PREF."locations loc where sm.loc_code=loc.loc_code and sm.type=17 and sm.trans_no=".db_escape($trans_no);
	$result = db_query($sql, "could not query stock moves");
	$myrow = db_fetch_row($result);
	return $myrow[1]; 
}

//------------------------------------------------------------------------------------------------

check_db_has_stock_items(_("There are no items defined in the system."));

if(get_post('ShowMoves') || get_post('adj_type'))
{
	$Ajax->activate('doc_tbl');
}

if (isset($_GET['stock_id']))
{
	$_POST['stock_id'] = $_GET['stock_id'];
}

if (!@$_GET['popup'])
	start_form();

if (!isset($_POST['stock_id']))
	$_POST['stock_id'] = get_global_stock_item();



start_table(TABLESTYLE_NOBORDER);
start_row();

locations_list_cells(_("Location:"), 'StockLocation', null, true);

inv_adjustment_types_list_cells(_("Adjustment Type:"), 'adj_type', null, true);

date_cells(_("From:"), 'AfterDate', '', null, -30);
date_cells(_("To:"), 'BeforeDate');

submit_cells('ShowMoves',_("Show Adjustments"),'',_('Refresh Inquiry'), 'default');
end_row();
end_table();
if (!@$_GET['popup'])
	end_form();

set_global_stock_item($_POST['stock_id']);


div_start('doc_tbl');

start_table(TABLESTYLE,"width='70%'");
$th = array(_("#"), _("Reference"), _("Date"), /* _("Location From"), */
		_("Location"), _("Adjustment Type"), _("Action"));
table_header($th);

$trans=get_stock_trans_num($_POST['StockLocation'],$_POST['AfterDate'],$_POST['BeforeDate'],
$_POST['adj_type']);

global $inv_adjustment_types; 

while($trans_no=db_fetch($trans))
{

	
	label_cell(get_trans_view_str($trans_no["type"], $trans_no["trans_no"]));
	label_cell($trans_no["reference"],'align=center');
	$trans_date=get_stock_trans_date($trans_no["trans_no"]);
	$date=sql2date($trans_date);
	label_cell($date,'align=center');
	$location_to=get_stock_loc_to($trans_no["trans_no"]);
	label_cell($location_to,'align=center');
	
	if($trans_no['adj_type']!=0)
	label_cell($inv_adjustment_types[$trans_no['adj_type']],'align=left');
    else
    label_cell("",'align=center'); 		
	
	//display_error($path_to_root);die;
	?>
	
	<td style="text-align:center;">
    <a href="#"  onclick="openWin(<?php echo $trans_no["trans_no"]; ?>)">Print</a>
    </td>
	<?php
	end_row();
}

// get unique transactions

end_table(1);
div_end();
if (!@$_GET['popup'])
	end_page(@$_GET['popup'], false, false);

?>
 <script>
 var myWindow;

function openWin(url_id) {
	myWindow = window.open("<?php echo $path_to_root . "/inventory/view/print_inv_adjustment_inquiry.php?id=".'"+url_id+"';?>", "Inventory Transfer Report", "width=800,height=700");
} 

</script>
