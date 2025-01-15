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
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/types.inc");
include_once($path_to_root . "/includes/current_user.inc");


define('SEARCH_BUTTON', "<input %s type='submit' class='combo_submit' style='border:0;background:url($path_to_root/themes/"
	."%s/images/locate.png) no-repeat;%s' aspect='fallback' name='%s' value=' ' title='"._("Set filter")."'> ");

define('SELECT_BUTTON', "<input %s type='submit' class='combo_select' style='border:0;background:url($path_to_root/themes/"
	."%s/images/button_ok.png) no-repeat;%s' aspect='fallback' name='%s' value=' ' title='"._("Select")."'> ");

//----------------------------------------------------------------------------
//	Universal sql combo generator
//	$sql must return selector values and selector texts in columns 0 & 1
//	Options are merged with defaults.

function combo_input($name, $selected_id, $sql, $valfield, $namefield,
	$options=null, $type=null)
{
global $Ajax, $path_to_root, $SysPrefs ;

$opts = array(		// default options
	'where'=> array(),		// additional constraints
	'order' => $namefield,	// list sort order
		// special option parameters
	'spec_option'=>false, 	// option text or false
	'spec_id' => 0,		// option id
		// submit on select parameters
	'default' => '', // default value when $_POST is not set
	'multi' => false,	// multiple select
	'select_submit' => false, //submit on select: true/false
	'async' => true,	// select update via ajax (true) vs _page_body reload
		// search box parameters
	'sel_hint' => null,
	'search_box' => false, 	// name or true/false
	'type' => 0,	// type of extended selector:
		// 0 - with (optional) visible search box, search by fragment inside id
		// 1 - with hidden search box, search by option text
		// 2 - with (optional) visible search box, search by fragment at the start of id
		// 3 - TODO reverse: box with hidden selector available via enter; this
		// would be convenient for optional ad hoc adding of new item
	'search_submit' => true, //search submit button: true/false
	'size' => 8,	// size and max of box tag
	'max' => 50,
	'height' => false,	// number of lines in select box
	'cells' => false,	// combo displayed as 2 <td></td> cells
	'search' => array(), // sql field names to search
	'format' => null, 	 // format functions for regular options
	'disabled' => false,
	'box_hint' => null, // box/selectors hints; null = std see below
	'category' => false, // category column name or false
	'show_inactive' => false, // show inactive records. 
	'editable' => false, // false, or length of editable entry field
	'editlink' => false	// link to entity entry/edit page (optional)
);
// ------ merge options with defaults ----------
	if($options != null)
		$opts = array_merge($opts, $options);
	if (!is_array($opts['where']))  $opts['where'] = array($opts['where']);

	$search_box = $opts['search_box']===true ? '_'.$name.'_edit' : $opts['search_box'];
	// select content filtered by search field:
	$search_submit = $opts['search_submit']===true ? '_'.$name.'_button' : $opts['search_submit'];
	// select set by select content field
	$search_button = $opts['editable'] ? '_'.$name.'_button' : ($search_box ? $search_submit : false);

	$select_submit =  $opts['select_submit'];
	$spec_id = $opts['spec_id'];
	$spec_option = $opts['spec_option'];
	if ($opts['type'] == 0) {
		$by_id = true;
		$class = 'combo';
	} elseif($opts['type'] == 1) {
		$by_id = false;
		$class = 'combo2';
	} else {
		$by_id = true;
		$class = 'combo3';
	}

	$disabled = $opts['disabled'] ? "disabled" : '';
	$multi = $opts['multi'];
	
	if(!count($opts['search'])) {
		$opts['search'] = array($by_id ? $valfield : $namefield);
	}
	if ($opts['sel_hint'] === null) 
		$opts['sel_hint'] = $by_id || $search_box==false ?
			'' : _('Press Space tab for search pattern entry');

	if ($opts['box_hint'] === null)
		$opts['box_hint'] = $search_box && $search_submit != false ?
			($by_id ? _('Enter code fragment to search or * for all')
			: _('Enter description fragment to search or * for all')) :'';

	if ($selected_id == null) {
		$selected_id = get_post($name, (string)$opts['default']);
	}
	if(!is_array($selected_id))
		$selected_id = array((string)$selected_id); // code is generalized for multiple selection support

	$txt = get_post($search_box);
	$rel = '';
	$limit = '';
	if (isset($_POST['_'.$name.'_update'])) { // select list or search box change
		if ($by_id) $txt = $_POST[$name];

		if (!$opts['async'])
			$Ajax->activate('_page_body');
		else
			$Ajax->activate($name);
	}
	if (isset($_POST[$search_button])) {
		if (!$opts['async'])
			$Ajax->activate('_page_body');
		else
			$Ajax->activate($name);
	}
	if ($search_box) {
		// search related sql modifications

		$rel = "rel='$search_box'"; // set relation to list
		if ($opts['search_submit']) {
			if (isset($_POST[$search_button])) {
				$selected_id = array(); // ignore selected_id while search
				if (!$opts['async'])
					$Ajax->activate('_page_body');
				else
					$Ajax->activate($name);
			}
			if ($txt == '') {
				if ($spec_option === false && $selected_id == array())
					$limit = ' LIMIT 1';
				else
					$opts['where'][] = $valfield . "=". db_escape(get_post($name, $spec_id));
			}
			else
				if ($txt != '*') {

					foreach($opts['search'] as $i=> $s)
						$opts['search'][$i] = $s . " LIKE "
							.db_escape(($class=='combo3' ? '' : '%').$txt.'%');
					$opts['where'][] = '('. implode($opts['search'], ' OR ') . ')';
				}
		}
	}

	// sql completion
	if (count($opts['where'])) {
		$where = strpos($sql, 'WHERE')==false ? ' WHERE ':' AND ';
		$where .= '('. implode(' AND ', $opts['where']) . ')';
		$group_pos = strpos($sql, 'GROUP BY');
		if ($group_pos) {
			$group = substr($sql, $group_pos);
			$sql = substr($sql, 0, $group_pos) . $where.' '.$group;
		} else {
			$sql .= $where;
		}
	}
	if ($opts['order'] != false) {
		if (!is_array($opts['order']))
			$opts['order'] = array($opts['order']);
		$sql .= ' ORDER BY '.implode(',',$opts['order']);
	}

	$sql .= $limit;
	// ------ make selector ----------
	$selector = $first_opt = '';
	$first_id = false;
	$found = false;
	$lastcat = null;
	$edit = false;
	$pname = false;
	if (($type === "customer" || $type === "supplier") && !empty($SysPrefs->prefs['shortname_name_in_list']))
		$pname = true;
	if($result = db_query($sql)) {
		while ($contact_row = db_fetch($result)) {
			$value = $contact_row[0];
			$descr = $opts['format']==null ?  $contact_row[1] :
				call_user_func($opts['format'], $contact_row, $pname);
			$sel = '';
 		 	if (get_post($search_button) && ($txt == $value)) {
 		 		$selected_id[] = $value;
 		 	}

 		 	if (in_array((string)$value, $selected_id, true)) {
				$sel = 'selected';
				$found = $value;
				$edit = $opts['editable'] && $contact_row['editable'] 
					&& (@$_POST[$search_box] == $value)
					? $contact_row[1] : false; // get non-formatted description
				if ($edit)
					break;	// selected field is editable - abandon list construction
			}
			// show selected option even if inactive 
			if (!$opts['show_inactive'] && @$contact_row['inactive'] && $sel==='') {
				continue;
			} else 
				$optclass = @$contact_row['inactive'] ? "class='inactive'" : '';

			if ($first_id === false) {
				$first_id = $value;
				$first_opt = $descr;
			}
			$cat = $contact_row[$opts['category']];
			if ($opts['category'] !== false && $cat != $lastcat){
				if ($lastcat!==null)
					$selector .= "</optgroup>";
				$selector .= "<optgroup label='".$cat."'>\n";
				$lastcat = $cat;
			}
			$selector .= "<option $sel $optclass value='$value'>$descr</option>\n";
		}
		if ($lastcat!==null)
			$selector .= "</optgroup>";
		db_free_result($result);
	}

	// Prepend special option.
	if ($spec_option !== false) { // if special option used - add it
		$first_id = $spec_id;
		$first_opt = $spec_option;
		$sel = $found===false ? 'selected' : '';
		$optclass = @$contact_row['inactive'] ? "class='inactive'" : '';
		$selector = "<option $sel value='$first_id'>$first_opt</option>\n"
			. $selector;
	}

	if ($found===false) {
		$selected_id = array($first_id);
	}
	
	$_POST[$name] = $multi ? $selected_id : $selected_id[0];

	if ($SysPrefs->use_popup_search)
		$selector = "<select id='$name' autocomplete='off' ".($multi ? "multiple" : '')
		. ($opts['height']!==false ? ' size="'.$opts['height'].'"' : '')
		. "$disabled name='$name".($multi ? '[]':'')."' class='$class' title='"
		. $opts['sel_hint']."' $rel>".$selector."</select>\n";
	else
		$selector = "<select autocomplete='off' ".($multi ? "multiple" : '')
		. ($opts['height']!==false ? ' size="'.$opts['height'].'"' : '')
		. "$disabled name='$name".($multi ? '[]':'')."' class='$class' title='"
		. $opts['sel_hint']."' $rel>".$selector."</select>\n";
	if ($by_id && ($search_box != false || $opts['editable']) ) {
		// on first display show selector list
		if (isset($_POST[$search_box]) && $opts['editable'] && $edit) {
			$selector = "<input type='hidden' name='$name' value='".$_POST[$name]."'>"
			."<input type='text' $disabled name='{$name}_text' id='{$name}_text' size='".
				$opts['editable']."' maxlength='".$opts['max']."' $rel value='$edit'>\n";
				set_focus($name.'_text'); // prevent lost focus
		} else if (get_post($search_submit ? $search_submit : "_{$name}_button"))
			set_focus($name); // prevent lost focus
		if (!$opts['editable'])
	 		$txt = $found;
		$Ajax->addUpdate($name, $search_box, $txt ? $txt : '');
	}

	$Ajax->addUpdate($name, "_{$name}_sel", $selector);

	// span for select list/input field update
	$selector = "<span id='_{$name}_sel'>".$selector."</span>\n";

	 // if selectable or editable list is used - add select button
	if ($select_submit != false || $search_button) {
	// button class selects form reload/ajax selector update
		$selector .= sprintf(SELECT_BUTTON, $disabled, user_theme(),
			(fallback_mode() ? '' : 'display:none;'),
			 '_'.$name.'_update')."\n";
	}
// ------ make combo ----------
	$edit_entry = '';
	if ($search_box != false) {
		$edit_entry = "<input $disabled type='text' name='$search_box' id='$search_box' size='".
			$opts['size']."' maxlength='".$opts['max'].
			"' value='$txt' class='$class' rel='$name' autocomplete='off' title='"
			.$opts['box_hint']."'"
			.(!fallback_mode() && !$by_id ? " style=display:none;":'')
			.">\n";
		if ($search_submit != false || $opts['editable']) {
			$edit_entry .= sprintf(SEARCH_BUTTON, $disabled, user_theme(),
				(fallback_mode() ? '' : 'display:none;'),
				$search_submit ? $search_submit : "_{$name}_button")."\n";
		}
	}
	default_focus(($search_box && $by_id) ? $search_box : $name);

	$img = "";
	if ($SysPrefs->use_popup_search && (!isset($opts['fixed_asset']) || !$opts['fixed_asset']))
	{
		$img_title = "";
		$link = "";
  		$id = $name;
  		if ($SysPrefs->use_popup_windows) {
  			if ($type != NULL) {
	    		switch (strtolower($type)) {
	      			case "stock":
	        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=all&client_id=" . $id;
	        			$img_title = _("Search items");
	        			break;
	      			case "stock_manufactured":
	        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=manufactured&client_id=" . $id;
	        			$img_title = _("Search items");
	        			break;
	      			case "stock_purchased":
	        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=purchasable&client_id=" . $id;
	        			$img_title = _("Search items");
	        			break;
	      			case "stock_sales":
	        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=sales&client_id=" . $id;
	        			$img_title = _("Search items");
	        			break;
	      			case "stock_costable":
	        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=costable&client_id=" . $id;
	        			$img_title = _("Search items");
	        			break;
	      			case "component":
	      				$parent = $opts['parent'];
	        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=component&parent=".$parent."&client_id=" . $id;
	        			$img_title = _("Search items");
	        			break;
	      			case "kits":
	        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=kits&client_id=" . $id;
	        			$img_title = _("Search items");
	        			break;
					case "tool_kits":
	        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=tool_kits&client_id=" . $id;
	        			$img_title = _("Search Tools items");
	        			break;	
						
	      			case "customer":
	        			$link = $path_to_root . "/sales/inquiry/customers_list.php?popup=1&client_id=" . $id;
	        			$img_title = _("Search customers");
	        			break;
	      			case "branch":
	        			$link = $path_to_root . "/sales/inquiry/customer_branches_list.php?popup=1&client_id=" . $id . "#customer_id";
	        			$img_title = _("Search branches");
	        			break;
	      			case "supplier":
	        			$link = $path_to_root . "/purchasing/inquiry/suppliers_list.php?popup=1&client_id=" . $id;
	        			$img_title = _("Search suppliers");
	        			break;
	      			case "account":
	      			case "account2":
	      				$skip = strtolower($type) == "account" ? false : true;
	        			$link = $path_to_root . "/gl/inquiry/accounts_list.php?popup=1&skip=".$skip."&client_id=" . $id;
	        			$img_title = _("Search GL accounts");
	        			break;
						
					case "servicejobcard":
        			$link = $path_to_root . "/service/inquiry/jobcard_list.php?popup=1&job_id=" . $id;
        			$img_title = _("Search Service Work Order");
        			break;	
					
					case "employee_search":
					$link = $path_to_root . "/modules/ExtendedHRM/manage/employee_search.php?popup=1&client_id=" . $id;
					$img_title = _("Search Employee");
					break;
                   
                    case "operator_search":
					$link = $path_to_root . "/service/inquiry/operator_list.php?popup=1&op_id=" . $id;
					$img_title = _("Search Opearator");
					break;	
                    
                     case "vehicle_search":
					$link = $path_to_root . "/service/inquiry/vehicle_list.php?popup=1&veh_id=" . $id;
					$img_title = _("Search Vehicle");
					break;						
	    		}
	    	}
  		}

  		if ($link !=="") {
    		$theme = user_theme();
    		$img = '<img src="'.$path_to_root.'/themes/'.$theme.'/images/'.ICON_VIEW.
    			'" style="vertical-align:middle;width:12px;height:12px;border:0;" onclick="javascript:lookupWindow(&quot;'.
    			$link.'&quot;, &quot;&quot;);" title="' . $img_title . '" style="cursor:pointer;" />';
  		}
	}

	if ($opts['editlink'])
		$selector .= ' '.$opts['editlink'];

	if ($search_box && $opts['cells'])
		$str = ($edit_entry!='' ? "<td>$edit_entry</td>" : '')."<td nowrap>$selector$img</td>";
	else
		$str = $edit_entry.$selector.$img;
	return $str;
}

/*
	Helper function.
	Returns true if selector $name is subject to update.
*/
function list_updated($name)
{
	return isset($_POST['_'.$name.'_update']) || isset($_POST['_'.$name.'_button']);
}
//----------------------------------------------------------------------------------------------
//	Universal array combo generator
//	$items is array of options 'value' => 'description'
//	Options is reduced set of combo_selector options and is merged with defaults.

function array_selector($name, $selected_id, $items, $options=null)
{
	global $Ajax;

$opts = array(		// default options
	'spec_option'=>false, 	// option text or false
	'spec_id' => 0,		// option id
	'select_submit' => false, //submit on select: true/false
	'async' => true,	// select update via ajax (true) vs _page_body reload
	'default' => '', // default value when $_POST is not set
	'multi'=>false,	// multiple select
		// search box parameters
	'height' => false,	// number of lines in select box
	'sel_hint' => null,
	'disabled' => false
);
// ------ merge options with defaults ----------
	if($options != null)
		$opts = array_merge($opts, $options);
	$select_submit =  $opts['select_submit'];
	$spec_id = $opts['spec_id'];
	$spec_option = $opts['spec_option'];
	$disabled = $opts['disabled'] ? "disabled" : '';
	$multi = $opts['multi'];

	if ($selected_id == null) {
		$selected_id = get_post($name, $opts['default']);
	}
	if(!is_array($selected_id))
		$selected_id = array((string)$selected_id); // code is generalized for multiple selection support

	if (isset($_POST[ '_'.$name.'_update'])) {
		if (!$opts['async'])
			$Ajax->activate('_page_body');
		else
			$Ajax->activate($name);
	}

	// ------ make selector ----------
	$selector = $first_opt = '';
	$first_id = false;
	$found = false;
	foreach($items as $value=>$descr) {
		$sel = '';
		if (in_array((string)$value, $selected_id, true)) {
			$sel = 'selected';
			$found = $value;
		}
		if ($first_id === false) {
			$first_id = $value;
			$first_opt = $descr;
		}
		$selector .= "<option $sel value='$value'>$descr</option>\n";
	}

	if ($first_id!==false) {
		$sel = ($found===$first_id) || ($found===false && ($spec_option===false)) ? "selected='selected'" : '';
	}
	// Prepend special option.
	if ($spec_option !== false) { // if special option used - add it
		$first_id = $spec_id;
		$first_opt = $spec_option;
		$sel = $found===false ? 'selected' : '';
		$selector = "<option $sel value='$spec_id'>$spec_option</option>\n"
			. $selector;
	}

	if ($found===false) {
		$selected_id = array($first_id);
	}
	$_POST[$name] = $multi ? $selected_id : $selected_id[0];

	$selector = "<select autocomplete='off' ".($multi  ? "multiple" : '')
		. ($opts['height']!==false ? ' size="'.$opts['height'].'"' : '')
		. "$disabled name='$name".($multi ? '[]' : '')."' class='combo' title='"
		. $opts['sel_hint']."'>".$selector."</select>\n";

	$Ajax->addUpdate($name, "_{$name}_sel", $selector);

	$selector = "<span id='_{$name}_sel'>".$selector."</span>\n";

	if ($select_submit != false) { // if submit on change is used - add select button
		$selector .= sprintf(SELECT_BUTTON, $disabled, user_theme(),
			(fallback_mode() ? '' : 'display:none;'),
			 '_'.$name.'_update')."\n";
	}
	default_focus($name);

	return $selector;
}

// ---------------------Ramesh fo operators multi selection----------------------------------

function combo_input_multi($name, $selected_id, $sql, $valfield, $namefield,
	$options=null, $type=null)
{
global $Ajax, $path_to_root, $SysPrefs ;

$opts = array(		// default options
	'where'=> array(),		// additional constraints
	'order' => $namefield,	// list sort order
		// special option parameters
	'spec_option'=>false, 	// option text or false
	'spec_id' => 0,		// option id
		// submit on select parameters
	'default' => '', // default value when $_POST is not set
	'multi' => false,	// multiple select
	'select_submit' => false, //submit on select: true/false
	'async' => true,	// select update via ajax (true) vs _page_body reload
		// search box parameters
	'sel_hint' => null,
	'search_box' => false, 	// name or true/false
	'type' => 0,	// type of extended selector:
		// 0 - with (optional) visible search box, search by fragment inside id
		// 1 - with hidden search box, search by option text
		// 2 - with (optional) visible search box, search by fragment at the start of id
		// 3 - TODO reverse: box with hidden selector available via enter; this
		// would be convenient for optional ad hoc adding of new item
	'search_submit' => true, //search submit button: true/false
	'size' => 8,	// size and max of box tag
	'max' => 50,
	'height' => false,	// number of lines in select box
	'cells' => false,	// combo displayed as 2 <td></td> cells
	'search' => array(), // sql field names to search
	'format' => null, 	 // format functions for regular options
	'disabled' => false,
	'box_hint' => null, // box/selectors hints; null = std see below
	'category' => false, // category column name or false
	'show_inactive' => false, // show inactive records. 
	'editable' => false,  // false, or length of editable entry field
	'editlink' => false,  // link to entity entry/edit page (optional)
	'orddesc' => false	 // link to order by descending (optional)
);
// ------ merge options with defaults ----------
	if($options != null)
		$opts = array_merge($opts, $options);
	if (!is_array($opts['where']))  $opts['where'] = array($opts['where']);

	$search_box = $opts['search_box']===true ? '_'.$name.'_edit' : $opts['search_box'];
	// select content filtered by search field:
	$search_submit = $opts['search_submit']===true ? '_'.$name.'_button' : $opts['search_submit'];
	// select set by select content field
	$search_button = $opts['editable'] ? '_'.$name.'_button' : ($search_box ? $search_submit : false);

	$select_submit =  $opts['select_submit'];
	$spec_id = $opts['spec_id'];
	$spec_option = $opts['spec_option'];
	if ($opts['type'] == 0) {
		$by_id = true;
		$class = 'combo';
	} elseif($opts['type'] == 1) {
		$by_id = false;
		$class = 'combo2';
	} else {
		$by_id = true;
		$class = 'combo3';
	}

	$disabled = $opts['disabled'] ? "disabled" : '';
	$multi = $opts['multi'];
	
	if(!count($opts['search'])) {
		$opts['search'] = array($by_id ? $valfield : $namefield);
	}
	if ($opts['sel_hint'] === null) 
		$opts['sel_hint'] = $by_id || $search_box==false ?
			'' : _('Press Space tab for search pattern entry');

	if ($opts['box_hint'] === null)
		$opts['box_hint'] = $search_box && $search_submit != false ?
			($by_id ? _('Enter code fragment to search or * for all')
			: _('Enter description fragment to search or * for all')) :'';

	if ($selected_id == null) {
		$selected_id = get_post($name, (string)$opts['default']);
	}
	if(!is_array($selected_id))
		$selected_id = array((string)$selected_id); // code is generalized for multiple selection support

	$txt = get_post($search_box);
	$rel = '';
	$limit = '';
	if (isset($_POST['_'.$name.'_update'])) { // select list or search box change
		if ($by_id) $txt = $_POST[$name];

		if (!$opts['async'])
			$Ajax->activate('_page_body');
		else
			$Ajax->activate($name);
	}
	if (isset($_POST[$search_button])) {
		if (!$opts['async'])
			$Ajax->activate('_page_body');
		else
			$Ajax->activate($name);
	}
	if ($search_box) {
		// search related sql modifications

		$rel = "rel='$search_box'"; // set relation to list
		if ($opts['search_submit']) {
			if (isset($_POST[$search_button])) {
				$selected_id = array(); // ignore selected_id while search
				if (!$opts['async'])
					$Ajax->activate('_page_body');
				else
					$Ajax->activate($name);
			}
			if ($txt == '') {
				if ($spec_option === false && $selected_id == array())
					$limit = ' LIMIT 1';
				else
					$opts['where'][] = $valfield . "=". db_escape(get_post($name, $spec_id));
			}
			else
				if ($txt != '*') {

					foreach($opts['search'] as $i=> $s)
						$opts['search'][$i] = $s . " LIKE "
							.db_escape(($class=='combo3' ? '' : '%').$txt.'%');
					$opts['where'][] = '('. implode($opts['search'], ' OR ') . ')';
				}
		}
	}

	// sql completion
	if (count($opts['where'])) {
		$where = strpos($sql, 'WHERE')==false ? ' WHERE ':' AND ';
		$where .= '('. implode($opts['where'], ' AND ') . ')';
		$group_pos = strpos($sql, 'GROUP BY');
		if ($group_pos) {
			$group = substr($sql, $group_pos);
			$sql = substr($sql, 0, $group_pos) . $where.' '.$group;
		} else {
			$sql .= $where;
		}
	}
	if ($opts['order'] != false) {
		if (!is_array($opts['order']))
			$opts['order'] = array($opts['order']);
		$sql .= ' ORDER BY '.implode(',',$opts['order']);
	}
    // for Descending order
	if ($opts['orddesc'] != false) {
		if (!is_array($opts['orddesc']))
			$opts['orddesc'] = array($opts['orddesc']);
		$des= ' DESC';
		$sql .= $des;
	}
	
	$sql .= $limit;
	// ------ make selector ----------
	$selector = $first_opt = '';
	$first_id = false;
	$found = false;
	$lastcat = null;
	$edit = false;
	$pname = false;
	if (($type === "customer" || $type === "supplier") && !empty($SysPrefs->prefs['shortname_name_in_list']))
		$pname = true;
	if($result = db_query($sql)) {
		while ($contact_row = db_fetch($result)) {
			$value = $contact_row[0];
			$descr = $opts['format']==null ?  $contact_row[1] :
				call_user_func($opts['format'], $contact_row, $pname);
			$sel = '';
 		 	if (get_post($search_button) && ($txt == $value)) {
 		 		$selected_id[] = $value;
 		 	}

 		 	if (in_array((string)$value, $selected_id, true)) {
				$sel = 'selected';
				$found = $value;
				$edit = $opts['editable'] && $contact_row['editable'] 
					&& (@$_POST[$search_box] == $value)
					? $contact_row[1] : false; // get non-formatted description
				if ($edit)
					break;	// selected field is editable - abandon list construction
			}
			// show selected option even if inactive 
			if (!$opts['show_inactive'] && @$contact_row['inactive'] && $sel==='') {
				continue;
			} else 
				$optclass = @$contact_row['inactive'] ? "class='inactive'" : '';

			if ($first_id === false) {
				$first_id = $value;
				$first_opt = $descr;
			}
			$cat = $contact_row[$opts['category']];
			if ($opts['category'] !== false && $cat != $lastcat){
				if ($lastcat!==null)
					$selector .= "</optgroup>";
				$selector .= "<optgroup label='".$cat."'>\n";
				$lastcat = $cat;
			}
			$selector .= "<option $sel $optclass value='$value'>$descr</option>\n";
		}
		if ($lastcat!==null)
			$selector .= "</optgroup>";
		db_free_result($result);
	}

	// Prepend special option.
	if ($spec_option !== false) { // if special option used - add it
		$first_id = $spec_id;
		$first_opt = $spec_option;
		$sel = $found===false ? 'selected' : '';
		$optclass = @$contact_row['inactive'] ? "class='inactive'" : '';
		$selector = "<option $sel value='$first_id'>$first_opt</option>\n"
			. $selector;
	}

	if ($found===false) {
		$selected_id = array($first_id);
	}
	
	$_POST[$name] = $multi ? $selected_id : $selected_id[0];

	if ($SysPrefs->use_popup_search)
		$selector = "<select id='$name' autocomplete='off' ".($multi ? "multiple" : '')
		. ($opts['height']!==false ? ' size="'.$opts['height'].'"' : '')
		. "$disabled name='$name".($multi ? '[]':'')."' class='$class' title='"
		. $opts['sel_hint']."' $rel $rel style='width:200px; height:150px;' onmouseleave='getRollCount();' ' onclick='getRollCount();' 'onmousedown='getRollCount();'>".$selector."</select>\n";
	else
		$selector = "<select autocomplete='off' ".($multi ? "multiple" : '')
		. ($opts['height']!==false ? ' size="'.$opts['height'].'"' : '')
		. "$disabled name='$name".($multi ? '[]':'')."' class='$class' title='"
		. $opts['sel_hint']."' $rel style='width:200px; height:150px;'>".$selector."</select>\n";
	if ($by_id && ($search_box != false || $opts['editable']) ) {
		// on first display show selector list
		if (isset($_POST[$search_box]) && $opts['editable'] && $edit) {
			$selector = "<input type='hidden' name='$name' value='".$_POST[$name]."'>"
			."<input type='text' $disabled name='{$name}_text' id='{$name}_text' size='".
				$opts['editable']."' maxlength='".$opts['max']."' $rel value='$edit'>\n";
				set_focus($name.'_text'); // prevent lost focus
		} else if (get_post($search_submit ? $search_submit : "_{$name}_button"))
			set_focus($name); // prevent lost focus
		if (!$opts['editable'])
	 		$txt = $found;
		$Ajax->addUpdate($name, $search_box, $txt ? $txt : '');
	}

	$Ajax->addUpdate($name, "_{$name}_sel", $selector);

	// span for select list/input field update
	$selector = "<span id='_{$name}_sel'>".$selector."</span>\n";

	 // if selectable or editable list is used - add select button
	if ($select_submit != false || $search_button) {
	// button class selects form reload/ajax selector update
		$selector .= sprintf(SELECT_BUTTON, $disabled, user_theme(),
			(fallback_mode() ? '' : 'display:none;'),
			 '_'.$name.'_update')."\n";
	}
// ------ make combo ----------
	$edit_entry = '';
	if ($search_box != false) {
		$edit_entry = "<input $disabled type='text' name='$search_box' id='$search_box' size='".
			$opts['size']."' maxlength='".$opts['max'].
			"' value='$txt' class='$class' rel='$name' autocomplete='off' title='"
			.$opts['box_hint']."'"
			.(!fallback_mode() && !$by_id ? " style=display:none;":'')
			.">\n";
		if ($search_submit != false || $opts['editable']) {
			$edit_entry .= sprintf(SEARCH_BUTTON, $disabled, user_theme(),
				(fallback_mode() ? '' : 'display:none;'),
				$search_submit ? $search_submit : "_{$name}_button")."\n";
		}
	}
	default_focus(($search_box && $by_id) ? $search_box : $name);

	$img = "";
	if ($SysPrefs->use_popup_search && (!isset($opts['fixed_asset']) || !$opts['fixed_asset']))
	{
		$img_title = "";
		$link = "";
  		$id = $name;
  		if ($SysPrefs->use_popup_windows) {
    		switch (strtolower($type)) {
      			case "stock":
        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=all&client_id=" . $id;
        			$img_title = _("Search items");
        			break;
      			case "stock_manufactured":
        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=manufactured&client_id=" . $id;
        			$img_title = _("Search items");
        			break;
      			case "stock_purchased":
        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=purchasable&client_id=" . $id;
        			$img_title = _("Search items");
        			break;
      			case "stock_sales":
        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=sales&client_id=" . $id;
        			$img_title = _("Search items");
        			break;
      			case "stock_costable":
        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=costable&client_id=" . $id;
        			$img_title = _("Search items");
        			break;
      			case "component":
      				$parent = $opts['parent'];
        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=component&parent=".$parent."&client_id=" . $id;
        			$img_title = _("Search items");
        			break;
      			case "kits":
        			$link = $path_to_root . "/inventory/inquiry/stock_list.php?popup=1&type=kits&client_id=" . $id;
        			$img_title = _("Search items");
        			break;
      			case "customer":
        			$link = $path_to_root . "/sales/inquiry/customers_list.php?popup=1&client_id=" . $id;
        			$img_title = _("Search customers");
        			break;
      			case "branch":
        			$link = $path_to_root . "/sales/inquiry/customer_branches_list.php?popup=1&client_id=" . $id . "#customer_id";
        			$img_title = _("Search branches");
        			break;
      			case "supplier":
        			$link = $path_to_root . "/purchasing/inquiry/suppliers_list.php?popup=1&client_id=" . $id;
        			$img_title = _("Search suppliers");
        			break;
      			case "account":
        			$link = $path_to_root . "/gl/inquiry/accounts_list.php?popup=1&client_id=" . $id;
        			$img_title = _("Search GL accounts");
        			break;
    		}
  		}

  		if ($link !=="") {
    		$theme = user_theme();
    		$img = '<img src="'.$path_to_root.'/themes/'.$theme.'/images/'.ICON_VIEW.
    			'" style="vertical-align:middle;width:12px;height:12px;border:0;" onclick="javascript:lookupWindow(&quot;'.
    			$link.'&quot;, &quot;&quot;);" title="' . $img_title . '" style="cursor:pointer;" />';
  		}
	}

	if ($opts['editlink'])
		$selector .= ' '.$opts['editlink'];

	if ($search_box && $opts['cells'])
		$str = ($edit_entry!='' ? "<td>$edit_entry</td>" : '')."<td>$selector$img</td>";
	else
		$str = $edit_entry.$selector.$img;
	return $str;
}

