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

function group_masters_by_suppliers($masters) {
    $data = array();
    foreach ($masters as $master) {
        if (!isset($data[$master['supp_name']])) {
            $data[$master['supp_name']] = array();
        }
        $data[$master['supp_name']][] = $master;
    }

    return $data;
}

function get_masters($from, $to) {

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
    $supplier_table_name = TB_PREF.'suppliers';

    $sql = <<<EOD
    SELECT order_no, ord_date, CONCAT(s.supp_code, " - ", s.supp_name) AS supp_name, s.curr_code, total, final_discount_amount
        FROM `$purchase_order_table_name` AS o
        LEFT JOIN `$supplier_table_name` AS s ON s.supplier_id = o.supplier_id
        WHERE ord_date BETWEEN '$from' AND '$to' AND trans_type=18
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
	

    $cols = array(0, 50, 150, 240, 280, 350, 420, 500, 570 );
    $headers = array(
        _('PO No.'),
        _('PO Dt.'),
        _('Reference'),
        _('Crncy'),
        _('Gross Amount'),
        _('Discount'),
        _('Net Amt'),
        _('Request No.'),
    );
    $aligns = array('left', 'left', 'left', 'left', 'right', 'right', 'right', 'right');

    $cols2 = array(0, 520);
    $headers2 = array('');
    $aligns2 = array('left');


    // $params =   array( 0 => $comments);
    $params = array('');


    $rep = new FrontReport(_('Supplierwise Inventory PO Summary For The Period '.$from.' - '.$to), 
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
	
    $masters = get_masters($from, $to);
    $grouped_masters = group_masters_by_suppliers($masters);
    $cur = get_company_Pref('curr_default');
    
    
    foreach ($grouped_masters as $supplier => $masters) {
        $rep->Font('BU');
        $rep->fontSize += 2;
        $rep->TextCol2(0, 1, trim($supplier));
        $rep->fontSize -= 2;
        $rep->Font();
        $rep->newline(1, 0, 15);

        $supplier_total = 0;
        foreach ($masters as $master) {
            
            $rep->TextCol(0, 1, $master['order_no']);
            $rep->TextCol(1, 2, implode('-', array_reverse(explode('-', $master['ord_date']))));
            $rep->TextCol(2, 3, '');
            $rep->TextCol(3, 4, $master['curr_code']);
            $rep->AmountCol(4, 5, $master['total'], $dec);
            $rep->AmountCol(5, 6, $master['final_discount_amount'], $dec);
            $rep->AmountCol(6, 7, $master['total'], $dec);
            $rep->newline(1, 0, 15);

            $supplier_total += $master['total'];
        }
        $rep->Font('B');
        $rep->TextCol(6, 7, str_pad('', 15, '_'));
        $rep->newline();
        $rep->AmountCol(6, 7, $supplier_total, $dec);
        $rep->Font();
        $rep->newline(1, 0, 15);
    }

    // $rep->Font('B');
    // $rep->fontSize += 2;
    // $rep->TextCol2(1, 2, 'Total: '.$cur);
    // $rep->Amountcol2(2, 3, $total_amount, $dec);
    // $rep->fontSize -= 2;
    // $rep->Font();

    $rep->End();

}