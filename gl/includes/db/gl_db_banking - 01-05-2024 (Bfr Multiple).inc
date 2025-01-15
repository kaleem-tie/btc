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
function add_exchange_variation($trans_no, $date_, $acc_id, $account,
    $currency, $person_type_id=null, $person_id = "", $memo='')
{
	if (is_company_currency($currency))
		return false;
	if ($date_ == null)
		$date_ = Today();
	$for_amount = 0;

	// We have to calculate all the currency accounts belonging to the GL account
	// upto $date_ and calculate with the exchange rates. And then compare with the GL account balance.
	$sql = "SELECT SUM(bt.amount) AS for_amount, ba.bank_curr_code 		
		FROM ".TB_PREF."bank_trans bt, ".TB_PREF."bank_accounts ba
		WHERE ba.id = bt.bank_act AND ba.account_code = ".db_escape($account)." AND bt.trans_date<='".date2sql($date_)."'
		GROUP BY ba.bank_curr_code";	
	$result = db_query($sql, "Transactions for bank account $acc_id could not be calculated");
	while ($row = db_fetch($result))
	{
		if ($row['for_amount'] == 0)
			continue;
		$rate = get_exchange_rate_from_home_currency($row['bank_curr_code'], $date_);
		$for_amount += round2($row['for_amount'] * $rate, user_price_dec());
	}	
	$amount = get_gl_trans_from_to("", $date_, $account);
	$diff = $amount - $for_amount;
	if (floatcmp($diff,0))
	{
		// generate journal entry // FIXME: use cart?
		global $Refs;

		begin_transaction();
		$trans_no = get_next_trans_no(ST_JOURNAL);
		$ref = $Refs->get_next(ST_JOURNAL, null, $date_);

		add_journal(ST_JOURNAL, $trans_no, $diff, $date_ , get_company_currency(), $ref, '', 1, $date_, $date_);
		$Refs->save(ST_JOURNAL, $trans_no, $ref);
		add_audit_trail(ST_JOURNAL, $trans_no, $date_);

		if ($person_type_id == null)
			$person_type_id = PT_MISC;
		add_gl_trans(ST_JOURNAL, $trans_no, $date_, $account, 0, 0, _("Exchange Variance"),
    		-$diff, null, $person_type_id, $person_id);

    	add_gl_trans(ST_JOURNAL, $trans_no, $date_, get_company_pref('exchange_diff_act'), 0, 0, 
    		_("Exchange Variance"), $diff, null, $person_type_id, $person_id);

		if ($memo)
			add_comments(ST_JOURNAL, $trans_no, $date_, $memo);

		$Refs->save(ST_JOURNAL, $trans_no, $ref);
		add_audit_trail(ST_JOURNAL, $trans_no, $date_);

		commit_transaction();
		return true;
	}
	return false;
}

function add_cust_supp_revaluation($ledger_act, $ex_act, $date, $amount, $person_type_id, $person_id, $memo)
{
	global $Refs;
	if ($amount == 0)
		return;

	begin_transaction();
	$trans_no = get_next_trans_no(ST_JOURNAL);
	$ref = $Refs->get_next(ST_JOURNAL);

	add_journal(ST_JOURNAL, $trans_no, $amount, $date, get_company_currency(), $ref, '', 1, $date, $date);
	$Refs->save(ST_JOURNAL, $trans_no, $ref);
	add_audit_trail(ST_JOURNAL, $trans_no, $date);

	add_gl_trans(ST_JOURNAL, $trans_no, $date, $ledger_act, 0, 0, _("Exchange Variance"),
		$amount, null, $person_type_id, $person_id);
	add_gl_trans(ST_JOURNAL, $trans_no, $date, $ex_act, 0, 0, 
		_("Exchange Variance"),	-$amount, null, $person_type_id, $person_id);
	add_comments(ST_JOURNAL, $trans_no, $date, $memo);
	commit_transaction();
}

function get_cust_account_curr_balances($date)
{
	$to = date2sql($date);

    $sql = "SELECT SUM(IF(t.type IN(". implode(',', array(ST_CUSTCREDIT, ST_CUSTPAYMENT, ST_BANKDEPOSIT, ST_JOURNAL))."),
			-(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount), 
	    	(t.ov_amount + t.ov_gst + t.ov_freight + t.ov_freight_tax + t.ov_discount))) AS amount,
	    	dt.debtor_no, dt.name, dt.curr_code, b.receivables_account
		FROM ".TB_PREF."debtor_trans t 
			LEFT JOIN ".TB_PREF."debtors_master dt ON t.debtor_no = dt.debtor_no
			LEFT JOIN ".TB_PREF."cust_branch b ON t.debtor_no = b.debtor_no
			LEFT JOIN ".TB_PREF."voided as v ON v.type = t.type and v.id=t.trans_no
    	WHERE ISNULL(v.date_) AND t.type <> ".ST_CUSTDELIVERY." AND t.tran_date <= '$to' 
    		AND t.branch_code=b.branch_code AND dt.curr_code<>'".get_company_pref('curr_default')."' 
    		GROUP BY t.debtor_no, b.receivables_account";

    $result = db_query($sql,"Open balances in foreign currency for cannot be retrieved");
	return  $result;
}

function get_supp_account_curr_balances($date)
{
	$to = date2sql($date);

    $sql = "SELECT SUM(-(t.ov_amount + t.ov_gst + t.ov_discount)) AS amount,
	    	supp.supplier_id, supp.supp_name, supp.curr_code, supp.payable_account
		FROM ".TB_PREF."supp_trans t 
			LEFT JOIN ".TB_PREF."suppliers supp ON t.supplier_id = supp.supplier_id
			LEFT JOIN ".TB_PREF."voided as v ON v.type = t.type and v.id=t.trans_no
    	WHERE ISNULL(v.date_) AND t.tran_date <= '$to' 
    		AND supp.curr_code<>'".get_company_pref('curr_default')."' 
    		GROUP BY t.supplier_id";

    $result = db_query($sql,"Open balances in foreign currency for cannot be retrieved");
	return  $result;
}

function get_account_home_balance($person_id, $person_type, $rec_account, $to_date)
{
	$to = date2sql($to_date);

    $sql = "SELECT SUM(amount) FROM ".TB_PREF."gl_trans  
			WHERE person_id=".db_escape($person_id)." AND account='$rec_account' 
			AND person_type_id=$person_type AND tran_date <= '$to'";
	$result = db_query($sql, "The AR/AP balance for customer $person_id could not be calculated");
	$row = db_fetch_row($result);
	return $row[0];
}

