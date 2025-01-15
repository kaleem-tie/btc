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
//----------------------------------------------------------------------------------------

function add_supp_allocation($amount, $trans_type_from, $trans_no_from,
	$trans_type_to, $trans_no_to, $person_id, $date_)
{
	$date = date2sql($date_);
	$sql = "INSERT INTO ".TB_PREF."supp_allocations (
		amt, date_alloc,
		trans_type_from, trans_no_from, trans_no_to, trans_type_to, person_id)
		VALUES (".db_escape($amount).", '$date', "
		.db_escape($trans_type_from).", ".db_escape($trans_no_from).", "
		.db_escape($trans_no_to).", ".db_escape($trans_type_to).", ".db_escape($person_id).")";
   
	db_query($sql, "A supplier allocation could not be added to the database");
}

//----------------------------------------------------------------------------------------


function delete_supp_allocation($trans_id)
{
	$sql = "DELETE FROM ".TB_PREF."supp_allocations WHERE id = ".db_escape($trans_id);
	db_query($sql, "The existing allocation $trans_id could not be deleted");
}

//----------------------------------------------------------------------------------------

function get_supp_trans_allocation_balance($trans_type, $trans_no)
{

	$sql = "SELECT (ov_amount+ov_gst+freight_cost+additional_charges+packing_charges+other_charges +freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff-ov_discount-alloc) AS BalToAllocate
		FROM ".TB_PREF."supp_trans WHERE trans_no="
		.db_escape($trans_no)." AND type=".db_escape($trans_type);

	
		
	$result = db_query($sql,"calculate the allocation");
	$myrow = db_fetch_row($result);

	return $myrow[0];
}

//----------------------------------------------------------------------------------------
//	Update supplier trans alloc field according to current status of supp_allocations
//
function update_supp_trans_allocation($trans_type, $trans_no, $person_id)
{

   
	$table_name = $trans_type == ST_PURCHORDER ? '`'.TB_PREF."purch_orders`" : '`'.TB_PREF."supp_trans`";
	$supp_alloc_table = '`'.TB_PREF."supp_allocations`";
	$person_id = db_escape($person_id);
	$trans_type = db_escape($trans_type);
	$trans_no = db_escape($trans_no);
	$where_condition = $trans_type == ST_PURCHORDER ? 'trans.order_no = '.$trans_no : 'trans.type='.$trans_type;


	$sql = "UPDATE $table_name AS trans, ";
	$sql .= " (SELECT person_id, sum(amt) AS amt FROM $supp_alloc_table WHERE person_id = $person_id AND ((trans_type_to = $trans_type AND trans_no_to = $trans_no) ";
	$sql .= " OR (trans_type_from = $trans_type AND trans_no_from = $trans_no) ) ) AS allocated SET trans.alloc = IFNULL(allocated.amt, 0) WHERE trans.supplier_id = allocated.person_id AND $where_condition AND trans.trans_no = $trans_no";
	

	// $sql = "UPDATE $table_name AS trans,
	// 		(SELECT person_id, sum(amt) amt from ".$sup_alloc_table."
	// 			WHERE person_id=".db_escape($person_id)." AND ((trans_type_to=".db_escape($trans_type)." AND trans_no_to=".db_escape($trans_no).")
	// 			OR (trans_type_from=".db_escape($trans_type)." AND trans_no_from=".db_escape($trans_no)."))) allocated
	// 	SET
	// 		trans.alloc=IFNULL(allocated.amt, 0)
	// 	WHERE trans.supplier_id=person_id AND "	. ($trans_type==ST_PURCHORDER ? 
	// 		  "trans.order_no=".db_escape($trans_no)
	// 		: "trans.type=".db_escape($trans_type)." AND trans.trans_no=".db_escape($trans_no));
     //display_error($sql); die;
	 db_query($sql, "The supp transaction record could not be modified for the allocation against it");
}

//-------------------------------------------------------------------------------------------------------------

function void_supp_allocations($type, $type_no, $date="")
{

	if($type != '6'){
	return clear_supp_alloctions($type, $type_no, null, $date);
	}else{
	return clear_pdc_supp_alloctions($type, $type_no, null, $date);
	}
	
}

//-------------------------------------------------------------------------------------------------------------

