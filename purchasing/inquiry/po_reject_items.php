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
include($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Outstanding Purchase Order Rejections"), false, false, "", $js);

if (isset($_GET['order_number']))
{
	$_POST['order_number'] = $_GET['order_number'];
}

//---------------------------------------------------------------------------------------

if (isset($_GET['PONumber']))
{
	$order_no = $_GET['PONumber'];
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
	
	
	update_purchase_order_authorise_reject_items($_POST['order_no'],$_POST['po_auth_req'],$_POST['authrise_reject_date'],$_POST['auth_rej_remarks']);
	display_notification(_('Purchase order has been rejected..!'));
	
	$path="../inquiry/po_search.php?";
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


hidden('order_no',$_GET['PONumber']);
	//hidden('selected_id',  $selected_id);

	
end_table();
start_table(TABLESTYLE2);
br(2);

    date_row(_("Reject Date :"), 'authrise_reject_date', '', null, 0, 0, 0);
	textarea_row(_("Remarks :"), 'auth_rej_remarks', null, 18, 5);
end_table();
br();

	submit_center('ADD_ITEM', _("Submit"), true, '', 'default');
	br();
	
	

end_form();
end_page();


