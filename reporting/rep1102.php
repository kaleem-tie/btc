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
$page_security = 'SA_DEB_OUT_REP';

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

//---------------------------------------------------------------------------------

print_debtors_outstanding_report();

function get_transactions($to,$customer_id=null,$folk=0)
{
	
	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
	
	
	$sql = "SELECT trans.type, trans.reference,trans.tran_date,trans.invoice_type,
	        trans.bank_account,trans.lpo_no,trans.due_date,
	        IF(trans.prep_amount, trans.prep_amount, trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff)
			AS TotalAmount,
		    trans.alloc as settled_amt,trans.pdc_amt,trans.order_ 
		FROM ".TB_PREF."debtor_trans trans
		WHERE trans.type IN (".ST_JOURNAL.",".ST_BANKPAYMENT.",".ST_BANKDEPOSIT.",
		".ST_CUSTCREDIT.",".ST_CUSTPAYMENT.",".ST_SALESINVOICE.")
			AND trans.debtor_no = $customer_id 
			AND trans.tran_date <= '$todate'
	        AND trans.ov_amount!=0";
			
		$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff) - trans.alloc) > 0";	
			
    
	if ($folk != 0){
      $sql .= " AND trans.sales_person_id = ".db_escape($folk);
	}
		
	$sql .= " ORDER BY trans.tran_date";
	
	return db_query($sql, "The customer transactions could not be retrieved");
}


function get_sales_pdc_transactions($to,$customer_id=null)
{
	
	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
	
	
	$sql = "SELECT trans.type, trans.reference,trans.tran_date,trans.invoice_type,
	        trans.bank_account,trans.lpo_no,trans.due_date,
	        trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff	AS pdc_amount,
		    trans.alloc as settled_amt,trans.pdc_amt,trans.order_,
            trans.pdc_cheque_date,trans.pdc_cheque_no,trans.our_ref_no			
		FROM ".TB_PREF."debtor_trans trans
		WHERE trans.type IN (".ST_CUSTPDC.")
			AND trans.debtor_no = $customer_id 
			AND trans.tran_date <= '$todate'
	        AND trans.ov_amount!=0";
			
		$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount) - trans.alloc) > 0";	
			
		
			
	$sql .= " ORDER BY trans.tran_date";

	return db_query($sql, "The customer transactions could not be retrieved");
}

function get_sales_order_location($order_)
{
    $sql="select from_stk_loc from ".TB_PREF."sales_orders where order_no=".db_escape($order_);
	//display_error($sql);die;
    
    $res=db_query($sql,"No transactions were returned");
    
    $row=db_fetch_row($res);
    
    return $row[0];
}

//---------------------------------------------------------------------------------

