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
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");

//--------------------------------------------------------------------------------------------------

function copy_from_cart()
{
	$cart = &$_SESSION['PO'];

	$_POST['supplier_id'] = $cart->supplier_id;
	$_POST['OrderDate'] = $cart->orig_order_date;
	if ($cart->trans_type == ST_SUPPINVOICE)
		$_POST['due_date'] = $cart->due_date;
    $_POST['supp_ref'] = $cart->supp_ref;
    $_POST['ref'] = $cart->reference;
	$_POST['Comments'] = $cart->Comments;
    $_POST['StkLocation'] = $cart->Location;
    $_POST['delivery_address'] = $cart->delivery_address;
	$_POST['prep_amount'] = price_format($cart->prep_amount);
	$_POST['_ex_rate'] = $cart->ex_rate;
	$_POST['cash_account'] = $cart->cash_account;
    $_POST['dimension'] = $cart->dimension;
    $_POST['dimension2'] = $cart->dimension2;
	$_POST['final_discount'] = percent_format($cart->final_discount);
	$_POST['final_discount_amount'] = price_format($cart->final_discount_amount);
	
	$_POST['freight_cost'] = price_format($cart->freight_cost);
	$_POST['additional_charges'] = price_format($cart->additional_charges);
	$_POST['packing_charges'] = price_format($cart->packing_charges);
	$_POST['other_charges'] = price_format($cart->other_charges);
	//Roundoff
	$_POST['ov_roundoff'] = price_format($cart->ov_roundoff);
	
	$_POST['generated_by'] = $cart->generated_by;
	
	$_POST['comparison_code']=$cart->comparison_code;
	
	$_POST['rc_supplier_id'] = $cart->rc_supplier_id;
	$_POST['rc_supplier_invoice_id'] = $cart->rc_supplier_invoice_id;
	$_POST['rc_invoice_date'] = $cart->rc_invoice_date;
	$_POST['rc_invoice_amount'] = price_format($cart->rc_invoice_amount);
	$_POST['reverse_charge'] = $cart->reverse_charge;
	$_POST['rc_custom_total'] = $cart->rc_custom_total;
	$_POST['rc_curr_code'] = $cart->rc_curr_code;
	$_POST['rc_bill_no'] = $cart->rc_bill_no;
	
	$_POST['bill_date'] = $cart->bill_date;
	
	$_POST['declaration_no']   = $cart->declaration_no;
	$_POST['cif_value']        = price_format($cart->cif_value);
	$_POST['vat_import_value'] = price_format($cart->vat_import_value);
	$_POST['custom_duties']    = price_format($cart->custom_duties);
	$_POST['container_number']   = $cart->container_number;
	$_POST['bl_no']              = $cart->bl_no;
	
    foreach($cart->tax_overrides as $id => $value)
	    $_POST['mantax'][$id] = price_format($value);
}

function copy_to_cart()
{
	$cart = &$_SESSION['PO'];

	$cart->supplier_id = $_POST['supplier_id'];	
	$cart->orig_order_date = $_POST['OrderDate'];
	if ($cart->trans_type == ST_SUPPINVOICE)
	{
		$cart->due_date = $_POST['due_date'];
		$cart->cash_account = get_post('cash_account');
	}
	$cart->reference = $_POST['ref'];
	$cart->supp_ref = $_POST['supp_ref'];
	$cart->Comments = $_POST['Comments'];	
	$cart->Location = $_POST['StkLocation'];
	$cart->delivery_address = $_POST['delivery_address'];
   	$cart->dimension = @$_POST['dimension'];
	$cart->dimension2 =  @$_POST['dimension2'];
	$cart->prep_amount = input_num('prep_amount', 0);
	$cart->ex_rate = input_num('_ex_rate', null);
	$cart->final_discount = input_num('final_discount');
	$cart->final_discount_amount = input_num('final_discount_amount');
	
	$cart->freight_cost = input_num('freight_cost');
	$cart->additional_charges = input_num('additional_charges');
	$cart->packing_charges = input_num('packing_charges');
	$cart->other_charges = input_num('other_charges');
	$cart->ov_roundoff = input_num('ov_roundoff');
	
	$cart->generated_by=$_POST['generated_by'];
	$cart->comparison_code=$_POST['comparison_code'];
	
	$cart->rc_supplier_id = $_POST['rc_supplier_id'];
	$cart->rc_supplier_invoice_id = $_POST['rc_supplier_invoice_id'];
	$cart->rc_invoice_date = $_POST['rc_invoice_date'];
	$cart->rc_invoice_amount = input_num('rc_invoice_amount'); 
	$cart->reverse_charge = $_POST['reverse_charge'];
	$cart->rc_custom_total = $_POST['rc_custom_total'];
	$cart->rc_curr_code =  $_POST['rc_curr_code'];
	$cart->rc_bill_no = $_POST['rc_bill_no'];
	
	$cart->bill_date = $_POST['bill_date'];
	
	
	$cart->declaration_no   = $_POST['declaration_no'];
	$cart->cif_value        = input_num('cif_value');
	$cart->vat_import_value = input_num('vat_import_value');
	$cart->custom_duties    = input_num('custom_duties');
	$cart->container_number   = $_POST['container_number'];
	$cart->bl_no              = $_POST['bl_no'];

    if (isset($_POST['mantax'])) {
		foreach($_POST['mantax'] as $id => $tax) {
			$cart->tax_overrides[$id] = user_numeric($_POST['mantax'][$id]); }
	}
}

//---------------------------------------------------------------------------------------------------

