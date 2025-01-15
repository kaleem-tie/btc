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
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/inventory.inc");
include_once($path_to_root . "/includes/db/allocations_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_order_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_credit_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_invoice_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_delivery_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_points_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_groups_db.inc");
include_once($path_to_root . "/sales/includes/db/recurrent_invoices_db.inc");
include_once($path_to_root . "/sales/includes/db/custalloc_db.inc");
include_once($path_to_root . "/sales/includes/db/cust_trans_db.inc");
include_once($path_to_root . "/sales/includes/db/cust_trans_details_db.inc");
include_once($path_to_root . "/sales/includes/db/payment_db.inc");
include_once($path_to_root . "/sales/includes/db/branches_db.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");
// include_once($path_to_root . "/sales/includes/db/sales_pdc_db.inc");
include_once($path_to_root . "/sales/includes/db/pdc_db.inc");

//----------------------------------------------------------------------------------------
// add a debtor-related gl transaction
// $date_ is display date (non-sql)
// $amount is in CUSTOMER'S currency

function add_gl_trans_customer($type, $type_no, $date_, $account, $dimension, $dimension2,
	$amount, $customer_id, $err_msg="", $rate=0)
{
	if ($err_msg == "")
		$err_msg = "The customer GL transaction could not be inserted";

	return add_gl_trans($type, $type_no, $date_, $account, $dimension, $dimension2, "", $amount,
		get_customer_currency($customer_id),
		PT_CUSTOMER, $customer_id, $err_msg, $rate);
}

//----------------------------------------------------------------------------------------

function get_calculated_price($stock_id, $add_pct)
{
	$avg = get_unit_cost($stock_id);
	if ($avg == 0)
		return 0;
	return round2($avg * (1 + $add_pct / 100), user_price_dec());
}

function round_to_nearest($price, $round_to)
{
	if ($price == 0)
		return 0;
	$pow = pow(10, user_price_dec());
	if ($pow >= $round_to)
		$mod = ($pow % $round_to);
	else
		$mod = ($round_to % $pow);
	if ($mod != 0)
		$price = ceil($price) - ($pow - $round_to) / $pow;
	else	
    	$price = ceil($price * ($pow / $round_to)) / ($pow / $round_to);
    return $price;

}

function get_price ($stock_id, $currency, $sales_type_id, $factor=null, $date=null)
{
	if ($date == null)
	    $date = new_doc_date();

	if ($factor === null) 
	{
		$myrow = get_sales_type($sales_type_id);
		$factor = $myrow['factor'];
	}

	$add_pct = get_company_pref('add_pct');
	$base_id = get_base_sales_type();
    $home_curr = get_company_currency();
	//	AND (sales_type_id = $sales_type_id	OR sales_type_id = $base_id)
	$sql = "SELECT price, curr_abrev, sales_type_id
		FROM ".TB_PREF."prices
		WHERE stock_id = ".db_escape($stock_id)."
			AND (curr_abrev = ".db_escape($currency)." OR curr_abrev = ".db_escape($home_curr).")";

	$result = db_query($sql, "There was a problem retrieving the pricing information for the part $stock_id for customer");
	$num_rows = db_num_rows($result);
	$rate = round2(get_exchange_rate_from_home_currency($currency, $date),
	    user_exrate_dec());
	$round_to = get_company_pref('round_to');
	$prices = array();
	while($myrow = db_fetch($result)) 
	{
	    $prices[$myrow['sales_type_id']][$myrow['curr_abrev']] = $myrow['price'];
	}
	$price = false;
	if (isset($prices[$sales_type_id][$currency])) 
	{
	    $price = $prices[$sales_type_id][$currency];
	}
	elseif (isset($prices[$base_id][$currency])) 
	{
	    $price = $prices[$base_id][$currency] * $factor;
	}
	elseif (isset($prices[$sales_type_id][$home_curr])) 
	{
	    $price = $prices[$sales_type_id][$home_curr] / $rate;
	}
	elseif (isset($prices[$base_id][$home_curr])) 
	{
	    $price = $prices[$base_id][$home_curr] * $factor / $rate;
	}
	elseif ($num_rows == 0 && $add_pct != -1)
	{
		$price = get_calculated_price($stock_id, $add_pct);
		if ($currency != $home_curr)
			$price /= $rate;
		if ($factor != 0)
			$price *= $factor;
	}	
	if ($price === false)
		return 0;
	elseif ($round_to != 1)	
		return round_to_nearest($price, $round_to);
	else
		return round2($price, user_price_dec());
}
//----------------------------------------------------------------------------------------
//
//	Get price for given item or kit.
//  When $std==true price is calculated as a sum of all included stock items,
//	otherwise all prices set for kits and items are accepted.
//
function get_kit_price($item_code, $currency, $sales_type_id, $factor=null, 
	$date=null, $std = false)
{
	$kit_price = 0.00;
	if (!$std) {
		$kit_price = get_price( $item_code, $currency, $sales_type_id, 
			$factor, $date);

		if ($kit_price !== 0) {
			return $kit_price;
		}
	}	
	// no price for kit found, get total value of all items
	$kit = get_item_kit($item_code);
	
	while($item = db_fetch($kit)) {
		if ($item['item_code'] != $item['stock_id']) {
			// foreign/kit code
			$kit_price += $item['quantity'] * get_kit_price( $item['stock_id'], 
				$currency, $sales_type_id, $factor, $date, $std);

		} else {
			// stock item
			$kit_price += $item['quantity'] * get_price( $item['stock_id'], 
				$currency, $sales_type_id, $factor, $date);
		}
	}
	return $kit_price;
}