//----------------------------------------------------------------------------------------------
function array_selector_row($label, $name, $selected_id, $items, $options=null)
{
	echo "<tr><td class='label'>$label</td>\n<td>";
	echo array_selector($name, $selected_id, $items, $options);
	echo "</td></tr>\n";
}

//----------------------------------------------------------------------------------------------
function _format_add_curr($row, $pname=false)
{
	static $company_currency;

	if ($company_currency == null)
	{
		$company_currency = get_company_currency();
	}
	if (!$pname)
	{
		return $row[1] . ($row[2] == $company_currency ? '' : ("&nbsp;-&nbsp;" . $row[2]));
	}		
	else
	{
		return $row[1] . "&nbsp;-&nbsp;" . $row[2] . ($row[3] == $company_currency ? '' : ("&nbsp;-&nbsp;" . $row[3]));
	}		
}

function add_edit_combo($type)
{
	global $path_to_root, $popup_editors, $SysPrefs;

	if (!isset($SysPrefs->use_icon_for_editkey) || $SysPrefs->use_icon_for_editkey==0)
		return "";
	// Derive theme path
	$theme_path = $path_to_root . '/themes/' . user_theme();

	$key = $popup_editors[$type][1];
	$onclick = "onclick=\"javascript:callEditor($key); return false;\"";
	$img = "<img width='12' height='12' border='0' alt='Add/Edit' title='Add/Edit' src='$theme_path/images/".ICON_EDIT."'>";
	return "<a target = '_blank' href='#' $onclick tabindex='-1'>$img</a>"; 
}

function supplier_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false,
	$all=false, $editkey = false)
{
	global $SysPrefs;
	if (!empty($SysPrefs->prefs['shortname_name_in_list']))
		$sql = "SELECT supplier_id, supp_ref, supp_name, curr_code, inactive FROM ".TB_PREF."suppliers ";
	else	
		$sql = "SELECT supplier_id, supp_ref, curr_code, inactive FROM ".TB_PREF."suppliers ";

	$mode = get_company_pref('no_supplier_list');

	if ($editkey)
		set_editor('supplier', $name, $editkey);

	$ret = combo_input($name, $selected_id, $sql, 'supplier_id', 'supp_name',
	array(
		'format' => '_format_add_curr',
	    'order' => array('supp_ref'),
		'search_box' => $mode!=0,
		'type' => 1,
        'search' => array("supp_ref","supp_name","gst_no"),        
		'spec_option' => $spec_option === true ? _("All Suppliers") : $spec_option,
		'spec_id' => ALL_TEXT,
		'select_submit'=> $submit_on_change,
		'async' => false,
		'sel_hint' => $mode ? _('Press Space tab to filter by name fragment') :
		_('Select supplier'),
		'show_inactive'=>$all,
		'editlink' => $editkey ? add_edit_combo('supplier') : false
		), "supplier");
	return $ret;
}

function supplier_list_cells($label, $name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $all=false, $editkey = false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";	
	echo supplier_list($name, $selected_id, $all_option, $submit_on_change, 
		$all, $editkey);
	echo "</td>\n";
}

function supplier_list_row($label, $name, $selected_id=null, $all_option = false, 
	$submit_on_change=false, $all=false, $editkey = false)
{
	echo "<tr><td class='label'>$label</td><td>";
	echo supplier_list($name, $selected_id, $all_option, $submit_on_change,
		$all, $editkey);
	echo "</td></tr>\n";
}
//----------------------------------------------------------------------------------------------

function customer_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false, $show_inactive=false, $editkey = false)
{
	global $SysPrefs;

	if (!empty($SysPrefs->prefs['shortname_name_in_list']))
		$sql = "SELECT debtor_no, debtor_ref, name, curr_code, inactive FROM ".TB_PREF."debtors_master ";
	else	
		$sql = "SELECT debtor_no, debtor_ref, curr_code, inactive FROM ".TB_PREF."debtors_master ";
		

	$mode = get_company_pref('no_customer_list');

	if ($editkey)
		set_editor('customer', $name, $editkey);

	$ret = combo_input($name, $selected_id, $sql, 'debtor_no', 'debtor_ref',
	array(
	    // 'format' => '_format_add_curr',
	    'order' => array('debtor_ref'),
		'search_box' => $mode!=0,
		'type' => 1,
		'size' => 20,
        'search' => array("debtor_ref","name","tax_id"),        
		'spec_option' => $spec_option === true ? _("All Customers") : $spec_option,
		'spec_id' => ALL_TEXT,
		'select_submit'=> $submit_on_change,
		'async' => false,
		'sel_hint' => $mode ? _('Press Space tab to filter by name fragment; F2 - entry new customer') :
		_('Select customer'),
		'show_inactive' => $show_inactive,
		'editlink' => $editkey ? add_edit_combo('customer') : false
	), "customer" );
	return $ret;
}

function customer_list_cells($label, $name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td nowrap>";
	echo customer_list($name, $selected_id, $all_option, $submit_on_change,
		$show_inactive, $editkey);
	echo "</td>\n";
}

function customer_list_row($label, $name, $selected_id=null, $all_option = false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false)
{

	echo "<tr><td class='label'>$label</td><td nowrap>";
	echo customer_list($name, $selected_id, $all_option, $submit_on_change,
		$show_inactive, $editkey);
	echo "</td>\n</tr>\n";
}

//------------------------------------------------------------------------------------------------

function customer_branches_list($customer_id, $name, $selected_id=null,
	$spec_option = true, $enabled=true, $submit_on_change=false, $editkey = false)
{

	$sql = "SELECT branch_code, branch_ref FROM ".TB_PREF."cust_branch
		WHERE debtor_no=" . db_escape($customer_id)." ";

	if ($editkey)
		set_editor('branch', $name, $editkey);

	$where = $enabled ? array("inactive = 0") : array();
	$ret = combo_input($name, $selected_id, $sql, 'branch_code', 'branch_ref',
	array(
		'where' => $where,
		'order' => array('branch_ref'),
		'spec_option' => $spec_option === true ? _('All branches') : $spec_option,
		'spec_id' => ALL_TEXT,
		'select_submit'=> $submit_on_change,
		'sel_hint' => _('Select customer branch'),
		'editlink' => $editkey ? add_edit_combo('branch') : false
	), "branch" );
	return $ret;
}
//------------------------------------------------------------------------------------------------

function customer_branches_list_cells($label,$customer_id, $name, $selected_id=null, 
	$all_option = true, $enabled=true, $submit_on_change=false, $editkey = false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo customer_branches_list($customer_id, $name, $selected_id, $all_option, $enabled, 
		$submit_on_change, $editkey);
	echo "</td>\n";
}

function customer_branches_list_row($label, $customer_id, $name, $selected_id=null, 
	$all_option = true, $enabled=true, $submit_on_change=false, $editkey = false)
{
	echo "<tr><td class='label'>$label</td>";
	customer_branches_list_cells(null, $customer_id, $name, $selected_id, 
		$all_option, $enabled, $submit_on_change, $editkey);
	echo "</tr>";
}

//------------------------------------------------------------------------------------------------

function locations_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{

	$sql = "SELECT loc_code, location_name, inactive FROM ".TB_PREF."locations WHERE fixed_asset=".(int)$fixed_asset;

	return combo_input($name, $selected_id, $sql, 'loc_code', 'location_name',
		array(
			'spec_option' => $all_option === true ? _("All Locations") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}

function locations_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo locations_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function locations_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	locations_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}

//-----------------------------------------------------------------------------------------------

function currencies_list($name, $selected_id=null, $submit_on_change=false, $exclude_home_curr=false)
{
	$sql = "SELECT curr_abrev, currency, inactive FROM ".TB_PREF."currencies";
	if ($exclude_home_curr)
		$sql .= " WHERE curr_abrev!='".get_company_currency()."'";

	// default to the company currency
	return combo_input($name, $selected_id, $sql, 'curr_abrev', 'currency',
		array(
			'select_submit'=> $submit_on_change,
			'default' => get_company_currency(),
			'async' => false
		) );
}

function currencies_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo currencies_list($name, $selected_id, $submit_on_change);
	echo "</td>\n";
}

function currencies_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	currencies_list_cells(null, $name, $selected_id, $submit_on_change);
	echo "</tr>\n";
}

//---------------------------------------------------------------------------------------------------

function fiscalyears_list($name, $selected_id=null, $submit_on_change=false)
{

	$sql = "SELECT * FROM ".TB_PREF."fiscal_year";

	// default to the company current fiscal year

	return combo_input($name, $selected_id, $sql, 'id', '',
		array(
			'order' => 'begin',
			'default' => get_company_pref('f_year'),
			'format' => '_format_fiscalyears',
			'select_submit'=> $submit_on_change,
			'async' => false
		) );
}

function _format_fiscalyears($row)
{
	return sql2date($row[1]) . "&nbsp;-&nbsp;" . sql2date($row[2])
	. "&nbsp;&nbsp;" . ($row[3] ? _('Closed') : _('Active'));
}

function fiscalyears_list_cells($label, $name, $selected_id=null,$submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo fiscalyears_list($name, $selected_id,$submit_on_change);
	echo "</td>\n";
}

function fiscalyears_list_row($label, $name, $selected_id=null,$submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	fiscalyears_list_cells(null, $name, $selected_id,$submit_on_change);
	echo "</tr>\n";
}
//------------------------------------------------------------------------------------

function dimensions_list($name, $selected_id=null, $no_option=false, $showname=' ',
	$submit_on_change=false, $showclosed=false, $showtype=1)
{
	$sql = "SELECT id, CONCAT(reference,'  ',name) as ref FROM ".TB_PREF."dimensions";

	$options = array(
		'order' => 'reference',
		'spec_option'=>$no_option ? $showname : false,
		'spec_id' => 0,
		'select_submit'=> $submit_on_change,
		'async' => false,
	);

	if (!$showclosed)
		$options['where'][] = "closed=0";
	if($showtype)
		$options['where'][] = "type_=".db_escape($showtype);

	return combo_input($name, $selected_id, $sql, 'id', 'ref', $options);
}

function dimensions_list_cells($label, $name, $selected_id=null, $no_option=false, $showname=null,
	$showclosed=false, $showtype=0, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo dimensions_list($name, $selected_id, $no_option, $showname, $submit_on_change, $showclosed, $showtype);
	echo "</td>\n";
}

function dimensions_list_row($label, $name, $selected_id=null, $no_option=false, $showname=null,
	$showclosed=false, $showtype=0, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	dimensions_list_cells(null, $name, $selected_id, $no_option, $showname,
		$showclosed, $showtype, $submit_on_change);
	echo "</tr>\n";
}

//---------------------------------------------------------------------------------------------------

function stock_items_list($name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $opts=array(), $editkey = false, $type = "stock",
	$only_rental_items=0, $expense_item=0)
{
	$sql = "SELECT stock_id, CONCAT(stock_id,' - ',s.description) As Description,
	c.description, s.inactive, s.editable FROM ".TB_PREF."stock_master s,".TB_PREF."stock_category c WHERE s.category_id=c.category_id";
			
	if($only_rental_items==1)
      $sql .= " AND tool_code_id!=0 AND rent_type=1";
    if($only_rental_items==2)
       $sql .= " AND tool_code_id!=0 AND rent_type=2";
	if($only_rental_items==3)
       $sql .= " AND (( tool_code_id!=0 AND rent_type=3) OR mb_flag='D')";   
    if($only_rental_items==4)
       $sql .= " AND tool_code_id!=0";
    if($only_rental_items==5)
       $sql .= " AND tool_code_id=0 AND expense_item=0";
	if($expense_item!=0)
      $sql .= " AND expense_item=1";
    
	if (isset($opts['fixed_asset']) && $opts['fixed_asset'])
		$sql .= " AND mb_flag='F'";
	else
		$sql .= " AND mb_flag!='F'";
		
	//$sql .= " AND !s.inactive";	
		
	//display_error($sql);	

		
	if ($editkey)
		set_editor('item', $name, $editkey);

	$ret = combo_input($name, $selected_id, $sql, 'stock_id', 's.description',
		array_merge(
		  array(
			'format' => '_format_stock_items',
			'spec_option' => $all_option===true ?  _("All Items") : $all_option,
			'spec_id' => ALL_TEXT,
			'search_box' => true,
			'search' => array("stock_id", "c.description","s.description"),
			'search_submit' => get_company_pref('no_item_list')!=0 && (!isset($opts['fixed_asset']) || !$opts['fixed_asset']),
			'size'=>10,
			'select_submit'=> $submit_on_change,
			'category' => 2,
			'order' => array('c.description','stock_id'),
			'editlink' => $editkey ? add_edit_combo('item') : false,
			'editable' => false,
			'max' => 255
		  ), $opts), $type );
	return $ret;
}

function _format_stock_items($row)
{
	return (user_show_codes() ?  ($row[0] . "&nbsp;-&nbsp;") : "") . $row[1];
}

// 0 => all , 1 => only rental items 2 => all items exclude rental items
function stock_items_list_cells($label, $name, $selected_id=null, $all_option=false,$submit_on_change=false, $all=false, $editkey = false, $opts= array(),
	$only_rental_items=0,$expense_item=0)
{
	if (isset($opts['fixed_asset']) && $opts['fixed_asset'])
		$editor_item = 'fa_item';
	else
		$editor_item = 'item';

// 	if ($editkey) ??
//		set_editor($editor_item, $name, $editkey);

	if ($label != null)
		echo "<td>$label</td>\n";

// ??
//  $opts = array_merge($options, array('cells'=>true, 'show_inactive'=>$all, 'new_icon' => $editkey ? 'item' : false));
//
//	echo stock_items_list($name, $selected_id, $all_option, $submit_on_change, $opts);

	echo stock_items_list($name, $selected_id, $all_option, $submit_on_change,
		array_merge(array('cells'=>true, 'show_inactive'=>$all), $opts), $editkey,'stock',$only_rental_items,
		$expense_item);

}
/*
function stock_items_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	echo "<tr>\n";
	stock_items_list_cells($label, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}
*/
//---------------------------------------------------------------------------------------------------
//
// Select item via foreign code.
//
function sales_items_list($name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $type='', $opts=array())
{
	// all sales codes
	$sql = "SELECT i.item_code, i.description, c.description, count(*)>1 as kit,
			 i.inactive, if(count(*)>1, '0', s.editable) as editable
			FROM
			".TB_PREF."stock_master s,
			".TB_PREF."item_codes i
			LEFT JOIN
			".TB_PREF."stock_category c
			ON i.category_id=c.category_id
			WHERE i.stock_id=s.stock_id
      AND mb_flag != 'F'";

	
	if ($type == 'local')	{ // exclude foreign codes
		$sql .=	" AND !i.is_foreign"; 
	} elseif ($type == 'kits') { // sales kits
		$sql .=	" AND !i.is_foreign AND i.item_code!=i.stock_id";
	}
	$sql .= " AND !i.inactive AND !s.inactive AND !s.no_sale";
	$sql .= " GROUP BY i.item_code";
	
	return combo_input($name, $selected_id, $sql, 'i.item_code', 'c.description',
		array_merge(
		  array(
			'format' => '_format_stock_items',
			'spec_option' => $all_option===true ?  _("All Items") : $all_option,
			'spec_id' => ALL_TEXT,
			'search_box' => true,
			'search' => array("i.item_code", "c.description", "i.description"),
			'search_submit' => get_company_pref('no_item_list')!=0,
			'size'=>15,
			'select_submit'=> $submit_on_change,
			'category' => 2,
			'order' => array('c.description','i.item_code'),
			'editable' => 30,
			'max' => 255
		  ), $opts), $type == 'kits' ? $type : "stock_sales" );
}

function sales_items_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $editkey=false)
{
	if ($editkey)
		set_editor('item', $name, $editkey);

	if ($label != null)
		echo "<td>$label</td>\n";
	echo sales_items_list($name, $selected_id, $all_option, $submit_on_change,
		'', array('cells'=>true, 'max'=>50)); // maximum is set to 50 and not default 255.
}

function sales_kits_list($name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	return sales_items_list($name, $selected_id, $all_option, $submit_on_change,
		'kits', array('cells'=>false, 'editable' => false));
}

function sales_local_items_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";
	echo sales_items_list($name, $selected_id, $all_option, $submit_on_change,
		'local', array('cells'=>false, 'editable' => false));
	echo "</td></tr>";
}
//------------------------------------------------------------------------------------

function stock_manufactured_items_list($name, $selected_id=null,
	$all_option=false, $submit_on_change=false)
{
	return stock_items_list($name, $selected_id, $all_option, $submit_on_change,
		array('where'=>array("mb_flag= 'M'")), false, "stock_manufactured");
}

function stock_manufactured_items_list_cells($label, $name, $selected_id=null,
				$all_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo stock_manufactured_items_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function stock_manufactured_items_list_row($label, $name, $selected_id=null,
		$all_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	stock_manufactured_items_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}
//------------------------------------------------------------------------------------

function stock_component_items_list($name, $parent_stock_id, $selected_id=null,
	$all_option=false, $submit_on_change=false, $editkey = false)
{
	$parent = db_escape($parent_stock_id);
	return stock_items_list($name, $selected_id, $all_option, $submit_on_change,
		array('where'=>array("stock_id != $parent"), 'parent'=> $parent_stock_id), $editkey, "component");
}


function stock_component_items_list_cells($label, $name, $parent_stock_id, 
	$selected_id=null, $all_option=false, $submit_on_change=false, $editkey = false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	$parent = db_escape($parent_stock_id);
	echo stock_items_list($name, $selected_id, $all_option, $submit_on_change,
		array('where'=>array("stock_id != '$parent_stock_id'"), 'cells'=>true, 'parent'=> $parent_stock_id), $editkey, "component");
}
//------------------------------------------------------------------------------------

function stock_costable_items_list($name, $selected_id=null,
	$all_option=false, $submit_on_change=false)
{
	return stock_items_list($name, $selected_id, $all_option, $submit_on_change,
		array('where'=>array("mb_flag!='D'")), false, "stock_costable");
}

function stock_costable_items_list_cells($label, $name, $selected_id=null, 
	$all_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo stock_items_list($name, $selected_id, $all_option, $submit_on_change,
		array('where'=>array("mb_flag!='D'"), 'cells'=>true), false, "stock_costable");
}

//------------------------------------------------------------------------------------
function stock_purchasable_items_list($name, $selected_id=null,	
	$all_option=false, $submit_on_change=false, $all=false, $editkey=false)
{
	return stock_items_list($name, $selected_id, $all_option, $submit_on_change,
		array('where'=>array("NOT no_purchase"), 
			'show_inactive'=>$all), $editkey, "stock_purchased");
}
//
//	This helper is used in PO/GRN/PI entry and supports editable descriptions.
//
function stock_purchasable_items_list_cells($label, $name, $selected_id=null,
			$all_option=false, $submit_on_change=false, $editkey=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo stock_items_list($name, $selected_id, $all_option, $submit_on_change,
		array('where'=>array("NOT no_purchase"), 
			 'editable' => 30,
			 'cells'=>true), $editkey);
}

//------------------------------------------------------------------------------------

function stock_item_types_list_row($label, $name, $selected_id=null, $enabled=true)
{
	global $stock_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $stock_types, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled) );
	echo "</td></tr>\n";
}

function stock_units_list_row($label, $name, $value=null, $enabled=true)
{
	$result = get_all_item_units();
	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	while($unit = db_fetch($result))
		$units[$unit['abbr']] = $unit['name'];

	echo array_selector($name, $value, $units, array( 'disabled' => !$enabled) );

	echo "</td></tr>\n";
}


//ravi
function stock_units_list_cells($name, $value=null, $enabled=true)
{
	echo "<td>";
	$result = get_all_item_units();
	while($unit = db_fetch($result))
		$units[$unit['abbr']] = $unit['name'];
	echo array_selector($name, $value, $units, array( 'disabled' => !$enabled) );
	echo "</td>";
}
//------------------------------------------------------------------------------------

function stock_purchasable_fa_list_cells($label, $name, $selected_id=null, $all_option=false,
	$submit_on_change=false, $all=false, $editkey = false, $exclude_items = array())
{
	// Check if a fixed asset has been bought.
	$where_opts[] = "stock_id NOT IN
    	( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE (type=".ST_SUPPRECEIVE." OR type=".ST_INVADJUST.") AND qty!=0 )";

	// exclude items currently on the order.
  	foreach($exclude_items as $item) {
    	$where_opts[] = "stock_id != ".db_escape($item->stock_id);
  	}
  	$where_opts[] = "mb_flag='F'";

	echo stock_items_list_cells($label, $name, $selected_id, $all_option, $submit_on_change, $all, $editkey, 
    array('fixed_asset' => true, 'where' => $where_opts));
}

function stock_disposable_fa_list($name, $selected_id=null,
	$all_option=false, $submit_on_change=false)
{
	// Check if a fixed asset has been bought....
	$where_opts[] = "stock_id IN
    	( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE type=".ST_SUPPRECEIVE." AND qty!=0 )";
	// ...but has not been disposed or sold already.
  	$where_opts[] = "stock_id NOT IN
		( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE (type=".ST_CUSTDELIVERY." OR type=".ST_INVADJUST.") AND qty<=0 )";

	$where_opts[] = "mb_flag='F'";

	echo stock_items_list($name, $selected_id, $all_option, $submit_on_change,
    array('fixed_asset' => true, 'where' => $where_opts));
}

function stock_disposable_fa_list_cells($label, $name, $selected_id=null,
	$all_option=false, $submit_on_change=false, $exclude_items = array())
{
	// Check if a fixed asset has been bought....
	$where_opts[] = "stock_id IN
    	( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE (type=".ST_SUPPRECEIVE." OR type=".ST_INVADJUST.") AND qty!=0 )";
	// ...but has not been disposed or sold already.
	$where_opts[] = "stock_id NOT IN
    	( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE (type=".ST_CUSTDELIVERY." OR type=".ST_INVADJUST.") AND qty<=0 )";

	$where_opts[] = "mb_flag='F'";

	foreach($exclude_items as $item) {
    	$where_opts[] = "stock_id != ".db_escape($item->stock_id);
	}

	if ($label != null)
		echo "<td>$label</td>\n";
	echo stock_items_list($name, $selected_id, $all_option, $submit_on_change, 
    array('fixed_asset' => true, 'cells'=>true, 'where' => $where_opts));
}

function stock_unpurchased_fa_list_cells($label, $name, $selected_id=null,
	$all_option=false, $submit_on_change=false, $exclude_items = array())
{
	$where_opts[] = "stock_id NOT IN
    	( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE qty!=0 )";

	$where_opts[] = "mb_flag='F'";

	foreach($exclude_items as $item) {
    	$where_opts[] = "stock_id != ".db_escape($item->stock_id);
	}

	if ($label != null)
		echo "<td>$label</td>\n";
	echo stock_items_list($name, $selected_id, $all_option, $submit_on_change, 
    array('fixed_asset' => true, 'cells'=>true, 'where' => $where_opts));
}


function stock_depreciable_fa_list_cells($label, $name, $selected_id=null,
	$all_option=false, $submit_on_change=false)
{

	// Check if a fixed asset has been bought....
	$where_opts[] = "stock_id IN
    	( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE (type=".ST_SUPPRECEIVE." OR type=".ST_INVADJUST.") AND qty!=0 )";
	// ...but has not been disposed or sold already.
	$where_opts[] = "stock_id NOT IN
    	( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE (type=".ST_CUSTDELIVERY." OR type=".ST_INVADJUST.") AND qty<=0 )";

	$year = get_current_fiscalyear();
	$begin = date2sql(add_months(sql2date($year['begin']), -1));
	$end = date2sql(add_months(sql2date($year['end']), -1));

	// check if current fiscal year
	$where_opts[] = "depreciation_date <= '".$end."'";
	$where_opts[] = "depreciation_date >= '".$begin."'";

	$where_opts[] = "material_cost > 0";
	$where_opts[] = "mb_flag='F'";

	if ($label != null)
		echo "<td>$label</td>\n";
	echo stock_items_list($name, $selected_id, $all_option, $submit_on_change,
		 array('fixed_asset' => true, 'where' => $where_opts, 'cells'=>true));
}

//------------------------------------------------------------------------------------

function tax_types_list($name, $selected_id=null, $none_option=false, $submit_on_change=false)
{
	$sql = "SELECT id, CONCAT(name, ' (',rate,'%)') as name FROM ".TB_PREF."tax_types";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'spec_option' => $none_option,
			'spec_id' => ALL_NUMERIC,
			'select_submit'=> $submit_on_change,
			'async' => false,
		) );
}

function tax_types_list_cells($label, $name, $selected_id=null, $none_option=false,
	$submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo tax_types_list($name, $selected_id, $none_option, $submit_on_change);
	echo "</td>\n";
}

function tax_types_list_row($label, $name, $selected_id=null, $none_option=false,
	$submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	tax_types_list_cells(null, $name, $selected_id, $none_option, $submit_on_change);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------

function tax_groups_list($name, $selected_id=null,
	$none_option=false, $submit_on_change=false)
{
	$sql = "SELECT id, name, inactive FROM ".TB_PREF."tax_groups";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'order' => 'id',
			'spec_option' => $none_option,
			'spec_id' => ALL_NUMERIC,
			'select_submit'=> $submit_on_change,
			'async' => false,
		) );
}

function tax_groups_list_cells($label, $name, $selected_id=null, $none_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo tax_groups_list($name, $selected_id, $none_option, $submit_on_change);
	echo "</td>\n";
}

