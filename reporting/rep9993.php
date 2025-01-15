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

function group_items($items, $master_hashmap) {

    $data = array();

    foreach ($items as $item) {
        $cat = $item['category'];
        $code = $item['item_code'].' - '.$item['description'];

        if (!isset($data[$cat])) {
            $data[$cat] = array();
        }

        if (!isset($data[$cat][$code])) {
            $data[$cat][$code] = array();
        }

        $master = $master_hashmap[$item['order_no']];
        $data[$cat][$code][] = array(
            'po_date' => implode('-', array_reverse(explode('-', $master['ord_date']))),
            'po_no' => $master['order_no'],
            'supplier' => $master['supp_code'].' - '.$master['supp_name'],
            'units' => 'PCS',
            'rate' => $item['unit_price'],
            'ordered_qty' => $item['quantity_ordered'],
            'received_qty' => $item['quantity_received'],
            'balance_qty' => $item['quantity_balance'],
            'amount' => $item['amount']
        );
    }

    return $data;
}

function get_purchase_order_items($master_ids) {
    $joined_master_ids = implode(',', $master_ids);

    $detail_table_name = TB_PREF.'purch_order_details';
    $item_codes_table_name = TB_PREF."item_codes";
    $stock_category_table_name = TB_PREF."stock_category";

    $sql = <<<EOD
        SELECT order_no, cat.description AS category, od.item_code, od.description, unit_price, quantity_ordered, quantity_received,
        CASE
            WHEN quantity_ordered - quantity_received < 0 THEN 0
            ELSE quantity_ordered - quantity_received
            END AS quantity_balance,
        unit_price * quantity_ordered AS amount
        FROM `$detail_table_name` AS od
        INNER JOIN `$item_codes_table_name` AS itm ON itm.item_code = od.item_code
        INNER JOIN `$stock_category_table_name` AS cat ON cat.category_id = itm.category_id
        WHERE order_no IN ($joined_master_ids) AND trans_type=18;
    EOD;

    $result = db_query($sql, "The customer transactions could not be retrieved");

    $po_items = array();
    while ($myrow = db_fetch_assoc($result)) {
        $po_items[] = $myrow;
    }
    return $po_items;
}

function group_items_by_master($items) {
    $grouped_items = array();

    foreach ($items as $itm) {
        $order_no = $itm['order_no'];
        $category = $itm['category'];
        $item_code = $itm['item_code'];
        $description = $itm['description'];
        $unit_price = $itm['unit_price'];
        $quantity_ordered = $itm['quantity_ordered'];
        $quantity_received = $itm['quantity_received'];
        $quantity_balance = $itm['quantity_balance'];
        $amount = $itm['amount'];


        if (!isset($grouped_items[$order_no])) {
            $grouped_items[$order_no] = array();
        }
        $grouped_items[$order_no][] = array(
            'item_code' => $item_code,
            'description' => $description,
            'unit_price' => $unit_price,
            'quantity_ordered' => $quantity_ordered,
            'quantity_received' => $quantity_received,
            'quantity_balance' => $quantity_balance,
            'amount' => $amount
        );
    }
    return $grouped_items;
}

function get_masters($from, $to) {

    $purchase_order_table_name = TB_PREF.'purch_orders';
    $supplier_table_name = TB_PREF.'suppliers';
    $payment_terms_table_name = TB_PREF.'payment_terms';

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

    $sql = <<<EOD
        SELECT order_no, ord_date, s.supp_code, s.supp_name, s.curr_code, total, final_discount_amount, p.terms
        FROM `$purchase_order_table_name` AS o
        LEFT JOIN `$supplier_table_name` AS s ON s.supplier_id = o.supplier_id
        LEFT JOIN `$payment_terms_table_name` AS p ON p.terms_indicator = s.payment_terms
        WHERE ord_date BETWEEN '$from' AND '$to' AND trans_type=18
        
    EOD;

    $result = db_query($sql, "The master could not be retrieved");

    $masters = array();
    while ($myrow = db_fetch_assoc($result)) {
        $masters[] = $myrow;
    }
    return $masters;
}

function get_master_ids($masters) {
    $xyz = array();
    foreach ($masters as $master) {
        $xyz[] = $master['order_no'];
    }
    return $xyz;
}

function get_hashmap_of_masters($masters) {
    $data = array();
    foreach ($masters as $master) {
        $data[$master['order_no']] = $master;
    }
    return $data;
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
	
   
   $cols = array(0, 600);

   $headers = array(
        _('Item Code & Name'),
    );
    $aligns = array('left');

    $cols2 = array(0, 50, 100, 310, 340, 375, 425, 475, 520, 580, 600);
    $headers2 = array(
        _('PO Dt.'),
        _('PO No.'), 
        _('Supplier Code & Name'),
        _('Units'),
        _('Rate'),
        _('Ord. Qty'),
        _('Recd. Qty'),
        _('Bal. Qty'),
        _('Amount'),
    );
    $aligns2 = array('left', 'center', 'left', 'left', 'right', 'right', 'right', 'right', 'right');


    // $params =   array( 0 => $comments);
    $params = array('');


    $rep = new FrontReport(_('Itemwise Inventory Purchase Order Register For The Period '.$from.' - '.$to), 
	"ItemwiseInventoryPOReportSummary", user_pagesize(), 9, $orientation);

    $rep->leftMargin = 5;
    $rep->rightMargin = 5;
    $rep->topMargin = 20;
    $rep->bottomMargin = 20;
	
    if ($orientation == 'L')
    	recalculate_cols($cols);

    
    $rep->Font();
    $rep->Info($params, $cols2, $headers2, $aligns2, $cols, $headers, $aligns);
    $rep->NewPage();
	
	$po_masters = get_masters($from, $to);
    $po_db_ids = get_master_ids($po_masters);
    $po_items = get_purchase_order_items($po_db_ids);
    $master_hashmap = get_hashmap_of_masters($po_masters);
    $data = group_items($po_items, $master_hashmap);
    
    foreach ($data as $category => $items) {
        $rep->Font('BU');
        $rep->fontSize += 2;
        $rep->TextCol2(0, 1, trim($category));
        $rep->fontSize -= 2;
        $rep->Font();
        $rep->newline(1, 0, 15);

        foreach ($items as $item => $pos) {
            $rep->newline(1, 0, 5);
            $rep->Font('BU');
            $rep->TextCol2(0, 1, $item);
            $rep->Font();
            $rep->newline(1, 0, 15);

            $sub_total = 0;
            foreach ($pos as $po) {
                $sub_total += $po['amount'];
                $rep->TextCol(0, 1, $po['po_date']);
                $rep->TextCol(1, 2, $po['po_no']);
                $rep->TextCol(2, 3, $po['supplier']);
                $rep->TextCol(3, 4, $po['units']);
                $rep->AmountCol(4, 5, $po['rate'], $dec);
                $rep->AmountCol(5, 6, $po['ordered_qty'], $dec);
                $rep->AmountCol(6, 7, $po['received_qty'], $dec);
                $rep->AmountCol(7, 8, $po['balance_qty'], $dec);
                $rep->AmountCol(8, 9, $po['amount'], $dec);
                $rep->newline(1, 0, 15);
            }
            $rep->Line($rep->row, 0);
            $rep->newline(1, 0, 15);
            $rep->SetFont('helvetica', 'B', 9);
            $rep->TextCol(7, 8, 'Sub Total');
            $rep->AmountCol(8, 9, $sub_total, $dec);
            $rep->Font();
            $rep->newline(1, 0, 1);
        }
    }

    $rep->End();

}