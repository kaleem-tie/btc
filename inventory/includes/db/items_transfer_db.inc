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

function add_stock_transfer($Items, $location_from, $location_to, $date_, $reference, $memo_,
$indent_num='',$tot,$indent_id)
{
	global $SysPrefs, $path_to_root, $Refs;

	begin_transaction();
	$args = func_get_args();
	$args = (object)array_combine(array('Items', 'location_from', 'location_to', 
	'date_', 'reference', 'memo_','indent_num','tot','indent_id'), $args);
	$args->trans_no = 0;
	hook_db_prewrite($args, ST_LOCTRANSFER);

	$transfer_id = get_next_trans_no(ST_LOCTRANSFER);

	if ($SysPrefs->loc_notification() == 1)
	{
		include_once($path_to_root . "/inventory/includes/inventory_db.inc");
		$st_ids = array();
		$st_names = array();
		$st_num = array();
		$st_reorder = array();
	}
	
	//  against indent 
	if(($tot=='2') && ($indent_id!='-1')){
	  
		add_transfer_items_transit($location_from, $location_to, $date_, $reference, $transfer_id,$indent_id);
		foreach ($Items as $line_item)
		{
			if ($SysPrefs->loc_notification() == 1)
				$loc = calculate_reorder_level($location_from, $line_item, $st_ids, $st_names, $st_num, $st_reorder);
			add_stock_transfer_item_transit($transfer_id, $line_item->stock_id, $location_from,
				$location_to, $date_, $reference, $line_item->quantity,$indent_id,$line_item->standard_cost,$tot);
		}
	}
	
	else 
	{
	foreach ($Items as $line_item)
	{
		if ($SysPrefs->loc_notification() == 1)
			$loc = calculate_reorder_level($location_from, $line_item, $st_ids, $st_names, $st_num, $st_reorder); 
		add_stock_transfer_item($transfer_id, $line_item->stock_id, $location_from,
			$location_to, $date_, $reference, $line_item->quantity);
	}
	}

	add_comments(ST_LOCTRANSFER, $transfer_id, $date_, $memo_);

	$Refs->save(ST_LOCTRANSFER, $transfer_id, $reference);
	add_audit_trail(ST_LOCTRANSFER, $transfer_id, $date_);

	$args->trans_no = $transfer_id;
	hook_db_postwrite($args, ST_LOCTRANSFER);

	commit_transaction();
	
	if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0)
		send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);

	return $transfer_id;
}

//-------------------------------------------------------------------------------------------------------------

// add 2 stock_moves entries for a stock transfer
// $date_ is display date (not sql)
// std_cost is in HOME currency
// it seems the standard_cost field is not used at all

function add_stock_transfer_item($transfer_id, $stock_id, $location_from, $location_to,
	$date_, $reference, $quantity)
{
	add_stock_move(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_from,
       	$date_, $reference, -$quantity, 0);

	add_stock_move(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_to,
	   	$date_, $reference, $quantity, 0);

}

//-------------------------------------------------------------------------------------------------------------
//
//	Get inventory transfer common data (currently this is still stored in stock_moves table).
//
function get_stock_transfer($trans_no,$intransit=0)
{
	// retrieve common data from any two from/to move records
	
	if($intransit==0)
	$sql = "SELECT loc_from.*, loc_to.*
		FROM
			(SELECT trans_no, type, tran_date, reference, move.loc_code as from_loc, loc.location_name as from_name
			FROM ".TB_PREF."stock_moves move
				LEFT JOIN ".TB_PREF."locations loc ON loc.loc_code=move.loc_code
			WHERE type=".ST_LOCTRANSFER." AND trans_no=".db_escape($trans_no). " AND qty<0 LIMIT 1) loc_from,

			(SELECT move.loc_code as to_loc, loc.location_name as to_name
			FROM ".TB_PREF."stock_moves move
				LEFT JOIN ".TB_PREF."locations loc ON loc.loc_code=move.loc_code
			WHERE type=".ST_LOCTRANSFER." AND trans_no=".db_escape($trans_no). " AND qty>0 LIMIT 1) loc_to";
			
	else
	$sql = "SELECT loc_from.*
		FROM
			(SELECT trans_no, type, tran_date, reference, move.loc_code as from_loc, loc.location_name as from_name
			FROM ".TB_PREF."stock_moves move
				LEFT JOIN ".TB_PREF."locations loc ON loc.loc_code=move.loc_code
			WHERE type=".ST_LOCTRANSFER." AND trans_no=".db_escape($trans_no). " AND qty<0 LIMIT 1) loc_from";	
			

	$result = db_query($sql, "Could not get transfer common data");

	$data = db_fetch($result);

	return $data;
}

//-------------------------------------------------------------------------------------------------------------

function get_stock_transfer_items($trans_no)
{
	$result = get_stock_moves(ST_LOCTRANSFER, $trans_no);

	if (db_num_rows($result) == 0)
	{
		return null;
	}

	return $result;
}

