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

function get_transactions($customer_id, $from, $to, $dimension=0 , $folk=0)
{
	$from = date2sql($from);
	$to = date2sql($to);

 	$sql = "SELECT 
			sorder.order_no,
			sorder.reference,
			debtor.name,
			branch.br_name,
			sorder.customer_ref as buyer_order_no,
			sorder.ord_date,
			sorder.delivery_date,
			sorder.deliver_to,
			sorder.type,
			debtor.curr_code,
			Sum(line.qty_sent) AS TotDelivered,
			Sum(line.quantity) AS TotQuantity,
			Sum(line.invoiced) AS TotInvoiced,
			Sum(line.unit_price*line.quantity*line.discount_percent*0.01) AS TotDiscount,
			sum(line.unit_price*line.quantity) AS GrossAmount,
			alloc,
			prep_amount,
			allocs.ord_payments,
			inv.inv_payments,
			sorder.total,
			sorder.trans_type,
			sorder.comments			
		FROM ".TB_PREF."sales_orders as sorder
		LEFT JOIN (SELECT trans_no_to, sum(amt) ord_payments FROM ".TB_PREF."cust_allocations WHERE trans_type_to=".ST_SALESORDER." GROUP BY trans_no_to)
			 allocs ON sorder.trans_type=".ST_SALESORDER." AND allocs.trans_no_to=sorder.order_no
		LEFT JOIN (SELECT order_, sum(prep_amount) inv_payments	FROM ".TB_PREF."debtor_trans  WHERE type=".ST_SALESINVOICE." GROUP BY order_)
				 inv ON sorder.trans_type=".ST_SALESORDER." AND inv.order_=sorder.order_no,"
			.TB_PREF."sales_order_details as line, "
			.TB_PREF."debtors_master as debtor, "
			.TB_PREF."cust_branch as branch
			WHERE sorder.order_no = line.order_no
			AND sorder.trans_type = line.trans_type
			AND sorder.trans_type = 30
			AND sorder.debtor_no = debtor.debtor_no
			AND sorder.branch_code = branch.branch_code
			AND debtor.debtor_no = branch.debtor_no 
			";

	
			$sql .=  " AND sorder.ord_date >= '$from'"
					." AND sorder.ord_date <= '$to'";
	

		//Chaiatanya : New Filter
		if ($customer_id != ALL_TEXT)
			$sql .= " AND sorder.debtor_no = ".db_escape($customer_id);	
		if ($folk != 0)
			$sql .= " AND sorder.sales_person_id = ".db_escape($folk);	
		
        if ($dimension != 0){
  		$sql .= " AND sorder.dimension_id = ".($dimension<0 ? 0 : db_escape($dimension));
		}
        else{
        $user_dms=$_SESSION["wa_current_user"]->user_dimensions;
		$sql .= " AND FIND_IN_SET(sorder.dimension_id,'$user_dms')";
        }			

		$sql .= " GROUP BY sorder.order_no,
					sorder.debtor_no,
					sorder.branch_code,
					sorder.customer_ref,
					sorder.ord_date,
					sorder.deliver_to";
		
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
    	$orientation = $_POST['PARAM_5'];
	    $destination = 1;
		
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

	$cols = array(0, 50, 100, 240, 300,330,360,400,440,490,550,660, 700);
	// $cols = array(0, 50, 120, 200, 300, 400,475,515);

	$headers = array(_('Date'), _('Reference'), _('Customer Name'),
	_('Cust PO Ref'), _('Currency'),_('Rate'), _('Gross Amount'),_('Disc Amount'), _('Total'),
 _('Total (in OMR)'), _('Req Del Date'),_('Comments'));
	

	
	$aligns = array('left',	'left',	'left',	'left','left','right','right','right','right','right','center', 'left');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Sales Folk'), 'from' => $salesfolk, 'to' => ''),
						4 => array('text' => _('Dimension')." 1", 'from' => get_dimension_string($dimension),'to' => ''));
						

    $rep = new FrontReport(_('Sales Order Listing'), "SalesOrderListing", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($fromcust,$from,$to, $dimension, $folk);
  
	$total_value =0;
	
	while ($myrow = db_fetch($result))
	{
		$copy_curr= get_company_currency();
		if($copy_curr!=$myrow['curr_code'])
		{
			// $rate = get_rate_by_transaction_date($myrow['ord_date']);
			$rate=get_last_exchange_rate($myrow['curr_code'],sql2date($myrow['ord_date']));
			$myrow['rate'] = $rate['rate_buy'];
		}else {
			$myrow['rate']=1;
		}
		 if($myrow['rate']==1)
	    $rep->SetTextColor(33, 33, 33);
	    else
	    $rep->SetTextColor(216, 67, 21);
	
		$rep->TextCol(0, 1, sql2date($myrow['ord_date']));
		$rep->TextCol(1, 2, $myrow['reference']);
		// $rep->TextCol(2, 3, $myrow['name']);
		$rep->TextCol(2, 3, $myrow['br_name']);
		$rep->TextCol(3, 4, $myrow['buyer_order_no']);
		$rep->TextCol(4, 5, $myrow['curr_code']);
		$rep->AmountCol(5, 6, $myrow['rate'],3);
		$rep->AmountCol(6, 7, $myrow['GrossAmount'], 3);
		$rep->AmountCol(7, 8, $myrow['TotDiscount'], 3);
		$rep->AmountCol(8, 9, $myrow['GrossAmount']-$myrow['TotDiscount'], 3);
		$rep->AmountCol(9, 10, ($myrow['GrossAmount']-$myrow['TotDiscount'])*$myrow['rate'],3);	
		$rep->TextCol(10, 11, sql2date($myrow['delivery_date']));
		
		
		$rep->TextCol(11, 12,$myrow['comments']);	
		
		$total_value +=($myrow['GrossAmount']-$myrow['TotDiscount'])*$myrow['rate'];
		
		
   		$rep->NewLine();
	}
	
	$rep->Line($rep->row  - 4);
	$rep->NewLine(2);
	// $rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(8, 9, 'Total');
	$rep->AmountCol(9, 10, $total_value,3);
	//$rep->SetFont('', '', 0);
	
	$rep->NewLine();
    $rep->End();
}

