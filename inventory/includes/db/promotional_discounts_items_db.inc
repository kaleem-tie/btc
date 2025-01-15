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
function add_items_promotional_discounts($stock_id, $from_date, $to_date, $disc, $offer_name)
{
    $from_date = date2sql($from_date);
    $to_date = date2sql($to_date);
    
	$sql = "INSERT INTO ".TB_PREF."items_promotion_discounts (stock_id, from_date, to_date, disc, offer_name)
		VALUES ("
		.db_escape($stock_id).","
		.db_escape($from_date).","
		.db_escape($to_date).","
		.db_escape($disc).",".db_escape($offer_name).")";
	db_query($sql,"a items promotional discount could not be added");
}

function update_items_promotional_discounts($id, $stock_id, $from_date, $to_date, $disc, $offer_name)

{
    $from_date = date2sql($from_date);
    $to_date = date2sql($to_date);
       
	$sql = "UPDATE ".TB_PREF."items_promotion_discounts SET stock_id = ".db_escape($stock_id).",
	from_date = ".db_escape($from_date).", to_date = ".db_escape($to_date).", 
	disc = ".db_escape($disc).",  offer_name=".db_escape($offer_name)."
     WHERE id = ".db_escape($id);

		
	db_query($sql,"a items promotional discount could not be updated");
}

function get_all_items_promotional_discounts()
{
    $sql = "SELECT pd.*, GROUP_CONCAT(s.description) AS item_name FROM ".TB_PREF."items_promotion_discounts pd, ".TB_PREF."stock_master s  WHERE FIND_IN_SET(s.stock_id,pd.stock_id) GROUP BY id";
    return db_query($sql, "could not get items promotional discounts");
}

function get_items_promotional_discount($id)
{
	$sql="SELECT * FROM ".TB_PREF."items_promotion_discounts WHERE id=".db_escape($id);
	$result = db_query($sql,"a items promotional discount could not be retrieved");
	return db_fetch($result);
}

function delete_items_promotional_discount($id)
{
	$sql="DELETE FROM ".TB_PREF."items_promotion_discounts WHERE id=".db_escape($id);

	db_query($sql,"a items promotional discount could not be deleted");
}


function get_all_pagination_items_promotional_discounts()
{

    $sql = "SELECT pd.offer_name,pd.stock_id,GROUP_CONCAT(s.description) AS item_name,
    pd.from_date,pd.to_date,pd.disc,pd.id	
	FROM ".TB_PREF."items_promotion_discounts pd,".TB_PREF."stock_master s 
	WHERE FIND_IN_SET(s.stock_id,pd.stock_id) 
	GROUP BY pd.id";
    return $sql;
}