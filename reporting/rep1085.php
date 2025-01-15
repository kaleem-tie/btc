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
	'SA_SUPPTRANSVIEW' : 'SA_CHEQUE_REP';
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

print_sup_pdc_cheque_payments();

//----------------------------------------------------------------------------------------------------


function get_sup_pdc_trans_rep($trans_no=null, $type=null)
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
		trans.supplier_id,
		supp.supp_code,
		trans.bank_account,
        trans.pdc_cheque_date
	 FROM ".TB_PREF."supp_trans as trans,"
	 		.TB_PREF."suppliers as supp
	 WHERE
	 	 trans.supplier_id =supp.supplier_id ";


	if ($trans_no != null and $type != null)
	{
		$sql .= " AND trans.trans_no=".db_escape($trans_no)."
				  AND trans.type=".db_escape($type);
	}
	
	//display_error($sql);

	$result = db_query($sql, "The transaction cannot be retrieved");
   	if (db_num_rows($result) == 0)
   		return false;
    return db_fetch($result);
}


function get_cheque_header_details($type, $trans_no)
{
	$sql = "SELECT
		trans.reference,
        trans.bank_account, 
        trans.tran_date,
        trans.pdc_cheque_no,
        trans.pdc_cheque_date
	 FROM ".TB_PREF."supp_trans as trans, "
	 		.TB_PREF."suppliers as supp
	 WHERE
	 	 trans.supplier_id =supp.supplier_id 
         AND trans.trans_no=".db_escape($trans_no)."
		AND trans.type=".db_escape($type);
	
 //display_error($sql);

	$result = db_query($sql, "The transaction cannot be retrieved");
   	if (db_num_rows($result) == 0)
   		return false;
    return db_fetch($result);

}


function get_bank_payment_bank_act_name_rep($bank_act)
{
	$sql = "SELECT bank_account_name 
	FROM ".TB_PREF."bank_accounts 
	WHERE  id = ".db_escape($bank_act);
	 $res = db_query($sql);
     $result = db_fetch($res);
     return $result[0];
}


function get_bank_curr($bank_act)
{
	$sql = "SELECT bank_curr_code 
	FROM ".TB_PREF."bank_accounts 
	WHERE  id = ".db_escape($bank_act);
	
	 $res = db_query($sql);
     $result = db_fetch($res);
     return $result[0];
}


function get_modified_supp_name($type, $trans_no, $supplier_id)
{
	$sql = "SELECT modified_supp_name 
	FROM ".TB_PREF."supp_trans 
	WHERE  type = ".db_escape($type )." AND trans_no = ".db_escape($trans_no)." AND supplier_id = ".db_escape($supplier_id);
	
	//display_error($sql);
	
	$res = db_query($sql);
     $result = db_fetch($res);
     return $result[0];
}

//-------------------------------------------------------------


function print_sup_pdc_cheque_payments()
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

		$cols = array(2, 35, 120, 300, 490, 500, 520);
         ///          0-  1 - 2 -  3 -  4   - 5 - 6 
	// $headers in doctext.inc
	$aligns = array('left',	'left',	'left', 'right', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
		$rep = new FrontReport(_('REMITTANCE'), "RemittanceBulk", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

	for ($i = $from; $i <= $to; $i++)
	{
		if ($fno[0] == $tno[0])
			$types = array($fno[1]);
		else
			$types = array(ST_SUPPPDC, ST_SUPPAYMENT, ST_SUPPCREDIT);
		foreach ($types as $j)
		{
			
			
			$myrow = get_sup_pdc_trans_rep($i,ST_SUPPPDC);
			
			
			if (!$myrow)
				continue;
			
			if ($currency != ALL_TEXT && $myrow['curr_code'] != $currency) {
				continue;
			}


			if ($email == 1)
			{
				$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
				$rep->title = _('REMITTANCE');
				$rep->filename = "Remittance" . $i . ".pdf";
			}
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);

			//$contacts = get_supplier_contacts($myrow['supplier_id'], 'invoice');
			$contacts = "";
			$rep->SetCommonData($myrow, null, null, null, ST_SUPPPDC_REP, $contacts);
			$rep->SetHeaderType('Header75');
			$rep->NewPage();
			$rep->NewLine();
			
			
			//$result = get_gl_trans_rep_eighty_four(ST_SUPPPDC, $i);
			$total_amount = 0;
			$k=1;	
				
				$modified_supp_name =get_modified_supp_name(ST_SUPPPDC, $i, $myrow["supplier_id"]);
				
				$rep->TextCol(0, 1,	$k, -2);
									
					$rep->TextCol(1, 2,	$myrow['supp_code'], -2);
					
					if($modified_supp_name!=''){
						$oldrow = $rep->row;
						$rep->TextColLines(2, 3, $modified_supp_name, -2);	
						$newrow = $rep->row;
				        $rep->row = $oldrow;
					}else{				
					    $oldrow = $rep->row;
						$rep->TextColLines(2, 3,	$myrow['supp_name'], -2);
						$newrow = $rep->row;
				        $rep->row = $oldrow;
					}
					
				
				$rep->AmountCol(3, 4, abs($myrow['Total']), $dec, -2);
				$rep->TextCol(4, 5, _("Dr"));
				
				$bank_cur_cod = get_bank_curr($myrow['bank_account']);
				$total_amount += $myrow["Total"];
				//$rep->row = $newrow;
				$rep->NewLine();
				 $k++;
				
				if ($rep->row < $rep->bottomMargin + (12 * $rep->lineHeight))
					$rep->NewPage();
			}
			
			$doctype = ST_SUPPPDC_REP;
			$rep->NewLine();

			$memo = get_comments_string(ST_SUPPPDC, $i);
			if ($memo != "")
			{
				$rep->NewLine();
				$rep->TextColLines(1, 5, $memo, -2);
			}
			
			
			
			$rep->NewLine(3);
			
			$rep->row = $rep->bottomMargin + (12 * $rep->lineHeight);
			
			$rep->Text($ccol+40, str_pad('', 120, '_'));	
			$rep->SetFont('helvetica', 'B', 9);
			$DisplayTotal = number_format2($total_amount,$dec);
			$rep->Text($ccol+400, _("Total (").$bank_cur_cod.")");
			$rep->Text($ccol+490, _(":  "));
		
			//$rep->Text($ccol+500, $DisplayTotal);
			$rep->AmountCol(3, 4, abs($total_amount), $dec, -2);
			$rep->SetFont('', '', 0);

			
			$rep->NewLine();
			
			$words = no_to_words(abs($total_amount));
			$hundredth_name=get_currency_hundredth_name($myrow['curr_code']);
			
			$payment_amount=$total_amount;
			list($whole, $decimal) = explode('.', $payment_amount);
			
            if($decimal)
			$words1=" ".$hundredth_name." only";
			else
			$words1=" only";
			
			 if ($words != "")
			{
				$rep->NewLine(1);
				$rep->Font('bold');
				//$rep->TextCol(0, 8, _("RO").":".$words.$words1, - 2);
				$rep->TextCol(0, 4, $bank_cur_cod.":".$words.$words1, - 2);
				$rep->Font();
			}
		$rep->NewLine();
		$rep->Text($ccol+40, str_pad('', 120, '_'));
			
			$rep->NewLine(5);
		$rep->SetFont('helvetica', 'B', 9);
	    $rep->Text($mcol + 55, _("Accountant/Chief Accountant"));
		$rep->Text($mcol + 235, _("Finance Manager"));
		$rep->Text($mcol + 390, _("Director"));
		$rep->Text($ccol + 480 , _("Received By"));
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

