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
//------------------- update average material cost ---------------------------------------------- 
function update_average_material_cost($supplier, $stock_id, $price, $qty, $date, $adj_only=false,
$conversion=1)
{
	// probably this function should be optimized
	// passing transaction cart as argument. This would
	// save a couple of db calls like get_supplier()
	
	if($conversion==0)
   {
	 $conversion=1;
   }
   
   $supp = get_supplier($supplier);
    if ($supplier != null) {
		$currency = $supp['curr_code'];
        if ($supp['tax_included'])
            $price = get_tax_free_price_for_item($stock_id, $price, $supp['tax_group_id'],
                $supp['tax_included']);
    } else
		$currency = null;
		
		
	if ($currency != null)
	{
		$ex_rate = get_exchange_rate_to_home_currency($currency, $date);
		
		$price_in_home_currency = $price / $ex_rate;
	}	
	else
		$price_in_home_currency = $price;

	$price_in_home_currency_ = $price_in_home_currency;	
	

	// Handle if inventory will become negative
    // Skip negative inventory adjustment for case of adjust_only
    if (is_inventory_item($stock_id) && !$adj_only) 
        handle_negative_inventory($stock_id, $qty, $price_in_home_currency, $date);
	
	$sql = "SELECT mb_flag, material_cost FROM ".TB_PREF."stock_master WHERE stock_id=".db_escape($stock_id);
	$result = db_query($sql);
	$myrow = db_fetch($result);
	$material_cost = $myrow['material_cost'];
	
	$cost_adjust = false;

	$qoh = get_qoh_on_date($stock_id);
	
	
	if ($adj_only)
	{
	
		if ($qoh > 0)
			$material_cost = (($qoh * $material_cost + $qty * $price_in_home_currency * $conversion)) /	$qoh;
			
	}
	else
	{
		if ($qoh < 0)
		{
			if ($qoh + $qty >= 0)
				$cost_adjust = true;
			$qoh = 0;
		}
		
	//display_error($qoh.'--'.$material_cost.'--'.$qty.'--'.$price_in_home_currency.'--'.$conversion);
		if ($qoh + $qty > 0){
			$material_cost = ($qoh * $material_cost + $qty * $price_in_home_currency * $conversion) /	($qoh + $qty);
			}
	}
	
	
	
	if ($cost_adjust) // Material_cost replaced with price
		adjust_deliveries($stock_id, $price_in_home_currency_, $date);
	$sql = "UPDATE ".TB_PREF."stock_master SET material_cost=".db_escape($material_cost)."
		WHERE stock_id=".db_escape($stock_id);
		
		
	//display_error($sql); die;	

	db_query($sql,"The cost details for the inventory item could not be updated");
	return $material_cost;
}
//-------------------------------------------------------------------------------------------------------------

function add_grn(&$po)
{
	global $Refs;

	$date_ = $po->orig_order_date;

	begin_transaction();
	hook_db_prewrite($po, ST_SUPPRECEIVE);

	if (!is_company_currency($po->curr_code))
	{
		if (!$po->ex_rate)
			$po->ex_rate = get_date_exchange_rate($po->curr_code, $date_);
		else
			add_new_exchange_rate($po->curr_code, $date_, $po->ex_rate);
	} else
		$po->ex_rate = 1;

	$grn = add_grn_batch($po->order_no, $po->supplier_id, $po->reference, $po->Location, $date_, $po->ex_rate,$po->freight_cost,$po->additional_charges,$po->packing_charges,
	$po->other_charges,
	$po->declaration_no,$po->cif_value,$po->vat_import_value,$po->custom_duties,
	$po->container_number,$po->bl_no);

    $clearing_act = get_company_pref('grn_clearing_act');
	if ($clearing_act) {	// otherwise GRN clearing account is not used
	    $total = 0;
	}
	
	
	//Item Standard cost
	$items_total_inc_tax = $po->get_trans_total();
	
	$items_total_exclude_taxes =  $po->get_trans_total_exclude_taxes();
	
	$conv = (($items_total_exclude_taxes+$po->freight_cost+$po->additional_charges+$po->packing_charges+$po->other_charges)*($po->ex_rate))/($items_total_exclude_taxes*$po->ex_rate);
	
	$conversion_factor = $conv;
	
	
	foreach ($po->line_items as $line_no => $order_line)
	{
		if ($order_line->receive_qty != 0 && $order_line->receive_qty != "" && isset($order_line->receive_qty))
		{
			$stock_gl_code = get_stock_gl_code($order_line->stock_id);

			/*Update sales_order_details for the new quantity received and the standard cost used for postings to GL 
			  and recorded in the stock movements for FIFO/LIFO stocks valuations*/
			//------------------- update average material cost and clearing account --------------------------------
			if (is_inventory_item($order_line->stock_id))
			{
				if ($clearing_act)
					$total += add_gl_trans_supplier(ST_SUPPRECEIVE, $grn, $date_, $stock_gl_code["inventory_account"],
						$stock_gl_code['dimension_id'], $stock_gl_code['dimension2_id'],
					    $order_line->taxfree_charge_value($po), $po->supplier_id, '', 0, $order_line->stock_id);
						
                    update_average_material_cost($po->supplier_id, $order_line->stock_id, ($order_line->price*(1-($order_line->discount_percent/100))),
					$order_line->receive_qty, $date_,'',$conversion_factor);
			}
			//------------------------------------------------------------------------------------------------------
			if ($order_line->qty_received == 0)
			{
				/*This must be the first receipt of goods against this line */
			   	/*Need to get the standard cost as it is now so we can process GL jorunals later*/
			   	$order_line->standard_cost = get_unit_cost($order_line->stock_id);
			}
			// Update the purchase data table
			add_or_update_purchase_data($po->supplier_id, $order_line->stock_id, $order_line->price, 
				$order_line->item_description); 

			/*Need to insert a grn item */ // also need to check for over-receive.(within allowance)
			if ($order_line->receive_qty + $order_line->qty_received > $order_line->quantity)
				$order_line->quantity = $order_line->receive_qty + $order_line->qty_received;
				
				
			$item_std_price = 	($order_line->taxfree_charge_value($po)/$order_line->receive_qty)*$conv;		
				
			$grn_item = add_grn_detail_item($grn, $order_line->po_detail_rec,
				$order_line->stock_id, $order_line->item_description,
				$order_line->standard_cost,	$order_line->receive_qty, $order_line->price, $order_line->quantity,$order_line->discount_percent,$order_line->standard_cost);

			$po->line_items[$line_no]->grn_item_id = $grn_item;
			/* Update location stock records - NB  a po cannot be entered for a service/kit parts done automatically */
			
			add_stock_move(ST_SUPPRECEIVE, $order_line->stock_id, $grn, $po->Location, $date_, "",
			 	$order_line->receive_qty, $order_line->standard_cost, $item_std_price);	
			
			

		} /*quantity received is != 0 */
	} /*end of order_line loop */

	if ($clearing_act && $total != 0.0) {
		$accs = get_supplier_accounts($po->supplier_id);
		$total += add_gl_trans_supplier(ST_SUPPRECEIVE, $grn, $date_, $clearing_act,
			$accs['dimension_id'], $accs['dimension2_id'], -$total, null);
	}
	$Refs->save(ST_SUPPRECEIVE, $grn, $po->reference);

	add_audit_trail(ST_SUPPRECEIVE, $grn, $date_);

	$po->trans_no = $grn;
	hook_db_postwrite($po, ST_SUPPRECEIVE);
	commit_transaction();

	return $grn;
}

