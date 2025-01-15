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

print_salesmanwise_do_summary_wp_register();


function getTransactions($from, $to, $sales_person=0, $location=0)
{
	
	$from = date2sql($from);
	$to = date2sql($to);
	
	$home_curr = get_company_currency();
	
	$sql="SELECT dt.type,dt.trans_no,dt.order_,dt.reference,dm.cust_code,dm.name,dt.tran_date,
	sum(dt.ov_amount+dt.ov_gst+dt.ov_discount+dt.ov_freight+dt.ov_freight_tax+dt.ov_roundoff) as net_amount,
	sum((dt.ov_amount+dt.ov_gst+dt.ov_discount+dt.ov_freight+dt.ov_freight_tax+dt.ov_roundoff)*dt.rate) as net_local_amount,dt.rate,dt.payment_terms,so.from_stk_loc,dt.debtor_no,dm.curr_code,
	so.customer_ref,dt.lpo_no,dt.lpo_date,dt.sales_person_id,sm.salesman_name,
	sum(dt.ov_amount+dt.ov_freight+dt.ov_discount+dt.ov_roundoff) as taxable_amount,
	sum(dt.ov_gst+dt.ov_freight_tax) as vat_value,
	sum((dt.ov_amount+dt.ov_discount+dt.ov_freight+dt.ov_roundoff)*dt.rate) as local_taxable_amount
	FROM ".TB_PREF."debtor_trans dt,
	".TB_PREF."debtors_master dm,
	".TB_PREF."sales_orders so,
	".TB_PREF."salesman sm 
	WHERE sm.salesman_code=dt.sales_person_id
	AND dm.debtor_no=dt.debtor_no 
	AND dt.order_=so.order_no AND so.trans_type=30 
	AND dt.type ='13' 
	AND dt.ov_amount!=0 AND dt.reference !='auto'
	AND dt.tran_date >= '$from' AND dt.tran_date <= '$to'";
	
	if ($sales_person != 0)
	$sql .= " AND dt.sales_person_id=".db_escape($sales_person);
	
	if ($location != '')
		$sql .= " AND so.from_stk_loc = ".db_escape($location);	
	
	$sql .= "  GROUP BY dt.trans_no,dt.type order by dt.sales_person_id,dt.tran_date,dt.trans_no";
	
	
    return db_query($sql,"No transactions were returned");
}


