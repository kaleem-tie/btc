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
$page_security = 'SA_PURCHASE_REGISTER_REP';

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

function get_transactions($fromsupp, $from, $to, $reg_type=0)
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
		(trans.ov_amount + trans.ov_gst + trans.ov_discount + trans.additional_charges + trans.packing_charges + trans.other_charges + trans.freight_cost + trans.freight_tax + trans.additional_tax + trans.packing_tax + trans.other_tax+trans.ov_roundoff) AS TotalAmount,
		trans.alloc AS Allocated,
		supplier.curr_code,
		inv_items.*,		
		trans.declaration_no,trans.cif_value,trans.vat_import_value,trans.custom_duties
    	FROM ".TB_PREF."supp_trans as trans, ".TB_PREF."suppliers as supplier, ".TB_PREF."supp_invoice_items as inv_items
    	WHERE supplier.supplier_id = trans.supplier_id
		AND trans.type='20' and inv_items.supp_trans_type=20 and trans.trans_no=inv_items.supp_trans_no and trans.type=inv_items.supp_trans_type  
	
		AND trans.ov_amount != 0";
			$sql .=  " AND trans.tran_date >= '$from'"
					." AND trans.tran_date <= '$to'";
	

		//Chaiatanya : New Filter
		if ($fromsupp != ALL_TEXT)
			$sql .= " AND trans.supplier_id = ".db_escape($fromsupp);	

      
		if ($reg_type == 1)
			$sql .= " AND trans.rate=1";
        elseif($reg_type == 2)	
            $sql .= " AND trans.rate!=1";		

		$sql .= " ORDER BY trans.trans_no,trans.tran_date";
		
    return db_query($sql,"No transactions were returned");
}


function get_transaction_user($inv_no)
{
	
	$sql="SELECT user_tbl.user_id from ".TB_PREF."audit_trail at_tbl,".TB_PREF."users user_tbl where at_tbl.trans_no=".db_escape($inv_no)." and at_tbl.type=20 and at_tbl.user=user_tbl.id order by at_tbl.id desc";
		
	$result=db_query($sql,"No transactions were returned");
	
	$row=db_fetch_row($result);
	return $row[0];
}


function get_po_details($po_detail_item){
    
    
    
   $sql1 = "SELECT po.reference,po.ord_date FROM ".TB_PREF."purch_orders po,".TB_PREF."purch_order_details pod WHERE po.order_no=pod.order_no and po.trans_type=pod.trans_type and  pod.po_detail_item=".db_escape($po_detail_item)." AND pod.trans_type ='18' AND po.trans_type = '18' limit 1";


    $res1 = db_query($sql1);
    $result1 = db_fetch($res1);
    
    return $result1;
    
}

function get_grn_details($grn_item_id){
    
    
    
   $sql1 = "SELECT grn.reference,grn.delivery_date,grn.id as grn_id FROM ".TB_PREF."grn_batch grn,".TB_PREF."grn_items grn_items WHERE grn.id=grn_items.grn_batch_id and grn_items.id=".db_escape($grn_item_id)." limit 1";


    $res1 = db_query($sql1);
    $result1 = db_fetch($res1);
    
    return $result1;
    
}

function get_supp_payment_reference($inv_no){
     
   $sql1 = "SELECT trans.reference FROM ".TB_PREF."supp_trans trans where trans.type=22 and trans.trans_no in (select alloc.trans_no_from from ".TB_PREF."supp_allocations alloc where alloc.trans_type_to=20 and alloc.trans_no_to=".db_escape($inv_no).")";

    $res1 = db_query($sql1);
    $result1 = db_fetch_row($res1);
    
    return $result1[0];
    
}


function get_inv_grn_batch_info($po_detail_item,$item_code){
    
   $sql1 = "SELECT grn.reference,grn.delivery_date
   FROM ".TB_PREF."inv_grn_batch grn, ".TB_PREF."inv_grn_items items
   WHERE items.grn_batch_id = grn.id
   AND items.po_detail_item=".db_escape($po_detail_item)."
   AND items.item_code=".db_escape($item_code)."";
   
   $res1 = db_query($sql1);
   $result1 = db_fetch($res1);
   return $result1;
    
}


//--------------------------------------------------------------------------------------------------

