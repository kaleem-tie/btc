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

function add_customer_complaint($customer_id,$subject,$description,$complaint_number,
$complaint_against=0,$reference='',$contact_person='',$mobile_number='',
$stock_id='',$filename='', $unique_name='', $filesize='', $filetype='',
$filename2='', $unique_name2='', $filesize2='', $filetype2='',
$filename3='', $unique_name3='', $filesize3='', $filetype3='',$ref_date='', $do_date='')
{
	
	if($ref_date!='')
	$ref_date=date2sql($ref_date);	
	$do_date=date2sql($do_date);	
	
	$current_user_id = $_SESSION['wa_current_user']->user;	
	
	$sql = "INSERT INTO ".TB_PREF."proj_customer_complaint (customer_id,  subject,description,current_user_id,trans_type,complaint_number,complaint_against,
	reference,contact_person,mobile_number,stock_id,
	complaint_filename, complaint_unique_name,complaint_filesize, complaint_filetype,
	complaint_filename2, complaint_unique_name2,complaint_filesize2, complaint_filetype2,
	complaint_filename3, complaint_unique_name3,complaint_filesize3, complaint_filetype3,ref_date,do_date)
		VALUES (".db_escape($customer_id).", ".db_escape($subject) . ", ".db_escape($description) . ",'$current_user_id','101',
		".db_escape($complaint_number) . ",".db_escape($complaint_against) . ",
		".db_escape($reference) . ",".db_escape($contact_person) . ",
		".db_escape($mobile_number).",".db_escape($stock_id).",
		".db_escape($filename).", ".db_escape($unique_name).", 
		".db_escape($filesize).", ".db_escape($filetype).",
		".db_escape($filename2).", ".db_escape($unique_name2).", 
		".db_escape($filesize2).", ".db_escape($filetype2).",
		".db_escape($filename3).", ".db_escape($unique_name3).", 
		".db_escape($filesize3).", ".db_escape($filetype3).", ".db_escape($ref_date).", ".db_escape($do_date).")";   
	db_query($sql,"check failed");
}



?>