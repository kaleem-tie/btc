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
//--------------------------------------------------------------------------------------------------

function copy_from_trans(&$supp_trans)
{
	$_POST['Comments'] = $supp_trans->Comments;
	$_POST['tran_date'] = $supp_trans->tran_date;
	$_POST['due_date'] = $supp_trans->due_date;
	$_POST['supp_reference'] = $supp_trans->supp_reference;
	$_POST['reference'] = $supp_trans->reference;
	$_POST['supplier_id'] = $supp_trans->supplier_id;
    $_POST['dimension'] = $supp_trans->dimension;
    $_POST['dimension2'] = $supp_trans->dimension2;
	
	$_POST['freight_cost'] = $supp_trans->freight_cost;
	$_POST['additional_charges'] = $supp_trans->additional_charges;
	$_POST['packing_charges'] = $supp_trans->packing_charges;
	$_POST['other_charges'] = $supp_trans->other_charges;
	$_POST['ov_roundoff'] = price_format($supp_trans->ov_roundoff); 
	
	$_POST['bill_date'] = $supp_trans->bill_date;
	
	$_POST['declaration_no']   = $supp_trans->declaration_no;
	$_POST['cif_value']        = price_format($supp_trans->cif_value);
	$_POST['vat_import_value'] = price_format($supp_trans->vat_import_value);
	$_POST['custom_duties']    = price_format($supp_trans->custom_duties);
	$_POST['container_number'] = $supp_trans->container_number;
	$_POST['bl_no']            = $supp_trans->bl_no;

	$_POST['_ex_rate'] = $supp_trans->ex_rate;
	if (isset($supp_trans->tax_overrides))
	    foreach($supp_trans->tax_overrides as $id => $value)
		    $_POST['mantax'][$id] = price_format($value);
			
	$_POST['lc_commission_amount'] = $supp_trans->lc_commission_amount;
	$_POST['lc_comm_memo']         = $supp_trans->lc_comm_memo;
	$_POST['transport_amount'] = $supp_trans->transport_amount;
	$_POST['transport_memo']    = $supp_trans->transport_memo;
	$_POST['freight_inward_amount'] = $supp_trans->freight_inward_amount;
	$_POST['freight_inward_memo']   = $supp_trans->freight_inward_memo;
	$_POST['demurrage_amount'] = $supp_trans->demurrage_amount;
	$_POST['demurrage_memo']    = $supp_trans->demurrage_memo;
	$_POST['insurance_amount'] = $supp_trans->insurance_amount;
	$_POST['insurance_memo']   = $supp_trans->insurance_memo;
	$_POST['unloading_amount'] = $supp_trans->unloading_amount;
	$_POST['unloading_memo']    = $supp_trans->unloading_memo;
	$_POST['port_ser_amount'] = $supp_trans->port_ser_amount;
	$_POST['port_ser_memo']   = $supp_trans->port_ser_memo;
	$_POST['cf_amount'] = $supp_trans->cf_amount;
	$_POST['cf_memo']    = $supp_trans->cf_memo;
	$_POST['cust_duty_amount'] = $supp_trans->cust_duty_amount;
	$_POST['cust_duty_memo']   = $supp_trans->cust_duty_memo;
	$_POST['others_amount'] = $supp_trans->others_amount;
	$_POST['others_memo']    = $supp_trans->others_memo;	
	$_POST['purch_inco_terms']    = $supp_trans->purch_inco_terms;
	$_POST['purch_type']    = $supp_trans->purch_type;
    
			
}

//--------------------------------------------------------------------------------------------------

function copy_to_trans(&$supp_trans, $mode=0)
{
	$supp_trans->Comments = $_POST['Comments'];
	$supp_trans->tran_date = $_POST['tran_date'];
	$supp_trans->due_date = $_POST['due_date'];
	$supp_trans->supp_reference = $_POST['supp_reference'];
	$supp_trans->reference = $_POST['reference'];
   	$supp_trans->dimension = @$_POST['dimension'];
	$supp_trans->dimension2 =  @$_POST['dimension2'];
	
	$supp_trans->freight_cost = input_num('freight_cost');
	$supp_trans->additional_charges = input_num('additional_charges');
	$supp_trans->packing_charges = input_num('packing_charges');
	$supp_trans->other_charges = input_num('other_charges');
	$supp_trans->ov_roundoff = input_num('ov_roundoff');

	$supp_trans->ex_rate = input_num('_ex_rate', null);
	
	$supp_trans->bill_date = $_POST['bill_date'];
	$supp_trans->Location = $_POST['Location'];
	
	$supp_trans->declaration_no   = $_POST['declaration_no'];
	$supp_trans->cif_value        = input_num('cif_value');
	$supp_trans->vat_import_value = input_num('vat_import_value');
	$supp_trans->custom_duties    = input_num('custom_duties');
	$supp_trans->container_number = $_POST['container_number'];
	$supp_trans->bl_no            = $_POST['bl_no'];
	
	
	$supp_trans->lc_commission_amount  = input_num('lc_commission_amount');
	$supp_trans->transport_amount      = input_num('transport_amount');
	$supp_trans->freight_inward_amount = input_num('freight_inward_amount');
	$supp_trans->demurrage_amount      = input_num('demurrage_amount');
	$supp_trans->insurance_amount      = input_num('insurance_amount');
	$supp_trans->unloading_amount      = input_num('unloading_amount');
	$supp_trans->port_ser_amount       = input_num('port_ser_amount');
	$supp_trans->cf_amount             = input_num('cf_amount');
	$supp_trans->cust_duty_amount      = input_num('cust_duty_amount');
	$supp_trans->others_amount         = input_num('others_amount');
	
	$supp_trans->lc_comm_memo        = $_POST['lc_comm_memo'];
	$supp_trans->transport_memo      = $_POST['transport_memo'];
	$supp_trans->freight_inward_memo = $_POST['freight_inward_memo'];
	$supp_trans->demurrage_memo = $_POST['demurrage_memo'];
	$supp_trans->insurance_memo = $_POST['insurance_memo'];
	$supp_trans->unloading_memo = $_POST['unloading_memo'];
	$supp_trans->port_ser_memo  = $_POST['port_ser_memo'];
	$supp_trans->cf_memo        = $_POST['cf_memo'];
	$supp_trans->cust_duty_memo = $_POST['cust_duty_memo'];
	$supp_trans->others_memo    = $_POST['others_memo'];
	$supp_trans->purch_inco_terms    = $_POST['purch_inco_terms'];
	$supp_trans->purch_type    = $_POST['purch_type'];
	
	
	$supp_trans->ov_amount = $supp_trans->ov_discount = 0; /* for starters */
    if (isset($_POST['mantax'])) {
		foreach($_POST['mantax'] as $id => $tax) {
	    	$supp_trans->tax_overrides[$id] = user_numeric($_POST['mantax'][$id]);
	    	}
	}
	else
		unset($supp_trans->tax_overrides);
	
	if (isset($supp_trans->grn_items) && count($supp_trans->grn_items) > 0)
	{
		foreach ( $supp_trans->grn_items as $grn)
		{
			 $supp_trans->ov_amount += round2(($grn->this_quantity_inv * $grn->chg_price*(1-$grn->discount_percent/100)),
			  user_price_dec());
		}
	}
	if (isset($supp_trans->gl_codes) && count($supp_trans->gl_codes) > 0)
	{
		foreach ( $supp_trans->gl_codes as $gl_line)
		{
			if ((!is_tax_account($gl_line->gl_code) || $supp_trans->tax_included) && $mode==0)
				$supp_trans->ov_amount += $gl_line->amount;
		}
	}
}

//--------------------------------------------------------------------------------------------------

function invoice_header(&$supp_trans)
{
	global $Ajax, $Refs;
	
	// if vars have been lost, recopy
	if (!isset($_POST['tran_date']))
		copy_from_trans($supp_trans);

	start_outer_table(TABLESTYLE2, "width='95%'");

	table_section(1);

	if ($supp_trans->trans_type == ST_SUPPCREDIT && $supp_trans->src_docs)
	{
		$_POST['supplier_id'] = $supp_trans->supplier_id;
		$supp = $supp_trans->supplier_name." - ".$supp_trans->currency;
		label_row(_("Supplier:"), $supp.hidden('supplier_id', $_POST['supplier_id'], false));
	}
	else
	{
    	if (!isset($_POST['supplier_id']) && (get_global_supplier() != ALL_TEXT))
    		$_POST['supplier_id'] = get_global_supplier();

		if (!$supp_trans->trans_no)
			supplier_list_row(_("Supplier:"), 'supplier_id', null, _("Select a Supplier"), true);
		else
			label_row(_("Supplier:"), $supp_trans->supplier_name
			.($supp_trans->currency ? ' - '.$supp_trans->currency  : '')
			.hidden('supplier_id', $_POST['supplier_id'], false));
	}
	if ($supp_trans->supplier_id != $_POST['supplier_id'])
	{
		copy_to_trans($supp_trans);
		// supplier has changed
		// delete all the order items - drastic but necessary because of
		// change of currency, etc
		$supp_trans->clear_items();
		read_supplier_details_to_trans($supp_trans, $_POST['supplier_id']);
		copy_from_trans($supp_trans);
	}

   	date_row(_("Date") . ":", 'tran_date', '', true, 0, 0, 0, "", true);

   	ref_row(_("Reference:"), 'reference', '', $Refs->get_next($supp_trans->trans_type, null,array('supplier' => get_post('supplier_id'), 'date' => get_post('tran_date'))), false, $supp_trans->trans_type);

	if ($supp_trans->trans_type == ST_SUPPCREDIT)
	{
		label_row(_("Source Invoices:"), implode(',' , $supp_trans->src_docs),'','','src_docs');
	}
	
	if ($supp_trans->trans_type == ST_SUPPINVOICE)
	{
		text_row(_("Supplier's Bill No.:"), 'supp_reference', $_POST['supp_reference'], 20, 60);
	}else 	
		text_row(_("Supplier's Ref.:"), 'supp_reference', $_POST['supp_reference'], 20, 60);
		
	date_row(_("Bill Date") . ":", 'bill_date');	
	
	//text_row(_("Supplier's Ref.:"), 'supp_reference', $_POST['supp_reference'], 20, 60);

	table_section(2, "33%");

	if (isset($_POST['_tran_date_changed'])) {
		$Ajax->activate('_ex_rate');
		$supp_trans->tran_date = $_POST['tran_date'];
		get_duedate_from_terms($supp_trans);
		$_POST['due_date'] = $supp_trans->due_date;
		$Ajax->activate('due_date');
	}

    date_row(_("Due Date") . ":", 'due_date');

    label_row(_("Terms:"), $supp_trans->terms['description']);

	if (get_company_pref('use_dimension'))
		dimensions_list_row(_('Dimension').':', 'dimension', null, true, _('Default'), false, 1);

	if (get_company_pref('use_dimension') == 2)
		dimensions_list_row(_('Dimension 2').':', 'dimension2', null, true, _('Default'), false, 2);
	if ($supp_trans->trans_type == ST_SUPPINVOICE)
	{
		// text_row(_("Credit Period:"), 'credit_period', $_POST['credit_period'], 20, 60);	
		// date_row(_("Cleared Date") . ":", 'cleared_date');
		shippers_list_row(_("Inco Terms:"), 'purch_inco_terms', $_POST['purch_inco_terms'],true);
		purchase_types_list_cells(_("Purchase Type:"), 'purch_type', $_POST['purch_type'],false);
		
	}
	table_section(3, "33%");

	set_global_supplier($_POST['supplier_id']);

	$company_currency = get_company_currency();

	if ($supp_trans->currency != $company_currency)
	{
        label_row(_("Supplier's Currency:"), "<b>" . $supp_trans->currency . "</b>");
		exchange_rate_display($company_currency, $supp_trans->currency, $_POST['tran_date']);
	}

  	label_row(_("Tax Group:"), $supp_trans->tax_description);
	supplier_credit_row($supp_trans->supplier_id, $supp_trans->credit);
	
	if ($supp_trans->trans_type==ST_SUPPINVOICE){
	// table_section(4);	
	text_row(_("Declaration No.:"), 'declaration_no', null, 20, 60);
	small_amount_row(_("CIF Value"), 'cif_value',price_format(get_post('cif_value',0)));
	
	small_amount_row(_("VAT Import Value"), 'vat_import_value',
	price_format(get_post('vat_import_value',0)));
	
	small_amount_row(_("Custom Duty"), 'custom_duties',
	price_format(get_post('custom_duties',0)));
	
	text_row(_("Container Number:"), 'container_number', null, 20, 60);
	text_row(_("BL No.:"), 'bl_no', null, 20, 60);
	
	}

	end_outer_table(1);
}