function print_payment_report()
{
    	global $path_to_root, $systypes_array;

    	$from        = $_POST['PARAM_0'];
    	$to          = $_POST['PARAM_1'];
    	$fromsupp    = $_POST['PARAM_2'];
		$reg_type    = $_POST['PARAM_3'];
    	$orientation = $_POST['PARAM_4'];
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


	$cols = array(2, 40, 80, 180, 220,260,300,340,380,425,450,490,510,550,600,650,700,750,800,850,900,950,1000,1050,1100,1150,1200,1250,1300,1350,1400,1450,1500,1550,1600,1650);

	$headers = array(_('IMP Inv Date'),_('Imp Inv No'), _('PO Date'),  _('PO No'),
	_('Received Doc Date'), _('Received Doc No'), 
	_('GRN Date'), _('GRN No'), _('Supplier Name'), 
	_('Supplier Inv No'),  _('Supplier Invoice Date'),_('Item Code') , _('Item Name'),
	_('Qty'), _('FC Rate'), _('Disc %'), _('FC Value'), _('LC Rate'), _('Disc %'), 
	_('LC Value'), _('Custom Duty'), _('CIF Value(LC)'), _('IMP VAT'), 
	_('Payment Status'), _('Ref Payment Voucher No'),_('User Name'));

	
	$aligns = array('left',	'left',	'left','left','left','left','left','left','left','left','left','left','left','right','right','right','right','right','right','right','right','right','right','right','right','right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Supplier'), 'from' => $supp,'to' => ''),
						3 => array('text' => _('Type'), 'from' => $rg_type,'to' => '')
						);

    $rep = new FrontReport(_('Purchase Register Report'), "PurchaseRegisterReport", user_pagesize(), 7, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($fromsupp,$from,$to, $reg_type);
$tot = 0;
	while ($myrow = db_fetch($result))
	{
	
	  if($myrow['quantity'])
	  {
	   if($myrow['rate']==1)
	    $rep->SetTextColor(33, 33, 33);
	    else
	    $rep->SetTextColor(216, 67, 21);
	
   		$rep->TextCol(0, 1, sql2date($myrow['tran_date']));
		$rep->TextCol(1, 2, $myrow['reference']);
		
		$podetails=get_po_details($myrow['po_detail_item_id']);
		$grndetails=get_grn_details($myrow['grn_item_id']);
		
		$invgrn=get_inv_grn_batch_info($myrow['po_detail_item_id'],$myrow['stock_id']);
		
		
		$rep->TextCol(2, 3, sql2date($podetails['ord_date'])); 
		$rep->TextCol(3, 4, $podetails['reference']);
		
		$rep->TextCol(4, 5, $invgrn['reference']); // INV GRN doc no
		$rep->TextCol(5, 6, sql2date($invgrn['delivery_date'])); // INV GRN doc date
		
		$rep->TextCol(6, 7, sql2date($grndetails['delivery_date'])); 
		$rep->TextCol(7, 8, $grndetails['reference']);
		
		$rep->TextCol(8, 9, $myrow['supp_name']);	
		$rep->TextCol(9, 10, $myrow['supp_reference']);	
		$rep->TextCol(10, 11, "");	
		
		$rep->TextCol(11, 12, $myrow['stock_id']);	
		$rep->TextCol(12, 13, $myrow['description']);	
		$rep->TextCol(13, 14, $myrow['quantity']);
		
		$rep->AmountCol(14,15, $myrow['unit_price'], $dec);
		$rep->AmountCol(15,16, $myrow['discount_percent'], $dec);
		$rep->AmountCol(16,17, $myrow['quantity']*$myrow['unit_price']*(100-$myrow['discount_percent'])*0.01, $dec);
		
		
		$rep->AmountCol(17,18, $myrow['unit_price']*$myrow['rate'], $dec);
		$rep->AmountCol(18,19, $myrow['discount_percent'], $dec);
		$rep->AmountCol(19,20, $myrow['quantity']*$myrow['unit_price']*(100-$myrow['discount_percent'])*0.01*$myrow['rate'], $dec);
			
		$rep->AmountCol(20,21, $myrow['custom_duties'], $dec);
		$rep->AmountCol(21,22, $myrow['cif_value'], $dec);
		$rep->AmountCol(22,23, $myrow['vat_import_value'], $dec);
		if($myrow['TotalAmount']==$myrow['Allocated'])
		$rep->TextCol(23, 24, "Complete Paid");
		if($myrow['TotalAmount']>$myrow['Allocated'] && $myrow['Allocated']!=0)
		$rep->TextCol(23, 24, "Partially Paid");
		if($myrow['Allocated']==0)
		$rep->TextCol(23, 24, "Not Paid");
		$rep->TextCol(24, 25, get_supp_payment_reference($myrow['trans_no']));	
		$rep->TextCol(25, 26, get_transaction_user($myrow['trans_no']));			
   		$rep->NewLine();
	}
	}
	
    $rep->End();
}

