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
function get_item_info($stock_id)
{
	$sql="SELECT * FROM ".TB_PREF."stock_master WHERE stock_id=".db_escape($stock_id)."";
	 $res =db_query($sql,"Could not get the item info!");
	 $result = db_fetch($res);
	 return $result;
}
function get_part_no($stock_id)
{
	$sql="SELECT part_number FROM ".TB_PREF."others WHERE stock_id=".db_escape($stock_id)."";
	$res =db_query($sql,"Could not get the item info!");
	 $result = db_fetch($res);
	 return $result['part_number'];
}
function get_sql_for_sales_invoiced_items($stock_id,$from, $to,$customer_id='',$cust_name='',$mobile='')
{
	$date_after = date2sql($from);
    $date_to = date2sql($to);
	$sql="SELECT trans.type, 
		trans.trans_no, 
		trans.order_, 
		trans.reference,
		trans.tran_date, 
		trans.due_date, 
		debtor.name, 
		branch.br_name,
		trans.cash_cust_name,
		trans.cash_cust_mobile,
		debtor.curr_code,so.from_stk_loc,
		line.quantity,
		(line.unit_price*(100-discount_percent)*0.01) as unit_price,debtor.debtor_no,line.standard_cost
		FROM ".TB_PREF."debtor_trans_details as line,
			 ".TB_PREF."debtor_trans as trans,
			 ".TB_PREF."cust_branch as branch,
			 ".TB_PREF."debtors_master as debtor, 
			 ".TB_PREF."sales_orders as so 
			 WHERE  trans.trans_no=line.debtor_trans_no  AND trans.type=line.debtor_trans_type 
			 AND trans.branch_code=branch.branch_code AND debtor.debtor_no = trans.debtor_no 
			 AND trans.type = ".ST_SALESINVOICE." AND so.order_no=trans.order_ AND line.stock_id=".db_escape($stock_id)." ";
		$sql .= " AND trans.tran_date >= '$date_after' AND trans.tran_date <= '$date_to' AND line.quantity!=0 ";
		if ($customer_id)
		$sql .= " AND debtor.debtor_no = ".db_escape($customer_id);
		if($cust_name!='')
		$sql .= " AND trans.cash_cust_name like ".db_escape($cust_name.'%')."";
		if($mobile!='')
		$sql .= " AND trans.cash_cust_mobile like ".db_escape($mobile.'%')."";
		
		$sql.=" ORDER by trans.tran_date desc"; 

		$res =db_query($sql,"Could not get the sales invoice info!");
	return $res;
}

function get_sql_for_outstanding_po($stock_id ,$from ,$to)
{
	$sql = "SELECT 
		porder.order_no, 
		porder.reference,
		supplier.supp_name, 
		location.location_name,
		porder.requisition_no, 
		porder.ord_date,
		supplier.curr_code,
		(line.unit_price*line.quantity_ordered)-((line.unit_price*line.quantity_ordered)*(line.discount_percent/100)) AS OrderValue,
		line.delivery_date < '". date2sql(Today()) ."'
		AND (line.quantity_ordered > line.quantity_received) As OverDue,
		line.quantity_ordered,
		line.quantity_received,
		line.unit_price,
		line.delivery_date,
		porder.requisition_no	
		FROM ".TB_PREF."purch_orders as porder,"
			.TB_PREF."purch_order_details as line, "
			.TB_PREF."suppliers as supplier, "
			.TB_PREF."locations as location
		WHERE porder.order_no = line.order_no
		AND porder.supplier_id = supplier.supplier_id
		AND location.loc_code = porder.into_stock_location
		AND (line.quantity_ordered > line.quantity_received) 
		AND porder.po_auth_req = '2' ";


		$data_after = date2sql($from);
		$data_before = date2sql($to);

		$sql .= "  AND porder.ord_date >= '$data_after'";
		$sql .= "  AND porder.ord_date <= '$data_before'";

		

		if ($stock_id != '')
		{
			$sql .= " AND line.item_code=".db_escape($stock_id);
		}
	// $sql .= " GROUP BY porder.order_no";
	$res =db_query($sql,"Could not get the sales invoice info!");
	return $res;
}

