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
$page_security = 'SA_CUST_SA_BAL_CONFIRM_REP'; 

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

//------------------------------------------------------------------------------------

print_statement_accounts_balances();

function get_transactions($customer_id, $to=null, $folk=0)
{

	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
		
	
	
	//ravi

	$sign = "IF(trans.type IN(".implode(',',  array(ST_CUSTCREDIT,ST_CUSTPAYMENT,ST_BANKDEPOSIT))."), -1, IF(trans.type=".ST_JOURNAL." AND trans.ov_amount<0,-1,1))";
	
	$value = "$sign*(IF(trans.prep_amount, trans.prep_amount,
		ABS(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff )) ".($all ? '' : "- trans.alloc").")";

	$due = "IF (trans.type=".ST_SALESINVOICE.", trans.due_date, trans.tran_date)";
	$sql = "SELECT debtor.name, debtor.curr_code,Sum(IFNULL($value,0)) AS Balance

		FROM ".TB_PREF."debtors_master debtor
			LEFT JOIN ".TB_PREF."debtor_trans trans ON trans.tran_date <= '$todate' AND debtor.debtor_no = trans.debtor_no AND trans.type IN (".ST_BANKPAYMENT.",".ST_BANKDEPOSIT.",".ST_CUSTCREDIT.",
		".ST_CUSTPAYMENT.",".ST_SALESINVOICE.",".ST_JOURNAL."),
			".TB_PREF."payment_terms terms,
			".TB_PREF."credit_status credit_status

		WHERE
			 debtor.payment_terms = terms.terms_indicator
			 AND debtor.credit_status = credit_status.id";

	if ($customer_id)
		$sql .= " AND debtor.debtor_no = ".db_escape($customer_id);
	
	if ($folk != -1){
      $sql .= " AND trans.sales_person_id = ".db_escape($folk);
	}
		
	$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount + trans.ov_roundoff) - trans.alloc) > ".FLOAT_COMP_DELTA;	
	
	$sql .= " GROUP BY
				debtor.debtor_no,
				terms.terms,
				terms.days_before_due,
				terms.day_in_following_month,
				debtor.credit_limit,
				credit_status.dissallow_invoices,
				credit_status.reason_description";
				
    $result = db_query($sql,"The customer details could not be retrieved");

    $customer_record = db_fetch($result);

    return $customer_record;

}



//---------------------------------------------------------------------------------------------

