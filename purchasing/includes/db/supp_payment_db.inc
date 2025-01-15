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
function write_supp_payment($trans_no, $supplier_id, $bank_account,
	$date_, $ref, $supp_amount, $supp_discount, $memo_, $bank_charge=0, $bank_amount=0, $dimension=0, 
	$dimension2=0,$mode_of_payment='',$cheque_no='',$dd_no='',$date_of_issue='',$dd_date_of_issue='',
	$pymt_ref='',$our_ref_no="",$amex=0, $vat_charge=0)
{
	global $Refs;
	
	begin_transaction();
	
	$args = func_get_args(); while (count($args) < 12) $args[] = 0;
	$args = (object)array_combine(array('trans_no', 'supplier_id', 'bank_account', 'date_',
		 'ref', 'supp_amount', 'supp_discount', 'memo_', 'bank_charge', 'bank_amount', 'dimension', 'dimension2','mode_of_payment','cheque_no','dd_no','date_of_issue','dd_date_of_issue',
		 'pymt_ref','our_ref_no','amex','vat_charge'), $args);
	$args->trans_no = 0;
	hook_db_prewrite( $args, ST_SUPPAYMENT);
	
	if ($trans_no != 0) {
	  delete_comments(ST_SUPPAYMENT, $trans_no);
	  void_bank_trans(ST_SUPPAYMENT, $trans_no, true);
	  delete_bank_trans(ST_SUPPAYMENT,$trans_no);
	  void_gl_trans(ST_SUPPAYMENT, $trans_no, true);
	  void_supp_allocations(ST_SUPPAYMENT, $trans_no, $date_);
	}
	$bank = get_bank_account($bank_account);
	
	if (!$bank_amount) 	// auto 
	{
		$rate = get_exchange_rate_from_to(get_supplier_currency($supplier_id),
			$bank['bank_curr_code'], $date_ );
		$bank_amount = $supp_amount/$rate;
	}

	if ($bank['bank_curr_code'] == get_company_currency()) // [0002506]
		$rate = $bank_amount/$supp_amount;
	else
		$rate = 0; 

	/* Create a supp_trans entry for the supplier payment */
	
	$payment_id = write_supp_trans(ST_SUPPAYMENT, $trans_no, $supplier_id, $date_, $date_,
		$ref, "", -$supp_amount, 0, -$supp_discount, "", $rate,"","","","","","","","","","",$our_ref_no
	);
	
	// Now debit creditors account with payment + discount

	$total = 0;
    $supplier_accounts = get_supplier_accounts($supplier_id);
	$total += add_gl_trans_supplier(ST_SUPPAYMENT, $payment_id, $date_, $supplier_accounts["payable_account"], $dimension, $dimension2,
		$supp_amount + $supp_discount, $supplier_id, "", $rate);

	// Now credit discount received account with discounts
	if ($supp_discount != 0)
	{
		$total += add_gl_trans_supplier(ST_SUPPAYMENT, $payment_id, $date_, $supplier_accounts["payment_discount_account"], $dimension, $dimension2,
			-$supp_discount, $supplier_id, "", $rate);
	}
	
	if ($bank['bank_curr_code'] == get_company_currency()) // [0002506]
		$rate = 1;
		
	if($bank_amount!=0 && $bank['bank_curr_code']!= get_company_currency() && $rate==1)
	{
	    
		   $rate=$supp_amount/$bank_amount;	
	}		

	if ($bank_charge != 0)
	{
		$charge_act = get_company_pref('bank_charge_act');
		$total += add_gl_trans(ST_SUPPAYMENT, $payment_id, $date_, $charge_act, $dimension, $dimension2, '',$bank_charge, $bank['bank_curr_code'],null,null,"",$rate);
	}
	
	
	if ($vat_charge != 0)
	{
		$vat_charge_act = get_bank_vat_charge_account($bank_account);
		$total += add_gl_trans(ST_SUPPAYMENT, $payment_id, $date_, $vat_charge_act, $dimension, $dimension2, '',$vat_charge, $bank['bank_curr_code'],null,null,"",$rate);
	}

	$total += add_gl_trans(ST_SUPPAYMENT, $payment_id, $date_, $bank['account_code'], $dimension, $dimension2, '',
		-($bank_amount + $bank_charge + $vat_charge), $bank['bank_curr_code'], PT_SUPPLIER, $supplier_id,"",$rate);

	/*Post a balance post if $total != 0 due to variance in AP and bank posted values*/
	if ($total != 0)
	{
		$variance_act = get_company_pref('exchange_diff_act');
		add_gl_trans(ST_SUPPAYMENT, $payment_id, $date_, $variance_act, $dimension, $dimension2, '',
			-$total, null, PT_SUPPLIER,  $supplier_id);
	}
	
	
	$target_ex_rate=$bank_amount/$supp_amount;
		$target_currency=get_supplier_currency($supplier_id);
		$target_value=$supp_amount;
		$bank_curr=get_bank_account_currency($bank_account);

   /*now enter the bank_trans entry */
	add_bank_trans(ST_SUPPAYMENT, $payment_id, $bank_account, $ref,
		$date_, -($bank_amount + $bank_charge + $vat_charge), PT_SUPPLIER, $supplier_id,$bank_curr,"",$target_ex_rate,
		$mode_of_payment,'',$cheque_no,$dd_no,$date_of_issue,$dd_date_of_issue,$pymt_ref,
		0,$target_currency,-$target_value,$our_ref_no,$amex);

	add_comments(ST_SUPPAYMENT, $payment_id, $date_, $memo_);

	$Refs->save(ST_SUPPAYMENT, $payment_id, $ref);

	$args->trans_no = $payment_id;
	hook_db_postwrite($args, ST_SUPPAYMENT);

	commit_transaction();

	return $payment_id;
}



