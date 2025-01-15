<?php
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
if(isset($_GET['type'])){
    $first = $_GET['first'];
    $second = $_GET['second'];
    $third = $_GET['third'];

    if(empty($first)) {
    	$_SESSION['l1first'] = _($help_context = '');
	    $_SESSION['l2second'] = _($help_context = '');
	    $_SESSION['l2third'] = _($help_context = '');
    } else {
    	$_SESSION['l1first'] = _($help_context = $first);
	    $_SESSION['l2second'] = _($help_context = $second);
	    $_SESSION['l2third'] = _($help_context = $third);
    }

    
    print_r($_SESSION);
}