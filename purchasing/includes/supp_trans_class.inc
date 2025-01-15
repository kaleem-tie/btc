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

class supp_trans 
{

	var $grn_items; /*array of objects of class grn_item using the id as the pointer */
	var $gl_codes; /*array of objects of class gl_codes using a counter as the pointer */
	var $supplier_id;
	var $supplier_name;
	var $terms;
	var $ex_rate;
	
	var $tax_description;
	var $tax_group_id;
	var $tax_included;
	
	var $trans_type;	// invoice or credit
	var $trans_no;

	var $Comments;
	var $tran_date;
	var $due_date;
	var $src_docs = array();		// source invoice for this credit note (if any)

	var $supp_reference;
	var $reference;
	var $ov_amount;
	var $ov_discount;
	var $ov_gst;
	var $gl_codes_counter=0;
	var $credit = 0;
	var $currency;
	var $tax_overrides = array();		// array of taxes manually inserted during sales invoice entry

	var $dimension,
		$dimension2;
		
	var $freight_cost =0;
	var $additional_charges =0;
	var $packing_charges =0;
	var $other_charges =0;
	
	var $freight_tax =0;
	var $additional_tax =0;
	var $packing_tax =0;
	var $other_tax =0;
	
	var $ov_roundoff =0;	
	
	var $rc_supplier_id =0;
	var $rc_supplier_invoice_id =0;
	var $rc_invoice_date =0;
	var $rc_invoice_amount =0;
	var $reverse_charge =0;
	var $rc_bill_no =0;
	var $rc_custom_total =0;
	var $rc_curr_code =0;
	
	var $bill_date;
	
	
	var $declaration_no;
	var $cif_value = 0;
	var $vat_import_value = 0;
	var $custom_duties = 0;
	
	var $container_number;
	var $bl_no;

	function __construct($trans_type, $trans_no=0)
	{
		$this->trans_type = $trans_type;
		/*Constructor function initialises a new Supplier Transaction object */
		$this->read($trans_type, $trans_no);
	}

	function read($trans_type, $trans_no)
	{
		$this->trans_type = $trans_type;
		$this->trans_no = $trans_no;
 		$this->grn_items = array();
 		$this->gl_codes = array();
		if ($trans_no) {
			read_supp_invoice($trans_no, $trans_type, $this);
			if ($trans_type == ST_SUPPCREDIT)
			{
                $this->src_docs = find_src_invoices($this);
			}
			read_supplier_details_to_trans($this, $this->supplier_id);
		}
	}

	function add_grn_to_trans($grn_item_id, $po_detail_item, $item_code, $item_description, 
		$qty_recd, $prev_quantity_inv, $this_quantity_inv, $order_price, $chg_price, 
		$true,  $std_cost_unit=null, $gl_code='', $discount_percent=0,
		$line_dimension=0,$rc_supplier_id_rev='',$rc_supplier_invoice_id_rev='')
	{
	
		$this->grn_items[$grn_item_id] = new grn_item($grn_item_id, $po_detail_item, 
			$item_code, $item_description, $qty_recd, $prev_quantity_inv, $this_quantity_inv, 
			$order_price, $chg_price, $std_cost_unit, $gl_code, $this->tax_included, $discount_percent,$line_dimension,$rc_supplier_id_rev,$rc_supplier_invoice_id_rev);
		
		$this->src_docs = find_src_invoices($this);

		unset($this->tax_overrides); // cancel tax overrides after cart change
		return 1;
	}

	function add_gl_codes_to_trans($gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_)
	{
		$this->gl_codes[$this->gl_codes_counter] = new gl_codes($this->gl_codes_counter, 
			$gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_);
		$this->gl_codes_counter++;
		unset($this->tax_overrides); // cancel tax overrides after cart change
		return 1;
	}

	function remove_grn_from_trans($grn_item_id)
	{
		unset($this->tax_overrides); // cancel tax overrides after cart change
	    unset($this->grn_items[$grn_item_id]);
	}
	function remove_gl_codes_from_trans($gl_code_counter)
	{
		unset($this->tax_overrides); // cancel tax overrides after cart change
		unset($this->gl_codes[$gl_code_counter]);
	}