//--------------------------------------------------------------------------------------------------
function update_parent_line($doc_type, $line_id, $qty_dispatched, $auto=false,$foc_quantity=0)
{
	$doc_type = get_parent_type($doc_type);

        $qty_dispatched = (float)$qty_dispatched;
		$foc_qty_dispatched = (float)$foc_quantity;

	if ($doc_type == 0)
		return false;
	else {
		if ($doc_type==ST_SALESORDER || $doc_type==ST_SALESQUOTE)
		{
			$sql = "UPDATE ".TB_PREF."sales_order_details
				SET qty_sent = qty_sent + $qty_dispatched";
			$sql .= ", foc_qty_sent = foc_qty_sent + $foc_qty_dispatched";	
				
			if ($auto)
			{
				$sql .= ", quantity = quantity + $qty_dispatched";
				$sql .= ", foc_quantity = foc_quantity + $foc_qty_dispatched";
			}
			$sql .= " WHERE id=".db_escape($line_id);
		}
		else
			$sql = "UPDATE ".TB_PREF."debtor_trans_details
				SET qty_done = qty_done + $qty_dispatched, foc_quantity=$foc_qty_dispatched, foc_qty_sent = foc_qty_sent + $foc_qty_dispatched 
				WHERE id=".db_escape($line_id);
	}

	db_query($sql, "The parent document detail record could not be updated");
	return true;
}


//--------------------------------------------------------------------------------------------------
// find inventory location for given transaction
//
function get_location(&$cart)
{
	$sql = "SELECT location.*
		FROM ".TB_PREF."stock_moves move,"
			.TB_PREF."locations location".
		" WHERE type=".db_escape($cart->trans_type).
		" AND trans_no=".key($cart->trans_no).
		" AND qty!=0 ".
		" AND location.loc_code=move.loc_code";

	$result = db_query($sql, 'Retreiving inventory location');

	if (db_num_rows($result)) {
		return db_fetch($result);
	}
	return null;
}
//--------------------------------------------------------------------------------------------------
// Generic read debtor transaction into cart
//
//	$trans_no - array of trans nums; special case trans_no==0 - new doc
//
function read_sales_trans($doc_type, $trans_no, &$cart)
{

	if (!is_array($trans_no) && $trans_no)
		$trans_no = array($trans_no);

	$cart->trans_type = $doc_type;
	if (!$trans_no) { // new document
		$cart->trans_no = $trans_no;
	} else {
		// read header data from first document
		$myrow = get_customer_trans($trans_no[0],$doc_type);
		

		
        $debtor_no = $myrow['debtor_no'];
		if (count_array($trans_no)>1)
			$cart->trans_no = get_customer_trans_version($doc_type, $trans_no);
		else
			$cart->trans_no = array($trans_no[0]=>$myrow["version"]);
		
		//ravi
		$cart->sales_person_id=$myrow['sales_person_id']; 

		$cart->set_sales_type($myrow["tpe"], $myrow["sales_type"], $myrow["tax_included"],0);

        $cart->set_customer($debtor_no, $myrow["DebtorName"],
			$myrow["curr_code"], $myrow["discount"], $myrow["payment_terms"]);

		$cart->set_branch($myrow["branch_code"], $myrow["tax_group_id"],
			$myrow["tax_group_name"]);
		
		$cart->reference = $myrow["reference"];
		$cart->prepaid = $myrow["prepaid"];
		$cart->order_no = $myrow["order_"];
		$cart->due_date = sql2date($myrow["due_date"]);
		$cart->document_date = sql2date($myrow["tran_date"]);
		$cart->dimension_id = $myrow['dimension_id']; // added 2.1 Joe Hunt 2008-11-12
		$cart->dimension2_id = $myrow['dimension2_id'];
		$cart->Comments = '';
		$cart->invoice_type = $myrow["invoice_type"];
		$cart->signed_collection_status = $myrow["signed_collection_status"];
		
		$cart->ov_roundoff = $myrow["ov_roundoff"];
		$cart->lpo_no = $myrow["lpo_no"];
		$cart->lpo_date = sql2date($myrow["lpo_date"]);
		$cart->inv_edit_ref = $myrow["inv_edit_ref"];
		
		
		foreach ( $trans_no as $trans ) {
			$cart->Comments .= get_comments_string($doc_type,$trans);
		}

		// FIX this should be calculated sum() for multiply parents

		$cart->set_delivery($myrow["ship_via"], $myrow["br_name"],
		$myrow["br_address"], $myrow["ov_freight"]);

		$location = 0;
		$myrow = get_location($cart); // find location from movement

		if($myrow!=null) {
			$cart->set_location($myrow['loc_code'], $myrow['location_name']);
		}

		$result = get_customer_trans_details($doc_type,$trans_no);
		if (db_num_rows($result) > 0) {
			for($line_no=0; $myrow = db_fetch($result); $line_no++)	{
			
  			$cart->line_items[$line_no] = new line_details(
					$myrow["stock_id"],$myrow["quantity"],
					$myrow["unit_price"], $myrow["discount_percent"],
					$myrow["qty_done"], $myrow["standard_cost"],
					$myrow["StockDescription"],$myrow["id"], $myrow["debtor_trans_no"],
					@$myrow["src_id"], $myrow['unit'],0,'','',
					$myrow['disc_amount'],$myrow['foc_quantity'],$myrow['foc_qty_sent']);
			}
		}
        $cart->prepayments = get_payments_for($trans_no[0], $doc_type, $debtor_no);
		


	} // !newdoc

	return true;
}
//----------------------------------------------------------------------------------------

