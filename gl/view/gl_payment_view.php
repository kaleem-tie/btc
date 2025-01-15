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
$page_security = 'SA_BANKTRANSVIEW';
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");

page(_($help_context = "View Bank Payment"), true);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

if (isset($_GET["trans_no"]))
{
	$trans_no = $_GET["trans_no"];
}

// get the pay-from bank payment info
$result = get_bank_trans(ST_BANKPAYMENT, $trans_no);

//if (db_num_rows($result) != 1)
	//display_db_error("duplicate payment bank transaction found", "");

$from_trans = db_fetch($result);

$company_currency = get_company_currency();

$show_currencies = false;

if ($from_trans['bank_curr_code'] != $from_trans['settle_curr'])
{
	$show_currencies = true;
}

$company = get_company_prefs();
display_heading("<font color=black>".$company['coy_name']."</font>");
echo "<br>";

display_heading(_("GL Payment") . " #$trans_no");

echo "<br>";
start_table(TABLESTYLE, "width='80%'");

if ($show_currencies)
{
	$colspan1 = 1;
	$colspan2 = 7;
}
else
{
	$colspan1 = 3;
	$colspan2 = 5;
}
start_row();
label_cells(_("From Bank Account"), $from_trans['bank_account_name'], "class='tableheader2'");
if ($show_currencies)
	label_cells(_("Currency"), $from_trans['bank_curr_code'], "class='tableheader2'");
label_cells(_("Amount"), number_format2(-$from_trans['amount'], user_price_dec()), "class='tableheader2'", "align=right");
label_cells(_("Date"), sql2date($from_trans['trans_date']), "class='tableheader2'");
end_row();
start_row();

label_cells(_("Pay To"),payment_person_name($from_trans["person_type_id"]), "class='tableheader2'", "colspan=$colspan1");

//label_cells(_("Pay To"), get_counterparty_name(ST_BANKPAYMENT, $from_trans['trans_no']), "class='tableheader2'", "colspan=$colspan1");
if ($show_currencies)
{
	label_cells(_("Settle currency"), $from_trans['settle_curr'], "class='tableheader2'");
	label_cells(_("Settled amount"), number_format2($from_trans['settled_amount'], user_price_dec()), "class='tableheader2'");
}
//label_cells(_("Payment Type"), $bank_transfer_types[$from_trans['account_type']], "class='tableheader2'");
end_row();
start_row();
label_cells(_("Reference"), $from_trans['ref'], "class='tableheader2'", "colspan=$colspan2");
end_row();

start_row();
label_cells(_("Mode Of Payment"), $mode_payment_types[$from_trans['mode_of_payment']], "class='tableheader2'");
if($from_trans['mode_of_payment']=='cheque')
{
	label_cells(_("Cheque No"), $from_trans['cheque_no'], "class='tableheader2'");
	label_cells(_("Date Of Issue"), sql2date($from_trans['date_of_issue']), "class='tableheader2'");
}else if($from_trans['mode_of_payment']=='dd'){
	label_cells(_("DD No"), $from_trans['dd_no'], "class='tableheader2'");
	label_cells(_("Date Of Issue"), sql2date($from_trans['dd_date_of_issue']), "class='tableheader2'");
}
 else if($from_trans['mode_of_payment'] == 'ot' || $from_trans['mode_of_payment'] == 'neft' || $from_trans['mode_of_payment'] == 'card' || $from_trans['mode_of_payment'] == 'rtgs'){
	 
	 if($from_trans['mode_of_payment'] == 'card'){
	label_cells(_("Card Last 4 Digits"), $from_trans['pymt_ref'], "class='tableheader2'");
	 }
	
	if($from_trans['amex']==1){
		$amex ="Yes";
	}
	else{
		$amex ="No";
	}
	
	label_cells(_("AMEX"), $amex, "class='tableheader2'");
}

$preared_user = get_transaction_prepared_by(ST_BANKPAYMENT, $trans_no);
label_row(_("Prepared By"), $preared_user, "class='tableheader2'");

end_row();


comments_display_row(ST_BANKPAYMENT, $trans_no);

