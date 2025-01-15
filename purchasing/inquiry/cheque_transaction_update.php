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
$page_security = 'SA_CHEQUE_TRANS_INQ';
$path_to_root = "../..";
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Cheque Transaction Update"), false, false, "", $js);

if (isset($_GET['trans_type']))
{
	$_POST['trans_type'] = $_GET['trans_type'];
}

if (isset($_GET['trans_no']))
{
	$_POST['trans_no'] = $_GET['trans_no'];
}

//---------------------------------------------------------------------------------------

if (!isset($_POST['date_']))
{
	$_POST['date_'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['date_']))
		$_POST['date_'] = end_fiscalyear();
}

function can_process()
{
	global $selected_id, $SysPrefs;
	
	if (strlen($_POST['modified_supp_name']) == 0 || $_POST['modified_supp_name'] == "") 
	{
		display_error(_("The supplier name must be entered."));
		set_focus('modified_supp_name');
		return false;
	}
	
	return true;
}

//-------------------------------------------------------------------------------------

if (isset($_POST['ADD_ITEM']) && can_process())
{

	$payment_id = update_modified_supp_name_in_supp_trans($_POST['trans_type'],
	$_POST['trans_no'],$_POST['modified_supp_name'],$_POST['supplier_id']);
	
	$payment_id = add_cheque_modified_transactions($_POST['trans_type'],
	$_POST['trans_no'],$_POST['modified_supp_name'],$_POST['supplier_id']);
	
	display_notification(_('Cheque details updated..!'));
	
	$trans_type = $_POST['trans_type'];
	
    $path="../inquiry/cheque_transactions_inquiry.php?";
	//meta_forward($_SERVER['PHP_SELF'], "AddedID=$payment_id&&$trans_type");
	
	if ($_POST['trans_type'] == ST_SUPPPDC)
	meta_forward($_SERVER['PHP_SELF'], "AddedPdc=$payment_id&&$trans_type");
   else
	meta_forward($_SERVER['PHP_SELF'], "AddedPAY=$payment_id&&$trans_type");
}


if (isset($_GET['AddedPdc'])) {
	
	$payment_id = $_GET['AddedPdc'];
	
	$trans_type = 6;
	
	display_notification(_('Cheque details updated..!'));
	
	submenu_view(_("View this PDC"), ST_SUPPPDC, $payment_id);
	submenu_print(_("&Print This PDC Cheque"), ST_SUPPPDC_REP, $payment_id."-".$trans_type, 'prtopt');
	submenu_option(_("Cheque Transactions Inquiry"), "purchasing/inquiry/cheque_transactions_inquiry.php?");
	display_footer_exit();

}
	
	
if (isset($_GET['AddedPAY'])) {
	
	$payment_id = $_GET['AddedPAY'];
	
	$trans_type = 22;
    display_notification(_('Cheque details updated..!'));
	submenu_view(_("View this Supplier Payment"), ST_SUPPAYMENT, $payment_id);
	submenu_option(_("Cheque Transactions Inquiry"), "purchasing/inquiry/cheque_transactions_inquiry.php?");
	submenu_print(_("&Print This Cheque Payment"), ST_SUPPAYMENT_REP_TWO, $payment_id."-".$trans_type, 'prtopt');
	display_footer_exit();

	
}


//-------------------------------------------------------------------------------------

if (get_post('_type_update')) 
{
  $Ajax->activate('_page_body');
}
//-------------------------------------------------------------------------------------


start_form();

start_table(TABLESTYLE2);

$existing_comments = "";
$dec = 0;


hidden('trans_type',$_GET['trans_type']);
hidden('trans_no',$_GET['trans_no']);

if($_POST['trans_type']==ST_SUPPAYMENT){
	
  $receipt = get_supp_trans($_POST['trans_no'], ST_SUPPAYMENT);


div_start('delivery_table');
start_table(TABLESTYLE, "width='80%'");
$th = array(_("To Supplier"), _("From Bank Account"), _("Date Paid"),
_("Amount LC"), _("Rerefence"), _("Cheque No"), _("Date Of Issue"));
table_header($th);
$k = 0; //row colour counter
global $delivery_times;


	alt_table_row_color($k);
	if($receipt['modified_supp_name']!=''){
		label_cell($receipt['modified_supp_name']);
	}
    else{	
	label_cell($receipt['supplier_name']);
	}
	label_cell($receipt['bank_account_name']);
	label_cell(sql2date($receipt["tran_date"]),'align=center');
	label_cell(number_format2(-$receipt['bank_amount'], user_price_dec()));
	label_cell($receipt['ref']);
	label_cell($receipt['cheque_no']);
	label_cell(sql2date($receipt["date_of_issue"]),'align=center');
    end_row();

if($receipt['modified_supp_name']!=''){
	$_POST['modified_supp_name'] = $receipt['modified_supp_name'];
}
else{	
   $_POST['modified_supp_name'] = $receipt['supplier_name'];
}

hidden('supplier_id',$receipt['supplier_id']);

end_table();
}
else if($_POST['trans_type']==ST_SUPPPDC){
	
	$receipt = get_supp_trans($_POST['trans_no'], ST_SUPPPDC);
	
	div_start('delivery_table');
start_table(TABLESTYLE, "width='80%'");
$th = array(_("To Supplier"), _("From Bank Account"), _("Date Paid"),
_("Amount"), _("Rerefence"), _("PDC No."), _("PDC Date"));
table_header($th);
$k = 0; //row colour counter
global $delivery_times;


	alt_table_row_color($k);
	if($receipt['modified_supp_name']!=''){
		label_cell($receipt['modified_supp_name']);
	}
    else{	
	label_cell($receipt['supplier_name']);
	}
	$bank_details=get_supp_bank_details($receipt['bank_account']);
	label_cell($bank_details['bank_account_name']);
	label_cell(sql2date($receipt["tran_date"]),'align=center');
	label_cell(number_format2(-$receipt['Total'], user_price_dec()));
	label_cell($receipt['reference']);
	label_cell($receipt['pdc_cheque_no']);
	label_cell(sql2date($receipt["pdc_cheque_date"]),'align=center');
    end_row();

if($receipt['modified_supp_name']!=''){
	$_POST['modified_supp_name'] = $receipt['modified_supp_name'];
}
else{	
   $_POST['modified_supp_name'] = $receipt['supplier_name'];
}

hidden('supplier_id',$receipt['supplier_id']);

end_table();
	
	
}	


start_table(TABLESTYLE2);
br(2);
text_row(_("Supplier Name Update:"), 'modified_supp_name', null, 60, 150);

end_table();
br();
submit_center('ADD_ITEM', _("Submit"), true, '', 'default');
br();
	
	

end_form();
end_page();


