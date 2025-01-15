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
$page_security = 'SA_SALESDELIVERY';

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
include_once($path_to_root . "/sales/includes/db/customers_db.inc");

//----------------------------------------------------------------------------------------------------

print_customer_orders();


function get_transactions($customer_id, $from, $to, $dimension=0, $folk=0)
{
	$from = date2sql($from);
	$to = date2sql($to);

 	$sql = "SELECT 
			sorder.order_no,
			sorder.reference,
			debtor.name,
			sorder.ord_date,
			dt.reference as inv_ref,
			dt.tran_date as inv_date,
			debtor.name,
			dt.trans_no,
			dt.tax_included,
			line.id as line_no, 
			line.stock_id,
			line.description,
			line.quantity,
			line.unit_price,
			line.discount_percent,
			line.unit_tax,
			line.src_id
		FROM ".TB_PREF."sales_orders as sorder,"
			.TB_PREF."debtor_trans_details as line, "
			.TB_PREF."debtors_master as debtor, "
			.TB_PREF."debtor_trans as dt
			WHERE dt.order_ = sorder.order_no 
			AND dt.type = line.debtor_trans_type
			AND sorder.trans_type = 30
			AND dt.trans_no = line.debtor_trans_no 
			AND dt.type = 10 and line.debtor_trans_type =10
			AND debtor.debtor_no = dt.debtor_no 
			AND dt.debtor_no = sorder.debtor_no ";
			$sql .=  " AND dt.tran_date >= '$from'"
					." AND dt.tran_date <= '$to'";
	

		//Chaiatanya : New Filter
		if ($customer_id != ALL_TEXT)
			$sql .= " AND dt.debtor_no = ".db_escape($customer_id);	

        if ($dimension != 0){
  		$sql .= " AND dt.dimension_id = ".($dimension<0 ? 0 : db_escape($dimension));
		}
		if ($folk != 0)
			$sql .= " AND sorder.sales_person_id = ".db_escape($folk);
        			
    return db_query($sql,"No transactions were returned");
}


function get_transaction_user($inv_no)
{
	
	$sql="SELECT user_tbl.user_id from ".TB_PREF."audit_trail at_tbl,".TB_PREF."users user_tbl where at_tbl.trans_no=".db_escape($inv_no)." and at_tbl.type=10 and at_tbl.user=user_tbl.id order by at_tbl.id desc";
		
	$result=db_query($sql,"No transactions were returned");
	
	$row=db_fetch_row($result);
	return $row[0];
}

function get_do_info($doitem_no)
{
	 $sql="SELECT dt.reference,dt.tran_date, dt.type, dt.trans_no from  ".TB_PREF."debtor_trans dt, ".TB_PREF."debtor_trans_details dtd where dt.type=dtd.debtor_trans_type and dt.trans_no=dtd.debtor_trans_no and dt.type=13 and dtd.debtor_trans_type=13 and dtd.id=".db_escape($doitem_no)."";
	 		
	$result=db_query($sql,"No transactions were returned");
	
	$row=db_fetch($result);
	return $row;
}


function get_cust_payment_reference($inv_no){
     
   $sql1 = "SELECT trans.trans_no,trans.reference FROM ".TB_PREF."debtor_trans trans where trans.type=12 and trans.trans_no in (select alloc.trans_no_from from ".TB_PREF."cust_allocations alloc where alloc.trans_type_to=10 and alloc.trans_no_to=".db_escape($inv_no).")";
    $res1 = db_query($sql1);
    if($result1 = db_fetch_row($res1))
	{
		$sql1 = "SELECT mode_of_payment FROM ".TB_PREF."bank_trans trans where trans.type=12 and trans.trans_no=".db_escape($result1[0])."";

		$res1 = db_query($sql1);
		$result = db_fetch_row($res1);
		return $result1[1]." ".$result[0];
	}
  
    return "";  
}

function get_comments_all($type, $type_no)
{
	$sql = "SELECT memo_ FROM ".TB_PREF."comments WHERE type="
		.db_escape($type)." AND id=".db_escape($type_no);

	$result = db_query($sql,"could not query comments transaction table");
    return db_fetch($result);
}


//----------------------------------------------------------------------------------------------------

