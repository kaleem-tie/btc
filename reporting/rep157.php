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

$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	'SA_SALESTRANSVIEW' : 'SA_SALESBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Receipts
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

//----------------------------------------------------------------------------------------------------

print_receipts();

//----------------------------------------------------------------------------------------------------
function get_receipt($type, $trans_no)
{
    $sql = "SELECT trans.*,
				SUM(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_roundoff) AS Total,
				trans.ov_discount, 
				debtor.name AS DebtorName,
				debtor.debtor_ref,
   				debtor.curr_code,
   				debtor.payment_terms,
   				debtor.tax_id AS tax_id,
   				debtor.address,
				trans.bank_account as bank_act
    			FROM ".TB_PREF."debtor_trans trans,"
    				.TB_PREF."debtors_master debtor
				WHERE trans.debtor_no = debtor.debtor_no
				AND trans.type = ".db_escape($type)."
				AND trans.trans_no = ".db_escape($trans_no);
				
   	$result = db_query($sql, "The remittance cannot be retrieved");
   	if (db_num_rows($result) == 0)
   		return false;
    return db_fetch($result);
}



function get_pdc_cust_transactions_rep($trans_no=null, $type=null)
{
	$sql = "SELECT
		trans.type,
		trans.trans_no,
		trans.reference,
		trans.tran_date,
		debtor.name AS DebtorName, 
		debtor.curr_code,
		IF(prep_amount, prep_amount, ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount+ov_roundoff) AS Total,
		trans.due_date,
		debtor.address,
		trans.version,
		trans.debtor_no,
		trans.branch_code
	 FROM ".TB_PREF."debtor_trans as trans,"
	 		.TB_PREF."debtors_master as debtor
	 WHERE
	 	 trans.debtor_no=debtor.debtor_no";
	//if ($customer_id)
		//$sql .= " AND trans.debtor_no=".db_escape($customer_id);

	if ($trans_no != null and $type != null)
	{
		$sql .= " AND trans.trans_no=".db_escape($trans_no)."
				  AND trans.type=".db_escape($type);
	}
	
	//display_error($sql);

	return db_query($sql, "Cannot retreive alloc to transactions");
}

// get received advance amount against the sales order

function get_so_advance_info($order_no,$trans_no)
{
	$sql = "SELECT ov_amount+ov_gst as adv_amount,reference,tran_date 
				FROM ".TB_PREF."debtor_trans
			WHERE type=12 and vat_adv_status=1 and order_=".db_escape($order_no)." and trans_no!=".$trans_no." group by trans_no";
      
  	    $result= db_query($sql,"Customer Branch Record Retreive");
		
		return $result;
}

// get received advance amount against the sales order

function get_sales_order_info($order_no)
{
	$sql = "SELECT reference,ord_date,total 
				FROM ".TB_PREF."sales_orders
			WHERE trans_type=30 and  order_no=".db_escape($order_no);
      
  	    $result= db_query($sql,"Customer Branch Record Retreive");
		
		return db_fetch($result);
}

function get_cust_pdc_bank_act_name_rep($bank_act)
{
	$sql = "SELECT bank_account_name,bank_curr_code 
	FROM ".TB_PREF."bank_accounts 
	WHERE  id = ".db_escape($bank_act);
	 $res = db_query($sql);
     $result = db_fetch($res);
     return $result;
}

//--------------------------------------------------

