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
$page_security = 'SA_CATEGORY_MONTHLY_REP'; //'SA_ITEMSVALREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Stock Check Sheet
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/includes/db/manufacturing_db.inc");

//----------------------------------------------------------------------------------------------------

print_stock_check();

/**
 * Bar codes checker - Checks if a barcode can be valid and returns type of barcode
 * 
 * @link		http://www.phpclasses.org/package/8560-PHP-Detect-type-and-check-EAN-and-UPC-barcodes.html
 * @type tests	EAN, EAN-8, EAN-13, GTIN-8, GTIN-12, GTIN-14, UPC, UPC-12 coupon code, JAN 
 * @author		Ferry Bouwhuis
 * @version		1.0.1
 * @LastChange	2014-04-13
 */


function get_inv_supplier_name1($supplier_id)
{
	$sql = "SELECT supp_name from ".TB_PREF."suppliers where supplier_id=".db_escape($supplier_id);

	$result=db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	return $row[0];
}

// Sales & Sales cost
function get_sales_data($from,$to,$category)
{
	$from_date = date2sql($from);
	$to_date = date2sql($to);
	
	$sql="SELECT SUM(dtd.quantity*dtd.unit_price*(1 - dtd.discount_percent/100) *trans.rate) AS sales_amt,SUM(dtd.quantity*dtd.standard_cost) as sales_cost FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtor_trans_details dtd,".TB_PREF."stock_master stock
	WHERE trans.type=dtd.debtor_trans_type AND trans.trans_no=dtd.debtor_trans_no 
	AND stock.stock_id=dtd.stock_id  AND trans.type=10 
	AND trans.tran_date>=".db_escape($from_date)." AND trans.tran_date<=".db_escape($to_date)."
	AND stock.category_id=".db_escape($category)."";
	
	/*if ($supplier != 'all')
		$sql .= " AND stock.supplier_id = ".db_escape($supplier);*/
	
	$sql .= " GROUP BY stock.category_id";	
	
	//display_error($sql);

    $tran_result= db_query($sql,"No transactions were returned");
	
	return db_fetch($tran_result);
	
}


//Sales foreign
function get_sales_foreign_data($from,$to,$category)
{
	$from_date = date2sql($from);
	$to_date = date2sql($to);
	
	$home_curr = get_company_currency();
	
	$sql="SELECT SUM(dtd.quantity*dtd.standard_cost) as sales_cost FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtor_trans_details dtd,".TB_PREF."stock_master stock,
	".TB_PREF."debtors_master debtor
	WHERE trans.type=dtd.debtor_trans_type AND trans.trans_no=dtd.debtor_trans_no 
	AND stock.stock_id=dtd.stock_id  AND debtor.debtor_no=trans.debtor_no AND trans.type=10 
	AND trans.tran_date>=".db_escape($from_date)." AND trans.tran_date<=".db_escape($to_date)."
	AND stock.category_id=".db_escape($category)."";
	
	$sql .= " AND debtor.curr_code != ".db_escape($home_curr)."";
	
	/*if ($supplier != 'all')
		$sql .= " AND stock.supplier_id = ".db_escape($supplier);*/

	$sql .= " GROUP BY stock.category_id";	
	
    $tran_result= db_query($sql,"No transactions were returned");
	
	return db_fetch($tran_result);
	
}


//Stock
function get_category_stock_data($from,$to,$category)
{
	$from_date = date2sql($from);
	$to_date = date2sql($to);
	
	
	
	$sql="SELECT SUM(move.qty) AS stock FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtor_trans_details dtd,".TB_PREF."stock_master stock,
	".TB_PREF."stock_moves move
	WHERE trans.type=dtd.debtor_trans_type AND trans.trans_no=dtd.debtor_trans_no 
	AND stock.stock_id=dtd.stock_id  AND move.stock_id=dtd.stock_id AND trans.type=10 
	AND trans.tran_date>=".db_escape($from_date)." AND trans.tran_date<=".db_escape($to_date)."
	AND stock.category_id=".db_escape($category)."";
	/*if ($supplier != 'all')
		$sql .= " AND stock.supplier_id = ".db_escape($supplier);*/

	$sql .= " GROUP BY stock.category_id";	

    $tran_result= db_query($sql,"No transactions were returned");
	
	return db_fetch($tran_result);
	
}



