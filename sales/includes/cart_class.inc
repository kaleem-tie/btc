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
/* Definition of the cart class
this class can hold all the information for:

i)   a sales order
ii)  an invoice
iii) a credit note
iv)  a delivery note
*/

include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

class Cart
{
	var $trans_type; // invoice, order, quotation, delivery note ...
	var $trans_no = array();// array (num1=>ver1,..) or 0 for new
	var $so_type = 0;		// for sales order: simple=0 template=1
	var $cart_id;		// used to detect multi-tab edition conflits
	var $line_items;  //array of objects of class line_details

	var $src_docs = array();	// array of arrays(num1=>ver1,...) or 0 for no src
	var $src_date;			// src document date (for info only)

	var $document_date;
	var $due_date;
	var $sales_type; // set to the customer's sales type
	var $sales_type_name; // set to customer's sales type name
	var $tax_included;

	var $customer_currency; // set to the customer's currency
	var $default_discount; // set to the customer's discount %
	var $customer_name;
	var $customer_id;
	var $Branch;
	var $email;

	var $deliver_to;
	var $delivery_address;
	var $phone;

	var $cust_ref;
	var $reference;
	var $Comments;
	var $Location;
	var $location_name;
	var $order_no; 		// the original order number

	var $ship_via;
	var $freight_cost = 0;

	var $tax_group_id;
	var $tax_group_name;
	var $tax_group_array = null; // saves db queries
	var $price_factor;	 // ditto for price calculations

	var	$pos;			// user assigned POS
	var $cash_account;
	var $account_name;
	var $cash_discount;	// not used as of FA 2.1
	var $dimension_id;
	var $dimension2_id;
	var $payment;
	var $payment_terms = array('cash_sale' => false, 'days_before_due' => 0); // cached payment terms
	var $credit;
	// prepayment mode:
	var $prepaid=false;	// true for documents issued in prepayment mode
	var $prep_amount=0;	// prepayment required for SO, invoiced amount for prepaiament invoice
	var $sum_paid; 		// sum of all allocated prepayments both to order and related invoices
	var $alloc; 		// sum of payments allocated to this document
	var $prepayments = array(); // allocation records for this document
	var $ex_rate;
	
	var $sales_person_id=0; //ravi

	var $fixed_asset = false;
	
	var $advance_ids = array();
	
	var $final_discount = 0;
	var $final_discount_amount = 0;
	
	var $delivery_time;
	var $invoice_type;
	var $ov_roundoff = 0;
	
	var $lpo_no;
	var $lpo_date;
	
	var $inv_edit_ref='';
	var $delivery_terms='';
	
	var $cash_cust_name;
	var $cash_cust_mobile;
	
	//-------------------------------------------------------------------------
	//
	//  $trans_no==0 => open new/direct document
	//  $trans_no!=0 && $prepare_child==false => update with parent constarints for reedition
	//  $trans_no!=0 && $prepare_child==true => prepare for child doc entry
	//	$prepare_child is set to ST_SALESINVOICE for prepayment invoices
	//
	function __construct($type, $trans_no=0, $prepare_child=false) {
		/*Constructor function initialises a new shopping cart */
		$this->line_items = array();
		$this->sales_type = "";
		if ($type == ST_SALESQUOTE)
			$this->trans_type = $type;
		else if ($type == ST_SALESENQ)
			$this->trans_type = $type;
		else if($type == ST_INSSALESQUOTE)
            $this->trans_type = $type;	
        else if($type == ST_INSSALESORDER)
			$this->trans_type = $type;
		else if($type == ST_INSSALESENQ)
			$this->trans_type = $type;
		else	
			$this->trans_type = ST_SALESORDER;
				
		$this->pos = get_sales_point(user_pos());
		$this->read($type, $trans_no, $prepare_child);

		
		$this->cart_id = uniqid('');
		
	}
	
	/*
		Optional sorting items by stock_id.
	*/
	function _cmp_lines($a, $b)
	{
		return strcmp($a->stock_id, $b->stock_id);
	}

