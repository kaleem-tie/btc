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
// Title:	Print Sales Orders
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

//----------------------------------------------------------------------------------------------------

print_sales_orders();

function print_sales_orders()
{
	global $path_to_root, $SysPrefs;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$print_as_quote = $_POST['PARAM_4'];
	$comments = $_POST['PARAM_5'];
	$orientation = $_POST['PARAM_6'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

	$cols = array(2,30, 100, 300,350,380,420,470,540);

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'left', 'right', 'left', 'center', 'right', 'right');

	$params = array('comments' => $comments, 'print_quote' => $print_as_quote);

	$cur = get_company_Pref('curr_default');

    if ($orientation == 'L')
    	recalculate_cols($cols);

	for ($i = $from; $i <= $to; $i++)
	{
		$myrow = get_sales_order_header($i, ST_SALESORDER);
		if ($currency != ALL_TEXT && $myrow['curr_code'] != $currency) {
			continue;
		}
		$baccount = get_default_bank_account($myrow['curr_code']);
		$params['bankaccount'] = $baccount['id'];
		$branch = get_branch($myrow["branch_code"]);

        if ($i == $from || $email == 1)
            $rep = new FrontReport(_("SALES ORDER"), "SalesOrderBulk", user_pagesize(), 9, $orientation);
        if ($print_as_quote == 1)
        {
            $rep->title = _('QUOTE');
            $rep->filename = "Quote" . $i . ".pdf";
        }
        else
        {
            $rep->title = _("SALES ORDER");
            $rep->filename = "SalesOrder" . $i . ".pdf";
        }		
		$rep->SetHeaderType('Header30');
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);

		$contacts = get_branch_contacts($branch['branch_code'], 'order', $branch['debtor_no'], true);
		$rep->SetCommonData($myrow, $branch, $myrow, $baccount, ST_SALESORDER, $contacts);
		$rep->NewPage();

		$result = get_sales_order_details($i, ST_SALESORDER);
		$SubTotal = 0;
		$items = $prices = array();
		$k=1;
        $gross_total = $total_disc_amount = 0;
		while ($myrow2=db_fetch($result))
		{
			/* $Net = round2(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
			   user_price_dec()); */

           $discount_amount =  $myrow2["unit_price"] * $myrow2["quantity"] * $myrow2["discount_percent"]/100;	
			$gross_total +=  $myrow2["quantity"]*$myrow2["unit_price"];	

			$Net = round2(($myrow2["quantity"] * $myrow2["unit_price"])-($myrow2["quantity"] * $myrow2["unit_price"]* ($myrow2["discount_percent"]/100)),
	               user_price_dec());
			$prices[] = $Net;
			$items[] = $myrow2['stk_code'];
			$SubTotal += $Net;
			$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
			$DisplayQty = number_format2($myrow2["quantity"],get_qty_dec($myrow2['stk_code']));
			
			$DisplayFOCQty = number_format2($myrow2["foc_quantity"],get_qty_dec($myrow2['stock_id']));
			
			$DisplayNet = number_format2($Net,$dec);
			if ($myrow2["discount_percent"]==0)
				$DisplayDiscount ="";
			else
				$DisplayDiscount = number_format2($myrow2["discount_percent"],user_percent_dec()) . "%";
			$rep->TextCol(0, 1,	$k, -2);
			$rep->TextCol(1, 2,	$myrow2['stk_code'], -2);
			$oldrow = $rep->row;
			$rep->TextColLines(2, 3, $myrow2['description'], -2);
			$newrow = $rep->row;
			$rep->row = $oldrow;
			if ($Net != 0.0 || !is_service($myrow2['mb_flag']) || !$SysPrefs->no_zero_lines_amount())
			{
				$rep->TextCol(3, 4,	$DisplayQty, -2);
				//$rep->TextCol(4, 5,	$myrow2['units'], -2);
				if($myrow2['unit']==1){
				 $item_info = get_item_edit_info($myrow2["stk_code"]);	
				$rep->TextCol(4, 5,	$item_info["units"], -2);
				}
				else if($myrow2["unit"]==2){
		         $sec_unit_info = get_item_sec_unit_info($myrow2["stk_code"]);
	             $rep->TextCol(4, 5,$sec_unit_info["sec_unit_name"], -2);
                }	
				
				$rep->TextCol(5, 6,	$DisplayPrice, -2);
				$rep->TextCol(6, 7,	$DisplayDiscount, -2);
				$rep->TextCol(7, 8,	$DisplayNet, -2);
			}
			$rep->row = $newrow;
            $k++;
			$rep->NewLine(0.5);
			
			if($myrow2["foc_quantity"]!=0){
            $rep->TextCol(0, 1,	$k, -2);
			$rep->TextCol(1, 2,	$myrow2['stk_code'], -2);
			$oldrow = $rep->row;
			$rep->TextColLines(2, 3, $myrow2['description'], -2);
			$newrow = $rep->row;
			$rep->row = $oldrow;
			$rep->TextCol(3, 4,	$DisplayFOCQty, -2);
            $rep->TextCol(4, 5,	_("FOC"), -2); 
            $rep->TextCol(5, 6,	'0.000', -2);
			$rep->TextCol(6, 7,	'0.000', -2);
			$rep->TextCol(7, 8,	'0.000', -2);
			$rep->row = $newrow;
			$rep->NewLine(0.5);
			$k++;
			}
			
			
			
			if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
				$rep->NewPage();
			//$k++;

           $total_disc_amount += $discount_amount;
		}
		if ($myrow['comments'] != "")
		{
			 if ($rep->row < $rep->bottomMargin + (13 * $rep->lineHeight))
             $rep->NewPage();
              
            $rep->NewLine();
			$rep->SetFont('helvetica', 'B', 9);
			$rep->Text($ccol+45 , _("Comments : "));
			$rep->SetFont('', '', 0);
			$rep->NewLine();
			$rep->TextColLines(0, 4, $myrow['comments'], -2);
		}
		$DisplaySubTot = number_format2($SubTotal,$dec);
        

		$rep->row = $rep->bottomMargin + (13 * $rep->lineHeight);
		$doctype = ST_SALESORDER;

        $DisplayGrossTot = number_format2($gross_total,$dec);
            $rep->TextCol(4, 7, _("Gross Total"), -2);
			$rep->TextCol(7, 8,	$DisplayGrossTot, -2);
			$rep->NewLine();

            $DisplayDiscAmtTot = number_format2($total_disc_amount,$dec);
            $rep->TextCol(4, 7, _("Discount Amount"), -2);
			$rep->TextCol(7, 8,	$DisplayDiscAmtTot, -2);
			$rep->NewLine();

		$rep->TextCol(4, 7, _("Sub-total"), -2);
		$rep->TextCol(7, 8,	$DisplaySubTot, -2);
		$rep->NewLine();
		if ($myrow['freight_cost'] != 0.0)
		{
			$DisplayFreight = number_format2($myrow["freight_cost"],$dec);
			$rep->TextCol(4, 7, _("Shipping"), -2);
			$rep->TextCol(7, 8,	$DisplayFreight, -2);
			$rep->NewLine();
		}	
		$DisplayTotal = number_format2($myrow["freight_cost"] + $SubTotal, $dec);
		if ($myrow['tax_included'] == 0) {
			$rep->TextCol(4, 7, _("TOTAL ORDER EX VAT"), - 2);
			$rep->TextCol(7, 8,	$DisplayTotal, -2);
			$rep->NewLine();
		}

		$tax_items = get_tax_for_items($items, $prices, $myrow["freight_cost"],
		  $myrow['tax_group_id'], $myrow['tax_included'],  null);
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
						$rep->TextCol(4, 7, _("Total Tax Excluded"), -2);
						$rep->TextCol(7, 8,	number_format2($tax_item['net_amount'], $dec), -2);
						$rep->NewLine();
					}
					$rep->TextCol(4, 7, $tax_type_name, -2);
					$rep->TextCol(7, 8,	$DisplayTax, -2);
					$first = false;
				}
				else
					$rep->Text($ccol+300 , _("Included") . " " . $tax_type_name . " " . _("Amount"). ": " . $DisplayTax);
					//$rep->TextCol(2, 8, _("Included") . " " . $tax_type_name . " " . _("Amount"). ": " . $DisplayTax, -2);
			}
			else
			{
				$SubTotal += $tax_item['Value'];
				$rep->TextCol(4, 7, $tax_type_name, -2);
				$rep->TextCol(7, 8,	$DisplayTax, -2);
			}
			$rep->NewLine();
		}

		$rep->NewLine();

		$DisplayTotal = number_format2($myrow["freight_cost"] + $SubTotal, $dec);
		$rep->Font('bold');
		$rep->TextCol(4, 7, _("TOTAL ORDER VAT INCL."), - 2);
		$rep->TextCol(7, 8,	$DisplayTotal, -2);
		$words = price_in_words($myrow["freight_cost"] + $SubTotal, ST_SALESORDER);
		if ($words != "")
		{
			$rep->NewLine(1);
			$rep->TextCol(1, 8, $myrow['curr_code'] . ": " . $words, - 2);
		}	
		
		$rep->SetFont('helvetica', 'B', 9);
		
        $rep->NewLine(1);
		$rep->TextWrapLines($mcol+45,$mcol+425,_("PLEASE NOTE : 5% VAT will be applied on invoices as per VAT laws of Sultanate of Oman."));
		$rep->SetFont('', '', 0);
		$rep->NewLine();
		
		
	    //if ($rep->row < $rep->bottomMargin + (5 * $rep->lineHeight))
        //$rep->NewPage();
		$rep->SetFont('helvetica', 'B', 9);
		$rep->TextCol(0, 7,"Terms & Conditions", -2);
		$rep->NewLine();
		$rep->SetFont('', '', 0);
		$rep->TextWrapLines($mcol+45,$mcol+425,_("Any variation in QUANTITY & QUALITY must be reported within two days of the delivery. Thereafter NO claims shall be entertained."));

		//$rep->NewLine(2);	
		//$preared_user = get_transaction_prepared_by(ST_SALESORDER, $i);
	/*$rep->SetFont('helvetica', 'B', 9);
	$rep->Text($ccol+45 , _("For ").$rep->company['coy_name']);
	$rep->Text($mcol + 430, _("Customer Name & Signature"));
	$rep->SetFont('', '', 0);*/
		
		$rep->Font();
        if ($i == $to || $email == 1)
            $rep->End($email);
		
	}
}

