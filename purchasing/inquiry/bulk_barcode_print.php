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
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include($path_to_root . "/purchasing/inquiry/barcode_generation.php");
// design our barcode display

$trans_no=$_GET['trans_no'];
$sql="SELECT sitems.stock_id,sitems.quantity FROM ".TB_PREF."supp_invoice_items sitems, ".TB_PREF."stock_master sm WHERE sitems.stock_id=sm.stock_id and sitems.stock_id!='' and sm.mb_flag='B' and sitems.supp_trans_type=20 and sitems.supp_trans_no=".db_escape($trans_no);

 $purchased_items_result = db_query($sql, "could not query stock usage");
	while($purchased_item = db_fetch($purchased_items_result))
	{
		
		for($item_count=0;$item_count<$purchased_item['quantity'];$item_count++)
		{
			echo '<div style="padding-top:1px; margin:5px auto;width:100%;">';
			echo bar128(stripslashes($purchased_item['stock_id']));
			echo '</div>';
		}
	}

?>
