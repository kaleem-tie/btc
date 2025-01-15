<?php
	include('barcode_with_price.php'); // include php barcode 128 class
	// design our barcode display
	echo '<div style="padding-top:1px; margin:5px auto;width:100%;">';
	echo bar128(stripslashes($_GET['barcode']),$_GET['sales_price']);
	echo '</div>';
?>