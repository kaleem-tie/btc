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
/*
  Write/update customer payment.

  Warning: $rate is leaved here for extensions compatibility reasons, will be removed in 2.4
	since 2.3.17 is not used: use $bank_amount instead.

	$amount - in customer currency (ex. discount)
	$discount - in customer currency
	$bank_amount - in bank currency (before charge)
	$charge - in bank currency
*/
function write_customer_payment(
	$trans_no, $customer_id, $branch_id, $bank_account, $date_,
	$ref, $amount, $discount, $memo_, $rate=0,
	$charge=0, $bank_amount=0, $dim1=0, $dim2=0,$mode_of_payment='',
	$cheque_no='',$dd_no='',$date_of_issue='',$dd_date_of_issue='', $pymt_ref=0,
	$our_ref_no="",$amex=0,$sales_person_id=0,$sales_person_ref="",$ref_no=null)
{
	global $Refs;
	
	begin_transaction();
	$args = func_get_args();
	// while (count($args) < 24) $args[] = 0;

	$args = (object)array_combine(
		array(
			'trans_no', 'customer_id', 'branch_id', 'bank_account', 'date_',
			'ref', 'amount', 'discount', 'memo_','rate',
			'charge', 'bank_amount', 'dim1', 'dim2','mode_of_payment',
			'cheque_no', 'dd_no', 'date_of_issue','dd_date_of_issue','pymt_ref',
			'our_ref_no','amex','sales_person_id','sales_person_ref','ref_no'), $args);
	hook_db_prewrite($args, ST_CUSTPAYMENT);

	$company_record = get_company_prefs();

	if ($trans_no != 0) {
	  	delete_comments(ST_CUSTPAYMENT, $trans_no);
	  	void_bank_trans(ST_CUSTPAYMENT, $trans_no, true);
	  	void_gl_trans(ST_CUSTPAYMENT, $trans_no, true);
	  	void_cust_allocations(ST_CUSTPAYMENT, $trans_no, $date_);
	}

	$bank = get_bank_account($bank_account);

	if (!$bank_amount) 	// backward compatibility workaround
	{
		if(!$rate)
			$rate = get_exchange_rate_from_to(get_customer_currency($customer_id),
				$bank['bank_curr_code'], $date_ );

		$bank_amount = $amount/$rate;
	}

	// do not use $rate here: global rate stored in exrate table is always used
	//$payment_no = write_customer_trans(ST_CUSTPAYMENT, $trans_no, $customer_id, $branch_id, 
	//$date_, $ref, $amount, $discount);
	$payment_no = write_customer_trans(
		ST_CUSTPAYMENT, $trans_no, $customer_id, $branch_id,
		$date_, $ref, $amount, $discount,
		0, 0, 0, 0,
		0, 0, "", 0,
		0, 0, 0, null,
		0, 0,"",'',
		$bank_account,'', 0, 0,
		$sales_person_id, 0, 0, 0,
		"", "", '', "",
		$sales_person_ref, "",$ref_no
	);	


	$bank_gl_account = get_bank_gl_account($bank_account);

	$total = 0;

	/* Bank account entry first */
	$total += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_,
		$bank_gl_account, $dim1, $dim2, '', ($bank_amount - $charge),  $bank['bank_curr_code'], PT_CUSTOMER, $customer_id);

	if ($branch_id != ANY_NUMERIC) {

		$branch_data = get_branch_accounts($branch_id);

		$debtors_account = $branch_data["receivables_account"];
		$discount_account = $branch_data["payment_discount_account"];

	} else {
		$debtors_account = $company_record["debtors_act"];
		$discount_account = $company_record["default_prompt_payment_act"];
	}

	if (($discount + $amount) != 0)	{
	/* Now Credit Debtors account with receipts + discounts */
	$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
		$debtors_account, $dim1, $dim2, -($discount + $amount), $customer_id,
		"Cannot insert a GL transaction for the debtors account credit");
	}
	if ($discount != 0)	{
		/* Now Debit discount account with discounts allowed*/
		$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
			$discount_account, $dim1, $dim2, $discount, $customer_id,
			"Cannot insert a GL transaction for the payment discount debit");
	}

	if ($charge != 0)	{
		/* Now Debit bank charge account with charges */
		$charge_act = get_bank_charge_account($bank_account);
		$total += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_,	$charge_act, $dim1, $dim2, '', 
			$charge, $bank['bank_curr_code'], PT_CUSTOMER,  $customer_id);
	}


	/*Post a balance post if $total != 0 due to variance in AR and bank posted values*/
	if ($total != 0)
	{
		$variance_act = get_company_pref('exchange_diff_act');
		add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_,	$variance_act, $dim1, $dim2, '',
			-$total, null, PT_CUSTOMER,  $customer_id);
	}
	
	$target_ex_rate=$bank_amount/$amount;
	$target_currency=get_customer_currency($customer_id);
	$target_value=$amount;
	$bank_curr=get_bank_account_currency($bank_account);

	/*now enter the bank_trans entry */
	add_bank_trans(ST_CUSTPAYMENT, $payment_no, $bank_account, $ref,$date_,
		 $bank_amount - $charge, PT_CUSTOMER,$customer_id,$bank_curr,"", $rate,
		 $mode_of_payment,0,$cheque_no,$dd_no,$date_of_issue,
		 $dd_date_of_issue,$pymt_ref,0,$target_currency,$target_value,$our_ref_no,$amex); 	

	add_comments(ST_CUSTPAYMENT, $payment_no, $date_, $memo_);

	$Refs->save(ST_CUSTPAYMENT, $payment_no, $ref);

	$args->trans_no = $payment_no;
	hook_db_postwrite($args, ST_CUSTPAYMENT);
	commit_transaction();

	return $payment_no;
}

