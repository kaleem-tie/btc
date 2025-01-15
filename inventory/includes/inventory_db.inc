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
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/inventory.inc");

include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_trans_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_prices_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_purchases_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_codes_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_locations_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_adjust_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_transfer_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_units_db.inc");
include_once($path_to_root . "/inventory/includes/db/transactions_inquiry_db.inc");
include_once($path_to_root . "/inventory/includes/db/material_indent_db.inc");

function item_img_name($stock_id)
{
	$stock_id = strtr($stock_id, "><\\/:|*?", '________');
	return clean_file_name($stock_id);
}

function get_stock_movements($stock_id, $StockLocation,	$BeforeDate, $AfterDate)
{
	$before_date = date2sql($BeforeDate);
	$after_date = date2sql($AfterDate);
	// PO Delivery and Customer Credit Notes references should be saved in stock moves reference in 2.5
  	$sql = "SELECT move.*, IF(ISNULL(supplier.supplier_id), debtor.name, supplier.supp_name) name,
		IF(move.type=".ST_SUPPRECEIVE.", grn.reference, IF(move.type=".ST_CUSTCREDIT.", cust_trans.reference, move.reference)) reference";

	if(!$StockLocation) {
		 $sql .= ", move.loc_code";
	}
  	$sql.=    " FROM ".TB_PREF."stock_moves move
				LEFT JOIN ".TB_PREF."supp_trans credit ON credit.trans_no=move.trans_no AND credit.type=move.type
				LEFT JOIN ".TB_PREF."grn_batch grn ON grn.id=move.trans_no AND move.type=".ST_SUPPRECEIVE."
				LEFT JOIN ".TB_PREF."suppliers supplier ON IFNULL(grn.supplier_id, credit.supplier_id)=supplier.supplier_id
				LEFT JOIN ".TB_PREF."debtor_trans cust_trans ON cust_trans.trans_no=move.trans_no AND cust_trans.type=move.type
				LEFT JOIN ".TB_PREF."debtors_master debtor ON cust_trans.debtor_no=debtor.debtor_no
		WHERE";

  	if ($StockLocation) {
    	$sql.= " move.loc_code=".db_escape($StockLocation)." AND";
	}

	$sql.= " move.tran_date >= '". $after_date . "'
		AND move.tran_date <= '" . $before_date . "'
		AND move.qty!=0 
		AND move.stock_id = ".db_escape($stock_id) . " ORDER BY move.tran_date, move.trans_id";
   
  	return db_query($sql, "could not query stock moves");
}

function calculate_reorder_level($location, $line, &$st_ids, &$st_names, &$st_num, &$st_reorder)
{
	$sql = "SELECT stock.*, loc.location_name, loc.email
		FROM ".TB_PREF."loc_stock stock,"
			.TB_PREF."locations loc
		WHERE stock.loc_code=loc.loc_code
		AND stock.stock_id = '" . $line->stock_id . "'
		AND stock.loc_code = '" . $location . "'";
	$res = db_query($sql,"a location could not be retreived");
	$loc = db_fetch($res);
	if ($loc['email'] != "")
	{
		$qoh = get_qoh_on_date($line->stock_id, $location);
		$qoh -= get_demand_qty($line->stock_id, $location);
		$qoh -= get_demand_asm_qty($line->stock_id, $location);
		$qoh -= $line->quantity;
		if ($qoh < $loc['reorder_level'])
		{
			$st_ids[] = $line->stock_id;
			$st_names[] = $line->item_description;
			$st_num[] = $qoh - $loc['reorder_level'];
			$st_reorder[] = $loc['reorder_level'];
		}
	}
	return $loc;
}

function send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder)
{
	global $path_to_root;

	require_once($path_to_root . "/reporting/includes/class.mail.inc");
	$company = get_company_prefs();
	$mail = new email($company['coy_name'], $company['email']);
	$to = $loc['location_name'] . " <" . $loc['email'] . ">";
	$subject = _("Stocks below Re-Order Level at ") . $loc['location_name'];
	$msg = "\n";
	for ($i = 0; $i < count($st_ids); $i++)
		$msg .= $st_ids[$i] . " " . $st_names[$i] . ", " . _("Re-Order Level") . ": " . $st_reorder[$i] . ", " . _("Below") . ": " . $st_num[$i] . "\n";
	$msg .= "\n" . _("Please reorder") . "\n\n";
	$msg .= $company['coy_name'];
	$mail->to($to);
	$mail->subject($subject);
	$mail->text($msg);
	return $mail->send();
}
function get_last_grn_date($stock_id)
{
   $sql="SELECT tran_date FROM ".TB_PREF."stock_moves WHERE type=25 and stock_id =".db_escape($stock_id)." order by tran_date desc";
     
   $result = db_query($sql, "No standard cost transactions were returned");
   if($row=db_fetch_row($result))
   {
      return sql2date($row[0]);
   }
   else
   return "Not Found!";
}

function get_last_purchase_details($stock_id)
{
  $sql="SELECT  supplier.curr_code, (sitems.unit_price*(100-sitems.discount_percent)/100) as purch_price FROM ".TB_PREF."supp_trans st,".TB_PREF."suppliers supplier,".TB_PREF."supp_invoice_items sitems WHERE st.supplier_id=supplier.supplier_id and st.type=sitems.supp_trans_type and st.type=20 and st.trans_no=sitems.supp_trans_no and sitems.stock_id=".db_escape($stock_id)." order by st.tran_date desc";
     
   $result = db_query($sql, "No standard cost transactions were returned");
   if($row=db_fetch_row($result))
   {
      return $row;
   }
   else
   return "Not Found!";
}


function get_item_purch_data_info($stock_id)
{
    $sql = "SELECT pdata.*, supplier.supp_name,	supplier.curr_code
		FROM ".TB_PREF."purch_data pdata
			INNER JOIN ".TB_PREF."suppliers supplier ON pdata.supplier_id=supplier.supplier_id
		WHERE stock_id = ".db_escape($stock_id)."";

   $result = db_query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");

  return db_fetch($result);
}

function get_item_supplier_id($stock_id)
{
    $sql = "SELECT supplier_id
		FROM ".TB_PREF."stock_master		
		WHERE stock_id = ".db_escape($stock_id)."";

   $result = db_query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");

  $row= db_fetch_row($result);
  return $row[0];
}