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
function add_tax_group($name, $taxes, $tax_shippings, $tax_additional, $tax_packing, $tax_other )
{
	begin_transaction();

	$sql = "INSERT INTO ".TB_PREF."tax_groups (name) VALUES (".db_escape($name).")";
	db_query($sql, "could not add tax group");
	
	$id = db_insert_id();
	
	add_tax_group_items($id, $taxes, $tax_shippings, $tax_additional, $tax_packing, $tax_other);	
	
	commit_transaction();	
}

function update_tax_group($id, $name, $taxes, $tax_shippings, $tax_additional, $tax_packing, $tax_other)
{
	begin_transaction();	

    $sql = "UPDATE ".TB_PREF."tax_groups SET name=".db_escape($name)." WHERE id=".db_escape($id);
	db_query($sql, "could not update tax group");
	
	delete_tax_group_items($id);
	add_tax_group_items($id, $taxes, $tax_shippings, $tax_additional, $tax_packing, $tax_other);	
	
	commit_transaction();			
}

function get_all_tax_groups($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."tax_groups";
	if (!$all) $sql .= " WHERE !inactive";
	
	return db_query($sql, "could not get all tax group");
} 

function get_tax_group($type_id)
{
	$sql = "SELECT * FROM ".TB_PREF."tax_groups WHERE id=".db_escape($type_id);
	
	$result = db_query($sql, "could not get tax group");
	
	return db_fetch($result);
}

function delete_tax_group($id)
{
	begin_transaction();
		
	$sql = "DELETE FROM ".TB_PREF."tax_groups WHERE id=".db_escape($id);
		
	db_query($sql, "could not delete tax group");
	
	delete_tax_group_items($id);	
	
	commit_transaction();
}

function add_tax_group_items($id, $items, $tax_shippings, $tax_additional, $tax_packing, $tax_other)
{
	for ($i=0; $i < count($items); $i++) 
	{
		$sql = "INSERT INTO ".TB_PREF."tax_group_items (tax_group_id, tax_type_id, tax_shipping,tax_additional,tax_packing,tax_other)
			VALUES (".db_escape($id).",  ".db_escape($items[$i]).", " . $tax_shippings[$i] .", 
			" . $tax_additional[$i] .", " . $tax_packing[$i] .", " . $tax_other[$i] .")";
		db_query($sql, "could not add item tax group item");					
	}		
}

function delete_tax_group_items($id)
{
	$sql = "DELETE FROM ".TB_PREF."tax_group_items WHERE tax_group_id=".db_escape($id);
	
	db_query($sql, "could not delete item tax group items");					
}

//
//	Return all tax types with rate value updated according to tax group selected
//	Call the function without arg to find shipment group taxes.
//
function get_tax_group_rates($group_id=null, $tax_shipping=false, $tax_additional=false, $tax_packing=false, $tax_other=false)
{
	global $SysPrefs;

	$sql = 
	"SELECT t.id as tax_type_id,"
		.($SysPrefs->suppress_tax_rates() == 1 ? "t.name as tax_type_name,"
			: "CONCAT(t.name, ' (', t.rate, '%)') as tax_type_name,")
		."t.sales_gl_code,
		  t.purchasing_gl_code,
		  IF(g.tax_type_id, t.rate, NULL) as rate,
		  g.tax_shipping, g.tax_additional, g.tax_packing, g.tax_other
		FROM ".TB_PREF."tax_types t 
		  LEFT JOIN ".TB_PREF."tax_group_items g ON t.id=g.tax_type_id
	AND g.tax_group_id=". ($group_id ? db_escape($group_id) : "(SELECT MIN(id) FROM ".TB_PREF."tax_groups)")		  
	. " WHERE !t.inactive";
	if ($tax_shipping)
		$sql .= " AND g.tax_shipping=1";
		
	if ($tax_additional)
		$sql .= " AND g.tax_additional=1";

    if ($tax_packing)
		$sql .= " AND g.tax_packing=1";

    if ($tax_other)
		$sql .= " AND g.tax_other=1";		
//display_error($sql);
	return db_query($sql, "cannot get tax types as array");
}

function get_tax_group_items_as_array($id)
{
	$ret_tax_array = array();
	
	$tax_group_items = get_tax_group_rates($id);
	
	while ($tax_group_item = db_fetch($tax_group_items)) 
	{
		$tax_group_item['Value'] = 0;
		$ret_tax_array[$tax_group_item['tax_type_id']] = $tax_group_item;

	}
	
	return $ret_tax_array;
}

function get_shipping_tax_as_array($id=null)
{
	$ret_tax_array = array();

	$tax_group_items = get_tax_group_rates($id, true);

	while ($tax_group_item = db_fetch($tax_group_items)) 
	{
		$tax_group_item['Value'] = 0;
		$ret_tax_array[$tax_group_item['tax_type_id']] = $tax_group_item;
	}
	
	return $ret_tax_array;
}


//-------------------------------------------------  //Rajesh -- 01-11-2021
// Additional tax
function get_additional_tax_as_array($id=null)
{
	$ret_tax_array = array();

	$tax_group_items = get_tax_group_rates($id, true);

	while ($tax_group_item = db_fetch($tax_group_items)) 
	{
		$tax_group_item['Value'] = 0;
		$ret_tax_array[$tax_group_item['tax_type_id']] = $tax_group_item;
	}
	
	return $ret_tax_array;
}

// Packing tax
function get_packing_tax_as_array($id=null)
{
	$ret_tax_array = array();

	$tax_group_items = get_tax_group_rates($id, true);

	while ($tax_group_item = db_fetch($tax_group_items)) 
	{
		$tax_group_item['Value'] = 0;
		$ret_tax_array[$tax_group_item['tax_type_id']] = $tax_group_item;
	}
	
	return $ret_tax_array;
}

// Other tax
function get_other_tax_as_array($id=null)
{
	$ret_tax_array = array();

	$tax_group_items = get_tax_group_rates($id, true);

	while ($tax_group_item = db_fetch($tax_group_items)) 
	{
		$tax_group_item['Value'] = 0;
		$ret_tax_array[$tax_group_item['tax_type_id']] = $tax_group_item;
	}
	
	return $ret_tax_array;
}


function get_gst_taxes_ids($id){
$sql = "SELECT * FROM ".TB_PREF."tax_group_items WHERE tax_group_id = '".$id."'";
//display_error($sql);
return db_query($sql);
}
