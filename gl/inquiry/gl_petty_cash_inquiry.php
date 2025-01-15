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
$page_security = 'SA_PETTY_CASH_INQ_VIEW';
$path_to_root="../..";
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Petty Cash Inquiry"), false, false, "", $js);

//---------------------------------------------------------------------------------------------
function trans_view($trans)
{
	
	return pager_link( _("View"),
			"/gl/view/gl_petty_cash_view.php?petty_cash_ref=".$trans["our_ref_no"]);
}


function prt_link($row)
{
	
	$petty_cash_trans_no = get_gl_petty_cash_entry_trans_no_by_our_ref_no($row["our_ref_no"]);
	
	return print_document_link($petty_cash_trans_no, _("Print"), true, ST_PETTY_CASH_REPORT, ICON_PRINT);
}



//-----------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('SearchOrders')) 
{
	$Ajax->activate('orders_tbl');
} elseif (get_post('_order_number_changed')) 
{
	$disable = get_post('order_number') !== '';


	$Ajax->activate('orders_tbl');
}
//---------------------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
cash_accounts_list_row(_("Petty Cash:") , 'bank_account', null, true,false); 
end_row();

start_row();
ref_cells(_("Our Ref No.#:"), 'ref_number', '',null, '', true);
date_cells(_("From:"), 'FromDate', '', null, -user_transaction_days());
date_cells(_("To:"), 'ToDate');
submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');
end_row();
end_table(1);

//---------------------------------------------------------------------------------------------


$sql = get_sql_for_petty_cash_inquiry(get_post('FromDate'), get_post('ToDate'),get_post('ref_number'),get_post('bank_account'));

$cols = array(
		_("#") => array('fun'=>'trans_view', 'ord'=>'', 'align'=>'center'), 
		_("Date") => array('name'=>'ord_date', 'type'=>'date', 'ord'=>'desc'),
		_("Petty Cash Account"),
		_("Our Ref No."),
		array('insert'=>true, 'fun'=>'prt_link')
);


//---------------------------------------------------------------------------------------------------

$table =& new_db_pager('orders_tbl', $sql, $cols);

$table->width = "80%";

display_db_pager($table);

end_form();
end_page();
