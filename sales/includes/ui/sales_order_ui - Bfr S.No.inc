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
include_once($path_to_root . "/sales/includes/cart_class.inc");

//--------------------------------------------------------------------------------
function add_to_order(&$order, $new_item, $new_item_qty, $price, $discount=0, 
$description='', $disc_amount=0,$units="", $foc_quantity=0)
{
	// calculate item price to sum of kit element prices factor for 
	// value distribution over all exploded kit items
	 $std_price = get_kit_price($new_item, $order->customer_currency, 
		$order->sales_type,	$order->price_factor, get_post('OrderDate'), true);

	if ($std_price == 0)
		$price_factor = 0;
	else
		$price_factor = $price/$std_price;

	$kit = get_item_kit($new_item);
	$item_num = db_num_rows($kit);
	
	while($item = db_fetch($kit)) {
		$std_price = get_kit_price($item['stock_id'], $order->customer_currency, 
			$order->sales_type,	$order->price_factor, get_post('OrderDate'), true);
		
		// rounding differences are included in last price item in kit
		$item_num--;
		if ($item_num) {
			$price -= $item['quantity']*$std_price*$price_factor;
			$item_price = $std_price*$price_factor;
		} else {
			if ($item['quantity']) 
				$price = $price/$item['quantity'];
			$item_price = $price;
		}
		$item_price = round($item_price, user_price_dec());
		
	
		if (!$item['is_foreign'] && $item['item_code'] != $item['stock_id'])
		{	// this is sales kit - recurse 
			add_to_order($order, $item['stock_id'], $new_item_qty*$item['quantity'],
				$item_price, $discount,'',0,$units,$foc_quantity);
		}
		else
		{	// stock item record eventually with foreign code

			// check duplicate stock item
			foreach ($order->line_items as $order_item)
			{
				if (strcasecmp($order_item->stock_id, $item['stock_id']) == 0)
				{
					display_warning(_("For Part :").$item['stock_id']. " " 
						. _("This item is already on this document. You have been warned."));
					return false;
					break;
				}
			}

			$order->add_to_cart (count($order->line_items),	$item['stock_id'], 
				$new_item_qty*$item['quantity'], $item_price, $discount, 0,0, $description,0 ,0 ,0, $disc_amount,$units,$foc_quantity);
				
					
		}
	}

}

function add_to_temp2_order(&$order, $item_number, $item_desc, $quantity, $units, 
$unit_price, $total_price){

			$order->add_to_temp2_cart (count($order->line_items), $item_number, $item_desc, $quantity, $units, $unit_price, $total_price);
}
//---------------------------------------------------------------------------------

function get_customer_details_to_order(&$order, $customer_id, $branch_id)
{
	global $SysPrefs;
	
	$ret_error = "";

	$myrow = get_customer_to_order($customer_id);

	$name = $myrow['name'];

	if ($myrow['dissallow_invoices'] == 1)
		$ret_error = _("The selected customer account is currently on hold. Please contact the credit control personnel to discuss.");

	$deliver = $myrow['address']; // in case no branch address use company address

	$order->set_customer($customer_id, $name, $myrow['curr_code'], 
		$myrow['discount'], $myrow['payment_terms'], $myrow['pymt_discount']);

	// the sales type determines the price list to be used by default
	$order->set_sales_type($myrow['salestype'], $myrow['sales_type'], $myrow['tax_included'],
	    $myrow['factor']);

	//$order->credit = $myrow['cur_credit'];
	
	$company_curr = get_company_pref('curr_default');
	$ex_rate = get_date_exchange_rate($myrow['curr_code'], Today());
	
	if($company_curr != $myrow['curr_code']){
		$order->credit = $myrow['cur_credit']*$ex_rate;
	}
	else{
		$order->credit = $myrow['cur_credit'];
	}

	
	$order->dimension_id = $myrow['dimension_id'];
	$order->dimension2_id = $myrow['dimension2_id'];
	
	$result = get_branch_to_order($customer_id, $branch_id);

	if (db_num_rows($result) == 0)
	{
		return _("The selected customer and branch are not valid, or the customer does not have any branches.");
	}

	$myrow = db_fetch($result);
	
	
	//ravi
	$order->sales_person_id = $myrow['salesman'];
	

	// FIX - implement editable contact selector in sales order 
	$contact = get_branch_contacts($branch_id, 'order', $customer_id);
	$order->set_branch($branch_id, $myrow["tax_group_id"],
	$myrow["tax_group_name"], @$contact["phone"], @$contact["email"]);

	$address = trim($myrow["br_post_address"]) != '' ? $myrow["br_post_address"]
		: (trim($myrow["br_address"]) != '' ? $myrow["br_address"]:$deliver);

	$order->set_delivery($myrow["default_ship_via"], $myrow["br_name"],
		$address);
	if ($order->trans_type == ST_SALESINVOICE) {
		$order->due_date = get_invoice_duedate($order->payment, $order->document_date);
	}
	elseif ($order->trans_type == ST_SALESORDER)
		$order->due_date = add_days($order->document_date, $SysPrefs->default_delivery_required_by());
	elseif ($order->trans_type == ST_SALESQUOTE)
		$order->due_date = add_days($order->document_date, $SysPrefs->default_quote_valid_days());

	if($order->payment_terms['cash_sale']) {
		$order->set_location($order->pos["pos_location"], $order->pos["location_name"]);
	} else
		$order->set_location($myrow["default_location"], $myrow["location_name"]);

	return $ret_error;
}

