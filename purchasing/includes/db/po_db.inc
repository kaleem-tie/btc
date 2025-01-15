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
// ------------------------------------------------------------------------------

function get_supplier_details_to_order(&$order, $supplier_id)
{
	$sql = "SELECT curr_code, supp_name, tax_group_id, supp.tax_included, dimension_id, dimension2_id,
			supp.credit_limit - Sum((ov_amount + ov_gst + ov_discount)) as cur_credit,
				terms.terms, terms.days_before_due, terms.day_in_following_month
		FROM ".TB_PREF."suppliers supp
			 LEFT JOIN ".TB_PREF."supp_trans trans ON supp.supplier_id = trans.supplier_id
			 LEFT JOIN ".TB_PREF."payment_terms terms ON supp.payment_terms=terms.terms_indicator
		WHERE supp.supplier_id = ".db_escape($supplier_id)."
		GROUP BY
			  supp.supp_name";

	$result = db_query($sql, "The supplier details could not be retreived");
	$myrow = db_fetch($result);

	$order->credit = $myrow["cur_credit"];
	$order->terms = array( 
		'description' => $myrow['terms'],
		'days_before_due' => $myrow['days_before_due'], 
		'day_in_following_month' => $myrow['day_in_following_month'] );

	$_POST['supplier_id'] = $supplier_id;
	$_POST['supplier_name'] = $myrow["supp_name"];
	$_POST['curr_code'] = $myrow["curr_code"];
	$_POST['dimension'] = $myrow["dimension_id"];
	$_POST['dimension2'] = $myrow["dimension2_id"];

	$order->set_supplier($supplier_id, $myrow["supp_name"], $myrow["curr_code"], 
		$myrow["tax_group_id"], $myrow["tax_included"]);
}

//----------------------------------------------------------------------------------------

function delete_po($po)
{
	global $Refs;

	begin_transaction();
	hook_db_prevoid(ST_PURCHORDER, $po);
	$sql = "DELETE FROM ".TB_PREF."purch_orders WHERE order_no=".db_escape($po);
	db_query($sql, "The order header could not be deleted");

	$sql = "DELETE FROM ".TB_PREF."purch_order_details WHERE order_no =".db_escape($po);
	db_query($sql, "The order detail lines could not be deleted");

	$Refs->restore_last(ST_PURCHORDER, $po);
	commit_transaction();
}

//----------------------------------------------------------------------------------------

function add_po(&$po_obj)
{
	global $Refs;

	begin_transaction();
	hook_db_prewrite($po_obj, ST_PURCHORDER);
	
	if($po_obj->move="QUOT-ORD"){
	
		$sql1 = "UPDATE ".TB_PREF."purch_orders SET conv_status='1'";
			$sql1 .= " WHERE order_no = ".db_escape($po_obj->quote_order); 
			$sql1 .=" AND trans_type=".db_escape(ST_PURCHQUOTE)."";
	
	db_query($sql1, "The purchase order could not be updated");
		
	}
	
	
	if($po_obj->move="ENQ-QUOT"){
	
		$sql1 = "UPDATE ".TB_PREF."purch_orders SET conv_status='1'";
			$sql1 .= " WHERE order_no = ".db_escape($po_obj->enq_quote); 
			$sql1 .=" AND trans_type=".db_escape(ST_PURCHENQ)."";
	
	db_query($sql1, "The purchase order could not be updated");
		
	}
	
	$company_record = get_company_prefs();
	$po_auth_req_amount = str_replace(',', '', $company_record["po_auth_req_amount"]);
  
    if($po_obj->get_trans_total() > $po_auth_req_amount ){
	$po_auth_req = 1;
	}
	else if($po_obj->reference == 'auto'){
		$po_auth_req = 2;
	}else{
		$po_auth_req = 2;
	}
	
	$order_no = get_next_trans_no($po_obj->trans_type);

     /*Insert to purchase order header record */
     $sql = "INSERT INTO ".TB_PREF."purch_orders (supplier_id, Comments, ord_date, reference, 
     	requisition_no, into_stock_location, delivery_address, total, tax_included, prep_amount,final_discount,final_discount_amount,po_auth_req,freight_cost,additional_charges,packing_charges,other_charges,trans_type,generated_by,comparison_code,order_no,delivery_terms,purch_inco_terms,purch_type) VALUES(";
     $sql .= db_escape($po_obj->supplier_id) . "," .
         db_escape($po_obj->Comments) . ",'" .
         date2sql($po_obj->orig_order_date) . "', " .
		 db_escape($po_obj->reference) . ", " .
         db_escape($po_obj->supp_ref) . ", " .
         db_escape($po_obj->Location) . ", " .
         db_escape($po_obj->delivery_address) . ", " .
         db_escape($po_obj->get_trans_total()). ", " .
         db_escape($po_obj->tax_included). ", " .
         db_escape($po_obj->prep_amount). ", " .
         db_escape($po_obj->final_discount) . ", " .
         db_escape($po_obj->final_discount_amount) .",
		  " .db_escape($po_auth_req) . ", " .
         db_escape($po_obj->freight_cost) .", " .
         db_escape($po_obj->additional_charges) .", " .
         db_escape($po_obj->packing_charges) .", " .
         db_escape($po_obj->other_charges) .", " .
         db_escape($po_obj->trans_type) .",
		 ". db_escape($po_obj->generated_by).",". db_escape($po_obj->comparison_code).",
		 ". db_escape($order_no).",
		 ". db_escape($po_obj->delivery_terms).",". db_escape($po_obj->purch_inco_terms).",
		 ". db_escape($po_obj->purch_type).")";

	 db_query($sql, "The purchase order header record could not be inserted");

     /*Get the auto increment value of the order number created from the sql above */
     $po_obj->order_no = $order_no;

     /*Insert the purchase order detail records */
     foreach ($po_obj->line_items as $line_no => $po_line)
     {
		$sql = "INSERT INTO ".TB_PREF."purch_order_details (trans_type,order_no, item_code, description, delivery_date,	unit_price,	quantity_ordered, discount_percent, disc_amount)  VALUES (";
		
		$sql .= $po_obj->trans_type . ", " .$po_obj->order_no . ", " . db_escape($po_line->stock_id). "," .
		db_escape($po_line->item_description). ",'" .
		date2sql($po_line->req_del_date) . "'," .
		db_escape($po_line->price) . ", " .
		db_escape($po_line->quantity). ",".db_escape($po_line->discount_percent).", " .
		db_escape($po_line->disc_amount). ")";
		db_query($sql, "One of the purchase order detail records could not be inserted");
		$po_obj->line_items[$line_no]->po_detail_rec = db_insert_id();
     }

	$Refs->save($po_obj->trans_type, $po_obj->order_no, $po_obj->reference);
	add_audit_trail($po_obj->trans_type, $po_obj->order_no, $po_obj->orig_order_date);
	hook_db_postwrite($po_obj, $po_obj->trans_type);
	commit_transaction();

	return $po_obj->order_no;
}

