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
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "View Payment to Supplier"), true, false, "", $js);

if (isset($_GET["trans_no"]))
{
	$trans_no = $_GET["trans_no"];
}

$receipt = get_supp_trans($trans_no, ST_SUPPAYMENT);

$company_currency = get_company_currency();

$show_currencies = false;
$show_both_amounts = false;

if (($receipt['bank_curr_code'] != $company_currency) || ($receipt['curr_code'] != $company_currency))
	$show_currencies = true;

if ($receipt['bank_curr_code'] != $receipt['curr_code']) 
{
	$show_currencies = true;
	$show_both_amounts = true;
}

$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

echo "<center>";

display_heading(_("Payment to Supplier") . " #$trans_no");

echo "<br>";
start_table(TABLESTYLE2, "width='80%'");

start_row();
label_cells(_("To Supplier"), $receipt['supplier_name'], "class='tableheader2'");
label_cells(_("From Bank Account"), $receipt['bank_account_name'], "class='tableheader2'");
label_cells(_("Date Paid"), sql2date($receipt['tran_date']), "class='tableheader2'");
end_row();
start_row();
if ($show_currencies)
	label_cells(_("Payment Currency"), $receipt['bank_curr_code'], "class='tableheader2'");
label_cells(_("Amount LC"), number_format2(-$receipt['bank_amount'], user_price_dec()), "class='tableheader2'");
if ($receipt['ov_discount'] != 0)
	label_cells(_("Discount"), number_format2(-$receipt['ov_discount']*$receipt['rate'], user_price_dec()), "class='tableheader2'");
//else
//	label_cells(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']], "class='tableheader2'");
end_row();
start_row();
if ($show_currencies) 
{
	label_cells(_("Supplier's Currency"), $receipt['curr_code'], "class='tableheader2'");
}
if ($show_both_amounts)
	label_cells(_("Amount FC"), number_format2(-$receipt['Total'], user_price_dec()), "class='tableheader2'");
label_cells(_("Reference"), $receipt['ref'], "class='tableheader2'");
end_row();
if ($receipt['ov_discount'] != 0)
{
	//start_row();
	//label_cells(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']], "class='tableheader2'");
   //end_row();
}


start_row();
label_cells(_("Mode Of Payment"), $mode_payment_types[$receipt['mode_of_payment']], "class='tableheader2'");
if($receipt['mode_of_payment']=='cheque')
{
	label_cells(_("Cheque No"), $receipt['cheque_no'], "class='tableheader2'");
	label_cells(_("Date Of Issue"), sql2date($receipt['date_of_issue']), "class='tableheader2'");
}else if($receipt['mode_of_payment']=='dd'){
	label_cells(_("DD No"), $receipt['dd_no'], "class='tableheader2'");
	label_cells(_("Date Of Issue"), sql2date($receipt['dd_date_of_issue']), "class='tableheader2'");
}
 else if($receipt['mode_of_payment'] == 'ot' || $receipt['mode_of_payment'] == 'neft' || $receipt['mode_of_payment'] == 'card' || $receipt['mode_of_payment'] == 'rtgs'){
	 
	 if($receipt['mode_of_payment'] == 'card'){
	label_cells(_("Card Last 4 Digits"), $receipt['pymt_ref'], "class='tableheader2'");
	 }
	
	if($receipt['amex']==1){
		$amex ="Yes";
	}
	else{
		$amex ="No";
	}
	
	//label_cells(_("AMEX"), $amex, "class='tableheader2'");
}

$preared_user = get_transaction_prepared_by(ST_SUPPAYMENT, $trans_no);
// label_row(_("Prepared By"), $preared_user, "class='tableheader2'", "colspan=3");
label_row(_("Exchange Rate"),number_format2($receipt['rate'], user_price_dec()), "class='tableheader2'", "colspan=3");
end_row();

comments_display_row(ST_SUPPAYMENT, $trans_no);

end_table(1);

$voided = is_voided_display(ST_SUPPAYMENT, $trans_no, _("This payment has been voided."));

