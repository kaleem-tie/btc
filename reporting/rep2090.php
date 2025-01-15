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
	'SA_SUPPTRANSVIEW' : 'SA_SUPPBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Purchase Orders
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/db/crm_contacts_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

//----------------------------------------------------------------------------------------------------

print_gen_po();

//----------------------------------------------------------------------------------------------------
function get_gen_supp_po($order_no)
{
   	$sql = "SELECT po.*, loc.location_name
		FROM ".TB_PREF."gen_purch_orders po,"
			.TB_PREF."locations loc
		WHERE po.trans_type=".ST_GEN_PURCHORDER."
		AND loc.loc_code = into_stock_location
		AND po.order_no = ".db_escape($order_no);
   	$result = db_query($sql, "The order cannot be retrieved");
    return db_fetch($result);
}

function get_gen_po_details($order_no)
{
	$sql = "SELECT poline.*
		FROM ".TB_PREF."gen_purch_order_details poline
		WHERE order_no =".db_escape($order_no)." AND poline.trans_type=".ST_GEN_PURCHORDER."";
	$sql .= " ORDER BY po_detail_item";
	return db_query($sql, "Retreive order Line Items");
}

function print_gen_po()
{
	global $path_to_root, $SysPrefs;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$orientation = $_POST['PARAM_5'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

	$cols = array(2, 30, 100, 230, 270, 300, 350, 400, 450, 520);

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'left', 'left', 'center', 'right', 'right', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
		$rep = new FrontReport(_('GENERAL PURCHASE ORDER'), "PurchaseOrderBulk", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

	for ($i = $from; $i <= $to; $i++)
	{
		$myrow = get_gen_supp_po($i);
		

		if ($email == 1)
		{
			$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
			$rep->title = _('PURCHASE ORDER');
			$rep->filename = "PurchaseOrder" . $i . ".pdf";
		}	
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);

		$contacts = array();
		$rep->SetCommonData($myrow, null, $myrow, $baccount, ST_GEN_PURCHORDER, $contacts);
		$rep->SetHeaderType('Header21');
		$rep->NewPage();

		$result = get_gen_po_details($i);
		$SubTotal = 0;
		$items = $prices = array();
		$sl_no=1;
		$flag = 0;
		while ($myrow2=db_fetch($result))
		{
			
			$Net = round2(($myrow2["quantity_ordered"] * $myrow2["unit_price"])-($myrow2["quantity_ordered"] * $myrow2["unit_price"]* ($myrow2["discount_percent"]/100)),
	               user_price_dec());   
				   
			$prices[] = $Net;
			$items[] = $myrow2['item_code'];
			$SubTotal += $Net;
			$dec2 = 0;
			$DisplayPrice = price_decimal_format($myrow2["unit_price"],$dec2);
			$DisplayQty = number_format2($myrow2["quantity_ordered"],get_qty_dec($myrow2['item_code']));
			$DisplayNet = number_format2($Net,$dec);
			
				if ($myrow2["discount_percent"]==0)
				$DisplayDiscount ="";
			    else
				$DisplayDiscount = number_format2($myrow2["discount_percent"],user_percent_dec()) . "%";
			
			if ($SysPrefs->show_po_item_codes()) {
			$data = get_purchase_data($myrow['supplier_id'], $myrow2['item_code']);
			//if($data !== false)
			// $rep->TextCol(0, 1,	$data['supplier_description'], -2);
			// else
				
			$rep->TextCol(0, 1,	$sl_no, -2);
			
				$rep->TextCol(1, 2,	$myrow2['item_code'], -2);
				$oldrow = $rep->row;
				if($data !== false)
				$rep->TextColLines(2, 4, $myrow2['description'].' ('.	$data['supplier_description'].')', -2);
				else
				$rep->TextColLines(2, 4, $myrow2['description'], -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
			} else
			{
				$$oldrow = $rep->row;
			    if($data !== false)
				$rep->TextColLines(2, 4, $myrow2['description'].' ('.	$data['supplier_description']. ')', -2);
				else
				$rep->TextColLines(2, 4, $myrow2['description'], -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
			}
			//$rep->TextCol(3, 4,	sql2date($myrow2['delivery_date']), -2);
			$rep->TextCol(4, 5,	$myrow2['units'], -2);
			$rep->TextCol(5, 6,	$DisplayQty, -2);
			
			$rep->TextCol(6, 7,	$DisplayPrice, -2);
			$rep->TextCol(7, 8,	$DisplayDiscount, -2);
			$rep->TextCol(8, 9,	$DisplayNet, -2);
			//$rep->NewLine(1);
			$rep->row = $newrow;
			  if ($rep->row < $rep->bottomMargin + (10 * $rep->lineHeight))
			   {
				$rep->NewPage();
				$flag=1;
			   }	
			
			$sl_no++;
		}
		
		$DisplaySubTot = number_format2($SubTotal,$dec);

		//$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
		$rep->NewLine(4);
		$doctype = ST_GEN_PURCHORDER;
		
		 
        
	    if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
                $rep->NewPage();
		$rep->TextCol(5, 8, _("Sub-total"), -2);
		$rep->TextCol(8, 9,	$DisplaySubTot, -2);
		$rep->NewLine();
		
		if ($myrow['freight_cost'] != 0.0)
		{
			if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
                $rep->NewPage();
			$DisplayFreight = number_format2($myrow["freight_cost"],$dec);
			$rep->TextCol(5, 8, _("Freight Charges"), -2);
			$rep->TextCol(8, 9,	$DisplayFreight, -2);
			$rep->NewLine();
		}
		
		if ($myrow['additional_charges'] != 0.0)
		{
			if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
                $rep->NewPage();
			$DisplayAdditionalCharges = number_format2($myrow["additional_charges"],$dec);
			$rep->TextCol(5, 8, _("Additional Charges"), -2);
			$rep->TextCol(8, 9,	$DisplayAdditionalCharges, -2);
			$rep->NewLine();
		}
		
		if ($myrow['packing_charges'] != 0.0)
		{
			if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
                $rep->NewPage();
			$DisplayPacking = number_format2($myrow["packing_charges"],$dec);
			$rep->TextCol(5, 8, _("Packing Charges"), -2);
			$rep->TextCol(8, 9,	$DisplayPacking, -2);
			$rep->NewLine();
		}
		
		if ($myrow['other_charges'] != 0.0)
		{
			if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
                $rep->NewPage();
			$DisplayOther = number_format2($myrow["other_charges"],$dec);
			$rep->TextCol(5, 8, _("Other Charges"), -2);
			$rep->TextCol(8, 9,	$DisplayOther, -2);
			$rep->NewLine();
		}

	      $tax_items = get_tax_for_items($items, $prices, $myrow["freight_cost"],
		  $myrow['tax_group_id'], $myrow['tax_included'],  null, TCA_LINES,
		  $myrow["additional_charges"],$myrow["packing_charges"],$myrow["other_charges"]);
		 
        if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
            $rep->NewPage();		 
		$first = true;
		foreach($tax_items as $tax_item)
		{
			if ($tax_item['Value'] == 0)
				continue;
			$DisplayTax = number_format2($tax_item['Value'], $dec);

			$tax_type_name = $tax_item['tax_type_name'];

			if ($myrow['tax_included'])
			{
				if ($SysPrefs->alternative_tax_include_on_docs() == 1)
				{
					if ($first)
					{
						$rep->TextCol(5, 8, _("Total Tax Excluded"), -2);
						$rep->TextCol(8, 9,	number_format2($tax_item['net_amount'], $dec), -2);
						$rep->NewLine();
					}
					$rep->TextCol(5, 8, $tax_type_name, -2);
					$rep->TextCol(8, 9,	$DisplayTax, -2);
					$first = false;
				}
				else
					$rep->TextCol(4, 8, _("Included") . " " . $tax_type_name . _("Amount") . ": " . $DisplayTax, -2);
			}
			else
			{
				$SubTotal += $tax_item['Value'];
				$rep->TextCol(5, 8, $tax_type_name, -2);
				$rep->TextCol(8, 9,	$DisplayTax, -2);
			}
			$rep->NewLine();
		}

		$rep->NewLine();
		$DisplayTotal = number_format2($SubTotal + $myrow["freight_cost"]+ $myrow["additional_charges"] +$myrow["packing_charges"]+ $myrow["other_charges"], $dec);
		//$rep->Font('bold');
		//$rep->TextCol(5, 8, _("TOTAL PO"), - 2);
		//$rep->TextCol(8, 9,	$DisplayTotal, -2);
		$words = no_to_words($SubTotal + $myrow["freight_cost"]+ $myrow["additional_charges"] +$myrow["packing_charges"]+ $myrow["other_charges"], array( 'type' => ST_GEN_PURCHORDER, 'currency' => $myrow['curr_code']));
			
		$hundredth_name=get_currency_hundredth_name($myrow['curr_code']);
		$order_amount=$SubTotal;
		list($whole, $decimal) = explode('.', $order_amount);
			
		if($decimal)
			$words1=" ".$hundredth_name." only";
		else
			$words1=" only";	
		
		if ($rep->row < $rep->bottomMargin + (7* $rep->lineHeight))
        $rep->NewPage();
	    $rep->Line($rep->row+10);
		$rep->NewLine();
		$rep->SetFont('helvetica', 'B', 9);
		$rep->TextWrapLines($mcol+60,$mcol+425,$words.$words1);
		$rep->TextCol(5, 8, _("Net Amount"), -2);
		$rep->TextCol(8, 9,	$DisplayTotal, -2);
		$rep->SetFont('', '', 0);
		$rep->NewLine();
		$rep->Line($rep->row+10);
		
		if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
        $rep->NewPage();
	    $rep->Text($mcol+ 45 ,_("Delivery Terms"));
		$rep->Text($mcol+ 115 ,_(":"));
		$rep->TextWrapLines($mcol+125,$mcol+425,$myrow['delivery_terms']);
		$rep->NewLine();
		if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
           $rep->NewPage();
		$rep->TextWrapLines($mcol+45,$mcol+425,$myrow['comments']);
		
    
		
		
	$rep->row = $rep->bottomMargin + (6 * $rep->lineHeight);
		if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
          $rep->NewPage();
	    $rep->NewLine(2);
$rep->SetFont('helvetica', 'B', 9);
$rep->Text($ccol + 95, str_pad('', 12, '_'));
$rep->Text($ccol + 270, str_pad('', 12, '_'));
$rep->Text($ccol + 445, str_pad('', 12, '_'));
$rep->NewLine();
$rep->Text($mcol + 100, _("Prepared By"));
$rep->Text($mcol + 270, _("Checked By"));
$rep->Text($mcol + 450, _("Approved By"));                

// Reset font
$rep->SetFont('', '', 0);

		
		
		/*
		if ($myrow['comments'] != "")
		{
			$rep->NewPage();
			$rep->TextColLines(1, 4, $myrow['comments'], -2);
		}
		*/
		$rep->Font();
		
		
		if ($email == 1)
		{
			$myrow['DebtorName'] = $myrow['supp_name'];

			if ($myrow['reference'] == "")
				$myrow['reference'] = $myrow['order_no'];
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