//-------------------------------------------------------------------------------------------------

function void_customer_payment($type, $type_no)
{
	begin_transaction();

	hook_db_prevoid($type, $type_no);
	void_bank_trans($type, $type_no, true);
	void_gl_trans($type, $type_no, true);
	void_cust_allocations($type, $type_no);
	void_customer_trans($type, $type_no);

	commit_transaction();
}

/*
	Retrieve bank charge amount from GL postings for customer payment.
	. Bank charge is not stored explicitly in database as of 2.3.xx
	. Due to roundings the retrieved charge can differ from the original amount when bank_curr!=home_curr && bank_curr!=cust_curr
*/
function get_cust_bank_charge($type, $trans_no)
{

	// restore charge amount from amounts in bank currency if possible, otherwise calculate from GL posting with exchange rate used for amount posting
	$sql = "SELECT	IF(act.bank_curr_code=home_curr.value, charge.amount,
					IF(act.bank_curr_code=debtor.curr_code, -(trans.amount-ar.ov_amount+ar.ov_discount),
					IFNULL(charge.amount*trans.amount/pmt.amount, 0)))
			FROM ".TB_PREF."bank_trans trans
				LEFT JOIN ".TB_PREF."bank_accounts act ON trans.bank_act=act.id
				LEFT JOIN ".TB_PREF."sys_prefs charge_act ON charge_act.name='bank_charge_act'
				LEFT JOIN ".TB_PREF."sys_prefs home_curr ON home_curr.name='curr_default'
				LEFT JOIN ".TB_PREF."gl_trans charge ON charge.type=trans.type AND charge.type_no=trans.trans_no AND charge.account=charge_act.value
				LEFT JOIN ".TB_PREF."gl_trans pmt ON pmt.type=trans.type AND pmt.type_no=trans.trans_no AND pmt.account=act.account_code
				LEFT JOIN ".TB_PREF."debtors_master debtor ON trans.person_id=debtor.debtor_no AND trans.person_type_id=".PT_CUSTOMER."
				LEFT JOIN ".TB_PREF."debtor_trans ar ON trans.type=ar.type AND trans.trans_no=ar.trans_no
			WHERE pmt.amount!=0 AND charge.amount!=0 AND trans.amount!=0
				AND trans.type=".db_escape($type)." AND trans.trans_no=".db_escape($trans_no);

	$result = db_query($sql, "cannot retrieve bank charge");

	if (!db_num_rows($result))
		return 0;

	$myrow = db_fetch($result);
	return $myrow['0'];
}


//----------------------------------------------------------------------------
 //Customer Advance Payment Entry With VAT
