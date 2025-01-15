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
include_once($path_to_root . "/includes/prefs/sysprefs.inc");
include_once($path_to_root . "/sales/includes/db/sm_collection_db.inc");

class sm_collection_cart
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
	//var $customer_id;
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
	
	var $bank_account;
	var $sales_person_ref;
	
	var $memo_;
	
	
	function __construct($type, $trans_no=0)
	{
		$this->trans_type = $type;
		$this->order_id = $trans_no;
		$this->clear_items();
		
	}
	
	
	function clear_items()
	{
    	unset($this->line_items);
		$this->line_items = array();
	}
	function add_all_cart_items($items)
	{
		$this->clear_items();
		foreach ($items as $item) {
			$trans_type = $item['type'];
			$trans_no = $item['trans_no'];
			$customer_id = $item['customer_id'];
			$branch_id = $item['branch_id'];
			$invoice_no = $item['invoice_no'];
			$amount = $item['amount'];

			$this->line_items[] = new sm_line_item($customer_id, $branch_id, $invoice_no, $amount, $trans_type, $trans_no);
		}
	}
	
	function add_to_cart($line_no, $customer_id="", $branch_id="", $invoice_no="", $amount=0)
	
	{	
		if (isset($amount))
		{
			$this->line_items[$line_no] = new sm_line_item($customer_id, $branch_id, $invoice_no, $amount);
			return true;
		}
		else
		{
			display_error("unexpected - adding an invalid estimate or null project", "", true);
		}

		return false;
	}	
	
	function update_cart_item($line_no, $customer_id="", $branch_id="", $invoice_no="", 
	$amount=0)
	{
		$this->line_items[$line_no]->customer_id  = $customer_id;
		$this->line_items[$line_no]->branch_id    = $branch_id;
		$this->line_items[$line_no]->invoice_no   = $invoice_no;
		$this->line_items[$line_no]->amount       = $amount;
		
	}
	
	
	function find_cart_item($customer_id,$branch_id,$invoice_no)
	{
		foreach($this->line_items as $line_no=>$line) {
			if ($line->customer_id == $customer_id && $line->branch_id == $branch_id && $line->invoice_no == $invoice_no)
			return $this->line_items[$line_no];
		}
		return null;
	}
	
	function remove_from_cart($line_no)
	{
		array_splice($this->line_items, $line_no, 1);
	}
	function count_items()
	{
		return count($this->line_items);
	}
}

class sm_line_item
{
	var $customer_id;
	var $branch_id;
	var $invoice_no;
	var $amount;
	var $trans_type;
	var $trans_no;
	
	function __construct($customer_id, $branch_id="", $invoice_no="", $amount=0, $trans_type = null, $trans_no = null)
	{
	
		$this->customer_id   = $customer_id;
		$this->branch_id     = $branch_id;
		$this->invoice_no    = $invoice_no;
		$this->amount        = $amount;
		$this->trans_type = $trans_type;
		$this->trans_no = $trans_no;
		
	}
}


