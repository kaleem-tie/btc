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
$page_security = 'SA_DEB_OUT_REP';

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
include_once($path_to_root . "/purchasing/includes/db/suppliers_db.inc");

//---------------------------------------------------------------------------------

print_debtors_outstanding_report();

function group_items_by_category($items) {
    $data = array();
    foreach ($items as $item) {
        if (!isset($data[$item['category']])) {
            $data[$item['category']] = array();
        }
        $data[$item['category']][] = $item;
    }

    return $data;
}

function get_summed_items($from, $to) {

    if ($from == null) {
        $from = date('Y-m-d');
    }
    else {
        $from = date2sql($from);
    }

	if ($to == null)
		$to = date("Y-m-d");
	else
		$to = date2sql($to);

    $purchase_order_table_name = TB_PREF.'purch_orders';
    $detail_table_name = TB_PREF.'purch_order_details';
    $item_codes_table_name = TB_PREF.'item_codes';
    $stock_category_table_name = TB_PREF.'stock_category';
    $stock_master_table_name = TB_PREF.'stock_master';

    $sql = <<<EOD
        WITH
        overall
        AS (
            SELECT cat.description AS category, od.item_code, od.description, stk.units AS unit, quantity_ordered, quantity_received,
                quantity_ordered - quantity_received AS quantity_balance,
                unit_price * quantity_ordered AS amount
                FROM `$detail_table_name` AS od
                INNER JOIN `$item_codes_table_name` AS itm ON itm.item_code = od.item_code
                INNER JOIN `$stock_category_table_name` AS cat ON cat.category_id = itm.category_id
                INNER JOIN `$stock_master_table_name` AS stk ON stk.stock_id = od.item_code
                WHERE order_no IN (SELECT order_no FROM $purchase_order_table_name WHERE ord_date BETWEEN '$from' AND '$to') AND trans_type=18
        )
        SELECT category, item_code, description, unit, SUM(quantity_ordered) AS quantity_ordered, SUM(quantity_received) AS quantity_received,
        SUM(quantity_balance) AS quantity_balance, SUM(amount) AS amount
        FROM overall
        GROUP BY category, item_code, description, unit
    EOD;

    $result = db_query($sql, "The customer transactions could not be retrieved");

    $summed_items = array();
    while ($myrow = db_fetch_assoc($result)) {
        $summed_items[] = $myrow;
    }
    return $summed_items;
}


//---------------------------------------------------------------------------------
function print_debtors_outstanding_report()
{
    global $path_to_root, $systypes_array;

    $from        = $_POST['PARAM_0'];
    $to          = $_POST['PARAM_1'];
    $fromcust    = $_POST['PARAM_2'];
    $no_zeros    = $_POST['PARAM_3'];
    $orientation = $_POST['PARAM_4'];
    $destination = $_POST['PARAM_5'];
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	
    $orientation = ($orientation ? 'L' : 'P');
    
    $dec = user_price_dec();
	

    $cols = array(0, 50, 260, 290, 360, 420, 490, 570);
    $headers = array(
        _('Item Code'),
        _('Description'), 
        _('Units'),
        _('Ord. Quantity'),
        _('Recd Qty'),
        _('Balance Qty'),
        _('Amount'),
    );
    $aligns = array('left', 'left', 'left', 'right', 'right', 'right', 'right');

    $cols2 = array(0, 100, 490, 570);
    $headers2 = array('', '', '');
    $aligns2 = array('left', 'right', 'right');


    // $params =   array( 0 => $comments);
    $params = array('');


    $rep = new FrontReport(_('Itemwise Inventory Purchase Order Summary For The Period '.$from.' - '.$to), 
	"ItemwiseInventoryPOReportSummary", user_pagesize(), 9, $orientation);

    $rep->leftMargin = 10;
    $rep->rightMargin = 10;
    $rep->topMargin = 20;
    $rep->bottomMargin = 20;
	
    if ($orientation == 'L')
    	recalculate_cols($cols);

    
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
    $rep->NewPage();
	
	$items = get_summed_items($from, $to);
    $grouped_items = group_items_by_category($items);
    $cur = get_company_Pref('curr_default');
    
    $total_amount = 0;
    foreach ($grouped_items as $category => $items) {
        $rep->Font('BU');
        $rep->fontSize += 2;
        $rep->TextCol2(0, 1, trim($category));
        $rep->fontSize -= 2;
        $rep->Font();
        $rep->newline(1, 0, 15);

        foreach ($items as $item) {
            
            $rep->TextCol(0, 1, $item['item_code']);
            $rep->TextCol(1, 2, $item['description']);
            $rep->TextCol(2, 3, $item['unit']);
            $rep->TextCol(3, 4, $item['quantity_ordered']);
            $rep->AmountCol(4, 5, $item['quantity_received'], $dec);
            $rep->AmountCol(5, 6, $item['quantity_balance'], $dec);
            $rep->AmountCol(6, 7, $item['amount'], $dec);
            $rep->newline(1, 0, 15);

            $total_amount += $item['amount'];
        }

        $rep->newline(1, 0, 5);
    }

    $rep->Font('B');
    $rep->fontSize += 2;
    $rep->TextCol2(1, 2, 'Total: '.$cur);
    $rep->Amountcol2(2, 3, $total_amount, $dec);
    $rep->fontSize -= 2;
    $rep->Font();

    $rep->End();

}