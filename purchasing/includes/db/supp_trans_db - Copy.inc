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
//-------------------------------------------------------------------------------------------------------------
//
//	FIXME - this should be revised for transaction update case.
//
function write_supp_trans($type, $trans_no, $supplier_id, $date_, $due_date, $reference, 
$supp_reference,
	$amount, $amount_tax, $discount, $err_msg="", $rate=0, $included=0,
	$freight_cost=0, $additional_charges=0, $packing_charges=0, $other_charges=0,
	$freight_tax=0,$additional_tax=0,$packing_tax=0,$other_tax=0,$ov_roundoff=0,
	$our_ref_no='',$pdc_cheque_no='',$pdc_cheque_date='',$bank_name='',$bank_account=0,
	$rc_supplier_id='',$rc_supplier_invoice_id='',$rc_invoice_date=null,$rc_invoice_amount=0,
	$reverse_charge=0,$rc_bill_no='',$rc_custom_total=0,$rc_curr_code='',$bill_date="",
	$declaration_no='',$cif_value=0,$vat_import_value=0,$custom_duties=0,
	$container_number='',$bl_no='',$purch_inco_terms=0,$purch_type=0)
{

    

	//$new = $trans_no ==0;
	
	$new = $trans_no > 0 ? null : true;
	
	$date = date2sql($date_);
	if ($due_date == "")
		$due_date = "0000-00-00";
	else
		$due_date = date2sql($due_date);
		
	$pdc_cheque_date = date2sql($pdc_cheque_date);	
	$bill_date       = date2sql($bill_date);
	
	//if ($new)
		//$trans_no = get_next_trans_no($type);
		$curr = get_supplier_currency($supplier_id);
	
	if ($rate == 0)
		$rate = get_exchange_rate_from_home_currency($curr, $date_);
	//ramesh for bankpayments not showing in supplier transaction inquiry
	if($trans_type ==ST_BANKPAYMENT ||  $trans_type ==ST_BANKDEPOSIT)
	{
		
	  $new = $trans_no > 0 ? true : null;
		if($new)
		{
		    if ($new && ($trans_type!=ST_BANKPAYMENT ||  $trans_type!=ST_BANKDEPOSIT)){
			$trans_no = get_next_trans_no($trans_type);
		    }
			$sql = "INSERT INTO ".TB_PREF."supp_trans (trans_no, type, supplier_id, tran_date, due_date,
			reference, supp_reference, ov_amount, ov_gst, rate, ov_discount, tax_included,freight_cost,additional_charges,packing_charges,other_charges,
			freight_tax,additional_tax,packing_tax,other_tax,ov_roundoff,our_ref_no,
			pdc_cheque_no,pdc_cheque_date,bank_name,bank_account,
			rc_supplier_id,rc_supplier_invoice_id,rc_invoice_date,rc_invoice_amount,
			reverse_charge,rc_bill_no,rc_custom_total,rc_curr_code,bill_date,
			declaration_no,cif_value,vat_import_value,custom_duties,container_number,bl_no,purch_inco_terms,purch_type) ";
			$sql .= "VALUES (".db_escape($trans_no).", ".db_escape($type).", ".db_escape($supplier_id).", '$date', '$due_date',
			".db_escape($reference).", ".db_escape($supp_reference).", ".db_escape($amount)
			.", ".db_escape($amount_tax).", ".db_escape($rate).", ".db_escape($discount).", ".db_escape($included).",
			".db_escape($freight_cost).", ".db_escape($additional_charges).", ".db_escape($packing_charges).", ".db_escape($other_charges).", 
			".db_escape($freight_tax).", ".db_escape($additional_tax).", ".db_escape($packing_tax).", ".db_escape($other_tax).",
			".db_escape($ov_roundoff).",".db_escape($our_ref_no).",".db_escape($pdc_cheque_no).",".db_escape($pdc_cheque_date).",
			".db_escape($bank_name).",".db_escape($bank_account).",	".db_escape($rc_supplier_id).",
			".db_escape($rc_supplier_invoice_id).",".db_escape($rc_invoice_date).", ".db_escape($rc_invoice_amount).",
			".db_escape($reverse_charge).",".db_escape($rc_bill_no).",
			".db_escape($rc_custom_total).",".db_escape($rc_curr_code).",".db_escape($bill_date).",
			".db_escape($declaration_no).",".db_escape($cif_value).",
			".db_escape($vat_import_value).",".db_escape($custom_duties).",
			".db_escape($container_number).",".db_escape($bl_no).",".db_escape($purch_inco_terms).",".db_escape($purch_type).")";
		}
		else
		{
			$sql = "UPDATE ".TB_PREF."supp_trans set supplier_id = ".db_escape($supplier_id).", tran_date = '$date',
			due_date = '$due_date',	reference = ".db_escape($reference).", supp_reference = ".db_escape($supp_reference).", 
			ov_amount = ".db_escape($amount).", ov_gst = ".db_escape($amount_tax).", rate = ".db_escape($rate).", 
			ov_discount = ".db_escape($discount).", tax_included = ".db_escape($included).",freight_cost = ".db_escape($freight_cost).",
			additional_charges = ".db_escape($additional_charges).",packing_charges = ".db_escape($packing_charges).",other_charges = ".db_escape($other_charges).",
			freight_tax = ".db_escape($freight_tax).",additional_tax = ".db_escape($additional_tax).",packing_tax =  ".db_escape($packing_tax).",
			other_tax = ".db_escape($other_tax).",ov_roundoff = ".db_escape($ov_roundoff).",our_ref_no = ".db_escape($our_ref_no).",
			pdc_cheque_no = ".db_escape($pdc_cheque_no).",pdc_cheque_date = ".db_escape($pdc_cheque_date).",bank_name = ".db_escape($bank_name).",
			bank_account = ".db_escape($bank_account).", rc_supplier_id = ".db_escape($rc_supplier_id).",
			rc_supplier_invoice_id = ".db_escape($rc_supplier_invoice_id).",rc_invoice_date = ".db_escape($rc_invoice_date).",rc_invoice_amount = ".db_escape($rc_invoice_amount).",
			reverse_charge = ".db_escape($reverse_charge).",rc_bill_no = ".db_escape($rc_bill_no).",rc_custom_total = ".db_escape($rc_custom_total).",
			rc_curr_code = ".db_escape($rc_curr_code).",bill_date = ".db_escape($bill_date).",
			declaration_no = ".db_escape($declaration_no).",cif_value = ".db_escape($cif_value).",vat_import_value = ".db_escape($vat_import_value).",
			custom_duties = ".db_escape($custom_duties).",container_number = ".db_escape($container_number).",bl_no = ".db_escape($bl_no).",purch_inco_terms = ".db_escape($purch_inco_terms).",purch_type = ".db_escape($purch_type)."
			WHERE type = ".db_escape($type)." AND trans_no = ".db_escape($trans_no)."
			AND supplier_id = ".db_escape($supplier_id)."";
		}
	}		
	
	else {
	//end
	if($trans_no==0)
	{
		if ($new && ($trans_type!=ST_BANKPAYMENT ||  $trans_type!=ST_BANKDEPOSIT)){
			$trans_no = get_next_trans_no($type);}
	
	 	$sql = "INSERT INTO ".TB_PREF."supp_trans (trans_no, type, supplier_id, tran_date, due_date,
		reference, supp_reference, ov_amount, ov_gst, rate, ov_discount, tax_included,freight_cost,additional_charges,packing_charges,other_charges,
		freight_tax,additional_tax,packing_tax,other_tax,ov_roundoff,our_ref_no,
		pdc_cheque_no,pdc_cheque_date,bank_name,bank_account,
		rc_supplier_id,rc_supplier_invoice_id,rc_invoice_date,rc_invoice_amount,
		reverse_charge,rc_bill_no,rc_custom_total,rc_curr_code,bill_date,
		declaration_no,cif_value,vat_import_value,custom_duties,container_number,bl_no,purch_inco_terms,purch_type) ";
		$sql .= "VALUES (".db_escape($trans_no).", ".db_escape($type).", ".db_escape($supplier_id).", '$date', '$due_date',
		".db_escape($reference).", ".db_escape($supp_reference).", ".db_escape($amount)
		.", ".db_escape($amount_tax).", ".db_escape($rate).", ".db_escape($discount).", ".db_escape($included).",
		".db_escape($freight_cost).", ".db_escape($additional_charges).", ".db_escape($packing_charges).", ".db_escape($other_charges).", 
		".db_escape($freight_tax).", ".db_escape($additional_tax).", ".db_escape($packing_tax).", ".db_escape($other_tax).",
		".db_escape($ov_roundoff).",".db_escape($our_ref_no).",".db_escape($pdc_cheque_no).",".db_escape($pdc_cheque_date).",
		".db_escape($bank_name).",".db_escape($bank_account).",	".db_escape($rc_supplier_id).",
		".db_escape($rc_supplier_invoice_id).",".db_escape($rc_invoice_date).", ".db_escape($rc_invoice_amount).",
		".db_escape($reverse_charge).",".db_escape($rc_bill_no).",
		".db_escape($rc_custom_total).",".db_escape($rc_curr_code).",".db_escape($bill_date).",
		".db_escape($declaration_no).",".db_escape($cif_value).",
		".db_escape($vat_import_value).",".db_escape($custom_duties).",
		".db_escape($container_number).",".db_escape($bl_no).",".db_escape($purch_inco_terms).",".db_escape($purch_type).")";
	}
	else
	{
		$sql = "UPDATE ".TB_PREF."supp_trans set supplier_id = ".db_escape($supplier_id).", tran_date = '$date',
		due_date = '$due_date',	reference = ".db_escape($reference).", supp_reference = ".db_escape($supp_reference).", 
		ov_amount = ".db_escape($amount).", ov_gst = ".db_escape($amount_tax).", rate = ".db_escape($rate).", 
		ov_discount = ".db_escape($discount).", tax_included = ".db_escape($included).",freight_cost = ".db_escape($freight_cost).",
		additional_charges = ".db_escape($additional_charges).",packing_charges = ".db_escape($packing_charges).",other_charges = ".db_escape($other_charges).",
		freight_tax = ".db_escape($freight_tax).",additional_tax = ".db_escape($additional_tax).",packing_tax =  ".db_escape($packing_tax).",
		other_tax = ".db_escape($other_tax).",ov_roundoff = ".db_escape($ov_roundoff).",our_ref_no = ".db_escape($our_ref_no).",
		pdc_cheque_no = ".db_escape($pdc_cheque_no).",pdc_cheque_date = ".db_escape($pdc_cheque_date).",bank_name = ".db_escape($bank_name).",
		bank_account = ".db_escape($bank_account).", rc_supplier_id = ".db_escape($rc_supplier_id).",
		rc_supplier_invoice_id = ".db_escape($rc_supplier_invoice_id).",rc_invoice_date = ".db_escape($rc_invoice_date).",rc_invoice_amount = ".db_escape($rc_invoice_amount).",
		reverse_charge = ".db_escape($reverse_charge).",rc_bill_no = ".db_escape($rc_bill_no).",rc_custom_total = ".db_escape($rc_custom_total).",
		rc_curr_code = ".db_escape($rc_curr_code).",bill_date = ".db_escape($bill_date).",
		declaration_no = ".db_escape($declaration_no).",cif_value = ".db_escape($cif_value).",vat_import_value = ".db_escape($vat_import_value).",
		custom_duties = ".db_escape($custom_duties).",container_number = ".db_escape($container_number).",bl_no = ".db_escape($bl_no).",purch_inco_terms = ".db_escape($purch_inco_terms).",purch_type = ".db_escape($purch_type)."
		WHERE type = ".db_escape($type)." AND trans_no = ".db_escape($trans_no)."
		AND supplier_id = ".db_escape($supplier_id)."";
	}
	
	} // else end ## Ramesh
	
	if ($err_msg == "")
		$err_msg = "Cannot insert a supplier transaction record";
	db_query($sql, $err_msg);
	//display_error($err_msg); die;
	add_audit_trail($type, $trans_no, $date_);

	return $trans_no;
}

