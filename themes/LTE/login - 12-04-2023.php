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
	if (!isset($path_to_root) || isset($_GET['path_to_root']) || isset($_POST['path_to_root']))
		die(_("Restricted access"));
	include_once($path_to_root . "/includes/ui.inc");
	include_once($path_to_root . "/includes/page/header.inc");
	include_once($path_to_root . "/config_db.php");
	include_once($path_to_root . "/config.php");
	include_once($path_to_root . "/includes/db/connect_db.inc");
	set_global_connection($def_coy);
include_once("kvcodes.inc");
create_tbl_option();
//echo $UTF8_fontfile;
	$js = "<script language='JavaScript' type='text/javascript'>
function defaultCompany()
{
	document.forms[0].company_login_name.options[".$_SESSION["wa_current_user"]->company."].selected = true;
}
</script>";
	add_js_file('login.js');
	// Display demo user name and password within login form if "$allow_demo_mode" is true
	if ($SysPrefs->allow_demo_mode == true)
	{
	    $demo_text = _("Login as user: demouser and password: password");
	}
	else
	{
		$demo_text = _("");
    if (@$SysPrefs->allow_password_reset) {
      $demo_text .= "<div class='form-group has-feedback' style='text-align:center'> "._("or")." <a href='$path_to_root/index.php?reset=1'>"._("request new password")."</a></div>";
    }
	}

	if (check_faillog())
	{
		$blocked_msg = '<span class="redfg">'._('Too many failed login attempts.<br>Please wait a while or try later.').'</span>';

	    $js .= "<script>setTimeout(function() {
	    	document.getElementsByName('SubmitUser')[0].disabled=0;
	    	document.getElementById('log_msg').innerHTML='$demo_text'}, 1000*".$SysPrefs->login_delay.");</script>";
	    $demo_text = $blocked_msg;
	}
	if (!isset($def_coy))
		$def_coy = 0;
	$def_theme = "default";

	$login_timeout = $_SESSION["wa_current_user"]->last_act;

	$title = $login_timeout ? _('Authorization Timeout') : $SysPrefs->app_title." ".$version." - "._("Login");

	if(kv_get_option('powered_name') != 'false'){
		$ltitle = kv_get_option('powered_name');
		if(kv_get_option('hide_version') == 0 )
			$ltitle .= " ".$version;
	}else{
		$ltitle = $SysPrefs->app_title; 
	}

	$encoding = isset($_SESSION['language']->encoding) ? $_SESSION['language']->encoding : "iso-8859-1";
	$rtl = isset($_SESSION['language']->dir) ? $_SESSION['language']->dir : "ltr";
	$onload = !$login_timeout ? "onload='defaultCompany()'" : "";
	$onload = !$login_timeout ? "onload='defaultCompany()'" : "";
	if(file_exists(dirname(__FILE__) .'/images/'.kv_get_option('favicon'))){
		$favicon = kv_get_option('favicon').'?'.rand(2,5);
	}else
		$favicon = 'favicon.ico?'.rand(2,5);
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
	echo "<html dir='$rtl' >\n";
	echo "<head profile=\"http://www.w3.org/2005/10/profile\"><title>$ltitle</title>\n";
   	echo "<meta http-equiv='Content-type' content='text/html; charset=$encoding' >\n";
   	echo '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">' ; 
   	echo '  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="'.$path_to_root.'/themes/LTE/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="'.$path_to_root.'/themes/LTE/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="'.$path_to_root.'/themes/LTE/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="'.$path_to_root.'/themes/LTE/css/AdminLTE.css">
  
  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesnt work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">'; ?>
  <style>
