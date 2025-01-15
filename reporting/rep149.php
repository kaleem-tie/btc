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
$page_security = 'SA_SALES_DO_REG_REP';

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

print_salesmanwise_delivery_orders_register();


function getTransactions($from, $to, $sales_person=0, $location=0)
{
	$from = date2sql($from);
	$to   = date2sql($to);
	
	$sql = "SELECT DISTINCT trans.trans_no,trans.type,trans.reference,trans.invoice_type,trans.tran_date,
	        debtor.cust_code,debtor.name,debtor.curr_code,
			(trans.ov_amount+trans.ov_freight+trans.ov_discount) AS net_amount,
			trans.ov_gst AS vat_amount,trans.ov_roundoff,trans.rate,trans.order_,
			trans.lpo_no,trans.lpo_date,trans.sales_person_id,sm.salesman_name,trans.debtor_no
		FROM ".TB_PREF."debtor_trans trans,
		".TB_PREF."debtors_master debtor,
		".TB_PREF."salesman sm 
		WHERE sm.salesman_code=trans.sales_person_id
		AND trans.debtor_no=debtor.debtor_no
		AND trans.tran_date>='$from'
		AND trans.tran_date<='$to'
		AND trans.type = ".ST_CUSTDELIVERY."
		AND trans.ov_amount!=0 AND trans.reference !='auto'";
     
	
	if ($sales_person != 0)
	$sql .= " AND trans.sales_person_id=".db_escape($sales_person);

	$sql .= " GROUP BY trans.trans_no ORDER BY trans.sales_person_id,trans.debtor_no,trans.trans_no";
	
   return db_query($sql,"No transactions were returned");

}