	/*
		Returns items array optionally sorted by item code.
	*/
	function get_items()
	{
		global $SysPrefs;

		$items = $this->line_items;
		
		if (@$SysPrefs->sort_sales_items)
			uasort($items, array($this, '_cmp_lines'));

		return $items;
	}
	//
	//	Prepare cart to new child document entry, just after initial parent doc read.
	//
	function prepare_child($type)
	{
	
		global $Refs;

		if ($type === true)
			$type = get_child_type($this->trans_type);	
	
		$this->trans_type = $type;

		$this->reference = $Refs->get_next($this->trans_type, null, array('date' => $this->document_date, 'customer' => $this->customer_id, 'branch' => $this->Branch, 'inv_type' => $this->invoice_type));
		
		if ($type == ST_CUSTCREDIT)
		    $this->src_date = $this->document_date;

		$this->document_date = new_doc_date();

		for($line_no = 0; $line_no < count($this->line_items); $line_no++) {
			$line = &$this->line_items[$line_no];
			$line->src_id = $line->id; // save src line ids for update
			$line->qty_dispatched = $type == ST_CUSTCREDIT ? '0' : 
				 (($this->prepaid && $type == ST_SALESINVOICE) ? $line->quantity : $line->quantity - $line->qty_done);
			$line->qty_old = 0;
		}
		unset($line);
		
		if ($type == ST_CUSTDELIVERY) {
			$this->order_no = key($this->trans_no);
			$cust = get_customer($this->customer_id);
			//$this->dimension_id = $cust['dimension_id'];
			//$this->dimension2_id = $cust['dimension2_id'];
		}
		if ($type == ST_SALESINVOICE) {
			$this->due_date = get_invoice_duedate($this->payment, $this->document_date);
		}

		$this->src_docs = $this->trans_no;
		$this->trans_no = 0;
	}
// ramesh for sales invoice edit info
	function get_sales_invoice_items($trans_no,$trans_type)
	{
		$result = get_customer_trans_details(ST_SALESINVOICE, $trans_no);
		
		 if (db_num_rows($result) > 0)
	    {
		$line_no=0;
		 while ($myrow1 = db_fetch($result))
		 {	
			 $this->add_to_cart($line_no, $myrow1["stock_id"], $myrow1["quantity"],$myrow1["unit_price"], $myrow1["discount_percent"], 0,0,$myrow1["description"],0,0,0,$myrow1["disc_amount"],$myrow1["unit"],$myrow1["foc_quantity"]);		
		 $line_no++;
		 }
	    }
	   // $Ajax->activate('items_table');
	}
	
	
	
