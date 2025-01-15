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
$page_security = 'SA_SUPP_PAYABLES_LEDGER_REP';

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

//----------------------------------------------------------------------------------------

print_payables_ledger();

function get_open_balance($supplier_id, $to)
{
    if ($to)
        $to = date2sql($to);

    $sql = "SELECT SUM(IF(t.type = ".ST_SUPPINVOICE." OR (t.type IN (".ST_JOURNAL." , ".ST_BANKDEPOSIT.") AND t.ov_amount>0),
        -abs(t.ov_amount + t.ov_gst + t.ov_discount + t.ov_discount+t.freight_cost+t.additional_charges+t.packing_charges+t.other_charges+t.freight_tax+t.additional_tax+t.packing_tax+t.other_tax+t.ov_roundoff), 0)) AS charges,";

    $sql .= "SUM(IF(t.type != ".ST_SUPPINVOICE." AND NOT(t.type IN (".ST_JOURNAL." , ".ST_BANKDEPOSIT.") AND t.ov_amount>0),
        abs(t.ov_amount + t.ov_gst + t.ov_discount + t.ov_discount+t.freight_cost+t.additional_charges+t.packing_charges+t.other_charges+t.freight_tax+t.additional_tax+t.packing_tax+t.other_tax+t.ov_roundoff) * -1, 0)) AS credits,";

    $sql .= "SUM(IF(t.type != ".ST_SUPPINVOICE." AND NOT(t.type IN (".ST_JOURNAL." , ".ST_BANKDEPOSIT.")), t.alloc * -1, t.alloc)) 
        AS Allocated,";

    $sql .= "SUM(IF(t.type = ".ST_SUPPINVOICE.", 1, IF(t.type = ".ST_JOURNAL." AND t.ov_amount>0, 1, -1)) *
        (abs(t.ov_amount + t.ov_gst + t.ov_discount + t.ov_discount+t.freight_cost+t.additional_charges+t.packing_charges+t.other_charges+t.freight_tax+t.additional_tax+t.packing_tax+t.other_tax+t.ov_roundoff) - abs(t.alloc))) AS OutStanding
        FROM ".TB_PREF."supp_trans t
        WHERE t.supplier_id = ".db_escape($supplier_id);
		
   
	if ($to)
        $sql .= " AND t.tran_date < '$to'";
    	
	$sql .= " GROUP BY supplier_id";
	

    $result = db_query($sql,"No transactions were returned");
    return db_fetch($result);
}

function getTransactions($supplier_id, $from, $to)
{
	$from = date2sql($from);
	$to = date2sql($to);

    $sql = "SELECT *,
				(ov_amount + ov_gst + ov_discount + additional_charges + packing_charges + other_charges + freight_cost + freight_tax + additional_tax + packing_tax + other_tax+ov_roundoff) AS TotalAmount,
				alloc AS Allocated,
				((type = ".ST_SUPPINVOICE.") AND due_date < '$to') AS OverDue
   			FROM ".TB_PREF."supp_trans
   			WHERE tran_date >= '$from' AND tran_date <= '$to' 
			AND type <> ".ST_SUPPPDC."
    		AND supplier_id = '$supplier_id' AND ov_amount!=0";
							
    		$sql .= "  ORDER BY tran_date";

    $TransResult = db_query($sql,"No transactions were returned");

    return $TransResult;
}


