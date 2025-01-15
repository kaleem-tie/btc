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

print_salesmanwise_customerwise_sales_summary();


function getTransactions($from_date, $to_date, $customer=0, $sales_person=0)
{
	
	$from_date = date2sql($from_date);
	$to_date = date2sql($to_date);
	
     $sql = "SELECT SUM((dtd.quantity*dtd.unit_price)*dt.rate *IF(dt.type=10,1,-1)) AS gross_amt,
	    SUM((unit_price*quantity*discount_percent/100)*dt.rate *IF(dt.type=10,1,-1)) AS disc_amt,
		debtor.cust_code,debtor.name,salesman.salesman_name
		FROM ".TB_PREF."debtor_trans_details dtd,
		".TB_PREF."debtor_trans dt,
        ".TB_PREF."debtors_master debtor ,
        ".TB_PREF."salesman salesman 		
		WHERE dt.debtor_no=debtor.debtor_no
		AND dt.sales_person_id=salesman.salesman_code
		AND dt.trans_no=dtd.debtor_trans_no 
		AND dt.type=dtd.debtor_trans_type AND dt.type in (10,11) 
		AND dtd.debtor_trans_type in (10,11) 
		AND dt.tran_date >= '$from_date' AND dt.tran_date <= '$to_date'
		AND dt.ov_amount!=0";
		
		if($customer != ''){
		$sql .= " AND dt.debtor_no = ".db_escape($customer);
	    }
	
	    if ($sales_person != 0)
	    $sql .= " AND dt.sales_person_id=".db_escape($sales_person);
		
	    $sql .= " GROUP BY dt.sales_person_id,dt.debtor_no ORDER BY dt.sales_person_id,dt.debtor_no";
 
   return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------

function print_salesmanwise_customerwise_sales_summary()
{
    	global $path_to_root, $systypes_array;

    	$from           = $_POST['PARAM_0'];
    	$to             = $_POST['PARAM_1'];
		$customer       = $_POST['PARAM_2'];
		$sales_person   = $_POST['PARAM_3'];
    	$comments       = $_POST['PARAM_4'];
	    $orientation    = $_POST['PARAM_5'];
	    $destination    = $_POST['PARAM_6'];
		
	
		
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
	
	

	$cols = array(0, 50,250,350,450,520);
	
	$headers2 = array(_('Customer Code'),  _('Customer Name'),  _('Gross Amt'), 
	  _('Disc Amt'), _('Net Amt'));
	
	$aligns = array('left',	'left',	'right', 'right', 'right');
	
	
	$aligns2 = $aligns;

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Sales Person'),'from' =>$salesfolk,'to' => ''));
						

    $rep = new FrontReport(_('SalesManwise Customerwise Sales Summary'), 
	"SalesManwiseCustomerwiseSalesSummary", user_pagesize(), 8, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$cols2 = $cols;
	
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
    $rep->NewPage();

	

	$result = getTransactions($from, $to, $customer,  $sales_person);
	
	$gross_amt_total = $disc_amt_total = $net_amt_total = 0;
	
    $grand_gross_amt_total = $grand_disc_amt_total = $grand_net_amt_total = 0;
	
	$salesper =  '';
	
	while ($myrow = db_fetch($result))
	{
		
		   
		   
		if ($salesper != $myrow['salesman_name'])
		{
			if ($salesper != '')
			{
				$rep->NewLine(2, 3);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(0, 2, _('Total of ').$salesper);
				$rep->AmountCol(2, 3, $gross_amt_total,$dec);
		        $rep->AmountCol(3, 4, $disc_amt_total,$dec);
		        $rep->AmountCol(4, 5, $net_amt_total,$dec);
		        
				$rep->SetFont('', '', 0);
				$rep->NewLine();
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$rep->NewLine();
				$gross_amt_total = $disc_amt_total = $net_amt_total = 0.0;
			}
			$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 4, _('SalesMan : ').$myrow['salesman_name']);
			$rep->SetFont('', '', 0);
			$salesper = $myrow['salesman_name'];
			$rep->NewLine();
		}
		   
	
		    $net_amt = $myrow['gross_amt']-$myrow['disc_amt'];
		   
			$rep->TextCol(0, 1,	$myrow['cust_code']);
			$rep->TextCol(1, 2,	$myrow['name']);
			$rep->AmountCol(2, 3, $myrow['gross_amt'],$dec);
		    $rep->AmountCol(3, 4, $myrow['disc_amt'],$dec);
		    $rep->AmountCol(4, 5, $net_amt,$dec);
		
			$rep->NewLine();
		
		 
		$gross_amt_total+=$myrow['gross_amt'];
		$disc_amt_total+=$myrow['disc_amt'];
		$net_amt_total+=$net_amt;	
		
		$grand_gross_amt_total+=$myrow['gross_amt'];
		$grand_disc_amt_total+=$myrow['disc_amt'];
		$grand_net_amt_total+=$net_amt;
		
	}
	

	
	
	$rep->NewLine(1, 2);
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(0, 2, _('Total ').$salesper);
	$rep->AmountCol(2, 3, $gross_amt_total,$dec);
	$rep->AmountCol(3, 4, $disc_amt_total,$dec);
	$rep->AmountCol(4, 5, $net_amt_total,$dec);
	$rep->SetFont('', '', 0);
	
	$rep->NewLine();
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->NewLine();
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(1, 2, _('Grand Total'));
	$rep->AmountCol(2, 3, $grand_gross_amt_total,$dec);
	$rep->AmountCol(3, 4, $grand_disc_amt_total,$dec);
	$rep->AmountCol(4, 5, $grand_net_amt_total,$dec);
	$rep->SetFont('', '', 0);
	
    $rep->End();
}

