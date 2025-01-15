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
page("Offers - Items", false, false, "", $js);
simple_page_mode(true);
//-----------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	$input_error = 0;
	
	 if (strlen($_POST['offer_name']) == 0 ) 
	{
		$input_error = 1;
		display_error(_("Offer name should not be empty."));
		set_focus('offer_name');
		return false;
	}
	
	if (empty($_POST["stock_id"]))
	{
		$input_error = 1;
		display_error(_("Item Item should not be empty.!"));
		set_focus('stock_id');
		return false;
	}
    	if (!is_date($_POST['from_date'])) 
	{
	    	$input_error = 1;
		display_error(_("The entered from date is invalid."));
		set_focus('from_date');
		return false;
	} 
	elseif (!is_date_in_fiscalyear($_POST['from_date'])) 
	{
	    $input_error = 1;
		display_error(_("The entered from date is out of fiscal year or is closed for further data entry."));
		set_focus('from_date');
		return false;
	}
		if (!is_date($_POST['to_date'])) 
	{
	    	$input_error = 1;
		display_error(_("The entered to date is invalid."));
		set_focus('to_date');
		return false;
	} 
	elseif (!is_date_in_fiscalyear($_POST['to_date'])) 
	{
	    $input_error = 1;
		display_error(_("The entered to date is out of fiscal year or is closed for further data entry."));
		set_focus('to_date');
		return false;
	}
    if($_POST['from_date']>$_POST['to_date'])
    {
         $input_error = 1;
		display_error(_("The entered fromdate should not more than the todate!"));
		set_focus('from_date');
		return false;
    }
    if (!check_num('disc', 0))
	{
	      $input_error = 1;
		display_error(_("The entered disount is negative or invalid."));
		set_focus('disc');
		return false;
	}
	if (input_num('disc')>100)
	{
	      $input_error = 1;
		display_error(_("The disount should not be more than the 100."));
		set_focus('disc');
		return false;
	}
    
	if ($input_error != 1) 
	{
		$stock_id=implode(',',$_POST["stock_id"]);
		
    	if ($selected_id != -1) 
    	{
    		update_items_promotional_discounts($selected_id, $stock_id, $_POST['from_date'], $_POST['to_date'], input_num('disc'), $_POST['offer_name']);
			display_notification(_('Selected offers - items has been updated'));
    	} 
    	else 
    	{
    		add_items_promotional_discounts($stock_id, $_POST['from_date'], $_POST['to_date'], input_num('disc'), $_POST['offer_name']);
			display_notification(_('New offers - items has been added'));
    	}
		$Mode = 'RESET';
	}
} 

//-----------------------------------------------------------------------------------
if ($Mode == 'Delete')
{
		delete_items_promotional_discount($selected_id);
		display_notification(_('Selected offers - items has been deleted'));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}
//-----------------------------------------------------------------------------------

 $result = get_all_items_promotional_discounts(check_value('show_inactive'));

start_form();
start_table(TABLESTYLE, "width='70%'");
$th = array(_("Offer Name"),_("Item Code"),_("Item Name"), _("From Date"), _("To Date"),_("Discount %"), "", "");
inactive_control_column($th);
table_header($th);

$k = 0;

while ($myrow = db_fetch($result)) 
{

	alt_table_row_color($k);	

	label_cell($myrow["offer_name"]);
	label_cell($myrow["stock_id"]);
	label_cell($myrow["item_name"]);
    label_cell(sql2date($myrow["from_date"]));
	label_cell(sql2date($myrow["to_date"]));
	qty_cell($myrow["disc"]);
	
 	edit_button_cell("Edit".$myrow['id'], _("Edit"));
 	delete_button_cell("Delete".$myrow['id'], _("Delete"));
	end_row();
}

// inactive_control_row($th);
end_table(1);
//-----------------------------------------------------------------------------------

start_table(TABLESTYLE2);

if ($selected_id != -1) 
{
 	if ($Mode == 'Edit') {
		//editing an existing status code
		$myrow = get_items_promotional_discount($selected_id);
		
		$_POST['stock_id']  = explode(',',$myrow["stock_id"]);
		$_POST['disc']  = $myrow["disc"];
		$_POST['offer_name']  = $myrow["offer_name"];
		
		$_POST['from_date']  = sql2date($myrow["from_date"]);
		
		$_POST['to_date']  = sql2date($myrow["to_date"]);
		
	}
	hidden('selected_id', $selected_id);
} 
text_row(_("Offer Name"),'offer_name',null,null,null);
sales_items_list_row_oldbkp(_("Items:"), 'stock_id', null, false, false,false);
date_row(_("From Date:"), 'from_date', '', true);
date_row(_("To Date:"), 'to_date', '', true);
small_amount_row(_("Discount %"), 'disc', percent_format($_POST['disc']), null, null, user_percent_dec());

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

//------------------------------------------------------------------------------------

end_page();

?>