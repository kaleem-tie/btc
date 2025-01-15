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

require_once($path_to_root. "/admin/db/fiscalyears_db.inc");
require_once($path_to_root. "/sales/includes/db/payment_db.inc");



function add_salesman_collection_entry($line_items,$bank_account,$date_,$sales_person_id=0,
  $sales_person_ref="",$memo_="",$ref_no=null,$pdc_number = null, $ref = null)
{
	global $SysPrefs, $path_to_root, $Refs;
	begin_transaction();
	$args = func_get_args();
	array_pop($args);
	$args = (object)array_combine(array('line_items','bank_account', 'date_', 'sales_person_id','sales_person_ref','memo_', 'ref_no','pdc_number'), $args);
	$args->trans_no = 0;
	hook_db_prewrite($args, ST_CUSTPAYMENT);
    $tran_date = date2sql($date_); 	 	
 	
	$trans_type=ST_CUSTPAYMENT;
	if($ref_no==0)
	{
		$ref_no = get_next_ref_no(ST_CUSTPAYMENT);
	}
	foreach ($line_items as $line_item)
	{
		if ($pdc_number != null) {
			$line_item->trans_no = 0;
		}
		// If Condition Added in kadar ali On 15 May 2024
		if($line_item->trans_no==0 || $line_item->trans_no==null){
	    	$trans_no = get_next_trans_no(ST_CUSTPAYMENT);
		}
		else{
			$trans_no = $line_item->trans_no;
		}
		
		
		/* $ref = $Refs->get_next(
			ST_CUSTPAYMENT, null,
			array('customer' => get_post('debtor_no'), 'date' => get_post('date_'))
		); */
		
		
		$payment_no = write_customer_payment(
			$trans_no, $line_item->customer_id, $line_item->branch_id,
			$bank_account, $date_, $ref,
			$line_item->amount, 0, $memo_,
			0, 0, $line_item->amount,
			0, 0,'cash',
			"","","",
			"","","",
			0, $sales_person_id,$sales_person_ref,$ref_no
		);	
		
		if($line_item->invoice_no!=0){
		$invoice_info =get_invoice_information($line_item->customer_id,$line_item->invoice_no);
		
		//update_debtors_trans($invoice_info['trans_no'],$line_item->amount);
		add_cust_allocations($line_item->customer_id,$line_item->amount,$date_,$payment_no,
		ST_CUSTPAYMENT,$invoice_info['trans_no'],ST_SALESINVOICE);
		
		 update_debtor_trans_allocation(ST_SALESINVOICE, $invoice_info['trans_no'], $line_item->customer_id);
		 update_debtor_trans_allocation(ST_CUSTPAYMENT, $payment_no, $line_item->customer_id);

		}
		
		if($pdc_number>0)
		{
			update_recall_info_against_pdc($payment_no,$tran_date,$pdc_number,$line_item->customer_id);
		}
		
	} 


	commit_transaction();

	return array('trans_no' => $payment_no, 'ref_no' => $ref_no);
}


function get_sales_invoice_number($invoice_ref)
{
	
//	$sql = "SELECT ((ov_amount + ov_gst + ov_freight + ov_freight_tax + ov_discount) - (alloc +pdc_amt)) as total FROM ".TB_PREF."debtor_trans WHERE reference=".db_escape($invoice_ref)." AND type=".ST_SALESINVOICE."";
	$sql = "SELECT ((sum(ov_amount) + sum(ov_gst) + sum(ov_freight) + sum(ov_freight_tax) + sum(ov_discount)) - (sum(alloc) +sum(pdc_amt))) as total FROM ".TB_PREF."debtor_trans WHERE reference=".db_escape($invoice_ref)." AND type=".ST_SALESINVOICE."";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result[0];
}