//-------------------------------------------------------------------------------------------------------------

function get_supp_trans($trans_no, $trans_type=-1, $supplier_id=null)
{
	$sql = "SELECT trans.*, (trans.ov_amount+trans.ov_gst+trans.ov_discount+trans.freight_cost+trans.additional_charges+trans.packing_charges+trans.other_charges + trans.freight_tax + trans.additional_tax + trans.packing_tax + trans.other_tax + trans.ov_roundoff) AS Total,
		supplier.supp_name AS supplier_name, supplier.curr_code AS curr_code ";

	if ($trans_type == ST_SUPPAYMENT || $trans_type == ST_BANKPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= ", account.id as bank_account, account.bank_name, account.bank_account_name, account.bank_curr_code,
			account.account_type AS BankTransType, bank_trans.amount AS bank_amount,
			bank_trans.ref,bank_trans.mode_of_payment as mode_of_payment,bank_trans.cheque_no as cheque_no,bank_trans.dd_no as dd_no,bank_trans.date_of_issue as date_of_issue,bank_trans.dd_date_of_issue as dd_date_of_issue,bank_trans.pymt_ref as pymt_ref,bank_trans.amex ";
	}

	$sql .= " FROM ".TB_PREF."supp_trans trans,"
			.TB_PREF."suppliers supplier";
			
	if ($trans_type == ST_SUPPAYMENT || $trans_type == ST_BANKPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= ", "
			.TB_PREF."bank_trans bank_trans,"
			.TB_PREF."bank_accounts account";
	}
	$where_condition = $supplier_id > 0 ? 'trans.supplier_id = '.$supplier_id." AND " : '';
	$sql .= " WHERE trans.trans_no=".db_escape($trans_no)." 
		AND $where_condition trans.supplier_id=supplier.supplier_id";
		//display_error($sql);
	if (isset($supplier_id))
		$sql .= " AND trans.supplier_id=".db_escape($supplier_id);

	if ($trans_type > -1)
		$sql .= " AND trans.type=".db_escape($trans_type);

	if ($trans_type == ST_SUPPAYMENT || $trans_type == ST_BANKPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= " AND bank_trans.trans_no =".db_escape($trans_no)."
			AND bank_trans.type=".db_escape($trans_type)." AND bank_trans.amount != 0 AND account.id=bank_trans.bank_act ";
	}
	
	//display_error($sql);
	$result = db_query($sql, "Cannot retreive a supplier transaction");

    if (db_num_rows($result) == 0)
    {
       // can't return nothing
       display_db_error("no supplier trans found for given params", $sql, true);
       exit;
    }
	

    if (db_num_rows($result) > 1)
    {
       // can't return multiple
       display_db_error("duplicate supplier transactions found for given params", $sql, true);
       exit;
    }
    return db_fetch($result);
}


