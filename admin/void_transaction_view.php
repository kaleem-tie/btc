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
$page_security = 'SA_VOIDTRANSACTION_INQUIRY';
$path_to_root = "..";
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/admin/db/transactions_db.inc");

include_once($path_to_root . "/admin/db/voiding_db.inc");
$js = "";
if ($use_date_picker)
	$js .= get_js_date_picker();
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
	
page(_($help_context = "Void Transaction Inquiry Details"), false, false, "", $js);

simple_page_mode(true);
//----------------------------------------------------------------------------------------

$Ajax->activate('void_tbl');
if (get_post('SearchDetails')) 
{
	
	$Ajax->activate('void_tbl');
} 


function voiding_controls()
{
	global $selected_id;

	$not_implemented =  array(ST_PURCHORDER, ST_SALESORDER, ST_SALESQUOTE, ST_COSTUPDATE);

	start_form();

    start_table(TABLESTYLE_NOBORDER);
	start_row();

	systypes_list_cells(_("Type:"), 'filterType', null, true, $not_implemented);
	if (list_updated('filterType'))
		$selected_id = -1;

	if (!isset($_POST['FromTransNo']))
        $_POST['FromTransNo'] = "1";
    if (!isset($_POST['ToTransNo']))
        $_POST['ToTransNo'] = "999999";

    ref_cells(_("from #:"), 'FromTransNo');

    ref_cells(_("to #:"), 'ToTransNo');

    submit_cells('ProcessSearch', _("Search"), '', '', 'default');
		
	end_row();
    end_table(1);
    
	
	$trans_ref = false;
	$sql = get_sql_for_view_transactions_details($_POST['filterType'], $_POST['FromTransNo'], $_POST['ToTransNo'], $trans_ref);
	if ($sql == "")
		return;

	$cols = array(
		_("#") => array('insert'=>true, 'fun'=>'view_link'), 
		_("Reference") => array('fun'=>'ref_view'), 
		_("Date") => array('type'=>'date', 'fun'=>'date_view'),
		_("GL") => array('insert'=>true, 'fun'=>'gl_view')
	);

	$table =& new_db_pager('transactions', $sql, $cols);
	$table->width = "40%";
	display_db_pager($table);
   

	end_form();
}


//------------------------------------------------------------------------------------------------

function view_link($trans)
{
	
	if (!isset($trans['type']))
		$trans['type'] = $_POST['filterType'];
	return get_trans_view_str($trans["type"], $trans["trans_no"]);
}



function gl_view($row)
{
	if (!isset($row['type']))
		$row['type'] = $_POST['filterType'];
	return get_gl_view_str($row["type"], $row["trans_no"]);
}

function date_view($row)
{
	return $row['trans_date'];
}

function ref_view($row)
{
	return $row['ref'];
}


//----------------------------------------------------------------------------------------------------

voiding_controls();


end_form();
end_page();

?>