function create_new_po($trans_type, $trans_no,$move="",$se=0)
{
	global $Refs;
	
		
	if (isset($_SESSION['PO']))
		unset ($_SESSION['PO']->line_items, $_SESSION['PO']);

	$cart = new purch_order;
	$_POST['OrderDate'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['OrderDate']))
		$_POST['OrderDate'] = end_fiscalyear();
	$cart->due_date = $cart->orig_order_date = $_POST['OrderDate'];

	$cart->trans_type = $trans_type;
	$cart->order_no = $trans_no;
	/*read in all the selected order into the Items cart  */
	if ($trans_no) {
		read_po($trans_no, $cart,false,$trans_type,$move);
		$cart->order_no = $trans_no;
	} else
	{
		$cart->reference = $Refs->get_next($trans_type, null,
			array('supplier_id' => $cart->supplier_id, 'date' => get_post('OrderDate')));
			//ravi	
		 if($trans_type==18)
		{
			$comp_prefs = get_company_prefs();
			$cart->Comments=$comp_prefs['shipping_terms'];	
		}
		if($se)	
		{
			read_se_items($cart,$se);
			$cart->supp_ref=get_sales_enquiry_reference($se);
			$cart->Comments = get_sales_enquiry_comments($se);
		}
	}
			
    if($move=="ENQ-QUOT")
   {
   
     $cart->reference = $Refs->get_next(ST_PURCHQUOTE);
	 $cart->enq_ref=$cart->supp_ref;
	 $cart->supp_ref="";
	 $cart->trans_type = ST_PURCHQUOTE;
     unset($cart->order_no);	 
   }		
			
	if($move=="QUOT-ORD")
   {
     $cart->reference = $Refs->get_next(ST_PURCHORDER);
	 $cart->trans_type = ST_PURCHORDER;
	 unset($cart->order_no);
   }	
   
	$_SESSION['PO'] = &$cart;
}

//---------------------------------------------------------------------------------------------------

