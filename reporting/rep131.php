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
$page_security = 'SA_CUST_SA_PDC_SHOW_DOWN_REP';

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

print_statement_accounts_for_audit();

function get_transactions($to,$customer_id=null,$folk=0)
{
	
	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
	
	
	$sql = "SELECT type, reference, tran_date,invoice_type,bank_account,lpo_no,due_date,
	     IF(trans.prep_amount, trans.prep_amount, trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff)
		 AS TotalAmount,trans.alloc as settled_amt,trans.pdc_amt
		FROM ".TB_PREF."debtor_trans trans
		WHERE type IN (".ST_BANKPAYMENT.",".ST_BANKDEPOSIT.",".ST_CUSTCREDIT.",
		".ST_CUSTPAYMENT.",".ST_SALESINVOICE.",".ST_JOURNAL.")
		AND debtor_no = $customer_id 
	    AND trans.ov_amount!=0 AND trans.tran_date <= '$todate'";
			
		$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff) - trans.alloc) > 0";	
			
    
	  if ($folk != 0){
      $sql .= " AND trans.sales_person_id = ".db_escape($folk);
	  }
		
	$sql .= " ORDER BY trans.tran_date";

	return db_query($sql, "The customer transactions could not be retrieved");
}

function get_sales_pdc_transactions($to,$customer_id=null)
{
	
	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
	
	
	$sql = "SELECT trans.type, trans.reference,trans.tran_date,trans.invoice_type,
	        trans.bank_account,trans.lpo_no,trans.due_date,
	        trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff	AS pdc_amount,
		    trans.alloc as settled_amt,trans.pdc_amt,trans.order_,
            trans.pdc_cheque_date,trans.pdc_cheque_no,trans.our_ref_no			
		FROM ".TB_PREF."debtor_trans trans
		WHERE trans.type IN (".ST_CUSTPDC.")
			AND trans.debtor_no = $customer_id 
			AND trans.tran_date <= '$todate'
	        AND trans.ov_amount!=0";
			
		$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount) - trans.alloc) > 0";	
			
		
			
	$sql .= " ORDER BY trans.tran_date";

	return db_query($sql, "The customer transactions could not be retrieved");
}


function get_monthly_sales_invoice_info($customer_id,$folk,$start_date,$end_date)
{
	

	$sql = "SELECT 
	    SUM(trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff - trans.alloc)	AS monthly_inv_amount
		FROM ".TB_PREF."debtor_trans trans

		WHERE type IN (".ST_SALESINVOICE.",".ST_JOURNAL.")
			AND debtor_no = $customer_id 
	        AND trans.ov_amount!=0 
			AND trans.tran_date >= '$start_date' AND trans.tran_date <= '$end_date'";
			
		$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount) - trans.alloc) > 0";	
			
    
	if ($folk != 0){
      $sql .= " AND trans.sales_person_id = ".db_escape($folk);
	}
		
	$sql .= " ORDER BY trans.tran_date";
	
	
	
	$result= db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	$monthly_inv_amount= $row[0]==""?0:$row[0];
	return $monthly_inv_amount;

		
}

function get_monthly_unadjusted_credit_info($customer_id,$folk,$to)
{
	
	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
	

	$sql = "SELECT 
	    SUM(trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff - trans.alloc)	AS monthly_inv_amount
		FROM ".TB_PREF."debtor_trans trans

		WHERE trans.type IN (".ST_BANKPAYMENT.",".ST_BANKDEPOSIT.",".ST_CUSTCREDIT.",
		".ST_CUSTPAYMENT.")
			AND debtor_no = $customer_id 
	        AND trans.ov_amount!=0 
			AND trans.tran_date <= '$todate'";
			
		$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount) - trans.alloc) > 0";	
			
    
	if ($folk != 0){
      $sql .= " AND trans.sales_person_id = ".db_escape($folk);
	}
		
	$sql .= " ORDER BY trans.tran_date";
	
	
	$result= db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	$monthly_unadjusted_amount= $row[0]==""?0:$row[0];
	return $monthly_unadjusted_amount;

		
}

//---------------------------------------------------------------------------------

