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
$page_security = 'SA_SALESMAN_AGING_REP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Aged Customer Balances
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//-----------------------------------------------------------------------------------------

print_aged_salesman_analysis();


function getTransactions($sales_person=0,$to,$leg_grp=0,$cust_class=0)
{

	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
		
	$past1 = get_company_pref('past_due_days');
	$past2 = 2 * $past1;
	$past3 = 3 * $past1;
	
	//ravi
	$sign = "IF(trans.type IN(".implode(',',  array(ST_CUSTCREDIT,ST_CUSTPAYMENT,ST_BANKDEPOSIT))."), -1, IF(trans.type=".ST_JOURNAL." AND trans.ov_amount<0,-1,1))";
	
	$value = "$sign*(IF(trans.prep_amount, trans.prep_amount,
		ABS(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + 
		trans.ov_discount + trans.ov_roundoff - (trans.alloc) )))";

	$due = "IF (trans.type=".ST_SALESINVOICE.", trans.due_date, trans.tran_date)";
	
	$sql = "SELECT debtor.name, debtor.curr_code,debtor.cust_code,
	    trans.debtor_no,trans.sales_person_id,salesman.salesman_name,
		Sum(IFNULL($value,0)) AS Balance,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0)) AS Due,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past1,$value,0)) AS Overdue1,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past2,$value,0)) AS Overdue2,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past3,$value,0)) AS Overdue3,
		SUM(trans.pdc_amt) AS pdc_amount
		FROM ".TB_PREF."debtor_trans trans,
			 ".TB_PREF."debtors_master debtor,
			".TB_PREF."salesman salesman
		WHERE trans.sales_person_id=salesman.salesman_code
		AND trans.debtor_no=debtor.debtor_no
		AND trans.type IN (".ST_JOURNAL.",".ST_BANKPAYMENT.",".ST_BANKDEPOSIT.",
		".ST_CUSTCREDIT.",".ST_CUSTPAYMENT.",".ST_SALESINVOICE.")
		AND trans.tran_date <= '$todate'";
	$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff) - (trans.pdc_amt + trans.alloc)) > ".FLOAT_COMP_DELTA;
	
	if ($sales_person != 0)
	       $sql .= " AND trans.sales_person_id=".db_escape($sales_person);	
	   
	if ($leg_grp != 0)
	{
		$sql .= " AND debtor.legal_group_id =".db_escape($leg_grp);
	}
	
	if ($cust_class != 0)
	{
		$sql .= " AND debtor.sale_cust_class_id =".db_escape($cust_class);
	}    
	   

	$sql .= " GROUP BY trans.sales_person_id,trans.debtor_no 
		ORDER BY trans.sales_person_id,trans.debtor_no";
		
	//display_error($sql);
				
    return db_query($sql,"No transactions were returned");

}



function get_invoices($sales_person=0,  $customer_id, $to)
{
	
	$todate = date2sql($to);
	$PastDueDays1 = get_company_pref('past_due_days');
	$PastDueDays2 = 2 * $PastDueDays1;
	$PastDueDays3 = 3 * $PastDueDays1;
	$sign = "IF(`type` IN(".implode(',',  array(ST_CUSTCREDIT,ST_CUSTPAYMENT,ST_BANKDEPOSIT,ST_CUSTPDC))."), -1, IF(type=".ST_JOURNAL." AND trans.ov_amount<0,-1,1))";

	$value = "$sign*(IF(trans.prep_amount, trans.prep_amount,
		ABS(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax 
		+ trans.ov_discount + trans.ov_roundoff  - (trans.alloc))))";

	$due = "IF (type=".ST_SALESINVOICE.", due_date, tran_date)";

	$sql = "SELECT type, reference, tran_date,
		$value as Balance,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0) AS Due,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $PastDueDays1,$value,0) AS Overdue1,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $PastDueDays2,$value,0) AS Overdue2,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) > $PastDueDays3,$value,0) AS Overdue3,
		trans.pdc_amt AS pdc_amount,trans.invoice_type
		FROM ".TB_PREF."debtor_trans trans,
			 ".TB_PREF."debtors_master debtor,
			 ".TB_PREF."salesman salesman
		WHERE trans.debtor_no=debtor.debtor_no
		AND trans.sales_person_id=salesman.salesman_code
		AND trans.type IN (".ST_JOURNAL.",".ST_BANKPAYMENT.",".ST_BANKDEPOSIT.",
		".ST_CUSTCREDIT.",".ST_CUSTPAYMENT.",".ST_SALESINVOICE.")
		AND trans.debtor_no = $customer_id 
		AND trans.sales_person_id = $sales_person 
		AND trans.tran_date <= '$todate'
	    AND ABS($value) > " . FLOAT_COMP_DELTA;
		
	$sql .= " ORDER BY tran_date";

    
  
	return db_query($sql, "The customer transactions could not be retrieved");
}


