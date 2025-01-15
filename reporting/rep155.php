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
	
	
	$sql = "SELECT sales_person_ref,reference,tran_date,ref_no,sales_person_id,sum(ov_amount) as collection FROM ".TB_PREF."debtor_trans where tran_date between '$fromdate' and '$todate' and sales_person_ref!='' and type=12";

	if ($sales_person != 0)
		$sql .= " AND sales_person_id=".db_escape($sales_person);
	$sql.=" group by sales_person_ref,reference,tran_date,ref_no,sales_person_id order by tran_date";
		
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
	$comments      = $_POST['PARAM_3'];
	$orientation   = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	
	$orientation = ($orientation ? 'L' : 'P');

	
	
	if ($sales_person == ALL_NUMERIC)
		$sales_person = 0;
	if ($sales_person == 0)
		$sales_person_name = _('All Sales person');
	else
		$sales_person_name = get_salesman_name($sales_person);

	$dec = user_price_dec();
	
	if($summary == 0)
	{
	$cols = array(0, 50, 120,200,300,400,450,515);

	$headers = array(_('Ref No.'), _('Doc No'), _('Doc Date'), _('Received From'), _('SMan'), _('Ref. Date'),	
	_('Amount'));

	$aligns = array('left',	'left',	'center', 'left', 'left', 'center',	'right');

	

    $params =   array( 	0 => $comments,
	    				1 => array(  'text' => _('Period'), 'from' => $from, 'to' => $to),
						2 => array('text' => _('Sales Person'), 'from' => $sales_person_name, 'to' => ''),
	    				3 => array(  'text' => _('Summary Only'),'from' => $sum,'to' => ''));

	$aligns2 = $aligns;

	$rep = new FrontReport(_('SalesManwise Cash Receipt'), "SalesManwiseCashReceipt", user_pagesize(), 8, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
	$cols2 = $cols;
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);

	$rep->NewPage();


	$result = GetSalesmanTrans($from, $to, $sales_person);
	$total_collection=0;

	while ($myrow=db_fetch($result))
	{
		
			$rep->NewLine();
			
			$rep->TextCol(0, 1,	$myrow['sales_person_ref']);
			$rep->TextCol(1, 2,	$myrow['reference']);
			$rep->DateCol(2, 3,	$myrow['tran_date'], true);
			$rep->TextCol(3, 4,	$myrow['ref_no']);
			$rep->TextCol(4, 5,	get_salesman_name($myrow['sales_person_id']));
			$rep->DateCol(5, 6,	$myrow['tran_date'], true);
			$rep->AmountCol(6, 7, $myrow['collection'], 3);
			$total_collection+=$myrow['collection'];
	}
	$rep->NewLine();
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->NewLine(1, 2);
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(1, 3, _('Grand Total : '));
	$rep->AmountCol(6, 7, $total_collection, 3);
	

	$rep->End();
	
}
}

