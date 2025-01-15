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

$page_security = 'SA_SALES_ORDER_QTY_REPORT';

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
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");




//----------------------------------------------------------------------------------------------------

print_stock_check();


function get_inv_supplier_name1($supplier_id)
{
	$sql = "SELECT supp_name from ".TB_PREF."suppliers where supplier_id=".db_escape($supplier_id);

	$result=db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	return $row[0];
}



function getTransactions($supplier,$category, $location, $subcategory,$date)
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
		
		$sql.=TB_PREF."stock_moves move
		WHERE item.category_id=category.category_id
		AND item.stock_id=move.stock_id
		AND (item.mb_flag='B' OR item.mb_flag='M')";
		
	if ($category != 0)
		$sql .= " AND item.category_id = ".db_escape($category);
	
	if ($subcategory != 'all')
		$sql .= "  AND item.item_sub_category = ".db_escape($subcategory);
	
	 if ($supplier != 'all')
		$sql .= "  AND item.supplier_id = ".db_escape($supplier);
	
	if ($location != 'all')
		$sql .= " AND IF(move.stock_id IS NULL, '1=1',move.loc_code = ".db_escape($location).")";
		
	if ($date != 0)
		$sql .= " AND move.tran_date <= '$date'";		
 
	$sql .= " GROUP BY item.category_id,
		category.description,
		item.stock_id,
		item.description
		ORDER BY item.category_id,
		item.stock_id";
		
	//display_error($sql); die;	

    return db_query($sql,"No transactions were returned");
}

//Sales YTD
function get_sales_quantity($stock_id,$supplier,$start_date_,$end_date_,$local_foreign_type)
{

    $home_curr = get_company_currency();
	
	$start_date = date2sql($start_date_);
	$end_date = date2sql($end_date_);

	$sql =  "SELECT 
			SUM(dtd.quantity* IF(dtd.debtor_trans_type=10,1,-1)) AS sales_qty 
			FROM "
			.TB_PREF."stock_master item";
			
		$sql.=",".TB_PREF."debtor_trans_details dtd,".TB_PREF."debtor_trans dt,".TB_PREF."debtors_master debtor
		WHERE item.stock_id=dtd.stock_id 
		AND dt.trans_no=dtd.debtor_trans_no 
		AND debtor.debtor_no=dt.debtor_no
		AND dt.type=dtd.debtor_trans_type and dt.type in (10,11) and dtd.debtor_trans_type in (10,11)  
		AND item.mb_flag<>'D' AND mb_flag <> 'F' 
		AND dt.tran_date >= '$start_date' AND dt.tran_date <= '$end_date' ";
					
		$sql .= " AND item.stock_id = ".db_escape($stock_id);
		
		if ($supplier != 'all')
		$sql .= " AND item.supplier_id = ".db_escape($supplier);
		
	  
		
		if ($local_foreign_type == '1'){
		$sql .= " AND debtor.curr_code =".db_escape($home_curr)."";
	    }
	    elseif ($local_foreign_type == '2'){
		$sql .= " AND debtor.curr_code != ".db_escape($home_curr)."";
	    }	

		$sql.=" GROUP BY item.stock_id";
		
		
		
		$result= db_query($sql,"No transactions were returned");
		$row=db_fetch_row($result);
		$sales_qty= $row[0]==""?0:$row[0];
				
		return $sales_qty;
		
}


//----------------------------------------------------------------------------------------------------