//--------------------------------------------------------------------------------

function print_aged_salesman_analysis()
{
    global $path_to_root, $systypes_array, $SysPrefs;

    $to          = $_POST['PARAM_0'];
	$folk        = $_POST['PARAM_1']; 
	$leg_grp     = $_POST['PARAM_2'];
    $cust_class  = $_POST['PARAM_3'];
	$currency    = $_POST['PARAM_4'];
	$summaryOnly = $_POST['PARAM_5'];
    $no_zeros    = $_POST['PARAM_6'];
    $comments    = $_POST['PARAM_7'];
	$orientation = $_POST['PARAM_8'];
	$destination = $_POST['PARAM_9'];
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	
	
	$orientation = ($orientation ? 'L' : 'L');
	
    	$dec = user_price_dec();
	

	if ($currency == ALL_TEXT)
	{
		$convert = true;
		$currency = _('Balances in Home Currency');
	}
	else
		$convert = false;
	
	if ($folk == ALL_NUMERIC)
        $folk = 0;
    if ($folk == 0)
        $salesfolk = _('All Sales Man');
     else
        $salesfolk = get_salesman_name($folk);

	if ($no_zeros) $nozeros = _('Yes');
	else $nozeros = _('No');
	
	if ($summaryOnly == 1)
		$summary = _('Summary Only');
	else
		$summary = _('Detailed Report');
	
	
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
	

	$PastDueDays1 = get_company_pref('past_due_days');
	$PastDueDays2 = 2 * $PastDueDays1;
	$PastDueDays3 = 3 * $PastDueDays1;
	$nowdue = "1-" . $PastDueDays1 . " " . _('Days');
	$pastdue1 = $PastDueDays1 + 1 . "-" . $PastDueDays2 . " " . _('Days');
	$pastdue2 = $PastDueDays2 + 1 . "-" . $PastDueDays3 . " " . _('Days');
	$pastdue3 = _('Over') . " " . $PastDueDays3 . " " . _('Days');

	
	
	$cols = array(0, 50, 160, 210, 260, 310, 360, 410, 460,530);
	
	$headers = array(_('Code'),	_('Account'), _('Current'), 
	$nowdue, $pastdue1, $pastdue2, $pastdue3, _('PDC Amount'), _('Net Balance'));
	
	$aligns = array('left',	'left',	'right', 'right', 'right', 'right', 'right',	
	'right','right');
	
	
    	$params =   array( 	0 => $comments,
    				1 => array('text' => _('End Date'), 'from' => $to, 'to' => ''),
					2 => array('text' => _('Sales folk'), 'from' => $salesfolk, 'to' => ''),
					3 => array('text' => _('Legal Group'), 'from' => $salesleg_grp, 'to' => ''),
					4 => array('text' => _('Customer Class'), 'from' => $salescust_class, 'to' => ''),
					5 => array('text' => _('Type'),		'from' => $summary,'to' => ''),
    				6 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
				    7 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => ''));

	
    $rep = new FrontReport(_('SalesManwise Aging Report'), 
	"SalesManwiseAgingReport", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$curr_total  = $nowdue_total = $pastdue1_total = $pastdue2_total = $pastdue3_total = 0;
    $curr_grand_total  = $nowdue_grand_total = $pastdue1_grand_total = 0;
	$pastdue2_grand_total = $pastdue3_grand_total = 0;
	$pdc_total = $pdc_grand_total = 0;
	$net_bal_total = $net_bal_grand_total = 0;
	
	$res = getTransactions($folk, $to, $leg_grp, $cust_class);
	

	while ($custrec=db_fetch($res))
	{
		
		if ($salesper != $custrec['salesman_name'])
		{
			if ($salesper != '')
			{
				$rep->NewLine(2, 3);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(0, 1, _('Total'));
				$rep->AmountCol(2, 3, $curr_total, $dec);
	            $rep->AmountCol(3, 4, $nowdue_total, $dec);
	            $rep->AmountCol(4, 5, $pastdue1_total, $dec);
	            $rep->AmountCol(5, 6, $pastdue2_total, $dec);
	            $rep->AmountCol(6, 7, $pastdue3_total, $dec);
	            $rep->AmountCol(7, 8, $pdc_total, $dec);
				
				$net_balance_total  = $net_bal_total-$pdc_total;
	
                if ($net_balance_total > 0.0)
		        $rep->TextCol(8, 9, number_format2($net_balance_total, $dec)." Dr");	
	            else
	            $rep->TextCol(8, 9, number_format2(-$net_balance_total, $dec)." Cr");	
				
	           
				$rep->SetFont('', '', 0);
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$rep->NewLine();
				
				$curr_total  = $nowdue_total = $pastdue1_total = $pastdue2_total = 0.0;$pastdue3_total = $pdc_total = $net_bal_total = 0.0;
				
			}
			$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 6, _('SalesMan : ').$custrec['salesman_name']);
			$rep->Text($ccol+40, str_pad('', 25, '_'));
			$rep->SetFont('', '', 0);
			$salesper = $custrec['salesman_name'];
			$rep->NewLine();
		}
		
		
		if (!$convert && $currency != $custrec['curr_code'])
			continue;

		if ($convert) $rate = get_exchange_rate_from_home_currency($custrec['curr_code'], $to);
		else $rate = 1.0;
		//$custrec = get_customer_details($myrow['debtor_no'], $to, $show_all);
		if (!$custrec)
			continue;
		$custrec['Balance'] *= $rate;
		$custrec['Due'] *= $rate;
		$custrec['Overdue1'] *= $rate;
		$custrec['Overdue2'] *= $rate;
		$custrec['Overdue3'] *= $rate;
		
		$str = array($custrec["Balance"] - $custrec["Due"],
			$custrec["Due"]-$custrec["Overdue1"],
			$custrec["Overdue1"]-$custrec["Overdue2"],
			$custrec["Overdue2"]-$custrec["Overdue3"],
			$custrec["Overdue3"],
			$custrec["Balance"]);
		if ($no_zeros && floatcmp(array_sum($str), 0) == 0) continue;

		if (!$summaryOnly)
		{
		 $rep->SetFont('helvetica', 'B', 9);	
		$rep->TextCol(0, 1, $custrec["cust_code"]);
		$rep->TextCol(1, 4, $custrec["name"]);
		  $rep->SetFont('', '', 0);
		$rep->NewLine();
		}
		
		
		
		if (!$summaryOnly)
		{
			$result = get_invoices($custrec['sales_person_id'], $custrec['debtor_no'], $to);
    		if (db_num_rows($result)==0)
				continue;
    		$rep->Line($rep->row + 4);
			while ($trans=db_fetch($result))
			{
				
				
				if($trans['type']==10){
				if($trans['invoice_type']=="SI")
				$inv_type = "SI";
			    else
				$inv_type = "SC";	 
			    }
			    else if($trans['type']==11){
				$inv_type = "SR";
			    }
			    else if($trans['type']==12){
				if($trans['bank_account']==1)
				$inv_type = "CR";
			    else
				$inv_type = "BR";	
			    }
			    else if($trans['type']==0 || $trans['type']==1 || $trans['type']==2){
				$inv_type = "";
			    }
				
				
				
				$trans['Balance'] *= $rate;
		        $trans['Due'] *= $rate;
		        $trans['Overdue1'] *= $rate;
		        $trans['Overdue2'] *= $rate;
		        $trans['Overdue3'] *= $rate;
				
				
				$rep->NewLine(1, 2);
				
				$rep->DateCol(0, 1, $trans['tran_date'], true, -2);
				$rep->TextCol(1, 2,	$inv_type." ".$trans['reference'], -2);
				$rep->AmountCol(2, 3, $trans["Balance"] - $trans["Due"], $dec);
		        $rep->AmountCol(3, 4, $trans["Due"]-$trans["Overdue1"], $dec);
		        $rep->AmountCol(4, 5, $trans["Overdue1"]-$trans["Overdue2"], $dec);
		        $rep->AmountCol(5, 6, $trans["Overdue2"]-$trans["Overdue3"], $dec);
		        $rep->AmountCol(6, 7, $trans["Overdue3"], $dec);
		        $rep->AmountCol(7, 8, $trans["pdc_amount"], $dec);
				$inv_net_balance = $trans["Balance"]-$trans["pdc_amount"];
		
		        if ($inv_net_balance > 0.0)
		        $rep->TextCol(8, 9, number_format2($inv_net_balance, $dec)." Dr");	
	            else
		        $rep->TextCol(8, 9, number_format2(-$inv_net_balance, $dec)." Cr");	
				
			}
			$rep->Line($rep->row - 8);
			$rep->NewLine(2);
		}
		
		if ($summaryOnly)
		{
		$rep->TextCol(0, 1, $custrec["cust_code"]);
		$rep->TextCol(1, 2, $custrec["name"]);
		}
		
		$rep->AmountCol(2, 3, $custrec["Balance"] - $custrec["Due"], $dec);
		$rep->AmountCol(3, 4, $custrec["Due"]-$custrec["Overdue1"], $dec);
		$rep->AmountCol(4, 5, $custrec["Overdue1"]-$custrec["Overdue2"], $dec);
		$rep->AmountCol(5, 6, $custrec["Overdue2"]-$custrec["Overdue3"], $dec);
		$rep->AmountCol(6, 7, $custrec["Overdue3"], $dec);
		$rep->AmountCol(7, 8, $custrec["pdc_amount"], $dec);
		
		$net_balance = $custrec["Balance"]-$custrec["pdc_amount"];
		
		if ($net_balance > 0.0)
		$rep->TextCol(8, 9, number_format2($net_balance, $dec)." Dr");	
	    else
		$rep->TextCol(8, 9, number_format2(-$net_balance, $dec)." Cr");	
		
		$rep->NewLine(1, 2);
		
		if (!$summaryOnly)
		{
		$rep->Line($rep->row + 4);
		$rep->NewLine(2);
		}
		
		$curr_total     += ($custrec["Balance"] - $custrec["Due"]);
		$nowdue_total   += ($custrec["Due"]-$custrec["Overdue1"]);
		$pastdue1_total += ($custrec["Overdue1"]-$custrec["Overdue2"]);
		$pastdue2_total += ($custrec["Overdue2"]-$custrec["Overdue3"]);
		$pastdue3_total += $custrec["Overdue3"];
		$pdc_total      += $custrec["pdc_amount"];
		$net_bal_total  += $custrec["Balance"];
		
		
		$curr_grand_total     += ($custrec["Balance"] - $custrec["Due"]);
		$nowdue_grand_total   += ($custrec["Due"]-$custrec["Overdue1"]);
		$pastdue1_grand_total += ($custrec["Overdue1"]-$custrec["Overdue2"]);
		$pastdue2_grand_total += ($custrec["Overdue2"]-$custrec["Overdue3"]);
		$pastdue3_grand_total += $custrec["Overdue3"];
		$pdc_grand_total      += $custrec["pdc_amount"];
		$net_bal_grand_total  += $custrec["Balance"];
		
	
	}
	
	if ($summaryOnly)
	{
    $rep->Line($rep->row  + 4);
    $rep->NewLine();
	}
	
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(0, 3, _('Total'));
	$rep->AmountCol(2, 3, $curr_total, $dec);
	$rep->AmountCol(3, 4, $nowdue_total, $dec);
	$rep->AmountCol(4, 5, $pastdue1_total, $dec);
	$rep->AmountCol(5, 6, $pastdue2_total, $dec);
	$rep->AmountCol(6, 7, $pastdue3_total, $dec);
	$rep->AmountCol(7, 8, $pdc_total, $dec);
	
	$net_balance_total  = $net_bal_total-$pdc_total;
	
    if ($net_balance_total > 0.0)
		$rep->TextCol(8, 9, number_format2($net_balance_total, $dec)." Dr");	
	else
	$rep->TextCol(8, 9, number_format2(-$net_balance_total, $dec)." Cr");	

	$rep->SetFont('', '', 0);
	$rep->NewLine(1);
	
   	$rep->Line($rep->row  - 4);
	$rep->NewLine(2);
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(0, 3, _('Grand Total'));
	$rep->AmountCol(2, 3, $curr_grand_total, $dec);
	$rep->AmountCol(3, 4, $nowdue_grand_total, $dec);
	$rep->AmountCol(4, 5, $pastdue1_grand_total, $dec);
	$rep->AmountCol(5, 6, $pastdue2_grand_total, $dec);
	$rep->AmountCol(6, 7, $pastdue3_grand_total, $dec);
	$rep->AmountCol(7, 8, $pdc_grand_total, $dec);
	
	
	
	$net_balance_grand_total  = $net_bal_grand_total-$pdc_grand_total;
	
    if ($net_balance_grand_total > 0.0)
		$rep->TextCol(8, 9, number_format2($net_balance_grand_total, $dec)." Dr");	
	else
	$rep->TextCol(8, 9, number_format2(-$net_balance_grand_total, $dec)." Cr");	
	
    $rep->SetFont('', '', 0);
	$rep->NewLine();
    $rep->End();
}
