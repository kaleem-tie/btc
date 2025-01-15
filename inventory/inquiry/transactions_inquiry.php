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
$page_security = 'SA_ITEMSTRANSINQ';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");
include($path_to_root . "/reporting/includes/tcpdf.php");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

if (isset($_GET['FixedAsset'])) {
  $page_security = 'SA_ASSET';
  $_SESSION['page_title'] = _($help_context = "Fixed Assets");
  $_POST['mb_flag'] = 'F';
  $_POST['fixed_asset']  = 1;
}
else {
  $_SESSION['page_title'] = _($help_context = "Transactions Inquiry");
	if (!get_post('fixed_asset'))
		$_POST['fixed_asset']  = 0;
}


page($_SESSION['page_title'], @$_REQUEST['popup'], false, "", $js);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");
include_once($path_to_root . "/fixed_assets/includes/fixed_assets_db.inc");

$user_comp = user_company();
$new_item = get_post('stock_id')=='' || get_post('cancel') || get_post('clone'); 
//------------------------------------------------------------------------------------
function set_edit($stock_id)
{
	$_POST = array_merge($_POST, get_item($stock_id));
	$_POST['depreciation_rate'] = number_format2($_POST['depreciation_rate'], 1);
	$_POST['depreciation_factor'] = number_format2($_POST['depreciation_factor'], 1);
	$_POST['depreciation_start'] = sql2date($_POST['depreciation_start']);
	$_POST['depreciation_date'] = sql2date($_POST['depreciation_date']);
	$_POST['del_image'] = 0;
	$_POST['buffer_period'] = sql2date($_POST['buffer_period']);
}

if (isset($_GET['stock_id']))
{
	$_POST['stock_id'] = $_GET['stock_id'];
}
$stock_id = get_post('stock_id');
if (list_updated('stock_id')) {
	$_POST['NewStockID'] = $stock_id = get_post('stock_id');
    clear_data();
	$Ajax->activate('details');
	$Ajax->activate('controls');
}

if (get_post('cancel')) {
	$_POST['NewStockID'] = $stock_id = $_POST['stock_id'] = '';
    clear_data();
	set_focus('stock_id');
	$Ajax->activate('_page_body');
}
if (list_updated('category_id') || list_updated('mb_flag') || list_updated('fa_class_id') || list_updated('depreciation_method')) {
	$Ajax->activate('details');
	$Ajax->activate('_page_body');
}

if (list_updated('category_id')) {

	$Ajax->activate('item_sub_category'); //item subcategory onchange via     Itemcategory name 
} 

if (list_updated('item_sub_category')) {

	$Ajax->activate('item_detail_category'); //item subcategory onchange via     Itemcategory name 
} 


if (list_updated('supplier_id')) {

	$Ajax->activate('supplier_division'); //item subcategory onchange via     Itemcategory name 
} 

// calculation 
if(isset($_POST['length']) || isset($_POST['breadth']) || isset($_POST['height']) )
{
	$_POST['cbf']=((input_num('length')*input_num('breadth')*input_num('height'))/1000000);
	$Ajax->activate('cbf');
		$Ajax->activate('_page_body');
}
 

$upload_file = "";
if (isset($_FILES['pic']) && $_FILES['pic']['name'] != '') 
{
	$stock_id = $_POST['NewStockID'];
	$result = $_FILES['pic']['error'];
 	$upload_file = 'Yes'; //Assume all is well to start off with
	$filename = company_path().'/images';
	if (!file_exists($filename))
	{
		mkdir($filename);
	}	
	$filename .= "/".item_img_name($stock_id).(substr(trim($_FILES['pic']['name']), strrpos($_FILES['pic']['name'], '.')));

  if ($_FILES['pic']['error'] == UPLOAD_ERR_INI_SIZE) {
    display_error(_('The file size is over the maximum allowed.'));
		$upload_file ='No';
  }
  elseif ($_FILES['pic']['error'] > 0) {
		display_error(_('Error uploading file.'));
		$upload_file ='No';
  }
	
	//But check for the worst 
	if ((list($width, $height, $type, $attr) = getimagesize($_FILES['pic']['tmp_name'])) !== false)
		$imagetype = $type;
	else
		$imagetype = false;

	if ($imagetype != IMAGETYPE_GIF && $imagetype != IMAGETYPE_JPEG && $imagetype != IMAGETYPE_PNG)
	{	//File type Check
		display_warning( _('Only graphics files can be uploaded'));
		$upload_file ='No';
	}
	elseif (!in_array(strtoupper(substr(trim($_FILES['pic']['name']), strlen($_FILES['pic']['name']) - 3)), array('JPG','PNG','GIF')))
	{
		display_warning(_('Only graphics files are supported - a file extension of .jpg, .png or .gif is expected'));
		$upload_file ='No';
	} 
	elseif ( $_FILES['pic']['size'] > ($SysPrefs->max_image_size * 1024)) 
	{ //File Size Check
		display_warning(_('The file size is over the maximum allowed. The maximum size allowed in KB is') . ' ' . $SysPrefs->max_image_size);
		$upload_file ='No';
	} 
	elseif ( $_FILES['pic']['type'] == "text/plain" ) 
	{  //File type Check
		display_warning( _('Only graphics files can be uploaded'));
        $upload_file ='No';
	} 
	elseif (file_exists($filename))
	{
		$result = unlink($filename);
		if (!$result) 
		{
			display_error(_('The existing image could not be removed'));
			$upload_file ='No';
		}
	}
	
	if ($upload_file == 'Yes')
	{
		$result  =  move_uploaded_file($_FILES['pic']['tmp_name'], $filename);
		if ($msg = check_image_file($filename)) {
			display_error($msg);
			unlink($filename);
			$upload_file ='No';
		}
	}
	$Ajax->activate('details');
 /* EOF Add Image upload for New Item  - by Ori */
}