function display_po_header(&$order,$rev_status=0)
{
	global $Ajax, $Refs;

	$editable = ($order->order_no == 0);

	start_outer_table(TABLESTYLE2, "width='80%'");

	table_section(1);
    if ($editable)
    {
        if (!isset($_POST['supplier_id']) && (get_global_supplier() != ALL_TEXT))
        	$_POST['supplier_id'] = get_global_supplier();

    	supplier_list_row(_("Supplier:"), 'supplier_id', null, false, true, false, true);
	}
	else
	{
		hidden('supplier_id', $order->supplier_id);
		label_row(_("Supplier:"), $order->supplier_name);
    }

	if ($order->supplier_id != get_post('supplier_id',-1)) {
		$old_supp = $order->supplier_id;
		get_supplier_details_to_order($order, $_POST['supplier_id']); 
	    get_duedate_from_terms($order);
		$_POST['due_date'] = $order->due_date;

		// supplier default price update
		foreach ($order->line_items as $line_no=>$item) {
			$line = &$order->line_items[$line_no];
			$line->price =  get_purchase_price ($order->supplier_id, $line->stock_id);
			$line->quantity = $line->quantity/get_purchase_conversion_factor ($old_supp, $line->stock_id)
					*get_purchase_conversion_factor ($order->supplier_id, $line->stock_id);
		}
	    $Ajax->activate('items_table');
	    $Ajax->activate('due_date');
	}
	set_global_supplier($_POST['supplier_id']);
	
	
		if($order->trans_type==ST_PURCHENQ){
date_row(_("Enquiry Date:") ,
		'OrderDate', '', true, 0, 0, 0, null, true);
}else if($order->trans_type==ST_PURCHQUOTE){
date_row(_("Quotation Date:") ,
		'OrderDate', '', true, 0, 0, 0, null, true);
}else{
	date_row($order->trans_type==ST_PURCHORDER ? _("Order Date:") :
		($order->trans_type==ST_SUPPRECEIVE ? _("Delivery Date:") : _("Booking Date:")),
		'OrderDate', '', true, 0, 0, 0, null, true);
}
	if (isset($_POST['_OrderDate_changed'])) {
		$order->orig_order_date = $_POST['OrderDate'];
	    get_duedate_from_terms($order);
	    $_POST['due_date'] = $order->due_date;
		$Ajax->activate('due_date');
	}
	supplier_credit_row($order->supplier_id, $order->credit);

	if (!is_company_currency($order->curr_code))
	{
		label_row(_("Supplier Currency:"), $order->curr_code);
		exchange_rate_display(get_company_currency(), $order->curr_code,
			$_POST['OrderDate']);
	}

    if ($editable)
    {
    	ref_row(_("Reference:"), 'ref', '', null, false, $order->trans_type, array('date'=> @$_POST['OrderDate']));
    }
    else
    {
    	hidden('ref', $order->reference);
    	label_row(_("Reference:"), $order->reference);
    }

	table_section(2);
	if ($order->trans_type==ST_SUPPINVOICE){
	text_row(_("Supplier's Bill No.:"), 'supp_ref', null, 20, 60);
	}
	else{
	text_row(_("Supplier's Reference:"), 'supp_ref', null, 20, 60);
	}

	    if ($order->trans_type==ST_SUPPINVOICE){
		date_row(_("Bill Date:"), 'bill_date', '', false, 0, 0, 0, null, true);
		
		date_row(_("Due Date:"), 'due_date', '', false, 0, 0, 0, null, true);
		}

  	

	if (get_company_pref('use_dimension'))
		dimensions_list_row(_('Dimension').':', 'dimension', null, true, _('Default'), false, 1);
	if (get_company_pref('use_dimension') == 2)
		dimensions_list_row(_('Dimension 2').':', 'dimension2', null, true, _('Default'), false, 2);
	locations_list_row(_("Receive Into:"), 'StkLocation', null, false, true, $order->fixed_asset); 

	table_section(3);

    if (!isset($_POST['StkLocation']) || $_POST['StkLocation'] == "" ||
    	isset($_POST['_StkLocation_update']) || !isset($_POST['delivery_address']) ||
    	$_POST['delivery_address'] == "")
    {
    	/*If this is the first time the form loaded set up defaults */

		$loc_row = get_item_location(get_post('StkLocation'));
        if ($loc_row)
        {
    	  	$_POST['delivery_address'] = $loc_row["delivery_address"];
			$Ajax->activate('delivery_address');
    	  	$_SESSION['PO']->Location = $_POST['StkLocation'];
    	  	$_SESSION['PO']->delivery_address = $_POST['delivery_address'];

        }
        else
        { /*The default location of the user is crook */
    	  	display_error(_("The default stock location set up for this user is not a currently defined stock location. Your system administrator needs to amend your user record."));
        }
    }

	textarea_row(_("Deliver to:"), 'delivery_address', $_POST['delivery_address'], 35, 4);
		//Final Discount
	if ($order->trans_type!=ST_PURCHENQ){
	start_row();
	small_amount_cells(_("Final Discount %"), 'final_discount', percent_format(get_post('final_discount',0)));
	submit_cells('calculate', _("Calculate"), "colspan=2 align='center'", _("Refresh"), true);
	end_row();
		
	start_row();
	small_amount_cells(_("Final Discount Amount"), 'final_discount_amount', price_format(get_post('final_discount_amount',0)));
	end_row();
	}
	
	if($order->trans_type==ST_PURCHQUOTE)
	{
	  $sql="SELECT * FROM ".TB_PREF."purch_orders where trans_type=36 and order_no=".db_escape($_GET['NewEnqToPurchQuote'])." ";
	  $res =db_query($sql);
	  $result = db_fetch($res);
	  table_section_title(_("For Quotation Comparision"));
	  if($_GET['NewEnqToPurchQuote']!='')
	  {
	  text_row(_("Comparision Code:"), 'comparison_code', $result['reference'], 16, 15);
	  }
	  else
	  {
	  text_row(_("Comparision Code:"), 'comparison_code', null, 16, 15);
	  }
	}	 
	
	
	if($order->trans_type==ST_PURCHORDER)
	{
		   label_row(_("Generated By: "), $_SESSION["wa_current_user"]->username);
	        hidden('generated_by',$_SESSION["wa_current_user"]->username);
	}
	
	if ($order->trans_type==ST_SUPPINVOICE){
	table_section(4);	
	text_row(_("Declaration No.:"), 'declaration_no', null, 20, 60);
	small_amount_row(_("CIF Value"), 'cif_value',price_format(get_post('cif_value',0)));
	
	small_amount_row(_("VAT Import Value"), 'vat_import_value',
	price_format(get_post('vat_import_value',0)));
	
	small_amount_row(_("Custom Duty"), 'custom_duties',
	price_format(get_post('custom_duties',0)));
	
	text_row(_("Container Number:"), 'container_number', null, 20, 60);
	text_row(_("BL No.:"), 'bl_no', null, 20, 60);
	
	}
	
	
	
	
	if ($order->trans_type==ST_SUPPINVOICE && ($rev_status=='1')){
	table_section(5);	
	reverse_charge_status_list_row(_("Against Reverse Charge:"), 'reverse_charge',null,true);
	if($_POST['reverse_charge']==1)
	{
	
	supplier_list_row(_("Supplier:"), 'rc_supplier_id', null, true, true, false, true);
	
	supplier_invoice_list_row(_("Purchase Reference:"),  'rc_supplier_invoice_id', null, true, 
	true, false,  false,$_POST['rc_supplier_id']);
	$invoice_record = get_invoice_record($_POST['rc_supplier_invoice_id']);
	$custom_total = get_custom_duty_total($_POST['rc_supplier_invoice_id']);
	label_row(_('Invoice Date'),sql2date($invoice_record['tran_date']));
	$curr_code = get_supp_currency($_POST['rc_supplier_id']);
	label_row(_('Invoice Amount'),price_format($invoice_record['total_amount']));
	label_row(_('Currency Code'),$curr_code);
	
	label_row(_('Inv Amount in Home Currency'),price_format($invoice_record['total_amount']*$invoice_record['rate']).' '.$home_curr);
	label_row(_('Custom Duty Total'),$custom_total);
	label_row(_('Bill No.'),$invoice_record['supp_reference']);
	

	hidden('rc_invoice_date',$invoice_record['tran_date']);
	hidden('rc_invoice_amount',price_format($invoice_record['total_amount']));
	hidden('rc_bill_no',$invoice_record['supp_reference']);
	hidden('rc_curr_code',$curr_code);
	hidden('rc_custom_total',$custom_total);
	
	
	}
	}
	else{
	hidden('reverse_charge','0');
	hidden('rc_supplier_id','');
	hidden('rc_supplier_invoice_id','');
	hidden('rc_invoice_date','00-00-0000');
	hidden('rc_invoice_amount','0');
	hidden('rc_bill_no','');
	hidden('rc_curr_code','');
	hidden('rc_custom_total',0);
	}	
		

	end_outer_table(); // outer table
	
	//display_error(json_encode($order));
	if (input_num('final_discount') != 0) {
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
	//display_error(input_num('final_discount_amount').'--'.$line_total);
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
	
	
}
//---------------------------------------------------------------------------------------------------

function display_po_items(&$order, $editable=true)
{

  if(list_updated('StkLocation'))
{
$total_available_qty = get_po_available_stock($_POST['stock_id'],$_POST['StkLocation'],$_POST['OrderDate']);

}
if(list_updated('stock_id'))
{
$total_available_qty = get_po_available_stock($_POST['stock_id'],$_POST['StkLocation'],$_POST['OrderDate']);

$last_purchase_price=get_last_purchase_price($_POST['stock_id']);

}
$total_available_qty = get_po_available_stock($_POST['stock_id'],$_POST['StkLocation'],$_POST['OrderDate']);
if($total_available_qty == '')
{
$total_available_qty = '0';
}else
{
$total_available_qty  = $total_available_qty;
}

    display_heading(_("Order Items"));

    div_start('items_table');
    start_table(TABLESTYLE, "width='80%'");

	$new = $order->order_no == 0;

   if($order->trans_type!=ST_PURCHENQ)
	{
   	$th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Received"),
   		_("Unit"), _("Required Delivery Date"), $order->tax_included ? _("Price after Tax") : _("Price before Tax"), _("Net Amount"), _("Discount %"), _("Discount Amount"), _("Line Total"), "");
	}else{
	$th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Received"), _("Unit"), "", "");
	}

	if ($order->trans_type != ST_PURCHORDER)
		array_remove($th, 5);
	//if ($order->trans_type == ST_PURCHENQ)
		//array_remove($th, 3);
	
	if ($new)
		array_remove($th, 3);

	if (count($order->line_items)) $th[] = '';

   	table_header($th);

	$id = find_submit('Edit');
	$total = 0;
	$k = 0;
   	foreach ($order->line_items as $line_no => $po_line)
   	{
    	$line_total = round(($po_line->quantity * $po_line->price)-($po_line->quantity * $po_line->price * ($po_line->discount_percent/100)),
		   user_price_dec());

    	$net_amount =	round($po_line->quantity * $po_line->price,  user_price_dec());
		
    	if (!$editable || ($id != $line_no))
		{
    		alt_table_row_color($k);
        	label_cell($po_line->stock_id);
    		label_cell($po_line->item_description);
        	if ($order->fixed_asset)
    		    label_cell(1);
         	else
            	qty_cell($po_line->quantity, false, get_qty_dec($po_line->stock_id));
			if (!$new)
	            qty_cell($po_line->qty_received, false, get_qty_dec($po_line->stock_id));
    		label_cell($po_line->units);
			if ($order->trans_type == ST_PURCHORDER)
        	    label_cell($po_line->req_del_date);
    		if($order->trans_type!=ST_PURCHENQ)
	       {	
    		amount_decimal_cell($po_line->price);
			
			amount_cell($net_amount);
            
			percent_cell($po_line->discount_percent);
			
			amount_decimal_cell($po_line->disc_amount);
			
            amount_cell($line_total);
			}

			if ($editable)
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
			po_item_controls($order, $k, $line_no);
		}
		$total += $line_total;
    }

	if ($id==-1 && $editable)
		po_item_controls($order, $k);

	$colspan = count($th)-2;
	if (count($order->line_items))
		$colspan--;

	$display_sub_total = price_format($total);
	if($order->trans_type!=ST_PURCHENQ)
	 {

	label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan align=right","align=right", 2);
	
	start_row();
	label_cell(_("Freight Charges"), "colspan=$colspan align=right");
	small_amount_cells(null, 'freight_cost', price_format(get_post('freight_cost',0)));
	end_row();
	
	start_row();
	label_cell(_("Additional Charges"), "colspan=$colspan align=right");
	small_amount_cells(null, 'additional_charges', price_format(get_post('additional_charges',0)));
	end_row();
	
	start_row();
	label_cell(_("Packing Charges"), "colspan=$colspan align=right");
	small_amount_cells(null, 'packing_charges', price_format(get_post('packing_charges',0)));
	end_row();
	
	start_row();
	label_cell(_("Other Charges"), "colspan=$colspan align=right");
	small_amount_cells(null, 'other_charges', price_format(get_post('other_charges',0)));
	end_row();

	//$taxes = $order->get_taxes(input_num('freight_cost'));
	
	$taxes = $order->get_taxes(input_num('freight_cost'),false,input_num('additional_charges'),input_num('packing_charges'),input_num('other_charges'));
	
	$tax_total = display_edit_tax_items($taxes, $colspan, $order->tax_included, 2);

	//Round Off
	if($order->trans_type==ST_SUPPINVOICE){
	start_row();
	label_cell(_("Round Off"), "colspan=$colspan align=right");
	small_amount_cells(null, 'ov_roundoff', price_format(get_post('ov_roundoff',0)));
	end_row();
	}

	$display_total = price_format(($total + input_num('freight_cost') + $tax_total + input_num('additional_charges') + input_num('packing_charges') + input_num('other_charges') + 
	input_num('ov_roundoff')));

	/*
	start_row();
	label_cells(_("Amount Total"), $display_total, "colspan=$colspan align='right'","align='right'");
	$order->trans_type == ST_SUPPINVOICE
	 ? submit_cells('update', _("Update"), "colspan=2 align='center'", _("Refresh"), true)
		: label_cell('', "colspan=2");
	end_row();
	*/
	
	start_row();
	label_cells(_("Amount Total"), $display_total, "colspan=$colspan align=right","align=right");
	submit_cells('update', _("Update"), "colspan=2 align='center'", _("Refresh"), true);
	end_row();
	}

	end_table(1);
	start_table(TABLESTYLE, "width='40%'");
	start_row();
	//display_error($total_available_qty);
	echo '<h5 align="center" style="color:green; font-size: 11px;">Selected Item Available Quantity: '.$total_available_qty.'</h5>';
	echo '<h5 align="center" style="color:green; font-size: 11px;">Selected Item Last Purchase Price: '.$last_purchase_price.'</h5>';
	end_row();
	end_table();
	
	div_end();
}