function direct_creditnote_header(&$supp_trans)
{
	global $Ajax, $Refs;
	
	// if vars have been lost, recopy
	if (!isset($_POST['tran_date']))
		copy_from_trans($supp_trans);

	start_outer_table(TABLESTYLE2, "width='95%'");

	table_section(1);

	if ($supp_trans->trans_type == ST_SUPPCREDIT && $supp_trans->src_docs)
	{
		$_POST['supplier_id'] = $supp_trans->supplier_id;
		$supp = $supp_trans->supplier_name." - ".$supp_trans->currency;
		label_row(_("Supplier:"), $supp.hidden('supplier_id', $_POST['supplier_id'], false));
	}
	else
	{
    	/*if (!isset($_POST['supplier_id']) && (get_global_supplier() != ALL_TEXT))
    		$_POST['supplier_id'] = get_global_supplier();*/

		if (!$supp_trans->trans_no)
			supplier_list_row(_("Supplier:"), 'supplier_id', null, _("Select a Supplier"), true);
		else
			label_row(_("Supplier:"), $supp_trans->supplier_name
			.($supp_trans->currency ? ' - '.$supp_trans->currency  : '')
			.hidden('supplier_id', $_POST['supplier_id'], false));
	}
	if ($supp_trans->supplier_id != $_POST['supplier_id'])
	{
		copy_to_trans($supp_trans);
		// supplier has changed
		// delete all the order items - drastic but necessary because of
		// change of currency, etc
		$supp_trans->clear_items();
		read_supplier_details_to_trans($supp_trans, $_POST['supplier_id']);
		copy_from_trans($supp_trans);
	}

   	date_row(_("Date") . ":", 'tran_date', '', true, 0, 0, 0, "", true);

   	ref_row(_("Reference:"), 'reference', '', $Refs->get_next($supp_trans->trans_type, null, 
   		array('supplier' => get_post('supplier_id'), 'date' => get_post('tran_date'))), false, $supp_trans->trans_type);

	if ($supp_trans->trans_type == ST_SUPPCREDIT)
	{
		label_row(_("Source Invoices:"), implode(',' , $supp_trans->src_docs),'','','src_docs');
	}
	
	if ($supp_trans->trans_type == ST_SUPPINVOICE)
	{
		text_row(_("Supplier's Bill No.:"), 'supp_reference', $_POST['supp_reference'], 20, 60);
	}else 	
		text_row(_("Supplier's Ref.:"), 'supp_reference', $_POST['supp_reference'], 20, 60);
		
	date_row(_("Bill Date") . ":", 'bill_date');	
	
	//text_row(_("Supplier's Ref.:"), 'supp_reference', $_POST['supp_reference'], 20, 60);

	table_section(2, "33%");

	if (isset($_POST['_tran_date_changed'])) {
		$Ajax->activate('_ex_rate');
		$supp_trans->tran_date = $_POST['tran_date'];
		get_duedate_from_terms($supp_trans);
		$_POST['due_date'] = $supp_trans->due_date;
		$Ajax->activate('due_date');
	}

    date_row(_("Due Date") . ":", 'due_date');

    label_row(_("Terms:"), $supp_trans->terms['description']);

	if (get_company_pref('use_dimension'))
		dimensions_list_row(_('Dimension').':', 'dimension', null, true, _('Default'), false, 1);

	if (get_company_pref('use_dimension') == 2)
		dimensions_list_row(_('Dimension 2').':', 'dimension2', null, true, _('Default'), false, 2);
	
	locations_list_row(_("Return From:"), 'Location', null, false, true, $order->fixed_asset); 
	
	hidden('declaration_no','');
	hidden('cif_value',0);
	hidden('vat_import_value',0);
	hidden('custom_duties',0);
	hidden('container_number','');
	hidden('bl_no','');
	
	table_section(3, "33%");

	set_global_supplier($_POST['supplier_id']);

	$company_currency = get_company_currency();

	if ($supp_trans->currency != $company_currency)
	{
        label_row(_("Supplier's Currency:"), "<b>" . $supp_trans->currency . "</b>");
		exchange_rate_display($company_currency, $supp_trans->currency, $_POST['tran_date']);
	}

  	label_row(_("Tax Group:"), $supp_trans->tax_description);
	supplier_credit_row($supp_trans->supplier_id, $supp_trans->credit);

	end_outer_table(1);
}

//--------------------------------------------------------------------------------------------------

function invoice_totals(&$supp_trans ,$mode=0)
{
	global $Ajax;

	copy_to_trans($supp_trans, $mode); // ## ramesh for invoice edit, GL should not add

	$colspan = 2;
 	div_start('tax_table');
   	start_table(TABLESTYLE, "width='95%'");
   	label_row(_("Sub-total:"), price_format( $supp_trans->ov_amount), "width=80% colspan=$colspan align=right", "width=10% align=right", 2);
	
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

    $taxes = $supp_trans->get_taxes($supp_trans->tax_group_id,$supp_trans->freight_cost);
    $tax_total = display_edit_tax_items($taxes, $colspan, $supp_trans->tax_included, 0, true);
	
	start_row();
	label_cell(_("Round Off"), "colspan=$colspan align=right");
	small_amount_cells(null, 'ov_roundoff', price_format(get_post('ov_roundoff',0)));
	end_row();

   // $display_total = price_format($supp_trans->ov_amount + $tax_total);
   
   
   $display_total = price_format($supp_trans->ov_amount + $tax_total + $supp_trans->freight_cost 
	+ $supp_trans->additional_charges + $supp_trans->packing_charges + $supp_trans->other_charges + $supp_trans->ov_roundoff);
   
	start_row();
	if ($supp_trans->trans_type == ST_SUPPINVOICE)
    	label_cells(_("Invoice Total:"), 
    		$display_total, "colspan=$colspan width=80% align=right style='font-weight:bold;'", "width=10% align=right style='font-weight:bold;'");
    else
		label_cells(_("Credit Note Total"),
			$display_total, "colspan=$colspan width=80% align=right style='font-weight:bold;color:red;'", "width=10% nowrap align=right style='font-weight:bold;color:red;'");
			
	submit_cells('update', _("Update"), "width=10% colspan=2 align='right'", _("Refresh"), true);
	end_row();
    end_table(1);
    start_table(TABLESTYLE2);
    textarea_row(_("Memo:"), "Comments", null, 50, 3);
    end_table(1);
	div_end();
}

//--------------------------------------------------------------------------------------------------
function display_gl_controls(&$supp_trans, $k)
{
	$accs = get_supplier_accounts($supp_trans->supplier_id);
   /* if (!isset($_POST['gl_code']))
		$_POST['gl_code'] = $accs['purchase_account'] ? 
			$accs['purchase_account'] : get_company_pref('default_cogs_act');*/

	alt_table_row_color($k);
	//echo gl_all_accounts_list('gl_code', null, false, true);
		echo gl_all_accounts_list('gl_code', null, false, true, _("Select a Account Name"));
	$dim = get_company_pref('use_dimension');
	if ($dim >= 1)
		dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
	if ($dim > 1)
		dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
	amount_cells(null, 'amount');
	if ($dim < 1)	
		text_cells_ex(null, 'memo_', 35, 50, null, null, null, hidden('dimension_id', 0, false).hidden('dimension2_id', 0, false));
	elseif ($dim < 2)	
		text_cells_ex(null, 'memo_', 35, 50, null, null, null, hidden('dimension2_id', 0, false));
	else	
		text_cells_ex(null, 'memo_', 35, 50, null, null, null);
	submit_cells('AddGLCodeToTrans', _("Add"), "",
		    _('Add GL Line'), true);
	submit_cells('ClearFields', _("Reset"), "",
		    _("Clear all GL entry fields"), true);
	end_row();	
}

// $mode = 0 none at the moment
//		 = 1 display on invoice/credit page
//		 = 2 display on view invoice
//		 = 3 display on view credit

