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
// Title:	Print Sales Enquiries
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

//----------------------------------------------------------------------------------------------------

print_sales_quotations();

function print_sales_quotations()
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

	$pictures = $SysPrefs->print_item_images_on_quote();
	// If you want a larger image, then increase pic_height f.i.
	// $SysPrefs->pic_height += 25;
	
	//$cols = array(4, 60, 225, 300, 325, 385, 450, 515);
	// $headers in doctext.inc
	//$aligns = array('left',	'left',	'right', 'left', 'right', 'right', 'right');
	
	$cols = array(4, 35, 130, 400, 470,540);

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'left', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
		$rep = new FrontReport(_("SALES ENQUIRY"), "SalesEnquiryBulk", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

	for ($i = $from; $i <= $to; $i++)
	{
		$myrow = get_sales_order_header($i, ST_INSSALESENQ);
		if ($currency != ALL_TEXT && $myrow['curr_code'] != $currency) {
			continue;
		}
		$baccount = get_default_bank_account($myrow['curr_code']);
		$params['bankaccount'] = $baccount['id'];
		$branch = get_branch($myrow["branch_code"]);
		if ($email == 1)
		{
			$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
			if ($SysPrefs->print_invoice_no() == 1)
				$rep->filename = "SalesQuotation" . $i . ".pdf";
			else	
				$rep->filename = "SalesQuotation" . $myrow['reference'] . ".pdf";
		}
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);

		$contacts = get_branch_contacts($branch['branch_code'], 'order', $branch['debtor_no'], true);
		$rep->SetCommonData($myrow, $branch, $myrow, $baccount, ST_INSSALESENQ, $contacts);
		$rep->SetHeaderType('Header22');
		$rep->NewPage();

		$result = get_sales_insquote_details($i, ST_INSSALESENQ);
		$SubTotal = 0;
		$items = $prices = array();
		$k=1;
		while ($myrow2=db_fetch($result))
		{
			$Net = round2(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
			   user_price_dec());
			$prices[] = $Net;
			$items[] = $myrow2['stk_code'];
			$SubTotal += $Net;
			
			$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
			$DisplayTotalPrice = number_format2($myrow2["total_price"],$dec);
			$DisplayQty = number_format2($myrow2["quantity"],2);
			$DisplayNet = number_format2($Net,$dec);
			if ($myrow2["discount_percent"]==0)
				$DisplayDiscount ="";
			else
				$DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "%";
			$rep->TextCol(0, 1,	$k, -2);
			
			//$rep->TextCol(1, 2, $myrow2['sap_code'], -2);
			//$rep->TextCol(2, 3,	$myrow2['mesc'], -2);
			$rep->TextCol(1, 2,	$myrow2['item_number'], -2);
			$oldrow = $rep->row;
			$rep->TextColLines(2, 3, $myrow2['description'], -2);
			$newrow = $rep->row;
			$rep->row = $oldrow;
		
			//$rep->TextCol(5, 6,	$myrow2['part_no'], -2);
			$rep->TextCol(3, 4,	$DisplayQty, -2);
			$rep->TextCol(4, 5,	$myrow2['unit'], -2);
			//$rep->TextCol(5, 6,	$DisplayPrice, -2);
			//$rep->TextCol(6, 7,$DisplayTotalPrice, -2);
			//$rep->TextCol(10, 11,	$myrow2['item_delivery'], -2);
			//$rep->TextCol(11, 12,	$myrow2['mnfr'], -2);
			//$rep->TextCol(12, 13,	$myrow2['origin'], -2);
			
			//$rep->row = $newrow;
			$k++;
			if ($pictures)
			{
				$image = company_path(). "/images/" . item_img_name($myrow2['stk_code']) . ".jpg";
				if (file_exists($image))
				{
					if ($rep->row - $SysPrefs->pic_height < $rep->bottomMargin)
						$rep->NewPage();
					$rep->AddImage($image, $rep->cols[1], $rep->row - $SysPrefs->pic_height, 0, $SysPrefs->pic_height);
					$rep->row -= $SysPrefs->pic_height;
					$rep->NewLine();
				}
			}
			//$rep->NewLine();
			$rep->row = $newrow;
			if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
				$rep->NewPage();
		}
		if ($myrow['comments'] != "")
		{
		
	        //$rep->NewPage();
			$rep->NewLine();
			$rep->SetFont('helvetica', 'B', 9);
			$rep->Text($ccol+45 , _("Comments : "));
			$rep->SetFont('', '', 0);
			$rep->NewLine();
			$rep->TextColLines(0, 4, $myrow['comments'], -2);
		}
		/* $DisplaySubTot = number_format2($SubTotal,$dec);

		$rep->row = $rep->bottomMargin + (18 * $rep->lineHeight);
		$doctype = ST_INSSALESENQ;

		$rep->TextCol(4, 6, _("Sub-total"), -2);
		$rep->TextCol(6, 7,	$DisplaySubTot, -2);
		$rep->NewLine(); */
		
		/*
		$DisplayTotal = number_format2($SubTotal, $dec);
		if ($myrow['tax_included'] == 0) {
			$rep->TextCol(3, 6, _("TOTAL ENQUIRY EX VAT"), - 2);
			$rep->TextCol(6, 7,	$DisplayTotal, -2);
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
						$rep->TextCol(3, 6, _("Total Tax Excluded"), -2);
						$rep->TextCol(6, 7,	number_format2($tax_item['net_amount'], $dec), -2);
						$rep->NewLine();
					}
					$rep->TextCol(3, 6, $tax_type_name, -2);
					$rep->TextCol(6, 7,	$DisplayTax, -2);
					$first = false;
				}
				else
					$rep->TextCol(3, 7, _("Included") . " " . $tax_type_name . " " . _("Amount") . ": " . $DisplayTax, -2);
			}
			else
			{
				$SubTotal += $tax_item['Value'];
				$rep->TextCol(3, 6, $tax_type_name, -2);
				$rep->TextCol(6, 7,	$DisplayTax, -2);
			}
			$rep->NewLine();
		}
		*/

		$rep->NewLine();
		
		
		$rep->row = $rep->bottomMargin + ( 5 * $rep->lineHeight);
		$rep->SetFont('helvetica', 'B', 9);
		$rep->Text($ccol+45 , _("For ").$rep->company['coy_name']);
		$rep->Text($mcol + 430, _("Customer Name & Signature"));
		$rep->SetFont('', '', 0);

		
		$rep->Font();
		
		
		
		if ($email == 1)
		{
			if ($SysPrefs->print_invoice_no() == 1)
				$myrow['reference'] = $i;
			$rep->End($email);
		}
	}
	if ($email == 0)
		$rep->End();
}