if (get_post('fixed_asset')) {
	check_db_has_fixed_asset_categories(_("There are no fixed asset categories defined in the system. At least one fixed asset category is required to add a fixed asset."));
	check_db_has_fixed_asset_classes(_("There are no fixed asset classes defined in the system. At least one fixed asset class is required to add a fixed asset."));
} else
	check_db_has_stock_categories(_("There are no item categories defined in the system. At least one item category is required to add a item."));

check_db_has_item_tax_types(_("There are no item tax types defined in the system. At least one item tax type is required to add a item."));

function clear_data()
{
	unset($_POST['long_description']);
	unset($_POST['description']); 
	unset($_POST['supplier_id']);
	unset($_POST['supplier_division']);
	unset($_POST['category_id']);
	unset($_POST['item_sub_category']);
	unset($_POST['item_detail_category']);
	unset($_POST['old_code']);
	unset($_POST['replacement_code']); 
	unset($_POST['hs_code']);
	unset($_POST['weight']);
	unset($_POST['automotive_products']);
	unset($_POST['power_tools_product']);
	unset($_POST['automotive_services']);
	unset($_POST['power_tools_services']);
	//unset($_POST['currency_id']);
	unset($_POST['invent_y_n']);
	unset($_POST['cost_y_n']);
	unset($_POST['lead_days']); 
	unset($_POST['country_id']); 
	unset($_POST['grp_id']);
	unset($_POST['mode_of_transport_id']);
	unset($_POST['part_classification_description']);
	unset($_POST['length']);
	unset($_POST['breadth']);
	unset($_POST['height']);
	unset($_POST['cbf']);
	unset($_POST['tax_type_id']);
	unset($_POST['units']);
	unset($_POST['mb_flag']);
	unset($_POST['NewStockID']);
	unset($_POST['dimension_id']);
	unset($_POST['dimension2_id']);
	unset($_POST['no_sale']);
	unset($_POST['no_purchase']);
	unset($_POST['depreciation_method']);
	unset($_POST['depreciation_rate']);
	unset($_POST['depreciation_factor']);
	unset($_POST['depreciation_start']);
	unset($_POST['shelf_life']);
}

//------------------------------------------------------------------------------------