//----------------------------------------------------------------------------------------

function add_grn_batch($po_number, $supplier_id, $reference, $location, $date_, $rate,
$freight_cost=0,$additional_charges=0,$packing_charges=0,$other_charges=0,
$declaration_no='',$cif_value=0,$vat_import_value=0,$custom_duties=0,
$container_number='',$bl_no='')
{
	$date = date2sql($date_);

	$sql = "INSERT INTO ".TB_PREF."grn_batch (purch_order_no, delivery_date, supplier_id, reference, loc_code, rate,freight_cost,additional_charges,packing_charges,other_charges,
	declaration_no,cif_value,vat_import_value,custom_duties,container_number,bl_no)
			VALUES (".db_escape($po_number).", ".db_escape($date).", "
			.db_escape($supplier_id).", ".db_escape($reference).", ".db_escape($location).", ".db_escape($rate).", ".db_escape($freight_cost).", ".db_escape($additional_charges).", ".db_escape($packing_charges).", ".db_escape($other_charges).",
			".db_escape($declaration_no).",".db_escape($cif_value).",
		    ".db_escape($vat_import_value).",".db_escape($custom_duties).",
			".db_escape($container_number).",".db_escape($bl_no).")";
			
			//display_error($sql); 
	db_query($sql, "A grn batch record could not be inserted.");

	return db_insert_id();
}

//-------------------------------------------------------------------------------------------------------------

function add_grn_detail_item($grn_batch_id, $po_detail_item, $item_code, $description, $standard_unit_cost,
	$quantity_received, $price, $quantity,$discount_percent=0,$item_standard_price=0)
{
	$sql = "UPDATE ".TB_PREF."purch_order_details
        SET quantity_received = quantity_received + ".db_escape($quantity_received).",
        std_cost_unit=".db_escape($standard_unit_cost).",
        quantity_ordered=".db_escape($quantity).",
        act_price=".db_escape($price)."
        WHERE po_detail_item = ".db_escape($po_detail_item);
	db_query($sql, "a purchase order details record could not be updated. This receipt of goods has not been processed ");

	$sql = "INSERT INTO ".TB_PREF."grn_items (grn_batch_id, po_detail_item, item_code, description, qty_recd,discount_percent,item_standard_price)
		VALUES (".db_escape($grn_batch_id).", "
		.db_escape($po_detail_item).", ".db_escape($item_code).", ".db_escape($description)
		.", ".db_escape($quantity_received).",".db_escape($discount_percent).",
		".db_escape($item_standard_price).")";

	db_query($sql, "A GRN detail item could not be inserted.");

	return db_insert_id();
}

//----------------------------------------------------------------------------------------
function get_grn_batch_from_item($item)
{
	$sql = "SELECT grn_batch_id FROM ".TB_PREF."grn_items WHERE id=".db_escape($item);
	$result = db_query($sql, "Could not retreive GRN batch id");
	$row = db_fetch_row($result);
	return $row[0];
}

function get_grn_batch($grn)
{
	$sql = "SELECT * FROM ".TB_PREF."grn_batch WHERE id=".db_escape($grn);
	$result = db_query($sql, "Could not retreive GRN batch id");
	return db_fetch($result);
}

