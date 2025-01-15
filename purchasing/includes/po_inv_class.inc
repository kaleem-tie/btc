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
/* Definition of the Supplier Transactions class to hold all the information for an accounts payable invoice or credit note
*/

include_once($path_to_root . "/taxes/tax_calc.inc");

class po_inv 
{

	var $inv_items; /*array of objects of class grn_item using the id as the pointer */
	var $gl_codes1; /*array of objects of class gl_codes using a counter as the pointer */
	var $invs_items;
	var $supplier_id;
	var $supplier_name;
	var $terms;
	var $ex_rate;
	
	var $tax_description;
	var $tax_group_id;
	var $tax_group_array = null; 
	var $tax_included;
	
	var $trans_type;	// invoice or credit
	var $trans_no;
	
	var $discount_percent;	

	var $Comments;
	var $tran_date;
	var $due_date;
	var $src_docs = array();		// source invoice for this credit note (if any)

	var $supp_reference;
	var $reference;
	var $ov_amount;
	var $ov_discount;
	var $ov_gst;
	var $gl_codes_counter1=0;
	var $credit = 0;
	var $currency;
	var $tax_overrides = array();		// array of taxes manually inserted during sales invoice entry
	
	var $credit_period;
	var $lpo_no;
	var $lpo_date;
	var $consgn_recv_date;
	var $cleared_date;
	var $bill_no;
	var $bill_date;

	var $dimension,
		$dimension2;
		
		
	var $declaration_no;
	var $cif_value = 0;
	var $vat_import_value = 0;
	var $custom_duties = 0;	
	
	var $container_number;
	var $bl_no;
	
	var $lc_commission_amount =0;
	var $transport_amount =0;
	var $freight_inward_amount =0;
	var $demurrage_amount =0;
	var $insurance_amount =0;
	var $unloading_amount =0;
	var $port_ser_amount =0;
	var $cf_amount =0;
	var $cust_duty_amount =0;
	var $others_amount =0;
	
	
	var $lc_comm_memo;
	var $transport_memo;
	var $freight_inward_memo;
	var $demurrage_memo;
	var $insurance_memo;
	var $unloading_memo;
	var $port_ser_memo;
	var $cf_memo;
	var $cust_duty_memo;
	var $others_memo;

	function __construct($trans_type, $trans_no=0)
	{
		$this->trans_type = $trans_type;
		/*Constructor function initialises a new Supplier Transaction object */
		// $this->read($trans_type, $trans_no);
	}

	function read($trans_type, $trans_no)
	{
	
		$this->trans_type = $trans_type;
		$this->trans_no = $trans_no;
 		$this->inv_items = array();
 		$this->gl_codes1 = array();
		if ($trans_no) {
			read_supp_invoice($trans_no, $trans_type, $this);
			if ($trans_type == ST_SUPPCREDIT)
			{
                $this->src_docs = find_src_invoices($this);
			}
			read_supplier_details_to_trans($this, $this->supplier_id);
		}
	}
	function set_grn_supplier($supplier_id, $supplier_name, $curr_code, $tax_group_id, $tax_included)
	{
		$this->supplier_id = $supplier_id;
		$this->supplier_name = $supplier_name;
		$this->curr_code = $curr_code;
		$this->tax_group_id = $tax_group_id;
		$this->tax_included = $tax_included;
		$this->tax_group_array = get_tax_group_items_as_array($tax_group_id);
	}
	function add_grn_to_tran_inv($grn_item_id, $po_detail_item, $item_code, $item_description, 
		$qty_recd, $prev_quantity_inv, $this_quantity_inv, $order_price, $chg_price, 
		$true, $std_cost_unit=null, $gl_code='', $discount_percent=0,$sap_no='',$our_ord_no='',$supplier_date='')
	{
	
		$this->inv_items[$grn_item_id] = new inv_item($grn_item_id, $po_detail_item, 
			$item_code, $item_description, $qty_recd, $prev_quantity_inv, $this_quantity_inv, 
			$order_price, $chg_price, $std_cost_unit, $gl_code, $this->tax_included,$discount_percent,$sap_no,$our_ord_no,$supplier_date);
			
			

		$this->src_docs = find_src_invoices($this);

		unset($this->tax_overrides); // cancel tax overrides after cart change
		return 1;
	}