function clear_supp_alloctions($type, $type_no, $person_id=null, $date="")
{
	$sql = "UPDATE  ".TB_PREF."supp_allocations ca
				LEFT JOIN ".TB_PREF."supp_trans paym ON ca.trans_type_from=paym.type AND ca.trans_no_from=paym.trans_no AND ca.person_id=paym.supplier_id
				LEFT JOIN ".TB_PREF."supp_trans st ON ca.trans_type_to=st.type AND ca.trans_no_to=st.trans_no AND ca.person_id=st.supplier_id
				LEFT JOIN ".TB_PREF."purch_orders po ON ca.trans_type_to=".ST_PURCHORDER." AND ca.trans_no_to=po.order_no AND ca.person_id=po.supplier_id
	 		SET paym.alloc=paym.alloc - ca.amt,
	 			st.alloc=st.alloc -  ca.amt,
	 			po.alloc=po.alloc -  ca.amt
			WHERE  ((ca.trans_type_from=".db_escape($type)." AND ca.trans_no_from=".db_escape($type_no).")
				OR (ca.trans_type_to=".db_escape($type)." AND ca.trans_no_to=".db_escape($type_no)."))";
	if ($person_id)
		$sql .= " AND ca.person_id=".db_escape($person_id);
	db_query($sql, "could not clear allocation");

	// remove any allocations for this transaction
	$sql = "DELETE FROM ".TB_PREF."supp_allocations
			WHERE  ((trans_type_from=".db_escape($type)." AND trans_no_from=".db_escape($type_no).")
				OR (trans_type_to=".db_escape($type)." AND trans_no_to=".db_escape($type_no)."))";
	if ($person_id)
		$sql .= " AND person_id=".db_escape($person_id);

	db_query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");
}
//-------------------------------------------------------------------------------------------------------------

function get_allocatable_from_supp_sql($supplier_id, $settled)
{
	$sql = "SELECT
		trans.type,
		trans.trans_no,
		IF(trans.supp_reference='',trans.reference,trans.supp_reference) as reference,
 		trans.tran_date,
		supplier.supp_name, 
		supplier.curr_code, 
		ov_amount+ov_gst+ov_discount+freight_cost+additional_charges+packing_charges+other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff AS Total,
		trans.alloc,
		trans.due_date,
		trans.supplier_id,
		supplier.address,
		round(abs(ov_amount+ov_gst+ov_discount+freight_cost+additional_charges+packing_charges+other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff)-alloc,6) <= 0 AS settled
	 FROM "
	 	.TB_PREF."supp_trans as trans, "
		.TB_PREF."suppliers as supplier"
	." WHERE trans.supplier_id=supplier.supplier_id
		AND type IN(".ST_SUPPAYMENT.",".ST_SUPPCREDIT.",".ST_BANKPAYMENT.",".ST_JOURNAL.") AND (trans.ov_amount < 0)";

	if (!$settled)
		$sql .= " AND (round(abs(ov_amount+ov_gst+ov_discount++freight_cost+additional_charges+packing_charges+other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff)-alloc,6) > 0)";

	if ($supplier_id != null)
		$sql .= " AND supplier.supplier_id = ".db_escape($supplier_id);

	return $sql;
}

function get_allocatable_purch_orders($supplier_id = null, $trans_no=null, $type=null)
{
	$due_dates = "SELECT order_no, MIN(delivery_date) as date
		FROM ".TB_PREF."purch_order_details det
		GROUP BY det.order_no";

	$sql = "SELECT
		".ST_PURCHORDER." as type,
		porder.order_no as trans_no,
		porder.reference,
		porder.ord_date,
		supplier.supp_name AS DebtorName, 
		supplier.curr_code,
		total as Total,
		porder.alloc,
		due_dates.date as due_date,
		supplier.address,
		amt,
		requisition_no as supp_ref
		FROM ".TB_PREF."purch_orders as porder
			LEFT JOIN ".TB_PREF."suppliers as supplier ON porder.supplier_id = supplier.supplier_id
			LEFT JOIN ".TB_PREF."supp_allocations as alloc
				ON porder.order_no = alloc.trans_no_to AND alloc.trans_type_to=".ST_PURCHORDER." AND alloc.person_id=porder.supplier_id
			LEFT JOIN ".TB_PREF."grn_batch as grn ON porder.order_no = grn.purch_order_no
			LEFT JOIN ($due_dates) due_dates ON due_dates.order_no=porder.order_no
		WHERE total>0";

	if ($trans_no != null and $type != null)
	{
		$sql .= " AND alloc.trans_no_from=".db_escape($trans_no)."
				  AND alloc.trans_type_from=".db_escape($type);
	}
	else
	{
		$sql .= " AND round(porder.prep_amount)>0 and Total>0 AND ISNULL(grn.purch_order_no)"; // only sales orders no yet received
	}
	
	if ($supplier_id)
		$sql .= " AND porder.supplier_id=".db_escape($supplier_id);

	$sql .= " GROUP BY porder.order_no, grn.purch_order_no";
	
	
	return $sql;
}
//-------------------------------------------------------------------------------------------------------------