//----------------------------------------------------------------------------------------

function get_supp_payment_before($supplier_id, $date)
{
	$sql = "SELECT "
	. TB_PREF . "supp_trans.trans_no,"
	. TB_PREF . "supp_trans.type,"
	. TB_PREF . "supp_trans.supplier_id,"
	. TB_PREF . "supp_trans.tran_date,"
	. TB_PREF . "supp_trans.ov_amount,"
	. TB_PREF . "bank_trans.ref AS bank_ref,"
	. TB_PREF . "bank_trans.amount AS bank_amount,"
	. TB_PREF . "bank_accounts.id AS bank_id,"
	. TB_PREF . "bank_accounts.bank_name,"
	. TB_PREF . "bank_accounts.bank_account_name,"
	. TB_PREF . "bank_accounts.bank_curr_code "
	. "FROM "
	. TB_PREF . "supp_trans,"
	. TB_PREF . "bank_trans,"
	. TB_PREF . "bank_accounts "
	. "WHERE "
	. TB_PREF . "supp_trans.supplier_id='" . $supplier_id . "' "
	. "AND " . TB_PREF . "supp_trans.tran_date<'" . $date . "' "
	. "AND " . TB_PREF . "supp_trans.type=" . ST_SUPPAYMENT . " "
	. "AND " . TB_PREF . "supp_trans.trans_no=" . TB_PREF . "bank_trans.trans_no "
	. "AND " . TB_PREF . "supp_trans.type=" . TB_PREF . "bank_trans.type "
	. "AND " . TB_PREF . "bank_accounts.id=" . TB_PREF . "bank_trans.bank_act "
	. "ORDER BY "
	. TB_PREF . "supp_trans.tran_date DESC "
	. "LIMIT 1 "
	;

	$result = db_query($sql, "Cannot retreive a previous supplier payment");

	if (db_num_rows($result) == 0)
	{
		return false;
	}

	return db_fetch($result);
}