//ravi
function add_gen_po(&$po_obj)
{
	global $Refs;

	begin_transaction();
	hook_db_prewrite($po_obj, ST_GEN_PURCHORDER);
		
	$company_record = get_company_prefs();
	$po_auth_req_amount = str_replace(',', '', $company_record["po_auth_req_amount"]);
  
    if($po_obj->get_trans_total() > $po_auth_req_amount ){
	$po_auth_req = 1;
	}
	else if($po_obj->reference == 'auto'){
		$po_auth_req = 2;
	}else{
		$po_auth_req = 2;
	}
	
	$order_no = get_next_trans_no($po_obj->trans_type);
  
    
  
     /*Insert to purchase order header record */
     $sql = "INSERT INTO ".TB_PREF."gen_purch_orders (supplier_name, Comments, ord_date, reference, 
     	requisition_no, into_stock_location, delivery_address, total, tax_included, prep_amount,final_discount,final_discount_amount,po_auth_req,freight_cost,additional_charges,packing_charges,other_charges,trans_type,generated_by,comparison_code,order_no,delivery_terms,purch_inco_terms,purch_type,supplier_gst_no,supplier_address) VALUES(";
     $sql .= db_escape($po_obj->supplier_name) . "," .
         db_escape($po_obj->Comments) . ",'" .
         date2sql($po_obj->orig_order_date) . "', " .
		 db_escape($po_obj->reference) . ", " .
         db_escape($po_obj->supp_ref) . ", " .
         db_escape($po_obj->Location) . ", " .
         db_escape($po_obj->delivery_address) . ", " .
         db_escape($po_obj->get_trans_total()). ", " .
         db_escape($po_obj->tax_included). ", " .
         db_escape($po_obj->prep_amount). ", " .
         db_escape($po_obj->final_discount) . ", " .
         db_escape($po_obj->final_discount_amount) .",
		  " .db_escape($po_auth_req) . ", " .
         db_escape($po_obj->freight_cost) .", " .
         db_escape($po_obj->additional_charges) .", " .
         db_escape($po_obj->packing_charges) .", " .
         db_escape($po_obj->other_charges) .", " .
         db_escape($po_obj->trans_type) .",
		 ". db_escape($po_obj->generated_by).",". db_escape($po_obj->comparison_code).",
		 ". db_escape($order_no).",
		 ". db_escape($po_obj->delivery_terms).",". db_escape($po_obj->purch_inco_terms).",
		 ". db_escape($po_obj->purch_type).",
		 ". db_escape($po_obj->supplier_gst_no).",
		 ". db_escape($po_obj->supplier_address).")";

	 db_query($sql, "The purchase order header record could not be inserted");

     /*Get the auto increment value of the order number created from the sql above */
     $po_obj->order_no = $order_no;

     /*Insert the purchase order detail records */
     foreach ($po_obj->line_items as $line_no => $po_line)
     {
		$sql = "INSERT INTO ".TB_PREF."gen_purch_order_details (trans_type,order_no, item_code, description, delivery_date,	unit_price,	quantity_ordered,units, discount_percent, disc_amount)  VALUES (";
		
		$sql .= $po_obj->trans_type . ", " .$po_obj->order_no . ", " . db_escape($po_line->stock_id). "," .
		db_escape($po_line->item_description). ",'" .
		date2sql($po_line->req_del_date) . "'," .
		db_escape($po_line->price) . ", " .
		db_escape($po_line->quantity). ",".db_escape($po_line->units).",".db_escape($po_line->discount_percent).", " .
		db_escape($po_line->disc_amount). ")";
		db_query($sql, "One of the purchase order detail records could not be inserted");
		$po_obj->line_items[$line_no]->po_detail_rec = db_insert_id();
     }

	$Refs->save($po_obj->trans_type, $po_obj->order_no, $po_obj->reference);
	add_audit_trail($po_obj->trans_type, $po_obj->order_no, $po_obj->orig_order_date);
	hook_db_postwrite($po_obj, $po_obj->trans_type);
	commit_transaction();

	return $po_obj->order_no;
}
//----------------------------------------------------------------------------------------

function update_po(&$po_obj)
{
	begin_transaction();
	hook_db_prewrite($po_obj, ST_PURCHORDER);
	
	$company_record = get_company_prefs();
	$po_auth_req_amount = str_replace(',', '', $company_record["po_auth_req_amount"]);
    if($po_obj->get_trans_total() > $po_auth_req_amount ){
	
	$po_auth_req = 1;
	
	}
	else if($po_obj->reference == 'auto'){
		$po_auth_req = 2;
	}else{
		$po_auth_req = 2;
	}


    /*Update the purchase order header with any changes */
    $sql = "UPDATE ".TB_PREF."purch_orders SET Comments=" . db_escape($po_obj->Comments) . ",
		requisition_no= ". db_escape( $po_obj->supp_ref). ",
		into_stock_location=" . db_escape($po_obj->Location). ",
		ord_date='" . date2sql($po_obj->orig_order_date) . "',
		delivery_address=" . db_escape($po_obj->delivery_address).",
		total=". db_escape($po_obj->get_trans_total()).",
		prep_amount=". db_escape($po_obj->prep_amount).",
		final_discount=". db_escape($po_obj->final_discount).",
		final_discount_amount=". db_escape($po_obj->final_discount_amount).",
		po_auth_req=". db_escape($po_auth_req).",
		freight_cost=". db_escape($po_obj->freight_cost).",
		additional_charges=". db_escape($po_obj->additional_charges).",
		packing_charges=". db_escape($po_obj->packing_charges).",
		other_charges=". db_escape($po_obj->other_charges).",
		generated_by=". db_escape($po_obj->generated_by).",
		comparison_code=". db_escape($po_obj->comparison_code).",
		delivery_terms=". db_escape($po_obj->delivery_terms).",
		purch_inco_terms=". db_escape($po_obj->purch_inco_terms).",
		purch_type=". db_escape($po_obj->purch_type).",
		tax_included=". db_escape($po_obj->tax_included);
    $sql .= " WHERE order_no = " . $po_obj->order_no." AND trans_type=".db_escape($po_obj->trans_type)."";
	db_query($sql, "The purchase order could not be updated");

	$sql = "DELETE FROM ".TB_PREF."purch_order_details WHERE order_no="
		.db_escape($po_obj->order_no)." AND trans_type=".db_escape($po_obj->trans_type)."";
	db_query($sql, "could not delete old purch order details");

    /*Now Update the purchase order detail records */
    foreach ($po_obj->line_items as $po_line)
    {
    	$sql = "INSERT INTO ".TB_PREF."purch_order_details (trans_type,po_detail_item, order_no, item_code, 
    		description, delivery_date, unit_price, quantity_ordered, quantity_received, discount_percent, disc_amount) VALUES (".db_escape($po_obj->trans_type).",".db_escape($po_line->po_detail_rec ? $po_line->po_detail_rec : 0). ","
			.$po_obj->order_no . ","
			.db_escape($po_line->stock_id). ","
			.db_escape($po_line->item_description). ",'"
			.date2sql($po_line->req_del_date) . "',"
			.db_escape($po_line->price) . ", "
			.db_escape($po_line->quantity) . ", "
			.db_escape($po_line->qty_received) . ", "
			.db_escape($po_line->discount_percent) . ", "
			.db_escape($po_line->disc_amount) . ")";
		db_query($sql, "One of the purchase order detail records could not be updated");
    }

	reallocate_payments($po_obj->order_no, ST_PURCHORDER, $po_obj->orig_order_date, $po_obj->get_trans_total(), $po_obj->prepayments, $po_obj->supplier_id);

	add_audit_trail($po_obj->trans_type, $po_obj->order_no, Today(), _("Updated."));
	hook_db_postwrite($po_obj, $po_obj->trans_type);
	commit_transaction();

	return $po_obj->order_no;
}

