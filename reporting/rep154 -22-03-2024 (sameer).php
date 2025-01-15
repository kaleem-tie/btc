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
// Title:	Salesman Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

//----------------------------------------------------------------------------------------------------

print_salesman_list();

//----------------------------------------------------------------------------------------------------

function GetSalesmanTrans($from, $to,$sales_person=0)
{
	$fromdate = date2sql($from);
	$todate = date2sql($to);

	$sql = "SELECT sm.salesman_name,line.stock_id,line.description,line.unit_price,
			line.quantity,line.unit,line.discount_percent,line.foc_quantity,
			trans.trans_no,trans.type,trans.reference,trans.invoice_type,trans.tran_date,
			trans.rate,trans.order_,trans.lpo_no,trans.lpo_date,trans.sales_person_id,
			debtor.cust_code,debtor.name,debtor.curr_code
		FROM ".TB_PREF."debtor_trans_details line,".TB_PREF."debtor_trans trans
		LEFT JOIN ".TB_PREF."debtors_master debtor ON debtor.debtor_no=trans.debtor_no
		LEFT JOIN ".TB_PREF."salesman sm ON sm.salesman_code=trans.sales_person_id
		WHERE trans.type = line.debtor_trans_type
		AND trans.trans_no = line.debtor_trans_no 
		AND trans.type = 10 and line.debtor_trans_type = 10
		AND trans.tran_date>='$fromdate'
		AND trans.tran_date<='$todate'
		AND trans.type = ".ST_SALESINVOICE."
		AND trans.ov_amount!=0 ";
			
	if ($sales_person != 0)
	$sql .= " AND trans.sales_person_id=".db_escape($sales_person);
		
		
	$sql .= " GROUP BY line.id ORDER BY trans.sales_person_id,line.description,trans.trans_no";
		
	return db_query($sql, "Error getting order details");
}


function get_so_location($order_no)
{
	$sql = "SELECT from_stk_loc,reference,order_no FROM ".TB_PREF."sales_orders 
	WHERE order_no=".db_escape($order_no)."
	AND trans_type=30";

	$result = db_query($sql,"could not query comments transaction table");
    return db_fetch($result);
}
//----------------------------------------------------------------------------------------------------