function write_customer_advance_payment($trans_no, $customer_id, $branch_id, $bank_account,$date_, $ref, $amount, $discount, $memo_, $rate=0, $charge=0, $bank_amount=0,
$mode_of_payment='',$cheque_no='',$dd_no='',$date_of_issue='',$dd_date_of_issue='',$pymt_ref=0,$our_ref_no="",$taxable_amount=0,$tax_amount=0,$vat_act='',$tax_percent=0,$dim1=0, $dim2=0,$amex=0,$order_=0)
{

	global $Refs;

	begin_transaction();
	$args = func_get_args(); while (count($args) < 14) $args[] = 0;
	
	$args = (object)array_combine(array('trans_no', 'customer_id', 'branch_id', 'bank_account', 
		'date_', 'ref', 'amount', 'discount', 'memo_','rate','charge', 'bank_amount', 'mode_of_payment','cheque_no','dd_no','date_of_issue','dd_date_of_issue',
		'pymt_ref','our_ref_no','taxable_amount','tax_amount',
		'vat_act','tax_percent','dim1','dim2','amex','order_'), $args);
	
	
	hook_db_prewrite($args, ST_CUSTPAYMENT);

	$company_record = get_company_prefs();

	if ($trans_no != 0) {
	  	delete_comments(ST_CUSTPAYMENT, $trans_no);
	  	void_bank_trans(ST_CUSTPAYMENT, $trans_no, true);
	  	void_gl_trans(ST_CUSTPAYMENT, $trans_no, true);
	  	void_cust_allocations(ST_CUSTPAYMENT, $trans_no, $date_);
	}

	$bank = get_bank_account($bank_account);
	


	if (!$bank_amount) 	// backward compatibility workaround
	{
	
		if(!$rate)
			$rate = get_exchange_rate_from_to(get_customer_currency($customer_id),
				$bank['bank_curr_code'], $date_ );

		$bank_amount = $amount/$rate;
	}
	
			
	
	$flag=0; //if customer paid in other currency
	if($bank_amount!=0 && $rate==0)
	{
	    $rate = $amount/$bank_amount;
		$flag=1;
	}
	

	// do not use $rate here: global rate stored in exrate table is always used
	$payment_no = write_customer_trans(ST_CUSTPAYMENT, $trans_no, $customer_id,$branch_id,
   $date_, $ref, $taxable_amount, $discount,$tax_amount, 0, 0, 
   0, $order_, 0, "",0,$rate, $dim1, $dim2, null,0, 0,$our_ref_no,'',0,$vat_act,$tax_percent,1);
	
	$bank_gl_account = get_bank_gl_account($bank_account);

	$total = 0;
	

	/* Bank account entry first */
	$total += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_,
		$bank_gl_account, $dim1, $dim2, '', ($bank_amount - $charge),  $bank['bank_curr_code'], PT_CUSTOMER, $customer_id,"",$bank['bank_curr_code']==get_company_currency()?1:$rate);

	if ($branch_id != ANY_NUMERIC) {

		$branch_data = get_branch_accounts($branch_id);

		$debtors_account = $branch_data["receivables_account"];
		$discount_account = $branch_data["payment_discount_account"];

	} else {
		$debtors_account = $company_record["debtors_act"];
		$discount_account = $company_record["default_prompt_payment_act"];
	}

	if (($discount + $amount) != 0)	{
	/* Now Credit Debtors account with receipts + discounts */
	$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
		$debtors_account, $dim1, $dim2, -($discount + $amount), $customer_id,
		"Cannot insert a GL transaction for the debtors account credit",$flag==1?0:$rate); 
	}
	if ($discount != 0)	{
		/* Now Debit discount account with discounts allowed*/
		$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
			$discount_account, $dim1, $dim2, $discount, $customer_id,
			"Cannot insert a GL transaction for the payment discount debit",$flag==1?0:$rate);
	}

	if ($charge != 0)	{
		/* Now Debit bank charge account with charges */
		$charge_act = get_bank_charge_account($bank_account);
		$total += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_,	$charge_act, $dim1, $dim2, '', 
			$charge, $bank['bank_curr_code'], PT_CUSTOMER,  $customer_id,"",$bank['bank_curr_code']==get_company_currency()?1:$rate); 
	}
	
	
	if($tax_amount!=0)
	{
	   $vat_advance_act = get_company_pref('sales_vat_advance');
		$total += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_,	$vat_advance_act, $dim1, $dim2, '', 
			$tax_amount, $bank['bank_curr_code'], PT_CUSTOMER,  $customer_id,"",$bank['bank_curr_code']==get_company_currency()?1:$rate); 
			
		$total += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_,	$vat_act, $dim1, $dim2, '', 
			-$tax_amount, $bank['bank_curr_code'], PT_CUSTOMER,  $customer_id,"",$bank['bank_curr_code']==get_company_currency()?1:$rate); 
		
	}
	
	if ($charge != 0)	{
		/* Now Debit bank charge account with charges */
		$charge_act = get_bank_charge_account($bank_account);
		$total += add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_,	$charge_act, $dim1, $dim2, '', 
			$charge, $bank['bank_curr_code'], PT_CUSTOMER,  $customer_id,"",$bank['bank_curr_code']==get_company_currency()?1:$rate); 
	}


	/*Post a balance post if $total != 0 due to variance in AR and bank posted values*/
	if ($total != 0)
	{
		$variance_act = get_company_pref('exchange_diff_act');
		add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_,	$variance_act, $dim1, $dim2, '',
			-$total, null, PT_CUSTOMER,  $customer_id,"",$rate); 
	}
	
	$target_ex_rate=$bank_amount/$amount;
	$target_currency=get_customer_currency($customer_id);
	$target_value=$amount;
	$bank_curr=get_bank_account_currency($bank_account);
		
	/*now enter the bank_trans entry */
	add_bank_trans(ST_CUSTPAYMENT, $payment_no, $bank_account, $ref,$date_,
		 $bank_amount - $charge, PT_CUSTOMER,$customer_id,$bank_curr,"", $rate,$mode_of_payment,0,$cheque_no,$dd_no,$date_of_issue,
		 $dd_date_of_issue,$pymt_ref,0,$target_currency,$target_value,$our_ref_no,$amex); 

	add_comments(ST_CUSTPAYMENT, $payment_no, $date_, $memo_);

	$Refs->save(ST_CUSTPAYMENT, $payment_no, $ref);

	$args->trans_no = $payment_no;
	hook_db_postwrite($args, ST_CUSTPAYMENT);
	commit_transaction();

	return $payment_no;
}

