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

function add_sales_cust_class_masters($cust_class_name, $description)
{
	$sql = "INSERT INTO ".TB_PREF."sales_cust_class(cust_class_name, description)
		VALUES (".db_escape($cust_class_name).",".db_escape($description).")";

	db_query($sql, "could not add Customer Class");
}

function update_sales_cust_class_masters($id, $cust_class_name, $description)
{
	$sql = "UPDATE ".TB_PREF."sales_cust_class SET 
	cust_class_name=".db_escape($cust_class_name).", 
	description=".db_escape($description)."
	WHERE id=".db_escape($id);

	db_query($sql, "could not update Customer Class");
}

function delete_sales_cust_class_masters($id)
{
	$sql="DELETE FROM ".TB_PREF."sales_cust_class WHERE id=".db_escape($id);

	db_query($sql, "could not delete Customer Class");
}

function get_all_sales_cust_class_masters($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."sales_cust_class";
	
	if (!$all) $sql .= " WHERE !inactive";

	return db_query($sql, "could not get all Customer Class");
}

function get_sales_cust_class_masters($id)
{
	$sql = "SELECT * FROM ".TB_PREF."sales_cust_class WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get Customer Class");

	return db_fetch($result);
}

function getvalid_sales_cust_class_masters($cust_class_name)
{
	
     $sql="SELECT COUNT(*) FROM ".TB_PREF."sales_cust_class WHERE cust_class_name=".db_escape($cust_class_name);
		
	$result = db_query($sql, "could not do check empty query");	
	$myrow = db_fetch_row($result);
	return $myrow[0] > 0; 
}

function getvalid_sales_cust_class_masters_edit($cust_class_name,$selected_id)
{
	  $sql="SELECT COUNT(*) FROM ".TB_PREF."sales_cust_class WHERE cust_class_name=".db_escape($cust_class_name)." and id !=".db_escape($selected_id)."";

	$result = db_query($sql, "could not do check empty query");	
	$myrow = db_fetch_row($result);
	return $myrow[0] > 0; 
}