function get_sales_child_lines($trans_type, $trans_no, $lines=true)
{
	if (!($ctype = get_child_type($trans_type)))
		return false;
	if (!is_array($trans_no)) {
		$trans_no = array($trans_no);
	}

	$par_tbl = $trans_type == ST_SALESORDER ? "sales_order_details" : "debtor_trans_details";
	$par_no = $trans_type == ST_SALESORDER ? "trans.order_no" : "trans.debtor_trans_no";

	foreach($trans_no as $n => $trans) {
		$trans_no[$n] = db_escape($trans);
	}
	$sql = "SELECT child.*
			FROM
				".TB_PREF."debtor_trans_details child
			LEFT JOIN ".TB_PREF."$par_tbl trans 
				ON child.src_id=trans.id AND child.debtor_trans_type=$ctype
			WHERE $par_no IN(". implode(',', array_values($trans_no)).")";

	if (!$lines)
		$sql .= " GROUP BY child.debtor_trans_no";

	$sql .= " ORDER BY child.debtor_trans_no";

	return db_query($sql, "can't retrieve child trans");
}

function get_sales_child_numbers($trans_type, $trans_no)
{
	$trans = array();
	$res = get_sales_child_lines($trans_type, $trans_no, false);
	while ($line = db_fetch($res)) {
		$trans[] = $line['debtor_trans_no'];
	}
	return $trans;
}

function get_sales_parent_lines($trans_type, $trans_no, $lines=true)
{
	$partype = get_parent_type($trans_type);

	if (!$partype)
		return false;

	$par_tbl = $partype == ST_SALESORDER ? "sales_order_details" : "debtor_trans_details";
	$par_no = $partype == ST_SALESORDER ? "parent.order_no" : "parent.debtor_trans_no";
	$sql = "SELECT parent.*
			FROM
				".TB_PREF."$par_tbl parent
			LEFT JOIN ".TB_PREF."debtor_trans_details trans 
				ON trans.src_id=parent.id
			WHERE
				trans.debtor_trans_type=".db_escape($trans_type)
				." AND trans.debtor_trans_no=".db_escape($trans_no);
	if (!$lines)
		$sql .= " GROUP BY $par_no";
	
	$sql .= " ORDER BY $par_no";
	
	return db_query($sql, "can't retrieve child trans");

}

function get_sales_parent_numbers($trans_type, $trans_no)
{
	$trans = array();
	$res = get_sales_parent_lines($trans_type, $trans_no, false);
	while ($line = db_fetch($res))
		$trans[] = $line[$trans_type==ST_CUSTDELIVERY ? 'order_no' : 'debtor_trans_no'];
	return $trans;
}

//----------------------------------------------------------------------------------------

function get_sales_child_documents($trans_type, $trans_no)
{
	// FIXME -  credit notes retrieved here should be those linked to invoices containing 
	// at least one line from related invoice

	if (!count_array($trans_no))
		return false;
	$childs = get_sales_child_numbers($trans_type, $trans_no, false);
	if (!count_array($childs))
		return false;
		
	$sql = "SELECT * FROM ".TB_PREF."debtor_trans
		WHERE type=".get_child_type($trans_type)." AND trans_no IN(". implode(',', array_values($childs)).")";

	return db_query($sql,"The related credit notes could not be retreived");
}

function get_sales_order_value($order_no)
{
	$sql = "SELECT total FROM ".TB_PREF."sales_orders WHERE trans_type=30 and order_no=".db_escape($order_no)."";
	
	$result= db_query($sql,"The related promotional could not be retreived");
	
	$row=db_fetch_row($result);
	return $row[0];
}

// For offers   ## Ramesh
function get_sale_item_promotional_discount_by_item($order_date,$stock_id)
{
	$order_date=date2sql($order_date);
	
	$sql = "SELECT promo.disc FROM ".TB_PREF."items_promotion_discounts promo WHERE FIND_IN_SET('$stock_id',promo.stock_id) and promo.from_date<=".db_escape($order_date)." and to_date>=".db_escape($order_date)." LIMIT 1";
	$result= db_query($sql,"The item wise promotional discount could not be retreived");
	$row=db_fetch_row($result);
	if($row[0]!='')
		return $row[0];
	else
		return 0;
}