// now display the allocations for this payment
if (!$voided) 
{
	display_allocations_from(PT_SUPPLIER, $receipt['supplier_id'], ST_SUPPAYMENT, $trans_no, -$receipt['Total']);
}
start_table("", "width='80%'");
    $th = array( );
	table_header($th);
	alt_table_row_color($k);

$words = no_to_words(round(-$receipt['Total']));
	start_row();
	label_cell("<b>Amount In Words (".$receipt['curr_code'].") :</b>".$words."only");
	end_row();
	end_table(1);
	
	br();br();br();
	
	start_table("", "width='80%'");
	start_row();
	//label_cell("<b>Prepared By: </b>".$preared_user,"align='left'");
	label_cell("<b>Prepared By</b>","align='center' colspan=3");
	label_cell("<b>Approved By</b>","align='center' colspan=3");
	label_cell("<b>Received By</b>","align='right'");
	end_row();
	end_table(1);
	
end_page(true, false, false, ST_SUPPAYMENT, $trans_no);

function no_to_words($number)
{   
 
    
     $no = $number;
	$no1 =round($number);
    $dec = explode('.', $number);
    $dec2 =substr($dec[1],0,3);

	if(strlen($dec2)==1)
	{
		$dec2=$dec2*100;
	}
	if(strlen($dec2)==2)
	{
		$dec2=$dec2*10;
	}
	
   $point = $dec2;
   $hundred = null;
   $digits_1 = strlen($no);
   $i = 0;
   $str = array();
   $words = array('0' => '', '1' => 'One', '2' => 'Two',
    '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six',
    '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
    '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve',
    '13' => 'Thirteen', '14' => 'Fourteen',
    '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
    '18' => 'Eighteen', '19' =>'Nineteen', '20' => 'Twenty',
    '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty',
    '60' => 'Sixty', '70' => 'Seventy',
    '80' => 'Eighty', '90' => 'Ninety');
   $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
   while ($i < $digits_1) {
     $divider = ($i == 2) ? 10 : 100;
     $number = floor($no % $divider);
     $no = floor($no / $divider);
     $i += ($divider == 10) ? 1 : 2;
     if ($number) {
        $plural = (($counter = count($str)) && $number > 9) ? '' : null;
        $hundred = ($counter == 1 && $str[0]) ? '' : null;
        $str [] = ($number < 21) ? $words[$number] .
            " " . $digits[$counter] . $plural . " " . $hundred
            :
            $words[floor($number / 10) * 10]
            . " " . $words[$number % 10] . " "
            . $digits[$counter] . $plural . " " . $hundred;
     } else $str[] = null;
  }
  $str = array_reverse($str);
  $result = implode('', $str);
  
		 
		  
		$hundred = null;
   $digits_1 = strlen($dec2);
   $i = 0;
   $str = array();
   $words = array('0' => '', '1' => 'One', '2' => 'Two',
    '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six',
    '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
    '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve',
    '13' => 'Thirteen', '14' => 'Fourteen',
    '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
    '18' => 'Eighteen', '19' =>'Nineteen', '20' => 'Twenty',
    '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty',
    '60' => 'Sixty', '70' => 'Seventy',
    '80' => 'Eighty', '90' => 'Ninety');
   $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
   while ($i < $digits_1) {
     $divider = ($i == 2) ? 10 : 100;
     $number = floor($dec2 % $divider);
     $dec2 = floor($dec2 / $divider);
     $i += ($divider == 10) ? 1 : 2;
     if ($number) {
        $plural = (($counter = count($str)) && $number > 9) ? '' : null;
        $hundred = ($counter == 1 && $str[0]) ? '' : null;
        $str [] = ($number < 21) ? $words[$number] .
            " " . $digits[$counter] . $plural . " " . $hundred
            :
            $words[floor($number / 10) * 10]
            . " " . $words[$number % 10] . " "
            . $digits[$counter] . $plural . " " . $hundred;
     } else $str[] = null;
  }
  $str = array_reverse($str);
  $result1 = trim(implode('', $str));
  
  if(!$result1)
   return $result;
   else  
   return trim($result).' and '.$result1; //ravi
} 
