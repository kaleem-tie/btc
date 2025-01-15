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
$page_security = 'SA_SCRDLISTINGREP';

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

function get_transactions($customer_id=null,$from,$to,$dimension=0, $folk=0)
{
	$from = date2sql($from);
	$to = date2sql($to);

	$sql = "SELECT DISTINCT
		trans.type,
		trans.trans_no,
		trans.reference,
		trans.tran_date,
		debtor.name AS DebtorName, 
		debtor.curr_code,
		trans.alloc,
		trans.rate,
		ov_amount+ov_freight+ov_freight_tax+ov_discount AS Total,
		trans.prep_amount,
		trans.ov_gst,
		trans.due_date,
		debtor.address,
		trans.debtor_no,
		debtor.curr_code,
		trans.tax_included,
		trans.ov_roundoff
	 FROM ".TB_PREF."debtor_trans as trans, 
	 ".TB_PREF."debtors_master as debtor"." 
	 WHERE trans.debtor_no=debtor.debtor_no
	AND ((trans.type=".ST_CUSTCREDIT.") AND (trans.ov_amount > 0))";
		
	$sql .=  " AND trans.tran_date >= '$from'"
					." AND trans.tran_date <= '$to'";	

	if ($customer_id != ALL_TEXT)
			$sql .= " AND trans.debtor_no = ".db_escape($customer_id);	

	if ($folk != 0)
			$sql .= " AND trans.sales_person_id = ".db_escape($folk);
	
	if ($dimension != 0){
  		$sql .= " AND trans.dimension_id = ".($dimension<0 ? 0 : db_escape($dimension));
		}
    else{
        $user_dms=$_SESSION["wa_current_user"]->user_dimensions;
		$sql .= " AND FIND_IN_SET(trans.dimension_id,'$user_dms')";
        }	
		
    $sql .=" ORDER BY trans.tran_date";
	
	
	return db_query($sql,"No transactions were returned");
}


//----------------------------------------------------------------------------------------------------

