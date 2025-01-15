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
include_once($path_to_root . "/purchasing/includes/supp_trans_class.inc");

include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/inventory.inc");

include_once($path_to_root . "/includes/date_functions.inc");

include_once($path_to_root . "/includes/db/allocations_db.inc");

include_once($path_to_root . "/purchasing/includes/db/supp_trans_db.inc");
include_once($path_to_root . "/purchasing/includes/db/po_db.inc");
include_once($path_to_root . "/purchasing/includes/db/grn_db.inc");
include_once($path_to_root . "/purchasing/includes/db/invoice_db.inc");
include_once($path_to_root . "/purchasing/includes/db/suppalloc_db.inc");
include_once($path_to_root . "/purchasing/includes/db/supp_payment_db.inc");
include_once($path_to_root . "/purchasing/includes/db/suppliers_db.inc");
include_once($path_to_root . "/purchasing/includes/db/supp_pdc_db.inc");
//-------------------------------------------------------------------------------------------------------------

// add a supplier-related gl transaction
// $date_ is display date (non-sql)
// $amount is in SUPPLIERS'S currency

function add_gl_trans_supplier($type, $type_no, $date_, $account, $dimension, $dimension2,  
	$amount, $supplier_id, $err_msg="", $rate=0, $memo="")
{
	if ($err_msg == "")
		$err_msg = "The supplier GL transaction could not be inserted";	
		
	return add_gl_trans($type, $type_no, $date_, $account, $dimension, $dimension2, $memo, 
		$amount, get_supplier_currency($supplier_id), 
		PT_SUPPLIER, $supplier_id, $err_msg, $rate);
}
function add_gl_trans_suppliers($type, $type_no, $date_, $account, $dimension, $dimension2,  
	$amount, $supplier_id, $err_msg="", $rate=0, $memo="")
{
	if ($err_msg == "")
		$err_msg = "The supplier GL transaction could not be inserted";	
		
	return add_gl_transs($type, $type_no, $date_, $account, $dimension, $dimension2, $memo, 
		$amount, get_supplier_currency($supplier_id), 
		PT_SUPPLIER, $supplier_id, $err_msg, $rate);
}

//----------------------------------------------------------------------------------------

function get_purchase_price($supplier_id, $stock_id)
{
	$sql = "SELECT price, conversion_factor FROM ".TB_PREF."purch_data 
		WHERE supplier_id = ".db_escape($supplier_id) . " 
		AND stock_id = ".db_escape($stock_id);
	$result = db_query($sql, "The supplier pricing details for " . $stock_id . " could not be retrieved");    

	if (db_num_rows($result) == 1)
	{
		$myrow = db_fetch($result);
		return $myrow["price"] / $myrow['conversion_factor'];
	} 
	else 
	{
		return 0;
	}	
}

function get_purchase_conversion_factor($supplier_id, $stock_id)
{
	$sql = "SELECT conversion_factor FROM ".TB_PREF."purch_data 
		WHERE supplier_id = ".db_escape($supplier_id)." 
		AND stock_id = ".db_escape($stock_id);
	$result = db_query($sql, "The supplier pricing details for " . $stock_id . " could not be retrieved");    

	if (db_num_rows($result) == 1)
	{
		$myrow = db_fetch($result);
		return $myrow['conversion_factor'];
	} 
	else 
	{
		return 1;
	}	
}
//----------------------------------------------------------------------------------------

function get_purchase_data($supplier_id, $stock_id)
{
	$sql = "SELECT * FROM ".TB_PREF."purch_data 
		WHERE supplier_id = ".db_escape($supplier_id) . "
		AND stock_id = ".db_escape($stock_id);
	$result = db_query($sql, "The supplier pricing details for " . $stock_id . " could not be retrieved");    

	return db_fetch($result);
}

