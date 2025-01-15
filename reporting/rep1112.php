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

function get_transactions($fromsupp, $from, $to, $reg_type=0)
{
	$from = date2sql($from);
	$to = date2sql($to);

 	$sql = "SELECT po.ord_date,po.order_no,po.reference,supplier.supp_name,supplier.curr_code,pod.item_code,pod.description,pod.quantity_ordered,pod.quantity_received,pod.unit_price,pod.discount_percent FROM ".TB_PREF."purch_orders po,".TB_PREF."purch_order_details pod,".TB_PREF."suppliers supplier WHERE po.trans_type=pod.trans_type and po.trans_type=18 and pod.trans_type=18 and po.order_no=pod.order_no and po.po_auth_req in (0,2) and pod.quantity_ordered>pod.quantity_received and po.ord_date BETWEEN '$from' and '$to' and po.supplier_id=supplier.supplier_id";
 	
		//Chaiatanya : New Filter
		if ($fromsupp != ALL_TEXT)
			$sql .= " AND po.supplier_id = ".db_escape($fromsupp);	
        
		if($reg_type == 1){
			$sql .= " AND supplier.curr_code='OMR'";
	   }	
       elseif($reg_type == 2){
            $sql .= " AND supplier.curr_code!= 'OMR'";
	   }			

		$sql .= " ORDER BY po.order_no,po.ord_date";
		
	
			
    return db_query($sql,"No transactions were returned");
}

function get_transaction_user($po_no)
{
	
	$sql="SELECT user_tbl.user_id from ".TB_PREF."audit_trail at_tbl,".TB_PREF."users user_tbl where at_tbl.trans_no=".db_escape($po_no)." and at_tbl.type=18 and at_tbl.user=user_tbl.id order by at_tbl.id desc";
		
	$result=db_query($sql,"No transactions were returned");
	
	$row=db_fetch_row($result);
	return $row[0];
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
	
    	$dec = user_price_dec();
		
	if ($reg_type == 0)
		 $rg_type ="All";
	else if ($reg_type == 1)
		 $rg_type ="Local";
	else if ($reg_type == 2)
		 $rg_type ="Import";


	$cols = array(2, 40, 80, 180, 220,260,300,340,380,425,450,490,510,550,600);

	$headers = array(_('PO Date'),_('PO No'), _('Supplier Name'),  _('Item Code'),
	_('Description'), _('PO Qty'), _('Received Qty'), _('FC Rate'), _('Disc %'), 
	_('FC Value'),  _('LC Rate') ,_('Disc %') , _('LC Value'), _('User Name'));

	
	$aligns = array('left',	'left',	'left','left','left','right','right','right','right','right','right','right','right','center');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Supplier'), 'from' => $supp,   	'to' => ''),
						3 => array('text' => _('Type'), 'from' => $rg_type,'to' => ''));

    $rep = new FrontReport(_('Pending PO Report'), "PendingPOItemsReport", user_pagesize(), 7, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($fromsupp,$from,$to,$reg_type);
$tot = 0;
	while ($myrow = db_fetch($result))
	{
	    $copy_curr= get_company_currency();
		if($copy_curr!=$myrow['curr_code'])
		{
			// $rate = get_rate_by_transaction_date($myrow['ord_date']);
			$rate=get_last_exchange_rate($myrow['curr_code'],sql2date($myrow['ord_date']));
			$myrow['rate'] = $rate['rate_buy'];
		}else {
			$myrow['rate']=1;
		}
		 if($myrow['rate']==1)
	    $rep->SetTextColor(33, 33, 33);
	    else
	    $rep->SetTextColor(216, 67, 21);
	
		$rep->TextCol(0, 1, sql2date($myrow['ord_date']));		
		$rep->TextCol(1, 2, $myrow['reference']);
		$rep->TextCol(2, 3, $myrow['supp_name']);
		$rep->TextCol(3, 4, $myrow['item_code']);
		$rep->TextCol(4, 5, $myrow['description']);
		$rep->TextCol(5,6, $myrow['quantity_ordered']);	
		$rep->TextCol(6,7, $myrow['quantity_received']);
		$rep->AmountCol(7,8, $myrow['unit_price'], $dec);
		$rep->AmountCol(8,9, $myrow['discount_percent'], $dec);
		$rep->AmountCol(9,10, $myrow['quantity_ordered']*$myrow['unit_price']*(100-$myrow['discount_percent'])*0.01, $dec);	
		$rep->AmountCol(10,11, $myrow['unit_price']*$myrow['rate'], $dec);
		$rep->AmountCol(11,12, $myrow['discount_percent'], $dec);
		$rep->AmountCol(12,13, $myrow['quantity_ordered']*$myrow['unit_price']*(100-$myrow['discount_percent'])*0.01*$myrow['rate'], $dec);	
		$rep->TextCol(13,14, get_transaction_user($myrow['order_no']));
		$rep->NewLine();
	}
	
	$rep->NewLine();
    $rep->End();
}

