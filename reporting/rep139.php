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
$page_security = 'SA_BILLWISE_MATCH_DETAILS_REP';

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

//--------------------------------------------------------------------------------------------

print_billwise_matching_details();


function get_transactions($debtorno, $from, $to)
{
	$from = date2sql($from);
	$to = date2sql($to);

 	

     $sql = "SELECT trans.*,
		IF(trans.prep_amount, trans.prep_amount, trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff)
			AS InvAmount,trans.alloc as settled_amt,trans.pdc_amt
     	FROM ".TB_PREF."debtor_trans trans
 			LEFT JOIN ".TB_PREF."voided voided ON trans.type=voided.type 
			AND trans.trans_no=voided.id
     	WHERE trans.tran_date >= '$from'
 			AND trans.tran_date <= '$to'
 			AND trans.debtor_no = ".db_escape($debtorno)."
 			AND trans.type = ".ST_SALESINVOICE."
 			AND ISNULL(voided.id)";
		
     	$sql .=" ORDER BY trans.tran_date";
		
    return db_query($sql,"No transactions were returned");
}



function get_allocatable_vouchers_from_cust_transactions($customer_id, $trans_no=null, 
$type=null)
{

	$sql = "SELECT
		trans.type,
		trans.trans_no,
		trans.reference,
		trans.tran_date,
		debtor.name AS DebtorName, 
		debtor.curr_code,
		ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount+ov_roundoff AS RecdTotal,
		trans.alloc,
		trans.due_date,
		debtor.address,
		trans.version,
		amt,
		trans.debtor_no,
		trans.sales_person_ref,
		trans.invoice_type
	 FROM  ".TB_PREF."debtor_trans as trans,"
			.TB_PREF."debtors_master as debtor,"
			.TB_PREF."cust_allocations as alloc
	 WHERE trans.debtor_no=debtor.debtor_no
			AND trans.trans_no = alloc.trans_no_from
			AND trans.type = alloc.trans_type_from
			AND trans.debtor_no = alloc.person_id
			AND trans.ov_amount!=0";

	if ($trans_no != null and $type != null)
	{
		$sql .=	" AND alloc.trans_no_to=".db_escape($trans_no)."
		  		  AND alloc.trans_type_to=".db_escape($type);
	}
	else
	{
		$sql .= " AND round(abs(ov_amount)+ov_gst+ov_freight+ov_freight_tax+ov_discount+ov_roundoff-alloc,6) > 0
		AND trans.type NOT IN (".implode(',',array(ST_CUSTPAYMENT,ST_BANKDEPOSIT,ST_CUSTCREDIT,ST_CUSTDELIVERY)).")";
		$sql .= " GROUP BY type, trans_no";
	}

	if($customer_id)
		$sql .= " AND trans.debtor_no=".db_escape($customer_id);
		
	

	return db_query($sql." ORDER BY trans_no", "Cannot retreive alloc to transactions");
}


function get_allocatable_pdc_vouchers_from_cust_transactions($customer_id, $trans_no=null, 
$type=null)
{

	$sql = "SELECT
		trans.type,
		trans.trans_no,
		trans.reference,
		trans.tran_date,
		debtor.name AS DebtorName, 
		debtor.curr_code,
		ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount+ov_roundoff AS RecdTotal,
		trans.alloc,
		trans.due_date,
		debtor.address,
		trans.version,
		amt,
		trans.debtor_no,
		trans.sales_person_ref
	 FROM  ".TB_PREF."debtor_trans as trans,"
			.TB_PREF."debtors_master as debtor,"
			.TB_PREF."cust_pdc_allocations as alloc
	 WHERE trans.debtor_no=debtor.debtor_no
			AND trans.trans_no = alloc.trans_no_from
			AND trans.type = alloc.trans_type_from
			AND trans.debtor_no = alloc.person_id
			AND trans.ov_amount!=0";

	if ($trans_no != null and $type != null)
	{
		$sql .=	" AND alloc.trans_no_to=".db_escape($trans_no)."
		  		  AND alloc.trans_type_to=".db_escape($type);
	}
	else
	{
		$sql .= " AND round(abs(ov_amount)+ov_gst+ov_freight+ov_freight_tax+ov_discount+ov_roundoff-alloc,6) > 0
		AND trans.type NOT IN (".implode(',',array(ST_CUSTPAYMENT,ST_BANKDEPOSIT,ST_CUSTCREDIT,ST_CUSTDELIVERY,ST_CUSTPDC)).")";
		$sql .= " GROUP BY type, trans_no";
	}

	if($customer_id)
		$sql .= " AND trans.debtor_no=".db_escape($customer_id);
		
	

	return db_query($sql." ORDER BY trans_no", "Cannot retreive alloc to transactions");
}


