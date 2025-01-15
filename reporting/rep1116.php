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
$page_security = 'SA_SALESBULKREP';

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
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

//----------------------------------------------------------------------------------------------------

function is_sales_quotation_reference_existed($reference)
{
	$sql = "SELECT count(*) FROM ".TB_PREF."sales_orders 
		WHERE trans_type=32
		AND reference=".db_escape($reference)."";
	$result= db_query($sql,"The related customer type could not be retreived");
	$row= db_fetch_row($result);
	return $row[0];
}

function get_sales_quotation_order_no_by_reference($reference){
	$sql = "SELECT order_no FROM ".TB_PREF."sales_orders 
	WHERE trans_type=32
	AND reference=".db_escape($reference)."";
	//display_error($sql);
	$result = db_query($sql, "could not get order no");
	$myrow = db_fetch_row($result);
	return $myrow[0]; 
}

//----------------------------------------------------------

print_customer_orders();

function print_customer_orders()
{
    global $path_to_root, $systypes_array;

    $reference   = $_POST['PARAM_0'];
    
	
	$destination = 1;
	
	
	 $quote_ref = is_sales_quotation_reference_existed($reference);
	
	if($quote_ref==0){
	display_error("Sales Quoation : ".$reference." is not existed!");
	return false;	
	}
	else{
    $quote_no = get_sales_quotation_order_no_by_reference($reference);
	}
	
	
	
	
	//$order_no = 0;
	
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report_new.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	
	$dec = user_price_dec();
	
	$myrow = get_sales_order_header($quote_no, ST_SALESQUOTE);
	
	$salesperson = get_salesman_name($myrow['sales_person_id']);
	
	
	$cols = array(0, 150,250,350,450,550);

	$headers = array(_('Item Code'), _('Item Description'), _('Quantity'), 
	_('Unit Price'),  _('Total Price'));
	
	$aligns = array('left','left','right','right','right');

    $params =   array( 0 => $comments,
    				    1 => array('text' => _('Quotation No.'), 
						'from' => $myrow['reference'],   	'to' => ''),
						2 => array('text' => _('Sales Person'), 'from' => $salesperson, 'to' => ''),
						3 => array('text' => _('Bank Sales Staff'), 'from' => '-', 'to' => ''),
						);
						

    $rep = new FrontReport(_(''), 
	"SalesQuotationTemplate", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


    $result = get_sales_order_details($quote_no, ST_SALESQUOTE);
	while ($myrow2 = db_fetch($result))
	{
	    $Net = round2(($myrow2["quantity"] * $myrow2["unit_price"])-($myrow2["quantity"] * $myrow2["unit_price"] * ($myrow2["discount_percent"]/100)), user_price_dec());
		
		//$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
		
		
		
		$DisplayPrice = round2(($myrow2["unit_price"])-($myrow2["unit_price"] * ($myrow2["discount_percent"]/100)), user_price_dec());
		
		
		$DisplayQty = number_format2($myrow2["quantity"],get_qty_dec($myrow2['stk_code']));
		$DisplayNet = number_format2($Net,$dec);
		if ($myrow2["discount_percent"]==0)
			$DisplayDiscount ="";
		else
			$DisplayDiscount = number_format2($myrow2["discount_percent"],user_percent_dec()) . "%";
	    
		$rep->TextCol(0, 1,	$myrow2['stk_code'], -2);
		$rep->TextCol(1, 2, $myrow2['description']);
		$rep->TextCol(2, 3,	$DisplayQty, -2);
		$rep->TextCol(3, 4,	$DisplayPrice, -2);
		$rep->TextCol(4, 5,	$DisplayNet, -2);
		
		$rep->NewLine();
		$k++;
	}
	
    $rep->End();
}