//
// Calculate and post currency accounts revaluation including foreign AP/AR
// Note: in 2.4.9 sparse 'ref' argument was removed (generated transactions use
// next references from default refline).
//
function add_exchange_variation_all($date, $memo)
{
	begin_transaction();
	$exchanged = false;
	$trans_no = get_next_trans_no(ST_JOURNAL);
	$sql = "SELECT * FROM ".TB_PREF."bank_accounts";
	$result = db_query($sql, "could not retreive bank accounts");
	$b_num = 0;
	while ($myrow = db_fetch($result))
	{
		if (add_exchange_variation($trans_no, $date, $myrow['id'], $myrow['account_code'],
			$myrow['bank_curr_code'], null, '', $memo))
		$b_num++;
	}

	// revaluation of customer/supplier currencies

	if ($date == null)
		$date = Today();
	$exchange_act = get_company_pref('exchange_diff_act');
	$je = 0;
	// debtors
	$res = get_cust_account_curr_balances($date);
	
	while($row = db_fetch($res)) 
	{
		$exrate = get_exchange_rate_from_home_currency($row['curr_code'], $date);
		$foreign = round2($row['amount']*$exrate, user_price_dec());
		$home = round2(get_account_home_balance($row['debtor_no'], PT_CUSTOMER, $row['receivables_account'], $date), user_price_dec());
		if ($foreign != $home) 
		{
			$amount = $foreign - $home;	
			add_cust_supp_revaluation($row['receivables_account'], $exchange_act, $date, $amount, PT_CUSTOMER, 
				$row['debtor_no'], $memo);
			$je++;	
		}
	}
	// creditors
	$res = get_supp_account_curr_balances($date);
	
	while($row = db_fetch($res)) 
	{
		$exrate = get_exchange_rate_from_home_currency($row['curr_code'], $date);
		$foreign = round2($row['amount']*$exrate, user_price_dec());
		$home = round2(get_account_home_balance($row['supplier_id'], PT_SUPPLIER, $row['payable_account'], $date), user_price_dec());
		if ($foreign != $home) 
		{
			$amount = $foreign - $home;	
			add_cust_supp_revaluation($row['payable_account'], $exchange_act, $date, $amount, PT_SUPPLIER, 
				$row['supplier_id'], $memo);
			$je++;	
		}
	}

	commit_transaction();
	return array($b_num, $je);
}
//----------------------------------------------------------------------------------
//	Add bank tranfer to database.
//
//	$from_account - source bank account id
//	$to_account   -	target bank account id
//	

function add_bank_transfer($from_account, $to_account, $date_,
	$amount, $ref, $memo_, $dim1, $dim2, $charge=0, $target_amount=0,$our_ref_no='',$ex_rate=0,$vat_charge=0,$vat_act=0,$bank_charge_dimension_id=0,$interest_charge=0,$interest_act='',$bank_interest_dimension_id=0,$vat_charge_dimension_id=0)
{
	global $Refs, $SysPrefs;

	begin_transaction();
	$args = func_get_args(); if (count($args) < 8) $args[] = 0;
	$args = (object)array_combine(array('from_account', 'to_account', 'date_', 'amount',  
		'ref', 'memo_', 'dimension_id', 'dimension2_id', 'charge', 'target_amount','our_ref_no','ex_rate','vat_charge','vat_act','bank_charge_dimension_id','interest_charge','interest_act','bank_interest_dimension_id','vat_charge_dimension_id'), $args);
	$args->trans_no = 0;
	hook_db_prewrite($args, ST_BANKTRANSFER);

	$trans_type = ST_BANKTRANSFER;

	$currency = get_bank_account_currency($from_account);

	$trans_no = get_next_trans_no($trans_type);
	
    $fromact = get_bank_account($from_account);
    $toact = get_bank_account($to_account);
    $person_id = _("From")." ".$fromact['bank_account_name']." "._("To")." ".$toact['bank_account_name'];

	$from_gl_account = get_bank_gl_account($from_account);
	$to_gl_account = get_bank_gl_account($to_account);
	
	$target_ex_rate=$target_amount/$amount;
	$target_currency=get_bank_account_currency($to_account);
	$target_value=$target_ex_rate*($amount+$charge+$vat_charge+$interest_charge);
	
	$total = 0;
	// do the source account postings
	
	if(get_company_currency()==$currency)
	{
	  $ex_rate=1;
	}
	
    $total += add_gl_trans($trans_type, $trans_no, $date_, $from_gl_account, $dim1, $dim2, $person_id,
		-($amount + $charge+$vat_charge+$interest_charge), $currency, null, null,"",$ex_rate);

    add_bank_trans($trans_type, $trans_no, $from_account, $ref,
		$date_, -($amount + $charge+$vat_charge+$interest_charge),
		PT_MISC, $person_id, $currency,
		"Cannot insert a source bank transaction",$target_ex_rate,
		'',0,'','','','','',0,$target_currency,-$target_value,$our_ref_no);
		
	/*	
	if ($SysPrefs->auto_currency_revaluation())
	{
		$trans_no1 = get_next_trans_no(ST_JOURNAL);
		add_exchange_variation($trans_no1, $date_, $from_account, $from_gl_account, 
			$currency, PT_MISC, $person_id);
	}
	*/
	
	if ($charge != 0)
	{
		/* Now Debit bank charge account with charges */
		$charge_act = get_bank_charge_account($from_account);
		$total += add_gl_trans($trans_type, $trans_no, $date_,
			$charge_act, $dim1, $dim2, $person_id, $charge, $currency, null, null,"",$ex_rate);
	}
	
	if ($vat_charge != 0)
	{
		/* Now Debit bank charge account with charges */
		$total += add_gl_trans($trans_type, $trans_no, $date_,
			$vat_act, $vat_charge_dimension_id, 0, $person_id, $vat_charge, $currency, null, null,"",$ex_rate);
	}
	
	if ($interest_charge != 0)
	{
		/* Now Debit bank charge account with charges */
		$total += add_gl_trans($trans_type, $trans_no, $date_,
			$interest_act, $bank_interest_dimension_id, 0, $person_id, $interest_charge, $currency, null, null,"",$ex_rate);
	}

	// provide backward compatibility for extension modules (target amount can be not passed)
	$to_currency = $target_amount ? $toact['bank_curr_code'] : $currency;
	$to_amount = $target_amount ? $target_amount : $amount;
	
	
	
	if(get_company_currency()==$to_currency)
	{
	  $ex_rate=1;
	  
	  $target_ex_rate = 1;
	}	
	else
	{
	    if($target_amount)
		$ex_rate=$amount/$target_amount;
		else
		$ex_rate=$ex_rate;
		
		$target_ex_rate=$amount/$target_amount;
	}
	
	
	$target_currency=get_bank_account_currency($from_account);
	$target_value=$amount;	

	// do the destination account postings
	$total += add_gl_trans($trans_type, $trans_no, $date_, $to_gl_account, $dim1, $dim2, $person_id,$to_amount, $to_currency, null, null,"",$ex_rate);
		
	/*Post a balance post if $total != 0 */
	if ($currency == $to_currency)
		add_gl_balance($trans_type, $trans_no, $date_, -$total);
	else	// in this case those are exchange variances between bank and home rates
	   	add_gl_trans($trans_type, $trans_no, $date_, get_company_pref('exchange_diff_act'),
	   		$dim1, $dim2, _("Exchange Variance"), -$total);
			
	
	
	add_bank_trans($trans_type, $trans_no, $to_account, $ref,
		$date_, $to_amount, PT_MISC, $person_id,
		$to_currency, "Cannot insert a destination bank transaction",$target_ex_rate,
		'',0,'','','','','',0,$target_currency,$target_value,$our_ref_no);
    /*
	if ($SysPrefs->auto_currency_revaluation())
	{
		$currency = get_bank_account_currency($to_account);
		add_exchange_variation($trans_no1, $date_, $to_account, $to_gl_account,	
			$currency, PT_MISC, $person_id);
	}
	*/
	
	add_comments($trans_type, $trans_no, $date_, $memo_);

	$Refs->save($trans_type, $trans_no, $ref);
	add_audit_trail($trans_type, $trans_no, $date_);

	$args->trans_no = $trans_no;
	hook_db_postwrite($args, ST_BANKTRANSFER);
	commit_transaction();

	return $trans_no;
}