//---------------------------------------------------------------------------------------------------

function display_po_summary(&$po, $is_self=false, $editable=false,$trans_type=false)
{
    start_table(TABLESTYLE, "width='90%'");

    start_row();
    label_cells(_("Reference"), $po->reference, "class='tableheader2'");

    label_cells(_("Supplier"), $po->supplier_name, "class='tableheader2'");

    if (!is_company_currency($po->curr_code))
    	label_cells(_("Order Currency"), $po->curr_code, "class='tableheader2'");

    if (!$is_self)
    {
    	label_cells(_("Purchase Order"), get_trans_view_str(ST_PURCHORDER, $po->order_no),
    		"class='tableheader2'");
    }
	end_row();
	start_row();
    label_cells(_("Date"), $po->orig_order_date, "class='tableheader2'");

    if ($editable)
    {
        if (!isset($_POST['Location']))
        	$_POST['Location'] = $po->Location;
        label_cell(_("Deliver Into Location"), "class='tableheader2'");
        locations_list_cells(null, 'Location', $_POST['Location']);
    }
    else
    {
    	label_cells(_("Deliver Into Location"), get_location_name($po->Location),
    		"class='tableheader2'");
    }

    if ($po->supp_ref != "")
    	label_cells(_("Supplier's Reference"), $po->supp_ref, "class='tableheader2'");
    end_row();
	
	
	//include_once($path_to_root . "/includes/db/inventory_db.inc");
	$preared_user = get_transaction_prepared_by($trans_type, $po->order_no);
    label_row(_("Prepared By"), $preared_user, "class='tableheader2'", "colspan=3");  
	

    if (!$editable)
    	label_row(_("Delivery Address"), $po->delivery_address, "class='tableheader2'",
    		"colspan=9");

	if (!$editable && ($po->prep_amount > 0))
	{
		start_row();
		label_cells(_("Required Pre-Payment"), price_format($po->prep_amount), "class='tableheader2'");
		label_cells(_("Pre-Payments Allocated"), price_format($po->alloc), "class='tableheader2'");
		end_row();
	}
	
	if($trans_type=='18'){
	start_row();
	$status="";
	if($po->po_auth_req == 0||$po->reference=="auto")
	{
	$status="Approved ";
	label_cells(_("Authorization"), $status, "class='tableheader2'");
	}
	else if($po->po_auth_req == 1 && $po->reference!="auto" )
	{
	$status="Pending";
	label_cells(_("Authorization"), $status, "class='tableheader2'");

	}
	else if($po->po_auth_req == 2)
	{
	$status="Approved";
	start_row();
	label_cells(_("Authorization"), $status, "class='tableheader2'");

	label_cells(_("Approved Remarks"), $po->auth_rej_remarks, "class='tableheader2'");
	end_row();
	start_row();
	label_cells(_("Approved Date"), $po->po_auth_req_date, "class='tableheader2'");
	label_cells(_("Authorized By"), $po->authorised_by, "class='tableheader2'");
    end_row();

	}
	else if($po->po_auth_req == 3)
	{
	$status="Rejected";
	}
    if($po->po_auth_req == 3)
	{
		         label_cells(_("Authorization"), $status, "class='tableheader2'");
				label_cells(_("Rejected Date"), $po->authrise_reject_date, "class='tableheader2'");
                label_cells(_("Rejected Remarks"), $po->auth_rej_remarks, "class='tableheader2'");

	}
	end_row();
	
	//start_row();
	//label_row(_("Generated By"), $po->generated_by, "class='tableheader2'");
	//end_row();
	}
	

    if ($po->Comments != "")
    	label_row(_("Order Comments"), $po->Comments, "class='tableheader2'",
    		"colspan=9");
    end_table(1);
}

