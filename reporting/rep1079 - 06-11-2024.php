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
	'SA_SUPPTRANSVIEW' : 'SA_SUPPBULKREP';
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

print_remittances();

//----------------------------------------------------------------------------------------------------
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

function get_gl_trans_bank_amount($type, $trans_id)
{
	$sql = "SELECT gl.*, cm.account_name, IFNULL(refs.reference, '') AS reference, user.real_name, st.our_ref_no AS our_ref_no,st.ov_amount,st.rate,
			COALESCE(st.tran_date, dt.tran_date, bt.trans_date, grn.delivery_date, gl.tran_date) as doc_date,
			IF(ISNULL(st.supp_reference), '', st.supp_reference) AS supp_reference,bt.bank_act
	FROM ".TB_PREF."gl_trans as gl
		LEFT JOIN ".TB_PREF."chart_master as cm ON gl.account = cm.account_code
		LEFT JOIN ".TB_PREF."refs as refs ON (gl.type=refs.type AND gl.type_no=refs.id)
		LEFT JOIN ".TB_PREF."audit_trail as audit ON (gl.type=audit.type AND gl.type_no=audit.trans_no AND NOT ISNULL(gl_seq))
		LEFT JOIN ".TB_PREF."users as user ON (audit.user=user.id)
	# all this below just to retrieve doc_date :>
		LEFT JOIN ".TB_PREF."supp_trans st ON gl.type_no=st.trans_no AND st.type=gl.type AND (gl.type!=".ST_JOURNAL." OR gl.person_id=st.supplier_id)
		LEFT JOIN ".TB_PREF."grn_batch grn ON grn.id=gl.type_no AND gl.type=".ST_SUPPRECEIVE." AND gl.person_id=grn.supplier_id
		LEFT JOIN ".TB_PREF."debtor_trans dt ON gl.type_no=dt.trans_no AND dt.type=gl.type AND (gl.type!=".ST_JOURNAL." OR gl.person_id=dt.debtor_no)
		LEFT JOIN ".TB_PREF."bank_trans bt ON bt.type=gl.type AND bt.trans_no=gl.type_no AND bt.amount!=0
			 AND bt.person_type_id=gl.person_type_id AND bt.person_id=gl.person_id
		LEFT JOIN ".TB_PREF."journal j ON j.type=gl.type AND j.trans_no=gl.type_no"

		." WHERE gl.type= ".db_escape($type) 
		." AND gl.type_no = ".db_escape($trans_id)
		." AND gl.amount <> 0"
		." GROUP BY gl.counter ORDER BY tran_date, counter";
		
		//display_error($sql);
  
	return db_query($sql, "The gl transactions could not be retrieved");
}


function get_remittance($type, $trans_no)
{
   	$sql = "SELECT trans.*, 
   		(trans.ov_amount+trans.ov_gst) AS Total,
   		trans.ov_discount,
   		supplier.supp_name,  supplier.supp_account_no, 
   		supplier.curr_code, supplier.payment_terms, supplier.gst_no AS tax_id, 
   		supplier.address, bank.supp_bank_name, bank.supp_bank_account_no
		FROM "
			.TB_PREF."supp_trans trans,"
			.TB_PREF."suppliers supplier LEFT JOIN ".TB_PREF."supplier_bank_details bank ON 
			supplier.supplier_id = bank.supplier_id
		WHERE trans.supplier_id = supplier.supplier_id
		AND trans.type = ".db_escape($type)."
		AND trans.trans_no = ".db_escape($trans_no);
   	$result = db_query($sql, "The remittance cannot be retrieved");
   	if (db_num_rows($result) == 0)
   		return false;
    return db_fetch($result);
}