function get_allocatable_to_supp_transactions($supplier_id=null, $trans_no=null, $type=null)
{
	$sql = "SELECT
		trans.type,
		trans.trans_no,
		IF(trans.supp_reference='',trans.reference,trans.supp_reference) as reference,
 		trans.tran_date,
		supplier.supp_name, 
		supplier.curr_code, 
		ov_amount+ov_gst+ov_discount+freight_cost+additional_charges+packing_charges+other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff AS Total,
		trans.alloc,
		trans.due_date,
		trans.supplier_id,
		amt,
		supp_reference

	 FROM ".TB_PREF."supp_trans as trans
			LEFT JOIN ".TB_PREF."supp_allocations as alloc
				ON trans.trans_no = alloc.trans_no_to AND trans.type = alloc.trans_type_to AND alloc.person_id=trans.supplier_id,"
	 		.TB_PREF."suppliers as supplier
	 WHERE
	 	 trans.supplier_id=supplier.supplier_id";
	if ($supplier_id)
		$sql .= " AND supplier.supplier_id=".db_escape($supplier_id);

	if ($trans_no != null and $type != null)
	{
		$sql .= " AND alloc.trans_no_from=".db_escape($trans_no)."
				  AND alloc.trans_type_from=".db_escape($type);
	}
	else
	{
		$sql .= " AND round(ov_amount+ov_gst+ov_discount+freight_cost+additional_charges+packing_charges+other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff-alloc,6) > 0
			AND trans.type NOT IN (".implode(',',array(ST_SUPPAYMENT, ST_BANKPAYMENT)).")";
		$sql .= " GROUP BY type, trans_no";
	}

	$orders = get_allocatable_purch_orders($supplier_id, $trans_no, $type);
	$sql = "($sql ORDER BY trans_no) \nUNION \n($orders)";
	
	//display_error($sql);

	return db_query($sql." ORDER BY due_date", "Cannot retreive alloc to transactions");
}

//-------------------------------------------------------------------------------------------------------------

function get_allocatable_from_supp_transactions($supplier_id, $trans_no=null, $type=null)
{
	$sql = "SELECT
		trans.type,
		trans.trans_no,
		IF(trans.supp_reference='',trans.reference,trans.supp_reference) as reference,
 		trans.tran_date,
		supplier.supp_name, 
		supplier.curr_code, 
		ov_amount+ov_gst+ov_discount+freight_cost+additional_charges+packing_charges+other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff AS Total,
		trans.alloc,
		trans.due_date,
		trans.supplier_id,
		supplier.address,
		amt,
		supp_reference
	 FROM  ".TB_PREF."supp_trans as trans,"
			.TB_PREF."suppliers as supplier,"
			.TB_PREF."supp_allocations as alloc
	 WHERE trans.supplier_id=supplier.supplier_id
			AND trans.trans_no = alloc.trans_no_from
			AND trans.type = alloc.trans_type_from
			AND trans.supplier_id = alloc.person_id";

	if ($trans_no != null and $type != null)
	{
		$sql .=	" AND alloc.trans_no_to=".db_escape($trans_no)."
		  		  AND alloc.trans_type_to=".db_escape($type);
	}
	else
	{
		$sql .= " AND round(ABS(ov_amount+ov_gst+ov_discount+freight_cost+additional_charges+packing_charges+other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff)-alloc,6) > 0
			AND trans.type NOT IN (".implode(',',array(ST_SUPPAYMENT,ST_BANKPAYMENT)).")";
		$sql .= " GROUP BY type, trans_no";
	}
	return db_query($sql." ORDER BY due_date", "Cannot retreive alloc to transactions");
}