function get_sql_for_outstanding_so($trans_type, $filter, $stock_item='', $from='', $to='',$customer_id=ALL_TEXT) 
{
    if ($filter=='OutstandingOnly')
        $order_value = 'Sum(line.unit_price*(line.quantity-line.qty_sent)*(1-line.discount_percent/100))';
    else
        $order_value = 'Sum(line.unit_price*line.quantity*(1-line.discount_percent/100))';

	$sql = "SELECT 
			sorder.order_no,
			sorder.reference,
			debtor.name,
			branch.br_name,"
			.($filter=='InvoiceTemplates' 
				|| $filter=='DeliveryTemplates' ?
			 "sorder.comments, " : "sorder.customer_ref, ")
			."sorder.ord_date,
			sorder.delivery_date,
			sorder.deliver_to,
			$order_value AS OrderValue,
			sorder.type,
			debtor.curr_code,
			Sum(line.qty_sent) AS TotDelivered,
			Sum(line.quantity) AS TotQuantity,
			Sum(line.invoiced) AS TotInvoiced,
			alloc,
			prep_amount,
			allocs.ord_payments,
			inv.inv_payments,
			sorder.total,
			sorder.trans_type,
			ship.shipper_name,
			line.unit_price,
			line.stk_code,
			line.description
		FROM ".TB_PREF."sales_orders as sorder
		LEFT JOIN (SELECT trans_no_to, sum(amt) ord_payments FROM ".TB_PREF."cust_allocations WHERE trans_type_to=".ST_SALESORDER." GROUP BY trans_no_to)
			 allocs ON sorder.trans_type=".ST_SALESORDER." AND allocs.trans_no_to=sorder.order_no
		LEFT JOIN (SELECT order_, sum(prep_amount) inv_payments	FROM ".TB_PREF."debtor_trans WHERE type=".ST_SALESINVOICE." GROUP BY order_)
				 inv ON sorder.trans_type=".ST_SALESORDER." AND inv.order_=sorder.order_no,"
			.TB_PREF."sales_order_details as line, "
			.TB_PREF."debtors_master as debtor, "
			.TB_PREF."cust_branch as branch,"
			.TB_PREF."shippers as ship
			WHERE sorder.order_no = line.order_no
			AND sorder.trans_type = line.trans_type
			AND sorder.trans_type = ".db_escape($trans_type)."
			AND sorder.debtor_no = debtor.debtor_no
			AND sorder.branch_code = branch.branch_code
			AND debtor.debtor_no = branch.debtor_no
			AND ship.shipper_id = sorder.ship_via";

	
		if ($filter!='DeliveryTemplates' && $filter!='InvoiceTemplates' && $filter!='OutstandingOnly')
		{
			$date_after = date2sql($from);
			$date_before = date2sql($to);

			$sql .=  " AND sorder.ord_date >= '$date_after'"
					." AND sorder.ord_date <= '$date_before'";
		}
	
		if ($trans_type == ST_SALESQUOTE && !check_value('show_all'))
			$sql .= " AND sorder.delivery_date >= '".date2sql(Today())."' AND line.qty_sent=0"; // show only outstanding, not realized quotes
		if ($stock_item != ALL_TEXT)
			$sql .= " AND line.stk_code=".db_escape($stock_item);
			
		if ($filter=='OutstandingOnly')
			$sql .= " AND line.qty_sent < line.quantity";

		if ($filter=='PrepaidOrders')
			$sql .= " AND prep_amount>0";

		elseif ($filter=='InvoiceTemplates' || $filter=='DeliveryTemplates')
			$sql .= " AND sorder.type=1";

		//Chaiatanya : New Filter
		if ($customer_id != ALL_TEXT)
			$sql .= " AND sorder.debtor_no = ".db_escape($customer_id);		
			
		$sql .= " GROUP BY sorder.order_no,
					sorder.debtor_no,
					sorder.branch_code,
					sorder.customer_ref,
					sorder.ord_date,
					sorder.deliver_to
				ORDER BY sorder.order_no DESC";
		// display_error($sql);
	$res =db_query($sql,"Could not get the sales sorder info!");
	return $res;
}
