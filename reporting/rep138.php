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
$page_security = 'SA_SALES_RECEIVE_LEDGER_REP';

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

//----------------------------------------------------------------------------------------------------

print_receivables_ledger();

function get_open_balance($debtorno, $to)
{
	
	if($to)
		$to = date2sql($to);
	$sql = "SELECT SUM(IF(t.type = ".ST_SALESINVOICE." OR (t.type IN (".ST_JOURNAL." , ".ST_BANKPAYMENT.") AND t.ov_amount>0),
             -abs(IF(t.prep_amount, t.prep_amount, t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount + t.ov_roundoff)), 0)) AS charges,";

	$sql .= "SUM(IF(t.type != ".ST_SALESINVOICE." AND NOT(t.type IN (".ST_JOURNAL." , ".ST_BANKPAYMENT.") AND t.ov_amount>0),
             abs(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount + t.ov_roundoff) * -1, 0)) AS credits,";		

    

 	$sql .=	"SUM(IF(t.type = ".ST_SALESINVOICE." OR (t.type IN (".ST_JOURNAL." , ".ST_BANKPAYMENT.") AND t.ov_amount>0), 1, -1) *
			(IF(t.prep_amount, t.prep_amount, abs(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount + t.ov_roundoff)) - abs(t.alloc))) AS OutStanding
		FROM ".TB_PREF."debtor_trans t
    	WHERE t.debtor_no = ".db_escape($debtorno)."
		AND t.ov_amount!=0
		AND t.type NOT IN (".ST_CUSTDELIVERY.",".ST_CUSTPDC.")";
    if ($to)
    	$sql .= " AND t.tran_date < '$to'";

	$sql .= " GROUP BY debtor_no";
	
    $result = db_query($sql,"No transactions were returned");
    return db_fetch($result);
}

function get_transactions($debtorno, $from, $to)
{
	$from = date2sql($from);
	$to = date2sql($to);

 	$allocated_from = 
 			"(SELECT trans_type_from as trans_type, trans_no_from as trans_no, date_alloc, sum(amt) amount
 			FROM ".TB_PREF."cust_allocations alloc
 				WHERE person_id=".db_escape($debtorno)."
 					AND date_alloc <= '$to'
 				GROUP BY trans_type_from, trans_no_from) alloc_from";
 	$allocated_to = 
 			"(SELECT trans_type_to as trans_type, trans_no_to as trans_no, date_alloc, sum(amt) amount
 			FROM ".TB_PREF."cust_allocations alloc
 				WHERE person_id=".db_escape($debtorno)."
 					AND date_alloc <= '$to'
 				GROUP BY trans_type_to, trans_no_to) alloc_to";

     $sql = "SELECT trans.*,
		IF(trans.prep_amount, trans.prep_amount, trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff)
			AS TotalAmount,
 		((trans.type = ".ST_SALESINVOICE.")	AND trans.due_date < '$to') AS OverDue
     	FROM ".TB_PREF."debtor_trans trans
 			LEFT JOIN ".TB_PREF."voided voided ON trans.type=voided.type AND trans.trans_no=voided.id
 			LEFT JOIN $allocated_from ON alloc_from.trans_type = trans.type AND alloc_from.trans_no = trans.trans_no
 			LEFT JOIN $allocated_to ON alloc_to.trans_type = trans.type AND alloc_to.trans_no = trans.trans_no
     	WHERE trans.tran_date >= '$from'
 			AND trans.tran_date <= '$to'
 			AND trans.debtor_no = ".db_escape($debtorno)."
 			AND trans.type NOT IN (".ST_CUSTDELIVERY.",".ST_CUSTPDC.")
			AND trans.ov_amount!=0
 			AND ISNULL(voided.id)";
		
     	$sql .=" ORDER BY trans.tran_date";
		
		
    return db_query($sql,"No transactions were returned");
}


function get_customer_payment_mode_info($type,$trans_no)
{
	$sql = "SELECT mode_of_payment,cheque_no,pymt_ref,date_of_issue FROM ".TB_PREF."bank_trans 
	WHERE type=12 and trans_no=".db_escape($trans_no)."";
	
	$result = db_query($sql,"No transactions were returned");
    return db_fetch($result);
	
}

function get_customer_payment_sales_person_ref_info($type,$trans_no)
{
	$sql = "SELECT sales_person_ref FROM ".TB_PREF."debtor_trans 
	WHERE type=12 and trans_no=".db_escape($trans_no)."";
	
	$result = db_query($sql,"No transactions were returned");
    return db_fetch($result);
	
}


