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
$page_security = 'SA_CUSTPAYMREP';

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

print_customer_balances();

function get_open_balance($debtorno, $to, $folk=0)
{
	
	if($to)
		$to = date2sql($to);
	$sql = "SELECT SUM(IF(t.type = ".ST_SALESINVOICE." OR (t.type IN (".ST_JOURNAL." , ".ST_BANKPAYMENT.") AND t.ov_amount>0),
             -abs(IF(t.prep_amount, t.prep_amount, t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount + t.ov_roundoff)), 0)) AS charges,";

	$sql .= "SUM(IF(t.type != ".ST_SALESINVOICE." AND NOT(t.type IN (".ST_JOURNAL." , ".ST_BANKPAYMENT.") AND t.ov_amount>0),
             abs(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount + t.ov_roundoff) * -1, 0)) AS credits,";		

    $sql .= "SUM(IF(t.type != ".ST_SALESINVOICE." AND NOT(t.type IN (".ST_JOURNAL." , ".ST_BANKPAYMENT.")), t.alloc * -1, t.alloc)) AS Allocated,";

 	$sql .=	"SUM(IF(t.type = ".ST_SALESINVOICE." OR (t.type IN (".ST_JOURNAL." , ".ST_BANKPAYMENT.") AND t.ov_amount>0), 1, -1) *
			(IF(t.prep_amount, t.prep_amount, abs(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount + t.ov_roundoff)) - abs(t.alloc))) AS OutStanding
		FROM ".TB_PREF."debtor_trans t
    	WHERE t.debtor_no = ".db_escape($debtorno)
		." AND t.type <> ".ST_CUSTDELIVERY;
    if ($to)
    	$sql .= " AND t.tran_date < '$to'";
	
	if ($folk != 0)
			$sql .= " AND t.sales_person_id = ".db_escape($folk);

	$sql .= " GROUP BY debtor_no";

    $result = db_query($sql,"No transactions were returned");
    return db_fetch($result);
}

function get_transactions($debtorno, $from, $to, $folk=0)
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
 		IFNULL(alloc_from.amount, alloc_to.amount) AS Allocated,
 		((trans.type = ".ST_SALESINVOICE.")	AND trans.due_date < '$to') AS OverDue
     	FROM ".TB_PREF."debtor_trans trans
 			LEFT JOIN ".TB_PREF."voided voided ON trans.type=voided.type AND trans.trans_no=voided.id
 			LEFT JOIN $allocated_from ON alloc_from.trans_type = trans.type AND alloc_from.trans_no = trans.trans_no
 			LEFT JOIN $allocated_to ON alloc_to.trans_type = trans.type AND alloc_to.trans_no = trans.trans_no
			

     	WHERE trans.tran_date >= '$from'
 			AND trans.tran_date <= '$to'
 			AND trans.debtor_no = ".db_escape($debtorno)."
 			AND trans.type <> ".ST_CUSTDELIVERY."
 			AND ISNULL(voided.id)";
			
			if ($folk != 0)
			$sql .= " AND trans.sales_person_id = ".db_escape($folk);
		
     	$sql .=" ORDER BY trans.tran_date";
		
		//display_error($sql);
    return db_query($sql,"No transactions were returned");
}