//---------------------------------------------------------------------------------

function display_order_summary($title, &$order, $editable_items=false)
{

if(list_updated('Location')){
$total_available_qty = get_available_stock($_POST['stock_id'],$_POST['Location'],$_POST['OrderDate']);
}
if(list_updated('stock_id')){
$total_available_qty = get_available_stock($_POST['stock_id'],$_POST['Location'],$_POST['OrderDate']);
}

$total_available_qty = get_available_stock($_POST['stock_id'],$_POST['Location'],$_POST['OrderDate']);
if($total_available_qty == ''){
$total_available_qty = '0';
}else{
$total_available_qty  = $total_available_qty;
}

	display_heading($title);

    div_start('items_table');
	start_table(TABLESTYLE, "width='90%'");

	if($order->trans_type==ST_SALESENQ){
	$th = array(_("Item Code"), _("Item Description"), _("Quantity"),_("Delivered"),_("Unit"), "");
	}
	else if($order->trans_type==ST_SALESINVOICE || $order->trans_type==ST_CUSTDELIVERY){
	$th = array(_("Item Code"), _("Item Description"), _("Quantity"),
		_("Delivered"),  _("FOC Quantity"),
		_("Unit"), $order->tax_included ? _("Price after Tax") : _("Price before Tax"), _("Discount %"),_("Discount Amount"), _("Total"), "");
	}
	else {
	$th = array(_("Item Code"), _("Item Description"), _("Quantity"),
		_("Delivered"),
		_("Unit"), $order->tax_included ? _("Price after Tax") : _("Price before Tax"), _("Discount %"),_("Discount Amount"), _("Total"), "");
	}

	if ($order->trans_no == 0) {
	unset( $th[3] );
	}else if($order->trans_type==ST_SALESENQ)
	{
		unset( $th[3] );
	}

	if (count($order->line_items))
	     $th[]= '';

	table_header($th);

	$total = 0;
	$k = 0;  //row colour counter

	$id = find_submit('Edit');

	$low_stock = $order->check_qoh($_POST['OrderDate'], $_POST['Location']);
	foreach ($order->get_items() as $line_no=>$stock_item)
	{

		/* $line_total = round($stock_item->qty_dispatched * $stock_item->price * (1 - $stock_item->discount_percent),
		   user_price_dec()); */
		$line_total = round(($stock_item->qty_dispatched * $stock_item->price)-($stock_item->qty_dispatched * $stock_item->price * ($stock_item->discount_percent/100)), user_price_dec());
		
		$qoh_msg = '';
		if (!$editable_items || $id != $line_no)
		{
			if (in_array($stock_item->stock_id, $low_stock))
				start_row("class='stockmankobg'");	// notice low stock status
			else 
				alt_table_row_color($k);

			view_stock_status_cell($stock_item->stock_id);

			label_cell($stock_item->item_description );
			$dec = get_qty_dec($stock_item->stock_id);
			qty_cell($stock_item->qty_dispatched, false, $dec);

			if ($order->trans_no!=0 && ($order->trans_type!=ST_SALESENQ))
				qty_cell($stock_item->qty_done, false, $dec);
				
			if($order->trans_type==ST_CUSTDELIVERY || $order->trans_type==ST_SALESINVOICE){
			qty_cell($stock_item->foc_quantity, false, $dec);
            }	
				
				
			if($stock_item->units==1){
              $item_info = get_item_edit_info($stock_item->stock_id);
			  label_cell($item_info["units"]);
            }
            else if($stock_item->units==2){
			  $sec_unit_info = get_item_sec_unit_info($stock_item->stock_id);
			  label_cell($sec_unit_info["sec_unit_name"]);
             }			

			//label_cell($stock_item->units);
			
			if($order->trans_type!=ST_SALESENQ){
				amount_cell($stock_item->price);
				percent_cell($stock_item->discount_percent);
				amount_cell($stock_item->disc_amount);
				amount_cell($line_total);
			}
			if ($editable_items)
			{
				edit_button_cell("Edit$line_no", _("Edit"),
				_('Edit document line'));
				delete_button_cell("Delete$line_no", _("Delete"),
				_('Remove line from document'));
			}
			end_row();
		}
		else
		{
			sales_order_item_controls($order, $k,  $line_no);
		}

		$total += $line_total;
	}

	if ($id==-1 && $editable_items)
		sales_order_item_controls($order, $k);
	
    if($order->trans_type==ST_CUSTDELIVERY || $order->trans_type==ST_SALESINVOICE){
	$colspan =8;
	}	
		
	if($order->trans_type!=ST_SALESENQ){
	$colspan =7;
	if ($order->trans_no!=0)
		++$colspan;
	/* start_row();
	label_cell(_("Shipping Charge"), "colspan=$colspan align=right");
	small_amount_cells(null, 'freight_cost', price_format(get_post('freight_cost',0)));
	label_cell('', 'colspan=2');
	end_row(); */
	hidden('freight_cost',0);
	$display_sub_total = price_format($total + input_num('freight_cost'));
	label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan align=right","align=right", 2);
	$taxes = $order->get_taxes(input_num('freight_cost'));
	$tax_total = display_edit_tax_items($taxes, $colspan, $order->tax_included, 2);
	if($order->trans_type== ST_SALESINVOICE){
	start_row();
	label_cell(_("Round Off"), "colspan=$colspan align=right");
	small_amount_cells(null, 'ov_roundoff', price_format(get_post('ov_roundoff',0)));
	label_cell('', 'colspan=2');
	end_row();
	}
	$display_total = price_format(($total + input_num('freight_cost') + $tax_total + input_num('freight_cost')+  input_num('ov_roundoff')));

	start_row();
	label_cells(_("Amount Total"), $display_total, "colspan=$colspan align=right","align=right");
	submit_cells('update', _("Update"), "colspan=2 align='center'", _("Refresh"), true);
	end_row();
	}
	end_table();
	
	
	start_table(TABLESTYLE, "width='40%'");
	start_row();
	
	//$_POST['customer_id']=$order->customer_id;
	$cost_home = get_unit_cost(get_post('stock_id')); // Added 2011-03-27 Joe Hunt
	$item_cost = $cost_home / get_exchange_rate_from_home_currency($_SESSION['Items']->customer_currency, $_SESSION['Items']->document_date);
	
	$dec3 = user_price_dec();
	
	$cost = number_format2($item_cost, $dec3);
	
	$cust_sales_cost = get_customer_last_sales_invoice_cost(get_post('stock_id'),$_POST['customer_id']); 
	$sales_cost = get_last_sales_invoice_cost(get_post('stock_id'));
		


	echo '<h5 align="center" style="color:green; font-size: 11px;">Selected Item Available Quantity: '.$total_available_qty.'                     &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Selected Item Last Sales Price: '.$sales_cost.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Selected Customer Last Sales Price: '.$cust_sales_cost.' </h5>';
	
	
	
	if($order->trans_type== ST_SALESQUOTE){

	echo '<h5 align="center" style="color:green; font-size: 11px;"><a style="color:green; font-size: 11px;" target="_blank" href="./inquiry/sales_orders_view.php?type=32"  >Previous Transactions</a> </h5>';
	             
	}
	
	if($order->trans_type== ST_SALESORDER){

	echo '<h5 align="center" style="color:green; font-size: 11px;"><a style="color:green; font-size: 11px;" target="_blank" href="./inquiry/sales_orders_view.php?type=30">Previous Transactions</a> </h5>';
	          
	}
	
	if($order->trans_type== ST_SALESINVOICE){

	echo '<h5 align="center" style="color:green; font-size: 11px;"><a style="color:green; font-size: 11px;" target="_blank" href="./inquiry/customer_inquiry.php?filterType=1">Previous Transactions</a> </h5>';

	}
	
	
	if($order->trans_type== ST_CUSTDELIVERY){

	echo '<h5 align="center" style="color:green; font-size: 11px;"><a style="color:green; font-size: 11px;" target="_blank" href="./inquiry/customer_inquiry.php?filterType=5"  >Previous Transactions</a> </h5>';
	       
	}

	end_row();
	end_table();
	
	if ($low_stock)
		display_note(_("Marked items have insufficient quantities in stock as on day of delivery."), 0, 1, "class='stockmankofg'");

    div_end();
}

