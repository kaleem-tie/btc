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
$page_security = 'SA_INV_ORDER_VARIATION_REP';

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

//---------------------------------------------------------------------------------------

print_invoice_orders_variation();

function get_transactions($from, $to, $customer_id, $folk=0)
{
	$from = date2sql($from);
	$to = date2sql($to);
	

 $sql = "SELECT DISTINCT dt.trans_no,dt.tran_date,dm.name,so.order_no,
 so.reference as so_ref,dt.reference as inv_ref
 FROM ".TB_PREF."debtor_trans dt,
 ".TB_PREF."debtor_trans_details dtd,
 ".TB_PREF."debtors_master dm,
 ".TB_PREF."sales_orders so,
 ".TB_PREF."sales_order_details sod
 WHERE dt.type=dtd.debtor_trans_type 
 AND dt.trans_no=dtd.debtor_trans_no 
 AND sod.order_no=so.order_no 
 AND sod.trans_type=so.trans_type 
 AND sod.stk_code = dtd.stock_id
 AND dt.type=10 
 AND dtd.debtor_trans_type=10 
 AND dt.tran_date BETWEEN ".db_escape($from)." AND ".db_escape($to)." 
 AND dt.debtor_no=dm.debtor_no 
 AND dt.order_=so.order_no AND dt.debtor_no=so.debtor_no AND so.trans_type=30
 AND (dtd.unit_price!=sod.unit_price OR dtd.discount_percent!=sod.discount_percent)
 AND dt.ov_amount!=0";
 

 if ($customer_id != ALL_TEXT)
	$sql .= " AND dt.debtor_no = ".db_escape($customer_id);	
		
 if ($folk != 0)
	$sql .= " AND dt.sales_person_id = ".db_escape($folk);

	//$sql.=" GROUP BY dt.trans_no ORDER BY dt.trans_no";
	
	

    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_invoice_orders_variation()
{
    	global $path_to_root, $systypes_array;

    	$from        = $_POST['PARAM_0'];
    	$to          = $_POST['PARAM_1'];
    	$fromcust    = $_POST['PARAM_2'];
		$folk        = $_POST['PARAM_3'];  
    	$orientation = $_POST['PARAM_4'];
	    $destination = $_POST['PARAM_5'];
		
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

	$cols = array(0,40,100,350,450,550);
	

	$headers = array(_('S.No.'), _('Invoice Date'), _('Customer Name'),
	_('Order No.(Ref)'), _('Inv No.(Ref)'));

	
	$aligns = array('left',	'left',	'left',	'left','left');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Dimension')." 1", 'from' => get_dimension_string($dimension),'to' => ''),
						4 => array('text' => _('Sales Folk'), 'from' => $salesfolk,	'to' => ''));
						

    $rep = new FrontReport(_('Invoice to Order Price Variation Report'), 
	"InvoicetoOrderPriceVariationReport", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($from,$to, $fromcust, $folk);
  
	
	$k=1;
	while ($myrow = db_fetch($result))
	{
		
		
		$rep->TextCol(0, 1, $k);
		$rep->TextCol(1, 2, sql2date($myrow['tran_date']));
		$rep->TextCol(2, 3, $myrow['name']);
		$rep->TextCol(3, 4, $myrow['order_no']."(".$myrow['so_ref'].")");
		$rep->TextCol(4, 5, $myrow['trans_no']."(".$myrow['inv_ref'].")");
		
		
   		$rep->NewLine();
		$k++;
	}
	
	$rep->Line($rep->row  - 4);
	$rep->NewLine(2);
		
	$rep->NewLine();
    $rep->End();
}

