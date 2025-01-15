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
$page_security = 'SA_SCHEDULE_DELIVERY_REP';

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

print_sales_deliveries();

function get_transactions($customer_id=null,$from_date,$to_date)
{
	
	$today = Today(); 
    $today = date2sql($today);
	
	$from_date = date2sql($from_date);
	$to_date   = date2sql($to_date);
	
   $sql = "SELECT 
			sorder.order_no,
			sorder.reference,
			sorder.delivery_date,
			sorder.debtor_no,
			debtor.name,
			sorder.ord_date,
			line.stk_code,
			line.description,
			line.quantity AS TotQuantity,
			line.qty_sent AS qty_sent,
			line.id as order_line_id,
            line.planned_status,
			'' AS update_reason,
			sorder.delivery_address as delivery_address,
			sorder.sales_person_id,
			sorder.contact_phone,
			sorder.from_stk_loc,
            'so' as sale_table,
            sorder.delivery_time as delivery_time			
		FROM ".TB_PREF."sales_orders as sorder,
		    ".TB_PREF."sales_order_details as line, 
			".TB_PREF."debtors_master as debtor
			WHERE sorder.order_no = line.order_no
			AND sorder.trans_type = line.trans_type
			AND sorder.trans_type = 30
			AND sorder.debtor_no = debtor.debtor_no
			AND line.quantity-line.qty_sent>0
			AND sorder.reference!='auto'
			AND line.planned_status='0'";
		$sql .=  " AND sorder.delivery_date >= '$from_date'"
				." AND sorder.delivery_date <= '$to_date'";		
			
		if ($customer_id != ALL_TEXT)
			$sql .= " AND sorder.debtor_no = ".db_escape($customer_id);
		$sql .=" UNION
		SELECT soplan.order_no,
         sorder.reference,
		 soplan.planned_date AS delivery_date,
		 sorder.debtor_no,
		 debtor.name,
		 sorder.ord_date,
		 line.stk_code,
		 line.description,
		 line.quantity AS TotQuantity,
		 line.qty_sent AS qty_sent,
		 soplan.order_line_id as order_line_id,
		 '' AS planned_status,
		 soplan.update_reason,
		sorder.delivery_address as delivery_address,
		sorder.sales_person_id,
		sorder.contact_phone,
		sorder.from_stk_loc,
        'sd' as sale_table,
        soplan.planned_delivery_time as delivery_time		
    	FROM ".TB_PREF."sales_delivery_plan as soplan,
		".TB_PREF."sales_orders as sorder,
		".TB_PREF."sales_order_details as line,
		".TB_PREF."debtors_master as debtor
    	WHERE soplan.order_no = sorder.order_no
		AND sorder.order_no = line.order_no
		AND sorder.trans_type = line.trans_type
		AND soplan.order_line_id = line.id
    	AND sorder.debtor_no = debtor.debtor_no
		AND line.quantity-line.qty_sent>0
		AND soplan.has_child=0";
	$sql .=  " AND soplan.planned_date >= '$from_date'"
					." AND soplan.planned_date <= '$to_date'";		
	if ($customer_id != ALL_TEXT)
			$sql .= " AND sorder.debtor_no = ".db_escape($customer_id);	
				
    $sql .=" ORDER BY delivery_date ASC";
	
	return db_query($sql,"No transactions were returned");
}

function get_sales_delivery_planned_date_rep($order_no,$order_line_id)
{
	$sql = "SELECT planned_date FROM ".TB_PREF."sales_delivery_plan 
			WHERE order_no = ".db_escape($order_no)."
			AND order_line_id = ".db_escape($order_line_id)."
			ORDER BY DESC";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
	
}	

function get_item_actual_delivery_date_rep($order_no)
{
	$sql = "SELECT delivery_date FROM ".TB_PREF."sales_orders 
			WHERE order_no = ".db_escape($order_no)."";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
	
}	

//----------------------------------------------------------------------------------------------------

function print_sales_deliveries()
{
    	global $path_to_root, $systypes_array, $delivery_times;
    	
		$from_date   = $_POST['PARAM_0'];
    	$to_date     = $_POST['PARAM_1'];
    	$fromcust    = $_POST['PARAM_2'];
		$currency    = $_POST['PARAM_3'];
    	$orientation = $_POST['PARAM_4'];
	    $destination = $_POST['PARAM_5'];
		
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	//$orientation = ($orientation ? 'P' : 'L');
     $orientation = 'L';

	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
	
	
	if ($plan_type == 0)
		$plan = _('All');
	else if ($plan_type == 1)
		$plan = _('Outstanding');
	else if ($plan_type == 2)
		$plan = _('Planned');
    	
	$dec = user_price_dec();
	
	
	$cols = array(0, 30,70,130,180,225,255,320,430,470,520,550);

    $headers = array(_('Reference'), _('Order Date'), _('Sales person'), 
                 _('Dispatch Location'), _('Delivery Date'),_('Time'), _('Address'), 
	            _('Item Name'), _('Quantity'),  _('Customer'),_('Contact No'));
				
    $aligns = array('left','left','left','left','left','left','left','left','center',
	'left','right');

    $params =   array( 	0 => $comments,
	                    1 => array('text' => _('Period'), 'from' => $from_date, 		'to' => $to_date),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''));

    $rep = new FrontReport(_('Upcoming Deliveries'), "Upcoming Deliveries", user_pagesize(), 7, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($fromcust,$from_date,$to_date);
  $k=1;
  $tot_value = 0;

 
	while ($myrow = db_fetch($result))
	{
		
		
	    $rep->TextCol(0, 1, $myrow['reference']);
		$rep->TextCol(1, 2, sql2date($myrow['ord_date']));
		$rep->TextCol(2, 3, get_salesman_name($myrow['sales_person_id']));
		$rep->TextCol(3, 4, get_location_name($myrow['from_stk_loc']));
		$rep->TextCol(4, 5, sql2date($myrow['delivery_date']));
		
		$rep->TextCol(5, 6, $delivery_times[$myrow['delivery_time']]);

		if ($destination){
		$rep->TextCol(6, 7, $myrow['delivery_address']);	
		}
         
		else{
		$oldrow = $rep->row;
		$rep->TextColLines(6, 7, $myrow['delivery_address'], -2);
		$newrow = $rep->row;
		$rep->row = $oldrow;
		}
        
		$rep->TextCol(7, 8, $myrow['description']);	
		
		$qdec = get_qty_dec($myrow['stk_code']);
		$rep->TextCol(8, 9, $myrow['TotQuantity']-$myrow['qty_sent']);
		$name = $myrow["debtor_no"]." ".$myrow["name"];
		$rep->TextCol(9, 10, $name);
		$rep->TextCol(10, 11, $myrow['contact_phone']);
        
		if ($destination){
		$rep->NewLine();
		}
		else{
		$rep->row = $newrow;
		}
		$rep->NewLine();
		$k++;
	}
    
   $rep->Line($rep->row - 4);	
   $rep->NewLine();
   $rep->End();
}

