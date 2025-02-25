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
$page_security = 'SA_ITEMSVALREP';
// ----------------------------------------------------------------
// $ Revision:	2.4 $
// Creator:		boxygen, Joe Hunt
// date_:		2017-05-14
// Title:		Costed Inventory Movements
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

function get_domestic_price($myrow, $stock_id)
{
    if ($myrow['type'] == ST_SUPPRECEIVE || $myrow['type'] == ST_SUPPCREDIT)
     {
        $price = $myrow['price'];
        if ($myrow['person_id'] > 0)
        {
            // Do we have foreign currency?
            $supp = get_supplier($myrow['person_id']);
            $currency = $supp['curr_code'];
            $ex_rate = $myrow['ex_rate'];
            $price *= $ex_rate;
        }
    }
    else
        $price = $myrow['standard_cost']; //pick standard_cost for sales deliveries

    return $price;
}

function fetch_items($category=0, $subcategory=0, $item='')
{
		$sql = "SELECT stock_id, stock.description AS name,
				stock.category_id,units,
				cat.description
			FROM ".TB_PREF."stock_master stock LEFT JOIN ".TB_PREF."stock_category cat ON stock.category_id=cat.category_id" ;
			
			if($subcategory!='all')
		{
			$sql.=" LEFT JOIN ".TB_PREF."item_sub_category item_sb ON stock.item_sub_category=item_sb.id";
		}
			
			$sql.="	WHERE mb_flag <> 'D' AND mb_flag <> 'F'";
		if ($category != 0)
			$sql .= " AND cat.category_id = ".db_escape($category);
		
		if ($subcategory != 'all'){
			$sql .= " AND stock.item_sub_category = ".db_escape($subcategory);
		}else if ($subcategory == 'all'){
			$sql .= " AND  (stock.item_sub_category = 0  OR stock.item_sub_category != 0)";
		}
		if ($item != '')
		$sql .= " AND stock_id = ".db_escape($item);
		/*if ($fromsupp != ALL_TEXT)
		$sql .= " AND stock.supplier_id=".db_escape($fromsupp);*/
	
		$sql .= " GROUP BY stock.category_id,
		cat.description,
		stock.stock_id,
		stock.description
		ORDER BY stock.category_id,
		stock.stock_id";

    return db_query($sql,"No transactions were returned");
}

function trans_qty($stock_id, $location, $from_date, $to_date, $inward = true)
{
	if ($from_date == null)
		$from_date = Today();

	$from_date = date2sql($from_date);	

	if ($to_date == null)
		$to_date = Today();

	$to_date = date2sql($to_date);

	$sql = "SELECT ".($inward ? '' : '-')."SUM(qty) FROM ".TB_PREF."stock_moves
		WHERE stock_id=".db_escape($stock_id)."
		AND tran_date >= '$from_date' 
		AND tran_date <= '$to_date' AND type <> ".ST_LOCTRANSFER;

	if ($location != '')
		$sql .= " AND loc_code = ".db_escape($location);

	if ($inward)
		$sql .= " AND qty > 0 ";
	else
		$sql .= " AND qty < 0 ";

	$result = db_query($sql, "QOH calculation failed");

	$myrow = db_fetch_row($result);	

	return $myrow[0];

}

function purch_trans_qty($stock_id, $location, $from_date, $to_date, $inward = true)
{
	if ($from_date == null)
		$from_date = Today();

	$from_date = date2sql($from_date);	

	if ($to_date == null)
		$to_date = Today();

	$to_date = date2sql($to_date);

	$sql = "SELECT ".($inward ? '' : '-')."SUM(qty) FROM ".TB_PREF."stock_moves
		WHERE stock_id=".db_escape($stock_id)."
		AND tran_date >= '$from_date' 
		AND tran_date <= '$to_date' AND type = ".ST_SUPPRECEIVE;

	if ($location != '')
		$sql .= " AND loc_code = ".db_escape($location);

	if ($inward)
		$sql .= " AND qty > 0 ";
	else
		$sql .= " AND qty < 0 ";

	$result = db_query($sql, "QOH calculation failed");

	$myrow = db_fetch_row($result);	

	return $myrow[0];

}

function transfer_trans_qty($stock_id, $location, $from_date, $to_date, $inward = true)
{
	if ($from_date == null)
		$from_date = Today();

	$from_date = date2sql($from_date);	

	if ($to_date == null)
		$to_date = Today();

	$to_date = date2sql($to_date);

	$sql = "SELECT ".($inward ? '' : '-')."SUM(qty) FROM ".TB_PREF."stock_moves
		WHERE stock_id=".db_escape($stock_id)."
		AND tran_date >= '$from_date' 
		AND tran_date <= '$to_date' AND type = ".ST_LOCTRANSFER;

	if ($location != '')
		$sql .= " AND loc_code = ".db_escape($location);

	if ($inward)
		$sql .= " AND qty > 0 ";
	else
		$sql .= " AND qty < 0 ";

	$result = db_query($sql, "QOH calculation failed");

	$myrow = db_fetch_row($result);	

	return $myrow[0];

}