function check_bank_transfer($trans_no, $from_account, $to_account, $date_,
    $amount, $target_amount=0)
{
	$dbResult = get_bank_trans(ST_BANKTRANSFER, $trans_no);

	$old_from = db_fetch($dbResult);
	$old_to = db_fetch($dbResult);
	if ($old_to['amount'] < 0.0) {
		$tmp = $old_from;
		$old_from = $old_to;
		$old_to = $tmp;
	}
	// There are four accounts to consider:
	// 1) The original from account that is being voided. This results in funds being put back which is always fine.
	// 2) The original to account that is being voided. This results in funds being removed which may result in a
	//    negative balance in the account at some time and therefore needs to be checked.
	$problemTransaction = check_bank_account_history(-$old_to['amount'], $old_to['bank_act'], sql2date($old_from['trans_date']));
	if ($problemTransaction) {
		// If the destination account is the same as that being edited, it may be that this edit will resolve the
		// problem of voiding.
		if ($to_account == $old_to['bank_act'] && sql_date_comp($problemTransaction['trans_date'], date2sql($date_)) > 0) {
			$problemTransaction['amount'] += $amount;
			if ($problemTransaction['amount'] >= 0) {
				$problemTransaction = null;
			}
		}
		if ($problemTransaction != null) {
			$problemTransaction['account'] = $old_to['bank_act'];
			$problemTransaction['bank_account_name'] = $old_to['bank_account_name'];
			return $problemTransaction;
		}
	}

	// 3) The edited from account, that is having funds removed which may result in a
	//    negative balance in the account at some time and therefore needs to be checked.
	$balance_offset = 0;
	if ($from_account == $old_from['bank_act'] && sql_date_comp(date2sql($date_), $old_from['trans_date']) >= 0) {
		// If the edited from_account is the same as the original transaction, and the new date is later than the old date
		// then add the amount from the original from transaction when checking the history to simulate the void that would
		// take effect from that date.
		$balance_offset = -$old_from['amount'];
	}
	$problemTransaction = check_bank_account_history(-$amount, $from_account, $date_, null, $balance_offset);
	if ($problemTransaction != null) {
		$problemTransaction['account'] = $old_from['bank_act'];
		$problemTransaction['bank_account_name'] = $old_from['bank_account_name'];
		return $problemTransaction;
	}
	// 4) The edited to account, that is having funds added which is always ok.

	return $problemTransaction;
}

function update_bank_transfer(
	$trans_no, $from_account, $to_account, $date_,
	$amount, $ref, $memo_, $dim1, $dim2, $charge=0, $target_amount=0,$our_ref_no='',$ex_rate=0,$vat_charge=0,$vat_act=0,$bank_charge_dimension_id=0,$interest_charge=0,$interest_act='',$bank_interest_dimension_id=0,$vat_charge_dimension_id=0)
{
	begin_transaction();
	delete_comments(ST_BANKTRANSFER, $trans_no);
	void_transaction(ST_BANKTRANSFER, $trans_no, $date_, _("Document reentered."));
	void_gl_trans(ST_BANKTRANSFER, $trans_no, true);
	$new_trans_no = add_bank_transfer(
		$from_account, $to_account, $date_, $amount,
		$ref, $memo_, $dim1, $dim2, $charge, $target_amount,$our_ref_no,$ex_rate,$vat_charge,$vat_act,$bank_charge_dimension_id,$interest_charge,$interest_act,$bank_interest_dimension_id,$vat_charge_dimension_id
	);
	commit_transaction();
	return $new_trans_no;
}