function update_recall_info_against_supplier_pdc($payment_id, $recall_date, $pdc_number,$supplier_id)
{
	
	$pdc_amount = get_supp_pdc_amount($pdc_number);
	
	$supp_pdcsql = "SELECT * FROM ".TB_PREF."supp_pdc_allocations WHERE trans_type_from='".ST_SUPPPDC."' AND trans_no_from = ".db_escape($pdc_number)." AND person_id=".db_escape($supplier_id)." AND recall_status=0 ";
	$supp_pdcres = db_query($supp_pdcsql);
	$supp_pdc_results = db_fetch($supp_pdcres);
	
	
	$dsql = "UPDATE ".TB_PREF."supp_trans SET 
	recall_status='1',current_pdc_status='1',
	recall_date=".db_escape(date2sql($recall_date)).", 
	recall_remarks='$payment_id' ,
	ov_amount = '0',alloc='0'  
	WHERE type='".ST_SUPPPDC."' AND trans_no=".db_escape($pdc_number)."";
	$dres = db_query($dsql);
	
	$suppsql = "UPDATE ".TB_PREF."supp_trans SET 
	        pdc_amt = pdc_amt - ".db_escape($supp_pdc_results['amt'])."
			WHERE trans_no = ".db_escape($supp_pdc_results['trans_no_to'])." 
			AND type = ".db_escape($supp_pdc_results['trans_type_to'])." 
			AND supplier_id=".db_escape($supplier_id)." ";			
			db_query($suppsql);	
	
	//  ## Ramesh  for recal status update.
	$supp_pdc_rcsql = "UPDATE ".TB_PREF."supp_pdc_allocations SET 
	recall_status='1' WHERE trans_type_from='".ST_SUPPPDC."' AND trans_no_from = ".db_escape($pdc_number)." AND person_id=".db_escape($supplier_id)." AND recall_status=0";
	$supp_pdcrcres = db_query($supp_pdc_rcsql);
}




//------------------------------------------------------------------------------------------------

function void_supp_payment($type, $type_no)
{
	begin_transaction();
	hook_db_prevoid($type, $type_no);

	void_bank_trans($type, $type_no, true);
	void_gl_trans($type, $type_no, true);
	void_supp_allocations($type, $type_no);
	void_supp_trans($type, $type_no);

	commit_transaction();
}

function void_supp_pdc($type, $type_no)
{
	begin_transaction();
	hook_db_prevoid($type, $type_no);

	void_bank_trans($type, $type_no, true);
	void_gl_trans($type, $type_no, true);
	void_supp_allocations($type, $type_no);
	void_supp_pdc_trans($type, $type_no);

	commit_transaction();
}
function delete_bank_trans($trans_type, $trans_no)
{
	$sql = "DELETE FROM ".TB_PREF."bank_trans WHERE type =".db_escape($trans_type)." AND trans_no = ".db_escape($trans_no);
	db_query($sql, "The existing allocation $trans_id could not be deleted");
}