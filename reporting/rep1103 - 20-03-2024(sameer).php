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
$page_security = 'SA_DEB_OUT_REP';

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

//---------------------------------------------------------------------------------

print_debtors_outstanding_report();



function get_transactions($to,$customer_id=null,$folk=0)
{
	
	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
	
	
	$sql = "SELECT trans.type, trans.reference,trans.tran_date,trans.invoice_type,
	        trans.bank_account,trans.lpo_no,trans.due_date,trans.pdc_amt,trans.order_ ,";
			
		$sql .=	"SUM(IF(trans.type = ".ST_SALESINVOICE." OR (trans.type IN (".ST_JOURNAL." , ".ST_BANKPAYMENT.") AND trans.ov_amount>0), 1, -1) *
			(IF(trans.prep_amount, trans.prep_amount, abs(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff)) - abs(trans.alloc))) AS OutStanding
		FROM ".TB_PREF."debtor_trans trans
		WHERE trans.type IN (".ST_JOURNAL.",".ST_BANKPAYMENT.",".ST_BANKDEPOSIT.",
		".ST_CUSTCREDIT.",".ST_CUSTPAYMENT.",".ST_SALESINVOICE.")
			AND trans.debtor_no = $customer_id 
			AND trans.tran_date <= '$todate'
	        AND trans.ov_amount!=0";
			
		$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff) - trans.alloc) > 0";	
			
    
	if ($folk != 0){
      $sql .= " AND trans.sales_person_id = ".db_escape($folk);
	}
		
	$sql .= " GROUP BY debtor_no ORDER BY trans.tran_date";
	

	return db_query($sql, "The customer transactions could not be retrieved");
}


function get_pdc_transactions($to,$customer_id=null)
{
	
	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
	
	
	$sql = "SELECT trans.type, trans.reference,trans.tran_date,trans.invoice_type,
	        trans.bank_account,trans.lpo_no,trans.due_date,trans.pdc_amt,trans.order_ ,
			SUM(trans.ov_amount) As pdc_amount
		FROM ".TB_PREF."debtor_trans trans
		WHERE trans.type IN (".ST_CUSTPDC.")
			AND trans.debtor_no = $customer_id
            AND trans.tran_date <= '$todate'			
	        AND trans.ov_amount!=0";
			
		$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount) -  trans.alloc) > 0";	
			
	$sql .= " GROUP BY debtor_no ORDER BY trans.tran_date";
	

	return db_query($sql, "The customer transactions could not be retrieved");
}

function get_sales_order_location($order_)
{
    $sql="select from_stk_loc from ".TB_PREF."sales_orders where order_no=".db_escape($order_);
	//display_error($sql);die;
    
    $res=db_query($sql,"No transactions were returned");
    
    $row=db_fetch_row($res);
    
    return $row[0];
}

//---------------------------------------------------------------------------------

function print_debtors_outstanding_report()
{
    	global $path_to_root, $systypes_array;

    	
    	$to          = $_POST['PARAM_0'];
    	$fromcust    = $_POST['PARAM_1'];
		$folk        = $_POST['PARAM_2']; 
	    $currency    = $_POST['PARAM_3'];
		$no_zeros    = $_POST['PARAM_4'];
    	$orientation = $_POST['PARAM_5'];
	    $destination = $_POST['PARAM_6'];
		
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
	
	
   
   $cols = array(0,60,200,270,340,430,500);

   $headers = array(_('Code'), _('Customer'), 
   _('Inv.Amount(FC)'), _('O/s Amount(RO)'), _('PDC Amt(RO)'),_('Net O/s Amt(RO)'));
   
   $aligns = array('left',	'left',	'right', 'right', 'right', 'right');
   


    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						4 => array('text' => _('Sales Folk'), 'from' => $salesfolk,	'to' => ''));

    $rep = new FrontReport(_('Debtors Outstanding Report - Summary'), 
	"DebtorsOutstandingReportSummary", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    //$rep->SetHeaderType('Header42');
    $rep->NewPage();
	
	$sql = "SELECT debtor_no, name, curr_code,address,cust_code FROM ".TB_PREF."debtors_master";
	if ($fromcust != ALL_TEXT)
		$sql .= " WHERE debtor_no=".db_escape($fromcust);
	$sql .= " GROUP BY ".TB_PREF."debtors_master.debtor_no ORDER BY name";
	$result = db_query($sql, "The customers could not be retrieved");
	
	$grand_inv_total = $grand_pdc_total = $grand_os_total = 0;
	while ($myrow = db_fetch($result))
    {
        if (!$convert && $currency != $myrow['curr_code']) continue;

       $rate = $convert ? get_exchange_rate_from_home_currency($myrow['curr_code'], Today()) : 1;
        
		 $res = get_transactions($to, $myrow['debtor_no'],$folk);
		
		if ($no_zeros && db_num_rows($res) == 0) continue;
        

        $invoice_total = 0;
        while ($trans = db_fetch($res)) //Detail starts here
        {
			
			if ($no_zeros) {
                    if ($trans['OutStanding'] == 0) continue;
            }
			
		   $invoice_total += $trans['OutStanding'];
		   
        }
		
		$pdc_res = get_pdc_transactions($to, $myrow['debtor_no']);

        $pdc_total = 0;
        while ($pdc = db_fetch($pdc_res)) //Detail starts here
        {
			
		   
		   $pdc_total += $pdc['pdc_amount'];
        }

		
		$rep->TextCol(0, 1, $myrow['cust_code']);
        $rep->TextCol(1, 2, $myrow['name']);
        $rep->AmountCol(2, 3, "", $dec);
		
		if($invoice_total>0.0)	
		$rep->TextCol(3, 4, number_format2($invoice_total, $dec)." Dr");
	    else
		$rep->TextCol(3, 4, number_format2(-$invoice_total, $dec)." Cr");	
	
		$rep->TextCol(4, 5, number_format2($pdc_total, $dec)." Cr");
		
		$net_os_amt = $invoice_total-$pdc_total;
		
		if($net_os_amt>0.0)	
		$rep->TextCol(5, 6, number_format2($net_os_amt, $dec)." Dr");
	    else
		$rep->TextCol(5, 6, number_format2(-$net_os_amt, $dec)." Cr");
		
        
		
        $rep->NewLine();
		
		$grand_inv_total += $invoice_total;
		$grand_pdc_total += $pdc_total;

    }

    $rep->Line($rep->row + 4);
    $rep->NewLine();
    $rep->fontSize += 2;
    $rep->TextCol(0, 3, _('Grand Total'));
    $rep->fontSize -= 2;
	
	
	if($grand_inv_total>0.0)	
	$rep->TextCol(3, 4, number_format2($grand_inv_total, $dec)." Dr");
	else
	$rep->TextCol(3, 4, number_format2(-$grand_inv_total, $dec)." Cr");	
   
    $net_os_amt_total = $grand_inv_total-$grand_pdc_total;
   
    if($net_os_amt_total>0.0)	
    $rep->TextCol(5, 6, number_format2($net_os_amt_total, $dec)." Dr");
	else
	$rep->TextCol(5, 6, number_format2(-$net_os_amt_total, $dec)." Cr");
    
  
	
    $rep->Line($rep->row - 6, 1);
    $rep->NewLine();
    $rep->End();

}