//--------------------------------------------------------------------------------

function po_item_controls(&$order, &$rowcounter, $line_no=-1)
{
   global $Ajax, $SysPrefs;

	alt_table_row_color($rowcounter);
	$new = $order->order_no == 0;

	$dec2 = 0;
	$id = find_submit('Edit');
	if (($id != -1) && $line_no == $id)
	{

		$_POST['stock_id'] = $order->line_items[$id]->stock_id;
		$dec = get_qty_dec($_POST['stock_id']);
		$_POST['qty'] = qty_format($order->line_items[$id]->quantity, $_POST['stock_id'], $dec);
		$_POST['price'] = price_decimal_format($order->line_items[$id]->price, $dec2);
		if ($order->trans_type == ST_PURCHORDER)
			$_POST['req_del_date'] = $order->line_items[$id]->req_del_date;

		$_POST['units'] = $order->line_items[$id]->units;
		$_POST['item_description'] = $order->line_items[$id]->item_description;
		
		$_POST['discount_percent'] = ($order->line_items[$id]->discount_percent);
		$_POST['disc_amount'] = $order->line_items[$id]->disc_amount;

		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['stock_id']);

		if ($order->line_items[$id]->descr_editable)
			text_cells(null,'item_description', null, 45, 150);
		else {
			hidden('item_description', $_POST['item_description']);
			label_cell($order->line_items[$id]->item_description); 
		}

	    $Ajax->activate('items_table');
		$qty_rcvd = $order->line_items[$id]->qty_received;
	}
	else
	{

		// Manufactured item can be purchased
		if ($order->fixed_asset)
			stock_purchasable_fa_list_cells(null, 'stock_id', null, false, true, false, true, $order->line_items);
		else
			stock_items_list_cells(null, 'stock_id', null, false, true, false, true, array('editable' => 30, 'where'=>array("NOT no_purchase")));

		if (list_updated('stock_id')) {
			    $Ajax->activate('price');
			    $Ajax->activate('units');
			    $Ajax->activate('qty');
			    $Ajax->activate('req_del_date');
			    $Ajax->activate('line_total');
		}

		$dec = 0; $_POST['units'] = '';
    	$item_info = get_item_edit_info($_POST['stock_id']);
    	if ($item_info) {
			$_POST['units'] = $item_info["units"];
	   		$dec = $item_info["decimals"];
	   	}
		
   		$_POST['qty'] =	number_format2(get_purchase_conversion_factor ($order->supplier_id, 
		$_POST['stock_id']), $dec);
		
		$_POST['price'] = price_decimal_format(get_purchase_price ($order->supplier_id, $_POST['stock_id']), $dec2);
		if ($order->trans_type == ST_PURCHORDER)
			$_POST['req_del_date'] = add_days(Today(), $SysPrefs->default_receival_required_by());
		$qty_rcvd = '';
	}

	if ($order->fixed_asset) {
		label_cell(1, '', 'qty');
		hidden('qty', 1);
	} else
		qty_cells(null, 'qty', null, null, null, $dec);

	if (!$new)
		qty_cell($qty_rcvd, false, $dec);

	label_cell($_POST['units'], '', 'units');
	if ($order->trans_type != ST_PURCHENQ){   
	if ($order->trans_type == ST_PURCHORDER)
		date_cells(null, 'req_del_date', '', null, 0, 0, 0);
	if ($qty_rcvd > 0)
	{
   		amount_decimal_cell($_POST['price']);
   		hidden('price', $_POST['price']);
   	}	
   	else	
		amount_cells(null, 'price', null, null, null, $dec2);
		
	$net_amount = input_num('qty') * input_num('price');
	amount_cell($net_amount, false, '','net_amount');	

    small_amount_cells(null, 'discount_percent', null, null, null, $dec2,false);

    small_amount_cells(null, 'disc_amount', null, null, null, $dec2,false);	

	$line_total = input_num('qty') * input_num('price') * (1 - input_num('discount_percent') / 100);
	amount_cell($line_total, false, '','line_total');
	}

	if ($id!=-1)
	{
		button_cell('UpdateLine', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelUpdate', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
		hidden('line_no', $line_no);
		set_focus('qty');
	}
	else
	{
		submit_cells('EnterLine', _("Add Item"), "colspan=2 align='center'",
		    _('Add new item to document'), true);
	}

	end_row();
}