	//Delivery items 
	function get_sales_delivery_items($trans_no,$trans_type)
	{
		$result = get_customer_trans_details(ST_CUSTDELIVERY, $trans_no);
		
		 if (db_num_rows($result) > 0)
	    {
		$line_no=0;
		 while ($myrow1 = db_fetch($result))
		 {	
			 $this->add_to_cart($line_no, $myrow1["stock_id"], $myrow1["quantity"],$myrow1["unit_price"], $myrow1["discount_percent"], 0,0,$myrow1["description"],0,0,0,$myrow1["disc_amount"],$myrow1["unit"],
			 $myrow1["foc_quantity"]);		
		 $line_no++;
		 }
	    }
	   // $Ajax->activate('items_table');
	}
	
//End
	//
	//	Prepares transaction for reedition updating with parent transaction data 
	//
	function set_parent_constraints($sodata, $src_no) {

		$srcdetails = get_sales_parent_lines($this->trans_type, $src_no);
		$src_type = get_parent_type($this->trans_type);

		// calculate & save: qtys on other docs and free qtys on src doc
		$line_no = 0; $src_docs = array();
		// Loop speed optimisation below depends on fact 
		// that child line_items contains subset of parent lines in _the_same_ order !
		while (($line_no < count($this->line_items)) && ($srcline = db_fetch($srcdetails))) {
			$line = &$this->line_items[$line_no];
			$src_docs[] = $src_type == ST_SALESORDER ?  $srcline['order_no'] : $srcline['debtor_trans_no'];
			while($srcline['id'] != $line->src_id) // Logic : This will increment the line_items array till sales_order line is matched.
 			{	// Fixes Delivery note bug : Parent constraints not working if sales order line deleted after delivery 
     			$line_no++;
     			$line = &$this->line_items[$line_no];
 			}
			if ($srcline['id'] == $line->src_id) {
				if ($this->trans_type == ST_SALESINVOICE)
					$line->src_no = $srcline['debtor_trans_no'];
				$line->qty_old = $line->qty_dispatched = $line->quantity;
				$line->quantity += $srcline['quantity'] - 
					($src_type==ST_SALESORDER ? $srcline['qty_sent'] : $srcline['qty_done']); // add free qty on src doc
				$line_no++;
			}
		}

		if ($src_type == ST_SALESORDER || $src_type == 0) {
			$this->src_docs = array( $sodata['order_no']=>$sodata['version']);
		} else {
			// get src_data from debtor_trans
			$this->src_docs = get_customer_trans_version($src_type, array_unique($src_docs));
		}
	}
	//-------------------------------------------------------------------------
	// Reading document into cart
	//
	function read($type, $trans_no=0, $prepare_child=false) {

		global $SysPrefs, $Refs;

		if (!is_array($trans_no)) $trans_no = array($trans_no);
		
		if ($trans_no[0]) { // read old transaction
			if ($type == ST_SALESORDER || $type == ST_SALESQUOTE || $type == ST_SALESENQ || $type == ST_INSSALESORDER || $type == ST_INSSALESQUOTE || $type == ST_INSSALESENQ ) { // sales order || sales quotation
				read_sales_order($trans_no[0], $this, $type,$prepare_child ); //ravi
			} else {	// other type of sales transaction
				read_sales_trans($type, $trans_no, $this);
				
			
				
				$this->prepayments = get_payments_for($trans_no[0], $type, $this->customer_id);
				$this->update_payments();
				if ($this->order_no) { // free hand credit notes have no order_no
					$sodata = get_sales_order_header($this->order_no, ST_SALESORDER);
					
					
						
					$this->cust_ref = $sodata["customer_ref"];
				// currently currency is hard linked to debtor account
					$this->delivery_to = $sodata["delivery_to"];
					$this->delivery_address = $sodata["delivery_address"];
				// child transaction reedition - update with parent info unless it is freehand
				if (!$this->is_prepaid() && !$prepare_child) // this is read for view/reedition
					$this->set_parent_constraints($sodata, $trans_no[0]);
				}
				
		}
  	
			// convert document into child and prepare qtys for entry
			if ($prepare_child)
				$this->prepare_child($prepare_child);
				

		} else { // new document
		
			$this->trans_type = $type;
			$this->trans_no = 0;
			$this->customer_currency = get_company_currency();
			// set new sales document defaults here
			if (get_global_customer() != ALL_TEXT)
			  $this->customer_id = get_global_customer();
			else
			  $this->customer_id = '';
			$this->document_date = new_doc_date();
			if (!is_date_in_fiscalyear($this->document_date))
				$this->document_date = end_fiscalyear();
			/* $this->reference = $Refs->get_next($this->trans_type, null, array('date' => Today(),	'customer' => $this->customer_id)); */
			
            /*			
			if ($type != ST_SALESORDER && $type != ST_SALESQUOTE && $type != ST_SALESENQ && $type != ST_INSSALESORDER && $type != ST_INSSALESQUOTE && $type != ST_INSSALESENQ) // Added 2.1 Joe Hunt 2008-11-12
			{
				$dim = get_company_pref('use_dimension');
				if ($dim > 0)
				{
					if ($this->customer_id == '')
						$this->dimension_id = 0;
					else
					{
						$cust = get_customer($this->customer_id);
						$this->dimension_id = $cust['dimension_id'];
					}	
					if ($dim > 1)
					{
						if ($this->customer_id == '')
							$this->dimension2_id = 0;
						else
							$this->dimension2_id = $cust['dimension2_id'];
					}		
				}		
			}	
			*/
			
			
			if ($type == ST_SALESINVOICE) {
				$this->due_date =
					get_invoice_duedate($this->payment, $this->document_date);
			} else
				$this->due_date =
					add_days($this->document_date, $SysPrefs->default_delivery_required_by());
		}
		$this->credit = get_current_cust_credit($this->customer_id);
	}