function update_gen_po(&$po_obj)
{
	begin_transaction();
	hook_db_prewrite($po_obj, ST_GEN_PURCHORDER);
	
	$company_record = get_company_prefs();
	$po_auth_req_amount = str_replace(',', '', $company_record["po_auth_req_amount"]);
    if($po_obj->get_trans_total() > $po_auth_req_amount ){
	
	$po_auth_req = 1;
	
	}
	else if($po_obj->reference == 'auto'){
		$po_auth_req = 2;
	}else{
		$po_auth_req = 2;
	}


    /*Update the purchase order header with any changes */
    $sql = "UPDATE ".TB_PREF."gen_purch_orders SET Comments=" . db_escape($po_obj->Comments) . ",
		requisition_no= ". db_escape( $po_obj->supp_ref). ",
		into_stock_location=" . db_escape($po_obj->Location). ",
		ord_date='" . date2sql($po_obj->orig_order_date) . "',
		delivery_address=" . db_escape($po_obj->delivery_address).",
		total=". db_escape($po_obj->get_trans_total()).",
		prep_amount=". db_escape($po_obj->prep_amount).",
		final_discount=". db_escape($po_obj->final_discount).",
		final_discount_amount=". db_escape($po_obj->final_discount_amount).",
		po_auth_req=". db_escape($po_auth_req).",
		freight_cost=". db_escape($po_obj->freight_cost).",
		additional_charges=". db_escape($po_obj->additional_charges).",
		packing_charges=". db_escape($po_obj->packing_charges).",
		other_charges=". db_escape($po_obj->other_charges).",
		generated_by=". db_escape($po_obj->generated_by).",
		comparison_code=". db_escape($po_obj->comparison_code).",
		delivery_terms=". db_escape($po_obj->delivery_terms).",
		supplier_name=". db_escape($po_obj->supplier_name).",
		purch_inco_terms=". db_escape($po_obj->purch_inco_terms).",
		purch_type=". db_escape($po_obj->purch_type).",
		supplier_gst_no=". db_escape($po_obj->supplier_gst_no).",
		supplier_address=". db_escape($po_obj->supplier_address).",
		tax_included=". db_escape($po_obj->tax_included);
    $sql .= " WHERE order_no = " . $po_obj->order_no." AND trans_type=".db_escape($po_obj->trans_type)."";
	
	//display_error($sql); die;
	
	db_query($sql, "The purchase order could not be updated");

	$sql = "DELETE FROM ".TB_PREF."gen_purch_order_details WHERE order_no="
		.db_escape($po_obj->order_no)." AND trans_type=".db_escape($po_obj->trans_type)."";
	db_query($sql, "could not delete old purch order details");

    /*Now Update the purchase order detail records */
    foreach ($po_obj->line_items as $po_line)
    {
    	$sql = "INSERT INTO ".TB_PREF."gen_purch_order_details (trans_type,po_detail_item, order_no, item_code, 
    		description, delivery_date, unit_price, quantity_ordered,units, quantity_received, discount_percent, disc_amount) VALUES (".db_escape($po_obj->trans_type).",".db_escape($po_line->po_detail_rec ? $po_line->po_detail_rec : 0). ","
			.$po_obj->order_no . ","
			.db_escape($po_line->stock_id). ","
			.db_escape($po_line->item_description). ",'"
			.date2sql($po_line->req_del_date) . "',"
			.db_escape($po_line->price) . ", "
			.db_escape($po_line->quantity) . ", "
			.db_escape($po_line->units) . ", "
			.db_escape($po_line->qty_received) . ", "
			.db_escape($po_line->discount_percent) . ", "
			.db_escape($po_line->disc_amount) . ")";
		db_query($sql, "One of the purchase order detail records could not be updated");
    }

	hook_db_postwrite($po_obj, $po_obj->trans_type);
	commit_transaction();

	return $po_obj->order_no;
}

//----------------------------------------------------------------------------------------

function get_po($order_no) {
    $sql = "SELECT * FROM ".TB_PREF."purch_orders WHERE order_no = ".db_escape($order_no);
    $result = db_query($sql);

    return db_fetch($result);
}

//----------------------------------------------------------------------------------------

