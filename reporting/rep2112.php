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
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

//---------------------------------------------------------------------


function get_supp_invoice_excel($trans_no)
{
   	$sql = "SELECT trans.*, supp_name, dimension_id, dimension2_id, curr_code
		FROM ".TB_PREF."supp_trans trans,"
			.TB_PREF."suppliers sup
		WHERE trans_no = ".db_escape($trans_no)." 
		AND type = ".ST_SUPPINVOICE."
		AND sup.supplier_id=trans.supplier_id";
   	$result = db_query($sql, "The order cannot be retrieved");
    return db_fetch($result);
}

function get_supp_invoice_excel_items($supp_trans_no)
{
	$sql = "SELECT inv.*, grn.*, unit_price AS FullUnitPrice, 
		stock.units,
		tax_type.exempt,
		tax_type.name as tax_type_name
		FROM "
			.TB_PREF."supp_invoice_items inv LEFT JOIN ".TB_PREF."grn_items grn ON grn.id =inv.grn_item_id
				LEFT JOIN ".TB_PREF."stock_master stock ON stock.stock_id=inv.stock_id
				LEFT JOIN ".TB_PREF."item_tax_types tax_type ON stock.tax_type_id=tax_type.id
		WHERE supp_trans_type = ".ST_SUPPINVOICE."
		AND supp_trans_no = ".db_escape($supp_trans_no)."
		AND gl_code=0 
		ORDER BY inv.id";
			
	return db_query($sql, "Cannot retreive supplier transaction detail records");
}

//----------------------------------------------------------

print_supplier_purchase_invocess();

function print_supplier_purchase_invocess()
{
    global $path_to_root, $systypes_array;

    $invoice_no   = $_POST['PARAM_0'];
    
	
	$destination = 1;
	
	
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	
	$dec = user_price_dec();
	
	
	$myrow = get_supp_invoice_excel($invoice_no);
	
	$supplier_curr_code = get_supplier_currency($myrow['supplier_id']);
	
	
	

	 if (!is_company_currency($supplier_curr_code)){
		$headers = array(_("Delivery"), _("Item"), _("Description"),_("Quantity"),
			 _("FC RATE"), _("Discount%"), _("FC Value"),
			 _("LC Rate"),_("Discount%"), _("LC Value"));
			
	  $cols = array(0,50,150,300,350,400,450,500,550,600,650);	
	  $aligns = array('left','left','left','right','right','right','right',
	  'right','right','right');	
	}
	else{
		 $headers = array(_("Delivery"), _("Item"), _("Description"),_("Quantity"),
			       _("LC Rate"), _("Discount%"), _("LC Value"));
		 $cols = array(0,50,150,300,350,400,450,500);
	     $aligns = array('left','left','left','right','right','right','right');		
	}
		
	

    $params =   array( 0 => $comments);
						
						

    $rep = new FrontReport(_(''), 
	"PurchaseVoucher - ".$myrow['reference'], user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();
	
    $result = get_supp_invoice_excel_items($invoice_no);
    $SubTotal = 0;
	$items = $prices = array();
	while ($myrow2 = db_fetch($result))
	{
	   
	   $grn_batch = get_grn_batch_from_item($myrow2['id']);
	   
	   $DisplayQty = number_format2($myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
	   $DisplayPrice = price_decimal_format($myrow2["unit_price"],$dec);
	   
	   if ($myrow2["discount_percent"]==0)
			$DisplayDiscount =0;
	   else
		   $DisplayDiscount = number_format2($myrow2["discount_percent"],user_percent_dec()) . "%";
	   
	   $FCValue = round2($myrow2["unit_price"] * abs($myrow2["quantity"])*(1-$myrow2["discount_percent"]/100) , user_price_dec());
	   $DisplayFCValue = number_format2($FCValue,$dec);
	   
	   $DisplayLCRate = number_format2($myrow2["unit_price"]*$myrow["rate"],$dec);
	    
	   $LCValue = (($myrow2["unit_price"] * abs($myrow2["quantity"])*(1-$myrow2["discount_percent"]/100)) *$myrow["rate"]);
	   $DisplayLCValue = number_format2($LCValue,$dec);
	    
		$rep->TextCol(0, 1,	$grn_batch, -2);
		$rep->TextCol(1, 2, $myrow2['stock_id']);
		$rep->TextCol(2, 3, $myrow2['description']);
		$rep->TextCol(3, 4,	$DisplayQty, -2);
		
		if (!is_company_currency($supplier_curr_code)){
			$rep->TextCol(4, 5,	$DisplayPrice, -2);
			$rep->TextCol(5, 6,	$DisplayDiscount, -2);
			$rep->TextCol(6, 7,	$DisplayFCValue, -2);
			$rep->TextCol(7, 8,	$DisplayLCRate, -2);
		    $rep->TextCol(8, 9,	$DisplayDiscount, -2);
		    $rep->TextCol(9, 10,$DisplayLCValue, -2);
		}else{
        $rep->TextCol(4, 5,	$DisplayLCRate, -2);
		$rep->TextCol(5, 6,	$DisplayDiscount, -2);
		$rep->TextCol(6, 7,$DisplayLCValue, -2);
        }	
		
		$rep->NewLine();
		$k++;
	}
	
    $rep->End();
}

