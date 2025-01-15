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
$page_security = 'SA_INVMULTIPLE';
$path_to_root = "..";
include_once($path_to_root . "/purchasing/includes/po_inv_class.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
//----------------------------------------------------------------------------------------
if(list_updated('Location')){
	
	$Ajax->activate('reference');
}

if (isset($_GET['NewINVM']))
{
	if (isset( $_SESSION['inv_trans']))
	{
		unset ($_SESSION['inv_trans']->inv_items);
		unset ($_SESSION['inv_trans']->gl_codes1);
		unset ($_SESSION['inv_trans']);
	}
	$help_context = "Direct Invoice Entry";
	$_SESSION['page_titles'] = _("Invoice Against Multiple Purchase Orders");
	//session_start();

	$_SESSION['inv_trans'] = new po_inv(ST_SUPPINVOICE);
	$_SESSION['inv_trans']->trans_type = ST_SUPPINVOICE;
		$_SESSION['inv_trans']->trans_no = 0;
} 
page($_SESSION['page_titles'], false, false, "", $js);


check_db_has_suppliers(_("There are no suppliers defined in the system."));

//---------------------------------------------------------------------------------------------------------------
function reset_tax_input()
{
	global $Ajax;

	unset($_POST['mantax']);
	$Ajax->activate('inv_tot');
}
if (isset($_POST['AddGLCodeToTrans'])){

	$Ajax->activate('gl_items');
	$Ajax->activate('_page_body');
	$input_error = false;

	$result = get_gl_account_info($_POST['gl_code']);
	if (db_num_rows($result) == 0)
	{
		display_error(_("The account code entered is not a valid code, this line cannot be added to the transaction."));
		set_focus('gl_code');
		$input_error = true;
	}
	else
	{
		$myrow = db_fetch_row($result);
		$gl_act_name = $myrow[1];
		if (!check_num('amount'))
		{
			display_error(_("The amount entered is not numeric. This line cannot be added to the transaction."));
			set_focus('amount');
			$input_error = true;
		}
	}

	if (!is_tax_gl_unique(get_post('gl_code'))) {
   		display_error(_("Cannot post to GL account used by more than one tax type."));
		set_focus('gl_code');
   		$input_error = true;
	}

	if ($input_error == false)
	{
		
		$_SESSION['inv_trans']->add_gl_codes_to_trans1($_POST['gl_code'], $gl_act_name,
			$_POST['dimension'], $_POST['dimension2_id'], 
			input_num('amount'), $_POST['memo_']);
		reset_tax_input();
		unset($_POST['amount']);
		unset($_POST['gl_code']);
		set_focus('gl_code');
	}
}


function check_reference_data(){
	$input_error = 0;
	if(get_post('pur_ord_no') == ''){
		display_error("Please enter po reference");
		$input_error = 1;
	}
	if(get_post('pur_ord_no')){
		$ref_exists = get_po_ref_exists($_POST['pur_ord_no'],$_POST['supplier_id']);
		if($ref_exists == ''){
			display_error("Please enter correct po reference number or this reference is not related to selected supplier");
			$input_error = 1;
		}
	}
	return $input_error;
}

if (isset($_GET['AddedID']))
{
	$invoice_no = $_GET['AddedID'];
	$trans_type = ST_SUPPINVOICE;


    echo "<center>";
    display_notification_centered(_("Supplier invoice has been processed."));
    display_note(get_trans_view_str($trans_type, $invoice_no, _("View this Invoice")));

	display_note(get_gl_view_str($trans_type, $invoice_no, _("View the GL Journal Entries for this Invoice")), 1);

	hyperlink_params("$path_to_root/purchasing/supplier_payment.php", _("Entry supplier &payment for this invoice"),
		"PInvoice=".$invoice_no."&trans_type=".$trans_type);

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another Invoice"), "NewINVM=Yes");

	hyperlink_params("$path_to_root/admin/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$invoice_no");
	
	display_footer_exit();
}
function check_data() {
	if(count($_SESSION['inv_trans']->inv_items) == '0'){
	display_error(_("Please Select Alteast One item"));
return false;	
	}
	return true;
}

function handle_commit_invoice()
{
	
	 	
	if (!get_post('Location')) 
	{
		display_error(_("There is no location selected."));
		set_focus('Location');
		return false;
	} 
	
	
	copy_to_trans($_SESSION['inv_trans']);
	
	
	if (!check_data())
		return;
	$grn = $_SESSION['inv_trans'];
	//display_error(json_encode($grn)); die;
	$grn_no = add_direct_grn($grn,'Direct');
	
	
	$inv_no = add_direct_supp_invoice($grn);

    $_SESSION['inv_trans']->clear_po_items();
    unset($_SESSION['inv_trans']);

	meta_forward($_SERVER['PHP_SELF'], "AddedID=$inv_no");
	
	return true;
}

//--------------------------------------------------------------------------------------------------

if (isset($_POST['PostInvoice']))
{
	handle_commit_invoice();
}
function commit_item_data($n)
{
	

	//if (check_item_data($n))
	//{
		
			//display_error(json_encode($_POST)); 
			$_SESSION['inv_trans']->add_grn_to_tran_inv($n, $_POST['po_detail_item'.$n],
			$_POST['item_code'.$n], $_POST['item_description'.$n], $_POST['qty_recd'.$n],
			$_POST['prev_quantity_inv'.$n], input_num('this_quantity_inv'.$n),
			$_POST['order_price'.$n], input_num('ChgPrice'.$n), $complete,
	$_POST['std_cost_unit'.$n], "",$_POST['discount_percent'.$n],$_POST['sap_no'.$n],$_POST['our_ord_no'.$n],$_POST['supplier_date'.$n]);
			
			
		reset_tax_input();
	//}
}
$id = find_submit('grn_item_id');

if ($id != -1)
{
commit_item_data($id);	
}

if(isset($_POST['SelectAll']))
{
	$all_items=get_inv_po_items_for_selection($_SESSION['inv_trans'],$_POST['supplier_id'],$_POST['stock_id'],$_POST['sap_search_no'],$_POST['pur_ord_no']);
	
	for($i=0;$i<count($all_items);$i++)
	{
	$_SESSION['inv_trans']->add_grn_to_tran_inv($all_items[$i], $_POST['po_detail_item'.$all_items[$i]],
			$_POST['item_code'.$all_items[$i]], $_POST['item_description'.$all_items[$i]], $_POST['qty_recd'.$all_items[$i]],
			$_POST['prev_quantity_inv'.$all_items[$i]], input_num('this_quantity_inv'.$all_items[$i]),
			$_POST['order_price'.$all_items[$i]], input_num('ChgPrice'.$all_items[$i]), $complete,
	$_POST['std_cost_unit'.$all_items[$i]], "",$_POST['discount_percent'.$all_items[$i]],$_POST['sap_no'.$all_items[$i]],$_POST['our_ord_no'.$all_items[$i]],$_POST['supplier_date'.$all_items[$i]]);	
	}
}


if (isset($_POST['InvGRNAlls']))
{
	//if(check_reference_data()){
   $Ajax->activate('grn_items1');
	//}
}
$id3 = find_submit('Delete');

if ($id3 != -1)
{
	$_SESSION['inv_trans']->remove_po_from_trans($id3);
	$Ajax->activate('grn_items1');
	reset_tax_input();
}
$id4 = find_submit('Delete2');

if ($id4 != -1)
{
	
	$_SESSION['inv_trans']->remove_gl_codes_from_trans1($id4);
	//clear_po_items();
	reset_tax_input();
	$Ajax->activate('gl_items');
}

$id5 = find_submit('Edit');
if ($id5 != -1)
{
    $_POST['gl_code'] = $_SESSION['inv_trans']->gl_codes1[$id5]->gl_code;
    $_POST['dimension_id'] = $_SESSION['inv_trans']->gl_codes1[$id5]->gl_dim;
    $_POST['dimension2_id'] = $_SESSION['inv_trans']->gl_codes1[$id5]->gl_dim2;
    $_POST['amount'] = $_SESSION['inv_trans']->gl_codes1[$id5]->amount;
    $_POST['memo_'] = $_SESSION['inv_trans']->gl_codes1[$id5]->memo_;

       $_SESSION['inv_trans']->remove_gl_codes_from_trans1($id5);
       reset_tax_input();
       $Ajax->activate('gl_items');
}

//---------------------------------------------------------------------------------------------------------------
start_form();

invoice_mheader($_SESSION['inv_trans'],$_POST['sap_search_no'],$_POST['pur_ord_no']);

if ($_POST['supplier_id']=='') 
		display_error(_("There is no supplier selected."));
else {
	
	$po_total=display_inv_po_items($_SESSION['inv_trans'], 1,$_POST['supplier_id'],$_POST['stock_id'],$_POST['sap_search_no'],$_POST['pur_ord_no']);
	
	
	$_SESSION['inv_trans']->items_total = $po_total;
	
    $_SESSION['inv_trans']->items_total_inc_tax = get_inv_trans_total($_SESSION['inv_trans']);

	display_gl_inv_items($_SESSION['inv_trans'], 1);
	
	
	display_inv_gl_new_accounts($_SESSION['inv_trans'], 1);

		
	div_start('inv_tot');
	inv_item_totals($_SESSION['inv_trans'],$po_total);
	div_end();

}
function item_search(){
	
	
	echo "<tr>";
	stock_items_list_cells(null, 'stock_id', null, _("Select Item"), true, false, true);
	text_cells(_("Supplier Reference"),'sap_search_no', null, 20, 150);
	text_cells(_("PO Ref No"),'pur_ord_no', null, 20, 150);
	 
		echo "<td width='10%' align='right'>";
		submit('InvGRNAlls', _("Search"), true, false,false);
		echo "<td width='10%' align='right'>";
		submit('SelectAll', _("Select All"), true, false,false);
		echo "</tr>";
	
	 
}

//-----------------------------------------------------------------------------------------
if ($id != -1 || $id2 != -1)
{
	
	$Ajax->activate('grn_items1');
	$Ajax->activate('inv_tot');
}

if (get_post('update'))
	$Ajax->activate('inv_tot');

br();
submit_center('PostInvoice', _("Enter Invoice"), true, '', 'default');
br();

end_form();

//--------------------------------------------------------------------------------------------------

end_page();