function get_sql_for_supplier_allocation_inquiry()
{
 	$date_after = date2sql($_POST['TransAfterDate']);
 	$date_to = date2sql($_POST['TransToDate']);

    $sql = "SELECT 
		trans.type, 
		trans.trans_no,
		trans.reference, 
		supplier.supp_name, 
		trans.supp_reference,
    	trans.tran_date, 
		trans.due_date,
		supplier.curr_code, 
    	(trans.ov_amount + trans.ov_gst  + trans.ov_discount + trans.freight_cost + trans.additional_charges+ trans.packing_charges + trans.other_charges+trans.freight_tax+trans.additional_tax+trans.packing_tax+trans.other_tax + trans.ov_roundoff) AS TotalAmount,  
		trans.alloc AS Allocated,
		((trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_SUPPCREDIT.") AND trans.due_date < '" . date2sql(Today()) . "') AS OverDue,
		trans.supplier_id
    	FROM "
			.TB_PREF."supp_trans as trans
			LEFT JOIN ".TB_PREF."voided as v
				ON trans.trans_no=v.id AND trans.type=v.type,"
			.TB_PREF."suppliers as supplier
    	WHERE supplier.supplier_id = trans.supplier_id
     	AND trans.tran_date >= '$date_after'
	   	AND trans.tran_date <= '$date_to'
		AND ISNULL(v.date_)";

   	if ($_POST['supplier_id'] != ALL_TEXT)
   		$sql .= " AND trans.supplier_id = ".db_escape($_POST['supplier_id']);
   	if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT)
   	{
   		if (($_POST['filterType'] == '1') || ($_POST['filterType'] == '2')) // invoices
   		{
   			$sql .= " AND trans.type = ".ST_SUPPINVOICE." ";
   		}
   		elseif ($_POST['filterType'] == '3')	// payments
   		{
			$sql .= " AND trans.type = ".ST_SUPPAYMENT." ";
   		}
   		elseif (($_POST['filterType'] == '4') || ($_POST['filterType'] == '5')) // credits
   		{
			$sql .= " AND trans.type = ".ST_SUPPCREDIT." ";
   		}

   		if (($_POST['filterType'] == '2') || ($_POST['filterType'] == '5')) // overdude 
   		{
   			$today =  date2sql(Today());
			$sql .= " AND trans.due_date < '$today' ";
   		}
   	}

   	if (!check_value('showSettled'))
   	{
   		$sql .= " AND (round(abs(ov_amount + ov_gst + ov_discount + freight_cost + additional_charges + packing_charges + other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff) - alloc,6) != 0) ";
   	}
   	return $sql;
}




function clear_pdc_supp_alloctions($type, $type_no, $person_id=null, $date="")
{
	$sql = "UPDATE  ".TB_PREF."supp_pdc_allocations ca
				LEFT JOIN ".TB_PREF."supp_trans paym ON ca.trans_type_from=paym.type AND ca.trans_no_from=paym.trans_no AND ca.person_id=paym.supplier_id
				LEFT JOIN ".TB_PREF."supp_trans st ON ca.trans_type_to=st.type AND ca.trans_no_to=st.trans_no AND ca.person_id=st.supplier_id
				LEFT JOIN ".TB_PREF."purch_orders po ON ca.trans_type_to=".ST_PURCHORDER." AND ca.trans_no_to=po.order_no AND ca.person_id=po.supplier_id
	 		SET paym.pdc_amt=paym.pdc_amt - ca.amt,
	 			st.pdc_amt=st.pdc_amt -  ca.amt,
	 			po.pdc_amt=po.pdc_amt -  ca.amt
			WHERE  ((ca.trans_type_from=".db_escape($type)." AND ca.trans_no_from=".db_escape($type_no).")
				OR (ca.trans_type_to=".db_escape($type)." AND ca.trans_no_to=".db_escape($type_no)."))";
	if ($person_id)
		$sql .= " AND ca.person_id=".db_escape($person_id);
	db_query($sql, "could not clear allocation");

	// remove any allocations for this transaction
	$sql = "DELETE FROM ".TB_PREF."supp_pdc_allocations
			WHERE  ((trans_type_from=".db_escape($type)." AND trans_no_from=".db_escape($type_no).")
				OR (trans_type_to=".db_escape($type)." AND trans_no_to=".db_escape($type_no)."))";
	if ($person_id)
		$sql .= " AND person_id=".db_escape($person_id);

	db_query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");
}


function add_supp_pdc_allocation($amount, $trans_type_from, $trans_no_from,
	$trans_type_to, $trans_no_to, $person_id, $date_)
{
	$date = date2sql($date_);
	$sql = "INSERT INTO ".TB_PREF."supp_pdc_allocations (
		amt, date_alloc,
		trans_type_from, trans_no_from, trans_no_to, trans_type_to, person_id)
		VALUES (".db_escape($amount).", '$date', "
		.db_escape($trans_type_from).", ".db_escape($trans_no_from).", "
		.db_escape($trans_no_to).", ".db_escape($trans_type_to).", ".db_escape($person_id).")";

	db_query($sql, "A supplier allocation could not be added to the database");
}



function update_supp_trans_allocation_with_pdc($trans_type, $trans_no, $person_id)
{

	$sql = 
		"UPDATE `".TB_PREF.($trans_type==ST_PURCHORDER ? 'purch_orders' : 'supp_trans')."` trans,
			(SELECT person_id, sum(amt) amt from ".TB_PREF."supp_pdc_allocations
				WHERE person_id=".db_escape($person_id)." AND recall_status=0 AND ((trans_type_to=".db_escape($trans_type)." AND trans_no_to=".db_escape($trans_no).")
				OR (trans_type_from=".db_escape($trans_type)." AND trans_no_from=".db_escape($trans_no)."))) allocated,
				
				(SELECT sum(amt) amt FROM ".TB_PREF."supp_pdc_allocations
				WHERE person_id=".db_escape($person_id)." AND recall_status=0 AND ((trans_type_to=".db_escape($trans_type)." AND trans_no_to=".db_escape($trans_no).")
				OR (trans_type_from=".db_escape($trans_type)." AND trans_no_from=".db_escape($trans_no).")) AND  trans_type_from !='22' ) allocated_pdc
		SET 
			trans.pdc_amt=IFNULL(allocated_pdc.amt,0)
		WHERE trans.supplier_id=".db_escape($person_id)." AND ".($trans_type==ST_PURCHORDER ?
			   "trans.trans_type=".db_escape($trans_type)." AND order_no=".db_escape($trans_no)
			:  "trans.type=".db_escape($trans_type)." AND trans_no=".db_escape($trans_no));	
	//display_error($sql);die;
	db_query($sql, "The supplier pdc transaction record could not be modified for the allocation against it");
	
	// trans.alloc=IFNULL(allocated.amt,0),
	
}