function get_supp_invoice_ref($type, $trans_no)
{
	$sql = "SELECT reference as ref
	FROM ".TB_PREF."supp_trans 
	WHERE type = ".db_escape($type)."
		AND trans_no = ".db_escape($trans_no);
	//display_error($sql);
	 $res = db_query($sql);
     $result = db_fetch($res);
     return $result['ref'];
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

	$cols = array(2, 100, 250,300, 350, 400, 515);
         ///      0-  1 - 2 - 3 - 4   - 5 - 6 
	// $headers in doctext.inc
	$aligns = array('left',	'left',	'left', 'left', 'right', 'right');

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
			$types = array(ST_BANKPAYMENT, ST_SUPPAYMENT, ST_SUPPCREDIT);
		foreach ($types as $j)
		{
			$myrow = get_remittance($j, $i);
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
				$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
				$rep->title = _('REMITTANCE');
				$rep->filename = "Remittance" . $i . ".pdf";
			}
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);

			$contacts = get_supplier_contacts($myrow['supplier_id'], 'invoice');
			$rep->SetCommonData($myrow, null, $myrow, $baccount, ST_BANKPAYMENT, $contacts);
			$rep->SetHeaderType('Header27');
			$rep->NewPage();
			$result3 = get_allocatable_to_supp_transactions($myrow['supplier_id'], $myrow['trans_no'], $myrow['type']);
			$result = get_gl_trans_bank_amount(ST_BANKPAYMENT, $myrow['trans_no']);

			$doctype = ST_BANKPAYMENT;

			$total_allocated = 0;
			
			if(db_num_rows($result3) == 0){
			$rep->TextCol(0, 4,	_("As advance payment towards:"), -2);
			}else{
				$rep->TextCol(0, 4,	_("As Full / Part payment towards:"), -2);
			}
			
			 
			//$rep->TextCol(0, 4,	_("As payment towards:"), -2);
			$rep->NewLine(2);
			
			while ($myrow2=db_fetch($result))
			{
				if($myrow2['amount']>0){
					
				$supp_details= get_supplier_details_by_code($myrow2['account']);
			    if($supp_details)
				{	
                   	$rep->TextCol(0, 1,	$supp_details['supp_code'], -2);	
					$rep->TextCol(1, 2,	$supp_details['name'], -2);					
				}
				else
				{	
				$rep->TextCol(0, 1,	$myrow2['account'], -2);
				
				if(isset($myrow2['person_id'])){
				$oldrow = $rep->row;
				$rep->TextCol(1, 2, get_supplier_name($myrow2['person_id']), -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				}else{
					
					$oldrow = $rep->row;
				$rep->TextColLines(1, 2, $myrow2['account_name'], -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				}	
					//$rep->TextCol(1, 2,	$myrow2['account_name'], -2);
				}
				
				$rep->TextCol(2, 3,	$myrow2[''], -2);
			
				$rep->TextCol(3, 4,	$myrow2['our_ref_no'], -2);
				if($myrow2['rate']!='1'){
				//$rep->AmountCol(4, 5, abs($myrow2['ov_amount']), $dec, -2);
					}
				$rep->AmountCol(5, 6, $myrow2['amount'], $dec, -2);
				}
				if ($myrow2['amount'] > 0 ) 
    	        $debit += $myrow2['amount'];
				$rep->AmountCol(6, 7, $myrow2[''], $dec, -2);

				if ($myrow2['amount'] > 0 ) 
				$total_allocated += $myrow2['amount'];
				$rep->NewLine(1);
				if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
					$rep->NewPage();
			}
			
			
			$rep->NewLine();

			$memo = get_comments_string($j, $i);
			if ($memo != "")
			{
				$rep->NewLine();
				$rep->TextColLines(1, 5, $memo, -2);
			}
			
			if(db_num_rows($result3) != 0){
			$rep->NewLine(2);
			$rep->fontSize += 1;
		    $rep->Font('bold');
			$rep->Text($mcol+45 ,_("Trans Type"));
			$rep->Text($mcol+150 ,_("Reference"));
			$rep->Text($mcol+220 ,_("Date"));
			$rep->Text($mcol+300 ,_("Invoice Amount"));
			$rep->Text($mcol+400 ,_("This Allocation"));
			$rep->Text($mcol+480 ,_("Left to Allocate"));
			$rep->Font();
		    $rep->fontSize -= 1;
			$rep->NewLine(2);
			}
			
			while ($myrow3=db_fetch($result3))
			{
				
				$refn = get_supp_invoice_ref($myrow3['type'], $myrow3['trans_no']);
				$rep->Text($mcol+45,	$systypes_array[$myrow3['type']], -2);
				$rep->Text($mcol+150,	$refn, -2);
				$rep->Text($mcol+220,	sql2date($myrow3['tran_date']), -2);
				//$rep->TextCol(3, 4,	sql2date($myrow3['due_date']), -2);
				$rep->Text($mcol+300, price_format($myrow3['Total'],3), -2);
				$rep->Text($mcol+400, price_format($myrow3['amt'],3), -2);
				$rep->Text($mcol+480 , price_format($myrow3['Total'] - $myrow3['alloc'],3), -2);

				//$total_allocated += $myrow3['amt'];
				$rep->NewLine(1);
				/*if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
					$rep->NewPage();*/
			}
			$rep->NewLine(3);
			
			$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
			

			//$rep->TextCol(3, 6, _("Total"), -2);
			$rep->AmountCol(5, 6, $total_allocated, $dec, -2);
			$rep->NewLine();
			//$rep->TextCol(3, 6, _("Left to Allocate"), -2);
			$myrow['Total'] *= -1;
			$myrow['ov_discount'] *= -1;
			//$rep->AmountCol(6, 7, $myrow['Total'] + $myrow['ov_discount'] - $total_allocated, $dec, -2);
			if (floatcmp($myrow['ov_discount'], 0))
			{
				$rep->NewLine();
			//	$rep->TextCol(3, 6, _("Discount"), - 2);
			//	$rep->AmountCol(6, 7, -$myrow['ov_discount'], $dec, -2);
			}	

			$rep->NewLine();
			$rep->Font('bold');
			//$rep->TextCol(3, 6, _("TOTAL REMITTANCE"), - 2);
			//$rep->AmountCol(6, 7, $myrow['Total'], $dec, -2);

			$words = no_to_words($debit, ST_BANKPAYMENT);
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
				$rep->TextCol(0, 8, _("RO").":".$words.$words1, - 2);
				$rep->Font();
			}
			
			
			//$rep->row = $rep->bottomMargin + (14 * $rep->lineHeight);
			
			if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
        $rep->NewPage();
	    $rep->SetFont('helvetica', 'B', 9);
		//$rep->Text($mcol+445 ,$myrow['user_id']);
		$rep->SetFont('', '', 0);
		$rep->NewLine(3);
		
		if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
        $rep->NewPage();
	  
		$rep->Text($mcol+45 ,_("-------------------------"));
		$rep->Text($mcol+270 ,_("---------------------------"));
		$rep->Text($mcol+500 ,_("-----------------"));
		$rep->NewLine();
		  $rep->SetFont('helvetica', 'B', 9);
		$rep->Text($mcol+55 ,_("Accountant"));
		$rep->Text($mcol+270 ,_("Finance Manager"));
		$rep->Text($mcol+510 ,_("Director"));
		$rep->SetFont('', '', 0);
		$rep->NewLine(2);
		$rep->SetFont('helvetica', 'B', 9);
		$rep->Text($mcol+45 ,_("Recieved By : ________________________"));
		$rep->NewLine(2);
		$rep->Text($mcol+45 ,_("Name             : ________________________"));
			
			$rep->Font();
			if ($email == 1)
			{
				$myrow['DebtorName'] = $myrow['supp_name'];
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