end_table(1);

$voided = is_voided_display(ST_BANKPAYMENT, $trans_no, _("This payment has been voided."));

$items = get_gl_trans(ST_BANKPAYMENT, $trans_no);

if (db_num_rows($items)==0)
{
	display_note(_("There are no items for this payment."));
}
else
{

	display_heading2(_("Items for this Payment"));
	if ($show_currencies)
		display_heading2(_("Item Amounts are Shown in:") . " " . $company_currency);
	if($from_trans['is_purch_cash_bill']==1)  // Multi Purch Cash bill View
	{
			echo "<br>";
			start_table(TABLESTYLE, "width='80%'");
			$dim = get_company_pref('use_dimension');
			if ($dim == 2)
				$th = array(_("Account Code"), _("Account Description"), _("Account Description"), _("Dimension")." 1", _("Dimension")." 2", _("Amount"), _("Memo"));
			elseif ($dim == 1)
				$th = array(_("Account Code"), _("Account Description"), _("Dimension"),
					_("Amount"),_("Supplier Name"),_("VAT Number"),_("Bill No"),_("Bill Date"),_("Bill Amount"),_("Is Tax Accout"), _("Memo"));
			else
				$th = array(_("Account Code"), _("Account Description"),
					_("Amount"), _("Memo"));
			table_header($th);
			$mult_cash_items = get_gl_trans_multiple_purch_cash_bill($trans_no);
			$k = 0; //row colour counter
			$total_amount = 0;
	
			while ($cash_item = db_fetch($mult_cash_items))
			{

				if ($cash_item["account"] != $from_trans["account_code"])
				{
					alt_table_row_color($k);
					
					

					label_cell($cash_item["account"]);
					label_cell($cash_item["account_name"]);
	
					
					
					if ($dim >= 1)
						label_cell(get_dimension_string($cash_item['dimension_id'], true));
					
					amount_cell($cash_item["amount"]);
					label_cell($cash_item["supp_name"]);
					label_cell($cash_item["supp_vat_no"]);
					label_cell($cash_item["supp_bill_no"]);
					label_cell(sql2date($cash_item["supp_bill_date"]));
					amount_cell($cash_item["bill_amount"]);
					if($cash_item["is_tax_account"] == 1){
					  $is_tax_account = "Yes";
					}
					else{
						$is_tax_account = "No";
					}
					label_cell($is_tax_account);
					label_cell($cash_item["memo_"]);
					end_row();
					$total_amount += $cash_item["amount"];
				}
			}

			label_row(_("Total"), number_format2($total_amount, user_price_dec()),"colspan=".(2+$dim)." align=right", "align=right");
			
			end_table(1);
	}else {
			echo "<br>";
			start_table(TABLESTYLE, "width='80%'");
			$dim = get_company_pref('use_dimension');
			if ($dim == 2)
				$th = array(_("Account Code"), _("Account Description"), _("Dimension")." 1", _("Dimension")." 2",
					_("Amount"), _("Memo"));
			elseif ($dim == 1)
				$th = array(_("Account Code"), _("Account Description"), _("Dimension"),
					_("Amount"), _("Memo"));
			else
				$th = array(_("Account Code"), _("Account Description"),
					_("Amount"), _("Memo"));
			table_header($th);

			$k = 0; //row colour counter
			$total_amount = 0;

			while ($item = db_fetch($items))
			{

				if ($item["account"] != $from_trans["account_code"])
				{
					alt_table_row_color($k);
					
					$counterpartyname = get_subaccount_name($item["account"], $item["person_id"]);
	                $counterparty_id = $counterpartyname ? sprintf(' %05d', $item["person_id"]) : '';
					
					 label_cell($item['account'].$counterparty_id);
	label_cell($item['account_name'] . ($counterpartyname ? ': '.$counterpartyname : ''));

					//label_cell($item["account"]);
					//label_cell($item["account_name"]);
					if ($dim >= 1)
						label_cell(get_dimension_string($item['dimension_id'], true));
					if ($dim > 1)
						label_cell(get_dimension_string($item['dimension2_id'], true));
					amount_cell($item["amount"]);
					label_cell($item["memo_"]);
					end_row();
					$total_amount += $item["amount"];
				}
			}

			label_row(_("Total"), number_format2($total_amount, user_price_dec()),"colspan=".(2+$dim)." align=right", "align=right");
			
			end_table(1);
	}
	$hundredth_name=get_currency_hundredth_name($from_trans['bank_curr_code']);
	$invoice_amount=$total_amount;
	list($whole, $decimal) = explode('.', $invoice_amount);
	if($decimal)
	$words1=$hundredth_name." only";
	else
	$words1=" only";
	
	$words = no_to_words1($total_amount);
	start_table(TABLESTYLE_NOBORDER, "width='80%'");
	label_cells(null, _("<b> Amount in Words ").$from_trans['bank_curr_code'] . ": " . $words.$words1."</b>");
	end_table();
	//label_row(_("Total R.O"), $words);

//if($from_trans['account_type']=='3' && $from_trans['person_type_id']=='0'){

if (!$voided)
		display_allocations_from($from_trans['person_type_id'], $from_trans['person_id'], 1, $trans_no, $from_trans['settled_amount']);	
br();
	br();
	br();
	br();	
?>
	 <table  align="center"    width="80%" cellpadding="" cellspacing="0">
  <tr>
  
  <td class="label" style="text-align:left">Paid By</td>
  
  <td class="label" style="text-align:center">Approved By</td>
  
  <td class="label" style="text-align:center">Received By</td>
  
  </tr>
  </table>
	
<?php	
//}
}