function print_statement_accounts_for_audit()
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
	
	
   $cols = array(0,50,120,170,240,300,370,450,520);

   $headers = array(_('Inv. Date'), _('Inv. No.'), _('LPO/Ref'), _('Inv. Amount'), _('Settled Amt'),          _('Inv. Balance'), _('Balance'));
	
   $aligns = array('left',	'left',	'left',	'right', 'right','right', 'right', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('End Date'), 'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						4 => array('text' => _('Sales Folk'), 'from' => $salesfolk,	'to' => ''));

    $rep = new FrontReport(_('Statement of Accounts-PDC Showing Down'), 
	"StatementofAccountsPDCShowingDown", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->SetHeaderType('Header40');
    $rep->NewPage();
	
	$sql = "SELECT debtor_no, name, curr_code,address,cust_code FROM ".TB_PREF."debtors_master";
	if ($fromcust != ALL_TEXT)
		$sql .= " WHERE debtor_no=".db_escape($fromcust);
	$sql .= " ORDER BY name";
	$result = db_query($sql, "The customers could not be retrieved");
	
	
	while ($myrow = db_fetch($result))
	{
		
		if (!$convert && $currency != $myrow['curr_code'])
			continue;

	   if ($convert) $rate = get_exchange_rate_from_home_currency($myrow['curr_code'], $to);
		else $rate = 1.0;
		
		$res = get_transactions($to, $myrow['debtor_no'],$folk);
		
		if ($no_zeros && db_num_rows($res) == 0) continue;
		
		if ($debtor != $myrow['name'])
		{
			$m=1;
			if ($debtor != '')
			{
			if ($destination!='1')	{
		     $rep->NewLine();
			}
		     $rep->NewPage();
			}
			$rep->SetFont('helvetica', 'B', 9);
			if ($destination!='1')
				$rep->NewLine();
			
			$rep->Text($ccol+40, $myrow['cust_code']);
			
			$rep->Text($ccol+100, _("M/s ").$myrow['name']);
			$rep->SetFont('', '', 0);
			$rep->NewLine();
			if ($destination!='1')
			$rep->TextWrapLines($ccol+100, $icol - $ccol-100, $myrow['address']);
			$rep->NewLine();
			$contacts = get_branch_contacts($myrow['branch_code'], 'order', $myrow['debtor_no'], true);
			$rep->Text($ccol+100, _('Phone : ').$contacts['phone']);
			$rep->Text($ccol+250, _('Fax : ').$contacts['fax']);
			$rep->NewLine(2);
			$rep->Text($ccol+40, _('Dear Sir, '));
			$rep->NewLine(2);
			
			$rep->SetFont('helvetica', 'B', 9);
			$rep->Text($ccol+65, _('Ref : OutStanding Bills '));
			$rep->SetFont('', '', 0);
			
			$rep->NewLine(2);
			$rep->Text($ccol+65, _('The following bills are outstanding as on ').$to);
			
			$debtor = $myrow['name'];
			
			
			$rep->NewLine(1);
			
			$rep->Line($rep->row - 2);
			$rep->NewLine(2);
			
			$rep->SetFont('helvetica', 'B', 9);
			
			$rep->Text($ccol + 420,  _("Amounts In RO "));
			
			$rep->NewLine();
			
			$rep->TextCol(0, 1, "Inv. Date");
			$rep->TextCol(1, 2, "Inv. No.");
			$rep->TextCol(2, 3, "LPO/Ref");
			$rep->TextCol(3, 4, "");
			$rep->TextCol(4, 5, "Inv. Amount");
			$rep->TextCol(5, 6,	"Settled Amt");
			$rep->TextCol(6, 7,	"Inv. Balance");
			$rep->TextCol(7, 8, "Balance");
			
			$rep->SetFont('', '', 0);
			
			$rep->NewLine();
			$rep->Line($rep->row - 2);
			$rep->NewLine();
			
			$m++;
			
		}
		
		$rep->NewLine();
		
		$inv_amt_total = $settled_amt_total = $inv_bal_total = $balance_total = 0;
		
		$balance =  0;
		
		while ($trans = db_fetch($res))
		{
			
			if ($no_zeros) {
                //if ($show_balance) {
                    if ($trans['TotalAmount'] == 0) continue;
            }
			
			$inv_type = "";
			
			
			//$inv_balance = $trans['inv_amount']-$trans['settled_amt'];
			
			$rep->DateCol(0, 1,	$trans['tran_date'], true);
			$rep->TextCol(1, 2,	$inv_type. "  ".$trans['reference']);
			$rep->TextCol(2, 3,	$trans['lpo_no']);
			if ($trans['type'] == ST_SALESINVOICE)
			$rep->TextCol(3, 4,	"");
		
		
		    $inv_amount_dr = $inv_amount_cr = 0.0;
			if ($trans['type'] == ST_CUSTCREDIT || $trans['type'] == ST_CUSTPAYMENT || 
			$trans['type'] == ST_BANKDEPOSIT || $trans['type'] == ST_CUSTPDC)
				$trans['TotalAmount'] *= -1;
				
			

            if ($trans['TotalAmount'] > 0.0)
			{
				$inv_amount_dr = round2($trans['TotalAmount'] * $rate, $dec);
				$rep->TextCol(4, 5, number_format2($inv_amount_dr, $dec)." Dr");
				$allocated_amt = round2($trans['settled_amt'] * $rate, $dec);
				$inv_amt_total += $inv_amount_dr;
			}
			else
			{
				$inv_amount_cr = round2(abs($trans['TotalAmount']) * $rate, $dec);
				$rep->TextCol(4, 5, number_format2($inv_amount_cr, $dec)." Cr");
				$allocated_amt = round2($trans['settled_amt'] * $rate, $dec) * -1;
				$inv_amt_total -= $inv_amount_cr;
			}				
				
				
			$rep->AmountCol(5, 6, $trans['settled_amt'], $dec);	


            if (($trans['type'] == ST_JOURNAL && $inv_amount_dr) || $trans['type'] == ST_SALESINVOICE || $trans['type'] == ST_BANKPAYMENT)
				$inv_balance = $inv_amount_dr - $allocated_amt;
		    else	
				$inv_balance = -$inv_amount_cr - $allocated_amt;
			
		    if($inv_balance > 0.0){
			$rep->TextCol(6, 7, number_format2($inv_balance, $dec)." Dr");
			$balance += $inv_balance;
		    }
		    else{
			$rep->TextCol(6, 7, number_format2(-$inv_balance, $dec)." Cr");	
			$balance -= -$inv_balance;
		    }

		
             if($balance > 0.0){
              $rep->TextCol(7, 8, number_format2($balance, $dec)." Dr");
		     }
            else{
            $rep->TextCol(7, 8, number_format2(-$balance, $dec)." Cr");	
            }				
			
			
			
			
			$rep->NewLine();
			
			$settled_amt_total += $trans['settled_amt'];
			
			$inv_bal_total = $balance;
			
		}
         
		 $rep->NewLine();
		 $rep->Text($ccol+40, str_pad('', 120, '_'));
       	 $rep->NewLine();
		 
		 $rep->SetFont('helvetica', 'B', 9);
		 $rep->Text($ccol + 50,  _("Closing Balance :  ").$myrow['cust_code']);
		 $rep->AmountCol(4, 5, $inv_amt_total, $dec);
		 $rep->AmountCol(5, 6, $settled_amt_total, $dec);
		 $rep->AmountCol(6, 7, $inv_bal_total, $dec);
		 
		 
		  if($inv_bal_total > 0.0){
           $rep->TextCol(7, 8, number_format2($inv_bal_total, $dec)." Dr");
		  }
          else{
            $rep->TextCol(7, 8, number_format2(-$inv_bal_total, $dec)." Cr");	
          }	
		 $rep->SetFont('', '', 0);
		 $rep->NewLine();
		 
		 //PDC INFo
		 $rep->NewLine(1);
		 $rep->Text($ccol + 40,  _("PDC's"));
		 $rep->NewLine();
		 $rep->Text($ccol+40, str_pad('', 140, '_'));
		 $rep->NewLine();
		 $rep->Text($ccol + 40,   _("Doc Date"));
		 $rep->Text($ccol + 100,  _("Doc No."));
		 $rep->Text($ccol + 200,  _("Chq No."));
		 $rep->Text($ccol + 290,  _("Chq Date "));
		 $rep->Text($ccol + 360,  _("Ref No."));
		 $rep->Text($ccol + 450,  _("Ref Date"));
		 $rep->TextCol(7, 8, _("Amount"));
		 $rep->Text($ccol+40, str_pad('', 140, '_'));
		 $rep->NewLine();
	     $rep->SetFont('', '', 0);
		 
		 $pdc_res = get_sales_pdc_transactions($to, $myrow['debtor_no']);
		 
		 $pdc_total = $net_balance_total = 0;
		  while ($pdc = db_fetch($pdc_res))
		{
			
		 $rep->Text($ccol + 40,   sql2date($pdc['tran_date']));
		 $rep->Text($ccol + 100,  $pdc['reference']);
		 $rep->Text($ccol + 200,  $pdc['pdc_cheque_no']);
		 $rep->Text($ccol + 290,  sql2date($pdc['pdc_cheque_date']));
		 $rep->Text($ccol + 360,  $pdc['our_ref_no']);
		 $rep->Text($ccol + 450,  sql2date($pdc['tran_date']));
		 $rep->AmountCol(7, 8, $pdc['pdc_amount'],$dec);
		 $rep->NewLine();
		 $pdc_total += $pdc['pdc_amount'];
		 
		}	
		
		 $rep->Text($ccol+480, str_pad('', 20, '_'));
		 $rep->NewLine();
		 $rep->SetFont('helvetica', 'B', 9);
		 $rep->Text($ccol + 40,  _("Post dated cheques total"));
		 $rep->TextCol(7, 8,  number_format2($pdc_total,$dec). " Cr" );
		 $rep->SetFont('', '', 0);
		 $rep->NewLine();
		 $rep->Text($ccol+480, str_pad('', 20, '_'));
		 $rep->NewLine();
		 $net_balance_total = $inv_bal_total - $pdc_total;
		 $rep->SetFont('helvetica', 'B', 9);
		 $rep->Text($ccol + 40,  _("Net Balance"));
		 
		 if ($inv_bal_total<0.0)
		 $rep->TextCol(7, 8,  number_format2(-$net_balance_total,$dec). " Cr" );
	     else
		 $rep->TextCol(7, 8,  number_format2($net_balance_total,$dec). " Dr" );	 
	 
		 $rep->SetFont('', '', 0);
		 $rep->NewLine(1);
		 
		 
		 
	   $date = date2sql($to);
       $mo  = date('m', strtotime($date));
	   $yr   = date('Y', strtotime($date));
	   $da = 1;
	   $cur_month = strftime('%b',mktime(0,0,0,$mo,$da,$yr));
	   $last_month = strftime('%b',mktime(0,0,0,$mo-1,$da,$yr));
	   $last_2_month = strftime('%b',mktime(0,0,0,$mo-2,$da,$yr));
	   $last_3_month = strftime('%b',mktime(0,0,0,$mo-3,$da,$yr));
	   $last_4_month = strftime('%b',mktime(0,0,0,$mo-4,$da,$yr));
	   
	    $rep->Text($ccol+40, str_pad('', 120, '_')); 
		$rep->NewLine(); 
		$rep->Text($ccol + 40,   $cur_month);
		$rep->Text($ccol + 110,  $last_month);
		$rep->Text($ccol + 200,  $last_2_month);
		$rep->Text($ccol + 280,  $last_3_month);
		$rep->Text($ccol + 350,  $last_4_month);
		$rep->Text($ccol + 420,  _("UnAdjusted (Cr)"));
	    $rep->TextCol(7, 8,   " Net Balance" );
		$rep->Text($ccol+40, str_pad('', 120, '_')); 
		
		
	
	
	//current month inv value
	$cur_first_date = date('Y-m-d',mktime(0,0,0,$mo,1,$yr));
	$cur_month = (int) (substr($cur_first_date, 5, 2));
	if (in_array($cur_month, array(1,3,5,7,8,10,12))){
		$last_day = 31;
	}
	else if (in_array($cur_month, array(4,6,9,11))){
		$last_day = 30;
	}
	else if (in_array($cur_month, array(2))){
		$last_day = 29;
	}
	$cur_last_date = date('Y-m-d',mktime(0,0,0,$mo,$last_day,$yr));
	$cur_month_inv_value = get_monthly_sales_invoice_info($myrow['debtor_no'],$folk,$cur_first_date,$cur_last_date);
	
	//last month inv value
	$last_mn_first_date = date('Y-m-d',mktime(0,0,0,$mo-1,1,$yr));
	$last_mn_month = (int) (substr($last_mn_first_date, 5, 2));
	if (in_array($last_mn_month, array(1,3,5,7,8,10,12))){
		$last_mn_day = 31;
	}
	else if (in_array($last_mn_month, array(4,6,9,11))){
		$last_mn_day = 30;
	}
	else if (in_array($last_mn_month, array(2))){
		$last_mn_day = 29;
	}
	$last_mn_last_date = date('Y-m-d',mktime(0,0,0,$mo-1,$last_mn_day,$yr));
	$last_mn_inv_value = get_monthly_sales_invoice_info($myrow['debtor_no'],$folk,$last_mn_first_date,$last_mn_last_date);
	
	//last 2nd month inv value
	$last_2mn_first_date = date('Y-m-d',mktime(0,0,0,$mo-2,1,$yr));
	$last_2mn_month = (int) (substr($last_2mn_first_date, 5, 2));
	
	if (in_array($last_2mn_month, array(1,3,5,7,8,10,12))){
		$last_2mn_day = 31;
	}
	else if (in_array($last_2mn_month, array(4,6,9,11))){
		$last_2mn_day = 30;
	}
	else if (in_array($last_2mn_month, array(2))){
		$last_2mn_day = 29;
	}
	$last_2mn_last_date = date('Y-m-d',mktime(0,0,0,$mo-2,$last_2mn_day,$yr));
	$last_2mn_inv_value = get_monthly_sales_invoice_info($myrow['debtor_no'],$folk,$last_2mn_first_date,$last_2mn_last_date);
	
	//last 3nd month inv value
	$last_3_mn_first_date = date('Y-m-d',mktime(0,0,0,$mo-3,1,$yr));
	$last_3_mn_month = (int) (substr($last_3_mn_first_date, 5, 2));
	if (in_array($last_3_mn_month, array(1,3,5,7,8,10,12))){
		$last_3_mn_day = 31;
	}
	else if (in_array($last_3_mn_month, array(4,6,9,11))){
		$last_3_mn_day = 30;
	}
	else if (in_array($last_3_mn_month, array(2))){
		$last_3_mn_day = 29;
	}
	$last_3_mn_last_date = date('Y-m-d',mktime(0,0,0,$mo-3,$last_3_mn_day,$yr));
	$last_3_mn_inv_value = get_monthly_sales_invoice_info($myrow['debtor_no'],$folk,$last_3_mn_first_date,$last_3_mn_last_date);
	
	
	//last 4th month inv value
	$last_4_mn_first_date = date('Y-m-d',mktime(0,0,0,$mo-4,1,$yr));
	$last_4_mn_month = (int) (substr($last_4_mn_first_date, 5, 2));
	if (in_array($last_4_mn_month, array(1,3,5,7,8,10,12))){
		$last_4_mn_day = 31;
	}
	else if (in_array($last_4_mn_month, array(4,6,9,11))){
		$last_4_mn_day = 30;
	}
	else if (in_array($last_4_mn_month, array(2))){
		$last_4_mn_day = 29;
	}
	$last_4_mn_last_date = date('Y-m-d',mktime(0,0,0,$mo-4,$last_4_mn_day,$yr));
	$last_4_mn_inv_value = get_monthly_sales_invoice_info($myrow['debtor_no'],$folk,$last_4_mn_first_date,$last_4_mn_last_date);
	
	
	
	$unadjusted_cr_value = get_monthly_unadjusted_credit_info($myrow['debtor_no'],$folk,$to);
	
	$cur_net_balance = $cur_month_inv_value+$last_mn_inv_value+$last_2mn_inv_value+$last_3_mn_inv_value+$last_4_mn_inv_value-$unadjusted_cr_value;
	$rep->NewLine();
	$rep->Text($ccol + 40,  number_format2($cur_month_inv_value,$dec));
	$rep->Text($ccol + 110,  number_format2($last_mn_inv_value,$dec));
	$rep->Text($ccol + 200,  number_format2($last_2mn_inv_value,$dec));
	$rep->Text($ccol + 280,  number_format2($last_3_mn_inv_value,$dec));
	$rep->Text($ccol + 350,  number_format2($last_4_mn_inv_value,$dec));
	$rep->Text($ccol + 420,  number_format2($unadjusted_cr_value,$dec));
	$rep->AmountCol(7, 8, $cur_net_balance,$dec);
	$rep->NewLine();
	$rep->Text($ccol+40, str_pad('', 120, '_')); 
		
	
	$rep->NewLine(1);
	$rep->Text($ccol+40, _("Payment against overdue bills may please be settlled immediately on receipt of this statement ") );
	
	$rep->NewLine(3);
	$rep->Text($ccol+40, _("Thanking You") );
	$rep->NewLine();
	$rep->SetFont('helvetica', 'B', 9);
	$rep->Text($ccol+40 , _("For ").$rep->company['coy_name']);
	$rep->SetFont('', '', 0);
	
	
	$rep->NewLine(4);
	
	

	$rep->Text($ccol+40, _("Accountant "));
	
	$rep->NewLine(3);
	$rep->Text($ccol+40, _("Note 1: In case the bills have already been settled, please ignore this statement and"));
	$rep->NewLine();
	$rep->Text($ccol+62, _("let us have the payment details."));
	$rep->NewLine();
	$rep->Text($ccol+62, _("2: In case of any discrepancies, please inform us immediately"));
	$rep->NewLine();
    $rep->Text($ccol+40, str_pad('', 120, '_'));
    $rep->NewLine();
	
		
		
	}
	
	
	
	
    $rep->NewLine();
    $rep->End();

}