function tax_groups_list_row($label, $name, $selected_id=null, $none_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	tax_groups_list_cells(null, $name, $selected_id, $none_option, $submit_on_change);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------

function item_tax_types_list($name, $selected_id=null, $show_inactive = false)
{
	$sql ="SELECT id, name FROM ".TB_PREF."item_tax_types";
	if (!$show_inactive)
		$sql .= " WHERE !inactive";

	return combo_input($name, $selected_id, $sql, 'id', 'name', array('order' => 'id') );
}

function item_tax_types_list_cells($label, $name, $selected_id=null, $show_inactive = false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo item_tax_types_list($name, $selected_id, $show_inactive);
	echo "</td>\n";
}

function item_tax_types_list_row($label, $name, $selected_id=null, $show_inactive = false)
{
	echo "<tr><td class='label'>$label</td>";
	item_tax_types_list_cells(null, $name, $selected_id, $show_inactive);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------

function shippers_list($name, $selected_id=null)
{
	$sql = "SELECT shipper_id, shipper_name, inactive FROM ".TB_PREF."shippers";
	return combo_input($name, $selected_id, $sql, 'shipper_id', 'shipper_name', 
		array('order'=>array('shipper_name')));
}

function shippers_list_cells($label, $name, $selected_id=null)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo shippers_list($name, $selected_id);
	echo "</td>\n";
}

function shippers_list_row($label, $name, $selected_id=null)
{
	echo "<tr><td class='label'>$label</td>";
	shippers_list_cells(null, $name, $selected_id);
	echo "</tr>\n";
}

//-------------------------------------------------------------------------------------

function sales_persons_list($name, $selected_id=null, $spec_opt=false)
{
	$sql = "SELECT salesman_code, salesman_name, inactive FROM ".TB_PREF."salesman";
	return combo_input($name, $selected_id, $sql, 'salesman_code', 'salesman_name', 
		array('order'=>array('salesman_name'),
			'spec_option' => $spec_opt,
			'spec_id' => ALL_NUMERIC));
}

function sales_persons_list_cells($label, $name, $selected_id=null, $spec_opt=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>\n";
	echo sales_persons_list($name, $selected_id, $spec_opt);
	echo "</td>\n";
}

function sales_persons_list_row($label, $name, $selected_id=null, $spec_opt=false)
{
	echo "<tr><td class='label'>$label</td>";
	sales_persons_list_cells(null, $name, $selected_id, $spec_opt);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------

function sales_areas_list($name, $selected_id=null, $special_option=false, $submit_on_change=false)
{
	$sql = "SELECT area_code, description, inactive FROM ".TB_PREF."areas";
	return combo_input($name, $selected_id, $sql, 'area_code', 'description', 
		array('spec_option' => $special_option===true ? ' ' : $special_option,
				'select_submit'=> $submit_on_change,
		'order' => 'description', 'spec_id' => 0));
}

function sales_areas_list_cells($label, $name, $selected_id=null, $special_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo sales_areas_list($name, $selected_id, $special_option, $submit_on_change);
	echo "</td>\n";
}

function sales_areas_list_row($label, $name, $selected_id=null, $special_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	sales_areas_list_cells(null, $name, $selected_id, $special_option, $submit_on_change);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------

function sales_groups_list($name, $selected_id=null, $special_option=false)
{
	$sql = "SELECT id, description, inactive FROM `".TB_PREF."groups`";
	return combo_input($name, $selected_id, $sql, 'id', 'description', array(
		'spec_option' => $special_option===true ? ' ' : $special_option,
		'order' => 'description', 'spec_id' => 0,
	));
}

function sales_groups_list_cells($label, $name, $selected_id=null, $special_option=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo sales_groups_list($name, $selected_id, $special_option);
	echo "</td>\n";
}

function sales_groups_list_row($label, $name, $selected_id=null, $special_option=false)
{
	echo "<tr><td class='label'>$label</td>";
	sales_groups_list_cells(null, $name, $selected_id, $special_option);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------

function _format_template_items($row)
{
	return ($row[0] . "&nbsp;- &nbsp;" . _("Amount") . "&nbsp;".$row[1]);
}

function templates_list($name, $selected_id=null, $special_option=false)
{
	$sql = "SELECT sorder.order_no,	Sum(line.unit_price*line.quantity*(1-line.discount_percent)) AS OrderValue
		FROM ".TB_PREF."sales_orders as sorder, ".TB_PREF."sales_order_details as line
		WHERE sorder.order_no = line.order_no AND sorder.type = 1 GROUP BY line.order_no";
	return combo_input($name, $selected_id, $sql, 'order_no', 'OrderValue', array(
		'format' => '_format_template_items',
		'spec_option' => $special_option===true ? ' ' : $special_option,
		'order' => 'order_no', 'spec_id' => 0,
	));
}

function templates_list_cells($label, $name, $selected_id=null, $special_option=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo templates_list($name, $selected_id, $special_option);
	echo "</td>\n";
}

function templates_list_row($label, $name, $selected_id=null, $special_option=false)
{
	echo "<tr><td class='label'>$label</td>";
	templates_list_cells(null, $name, $selected_id, $special_option);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------

function workorders_list($name, $selected_id=null)
{
	$sql = "SELECT id, wo_ref FROM ".TB_PREF."workorders WHERE closed=0";
	return combo_input($name, $selected_id, $sql, 'id', 'wo_ref', array());
}

function workorders_list_cells($label, $name, $selected_id=null)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo workorders_list($name, $selected_id);
	echo "</td>\n";
}

function workorders_list_row($label, $name, $selected_id=null)
{
	echo "<tr><td class='label'>$label</td>";
	workorders_list_cells(null, $name, $selected_id);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------

function payment_terms_list($name, $selected_id=null)
{
	$sql = "SELECT terms_indicator, terms, inactive FROM ".TB_PREF."payment_terms";
	return combo_input($name, $selected_id, $sql, 'terms_indicator', 'terms', array());
}

function payment_terms_list_cells($label, $name, $selected_id=null)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo payment_terms_list($name, $selected_id);
	echo "</td>\n";
}

function payment_terms_list_row($label, $name, $selected_id=null)
{
	echo "<tr><td class='label'>$label</td>";
	payment_terms_list_cells(null, $name, $selected_id);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------

function credit_status_list($name, $selected_id=null)
{
	$sql ="SELECT id, reason_description, inactive FROM ".TB_PREF."credit_status";
	return combo_input($name, $selected_id, $sql, 'id', 'reason_description', array());
}

function credit_status_list_cells($label, $name, $selected_id=null)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo credit_status_list($name, $selected_id);
	echo "</td>\n";
}

function credit_status_list_row($label, $name, $selected_id=null)
{
	echo "<tr><td class='label'>$label</td>";
	credit_status_list_cells(null, $name, $selected_id);
	echo "</tr>\n";
}

//-----------------------------------------------------------------------------------------------

function sales_types_list($name, $selected_id=null, $submit_on_change=false, $special_option=false)
{
	$sql = "SELECT id, sales_type, inactive FROM ".TB_PREF."sales_types";

	return combo_input($name, $selected_id, $sql, 'id', 'sales_type',
	array(
		'spec_option' => $special_option===true ? _("All Sales Types") : $special_option,
		'spec_id' => 0,
		'select_submit'=> $submit_on_change,
	) );
}

function sales_types_list_cells($label, $name, $selected_id=null, $submit_on_change=false, $special_option=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo sales_types_list($name, $selected_id, $submit_on_change, $special_option);
	echo "</td>\n";
}

function sales_types_list_row($label, $name, $selected_id=null, $submit_on_change=false, $special_option=false)
{
	echo "<tr><td class='label'>$label</td>";
	sales_types_list_cells(null, $name, $selected_id, $submit_on_change, $special_option);
	echo "</tr>\n";
}

//-----------------------------------------------------------------------------------------------

function _format_date($row)
{
	return sql2date($row['reconciled']);
}

function bank_reconciliation_list($account, $name, $selected_id=null, $submit_on_change=false, $special_option=false)
{
	$sql = "SELECT reconciled, reconciled FROM ".TB_PREF."bank_trans
		WHERE bank_act=".db_escape($account)." AND reconciled IS NOT NULL
		GROUP BY reconciled";
	return combo_input($name, $selected_id, $sql, 'id', 'reconciled',
		array(
			'spec_option' => $special_option,
			'format' => '_format_date',
			'spec_id' => '',
			'select_submit'=> $submit_on_change
		) );
}

function bank_reconciliation_list_cells($label,$account, $name, $selected_id=null, $submit_on_change=false, $special_option=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo bank_reconciliation_list($account, $name, $selected_id, $submit_on_change, $special_option);
	echo "</td>\n";
}
/*
function bank_reconciliation_list_row($label, $account, $name, $selected_id=null, $submit_on_change=false, $special_option=false)
{
	echo "<tr>\n";
	bank_reconciliation_list_cells($label, $account, $name, $selected_id, $submit_on_change, $special_option);
	echo "</tr>\n";
}
*/
//-----------------------------------------------------------------------------------------------

function workcenter_list($name, $selected_id=null, $all_option=false)
{

	$sql = "SELECT id, name, inactive FROM ".TB_PREF."workcentres";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'spec_option' =>$all_option===true ? _("All Suppliers") : $all_option,
			'spec_id' => ALL_TEXT,
		) );
}

function workcenter_list_cells($label, $name, $selected_id=null, $all_option=false)
{
	default_focus($name);
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo workcenter_list($name, $selected_id, $all_option);
	echo "</td>\n";
}

function workcenter_list_row($label, $name, $selected_id=null, $all_option=false)
{
	echo "<tr><td class='label'>$label</td>";
	workcenter_list_cells(null, $name, $selected_id, $all_option);
	echo "</tr>\n";
}

//-----------------------------------------------------------------------------------------------

function bank_accounts_list($name, $selected_id=null, $submit_on_change=false, $spec_option=false)
{
	$sql = "SELECT id, bank_account_name, bank_curr_code, inactive
		FROM ".TB_PREF."bank_accounts";

	return combo_input($name, $selected_id, $sql, 'id', 'bank_account_name',
		array(
			'format' => '_format_add_curr',
			'select_submit'=> $submit_on_change,
			'spec_option' => $spec_option,
			'spec_id' => '',
			'async' => false
		) );
}

function bank_accounts_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo bank_accounts_list($name, $selected_id, $submit_on_change);
	echo "</td>\n";
}

function bank_accounts_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	bank_accounts_list_cells(null, $name, $selected_id, $submit_on_change);
	echo "</tr>\n";
}
//-----------------------------------------------------------------------------------------------

function cash_accounts_list_row($label, $name, $selected_id=null, $submit_on_change=false, $all_option=false)
{

	$sql = "SELECT id, bank_account_name, bank_curr_code, inactive
		FROM ".TB_PREF."bank_accounts
		WHERE account_type=".BT_CASH;

	if ($label != null)
		echo "<tr><td class='label'>$label</td>\n";
	echo "<td>";
	echo combo_input($name, $selected_id, $sql, 'id', 'bank_account_name',
		array(
			'spec_option' => $all_option,
			'spec_id' => ALL_TEXT,
			'format' => '_format_add_curr',
			'select_submit'=> $submit_on_change,
			'async' => true
		) );
	echo "</td></tr>\n";
}
//-----------------------------------------------------------------------------------------------

function pos_list_row($label, $name, $selected_id=null, $spec_option=false, $submit_on_change=false)
{
	$sql = "SELECT id, pos_name, inactive FROM ".TB_PREF."sales_pos";

	default_focus($name);
	echo '<tr>';
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo combo_input($name, $selected_id, $sql, 'id', 'pos_name',
		array(
			'select_submit'=> $submit_on_change,
			'async' => true,
			'spec_option' =>$spec_option,
			'spec_id' => -1,
			'order'=> array('pos_name')
		) );
	echo "</td></tr>\n";

}
//-----------------------------------------------------------------------------------------------
// Payment type selector for current user.
//
function sale_payment_list($name, $category, $selected_id=null, $submit_on_change=true, $prepayments=true)
{
	$sql = "SELECT terms_indicator, terms, inactive FROM ".TB_PREF."payment_terms";

	if ($category == PM_CASH) // only cash
			$sql .= " WHERE days_before_due=0 AND day_in_following_month=0";
	elseif ($category == PM_CREDIT) // only delayed payments
			$sql .= " WHERE days_before_due".($prepayments ? '!=': '>')."0 OR day_in_following_month!=0";
	elseif (!$prepayments)
			$sql .= " WHERE days_before_due>=0";

	return combo_input($name, $selected_id, $sql, 'terms_indicator', 'terms',
		array(
			'select_submit'=> $submit_on_change,
			'async' => true
		) );

}

function sale_payment_list_cells($label, $name, $category, $selected_id=null, $submit_on_change=true, $prepayments=true)
{
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo sale_payment_list($name, $category, $selected_id, $submit_on_change, $prepayments);

	echo "</td>\n";
}
//-----------------------------------------------------------------------------------------------

function class_list($name, $selected_id=null, $submit_on_change=false)
{
	$sql = "SELECT cid, class_name FROM ".TB_PREF."chart_class";

	return combo_input($name, $selected_id, $sql, 'cid', 'class_name',
		array(
			'select_submit'=> $submit_on_change,
			'async' => false
		) );

}

function class_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo class_list($name, $selected_id, $submit_on_change);
	echo "</td>\n";
}

function class_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	class_list_cells(null, $name, $selected_id, $submit_on_change);
	echo "</tr>\n";
}

//-----------------------------------------------------------------------------------------------
function stock_categories_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $fixed_asset=false)
{
	$where_opts = array();
	if ($fixed_asset)
		$where_opts[0] = "dflt_mb_flag='F'";
	else
		$where_opts[0] = "dflt_mb_flag!='F'";

	$sql = "SELECT category_id, description, inactive FROM ".TB_PREF."stock_category";
	
	//display_error($sql);
	
	return combo_input($name, $selected_id, $sql, 'category_id', 'description',
 		array('order'=>'category_id',
			'spec_option' => $spec_opt,
			'spec_id' => -1,
 			'select_submit'=> $submit_on_change,
 			'async' => true,
			'where' => $where_opts,
 		));
}

function stock_categories_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo stock_categories_list($name, $selected_id, $spec_opt, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function stock_categories_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	stock_categories_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}

//-----------------------------------------------------------------------------------------------
function fixed_asset_classes_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	$sql = "SELECT c.fa_class_id, CONCAT(c.fa_class_id,' - ',c.description) `desc`, CONCAT(p.fa_class_id,' - ',p.description) `class`, c.inactive FROM "
		.TB_PREF."stock_fa_class c LEFT JOIN ".TB_PREF."stock_fa_class p ON c.parent_id=p.fa_class_id";

	return combo_input($name, $selected_id, $sql, 'c.fa_class_id', 'desc',
		array('order'=>'c.fa_class_id',
			'spec_option' => $spec_opt,
			'spec_id' => '-1',
 			'select_submit'=> $submit_on_change,
 			'async' => true,
			'search_box' => true,
			'search' => array("c.fa_class_id"),
		    'search_submit' => false,
		    'spec_id' => '',
		    'size' => 3,
		    'max' => 3,
			'category' => 'class',
 		));
}

function fixed_asset_classes_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	echo "<td>";
	echo fixed_asset_classes_list($name, $selected_id, $spec_opt, $submit_on_change);
	echo "</td></tr>\n";
}

//-----------------------------------------------------------------------------------------------

function gl_account_types_list($name, $selected_id=null, $all_option=false, $all=true)
{

	$sql = "SELECT id, name FROM ".TB_PREF."chart_types";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'format' => '_format_account',
			'order' => array('class_id', 'id', 'parent'),
			'spec_option' =>$all_option,
			'spec_id' => ALL_TEXT
		) );
}

function gl_account_types_list_cells($label, $name, $selected_id=null, $all_option=false, $all=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo gl_account_types_list($name, $selected_id, $all_option, $all);
	echo "</td>\n";
}

function gl_account_types_list_row($label, $name, $selected_id=null, $all_option=false,	$all=false)
{
	echo "<tr><td class='label'>$label</td>";
	gl_account_types_list_cells(null, $name, $selected_id, $all_option,
		$all);
	echo "</tr>\n";
}

//-----------------------------------------------------------------------------------------------
function gl_all_accounts_list($name, $selected_id=null, $skip_bank_accounts=false,
	$cells=false, $all_option=false, $submit_on_change=false, $all=false, $type_id=false)
{
	if ($skip_bank_accounts)
		$sql = "SELECT chart.account_code, chart.account_name, type.name, chart.inactive, type.id
			FROM (".TB_PREF."chart_master chart,".TB_PREF."chart_types type) "
			."LEFT JOIN ".TB_PREF."bank_accounts acc "
			."ON chart.account_code=acc.account_code
				WHERE acc.account_code  IS NULL
			AND chart.account_type=type.id";
	else
		$sql = "SELECT chart.account_code, chart.account_name, type.name, chart.inactive, type.id
			FROM ".TB_PREF."chart_master chart,".TB_PREF."chart_types type
			WHERE chart.account_type=type.id";
	if ($type_id ) $sql .= " AND chart.account_type=".db_escape($type_id);
	$account = ($skip_bank_accounts ? "account2" : "account");
	return combo_input($name, $selected_id, $sql, 'chart.account_code', 'chart.account_name',
		array(
			'format' => '_format_account',
			'spec_option' => $all_option===true ?  _("Use Item Sales Accounts") : $all_option,
			'spec_id' => '',
			'type' => 2,
			'order' => array('type.class_id','type.id','account_code'),
			'search_box' => $cells,
			'search_submit' => false,
			'size' => 12,
			'max' => 10,
			'cells' => true,
			'select_submit'=> $submit_on_change,
			'async' => false,
			'category' => 2,
			'show_inactive' => $all
		), $account );
}

function _format_account($row)
{
	return $row[0] .  "&nbsp;&nbsp;&nbsp;&nbsp;" . $row[1];
}

function gl_all_accounts_list_cells($label, $name, $selected_id=null, 
	$skip_bank_accounts=false, $cells=false, $all_option=false, 
	$submit_on_change=false, $all=false, $type_id=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo gl_all_accounts_list($name, $selected_id, 
		$skip_bank_accounts, $cells, $all_option, $submit_on_change, $all, $type_id);
	echo "</td>\n";
}

function gl_all_accounts_list_row($label, $name, $selected_id=null, 
	$skip_bank_accounts=false, $cells=false, $all_option=false, $submit_on_change=false, $all=false, $type_id=false)
{
	echo "<tr><td class='label'>$label</td>";
	gl_all_accounts_list_cells(null, $name, $selected_id, 
		$skip_bank_accounts, $cells, $all_option, $submit_on_change, $all, $type_id);
	echo "</tr>\n";
}

function yesno_list($name, $selected_id=null, $name_yes="", $name_no="", 
$submit_on_change=false)
{
	$items = array();
	$items['0'] = strlen($name_no) ? $name_no : _("No");
	$items['1'] = strlen($name_yes) ? $name_yes : _("Yes");

	return array_selector($name, $selected_id, $items, 
		array( 
			'select_submit'=> $submit_on_change,
			'async' => false ) ); // FIX?
}

function yesno_list_cells($label, $name, $selected_id=null, $name_yes="", $name_no="", 
$submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo yesno_list($name, $selected_id, $name_yes, $name_no, $submit_on_change);
	echo "</td>\n";
}

function noyes_list($name, $selected_id=null, $name_yes="", $name_no="", 
$submit_on_change=false)
{
	$items = array();
	$items['0'] = strlen($name_no) ? $name_no : _("Yes");
	$items['1'] = strlen($name_yes) ? $name_yes : _("No");

	return array_selector($name, $selected_id, $items, 
		array( 
			'select_submit'=> $submit_on_change,
			'async' => false ) ); // FIX?
}

function noyes_list_cells($label, $name, $selected_id=null, $name_yes="", $name_no="", 
$submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo noyes_list($name, $selected_id, $name_yes, $name_no, $submit_on_change);
	echo "</td>\n";
}

function yesno_list_row($label, $name, $selected_id=null, $name_yes="", $name_no="", 
$submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	yesno_list_cells(null, $name, $selected_id, $name_yes, $name_no, $submit_on_change);
	echo "</tr>\n";
}

//---------------------------------------------------------------------------------------

function yes_or_no_list($name, $selected_id=null, $name_yes="", $name_no="", $submit_on_change=false)
{
	$items = array();
	
	$items['1'] = strlen($name_yes) ? $name_yes : _("Yes");
	$items['0'] = strlen($name_no) ? $name_no : _("No");

	return array_selector($name, $selected_id, $items, 
		array( 
			'select_submit'=> $submit_on_change,
			'async' => false ) ); // FIX?
}

function yes_or_no_list_cells($label, $name, $selected_id=null, $name_yes="", $name_no="", $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo yes_or_no_list($name, $selected_id, $name_yes, $name_no, $submit_on_change);
	echo "</td>\n";
}

function yes_or_no_list_row($label, $name, $selected_id=null, $name_yes="", $name_no="", $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	yes_or_no_list_cells(null, $name, $selected_id, $name_yes, $name_no, $submit_on_change);
	echo "</tr>\n";
}


//--------------------------------------------------------------------------------

function languages_list($name, $selected_id=null, $all_option=false)
{
	global $installed_languages;

	$items = array();
	if ($all_option)
		$items[''] = $all_option;
	foreach ($installed_languages as $lang)
			$items[$lang['code']] = $lang['name'];
	return array_selector($name, $selected_id, $items);
}

function languages_list_cells($label, $name, $selected_id=null, $all_option=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo languages_list($name, $selected_id, $all_option);
	echo "</td>\n";
}

function languages_list_row($label, $name, $selected_id=null, $all_option=false)
{
	echo "<tr><td class='label'>$label</td>";
	languages_list_cells(null, $name, $selected_id, $all_option);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------------------

function bank_account_types_list($name, $selected_id=null)
{
	global $bank_account_types;

	return array_selector($name, $selected_id, $bank_account_types);
}

function bank_account_types_list_cells($label, $name, $selected_id=null)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo bank_account_types_list($name, $selected_id);
	echo "</td>\n";
}

function bank_account_types_list_row($label, $name, $selected_id=null)
{
	echo "<tr><td class='label'>$label</td>";
	bank_account_types_list_cells(null, $name, $selected_id);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------------------
function payment_person_types_list($name, $selected_id=null, $submit_on_change=false)
{
	global $payment_person_types;

	$items = array();
	foreach ($payment_person_types as $key=>$type)
	{
		if ($key != PT_WORKORDER)
			$items[$key] = $type;
	}		
	return array_selector($name, $selected_id, $items, 
		array( 'select_submit'=> $submit_on_change ) );
}

function payment_person_types_list_cells($label, $name, $selected_id=null, $related=null)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo payment_person_types_list($name, $selected_id, $related);
	echo "</td>\n";
}

function payment_person_types_list_row($label, $name, $selected_id=null, $related=null)
{
	echo "<tr><td class='label'>$label</td>";
	payment_person_types_list_cells(null, $name, $selected_id, $related);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------------------

function wo_types_list($name, $selected_id=null)
{
	global $wo_types_array;
	
	return array_selector($name, $selected_id, $wo_types_array, 
		array( 'select_submit'=> true, 'async' => true ) );
}

function wo_types_list_row($label, $name, $selected_id=null)
{
	echo "<tr><td class='label'>$label</td><td>\n";
	echo wo_types_list($name, $selected_id);
	echo "</td></tr>\n";
}

//------------------------------------------------------------------------------------------------

function dateformats_list_row($label, $name, $value=null)
{
	global $SysPrefs;

	echo "<tr><td class='label'>$label</td>\n<td>";
	echo array_selector( $name, $value, $SysPrefs->dateformats );
	echo "</td></tr>\n";
}

function dateseps_list_row($label, $name, $value=null)
{
	global $SysPrefs;

	echo "<tr><td class='label'>$label</td>\n<td>";
	echo array_selector( $name, $value, $SysPrefs->dateseps );
	echo "</td></tr>\n";
}

function thoseps_list_row($label, $name, $value=null)
{
	global $SysPrefs;

	echo "<tr><td class='label'>$label</td>\n<td>";
	echo array_selector( $name, $value, $SysPrefs->thoseps );
	echo "</td></tr>\n";
}

function decseps_list_row($label, $name, $value=null)
{
	global $SysPrefs;

	echo "<tr><td class='label'>$label</td>\n<td>";
	echo array_selector( $name, $value, $SysPrefs->decseps );
	echo "</td></tr>\n";
}

function themes_list_row($label, $name, $value=null)
{
	global $path_to_root;

	$path = $path_to_root.'/themes/';
	$themes = array();
	$themedir = opendir($path);
	while(false !== ($fname = readdir($themedir)))
	{
		if($fname!='.' && $fname!='..' && $fname!='CVS' && is_dir($path.$fname))
		{
			$themes[$fname] =  $fname;
		}
	}
	ksort($themes);

	echo "<tr><td class='label'>$label</td>\n<td>";
	echo array_selector( $name, $value, $themes );
	echo "</td></tr>\n";
}

function pagesizes_list_row($label, $name, $value=null)
{
	global $SysPrefs;

	$items = array();
	foreach ($SysPrefs->pagesizes as $pz)
		$items[$pz] = $pz;

	echo "<tr><td class='label'>$label</td>\n<td>";
	echo array_selector( $name, $value, $items );
	echo "</td></tr>\n";
}

function systypes_list($name, $value=null, $spec_opt=false, $submit_on_change=false, $exclude=array())
{
	global $systypes_array;

	// emove non-voidable transactions if needed
	$systypes = array_diff_key($systypes_array, array_flip($exclude));
	return array_selector($name, $value, $systypes, 
		array( 
			'spec_option'=> $spec_opt,
			'spec_id' => ALL_NUMERIC,
			'select_submit'=> $submit_on_change,
			'async' => false,
			)
	);
}

function systypes_list_cells($label, $name, $value=null, $submit_on_change=false, $exclude=array())
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo systypes_list($name, $value, false, $submit_on_change, $exclude);
	echo "</td>\n";
}

function systypes_list_row($label, $name, $value=null, $submit_on_change=false, $exclude=array())
{
	echo "<tr><td class='label'>$label</td>";
	systypes_list_cells(null, $name, $value, $submit_on_change, $exclude);
	echo "</tr>\n";
}

function journal_types_list_cells($label, $name, $value=null, $submit_on_change=false)
{
	global $systypes_array;

	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";

	$items = $systypes_array;

	// exclude quotes, orders and dimensions
	foreach (array(ST_PURCHORDER, ST_SALESORDER, ST_DIMENSION, ST_SALESQUOTE, ST_LOCTRANSFER) as $excl)
			unset($items[$excl]);
	
	echo array_selector($name, $value, $items, 
		array( 
			'spec_option'=> _("All"),
			'spec_id' => -1,
			'select_submit'=> $submit_on_change,
			'async' => false
			)
	);
	echo "</td>\n";
}

function cust_allocations_list_cells($label, $name, $selected=null, $submit_on_change=false)
{

	if ($label != null)
		label_cell($label);
	echo "<td>\n";
	$allocs = array( 
		ALL_TEXT=>_("All Types"),
		'1'=> _("Sales Invoices"),
		'2'=> _("Unsettled transactions"),
		'3' => _("Payments"),
		'4' => _("Credit Notes"),
		'5' => _("Delivery Notes"),
		'6' => _("PDC Entries")
	);
	echo array_selector($name, $selected, $allocs, array('select_submit'=> $submit_on_change));
	echo "</td>\n";
}

function supp_allocations_list_cell($name, $selected=null)
{

	echo "<td>\n";
	$allocs = array( 
		ALL_TEXT=>_("All Types"),
		'1'=> _("Invoices"),
		'2'=> _("Overdue Invoices"),
		'3' => _("Payments"),
		'4' => _("Credit Notes"),
		'5' => _("Overdue Credit Notes")
	);
	echo array_selector($name, $selected, $allocs);
	echo "</td>\n";
}

function supp_transactions_list_cell($name, $selected=null, $submit_on_change=false)
{

	echo "<td>\n";
	$allocs = array( 
		ALL_TEXT=>_("All Types"),
		'6'=>_("GRNs"),
		'1'=> _("Invoices"),
		'2'=> _("Unsettled transactions"),
		'3' => _("Payments"),
		'4' => _("Credit Notes"),
		'5' => _("Overdue Credit Notes")
	);

	echo array_selector($name, $selected, $allocs, array('select_submit'=> $submit_on_change));
	echo "</td>\n";
}

function policy_list_cells($label, $name, $selected=null)
{
	if ($label != null)
		label_cell($label);
	echo "<td>\n";
	echo array_selector($name, $selected, 
				array( '' => _("Automatically put balance on back order"),
			 		'CAN' => _("Cancel any quantites not delivered")) );
	echo "</td>\n";
}

function policy_list_row($label, $name, $selected=null)
{
	echo "<tr><td class='label'>$label</td>";
	policy_list_cells(null, $name, $selected);
	echo "</tr>\n";
}

function credit_type_list_cells($label, $name, $selected=null, $submit_on_change=false)
{
	if ($label != null)
		label_cell($label);
	echo "<td>\n";
	echo array_selector($name, $selected, 
			array( 'Return' => _("Items Returned to Inventory Location"),
		 		'WriteOff' => _("Items Written Off")),
			array( 'select_submit'=> $submit_on_change ) );
	echo "</td>\n";
}

function credit_type_list_row($label, $name, $selected=null, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	credit_type_list_cells(null, $name, $selected, $submit_on_change);
	echo "</tr>\n";
}

function number_list($name, $selected, $from, $to, $no_option=false)
{
	$items = array();
	for ($i = $from; $i <= $to; $i++)
		$items[$i] = "$i";

	return array_selector($name, $selected, $items,
			array(	'spec_option' => $no_option,
					'spec_id' => ALL_NUMERIC) );
}

function number_list_cells($label, $name, $selected, $from, $to, $no_option=false)
{
	if ($label != null)
		label_cell($label);
	echo "<td>\n";
	echo number_list($name, $selected, $from, $to, $no_option);
	echo "</td>\n";
}

function number_list_row($label, $name, $selected, $from, $to, $no_option=false)
{
	echo "<tr><td class='label'>$label</td>";
	echo number_list_cells(null, $name, $selected, $from, $to, $no_option);
	echo "</tr>\n";
}

function print_profiles_list_row($label, $name, $selected_id=null, $spec_opt=false,
	$submit_on_change=true)
{
	$sql = "SELECT profile FROM ".TB_PREF."print_profiles"
		." GROUP BY profile";
	$result = db_query($sql, 'cannot get all profile names');
	$profiles = array();
	while($myrow=db_fetch($result)) {
		$profiles[$myrow['profile']] = $myrow['profile'];
	}

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $profiles, 
		array( 'select_submit'=> $submit_on_change,
			'spec_option'=>$spec_opt,
			'spec_id' => ''
		 ));

	echo "</td></tr>\n";
}

function printers_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	static $printers; // query only once for page display

	if (!$printers) {
		$sql = "SELECT id, name, description FROM ".TB_PREF."printers";	
		$result = db_query($sql, 'cannot get all printers');
		$printers = array();
		while($myrow=db_fetch($result)) {
			$printers[$myrow['id']] = $myrow['name'].'&nbsp;-&nbsp;'.$myrow['description'];
		}
	}
	return array_selector($name, $selected_id, $printers, 
		array( 'select_submit'=> $submit_on_change,
			'spec_option'=>$spec_opt,
			'spec_id' => ''
		 ));
}

//------------------------------------------------------------------------------------------------

function quick_entries_list($name, $selected_id=null, $type=null, $submit_on_change=false)
{
	$where = false;
	$sql = "SELECT id, description FROM ".TB_PREF."quick_entries";
	if ($type != null)
		$sql .= " WHERE type=$type";

	return combo_input($name, $selected_id, $sql, 'id', 'description',
		array(
			'spec_id' => '',
			'order' => 'description',
			'select_submit'=> $submit_on_change,
			'async' => false
		) );

}

function quick_entries_list_cells($label, $name, $selected_id, $type, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";	
	echo quick_entries_list($name, $selected_id, $type, $submit_on_change);
	echo "</td>";
}

function quick_entries_list_row($label, $name, $selected_id, $type, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	quick_entries_list_cells(null, $name, $selected_id, $type, $submit_on_change);
	echo "</tr>\n";
}


function quick_actions_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $quick_actions;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $quick_actions, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

function quick_entry_types_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $quick_entry_types;
		
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $quick_entry_types, 
		array( 
			'select_submit'=> $submit_on_change
			) );
	echo "</td></tr>\n";
}

function record_status_list_row($label, $name) {
	return yesno_list_row($label, $name, null, 	_('Inactive'), _('Active'));
}

function class_types_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $class_types;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $class_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

//------------------------------------------------------------------------------------------------

function security_roles_list($name, $selected_id=null, $new_item=false, $submit_on_change=false,
	$show_inactive = false)
{

	$sql = "SELECT id, role, inactive FROM ".TB_PREF."security_roles";

	return combo_input($name, $selected_id, $sql, 'id', 'description',
		array(
			'spec_option'=>$new_item ? _("New role") : false,
			'spec_id' => '',
			'select_submit'=> $submit_on_change,
			'show_inactive' => $show_inactive
		) );
}

function security_roles_list_cells($label, $name, $selected_id=null, $new_item=false, $submit_on_change=false,
	$show_inactive = false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo security_roles_list($name, $selected_id, $new_item, $submit_on_change, $show_inactive);
	echo "</td>\n";
}

function security_roles_list_row($label, $name, $selected_id=null, $new_item=false, $submit_on_change=false,
	$show_inactive = false)
{
	echo "<tr><td class='label'>$label</td>";
	security_roles_list_cells(null, $name, $selected_id, $new_item, $submit_on_change, $show_inactive);
	echo "</tr>\n";
}

function tab_list_row($label, $name, $selected_id=null)
{
	global $installed_extensions;
	
	$tabs = array();
	foreach ($_SESSION['App']->applications as $app) {
		$tabs[$app->id] = access_string($app->name, true);
	}
	echo "<tr>\n";
	echo "<td class='label'>$label</td><td>\n";
	echo array_selector($name, $selected_id, $tabs);
	echo "</td></tr>\n";
}

//-----------------------------------------------------------------------------------------------

function tag_list($name, $height, $type, $multi=false, $all=false, $spec_opt = false)
{
	// Get tags
	global $path_to_root;
	include_once($path_to_root . "/admin/db/tags_db.inc");
	$results = get_tags($type, $all);

	while ($tag = db_fetch($results))
		$tags[$tag['id']] = $tag['name'];
	
	if (!isset($tags)) {
		$tags[''] = $all ? _("No tags defined.") : _("No active tags defined.");
		$spec_opt = false;
	}
	return array_selector($name, null, $tags,
		array(
			'multi' => $multi,
			'height' => $height,
			'spec_option'=> $spec_opt,
			'spec_id' => -1,
		) );
}

function tag_list_cells($label, $name, $height, $type, $mult=false, $all=false, $spec_opt = false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>\n";
	echo tag_list($name, $height, $type, $mult, $all, $spec_opt);
	echo "</td>\n";
	
}

