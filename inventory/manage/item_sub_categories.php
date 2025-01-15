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
$page_security = 'SA_ITEM_SUB_CATEGORY';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Item Subcategory"));

include($path_to_root . "/inventory/includes/db/item_sub_categories_db.inc");

include($path_to_root . "/includes/ui.inc");

simple_page_mode(true);
//-----------------------------------------------------------------------------------
if (isset($_GET['category_id']))
{
	$_POST['category_id'] = $_GET['category_id'];
	$selected_parent =  $_GET['category_id'];
}
if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	//initialise no input errors assumed initially before we test
	$input_error = 0;
	/*if (strlen($_POST['code']) == 0) 
	{
		$input_error = 1;
		display_error(_("The Code cannot be empty."));
		set_focus('code');
	}*/
	if (strlen($_POST['item_name']) == 0) 
	{
		$input_error = 1;
		display_error(_("The Name cannot be empty."));
		set_focus('item_name');
	}
	if (strlen($_POST['category_id']) == '') 
	{
		$input_error = 1;
		display_error(_("The Category cannot be empty."));
		set_focus('category_id');
	}

	
	
	if ($input_error != 1) 
	{
		
    	if ($selected_id != -1) 
    	{
    		update_item_sub_category($selected_id, $_POST['code'], $_POST['item_name'], $_POST['category_id']);
			display_notification(_('Selected Item Subcategory has been updated'));
    	} 
    	else 
    	{
    		add_item_sub_category($_POST['code'], $_POST['item_name'], $_POST['category_id']);
			display_notification(_('New Item Subcategory has been added'));
    	}
		$Mode = 'RESET';
	}
} 
//-----------------------------------------------------------------------------------
if ($Mode == 'Delete')
{

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'detail_category'
	/*if (key_in_foreign_table($selected_id, 'detail_category','sub_category_id'))
	{
		display_error(_("Cannot delete this Item Subcategory because Items have been created using this Subcategory."));
	}*/ 
	if (key_in_foreign_table($selected_id, 'stock_master', 'item_sub_category'))
	{
		display_error(_("Cannot delete this item Subcategory because Item Category have been created using this Item master."));
	} 
	else 
	{
		delete_item_sub_category($selected_id);
		display_notification(_('Selected item Subcategory has been deleted'));
	}
	$Mode = 'RESET';
}
if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST['code'],$_POST['name']);
	$_POST['show_inactive'] = $sav;
}
if (list_updated('category_id')) {

	// copy_item_sub_category($_POST['category_id']);
	$Ajax->activate('_page_body');
	

}
//-----------------------------------------------------------------------------------

$result = get_all_item_sub_category(check_value('show_inactive'),$_POST['category_id']);

/* function copy_item_sub_category($category_id)
{
	
	while ($myrow = db_fetch($result))
	{
		
		$_POST['item_name'] = $myrow["item_name"];
		$_POST['category_id'] = $myrow["category_id"];
				
		//on_submit($category_id, -1);
	}
 } */
start_form();
//-----------------------------------------------------------------------------------

start_table(TABLESTYLE2);

if ($selected_id != -1) 
{
 	if ($Mode == 'Edit') {
		//editing an existing status code
		$myrow = get_item_sub_category($selected_id);
		
		
		$_POST['item_name']  = $myrow["item_name"];
		$_POST['category_id']  = $myrow["category_id"];
	}
	hidden('selected_id', $selected_id);
} 
stock_categories_list_row(_("Category:<b style='color:red;'>*</b>"), 'category_id', null,_('Select Item Category'),true);

hidden('code',0);
text_row(_("Name:<b style='color:red;'>*</b>"), 'item_name', null, 30, 40);




end_table(1);
start_table(TABLESTYLE, "width='50%'");
$th = array(_("Name"), _("Category"), "", "");
inactive_control_column($th);
table_header($th);
submit_add_or_update_center($selected_id == -1, '', 'both');
echo '<br>';
end_form();

$k = 0;
while ($myrow = db_fetch($result)) 
{
	
	
	alt_table_row_color($k);	

	
	label_cell($myrow["item_name"]);
	label_cell($myrow["description"]);
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'item_sub_category', 'id');
 	edit_button_cell("Edit".$myrow['id'], _("Edit"));
 	delete_button_cell("Delete".$myrow['id'], _("Delete"));
	end_row();
}

inactive_control_row($th);
end_table(1);


//------------------------------------------------------------------------------------

end_page();

