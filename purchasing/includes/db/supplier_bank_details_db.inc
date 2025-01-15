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


function add_supplier_bank_account($supp_bank_name, $supp_bank_account_no,$supplier_id,$supp_bank_branch,$supp_iban,$supp_swift)
{
	$sql = "INSERT INTO ".TB_PREF."supplier_bank_details (supp_bank_name,supp_bank_account_no,supplier_id,supp_bank_branch,supp_iban,supp_swift) 
		VALUES (".db_escape($supp_bank_name).", ".db_escape($supp_bank_account_no).",".db_escape($supplier_id).",".db_escape($supp_bank_branch).",".db_escape($supp_iban).",".db_escape($supp_swift).")";
		
	db_query($sql,"a jobcard debtors could not be added");
	
}
function get_supplier_bank_account_edit($id)
{
	$sql = "SELECT * FROM ".TB_PREF."supplier_bank_details WHERE id=".db_escape($id);
	$result = db_query($sql,"jobcard debtors could not be retrieved");
	return db_fetch($result);
}
function delete_supplier_bank_account($id)
{
	$sql="DELETE FROM ".TB_PREF."supplier_bank_details WHERE id= ".db_escape($id);
	db_query($sql,"jobcard debtors could not be deleted");
}
function update_supplier_bank_account($id,$supp_bank_name, $supp_bank_account_no,$supplier_id,$supp_bank_branch,$supp_iban,$supp_swift)
{
	$sql = "UPDATE ".TB_PREF."supplier_bank_details SET supp_bank_name=".db_escape($supp_bank_name).", 
		supp_bank_account_no=".db_escape($supp_bank_account_no).",
		supplier_id=".db_escape($supplier_id).",supp_bank_branch=".db_escape($supp_bank_branch).",
		supp_iban=".db_escape($supp_iban).",supp_swift=".db_escape($supp_swift)."
		WHERE id=".db_escape($id);
		
	db_query($sql," jobcard debtors could not be updated");
}
function get_all_supplier_bank_account($supplier_id)
{
	$sql = "SELECT * FROM ".TB_PREF."supplier_bank_details AS s_details,".TB_PREF."suppliers supp  WHERE s_details.supplier_id=".db_escape($supplier_id)." AND  supp.supplier_id=s_details.supplier_id";
	

	return db_query($sql, "could not get all Grade");
}