// ------------------------------------------------------------------------------

function display_order_header(&$order, $editable, $date_text)
{
	global $Ajax, $SysPrefs;

	start_outer_table(TABLESTYLE2, "width='80%'");

	table_section(1);
	
	$customer_error = "";
	$change_prices = 0;

	if (isset($order) && !$editable)
	{
		label_row(_("Customer:"), $order->customer_name . " - " . $order->deliver_to);
		hidden('customer_id', $order->customer_id);
		hidden('branch_id', $order->Branch);
		hidden('sales_type', $order->sales_type);
		
		hidden('dimension_id', $order->dimension_id); // 2008-11-12 Joe Hunt
		hidden('dimension2_id', $order->dimension2_id);
		
	}
	else
	{
		customer_list_row(_("Customer:"), 'customer_id', null, false, true, false, true);

		if ($order->customer_id != get_post('customer_id', -1))
		{
			// customer has changed
			$Ajax->activate('branch_id');
						
  		}
		customer_branches_list_row(_("Branch:"),
	  	  $_POST['customer_id'], 'branch_id', null, false, true, true, true);

		if( ($order->customer_id != get_post('customer_id', -1)) ||
			($order->Branch != get_post('branch_id', -1)) ||
				list_updated('customer_id')) 
		{

			if (!isset($_POST['branch_id']) || $_POST['branch_id'] == "")
			{
				// ignore errors on customer search box call
				if ($_POST['customer_id'] == '')
					$customer_error = _("No customer found for entered text.");
				else
					$customer_error = _("The selected customer does not have any branches. Please create at least one branch.");
				unset($_POST['branch_id']);
				$order->Branch = 0;
			} 
			else
			{

				$old_order = (PHP_VERSION<5) ? $order : clone $order;

				$customer_error = get_customer_details_to_order($order, $_POST['customer_id'], $_POST['branch_id']);
				$_POST['Location'] = $order->Location;
				$_POST['deliver_to'] = $order->deliver_to;
				$_POST['delivery_address'] = $order->delivery_address;
				$_POST['phone'] = $order->phone;
				$_POST['delivery_date'] = $order->due_date;
				
				$_POST['salesman'] = $order->salesman;
				
	
				if (!in_array($order->trans_type, array(ST_SALESQUOTE, ST_SALESORDER,ST_SALESENQ))
					&& ($order->pos['cash_sale'] != $order->pos['credit_sale'])
					&& (($order->payment_terms['cash_sale'] && !$order->pos['cash_sale']) ||
						(!$order->payment_terms['cash_sale'] && !$order->pos['credit_sale']))) {
							// force payment terms refresh if terms are editable
							// and pos have no permitions for terms selected in customer record.
							// Terms are set to first terms in allowed category below.
							display_warning(
								sprintf(_("Customer's payment terms '%s' cannot be selected on this POS"),
									$order->payment_terms['terms']));
							$order->payment = '';
				} elseif (get_post('payment') !== $order->payment) {
					$_POST['payment'] = $order->payment;
					$Ajax->activate('delivery');
					$Ajax->activate('payment');
				} else {
					if ($order->trans_type == ST_SALESINVOICE)
					{
						$_POST['delivery_date'] = $order->due_date;
						$Ajax->activate('delivery_date');
					}
					$Ajax->activate('Location');
					$Ajax->activate('deliver_to');
					$Ajax->activate('phone');
					$Ajax->activate('delivery_address');
					$Ajax->activate('salesman');
				}
				// change prices if necessary
				// what about discount in template case?
				if ($old_order->customer_currency != $order->customer_currency) {
					$change_prices = 1;
				}
				if ($old_order->sales_type != $order->sales_type) {
				//  || $old_order->default_discount!=$order->default_discount
					$_POST['sales_type'] = $order->sales_type;
					$Ajax->activate('sales_type');
					$change_prices = 1;
				}
				if ($old_order->dimension_id != $order->dimension_id) {
					$_POST['dimension_id'] = $order->dimension_id;
				    $Ajax->activate('dimension_id');
				}
				if ($old_order->dimension2_id != $order->dimension2_id) {
					$_POST['dimension2_id'] = $order->dimension2_id;
				    $Ajax->activate('dimension2_id');
				}
				
				
				unset($old_order);
				unset($_POST['customer_id']);
				
			}
			set_global_customer($_POST['customer_id']);
		} // changed branch
		else
		{
			$row = get_customer_to_order($_POST['customer_id']);
			if ($row && $row['dissallow_invoices'] == 1)
				$customer_error = _("The selected customer account is currently on hold. Please contact the credit control personnel to discuss.");
		}
	}
	
	if ($order->trans_type == ST_SALESINVOICE) {
	invoice_types_list_row(_("Invoice Type:"), 'invoice_type',null,true);
	
	  if (list_updated('invoice_type')){
	   $Ajax->activate('items_table');
	  }
	
	}
		
	if ($order->trans_type == ST_SALESINVOICE) {
	ref_row(_("Reference").':', 'ref', _('Reference number unique for this document type'), null, '', $order->trans_type, array('date'=> @$_POST['OrderDate'],'inv_type'=>$_POST['invoice_type']));
	}else{
	ref_row(_("Reference").':', 'ref', _('Reference number unique for this document type'), null, '', $order->trans_type, array('date'=> @$_POST['OrderDate']));
	}

	table_section(2);

	if (!is_company_currency($order->customer_currency) && in_array($order->trans_type, array(ST_SALESINVOICE, ST_CUSTDELIVERY)))
	{
		label_row(_("Customer Currency:"), $order->customer_currency);
		exchange_rate_display(get_company_currency(), $order->customer_currency,
			($editable ? $_POST['OrderDate'] : $order->document_date));
	}
    
    div_start('cust_credit');
	customer_credit_row($_POST['customer_id'], $order->credit);
    div_end();
    
	label_row(_("Customer Discount:"), ($order->default_discount * 100) . "%");

	table_section(3);
	start_row();
	if (($order->pos['cash_sale'] || $order->pos['credit_sale']) 
		&& !$order->is_started()) {
		 // editable payment type 
        if (isset($_POST['payment']) && $_POST['payment'] !== $order->payment) {
			$order->payment = get_post('payment');
			$order->payment_terms = get_payment_terms($order->payment);
			$order->due_date = get_invoice_duedate($order->payment, $order->document_date);
			if ($order->payment_terms['cash_sale']) {
				$_POST['Location'] = $order->Location = $order->pos['pos_location'];
				$order->location_name = $order->pos['location_name'];
			}
			$Ajax->activate('items_table');
			$Ajax->activate('delivery');
		}
		$paymcat = !$order->pos['cash_sale'] ? PM_CREDIT : 
			(!$order->pos['credit_sale'] ? PM_CASH : PM_ANY);
		// all terms are available for SO
		$is_order = in_array($order->trans_type, array(ST_SALESQUOTE, ST_SALESORDER, ST_SALESENQ));
		sale_payment_list_cells(_('Payment:'), 'payment', $is_order ? PM_ANY : $paymcat, null, true, $is_order);
	} else {
		//label_cells(_('Payment:'), $order->payment_terms['terms'], "class='label'");
		sale_payment_list_cells(_('Payment:'), 'payment', $order->payment_terms['terms'], null, true);
	}
	end_row();

	if($editable) {
		$str = sales_types_list_row(_("Price List:"), 'sales_type', null, true);
	} else {
		label_row(_("Price List:"), $order->sales_type_name);
	}
	if ($order->sales_type != $_POST['sales_type']) {
		$myrow = get_sales_type($_POST['sales_type']);
		$order->set_sales_type($myrow['id'], $myrow['sales_type'],
			$myrow['tax_included'], $myrow['factor']);
		$Ajax->activate('sales_type');
		$change_prices = 1;
	}

	table_section(4);

	if ($editable)
	{
		if (!isset($_POST['OrderDate']) || $_POST['OrderDate'] == "")
			$_POST['OrderDate'] = $order->document_date;

		date_row($date_text, 'OrderDate', _('Date of order receive'),
			$order->trans_no==0, 0, 0, 0, null, true);
		if (isset($_POST['_OrderDate_changed']) || list_updated('payment')) {
			if (!is_company_currency($order->customer_currency) 
				&& (get_base_sales_type()>0)) {
					$change_prices = 1;
			}
			$Ajax->activate('_ex_rate');
			if ($order->trans_type == ST_SALESINVOICE) {
				$_POST['delivery_date'] = get_invoice_duedate(get_post('payment'), get_post('OrderDate'));
			} else 
				$_POST['delivery_date'] = add_days(get_post('OrderDate'), $SysPrefs->default_delivery_required_by());
			$Ajax->activate('items_table');
			$Ajax->activate('delivery_date');
		}
		
			
	}
	else
	{
		label_row($date_text, $order->document_date);
		hidden('OrderDate', $order->document_date);
	}
	
	
	// 2008-11-12 Joe Hunt added dimensions
	$dim = get_company_pref('use_dimension');
	if ($dim > 0)
		dimensions_list_row(_("Dimension").":", 'dimension_id', 
					null, false, ' ', false, 1, false);
	else
		hidden('dimension_id', 0);
	if ($dim > 1)
		dimensions_list_row(_("Dimension")." 2:", 'dimension2_id', 
					null, false, ' ', false, 2, false);
	else
		hidden('dimension2_id', 0);
		
		
    
	
	
	//ravi
	
	 if(!isset($_POST['salesman']))
	{
		$_POST['salesman']=$order->sales_person_id;
	} 
	
	sales_persons_list_row(_("Sales Person:"), 'salesman',$_POST['salesman']); //ravi
	
	//Final Discount
	
	if($_POST['final_discount']==0){
	if($order->payment_terms['cash_sale'])
	{
	  $_POST['final_discount'] = 1.5;
	  
	}
	else{
	$_POST['final_discount'] = 0;
	}
	}
	if ($order->trans_type == ST_SALESQUOTE || $order->trans_type == ST_SALESORDER 
	|| $order->trans_type == ST_SALESINVOICE){
		if($_SESSION["wa_current_user"]->sales_disc_edit){
			table_section(5);
			start_row();
			small_amount_cells(_("Final Discount %"), 'final_discount', percent_format(get_post('final_discount',0)));
			submit_cells('calculate', _("Calculate"), "colspan=2 align='center'", _("Refresh"), true);
			end_row();
			start_row();
			small_amount_cells(_("Final Discount Amount"), 'final_discount_amount', price_format(get_post('final_discount_amount',0)));
			end_row();
			} else {
				table_section(5);
			start_row();
			small_amount_cells(_("Final Discount %"), 'final_discount', percent_format(get_post('final_discount',0)),null,null,2,true);
			submit_cells('calculate', _("Calculate"), "colspan=2 align='center'", _("Refresh"), true);
			end_row();
			start_row();
			small_amount_cells(_("Final Discount Amount"), 'final_discount_amount', price_format(get_post('final_discount_amount',0)),null,null,2,true);
			end_row();
			}
	}
	end_outer_table(1); // outer table

	if ($change_prices != 0) {
		foreach ($order->line_items as $line_no=>$item) {
			$line = &$order->line_items[$line_no];
			$line->price = get_kit_price($line->stock_id, $order->customer_currency,
				$order->sales_type, $order->price_factor, get_post('OrderDate'));
		}
	    $Ajax->activate('items_table');
	}
	
	// Final Discount
	if (input_num('final_discount') != 0 && isset($_POST['calculate'])) {
		foreach ($order->line_items as $line_no=>$item) {
		
			$line = &$order->line_items[$line_no];
			if(input_num('final_discount') != 0){
			$line->discount_percent =  input_num('final_discount');
			$line_sub_total  = $line->price*$line->quantity;
			$line->disc_amount = (($line_sub_total*$line->discount_percent/100)) ;
			}
			else{
			$line->discount_percent =  0;
			}
		}
		$Ajax->activate('discount_percent');
	    $Ajax->activate('items_table');
	}
	
	
	if (input_num('final_discount_amount') != 0) {
	$line_total = 0;
	foreach ($order->line_items as $line_no=>$item) {
		$line = &$order->line_items[$line_no];
		$line_total  += $line->price*$line->quantity;
	}
	
	$disc_per = input_num('final_discount_amount')*100/$line_total;
		foreach ($order->line_items as $line_no=>$item) {
		
			$line = &$order->line_items[$line_no];
			$line_items_count = count($order->line_items);
			$line_sub_total  = $line->price*$line->quantity;
		
		$final_discount = input_num('final_discount_amount');
			
			if(input_num('final_discount_amount') != 0){
			
			$line->disc_amount = $line_sub_total*$disc_per/100 ;
			$line->discount_percent = $disc_per;
			
			}
			else{
			$line->discount_percent =  0;
			}
		}
		$Ajax->activate('discount_percent');
	    $Ajax->activate('items_table');
	}
	
	
	if(isset($_POST['calculate']) && input_num('final_discount_amount') == 0 && input_num('final_discount') == 0){
	
	foreach ($order->line_items as $line_no=>$item) {
		
			$line = &$order->line_items[$line_no];
			$line_items_count = count($order->line_items);
			$line_sub_total  = $line->price*$line->quantity;
            $line->disc_amount = 0;
			$line->discount_percent =  0;

		}
		$Ajax->activate('discount_percent');
	    $Ajax->activate('items_table');
	
	}

// End
	
	return $customer_error;
}

