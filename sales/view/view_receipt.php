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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 600);

page(_($help_context = "View Customer Payment"), true, false, "", $js);

if (isset($_GET["trans_no"]))
{
	$trans_id = $_GET["trans_no"];
}
if (isset($_GET["ref_no"]))
{
	$ref_no = $_GET["ref_no"];
}

if($ref_no>0){
$receipt = get_customer_trans_ref_no($trans_id, ST_CUSTPAYMENT,null,$ref_no);
}
else
{
	$receipt = get_customer_trans($trans_id, ST_CUSTPAYMENT);
}


$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

display_heading(sprintf(_("Customer Payment #%d"),$trans_id==0? $ref_no:$trans_id));
echo "<br>";


start_table(TABLESTYLE, "width='80%'");
start_row();
//label_cells(_("From Customer"), $receipt['DebtorName'], "class='tableheader2'");

label_cells(_("Reference"), $receipt['reference'], "class='tableheader2'");
label_cells(_("Date of Deposit"), sql2date($receipt['tran_date']), "class='tableheader2'");


end_row();
start_row();
//label_cells(_("Customer Currency"), $receipt['curr_code'], "class='tableheader2'");

end_row();
start_row();
label_cells(_("Amount"), price_format($receipt['Total'] - $receipt['ov_discount']), "class='tableheader2'");
label_cells(_("Into Bank Account"), $receipt['bank_account_name'].' ['.$receipt['bank_curr_code'].']', "class='tableheader2'");


//label_cells(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']], "class='tableheader2'");

end_row();


start_row();
label_cells(_("Bank Amount"), price_format($receipt['bank_amount']), "class='tableheader2'");
label_cells(_("Our Reference No."), $receipt['pt_our_ref_no'], "class='tableheader2'");


end_row();


start_row();
label_cells(_("Discount"), price_format($receipt['ov_discount']), "class='tableheader2'");
label_cells(_("Sales Person"), get_salesman_name($receipt["sales_person_id"]), "class='tableheader2'");

	
end_row();


start_row();
label_cells(_("Sales Person Ref No."), $receipt['sales_person_ref'], "class='tableheader2'");
label_cells(_("Mode Of Payment"), $mode_payment_types[$receipt['mode_of_payment']], "class='tableheader2'");	
end_row();
start_row();
$preared_user = get_transaction_prepared_by(ST_CUSTPAYMENT, $trans_id);
label_cells(_("Prepared By"), $preared_user, "class='tableheader2'");
// label_cells(_("Mode Of Payment"), $mode_payment_types[$receipt['mode_of_payment']], "class='tableheader2'");	
end_row();

start_row();
if($receipt['mode_of_payment']=='cheque')
{
	label_cells(_("Cheque No"), $receipt['cheque_no'], "class='tableheader2'");
	label_cells(_("Date Of Issue"), $receipt['date_of_issue'], "class='tableheader2'");
}else if($receipt['mode_of_payment']=='dd'){
	label_cells(_("DD No"), $receipt['dd_no'], "class='tableheader2'");
	label_cells(_("Date Of Issue"), $receipt['dd_date_of_issue'], "class='tableheader2'");
}

 else if($receipt['mode_of_payment'] == 'ot' || $receipt['mode_of_payment'] == 'neft' || $receipt['mode_of_payment'] == 'card' || $receipt['mode_of_payment'] == 'rtgs'){
	 
	 if($receipt['mode_of_payment'] == 'card'){
	label_cells(_("Card Last 4 Digits"), $receipt['pymt_ref'], "class='tableheader2'");
	 }
}
end_row();
comments_display_row(ST_CUSTPAYMENT, $trans_id);

end_table(1);

$voided = is_voided_display(ST_CUSTPAYMENT, $trans_id, _("This customer payment has been voided."));

if (!$voided) // $voided == false
{
	if($ref_no>0){

	display_allocations_from(PT_CUSTOMER, $receipt['debtor_no'], ST_CUSTPAYMENT, $trans_id, $receipt['Total'],$ref_no);}
	else{
		display_allocations_from(PT_CUSTOMER, $receipt['debtor_no'], ST_CUSTPAYMENT, $trans_id, $receipt['Total']);
	}
}

br();
br();	
br();	
br();	
?>
  <table  align="center"    width="80%" cellpadding="" cellspacing="0">
  <tr>
  <td  style="text-align:left"><b>Prepared By</b></td>
  <td  style="text-align:right"><b>Approved By</b></td>
  </tr>
  </table>
	
<?php	


end_page(true, false, false, ST_CUSTPAYMENT, $trans_id);