function print_salesman_list()
{
	global $path_to_root;

	$from          = $_POST['PARAM_0'];
	$to            = $_POST['PARAM_1'];
	$sales_person  = $_POST['PARAM_2'];
	$summary       = $_POST['PARAM_3'];
	$comments      = $_POST['PARAM_4'];
	$orientation   = $_POST['PARAM_5'];
	$destination = $_POST['PARAM_6'];
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	
	$orientation = ($orientation ? 'L' : 'L');

	if ($summary == 0)
		$sum = _("No");
	else
		$sum = _("Yes");
	
	
	if ($sales_person == ALL_NUMERIC)
		$sales_person = 0;
	if ($sales_person == 0)
		$sales_person_name = _('All Sales person');
	else
		$sales_person_name = get_salesman_name($sales_person);

	$dec = user_price_dec();
	
	if($summary == 0)
	{
	$cols = array(0, 50,90,120,220,260,300,330,350,400,450,500,550);

	$headers = array(_('Inv No.'), _('Inv Date'), _('Locn'), _('Customer'), _('LPO No.'), _('LPO Date'),	
	_('SO No.'), _('Units'), _('Qunatity'),	 _('Unit Rate'), _('Disc'), _('Net Amount'));

	$aligns = array('left',	'left',	'left', 'left', 'left', 'left',	'left',	'left', 
	'right', 'right',	'right',	'right');

	

    $params =   array( 	0 => $comments,
	    				1 => array(  'text' => _('Period'), 'from' => $from, 'to' => $to),
						2 => array('text' => _('Sales Person'), 'from' => $sales_person_name, 'to' => ''),
	    				3 => array(  'text' => _('Summary Only'),'from' => $sum,'to' => ''));

	$aligns2 = $aligns;

	$rep = new FrontReport(_('SalesManwise Items Sales Register'), "SalesManwise Items Sales Register", user_pagesize(), 8, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
	$cols2 = $cols;
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);

	$rep->NewPage();
	
	$total_salesper_inv =  $item_total_net_amt = $grand_total = 0;
	
	$salesper =  $item_name = $item_names = '';

	$result = GetSalesmanTrans($from, $to, $sales_person);

	while ($myrow=db_fetch($result))
	{
		
		if ($item_name != $myrow['description'])
		  {
			
			if ($item_name != '')
			{
				
				$rep->NewLine(1,2);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(2, 6, $item_name. _(' Total:'));
				$rep->AmountCol(11, 12, $item_total_net_amt, $dec);
				$rep->SetFont('', '', 0);
				$rep->NewLine();
				$rep->Line($rep->row - 4);
				$rep->NewLine();
				$rep->NewLine();
				$item_total_net_amt =  0.0;
			}
			$item_name = $myrow['description'];
			$item_code = $myrow['stock_id'];
		   }
		   
		
		
		if ($salesper != $myrow['salesman_name'])
		{
			if ($salesper != '')
			{
				$rep->NewLine(2, 3);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(4, 6, _('Total By ').$salesper);
				$rep->AmountCol(11, 12, $total_salesper_inv, $dec);
				$rep->SetFont('', '', 0);
				$rep->NewLine();
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$rep->NewLine();
				$total_salesper_inv = 0.0;
			}
			$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 4, _('SalesMan : ').$myrow['salesman_name']);
			$rep->SetFont('', '', 0);
			$salesper = $myrow['salesman_name'];
			$rep->NewLine();
		}
		
		if ($item_names != $myrow['description'])
		{
			$rep->NewLine();
			$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 5, $myrow['stock_id']." - ".$myrow['description']);
			$rep->SetFont('', '', 0);
			$item_names = $myrow['description'];
			$rep->NewLine();
		}
		
		
		
		$rate = $myrow['rate'];
		
		
		if (!$summary)
		{
			
			$so_loc = get_so_location($myrow['order_']);
			
			$DisplayQty = number_format2($myrow["quantity"],get_qty_dec($myrow['stock_id']));
			$DisplayFOCQty = number_format2($myrow["foc_quantity"],get_qty_dec($myrow['stock_id']));
			$DisplayPrice = number_format2($myrow["unit_price"] * $myrow["rate"],$dec);
            $discount_amount =  (($myrow["unit_price"] * $myrow["quantity"] * $myrow["discount_percent"]/100) * $myrow["rate"]);		
            $DisplayDiscAmt = number_format2($discount_amount,$dec);
            $Net = round2((($myrow["quantity"] * $myrow["unit_price"])-($myrow["quantity"] * $myrow["unit_price"]* ($myrow["discount_percent"]/100)) * $myrow["rate"]) ,user_price_dec());
			$DisplayNet = number_format2($Net,$dec);
			
			$rep->TextCol(0, 1,	$myrow['reference']);
			$rep->DateCol(1, 2,	$myrow['tran_date'], true);
			$rep->TextCol(2, 3,	$so_loc['from_stk_loc']);
			$rep->TextCol(3, 4,	$myrow['cust_code']." - ".$myrow['name']);
			$rep->TextCol(4, 5,	$myrow['lpo_no']);
			$rep->DateCol(5, 6,	$myrow['lpo_date'], true);
			$rep->TextCol(6, 7,	$so_loc['reference']);
			if($myrow['unit']==1){
				 $item_info = get_item_edit_info($myrow["stock_id"]);	
				 $rep->TextCol(7, 8,	$item_info["units"], -2);
			}
			else if($myrow["unit"]==2){
		         $sec_unit_info = get_item_sec_unit_info($myrow["stock_id"]);
	             $rep->TextCol(7, 8,$sec_unit_info["sec_unit_name"], -2);
            }	
			
			
			$rep->TextCol(8, 9,	  $DisplayQty, -2);
			$rep->TextCol(9, 10,  $DisplayPrice, -2);
			$rep->TextCol(10, 11, $DisplayDiscAmt, -2);
			$rep->TextCol(11, 12, $DisplayNet);
			$rep->NewLine();
			
			$total_salesper_inv   +=  $Net;
			$item_total_net_amt   +=  $Net;
			$grand_total          +=  $Net;
			
		}
		
	}
	 if ($item_name != '')
	{
		$rep->NewLine(1, 2);
		$rep->SetFont('helvetica', 'B', 9);
		$rep->TextCol(2, 6, $item_name. _(' Total'));
		$rep->AmountCol(11, 12, $item_total_net_amt, $dec);
		$rep->SetFont('', '', 0);
		$rep->NewLine();
		$rep->Line($rep->row - 2);
		$rep->NewLine(1);
		
	}
	
	$rep->NewLine(1, 2);
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(3, 6, _('Total By ').$salesper);
	$rep->AmountCol(11, 12, $total_salesper_inv, $dec);
	$rep->SetFont('', '', 0);
	
	$rep->NewLine();
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->NewLine();
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(7, 9, _('Grand Total'));
	$rep->AmountCol(11, 12, $grand_total, $dec);
	$rep->SetFont('', '', 0);
	$rep->NewLine();
	$rep->End();
	
}
else
{
	$cols = array(0,100,250,300,360,420,480,540);

	$headers = array(_('Item Code'),_('Item Name '), _('Units'),
	_('Quantity'), _('Item Amt'), _('Disc Amt'), _('Net Amount'));
	

	$aligns = array('left',	'left',	'left', 'right', 'right', 'right', 'right');

    $params =   array( 	0 => $comments,
	    				1 => array(  'text' => _('Period'), 'from' => $from, 'to' => $to),
	    				2 => array(  'text' => _('Summary Only'),'from' => $sum,'to' => ''));


	$rep = new FrontReport(_('SalesManwise Items Sales Summary'), "SalesManwiseItemsSalesSummary",
	user_pagesize(), 8, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
	$cols2 = $cols;
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);

	$rep->NewPage();
	
	
	$total_salesper_inv =  $item_total_net_amt = $grand_total = 0;
	
	$itm_total_qty = $itm_total_amt = $itm_total_disc_amt = 0;
	
	$salesper =  $item_name = $item_names = '';

	$result = GetSalesmanTrans($from, $to, $sales_person);
	$itm_units = '';
	while ($myrow=db_fetch($result))
	{
		
		if ($item_name != $myrow['description'])
		  {
			
			if ($item_name != '')
			{
				
				$rep->NewLine(0,1);
				$rep->TextCol(0, 1, $item_code);
				$rep->TextCol(1, 2, $item_name);
				
				if($myrow['unit']==1){
				 $item_info = get_item_edit_info($myrow["stock_id"]);	
				 $rep->TextCol(2, 3,	$item_info["units"], -2);
				 $itm_units = $item_info["units"];
				}
				else if($myrow["unit"]==2){
		         $sec_unit_info = get_item_sec_unit_info($myrow["stock_id"]);
	             $rep->TextCol(2, 3,$sec_unit_info["sec_unit_name"], -2);
				  $itm_units = $sec_unit_info["sec_unit_name"];
                }	
				
				$rep->TextCol(3, 4,	$itm_total_qty, -2);
			    $rep->TextCol(4, 5, $itm_total_amt, -2);
			    $rep->TextCol(5, 6, $itm_total_disc_amt, -2);
				$rep->AmountCol(6, 7, $item_total_net_amt, $dec);
				
				$rep->NewLine();
				
				$itm_total_qty = $itm_total_amt = $itm_total_disc_amt = 0;
				$item_total_net_amt =  0.0;
			}
			$item_name = $myrow['description'];
			$item_code = $myrow['stock_id'];
			$item_units = $itm_units;
		   }
		   
		
		
		if ($salesper != $myrow['salesman_name'])
		{
			if ($salesper != '')
			{
				$rep->NewLine(2, 3);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(3, 6, _('Total By ').$salesper);
				$rep->AmountCol(6, 7, $total_salesper_inv, $dec);
				$rep->SetFont('', '', 0);
				$rep->NewLine();
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$rep->NewLine();
				$total_salesper_inv = 0.0;
			}
			$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 4, _('SalesMan : ').$myrow['salesman_name']);
			$rep->SetFont('', '', 0);
			$salesper = $myrow['salesman_name'];
			$rep->NewLine();
		}
		
		/*
		if ($item_names != $myrow['description'])
		{
			$rep->NewLine();
			
			$rep->TextCol(0, 5, $myrow['stock_id']." - ".$myrow['description']);
			
			$item_names = $myrow['description'];
			$rep->NewLine();
		}
		*/
		
		
		$rate = $myrow['rate'];
		
		
		
			
			$so_loc = get_so_location($myrow['order_']);
			
			$DisplayQty = number_format2($myrow["quantity"],get_qty_dec($myrow['stock_id']));
			$DisplayFOCQty = number_format2($myrow["foc_quantity"],get_qty_dec($myrow['stock_id']));
			$DisplayPrice = number_format2($myrow["unit_price"] * $myrow["rate"],$dec);
            $discount_amount =  (($myrow["unit_price"] * $myrow["quantity"] * $myrow["discount_percent"]/100) * $myrow["rate"]);		
            $DisplayDiscAmt = number_format2($discount_amount,$dec);
            $Net = round2((($myrow["quantity"] * $myrow["unit_price"])-($myrow["quantity"] * $myrow["unit_price"]* ($myrow["discount_percent"]/100)) * $myrow["rate"]) ,user_price_dec());
			$DisplayNet = number_format2($Net,$dec);
			
			
			
			
			//$rep->TextCol(8, 9,	  $DisplayQty, -2);
			//$rep->TextCol(9, 10,  $DisplayPrice, -2);
			//$rep->TextCol(10, 11, $DisplayDiscAmt, -2);
			//$rep->TextCol(11, 12, $DisplayNet);
			//$rep->NewLine();
			
			$item_price = (($myrow["unit_price"] * $myrow["quantity"]) * $myrow["rate"]);
			
			$itm_total_qty+=  $myrow["quantity"];
			$itm_total_amt+=  $item_price;
			$itm_total_disc_amt+=  $discount_amount;
			
			$total_salesper_inv+=$Net;
			$item_total_net_amt+=$Net;
			$grand_total+=$Net;
			
		
		
	}
	if ($item_name != '')
	{
		$rep->NewLine(0, 1);
		$rep->TextCol(0, 1, $item_code);
		$rep->TextCol(1, 2, $item_name);
		$rep->TextCol(2, 3, $item_units);
		$rep->TextCol(3, 4,	$itm_total_qty, -2);
		$rep->TextCol(4, 5, $itm_total_amt, -2);
		$rep->TextCol(5, 6, $itm_total_disc_amt, -2);
		$rep->AmountCol(6, 7, $item_total_net_amt, $dec);
		$rep->NewLine();
		
		
	}
	
	$rep->NewLine(1, 2);
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(3, 6, _('Total By ').$salesper);
	$rep->AmountCol(6, 7, $total_salesper_inv, $dec);
	$rep->SetFont('', '', 0);
	
	$rep->NewLine();
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->NewLine();
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(5, 6, _('Grand Total'));
	$rep->AmountCol(6, 7, $grand_total, $dec);
	$rep->SetFont('', '', 0);
	$rep->NewLine();
	$rep->End();
 }
}