function get_po_order($id){
$sql = "SELECT * FROM ".TB_PREF."purch_order_details WHERE po_detail_item=".db_escape($id)."";
$res = db_query($sql);
$result = db_fetch($res);
return $result;

}

function set_direct_grn_item_credited(&$entered_grn, $supplier, $transno, $date,$loc_code)
{
	
	
	
	$mcost = update_average_material_cost($supplier, $entered_grn->item_code,
		$entered_grn->chg_price, $entered_grn->this_quantity_inv, $date);

    add_stock_move(ST_SUPPCREDIT, $entered_grn->item_code, $transno, $loc_code, $date, "",
       	$entered_grn->this_quantity_inv, $mcost, $entered_grn->chg_price);
		
		
}

function set_grn_item_credited(&$entered_grn, $supplier, $transno, $date)
{
	
		
	$mcost = update_average_material_cost($supplier, $entered_grn->item_code,
		$entered_grn->chg_price*(1-($entered_grn->discount_percent/100)), $entered_grn->this_quantity_inv, $date);		

 	$sql = "SELECT grn.*, item.*
    	FROM ".TB_PREF."grn_batch grn,"
    		.TB_PREF."grn_items item
    	WHERE item.grn_batch_id=grn.id
		AND item.id=".db_escape($entered_grn->id)."
    	AND item.item_code=".db_escape($entered_grn->item_code);

	$result = db_query($sql, "Could not retreive GRNS");
	$myrow = db_fetch($result);

	$sql = "UPDATE ".TB_PREF."purch_order_details
        SET quantity_received = quantity_received + "
        	.db_escape($entered_grn->this_quantity_inv).",
        quantity_ordered = quantity_ordered + "
        .db_escape($entered_grn->this_quantity_inv).",
        qty_invoiced = qty_invoiced + ".db_escape($entered_grn->this_quantity_inv).",
        std_cost_unit=".db_escape($mcost).",
        act_price=".db_escape($entered_grn->chg_price)."
        WHERE po_detail_item = ".$myrow["po_detail_item"];
		
	db_query($sql, "a purchase order details record could not be updated. This receipt of goods has not been processed ");


	//$sql = "UPDATE ".TB_PREF."grn_items SET qty_recd=0, quantity_inv=0 WHERE id=$entered_grn->id";
	$sql = "UPDATE ".TB_PREF."grn_items SET qty_recd=qty_recd+".db_escape($entered_grn->this_quantity_inv)
	.",quantity_inv=quantity_inv+".db_escape($entered_grn->this_quantity_inv)
	." WHERE id=".db_escape($entered_grn->id);
	db_query($sql);

    add_stock_move(ST_SUPPCREDIT, $entered_grn->item_code, $transno, $myrow['loc_code'], $date, "",
       	$entered_grn->this_quantity_inv, $mcost, $entered_grn->chg_price);
			
}

function get_grn_items($grn_batch_id=0, $supplier_id="", $outstanding_only=false,
	$is_invoiced_only=false, $invoice_no=0, $begin="", $end="")
{
    $sql = "SELECT grn.*, grn_item.*, po_item.delivery_date as req_del_date,
		grn_item.quantity_inv".($invoice_no ? "-invoice_item.quantity" : '')."  quantity_inv,
    	po_item.unit_price,
    	po_item.act_price,
    	po_item.quantity_ordered,
    	po_item.std_cost_unit, units
    	FROM	".TB_PREF."grn_batch grn, "
    			.TB_PREF."purch_order_details po_item, "
    			.TB_PREF."stock_master stock_item,"
	   			.TB_PREF."grn_items grn_item ";
 
    if ($invoice_no != 0) {
    	$sql .= " LEFT JOIN ".TB_PREF."supp_invoice_items invoice_item ON ";

		$inv_cond = "invoice_item.supp_trans_type=".ST_SUPPINVOICE." AND invoice_item.supp_trans_no ";
		if (is_array($invoice_no))
			$inv_cond .= " IN (".implode(',' , $invoice_no) . ")";
		else
			$inv_cond .= " = $invoice_no";
		$sql .= "($inv_cond) AND grn_item.id=invoice_item.grn_item_id";
	}
    $sql .= " WHERE grn_item.grn_batch_id=grn.id
		AND grn_item.po_detail_item=po_item.po_detail_item";

	if ($outstanding_only)
    	$sql .= " AND (".($invoice_no ? "$inv_cond OR ":'')."grn_item.qty_recd - grn_item.quantity_inv > 0)";

	$sql .= " AND stock_item.stock_id=grn_item.item_code ";

	if ($begin != "")
		$sql .= " AND grn.delivery_date>='".date2sql($begin)."'";
	if ($end != "")
		$sql .= " AND grn.delivery_date<='".date2sql($end)."'";
	if ($grn_batch_id != 0)
		$sql .= " AND grn.id=".db_escape($grn_batch_id)
			." AND grn_item.grn_batch_id=".db_escape($grn_batch_id);

	if ($is_invoiced_only)
		$sql .= " AND grn_item.quantity_inv > 0";

	if ($supplier_id != "")
		$sql .= " AND grn.supplier_id =".db_escape($supplier_id);

	$sql .= " ORDER BY grn.delivery_date, grn.id, grn_item.id";

	return db_query($sql, "Could not retreive GRNS");
}

//----------------------------------------------------------------------------------------

// get the details for a given grn item