	//-------------------------------------------------------------------------
	// Writing new/modified sales document to database.
	// Makes parent documents for direct delivery/invoice by recurent call.
	// $policy - 0 or 1:  writeoff/return for IV, back order/cancel for DN
	// $documinvoice - 0 or 1:  Regular process, documinvoice against the SO
	function write($policy=0,$documinvoice=0) {
		
		global $SysPrefs, $Refs;
		
		if($documinvoice==1)
		{
		  $this->trans_type = ST_SALESINVOICE;
		}

		
		
		begin_transaction(); // prevents partial database changes in case of direct delivery/invoice
		if ($this->reference != 'auto' && $this->trans_no == 0 && !is_new_reference($this->reference, $this->trans_type))
		{
			
			if (!empty($SysPrefs->prefs['ref_no_auto_increase'])){
			
				$this->reference = $Refs->get_next($this->trans_type, null, array('date' => Today(),'inv_type'=>$this->invoice_type));
			}
			else	
			{
				commit_transaction();
				return -1;
			}
		}
		if($documinvoice==1)
		{
			if (($this->trans_type == ST_SALESINVOICE || $this->trans_type == ST_CUSTDELIVERY) && !$this->is_prepaid()) {
				// this is direct document - first add parent
				$ref = $this->reference;
				$date = $this->document_date;
				$due_date = $this->due_date;
				$dimension_id = $this->dimension_id;
				$dimension2_id = $this->dimension2_id;
				$this->trans_type = get_parent_type($this->trans_type);
				$this->reference = 'auto';
				//ravi
				if($this->trans_type==ST_SALESORDER && $documinvoice==1)
				$trans_no=$this->order_no;
				else			
				$trans_no = $this->write(1); 
				
			// re-read parent document converting it to child
				$this->read($this->trans_type, $trans_no, true); 
				$this->document_date = $date;
				$this->reference = $ref;
				$this->due_date = $due_date;
				$this->dimension_id = $dimension_id;
				$this->dimension2_id = $dimension2_id;
			}
		}
		else
		{
			if (count($this->src_docs) == 0 && ($this->trans_type == ST_SALESINVOICE || $this->trans_type == ST_CUSTDELIVERY) && !$this->is_prepaid()) {
				
				// this is direct document - first add parent
				$ref = $this->reference;
				$date = $this->document_date;
				$due_date = $this->due_date;
				$dimension_id = $this->dimension_id;
				$dimension2_id = $this->dimension2_id;
				$this->trans_type = get_parent_type($this->trans_type);
				$this->reference = 'auto';
				$trans_no = $this->write(1); 
				
			// re-read parent document converting it to child
				$this->read($this->trans_type, $trans_no, true); 
				$this->document_date = $date;
				$this->reference = $ref;
				$this->due_date = $due_date;
				$this->dimension_id = $dimension_id;
				$this->dimension2_id = $dimension2_id;
			}		
		}
		
		$this->reference = @html_entity_decode($this->reference, ENT_QUOTES);
		$this->Comments = @html_entity_decode($this->Comments, ENT_QUOTES);
		foreach($this->line_items as $lineno => $line) {
		
		
			$this->line_items[$lineno]->stock_id = @html_entity_decode($line->stock_id, ENT_QUOTES);
			$this->line_items[$lineno]->item_description = @html_entity_decode($line->item_description, ENT_QUOTES);
		} 
		switch($this->trans_type) {
			
			case ST_SALESINVOICE:
			    $ret = write_sales_invoice($this); break;
			case ST_CUSTCREDIT:
				$ret = write_credit_note($this, $policy); break;
			case ST_CUSTDELIVERY:
				$ret = write_sales_delivery($this, $policy); break;
			case ST_SALESORDER:
			case ST_SALESENQ:
			case ST_SALESQUOTE:
			case ST_INSSALESORDER:
			case ST_INSSALESQUOTE:
			case ST_INSSALESENQ:
				if ($this->trans_no==0)	// new document
					$ret = add_sales_order($this);
				else
					$ret = update_sales_order($this);
		}

		commit_transaction();

		return $ret;
	}

