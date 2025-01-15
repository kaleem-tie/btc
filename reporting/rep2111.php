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

// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Customer Balances
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

//----------------------------------------------------------------------------------------------------

function is_purchase_order_reference_existed($reference)
{
	$sql = "SELECT count(*) FROM ".TB_PREF."purch_orders 
		WHERE trans_type=18
		AND reference=".db_escape($reference)."";
	$result= db_query($sql,"The related customer type could not be retreived");
	$row= db_fetch_row($result);
	return $row[0];
}

function get_purchase_order_order_no_by_reference($reference){
	$sql = "SELECT order_no FROM ".TB_PREF."purch_orders 
	WHERE trans_type=18
	AND reference=".db_escape($reference)."";
	//display_error($sql);
	$result = db_query($sql, "could not get order no");
	$myrow = db_fetch_row($result);
	return $myrow[0]; 
}


function get_supp_po_excel($order_no)
{
   	$sql = "SELECT po.*, supplier.supp_name, supplier.supp_account_no,supplier.tax_included,
   		supplier.gst_no AS tax_id,
   		supplier.curr_code, supplier.payment_terms, loc.location_name,
   		supplier.address, supplier.contact, supplier.tax_group_id
		FROM ".TB_PREF."purch_orders po,"
			.TB_PREF."suppliers supplier,"
			.TB_PREF."locations loc
		WHERE po.supplier_id = supplier.supplier_id
		AND po.trans_type=".ST_PURCHORDER."
		AND loc.loc_code = into_stock_location
		AND po.order_no = ".db_escape($order_no);
		
   	$result = db_query($sql, "The order cannot be retrieved");
    return db_fetch($result);
}

function get_po_excel_details($order_no)
{
	$sql = "SELECT poline.*, units
		FROM ".TB_PREF."purch_order_details poline
			LEFT JOIN ".TB_PREF."stock_master item ON poline.item_code=item.stock_id
		WHERE order_no =".db_escape($order_no)." AND poline.trans_type=".ST_PURCHORDER."";
	$sql .= " ORDER BY po_detail_item";
			
	return db_query($sql, "Retreive order Line Items");
}

//----------------------------------------------------------

print_supplier_purchase_orders();

function print_supplier_purchase_orders()
{
    global $path_to_root, $systypes_array;

    $purch_order_no   = $_POST['PARAM_0'];
    
	
	$destination = 1;
	
	
	 
	/*
	if($order_ref==0){
	display_error("Purchase Order : ".$reference." is not existed!");
	return false;	
	}
	else{
    $purch_order_no = get_purchase_order_order_no_by_reference($reference);
	}*/
	
	
	//$order_no = 0;
	
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	
	$dec = user_price_dec();
	

	$myrow = get_supp_po_excel($purch_order_no);
	
	$cols = array(0,100,200,400,450,500,550,600,650,700,750,800,850,900);

	$headers = array(_("Item Code"), _("Supplier Item Code"), _("Item Description"), 
	 _("Quantity"), _("Unit"), _("Price"),_("Requested By"),  _("Net Amount"),
	 _("Discount %"),_("Discount Amount"), _("Line Total"), _("Quantity Received"),
	 _("Quantity Invoiced"));
	
	$aligns = array('left','left','left','right','center','right','center','right',
	'right','right','right','right','right');

    $params =   array( 0 => $comments);
						
						

    $rep = new FrontReport(_(''), 
	"PurchaseOrder - ".$myrow['reference'], user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();
	
	



    $result = get_po_excel_details($purch_order_no);
    $SubTotal = 0;
	$items = $prices = array();
	while ($myrow2 = db_fetch($result))
	{
	   
		
		$net_amount = $myrow2["quantity_ordered"] * $myrow2["unit_price"];
		$DisplayNetAmount = number_format2($net_amount,$dec);
		
		$line_total = round2(($myrow2["quantity_ordered"] * $myrow2["unit_price"])-($myrow2["quantity_ordered"] * $myrow2["unit_price"] * ($myrow2["discount_percent"]/100)),user_price_dec());
		$DisplayLineTotal = number_format2($line_total,$dec);
		
		$DisplayQty = number_format2($myrow2["quantity_ordered"],get_qty_dec($myrow2['item_code']));
		$DisplayReceivedQty = number_format2($myrow2["quantity_received"],get_qty_dec($myrow2['item_code']));
		$DisplayInvQty = number_format2($myrow2["qty_invoiced"],get_qty_dec($myrow2['item_code']));
		
		$DisplayPrice = price_decimal_format($myrow2["unit_price"],$dec2);
		
		if ($myrow2["discount_percent"]==0)
			$DisplayDiscount =0;
		else
		   $DisplayDiscount = number_format2($myrow2["discount_percent"],user_percent_dec()) . "%";
		
		$discount_amount = ($myrow2["unit_price"]*$myrow2["quantity_ordered"])*$myrow2["discount_percent"]/100 ; 
		 $DisplayDiscountAmount = number_format2($discount_amount,user_price_dec());
		
	    $data = get_purchase_data($myrow['supplier_id'], $myrow2['item_code']);
	   
	    
		$rep->TextCol(0, 1,	$myrow2['item_code'], -2);
		$rep->TextCol(1, 2, $data['supplier_description']);
		$rep->TextCol(2, 3, $myrow2['description']);
		$rep->TextCol(3, 4,	$DisplayQty, -2);
		$rep->TextCol(4, 5,	$myrow2['units'], -2);
		$rep->TextCol(5, 6,	$DisplayPrice, -2);
		$rep->TextCol(6, 7,	sql2date($myrow2['delivery_date']), -2);
		$rep->TextCol(7, 8,	$DisplayNetAmount, -2);
		$rep->TextCol(8, 9,	$DisplayDiscount, -2);
		$rep->TextCol(9, 10,$DisplayDiscountAmount, -2);
		$rep->TextCol(10, 11,$DisplayLineTotal, -2);
		$rep->TextCol(11, 12,$DisplayReceivedQty, -2);
		$rep->TextCol(12, 13,$DisplayInvQty, -2);
		
		$rep->NewLine();
		$k++;
	}
	
    $rep->End();
}

