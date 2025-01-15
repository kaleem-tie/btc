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
$page_security = 'SA_SALESMAN_COLLECTION_DETAILS_REP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Sales Summary Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//------------------------------------------------------------------


print_sales_summary_report();

function getTransactions($from, $to, $folk, $leg_grp=0, $cust_class=0)
{
	$fromdate = date2sql($from);
	$todate = date2sql($to);

	$sql = "SELECT dt.*,d.debtor_no, d.name AS DebtorName, d.cust_code, dt.type, dt.trans_no,  
			(ov_amount+ov_freight+ov_discount+ov_roundoff) *dt.rate AS total, 
			dt.sales_person_id,sm.salesman_name
		FROM ".TB_PREF."debtor_trans dt
			LEFT JOIN ".TB_PREF."debtors_master d ON d.debtor_no=dt.debtor_no
			LEFT JOIN ".TB_PREF."salesman sm ON sm.salesman_code=dt.sales_person_id
		WHERE (dt.type=".ST_CUSTPAYMENT.") 
		AND dt.tran_date >= '$fromdate'
 		AND dt.tran_date <= '$todate'
		AND dt.ov_amount!=0";
		
	/*if ($folk!=0)
		$sql .= " AND dt.sales_person_id= ".db_escape($folk)." ";*/
	
	
	if ($folk != 0){
	       $sql .= " AND dt.sales_person_id=".db_escape($folk);	
	}
	
	   if ($leg_grp != 0)
	{
		$sql .= " AND d.legal_group_id =".db_escape($leg_grp);
	}
	
	if ($cust_class != 0)
	{
		$sql .= " AND d.sale_cust_class_id =".db_escape($cust_class);
	}
	
	$sql .= " ORDER BY dt.sales_person_id,dt.debtor_no"; 
	
    return db_query($sql,"No transactions were returned");
}


function get_allocatable_vouchers_to_cust_transactions($customer_id = null, $trans_no=null, 
$type=null)
{
	$sql = "SELECT
		trans.type,
		trans.trans_no,
		trans.reference,
		trans.tran_date,
		debtor.name AS DebtorName, 
		debtor.curr_code,
		IF(prep_amount, prep_amount, ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount+ov_roundoff) AS Total,
		trans.alloc,
		trans.due_date,
		debtor.address,
		trans.version,
		amt,
		trans.debtor_no,
		trans.branch_code,
		trans.order_,
		trans.invoice_type
	 FROM ".TB_PREF."debtor_trans as trans
			LEFT JOIN ".TB_PREF."cust_allocations as alloc
				ON trans.trans_no = alloc.trans_no_to AND trans.type = alloc.trans_type_to AND alloc.person_id=trans.debtor_no,"
	 		.TB_PREF."debtors_master as debtor
	 WHERE
	 	 trans.debtor_no=debtor.debtor_no";
	if ($customer_id)
		$sql .= " AND trans.debtor_no=".db_escape($customer_id);

	if ($trans_no != null and $type != null)
	{
		$sql .= " AND alloc.trans_no_from=".db_escape($trans_no)."
				  AND alloc.trans_type_from=".db_escape($type);
	}
	else
	{
		$sql .= "
				 AND (
					trans.type='".ST_SALESINVOICE."'
					AND round(IF(prep_amount, prep_amount, ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount+ov_roundoff)-alloc,6) > 0
					OR
					trans.type='". ST_CUSTCREDIT."'
					AND round(-IF(prep_amount, prep_amount, ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount+ov_roundoff)-alloc,6) > 0
					OR
				  	trans.type = '". ST_JOURNAL."'
					AND ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount>0
					OR
				  	trans.type = '". ST_BANKPAYMENT."'
					AND ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount>0
				)";
		$sql .= " GROUP BY type, trans_no";
	}
	

	return db_query($sql, "Cannot retreive alloc to transactions");
}


function get_sales_order_cust_ref($order_)
{
    $sql="SELECT customer_ref FROM ".TB_PREF."sales_orders 
	WHERE trans_type=30
	AND order_no=".db_escape($order_);
    
    $res=db_query($sql,"No transactions were returned");
    $row=db_fetch_row($res);
    return $row[0];
}

//----------------------------------------------------------------------------------------------------

