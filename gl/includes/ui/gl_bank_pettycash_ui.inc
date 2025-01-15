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

function display_bank_pettycash_header(&$order)
{
	global $Ajax;
	$payment = $order->trans_type == ST_BANKPAYMENT;

	$customer_error = false;
	div_start('pmt_header');
	
	// Newly added
    div_start('payments_mode_div');

	start_outer_table(TABLESTYLE2, "width='90%'"); // outer table

	table_section(1);
	
    date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);

	
	

	table_section(2, "60%");

	
	
	if (isset($_POST['_PayType_update'])) {
		//$_POST['person_id'] = '';
		$Ajax->activate('pmt_header');
		$Ajax->activate('code_id');
		$Ajax->activate('pagehelp');
		$Ajax->activate('editors');
		$Ajax->activate('footer');
	}
    
   
	//text_row(_("Our Reference No.<b style='color:red;'>*</b>"),'our_ref_no',null,20,50);

	//table_section(3, "33%");

	if (!$order->order_id && !get_post('bank_account'))
	{
		if ($_POST['PayType'] == PT_SUPPLIER)	
			$_POST['bank_account'] = get_default_supplier_bank_account($_POST['person_id']);
		else
			unset($_POST['bank_account']);
	}		
    
    cash_accounts_list_row( $payment ? _("From:") : _("Into:"), 'bank_account', null, true,false); 
    if ($payment)
		bank_balance_row($_POST['bank_account']);

	$bank_currency = get_bank_account_currency($_POST['bank_account']);

	exchange_rate_display(get_company_currency(), $bank_currency, $_POST['date_']);
	
	
	ref_row(_("Reference:"), 'ref', '', $order->reference, false, $order->trans_type,array('date'=>get_post('date_'),'bank_act'=>$_POST['bank_account']),1);
	
	end_outer_table(1); // outer table

	div_end();
	if ($customer_error)
	{
		end_form();
		end_page();
		exit;
	}
}
//---------------------------------------------------------------------------------

function display_pettycash_gl_items($title, &$order)
{
	global $path_to_root;

	$dim = get_company_pref('use_dimension');
	
	$sub_type = is_subledger_account(get_post('code_id'));
	$has_subaccounts = $order->has_sub_accounts();
	
	$colspan = ($dim == 2 ? 4 : ($dim == 1 ? 3 : 2));
	display_heading($title);

    div_start('items_table');
	start_table(TABLESTYLE, "width='95%'");

	if ($dim == 2)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension")." 1",
			_("Dimension")." 2", _("Amount"), _("Memo"), "");
	elseif ($dim == 1)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension"),
			_("Amount"), _("Memo"), "");
	else
		$th = array(_("Account Code"), _("Account Description"),
			_("Amount"), _("Memo"), "");

	if (count($order->gl_items)) $th[] = '';
	if ($sub_type || $has_subaccounts)
		array_insert($th, 2, _("Counterparty"));
		

	table_header($th);
	$k = 0;  //row colour counter
	
	$id = find_submit('Edit');

	
	foreach ($order->gl_items as $line => $item)
	{
		if ($id != $line)
		{
    		alt_table_row_color($k);

			//label_cell($item->code_id);
			//label_cell($item->description);
			
			$acc = $item->code_id . ($item->person_id ? sprintf(' %05d', $item->person_id) : '');
			label_cells($acc, $item->description);
			
			
			if ($sub_type || $has_subaccounts)
			label_cell($item->person_name);
			
			
    		if ($dim >= 1)
				label_cell(get_dimension_string($item->dimension_id, true));
    		if ($dim > 1)
				label_cell(get_dimension_string($item->dimension2_id, true));
				
			amount_cell($item->amount);
			label_cell($item->reference);

			edit_button_cell("Edit$line", _("Edit"),
				_('Edit document line'));
			delete_button_cell("Delete$line", _("Delete"),
				_('Remove line from document'));
    		end_row();
		}
		else
		{
			gl_edit_item_controls($order, $dim, $item->person_id, $line);
		}
	}

	if ($id == -1)
		gl_edit_item_controls($order, $dim, $sub_type);
		
		
	if ($order->count_gl_items()) 
	{
		$colspan = ($dim == 2 ? "4" : ($dim == 1 ? "3" : "2")) + ($sub_type || $has_subaccounts ? 1 : 0);
		start_row();
		label_row(_("Total"), number_format2(abs($order->gl_items_total()), user_price_dec()),"colspan=" . $colspan . " align=right", "align=right",3);
		end_row();
	}	

	

    end_table();
	div_end();
}

