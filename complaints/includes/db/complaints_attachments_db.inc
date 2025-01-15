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


function add_complaints_attachments($complaint_id,$filename='',$unique_name='',$filetype='',$filesize='')
{
    //$warranty_date = date2sql($warranty_date);

	$sql = "INSERT INTO ".TB_PREF."complaints_attachments(complaint_id,filename,unique_name,filetype,filesize)
		VALUES(".db_escape($complaint_id).",".db_escape($filename).",".db_escape($unique_name).",".db_escape($filetype).",".db_escape($filesize).")";
		
	db_query($sql, "could not add complaint attachments");
}

function update_complaints_attachments($id,$filename='',$unique_name='',$filetype='',$filesize='')
{

	$sql = "UPDATE ".TB_PREF."complaints_attachments SET 
	filename=".db_escape($filename).",
	unique_name=".db_escape($unique_name).",
	filetype=".db_escape($filetype).",
	filesize=".db_escape($filesize)."
	WHERE id=".db_escape($id);
	db_query($sql, "could not update Project");
}


function get_complaints_attachments($id)
{
	$sql = "SELECT * FROM ".TB_PREF."complaints_attachments WHERE id=".db_escape($id);

	$result = db_query($sql, "could not Project");

	return db_fetch($result);
}


