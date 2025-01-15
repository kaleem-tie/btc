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
$page_security = 'SA_SALES_DELIVERY_PLAN';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Sales Delivery Plan"), false, false, "", $js);

if (isset($_GET['order_no']))
{
	$_POST['order_no'] = $_GET['order_no'];
}

if (isset($_GET['order_line_id']))
{
	$_POST['order_line_id'] = $_GET['order_line_id'];
}

if (isset($_GET['planned_from_date']))
{
	$_POST['planned_from_date'] = $_GET['planned_from_date'];
}

if (isset($_GET['planned_from_time']))
{
	$_POST['planned_from_time'] = $_GET['planned_from_time'];
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
	
	if (check_value('all_items_applicable') != 0) 
	{
	 $so_details=get_all_sales_order_item_details($_POST['order_no']);
	  while ($so_item = db_fetch($so_details)) 
	  {	
	    update_sales_order_plan($_POST['order_no'],$so_item['id'],1);
	    add_sales_delivery_plan($_POST['order_no'],$so_item['id'],
	     $_POST['update_reason'],$_POST['planned_date'],$_POST['planned_delivery_time']);	
	  }
	}	
	else{
	update_sales_order_plan($_POST['order_no'],$_POST['order_line_id'],1);
	
	add_sales_delivery_plan($_POST['order_no'],$_POST['order_line_id'],
	$_POST['update_reason'],$_POST['planned_date'],$_POST['planned_delivery_time']);
	}
	
	display_notification(_('Sales delivery has been planned..!'));
	
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
hidden('order_line_id',$_GET['order_line_id']);
hidden('planned_from_date',$_GET['planned_from_date']);
hidden('planned_from_time',$_GET['planned_from_time']);


$result = get_all_sales_delivery_plan_details($_POST['order_no'],$_POST['order_line_id']);
div_start('delivery_table');
start_table(TABLESTYLE, "width='50%'");
$th = array(_("S.No"), _("Planned Delivery Date"), _("Planned Delivery Time"), 
_("Reason for Update"));
table_header($th);
$k = 0; //row colour counter
$n=1;
global $delivery_times;
while ($myrow = db_fetch($result))
{

	alt_table_row_color($k);
	
	label_cell($n,'align=center');
	label_cell(sql2date($myrow["planned_date"]),'align=center');
	label_cell($delivery_times[$myrow["planned_delivery_time"]],'align=center');
	label_cell($myrow["update_reason"]);
    end_row();
    $n++;
}
end_table();
if (db_num_rows($result) == 0)
{
	display_note(_("There are no planned deliveries set up for this item."), 1);
}

$_POST['planned_date']          = sql2date($_POST['planned_from_date']);
$_POST['planned_delivery_time'] = $_POST['planned_from_time'];

start_table(TABLESTYLE2);
br(2);

    textarea_row(_("Reason for Update :"), 'update_reason', null, 18, 5);
    date_row(_("Planned Delivery Date :"), 'planned_date', '', null, 0, 0, 0);
	
	delivery_times_list_row(_("Planned Delivery Time:"),'planned_delivery_time',null,false);
	
	check_row(_('Applicable to All Items:'), 'all_items_applicable', null, true);
	
end_table();
br();

	submit_center('ADD_ITEM', _("Submit"), true, '', 'default');
	br();
	
	

end_form();
end_page();