function read_po_header($order_no, &$order,$trans_type)
{
    	$sql = "SELECT po.*, supplier.*, loc.location_name 
		FROM ".TB_PREF."purch_orders po,"
			.TB_PREF."suppliers supplier,"
			.TB_PREF."locations loc
		WHERE po.supplier_id = supplier.supplier_id
		AND loc.loc_code = into_stock_location
		AND po.order_no = ".db_escape($order_no)." and po.trans_type = ".db_escape($trans_type)."";

   	$result = db_query($sql, "The order cannot be retrieved");

	if (db_num_rows($result) == 1)
	{

      	$myrow = db_fetch($result);
		
		
	if($myrow['trans_type'] == ST_PURCHORDER){
      	$order->trans_type = ST_PURCHORDER;
      	$order->order_no = $order_no;

      	$order->set_supplier($myrow["supplier_id"], $myrow["supp_name"], $myrow["curr_code"],
      		$myrow['tax_group_id'], $myrow["tax_included"]);

		$order->credit = get_current_supp_credit($order->supplier_id);

      	$order->orig_order_date = sql2date($myrow["ord_date"]);
      	$order->Comments = $myrow["comments"];
			
      	$order->Location = $myrow["into_stock_location"];
      	$order->supp_ref = $myrow["requisition_no"];
      	$order->reference = $myrow["reference"];
      	$order->delivery_address = $myrow["delivery_address"];
      	$order->alloc = $myrow["alloc"];
      	$order->prep_amount = $myrow["prep_amount"];
      	$order->final_discount = $myrow["final_discount"];
		$order->final_discount_amount = $myrow["final_discount_amount"];
		$order->po_auth_req = $myrow["po_auth_req"];
		
		$order->freight_cost = $myrow["freight_cost"];
		$order->additional_charges = $myrow["additional_charges"];
		$order->packing_charges = $myrow["packing_charges"];
		$order->other_charges = $myrow["other_charges"];
		
		$order->po_auth_req = $myrow["po_auth_req"];
		$order->auth_rej_remarks = $myrow["auth_rej_remarks"];
		$order->po_auth_req_date = sql2date($myrow["po_auth_req_date"]);
		$order->authrise_reject_date = sql2date($myrow["authrise_reject_date"]);
		
		$order->generated_by = $myrow['generated_by'];
		$order->comparison_code = $myrow['comparison_code'];
		
		$order->delivery_terms = $myrow['delivery_terms'];
		
		$order->authorised_by = $myrow['authorised_by'];
		$order->purch_inco_terms = $myrow['purch_inco_terms'];
		$order->purch_type = $myrow['purch_type'];
		$order->prepayments = get_payments_for($order_no, ST_PURCHORDER, $myrow["supplier_id"]);
}
		else if($myrow['trans_type'] == ST_PURCHQUOTE){ 
      	$order->trans_type = ST_PURCHQUOTE;
      	$order->order_no = $order_no;

      	$order->set_supplier($myrow["supplier_id"], $myrow["supp_name"], $myrow["curr_code"],
      		$myrow['tax_group_id'], $myrow["tax_included"]);

		$order->credit = get_current_supp_credit($order->supplier_id);

      	$order->orig_order_date = sql2date($myrow["ord_date"]);
      	$order->Comments = $myrow["comments"];
      	$order->Location = $myrow["into_stock_location"];
      	$order->supp_ref = $myrow["requisition_no"];
      	$order->reference = $myrow["reference"];
      	$order->delivery_address = $myrow["delivery_address"];
      	$order->alloc = $myrow["alloc"];
      	$order->prep_amount = $myrow["prep_amount"];
		
		$order->final_discount = $myrow["final_discount"];
		$order->final_discount_amount = $myrow["final_discount_amount"];
		$order->po_auth_req = $myrow["po_auth_req"];
		
		$order->freight_cost = $myrow["freight_cost"];
		$order->additional_charges = $myrow["additional_charges"];
		$order->packing_charges = $myrow["packing_charges"];
		$order->other_charges = $myrow["other_charges"];
		
		$order->po_auth_req = $myrow["po_auth_req"];
		$order->auth_rej_remarks = $myrow["auth_rej_remarks"];
		$order->po_auth_req_date = sql2date($myrow["po_auth_req_date"]);
		$order->authrise_reject_date = sql2date($myrow["authrise_reject_date"]);
		
		$order->generated_by = $myrow['generated_by'];
		$order->comparison_code = $myrow['comparison_code'];
		
		$order->delivery_terms = $myrow['delivery_terms'];
		
		$order->prepayments = get_payments_for($order_no, ST_PURCHQUOTE, $myrow["supplier_id"]);
		}
		else if($myrow['trans_type'] == ST_PURCHENQ){ 
      	$order->trans_type = ST_PURCHENQ;
      	$order->order_no = $order_no;

      	$order->set_supplier($myrow["supplier_id"], $myrow["supp_name"], $myrow["curr_code"],
      		$myrow['tax_group_id'], $myrow["tax_included"]);

		$order->credit = get_current_supp_credit($order->supplier_id);

      	$order->orig_order_date = sql2date($myrow["ord_date"]);
      	$order->Comments = $myrow["comments"];
      	$order->Location = $myrow["into_stock_location"];
      	$order->supp_ref = $myrow["requisition_no"];
      	$order->reference = $myrow["reference"];
      	$order->delivery_address = $myrow["delivery_address"];
      	$order->alloc = $myrow["alloc"];
      	$order->prep_amount = $myrow["prep_amount"];
		
		$order->final_discount = $myrow["final_discount"];
		$order->final_discount_amount = $myrow["final_discount_amount"];
		$order->po_auth_req = $myrow["po_auth_req"];
		
		$order->freight_cost = $myrow["freight_cost"];
		$order->additional_charges = $myrow["additional_charges"];
		$order->packing_charges = $myrow["packing_charges"];
		$order->other_charges = $myrow["other_charges"];
		
      	$order->prepayments = get_payments_for($order_no, ST_PURCHENQ, $myrow["supplier_id"]);
		
		$order->generated_by = $myrow['generated_by'];
		$order->comparison_code = $myrow['comparison_code'];
		
		$order->delivery_terms = $myrow['delivery_terms'];
		
		}
      	return true;
	}
	display_db_error("FATAL : duplicate purchase order found", "", true);
	return false;
}