function tag_list_row($label, $name, $height, $type, $mult=false, $all=false, $spec_opt = false)
{
	echo "<tr><td class='label'>$label</td>";
	tag_list_cells(null, $name, $height, $type, $mult, $all, $spec_opt);
	echo "</tr>\n";	
}

//---------------------------------------------------------------------------------------------
//	List of sets of active extensions 
//
function extset_list($name, $value=null, $submit_on_change=false)
{
	global $db_connections;

	$items = array();
	foreach ($db_connections as $comp)
		$items[] = sprintf(_("Activated for '%s'"), $comp['name']);
	return array_selector( $name, $value, $items,
		array(
			'spec_option'=> _("Available and/or installed"),
			'spec_id' => -1,
			'select_submit'=> $submit_on_change,
			'async' => true
		));
}

function crm_category_types_list($name, $selected_id=null, $filter=array(), $submit_on_change=true)
{

	$sql = "SELECT id, name, type, inactive FROM ".TB_PREF."crm_categories";

	$multi = false;
	$groups = false;
	$where = array();
	if (@$filter['class']) {
		$where[] = 'type='.db_escape($filter['class']);
	} else
		$groups = 'type';
	if (@$filter['subclass']) $where[] = 'action='.db_escape($filter['subclass']);
	if (@$filter['entity']) $where[] = 'entity_id='.db_escape($filter['entity']);
	if (@$filter['multi']) { // contact category selector for person
		$multi = true;
	}

	return combo_input($name, $selected_id, $sql, 'id', 'name',
	 	array(
	 		'multi' => $multi,
	 		'height' => $multi ? 5:1,
			'category' => $groups,
	 		'select_submit'=> $submit_on_change,
 			'async' => true,
 			'where' => $where
	 	));
}

function crm_category_types_list_row($label, $name, $selected_id=null, $filter=array(), $submit_on_change=true)
{
	echo "<tr><td class='label'>$label</td><td>";
	echo crm_category_types_list($name, $selected_id, $filter, $submit_on_change);
	echo "</td></tr>\n";
}

function payment_type_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $pterm_types;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $pterm_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

function coa_list_row($label, $name, $value=null)
{
	global $path_to_root, $installed_extensions;

	$path = $path_to_root.'/sql/';
	$coas = array();
	$sqldir = opendir($path);
	while (false !== ($fname = readdir($sqldir)))
	{
		if (is_file($path.$fname) && substr($fname,-4)=='.sql' && @($fname[2] == '_'))
		{
			$ext = array_search_value($fname, $installed_extensions, 'sql');
			if ($ext!=null) {
				$descr = $ext['name'];
			} elseif ($fname == 'en_US-new.sql') { // two standard COAs
				$descr = _("Standard new company American COA (4 digit)");
			} elseif ($fname == 'en_US-demo.sql') {
				$descr = _("Standard American COA (4 digit) with demo data");
			} else
				$descr = $fname;

			$coas[$fname] =  $descr;
		}
	}
	ksort($coas);

	echo "<tr><td class='label'>$label</td>\n<td>";
	echo array_selector( $name, $value, $coas );
	echo "</td></tr>\n";
}

function payment_services($name)
{
	global $payment_services;

	$services = (object)array_combine(array_keys($payment_services), array_keys($payment_services));

	return array_selector($name, null, $services, array(
			'spec_option'=> _("No payment Link"),
			'spec_id' => '',
		));
}

function tax_algorithm_list($name, $value=null, $submit_on_change = false)
{
	global $tax_algorithms;
	
	return array_selector($name, $value, $tax_algorithms, 
		array( 
			'select_submit'=> $submit_on_change,
			'async' => true,
			)
	);
}

function tax_algorithm_list_cells($label, $name, $value=null, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo tax_algorithm_list($name, $value, $submit_on_change);
	echo "</td>\n";
}

function tax_algorithm_list_row($label, $name, $value=null, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	tax_algorithm_list_cells(null, $name, $value, $submit_on_change);
	echo "</tr>\n";
}

function refline_list($name, $type, $value=null, $spec_option=false)
{
	$sql = "SELECT id, prefix, inactive FROM ".TB_PREF."reflines";

	$where = array();

	if (isset($type))
		$where = array('`trans_type`='.db_escape($type));
		
		

	return combo_input($name, $value, $sql, 'id', 'prefix',
		array(
			'order'=>array('prefix'),
			'spec_option' => $spec_option,
			'spec_id' => '',
			'type' => 2,
			'where' => $where,
			'select_submit' => true,
			)
		);
}

function refline_list_row($label, $name, $type, $selected_id=null, $spec_option=false)
{
	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo refline_list($name, $type, $selected_id, $spec_option);
	echo "</td></tr>\n";
}


//----------------------------------------------------------------------------------------------

function subledger_list($name, $account, $selected_id=null)
{

	$type = is_subledger_account($account);
	if (!$type)
		return '';

	if($type > 0)
		$sql = "SELECT DISTINCT d.debtor_no as id, debtor_ref as name 
		FROM "
		.TB_PREF."debtors_master d,"
		.TB_PREF."cust_branch c
		WHERE d.debtor_no=c.debtor_no AND NOT d.inactive AND c.receivables_account=".db_escape($account);
	else
		$sql = "SELECT supplier_id as id, supp_ref as name 
		FROM "
		.TB_PREF."suppliers s
		WHERE NOT s.inactive AND s.payable_account=".db_escape($account);

	$mode = get_company_pref('no_customer_list');

	return combo_input($name, $selected_id, $sql, 'id', 'name',
	array(
		'type' => 1,
		'size' => 20,
		'async' => false,
	) );
}

function subledger_list_cells($label, $name, $account, $selected_id=null)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td nowrap>";
	echo subledger_list($name, $account, $selected_id);
	echo "</td>\n";
}

function subledger_list_row($label, $name, $selected_id=null, $all_option = false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false)
{
	echo "<tr><td class='label'>$label</td><td nowrap>";
	echo subledger_list($name, $account, $selected_id);
	echo "</td>\n</tr>\n";
}

function accounts_type_list_row($label, $name, $selected_id=null)
{
	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";
	$sel = array(_("Numeric"), _("Alpha Numeric"), _("ALPHA NUMERIC"));
	echo array_selector($name, $selected_id, $sel); 
	echo "</td></tr>\n";
}

function users_list_cells($label, $name, $selected_id=null, $submit_on_change=false, $spec_opt=true)
{
    $where = false;
	$sql = " SELECT user_id, IF(real_name = '', user_id, real_name) FROM ".TB_PREF."users";

    if ($label != null)
        echo "<td>$label</td>\n";
    echo "<td>";

    echo combo_input($name, $selected_id, $sql, 'user_id', 'real_name',
        array(
            'spec_option' => $spec_opt===true ?_("All users") : $spec_opt,
            'spec_id' => '',
            'order' => 'real_name',
            'select_submit'=> $submit_on_change,
            'async' => false
        ) );
    echo "</td>";

}

function collations_list_row($label, $name, $selected_id=null)
{
	global $supported_collations;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $supported_collations, 
		array('select_submit'=> false) );
	echo "</td></tr>\n";
}


//-----------------------------------------------------------------

function material_indent_request_types_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $material_indent_request_type;
    	
	 if ($label != null)
		echo "<td>$label</td>\n";
     
	echo "<td>";
	echo array_selector($name, $selected_id, $material_indent_request_type, 
		array( 
			'select_submit'=> $submit_on_change, 
			'enabled' => !$enabled) );
	
	echo"</td>";
	
}

function material_indent_request_types_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $material_indent_request_type;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $material_indent_request_type, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

function stock_transfer_type_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $stock_transfer_type;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $stock_transfer_type, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

function mode_of_payment_list($name, $selected_id=null, $submit_on_change=false)
{
	global $mode_payment_types;
	
	return array_selector($name, $selected_id, $mode_payment_types, 
		array( 'select_submit'=> $submit_on_change ) );
}

function mode_of_payment_list_row($label, $name, $selected_id=null,  $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td><td>\n";
	echo mode_of_payment_list($name, $selected_id,$submit_on_change);
	echo "</td></tr>\n";
}


function mode_of_payment_list_cells($label, $name, $selected_id=null,  $submit_on_change=false)
{
	 if ($label != null)
		echo "<td>$label</td>\n";
     
	echo "<td>";
	echo mode_of_payment_list($name, $selected_id,$submit_on_change);
	echo"</td>";
}

//----------------------------------------------------------------------------------------

//Invoice payment types cash/credit
function sales_invoice_payment_type_list($name, $selected_id=null, $submit_on_change=false)
{
	global $invoice_payment_type;
	
	return array_selector($name, $selected_id, $invoice_payment_type, 
		array( 'select_submit'=> $submit_on_change ) );
}

function sales_invoice_payment_type_list_cells($label, $name, $selected_id=null,  $submit_on_change=false)
{
	 if ($label != null)
		echo "<td>$label</td>\n";
     
	echo "<td>";
	echo sales_invoice_payment_type_list($name, $selected_id,$submit_on_change);
	echo"</td>";
}

function sales_invoice_payment_type_list_row($label, $name, $selected_id=null,  $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td><td>\n";
	echo sales_invoice_payment_type_list($name, $selected_id,$submit_on_change);
	echo "</td></tr>\n";
}

function vat_registered_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $vat_registered;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $vat_registered, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}


function business_types_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $business_types;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $business_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

function vat_status_list($name, $selected_id=null)
{
	global $vat_status;
	
	return array_selector($name, $selected_id, $vat_status, 
		array( 'select_submit'=> false, 'async' => true ) );
}

function vat_status_list_row($label, $name, $selected_id=null)
{
	echo "<tr><td class='label'>$label</td><td>\n";
	echo vat_status_list($name, $selected_id);
	echo "</td></tr>\n";
}


function reverse_charge_status_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $reverse_charge_status;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $reverse_charge_status, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

//------------------------------------------------------------------------------
function indent_request_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$loc_code)
{
	$sql = "SELECT DISTINCT indent.indent_id, indent.reference FROM ".TB_PREF."material_indent_request indent,".TB_PREF."material_indent_request_details details WHERE indent.indent_id=details.indent_id and indent.indent_from_loc=".db_escape($loc_code)." and details.quantity>details.qty_received OR  indent.closed=1";
	
	return combo_input($name, $selected_id, $sql, 'indent_id', 'reference',
 	array('order'=>'indent_id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function indent_request_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$loc_code)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo indent_request_list($name, $selected_id, $spec_opt, $submit_on_change, $loc_code);
	echo "</td>\n";
}

function indent_request_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$loc_code)
{
 
	echo "<tr><td class='label'>$label</td>";
	indent_request_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$loc_code);
	echo "</tr>\n";
}

function indent_req_items_list($name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $opts=array(), $editkey = false, $type = "stock",$indent_id)
{
	$sql = "SELECT s.stock_id, CONCAT(s.stock_id,'       ', s.description) as item_desc, c.description, s.inactive, s.editable
			FROM ".TB_PREF."material_indent_request_details items ,".TB_PREF."material_indent_request ir, ".TB_PREF."stock_master s,".TB_PREF."stock_category c WHERE s.category_id=c.category_id AND items.indent_id=ir.indent_id AND s.stock_id=items.stock_id AND ir.indent_id=".db_escape($indent_id)."";
	
	if (isset($opts['fixed_asset']) && $opts['fixed_asset'])
		$sql .= " AND mb_flag='F'";
	else
		$sql .= " AND mb_flag!='F'";
	if ($editkey)
		set_editor('item', $name, $editkey);
	
	$ret = combo_input($name, $selected_id, $sql, 'stock_id', 'item_desc',
		array_merge(
		  array(
			'format' => '_format_stock_items',
			'spec_option' => $all_option===true ?  _("Select Item") : $all_option,
			'spec_id' => ALL_TEXT,
			'search_box' => true,
			'search' => array("stock_id", "c.description","s.description"),
			'search_submit' => false && (!isset($opts['fixed_asset']) || !$opts['fixed_asset']),
			'size'=>20,
			'select_submit'=> $submit_on_change,
			'category' => 2,
			'order' => array('c.description','stock_id'),
			'editlink' => $editkey ? add_edit_combo('item') : false,
			'editable' => false,
			'max' => 255
		  ), $opts), $type );
	return $ret;
}

function indent_req_items_list_cells($label, $name, $selected_id=null, 
	$all_option=false, $submit_on_change=false,$indent_id)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo indent_req_items_list($name, $selected_id, $all_option, $submit_on_change,
		array('where'=>array("mb_flag!='D'"), 'cells'=>true), false, "stock_costable",$indent_id);
}


function indent_received_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$from_loc_code,$to_loc_code)
{
	$sql = "SELECT id, reference FROM ".TB_PREF."inventory_received_against_indent WHERE location_to=".db_escape($to_loc_code)." AND location_from=".db_escape($from_loc_code)." AND status=0";
	
	//display_error($sql);
	
	return combo_input($name, $selected_id, $sql, 'id', 'reference',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function indent_received_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$from_loc_code,$to_loc_code)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo indent_received_list($name, $selected_id, $spec_opt, $submit_on_change,$from_loc_code,$to_loc_code);
	echo "</td>\n";
}

function indent_received_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$from_loc_code,$to_loc_code)
{
	echo "<tr><td class='label'>$label</td>";
	indent_received_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$from_loc_code,$to_loc_code);
	echo "</tr>\n";
}

function supplier_invoice_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false, 
	$show_inactive=false, $editkey = false,$supplier_id='')
{

	global $SysPrefs;
	$sql = "SELECT trans_no,reference  FROM ".TB_PREF."supp_trans WHERE supplier_id = ".db_escape($supplier_id)." AND type = '20' AND ov_amount > '0'";
	
	 if ($editkey)
		set_editor('supplier', $name, $editkey);
	$ret = combo_input($name, $selected_id, $sql, 'trans_no', 'reference',
	array( 'order' => array('reference'),
		 'search_box' => $mode!=0,
		 'type' => 2,
		 'size' => 20,
         'search' => array("reference","reference","trans_no"),        
		 'spec_option' => $spec_option === true ? _("All Invoice Nos") : $spec_option,
		 'spec_id' => ALL_TEXT,
		 'select_submit'=> $submit_on_change,
		 'async' => false,
		 'sel_hint' => $mode ? _('Press Space tab to filter by name fragment; F2 - entry new jobcard') :
		 _('Select Jobcard'),
		 'show_inactive' => $show_inactive,
		// 'editlink' => $editkey ? add_edit_combo('supplier') : false
	), "suppliers" ); 
	 return $ret;
}

function supplier_invoice_list_cells($label, $name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false,$supplier_id='')
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo supplier_invoice_list($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey,$supplier_id);
	echo "</td>\n";
}

function supplier_invoice_list_row($label, $name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false,$supplier_id='')
{
	echo "<tr><td class='label'>$label</td><td>";
	echo supplier_invoice_list($name, $selected_id, $all_option, $submit_on_change,
		$show_inactive, $editkey,$supplier_id);
	echo "</td></tr>\n";
}


//HRM //ravi

//---------------------------------------------------------------------------------------------------------------------------------

function empl_status_list($name, $selected_id=null, $all_option=false, $submit_on_change=false,$multi=false)
{

	$sql = "SELECT id, name, inactive FROM ".TB_PREF."kv_employee_status";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'spec_option' => $all_option === true ? _("Select Employee Status") : $all_option,
			'spec_id' => ALL_TEXT,
			'multi' => $multi,
			'select_submit'=> $submit_on_change
			
		) );
}

function empl_status_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo empl_status_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function empl_status_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	empl_status_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}


function designation_list_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{

	 
	
	$sql = "SELECT id, name, inactive FROM ".TB_PREF."kv_designation";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'spec_option' => $all_option === true ? _("Select Designation") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}

function designation_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo designation_list_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function designation_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	designation_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}

function empl_grade_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	$sql = "SELECT id, name, inactive FROM ".TB_PREF."kv_grade";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'spec_option' => $all_option === true ? _("Select Designation") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}

function empl_grade_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo empl_grade_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function empl_grade_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	empl_grade_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}
//------------------------------------------------------------------------------------------------------------------
function empl_cat_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{

	 
	
	$sql = "SELECT id, name, inactive FROM ".TB_PREF."kv_category";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'spec_option' => $all_option === true ? _("Select Designation") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}

function empl_cat_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo empl_cat_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function empl_cat_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	empl_cat_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}
function empl_skill_level_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{

	 
	
	$sql = "SELECT id, name, inactive FROM ".TB_PREF."kv_skill_level";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'spec_option' => $all_option === true ? _("Select Designation") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}


function empl_skill_level_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo empl_skill_level_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function empl_skill_level_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	empl_skill_level_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}



 function empl_sal_type_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $empl_sal_type;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $empl_sal_type, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}
 function empl_approval_type_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $empl_approval_type;
	
	echo "<td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $empl_approval_type, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}


//------------------------------------------------------------------------------------------------------------

function sort_by_list($name, $selected_id=null, $item_wise="", $job_wise="", $submit_on_change=false)
{
	$items = array();
	$items['0'] = strlen($item_wise) ? $item_wise : _("Item Wise");
	$items['1'] = strlen($job_wise) ? $job_wise : _("Job Wise");

	return array_selector($name, $selected_id, $items, 
		array( 
			'select_submit'=> $submit_on_change,
			'async' => false ) ); // FIX?
}

function sort_by_list_cells($label, $name, $selected_id=null, $item_wise="", $job_wise="", $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo sort_by_list($name, $selected_id, $item_wise, $job_wise, $submit_on_change);
	echo "</td>\n";
}

function sort_by_list_row($label, $name, $selected_id=null, $item_wise="", $job_wise="", $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	sort_by_list_cells(null, $name, $selected_id, $item_wise, $job_wise, $submit_on_change);
	echo "</tr>\n";
}

 function empl_social_security_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $empl_social_security;
	
	echo "<td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $empl_social_security, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}


function employee_dependents_relationship_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $empl_dependents_relationship;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $empl_dependents_relationship, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

function country_types_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	global $countries;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $countries, 
		array( 
			'order' => array('Sort_order'),
			'spec_option' => $all_option === true ? _("Select Country") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}
function employee_dependents_gender_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $empl_gender;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $empl_gender, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}



function document_type_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{

	$sql = "SELECT id, name, inactive,Sort_order FROM ".TB_PREF."kv_document_types WHERE type=2";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'order' => array('Sort_order'),
			'spec_option' => $all_option === true ? _("All Types") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}

function document_type_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo document_type_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function document_type_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	document_type_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}
function family_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $family_doc;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $family_doc, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}
function empl_type_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $empl_type;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $empl_type, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}


function leave_applicable_employees_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $leave_applicable_employees;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $leave_applicable_employees, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

//--------------------------------------------------------------
//Service & Rental

/* function tool_types_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{

   global $tool_types;
   
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $tool_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}

 function tool_types_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $tool_types;
	
	echo "<td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $tool_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}
 */
 
// tool type dynamic
function tool_types_list($name, $selected_id=null, $submit_on_change=false, 
$spec_opt=false)
{
	$sql = "SELECT id, tool_type, inactive FROM 0_ser_tool_types where inactive=0";
	
	return combo_input($name, $selected_id, $sql, 'id', 'tool_type',
 	array('order'=>'id',
	    'spec_option' => $spec_opt === true ? _("Select a Equipment Type") : $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function tool_types_list_cells($label, $name, $selected_id=null, $submit_on_change=false,$spec_opt=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo tool_types_list($name, $selected_id, $submit_on_change,$spec_opt);
	echo "</td>\n";
}

function tool_types_list_row($label, $name, $selected_id=null, $submit_on_change=false,$spec_opt=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	tool_types_list_cells(null, $name, $selected_id, $submit_on_change, $spec_opt);
	echo "</tr>\n";
}
//end

function service_component_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false)
{
	$sql = "SELECT id, component_name, inactive FROM 0_ser_components";
	
	return combo_input($name, $selected_id, $sql, 'id', 'component_name',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_component_list_cells($label, $name, $selected_id=null, 
$spec_opt=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_component_list($name, $selected_id, $spec_opt, $submit_on_change);
	echo "</td>\n";
}

function service_component_list_row($label, $name, $selected_id=null, 
$spec_opt=false, $submit_on_change=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_component_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
	echo "</tr>\n";
}


function service_tool_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,
$tool_type_id=false, $tool_code_id=false)
{
	$sql = "SELECT id, tool_name, inactive FROM 0_ser_tools";
	
	if($tool_type_id)
	{
		$sql .=" WHERE  tool_type_id=".db_escape($tool_type_id)."";
	}
	
	if($tool_code_id)
	{
		$sql .=" AND  tool_code_id=".db_escape($tool_code_id)."";
	}
	
	
	
	return combo_input($name, $selected_id, $sql, 'id', 'tool_name',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_tool_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_type_id=false, $tool_code_id=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_tool_list($name, $selected_id, $spec_opt, $submit_on_change,$tool_type_id, $tool_code_id);
	echo "</td>\n";
}

function service_tool_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_type_id=false, $tool_code_id=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_tool_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$tool_type_id, $tool_code_id);
	echo "</tr>\n";
}


function service_freezed_tool_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $tool_type_id=false, $tool_code_id=false)
{
	$sql = "SELECT tools.id, tools.tool_name, tools.inactive FROM 0_ser_tools tools, 0_ser_tool_specific_components_status tsc  WHERE tools.id=tsc.tool_id AND freeze_status=1";
	
	if($tool_type_id)
	{
		$sql .=" AND  tools.tool_type_id=".db_escape($tool_type_id)."";
	}
	
	if($tool_code_id)
	{
		$sql .=" AND  tools.tool_code_id=".db_escape($tool_code_id)."";
	}
	
	return combo_input($name, $selected_id, $sql, 'tools.id', 'tools.tool_name',
 	array('order'=>'tools.id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}


//ravi
function service_tool_sno_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false, $tool_id=false)
{
	$sql = "SELECT tool_serial_no,tool_serial_no FROM 0_ser_tool_current_status  WHERE 1=1";
	
	if($tool_id)
	{
		$sql .=" AND  tool_id=".db_escape($tool_id)."";
	}
	
		
	return combo_input($name, $selected_id, $sql, 'tool_serial_no', 'tool_serial_no',
 	array('order'=>'tool_serial_no',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}



function service_freezed_tool_list_cells($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false, $tool_type_id=false, $tool_code_id=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_freezed_tool_list($name, $selected_id, $spec_opt, $submit_on_change, $tool_type_id,$tool_code_id);
	echo "</td>\n";
}

function service_freezed_tool_list_row($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false, $tool_type_id=false, $tool_code_id=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_freezed_tool_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change, $tool_type_id,$tool_code_id);
	echo "</tr>\n";
}


//---------------------------------------------------------------------------
//sameer
//--------------------------------------------------------------------------

function tool_jar_categories_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{	
	$sql = "SELECT id,tool_category FROM 0_ser_tool_category WHERE inactive=0 ";
		
	return combo_input($name, $selected_id, $sql, 'id', 'tool_category',
 	array('order'=>'tool_category',
		'spec_option' => $spec_opt === true ? _("Select a Equipment Make") : $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function tool_jar_categories_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	if ($label != null)
	echo "<td>$label</td>\n";
	echo "<td>";
	echo tool_jar_categories_list($name, $selected_id, $spec_opt, $submit_on_change);
	echo "</td>\n";
}

function tool_jar_categories_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	tool_jar_categories_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
	echo "</tr>\n";
	
}

 function tool_shock_category_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $tool_shock_category;
	
	echo "<td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $tool_shock_category, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}



function tool_shock_category_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
    global $tool_shock_category;

	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $tool_shock_category, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}


function service_freezed_component_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $tool_id=false)

{
	$sql = "SELECT components.id, components.component_name, components.inactive FROM 0_ser_components components, 0_ser_tool_specific_components tsc  WHERE components.id=tsc.component_id ";
	
	if($tool_id)
	{
		$sql .=" AND  tsc.tool_id=".db_escape($tool_id)."";
	}
	
	
	
	return combo_input($name, $selected_id, $sql, 'components.id', 'components.component_name',
 	array('order'=>'components.id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_freezed_component_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $tool_id=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_freezed_component_list($name, $selected_id, $spec_opt, $submit_on_change, $tool_id);
	echo "</td>\n";
}

function service_freezed_component_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $tool_id=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_freezed_component_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change, $tool_id);
	echo "</tr>\n";
}


function spare_component_types_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $spare_component_types;
	
	echo "<td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $spare_component_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}


function spare_component_types_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
    global $spare_component_types;

	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $spare_component_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}



function tool_codes_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $tool_type_id=0)
{	
	$sql = "SELECT id,tool_code FROM 0_ser_tool_codes WHERE inactive=0 ";
		if(($tool_type_id!='-1') && $tool_type_id !='0')
		{
			$sql .=" AND tool_type_id =".db_escape($tool_type_id)."";
		}
	
	return combo_input($name, $selected_id, $sql, 'id', 'tool_code',
 	array('order'=>'tool_code',
		'spec_option' => $spec_opt === true ? _("Select a Equipment Model") : $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function tool_codes_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $tool_type_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo tool_codes_list($name, $selected_id, $spec_opt, $submit_on_change, $tool_type_id);
	echo "</td>\n";
}

function tool_codes_list_row($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false ,$tool_type_id=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	tool_codes_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$tool_type_id);
	echo "</tr>\n";
}


function tool_comment_types_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{

   global $tool_comment_types;
   
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $tool_comment_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}

 function tool_comment_types_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $tool_comment_types;
	
	echo "<td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $tool_comment_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}


function service_tool_serial_no_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$tool_id=false,$status=0, $customer_id=0, $mounted_primary=0)

{
	//ravi
	
	$status=str_replace("'","",$status);
	
	$sql = "SELECT tool_serial_no, tool_serial_no FROM 0_ser_tool_current_status where 1=1 ";
	
	if($tool_id)
	{
		$sql .=" AND tool_id=".db_escape($tool_id)."";
	}
	if($status!=0){
	$sql .=" AND current_status in ($status)";  //ravi
	}
	if($customer_id!=0){
	$sql .=" AND customer_id=".db_escape($customer_id)."";
	}
	
	/* if($mounted_primary!=0){
	$sql .=" AND fleet_type_id !='2'";
	} */
	
	//	display_error($sql);
				
	return combo_input($name, $selected_id, $sql, 'tool_serial_no', 'tool_serial_no',
 	array('order'=>'tool_serial_no',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_tool_serial_no_list_cells($label, $name, $selected_id=null, $spec_opt=false,
 $submit_on_change=false,$tool_id=false,$status=0, $customer_id=0, $mounted_primary=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_tool_serial_no_list($name, $selected_id, $spec_opt, $submit_on_change,$tool_id,$status,$customer_id,$mounted_primary);
	echo "</td>\n";
}

function service_tool_serial_no_list_row($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$tool_id=false,$status=0, $customer_id=0, $mounted_primary=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_tool_serial_no_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$tool_id,$status,$customer_id,$mounted_primary);
	echo "</tr>\n";
}


 function tool_current_status_list_row($label, $name, $selected_id=null, $submit_on_change=false,
 $spec_option=false)
{
	global $tool_current_status;
	
	echo "<td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $tool_current_status, 
		array( 
			'select_submit'=> $submit_on_change,
			'spec_option' => $spec_option === true ? _("All Current Status") : $spec_option,
			'spec_id' => ALL_TEXT,
		) );
	echo "</td>\n";
}


//ravi
 function adjustment_tool_current_status_list_row($label, $name, $selected_id=null, $submit_on_change=false, $spec_option=false)
{
	global $adj_tool_current_status;
	
	echo "<td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $adj_tool_current_status, 
		array( 
			'select_submit'=> $submit_on_change,
			'spec_option' => $spec_option === true ? _("All Current Status") : $spec_option,
			'spec_id' => ALL_TEXT,
		) );
	echo "</td>\n";
}
 
function tool_current_status_multi_list_row($label, $name, $selected_id=null, 
 $submit_on_change=false, $spec_option=false)
{
	global $tool_current_status;
	
	echo "<td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $tool_current_status, 
		array( 
			'select_submit'=> $submit_on_change,
			'spec_option' => $spec_option === true ? _("All Current Status") : $spec_option,
			'spec_id' => ALL_TEXT,
			'multi' =>true
		) );
	echo "</td>\n";
}

function tool_current_status_list($name, $selected_id=null, $submit_on_change=false, $spec_option=false)
{
	global $tool_current_status;
	
	return array_selector($name, null, $tool_current_status, 
	array( 
			'select_submit'=> $submit_on_change,
			'spec_option' => $spec_option === true ? _("All Current Status") : $spec_option,
			'spec_id' => ALL_TEXT,
		) );
}


function jc_current_status_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $jc_current_status;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $jc_current_status, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}


function service_sl2_jobcard_list($name, $selected_id=null, $spec_option=false, 
$submit_on_change=false, $show_inactive=false, $editkey = false,$jc_status='')
	
{
	global $SysPrefs;
	$sql = "SELECT reference,CONCAT(reference,'  -  ',tool_serial_no,'      (', t.tool_name ,')') as wo_desc FROM ".TB_PREF."ser_sl2_jobcard_open  jc, 0_ser_tools t WHERE jc.tool_id=t.id  ";
	if($jc_status!='')
	{
		$sql .=" AND status=".db_escape($jc_status)." ";
	}
	
	/*
	return combo_input($name, $selected_id, $sql, 'reference', 'wo_desc',
 	array('order'=>'jc.id',
		'spec_option' => $spec_option,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
	*/
	
	
	if ($editkey)
		set_editor('servicejobcard', $name, $editkey);
	$ret = combo_input($name, $selected_id, $sql, 'reference', 'wo_desc',
	//array( 'order' => array('jc.id'),
	array( 'order' => array('jc.id'),
		 'search_box' => $mode!=0,
		 'type' => 1,
		 'size' => 20,
         'search' => array("reference","reference","reg_no"),        
		 'spec_option' => $spec_option === true ? _("All Job Cards") : $spec_option,
		 'spec_id' => -1,
		 'select_submit'=> $submit_on_change,
		 'async' => true,
		 'sel_hint' => $mode ? _('Press Space tab to filter by name fragment; F2 - entry new jobcard') :
		 _('Select Work Order'),
		 'show_inactive' => $show_inactive,
		 'editlink' => $editkey ? add_edit_combo('servicejobcard') : false
	), "servicejobcard" ); 
	 return $ret;
	
}


function service_sl2_jobcard_list_cells($label, $name, $selected_id=null, $all_option=false,	$submit_on_change=false, $show_inactive=false, $editkey = false,$jc_status='')
{
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td nowrap>";
	echo service_sl2_jobcard_list($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey,$jc_status);
	echo "</td>\n";
}


function service_sl2_jobcard_list_row($label, $name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false,$jc_status='')
{

	echo "<tr><td class='label'>$label</td><td nowrap>";
	echo service_sl2_jobcard_list($name, $selected_id, $all_option, $submit_on_change,
		$show_inactive, $editkey,$jc_status);
	echo "</td>\n</tr>\n";
}