function get_comments_all($type, $type_no)
{
	$sql = "SELECT memo_ FROM ".TB_PREF."comments WHERE type="
		.db_escape($type)." AND id=".db_escape($type_no);

	$result = db_query($sql,"could not query comments transaction table");
    return db_fetch($result);
}


//----------------------------------------------------------------------------------------------------

function print_receivables_ledger()
{
    	global $path_to_root, $systypes_array;

    	$from        = $_POST['PARAM_0'];
    	$to          = $_POST['PARAM_1'];
    	$fromcust    = $_POST['PARAM_2'];
    	$currency    = $_POST['PARAM_3'];
    	$no_zeros    = $_POST['PARAM_4'];
    	$comments    = $_POST['PARAM_5'];
	    $orientation = $_POST['PARAM_6'];
	    $destination = $_POST['PARAM_7'];
		
		$show_balance = 1;
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
    $dec = user_price_dec();
	

	if ($show_balance) $sb = _('Yes');
	else $sb = _('No');

	if ($currency == ALL_TEXT)
	{
		$convert = true;
		$currency = _('Balances in Home Currency');
	}
	else
		$convert = false;

	if ($no_zeros) $nozeros = _('Yes');
	else $nozeros = _('No');
	
	
	 $headers2 = array(_('Code'), _('Account Name'), _(''), _('Narration'));

	$cols = array(0, 60, 130, 190, 260, 300, 390, 460, 540);

	$headers = array(_('Date'), _('Doc No.'), _('Ref No.'), _('Chq No.'), _('Chq Date'), 
	_('Debit(RO)'), _('Credit(RO)'),_('Balance(RO)'));
	
	$aligns = array('left',	'left',	'left',	'left',	'left', 'right', 'right', 'right');
	
	$aligns2 = $aligns;

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
    				    3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						4 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => ''));

    $rep = new FrontReport(_('Receivables Ledger'), "ReceivablesLedger", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$cols2 = $cols;
	
    $rep->Font();
     $rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
    $rep->NewPage();

	
	
	$grandtotal = array(0,0,0);

	$sql = "SELECT debtor_no, name, curr_code,cust_code FROM ".TB_PREF."debtors_master ";
	if ($fromcust != ALL_TEXT)
		$sql .= "WHERE debtor_no=".db_escape($fromcust);
	$sql .= " ORDER BY name ";
	
	$result = db_query($sql, "The customers could not be retrieved");

	while ($myrow = db_fetch($result))
	{
		
		if (!$convert && $currency != $myrow['curr_code']) continue;
		
		$accumulate = 0;
		$rate = $convert ? get_exchange_rate_from_home_currency($myrow['curr_code'], Today()) : 1;
		$bal = get_open_balance($myrow['debtor_no'], $from);
		$init = array();

		$init[0] = round2(($bal != false ? abs($bal['charges']) : 0)*$rate, $dec);
		$init[1] = round2(($bal != false ? abs($bal['credits']) : 0)*$rate, $dec);
	
		if ($show_balance)
		{
			
			$init[2] = $init[0] - $init[1];
			$accumulate += $init[2];
		}	
		else{	
			$init[2] = round2(($bal != false ? $bal['OutStanding'] : 0)*$rate, $dec);
		}
		

		$res = get_transactions($myrow['debtor_no'], $from, $to);
				
		
		if ($no_zeros && db_num_rows($res) == 0) continue;

		
		$rep->SetFont('helvetica', 'B', 9);
		$rep->fontSize += 2;
		$rep->TextCol(0, 4, $myrow['cust_code']." - ".$myrow['name']);
		$rep->fontSize -= 2;
		if ($convert)
			$rep->TextCol(4, 5,	$myrow['curr_code']);
		
		$rep->TextCol(5, 6,	_("Opening Balance"));
		$rep->AmountCol(7, 8, $init[2], $dec);
		 $rep->SetFont('', '', 0);
		//$rep->AmountCol(4, 5, $init[0], $dec);
		//$rep->AmountCol(5, 6, $init[1], $dec);
		
		
		
		
		$total = array(0,0,0);
		
		for ($i = 0; $i < 3; $i++)
		{
			$total[$i] += $init[$i];
			$grandtotal[$i] += $init[$i];
		}
		$rep->NewLine(1, 2);
		$rep->Line($rep->row + 4);
		if (db_num_rows($res)==0) {
			$rep->NewLine(1, 2);
			continue;
		}
		
		
		while ($trans = db_fetch($res))
		{
            if ($no_zeros) {
                if ($show_balance) {
                    if ($trans['TotalAmount'] == 0) continue;
                } 
            }
			
			$inv_type = "";
			
			
			
			
			$rep->NewLine(1, 2);
			
			$rep->DateCol(0, 1,	$trans['tran_date'], true);
			$rep->TextCol(1, 2,	$inv_type. "  ".$trans['reference']);
			if ($trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_BANKDEPOSIT)
			{
				$cust_payment_sp_ref=get_customer_payment_sales_person_ref_info($trans['type'],$trans['trans_no']); 
				$rep->TextCol(2, 3,$cust_payment_sp_ref['sales_person_ref']);
				
				$cust_payment=get_customer_payment_mode_info($trans['type'],$trans['trans_no']); 
				
				if($cust_payment['mode_of_payment']=='cheque'){
					$rep->TextCol(3, 4,$cust_payment['cheque_no']);
					$rep->DateCol(4, 5,	$cust_payment['date_of_issue'], true);
				}	
				else{
					$rep->TextCol(3, 4,$cust_payment['pymt_ref']);
					$rep->TextCol(4, 5,"");
				}
			}
			else{
				$rep->TextCol(2, 3,"");
				$rep->TextCol(3, 4,"");
				$rep->TextCol(4, 5,"");
			}

			$item[0] = $item[1] = 0.0;
			
			if ($trans['type'] == ST_CUSTCREDIT || $trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_BANKDEPOSIT)
				$trans['TotalAmount'] *= -1;
			
			if ($trans['TotalAmount'] > 0.0)
			{
				$item[0] = round2($trans['TotalAmount'] * $rate, $dec);
				$rep->AmountCol(5, 6, $item[0], $dec);
				$accumulate += $item[0];
			}
			else
			{
				$item[1] = round2(abs($trans['TotalAmount']) * $rate, $dec);
				$rep->AmountCol(6, 7, $item[1], $dec);
				$accumulate -= $item[1];
			}
			
			
			if (($trans['type'] == ST_JOURNAL && $item[0]) || $trans['type'] == ST_SALESINVOICE || $trans['type'] == ST_BANKPAYMENT)
				$item[2] = $item[0];
			else	
				$item[2] = -$item[1];
			
			if ($accumulate > 0.0)
			$rep->TextCol(7, 8, number_format2($accumulate, $dec)." Dr");	
			else
			$rep->TextCol(7, 8, number_format2(-$accumulate, $dec)." Cr");	
			
			for ($i = 0; $i < 3; $i++)
			{
				$total[$i] += $item[$i];
				$grandtotal[$i] += $item[$i];
			}
			
			
			
			if ($show_balance)
				$total[2] = $total[0] - $total[1];
			
			
			if ($trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_BANKDEPOSIT)
			{
				
				$com_res = get_comments_all($trans['type'],$trans['trans_no']);
				
				if($com_res['memo_']!=''){
				$rep->NewLine();
				$rep->TextCol(3, 7,	$com_res['memo_']);	
				}
			}
			
			
		}
		$rep->Line($rep->row - 8);
		$rep->NewLine(2);
		$rep->SetFont('helvetica', 'B', 9);
		$rep->TextCol(1, 5, _('** Sub Totals **  ').$myrow['name']);
		$rep->AmountCol(5, 6, $total[0]-$init[2], $dec);
        $rep->AmountCol(6, 7, $total[1], $dec);
		
		if ($total[2] > 0.0)
	    $rep->TextCol(7, 8, number_format2($total[2], $dec)." Dr");	
		else
		$rep->TextCol(7, 8, number_format2(-$total[2], $dec)." Cr");		
		
		
		$rep->SetFont('', '', 0);
   		$rep->Line($rep->row  - 4);
   		$rep->NewLine(2);
		
		
		
	}
	$rep->fontSize += 2;
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(3, 5, _('Grand Total'));
	$rep->fontSize -= 2;
	if ($show_balance)
		$grandtotal[2] = $grandtotal[0] - $grandtotal[1];
	//for ($i = 0; $i < 3; $i++)
	$rep->AmountCol(5, 6, $grandtotal[0], $dec);
    $rep->AmountCol(6, 7, $grandtotal[1], $dec);
	
	if ($grandtotal[2] > 0.0)
	$rep->TextCol(7, 8, number_format2($grandtotal[2], $dec)." Dr");	
	else
	$rep->TextCol(7, 8, number_format2(-$grandtotal[2], $dec)." Cr");	
	
	$rep->SetFont('', '', 0);
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    	$rep->End();
}

