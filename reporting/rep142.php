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

print_pdc_transactions();

//----------------------------------------------------------------------------------------------------


function get_pdc_transactions($from, $to, $account, $cheque_status=0)
{
	$from = date2sql($from);
	$to = date2sql($to);
	
	$sql = "SELECT trans.*,cust.cust_code,cust.name,com.memo_ FROM ".TB_PREF."debtor_trans trans
	LEFT JOIN ".TB_PREF."voided voided ON trans.type=voided.type AND trans.trans_no=voided.id
	LEFT JOIN ".TB_PREF."comments com ON trans.type=com.type AND trans.trans_no=com.id,
	".TB_PREF."debtors_master cust
		WHERE trans.debtor_no = cust.debtor_no
		AND trans.bank_account = '$account'
		AND trans.tran_date >= '$from'
 		AND trans.tran_date <= '$to'
		AND trans.type = ".ST_CUSTPDC."
		AND ISNULL(voided.id)";
		
	if ($cheque_status == 1)
	{
		$sql .= " AND trans.current_pdc_status = 1";
	}
    else if ($cheque_status == 2)	
	{
		$sql .= " AND trans.current_pdc_status = 2";
	}
		
	$sql .= " GROUP BY trans.trans_no,trans.debtor_no ORDER BY trans.trans_no";	

	return db_query($sql,"The transactions for '$account' could not be retrieved");
}


function get_pdc_customer_payment_ref_info($recall_remarks)
{
    $sql="SELECT reference,bank_account,type,ov_amount FROM ".TB_PREF."debtor_trans 
	WHERE type=12
	AND trans_no=".db_escape($recall_remarks);
    $result = db_query($sql, "could not get payment");
	return db_fetch($result);
}


//--------------------------------------------------------------------//

function print_pdc_transactions()
{
	global $path_to_root, $systypes_array;

	$acc           = $_POST['PARAM_0'];
	$from          = $_POST['PARAM_1'];
	$to            = $_POST['PARAM_2'];
	$cheque_status = $_POST['PARAM_3'];
	$comments      = $_POST['PARAM_4'];
	$orientation   = $_POST['PARAM_5'];
	$destination   = $_POST['PARAM_6'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	
	
	
	if ($cheque_status == 0)
        $chq_status = _('All Cheques');
    else if($cheque_status == 1)
         $chq_status = _('Cleared Cheques Only');
	else if($cheque_status == 2)
         $chq_status = _('Bounced Cheques');
	
	
	$rep = new FrontReport(_('PDC Register'), "PDCRegister", user_pagesize(), 8, $orientation);
	$dec = user_price_dec();
	
	$headers = array(_('Date'),	_('Doc. No.'),	_('A/C Code'), _('Description'), _('Narration'),
		_('Chq No.'),	_('Chq Date'), _('Receipts'), _('FA Doc. No.'));

	$cols = array(0,40,90,125,240,370,410,450,490,560);

	$aligns = array('left',	'left',	'left',	'left',	'left',	'left', 'left', 'right', 'center');

	

	if ($orientation == 'L')
		recalculate_cols($cols);
	
	
	$sql = "SELECT id, bank_account_name, bank_curr_code, bank_account_number 
	FROM ".TB_PREF."bank_accounts";
	if ($acc != ALL_TEXT)
		$sql .= " WHERE id = $acc";
	$result = db_query($sql, "could not retreive bank accounts");
	
	while ($account=db_fetch($result))
	{
		$act = $account['bank_account_name']." - ".$account['bank_curr_code']." - ".$account['bank_account_number'];
		
		$params =   array( 	0 => $comments,
			1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
			2 => array('text' => _('Bank Account'),'from' => $act,'to' => ''),
			3 => array('text' => _('Displays'),'from' => $chq_status,'to' => ''));

		$rep->Font();
		$rep->pageNumber = 0;
		$rep->Info($params, $cols, $headers, $aligns);
		
		
		$trans = get_pdc_transactions($from, $to, $account['id'], $cheque_status);
		$rows = db_num_rows($trans);
		
		if ($rows != 0)
		$rep->NewPage();

		
		if ($rows != 0)
		{
			//$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(0, 5,	$act);
			//$rep->SetFont('', '', 0);
			
			
			$rep->NewLine(2);
			$total_pdc_amount = 0;
			if ($rows > 0)
			{
				
				
				while ($myrow=db_fetch($trans))
				{
					
					
					
					
					$rep->DateCol(0, 1,	$myrow["tran_date"], true);
                    $rep->TextCol(1, 2,	"PE ".$myrow['reference']);
					$rep->TextCol(2, 3,	$myrow['cust_code']);
					$rep->TextCol(3, 4,	$myrow['name']);
					$rep->TextCol(4, 5,	$myrow['memo_']);
					$rep->TextCol(5, 6,	$myrow['pdc_cheque_no']);
					$rep->DateCol(6, 7,	$myrow["pdc_cheque_date"], true);
					
					if($myrow['current_pdc_status']==0){
					$rep->AmountCol(7, 8, $myrow["ov_amount"], $dec);
					$total_pdc_amount += $myrow['ov_amount'];
					}
					else if($myrow['current_pdc_status']==1){
						
					$pdc_payments = get_pdc_customer_payment_ref_info($myrow['recall_remarks']);	
						
					$rep->AmountCol(7, 8, $pdc_payments["ov_amount"], $dec);
					$total_pdc_amount += $pdc_payments['ov_amount'];
					}
					if($myrow['current_pdc_status']==1){
						
					$pdc_payments = get_pdc_customer_payment_ref_info($myrow['recall_remarks']);
					
					  if($pdc_payments['type']==12){
				        if($pdc_payments['bank_account']==1)
				        $payment_type = "CR";
			            else
				        $payment_type = "BR";	
			          }
					$rep->TextCol(8, 9,	$payment_type." ".$pdc_payments['reference']);
					}
					
					
					$rep->NewLine();
					
					if ($rep->row < $rep->bottomMargin + $rep->lineHeight)
					{
						$rep->Line($rep->row - 2);
						$rep->NewPage();
					}
					
				}
				$rep->NewLine();
			}
			
			//$rep->SetFont('helvetica', 'B', 9);
			$rep->TextCol(6, 7, _("Total"));
			$rep->AmountCol(7, 8, $total_pdc_amount, $dec);
			//$rep->SetFont('', '', 0);
			$rep->NewLine(2);

			$rep->Line($rep->row - $rep->lineHeight + 4);
			$rep->NewLine(2, 1);
			
			
			
		}
	}
	$rep->End();
}

