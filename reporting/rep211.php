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
$page_security = 'SA_SUPP_STATEMENT_ACC_REP';

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

print_statement_accounts_for_creditors();



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

    //display_error($sql);  
     
    $TransResult = db_query($sql,"No transactions were returned");

    return $TransResult;
}


function get_purchases_pdc_transactions($supplier_id, $to)
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
			AND type IN (".ST_SUPPPDC.") 
    		AND supplier_id = '$supplier_id' 
			AND ov_amount!=0";
    		
			
	$sql .= " AND ABS(ov_amount + ov_gst + ov_discount + additional_charges + packing_charges + other_charges + freight_cost + freight_tax + additional_tax + packing_tax + other_tax+ov_roundoff) - alloc > ".FLOAT_COMP_DELTA." ";  

   $sql .= " ORDER BY tran_date";	

    //display_error($sql);  
     
    $TransResult = db_query($sql,"No transactions were returned");

    return $TransResult;
}



function get_monthly_purchases_invoice_info($supplier_id,$start_date,$end_date)
{
	

	$sql = "SELECT SUM(IF(trans.type = ".ST_SUPPINVOICE.", 1, IF(trans.type = ".ST_JOURNAL." AND trans.ov_amount>0, 1, -1)) * (abs(trans.ov_amount + trans.ov_gst + trans.ov_discount + trans.ov_discount+trans.freight_cost+trans.additional_charges+trans.packing_charges+trans.other_charges+trans.freight_tax+trans.additional_tax+trans.packing_tax+trans.other_tax+trans.ov_roundoff) - abs(trans.alloc))) AS  monthly_inv_amount
		FROM ".TB_PREF."supp_trans trans
		WHERE type IN (".ST_SUPPINVOICE.",".ST_JOURNAL.")
			AND supplier_id = $supplier_id 
	        AND trans.ov_amount!=0 
			AND trans.tran_date >= '$start_date' AND trans.tran_date <= '$end_date'";
			
		$sql .= " AND ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount + trans.ov_discount+trans.freight_cost+trans.additional_charges+trans.packing_charges+trans.other_charges+trans.freight_tax+trans.additional_tax+trans.packing_tax+trans.other_tax+trans.ov_roundoff) - trans.alloc > ".FLOAT_COMP_DELTA." ";
			
		
	$sql .= " ORDER BY trans.tran_date";
	
	
	
	$result= db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	$monthly_inv_amount= $row[0]==""?0:$row[0];
	return $monthly_inv_amount;

		
}



function get_monthly_unadjusted_debit_info($customer_id,$end_date)
{
	
	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
	

	$sql = "SELECT 
	    SUM(trans.ov_amount + trans.ov_gst + trans.ov_discount + trans.additional_charges + trans.packing_charges + trans.other_charges + trans.freight_cost + trans.freight_tax + trans.additional_tax  + trans.packing_tax + trans.other_tax + trans.ov_roundoff - trans.alloc)	AS monthly_inv_amount
		FROM ".TB_PREF."supp_trans trans
		WHERE trans.type IN (".ST_BANKPAYMENT.",".ST_BANKDEPOSIT.",".ST_SUPPCREDIT.",
		".ST_SUPPAYMENT.")
			AND supplier_id = $customer_id 
	        AND trans.ov_amount!=0 
			AND trans.tran_date <= '$todate'";
			
		$sql .= " AND ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount + trans.ov_discount+trans.freight_cost+trans.additional_charges+trans.packing_charges+trans.other_charges+trans.freight_tax+trans.additional_tax+trans.packing_tax+trans.other_tax+trans.ov_roundoff) - trans.alloc > ".FLOAT_COMP_DELTA." ";
			
	$sql .= " ORDER BY trans.tran_date";
	
	
	$result= db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	$monthly_unadjusted_amount= $row[0]==""?0:$row[0];
	return $monthly_unadjusted_amount;
}


//---------------------------------------------------------------------------------