function get_grn_item_detail($grn_item_no)
{
	$sql = "SELECT grn.*, po.unit_price, grn.qty_recd - grn.quantity_inv AS QtyOstdg,
    		po.std_cost_unit
		FROM ".TB_PREF."grn_items grn,"
			.TB_PREF."purch_order_details po,"
			.TB_PREF."stock_master item
		WHERE grn.po_detail_item=po.po_detail_item
 			AND item.stock_id=grn.item_code
			AND grn.id=".db_escape($grn_item_no);

	$result = db_query($sql, "could not retreive grn item details");
	return db_fetch($result);
}

//----------------------------------------------------------------------------------------

function read_grn_items_to_order($grn_batch, &$order)
{
	$result = get_grn_items($grn_batch);

  	if (db_num_rows($result) > 0)
  	{

	 	while ($myrow = db_fetch($result))
	 	{
	 		if ($myrow['qty_recd'] == 0 && $myrow['quantity_inv'] == 0)
				continue; // We will not have empty credited rows.
		 	if (is_null($myrow["units"]))
		 	{
				$units = "";
		 	}
		 	else
		 	{
				$units = $myrow["units"];
		 	}

			$order->add_to_order($order->lines_on_order, $myrow["item_code"],
				$myrow["qty_recd"],$myrow["description"], $myrow["unit_price"],$units,
				sql2date($myrow["req_del_date"]), $myrow["quantity_inv"],
				$myrow["qty_recd"],$myrow["discount_percent"]);

			$order->line_items[$order->lines_on_order-1]->po_detail_rec = $myrow["po_detail_item"];
		} /* line po from purchase order details */
  	} //end of checks on returned data set
}

//----------------------------------------------------------------------------------------

// read a grn into an order class

function read_grn($grn_batch, &$order)
{
	$sql= "SELECT *	FROM ".TB_PREF."grn_batch WHERE id=".db_escape($grn_batch);
	
	$result = db_query($sql, "The grn sent is not valid");

	$row = db_fetch($result);
	$po_number = $row["purch_order_no"];
	$order->ex_rate = $row["rate"];
	//ravi
	if($po_number==-1)
	{
	
		$grnsql = "SELECT dts.order_no FROM ".TB_PREF."grn_items as gi LEFT JOIN ".TB_PREF."purch_order_details as dts ON dts.po_detail_item = gi.po_detail_item WHERE grn_batch_id=".db_escape($grn_batch)." AND gi.po_detail_item != '-1' limit 1";
	$grnres = db_query($grnsql);
	$grnresult = db_fetch_row($grnres);
	
	$result = read_po_header($grnresult['0'], $order,18);
	    $order->trans_type = ST_SUPPRECEIVE;
		$order->orig_order_date = sql2date($row["delivery_date"]);
		$order->Location = $row["loc_code"];
		$order->reference = $row["reference"];
		
		$order->credit_period = $row["credit_period"];
		$order->consgn_recv_date = sql2date($row["consgn_recv_date"]);
		$order->cleared_date = sql2date($row["cleared_date"]);
		$order->lpo_date = sql2date($row["lpo_date"]);
		$order->lpo_no = $row["lpo_no"];
		
		$order->freight_cost = $row["freight_cost"];
		$order->additional_charges = $row["additional_charges"];
		$order->packing_charges = $row["packing_charges"];
		$order->other_charges = $row["other_charges"];		
		read_grn_items_to_order($grn_batch, $order);
	}
	else
	{
	$result = read_po_header($po_number, $order,18);

		if ($result)
		{

			$order->trans_type = ST_SUPPRECEIVE;
			$order->orig_order_date = sql2date($row["delivery_date"]);
			$order->Location = $row["loc_code"];
			$order->reference = $row["reference"];
			$order->freight_cost = $row["freight_cost"];
			$order->additional_charges = $row["additional_charges"];
			$order->packing_charges = $row["packing_charges"];
			$order->other_charges = $row["other_charges"];

			read_grn_items_to_order($grn_batch, $order);
		}
	}
}

//----------------------------------------------------------------------------------------------------------

// get the GRNs (batch info not details) for a given po number

function get_po_grns($po_number)
{
    $sql = "SELECT * FROM ".TB_PREF."grn_batch WHERE purch_order_no=".db_escape($po_number);

	return db_query($sql, "The grns for the po $po_number could not be retreived");
}

//----------------------------------------------------------------------------------------------------------

function exists_grn($grn_batch)
{
	$sql = "SELECT id FROM ".TB_PREF."grn_batch WHERE id=".db_escape($grn_batch);
	$result = db_query($sql, "Cannot retreive a grn");

    return (db_num_rows($result) > 0);
}

//----------------------------------------------------------------------------------------------------------

function exists_grn_on_invoices($grn_batch)
{
	$sql = "SELECT inv.id
		FROM ".TB_PREF."supp_invoice_items inv,"
			.TB_PREF."grn_items grn
		WHERE inv.grn_item_id=grn.id
		AND quantity != 0
		AND grn_batch_id=".db_escape($grn_batch);
	$result = db_query($sql, "Cannot query GRNs");

    return (db_num_rows($result) > 0);
}

//----------------------------------------------------------------------------------------------------------