if (isset($_POST['addupdate'])) 
{
//display_error($_POST['supplier_id']);die;
	$input_error = 0;
	if ($upload_file == 'No')
		$input_error = 1;
	if (strlen($_POST['NewStockID']) == 0) 
	{
		$input_error = 1;
		display_error( _('The Item Code cannot be empty'));
		set_focus('NewStockID');
	}
	elseif (strstr($_POST['NewStockID'], " ") || strstr($_POST['NewStockID'],"'") || 
		strstr($_POST['NewStockID'], "+") || strstr($_POST['NewStockID'], "\"") || 
		strstr($_POST['NewStockID'], "&") || strstr($_POST['NewStockID'], "\t")) 
	{
		$input_error = 1;
		display_error( _('The item code cannot contain any of the following characters -  & + OR a space OR quotes'));
		set_focus('NewStockID');

	}
	elseif (strlen($_POST['description']) == 0) 
	{
		$input_error = 1;
		display_error( _('The item name must be entered.'));
		set_focus('description');
	} 
	elseif (strlen($_POST['long_description']) == 0) 
	{
		$input_error = 1;
		display_error( _('The Description  cannot be empty'));
		set_focus('long_description');
	}
	
	elseif (($_POST['category_id']) == -1) 
	{
		$input_error = 1;
		display_error( _('Please select the Category.'));
		set_focus('category_id');
	}
	
	
	elseif (strlen($_POST['NewStockID']) == 0) 
	{
		$input_error = 1;
		display_error( _('The item code cannot be empty'));
		set_focus('NewStockID');
	}
	elseif (strstr($_POST['NewStockID'], " ") || strstr($_POST['NewStockID'],"'") || 
		strstr($_POST['NewStockID'], "+") || strstr($_POST['NewStockID'], "\"") || 
		strstr($_POST['NewStockID'], "&") || strstr($_POST['NewStockID'], "\t")) 
	{
		$input_error = 1;
		display_error( _('The item code cannot contain any of the following characters -  & + OR a space OR quotes'));
		set_focus('NewStockID');

	}
	elseif ($new_item && db_num_rows(get_item_kit($_POST['NewStockID'])))
	{
		  	$input_error = 1;
      		display_error( _("This item code is already assigned to stock item or sale kit."));
			set_focus('NewStockID');
	}
	
	/* if (!check_num('weight', 0)) 
	{
		$input_error = 1;
		display_error(_("The Weight entered must be numeric and greater than zero."));
		set_focus('weight');
		 
	} */
	if (!get_post('fixed_asset')){
		if (strlen($_POST['supplier_id']) == 0) 
		{
			$input_error = 1;
			display_error( _('Please select the Supplier'));
			set_focus('supplier_id');
		}
	
		if (($_POST['supplier_division']) == -1) 
		{
			$input_error = 1;
			display_error( _('Please select the Supplier Division'));
			set_focus('supplier_division');
		}
		/* if (strlen($_POST['lead_days']) == 0) 
		{
			$input_error = 1;
			display_error( _('The Lead Days  cannot be empty'));
			set_focus('lead_days');
		} */
	
		if (($_POST['country_id']) == 0)  
		{
			$input_error = 1;
			display_error( _(' Please select the Country'));
			set_focus('country_id');
		}
		if (strlen($_POST['grp_id']) == 0) 
		{
			$input_error = 1;
			display_error( _('Please select the Grp'));
			set_focus('grp_id');
		}
	
	
		/* if (strlen($_POST['mode_of_transport_id']) == 0) 
		{
			$input_error = 1;
			display_error( _('Please select the Mode Of Transport.'));  
			set_focus('mode_of_transport_id');
		} */
	
		if (strlen($_POST['part_classification_description']) == 0)  
		{
			$input_error = 1;
			display_error( _('Please select the part Classification.'));
			set_focus('part_classification_description');
		}
		if (!check_num('lead_days', 0)) 
		{
			$input_error = 1;
			display_error(_("The lead days entered must be numeric and greater than zero."));
			set_focus('lead_days');
		 
		}
}
		

	
	
  if (get_post('fixed_asset')) {
    if ($_POST['depreciation_rate'] > 100) {
      $_POST['depreciation_rate'] = 100;
    }
    elseif ($_POST['depreciation_rate'] < 0) {
      $_POST['depreciation_rate'] = 0;
    }
    $move_row = get_fixed_asset_move($_POST['NewStockID'], ST_SUPPRECEIVE);
    if ($move_row && isset($_POST['depreciation_start']) && strtotime($_POST['depreciation_start']) < strtotime($move_row['tran_date'])) {
      display_warning(_('The depracation cannot start before the fixed asset purchase date'));
    }
  }
	
	if ($input_error != 1)
	{
		if (check_value('del_image'))
		{
			$filename = company_path().'/images/'.item_img_name($_POST['NewStockID']).".jpg";
			if (file_exists($filename))
				unlink($filename);
		}
		
		if (!$new_item) 
		{ /*so its an existing one */
			update_item($_POST['NewStockID'], $_POST['description'],$_POST['old_code'],$_POST['replacement_code'],$_POST['hs_code'],$_POST['buffer_period'],input_num('weight'), check_value('automotive_products'), check_value('power_tools_product'), check_value('automotive_services'),check_value('power_tools_services'),$_POST['currency_id'],$_POST['invent_y_n'],$_POST['cost_y_n'],input_num('lead_days'),$_POST['country_id'],
				$_POST['long_description'],$_POST['supplier_id'],$_POST['supplier_division'], $_POST['category_id'],$_POST['item_sub_category'],$_POST['item_detail_category'],$_POST['grp_id'],$_POST['mode_of_transport_id'], $_POST['part_classification_description'],input_num('length'),input_num('breadth'),input_num('height'),input_num('cbf'),
				$_POST['tax_type_id'], get_post('units'),
				get_post('fixed_asset') ? 'F' : get_post('mb_flag'), $_POST['sales_account'],
				$_POST['inventory_account'], $_POST['cogs_account'],
				$_POST['adjustment_account'], $_POST['wip_account'], 
				$_POST['dimension_id'], $_POST['dimension2_id'],
				check_value('no_sale'), check_value('editable'), check_value('no_purchase'),
				get_post('depreciation_method'), input_num('depreciation_rate'), input_num('depreciation_factor'), get_post('depreciation_start', null),
				get_post('fa_class_id'),$_POST['shelf_life']);

			update_record_status($_POST['NewStockID'], $_POST['inactive'],
				'stock_master', 'stock_id');
			update_record_status($_POST['NewStockID'], $_POST['inactive'],
				'item_codes', 'item_code');
			set_focus('stock_id');
			$Ajax->activate('stock_id'); // in case of status change
			display_notification(_("Item has been updated."));
		} 
		else 
		{ //it is a NEW part 

	//display_error(json_encode($_POST));die;
			add_item($_POST['NewStockID'], $_POST['description'],$_POST['old_code'],$_POST['replacement_code'],$_POST['hs_code'],$_POST['buffer_period'],input_num('weight'), check_value('automotive_products'), check_value('power_tools_product'), check_value('automotive_services'), check_value('power_tools_services'),$_POST['currency_id'],$_POST['invent_y_n'],$_POST['cost_y_n'],$_POST['country_id'],input_num('lead_days'),
				$_POST['long_description'],$_POST['supplier_id'],$_POST['supplier_division'], $_POST['category_id'],$_POST['item_sub_category'],$_POST['item_detail_category'],$_POST['grp_id'],$_POST['mode_of_transport_id'],$_POST['part_classification_description'],input_num('length'),input_num('breadth'),input_num('height'),input_num('cbf'), $_POST['tax_type_id'],
				$_POST['units'], get_post('fixed_asset') ? 'F' : get_post('mb_flag'), $_POST['sales_account'],
				$_POST['inventory_account'], $_POST['cogs_account'],
				$_POST['adjustment_account'], $_POST['wip_account'], 
				$_POST['dimension_id'], $_POST['dimension2_id'],
				check_value('no_sale'), check_value('no_purchase'), check_value('editable'),
				get_post('depreciation_method'), input_num('depreciation_rate'), input_num('depreciation_factor'), get_post('depreciation_start', null),
				get_post('fa_class_id'),$_POST['shelf_life']);

			display_notification(_("A new item has been added.")); 
			$_POST['stock_id'] = $_POST['NewStockID'] = 
			$_POST['description'] = $_POST['old_code'] = $_POST['replacement_code'] = $_POST['hs_code'] = $_POST['buffer_period']  =$_POST['long_description'] = '';
			$_POST['no_sale'] = $_POST['weight'] = $_POST['lead_days'] = $_POST['editable'] =$_POST['length'] =$_POST['breadth'] =$_POST['height'] =$_POST['cbf'] = $_POST['automotive_products'] = $_POST['power_tools_product'] =$_POST['automotive_services'] =$_POST['power_tools_services'] = $_POST['no_purchase'] =0;
			set_focus('NewStockID');
		}
		$Ajax->activate('_page_body');
	}
}