function add_or_update_purchase_data($supplier_id, $stock_id, $price, $description="", $uom="")
{
	 /* $data = get_purchase_data($supplier_id, $stock_id);

	$description = substr($description, 0, 50); // FIXME: temporary constraint to avoid db errors on long descriptions, see [0005170]
	if ($data === false)
	{
		$sql = "INSERT INTO ".TB_PREF."purch_data (supplier_id, stock_id, price, suppliers_uom,
			conversion_factor, supplier_description) VALUES (".db_escape($supplier_id)
			.", ".db_escape($stock_id).", ".db_escape($price).", "
			.db_escape($uom).", 1, ".db_escape($description).")";
		db_query($sql,"The supplier purchasing details could not be added");
		return;
	}	
	$price = round($price * $data['conversion_factor'], user_price_dec());
	$sql = "UPDATE ".TB_PREF."purch_data SET price=".db_escape($price);
	if ($uom != "")
		$sql .= ",suppliers_uom=".db_escape($uom);
	if ($description != "")	
		$sql .= ",supplier_description=".db_escape($description);
	$sql .= " WHERE stock_id=".db_escape($stock_id)." AND supplier_id=".db_escape($supplier_id);
	db_query($sql,"The supplier purchasing details could not be updated"); */
	return true;
}

function get_po_prepayments($supp_trans)
{
	// collect purchase order line ids
	$allocations = array();
	$line_ids = array();
	foreach($supp_trans->grn_items as $item)
		if ($item->po_detail_item)
			$line_ids[] = $item->po_detail_item;

	if (!count($line_ids))
		return $allocations;

	$sql = "SELECT DISTINCT allocs.*
		FROM ".TB_PREF."supp_allocations allocs 
			LEFT JOIN ".TB_PREF."purch_order_details line ON line.order_no=allocs.trans_no_to AND trans_type_to=".ST_PURCHORDER."
		WHERE line.po_detail_item IN(".implode(',', array_values($line_ids)).")";

	$result = db_query($sql, "Cannot retrieve po prepayments");

	while($dat = db_fetch($result))
	{
		$allocations[] = $dat;
	}

	return $allocations;
}