function get_sales_order_cust_ref($order_)
{
    $sql="SELECT customer_ref FROM ".TB_PREF."sales_orders 
	WHERE trans_type=30
	AND order_no=".db_escape($order_);
    
    $res=db_query($sql,"No transactions were returned");
    $row=db_fetch_row($res);
    return $row[0];
}

//----------------------------------------------------------------------------

function print_billwise_matching_details()
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
	
	
	 $headers2 = array(_(''), _(''), _('Invoice'), _(''), _('Settled'), _('Balance'), 
	 _(''), _('Matched Details'), _(''), _('Paid'), _(''));

	$cols = array(0, 50, 110, 150, 210, 270, 330, 400, 450, 510);

	$headers = array(_('Date'), _('Doc No.'), _('Ref No.'), _('Amount'), _('Amount'), 
	_('Amount'), _('Vouch No.'),_('Ref.'),_('Date'),_('Recd Amount'));
	
	$aligns = array('left',	'left',	'left',	'right','right', 'right', 'center', 
	'left', 'left', 'right');
	
	$aligns2 = $aligns;

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
    				    3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						4 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => ''));

    $rep = new FrontReport(_('Billwise Matching Details Report'), 
	"BillwiseMatchingDetailsReport", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$cols2 = $cols;
	
    $rep->Font();
     $rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
    $rep->NewPage();

	$inv_grand_total = $settled_grand_total = $balance_grand_total = 0;

	$sql = "SELECT debtor_no, name, curr_code,cust_code FROM ".TB_PREF."debtors_master ";
	if ($fromcust != ALL_TEXT)
		$sql .= "WHERE debtor_no=".db_escape($fromcust);
	$sql .= " ORDER BY name ";
	
	$result = db_query($sql, "The customers could not be retrieved");

	while ($myrow = db_fetch($result))
	{
		
		
		
		if (!$convert && $currency != $myrow['curr_code']) continue;
		
		$accumulate = 0;
	    $rate = $convert?get_exchange_rate_from_home_currency($myrow['curr_code'],Today()) : 1;
		

		$res = get_transactions($myrow['debtor_no'], $from, $to);
				
		
		if ($no_zeros && db_num_rows($res) == 0) continue;

		
		$rep->SetFont('helvetica', 'B', 9);
		$rep->fontSize += 2;
		$rep->TextCol(0, 4, $myrow['cust_code']." - ".$myrow['name']);
		$rep->fontSize -= 2;
		if ($convert)
			$rep->TextCol(4, 5,	$myrow['curr_code']);
		 $rep->SetFont('', '', 0);
		
		$rep->NewLine(1, 2);
		$rep->Line($rep->row + 4);
		if (db_num_rows($res)==0) {
			$rep->NewLine(1, 2);
			continue;
		}
		
		
		
		$inv_total = $settled_total = $balance_total = 0;
		while ($trans = db_fetch($res))
		{
            
			
			
			$inv_type = "";
			
			$rep->NewLine(1, 2);
			
			$balance_amt = $trans['InvAmount']-$trans['settled_amt']-$trans['pdc_amt'];
			
			$rep->DateCol(0, 1,	$trans['tran_date'], true);
			$rep->TextCol(1, 2,	$inv_type. "  ".$trans['reference']);
			
			$inv_cust_ref = get_sales_order_cust_ref($trans['order_']);
			
			$rep->TextCol(2, 3,$inv_cust_ref);
			$rep->TextCol(3, 4, number_format2($trans['InvAmount'], $dec)." Dr");	
			
			$settled_amount = $trans['settled_amt']+$trans['pdc_amt'];
			
			$rep->TextCol(4, 5, number_format2($settled_amount, $dec)." Cr");
			if($balance_amt>0){
				$rep->TextCol(5, 6, number_format2($balance_amt, $dec)." Dr");
			}	
			
			
			$alloc_result = get_allocatable_vouchers_from_cust_transactions($myrow['debtor_no'], $trans['trans_no'], ST_SALESINVOICE);
			while ($alloc_row = db_fetch($alloc_result))
            {
				
				if($alloc_row['type']==11){
				$alloc_type = "SR";
			    }
			    else if($alloc_row['type']==12){
				if($alloc_row['bank_account']==1)
				$alloc_type = "CR";
			    else
				$alloc_type = "BR";	
			    }
				
				$rep->TextCol(6, 7,	$alloc_type. "  ".$alloc_row['reference']);
				$rep->TextCol(7, 8,	$alloc_row['sales_person_ref']);
				$rep->DateCol(8, 9,	$alloc_row['tran_date'], true);
				$rep->TextCol(9, 10, number_format2($alloc_row['amt'], $dec)." Cr");
				$rep->NewLine();
			}	
			
			
			
			
			//PDC Allocations
			$pdc_alloc_result = get_allocatable_pdc_vouchers_from_cust_transactions($myrow['debtor_no'], $trans['trans_no'], ST_SALESINVOICE);
			while ($pdc_alloc_row = db_fetch($pdc_alloc_result))
            {
				
				if($pdc_alloc_row['type']==5){
				$pdc_alloc_type = "PE";	
			    }
				
				$rep->TextCol(6, 7,	$pdc_alloc_type. "  ".$pdc_alloc_row['reference']);
				$rep->TextCol(7, 8,	$pdc_alloc_row['sales_person_ref']);
				$rep->DateCol(8, 9,	$pdc_alloc_row['tran_date'], true);
				$rep->TextCol(9, 10, number_format2($pdc_alloc_row['amt'], $dec)." Cr");
				$rep->NewLine();
			}	
			
			
			
			$inv_total += $trans['InvAmount'];
			$settled_total += $settled_amount;
			
			$inv_grand_total += $trans['InvAmount'];
			$settled_grand_total += $settled_amount;
			
			
		}
		
		$rep->Line($rep->row - 8);
		$rep->NewLine(2);
		$rep->SetFont('helvetica', 'B', 9);
		$rep->TextCol(1, 3, _('Account Summary '));
		$rep->TextCol(3, 4, number_format2($inv_total, $dec)." Dr");	
		$rep->TextCol(4, 5, number_format2($settled_total, $dec)." Cr");	
	    $rep->TextCol(5, 6, number_format2($inv_total-$settled_total, $dec)." Dr");	
		$rep->SetFont('', '', 0);
   		$rep->Line($rep->row  - 4);
   		$rep->NewLine(2);
		
		
	}
	
	$rep->fontSize += 2;
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(2, 3, _('Grand Total'));
	$rep->fontSize -= 2;
	
	$rep->TextCol(3, 4, number_format2($inv_grand_total, $dec)." Dr");	
	$rep->TextCol(4, 5, number_format2($settled_grand_total, $dec)." Cr");	
	$rep->TextCol(5, 6, number_format2($inv_grand_total-$settled_grand_total, $dec)." Dr");	
	$rep->SetFont('', '', 0);
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    	$rep->End();
}

