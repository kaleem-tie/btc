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
$page_security = 'SA_INVENTORY_ADJUSTMENT_REPORT'; //'SA_ITEMSVALREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Jujuk
// date_:	2011-05-24
// Title:	Stock Movements
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui/ui_input.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");

//----------------------------------------------------------------------------------------------------

inventory_movements();


function get_inv_supplier_name1($supplier_id)
{
	$sql = "SELECT supp_name from ".TB_PREF."suppliers where supplier_id=".db_escape($supplier_id);

	$result=db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	return $row[0];
}



function getTransUser($trans_no)
{
	$sql = "SELECT users.user_id from ".TB_PREF."users users,".TB_PREF."audit_trail at where at.user=users.id and type=17 and trans_no=".db_escape($trans_no);

	$result=db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	return $row[0];
}

function fetch_items($category, $subcategory, $from_date,$to_date,$location)
{
	$from_date=date2sql($from_date);
	$to_date=date2sql($to_date);
	
		$sql = "SELECT mov.*, stock.description,
				stock.units
				
			FROM ".TB_PREF."stock_master stock,".TB_PREF."stock_moves mov WHERE mov.stock_id = stock.stock_id and mov.type=17 and  mb_flag <> 'D' AND mb_flag <>'F' and mov.tran_date>=".db_escape($from_date)." and mov.tran_date<=".db_escape($to_date)."";
		
		if ($category != 0)
		$sql .= " AND stock.category_id = ".db_escape($category);		
				
		if ($subcategory != 'all')
		$sql .= " AND stock.item_sub_category = ".db_escape($subcategory);
	
	    /*if ($supplier != 'all')
		$sql .= " AND stock.supplier_id = ".db_escape($supplier);*/
	
	   if ($location != 'all')
		$sql .= " AND IF(mov.stock_id IS NULL, '1=1',mov.loc_code = ".db_escape($location).")";
	
		$sql .= " ORDER BY mov.tran_date";
		
    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function inventory_movements()
{
    global $path_to_root;

  //  $supplier    = $_POST['PARAM_0'];
    $from_date   = $_POST['PARAM_0'];
    $to_date     = $_POST['PARAM_1'];
    $category    = $_POST['PARAM_2'];
	$subcategory = $_POST['PARAM_3'];
	$location    = $_POST['PARAM_4'];
    $comments    = $_POST['PARAM_5'];
	$orientation = $_POST['PARAM_6'];
	$destination = $_POST['PARAM_7'];
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	
	$dec = user_price_dec();
	
	
	if ($category == ALL_NUMERIC)
		$category = 0;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);
	
	if ($subcategory == ALL_TEXT)
		$subcategory = 'all';
	if ($subcategory == 'all')
		$subcat = _('All');
	else
		$subcat = get_stock_subcategory_name($subcategory);
	


	if ($location == ALL_TEXT)
		$location = 'all';
	if ($location == 'all')
		$loc = _('All');
	else
		$loc = get_location_name($location);
	
	/*if ($supplier == ALL_TEXT)
		$supplier = 'all';
	if ($supplier== 'all')
		$sup = _('All');
	else
		$sup = get_inv_supplier_name1($supplier);*/
	


	$cols = array(0, 60, 100,180, 320,340,380,420,460,520,560);

	$headers = array(_('Date'),_('Reference'),_('Code'), _('Description'),	_('UOM'),	
	_('Quantity'), _('Price'), _('Total'), _('Adjusted By'), _('Loc Code'));

	$aligns = array('left',	'left',	'left','left',	'left', 'right', 'right', 'right','center','right');

    $params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $from_date, 'to' => $to_date),
						//2 => array('text' => _('Supplier'), 'from' => $sup, 'to' => ''),
    				    2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
						3 => array('text' => _('Sub Category'), 'from' => $subcat, 'to' => ''),
						4 => array('text' => _('Location'), 'from' => $loc, 'to' => ''));

    $rep = new FrontReport(_('Inventory Adjustment Report'), "InventoryAdjustmentReport", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

$result = fetch_items($category, $subcategory, $from_date,$to_date,$location);

	$catgor = '';
	while ($myrow=db_fetch($result))
	{
		
		$rep->NewLine();
		$rep->TextCol(0, 1,	sql2date($myrow['tran_date']));
		$rep->TextCol(1, 2,	$myrow['reference']);
		$rep->TextCol(2, 3,	$myrow['stock_id']);
		$rep->TextCol(3, 4, $myrow['description']);
		$rep->TextCol(4, 5, $myrow['units']);
		$stock_qty_dec = get_qty_dec($myrow['stock_id']);
		$rep->AmountCol(5, 6, $myrow['qty'], $stock_qty_dec);
		$rep->AmountCol(6, 7,$myrow['standard_cost'],$dec);
		$rep->AmountCol(7, 8,$myrow['qty']*$myrow['standard_cost'], $dec);
		$rep->TextCol(8, 9, getTransUser($myrow['trans_no']));
		$rep->TextCol(9, 10, $myrow['loc_code']);
		$rep->NewLine(0, 1);
	}
	$rep->Line($rep->row  - 4);

	$rep->NewLine();
    $rep->End();
}