function get_sales_invoice_trans_number($invoice_ref, $trans_type, $trans_no = null, $debtor_no = null)
{
	if($trans_no>0)
	{
		if($trans_type!=ST_CUSTPDC)
		{
			$sql = "SELECT ((sum(a.ov_amount) + sum(a.ov_gst) + sum(a.ov_freight) + sum(a.ov_freight_tax) + sum(a.ov_discount)) - (sum(a.alloc) + sum(a.pdc_amt))+IFNULL(x.trans_amount,0)) as total 
			FROM ".TB_PREF."debtor_trans a
			LEFT OUTER JOIN (select c.trans_no_to AS trans_no, c.trans_type_to AS type, IFNULL((sum(b.ov_amount) + sum(b.ov_gst) + sum(b.ov_freight) + sum(b.ov_freight_tax) + sum(b.ov_discount)),0) AS trans_amount from ".TB_PREF."debtor_trans b 
			INNER JOIN ".TB_PREF."cust_allocations c ON b.type = c.trans_type_from AND b.trans_no =	c.trans_no_from
			WHERE b.trans_no = ".db_escape($trans_no)." and b.type = ".db_escape($trans_type)."  ) x
						ON a.type = x.type and a.trans_no = x.trans_no
			WHERE a.reference=".db_escape($invoice_ref)." AND a.type=".ST_SALESINVOICE."";
		}
		else
		{
			$sql = "SELECT ((sum(a.ov_amount) + sum(a.ov_gst) + sum(a.ov_freight) + sum(a.ov_freight_tax) + 
				sum(a.ov_discount)) - (sum(a.alloc) + sum(a.pdc_amt))+IFNULL(x.trans_amount,0)) as total 
			FROM ".TB_PREF."debtor_trans a
			LEFT OUTER JOIN (select c.trans_no_to AS trans_no, c.trans_type_to AS type, IFNULL((sum(b.ov_amount) + sum(b.ov_gst) + sum(b.ov_freight) + sum(b.ov_freight_tax) + sum(b.ov_discount)),0) AS trans_amount from ".TB_PREF."debtor_trans b 
			INNER JOIN ".TB_PREF."cust_pdc_allocations c ON b.type = c.trans_type_from AND b.trans_no =	c.trans_no_from AND b.debtor_no = c.person_id
			WHERE b.trans_no = ".db_escape($trans_no)." and b.type = ".db_escape($trans_type)." AND b.debtor_no = ".db_escape($debtor_no)." ) x
						ON a.type = x.type and a.trans_no = x.trans_no
			WHERE a.reference=".db_escape($invoice_ref)." AND a.type=".ST_SALESINVOICE." AND a.debtor_no = ".db_escape($debtor_no)."";
		}

		
	}
	else
	{
	$sql = "SELECT ((sum(ov_amount) + sum(ov_gst) + sum(ov_freight) + sum(ov_freight_tax) + sum(ov_discount)) - (sum(alloc) + sum(pdc_amt))) as total 
		FROM ".TB_PREF."debtor_trans WHERE reference=".db_escape($invoice_ref)." AND type=".ST_SALESINVOICE."";
	}
	
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result[0];
}

function get_sales_invoice_amt($invoice_ref)
{
	
	$sql = "SELECT (sum(ov_amount) + sum(ov_gst) + sum(ov_freight) + sum(ov_freight_tax) + sum(ov_discount)) as total FROM ".TB_PREF."debtor_trans WHERE reference=".db_escape($invoice_ref)." AND type=".ST_SALESINVOICE."";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result[0];
}

function get_last_pdc_ref_no_for_allocation($trans_type)
{

   $sql="SELECT sales_person_ref FROM ".TB_PREF."debtor_trans 
   WHERE type=5 ORDER BY trans_no desc";
   $result = db_query($sql, "No standard cost transactions were returned");
   if($row=db_fetch_row($result))
   {
      return $row[0];
   }
   else
   return "Not Found";
}

function check_exists_pdc_ref_no_for_allocation($sales_person_ref, $ref_no = null)
{
	 $sql="SELECT COUNT(*) FROM ".TB_PREF."debtor_trans 
	 WHERE type=5
	 AND sales_person_ref=".db_escape($sales_person_ref);
	 $result = db_query($sql, "could not do check empty query");	
	 $myrow = db_fetch_row($result);
	 return ($myrow[0] > 0)?1:0; 
}

function get_pdc_ref_no_for_allocation($sales_person_ref)
{

   $sql="SELECT reference FROM ".TB_PREF."debtor_trans 
   WHERE type=5
   AND sales_person_ref= ".db_escape($sales_person_ref)."";
   $result = db_query($sql, "No standard cost transactions were returned");
   if($row=db_fetch_row($result))
   {
      return $row[0];
   }
   else
   return "Not Found!";
}


function check_exists_customer_pdc_cheque_no($pdc_cheque_no)
{
	 $sql="SELECT COUNT(*) FROM ".TB_PREF."debtor_trans 
	 WHERE type=5
	 AND pdc_cheque_no=".db_escape($pdc_cheque_no);
	 $result = db_query($sql, "could not do check empty query");	
	 $myrow = db_fetch_row($result);
	 return ($myrow[0] > 0)?1:0; 
}

function get_exists_customer_pdc_cheque_no($pdc_cheque_no)
{

   $sql="SELECT reference FROM ".TB_PREF."debtor_trans 
   WHERE type=5
   AND pdc_cheque_no= ".db_escape($pdc_cheque_no)."";
   $result = db_query($sql, "No customer pdc transactions were returned");
   if($row=db_fetch_row($result))
   {
      return $row[0];
   }
   else
   return "Not Found!";
}