if (get_post('clone')) {
	set_edit($_POST['stock_id']); // restores data for disabled inputs too
	unset($_POST['stock_id']);
	$stock_id = '';
	unset($_POST['inactive']);
	set_focus('NewStockID');
	$Ajax->activate('_page_body');
}

//------------------------------------------------------------------------------------

function check_usage($stock_id, $dispmsg=true)
{
	$msg = item_in_foreign_codes($stock_id);

	if ($msg != '')	{
		if($dispmsg) display_error($msg);
		return false;
	}
	return true;
}

//------------------------------------------------------------------------------------

if (isset($_POST['delete']) && strlen($_POST['delete']) > 1) 
{

	if (check_usage($_POST['NewStockID'])) {

		$stock_id = $_POST['NewStockID'];
		delete_item($stock_id);
		$filename = company_path().'/images/'.item_img_name($stock_id).".jpg";
		if (file_exists($filename))
			unlink($filename);
		display_notification(_("Selected item has been deleted."));
		$_POST['stock_id'] = '';
		clear_data();
		set_focus('stock_id');
		$new_item = true;
		$Ajax->activate('_page_body');
	}
}

function item_settings(&$stock_id, $new_item) 
{
	global $SysPrefs, $path_to_root, $page_nested, $depreciation_methods;

	start_outer_table(TABLESTYLE2);

	table_section(1);

	table_section_title(_("General Settings"));

	//------------------------------------------------------------------------------------
	if ($new_item) 
	{
		$tmpCodeID=null;
		$post_label = null;
		if (!empty($SysPrefs->prefs['barcodes_on_stock']))
		{
			$post_label = '<button class="ajaxsubmit" type="submit" aspect=\'default\'  name="generateBarcode"  id="generateBarcode" value="Generate Barcode EAN8"> '._("Generate EAN-8 Barcode").' </button>';
			if (isset($_POST['generateBarcode']))
			{
				$tmpCodeID=generateBarcode();
				$_POST['NewStockID'] = $tmpCodeID;
			}
		}	
		text_row(_("Item Code:<b style='color:red;'>*</b>"), 'NewStockID', $tmpCodeID, 21, 20, null, "", $post_label);
		$_POST['inactive'] = 0;
	} 
	else 
	{ // Must be modifying an existing item
		if (get_post('NewStockID') != get_post('stock_id') || get_post('addupdate')) { // first item display

			$_POST['NewStockID'] = $_POST['stock_id'];
			set_edit($_POST['stock_id']);
		}
		label_row(_("Item Code:"),$_POST['NewStockID']);
		hidden('NewStockID', $_POST['NewStockID']);
		set_focus('description');
	}
	$fixed_asset = get_post('fixed_asset');

	text_row(_("Name:<b style='color:red;'>*</b>"), 'description', null, 25, 200);
	if (!get_post('fixed_asset')){
	text_row(_("Shelf life:"), 'shelf_life', null, 21, 20, null);}
	

	textarea_row(_("Description:<b style='color:red;'>*</b>"), 'long_description', null, 42, 3);
	if (!get_post('fixed_asset')){
	inv_supplier_list_row(_("Supplier:<b style='color:red;'>*</b>"), 'supplier_id', null, _('Select Supplier'), true);   ////
	supplier_subcategory_list_row(_("Supplier Division:<b style='color:red;'>*</b>"), 'supplier_division', null, _('Select Supplier Division'), true,$_POST['supplier_id']);
	}
	stock_categories_list_row(_("Category:<b style='color:red;'>*</b>"), 'category_id', null, false, true, $fixed_asset); 
	if (!get_post('fixed_asset')){
	sub_itemsubcategory_list_row(_("Sub Category:"), 'item_sub_category', null, _('NONE'), true,$_POST['category_id']); 
	
	$item_sub_category_name = $_POST['item_sub_category'];
	
	detail_category_name_list_row(_("Detail Category:"), 'item_detail_category', null, _('NONE'), true,$_POST['item_sub_category']); }
	
	if ($new_item && (list_updated('category_id') || !isset($_POST['sales_account']))) { // changed category for new item or first page view
	//item_sub_category_list_row(_("Sub Category:"), 'category_id', null,_('Select Item Sub  Category'));
	
	
	
	//$item_sub_category_name = $_POST['item_sub_category'];

		$category_record = get_item_category($_POST['category_id']);
         
		$_POST['tax_type_id'] = $category_record["dflt_tax_type"];
		$_POST['units'] = $category_record["dflt_units"];
		$_POST['mb_flag'] = $category_record["dflt_mb_flag"];
		$_POST['inventory_account'] = $category_record["dflt_inventory_act"];
		$_POST['cogs_account'] = $category_record["dflt_cogs_act"];
		$_POST['sales_account'] = $category_record["dflt_sales_act"];
		$_POST['adjustment_account'] = $category_record["dflt_adjustment_act"];
		$_POST['wip_account'] = $category_record["dflt_wip_act"];
		$_POST['dimension_id'] = $category_record["dflt_dim1"];
		$_POST['dimension2_id'] = $category_record["dflt_dim2"];
		$_POST['no_sale'] = $category_record["dflt_no_sale"];
		$_POST['no_purchase'] = $category_record["dflt_no_purchase"];
		$_POST['editable'] = 0;

	}
	$fresh_item = !isset($_POST['NewStockID']) || $new_item 
		|| check_usage($_POST['stock_id'],false);

	// show inactive item tax type in selector only if already set.
  item_tax_types_list_row(_("Item Tax Type:"), 'tax_type_id', null, !$new_item && item_type_inactive(get_post('tax_type_id')));

	if (!get_post('fixed_asset'))
		stock_item_types_list_row(_("Item Type:"), 'mb_flag', null, $fresh_item);

	stock_units_list_row(_('Units of Measure:'), 'units', null, $fresh_item);


	if (!get_post('fixed_asset')) {
		check_row(_("Editable description:"), 'editable');
		check_row(_("Exclude from sales:"), 'no_sale');
		check_row(_("Exclude from purchases:"), 'no_purchase');
	}

	if (get_post('fixed_asset')) {
		table_section_title(_("Depreciation"));

		fixed_asset_classes_list_row(_("Fixed Asset Class").':', 'fa_class_id', null, false, true);

		array_selector_row(_("Depreciation Method").":", "depreciation_method", null, $depreciation_methods, array('select_submit'=> true));

		if (!isset($_POST['depreciation_rate']) || (list_updated('fa_class_id') || list_updated('depreciation_method'))) {
			$class_row = get_fixed_asset_class($_POST['fa_class_id']);
			$_POST['depreciation_rate'] = get_post('depreciation_method') == 'N' ? ceil(100/$class_row['depreciation_rate'])
				: $class_row['depreciation_rate'];
		}

		if ($_POST['depreciation_method'] == 'O')
		{
			hidden('depreciation_rate', 100);
			label_row(_("Depreciation Rate").':', "100 %");
		}
		elseif ($_POST['depreciation_method'] == 'N')
		{
			small_amount_row(_("Depreciation Years").':', 'depreciation_rate', null, null, _('years'), 0);
		}
		elseif ($_POST['depreciation_method'] == 'D')
			small_amount_row(_("Base Rate").':', 'depreciation_rate', null, null, '%', user_percent_dec());
		else
			small_amount_row(_("Depreciation Rate").':', 'depreciation_rate', null, null, '%', user_percent_dec());

		if ($_POST['depreciation_method'] == 'D')
			small_amount_row(_("Rate multiplier").':', 'depreciation_factor', null, null, '', 2);

		// do not allow to change the depreciation start after this item has been depreciated
		if ($new_item || $_POST['depreciation_start'] == $_POST['depreciation_date'])
			date_row(_("Depreciation Start").':', 'depreciation_start', null, null, 1 - date('j'));
		else {
			hidden('depreciation_start');
			label_row(_("Depreciation Start").':', $_POST['depreciation_start']);
			label_row(_("Last Depreciation").':', $_POST['depreciation_date']==$_POST['depreciation_start'] ? _("None") :  $_POST['depreciation_date']);
		}
		hidden('depreciation_date');
	}
	
	
	table_section(2);
	
	
	if (!get_post('fixed_asset')){
	text_row(_("Old Code:"), 'old_code', null, 25, 150);
	text_row(_("Replacement Code:"), 'replacement_code', null, 25, 150);
	text_row(_("H S Code:"), 'hs_code', null, 25, 20);
	//date_row(_("Buffer Period:"), 'buffer_period', null, true);
	hidden('buffer_period', 0); 
	
	 qty_row(_("Weight (In Kg's):"), 'weight', null, null, null); 
	//currencies_list_row(_("Currency:"), 'currency_id', null, true);
	//inventory_types_list_row(_("Inventory Y/N:"),'invent_y_n');
	hidden('invent_y_n', 0);
	//inventory_types_list_row(_("Costing Y/N:"),'cost_y_n'); 
	hidden('cost_y_n', 0); 
	 qty_row(_("Lead Days:"), 'lead_days', null, null, null); 
	country_types_list_row(_("Country of Origin: <b style='color:red;'>*</b>"),'country_id'); 
	grp_list_row(_("GRP:<b style='color:red;'>*</b>"), 'grp_id', null,_('Select GRP')); 
	mode_of_transport_list_row(_("Mode of Transport:"), 'mode_of_transport_id', null, _('Select Mode Of Transport'));
	 part_classification_list_row(_("Part Classification:<b style='color:red;'>*</b>"), 'part_classification_description', null, _('Select Part Classification'));
	 
	 qty_conversion_row(_("Length (in cm):"), 'length', null, null, null, 0);
	 qty_conversion_row(_("Breadth (in cm):"), 'breadth', 0, null, null, 0);
	  qty_conversion_row(_("Height (in cm):"), 'height', 0, null, null, 0);
	qty_row(_("CBF:"), 'cbf', null); }
	  
	 
	
	
	
	
	table_section(3);

	$dim = get_company_pref('use_dimension');
	if ($dim >= 1)
	{
		table_section_title(_("Dimensions"));

		dimensions_list_row(_("Dimension")." 1", 'dimension_id', null, true, " ", false, 1);
		if ($dim > 1)
			dimensions_list_row(_("Dimension")." 2", 'dimension2_id', null, true, " ", false, 2);
	}
	if ($dim < 1)
		hidden('dimension_id', 0);
	if ($dim < 2)
		hidden('dimension2_id', 0);

	//table_section_title(_("GL Accounts"));

	//gl_all_accounts_list_row(_("Sales Account:"), 'sales_account', $_POST['sales_account']);
	hidden('sales_account',$_POST['sales_account']);
	if (get_post('fixed_asset')) {
		//gl_all_accounts_list_row(_("Asset account:"), 'inventory_account', $_POST['inventory_account']);
			hidden('inventory_account',$_POST['inventory_account']);

		
		//gl_all_accounts_list_row(_("Depreciation cost account:"), 'cogs_account', $_POST['cogs_account']);
		hidden('cogs_account',$_POST['cogs_account']);
		//gl_all_accounts_list_row(_("Depreciation/Disposal account:"), 'adjustment_account', $_POST['adjustment_account']);
		hidden('adjustment_account',$_POST['adjustment_account']);
	}
	elseif (!is_service(get_post('mb_flag')))
	{
		//gl_all_accounts_list_row(_("Inventory Account:"), 'inventory_account', $_POST['inventory_account']);
		hidden('inventory_account',$_POST['inventory_account']);
		
		//gl_all_accounts_list_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
		hidden('cogs_account',$_POST['cogs_account']);
		//gl_all_accounts_list_row(_("Inventory Adjustments Account:"), 'adjustment_account', $_POST['adjustment_account']);
		hidden('adjustment_account',$_POST['adjustment_account']);
	}
	else 
	{
		//gl_all_accounts_list_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
		hidden('cogs_account',$_POST['cogs_account']);
		hidden('inventory_account', $_POST['inventory_account']);
		hidden('adjustment_account', $_POST['adjustment_account']);
	}


	//if (is_manufactured(get_post('mb_flag')))
		//gl_all_accounts_list_row(_("WIP Account:"), 'wip_account', $_POST['wip_account']);
	//else
		hidden('wip_account', $_POST['wip_account']);

	table_section_title(_("Other"));

	// Add image upload for New Item  - by Joe
	file_row(_("Image File (.jpg)") . ":", 'pic', 'pic');
	// Add Image upload for New Item  - by Joe
	$stock_img_link = "";
	$check_remove_image = false;

	if (@$_POST['NewStockID'] && file_exists(company_path().'/images/'
		.item_img_name($_POST['NewStockID']).".jpg")) 
	{
	 // 31/08/08 - rand() call is necessary here to avoid caching problems.
		$stock_img_link .= "<img id='item_img' alt = '[".$_POST['NewStockID'].".jpg".
			"]' src='".company_path().'/images/'.item_img_name($_POST['NewStockID']).
			".jpg?nocache=".rand()."'"." height='".$SysPrefs->pic_height."' border='0'>";
		$check_remove_image = true;
	} 
	else 
	{
		$stock_img_link .= _("No image");
	}

	label_row("&nbsp;", $stock_img_link);
	if ($check_remove_image)
		check_row(_("Delete Image:"), 'del_image');

	record_status_list_row(_("Item status:"), 'inactive');
	if (get_post('fixed_asset')) {
		table_section_title(_("Values"));
		if (!$new_item) {
			hidden('material_cost');
			hidden('purchase_cost');
			label_row(_("Initial Value").":", price_format($_POST['purchase_cost']), "", "align='right'");
			label_row(_("Depreciations").":", price_format($_POST['purchase_cost'] - $_POST['material_cost']), "", "align='right'");
			label_row(_("Current Value").':', price_format($_POST['material_cost']), "", "align='right'");
		}
	}
	if (!get_post('fixed_asset')){
	table_section_title(_("Vertical Section"));
	    check_row(_("Automotive Products:"), 'automotive_products');
		check_row(_("Power Tools Products:"), 'power_tools_product');
		check_row(_("Automotive Services:"), 'automotive_services');
	check_row(_("Power Tools Services:"), 'power_tools_services');}

	end_outer_table(1);

	/* div_start('controls');
	if (@$_REQUEST['popup']) hidden('popup', 1);
	if (!isset($_POST['NewStockID']) || $new_item) 
	{
		submit_center('addupdate', _("Insert New Item"), true, '', 'default');
	} 
	else 
	{
		submit_center_first('addupdate', _("Update Item"), '', 
			$page_nested ? true : 'default');
		submit_return('select', get_post('stock_id'), 
			_("Select this items and return to document entry."));
		submit('clone', _("Clone This Item"), true, '', true);
		submit('delete', _("Delete This Item"), true, '', true);
		submit_center_last('cancel', _("Cancel"), _("Cancel Edition"), 'cancel');
	}

	div_end(); */
}