//ravi
function read_gen_po_header($order_no, &$order,$trans_type)
{
    	$sql = "SELECT po.*, loc.location_name 
		FROM ".TB_PREF."gen_purch_orders po,"
			.TB_PREF."locations loc
		WHERE loc.loc_code = into_stock_location
		AND po.order_no = ".db_escape($order_no)." and po.trans_type = ".db_escape($trans_type)."";

   	$result = db_query($sql, "The order cannot be retrieved");

	if (db_num_rows($result) == 1)
	{

      	$myrow = db_fetch($result);
		
		
	if($myrow['trans_type'] == ST_GEN_PURCHORDER){
      	$order->trans_type = ST_GEN_PURCHORDER;
      	$order->order_no = $order_no;


      	$order->orig_order_date = sql2date($myrow["ord_date"]);
      	$order->Comments = $myrow["comments"];
			
      	$order->Location = $myrow["into_stock_location"];
      	$order->supp_ref = $myrow["requisition_no"];
		$order->supplier_name = $myrow["supplier_name"];
      	$order->reference = $myrow["reference"];
      	$order->delivery_address = $myrow["delivery_address"];
      	$order->alloc = $myrow["alloc"];
      	$order->prep_amount = $myrow["prep_amount"];
      	$order->final_discount = $myrow["final_discount"];
		$order->final_discount_amount = $myrow["final_discount_amount"];
		$order->po_auth_req = $myrow["po_auth_req"];
		
		$order->freight_cost = $myrow["freight_cost"];
		$order->additional_charges = $myrow["additional_charges"];
		$order->packing_charges = $myrow["packing_charges"];
		$order->other_charges = $myrow["other_charges"];
		
		$order->po_auth_req = $myrow["po_auth_req"];
		$order->auth_rej_remarks = $myrow["auth_rej_remarks"];
		$order->po_auth_req_date = sql2date($myrow["po_auth_req_date"]);
		$order->authrise_reject_date = sql2date($myrow["authrise_reject_date"]);
		
		$order->generated_by = $myrow['generated_by'];
		$order->comparison_code = $myrow['comparison_code'];
		
		$order->delivery_terms = $myrow['delivery_terms'];
		
		$order->authorised_by = $myrow['authorised_by'];
		$order->purch_inco_terms = $myrow['purch_inco_terms'];
		$order->purch_type = $myrow['purch_type'];
		
		$order->supplier_gst_no  = $myrow['supplier_gst_no'];
		$order->supplier_address = $myrow['supplier_address'];
		
		$order->prepayments = get_payments_for($order_no, ST_PURCHORDER, $myrow["supplier_id"]);
}
		else if($myrow['trans_type'] == ST_PURCHQUOTE){ 
      	$order->trans_type = ST_PURCHQUOTE;
      	$order->order_no = $order_no;

      	$order->set_supplier($myrow["supplier_id"], $myrow["supp_name"], $myrow["curr_code"],
      		$myrow['tax_group_id'], $myrow["tax_included"]);

		$order->credit = get_current_supp_credit($order->supplier_id);

      	$order->orig_order_date = sql2date($myrow["ord_date"]);
      	$order->Comments = $myrow["comments"];
      	$order->Location = $myrow["into_stock_location"];
      	$order->supp_ref = $myrow["requisition_no"];
      	$order->reference = $myrow["reference"];
      	$order->delivery_address = $myrow["delivery_address"];
      	$order->alloc = $myrow["alloc"];
      	$order->prep_amount = $myrow["prep_amount"];
		
		$order->final_discount = $myrow["final_discount"];
		$order->final_discount_amount = $myrow["final_discount_amount"];
		$order->po_auth_req = $myrow["po_auth_req"];
		
		$order->freight_cost = $myrow["freight_cost"];
		$order->additional_charges = $myrow["additional_charges"];
		$order->packing_charges = $myrow["packing_charges"];
		$order->other_charges = $myrow["other_charges"];
		
		$order->po_auth_req = $myrow["po_auth_req"];
		$order->auth_rej_remarks = $myrow["auth_rej_remarks"];
		$order->po_auth_req_date = sql2date($myrow["po_auth_req_date"]);
		$order->authrise_reject_date = sql2date($myrow["authrise_reject_date"]);
		
		$order->generated_by = $myrow['generated_by'];
		$order->comparison_code = $myrow['comparison_code'];
		
		$order->delivery_terms = $myrow['delivery_terms'];
		
		$order->supplier_gst_no  = $myrow['supplier_gst_no'];
		$order->supplier_address = $myrow['supplier_address'];
		
		$order->prepayments = get_payments_for($order_no, ST_PURCHQUOTE, $myrow["supplier_id"]);
		}
		else if($myrow['trans_type'] == ST_PURCHENQ){ 
      	$order->trans_type = ST_PURCHENQ;
      	$order->order_no = $order_no;

      	$order->set_supplier($myrow["supplier_id"], $myrow["supp_name"], $myrow["curr_code"],
      		$myrow['tax_group_id'], $myrow["tax_included"]);

		$order->credit = get_current_supp_credit($order->supplier_id);

      	$order->orig_order_date = sql2date($myrow["ord_date"]);
      	$order->Comments = $myrow["comments"];
      	$order->Location = $myrow["into_stock_location"];
      	$order->supp_ref = $myrow["requisition_no"];
      	$order->reference = $myrow["reference"];
      	$order->delivery_address = $myrow["delivery_address"];
      	$order->alloc = $myrow["alloc"];
      	$order->prep_amount = $myrow["prep_amount"];
		
		$order->final_discount = $myrow["final_discount"];
		$order->final_discount_amount = $myrow["final_discount_amount"];
		$order->po_auth_req = $myrow["po_auth_req"];
		
		$order->freight_cost = $myrow["freight_cost"];
		$order->additional_charges = $myrow["additional_charges"];
		$order->packing_charges = $myrow["packing_charges"];
		$order->other_charges = $myrow["other_charges"];
		
      	$order->prepayments = get_payments_for($order_no, ST_PURCHENQ, $myrow["supplier_id"]);
		
		$order->generated_by = $myrow['generated_by'];
		$order->comparison_code = $myrow['comparison_code'];
		
		$order->supplier_gst_no  = $myrow['supplier_gst_no'];
		$order->supplier_address = $myrow['supplier_address'];
		
		$order->delivery_terms = $myrow['delivery_terms'];
		
		}
      	return true;
	}
	display_db_error("FATAL : duplicate purchase order found", "", true);
	return false;
}
//----------------------------------------------------------------------------------------

function read_po_items($order_no, &$order, $open_items_only=false,$trans_type=false,$move='')
{
	/*now populate the line po array with the purchase order details records */

	$sql = "SELECT poline.*, units
		FROM ".TB_PREF."purch_order_details poline
			LEFT JOIN ".TB_PREF."stock_master item	ON poline.item_code=item.stock_id
		WHERE order_no =".db_escape($order_no)." and trans_type =".db_escape($trans_type);

    if ($open_items_only)
 		$sql .= " AND (poline.quantity_ordered > poline.quantity_received) ";

	$sql .= " ORDER BY po_detail_item";
	$result = db_query($sql, "The lines on the purchase order cannot be retrieved");

    if (db_num_rows($result) > 0)
    {
		while ($myrow = db_fetch($result))
        {
        	/* $data = get_purchase_data($order->supplier_id, $myrow['item_code']);
        	if ($data !== false)
        	{
        		if ($data['supplier_description'] != "")
        			$myrow['description'] = $data['supplier_description'];
        	}	 */	
            if (is_null($myrow["units"]))
            {
		       	$units = "";
            }
            else
            {
                $units = $myrow["units"];
            }
			if($move=='QUOT-ORD')
				{
					
					$item_row = get_item($myrow["item_code"]);
					if ($item_row)
					{
						
						if ($order->add_to_order($order->lines_on_order, $myrow["item_code"],
							$myrow["quantity_ordered"],$myrow["description"],
							$myrow["unit_price"],$units, sql2date($myrow["delivery_date"]),
							$myrow["qty_invoiced"], $myrow["quantity_received"], $myrow["discount_percent"], $myrow["disc_amount"])) {
							$order->line_items[$order->lines_on_order-1]->po_detail_rec = $myrow["po_detail_item"];
						}
					}
				}
				else{
					 if ($order->add_to_order($order->lines_on_order, $myrow["item_code"],
						$myrow["quantity_ordered"],$myrow["description"],
						$myrow["unit_price"],$units, sql2date($myrow["delivery_date"]),
						$myrow["qty_invoiced"], $myrow["quantity_received"], $myrow["discount_percent"], $myrow["disc_amount"])) {
						$order->line_items[$order->lines_on_order-1]->po_detail_rec = $myrow["po_detail_item"];
					}
				}
        } /* line po from purchase order details */
    } //end of checks on returned data set
}


