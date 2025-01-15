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
$page_security = 'SA_SINLISTINGREP';

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

function get_transactions($customer_id=null,$from,$to,$location,$dimension=0, $folk=0)
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
		trans.tax_included
	 FROM ".TB_PREF."debtor_trans as trans, 
	 ".TB_PREF."sales_orders as sorder,
	 ".TB_PREF."debtors_master as debtor"." 
	 WHERE trans.debtor_no=debtor.debtor_no
	 AND trans.order_=sorder.order_no
	AND ((trans.type=".ST_SALESINVOICE.") AND (trans.ov_amount > 0))";
		
	$sql .=  " AND trans.tran_date >= '$from'"
					." AND trans.tran_date <= '$to'";	

	if ($customer_id != ALL_TEXT)
			$sql .= " AND trans.debtor_no = ".db_escape($customer_id);	
		
	if ($location != '')
		$sql .= " AND sorder.from_stk_loc = ".db_escape($location);	
	
	if ($folk != 0)
			$sql .= " AND sorder.sales_person_id = ".db_escape($folk);
		
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
		$location = $_POST['PARAM_3'];
		$dimension = $_POST['PARAM_4'];
		$folk = $_POST['PARAM_5'];  // added by Faisal to filter by sales person
		$currency = $_POST['PARAM_6'];
    	$orientation = $_POST['PARAM_7'];
	    $destination = $_POST['PARAM_8'];
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
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

	$cols = array(0, 20, 65, 210,250,280,320,370,450,500,550);

	$headers = array(_('S.No'), _('Date'), _('Customer'), _('Reference'),  
	_('Currency'),_('Rate'),_('Alloc Amount'), 
	_('Taxable Value(in OMR)'),_('Tax Value'), _('Total Amount'));

	
	$aligns = array('left','left','left','left','left',	'center','right','right','right','right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						4 => array('text' => _('Location'), 'from' => $loc, 'to' => ''),
						5 => array('text' => _('Dimension')." 1", 'from' => get_dimension_string($dimension),'to' => ''),
						6 => array('text' => _('Sales Folk'), 'from' => $salesfolk,	'to' => ''));

    $rep = new FrontReport(_('Sales Invoice Listing'), "SalesInvoiceListing", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($fromcust,$from,$to,$location,$dimension, $folk);
$k=1;
$tot_value = 0;
$total_taxable_value=0;
$total_tax_value=0;
 
	while ($myrow = db_fetch($result))
	{
	    if (!$convert && $currency != $myrow['curr_code']) continue;
		
	    if($myrow['rate']==1)
	    $rep->SetTextColor(33, 33, 33);
	    else
	    $rep->SetTextColor(216, 67, 21);
	    
		$rep->TextCol(0, 1, $k);
		$rep->TextCol(1, 2, sql2date($myrow['tran_date']));
		$rep->TextCol(2, 3, $myrow['DebtorName']);
		$rep->TextCol(3,4, $myrow['reference']);
		$rep->TextCol(4, 5, $myrow['curr_code']);
		$rep->AmountCol(5,6, $myrow['rate'],4);
		$rep->AmountCol(6, 7, $myrow['alloc'],$dec);
		
		
		
		$tot_value += $myrow['Total']+$myrow['ov_gst'];
		if($myrow['tax_included']==1)
		{
			//Ramesh
		 $Total_amt=0;
		 $Total_tax=0;
		  $tax_items = get_trans_tax_details(ST_SALESINVOICE, $myrow['trans_no']);
    		while ($tax_item = db_fetch($tax_items))
    		{
    			if ($tax_item['amount'] == 0)
    				continue;
    			$Total_tax += $tax_item['amount'];
				
				if ($tax_item['net_amount'] == 0)
    				continue;
				$Total_amt += $tax_item['net_amount'];
    		}
			$rep->AmountCol(7,8, $Total_amt*$myrow['rate'],$dec);
			$rep->AmountCol(8,9, $Total_tax*$myrow['rate'],$dec);
		
			$total_taxable_value+=$Total_amt*$myrow['rate'];
			$total_tax_value+=$Total_tax*$myrow['rate'];
		} else {
			$rep->AmountCol(7,8, $myrow['Total']*$myrow['rate'],$dec);
			$rep->AmountCol(8,9, $myrow['ov_gst']*$myrow['rate'],$dec);
			
			$total_taxable_value+=$myrow['Total']*$myrow['rate'];
			$total_tax_value+=$myrow['ov_gst']*$myrow['rate'];
		}
		
		
		$rep->AmountCol(9,10, $myrow['Total']+$myrow['ov_gst'],$dec);
		// $rep->AmountCol(7, 8, $myrow['prep_amount'],2);
		
	
   		$rep->NewLine(1);
		
		$k++;
	}
	
	
	
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
	$rep->NewLine();
	$rep->TextCol(5, 7, 'Total');
	$rep->AmountCol(7, 8, $total_taxable_value,$dec);
	$rep->AmountCol(8, 9, $total_tax_value,$dec);
	$rep->AmountCol(9, 10, $tot_value,$dec);
    $rep->End();
}