function get_trans_cost_value($trans_no)
{
	$sql = "SELECT sum(quantity*standard_cost)
		FROM ".TB_PREF."debtor_trans_details WHERE debtor_trans_type=13 and debtor_trans_no=".db_escape($trans_no);
		
    $result = db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	return $row[0];
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

function print_salesmanwise_do_summary_wp_register()
{
    	global $path_to_root, $systypes_array;

    	$from           = $_POST['PARAM_0'];
    	$to             = $_POST['PARAM_1'];
		$sales_person   = $_POST['PARAM_2'];
		$location       = $_POST['PARAM_3'];
    	$comments       = $_POST['PARAM_4'];
	    $orientation    = $_POST['PARAM_5'];
	    $destination    = $_POST['PARAM_6'];
		
	
		
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
	
	
	
	if ($location == '')
		$loc = _('All');
	else
		$loc = get_location_name($location);

	$cols = array(0, 40,80,200,240,280,320,360,400,450,500,550);
	
	$headers2 = array(_('DO No.'),  _('DO Date'),  _('Customer'), 
	  _('LPO No.'), _('LPO Date'),  _('Loct'), _('Area'), 
	  _('Amount'), _('Total Cost'), _('Profit'), _('Profit %'));
	
	$aligns = array('left',	'left',	'left',	'left','left', 'left','left', 
	'right', 'right', 'right', 'right');
	
	
	$aligns2 = $aligns;

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
						2 => array('text' => _('Sales Person'), 'from' => $salesfolk, 'to' => ''),
						3 => array('text' => _('Location'), 'from' => $loc, 'to' => ''));
						

    $rep = new FrontReport(_('SalesManwise DO summary (WP)'), 
	"SalesManwiseDOsummary(WP)", user_pagesize(), 7, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$cols2 = $cols;
	
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
    $rep->NewPage();

	

	$result = getTransactions($from, $to, $sales_person, $location);
	
    $total_profit = $total_cost_amount = $total_salesper_do = 0.0;
	
	$grand_total_profit = $grand_total_cost_amount = $grand_total_salesper_do = 0.0;
	
	$salesper =  $customer_name = $cust_names = '';
	
	while ($myrow = db_fetch($result))
	{
		
           $so_loc = get_so_location($myrow['order_']);
		  
			
			if($myrow['reference']=='auto')
				$do_ref = "auto - ".$myrow['trans_no'];
			else
			   $do_ref = $myrow['reference'];
		   
		   
		   if($so_loc['reference']=='auto')
				$so_ref = "auto - ".$so_loc['order_no'];
			else
			   $so_ref = $so_loc['reference'];
		   
		   
		  $cust_br_area = get_cust_branch_area_name_rep($myrow['debtor_no']); 
		   
		   
		if ($salesper != $myrow['salesman_name'])
		{
			if ($salesper != '')
			{
				$rep->NewLine(2, 3);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(3, 5, _('Total of ').$salesper);
				$rep->AmountCol(7, 8, $total_salesper_do,$dec);
		        $rep->AmountCol(8, 9, $total_cost_amount,$dec);
		        $rep->AmountCol(9, 10, $total_profit,$dec);
				if($total_profit!=0)
		        $rep->AmountCol(10, 11, (($total_profit/$total_salesper_do)*100),$dec);	
				$rep->SetFont('', '', 0);
				$rep->NewLine();
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$rep->NewLine();
				$total_salesper_do = $total_cost_amount = $total_profit = 0.0;
			}
			$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 4, _('SalesMan : ').$myrow['salesman_name']);
			$rep->SetFont('', '', 0);
			$salesper = $myrow['salesman_name'];
			$rep->NewLine();
		}
		   
	
		   
		   
			$rep->TextCol(0, 1,	$do_ref);
			$rep->DateCol(1, 2,	$myrow['tran_date'], true);
			$rep->TextCol(2, 3,	$myrow['cust_code']." - ".$myrow['name']);
			$rep->TextCol(3, 4,	$myrow['lpo_no']);
			$rep->DateCol(4, 5,	$myrow['lpo_date'], true);
			$rep->TextCol(5, 6,	$so_loc['from_stk_loc']);
			$rep->TextCol(6, 7,	 $cust_br_area['description']);
			
			
			$cost_amount=get_trans_cost_value($myrow['trans_no']);
			$profit=$myrow['local_taxable_amount']-$cost_amount;
			
			
			
			$rep->AmountCol(7, 8, $myrow['local_taxable_amount'],$dec);
		    $rep->AmountCol(8, 9, $cost_amount,$dec);
		    $rep->AmountCol(9, 10, $profit,$dec);
		    $rep->AmountCol(10, 11, ($profit/$myrow['local_taxable_amount'])*100,$dec);	
		
			$rep->NewLine();
		
		 
		$total_salesper_do+=$myrow['local_taxable_amount'];
		$total_cost_amount+=$cost_amount;
		$total_profit+=$profit;	
		
		$grand_total_salesper_do+=$myrow['local_taxable_amount'];
		$grand_total_cost_amount+=$cost_amount;
		$grand_total_profit+=$profit;	
		
	}
	
	
	$rep->NewLine(1, 2);
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(3, 5, _('Total ').$salesper);
	$rep->AmountCol(7, 8, $total_salesper_do, $dec);
	$rep->AmountCol(8, 9, $total_cost_amount, $dec);
	$rep->AmountCol(9, 10, $total_profit, $dec);
	if($total_profit!=0)
	$rep->AmountCol(10, 11, (($total_profit/$total_salesper_do)*100),$dec);	
	$rep->SetFont('', '', 0);
	
	$rep->NewLine();
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->NewLine();
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(5, 7, _('Grand Total'));
	$rep->AmountCol(7, 8, $grand_total_salesper_do, $dec);
	$rep->AmountCol(8, 9, $grand_total_cost_amount, $dec);
	$rep->AmountCol(9, 10, $grand_total_profit, $dec);
	if($grand_total_profit!=0)
	$rep->AmountCol(10, 11, (($grand_total_profit/$grand_total_salesper_do)*100),$dec);	
	$rep->SetFont('', '', 0);
	
    $rep->End();
}

