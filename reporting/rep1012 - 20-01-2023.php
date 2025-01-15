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

function get_transactions($customer_id, $from, $to)
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
			alloc,
			prep_amount,
			allocs.ord_payments,
			inv.inv_payments,
			sorder.total,
			sorder.trans_type
		FROM ".TB_PREF."sales_orders as sorder
		LEFT JOIN (SELECT trans_no_to, sum(amt) ord_payments FROM ".TB_PREF."cust_allocations WHERE trans_type_to=".ST_SALESORDER." GROUP BY trans_no_to)
			 allocs ON sorder.trans_type=".ST_SALESORDER." AND allocs.trans_no_to=sorder.order_no
		LEFT JOIN (SELECT order_, sum(prep_amount) inv_payments	FROM ".TB_PREF."debtor_trans WHERE type=".ST_SALESINVOICE." GROUP BY order_)
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
    	$orientation = $_POST['PARAM_3'];
	    $destination = $_POST['PARAM_4'];
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
    	$dec = user_price_dec();

	$cols = array(0, 33, 100, 240, 300,350,370,410,480,540);
	// $cols = array(0, 50, 120, 200, 300, 400,475,515);

	$headers = array(_('Date'), _('Reference'), _('Customer Name'), _('Cust PO Ref'), _('Currency'),_('Rate'), _('Total'), _('Txbl Val(in OMR)'), _('Req Del Date'));

	// if ($show_balance)
		// $headers[7] = _('Balance');
	$aligns = array('center',	'center',	'left',	'left','left','right','right','right','center');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => '')
						);

    $rep = new FrontReport(_('Customer Orders'), "CustomerBalances", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($fromcust,$from,$to);
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
		$rep->AmountCol(5, 6, $myrow['rate'],4);
		$rep->AmountCol(6, 7, $myrow['total'], 3);
		$rep->AmountCol(7, 8, $myrow['total']*$myrow['rate'],3);		
		$rep->TextCol(8, 9, sql2date($myrow['delivery_date']));
		$total_value +=$myrow['total']*$myrow['rate'];
		
		
   		$rep->NewLine(2);
	}
	
	$rep->TextCol(6, 7, 'Total');
	$rep->AmountCol(7, 8, $total_value,3);
	$rep->NewLine();
    	$rep->End();
}