//-------------------------------------------------------------------------------------------------------------

function void_stock_transfer($type_no)
{
	hook_db_prevoid(ST_LOCTRANSFER, $type_no);
	void_stock_move(ST_LOCTRANSFER, $type_no);
}


function get_mir_items($indent_id,$stock_id)
{
	$sql="SELECT * FROM ".TB_PREF."material_indent_request_details WHERE indent_id=".db_escape($indent_id)." AND stock_id=".db_escape($stock_id)."";
	
	
	$res = db_query($sql,"Could not get the material info!");
	return $result = db_fetch($res);
}


function get_mir_total_qty_itemwise($indent_id,$stock_id)
{
	$sql="SELECT (quantity) as leftover_qty FROM ".TB_PREF."material_indent_request_details WHERE indent_id=".db_escape($indent_id)." AND stock_id=".db_escape($stock_id)."";
	
	$res = db_query($sql,"Could not get the material info!");
	$result = db_fetch($res);
	return $result['leftover_qty'];
}

function add_stock_transfer_item_transit($transfer_id, $stock_id, $location_from, $location_to,
	$date_, $reference, $quantity,$indent_num='',$standard_cost=null,$indent_type)
{
	
		 //ravi
      $cost_rate = get_unit_cost($stock_id);
	  $stock_gl_codes = get_stock_gl_code($stock_id);
	  
  
	add_stock_move(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_from,
       	$date_, $reference, -$quantity, $standard_cost,0,$indent_num,$indent_type);
		
		
	/*	
	add_gl_trans_std_cost(ST_LOCTRANSFER, $transfer_id, $date_,
			$stock_gl_codes['inventory_account'], 0, 0, '', -$quantity*$cost_rate );
			
    add_gl_trans_std_cost(ST_LOCTRANSFER, $transfer_id, $date_,
			$intransit_act, 0, 0, '', $quantity*$cost_rate );		
    */			
	
}



  function add_transfer_items_transit($location_from, $location_to, $date_, $reference,$transfer_id,$indent_id)
{
	$sql=" INSERT INTO ".TB_PREF."inventory_received_against_indent (location_from,location_to,date_,reference,transfer_id,indent_id) VALUES(".db_escape($location_from).",".db_escape($location_to).",".db_escape(date2sql($date_)).",".db_escape($reference).",".db_escape($transfer_id).",".db_escape($indent_id).")";
	
	//display_error($sql);
	db_query($sql, "The transit issue cannot be inserted");
	return db_insert_id();
}


//-----------------------------

function add_stock_receive_item($transfer_id, $stock_id, $location_from, $location_to,
	$date_, $reference, $quantity,$receiver_id,$standard_cost=0)
{

	$standard_cost = get_unit_cost($stock_id);
	 
	$stock_gl_codes = get_stock_gl_code($stock_id);
	  
	$stock_transfer_indent = get_stock_transfer_details_info($transfer_id);
	  
	add_stock_move(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_to,
	   	$date_, $reference, $quantity, $standard_cost,0);

				
	$result=get_indent_id($receiver_id);
		$sql="UPDATE ".TB_PREF."material_indent_request_details SET qty_received=qty_received+$quantity WHERE indent_id=".db_escape($result['indent_id'])." AND  stock_id=".db_escape($stock_id)."";
		// display_error($sql);die;
	db_query($sql,"could not update the received status!");
}  

function get_trans_id_from_indent($id)
{
	$sql = "SELECT transfer_id FROM ".TB_PREF."inventory_received_against_indent
			WHERE transfer_id=".db_escape($id);

	$result = db_query($sql,"No transactions were returned");
	if (db_num_rows($result) == 1)
	{
		$row = db_fetch_row($result);
		return $row[0];
	}
}

function get_indent_stock_trasfer($trans_no)
{
$sql = "SELECT trans_no, type, tran_date, reference, move.loc_code as from_loc, loc.location_name as from_name
			FROM ".TB_PREF."stock_moves move
				LEFT JOIN ".TB_PREF."locations loc ON loc.loc_code=move.loc_code
			WHERE type=".ST_LOCTRANSFER." AND trans_no=".db_escape($trans_no). " AND qty<0 LIMIT 1";
	
		$result = db_query($sql, "Could not get transfer common data");
		
	$data = db_fetch($result);

	return $data;		
}

function get_to_location_from_transfers($trans_no)
{
 $sql = "SELECT location_name 
		FROM ".TB_PREF."inventory_received_against_indent indent,".TB_PREF."locations loc
			WHERE indent.location_to=loc.loc_code and indent.transfer_id=".db_escape($trans_no);
			
	$result = db_query($sql, "Could not get transfer common data");

	$data = db_fetch_row($result);

	return $data[0];
} 