//---------------------------------------------------------------------------------------------------
//
//	Add Purchase Order, GRN or Purchase Invoice with parent auto documents (if any)
//
function add_direct_supp_trans($cart)
{
	global $Refs, $type_shortcuts;

	if (($cart->trans_type != ST_PURCHORDER) && ($cart->trans_type != ST_PURCHQUOTE)  && ($cart->trans_type != ST_PURCHENQ)) {
		// for direct grn/invoice set same dates for lines as for whole document
		foreach ($cart->line_items as $line_no =>$line)
			$cart->line_items[$line_no]->req_del_date = $cart->orig_order_date;
	}
		
	$ref = $cart->reference;
	$actual_trans_type=$cart->trans_type;
	
	if (($cart->trans_type != ST_PURCHORDER) && ($cart->trans_type != ST_PURCHQUOTE) && 
	($cart->trans_type != ST_PURCHENQ)) {
		$cart->reference = 'auto';
		$actual_trans_type=$cart->trans_type;
		$cart->trans_type=ST_PURCHORDER;
		begin_transaction();	// all db changes as single transaction for direct document
	}
	$order_no = add_po($cart);
	$cart->trans_type=$actual_trans_type;
   	$cart->order_no = $order_no;	

	if ($cart->trans_type == ST_PURCHORDER || $cart->trans_type == ST_PURCHQUOTE 
	|| $cart->trans_type == ST_PURCHENQ)
		return $order_no;
		

	//Direct GRN
	if ($cart->trans_type == ST_SUPPRECEIVE)
		$cart->reference = $ref;
	if ($cart->trans_type != ST_SUPPINVOICE)
		$cart->Comments = $cart->reference; //grn does not hold supp_ref
	foreach($cart->line_items as $key => $line)
		$cart->line_items[$key]->receive_qty = $line->quantity;
	$grn_no = add_grn($cart);
	if ($cart->trans_type == ST_SUPPRECEIVE) {
		commit_transaction(); // save PO+GRN
		return $grn_no;
	}
	//	Direct Purchase Invoice
	$inv = new supp_trans(ST_SUPPINVOICE);
	$inv->Comments = $cart->Comments;
	$inv->supplier_id = $cart->supplier_id;
	$inv->tran_date = $cart->orig_order_date;
	$inv->due_date = $cart->due_date;
	$inv->dimension = $cart->dimension;
	$inv->dimension2 = $cart->dimension2;
	$inv->reference = $ref;
	$inv->supp_reference = $cart->supp_ref;
	$inv->tax_included = $cart->tax_included;
	$supp = get_supplier($cart->supplier_id);
	$inv->tax_group_id = $supp['tax_group_id'];
	$inv->ov_amount = $inv->ov_gst = $inv->ov_discount = 0;
	
	$inv->freight_cost = $cart->freight_cost;
	$inv->additional_charges = $cart->additional_charges;
	$inv->packing_charges = $cart->packing_charges;
	$inv->other_charges = $cart->other_charges;
	$inv->ov_roundoff = $cart->ov_roundoff;
	
	$inv->rc_supplier_id = $cart->rc_supplier_id;
	$inv->rc_supplier_invoice_id = $cart->rc_supplier_invoice_id;
	$inv->rc_invoice_date = $cart->rc_invoice_date;
	$inv->rc_invoice_amount = $cart->rc_invoice_amount;
	$inv->reverse_charge = $cart->reverse_charge;
	$inv->rc_bill_no = $cart->rc_bill_no;
	$inv->rc_custom_total = $cart->rc_custom_total;
	$inv->rc_curr_code = $cart->rc_curr_code;
	
	$inv->bill_date = $cart->bill_date;
	
	$inv->declaration_no   = $cart->declaration_no;
	$inv->cif_value        = $cart->cif_value;
	$inv->vat_import_value = $cart->vat_import_value;
	$inv->custom_duties    = $cart->custom_duties;
	
	$inv->container_number  = $cart->container_number;
	$inv->bl_no             = $cart->bl_no;
	$inv->purch_inco_terms  = $cart->purch_inco_terms;
	$inv->purch_type  = $cart->purch_type;
	
	$total = 0;
		foreach($cart->line_items as $key => $line) {
		$inv->add_grn_to_trans($line->grn_item_id, $line->po_detail_rec, $line->stock_id,
			$line->item_description, $line->receive_qty, 0, $line->receive_qty,
			$line->price, $line->price, true, get_unit_cost($line->stock_id), '',$line->discount_percent,$line->line_dimension,$line->rc_supplier_id_rev,$line->rc_supplier_invoice_id_rev);
		//$total += round2(($line->receive_qty * $line->price), user_price_dec());
		$total += round2(($line->receive_qty * $line->price*(1-$line->discount_percent)), user_price_dec());	
	}
	$inv->tax_overrides = $cart->tax_overrides;
	if (!$inv->tax_included) {
		$taxes = $inv->get_taxes($inv->tax_group_id, 0, false);
		foreach( $taxes as $taxitem) {
			$total += isset($taxitem['Override']) ? $taxitem['Override'] : $taxitem['Value'];
		}
	}
	$inv->currency = $cart->curr_code;
	$inv->ex_rate = $cart->ex_rate;

	$inv_no = add_supp_invoice($inv);

	if ($cart->cash_account) {
		$pmt_no = write_supp_payment(0, $inv->supplier_id, $cart->cash_account,	$inv->tran_date, $Refs->get_next(ST_SUPPAYMENT, null, $inv->tran_date), 
			$total, 0, _('Payment for:').$inv->supp_reference .' ('.$type_shortcuts[ST_SUPPINVOICE].$inv_no.')' . ' ' . $cart->Comments, 0, 0, $cart->dimension, $cart->dimension2);
		add_supp_allocation($total, ST_SUPPAYMENT, $pmt_no, ST_SUPPINVOICE, $inv_no, $inv->supplier_id, $inv->tran_date);
		update_supp_trans_allocation(ST_SUPPINVOICE, $inv_no, $inv->supplier_id);
		update_supp_trans_allocation(ST_SUPPAYMENT, $pmt_no, $inv->supplier_id);
	}
	commit_transaction(); // save PO+GRN+PI(+SP)
	return $inv_no;
}

//ravi
function add_gen_direct_supp_trans($cart)
{
	global $Refs, $type_shortcuts;
	$ref = $cart->reference;
	$actual_trans_type=$cart->trans_type;
	$order_no = add_gen_po($cart);
	$cart->trans_type=$actual_trans_type;
   	$cart->order_no = $order_no;	
	
	return $order_no;
}
