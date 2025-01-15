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
	'SA_BANKREP' : 'SA_CHEQUE_REP';
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

print_bank_payments();

//----------------------------------------------------------------------------------------------------
function get_bank_trans_rep($type, $trans_no=null, $person_type_id=null, $person_id=null)
{
	$sql = "SELECT bt.*, act.*,
		IFNULL(abs(dt.ov_amount), IFNULL(ABS(st.ov_amount), bt.amount)) settled_amount,
		IFNULL(abs(dt.ov_amount/bt.amount), IFNULL(ABS(st.ov_amount/bt.amount), 1)) settle_rate,
		IFNULL(debtor.curr_code, IFNULL(supplier.curr_code, act.bank_curr_code)) settle_curr,
		supplier.supplier_id

		FROM ".TB_PREF."bank_trans bt
				 LEFT JOIN ".TB_PREF."debtor_trans dt ON dt.type=bt.type AND dt.trans_no=bt.trans_no
				 LEFT JOIN ".TB_PREF."debtors_master debtor ON debtor.debtor_no = dt.debtor_no
				 LEFT JOIN ".TB_PREF."supp_trans st ON st.type=bt.type AND st.trans_no=bt.trans_no
				 LEFT JOIN ".TB_PREF."suppliers supplier ON supplier.supplier_id = st.supplier_id,
			 ".TB_PREF."bank_accounts act
		WHERE act.id=bt.bank_act ";
	if (isset($type))
		$sql .= " AND bt.type=".db_escape($type);
	if (isset($trans_no))
		$sql .= " AND bt.trans_no = ".db_escape($trans_no);
	if (isset($person_type_id))
		$sql .= " AND bt.person_type_id = ".db_escape($person_type_id);
	if (isset($person_id))
		$sql .= " AND bt.person_id = ".db_escape($person_id);
	if($type != ST_BANKTRANSFER && $type != ST_SUPPAYMENT && $type!= ST_BANKDEPOSIT)   // Ramesh added for salesman collection entry
		$sql .= " AND bt.amount>0 ";
	$sql .= " ORDER BY trans_date, bt.id";
    
	$result = db_query($sql, "The remittance cannot be retrieved");
   	if (db_num_rows($result) == 0)
   		return false;
    return db_fetch($result);
}


function get_bank_trans_to($type, $trans_no)
{
$sql = "SELECT * FROM ".TB_PREF."bank_trans WHERE type=".db_escape($type)." and trans_no=".db_escape($trans_no)." and amount > 0";

$sql .= " ORDER BY trans_date, id";
$result = db_query($sql, "The remittance cannot be retrieved");
   	if (db_num_rows($result) == 0)
   		return false;
    return db_fetch($result);
}



function get_gl_trans_rep_eighty_two($type, $trans_id)
{
	$sql = "SELECT gl.*, cm.account_name, IFNULL(refs.reference, '') AS reference, user.real_name, 
			COALESCE(st.tran_date, dt.tran_date, bt.trans_date, grn.delivery_date, gl.tran_date) as doc_date,
			IF(ISNULL(st.supp_reference), '', st.supp_reference) AS supp_reference
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
  
	return db_query($sql, "The gl transactions could not be retrieved");
}


function get_bank_deposit_bank_act_name_rep($bank_act)
{
	$sql = "SELECT bank_account_name 
	FROM ".TB_PREF."bank_accounts 
	WHERE  id = ".db_escape($bank_act);
	 $res = db_query($sql);
     $result = db_fetch($res);
     return $result[0];
}

//-------------------------------------------------------------