function print_statement_accounts_balances()
{
    global $path_to_root, $systypes_array, $SysPrefs;
		
	
    $to           = $_POST['PARAM_0'];
    $fromcust     = $_POST['PARAM_1'];
	$folk         = $_POST['PARAM_2']; 
	$currency     = $_POST['PARAM_3'];	
    $no_zeros     = $_POST['PARAM_4'];
    $comments     = $_POST['PARAM_5'];
	$orientation  = $_POST['PARAM_6'];
	$destination  = $_POST['PARAM_7'];
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
	
	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
	
    $dec = user_price_dec();

	if ($show_balance) $sb = _('Yes');
	else $sb = _('No');

	if ($currency == ALL_TEXT)
	{
		$convert = true;
		$currency = _('Balances in Home Currency');
	}
	else
		$convert = false;

	if ($no_zeros) $nozeros = _('Yes');
	else $nozeros = _('No');
	
	
	 $cols = array(0, 90);
     $headers = array();
     $aligns = array('');
	
	

    $params =   array( 	0 => $comments);

    $rep = new FrontReport(_('Statement of Accounts - Balance Confirmation'), 
	"StatementofAccountsBalanceConfirmation", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
	$rep->SetHeaderType('Header41');
    $rep->NewPage();



	$sql = "SELECT debtor_no, name, curr_code,address FROM ".TB_PREF."debtors_master";
	if ($fromcust != ALL_TEXT)
		$sql .= " WHERE debtor_no=".db_escape($fromcust);
	$sql .= " ORDER BY name";
	$result = db_query($sql, "The customers could not be retrieved");
	

	while ($myrow = db_fetch($result))
	{
		
	   if (!$convert && $currency != $myrow['curr_code'])
			continue;

	   if ($convert) $rate = get_exchange_rate_from_home_currency($myrow['curr_code'], $to);
		else $rate = 1.0;
		
		$custrec = get_transactions($myrow['debtor_no'], $to, $folk);
		if (!$custrec)
			continue;	
		$custrec['Balance'] *= $rate;
		
		//if ($no_zeros && db_num_rows($res) == 0) continue;
		
		
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
			$rep->SetFont('helvetica', 'B', 9);
			if ($destination!='1')
				$rep->NewLine();
			$rep->Text($ccol+40, _("M/s ").$myrow['name']);
			$rep->SetFont('', '', 0);
			$rep->Text($ccol+440, $to);
			$rep->NewLine();
			if ($destination!='1')
			$rep->TextWrapLines($ccol+40, $icol - $ccol-100, $myrow['address']);
			$rep->NewLine();
			$contacts = get_branch_contacts($myrow['branch_code'], 'order', $myrow['debtor_no'], true);
			$rep->Text($ccol+40, _('Phone : ').$contacts['phone']);
			$rep->Text($ccol+150, _('Fax : ').$contacts['fax']);
			$rep->NewLine(2);
			$rep->Text($ccol+40, _('Dear Sir, '));
			$rep->NewLine(2);
			
			$rep->SetFont('helvetica', 'B', 9);
			$rep->Text($ccol+40, _('Re : Outstanding as on ').$to);
			$rep->SetFont('', '', 0);
			
			$rep->NewLine(2);
			$rep->Text($ccol+40, _('In accordance with the requirements of our Auditors M/s, Moore Stephens, Chartered Accountants, P. O Box-933, P/C-112,'));
			$rep->NewLine();
			$rep->Text($ccol+40, _('Ruwi, Sultanate of Oman, Tel# 968 24812041, Fax # 968 24812043, we request you to kindly confirm the balance in your account'));
			$rep->NewLine();
			
			$rep->Text($ccol+40, _('which as per our books is as under on ').$to);
			$rep->NewLine(2);
			$rep->SetFont('helvetica', 'B', 9);
			
			$cust_balance = number_format2($custrec['Balance'],$dec);
			
			$rep->Text($ccol+40, _('Balance due to us : ').$myrow['curr_code']." ".$cust_balance.' (as per detailed statement attached)');
			$rep->SetFont('', '', 0);
			$rep->NewLine(3);
			$rep->Text($ccol+40, _('Please post/fax this letter direct to our auditors. If the above amount does not agree with the balance as per your records, '));
			$rep->NewLine();
			$rep->Text($ccol+40, _('please send a detailed statement of our account in your books so that the difference can be reconciled. '));
			$rep->NewLine(3);
			$rep->Text($ccol+40, _('Your prompt reply will be highly appreciated. '));
			$rep->NewLine(3);
			$rep->Text($ccol+40, _('Yours faithfully'));
			$rep->NewLine(3);
			$rep->Text($ccol+40, _('For '));
            
			$rep->SetFont('helvetica', 'B', 10);
			$rep->Text($ccol+60, $rep->company['coy_name']);
			$rep->SetFont('', '', 0);
			$rep->NewLine(3);
		
			$rep->Text($ccol+40, _('Signature '));
		    $rep->NewLine();
			
			$rep->Text($ccol+40, str_pad('', 250, '..'));
			$rep->NewLine(1);
			
			$rep->Text($ccol+40, _('Moore Stephens '));
		    $rep->NewLine();
			$rep->Text($ccol+40, _('P. O. Box-933, P/C-112, Ruwi'));
		    $rep->NewLine();
			$rep->Text($ccol+40, _('Sultanate of Oman '));
		    $rep->NewLine();
			$rep->Text($ccol+40, _('Tel # 968 24812041'));
		    $rep->NewLine(3);
			$rep->SetFont('helvetica', 'B', 9);
			$rep->Text($ccol+40, _('We confirm that the balance in the account of the above referred company on ').$to.' i.e');
		    $rep->NewLine();
			$rep->Text($ccol+40, $myrow['curr_code']." ".$cust_balance.' due to them.');
			$rep->SetFont('', '', 0);
		    $rep->NewLine();
			$rep->NewLine(2);
			$rep->Text($ccol+40, _('The balance shown in our account by the above referred company is not correct. We are sending statement from our books'));
			$rep->NewLine(2);
			$rep->Text($ccol+40, _('Yours faithfully'));
		    $rep->NewLine();
			$rep->Text($ccol+40, _('For '));
			$rep->SetFont('helvetica', 'B', 9);
			$rep->Text($ccol+60, $myrow['name']);
			$rep->SetFont('', '', 0);
			
			$rep->NewLine(2);
			$rep->Text($ccol+40, _('Signature:'));
			$rep->NewLine(2);
			$rep->Text($ccol+40, _('Date:'));
		    $rep->NewLine();
			
			$debtor = $myrow['name'];
		}
	 $rep->NewLine();
			
	}
		
	
	$rep->NewLine();
    $rep->End();
}