function print_customer_orders()
{
    	global $path_to_root, $systypes_array;

    	$from = $_POST['PARAM_0'];
    	$to = $_POST['PARAM_1'];
    	$fromcust = $_POST['PARAM_2'];
		$dimension = $_POST['PARAM_3'];
		$folk = $_POST['PARAM_4'];  // added by Faisal to filter by sales person
    	$orientation = $_POST['PARAM_5'];
	    $destination = 1;
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
	
	if ($folk == ALL_NUMERIC)
        $folk = 0;
    if ($folk == 0)
        $salesfolk = _('All Sales Man');
     else
        $salesfolk = get_salesman_name($folk);
	
    	$dec = user_price_dec();

	$cols = array(0, 50, 100, 150, 300,340,480,520,560,600,650,700,750,800,850,900,950,1000, 1050);

	$headers = array(_('Date'), _('INV No'), 
	_('Customer Name'),_('Item Code'),_('Item Name'), _('Qty'),_('Unit Price'),_('Discount %'),_('LC Value'),_('VAT'),_('Total'),_('SO Date'),_('SO No'),_('DO Date'),_('DO No'),_('Customer Payment'),_('User'), _('Comments'));
	

	$aligns = array('left',	'left',	'left',	'left','left','right','right','right','right','right','right',	'left',	'left',	'left',	'left','right','center','left');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Dimension')." 1", 'from' => get_dimension_string($dimension),'to' => ''),
						4 => array('text' => _('Sales Folk'), 'from' => $salesfolk,	'to' => ''));
						

    $rep = new FrontReport(_('Sales Register with SO and DO Details'), "SalesRegisterwithSOandDODetails", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($fromcust,$from,$to, $dimension, $folk);
  
	$total_value =0;
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
	
      if($myrow['quantity'])
	  {
		$rep->TextCol(0, 1, sql2date($myrow['inv_date']));
		$rep->TextCol(1, 2, $myrow['inv_ref']);
		$rep->TextCol(2, 3, $myrow['name']);
		$rep->TextCol(3, 4, $myrow['stock_id']);
		$rep->TextCol(4, 5, $myrow['description']);
		$rep->TextCol(5, 6, $myrow['quantity']);
		if($myrow['tax_included'])
		{
		$rep->AmountCol(6, 7, $myrow['unit_price']-$myrow['unit_tax'],3);
		$rep->AmountCol(7, 8, $myrow['discount_percent'],2);
		$rep->AmountCol(8, 9, $myrow['quantity']*($myrow['unit_price']-$myrow['unit_tax'])*(100-$myrow['discount_percent'])*0.01,3);
		$rep->AmountCol(9, 10, $myrow['quantity']*$myrow['unit_tax']*(100-$myrow['discount_percent'])*0.01,3);
		$rep->AmountCol(10, 11, ($myrow['quantity']*($myrow['unit_price']-$myrow['unit_tax'])*(100-$myrow['discount_percent'])*0.01)+($myrow['quantity']*$myrow['unit_tax']*(100-$myrow['discount_percent'])*0.01),3);
		}
	    else
		{
		$rep->AmountCol(6, 7, $myrow['unit_price'],3);	
		$rep->AmountCol(7, 8, $myrow['discount_percent'],2);
		$rep->AmountCol(8, 9, $myrow['quantity']*$myrow['unit_price']*(100-$myrow['discount_percent'])*0.01,3);
		$rep->AmountCol(9, 10, $myrow['quantity']*$myrow['unit_tax']*((100-$myrow['discount_percent'])*0.01),3);
		$rep->AmountCol(10, 11, ($myrow['quantity']*($myrow['unit_price'])*(100-$myrow['discount_percent'])*0.01)+($myrow['quantity']*$myrow['unit_tax']*(100-$myrow['discount_percent'])*0.01),3);
		}
		$rep->TextCol(11, 12, sql2date($myrow['ord_date']));
		$rep->TextCol(12, 13, $myrow['reference']);
		
		$do_details=get_do_info($myrow['src_id']);
		
		$rep->TextCol(13, 14, sql2date($do_details['tran_date']));
		$rep->TextCol(14, 15, $do_details['reference']);
		
		$rep->TextCol(15, 16, get_cust_payment_reference($myrow['trans_no']));
		$rep->TextCol(16, 17, get_transaction_user($myrow['trans_no']));
		
		$com_res = get_comments_all($do_details['type'],$do_details['trans_no']);
		
		$rep->TextCol(17, 18, $com_res['memo_']);	
		
		
		
   		$rep->NewLine();
	  }
	}
		
	$rep->NewLine();
    $rep->End();
}