	function set_customer($customer_id, $customer_name, $currency, $discount, $payment, $cdiscount=0)
	{
		$this->customer_name = $customer_name;
		$this->customer_id = $customer_id;
		$this->default_discount = $discount;
		$this->customer_currency = $currency;
		$this->payment = $payment;
		$this->payment_terms = get_payment_terms($payment);
		$this->cash_discount = $cdiscount;

		if ($this->payment_terms['cash_sale']) {
			$this->Location = $this->pos['pos_location'];
			$this->location_name = $this->pos['location_name'];
		}
		$this->credit = get_current_cust_credit($customer_id);
	}

	function set_branch($branch_id, $tax_group_id, $tax_group_name, $phone='', $email='')
	{
		$this->Branch = $branch_id;
		$this->phone = $phone;
		$this->email = $email;
		$this->tax_group_id = $tax_group_id;
		$this->tax_group_array = get_tax_group_items_as_array($tax_group_id);
	}

	function set_sales_type($sales_type, $sales_name, $tax_included=0, $factor=0)
	{
	    $this->sales_type = $sales_type;
	    $this->sales_type_name = $sales_name;
	    $this->tax_included = $tax_included;
	    $this->price_factor = $factor;
	}

	function set_location($id, $name)
	{
		$this->Location = $id;
		$this->location_name = $name;
	}

	function set_delivery($shipper, $destination, $address, $freight_cost=null)
	{
		$this->ship_via = $shipper;
		$this->deliver_to = $destination;
		$this->delivery_address = $address;
		if (isset($freight_cost))
			$this->freight_cost = $freight_cost;
	}

	function add_to_cart($line_no, $stock_id, $qty, $price, $disc, $qty_done=0, 
	$standard_cost=0, $description=null, $id=0, $src_no=0,	$src_id=0, $disc_amount =0,
	$units="",$foc_quantity=0,$foc_qty_sent=0)
	{
		
		$line = new line_details($stock_id, $qty, $price, $disc,
			$qty_done,  $standard_cost, $description, $id, $src_no, $src_id, $units, 0, '', '', $disc_amount,$foc_quantity,$foc_qty_sent);

		if ($line->valid) {
			$this->line_items[$line_no] = $line;
			return 1;
		} else
			display_error(_("You have to enter valid stock code or nonempty description"));
		return 0;
	}
	
	function add_to_temp2_cart($line_no, $item_number, $item_desc, $qty=0, $units="", 
	$unit_price=0, $total_price=0, $stock_id='',$disc=0,$qty_done=0,$standard_cost=0,$id=0,$src_no=0,$src_id=0){
	
		$line = new line_details($stock_id, $qty, $unit_price, $disc,
			$qty_done,  $standard_cost, $item_desc, $id, $src_no, $src_id, $units, $total_price,$item_number,$this->trans_type); //ravi
		$this->line_items[$line_no] = $line;
			return 1;
	}
	
	function update_temp2_cart_item($line_no,$item_number,$description,$quantity,$units,
	$unit_price,$total_price){
		if($description != "")
			$this->line_items[$line_no]->item_number = $item_number;
			$this->line_items[$line_no]->item_description = $description;
			$this->line_items[$line_no]->quantity = $quantity;
			$this->line_items[$line_no]->units = $units;
			$this->line_items[$line_no]->price = $unit_price;
			$this->line_items[$line_no]->total_price = $total_price;
	}
	
