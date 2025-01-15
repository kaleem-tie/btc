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
function write_supp_pdc($trans_no, $supplier_id, $bank_account,
	$date_, $ref, $supp_amount, $supp_discount, $memo_, $rate=0,$bank_charge=0, $bank_amount=0, 
	$dimension=0, $dimension2=0,$our_ref_no="", $pdc_cheque_no="" ,$pdc_cheque_date="", $bank_name) //ravi
{
	global $Refs;
	
	

	begin_transaction();
	$args = func_get_args(); while (count($args) < 12) $args[] = 0;
	$args = (object)array_combine(array('trans_no', 'supplier_id', 'bank_account', 'date_',
		 'ref', 'supp_amount', 'supp_discount', 'memo_', 'rate','bank_charge', 'bank_amount', 'dimension', 'dimension2','our_ref_no','pdc_cheque_no','pdc_cheque_date','bank_name'), $args);
	$args->trans_no = 0;
	hook_db_prewrite( $args, ST_SUPPPDC);

	if ($trans_no != 0) {
	  delete_comments(ST_SUPPPDC, $trans_no);
	  void_bank_trans(ST_SUPPPDC, $trans_no, true);
	  void_gl_trans(ST_SUPPPDC, $trans_no, true);
	  void_supp_allocations(ST_SUPPPDC, $trans_no, $date_);
	}
    $company_record = get_company_prefs();

	$bank = get_bank_account($bank_account);

	if (!$bank_amount) 	// auto 
	{
		if(!$rate)  //ravi
		$rate = get_exchange_rate_from_to(get_supplier_currency($supplier_id),
			$bank['bank_curr_code'], $date_ );   
		$bank_amount = $supp_amount*$rate;//ravi
	}
	
	
	
	
	/* Create a supp_trans entry for the supplier payment */
	$payment_id = write_supp_trans(ST_SUPPPDC, 0, $supplier_id, $date_, $date_,$ref, 
		"", -$supp_amount, 0, -$supp_discount, "", $rate,0,
		0, 0, 0, 0, 0, 0, 0, 0, 0,
		$our_ref_no, $pdc_cheque_no, $pdc_cheque_date, $bank_name, $bank_account);
		
	

	add_comments(ST_SUPPPDC, $payment_id, $date_, $memo_);

	$Refs->save(ST_SUPPPDC, $payment_id, $ref);

	$args->trans_no = $payment_id;
	hook_db_postwrite($args, ST_SUPPPDC);

	commit_transaction();

	return $payment_id;
}

//------------------------------------------------------------------------------------------------

function get_supp_pdc_amount($type_no){

$sql = "SELECT ov_amount FROM ".TB_PREF."supp_trans WHERE trans_no=".db_escape($type_no)." AND type='".ST_SUPPPDC."'";
$res = db_query($sql);
$result = db_fetch_row($res);
return $result['0'];
}

function check_exists_supplier_pdc_cheque_no($pdc_cheque_no)
{
	 $sql="SELECT COUNT(*) FROM ".TB_PREF."supp_trans 
	 WHERE type=6
	 AND pdc_cheque_no=".db_escape($pdc_cheque_no);
	 $result = db_query($sql, "could not do check empty query");	
	 $myrow = db_fetch_row($result);
	 return ($myrow[0] > 0)?1:0; 
}

function get_exists_supplier_pdc_cheque_no($pdc_cheque_no)
{

   $sql="SELECT reference FROM ".TB_PREF."supp_trans 
   WHERE type=6
   AND pdc_cheque_no= ".db_escape($pdc_cheque_no)."";
   $result = db_query($sql, "No customer pdc transactions were returned");
   if($row=db_fetch_row($result))
   {
      return $row[0];
   }
   else
   return "Not Found!";
}