function update_recall_info_against_pdc($payment_no, $recall_date, $pdc_number,$customer_id)
{
	
	$pdc_amount = get_pdc_amount($pdc_number);
	
	$cus_pdcsql = "SELECT * FROM ".TB_PREF."cust_pdc_allocations WHERE trans_type_from='".ST_CUSTPDC."' AND trans_no_from = ".db_escape($pdc_number)." AND person_id=".db_escape($customer_id)."";
	$cust_pdcres = db_query($cus_pdcsql);
	$cust_pdc_results = db_fetch($cust_pdcres);
	
	
	$dsql = "UPDATE ".TB_PREF."debtor_trans SET recall_status='1',current_pdc_status='1', recall_date=".db_escape($recall_date).", recall_remarks='$payment_no' ,ov_amount = '0',alloc='0'
	WHERE type='".ST_CUSTPDC."' AND trans_no=".db_escape($pdc_number)." AND debtor_no=".db_escape($customer_id)."";
	$dres = db_query($dsql);
	
	// ## updated recall statu for debtor_trans total pdc_amount ## Ramesh 15/8/2024
	$rpsql = "UPDATE ".TB_PREF."cust_pdc_allocations SET recall_status='1'
	WHERE trans_type_from='".ST_CUSTPDC."' AND trans_no_from=".db_escape($pdc_number)." AND person_id=".db_escape($customer_id)."";
	$rpres = db_query($rpsql);
	
	$debtsql = "UPDATE ".TB_PREF."debtor_trans SET 
	        pdc_amt = pdc_amt - ".db_escape($cust_pdc_results['amt'])."
			WHERE trans_no = ".db_escape($cust_pdc_results['trans_no_to'])." 
			AND type = ".db_escape($cust_pdc_results['trans_type_to'])." 
			AND debtor_no=".db_escape($customer_id)." ";
	
			db_query($debtsql);	
}

function get_pdc_amount_against_invoice($pdc_number,$trans_no_to)
{
	$cus_pdcsql = "SELECT amt FROM ".TB_PREF."cust_pdc_allocations WHERE trans_type_from='".ST_CUSTPDC."' AND trans_no_from = ".db_escape($pdc_number)." AND trans_no_to=".db_escape($trans_no_to)."";
	$cust_pdcres = db_query($cus_pdcsql);
	$cust_pdc_results = db_fetch_row($cust_pdcres);
	return $cust_pdc_results[0];
}

function get_pdc_amount_of_invoice($trans_type,$trans_no)
{
	$cus_pdcsql = "SELECT pdc_amt FROM ".TB_PREF."debtor_trans WHERE type=".db_escape($trans_type)." AND trans_no = ".db_escape($trans_no)." ";
	$cust_pdcres = db_query($cus_pdcsql);
	$cust_pdc_results = db_fetch_row($cust_pdcres);
	return $cust_pdc_results[0];
}