//----------------------------------------------------------------------------------
//	Add bank payment or deposit to database.
//
//	$from_account - bank account id
//  $items - transaction cart (line amounts in bank account's currency); negative for deposit
//  $person_type_id - defines type of $person_id identifiers
//  $person_id	- supplier/customer/other id
//  $person_detail_id - customer branch id or not used
//  $settled_amount - settled amount in AR/AP (if applicable) in customer/supplier currency (always non-negative number)
//
// returns an array of (inserted trans type, trans no)
//
// FIXME -revise code for update case
//
function write_bank_transaction($trans_type, $trans_no, $from_account, $items, $date_,
	$person_type_id, $person_id, $person_detail_id,	$ref, $memo_, $use_transaction=true, 
	$settled_amount=null,$mode_of_payment='',$cheque_no='',$dd_no='',$date_of_issue='',
	$dd_date_of_issue='',$pymt_ref='',$is_purch_cash_bill=0,$our_ref_no='',$ex_rate=1,
	$amex=0)
{
	global $Refs, $SysPrefs;

	// we can only handle type 1 (payment)and type 2 (deposit)
	if ($trans_type != ST_BANKPAYMENT && $trans_type != ST_BANKDEPOSIT)
		display_db_error("Invalid type ($trans_type) sent to add_bank_transaction");

	$do_exchange_variance = false;
	if ($use_transaction)
		begin_transaction();

	$args = func_get_args(); if (count($args) < 11) $args[] = true;
	$args = (object)array_combine(array('trans_type', 'trans_no', 'from_account', 'items', 'date_',
		'person_type_id', 'person_id', 'person_detail_id', 'ref', 'memo_', 'use_transaction', 'settled_amount','mode_of_payment','cheque_no','dd_no','date_of_issue','dd_date_of_issue','pymt_ref','is_purch_cash_bill','our_ref_no','ex_rate','amex'),
		$args);
	hook_db_prewrite($args, $trans_type);

	$aid = 0;
	if ($trans_no) {
		$old_trans = $trans_no;
		$Refs->restore_last($trans_type, $trans_no);
		$aid = has_attachment($trans_type, $trans_no);
	} else
		$old_trans = false;

	$currency = get_bank_account_currency($from_account);
	$bank_gl_account = get_bank_gl_account($from_account);

	// the gl items are already inversed/negated for type 2 (deposit)
	$total_amount = $items->gl_items_total();

    if ($person_type_id == PT_CUSTOMER)
    {
    	// we need to add a customer transaction record
		// convert to customer currency
		if (!isset($settled_amount)) // leaved for backward/ext compatibility 
			$cust_amount = exchange_from_to(abs($total_amount), $currency, get_customer_currency($person_id), $date_);
		else
			$cust_amount = $settled_amount;

		if ($trans_type == ST_BANKPAYMENT)
			$cust_amount = -$cust_amount;
			
		$target_ex_rate=$total_amount/(-$cust_amount);
		$target_currency=get_customer_currency($person_id);
		$target_value=$cust_amount;		

		$trans_no = write_customer_trans($trans_type, 0, $person_id, $person_detail_id, $date_,
        	$ref, $cust_amount);
		if ($old_trans)
			move_trans_attachments($trans_type, $old_trans, $trans_no);
    }
    elseif ($person_type_id == PT_SUPPLIER)
    {
    	// we need to add a supplier transaction record
		// convert to supp currency
		if (!isset($settled_amount)) // leaved for for backward/ext compatibility 
			$supp_amount = exchange_from_to(abs($total_amount), $currency, get_supplier_currency($person_id), $date_);
		else
			$supp_amount = $settled_amount;

		if ($trans_type == ST_BANKPAYMENT)
			$supp_amount = -$supp_amount;
			
		$target_ex_rate=$total_amount/(-$supp_amount);
		$target_currency=get_supplier_currency($person_id);
		$target_value=$supp_amount;			

		$trans_no = write_supp_trans($trans_type, 0, $person_id, $date_, '',
			$ref, "", $supp_amount, 0, 0);
		if ($old_trans)
			move_trans_attachments($trans_type, $old_trans, $trans_no);
    }
    else
    {
	    $target_ex_rate=1;
		$target_currency=$currency;
		$target_value=$total_amount;
	
   		$trans_no = get_next_trans_no($trans_type);
    	$do_exchange_variance = $SysPrefs->auto_currency_revaluation();
    	if ($do_exchange_variance)
    		$trans_no1 = get_next_trans_no(ST_JOURNAL);
    }
	if ($aid != 0)
	{
		$row = get_attachment($aid);
		update_attachment($aid, $row['type_no'], $trans_no, $row['description'],
			$row['filename'], $row['unique_name'], $row['filesize'], $row['filetype']);
	}
	// do the source account postings
	$ex_rate=$ex_rate==''?1:$ex_rate;

    add_bank_trans($trans_type, $trans_no, $from_account, $ref,
    	$date_, -$total_amount,
    	$person_type_id, $person_id,
    	$currency,
    	"Cannot insert a source bank transaction",$ex_rate,$mode_of_payment,'',$cheque_no,$dd_no,$date_of_issue,$dd_date_of_issue,$pymt_ref,$is_purch_cash_bill,$target_currency,$target_value,$our_ref_no,$amex);
		
		
		
	$total = 0;
	foreach ($items->gl_items as $gl_item)
	{
		$is_bank_to = is_bank_account($gl_item->code_id);

		if ($trans_type == ST_BANKPAYMENT AND $is_bank_to)
		{
			// we don't allow payments to go to a bank account. use transfer for this !
			display_db_error("invalid payment entered. Cannot pay to another bank account", "");
		}

    	// do the destination account postings
    	$total += add_gl_trans($trans_type, $trans_no, $date_, $gl_item->code_id,
    		$gl_item->dimension_id, $gl_item->dimension2_id, $gl_item->reference,
    		$gl_item->amount, $currency, $person_type_id, $person_id,"",$ex_rate);

    	if ($is_bank_to)
    	{
    		add_bank_trans($trans_type, $trans_no, $is_bank_to, $ref,
    			$date_, $gl_item->amount,
    			$person_type_id, $person_id, $currency,
    			"Cannot insert a destination bank transaction",$ex_rate);
				
    		if ($do_exchange_variance)
    		{
    			add_exchange_variation($trans_no1, $date_, $is_bank_to, $gl_item->code_id, 
    				$currency, $person_type_id, $person_id);
    		}
    	}
		// store tax details if the gl account is a tax account

		$amount = $gl_item->amount;
		//$ex_rate = get_exchange_rate_from_home_currency($currency, $date_);

		add_gl_tax_details($gl_item->code_id, $trans_type, $trans_no, -$amount,
			$ex_rate, $date_, $memo_);
			
			
		if($is_purch_cash_bill=='1') 
		{
			add_multiple_purchase_cash_bill($trans_no, $gl_item->code_id, $gl_item->dimension_id, $gl_item->amount, $gl_item->supp_name, $gl_item->supp_vat_no, $gl_item->supp_bill_no, $gl_item->supp_bill_date, $gl_item->bill_amount, $gl_item->is_tax_account,$gl_item->reference);
		}		
	}

	// do the source account postings
    add_gl_trans($trans_type, $trans_no, $date_, $bank_gl_account, $gl_item->dimension_id, $gl_item->dimension2_id, $memo_,
    	-$total, null, $person_type_id, $person_id,"",$ex_rate);

    if ($do_exchange_variance)
    {
    	add_exchange_variation($trans_no1, $date_, $from_account, $bank_gl_account, 
    		$currency, $person_type_id, $person_id);
	}

	add_comments($trans_type, $trans_no, $date_, $memo_);

	$Refs->save($trans_type, $trans_no, $ref);
	add_audit_trail($trans_type, $trans_no, $date_);

	// old transaction can be voided only after new transaction is entered,
	//  otherwise the operation could fail for cash accounts due to temporary negative balance
	if ($old_trans) 
	{
		$msg = void_transaction($trans_type, $old_trans, $date_, _("Document reentered."));
		if ($msg)
		{
			display_error($msg);
			return false;
		}
	}


	$args->trans_no = $trans_no;
	hook_db_postwrite($args, $trans_type);
	if ($use_transaction)
		commit_transaction();

	return array($trans_type, $trans_no);
}


