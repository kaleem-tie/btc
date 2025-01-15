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
//$page_security = 'SA_ITEMSVALREP';

$page_security = 'SA_INVENTORY_VALUATION_REPORT';

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



function get_inv_supplier_name1($supplier_id)
{
	$sql = "SELECT supp_name from ".TB_PREF."suppliers where supplier_id=".db_escape($supplier_id);

	$result=db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	return $row[0];
}





function getTransactions($supplier,$category, $location, $subcategory,$rep_date)
{
	$sql = "SELECT item.category_id,
			category.description AS cat_description,
			item.stock_id, item.units,
			item.description, item.inactive,
			IF(move.stock_id IS NULL, '', move.loc_code) AS loc_code,
			SUM(IF(move.stock_id IS NULL,0,move.qty)) AS QtyOnHand,
			item.material_cost AS UnitCost,
			SUM(move.qty) * item.material_cost AS ItemTotal
		FROM "
			.TB_PREF."stock_master item,"
			.TB_PREF."stock_category category,";
		if($supplier!='all')
		{
			$sql.=TB_PREF."suppliers supp,";
		}	
		
		if($subcategory!='all')
		{
			$sql.=TB_PREF."item_sub_category item_sb,";
		}		
		
		$sql.=TB_PREF."stock_moves move
		WHERE item.category_id=category.category_id
		AND item.stock_id=move.stock_id
		AND (item.mb_flag='B' OR item.mb_flag='M')";
	if ($category != 0)
		$sql .= " AND item.category_id = ".db_escape($category);
	
	if ($subcategory != 'all')
		$sql .= " AND item.item_sub_category=item_sb.id AND item.item_sub_category = ".db_escape($subcategory);
	
	
	 if ($supplier != 'all')
		$sql .= " AND item.supplier_id = supp.supplier_id AND item.supplier_id = ".db_escape($supplier);
	
	
	if ($location != 'all')
		$sql .= " AND IF(move.stock_id IS NULL, '1=1',move.loc_code = ".db_escape($location).")";
		
	if ($rep_date != 0)
		$sql .= " AND move.tran_date <= '$rep_date'";		
 
	$sql .= " GROUP BY item.category_id,
		category.description,
		item.stock_id,
		item.description
		ORDER BY item.category_id,
		item.stock_id";
		
	//display_error($sql); die;	

    return db_query($sql,"No transactions were returned");
}


//----------------------------------------------------------------------------------------------------

function print_stock_check()
{
    global $comp_path, $path_to_root, $pic_height;

	
	$rep_date = $_POST['PARAM_0'];
	$supplier = $_POST['PARAM_1'];
    $category = $_POST['PARAM_2'];
	$subcategory = $_POST['PARAM_3'];
    $location = $_POST['PARAM_4'];
    $comments = $_POST['PARAM_5'];
	$destination = $_POST['PARAM_6'];
	
	//$destination=1;
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	
	
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
	
	
	
	if ($grp == ALL_TEXT)
		$grp = 'all';
	if ($grp == 'all')
		$gr = _('All');
	else
		$gr = get_stock_grp_name($grp);
	
	 

	if ($location == ALL_TEXT)
		$location = 'all';
	if ($location == 'all')
		$loc = _('All');
	else
		$loc = get_location_name($location);
	
	if ($supplier == ALL_TEXT)
		$supplier = 'all';
	if ($supplier== 'all')
		$sup = _('All');
	else
		$sup = get_inv_supplier_name1($supplier);
	
	
	
	
		
	$cols = array(0, 100, 250, 300,350,410,460,520);
	$headers = array(_('Stock ID'), _('Description'), _('Unit'), _('Qty'), 
	_('Selling Price'), _('Cost Rate'), _('Cost Value'));
	
	$aligns = array('left',	'left','left','right','right','right','right');


   $params =   array( 	0 => $comments,
	                    1 => array('text' => _('Date'), 'from' => $rep_date, 'to' => ''),
    				    2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
                        3 => array('text' => _('Supplier'), 'from' => $sup, 'to' => ''),
						5 => array('text' => _('Sub Category'), 'from' => $subcat, 'to' => ''),	
    				    8 => array('text' => _('Location'), 'from' => $loc, 'to' => ''));

	$user_comp = "";

    $rep = new FrontReport(_('Dated Stock Sheet'), "DatedStockSheet", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();
	
	$res = getTransactions($supplier,$category, $location, $subcategory,date2sql($rep_date));
	
	$total_qty = $total_cost_value = 0.0;

	//$res = getTransactions($category, $location,date2sql($rep_date));
	$catt = '';
	while ($trans=db_fetch($res))
	{
		if ($location == 'all')
			$loc_code = "";
		else
			$loc_code = $location;
		
		
      
		
		
		$rep->NewLine();
		$dec = user_price_dec();
		
		$sale_rate=get_kit_price($trans['stock_id'],'OMR',1);
		
		$rep->TextCol(0, 1, $trans['stock_id']);
		$rep->TextCol(1, 2, $trans['description']);
		$rep->TextCol(2, 3, $trans['units']);
		$rep->AmountCol(3, 4, $trans['QtyOnHand'], $dec);
		
		$rep->AmountCol(4, 5, $sale_rate, $dec);
		
		$rep->AmountCol(5, 6, $trans['UnitCost'], $dec);
		$rep->AmountCol(6, 7, $trans['ItemTotal'], $dec);
		
		
		$total_qty += $trans['QtyOnHand'];
	    $total_cost_value += $trans['ItemTotal'];
		
	}
	$rep->Line($rep->row - 4);
	$rep->NewLine(2);
	
	$rep->TextCol(1, 3, _('Grand Total'));
	$rep->AmountCol(3, 4, $total_qty, $dec);
	$rep->AmountCol(6, 7, $total_cost_value, $dec);
	
    $rep->End();
}

