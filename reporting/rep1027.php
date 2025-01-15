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
$page_security = 'SA_SINLISTINGREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt, Chaitanya for the recursive version 2009-02-05.
// date_:	2005-05-19
// Title:	Annual expense breakdown
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/admin/db/tags_db.inc");

//----------------------------------------------------------------------------------------------------

print_sales_summary_report();



function get_sales_summary_result($dimension,$customer,$folk,$from_date,$to_date)
{
	
	$sql .=	"select SUM(IF(t.type = ".ST_SALESINVOICE.", 1, -1) *
			(t.ov_amount+t.ov_gst+t.ov_freight+t.ov_roundoff)*t.rate) AS sale_value
		FROM ".TB_PREF."debtor_trans t where  t.type in (10,11) and tran_date between '$from_date' and '$to_date' ";
  
   if($dimension)
	  $sql.=" and dimension_id=$dimension";   
   
   if($customer!='')
	   $sql.=" and debtor_no=$customer"; 
   
   if($folk)
	   $sql.=" and sales_person_id=$folk";   
   // display_error($sql);
    $result = db_query($sql,"No transactions were returned");
	
    $sales_summary_result = db_fetch($result);
	
	return $sales_summary_result;
}

function print_sales_summary_report()
{
	global $path_to_root, $SysPrefs, $tmonths;

		$year = $_POST['PARAM_0'];
		$dimension = $_POST['PARAM_1'];
		$fromcust = $_POST['PARAM_2'];
		$folk = $_POST['PARAM_3'];
	   	$orientation = $_POST['PARAM_4'];
		$destination = $_POST['PARAM_5'];
  
  

  
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
	
	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
    	
	if ($folk == ALL_NUMERIC)
        $folk = 0;
    if ($folk == 0)
        $salesfolk = _('All Sales Man');
     else
        $salesfolk = get_salesman_name($folk);

	$cols = array(1, 50,300,515);
	
	// from now
	 $sql = "SELECT begin, end, YEAR(end) AS yr, MONTH(end) AS mo FROM ".TB_PREF."fiscal_year WHERE id=".db_escape($year);
	$result = db_query($sql, "could not get fiscal year");
	$row = db_fetch($result);
	
	$year = sql2date($row['begin'])." - ".sql2date($row['end']);
	$year = sql2date($row['begin'])." - ".sql2date($row['end']);
	$yr = $row['yr'];
	$mo = $row['mo'];
	$da = 1;
	if ($SysPrefs->date_system == 1)
		list($yr, $mo, $da) = jalali_to_gregorian($yr, $mo, $da);
	elseif ($SysPrefs->date_system == 2)
		list($yr, $mo, $da) = islamic_to_gregorian($yr, $mo, $da);

	$headers = array(_('S.No'), _('Month'),_('Sale Value'));

	$aligns = array('left',	'left',	'right');


    	$params =   array(0 => $comments,
                    	1 => array('text' => _("Year"),
                    		'from' => $year, 'to' => ''),
                    	2 => array('text' => _('Dimension'),
                    		'from' => get_dimension_string($dimension), 'to' => ''),
                    	3 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						4 => array('text' => _('Sales Folk'), 'from' => $salesfolk,	'to' => ''));
 

	$rep = new FrontReport(_('Monthly Sales Sumamry Report'), "monthlysalessummary", user_pagesize(), 9,
										$orientation);
   if ($orientation == 'L')
    	recalculate_cols($cols);
 	
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->NewPage();
	
	$total=0;
	for($i=1;$i<=12;$i++)
	{
		 $rep->NewLine();
		 $rep->TextCol(0, 1, $i);
		 
		 $rep->TextCol(1, 2, $tmonths[date('n',mktime(0,0,0,$mo-(12-$i),$da,$yr))]);
		 $sales_result= get_sales_summary_result($dimension,$fromcust,$folk,$yr.'-'.($mo-(12-$i)).'-'.$da,$yr.'-'.($mo-(12-$i)).'-31');
		 $rep->AmountCol(2, 3, $sales_result['sale_value'],3);
		 $total+=$sales_result['sale_value'];
	}
	
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
	$rep->NewLine();
	$rep->TextCol(1, 2, 'Total');
	$rep->AmountCol(2, 3, $total,3);

  $rep->End();
}

