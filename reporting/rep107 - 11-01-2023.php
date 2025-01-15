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
// Title:	Print Invoices
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

//----------------------------------------------------------------------------------------------------
function get_invoice_range($from, $to, $currency=false)
{
	global $SysPrefs;

	$ref = ($SysPrefs->print_invoice_no() == 1 ? "trans_no" : "reference");

	$sql = "SELECT trans.trans_no, trans.reference";

//  if($currency !== false)
//		$sql .= ", cust.curr_code";

	$sql .= " FROM ".TB_PREF."debtor_trans trans 
			LEFT JOIN ".TB_PREF."voided voided ON trans.type=voided.type AND trans.trans_no=voided.id";

	if ($currency !== false)
		$sql .= " LEFT JOIN ".TB_PREF."debtors_master cust ON trans.debtor_no=cust.debtor_no";

	$sql .= " WHERE trans.type=".ST_SALESINVOICE
		." AND ISNULL(voided.id)"
 		." AND trans.trans_no BETWEEN ".db_escape($from)." AND ".db_escape($to);			

	if ($currency !== false)
		$sql .= " AND cust.curr_code=".db_escape($currency);

	$sql .= " ORDER BY trans.tran_date, trans.$ref";

	return db_query($sql, "Cant retrieve invoice range");
}

function get_invoice_sales_delivery_reference($type, $trans_no)
{
	$sql="SELECT reference as dispatch_refence FROM ".TB_PREF."debtor_trans WHERE trans_no=".db_escape($trans_no)." AND  type=".db_escape($type)."";

	$res= db_query($sql);
	$result = db_fetch_row($res);
	return $result == false ? false : $result[0];
}

function get_invoice_prepared_by($type, $trans_no)
{
	$sql="SELECT user FROM ".TB_PREF."audit_trail WHERE trans_no=".db_escape($trans_no)." AND  type=".db_escape($type)."";

	$res= db_query($sql);
	$result = db_fetch_row($res);
	return $result == false ? false : $result[0];
}

//Delivery date
function get_invoice_sales_delivery_date($type, $trans_no)
{
	$sql="SELECT tran_date as dispatch_date FROM ".TB_PREF."debtor_trans WHERE trans_no=".db_escape($trans_no)." AND  type=".db_escape($type)."";

	$res= db_query($sql);
	$result = db_fetch_row($res);
	return $result == false ? false : $result[0];
}


function get_customer_payment_mode_info($type,$trans_no)
{
	$sql = "SELECT mode_of_payment,cheque_no,pymt_ref FROM ".TB_PREF."bank_trans 
	WHERE type=12 and trans_no=".db_escape($trans_no)."";
	
	$result = db_query($sql,"No transactions were returned");
    return db_fetch($result);
	
}

print_invoices();

// English to Arabic
function EnglishToArabic($source)
{
	$arabic_data[]='';
	if(strlen($source) == 1)
	{
		if($source=='i')
		{
				$arabic[]="آ";
		}

	}

	$arabic_data[]=" ";
			for ($i = 0; $i < strlen($source); $i++) 
			{
				$char = substr($source, $i, 1);

				// check for arabic characters in the string and just output them if they exist
				if(ord(substr($source, 0, 1))==216 || ord(substr($source, 0, 1))==217)
				{	
					$arabic_data[]= substr($source, $i, 2);
					$i++;
					continue;
				}

				$char = strtolower($char);
				switch($char)
				{

					case 'a':
						$arabic_data[]='ا'; // alif
					break;
					case 'b':
						$arabic_data[]='ب'; // bah
					break;
					case 'c':
						$arabic_data[]='ك'; // kah
					break;
					case 'd':
						$arabic_data[]='د'; // dal
					break;
					case 'e':
						$arabic_data[]='ي'; // yeh
					break;
					case 'f':
						$arabic_data[]='ف'; // feh
					break;
					case 'g':
						$arabic_data[]='غ'; // ghaim
					break;
					case 'h':
						$arabic_data[]='ه'; // heh
					break;
					case 'i':
						$arabic_data[]='ي'; // yeh
					break;
					case 'j':
						$arabic_data[]='ج'; // jeem
					break;
					case 'k':
						$arabic_data[]='ك'; // kaf
					break;
					case 'l':
						$arabic_data[]='ل'; // lam
					break;
					case 'm':
						$arabic_data[]='م'; // meem
					break;
					case 'n':
						$arabic_data[]='ن'; // noon
					break;
					case 'o':
						$arabic_data[]='و'; // waw
					break;
					case 'p':
						$arabic_data[]='ب'; // beh
					break;
					case 'q':
						$arabic_data[]='ك'; // kah
					break;
					case 'r':
						$arabic_data[]='ر'; // reh
					break;
					case 's':
						$arabic_data[]='س'; // seen
					break;
					case 't':
						$arabic_data[]='ت'; // teh
					break;
					case 'u':
						$arabic_data[]='و'; // waw
					break;
					case 'v':
						$arabic_data[]='ڤ'; // veh
					break;
					case 'w':
						$arabic_data[]='و'; // waw
					break;
					case 'x':
						$arabic_data[]='كس'; // kaf and seen
					break;
					case 'y':
						$arabic_data[]='ي'; // yeh
					break;
					case 'z':
						$arabic_data[]='ز'; // zain
					break;
					default:
						$arabic_data[]=$char;			
					break;
				}
			}

return implode('', $arabic_data);

}

