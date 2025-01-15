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
function add_brands_promotional_discounts($supplier_id, $from_date, $to_date, $disc, $offer_name)
{
    $from_date = date2sql($from_date);
    $to_date = date2sql($to_date);
    
	$sql = "INSERT INTO ".TB_PREF."brands_promotion_discounts (supplier_id, from_date, to_date, disc, offer_name)
		VALUES ("
		.db_escape($supplier_id).","
		.db_escape($from_date).","
		.db_escape($to_date).","
		.db_escape($disc).",".db_escape($offer_name).")";
	db_query($sql,"a brands promotional discount could not be added");
}

function update_brands_promotional_discounts($id, $supplier_id, $from_date, $to_date, $disc, $offer_name)

{
    $from_date = date2sql($from_date);
    $to_date = date2sql($to_date);
       
	$sql = "UPDATE ".TB_PREF."brands_promotion_discounts SET supplier_id = ".db_escape($supplier_id).",
	from_date = ".db_escape($from_date).", to_date = ".db_escape($to_date).", disc = ".db_escape($disc).",  offer_name=".db_escape($offer_name)."
        WHERE id = ".db_escape($id);
	db_query($sql,"a brands promotional discount could not be updated");
}

function get_all_brands_promotional_discounts()
{
    $sql = "SELECT pd.*, GROUP_CONCAT(s.supp_name) AS supplier_name FROM ".TB_PREF."brands_promotion_discounts pd, ".TB_PREF."suppliers s  WHERE FIND_IN_SET(s.supplier_id,pd.supplier_id) GROUP BY id";
    	 return db_query($sql, "could not get brands promotional discounts");
}

function get_brands_promotional_discount($id)
{
	$sql="SELECT * FROM ".TB_PREF."brands_promotion_discounts WHERE id=".db_escape($id);
	$result = db_query($sql,"a brands promotional discount could not be retrieved");
	return db_fetch($result);
}

function delete_brands_promotional_discount($id)
{
	$sql="DELETE FROM ".TB_PREF."brands_promotion_discounts WHERE id=".db_escape($id);

	db_query($sql,"a brands promotional discount could not be deleted");
}