//----------------------------------------------------------------------------------------

function exists_supp_trans($type, $type_no)
{
	if ($type == ST_SUPPRECEIVE)
		return exists_grn($type_no);

	$sql = "SELECT trans_no FROM ".TB_PREF."supp_trans WHERE type=".db_escape($type)."
		AND trans_no=".db_escape($type_no);
	$result = db_query($sql, "Cannot retreive a supplier transaction");

    return (db_num_rows($result) > 0);
}

//----------------------------------------------------------------------------------------

function void_supp_trans($type, $type_no)
{

	$sql = "UPDATE ".TB_PREF."supp_trans SET ov_amount=0, ov_discount=0, ov_gst=0,
	        freight_cost=0, additional_charges=0, packing_charges=0, other_charges=0, 
		   freight_tax=0, additional_tax=0, packing_tax=0, other_tax=0, ov_roundoff=0,
		alloc=0 WHERE type=".db_escape($type)." AND trans_no=".db_escape($type_no);

	db_query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");
}


function void_supp_pdc_trans($type, $type_no)
{
	$sql = "UPDATE ".TB_PREF."supp_trans SET ov_amount=0, ov_discount=0, ov_gst=0,
	       freight_cost=0, additional_charges=0, packing_charges=0, other_charges=0, 
		   freight_tax=0, additional_tax=0, packing_tax=0, other_tax=0, ov_roundoff=0,
		   alloc=0, pdc_amt =0 WHERE type=".db_escape($type)." 
		   AND trans_no=".db_escape($type_no);

	db_query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");
}

