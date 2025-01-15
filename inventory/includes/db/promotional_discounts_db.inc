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
function add_promotional_discounts($category_id, $from_date, $to_date, $disc, $offer_name)
{
    $from_date = date2sql($from_date);
    $to_date = date2sql($to_date);
    
	$sql = "INSERT INTO ".TB_PREF."promotion_discounts (category_id, from_date, to_date, disc, offer_name)
		VALUES ("
		.db_escape($category_id).","
		.db_escape($from_date).","
		.db_escape($to_date).","
		.db_escape($disc).",".db_escape($offer_name).")";
	db_query($sql,"a promotional discount could not be added");
}

function update_promotional_discounts($id, $category_id, $from_date, $to_date, $disc, $offer_name)

{
    $from_date = date2sql($from_date);
    $to_date = date2sql($to_date);
       
	$sql = "UPDATE ".TB_PREF."promotion_discounts SET category_id = ".db_escape($category_id).",
	from_date = ".db_escape($from_date).", to_date = ".db_escape($to_date).", disc = ".db_escape($disc).",  offer_name=".db_escape($offer_name)."
        WHERE id = ".db_escape($id);
	db_query($sql,"a promotional discount could not be updated");
}

function get_promotional_discounts()
{
    $sql = "SELECT pd.*, GROUP_CONCAT(c.description) AS category_name FROM ".TB_PREF."promotion_discounts pd, ".TB_PREF."stock_category c  WHERE FIND_IN_SET(c.category_id,pd.category_id) GROUP BY id";
    	 return db_query($sql, "could not get promotional discounts");
}

function get_promotional_discount($id)
{
	$sql="SELECT * FROM ".TB_PREF."promotion_discounts WHERE id=".db_escape($id);
	$result = db_query($sql,"a promotional discount could not be retrieved");
	return db_fetch($result);
}

function delete_promotional_discount($id)
{
	$sql="DELETE FROM ".TB_PREF."promotion_discounts WHERE id=".db_escape($id);

	db_query($sql,"a promotional discount could not be deleted");
}