function add_stock_received($Items, $location_from, $location_to, $date_, $reference, $memo_,$receiver_id,$transfer_id)
{

	global $SysPrefs, $path_to_root, $Refs;

	begin_transaction();
	$args = func_get_args();
	$args = (object)array_combine(array('Items', 'location_from', 'location_to', 
	'date_', 'reference', 'memo_','receiver_id','transfer_id'), $args);
	$args->trans_no = 0;
	hook_db_prewrite($args, ST_LOCTRANSFER);

	// $transfer_id = get_next_trans_no(ST_LOCTRANSFER);

	if ($SysPrefs->loc_notification() == 1)
	{
		include_once($path_to_root . "/inventory/includes/inventory_db.inc");
		$st_ids = array();
		$st_names = array();
		$st_num = array();
		$st_reorder = array();
	}
	// ramesh  *** against indent ***
	
		foreach ($Items as $line_item)
		{
			if ($SysPrefs->loc_notification() == 1)
				$loc = calculate_reorder_level($location_from, $line_item, $st_ids, $st_names, $st_num, $st_reorder);
				
			add_stock_receive_item($transfer_id, $line_item->stock_id, $location_from,
				$location_to, $date_, $reference, $line_item->quantity,$receiver_id);
		}
	$sql="UPDATE ".TB_PREF."inventory_received_against_indent SET status=1, received_date=".db_escape(date2sql($date_))." WHERE id=".db_escape($receiver_id)."";
	db_query($sql,"could not update the received status!");

	add_comments(ST_LOCTRANSFER, $transfer_id, $date_, $memo_);

	$Refs->save(ST_LOCTRANSFER, $transfer_id, $reference);
	add_audit_trail(ST_LOCTRANSFER, $transfer_id, $date_);

	$args->trans_no = $transfer_id;
	hook_db_postwrite($args, ST_LOCTRANSFER);

	commit_transaction();
	
	if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0)
		send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);

	return $transfer_id;
}


function get_indent_id($id)
{
	$sql ="SELECT indent_id FROM ".TB_PREF."inventory_received_against_indent WHERE id=".db_escape($id)."";
	$res= db_query($sql,"could not get the indent id!");
	return $result =db_fetch($res);
}

function get_stock_transfer_reveiced($trans_no)
{
	// retrieve common data from any two from/to move records
	$sql = "SELECT * FROM ".TB_PREF."stock_moves move WHERE type=".ST_LOCTRANSFER." AND trans_no=".db_escape($trans_no). "";
	
	$result = db_query($sql, "Could not get transfer common data");
	return $result;
}

function get_stock_transfer_details_info($trans_no)
{
	//retrieve common data from any two from/to move records
	$sql = "SELECT * FROM ".TB_PREF."stock_moves move WHERE type=".ST_LOCTRANSFER." AND trans_no=".db_escape($trans_no). " GROUP BY trans_no";
	
	$res = db_query($sql,"Could not get the material info!");
	$result = db_fetch($res);
	return $result;
}

function get_transer_id($receiver_id)
{
	$sql="SELECT transfer_id FROM ".TB_PREF."inventory_received_against_indent WHERE id=".db_escape($receiver_id)."";
	
	
	$res = db_query($sql,"Could not get the material info!");
	$result = db_fetch($res);
	
	return $result['transfer_id'];
}

function get_transer_details($receiver_id)
{
	$sql="SELECT transfer_id,reference FROM ".TB_PREF."inventory_received_against_indent WHERE id=".db_escape($receiver_id)."";
	
	
	$res = db_query($sql,"Could not get the material info!");
	$result = db_fetch($res);
	return $result;
}


function get_inv_adjustment_inquiry($trans_no)
  
  {
	$sql = "SELECT 
			sm.tran_date,
			sm.reference,
			sm.loc_code,
			sm.stock_id,
			sm.adj_type,
			sm1.stock_id,
			lo.loc_code,
			lo.location_name,
			sm1.description,
			sm.qty,
			sm1.units
			FROM ".TB_PREF."stock_moves sm , ".TB_PREF."stock_master sm1, ".TB_PREF."locations lo 
			
			where sm.trans_no = ".db_escape($trans_no)." and type=".ST_INVADJUST." and sm.stock_id = sm1.stock_id and sm.loc_code = lo.loc_code";
	    
	//display_error($sql);
	
		$result = db_query($sql);

	$data = db_fetch($result);

	return $data;
  
  }
  
  
function get_stock_transfer_loc_to1($trans_no)
{
	$sql="SELECT  distinct sm.loc_code,loc.location_name  from ".TB_PREF."stock_moves sm,".TB_PREF."locations loc where sm.loc_code=loc.loc_code and sm.type=16 and sm.qty>0  and sm.trans_no=".db_escape($trans_no);
	$result = db_query($sql, "could not query stock moves");
	$myrow = db_fetch_row($result);
	return $myrow[1]; 
}