function void_grn($grn_batch)
{
	// if this grn is references on any invoices/credit notes, then it
	// can't be voided
	if (exists_grn_on_invoices($grn_batch))
		return false;

	begin_transaction();
	hook_db_prevoid(ST_SUPPRECEIVE, $grn_batch);

	void_bank_trans(ST_SUPPRECEIVE, $grn_batch, true);
	void_gl_trans(ST_SUPPRECEIVE, $grn_batch, true);

	// clear the quantities of the grn items in the POs and invoices
	$result = get_grn_items($grn_batch);

    if (db_num_rows($result) > 0)
    {

    	while ($myrow = db_fetch($result))
        {

        	$sql = "UPDATE ".TB_PREF."purch_order_details
                SET quantity_received = quantity_received - " . $myrow["qty_recd"] . "
                WHERE po_detail_item = " . $myrow["po_detail_item"];

        	db_query($sql, "a purchase order details record could not be voided.");
        }
    }

	// clear the quantities in the grn items
	$sql = "UPDATE ".TB_PREF."grn_items SET qty_recd=0, quantity_inv=0
		WHERE grn_batch_id=".db_escape($grn_batch);

	db_query($sql, "A grn detail item could not be voided.");

    // clear the stock move items
    void_stock_move(ST_SUPPRECEIVE, $grn_batch);

	commit_transaction();

	return true;
}

//----------------------------------------------------------------------------------------------------------