function get_sale_item_promotional_discount_by_supplier($order_date,$stock_id)
{
	$order_date=date2sql($order_date);
	
	$sql = "SELECT promo.disc FROM ".TB_PREF."brands_promotion_discounts promo,".TB_PREF."stock_master sm WHERE sm.stock_id=".db_escape($stock_id)." and FIND_IN_SET(sm.supplier_id,promo.supplier_id) and promo.from_date<=".db_escape($order_date)." and to_date>=".db_escape($order_date)." LIMIT 1";	
	$result= db_query($sql,"The Barnd wise promotional dicount could not be retreived");
	
	$row=db_fetch_row($result);
	if($row[0]!='')
		return $row[0];
	else
		return 0;
}

function get_sale_item_promotional_discount_by_category($order_date,$stock_id)
{
	$order_date=date2sql($order_date);
	
	$sql = "SELECT promo.disc FROM ".TB_PREF."promotion_discounts promo,".TB_PREF."stock_master sm WHERE sm.stock_id=".db_escape($stock_id)." and FIND_IN_SET(sm.category_id,promo.category_id) and promo.from_date<=".db_escape($order_date)." and to_date>=".db_escape($order_date)." LIMIT 1";	
	$result= db_query($sql,"The related promotional could not be retreived");
	$row=db_fetch_row($result);
	if($row[0]!='')
		return $row[0];
	else
		return 0;
}


//------------------------------------------------------//

function read_sales_delivery_items($order_no,$delivery_date)
{
	$delivery_date=date2sql($delivery_date);
	$sql = "SELECT line.stock_id as stk_code,
				line.description,
				line.quantity,
				line.unit				
			FROM ".TB_PREF."debtor_trans_details line,".TB_PREF."debtor_trans dt,"
				.TB_PREF."stock_master item
			WHERE line.stock_id = item.stock_id
			     AND dt.type=line.debtor_trans_type and dt.trans_no=line.debtor_trans_no 
				AND dt.order_ =".db_escape($order_no) 
				." AND dt.type =13 AND dt.tran_date = ".db_escape($delivery_date) . "";
				
			
				
	return db_query($sql,"The related items could not be retreived");			
}

function read_sales_rescheduled_items($order_no,$delivery_date)
{
	$delivery_date=date2sql($delivery_date);
	$sql = "SELECT line.stk_code,
				line.description,
				line.quantity,
				line.qty_sent,
				line.unit,
              	del_plan.planned_date,
                del_plan.planned_delivery_time				
			FROM ".TB_PREF."sales_delivery_plan del_plan,".TB_PREF."sales_order_details line,"
				.TB_PREF."stock_master item
			WHERE del_plan.order_line_id = line.id and line.quantity>line.qty_sent 
			     AND del_plan.order_no=line.order_no and line.trans_type=30 and del_plan.has_child=0  
				AND del_plan.order_no =".db_escape($order_no) 
				." AND item.stock_id =line.stk_code AND del_plan.planned_date = ".db_escape($delivery_date) . "";
				
	return db_query($sql,"The related items could not be retreived");			
}

function read_sales_order_items($order_no,$delivery_date)
{
	$delivery_date=date2sql($delivery_date);
	$sql = "SELECT line.stk_code,
				line.description,
				line.quantity,
				line.qty_sent,
				line.unit				
			FROM ".TB_PREF."sales_orders sorder,".TB_PREF."sales_order_details line,"
				.TB_PREF."stock_master item
			WHERE sorder.order_no = line.order_no and sorder.trans_type=line.trans_type and sorder.trans_type=30 
			     AND line.trans_type=30 and line.planned_status=0 and line.quantity>line.qty_sent 
				AND sorder.order_no =".db_escape($order_no) 
				." AND item.stock_id =line.stk_code AND sorder.delivery_date = ".db_escape($delivery_date) . "";
				
	return db_query($sql,"The related items could not be retreived");			
}