function get_supplier_payment_mode_info($type,$trans_no)
{
	$sql = "SELECT mode_of_payment,cheque_no,pymt_ref,date_of_issue FROM ".TB_PREF."bank_trans 
	WHERE type=22 and trans_no=".db_escape($trans_no)."";
	
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


//-------------------------------------------------------------------------------------------

function print_payables_ledger()
{
    	global $path_to_root, $systypes_array;

    	$from        = $_POST['PARAM_0'];
    	$to          = $_POST['PARAM_1'];
    	$fromsupp    = $_POST['PARAM_2'];
		$reg_type    = $_POST['PARAM_3'];
    	$currency    = $_POST['PARAM_4'];
    	$no_zeros    = $_POST['PARAM_5'];
    	$comments    = $_POST['PARAM_6'];
	    $orientation = $_POST['PARAM_7'];
	    $destination = $_POST['PARAM_8'];
		
		$show_balance = 1;
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	
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
	
	
	 $headers2 = array(_('Code'), _('Account Name'), _(''), _('Narration'));

	$cols = array(0, 60, 130, 190, 260, 300, 390, 460, 540);

	$headers = array(_('Date'), _('Doc No.'), _('Ref No.'), _('Chq No.'), _('Chq Date'), 
	_('Debit(RO)'), _('Credit(RO)'),_('Balance(RO)'));
	
	$aligns = array('left',	'left',	'left',	'left',	'left', 'right', 'right', 'right');
	
	$aligns2 = $aligns;

    $params =   array( 	0 => $comments,
    			1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    			2 => array('text' => _('Supplier'), 'from' => $supp, 'to' => ''),
				3 => array('text' => _('Type'), 'from' => $rg_type,'to' => ''),
    			4 => array(  'text' => _('Currency'),'from' => $currency, 'to' => ''),
				5 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => ''));

    $rep = new FrontReport(_('Payables Ledger'), "PayablesLedger", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$cols2 = $cols;
	
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
    $rep->NewPage();

	
	
	$grandtotal = array(0,0,0);

	$sql = "SELECT supplier_id, supp_name AS name, curr_code, supp_code FROM ".TB_PREF."suppliers 
	WHERE 1=1";
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
		$accumulate = 0;
		$rate = $convert ? get_exchange_rate_from_home_currency($myrow['curr_code'], Today()) : 1;
		$bal = get_open_balance($myrow['supplier_id'], $from);
		$init = array();
		$init[1] = round2(($bal != false ? abs($bal['charges']) : 0)*$rate, $dec);
		$init[0] = round2(($bal != false ? abs($bal['credits']) : 0)*$rate, $dec);
		
		
		if ($show_balance)
		{
			$init[2] = $init[1] - $init[0];
			$accumulate += $init[2];
		}	
		else	
			$init[2] = round2(($bal != false ? $bal['OutStanding'] : 0)*$rate, $dec);

		$res = getTransactions($myrow['supplier_id'], $from, $to);
		if ($no_zeros && db_num_rows($res) == 0) continue;

		
		$rep->SetFont('helvetica', 'B', 9);
		$rep->fontSize += 2;
		$rep->TextCol(0, 4, $myrow['supp_code']." - ".$myrow['name']);
		$rep->fontSize -= 2;
		if ($convert)
			$rep->TextCol(4, 5,	$myrow['curr_code']);
		
		$rep->TextCol(5, 6,	_("Opening Balance"));
		
		if ($init[2] > 0.0)
		{
		    $rep->TextCol(7, 8, number_format2($init[2], $dec)." Cr");
		}
		else{
			$rep->TextCol(7, 8, number_format2($init[2], $dec)." Dr");
		}	
		$rep->SetFont('', '', 0);
		
		
		$total = array(0,0,0);
		for ($i = 0; $i < 4; $i++)
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
			
			
			
			$rep->NewLine(1, 2);
			
			$rep->DateCol(0, 1,	$trans['tran_date'], true);
			$rep->TextCol(1, 2,	$inv_type. "  ".$trans['reference']);
			$rep->TextCol(2, 3, $trans['supp_reference']);
			
			if ($trans['type'] == ST_SUPPAYMENT || $trans['type'] == ST_BANKDEPOSIT)
			{
				
				$supp_payment=get_supplier_payment_mode_info($trans['type'],$trans['trans_no']); 
				
				if($supp_payment['mode_of_payment']=='cheque'){
					$rep->TextCol(3, 4,$supp_payment['cheque_no']);
					$rep->DateCol(4, 5,	$supp_payment['date_of_issue'], true);
				}	
				else{
					$rep->TextCol(3, 4,"");
					$rep->TextCol(4, 5,"");
				}
			}
			else{
				$rep->TextCol(3, 4,"");
				$rep->TextCol(4, 5,"");
			}

			$item[0] = $item[1] = 0.0;
			
			
			if ($trans['TotalAmount'] > 0.0)
			{
				$item[1] = round2(abs($trans['TotalAmount']) * $rate, $dec);
				$rep->AmountCol(6, 7, $item[1], $dec);
				$accumulate += $item[1];
			}
			else
			{
				$item[0] = round2(abs($trans['TotalAmount']) * $rate, $dec);
				$rep->AmountCol(5, 6, $item[0], $dec);
				$accumulate -= $item[0];
			}
			
			
			if ($trans['TotalAmount'] > 0.0)
				$item[2] = $item[1];
			else	
				$item[2] = -$item[0];
			
			
			
			if ($accumulate > 0.0)
			$rep->TextCol(7, 8, number_format2($accumulate, $dec)." Cr");	
			else
			$rep->TextCol(7, 8, number_format2(-$accumulate, $dec)." Dr");	
			
			for ($i = 0; $i < 3; $i++)
			{
				$total[$i] += $item[$i];
				$grandtotal[$i] += $item[$i];
			}
			
			if ($show_balance)
				$total[2] = $total[0] - $total[1];
			
			if ($trans['type'] == ST_SUPPAYMENT || $trans['type'] == ST_BANKDEPOSIT)
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

