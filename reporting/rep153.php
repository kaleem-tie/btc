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
$page_security = 'SA_SALESMANWISE_SALES_REP';

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

print_salesmanwise_sales_register();


function getTransactions($from, $to, $customer=0, $sales_person=0)
{
	$from = date2sql($from);
	$to   = date2sql($to);
	
	$sql = "SELECT DISTINCT trans.trans_no,trans.type,trans.reference,trans.invoice_type,trans.tran_date,
	        debtor.cust_code,debtor.name,debtor.curr_code,trans.debtor_no,
			(trans.ov_amount+trans.ov_freight+trans.ov_discount) AS net_amount,
			trans.ov_gst AS vat_amount,trans.ov_roundoff,trans.rate,trans.order_,
			trans.lpo_no,trans.lpo_date,trans.sales_person_id,sm.salesman_name
		FROM ".TB_PREF."debtor_trans trans,
		".TB_PREF."debtors_master debtor,
		".TB_PREF."salesman sm
		WHERE sm.salesman_code=trans.sales_person_id
		AND trans.debtor_no=debtor.debtor_no
		AND trans.tran_date>='$from'
		AND trans.tran_date<='$to'
		AND trans.type = ".ST_SALESINVOICE."
		AND trans.ov_amount!=0";
     
	if($customer != ''){
		$sql .= " AND trans.debtor_no = ".db_escape($customer);
	}
	
	if ($sales_person != 0)
	$sql .= " AND trans.sales_person_id=".db_escape($sales_person);

   	
	$sql .= " GROUP BY trans.trans_no ORDER BY trans.sales_person_id,trans.trans_no";
		
	
   return db_query($sql,"No transactions were returned");

}


function get_sales_invoice_line_transactions($invoice_no) {
	
	$sql = "SELECT 
			item.stock_id,
			item.description,
			line.unit_price,
			line.quantity,
			line.unit,
			line.discount_percent,
			line.foc_quantity
		FROM ".TB_PREF."stock_master item,";
		$sql.=TB_PREF."debtor_trans trans,
			".TB_PREF."debtor_trans_details line
		WHERE line.stock_id = item.stock_id
		AND trans.type = line.debtor_trans_type
		AND trans.trans_no = line.debtor_trans_no 
		AND trans.type = 10 and line.debtor_trans_type = 10
		AND item.mb_flag <>'F'
		AND trans.trans_no = ".db_escape($invoice_no)."
		AND trans.type = ".ST_SALESINVOICE."";
	$sql .= " ORDER BY trans.trans_no,line.id";
	
	
	 return db_query($sql,"No transactions were returned");
}



function get_total_sales_value_and_discount_value($trans_no)
{
	$sql = "SELECT SUM((dtd.quantity*dtd.unit_price)*dt.rate) AS sales_value,
	SUM((unit_price*quantity*discount_percent/100)*dt.rate) AS discount_value
	FROM ".TB_PREF."debtor_trans_details as dtd,".TB_PREF."debtor_trans as dt
	WHERE dt.type = dtd.debtor_trans_type
	AND dt.trans_no = dtd.debtor_trans_no
	AND dtd.debtor_trans_type = ".ST_SALESINVOICE."
	AND dtd.debtor_trans_no=".db_escape($trans_no)." 
	GROUP BY dtd.debtor_trans_no";

    $result = db_query($sql,"No transactions were returned");
    if ($result !== false)
    	return db_fetch($result);
    else
    	return null;
} 

function get_so_location($order_no)
{
	$sql = "SELECT from_stk_loc,reference,order_no FROM ".TB_PREF."sales_orders 
	WHERE order_no=".db_escape($order_no)."
	AND trans_type=30";

	$result = db_query($sql,"could not query comments transaction table");
    return db_fetch($result);
}

function get_cust_branch_area_name_rep($debtor_no)
{
	$sql = "SELECT area.description 
	FROM ".TB_PREF."debtors_master cust,
	".TB_PREF."cust_branch branch,".TB_PREF."areas area 
	WHERE cust.debtor_no = branch.debtor_no
	AND branch.area = area.area_code
	AND branch.debtor_no=".db_escape($debtor_no)." GROUP BY cust.debtor_no";
   
	$result = db_query($sql,"could not query comments transaction table");
    return db_fetch($result);
}
//----------------------------------------------------------------------------

