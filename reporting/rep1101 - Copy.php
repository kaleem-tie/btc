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
$page_security = 'SA_STAT_ACC_PDC_REP';

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

function get_transactions($fromcust=ALL_TEXT,$to,$location,$dimension=0, $folk=0)
{
	
	$to = date2sql($to);

	$sql = "SELECT DISTINCT
		trans.type,
		trans.trans_no,
		trans.reference,
		trans.tran_date,
		debtor.name AS DebtorName, 
		debtor.curr_code,
		trans.alloc,
		trans.rate,
		 trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff AS Total,
		trans.prep_amount,
		trans.ov_gst,
		trans.due_date,
		debtor.address,
		trans.debtor_no,
		debtor.curr_code,
		trans.tax_included,
		trans.ov_roundoff,
		trans.lpo_no AS LpoNum,
		trans.pdc_amt AS pdc_amt,
		debtor.cust_code,
		MONTH()
	 FROM ".TB_PREF."debtor_trans as trans, 
	 ".TB_PREF."debtors_master as debtor"." 
	 WHERE trans.debtor_no=debtor.debtor_no
	AND ((trans.type=".ST_SALESINVOICE.")OR(trans.type=".ST_CUSTPAYMENT.")OR(trans.type=".ST_CUSTCREDIT.") AND ((trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff)-trans.alloc ) >= 0.1)";
		
	$sql .=  " AND trans.tran_date <= '$to'";
					
					

	if ($fromcust != ALL_TEXT)
			$sql .= " AND trans.debtor_no = ".db_escape($fromcust);	
		
	/*if ($location != '')
		$sql .= " AND sorder.from_stk_loc = ".db_escape($location);	*/
	
	if ($folk != 0)
			$sql .= " AND trans.sales_person_id = ".db_escape($folk);
		
	/*if ($dimension != 0){
  		$sql .= " AND trans.dimension_id = ".($dimension<0 ? 0 : db_escape($dimension));
		}*/
   /* else{
        $user_dms=$_SESSION["wa_current_user"]->user_dimensions;
		$sql .= " AND FIND_IN_SET(trans.dimension_id,'$user_dms')";
        }	*/
		
    $sql .=" ORDER BY trans.tran_date";
	
	//display_error($sql);
	
	return db_query($sql,"No transactions were returned");
}






//----------------------------------------------------------------------------------------------------

