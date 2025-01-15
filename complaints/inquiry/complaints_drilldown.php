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
$page_security = 'SA_COMPLAINT_INQUIRY';
$path_to_root="../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

$js = "";
if (user_use_date_picker())
	$js = get_js_date_picker();

page(_($help_context = "Complaints Drilldown"), false, false, "", $js);
//----------------------------------------------------------------------------------------------------
function get_all_complaint_items()
{
	$sql = "SELECT c.*,count(*) as complaint_count,s.description FROM ".TB_PREF."proj_customer_complaint c ,".TB_PREF."stock_master s WHERE c.stock_id=s.stock_id AND c.stock_id!='' GROUP BY stock_id";
	
	return $result = db_query($sql, "could not Project");
}
//----------------------------------------------------------------------------------------------------
	
function display_complaints_sheet()
{
	$comp_items=get_all_complaint_items();
	div_start('balance_tbl');
	
	start_table(TABLESTYLE, "width='50%'");
	$th = array (_('Item'),_('No of Complaints'));
    table_header($th);
	while ($result = db_fetch($comp_items))
	{
		global $path_to_root;
		 $url = "<a href='$path_to_root/complaints/inquiry/complaints_inquiry_drilldown.php?StockId=" . $result['stock_id'] ."'>" . $result['description']."</a>";	
		 
         alt_table_row_color($k);
		 label_cell($url);
		 label_cell($result['complaint_count'], "align='center'");
		 end_row();
		hidden('StockId',$result['stock_id']);
		
	}
	end_table(); // outer table
	div_end();
}
//----------------------------------------------------------------------------------------------------
start_form(true);
display_complaints_sheet();
end_form();
end_page(false, true);