function print_salesmanwise_sales_register()
{
    	global $path_to_root, $systypes_array;

    	$from           = $_POST['PARAM_0'];
    	$to             = $_POST['PARAM_1'];
    	$customer       = $_POST['PARAM_2'];
		$sales_person   = $_POST['PARAM_3'];
		$summaryOnly    = $_POST['PARAM_4'];
    	$comments       = $_POST['PARAM_5'];
	    $orientation    = $_POST['PARAM_6'];
	    $destination    = $_POST['PARAM_7'];
		
	
		
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
	
	

	$cols = array(0, 50,90,120,250,280,320,360,390,440,490,540);
	
	$headers2 = array(_('Inv No.'), _('Inv Date'), _('Locn'), _('Cust Code - Name'),  
	_('Curncy'), _('LPO No.'), _('LPO Date'), 
	_('Area'), _('Gross Amt'), _('Disc Amt'),  _('Net Incl. Amt'));
	 
	  

    if (!$summaryOnly){
	$headers = array(_('Sl.'), _('Item Code'), _('Description'), _(''), _(''),
	_('Units'), _('Quantity'), _('Unit Rate'), _('Disc Amt'), _('Itm. Amount'));
	}
	
	$aligns = array('left',	'left',	'left',	'left',
	'left', 'left',	'left',	'right', 'right', 'right', 'right');
	
	
	
	$aligns2 = $aligns;

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Sales Person'), 'from' => $salesfolk, 'to' => ''),
						4 => array('text' => _('Location'), 'from' => $loc, 'to' => ''),
						5 => array('text' => _('FOC'), 'from' => $foc, 'to' => ''),
						6 => array('text' => _('Display Type'), 'from' => $summary, 'to' => ''));
						

    $rep = new FrontReport(_('SalesManwise Sales Register - (Detailed & Summary)'), 
	"SalesManwiseSalesRegister(DetailedandSummary)", user_pagesize(), 7, $orientation);
	
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$cols2 = $cols;
	
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
    $rep->NewPage();
	 

	$result = getTransactions($from, $to, $customer, $sales_person);
	
	$sp_total_net_amt = $grand_net_incl_amt = 0;
	$salesper =   '';
   
	while ($myrow = db_fetch($result))
	{
		
		
		
			if ($salesper != $myrow['salesman_name'])
		   {
			 if ($salesper != '')
			 {
				$rep->NewLine(2, 3);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(7, 9, _('Total ').$salesper);
				$rep->AmountCol(9, 10, $sp_total_net_amt, $dec);
				$rep->SetFont('', '', 0);
				$rep->NewLine();
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$rep->NewLine();
				$sp_total_net_amt = 0.0;
			 }
			$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 4, _('SalesMan : ').$myrow['salesman_name']);
			$rep->SetFont('', '', 0);
			$salesper = $myrow['salesman_name'];
			$rep->NewLine();
		  }
		
		
           $so_loc = get_so_location($myrow['order_']);
		   $cust_br_area = get_cust_branch_area_name_rep($myrow['debtor_no']); 
		   $net_amt = ($myrow['net_amount']*$myrow['rate']);
		   $vat_amt = ($myrow['vat_amount']*$myrow['rate']);
			
		   
			$rep->TextCol(0, 1,	$myrow['reference']);
			$rep->DateCol(1, 2,	$myrow['tran_date'], true);
			$rep->TextCol(2, 3,	$so_loc['from_stk_loc']);
			$rep->TextCol(3, 4,	$myrow['cust_code']." - ".$myrow['name']);
			$rep->TextCol(4, 5,	$myrow['curr_code']);
			$rep->TextCol(5, 6,	$myrow['lpo_no']);
			$rep->DateCol(6, 7,	$myrow['lpo_date'], true);
			$rep->TextCol(7, 8,	$cust_br_area['description']);
			
			$sales_disc_value = get_total_sales_value_and_discount_value($myrow['trans_no']);
		    $rep->AmountCol(8, 9, $sales_disc_value['sales_value'],$dec);
		    $rep->AmountCol(9, 10, $sales_disc_value['discount_value'],$dec);
			$rep->AmountCol(10, 11, $net_amt,$dec);
			$rep->NewLine();
			
			$sp_total_net_amt += $net_amt;
			$grand_net_incl_amt += $net_amt;
			
			
			
		  if (!$summaryOnly)
		  {
			 
			$inv_line_details = get_sales_invoice_line_transactions($myrow['trans_no']);
    		
    		
			$sl_no=1;
			$inv_itm_total  = 0;
			$sign = 1;
			while ($inv_line=db_fetch($inv_line_details))
			{
				
				
				
				$DisplayQty = number_format2($inv_line["quantity"],get_qty_dec($inv_line['stock_id']));
				$DisplayFOCQty = number_format2($inv_line["foc_quantity"],get_qty_dec($inv_line['stock_id']));
				$DisplayPrice = number_format2($inv_line["unit_price"],$dec);
                $discount_amount =  ($inv_line["unit_price"] * $inv_line["quantity"] * $inv_line["discount_percent"]/100);		
                $DisplayDiscAmt = number_format2($discount_amount,$dec);
                $Net = round2(($inv_line["quantity"] * $inv_line["unit_price"])-($inv_line["quantity"] * $inv_line["unit_price"]* ($inv_line["discount_percent"]/100)),user_price_dec());
				$DisplayNet = number_format2($Net,$dec);
				
				
			    $rep->NewLine(1, 2);
				
			    $rep->TextCol(0, 1,	$sl_no);
			    $rep->TextCol(1, 2,	$inv_line['stock_id']);
			    $rep->TextCol(2, 5,	$inv_line['description']);
			
			    if($inv_line['unit']==1){
				 $item_info = get_item_edit_info($inv_line["stock_id"]);	
				 $rep->TextCol(5, 6,	$item_info["units"], -2);
				}
				else if($inv_line["unit"]==2){
		         $sec_unit_info = get_item_sec_unit_info($inv_line["stock_id"]);
	             $rep->TextCol(5, 6,$sec_unit_info["sec_unit_name"], -2);
                }	
				
				
			
			    $rep->TextCol(6, 7,	$DisplayQty, -2);
			    $rep->TextCol(7, 8,	$DisplayPrice, -2);
				$rep->TextCol(8, 9,$DisplayDiscAmt, -2);
			    $rep->TextCol(9, 10, $DisplayNet);
				
				
			    
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
	
	
	
	$rep->NewLine(1, 2);
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(7, 9, _('Total ').$salesper);
	$rep->AmountCol(9, 10, $sp_total_net_amt, $dec);
	$rep->SetFont('', '', 0);
	$rep->NewLine(1);
	$rep->Line($rep->row - 2);
	$rep->NewLine(1);
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(7 , 9, _('Grand Total'));
	$rep->AmountCol(9, 10, $grand_net_incl_amt, $dec);
	$rep->SetFont('', '', 0);
	
    $rep->End();
}