function service_major_complaints_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	$sql = "SELECT id, description,inactive FROM ".TB_PREF."ser_major_complaints";
	return combo_input($name, $selected_id, $sql, 'id', 'description',
		array(
			'spec_option' => $all_option === true ? _("Select Major Complaint") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}
function service_major_complaints_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
	echo "<td>$label</td>\n";
	echo "<td>";
	echo service_major_complaints_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}
function service_major_complaints_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	service_major_complaints_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}


function jobcard_status_row($label, $name, $selected_id=null, $enabled=true)
{
	global $jobcard_status;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $jobcard_status, 
		array( 
			'select_submit'=> false, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}

//ravi
function jobcard_status_cells($label,$name, $selected_id=null, $enabled=true,$spec_option=false)
{
	global $jobcard_status;	
	echo "<td>$label</td>";
	echo "<td>";
	echo array_selector($name, $selected_id, $jobcard_status, 
		array( 
			'select_submit'=> false,
		    'spec_option' => $spec_option === true ? _("All") : $spec_option,			
			'disabled' => !$enabled));
	echo "</td>";
}

// service levels types

function service_level_types_row($label, $name, $selected_id=null, $enabled=true)
{
	global $service_levels_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $service_levels_types, 
		array( 
			'select_submit'=> false, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}

//ravi
function service_locations_row($label, $name, $selected_id=null, $enabled=true)
{
	global $service_locations;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $service_locations, 
		array( 
			'select_submit'=> false, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}

function component_current_status_list_row($label, $name, $selected_id=null, $submit_on_change=false, $spec_option=false)
{
	global $component_current_status;
	
	echo "<td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $component_current_status, 
		array( 
			'select_submit'=> $submit_on_change,
			'spec_option' => $spec_option === true ? _("All Current Status") : $spec_option,
			'spec_id' => ALL_TEXT,
		) );
	echo "</td>\n";
}



function service_mi_issue_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $jobcard_no='')
{
	$sql = "SELECT id, reference FROM ".TB_PREF."ser_jobcard_mi WHERE ".TB_PREF."ser_jobcard_mi.jobcard_no = ".db_escape($jobcard_no)."";
	
 	return combo_input($name, $selected_id, $sql, 'id', 'reference',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_mi_issue_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $jobcard_no='')
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_mi_issue_list($name, $selected_id, $spec_opt, $submit_on_change, $jobcard_no);
	echo "</td>\n";
}

function service_mi_issue_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $jobcard_no='')
{
	echo "<tr><td class='label'>$label</td>";
	service_mi_issue_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change, $jobcard_no);
	echo "</tr>\n";
}

function mi_issued_items_list($name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $opts=array(), $editkey = false, $type = "stock",$jobcard_no=0,$mi_id=0)
{
	$sql = "SELECT s.stock_id, s.description, c.description, s.inactive, s.editable
			FROM ".TB_PREF."ser_jobcard_mi_items items ,".TB_PREF."ser_jobcard_mi mi, ".TB_PREF."stock_master s,".TB_PREF."stock_category c WHERE s.category_id=c.category_id AND items.mi_id=mi.id AND s.stock_id=items.stock_id AND mi.jobcard_no=".db_escape($jobcard_no)." AND mi.id=".db_escape($mi_id)."";

	if (isset($opts['fixed_asset']) && $opts['fixed_asset'])
		$sql .= " AND mb_flag='F'";
	else
		$sql .= " AND mb_flag!='F'";
			

	if ($editkey)
		set_editor('item', $name, $editkey);

	$ret = combo_input($name, $selected_id, $sql, 'stock_id', 's.description',
		array_merge(
		  array(
			'format' => '_format_stock_items',
			'spec_option' => $all_option===true ?  _("All Items") : $all_option,
			'spec_id' => ALL_TEXT,
			'search_box' => true,
			'search' => array("stock_id", "c.description","s.description"),
			'search_submit' => get_company_pref('no_item_list')!=0 && (!isset($opts['fixed_asset']) || !$opts['fixed_asset']),
			'size'=>10,
			'select_submit'=> $submit_on_change,
			'category' => 2,
			'order' => array('c.description','stock_id'),
			'editlink' => $editkey ? add_edit_combo('item') : false,
			'editable' => false,
			'max' => 255
		  ), $opts), $type );
	return $ret;
}

function mi_issued_items_list_cells($label, $name, $selected_id=null, 
	$all_option=false, $submit_on_change=false,$jobcard_no=0,$mi_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo mi_issued_items_list($name, $selected_id, $all_option, $submit_on_change,
		array(true, 'cells'=>true), false, "stock",$jobcard_no,$mi_id);
}

//ravi
function am_mi_issued_items_list($name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $opts=array(), $editkey = false, $type = "stock",$jobcard_no=0,$mi_id=0)
{
	$sql = "SELECT s.stock_id, s.description, c.description, s.inactive, s.editable
			FROM ".TB_PREF."am_jobcard_mi_items items ,".TB_PREF."am_jobcard_mi mi, ".TB_PREF."stock_master s,".TB_PREF."stock_category c WHERE s.category_id=c.category_id AND items.mi_id=mi.id AND s.stock_id=items.stock_id AND mi.jobcard_no=".db_escape($jobcard_no)." AND mi.id=".db_escape($mi_id)."";

	if (isset($opts['fixed_asset']) && $opts['fixed_asset'])
		$sql .= " AND mb_flag='F'";
	else
		$sql .= " AND mb_flag!='F'";
			

	if ($editkey)
		set_editor('item', $name, $editkey);

	$ret = combo_input($name, $selected_id, $sql, 'stock_id', 's.description',
		array_merge(
		  array(
			'format' => '_format_stock_items',
			'spec_option' => $all_option===true ?  _("All Items") : $all_option,
			'spec_id' => ALL_TEXT,
			'search_box' => true,
			'search' => array("stock_id", "c.description","s.description"),
			'search_submit' => get_company_pref('no_item_list')!=0 && (!isset($opts['fixed_asset']) || !$opts['fixed_asset']),
			'size'=>10,
			'select_submit'=> $submit_on_change,
			'category' => 2,
			'order' => array('c.description','stock_id'),
			'editlink' => $editkey ? add_edit_combo('item') : false,
			'editable' => false,
			'max' => 255
		  ), $opts), $type );
	return $ret;
}

function am_mi_issued_items_list_cells($label, $name, $selected_id=null, 
	$all_option=false, $submit_on_change=false,$jobcard_no=0,$mi_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo am_mi_issued_items_list($name, $selected_id, $all_option, $submit_on_change,
		array(true, 'cells'=>true), false, "stock",$jobcard_no,$mi_id);
}


function hr_departments_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	$sql = "SELECT id, description,inactive FROM ".TB_PREF."kv_departments";
	return combo_input($name, $selected_id, $sql, 'id', 'description',
		array(
			'spec_option' => $all_option === true ? _("Select HR Department") : $all_option,
			'spec_id' => -1,
			'select_submit'=> $submit_on_change
		) );
}
function hr_departments_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
	echo "<td>$label</td>\n";
	echo "<td>";
	echo hr_departments_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}
function hr_departments_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	hr_departments_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}


function observation_type_row($label, $name, $selected_id=null, $enabled=true)
{
	global $observation_type;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $observation_type, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}

//ravi
function breakdown_status_row($label, $name, $selected_id=null, $enabled=true)
{
	global $breakdown_status;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $breakdown_status, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}

function component_service_status_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
    global $component_status;

	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $component_status, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}

function available_component_serial_no_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $component_id=false ,$tool_id=0)
{
	$sql = "SELECT component_serial_no, component_serial_no FROM 0_ser_component_current_status WHERE component_id=".db_escape($component_id)." AND current_status='2'";
	if($tool_id)
	{
		$sql .=" AND suitable_tool_id =".db_escape($tool_id)."";
	}
	return combo_input($name, $selected_id, $sql, 'component_serial_no', 'component_serial_no',
		array(
			'spec_option' => $all_option === true ? _("Select Component Serial Number") : $all_option,
			'spec_id' => -1,
			'select_submit'=> $submit_on_change
		) );
}
function available_component_serial_no_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $component_id=false, $tool_id=0)
{
	if ($label != null)
	echo "<td>$label</td>\n";
	echo "<td>";
	echo available_component_serial_no_list($name, $selected_id, $all_option, $submit_on_change, $component_id, $tool_id);
	echo "</td>\n";
}
function available_component_serial_no_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $component_id=false , $tool_id=0)
{
	echo "<tr><td class='label'>$label</td>";
	available_component_serial_no_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $component_id, $tool_id);
	echo "</tr>\n";
}

//-------------------------------------------------------------
//service items
function service_items_list_cells($label, $name, $selected_id=null, $all_option=false, 
$submit_on_change=false, $editkey=false)
{
	if ($editkey)
		set_editor('item', $name, $editkey);

	if ($label != null)
		echo "<td>$label</td>\n";
	echo service_items_list($name, $selected_id, $all_option, $submit_on_change,
		'', array('cells'=>true, 'max'=>50)); // maximum is set to 50 and not default 255.
}


function service_items_list($name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $type='', $opts=array())
{
	// all sales codes
	$sql = "SELECT i.item_code, i.description, c.description, count(*)>1 as kit,
			 i.inactive, if(count(*)>1, '0', s.editable) as editable
			FROM
			".TB_PREF."stock_master s,
			".TB_PREF."item_codes i
			LEFT JOIN
			".TB_PREF."stock_category c
			ON i.category_id=c.category_id
			WHERE i.stock_id=s.stock_id
      AND mb_flag = 'D'";

	
	if ($type == 'local')	{ // exclude foreign codes
		$sql .=	" AND !i.is_foreign"; 
	} elseif ($type == 'kits') { // sales kits
		$sql .=	" AND !i.is_foreign AND i.item_code!=i.stock_id";
	}
	$sql .= " AND !i.inactive AND !s.inactive AND !s.no_sale";
	$sql .= " GROUP BY i.item_code";

	return combo_input($name, $selected_id, $sql, 'i.item_code', 'c.description',
		array_merge(
		  array(
			'format' => '_format_stock_items',
			'spec_option' => $all_option===true ?  _("All Items") : $all_option,
			'spec_id' => ALL_TEXT,
			'search_box' => true,
			'search' => array("i.item_code", "c.description", "i.description"),
			'search_submit' => get_company_pref('no_item_list')!=0,
			'size'=>15,
			'select_submit'=> $submit_on_change,
			'category' => 2,
			'order' => array('c.description','i.item_code'),
			'editable' => 30,
			'max' => 255
		  ), $opts), $type == 'kits' ? $type : "stock_sales" );
}

function all_component_serial_no_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $component_id=false)
{
	$sql = "SELECT component_serial_no, component_serial_no FROM 0_ser_component_current_status WHERE component_id=".db_escape($component_id)."";
	return combo_input($name, $selected_id, $sql, 'component_serial_no', 'component_serial_no',
		array(
			'spec_option' => $all_option === true ? _("Select Component Serial Number") : $all_option,
			'spec_id' => -1,
			'select_submit'=> $submit_on_change
		) );
}
function all_component_serial_no_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $component_id=false)
{
	if ($label != null)
	echo "<td>$label</td>\n";
	echo "<td>";
	echo all_component_serial_no_list($name, $selected_id, $all_option, $submit_on_change, $component_id);
	echo "</td>\n";
}
function all_component_serial_no_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $component_id=false)
{
	echo "<tr><td class='label'>$label</td>";
	all_component_serial_no_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $component_id);
	echo "</tr>\n";
}


function service_document_types_row($label, $name, $selected_id=null, $enabled=true)
{
	global $service_document_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $service_document_types, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}



function service_tool_code_tool_serial_no_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_type_id=false,$tool_capacity_id=false,$tool_category_id=false,$status=0)

{
	$sql = "SELECT tcs.tool_serial_no, tcs.tool_serial_no FROM 0_ser_tool_current_status tcs LEFT JOIN 0_ser_tool_capacity tcap ON tcap.id=tcs.tool_capacity_id LEFT JOIN 0_ser_tool_category tcat ON tcat.id=tcs.tool_category";
	
	$sql .=" WHERE tcs.tool_capacity_id = ".db_escape($tool_capacity_id)." and tcs.tool_category=".db_escape($tool_category_id)."";
	
	if($status!=0){
	$sql .=" AND tcs.current_status=".db_escape($status)."";
	}

	return combo_input($name, $selected_id, $sql, 'tool_serial_no', 'tool_serial_no',
 	array('order'=>'tool_serial_no',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_tool_code_tool_serial_no_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_type_id=false,$tool_capacity_id=false,$tool_category_id=false,$status=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_tool_code_tool_serial_no_list($name, $selected_id, $spec_opt, $submit_on_change,$tool_type_id,$tool_capacity_id,$tool_category_id,$status);
	echo "</td>\n";
}

function service_tool_code_tool_serial_no_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_type_id=false,$tool_capacity_id=false,$tool_category_id=false,$status=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_tool_code_tool_serial_no_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$tool_type_id,$tool_capacity_id,$tool_category_id,$status);
	echo "</tr>\n";
}

//---------------------------------------------------------------------------

function service_tool_enquiry_types_list_row($label, $name, $selected_id=null, 
$submit_on_change=false)
{
	global $service_tool_enquiry_types;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $service_tool_enquiry_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}


function service_tool_order_types_list_row($label, $name, $selected_id=null, 
$submit_on_change=false)
{
	global $service_tool_order_types;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $service_tool_order_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}



function service_forecast_activity_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$customer_id)
{
	$sql = "SELECT id, reference FROM ".TB_PREF."ser_forecast_entry 
	WHERE debtor_no=".db_escape($customer_id)." GROUP BY id";
	
	//display_error($sql);
	return combo_input($name, $selected_id, $sql, 'id', 'reference',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_forecast_activity_list_cells($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$customer_id)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_forecast_activity_list($name, $selected_id, $spec_opt, $submit_on_change, $customer_id);
	echo "</td>\n";
}

function service_forecast_activity_list_row($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$customer_id=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_forecast_activity_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$customer_id);
	echo "</tr>\n";
}


function service_forecast_tool_codes_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $forecast_id=0, $groupby=0)
{	
	$sql = "SELECT sfd.tool_code_id,tc.tool_code FROM ".TB_PREF."ser_forecast_details as sfd,
	0_ser_tool_codes as tc 
	WHERE sfd.tool_code_id = tc.id 
	AND sfd.forecast_id=".db_escape($forecast_id)."";
	if($groupby)
	{
		$sql .=" GROUP BY sfd.tool_code_id";
	}
	return combo_input($name, $selected_id, $sql, 'sfd.tool_code_id', 'tc.tool_code',
 	array('order'=>'sfd.id',
		'spec_option' => $spec_opt === true ? _("Select a Equipment Model") : $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_forecast_tool_codes_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $forecast_id=0,$groupby=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_forecast_tool_codes_list($name, $selected_id, $spec_opt, $submit_on_change, $forecast_id, $groupby);
	echo "</td>\n";
}

function service_forecast_tool_codes_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false ,$forecast_id=0, $groupby=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_forecast_tool_codes_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$forecast_id, $groupby);
	echo "</tr>\n";
}




function rental_tool_items_serial_no_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$stock_id=false)
{
 
	$sql = "SELECT tcs.tool_serial_no, tcs.tool_serial_no FROM 0_ser_tool_current_status tcs,0_ser_tools st,".TB_PREF."stock_master sm 
	WHERE tcs.tool_id=st.id 
	and st.tool_code_id=sm.tool_code_id 
	and st.id=sm.tool_id";
	if($stock_id)
	$sql.=" and sm.stock_id = ".db_escape($stock_id)."";
	
	//display_error($sql);
		
	return combo_input($name, $selected_id, $sql, 'tool_serial_no', 'tool_serial_no',
 	array('order'=>'tool_serial_no',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function rental_tool_items_serial_no_list_cells($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$stock_id=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo rental_tool_items_serial_no_list($name, $selected_id, $spec_opt, $submit_on_change,$stock_id);
	echo "</td>\n";
}


function service_invoice_types_list_cells($label, $name, $selected=null, $submit_on_change=false)
{

	if ($label != null)
		label_cell($label);
	echo "<td>\n";
	$service_inv_types = array( 
		ALL_TEXT=>_("All Types"),
		'1'=> _("Day Based"),
		'2'=> _("Foot Based"),
		'3'=> _("Damage Beyond Repair"),
		'4'=> _("Hourly Based")
	);
	echo array_selector($name, $selected, $service_inv_types, array('select_submit'=> $submit_on_change));
	echo "</td>\n";
}

function rental_tool_item_types_list_row($label, $name, $selected_id=null, $enabled=true)
{
	global $rental_tool_item_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $rental_tool_item_types, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled) );
	echo "</td></tr>\n";
}

function rental_tool_item_types_list_cells($label, $name, $selected_id=null, $enabled=true)
{
	global $rental_tool_item_types;

	 if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $rental_tool_item_types, 
		array( 
			'select_submit'=> $submit_on_change, 
			'enabled' => !$enabled) );
	echo"</td>";
}



function rental_forecast_types_list_row($label, $name, $selected_id=null, $enabled=true)
{
	global $rental_forecast_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $rental_forecast_types, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled) );
	echo "</td></tr>\n";
}

function rental_forecast_types_list_cells($label, $name, $selected_id=null, $enabled=true)
{
	global $rental_forecast_types;

	 if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $rental_forecast_types, 
		array( 
			'select_submit'=> $submit_on_change, 
			'enabled' => !$enabled) );
	echo"</td>";
}


function service_invoice_serial_no_print_options_cells($label, $name, $selected_id=null, $submit_on_change=false)
{

	global $service_invoice_serial_no_type;
	 if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $service_invoice_serial_no_type, 
		array( 
			'select_submit'=> $submit_on_change, 
			'enabled' => !$enabled) );
	echo"</td>";
}

function empl_entry_type_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $empl_entry_type;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $empl_entry_type, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

function empl_other_leave_type_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $empl_other_leave_type;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $empl_other_leave_type, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

//---------------------------------------------------------------------------------

function employee_overtime_percentage_types_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $employee_overtime_percentage_types;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $employee_overtime_percentage_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

//-----------------------------------------------------------------------------------




function service_delivery_qc_types_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $service_delivery_qc_types;
	echo "<td>";
	echo array_selector($name, $selected_id, $service_delivery_qc_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";

}


function pre_service_invoice_list_row($label, $name, $selected_id=null, $enabled=true)
{
	global $pre_service_invoice;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $pre_service_invoice, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}


function vat_exempt_list_row($label, $name, $selected_id=null, $enabled=true)
{
	global $vat_exempt;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $vat_exempt, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}


function pre_service_day_invoice_list($name, $selected_id=null, $all_option=false, $submit_on_change=false,$customer_id=0)
{

	$sql = "SELECT trans_no, reference FROM ".TB_PREF."pre_debtor_trans 
	WHERE trans_no NOT IN (SELECT pre_inv_no FROM ".TB_PREF."debtor_trans) AND debtor_no=".db_escape($customer_id)."";
	
	return combo_input($name, $selected_id, $sql, 'trans_no', 'reference',
		array(
			'spec_option' => $all_option === true ? _("Select") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change,
			'async' => true
		) );
}

function pre_service_day_invoice_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false,$customer_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo pre_service_day_invoice_list($name, $selected_id, $all_option, $submit_on_change,$customer_id);
	echo "</td>\n";
}

function pre_service_day_invoice_list_row($label, $name, $selected_id=null, $all_option=false, 
$submit_on_change=false,$customer_id=0)
{
	echo "<tr><td class='label'>$label</td>";
	pre_service_day_invoice_list_cells(null, $name, $selected_id, $all_option, $submit_on_change,$customer_id);
	echo "</tr>\n";
}

//----------------------------------------------------------
//Tool Name by tool code
function service_tool_by_tool_code_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$tool_code_id=false,$tool_type_id=false)
{
	$sql = "SELECT id, tool_name, inactive FROM 0_ser_tools WHERE 1=1";
	
	if($tool_code_id)
	{
		$sql .=" AND  tool_code_id=".db_escape($tool_code_id)."";
	}
	if($tool_type_id)
	{
		$sql .=" AND  tool_type_id=".db_escape($tool_type_id)."";
	}
	
	//display_error($sql);
	
	return combo_input($name, $selected_id, $sql, 'id', 'tool_name',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_tool_by_tool_code_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_code_id=false,$tool_type_id=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_tool_by_tool_code_list($name, $selected_id, $spec_opt, $submit_on_change,$tool_code_id,$tool_type_id);
	echo "</td>\n";
}

function service_tool_by_tool_code_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_code_id=false,$tool_type_id=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_tool_by_tool_code_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$tool_code_id,$tool_type_id);
	echo "</tr>\n";
}

	
//-----------------------------------------------------------------------------------------------

/*
 function tool_jar_categories_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $tool_jar_categories;
	
	echo "<td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $tool_jar_categories, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}


function tool_jar_categories_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
    global $tool_jar_categories;

	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $tool_jar_categories, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}

*/
//-----------------------------------------------------------------------

function customer_contract_no_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$customer_id=false)
{
	$sql = "SELECT id, contract_no, inactive FROM ".TB_PREF."customer_contracts";
	
	if($customer_id)
	{
		$sql .=" WHERE customer_id=".db_escape($customer_id)."";
	}
   // display_error($sql);
	
	return combo_input($name, $selected_id, $sql, 'id', 'contract_no',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function customer_contract_no_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$customer_id=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo customer_contract_no_list($name, $selected_id, $spec_opt, $submit_on_change,$customer_id);
	echo "</td>\n";
}

function customer_contract_no_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$customer_id=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	customer_contract_no_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$customer_id);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------------------------
//sameer
function project_site_list($name, $selected_id=null, $all_option=false, $submit_on_change=false )
{	
	$sql = "SELECT id, project_name FROM ".TB_PREF."ser_project_master WHERE inactive=0 ";
	return combo_input($name, $selected_id, $sql, 'id', 'project_name',
 	array('order'=>'project_name',
		'spec_option' => $all_option === true ? _("Select a Project Name") : $all_option,
		$all_option === true ? _("All Locations") : $all_option,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function project_site_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false )
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo project_site_list($name, $selected_id, $spec_opt, $submit_on_change);
	echo "</td>\n";
}

function project_site_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false )
{
 
	echo "<tr><td class='label'>$label</td>";
	project_site_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
	echo "</tr>\n";
}

//------------------------------------------------------------------------------------------------------

function multi_tool_list_cells($label, $name, $selected_id=null, $all_option=false, 
$submit_on_change=false, $all=false, $editkey = false)
{
	if ($label != null)
	{
		echo "<td class='label'>$label</td><td>\n";
		echo multi_tool_list($name, $selected_id, $all_option, $submit_on_change, 
		$all, $editkey);
		echo "</td>\n";
	}
else
{
		echo "<td class='label'>\n";
	echo multi_tool_list($name, $selected_id, $all_option, $submit_on_change, 
		$all, $editkey);
		echo "</td>\n";
}	
		
}
function multi_tool_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false,$all=false, $editkey = false)
{
    global $Ajax;

	$sql ="SELECT  id,tool_name FROM 0_ser_tools";
	
	$ret = combo_input($name, $selected_id, $sql, 'id', 'tool_name',
	array(
		'format' => '_format_add_curr',
	    'order' => array('id'),
		'multi' => true,	
		'select_submit'=> $submit_on_change,
		'async' => true,
		'sel_hint' => $mode ? _('Press Space tab to filter by name fragment') :
	_('Select Tools'),
		'show_inactive'=>$all
		));
	if ($editkey)
		$ret .= add_frequency_combo('tool_name');		
	return $ret;
}

//----------------------------------------------

function service_tool_operator_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_id=false, $multiple=false)
{
	 $sql = "SELECT id,CONCAT(operator_code,' - ',operator_name) AS operator_name,inactive FROM 0_operators WHERE FIND_IN_SET(".db_escape($tool_id).",tool_id) AND allocation_status=0";
	
	// one operator for multiple equipments
	/* $sql = "SELECT id,CONCAT(operator_code,' - ',operator_name) AS operator_name,inactive FROM 0_operators WHERE FIND_IN_SET(".db_escape($tool_id).",tool_id)"; */
	
	
	if($multiple)
	{
	return combo_input_multi($name, $selected_id, $sql, 'id', 'operator_name',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true,
		'multi' => $multiple
 	));
	}
	else
	{
	   return combo_input($name, $selected_id, $sql, 'id', 'operator_name',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
	}
}

function service_tool_operator_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_id=false, $multiple=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_tool_operator_list($name, $selected_id, $spec_opt, $submit_on_change,$tool_id, $multiple);
	echo "</td>\n";
}

function service_tool_operator_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_id=false, $multiple=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_tool_operator_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$tool_id,$multiple);
	echo "</tr>\n";
}



function onsite_item_types_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $onsite_item_types;
	echo "<td>";
	echo array_selector($name, $selected_id, $onsite_item_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";

}


function service_customer_tool_serial_no_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$customer_id=false)

{
	$sql = "SELECT tool_serial_no, tool_serial_no FROM 0_ser_tool_current_status WHERE current_status=2";
	
	if($customer_id)
	{
		$sql .=" AND customer_id = ".db_escape($customer_id)."";
	}
	
	return combo_input($name, $selected_id, $sql, 'tool_serial_no', 'tool_serial_no',
 	array('order'=>'tool_serial_no',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_customer_tool_serial_no_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$customer_id=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_customer_tool_serial_no_list($name, $selected_id, $spec_opt, $submit_on_change,$customer_id);
	echo "</td>\n";
}

function service_customer_tool_serial_no_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$customer_id=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_customer_tool_serial_no_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$customer_id);
	echo "</tr>\n";
}



//-----------------------------------------------------------------------------------------------------------
function project_site_expense_items_list($name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $opts=array(), $editkey = false, $expense_item=0)
{
	$sql = "SELECT stock_id, s.description, c.description, s.inactive, s.editable
			FROM ".TB_PREF."stock_master s,".TB_PREF."stock_category c WHERE s.category_id=c.category_id";
			
	
	if($expense_item!=0)
      $sql .= " AND expense_item=1";
  
	$sql .= " AND mb_flag ='D'";
		
	

	if ($editkey)
		set_editor('item', $name, $editkey);

	$ret = combo_input($name, $selected_id, $sql, 'stock_id', 's.description',
		array_merge(
		  array(
			'format' => '_format_stock_items',
			'spec_option' => $all_option===true ?  _("All Items") : $all_option,
			'spec_id' => ALL_TEXT,
			'search_box' => true,
			'search' => array("stock_id", "c.description","s.description"),
			'search_submit' => get_company_pref('no_item_list')!=0 && (!isset($opts['fixed_asset']) || !$opts['fixed_asset']),
			'size'=>10,
			'select_submit'=> $submit_on_change,
			'category' => 2,
			'order' => array('c.description','stock_id'),
			'editlink' => $editkey ? add_edit_combo('item') : false,
			'editable' => false,
			'max' => 255
		  ), $opts), $type );
	return $ret;
}


// 0 => all , 1 => only rental items 2 => all items exclude rental items
 
function project_site_expense_items_list_cells($label, $name, $selected_id=null, $all_option=false,$submit_on_change=false, $all=false, $editkey = false, $opts= array(),
	$expense_item=0)
{
	if (isset($opts['fixed_asset']) && $opts['fixed_asset'])
		$editor_item = 'fa_item';
	else
		$editor_item = 'item';

// 	if ($editkey) ??
//		set_editor($editor_item, $name, $editkey);

	if ($label != null)
		echo "<td>$label</td>\n";

// ??
//  $opts = array_merge($options, array('cells'=>true, 'show_inactive'=>$all, 'new_icon' => $editkey ? 'item' : false));
//
//	echo project_site_expense_items_list($name, $selected_id, $all_option, $submit_on_change, $opts);

	echo project_site_expense_items_list($name, $selected_id, $all_option, $submit_on_change,
		array_merge(array('cells'=>true, 'show_inactive'=>$all), $opts), $editkey,'stock',
		$expense_item);

}

function existing_tool_types_list_row($label, $name, $selected_id=null, $enabled=true)
{
	global $existing_tool_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $existing_tool_types, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}



function hire_types_list_row($label, $name, $selected_id=null, $enabled=true)
{
	global $hire_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $hire_types, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}


function service_partner_owner_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	$sql = "SELECT id, partner_owner_name, inactive FROM ".TB_PREF."ser_partner_owner_master";
	
	return combo_input($name, $selected_id, $sql, 'id', 'partner_owner_name',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_partner_owner_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_partner_owner_list($name, $selected_id, $spec_opt, $submit_on_change);
	echo "</td>\n";
}

function service_partner_owner_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_partner_owner_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
	echo "</tr>\n";
}

function low_bed_required_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $low_bed_required;
	echo "<td>";
	echo array_selector($name, $selected_id, $low_bed_required, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";

}


function service_customer_tool_order_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$customer_id=false)
{
	$sql = "SELECT id, reference FROM ".TB_PREF."ser_tool_order_entry 
	WHERE debtor_no = ".db_escape($customer_id).""; 
	return combo_input($name, $selected_id, $sql, 'id', 'reference',
 	array('order'=>'reference',
		'spec_option' => $spec_opt,
		'spec_id' => 0,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_customer_tool_order_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$customer_id=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_customer_tool_order_list($name, $selected_id, $spec_opt, $submit_on_change,$customer_id);
	echo "</td>\n";
}

function service_customer_tool_order_list_row($label, $name, $selected_id=null, 
$spec_opt=false, $submit_on_change=false,$customer_id=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_customer_tool_order_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$customer_id);
	echo "</tr>\n";
}


function po_search_types_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
    global $po_search_types;

	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $po_search_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}

function invoice_times_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $invoice_times;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $invoice_times, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

function invoice_types_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $invoice_types;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $invoice_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

function invoice_day_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $invoice_day;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $invoice_day, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

//-----------------------------------------------------------

function schedule_invoice_types_list_row($label, $name, $selected_id=null, $enabled=true)
{
	global $schedule_invoice_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $schedule_invoice_types, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}


function schedule_tool_orders_list($name, $selected_id=null, $all_option=false, $submit_on_change=false,$rental_order_no=0)
{
    
	//DATE_FORMAT("2017-06-15", "%d/%m/%Y");

	$sql = "SELECT plan.id, CONCAT(tool.reference,'  ', date_format(plan.planned_date,'%d/%m/%Y')) 
	FROM ".TB_PREF."rental_order_entry tool,
    ".TB_PREF."equipment_invoice_plan plan	
	WHERE plan.order_id = tool.id
	AND plan.invoice_status = 0
	AND plan.has_child = 0
	AND tool.id=".db_escape($rental_order_no)."";
	
	//display_error($sql);
	
	return combo_input($name, $selected_id, $sql, 'plan.id', 'reference',
		array(
			'spec_option' => $all_option === true ? _("Select") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change,
			'async' => true
		) );
}