function print_sales_summary_report()
{
	global $path_to_root;
	
	$from        = $_POST['PARAM_0'];
	$to          = $_POST['PARAM_1'];
	$folk        = $_POST['PARAM_2']; 
	$leg_grp     = $_POST['PARAM_3'];
   	$cust_class  = $_POST['PARAM_4'];
	$comments    = $_POST['PARAM_5'];
	$orientation = $_POST['PARAM_6'];
	$destination = $_POST['PARAM_7'];
	

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	$orientation = ($orientation ? 'L' : 'P');

	if ($folk == ALL_NUMERIC)
        $folk = 0;
    if ($folk == 0)
        $salesfolk = _('All Sales Man');
     else
        $salesfolk = get_salesman_name($folk);
	
	
	 if ($leg_grp == ALL_NUMERIC)
        $leg_grp = 0;
     if ($leg_grp == 0)
        $salesleg_grp = _('All Legal Group');
     else
        $salesleg_grp = get_legal_group_name($leg_grp);
	
	 if ($cust_class == ALL_NUMERIC)
        $cust_class = 0;
     if ($cust_class == 0)
        $salescust_class = _('All Customer Class');
     else
        $salescust_class = get_customer_class_name($cust_class);
	
	$dec = user_price_dec();

	if ($currency == ALL_TEXT)
    {
        $convert = true;
        $currency = _('Balances in Home Currency');
    }
    else
        $convert = false;
	

	$rep = new FrontReport(_('SalesManwise Collection Register - Details'), 
	"SalesManwiseCollectionRegisterDetails", user_pagesize(), 9, $orientation);

	$params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
						2 => array('text' => _('Sales Folk'), 'from' => $salesfolk,	'to' => ''),
						//3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
						3 => array('text' => _('Legal Group'), 'from' => $salesleg_grp, 	'to' => ''),
						4 => array('text' => _('Customer Class'), 'from' => $salescust_class, 	'to' => ''));

	$cols = array(0, 60,140,240,320,390,450,520);

	$headers = array(_('Date'), _('Pay.Ref.No.'), _('Invoice No.'), _('Inv Date'), 
	_('Ref. No.'), _('Ref Date'), _('Amount'));
	
	$aligns = array('left', 'left','left', 'left','left', 'left', 'right');
	
	
    if ($orientation == 'L')
    	recalculate_cols($cols);

	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->NewPage();
	
	$res = getTransactions($from, $to,$folk, $leg_grp, $cust_class);

	$inv_total = $total_cust = $total_collection = $inv_grand_total = 0.0;
	
	
	$salesper =  $customer_name = $cust_names = '';
	
	
	while ($trans=db_fetch($res))
	{
		
		if ($customer_name != $trans['DebtorName'])
		{
			
			if ($customer_name != '')
			{
				
				$rep->NewLine(2, 3);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(2, 6, _('Total for ').$customer_name);
				$rep->AmountCol(6, 7, $total_cust, $dec);
				$rep->SetFont('', '', 0);
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$rep->NewLine();
				$total_cust =  0.0;
			}
			
			$customer_name = $trans['DebtorName'];
			$customer_code = $trans['cust_code'];
		}
		
		if ($salesper != $trans['salesman_name'])
		{
			if ($salesper != '')
			{
				$rep->NewLine(2, 3);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(4, 6, _('Total Collection By ').$salesper);
				$rep->AmountCol(6, 7, $total_collection, $dec);
				$rep->SetFont('', '', 0);
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$rep->NewLine();
				$total_collection = 0.0;
			}
			$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 4, _('SalesMan : ').$trans['salesman_name']);
			$rep->SetFont('', '', 0);
			$salesper = $trans['salesman_name'];
			$rep->NewLine();
			
		}
		
		
		 if ($cust_names != $trans['DebtorName'])
		{
			$rep->NewLine();
			$rep->TextCol(1, 5, _('Customer : ').$trans['cust_code']." ".$trans['DebtorName']);
			$cust_names = $trans['DebtorName'];
			$rep->NewLine();
			
		}
		
		
		    $inv_type = "";
			
		
		$rep->DateCol(0, 1,	$trans['tran_date'], true);
		$rep->TextCol(1, 2,	$inv_type. "  ".$trans['reference']);
		
		
		$alloc_result = get_allocatable_vouchers_to_cust_transactions($trans['debtor_no'], $trans['trans_no'], ST_CUSTPAYMENT);
		
		$flag=0;
		while ($alloc_row = db_fetch($alloc_result))
        {
			
            $flag =1;
			
				
				$alloc_type = "";
				
				$inv_cust_ref = get_sales_order_cust_ref($alloc_row['order_']);
				
				$rep->TextCol(2, 3,	$alloc_type. "  ".$alloc_row['reference']);
				$rep->DateCol(3, 4,	$alloc_row['tran_date'], true);
				$rep->TextCol(4, 5,	$inv_cust_ref);
				$rep->TextCol(5, 6,	"");
				
				
				$rep->TextCol(6, 7, number_format2($alloc_row['amt'], $dec));
				
			   
				$rep->NewLine();
			
            
            $total_cust += $alloc_row['amt'];	
            $total_collection += $alloc_row['amt'];	
            $inv_total += $alloc_row['amt']; 			
            $inv_grand_total += $alloc_row['amt'];			
				
		}
		
		if($flag==0){
			$rep->TextCol(6, 7, number_format2($trans['total'], $dec));
			$total_cust += $trans['total'];	
            $total_collection += $trans['total'];	
            $inv_total += $trans['total']; 			
            $inv_grand_total += $trans['total'];	
		}
			
		
		
		
		
		$rep->NewLine();
		
	}
	
	
	if ($customer_name != '')
	{
		$rep->NewLine(2, 3);
		$rep->SetFont('helvetica', 'B', 9);
		$rep->TextCol(2, 6, _('Total for '). $customer_name);
		$rep->AmountCol(6, 7, $total_cust, $dec);
		$rep->SetFont('', '', 0);
		$rep->Line($rep->row - 2);
		$rep->NewLine();
		$rep->NewLine();
	}


    
	$rep->NewLine(1, 2);
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(3, 6, _('Total Collection By ').$salesper);
	$rep->AmountCol(6, 7, $total_collection, $dec);
	$rep->SetFont('', '', 0);
	
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->NewLine();
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(4, 6, _('Grand Total'));
	$rep->AmountCol(6, 7, $inv_grand_total, $dec);
	$rep->SetFont('', '', 0);

	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

