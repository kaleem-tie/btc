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
$page_security = 'SA_CREATECOMPANY';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/themes/LTE/includes/users.php");

page(_($help_context = "Master Logins"));
global $db_connections;

//-------------------------------------------------------------------------------------------------

if($_SESSION['wa_current_user']->company != 0 ){
	display_warning(_("The Settings allowed From the main company"));
	end_page();

}
if (isset($_POST['update']) && $_POST['update'] != ""){

	$companies = [];
	foreach($db_connections as $cid => $single){
		if(check_value($cid))
			$companies[] = $cid;
	}
	$companies = base64_encode(serialize($companies));
	if (isset($_POST['master_id'])  ){
    	update_master_login($_POST['master_id'], $_POST['user_id'], $_POST['role_id'], $companies);    	
    }  	else  	{
    	add_master_login($_POST['user_id'], $_POST['role_id'], $companies); // check_value('rep_popup'), $_POST['pos']);			
		
   	}
   	display_notification_centered(_("The selected user access has been updated."));
}


start_form();

if (db_has_users()) {
	start_table(TABLESTYLE_NOBORDER);
	start_row();
	users_list_cells(_("Select an User: "), 'user_id', null,true,_('Select'));	
	end_row();
	end_table();
	if (get_post('user_id')) {
		$Ajax->activate('user_id');
		set_focus('user_id');
	}
} else {
	hidden('user_id');
}

if(get_post('user_id')  != '' ){
	//-------------------------------------------------------------------------------------------------
	start_table(TABLESTYLE2);

	$row = get_master_login(get_post('user_id'));
	if($row['role'])
		$_POST['role_id'] = $row['role'];
	else
		$_POST['role_id'] = check_user_role_for_master($_POST['user_id']);
	security_roles_list_row(_("Access Level:"), 'role_id', null); 
	check_row(_("Check All Companies"), 'select_all', null, true);
	if($row['id'])
		hidden('master_id',$row['id']);
	end_table();
	start_outer_table(TABLESTYLE2);
	$data = @unserialize(base64_decode($row['companies']));
	if($data !== false)
		$companies = unserialize(base64_decode($row['companies']));
	$vj = $kv = 1; 
  	foreach($db_connections as $cid => $single){
	  	if($vj == 1)
	  		table_section($kv);			
	  	if(isset($companies) && in_array($cid, $companies))
	  		$_POST[$cid] = 1;
	  	elseif(check_value('select_all'))
	  		$_POST[$cid] = 1;
	  	else
	  		$_POST[$cid] = 0;
	  	check_row($single['name'], $cid, null);

	  	$vj++;
	  	if($vj>25){
	  		$vj=1;
	  		$kv++;
	  	}
  	}

	end_outer_table(1);

	submit_center('update', _("Submit"), true, '',  'default');
}

end_form();
end_page();

function db_has_users(){
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."users");
}