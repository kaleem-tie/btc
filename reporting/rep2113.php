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

print_remittances();

//----------------------------------------------------------------------------------------------------
function get_receipt($type, $trans_no)
{
    $sql = "SELECT trans.*, (ov_amount+ov_gst+freight_cost+additional_charges+packing_charges+other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff) AS Total,
				trans.ov_discount, 
				supp.supp_name AS SuppName,
				supp.supp_ref ,
   				supp.curr_code,
   				supp.payment_terms,
   				supp.tax_group_id AS tax_id,
   				supp.address,
				trans.bank_account as bank_act,
				trans.supplier_id,
				trans.ov_gst,
				supp.supp_code,
				trans.our_ref_no
    			FROM ".TB_PREF."supp_trans trans,"
    				.TB_PREF."suppliers supp
				WHERE trans.supplier_id =supp.supplier_id
				AND trans.type = ".db_escape($type)."
				AND trans.trans_no = ".db_escape($trans_no);
				
				//display_error($sql);
				
   	$result = db_query($sql, "The remittance cannot be retrieved");
   	if (db_num_rows($result) == 0)
   		return false;
    return db_fetch($result);
}



function get_pdc_supplier_transactions_rep($trans_no=null, $type=null)
{
	$sql = "SELECT
		trans.type,
		trans.trans_no,
		trans.reference,
		trans.tran_date,
		supp.supp_name, 
		supp.curr_code,
		trans.ov_amount+trans.ov_gst AS Total,
		trans.due_date,
		supp.address,
		trans.supplier_id
	 FROM ".TB_PREF."supp_trans as trans,"
	 		.TB_PREF."suppliers as supp
	 WHERE
	 	 trans.supplier_id =supp.supplier_id ";
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



function get_cust_pdc_bank_act_name_rep($bank_act)
{
	$sql = "SELECT bank_account_name,bank_curr_code 
	FROM ".TB_PREF."bank_accounts 
	WHERE  id = ".db_escape($bank_act);
	 $res = db_query($sql);
     $result = db_fetch($res);
     return $result;
}

function get_supp_invoice_payment_bank_act_name_rep($bank_act)
{
	$sql = "SELECT bank_account_name 
	FROM ".TB_PREF."bank_accounts 
	WHERE  id = ".db_escape($bank_act);
	 $res = db_query($sql);
     $result = db_fetch($res);
     return $result[0];
}




//--------------------------------------------------

function print_remittances()
{
	global $path_to_root, $systypes_array;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$orientation = $_POST['PARAM_5'];

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
			$types = array(ST_BANKDEPOSIT, ST_SUPPAYMENT, ST_SUPPPDC);
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

			//$contacts = get_supplier_contacts($myrow['supplier_id'], 'invoice');
			$contacts = "";
			$rep->SetCommonData($myrow, null, $myrow, $baccount, ST_SUPPPDC, $contacts);
 			$rep->SetHeaderType('header10');
			$rep->NewPage();
			
			
			$alloc_result = get_allocatable_to_pdc_supp_transactions($myrow['supplier_id'],$myrow['trans_no'], $myrow['type']);
			
			
			if(db_num_rows($alloc_result)==0){
               $result = get_pdc_supplier_transactions_rep($myrow['trans_no'], $myrow['type']);
            }
            else{
				$result = get_allocatable_to_pdc_supp_transactions($myrow['supplier_id'],$myrow['trans_no'], $myrow['type']);
			}
	

		// $result = get_allocatable_to_pdc_supp_transactions($myrow['supplier_id'],$myrow['trans_no'], $myrow['type']);
	
			$total_allocated = 0;
			$rep->TextCol(0, 4,	_("Full / part / payment towards:"), -2);
			$rep->NewLine(1);
			
			$flag=0;
			$supplier_name="";
			$tot_allocate = 0;
			$tot_amount = 0;
			

			while ($myrow2=db_fetch($result))
			{
				//display_error(http_build_query($myrow2));
				$flag=1;
				if($supplier_name != $myrow2['supp_name'])
				{
					$rep->NewLine(1);
					$supplier_name = $myrow2['supp_name'];
					$rep->TextCol(0, 7,	$myrow2['supp_name']);
					$rep->NewLine();
				}
				
				if(db_num_rows($alloc_result)!=0){
				$rep->TextCol(0, 1,	$systypes_array[$myrow2['type']], -2);
				$rep->TextCol(1, 2,	$myrow2['supp_reference'], -2);
				$rep->TextCol(2, 3,	sql2date($myrow2['tran_date']), -2);
				$rep->TextCol(3, 4,	sql2date($myrow2['due_date']), -2);
				}
				
				$rep->AmountCol(4, 5, abs($myrow2['Total']), $dec, -2);
				if(db_num_rows($alloc_result)!=0){
				$rep->AmountCol(5, 6, $myrow2['amt'], $dec, -2);
				}
				$rep->AmountCol(6, 7, abs($myrow2['Total'] - $myrow2['alloc']), $dec, -2);
				
				$tot_amount+=$myrow2['amt'];
				if($myrow2['supp_reference']!=null)
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


            $doctype = ST_SUPPPDC;
			$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);

           if (floatcmp($myrow['ov_amount'], 0))
			{
				$rep->NewLine();
				$rep->TextCol(3, 6, _("Amount"), - 2);
				//$rep->AmountCol(6, 7, $myrow['ov_amount'], $dec, -2);
				$rep->AmountCol(6, 7, abs($myrow['Total']), $dec, -2);
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
                $myrow['ov_discount'] = -abs($myrow['ov_discount']); // Ensure it's negative
				$rep->AmountCol(6, 7, $myrow['ov_discount'], $dec, -2);
			}	
			$rep->NewLine();
			$rep->Font('bold');
			$rep->TextCol(3, 6, _("TOTAL PDC"), - 2);
			$rep->AmountCol(6, 7, abs($myrow['Total']), $dec, -2);
			//$rep->AmountCol(6, 7, $tot_amount, $dec, -2);

			//$words = no_to_words($myrow['Total'], ST_CUSTPDC);
			$words = no_to_words(abs($myrow['Total']), ST_SUPPPDC);
			//$words = no_to_words($tot_pdc, ST_SUPPPDC);
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