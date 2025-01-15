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
$page_security = 'SA_SALESMAN_OUTSTAND_REP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Inventory Sales Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

//----------------------------------------------------------------------------------------------------

print_salesman_outstanding_register();

function getTransactions($sales_person=0,$to,$leg_grp=0,$cust_class=0)
{
	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
	
	
	$sql = "SELECT cust.name,cust.curr_code,cust.cust_code,salesman.salesman_name,";
	
	$sql .=	"SUM(IF(trans.type = ".ST_SALESINVOICE." OR (trans.type IN (".ST_JOURNAL." , ".ST_BANKPAYMENT.") AND trans.ov_amount>0), 1, -1) *
			(IF(trans.prep_amount, trans.prep_amount, abs(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff)) - abs(trans.alloc))) AS OutStanding,SUM(trans.pdc_amt) AS pdc_amt
		FROM ".TB_PREF."debtor_trans trans,
			 ".TB_PREF."debtors_master cust,
			".TB_PREF."salesman salesman
		WHERE trans.sales_person_id=salesman.salesman_code
		AND trans.debtor_no=cust.debtor_no
        AND trans.type IN (".ST_JOURNAL.",".ST_BANKPAYMENT.",".ST_BANKDEPOSIT.",
		".ST_CUSTCREDIT.",".ST_CUSTPAYMENT.",".ST_SALESINVOICE.")
        AND trans.tran_date <= '$todate'";
		$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff) - trans.alloc) > 0";	
			
		if ($sales_person != 0)
	       $sql .= " AND trans.sales_person_id=".db_escape($sales_person);	
	   
	    if ($leg_grp != 0)
	    {
		$sql .= " AND cust.legal_group_id =".db_escape($leg_grp);
	    }
	
	    if ($cust_class != 0)
	    {
		$sql .= " AND cust.sale_cust_class_id =".db_escape($cust_class);
	    }
	   
		$sql .= " GROUP BY trans.sales_person_id,trans.debtor_no 
		ORDER BY trans.sales_person_id,trans.debtor_no";
	
    return db_query($sql,"No transactions were returned");

}


//----------------------------------------------------------------------------------------------------

function print_salesman_outstanding_register()
{
    global $path_to_root;

	$to           = $_POST['PARAM_0'];
    $sales_person = $_POST['PARAM_1'];
	$leg_grp      = $_POST['PARAM_2'];
    $cust_class   = $_POST['PARAM_3'];
	$comments     = $_POST['PARAM_4'];
	$orientation  = $_POST['PARAM_5'];
	$destination  = $_POST['PARAM_6'];
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
    $dec = user_price_dec();


	if ($sales_person == ALL_NUMERIC)
		$sales_person = 0;
	if ($sales_person == 0)
		$sales_person_name = _('All Sales person');
	else
		$sales_person_name = get_salesman_name($sales_person);
	
	
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

	
	$cols = array(0, 60, 300, 380, 450,520);

	$headers = array(_('Code'), _('Account'), _('O/S Amount'), _('PDC Amount'), _('Net Balance'));
	

	$aligns = array('left',	'left',	 'right', 'right', 'right');

    $params =   array( 0 => $comments,
    				  1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
    				  2 => array('text' => _('Sales Person'), 'from' => $sales_person_name, 'to' => ''),
					  3 => array('text' => _('Legal Group'), 'from' => $salesleg_grp, 	'to' => ''),
					  4 => array('text' => _('Customer Class'), 'from' => $salescust_class, 	'to' => ''));

    $rep = new FrontReport(_('SalesManwise Outstanding Register - Summary'), 
	"SalesManwiseOutstandingRegisterSummary", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$res = getTransactions($sales_person, $to, $leg_grp, $cust_class);

	
	
	$os_amt_total = $pdc_amt_total = $net_bal_total = 0.0;
	$grand_net_total =  0;
	
	$salesper = '';
	
	while ($trans=db_fetch($res))
	{
		
			
		
		if ($salesper != $trans['salesman_name'])
		{
			if ($salesper != '')
			{
				$rep->NewLine(2, 3);
				$rep->SetFont('helvetica', 'B', 9);
				$rep->TextCol(0, 1, _('Total'));
				//$rep->TextCol(1, 4, $salesper);
				
				if ($net_bal_total > 0.0)
	            {
	            $rep->TextCol(4, 5, number_format2($net_bal_total, $dec)." Dr");
	            }
	            else{
	            $rep->TextCol(4, 5, number_format2(-$net_bal_total, $dec)." Cr");
	            }
				
				//$rep->AmountCol(4, 5, $net_bal_total, $dec);
				$rep->SetFont('', '', 0);
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$rep->NewLine();
				$net_bal_total = 0.0;
			}
			$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 6, _('SalesMan : ').$trans['salesman_name']);
			$rep->Text($ccol+40, str_pad('', 25, '_'));
			$rep->SetFont('', '', 0);
			$salesper = $trans['salesman_name'];
			$rep->NewLine();
		}
		
		
		
		
		$net_bal = $trans['OutStanding']-$trans['pdc_amt'];
		
		$rep->TextCol(0, 1, $trans['cust_code']);
        $rep->TextCol(1, 2, $trans['name']);
		
		
		if ($trans['OutStanding'] > 0.0)
		{
		$rep->TextCol(2, 3, number_format2($trans['OutStanding'], $dec)." Dr");		
		}
		else{
		$rep->TextCol(2, 3, number_format2(-$trans['OutStanding'], $dec)." Cr");	
		}	
       
		$rep->TextCol(3, 4, number_format2($trans['pdc_amt'], $dec)." Cr");
		
		if ($net_bal > 0.0)
		{
        $rep->TextCol(4, 5, number_format2($net_bal, $dec)." Dr");
		}
		else{
		$rep->TextCol(4, 5, number_format2(-$net_bal, $dec)." Cr");	
		}	
		
	    $rep->NewLine();
		
		
		$os_amt_total += $trans['OutStanding'];
		$pdc_amt_total += $trans['pdc_amt'];
		$net_bal_total += $net_bal;
		
		$grand_net_total += $net_bal;
	}
	
	


	$rep->NewLine(2, 3);
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(2, 3, _('Total'));
	//$rep->TextCol(2, 4, $salesper);
	
	if ($net_bal_total > 0.0)
	{
	$rep->TextCol(4, 5, number_format2($net_bal_total, $dec)." Dr");
	}
	else{
	$rep->TextCol(4, 5, number_format2(-$net_bal_total, $dec)." Cr");
	}
	$rep->NewLine(1);
	
	$rep->Line($rep->row  - 4);
	$rep->NewLine(2);
	
	$rep->TextCol(2, 3, _('Grand Total'));
	//$rep->TextCol(2, 4, $salesper);
	if ($grand_net_total > 0.0)
	{
	$rep->TextCol(4, 5, number_format2($grand_net_total, $dec)." Dr");
	}
	else{
	$rep->TextCol(4, 5, number_format2(-$grand_net_total, $dec)." Cr");
	}
	
	
	//$rep->TextCol(4, 5, number_format2($grand_net_total, $dec)." Dr");
	$rep->NewLine();
	$rep->SetFont('', '', 0);

	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