	function add_gl_codes_to_trans1($gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_)
	{
		$this->gl_codes1[$this->gl_codes_counter1] = new gl_codess($this->gl_codes_counter1, 
			$gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_);
		$this->gl_codes_counter1++;
		unset($this->tax_overrides); // cancel tax overrides after cart change
		return 1;
	}

	function remove_po_from_trans($grn_item_id)
	{
		unset($this->tax_overrides); // cancel tax overrides after cart change
	    unset($this->inv_items[$grn_item_id]);
	}
	function remove_gl_codes_from_trans1($gl_code_counter)
	{
	
		unset($this->tax_overrides); // cancel tax overrides after cart change
		unset($this->gl_codes1[$gl_code_counter]);
		
	}

	function is_valid_trans_to_post1()
	{
		return (count($this->grn_items) > 0 || count($this->gl_codes) > 0 || 
			($this->ov_amount != 0) || ($this->ov_discount > 0));
	}

	function clear_po_items()
	{
		unset($this->inv_items);
		unset($this->gl_codes1);
		$this->ov_amount = $this->ov_discount = $this->supplier_id = 0;

		$this->inv_items = array();
		$this->gl_codes = array();
	}

   function get_taxes($tax_group_id=null, $shipping_cost=0, $gl_codes=true, $additional_cost=0, $packing_cost=0, $other_cost=0)
    {
    	$items = array();
    	$prices = array();
		
	
		
	
        if ($tax_group_id == null)
        	$tax_group_id = $this->tax_group_id;
			
		if($shipping_cost==0)
			$shipping_cost = $this->freight_cost;
			
		if($additional_cost==0)
			$additional_cost = $this->additional_charges;

         if($packing_cost==0)
			$packing_cost = $this->packing_charges;

         if($other_cost==0)
			$other_cost = $this->other_charges;  	

		// preload the taxgroup
		$tax_group = get_tax_group_items_as_array($tax_group_id);

        foreach ($this->inv_items as $ln_itm) 
        {
        	$items[] = $ln_itm->item_code;
        	//$prices[] =round( ($ln_itm->this_quantity_inv * $ln_itm->chg_price),
				//user_price_dec());
			$prices[] =round( ($ln_itm->this_quantity_inv * $ln_itm->chg_price*(1-$ln_itm->discount_percent/100)),
				user_price_dec());	
				
				
        }

        if ($tax_group_id == null)
        	$tax_group_id = $this->tax_group_id;
			
			
			
        $taxes = get_tax_for_items($items, $prices, $shipping_cost, $tax_group_id, 
        	$this->tax_included,$tax_group,'',$additional_cost, $packing_cost, $other_cost);

		if (isset($this->tax_overrides))
			foreach($this->tax_overrides as $id => $value) // add values entered manually
			{
				$taxes[$id]['Override'] = $value;
			}

		// Taxes included in gl_codes table have exact value, but count to overrides as well.
		// Therefore when we want to now taxes only for items (gl_codes==false),
		// we have to subtract gl_taxes from override values.
		foreach ($this->gl_codes as $gl_code)
		{
			$index = is_tax_account($gl_code->gl_code);
			if ($index !== false)
			{
				if ($gl_codes)
					$taxes[$index]['Value'] += $gl_code->amount;
				elseif (isset($this->tax_overrides))
					$taxes[$index]['Override'] -= $gl_code->amount;
			}
		}

        return $taxes;
		
		
				
    }
	function get_items_total()
	{
		$total = 0;

		foreach ($this->inv_items as $ln_itm)
			$total += round($ln_itm->this_quantity_inv * $ln_itm->chg_price*(1-$ln_itm->discount_percent/100), user_price_dec());

		foreach ($this->gl_codes as $gl_line)
		{
   			if (!is_tax_account($gl_line->gl_code) || $this->tax_included)
				$total += $gl_line->amount;
		}
		return $total;
	}
	function get_shipping_tax()
	{

		$tax_items = get_shipping_tax_as_array($this->tax_group_id);
		
		$tax_group = get_tax_group_items_as_array($this->tax_group_id);
		
		$tax_rate = 0;
		if ($tax_items != null) {
			foreach ($tax_items as $item_tax) {
				$index = $item_tax['tax_type_id'];
				if (isset($tax_group[$index]['rate'])) {
					$tax_rate += $item_tax['rate'];
				}
			}
		}
		
		
		//display_error($tax_rate);
		
		if($this->tax_included)
			return round($this->freight_cost*$tax_rate/($tax_rate+100),  user_price_dec());
		else
			return round($this->freight_cost*$tax_rate/100,  user_price_dec());
	}
	function get_additional_tax()
	{

		$tax_items = get_additional_tax_as_array($this->tax_group_id);
		
		$tax_group = get_tax_group_items_as_array($this->tax_group_id);
		
		$tax_rate = 0;
		if ($tax_items != null) {
			foreach ($tax_items as $item_tax) {
				$index = $item_tax['tax_type_id'];
				if (isset($tax_group[$index]['rate'])) {
					$tax_rate += $item_tax['rate'];
				}
			}
		}
		
		if($this->tax_included)
			return round($this->additional_charges*$tax_rate/($tax_rate+100),  user_price_dec());
		else
			return round($this->additional_charges*$tax_rate/100,  user_price_dec());
	}
	//Packing Tax
	function get_tax_free_packing()
	{

		if ($this->tax_included==0)
			return $this->packing_charges;
		else
			return ($this->packing_charges - $this->get_packing_tax());
	}
	