//ravi advance payment return
function write_bank_transaction_for_advance_return($trans_type, $trans_no, $from_account, $items, $date_,
	$person_type_id, $person_id, $person_detail_id,	$ref, $memo_, $use_transaction=true, 
	$settled_amount=null,$mode_of_payment='',$cheque_no='',$dd_no='',$date_of_issue='',
	$dd_date_of_issue='',$pymt_ref='',$is_purch_cash_bill=0,$our_ref_no='',$ex_rate=1,
	$amex=0,$advance_ids)
{
	global $Refs, $SysPrefs;

	// we can only handle type 1 (payment)and type 2 (deposit)
	if ($trans_type != ST_BANKPAYMENT && $trans_type != ST_BANKDEPOSIT)
		display_db_error("Invalid type ($trans_type) sent to add_bank_transaction");

	$do_exchange_variance = false;
	if ($use_transaction)
		begin_transaction();

	$args = func_get_args(); if (count($args) < 11) $args[] = true;
	$args = (object)array_combine(array('trans_type', 'trans_no', 'from_account', 'items', 'date_',
		'person_type_id', 'person_id', 'person_detail_id', 'ref', 'memo_', 'use_transaction', 'settled_amount','mode_of_payment','cheque_no','dd_no','date_of_issue','dd_date_of_issue','pymt_ref','is_purch_cash_bill','our_ref_no','ex_rate','amex','advance_ids'),
		$args);
	hook_db_prewrite($args, $trans_type);

	$aid = 0;
	if ($trans_no) {
		$old_trans = $trans_no;
		$Refs->restore_last($trans_type, $trans_no);
		$aid = has_attachment($trans_type, $trans_no);
	} else
		$old_trans = false;

	$currency = get_bank_account_currency($from_account);
	$bank_gl_account = get_bank_gl_account($from_account);

	// the gl items are already inversed/negated for type 2 (deposit)
	$total_amount = $items->gl_items_total();

    if ($person_type_id == PT_CUSTOMER)
    {
    	// we need to add a customer transaction record
		// convert to customer currency
		if (!isset($settled_amount)) // leaved for backward/ext compatibility 
			$cust_amount = exchange_from_to(abs($total_amount), $currency, get_customer_currency($person_id), $date_);
		else
			$cust_amount = $settled_amount;

		if ($trans_type == ST_BANKPAYMENT)
			$cust_amount = -$cust_amount;
			
		$target_ex_rate=$total_amount/(-$cust_amount);
		$target_currency=get_customer_currency($person_id);
		$target_value=$cust_amount;		

		$trans_no = write_customer_trans($trans_type, 0, $person_id, $person_detail_id, $date_,
        	$ref, $cust_amount);
		if ($old_trans)
			move_trans_attachments($trans_type, $old_trans, $trans_no);
		
		//ravi
		$transactional_amount=abs($cust_amount);
		
        if($transactional_amount!=0 && count($advance_ids)>0)
       {
	    $total_advance=0; 
		$allocated_advance=0;
		$advances_info=get_sales_advances_info_by_ids($advance_ids);
		while($sales_advance=db_fetch($advances_info))
		{
			$advance_amount=$sales_advance['ov_amount'];
			$total_advance+=$advance_amount;
			if($total_advance<=$transactional_amount)
			{
				//tax on adjusted value
				
			add_cust_allocation($advance_amount, ST_CUSTPAYMENT, $sales_advance['trans_no'], ST_BANKPAYMENT, $trans_no, $person_id, $date_);
			update_debtor_trans_allocation(ST_BANKPAYMENT, $trans_no, $person_id);
			update_debtor_trans_allocation(ST_CUSTPAYMENT, $sales_advance['trans_no'], $person_id);
				  
			$vat_advance_act = get_company_pref('sales_vat_advance');
			
			$tax_amount_on_advance= ($advance_amount*5)/(105);
			
		    add_gl_trans(ST_CUSTPAYMENT, $sales_advance['trans_no'], $date_,$vat_advance_act, 0, 0, 'BT '.$ref, 
			-$tax_amount_on_advance, get_customer_currency($person_id), PT_CUSTOMER,   $person_id,""); //ravi
			
		    add_gl_trans(ST_CUSTPAYMENT, $sales_advance['trans_no'], $date_,$sales_advance['vat_act'], 0, 0, 'BT '.$ref, 
			$tax_amount_on_advance, get_customer_currency($person_id), PT_CUSTOMER,   $person_id,""); //ravi
			
		    $dsql = "UPDATE ".TB_PREF."debtor_trans SET vat_adv_status='0' WHERE type=12 AND trans_no=".db_escape($sales_advance['trans_no'])."";
	    	$dres = db_query($dsql);
		  
			$allocated_advance+=$advance_amount; 
			}
				
				if($total_advance>$transactional_amount && $transactional_amount>$allocated_advance)
				{
					$tax_amount_on_advance= (($transactional_amount-$allocated_advance)*5)/(105);


				 add_cust_allocation($transactional_amount-$allocated_advance, ST_CUSTPAYMENT, $sales_advance['trans_no'], ST_BANKPAYMENT, $trans_no, $person_id, $date_);

				 update_debtor_trans_allocation(ST_BANKPAYMENT, $trans_no, $person_id);
				   update_debtor_trans_allocation(ST_CUSTPAYMENT, $sales_advance['trans_no'], $person_id);
				   
				 $vat_advance_act = get_company_pref('sales_vat_advance');
			    
			
				add_gl_trans(ST_CUSTPAYMENT, $sales_advance['trans_no'], $date_,$vat_advance_act, 0, 0,'BT '.$ref, 
				-$tax_amount_on_advance, get_customer_currency($person_id), PT_CUSTOMER,$person_id,""); //ravi
			
				add_gl_trans(ST_CUSTPAYMENT, $sales_advance['trans_no'], $date_,$sales_advance['vat_act'], 0,0, "BT ".$ref, 
				$tax_amount_on_advance, get_customer_currency($person_id), PT_CUSTOMER,   $person_id,""); //ravi
			 
				   $allocated_advance+=($transactional_amount-$allocated_advance);
				}
				
		}		
	    }		
		//end
		
		
		
    }
    elseif ($person_type_id == PT_SUPPLIER)
    {
    	// we need to add a supplier transaction record
		// convert to supp currency
		if (!isset($settled_amount)) // leaved for for backward/ext compatibility 
			$supp_amount = exchange_from_to(abs($total_amount), $currency, get_supplier_currency($person_id), $date_);
		else
			$supp_amount = $settled_amount;

		if ($trans_type == ST_BANKPAYMENT)
			$supp_amount = -$supp_amount;
			
		$target_ex_rate=$total_amount/(-$supp_amount);
		$target_currency=get_supplier_currency($person_id);
		$target_value=$supp_amount;			

		$trans_no = write_supp_trans($trans_type, 0, $person_id, $date_, '',
			$ref, "", $supp_amount, 0, 0);
		if ($old_trans)
			move_trans_attachments($trans_type, $old_trans, $trans_no);
    }
    else
    {
	    $target_ex_rate=1;
		$target_currency=$currency;
		$target_value=$total_amount;
	
   		$trans_no = get_next_trans_no($trans_type);
    	$do_exchange_variance = $SysPrefs->auto_currency_revaluation();
    	if ($do_exchange_variance)
    		$trans_no1 = get_next_trans_no(ST_JOURNAL);
    }
	if ($aid != 0)
	{
		$row = get_attachment($aid);
		update_attachment($aid, $row['type_no'], $trans_no, $row['description'],
			$row['filename'], $row['unique_name'], $row['filesize'], $row['filetype']);
	}
	// do the source account postings
	$ex_rate=$ex_rate==''?1:$ex_rate;

    add_bank_trans($trans_type, $trans_no, $from_account, $ref,
    	$date_, -$total_amount,
    	$person_type_id, $person_id,
    	$currency,
    	"Cannot insert a source bank transaction",$ex_rate,$mode_of_payment,'',$cheque_no,$dd_no,$date_of_issue,$dd_date_of_issue,$pymt_ref,$is_purch_cash_bill,$target_currency,$target_value,$our_ref_no,$amex);
		
		
		
	$total = 0;
	foreach ($items->gl_items as $gl_item)
	{
		$is_bank_to = is_bank_account($gl_item->code_id);

		if ($trans_type == ST_BANKPAYMENT AND $is_bank_to)
		{
			// we don't allow payments to go to a bank account. use transfer for this !
			display_db_error("invalid payment entered. Cannot pay to another bank account", "");
		}

    	// do the destination account postings
    	$total += add_gl_trans($trans_type, $trans_no, $date_, $gl_item->code_id,
    		$gl_item->dimension_id, $gl_item->dimension2_id, $gl_item->reference,
    		$gl_item->amount, $currency, $person_type_id, $person_id,"",$ex_rate);

    	if ($is_bank_to)
    	{
    		add_bank_trans($trans_type, $trans_no, $is_bank_to, $ref,
    			$date_, $gl_item->amount,
    			$person_type_id, $person_id, $currency,
    			"Cannot insert a destination bank transaction",$ex_rate);
				
    		if ($do_exchange_variance)
    		{
    			add_exchange_variation($trans_no1, $date_, $is_bank_to, $gl_item->code_id, 
    				$currency, $person_type_id, $person_id);
    		}
    	}
		// store tax details if the gl account is a tax account

		$amount = $gl_item->amount;
		//$ex_rate = get_exchange_rate_from_home_currency($currency, $date_);

		add_gl_tax_details($gl_item->code_id, $trans_type, $trans_no, -$amount,
			$ex_rate, $date_, $memo_);
			
			
		if($is_purch_cash_bill=='1') 
		{
			add_multiple_purchase_cash_bill($trans_no, $gl_item->code_id, $gl_item->dimension_id, $gl_item->amount, $gl_item->supp_name, $gl_item->supp_vat_no, $gl_item->supp_bill_no, $gl_item->supp_bill_date, $gl_item->bill_amount, $gl_item->is_tax_account,$gl_item->reference);
		}		
	}

	// do the source account postings
    add_gl_trans($trans_type, $trans_no, $date_, $bank_gl_account, $gl_item->dimension_id, $gl_item->dimension2_id, $memo_,
    	-$total, null, $person_type_id, $person_id,"",$ex_rate);

    if ($do_exchange_variance)
    {
    	add_exchange_variation($trans_no1, $date_, $from_account, $bank_gl_account, 
    		$currency, $person_type_id, $person_id);
	}

	add_comments($trans_type, $trans_no, $date_, $memo_);

	$Refs->save($trans_type, $trans_no, $ref);
	add_audit_trail($trans_type, $trans_no, $date_);

	// old transaction can be voided only after new transaction is entered,
	//  otherwise the operation could fail for cash accounts due to temporary negative balance
	if ($old_trans) 
	{
		$msg = void_transaction($trans_type, $old_trans, $date_, _("Document reentered."));
		if ($msg)
		{
			display_error($msg);
			return false;
		}
	}


	$args->trans_no = $trans_no;
	hook_db_postwrite($args, $trans_type);
	if ($use_transaction)
		commit_transaction();

	return array($trans_type, $trans_no);
}
///--------------------------------------------------------------------------------------
// Petty Cash entry

