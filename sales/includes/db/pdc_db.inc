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
function write_customer_pdc(
	$trans_no, $customer_id, $branch_id, $bank_account, $date_,
	$ref, $amount, $discount, $memo_, $rate=0,
	$charge=0, $bank_amount=0, $dim1=0, $dim2=0, $our_ref_no="",
	$pdc_cheque_no="" ,$pdc_cheque_date="", $bank_name='', $line_items,$sales_person_ref="")
{
	global $Refs;
	
	begin_transaction();
	$args = func_get_args(); while (count($args) < 14) $args[] = 0;
	
	$args = (object)array_combine(array('trans_no', 'customer_id', 'branch_id', 'bank_account', 'date_', 'ref', 'amount', 'discount', 'memo_','rate','charge', 'bank_amount', 'dim1', 'dim2','our_ref_no','pdc_cheque_no','pdc_cheque_date','bank_name', 'line_items','sales_person_ref'), $args);
	hook_db_prewrite($args, ST_CUSTPDC);
	
	$company_record = get_company_prefs();	

	if ($trans_no != 0) {
	  	delete_comments(ST_CUSTPDC, $trans_no);
	  	void_bank_trans(ST_CUSTPDC, $trans_no, true);
	  	void_gl_trans(ST_CUSTPDC, $trans_no, true);
	  	void_cust_allocations(ST_CUSTPDC, $trans_no, $date_);
	}

	$bank = get_bank_account($bank_account);
	
	if ($trans_no < 1) {
		$trans_no = get_next_trans_no(ST_CUSTPDC);
	}
	
	foreach ($line_items as $idx => $line_item) {
		
		if (!$bank_amount) 	// backward compatibility workaround
		{
			if(!$rate)
			{
				$rate = get_exchange_rate_from_to(get_customer_currency($line_item['customer_id']),$bank['bank_curr_code'], $date_ );
			}
			$bank_amount = $amount/$rate;
		}
		
		
		$flag=0; //if customer paid in other currency
		if($bank_amount!=0 && $rate==0)
		{
			$rate = $amount/$bank_amount;
			$flag=1;
		}
		
		$payment_no = write_multiple_customer_trans(
			ST_CUSTPDC, $trans_no, $line_item['customer_id'],
			$line_item['branch_id'], $date_, $ref,
			$line_item['amount'], $discount, 0, 0,
			0, 0, 0, 0,
			"", 0, 0, $dim1,
			$dim2, "", 0, 0,
			$our_ref_no, $bank_name, $bank_account, "",
			0, 0, 0, 0,
			0, 0, $pdc_cheque_no, $pdc_cheque_date,null,"",$sales_person_ref,"",null,$line_item['invoice_no']=="0"?'0':'1');

	}
	
	// do not use $rate here: global rate stored in exrate table is always used
	
	
	add_comments(ST_CUSTPDC, $trans_no, $date_, $memo_);

	//add_comments(ST_CUSTPDC, $payment_no, $date_, $memo_.'PDC No:'.$pdc_cheque_no.' PDC DATE:'.$pdc_cheque_date);


	// $Refs->save(ST_CUSTPDC, $trans_no, $ref);

	$args->trans_no = $trans_no;
	hook_db_postwrite($args, ST_CUSTPDC);
	commit_transaction();

	return $trans_no;
}

//-------------------------------------------------------------------------------------------------

function void_customer_pdc($type, $type_no)
{
	begin_transaction();

	hook_db_prevoid($type, $type_no);
	void_bank_trans($type, $type_no, true); // need to check 
	void_gl_trans($type, $type_no, true);  // need to check 
	void_cust_allocations($type, $type_no);
	void_pdc_customer_trans($type, $type_no);

	commit_transaction();
}


function get_pdc_amount($type_no){

$sql = "SELECT ov_amount FROM ".TB_PREF."debtor_trans WHERE trans_no=".db_escape($type_no)." AND type='".ST_CUSTPDC."'";

$res = db_query($sql);
$result = db_fetch_row($res);
return $result['0'];


}
