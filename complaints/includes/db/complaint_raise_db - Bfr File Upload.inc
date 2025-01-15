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
$stock_id='')
{
	
	$current_user_id = $_SESSION['wa_current_user']->user;
		
	$sql = "INSERT INTO ".TB_PREF."proj_customer_complaint (customer_id,  subject,description,current_user_id,trans_type,complaint_number,complaint_against,
	reference,contact_person,mobile_number,stock_id)
		VALUES (".db_escape($customer_id).", ".db_escape($subject) . ", ".db_escape($description) . ",'$current_user_id','101',
		".db_escape($complaint_number) . ",".db_escape($complaint_against) . ",
		".db_escape($reference) . ",".db_escape($contact_person) . ",
		".db_escape($mobile_number).",".db_escape($stock_id).")";

	db_query($sql,"check failed");
}



?>