//-------------------------------------------------------------------------------------------- 

start_form(true);

if (db_has_stock_items()) 
{
	start_table(TABLESTYLE_NOBORDER);
	start_row();
    stock_items_list_cells(_("Select an item:"), 'stock_id', null,
	  _('New item'), true, check_value('show_inactive'), false, array('fixed_asset' => get_post('fixed_asset')));
	$new_item = get_post('stock_id')=='';
	// check_cells(_("Show inactive:"), 'show_inactive', null, true);
	end_row();
	end_table();

	if (get_post('_show_inactive_update')) {
		$Ajax->activate('stock_id');
		set_focus('stock_id');
	}
}
else
{
	hidden('stock_id', get_post('stock_id'));
}
br();
 div_start('details');
if(($_POST["stock_id"] !='')){
	
		 $item_info = get_item_info($_POST['stock_id']);

		start_table(TABLESTYLE, "width='100%'");
		$th = array(_("Item Code"),_("Item Name"), _("Total Stock"), _("Pending PO"), _("Pending SO"), 
         _("Cost Rate"), _("Sale Rate"),_("Last GRN Date"),_("Currency"),
		 _("Price"),_("Supplier Name"),_("Supplier Item Code"));
		table_header($th);


		$pending_po = get_on_porder_qty($_POST['stock_id']);
		$pending_po += get_on_worder_qty($_POST['stock_id']);
		$qoh = get_qoh_on_date($_POST['stock_id']);
		$demand_qty = get_demand_qty($_POST['stock_id']);
		$demand_qty += get_demand_asm_qty($_POST['stock_id']);
		$dec = get_qty_dec($_POST['stock_id']); 
		

		$sale_rate=get_kit_price($_POST['stock_id'],'OMR',1);
		label_cell($item_info['stock_id']);
		label_cell($item_info['description']);
        if($item_info['mb_flag']=='D')
		label_cell('#', "nowrap align=center");
		else
        label_cell(number_format2($qoh, $dec), "nowrap align=center");
        label_cell(number_format2($pending_po, $dec), "nowrap align=center"); 
        label_cell(number_format2($demand_qty, $dec), "nowrap align=center");
		if($_SESSION["wa_current_user"]->show_cost_rate)		
        label_cell(number_format2($item_info['material_cost'], 3), "nowrap align=center");
		else
		label_cell('Disable', "nowrap align=center");		
        label_cell(number_format2($sale_rate, 3), "nowrap align=center");
		// label_cell($item_info['hs_code']);
        // label_cell(number_format2($item_info['weight'], $dec), "nowrap align=center");
		// label_cell($item_info['replacement_code']);
		
	

		label_cell(get_last_grn_date($item_info['stock_id']),"align=center");
		
		$lpinfo=get_last_purchase_details($item_info['stock_id']);
		if($lpinfo!='Not Found!')
		{
			label_cell($lpinfo[0],"align=center");
			label_cell(number_format2($lpinfo[1], 3), "nowrap align=center");
		}
		
		$item_supp_info = get_item_purch_data_info($item_info['stock_id']);
		
		label_cell($item_supp_info['supp_name']);
		label_cell($item_supp_info['supplier_description']);
			
		end_table();
}

