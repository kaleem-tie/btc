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

function get_all_loc_dim_cashact_details()
{
  $sql = "SELECT ldc.id,concat(loc.loc_code,' ',loc.location_name) AS location,concat(dim.reference,' ',dim.name) AS dimension,concat(bank.account_code,' ',bank.bank_account_name) as bank_name FROM ".TB_PREF."location_dimension_cashaccount AS ldc,".TB_PREF."locations AS loc,".TB_PREF."dimensions AS dim,".TB_PREF."bank_accounts AS bank  WHERE ldc.loc_code=loc.loc_code and ldc.dimension_id=dim.id and ldc.cash_account_id=bank.id";
	
	return db_query($sql, "could not get all Detail Category");
}

function add_loc_dim_cashact($loc_code,$dimension_id,$cash_account_id)
{
	$sql = "INSERT INTO ".TB_PREF."location_dimension_cashaccount (loc_code, dimension_id, cash_account_id)
		VALUES (".db_escape($loc_code).",".db_escape($dimension_id).",".db_escape($cash_account_id).")";
	db_query($sql, "could not add Detail Category");
}

function delete_loc_dim_cashact($id)
{
	$sql="DELETE FROM ".TB_PREF."location_dimension_cashaccount WHERE id=".db_escape($id);

	db_query($sql, "could not delete Detail Category");
}