function get_sales_delivery_line_transactions($delivery_no, $foc_reg) {
	
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
		AND trans.type = 13 and line.debtor_trans_type = 13
		AND item.mb_flag <>'F'
		AND trans.trans_no = ".db_escape($delivery_no)."
		AND trans.type = ".ST_CUSTDELIVERY."";
		
	if ($foc_reg != 0)
		$sql .= " AND line.foc_quantity != 0"; 
		
     
	$sql .= " ORDER BY trans.trans_no,line.id";
	
	
	 return db_query($sql,"No transactions were returned");
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

function print_salesmanwise_delivery_orders_register()
{
    	global $path_to_root, $systypes_array;

    	$from           = $_POST['PARAM_0'];
    	$to             = $_POST['PARAM_1'];
		$sales_person   = $_POST['PARAM_2'];
		$location       = $_POST['PARAM_3'];
		$summaryOnly    = $_POST['PARAM_4'];
		$foc_reg        = $_POST['PARAM_5'];
    	$comments       = $_POST['PARAM_6'];
	    $orientation    = $_POST['PARAM_7'];
	    $destination    = $_POST['PARAM_8'];
		
	
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	
	
	$dec = user_price_dec();
	
    
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
	
	
	
	if ($foc_reg == 1)
		$foc = _('Yes');
	else
		$foc = _('No');
	
	
	
	if ($location == '')
		$loc = _('All');
	else
		$loc = get_location_name($location);

	$cols = array(0, 40, 90, 120,250,300,350,400,450,500,550);
	
	$headers2 = array(_('DO No.'), _('DO Date'), _('Locn'), _('Cust Code - Name'),  _('Curncy'),
	  _('LPO No.'), _('LPO Date'), _('SO. No.'), _('Area'), _('DO Amount'));
	 
	  

    if (!$summaryOnly){
	$headers = array(_('Sl. No.'), _('Item Code'), _('Description'), _(''),  _('Units'),
	  _('Quantity'), _('FOC Qty'), _('Unit Rate'), _('Disc Amt'), _('Itm. Amount'));
	}
	
	$aligns = array('left',	'left',	'left',	'left','left', 
	'right', 'right', 'right', 'right', 'right');
	
	
	$aligns2 = $aligns;

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
						2 => array('text' => _('Sales Person'), 'from' => $salesfolk, 'to' => ''),
						3 => array('text' => _('Location'), 'from' => $loc, 'to' => ''),
						4 => array('text' => _('FOC'), 'from' => $foc, 'to' => ''),
						5 => array('text' => _('Display Type'), 'from' => $summary, 'to' => ''));
						

    $rep = new FrontReport(_('SalesManwise DO Register'), 
	"SalesManwiseDORegister", user_pagesize(), 7, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$cols2 = $cols;
	
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
    $rep->NewPage();

	

	$result = getTransactions($from, $to, $sales_person, $location);
	
    $do_total = $total_cust = $total_salesper_do = $do_grand_total = 0.0;
	
	$salesper =  $customer_name = $cust_names = '';
	
	while ($myrow = db_fetch($result))
	{
		
           $so_loc = get_so_location($myrow['order_']);
		   $net_amt = ($myrow['net_amount']*$myrow['rate']);
		   $vat_amt = ($myrow['vat_amount']*$myrow['rate']);
			
			if($myrow['reference']=='auto')
				$do_ref = "auto - ".$myrow['trans_no'];
			else
			   $do_ref = $myrow['reference'];
		   
		   
		   if($so_loc['reference']=='auto')
				$so_ref = "auto - ".$so_loc['order_no'];
			else
			   $so_ref = $so_loc['reference'];
		   
		   
		  $cust_br_area = get_cust_branch_area_name_rep($myrow['debtor_no']); 
		   
		   
		   
		  if ($customer_name != $myrow['name'])
		  {
			
			if ($customer_name != '')
			{
				
				$rep->NewLine(1,2);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(2, 6, _('Subtotal:- ').$customer_name);
				$rep->AmountCol(9, 10, $total_cust, $dec);
				$rep->SetFont('', '', 0);
				$rep->NewLine();
				$rep->Line($rep->row - 4);
				$rep->NewLine();
				$rep->NewLine();
				$total_cust =  0.0;
			}
			$customer_name = $myrow['name'];
			$customer_code = $myrow['cust_code'];
		   }
		   
		   
		if ($salesper != $myrow['salesman_name'])
		{
			if ($salesper != '')
			{
				$rep->NewLine(2, 3);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(4, 6, _('Total DO By ').$salesper);
				$rep->AmountCol(9, 10, $total_salesper_do, $dec);
				$rep->SetFont('', '', 0);
				$rep->NewLine();
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$rep->NewLine();
				$total_salesper_do = 0.0;
			}
			$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 4, _('SalesMan : ').$myrow['salesman_name']);
			$rep->SetFont('', '', 0);
			$salesper = $myrow['salesman_name'];
			$rep->NewLine();
		}
		   
		   
		   
		if ($cust_names != $myrow['name'])
		{
			$rep->NewLine();
			$rep->TextCol(1, 5, _('Customer : ').$myrow['cust_code']." ".$myrow['name']);
			$cust_names = $myrow['name'];
			$rep->NewLine();
			
		}
		   
		   
			$rep->TextCol(0, 1,	$do_ref);
			$rep->DateCol(1, 2,	$myrow['tran_date'], true);
			$rep->TextCol(2, 3,	$so_loc['from_stk_loc']);
			$rep->TextCol(3, 4,	"");
			$rep->TextCol(4, 5,	$myrow['curr_code']);
			$rep->TextCol(5, 6,	$myrow['lpo_no']);
			$rep->DateCol(6, 7,	$myrow['lpo_date'], true);
			$rep->TextCol(7, 8,	$so_ref);
			$rep->TextCol(8, 9,	 $cust_br_area['description']);
			$rep->AmountCol(9, 10, $net_amt+$vat_amt,$dec);
			$rep->NewLine();
			
			
			
		  if (!$summaryOnly)
		  {
			 
			$do_line_details = get_sales_delivery_line_transactions($myrow['trans_no'], $foc_reg);
    		
    		
			$sl_no=1;
			$do_itm_total  = 0;
			$sign = 1;
			while ($do_line=db_fetch($do_line_details))
			{
				
				
				
				$DisplayQty = number_format2($do_line["quantity"],get_qty_dec($do_line['stock_id']));
				$DisplayFOCQty = number_format2($do_line["foc_quantity"],get_qty_dec($do_line['stock_id']));
				$DisplayPrice = number_format2($do_line["unit_price"],$dec);
                $discount_amount =  ($do_line["unit_price"] * $do_line["quantity"] * $do_line["discount_percent"]/100);		
                $DisplayDiscAmt = number_format2($discount_amount,$dec);
                $Net = round2(($do_line["quantity"] * $do_line["unit_price"])-($do_line["quantity"] * $do_line["unit_price"]* ($do_line["discount_percent"]/100)),user_price_dec());
				$DisplayNet = number_format2($Net,$dec);
				
				
			    $rep->NewLine(1, 2);
				
			    $rep->TextCol(0, 1,	$sl_no);
			    $rep->TextCol(1, 2,	$do_line['stock_id']);
			    $rep->TextCol(2, 4,	$do_line['description']);
			
			    if($do_line['unit']==1){
				 $item_info = get_item_edit_info($do_line["stock_id"]);	
				 $rep->TextCol(4, 5,	$item_info["units"], -2);
				}
				else if($do_line["unit"]==2){
		         $sec_unit_info = get_item_sec_unit_info($do_line["stock_id"]);
	             $rep->TextCol(4, 5,$sec_unit_info["sec_unit_name"], -2);
                }	
				
				
			
			    $rep->TextCol(5, 6,	$DisplayQty, -2);
				$rep->TextCol(6, 7,	$DisplayFOCQty, -2);
			    $rep->TextCol(7, 8,	$DisplayPrice, -2);
				$rep->TextCol(8, 9,	$DisplayDiscAmt, -2);
			    $rep->TextCol(9, 10, $DisplayNet);
				
				
			    
			    $sl_no++;	
				$do_itm_total  +=  $Net;
			}
			
			$rep->NewLine();
			$rep->Line($rep->row - 2);
			$rep->NewLine(2);
		 }
		 
		
		$total_cust        += $net_amt+$vat_amt;	
        $total_salesper_do += $net_amt+$vat_amt;	
        $do_total          += $net_amt+$vat_amt; 			
        $do_grand_total    += $net_amt+$vat_amt;		
		
		
	}
	
	
    if ($customer_name != '')
	{
		$rep->NewLine(1, 2);
		$rep->SetFont('helvetica', 'B', 9);
		$rep->TextCol(2, 6, _('Subtotal:- '). $customer_name);
		$rep->AmountCol(9, 10, $total_cust, $dec);
		$rep->SetFont('', '', 0);
		$rep->NewLine();
		$rep->Line($rep->row - 2);
		$rep->NewLine(1);
		
	}
	
	
	$rep->NewLine(1, 2);
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(3, 6, _('Total DO By ').$salesper);
	$rep->AmountCol(9, 10, $total_salesper_do, $dec);
	$rep->SetFont('', '', 0);
	
	$rep->NewLine();
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->NewLine();
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(7, 9, _('Grand Total'));
	$rep->AmountCol(9, 10, $do_grand_total, $dec);
	$rep->SetFont('', '', 0);
	
    $rep->End();
}