	function update_cart_item($line_no, $qty, $price, $disc, $description="", 
	$disc_amount=0,$units="",$foc_quantity=0)
	{
		if ($description != "")
			$this->line_items[$line_no]->item_description = $description;
		$this->line_items[$line_no]->quantity = $qty;
		$this->line_items[$line_no]->qty_dispatched = $qty;
		$this->line_items[$line_no]->price = $price;
		$this->line_items[$line_no]->discount_percent = $disc;
		$this->line_items[$line_no]->disc_amount = $disc_amount;
		$this->line_items[$line_no]->units = $units;
		$this->line_items[$line_no]->foc_quantity = $foc_quantity;
	}

	function update_add_cart_item_qty($line_no, $qty)
	{
		$this->line_items[$line_no]->quantity += $qty;
	}

	function remove_from_cart($line_no)
	{
		array_splice($this->line_items, $line_no, 1);
	}

	function clear_items()
	{
		unset($this->line_items);
		$this->line_items = array();
		$this->sales_type = "";
		$this->trans_no = 0;
		$this->customer_id = $this->order_no = 0;
	}

	function count_items()
	{
		$counter=0;
		foreach ($this->line_items as $line) {
			if ($line->quantity!=$line->qty_done) $counter++;
		}
		return $counter;
	}

	function get_items_total()
	{
		$total = 0;
		foreach ($this->line_items as $ln_itm) {
			$price = $ln_itm->line_price();
			// $total += round($ln_itm->quantity * $price * (1 - $ln_itm->discount_percent),user_price_dec());
			$total += round($ln_itm->quantity * $price * (1 - $ln_itm->discount_percent/100),user_price_dec());
		}
		return $total;
	}

	function get_items_total_dispatch()
	{
		$total = 0;

		foreach ($this->line_items as $ln_itm) {
			$price = $ln_itm->line_price();
			// $total += round(($ln_itm->qty_dispatched * $price * (1 - $ln_itm->discount_percent)),user_price_dec());
			$total += round(($ln_itm->qty_dispatched * $price * (1 - $ln_itm->discount_percent/100)),user_price_dec());
		}
		return $total;
	}

	function has_items_dispatch()
	{
		foreach ($this->line_items as $ln_itm) {
			if ($ln_itm->qty_dispatched > 0)
				return true;
		}
		return false;
	}

	function any_already_delivered()
	{
		/* Checks if there have been any line item processed */

		foreach ($this->line_items as $stock_item) {
			if ($stock_item->qty_done !=0) {
				return 1;
			}
		}

		return 0;

	}

	function some_already_delivered($line_no)
	{
		/* Checks if there have been deliveries of a specific line item */
		if (isset($this->line_items[$line_no]) &&
			$this->line_items[$line_no]->qty_done != 0) {
			return 1;
		}
		return 0;
	}