function adj_trans_qty($stock_id, $location, $from_date, $to_date, $inward = true)
{
	if ($from_date == null)
		$from_date = Today();

	$from_date = date2sql($from_date);	

	if ($to_date == null)
		$to_date = Today();

	$to_date = date2sql($to_date);

	$sql = "SELECT ".($inward ? '' : '-')."SUM(qty) FROM ".TB_PREF."stock_moves
		WHERE stock_id=".db_escape($stock_id)."
		AND tran_date >= '$from_date' 
		AND tran_date <= '$to_date' AND type = ".ST_INVADJUST;

	if ($location != '')
		$sql .= " AND loc_code = ".db_escape($location);

	if ($inward)
		$sql .= " AND qty > 0 ";
	else
		$sql .= " AND qty < 0 ";

	$result = db_query($sql, "QOH calculation failed");

	$myrow = db_fetch_row($result);	

	return $myrow[0];

}

function sales_trans_qty($stock_id, $location, $from_date, $to_date, $inward = true)
{
	if ($from_date == null)
		$from_date = Today();

	$from_date = date2sql($from_date);	

	if ($to_date == null)
		$to_date = Today();

	$to_date = date2sql($to_date);

	$sql = "SELECT ".($inward ?'-' : '')."SUM(qty) FROM ".TB_PREF."stock_moves
		WHERE stock_id=".db_escape($stock_id)."
		AND tran_date >= '$from_date' 
		AND tran_date <= '$to_date' AND type = ".ST_CUSTDELIVERY;

	if ($location != '')
		$sql .= " AND loc_code = ".db_escape($location);

	if ($inward)
	//	$sql .= " AND qty > 0 ";
	//else
		$sql .= " AND qty < 0 ";
	
	//display_error($sql);

	$result = db_query($sql, "QOH calculation failed");
	
	$myrow = db_fetch_row($result);	

	return $myrow[0];

}

function avg_unit_cost($stock_id, $location, $to_date)
{
	if ($to_date == null)
		$to_date = Today();

	$to_date = date2sql($to_date);

  	$sql = "SELECT move.*, supplier.supplier_id person_id, IF(ISNULL(grn.rate), credit.rate, grn.rate) ex_rate
  		FROM ".TB_PREF."stock_moves move
				LEFT JOIN ".TB_PREF."supp_trans credit ON credit.trans_no=move.trans_no AND credit.type=move.type
				LEFT JOIN ".TB_PREF."grn_batch grn ON grn.id=move.trans_no AND 25=move.type
				LEFT JOIN ".TB_PREF."suppliers supplier ON IFNULL(grn.supplier_id, credit.supplier_id)=supplier.supplier_id
				LEFT JOIN ".TB_PREF."debtor_trans cust_trans ON cust_trans.trans_no=move.trans_no AND cust_trans.type=move.type
				LEFT JOIN ".TB_PREF."debtors_master debtor ON cust_trans.debtor_no=debtor.debtor_no
			WHERE stock_id=".db_escape($stock_id)."
			AND move.tran_date < '$to_date' AND qty <> 0 AND move.type <> ".ST_LOCTRANSFER;

	if ($location != '')
		$sql .= " AND move.loc_code = ".db_escape($location);

	$sql .= " ORDER BY tran_date";	

	$result = db_query($sql, "No standard cost transactions were returned");

    if ($result == false)
    	return 0;

	$qty = $tot_cost = 0;
	while ($row=db_fetch($result))
	{
		$qty += $row['qty'];	
		$price = get_domestic_price($row, $stock_id);
        $tran_cost = $price * $row['qty'];
        $tot_cost += $tran_cost;
	}
	if ($qty == 0)
		return 0;
	return $tot_cost / $qty;
}

//----------------------------------------------------------------------------------------------------