		function get_packing_tax()
	{

		$tax_items = get_packing_tax_as_array($this->tax_group_id);
		
		$tax_group = get_tax_group_items_as_array($this->tax_group_id);
		
		$tax_rate = 0;
		if ($tax_items != null) {
			foreach ($tax_items as $item_tax) {
				$index = $item_tax['tax_type_id'];
				if (isset($tax_group[$index]['rate'])) {
					$tax_rate += $item_tax['rate'];
				}
			}
		}
		
		if($this->tax_included)
			return round($this->packing_charges*$tax_rate/($tax_rate+100),  user_price_dec());
		else
			return round($this->packing_charges*$tax_rate/100,  user_price_dec());
	}
	
	
	//Other Tax
	function get_tax_free_other()
	{

		if ($this->tax_included==0)
			return $this->other_charges;
		else
			return ($this->other_charges - $this->get_other_tax());
	}
	
	
		function get_other_tax()
	{

		$tax_items = get_other_tax_as_array($this->tax_group_id);
		
		$tax_group = get_tax_group_items_as_array($this->tax_group_id);
		
		$tax_rate = 0;
		if ($tax_items != null) {
			foreach ($tax_items as $item_tax) {
				$index = $item_tax['tax_type_id'];
				if (isset($tax_group[$index]['rate'])) {
					$tax_rate += $item_tax['rate'];
				}
			}
		}
		
		if($this->tax_included)
			return round($this->other_charges*$tax_rate/($tax_rate+100),  user_price_dec());
		else
			return round($this->other_charges*$tax_rate/100,  user_price_dec());
	}
	
	
	function get_grn_taxes($shipping_cost=null, $receival=false, $additional_cost=null, $packing_cost=null, $other_cost=null)
    {
    	$items = array();
    	$prices = array();
		
	
      if($shipping_cost==null)
			$shipping_cost = $this->freight_cost;
			
		if($additional_cost==null)
			$additional_cost = $this->additional_charges;

         if($packing_cost==null)
			$packing_cost = $this->packing_charges;

         if($other_cost==null)
			$other_cost = $this->other_charges;  			

		// preload the taxgroup
		$tax_group = get_tax_group_items_as_array($tax_group_id);

        foreach ($this->inv_items as $ln_itm) 
        {
        	$items[] = $ln_itm->item_code;
        	//$prices[] =round( ($ln_itm->this_quantity_inv * $ln_itm->order_price),
				//user_price_dec());
			$prices[] =round( ($ln_itm->this_quantity_inv * $ln_itm->order_price*(1-$ln_itm->discount_percent/100)),
				user_price_dec());	
				
				
        }

     
        
			
		$taxes = get_tax_for_items($items, $prices, $shipping_cost,
		  $this->tax_group_id, $this->tax_included,  $this->tax_group_array, '' , $additional_cost, $packing_cost, $other_cost);

		if (isset($this->tax_overrides))
			foreach($this->tax_overrides as $id => $value) // add values entered manually
			{
				$taxes[$id]['Override'] = $value;
			}

		// Taxes included in gl_codes table have exact value, but count to overrides as well.
		// Therefore when we want to now taxes only for items (gl_codes==false),
		// we have to subtract gl_taxes from override values.
		foreach ($this->gl_codes as $gl_code)
		{
			$index = is_tax_account($gl_code->gl_code);
			if ($index !== false)
			{
				if ($gl_codes)
					$taxes[$index]['Value'] += $gl_code->amount;
				elseif (isset($this->tax_overrides))
					$taxes[$index]['Override'] -= $gl_code->amount;
			}
		}

        return $taxes;
		
		
				
    }
	//
	//	Returns total invoice amount without taxes.
	//
    function get_total_taxfree1($tax_group_id=null)
    {
    	$total = 0;

		// preload the taxgroup !
		if ($tax_group_id != null)
			$tax_group = get_tax_group_items_as_array($tax_group_id);
		else
			$tax_group = null;

		foreach ($this->inv_items as $ln_itm)
    		$total += round(($ln_itm->this_quantity_inv * $ln_itm->taxfree_charge_price($tax_group_id, $tax_group)),
			 user_price_dec());

   		foreach ($this->gl_codes as $gl_line)
   		{
   			if (!is_tax_account($gl_line->gl_code))
   				$total += $gl_line->amount;
   		}
   		return $total;
    }
	//
	//	Returns transaction total 
	//
	function get_po_items_total()
	{
		$total = 0;

		foreach ($this->inv_items as $ln_itm)
			$total += round($ln_itm->this_quantity_inv * $ln_itm->chg_price*(1-$ln_itm->discount_percent/100), user_price_dec());

		foreach ($this->gl_codes as $gl_line)
		{
   			if (!is_tax_account($gl_line->gl_code) || $this->tax_included)
				$total += $gl_line->amount;
		}
		return $total;
	}
	
} /* end of class defintion */

