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
$page_security = 'SA_SALESDELIVERY';

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


function get_transactions($customer_id, $from, $to, $dimension=0, $folk=0)
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
			debtor.name,
			debtor.curr_code,
			line.id as line_order_no, 
			line.stk_code,
			line.description,
			line.qty_sent AS TotDelivered,
			line.quantity AS TotQuantity,
			line.invoiced AS TotInvoiced,
			line.unit_price,
			line.discount_percent,
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
			AND sorder.reference!='auto' 
			AND (line.quantity>line.qty_sent)
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

				
        
			
    return db_query($sql,"No transactions were returned");
}


function get_transaction_user($so_no)
{
	
	$sql="SELECT user_tbl.user_id from ".TB_PREF."audit_trail at_tbl,".TB_PREF."users user_tbl where at_tbl.trans_no=".db_escape($so_no)." and at_tbl.type=30 and at_tbl.user=user_tbl.id order by at_tbl.id desc";
		
	$result=db_query($sql,"No transactions were returned");
	
	$row=db_fetch_row($result);
	return $row[0];
}

function get_invoiced_qty($soline_no)
{
	 $sql="SELECT sum(dtd.quantity) from  ".TB_PREF."debtor_trans_details dtd where dtd.debtor_trans_type=10 and dtd.src_id in (select dispatch.id from ".TB_PREF."debtor_trans_details dispatch where dispatch.debtor_trans_type=13 and dispatch.src_id=".db_escape($soline_no).")";
	 		
	$result=db_query($sql,"No transactions were returned");
	
	$row=db_fetch_row($result);
	return $row[0];
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

	$cols = array(0, 50, 100, 150, 300,340,480,520,560,600,650,700);

	$headers = array(_('SO Date'), _('So Number'), 
	_('Cust PO Ref'),_('Customer Name'),_('Item Code'),_('Item Name'), _('Qty'),_('Req Delivery Date'),_('Dispatch Qty'),_('Invoice Qty'),_('User'),_('Comments'));
	

	$aligns = array('left',	'left',	'left',	'left','left','left','left','right','right','right','center', 'left');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Dimension')." 1", 'from' => get_dimension_string($dimension),'to' => ''),
						4 => array('text' => _('Sales Folk'), 'from' => $salesfolk,	'to' => ''));
						

    $rep = new FrontReport(_('Pending DO Items Listing'), "PendingDOItemsListing", user_pagesize(), 9, $orientation);
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
		$rep->TextCol(2, 3, $myrow['buyer_order_no']);
		$rep->TextCol(3, 4, $myrow['name']);
		$rep->TextCol(4, 5, $myrow['stk_code']);
		$rep->TextCol(5, 6, $myrow['description']);
		$rep->TextCol(6, 7, $myrow['TotQuantity']);
		$rep->TextCol(7, 8, sql2date($myrow['delivery_date']));
		$rep->TextCol(8, 9, $myrow['TotDelivered']);
		$rep->TextCol(9, 10, get_invoiced_qty($myrow['line_order_no']));
		$rep->TextCol(10, 11, get_transaction_user($myrow['order_no']));
		$rep->TextCol(11, 12,$myrow['comments']);	
		
	
   		$rep->NewLine();
	}
		
	$rep->NewLine();
    $rep->End();
}

