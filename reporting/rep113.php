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
$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	'SA_SALESTRANSVIEW' : 'SA_SALESBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Print Credit Notes
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

//----------------------------------------------------------------------------------------------------

function get_sales_invoice_reference_against_credit_note($order_no)
{
	$sql="SELECT reference FROM ".TB_PREF."debtor_trans 
	WHERE type=10 AND  order_=".db_escape($order_no)."";
	$res= db_query($sql);
	$result = db_fetch_row($res);
	return $result == false ? false : $result[0];
}

function get_sales_invoice_date_against_credit_note($order_no)
{
	$sql="SELECT tran_date FROM ".TB_PREF."debtor_trans 
	WHERE type=10 AND  order_=".db_escape($order_no)."";
	$res= db_query($sql);
	$result = db_fetch_row($res);
	return $result == false ? false : $result[0];
}

print_credits();

//----------------------------------------------------------------------------------------------------

function print_credits()
{
	global $path_to_root, $SysPrefs;
	
	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$paylink = $_POST['PARAM_4'];
	$comments = $_POST['PARAM_5'];
	$orientation = $_POST['PARAM_6'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

 	$fno = explode("-", $from);
	$tno = explode("-", $to);
	$from = min($fno[0], $tno[0]);
	$to = max($fno[0], $tno[0]);

	//$cols = array(2, 25, 100, 290, 320, 370, 420, 470, 530);
	
	$cols = array(2, 25, 100, 280, 310, 360, 420, 480, 530);

	// $headers in doctext.inc
	$aligns = array('left',	'left','left','left','right', 'right', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
		$rep = new FrontReport(_('CREDIT NOTE'), "InvoiceBulk", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

	for ($i = $from; $i <= $to; $i++)
	{
		if (!exists_customer_trans(ST_CUSTCREDIT, $i))
			continue;
		$sign = -1;
		$myrow = get_customer_trans($i, ST_CUSTCREDIT);
		if ($currency != ALL_TEXT && $myrow['curr_code'] != $currency) {
			continue;
		}
		$baccount = get_default_bank_account($myrow['curr_code']);
		$params['bankaccount'] = $baccount['id'];

		$branch = get_branch($myrow["branch_code"]);
		$branch['disable_branch'] = $paylink; // helper
		$sales_order = null;
		if ($email == 1)
		{
			$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
			$rep->title = _('CREDIT NOTE');
			$rep->filename = "CreditNote" . $myrow['reference'] . ".pdf";
		}
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);

		$contacts = get_branch_contacts($branch['branch_code'], 'invoice', $branch['debtor_no'], true);
		$rep->SetCommonData($myrow, $branch, $sales_order, $baccount, ST_CUSTCREDIT, $contacts);
		$rep->SetHeaderType('Header69');
		$rep->NewPage();

		$result = get_customer_trans_details(ST_CUSTCREDIT, $i);
		$SubTotal = 0;
		$k=1;
		$gross_total = $total_disc_amount = 0;
		while ($myrow2=db_fetch($result))
		{
			if ($myrow2["quantity"] == 0)
				continue;
			
			
			 $discount_amount =  $myrow2["unit_price"] * $myrow2["quantity"] * 
			 $myrow2["discount_percent"]/100;	
			$gross_total +=  ($sign * $myrow2["quantity"]*$myrow2["unit_price"]);	

			$Net = round2($sign * ($myrow2["unit_price"] * $myrow2["quantity"]),
			   user_price_dec());
			$SubTotal += $Net;
			$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
			$DisplayQty = number_format2(-$sign*$myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
			$DisplayNet = number_format2(-$Net,$dec);
			if ($myrow2["discount_percent"]==0)
				$DisplayDiscount ="";
			else
				$DisplayDiscount = number_format2($myrow2["discount_percent"],user_percent_dec()) . "%";
			
			$item = get_item($myrow2['stock_id']);
			
			$rep->TextCol(0, 1,	$k, -2);
			$rep->TextCol(1, 2,	$myrow2['stock_id'], -2);
			$oldrow = $rep->row;
			$rep->TextColLines(2, 3, $myrow2['StockDescription'], -2);
			$newrow = $rep->row;
			$rep->row = $oldrow;
			
			//$rep->TextCol(3, 4,	$myrow2['units'], -2);
			if($myrow2['unit']==1){
				 $item_info = get_item_edit_info($myrow2["stock_id"]);	
				$rep->TextCol(3, 4,	$item_info["units"], -2);
				}
				else if($myrow2["unit"]==2){
		         $sec_unit_info = get_item_sec_unit_info($myrow2["stock_id"]);
	             $rep->TextCol(3, 4,$sec_unit_info["sec_unit_name"], -2);
                }	
			$rep->TextCol(4, 5,	$DisplayQty, -2);
			$rep->TextCol(5, 6,	$DisplayPrice, -2);
			//$rep->TextCol(6, 7,	$DisplayDiscount, -2);
			$rep->TextCol(6, 7,	$DisplayNet, -2);
			
			if($item['exempt']==0){
					$rep->TextCol(7, 8,	"5.00", -2);
			}
			else{
					$rep->TextCol(7, 8,	"0", -2);	
			}	
			
			$rep->row = $newrow;
			if ($rep->row < $rep->bottomMargin + (19 * $rep->lineHeight))
				$rep->NewPage();
			
			
			$total_disc_amount += $discount_amount;
			$k++;
		}

		$memo = get_comments_string(ST_CUSTCREDIT, $i);
		if ($memo != "")
		{
			$rep->NewLine();
			$rep->SetFont('helvetica', 'B', 9);
			$rep->Text($ccol+45 , _("Comments : "));
			$rep->SetFont('', '', 0);
			$rep->NewLine();
			$rep->TextColLines(0, 3, $memo, -2);
		}
		
		
		

		$DisplaySubTot = number_format2(-$SubTotal,$dec);

		$rep->row = $rep->bottomMargin + (19 * $rep->lineHeight);
		$doctype = ST_CUSTCREDIT;
		
		$rep->Text($ccol+40, str_pad('', 120, '_'));
		$rep->NewLine();
		
		
		$DisplayGrossTot = number_format2(-$gross_total,$dec);
		$DisplayDiscAmtTot = number_format2(-$total_disc_amount,$dec);
		
		
		    $rep->SetFont('helvetica', 'B', 9);
			$rep->Text($ccol+50, _("Gross Total  "));
			$rep->Text($ccol+110, _(":  "));
			$rep->Text($ccol+120, $DisplayGrossTot);
			
			
			$rep->Text($ccol+250, _("Discount  "));
			$rep->Text($ccol+300, _(":  "));
			$rep->SetFont('', '', 0);
			$rep->Text($ccol+310, $DisplayDiscAmtTot);
			
			$rep->SetFont('helvetica', 'B', 9);
			$rep->Text($ccol+420, _("Net Amount  "));
			$rep->Text($ccol+490, _(":  "));
			$rep->Text($ccol+520, $DisplaySubTot);
			$rep->NewLine();
			
			
			
		$tax_items = get_trans_tax_details(ST_CUSTCREDIT, $i);
		$first = true;
		while ($tax_item = db_fetch($tax_items))
		{
			if ($tax_item['amount'] == 0)
				continue;
			$DisplayTax = number_format2(-$sign*$tax_item['amount'], $dec);

			if ($SysPrefs->suppress_tax_rates() == 1)
				$tax_type_name = $tax_item['tax_type_name'];
			else
				$tax_type_name = $tax_item['tax_type_name']." (".$tax_item['rate']."%) ";

			if ($myrow['tax_included'])
			{
				if ($SysPrefs->alternative_tax_include_on_docs() == 1)
				{
					if ($first)
					{
						
						$rep->SetFont('helvetica', 'B', 9);
						$rep->Text($ccol+420, _("Total Tax Excluded  "));
						$rep->Text($ccol+490, _(":  "));
						$rep->SetFont('', '', 0);
						$rep->Text($ccol+520, number_format2($sign*$tax_item['net_amount'], $dec));
						$rep->NewLine();
					}
					
					$rep->SetFont('helvetica', 'B', 9);
					$rep->Text($ccol+420, $tax_type_name);
					$rep->Text($ccol+490, _(":  "));
					$rep->SetFont('', '', 0);
					$rep->Text($ccol+520, $DisplayTax);
					$first = false;
				}
				else
				   $rep->SetFont('helvetica', 'B', 9);
					$rep->Text($ccol+350 , _("Included") . " " . $tax_type_name . " " . _("Amount"). ": " . $DisplayTax);
					$rep->SetFont('', '', 0);	
			}
			else
			{
				$rep->SetFont('helvetica', 'B', 9);
				$rep->Text($ccol+420, $tax_type_name);
				$rep->Text($ccol+490, _(":  "));
				$rep->SetFont('', '', 0);
				$rep->Text($ccol+520, $DisplayTax);
			}
			$rep->NewLine();
		}
		
		
		
		
		if ($myrow['ov_roundoff'] != 0.0)
		{
			$rep->NewLine();
   			$DisplayRoundoff = number_format2($sign*$myrow["ov_roundoff"],$dec);
			$rep->SetFont('helvetica', 'B', 9);	
			$rep->Text($ccol+200, _("Round Off  "));
			$rep->Text($ccol+290, _(":  "));
			$rep->SetFont('', '', 0);
			$rep->Text($ccol+300, $DisplayRoundoff);
			$rep->NewLine();
		}	
		
		
		$DisplayTotal = number_format2(-$sign*($myrow["ov_freight"] + $myrow["ov_gst"] +
			$myrow["ov_amount"]+$myrow["ov_freight_tax"]+$myrow["ov_roundoff"]),$dec);
		$rep->SetFont('helvetica', 'B', 9);
		// $rep->Text($ccol+420, _("TOTAL CREDIT"));
		$rep->Text($ccol+420, _("Net Include vat"));
		$rep->Text($ccol+490, _(":  "));
		$rep->Text($ccol+520, $DisplayTotal);
		$rep->SetFont('', '', 0);
		
		
		$words = no_to_words($myrow['Total']);
			
        $hundredth_name=get_currency_hundredth_name($myrow['curr_code']);
		$invoice_amount=$rep->formData['prepaid'] ? $myrow['prep_amount'] : $myrow['Total'];
			list($whole, $decimal) = explode('.', $invoice_amount);
			
		if($decimal)
		$words1=" ".$hundredth_name." only";
		else
		$words1=" only";	
		
		if ($words != "")
		{
				$rep->NewLine(1);
				$rep->Font('bold');
				$rep->TextCol(0, 8, $myrow['curr_code'].":".$words.$words1, - 2);
				$rep->Font();
		}
		$rep->Font();
		$rep->Text($ccol+40, str_pad('', 120, '_'));
		$rep->NewLine();
		
		$invoice_reference = get_sales_invoice_reference_against_credit_note($myrow["order_"]);
		
		$invoice_tran_date = get_sales_invoice_date_against_credit_note($myrow["order_"]);
		
		
		
		$rep->SetFont('helvetica', 'B', 9);
		$rep->TextCol(0, 7,"Note:", -2);
		$rep->SetFont('', '', 0);
		$rep->NewLine();	
		if($invoice_reference!=0)
		$rep->TextCol(0, 3, _("Sales Invoice Reference ".$invoice_reference. ",".sql2date($invoice_tran_date)), -2);	
		
		
		
		$rep->NewLine(4);
		$rep->SetFont('helvetica', 'B', 9);
	    $rep->Text($mcol + 55, _("Customer Signature"));
		$printdate = Today() . '   ' . Now();
	    $rep->Text($mcol + 195, ("Print Out Date ").$printdate);
		$rep->Text($ccol+375 , _("For ").$rep->company['coy_name']);
	    $rep->SetFont('', '', 0);
		
		if ($email == 1)
		{
			$myrow['dimension_id'] = $paylink; // helper for pmt link
			$rep->End($email);
		}
	}
	if ($email == 0)
		$rep->End();
}

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

