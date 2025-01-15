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

function add_item_sub_category($code, $item_name, $category_id)
{
	$sql = "INSERT INTO ".TB_PREF."item_sub_category (code, item_name, category_id)
		VALUES (".db_escape($code).",".db_escape($item_name).",".db_escape($category_id).")";
	db_query($sql, "could not add Item subcategory");
}

function update_item_sub_category($id, $code, $item_name, $category_id)
{
	$sql = "UPDATE ".TB_PREF."item_sub_category SET code=".db_escape($code).", item_name=".db_escape($item_name).", category_id=".db_escape($category_id)."
		WHERE id=".db_escape($id);

	db_query($sql, "could not update Item subcategory");
}
function delete_item_sub_category($id)
{
	$sql="DELETE FROM ".TB_PREF."item_sub_category WHERE id=".db_escape($id);

	db_query($sql, "could not delete Item subcategory");
}
function get_all_item_sub_category($all=false,$category_id)
{
	$sql = "SELECT sub.*,stk.description FROM ".TB_PREF."item_sub_category AS sub LEFT JOIN ".TB_PREF."stock_category AS stk ON sub.category_id=stk.category_id WHERE sub.category_id = ".db_escape($category_id)."";
	
	if (!$all) $sql .= " AND !sub.inactive";
	return db_query($sql, "could not get all Item subcategory");
}
function get_item_sub_category($id)
{
	$sql = "SELECT * FROM ".TB_PREF."item_sub_category WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get Item subcategory");

	return db_fetch($result);
}
function get_valid_sub_category_code($code)
{
	
     $sql="SELECT COUNT(*) FROM ".TB_PREF."item_sub_category WHERE code=".db_escape($code);
		
	$result = db_query($sql, "could not do check empty query");	
	$myrow = db_fetch_row($result);
	return $myrow[0] > 0; 
}
function get_valid_sub_category_code_edit($code,$selected_id)
{
	  $sql="SELECT COUNT(*) FROM ".TB_PREF."item_sub_category WHERE code=".db_escape($code)." and id !=".db_escape($selected_id)."";
		
	$result = db_query($sql, "could not do check empty query");	
	$myrow = db_fetch_row($result);
	return $myrow[0] > 0; 
}