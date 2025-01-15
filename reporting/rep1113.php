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
$page_security = 'SA_SUPPLIERANALYTIC';

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
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_payment_report();

function get_transactions($fromsupp, $from, $to,$reg_type=0)
{
	$from = date2sql($from);
	$to = date2sql($to);

 	$sql = "SELECT trans.type, 
		trans.trans_no,
		trans.reference, 
		supplier.supp_name, 
		trans.supp_reference,
    	trans.tran_date, 
		trans.due_date,
		trans.rate,
		supplier.curr_code, 
    	(trans.ov_amount + trans.ov_gst  + trans.ov_discount + trans.freight_cost + trans.other_charges) AS TotalAmount, 
		trans.alloc AS Allocated,
		trans.declaration_no,trans.cif_value,trans.vat_import_value,trans.custom_duties,
		trans.container_number,trans.bl_no
    	FROM ".TB_PREF."supp_trans as trans, ".TB_PREF."suppliers as supplier
    	WHERE supplier.supplier_id = trans.supplier_id
		AND trans.type='20'
	
		AND trans.ov_amount != 0";
			$sql .=  " AND trans.tran_date >= '$from'"
					." AND trans.tran_date <= '$to'";
	

		//Chaiatanya : New Filter
		if ($fromsupp != ALL_TEXT)
			$sql .= " AND trans.supplier_id = ".db_escape($fromsupp);	

        if($reg_type == 1){
			$sql .= " AND supplier.curr_code='OMR'";
	   }	
       elseif($reg_type == 2){
            $sql .= " AND supplier.curr_code!= 'OMR'";
	   }			

		$sql .= " GROUP BY trans.trans_no ORDER BY trans.trans_no,trans.tran_date";
		
	
			
    return db_query($sql,"No transactions were returned");
}

function get_po_reference($supp_trans_no){
    
  
    $sql = "SELECT inv_items.po_detail_item_id FROM ".TB_PREF."supp_invoice_items as inv_items WHERE inv_items.supp_trans_no = ".db_escape($supp_trans_no)." limit 1";
	// display_error($sql);
    $res = db_query($sql);
    
    $result = db_fetch_row($res);
    
    
   $sql1 = "SELECT po.reference FROM ".TB_PREF."purch_order_details as dts LEFT JOIN ".TB_PREF."purch_orders as po ON po.order_no = dts.order_no WHERE dts.po_detail_item=".db_escape($result['0'])." AND dts.trans_type ='18' AND po.trans_type = '18' limit 1";
   // display_error($sql1);
    $res1 = db_query($sql1);
    $result1 = db_fetch_row($res1);
    
    return $result1['0'];
    
}


//--------------------------------------------------------------------------------------------------

function print_payment_report()
{
    	global $path_to_root, $systypes_array;

    	$from = $_POST['PARAM_0'];
    	$to = $_POST['PARAM_1'];
    	$fromsupp = $_POST['PARAM_2'];
		$reg_type    = $_POST['PARAM_3'];
    	$orientation = $_POST['PARAM_4'];
	    $destination = $_POST['PARAM_5'];
		
		$destination = 1;
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	if ($fromsupp == ALL_TEXT)
		$supp = _('All');
	else
		$supp = get_supplier_name($fromsupp);
	
	if ($reg_type == 0)
		 $rg_type ="All";
	else if ($reg_type == 1)
		 $rg_type ="Local";
	else if ($reg_type == 2)
		 $rg_type ="Import";
	
    	$dec = user_price_dec();


	$cols = array(2,100,200,300,400,500,600,700,800,900,1000,1100,1200,1300,1400,1500);

	$headers = array(_('Invoice Ref'),_('Date'), _('Supplier Name'), _('Supplier Ref'),
	_('Due Date'), _('Declaration No.'), _('CIF'), _('VAT Import'), _('Custom Duties'), 
	_('Container Number'),_('BL No.'),_('Currency'),  _('Inv Amt') ,_('Rate') ,
	_('Inv Amt (OMR)'));

	
	$aligns = array('left',	'left',	'left','left','left','left','right','right','right','left',	'left','right','right','right','right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Supplier'), 'from' => $supp,   	'to' => ''),
						3 => array('text' => _('Type'), 'from' => $rg_type,'to' => ''));

    $rep = new FrontReport(_('Supplier Invoice Listing'), "SupplierInvoiceListing", user_pagesize(), 7, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($fromsupp,$from,$to,$reg_type);
$tot = 0;
	while ($myrow = db_fetch($result))
	{
	
	   if($myrow['rate']==1)
	    $rep->SetTextColor(33, 33, 33);
	    else
	    $rep->SetTextColor(216, 67, 21);
	
      //$po_reference =	get_po_reference( $myrow['reference']);
		$rep->TextCol(0, 1, $myrow['reference']);
		$rep->TextCol(1, 2, sql2date($myrow['tran_date']));
		
		$rep->TextCol(2, 3, $myrow['supp_name']);
		$rep->TextCol(3, 4, $myrow['supp_reference']);
		$rep->TextCol(4, 5, sql2date($myrow['due_date']));
		$rep->TextCol(5, 6, $myrow['declaration_no']);
		$rep->AmountCol(6,7, $myrow['cif_value'], $dec);	
		$rep->AmountCol(7,8, $myrow['vat_import_value'], $dec);
		$rep->AmountCol(8,9, $myrow['custom_duties'], $dec);
		
		$rep->TextCol(9, 10, $myrow['container_number']);
		$rep->TextCol(10, 11, $myrow['bl_no']);
		
		$rep->TextCol(11, 12, $myrow['curr_code']);
		$rep->AmountCol(12,13, $myrow['TotalAmount'], $dec);	
		$rep->AmountCol(13,14, $myrow['rate'], $dec);
		$rep->AmountCol(14,15, $myrow['rate']*$myrow['TotalAmount'], $dec);		
		$tot += $myrow['rate']*$myrow['TotalAmount'];
		$rep->SetTextColor(0, 0, 0);
   		$rep->NewLine();
	}
	
	
	$rep->Line($rep->row);
	$rep->NewLine(2);
	//$rep->SetFont('helvetica', 'B', 8);
	$rep->TextCol(13, 14, 'Total');
	$rep->AmountCol(14,15, $tot, $dec);	
	//$rep->SetFont('', '', 0);
	$rep->NewLine();
    $rep->End();
}