//----------------------------------------------------------------------------------------

function clear_supp_trans($type, $type_no)
{
	$sql = "DELETE FROM ".TB_PREF."supp_trans 
			WHERE type=".db_escape($type)." AND trans_no=".db_escape($type_no);

	db_query($sql, "could not clear supp transactions for type=$type and trans_no=$type_no");
}
//----------------------------------------------------------------------------------------

function post_void_supp_trans($type, $type_no)
{
	if ($type == ST_SUPPAYMENT)
	{
		void_supp_payment($type, $type_no);
		return true;
	}

	if ($type == ST_SUPPINVOICE || $type == ST_SUPPCREDIT)
	{
	 	void_supp_invoice($type, $type_no);
		return true;
	}

	if ($type == ST_SUPPRECEIVE)
	{
	 	return void_grn($type_no);
	}
	if ($type == ST_SUPPPDC)
	{
	 	 void_supp_pdc($type, $type_no);
		return true;
	}

	return false;
}

//----------------------------------------------------------------------------------------

function get_sql_for_supplier_inquiry($filter, $after_date, $to_date, $supplier_id=ALL_TEXT, $purch_type=0)
{
    $date_after = date2sql($after_date);
    $date_to = date2sql($to_date);

    $sql = "SELECT trans.type, 
		trans.trans_no,
		trans.reference, 
		supplier.supp_name, 
		trans.supp_reference,
    	trans.tran_date, 
		trans.due_date,
		supplier.curr_code, 
    	(trans.ov_amount + trans.ov_gst  + trans.ov_discount + trans.freight_cost + trans.additional_charges + trans.packing_charges + trans.other_charges + trans.freight_tax + trans.additional_tax + trans.packing_tax + trans.other_tax + trans.ov_roundoff) AS TotalAmount,

    	IF (trans.type=".ST_SUPPINVOICE." OR trans.type=".ST_BANKDEPOSIT."
  	OR (trans.type=".ST_JOURNAL." AND (trans.ov_amount + trans.ov_gst + trans.ov_discount + trans.freight_cost + trans.additional_charges + trans.packing_charges + trans.other_charges + trans.freight_tax + trans.additional_tax + trans.packing_tax + trans.other_tax + trans.ov_roundoff)>0), 1, -1)*
    		(ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount + trans.freight_cost + trans.additional_charges + trans.packing_charges + trans.other_charges + trans.freight_tax + trans.additional_tax + trans.packing_tax + trans.other_tax + trans.ov_roundoff) - trans.alloc) Balance,
		trans.alloc AS Allocated,
		((trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_SUPPCREDIT.") AND trans.due_date < '" . date2sql(Today()) . "') AS OverDue,
    	(ABS(trans.ov_amount + trans.ov_gst  + trans.ov_discount + trans.freight_cost + trans.additional_charges + trans.packing_charges + trans.other_charges + trans.freight_tax + trans.additional_tax + trans.packing_tax + trans.other_tax + trans.ov_roundoff) - trans.alloc <= ".FLOAT_COMP_DELTA.") AS Settled,trans.recall_status,trans.current_pdc_status
    	FROM ".TB_PREF."supp_trans as trans, ".TB_PREF."suppliers as supplier
    	WHERE supplier.supplier_id = trans.supplier_id
		AND trans.ov_amount != 0";	// exclude voided transactions

	$sql2 = "SELECT ".ST_SUPPRECEIVE." as type, 
		trans.id as trans_no,
		trans.reference, 
		supplier.supp_name, 
		po.requisition_no AS supp_reference,
    	delivery_date as tran_date, 
		'' as due_date,
		supplier.curr_code, 
		0 as Balance, 
    	'' AS TotalAmount,
		'' AS Allocated,
		0 as OverDue,
    	1 as Settled,0 as recall_status,0 as current_pdc_status
    	FROM ".TB_PREF."grn_batch as trans, ".TB_PREF."suppliers as supplier, ".TB_PREF."purch_orders as po
    	WHERE supplier.supplier_id = trans.supplier_id
    	AND trans.purch_order_no = po.order_no";
		
	$sql22 = "SELECT ".ST_SUPPRECEIVE." as type, 
		trans.id as trans_no,
		trans.reference, 
		supplier.supp_name, 
		'' as supp_reference,
		delivery_date as tran_date, 
		'' as due_date,
		supplier.curr_code, 
		0 as Balance, 
    	'' AS TotalAmount,
		'' AS Allocated,
		0 as OverDue,
    	1 as Settled,0 as recall_status,0 as current_pdc_status
    	FROM ".TB_PREF."grn_batch as trans, ".TB_PREF."suppliers as supplier
    	WHERE supplier.supplier_id = trans.supplier_id";		
		

	if ($filter == '2')
		$sql .= " AND ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount +
		trans.freight_cost + trans.additional_charges + trans.packing_charges + trans.other_charges + trans.freight_tax + trans.additional_tax + trans.packing_tax + trans.other_tax + trans.ov_roundoff) - trans.alloc>".FLOAT_COMP_DELTA;
	else {
		$sql .= " AND trans.tran_date >= '$date_after'
			AND trans.tran_date <= '$date_to'";
		$sql2 .= " AND trans.delivery_date >= '$date_after'
            AND trans.delivery_date <= '$date_to'";
	}

   	if ($supplier_id != ALL_TEXT) {
   		$sql .= " AND trans.supplier_id = ".db_escape($supplier_id);
   		$sql2 .= " AND trans.supplier_id = ".db_escape($supplier_id);
		$sql22 .= " AND trans.supplier_id = ".db_escape($supplier_id);
	}
	
	if ($purch_type > 0)
   		$sql .= " AND trans.purch_type = ".db_escape($purch_type);
		
	if (($filter == '6')) 
	{
			$sql = $sql22;
	} 
	elseif (!isset($filter) || $filter == ALL_TEXT) {
		$sql = "SELECT * FROM (($sql) UNION ($sql2)) as tr WHERE 1";
   	}

   	if (isset($filter) && $filter != ALL_TEXT)
   	{
   		if (($filter == '1')) 
   		{
   			$sql .= " AND (trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_BANKDEPOSIT.")";
   		} 
   		elseif ($filter == '3') 
   		{
			$sql .= " AND (trans.type = ".ST_SUPPAYMENT." OR trans.type = ".ST_BANKPAYMENT.") ";
   		} 
   		elseif (($filter == '4') || ($filter == '5')) 
   		{
			$sql .= " AND trans.type = ".ST_SUPPCREDIT."  ";
   		}
	    elseif ($filter == '7') 
   		{
			$sql .= " AND (trans.type = ".ST_SUPPPDC.") ";
   		} 
   		if (($filter == '2') || ($filter == '5')) 
   		{
   			$today =  date2sql(Today());
			$sql .= " AND trans.due_date < '$today' ";
   		}
   	}
   	return $sql;
}

