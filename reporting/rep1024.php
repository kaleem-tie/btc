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
$page_security = 'SA_SALESORDER';

// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Customer Balances
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");

//----------------------------------------------------------------------------------------------------

print_customer_orders();

function get_transactions($customer_id=null,$from,$to,$location,$dimension=0,$folk=0)
{
	$from = date2sql($from);
	$to = date2sql($to);
	
	$sql = "SELECT DISTINCT
			sorder.order_no,
			sorder.reference,
			debtor.name,
			sorder.customer_ref as buyer_order_no,
			sorder.ord_date,
			sorder.delivery_date,
			sorder.deliver_to,
			sorder.type,
			debtor.name,
			debtor.curr_code,
			sum(det.unit_price*det.quantity*(100-det.discount_percent)*0.01) as total,
			sorder.trans_type
		FROM ".TB_PREF."sales_orders as sorder,
		      ".TB_PREF."sales_order_details as det,
	        ".TB_PREF."debtors_master as debtor
			WHERE sorder.trans_type = 30 AND det.trans_type=30 and sorder.trans_type=det.trans_type and sorder.order_no=det.order_no 
			AND sorder.debtor_no = debtor.debtor_no
			";
			
		$sql .=  " AND sorder.ord_date >= '$from'"
				." AND sorder.ord_date <= '$to'";


	if ($customer_id != ALL_TEXT)
			$sql .= " AND sorder.debtor_no = ".db_escape($customer_id);	
		
	if ($folk != 0)
			$sql .= " AND sorder.sales_person_id = ".db_escape($folk);	
		
	if ($location != '')
		$sql .= " AND sorder.from_stk_loc = ".db_escape($location);	
	
	if ($dimension != 0){
  		$sql .= " AND sorder.dimension_id = ".($dimension<0 ? 0 : db_escape($dimension));
		}
    else{
        $user_dms=$_SESSION["wa_current_user"]->user_dimensions;
		$sql .= " AND FIND_IN_SET(sorder.dimension_id,'$user_dms')";
        }	
		
    $sql .=" group by sorder.order_no ORDER BY sorder.ord_date";
	
	
	return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_customer_orders()
{
    global $path_to_root, $systypes_array;

    $from        = $_POST['PARAM_0'];
    $to          = $_POST['PARAM_1'];
    $fromcust    = $_POST['PARAM_2'];
	$location    = $_POST['PARAM_3'];
	$dimension   = $_POST['PARAM_4'];
	$folk = $_POST['PARAM_5'];  // added by Faisal to filter by sales person
	$currency    = $_POST['PARAM_6'];
    $orientation = $_POST['PARAM_7'];
	$destination = $_POST['PARAM_8'];
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
    
	if ($folk == ALL_NUMERIC)
        $folk = 0;
    if ($folk == 0)
        $salesfolk = _('All Sales Man');
     else
        $salesfolk = get_salesman_name($folk);
	
	$dec = user_price_dec();
	
	if ($currency == ALL_TEXT)
    {
        $convert = true;
        $currency = _('Balances in Home Currency');
    }
    else
        $convert = false;
	
	if ($location == '')
		$loc = _('All');
	else
		$loc = get_location_name($location);

	$cols = array(0, 100,350,500);

	$headers = array(_('S.No'), _('Date'),_('Sales Order Value(in OMR)'));

	
	$aligns = array('left','left','right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						4 => array('text' => _('Location'), 'from' => $loc, 'to' => ''),
						5 => array('text' => _('Dimension')." 1", 'from' => get_dimension_string($dimension),'to' => ''),
						6 => array('text' => _('Sales Folk'), 'from' => $salesfolk, 	'to' => ''));

    $rep = new FrontReport(_('Day wise Sales Order Listing'), "DaySalesOrderListing", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


$result=get_transactions($fromcust,$from,$to,$location,$dimension,$folk);
$k=1;
$tot_value = 0;
$total_taxable_value=0;

$sales_data=array();
$sale_tran_dates=array();
$unique_sale_tran_dates=array();
 
	while ($myrow = db_fetch($result))
	{
		
				
		array_push($sale_tran_dates,$myrow['ord_date']);
		
	    if (!$convert && $currency != $myrow['curr_code']) continue;
		
		$order_date = sql2date($myrow['ord_date']);
		
		$rate = $convert ? get_exchange_rate_from_home_currency($myrow['curr_code'], $order_date) : 1;
		
		$tot_value += $myrow['total'];
			
			if(!isset($sales_data[$myrow['ord_date']]))
			{
			$sales_data[$myrow['ord_date']]['order_value']=0;
			}
			
			$sales_data[$myrow['ord_date']]['order_value']+= $myrow['total']*$rate;
			$total_taxable_value+=$myrow['total']*$rate;
			
			//display_error($total_taxable_value);
			
	}
	
	  $unique_sale_tran_dates=array_unique($sale_tran_dates);
	  
	 $k=1;
	  foreach($unique_sale_tran_dates as $day_sale)
	   {
		  $rep->TextCol(0, 1, $k);
		  $rep->TextCol(1, 2, sql2date($day_sale));
		  $rep->AmountCol(2, 3, $sales_data[$day_sale]['order_value'],$dec);
		  $rep->NewLine();
		  $k++;
		 
	   }  
	
	
	
	
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
	$rep->NewLine();
	$rep->TextCol(1, 2, 'Total');
	$rep->AmountCol(2, 3, $total_taxable_value,$dec); 
    $rep->End();
}