function schedule_tool_orders_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false,$rental_order_no=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo schedule_tool_orders_list($name, $selected_id, $all_option, $submit_on_change,$rental_order_no);
	echo "</td>\n";
}

function schedule_tool_orders_list_row($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false,$rental_order_no=0)
{
	echo "<tr><td class='label'>$label</td>";
	schedule_tool_orders_list_cells(null, $name, $selected_id, $all_option, $submit_on_change,$rental_order_no);
	echo "</tr>\n";
}

// Documents list
function document_types_list($name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	$sql = "SELECT id, document_type, inactive FROM ".TB_PREF."document_types 
	WHERE 1=1";

	return combo_input($name, $selected_id, $sql, 'id', 'document_type',
		array(
			'order' => array('document_type'),
			'spec_option' => $all_option === true ? _("All Document Types") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}
function document_types_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo document_types_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function document_types_list_row($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	document_types_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}

function equipment_expenses_types_list($name, $selected_id=null, $all_option=false, $submit_on_change=false,
$expense_type=0)
{
	$sql = "SELECT id, expense_name, inactive FROM ".TB_PREF."ser_expenses_master 
	WHERE 1=1";
	
	if($expense_type!=0)
	{
		$sql .=" AND expense_type = ".db_escape($expense_type)."";
	}
	
	
	
	return combo_input($name, $selected_id, $sql, 'id', 'expense_name',
		array(
			'order' => array('expense_name'),
			'spec_option' => $all_option === true ? _("Select an Expense Name") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}
function equipment_expenses_types_list_cells($label, $name, $selected_id=null, $all_option=false, 
$submit_on_change=false,$expense_type=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo equipment_expenses_types_list($name, $selected_id, $all_option, $submit_on_change,$expense_type);
	echo "</td>\n";
}

function equipment_expenses_types_list_row($label, $name, $selected_id=null, $all_option=false,
$submit_on_change=false,$expense_type=0)
{
	echo "<tr><td class='label'>$label</td>";
	equipment_expenses_types_list_cells(null, $name, $selected_id, $all_option, $submit_on_change,$expense_type);
	echo "</tr>\n";
}
//------------------------------------------------------------------------------------------
function site_locations_list($name, $selected_id=null, $special_option=false, $area_id=0, 
$submit_on_change=false)
{
	$sql = "SELECT site_loc_code, site_location_name, inactive FROM ".TB_PREF."site_locations";
	if($area_id)
	$sql .=" WHERE area_id=".db_escape($area_id)."";
	return combo_input($name, $selected_id, $sql, 'site_loc_code', 'site_location_name', 
		array('spec_option' => $special_option===true ? ' ' : $special_option,
		'select_submit'=> $submit_on_change,
		'order' => 'site_loc_code', 'spec_id' => 0));
}

function site_locations_list_cells($label, $name, $selected_id=null, $special_option=false,
 $area_id=0, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo site_locations_list($name, $selected_id, $special_option, $area_id,$submit_on_change);
	echo "</td>\n";
}

function site_locations_list_row($label, $name, $selected_id=null, $special_option=false,
 $area_id=0, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	site_locations_list_cells(null, $name, $selected_id, $special_option, 
	$area_id, $submit_on_change);
	echo "</tr>\n";
}

function customer_site_locations_list($name, $selected_id=null, $special_option=false, 
$customer_id=0, $submit_on_change=false)
{
	$sql = "SELECT sl.site_loc_code, sl.site_location_name FROM ".TB_PREF."customer_site_locations cs ,".TB_PREF."site_locations sl WHERE cs.site_loc_code=sl.site_loc_code AND customer_id=".db_escape($customer_id)."";
	
	return combo_input($name, $selected_id, $sql, 'sl.site_loc_code', 'sl.site_location_name', 
		array('spec_option' => $special_option===true ? ' ' : $special_option,
				'select_submit'=> $submit_on_change,
		'order' => 'site_location_name', 'spec_id' => 0));
}

function customer_site_locations_list_cells($label, $name, $selected_id=null, $special_option=false, $customer_id=0, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo customer_site_locations_list($name, $selected_id, $special_option, $customer_id, $submit_on_change);
	echo "</td>\n";
}

function customer_site_locations_list_row($label, $name, $selected_id=null, $special_option=false, 
$customer_id=0, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	customer_site_locations_list_cells(null, $name, $selected_id, $special_option, $customer_id, $submit_on_change);
	echo "</tr>\n";
}



function all_tool_types_fileter_list_cells($label, $name, $selected_id=null, 
$submit_on_change=false)
{

   global $all_tool_types_filter;
   
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $all_tool_types_filter, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}

 function all_tool_types_fileter_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $all_tool_types_filter;
	
	echo "<td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $all_tool_types_filter, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}


function service_order_list($name, $selected_id=null, $special_option=false, $submit_on_change=false, $customer_id=0)
{
	$sql = "SELECT id, reference FROM ".TB_PREF."rental_order_entry ord WHERE debtor_no=".db_escape($customer_id)." AND order_closed=0";
	
	
	return combo_input($name, $selected_id, $sql, 'id', 'reference', 
		array('spec_option' => $special_option===true ? ' ' : $special_option,
				'select_submit'=> $submit_on_change,
		'order' => 'id', 'spec_id' => 0));
}

function service_order_list_cells($label, $name, $selected_id=null, $special_option=false, $submit_on_change=false, $customer_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_order_list($name, $selected_id, $special_option, $submit_on_change, $customer_id);
	echo "</td>\n";
}

function service_order_list_row($label, $name, $selected_id=null, $special_option=false,$submit_on_change=false, $customer_id=0)
{
	echo "<tr><td class='label'>$label</td>";
	service_order_list_cells(null, $name, $selected_id, $special_option, $submit_on_change, $customer_id);
	echo "</tr>\n";
}


function service_tool_available_operator_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$tool_id=false)
{
	$sql = "SELECT id,CONCAT(operator_code,' - ',operator_name) AS operator_name,inactive FROM 0_operators WHERE FIND_IN_SET(".db_escape($tool_id).",tool_id) AND allocation_status=0";
	return combo_input($name, $selected_id, $sql, 'id', 'operator_name',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_tool_available_operator_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_id=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_tool_available_operator_list($name, $selected_id, $spec_opt, $submit_on_change,$tool_id);
	echo "</td>\n";
}

function service_tool_available_operator_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_id=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_tool_available_operator_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$tool_id);
	echo "</tr>\n";
}


function service_schedule_types_row($label, $name, $selected_id=null, $enabled=true)
{
	global $service_schedule_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $service_schedule_types, 
		array( 
			'select_submit'=> false, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}


function breakdown_problem_types_row($label, $name, $selected_id=null, $enabled=true)
{
	global $breakdown_problem_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $breakdown_problem_types, 
		array( 
			'select_submit'=> false, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}



function breakdown_service_reference_list($name, $selected_id=null, $special_option=false, $submit_on_change=false, $tool_serial_no=0)
{
	$sql = "SELECT id, reference FROM ".TB_PREF."ser_tool_breakdown_request 
	WHERE tool_serial_no=".db_escape($tool_serial_no)." 
	AND service_required=1 AND service_status=0";
	return combo_input($name, $selected_id, $sql, 'id', 'reference', 
		array('spec_option' => $special_option===true ? ' ' : $special_option,
				'select_submit'=> $submit_on_change,
		'order' => 'id', 'spec_id' => 0));
}

function breakdown_service_reference_list_cells($label, $name, $selected_id=null, $special_option=false, $submit_on_change=false, $tool_serial_no=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo breakdown_service_reference_list($name, $selected_id, $special_option, $submit_on_change, $tool_serial_no);
	echo "</td>\n";
}

function breakdown_service_reference_list_row($label, $name, $selected_id=null, 
$special_option=false,$submit_on_change=false, $tool_serial_no=0)
{
	echo "<tr><td class='label'>$label</td>";
	breakdown_service_reference_list_cells(null, $name, $selected_id, $special_option, $submit_on_change, $tool_serial_no);
	echo "</tr>\n";
}


// Complaints Module --  Rajesh --01-04-2023
function complaint_against_types_list_row($label, $name, $selected_id=null, 
$submit_on_change=false)
{
	global $complaint_against_types;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $complaint_against_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}


function customer_complaint_status_list($name, $selected_id=null,  $submit_on_change=false, $all_option=false){
    
    $status = array(1 => _("Open"), 2 => _("In Progress"), 3 => _("Closed"));

    return array_selector($name, $selected_id, $status, 
        array( 
            'select_submit'=> $submit_on_change,
            'async' => false,
            'spec_option'=> ($all_option == true ? _("All") : false ),
            'disabled' => false ) ); // FIX?
}

function customer_complaint_status_list_cells($label, $name, $selected_id=null,  $submit_on_change=false, $all_option=false){
    if ($label != null)
        echo "<td>$label</td>\n";
    echo "<td>";
    echo customer_complaint_status_list($name, $selected_id, $submit_on_change, $all_option);
    echo "</td>\n";
}

//--------------------------------------------------------------------------------
// Customer wise Sales details in complaint module

function complaint_sales_orders($name, $selected_id=null, $all_option=false, $submit_on_change=false, $customer_id=0)
{

	$sql = "SELECT sorder.reference, sorder.reference
					FROM ".TB_PREF."debtors_master debtor,
					".TB_PREF."sales_orders sorder
					WHERE debtor.debtor_no=sorder.debtor_no	
					AND sorder.trans_type=".ST_SALESORDER." 
					AND reference!='auto'
					AND sorder.debtor_no =".db_escape($customer_id)."
					GROUP BY sorder.order_no";

	return combo_input($name, $selected_id, $sql, 'order_no', 'reference',
		array(
			'spec_option' => $all_option === true ? _("All Sales Orders") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}

function complaint_sales_orders_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $customer_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo complaint_sales_orders($name, $selected_id, $all_option, $submit_on_change,$customer_id);
	echo "</td>\n";
}

function complaint_sales_orders_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false,$customer_id=0)
{
	echo "<tr><td class='label'>$label</td>";
	complaint_sales_orders_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $customer_id);
	echo "</tr>\n";
}

//-------------------------------------------------------------

function complaint_sales_invoices($name, $selected_id=null, $all_option=false, $submit_on_change=false, $customer_id=0)
{
					
		$sql = "SELECT debtor_trans.reference,debtor_trans.reference
						FROM ".TB_PREF."debtors_master debtor,"
							.TB_PREF."debtor_trans debtor_trans LEFT JOIN ".TB_PREF."voided AS vd ON debtor_trans.type=vd.type AND
							debtor_trans.trans_no=vd.id
						WHERE debtor_trans.type=".ST_SALESINVOICE." AND debtor.debtor_no=debtor_trans.debtor_no AND ISNULL(vd.id)
                 AND debtor_trans.debtor_no=".db_escape($customer_id)."					
						GROUP BY trans_no";		
	return combo_input($name, $selected_id, $sql, 'trans_no', 'reference',
		array(
			'spec_option' => $all_option === true ? _("All Sales Invoices") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}

function complaint_sales_invoices_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $customer_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo complaint_sales_invoices($name, $selected_id, $all_option, $submit_on_change,$customer_id);
	echo "</td>\n";
}

function complaint_sales_invoices_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false,$customer_id=0)
{
	echo "<tr><td class='label'>$label</td>";
	complaint_sales_invoices_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $customer_id);
	echo "</tr>\n";
}

//----------------------------------------------------------------------

function complaint_sales_payments($name, $selected_id=null, $all_option=false, $submit_on_change=false, $customer_id=0)
{
					
		$sql = "SELECT debtor_trans.reference,debtor_trans.reference
						FROM ".TB_PREF."debtors_master debtor,"
							.TB_PREF."debtor_trans debtor_trans LEFT JOIN ".TB_PREF."voided AS vd ON debtor_trans.type=vd.type AND
							debtor_trans.trans_no=vd.id
						WHERE debtor_trans.type=".ST_CUSTPAYMENT." AND debtor.debtor_no=debtor_trans.debtor_no AND ISNULL(vd.id)
                 AND debtor_trans.debtor_no=".db_escape($customer_id)."					
						GROUP BY trans_no";		
	return combo_input($name, $selected_id, $sql, 'trans_no', 'reference',
		array(
			'spec_option' => $all_option === true ? _("All Sales Payments") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}

function complaint_sales_payments_list_cells($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false, $customer_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo complaint_sales_payments($name, $selected_id, $all_option, $submit_on_change,$customer_id);
	echo "</td>\n";
}

function complaint_sales_payments_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false,$customer_id=0)
{
	echo "<tr><td class='label'>$label</td>";
	complaint_sales_payments_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $customer_id);
	echo "</tr>\n";
}

function transportation_types_list_cell($name, $selected_id=null, 
$submit_on_change=false)
{
	global $transportation_types;
	
	echo "<td>";
	echo array_selector($name, $selected_id, $transportation_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>";
}

//----------------------------------------------------------------
//Tool Issues

function tool_issue_priorities_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $tool_issue_priorities;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $tool_issue_priorities, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

function tool_issue_priorities_list_cells($label, $name, $selected_id=null, 
$submit_on_change=false)
{
	global $tool_issue_priorities;
	echo "<td>";
	echo array_selector($name, $selected_id, $tool_issue_priorities, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";

}


function service_tool_issues_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false)
{
	$sql = "SELECT id, issue_name, inactive FROM ".TB_PREF."ser_tool_issues";
	
	return combo_input($name, $selected_id, $sql, 'id', 'issue_name',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => 0,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_tool_issues_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_tool_issues_list($name, $selected_id, $spec_opt, $submit_on_change);
	echo "</td>\n";
}

function service_tool_issues_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_tool_issues_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
	echo "</tr>\n";
}

function service_operators_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false, $editkey = false)
{
	$sql = "SELECT id, CONCAT(operator_code,' - ',operator_name) as operator_name, inactive FROM 0_operators";
	
	if ($editkey)
		set_editor('operator_search', $name, $editkey);
	
	$ret = combo_input($name, $selected_id, $sql, 'id', 'operator_name',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => 0,
 		'select_submit'=> $submit_on_change,
 		'async' => true,
		'sel_hint' => _('Select Operator'),
		'editlink' => $editkey ? add_edit_combo('operator_search') : false
	), "operator_search" );
	return $ret;
}

function service_operators_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $editkey = false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_operators_list($name, $selected_id, $spec_opt, $submit_on_change, $editkey);
	echo "</td>\n";
}

function service_operators_list_row($label, $name, $selected_id=null, $spec_opt=false,
$submit_on_change=false, $editkey = false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_operators_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change, $editkey);
	echo "</tr>\n";
}


function site_location_wise_customer_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$site_loc_code=false)
{
	$sql = "SELECT DISTINCT cust.debtor_no, cust.name, cust.inactive FROM ".TB_PREF."customer_site_locations csl,".TB_PREF."debtors_master cust 
	WHERE csl.customer_id = cust.debtor_no";
	
	if($site_loc_code)
	{
		$sql .=" AND  csl.site_loc_code=".db_escape($site_loc_code)." 
		GROUP BY csl.customer_id";
	}
	
	return combo_input($name, $selected_id, $sql, 'cust.debtor_no', 'cust.name',
 	array('order'=>'cust.debtor_no',
		'spec_option' => $spec_opt,
		'spec_id' => 0,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function site_location_wise_customer_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$site_loc_code=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo site_location_wise_customer_list($name, $selected_id, $spec_opt, $submit_on_change,$site_loc_code);
	echo "</td>\n";
}

function site_location_wise_customer_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$site_loc_code=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	site_location_wise_customer_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$site_loc_code);
	echo "</tr>\n";
}


function customer_service_tool_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$customer_id=false,$tool_code_id=false)
{
	$sql = "SELECT tc.id, tc.tool_name, tc.inactive FROM 0_ser_tool_current_status tcs,0_ser_tools tc 
	WHERE tcs.tool_id = tc.id ";
	
	if($tool_code_id)
	{
		$sql .=" AND  tc.tool_code_id=".db_escape($tool_code_id)."";
	}
	
	if($customer_id)
	{
		$sql .=" AND  tcs.customer_id=".db_escape($customer_id)." GROUP BY tcs.tool_id";
	}
	
	
	return combo_input($name, $selected_id, $sql, 'tc.id', 'tc.tool_name',
 	array('order'=>'tc.id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function customer_service_tool_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$customer_id=false,$tool_code_id=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo customer_service_tool_list($name, $selected_id, $spec_opt, $submit_on_change,$customer_id,$tool_code_id);
	echo "</td>\n";
}

function customer_service_tool_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$customer_id=false,$tool_code_id=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	customer_service_tool_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$customer_id,$tool_code_id);
	echo "</tr>\n";
}


function all_tool_issue_priorities_list_cells($label, $name, $selected_id=null, 
$submit_on_change=false)
{

   global $all_tool_issue_priorities;
   
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $all_tool_issue_priorities, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}

function multiple_companies_filter_rep($name, $value=null, $spec_opt=false,$submit_on_change=false)
{
	global $db_connections;
     $companies_array=array();
	for ($i = 0; $i < count($db_connections); $i++){
		$companies_array[$db_connections[$i]['tbpref']]=$db_connections[$i]['name'];
		
	}
		return array_selector($name, $value, $companies_array, 
			array( 
				'spec_option'=> $spec_opt,
				'spec_id' => ALL_NUMERIC,
				'async' => false,
				'select_submit'=> $submit_on_change
				)
			);

}

//------------------------------------------------------------------
//sameer

function maintenance_department_persons_list($name, $selected_id=null, $spec_opt=false,$submit_on_change=false)
{
	
	$sql = "SELECT empl.empl_id, CONCAT(empl.empl_firstname,'  ',empl.empl_lastname,' - ',empl.empl_id) as empl_firstname FROM ".TB_PREF."kv_empl_info empl,".TB_PREF."kv_empl_job job WHERE empl.empl_id=job.empl_id AND job.department = '5'";
	
	return combo_input($name, $selected_id, $sql, 'empl.empl_id', 'empl_firstname',
 	array('order'=>'empl_firstname',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function maintenance_department_persons_cells($label, $name, $selected_id=null, $spec_opt=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>\n";
	echo maintenance_department_persons_list($name, $selected_id, $spec_opt);
	echo "</td>\n";
}

function maintenance_department_persons_list_row($label, $name, $selected_id=null, 
$spec_opt=false)
{
	echo "<tr><td class='label'>$label</td>";
	maintenance_department_persons_cells(null, $name, $selected_id, $spec_opt);
	echo "</tr>\n";
}


function tool_kit_against_types_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $tool_kit_against_types;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $tool_kit_against_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

function tool_kit_against_types_list_cells($label, $name, $selected_id=null, 
$submit_on_change=false)
{
	global $tool_kit_against_types;
	echo "<td>";
	echo array_selector($name, $selected_id, $tool_kit_against_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";

}


function tool_kit_items_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $editkey=false)
{
	if ($editkey)
		set_editor('item', $name, $editkey);

	if ($label != null)
		echo "<td>$label</td>\n";
	echo tool_kit_items_list($name, $selected_id, $all_option, $submit_on_change,
		'', array('cells'=>true, 'max'=>50)); // maximum is set to 50 and not default 255.
}

function tool_kit_items_list($name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $type='', $opts=array())
{
	// all sales codes
	$sql = "SELECT i.item_code, i.description, c.description, count(*)>1 as kit,
			 i.inactive, if(count(*)>1, '0', s.editable) as editable
			FROM
			".TB_PREF."stock_master s,
			".TB_PREF."item_codes i
			LEFT JOIN
			".TB_PREF."stock_category c
			ON i.category_id=c.category_id
			WHERE i.stock_id=s.stock_id
            AND mb_flag != 'F' AND c.category_id = 7";

	
	if ($type == 'local')	{ // exclude foreign codes
		$sql .=	" AND !i.is_foreign"; 
	} elseif ($type == 'kits') { // sales kits
		$sql .=	" AND !i.is_foreign AND i.item_code!=i.stock_id";
	}
	$sql .= " AND !i.inactive AND !s.inactive";
	$sql .= " GROUP BY i.item_code";
	
	
	return combo_input($name, $selected_id, $sql, 'i.item_code', 'c.description',
		array_merge(
		  array(
			'format' => '_format_stock_items',
			'spec_option' => $all_option===true ?  _("All Items") : $all_option,
			'spec_id' => ALL_TEXT,
			'search_box' => true,
			'search' => array("i.item_code", "c.description", "i.description"),
			'search_submit' => get_company_pref('no_item_list')!=0,
			'size'=>15,
			'select_submit'=> $submit_on_change,
			'category' => 2,
			'order' => array('c.description','i.item_code'),
			'editable' => 30,
			'max' => 255
		  ), $opts), $type == 'tool_kits' ? $type : "tool_kits" );
}


function tool_kit_return_types_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $tool_kit_return_types;
    	
	 if ($label != null)
		echo "<td>$label</td>\n";
     
	echo "<td>";
	echo array_selector($name, $selected_id, $tool_kit_return_types, 
		array( 
			'select_submit'=> $submit_on_change, 
			'enabled' => !$enabled) );
	
	echo"</td>";
}


function service_jobcard_tool_serial_no_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$job_no=false)
{
	
	$sql = "SELECT tool_serial_no, tool_serial_no FROM ".TB_PREF."ser_sl2_jobcard_open WHERE reference=".db_escape($job_no)."";
				
	return combo_input($name, $selected_id, $sql, 'tool_serial_no', 'tool_serial_no',
 	array('order'=>'tool_serial_no',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_jobcard_tool_serial_no_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$job_no=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_jobcard_tool_serial_no_list($name, $selected_id, $spec_opt, $submit_on_change,$job_no);
	echo "</td>\n";
}

function service_jobcard_tool_serial_no_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$job_no=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_jobcard_tool_serial_no_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$job_no);
	echo "</tr>\n";
}

//ravi
function fa_equipments_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	$sql = "SELECT stock_id, description FROM 0_stock_master WHERE mb_flag='F' and category_id=6 and stock_id not in (select fa_item_code from 0_ser_tool_current_status) and !inactive";
				
	return combo_input($name, $selected_id, $sql, 'stock_id', 'description',
 	array('order'=>'description',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function fa_equipments_list_cells($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo fa_equipments_list($name, $selected_id, $spec_opt, $submit_on_change);
	echo "</td>\n";
}

function fa_equipments_list_row($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	fa_equipments_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
	echo "</tr>\n";
}


//----------------------------------------------------------

function service_tool_all_operators_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_id=false)
{
	$sql = "SELECT id,CONCAT(operator_code,' - ',operator_name) AS operator_name,inactive FROM 0_operators WHERE FIND_IN_SET(".db_escape($tool_id).",tool_id)";
	
	return combo_input($name, $selected_id, $sql, 'id', 'operator_name',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_tool_all_operators_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_id=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_tool_all_operators_list($name, $selected_id, $spec_opt, $submit_on_change,$tool_id);
	echo "</td>\n";
}

function service_tool_all_operators_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_id=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_tool_all_operators_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$tool_id);
	echo "</tr>\n";
}


function service_tool_serial_no_not_in_disposed_and_soldoff_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_id=false,$status=0, $customer_id=0)

{
	//ravi
	
	$status=str_replace("'","",$status);
	
	$sql = "SELECT tool_serial_no, tool_serial_no FROM 0_ser_tool_current_status 
	WHERE current_status NOT IN ('8','9')";
	
	if($tool_id)
	{
		$sql .=" AND tool_id=".db_escape($tool_id)."";
	}
	
	
	if($customer_id!=0){
	$sql .=" AND customer_id=".db_escape($customer_id)."";
	}
	
	return combo_input($name, $selected_id, $sql, 'tool_serial_no', 'tool_serial_no',
 	array('order'=>'tool_serial_no',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_tool_serial_no_not_in_disposed_and_soldoff_list_cells($label, $name,
$selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_id=false,$status=0, 
$customer_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_tool_serial_no_not_in_disposed_and_soldoff_list($name, $selected_id, $spec_opt, $submit_on_change,$tool_id,$status,$customer_id);
	echo "</td>\n";
}

function service_tool_serial_no_not_in_disposed_and_soldoff_list_row($label, $name, 
$selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_id=false,$status=0, 
$customer_id=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_tool_serial_no_not_in_disposed_and_soldoff_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$tool_id,$status,$customer_id);
	echo "</tr>\n";
}


function checklist_types_list_row($label, $name, $selected_id=null, $enabled=true)
{
	global $check_list_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $check_list_types, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled) );
	echo "</td></tr>\n";
}


function equipment_documents_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $tool_serial_no='')
{
	$sql = "SELECT id, document_name, inactive FROM ".TB_PREF."tool_documents WHERE tool_serial_no =".db_escape($tool_serial_no)."";
	return combo_input($name, $selected_id, $sql, 'id', 'document_name',
		array(
			'order' => array('id'),
			'spec_option' => $all_option === true ? _("Select Documents") : $all_option,
			'spec_id' => -1,
			'select_submit'=> $submit_on_change
		) );
}
function equipment_documents_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $tool_serial_no='')
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo equipment_documents_list($name, $selected_id, $all_option, $submit_on_change, $tool_serial_no);
	echo "</td>\n";
}

function equipment_documents_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $tool_serial_no='')
{
	echo "<tr><td class='label'>$label</td>";
	equipment_documents_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $tool_serial_no);
	echo "</tr>\n";
}

/*
function accessory_types_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	global $accessories_types;

	return array_selector($name, $selected_id, $accessories_types,
	array( 'spec_option'=> $spec_opt,
			'spec_id' => -1,
	       'select_submit'=> $submit_on_change,
 		   'async' => true ) );
}


function accessory_types_list_row($label, $name, $selected_id=null, $enabled=true, $spec_option=false)
{
	global $accessories_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $accessories_types, 
		array( 
			'select_submit'=> true, 
			'spec_option' => $spec_option === true ? _("All Types") : $spec_option,
			'spec_id' => -1,
			'disabled' => !$enabled) );
	echo "</td></tr>\n";
}
*/


function accessory_types_list($name, $selected_id=null, $all_option=false, $submit_on_change=false,$enabled=true)
{

	$sql = "SELECT id, accessories_types, inactive FROM ".TB_PREF."ser_accessories_types";

	return combo_input($name, $selected_id, $sql, 'id', 'accessories_types',
		array(
			'spec_option' => $all_option === true ? _("All Accessories") : $all_option,
			'spec_id' => -1,
			'select_submit'=> $submit_on_change,
			'disabled' => !$enabled
		) );
}

function accessory_types_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false,$enabled=true)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo accessory_types_list($name, $selected_id, $all_option, $submit_on_change,$enabled);
	echo "</td>\n";
}

function accessory_types_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false,$enabled=true)
{
	echo "<tr><td class='label'>$label</td>";
	accessory_types_list_cells(null, $name, $selected_id, $all_option, $submit_on_change,$enabled);
	echo "</tr>\n";
}

function accessories_current_status_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	global $accessories_current_status;

	return array_selector($name, $selected_id, $accessories_current_status,
	array( 'spec_option'=> $spec_opt,
			'spec_id' => -1,
	       'select_submit'=> $submit_on_change,
 		   'async' => true ) );
}




 function accessories_current_status_list_row($label, $name, $selected_id=null, $submit_on_change=false, $spec_option=false)
{
	global $accessories_current_status;
	
	echo "<td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $accessories_current_status, 
		array( 
			'select_submit'=> $submit_on_change,
			'spec_option' => $spec_option === true ? _("All Current Status") : $spec_option,
			'spec_id' => -1,
		) );
	echo "</td>\n";
}



