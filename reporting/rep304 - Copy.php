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
$page_security = 'SA_SALESANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Inventory Sales Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

//----------------------------------------------------------------------------------------------------

print_inventory_sales();

function getTransactions($category, $location, $fromcust, $from, $to, $show_service, $fromsupp = ALL_TEXT)
{
	$from = date2sql($from);
	$to = date2sql($to);

	$sql = "SELECT item.category_id,
			category.description AS cat_description,
			item.stock_id,
			item.description, item.inactive,
			item.mb_flag,
			move.loc_code,
			trans.debtor_no,
			debtor.name AS debtor_name,
			move.tran_date,
			SUM(-move.qty) AS qty,
			SUM(-move.qty*move.price) AS amt,
			SUM(-IF(move.standard_cost <> 0, move.qty * move.standard_cost, move.qty *item.material_cost)) AS cost
		FROM ".TB_PREF."stock_master item,
			".TB_PREF."stock_category category,
			".TB_PREF."debtor_trans trans,
			".TB_PREF."debtors_master debtor,
			".TB_PREF."stock_moves move
		WHERE item.stock_id=move.stock_id
		AND item.category_id=category.category_id
		AND trans.debtor_no=debtor.debtor_no
		AND move.type=trans.type
		AND move.trans_no=trans.trans_no
		AND move.tran_date>='$from'
		AND move.tran_date<='$to'
		AND (trans.type=".ST_CUSTDELIVERY." OR move.type=".ST_CUSTCREDIT.")";

	if (!$show_service)
		$sql .= " AND (item.mb_flag='B' OR item.mb_flag='M')";
	else
		$sql .= " AND item.mb_flag<>'F'";
	if ($category != 0)
		$sql .= " AND item.category_id = ".db_escape($category);

	if ($location != '')
		$sql .= " AND move.loc_code = ".db_escape($location);

	if ($fromcust != '')
		$sql .= " AND debtor.debtor_no = ".db_escape($fromcust);
		
	if ($fromsupp != ALL_TEXT)
		$sql .= " AND item.supplier_id=".db_escape($fromsupp);
	
	$sql .= " GROUP BY item.stock_id, debtor.name ORDER BY item.category_id,
		item.stock_id, debtor.name";

    return db_query($sql,"No transactions were returned");

}

//----------------------------------------------------------------------------------------------------