function print_stock_check()
{
    global $comp_path, $path_to_root, $pic_height;

    
	
	$date = $_POST['PARAM_0'];
	$supplier = $_POST['PARAM_1'];
    $category = $_POST['PARAM_2'];
	$subcategory = $_POST['PARAM_3'];
    $location = $_POST['PARAM_4'];
    $comments = $_POST['PARAM_5'];
	$destination = $_POST['PARAM_6'];
	
	$destination=1;
	
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
	
	
	$current_month_begin=date('Y-m-01', strtotime(date2sql($date)));
	$current_year_begin=date('Y-01-01', strtotime(date2sql($date)));
	
	$current_year_end=date('Y-12-31', strtotime(date2sql($date)));

	
	//last year current month begin
	$last_year_current_month_begin = strtotime("-1 year", strtotime($current_month_begin));
	$last_year_current_month_begin= date("Y-m-d", $last_year_current_month_begin);
	
	//last year current month end
	$last_year_current_month_end = strtotime("-1 year", strtotime(date2sql($date)));
	$last_year_current_month_end= date("Y-m-d", $last_year_current_month_end);
	
	//last year current year begin
	$last_year_current_year_begin = strtotime("-1 year", strtotime($current_year_begin));
	$last_year_current_year_begin= date("Y-m-d", $last_year_current_year_begin);
	
	//last year current month end
	$last_year_current_year_end = strtotime("-1 year", strtotime(date2sql($date)));
	$last_year_current_year_end= date("Y-m-d", $last_year_current_year_end);
	
	//last year end
	$last_year_end = strtotime("-1 year", strtotime($current_year_end));
	$last_year_end= date("Y-m-d", $last_year_end);
	
	
     $current_month_begin=sql2date($current_month_begin);
	 $last_year_current_month_begin=sql2date($last_year_current_month_begin);
	 $last_year_current_month_end=sql2date($last_year_current_month_end);
	 $last_year_current_year_begin=sql2date($last_year_current_year_begin);
	 $last_year_current_year_end=sql2date($last_year_current_year_end);
	 $current_year_begin=sql2date($current_year_begin);
	 $last_year_end=sql2date($last_year_end);
	 
	
	 $curyeardate = strtotime(date("Y-m-t", strtotime(date2sql($date))));
	 
	 $lastyeardate = strtotime("-1 year", strtotime(date2sql($date)));
	 $lastyear = strtotime(date("Y-m-t", $lastyeardate));
	 
	 $c_year = date('Y', $curyeardate);
	 $p_year = date('Y', $lastyear);
	 
	
		
	$cols2 = array(0,30, 100, 225, 250, 315, 380, 445,	500,560,610,660);
	$headers2 = array(_('S.No.'),_('Part Code'), _('Product Description'), _('UOM'), 
	_('Stock'), _('Back Order Qty'), _('Sales Order Qty'),
	"Sales YTD ".$c_year, "Sales YTD ".$c_year, "Sales YTD ".$p_year, "Sales YTD ".$p_year);
	$aligns2 = array('left',	'left',	'left','left', 'right', 'right', 'right', 'right', 'right', 'right', 'right');
	
	$cols1 = array(0,30, 100, 225, 250, 315, 380, 445,	500,560,610,660);
	$headers1 = array(_(''),_(''), _(''), _(''), 
	_(''), _(''), _(''),
	_('Local'), _('Export'), _('Local'), _('Export'));
	$aligns1 = array('left',	'left',	'left','left', 'right', 'right', 'right', 'right', 'right', 'right', 'right');


   $params =   array( 	0 => $comments,
	                    1 => array('text' => _('Date'), 'from' => $date, 'to' => ''),
    				    2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
                        3 => array('text' => _('Supplier'), 'from' => $sup, 'to' => ''),
						5 => array('text' => _('Sub Category'), 'from' => $subcat, 'to' => ''),			
    				    6 => array('text' => _('Location'), 'from' => $loc, 'to' => ''));

	$user_comp = "";

    $rep = new FrontReport(_('Order Qty Based On Sales With All Details'), "OrderQtyBasedOnSalesWithAllDetails", user_pagesize());

    $rep->Font();
    //$rep->Info($params, $cols, $headers, $aligns);
	$rep->Info($params, $cols1, $headers1, $aligns1, $cols2, $headers2, $aligns2);
    $rep->NewPage();
	
	$res = getTransactions($supplier,$category, $location, $subcategory,date2sql($date));
	
	$total_stock = $total_back_order_qty = $total_sales_order_qty = 0.0;
	
	$total_cur_year_local_sales = $total_cur_year_foreign_sales = $total_prev_year_local_sales = $total_prev_year_foreign_sales = 0.0;
	
	$i=1;

	
	$catt = '';
	while ($trans=db_fetch($res))
	{
		if ($location == 'all')
			$loc_code = "";
		else
			$loc_code = $location;
		
		$demandqty = get_demand_qty($trans['stock_id'], $loc_code);
		$demandqty += get_demand_asm_qty($trans['stock_id'], $loc_code);
		$onorder = get_on_porder_qty($trans['stock_id'], $loc_code);
		$onorder += get_on_worder_qty($trans['stock_id'], $loc_code);
		
		
		$rep->NewLine();
		$dec = user_price_dec();
		
		$rep->TextCol(0, 1, $i);
		$rep->TextCol(1, 2, $trans['stock_id']);
		$rep->TextCol(2, 3, $trans['description']);
		$rep->TextCol(3, 4, $trans['units']);
		$rep->AmountCol(4, 5, $trans['QtyOnHand'], $dec);
		$rep->AmountCol(5, 6, $onorder, $dec);
		$rep->AmountCol(6, 7, $demandqty, $dec);
		
		//Current Year Local
		$ytd_cy_local_stock=get_sales_quantity($trans['stock_id'],$supplier,$current_year_begin,$date,1);
		$rep->AmountCol(7, 8,$ytd_cy_local_stock,3);
		
		
		//Current Year Foreign
		$ytd_cy_foreign_stock=get_sales_quantity($trans['stock_id'],$supplier,$current_year_begin,$date,2);
		$rep->AmountCol(8, 9,$ytd_cy_foreign_stock,$dec);
	
		
		//Previous Year Local
		$ytd_py_local_stock=get_sales_quantity($trans['stock_id'],$supplier,$last_year_current_year_begin,$last_year_end,1);
		$rep->AmountCol(9, 10,$ytd_py_local_stock,$dec);
		
		//Previous Year Foreign
		$ytd_py_foreign_stock=get_sales_quantity($trans['stock_id'],$supplier,$last_year_current_year_begin,$last_year_end,2);
		$rep->AmountCol(10, 11,$ytd_py_foreign_stock,$dec);
		
		
		$total_stock += $trans['QtyOnHand'];
		$total_back_order_qty += $onorder;
		$total_sales_order_qty += $demandqty;
		
		$total_cur_year_local_sales += $ytd_cy_local_stock;
		$total_cur_year_foreign_sales += $ytd_cy_foreign_stock;
		
		$total_prev_year_local_sales += $ytd_py_local_stock;
		$total_prev_year_foreign_sales += $ytd_py_foreign_stock;
		
		
		
	    $i++;
		
	}
	$rep->Line($rep->row - 4);
	$rep->NewLine();
	
	$rep->TextCol(2, 4, _("Total"));
	
	$rep->AmountCol(4, 5,$total_stock,$dec);
	$rep->AmountCol(5, 6,$total_back_order_qty,$dec);
	$rep->AmountCol(6, 7,$total_sales_order_qty,$dec);
	
	$rep->AmountCol(7, 8,$total_cur_year_local_sales,$dec);
	$rep->AmountCol(8, 9,$total_cur_year_foreign_sales,$dec);
	$rep->AmountCol(9, 10,$total_prev_year_local_sales,$dec);
	$rep->AmountCol(10, 11,$total_prev_year_foreign_sales,$dec);
	
    $rep->End();
}

