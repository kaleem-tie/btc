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
$page_security = 'SA_GLREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	GL Accounts Transactions
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/admin/db/fiscalyears_db.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_GL_transactions();

//----------------------------------------------------------------------------------------------------

function get_all_bank_details($type, $trans_no)
{
	  $sql = "SELECT cheque_no, date_of_issue, dd_date_of_issue FROM ".TB_PREF."bank_trans WHERE 
	  type =".db_escape($type)." AND trans_no =".db_escape($trans_no)."";
			
	  $res = db_query($sql);
	  $result = db_fetch($res);
	  return $result;
		
}

//---------------------------------------------------------------------------------------------------------



function print_GL_transactions()
{
	global $path_to_root, $systypes_array;

	$dim = get_company_pref('use_dimension');
	$dimension = $dimension2 = 0;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$fromacc = $_POST['PARAM_2'];
	$toacc = $_POST['PARAM_3'];
	if ($dim == 2)
	{
		$dimension = $_POST['PARAM_4'];
		$dimension2 = $_POST['PARAM_5'];
		$comments = $_POST['PARAM_6'];
		$orientation = $_POST['PARAM_7'];
		$destination = $_POST['PARAM_8'];
	}
	elseif ($dim == 1)
	{
		$dimension = $_POST['PARAM_4'];
		$comments = $_POST['PARAM_5'];
		$orientation = $_POST['PARAM_6'];
		$destination = $_POST['PARAM_7'];
	}
	else
	{
		$comments = $_POST['PARAM_4'];
		$orientation = $_POST['PARAM_5'];
		$destination = $_POST['PARAM_6'];
	}
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	$orientation = ($orientation ? 'L' : 'L');

	$rep = new FrontReport(_('GL Account Transactions'), "GLAccountTransactions", user_pagesize(), 8, $orientation);
	$dec = user_price_dec();

	if ($dim == 2)
	{
		$cols = array(0, 50, 130, 180, 230, 280, 320, 380, 450, 530);
		//------------0--1---2----3----4----5----6----7----8----9----10-------
		//------------------------dim1-dim2-----------------------------------
		$headers2 = array(_('Code'),	_('Account Name'), _('Narration'),	_(''), _(''), _(''),
			 _(''),	_(''), _(''));
		
		$headers = array(_('Date'),	_('Doc No'), _('RefNo'),	_('RefDate'), _('Chq No'), _('Chq Date'),
			_('Debit (RO)'),	_('Credit (RO)'), _('Balance (RO)'));
	}
	elseif ($dim == 1)
	{
		$cols = array(0, 50, 130, 180, 230, 280, 320, 380, 450, 530);
		//------------0--1---2----3----4----5----6----7----8----9----10-------
		//------------------------dim1----------------------------------------
		
		$headers2 = array(_('Code'),	_('Account Name'), _('Narration'),	_(''), _(''), _(''),
			 _(''),	_(''), _(''));
		$headers = array(_('Date'),	_('Doc No'), _('RefNo'),	_('RefDate'), _('Chq No'), _('Chq Date'),
			_('Debit (RO)'),	_('Credit (RO)'), _('Balance (RO)'));
	}
	else
	{
		$cols = array(0, 50, 130, 180, 230, 280, 320, 380, 450, 530);
		//------------0--1---2----3----4----5----6----7----8----9----10-------
		//--------------------------------------------------------------------
		$headers2 = array(_('Code'),	_('Account Name'), _('Narration'),	_(''), _(''), _(''),
			 _(''),	_(''), _(''));
		$headers = array(_('Date'),	_('Doc No'), _('RefNo'),	_('RefDate'), _('Chq No'), _('Chq Date'),
			_('Debit (RO)'),	_('Credit (RO)'), _('Balance (RO)'));

	}
	$aligns = array('left', 'left', 'left',	'left',	'left',	'left',	'right', 'right', 'right');

	if ($dim == 2)
	{
    	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Accounts'),'from' => $fromacc,'to' => $toacc),
                    	3 => array('text' => _('Dimension')." 1", 'from' => get_dimension_string($dimension),
                            'to' => ''),
                    	4 => array('text' => _('Dimension')." 2", 'from' => get_dimension_string($dimension2),
                            'to' => ''));
    }
    elseif ($dim == 1)
    {
    	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Accounts'),'from' => $fromacc,'to' => $toacc),
                    	3 => array('text' => _('Dimension'), 'from' => get_dimension_string($dimension),
                            'to' => ''));
    }
    else
    {
    	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Accounts'),'from' => $fromacc,'to' => $toacc));
    }
    if ($orientation == 'L')
    	recalculate_cols($cols);
	$cols2 = $cols;

	$rep->Font();
	
	$rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
	//$rep->Info($params, $cols, $headers, $aligns);
	$rep->NewPage();

	$accounts = get_gl_accounts($fromacc, $toacc);
	$debit_total=0;
	$credit_total=0;

	while ($account=db_fetch($accounts))
	{
		//ravi
		$act_head_debit=0;
		$act_head_credit=0;
		if (is_account_balancesheet($account["account_code"]))
			$begin = "";
		else
		{
			$begin = get_fiscalyear_begin_for_date($from);
			if (date1_greater_date2($begin, $from))
				$begin = $from;
			$begin = add_days($begin, -1);
		}
		$prev_balance = get_gl_balance_from_to($begin, $from, $account["account_code"], $dimension, $dimension2);

		$trans = get_gl_transactions($from, $to, -1, $account['account_code'], $dimension, $dimension2);
		$rows = db_num_rows($trans);
		if ($prev_balance == 0.0 && $rows == 0)
			continue;
		$rep->SetFont('helvetica', 'B', 9);
		
		$name = $account['account_name'];
		$rep->TextCol(0, 4,	$account['account_code'] . " " . $account['account_name'], -2);
		$rep->TextCol(4, 6, _('Opening Balance'));
		if ($prev_balance > 0.0)
		{
			$act_head_debit+=abs($prev_balance);
			$rep->TextCol(6, 7, number_format2(abs($prev_balance), $dec)." Dr");
		//	$rep->AmountCol(7, 8, abs($prev_balance), $dec);
		}
		else
		{ 
	        $act_head_credit+=abs($prev_balance);
			$rep->TextCol(7, 8, number_format2(abs($prev_balance), $dec)." Cr");
			//$rep->AmountCol(8, 9, abs($prev_balance), $dec);
		}
		$rep->SetFont('', '', 0);
		$total = $prev_balance;
		$rep->NewLine(2);
		if ($rows > 0)
		{
			while ($myrow=db_fetch($trans))
			{
				$total += $myrow['amount'];
				
				if($myrow['type']==1){
	           $inv_type = "BP";
	           }elseif($myrow['type']==2){
	            $inv_type = "BD";
	           } elseif($myrow['type']==4){
	           $inv_type = "BT";
	           } elseif($myrow['type']==10){
	           $inv_type = "SI";
	          }elseif($myrow['type']==11){
	           $inv_type = "CN";
	           }elseif($myrow['type']==12){
	            $inv_type = "CP";
	          }elseif($myrow['type']==13){
	           $inv_type = "DN";
	            }elseif($myrow['type']==18){
	           $inv_type = "PO";
	            }elseif($myrow['type']==20){
	            $inv_type = "PI";
	             }elseif($myrow['type']==21){
	             $inv_type = "PC";
	            }elseif($myrow['type']==22){
	              $inv_type = "SP";
	             }elseif($myrow['type']==25){
	                 $inv_type = "GRN";
				}elseif($myrow['type']==16){
	               $inv_type = "IT";
	            }elseif($myrow['type']==17){
	              $inv_type = "IA";
	             }else{
	             $inv_type = $systypes_array[$myrow["type"]];
	             }
				 
				 $reference = get_reference($myrow["type"], $myrow["type_no"]);
				 $rep->DateCol(0, 1, $myrow["tran_date"], true);
				$rep->TextCol(1, 2, $inv_type." ". $reference, -2);
				
				$txt = payment_person_name($myrow["person_type_id"],$myrow["person_id"], false);
				
				//$memo = $myrow['memo_'];
				
				if ($myrow['memo_'] == ""){
					$memo = get_comments_string($myrow['type'], $myrow['type_no']);
				} else{
					$memo = $myrow['memo_'];
				}
				
				/*if ($txt != "")
				{
					if ($memo != "")
						$txt = $txt."/".$memo;
				}
				else
					$txt = $memo;*/
				$rep->TextCol(2, 6,	$memo, -2);
				
				
				if ($myrow['amount'] > 0.0)
				{
					$act_head_debit+=abs($myrow['amount']);
					$debit_sss = abs($myrow['amount']);
					$rep->TextCol(6, 7, number_format2($debit_sss, $dec)." Dr");
				//	$rep->AmountCol(6, 7, abs($myrow['amount']), $dec .' '." Dr");
				}
				else
			    {
					$act_head_credit+=abs($myrow['amount']);
					$credit_sss = abs($myrow['amount']);
					$rep->TextCol(7, 8, number_format2($credit_sss, $dec)." Cr");
					//$rep->AmountCol(7, 8, abs($myrow['amount']), $dec .' '." Cr");
				}
				if($total < 0.0){
				$rep->TextCol(8, 9, number_format2(abs($total), $dec)." Dr");
				}else{
					$rep->TextCol(8, 9, number_format2(abs($total), $dec)." Cr");
				}
				
				$bank = get_all_bank_details($myrow['type'], $myrow['type_no']);
				
				
				$rep->NewLine();
				
				if ($bank['date_of_issue']){
					$date_ch = $bank['date_of_issue'];
				} else{
					$date_ch = $bank['dd_date_of_issue'];
				}
				if($date_ch == '0000-00-00'){
					$date_ch = '';
				}
					
				$rep->TextCol(2, 3,	$bank['cheque_no'], -2); 
				$rep->TextCol(4, 5,	$bank['cheque_no'], -2); 
				$rep->DateCol(5, 6,$date_ch, true);
				$rep->NewLine();
				if ($rep->row < $rep->bottomMargin + $rep->lineHeight)
				{
					$rep->Line($rep->row - 2);
					$rep->NewPage();
				}
			}
			$rep->NewLine();
		}
		//$rep->Font('bold');
		$rep->SetFont('helvetica', 'B', 9);
		
		$debit_total+=abs($act_head_debit);
	    $credit_total+=abs($act_head_credit);
		
		
		//$rep->NewLine();
		$rep->Line($rep->row - $rep->lineHeight + 4);
		$rep->NewLine(2, 1);
		$rep->TextCol(1, 6,	_("**SUB Totals**".' '.$name));
		$rep->TextCol(6, 7, number_format2(abs($act_head_debit), $dec)." Dr");
		//$rep->AmountCol(6, 7, abs($act_head_debit).' '." Dr", $dec);
		$rep->TextCol(7, 8, number_format2(abs($act_head_credit), $dec)." Cr");
		//$rep->AmountCol(7, 8, abs($act_head_credit).' '." Cr", $dec);
		
		
		
		//$rep->TextCol(1, 6,	_("Ending Balance"));
		if ($total > 0.0)
			//$rep->AmountCol(8, 9, abs($total).' '." Cr", $dec);
		   $rep->TextCol(8, 9, number_format2(abs($total), $dec)." Cr");
		else
		   $rep->TextCol(8, 9, number_format2(abs($total), $dec)." Dr");	
	    	//$rep->AmountCol(8, 9, abs($total).' '." Cr", $dec);
		
		//$rep->NewLine();		
		
		//$rep->Font();
		$rep->Line($rep->row - $rep->lineHeight + 4);
		$rep->NewLine(2, 1);
	}
	//ravi
	  $rep->TextCol(1, 6,	_("Grand Total"));
		$rep->TextCol(6, 7, number_format2(abs($debit_total), $dec)." Dr");
		//$rep->AmountCol(6, 7, abs($debit_total).' '." Dr", $dec);
		$rep->TextCol(7, 8, number_format2(abs($credit_total), $dec)." Cr");
		//$rep->AmountCol(7, 8, abs($credit_total).' '." Cr", $dec);
		//$rep->NewLine();
				
		//$rep->TextCol(1, 6,	_("Ending Balance"));
		if ($debit_total >= $credit_total)
			 $rep->TextCol(8, 9, number_format2(abs($debit_total)-abs($credit_total), $dec)." Dr");
			//$rep->AmountCol(6, 7, abs($debit_total)-abs($credit_total), $dec);
		else
		 	$rep->TextCol(8, 9, number_format2(abs($credit_total)-abs($debit_total), $dec)." Cr");
		   //$rep->AmountCol(7, 8, abs($credit_total)-abs($debit_total), $dec);
	$rep->SetFont('', '', 0);
	//	$rep->Font();
		$rep->Line($rep->row - $rep->lineHeight + 4);
		$rep->NewLine(2, 1);
	
	$rep->End();
}