function print_bank_payments()
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
         ///          0-  1 - 2 - 3 - 4   - 5 - 6 
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
			$types = array(ST_BANKTRANSFER, ST_SUPPAYMENT, ST_SUPPCREDIT);
		foreach ($types as $j)
		{
			
			
			$myrow = get_bank_trans_rep(ST_BANKTRANSFER, $i);
			
			
			if (!$myrow)
				continue;
			
		//	if ($currency != ALL_TEXT && $myrow['curr_code'] != $currency) {
		//		continue;
		//	}
			$res = get_bank_trans($j, $i);
			$baccount = db_fetch($res);
			$params['bankaccount'] = $myrow['bank_act'];

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
			$rep->SetCommonData($myrow, null, $myrow, $myrow, ST_BANKTRANSFER_REP, $contacts);
			$rep->SetHeaderType('Header72');
			$rep->NewPage();
			$rep->NewLine();
			
			
			$result = get_gl_trans_rep_eighty_two(ST_BANKTRANSFER, $i);
			$total_amount = 0;
			$k=1;
			while ($myrow2=db_fetch($result))
			{
				if ($myrow2["account"] != $myrow["account_code"])
				{
				$counterpartyname = get_subaccount_name($myrow2["account"], $myrow2["person_id"]);
	            $counterparty_id = $counterpartyname ? sprintf(' %05d', $myrow2["person_id"]) : '';
				
				//display_error(get_company_Pref('debtors_act'));
				$rep->TextCol(0, 1,	$k, -2);
				$cust_info = get_customer($myrow2["person_id"]);
				$supp_info = get_supplier($myrow2["person_id"]);
				
				
				if($myrow2["account"]==get_company_Pref('debtors_act')){
					$rep->TextCol(1, 2,	$cust_info['cust_code'], -2);
					$rep->TextCol(2, 3,	$cust_info['name'], -2);
				}	
				elseif($myrow2["account"]==get_company_Pref('creditors_act')){
					$rep->TextCol(1, 2,	$supp_info['supp_code'], -2);
					$rep->TextCol(2, 3,	$supp_info['supp_name'], -2);
				}
				elseif($myrow2["account"]!=get_company_Pref('debtors_act') && $myrow2["account"]!=get_company_Pref('creditors_act')){
				$rep->TextCol(1, 2,	$myrow2['account'].$counterparty_id, -2);
			    $rep->TextCol(2, 3,	$myrow2['account_name'] . ($counterpartyname ? ': '.$counterpartyname : ''), -2);
				}
				
				
				
				/*
				$oldrow = $rep->row;
				$rep->TextColLines(1, 2, $counterpartyname, -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				*/
				$rep->AmountCol(3, 4, $myrow2['amount'], $dec, -2);
				$rep->TextCol(4, 5, _("Dr"));
				$total_amount += $myrow2["amount"];
				//$rep->row = $newrow;
				$rep->NewLine();
				$k++;
				
				if ($rep->row < $rep->bottomMargin + (12 * $rep->lineHeight))
					$rep->NewPage();
				
				}
				
				
			}
			
		

			$memo = get_comments_string(ST_BANKTRANSFER, $i);
			if ($memo != "")
			{
				$rep->NewLine();
				$rep->TextColLines(2, 4, $memo, -2);
			}
						
			//$rep->NewLine(3);
			$rep->row = $rep->bottomMargin + (12 * $rep->lineHeight);
			$doctype = ST_BANKTRANSFER_REP;
			
			
			$rep->NewLine();
			$rep->SetFont('helvetica', 'B', 9);
			$DisplayTotal = number_format2($total_amount,$dec);
			
			$rep->Text($ccol+400, _("Total (").$myrow['bank_curr_code'].")");
		//	$rep->TextCol(2, 3, _("TOTAL (").$myrow['bank_curr_code'].")", - 2);
			$rep->AmountCol(3, 4, $total_amount, $dec, -2);
			$rep->SetFont('', '', 0);
			$rep->Text($ccol+40, str_pad('', 120, '_'));
		
			$words = no_to_words($total_amount);
			$hundredth_name=get_currency_hundredth_name($myrow['bank_curr_code']);
			
			$rep->NewLine();

			
			$payment_amount=$total_amount;
			list($whole, $decimal) = explode('.', $payment_amount);
			
            if($decimal)
			$words1=" ".$hundredth_name." only";
			else
			$words1=" only";
			
			 if ($words != "")
			{
				$rep->NewLine(1);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(0, 4, $myrow['bank_curr_code'].": ".$words.$words1, - 2); 
				$rep->SetFont('', '', 0);
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
			
			
			
			
	/*		if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
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
		}*/
	}
	//if ($email == 0)
	//	$rep->End();
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