br();
$stock_id = get_post('stock_id');

if (!$stock_id && (!isset($_POST['oe_reference']) && !$_POST['_tabs_sel'])=='spl_enquiry')
	unset($_POST['_tabs_sel']); // force settings tab for new customer

$tabs = (get_post('fixed_asset'))
	? array(
		'settings' => array(_('&General settings'), $stock_id),
		'movement' => array(_('&Transactions'), $stock_id) )
	: array(
		'sales' => array(_('&Sales'), (user_check_access('SA_SALESINV') ? $stock_id : null)),
		'outstanding_po' => array(_('&PO'), (user_check_access('SA_OUTSTANDING_PO') ? $stock_id : null)),
		'outstanding_so' => array(_('&SO'), (user_check_access('SA_OUTSTANDING_SO') ? $stock_id : null)),
		'cust_outstanding_so' => array(_('&Cust.SO'), (user_check_access('SA_CUST_OUTSTANDING_SO') ? $stock_id : null)),
		'cust_sales' => array(_('&Cust Sales'), (is_inventory_item($stock_id) && 
			user_check_access('SA_SALESINV_CUST') ? $stock_id : null)),
			
		// 'movement' => array(_('&Price List'), (user_check_access('SA_ITEMSTRANSVIEW') && is_inventory_item($stock_id) ?		$stock_id : null)),
	    'stock_month_sales' => array(_('YTD Sale'),(user_check_access('SA_STOCK_MONTH_SALES') ? $stock_id : null)),
		'status' => array(_('&Status'), (user_check_access('SA_ITEMSSTATVIEW') ? $stock_id : null)), 
		// 'stock_others' => array(_('&Others'), (user_check_access('SA_OTHERS') ? $stock_id : null)),
		// 'stock_enquiry' => array(_('&Lost Sales Enquiry Items'), (user_check_access('SA_STOCK_ENQUIRY') ? true : null)),
        // 'spl_enquiry' => array(_('&Supplier Price List Enquiry'), (user_check_access('SA_SUPPLIER_PRICE_ENQUIRY') ? true : null)),		
	);

