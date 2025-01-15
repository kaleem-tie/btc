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
$page_security = 'SA_CUST_SA_PRINT_CONFIRM_REP';

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

    $rep = new FrontReport(_('Statement of Accounts - Balance Confirmation for the Purpose of Audit'), "StatementofAccountsBalanceConfirmationforthePurposeofAudit", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->SetHeaderType('Header42');
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
			$rep->Text($ccol+40, _('Sub: BALANCE CONFIRMATION FOR THE PURPOSE OF AUDIT '));
			$rep->SetFont('', '', 0);
			
			$rep->NewLine(2);
			$rep->Text($ccol+40, _('Our auditors are conducting an audit of financial statements. To this regard, please confirm the '));
			$rep->NewLine();
			$rep->Text($ccol+40, _('following bills outstanding as on ').$to);
			
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
			$rep->TextCol(3, 4, "Due On");
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
		$closing_bal_total = $balance = $grand_balance_total = 0;
		$pdc_total = 0;
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
			$rep->DateCol(3, 4,	$trans['due_date'], true);
		
		    
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
			
			$closing_bal_total = $balance;
			
			$pdc_total += $trans['pdc_amt'];
		}
         
		 $rep->NewLine();
		 $rep->Text($ccol+40, str_pad('', 120, '_'));
       	 $rep->NewLine();
		 
		 $net_balance = $closing_bal_total-$pdc_total;
		 
		 $rep->SetFont('helvetica', 'B', 9);
		 $rep->Text($ccol + 300,  _("Closing Balance :  ").$myrow['cust_code']);
		 
		 if($closing_bal_total > 0.0){
           $rep->TextCol(7, 8, number_format2($closing_bal_total, $dec)." Dr");
		  }
          else{
            $rep->TextCol(7, 8, number_format2(-$closing_bal_total, $dec)." Cr");	
          }	
		 
		 
		 //$rep->TextCol(7, 8, number_format2($closing_bal_total, $dec)." Dr");
		 $rep->NewLine();
		 $rep->Text($ccol + 300,  _("Post dated cheques if any "));
		 $rep->TextCol(7, 8, number_format2($pdc_total, $dec)." Cr");
		 $rep->NewLine();
		 $rep->Text($ccol + 300,  _("Net Balance  "));
		 
		  if($net_balance > 0.0){
           $rep->TextCol(7, 8, number_format2($net_balance, $dec)." Dr");
		  }
          else{
            $rep->TextCol(7, 8, number_format2(-$net_balance, $dec)." Cr");	
          }	
		 
		 //$rep->TextCol(7, 8, number_format2($net_balance, $dec)." Dr");
		 $rep->SetFont('', '', 0);
		 $rep->NewLine();
		 $rep->Text($ccol+40, str_pad('', 120, '_'));
		
	
	$rep->NewLine(2);
	$rep->Text($ccol+40, _("Kindly confirm the above by return fax to our fax no. (968) 750193 . Any discrepancies in this statement ") );
	$rep->NewLine();
	$rep->Text($ccol+40, _("statement, may please be reported to us immediately."));
	$rep->NewLine(3);
	$rep->Text($ccol+40, _("Thanking You") );
	$rep->NewLine();
	$rep->SetFont('helvetica', 'B', 9);
	$rep->Text($ccol+40 , _("For ").$rep->company['coy_name']);
	$rep->SetFont('', '', 0);
	
	
	$rep->NewLine(4);
	$rep->Text($ccol+40, str_pad('', 120, '_'));
	$rep->NewLine(2);
	$rep->SetFont('helvetica', 'B', 9);
	$rep->Text($ccol+40 , _("To : ").$rep->company['coy_name']);
	$rep->SetFont('', '', 0);
	$rep->NewLine(2);
	$rep->Text($ccol+40, _("The amount due to you of RO .................................. agrees/does not agree with our records as on ").$to );
	
	$rep->NewLine(3);
	$rep->Text($ccol+40, _("Signature"));
	$rep->Text($ccol+240, _("Company Seal"));
	$rep->Text($ccol+440, _("Date :"));
	
	}
	
    $rep->NewLine();
    $rep->End();

}