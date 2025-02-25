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

function add_customer($CustName, $cust_ref, $address, $tax_id, $curr_code,
	$dimension_id, $dimension2_id, $credit_status, $payment_terms, $discount, $pymt_discount, 
	$credit_limit, $sales_type, $notes, $cash_customer,
	$cr_no = '',$cr_date = null,$sponser_name ='',$nature_of_business ='',$business_type_id,
	$vat_reg,$vat_renewal_date,$vat_effective_date,$tax_card_no,$vat_status)
{
	$sql = "INSERT INTO ".TB_PREF."debtors_master (name, debtor_ref, address, tax_id,
		dimension_id, dimension2_id, curr_code, credit_status, payment_terms, discount, 
		pymt_discount,credit_limit, sales_type, notes, cash_customer,
		cr_no,cr_date,sponser_name,nature_of_business,business_type_id,
		vat_reg,vat_renewal_date,vat_effective_date,tax_card_no,vat_status) VALUES ("
		.db_escape($CustName) .", " .db_escape($cust_ref) .", "
		.db_escape($address) . ", " . db_escape($tax_id) . ","
		.db_escape($dimension_id) . ", " 
		.db_escape($dimension2_id) . ", ".db_escape($curr_code) . ", 
		" . db_escape($credit_status) . ", ".db_escape($payment_terms) . ", " . $discount . ", 
		" . $pymt_discount . ", " . $credit_limit 
		 .", ".db_escape($sales_type).", ".db_escape($notes) . ", ".db_escape($cash_customer) . ", ".db_escape($cr_no) . ", '".date2sql($cr_date)."', ".db_escape($sponser_name) . ", ".db_escape($nature_of_business) . ",".db_escape($business_type_id) . ",
		 ".db_escape($vat_reg) . ",'".date2sql($vat_renewal_date)."','".date2sql($vat_effective_date)."',
		 ".db_escape($tax_card_no) . ",".db_escape($vat_status) . ")";
	db_query($sql,"The customer could not be added");
}

function update_customer($customer_id, $CustName, $cust_ref, $address, $tax_id, $curr_code,
	$dimension_id, $dimension2_id, $credit_status, $payment_terms, $discount, $pymt_discount,
	$credit_limit, $sales_type, $notes,$cash_customer,
	$cr_no = '',$cr_date = null,$sponser_name ='',$nature_of_business ='',$business_type_id,
	$vat_reg,$vat_renewal_date,$vat_effective_date,$tax_card_no,$vat_status)
{
	$sql = "UPDATE ".TB_PREF."debtors_master SET name=" . db_escape($CustName) . ", 
		debtor_ref=" . db_escape($cust_ref) . ",
		address=".db_escape($address) . ", 
		tax_id=".db_escape($tax_id) . ", 
		curr_code=".db_escape($curr_code) . ", 
		dimension_id=".db_escape($dimension_id) . ", 
		dimension2_id=".db_escape($dimension2_id) . ", 
		credit_status=".db_escape($credit_status) . ", 
		payment_terms=".db_escape($payment_terms) . ", 
		discount=" . $discount . ", 
		pymt_discount=" . $pymt_discount . ", 
		credit_limit=" . $credit_limit . ", 
		sales_type = ".db_escape($sales_type) . ", 
		notes=".db_escape($notes) .",
		cash_customer=".db_escape($cash_customer) .",
		cr_no=".db_escape($cr_no) .", 
		cr_date='".date2sql($cr_date)."',
		sponser_name = ".db_escape($sponser_name) . ", 
		nature_of_business=".db_escape($nature_of_business) .", 
		business_type_id=".db_escape($business_type_id) .", 
		vat_reg=".db_escape($vat_reg) .",
		vat_renewal_date = '".date2sql($vat_renewal_date)."',
		vat_effective_date = '".date2sql($vat_effective_date)."',
		tax_card_no=".db_escape($tax_card_no) .",
		vat_status=".db_escape($vat_status) ."
		WHERE debtor_no = ".db_escape($customer_id);

	db_query($sql,"The customer could not be updated");
}

function delete_customer($customer_id)
{
	begin_transaction();
	delete_entity_contacts('customer', $customer_id);

	$sql = "DELETE FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape($customer_id);
	db_query($sql,"cannot delete customer");
	commit_transaction();
}

