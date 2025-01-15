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
$page_security = 'SA_SALESDELIVERY';

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
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");

//----------------------------------------------------------------------------------------------------

print_customer_orders();

function get_transactions($customer_id, $from, $to, $dimension=0, $folk=0)
{
	$from = date2sql($from);
	$to = date2sql($to);

 	$sql = "SELECT so.reference as so_ref,so.ord_date,dm.name,dt.reference as do_ref,dt.tran_date,dtd.stock_id,dtd.quantity-dtd.qty_done as outstanding
FROM ".TB_PREF."debtor_trans dt,
".TB_PREF."debtor_trans_details dtd,
".TB_PREF."debtors_master dm,
".TB_PREF."sales_orders so
WHERE dt.type=dtd.debtor_trans_type and dt.trans_no=dtd.debtor_trans_no and dt.type=13 and dtd.debtor_trans_type=13 and dt.tran_date BETWEEN ".db_escape($from)." and ".db_escape($to)." and dtd.quantity>dtd.qty_done and dt.debtor_no=dm.debtor_no and dt.order_=so.order_no and dt.debtor_no=so.debtor_no and so.trans_type=30";

if ($customer_id != ALL_TEXT)
			$sql .= " AND so.debtor_no = ".db_escape($customer_id);	
		
 if ($dimension != 0){
  		$sql .= " AND so.dimension_id = ".($dimension<0 ? 0 : db_escape($dimension));
		}		
if ($folk != 0)
			$sql .= " AND so.sales_person_id = ".db_escape($folk);

    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_customer_orders()
{
    	global $path_to_root, $systypes_array;

    	$from = $_POST['PARAM_0'];
    	$to = $_POST['PARAM_1'];
    	$fromcust = $_POST['PARAM_2'];
		$dimension = $_POST['PARAM_3'];
		$folk = $_POST['PARAM_4'];  // added by Faisal to filter by sales person
    	$orientation = $_POST['PARAM_5'];
	    $destination = $_POST['PARAM_6'];
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
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
	
    	$dec = user_price_dec();

	$cols = array(0, 50, 100, 300,350,400,450,500);
	

	$headers = array(_('SO REF'), _('SO DATE'), _('Customer Name'),
	_('DO REF'), _('DO DATE'),_('STOCK ID'), _('OUTSTANDING'));
	

	
	$aligns = array('center',	'center',	'left',	'center','center','center','right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Dimension')." 1", 'from' => get_dimension_string($dimension),'to' => ''),
						4 => array('text' => _('Sales Folk'), 'from' => $salesfolk,	'to' => ''));
						

    $rep = new FrontReport(_('Outstanding DO Listing'), "OutstandingDOListing", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($fromcust,$from,$to, $dimension, $folk);
  
	$total_value =0;
	while ($myrow = db_fetch($result))
	{
		
		
		$rep->TextCol(0, 1, $myrow['so_ref']);
		$rep->TextCol(1, 2, sql2date($myrow['ord_date']));
		$rep->TextCol(2, 3, $myrow['name']);
		$rep->TextCol(3, 4, $myrow['do_ref']);
		$rep->TextCol(4, 5, sql2date($myrow['tran_date']));
		$rep->TextCol(5, 6, $myrow['stock_id']);
		$rep->TextCol(6, 7, $myrow['outstanding']);
		
		
   		$rep->NewLine();
	}
	
	$rep->Line($rep->row  - 4);
	$rep->NewLine(2);
		
	$rep->NewLine();
    $rep->End();
}