function check_exists_petty_cash_our_ref_no($our_ref_no)
{
	 $sql="SELECT COUNT(*) FROM ".TB_PREF."bank_trans 
	 WHERE type=1
	 AND our_ref_no=".db_escape($our_ref_no);
	 $result = db_query($sql, "could not do check empty query");	
	 $myrow = db_fetch_row($result);
	 return ($myrow[0] > 0)?1:0; 
}



function get_petty_cash_reference_by_our_ref_no($our_ref_no)
{


   $sql="SELECT ref FROM ".TB_PREF."bank_trans 
   WHERE type=1
   AND our_ref_no= ".db_escape($our_ref_no)."";
   $result = db_query($sql, "No standard cost transactions were returned");
   if($row=db_fetch_row($result))
   {
      return $row[0];
   }
   else
   return "Not Found!";
}


function add_gl_pettycash_trans($type, $trans_no, $bank_act, $ref, $date_,
	 $account, $dimension, $dimension2, $memo_, $amount, $person_type_id=null, $person_id, 
	 $rate=0,$target_currency='',$target_amount=0,$our_ref_no='')
{
	$sqlDate = date2sql($date_);

	// convert $amount to the bank's currency
	if ($currency != "")
	{
    	$bank_account_currency = get_bank_account_currency($bank_act);
    	if ($rate == 0){
    		$to_bank_currency = get_exchange_rate_from_to($currency, $bank_account_currency, $date_);
			$amount_bank = ($amount / $to_bank_currency);
			}
    	else
    		//$to_bank_currency = 1 / $rate;
    	
		$amount_bank = ($amount);
	}
	else
		$amount_bank = $amount;
	$amount_bank = round2($amount_bank, user_price_dec());	
	
	
	$sql = "INSERT INTO ".TB_PREF."gl_pettycash_trans (type, trans_no, bank_act, ref,
		trans_date, account, dimension_id, dimension2_id, memo_, amount, 
		person_type_id,person_id,target_currency,target_amount,our_ref_no) ";

	$sql .= "VALUES ($type, $trans_no, '$bank_act', ".db_escape($ref).", '$sqlDate',
	    ".db_escape($account).", ".db_escape($dimension).", "
		.db_escape($dimension2).", ".db_escape($memo_).",
		".db_escape($amount_bank).", ". db_escape($person_type_id).",
		". db_escape($person_id).",". db_escape($target_currency).", 
		". db_escape($target_amount).",".db_escape($our_ref_no).")";

	if ($err_msg == "")
		$err_msg = "The bank transaction could not be inserted";

	db_query($sql, $err_msg);
}