function display_po_reverse_items(&$order, $editable=true)
{

if(list_updated('StkLocation'))
{
$total_available_qty = get_po_available_stock($_POST['stock_id'],$_POST['StkLocation'],$_POST['OrderDate']);

}
if(list_updated('stock_id'))
{
$total_available_qty = get_po_available_stock($_POST['stock_id'],$_POST['StkLocation'],$_POST['OrderDate']);
}
$total_available_qty = get_po_available_stock($_POST['stock_id'],$_POST['StkLocation'],$_POST['OrderDate']);
if($total_available_qty == '')
{
$total_available_qty = '0';
}else
{
$total_available_qty  = $total_available_qty;
}

    display_heading(_("Order Items"));

    div_start('items_table');
    start_table(TABLESTYLE, "width='80%'");

	$new = $order->order_no == 0;
	
	
    if($order->trans_type!=ST_PURCHENQ)
	{
   	$th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Received"),
   		_("Unit"), _("Required Delivery Date"), $order->tax_included ? _("Price after Tax") : _("Price before Tax"), _("Net Amount"), _("Discount %"), _("Discount Amount"),
		_('Dimension'),_("Supplier"),_("Purchase Reference"), _("Line Total"), "");
	}else{
	$th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Received"), _("Unit"), "", "");
	}

	if ($order->trans_type != ST_PURCHORDER)
		array_remove($th, 5);
	if ($new)
		array_remove($th, 3);

	if (count($order->line_items)) $th[] = '';

   	table_header($th);

	$id = find_submit('Edit');
	$total = 0;
	$k = 0;
   	foreach ($order->line_items as $line_no => $po_line)
   	{
    	$line_total = round(($po_line->quantity * $po_line->price)-($po_line->quantity * $po_line->price * ($po_line->discount_percent/100)),
		   user_price_dec());

    	$net_amount =	round($po_line->quantity * $po_line->price,  user_price_dec());
		
		
    	if (!$editable || ($id != $line_no))
		{
    		alt_table_row_color($k);
        	label_cell($po_line->stock_id);
    		label_cell($po_line->item_description);
        	if ($order->fixed_asset)
    		    label_cell(1);
         	else
            	qty_cell($po_line->quantity, false, get_qty_dec($po_line->stock_id));
			if (!$new)
	            qty_cell($po_line->qty_received, false, get_qty_dec($po_line->stock_id));
    		label_cell($po_line->units);
			if ($order->trans_type == ST_PURCHORDER)
        	    label_cell($po_line->req_del_date);
				
			if($order->trans_type!=ST_PURCHENQ)
	       {	
    		amount_decimal_cell($po_line->price);
			
			amount_cell($net_amount);
            
			percent_cell($po_line->discount_percent);
			
			amount_decimal_cell($po_line->disc_amount);
			
			$line_dimension_name = get_dimension_name($po_line->line_dimension);
			label_cell($line_dimension_name);
			$supp_name = get_supplier_name($po_line->rc_supplier_id_rev);
			label_cell($supp_name);
			$entered_ref = get_invoice_record($po_line->rc_supplier_invoice_id_rev);
			label_cell($entered_ref['reference']);
			
            amount_cell($line_total);
			}

			if ($editable)
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
			po_reverse_item_controls($order, $k, $line_no);
		}
		$total += $line_total;
    }

	if ($id==-1 && $editable)
		po_reverse_item_controls($order, $k);

	$colspan = count($th)-2;
	if (count($order->line_items))
		$colspan--;

	$display_sub_total = price_format($total);
    if($order->trans_type!=ST_PURCHENQ)
	 {
	label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan align=right","align=right", 2);
	
	start_row();
	label_cell(_("Freight Charges"), "colspan=$colspan align=right");
	small_amount_cells(null, 'freight_cost', price_format(get_post('freight_cost',0)));
	end_row();
	
	start_row();
	label_cell(_("Additional Charges"), "colspan=$colspan align=right");
	small_amount_cells(null, 'additional_charges', price_format(get_post('additional_charges',0)));
	end_row();
	
	start_row();
	label_cell(_("Packing Charges"), "colspan=$colspan align=right");
	small_amount_cells(null, 'packing_charges', price_format(get_post('packing_charges',0)));
	end_row();
	
	start_row();
	label_cell(_("Other Charges"), "colspan=$colspan align=right");
	small_amount_cells(null, 'other_charges', price_format(get_post('other_charges',0)));
	end_row();

	//$taxes = $order->get_taxes(input_num('freight_cost'));
	
	$taxes = $order->get_taxes(input_num('freight_cost'),false,input_num('additional_charges'),input_num('packing_charges'),input_num('other_charges'));
	
	$tax_total = display_edit_tax_items($taxes, $colspan, $order->tax_included, 2);
	
	
	
	//Round Off
	if($order->trans_type==ST_SUPPINVOICE){
	start_row();
	label_cell(_("Round Off"), "colspan=$colspan align=right");
	small_amount_cells(null, 'ov_roundoff', price_format(get_post('ov_roundoff',0)));
	end_row();
	}

	$display_total = price_format(($total + input_num('freight_cost') + $tax_total + input_num('additional_charges') + input_num('packing_charges') + input_num('other_charges') + 
	input_num('ov_roundoff')));
	
	

    /*
	start_row();
	label_cells(_("Amount Total"), $display_total, "colspan=$colspan align='right'","align='right'");
	$order->trans_type == ST_SUPPINVOICE
	 ? submit_cells('update', _("Update"), "colspan=2 align='center'", _("Refresh"), true)
		: label_cell('', "colspan=2");
	end_row();
	*/
	
	start_row();
	label_cells(_("Amount Total"), $display_total, "colspan=$colspan align=right","align=right");
	submit_cells('update', _("Update"), "colspan=2 align='center'", _("Refresh"), true);
	end_row();
	}

	end_table(1);
	
	
	div_end();
}


