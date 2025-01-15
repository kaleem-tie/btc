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
$page_security = 'SA_SALESMAN_OUTSTAND_REP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Inventory Sales Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

//----------------------------------------------------------------------------------------------------

print_salesman_outstanding_register();

function getTransactions($sales_person=0,$to,$leg_grp=0,$cust_class=0)
{
	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);

	$sql = "SELECT DISTINCT trans.type,trans.invoice_type,
	        trans.tran_date,trans.reference,
			trans.alloc,trans.pdc_amt,
	        cust.cust_code,
			cust.name AS DebtorName,
			cust.curr_code,
			IF(trans.prep_amount, trans.prep_amount, trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff)
			AS TotalAmount,
			trans.type,
			salesman.salesman_name
		FROM ".TB_PREF."debtor_trans trans,
			 ".TB_PREF."debtors_master cust,
			".TB_PREF."salesman salesman
		WHERE trans.sales_person_id=salesman.salesman_code
		AND trans.debtor_no=cust.debtor_no
		AND trans.type IN (".ST_JOURNAL.",".ST_BANKPAYMENT.",".ST_BANKDEPOSIT.",
		".ST_CUSTCREDIT.",".ST_CUSTPAYMENT.",".ST_SALESINVOICE.")
		AND trans.ov_amount!=0
		AND trans.tran_date <= '$todate'";
	
	   
	$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff) - trans.alloc) > 0";
	
	if ($sales_person != 0)
	       $sql .= " AND trans.sales_person_id=".db_escape($sales_person);	
	   
	if ($leg_grp != 0)
	{
		$sql .= " AND cust.legal_group_id =".db_escape($leg_grp);
	}
	
	if ($cust_class != 0)
	{
		$sql .= " AND cust.sale_cust_class_id =".db_escape($cust_class);
	}   
		
		
	$sql .= " ORDER BY salesman.salesman_code, trans.debtor_no,cust.name,trans.tran_date";
		
	return db_query($sql, "Error getting order details");
}


//----------------------------------------------------------------------------------------------------

