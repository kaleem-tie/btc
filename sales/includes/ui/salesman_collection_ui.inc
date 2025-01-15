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
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/sm_collection_cart.inc");

//--------------------------------------------------------------------------------
function add_to_order(&$order, $customer_id, $branch_id="", $invoice_no="", $amount=0) 
{
	if ($order->find_cart_item($customer_id,$branch_id,$invoice_no))
         display_error(_("For Customer :") . $invoice_no . " " . " This invoice reference is already on this document. You can change the invoice on the existing line if necessary.");
	else    
     $order->add_to_cart(count($order->line_items), $customer_id, $branch_id, $invoice_no,$amount);
}
//--------------------------------------------------------------------------------

function display_sm_collection_header(&$order)
{
	global $Refs, $Ajax, $SysPrefs;
	
	start_outer_table(TABLESTYLE2, "width='70%'"); // outer table
	table_section(1);
	
	
	//bank_accounts_list_row(_("Into Bank Account:"), 'bank_account', null, true);
	bank_accounts_list_trans_row(_("Into Bank Account:"), 'bank_account', null, true, false, true);
	
    date_row(_("Collected Date:"), 'date_', '', true);
	if ($_POST["ref_no"] > 0 ){
		$myrow = get_selsmancollection_header($_POST['ref_no'], ST_CUSTPAYMENT);
	      label_row(_("Reference:"), $myrow['reference']);
	      hidden('ref', $myrow['reference']); 
	}else {
		ref_row(_("Reference:"), 'ref','' , null, '', ST_CUSTPAYMENT,null,0,1);
	}
	text_row(_("Ref No :"), 'ref_no', $_POST["ref_no"], 20, 20, null, "", "", "readonly");
	table_section(2, "50%");
	
	
	
	sales_persons_list_row(_("Sales Person:"), 'sales_person_id',null,
    false,true);
	
	
	if (list_updated('bank_account') || list_updated('sales_person_id')){
	$Ajax->activate('_page_body');
    }
	

     if($_POST['bank_account']==1){
	label_row(_("Last Sales Person Ref No.(Cash):"),get_last_sales_person_payment_cash_ref($_POST['sales_person_id']));
    }else{
	label_row(_("Last Sales Person Ref No.(Cheque):"),get_last_sales_person_payment_cheque_ref($_POST['sales_person_id']));
    }
	
    text_row(_("Sales Person Ref No.<b style='color:red;'>*</b>"), 'sales_person_ref', null, 20, 20);
	
	
	if (isset($_GET['PdcNumber']) && $_GET['PdcNumber'] > 0 ){
	table_section(3, "50%");
	$pdc_cheque = get_customer_pdc_trans_cheque_details(ST_CUSTPDC,$_GET['PdcNumber']);
	
	label_row(_("PDC Reference:"),$pdc_cheque['reference']);
	label_row(_("Cheque Bank Name:"),$pdc_cheque['bank_name']);
	label_row(_("Cheque No.:"),$pdc_cheque['pdc_cheque_no']);
	label_row(_("Cheque Date:"),sql2date($pdc_cheque['pdc_cheque_date']));
	}
	else if(isset($_POST['PdcNumber']) && $_POST['PdcNumber'] > 0){
	table_section(3, "50%");
	$pdc_cheque = get_customer_pdc_trans_cheque_details(ST_CUSTPDC,$_POST['PdcNumber']);
	
	label_row(_("PDC Reference:"),$pdc_cheque['reference']);
	label_row(_("Cheque Bank Name:"),$pdc_cheque['bank_name']);
	label_row(_("Cheque No.:"),$pdc_cheque['pdc_cheque_no']);
	label_row(_("Cheque Date:"),sql2date($pdc_cheque['pdc_cheque_date']));
	}
   
	end_outer_table(1); // outer table
}

//---------------------------------------------------------------------------------