//----------------------------------------------------------------------------------------------------

function print_stock_check()
{
    global $path_to_root, $SysPrefs;
	
	
    $from        = $_POST['PARAM_0'];
    $to          = $_POST['PARAM_1'];
	//$supplier    = $_POST['PARAM_2'];
   	$category    = $_POST['PARAM_2'];
   	$comments    = $_POST['PARAM_3'];
	$orientation = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
	
	
	if ($category == ALL_NUMERIC)
		$category = 0;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);
	
	
		
	/*if ($supplier == ALL_TEXT)
		$supplier = 'all';
	if ($supplier== 'all')
		$sup = _('All');
	else
		$sup = get_inv_supplier_name1($supplier);*/
	
	
	$cols = array(0, 150, 220,300,410,520);
	$headers = array(_('Category Name'), _('Sales'), _('Sales Cost'), _('Sales Foreign Cost Omr'), _('Stock'));
	$aligns = array('left',	'right','right','right','right');

    $params =   array(
		0 => $comments,
		1 => array('text' => _('Period'), 'from' => $from, 	'to' => $to),
		//2 => array('text' => _('Supplier'), 'from' => $sup, 'to' => ''),
    	2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
    	3 => array('text' => _('Only Shortage'), 'from' => $short, 'to' => ''),
		4 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => ''));
	

   	$rep = new FrontReport(_('Category Wise Monthly Report'), "CategoryWiseMonthlyReport", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
	
	$rep->SetHeaderType('Header40');
	
    $rep->NewPage();
	
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	
	$rep->Font('bold');
	$rep->TextCol(0, 1, "Category Name");
	$rep->TextCol(1, 2, "Sales");
	$rep->TextCol(2, 3, "Sales Cost");
	$rep->TextCol(3, 4, "Sales Foreign Cost Omr");
	$rep->TextCol(4, 5, "Stock");
    $rep->Font();
	$rep->NewLine();
	$rep->Line($rep->row - 2);
	$rep->NewLine(1);
	
	

	$dec = user_price_dec();
	
	
	$sql = "SELECT category_id, description as category_description FROM ".TB_PREF."stock_category ";
	
	$sql .= "WHERE 1=1";
	if ($category != 0)
		$sql .= " AND category_id = ".db_escape($category);
	$sql .= " ORDER BY category_id";
	
	$result = db_query($sql, "The categories could not be retrieved");
	
	$total_sales = $total_sales_cost = $total_foreign_sales_cost = $total_stock = 0;
	
	while ($trans=db_fetch($result))
	{
		$sales_data = get_sales_data($from,$to,$trans['category_id']);
		
		$sales_foreign_data = get_sales_foreign_data($from,$to,$trans['category_id']);
		
		$stock_data = get_category_stock_data($from,$to,$trans['category_id']);
		
		$rep->TextCol(0, 1, $trans['category_description']);
		$rep->AmountCol(1, 2, $sales_data['sales_amt'], $dec);
		$rep->AmountCol(2, 3, $sales_data['sales_cost'], $dec);
		$rep->AmountCol(3, 4, $sales_foreign_data['sales_cost'], $dec);
		$rep->AmountCol(4, 5, $stock_data['stock'], $dec);
		
		$rep->NewLine();
		
		$total_sales+=$sales_data['sales_amt'];
		$total_sales_cost+= $sales_data['sales_cost'];
		$total_foreign_sales_cost+=$sales_foreign_data['sales_cost'];
		$total_stock+=$stock_data['stock'];
	}
	$rep->Line($rep->row - 4);
	$rep->NewLine(2);
	
	$rep->TextCol(0, 1, _('Total'));
	$rep->AmountCol(1, 2, $total_sales, $dec);
	$rep->AmountCol(2, 3, $total_sales_cost, $dec);
	$rep->AmountCol(3, 4, $total_foreign_sales_cost, $dec);
	$rep->AmountCol(4, 5, $total_stock, $dec);
	
    $rep->End();
}

