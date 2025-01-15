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
$page_security = 'SA_MATERIAL_INDENT';

$path_to_root = "..";

 include_once($path_to_root . "/includes/ui/material_indent_cart_class.inc");

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/matirial_indent_ui.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

if (isset($_GET['NewIndent'])) {


		$_SESSION['page_title'] = _($help_context = "Material Indent Request");
	
}
page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------------------------------

check_db_has_costable_items(_("There are no inventory items defined in the system (Purchased or manufactured items)."));

//-----------------------------------------------------------------------------------------------

/* if (list_updated('from_loc')) {
	$Ajax->activate('page_body');
}
 */
if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_MATERIAL_INDENT;
	//display_error($trans_no);

	display_notification_centered(_("Material Indent Request has been processed"));
	display_note(get_trans_view_str($trans_type, $trans_no, _("&View this Indent Request")));

    //$itm = db_fetch(get_stock_transfer_items($_GET['AddedID']));

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Indent Request"), "NewIndent=1");
	
	hyperlink_params("$path_to_root/admin/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$trans_no");

	display_footer_exit();
}
//--------------------------------------------------------------------------------------------------

function line_start_focus() {
  global 	$Ajax;

  $Ajax->activate('items_table');
  set_focus('_stock_id_edit');
}
//-----------------------------------------------------------------------------------------------

function handle_new_order()
{
	if (isset($_SESSION['indent']))
	{
		$_SESSION['indent']->clear_items();
		unset ($_SESSION['indent']);
	}

	$_SESSION['indent'] = new Indent_request_cart(ST_MATERIAL_INDENT);
  $_SESSION['indent']->fixed_asset = isset($_GET['FixedAsset']);
	$_POST['AdjDate'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['AdjDate']))
		$_POST['AdjDate'] = end_fiscalyear();
	$_SESSION['indent']->tran_date = $_POST['AdjDate'];	
}

//-----------------------------------------------------------------------------------------------
if (isset($_POST['Process']))
{

	$tr = &$_SESSION['indent'];
	$input_error = 0;

	if (count($tr->Indent_line_item) == 0)	{
		display_error(_("You must enter at least one non empty item line."));
		set_focus('stock_id');
		$input_error = 1;
	}
	if (!check_reference($_POST['ref'], ST_MATERIAL_INDENT))
	{
		set_focus('ref');
		$input_error = 1;
	} 
	elseif (!is_date($_POST['AdjDate'])) 
	{
		display_error(_("The entered transfer date is invalid."));
		set_focus('AdjDate');
		$input_error = 1;
	} 
	elseif (!is_date_in_fiscalyear($_POST['AdjDate'])) 
	{
		display_error(_("The entered date is out of fiscal year or is closed for further data entry."));
		set_focus('AdjDate');
		$input_error = 1;
	} 
	elseif ($_POST['indent_req_loc'] == $_POST['from_loc'])
	{
		display_error(_("Requested To Location should be different from Requested From Location ."));
		set_focus('indent_req_loc');
		$input_error = 1;
	}
	
	
	if($_POST['ind_req_type_id'] ==0)
	{
			display_error(_("Please select the indent request type."));
			set_focus('ind_req_type_id');
			$input_error = 1;
	}
	
	
	


	if ($input_error == 1)
		unset($_POST['Process']);
}

//-------------------------------------------------------------------------------

if (isset($_POST['Process']))
{
	$trans_no = add_material_indent_request($_SESSION['indent']->Indent_line_item,
		$_POST['indent_req_loc'], $_POST['from_loc'],
		$_POST['AdjDate'], $_POST['ref'], $_POST['memo_'],$_POST['qoh'],$_SESSION["wa_current_user"]->username,$_POST['ind_req_type_id']);

	new_doc_date($_POST['AdjDate']);
	$_SESSION['indent']->clear_items();
	unset($_SESSION['indent']);
   	meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
} /*end of process credit note */

//-----------------------------------------------------------------------------------------------

function check_item_data()
{
	if (!check_num('qty', 0) || input_num('qty') == 0)
	{
		display_error(_("The quantity entered must be a positive number."));
		set_focus('qty');
		return false;
	}
   	return true;
}

//-----------------------------------------------------------------------------------------------

function handle_update_item()
{
	$id = $_POST['LineNo'];
   	if (!isset($_POST['std_cost']))
   		$_POST['std_cost'] = $_SESSION['indent']->Indent_line_item[$id]->standard_cost;
   	$_SESSION['indent']->update_cart_item($id, input_num('qty'), $_POST['std_cost'],$_POST['qoh']);
	unset($_POST['stock_id']);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_delete_item($id)
{
	$_SESSION['indent']->remove_from_cart($id);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_new_item()
{
	if (!isset($_POST['std_cost']))
   		$_POST['std_cost'] = 0;
	add_to_order($_SESSION['indent'], $_POST['stock_id'], input_num('qty'), $_POST['std_cost'],'');
	unset($_POST['stock_id']);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

$id = find_submit('Delete');

if ($id != -1)
	handle_delete_item($id);
	
if (isset($_POST['AddItem']) && check_item_data())
	handle_new_item();

if (isset($_POST['UpdateItem']) && check_item_data())
	handle_update_item();

if (isset($_POST['CancelItemChanges'])) {
	line_start_focus();
}
//-----------------------------------------------------------------------------------------------

if (isset($_GET['NewIndent']) || !isset($_SESSION['indent']))
{
	if (isset($_GET['fixed_asset']))
		check_db_has_disposable_fixed_assets(_("There are no fixed assets defined in the system."));
	else
		check_db_has_costable_items(_("There are no inventory items defined in the system (Purchased or manufactured items)."));

	handle_new_order();
}

//------------------------------------------------------------------------------------------------
start_form();

display_indent_header($_SESSION['indent']);

start_table(TABLESTYLE, "width='70%'", 10);
start_row();
echo "<td>";
display_indent_items(_("Items"), $_SESSION['indent']);
transfer_options_controls();
echo "</td>";
end_row();
end_table(1);

submit_center_first('Update', _("Update"), '', null);
submit_center_last('Process', _("Process Request"), '',  'default');

//user_check_access('SA_MATERIAL_INDENT_UPDATE') ? submit_center_first('Update', _("Update"), '', null) : '';
//user_check_access('SA_MATERIAL_INDENT_ADDNEW') ? submit_center_last('Process', _("Process Indent"), '',  'default') : '';

end_form();
end_page();