function read_gen_po_items($order_no, &$order, $open_items_only=false,$trans_type=false,$move='')
{
	/*now populate the line po array with the purchase order details records */

	$sql = "SELECT poline.*
		FROM ".TB_PREF."gen_purch_order_details poline 
		WHERE order_no =".db_escape($order_no)." and trans_type =".db_escape($trans_type);

	$sql .= " ORDER BY po_detail_item";
	$result = db_query($sql, "The lines on the purchase order cannot be retrieved");

    if (db_num_rows($result) > 0)
    {
		while ($myrow = db_fetch($result))
        {
        	                $units = $myrow["units"];
            			 if ($order->add_to_order($order->lines_on_order, $myrow["item_code"],
						$myrow["quantity_ordered"],$myrow["description"],
						$myrow["unit_price"],$units, sql2date($myrow["delivery_date"]),
						$myrow["qty_invoiced"], $myrow["quantity_received"], $myrow["discount_percent"], $myrow["disc_amount"])) {
						$order->line_items[$order->lines_on_order-1]->po_detail_rec = $myrow["po_detail_item"];
				}
        } /* line po from purchase order details */
    } //end of checks on returned data set
}

//----------------------------------------------------------------------------------------

function read_po($order_no, &$order, $open_items_only=false,$trans_type=ST_PURCHORDER,$move='')
{
	$result = read_po_header($order_no, $order,$trans_type);
	
	if($move=="QUOT-ORD")
   {
            $comp_prefs = get_company_prefs();
			$order->Comments=$comp_prefs['shipping_terms'];
   }

	if ($result)
		read_po_items($order_no, $order, $open_items_only,$trans_type,$move);
}

function read_gen_po($order_no, &$order, $open_items_only=false,$trans_type=ST_GEN_PURCHORDER,$move='')
{
	$result = read_gen_po_header($order_no, $order,$trans_type);
	
	if($move=="QUOT-ORD")
   {
            $comp_prefs = get_company_prefs();
			$order->Comments=$comp_prefs['shipping_terms'];
   }

	if ($result)
		read_gen_po_items($order_no, $order, $open_items_only,$trans_type,$move);
}

//----------------------------------------------------------------------------------------

function get_po_items($order_no)
{
	$sql = "SELECT item_code, quantity_ordered, quantity_received, qty_invoiced
		FROM ".TB_PREF."purch_order_details
		WHERE trans_type=18 and order_no=".db_escape($order_no)
		." ORDER BY po_detail_item";

	$result = db_query($sql, "could not query purch order details");
    check_db_error("Could not check that the details of the purchase order had not been changed by another user ", $sql);
    return $result;
}
//----------------------------------------------------------------------------------------

function get_short_info($stock_id)
{
	$sql = "SELECT description, units, mb_flag
		FROM ".TB_PREF."stock_master WHERE stock_id = ".db_escape($stock_id);

	return db_query($sql,"The stock details for " . $stock_id . " could not be retrieved");
}

function get_sql_for_po_search_completed($from, $to, $supplier_id=ALL_TEXT, $location=ALL_TEXT,
	$order_number = '', $stock_id = '', $also_closed=false, $type, $purch_type=0)
{
	$sql = "SELECT 
		porder.order_no, 
		porder.reference, 
		supplier.supp_name, 
		location.location_name,
		porder.requisition_no, 
		porder.ord_date, 
		supplier.curr_code, 
		Sum((line.unit_price*line.quantity_ordered)-((line.unit_price*line.quantity_ordered)*(line.discount_percent/100))) AS OrderValue,
		Sum(line.delivery_date < '". date2sql(Today()) ."'
		AND (line.quantity_ordered > line.quantity_received)) As OverDue,
		porder.into_stock_location,
		chk.isopen,
		porder.po_auth_req
		FROM ".TB_PREF."purch_orders as porder
				LEFT JOIN (
					SELECT order_no, SUM(quantity_ordered-quantity_received + quantity_ordered-qty_invoiced) isopen
					FROM ".TB_PREF."purch_order_details
					GROUP BY order_no
				) chk ON chk.order_no=porder.order_no,"
			.TB_PREF."purch_order_details as line, "
			.TB_PREF."suppliers as supplier, "
			.TB_PREF."locations as location
		WHERE porder.order_no = line.order_no
		AND porder.trans_type=$type
		AND line.trans_type=$type
		AND porder.supplier_id = supplier.supplier_id
		AND location.loc_code = porder.into_stock_location ";

	if ($supplier_id != ALL_TEXT)
		$sql .= "AND supplier.supplier_id=".$supplier_id." ";
	if ($order_number != "")
	{
		$sql .= "AND porder.reference LIKE ".db_escape('%'. $order_number . '%');
	}
	else
	{

		$data_after = date2sql($from);
		$date_before = date2sql($to);

		$sql .= " AND porder.ord_date >= '$data_after'";
		$sql .= " AND porder.ord_date <= '$date_before'";

		if ($location != ALL_TEXT)
		{
			$sql .= " AND porder.into_stock_location = ".db_escape($location);
		}
		if ($stock_id !== '')
		{
			$sql .= " AND line.item_code=".db_escape($stock_id);
		}
		if ($supplier_id != ALL_TEXT)
			$sql .= " AND supplier.supplier_id=".db_escape($supplier_id);
		
		if ($purch_type >0 )
		{
			$sql .= " AND porder.purch_type = ".db_escape($purch_type);
		}

	}

	if($type == ST_PURCHQUOTE || $type == ST_PURCHENQ) {
		$sql .= " AND porder.conv_status !=1";
	}
		
	if (!$also_closed)
		$sql .= " AND isopen";
	$sql .= " GROUP BY porder.order_no";
	
	return $sql;
}