//---------------------------------------------------------------------------------

function gl_edit_item_controls(&$order, $dim, $sub_accounts, $Index=null)
{
	global $Ajax;
	$payment = $order->trans_type == ST_BANKPAYMENT;

	start_row();
	
	
	$id = find_submit('Edit');
	
	if ($Index != -1 && $Index == $id)
	{
		$item = $order->gl_items[$Index];
		$_POST['code_id'] = $item->code_id;
		$_POST['dimension_id'] = $item->dimension_id;
		$_POST['dimension2_id'] = $item->dimension2_id;
		$_POST['amount'] = price_format(abs($item->amount));
		$_POST['description'] = $item->description;
		$_POST['LineMemo'] = $item->reference;
		
		$_POST['person_id'] = $item->person_id;

		hidden('Index', $id);
	    $Ajax->activate('items_table');
	}
	else
	{
		if(isset($_POST['_code_id_update'])) {
			    $Ajax->activate('code_id');
		}
		
	}
	
	if(isset($_POST['code_id'])) {
			  $Ajax->activate('items_table');
	}
	
	//echo gl_all_accounts_list('code_id', null, true, true,false,true);
	
		echo gl_all_accounts_list('code_id', null, true, true, _("Select a Account Name"),true);
	
	if ($sub_accounts)
		{
			// for home currency postings show all customers
			$sel_cur = get_post('currency', $order->currency);
			if(get_post('code_id')=='303')
			subledger_list_cells(null, 'person_id', get_post('code_id'), null);
			else
			label_cell('');
			
		}
		elseif ($order->has_sub_accounts())
		label_cell('');
			
			
	if (get_post('_code_id_update'))
	{
	    $Ajax->activate('code_id');
		set_focus($sub_accounts ? 'person_id' : ($dim < 1 ? 'amount' : 'dimension_id'));
	}		
	
	
	
	
	
	if ($dim >= 1)
		dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
	else
		hidden('dimension_id', 0);
	if ($dim > 1)
		dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
	else
		hidden('dimension2_id', 0);

	amount_cells(null, 'amount');
	text_cells_ex(null, 'LineMemo', 35, 255);

	if ($id != -1)
	{
		button_cell('UpdateItem', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
 		set_focus('amount');
	}
	else
	{
		submit_cells('AddItem', _("Add Item"), "colspan=2",
		    _('Add new item to document'), true);
	}

	end_row();
}


//---------------------------------------------------------------------------------

function gl_pettycash_options_controls($order)
{
	div_start('footer');
	echo "<br><table align='center'>";

	$type = get_post('PayType');
	$bank_curr = get_bank_account_currency(get_post('bank_account'));
	$person_curr = $type == PT_CUSTOMER ? get_customer_currency(get_post('person_id')) 
		: ($type == PT_SUPPLIER ? get_supplier_currency(get_post('person_id')) : $bank_curr);

	if ($person_curr != $bank_curr)
	{
		$_POST['settled_amount'] =
			price_format(abs($order->gl_items_total() / get_exchange_rate_from_to($bank_curr, $person_curr, get_post('date_'))));
		amount_row($type == PT_CUSTOMER ? _("Settled AR Amount:") :  _("Settled AP Amount:"),
			 'settled_amount', null, null, $person_curr, user_price_dec());
	}
	textarea_row(_("Memo"), 'memo_', null, 50, 3);

  	echo "</table>";
  	div_end();
}