tabbed_content_start('tabs', $tabs,'status');

	switch (get_post('_tabs_sel')) {
		default:
		case 'sales':
			$_GET['stock_id'] = $stock_id;
			$_GET['page_level'] = 1;
			include_once($path_to_root."/inventory/inquiry/sales_invoices.php");
			break;
		case 'outstanding_po':
			$_GET['stock_id'] = $stock_id;
			$_GET['page_level'] = 1;
			include_once($path_to_root."/inventory/inquiry/outstanding_po.php");
			break;
		case 'outstanding_so':
			$_GET['stock_id'] = $stock_id;
			$_GET['page_level'] = 1;
			include_once($path_to_root."/inventory/inquiry/outstanding_so.php");
			break;
		case 'cust_outstanding_so':
			$_GET['stock_id'] = $stock_id;
			$_GET['page_level'] = 1;
			include_once($path_to_root."/inventory/inquiry/cust_outstanding_so.php");
			break;
		case 'cust_sales':
			$_GET['stock_id'] = $stock_id;
			$_GET['page_level'] = 1;
			include_once($path_to_root."/inventory/inquiry/sales_invoices_cust.php");
			break;
		case 'movement':
			if (!is_inventory_item($stock_id))
				break;
			$_GET['stock_id'] = $stock_id;
			include_once($path_to_root."/inventory/inquiry/inv_suppliers_price_list_transaction_inquiry.php");
			break;
		case 'status':
			$_GET['stock_id'] = $stock_id;
			include_once($path_to_root."/inventory/inquiry/stock_status.php");
			break;
		case 'others':
			$_GET['stock_id'] = $stock_id;
			$_GET['page_level'] = 1;
			include_once($path_to_root."/inventory/others.php");
			break;

       case 'stock_others':
			$_GET['stock_id'] = $stock_id;
			$_GET['page_level'] = 1;
			include_once($path_to_root."/inventory/stock_others.php");
			break;
			
	  case 'stock_enquiry':
			$_GET['page_level'] = 1;
			include_once($path_to_root."/inventory/stock_enquiry.php");
			break;
			
	case 'spl_enquiry':
			$_GET['page_level'] = 1;
			include_once($path_to_root."/inventory/supplier_price_list_enquiry.php");
			break;		
			
	 case 'stock_month_sales':
          	$_GET['stock_id'] = $stock_id;
			$_GET['page_level'] = 1;
			include_once($path_to_root."/inventory/stock_month_sales.php");
			break; 
	};