function po_reverse_item_controls(&$order, &$rowcounter, $line_no=-1)
{
   global $Ajax, $SysPrefs;

	alt_table_row_color($rowcounter);
	$new = $order->order_no == 0;

	$dec2 = 0;
	$id = find_submit('Edit');
	if (($id != -1) && $line_no == $id)
	{

		$_POST['stock_id'] = $order->line_items[$id]->stock_id;
		$dec = get_qty_dec($_POST['stock_id']);
		$_POST['qty'] = qty_format($order->line_items[$id]->quantity, $_POST['stock_id'], $dec);
		$_POST['price'] = price_decimal_format($order->line_items[$id]->price, $dec2);
		if ($order->trans_type == ST_PURCHORDER)
			$_POST['req_del_date'] = $order->line_items[$id]->req_del_date;

		$_POST['units'] = $order->line_items[$id]->units;
		$_POST['item_description'] = $order->line_items[$id]->item_description;
		
		$_POST['discount_percent'] = ($order->line_items[$id]->discount_percent);
		$_POST['disc_amount'] = $order->line_items[$id]->disc_amount;
		
		$_POST['line_dimension'] = $order->line_items[$id]->line_dimension;
		$_POST['rc_supplier_id_rev'] = $order->line_items[$id]->rc_supplier_id_rev;
		$_POST['rc_supplier_invoice_id_rev'] = $order->line_items[$id]->rc_supplier_invoice_id_rev;

		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['stock_id']);

		if ($order->line_items[$id]->descr_editable)
			text_cells(null,'item_description', null, 45, 150);
		else {
			hidden('item_description', $_POST['item_description']);
			label_cell($order->line_items[$id]->item_description); 
		}

	    $Ajax->activate('items_table');
		$qty_rcvd = $order->line_items[$id]->qty_received;
	}
	else
	{

		// Manufactured item can be purchased
		if ($order->fixed_asset)
			stock_purchasable_fa_list_cells(null, 'stock_id', null, false, true, false, true, $order->line_items);
		else
			stock_items_list_cells(null, 'stock_id', null, false, true, false, true, array('editable' => 30, 'where'=>array("NOT no_purchase")));

		if (list_updated('stock_id')) {
			    $Ajax->activate('price');
			    $Ajax->activate('units');
			    $Ajax->activate('qty');
			    $Ajax->activate('req_del_date');
			    $Ajax->activate('line_total');
		}

		$dec = 0; $_POST['units'] = '';
    	$item_info = get_item_edit_info($_POST['stock_id']);
    	if ($item_info) {
			$_POST['units'] = $item_info["units"];
	   		$dec = $item_info["decimals"];
	   	}
		
		if (list_updated('rc_supplier_id_rev') ) {
	
			$_POST['qty'] = $_POST['qty'];
		}
		else {
   		$_POST['qty'] =	number_format2(get_purchase_conversion_factor ($order->supplier_id, $_POST['stock_id']), $dec);
		$_POST['price'] = price_decimal_format(get_purchase_price ($order->supplier_id, $_POST['stock_id']), $dec2);
		}
		
		if ($order->trans_type == ST_PURCHORDER)
			$_POST['req_del_date'] = add_days(Today(), $SysPrefs->default_receival_required_by());
		$qty_rcvd = '';
	}

	if ($order->fixed_asset) {
		label_cell(1, '', 'qty');
		hidden('qty', 1);
	} else
		qty_cells(null, 'qty', null, null, null, $dec);

	if (!$new)
		qty_cell($qty_rcvd, false, $dec);

	label_cell($_POST['units'], '', 'units');
	
	if ($order->trans_type != ST_PURCHENQ){   
	if ($order->trans_type == ST_PURCHORDER)
		date_cells(null, 'req_del_date', '', null, 0, 0, 0);
	if ($qty_rcvd > 0)
	{
   		amount_decimal_cell($_POST['price']);
   		hidden('price', $_POST['price']);
   	}	
   	else	
		amount_cells(null, 'price', null, null, null, $dec2);
		
	$net_amount = input_num('qty') * input_num('price');
	amount_cell($net_amount, false, '','net_amount');	

    small_amount_cells(null, 'discount_percent', null, null, null, $dec2,true);

    small_amount_cells(null, 'disc_amount', null, null, null, $dec2,true);	
	
	if($order->trans_type == ST_SUPPINVOICE){
	if (get_company_pref('use_dimension'))
		dimensions_list_cells(_(''), 'line_dimension', null, true, _('Default'), false, 1);
		supplier_list_cells(null, 'rc_supplier_id_rev', null, true, true, false, true);
	
		supplier_invoice_list_cells(null,  'rc_supplier_invoice_id_rev', null, true, 
		false, false,  false,$_POST['rc_supplier_id_rev']);
		}

	$line_total = input_num('qty') * input_num('price') * (1 - input_num('discount_percent') / 100);
	amount_cell($line_total, false, '','line_total');
	}

	if ($id!=-1)
	{
		button_cell('UpdateLine', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelUpdate', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
		hidden('line_no', $line_no);
		set_focus('qty');
	}
	else
	{
		submit_cells('EnterLine', _("Add Item"), "colspan=2 align='center'",
		    _('Add new item to document'), true);
	}

	end_row();
}

