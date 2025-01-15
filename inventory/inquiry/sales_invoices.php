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
$page_security = 'SA_SALESINV';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/includes/ui.inc");
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

	$_SESSION['page_title'] = _($help_context = "Sales");


page($_SESSION['page_title'], isset($_GET['stock_id']), false, "", $js);
//------------------------------------------------------------------------------------------------

if(get_post('ShowMoves'))
{
	$Ajax->activate('doc_tbl');
}

if (isset($_GET['stock_id']))
{
	$_POST['stock_id'] = $_GET['stock_id'];
}

start_form();

hidden('fixed_asset');

if (!isset($_POST['stock_id']))
	$_POST['stock_id'] = get_global_stock_item();



start_table(TABLESTYLE_NOBORDER );
start_row();
date_cells(_("From:"), 'AfterDate', '', null, -user_transaction_days());
date_cells(_("To:"), 'BeforeDate');

submit_cells('ShowMoves',_("Show"),'',_('Refresh Inquiry'), 'default');
end_row();
end_table();
end_form();

// set_global_stock_item($_POST['stock_id']);

$before_date = date2sql($_POST['BeforeDate']);
$after_date = date2sql($_POST['AfterDate']);
$display_location = !$_POST['StockLocation'];
$result = get_sql_for_sales_invoiced_items($_POST['stock_id'], $_POST['AfterDate'],$_POST['BeforeDate']);

div_start('doc_tbl');
start_table(TABLESTYLE , "width='100%'");
$th = array(_("Br"), _("Type"), _("Voucher No"), _("Voucher Date"), _("Customer Name"), _("Qty"), _("Currency"), _("Rate"), _("Net Amt"), _("Cost Rate"), _("Net Cost"), _("Customer Code"));

table_header($th);
$k = 0; //row colour counter
$dec = get_qty_dec($_POST['stock_id']);
$tot_net=0;
while ($myrow = db_fetch($result))
{
		
	$type_name = $systypes_array[$myrow["type"]];
	alt_table_row_color($k);
	label_cell($myrow["from_stk_loc"]);
	label_cell($type_name);
	label_cell(get_trans_view_str($myrow["type"], $myrow["trans_no"], $myrow["reference"]));
	label_cell(sql2date($myrow["tran_date"]),"align=center");
	label_cell($myrow["name"]);
	qty_cell($myrow["quantity"], false, $dec);
	label_cell($myrow["curr_code"],"align=center");
	amount_cell($myrow["unit_price"],$myrow["unit_price"]<$myrow['standard_cost']?true:false);
	amount_cell($myrow["quantity"]*$myrow["unit_price"]);
	if($_SESSION["wa_current_user"]->show_cost_rate)
	{
	amount_cell($myrow['standard_cost']);
	amount_cell($myrow["quantity"]*$myrow['standard_cost']);
	}
	else
	{
		label_cell('#',"align=center");
		label_cell('#',"align=center");
	}
	label_cell($myrow["debtor_no"],"align=center");
	$tot_net +=$myrow["quantity"]*$myrow["unit_price"];
}
end_row();
label_cell("");
label_cell("");
label_cell("");
label_cell("");
label_cell("");
label_cell("");
label_cell("");
label_cell("");
amount_cell($tot_net);
label_cell("");
label_cell("");
label_cell("");


end_table(1);
div_end();
end_page();

