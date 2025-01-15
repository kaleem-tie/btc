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
$page_security = 'SA_INVENTORY_AGING_REPORT';  //'SA_ITEMSANALYTIC'; //'SA_ITEMSANALYTIC';
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


function getTransactions($supplier,$category,$subcategory)
{
	
	$sql = "SELECT ".TB_PREF."stock_master.category_id,
			".TB_PREF."stock_category.description AS cat_description,
			".TB_PREF."stock_master.stock_id,
			".TB_PREF."stock_master.description
		FROM "
			.TB_PREF."stock_master,"
			.TB_PREF."stock_category";
		if($supplier!='all')
		{
			$sql.=",".TB_PREF."suppliers supp";
		}
			
		if ($subcategory != 'all')
		{
			$sql.=",".TB_PREF."item_sub_category item_sb";
		}		
		 
		
		$sql.="	WHERE ".TB_PREF."stock_master.category_id=".TB_PREF."stock_category.category_id
		AND (".TB_PREF."stock_master.mb_flag='B' OR ".TB_PREF."stock_master.mb_flag='M')";

	if ($category != 0)
		$sql .= " AND ".TB_PREF."stock_master.category_id = ".db_escape($category);
		
	if ($subcategory != 'all')
		$sql .= " AND ".TB_PREF."stock_master.item_sub_category=item_sb.id AND ".TB_PREF."stock_master.item_sub_category = ".db_escape($subcategory);	
		
		
	if ($supplier != 'all')
		$sql .= " AND ".TB_PREF."stock_master.supplier_id = supp.supplier_id AND ".TB_PREF."stock_master.supplier_id = ".db_escape($supplier);
	 
	$sql .= " GROUP BY 
		".TB_PREF."stock_master.stock_id 
		ORDER BY ".TB_PREF."stock_master.category_id,
		".TB_PREF."stock_master.stock_id";
		
	

    return db_query($sql,"No transactions were returned");
}


 function get_aging_unit_cost($stock_id,$tran_date){
$sql = "SELECT standard_cost FROM ".TB_PREF."stock_moves WHERE stock_id=".db_escape($stock_id)." AND tran_date <= ".db_escape($tran_date)." AND type IN('17','25') order by trans_id desc LIMIT 1";

$res = db_query($sql);
$row = db_fetch_row($res);
return $row['0'];
}

function getOpeningStock($stock_id,$location,$rep_date,$PastDueDays5)
{
	$sql = "SELECT sum(qty) FROM ".TB_PREF."stock_moves WHERE stock_id=".db_escape($stock_id)." AND tran_date <= DATE_ADD(".db_escape($rep_date).", INTERVAL -($PastDueDays5+1) DAY)";
	
	if ($location != 'all')
		$sql .= " AND loc_code = ".db_escape($location)."";	
	
     $res = db_query($sql);
    $row = db_fetch_row($res);
	if($row[0]==null || $row[0]=='')
	return 0;
    return $row['0'];
}

function getInwardStock($stock_id,$location,$from_date,$to_date)
{
	$sql = "SELECT sum(qty) FROM ".TB_PREF."stock_moves WHERE stock_id=".db_escape($stock_id)." AND tran_date between ".db_escape($from_date)." and ".db_escape($to_date)." and qty>0";
	
	if ($location != 'all')
		$sql .= " AND loc_code = ".db_escape($location)."";
     $res = db_query($sql);
    $row = db_fetch_row($res);
	if($row[0]==null || $row[0]=='')
	return 0;
    return $row['0'];
}