function print_customer_orders()
{
    	global $path_to_root, $systypes_array;

    	
    	$to = $_POST['PARAM_0'];
    	$fromcust = $_POST['PARAM_1'];
		$folk = $_POST['PARAM_2'];  // added by Faisal to filter by sales person
		$currency = $_POST['PARAM_3'];
    	$orientation = $_POST['PARAM_4'];
	    $destination = $_POST['PARAM_5'];
		
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
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
	
	if ($currency == ALL_TEXT)
    {
        $convert = true;
        $currency = _('Balances in Home Currency');
    }
    else
        $convert = false;
	
	/*if ($location == '')
		$loc = _('All');
	else
		$loc = get_location_name($location);*/

	// $cols = array(0, 20, 65, 210,250,280,320,370,450,500,550,600);
	//$cols = array(0, 45, 65, 100,150,180,230,280,330,470,500,550);
	//$cols = array(0,45,95,130,180,210,260,310,360);
	
	$cols = array(0, 80, 120, 180, 220, 250, 310, 390, 450, 530);
	
	$cols2 = array(0,100, 180, 240, 300, 350, 400,480);	

	$headers = array( _('Invoice Date'),_(''), _('Invoice No.'), _('LPO No.'),  
	_(''), _('Invoice Amount'),_('Settle Amount'), _('PDC Amount'), 
	_('Invoice Balance') );

	
	$aligns = array('left','left','left','left','center','center','center','center','center');
	
	/*$aligns2 = array('left','left', 'left', 'left', 'left','left', 'left','left');
	
	
	$c_date = $to;  
   
    $date = date2sql($date);
    $mon  = date('m', strtotime($date));
    $year   = date('Y', strtotime($date));
	
	$selected_year   = date('Y', strtotime($date));
	
	$str = ($year-1).'-'.$mon;
	$start_date = date('Y-m-d', strtotime("-3 months", strtotime($date)));
	
	$start =  new DateTime($start_date);
 
    $interval = new DateInterval('P1M');
		$end = new DateTime($date);
	    $end->add($interval);
    $period   = new DatePeriod($start, $interval, $end);
    $months = array();
	
    foreach ($period as $dt) { 
        $months[$dt->format('Y-m-d')] = $dt->format('Y-m');
    }
    $reverse_months = array_reverse($months);


	$headers2 = array(_(''),_(''), _(''),_(''), _(''),_(''),_(''),_(''));
	
	

	foreach($reverse_months as $key => $mns){
	   $date = strtotime($mns);
		$lastdate = strtotime(date("Y-m-t", $date ));
		$day = date("d", $lastdate);
		$month = date('M', $lastdate);
		$year = date('Y', $lastdate);
	
	$my_date = array_push($headers,$month.' '.$year);
	
	$per1 = $tmonths[date('n',mktime(0,0,0,$mo-1,$da,$yr))];
		
	//display_error(json_encode($headers)); 
		
    }
	
	$y = 480;
	foreach($reverse_months as $key => $mns){
		
	for($j=1;$j<=28;$j++){
		
	$y += 30;
	//display_error($y);
	}
	array_push($cols,$y);
	array_push($aligns,'center');
	
	}
	
	array_push($cols,$y+800);
	array_push($aligns,'center');*/


   

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						4 => array('text' => _('Sales Folk'), 'from' => $salesfolk,	'to' => ''));

    $rep = new FrontReport(_('Statement of Accounts with PDC Match'), "StatementofAccountswithPDCMatch", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->SetHeaderType('Header40');
    $rep->NewPage();
	
	 //$rep->Info($params, $cols2, $headers2, $aligns2);
	

	
	$sql = "SELECT debtor_no, name, curr_code,address, cust_code FROM ".TB_PREF."debtors_master ";
	$sql .= "WHERE 1=1";
	if ($fromcust != ALL_TEXT)
		$sql .= " AND debtor_no=".db_escape($fromcust);
	$sql .= " ORDER BY name";
	
	//display_error($sql);
	
	$res = db_query($sql, "The customers could not be retrieved");
	
while ($trans = db_fetch($res)){
	
	 
     if ($debtor != $myrow['name'])
		{
			$m=1;
			if ($debtor != '')
			{
			if ($destination!='1')	{
		     $rep->NewLine();
			}
		     $rep->NewPage();
			}  
            //$rep->NewLine();
		    //$rep->Font('bold');
		    $rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 4, _('').$trans['cust_code']."    ".$trans['name']);
			$rep->NewLine();
			$rep->TextCol(0, 7, _('CR No. :  ').$trans['cr_no']);
			$rep->Font();
			$rep->NewLine();
			 $rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 3, "Address :");
			$rep->TextWrapLines($ccol+90, $icol - $ccol-100, $trans['address']);
		
			 $contacts = get_branch_contacts($trans['debtor_no']);
			//if(!$convert)
			$rep->TextCol(0, 3, _('Currency : ').$trans['curr_code']);
			//else
			$rep->NewLine();
			$rep->TextCol(0, 3, _('Phone : ').$contacts['phone']."  ". _('Fax : ').$contacts['fax']);
			$rep->NewLine();
			$rep->NewLine(2);
		}
			//$rep->Text($ccol+40, str_pad('', 120, '_'));
			$rep->NewLine();
			
			$rep->Text($ccol+40, _('Dear Sir, '));
			
			$rep->NewLine(2);
			$rep->Text($ccol+80, _('Ref : Outstanding Bills'));
			$rep->NewLine(2);
			$rep->Text($ccol+80, _('The following bills are outstanding as on ').$to);
             $rep->NewLine(1);
			 
			$rep->Text($ccol+40, str_pad('', 120, '_'));
			$rep->NewLine();
			
			//$debtor = $myrow['name'];
		
			$rep->NewLine(1);
			
			$rep->Line($rep->row - 2);
			
			
			//$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(6, 7, "Amounts in LC");
			//$rep->Text($ccol-40, str_pad('', 120, '_'));
			
		//	$rep->Font('bold');
			$rep->SetFont('helvetica', 'B', 9);
			$rep->NewLine(2);
			
			
			$rep->TextCol(0, 1, "Invoice Date");
			$rep->TextCol(1, 2, "");
			$rep->TextCol(2, 3, "Invoice No.");
			$rep->TextCol(3, 4, "LPO No.");
			$rep->TextCol(4, 5, "");
			$rep->TextCol(5, 6, "Invoice Amount");
			$rep->TextCol(6, 7,	"Settle Amount");
			$rep->TextCol(7, 8, "PDC Amount");
			$rep->TextCol(8, 9, "Invoice Balance");
			
			$rep->NewLine();
			//$rep->TextCol(4, 5, "LPO No.");
			$rep->Line($rep->row - 2);	
			$rep->Font();
			
			$rep->NewLine();
			
			  $rep->TextCol(0, 1, "");
			
			$m++;
			//$rep->NewPage();
			
    //}		
			 
  $result=get_transactions($fromcust,$to, $location,$dimension, $folk);
  
	