function write_bank_transaction_for_petty_cash_entry($trans_type, $trans_no, $from_account, 
    $items, $date_, $memo_, $use_transaction=true, $our_ref_no='',$ex_rate=1)
{
	global $SysPrefs, $path_to_root, $Refs;
	begin_transaction();
	$args = func_get_args();
	$args = (object)array_combine(array('trans_type','trans_no', 'from_account', 'items',
	'date_','memo_','use_transaction','our_ref_no','ex_rate'), $args);
	$args->trans_no = 0;
	hook_db_prewrite($args, ST_BANKPAYMENT);
    $tran_date = date2sql($date_); 	 	
 	
	$trans_type=ST_BANKPAYMENT;
	
	
	$currency = get_bank_account_currency($from_account);
	$bank_gl_account = get_bank_gl_account($from_account);
	
	foreach ($items->gl_items as $gl_item)
	{
	
	$trans_no = get_next_trans_no(ST_BANKPAYMENT);
		
	$ref = $Refs->get_next(ST_BANKPAYMENT, null, array('date'=>get_post('date_'),'bank_act'=>$_POST['bank_account']));
	
	
	$total_amount = $gl_item->amount;
	
	
	if($gl_item->person_id!=''){
	    $person_type_id = PT_SUPPLIER;
	}
	else
	    $person_type_id = "";
	
	
	  if ($person_type_id == PT_SUPPLIER)
      {
    	// we need to add a supplier transaction record
		// convert to supp currency
		if (!isset($settled_amount)) // leaved for for backward/ext compatibility 
			$supp_amount = exchange_from_to(abs($total_amount), $currency, get_supplier_currency($gl_item->person_id), $date_);
		else
			$supp_amount = $settled_amount;

		if ($trans_type == ST_BANKPAYMENT)
			$supp_amount = -$supp_amount;
			
		$target_ex_rate=$total_amount/(-$supp_amount);
		$target_currency=get_supplier_currency($gl_item->person_id);
		$target_value=$supp_amount;	
		
		$trans_no = write_supp_trans($trans_type, 0, $gl_item->person_id, $date_, '',
			$ref, "", $supp_amount, 0, 0, "", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,$our_ref_no);
	
		if ($old_trans)
			move_trans_attachments($trans_type, $old_trans, $trans_no);
     }
     else
     {
	    $target_ex_rate=1;
		$target_currency=$currency;
		$target_value=$total_amount;
	
   		$trans_no = get_next_trans_no($trans_type);
    	$do_exchange_variance = $SysPrefs->auto_currency_revaluation();
    	if ($do_exchange_variance)
    		$trans_no1 = get_next_trans_no(ST_JOURNAL);
     }	
		$ex_rate=$ex_rate==''?1:$ex_rate;
		
		 add_bank_trans($trans_type, $trans_no, $from_account, $ref,
    	$date_, -$gl_item->amount,
    	$person_type_id, $gl_item->person_id,
    	$currency,
    	"Cannot insert a source bank transaction",$ex_rate,$mode_of_payment,'',$cheque_no,$dd_no,$date_of_issue,$dd_date_of_issue,$pymt_ref,$is_purch_cash_bill,$target_currency,$target_value,$our_ref_no,$amex);
	
	
	$is_bank_to = is_bank_account($gl_item->code_id);

		if ($trans_type == ST_BANKPAYMENT AND $is_bank_to)
		{
			// we don't allow payments to go to a bank account. use transfer for this !
			display_db_error("invalid payment entered. Cannot pay to another bank account", "");
		}

    	// do the destination account postings
    	add_gl_trans($trans_type, $trans_no, $date_, $gl_item->code_id,
    		$gl_item->dimension_id, $gl_item->dimension2_id, $gl_item->reference,
    		$gl_item->amount, $currency, $person_type_id, $gl_item->person_id,"",$ex_rate);
	
	 add_gl_trans($trans_type, $trans_no, $date_, $bank_gl_account, $gl_item->dimension_id, $gl_item->dimension2_id, $memo_, -$gl_item->amount, null, $person_type_id, $gl_item->person_id,"",$ex_rate);
	 
	 
	 //Petty Cash Transactions stored in new table
	  add_gl_pettycash_trans($trans_type, $trans_no, $from_account, $ref,$date_, 
    	$gl_item->code_id,$gl_item->dimension_id, $gl_item->dimension2_id, $gl_item->reference,
		-$gl_item->amount,$person_type_id,$gl_item->person_id,$ex_rate,$target_currency,$target_value,$our_ref_no);	 
	 
	 
	 add_comments($trans_type, $trans_no, $date_, $memo_);
	 
	 $Refs->save($trans_type, $trans_no, $ref);
	 add_audit_trail($trans_type, $trans_no, $date_);
	
	
	}

	$args->trans_no = $trans_no;
	hook_db_postwrite($args, $trans_type);
	if ($use_transaction)
		commit_transaction();

	return array($trans_type, $trans_no);
}