function print_salesman_outstanding_register()
{
    global $path_to_root;

	$to           = $_POST['PARAM_0'];
    $sales_person = $_POST['PARAM_1'];
	$leg_grp      = $_POST['PARAM_2'];
    $cust_class   = $_POST['PARAM_3'];
	$comments     = $_POST['PARAM_4'];
	$orientation  = $_POST['PARAM_5'];
	$destination  = $_POST['PARAM_6'];
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
    $dec = user_price_dec();


	if ($sales_person == ALL_NUMERIC)
		$sales_person = 0;
	if ($sales_person == 0)
		$sales_person_name = _('All Sales person');
	else
		$sales_person_name = get_salesman_name($sales_person);
	
	
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
	
	
    $headers2 = array(_('Code'), _('Account'));
	
	$cols = array(0, 60, 180, 240,310,380,460,540);

	$headers = array(_('Inv.Date'), _('Inv.No.'), _('Ref.No.'), _('Inv. Amount'), 
	_('Settled Amt'), _('Inv. Balance'), _('Balance'));
	
	$aligns = array('left',	'left',	'left',	 'right', 'right', 'right', 'right');
	
	
	$aligns2 = $aligns;

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Sales Person'), 'from' => $sales_person_name, 
						'to' => ''),
						3 => array('text' => _('Legal Group'), 'from' => $salesleg_grp, 'to' => ''),
					    4 => array('text' => _('Customer Class'), 'from' => $salescust_class, 'to' => ''));

    $rep = new FrontReport(_('SalesManwise Outstanding Register - Detailed'), 
	"SalesManwiseOutstandingRegisterDetailed", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$cols2 = $cols;

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
    $rep->NewPage();

	$res = getTransactions($sales_person, $to, $leg_grp, $cust_class);

	
	$total = $total_cust_close_bal = $grandtotal = $balance = $sp_grand_total = 
	$sp_grand_bal_total = 0.0;
	
	$salesper = $customer_name = $cust_names = '';
	
	
	$pdc_total = $net_balance_total = 0;
	while ($trans=db_fetch($res))
	{
		
		

	    if ($trans['curr_code']!='OMR') 
		$rate = get_exchange_rate_from_home_currency($trans['curr_code'], $to);
		else $rate = 1.0;
		
		
		if ($customer_name != $trans['DebtorName'])
		{
			
			if ($customer_name != '')
			{
				
				$rep->NewLine(2, 3);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(2, 6, _('Closing Balances :'));
				
				if($total_cust_close_bal > 0.0){
                $rep->TextCol(6, 7, number_format2($total_cust_close_bal, $dec)." Dr");
		        }
                 else{
                $rep->TextCol(6, 7, number_format2(-$total_cust_close_bal, $dec)." Cr");	
                }
				
				$rep->NewLine();
		        $rep->TextCol(2, 6, _('Post dated cheques total :'));
		        $rep->TextCol(6, 7,  number_format2($pdc_total,$dec). " Cr" );
				$rep->NewLine();
		        $net_balance_total = $total_cust_close_bal - $pdc_total;
				$rep->TextCol(2, 6, _('Net Balance :'));
				
				if($net_balance_total > 0.0){
                $rep->TextCol(6, 7, number_format2($net_balance_total, $dec)." Dr");
		        }
                 else{
                $rep->TextCol(6, 7, number_format2(-$net_balance_total, $dec)." Cr");	
                }
		        $rep->SetFont('', '', 0);
				$sp_grand_total += $net_balance_total;
				$sp_grand_bal_total += $net_balance_total;
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$total_cust_close_bal = $pdc_total = $net_balance_total = $balance = 0.0;
				
			}
			$customer_name = $trans['DebtorName'];
			$customer_code = $trans['cust_code'];
		}
		
		
		if ($salesper != $trans['salesman_name'])
		{
			if ($salesper != '')
			{
				$rep->NewLine(2, 3);
				$rep->SetFont('helvetica', 'B', 9);
				
				//$net_balance_total = $total_cust_close_bal - $pdc_total;
				
				//$rep->TextCol(0, 1, _('Total SP'));
				//$rep->TextCol(1, 4, $salesper);
				//$rep->AmountCol(5, 6, $net_balance_total, $dec);
				//$rep->AmountCol(6, 7, $net_balance_total, $dec);
				$rep->SetFont('', '', 0);
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$rep->NewLine();
				//$sp_grand_total = $sp_grand_bal_total  = 0.0;
			}
			$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 6, _('SalesMan : ').$trans['salesman_name']);
			$rep->Text($ccol+40, str_pad('', 25, '_'));
			$rep->SetFont('', '', 0);
			$salesper = $trans['salesman_name'];
			$rep->NewLine();
		}
		
		 if ($cust_names != $trans['DebtorName'])
		{
			
			$rep->TextCol(0, 6, $trans['cust_code']." - ".$trans['DebtorName']);
			$cust_names = $trans['DebtorName'];
			$rep->NewLine();
		}	
		
		
		$inv_type = "";
		
		  
		
		
		
		$rep->DateCol(0, 1,	$trans['tran_date'], true);
        $rep->TextCol(1, 2,	$inv_type. "  ".$trans['reference']);
		$rep->TextCol(2, 3,	"");
		
		
		$inv_amount_dr = $inv_amount_cr = 0.0;
		if ($trans['type'] == ST_CUSTCREDIT || $trans['type'] == ST_CUSTPAYMENT || 
			$trans['type'] == ST_BANKDEPOSIT || $trans['type'] == ST_CUSTPDC)
				$trans['TotalAmount'] *= -1;
				
				
		if ($trans['TotalAmount'] > 0.0)
		{
				$inv_amount_dr = round2($trans['TotalAmount'] * $rate, $dec);
				$rep->TextCol(3, 4, number_format2($inv_amount_dr, $dec)." Dr");
				$allocated_amt = round2($trans['alloc'] * $rate, $dec);
				//$balance += $item[0];
		}
		else
		{
				$inv_amount_cr = round2(abs($trans['TotalAmount']) * $rate, $dec);
				$rep->TextCol(3, 4, number_format2($inv_amount_cr, $dec)." Cr");
				$allocated_amt = round2($trans['alloc'] * $rate, $dec) * -1;
				//$balance -= $inv_amount_cr;
		}
					
		$rep->AmountCol(4, 5, $trans['alloc'], $dec);	

        if (($trans['type'] == ST_JOURNAL && $inv_amount_dr) || $trans['type'] == ST_SALESINVOICE || $trans['type'] == ST_BANKPAYMENT)
				$inv_balance = $inv_amount_dr - $allocated_amt;
		else	
				$inv_balance = -$inv_amount_cr - $allocated_amt;
			
		if($inv_balance > 0.0){
			$rep->TextCol(5, 6, number_format2($inv_balance, $dec)." Dr");
			$balance += $inv_balance;
		}
		else{
			$rep->TextCol(5, 6, number_format2(-$inv_balance, $dec)." Cr");	
			$balance -= -$inv_balance;
		}	

        if($balance > 0.0){
        $rep->TextCol(6, 7, number_format2($balance, $dec)." Dr");
		}
        else{
        $rep->TextCol(6, 7, number_format2(-$balance, $dec)." Cr");	
        }			
		
		
	    $rep->NewLine();
		
		$total_cust_close_bal = $balance;
		$total = $balance;
		$pdc_total += $trans['pdc_amt'];
	}
	
	


	if ($customer_name != '')
	{
		$rep->NewLine(2, 3);
		$rep->TextCol(0, 1, _('Total'));
		$rep->SetFont('helvetica', 'B', 9);
		$rep->TextCol(2, 6, _('Closing Balances :'));
		
		if($total_cust_close_bal > 0.0){
        $rep->TextCol(6, 7, number_format2($total_cust_close_bal, $dec)." Dr");
		}
        else{
        $rep->TextCol(6, 7, number_format2(-$total_cust_close_bal, $dec)." Cr");	
        }
		
		
		$rep->NewLine();
		$rep->TextCol(2, 6, _('Post dated cheques total :'));
		$rep->TextCol(6, 7,  number_format2($pdc_total,$dec). " Cr" );
		$rep->NewLine();
		$net_balance_total = $total_cust_close_bal - $pdc_total;
		$rep->TextCol(2, 6, _('Net Balance :'));
		
		
		if($net_balance_total > 0.0){
        $rep->TextCol(6, 7, number_format2($net_balance_total, $dec)." Dr");
		}
        else{
        $rep->TextCol(6, 7, number_format2(-$net_balance_total, $dec)." Cr");	
        }
		
		
		$rep->SetFont('', '', 0);
		$rep->Line($rep->row - 2);
		$rep->NewLine();
		
	 $sp_grand_total += $net_balance_total;
	 $sp_grand_bal_total += $net_balance_total;
	}
	
	
	
	
	/*
	$rep->TextCol(0, 1, _('Total'));
	$rep->TextCol(1, 6, $salesper);
	$rep->TextCol(6, 7,  number_format2($sp_grand_total,$dec). " Dr" );
	
	
	$rep->Line($rep->row - 2);
	*/
	$rep->SetFont('helvetica', 'B', 9);
	$rep->NewLine();
	$rep->TextCol(4, 6, _('Grand Total'));
	
	
	if($sp_grand_bal_total > 0.0){
        $rep->TextCol(6, 7, number_format2($sp_grand_bal_total, $dec)." Dr");
	}
    else{
        $rep->TextCol(6, 7, number_format2(-$sp_grand_bal_total, $dec)." Cr");	
    }
	$rep->SetFont('', '', 0);

	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