function read_cancel_sales_order($order_no, &$order, $trans_type)
{
	
	$myrow = get_cancel_sales_order_header($order_no, $trans_type);

	$order->trans_type = $myrow['trans_type'];
	$order->so_type =  $myrow["type"];
	$order->trans_no = array($order_no=> $myrow["version"]);

	$order->set_customer($myrow["debtor_no"], $myrow["name"],
	  $myrow["curr_code"], $myrow["discount"], $myrow["payment_terms"]);

	$order->set_branch($myrow["branch_code"], $myrow["tax_group_id"],
	  $myrow["tax_group_name"], $myrow["contact_phone"]);

	$order->set_sales_type($myrow["sales_type_id"], $myrow["sales_type"], 
	    $myrow["tax_included"], $myrow["factor"]); // no default price calculations on edit

	$order->set_location($myrow["from_stk_loc"], $myrow["location_name"]);

	$order->set_delivery($myrow["ship_via"], $myrow["deliver_to"],
	  $myrow["delivery_address"], $myrow["freight_cost"]);

	$order->cust_ref = $myrow["customer_ref"];
	$order->sales_type =$myrow["order_type"];
	$order->reference = $myrow["reference"];
	$order->Comments = $myrow["comments"];
	$order->due_date = sql2date($myrow["delivery_date"]);
	$order->document_date = sql2date($myrow["ord_date"]);
	
	$order->dimension_id = $myrow["dimension_id"];
	$order->dimension2_id = $myrow["dimension2_id"];
	
	//ravi
	$order->sales_person_id=$myrow['sales_person_id'];

	$order->prepaid = $myrow["prepaid"];
	$order->alloc = $myrow['alloc'];
	$order->sum_paid = $myrow["sum_paid"]; // sum of all prepayments to so (also invoiced)
	$order->prep_amount = $myrow["prep_amount"];
	$order->prepayments = get_payments_for($order_no, $myrow['trans_type'], $myrow['debtor_no']);
	if($order->trans_type == ST_SALESORDER || $order->trans_type == ST_SALESQUOTE){
		$result = get_sales_order_details($order_no, $order->trans_type);
		if (db_num_rows($result) > 0)
		{
			$line_no=0;
			while ($myrow = db_fetch($result))
			{
				$order->add_to_cart($line_no,$myrow["stk_code"],$myrow["quantity"],
					$myrow["unit_price"], $myrow["discount_percent"],
					$myrow["qty_done"], $myrow["material_cost"], $myrow["description"], $myrow["id"], 0, 0, $myrow['disc_amount'], $myrow['unit'] );
			$line_no++;
			
			
			}
		}
	}
}

function get_cancel_sales_order_header($order_no, $trans_type)
{
	$sql = "SELECT sorder.*,
	  cust.name,
	  cust.curr_code,
	  cust.address,
	  loc.location_name,
	  cust.discount,
	  stype.sales_type,
	  stype.id AS sales_type_id,
	  stype.tax_included,
	  stype.factor,
 	  ship.shipper_name,
	  tax_group.name AS tax_group_name,
	  tax_group.id AS tax_group_id,
	  cust.tax_id,
	  sorder.alloc,
	  IFNULL(allocs.ord_allocs, 0)+IFNULL(inv.inv_allocs ,0) AS sum_paid,
	  sorder.prep_amount>0 as prepaid
	FROM ".TB_PREF."cancel_sales_orders sorder
			LEFT JOIN (SELECT trans_no_to, sum(amt) ord_allocs FROM ".TB_PREF."cust_allocations
				WHERE trans_type_to=".ST_SALESORDER." AND trans_no_to=".db_escape($order_no)." GROUP BY trans_no_to)
				 allocs ON sorder.trans_type=".ST_SALESORDER." AND allocs.trans_no_to=sorder.order_no
			LEFT JOIN (SELECT order_, sum(alloc) inv_allocs FROM ".TB_PREF."debtor_trans 
				WHERE type=".ST_SALESINVOICE." AND order_=".db_escape($order_no)."  GROUP BY order_)
				 inv ON sorder.trans_type=".ST_SALESORDER." AND inv.order_=sorder.order_no
			LEFT JOIN ".TB_PREF."shippers ship ON  ship.shipper_id = sorder.ship_via,"
	  .TB_PREF."debtors_master cust,"
	  .TB_PREF."sales_types stype, "
	  .TB_PREF."tax_groups tax_group, "
	  .TB_PREF."cust_branch branch,"
	  .TB_PREF."locations loc
	WHERE sorder.order_type=stype.id
		AND branch.branch_code = sorder.branch_code
		AND branch.tax_group_id = tax_group.id
		AND sorder.debtor_no = cust.debtor_no
		AND loc.loc_code = sorder.from_stk_loc
		AND sorder.trans_type = " . db_escape($trans_type) ."
		AND sorder.order_no = " . db_escape($order_no );

	$result = db_query($sql, "order Retreival");
	$num = db_num_rows($result);
	if ($num > 1)
	{
		display_warning("You have duplicate document in database: (type:$trans_type, number:$order_no).");
	}
	else if ($num == 1)
	{
		return db_fetch($result);
	}
	else
		display_warning("You have missing or invalid sales document in database (type:$trans_type, number:$order_no).");

}

function read_cancel_sales_order_items($order_no,$delivery_date)
{
	$delivery_date=date2sql($delivery_date);
	$sql = "SELECT line.stk_code,
				line.description,
				line.quantity,
				line.qty_sent,
				line.unit				
			FROM ".TB_PREF."cancel_sales_orders sorder,".TB_PREF."cancel_sales_order_details line,"
				.TB_PREF."stock_master item
			WHERE sorder.order_no = line.order_no and sorder.trans_type=line.trans_type and sorder.trans_type=30 
			     AND line.trans_type=30 and line.planned_status=0 and line.quantity>line.qty_sent 
				AND sorder.order_no =".db_escape($order_no) 
				." AND item.stock_id =line.stk_code AND sorder.delivery_date = ".db_escape($delivery_date) . "";
				
	return db_query($sql,"The related items could not be retreived");			
}

