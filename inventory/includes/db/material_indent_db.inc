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
function add_material_indent_request($items,$indent_req_loc,$indent_from_loc,$date,$ref,$memo =null,$qoh=0,$requested_by='',$ind_req_type_id=0)
{

	global $SysPrefs, $path_to_root, $Refs;

	begin_transaction();
	$args = func_get_args();
	$args = (object)array_combine(array('items', 'indent_req_loc', 'indent_from_loc', 'date', 'ref','memo','qoh','requested_by','ind_req_type_id'), $args);
	$args->trans_no = 0;
	hook_db_prewrite($args, ST_MATERIAL_INDENT);

    $tran_date = date2sql($date); 	 	
    	
	$trans_type=ST_MATERIAL_INDENT;

	$sql = "INSERT INTO ".TB_PREF."material_indent_request(type, req_loc_code, indent_from_loc,tran_date,reference,memo,requested_by,ind_req_type_id) VALUES ("
		.db_escape($trans_type).", "
		.db_escape($indent_req_loc).","
		
		.db_escape($indent_from_loc).", ".db_escape($tran_date).",".db_escape($ref).", ".db_escape($memo).",".db_escape($requested_by).",
		".db_escape($ind_req_type_id).")";
	
	
	db_query($sql, "The stock movement record cannot be inserted");

	$indent_id=db_insert_id();


	if ($SysPrefs->loc_notification() == 1)
	{
		include_once($path_to_root . "/inventory/includes/inventory_db.inc");
		$st_ids = array();
		$st_names = array();
		$st_num = array();
		$st_reorder = array();
	}
	
		foreach ($items as $line_item)
	{
	$i=0;
		
	
	$sql1 = "INSERT INTO ".TB_PREF."material_indent_request_details(indent_id, stock_id, item_description, quantity) VALUES (".db_escape($indent_id).",".db_escape($line_item->stock_id).", ".db_escape($line_item->item_description).",  ".db_escape($line_item->quantity).")";
	db_query($sql1, "The stock movement record cannot be inserted");
	
    $today = Today();
		
	
		
		
	} /* inserted line items into sales order details */

	add_comments(ST_MATERIAL_INDENT, $indent_id, $date, $memo_);

	$Refs->save(ST_MATERIAL_INDENT, $indent_id, $reference);
	
	add_audit_trail(ST_MATERIAL_INDENT, $indent_id, $date);

	$args->trans_no = $indent_id;
	hook_db_postwrite($args, ST_MATERIAL_INDENT);
	commit_transaction();
	if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0)
		send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);

	return $indent_id;
}


//-------------------------------------------------------------------------------------------------------------
//
//	Get inventory transfer common data (currently this is still stored in stock_moves table).
//
function get_material_indent_header($trans_no)
{
	// retrieve common data from any two from/to move records
	$sql = "SELECT indent.*,loc1.location_name AS loc1,loc2.location_name AS loc2 FROM ".TB_PREF."material_indent_request AS indent LEFT JOIN  ".TB_PREF."locations AS loc1  ON loc1.loc_code = indent.req_loc_code LEFT JOIN ".TB_PREF."locations AS loc2 ON loc2.loc_code =indent.indent_from_loc WHERE indent.indent_id=".db_escape($trans_no);
	
	$result = db_query($sql, "Could not get transfer common data");
	return $result;
}

//-------------------------------------------------------------------------------------------------------------

function get_material_indent_items($trans_no)
{
	$result = get_material_indent(ST_MATERIAL_INDENT, $trans_no);

	if (db_num_rows($result) == 0)
	{
		return null;
	}

	return $result;
}
function get_material_indent($type, $type_no)
{
	$sql = "SELECT details.* FROM ".TB_PREF."material_indent_request_details details
			WHERE details.indent_id =".db_escape($type_no)." ORDER BY indent_id";

	return db_query($sql, "Could not get stock moves");
}
//-------------------------------------------------------------------------------------------------------------

function void_material_indent($type_no)
{
	hook_db_prevoid(ST_MATERIAL_INDENT, $type_no);
	void_stock_move(ST_MATERIAL_INDENT, $type_no);
}

