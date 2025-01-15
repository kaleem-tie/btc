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
	
	/*
	if (strlen($_POST['modified_supp_name']) == 0 || $_POST['modified_supp_name'] == "") 
	{
		display_error(_("The supplier name must be entered."));
		set_focus('modified_supp_name');
		return false;
	}
	*/
	
	return true;
}


function display_bank_payment_items_process($trans_no)
{
	
	div_start('grn_items');
    start_table(TABLESTYLE, "colspan=7 width='80%'");
    $th = array(_("Account Code"), _("Account Description"), _("Amount"),
	_("Supplier Name Update"));
    table_header($th);
	
	$payment_items = get_gl_trans(ST_BANKPAYMENT, $trans_no);

     $k = 0;
    /*show the line items on the order with the quantity being received for modification */
		while ($item = db_fetch($payment_items))
		{
			
			if ($item["account"] != $receipt["account_code"])
			{
			
				$ind_result[] = $item;
				alt_table_row_color($k);
				
				$supp_info = get_supplier($item["person_id"]);
				
				if($item["account"]==get_company_Pref('creditors_act')){
				
				label_cell($supp_info['supp_code']);
				label_cell($supp_info['supp_name']);
				amount_cell($item["amount"]);
				
				$supp_trans = get_supp_trans($trans_no, $_POST['trans_type'], $item['person_id']);	
				
				if($supp_trans["modified_supp_name"]!='') {
				  $modified_supp_name = $supp_trans["modified_supp_name"];
				}
			    else{
				  $modified_supp_name = $supp_info['supp_name'];
				}
				
				text_cells(null,'modified_supp_name_'.$item['person_id'].'_1', $modified_supp_name, 45, 80);
				hidden('trans_no',$trans_no);
				hidden('person_id',$item["person_id"]);
                }
			}
			
		$k++; 
		}
    $_SESSION['IND'] = $ind_result;
   
     end_table();
	 div_end();
}


//-------------------------------------------------------------------------------------

if (isset($_POST['ADD_ITEM']) && can_process())
{
	foreach ($_SESSION['IND'] as $k => $items)
	{
		/*
		if($_POST['modified_supp_name_'.$items['person_id'].'_1']=='')
		{
			display_error("supplier name cannot be empty");	
			return false;
		}
		*/
		
     if($_POST['modified_supp_name_'.$items['person_id'].'_1']!=''){
	$payment_id = update_modified_supp_name_in_supp_trans($_POST['trans_type'],
	  $_POST['trans_no'],$_POST['modified_supp_name_'.$items['person_id'].'_1'],$items['person_id']);
	
	 $payment_id = add_cheque_modified_transactions($_POST['trans_type'],
	  $_POST['trans_no'],$_POST['modified_supp_name_'.$items['person_id'].'_1'],$items['person_id']);
	 }
	}
	display_notification(_('Cheque details updated..!'));
	
	$path="../inquiry/cheque_transactions_inquiry.php?";
	meta_forward($_SERVER['PHP_SELF'], "AddedID=$payment_id");
}

if (isset($_GET['AddedID'])) {
	
	$payment_id = $_GET['AddedID'];
	
	display_notification(_('Cheque details updated..!'));
	
	submenu_view(_("View this Bank Payment"), ST_BANKPAYMENT, $payment_id);

	submenu_print(_("&Print This Cheque Payment"), ST_BANKPAYMENT_REP, $payment_id, 'prtopt');
	
    submenu_option(_("Cheque Transactions Inquiry"), "purchasing/inquiry/cheque_transactions_inquiry.php?");
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


$result = get_bank_trans(ST_BANKPAYMENT, $_POST['trans_no']);	
$receipt = db_fetch($result);


div_start('delivery_table');
start_table(TABLESTYLE, "width='80%'");
$th = array(_("From Bank Account"), _("Date"), _("Amount"), _("Pay To"),
 _("Reference"), _("Cheque No"), _("Date Of Issue"));
table_header($th);
$k = 0; //row colour counter
global $delivery_times;


	alt_table_row_color($k);
	
	label_cell($receipt['bank_account_name']);
	label_cell(sql2date($receipt["trans_date"]),'align=center');
	label_cell(number_format2(-$receipt['amount'], user_price_dec()));
	label_cell(payment_person_name_for_reports($receipt["person_type_id"]));
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



end_table();

br();
display_heading(_("Items for this Payment"));

display_bank_payment_items_process($_POST['trans_no']);
br();
submit_center('ADD_ITEM', _("Submit"), true, '', 'default');
br();
	
	

end_form();
end_page();