//--------------------------------------------------------------------------------

function sales_order_item_controls(&$order, &$rowcounter, $line_no=-1)
{
    global $Ajax;

	alt_table_row_color($rowcounter);

	$id = find_submit('Edit');
	if ($line_no!=-1 && $line_no == $id) // edit old line
	{
		$_POST['stock_id'] = $order->line_items[$id]->stock_id;
		$dec = get_qty_dec($_POST['stock_id']);
		$_POST['qty'] = number_format2($order->line_items[$id]->qty_dispatched, $dec);
		$_POST['price'] = price_format($order->line_items[$id]->price);
		$_POST['discount_percent'] = percent_format($order->line_items[$id]->discount_percent);
		$_POST['disc_amount'] = $order->line_items[$id]->disc_amount;
		//$units = $order->line_items[$id]->units;
		
		$_POST['units'] = $order->line_items[$id]->units;
		$_POST['foc_quantity'] = number_format2($order->line_items[$id]->foc_quantity, $dec);
		
		$_POST['item_description'] = $order->line_items[$id]->item_description;
		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['stock_id']);
		if ($order->line_items[$id]->descr_editable)
		textarea_cells(null,'item_description', null, 50,5);
			//text_cells(null,'item_description', null, 45, 50);
		else {
			hidden('item_description', $_POST['item_description']);
			label_cell($_POST['item_description']);
		}
	    $Ajax->activate('items_table');
	}
	else	// prepare new line
	{
		if ($order->fixed_asset)
            stock_disposable_fa_list_cells(null,'stock_id', null, _('[Select item]'), true, $order->line_items);
        else {
            if (!isset($_POST['stock_id'])) {   // Check if stock_id dosesn't exist
				$_POST['stock_id'] = last_sales_order_detail($order, 'stk_code');  //get the llast sales stock id.
			}
           sales_items_list_cells(null,'stock_id', $_POST['stock_id'], _("Select an Item"), true, true);
        }
        if (list_updated('stock_id') || isset($_POST['OrderDate'])) {            
            $Ajax->activate('price');
            $Ajax->activate('units');
            $Ajax->activate('qty');
			$Ajax->activate('discount_percent');
            $Ajax->activate('line_total');
        }		

		$item_info = get_item_edit_info($_POST['stock_id']);
		
		//$units = ''; 
		$dec = 2;
		if ($item_info) {
				//$units = $item_info["units"];
				$dec = $item_info['decimals'];
		}
		$_POST['qty'] = number_format2(0, $dec);
		
		
		//promotional discount
	     $item_promotional_discount = get_sale_item_promotional_discount_by_item($_POST['OrderDate'],$_POST['stock_id']);
		if(!$item_promotional_discount){
			$item_promotional_discount = get_sale_item_promotional_discount_by_supplier($_POST['OrderDate'],$_POST['stock_id']);
		 }
		if(!$item_promotional_discount){
	     $item_promotional_discount = get_sale_item_promotional_discount_by_category($_POST['OrderDate'],$_POST['stock_id']);
		 }
		 
		 if($item_promotional_discount)
		 {
			  $_POST['discount_percent']=$item_promotional_discount;
		 }
		 else
		 {
		// default to the customer's discount %
		  $_POST['discount_percent'] = percent_format($order->default_discount * 100);
		 }
	}

	if ($order->fixed_asset) {
		label_cell(1, '', 'qty');
		hidden('qty', 1);
	} else 
		qty_cells(null, 'qty', $_POST['qty'], null, null, $dec);

	if ($order->trans_no!=0 && ($order->trans_type!=ST_SALESENQ)) {
		qty_cell($line_no==-1 ? 0 :$order->line_items[$line_no]->qty_done, false, $dec);
	}
	
	if($order->trans_type==ST_CUSTDELIVERY || $order->trans_type==ST_SALESINVOICE) {
	qty_cells(null, 'foc_quantity', null, null, null, $dec);
	}

	//ravi
	// label_cell($units, '', 'units');
	stock_item_units_list_cells('units',null,$_POST['stock_id'],true);
	
	
	$price = get_kit_price($_POST['stock_id'],
			$order->customer_currency, $order->sales_type,
			$order->price_factor, get_post('OrderDate'));
			
	if($_POST['units']==1){		
	$_POST['price'] = price_format($price);
	}
	else{
	$sec_unit_info = get_item_sec_unit_info($_POST['stock_id']);
	
	if($sec_unit_info['conversion_factor']!='')
	$sec_unit_conv_factor = $sec_unit_info['conversion_factor'];
	else
	$sec_unit_conv_factor = 1;
	
	$sec_uom_price = ($price/$sec_unit_conv_factor);
	
	$_POST['price'] = price_format($sec_uom_price);
	}
	
	
	if (list_updated('units')) {            
        $Ajax->activate('price');
    }
	
	
	if($order->trans_type!=ST_SALESENQ){
	amount_cells(null, 'price');

    if($_SESSION["wa_current_user"]->sales_disc_edit){
	small_amount_cells(null, 'discount_percent', percent_format($_POST['discount_percent']), null, null, user_percent_dec());
	small_amount_cells(null, 'disc_amount', null, null, null, 3);
	}
    else{
		small_amount_cells(null, 'discount_percent', percent_format($_POST['discount_percent']), null, null, user_percent_dec(),true);	
		small_amount_cells(null, 'disc_amount', null, null, null, 3,true);
	}
	// small_amount_cells(null, 'disc_amount', null, null, null, 3);
	$line_total = input_num('qty') * input_num('price') * (1 - input_num('discount_percent') / 100);

	amount_cell($line_total, false, '','line_total');
	}

	if ($id!=-1)
	{
		button_cell('UpdateItem', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
		hidden('LineNo', $line_no);
		set_focus('qty');
	}
	else
	{
		submit_cells('AddItem', _("Add Item"), "colspan=2 align='center'",
		    _('Add new item to document'), true);
	}

	end_row();
	

}