//---------------------------------------------------------------------------------------------------

function display_po_footer(&$trans)
{
	global $Ajax;

start_table(TABLESTYLE2);

if ($trans->trans_type == ST_SUPPINVOICE) {
	cash_accounts_list_row(_("Payment:"), 'cash_account', null, false, _('Delayed'));
} elseif ($trans->trans_type == ST_PURCHORDER)
	amount_row(_("Pre-Payment Required:"), 'prep_amount');

textarea_row(_("Memo:"), 'Comments', null, 70, 10);

end_table();
}
//---------------------------------------------------------------------------------------------------

//qoh qty
function get_po_available_stock($stock_id,$location,$order_date)
{

$ord_date = date2sql($order_date);
$sql = "SELECT sum(qty) as quantity FROM ".TB_PREF."stock_moves WHERE stock_id=".db_escape($stock_id)." AND loc_code=".db_escape($location)." AND tran_date <= ".db_escape($ord_date)."";
$res = db_query($sql);
$result = db_fetch_row($res);
$demand_qty=get_demand_qty($stock_id,$location);
return $result['0']-$demand_qty; 

}

//ravi

function get_last_purchase_price($stock_id)
{
	$sql = "SELECT unit_price*((100-discount_percent)*0.01) FROM ".TB_PREF."supp_invoice_items WHERE stock_id=".db_escape($stock_id)." and supp_trans_type=20 order by id desc";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];  
}

//---------------------------------------------------------------------------------------------
function get_dimension_name($dimension_id){
$sql = "SELECT CONCAT(reference,'  ',name) as ref FROM ".TB_PREF."dimensions WHERE id=".db_escape($dimension_id)."";
$res = db_query($sql);
$row = db_fetch_row($res);

return $row['0'];

}

function get_invoice_record($invoice_id){
$sql = "SELECT tran_date,sum(ov_amount+ov_gst+ov_discount+freight_cost+additional_charges+packing_charges+other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff) as total_amount,supp_reference,rate,reference FROM ".TB_PREF."supp_trans WHERE trans_no = ".db_escape($invoice_id)." AND type='20'";
$res = db_query($sql);
$result = db_fetch($res);
return $result;
}


function get_custom_duty_total($invoice_id){
$sql = "SELECT  sum(unit_price) as custom_amount FROM ".TB_PREF."supp_invoice_items WHERE supp_trans_no=".db_escape($invoice_id)." AND supp_trans_type='20' AND gl_code ='1706' GROUP BY supp_trans_no";
$res = db_query($sql);
$result = db_fetch_row($res);
return $result['0'];
}

function get_supp_currency($supplier_id){
$sql = "SELECT curr_code FROM ".TB_PREF."suppliers WHERE supplier_id = ".db_escape($supplier_id)."";
$res = db_query($sql);
$result = db_fetch_row($res);
return $result['0'];
}
//---------------------------------------------------------------------------------------------------