class inv_item 
{

/* Contains relavent information from the purch_order_details as well to provide in cached form,
all the info to do the necessary entries without looking up ie additional queries of the database again */

	var $id;
	var $po_detail_item;
	var $item_code;
	var $item_description;
	var $qty_recd;
	var $prev_quantity_inv;
	var $this_quantity_inv;
	var $order_price;
	var $chg_price;
	var $std_cost_unit;
	var $gl_code;
	var $tax_included;
	var $discount_percent;
	var $sap_no;
	var $our_ord_no;
	var $supplier_date;

	function __construct($id, $po_detail_item, $item_code, $item_description, $qty_recd, 
		$prev_quantity_inv, $this_quantity_inv, $order_price, $chg_price,
		$std_cost_unit, $gl_code, $tax_included,$discount_percent,$sap_no,$our_ord_no,$supplier_date)
	{

		$this->id = $id;
		$this->po_detail_item = $po_detail_item;
		$this->item_code = $item_code;
		$this->item_description = $item_description;
		$this->qty_recd = $qty_recd;
		$this->prev_quantity_inv = $prev_quantity_inv;
		$this->this_quantity_inv = $this_quantity_inv;
		$this->order_price =$order_price;
		$this->chg_price = $chg_price;
		$this->std_cost_unit = $std_cost_unit;
		$this->gl_code = $gl_code;
		$this->tax_included = $tax_included;
		$this->sap_no = $sap_no;
		$this->our_ord_no = $our_ord_no;
		
		$this->discount_percent = $discount_percent;
		$this->supplier_date = $supplier_date;
	}

	function full_charge_price($tax_group_id, $tax_group=null)
	{
		return get_full_price_for_item1($this->item_code, 
		  $this->chg_price, $tax_group_id, $this->tax_included, $tax_group);
	}

	function taxfree_charge_price1($tax_group_id, $tax_group=null)
	{
		return get_tax_free_price_for_item($this->item_code, $this->chg_price, 
		  $tax_group_id, $this->tax_included, $tax_group);
	}
	//sailaja grn against multiple po's
function taxfree_charge_direct_value($po)
	{
		return get_tax_free_price_for_item($this->item_code, $this->this_quantity_inv*$this->order_price*(1-($this->discount_percent/100)),
		  $po->tax_group_id, $po->tax_included, $po->tax_group_array);
	}
}


class gl_codess
{

	var $Counter;
	var $gl_code;
	var $gl_act_name;
	var $gl_dim;
	var $gl_dim2;
	var $amount;
	var $memo_;

	function __construct($Counter, $gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_)
	{

	/* Constructor function to add a new gl_codes object with passed params */
		$this->Counter = $Counter;
		$this->gl_code = $gl_code;
		$this->gl_act_name = $gl_act_name;
		$this->gl_dim = $gl_dim;
		$this->gl_dim2 = $gl_dim2;
		$this->amount = $amount;
		$this->memo_= $memo_;
	}
}