function print_receipts()
{
	global $path_to_root, $systypes_array,$mode_payment_types;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
    $email = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$orientation = $_POST['PARAM_5'];
	$ref_no = $_POST['PARAM_6'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

 	$fno = explode("-", $from);
	$tno = explode("-", $to);
	$from = min($fno[0], $tno[0]);
	$to = max($fno[0], $tno[0]);

	$cols = array(4, 85, 150, 225, 275, 360, 450, 515);

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'left', 'left', 'right', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
		$rep = new FrontReport(_('PDC'), "PDCBulk", user_pagesize(), 9, $orientation);
   	if ($orientation == 'L')
    	recalculate_cols($cols);

	for ($i = $from; $i <= $to; $i++)
	{
		if ($fno[0] == $tno[0])
			$types = array($fno[1]);
		else
			$types = array(ST_BANKDEPOSIT, ST_CUSTPAYMENT, ST_CUSTPDC);
		foreach ($types as $j)
		{
			$myrow = get_receipt($j, $i);
			if (!$myrow)
				continue;
			if ($currency != ALL_TEXT && $myrow['curr_code'] != $currency) {
				continue;
			}
			$res = get_bank_trans($j, $i);
			$baccount = db_fetch($res);
			$params['bankaccount'] = $baccount['bank_act'];

			if ($email == 1)
			{
				$rep = new FrontReport("", "", user_pagesize(), 8, $orientation);
				$rep->title = _('PDC');
				$rep->filename = "PDC" . $i . ".pdf";
			}
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);

			$contacts = get_branch_contacts($myrow['branch_code'], 'invoice', $myrow['debtor_no']);
			$rep->SetCommonData($myrow, null, $myrow, $baccount, ST_CUSTPDC, $contacts);
 			$rep->SetHeaderType('header8');
			$rep->NewPage();
			
			
			$alloc_result = get_allocatable_to_pdc_cust_transactions($myrow['trans_no'], $myrow['type']);
				
			if(db_num_rows($alloc_result)==0){
               $result = get_pdc_cust_transactions_rep($myrow['trans_no'], $myrow['type']);
            }
            else{
				$result = get_allocatable_to_pdc_cust_transactions($myrow['trans_no'], $myrow['type']);
			}
	
		    

		

			$total_allocated = 0;
			$rep->TextCol(0, 4,	_("Full / part / payment towards:"), -2);
			$rep->NewLine(1);
            $flag=0;
			$debtor_name="";
			$tot_allocate = 0;
			$tot_amount = 0;
			while ($myrow2=db_fetch($result))
			{
				//display_error(http_build_query($myrow2));
				$flag=1;
				if($debtor_name != $myrow2['DebtorName'])
				{
					$rep->NewLine(1);
					$debtor_name = $myrow2['DebtorName'];
					$rep->TextCol(0, 7,	$myrow2['DebtorName']);
					$rep->NewLine();
				}
				
				if(db_num_rows($alloc_result)!=0){
				$rep->TextCol(0, 1,	$systypes_array[$myrow2['type']], -2);
				$rep->TextCol(1, 2,	$myrow2['reference'], -2);
				$rep->TextCol(2, 3,	sql2date($myrow2['tran_date']), -2);
				$rep->TextCol(3, 4,	sql2date($myrow2['due_date']), -2);
				}
				
				$rep->AmountCol(4, 5, $myrow2['Total'], $dec, -2);
				if(db_num_rows($alloc_result)!=0){
				$rep->AmountCol(5, 6, $myrow2['amt'], $dec, -2);
				}
				$rep->AmountCol(6, 7, $myrow2['Total'] - $myrow2['alloc'], $dec, -2);
				
				$tot_amount+=$myrow2['amt'];
				if($myrow2['reference']!=null)
				{
					$tot_allocate+=$myrow2['amt'];
				}

				$total_allocated += $myrow2['amt'];
				$rep->NewLine(1);
				if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
					$rep->NewPage();
			}
			$memo = get_comments_string($j, $i);
			if ($memo != "")
			{
				$rep->NewLine();
				$rep->SetFont('helvetica', 'B', 9);
			$rep->Text($ccol+45 , _("Comments : "));
			$rep->SetFont('', '', 0);
			$rep->NewLine();
				$rep->TextColLines(0, 5, $memo, -2);
			}


            $doctype = ST_CUSTPDC;
			$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);

           if (floatcmp($myrow['ov_amount'], 0))
			{
				$rep->NewLine();
				$rep->TextCol(3, 6, _("Amount"), - 2);
				//$rep->AmountCol(6, 7, $myrow['ov_amount'], $dec, -2);
				$rep->AmountCol(6, 7, $myrow['Total'], $dec, -2);
			}	
			
			if (floatcmp($myrow['ov_gst'], 0))
			{
				$rep->NewLine();
				$rep->TextCol(3, 6, _("Vat at (").$myrow['vat_adv_percent']. ") %", - 2);
				$rep->AmountCol(6, 7, $myrow['ov_gst'], $dec, -2);
			}	

            $rep->NewLine();

			$rep->TextCol(3, 6, _("Total Allocated"), -2);
			//$rep->AmountCol(6, 7, $total_allocated, $dec, -2);
			$rep->AmountCol(6, 7, $tot_allocate, $dec, -2);
			$rep->NewLine();
			//$rep->TextCol(3, 6, _("Left to Allocate"), -2);
			//$rep->AmountCol(6, 7, $myrow['Total'] + $myrow['ov_discount'] - $total_allocated, $dec, -2);
			//$rep->AmountCol(6, 7, $tot_amount - $tot_allocate, $dec, -2);
			if (floatcmp($myrow['ov_discount'], 0))
			{
				$rep->NewLine();
				$rep->TextCol(3, 6, _("Discount"), - 2);
				$rep->AmountCol(6, 7, -$myrow['ov_discount'], $dec, -2);
			}	
			$rep->NewLine();
			$rep->Font('bold');
			$rep->TextCol(3, 6, _("TOTAL PDC"), - 2);
			$rep->AmountCol(6, 7, $myrow['Total'], $dec, -2);
			//$rep->AmountCol(6, 7, $tot_amount, $dec, -2);

			//$words = no_to_words($myrow['Total'], ST_CUSTPDC);
			$words = no_to_words($myrow['Total'], ST_CUSTPDC);
			$hundredth_name=get_currency_hundredth_name($myrow['curr_code']);
			
			$payment_amount=$myrow['Total'];
			list($whole, $decimal) = explode('.', $payment_amount);
			
			if($decimal)
			$words1=" ".$hundredth_name." only";
			else
			$words1=" only";
		
		    if ($words != "")
			{
				$rep->NewLine(1);
				$rep->Font('bold');
				$rep->TextCol(0, 8, $myrow['curr_code'].":".$words.$words1, - 2);
				$rep->Font();
			}
			
			$rep->Font();
			$rep->NewLine(4);
			$rep->SetFont('helvetica', 'B', 9);
	        $rep->Text($ccol+45 , _("For ").$rep->company['coy_name']);
	        $rep->Text($mcol + 430, _("Customer Name & Signature"));
	        $rep->SetFont('', '', 0);
			
			if ($email == 1)
			{
				$rep->End($email);
			}
		}
	}
    if ($email == 0)
		$rep->End();
}