//--------------------------------------------------------------------------------

function display_delivery_details(&$order)
{
	global $Ajax;

	div_start('delivery');	


	if ($order->payment_terms['cash_sale']) {	// Direct payment sale
		$Ajax->activate('items_table');
		display_heading(_('Cash payment'));
		start_table(TABLESTYLE2, "width='60%'");

		locations_list_row(_("Deliver from Location:"), 'Location', null, false, true);
		
 		if (list_updated('Location'))
 			$Ajax->activate('items_table');
		label_row(_("Cash account:"), $order->pos['bank_account_name']);
		textarea_row(_("Comments:"), "Comments", $order->Comments, 31, 5);
		end_table();
		hidden('delivery_date', $order->due_date);
	} else {
		if ($order->trans_type==ST_SALESINVOICE)
		{
			$title = _("Delivery Details");
			$delname = _("Due Date").':';
		}
		elseif ($order->trans_type==ST_CUSTDELIVERY)
		{
			$title = _("Invoice Delivery Details");
			$delname = _("Invoice before").':';
		}
		elseif ($order->trans_type==ST_SALESQUOTE || $order->trans_type==ST_INSSALESENQ)
		{
			$title = _("Quotation Delivery Details");
			$delname = _("Valid until").':';
		}
		elseif ($order->trans_type==ST_SALESENQ)
		{
			$title = _("Enquiry Delivery Details");
			$delname = _("Valid until").':';
		}
		else
		{
			$title = _("Order Delivery Details");
			//$delname = _("Required Delivery Date").':';
            $delname = _("Preferred Delivery Date").':';
		}
		display_heading($title);
		start_outer_table(TABLESTYLE2, "width='90%'");
		table_section(1);

		locations_list_row(_("Deliver from Location:"), 'Location', null, false, true, $order->fixed_asset);
		if ($order->payment_terms['days_before_due'] == -1)
		{
			$Ajax->addUpdate('items_table', 'prep_amount', price_format($order->get_trans_total())); // bind to items_table update
			if (!$order->is_started())
				amount_row(_("Pre-Payment Required:"), 'prep_amount');
			else
				label_row(_("Pre-Payment Required:"), price_format($order->prep_amount), "class='label'");
		}
		if (list_updated('Location'))
			$Ajax->activate('items_table');


		
        date_row($delname, 'delivery_date',
			$order->trans_type==ST_SALESORDER ?  _('Enter requested day of delivery') 
				: ($order->trans_type==ST_SALESQUOTE ? _('Enter Valid until Date') : (
				$order->trans_type==ST_SALESENQ ? _('Enter Valid until Date'):'')));
				
			
        if($order->trans_type==ST_SALESORDER){			
		delivery_times_list_row(_("Preferred Delivery Time:"),'delivery_time',null,false);
        } 		
		
				
		text_row(_("Deliver To:"), 'deliver_to', $order->deliver_to, 50, 60,
			_('Additional identifier for delivery e.g. name of receiving person'));

		textarea_row(_("Address:"), 'delivery_address', $order->delivery_address, 35, 5, 255,
			_('Delivery address. Default is address of customer branch'));

		table_section(2);

		text_row(_("Contact Phone Number:"), 'phone', $order->phone, 25, 25,
		    _('Phone number of ordering person. Defaults to branch phone number'));
		text_row(_("Customer Reference:"), 'cust_ref', $order->cust_ref, 25, 25,
		  _('Customer reference number for this order (if any)'));
		  
		  
		
        text_row(_("LPO No.:"), 'lpo_no', $order->lpo_no, 25, 25);	
		date_row(_("LPO Date :"), 'lpo_date', '', true);
        	
		  
		  
		if($order->trans_type==ST_SALESORDER)  
		textarea_row(_("Comments / Partial Delivery Details:"), "Comments", $order->Comments, 31, 5);
		else
		textarea_row(_("Comments:"), "Comments", $order->Comments, 31, 5);
		shippers_list_row(_("Shipping Company:"), 'ship_via', $order->ship_via);

		end_outer_table(1);
	}
	div_end();
}

