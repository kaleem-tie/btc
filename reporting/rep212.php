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
$page_security = 'SA_SUPP_OUTSTANDING_REP';

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
include_once($path_to_root . "/includes/db/crm_contacts_db.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//---------------------------------------------------------------------------------

print_creditors_outstanding_report();

function get_transactions($supplier_id, $to)
{
	
	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);

    $sql = "SELECT *,
				(ov_amount + ov_gst + ov_discount + additional_charges + packing_charges + other_charges + freight_cost + freight_tax + additional_tax + packing_tax + other_tax+ov_roundoff) AS TotalAmount,alloc AS settled_amt,
				((type = ".ST_SUPPINVOICE.") AND due_date < '$to') AS OverDue
   			FROM ".TB_PREF."supp_trans
   			WHERE tran_date <= '$todate'
			AND type <> ".ST_SUPPPDC."
    		AND supplier_id = '$supplier_id' 
			AND ov_amount!=0";
    		
			
	$sql .= " AND ABS(ov_amount + ov_gst + ov_discount + additional_charges + packing_charges + other_charges + freight_cost + freight_tax + additional_tax + packing_tax + other_tax+ov_roundoff) - alloc > ".FLOAT_COMP_DELTA." ";  

   $sql .= " ORDER BY tran_date";	
     
    $TransResult = db_query($sql,"No transactions were returned");

    return $TransResult;
}


//---------------------------------------------------------------------------------