	function is_valid_trans_to_post()
	{
		return (count($this->grn_items) > 0 || count($this->gl_codes) > 0 || 
			($this->ov_amount != 0) || ($this->ov_discount > 0));
	}

	function clear_items()
	{
		unset($this->grn_items);
		unset($this->gl_codes);
		$this->ov_amount = $this->ov_discount = $this->supplier_id = 0;

		$this->grn_items = array();
		$this->gl_codes = array();
	}

    function get_taxes($tax_group_id=null, $shipping_cost=0, $gl_codes=true, 
	$additional_cost=0, $packing_cost=0, $other_cost=0)
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

		foreach ($this->grn_items as $ln_itm) 
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
	//
	//	Returns total invoice amount without taxes.
	//
    function get_total_taxfree($tax_group_id=null)
    {
    	$total = 0;

		// preload the taxgroup !
		if ($tax_group_id != null)
			$tax_group = get_tax_group_items_as_array($tax_group_id);
		else
			$tax_group = null;

		foreach ($this->grn_items as $ln_itm)
    		$total += round(($ln_itm->this_quantity_inv * $ln_itm->taxfree_charge_price($tax_group_id, $tax_group)),
			 user_price_dec());

   		foreach ($this->gl_codes as $gl_line)
   		{
   			if (!is_tax_account($gl_line->gl_code))
   				$total += $gl_line->amount;
   		}
   		return $total;
    }
	
	
	//Shipping Tax
	function get_tax_free_shipping()
	{

		if ($this->tax_included==0)
			return $this->freight_cost;
		else
			return ($this->freight_cost - $this->get_shipping_tax());
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
	
	
	//Additional Tax
	function get_tax_free_additional()
	{

		if ($this->tax_included==0)
			return $this->additional_charges;
		else
			return ($this->additional_charges - $this->get_additional_tax());
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
	
	
	//
	//	Returns transaction total 
	//
	function get_items_total()
	{
		$total = 0;

	foreach ($this->grn_items as $ln_itm)
			$total += round($ln_itm->this_quantity_inv * $ln_itm->chg_price*(1-$ln_itm->discount_percent/100), user_price_dec());
		
		// Commented by ramesh for local expenses should not add in total	
		/* foreach ($this->gl_codes as $gl_line)
		{
   			if (!is_tax_account($gl_line->gl_code) || $this->tax_included)
				$total += $gl_line->amount;
		} */
		return $total;
	}
} /* end of class defintion */

class grn_item 
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
	
	var $line_dimension;
	var $rc_supplier_id_rev;
	var $rc_supplier_invoice_id_rev;

	function __construct($id, $po_detail_item, $item_code, $item_description, $qty_recd, 
		$prev_quantity_inv, $this_quantity_inv, $order_price, $chg_price,
		$std_cost_unit, $gl_code, $tax_included,$discount_percent=0,
		$line_dimension=0,$rc_supplier_id_rev='',$rc_supplier_invoice_id_rev='')
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
		
		$this->discount_percent = $discount_percent;
		
		$this->line_dimension = $line_dimension;
		$this->rc_supplier_id_rev = $rc_supplier_id_rev;
		$this->rc_supplier_invoice_id_rev = $rc_supplier_invoice_id_rev;
	}

	function full_charge_price($tax_group_id, $tax_group=null)
	{
		return get_full_price_for_item($this->item_code, 
		  $this->chg_price, $tax_group_id, $this->tax_included, $tax_group);
	}

	function taxfree_charge_price($tax_group_id, $tax_group=null)
	{
		return get_tax_free_price_for_item($this->item_code, $this->chg_price, 
		  $tax_group_id, $this->tax_included, $tax_group);
	}
	//Ramesh grn against multiple po's
	function taxfree_charge_price1($tax_group_id, $tax_group=null)
	{
		return get_tax_free_price_for_item($this->item_code, $this->chg_price, 
		  $tax_group_id, $this->tax_included, $tax_group);
	}
	
	function taxfree_charge_direct_value($po)
	{
		return get_tax_free_price_for_item($this->item_code, $this->this_quantity_inv*$this->order_price*(1-($this->discount_percent/100)),
		  $po->tax_group_id, $po->tax_included, $po->tax_group_array);
	}
}


class gl_codes 
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