function get_sql_for_po_search($from, $to, $supplier_id=ALL_TEXT, $location=ALL_TEXT, $order_number='', $stock_id='')
{
	$sql = "SELECT 
		porder.order_no, 
		porder.reference,
		supplier.supp_name, 
		location.location_name,
		porder.requisition_no, 
		porder.ord_date,
		supplier.curr_code,
		Sum((line.unit_price*line.quantity_ordered)-((line.unit_price*line.quantity_ordered)*(line.discount_percent/100))) AS OrderValue,
		Sum(line.delivery_date < '". date2sql(Today()) ."'
		AND (line.quantity_ordered > line.quantity_received)) As OverDue
		FROM ".TB_PREF."purch_orders as porder,"
			.TB_PREF."purch_order_details as line, "
			.TB_PREF."suppliers as supplier, "
			.TB_PREF."locations as location
		WHERE porder.order_no = line.order_no
		AND porder.trans_type = ".ST_PURCHORDER."
		AND porder.supplier_id = supplier.supplier_id
		AND location.loc_code = porder.into_stock_location
		AND (line.quantity_ordered > line.quantity_received)
		AND porder.po_auth_req = '2'";	
	if ($order_number != "")
	{
		$sql .= "AND porder.reference LIKE ".db_escape('%'. $order_number . '%');
	}
	else
	{
		$data_after = date2sql($from);
		$data_before = date2sql($to);

		$sql .= "  AND porder.ord_date >= '$data_after'";
		$sql .= "  AND porder.ord_date <= '$data_before'";

		if ($location != ALL_TEXT)
		{
			$sql .= " AND porder.into_stock_location = ".db_escape($location);
		}

		if ($stock_id != '')
		{
			$sql .= " AND line.item_code=".db_escape($stock_id);
		}
		if ($supplier_id != ALL_TEXT)
			$sql .= " AND supplier.supplier_id=".db_escape($supplier_id);
	} //end not order number selected

	$sql .= " GROUP BY porder.order_no";

	return $sql;
}

//---------------------------------------------------------------------------------------
//PO Authorizations
function get_sql_for_po_search_authorisations($from, $to, $supplier_id=ALL_TEXT, $location=ALL_TEXT, $order_number='', $stock_id='',$type='')
{
$company_record = get_company_prefs();

	  $po_auth_req_amount = str_replace(',', '', $company_record["po_auth_req_amount"]);
	  
	
	  
	$sql = "SELECT 
		porder.order_no, 
		porder.reference,
		supplier.supp_name, 
		location.location_name,
		porder.requisition_no, 
		porder.ord_date,
		supplier.curr_code,
		/* porder.total+ porder.freight_cost+ porder.additional_charges+ porder.packing_charges+ porder.other_charges AS OrderValue, */
		 Sum((line.unit_price*line.quantity_ordered)-((line.unit_price*line.quantity_ordered)*(line.discount_percent/100))) AS OrderValue,
		Sum(line.delivery_date < '". date2sql(Today()) ."'
		AND (line.quantity_ordered > line.quantity_received)) As OverDue
		FROM ".TB_PREF."purch_orders as porder,"
			.TB_PREF."purch_order_details as line,".TB_PREF."suppliers as supplier, "
			.TB_PREF."locations as location
		WHERE porder.order_no = line.order_no
		AND porder.trans_type ='18'
		AND porder.supplier_id = supplier.supplier_id
		AND location.loc_code = porder.into_stock_location
		AND (line.quantity_ordered > line.quantity_received) 
		AND porder.po_auth_req = 1
		AND porder.reference !='auto'
		AND porder.total >".db_escape($po_auth_req_amount);

	if ($order_number != "")
	{
		$sql .= "AND porder.reference LIKE ".db_escape('%'. $order_number . '%');
	}
	else
	{
		$data_after = date2sql($from);
		$data_before = date2sql($to);

		$sql .= "  AND porder.ord_date >= '$data_after'";
		$sql .= "  AND porder.ord_date <= '$data_before'";

		if ($location != ALL_TEXT)
		{
			$sql .= " AND porder.into_stock_location = ".db_escape($location);
		}

		if ($stock_id != '')
		{
			$sql .= " AND line.item_code=".db_escape($stock_id);
		}
		if ($supplier_id != ALL_TEXT)
			$sql .= " AND supplier.supplier_id=".db_escape($supplier_id);
	} //end not order number selected

	$sql .= " GROUP BY porder.order_no";

	return $sql;
}


function update_purchase_order_authorise_reject_items($order_no,$po_auth_req,$authrise_reject_date,$auth_rej_remarks)
{
	$authrise_reject_date=date2sql($authrise_reject_date);
	
	$sql = "UPDATE ".TB_PREF."purch_orders SET 	
		po_auth_req= '3',
		authrise_reject_date=" . db_escape($authrise_reject_date) . ",
		auth_rej_remarks=" . db_escape($auth_rej_remarks) . "
		WHERE trans_type ='18'
		AND order_no = ".db_escape($order_no);
	//display_error($sql);die;
	db_query($sql, "could not be updated histo pathology request");
	
}


function update_purchase_order_authorise_items($order_no,$po_auth_req,$po_auth_req_date,$auth_rej_remarks='',$authorised_by='')
{
	$po_auth_req_date=date2sql($po_auth_req_date);
	
	$sql = "UPDATE ".TB_PREF."purch_orders SET 	
		po_auth_req= '2',
		po_auth_req_date=" . db_escape($po_auth_req_date) . ",
		auth_rej_remarks=" . db_escape($auth_rej_remarks) . ",
		authorised_by=" . db_escape($authorised_by) . "
		WHERE trans_type ='18'
		AND order_no = ".db_escape($order_no);
		
	db_query($sql, "could not be updated histo pathology request");
	
}

//--------------------------------------------------------------------------------------------
//Sales enquiry to purchase enquiry conversion 

function read_se_items(&$order,$se,$open_items_only=false)
{
	/*now populate the line po array with the purchase order details records */

	$sql = "SELECT soline.*
		FROM ".TB_PREF."sales_order_details soline
		WHERE order_no =".db_escape($se)." and trans_type=37";

	// $sql .= " ORDER BY item_number";
		
	$result = db_query($sql, "The lines on the purchase order cannot be retrieved");

   if (db_num_rows($result) > 0)
    {
		$i=0;
	while ($myrow = db_fetch($result))
        {
			
		 if ($order->add_to_order($i, $myrow["item_number"],
            	$myrow["quantity"],$myrow["description"],0,$myrow['unit'],'',0, 0, 0, 0,0,'','')) {
				$order->line_items[$i]->po_detail_rec = $myrow["stk_code"];
			}
			
		
		$i++;	
        } 
    }  //end of checks on returned data set
}
 
function get_sales_enquiry_reference($se)
{
	$sql = "SELECT reference FROM ".TB_PREF."sales_orders WHERE order_no =".db_escape($se)." and trans_type=33";
	
	$result = db_query($sql, "The lines on the purchase order cannot be retrieved");
	
	$myrow = db_fetch_row($result);
	
	return $myrow[0];
		
} 

function get_sales_enquiry_comments($se)
{
	$sql = "SELECT comments FROM ".TB_PREF."sales_orders WHERE order_no =".db_escape($se)." and trans_type=33";
	
	$result = db_query($sql, "The lines on the purchase order cannot be retrieved");
	
	$myrow = db_fetch_row($result);
	
	return $myrow[0];
		
}