function print_creditors_outstanding_report()
{
    	global $path_to_root, $systypes_array;

    	
    	$to          = $_POST['PARAM_0'];
    	$fromsupp    = $_POST['PARAM_1'];
		$reg_type    = $_POST['PARAM_2']; 
	    $currency    = $_POST['PARAM_3'];
		$summary     = $_POST['PARAM_4'];
		$no_zeros    = $_POST['PARAM_5'];
	    $comments    = $_POST['PARAM_6'];
	    $orientation = $_POST['PARAM_7'];
	    $destination = $_POST['PARAM_8'];
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	
	$dec = user_price_dec();
	
	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
    	
	if ($fromsupp == ALL_TEXT)
		$supp = _('All');
	else
		$supp = get_supplier_name($fromsupp);
	
	
	if ($reg_type == 0)
		 $rg_type ="All";
	else if ($reg_type == 1)
		 $rg_type ="Local";
	else if ($reg_type == 2)
		 $rg_type ="Import";
	
	
	
	if ($currency == ALL_TEXT)
	{
		$convert = true;
		$currency = _('Balances in Home currency');
	}
	else
		$convert = false;

	if ($no_zeros) $nozeros = _('Yes');
	else $nozeros = _('No');
	
	
	if ($summary == 0)
		$sum = _("Detailed");
	else
		$sum = _("Summary");
	
	
   if($summary == 0)
   {
   $cols = array(0,50,100,150,190,240,290,345,400,450,510,560);

   $headers = array(_('Inv.Date'), _('Inv.No.'), _('Ref.No.'),  _('Mode'),
   _('Inv.Amount(FC)'), _('Crncy'), _('Inv.Amount(RO)'), _('Settled Amt(RO)'),_('Inv.Balance(RO)'),
   _('Inv.Balance(FC)'),_('Due On'));
   
   $aligns = array('left',	'left',	'left',	'left', 
   'right', 'center','right', 'right', 'right', 'right', 'center');
   


     $params =   array( 0 => $comments,
    				    1 => array('text' => _('End Date'), 'to' => $to),
    				    2 => array('text' => _('Supplier'), 'from' => $supp,   	'to' => ''),
						3 => array('text' => _('Type'), 'from' => $rg_type,'to' => ''),
						4 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						5 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => ''),
						6 => array( 'text' => _('Display Type'),'from' => $sum,'to' => ''));

    $rep = new FrontReport(_('Creditors Outstanding Report'), 
	"DebtorsOutstandingReport", user_pagesize(), 8, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    //$rep->SetHeaderType('Header42');
    $rep->NewPage();
	
	$sql = "SELECT supplier_id, supp_name AS name, curr_code, address, supp_code 
	        FROM ".TB_PREF."suppliers WHERE 1=1";
	        if ($fromsupp != ALL_TEXT)
		    $sql .= " AND supplier_id=".db_escape($fromsupp);
	        if($reg_type == 1){
			$sql .= " AND curr_code='OMR'";
	        }	
            elseif($reg_type == 2){
            $sql .= " AND curr_code!= 'OMR'";
	        }
	$sql .= " ORDER BY supp_name";
	$result = db_query($sql, "The suppliers could not be retrieved");
	
	$grand_total = $grand_total_fc = 0;
	while ($myrow = db_fetch($result))
	{
		
		if (!$convert && $currency != $myrow['curr_code'])
			continue;

	    $rate = $convert ? get_exchange_rate_from_home_currency($myrow['curr_code'], Today()) : 1;
		
		$res = get_transactions($myrow['supplier_id'], $to);
		
		if ($no_zeros && db_num_rows($res) == 0) continue;
		
		if ($supplier != $myrow['name'])
		{
			$m=1;
			if ($supplier != '')
			{
			if ($destination!='1')	{
		     $rep->NewLine();
			}
		     //$rep->NewPage();
			 $rep->Text($ccol+40, str_pad('', 200, '_'));
			
			}
			$rep->SetFont('helvetica', 'B', 9);
			if ($destination!='1')
				$rep->NewLine();
			
			$rep->Text($ccol+40, $myrow['supp_code']);
			
			$rep->Text($ccol+100, _("M/s ").$myrow['name']);
			$rep->SetFont('', '', 0);
			$rep->NewLine();
			
			$m++;
			$supplier = $myrow['name'];
		}
		
		$rep->NewLine();
		$invoice_total = $balance = $balance_fc = $invoice_total_fc = 0;
		
		while ($trans = db_fetch($res))
		{
			
			if ($no_zeros) {
                //if ($show_balance) {
                    if ($trans['TotalAmount'] == 0) continue;
            }
			
			if($trans['type']==0){
				 $inv_type = "JV";
			}
			else if($trans['type']==20){
				 $inv_type = "PI";
			}
			else if($trans['type']==21){
				$inv_type = "PR";
			}
			else if($trans['type']==22){
				if($trans['bank_account']==1)
				$inv_type = "CP";
			    else
				$inv_type = "BP";	
			}
			
			
			$rep->DateCol(0, 1,	$trans['tran_date'], true);
			$rep->TextCol(1, 2,	$inv_type. "  ".$trans['reference']);
			$rep->TextCol(2, 3,	$trans['supp_reference']);
			$rep->TextCol(3, 4,	"");
			
			
			if($myrow['curr_code']!='OMR'){
				if ($trans['TotalAmount'] > 0.0){
					$rep->AmountCol(4, 5, $trans['TotalAmount'], $dec);
				}
			    else
			    {
				$rep->AmountCol(4, 5, $trans['TotalAmount'], $dec);
			    }
			}
			
			$rep->TextCol(5, 6,	$myrow['curr_code']);
			
			$inv_amount_cr = $inv_amount_dr =  0.0;
			
			$inv_amount_fc_cr = $inv_amount_fc_dr =  0.0;
			
			if ($trans['TotalAmount'] > 0.0)
			{
				$inv_amount_cr = round2($trans['TotalAmount'] * $rate, $dec);
				$rep->TextCol(6, 7, number_format2($inv_amount_cr, $dec)." Cr");
				$allocated_amt = round2($trans['settled_amt'] * $rate, $dec);
				
				$inv_amount_cr_fc = round2($trans['TotalAmount'], $dec);
				$allocated_amt_fc = round2($trans['settled_amt'], $dec);
			}
			else
			{
				$inv_amount_dr = round2(abs($trans['TotalAmount']) * $rate, $dec);
				$rep->TextCol(6, 7, number_format2($inv_amount_dr, $dec)." Dr");
				$allocated_amt = round2($trans['settled_amt'] * $rate, $dec) * -1;
				
				$inv_amount_dr_fc = round2(abs($trans['TotalAmount']), $dec);
				$allocated_amt_fc = round2($trans['settled_amt'], $dec) * -1;
			}
			
			$rep->AmountCol(7, 8, $trans['settled_amt'], $dec);
			
			
			if ($trans['TotalAmount'] > 0.0){
				$inv_balance = $inv_amount_cr - $allocated_amt;
				$inv_balance_fc = $inv_amount_cr_fc - $allocated_amt_fc;
			}
			else{	
				$inv_balance = -$inv_amount_dr - $allocated_amt;
				$inv_balance_fc = -$inv_amount_dr_fc - $allocated_amt_fc;
			}
			
			
			if($inv_balance > 0.0){
			$rep->TextCol(8, 9, number_format2($inv_balance, $dec)." Cr");
			$balance += $inv_balance;
		    }
		    else{
			$rep->TextCol(8, 9, number_format2(-$inv_balance, $dec)." Dr");	
			$balance -= -$inv_balance;
		    }
			
			if($myrow['curr_code']!='OMR'){
			 if($inv_balance_fc > 0.0){
			 $rep->TextCol(9, 10, number_format2($inv_balance_fc, $dec)." Cr");
			 $balance_fc += $inv_balance_fc;
		     }
		     else{
			 $rep->TextCol(9, 10, number_format2(-$inv_balance_fc, $dec)." Dr");	
			 $balance_fc -= -$inv_balance_fc;
		     }
		    }
		
			if ($trans['type'] == ST_SUPPINVOICE)
			$rep->DateCol(10, 11,	$trans['due_date'], true);
			
			
			$rep->NewLine();
			
			$invoice_total    = $balance;
			$invoice_total_fc = $balance_fc;
	
		}
         
		 
		
		 
		 $rep->Text($ccol+40, str_pad('', 200, '_'));
       	 $rep->NewLine();
		 $rep->SetFont('helvetica', 'B', 9);
		 $rep->Text($ccol + 400,  $myrow['supp_code']." "._("Total"));
		 
		 if($invoice_total>0.0)	
		 $rep->TextCol(8, 9, number_format2($invoice_total, $dec)." Cr");
	     else
		 $rep->TextCol(8, 9, number_format2(-$invoice_total, $dec)." Dr");	 
	 
	    
	     if($invoice_total_fc>0.0)	
		 $rep->TextCol(9, 10, number_format2($invoice_total_fc, $dec)." Cr");
	     else
		 $rep->TextCol(9, 10, number_format2(-$invoice_total_fc, $dec)." Dr");	 
		 
	 
		 $rep->NewLine(1);
	
		 
		 
		 $grand_total    += $invoice_total;
		 $grand_total_fc += $invoice_total_fc;
	}
	
	  $rep->NewLine(2);
	  $rep->Text($ccol+40, str_pad('', 200, '_'));
	  $rep->NewLine();
	  $rep->SetFont('helvetica', 'B', 9);
	  $rep->Text($ccol + 500,  _("Grand Total"));

      if($grand_total>0.0)	
		 $rep->TextCol(8, 9, number_format2($grand_total, $dec)." Cr");
	  else
		 $rep->TextCol(8, 9, number_format2(-$grand_total, $dec)." Dr");	

     //if($grand_total_fc>0.0)	
		// $rep->TextCol(9, 10, number_format2($grand_total_fc, $dec)." Cr");
	 //else
		 //$rep->TextCol(9, 10, number_format2(-$grand_total_fc, $dec)." Dr");	 
       
  
	  $rep->SetFont('', '', 0);
	  $rep->NewLine();
	
    $rep->NewLine();
    $rep->End();
	}
    else
    {
	   $cols = array(0,60,250,320,380,450,520);

       $headers = array(_('Code'), _('Supplier'), 
       _('Inv.Amount(FC)'), _('O/s Amount(RO)'), _(''),_('Net O/s Amt(RO)'));
   
       $aligns = array('left',	'left',	'right', 'right', 'right', 'right');

        $params =   array( 0 => $comments,
    				    1 => array('text' => _('End Date'), 'to' => $to),
    				    2 => array('text' => _('Supplier'), 'from' => $supp,   	'to' => ''),
						3 => array('text' => _('Type'), 'from' => $rg_type,'to' => ''),
						4 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						5 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => ''),
						6 => array( 'text' => _('Display Type'),'from' => $sum,'to' => ''));


	 $rep = new FrontReport(_('Creditors Outstanding Report'), 
	"DebtorsOutstandingReport", user_pagesize(), 8, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();
	
	
	$sql = "SELECT supplier_id, supp_name AS name, curr_code, address, supp_code 
	        FROM ".TB_PREF."suppliers WHERE 1=1";
	        if ($fromsupp != ALL_TEXT)
		    $sql .= " AND supplier_id=".db_escape($fromsupp);
	        if($reg_type == 1){
			$sql .= " AND curr_code='OMR'";
	        }	
            elseif($reg_type == 2){
            $sql .= " AND curr_code!= 'OMR'";
	        }
	$sql .= " ORDER BY supp_name";
	$result = db_query($sql, "The suppliers could not be retrieved");
	
	$grand_total = $grand_total_fc = 0;
	$supplier = 0;
	while ($myrow = db_fetch($result))
	{
		
		if (!$convert && $currency != $myrow['curr_code'])
			continue;
	    $rate = $convert ? get_exchange_rate_from_home_currency($myrow['curr_code'], Today()) : 1;
		$res = get_transactions($myrow['supplier_id'], $to);
	    if ($no_zeros && db_num_rows($res) == 0) continue;
		
		$rep->NewLine(0, 2, false, $supplier);
		if ($supplier != $myrow['name'])
		{
			$m=1;
			//$rep->TextCol(0, 1,	$myrow['supp_code']);
			//$rep->TextCol(1, 2,	$myrow['name']);
		
			//$rep->NewLine();
			$m++;
			$supplier = $myrow['name'];
		}
		
		
		
		$invoice_total = $balance = $balance_fc = $invoice_total_fc = 0;
		while ($trans = db_fetch($res))
		{
			
			if ($no_zeros) {
                //if ($show_balance) {
                    if ($trans['TotalAmount'] == 0) continue;
            }
			
			$inv_amount_cr = $inv_amount_dr =  0.0;
			$inv_amount_fc_cr = $inv_amount_fc_dr =  0.0;
			if ($trans['TotalAmount'] > 0.0)
			{
				$inv_amount_cr = round2($trans['TotalAmount'] * $rate, $dec);
				$allocated_amt = round2($trans['settled_amt'] * $rate, $dec);
				$inv_amount_cr_fc = round2($trans['TotalAmount'], $dec);
				$allocated_amt_fc = round2($trans['settled_amt'], $dec);
			}
			else
			{
				$inv_amount_dr = round2(abs($trans['TotalAmount']) * $rate, $dec);
				$allocated_amt = round2($trans['settled_amt'] * $rate, $dec) * -1;
				$inv_amount_dr_fc = round2(abs($trans['TotalAmount']), $dec);
				$allocated_amt_fc = round2($trans['settled_amt'], $dec) * -1;
			}
			
			if ($trans['TotalAmount'] > 0.0){
				$inv_balance = $inv_amount_cr - $allocated_amt;
				$inv_balance_fc = $inv_amount_cr_fc - $allocated_amt_fc;
			}
			else{	
				$inv_balance = -$inv_amount_dr - $allocated_amt;
				$inv_balance_fc = -$inv_amount_dr_fc - $allocated_amt_fc;
			}
			
			if($inv_balance > 0.0){
			$balance += $inv_balance;
		    }
		    else{
			$balance -= -$inv_balance;
		    }
			
			if($myrow['curr_code']!='OMR'){
			 if($inv_balance_fc > 0.0){
			 $balance_fc += $inv_balance_fc;
		     }
		     else{
			 $balance_fc -= -$inv_balance_fc;
		     }
		    }
			
			$invoice_total    = $balance;
			$invoice_total_fc = $balance_fc;
			
		}	
		
		
		
       	 
		
		 $rep->TextCol(0, 1,	$myrow['supp_code']);
		 $rep->TextCol(1, 2,	$myrow['name']. "(".$myrow['curr_code'].")");
	    
	     if($invoice_total_fc>0.0)	
		 $rep->TextCol(2, 3, number_format2($invoice_total_fc, $dec)." Cr");
	     else
		 $rep->TextCol(2, 3, number_format2(-$invoice_total_fc, $dec)." Dr");	

         if($invoice_total>0.0)	
		 $rep->TextCol(3, 4, number_format2($invoice_total, $dec)." Cr");
	     else
		 $rep->TextCol(3, 4, number_format2(-$invoice_total, $dec)." Dr");	 	

         $net_os_amt = $invoice_total-$pdc_total;	 
		 
		 if($net_os_amt>0.0)	
		 $rep->TextCol(5, 6, number_format2($net_os_amt, $dec)." Cr");
	     else
		 $rep->TextCol(5, 6, number_format2(-$net_os_amt, $dec)." Dr");	 
		 $rep->NewLine();
		 $grand_total    += $invoice_total;
		 $grand_total_fc += $invoice_total_fc;
	
	}
	
	  $rep->NewLine(2);
	  $rep->Text($ccol+40, str_pad('', 200, '_'));
	  $rep->NewLine();
	  $rep->SetFont('helvetica', 'B', 9);
	  $rep->Text($ccol + 500,  _("Grand Total"));

      if($grand_total>0.0)	
		 $rep->TextCol(5, 6, number_format2($grand_total, $dec)." Cr");
	  else
		 $rep->TextCol(5, 6, number_format2(-$grand_total, $dec)." Dr");	

    
  
	  $rep->SetFont('', '', 0);
	  $rep->NewLine();
	
 
    $rep->Line($rep->row  - 4);
	$rep->NewLine();
	$rep->End();
	   
   }	   

}