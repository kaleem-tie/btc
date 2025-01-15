<?php 
/*--------------------------------------------------\
| Kvcodes    	|               | default.css       |
|---------------------------------------------------|
| For use with:                                    	|
| FrontAccounting 									|
| http://www.kvcodes.com/  	            			|
| by KV varadha                            			|
|---------------------------------------------------|
| Note:                                         	|
| Changes can be made to this CSS that will be  	|
| reflected throughout FrontAccounting.             |
|                                                   |
\--------------------------------------------------*/
$page_security = 'SA_BACKUP';
$path_to_root = "../..";
 global $lte_options;

include_once($path_to_root."/includes/ui.inc");
include_once($path_to_root."/includes/session.inc");
include_once($path_to_root."/themes/LTE/kvcodes.inc");
include_once($path_to_root . "/admin/db/company_db.inc");

if(!function_exists('kv_update_user_theme')){
	function kv_update_user_theme($id, $theme){
		$sql = "UPDATE ".TB_PREF."users SET theme=". db_escape($theme)." WHERE id=".db_escape($id);
		return db_query($sql, "could not update user display prefs for $id");
	}	
}

page(_($help_context = "LTE Theme Options"));

if(isset($_GET['updated'])){
	display_notification("Your Custom Settings Updated Successfully!");
}
if(isset($_POST['submit_options'])){
	$dir =  $path_to_root."/themes/LTE/images";
	if($_FILES['logo']["size"] >0){
		$tmpname = $_FILES['logo']['tmp_name'];		
		$ext = end((explode(".", $_FILES['logo']['name'])));
		$filesize = $_FILES['logo']['size'];
		$filetype = $_FILES['logo']['type'];
		if (file_exists($dir."/".kv_get_option('logo')))
			unlink($dir."/".kv_get_option('logo'));
			
		move_uploaded_file($tmpname, $dir."/kv_logo.".$ext);
		kv_update_option('logo', 'kv_logo.'.$ext);
	}
	
	if($_FILES['favicon']["size"] >0){
		$tmpname = $_FILES['favicon']['tmp_name'];		
		$extn = end((explode(".", $_FILES['favicon']['name'])));
		$filesize = $_FILES['favicon']['size'];
		$filetype = $_FILES['favicon']['type'];
		if (file_exists($dir."/".kv_get_option('favicon')))
			unlink($dir."/".kv_get_option('favicon'));
			
		move_uploaded_file($tmpname, $dir."/kv_favicon.".$extn);
		kv_update_option('favicon', 'kv_favicon.'.$extn);
	}

	if(!isset($_POST['hide_version'])){
		$_POST['hide_version'] = 0;
	}
	if(!isset($_POST['hide_help_link'])){
		$_POST['hide_help_link'] = 0;
	}
	if(!isset($_POST['hide_dashboard'])){
		$_POST['hide_dashboard'] = 0;
	}
		
	kv_update_option('hide_version', $_POST['hide_version']);
	kv_update_option('hide_help_link', $_POST['hide_help_link']);
	kv_update_option('hide_dashboard', $_POST['hide_dashboard']);
	kv_update_option('enable_master_login', check_value('enable_master_login'));

	if(check_value('enable_master_login') == 1 ) {
		if($_SESSION['wa_current_user']->company == 0 && isset($_SESSION['wa_current_user']->user)) {
                $sql = "CREATE TABLE IF NOT EXISTS `0_master_login` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `user_id` varchar(60) NOT NULL,
			  `role` int(11) NOT NULL,
			  `companies` text NOT NULL,
			  PRIMARY KEY(`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;"; 
			            db_query($sql, 'master Table has not Created!');

            $sql1 = db_query("SELECT COUNT(*) FROM 0_master_login WHERE user_id = ".db_escape($_SESSION['wa_current_user']->loginname), "Can't get master_login");
            if(db_num_rows($sql1) == 0){
                 $sql2 = "INSERT INTO 0_master_login (user_id, role, companies) VALUES (".db_escape($_SESSION['wa_current_user']->loginname).", ".db_escape($_SESSION['wa_current_user']->access).", ".db_escape(base64_encode(serialize([$_SESSION['wa_current_user']->company]))).")";
                  db_query($sql2, "Cant insert default master");
          	}   
        }           
   
	}
	
	if(strlen(trim($_POST['powered_name'])) > 0 ){
		kv_update_option('powered_name', $_POST['powered_name']);
	}

	if(strlen(trim($_POST['powered_url'])) > 0 ){
		kv_update_option('powered_url', $_POST['powered_url']);
	}
	// if(strlen(trim($_POST['theme'])) > 0 ){
	// 	kv_update_option('theme', $_POST['theme']);
	// }
	if(strlen(trim($_POST['color_scheme'])) > 0 ){
		kv_update_option('color_scheme', $_POST['color_scheme']);
	}
	if(check_value('set_default_theme')){
		if(!defined('DEFAULT_THEME')){
			$fp = fopen($path_to_root.'/config.php', 'a');//opens file in append mode  
			fwrite($fp, PHP_EOL."define('DEFAULT_THEME','LTE');".PHP_EOL);  
			fclose($fp);  

			$session_inc = file_get_contents($path_to_root.'/includes/session.inc');
			$pt_root = '$path_to_root';
			$login = "if(defined('DEFAULT_THEME') && file_exists($pt_root.'/themes/'.DEFAULT_THEME.'/login.php'))
					include($pt_root . '/themes/'.DEFAULT_THEME.'/login.php');
				else
					include($pt_root . '/access/login.php');";

			$session_inc_1 = str_replace('include($path_to_root . "/access/login.php");', $login, $session_inc); 

			$pwd_reset = "if(defined('DEFAULT_THEME') && file_exists($pt_root.'/themes/'.DEFAULT_THEME.'/password_reset.php'))
					include($pt_root . '/themes/'.DEFAULT_THEME.'/password_reset.php');
				else
					include($pt_root . '/access/password_reset.php');";

			$session_inc_2 = str_replace('include($path_to_root . "/access/password_reset.php");', $pwd_reset, $session_inc_1); 

			file_put_contents($path_to_root.'/includes/session.inc', $session_inc_2);
		} else {
			$config_php = file_get_contents($path_to_root.'/config.php');  
			// using str_replace() function 
			$resStr = str_replace("define('DEFAULT_THEME','".DEFAULT_THEME."');", "define('DEFAULT_THEME','LTE');", $config_php); 

			file_put_contents($path_to_root.'/config.php', $resStr);
		}

		$sql ="ALTER TABLE `".TB_PREF."users` CHANGE `theme` `theme` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'LTE';";
		db_query($sql, "Can't set LTE as default theme");
	}
	if(check_value('set_exist_user')){
		$sql ="UPDATE `".TB_PREF."users` SET `theme`='LTE'  WHERE 1";
		db_query($sql, "Can't set LTE as default theme");
	}
	$_POST['theme'] = clean_file_name($_POST['theme']);
	$chg_theme = $_POST['theme'];
	if ($chg_theme){
		kv_update_user_theme($_SESSION["wa_current_user"]->user, $_POST['theme']);
		$_SESSION["wa_current_user"]->prefs->theme = $_POST['theme'];	
	}
	unset($_FILES);
	unset($_POST);
	if ($chg_theme)
		meta_forward($path_to_root.'/themes/'.$chg_theme.'/theme-options.php', 'updated=yes');	
	else
		meta_forward($_SERVER['PHP_SELF'].'?updated=yes');			
}

if($lte_options['hide_version'] == 0 || $lte_options['hide_version'] == 1 ){
	$_POST['hide_version'] = $lte_options['hide_version']; 
}else{
	$_POST['hide_version']= 0;
}
if($lte_options['hide_dashboard'] == 0 || $lte_options['hide_dashboard'] == 1 ){
	$_POST['hide_dashboard'] = $lte_options['hide_dashboard']; 
}else{
	$_POST['hide_dashboard']= 0;
}

if($lte_options['hide_help_link'] == 0 || $lte_options['hide_help_link'] == 1 ){
	$_POST['hide_help_link'] = $lte_options['hide_help_link']; 
}else{
	$_POST['hide_help_link']= 0;
}

if($lte_options['powered_name'] != 'false'){
	$_POST['powered_name'] = $lte_options['powered_name']; 
}else{
	$_POST['powered_name']= 'FrontAccounting';
}

if($lte_options['powered_url'] != 'false'){
	$_POST['powered_url'] = $lte_options['powered_url']; 
}else{
	$_POST['powered_url']= 'frontaccounting.com';
}

if($lte_options['color_scheme'] != 'false'){
	$_POST['color_scheme'] = $lte_options['color_scheme']; 
}else{
	$_POST['color_scheme']= 'default';
}

if($lte_options['enable_master_login'] != 'false'){
	$_POST['enable_master_login'] = $lte_options['enable_master_login']; 
}else{
	$_POST['enable_master_login']= 0;
}

	start_form(true);
		start_outer_table(TABLESTYLE, "width='60%'");
		table_section(1);
			table_section_title(_("General Options"));

				kv_image_row(_("Upload Logo") . ":", 'logo', 'logo');
				kv_image_row(_("Favicon Icon") . ":", 'favicon', 'favicon');			
				
				text_row(_("Powered Name*:"), 'powered_name', null, 28, 80);	
				text_row(_("Powered By*:"), 'powered_url', null, 28, 80);	
				
				themes_list_row(_("Theme:"), "theme", user_theme());
				LTE_color_schemes(_("Color Schemes:"), "color_scheme", null);

		table_section(2);
			table_section_title(_("Selective Features"));
				check_row(_("Hide Version Details").':', 'hide_version', null);
				check_row(_("Hide Top Help Link").':', 'hide_help_link', null);
				check_row(_("Hide Dashboard").':', 'hide_dashboard', null);
				check_row(_("Set LTE as Default Theme").':', 'set_default_theme', null);
				check_row(_("Set LTE to existing Users").':', 'set_exist_user', null);
				check_row(_("Enable Master Login").':', 'enable_master_login', null);
			//table_section_title(_("General Options"));
			//table_section_title(_("General Options"));
				//text_row(_("Powered Name*:"), 'powered_name', null, 28, 80);	
				//text_row(_("Powered By*:"), 'powered_url', null, 28, 80);
			//$tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
			//print_r($tzlist); 

			//$tzlis =  generate_timezone_list();

			//$options = array('select_submit'=> false,'disabled' => null);
			//echo '<tr><td> Time Zone </td><td>'. array_selector('time_zone', null, $tzlis, $options).'</td> </tr>';

			//print_r($tzlis);

		end_outer_table();
		br();
		submit_center('submit_options', _("Update Options"), _('Theme data'));

	end_form();

br();
br();
end_page(); ?>