// Documents list
function ser_document_types_list($name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	$sql = "SELECT id, document_type, inactive FROM ".TB_PREF."ser_document_types 
	WHERE 1=1";

	return combo_input($name, $selected_id, $sql, 'id', 'document_type',
		array(
			'order' => array('document_type'),
			'spec_option' => $all_option === true ? _("All Document Types") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}
function ser_document_types_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo ser_document_types_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function ser_document_types_list_row($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	ser_document_types_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}

//--------------------------------------------------------


function ser_color_list($name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	$sql = "SELECT id, color, inactive FROM ".TB_PREF."ser_color_master 
	WHERE 1=1";

	return combo_input($name, $selected_id, $sql, 'id', 'color',
		array(
			'order' => array('color'),
			'spec_option' => $all_option === true ? _("All Colors") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}
function ser_color_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo ser_color_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function ser_color_list_row($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	ser_color_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}

//---------------------------------------------------------------------------
function ser_engine_brands_list($name, $selected_id=null, $all_option=false,
$submit_on_change=false)
{
	$sql = "SELECT id, engine_brand, inactive FROM ".TB_PREF."ser_engine_brands 
	WHERE 1=1";

	return combo_input($name, $selected_id, $sql, 'id', 'engine_brand',
		array(
			'order' => array('engine_brand'),
			'spec_option' => $all_option === true ? _("All Engine Brands") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}

function ser_engine_brands_list_cells($label, $name, $selected_id=null, $all_option=false, 
$submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo ser_engine_brands_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function ser_engine_brands_list_row($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	ser_engine_brands_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}


function ser_transmission_brands_list($name, $selected_id=null, $all_option=false,
$submit_on_change=false)
{
	$sql = "SELECT id, transmission_brand, inactive FROM ".TB_PREF."ser_transmission_brands 
	WHERE 1=1";

	return combo_input($name, $selected_id, $sql, 'id', 'transmission_brand',
		array(
			'order' => array('transmission_brand'),
			'spec_option' => $all_option === true ? _("All Transmission Brands") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}

function ser_transmission_brands_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo ser_transmission_brands_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function ser_transmission_brands_list_row($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	ser_transmission_brands_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}

//-----------------------------------------------------------------------

function fleet_specification_types_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $fleet_specification_types;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $fleet_specification_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}


function fleet_specification_types_list_cells($label, $name, $selected_id=null, 
$submit_on_change=false)
{
	global $fleet_specification_types;
	echo "<td>";
	echo array_selector($name, $selected_id, $fleet_specification_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";

}

function ser_model_year_master_list($name, $selected_id=null, $all_option=false,
$submit_on_change=false)
{
	$sql = "SELECT id, model_year, inactive FROM ".TB_PREF."ser_model_year_master 
	WHERE 1=1";

	return combo_input($name, $selected_id, $sql, 'id', 'model_year',
		array(
			'order' => array('id'),
			'spec_option' => $all_option === true ? _("All Model Year") : $all_option,
			'spec_id' => -1,
			'select_submit'=> $submit_on_change
		) );
}

function ser_model_year_master_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo ser_model_year_master_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function ser_model_year_master_list_row($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	ser_model_year_master_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}


function ser_no_of_axles_master_list($name, $selected_id=null, $all_option=false,
$submit_on_change=false)
{
	$sql = "SELECT id, no_of_axles, inactive FROM ".TB_PREF."ser_no_of_axles_master 
	WHERE 1=1";

	return combo_input($name, $selected_id, $sql, 'id', 'no_of_axles',
		array(
			'order' => array('id'),
			'spec_option' => $all_option === true ? _("All no of axles") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}

function ser_no_of_axles_master_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo ser_no_of_axles_master_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function ser_no_of_axles_master_list_row($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	ser_no_of_axles_master_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}

function ser_tyre_sizes_list($name, $selected_id=null, $all_option=false,
$submit_on_change=false)
{
	$sql = "SELECT id, tyre_size, inactive FROM ".TB_PREF."ser_tyre_sizes 
	WHERE 1=1";

	return combo_input($name, $selected_id, $sql, 'id', 'tyre_size',
		array(
			'order' => array('id'),
			'spec_option' => $all_option === true ? _("All tyre sizes") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}

function ser_tyre_sizes_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo ser_tyre_sizes_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function ser_tyre_sizes_list_row($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	ser_tyre_sizes_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}


function ser_engine_models_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $engine_brand_id='')
{
	$sql = "SELECT id, engine_model, inactive FROM ".TB_PREF."ser_engine_models 
	WHERE engine_brand_id =".db_escape($engine_brand_id)."";
	return combo_input($name, $selected_id, $sql, 'id', 'engine_model',
		array(
			'order' => array('id'),
			'spec_option' => $all_option === true ? _("Select engine models") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}

function ser_engine_models_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $engine_brand_id='')
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo ser_engine_models_list($name, $selected_id, $all_option, $submit_on_change, $engine_brand_id);
	echo "</td>\n";
}

function ser_engine_models_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $engine_brand_id='')
{
	echo "<tr><td class='label'>$label</td>";
	ser_engine_models_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $engine_brand_id);
	echo "</tr>\n";
}


function ser_transmission_models_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $transmission_brand_id='')
{
	$sql = "SELECT id, transmission_model, inactive FROM ".TB_PREF."ser_transmission_models 
	WHERE transmission_brand_id =".db_escape($transmission_brand_id)."";
	return combo_input($name, $selected_id, $sql, 'id', 'transmission_model',
		array(
			'order' => array('id'),
			'spec_option' => $all_option === true ? _("Select transmission models") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}

function ser_transmission_models_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $transmission_brand_id='')
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo ser_transmission_models_list($name, $selected_id, $all_option, $submit_on_change, $transmission_brand_id);
	echo "</td>\n";
}

function ser_transmission_models_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $transmission_brand_id='')
{
	echo "<tr><td class='label'>$label</td>";
	ser_transmission_models_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $transmission_brand_id);
	echo "</tr>\n";
}



function ser_boom_hoist_height_master_list($name, $selected_id=null, $all_option=false,
$submit_on_change=false)
{
	$sql = "SELECT id, boom_hoist_height, inactive FROM ".TB_PREF."ser_boom_hoist_height_master 
	WHERE 1=1";

	return combo_input($name, $selected_id, $sql, 'id', 'boom_hoist_height',
		array(
			'order' => array('id'),
			'spec_option' => $all_option === true ? _("All boom/hoist height") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}

function ser_boom_hoist_height_master_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo ser_boom_hoist_height_master_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function ser_boom_hoist_height_master_list_row($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	ser_boom_hoist_height_master_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}


function ser_fork_length_master_list($name, $selected_id=null, $all_option=false,
$submit_on_change=false)
{
	$sql = "SELECT id, fork_length, inactive FROM ".TB_PREF."ser_fork_length_master 
	WHERE 1=1";

	return combo_input($name, $selected_id, $sql, 'id', 'fork_length',
		array(
			'order' => array('id'),
			'spec_option' => $all_option === true ? _("All fork length") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}

function ser_fork_length_master_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo ser_fork_length_master_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function ser_fork_length_master_list_row($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	ser_fork_length_master_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}


//ravi

function unassigned_operator_service_tool_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$operator_id=false,$operator_type=false)
{
  /* $sql = "SELECT tcs.tool_serial_no,tcs.tool_serial_no FROM ".TB_PREF."ser_tool_current_status tcs WHERE tcs.current_status=2 and (operator_id=0 OR operator_id2=0) and tool_id in (select op.tool_id from ".TB_PREF."operators op where op.id=".db_escape($operator_id).")"; */


if($operator_type==1)
 $sql = " SELECT tcs.tool_serial_no,tcs.tool_serial_no 
FROM ".TB_PREF."ser_tool_current_status tcs
WHERE operator_id=0 and  FIND_IN_SET(tcs.tool_id , (SELECT GROUP_CONCAT(op.tool_id SEPARATOR ',') FROM ".TB_PREF."operators op where op.id=".db_escape($operator_id)."))";
if($operator_type==2)
 $sql = " SELECT tcs.tool_serial_no,tcs.tool_serial_no 
FROM ".TB_PREF."ser_tool_current_status tcs
WHERE operator_id2=0 and  FIND_IN_SET(tcs.tool_id , (SELECT GROUP_CONCAT(op.tool_id SEPARATOR ',') FROM ".TB_PREF."operators op where op.id=".db_escape($operator_id)."))";
else
 $sql = " SELECT tcs.tool_serial_no,tcs.tool_serial_no 
FROM ".TB_PREF."ser_tool_current_status tcs
WHERE (operator_id=0 OR operator_id2=0) and  FIND_IN_SET(tcs.tool_id , (SELECT GROUP_CONCAT(op.tool_id SEPARATOR ',') FROM ".TB_PREF."operators op where op.id=".db_escape($operator_id)."))";

	
   return combo_input($name, $selected_id, $sql, 'tool_serial_no', 'tool_serial_no',
 	array('order'=>'tool_serial_no',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function unassigned_operator_service_tool_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$operator_id=false,$operator_type=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo unassigned_operator_service_tool_list($name, $selected_id, $spec_opt, $submit_on_change,$operator_id,$operator_type);
	echo "</td>\n";
}

function unassigned_operator_service_tool_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$operator_id=false,$operator_type=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	unassigned_operator_service_tool_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$operator_id,$operator_type);
	echo "</tr>\n";
}


function operator_status_list_row($label, $name, $selected_id=null, $submit_on_change=false,$spec_opt=false)
{
	global $operator_status;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $operator_status, 
		array( 
			'select_submit'=> $submit_on_change,
			'spec_id' => -1,
			'spec_option' => $spec_opt
		) );
	echo "</td></tr>\n";
}



//------------------------------------------------------------------------------

function fixed_asset_service_tool_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false)
{
	$sql = "SELECT stock_id, description , inactive FROM 0_stock_master WHERE category_id=6";
	
		
	return combo_input($name, $selected_id, $sql, 'stock_id', 'description',
 	array('order'=>'stock_id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function fixed_asset_service_tool_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo fixed_asset_service_tool_list($name, $selected_id, $spec_opt, $submit_on_change);
	echo "</td>\n";
}

function fixed_asset_service_tool_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	fixed_asset_service_tool_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
	echo "</tr>\n";
}

//-------------------------------------------------------------------------------------------

function service_fuel_expense_tool_operator_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	$sql = "SELECT id,CONCAT(operator_code,' - ',operator_name) AS operator_name,inactive FROM 0_operators";
		
	return combo_input($name, $selected_id, $sql, 'id', 'operator_name',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_fuel_expense_tool_operator_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_fuel_expense_tool_operator_list($name, $selected_id, $spec_opt, $submit_on_change);
	echo "</td>\n";
}

function service_fuel_expense_tool_operator_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_fuel_expense_tool_operator_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
	echo "</tr>\n";
}


function mounted_types_list_row($label, $name, $selected_id=null, $enabled=true)
{
	global $mounted_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $mounted_types, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}


function fleet_types_list_row($label, $name, $selected_id=null, $enabled=true)
{
	global $fleet_types;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $fleet_types, 
		array( 
			'select_submit'=> false, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}


function mounted_fa_equipments_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	//$sql = "SELECT stock_id, description FROM 0_stock_master WHERE mb_flag='F' and //category_id=6 and !inactive";
	
	$sql = "SELECT stock_id, description FROM 0_stock_master WHERE mb_flag='F' and category_id=6 and stock_id not in (select fa_item_code from 0_ser_tool_current_status) and !inactive
    UNION
    SELECT stock_id, description FROM 0_stock_master WHERE mb_flag='F' and category_id=6 and stock_id in (select fa_item_code from 0_ser_tool_current_status
    where mounted_type_id=1) and !inactive";
	return combo_input($name, $selected_id, $sql, 'stock_id', 'description',
 	array('order'=>'description',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function mounted_fa_equipments_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo mounted_fa_equipments_list($name, $selected_id, $spec_opt, $submit_on_change);
	echo "</td>\n";
}

function mounted_fa_equipments_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	mounted_fa_equipments_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
	echo "</tr>\n";
}

//-----------------------------------sameer
function inv_supplier_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false,$multi=false)
{

	$sql = "SELECT id, supplier, inactive FROM ".TB_PREF."inv_suppliers";

	return combo_input($name, $selected_id, $sql, 'id', 'supplier',
		array(
			'spec_option' => $all_option === true ? _("All Suppliers") : $all_option,
			'spec_id' => -1,
			'select_submit'=> $submit_on_change,
			'multi' => $multi,
		) );
}

function inv_supplier_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo inv_supplier_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function inv_supplier_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	inv_supplier_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}


function supplier_category_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $category)
{
	$sql = "SELECT id, name, inactive FROM ".TB_PREF."supplier_division WHERE ".TB_PREF."supplier_division.supplier_id = ".$category."";
  //display_error($sql);
	return combo_input($name, $selected_id, $sql, 'id', 'name',
	array('order'=>'name',
		'spec_option' => $spec_opt === true ? _("All Subcategories") : $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function supplier_category_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $category)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo supplier_category_list($name, $selected_id, $spec_opt, $submit_on_change, $category);
	echo "</td>\n";
}

function supplier_subcategory_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $category)
{
	echo "<tr><td class='label'>$label</td>";
	supplier_category_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change, $category);
	echo "</tr>\n";
}

function inventory_brand_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{

	 
	
	$sql = "SELECT id, brand_name, inactive FROM ".TB_PREF."brand";

	return combo_input($name, $selected_id, $sql, 'id', 'brand_name',
		array(
			'spec_option' => $all_option === true ? _("Select Brand") : $all_option,
			'spec_id' => -1,
			'select_submit'=> $submit_on_change
		) );
}

function inventory_brand_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo inventory_brand_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function inventory_brand_list_row($label, $name, $selected_id=null, $all_option=true, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	inventory_brand_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}

//-------------------------------------------------------------------
function item_grp_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	
	$sql = "SELECT id, group_name, inactive FROM ".TB_PREF."item_grp";

	return combo_input($name, $selected_id, $sql, 'id', 'group_name',
		array(
			'spec_option' => $all_option === true ? _("Select Group") : $all_option,
			'spec_id' => -1,
			'select_submit'=> $submit_on_change
		) );
}

function item_grp_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo item_grp_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function item_grp_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	item_grp_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}

//ravi 
function rental_operator_status_list_row($label, $name, $selected_id=null, $enabled=true)
{
	global $quote_operator_status;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $quote_operator_status, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled) );
	echo "</td></tr>\n";
}

function rental_operator_status_list_cells($label, $name, $selected_id=null, $enabled=true)
{
	global $quote_operator_status;

	 if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $quote_operator_status, 
		array( 
			'select_submit'=> $submit_on_change, 
			'enabled' => !$enabled) );
	echo"</td>";
}

//-----------------------------------------------------------------------------------------------
function tool_capacity_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $tool_type_id=0)
{	
	$sql = "SELECT id,tool_capacity FROM 0_ser_tool_capacity WHERE inactive=0 ";
		if(($tool_type_id!='-1') && $tool_type_id !='0')
		{
			$sql .=" AND tool_type_id =".db_escape($tool_type_id)."";
		}
	
	return combo_input($name, $selected_id, $sql, 'id', 'tool_capacity',
 	array('order'=>'tool_capacity',
		'spec_option' => $spec_opt === true ? _("Select a Equipment Capacity") : $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function tool_capacity_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $tool_type_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo tool_capacity_list($name, $selected_id, $spec_opt, $submit_on_change, $tool_type_id);
	echo "</td>\n";
}

function tool_capacity_list_row($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false ,$tool_type_id=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	tool_capacity_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$tool_type_id);
	echo "</tr>\n";
}

//-------------------------------------------------------------------------------------------------------------

function service_forecast_tool_capacity_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $forecast_id=0, $tool_type_id=0, $groupby=0)
{	
	$sql = "SELECT sfd.tool_capacity_id,tcap.tool_capacity FROM ".TB_PREF."ser_forecast_details as sfd,
	0_ser_tool_capacity as tcap 
	WHERE sfd.tool_capacity_id = tcap.id 
	AND sfd.forecast_id=".db_escape($forecast_id)."";
	
	if(($tool_type_id!='-1') && $tool_type_id !='0')
		{
			$sql .=" AND sfd.tool_type_id =".db_escape($tool_type_id)."";
		}
		
	if($groupby)
	{
		$sql .=" GROUP BY sfd.tool_capacity_id";
	}
	
	//display_error($sql);
	return combo_input($name, $selected_id, $sql, 'sfd.tool_capacity_id', 'tcap.tool_capacity',
 	array('order'=>'sfd.id',
		'spec_option' => $spec_opt === true ? _("Select a Equipment Capacity") : $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_forecast_tool_capacity_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $forecast_id=0,  $tool_type_id=0,$groupby=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_forecast_tool_capacity_list($name, $selected_id, $spec_opt, $submit_on_change, $forecast_id,  $tool_type_id, $groupby);
	echo "</td>\n";
}

function service_forecast_tool_capacity_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false ,$forecast_id=0,  $tool_type_id=0, $groupby=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_forecast_tool_capacity_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$forecast_id, $tool_type_id,  $groupby);
	echo "</tr>\n";
}

//-----------------------------------------------------------------------------------------------

function service_forecast_tool_type_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $forecast_id=0, $groupby=0)
{	
	$sql = "SELECT sfd.tool_type_id,tt.tool_type FROM ".TB_PREF."ser_forecast_details as sfd,
	".TB_PREF."ser_tool_types as tt 
	WHERE sfd.tool_type_id = tt.id 
	AND sfd.forecast_id=".db_escape($forecast_id)."";
	if($groupby)
	{
		$sql .=" GROUP BY sfd.tool_type_id";
	}
	return combo_input($name, $selected_id, $sql, 'sfd.tool_type_id', 'tt.tool_type',
 	array('order'=>'sfd.id',
		'spec_option' => $spec_opt === true ? _("Select a Equipment Types") : $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_forecast_tool_type_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $forecast_id=0,$groupby=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_forecast_tool_type_list($name, $selected_id, $spec_opt, $submit_on_change, $forecast_id, $groupby);
	echo "</td>\n";
}

function service_forecast_tool_type_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false ,$forecast_id=0, $groupby=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_forecast_tool_type_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$forecast_id, $groupby);
	echo "</tr>\n";
}


//--------------------------------------------------------------------------------------------------------------------

function current_fiscal_months($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $disabled=false) {
   
   $Year_id = get_post('year');
   $month_id = (int)get_post($name); 
  
   if(!empty($month_id) && $month_id>0 ){} else { $selected_id = date("m");}
    $hrm_months_list = get_months_in_fiscal_year();
    $options = array(
        'spec_option' => $spec_opt === true ? _("Select a Month") : $spec_opt,
		'spec_id' => -1,
		'select_submit'=> $submit_on_change,
        'disabled' => $disabled
    );
    return  array_selector($name, $selected_id, $hrm_months_list, $options);
}

function get_months_in_fiscal_year(){   
    $Year_id = get_post('year');
	if($Year_id){
		$fiscal_year_get = get_fiscalyear($Year_id);
	} else {
        $Year_id = get_post('PARAM_0');
        if($Year_id)
            $fiscal_year_get = get_fiscalyear($Year_id);
        else
		    $fiscal_year_get = get_current_fiscalyear();
	}
    $array_months = array();
    $start    = (new DateTime($fiscal_year_get['begin']))->modify('first day of this month');
    $end      = (new DateTime($fiscal_year_get['end']))->modify('first day of next month');
    $interval = DateInterval::createFromDateString('1 month');
    $period   = new DatePeriod($start, $interval, $end);

    foreach ($period as $dt) {
        $array_months[$dt->format("m")] = date("m - F", strtotime($dt->format("Y")."-".$dt->format("m")."-01"));
    }
    return $array_months;
}


function get_month_name_by_id($month){
    $months =  array(
                '01' => 'January',
                '02' => 'February',
                '03' => 'March',
                '04' => 'April',
                '05' => 'May',
                '06' => 'June',
                '07' => 'July',
                '08' => 'August',
                '09' => 'September',
                '10' => 'October',
                '11' => 'November',
                '12' => 'December'
                );
    return $months[$month];
}

// Componets not mandatory  #Ramesh
function service_all_tool_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $tool_type_id=false, $tool_code_id=false)
{
	$sql = "SELECT tools.id, tools.tool_name, tools.inactive FROM 0_ser_tools tools WHERE 1 ";	
	if($tool_type_id)
	{
		$sql .=" AND  tools.tool_type_id=".db_escape($tool_type_id)."";
	}	
	if($tool_code_id)
	{
		$sql .=" AND  tools.tool_code_id=".db_escape($tool_code_id)."";
	}	
	return combo_input($name, $selected_id, $sql, 'tools.id', 'tools.tool_name',
 	array('order'=>'tools.id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}
function service_all_tool_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $tool_type_id=false, $tool_code_id=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_all_tool_list($name, $selected_id, $spec_opt, $submit_on_change, $tool_type_id,$tool_code_id);
	echo "</td>\n";
}

function service_all_tool_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $tool_type_id=false, $tool_code_id=false)
{ 
	echo "<tr><td class='label'>$label</td>";
	service_all_tool_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change, $tool_type_id,$tool_code_id);
	echo "</tr>\n";
}


//ravi
function fa_items_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	$sql = "SELECT stock_id, description FROM 0_stock_master WHERE mb_flag='F' and category_id
	!=6  and !inactive";
				
	return combo_input($name, $selected_id, $sql, 'stock_id', 'description',
 	array('order'=>'description',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function fa_items_list_cells($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo fa_items_list($name, $selected_id, $spec_opt, $submit_on_change);
	echo "</td>\n";
}

function fa_items_list_row($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	fa_items_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
	echo "</tr>\n";
}

//-------------------------------------------------------------------------------
//sameer

function transaction_type_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	global $tool_transaction_types;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $tool_transaction_types, 
		array( 
			'spec_option' => $spec_opt,
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}

//------------------------------------------------------------------------------
// Rental tool order assign
function service_tool_serial_no_by_tool_type_id_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_type_id=false,$status=0)

{
	$sql = "SELECT tcs.tool_serial_no, tcs.tool_serial_no 
	FROM 0_ser_tool_current_status tcs,0_ser_tools tools ";
	
	$sql .=" WHERE tcs.tool_id = tools.id
	AND tools.tool_type_id=".db_escape($tool_type_id)."";
	
	if($status!=0){
	$sql .=" AND tcs.current_status=".db_escape($status)."";
	}

	return combo_input($name, $selected_id, $sql, 'tool_serial_no', 'tool_serial_no',
 	array('order'=>'tool_serial_no',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_tool_serial_no_by_tool_type_id_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_type_id=false,$status=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_tool_serial_no_by_tool_type_id_list($name, $selected_id, $spec_opt, $submit_on_change,$tool_type_id,$status);
	echo "</td>\n";
}

function service_tool_serial_no_by_tool_type_id_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$tool_type_id=false,$status=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_tool_serial_no_by_tool_type_id_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$tool_type_id,$status);
	echo "</tr>\n";
}


function service_expense_types_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{

   global $service_expense_types;
   
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo array_selector($name, $selected_id, $service_expense_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}

 function service_expense_types_list_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $service_expense_types;
	
	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";
	
	echo array_selector($name, $selected_id, $service_expense_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}


// Rental tool order assign
function rental_order_fleet_assign_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$stock_id=false,$status=0)

{
	/* $sql = "SELECT tcs.tool_serial_no, tcs.tool_serial_no 
	FROM 0_ser_tool_current_status tcs,0_stock_master sm";
	
	$sql .=" WHERE tcs.tool_id = sm.tool_id 
	AND sm.stock_id=".db_escape($stock_id).""; */
	
	
	$sql = "select tools.tool_type_id from 0_ser_tools tools,0_stock_master sm where tools.id=sm.tool_id and sm.stock_id=".db_escape($stock_id)."";
	
	
	$result = db_query($sql, 'cannot get all printers');
        
    $row=db_fetch_row($result);

    $tool_type_id=$row[0];
	
	$sql = "SELECT tcs.tool_serial_no, tcs.tool_serial_no 
	FROM 0_ser_tool_current_status tcs where tcs.tool_id in (select id from 0_ser_tools tools where tool_type_id=".db_escape($tool_type_id).")" ;
	
	/*if($status!=0){
	$sql .=" AND tcs.current_status=".db_escape($status)."";
	}*/
	
	if($status!=0){
	$sql .=" AND tcs.current_status in ($status)";  //ravi
	}

	return combo_input($name, $selected_id, $sql, 'tool_serial_no', 'tool_serial_no',
 	array('order'=>'tool_serial_no',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function rental_order_fleet_assign_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$stock_id=false,$status=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo rental_order_fleet_assign_list($name, $selected_id, $spec_opt, $submit_on_change,$stock_id,$status);
	echo "</td>\n";
}


//ravi
function rental_quotation_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,
$customer_id)
{
	$sql = "SELECT id, reference FROM ".TB_PREF."rental_quote_entry 
	WHERE debtor_no=".db_escape($customer_id)." GROUP BY id";
	
	//display_error($sql);
	return combo_input($name, $selected_id, $sql, 'id', 'reference',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function rental_quotation_list_cells($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$customer_id)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo rental_quotation_list($name, $selected_id, $spec_opt, $submit_on_change, $customer_id);
	echo "</td>\n";
}

function rental_quotation_list_row($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$customer_id=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	rental_quotation_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$customer_id);
	echo "</tr>\n";
}


//Rajesh
function sales_orders_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $customer_id)
{
	$sql = "SELECT orders.order_no,orders.reference FROM ".TB_PREF."sales_orders as orders INNER JOIN ".TB_PREF."sales_order_details as details ON details.order_no = orders.order_no 
	WHERE orders.debtor_no =".db_escape($customer_id)." 
	AND orders.trans_type='30' AND details.trans_type = '30'  
	AND (details.qty_sent = '0' OR details.qty_sent < details.quantity) 
	AND orders.reference!='auto' GROUP BY orders.order_no";
	
	return combo_input($name, $selected_id, $sql, 'order_no', 'reference',
 	array('order'=>'order_no',
		'spec_option' => $spec_opt,
		'spec_id' => 0,
 		'select_submit'=> $submit_on_change,
 		'async' => true,
		'orddesc' =>'order_no'
 	));
}

function sales_orders_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $customer_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo sales_orders_list($name, $selected_id, $spec_opt, $submit_on_change, $customer_id);
	echo "</td>\n";
}

function sales_orders_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$customer_id=0)
{
	echo "<tr><td class='label'>$label</td>";
	sales_orders_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change, $customer_id);
	echo "</tr>\n";
}


//ravi
function rental_orders_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $customer_id)
{
	$sql = "SELECT orders.id as order_no,orders.reference FROM ".TB_PREF."rental_order_entry as orders 
	WHERE orders.debtor_no =".db_escape($customer_id)." and order_closed=0 ";
	
	return combo_input($name, $selected_id, $sql, 'order_no', 'reference',
 	array('order'=>'order_no',
		'spec_option' => $spec_opt,
		'spec_id' => 0,
 		'select_submit'=> $submit_on_change,
 		'async' => true,
		'orddesc' =>'order_no'
 	));
}

function rental_orders_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $customer_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo rental_orders_list($name, $selected_id, $spec_opt, $submit_on_change, $customer_id);
	echo "</td>\n";
}

function rental_orders_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$customer_id=0)
{
	echo "<tr><td class='label'>$label</td>";
	rental_orders_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change, $customer_id);
	echo "</tr>\n";
}


//ravi
function assigned_fleet_serial_no_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$rental_order_no=0,$delivery_against_exchange=0)

{
	//ravi
	
	$status=str_replace("'","",$status);
	
	$sql = "SELECT DISTINCT tool_serial_no, tool_serial_no FROM ".TB_PREF."ser_tool_order_details_assign where 1=1 ";
			
	if($rental_order_no!=0){
	$sql .=" AND order_id =".db_escape($rental_order_no)."";
	} 
	
	if($delivery_against_exchange)
	{
	   $sql = "SELECT DISTINCT tool_serial_no, tool_serial_no FROM ".TB_PREF."ser_tool_current_status where current_status=5";
	}
		
				
	return combo_input($name, $selected_id, $sql, 'tool_serial_no', 'tool_serial_no',
 	array('order'=>'tool_serial_no',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function assigned_fleet_serial_no_list_cells($label, $name, $selected_id=null, $spec_opt=false,
 $submit_on_change=false, $rental_order_no=0, $delivery_against_exchange=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo assigned_fleet_serial_no_list($name, $selected_id, $spec_opt, $submit_on_change,$rental_order_no,$delivery_against_exchange);
	echo "</td>\n";
}

function assigned_fleet_serial_no_list_row($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false, $rental_order_no=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	assigned_fleet_serial_no_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$rental_order_no);
	echo "</tr>\n";
}



//ravi

function rental_order_timesheet_list($name, $selected_id=null, $all_option=false, $submit_on_change=false,$rental_order_no=0)
{
    
	$sql = "SELECT id, CONCAT(reference,'  ', date_format(date_,'%d/%m/%Y')) as reference 
	FROM ".TB_PREF."time_sheet_entry 	
	WHERE rental_order_no=".db_escape($rental_order_no)." 
	AND is_invoiced=0";
		
	return combo_input($name, $selected_id, $sql, 'id', 'reference',
		array(
			'spec_option' => $all_option === true ? _("Select") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change,
			'async' => true
		) );
}

function rental_order_timesheet_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false,$rental_order_no=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo rental_order_timesheet_list($name, $selected_id, $all_option, $submit_on_change,$rental_order_no);
	echo "</td>\n";
}

function rental_order_timesheet_list_row($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false,$rental_order_no=0)
{
	echo "<tr><td class='label'>$label</td>";
	rental_order_timesheet_list_cells(null, $name, $selected_id, $all_option, $submit_on_change,$rental_order_no);
	echo "</tr>\n";
}



//ravi
function ser_tool_quotation_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false,
$customer_id)
{
	$sql = "SELECT id, reference FROM ".TB_PREF."ser_tool_quote_entry 
	WHERE debtor_no=".db_escape($customer_id)." GROUP BY id";
	
	//display_error($sql);
	return combo_input($name, $selected_id, $sql, 'id', 'reference',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function ser_tool_quotation_list_cells($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$customer_id)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo ser_tool_quotation_list($name, $selected_id, $spec_opt, $submit_on_change, $customer_id);
	echo "</td>\n";
}

function ser_tool_quotation_list_row($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$customer_id=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	ser_tool_quotation_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$customer_id);
	echo "</tr>\n";
}


function service_forecast_activity_new_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$customer_id)
{
	$sql = "SELECT id, reference FROM ".TB_PREF."ser_forecast_entry 
	WHERE id NOT IN (SELECT forecast_id FROM ".TB_PREF."ser_tool_quote_entry)
	AND debtor_no=".db_escape($customer_id)." GROUP BY id";
	
	//display_error($sql);
	return combo_input($name, $selected_id, $sql, 'id', 'reference',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_forecast_activity_new_list_cells($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$customer_id)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_forecast_activity_new_list($name, $selected_id, $spec_opt, $submit_on_change, $customer_id);
	echo "</td>\n";
}

function service_forecast_activity_new_list_row($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$customer_id=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_forecast_activity_new_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$customer_id);
	echo "</tr>\n";
}

//-----------------------------------------------------------------------------------------------

function service_forecast_rig_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $forecast_id=0, $groupby=0)
{	
	$sql = "SELECT sfd.rig,sl.site_location_name FROM ".TB_PREF."ser_forecast_details as sfd,
	".TB_PREF."site_locations as sl WHERE sfd.rig = sl.site_loc_code 
	AND sfd.forecast_id=".db_escape($forecast_id)."";
	if($groupby)
	{
		$sql .=" GROUP BY sfd.rig";
	}
	
	//display_error($sql);
	return combo_input($name, $selected_id, $sql, 'sfd.rig', 'sl.site_location_name',
 	array('order'=>'sfd.id',
		'spec_option' => $spec_opt === true ? _("Select a Site Location") : $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_forecast_rig_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $forecast_id=0,$groupby=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_forecast_rig_list($name, $selected_id, $spec_opt, $submit_on_change, $forecast_id, $groupby);
	echo "</td>\n";
}

function service_forecast_rig_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false ,$forecast_id=0, $groupby=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_forecast_rig_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$forecast_id, $groupby);
	echo "</tr>\n";
}

// AM Service

function brand_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{

	 
	
	$sql = "SELECT id, brand_name, inactive FROM ".TB_PREF."am_brand";

	return combo_input($name, $selected_id, $sql, 'id', 'brand_name',
		array(
			'spec_option' => $all_option === true ? _("All Locations") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}

function brand_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo brand_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function brand_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	brand_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}

//Archana
function type_list($name, $selected_id=null, $name_own="", $name_external="", $submit_on_change=false)
{
	$items = array();
	$items['0'] = strlen($name_external) ? $name_external : _("External");
	$items['1'] = strlen($name_own) ? $name_own : _("Own");

	return array_selector($name, $selected_id, $items, 
		array( 
			'select_submit'=> $submit_on_change,
			'async' => false ) ); // FIX?
}

function type_list_cells($label, $name, $selected_id=null, $name_own="", $name_external="", $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo type_list($name, $selected_id, $name_own, $name_external, $submit_on_change);
	echo "</td>\n";
}

function type_list_row($label, $name, $selected_id=null, $name_own="", $name_external="", $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	type_list_cells(null, $name, $selected_id, $name_own, $name_external, $submit_on_change);
	echo "</tr>\n";
}
function model_vehicle_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $brand)
{
	$sql = "SELECT id, name, inactive FROM ".TB_PREF."am_model_vehicle WHERE 1=1";
	
	if($brand){
	
	 $sql .= " AND brand_id = ".$brand."";
	}
	
	return combo_input($name, $selected_id, $sql, 'id', 'name',
	array('order'=>'id',
		'spec_option' => $spec_opt === true ? _("All Subcategories") : $spec_opt,
		'spec_id' => ALL_TEXT,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function model_vehicle_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $brand)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo model_vehicle_list($name, $selected_id, $spec_opt, $submit_on_change, $brand);
	echo "</td>\n";
}

function model_vehicle_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $brand)
{
	echo "<tr><td class='label'>$label</td>";
	model_vehicle_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change, $brand);
	echo "</tr>\n";
}
function group_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{

	 
	
	$sql = "SELECT id, name, inactive FROM ".TB_PREF."am_group";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'spec_option' => $all_option === true ? _("All Locations") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}

