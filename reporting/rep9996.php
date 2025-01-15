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

function get_purchase_order_items($master_ids) {
    $joined_master_ids = implode(',', $master_ids);

    $detail_table_name = TB_PREF.'purch_order_details';
    $item_codes_table_name = TB_PREF."item_codes";
    $stock_category_table_name = TB_PREF."stock_category";
    $stock_master_table_name = TB_PREF."stock_master";

    $sql = <<<EOD
        SELECT order_no, cat.description AS category, od.item_code, od.description, stk.units AS unit, unit_price, unit_price * quantity_ordered AS amount, quantity_ordered, quantity_received,
        CASE
            WHEN quantity_ordered - quantity_received < 0 THEN 0
            ELSE quantity_ordered - quantity_received
            END AS quantity_balance
        FROM `$detail_table_name` AS od
        INNER JOIN `$item_codes_table_name` AS itm ON itm.item_code = od.item_code
        INNER JOIN `$stock_category_table_name` AS cat ON cat.category_id = itm.category_id
        INNER JOIN `$stock_master_table_name` AS stk ON stk.stock_id = od.item_code
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
        $item_code = $itm['item_code'];
        $description = $itm['description'];
        $unit_price = $itm['unit_price'];
        $unit = $itm['unit'];
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
            'unit' => $unit,
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
        SELECT order_no, ord_date, CONCAT(s.supp_code, ' - ', s.supp_name) AS supp_name, s.curr_code, total, final_discount_amount
        FROM `$purchase_order_table_name` AS o
        LEFT JOIN `$supplier_table_name` AS s ON s.supplier_id = o.supplier_id
        WHERE ord_date BETWEEN '$from' AND '$to' AND trans_type=18
        
    EOD;

    $result = db_query($sql, "The master could not be retrieved");

    $masters = array();
    while ($myrow = db_fetch_assoc($result)) {
        $masters[] = $myrow;
    }
    return $masters;
}

function group_masters_by_supplier($masters) {
    $data = array();
    foreach ($masters as $master) {
        if (!isset($data[$master['supp_name']])) {
            $data[$master['supp_name']] = [];
        }
        $data[$master['supp_name']][] = $master;
    }
    return $data;
}

function get_master_ids($masters) {
    $xyz = array();
    foreach ($masters as $master) {
        $xyz[] = $master['order_no'];
    }
    return $xyz;
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

	
    $orientation = ($orientation ? 'L' : 'L');
	
   
   $cols = array(10, 60, 280, 340, 380, 440, 500, 560
);
   $headers = array(
        _('PO No.'),
        _('PO Date'), 
        _('Reference'),
        _('Crncy'),
        _('Gross Amount'),
        _('Discount'),
        _('Net Amt')
    );
    $aligns = array('left',	'left', 'left', 'left', 'right', 'right', 'right');

    // $cols2 = $cols;
    $cols2 = array(20, 85, 300, 350, 390, 440, 540, 625, 680);
    $headers2 = array(
        _('Item Code'),
        _('Item Description'), 
        _('Units'),
        _('Ord. Qty'),
        _('Rate'),
        _('Item Amt'),
        _('Recd Qty'),
        _('Bal Qty'),
    );
    // $aligns2 = $aligns;
    $aligns2 = array('left', 'left', 'left', 'right', 'right', 'right', 'right', 'right');


    // $params =   array( 0 => $comments);
    $params = array('');


    $rep = new FrontReport(_('Supplierwise Inventory PO Register For The Period '.$from.' - '.$to), 
	"ItemwiseInventoryPOReportSummary", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    // $rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
    $rep->Info($params, $cols2, $headers2, $aligns2, $cols, $headers, $aligns);
    //$rep->SetHeaderType('Header42');
    $rep->NewPage();

    $dec = user_price_dec();

    $masters = get_masters($from, $to);
    $grouped_masters = group_masters_by_supplier($masters);
    $po_db_ids = get_master_ids($masters);
    $po_items = get_purchase_order_items($po_db_ids);
    $items_grouped_by_master = group_items_by_master($po_items);

    foreach ($grouped_masters as $supplier => $po_masters) {
        // echo $supplier;
        $rep->Font('BU');
        $rep->fontSize += 1;
        $rep->Text($ccol+35, $supplier, 0, 0, 0, 'left');
        $rep->Font();
        $rep->fontSize -= 1;
        $rep->NewLine();

        $supplier_amount = 0;
        foreach ($po_masters as $myrow) {

            $supplier_amount += $myrow['total'];

            $rep->TextCol2(0, 1, $myrow['order_no']);
            $rep->TextCol2(1, 2, implode('-', array_reverse(explode('-', $myrow['ord_date']))));
            
            $rep->TextCol2(2, 3, '');
            $rep->TextCol2(3, 4, $myrow['curr_code']);
            $rep->AmountCol2(4, 5, $myrow['total'], $dec);
            $rep->AmountCol2(5, 6, $myrow['final_discount_amount'], $dec);
            $rep->AmountCol2(6, 7, $myrow['total'], $dec);
            $rep->NewLine();

            // print_r($items_grouped_by_master);
            $po_items = $items_grouped_by_master[$myrow['order_no']];
            foreach ($po_items as $item) {
                $rep->SetTextColor(220, 38, 37);
                $rep->TextCol(0, 1, $item['item_code']);
                $rep->TextCol(1, 2, $item['description']);
                $rep->TextCol(2, 3, $item['unit']);
                $rep->AmountCol(3, 4, $item['quantity_ordered'], $dec);
                $rep->AmountCol(4, 5, $item['unit_price'], $dec);
                $rep->AmountCol(5, 6, $item['amount'], $dec);
                $rep->AmountCol(6, 7, $item['quantity_received'], $dec);
                $rep->AmountCol(7, 8, $item['quantity_balance'], $dec);
                $rep->SetTextColor(0, 0, 0);
                $rep->NewLine();

                // $rep->AmountCol(4, 5, $master['total'], $dec);
            }

            
        }

        $rep->Text($ccol+40, str_pad('', 500, '-'));
        $rep->NewLine();
        $rep->fontSize += 1;
        $rep->Font('B');
        $rep->AmountCol2(6, 7, $supplier_amount, $dec);
        $rep->Font();
        $rep->fontSize -= 1;
        $rep->NewLine();

        
        
        // $my_items = $items_grouped_by_master[$myrow['order_no']];
        // foreach ($my_items as $my_item) {
        //     $rep->SetTextColor(220, 38, 37);
        //     $rep->TextCol(0, 1, $my_item['item_code']);
        //     $rep->TextCol(1, 2, $my_item['description']);
        //     $rep->TextCol(2, 3, 'PCS');
        //     $rep->TextCol(3, 4, $my_item['quantity_ordered']);
        //     $rep->TextCol(4, 5, $my_item['unit_price']);
        //     $rep->TextCol(5, 6, $my_item['quantity_ordered'] * $my_item['unit_price']);
        //     $rep->SetTextColor(0, 0, 0);
        //     $rep->NewLine();
        // }
        
        // // $rep->NewLine();
        // $rep->TextCol2(0, 1, 'Payment Terms:');
        // $rep->TextCol2(1, 2, $myrow['terms']);

        // $rep->SetTextColor(0, 0, 0);
        // $rep->NewLine();
        // $rep->Text($ccol+ $rep->leftMargin, str_pad('', 500, '.'));
        // $rep->NewLine();

    }


    $rep->End();

}