function check_exists_cash_sales_person_ref_no($sales_person_ref, $ref_no = null)
{
	 $sql="SELECT COUNT(*) FROM ".TB_PREF."debtor_trans 
	 WHERE type=12
	 AND bank_account='1'
	 AND sales_person_ref=".db_escape($sales_person_ref);

	 if ($ref_no > 0) {
		$sql .= " AND ref_no != ".db_escape($ref_no);
	 }
	 
	 $result = db_query($sql, "could not do check empty query");	
	 $myrow = db_fetch_row($result);
	 return ($myrow[0] > 0)?1:0; 
}

function check_exists_credit_sales_person_ref_no($sales_person_ref, $ref_no = null)
{
	 $sql="SELECT COUNT(*) FROM ".TB_PREF."debtor_trans 
	 WHERE type=12
	 AND bank_account!='1'
	 AND sales_person_ref=".db_escape($sales_person_ref);

	if ($ref_no > 0) {
		$sql .= " AND ref_no != ".db_escape($ref_no);
	}
	 
	 $result = db_query($sql, "could not do check empty query");	
	 $myrow = db_fetch_row($result);
	 return ($myrow[0] > 0)?1:0; 
}


function get_sales_person_abrev($sales_person_id)
{


   $sql="SELECT salesman_abrev FROM ".TB_PREF."salesman 
   WHERE salesman_code= ".db_escape($sales_person_id)."";
   $result = db_query($sql, "No standard cost transactions were returned");
   if($row=db_fetch_row($result))
   {
      return $row[0];
   }
   else
   return "Not Found!";
}


function get_last_sales_person_payment_cash_ref($sales_person_id)
{

   $salesman_abrev = get_sales_person_abrev($sales_person_id);

   $sql="SELECT sales_person_ref FROM ".TB_PREF."debtor_trans 
   WHERE type=12
   AND bank_account='1'
   AND sales_person_id= ".db_escape($sales_person_id)."  
   ORDER BY trans_no desc";
   $result = db_query($sql, "No standard cost transactions were returned");
   if($row=db_fetch_row($result))
   {
      return $row[0];
   }
   else
   return $salesman_abrev;
}


function get_last_sales_person_payment_cheque_ref($sales_person_id)
{

   $salesman_abrev = get_sales_person_abrev($sales_person_id);

   $sql="SELECT sales_person_ref FROM ".TB_PREF."debtor_trans 
   WHERE type=12
   AND bank_account!='1'
   AND sales_person_id= ".db_escape($sales_person_id)."  
   ORDER BY trans_no desc";
   
   $result = db_query($sql, "No standard cost transactions were returned");
   if($row=db_fetch_row($result))
   {
      return $row[0];
   }
   else
   return $salesman_abrev;
}


function get_cash_sales_payemnt_reference_by_sales_person_ref($sales_person_ref)
{


   $sql="SELECT reference FROM ".TB_PREF."debtor_trans 
   WHERE type=12
   AND bank_account='1'
   AND sales_person_ref= ".db_escape($sales_person_ref)."";
   $result = db_query($sql, "No standard cost transactions were returned");
   if($row=db_fetch_row($result))
   {
      return $row[0];
   }
   else
   return "Not Found!";
}


function get_credit_sales_payemnt_reference_by_sales_person_ref($sales_person_ref)
{
   $sql="SELECT reference FROM ".TB_PREF."debtor_trans 
   WHERE type=12
   AND bank_account!='1'
   AND sales_person_ref= ".db_escape($sales_person_ref)."";
   
   $result = db_query($sql, "No standard cost transactions were returned");
   if($row=db_fetch_row($result))
   {
      return $row[0];
   }
   else
   return "Not Found!";
}


function check_exists_sales_person_ref_no($sales_person_ref, $ref_no = null)
{
	 $sql="SELECT COUNT(*) FROM ".TB_PREF."debtor_trans 
	 WHERE type=12
	 AND sales_person_ref=".db_escape($sales_person_ref);
	 if ($ref_no > 0) {
		$sql .= " AND ref_no != ".db_escape($ref_no);
	 }
	 $result = db_query($sql, "could not do check empty query");	
	 $myrow = db_fetch_row($result);
	 return ($myrow[0] > 0)?1:0; 
}

function get_salesman_person_ref_of_payment($sales_person_ref)
{


   $sql="SELECT reference FROM ".TB_PREF."debtor_trans 
   WHERE type=12
   AND sales_person_ref= ".db_escape($sales_person_ref)."";
   $result = db_query($sql, "No standard cost transactions were returned");
   if($row=db_fetch_row($result))
   {
      return $row[0];
   }
   else
   return "Not Found!";
}