.form-group {
	margin-bottom: 0;
}
.login-page, .register-page {
	background: linear-gradient(145deg, #fff 0%, #ddd 100%);
}
.login-box, .register-box {
	background: transparent;
	/*box-shadow: 0 -5px 80px 0 rgba(0, 0, 0, .2)*/
	position: absolute;
	left: 50%;
	top: 50%;
	transform: translate(-50%, -50%);
	margin: 0;
}
.login-logo, .register-logo {
	margin: 15px 0px;
}
.login-box-msg {
	color: #333;
}
.login-box-body, .register-box-body {
	background: transparent;
	padding-top: 0;
}
.form-control {
    border-top: none;
    border-bottom: 1px solid;
	border-color: #e5e9ea;
}
.form-control, .form-control-feedback {
    height: unset;
    padding: 15px 10px;
    border-radius: 0;
}
.form-control:focus {
    border-color: #ccc;
    box-shadow: none;
}
.login-box-body .form-control-feedback, .register-box-body .form-control-feedback {
    height: unset;
    top: 50%;
    transform: translateY(-50%);
    color: #00aeef;
}
.form-group:first-child .form-control {
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
}

.login-box .btn {
    margin-top: 25px;
    border-radius: 6px;
}

.form-group .form-control ab{
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 6px;
}

font {
	color: #01aef0;
	font-family: 'Source Sans Pro', 'Helvetica Neue', Helvetica, Arial, sans-serif;
	font-weight: 400;
	font-size: 28px;
}
  </style>
 	<?php echo "<link href='$path_to_root/themes/LTE/images/$favicon' rel='icon' type='image/x-icon'> \n";
	send_scripts();
	if (!$login_timeout){
		echo $js;
	}
	echo "</head>\n";

	echo "<body class='hold-transition login-page' $onload>\n";

	//echo "<table class='titletext'><tr><td>$title</td></tr></table>\n";
	
	//div_start('login');	
	
	
	echo "<div class='login-box'> <div class='login-logo'>";
	if (!$login_timeout) { // FA logo
    	if(file_exists(dirname(__FILE__) .'/images/'.kv_get_option('logo'))){
			$logo_img = kv_get_option('logo').'?'.rand(2,5);
		}else
			$logo_img = 'LTE.png?'.rand(2,5);
    	echo "<a target='_blank' href='".kv_get_option('powered_url')."'><img src='$path_to_root/themes/LTE/images/$logo_img' alt='".kv_get_option('powered_name')."' onload='fixPNG(this)' border='0' style='max-width: 250px;'/></a>";
	} else { 
		echo "<font size=5>"._('Authorization timeout')."</font>";
	} 
	echo "</div>\n";	

	echo "<input type='hidden' id=ui_mode name='ui_mode' value='".$_SESSION["wa_current_user"]->ui_mode."' >\n"; ?>
	
	<div class="login-box-body">
    <p class="login-box-msg">Sign in to start your session</p>

	<?php start_form(false, false, $_SESSION['timeout']['uri'], "loginform");
	//start_table(false, "class='login'");
	/*if (!$login_timeout){
		if(kv_get_option('hide_version') == 0 )
			table_section_title(_("Version")." $version   Build ".$SysPrefs->build_version ." - "._("Login"));
		else
			table_section_title(_("Login"));
	}*/
	$value = $login_timeout ? $_SESSION['wa_current_user']->loginname : ($SysPrefs->allow_demo_mode ? "demouser":"");	
	
	//text_cells( null, "user_name_entry_field", $value, 20, 30, false, "", "", " placeholder='Username' ");
	echo '<div class="form-group has-feedback">
        <input type="text" class="form-control" placeholder="Username" name="user_name_entry_field">
        <span class="glyphicon glyphicon-user form-control-feedback"></span>
      </div>'; 
	$password = $SysPrefs->allow_demo_mode ? "password":"";

	
      echo '<div class="form-group has-feedback">
        <input type="password" class="form-control ab" placeholder="Password" name="password">
        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
      </div>';

	//password_row('password', $password);
	//echo '<tr> <td><input type="password" name="password" size="20" maxlength="20" value="" placeholder="Password"> </td> </tr>';

	//if ($login_timeout) 
		hidden('company_login_name',0);
// 	} else {
// 		if (isset($_SESSION['wa_current_user']->company))
// 			$coy =  $_SESSION['wa_current_user']->company;
// 		else
// 			$coy = $def_coy;
// 		if (!@$text_company_selection) {
// 			echo "<div class='form-group has-feedback'> <select name='company_login_name' class='form-control'>\n";
// 			for ($i = 0; $i < count($db_connections); $i++)
// 				echo "<option value=$i ".($i==$coy ? 'selected':'') .">" . $db_connections[$i]["name"] . "</option>";
// 			echo "</select>\n";
// 			echo "</div>";
// 		} else {
// //			$coy = $def_coy;
// 			//text_cells(_("Company"), "company_login_nickname", "", 20, 50);
// 			echo '<div class="form-group has-feedback">
//         <input type="text" class="form-control" placeholder="Username" name="company_login_nickname">
//         <span class="glyphicon glyphicon-industry form-control-feedback"></span>    </div>';
// 		}
// 		//start_row();
// 		//label_cell($demo_text, "colspan=2 align='center' id='log_msg'");
// 		//end_row();
// 		echo $demo_text;
// 	} 
	//end_table(1);
	echo "<center><input type='submit' class='btn btn-primary btn-block btn-flat btn-lg' value='&nbsp;&nbsp;"._("Login")."' name='SubmitUser'"
		.($login_timeout ? '':" onclick='set_fullmode();'").(isset($blocked_msg) ? " disabled" : '')." ></center>\n";

	foreach($_SESSION['timeout']['post'] as $p => $val) {
		// add all request variables to be resend together with login data
		if (!in_array($p, array('ui_mode', 'user_name_entry_field', 'password', 'SubmitUser', 'company_login_name'))) 
			if (!is_array($val))
				echo "<input type='hidden' name='$p' value='$val'>";
			else
				foreach($val as $i => $v)
					echo "<input type='hidden' name='{$p}[$i]' value='$v'>";
	}
	end_form(1);
	$Ajax->addScript(true, "document.forms[0].password.focus();");

    echo "<script language='JavaScript' type='text/javascript'>
    //<![CDATA[
            <!--
            document.forms[0].user_name_entry_field.select();
            document.forms[0].user_name_entry_field.focus();
            //-->
    //]]>
    </script>";
	//echo '<div align="center" ><a href="http://www.kvcodes.com/module/LTE-frontaccouting-theme/" target="_blank"> Kvcodes </a> </div> ' ; 
    div_end();
    div_end();
	echo '<!-- jQuery 3 -->
<script src="'.$path_to_root.'/themes/LTE/js/jquery.min.js"></script>
<!-- Bootstrap 3.3.7 -->
<script src="'.$path_to_root.'/themes/LTE/js/bootstrap.min.js"></script>'; 
	echo "</body></html>\n";
	exit;
?>