//---------------------------------------------------------------------------------
//qoh qty
function get_available_stock($stock_id,$location,$order_date){
$ord_date = date2sql($order_date);
$sql = "SELECT sum(qty) as quantity FROM ".TB_PREF."stock_moves WHERE stock_id=".db_escape($stock_id)." AND loc_code=".db_escape($location)." AND tran_date <= ".db_escape($ord_date)."";
//display_error($sql);
$res = db_query($sql);
$result = db_fetch_row($res);

$demand_qty=get_demand_qty($stock_id,$location);

return $result['0']-$demand_qty; 

}


function display_order_temp2_summary($title, &$order, $editable_items=false)
{

	display_heading($title);

    div_start('items_table');
	start_table(TABLESTYLE, "width='100%'");

	if($order->trans_type==ST_INSSALESENQ){
	$th = array(_("Item Code"), _("Description"),_("Qty"),_("Unit"),  "");
	}else {
		$th = array(_("Item Code"), _("Description"),_("Qty"),_("Unit"),_("Rate"),_("Amount"),  "");
	}
	
	if (count($order->line_items))
	     $th[]= '';

	table_header($th);

	$total = 0;
	$k = 0;  //row colour counter

	$id = find_submit('Edit');
	//$low_stock = $order->check_qoh($_POST['OrderDate'], $_POST['Location']);

	foreach ($order->get_items() as $line_no=>$stock_item)
	{
	

		$line_total = round($stock_item->qty_dispatched * $stock_item->price * (1 - $stock_item->discount_percent),
		   user_price_dec());

		$qoh_msg = '';
		if (!$editable_items || $id != $line_no)
		{
			// if (in_array($stock_item->stock_id, $low_stock))
			// 	start_row("class='stockmankobg'");	// notice low stock status
			// else 
				alt_table_row_color($k);

			label_cell($stock_item->item_number);
			label_cell($stock_item->item_description );
			qty_cell($stock_item->quantity, false, $dec);
			label_cell($stock_item->units);
			
		if($order->trans_type==ST_INSSALESQUOTE){
			amount_cell($stock_item->price);
			amount_cell($stock_item->total_price);
		}
			if ($editable_items)
			{
				edit_button_cell("Edit$line_no", _("Edit"),
				_('Edit document line'));
				delete_button_cell("Delete$line_no", _("Delete"),
				_('Remove line from document'));
			}
			end_row();
		}
		else
		{
			sales_order_temp2_item_controls($order, $k,  $line_no);
		}

		$total += $line_total;
	}

	if ($id==-1 && $editable_items)
		sales_order_temp2_item_controls($order, $k);
	if($order->trans_type==ST_INSSALESQUOTE){
		$colspan = 5;
	}else {
		$colspan = 3;
	}
	if ($order->trans_no!=0)
		++$colspan;
	start_row();
	//label_cell(_("Shipping Charge"), "colspan=$colspan align=right");
	//small_amount_cells(null, 'freight_cost', price_format(get_post('freight_cost',0)));
	//label_cell('', 'colspan=2');
	end_row();
	$display_sub_total = price_format($total + input_num('freight_cost'));
	label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan align=right","align=right", 2);
	$taxes = $order->get_taxes(input_num('freight_cost'));
	$tax_total = display_edit_tax_items($taxes, $colspan, $order->tax_included, 2);

	$display_total = price_format(($total + input_num('freight_cost') + $tax_total));

	start_row();
	label_cells(_("Amount Total"), $display_total, "colspan=$colspan align=right","align=right");
	submit_cells('update', _("Update"), "colspan=$colspan align='center'", _("Refresh"), true);
	end_row();

	end_table();
	
	
	start_table(TABLESTYLE, "width='40%'");
    start_row();
			
	echo '<h5 align="center" style="color:green; font-size: 11px;"><a style="color:green; font-size: 11px;" target="_blank" href="./inquiry/sales_insquote_view.php?type=37"  >Previous Transactions</a> </h5>';
	
    end_row();
	end_table();
	


	if ($low_stock)
		display_note(_("Marked items have insufficient quantities in stock as on day of delivery."), 0, 1, "class='stockmankofg'");

    div_end();
}


