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

function update_customer_unlock($id)
{
	$sql = "UPDATE ".TB_PREF."debtors_master SET customer_lock = 0 WHERE debtor_no=".db_escape($id);

	db_query($sql, "could not update customer unlock");
}

function update_customer_lock($id)
{
	$sql = "UPDATE ".TB_PREF."debtors_master SET customer_lock = 0 WHERE debtor_no=".db_escape($id);

	db_query($sql, "could not update customer unlock");
}

function get_all_customer_lock($all=false)
{
	$sql = "SELECT debtor_no, concat(name,' - ',cust_code) name FROM ".TB_PREF."debtors_master WHERE customer_lock = 1 ";
	
	return db_query($sql, "could not get all Item subcategory");
}

function get_customer_info($customer_id)
{
   $sql = "SELECT cust_code, name FROM ".TB_PREF."debtors_master WHERE debtor_no = ".db_escape($customer_id)."";
	
	$result= db_query($sql, "could not get all Item subcategory");
	return db_fetch($result);
}

function get_all_customer_credit()
{
	$sql = "SELECT cust.cust_code, cust.name, cust.credit_limit, cust.credit_limit - Sum(IFNULL(IF(trans.type IN(11,12,2), -1, 1) * (ov_amount + ov_gst + ov_freight + ov_freight_tax + ov_discount),0)) as cur_credit FROM ".TB_PREF."debtors_master cust LEFT JOIN ".TB_PREF."debtor_trans trans ON trans.type!=13 AND trans.debtor_no = cust.debtor_no,".TB_PREF."credit_status credit_status,".TB_PREF."sales_types stype WHERE cust.sales_type=stype.id AND cust.credit_status=credit_status.id GROUP by cust.debtor_no";
	

return db_query($sql, "could not get all Item subcategory");

}

function request_customer_unlock($debtor_no)
{
  $sql = "UPDATE ".TB_PREF."debtors_master SET customer_unlock_request =1,customer_lock =1 WHERE debtor_no=".db_escape($debtor_no);

	db_query($sql, "could not update customer unlock");
}

function get_all_customer_requests()
{
  $sql = "SELECT cust_code, name FROM ".TB_PREF."debtors_master WHERE customer_unlock_request =1 and customer_lock=1";
	
	return db_query($sql, "could not get all Item subcategory");
}