function print_customer_orders()
{
    	global $path_to_root, $systypes_array;

    	$from = $_POST['PARAM_0'];
    	$to = $_POST['PARAM_1'];
    	$fromcust = $_POST['PARAM_2'];
		$dimension = $_POST['PARAM_3'];
		$folk = $_POST['PARAM_4'];  // added by Faisal to filter by sales person
		$currency = $_POST['PARAM_5'];
    	$orientation = $_POST['PARAM_6'];
	    $destination = $_POST['PARAM_7'];
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
    	
		$dec = user_price_dec();
	
	if ($currency == ALL_TEXT)
    {
        $convert = true;
        $currency = _('Balances in Home Currency');
    }
    else
        $convert = false;
	
	if ($folk == ALL_NUMERIC)
        $folk = 0;
    if ($folk == 0)
        $salesfolk = _('All Sales Man');
     else
        $salesfolk = get_salesman_name($folk);
	
	$cols = array(0, 80,170,260,350, 400, 500);

	$headers = array(_('S.No'), _('Date'),_('Taxable Value(in OMR)'),_('Tax Value'), _('Round off'), _('Total Amount'));

	
	$aligns = array('left','left','right','right','right','right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						4 => array('text' => _('Location'), 'from' => $loc, 'to' => ''),
						5 => array('text' => _('Dimension')." 1", 'from' => get_dimension_string($dimension),'to' => ''),
						6 => array('text' => _('Sales Folk'), 'from' => $salesfolk,	'to' => ''));

    $rep = new FrontReport(_('Day wise Sales Invoice Listing'), "DaySalesInvoiceListing", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($fromcust,$from,$to,$dimension, $folk);
$k=1;
$tot_value = 0;
$total_taxable_value=0;
$total_tax_value=0;
$total_roundoff=0;

$sales_data=array();
$sale_tran_dates=array();
$unique_sale_tran_dates=array();
 
	while ($myrow = db_fetch($result))
	{
		
				
		array_push($sale_tran_dates,$myrow['tran_date']);
		
	    if (!$convert && $currency != $myrow['curr_code']) continue;
		
		$tot_value += $myrow['Total']+$myrow['ov_gst']+$myrow['ov_roundoff'];
		if($myrow['tax_included']==1)
		{
			//Ramesh
		 $Total_amt=0;
		 $Total_tax=0;
		  $tax_items = get_trans_tax_details(ST_CUSTCREDIT, $myrow['trans_no']);
    		while ($tax_item = db_fetch($tax_items))
    		{
    			if ($tax_item['amount'] == 0)
    				continue;
    			$Total_tax += $tax_item['amount'];
				
				if ($tax_item['net_amount'] == 0)
    				continue;
				$Total_amt += $tax_item['net_amount'];
    		}
			
			if(!isset($sales_data[$myrow['tran_date']]))
			{
			$sales_data[$myrow['tran_date']]['taxable_value']=0;
			$sales_data[$myrow['tran_date']]['tax_value']=0;
			$sales_data[$myrow['tran_date']]['ov_roundoff']=0;
			}
			
			$sales_data[$myrow['tran_date']]['taxable_value']+= $Total_amt*$myrow['rate'];
			$sales_data[$myrow['tran_date']]['tax_value']+=$Total_tax*$myrow['rate'];
			$sales_data[$myrow['tran_date']]['ov_roundoff']+=$myrow['ov_roundoff'];
			
		
			$total_taxable_value+=$Total_amt*$myrow['rate'];
			$total_tax_value+=$Total_tax*$myrow['rate'];
			$total_roundoff+=$myrow['ov_roundoff'];
		} else {
			
			if(!isset($sales_data[$myrow['tran_date']]))
			{
			$sales_data[$myrow['tran_date']]['taxable_value']=0;
			$sales_data[$myrow['tran_date']]['tax_value']=0;
			$sales_data[$myrow['tran_date']]['ov_roundoff']=0;
			}
			
			$sales_data[$myrow['tran_date']]['taxable_value']+= $myrow['Total']*$myrow['rate'];
			$sales_data[$myrow['tran_date']]['tax_value']+=$myrow['ov_gst']*$myrow['rate'];
			$sales_data[$myrow['tran_date']]['ov_roundoff']+=$myrow['ov_roundoff'];
			
			
			$total_taxable_value+=$myrow['Total']*$myrow['rate'];
			$total_tax_value+=$myrow['ov_gst']*$myrow['rate'];
			$total_roundoff+=$myrow['ov_roundoff'];
		}
		
	
   		

	}
	
	  $unique_sale_tran_dates=array_unique($sale_tran_dates);
	  
	 $k=1;
	  foreach($unique_sale_tran_dates as $day_sale)
	   {
		  $rep->TextCol(0, 1, $k);
		  $rep->TextCol(1, 2, sql2date($day_sale));
		  $rep->AmountCol(2, 3, $sales_data[$day_sale]['taxable_value'],3);
		  $rep->AmountCol(3, 4,  $sales_data[$day_sale]['tax_value'],3);
		  $rep->AmountCol(4, 5,  $sales_data[$day_sale]['ov_roundoff'],3);
		  $rep->AmountCol(5, 6,  $sales_data[$day_sale]['taxable_value']+ $sales_data[$day_sale]['tax_value'],3);
		  $rep->NewLine();
		  $k++;
		 
	   }  
	
	
	
	
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
	$rep->NewLine();
	$rep->TextCol(1, 2, 'Total');
	$rep->AmountCol(2, 3, $total_taxable_value,$dec);
	$rep->AmountCol(3, 4, $total_tax_value,$dec);
	$rep->AmountCol(4, 5, $total_roundoff,$dec);
	$rep->AmountCol(5, 6, $tot_value,$dec); 
    $rep->End();
}