function add_direct_grn(&$po,$type='')
{
	global $Refs;

	$date_ = $po->tran_date;

	begin_transaction();
	hook_db_prewrite($po, ST_SUPPRECEIVE);

	if (!is_company_currency($po->currency))
	{
		if (!$po->ex_rate)
			$po->ex_rate = get_date_exchange_rate($po->currency, $date_);
		else
			add_new_exchange_rate($po->currency, $date_, $po->ex_rate);
	} else
		$po->ex_rate = 1;
$pur_ord_no = '-1';
$pur_reference = 'auto';
	$grn = add_grn_batch($pur_ord_no, $po->supplier_id, $pur_reference, $po->Location, $date_, $po->ex_rate, 
	$po->freight_cost,$po->additional_charges,$po->packing_charges,$po->other_charges,
	$po->declaration_no,$po->cif_value,$po->vat_import_value,$po->custom_duties,
	$po->container_number,$po->bl_no);

    $clearing_act = get_company_pref('grn_clearing_act');
	if ($clearing_act) {	// otherwise GRN clearing account is not used
	    $total = 0;
	}
	//start gl codes insertion for grn
	if( isset($po->gl_codes1) && count($po->gl_codes1) > 0){
	foreach ($po->gl_codes1 as $entered_gl_code)
    {
	    /*GL Items are straight forward - just do the debit postings to the GL accounts specified -
	    the credit is to creditors control act  done later for the total invoice value + tax*/

		

		$memo_ = $entered_gl_code->memo_;
		/* $total += add_gl_trans_suppliers($trans_type, $grn, $date_, $entered_gl_code->gl_code,
			$entered_gl_code->gl_dim, $entered_gl_code->gl_dim2, -$entered_gl_code->amount, $po->supplier_id, "", 0, $memo_); */
			$gl_total += $entered_gl_code->amount; 

		
		add_grn_gl_item($grn, $entered_gl_code->gl_code, $entered_gl_code->amount, $memo_,
			$entered_gl_code->gl_dim, $entered_gl_code->gl_dim2); 
		// store tax details if the gl account is a tax account
		
		/* add_gl_tax_details($entered_gl_code->gl_code, 
			$trans_type, $grn, $entered_gl_code->amount,
			$ex_rate, $date_, $po->supp_ref, $po->tax_included); */
    }
	}

    
	$conv = (	($po->items_total+$po->freight_cost+$po->additional_charges+$po->packing_charges+$po->other_charges)*($po->ex_rate)+$gl_total)/(($po->items_total*$po->ex_rate));
	
	$conversion_factor = $conv;

	if($type != 'Direct'){
	foreach ($po->line_items as $line_no => $order_line)
	{
		if ($order_line->receive_qty != 0 && $order_line->receive_qty != "" && isset($order_line->receive_qty))
		{
			$stock_gl_code = get_stock_gl_code($order_line->stock_id);

			/*Update sales_order_details for the new quantity received and the standard cost used for postings to GL 
			  and recorded in the stock movements for FIFO/LIFO stocks valuations*/
			//------------------- update average material cost and clearing account --------------------------------
			if (is_inventory_item($order_line->stock_id))
			{
				if ($clearing_act)
					$total += add_gl_trans_supplier(ST_SUPPRECEIVE, $grn, $date_, $stock_gl_code["inventory_account"],
						$stock_gl_code['dimension_id'], $stock_gl_code['dimension2_id'],
					    $order_line->taxfree_charge_value($po), $po->supplier_id, '', 0, $order_line->stock_id);
						
 				update_average_material_cost($po->supplier_id, $order_line->stock_id, ($order_line->price*(1-($order_line->discount_percent/100))),
					$order_line->receive_qty, $date_,'',$conversion_factor);
			}
			
			
			 if($order_line->discount_percent !=0)
				{
					$total += add_gl_trans_supplier(ST_SUPPRECEIVE, $grn, $date_, 4510,
					$stock_gl_code['dimension_id'], $stock_gl_code['dimension2_id'],-(($order_line->receive_qty * $order_line->price)*($order_line->discount_percent/100)), $po->supplier_id);
				}
			//------------------------------------------------------------------------------------------------------
			if ($order_line->qty_received == 0)
			{
				/*This must be the first receipt of goods against this line */
			   	/*Need to get the standard cost as it is now so we can process GL jorunals later*/
			   	$order_line->standard_cost = get_unit_cost($order_line->stock_id);
			}
			// Update the purchase data table
			add_or_update_purchase_data($po->supplier_id, $order_line->stock_id, $order_line->price, 
				$order_line->item_description); 

			/*Need to insert a grn item */ // also need to check for over-receive.(within allowance)
			if ($order_line->receive_qty + $order_line->qty_received > $order_line->quantity)
				$order_line->quantity = $order_line->receive_qty + $order_line->qty_received;
			$grn_item = add_grn_detail_item($grn, $order_line->po_detail_rec,
				$order_line->stock_id, $order_line->item_description,
				$order_line->standard_cost,	$order_line->receive_qty, $order_line->price, $order_line->quantity, $order_line->discount_percent);
				

			$po->line_items[$line_no]->grn_item_id = $grn_item;
			/* Update location stock records - NB  a po cannot be entered for a service/kit parts done automatically */
			add_stock_move(ST_SUPPRECEIVE, $order_line->stock_id, $grn, $po->Location, $date_, "",
			 	$order_line->receive_qty, $order_line->standard_cost, ($order_line->taxfree_charge_value($po)/$order_line->receive_qty)*$conversion_factor);		

		} /*quantity received is != 0 */
	} /*end of order_line loop */
	
	}else{ 
	//start data insert grn against multiple pos
	foreach ($po->inv_items as $line_no => $order_line)
	{
		if ($order_line->this_quantity_inv != 0 && $order_line->this_quantity_inv != "" && isset($order_line->this_quantity_inv))
		{
			$stock_gl_code = get_stock_gl_code($order_line->item_code);

			/*Update sales_order_details for the new quantity received and the standard cost used for postings to GL 
			  and recorded in the stock movements for FIFO/LIFO stocks valuations*/
			//------------------- update average material cost and clearing account --------------------------------
			if (is_inventory_item($order_line->item_code))
			{
			
				if ($clearing_act)
					$total += add_gl_trans_supplier(ST_SUPPRECEIVE, $grn, $date_, $stock_gl_code["inventory_account"],
						$stock_gl_code['dimension_id'], $stock_gl_code['dimension2_id'],
					    $order_line->taxfree_charge_direct_value($po), $po->supplier_id, '', 0, $order_line->item_code);
						
 				update_average_material_cost($po->supplier_id, $order_line->item_code, ($order_line->order_price*(1-($order_line->discount_percent/100))),
					$order_line->this_quantity_inv, $date_,'',$conversion_factor);
			}
			
			
			 if($order_line->discount_percent !=0)
				{
					$total += add_gl_trans_supplier(ST_SUPPRECEIVE, $grn, $date_, 4510,
					$stock_gl_code['dimension_id'], $stock_gl_code['dimension2_id'],-(($order_line->this_quantity_inv * $order_line->order_price)*($order_line->discount_percent/100)), $po->supplier_id);
				}
			//------------------------------------------------------------------------------------------------------
			if ($order_line->prev_quantity_inv == 0)
			{
				/*This must be the first receipt of goods against this line */
			   	/*Need to get the standard cost as it is now so we can process GL jorunals later*/
			   	$order_line->standard_cost = get_unit_cost($order_line->item_code);
				
			}
			// Update the purchase data table
			add_or_update_purchase_data($po->supplier_id, $order_line->item_code, $order_line->order_price, 
				$order_line->item_description); 

			/*Need to insert a grn item */ // also need to check for over-receive.(within allowance)
			if ($order_line->this_quantity_inv + $order_line->prev_quantity_inv > $order_line->qty_recd)
				$order_line->qty_recd = $order_line->this_quantity_inv + $order_line->prev_quantity_inv;
			$grn_item = add_grn_detail_item($grn, $order_line->po_detail_item,
				$order_line->item_code, $order_line->item_description,
				$order_line->standard_cost,	$order_line->this_quantity_inv, $order_line->order_price, $order_line->qty_recd, $order_line->discount_percent);
		
			$po->inv_items[$line_no]->grn_item_id = $grn_item;
			/* Update location stock records - NB  a po cannot be entered for a service/kit parts done automatically */
			add_stock_move(ST_SUPPRECEIVE, $order_line->item_code, $grn, $po->Location, $date_, "",
			 	$order_line->this_quantity_inv, $order_line->standard_cost, ($order_line->taxfree_charge_direct_value($po)/$order_line->this_quantity_inv)*$conversion_factor);		

		} /*quantity received is != 0 */
	} /*end of order_line loop */
	
	
	}

	if ($clearing_act && $total != 0.0) {
		$accs = get_supplier_accounts($po->supplier_id);
		$total += add_gl_trans_supplier(ST_SUPPRECEIVE, $grn, $date_, $clearing_act,
			$accs['dimension_id'], $accs['dimension2_id'], -$total, null);
	}
	
	$Refs->save(ST_SUPPRECEIVE, $grn, $po->reference);

	add_audit_trail(ST_SUPPRECEIVE, $grn, $date_);

	$po->trans_no = $grn;
	hook_db_postwrite($po, ST_SUPPRECEIVE);
	commit_transaction();

	return $grn;
}