function print_inventory_sales()
{
    global $path_to_root;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
    $category = $_POST['PARAM_2'];
    $location = $_POST['PARAM_3'];
    $fromcust = $_POST['PARAM_4'];
    $fromsupp = $_POST['PARAM_5'];
	$show_service = $_POST['PARAM_6'];
	$summary = $_POST['PARAM_7'];
	$comments = $_POST['PARAM_8'];
	$orientation = $_POST['PARAM_9'];
	$destination = $_POST['PARAM_10'];
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

	if ($location == '')
		$loc = _('All');
	else
		$loc = get_location_name($location);

	if ($fromcust == '')
		$fromc = _('All');
	else
		$fromc = get_customer_name($fromcust);
	
	if ($show_service) $show_service_items = _('Yes');
	else $show_service_items = _('No');
	
	if ($summary) $show_summary = _('Yes');
	else $show_summary = _('No');
	
	if ($fromsupp == ALL_TEXT)
		$supp = _('All');
	else
		$supp = get_supplier_name($fromsupp);
	$cols = array(0, 45, 195, 270, 320, 370, 420,470,520);

	$headers = array(_('Category'), _('Description'), _('Customer'), _('Qty'), _('Sales'), _('Cost'), _('Profit'),_('Profit %'));
	if ($fromcust != '')
		$headers[2] = '';

	$aligns = array('left',	'left',	'left', 'right', 'right', 'right', 'right', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
    				    3 => array('text' => _('Location'), 'from' => $loc, 'to' => ''),
    				    4 => array('text' => _('Customer'), 'from' => $fromc, 'to' => ''),
    				    5 => array('text' => _('Show Service Items'), 'from' => $show_service_items, 'to' => ''),
						6 => array('text' => _('Show Summary'), 'from' => $show_summary, 'to' => ''),
						7 => array('text' => _('Supplier'), 'from' => $supp, 'to' => ''));

    $rep = new FrontReport(_('Item Wise Profitability  Report'), "ItemWiseProfitabilityReport", user_pagesize(), 9, $orientation);
   	if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$res = getTransactions($category, $location, $fromcust, $from, $to, $show_service, $fromsupp);
	$total = $total_qty = $grandtotal = 0.0;
	$total1 = $grandtotal1 = 0.0;
	$total2 = $grandtotal2 = 0.0;
	
	$itotal = $itotal_qty = 0.0;
	$itotal1 = 0.0;
	$itotal2 = 0.0;
	$catt = '';
	$stk_id = '';
	$stk_desc = '';
	$profit_percent=0.0;
	while ($trans=db_fetch($res))
		{
			
			if($stk_id !='' && $stk_id != $trans['stock_id'] && $summary==1)
			{
				
				
					$rep->NewLine();
					$rep->TextCol(0, 1, $stk_id);
					$rep->TextCol(1, 2, $stk_desc);
					$rep->AmountCol(3, 4, $itotal_qty, $dec);
							$rep->AmountCol(4, 5, $itotal, $dec);
							if (is_service($trans['mb_flag']))
							$rep->TextCol(5, 6, "---");
							else	
							$rep->AmountCol(5, 6, $itotal1, $dec);
							$rep->AmountCol(6, 7, $itotal2, $dec);
						 if ($itotal1>0 && $itotal1!=0)
							   $profit_percent = (($itotal2/ $itotal1)*100);
							$rep->AmountCol(7, 8, $profit_percent, $dec);
					
						$itotal = $itotal_qty = $itotal1 = $itotal2 = 0.0;
					$stk_id = $trans['stock_id'];
					$stk_desc = $trans['description'];
						$rep->NewLine();
			}
			$stk_id = $trans['stock_id'];
			$stk_desc = $trans['description'];
			if ($catt != $trans['cat_description'])
			{
				if ($catt != '')
				{
					$rep->NewLine(2, 3);
					$rep->TextCol(0, 4, _('Total'));
					$rep->AmountCol(4, 5, $total, $dec);
					$rep->AmountCol(5, 6, $total1, $dec);
					$rep->AmountCol(6, 7, $total2, $dec);
					$rep->Line($rep->row - 2);
					$rep->NewLine();
					$rep->NewLine();
					$total = $total1 = $total2 = 0.0;
				}
				$rep->TextCol(0, 1, $trans['category_id']);
				$rep->TextCol(1, 6, $trans['cat_description']);
				$catt = $trans['cat_description'];
				$rep->NewLine();
			}

			$curr = get_customer_currency($trans['debtor_no']);
			$rate = get_exchange_rate_from_home_currency($curr, sql2date($trans['tran_date']));
			$trans['amt'] *= $rate;
			$cb = $trans['amt'] - $trans['cost'];
			if($summary==0){
			$rep->NewLine();
			$rep->fontSize -= 2;
						
			$rep->TextCol(0, 1, $trans['stock_id']);
			if ($fromcust == ALL_TEXT)
			{
				$rep->TextCol(1, 2, $trans['description'].($trans['inactive']==1 ? " ("._("Inactive").")" : ""), -1);
				$rep->TextCol(2, 3, $trans['debtor_name']);
			}
			else
				$rep->TextCol(1, 3, $trans['description'].($trans['inactive']==1 ? " ("._("Inactive").")" : ""), -1);
			$rep->AmountCol(3, 4, $trans['qty'], get_qty_dec($trans['stock_id']));
			$rep->AmountCol(4, 5, $trans['amt'], $dec);
			if (is_service($trans['mb_flag']))
				$rep->TextCol(5, 6, "---");
			else	
				$rep->AmountCol(5, 6, $trans['cost'], $dec);
			$rep->AmountCol(6, 7, $cb, $dec);
				 if ($trans['cost']>0 && $trans['cost']!=0)
				   $profit_percent = (($cb/ $trans['cost'])*100);
			  
			$rep->AmountCol(7, 8, $profit_percent, $dec);
			
			$rep->fontSize += 2;
			}
			$total += $trans['amt'];
			$total1 += $trans['cost'];
			$total2 += $cb;
			$grandtotal += $trans['amt'];
			$grandtotal1 += $trans['cost'];
			$grandtotal2 += $cb;
			
			$trans['amt'] *= $rate;
				$icb = $trans['amt'] - $trans['cost'];
				$itotal += $trans['amt'];
				$itotal1 += $trans['cost'];
				$itotal2 += $icb;
				$itotal_qty +=$trans['qty'];
		}
		
		// last item
			if($summary ==1){
				$rep->NewLine(2, 3);
						$rep->TextCol(0, 1, $stk_id);
						$rep->TextCol(1, 2, $stk_desc);
						$rep->AmountCol(3, 4, $itotal_qty, $dec);
							$rep->AmountCol(4, 5, $itotal, $dec);
							if (is_service($trans['mb_flag']))
							$rep->TextCol(5, 6, "---");
							else	
							$rep->AmountCol(5, 6, $itotal1, $dec);
							$rep->AmountCol(6, 7, $itotal2, $dec);
						 if ($itotal1>0 && $itotal1!=0)
							   $profit_percent = (($itotal2/ $itotal1)*100);
							$rep->AmountCol(7, 8, $profit_percent, $dec);
				}
		//End
		
		$rep->NewLine(2, 3);
	$rep->TextCol(0, 4, _('Total'));
	$rep->AmountCol(4, 5, $total, $dec);
	$rep->AmountCol(5, 6, $total1, $dec);
	$rep->AmountCol(6, 7, $total2, $dec);
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->NewLine(2, 1);
	$rep->TextCol(0, 4, _('Grand Total'));
	$rep->AmountCol(4, 5, $grandtotal, $dec);
	$rep->AmountCol(5, 6, $grandtotal1, $dec);
	$rep->AmountCol(6, 7, $grandtotal2, $dec);

	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();

	
}
