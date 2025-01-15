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
$page_security = 'SA_UNALLOC_CUST_TRANS_REP';

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

print_unallocated_customer_transactions();

function get_transactions($customer_id=null)
{
	
  $sql = "SELECT
		trans.type,
		trans.trans_no,
		trans.reference,
		trans.tran_date,
		debtor.name AS DebtorName, 
		debtor.curr_code,
		ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount+ov_roundoff AS Total,
		trans.alloc,
		trans.due_date,
		debtor.address,
		trans.version,
		round(abs(ov_amount)+ov_gst+ov_freight+ov_freight_tax+ov_discount+ov_roundoff-alloc,6) <= 0 AS settled,
		trans.debtor_no

	 FROM "
	 	.TB_PREF."debtor_trans as trans, "
		.TB_PREF."debtors_master as debtor"
	." WHERE trans.debtor_no=debtor.debtor_no
		AND (((type=".ST_CUSTPAYMENT." OR type=".ST_BANKDEPOSIT.") AND (trans.ov_amount > 0))
		 OR (type=".ST_CUSTCREDIT. " AND (ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount+ov_roundoff)>0)
		 OR (type=".ST_JOURNAL. " AND (trans.ov_amount < 0)))";

    $sql .= " AND round(abs(ov_amount+ov_gst)+ov_freight+ov_freight_tax+ov_discount+ov_roundoff-alloc, 6) > 0";

	if ($customer_id != ALL_TEXT)
			$sql .= " AND trans.debtor_no = ".db_escape($customer_id);	
	
	return db_query($sql,"No transactions were returned");
}


//----------------------------------------------------------------------------------------------------

function print_unallocated_customer_transactions()
{
    	global $path_to_root, $systypes_array;
    	
		
    	$fromcust    = $_POST['PARAM_0'];
    	$orientation = $_POST['PARAM_1'];
	    $destination = $_POST['PARAM_2'];
		
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'P' : 'L');
    

	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
	
    	
	$dec = user_price_dec();
	
	
	$cols = array(0, 100,130,180,230,360,400,450,530);

    $headers = array(_('Transaction Type'), _('#'), _('Reference'), _('Date'),
                   _('Customer'), _('Currency'), _('Total'),  _('Left to Allocate'));
	            
				
    $aligns = array('left','left','left','left','left','left','right','right');
	

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''));

    $rep = new FrontReport(_('Unallocated Customer Transactions'), 
      "UnallocatedCustomerTransactions", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($fromcust);
  $k=1;
  $total_value = $total_alloc = 0;

 
	while ($myrow = db_fetch($result))
	{
		
      $total = price_format($myrow['type'] == ST_JOURNAL && $myrow["Total"] < 0 ? -$myrow["Total"] : $myrow["Total"]);
      $amount_left = price_format(($myrow['type'] == ST_JOURNAL && $myrow["Total"] < 0 ? -$myrow["Total"] : $myrow["Total"])-$myrow["alloc"]);
		
	    $rep->TextCol(0, 1, $systypes_array[$myrow['type']]);
		$rep->TextCol(1, 2, $myrow['trans_no']);
		$rep->TextCol(2, 3, $myrow['reference']);
		$rep->TextCol(3, 4, sql2date($myrow['tran_date']));
		if ($destination){
		$rep->TextCol(4, 5, $myrow["DebtorName"]);	
		}
		else{
		$oldrow = $rep->row;
		$rep->TextColLines(4, 5, $myrow["DebtorName"], -2);
		$newrow = $rep->row;
		$rep->row = $oldrow;
		}
        
		$rep->TextCol(5, 6, $myrow['curr_code']);	
		$rep->TextCol(6, 7, $total);
		$rep->TextCol(7, 8, $amount_left);
        
		if ($destination){
		$rep->NewLine();
		}
		else{
		$rep->row = $newrow;
		}
		$rep->NewLine();
		$k++;

       $total_value +=$myrow['Total']; 
       $total_alloc +=$myrow['alloc'];
	}
    
    $rep->Line($rep->row  - 4);
	$rep->NewLine(2);
	//$rep->TextCol(8, 9, 'Total');
	//$rep->AmountCol(7, 8, $total_value,3);
	$rep->NewLine();
    $rep->End();
}

