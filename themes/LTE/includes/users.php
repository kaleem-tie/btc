<?php

function add_master_login($user_id, $role, $companies){
	UpdateLoginAccounts($user_id, $companies);
	$sql = "INSERT INTO 0_master_login (user_id, role, companies) VALUES (".db_escape($user_id).", ".db_escape($role).", ".db_escape($companies) ." )";

	return db_query($sql, "could not add user for $user_id");
}

//-----------------------------------------------------------------------------------------------

function update_master_login($id, $user_id, $role, $companies){
	UpdateLoginAccounts($user_id, $companies);
	$sql = "UPDATE 0_master_login SET role=".db_escape($role).", companies=".db_escape($companies).", user_id=".db_escape($user_id)." WHERE id=" . db_escape($id);
	return db_query($sql, "could not update user for $user_id");
}

//-----------------------------------------------------------------------------------------------
function get_master_login($id){
	$sql = "SELECT * FROM 0_master_login WHERE user_id=".db_escape($id);
	$result = db_query($sql, "could not get user $id");
	return db_fetch($result);
}

//-----------------------------------------------------------------------------------------------
function check_user_role_for_master($id) {
	$sql = "SELECT role_id FROM ".TB_PREF."users WHERE user_id=". db_escape($id);
	$result = db_query($sql,"Cant check user activity");
	$ret = db_fetch($result);

	return $ret[0];
}

function Check_user_login_exist($userid, $cid) {
	global $db_connections;
	$db_table_name = $db_connections[$cid]['dbname'].'.'.$db_connections[$cid]['tbpref'];
	$sql = "SELECT id FROM ".$db_table_name."users WHERE user_id =".db_escape($userid)." LIMIT 1";
	return check_empty_result($sql);
}
function UpdateLoginAccounts($userid, $companies){
	global $db_connections;
	$comapnies = unserialize(base64_decode($companies));
	$user_details = get_user_master_login($userid);
	foreach($comapnies as $cid){
		if(!Check_user_login_exist($userid, $cid)){
			$db_table_name = $db_connections[$cid]['dbname'].'.'.$db_connections[$cid]['tbpref'];
			$sql0 = "INSERT INTO ".$db_table_name."users (user_id, real_name, password, phone, email, role_id, language, pos, print_profile, rep_popup) VALUES (".db_escape($user_details['user_id']).", ".db_escape($user_details['real_name']).", ".db_escape($user_details['password']) .",".db_escape($user_details['phone']).",".db_escape($user_details['email']).", ".db_escape($user_details['role_id']).", ".db_escape($user_details['language']).", ".db_escape($user_details['pos']).",".db_escape($user_details['print_profile']).",".db_escape($user_details['rep_popup'])." )";
			db_query($sql0, "could not add user for $userid");
		}
	}
}

//-----------------------------------------------------------------------------------------------
function get_user_master_login($id){
	$sql = "SELECT * FROM ".TB_PREF."users WHERE user_id=".db_escape($id);
	$result = db_query($sql, "could not get user $id");
	return db_fetch($result);
}
/*
{"user":"8",
"loginname":"kvvaradha14",
"username":"kvvaradha14",
"name":"",
"email":null,
"company":0,
"pos":"1"
,"access":"3","timeout":"1800","last_act":1555217586,"role_set":["257","258","259","260","513","514","515","516","517","518","519","520","521","522","523","524","525","526","769","770","771","772","773","774","775","2817","2818","2819","2820","2821","2822","2823","3073","3074","3082","3075","3076","3077","3078","3079","3080","3081","3329","3330","3331","3332","3333","3334","3335","5377","5633","5634","5635","5636","5637","5641","5638","5639","5640","5889","5890","5891","7937","7938","7939","7940","8193","8194","8195","8196","8197","8449","8450","8451","9217","9218","9220","9473","9474","9475","9476","9729","10497","10753","10754","10755","10756","10757","11009","11010","11011","11012","13057","13313","13314","13315","15617","15618","15619","15620","15621","15622","15623","15624","15628","15625","15626","15627","15629","15873","15874","15875","15876","15877","15878","15879","15880","15883","15881","15882","15884","16129","16130","16131","16132","746596","812132"],"old_db":false,"logged":true,"ui_mode":"1","prefs":{"language":"C","qty_dec":"2","price_dec":"2","exrate_dec":"4","percent_dec":"1","show_gl_info":"1","show_codes":"0","date_format":"1","date_sep":"0","tho_sep":"1","dec_sep":"1","theme":"vanigam","print_profile":"","rep_popup":"1","pagesize":"A4","show_hints":"0","query_size":"10","graphic_links":"1","sticky_date":"0","startup_tab":"orders","transaction_days":"30","save_report_selection":null,"use_date_picker":"1","def_print_destination":"0","def_print_orientation":"0","save_report_selections":"0"},"cur_con":0}
*/