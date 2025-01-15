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
$page_security = 'SA_STOCK_MONTH_SALES';

if (@$_GET['page_level'] == 1)
	$path_to_root = "../..";
else	
	$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows && $SysPrefs->use_popup_search)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "Inventory Item Other"), false, false, "", $js);

//---------------------------------------------------------------------------------------------------

//check_db_has_stock_items(_("There are no items defined in the system."));



simple_page_mode(true);

//---------------------------------------------------------------------------------------------------
$input_error = 0;

if (isset($_GET['stock_id']))
{
	$_POST['stock_id'] = $_GET['stock_id'];
}
if (isset($_GET['Item']))
{
	$_POST['stock_id'] = $_GET['Item'];
}


function get_item_sales_data($from_date,$to_date,$stock_id){
	$sql = "SELECT dts.unit_price,dts.quantity,dts.discount_percent,trans.rate FROM ".TB_PREF."debtor_trans_details as dts LEFT JOIN ".TB_PREF."debtor_trans as trans ON trans.trans_no = dts.debtor_trans_no WHERE trans.type='10' AND dts.debtor_trans_type=10 AND dts.stock_id = ".db_escape($stock_id)." AND trans.tran_date >= ".db_escape($from_date)." AND trans.tran_date <= ".db_escape($to_date).""; 
		
	$res =  db_query($sql);
	return $res;
}
//---------------------------------------------------------------------------------------------------
$action = $_SERVER['PHP_SELF'];
if ($page_nested)
	$action .= "?stock_id=".get_post('stock_id');
start_form(false, false, $action);
start_table(TABLESTYLE_NOBORDER );
start_row();

date_cells(_("From:"), 'AfterDate', '', null, -user_transaction_days());


submit_cells('ShowMoves',_("Show"),'',_('Refresh Inquiry'), 'default');
end_row();
end_table();
end_form();

//----------------------------------------------------------------------------------------------------



if (list_updated('stock_id')) {
	$Ajax->activate('month_sales');
	
}
if(isset($_POST['ShowMoves'])){
	
	$Ajax->activate('month_sales');
}


//---------------------------------------------------------------------------------------------------




div_start('month_sales');
$date = date2sql($_POST['AfterDate']);
 $mon  = date('m', strtotime($date));
    $year   = date('Y', strtotime($date));
    

	$str = ($year-1).'-'.$mon;
	
   $start    = (new DateTime('1 year ago'))->modify('first day of '.$str.'');
	
	
    //$end      = (new DateTime())->modify('first day of this month');
	
	$end = new DateTime($date);

    $interval = new DateInterval('P1M');
	
    $period   = new DatePeriod($start, $interval, $end);
    
    $months = array();
    foreach ($period as $dt) { 
        $months[$dt->format('Y-m-d')] = $dt->format('Y-m');
    }
    $reverse_months = array_reverse($months);
	start_row();
	//echo "<center><table";
	
	start_outer_table(TABLESTYLE2,"8.33%");
	$i=0;
   foreach($reverse_months as $key => $mns){
	   $date = strtotime($mns);
	$i++;   
// Last date of current month.
$lastdate = strtotime(date("Y-m-t", $date ));

$day = date("Y-m-d", $lastdate);
$month = date('M', $lastdate);
$year = date('Y', $lastdate);
	  $item_result = get_item_sales_data($key,$day,$_POST['stock_id'] );
	  $qty = 0;
	  $tot_price = 0;
	  while($item_res = db_fetch($item_result)){
		 $qty += $item_res['quantity'];
		 $tot_price += round2(($item_res['quantity']*$item_res['unit_price']-($item_res['quantity']*$item_res['unit_price']*$item_res['discount_percent']/100))*$item_res['rate'],user_price_dec());
	  }
		table_section($i);
	    
		  echo '<tr  style=" border: 1px solid #ada6a6; "><td colspan="2"  class="tableheader">'.$month.' '.$year.'</td></tr>';
		  
		  echo '<tr  ><td style=" border: 1px solid #ada6a6;"> '.$qty.'</td><td style=" border: 1px solid #ada6a6;">'.$tot_price.'</td></tr>';
		   		   
   }
   end_outer_table(1);
 

end_row();

div_end();

end_form();
end_page();
