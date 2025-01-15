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
	'SA_GLANALYTIC' : 'SA_GLREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Purchase Remittance
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/db/crm_contacts_db.inc");

//----------------------------------------------------------------------------------------------------

print_petty_cash_payments();

function get_petty_cash_payment_prints($type, $trans_no)
{
   	$sql = "SELECT trans.*, act.*
		FROM "
			.TB_PREF."gl_pettycash_trans trans,"
			.TB_PREF."bank_accounts act
		WHERE act.id=trans.bank_act
		AND trans.type = ".db_escape($type)."
		AND trans.trans_no = ".db_escape($trans_no);
   	$result = db_query($sql, "The remittance cannot be retrieved");
   	if (db_num_rows($result) == 0)
   		return false;
    return db_fetch($result);
}

function get_supplier_details_by_code($supp_gl_account)
{
	$sql = "SELECT 	supp_code as supp_code, supp_name as name 
		FROM "
		.TB_PREF."suppliers s
		WHERE NOT s.inactive AND s.payable_account=".db_escape($supp_gl_account)."";
		
	$result = db_query($sql, "The remittance cannot be retrieved");
   	if (db_num_rows($result) == 0)
   		return false;
    return db_fetch($result);	
		
}

function get_pettycash_dimension_string($id, $html=false, $space=' ')
{
	if ($id <= 0)
	{
		if ($html)
			$dim = "&nbsp;";
		else
			$dim = "";
	}
	else
	{
		$row = get_dimension($id, true);
		$dim = $row['name'];
	}

	return $dim;
}

//----------------------------------------------------------------------------------------------------


function print_petty_cash_payments()
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

	$cols = array(2, 25, 80, 420, 440, 520);
               // 0 - 1 - 2  - 3 - 4  - 5 
	// $headers in doctext.inc
	$aligns = array('left',	'left',	'left', 'left', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
		$rep = new FrontReport(_('REMITTANCE'), "RemittanceBulk", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

	for ($i = $from; $i <= $to; $i++)
	{
		
			
			$myrow = get_petty_cash_payment_prints(ST_BANKPAYMENT, $i);
			if (!$myrow)
				continue;
			//if ($currency != ALL_TEXT && $myrow['curr_code'] != $currency) {
				//continue;
			//}
			
			$res = get_gl_petty_cash_trans(ST_BANKPAYMENT, $myrow['our_ref_no']);
			$baccount = db_fetch($res);
			$params['bankaccount'] = $baccount['bank_act'];

			if ($email == 1)
			{
				$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
				$rep->title = _('CASH PAYMENT');
				$rep->filename = "CASHPAYMENT" . $i . ".pdf";
			}
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);
			
			

			//$contacts = get_supplier_contacts($baccount['person_id'], 'invoice');
			$rep->SetCommonData($myrow, null, $myrow, $baccount, ST_PETTY_CASH_REPORT, $baccount);
			$rep->SetHeaderType('Header70');
            $rep->NewPage();
			
			$items = get_petty_cash_gl_trans(ST_BANKPAYMENT, $myrow['our_ref_no']);
			
			$k=1;
            $total_amount = 0;
			while ($myrow2=db_fetch($items))
			{
				$rep->TextCol(0, 1,	$k, -2);
				/*
				$supp_details= get_supplier_details_by_code($myrow2['account']);
			    if($supp_details)
				{	
                   	$rep->TextCol(1, 2,	$supp_details['supp_code'], -2);	
					$rep->TextCol(2, 3,	$supp_details['name'], -2);					
				}
				else
				{
				$rep->TextCol(1, 2,	$myrow2['account'], -2);
				
				if($myrow2["person_type_id"]=='3')
				$rep->TextCol(2, 3,	payment_person_name($myrow2["person_type_id"],$myrow2["person_id"]), -2);	
				else
				$rep->TextCol(2, 3,	$myrow2['account_name'], -2);
			    }
				*/
				
				if($myrow2["account"]==get_company_Pref('creditors_act')){
				$supp_info = get_supplier($myrow2["person_id"]);
				$rep->TextCol(1, 2,	$supp_info['supp_code'], -2);	
				$rep->TextCol(2, 3,	$supp_info['supp_name'], -2);	
				}	
				else{
				$rep->TextCol(1, 2,	$myrow2['account'], -2);
				$rep->TextCol(2, 3,	$myrow2['account_name'], -2);
				}
				
				//$rep->TextCol(3, 4,	get_pettycash_dimension_string($myrow2['dimension_id']), -2);
				//$rep->TextCol(4, 5,	$myrow2['ref'], -2);
				
				$rep->AmountCol(4, 5, -$myrow2['amount'], $dec, -2);
				$total_amount += $myrow2['amount'];
				$rep->NewLine();
				
				$rep->TextCol(2, 4,	$myrow2['memo_'], -2);
				$rep->NewLine();
				
				$k++;
				
				if ($rep->row < $rep->bottomMargin + (12 * $rep->lineHeight))
					$rep->NewPage();
			}
			
			
			

			$memo = get_comments_string($j, $i);
			if ($memo != "")
			{
				$rep->NewLine();
				$rep->TextColLines(1, 5, $memo, -2);
			}
			$rep->row = $rep->bottomMargin + (12 * $rep->lineHeight);
			$doctype = ST_PETTY_CASH_REPORT;
			

		

			$rep->Text($ccol+40, str_pad('', 120, '_'));
		    $rep->NewLine();
			$rep->SetFont('helvetica', 'B', 9);
			
			  $rep->Text($ccol + 410, _("TOTAL (").$myrow['bank_curr_code'].")");
			
			//$rep->TextCol(3, 4, _("TOTAL (").$myrow['bank_curr_code'].")", - 2);
			$rep->AmountCol(4, 5, -$total_amount, $dec, -2);
			$rep->SetFont('', '', 0);

			$words = no_to_words(-$total_amount);
			$hundredth_name=get_currency_hundredth_name($myrow['bank_curr_code']);
			
			$payment_amount=$myrow['Total'];
			list($whole, $decimal) = explode('.', $payment_amount);
			
            if($decimal)
			$words1=" ".$hundredth_name." only";
			else
			$words1=" only";
			
			 if ($words != "")
			{
				$rep->NewLine(1);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(0, 7, $myrow['bank_curr_code']." : ".$words.$words1, - 2);
				$rep->SetFont('', '', 0);	
			}
		$rep->NewLine();
		$rep->Text($ccol+40, str_pad('', 120, '_'));
		
			
		$rep->NewLine(6);
		$rep->SetFont('helvetica', 'B', 9);
	    $rep->Text($mcol + 55, _("Accountant"));
		$rep->Text($mcol + 275, _("Finance Manager"));
		//$rep->Text($mcol + 370, _("General Manager"));
		$rep->Text($ccol + 520 , _("Director "));
	    $rep->SetFont('', '', 0);
			
			
			
			$rep->Font();
			if ($email == 1)
			{
				$myrow['DebtorName'] = $myrow['supp_name'];
				$rep->End($email);
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