function add_grn_gl_item($trans_no, $gl_code, $amount, $memo_, $dim_id=0, $dim2_id=0)
{
	$sql = "INSERT INTO ".TB_PREF."grn_items (grn_batch_id, po_detail_item, item_code, description, qty_recd,discount_percent,grn_gl_code,grn_gl_amount,gl_dimension_id,gl_dimension2_id,gl_memo)
		VALUES (".db_escape($trans_no).", '-1', '', '',
		 '0', '0',".db_escape($gl_code).",".db_escape($amount).",".db_escape($dim_id).",".db_escape($dim2_id).",".db_escape($gl_memo).")";

	db_query($sql, "A GRN detail item could not be inserted.");
}




//ravi

//-------------------------------------------------------------------------------------------------------------

function add_inv_grn(&$po)
{
	global $Refs;

	$date_ = $po->orig_order_date;

	begin_transaction();
	hook_db_prewrite($po, ST_INVSUPPRECEIVE);

	if (!is_company_currency($po->curr_code))
	{
		if (!$po->ex_rate)
			$po->ex_rate = get_date_exchange_rate($po->curr_code, $date_);
		else
			add_new_exchange_rate($po->curr_code, $date_, $po->ex_rate);
	} else
		$po->ex_rate = 1;

	$grn = add_inv_grn_batch($po->order_no, $po->supplier_id, $po->reference, $po->Location, $date_, $po->ex_rate,$po->freight_cost,$po->additional_charges,$po->packing_charges,
	$po->other_charges,
	$po->declaration_no,$po->cif_value,$po->vat_import_value,$po->custom_duties,
	$po->container_number,$po->bl_no);

    $clearing_act = get_company_pref('grn_clearing_act');
	if ($clearing_act) {	// otherwise GRN clearing account is not used
	    $total = 0;
	}
	
	
	//Item Standard cost
	$items_total_inc_tax = $po->get_trans_total();
	
	$items_total_exclude_taxes =  $po->get_trans_total_exclude_taxes();
	
	$conv = (($items_total_exclude_taxes+$po->freight_cost+$po->additional_charges+$po->packing_charges+$po->other_charges)*($po->ex_rate))/($items_total_exclude_taxes*$po->ex_rate);
	
	$conversion_factor = $conv;
	
	
	foreach ($po->line_items as $line_no => $order_line)
	{
		if ($order_line->receive_qty != 0 && $order_line->receive_qty != "" && isset($order_line->receive_qty))
		{
			
				
			$grn_item = add_inv_grn_detail_item($grn, $order_line->po_detail_rec,
				$order_line->stock_id, $order_line->item_description,
				$order_line->standard_cost,	$order_line->receive_qty, $order_line->price, $order_line->quantity,$order_line->discount_percent,$order_line->standard_cost);

			$po->line_items[$line_no]->grn_item_id = $grn_item;
			
			
			

		} /*quantity received is != 0 */
	} /*end of order_line loop */

	
	$Refs->save(ST_INVSUPPRECEIVE, $grn, $po->reference);

	add_audit_trail(ST_INVSUPPRECEIVE, $grn, $date_);

	$po->trans_no = $grn;
	hook_db_postwrite($po, ST_INVSUPPRECEIVE);
	commit_transaction();

	return $grn;
}

//----------------------------------------------------------------------------------------

function add_inv_grn_batch($po_number, $supplier_id, $reference, $location, $date_, $rate,
$freight_cost=0,$additional_charges=0,$packing_charges=0,$other_charges=0,
$declaration_no='',$cif_value=0,$vat_import_value=0,$custom_duties=0,
$container_number='',$bl_no='')
{
	$date = date2sql($date_);

	$sql = "INSERT INTO ".TB_PREF."inv_grn_batch (purch_order_no, delivery_date, supplier_id, reference, loc_code, rate,freight_cost,additional_charges,packing_charges,other_charges,
	declaration_no,cif_value,vat_import_value,custom_duties,container_number,bl_no)
			VALUES (".db_escape($po_number).", ".db_escape($date).", "
			.db_escape($supplier_id).", ".db_escape($reference).", ".db_escape($location).", ".db_escape($rate).", ".db_escape($freight_cost).", ".db_escape($additional_charges).", ".db_escape($packing_charges).", ".db_escape($other_charges).",
			".db_escape($declaration_no).",".db_escape($cif_value).",
		    ".db_escape($vat_import_value).",".db_escape($custom_duties).",
			".db_escape($container_number).",".db_escape($bl_no).")";
			
			//display_error($sql); 
	db_query($sql, "A grn batch record could not be inserted.");

	return db_insert_id();
}

//-------------------------------------------------------------------------------------------------------------

function add_inv_grn_detail_item($grn_batch_id, $po_detail_item, $item_code, $description, $standard_unit_cost,
	$quantity_received, $price, $quantity,$discount_percent=0,$item_standard_price=0)
{
	
	$sql = "INSERT INTO ".TB_PREF."inv_grn_items (grn_batch_id, po_detail_item, item_code, description, qty_recd,discount_percent,item_standard_price)
		VALUES (".db_escape($grn_batch_id).", "
		.db_escape($po_detail_item).", ".db_escape($item_code).", ".db_escape($description)
		.", ".db_escape($quantity_received).",".db_escape($discount_percent).",
		".db_escape($item_standard_price).")";

	db_query($sql, "A GRN detail item could not be inserted.");

	return db_insert_id();
}