$k=1;
$tot_value = 0;
$total_taxable_value=0;
$total_tax_value=0;
$total_roundoff=0;

$invoice_total = $balance = 0;
 
	while ($myrow = db_fetch($result)){
	    
	
		if (!$convert && $currency != $myrow['curr_code']) continue;
		
	    if($myrow['rate']==1)
	    $rep->SetTextColor(33, 33, 33);
	    else
	    $rep->SetTextColor(216, 67, 21);
	    
		
		$rep->NewLine(1, 3);
		$rep->TextCol(0, 1, sql2date($myrow['tran_date']));
		
		if ($myrow['type'] == 10)
		{
			$type= "SI";
			$dr=" Dr";
		}
		
		if ($myrow['type'] == 11)
		{
			$type= "SR";
			$dr=" Cr";
		}
		
		if ($myrow['type'] == 12)
		{
			$type= "BR";
			$dr=" Cr";
		}
		
		//display_error($type);
		
		$rep->TextCol(1, 2, $type);
		$rep->TextCol(2, 3, $myrow['reference']);
		$rep->TextCol(3, 4, $myrow['LpoNum']);
		//$rep->TextCol(3,4, $myrow['']);
		$rep->TextCol(4, 5, $myrow['']);
		
		
		
			//$tot_value = $myrow['Total'];	
			
			$tot_value = $myrow['Total'];
		//$rep->AmountCol(5,6, $tot_value,$dec);
		$rep->TextCol(5,6, number_format2($tot_value,$dec).$dr);
		$rep->AmountCol(6, 7, $myrow['alloc'],$dec);
		
		$rep->AmountCol(7,8, $myrow['pdc_amt'],$dec);
		
		$balance=$tot_value-$myrow['alloc']-$myrow['pdc_amt'];
		
		
		if($myrow['tax_included']==1)
		{
			//Ramesh
		 $Total_amt=0;
		 $Total_tax=0;
		  $tax_items = get_trans_tax_details(ST_SALESINVOICE, $myrow['trans_no']);
    		while ($tax_item = db_fetch($tax_items))
    		{
    			if ($tax_item['amount'] == 0)
    				continue;
    			$Total_tax += $tax_item['amount'];
				
				if ($tax_item['net_amount'] == 0)
    				continue;
				$Total_amt += $tax_item['net_amount'];
    		}
			//$rep->AmountCol(7,8, $Total_amt*$myrow['rate'],$dec);
			
			
			if ($trans['type'] == ST_CUSTCREDIT || $trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_BANKDEPOSIT){
			$rep->TextCol(8, 9, number_format2($inv_balance, $dec)." Cr");
			$balance -= $inv_balance;
			}
		    else{
			$rep->TextCol(8, 9, number_format2($inv_balance, $dec)." Dr");	
			$balance += $inv_balance;
			}
			
			
			//$rep->TextCol(8,9, number_format2($balance,$dec).$dr);
			//$rep->AmountCol(8,9, $balance,$dec);
			//$rep->AmountCol(8,9, $Total_tax*$myrow['rate'],$dec);
			//$rep->AmountCol(9,10, $myrow['ov_roundoff'],$dec);

		
		
			//$total_taxable_value+=$tot_value;
			$total_alloc_value+=$myrow['alloc'];
			$total_pdc+=$myrow['pdc_amt'];
			//$total_balance+=$balance;
			$grand_total += $net_balance_total;
			$invoice_total = $balance;
			$net_balance_total = $invoice_total - $total_pdc;
		} else {
			//$rep->AmountCol(7,8, $myrow['Total']*$myrow['rate'],$dec);
			//$rep->AmountCol(8,9, $myrow['ov_gst']*$myrow['rate'],$dec);
			$rep->TextCol(8,9, number_format2($balance,$dec).$dr);
			//$rep->AmountCol(8,9, $balance,$dec);
			//$rep->AmountCol(9,10, $myrow['ov_roundoff'],$dec);
			
			//$total_taxable_value+=$tot_value;
			$total_alloc_value+=$myrow['alloc']*$myrow['rate'];
			$total_pdc+=$myrow['pdc_amt'];
			//$total_balance+=$balance;
			$grand_total += $net_balance_total;
			$net_balance_total = $invoice_total - $total_pdc;
			$cust_code=$myrow['cust_code'];
		}
		
		
		//$rep->AmountCol(10,11, $myrow['Total']+$myrow['ov_gst']+$myrow['ov_roundoff'],$dec);
		// $rep->AmountCol(7, 8, $myrow['prep_amount'],2);
		
	
   		$rep->NewLine(1);
		
		$k++;

		 
	  }	
	
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
	$rep->NewLine();
	
	$rep->TextCol(0, 1, 'Closing Balance :' );
	$rep->TextCol(1, 2,  $cust_code);	
	$net_balance_total = $invoice_total - $total_pdc;
    $rep->TextCol(5, 6, number_format2($invoice_total,$dec)." Dr");
	$rep->AmountCol(6, 7, $total_alloc_value,$dec);
	$rep->AmountCol(7, 8, $total_pdc,$dec);
	$rep->TextCol(8, 9, number_format2($net_balance_total,$dec)." Dr");
	$rep->Text($ccol+40, str_pad('', 120, '_'));
	$rep->NewLine(2);
    
	$rep->NewLine(2);
	 $rep->TextCol(0, 1, $myrow['PER1']  );
	 
	 $rep->SetFont('helvetica', 'B', 9);
	$rep->Text($ccol+80, _('Payment against overdue bills may please be settlled immediately on receipt of this statement'));
	$rep->NewLine(3);
	$rep->SetFont('helvetica', 'B', 9);  
	$rep->Text($ccol+80, _('Thanking You'));
	$rep->NewLine();
	$rep->SetFont('helvetica', 'B', 9); 
	$rep->Text($ccol+80, _('For'));
	$rep->Text($ccol+120, _(' BAHANIS TRADING CO. LLC'));
	$rep->NewLine(5);
	$rep->SetFont('helvetica', 'B', 9);  
	$rep->Text($ccol+80, _('Accountant'));
	$rep->NewLine(2);
	$rep->SetFont('helvetica', 'B', 9);  
	$rep->Text($ccol+80, _('Note 1: In case the bills have already been settled, please ignore this statement and'));
	$rep->NewLine();
	$rep->SetFont('helvetica', 'B', 9); 
	$rep->Text($ccol+90, _('let us have the payment details.'));
	$rep->NewLine();
	$rep->SetFont('helvetica', 'B', 9); 
	$rep->Text($ccol+85, _('2: In case of any discrepancies, please inform us immediately'));
	
	$rep->End();
	
  }
  

	   
	
	}