function group_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo group_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function group_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	group_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}
function plate_type_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{

	$sql = "SELECT id, name, inactive FROM ".TB_PREF."am_plate";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'spec_option' => $all_option === true ? _("All Locations") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}

function plate_type_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo plate_type_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function plate_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	plate_type_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}
function vehicle_type_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{

	 
	
	$sql = "SELECT id, name, inactive FROM ".TB_PREF."am_vehicle_type";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'spec_option' => $all_option === true ? _("All Locations") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}

function vehicle_type_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo vehicle_type_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function vehicle_type_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	vehicle_type_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}
function colour_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{

	 
	
	$sql = "SELECT id, name, inactive FROM ".TB_PREF."am_colour";

	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'spec_option' => $all_option === true ? _("All Locations") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}

function colour_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo colour_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}

function colour_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	colour_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}
function gear_row($label, $name, $selected_id=null, $enabled=true)
{
	global $gear;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $gear, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}

function am_jobcard_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false, 
	$show_inactive=false, $editkey = false,$jc_status='',$all_loc=true)
{
	global $SysPrefs;
	$sql = "SELECT job_no,job_no,chasis_no,reg_no FROM ".TB_PREF."am_jobcard_open WHERE 1 ";
	if($jc_status!='')
	{
		$sql .=" AND status=".db_escape($jc_status)." ";
	}
  /*  if(!$all_loc)
	{
		$user_locs=$_SESSION["wa_current_user"]->user_location;
		$sql .=" AND  FIND_IN_SET(loc_code,'$user_locs')";
	} */
	
	 if ($editkey)
		set_editor('amjobcard', $name, $editkey);
	$ret = combo_input($name, $selected_id, $sql, 'job_no', 'job_no',
	//array( 'order' => array('job_no'),
	array( 'order' => array('id'),
		 'search_box' => $mode!=0,
		 'type' => 1,
		 'size' => 20,
         'search' => array("job_no","job_no","reg_no"),        
		 'spec_option' => $spec_option === true ? _("All Job Cards") : $spec_option,
		 'spec_id' => ALL_TEXT,
		 'select_submit'=> $submit_on_change,
		 'async' => false,
		 'sel_hint' => $mode ? _('Press Space tab to filter by name fragment; F2 - entry new jobcard') :
		 _('Select Jobcard'),
		 'show_inactive' => $show_inactive,
		 'editlink' => $editkey ? add_edit_combo('amjobcard') : false
	), "amjobcard" ); 
	 return $ret;
}


function am_jobcard_list_cells($label, $name, $selected_id=null, $all_option=false,	$submit_on_change=false, $show_inactive=false, $editkey = false,$jc_status='',$all_loc=true)
{
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td nowrap>";
	echo am_jobcard_list($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey,$jc_status,$all_loc);
	echo "</td>\n";
}


function am_jobcard_list_row($label, $name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false,$jc_status='',$all_loc=true)
{

	echo "<tr><td class='label'>$label</td><td nowrap>";
	echo am_jobcard_list($name, $selected_id, $all_option, $submit_on_change,
		$show_inactive, $editkey,$jc_status,$all_loc);
	echo "</td>\n</tr>\n";
}

function am_chasis_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false, 
	$show_inactive=false, $editkey = false)
{
	global $SysPrefs;
	$sql = "SELECT chasis_no,chasis_no FROM ".TB_PREF."am_vehicle_master";
	
	if ($editkey)
		set_editor('amvehicle', $name, $editkey);
	$ret = combo_input($name, $selected_id, $sql, 'chasis_no', 'chasis_no',
	array( 'order' => array('chasis_no'),
		 'search_box' => $mode!=0,
		 'type' => 1,
		 'size' => 20,
         'search' => array("chasis_no","chasis_no","chasis_no"),        
		 'spec_option' => $spec_option === true ? _("All Job Cards") : $spec_option,
		 'spec_id' => ALL_TEXT,
		 'select_submit'=> $submit_on_change,
		 'async' => false,
		 'sel_hint' => $mode ? _('Press Space tab to filter by name fragment; F2 - entry new jobcard') :
		 _('Select Jobcard'),
		 'show_inactive' => $show_inactive,
		 'editlink' => $editkey ? add_edit_combo('amvehicle') : false
	), "amvehicle" ); 
	 return $ret;
}

function am_chasis_list_cells($label, $name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false)
{
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td nowrap>";
	echo am_chasis_list($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey);
	echo "</td>\n";
}


function am_chasis_list_row($label, $name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false)
{

	echo "<tr><td class='label'>$label</td><td nowrap>";
	echo am_chasis_list($name, $selected_id, $all_option, $submit_on_change,
		$show_inactive, $editkey);
	echo "</td>\n</tr>\n";
}

function am_reg_no_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false, 
	$show_inactive=false, $editkey = false)
{
	global $SysPrefs;
	$sql = "SELECT reg_no,reg_no FROM ".TB_PREF."am_vehicle_master";
	
	if ($editkey)
		set_editor('amvehicle', $name, $editkey);
	$ret = combo_input($name, $selected_id, $sql, 'reg_no', 'reg_no',
	array( 'order' => array('reg_no'),
		 'search_box' => $mode!=0,
		 'type' => 1,
		 'size' => 20,
         'search' => array("reg_no","reg_no","reg_no"),        
		 'spec_option' => $spec_option === true ? _("All Job Cards") : $spec_option,
		 'spec_id' => ALL_TEXT,
		 'select_submit'=> $submit_on_change,
		 'async' => false,
		 'sel_hint' => $mode ? _('Press Space tab to filter by name fragment; F2 - entry new jobcard') :
		 _('Select Jobcard'),
		 'show_inactive' => $show_inactive,
		 'editlink' => $editkey ? add_edit_combo('amvehicle') : false
	), "amvehicle" ); 
	 return $ret;
}

function am_reg_no_list_cells($label, $name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false)
{
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td nowrap>";
	echo am_reg_no_list($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey);
	echo "</td>\n";
}


function am_reg_no_list_row($label, $name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false)
{

	echo "<tr><td class='label'>$label</td><td nowrap>";
	echo am_reg_no_list($name, $selected_id, $all_option, $submit_on_change,
		$show_inactive, $editkey);
	echo "</td>\n</tr>\n";
}
function cust_type_row($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $cust_type;
	
	echo "<tr><td class='label'>$label</td><td>";
	echo array_selector($name, $selected_id, $cust_type, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td></tr>\n";
}


function activeinactive_list($name, $selected_id=null, $name_active="", $name_inactive="", $submit_on_change=false)
{
	$items = array();
	$items['0'] = strlen($name_inactive) ? $name_inactive : _("Active");
	$items['1'] = strlen($name_active) ? $name_active : _("Inactive");

	return array_selector($name, $selected_id, $items, 
		array( 
			'select_submit'=> $submit_on_change,
			'async' => false ) ); // FIX?
}

function activeinactive_list_cells($label, $name, $selected_id=null, $name_active="", $name_inactive="", $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo activeinactive_list($name, $selected_id, $name_active, $name_inactive, $submit_on_change);
	echo "</td>\n";
}

function activeinactive_list_row($label, $name, $selected_id=null, $name_active="", $name_inactive="", $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	activeinactive_list_cells(null, $name, $selected_id, $name_active, $name_inactive, $submit_on_change);
	echo "</tr>\n";
}

//ravi
function am_major_complaints_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	$sql = "SELECT id, description,inactive FROM ".TB_PREF."am_major_complaints";
	return combo_input($name, $selected_id, $sql, 'id', 'description',
		array(
			'spec_option' => $all_option === true ? _("Select Major Complaint") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}
function am_major_complaints_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
	echo "<td>$label</td>\n";
	echo "<td>";
	echo am_major_complaints_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}
function am_major_complaints_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	am_major_complaints_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}

function am_checklist_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	$sql = "SELECT id, name,inactive FROM ".TB_PREF."am_checklist";
	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'spec_option' => $all_option === true ? _("Select Checklist") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}
function am_checklist_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
	echo "<td>$label</td>\n";
	echo "<td>";
	echo am_checklist_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}
function am_checklist_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	am_checklist_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}
function am_departments_list($name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	$sql = "SELECT id, name,inactive FROM ".TB_PREF."am_work_shop_department";
	return combo_input($name, $selected_id, $sql, 'id', 'name',
		array(
			'spec_option' => $all_option === true ? _("Select Workshop Department") : $all_option,
			'spec_id' => ALL_TEXT,
			'select_submit'=> $submit_on_change
		) );
}
function am_departments_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	if ($label != null)
	echo "<td>$label</td>\n";
	echo "<td>";
	echo am_departments_list($name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</td>\n";
}
function am_departments_list_row($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $fixed_asset=false)
{
	echo "<tr><td class='label'>$label</td>";
	am_departments_list_cells(null, $name, $selected_id, $all_option, $submit_on_change, $fixed_asset);
	echo "</tr>\n";
}

function sales_item_warranty_type_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $sales_item_warranty_type;
	
	echo "<td>";
	echo array_selector($name, $selected_id, $sales_item_warranty_type, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}

function am_part_types_list($name, $selected_id=null, $submit_on_change=false, $special_option=false)
{
	$sql = "SELECT id, name, inactive,sort_order FROM ".TB_PREF."am_part_type";
	return combo_input($name, $selected_id, $sql, 'id', 'name',
	array(
		'order' => array('sort_order'),
		'spec_option' => $special_option===true ? _("All Part Types") : $special_option,
		'spec_id' => 0,
		'select_submit'=> $submit_on_change,
		
	) 
	
	);
}

function am_part_types_list_cells($label, $name, $selected_id=null, $submit_on_change=false, $special_option=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo am_part_types_list($name, $selected_id, $submit_on_change, $special_option);
	echo "</td>\n";
}

function am_part_types_list_row($label, $name, $selected_id=null, $submit_on_change=false, $special_option=false)
{
	echo "<tr><td class='label'>$label</td>";
	am_part_types_list_cells(null, $name, $selected_id, $submit_on_change, $special_option);
	echo "</tr>\n";
}

function am_mi_issue_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $jobcard_no)
{
	$sql = "SELECT id, reference FROM ".TB_PREF."am_jobcard_mi WHERE ".TB_PREF."am_jobcard_mi.jobcard_no = ".db_escape($jobcard_no)."";
	
	//display_error($sql);
	
 	return combo_input($name, $selected_id, $sql, 'id', 'reference',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function am_mi_issue_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $jobcard_no)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo am_mi_issue_list($name, $selected_id, $spec_opt, $submit_on_change, $jobcard_no);
	echo "</td>\n";
}

function am_mi_issue_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $jobcard_no)
{
	echo "<tr><td class='label'>$label</td>";
	am_mi_issue_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change, $jobcard_no);
	echo "</tr>\n";
}


function am_si_issue_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $jobcard_no)
{
	$sql = "SELECT id, reference FROM ".TB_PREF."am_jobcard_si WHERE ".TB_PREF."am_jobcard_si.jobcard_no = ".db_escape($jobcard_no)."";
 	return combo_input($name, $selected_id, $sql, 'id', 'reference',
 	array('order'=>'id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function am_si_issue_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $jobcard_no)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo am_si_issue_list($name, $selected_id, $spec_opt, $submit_on_change, $jobcard_no);
	echo "</td>\n";
}

function am_si_issue_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false, $jobcard_no)
{
	echo "<tr><td class='label'>$label</td>";
	am_si_issue_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change, $jobcard_no);
	echo "</tr>\n";
}


function si_issued_items_list($name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $opts=array(), $editkey = false, $type = "stock",
	$jobcard_no=false,$si_id=0)
{
	$sql = "SELECT s.stock_id, s.description, c.description, s.inactive, s.editable
			FROM ".TB_PREF."am_jobcard_si_items items ,".TB_PREF."am_jobcard_si si, ".TB_PREF."stock_master s,".TB_PREF."stock_category c WHERE s.category_id=c.category_id AND items.si_id=si.id AND s.stock_id=items.stock_id AND si.jobcard_no=".db_escape($jobcard_no)." AND si.id=".db_escape($si_id)."";
	if (isset($opts['fixed_asset']) && $opts['fixed_asset'])
		$sql .= " AND mb_flag='F'";
	else
		$sql .= " AND mb_flag!='F'";
	      
	if ($editkey)
		set_editor('item', $name, $editkey);
	$ret = combo_input($name, $selected_id, $sql, 's.stock_id', 's.description',
		array_merge(
		  array(
			'format' => '_format_stock_items',
			'spec_option' => $all_option===true ?  _("Select Item") : $all_option,
			'spec_id' => ALL_TEXT,
			'search_box' => true,
			'search' => array("s.stock_id", "c.description","s.description"),
			'search_submit' => get_company_pref('no_item_list')!=0 && (!isset($opts['fixed_asset']) || !$opts['fixed_asset']),
			'size'=>10,
			'select_submit'=> $submit_on_change,
			'category' => 2,
			'order' => array('c.description','s.stock_id'),
			'editlink' => $editkey ? add_edit_combo('item') : false,
			'editable' => false,
			'max' => 255
		  ), $opts), $type );
	return $ret;
}
			
		
function si_issued_items_list_cells($label, $name, $selected_id=null, 
	$all_option=false, $submit_on_change=false,$jobcard_no=false,$si_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo si_issued_items_list($name, $selected_id, $all_option, $submit_on_change,
		array(true, 'cells'=>true), false, "stock",$jobcard_no,$si_id);
		
}

//------------------------------------------------------
function jc_customer_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false, 
	$show_inactive=false, $editkey = false,$customer_id)
{

	global $SysPrefs;
	if (!empty($SysPrefs->prefs['shortname_name_in_list']))
		$sql = "SELECT debtor_no, debtor_ref, name, curr_code, inactive FROM ".TB_PREF."debtors_master ";
	else
        {	
		$sql = "SELECT debtor_no, debtor_ref, curr_code, inactive FROM ".TB_PREF."debtors_master ";
		if($customer_id != "")
		{
		$sql .= " WHERE debtor_no = ".db_escape($customer_id);
		}
		}
		//display_error($sql);
	$mode = get_company_pref('no_customer_list');

	if ($editkey)
		set_editor('customer', $name, $editkey);

	$ret = combo_input($name, $selected_id, $sql, 'debtor_no', 'debtor_ref',
	array(
	    'format' => '_format_add_curr',
	    'order' => array('debtor_ref'),
		'search_box' => $mode!=0,
		'type' => 1,
		'size' => 20,
        'search' => array("debtor_ref","name","tax_id"),        
		'spec_option' => $spec_option === true ? _("All Customers") : $spec_option,
		'spec_id' => ALL_TEXT,
		'select_submit'=> $submit_on_change,
		'async' => false,
		'sel_hint' => $mode ? _('Press Space tab to filter by name fragment; F2 - entry new customer') :
		_('Select customer'),
		'show_inactive' => $show_inactive,
		'editlink' => $editkey ? add_edit_combo('customer') : false
	), "customer" );
	return $ret;
}

function jc_customer_list_cells($label, $name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false,$customer_id)
{

	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td nowrap>";
	echo jc_customer_list($name, $selected_id, $all_option, $submit_on_change,
		$show_inactive, $editkey,$customer_id);
	echo "</td>\n";
}

function jc_customer_list_row($label, $name, $selected_id=null, $all_option = false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false,$customer_id)
{
	echo "<tr><td class='label'>$label</td><td nowrap>";
	echo jc_customer_list_cells($label,$name, $selected_id, $all_option, $submit_on_change,
		$show_inactive, $editkey,$customer_id);
	echo "</td>\n</tr>\n";
}


function jobcard_type_list_cells($label, $name, $selected=null, $submit_on_change=false)
{

	if ($label != null)
		label_cell($label);
	echo "<td>\n";
	$allocs = array( 
		ALL_TEXT=>_("All Types"),
		'1'=> _("Automotive JC Advance"),
		'2'=> _("Automotive JC Excess Receipt"),
		'3'=> _("Automotive JC Advance Return"),
	);
	echo array_selector($name, $selected, $allocs, array('select_submit'=> $submit_on_change));
	echo "</td>\n";
}

//AM service quottaion part type
function am_service_quotation_part_types_list_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $am_service_quotation_part_types;
	
	echo "<td>";
	echo array_selector($name, $selected_id, $am_service_quotation_part_types, 
		array( 
			'select_submit'=> $submit_on_change
		) );
	echo "</td>\n";
}

//Cash payment type
function sale_invoice_cash_payment_terms_list($name, $category, $selected_id=null, $submit_on_change=true, $prepayments=true,$spec_option=false)
{
	$sql = "SELECT terms_indicator, terms, inactive FROM ".TB_PREF."payment_terms 
	WHERE days_before_due=0 AND day_in_following_month=0";
	
		//display_error($sql);

	return combo_input($name, $selected_id, $sql, 'terms_indicator', 'terms',
		array(
		    'spec_option' => $spec_option === true ? _("Select a Payment Type") : $spec_option,
		    'spec_id' => ALL_TEXT, 
			'select_submit'=> $submit_on_change,
			'async' => true
		) );

}

function sale_invoice_cash_payment_terms_list_cells($label, $name, $category, $selected_id=null, $submit_on_change=true, $prepayments=true,$all_option=false)
{
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo sale_invoice_cash_payment_terms_list($name, $category, $selected_id, $submit_on_change, $prepayments,$all_option);

	echo "</td>\n";
}

//Dimensions based on locations
function location_wise_dimensions_list($name, $selected_id=null, $no_option=false, $showname=' ',
	$submit_on_change=false, $showclosed=false, $showtype=1, $all_dimensions=false,$loc_code=0)
{
	//$sql = "SELECT id, CONCAT(reference,'  ',name) as ref FROM ".TB_PREF."dimensions";
	
	$sql = "SELECT dim.id,CONCAT(dim.reference,'  ',dim.name) as ref FROM  ".TB_PREF."location_dimension_cashaccount locdim, 
    ".TB_PREF."dimensions dim 
    WHERE locdim.dimension_id=dim.id 
    AND locdim.loc_code=".db_escape($loc_code)."";
	
	
	if(!$all_dimensions)
	{
		$user_dms=$_SESSION["wa_current_user"]->user_dimensions;
		$sql .=" AND  FIND_IN_SET(dim.id,'$user_dms')";
	}
	
	$sql .=" GROUP BY dim.id";
	
	//display_error($sql);
	
	$options = array(
		'order' => 'reference',
		'spec_option'=>$no_option ? $showname : false,
		'spec_id' => 0,
		'select_submit'=> $submit_on_change,
		'async' => false,
	);

	if (!$showclosed)
		$options['where'][] = "closed=0";
	if($showtype)
		$options['where'][] = "type_=".db_escape($showtype);

	return combo_input($name, $selected_id, $sql, 'id', 'ref', $options);
}

function location_wise_dimensions_list_cells($label, $name, $selected_id=null, $no_option=false, $showname=null,
	$showclosed=false, $showtype=0, $submit_on_change=false,$user_dms=false,$loc_code=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo location_wise_dimensions_list($name, $selected_id, $no_option, $showname, $submit_on_change, $showclosed, $showtype, $user_dms,$loc_code);
	echo "</td>\n";
}

function location_wise_dimensions_list_row($label, $name, $selected_id=null, $no_option=false, $showname=null,
	$showclosed=false, $showtype=0, $submit_on_change=false,$user_dms=false,$loc_code=0)
{
	echo "<tr><td class='label'>$label</td>";
	location_wise_dimensions_list_cells(null, $name, $selected_id, $no_option, $showname,
		$showclosed, $showtype, $submit_on_change,$user_dms,$loc_code);
	echo "</tr>\n";
}



function am_tariff_proposal_workdesc_list($name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	$sql = "SELECT id, work_description,inactive FROM ".TB_PREF."am_tariff_proposal";
	return combo_input($name, $selected_id, $sql, 'id', 'work_description',
		array(
			'spec_option' => $all_option === true ? _("Select Work Description") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}


function am_tariff_proposal_workdesc_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	if ($label != null)
	echo "<td>$label</td>\n";
	echo "<td>";
	echo am_tariff_proposal_workdesc_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function am_tariff_proposal_workdesc_list_row($label, $name, $selected_id=null, $all_option=false, 
$submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	am_tariff_proposal_workdesc_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}



// LOFT Document Types list
function loft_document_types_list($name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	$sql = "SELECT id, document_type, inactive FROM ".TB_PREF."loft_document_types 
	WHERE 1=1";

	return combo_input($name, $selected_id, $sql, 'id', 'document_type',
		array(
			'order' => array('document_type'),
			'spec_option' => $all_option === true ? _("All LOFT Document Types") : $all_option,
			'spec_id' => 0,
			'select_submit'=> $submit_on_change
		) );
}

function loft_document_types_list_cells($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo loft_document_types_list($name, $selected_id, $all_option, $submit_on_change);
	echo "</td>\n";
}

function loft_document_types_list_row($label, $name, $selected_id=null, 
$all_option=false, $submit_on_change=false)
{
	echo "<tr><td class='label'>$label</td>";
	loft_document_types_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
	echo "</tr>\n";
}


//---------------------------------//
//Equipment Types
function multi_ser_tool_types_list_cells($label, $name, $selected_id=null, $all_option=false, 
$submit_on_change=false, $all=false, $editkey = false)
{
	if ($label != null)
	{
		echo "<td class='label'>$label</td><td>\n";
		echo multi_ser_tool_types_list($name, $selected_id, $all_option, $submit_on_change, 
		$all, $editkey);
		echo "</td>\n";
	}
else
{
		echo "<td class='label'>\n";
	echo multi_ser_tool_types_list($name, $selected_id, $all_option, $submit_on_change, 
		$all, $editkey);
		echo "</td>\n";
}	
		
}
function multi_ser_tool_types_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false,$all=false, $editkey = false)
{
    global $Ajax;

	$sql ="SELECT  id,tool_type FROM 0_ser_tool_types";
	
	$ret = combo_input($name, $selected_id, $sql, 'id', 'tool_type',
	array(
		'format' => '_format_add_curr',
	    'order' => array('id'),
		'multi' => true,	
		'select_submit'=> $submit_on_change,
		'async' => true,
		'sel_hint' => $mode ? _('Press Space tab to filter by name fragment') :
	_('Select Tools'),
		'show_inactive'=>$all
		));
	if ($editkey)
		$ret .= add_frequency_combo('tool_type');		
	return $ret;
}



function select_no_or_yes_list_row($label, $name, $selected_id=null, $enabled=true)
{
	global $select_no_or_yes;

	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	echo array_selector($name, $selected_id, $select_no_or_yes, 
		array( 
			'select_submit'=> true, 
			'disabled' => !$enabled));
	echo "</td></tr>\n";
}



//Sales Invoice Print Options List ----------------------

function salesinv_options_cells($label, $name, $selected_id=null, $submit_on_change=false)
{
	global $inv_print;
    	
	 if ($label != null)
		echo "<td>$label</td>\n";
     
	echo "<td>";
	echo array_selector($name, $selected_id, $inv_print, 
		array( 
			'select_submit'=> $submit_on_change, 
			'enabled' => !$enabled) );
	
	echo"</td>";
	
}

function stock_depreciable_fa_list_cells1($label, $name, $selected_id=null,
	$all_option=false, $submit_on_change=false)
{

	// Check if a fixed asset has been bought....
	$where_opts[] = "stock_id IN
    	( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE type=".ST_SUPPRECEIVE." AND qty!=0 )";
	// ...but has not been disposed or sold already.
	$where_opts[] = "stock_id NOT IN
    	( SELECT stock_id FROM ".TB_PREF."stock_moves WHERE (type=".ST_CUSTDELIVERY." OR type=".ST_INVADJUST.") AND qty!=0 )";

	$year = get_current_fiscalyear();
	$begin = date2sql(add_months(sql2date($year['begin']), -1));
	$end = date2sql(add_months(sql2date($year['end']), -1));

	// check if current fiscal year
	$where_opts[] = "depreciation_date <= '".$end."'";
	$where_opts[] = "depreciation_date >= '".$begin."'";

	$where_opts[] = "material_cost > 0";
	$where_opts[] = "mb_flag='F'";
	
	echo "<tr>";
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td>";

	//if ($label != null)
	//echo "<tr><td class='label'>$label</td></tr>";
		//echo "<td>$label</td>\n";
		
	echo stock_items_list($name, $selected_id, $all_option, $submit_on_change,
		 array('fixed_asset' => true, 'where' => $where_opts, 'cells'=>true));
	
	
		 
	echo "</td></tr>"; 
}

//---------------------------------------------------------------------------------------venkat
function item_category_list_serach($name, $selected_id=null, $spec_option=false, $submit_on_change=false, 
	$show_inactive=false, $editkey = false,$fixed_asset=false)
{



$where_opts = array();
	if ($fixed_asset)
		$where_opts[0] = "dflt_mb_flag='F'";
	else
		$where_opts[0] = "dflt_mb_flag!='F'";
		
	global $SysPrefs;
$sql = "SELECT category_id, description, inactive FROM ".TB_PREF."stock_category";
	if ($editkey)
		set_editor('item_category', $name, $editkey);
	$ret = combo_input($name, $selected_id, $sql, 'category_id', 'description',
	array( 'order' => array('category_id'),
		 'search_box' => $mode!=0,
		 'type' => 1,
		 'size' => 20,
         'search' => array("category_id"),        
		 'spec_option' => $spec_option === true ? _("All Item Categories") : $spec_option,
		 'spec_id' => ALL_TEXT,
		 'select_submit'=> $submit_on_change,
		 'async' => true,
		 'sel_hint' => $mode ? _('Press Space tab to filter by name fragment; F2 - entry new jobcard') :
		 _('Select Item Categories'),
		 'show_inactive' => $show_inactive,
		 'editlink' => $editkey ? add_edit_combo('item_category') : false,
		 'where' => $where_opts,
	), "item_category" ); 
	 return $ret;
}

function item_category_list_serach_cells($label, $name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false,$fixed_asset=false)
{
	if ($label != null)
		echo "<td class='label'>$label</td>\n";
	echo "<td nowrap>";
	echo item_category_list_serach($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey,$fixed_asset);
	echo "</td>\n";
}


function item_category_search_list_row($label, $name, $selected_id=null, $all_option=false, 
	$submit_on_change=false, $show_inactive=false, $editkey = false,$fixed_asset=false)
{

	echo "<tr><td class='label'>$label</td><td nowrap>";
	echo item_category_list_serach_cells($name, $selected_id, $all_option, $submit_on_change,
		$show_inactive, $editkey,$fixed_asset);
	echo "</td>\n</tr>\n";
}


function refline_list_cells($label, $name, $type, $selected_id=null, $spec_option=false)
{
	
	echo "<td>";

	echo refline_list($name, $type, $selected_id, $spec_option);
	echo "</td>\n";
}


//------------------------------------------------------------------

function service_tool_type_tool_serial_no_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$tool_type_id=false,$tool_id=false,$status=0, $customer_id=0)

{
	//ravi
	
	$status=str_replace("'","",$status);
	
	
	
	$sql = "SELECT tcs.tool_serial_no, tcs.tool_serial_no FROM 0_ser_tool_current_status tcs,0_ser_tools tc 
	WHERE tcs.tool_id = tc.id ";
	
	if($tool_type_id)
	{
		$sql .=" AND tc.tool_type_id=".db_escape($tool_type_id)."";
	}
	
	if($tool_id)
	{
		$sql .=" AND tcs.tool_id=".db_escape($tool_id)."";
	}
	if($status!=0){
	$sql .=" AND tcs.current_status in ($status)";  //ravi
	}
	if($customer_id!=0){
	$sql .=" AND tcs.customer_id=".db_escape($customer_id)."";
	}
	
	//	display_error($sql);
				
	return combo_input($name, $selected_id, $sql, 'tool_serial_no', 'tool_serial_no',
 	array('order'=>'tool_serial_no',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function service_tool_type_tool_serial_no_list_cells($label, $name, $selected_id=null, $spec_opt=false,
 $submit_on_change=false,$tool_type_id=false,$tool_id=false,$status=0, $customer_id=0)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo service_tool_type_tool_serial_no_list($name, $selected_id, $spec_opt, $submit_on_change,$tool_type_id,$tool_id,$status,$customer_id);
	echo "</td>\n";
}

function service_tool_type_tool_serial_no_list_row($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false,$tool_type_id=false,$tool_id=false,$status=0, $customer_id=0)
{
 
	echo "<tr><td class='label'>$label</td>";
	service_tool_type_tool_serial_no_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$tool_type_id,$tool_id,$status,$customer_id);
	echo "</tr>\n";
}

//-----------------------------------------------------
//Lifting Accessories Fixed Assets -- Rajesh -- 30-07-2024
function fa_lifting_accessories_list($name, $selected_id=null, $spec_opt=false, $submit_on_change=false)
{
	$sql = "SELECT stock_id, stock_id FROM 0_stock_master WHERE mb_flag='F' and category_id=47  and !inactive";
				
	return combo_input($name, $selected_id, $sql, 'stock_id', 'stock_id',
 	array('order'=>'stock_id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true
 	));
}

function fa_lifting_accessories_list_cells($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo fa_lifting_accessories_list($name, $selected_id, $spec_opt, $submit_on_change);
	echo "</td>\n";
}

function fa_lifting_accessories_list_row($label, $name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false)
{
 
	echo "<tr><td class='label'>$label</td>";
	fa_lifting_accessories_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
	echo "</tr>\n";
}

//sameer
//---------------------------------------------------------------------------------


function vehicle_fixed_asset_service_tool_list($name, $selected_id=null, $spec_opt=false, 
$submit_on_change=false, $editkey = false)
{
	$sql = "SELECT stock_id, stock_id , inactive FROM 0_stock_master WHERE category_id IN (6,50,51,52)";
	
	if ($editkey)
		set_editor('vehicle_search', $name, $editkey);
	
	$ret = combo_input($name, $selected_id, $sql, 'id', 'description',
 	array('order'=>'stock_id',
		'spec_option' => $spec_opt,
		'spec_id' => -1,
 		'select_submit'=> $submit_on_change,
 		'async' => true,
		'sel_hint' => _('Select Vehicle'),
		'editlink' => $editkey ? add_edit_combo('vehicle_search') : false
	), "vehicle_search" );
	return $ret;
}

function vehicle_fixed_asset_service_tool_list_cells($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$editkey = false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo vehicle_fixed_asset_service_tool_list($name, $selected_id, $spec_opt, $submit_on_change,$editkey);
	echo "</td>\n";
}

function vehicle_fixed_asset_service_tool_list_row($label, $name, $selected_id=null, $spec_opt=false, $submit_on_change=false,$editkey = false)
{
 
	echo "<tr><td class='label'>$label</td>";
	vehicle_fixed_asset_service_tool_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change,$editkey);
	echo "</tr>\n";
}

