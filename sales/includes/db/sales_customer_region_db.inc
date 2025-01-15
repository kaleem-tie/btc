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

function add_sales_cust_region_masters($cust_region_name, $description)
{
	$sql = "INSERT INTO ".TB_PREF."sales_cust_region(cust_region_name, description)
		VALUES (".db_escape($cust_region_name).",".db_escape($description).")";

	db_query($sql, "could not add Customer Region");
}

function update_sales_cust_region_masters($id, $cust_region_name, $description)
{
	$sql = "UPDATE ".TB_PREF."sales_cust_region SET 
	cust_region_name=".db_escape($cust_region_name).", 
	description=".db_escape($description)."
	WHERE id=".db_escape($id);

	db_query($sql, "could not update Customer Region");
}

function delete_sales_cust_region_masters($id)
{
	$sql="DELETE FROM ".TB_PREF."sales_cust_region WHERE id=".db_escape($id);

	db_query($sql, "could not delete Customer Region");
}

function get_all_sales_cust_region_masters($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."sales_cust_region";
	
	if (!$all) $sql .= " WHERE !inactive";

	return db_query($sql, "could not get all Customer Region");
}

function get_sales_cust_region_masters($id)
{
	$sql = "SELECT * FROM ".TB_PREF."sales_cust_region WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get Customer Region");

	return db_fetch($result);
}

function getvalid_sales_cust_region_masters($cust_region_name)
{
	
     $sql="SELECT COUNT(*) FROM ".TB_PREF."sales_cust_region WHERE cust_region_name=".db_escape($cust_region_name);
		
	$result = db_query($sql, "could not do check empty query");	
	$myrow = db_fetch_row($result);
	return $myrow[0] > 0; 
}

function getvalid_sales_cust_region_masters_edit($cust_region_name,$selected_id)
{
	  $sql="SELECT COUNT(*) FROM ".TB_PREF."sales_cust_region WHERE cust_region_name=".db_escape($cust_region_name)." and id !=".db_escape($selected_id)."";

	$result = db_query($sql, "could not do check empty query");	
	$myrow = db_fetch_row($result);
	return $myrow[0] > 0; 
}