function trans_qty_unit_cost($stock_id, $location, $from_date, $to_date, $inward = true)
{
	if ($from_date == null)
		$from_date = Today();

	$from_date = date2sql($from_date);	

	if ($to_date == null)
		$to_date = Today();

	$to_date = date2sql($to_date);

  	$sql = "SELECT move.*, supplier.supplier_id person_id, IF(ISNULL(grn.rate), credit.rate, grn.rate) ex_rate
  		FROM ".TB_PREF."stock_moves move
				LEFT JOIN ".TB_PREF."supp_trans credit ON credit.trans_no=move.trans_no AND credit.type=move.type
				LEFT JOIN ".TB_PREF."grn_batch grn ON grn.id=move.trans_no AND 25=move.type
				LEFT JOIN ".TB_PREF."suppliers supplier ON IFNULL(grn.supplier_id, credit.supplier_id)=supplier.supplier_id
				LEFT JOIN ".TB_PREF."debtor_trans cust_trans ON cust_trans.trans_no=move.trans_no AND cust_trans.type=move.type
				LEFT JOIN ".TB_PREF."debtors_master debtor ON cust_trans.debtor_no=debtor.debtor_no
		WHERE stock_id=".db_escape($stock_id)."
		AND move.tran_date >= '$from_date' AND move.tran_date <= '$to_date' AND qty <> 0 AND move.type <> ".ST_LOCTRANSFER;

	if ($location != '')
		$sql .= " AND move.loc_code = ".db_escape($location);

	if ($inward)
		$sql .= " AND qty > 0 ";
	else
		$sql .= " AND qty < 0 ";
	$sql .= " ORDER BY tran_date";
	
	$result = db_query($sql, "No standard cost transactions were returned");
    
    if ($result == false)
    	return 0;
	
	$qty = $tot_cost = 0;
	while ($row=db_fetch($result))
	{
        $qty += $row['qty'];
        $price = get_domestic_price($row, $stock_id); 
        $tran_cost = $row['qty'] * $price;
        $tot_cost += $tran_cost;
	}	
	if ($qty == 0)
		return 0;
	return $tot_cost / $qty;
}

//----------------------------------------------------------------------------------------------------