function no_to_words($number)
{   
 
    
     $no = $number;
	$no1 =round($number);
   $dec = explode('.', $number);
    $dec2 =substr($dec[1],0,3);

	if(strlen($dec2)==1)
	{
		$dec2=$dec2*100;
	}
	if(strlen($dec2)==2)
	{
		$dec2=$dec2*10;
	}
	
   $point = $dec2;
   $hundred = null;
   $digits_1 = strlen($no);
   $i = 0;
   $str = array();
   $words = array('0' => '', '1' => 'One', '2' => 'Two',
    '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six',
    '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
    '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve',
    '13' => 'Thirteen', '14' => 'Fourteen',
    '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
    '18' => 'Eighteen', '19' =>'Nineteen', '20' => 'Twenty',
    '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty',
    '60' => 'Sixty', '70' => 'Seventy',
    '80' => 'Eighty', '90' => 'Ninety');
   $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
   while ($i < $digits_1) {
     $divider = ($i == 2) ? 10 : 100;
     $number = floor($no % $divider);
     $no = floor($no / $divider);
     $i += ($divider == 10) ? 1 : 2;
     if ($number) {
        $plural = (($counter = count($str)) && $number > 9) ? '' : null;
        $hundred = ($counter == 1 && $str[0]) ? '' : null;
        $str [] = ($number < 21) ? $words[$number] .
            " " . $digits[$counter] . $plural . " " . $hundred
            :
            $words[floor($number / 10) * 10]
            . " " . $words[$number % 10] . " "
            . $digits[$counter] . $plural . " " . $hundred;
     } else $str[] = null;
  }
  $str = array_reverse($str);
  $result = implode('', $str);
  
		 
		  
		$hundred = null;
   $digits_1 = strlen($dec2);
   $i = 0;
   $str = array();
   $words = array('0' => '', '1' => 'One', '2' => 'Two',
    '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six',
    '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
    '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve',
    '13' => 'Thirteen', '14' => 'Fourteen',
    '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
    '18' => 'Eighteen', '19' =>'Nineteen', '20' => 'Twenty',
    '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty',
    '60' => 'Sixty', '70' => 'Seventy',
    '80' => 'Eighty', '90' => 'Ninety');
   $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
   while ($i < $digits_1) {
     $divider = ($i == 2) ? 10 : 100;
     $number = floor($dec2 % $divider);
     $dec2 = floor($dec2 / $divider);
     $i += ($divider == 10) ? 1 : 2;
     if ($number) {
        $plural = (($counter = count($str)) && $number > 9) ? '' : null;
        $hundred = ($counter == 1 && $str[0]) ? '' : null;
        $str [] = ($number < 21) ? $words[$number] .
            " " . $digits[$counter] . $plural . " " . $hundred
            :
            $words[floor($number / 10) * 10]
            . " " . $words[$number % 10] . " "
            . $digits[$counter] . $plural . " " . $hundred;
     } else $str[] = null;
  }
  $str = array_reverse($str);
  $result1 = trim(implode('', $str));
  
  if(!$result1)
   return $result;
   else  
   return trim($result).' and '.$result1; //ravi
} 