//----------------------------------------------------------------------------------------------------

function print_invoices()
{
	global $path_to_root, $SysPrefs;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$pay_service = $_POST['PARAM_4'];
	$comments = $_POST['PARAM_5'];
	$customer = $_POST['PARAM_6'];
	$orientation = $_POST['PARAM_7'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

 	$fno = explode("-", $from);
	$tno = explode("-", $to);
	$from = min($fno[0], $tno[0]);
	$to = max($fno[0], $tno[0]);

	//-------------code-Descr-Qty--uom--tax--prc--Disc-Tot--//
	$cols = array(2, 35, 100, 210, 250, 280, 330, 380, 430, 470, 520);

	// $headers in doctext.inc
	$aligns = array('left',	'left','left',	'left', 'left', 'right', 'right', 'right', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
		$rep = new FrontReport(_('INVOICE'), "InvoiceBulk", user_pagesize(), 9, $orientation);
	if ($orientation == 'L')
		recalculate_cols($cols);

	$range = Array();
	if ($currency == ALL_TEXT)
		$range = get_invoice_range($from, $to);
	else
		$range = get_invoice_range($from, $to, $currency);

	while($row = db_fetch($range))
	{
			if (!exists_customer_trans(ST_SALESINVOICE, $row['trans_no']))
				continue;
			$sign = 1;
			$myrow = get_customer_trans($row['trans_no'], ST_SALESINVOICE);

			if ($customer && $myrow['debtor_no'] != $customer) {
				continue;
			}
//			if ($currency != ALL_TEXT && $myrow['curr_code'] != $currency) {
//				continue;
//			}
			
			$baccount = get_default_bank_account($myrow['curr_code']);
			$params['bankaccount'] = $baccount['id'];

			$branch = get_branch($myrow["branch_code"]);
			$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);
			if ($email == 1)
			{
				$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
				$rep->title = _('TAX INVOICE');
				$rep->filename = "Invoice" . $myrow['reference'] . ".pdf";
			}	
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);

			$contacts = get_branch_contacts($branch['branch_code'], 'invoice', $branch['debtor_no'], true);
			$baccount['payment_service'] = $pay_service;
			$rep->SetCommonData($myrow, $branch, $sales_order, $baccount, ST_SALESINVOICE, $contacts);
			$rep->SetHeaderType('Header6');
			$rep->NewPage();
			// calculate summary start row for later use
			$summary_start_row = $rep->bottomMargin + (20.7 * $rep->lineHeight);

			$show_this_payment = $rep->formData['prepaid'] == 'partial'; // include payments invoiced here in summary

			if ($rep->formData['prepaid'])
			{
				
				$result = get_sales_order_invoices($myrow['order_']);
				$prepayments = array();
				while($inv = db_fetch($result))
				{
					$prepayments[] = $inv;
					if ($inv['trans_no'] == $row['trans_no'])
					break;
				}

				if (count($prepayments) > ($show_this_payment ? 0 : 1))
					$summary_start_row += (count($prepayments)) * $rep->lineHeight;
				else
					unset($prepayments);
			}

   			$result = get_customer_trans_details(ST_SALESINVOICE, $row['trans_no']);
			$SubTotal = 0;
			$k=1;
			while ($myrow2=db_fetch($result))
			{
				if ($myrow2["quantity"] == 0)
					continue;
				
			
				
		if ($myrow['tax_included']){		
	
		$myrow2["unit_price"] = $myrow2["unit_price"]-(($myrow2["unit_price"]*5)/(100+5));
		
		//$DisplayPriceTaxIncl = number_format2($DisplayPriceIncluded,$dec);
		}else{
			
			$myrow2["unit_price"] = $myrow2["unit_price"];
		}
		
		$Net = round2($sign * ((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]), user_price_dec());	
		

				
				$SubTotal += $Net;
				
				
				
	    		$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
				
	    		$DisplayQty = number_format2($sign*$myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
	    		$DisplayNet = number_format2($Net,$dec);
	    		if ($myrow2["discount_percent"]==0)
		  			$DisplayDiscount ="";
	    		else
		  			$DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "%";
				
				
				$item = get_item($myrow2['stock_id']);
				
				
				
				
				if($item['exempt']==0){
				$DisplayVat = number_format2($Net*5/100,$dec);
				}
				else{
					$DisplayVat = 0;
				}
				
				$FullUnitPrice = number_format2($Net+$DisplayVat,$dec);
				
				$c=0;
				
				
				$rep->TextCol($c++, $c,	$k, -2);
				$rep->TextCol($c++, $c,	$myrow2['stock_id'], -2);
				$oldrow = $rep->row;
				$rep->TextColLines($c++, $c, $myrow2['StockDescription'], -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				if ($Net != 0.0 || !is_service($myrow2['mb_flag']) || !$SysPrefs->no_zero_lines_amount())
				{
					$rep->TextCol($c++, $c,	$myrow2['units'], -2);
					$rep->TextCol($c++, $c,	$DisplayQty, -2);
					
					$rep->TextCol($c++, $c,	$DisplayPrice, -2);
					
					
					$rep->TextCol($c++, $c,	$DisplayDiscount, -2);
					$rep->TextCol($c++, $c,	$DisplayNet, -2);
					$rep->TextCol($c++, $c,	$DisplayVat, -2);
					$rep->TextCol($c++, $c,	$FullUnitPrice, -2);
				}
				$rep->row = $newrow;
				//$rep->NewLine(1);
				if ($rep->row < $summary_start_row)
					$rep->NewPage();
				
				$k++;
			}

			$memo = get_comments_string(ST_SALESINVOICE, $row['trans_no']);
			
			
			if ($memo != "")
			{
				//$rep->NewLine();
				//$rep->TextColLines(1, 3, $memo, -2);
			}

           if ($myrow['tax_included']){
				$DisplaySubTot = number_format2($SubTotal*1.05,$dec);
			}else{
				$DisplaySubTot = number_format2($SubTotal,$dec);
			}
   			

			// set to start of summary line:
    		$rep->row = $summary_start_row;
			
			/*
			if (isset($prepayments))
			{
				// Partial invoices table
				$rep->TextCol(0, 3,_("Prepayments invoiced to this order up to day:"));
				$rep->TextCol(0, 3,	str_pad('', 150, '_'));
				$rep->cols[2] -= 20;
				$rep->aligns[2] = 'right';
				$rep->NewLine(); $c = 0; $tot_pym=0;
				$rep->TextCol(0, 3,	str_pad('', 150, '_'));
				$rep->TextCol(0, 2, _("Date"));
				$rep->TextCol(2, 3,	_("Invoice reference"));
				$rep->TextCol(3, 4,	_("Amount"));

				foreach ($prepayments as $invoice)
				{
					if ($show_this_payment || ($invoice['reference'] != $myrow['reference']))
					{
						$rep->NewLine();
						$c = 0; $tot_pym += $invoice['prep_amount'];
						$rep->TextCol(0, 2,	sql2date($invoice['tran_date']));
						$rep->TextCol(2, 3,	$invoice['reference']);
						$rep->TextCol(3, 4, number_format2($invoice['prep_amount'], $dec));
					}
					if ($invoice['reference']==$myrow['reference']) break;
				}
				$rep->TextCol(0, 3,	str_pad('', 150, '_'));
				$rep->NewLine();
				$rep->TextCol(0, 2,	_("Total payments:"));
				$rep->TextCol(2, 3,	number_format2($tot_pym, $dec));
			}
			*/
			
			
			$alloc_result = get_allocatable_from_cust_transactions($myrow['debtor_no'], $row['trans_no'], ST_SALESINVOICE);
			if (db_num_rows($alloc_result) != 0){
				
				$rep->TextCol(2, 3,_("Payments"));
				$rep->TextCol(0, 5,	str_pad('', 150, '_'));
				$rep->cols[2] -= 20;
				$rep->aligns[2] = 'right';
				$rep->NewLine(); $c = 0; $tot_payment=0;
				$rep->TextCol(0, 5,	str_pad('', 150, '_'));
				
				$rep->Text($ccol+45, _("Date"));
				$rep->Text($ccol+100, _("Reference"));
				$rep->Text($ccol+160, _("Mode of Payment"));
				$rep->Text($ccol+260, _("Amount"));
				
				$k = $total_allocated = 0;
				while ($alloc_row = db_fetch($alloc_result))
                {
					
				$cust_payment=get_customer_payment_mode_info($alloc_row['type'],$alloc_row['trans_no']); 

                if($cust_payment['mode_of_payment']=='cash'){
                  $mode_of_payment="Cash";
                }
                else if($cust_payment['mode_of_payment']=='ot'){
                  $mode_of_payment="Online Transfer";
                }	
                else if($cust_payment['mode_of_payment']=='card'){				
    	        $mode_of_payment="Visa Card";
                }
                else if($cust_payment['mode_of_payment']=='cheque'){				
    	        $mode_of_payment="Cheque";
                }				
				
				$rep->NewLine();
				$c = 0; $tot_payment += $alloc_row['amt'];
				$rep->Text($ccol+45, sql2date($alloc_row['tran_date']));
				$rep->Text($ccol+100, $alloc_row['reference']);
				$rep->Text($ccol+160, $mode_of_payment);
				$rep->Text($ccol+260, number_format2($alloc_row['amt'],$dec));
				}
				$rep->TextCol(0, 5,	str_pad('', 150, '_'));
				$rep->NewLine();
				$rep->TextCol(0, 2,	_("Total Payments:"));
				$rep->Text($ccol+260, number_format2($tot_payment,$dec));
				$rep->NewLine();
			}
		 


			$doctype = ST_SALESINVOICE;
    		$rep->row = $summary_start_row-35;
			$rep->cols[2] += 20;
			$rep->cols[3] += 20;
			$rep->aligns[3] = 'left';

			$rep->TextCol(5, 8, _("Sub-total"), -2);
			$rep->TextCol(8, 10,	$DisplaySubTot, -2);
			$rep->NewLine();
			if ($myrow['ov_freight'] != 0.0)
			{
   				$DisplayFreight = number_format2($sign*$myrow["ov_freight"],$dec);
				$rep->TextCol(5, 8, _("Shipping"), -2);
				$rep->TextCol(8, 10,	$DisplayFreight, -2);
				$rep->NewLine();
			}	
			$tax_items = get_trans_tax_details(ST_SALESINVOICE, $row['trans_no']);
			$first = true;
    		while ($tax_item = db_fetch($tax_items))
    		{
    			if ($tax_item['amount'] == 0)
    				continue;
    			$DisplayTax = number_format2($sign*$tax_item['amount'], $dec);

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
							$rep->TextCol(5, 8, _("Total Tax Excluded"), -2);
							$rep->TextCol(8, 10,	number_format2($sign*$tax_item['net_amount'], $dec), -2);
							$rep->NewLine();
    					}
						$rep->TextCol(5, 8, $tax_type_name, -2);
						$rep->TextCol(8, 10,	$DisplayTax, -2);
						$first = false;
    				}
    				else
						$rep->TextCol(5, 10, _("Included") . " " . $tax_type_name . _("Amount") . ": " . $DisplayTax, -2);
				}
    			else
    			{
					$rep->TextCol(5, 8, $tax_type_name, -2);
					$rep->TextCol(8, 10,	$DisplayTax, -2);
				}
				$rep->NewLine();
    		}

    		$rep->NewLine();
			$DisplayTotal = number_format2($sign*($myrow["ov_freight"] + $myrow["ov_gst"] +
				$myrow["ov_amount"]+$myrow["ov_freight_tax"]),$dec);
			$rep->Font('bold');
			if (!$myrow['prepaid']) $rep->Font('bold');
				$rep->TextCol(5, 8, $rep->formData['prepaid'] ? _("TOTAL ORDER VAT INCL.") : _("TOTAL INVOICE"), - 2);
			$rep->TextCol(8, 10, $DisplayTotal, -2);
			if ($rep->formData['prepaid'])
			{
				$rep->NewLine();
				$rep->Font('bold');
				$rep->TextCol(5, 8, $rep->formData['prepaid']=='final' ? _("THIS INVOICE") : _("TOTAL INVOICE"), - 2);
				$rep->TextCol(8, 10, number_format2($myrow['prep_amount'], $dec), -2);
			}
			
			
			$words = no_to_words($rep->formData['prepaid'] ? $myrow['prep_amount'] : $myrow['Total'], array( 'type' => ST_SALESINVOICE, 'currency' => $myrow['curr_code']));
			
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
			if ($email == 1)
			{
				$rep->End($email, sprintf(_("Invoice %s from %s"), $myrow['reference'], get_company_pref('coy_name')));
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