function print_debtors_outstanding_report()
{
    	global $path_to_root, $systypes_array;

    	
    	$to          = $_POST['PARAM_0'];
    	$fromcust    = $_POST['PARAM_1'];
		$leg_grp     = $_POST['PARAM_2'];
		$cust_class  = $_POST['PARAM_3'];
		$grp_comp    = $_POST['PARAM_4'];
		$folk        = $_POST['PARAM_5']; 
	    $currency    = $_POST['PARAM_6'];
		$no_zeros    = $_POST['PARAM_7'];
    	$orientation = $_POST['PARAM_8'];
	    $destination = $_POST['PARAM_9'];
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	
	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
    	
	if ($folk == ALL_NUMERIC)
        $folk = 0;
    if ($folk == 0)
        $salesfolk = _('All Sales Man');
     else
        $salesfolk = get_salesman_name($folk);
	
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
	
	if ($grp_comp == ALL_NUMERIC)
        $grp_comp = 0;
    if ($grp_comp == 0)
        $salesgrp_comp = _('All Group Companies');
     else
        $salesgrp_comp = get_group_company_name($grp_comp);
	
	$dec = user_price_dec();
	
	if ($currency == ALL_TEXT)
    {
        $convert = true;
        $currency = _('Balances in Home Currency');
    }
    else
        $convert = false;
	
	
   
   $cols = array(0,50,100,150,190,240,290,345,400,450,510,560);

   $headers = array(_('Inv.Date'), _('Inv.No.'), _('Ref.No.'),  _('Location'),
   _('Inv.Amount(FC)'), _('Crncy'), _('Inv.Amount(RO)'), _('Settled Amt(RO)'),_('Inv.Balance(RO)'),_('Inv.Balance(FC)'),_('Due On'));
   
   $aligns = array('left',	'left',	'left',	'left', 
   'right', 'center','right', 'right', 'right', 'right', 'center');
   


    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Date'),  'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Legal Group'), 'from' => $salesleg_grp, 	'to' => ''),
						4 => array('text' => _('Customer Class'), 'from' => $salescust_class, 	'to' => ''),
						5 => array('text' => _('Group Company'), 'from' => $salesgrp_comp, 	'to' => ''),
						6 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						7 => array('text' => _('Sales Folk'), 'from' => $salesfolk,	'to' => ''));

    $rep = new FrontReport(_('Debtors Outstanding Report - Detailed'), 
	"DebtorsOutstandingReportDetailed", user_pagesize(), 8, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    //$rep->SetHeaderType('Header42');
    $rep->NewPage();
	
	$sql = "SELECT debtor_no, name, curr_code,address,cust_code FROM ".TB_PREF."debtors_master m WHERE 1=1";
	if ($fromcust != ALL_TEXT)
		$sql .= " AND m.debtor_no=".db_escape($fromcust);
	
	if ($leg_grp != 0)
	{
		$sql .= " AND m.legal_group_id =".db_escape($leg_grp);
	}
	
	if ($cust_class != 0)
	{
		$sql .= " AND m.sale_cust_class_id =".db_escape($cust_class);
	}
	
	if ($grp_comp != 0)
	{
		$sql .= " AND m.sale_cust_group_comp_id =".db_escape($grp_comp);
	}
	
	$sql .= " ORDER BY m.name";
	$result = db_query($sql, "The customers could not be retrieved");
	
	$grand_total = 0;
	while ($myrow = db_fetch($result))
	{
		
		if (!$convert && $currency != $myrow['curr_code'])
			continue;

	   if ($convert) $rate = get_exchange_rate_from_home_currency($myrow['curr_code'], $to);
		else $rate = 1.0;
		
		$res = get_transactions($to, $myrow['debtor_no'],$folk);
		
		//if ($no_zeros && db_num_rows($res) == 0) continue;
		if (db_num_rows($res) == 0) continue;
		
		if ($debtor != $myrow['name'])
		{
			$m=1;
			if ($debtor != '')
			{
			if ($destination!='1')	{
		     $rep->NewLine();
			}
		     $rep->NewPage();
			}
			$rep->SetFont('helvetica', 'B', 9);
			if ($destination!='1')
				$rep->NewLine();
			
			$rep->Text($ccol+40, $myrow['cust_code']);
			
			$rep->Text($ccol+100, _("M/s ").$myrow['name']);
			$rep->SetFont('', '', 0);
			$rep->NewLine();
			
			$m++;
			$debtor = $myrow['name'];
		}
		
		$rep->NewLine();
		$invoice_total = $balance = 0;
		
		while ($trans = db_fetch($res))
		{
			
			//if ($no_zeros) {
                //if ($show_balance) {
                    if ($trans['TotalAmount'] == 0) continue;
            //}
			
			
			
			
			$inv_type = "";
			
			$rep->DateCol(0, 1,	$trans['tran_date'], true);
			$rep->TextCol(1, 2,	$inv_type. "  ".$trans['reference']);
			$rep->TextCol(2, 3,	$trans['lpo_no']);
			
			$so_location = get_sales_order_location($trans['order_']);
			
			$rep->TextCol(3, 4,	$so_location);
			$rep->TextCol(4, 5,	"");
			$rep->TextCol(5, 6,	$myrow['curr_code']);
			
			$inv_amount_dr = $inv_amount_cr = 0.0;
			if ($trans['type'] == ST_CUSTCREDIT || $trans['type'] == ST_CUSTPAYMENT || 
			$trans['type'] == ST_BANKDEPOSIT || $trans['type'] == ST_CUSTPDC)
				$trans['TotalAmount'] *= -1;
			
			
			if ($trans['TotalAmount'] > 0.0)
			{
				$inv_amount_dr = round2($trans['TotalAmount'] * $rate, $dec);
				$rep->TextCol(6, 7, number_format2($inv_amount_dr, $dec)." Dr");
				
				
				$allocated_amt = round2($trans['settled_amt'] * $rate, $dec);
			}
			else
			{
				$inv_amount_cr = round2(abs($trans['TotalAmount']) * $rate, $dec);
				$rep->TextCol(6, 7, number_format2($inv_amount_cr, $dec)." Cr");
				//$balance = $inv_amount_cr-$trans['settled_amt'];
				
				$allocated_amt = round2($trans['settled_amt'] * $rate, $dec) * -1;
			}
			
			$rep->AmountCol(7, 8, $trans['settled_amt'], $dec);
			
			
			if (($trans['type'] == ST_JOURNAL && $inv_amount_dr) || $trans['type'] == ST_SALESINVOICE || $trans['type'] == ST_BANKPAYMENT)
				$inv_balance = $inv_amount_dr - $allocated_amt;
			else	
				$inv_balance = -$inv_amount_cr - $allocated_amt;
			
			if($inv_balance > 0.0){
			$rep->TextCol(8, 9, number_format2($inv_balance, $dec)." Dr");
			$balance += $inv_balance;
			}
		    else{
			$rep->TextCol(8, 9, number_format2(-$inv_balance, $dec)." Cr");	
			$balance -= -$inv_balance;
			}
		
		    
			$rep->TextCol(9, 10,	"");
			if ($trans['type'] == ST_SALESINVOICE)
			$rep->DateCol(10, 11,	$trans['due_date'], true);
			
			
			$rep->NewLine();
			
			$invoice_total = $balance;
			
			//$pdc_total += $trans['pdc_amt'];
		}
         
		 
		 $pdc_res = get_sales_pdc_transactions($to, $myrow['debtor_no']);
		 
		 $rep->Text($ccol+40, str_pad('', 200, '_'));
       	 $rep->NewLine();
		 $rep->SetFont('helvetica', 'B', 9);
		 $rep->Text($ccol + 400,  $myrow['cust_code']." "._("Total"));
		 
		 if($invoice_total<0.0)	
		 $rep->TextCol(8, 9, number_format2(-$invoice_total, $dec)." Cr");
	     else
		 $rep->TextCol(8, 9, number_format2($invoice_total, $dec)." Dr");	 
			 
	 
		 $rep->NewLine(1);
		 $rep->Text($ccol + 40,  _("PDC's"));
		 $rep->NewLine();
		 $rep->Text($ccol+40, str_pad('', 140, '_'));
		 $rep->NewLine();
		 $rep->Text($ccol + 40,   _("Doc Date"));
		 $rep->Text($ccol + 120,  _("Doc No"));
		 $rep->Text($ccol + 220,  _("Chq No."));
		 $rep->Text($ccol + 340,  _("Chq Date "));
		 $rep->Text($ccol + 420,  _("Ref No."));
		 $rep->Text($ccol + 520,  _("Ref Date"));
		 $rep->Text($ccol + 630,  _("Amount"));
		 $rep->NewLine();
		 $rep->Text($ccol+40, str_pad('', 140, '_'));
		 $rep->NewLine();
	     $rep->SetFont('', '', 0);
		 
		 $pdc_total = $net_balance_total = 0;
		 while ($pdc = db_fetch($pdc_res))
		{
			
		 $rep->Text($ccol + 40,   sql2date($pdc['tran_date']));
		 $rep->Text($ccol + 120,  $pdc['reference']);
		 $rep->Text($ccol + 220,  $pdc['pdc_cheque_no']);
		 $rep->Text($ccol + 340,  sql2date($pdc['pdc_cheque_date']));
		 $rep->Text($ccol + 420,  $pdc['our_ref_no']);
		 $rep->Text($ccol + 520,  sql2date($pdc['tran_date']));
		 $rep->Text($ccol + 630,  number_format2($pdc['pdc_amount'],$dec));
		 $rep->NewLine();
		 $pdc_total += $pdc['pdc_amount'];
		 
		}	
		
		 $rep->Text($ccol+40, str_pad('', 140, '_'));
		 $rep->NewLine();
		 $rep->SetFont('helvetica', 'B', 9);
		 $rep->Text($ccol + 40,  _("Post dated cheques"));
		 $rep->Text($ccol + 630,  number_format2($pdc_total,$dec). " Cr" );
		 $rep->SetFont('', '', 0);
		 $rep->NewLine();
		 $rep->Text($ccol+40, str_pad('', 140, '_'));
		 $rep->NewLine();
		 $net_balance_total = $invoice_total - $pdc_total;
		 $rep->SetFont('helvetica', 'B', 9);
		 $rep->Text($ccol + 40,  _("Net Balance"));
		 
		 if($net_balance_total<0.0)	
		 $rep->Text($ccol + 630,  number_format2(-$net_balance_total,$dec)." Cr");
	     else
		 $rep->Text($ccol + 630,  number_format2($net_balance_total,$dec)." Dr");	 
		 
	 
		 $rep->SetFont('', '', 0);
		 $rep->NewLine();
		 
		 $grand_total += $net_balance_total;
	}
	
	  $rep->NewLine(2);
	  $rep->Text($ccol+40, str_pad('', 140, '_'));
	  $rep->NewLine();
	  $rep->SetFont('helvetica', 'B', 9);
	  $rep->Text($ccol + 40,  _("Grand Total"));
	  
	  if($grand_total<0.0)	
	  $rep->Text($ccol + 630,  number_format2(-$grand_total,$dec)." Cr");
      else
	  $rep->Text($ccol + 630,  number_format2($grand_total,$dec)." Dr");	  
  
	  $rep->SetFont('', '', 0);
	  $rep->NewLine();
	
    $rep->NewLine();
    $rep->End();

}