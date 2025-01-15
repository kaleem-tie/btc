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
$page_security = 'SA_INVENTORY_ITEM_WISE_PENDING_PURCHASE_ORDER_REPORT';  //'SA_PENDING_PO_ITEMS_REP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Chaitanya
// date_:	2005-05-19
// Title:	Sales Summary Report
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

function get_inv_supplier_name1($supplier_id)
{
	$sql = "SELECT supp_name from ".TB_PREF."suppliers where supplier_id=".db_escape($supplier_id);

	$result=db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	return $row[0];
}



function getTransactions($supplier,$category, $from, $to, $subcategory, $item,$currency_type)
{
	$from = date2sql($from);
	$to = date2sql($to);
	
	
	$home_curr = get_company_currency();
	
	$sql = "SELECT trans.reference,trans.ord_date,supplier.supp_name,item.category_id,
			category.description AS cat_description,
			item.stock_id,
			item.description,
			line.unit_price *(100-line.discount_percent)*(0.01) AS unit_price,
	SUM(line.quantity_ordered) as quantity_ordered,SUM(line.quantity_received) as quantity_received,trans.requisition_no,trans.into_stock_location,trans.order_no,supplier.supplier_id as trans_supplier_id,Sum((line.unit_price*line.quantity_ordered)-(line.unit_price*line.quantity_ordered*line.discount_percent/100)) AS OrderValue
		FROM ".TB_PREF."stock_master item,
			".TB_PREF."stock_category category,".TB_PREF."suppliers supplier,";
		
		if($subcategory!='all')
		{
			$sql.=TB_PREF."item_sub_category item_sb,";
		}	
		
			$sql.=TB_PREF."purch_orders trans,
			".TB_PREF."purch_order_details line
		WHERE line.item_code = item.stock_id
		AND item.category_id=category.category_id 
		AND line.order_no=trans.order_no
		AND trans.supplier_id=supplier.supplier_id
		AND trans.ord_date>='$from'
		AND trans.ord_date<='$to'
		AND line.quantity_ordered-line.quantity_received <> 0
		AND item.mb_flag <>'F'
		AND trans.po_auth_req = '2'";
		
		if ($category != 0)
			$sql .= " AND item.category_id = ".db_escape($category); 
		if ($subcategory != 'all')
		$sql .= " AND item.item_sub_category=item_sb.id AND item.item_sub_category = ".db_escape($subcategory);
	
	if ($supplier != 'all')
		$sql .= " AND item.supplier_id = supplier.supplier_id 
	              AND item.supplier_id = ".db_escape($supplier);

    if($item)
	   $sql .= "  AND item.stock_id = ".db_escape($item)."";
   
   if ($currency_type == '1'){
		$sql .= " AND supplier.curr_code =".db_escape($home_curr);
	}
	elseif ($currency_type == '2'){
		$sql .= " AND supplier.curr_code != ".db_escape($home_curr);
	}
		
	$sql .= " GROUP BY trans.order_no ORDER BY trans.supplier_id";
		
	//display_error($sql); die;
		
    return db_query($sql,"No transactions were returned");

}
function get_suppliers_currency($supplier_id){
	
	$sql = "SELECT curr_code FROM ".TB_PREF."suppliers WHERE supplier_id=".db_escape($supplier_id)."";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
	
}

function get_cancel_qty($order_no,$stock_id){
	$sql = "SELECT sum(quantity_ordered) as ord_qty FROM ".TB_PREF."cancel_purch_order_details WHERE order_no = ".db_escape($order_no)." AND item_code = ".db_escape($stock_id)." GROUP BY order_no";
	$res = db_query($sql);
    $result = db_fetch_row($res);
}

function get_cancel_qty_item_wise($order_no,$stock_id){
	$sql = "SELECT sum(quantity_ordered) as ord_qty FROM ".TB_PREF."cancel_purch_order_details WHERE order_no = ".db_escape($order_no)." AND item_code = ".db_escape($stock_id)." ";
	$res = db_query($sql);
    $result = db_fetch_row($res);
}

