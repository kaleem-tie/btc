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
$page_security = 'SA_PETTY_CASH_INQ_VIEW';
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");

page(_($help_context = "View Bank Payment"), true);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

if (isset($_GET["petty_cash_ref"]))
{
	$petty_cash_ref = $_GET["petty_cash_ref"];
}

// get the petty cash info
$result = get_gl_petty_cash_trans(ST_BANKPAYMENT, $petty_cash_ref);

if (db_num_rows($result) != 1)
	display_db_error("duplicate petty cash bank transaction found", "");

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

display_heading(_("Petty Cash Payment") . " #$petty_cash_ref");

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

label_cells(_("Date"), sql2date($from_trans['trans_date']), "class='tableheader2'");
end_row();


start_row();
label_cells(_("Our Reference No."), $from_trans['our_ref_no'], "class='tableheader2'", "colspan=$colspan2");
end_row();

start_row();
$preared_user = get_transaction_prepared_by(ST_BANKPAYMENT, $from_trans['trans_no']);
label_row(_("Prepared By"), $preared_user, "class='tableheader2'");
end_row();


comments_display_row(ST_BANKPAYMENT, $from_trans['trans_no']);

end_table(1);

$voided = is_voided_display(ST_BANKPAYMENT, $from_trans['trans_no'], _("This Petty Cash has been voided."));

$items = get_petty_cash_gl_trans(ST_BANKPAYMENT, $petty_cash_ref);

if (db_num_rows($items)==0)
{
	display_note(_("There are no items for this Petty Cash."));
}
else
{

	display_heading2(_("Items for this Petty Cash"));
	if ($show_currencies)
		display_heading2(_("Item Amounts are Shown in:") . " " . $company_currency);
   
			echo "<br>";
			start_table(TABLESTYLE, "width='80%'");
			$dim = get_company_pref('use_dimension');
			if ($dim == 2)
				$th = array(_("Account Code"), _("Account Description"), _("Supplier"), _("Dimension")." 1", _("Dimension")." 2",
					_("Amount"));
			elseif ($dim == 1)
				$th = array(_("Account Code"), _("Account Description"), _("Supplier"), _("Dimension"),
					_("Amount"));
			else
				$th = array(_("Account Code"), _("Account Description"), _("Supplier"),
					_("Amount"));
			table_header($th);

			$k = 0; //row colour counter
			$total_amount = 0;

			while ($item = db_fetch($items))
			{

				if ($item["account"] != $from_trans["account_code"])
				{
					alt_table_row_color($k);

					label_cell($item["account"]);
					label_cell($item["account_name"]);
					
					if($item["person_type_id"]=='3')
					label_cell(payment_person_name($item["person_type_id"],$item["person_id"]));
				    else
					label_cell("");	
					
					if ($dim >= 1)
						label_cell(get_dimension_string($item['dimension_id'], true));
					if ($dim > 1)
						label_cell(get_dimension_string($item['dimension2_id'], true));
					amount_cell(-$item["amount"]);
					// label_cell($item["memo_"]);
					end_row();
                    if ($item["memo_"] != '') {
                        start_row();
                        echo "<td colspan='6'>Memo: ".$item["memo_"]."</td>";
                        end_row();
                        // start_row();
                        // echo "<td colspan='10'></td>";
                        // end_row();
                    }
					$total_amount += $item["amount"];
				}
			}

			label_row(_("Total"), number_format2(-$total_amount, user_price_dec()),"colspan=".(3+$dim)." align=right", "align=right");
			
			end_table(1);
	
	$hundredth_name=get_currency_hundredth_name($from_trans['bank_curr_code']);
	$invoice_amount=-$total_amount;
	list($whole, $decimal) = explode('.', $invoice_amount);
	if($decimal)
	$words1=$hundredth_name." only";
	else
	$words1=" only";


	
	$words = no_to_words(-$total_amount);
	start_table(TABLESTYLE_NOBORDER, "width='80%'");
	label_cells(null, _("<b> Amount in Words ").$from_trans['bank_curr_code'] . ": " . $words.$words1."</b>");
	end_table();
	

//if($from_trans['account_type']=='3' && $from_trans['person_type_id']=='0'){
/*
if (!$voided)
		display_allocations_from($from_trans['person_type_id'], $from_trans['person_id'], 1, $trans_no, $from_trans['settled_amount']);	
*/
br();
	br();
	br();
	br();	
?>
	 <table  align="center"    width="80%" cellpadding="" cellspacing="0">
  <tr>
  
  <td class="label" style="text-align:left">Accountant</td>
  
   <td class="label" style="text-align:left">Finance Manager</td>
  
  <td class="label" style="text-align:center">General Manager</td>
  
  <td class="label" style="text-align:center">Director</td>
  
  </tr>
  </table>
	
<?php	
//}
}

end_page(true, false, false, ST_BANKPAYMENT, $trans_no);



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