function sales_order_temp2_item_controls(&$order, &$rowcounter, $line_no=-1)
{
    global $Ajax;

	alt_table_row_color($rowcounter);

	$id = find_submit('Edit');
	if ($line_no!=-1 && $line_no == $id) // edit old line
	{
		$_POST['units'] = $order->line_items[$id]->units;
		$_POST['qty'] = number_format2($order->line_items[$id]->quantity, $dec);
		$_POST['unit_price'] = price_format($order->line_items[$id]->price);
		$_POST['item_description'] = $order->line_items[$id]->item_description;
		$_POST['item_number'] = $order->line_items[$id]->item_number;
		$_POST['total_price'] = price_format($order->line_items[$id]->total_price);
		
	    $Ajax->activate('items_table');
	}
	else	// prepare new line
	{
		
	}
	text_cells_ex(null, 'item_number', 10, 10, null, null,null,null,true);
	textarea_cells(null,'item_description', null, 80, 5);
	qty_cells(null, 'qty', $_POST['qty'], null, null, $dec);
	text_cells(null,'units',null,5,5);
	if($order->trans_type==ST_INSSALESQUOTE){
		amount_cells(null, 'unit_price');
		amount_cells(null, 'total_price');
	}
	if ($id!=-1)
	{
		button_cell('UpdateItem', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
		hidden('LineNo', $line_no);
		set_focus('qty');
	}
	else
	{
		submit_cells('AddItem', _("Add Item"), "colspan=2 align='center'",
		    _('Add new item to document'), true);
	}

	end_row();
}
