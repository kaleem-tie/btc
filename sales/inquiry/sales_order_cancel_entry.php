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
$page_security = 'SA_SALESORDER_CANCEL';
$path_to_root = "../..";
include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_order_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Sales Order Cancel Entry"), false, false, "", $js);

//---------------------------------------------------------------------------------------

if (isset($_GET['order_no']))
{
	$order_no = $_GET['order_no'];
}
if (isset($_GET['del_status'])){
	$status = $_GET['status'];
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
	
		
		date_default_timezone_set('Asia/Kolkata');
		$date = date('Y-m-d H:i:s');
	
		$order_no=$_POST['order_no'];
		$remarks=$_POST['cancel_remarks'];
		
		$cancel_status=1;
		
		$current_user = $_SESSION['wa_current_user']->loginname;
		if($_POST['del_status'] == ''){
		
		add_cancel_sales_order($_SESSION['Items']);
		
		delete_sales_order(key($_SESSION['Items']->trans_no), $_SESSION['Items']->trans_type);
		display_notification(_('Sales order has been cancelled..!'));
		}
		else{
			
			add_cancel_sales_order($_SESSION['Items']);
		
			close_sales_order($order_no);
			
				
			$sql="UPDATE ".TB_PREF."sales_orders SET
				cancel_status='$cancel_status',
				cancel_date='$date', 
				cancel_remarks='$remarks',
				current_user_id='$current_user'
				WHERE order_no='$order_no' AND trans_type='30'";
				//display_error($sql); die;
				db_query($sql);
				
			display_notification(_("Undelivered part of order has been cancelled as requested."), 1);
		}
	
	
	
	$path="../inquiry/sales_orders_view.php?type=30";
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


hidden('order_no',$_GET['order_no']);
hidden('del_status',$_GET['del_status']);
	//hidden('selected_id',  $selected_id);
	
	
	
end_table();
start_table(TABLESTYLE2);
br(2);

     label_row(_("User : "), $_SESSION['wa_current_user']->loginname);
	textarea_row(_("Reason for Cancel :"), 'cancel_remarks', null, 35, 5);
end_table();
br();

	submit_center('ADD_ITEM', _("Submit"), true, '', 'default');
	br();
	
	

end_form();
end_page();