function display_sm_collection_items($title, &$order)
{
	global $path_to_root;

	display_heading($title);
    div_start('items_table');
	start_table(TABLESTYLE, "width='90%'");

	$th = array(_("Customer"), _("Branch"), _("Invoice Ref"), _("O/S Amount"), _("Amount"), "", "");
		
	
	table_header($th);

	$k = 0;  //row colour counter
	$id = get_post('selected_id', find_submit('Edit'));
	$total_price=0;
	foreach ($order->line_items as $line_no=>$ts_item)
	{
		if ($id != $line_no)
		{
			alt_table_row_color($k);
			
			
			$customer_name = get_customer_name($ts_item->customer_id);
			$branch_name = get_branch_name($ts_item->branch_id);
			//$invoice_os_amt = get_sales_invoice_number($ts_item->invoice_no);
			//display_error("trans Type = ".$ts_item->trans_type." No = ".$ts_item->trans_no."Invoiceno = ".$ts_item->invoice_no);
			$invoice_os_amt = get_sales_invoice_trans_number($ts_item->invoice_no,$ts_item->trans_type,$ts_item->trans_no, $ts_item->customer_id);			
			label_cell($customer_name);
			label_cell($branch_name);
			
			if($ts_item->invoice_no!=0)
			{
				label_cell($ts_item->invoice_no);
				amount_cell($invoice_os_amt);
			}
			else
			{
				label_cell("");
				label_cell("");
			}
			amount_cell($ts_item->amount);
			
					
			$total_price += $ts_item->amount;
	
			if ($id == -1 && !isset($_GET['PdcNumber']))
			{
				edit_button_cell("Edit$line_no", _('Edit'), _('Edit line'));
				delete_button_cell("Delete$line_no", _("Delete"), _('Remove line from document'));
			}
        	else
        		label_cell('');
			
			end_row();
		}
		else
		{
			sm_collection_edit_item_controls($order, $line_no);  //ravi
		}
	}

	if ($id == -1 && !isset($_GET['PdcNumber']))
	{ 
		sm_collection_edit_item_controls($order); //ravi
	}
		
	start_row();
	label_cells(_("Amount Total"), price_format($total_price), "colspan=4 align=right",
	"align=right");
	end_row();	
	
	
    end_table();

	if ($id != -1)
	    hidden('selected_id', $id);
	div_end();
}

//---------------------------------------------------------------------------------

function sm_collection_edit_item_controls(&$order, $line_no=-1)
{
	global $Ajax;
	start_row();

	$dec2 = 0;
    $id = find_submit('Edit');
	
	if ($line_no != -1 && $line_no == $id)
	{
		$_POST['trans_no']		  = $order->line_items[$line_no]->trans_no; // by kadar ali
	    $_POST['customer_id']     = $order->line_items[$line_no]->customer_id;
		$_POST['branch_id']       = $order->line_items[$line_no]->branch_id;
		$_POST['invoice_no']      = $order->line_items[$line_no]->invoice_no;
		$_POST['amount']          = price_format($order->line_items[$id]->amount);
		
	    $Ajax->activate('items_table');
	}
	// else
	// {
		
		customer_list_cells(null, 'customer_id', null, false, true, false, true);

		if ($order->customer_id != get_post('customer_id', -1))
		{
			// customer has changed
			$Ajax->activate('branch_id');
			$Ajax->activate('invoice_no');
			$Ajax->activate('items_table');
  		}
		
		customer_branches_list_cells(null,
	  	  $_POST['customer_id'], 'branch_id', null, false, true, true, true);
		
		slales_invoice_list_cells(null,'invoice_no', null, _("Select a invoice"), true, $_POST['customer_id'], 
		$_POST['branch_id']);
		
		if (get_post('invoice_no')!='')
		{
			$Ajax->activate('invoice_os_amt');
			$Ajax->activate('items_table');
  		}
		
		$invoice_os_amt = 0;
		if ($line_no === -1) {
			$invoice_os_amt = get_sales_invoice_number($_POST['invoice_no']);
		}
		else {
			$invoice_os_amt = get_sales_invoice_trans_number($_POST['invoice_no'], $order->line_items[$line_no]->trans_type, $order->line_items[$line_no]->trans_no);
		}

		label_cell(number_format2($invoice_os_amt, user_price_dec()), '', 'invoice_os_amt');
	
	    amount_cells(null, 'amount');
		

	if ($line_no != -1)
	{
		button_cell('UpdateItem', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
		hidden('LineNo', $line_no);
		hidden('trans_no', $_POST['trans_no']);
		set_focus('qty');
	}
	else
	{
		submit_cells('AddItem', _("Add Item"), "colspan=2",
		    _('Add new item to document'), true);
	}

	end_row();
}

//---------------------------------------------------------------------------------

function sm_collection_controls(&$order)
{
	  echo "<br>";
	  start_table();
	  
	
	  
	  textarea_row(_("Memo"), 'memo_', $_POST["memo_"], 50, 3);
      
	  
	  end_table(1);
}