function add_new_signed_copy($trans_no,$uploaded_date, $uploaded_remarks, $filename,  $unique_name, $filesize, $filetype)
{
	$uploaded_date=date2sql($uploaded_date);
	$sql = "INSERT INTO ".TB_PREF."signed_copy_collection_register (invoice_no, collected_date	,remarks, filename, unique_name, filesize, filetype, collected_by)
	VALUES (".db_escape($trans_no).",
    ".db_escape($uploaded_date).",
	".db_escape($uploaded_remarks).",
	".db_escape($filename).",".db_escape($unique_name).", 
	".db_escape($filesize).", ".db_escape($filetype).", ".db_escape($_SESSION["wa_current_user"]->loginname).")";
	db_query($sql, "could not add new signed copy!");
	return db_insert_id();
}

function update_sales_invoice_signed_copy($order_no)
{
	$sql = "UPDATE ".TB_PREF."debtor_trans SET signed_collection_status=1 WHERE trans_no=".db_escape($order_no)." AND type = ".ST_SALESINVOICE."";
 	db_query($sql, "could not update tender quotation");
}


//----------------------------------------------------------------------------------
function get_group_company_name($id)
{
	$sql = "SELECT cust_group_comp_name FROM ".TB_PREF."sales_cust_group_comp WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get Group Company Name");

	$row = db_fetch_row($result);
	return is_array($row) ? $row[0] : false;
}

function get_legal_group_name($id)
{
	$sql = "SELECT legal_group_name FROM ".TB_PREF."sales_legal_group WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get Legal Group Name");

	$row = db_fetch_row($result);
	return is_array($row) ? $row[0] : false;
}

function get_customer_class_name($id)
{
	$sql = "SELECT cust_class_name FROM ".TB_PREF."sales_cust_class WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get Cutomer Class Name");

	$row = db_fetch_row($result);
	return is_array($row) ? $row[0] : false;
}

// check customer existance
function is_customer_existed($customer_code)
{
	$sql = "SELECT count(*) FROM ".TB_PREF."debtors_master WHERE cust_code=".db_escape($customer_code)."";
	$result= db_query($sql,"The customer could not be retreived!");
	$row= db_fetch_row($result);
	return $row[0];
}
function get_debtor_no($customer_code)
{
	$sql = "SELECT debtor_no FROM ".TB_PREF."debtors_master WHERE cust_code=".db_escape($customer_code)."";
	$result= db_query($sql,"The customer could not be retreived!");
	$row= db_fetch_row($result);
	return $row[0];
}
function check_customer_having_invoice($debtor_no,$invoice_ref)
{
	$sql = "SELECT count(*) FROM ".TB_PREF."debtor_trans WHERE debtor_no=".db_escape($debtor_no)." AND reference=".db_escape($invoice_ref)."
	AND type = ".ST_SALESINVOICE."";
	$result= db_query($sql,"The customer could not be retreived!");
	$row= db_fetch_row($result);
	return $row[0];
}
function is_sales_person_existed($sales_person_name)
{
	$sql = "SELECT count(*) FROM ".TB_PREF."salesman WHERE salesman_name=".db_escape($sales_person_name)."";

	$result= db_query($sql,"The related Sales Executives could not be retreived");
	$row= db_fetch_row($result);
	return $row[0];
}

function get_salesperson_no_by_name($sales_person_name){
	$sql = "SELECT salesman_code FROM ".TB_PREF."salesman WHERE 
	salesman_name=".db_escape($sales_person_name)."";
	$result = db_query($sql, "could not get discount");
	$myrow = db_fetch_row($result);
	return $myrow[0]; 
}

function get_debtor_no_by_vendor_code($cust_code){
	$sql = "SELECT debtor_no FROM ".TB_PREF."debtors_master WHERE 
	cust_code=".db_escape($cust_code)."";
	$result = db_query($sql, "could not get discount");
	$myrow = db_fetch_row($result);
	return $myrow[0]; 
}

function get_invoice_information($debtor_no,$invoice_ref)
{
	$sql = "SELECT trans_no, ((ov_amount + ov_gst + ov_freight + ov_freight_tax + ov_discount) - (alloc +pdc_amt))as total FROM ".TB_PREF."debtor_trans WHERE debtor_no=".db_escape($debtor_no)." AND reference=".db_escape($invoice_ref)."
	AND type = ".ST_SALESINVOICE."";
	$result= db_query($sql,"The customer could not be retreived!");
	return $row= db_fetch($result);
}

function update_debtors_trans($trans_no, $amount)
{
	$sql = "UPDATE ".TB_PREF."debtor_trans SET alloc=alloc+$amount WHERE trans_no=".db_escape($trans_no)." AND type = ".ST_SALESINVOICE."";
 	db_query($sql, "could not update tender quotation");
}

function add_cust_allocations($debtor_no,$amount,$collected_date,$payment_no,$trans_type_from,
$trans_no,$trans_type_to)
{
	$collected_date=date2sql($collected_date);
	$sql = "INSERT INTO ".TB_PREF."cust_allocations (person_id, amt, date_alloc, trans_no_from, trans_type_from, trans_no_to, trans_type_to)
	VALUES (".db_escape($debtor_no).",
    ".db_escape($amount).",
	".db_escape($collected_date).",
	".db_escape($payment_no).",".db_escape($trans_type_from).", 
	".db_escape($trans_no).", ".db_escape($trans_type_to).")";
	db_query($sql, "could not add new customer allocation!");	
}



