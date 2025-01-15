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
$page_security = 'SA_GEN_SALES_SUMMARY_REP';

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
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
//--------------------------------------------------------------------------------------------

print_general_sales_summary_register();


function getTransactions($from, $to, $customer=0, $sales_person=0, $ret_cred_type)
{
	$from = date2sql($from);
	$to   = date2sql($to);
	
	$sql = "SELECT DISTINCT trans.trans_no,trans.type,trans.reference,trans.invoice_type,trans.tran_date,
	        debtor.cust_code,debtor.name,debtor.curr_code,
			(trans.ov_amount+trans.ov_freight+trans.ov_discount) AS net_amount,
			trans.ov_gst AS vat_amount,trans.ov_roundoff,trans.rate,trans.order_
		FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor
		WHERE trans.debtor_no=debtor.debtor_no
		AND trans.tran_date>='$from'
		AND trans.tran_date<='$to'
		AND (trans.type=".ST_SALESINVOICE." OR trans.type=".ST_CUSTCREDIT.")
		AND trans.ov_amount!=0";
     
	if($customer != ''){
		$sql .= " AND trans.debtor_no = ".db_escape($customer);
	}
	
	if ($sales_person != 0)
	$sql .= " AND trans.sales_person_id=".db_escape($sales_person);


    if ($ret_cred_type == 1){
	$sql .= " AND trans.invoice_type='SC'";
	}
	else if($ret_cred_type == 2){
		$sql .= " AND trans.invoice_type='SI'";
	}
		
	$sql .= " GROUP BY trans.type,trans.trans_no ORDER BY trans.trans_no,trans.tran_date";
		
		
	
   return db_query($sql,"No transactions were returned");

}


function get_sales_invoice_credit_line_transactions($invoice_no,$trans_type, $foc_reg) {
	
	$sql = "SELECT 
			item.stock_id,
			item.description,
			line.unit_price,
			line.quantity,
			line.unit,
			line.discount_percent,
			line.foc_quantity,
			line.debtor_trans_type
		FROM ".TB_PREF."stock_master item,";
		$sql.=TB_PREF."debtor_trans trans,
			".TB_PREF."debtor_trans_details line
		WHERE line.stock_id = item.stock_id
		AND trans.type = line.debtor_trans_type
		AND trans.trans_no = line.debtor_trans_no 
		AND item.mb_flag <>'F'
		AND trans.trans_no = ".db_escape($invoice_no)."
		AND trans.type     = ".db_escape($trans_type)."
		AND (trans.type=".ST_SALESINVOICE." OR trans.type=".ST_CUSTCREDIT.")";
		
	if ($foc_reg != 0)
		$sql .= " AND line.foc_quantity != 0"; 
		
     
	$sql .= " ORDER BY trans.trans_no,line.id";
	
	
	 return db_query($sql,"No transactions were returned");
}


function get_total_sales_value_and_discount_value($trans_no,$trans_type)
{
	$sql = "SELECT SUM(quantity*unit_price) AS sales_value,
	SUM(unit_price*quantity*discount_percent/100) AS discount_value
	FROM ".TB_PREF."debtor_trans_details 
	WHERE debtor_trans_type in ('10','11') 
	AND debtor_trans_no=".db_escape($trans_no)." 
	AND debtor_trans_type=".db_escape($trans_type)." 
	GROUP BY debtor_trans_no";

    $result = db_query($sql,"No transactions were returned");
    if ($result !== false)
    	return db_fetch($result);
    else
    	return null;
} 


function get_so_location($order_no)
{
	$sql = "SELECT from_stk_loc FROM ".TB_PREF."sales_orders 
	WHERE order_no=".db_escape($order_no)."
	AND trans_type=30";

	$result = db_query($sql,"could not query comments transaction table");
    return db_fetch($result);
}
//----------------------------------------------------------------------------