function get_supp_bank_details($bank_act)
{

	$sql= "SELECT * FROM ".TB_PREF."bank_accounts
			WHERE id=".db_escape($bank_act);
	$res = db_query($sql, 'document version retreival');
    return db_fetch($res);
}



//ravi

function get_sql_for_inv_grn_inquiry($after_date, $to_date, $supplier_id=ALL_TEXT)
{
    $date_after = date2sql($after_date);
    $date_to = date2sql($to_date);

		$filter=6; 
		
	$sql22 = "SELECT ".ST_INVSUPPRECEIVE." as type, 
		trans.id as trans_no,
		trans.reference, 
		supplier.supp_name, 
		'' as supp_reference,
		delivery_date as tran_date, 
		'' as due_date,
		supplier.curr_code, 
		0 as Balance, 
    	'' AS TotalAmount,
		'' AS Allocated,
		0 as OverDue,
    	1 as Settled,0 as recall_status,0 as current_pdc_status
    	FROM ".TB_PREF."inv_grn_batch as trans, ".TB_PREF."suppliers as supplier
    	WHERE supplier.supplier_id = trans.supplier_id and trans.id not in (select voided.id from ".TB_PREF."voided voided where voided.type=125)";		
		


		$sql .= " AND trans.tran_date >= '$date_after'
			AND trans.tran_date <= '$date_to'";
		$sql2 .= " AND trans.delivery_date >= '$date_after'
            AND trans.delivery_date <= '$date_to'";

   	if ($supplier_id != ALL_TEXT) {
		$sql22 .= " AND trans.supplier_id = ".db_escape($supplier_id);
	}
	if (($filter == '6')) 
	{
			$sql = $sql22;
	} 
	elseif (!isset($filter) || $filter == ALL_TEXT) {
		$sql = "SELECT * FROM (($sql) UNION ($sql2)) as tr WHERE 1";
   	}

   	return $sql;
}



function bounce_pdc_supplier_trans($type, $type_no, $bounce_date)
{
	// clear all values and mark as void
	$sql = "UPDATE ".TB_PREF."supp_trans SET current_pdc_status=2, bounce_date=".db_escape(date2sql($bounce_date))."
	WHERE type=".db_escape($type)." AND trans_no=".db_escape($type_no);
	db_query($sql, "could not void debtor transactions for type=$type and trans_no=$type_no");
}