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
$page_security = 'SA_PDC_REG_REP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Bank Accounts Transactions
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_pdc_recall_alert_transactions();

//----------------------------------------------------------------------------------------------------


function get_pdc_transactions($to, $account, $fromcust=0)
{
	$to = date2sql($to);
	
	$sql = "SELECT trans.trans_no,trans.reference,trans.tran_date,cust.cust_code,
	cust.name,trans.pdc_cheque_no,trans.pdc_cheque_date,trans.ov_amount
	FROM ".TB_PREF."debtor_trans as trans,".TB_PREF."debtors_master as cust 
	WHERE trans.debtor_no = cust.debtor_no
	AND trans.type = 5
	AND trans.ov_amount!=0 AND current_pdc_status=0
	AND trans.tran_date <= '$to'";
	
	if ($fromcust != ''){
	       $sql .= " AND trans.debtor_no=".db_escape($fromcust);	
	}
	
	if ($account != ''){
	       $sql .= " AND trans.bank_account=".db_escape($account);	
	}
	
	$sql .= " ORDER BY trans.trans_no,trans.pdc_cheque_date";	

	return db_query($sql,"The transactions for '$account' could not be retrieved");
}



//--------------------------------------------------------------------//

function print_pdc_recall_alert_transactions()
{
	global $path_to_root, $systypes_array;

	$to            = $_POST['PARAM_0'];
	$bank_acc      = $_POST['PARAM_1'];
	$fromcust      = $_POST['PARAM_2'];
	$comments      = $_POST['PARAM_3'];
	$orientation   = $_POST['PARAM_4'];
	$destination   = $_POST['PARAM_5'];
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	
	
	if ($fromcust == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
	
	
	
	$headers = array(_("#"), _("Doc No."), _("Doc Date"), _("Cust Code"),_("Customer Name"),
	_("Chq No."),_("Chq Date"),_("Amount"));

	$cols = array(4,25,75,125,180,380,450,500,550);

	$aligns = array('left',	'left',	'left',	'left',	'left',	'left', 'left', 'right');

	

	$params =   array( 0 => $comments,
    				  1 => array('text' => _('End Date'),'from' => $to, 'to' => ''),
    				  2 => array('text' => _('Customer'), 'from' => $cust, 'to' => ''));

    $rep = new FrontReport(_('PDC Recall Alerts'), 
	"PDCRecallAlerts", user_pagesize(), 8, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();
	
	$trans = get_pdc_transactions($to, $bank_acc, $fromcust);
		$total_pdc_amount = 0;
		while ($myrow=db_fetch($trans))
		{
					
					
			$rep->TextCol(0, 1,	$myrow['trans_no']);
            $rep->TextCol(1, 2,	$myrow['reference']);
			$rep->TextCol(2, 3,	sql2date($myrow['tran_date']));
			$rep->TextCol(3, 4,	$myrow['cust_code']);
			$rep->TextCol(4, 5,	$myrow['name']);
			$rep->TextCol(5, 6,	$myrow['pdc_cheque_no']);
			$rep->TextCol(6, 7,	sql2date($myrow['pdc_cheque_date']));
			$rep->AmountCol(7, 8, $myrow['ov_amount'],user_price_dec());

			$total_pdc_amount += $myrow['ov_amount'];
			$rep->NewLine();
					
					
		}
		
		
		$rep->Line($rep->row  - 2);
	    $rep->NewLine();
        if ($destination==0)		
		  $rep->SetFont('helvetica', 'B', 9);
		$rep->TextCol(6, 7, _("Total"));
		$rep->AmountCol(7, 8, $total_pdc_amount, user_price_dec());
		if ($destination==0)
		   $rep->SetFont('', '', 0);
		$rep->NewLine(2);

		
	
	$rep->Line($rep->row  - 2);
	$rep->NewLine();
    $rep->End();
}

