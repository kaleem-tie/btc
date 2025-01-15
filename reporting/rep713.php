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

function get_cash_account($cash_account)
{
	 $sql = "SELECT * FROM ".TB_PREF."bank_accounts WHERE 
	  id =".db_escape($cash_account)."";
			
	  $res = db_query($sql);
	  $result = db_fetch($res);
	  return $result; 
}


function get_account_name($account)
{
	 $sql = "SELECT account_name FROM ".TB_PREF."chart_master WHERE 
	  account_code =".db_escape($account)."";
			
	  $res = db_query($sql);
	  $result = db_fetch_row($res);
	  return $result[0]; 
}

function get_petty_cash_trans_no($from, $to,$cash_account)
{
	$from = date2sql($from);
	$to=date2sql($to);
	
	$sql = "SELECT distinct ref,trans_date FROM ".TB_PREF."gl_pettycash_trans WHERE 
	  bank_act=".db_escape($cash_account)." and trans_date between '$from' and '$to'";
			
	  $res = db_query($sql);
	  
	  return $res;
	
}

function get_petty_cash_account_details($ref)
{
	 $sql = "SELECT * FROM ".TB_PREF."gl_pettycash_trans WHERE ref=".db_escape($ref)."";
			
	  $res = db_query($sql);
	  
	  return $res;
}

//---------------------------------------------------------------------------------------------------------



function print_GL_transactions()
{
	global $path_to_root, $systypes_array;

	$dim = get_company_pref('use_dimension');
	$dimension = $dimension2 = 0;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$cash_account = $_POST['PARAM_2'];
	if ($dim == 1)
	{
		$dimension = $_POST['PARAM_3'];
		$comments = $_POST['PARAM_4'];
		$orientation = $_POST['PARAM_5'];
		$destination = $_POST['PARAM_6'];
	}
	else
	{
		$comments = $_POST['PARAM_3'];
		$orientation = $_POST['PARAM_4'];
		$destination = $_POST['PARAM_5'];
	}
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	$orientation = ($orientation ? 'L' : 'L');

	$rep = new FrontReport(_('Petty Cash Payment'), "PettyCashPayment", user_pagesize(), 8, $orientation);
	$dec = user_price_dec();

	if ($dim == 1)
	{
		$cols = array(0, 80, 230, 320, 380, 450, 515);
		//------------0--1---2----3----4----5----6----7----8----9----10-------
		//------------------------dim1----------------------------------------
		
		$headers2 = array(_('Doc No'),	_('Doc Date'), _('Code'),	_('Cash Account'), _(''), _(''),
			 _(''),	_(''), _(''));
		$headers = array(_('Code'),	_('Account Name'), _('Narration'),	_('RefNo'), _('Ref Date'), _('Amount'));
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
	$aligns = array('left', 'left', 'left',	'left',	'left',	'right');

	if ($dim == 1)
    {
		$bank_details= get_cash_account($cash_account);
		
    	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Accounts'),'from' => $fromacc,'to' => $toacc),
                    	3 => array('text' => _('Cash Account'), 'from' => $bank_details['bank_account_name'],
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

	$petty_cash_trans_nos = get_petty_cash_trans_no($from, $to,$cash_account);

   $total=0;
   $grand_total=0;

	while ($petty_cash_trans_no=db_fetch($petty_cash_trans_nos))
	{
		$total=0;
			$rep->NewLine();
			
			$rep->TextCol(0, 1,	$petty_cash_trans_no['ref']);
			$rep->DateCol(1, 2,	$petty_cash_trans_no['trans_date'], true);
			$rep->TextCol(2, 3,$bank_details['account_code']);
			$rep->TextCol(3, 4,	$bank_details['bank_account_name']);
			
			$petty_cash_account_details = get_petty_cash_account_details($petty_cash_trans_no['ref']);
			
			while($petty_cash_account_detail = db_fetch($petty_cash_account_details))
			{
			$rep->NewLine();
			
			$rep->TextCol(0, 1,	$petty_cash_account_detail['account']);
			$rep->TextCol(1, 2,	get_account_name($petty_cash_account_detail['account']));
			$rep->TextCol(2, 3,	$petty_cash_account_detail['memo_']);
			$rep->TextCol(3, 4,	$petty_cash_trans_no['ref']);
			$rep->AmountCol(5, 6, abs($petty_cash_account_detail['amount']), 3);
			$total+=abs($petty_cash_account_detail['amount']);
			$grand_total+=abs($petty_cash_account_detail['amount']);
			}
			$rep->NewLine();
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->NewLine(1, 2);
	$rep->TextCol(1, 3, _(' Total : '));
	$rep->AmountCol(5, 6, $total, 3);
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	}

	$rep->NewLine(1, 2);
	$rep->TextCol(1, 3, _(' Grand Total : '));
	$rep->AmountCol(5, 6, $grand_total, 3);
	
	$rep->End();
}