function get_cust_payment_sales_person_ref($trans_no)
{
	$sql = "SELECT sales_person_ref FROM ".TB_PREF."debtor_trans 
	WHERE trans_no=".db_escape($trans_no)." 
	AND type = ".ST_CUSTPAYMENT."";
	$result = db_query($sql, "could not get discount");
	$myrow = db_fetch_row($result);
	return $myrow[0]; 
}



function get_inv_sales_order_reference($order_no)
{
	$sql = "SELECT reference FROM ".TB_PREF."sales_orders 
	WHERE order_no=".db_escape($order_no)." 
	AND  trans_type = ".ST_SALESORDER."";
	$result = db_query($sql, "could not get discount");
	$myrow = db_fetch_row($result);
	return $myrow[0]; 
}

function isDoInvoiced($order_no){
	$sql  = "SELECT count(*)  FROM  ".TB_PREF."debtor_trans WHERE order_=".db_escape($order_no)." AND type=".ST_SALESINVOICE."";
	$result = db_query($sql,"could not get count");
	$myrow = db_fetch_row($result);
	return $myrow['0'];
}

function updateSoPrice($line_id,$price){
	$sql = "UPDATE ".TB_PREF."sales_order_details SET unit_price=".db_escape($price)." WHERE id=".db_escape($line_id)." AND trans_type = ".ST_SALESORDER."";
 	db_query($sql, "could not update so price");

}

// Sales order total void  
function void_sales_orders_total($trans_type, $order_no)
{
	// 
	$sql = "UPDATE ".TB_PREF."sales_orders SET total=0, prep_amount=0
		WHERE trans_type=".db_escape($trans_type)." 
		AND order_no=".db_escape($order_no);

	db_query($sql, "could not void sales order transactions for trans_type=$trans_type and order_no=$order_no");
}


function vaidateInvoiceDatewithDoDate($dispatch_id,$invoice_date)
{
  $invoice_date = date2sql($invoice_date);
  $sql = "SELECT DATEDIFF(".db_escape($invoice_date).",dt.tran_date) from ".TB_PREF."debtor_trans dt where dt.type=13 and dt.trans_no=".db_escape($dispatch_id).""; 
  $result = db_query($sql,"could not get count");
	if($myrow = db_fetch_row($result))
	return $myrow['0'];
	else
	return 0;
}




function get_customer_wise_pdc_in_hand($customer_id){

$sql = "SELECT SUM(ov_amount) As total_amount FROM ".TB_PREF."debtor_trans WHERE debtor_no=".db_escape($customer_id)."";

$sql .= "AND type=5 AND current_pdc_status=0";

//display_error($sql);

$res = db_query($sql);
$result = db_fetch($res);
	if($result['total_amount']>0){
	return $result['total_amount'];
	}else{
		return 0;
	} 
	
}



function get_customer_wise_current_balance_without_pdc($customer_id){

 	  $sql = "SELECT SUM(IF(trans.prep_amount, trans.prep_amount, trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff -alloc) )AS TotalAmount FROM ".TB_PREF."debtor_trans trans LEFT JOIN ".TB_PREF."voided voided ON trans.type=voided.type AND trans.trans_no=voided.id LEFT JOIN (SELECT trans_type_from as trans_type, trans_no_from as trans_no,  sum(amt) amount FROM ".TB_PREF."cust_allocations alloc WHERE person_id=".db_escape($customer_id)." GROUP BY trans_type_from, trans_no_from) alloc_from ON alloc_from.trans_type = trans.type AND alloc_from.trans_no = trans.trans_no LEFT JOIN (SELECT trans_type_to as trans_type, trans_no_to as trans_no,  sum(amt) amount FROM ".TB_PREF."cust_allocations alloc WHERE person_id=".db_escape($customer_id)." GROUP BY trans_type_to, trans_no_to) alloc_to ON alloc_to.trans_type = trans.type AND alloc_to.trans_no = trans.trans_no WHERE trans.debtor_no =".db_escape($customer_id)." AND trans.type <> 13 AND trans.type <> 5 AND trans.ov_amount!=0 AND ISNULL(voided.id)";
			
	//display_error($sql);
		
	$res = db_query($sql);
    $result = db_fetch($res);
	if($result['TotalAmount']>0){
	return $result['TotalAmount'];
	}else{
		return 0;
	} 
		
}


function get_customer_wise_pending_do($customer_id){

 $sql = "SELECT SUM(ov_amount+ov_gst+ov_freight+ov_freight_tax) AS DeliveryValue FROM ".TB_PREF."sales_orders as sorder, ".TB_PREF."debtor_trans as trans, ".TB_PREF."debtor_trans_details as line, ".TB_PREF."debtors_master as debtor, ".TB_PREF."cust_branch as branch WHERE sorder.order_no = trans.order_ AND trans.debtor_no = debtor.debtor_no AND trans.type = 13 AND line.debtor_trans_no = trans.trans_no AND line.debtor_trans_type = trans.type AND trans.branch_code = branch.branch_code AND trans.debtor_no = branch.debtor_no AND line.qty_done < line.quantity AND trans.debtor_no=".db_escape($customer_id).""; 
 
 
 $res = db_query($sql);
    $result = db_fetch($res);
	if($result['DeliveryValue']>0){
	return $result['DeliveryValue'];
	}else{
		return 0;
	} 
}


