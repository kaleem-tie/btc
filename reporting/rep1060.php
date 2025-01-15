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
$page_security = 'SA_SALESMANREP';
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

	$sql = "SELECT DISTINCT sorder.*,
			cust.name AS DebtorName,
			cust.curr_code,
			salesman.*
		FROM ".TB_PREF."debtors_master cust,
			 ".TB_PREF."sales_orders sorder,
			 ".TB_PREF."salesman salesman
		WHERE sorder.trans_type = ".ST_SALESORDER."
		    AND sorder.sales_person_id=salesman.salesman_code
		    AND sorder.debtor_no=cust.debtor_no
		    AND sorder.ord_date>='$fromdate'
		    AND sorder.ord_date<='$todate'";
			
	if ($sales_person != 0)
	$sql .= " AND sorder.sales_person_id=".db_escape($sales_person);	
		
		
		$sql .= " ORDER BY salesman.salesman_code, sorder.ord_date";
		
	return db_query($sql, "Error getting order details");
}

//----------------------------------------------------------------------------------------------------

function print_salesman_list()
{
	global $path_to_root;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$sales_person  = $_POST['PARAM_2'];
	$summary = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$orientation = $_POST['PARAM_5'];
	$destination = $_POST['PARAM_6'];
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	$orientation = ($orientation ? 'L' : 'P');

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
	$cols = array(0, 50, 150, 225, 325,	385, 450, 515);

	$headers = array(_('Order'), _('Customer'), _(''), _('Customer Ref'),
		_('Inv Date'),	_('Total'),	_('Provision'));

	$aligns = array('left',	'left',	'left', 'left', 'left', 'right',	'right');

	$headers2 = array(_('Salesman'), " ",	_('Phone'), _('Email'),	_('Provision'),
		_('Break Pt.'), _('Provision')." 2");

    $params =   array( 	0 => $comments,
	    				1 => array(  'text' => _('Period'), 'from' => $from, 'to' => $to),
						2 => array('text' => _('Sales Person'), 'from' => $sales_person_name, 'to' => ''),
	    				3 => array(  'text' => _('Summary Only'),'from' => $sum,'to' => ''));

	$aligns2 = $aligns;

	$rep = new FrontReport(_('Salesman Listing (Orders)'), "SalesmanListing", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
	$cols2 = $cols;
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);

	$rep->NewPage();
	$salesman = 0;
	$subtotal = $total = $subprov = $provtotal = 0;

	$result = GetSalesmanTrans($from, $to, $sales_person);

	while ($myrow=db_fetch($result))
	{
		$rep->NewLine(0, 2, false, $salesman);
		if ($salesman != $myrow['salesman_code'])
		{
			if ($salesman != 0)
			{
				$rep->Line($rep->row - 8);
				$rep->NewLine(2);
				$rep->TextCol(0, 3, _('Total'));
				$rep->AmountCol(5, 6, $subtotal, $dec);
				$rep->AmountCol(6, 7, $subprov, $dec);
    			$rep->Line($rep->row  - 4);
    			$rep->NewLine(2);
			}
			$rep->TextCol(0, 2,	$myrow['salesman_code']." ".$myrow['salesman_name']);
			$rep->TextCol(2, 3,	$myrow['salesman_phone']);
			$rep->TextCol(3, 4,	$myrow['salesman_email']);
			$rep->TextCol(4, 5,	number_format2($myrow['provision'], user_percent_dec()) ." %");
			$rep->AmountCol(5, 6, $myrow['break_pt'], $dec);
			$rep->TextCol(6, 7,	number_format2($myrow['provision2'], user_percent_dec()) ." %");
			$rep->NewLine(2);
			$salesman = $myrow['salesman_code'];
			$total += $subtotal;
			$provtotal += $subprov;
			$subtotal = 0;
			$subprov = 0;
		}
		
		$amt = $myrow['total'] ;
		
		if ($myrow['provision2'] == 0)
			$prov = $myrow['provision'] * $amt / 100;
		else {
			$amt1 = min($amt, max(0, $myrow['break_pt']-$subtotal));
			$amt2 = $amt - $amt1;

			$prov = $amt1*$myrow['provision']/100 + $amt2*$myrow['provision2']/100;
		}
		if (!$summary)
		{
			if ($myrow['type']!= ST_CUSTCREDIT )
	        $rep->SetTextColor(33, 33, 33);
	        else
	        $rep->SetTextColor(216, 67, 21);
			$rep->TextCol(0, 1,	$myrow['order_no']);
			$rep->TextCol(1, 3,	$myrow['DebtorName']);
			$rep->TextCol(3, 4,	$myrow['customer_ref']);
			$rep->DateCol(4, 5,	$myrow['ord_date'], true);
			$rep->AmountCol(5, 6, $amt, $dec);
			$rep->AmountCol(6, 7, $prov, $dec);
			$rep->NewLine();
			$rep->SetTextColor(33, 33, 33);
		}
		$subtotal += $amt;
		$subprov += $prov;
	}
	if ($salesman != 0)
	{
		$rep->Line($rep->row - 4);
		$rep->NewLine(2);
		$rep->TextCol(0, 3, _('Total'));
		$rep->AmountCol(5, 6, $subtotal, $dec);
		$rep->AmountCol(6, 7, $subprov, $dec);
		$rep->Line($rep->row  - 4);
		$rep->NewLine(2);
		$total += $subtotal;
		$provtotal += $subprov;
	}
	$rep->fontSize += 2;
	$rep->TextCol(0, 3, _('Grand Total'));
	$rep->fontSize -= 2;
	$rep->AmountCol(5, 6, $total, $dec);
	$rep->AmountCol(6, 7, $provtotal, $dec);
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
	$rep->End();
	
}
else
{
	$cols = array(0, 50, 385, 515);

	$headers = array(_('S.No'),_('Salesman '), _('Total'));

	$aligns = array('left',	'left',	'right');

    $params =   array( 	0 => $comments,
	    				1 => array(  'text' => _('Period'), 'from' => $from, 'to' => $to),
	    				2 => array(  'text' => _('Summary Only'),'from' => $sum,'to' => ''));


	$rep = new FrontReport(_('Salesman Listing (Orders)'), "SalesmanListing", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
	$cols2 = $cols;
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);

	$rep->NewPage();
	$salesman = 0;
	$subtotal = $total = $subprov = $provtotal = 0;

	$result = GetSalesmanTrans($from, $to, $sales_person);
	$k=1;

	while ($myrow=db_fetch($result))
	{
		$rep->NewLine(0, 2, false, $salesman);
		if ($salesman != $myrow['salesman_code'])
		{
			if ($salesman != 0)
			{
				$rep->TextCol(0, 1,	$k);
				$rep->TextCol(1, 2,	$salespersonname);
				$rep->AmountCol(2, 3, $subtotal, $dec);
				$rep->NewLine();
				$k++;
				
			}
			$salesman = $myrow['salesman_code'];
			$salespersonname=$myrow['salesman_name'];
			$total += $subtotal;
			$provtotal += $subprov;
			$subtotal = 0;
			$subprov = 0;
		}
		
		$amt = $myrow['total'];
		
		if ($myrow['provision2'] == 0)
			$prov = $myrow['provision'] * $amt / 100;
		else {
			$amt1 = min($amt, max(0, $myrow['break_pt']-$subtotal));
			$amt2 = $amt - $amt1;

			$prov = $amt1*$myrow['provision']/100 + $amt2*$myrow['provision2']/100;
		}
		
		$subtotal += $amt;
		$subprov += $prov;
	}
	if ($salesman != 0)
	{
		$rep->TextCol(0, 1,	$k);
		$rep->TextCol(1, 2,	$salespersonname);
		$rep->AmountCol(2, 3, $subtotal, $dec);
		$rep->NewLine();
		$total += $subtotal;
		$provtotal += $subprov;
	}
	$rep->NewLine(2);
	$rep->fontSize += 2;
	$rep->TextCol(1, 2, _('Grand Total'));
	$rep->fontSize -= 2;
	$rep->AmountCol(2, 3, $total, $dec);
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
	$rep->End();
}
}