function get_gl_petty_cash_entry_our_ref_no($trans_no)
{
	$sql = "SELECT our_ref_no FROM ".TB_PREF."gl_pettycash_trans 
	WHERE trans_no=".db_escape($trans_no)." 
	AND type = ".ST_BANKPAYMENT."";
	$result = db_query($sql, "could not get discount");
	$myrow = db_fetch_row($result);
	return $myrow[0]; 
}


function get_gl_petty_cash_trans($type, $our_ref_no=null)
{
	$sql = "SELECT bt.*, act.*,
		IFNULL(abs(dt.ov_amount), IFNULL(ABS(st.ov_amount), bt.amount)) settled_amount,
		IFNULL(abs(dt.ov_amount/bt.amount), IFNULL(ABS(st.ov_amount/bt.amount), 1)) settle_rate,
		IFNULL(debtor.curr_code, IFNULL(supplier.curr_code, act.bank_curr_code)) settle_curr

		FROM ".TB_PREF."gl_pettycash_trans bt
				 LEFT JOIN ".TB_PREF."debtor_trans dt ON dt.type=bt.type AND dt.trans_no=bt.trans_no
				 LEFT JOIN ".TB_PREF."debtors_master debtor ON debtor.debtor_no = dt.debtor_no
				 LEFT JOIN ".TB_PREF."supp_trans st ON st.type=bt.type AND st.trans_no=bt.trans_no
				 LEFT JOIN ".TB_PREF."suppliers supplier ON supplier.supplier_id = st.supplier_id,
			 ".TB_PREF."bank_accounts act
		WHERE act.id=bt.bank_act ";
	if (isset($type))
		$sql .= " AND bt.type=".db_escape($type);
		
	if (isset($our_ref_no))
		$sql .= " AND bt.our_ref_no = ".db_escape($our_ref_no);
	
	$sql .= " GROUP BY bt.our_ref_no ORDER BY trans_date, bt.id";
	
	

	return db_query($sql, "query for bank transaction");
}


function get_petty_cash_gl_trans($type, $our_ref_no)
{
	$sql = "SELECT gl.*, cm.account_name, IFNULL(refs.reference, '') AS reference, user.real_name, 
			COALESCE(st.tran_date, dt.tran_date, bt.trans_date, grn.delivery_date, gl.trans_date) as doc_date,
			IF(ISNULL(st.supp_reference), '', st.supp_reference) AS supp_reference
	FROM ".TB_PREF."gl_pettycash_trans as gl
		LEFT JOIN ".TB_PREF."chart_master as cm ON gl.account = cm.account_code
		LEFT JOIN ".TB_PREF."refs as refs ON (gl.type=refs.type AND gl.trans_no=refs.id)
		LEFT JOIN ".TB_PREF."audit_trail as audit ON (gl.type=audit.type AND gl.trans_no=audit.trans_no AND NOT ISNULL(gl_seq))
		LEFT JOIN ".TB_PREF."users as user ON (audit.user=user.id)
	# all this below just to retrieve doc_date :>
		LEFT JOIN ".TB_PREF."supp_trans st ON gl.trans_no=st.trans_no AND st.type=gl.type AND (gl.type!=".ST_JOURNAL." OR gl.person_id=st.supplier_id)
		LEFT JOIN ".TB_PREF."grn_batch grn ON grn.id=gl.trans_no AND gl.type=".ST_SUPPRECEIVE." AND gl.person_id=grn.supplier_id
		LEFT JOIN ".TB_PREF."debtor_trans dt ON gl.trans_no=dt.trans_no AND dt.type=gl.type AND (gl.type!=".ST_JOURNAL." OR gl.person_id=dt.debtor_no)
		LEFT JOIN ".TB_PREF."bank_trans bt ON bt.type=gl.type AND bt.trans_no=gl.trans_no AND bt.amount!=0
			 AND bt.person_type_id=gl.person_type_id AND bt.person_id=gl.person_id
		LEFT JOIN ".TB_PREF."journal j ON j.type=gl.type AND j.trans_no=gl.trans_no"

		." WHERE gl.type= ".db_escape($type) 
		." AND gl.our_ref_no = ".db_escape($our_ref_no)
		." AND gl.amount <> 0"
		." ORDER BY gl.id";

	return db_query($sql, "The gl transactions could not be retrieved");
}



function get_sql_for_petty_cash_inquiry($FromDate,$ToDate,$our_ref_no="",$bank_account=null)
{
	$date_after = date2sql($FromDate);
	$date_before = date2sql($ToDate);
	
	$sql="SELECT pettycash.our_ref_no,pettycash.trans_date,act.bank_account_name,
	pettycash.our_ref_no as pc_ref_no
	FROM ".TB_PREF."gl_pettycash_trans AS pettycash,".TB_PREF."bank_accounts AS act 
	WHERE  act.id=pettycash.bank_act
	AND pettycash.trans_date>=".db_escape($date_after)." 
	AND pettycash.trans_date<=".db_escape($date_before);
	
	if ($our_ref_no!='')
		$sql .= " AND pettycash.our_ref_no = ".db_escape($our_ref_no);
	
	
	if (isset($bank_account))
		$sql .= " AND pettycash.bank_act = ".db_escape($bank_account);	
	
	
	$sql .= " GROUP BY pettycash.our_ref_no ORDER BY trans_date, pettycash.id";
	
	
   return $sql;
}


function get_gl_petty_cash_entry_trans_no_by_our_ref_no($our_ref_no)
{
	$sql = "SELECT trans_no FROM ".TB_PREF."gl_pettycash_trans 
	WHERE our_ref_no=".db_escape($our_ref_no)." 
	AND type = ".ST_BANKPAYMENT." ORDER BY id DESC  LIMIT 1";
	$result = db_query($sql, "could not get discount");
	$myrow = db_fetch_row($result);
	return $myrow[0]; 
}