function print_statement_accounts_for_creditors()
{
    	global $path_to_root, $systypes_array;

    	$to          = $_POST['PARAM_0'];
    	$fromsupp    = $_POST['PARAM_1'];
		$reg_type    = $_POST['PARAM_2']; 
	    $currency    = $_POST['PARAM_3'];
		$no_zeros    = $_POST['PARAM_4'];
	    $comments    = $_POST['PARAM_5'];
	    $orientation = $_POST['PARAM_6'];
	    $destination = $_POST['PARAM_7'];
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
	
	$dec = user_price_dec();
	
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
	
	
   $cols = array(0,50,120,170,240,300,370,450,520);

   $headers = array(_('Inv. Date'), _('Inv. No.'), _('LPO/Ref'), _('Inv. Amount'), _('Settled Amt'),          _('Inv. Balance'), _('Balance'));
	
   $aligns = array('left',	'left',	'left',	'right', 'right','right', 'right', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('End Date'), 'to' => $to),
    				    2 => array('text' => _('Supplier'), 'from' => $supp,   	'to' => ''),
						3 => array('text' => _('Type'), 'from' => $rg_type,'to' => ''),
						4 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						5 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => ''));

    $rep = new FrontReport(_('Statement of Accounts of Creditors'), 
	"Statement of Accounts of Creditors", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->SetHeaderType('Header40');
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
		     $rep->NewPage();
			}
			$rep->SetFont('helvetica', 'B', 9);
			if ($destination!='1')
				$rep->NewLine();
			
			$rep->Text($ccol+40, $myrow['supp_code']);
			
			$rep->Text($ccol+100, _("M/s ").$myrow['name']);
			$rep->SetFont('', '', 0);
			$rep->NewLine();
			if ($destination!='1')
			$rep->TextWrapLines($ccol+100, $icol - $ccol-100, $myrow['address']);
			$contacts = get_supplier_contacts($myrow['supplier_id']);
			$rep->Text($ccol+100, _('Phone : ').$contacts[0]['phone']);
			$rep->Text($ccol+250, _('Fax : ').$contacts[0]['fax']);
			$rep->NewLine(2);
			$rep->Text($ccol+40, _('Dear Sir, '));
			$rep->NewLine(2);
			
			$rep->SetFont('helvetica', 'B', 9);
			$rep->Text($ccol+65, _('Ref : OutStanding Bills '));
			$rep->SetFont('', '', 0);
			
			$rep->NewLine(2);
			$rep->Text($ccol+65, _('The following bills are outstanding as on ').$to);
			
			$supplier = $myrow['name'];
			
			
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
			
			if ($no_zeros && floatcmp(abs($trans['TotalAmount']), $trans['settled_amt']) == 0) continue;
			
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
		
		
		    $inv_amount_cr = $inv_amount_dr =  0.0;
			
			
            if ($trans['TotalAmount'] > 0.0)
			{
				$inv_amount_cr = round2($trans['TotalAmount'] * $rate, $dec);
				$rep->TextCol(4, 5, number_format2($inv_amount_cr, $dec)." Cr");
				$allocated_amt = round2($trans['settled_amt'] * $rate, $dec);
				$inv_amt_total += $inv_amount_cr;
			}
			else
			{
				$inv_amount_dr = round2(abs($trans['TotalAmount']) * $rate, $dec);
				$rep->TextCol(4, 5, number_format2($inv_amount_dr, $dec)." Dr");
				$allocated_amt = round2($trans['settled_amt'] * $rate, $dec) * -1;
				$inv_amt_total -= $inv_amount_dr;
			}				
			
				
			$rep->AmountCol(5, 6, $trans['settled_amt'], $dec);	

			if ($trans['TotalAmount'] > 0.0)
				$inv_balance = $inv_amount_cr - $allocated_amt;
			else	
				$inv_balance = -$inv_amount_dr - $allocated_amt;
			
			
		    if($inv_balance > 0.0){
			$rep->TextCol(6, 7, number_format2($inv_balance, $dec)." Cr");
			$balance += $inv_balance;
		    }
		    else{
			$rep->TextCol(6, 7, number_format2(-$inv_balance, $dec)." Dr");	
			$balance -= -$inv_balance;
		    }

		
             if($balance > 0.0){
              $rep->TextCol(7, 8, number_format2($balance, $dec)." Cr");
		     }
            else{
            $rep->TextCol(7, 8, number_format2(-$balance, $dec)." Dr");	
            }				
			
			$rep->NewLine();
			
			$settled_amt_total += $trans['settled_amt'];
			
			$inv_bal_total = $balance;
			
		}
         
		 $rep->NewLine();
		 $rep->Text($ccol+40, str_pad('', 120, '_'));
       	 $rep->NewLine();
		 
		 $rep->SetFont('helvetica', 'B', 9);
		 $rep->Text($ccol + 50,  _("Closing Balance :  ").$myrow['supp_code']);
		 $rep->AmountCol(4, 5, $inv_amt_total, $dec);
		 $rep->AmountCol(5, 6, $settled_amt_total, $dec);
		 $rep->AmountCol(6, 7, $inv_bal_total, $dec);
		  if($inv_bal_total > 0.0){
           $rep->TextCol(7, 8, number_format2($inv_bal_total, $dec)." Cr");
		  }
          else{
            $rep->TextCol(7, 8, number_format2(-$inv_bal_total, $dec)." Dr");	
          }	
		 $rep->SetFont('', '', 0);
		 $rep->NewLine(2);
		 
		
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
		 
		 $pdc_res = get_purchases_pdc_transactions($myrow['supplier_id'],$to);
		 
		 $pdc_total = $net_balance_total = 0;
		  while ($pdc = db_fetch($pdc_res))
		{
			
		 $rep->Text($ccol + 40,   sql2date($pdc['tran_date']));
		 $rep->Text($ccol + 100,  $pdc['reference']);
		 $rep->Text($ccol + 200,  $pdc['pdc_cheque_no']);
		 $rep->Text($ccol + 290,  sql2date($pdc['pdc_cheque_date']));
		 $rep->Text($ccol + 360,  $pdc['our_ref_no']);
		 $rep->Text($ccol + 450,  sql2date($pdc['tran_date']));
		 $rep->AmountCol(7, 8, -$pdc['TotalAmount'],$dec);
		 $rep->NewLine();
		 $pdc_total += $pdc['TotalAmount'];
		 
		}	
		
		 $rep->Text($ccol+480, str_pad('', 20, '_'));
		 $rep->NewLine();
		 $rep->SetFont('helvetica', 'B', 9);
		 $rep->Text($ccol + 40,  _("Post dated cheques total"));
		 $rep->TextCol(7, 8,  number_format2(-$pdc_total,$dec). " Dr" );
		 $rep->SetFont('', '', 0);
		 $rep->NewLine();
		 $rep->Text($ccol+480, str_pad('', 20, '_'));
		 $rep->NewLine();
		 $net_balance_total = $inv_bal_total - (-$pdc_total);
		 $rep->SetFont('helvetica', 'B', 9);
		 $rep->Text($ccol + 40,  _("Net Balance"));
		 
		 if ($net_balance_total>0.0)
		 $rep->TextCol(7, 8,  number_format2($net_balance_total,$dec). " Cr" );
	     else
		 $rep->TextCol(7, 8,  number_format2(-$net_balance_total,$dec). " Dr" );	 
	 
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
		$rep->Text($ccol + 420,  _("UnAdjusted (Dr)"));
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
	$cur_month_inv_value1 = get_monthly_purchases_invoice_info($myrow['supplier_id'],$cur_first_date,$cur_last_date);
	
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
	$last_mn_inv_value1 = get_monthly_purchases_invoice_info($myrow['supplier_id'],$last_mn_first_date,$last_mn_last_date);
	
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
	$last_2mn_inv_value1 = get_monthly_purchases_invoice_info($myrow['supplier_id'],$last_2mn_first_date,$last_2mn_last_date);
	
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
	$last_3_mn_inv_value1 = get_monthly_purchases_invoice_info($myrow['supplier_id'],$last_3_mn_first_date,$last_3_mn_last_date);
	
	
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
	$last_4_mn_inv_value1 = get_monthly_purchases_invoice_info($myrow['supplier_id'],$last_4_mn_first_date,$last_4_mn_last_date);
	
	
	
	$unadjusted_dr_value1 = get_monthly_unadjusted_debit_info($myrow['supplier_id'],$to);
	
	$cur_month_inv_value   = $cur_month_inv_value1*$rate;
	$last_mn_inv_value     = $last_mn_inv_value1*$rate;
	$last_2mn_inv_value    = $last_2mn_inv_value1*$rate;
	$last_3_mn_inv_value   = $last_3_mn_inv_value1*$rate;
	$last_4_mn_inv_value   = $last_4_mn_inv_value1*$rate;
	$unadjusted_dr_value   = $unadjusted_dr_value1*$rate;
	
	
	$cur_net_balance = $cur_month_inv_value+$last_mn_inv_value+$last_2mn_inv_value+$last_3_mn_inv_value+$last_4_mn_inv_value-(-$unadjusted_dr_value);
	$rep->NewLine();
	$rep->Text($ccol + 40,  number_format2($cur_month_inv_value,$dec));
	$rep->Text($ccol + 110,  number_format2($last_mn_inv_value,$dec));
	$rep->Text($ccol + 200,  number_format2($last_2mn_inv_value,$dec));
	$rep->Text($ccol + 280,  number_format2($last_3_mn_inv_value,$dec));
	$rep->Text($ccol + 350,  number_format2($last_4_mn_inv_value,$dec));
	$rep->Text($ccol + 420,  number_format2(-$unadjusted_dr_value,$dec));
	$rep->AmountCol(7, 8, $cur_net_balance,$dec);
	$rep->NewLine();
	$rep->Text($ccol+40, str_pad('', 120, '_')); 
	
		
	
	$rep->NewLine(1);
	
	
	$rep->NewLine(3);
	$rep->Text($ccol+40, _("Thanking You") );
	$rep->NewLine();
	$rep->SetFont('helvetica', 'B', 9);
	$rep->Text($ccol+40 , _("For ").$rep->company['coy_name']);
	$rep->SetFont('', '', 0);
	
	
	$rep->NewLine(4);
	
	

	$rep->Text($ccol+40, _("Accountant "));
    $rep->NewLine();
	
		
		
	}
	
	
	
	
    $rep->NewLine();
    $rep->End();

}