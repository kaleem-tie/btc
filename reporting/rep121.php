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
// Title:	Print SALES QUOTATIONs
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
	$comments = $_POST['PARAM_4'];
	$orientation = $_POST['PARAM_5'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

	$cols = array(2,30, 100, 300,350,380,420,470,540);

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'left', 'right', 'left', 'right', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

    if ($orientation == 'L')
    	recalculate_cols($cols);

	for ($i = $from; $i <= $to; $i++)
	{
		$myrow = get_sales_order_header($i, ST_INSSALESQUOTE);
		if ($currency != ALL_TEXT && $myrow['curr_code'] != $currency) {
			continue;
		}
		$baccount = get_default_bank_account($myrow['curr_code']);
		$params['bankaccount'] = $baccount['id'];
		$branch = get_branch($myrow["branch_code"]);

        if ($i == $from || $email == 1)
            $rep = new FrontReport(_("SALES QUOTATION"), "SalesOrderBulk", user_pagesize(), 9, $orientation);
        if ($print_as_quote == 1)
        {
            $rep->title = _('QUOTE');
            $rep->filename = "Quote" . $i . ".pdf";
        }
        else
        {
            $rep->title = _("SALES QUOTATION");
            $rep->filename = "SalesOrder" . $i . ".pdf";
        }		
		$rep->SetHeaderType('Header22');
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);

		$contacts = get_branch_contacts($branch['branch_code'], 'order', $branch['debtor_no'], true);
		$rep->SetCommonData($myrow, $branch, $myrow, $baccount, ST_INSSALESQUOTE, $contacts);
		$rep->NewPage();

		$result = get_sales_insquote_details($i, ST_INSSALESQUOTE);
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
			$DisplayQty = number_format2($myrow2["quantity"],get_qty_dec($myrow2['stk_code']));
			$DisplayNet = number_format2($Net,$dec);
			if ($myrow2["discount_percent"]==0)
				$DisplayDiscount ="";
			else
				$DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "%";
			
			$rep->TextCol(0, 1,	$k, -2);
			$rep->TextCol(1, 2,	$myrow2['item_number'], -2);
			$oldrow = $rep->row;
			$rep->TextColLines(2, 3, $myrow2['description'], -2);
			$newrow = $rep->row;
			$rep->row = $oldrow;
			if ($Net != 0.0 || !is_service($myrow2['mb_flag']) || !$SysPrefs->no_zero_lines_amount())
			{
				$rep->TextCol(3, 4,	$DisplayQty, -2);
				$rep->TextCol(4, 5,	$myrow2['unit'], -2);
				$rep->TextCol(5, 6,	$DisplayPrice, -2);
				$rep->TextCol(6, 7,	$DisplayDiscount, -2);
				$rep->TextCol(7, 8,	$DisplayNet, -2);
			}
			$rep->row = $newrow;
			if ($rep->row < $rep->bottomMargin + (18 * $rep->lineHeight))
				$rep->NewPage();
			$k++;
		}
		if ($myrow['comments'] != "")
		{
			$rep->NewLine();
			$rep->TextColLines(1, 3, $myrow['comments'], -2);
		}
		$DisplaySubTot = number_format2($SubTotal,$dec);

		$rep->row = $rep->bottomMargin + (18 * $rep->lineHeight);
		$doctype = ST_INSSALESQUOTE;

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
		$words = price_in_words($myrow["freight_cost"] + $SubTotal, ST_INSSALESQUOTE);
		if ($words != "")
		{
			$rep->NewLine(1);
			$rep->TextCol(1, 8, $myrow['curr_code'] . ": " . $words, - 2);
		}	
		$rep->Font();
        if ($i == $to || $email == 1)
            $rep->End($email);
		
	}
}