function print_general_sales_summary_register()
{
    	global $path_to_root, $systypes_array;

    	$from           = $_POST['PARAM_0'];
    	$to             = $_POST['PARAM_1'];
    	$customer       = $_POST['PARAM_2'];
		$sales_person   = $_POST['PARAM_3'];
		$summaryOnly    = $_POST['PARAM_4'];
		$ret_cred_type  = $_POST['PARAM_5'];
		$foc_reg        = $_POST['PARAM_6'];
    	$comments       = $_POST['PARAM_7'];
	    $orientation    = $_POST['PARAM_8'];
	    $destination    = $_POST['PARAM_9'];
		
	
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	
	
	$dec = user_price_dec();
	

	if ($customer == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($customer);
    
	if ($sales_person == ALL_NUMERIC)
        $sales_person = 0;
    if ($sales_person == 0)
        $salesfolk = _('All Sales Man');
     else
        $salesfolk = get_salesman_name($sales_person);
	
	
	
	if ($summaryOnly == 1)
		$summary = _('Summary Only');
	else
		$summary = _('Detailed Report');
	
	
	
	if ($ret_cred_type == 0)
		$ret_cred = _('All');
	else if ($ret_cred_type == 1)
		$ret_cred = _('Retail Sales');
	else if ($ret_cred_type == 2)
		$ret_cred = _('Credit Sales');
	
	if ($foc_reg == 1)
		$foc = _('Yes');
	else
		$foc = _('No');

	$cols = array(0, 40, 75,100,210,230,275,315,355,400,450,500,550);
	
	$headers2 = array(_('Inv No.'), _('Inv Date'), _('Locn'), _('Cust Code - Name'),  _('Curncy'),
	  _('Gross Amt'), _('Disc Amt'), _('Net Amt'), _('VAT Applied On'),
	  _('VAT Amt'), _('Round Off'), _('Net Incl. Amt'));
	  

	 
	

    if (!$summaryOnly){
	$headers = array(_('Sl. No.'), _('Item Code'), _('Description'), _(''),  _('Units'),
	  _('Quantity'), _('FOC Qty'), _('Unit Rate'), _('Disc Amt'), _('Itm. Amount'),   _('VAT %'));
	}
	
	$aligns = array('left',	'left',	'left',	'left','left', 
	'right', 'right', 'right', 'right', 'right', 'right', 'right');
	
	
	$aligns2 = $aligns;

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Sales Person'), 'from' => $salesfolk, 'to' => ''),
						4 => array('text' => _('Sale Type'), 'from' => $ret_cred, 'to' => ''),
						5 => array('text' => _('FOC'), 'from' => $foc, 'to' => ''),
						6 => array('text' => _('Display Type'), 'from' => $summary, 'to' => ''));
						

    $rep = new FrontReport(_('General Sales Summary Register'), 
	"GeneralSalesSummaryRegister", user_pagesize(), 7, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$cols2 = $cols;
	
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
    $rep->NewPage();

	
	
	

	$result = getTransactions($from, $to, $customer, $sales_person, $ret_cred_type);
	
    $grand_net_incl_amt = 0;
	while ($myrow = db_fetch($result))
	{
		
		    if ($myrow['type']!= ST_CUSTCREDIT )
	        $rep->SetTextColor(33, 33, 33);
	        else
	        $rep->SetTextColor(216, 67, 21);
		   
		
            if($myrow['reference']=='SC')
				 $inv_type="SC";
			else if($myrow['reference']=='SI')
				 $inv_type="SI";
			
			$rep->TextCol(0, 1,	$myrow['reference']);
			$rep->DateCol(1, 2,	$myrow['tran_date'], true);
			
			$so_loc = get_so_location($myrow['order_']);
			
			$rep->TextCol(2, 3,	$so_loc['from_stk_loc']);
			$rep->TextCol(3, 4,	$myrow['cust_code']." - ".$myrow['name']);
			$rep->TextCol(4, 5,	$myrow['curr_code']);
			
			$sales_disc_value = get_total_sales_value_and_discount_value($myrow['trans_no'],
			$myrow['type']);
		    $rep->AmountCol(5, 6, $sales_disc_value['sales_value'],$dec);
		    $rep->AmountCol(6, 7, $sales_disc_value['discount_value'],$dec);
			
			
			if ($myrow['type'] == ST_CUSTCREDIT ){
				$myrow['net_amount'] *= -1;
				$myrow['vat_amount'] *= -1;
				$myrow['ov_roundoff'] *= -1;
		    }
		
			
			$net_amt = ($myrow['net_amount']*$myrow['rate']);
			$vat_amt = ($myrow['vat_amount']*$myrow['rate']);
			$roundoff_amt = ($myrow['ov_roundoff']*$myrow['rate']);
			
			
			
			$rep->AmountCol(7, 8, $net_amt,$dec);
			$rep->AmountCol(8, 9, $net_amt,$dec);
			$rep->AmountCol(9, 10, $vat_amt,$dec);
			$rep->AmountCol(10, 11, $roundoff_amt,$dec);
			$rep->AmountCol(11, 12, $net_amt+$vat_amt+$roundoff_amt,$dec);
			$rep->NewLine();
			$rep->SetTextColor(33, 33, 33);
			
			
			$grand_net_incl_amt += $net_amt+$vat_amt+$roundoff_amt;
			
			
			
		  if (!$summaryOnly)
		  {
			 
			$inv_details = get_sales_invoice_credit_line_transactions($myrow['trans_no'],$myrow['type'], $foc_reg);
    		
    		
			$sl_no=1;
			$inv_itm_total  = 0;
			$sign = 1;
			while ($inv=db_fetch($inv_details))
			{
				
				$headers = array(_('Sl. No.'), _('Item Code'), _('Description'), _(''),  _('Units'),
	  _('Quantity'), _('FOC Qty'), _('Unit Rate'), _('Disc%'), _('Itm. Amount'),   _('VAT %'));
				
				
				$DisplayQty = number_format2($inv["quantity"],get_qty_dec($inv['stock_id']));
				$DisplayFOCQty = number_format2($inv["foc_quantity"],get_qty_dec($inv['stock_id']));
				$DisplayPrice = number_format2($inv["unit_price"],$dec);
                $discount_amount =  ($inv["unit_price"] * $inv["quantity"] * $inv["discount_percent"]/100);		
                $DisplayDiscAmt = number_format2($discount_amount,$dec);
                $Net = round2(($inv["quantity"] * $inv["unit_price"])-($inv["quantity"] * $inv["unit_price"]* ($inv["discount_percent"]/100)),user_price_dec());
				$DisplayNet = number_format2($Net,$dec);
				
				
			    $rep->NewLine(1, 2);
				
			    $rep->TextCol(0, 1,	$sl_no);
			    $rep->TextCol(1, 2,	$inv['stock_id']);
			    $rep->TextCol(2, 4,	$inv['description']);
			
			    if($inv['unit']==1){
				 $item_info = get_item_edit_info($inv["stock_id"]);	
				 $rep->TextCol(4, 5,	$item_info["units"], -2);
				}
				else if($inv["unit"]==2){
		         $sec_unit_info = get_item_sec_unit_info($inv["stock_id"]);
	             $rep->TextCol(4, 5,$sec_unit_info["sec_unit_name"], -2);
                }	
				
				
			
			    $rep->TextCol(5, 6,	$DisplayQty, -2);
				$rep->TextCol(6, 7,	$DisplayFOCQty, -2);
			    $rep->TextCol(7, 8,	$DisplayPrice, -2);
				$rep->TextCol(8, 9,	$DisplayDiscAmt, -2);
			    $rep->TextCol(9, 10, $DisplayNet);
				
				$item = get_item($inv['stock_id']);
			    if($item['exempt']==0){
			    $rep->TextCol(10, 11, "5.00", -2);
				}
				else{
					$rep->TextCol(10, 11, "0", -2);
				}	
			    
			    $sl_no++;	
				$inv_itm_total  +=  $Net;
			}
			$rep->NewLine();
			$rep->SetFont('helvetica', 'B', 9);
	        $rep->TextCol(7, 9, _('Item Total'));
	        $rep->AmountCol(9, 10, $inv_itm_total, $dec);
	        $rep->SetFont('', '', 0);
			$rep->Line($rep->row - 8);
			$rep->NewLine(2);
		 }
	}
	
	
	
	$rep->Line($rep->row  - 4);
	$rep->NewLine(2);
	
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(10 , 11, _('Grand Total'));
	$rep->AmountCol(11, 12, $grand_net_incl_amt, $dec);
	$rep->SetFont('', '', 0);
	
    $rep->End();
}