//INV AGN MULTI-PO

function get_inv_po_items($po_id=0, $supplier_id="", $outstanding_only=false,
	$is_invoiced_only=false, $invoice_no=0, $begin="", $end="",$stock_id='',$sap_search_no='',$pur_ord_no = '')
{

    $sql = "SELECT po.*, po_item.*, po_item.delivery_date as req_del_date,
		
    	po_item.unit_price,
    	po_item.act_price,
    	po_item.quantity_ordered,
    	po_item.std_cost_unit, units
    	FROM	".TB_PREF."purch_orders po, "
    			.TB_PREF."purch_order_details po_item, "
    			.TB_PREF."stock_master stock_item ";
 
    if ($invoice_no != 0) {
    	$sql .= " LEFT JOIN ".TB_PREF."grn_batch_items invoice_item ON ";

		$inv_cond = "invoice_item.supp_trans_type=".ST_SUPPINVOICE." AND invoice_item.supp_trans_no ";
		if (is_array($invoice_no))
			$inv_cond .= " IN (".implode(',' , $invoice_no) . ")";
		else
			$inv_cond .= " = $invoice_no";
		$sql .= "($inv_cond) AND po_item.po_detail_item=invoice_item.po_detail_item";
	}
    $sql .= " WHERE po_item.order_no=po.order_no
		AND po.po_auth_req = '2'
		AND po_item.trans_type=po.trans_type AND po.trans_type=18 
		 AND po.supplier_id = ".db_escape($supplier_id)." ";

	if ($outstanding_only)
    	$sql .= " AND (".($invoice_no ? "$inv_cond OR ":'')."po_item.quantity_ordered - po_item.quantity_received > 0)";

	$sql .= " AND stock_item.stock_id=po_item.item_code ";

	if ($begin != "")
		$sql .= " AND po.ord_date>='".date2sql($begin)."'";
	if ($end != "")
		$sql .= " AND po.ord_date<='".date2sql($end)."'";
	if ($po_id != 0)
		$sql .= " AND po.order_no=".db_escape($po_id)
			." AND po_item.order_no=".db_escape($po_id);
	if($stock_id != ''){
	$sql .= " AND po_item.item_code = ".db_escape($stock_id);
	}
	//if ($is_invoiced_only)
		//$sql .= " AND grn_item.quantity_inv > 0";
		
	if ($pur_ord_no != "")
		$sql .= " AND po.reference =".db_escape($pur_ord_no);	

	//if ($pur_ord_no != "")
		//$sql .= " AND po.sap_search_no =".db_escape($pur_ord_no);
		
		if ($sap_search_no != "")
		$sql .= " AND po.requisition_no =".db_escape($sap_search_no);

	// $sql .= " ORDER BY po.ord_date, po.order_no, po_item.order_no";
		$sql .= " ORDER BY  po_item.po_detail_item";

	return db_query($sql, "Could not retreive GRNS");
}

function get_manual_received_qunatity($po_item_id)
{
	/* $sql ="SELECT SUM(qty_recd) qty_recd FROM ".TB_PREF."inv_grn_items WHERE po_detail_item=".db_escape($po_item_id)." GROUP BY po_detail_item"; */
	
	$sql ="SELECT SUM(grn_items.qty_recd) qty_recd FROM ".TB_PREF."inv_grn_items grn_items,".TB_PREF."inv_grn_batch grn_batch WHERE po_detail_item=".db_escape($po_item_id)." and grn_batch.id=grn_items.grn_batch_id and grn_batch.id not in (select voided.id from 0_voided voided where voided.type=125) GROUP BY po_detail_item";
	$result = db_query($sql, "The manual received quantity not retrieved");
	$myrow = db_fetch_row($result);
	return $myrow[0];
}
function get_purchasing_inco_terms($purch_inco_terms)
{
	$sql = "SELECT * FROM ".TB_PREF."shippers WHERE shipper_id=".db_escape($purch_inco_terms);

	$result = db_query($sql, "could not get shipper");
	return db_fetch($result);
}


function get_po_no_inco_terms($supplier_id,$sap_search_no='',$pur_ord_no = '')
{

    $sql = "SELECT  po.*, po_item.*, po_item.delivery_date as req_del_date,		
    	po_item.unit_price,
    	po_item.act_price,
    	po_item.quantity_ordered,
    	po_item.std_cost_unit, units
    	FROM	".TB_PREF."purch_orders po, "
    			.TB_PREF."purch_order_details po_item, "
    			.TB_PREF."stock_master stock_item ";
 
    $sql .= " WHERE po_item.order_no=po.order_no
		AND po.po_auth_req = '2'
		AND po_item.trans_type=po.trans_type AND po.trans_type=18 
		 AND po.supplier_id = ".db_escape($supplier_id)." ";

	$sql .= " AND stock_item.stock_id=po_item.item_code ";

	if ($pur_ord_no != "")
		$sql .= " AND po.reference =".db_escape($pur_ord_no);	
		
		if ($sap_search_no != "")
		$sql .= " AND po.requisition_no =".db_escape($sap_search_no);
		$sql .= " ORDER BY  po_item.po_detail_item";
		
	//	display_error($sql);

	$result =  db_query($sql, "Could not retreive GRNS");
	
	return db_fetch($result);
}


function get_sql_for_gen_po_search_completed($from, $to, $location=ALL_TEXT,
	$order_number = '')
{
	$sql = "SELECT 
		porder.order_no, 
		porder.reference, 
		porder.supplier_name,  
		location.location_name,
		porder.requisition_no, 
		porder.ord_date, 
		Sum((line.unit_price*line.quantity_ordered)-((line.unit_price*line.quantity_ordered)*(line.discount_percent/100))) AS OrderValue,
		Sum(line.delivery_date < '". date2sql(Today()) ."'
		AND (line.quantity_ordered > line.quantity_received)) As OverDue,
		porder.into_stock_location
		FROM ".TB_PREF."gen_purch_orders as porder,"
			.TB_PREF."gen_purch_order_details as line, "
			.TB_PREF."locations as location
		WHERE porder.order_no = line.order_no
		AND porder.trans_type=180
		AND line.trans_type=180
		AND location.loc_code = porder.into_stock_location ";

	if ($order_number != "")
	{
		$sql .= "AND porder.reference LIKE ".db_escape('%'. $order_number . '%');
	}
	else
	{

		$data_after = date2sql($from);
		$date_before = date2sql($to);

		$sql .= " AND porder.ord_date >= '$data_after'";
		$sql .= " AND porder.ord_date <= '$date_before'";

		if ($location != ALL_TEXT)
		{
			$sql .= " AND porder.into_stock_location = ".db_escape($location);
		}

	}

	$sql .= " GROUP BY porder.order_no";
		
	return $sql;
}