<?php
define("FA_LOGOUT_PHP_FILE","");

$page_security = 'SA_OPEN';
$path_to_root="../..";
include($path_to_root . "/includes/session.inc");
add_js_file('login.js');

include($path_to_root . "/includes/page/header.inc");
page_header(_("Logout"), true, false, '');
end_page(false, true);
session_unset();
@session_destroy();
header("location:".$path_to_root);
?>