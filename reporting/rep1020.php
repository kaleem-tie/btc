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
$page_security = 'SA_SALESPAYMNT';

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

function get_transactions($customer_id=null,$from,$to)
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
		trans.ov_amount,
		bt.mode_of_payment,
		trans.ov_gst,
		trans.ov_roundoff
	 FROM ".TB_PREF."debtor_trans as trans,".TB_PREF."bank_trans as bt,
	 ".TB_PREF."debtors_master as debtor"." 
	 WHERE trans.debtor_no=debtor.debtor_no and trans.type=bt.type and trans.trans_no=bt.trans_no 
	AND trans.type=".ST_CUSTPAYMENT." AND trans.ov_amount > 0 ";
		
	$sql .=  " AND trans.tran_date >= '$from'"
					." AND trans.tran_date <= '$to'";	

	if ($customer_id != ALL_TEXT)
			$sql .= " AND trans.debtor_no = ".db_escape($customer_id);	
				
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
		$currency = $_POST['PARAM_3'];
    	$orientation = $_POST['PARAM_4'];
	    $destination = $_POST['PARAM_5'];
		
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
	
	if ($currency == ALL_TEXT)
    {
        $convert = true;
        $currency = _('Balances in Home Currency');
    }
    else
        $convert = false;
	
	
	$cols = array(0, 50,80,260,310,360, 400, 450,515);

	$headers = array(_('Date'), _('Receipt No'), _('Customer'), _('Payment Mode'),  
	_('Amount'),_('VAT'),_('Round off'),_('Gross Total'));

	
	$aligns = array('left','left','left','left','right','right','right','right','right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						);

    $rep = new FrontReport(_('Receipt Register'), "Receipt Register", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($fromcust,$from,$to);
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
	    
		$rep->TextCol(0, 1, sql2date($myrow['tran_date']));
		$rep->TextCol(1,2, $myrow['reference']);
		$rep->TextCol(2, 3, $myrow['DebtorName']);
		$rep->TextCol(3, 4, $myrow['mode_of_payment']);
		$rep->AmountCol(4,5, $myrow['ov_amount'],3);
		$rep->AmountCol(5,6, $myrow['ov_gst'],3);
		$rep->AmountCol(6,7, $myrow['ov_roundoff'],3);
		$rep->AmountCol(7, 8, $myrow['ov_gst']+$myrow['ov_amount']+$myrow['ov_roundoff'],3);
		$rep->NewLine(1);
		$k++;
	}
	
    $rep->End();
}