	function get_taxes($shipping_cost=null)
	{
		$items = array();
		$prices = array();
		if($shipping_cost==null)
			$shipping_cost = $this->freight_cost;

		foreach ($this->line_items as $ln_itm) {
			
			$items[] = $ln_itm->stock_id;
			$prices[] = round(((
				$this->trans_type==ST_SALESORDER ? 	$ln_itm->quantity : $ln_itm->qty_dispatched) *
				$ln_itm->line_price()* (1 - $ln_itm->discount_percent/100)),  user_price_dec());
		}

		$taxes = get_tax_for_items($items, $prices, $shipping_cost,
		  	$this->tax_group_id, $this->tax_included,  $this->tax_group_array);

    // Adjustment for swiss franken, we always have 5 rappen = 1/20 franken
    if ($this->customer_currency == 'CHF') {
			$val = $taxes['1']['Value'];
			$val1 = (floatval((intval(round(($val*20),0)))/20));
			$taxes['1']['Value'] = $val1;
		} 
		return $taxes;
	}


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
		$tax_rate = 0;
		if ($tax_items != null) {
			foreach ($tax_items as $item_tax) {
				$index = $item_tax['tax_type_id'];
				if (isset($this->tax_group_array[$index]['rate'])) {
					$tax_rate += $item_tax['rate'];
				}
			}
		}
		if($this->tax_included)
			return round($this->freight_cost*$tax_rate/($tax_rate+100),  user_price_dec());
		else
			return round($this->freight_cost*$tax_rate/100,  user_price_dec());
	}
	/*
		Returns transaction value including all taxes
	*/
	function get_trans_total() {
		
		$total = $this->get_items_total() + $this->freight_cost;
		$dec = user_price_dec();
		if (!$this->tax_included ) {
			$taxes = $this->get_taxes();
			foreach($taxes as $tax)
				$total += round($tax['Value'], $dec);
		}

		return $total;
	}

	/*
		Checks cart quantities on document_date.
		Returns array of stock_ids which stock quantities would go negative on some day.
	*/
	function check_qoh($date=null, $location=null)
	{
	
	
	
		$low_stock = array();
		// check only for customer delivery and direct sales invoice 
	   	if (!($this->trans_type == ST_CUSTDELIVERY || ($this->trans_type == ST_SALESINVOICE && $this->trans_no==0)))
	   		return $low_stock;

		// collect quantities by stock_id
		$qtys = array();
		foreach ($this->line_items as $line_no => $line_item)
		{
		
			if (has_stock_holding($line_item->mb_flag))
			{
			
			   if($line_item->units==1){
			   
			   $check_qty = ($line_item->qty_dispatched)*1;
			   $check_dn_qty = ($line_item->qty_dispatched-$line_item->qty_old)*1;
			   
			   
			   if (!$this->trans_no) // new delivery
					$qtys[$line_item->stock_id]['qty'] = $check_qty + @$qtys[$line_item->stock_id]['qty'];
				else	// DN modification: check change in quantity
					$qtys[$line_item->stock_id]['qty'] = ($check_dn_qty) + @$qtys[$line_item->stock_id]['qty'];
				$qtys[$line_item->stock_id]['line'] = $line_no;
			   }
			   else if($line_item->units==2){
			   
			   $sec_unit_info = get_item_sec_unit_info($line_item->stock_id);
			   
			   $check_qty = ($line_item->qty_dispatched/$sec_unit_info['conversion_factor']);
			   
			   $check_dn_qty = ($line_item->qty_dispatched-$line_item->qty_old)/
			   $sec_unit_info['conversion_factor'];
			   
			   if (!$this->trans_no) // new delivery
					$qtys[$line_item->stock_id]['qty'] = $check_qty + @$qtys[$line_item->stock_id]['qty'];
				else	// DN modification: check change in quantity
					$qtys[$line_item->stock_id]['qty'] = ($check_dn_qty) + @$qtys[$line_item->stock_id]['qty'];
				$qtys[$line_item->stock_id]['line'] = $line_no;
			   
			   }
			
			}
		}

		foreach($qtys as $stock_id => $sum)
		{
			if (check_negative_stock($stock_id, -$sum['qty'], $location ? $location : $this->Location, $date ? $date : $this->document_date))
				$low_stock[] = $stock_id;
		}

		return $low_stock;
	}

	/*
		Returns true for documents issued in prepayment cycle.
	*/
	function is_prepaid()
	{
		return $this->prepaid; 
	}
	/*
		Order is ready for delivery in prepament mode.
	*/
	function is_released()
	{
		return floatcmp($this->sum_paid, $this->prep_amount) >= 0;
	}

	/*
		Check whether order has been already invoiced/send or not.
	*/
	function is_started()
	{
		if($this->trans_type==ST_SALESENQ || $this->trans_type==ST_SALESQUOTE)
		return false;
	
		if ($this->trans_no==0)
			return false;
		$order_no = array_keys($this->trans_no);
		
		return  is_sales_order_started(reset($order_no));
	}

	/*
		Check payment terms and prepayments selected for this invoice,
		and updates	partial/final invoice value respectively.
	*/
	function update_payments()
	{
		$remainder = prepaid_invoice_remainder($this->order_no);

		// recalculate prepaid part from payments
		if ($this->payment_terms['days_before_due'] == -1)
		{	// this is partial invoice for selected prepayments made.
			$paid = 0;
			foreach($this->prepayments as $payment)
				$paid += $payment['amt'];
			$this->prep_amount = $this->trans_no ? $paid : min($remainder, $paid);
		} else	// this is final invoice
			$this->prep_amount = $remainder;
	}
} /* end of class defintion */