br();
tabbed_content_end();

div_end();

hidden('fixed_asset', get_post('fixed_asset'));

if (get_post('fixed_asset'))
	hidden('mb_flag', 'F');

end_form();

//------------------------------------------------------------------------------------

end_page(@$_REQUEST['popup']);

function generateBarcode() {
	$tmpBarcodeID = "";
	$tmpCountTrys = 0;
	while ($tmpBarcodeID == "")	{
		srand ((double) microtime( )*1000000);
		$random_1  = rand(1,9);
		$random_2  = rand(0,9);
		$random_3  = rand(0,9);
		$random_4  = rand(0,9);
		$random_5  = rand(0,9);
		$random_6  = rand(0,9);
		$random_7  = rand(0,9);
		//$random_8  = rand(0,9);

			// http://stackoverflow.com/questions/1136642/ean-8-how-to-calculate-checksum-digit
		$sum1 = $random_2 + $random_4 + $random_6; 
		$sum2 = 3 * ($random_1  + $random_3  + $random_5  + $random_7 );
		$checksum_value = $sum1 + $sum2;

		$checksum_digit = 10 - ($checksum_value % 10);
		if ($checksum_digit == 10) 
			$checksum_digit = 0;

		$random_8  = $checksum_digit;

		$tmpBarcodeID = $random_1 . $random_2 . $random_3 . $random_4 . $random_5 . $random_6 . $random_7 . $random_8;

		// LETS CHECK TO SEE IF THIS NUMBER HAS EVER BEEN USED
		$query = "SELECT stock_id FROM ".TB_PREF."stock_master WHERE stock_id='" . $tmpBarcodeID . "'";
		$arr_stock = db_fetch(db_query($query));
  
		if (  !$arr_stock['stock_id'] ) {
			return $tmpBarcodeID;
		}
		$tmpBarcodeID = "";	 
	}
}