function display_gl_items(&$supp_trans, $mode=0)
{
	global $path_to_root, $Ajax;

    // if displaying in form, and no items, exit
    if (($mode == 2 || $mode == 3) && count($supp_trans->gl_codes) == 0)
    	return 0;

	if ($supp_trans->trans_type == ST_SUPPINVOICE)
		$heading = _("GL Items for this Invoice");
	else
		$heading = _("GL Items for this Credit Note");

	start_outer_table(TABLESTYLE, "width='95%'");

	if ($mode == 1)
	{
		$qes = has_quick_entries(QE_SUPPINV);
		if ($qes !== false)
		{
			echo "<div style='float:right;'>";
			echo _("Quick Entry:")."&nbsp;"; 
			echo quick_entries_list('qid', null, QE_SUPPINV, true);
			$qid = get_quick_entry(get_post('qid'));
			if (list_updated('qid')) {
				unset($_POST['totamount']); // enable default
				$Ajax->activate('totamount');
			}
			echo "&nbsp;".$qid['base_desc'].":"."&nbsp;";

			$amount = input_num('totamount', $qid['base_amount']);
			$dec = user_price_dec();
			echo "<input class='amount' type='text' name='totamount' size='7' maxlength='12' dec='$dec' value='$amount'>&nbsp;";
			submit('go', _("Go"), true, false, true);
			echo "</div>";

		}	
	}
	display_heading($heading);

	end_outer_table(0, false);

	div_start('gl_items');
	start_table(TABLESTYLE, "width='95%'");

	$dim = get_company_pref('use_dimension');
	if ($dim == 2)
    	$th = array(_("Account"), _("Name"), _("Dimension")." 1", _("Dimension")." 2", _("Amount"), _("Memo"));
	elseif ($dim == 1)
    	$th = array(_("Account"), _("Name"), _("Dimension"), _("Amount"), _("Memo"));
    else
    	$th = array(_("Account"), _("Name"), _("Amount"), _("Memo"));

	if ($mode == 1)
	{
		$th[] = "";
		$th[] = "";
	}	
	table_header($th);
	$total_gl_value=$total = 0;
	$i = $k = 0;

	if (count($supp_trans->gl_codes) > 0)
	{

		foreach ($supp_trans->gl_codes as $entered_gl_code)
		{

			alt_table_row_color($k);

			if ($mode == 3)
				$entered_gl_code->amount = -$entered_gl_code->amount;

			label_cell($entered_gl_code->gl_code);
			label_cell($entered_gl_code->gl_act_name);

			if ($dim >= 1)
   				label_cell(get_dimension_string($entered_gl_code->gl_dim, true));
			if ($dim > 1)
   				label_cell(get_dimension_string($entered_gl_code->gl_dim2, true));

			amount_cell($entered_gl_code->amount, true);
			label_cell($entered_gl_code->memo_);

			if ($mode == 1)
			{
				delete_button_cell("Delete2" . $entered_gl_code->Counter, _("Delete"),
					  _('Remove line from document'));
                //edit_button_cell("Edit" . $entered_gl_code->Counter, _("Edit"),
                  //    _('Edit line from document'));
			}	
			end_row();
			if ($mode > 1) {
				if ($supp_trans->tax_included || !is_tax_account($entered_gl_code->gl_code))
					$total_gl_value += $entered_gl_code->amount;
			}		
			else	
				$total_gl_value += $entered_gl_code->amount;
			$total += $entered_gl_code->amount;	
			$i++;
			if ($i > 15)
			{
				$i = 0;
				table_header($th);
			}
		}

	}
	if ($mode == 1)
		display_gl_controls($supp_trans, $k);
	$colspan = ($dim == 2 ? 4 : ($dim == 1 ? 3 : 2));
	label_row(_("Total"), price_format($total),
		"colspan=".$colspan." align=right", "nowrap align=right", ($mode==1?3:1));

	end_table(1);
	div_end();

	return $total_gl_value;
}

//--------------//-----------------------------------------------------------------------------------------

