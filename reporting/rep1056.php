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
$page_security = 'SA_SALEPRICLISTREP';
// ----------------------------------------------------------------
// $ Revision:	2.4 $
// Creator:		Joe Hunt, boxygen
// date_:		2014-05-13
// Title:		Sale Price Listing Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");
//include_once($path_to_root . "/sales/includes/db/customers_db.inc");

//----------------------------------------------------------------------------------------------------

print_sales_price_listing_report();


function getSalesPrice($stock_id)	
{
	$sql= "Select price from ".TB_PREF."prices p WHERE sales_type_id=1 AND stock_id =" .db_escape($stock_id);
	
	$result = db_query($sql,"No prices were returned");
	
	$row = db_fetch_row($result);
	return $row[0];
	
}


function getTransactions($category, $fromsupp = ALL_TEXT)
{
	
          $sql = "SELECT s.stock_id, cat.description as cat_description,
          s.description, s.units, cat.category_id, s.material_cost as cost_price
         FROM ".TB_PREF."stock_master  s, 
         ".TB_PREF."stock_category cat 
         WHERE s.category_id = cat.category_id";

	   if ($category != 0)
       $sql .= " AND s.category_id = ".db_escape($category);
   
  if ($fromsupp != ALL_TEXT)
		$sql .= " AND s.supplier_id=".db_escape($fromsupp);

    $sql .= " ORDER BY cat.category_id";

	//display_error($sql); die;
	
		 
    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_sales_price_listing_report()
{
    global $path_to_root, $SysPrefs;

    $category = $_POST['PARAM_0'];
    $fromsupp = $_POST['PARAM_1'];
    $comments = $_POST['PARAM_2'];
	$orientation = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	//$detail = !$detail;
    $dec = user_price_dec();

	$orientation = ($orientation ? 'L' : 'P');
	if ($category == ALL_NUMERIC)
		$category = 0;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);

	if ($fromsupp == ALL_TEXT)
		$supp = _('All');
	else
		$supp = get_supplier_name($fromsupp);
    	$dec = user_price_dec();

 
	  $cols = array(0, 75, 250, 270, 330, 390, 430, 490, 540);

	$headers = array(_('Category'), '', _('UOM'), _('Sales Price'), _('Cost Price'), _('Profit'), _('Profit %') );

	$aligns = array('left',	'left',	'left', 'right', 'right','right', 'right', 'right');

    $params =   array( 	0 => $comments,
    					1 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
    				    2 => array('text' => _('Supplier'), 'from' => $supp, 'to' => '')
						);

    $rep = new FrontReport(_('Sales Price Listing Report'), "SalesPriceListReport", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$res = getTransactions($category, $fromsupp);
	
	$catt = '';
	while ($trans=db_fetch($res))
	{
		if ($catt != $trans['cat_description'])
		{
			if ($catt != '')
			{
				$rep->Line($rep->row - 2);
				$rep->NewLine(2, 3);
			}
			$rep->TextCol(0, 1, $trans['category_id']);
			$rep->TextCol(1, 2, $trans['cat_description']);
			$catt = $trans['cat_description'];
			$rep->NewLine();
		}
		
		
		
			$rep->NewLine();
			$rep->fontSize -= 2;
			$rep->TextCol(0, 1, $trans['stock_id']);
			$rep->TextCol(1, 2, $trans['description']);
			$rep->TextCol(2, 3, $trans['units']);
			
			$rep->AmountCol(3, 4, getSalesPrice($trans['stock_id']),$dec);
			$rep->AmountCol(4, 5, $trans['cost_price'],$dec);
			
			if (getSalesPrice($trans['stock_id'])<=0 || $trans['cost_price'] <= 0){
				$trans['cost_price'] = 0;
				$profit = 0;
			}else 
			{
				$profit = getSalesPrice($trans['stock_id'])- $trans['cost_price'];
			}
			$rep->AmountCol(5, 6, $profit,$dec);
			
			//$profit == 0 ? $trans['cost_price'] = 0 : $profit_percent = $profit/ $trans['cost_price'] *100; 
			if($profit==0)
			{
				$trans['cost_price']=0;
				$profit_percent=0;
				
			}else
			$profit_percent = $profit/ $trans['cost_price'] *100; 
			
			$rep->AmountCol(6, 7, $profit_percent,$dec);
			
			//$rep->AmountCol(4, 5, ($trans['cost_price']));
			
			//$dec2 = 0;
			//price_decimal_format($UnitCost, $dec2);
			//$rep->AmountCol(6, 7, $UnitCost, 3);
			$rep->fontSize += 2;
		
		
	}
	

	
		$rep->Line($rep->row - 2);
		$rep->NewLine();
	
	//$rep->NewLine(2, 1);
	
	//$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
   //ravi end

}

