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
// $ Revision:	2.4 $
// Creator:		Joe Hunt, boxygen
// date_:		2014-05-13
// Title:		Inventory Valuation
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

//----------------------------------------------------------------------------------------------------

print_salesmanwise_monthly_sales_summary_report();




function getAllSalesPersons($sales_person)
{
		
	$sql = "SELECT salesman_code,salesman_name from ".TB_PREF."salesman WHERE inactive=0";
	
	if ($sales_person != 0)
	$sql .= " AND 	salesman_code=".db_escape($sales_person);
	
	return db_query($sql,"No transactions were returned");
}



function get_salesman_monthly_sales($sales_person,$start_date,$end_date)
{
	
   $sql .=	"SELECT SUM(IF(trans.type = ".ST_SALESINVOICE.", 1, -1) *
			(trans.ov_amount+trans.ov_freight+trans.ov_roundoff)*trans.rate) AS sale_value
		FROM ".TB_PREF."debtor_trans trans,
			 ".TB_PREF."debtors_master cust,
			 ".TB_PREF."sales_orders sorder,
			 ".TB_PREF."salesman salesman 
			 WHERE sorder.order_no=trans.order_
		    AND sorder.trans_type = ".ST_SALESORDER."
		    AND trans.sales_person_id=salesman.salesman_code
		    AND trans.debtor_no=cust.debtor_no
		    AND (trans.type=".ST_SALESINVOICE." OR trans.type=".ST_CUSTCREDIT.")
		   AND trans.tran_date between '$start_date' AND '$end_date' AND trans.ov_amount!=0";
   
    if ($sales_person != 0)
	$sql .= " AND trans.sales_person_id=".db_escape($sales_person);

    $sql.=" GROUP BY trans.sales_person_id";	 

	$result= db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	$sales_price= $row[0]==""?0:$row[0];
	return $sales_price;
		
		
		
}


//----------------------------------------------------------------------------------------------------