class line_details
{
	var $id;
	var $stock_id;
	var $item_description;
	var $units;
	var $mb_flag;
	var $tax_type;
	var $tax_type_name;
	var $src_no;	// number of src doc for this line
	var $src_id;
	var $price;
	var $discount_percent;
	
	var $standard_cost;
	var $descr_editable;
	var $disc_amount;
	var $valid; // validation in constructor
	/*
		Line quantity properties in various cart create modes:
		
		view:
		$quantity - quantity on current document
		$qty_done - quantity processed on all child documents
		$qty_dispatched - not used
		$qty_old - not used

		edit:
		$quantity - free parent quantity including this doc (= max allowed quantity)
		$qty_done - qty processed on child documents (= min allowed qty)
		$qty_dispatched - quantity currently selected to process
		$qty_old - quantity processed on this document before reedition 

		new child entry (view parent followed by prepare_child() call):
		$quantity - max allowed quantity (get from parent)
		$qty_done - qty processed on other child documents
		$qty_dispatched - quantity currently selected to process
		$qty_old - 0; not used
	*/
	var $quantity;
	var $qty_done;
	var $qty_dispatched;
	var $qty_old = 0;
	
	var $uom;
	var $total_price;
	var $item_number;
	var $foc_quantity;
	var $foc_qty_sent;
	

	function __construct($stock_id, $qty, $prc, $disc_percent,
		$qty_done, $standard_cost, $description, $id=0, $src_no=0, $src_id=0,$units='', $total_price=0, $item_number='',$trans_type='', $disc_amount=0,$foc_quantity=0,$foc_qty_sent=0)
	{
	/* Constructor function to add a new LineDetail object with passed params */

		$this->id = $id;
		$this->src_no = $src_no;
		$this->src_id = $src_id;
		$this->item_description = $description;
		$this->stock_id = $stock_id;
		$this->quantity = $qty;
		$this->qty_dispatched = $qty;
		
		$this->discount_percent = $disc_percent;
		$this->qty_done = $qty_done;
		$this->standard_cost = $standard_cost;
		$this->valid = true;			
		$this->item_number = $item_number;
		//$this->uom = $uom;
		$this->total_price = $total_price;
		
		$this->units = $units;
		$this->price = $prc;
		
		
		$this->foc_quantity = $foc_quantity;
		$this->foc_qty_sent = $foc_qty_sent;
		
		if($trans_type != ST_INSSALESENQ && $trans_type != ST_INSSALESQUOTE){
		$item_row = get_item($stock_id);

		if (!$item_row) 
			return;
			
		$this->mb_flag = $item_row["mb_flag"];
		//$this->units = $item_row["units"];
		
		$this->descr_editable = $item_row["editable"];
		if ($description == null || !$this->descr_editable)
			$this->item_description = $item_row["description"];
		else
			$this->item_description = $description;
		$this->tax_type = $item_row["tax_type_id"];
		$this->tax_type_name = $item_row["tax_type_name"];
		$this->disc_amount = $disc_amount;
		}
		
		
		
		
	}

	// get unit price as stated on document
	function line_price()
	{
		return $this->price;
	}
}

