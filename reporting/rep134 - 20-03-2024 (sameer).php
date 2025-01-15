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
$page_security = 'SA_SALESMAN_COLLECTION_REP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Sales Summary Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//------------------------------------------------------------------


print_sales_summary_report();

function getTransactions($from, $to, $folk)
{
	$fromdate = date2sql($from);
	$todate = date2sql($to);

	$sql = "SELECT cust.name,cust.curr_code,cust.cust_code,salesman.salesman_name,  
			SUM(ov_amount+ov_freight+ov_discount+ov_roundoff) AS total
		FROM ".TB_PREF."debtor_trans trans,
			 ".TB_PREF."debtors_master cust,
			 ".TB_PREF."salesman salesman
		WHERE trans.sales_person_id=salesman.salesman_code
		AND trans.debtor_no=cust.debtor_no
		AND trans.tran_date >= '$fromdate'
 		AND trans.tran_date <= '$todate'
		AND (trans.type=".ST_CUSTPAYMENT.") 
		AND trans.ov_amount!=0";
		
	if ($folk != 0)
	       $sql .= " AND trans.sales_person_id=".db_escape($folk);	
		
	$sql .= " GROUP BY trans.sales_person_id,trans.debtor_no 
		ORDER BY trans.sales_person_id,trans.debtor_no";
		
    return db_query($sql,"No transactions were returned");
}


//---------------------------------------------------------------------------------------

function print_sales_summary_report()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$folk    = $_POST['PARAM_2']; 
	$comments = $_POST['PARAM_3'];
	$orientation = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];
	
	


	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	$orientation = ($orientation ? 'L' : 'P');

	if ($folk == ALL_NUMERIC)
        $folk = 0;
    if ($folk == 0)
        $salesfolk = _('All Sales Man');
     else
        $salesfolk = get_salesman_name($folk);
	
	$dec = user_price_dec();

	if ($currency == ALL_TEXT)
    {
        $convert = true;
        $currency = _('Balances in Home Currency');
    }
    else
        $convert = false;
	

	$rep = new FrontReport(_('SalesManwise Collection Register - Summary'), 
	"SalesManwiseCollectionRegisterSummary", user_pagesize(), 9, $orientation);

	$params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
						2 => array('text' => _('Sales Folk'), 'from' => $salesfolk,	'to' => ''));

	$cols = array(0, 180, 400, 500);

	$headers = array(_('Customer Code'), _('Customer Name'), _('Amount'));
	$aligns = array('left', 'left', 'right');
	
    if ($orientation == 'L')
    	recalculate_cols($cols);

	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->NewPage();
	
	$totalnet = 0.0;
	$totaltax = 0.0;
	$transactions = getTransactions($from, $to, $folk);

	$custno = 0;
	$total = $grand_total =  0;
	$salesper = '';
	while ($trans=db_fetch($transactions))
	{
		
		if ($salesper != $trans['salesman_name'])
		{
			if ($salesper != '')
			{
				$rep->NewLine(2, 3);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(1, 2, _('Total Collection By ').$salesper);
				//$rep->TextCol(1, 4, $salesper);
				$rep->AmountCol(2, 3, $total, $dec);
				$rep->SetFont('', '', 0);
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$rep->NewLine();
				$total = 0.0;
			}
			$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 2, _('SalesMan : ').$trans['salesman_name']);
			$rep->SetFont('', '', 0);
			$salesper = $trans['salesman_name'];
			$rep->NewLine();
		}
				
		$rep->TextCol(0, 1, $trans['cust_code']);
        $rep->TextCol(1, 2, $trans['name']);
		$rep->AmountCol(2, 3, $trans['total'], $dec);
		
		$total += $trans['total'];
		$grand_total += $trans['total'];
		
		$rep->NewLine();
		 
	}
	
	if ($salesper != '')
	{
	$rep->NewLine(2, 3);
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(1, 2, _('Total Collection By ').$salesper);
	$rep->AmountCol(2, 3, $total, $dec);
	$rep->NewLine(1);
	}
	
	$rep->Line($rep->row  - 4);
	$rep->NewLine(2);
	
	$rep->TextCol(1, 2, _('Grand Total'));
	//$rep->TextCol(2, 4, $salesper);
	$rep->AmountCol(2, 3, $grand_total, $dec);
	$rep->NewLine();
	$rep->SetFont('', '', 0);

	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