end_page(true, false, false, ST_BANKPAYMENT, $trans_no);



function no_to_words1($number)
{   
 /* $words = array('0'=> '' ,'1'=> 'one' ,'2'=> 'two' ,'3' => 'three','4' => 'four','5' => 'five','6' => 'six','7' => 'seven','8' => 'eight','9' => 'nine','10' => 'ten','11' => 'eleven','12' => 'twelve','13' => 'thirteen','14' => 'fourteen','15' => 'fifteen','16' => 'sixteen','17' => 'seventeen','18' => 'eighteen','19' => 'nineteen','20' => 'twenty','30' => 'thirty','40' => 'forty','50' => 'fifty','60' => 'sixty','70' => 'seventy','80' => 'eighty','90' => 'ninety','100' => 'hundred','1000' => 'thousand','100000' => 'lakh','10000000' => 'crore');
    if($no == 0)
        return ' ';
    else {
	$novalue='';
	$highno=$no;
	$remainno=0;
	$value=100;
	$value1=1000;       
            while($no>=100)    {
                if(($value <= $no) &&($no  < $value1))    {
                $novalue=$words["$value"];
                $highno = (int)($no/$value);
                $remainno = $no % $value;
                break;
                }
                $value= $value1;
                $value1 = $value * 100;
            }       
          if(array_key_exists("$highno",$words))
              return $words["$highno"]." ".$novalue." ".no_to_words($remainno);
          else {
             $unit=$highno%10;
             $ten =(int)($highno/10)*10;            
             return $words["$ten"]." ".$words["$unit"]." ".$novalue." ".no_to_words($remainno);
           }
    } */
    
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
        $hundred = ($counter == 1 && $str[0]) ? 'and ' : null;
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
  
		  /*if($point!=0){
			    $points = ($point) ?
					" " . $words[floor($point / 10) * 10] . " " . 
				  $words[$point = $point % 10] : '';
				
				  $paisa=$points." Baisa ";
		  }else
		  {
			  $paisa='';
		  } */
		  
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
        $hundred = ($counter == 1 && $str[0]) ? 'and ' : null;
        $str [] = ($number < 21) ? $words[$number] .
            " " . $digits[$counter] . $plural . " " . $hundred
            :
            $words[floor($number / 10) * 10]
            . " " . $words[$number % 10] . " "
            . $digits[$counter] . $plural . " " . $hundred;
     } else $str[] = null;
  }
  $str = array_reverse($str);
  $result1 = implode('', $str);
  
   if(!$result1)
   return $result;
   else  
   return $result .'and '. $result1;
} 