function print_salesmanwise_monthly_sales_summary_report()
{
    global $path_to_root, $SysPrefs;

	$year         = $_POST['PARAM_0'];
	$sales_person = $_POST['PARAM_1'];
	$comments     = $_POST['PARAM_2'];
    $orientation  = $_POST['PARAM_3'];
	$destination  = $_POST['PARAM_4'];
	
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	
    $dec = user_price_dec();

	//$orientation = ($orientation ? 'L' : 'P');
	
     $orientation = 'L';
	
	
	if ($sales_person == ALL_NUMERIC)
        $sales_person = 0;
    if ($sales_person == 0)
        $salesfolk = _('All Sales Man');
     else
        $salesfolk = get_salesman_name($sales_person);

	
	
	
	//------------0--1---2----3----4----5----6----7----8----10---11---12---13---14---15---16-

	// from now
	$sql = "SELECT begin, end, YEAR(end) AS yr, MONTH(end) AS mo FROM ".TB_PREF."fiscal_year WHERE id=".db_escape($year);
	$result = db_query($sql, "could not get fiscal year");
	$row = db_fetch($result);
	
	$year = sql2date($row['begin'])." - ".sql2date($row['end']);
	
	$yr = $row['yr'];
	$mo = $row['mo'];
	$da = 1;
	
	
	
	
	if ($SysPrefs->date_system == 1)
		list($yr, $mo, $da) = jalali_to_gregorian($yr, $mo, $da);
	elseif ($SysPrefs->date_system == 2)
		list($yr, $mo, $da) = islamic_to_gregorian($yr, $mo, $da);
	$per12 = strftime('%b',mktime(0,0,0,$mo,$da,$yr));
	$per11 = strftime('%b',mktime(0,0,0,$mo-1,$da,$yr));
	$per10 = strftime('%b',mktime(0,0,0,$mo-2,$da,$yr));
	$per09 = strftime('%b',mktime(0,0,0,$mo-3,$da,$yr));
	$per08 = strftime('%b',mktime(0,0,0,$mo-4,$da,$yr));
	$per07 = strftime('%b',mktime(0,0,0,$mo-5,$da,$yr));
	$per06 = strftime('%b',mktime(0,0,0,$mo-6,$da,$yr));
	$per05 = strftime('%b',mktime(0,0,0,$mo-7,$da,$yr));
	$per04 = strftime('%b',mktime(0,0,0,$mo-8,$da,$yr));
	$per03 = strftime('%b',mktime(0,0,0,$mo-9,$da,$yr));
	$per02 = strftime('%b',mktime(0,0,0,$mo-10,$da,$yr));
	$per01 = strftime('%b',mktime(0,0,0,$mo-11,$da,$yr));
	
	
	$cols = array(0, 60, 90,130,170,210,250,290,330,370,410,450,490,530,570,620);
	
	
	$headers = array(_('SalesMan'),  $per01, $per02, $per03, $per04,
		$per05, $per06, $per07, $per08, $per09, $per10, $per11, $per12, _('Total'));

	$aligns = array('left',	'right', 'right', 'right',	'right', 'right', 'right',
		'right', 'right', 'right',	'right', 'right', 'right', 'right');
	
	
    $params =   array( 	0 => "",
    					1 => array('text' => _('Selected Fiscal Year'), 'from' => $year, 		'to' => ''),
						2 => array('text' => _('Sales Person'), 'from' => $salesfolk, 'to' => ''));
						

    $rep = new FrontReport(_('SalesManwise Monthly Sales Summary'), "SalesManwiseMonthlySalesSummary", user_pagesize(), 7, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->NewPage();
	
	
	
	$jan_total = $feb_total = $mar_total = $apr_total = $may_total = $jun_total = 0;
	$jul_total = $aug_total = $sep_total = $oct_total = $nov_total = $dec_total = 0;
	$grand_annual_total = 0;
	

    $sales_person_details=getAllSalesPersons($sales_person);
	
	
	while($sales_person=db_fetch($sales_person_details))
	{
		
	
	
	$date_13 = date('Y-m-d',mktime(0,0,0,$mo+1,1,$yr));
	$date_12 = date('Y-m-d',mktime(0,0,0,$mo,1,$yr));
	$date_11 = date('Y-m-d',mktime(0,0,0,$mo-1,1,$yr));
	$date_10 = date('Y-m-d',mktime(0,0,0,$mo-2,1,$yr));
	$date_9 = date('Y-m-d',mktime(0,0,0,$mo-3,1,$yr));
	$date_8 = date('Y-m-d',mktime(0,0,0,$mo-4,1,$yr));
	$date_7 = date('Y-m-d',mktime(0,0,0,$mo-5,1,$yr));
	$date_6 = date('Y-m-d',mktime(0,0,0,$mo-6,1,$yr));
	$date_5 = date('Y-m-d',mktime(0,0,0,$mo-7,1,$yr));
	$date_4 = date('Y-m-d',mktime(0,0,0,$mo-8,1,$yr));
	$date_3 = date('Y-m-d',mktime(0,0,0,$mo-9,1,$yr));
	$date_2 = date('Y-m-d',mktime(0,0,0,$mo-10,1,$yr));
	$date_1 = date('Y-m-d',mktime(0,0,0,$mo-11,1,$yr));
	
	
	$sales_person_id=$sales_person['salesman_code'];
    $rep->TextCol(0, 1, $sales_person['salesman_name']);
	
	   
	   $jan_sales = get_salesman_monthly_sales($sales_person_id,$date_1,$date_2);
	   $feb_sales = get_salesman_monthly_sales($sales_person_id,$date_2,$date_3);
	   $mar_sales = get_salesman_monthly_sales($sales_person_id,$date_3,$date_4);
	   $apr_sales = get_salesman_monthly_sales($sales_person_id,$date_4,$date_5);
	   $may_sales = get_salesman_monthly_sales($sales_person_id,$date_5,$date_6);
	   $jun_sales = get_salesman_monthly_sales($sales_person_id,$date_6,$date_7);
	   $jul_sales = get_salesman_monthly_sales($sales_person_id,$date_7,$date_8);
	   $aug_sales = get_salesman_monthly_sales($sales_person_id,$date_8,$date_9);
	   $sep_sales = get_salesman_monthly_sales($sales_person_id,$date_9,$date_10);
	   $oct_sales = get_salesman_monthly_sales($sales_person_id,$date_10,$date_11);
	   $nov_sales = get_salesman_monthly_sales($sales_person_id,$date_11,$date_12);
	   $dec_sales = get_salesman_monthly_sales($sales_person_id,$date_12,$date_13);
	   $annual_sales = get_salesman_monthly_sales($sales_person_id,$date_1,$date_13);
	   
		
		
		$rep->AmountCol(1, 2,  $jan_sales, $dec);
		$rep->AmountCol(2, 3,  $feb_sales, $dec);
		$rep->AmountCol(3, 4,  $mar_sales, $dec);
		$rep->AmountCol(4, 5,  $apr_sales, $dec);
		$rep->AmountCol(5, 6,  $may_sales, $dec);
		$rep->AmountCol(6, 7,  $jun_sales, $dec);
		$rep->AmountCol(7, 8,  $jul_sales, $dec);
		$rep->AmountCol(8, 9,  $aug_sales, $dec);
		$rep->AmountCol(9, 10, $sep_sales, $dec);
		$rep->AmountCol(10, 11, $oct_sales, $dec);
		$rep->AmountCol(11, 12, $nov_sales, $dec);
		$rep->AmountCol(12, 13, $dec_sales, $dec);
	    $rep->AmountCol(13, 14, $annual_sales, $dec);
		
	  $jan_total+= $jan_sales;
	  $feb_total+= $feb_sales; 
	  $mar_total+= $mar_sales; 
	  $apr_total+= $apr_sales; 
	  $may_total+= $may_sales; 
	  $jun_total+= $jun_sales; 
	  $jul_total+= $jul_sales; 
	  $aug_total+= $aug_sales; 
	  $sep_total+= $sep_sales; 
	  $oct_total+= $oct_sales; 
	  $nov_total+= $nov_sales; 
	  $dec_total+= $dec_sales; 
	  $grand_annual_total+= $annual_sales; 
	
    $rep->NewLine();

	}	
	
	
	
 	$rep->Line($rep->row  - 4);
	$rep->NewLine(2);
	
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(0, 1, _('Grand Total ').$salesper);
    $rep->AmountCol(1, 2,  $jan_total, $dec);
	$rep->AmountCol(2, 3,  $feb_total, $dec);
	$rep->AmountCol(3, 4,  $mar_total, $dec);
	$rep->AmountCol(4, 5,  $apr_total, $dec);
	$rep->AmountCol(5, 6,  $may_total, $dec);
	$rep->AmountCol(6, 7,  $jun_total, $dec);
	$rep->AmountCol(7, 8,  $jul_total, $dec);
	$rep->AmountCol(8, 9,  $aug_total, $dec);
	$rep->AmountCol(9, 10, $sep_total, $dec);
	$rep->AmountCol(10, 11, $oct_total, $dec);
	$rep->AmountCol(11, 12, $nov_total, $dec);
	$rep->AmountCol(12, 13, $dec_total, $dec);
	$rep->AmountCol(13, 14, $grand_annual_total, $dec);
	$rep->SetFont('', '', 0);
		
    $rep->End();
}