function display_grn_items_for_selection(&$supp_trans, $k)
{
	if ($supp_trans->trans_type == ST_SUPPINVOICE)	// outstanding grns and eventually for selected invoice
		$result = get_grn_items(0, $supp_trans->supplier_id, true, false, $supp_trans->trans_no);
	else
	{ // only invoiced
		if (isset($_POST['receive_begin']) && isset($_POST['receive_end']))
			$result = get_grn_items(0, $supp_trans->supplier_id, false, true, 0, $_POST['receive_begin'], $_POST['receive_end']);
		elseif ($supp_trans->src_docs)
			$result = get_grn_items(0, $supp_trans->supplier_id, false, true, array_keys($supp_trans->src_docs));
		else
			$result = get_grn_items(0, $supp_trans->supplier_id, false, true);
	}
    if (db_num_rows($result) == 0)
    {
    	return false;
    }

    /*Set up a table to show the outstanding GRN items for selection */
    while ($myrow = db_fetch($result))
    {
		$grn_already_on_invoice = false;

    	foreach ($supp_trans->grn_items as $entered_grn)
    	{
    		if ($entered_grn->id == $myrow["id"])
    		{
    			$grn_already_on_invoice = true;
    		}
    	}

    	if ($grn_already_on_invoice == false)
    	{
			alt_table_row_color($k);

			$n = $myrow["id"];
			
			
			label_cell($sl_no);
			
			if($myrow["purch_order_no"] != '-1'){
			label_cell(get_trans_view_str(ST_SUPPRECEIVE, $myrow["grn_batch_id"]), "nowrap align='right'");
			}else{
			label_cell(multi_po_receive_view_str(ST_SUPPRECEIVE, $myrow["grn_batch_id"]), "nowrap align='right'");
			}
		   	hidden('qty_recd'.$n, $myrow["qty_recd"]);
           	hidden('item_code'.$n, $myrow["item_code"]);
           	hidden('item_description'.$n, $myrow["description"]);
           	hidden('prev_quantity_inv'.$n, $myrow['quantity_inv']);
           	hidden('order_price'.$n, $myrow['unit_price']);
           	hidden('po_detail_item'.$n, $myrow['po_detail_item']);
			hidden('discount_percent'.$n, $myrow['discount_percent']);
			
        	if($myrow["purch_order_no"] != '-1'){
        	label_cell(get_trans_view_str(ST_PURCHORDER, $myrow["purch_order_no"]), "nowrap align='right'");
			}else{
			label_cell("#");
			}
            label_cell($myrow["item_code"]);
            label_cell($myrow["description"]);
            label_cell(sql2date($myrow["delivery_date"]));
            $dec = get_qty_dec($myrow["item_code"]);
            qty_cell($myrow["qty_recd"], false, $dec);
            qty_cell($myrow["quantity_inv"], false, $dec);
            if ($supp_trans->trans_type == ST_SUPPINVOICE)
            	qty_cells(null, 'this_quantity_inv'.$n, number_format2($myrow["qty_recd"] - $myrow["quantity_inv"], $dec), 
            		null, null, $dec);
            else
            	qty_cells(null, 'This_QuantityCredited'.$n, number_format2(max($myrow["quantity_inv"], 0), $dec), 
            		null, null, $dec);
            $dec2 = 0;
            if ($supp_trans->trans_type == ST_SUPPINVOICE)
            {
            	amount_cells(null, 'ChgPrice'.$n, price_decimal_format($myrow["unit_price"], $dec2), null, null, $dec2);
				
				label_cell(($myrow["discount_percent"])."%", $dec2);
            	amount_cell(round2((1-$myrow["discount_percent"]/100)*$myrow["unit_price"] * ($myrow["qty_recd"] - $myrow["quantity_inv"]), user_price_dec()));
				
            	
            }
            else
            {
            	amount_cells(null, 'ChgPrice'.$n, price_decimal_format($myrow["act_price"], $dec2), null, null, $dec2);
            	amount_cell(round2($myrow["act_price"] * max($myrow['quantity_inv'], 0), user_price_dec()));
            }	
            if ($supp_trans->trans_type == ST_SUPPINVOICE)
        		submit_cells('grn_item_id'.$n, _("Add"), '', _("Add to Invoice"), true);
        	else	
        		submit_cells('grn_item_id'.$n, _("Add"), '', _("Add to Credit Note"), true);
    		if (($supp_trans->trans_type == ST_SUPPINVOICE) && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE')) {	// Added 2008-10-18 by Joe Hunt. Special access rights needed.
        		submit_cells('void_item_id'.$n, _("Remove"), '', _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!"), true);
				submit_js_confirm('void_item_id'.$n,
					sprintf(_('You are about to remove all yet non-invoiced items from delivery line #%d. This operation also irreversibly changes related order line. Do you want to continue ?'), $n));
			}
			end_row();
    	}
    }
    return true;
}

//------------------------------------------------------------------------------------

// $mode = 0 none at the moment
//		 = 1 display on invoice/credit page
//		 = 2 display on view invoice
//		 = 3 display on view credit

function display_grn_items(&$supp_trans, $mode=0)
{
	global $path_to_root;

    $ret = true;
    // if displaying in form, and no items, exit
    if (($mode == 2  || $mode == 3) && count($supp_trans->grn_items) == 0)
    	return 0;

	start_outer_table("style='border:1px solid #cccccc;' width='95%'");

	$heading2 = "";
	if ($mode == 1)
	{
		if ($supp_trans->trans_type == ST_SUPPINVOICE)
		{
			$heading = _("Items Received Yet to be Invoiced");
    		if ($_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	// Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
				$heading2 = _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!");
		}
		else
			$heading = _("Delivery Item Selected For Adding To A Supplier Credit Note");
	}
	else
	{
		if ($supp_trans->trans_type == ST_SUPPINVOICE)
			$heading = _("Received Items Charged on this Invoice");
		else
			$heading = _("Received Items Credited on this Note");
	}

	display_heading($heading);

	if ($mode == 1)
	{
		if ($supp_trans->trans_type == ST_SUPPCREDIT)
		{
			echo "</td>";
			date_cells(_("Received between"), 'receive_begin', "", null, 
				-user_transaction_days(), 0, 0, "valign=middle");
			date_cells(_("and"), 'receive_end', '', null, 1, 0, 0, "valign=middle");
			submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), true);
			echo "<td>";
		}

		if ($heading2 != "")
		{
			display_note($heading2, 0, 0, "class='overduefg'");
		}
		echo "</td><td width='10%' align='right'>";
		submit('InvGRNAll', _("Add All Items"), true, false,true);
	}

	end_outer_table(0, false);

  	div_start('grn_items');
	start_table(TABLESTYLE, "width='95%'");
	if ($mode == 1)
	{
    	$th = array(_("S.No."), _("Delivery"), _("P.O."), _("Item"), _("Description"),
    		_("Received On"), _("Quantity Received"), _("Quantity Invoiced"),
    		_("Qty Yet To Invoice"), $supp_trans->tax_included ? _("Price after Tax") : _("Price before Tax"),_("Discount%"), _("Total"), "");
			
    	if (($supp_trans->trans_type == ST_SUPPINVOICE) && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	// Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
    		$th[] = "";
		if ($supp_trans->trans_type == ST_SUPPCREDIT)
		{
			$th[8] = _("Qty Yet To Credit");
		}
    }
    else
		$th = array(_("S.No."), _("Delivery"), _("Item"), _("Description"),
			_("Quantity"), _("Price"), _("Discount%"), _("Line Value"));

	table_header($th);
    $total_grn_value = 0;
    $i = $k = 0;
	$loc_total= 0;
    $loc_unit_price = 0;
	$total_qty =0;
	$sl_no=1;
	if (count($supp_trans->grn_items) > 0)
	{	
	

    	foreach ($supp_trans->grn_items as $entered_grn)
    	{

    		alt_table_row_color($k);
			
			label_cell($sl_no);

			$grn_batch = get_grn_batch_from_item($entered_grn->id);
    		label_cell(get_trans_view_str(ST_SUPPRECEIVE, $grn_batch), "nowrap align='right'");
    		if ($mode == 1)
    		{
//				label_cell($entered_grn->id);
 				$row = get_grn_batch($grn_batch);
				//display_error($row['purch_order_no']);
				if($row['purch_order_no'] != '-1'){
				label_cell(get_trans_view_str(ST_PURCHORDER, $row["purch_order_no"]), "nowrap align='right'"); // PO
				}else{
				label_cell('#');
				}// PO
			}	
			label_cell($entered_grn->item_code);
			label_cell($entered_grn->item_description);
            $dec = get_qty_dec($entered_grn->item_code);
            if ($mode == 1)
            {
           		label_cell(sql2date($row['delivery_date']));
 				qty_cell($entered_grn->qty_recd, false, $dec);
				qty_cell($entered_grn->prev_quantity_inv, false, $dec);
            }
			qty_cell(abs($entered_grn->this_quantity_inv), true, $dec);
			amount_decimal_cell($entered_grn->chg_price);
			
			amount_decimal_cell($entered_grn->discount_percent);
			
			amount_cell( round2($entered_grn->chg_price * abs($entered_grn->this_quantity_inv)*(1-$entered_grn->discount_percent/100) , user_price_dec()), true);
			
			
			
			if ($mode == 1)
			{
				delete_button_cell("Delete" . $entered_grn->id, _("Edit"), _('Edit document line'));
				if (($supp_trans->trans_type == ST_SUPPINVOICE) && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	  
					label_cell("");
			}	
			end_row();

    		$total_grn_value += round2($entered_grn->chg_price * abs($entered_grn->this_quantity_inv)*(1-$entered_grn->discount_percent/100),
			   user_price_dec());   

    		$i++;
			$sl_no++;
			
    		if ($i > 15)
    		{
    		 	$i = 0;
    		 	table_header($th);
    		}
			
		  $total_qty += $entered_grn->this_quantity_inv;	
    	}
    }
	if ($mode == 1)
	{
		$ret = display_grn_items_for_selection($supp_trans, $k);
    	$colspan = 11;
	}
	else
		$colspan = 7;
		
	label_cells(_("Total"), number_format2($total_qty,3),
		"colspan=4 align=right", "nowrap align=right");	
		
	label_cells(_("Total"), price_format($total_grn_value),
		"colspan=2 align=right", "nowrap align=right");
	
	
	if (!$ret)
	{
		start_row();
		echo "<td colspan=".($colspan + 1).">";
		if ($supp_trans->trans_type == ST_SUPPINVOICE)
			display_note(_("There are no outstanding items received from this supplier that have not been invoiced by them."), 0, 0);
		else
		{
			display_note(_("There are no received items for the selected supplier that have been invoiced."));
			display_note(_("Credits can only be applied to invoiced items."), 0, 0);
		}
		echo "</td>";
		end_row();
	}	
    end_table(1);
    div_end();

	return $total_grn_value;
}

//ravi direct credit note view
function display_grn_creditnote_items(&$supp_trans, $mode=0)
{
	global $path_to_root;

    $ret = true;
    // if displaying in form, and no items, exit
    if (($mode == 2  || $mode == 3) && count($supp_trans->grn_items) == 0)
    	return 0;

	start_outer_table("style='border:1px solid #cccccc;' width='95%'");

	$heading2 = "";
	if ($mode == 1)
	{
		if ($supp_trans->trans_type == ST_SUPPINVOICE)
		{
			$heading = _("Items Received Yet to be Invoiced");
    		if ($_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	// Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
				$heading2 = _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!");
		}
		else
			$heading = _("Delivery Item Selected For Adding To A Supplier Credit Note");
	}
	else
	{
		if ($supp_trans->trans_type == ST_SUPPINVOICE)
			$heading = _("Received Items Charged on this Invoice");
		else
			$heading = _("Received Items Credited on this Note");
	}

	display_heading($heading);

	if ($mode == 1)
	{
		if ($supp_trans->trans_type == ST_SUPPCREDIT)
		{
			echo "</td>";
			date_cells(_("Received between"), 'receive_begin', "", null, 
				-user_transaction_days(), 0, 0, "valign=middle");
			date_cells(_("and"), 'receive_end', '', null, 1, 0, 0, "valign=middle");
			submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), true);
			echo "<td>";
		}

		if ($heading2 != "")
		{
			display_note($heading2, 0, 0, "class='overduefg'");
		}
		echo "</td><td width='10%' align='right'>";
		submit('InvGRNAll', _("Add All Items"), true, false,true);
	}

	end_outer_table(0, false);

  	div_start('grn_items');
	start_table(TABLESTYLE, "width='95%'");
	if ($mode == 1)
	{
    	$th = array(_("S.No."), _("Delivery"), _("P.O."), _("Item"), _("Description"),
    		_("Received On"), _("Quantity Received"), _("Quantity Invoiced"),
    		_("Qty Yet To Invoice"), $supp_trans->tax_included ? _("Price after Tax") : _("Price before Tax"),_("Discount%"), _("Total"), "");
			
    	if (($supp_trans->trans_type == ST_SUPPINVOICE) && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	// Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
    		$th[] = "";
		if ($supp_trans->trans_type == ST_SUPPCREDIT)
		{
			$th[8] = _("Qty Yet To Credit");
		}
    }
    else
		$th = array(_("S.No."), _("Delivery"), _("Item"), _("Description"),
			_("Quantity"), _("Price"), _("Discount%"), _("Line Value"));

	table_header($th);
    $total_grn_value = 0;
    $i = $k = 0;
	$loc_total= 0;
    $loc_unit_price = 0;
	$total_qty =0;
	$sl_no=1;
	if (count($supp_trans->grn_items) > 0)
	{	
	

    	foreach ($supp_trans->grn_items as $entered_grn)
    	{

    		alt_table_row_color($k);
			
			label_cell($sl_no);

			$grn_batch = get_grn_batch_from_item($entered_grn->id);
    		label_cell('#');
    		if ($mode == 1)
    		{
//				label_cell($entered_grn->id);
 				$row = get_grn_batch($grn_batch);
				//display_error($row['purch_order_no']);
				if($row['purch_order_no'] != '-1'){
				label_cell(get_trans_view_str(ST_PURCHORDER, $row["purch_order_no"]), "nowrap align='right'"); // PO
				}else{
				label_cell('#');
				}// PO
			}	
			label_cell($entered_grn->item_code);
			label_cell($entered_grn->item_description);
            $dec = get_qty_dec($entered_grn->item_code);
            if ($mode == 1)
            {
           		label_cell(sql2date($row['delivery_date']));
 				qty_cell($entered_grn->qty_recd, false, $dec);
				qty_cell($entered_grn->prev_quantity_inv, false, $dec);
            }
			qty_cell(abs($entered_grn->this_quantity_inv), true, $dec);
			amount_decimal_cell($entered_grn->chg_price);
			
			amount_decimal_cell($entered_grn->discount_percent);
			
			amount_cell( round2($entered_grn->chg_price * abs($entered_grn->this_quantity_inv)*(1-$entered_grn->discount_percent/100) , user_price_dec()), true);
			
			
			
			if ($mode == 1)
			{
				delete_button_cell("Delete" . $entered_grn->id, _("Edit"), _('Edit document line'));
				if (($supp_trans->trans_type == ST_SUPPINVOICE) && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	  
					label_cell("");
			}	
			end_row();

    		$total_grn_value += round2($entered_grn->chg_price * abs($entered_grn->this_quantity_inv)*(1-$entered_grn->discount_percent/100),
			   user_price_dec());   

    		$i++;
			$sl_no++;
			
    		if ($i > 15)
    		{
    		 	$i = 0;
    		 	table_header($th);
    		}
			
		  $total_qty += $entered_grn->this_quantity_inv;	
    	}
    }
	if ($mode == 1)
	{
		$ret = display_grn_items_for_selection($supp_trans, $k);
    	$colspan = 11;
	}
	else
		$colspan = 7;
		
	label_cells(_("Total"), number_format2($total_qty,3),
		"colspan=4 align=right", "nowrap align=right");	
		
	label_cells(_("Total"), price_format($total_grn_value),
		"colspan=2 align=right", "nowrap align=right");
	
	
	if (!$ret)
	{
		start_row();
		echo "<td colspan=".($colspan + 1).">";
		if ($supp_trans->trans_type == ST_SUPPINVOICE)
			display_note(_("There are no outstanding items received from this supplier that have not been invoiced by them."), 0, 0);
		else
		{
			display_note(_("There are no received items for the selected supplier that have been invoiced."));
			display_note(_("Credits can only be applied to invoiced items."), 0, 0);
		}
		echo "</td>";
		end_row();
	}	
    end_table(1);
    div_end();

	return $total_grn_value;
}

//--------------------------------------------------------------------------------------------------
function get_duedate_from_terms(&$trans)
{
	$date = get_class($trans) == 'purch_order' ? $trans->orig_order_date : $trans->tran_date;

	if (!is_date($date))
	{
		$date = Today();
	}
	if ($trans->terms['day_in_following_month'])
	{ /*Its a day in the following month when due */
		$trans->due_date =
			add_days(end_month($date), $trans->terms["day_in_following_month"]);
	}
	else
	{ /*Use the Days Before Due to add to the invoice date */
		$trans->due_date = add_days($date, $trans->terms["days_before_due"]);
	}
}

//--------------------------------------------------------------------------------------------------


function invoice_mheader(&$supp_trans=0,$sap_search_no=0,$pur_ord_no=0)
{
	global $Ajax, $Refs;
	// if vars have been lost, recopy
	if (!isset($_POST['tran_date']))
		copy_from_trans($supp_trans);

	start_outer_table(TABLESTYLE2, "width='95%'");

	table_section(1);

	if ($supp_trans->trans_type == ST_SUPPCREDIT && $supp_trans->src_docs)
	{
		$_POST['supplier_id'] = $supp_trans->supplier_id;
		$supp = $supp_trans->supplier_name." - ".$supp_trans->currency;
		label_row(_("Supplier:"), $supp.hidden('supplier_id', $_POST['supplier_id'], false));
	}
	else
	{
	    // removed due to client not Required
    	/* if (!isset($_POST['supplier_id']) && (get_global_supplier() != ALL_TEXT))
    		$_POST['supplier_id'] = get_global_supplier(); */
			
		if (!$supp_trans->trans_no)
			supplier_list_row(_("Supplier:"), 'supplier_id', $_POST['supplier_id'], true, true);
		else
			label_row(_("Supplier:"), $supp_trans->supplier_name
			.($supp_trans->currency ? ' - '.$supp_trans->currency  : '')
			.hidden('supplier_id', $_POST['supplier_id'], false));
		
	}
	
	if ($supp_trans->supplier_id != $_POST['supplier_id'])
	{
	
	 	copy_to_trans($supp_trans);
			// supplier has changed
		// delete all the order items - drastic but necessary because of
		// change of currency, etc
		$supp_trans->clear_po_items();
		
		read_supplier_details_to_trans($supp_trans, $_POST['supplier_id']);
		copy_from_trans($supp_trans);
		
	}

   	date_row(_("Date") . ":", 'tran_date', '', true, 0, 0, 0, "", true);

	locations_list_row(_("Location:<b style='color:red;'>*</b>"), 'Location', null, false, true, 0); 
	
	if($supp_trans->trans_type==20)
	ref_row(_("Reference:"), 'reference', '', $Refs->get_next($supp_trans->trans_type, null, 
   		array('supplier' => get_post('supplier_id'), 'date' => get_post('tran_date'), 'inv_type' => 'PI')), false, $supp_trans->trans_type, array('date'=> get_post('tran_date'),'location'=> 'PI'));
	else		
   	ref_row(_("Reference:"), 'reference', '', $Refs->get_next($supp_trans->trans_type, null, 
   		array('supplier' => get_post('supplier_id'), 'date' => get_post('tran_date'), 'location' => @$_POST['Location'])), false, $supp_trans->trans_type, array('date'=> get_post('tran_date'),'location'=> @$_POST['Location']));

	if ($supp_trans->trans_type == ST_SUPPCREDIT)
	{
		label_row(_("Source Invoices:"), implode(',' , $supp_trans->src_docs),'','','src_docs');
		
		//text_row(_("Bill No.:"), 'bill_no', $_POST['bill_no'], 20, 60);	
	}
	if($supp_trans->trans_type == ST_SUPPINVOICE)
	{
		text_row(_("Supplier's Bill No.:"), 'supp_reference', $_POST['supp_reference'], 20, 60);
	}
    else
    {
	text_row(_("Supplier's Ref.:"), 'supp_reference', $_POST['supp_reference'], 20, 60);

    }	
	date_row(_("Bill Date") . ":", 'bill_date');
		
	
	table_section(2, "33%");

	if (isset($_POST['_tran_date_changed'])) {
		$Ajax->activate('_ex_rate');
		$supp_trans->tran_date = $_POST['tran_date'];
		get_duedate_from_terms($supp_trans);
		$_POST['due_date'] = $supp_trans->due_date;
		$Ajax->activate('due_date');
	}

    date_row(_("Due Date") . ":", 'due_date');

    label_row(_("Terms:"), $supp_trans->terms['description']);

	if (get_company_pref('use_dimension'))
		dimensions_list_row(_('Dimension').':', 'dimension', null, true, _('Default'), false, 1);

	if (get_company_pref('use_dimension') == 2)
		dimensions_list_row(_('Dimension 2').':', 'dimension2', null, true, _('Default'), false, 2);
		
	
       
	 //  display_error($pur_ord_no);
	
	// text_row(_("Credit Period:"), 'credit_period', $_POST['credit_period'], 20, 60);	
	
	// date_row(_("Cleared Date") . ":", 'cleared_date');
	
	$company_currency = get_company_currency();
	if($pur_ord_no != '' || $sap_search_no != ''){
	$po_tran = get_po_no_inco_terms($_POST['supplier_id'],$sap_search_no,$pur_ord_no);
	
	//display_error($po_tran['purch_inco_terms']);
	 
	shippers_list_row(_("Inco Terms:"), 'purch_inco_terms', $po_tran['purch_inco_terms'],true);
	
	}else{
	shippers_list_row(_("Inco Terms:"), 'purch_inco_terms', $_POST['purch_inco_terms'],true);
	}
	
	if ($supp_trans->currency != $company_currency)
	{
		
		purchase_types_list_cells(_("Purchase Type:"), 'purch_type', 2,false);
	}else{
	purchase_types_list_cells(_("Purchase Type:"), 'purch_type', 1,false);
	}	
	

	table_section(3, "33%");

	set_global_supplier($_POST['supplier_id']);

	

	if ($supp_trans->currency != $company_currency)
	{
        label_row(_("Supplier's Currency:"), "<b>" . $supp_trans->currency . "</b>");
		if ($_POST['supplier_id'] != '')
		exchange_rate_display($company_currency, $supp_trans->currency, $_POST['tran_date']);
	}

  	label_row(_("Tax Group:"), $supp_trans->tax_description);
	supplier_credit_row($supp_trans->supplier_id, $supp_trans->credit);
	
	
	if ($supp_trans->trans_type == ST_SUPPINVOICE){
	text_row(_("Declaration No.:"), 'declaration_no', null, 20, 60);

	small_amount_row(_("CIF Value"), 'cif_value',price_format(get_post('cif_value',0)));
	
	small_amount_row(_("VAT Import Value"), 'vat_import_value',
	price_format(get_post('vat_import_value',0)));
	
	small_amount_row(_("Custom Duty"), 'custom_duties',
	price_format(get_post('custom_duties',0)));
	
	text_row(_("Container Number :"), 'container_number', null, 20, 60);
	text_row(_("BL No.:"), 'bl_no', null, 20, 60);
	
	}
	
	
	/* text_row(_("LPO No.:"), 'lpo_no', $_POST['lpo_no'], 20, 60);	
	date_row(_("LPO Date") . ":", 'lpo_date');
	date_row(_("Consgnmnt Received Date") . ":", 'consgn_recv_date');
	text_row(_("BOE No.:"), 'boe_no', $_POST['boe_no'], 20, 60);
	date_row(_("BOE Date") . ":", 'boe_date'); */
	

	end_outer_table(1);
}



function display_inv_po_items(&$supp_trans, $mode=0,$supplier_id,$stock_id='',$sap_search_no,$pur_ord_no)
{

	global $path_to_root;

    $ret = true;
    // if displaying in form, and no items, exit
    if (($mode == 2  || $mode == 3) && count($supp_trans->grn_items) == 0)
    	return 0;

	start_outer_table("style='border:1px solid #cccccc;' width='95%'");

	$heading2 = "";
	if ($mode == 1)
	{
		if ($supp_trans->trans_type == ST_SUPPINVOICE)
		{
			$heading = _("Items Ordered Yet to be Invoiced");
			item_search();
    		if ($_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	// Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
				$heading2 = _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!");
		}
		else
			$heading = _("Delivery Item Selected For Adding To A Supplier Credit Note");
	}
	else
	{
		if ($supp_trans->trans_type == ST_SUPPINVOICE)
			$heading = _("Received Items Charged on this Invoice");
		else
			$heading = _("Received Items Credited on this Note");
	}

	display_heading($heading);

	if ($mode == 1)
	{
		if ($supp_trans->trans_type == ST_SUPPCREDIT)
		{
			echo "</td>";
			date_cells(_("Received between"), 'receive_begin', "", null, 
				-user_transaction_days(), 0, 0, "valign=middle");
			date_cells(_("and"), 'receive_end', '', null, 1, 0, 0, "valign=middle");
			submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), true);
			echo "<td>";
		}

		if ($heading2 != "")
		{
			display_note($heading2, 0, 0, "class='overduefg'");
		}
		echo "</td>";
		//submit('InvGRNAll', _("Add All Items"), true, false,true);
	}

	end_outer_table(0, false);

  	div_start('grn_items1');
	start_table(TABLESTYLE, "width='95%'");
	if ($mode == 1)
	{
    	$th = array( _("S.No."), _("P.O."), _("Supplier Ref"),_("Our Ref No"),_("Supplier Date"),_("Item"), _("Description"),
    		 _("Quantity Ordered"),_("Quantity Received"), _("Quantity Invoiced"),
    		_("Qty Yet To Invoice"), $supp_trans->tax_included ? _("Price after Tax") : _("Price before Tax"),_("Discount%"), 
    		_("Total"), "");
    	if (($supp_trans->trans_type == ST_SUPPINVOICE) && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	// Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
    		$th[] = "";
		if ($supp_trans->trans_type == ST_SUPPCREDIT)
		{
			$th[8] = _("Qty Yet To Credit");
		}
    }
    else
		$th = array( _("S.No."), _("Delivery"), _("Item"), _("Description"),
			_("Quantity"), _("Price"), _("Discount%"),  _("Line Value"));

	table_header($th);
    $total_grn_value = 0;
    $i = $k = 0;
	$sl_no=1;

	if (count($supp_trans->inv_items) > 0)
	{
			
    	foreach ($supp_trans->inv_items as $entered_grn)
    	{

    		alt_table_row_color($k);
			
			label_cell($sl_no);

			
    		if ($mode == 1)
    		{
//				label_cell($entered_grn->id);
				$row = get_po_order($entered_grn->id);
				
 				
				label_cell(get_trans_view_str(ST_PURCHORDER, $row["order_no"]), "nowrap align='right'"); // PO
			}	
			
			
			label_cell($entered_grn->sap_no);
			label_cell($entered_grn->our_ord_no);
			label_cell($entered_grn->supplier_date);
			
			view_purchase_stock_status_cell($entered_grn->item_code);
			label_cell($entered_grn->item_description);
            $dec = get_qty_dec($entered_grn->item_code);
            if ($mode == 1)
            {
           		//label_cell(sql2date($row['delivery_date']));
 				qty_cell($entered_grn->qty_recd, false, $dec);
 				$manual_quantity_received=get_manual_received_qunatity($entered_grn->id);
            qty_cell($manual_quantity_received, false, $dec);
				qty_cell($entered_grn->prev_quantity_inv, false, $dec);
            }
			qty_cell(abs($entered_grn->this_quantity_inv), true, $dec);
			amount_decimal_cell($entered_grn->chg_price);
			
			amount_decimal_cell($entered_grn->discount_percent);
			amount_cell( round2($entered_grn->chg_price * abs($entered_grn->this_quantity_inv)*(1-$entered_grn->discount_percent/100) , user_price_dec()), true);

			if ($mode == 1)
			{
				delete_button_cell("Delete" . $entered_grn->id, _("Edit"), _('Edit document line'));
				if (($supp_trans->trans_type == ST_SUPPINVOICE) && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	  
					label_cell("");
			}	
			end_row();

    		
			   
			$total_grn_value += round2($entered_grn->chg_price * abs($entered_grn->this_quantity_inv)*(1-$entered_grn->discount_percent/100),
			   user_price_dec());   

    		$i++;
			
			$sl_no++;
			
    		if ($i > 15)
    		{
    		 	$i = 0;
    		 	table_header($th);
    		}
    	}
    }
	if ($mode == 1)
	{
	if($pur_ord_no != '' || $stock_id != '' || $sap_search_no != ''){
	
		$ret = display_inv_po_items_for_selection($supp_trans, $k,$supplier_id,$stock_id,$sap_search_no,$pur_ord_no);
		}
    	$colspan = 13;
	}
	else
		$colspan = 7;
		
	label_row(_("Total"), price_format($total_grn_value),
		"colspan=$colspan align=right", "nowrap align=right");
	
		
	if (!$ret)
	{
		start_row();
		echo "<td colspan=".($colspan + 1).">";
		if ($supp_trans->trans_type == ST_SUPPINVOICE)
			display_note(_("There are no outstanding items received from this supplier that have not been invoiced by them."), 0, 0);
		else
		{
			display_note(_("There are no received items for the selected supplier that have been invoiced."));
			display_note(_("Credits can only be applied to invoiced items."), 0, 0);
		}
		echo "</td>";
		end_row();
	}	
    end_table(1);
    div_end();

	return $total_grn_value;
}


function get_inv_trans_total($inv) {
		
		$total = 0;
		$dec = user_price_dec();
		
		

		foreach ($inv->inv_items as $ln_itm) {
			$items[] = $ln_itm->item_code;
			$value = round($ln_itm->this_quantity_inv * $ln_itm->order_price*(1 - $ln_itm->discount_percent/100), $dec);
			$prices[] =$value;
			$total += $value;
		}

		if (!$inv->tax_included && isset($items)) { //ravi
			$taxes = get_tax_for_items($items, $prices, 0, $inv->tax_group_id,
			$inv->tax_included,  $inv->tax_group_array);

			foreach($taxes as $tax)
				$total += round($tax['Value'], $dec);
		}
	
		return $total;
}


	
function display_gl_inv_items(&$supp_trans, $mode=0)
{

	global $path_to_root, $Ajax;

    // if displaying in form, and no items, exit
    if (($mode == 2 || $mode == 3) && count($supp_trans->gl_codes1) == 0)
    	return 0;

	if ($supp_trans->trans_type == ST_SUPPINVOICE)
		$heading = _("GL Items for this Invoice");
	else
		$heading = _("GL Items for this Credit Note");

	start_outer_table(TABLESTYLE, "width='95%'");

	if ($mode == 1)
	{
		$qes = has_quick_entries(QE_SUPPINV);
		if ($qes !== false)
		{
			echo "<div style='float:right;'>";
			echo _("Quick Entry:")."&nbsp;"; 
			echo quick_entries_list('qid', null, QE_SUPPINV, true);
			$qid = get_quick_entry(get_post('qid'));
			if (list_updated('qid')) {
				unset($_POST['totamount']); // enable default
				$Ajax->activate('totamount');
			}
			echo "&nbsp;".$qid['base_desc'].":"."&nbsp;";

			$amount = input_num('totamount', $qid['base_amount']);
			$dec = user_price_dec();
			echo "<input class='amount' type='text' name='totamount' size='7' maxlength='12' dec='$dec' value='$amount'>&nbsp;";
			submit('go', _("Go"), true, false, true);
			echo "</div>";

		}	
	}
	display_heading($heading);

	end_outer_table(0, false);

	div_start('gl_items');
	start_table(TABLESTYLE, "width='95%'");

	//$dim = get_company_pref('use_dimension');
	$dim = 0;
	if ($dim == 2)
    	$th = array(_("Account"), _("Name"), _("Dimension")." 1", _("Dimension")." 2", _("Amount"), _("Memo"));
	elseif ($dim == 1)
    	$th = array(_("Account"), _("Name"), _("Dimension"), _("Amount"), _("Memo"));
    else
    	$th = array(_("Account"), _("Name"), _("Amount"), _("Memo"));

	if ($mode == 1)
	{
		$th[] = "";
		$th[] = "";
	}	
	table_header($th);
	$total_gl_value=$total = 0;
	$i = $k = 0;

	if (isset($supp_trans->gl_codes1) && count($supp_trans->gl_codes1) > 0)
	{

		foreach ($supp_trans->gl_codes1 as $entered_gl_code)
		{

			alt_table_row_color($k);

			if ($mode == 3)
				$entered_gl_code->amount = -$entered_gl_code->amount;

			label_cell($entered_gl_code->gl_code);
			label_cell($entered_gl_code->gl_act_name);

			if ($dim >= 1)
   				label_cell(get_dimension_string($entered_gl_code->gl_dim, true));
			if ($dim > 1)
   				label_cell(get_dimension_string($entered_gl_code->gl_dim2, true));

			amount_cell($entered_gl_code->amount, true);
			label_cell($entered_gl_code->memo_);

			if ($mode == 1)
			{
				delete_button_cell("Delete2" . $entered_gl_code->Counter, _("Delete"),
					  _('Remove line from document'));
                //edit_button_cell("Edit" . $entered_gl_code->Counter, _("Edit"),
                     // _('Edit line from document'));
			}	
			end_row();
			if ($mode > 1) {
				if ($supp_trans->tax_included || !is_tax_account($entered_gl_code->gl_code))
					$total_gl_value += $entered_gl_code->amount;
			}		
			else	
				$total_gl_value += $entered_gl_code->amount;
			$total += $entered_gl_code->amount;	
			$i++;
			if ($i > 15)
			{
				$i = 0;
				table_header($th);
			}
		}

	}
	if ($mode == 1)
		display_gl_inv_controls($supp_trans, $k);
	$colspan = ($dim == 2 ? 4 : ($dim == 1 ? 3 : 2));
	label_row(_("Total"), price_format($total),
		"colspan=".$colspan." align=right", "nowrap align=right", ($mode==1?3:1));

	end_table(1);
	div_end();

	return $total_gl_value;
}

function inv_item_totals(&$po_trans,$po_total)
{
	global $Ajax;

	copy_to_trans($po_trans);
	
	
	$colspan = 11;
 	div_start('tax_table');
   	start_table(TABLESTYLE, "width='95%'");
   	label_row(_("Sub Total:"),price_format($po_total), "colspan=$colspan align=right", "nowrap align=right",0);
	
	
	start_row();
	label_cell(_("Freight Charges"), "colspan=$colspan align=right");
	small_amount_cells(null, 'freight_cost', price_format(get_post('freight_cost',0)));
	end_row();
	
	start_row();
	label_cell(_("Wire Charges"), "colspan=$colspan align=right");
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
	
	
	

  $taxes = $_SESSION['inv_trans']->get_grn_taxes(input_num('freight_cost'), true, input_num('additional_charges'), input_num('packing_charges'),input_num('other_charges'));
	
  $tax_total = display_edit_tax_items($taxes, $colspan, $_SESSION['inv_trans']->tax_included, 2);

	$display_total = price_format(($po_total + input_num('freight_cost') + $tax_total + input_num('additional_charges') + input_num('packing_charges') + input_num('other_charges')));
	
	
	 //$taxes = $_SESSION['inv_trans']->get_grn_taxes(input_num('freight_cost'), true);
	
  //  $tax_total = display_edit_tax_items($taxes, $colspan, //$_SESSION['po_trans']->tax_included, 2);

	//$display_total = price_format(($po_total + input_num('freight_cost') + //$tax_total ));
	
	start_row();
	
    	label_cells(_("Grand Total:"), 
    		$display_total, "colspan=$colspan width=80% align=right style='font-weight:bold;'", "width=10% align=right style='font-weight:bold;'");
    
	submit_cells('update', _("Update"), "width=10% colspan=2 align='right'", _("Refresh"), true);
	end_row();
    end_table(1);
	
	start_table(TABLESTYLE2);
	textarea_row(_("Memo:"), 'Comments', null, 70, 4);	
	end_table(2);
   
	div_end();
}


function display_gl_inv_controls(&$supp_trans, $k)
{
	$accs = get_supplier_accounts($supp_trans->supplier_id);
    /*if (!isset($_POST['gl_code']))
		$_POST['gl_code'] = $accs['purchase_account'] ? 
			$accs['purchase_account'] : get_company_pref('default_cogs_act');*/

	alt_table_row_color($k);
	echo gl_all_accounts_list('gl_code', null, false, true, _("Select a Account Name"));
	//$dim = get_company_pref('use_dimension');
	$dim = 0;
	if ($dim >= 1)
		dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
	if ($dim > 1)
		dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
	amount_cells(null, 'amount');
	if ($dim < 1)	
		text_cells_ex(null, 'memo_', 35, 50, null, null, null, hidden('dimension_id', 0, false).hidden('dimension2_id', 0, false));
	elseif ($dim < 2)	
		text_cells_ex(null, 'memo_', 35, 50, null, null, null, hidden('dimension2_id', 0, false));
	else	
		text_cells_ex(null, 'memo_', 35, 50, null, null, null);
	submit_cells('AddGLCodeToTrans', _("Add"), "",
		    _('Add GL Line'), true);
	submit_cells('ClearFields', _("Reset"), "",
		    _("Clear all GL entry fields"), true);
	end_row();	
}

function display_inv_po_items_for_selection(&$supp_trans, $k,$supplier_id,$stock_id,$sap_search_no,$pur_ord_no)
{

	if ($supp_trans->trans_type == ST_SUPPINVOICE)	// outstanding grns and eventually for selected invoice
		$result = get_inv_po_items(0, $supplier_id, true, false, $supp_trans->trans_no,'','',$stock_id,$sap_search_no,$pur_ord_no);
	else
	{ // only invoiced
		if (isset($_POST['receive_begin']) && isset($_POST['receive_end']))
			$result = get_inv_po_items(0, $supplier_id, false, true, 0, $_POST['receive_begin'], $_POST['receive_end']);
		elseif ($supp_trans->src_docs)
			$result = get_inv_po_items(0, $supplier_id, false, true, array_keys($supp_trans->src_docs));
		else
			$result = get_inv_po_items(0, $supplier_id, false, true);
	}

    if (db_num_rows($result) == 0)
    {
    	return false;
    }

    /*Set up a table to show the outstanding GRN items for selection */
	
	
	
    while ($myrow = db_fetch($result))
    {
		$grn_already_on_invoice = false;

    	foreach ($supp_trans->inv_items as $entered_grn)
    	{
    		if ($entered_grn->id == $myrow["po_detail_item"])
    		{
    			$grn_already_on_invoice = true;
    		}
    	}

    	if ($grn_already_on_invoice == false)
    	{
			alt_table_row_color($k);

			$n = $myrow["po_detail_item"];
			
		   	hidden('qty_recd'.$n, $myrow["quantity_ordered"]);
           	hidden('item_code'.$n, $myrow["item_code"]);
           	hidden('item_description'.$n, $myrow["description"]);
           	hidden('prev_quantity_inv'.$n, $myrow['quantity_received']);
           	hidden('order_price'.$n, $myrow['unit_price']);
           	hidden('po_detail_item'.$n, $myrow['po_detail_item']);
			hidden('discount_percent'.$n, $myrow['discount_percent']);
			hidden('sap_no'.$n,$myrow['requisition_no']);
			hidden('our_ord_no'.$n,$myrow['our_ref_no']);
			hidden('supplier_date'.$n,sql2date($myrow['supplier_date']));
			
			
			label_cell($sl_no);
        	label_cell(get_trans_view_str(ST_PURCHORDER, $myrow["order_no"]), "nowrap align='right'");
			
			
            //label_cell($myrow["item_code"]);
			label_cell($myrow['requisition_no']);
			label_cell($myrow['reference']);
			label_cell(sql2date($myrow['ord_date']));
			view_purchase_stock_status_cell($myrow["item_code"]);
            label_cell($myrow["description"]);
            //label_cell(sql2date($myrow["delivery_date"]));
            $dec = get_qty_dec($myrow["item_code"]);
            qty_cell($myrow["quantity_ordered"], false, $dec);
            $manual_quantity_received=get_manual_received_qunatity($myrow["po_detail_item"]);
            qty_cell($manual_quantity_received, false, $dec);
            qty_cell($myrow["quantity_received"], false, $dec);
            if ($supp_trans->trans_type == ST_SUPPINVOICE)
            	qty_cells(null, 'this_quantity_inv'.$n, number_format2($myrow["quantity_ordered"] - $myrow["quantity_received"], $dec), 
            		null, null, $dec);
            else
            	qty_cells(null, 'This_QuantityCredited'.$n, number_format2(max($myrow["qty_invoiced"], 0), $dec), 
            		null, null, $dec);
            $dec2 = 0;
            if ($supp_trans->trans_type == ST_SUPPINVOICE)
            {
            	amount_cells(null, 'ChgPrice'.$n, price_decimal_format($myrow["unit_price"], $dec2), null, null, $dec2,true);
            	label_cell(($myrow["discount_percent"])."%", $dec2);
            	amount_cell(round2((1-$myrow["discount_percent"]/100)*$myrow["unit_price"] * ($myrow["quantity_ordered"] - $myrow["quantity_received"]), user_price_dec()));
            }
            else
            {
            	amount_cells(null, 'ChgPrice'.$n, price_decimal_format($myrow["act_price"], $dec2), null, null, $dec2);
            	amount_cell(round2($myrow["act_price"] * max($myrow['quantity_inv'], 0), user_price_dec()));
            }	
            if ($supp_trans->trans_type == ST_SUPPINVOICE)
        		submit_cells('grn_item_id'.$n, _("Add"), '', _("Add to Invoice"), true);
        	else	
        		submit_cells('grn_item_id'.$n, _("Add"), '', _("Add to Credit Note"), true);
    		if (($supp_trans->trans_type == ST_SUPPINVOICE) && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE')) {	// Added 2008-10-18 by Joe Hunt. Special access rights needed.
        		submit_cells('void_item_id'.$n, _("Remove"), '', _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!"), true);
				submit_js_confirm('void_item_id'.$n,
					sprintf(_('You are about to remove all yet non-invoiced items from delivery line #%d. This operation also irreversibly changes related order line. Do you want to continue ?'), $n));
			}
			
			end_row();
    	}
		
		
    }
    return true;
}



function display_grn_items_for_pv(&$supp_trans, $mode=0,$total_gl=0)
{
	global $path_to_root;

    $ret = true;
    // if displaying in form, and no items, exit
    if (($mode == 2  || $mode == 3) && count($supp_trans->grn_items) == 0)
    	return 0;

	start_outer_table("style='border:1px solid #cccccc;' width='95%'");

	$heading2 = "";
	if ($mode == 1)
	{
		if ($supp_trans->trans_type == ST_SUPPINVOICE)
		{
			$heading = _("Items Received Yet to be Invoiced");
    		if ($_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	// Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
				$heading2 = _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!");
		}
		else
			$heading = _("Delivery Item Selected For Adding To A Supplier Credit Note");
	}
	else
	{
		if ($supp_trans->trans_type == ST_SUPPINVOICE)
			$heading = _("Received Items Charged on this Invoice");
		else
			$heading = _("Received Items Credited on this Note");
	}

	display_heading($heading);

	if ($mode == 1)
	{
		if ($supp_trans->trans_type == ST_SUPPCREDIT)
		{
			echo "</td>";
			date_cells(_("Received between"), 'receive_begin', "", null, 
				-user_transaction_days(), 0, 0, "valign=middle");
			date_cells(_("and"), 'receive_end', '', null, 1, 0, 0, "valign=middle");
			submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), true);
			echo "<td>";
		}

		if ($heading2 != "")
		{
			display_note($heading2, 0, 0, "class='overduefg'");
		}
		echo "</td><td width='10%' align='right'>";
		submit('InvGRNAll', _("Add All Items"), true, false,true);
	}

	end_outer_table(0, false);
	
	
	$supplier_curr_code = get_supplier_currency($supp_trans->supplier_id);
	
	

  	div_start('grn_items');
	start_table(TABLESTYLE, "width='95%'");
	if ($mode == 1)
	{
    	$th = array(_("S.No."),_("Delivery"), _("P.O."), _("Item"), _("Description"),
    		_("Received On"), _("Quantity Received"), _("Quantity Invoiced"),
    		_("Qty Yet To Invoice"), $supp_trans->tax_included ? _("Price after Tax") : _("Price before Tax"), 
    		_("Discount%"), _("Total"), "");
    	if (($supp_trans->trans_type == ST_SUPPINVOICE) && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	// Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
    		$th[] = "";
		if ($supp_trans->trans_type == ST_SUPPCREDIT)
		{
			$th[8] = _("Qty Yet To Credit");
		}
    }
    else
	{
	
	    if (!is_company_currency($supplier_curr_code)){
		$th = array(_("S.No."),_("Delivery"), _("Item"), _("Description"),
			_("Quantity"), _("FC RATE"), _("Discount%"), _("FC Value"), _("LC Rate"), _("Discount%"), _("LC Value"));
		}else{
		$th = array(_("S.No."),_("Delivery"), _("Item"), _("Description"),
			_("Quantity"), _("LC Rate"), _("Discount%"), _("LC Value"));
		}
	}

	table_header($th);
    $total_grn_value = 0;
    $i = $k = 0;
	$loc_total= 0;
    $loc_unit_price = 0;
	$total_qty = 0;
	$sl_no=1;
	if (count($supp_trans->grn_items) > 0)
	{
	      //ravi
	      $invoice_total= ($supp_trans->ov_amount + $supp_trans->ov_gst + $supp_trans->freight_cost 
+ $supp_trans->additional_charges + $supp_trans->packing_charges + $supp_trans->other_charges 
+ $supp_trans->freight_tax + $supp_trans->additional_tax + $supp_trans->packing_tax + $supp_trans->other_tax + $supp_trans->ov_roundoff)*$supp_trans->rate;

		$conversion_factor=($invoice_total+$total_gl)/($supp_trans->ov_amount*$supp_trans->rate);



    	foreach ($supp_trans->grn_items as $entered_grn)
    	{

    		alt_table_row_color($k);
			
			label_cell($sl_no);

			$grn_batch = get_grn_batch_from_item($entered_grn->id);
    		label_cell(get_trans_view_str(ST_SUPPRECEIVE, $grn_batch), "nowrap align='right'");
    		if ($mode == 1)
    		{
//				label_cell($entered_grn->id);
 				$row = get_grn_batch($grn_batch);
				label_cell(get_trans_view_str(ST_PURCHORDER, $row["purch_order_no"]), "nowrap align='right'"); // PO
			}	
			label_cell($entered_grn->item_code);
			label_cell($entered_grn->item_description);
            $dec = get_qty_dec($entered_grn->item_code);
            if ($mode == 1)
            {
           		label_cell(sql2date($row['delivery_date']));
 				qty_cell($entered_grn->qty_recd, false, $dec);
				qty_cell($entered_grn->prev_quantity_inv, false, $dec);
            }
			qty_cell(abs($entered_grn->this_quantity_inv), true, $dec);
			
			if (!is_company_currency($supplier_curr_code)){
			amount_decimal_cell($entered_grn->chg_price);
			amount_decimal_cell($entered_grn->discount_percent);
			amount_cell( round2($entered_grn->chg_price * abs($entered_grn->this_quantity_inv)*(1-$entered_grn->discount_percent/100) , user_price_dec()), true);
			}
			
			amount_decimal_cell($entered_grn->chg_price*$supp_trans->rate*$conversion_factor);
			amount_decimal_cell($entered_grn->discount_percent);
			amount_decimal_cell($entered_grn->chg_price * abs($entered_grn->this_quantity_inv)*(1-$entered_grn->discount_percent/100) *$supp_trans->rate*$conversion_factor);
			
			$loc_unit_price +=$entered_grn->chg_price*$supp_trans->rate;
		    $loc_total += round2(($entered_grn->chg_price * abs($entered_grn->this_quantity_inv)*(1-$entered_grn->discount_percent/100)) *$supp_trans->rate*$conversion_factor , user_price_dec()); 
			
			if ($mode == 1)
			{
				delete_button_cell("Delete" . $entered_grn->id, _("Edit"), _('Edit document line'));
				if (($supp_trans->trans_type == ST_SUPPINVOICE) && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	  
					label_cell("");
			}	
			end_row();

    		$total_grn_value += round2($entered_grn->chg_price * abs($entered_grn->this_quantity_inv)*(1-$entered_grn->discount_percent/100),
			   user_price_dec());   
			   
			$total_qty += $entered_grn->this_quantity_inv;

    		$i++;
			$sl_no++;
    		if ($i > 15)
    		{
    		 	$i = 0;
    		 	table_header($th);
    		}
    	}
    }
	if ($mode == 1)
	{
		$ret = display_grn_items_for_selection($supp_trans, $k);
		if (!is_company_currency($supplier_curr_code)){
    	$colspan = 10;
		}
		else{
		$colspan = 7;
		}
	}
	else
		//$colspan = 6;
	label_cells(_("Total"), number_format2($total_qty,3),
		"colspan=4 align=right", "nowrap align=right");		
		
	
	if (!is_company_currency($supplier_curr_code)){	
	label_cells(_("Total"), price_format($total_grn_value),
		"colspan=2 align=right", "nowrap align=right");
	}
	
	
	// amount_cell($loc_unit_price);
	label_cell("");
	label_cell("");
	amount_cell($loc_total);
	
	
	
	if (!$ret)
	{
		start_row();
		echo "<td colspan=".($colspan + 1).">";
		if ($supp_trans->trans_type == ST_SUPPINVOICE)
			display_note(_("There are no outstanding items received from this supplier that have not been invoiced by them."), 0, 0);
		else
		{
			display_note(_("There are no received items for the selected supplier that have been invoiced."));
			display_note(_("Credits can only be applied to invoiced items."), 0, 0);
		}
		echo "</td>";
		end_row();
	}	
    end_table(1);
    div_end();

	return $total_grn_value;
}


//supplier direct credit note  START # Ramesh
function display_direct_credit_items(&$order, $editable=true)
{

    display_heading(_("Credit Items"));
    div_start('items_table');
    start_table(TABLESTYLE, "width='80%'");

	//$new = $order->order_no == 0;
if($order->trans_type != ST_SUPPINVOICE){
   	$th = array( _("S.No."), _("Item Code"), _("Item Description"), _("Quantity"), 
   		_("Unit"),  $order->tax_included ? _("Price after Tax") : _("Price before Tax"), _("Net Amount"), _("Discount %"), _("Discount Amount"), _("Line Total"), "");
		}else{
			$th = array( _("S.No."), _("Item Code"), _("Item Description"), _("Quantity"), 
   		_("Unit"), _("Required Delivery Date"), $order->tax_included ? _("Price after Tax") : _("Price before Tax"), _("Net Amount"), _("Discount %"), _("Discount Amount"),_('Dimension'), _("Line Total"), "");
		}

	if ($order->trans_type != ST_PURCHORDER)
		//array_remove($th, 5);
	if ($new)
		array_remove($th, 4);

	if (count($order->grn_items)) $th[] = '';

   	table_header($th);

	$id = find_submit('Edit');
	$total = 0;
	$k = 0;
	$sl_no=1;
	
   	foreach ($order->grn_items as $line_no => $po_line)
   	{
	

		$line_total = round(($po_line->this_quantity_inv * $po_line->order_price)-($po_line->this_quantity_inv * $po_line->order_price * ($po_line->discount_percent/100)), user_price_dec()); 
		
    	$net_amount =	round($po_line->this_quantity_inv * $po_line->order_price,  user_price_dec());
		
    	if (!$editable || ($id != $line_no))
		{
		  		alt_table_row_color($k);
			
        	//label_cell($po_line->item_code);
			
			label_cell($sl_no);
			
			view_purchase_stock_status_cell($po_line->item_code);
			
    		$item_info = get_item_edit_info($po_line->item_code);
			
			
			
			label_cell($item_info['description']);
            	qty_cell($po_line->this_quantity_inv, false, get_qty_dec($po_line->item_code));
			
    		label_cell($item_info['units']);
			
				
    		amount_decimal_cell($po_line->order_price);
			
			amount_cell($net_amount);
            
			percent_cell($po_line->discount_percent);
			
			
			
			amount_decimal_cell($po_line->disc_amount);
			
			amount_cell($line_total);
			
			
			if ($editable)
			{
					edit_button_cell("Edit$line_no", _("Edit"),
					  _('Edit document line'));
					delete_button_cell("Delete$line_no", _("Delete"),
						_('Remove line from document'));
			}
		end_row();
		}
		else{
		credit_item_controls($order, $k, $line_no);
		}
		
		
		$total += $line_total;
		$sl_no++;
    }

	if ($id==-1 && $editable)
	
		credit_item_controls($order, $k);
	

	$colspan = count($th)-2;
	if (count($order->grn_items))
		$colspan--;

	$display_sub_total = price_format($total);

	label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan align=right","align=right", 2);
	
	
	
	$taxes = $order->get_taxes(input_num('freight_cost'),false,input_num('additional_charges'),input_num('packing_charges'),input_num('other_charges'));
	
	//$tax_total = display_edit_tax_items($taxes, $colspan, $order->tax_included, 2, $order->trans_type==ST_SUPPINVOICE);
	
	$tax_total = display_edit_tax_items($taxes, $colspan, $order->tax_included, 2);

	$display_total = price_format(($total + input_num('freight_cost') + $tax_total + input_num('additional_charges') + input_num('packing_charges') + input_num('other_charges')));
	
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

	end_table(1);
	div_end();
}
function credit_item_controls(&$order, &$rowcounter, $line_no=-1)
{

   global $Ajax, $SysPrefs;

	alt_table_row_color($rowcounter);
	$new = $order->order_no == 0;

	$dec2 = 0;
	$id = find_submit('Edit');
	if (($id != -1) && $line_no == $id)
	{
	
        $item_info = get_item_edit_info($order->grn_items[$id]->item_code);
		$_POST['stock_id'] = $order->grn_items[$id]->item_code;
		$dec = get_qty_dec($_POST['stock_id']);
		$_POST['qty'] = qty_format($order->grn_items[$id]->this_quantity_inv, $_POST['stock_id'], $dec);
		$_POST['price'] = price_decimal_format($order->grn_items[$id]->order_price, $dec2);
		
		
		$_POST['discount_percent'] = ($order->grn_items[$id]->discount_percent);
		$_POST['disc_amount'] = $order->grn_items[$id]->disc_amount;
		
		
		

		$_POST['units'] = $item_info['units'];
		$_POST['item_description'] = $order->grn_items[$id]->item_description;
		$_POST['line_dimension'] = $order->grn_items[$id]->line_dimension;

		hidden('stock_id', $_POST['stock_id']);
		
		label_cell($sl_no);
		label_cell($_POST['stock_id']);
		
		if ($order->grn_items[$id]->descr_editable)
			text_cells(null,'item_description', null, 45, 150);
		else {
			hidden('item_description', $_POST['item_description']);
			label_cell($_POST['item_description']); 
		}

	    $Ajax->activate('items_table');
		$qty_rcvd = $order->grn_items[$id]->qty_received;
	}
	else
	{
	
	    label_cell($sl_no);

		// Manufactured item can be purchased
		if ($order->fixed_asset)
			stock_purchasable_fa_list_cells(null, 'stock_id', null, true, true, false, true, $order->line_items);
		else
			stock_items_list_cells(null, 'stock_id', null, true, true, false, true, array('editable' => 30, 'where'=>array("NOT no_purchase")));

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
   		$_POST['qty'] =	number_format2(get_purchase_conversion_factor ($order->supplier_id, $_POST['stock_id']), $dec);
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
	if ($order->trans_type == ST_PURCHORDER)
		date_cells(null, 'req_del_date','', null, 0, 0, 0);
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
	
	
	 //$disc_amount=percent_format((get_post('qty')*get_post('price'))*($_POST['discount_percent']/100));
		
	 //amount_decimal_cell($disc_amount, false, '','disc_amount');
	 
	 small_amount_cells(null, 'disc_amount', null, null, null, $dec2,true);
	if($order->trans_type == ST_SUPPINVOICE){
	if (get_company_pref('use_dimension'))
		dimensions_list_cells(_(''), 'line_dimension', null, true, _('Default'), false, 1);
	
		}

	$line_total = input_num('qty') * input_num('price') * (1 - input_num('discount_percent') / 100);
	amount_cell($line_total, false, '','line_total');

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
//supplier direct credit note  END # Ramesh


//ravi

function get_inv_po_items_for_selection(&$supp_trans,$supplier_id,$stock_id,$sap_search_no,$pur_ord_no)
{
	if ($supp_trans->trans_type == ST_SUPPINVOICE)	// outstanding grns and eventually for selected invoice
		$result = get_inv_po_items(0, $supplier_id, true, false, $supp_trans->trans_no,'','',$stock_id,$sap_search_no,$pur_ord_no);
	else
	{ // only invoiced
		if (isset($_POST['receive_begin']) && isset($_POST['receive_end']))
			$result = get_inv_po_items(0, $supplier_id, false, true, 0, $_POST['receive_begin'], $_POST['receive_end']);
		elseif ($supp_trans->src_docs)
			$result = get_inv_po_items(0, $supplier_id, false, true, array_keys($supp_trans->src_docs));
		else
			$result = get_inv_po_items(0, $supplier_id, false, true);
	}

    if (db_num_rows($result) == 0)
    {
    	return false;
    }

	$n=array();
    /*Set up a table to show the outstanding GRN items for selection */
    while ($myrow = db_fetch($result))
    {
		$grn_already_on_invoice = false;

    	foreach ($supp_trans->inv_items as $entered_grn)
    	{
    		if ($entered_grn->id == $myrow["po_detail_item"])
    		{
    			$grn_already_on_invoice = true;
    		}
    	}

    	if ($grn_already_on_invoice == false)
    	{
			array_push($n,$myrow["po_detail_item"]);
		}
	}
	
	return $n;
}


function display_inv_gl_new_accounts(&$supp_trans, $mode=0)
{



	global $path_to_root, $Ajax;

    // if displaying in form, and no items, exit
    if (($mode == 2 || $mode == 3) && count($supp_trans->gl_codes1) == 0)
    	return 0;

	

	div_start('gl_items');
	start_table(TABLESTYLE, "width='95%'");

	
	
    $th = array(_("Account"), _("Name"), _("Amount"), _("Memo"));

	if ($mode == 1)
	{
		$th[] = "";
	}	
	table_header($th);
	$total_gl_value=$total = 0;
	$i = $k = 0;

    start_row();
	label_cell("602032");
	label_cell("L.C.Commission");
	amount_cells(null, 'lc_commission_amount');
	text_cells_ex(null, 'lc_comm_memo', 35, 50, null, null, null);
	end_row();
	
	start_row();
	label_cell("602029");
	label_cell("Transport");
	amount_cells(null, 'transport_amount');
	text_cells_ex(null, 'transport_memo', 35, 50, null, null, null);
	end_row();
	
	
	start_row();
	label_cell("602009");
	label_cell("Freight Inward");
	amount_cells(null, 'freight_inward_amount');
	text_cells_ex(null, 'freight_inward_memo', 35, 50, null, null, null);
	end_row();
	
	start_row();
	label_cell("602013");
	label_cell("Demurrage");
	amount_cells(null, 'demurrage_amount');
	text_cells_ex(null, 'demurrage_memo', 35, 50, null, null, null);
	end_row();
	
	     start_row();
	label_cell("602011");
	label_cell("Insurance");
	amount_cells(null, 'insurance_amount');
	text_cells_ex(null, 'insurance_memo', 35, 50, null, null, null);
	end_row();
	
	start_row();
	label_cell("602033");
	label_cell("Unloading Charges");
	amount_cells(null, 'unloading_amount');
	text_cells_ex(null, 'unloading_memo', 35, 50, null, null, null);
	end_row();
	
	
	start_row();
	label_cell("602034");
	label_cell("Port Service Charges");
	amount_cells(null, 'port_ser_amount');
	text_cells_ex(null, 'port_ser_memo', 35, 50, null, null, null);
	end_row();
	
	start_row();
	label_cell("602035");
	label_cell("C.F Charges");
	amount_cells(null, 'cf_amount');
	text_cells_ex(null, 'cf_memo', 35, 50, null, null, null);
	end_row();
	
	start_row();
	label_cell("602006");
	label_cell("Customs Duty");
	amount_cells(null, 'cust_duty_amount');
	text_cells_ex(null, 'cust_duty_memo', 35, 50, null, null, null);
	end_row();
	
	start_row();
	label_cell("602036");
	label_cell("Others");
	amount_cells(null, 'others_amount');
	text_cells_ex(null, 'others_memo', 35, 50, null, null, null);
	end_row();
	
	start_row();
	label_cell("","colspan=3");
	submit_cells('gl_update', _("Update"), "width=10% colspan=2 align='right'", _("Refresh"), true);
	end_row();


	end_table(1);
	div_end();

	
}