function getOutwardStock($stock_id,$location,$from_date,$to_date)
{
	$sql = "SELECT sum(qty) FROM ".TB_PREF."stock_moves WHERE stock_id=".db_escape($stock_id)." AND tran_date between ".db_escape($from_date)." and ".db_escape($to_date)." and qty<0";
	
	if ($location != 'all')
		$sql .= " AND loc_code = ".db_escape($location)."";
   
    $res = db_query($sql);
    $row = db_fetch_row($res);
	if($row[0]==null || $row[0]=='')
	return 0;
    return $row['0'];
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
	$orientation = $_POST['PARAM_6'];
	$destination = $_POST['PARAM_7'];
	
	$destination=1;
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	
	$orientation = ($orientation ? 'P' : 'L');

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
	
	$PastDueDays1 = get_company_pref('past_due_days');
	$PastDueDays2 = 2 * $PastDueDays1;
	$nowdue = "1-" . $PastDueDays1 . " " . _('Days');
	$pastdue1 = $PastDueDays1 + 1 . "-" . $PastDueDays2 . " " . _('Days');
	$pastdue2 = _('Over') . " " . $PastDueDays2 . " " . _('Days');
	

//UP	
$cols2 = array(0, 150,220,300,380,450,520,600,680,750,820,900);

$headers2 = array(_('Stock ID'), _('Description'),  _(''), $nowdue, _(''),  $pastdue1, _(''), $pastdue2,  _(''));
		
$aligns2 = array('left','left',	'right', 'right','right','right', 'right','right','right', 'right','right');


//Down
$cols1 = array(0, 150,220,300,380,450,520,600,680,750,820,900);

$headers1 = array(_(''), _(''), _('Unit Price'), _('Qty'), _('Total'), 
_('Qty'), _('Total'),_('Qty'), _('Total'),  _('Total Qty'), _('Total Value'));

$aligns1 = array('left','left',	'right', 'right','right','right', 'right','right','right', 'right','right');


$params =   array( 	0 => $comments,
	                    1 => array('text' => _('Date'), 'from' => $rep_date, 'to' => ''),
    				    2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
                        3 => array('text' => _('Supplier'), 'from' => $sup, 'to' => ''),
						4 => array('text' => _('Sub Category'), 'from' => $subcat, 'to' => ''),		
    				    5 => array('text' => _('Location'), 'from' => $loc, 'to' => ''));

	$user_comp = "";

   // $rep = new FrontReport(_('Item Aging Report'), "ItemAgingReport", user_pagesize());
   
   $rep = new FrontReport(_('Item Aging Report'), "ItemAgingReport", user_pagesize(), 9, $orientation);
   	if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
	$rep->Info($params, $cols1, $headers1, $aligns1, $cols2, $headers2, $aligns2);
    //$rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$res = getTransactions($supplier,$category, $subcategory);
	$catt = '';
	
	$grand_tot_balance  = $grand_tot_std  = $grand_tot_amount = $pastdue0_total=$pastdue1_total=$pastdue2_total=$pastdue3_total= $pastdue4_total=$pastdue5_total=0;
	

	while ($trans=db_fetch($res))
	{
		$out_ward_stock=0;
		$total_qty=0;
	    $total_balance=0;
		
		if ($location == 'all')
			$loc_code = "";
		else
			$loc_code = $location;

		$rep->NewLine();
		$dec = user_price_dec();
		
		//$dec = get_qty_dec($trans['stock_id']);
		$dec = 3;
		$rep->TextCol(0, 1, $trans['stock_id']);
		$rep->TextCol(1, 2, $trans['description']);
		
		$stock_price=number_format2(get_aging_unit_cost($trans['stock_id'],date2sql($rep_date)),3);
		$rep->AmountCol(2, 3, $stock_price,$dec);
		
		
		$PastDueDays1 = get_company_pref('past_due_days');
	    $PastDueDays2 = 2 * $PastDueDays1;
	    
				
		
		//outward stock
		$out_ward_stock=abs(getOutwardStock($trans['stock_id'],$location,date('Y-m-d', strtotime(date2sql($rep_date). ' - '.($PastDueDays2).' days')),date2sql($rep_date)));
		
		
		//PastDueDays0
		$past_due_days0_stock=getInwardStock($trans['stock_id'],$location,date('Y-m-d', strtotime(date2sql($rep_date). ' - '.($PastDueDays1+1).' days')),date2sql($rep_date));
		
		if($past_due_days0_stock<=$out_ward_stock)
		{
			 $past_due_days0_stock=0;
			 $out_ward_stock-=$past_due_days0_stock;
		}
		else
        {
			$past_due_days0_stock=$past_due_days0_stock-$out_ward_stock;
			$out_ward_stock=0;
		}	
		
	    $rep->AmountCol(3, 4, $past_due_days0_stock,$dec);
		$rep->AmountCol(4, 5, $past_due_days0_stock*$stock_price,$dec);
		
		$total_qty+=$past_due_days0_stock;
		$pastdue0_total+=$past_due_days0_stock*$stock_price;
		
		
		//PastDueDays1
		$past_due_days1_stock=getInwardStock($trans['stock_id'],$location,date('Y-m-d', strtotime(date2sql($rep_date). ' - '.($PastDueDays2+1).' days')),date('Y-m-d', strtotime(date2sql($rep_date). ' - '.($PastDueDays1).' days')));
		
		if($past_due_days1_stock<=$out_ward_stock)
		{
			 $past_due_days1_stock=0;
			 $out_ward_stock-=$past_due_days1_stock;
		}
		else
        {
			$past_due_days1_stock=$past_due_days1_stock-$out_ward_stock;
			$out_ward_stock=0;
		}	
		
	    $rep->AmountCol(5, 6, $past_due_days1_stock,$dec);
		$rep->AmountCol(6,7, $past_due_days1_stock*$stock_price,$dec);
		$total_qty+=$past_due_days1_stock;
		$pastdue1_total+=$past_due_days1_stock*$stock_price;
		
		
		//PastDueDays5
		$aging_opening_stock=getOpeningStock($trans['stock_id'],$location,date2sql($rep_date),$PastDueDays2);
		
				
		if($aging_opening_stock<=$out_ward_stock)
		{
			 $aging_opening_stock=0;
			 $out_ward_stock-=$aging_opening_stock;
		}
		else
        {
			$aging_opening_stock=$aging_opening_stock-$out_ward_stock;
			$out_ward_stock=0;
		}			

	    $rep->AmountCol(7, 8, $aging_opening_stock,$dec);
		$rep->AmountCol(8, 9, $aging_opening_stock*$stock_price,$dec);
		$total_qty+=$aging_opening_stock;
		$pastdue2_total+=$aging_opening_stock*$stock_price;
		
		
		
        $rep->AmountCol(9, 10, $total_qty,$dec);
		$rep->AmountCol(10, 11, $total_qty*$stock_price,$dec);
		
		}		


	$rep->Line($rep->row - 4);
	$rep->NewLine();
	
	
	
	$rep->font('bold');
	$rep->TextCol(1 , 2, _('Grand Total'));
	$rep->AmountCol(4, 5, $pastdue0_total,$dec);
	$rep->AmountCol(6, 7, $pastdue1_total,$dec);
	$rep->AmountCol(8, 9, $pastdue2_total,$dec);
	$rep->AmountCol(10, 11, ($pastdue0_total+$pastdue1_total+$pastdue2_total),$dec);
	$rep->font();	
	
    $rep->End();
}