function get_customer_payment_mode_info($type,$trans_no)
{
	$sql = "SELECT mode_of_payment,cheque_no,pymt_ref FROM ".TB_PREF."bank_trans 
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

function print_customer_balances()
{
    	global $path_to_root, $systypes_array;

    	$from = $_POST['PARAM_0'];
    	$to = $_POST['PARAM_1'];
		$grp_comp = $_POST['PARAM_2'];
    	$leg_grp = $_POST['PARAM_3'];
    	$cust_class = $_POST['PARAM_4'];
		$area = $_POST['PARAM_5']; 
    	$fromcust = $_POST['PARAM_6'];
		$folk = $_POST['PARAM_7'];
    	$show_balance = $_POST['PARAM_8'];
    	$currency = $_POST['PARAM_9'];
    	$no_zeros = $_POST['PARAM_10'];
    	$comments = $_POST['PARAM_11'];
	    $orientation = $_POST['PARAM_12'];
	    $destination = $_POST['PARAM_13'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
    $dec = user_price_dec();
	
	
	 if ($folk == ALL_NUMERIC)
        $folk = 0;
    if ($folk == 0)
        $salesfolk = _('All Sales Man');
     else
        $salesfolk = get_salesman_name($folk);
	
    if ($grp_comp == ALL_NUMERIC)
        $grp_comp = 0;
    if ($grp_comp == 0)
        $salesgrp_comp = _('All Group Companies');
     else
        $salesgrp_comp = get_group_company_name($grp_comp);
	
	 if ($leg_grp == ALL_NUMERIC)
        $leg_grp = 0;
    if ($leg_grp == 0)
        $salesleg_grp = _('All Legal Group');
     else
        $salesleg_grp = get_legal_group_name($leg_grp);
	
	 if ($cust_class == ALL_NUMERIC)
        $cust_class = 0;
    if ($cust_class == 0)
        $salescust_class = _('All Customer Class');
     else
        $salescust_class = get_customer_class_name($cust_class);
	
	if ($area == ALL_NUMERIC)
        $area = 0;

    if ($area == 0)
        $sarea = _('All Areas');
    else
        $sarea = get_area_name($area);


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

	$cols = array(0, 95, 140, 200,	250, 320, 385, 450,	515, 600);

	$headers = array(_('Trans Type'), _('#'), _('Date'), _('Due Date'), _('Debits'), _('Credits'),
		_('Allocated'), _('Outstanding'), _('Memo'));

	if ($show_balance)
		$headers[7] = _('Balance');
	$aligns = array('left',	'left',	'left',	'left',	'right', 'right', 'right', 'right', 'left');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
						2 => array('text' => _('Group Company'), 'from' => $salesgrp_comp, 	'to' => ''),
						3 => array('text' => _('Legal Group'), 'from' => $salesleg_grp, 	'to' => ''),
						4 => array('text' => _('Customer Class'), 'from' => $salescust_class, 	'to' => ''),
						5 => array('text' => _('Sales Areas'), 'from' => $sarea, 		'to' => ''),
    				    6 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						7 => array('text' => _('Sales Person'), 'from' => $salesfolk, 	'to' => ''),
    				    8 => array('text' => _('Show Balance'), 'from' => $sb,   	'to' => ''),
    				    9 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						10 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => ''));

    $rep = new FrontReport(_('Customer Balances'), "CustomerBalances", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$grandtotal = array(0,0,0,0);

	$sql = "SELECT m.debtor_no, m.name, m.curr_code FROM ".TB_PREF."debtors_master m, ".TB_PREF."cust_branch cb, ".TB_PREF."debtor_trans t
        WHERE m.debtor_no=cb.debtor_no AND t.debtor_no=m.debtor_no";
	
	
	if ($grp_comp != 0)
	{
		$sql .= " AND m.sale_cust_group_comp_id =".db_escape($grp_comp);
	}
	
	if ($leg_grp != 0)
	{
		$sql .= " AND m.legal_group_id =".db_escape($leg_grp);
	}
	
	if ($cust_class != 0)
	{
		$sql .= " AND m.sale_cust_class_id =".db_escape($cust_class);
	}
	
	if ($fromcust != ALL_TEXT)
	{
		$sql .= " AND m.debtor_no=".db_escape($fromcust);
	}
	
	if ($area != 0)
    {
       $sql .= " AND cb.area=".db_escape($area);
    }
	
	 if ($folk != 0 )
    {
        $sql .= " AND t.sales_person_id=".db_escape($folk);
    }
	
	$sql .= " Group By m.name ORDER BY m.name ";
	
	//display_error($sql);
	$result = db_query($sql, "The customers could not be retrieved");

	while ($myrow = db_fetch($result))
	{
		if (!$convert && $currency != $myrow['curr_code']) continue;
		
		$accumulate = 0;
		$rate = $convert ? get_exchange_rate_from_home_currency($myrow['curr_code'], Today()) : 1;
		$bal = get_open_balance($myrow['debtor_no'], $from, $folk);
		$init = array();

		$init[0] = round2(($bal != false ? abs($bal['charges']) : 0)*$rate, $dec);
		$init[1] = round2(($bal != false ? abs($bal['credits']) : 0)*$rate, $dec);
		$init[2] = round2(($bal != false ? $bal['Allocated'] : 0)*$rate, $dec);
		if ($show_balance)
		{
			$init[3] = $init[0] - $init[1];
			$accumulate += $init[3];
		}	
		else	
			$init[3] = round2(($bal != false ? $bal['OutStanding'] : 0)*$rate, $dec);

		$res = get_transactions($myrow['debtor_no'], $from, $to, $folk);
				
		if ($no_zeros && db_num_rows($res) == 0) continue;

		$rep->fontSize += 2;
		$rep->TextCol(0, 2, $myrow['name']);
		if ($convert)
			$rep->TextCol(2, 3,	$myrow['curr_code']);
		$rep->fontSize -= 2;
		$rep->TextCol(3, 4,	_("Open Balance"));
		$rep->AmountCol(4, 5, $init[0], $dec);
		$rep->AmountCol(5, 6, $init[1], $dec);
		$rep->AmountCol(6, 7, $init[2], $dec);
		$rep->AmountCol(7, 8, $init[3], $dec);
		$total = array(0,0,0,0);
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
                } else {
                    if (floatcmp(abs($trans['TotalAmount']), $trans['Allocated']) == 0) continue;
                }
            }
			$rep->NewLine(1, 2);
			$rep->TextCol(0, 1, $systypes_array[$trans['type']]);
			$rep->TextCol(1, 2,	$trans['reference']);
			$rep->DateCol(2, 3,	$trans['tran_date'], true);
			if ($trans['type'] == ST_SALESINVOICE)
				$rep->DateCol(3, 4,	$trans['due_date'], true);
			$item[0] = $item[1] = 0.0;
			if ($trans['type'] == ST_CUSTCREDIT || $trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_BANKDEPOSIT)
				$trans['TotalAmount'] *= -1;
			if ($trans['TotalAmount'] > 0.0)
			{
				$item[0] = round2($trans['TotalAmount'] * $rate, $dec);
				$rep->AmountCol(4, 5, $item[0], $dec);
				$accumulate += $item[0];
				$item[2] = round2($trans['Allocated'] * $rate, $dec);
			}
			else
			{
				$item[1] = round2(abs($trans['TotalAmount']) * $rate, $dec);
				$rep->AmountCol(5, 6, $item[1], $dec);
				$accumulate -= $item[1];
				$item[2] = round2($trans['Allocated'] * $rate, $dec) * -1;
			}
			$rep->AmountCol(6, 7, $item[2], $dec);
			if (($trans['type'] == ST_JOURNAL && $item[0]) || $trans['type'] == ST_SALESINVOICE || $trans['type'] == ST_BANKPAYMENT)
				$item[3] = $item[0] - $item[2];
			else	
				$item[3] = -$item[1] - $item[2];
			if ($show_balance){	
				$rep->AmountCol(7, 8, $accumulate, $dec);
			}else{	
				$rep->AmountCol(7, 8, $item[3], $dec);
			}
			
		$com_res = get_comments_all($trans['type'],$trans['trans_no']);
			
            $rep->TextCol(8, 9,	$com_res['memo_']);	
			
			for ($i = 0; $i < 4; $i++)
			{
				$total[$i] += $item[$i];
				$grandtotal[$i] += $item[$i];
			}
			if ($show_balance)
				$total[3] = $total[0] - $total[1];
			
			
			if ($trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_BANKDEPOSIT)
			{
				$rep->NewLine();
				
				$cust_payment=get_customer_payment_mode_info($trans['type'],$trans['trans_no']); 
				
				
				$rep->TextCol(0, 1,$cust_payment['mode_of_payment']);
				
				if($cust_payment['mode_of_payment']=='cheque'){
					$rep->TextCol(1, 2,$cust_payment['cheque_no']);
				}	
				else{
					$rep->TextCol(1, 2,$cust_payment['pymt_ref']);
				}
				
			}
			
		}
		$rep->Line($rep->row - 8);
		$rep->NewLine(2);
		$rep->TextCol(0, 3, _('Total'));
		for ($i = 0; $i < 4; $i++)
			$rep->AmountCol($i + 4, $i + 5, $total[$i], $dec);
   		$rep->Line($rep->row  - 4);
   		$rep->NewLine(2);
	}
	$rep->fontSize += 2;
	$rep->TextCol(0, 3, _('Grand Total'));
	$rep->fontSize -= 2;
	if ($show_balance)
		$grandtotal[3] = $grandtotal[0] - $grandtotal[1];
	for ($i = 0; $i < 4; $i++)
		$rep->AmountCol($i + 4, $i + 5, $grandtotal[$i], $dec);
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    	$rep->End();
}

