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
// Creator:	Janusz Dobrwolski
// date_:	2008-01-14
// Title:	Print Delivery Notes
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

//----------------------------------------------------------------------------------------------------

print_deliveries();

function get_sales_delivery_location_name($delivery_no, $trans_type)
{
	$sql = "SELECT loc_code FROM ".TB_PREF."stock_moves 
	WHERE trans_no = ".db_escape($delivery_no)." 
	AND type = ".db_escape($trans_type)." GROUP By trans_no";
	$result = db_query($sql, "order Retreival");
	return db_fetch($result);
}

//----------------------------------------------------------------------------------------------------

function print_deliveries()
{
	global $path_to_root, $SysPrefs;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$email = $_POST['PARAM_2'];
	$packing_slip = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$orientation = $_POST['PARAM_5'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

	$fno = explode("-", $from);
	$tno = explode("-", $to);
	$from = min($fno[0], $tno[0]);
	$to = max($fno[0], $tno[0]);

	$cols = array(2, 30, 130, 400, 450, 500);

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'left', 'center', 'right');

	$params = array('comments' => $comments, 'packing_slip' => $packing_slip);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
	{
		if ($packing_slip == 0)
			$rep = new FrontReport(_('DELIVERY'), "DeliveryNoteBulk", user_pagesize(), 9, $orientation);
		else
			$rep = new FrontReport(_('PACKING SLIP'), "PackingSlipBulk", user_pagesize(), 9, $orientation);
	}
    if ($orientation == 'L')
    	recalculate_cols($cols);
	for ($i = $from; $i <= $to; $i++)
	{
			if (!exists_customer_trans(ST_CUSTDELIVERY, $i))
				continue;
			$myrow = get_customer_trans($i, ST_CUSTDELIVERY);
			$branch = get_branch($myrow["branch_code"]);
			$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER); // ?
			if ($email == 1)
			{
				$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
				if ($packing_slip == 0)
				{
					$rep->title = _('DELIVERY NOTE');
					$rep->filename = "Delivery" . $myrow['reference'] . ".pdf";
				}
				else
				{
					$rep->title = _('PACKING SLIP');
					$rep->filename = "Packing_slip" . $myrow['reference'] . ".pdf";
				}
			}
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);

			$contacts = get_branch_contacts($branch['branch_code'], 'delivery', $branch['debtor_no'], true);
			$rep->SetCommonData($myrow, $branch, $sales_order, '', ST_CUSTDELIVERY, $contacts);
			$rep->SetHeaderType('Header68');
			$rep->NewPage();

   			$result = get_customer_trans_details(ST_CUSTDELIVERY, $i);
			$SubTotal = 0;
			$k=1;
			while ($myrow2=db_fetch($result))
			{
				if ($myrow2["quantity"] == 0)
					continue;

				$Net = round2(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
				   user_price_dec());
				$SubTotal += $Net;
	    		$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
	    		$DisplayQty = number_format2($myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
				
				$DisplayFOCQty = number_format2($myrow2["foc_quantity"],get_qty_dec($myrow2['stock_id']));
				
	    		$DisplayNet = number_format2($Net,$dec);
	    		if ($myrow2["discount_percent"]==0)
		  			$DisplayDiscount ="";
	    		else
		  			$DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "%";
				
				$rep->TextCol(0, 1,	$k, -2);
				$rep->TextCol(1, 2,	$myrow2['stock_id'], -2);
				$oldrow = $rep->row;
				$rep->TextColLines(2, 3, $myrow2['StockDescription'], -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				if ($Net != 0.0  || !is_service($myrow2['mb_flag']) || !$SysPrefs->no_zero_lines_amount())
				{
					
					//$rep->TextCol(3, 4,	$myrow2['units'], -2);
					
				if($myrow2['unit']==1){
				 $item_info = get_item_edit_info($myrow2["stock_id"]);	
				$rep->TextCol(3, 4,	$item_info["units"], -2);
				}
				else if($myrow2["unit"]==2){
		         $sec_unit_info = get_item_sec_unit_info($myrow2["stock_id"]);
	              $rep->TextCol(3, 4, $sec_unit_info["sec_unit_name"], -2);
                }

                 $rep->TextCol(4, 5,	$DisplayQty, -2);				
				}
				$rep->row = $newrow;
				
				if($myrow2["foc_quantity"]!=0){
				$rep->TextCol(3, 5,	_("FOC - ").$DisplayFOCQty, -2);
				$rep->NewLine();
				}
				
				//$rep->NewLine(1);
				if ($rep->row < $rep->bottomMargin + (17 * $rep->lineHeight))
					$rep->NewPage();
			}

			$memo = get_comments_string(ST_CUSTDELIVERY, $i);
			if ($memo != "")
			{
				$rep->NewLine();
				$rep->TextColLines(1, 3, $memo, -2);
			}

   			$DisplaySubTot = number_format2($SubTotal,$dec);

    	$rep->row = $rep->bottomMargin + (17 * $rep->lineHeight);
		$doctype=ST_CUSTDELIVERY;
			
			
		$rep->row = $rep->bottomMargin + (16 * $rep->lineHeight);	
	    $rep->NewLine();
		$rep->SetFont('helvetica', 'B', 9);
		$rep->TextCol(0, 7,"Terms & Conditions", -2);
		$rep->NewLine();
		$rep->SetFont('', '', 0);
		$rep->TextCol(0, 7,"Any variation in QUANTITY & QUALITY must be reported within two days of the delivery. Thereafter NO claims shall be entertained.", -2);
				
			
		$rep->NewLine(5);
	  $rep->SetFont('helvetica', 'B', 9);
	  $rep->Text($mcol + 55, _("Customer Signature"));
	  $rep->Text($ccol+375 , _("For ").$rep->company['coy_name']);
	  $rep->SetFont('', '', 0);
				
			if ($email == 1)
			{
				$rep->End($email);
			}
	}
	if ($email == 0)
		$rep->End();
}