function get_supplier_pdc_amount_of_invoice($trans_type,$trans_no)
{
	$supp_pdcsql = "SELECT pdc_amt FROM ".TB_PREF."supp_trans WHERE type=".db_escape($trans_type)." AND trans_no = ".db_escape($trans_no)."";
	$supp_pdcres = db_query($supp_pdcsql);
	$supp_pdc_results = db_fetch_row($supp_pdcres);
	return $supp_pdc_results[0];
}


function get_supplier_pdc_amount_against_invoice($pdc_number,$trans_no_to)
{
	$supp_pdcsql = "SELECT amt FROM ".TB_PREF."supp_pdc_allocations WHERE trans_type_from='".ST_SUPPPDC."' AND trans_no_from = ".db_escape($pdc_number)." AND trans_no_to=".db_escape($trans_no_to)."";
	$supp_pdcres = db_query($supp_pdcsql);
	$supp_pdc_results = db_fetch_row($supp_pdcres);
	return $supp_pdc_results[0];
}

function get_supplier_payment_amount_of_invoice($trans_type,$trans_no)
{
	$supp_pdcsql = "SELECT ov_amount FROM ".TB_PREF."supp_trans WHERE type=".db_escape($trans_type)." AND trans_no = ".db_escape($trans_no)."";
	$supp_pdcres = db_query($supp_pdcsql);
	$supp_pdc_results = db_fetch_row($supp_pdcres);
	return $supp_pdc_results[0];
}


function get_allocatable_to_pdc_supp_transactions($supplier_id=null, $trans_no=null, $type=null)
{
	$sql = "SELECT
		trans.type,
		trans.trans_no,
		alloc.trans_no_from,
		IF(trans.supp_reference='',trans.reference,trans.supp_reference) as reference,
 		trans.tran_date,
		supplier.supp_name, 
		supplier.curr_code, ov_amount+ov_gst+ov_discount+freight_cost+additional_charges+packing_charges+other_charges+
		freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff AS Total,
		alloc.amt as alloc,
		trans.due_date,
		trans.supplier_id,
		amt,
		supp_reference

	 FROM ".TB_PREF."supp_trans as trans
			LEFT JOIN ".TB_PREF."supp_pdc_allocations as alloc
				ON trans.trans_no = alloc.trans_no_to AND trans.type = alloc.trans_type_to AND alloc.person_id=trans.supplier_id,"
	 		.TB_PREF."suppliers as supplier
	 WHERE
	 	 trans.supplier_id=supplier.supplier_id";
	if ($supplier_id)
		$sql .= " AND supplier.supplier_id=".db_escape($supplier_id);

	if ($trans_no != null and $type != null)
	{
		$sql .= " AND alloc.trans_no_from=".db_escape($trans_no)."
				  AND alloc.trans_type_from=".db_escape($type);
	}
	else
	{
		$sql .= " AND round(ov_amount+ov_gst+ov_discount+freight_cost+additional_charges+packing_charges+other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff-alloc,6) > 0
			AND trans.type NOT IN (".implode(',',array(ST_SUPPAYMENT, ST_BANKPAYMENT)).")";
		$sql .= " GROUP BY type, trans_no";
	}


	return db_query($sql." ORDER BY due_date", "Cannot retreive alloc to transactions");
}