function read_inv_grn($grn_batch, &$order)
{
	$sql= "SELECT *	FROM ".TB_PREF."inv_grn_batch WHERE id=".db_escape($grn_batch);
	
	$result = db_query($sql, "The grn sent is not valid");

	$row = db_fetch($result);
	$po_number = $row["purch_order_no"];
	$order->ex_rate = $row["rate"];
	//ravi
	if($po_number==-1)
	{
	
		$grnsql = "SELECT dts.order_no FROM ".TB_PREF."inv_grn_items as gi LEFT JOIN ".TB_PREF."purch_order_details as dts ON dts.po_detail_item = gi.po_detail_item WHERE grn_batch_id=".db_escape($grn_batch)." AND gi.po_detail_item != '-1' limit 1";
	$grnres = db_query($grnsql);
	$grnresult = db_fetch_row($grnres);
	
	$result = read_po_header($grnresult['0'], $order,18);
	    $order->trans_type = ST_INVSUPPRECEIVE;
		$order->orig_order_date = sql2date($row["delivery_date"]);
		$order->Location = $row["loc_code"];
		$order->reference = $row["reference"];
		
		$order->credit_period = $row["credit_period"];
		$order->consgn_recv_date = sql2date($row["consgn_recv_date"]);
		$order->cleared_date = sql2date($row["cleared_date"]);
		$order->lpo_date = sql2date($row["lpo_date"]);
		$order->lpo_no = $row["lpo_no"];
		
		$order->freight_cost = $row["freight_cost"];
		$order->additional_charges = $row["additional_charges"];
		$order->packing_charges = $row["packing_charges"];
		$order->other_charges = $row["other_charges"];		
		read_inv_grn_items_to_order($grn_batch, $order);
	}
	else
	{
	$result = read_po_header($po_number, $order,18);

		if ($result)
		{

			$order->trans_type = ST_INVSUPPRECEIVE;
			$order->orig_order_date = sql2date($row["delivery_date"]);
			$order->Location = $row["loc_code"];
			$order->reference = $row["reference"];
			$order->freight_cost = $row["freight_cost"];
			$order->additional_charges = $row["additional_charges"];
			$order->packing_charges = $row["packing_charges"];
			$order->other_charges = $row["other_charges"];

			read_inv_grn_items_to_order($grn_batch, $order);
		}
	}
}

function read_inv_grn_items_to_order($grn_batch, &$order)
{
	$result = get_inv_grn_items($grn_batch);

  	if (db_num_rows($result) > 0)
  	{

	 	while ($myrow = db_fetch($result))
	 	{
	 		if ($myrow['qty_recd'] == 0 && $myrow['quantity_inv'] == 0)
				continue; // We will not have empty credited rows.
		 	if (is_null($myrow["units"]))
		 	{
				$units = "";
		 	}
		 	else
		 	{
				$units = $myrow["units"];
		 	}

			$order->add_to_order($order->lines_on_order, $myrow["item_code"],
				$myrow["qty_recd"],$myrow["description"], $myrow["unit_price"],$units,
				sql2date($myrow["req_del_date"]), $myrow["quantity_inv"],
				$myrow["qty_recd"],$myrow["discount_percent"]);

			$order->line_items[$order->lines_on_order-1]->po_detail_rec = $myrow["po_detail_item"];
		} /* line po from purchase order details */
  	} //end of checks on returned data set
}

function get_inv_grn_items($grn_batch_id=0, $supplier_id="", $outstanding_only=false,
	$is_invoiced_only=false, $invoice_no=0, $begin="", $end="")
{
    $sql = "SELECT grn.*, grn_item.*, po_item.delivery_date as req_del_date,
		grn_item.quantity_inv".($invoice_no ? "-invoice_item.quantity" : '')."  quantity_inv,
    	po_item.unit_price,
    	po_item.act_price,
    	po_item.quantity_ordered,
    	po_item.std_cost_unit, units
    	FROM	".TB_PREF."inv_grn_batch grn, "
    			.TB_PREF."purch_order_details po_item, "
    			.TB_PREF."stock_master stock_item,"
	   			.TB_PREF."inv_grn_items grn_item ";
 
       $sql .= " WHERE grn_item.grn_batch_id=grn.id
		AND grn_item.po_detail_item=po_item.po_detail_item";

	if ($outstanding_only)
    	$sql .= " AND (".($invoice_no ? "$inv_cond OR ":'')."grn_item.qty_recd - grn_item.quantity_inv > 0)";

	$sql .= " AND stock_item.stock_id=grn_item.item_code ";

	if ($begin != "")
		$sql .= " AND grn.delivery_date>='".date2sql($begin)."'";
	if ($end != "")
		$sql .= " AND grn.delivery_date<='".date2sql($end)."'";
	if ($grn_batch_id != 0)
		$sql .= " AND grn.id=".db_escape($grn_batch_id)
			." AND grn_item.grn_batch_id=".db_escape($grn_batch_id);

	if ($is_invoiced_only)
		$sql .= " AND grn_item.quantity_inv > 0";

	if ($supplier_id != "")
		$sql .= " AND grn.supplier_id =".db_escape($supplier_id);

	$sql .= " ORDER BY grn.delivery_date, grn.id, grn_item.id";

	return db_query($sql, "Could not retreive GRNS");
}



function get_inv_grn_po_reference($trans_no,$type)
{
	$sql="SELECT reference FROM ".TB_PREF."purch_orders   
	WHERE order_no=".db_escape($trans_no)."
	AND trans_type=".db_escape($type)."";
	$res= db_query($sql);
	$result = db_fetch_row($res);
	return $result == false ? false : $result[0];
}