/*
	This function probably should be renamed to get_customer_summary
*/
function get_customer_details($customer_id, $to=null, $all=true,$dimension=0)
{

	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
	$past1 = get_company_pref('past_due_days');
	$past2 = 2 * $past1;

	$sign = "IF(trans.type IN(".implode(',',  array(ST_CUSTCREDIT,ST_CUSTPAYMENT,ST_BANKDEPOSIT))."), -1, 1)";
	$value = "$sign*(IF(trans.prep_amount, trans.prep_amount,
		ABS(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount)) ".($all ? '' : "- trans.alloc").")";

	$due = "IF (trans.type=".ST_SALESINVOICE.", trans.due_date, trans.tran_date)";
	$sql = "SELECT debtor.name, debtor.curr_code, terms.terms,
		debtor.credit_limit, credit_status.dissallow_invoices, credit_status.reason_description,

		Sum(IFNULL($value,0)) AS Balance,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0)) AS Due,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past1,$value,0)) AS Overdue1,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past2,$value,0)) AS Overdue2

		FROM ".TB_PREF."debtors_master debtor
			LEFT JOIN ".TB_PREF."debtor_trans trans ON trans.tran_date <= '$todate' AND debtor.debtor_no = trans.debtor_no AND trans.type <> ". ST_CUSTDELIVERY.",
			".TB_PREF."payment_terms terms,
			".TB_PREF."credit_status credit_status

		WHERE
			 debtor.payment_terms = terms.terms_indicator
			 AND debtor.credit_status = credit_status.id";

	if ($customer_id)
		$sql .= " AND debtor.debtor_no = ".db_escape($customer_id);
		
	if (!$all)
		$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount) - trans.alloc) > ".FLOAT_COMP_DELTA;

	$sql .= " GROUP BY
				debtor.name,
				terms.terms,
				terms.days_before_due,
				terms.day_in_following_month,
				debtor.credit_limit,
				credit_status.dissallow_invoices,
				credit_status.reason_description";

    $result = db_query($sql,"The customer details could not be retrieved");

    $customer_record = db_fetch($result);

    return $customer_record;

}


function get_customer($customer_id)
{
	$sql = "SELECT * FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape($customer_id);

	$result = db_query($sql, "could not get customer");

	return db_fetch($result);
}

function get_customer_name($customer_id)
{
	$sql = "SELECT name FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape($customer_id);

	$result = db_query($sql, "could not get customer");

	$row = db_fetch_row($result);

	return is_array($row) ? $row[0] : false;
}

function get_customer_habit($customer_id)
{
	$sql = "SELECT debtor.pymt_discount, credit_status.dissallow_invoices
			FROM ".TB_PREF."debtors_master debtor,"
				.TB_PREF."credit_status credit_status
			WHERE debtor.credit_status = credit_status.id
			AND debtor.debtor_no = ".db_escape($customer_id);

	$result = db_query($sql, "could not query customers");

	return db_fetch($result);
}

function get_customer_contacts($customer_id, $action=null)
{
	$results = array();
	$res = get_crm_persons('customer', $action, $customer_id);
	while($contact = db_fetch($res))
	{
		if ($contact['lang'] == 'C') // Fix for improper lang in demo sql files.
			$contact['lang'] = '';
		$results[] = $contact;
	}	
	return $results;
}

function get_current_cust_credit($customer_id)
{
	$custdet = get_customer_details($customer_id);

	if (!is_array($custdet))
		return 0;
	return $custdet['credit_limit']-$custdet['Balance'];

}

function is_new_customer($id)
{
	$tables = array('cust_branch', 'debtor_trans', 'recurrent_invoices', 'sales_orders');

	return !key_in_foreign_table($id, $tables, 'debtor_no');
}

function get_customer_by_ref($reference)
{
	$sql = "SELECT * FROM ".TB_PREF."debtors_master WHERE debtor_ref=".db_escape($reference);

	$result = db_query($sql, "could not get customer");

	return db_fetch($result);
}

//----------------------------------------------------------------------------------

function get_customer_currency($customer_id=null, $branch_id=null)
{
    $sql = "SELECT curr_code
    	FROM ".TB_PREF."debtors_master cust
    		LEFT JOIN ".TB_PREF."cust_branch branch ON branch.debtor_no=cust.debtor_no
    	WHERE " .(isset($branch_id) ? "branch_code = ".db_escape($branch_id) : "cust.debtor_no = ".db_escape($customer_id));

	$result = db_query($sql, "Retreive currency of customer $customer_id");

	$myrow=db_fetch_row($result);
	return $myrow ? $myrow[0] : get_company_currency();
}

function get_customers_search($customer)
{
	global $SysPrefs;

	if (isset($SysPrefs->max_rows_in_search))
		$limit = $SysPrefs->max_rows_in_search;
	else
		$limit = 10;

	$sql = "SELECT debtor_no, name, debtor_ref, address, tax_id FROM ".TB_PREF."debtors_master 
	  WHERE (  name LIKE " . db_escape("%" . $customer. "%") . " OR 
    		 debtor_ref LIKE " . db_escape("%" . $customer. "%") . " OR 
	        address LIKE " . db_escape("%" . $customer. "%") . " OR 
    	     tax_id LIKE " . db_escape("%" . $customer. "%").")
	  ORDER BY name LIMIT 0,".(int)($limit);

	return db_query($sql, "Failed in retreiving customer list.");
}