function inventory_movements()
{
    global $path_to_root;

    $from_date = $_POST['PARAM_0'];
    $to_date = $_POST['PARAM_1'];
    $category = $_POST['PARAM_2'];
	$subcategory     = $_POST['PARAM_3'];
	$location = $_POST['PARAM_4'];
	$item = $_POST['PARAM_5'];
	//$fromsupp = $_POST['PARAM_5'];
    $comments = $_POST['PARAM_6'];
	$orientation = $_POST['PARAM_7'];
	$destination = $_POST['PARAM_8'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
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

	if ($location == '')
		$loc = _('All');
	else
		$loc = get_location_name($location);
	
	if ($item == '')
		$itm = _('All');
	else
		$itm = $item;
	
	/*if ($fromsupp == ALL_TEXT)
		$supp = _('All');
	else
		$supp = get_supplier_name($fromsupp);*/

	$cols = array(0, 60, 134, 160, 200, 250, 300, 350, 400, 440, 480, 520);

	$headers = array(_('Item Code'), _('Description'),	_('UOM'), _('Opening'), _('Purchases'), _('Transfers'),_('Sales/DO'), _('Adjustments /'), '',_('ClosingStock'), '' );
	$headers2 = array("", "", "", _("Quantity"), _("(Net)"), _("(+/-)"), _("(Net)"), _("Others"), _("Qty"), _("Cost"), _("Value"));

	$aligns = array('left',	'left',	'right', 'right', 'right', 'right', 'right','right' ,'right', 'right', 'right');

    $params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $from_date, 'to' => $to_date),
    				    2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
						3 => array('text' => _('Sub Category'), 'from' => $subcat, 'to' => ''),
						4 => array('text' => _('Location'), 'from' => $loc, 'to' => ''),
						5 => array('text' => _('Item'), 'from' => $itm, 'to' => ''));
						//4 => array('text' => _('Supplier'), 'from' => $supp, 'to' => '')

    $rep = new FrontReport(_('Stock Ledger Summary - 2'), "StockLedgerSummary2", user_pagesize(), 8, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers2, $aligns, $cols, $headers, $aligns);
    $rep->NewPage();

	$totval_open = $totval_in = $totval_out = $totval_close = 0; 
	$result = fetch_items($category,$subcategory,$item);

	$dec = user_price_dec();
	$catgor = '';
	while ($myrow=db_fetch($result))
	{
		if ($catgor != $myrow['description'])
		{
			//if ($totval_open + $totval_in - $totval_out != 0){
			   if ($catgor != '')
			{
				
				
				$rep->Line($rep->row  - 4);
	            $rep->NewLine(2);
				//	$rep->NewLine(2, 3);
				 $rep->TextCol(1, 2,	_("*** Sub Total **"));
	             $rep->AmountCol(10, 11, $totval_open + $totval_in - $totval_out,$dec);
	             $rep->NewLine(1);
				 $rep->Line($rep->row - 2);
				 $rep->NewLine();
					
				
			}
			$rep->NewLine();
			$totval_open = 0;
			$totval_in = 0; 
			$totval_out = 0; 
			
			
			$rep->NewLine(2);
			$rep->fontSize += 2;
			$rep->TextCol(0, 3, $myrow['category_id'] . " - " . $myrow['description']);
			$catgor = $myrow['description'];
			$rep->fontSize -= 2;
			$rep->NewLine();
			
			//}
		}
		$qoh_start = get_qoh_on_date($myrow['stock_id'], $location, add_days($from_date, -1));
		$qoh_end = get_qoh_on_date($myrow['stock_id'], $location, $to_date);
		
		$inward = trans_qty($myrow['stock_id'], $location, $from_date, $to_date);
		$sales_inward = sales_trans_qty($myrow['stock_id'], $location, $from_date, $to_date);
		$adj_inward = adj_trans_qty($myrow['stock_id'], $location, $from_date, $to_date);
		$transfer_inward = transfer_trans_qty($myrow['stock_id'], $location, $from_date, $to_date);
		$purch_inward = purch_trans_qty($myrow['stock_id'], $location, $from_date, $to_date);
		$outward = trans_qty($myrow['stock_id'], $location, $from_date, $to_date, false);
		$openCost = avg_unit_cost($myrow['stock_id'], $location, $from_date);
		$unitCost = avg_unit_cost($myrow['stock_id'], $location, add_days($to_date, 1));
		if ($qoh_start == 0 && $inward == 0 && $outward == 0 && $qoh_end == 0 && $sales_inward == 0 && 
		$adj_inward == 0 && $transfer_inward == 0 && $purch_inward == 0)
			continue;
		$rep->NewLine();
		$rep->TextCol(0, 1,	$myrow['stock_id']);
		$rep->TextCol(1, 2, substr($myrow['name'], 0, 24) . ' ');
		$rep->TextCol(2, 3, $myrow['units']);
		$rep->AmountCol(3, 4, $qoh_start, get_qty_dec($myrow['stock_id']));
	//	$rep->AmountCol(4, 5, $openCost, $dec);
		$openCost *= $qoh_start;
		$totval_open += $openCost;
	//	$rep->AmountCol(5, 6, $openCost);
		
		//if($inward>0){
		//	$rep->AmountCol(6, 7, $inward, get_qty_dec($myrow['stock_id']));
					
			if($purch_inward > 0){
				$rep->AmountCol(4, 5, $purch_inward, get_qty_dec($myrow['stock_id']));
			}
			
			if($transfer_inward > 0){
				$rep->AmountCol(5, 6, $transfer_inward, get_qty_dec($myrow['stock_id']));
			}
			if($sales_inward > 0){
				$rep->AmountCol(6, 7, $sales_inward, get_qty_dec($myrow['stock_id']));
			}
			
			if($adj_inward > 0){
				$rep->AmountCol(7, 8, $adj_inward, get_qty_dec($myrow['stock_id']));
			}
			
			
			$unitCost_in = trans_qty_unit_cost($myrow['stock_id'], $location, $from_date, $to_date);
			//$rep->AmountCol(7, 8, $unitCost_in,$dec);
			$unitCost_in *= $inward;
			$totval_in += $unitCost_in;
			//$rep->AmountCol(8, 9, $unitCost_in);
		//}
		
		if($outward>0){
			//$rep->AmountCol(9, 10, $outward, get_qty_dec($myrow['stock_id']));
			$unitCost_out =	trans_qty_unit_cost($myrow['stock_id'], $location, $from_date, $to_date, false);
			//$rep->AmountCol(10, 11, $unitCost_out,$dec);
			$unitCost_out *= $outward;
			$totval_out += $unitCost_out;
			//$rep->AmountCol(11, 12, $unitCost_out);
		}
		
		$rep->AmountCol(8, 9, $qoh_end, get_qty_dec($myrow['stock_id']));
		$rep->AmountCol(9, 10, $unitCost,$dec);
		$unitCost *= $qoh_end;
		$totval_close += $unitCost;
		$rep->AmountCol(10, 11, $unitCost,$dec);
		
		$rep->NewLine(0, 1);
	}
	$rep->Line($rep->row  - 4);
	$rep->NewLine(2);
	$rep->TextCol(1, 2,	_("*** Sub Total ** CA"));
	//$rep->AmountCol(5, 6, $totval_open);
	//$rep->AmountCol(8, 9, $totval_in);
	//$rep->AmountCol(11, 12, $totval_out);
	//$rep->AmountCol(10, 11, $totval_open + $totval_in - $totval_out);
	$rep->AmountCol(10, 11, $totval_open + $totval_in - $totval_out,$dec);
	$rep->NewLine(1);
	$rep->Line($rep->row  - 4);
	//$rep->NewLine(2);
	$rep->Line($rep->row  - 4);
	$rep->NewLine(2);
	$rep->TextCol(0, 2,	_("Grand Total"));
	$rep->AmountCol(10, 11, $totval_close,$dec);
	$rep->Line($rep->row  - 4);
	$rep->NewLine(2);
	$rep->TextCol(0, 1,	_("E.O.R"));
	

    $rep->End();
}