function get_order_item_info($order_no)
{
	$sql ="SELECT pi.*,m.units FROM ".TB_PREF."purch_order_details pi ,".TB_PREF."stock_master m WHERE order_no=".db_escape($order_no)." AND pi.item_code=m.stock_id  order by order_no";
	
	return $res=db_query($sql, "The transactions for invoice items not be retrieved");
}
//----------------------------------------------------------------------------------------------------

function print_inventory_sales()
{
    global $path_to_root,$systypes_array;

    $supplier        = $_POST['PARAM_0'];
	$from            = $_POST['PARAM_1'];
	$to              = $_POST['PARAM_2'];
    $category        = $_POST['PARAM_3'];
	$subcategory     = $_POST['PARAM_4'];
	$currency_type   = $_POST['PARAM_5'];
	$item            = $_POST['PARAM_6'];
	$comments        = $_POST['PARAM_7'];
	$reporttype      = $_POST['PARAM_8'];
	$orientation     = $_POST['PARAM_9'];
	$destination     = $_POST['PARAM_10'];
	
	
	//ravi
	$destination=1;
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
	
	if ($subcategory == ALL_TEXT)
		$subcategory = 'all';
	if ($subcategory == 'all')
		$subcat = _('All');
	else
		$subcat = get_stock_subcategory_name($subcategory);
	
	
	if ($supplier == ALL_TEXT)
		$supplier = 'all';
	if ($supplier== 'all')
		$sup = _('All');
	else
		$sup = get_inv_supplier_name1($supplier);
	
	
	if($currency_type == '0'){
       $cur_type = "All";
    } elseif($currency_type == '1'){
       $cur_type = "Local";
    }elseif($currency_type == '2'){
       $cur_type = "Foreign";
    }

	
	if($reporttype == 0){
		$report_type = 'Summary';
		
	}else{
		$report_type = 'Detail';
	}

	$cols = array(0, 50, 90, 130,240,320,425,450,500,550,600,650,700,750,800,850);

	$headers = array(_('Br'),_('PO No'),_('Vr Date'),_('PO Currency'),_('Rate'),_('Our Ref No'),_('Our Ref Date'), _('Supplier No'), _('Supplier Date'), _('Supplier EDD'), _('PO Qty'), _('GRN Qty'), _('Cancelled Qty'), _('Balance'), _('OrderValue'));	

	$aligns = array('left',	'left','left','left','left','left','right','left','left', 'right', 'right','right', 'right', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
                        2 => array('text' => _('Supplier'), 'from' => $sup, 'to' => ''),
    				    3 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
						4 => array('text' => _('Sub Category'), 'from' => $subcat, 'to' => ''),
						5 => array('text' => _('Currency Type'), 'from' => $cur_type, 'to' => ''),
						6 => array('text' => _('Report Type'), 'from' => $report_type, 'to' => ''));

    $rep = new FrontReport(_('Item wise Pending Purchase Order Report'), "ItemWisePendingPurchaseReport", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();
// if($report_type == 'Summary'){
	$res = getTransactions($supplier,$category, $from, $to ,$subcategory, $item,$currency_type);
	
	$total = $grandtotal =$outstanding_value=0.0;
	$catt = '';
	$supplier_id = '';
	while ($trans=db_fetch($res))
	{
		
		 if ($supplier_id != $trans['supp_name'])
		{
			if ($supplier_id != '')
			{
				
				$rep->NewLine();
				$rep->TextCol(13, 14, _('Total in Supplier Currency'));
				$rep->AmountCol(14,15, $total, $dec);
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$total = 0.0;
				
			}
			
			$rep->formatTitle->setBold();
			$rep->fontSize += 6;
			$rep->TextCol(0, 2, 'Supplier Name');
			$rep->TextCol(2, 9, $trans['supp_name']);
			$rep->fontSize -= 6;
			$rep->Font();
			$supplier_id = $trans['supp_name'];
		}
		 $rep->NewLine();
		$rep->TextCol(0, 1, $trans['into_stock_location']);
		$rep->TextCol(1, 2, $trans['reference']);
		$rep->TextCol(2, 3, sql2date($trans['ord_date']));
		$rep->TextCol(3, 4, get_suppliers_currency($trans['trans_supplier_id']));
		$currency = get_suppliers_currency($trans['trans_supplier_id']);
		$rate = round2(get_exchange_rate_from_home_currency($currency, sql2date($trans['ord_date'])),
	    user_exrate_dec());
		$rep->AmountCol(4, 5, $rate,user_exrate_dec());
		$rep->TextCol(5, 6, $trans['our_ref_no']);
		$rep->TextCol(6, 7, sql2date($trans['our_ref_date']));
		$rep->TextCol(7, 8, $trans['requisition_no']);
		$rep->TextCol(8, 9, sql2date($trans['supplier_date']));
		$rep->TextCol(9, 10, sql2date($trans['e_date']));
		$rep->TextCol(10,11,'');
		$rep->TextCol(11,12,'');
		$rep->TextCol(12,13,'');
		$rep->TextCol(13,14,'');
		//$cancel_qty = get_cancel_qty($trans['order_no'],$trans['stock_id']);
		
		$cancel_qty = 0;
		
		$balance_qty =  $trans['quantity_ordered']-($trans['quantity_received']+$cancel_qty);
	
		 $rep->AmountCol(14, 15, $trans['OrderValue'],$dec);
		$total += $trans['OrderValue'];
		$grandtotal += $trans['OrderValue']*$rate;
		//$rep->NewLine();
		
		if($report_type == 'Detail'){
		 $order_items = get_order_item_info($trans['order_no']);
		//items while start
		$rep->NewLine();
		$rep->Font('bold');
			$rep->TextCol(2, 3, _('Item Code'));
			$rep->TextCol(3, 4, _('Item Description'));
			$rep->TextCol(4, 5, _('Quantity'));
			$rep->TextCol(5, 6, _('Unit'));
			$rep->TextCol(6, 7, _('Price'));
			$rep->TextCol(7, 8, _('Quantity Received'));
			$rep->TextCol(8, 9, _('Quantity Invoiced'));
			$rep->TextCol(9, 10, _('Quantity Cancelled'));
			$rep->TextCol(10, 11, _('Quantity Balanced'));
			$rep->TextCol(11, 12, _('Balanced Amount'));
		    $rep->Font();
		   $rep->NewLine();
			while($order_result = db_fetch($order_items)){
				//display_error(json_encode($order_result));
				$rep->TextCol(2, 3, $order_result['item_code']);
				$rep->TextCol(3, 4, $order_result['description']);
				$rep->AmountCol(4, 5, $order_result['quantity_ordered'],2);
				$rep->TextCol(5, 6, $order_result['units']);
				$rep->AmountCol(6, 7, $order_result['unit_price'], $dec);
				
				
				$net_amount =$order_result['quantity_ordered'] * $order_result['unit_price'];
				// $rep->AmountCol(8, 9, $net_amount, $dec);
				// $rep->AmountCol(9, 10, $order_result['discount_percent'], $dec);
				$discount_amount = ($order_result['unit_price']*$order_result['quantity_ordered'])* $order_result['discount_percent']/100 ;
				// $rep->AmountCol(10, 11, $discount_amount, $dec);
				// $rep->AmountCol(11, 12, $line_total, $dec);
				$rep->AmountCol(7, 8, $order_result['quantity_received'],2);
				$rep->AmountCol(8, 9, $order_result['qty_invoiced'],2);
				//$cancel_qty = get_cancel_qty_item_wise($order_result['order_no'],$order_result['item_code']);
				$cancel_qty = 0;
				$rep->AmountCol(9, 10, $cancel_qty,2);
				$balance_qty =  $order_result['quantity_ordered']-($order_result['quantity_received']+$cancel_qty);
				$rep->AmountCol(10, 11, $balance_qty,2);
				$balance_amount = round2(($balance_qty * $order_result['unit_price'])-($balance_qty * $order_result['unit_price'] * ($order_result['discount_percent']/100)),user_price_dec());
				$rep->AmountCol(11, 12, $balance_amount,2);
				$rep->NewLine();
			 }
			
		}
		
	}
	
	$rep->NewLine();
	$rep->TextCol(13, 14, _('Total in Supplier Currency'));
	$rep->AmountCol(14,15, $total, $dec);
	$rep->NewLine(2);
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->TextCol(13, 14, _('Total in OMR'));
	$rep->AmountCol(14,15, $grandtotal, $dec);
	$rep->NewLine();
    $rep->End();
}

