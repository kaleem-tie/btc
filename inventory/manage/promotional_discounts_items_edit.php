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
$page_security = 'SA_PROMO_DISC';

$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/db_pager.inc");
// page(_($help_context = "Promotional Discounts"));

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/inventory/includes/db/promotional_discounts_items_db.inc");

include($path_to_root . "/includes/ui.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
	
page(_($help_context = "Offers - Items (Edit)"), false, false, "", $js);

simple_page_mode(true);

if (isset($_GET['pd_id'])) {
		
		$_POST['pd_id'] = $_GET['pd_id'];
}


//---------------------------------------------------------------------------------------


if (!isset($_POST['date_']))
{
	$_POST['date_'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['date_']))
		$_POST['date_'] = end_fiscalyear();
}

function can_process()
{
//	global $selected_id, $SysPrefs;
	
	return true;
}

//-------------------------------------------------------------------------------------

if (isset($_POST['ADD_ITEM']) && can_process())
{
	
	$stock_id=implode(',',$_POST["stock_id"]);
	
	
	update_items_promotional_discounts($_POST['pd_id'], $stock_id, $_POST['from_date'], $_POST['to_date'], input_num('disc'), $_POST['offer_name']);
	display_notification(_('Selected offers - items has been updated'));
	
	$path="../manage/promotional_discounts_items.php";
	meta_forward($path);
}



//-------------------------------------------------------------------------------------

if (get_post('_type_update')) 
{
  $Ajax->activate('_page_body');
}
//-------------------------------------------------------------------------------------


start_form();

start_table(TABLESTYLE2);

$existing_comments = "";
$dec = 0;


hidden('pd_id',$_GET['pd_id']);

$myrow    = get_items_promotional_discount($_GET['pd_id']);
$_POST['stock_id']  = explode(',',$myrow["stock_id"]);
$_POST['disc']  = $myrow["disc"];
$_POST['offer_name']  = $myrow["offer_name"];
$_POST['from_date']  = sql2date($myrow["from_date"]);
$_POST['to_date']  = sql2date($myrow["to_date"]);


	
end_table();
start_table(TABLESTYLE2);
br(2);

text_row(_("Offer Name"),'offer_name',null,null,null);
sales_items_list_row_oldbkp(_("Items:"), 'stock_id', null, false, false,false);
date_row(_("From Date:"), 'from_date', '', true);
date_row(_("To Date:"), 'to_date', '', true);
small_amount_row(_("Discount %"), 'disc', percent_format($_POST['disc']), null, null, user_percent_dec());

    
end_table();
br();

submit_center('ADD_ITEM', _("Submit"), true, '', 'default');
br();
	
end_form();
end_page();