function get_customer_wise_credit_limit($customer_id){

 $sql = "SELECT credit_limit FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape($customer_id).""; 
 
  $res = db_query($sql);
  $result = db_fetch($res);
  return $result['credit_limit'];
	
}

function get_customer_wise_sales_invoice_due($customer_id){

$sql = "SELECT trans_no, reference, due_date FROM 0_debtor_trans WHERE type=10 and debtor_no=".db_escape($customer_id)." ORDER BY trans_no ASC";

 $res = db_query($sql);
  $result = db_fetch($res);
  return $result;
}


function get_cust_pdc_sales_person_ref($trans_no)
{
	$sql = "SELECT sales_person_ref FROM ".TB_PREF."debtor_trans 
	WHERE trans_no=".db_escape($trans_no)." 
	AND type = ".ST_CUSTPDC."";
	$result = db_query($sql, "could not get discount");
	$myrow = db_fetch_row($result);
	return $myrow[0]; 
}
function get_credit_limit_tag_id($customer_id){

 $sql = "SELECT credit_limit_tag_id FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape($customer_id).""; 
  $res = db_query($sql);
  $result = db_fetch($res);
  return $result['credit_limit_tag_id'];
}

//---------------------------------------------
//Do Cum Invoice Edit Qty  
function get_sales_invoice_child_delivery_info($trans_type, $trans_no){

    $sql = "SELECT parent.*
			FROM
				".TB_PREF."debtor_trans_details as parent
			LEFT JOIN ".TB_PREF."debtor_trans_details trans 
				ON trans.src_id=parent.id
			WHERE
				trans.debtor_trans_type=".db_escape($trans_type)
				." AND trans.debtor_trans_no=".db_escape($trans_no);
	
	$sql .= " GROUP BY parent.debtor_trans_no ORDER BY parent.debtor_trans_no";
  $res = db_query($sql);
  $result = db_fetch($res);
  return $result;
}


function update_documinvoice_delivery_qty($line_id,$qty_invoiced=0)
{

    $qty_invoiced = (float)$qty_invoiced;
	
	$sql = "UPDATE ".TB_PREF."debtor_trans_details
		SET qty_done = $qty_invoiced, quantity = $qty_invoiced
		WHERE id = ".db_escape($line_id)."
		AND debtor_trans_type=".ST_CUSTDELIVERY;

	db_query($sql, "The sales order detail record could not be updated");
}

function get_sales_delivery_src_id($line_id){
 $sql = "SELECT src_id FROM ".TB_PREF."debtor_trans_details 
 WHERE debtor_trans_type=13
 AND id=".db_escape($line_id).""; 
 
 $res = db_query($sql);
 $result = db_fetch($res);
 return $result;
}


function get_sales_delivery_quantity($src_id){
 $sql = "SELECT SUM(quantity) as qty_delivered FROM ".TB_PREF."debtor_trans_details 
 WHERE debtor_trans_type=13
 AND src_id=".db_escape($src_id).""; 
 
 $res = db_query($sql);
 $result = db_fetch($res);
 return $result;
}

function update_documinvoice_order_qty($line_id,$qty_delivered=0)
{

    $qty_delivered = (float)$qty_delivered;
	
	$sql = "UPDATE ".TB_PREF."sales_order_details
		SET qty_sent = $qty_delivered
		WHERE id = ".db_escape($line_id)."
		AND trans_type=".ST_SALESORDER;

	db_query($sql, "The sales order detail record could not be updated");
}


function update_documinvoice_delivery_totals($trans_no,$Total=0,$Tax=0)
{

	$sql = "UPDATE ".TB_PREF."debtor_trans
		SET ov_amount=$Total, ov_gst=$Tax
		WHERE trans_no = ".db_escape($trans_no)."
		AND type=".ST_CUSTDELIVERY;
	db_query($sql, "The sales order detail record could not be updated");
}

function get_sales_order_line_item_total($order_no){

$order_value = 'Sum(line.unit_price*line.quantity*(1-line.discount_percent))+freight_cost';

$sql = "SELECT 
			sorder.order_no,
			sorder.reference,";
			$sql.=" $order_value AS OrderValue, ";
			$sql.=" sorder.type
FROM ".TB_PREF."sales_orders as sorder,".TB_PREF."sales_order_details as line
 WHERE sorder.order_no = line.order_no
 AND sorder.trans_type = line.trans_type
 AND sorder.trans_type =  30
 AND sorder.order_no=".db_escape($order_no).""; 
 $res = db_query($sql);
 $result = db_fetch($res);
 return $result;
}

function update_documinvoice_sales_order_totals($order_no,$Total=0)
{
	$sql = "UPDATE ".TB_PREF."sales_orders
		SET total=$Total
		WHERE order_no = ".db_escape($order_no)."
		AND trans_type=".ST_SALESORDER;

	db_query($sql, "The sales order detail record could not be updated");
}