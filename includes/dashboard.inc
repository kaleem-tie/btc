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
//$colors = array('#008cc9','#4db625','#ef5500','#eef100','#05c6e6', '#5ee66a'); // Current in Use 	 
$colors = Chart::$palette;
$align = $_SESSION['language']->dir == 'rtl' ? 'right' : 'left';
$style = "<style>
div.square {
	display: inline-block;
	width: 200px;
	height:40px;
	padding: 3px 5px 0 5px;
	font-size:12px;
	font-weight:normal;
	text-align: $align;
	line-height: 1.2;
	color:#fff;
	border-radius:6px;
}

div.square1 {
	background:{$colors[1]};
}
div.square2 {
	background:{$colors[2]};
}

.span1 {
	width:200px;
	padding:0;
	margin:0;
	font-size:16px;
	font-weight:bold;
	text-align: center;
}
</style>";

function dashboard($sel_app)
{
	global  $style;
	if (is_object($sel_app) && !$_SESSION["wa_current_user"]->check_application_access($selected_app))
		return;
	echo $style;
	echo "<script> 
	function chart_update(el, id)
	{
		var xhr = XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
		xhr.onreadystatechange=function()
		{
			if (xhr.readyState==4 && xhr.status==200)
			{
				var xxx = document.createElement('div');
				xxx.innerHTML = xhr.responseText;
				document.getElementById(id).innerHTML = xxx.querySelector('#'+id).innerHTML;
			}
		}
		xhr.open('POST', location.pathname, true);
		xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		xhr.send('id='+id+'&'+el.id+'='+el.value+'&sel_app=$sel_app');		
	}
	</script>";
	//check_for_overdue_recurrent_invoices();
		
	if ($sel_app == "orders")
		display_customer_topten();
	elseif ($sel_app == "AP")
		display_supplier_topten();
	elseif ($sel_app == "stock")
		display_stock_topten();
	elseif ($sel_app == "manuf")
		display_stock_topten(1);
	elseif ($sel_app == "assets")
		display_stock_topten(2);
	elseif ($sel_app == "proj")
		display_dimension_topten();
	elseif ($sel_app == "GL")
		display_gl_info();
	//else	
		//display_all();
}

function display_title($title, $id=false, $type = false, $per = '', $num = false)
{
	br();
	start_form();
	echo "<div>";
	echo "<span class='headingtext'>$title</span>";
	if ($id)
	{
		/*
		global $Ajax;
		echo "<span style='margin-left:10px;text-align:right;'>";
		$options = array('select_submit' => true ); 
		if (!empty($per))
		{
			$name = "per_$id";
			$sel1 = array(3 => "3", 4 => "4", 5 => "5", 6 => "6", 7 => "7", 8=> "8");
			echo array_selector($name, $per, $sel1, $options);
			echo " $per\n";
		}
		else
		{
			$name = "select_$id";
			$sel = array(1 => _("Vertical bars"), 2 => _("Horizontal bars"), 3 => _("Dots"), 4 => _("Lines"), 5 => _("Pie"), 6 => _("Donut"), 7 => _("Half Donut"), 8 => _("Splines"), 9 => _("Tables"));
			echo array_selector($name, $type, $sel, $options);
		}
		if (list_updated($name))
			$Ajax->activate($id);
		echo "</span>";
		*/
		echo "<span style='margin-left:10px;text-align:right;'>";
		if (!empty($per))
		{
			$sel1 = array(3 => "3", 4 => "4", 5 => "5", 6 => "6", 7 => "7", 8=> "8");
			echo "<select id='per_$id' name='per_$id' style='border:1px solid #bbb;color:#555;' onchange='chart_update(this, \"$id\");'>";
			foreach ($sel1 as $i => $s)
			{
				echo "<option value='$i'";
				if ($num && $i == $num) 
					echo " selected";
				echo ">$s</option>";
			}
			echo "</select> $per\n";
		}
		else
		{
			$sel = array(1 => _("Vertical bars"), 2 => _("Horizontal bars"), 3 => _("Dots"), 4 => _("Lines"), 5 => _("Pie"), 6 => _("Donut"), 7 => _("Half Donut"), 8 => _("Splines"), 9 => _("Tables"));
			echo "<select id='select_$id' name='select_$id' style='border:1px solid #bbb;color:#555;' onchange='chart_update(this, \"$id\");'>";
			foreach ($sel as $i => $s)
			{
				echo "<option value='$i'";
				if ($type && $i == $type) 
					echo " selected";
				echo ">$s</option>";
			}
			echo "</select>\n";
		}
		echo "</span>";
	}
	echo "</div>\n";	
	end_form();
	br();
}	

function table($width='100%', $twidth=false)
{
	$tstyle = ($twidth != false) ? "style='width:$twidth;'" : "";
	echo "<table width='$width'><tr valign=top><td align='center' $tstyle>";
}

function td($width=false)
{
	$style = ($width != false) ? "style='width:$width;'" : "";
	echo "</td><td align='center' $style>\n";
}	

function table_end()
{
	echo "</td></tr></table>\n";
}

function headers($labels)
{
	echo "<tr>\n";
	foreach ($labels as $label)
		echo "<td class='tableheader'>$label</td>\n";
	echo "</tr>\n";
}

function display_customer_topten()
{
	$today = calc_today();
	 	 
	table('100%');
	if(user_check_access('SA_SDB_CUSTOMERS_COUNT'))
	{
	td('25%');
	echo "<div class='square square1'>"._('Customers')."<p class='span1'>";
	echo get_num_customers()."</p></div>";
	}
	if(user_check_access('SA_SDB_BRANCHES_COUNT'))
	{
	td('25%');
	echo "<div class='square square1'>"._('Branches')."<p class='span1'>";
	echo get_num_branches()."</p></div>";
	}
	if(user_check_access('SA_SDB_SALESMAN_COUNT'))
	{
	td('25%');
	echo "<div class='square square1'>"._('Salesmen')."<p class='span1'>";
	echo get_num_salesmen()."</p></div>";
	}
	if(user_check_access('SA_SDB_ODINVOICE_COUNT'))
	{
	td('25%');
	echo "<div class='square square2'>"._('Overdue Invoices')."<p class='span1'>";
	echo get_num_overdue_sales()."</p></div>";
	}
	table_end();
	
	
	//PDC Recall Pending transactions -- Rajesh -- 31-07-2024
	table('100%', '100%');
	get_pending_pdc_recall_transactions_dashboard("PDC Recall Alerts",$today);
	table_end();
	echo "<hr>";
	
	
	//ravi
	if(user_check_access('SA_SDB_SALES_ENQUIRIES'))
	{
	$pg = new chart('horizontalBar', 'ST1');
	if (isset($_POST['select_ST1']))
		$pg->type = $_POST['select_ST1'];
	$today = calc_today();
	table('100%', '50%');
	$title = sales_transactions($today, ST_INSSALESENQ, 66, $pg);
	td('50%');
	source_graphic_for_totals($today, $title[0], _("Month"), $pg);
	table_end();
	}
	if(user_check_access('SA_SDB_SALES_QUOTATIONS'))
	{
	$pg = new chart('horizontalBar', 'ST2');
	if (isset($_POST['select_ST2']))
		$pg->type = $_POST['select_ST2'];
	table('100%', '50%');
	$title = sales_transactions($today, ST_SALESQUOTE, 66, $pg);
	td('50%');
	source_graphic_for_totals($today, $title[0], _("Month"), $pg);
	table_end();
	}
	if(user_check_access('SA_SDB_SALES_ORDERS'))
	{
	$pg = new chart('horizontalBar', 'ST3');
	if (isset($_POST['select_ST3']))
		$pg->type = $_POST['select_ST3'];
		table('100%', '50%');
	$title = sales_transactions($today, ST_SALESORDER, 66, $pg);
	td('50%');
	source_graphic_for_totals($today, $title[0], _("Month"), $pg);
	table_end();
	}
	if(user_check_access('SA_SDB_SALES_DISPATCHES'))
	{
	$pg = new chart('horizontalBar', 'ST4');
	if (isset($_POST['select_ST4']))
		$pg->type = $_POST['select_ST4'];
	table('100%', '50%');
	$title = sales_transactions($today, ST_CUSTDELIVERY, 66, $pg);
	td('50%');
	source_graphic_for_totals($today, $title[0], _("Month"), $pg);
	table_end();
	}
	if(user_check_access('SA_SDB_SALES_INVOICES'))
	{
	$pg = new chart('horizontalBar', 'ST5');
	if (isset($_POST['select_ST5']))
		$pg->type = $_POST['select_ST5'];
	table('100%', '50%');
	$title = sales_transactions($today, ST_SALESINVOICE, 66, $pg);
	td('50%');
	source_graphic_for_totals($today, $title[0], _("Month"), $pg);
	table_end();
	}
	if(user_check_access('SA_SDB_SALES_RETURNS'))
	{
	$pg = new chart('horizontalBar', 'ST6');
	if (isset($_POST['select_ST6']))
		$pg->type = $_POST['select_ST6'];
	table('100%', '50%');
	$title = sales_transactions($today, ST_CUSTCREDIT, 66, $pg);
	td('50%');
	source_graphic_for_totals($today, $title[0], _("Month"), $pg);
	table_end();
	}
	
	//end
	if(user_check_access('SA_SDB_SALES_TOP10_CUSTOMERS') || user_check_access('SA_SDB_SALES_CUSTOMER_AGING') || user_check_access('SA_SDB_SALES_TOTAL') || user_check_access('SA_SDB_SALES_AGING_TOTAL'))
	{
	$pg = new chart('horizontalBar', 'c1');
	if (isset($_POST['select_c1']))
		$pg->type = $_POST['select_c1'];
	$today = calc_today();
	table('100%', '50%');
	if(user_check_access('SA_SDB_SALES_TOP10_CUSTOMERS'))
	$title = customer_top($today, 10, 66, $pg);
	$total = customer_aging($today, 66);
	td('50%');
	source_graphic($today, $title[0], _("Customer"), $pg);
	br(3);
	echo "<div class='square square1'>"._('Sales Total')."<p class='span1'>";
	echo number_format2($title[1])."</p></div>";
	br(3);
	echo "<div class='square square1'>"._('Aging Total')."<p class='span1'>";
	echo number_format2($total)."</p></div>";
	table_end();
	
  }
  
    table('100%', '100%');
	if(user_check_access('SA_SDB_SALES_OD_INVOICE_LIST'))
	{
	customer_trans($today);
	}
	if(user_check_access('SA_SDB_SALES_UPCOMING_DISPATCHES'))
	{
    sales_outstanding_upcoming_item_deliveries($today);
	}
	table_end();
}

function display_supplier_topten()
{
	table('100%');
	if(user_check_access('SA_PDB_SUPPLIERS_COUNT'))
	{
	td('25%');
	echo "<div class='square square2'>"._('Suppliers')."<p class='span1'>";
	echo get_num_suppliers()."</p></div>";
	}
	if(user_check_access('SA_PDB_PURCH_ORDERS_COUNT'))
	{
	td('25%');
	echo "<div class='square square2'>"._('New Orders')."<p class='span1'>";
	echo get_new_purch_orders()."</p></div>";
	}
	if(user_check_access('SA_PDB_SUPPLIER_INVOICES_COUNT'))
	{
	td('25%');
	echo "<div class='square square2'>"._('Invoices')."<p class='span1'>";
	echo get_num_supp_invoices()."</p></div>";
	}
	if(user_check_access('SA_PDB_SUPPLIER_ODINVOICE_COUNT'))
	{
	td('25%');
	echo "<div class='square square2'>"._('Overdue Invoices')."<p class='span1'>";
	echo get_num_overdue_purch()."</p></div>";
	}
	table_end();
	
	//ravi
	if(user_check_access('SA_PDB_PENDING_PO_APPROVALS'))
	{
	table('100%', '50%');
	pending_po_approvals();
	table_end();
	}
	

	if(user_check_access('SA_PDB_PURCHASES_ENQUIRIES'))
	{
	$pg = new chart('horizontalBar', 'PT1');
	if (isset($_POST['select_PT1']))
		$pg->type = $_POST['select_PT1'];
	$today = calc_today();
	table('100%', '50%');
	$title = purchase_transactions($today, ST_PURCHENQ, 66, $pg);
	td('50%');
	source_graphic_for_totals($today, $title[0], _("Month"), $pg);
	table_end();
	}
	
	if(user_check_access('SA_PDB_PURCHASES_QUOTATIONS'))
	{
	$pg = new chart('horizontalBar', 'PT2');
	if (isset($_POST['select_PT2']))
		$pg->type = $_POST['select_PT2'];
	table('100%', '50%');
	$title = purchase_transactions($today, ST_PURCHQUOTE, 66, $pg);
	td('50%');
	source_graphic_for_totals($today, $title[0], _("Month"), $pg);
	table_end();
	}
	
	
	if(user_check_access('SA_PDB_PURCHASES_ORDERS'))
	{
	$pg = new chart('horizontalBar', 'PT3');
	if (isset($_POST['select_PT3']))
		$pg->type = $_POST['select_PT3'];
	table('100%', '50%');
	$title = purchase_transactions($today, ST_PURCHORDER, 66, $pg);
	td('50%');
	source_graphic_for_totals($today, $title[0], _("Month"), $pg);
	table_end();
	}
	
	if(user_check_access('SA_PDB_PURCHASES_DISPATCHES'))
	{
	$pg = new chart('horizontalBar', 'PT4');
	if (isset($_POST['select_PT4']))
		$pg->type = $_POST['select_PT4'];
	table('100%', '50%');
	$title = purchase_transactions($today, ST_SUPPRECEIVE, 66, $pg);
	td('50%');
	source_graphic_for_totals($today, $title[0], _("Month"), $pg);
	table_end();
	}
	
	if(user_check_access('SA_PDB_PURCHASES_INVOICES'))
	{
	$pg = new chart('horizontalBar', 'PT5');
	if (isset($_POST['select_PT5']))
		$pg->type = $_POST['select_PT5'];
	table('100%', '50%');
	$title = purchase_transactions($today, ST_SUPPINVOICE, 66, $pg);
	td('50%');
	source_graphic_for_totals($today, $title[0], _("Month"), $pg);
	table_end();
	}
	
	if(user_check_access('SA_PDB_PURCHASES_RETURNS'))
	{
	$pg = new chart('horizontalBar', 'PT6');
	if (isset($_POST['select_PT6']))
		$pg->type = $_POST['select_PT6'];
	table('100%', '50%');
	$title = purchase_transactions($today, ST_SUPPCREDIT, 66, $pg);
	td('50%');
	source_graphic_for_totals($today, $title[0], _("Month"), $pg);
	table_end();
	}
	
	
	
	if(user_check_access('SA_PDB_PURCHASES_TOP10_SUPPLIERS') || user_check_access('SA_PDB_SUPPLIERS_AGING') || user_check_access('SA_PDB_PURCHASES_TOTAL') || user_check_access('SA_PDB_PURCHASES_AGING_TOTAL'))
	{
	$pg = new chart('horizontalBar', 's1');
	if (isset($_POST['select_s1']))
		$pg->type = $_POST['select_s1'];
	$today = calc_today();
	table('100%', '50%');
	$title = supplier_top($today, 10, 66, $pg);
	$total = supplier_aging($today, 66);
	td('50%');
	source_graphic($today, $title[0], _("Supplier"), $pg);
	br(3);
	echo "<div class='square square2'>"._('Purchasable Total')."<p class='span1'>";
	echo number_format2($title[1])."</p></div>";
	br(3);
	echo "<div class='square square2'>"._('Aging Total')."<p class='span1'>";
	echo number_format2($total)."</p></div>";
	table_end();
	}
	
	if(user_check_access('SA_PDB_PURCHASES_OD_INVOICE_LIST'))
	{
	table('100%', '100%');
	supplier_trans($today);
	table_end();
	}
}

function display_stock_topten($type=0)
{

   

	if ($type == 0)
	{
	    table('100%');
	    auto_deliveries_outstanding_invoices();
		table_end();
	
		table('100%');
		
		if(user_check_access('SA_IDB_ITEMS_COUNT'))
	    {
		td('25%');
		echo "<div class='square square1'>"._('Items')."<p class='span1'>";
		echo get_num_items()."</p></div>";
		}
		if(user_check_access('SA_IDB_LOCATIONS_COUNT'))
	    {
		td('25%');
		echo "<div class='square square1'>"._('Locations')."<p class='span1'>";
		echo get_num_locations()."</p></div>";
		}
		if(user_check_access('SA_IDB_KITS_COUNT'))
	    {
		td('25%');
		echo "<div class='square square1'>"._('Kits')."<p class='span1'>";
		echo get_num_kits()."</p></div>";
		}
		if(user_check_access('SA_IDB_REORDER_COUNT'))
	    {
		td('25%');
		echo "<div class='square square2'>"._('Below Reorder Level')."<p class='span1'>";
		echo get_num_reorder()."</p></div>";
		}
		table_end();
	}
	elseif ($type == 1)
	{
		table('100%');
		if(user_check_access('SA_IDB_ASSEMBLED_ITEMS_COUNT'))
	    {
		td('25%');
		echo "<div class='square square1'>"._('Assembled Items')."<p class='span1'>";
		echo get_num_assembled()."</p></div>";
		}
		if(user_check_access('SA_IDB_MANUF_ITEMS_COUNT'))
	    {
		td('25%');
		echo "<div class='square square1'>"._('Manufactured Items')."<p class='span1'>";
		echo get_num_manufactured()."</p></div>";
		}
		if(user_check_access('SA_IDB_WORK_CENTRES_COUNT'))
	    {
		td('25%');
		echo "<div class='square square1'>"._('Work Centres')."<p class='span1'>";
		echo get_num_workcentres()."</p></div>";
		}
		if(user_check_access('SA_IDB_OPEN_WO_COUNT'))
	    {
		td('25%');
		echo "<div class='square square1'>"._('Open Workorders')."<p class='span1'>";
		echo get_open_workorders()."</p></div>";
		}
		table_end();
	}
	elseif ($type == 2)
	{
		table('100%');
		if(user_check_access('SA_IDB_FIXED_ASSETS_COUNT'))
	    {
		td('25%');
		echo "<div class='square square1'>"._('Fixed Assets')."<p class='span1'>";
		echo get_num_fixed_assets()."</p></div>";
		}
		if(user_check_access('SA_IDB_FIXED_LOCATIONS_COUNT'))
	    {
		td('25%');
		echo "<div class='square square1'>"._('Locations')."<p class='span1'>";
		echo get_num_fixed_locations()."</p></div>";
		}
		if(user_check_access('SA_IDB_FIXED_CATEGORIES_COUNT'))
	    {
		td('25%');
		echo "<div class='square square1'>"._('Categories')."<p class='span1'>";
		echo get_num_fixed_categories()."</p></div>";
		}
		if(user_check_access('SA_IDB_FIXED_ASSET_CLASSES_COUNT'))
	    {
		td('25%');
		echo "<div class='square square1'>"._('Fixed Asset Classes')."<p class='span1'>";
		echo get_num_fixed_classes()."</p></div>";
		}
		table_end();
	}
	
	if(user_check_access('SA_IDB_INVENTORY_TOP10_ITEMS') || user_check_access('SA_IDB_INVENTORY_TOTAL_SALES') || user_check_access('SA_IDB_INVENTORY_TOTAL_COSTS'))
	{
	$pg = new chart('bar', 'i1');
	if (isset($_POST['select_i1']))
		$pg->type = $_POST['select_i1'];
	table('100%', '50%');
	$today = calc_today();
	$title = stock_top($today, 10, 66, $type, $pg);
	table('50%', '25%');
	echo "<div class='square square1'>"._('Total Sales')."<p class='span1'>";
	echo number_format2($title[1])."</p></div>";
	td('25%');
	echo "<div class='square square2'>"._('Total Costs')."<p class='span1'>";
	echo number_format2($title[2])."</p></div>";
	table_end();
	}
	
	if(user_check_access('SA_IDB_INVENTORY_TOP10_ITEMS') || user_check_access('SA_IDB_INVENTORY_TOTAL_RESULTS'))
	{
	td('50%');
	if ($type == 2)
		$source = _("Fixed Assets");
	elseif ($type == 1)
		$source = _("Manufacturing");
	else	
		$source = _("Items");
	source_graphic($today, $title[0], $source, $pg);
	echo "<div class='square'><span>"._('Total Results')."<br />";
	echo number_format2($title[3])."</div>";
	table_end();
	}
	
	if(user_check_access('SA_IDB_INVENTORY_ITEMS_BELOW_REORDER'))
	{
	table('100%', '100%');
	stock_below_reorder($today, $type);
	table_end();
	}

	//ravi
	table('100%');
	if(user_check_access('SA_IDB_INVENTORY_TOP10_STAGNANT_ITEMS'))
	{
	td('50%');
	most_stagnant_items($today);
	}
	
	if(user_check_access('SA_IDB_INVENTORY_PENDING_MATERIAL_INDENT_REQUESTS'))
	{
	td('50%');
	pending_material_indent_requests();
	}
	table_end();
	

}

function display_dimension_topten()
{
	table('100%');
	if(user_check_access('SA_DDB_DIMENSIONS_COUNT'))
	{
	td('25%');
	echo "<div class='square square1'>"._('Dimensions')."<p class='span1'>";
	echo get_num_dimensions()."</p></div>";
	}
	if(user_check_access('SA_DDB_TYPE2_DIMENSIONS_COUNT'))
	{
	td('25%');
	echo "<div class='square square1'>"._('Type 2 Dimensions')."<p class='span1'>";
	echo get_dim_type2()."</p></div>";
	}
	if(user_check_access('SA_DDB_DIMENSIONS_TOTAL_BALANCE'))
	{
	td('25%');
	$res = get_dim_total();
	$s = $res[0] < 0.0 ? 2 : 1;
	echo "<div class='square square{$s}'>"._('Dimensions Total Balance')."<p class='span1'>";
	echo number_format2($res[0])."</p></div>";
	}
	if(user_check_access('SA_DDB_DIMENSIONS_TOTAL_RESULT'))
	{
	td('25%');
	$s = $res[1] < 0.0 ? 2 : 1;
	echo "<div class='square square{$s}'>"._('Dimensions Total Result')."<p class='span1'>";
	echo number_format2($res[1])."</p></div>";
	}
	table_end();
	
	if(user_check_access('SA_DDB_TOP5_DIMENSIONS'))
	{
	$pg = new chart('bar', 'd1');
	if (isset($_POST['select_d1']))
		$pg->type = $_POST['select_d1'];
	table('100%', '50%');
	$today = calc_today();
	$title = dimension_top($today, 5, 66, $pg);
	td('50%');
	source_graphic($today, $title, _("Dimension"), $pg);
	table_end();
	}
}	

function display_gl_info()
{
	$today = calc_today();
	table('100%');
	if(user_check_access('SA_FDB_RECEIVABLES_COUNT'))
	{
	td('25%');
	echo "<div class='square square1'>"._('Receivables')."<p class='span1'>";
	echo number_format2(customer_balance($today))."</p></div>";
	}
	if(user_check_access('SA_FDB_PAYABLES_COUNT'))
	{
	td('25%');
	echo "<div class='square square2'>"._('Payables')."<p class='span1'>";
	echo number_format2(-supplier_balance($today))."</p></div>";
	}
	if(user_check_access('SA_FDB_TODAY_DEPOSITS_COUNT'))
	{
	td('25%');
	echo "<div class='square square1'>"._('Todays Deposits')."<p class='span1'>";
	echo number_format2(get_today_deposits($today))."</p></div>";
	}
	if(user_check_access('SA_FDB_TODAY_PAYMENTS_COUNT'))
	{
	td('25%');
	echo "<div class='square square2'>"._('Todays Payments')."<p class='span1'>";
	echo number_format2(get_today_payments($today))."</p></div>";
	}
	table_end();
	
	
	if(user_check_access('SA_FDB_CLASS_BALANCES'))
	{
	$pg = new chart('halfDonut', 'g1');
	if (isset($_POST['select_g1']))
		$pg->type = $_POST['select_g1'];
	table('100%', '50%');
	$title = gl_top($today, 66, $pg);
	source_graphic($today, $title, _("Class"), $pg);
	}
	
	if(user_check_access('SA_FDB_GL_MONTH_PERFORMANCE'))
	{
	gl_month_performance($today, 66, 5);
	}
	td('50%');
	if(user_check_access('SA_FDB_GL_WEEK_PERFORMANCE'))
	{
	gl_week_performance($today, 66, 4);
	}
	if(user_check_access('SA_FDB_GL_CASH_FLOW'))
	{
	cash_flow($today);
	}
	if(user_check_access('SA_FDB_GL_BANK_BALANCE'))
	{
	bank_balance($today, 66);
	}
	table_end();
	
	
}	

function display_all()
{
	$today = calc_today();

	table('100%');
	td('25%');
	echo "<div class='square square1'>"._('Users')."<p class='span1'>";
	echo get_num_users()."</p></div>";
	td('25%');
	echo "<div class='square square1'>"._('Extensions')."<p class='span1'>";
	echo get_num_extensions()."</p></div>";
	td('25%');
	echo "<div class='square square1'>"._('Languages')."<p class='span1'>";
	echo get_num_languages()."</p></div>";
	td('25%');
	echo "<div class='square square1'>"._('Database Size')."<p class='span1'>";
	echo get_database_size()."</p></div>";
	table_end();
	
	$pg = new chart('horizontalBar', 'c2');
	if (isset($_POST['select_c2']))
		$pg->type = $_POST['select_c2'];
	table('100%', '50%');
	$title = customer_top($today, 3, 66, $pg);
	source_graphic($today, $title[0], _("Customer"), $pg);
	$pg = new chart('horizontalBar', 's2');
	if (isset($_POST['select_s2']))
		$pg->type = $_POST['select_s2'];
	$title = supplier_top($today, 3, 66, $pg);
	source_graphic($today, $title[0], _("Supplier"), $pg);
	$pg = new chart('bar', 'i2');
	if (isset($_POST['select_i2']))
		$pg->type = $_POST['select_i2'];
	$title = stock_top($today, 3, 66, 0, $pg);
	source_graphic($today, $title[0], _("Items"), $pg);
	td('50%');
	dimension_top($today, 3, 66);
	$pg = new chart('pie', 'd2');
	if (isset($_POST['select_d2']))
		$pg->type = $_POST['select_d2'];
	$title = gl_top($today, 66, $pg);
	source_graphic($today, $title, _("Class"), $pg);
	stock_top($today, 3, 66, 2);
	stock_top($today, 3, 66, 1);
	table_end();
}

function customer_top($today, $limit=10, $width='33', &$pg=null)
{
	$begin = date2sql(begin_fiscalyear());
	$today = date2sql($today);
	$sql = "SELECT SUM((ov_amount + ov_discount) * rate * IF(trans.type = ".ST_CUSTCREDIT.", -1, 1)) AS total,d.debtor_no, d.name FROM ".TB_PREF."debtor_trans AS trans, ".TB_PREF."debtors_master AS d WHERE trans.debtor_no=d.debtor_no
		AND (trans.type = ".ST_SALESINVOICE." OR trans.type = ".ST_CUSTCREDIT.")
		AND tran_date >= '$begin' AND tran_date <= '$today' GROUP by d.debtor_no ORDER BY total DESC, d.debtor_no
		LIMIT $limit";
	$result = db_query($sql);
	$title = sprintf(_("Top %s customers in fiscal year"), $limit);
	display_title($title);
	$th = array(_("Customer"), _("Amount"));
	start_table(TABLESTYLE, "width='$width%'");
	headers($th);
	check_page_security('SA_SALESTRANSVIEW');
	$k = 0; //row colour counter
	$names = $totals = array();
	$sales = 0;
	while ($myrow = db_fetch($result))
	{
		$sales += round($myrow['total']);
		alt_table_row_color($k);
		$name = $myrow["debtor_no"]." ".htmlspecialchars_decode($myrow["name"]);
		label_cell($name);
		amount_cell($myrow['total']);
		if ($pg != null)
		{
			$names[] = $name;
			$totals[] = round($myrow['total']);
		}	
		end_row();
	}
	if ($pg != null)
	{
		$pg->setLabels($names);
		$pg->addSerie(_('Sales'), $totals);
	}
	end_table(2);
	return array($title, $sales);
}


function sales_transactions($today, $type, $width='33', &$pg=null)
{
	$begin = date2sql(begin_fiscalyear());
	$today = date2sql($today);
	
	if($type==ST_CUSTCREDIT || $type == ST_CUSTDELIVERY || $type==ST_SALESINVOICE)
	$sql = "SELECT count(trans_no) as total_sales_count,DATE_FORMAT(tran_date,'%b') as months FROM ".TB_PREF."debtor_trans WHERE type=$type AND tran_date >='$begin' AND tran_date <='$today'
		GROUP BY concat(cast(case when weekofyear(tran_date) = 1 and month(tran_date)=12 then year(tran_date) + 1 else year(tran_date) end as char),cast(month(tran_date) as char)) order by tran_date";	
	else
	$sql = "SELECT count(order_no) as total_sales_count,DATE_FORMAT(ord_date,'%b') as months FROM ".TB_PREF."sales_orders WHERE trans_type=$type AND ord_date >='$begin' AND ord_date <='$today'
		GROUP BY concat(cast(case when weekofyear(ord_date) = 1 and month(ord_date)=12 then year(ord_date) + 1 else year(ord_date) end as char),cast(month(ord_date) as char)) order by ord_date";
					
	$result = db_query($sql);
	
	if($type==ST_INSSALESENQ)
	{
	$title = sprintf(_("Enquiries in fiscal year"), 10);
	$th = array(_("Month"), _("Total Enquiries"));
	$graphic_title="Sales Enquiries";
	}
	if($type==ST_SALESQUOTE)
	{
	$title = sprintf(_("Quotations in fiscal year"), 10);
	$th = array(_("Month"), _("Total Quotations"));
	$graphic_title="Sales Quotations";
	}
	if($type==ST_SALESORDER)
	{
	$title = sprintf(_("Orders in fiscal year"), 10);
	$th = array(_("Month"), _("Total Orders"));
	$graphic_title="Sales Orders";
	}
	if($type==ST_CUSTDELIVERY)
	{
	$title = sprintf(_("Dispatches in fiscal year"), 10);
	$th = array(_("Month"), _("Total Dispatches"));
	$graphic_title="Sales Dispatches";
	}
	if($type==ST_SALESINVOICE)
	{
	$title = sprintf(_("Invoices in fiscal year"), 10);
	$th = array(_("Month"), _("Total Invoices"));
	$graphic_title="Sales Invoices";
	}
	if($type==ST_CUSTCREDIT)
	{
	$title = sprintf(_("Returns in fiscal year"), 10);
	$th = array(_("Month"), _("Total Returns"));
	$graphic_title="Sales Returns";
	}
	
	display_title($title);
	
	start_table(TABLESTYLE, "width='$width%'");
	headers($th);
	check_page_security('SA_SALESTRANSVIEW');
	$k = 0; //row colour counter
	$months = $sale_count = array();
	$sales=0;
	while ($myrow = db_fetch_assoc($result))
	{
		$sales+= round($myrow['total_sales_count']);
		alt_table_row_color($k);
		$month_name = $myrow["months"];
		label_cell($month_name,"align=center");
		label_cell($myrow['total_sales_count'],"align=center");
		if ($pg != null)
		{
			$months[] = $month_name;
			$sale_count[] = round($myrow['total_sales_count']);
		}	
		end_row();
	}
	if ($pg != null)
	{
		$pg->setLabels($months);
		$pg->addSerie($graphic_title, $sale_count);
	}
	end_table(2);
	return array($title, $sales);
}


//purchase transactions
function purchase_transactions($today, $type, $width='33', &$pg=null)
{
	$begin = date2sql(begin_fiscalyear());
	$today = date2sql($today);
	
	if($type==ST_SUPPCREDIT || $type == ST_SUPPINVOICE)
	$sql = "SELECT count(trans_no) as total_purchase_count,DATE_FORMAT(tran_date,'%b') as months FROM ".TB_PREF."supp_trans WHERE type=$type AND tran_date >='$begin' AND tran_date <='$today' and ov_amount!=0 
		GROUP BY concat(cast(case when weekofyear(tran_date) = 1 and month(tran_date)=12 then year(tran_date) + 1 else year(tran_date) end as char),cast(month(tran_date) as char)) order by tran_date";
    else if($type==ST_SUPPRECEIVE)
	$sql = "SELECT count(id) as total_purchase_count,DATE_FORMAT(delivery_date,'%b') as months FROM ".TB_PREF."grn_batch WHERE delivery_date >='$begin' AND delivery_date <='$today'
		GROUP BY concat(cast(case when weekofyear(delivery_date) = 1 and month(delivery_date)=12 then year(delivery_date) + 1 else year(delivery_date) end as char),cast(month(delivery_date) as char)) order by delivery_date";		
	else
	$sql = "SELECT count(order_no) as total_purchase_count,DATE_FORMAT(ord_date,'%b') as months FROM ".TB_PREF."purch_orders WHERE trans_type=$type AND ord_date >='$begin' AND ord_date <='$today'
		GROUP BY concat(cast(case when weekofyear(ord_date) = 1 and month(ord_date)=12 then year(ord_date) + 1 else year(ord_date) end as char),cast(month(ord_date) as char)) order by ord_date";

						
	$result = db_query($sql);
	
	if($type==ST_PURCHENQ)
	{
	$title = sprintf(_("Enquiries in fiscal year"), 10);
	$th = array(_("Month"), _("Total Enquiries"));
	$graphic_title="Purchase Enquiries";
	}
	if($type==ST_PURCHQUOTE)
	{
	$title = sprintf(_("Quotations in fiscal year"), 10);
	$th = array(_("Month"), _("Total Quotations"));
	$graphic_title="Purchase Quotations";
	}
	if($type==ST_PURCHORDER)
	{
	$title = sprintf(_("Orders in fiscal year"), 10);
	$th = array(_("Month"), _("Total Orders"));
	$graphic_title="Purchase Orders";
	}
	if($type==ST_SUPPRECEIVE)
	{
	$title = sprintf(_("GRN / MRN in fiscal year"), 10);
	$th = array(_("Month"), _("Total Dispatches"));
	$graphic_title="Purchase GRN/MRN";
	}
	if($type==ST_SUPPINVOICE)
	{
	$title = sprintf(_("Invoices in fiscal year"), 10);
	$th = array(_("Month"), _("Total Invoices"));
	$graphic_title="Purchase Invoices";
	}
	if($type==ST_SUPPCREDIT)
	{
	$title = sprintf(_("Returns in fiscal year"), 10);
	$th = array(_("Month"), _("Total Returns"));
	$graphic_title="Purchase Returns";
	}
	
	display_title($title);
	
	start_table(TABLESTYLE, "width='$width%'");
	headers($th);
	check_page_security('SA_SUPPTRANSVIEW');
	$k = 0; //row colour counter
	$months = $purchase_count = array();
	$purchases=0;
	while ($myrow = db_fetch_assoc($result))
	{
		$purchases+= round($myrow['total_purchase_count']);
		alt_table_row_color($k);
		$month_name = $myrow["months"];
		label_cell($month_name,"align=center");
		label_cell($myrow['total_purchase_count'],"align=center");
		if ($pg != null)
		{
			$months[] = $month_name;
			$purchase_count[] = round($myrow['total_purchase_count']);
		}	
		end_row();
	}
	if ($pg != null)
	{
		$pg->setLabels($months);
		$pg->addSerie($graphic_title, $purchase_count);
	}
	end_table(2);
	return array($title, $purchases);
}


function supplier_top($today, $limit=10, $width='33', &$pg=null)
{
	$begin = date2sql(begin_fiscalyear());
	$today = date2sql($today);
	$sql = "SELECT SUM((trans.ov_amount + trans.ov_discount) * rate) AS total, s.supplier_id, s.supp_name FROM
		".TB_PREF."supp_trans AS trans, ".TB_PREF."suppliers AS s WHERE trans.supplier_id=s.supplier_id
		AND (trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_SUPPCREDIT.")
		AND tran_date >= '$begin' AND tran_date <= '$today' GROUP by s.supplier_id ORDER BY total DESC, s.supplier_id
		LIMIT $limit";
	$result = db_query($sql);
	$title = sprintf(_("Top %s suppliers in fiscal year"), $limit);
	display_title($title);
	$th = array(_("Supplier"), _("Amount"));
	start_table(TABLESTYLE, "width='$width%'");
	headers($th);
	check_page_security('SA_SUPPTRANSVIEW');
	$k = 0; //row colour counter
	$names = $totals = array();
	$total = 0;
	while ($myrow = db_fetch($result))
	{
		$total += $myrow['total'];
		alt_table_row_color($k);
		$name = $myrow["supplier_id"]." ".htmlspecialchars_decode($myrow["supp_name"]);
		label_cell($name);
		amount_cell($myrow['total']);
		if ($pg != null)
		{
			$names[] = $name;
			$totals[] = round($myrow['total']);
		}	
		end_row();
	}
	if ($pg != null)
	{
		$pg->setLabels($names);
		$pg->addSerie(_('Purchases'), $totals);
	}
	end_table(2);
	return array($title, $total);
}

function stock_top($today, $limit=10, $width='33', $type=0, &$pg=null)
{
	if ($type == 2)
		$sec = 'SA_ASSETSANALYTIC';
	elseif ($type == 1)
		$sec = 'SA_WORKORDERANALYTIC';
	else
		$sec = 'SA_ITEMSTRANSVIEW';
	$begin = date2sql(begin_fiscalyear());
	$today = date2sql($today);
	if ($type == 0)
	{
		$sql = "SELECT SUM((trans.unit_price * trans.quantity) * d.rate) AS total, s.stock_id, s.description, 
			SUM(trans.quantity) AS qty, SUM((s.material_cost + s.overhead_cost + s.labour_cost) * trans.quantity) AS costs FROM
			".TB_PREF."debtor_trans_details AS trans, ".TB_PREF."stock_master AS s, ".TB_PREF."debtor_trans AS d 
			WHERE trans.stock_id=s.stock_id AND trans.debtor_trans_type=d.type AND trans.debtor_trans_no=d.trans_no
			AND (d.type = ".ST_SALESINVOICE." OR d.type = ".ST_CUSTCREDIT.") ";
	}
	else
	{
		$sql = "SELECT SUM(m.qty * (s.material_cost + s.labour_cost + s.overhead_cost)) AS total, s.stock_id, s.description, 
			SUM(qty) AS qty FROM ".TB_PREF."stock_master AS s, ".TB_PREF."stock_moves AS m 
			WHERE s.stock_id=m.stock_id ";
		if ($type == 1)
			$sql .= "AND s.mb_flag='M' AND m.type <> ".ST_CUSTDELIVERY." AND m.type <> ".ST_CUSTCREDIT." ";
		elseif ($type == 2)	
			$sql .= "AND s.mb_flag='F' ";
	}
	if ($type != 2)
		$sql .= "AND tran_date >= '$begin' ";
	$sql .= "AND tran_date <= '$today' GROUP by s.stock_id ORDER BY total DESC, s.stock_id LIMIT $limit";
	$result = db_query($sql);
	if ($type == 1)
		$title = sprintf(_("Top %s Manufactured Items in fiscal year"), $limit);
	elseif ($type == 2)
		$title = sprintf(_("Top %s Fixed Assets"), $limit);
	else	
		$title = sprintf(_("Top %s Sold Items in fiscal year"), $limit);
	display_title($title);	
	if ($type == 0)	
		$th = array(_("Item"), _("Sales"), _("Costs"), _("Results"), _("Quantity"));
	else	
		$th = array(_("Item"), _("Amount"), _("Quantity"));
	start_table(TABLESTYLE, "width='$width%'");
	headers($th);
	check_page_security($sec);
	$k = 0; //row colour counter
	$names = $totals = $costs = $results = array();
	$sales = $cost = $res = 0;
	while ($myrow = db_fetch($result))
	{
		$sales += $myrow['total'];
		if ($type == 0)
		{
			$cost += $myrow['costs'];
			$res += ($myrow['total'] - $myrow['costs']);
		}
		alt_table_row_color($k);
		$name = $myrow["description"];
		label_cell($name);
		amount_cell($myrow['total']);
		if ($type == 0)
		{
			amount_cell($myrow['costs']);
			amount_cell($myrow['total'] - $myrow['costs']);
		}
		qty_cell($myrow['qty']);
		if ($pg != NULL)
		{
			$names[] = $name;
			$totals[] = round($myrow['total']);	
			if ($type == 0)
			{
				$costs[] = round($myrow['costs']);
				$results[] = round($myrow['total'] - $myrow['costs']);
			}
		}	
		end_row();
	}
	if ($pg != null)
	{
		$pg->setLabels($names);
		$pg->addSerie(_('Sales'), $totals);
		if ($type == 0)
		{
			$pg->addSerie(_('Costs'), $costs);
			$pg->addSerie(_('Results'), $results, 8);
		}
	}
	end_table(2);
	return array($title, $sales, $cost, $res);
}

function dimension_top($today, $limit=10, $width='33', &$pg=null)
{
	$begin = date2sql(begin_fiscalyear());
	$today = date2sql($today);

	$sql = "SELECT SUM(IF(c.ctype > 3, -amount, 0)) AS result, SUM(IF(c.ctype < 4, amount, 0)) AS balance, d.reference, d.name FROM ".TB_PREF."gl_trans g 
		LEFT JOIN ".TB_PREF."chart_master AS a ON g.account = a.account_code
		LEFT JOIN ".TB_PREF."chart_types AS t ON t.id = a.account_type
		LEFT JOIN ".TB_PREF."dimensions AS d ON (g.dimension_id = d.id OR g.dimension2_id = d.id)
		LEFT JOIN ".TB_PREF."chart_class AS c ON t.class_id = c.cid
		WHERE IF(c.ctype > 3, tran_date >= '$begin', tran_date >= '0000-00-00') 
		AND tran_date <= '$today'  AND d.reference IS NOT NULL GROUP BY d.reference ORDER BY result DESC LIMIT $limit"; 	
	$result = db_query($sql, "Transactions could not be calculated");
	$title = sprintf(_("Top %s Dimensions in fiscal year"), $limit);
	display_title($title);
	$th = array(_("Dimension"), _("Balance"), _("Result"));
	start_table(TABLESTYLE, "width='$width%'");
	headers($th);
	check_page_security('SA_DIMTRANSVIEW');
	$k = 0; //row colour counter
	$names = $balances = $results = array();
	while ($myrow = db_fetch($result))
	{
		alt_table_row_color($k);
		$name = $myrow['reference']." ".$myrow["name"];
		label_cell($name);
		amount_cell($myrow['balance']);
		amount_cell($myrow['result']);
		if ($pg != null)
		{
			$names[] = $name; 
			$balances[] = round(abs($myrow['balance']));
			$results[] = round(abs($myrow['result']));
		}	
		end_row();
	}
	if ($pg != null)
	{
		$pg->setLabels($names);
		$pg->setValues(true);
		$pg->addSerie(_('Balance'), $balances);
		$pg->addSerie(_('Result'), $results);
	}
	end_table(2);
	return $title;
}

function gl_top($today, $width='33', &$pg=null)
{
	$begin = date2sql(begin_fiscalyear());
	$today = date2sql($today);
	$sql = "SELECT SUM(IF(c.ctype > 3, -amount, amount)) AS total, c.class_name, c.ctype FROM
		".TB_PREF."gl_trans,".TB_PREF."chart_master AS a, ".TB_PREF."chart_types AS t, 
		".TB_PREF."chart_class AS c WHERE
		account = a.account_code AND a.account_type = t.id AND t.class_id = c.cid
		AND IF(c.ctype > 3, tran_date >= '$begin', tran_date >= '0000-00-00') 
		AND tran_date <= '$today' GROUP BY c.cid ORDER BY c.cid"; 
	$result = db_query($sql, "Transactions could not be calculated");
	$title = _("Class Balances");
	display_title($title);
	start_table(TABLESTYLE2, "width='$width%'");
	check_page_security('SA_GLANALYTIC');
	$total = 0;
	$names = $totals = array();
	while ($myrow = db_fetch($result))
	{
		if ($myrow['ctype'] > 3)
		{
			$total += round($myrow['total']);
			if ($pg != null)
			{
				$names[] = $myrow['class_name']; 
				$totals[] = round(abs($myrow['total']));
			}	
		}	
		label_row($myrow['class_name'], number_format2($myrow['total'], user_price_dec()), 
			"class='label' style='font-weight:bold;'", "style='font-weight:bold;' align=right");
	}
	$calculated = _("Calculated Return");
	label_row("&nbsp;", "");
	label_row($calculated, number_format2($total, user_price_dec()), 
		"class='label' style='font-weight:bold;'", "style='font-weight:bold;' align=right");
	if ($pg != null)
	{
		$names[] = $calculated; 
		$totals[] = $total;
		$pg->setLabels($names);
		$pg->addSerie(_('Totals'), $totals);
		$pg->setValues(true);
		$pg->setDTitle(number_format2($total));
	}
	end_table(2);
	return $title;
}

function gl_week_performance($today, $width="33", $weeks=4)
{
	global $SysPrefs;
	
	$pg = new chart('bar', 'g3');
	if (isset($_POST['select_g3']))
		$pg->type = $_POST['select_g3'];
	if (isset($_POST['per_g3']))
		$weeks = $_POST['per_g3'];
	$begin = date2sql(begin_fiscalyear());
	$today1 = date2sql($today);
	$sep = $SysPrefs->dateseps[user_date_sep()];
	$sql = "SELECT week_name, sales, costs 
		FROM(SELECT DATE_FORMAT(tran_date, '%X{$sep}%V') AS week_name, 
			SUM(IF(c.ctype = 4, amount * -1, 0)) AS sales, 
			SUM(IF(c.ctype = 6, amount, 0)) AS costs FROM 
			".TB_PREF."gl_trans, ".TB_PREF."chart_master AS a, ".TB_PREF."chart_types AS t, 
			".TB_PREF."chart_class AS c WHERE(c.ctype = 4 OR c.ctype = 6) 
			AND account = a.account_code AND a.account_type = t.id AND t.class_id = c.cid 
			AND tran_date >= '$begin' AND tran_date <= '$today1' 
			GROUP BY week_name ORDER BY week_name DESC LIMIT 0, $weeks) b 
		GROUP BY week_name ORDER BY week_name ASC";
	$result = db_query($sql, "Transactions could not be calculated");
	$title = sprintf(_("Last %s weeks Performance"), $weeks);
	check_page_security('SA_GLANALYTIC');
	$week_names = $sales = $costs = $results = array();
	while ($myrow = db_fetch($result))
	{
		$week_names[] = $myrow['week_name']; 
		$sales[] = round($myrow['sales']);
		$costs[] = round($myrow['costs']);
		$results[] = round($myrow['sales'] - $myrow['costs']);
	}	
	$pg->setLabels($week_names);
	$pg->addSerie(_('Sales'), $sales, 'bar');
	$pg->addSerie(_('Costs'), $costs, 'bar');
	$pg->addSerie(_('Results'), $results, 'spline');
	source_graphic($today, $title, _("Week"), $pg, _("Weeks"), $weeks);
}

function gl_month_performance($today, $width="33", $months=5)
{
	global $SysPrefs;
	$pg = new chart('bar', 'g4');
	if (isset($_POST['select_g4']))
		$pg->type = $_POST['select_g4'];
	if (isset($_POST['per_g4']))
		$months = $_POST['per_g4'];
	$begin = date2sql(begin_fiscalyear());
	$today1 = date2sql($today);
	$sep = $SysPrefs->dateseps[user_date_sep()];
	$sql = "SELECT month_name, sales, costs 
		FROM(SELECT DATE_FORMAT(tran_date, '%Y{$sep}%m') AS month_name, 
			SUM(IF(c.ctype = 4, amount * -1, 0)) AS sales, 
			SUM(IF(c.ctype = 6, amount, 0)) AS costs FROM 
			".TB_PREF."gl_trans, ".TB_PREF."chart_master AS a, ".TB_PREF."chart_types AS t, 
			".TB_PREF."chart_class AS c WHERE(c.ctype = 4 OR c.ctype = 6) 
			AND account = a.account_code AND a.account_type = t.id AND t.class_id = c.cid 
			AND tran_date >= '$begin' AND tran_date <= '$today1' 
			GROUP BY month_name ORDER BY month_name DESC LIMIT 0, $months) b 
		GROUP BY month_name ORDER BY month_name ASC";
	$result = db_query($sql, "Transactions could not be calculated");
	$title = sprintf(_("Last %s Months Performance"), $months);
	check_page_security('SA_GLANALYTIC');
	$month_names = $sales = $costs = $results = array();
	while ($myrow = db_fetch($result))
	{
		$month_names[] = $myrow['month_name']; 
		$sales[] = round($myrow['sales']);
		$costs[] = round($myrow['costs']);
		$results[] = round($myrow['sales'] - $myrow['costs']);
	}	
	$pg->setLabels($month_names);
	$pg->addSerie(_('Sales'), $sales, 'bar');
	$pg->addSerie(_('Costs'), $costs, 'bar');
	$pg->addSerie(_('Results'), $results, 'spline');
	source_graphic($today, $title, _("Month"), $pg, _("Months"), $months);
}

function customer_aging($today, $width="33")
{
	$pg = new chart('bar', 'c3');
	if (isset($_POST['select_c3']))
		$pg->type = $_POST['select_c3'];
	$today1 = date2sql($today);
	$past1 = get_company_pref('past_due_days');
	$past2 = 2 * $past1;

	$sign = "IF(`type` IN(".implode(',',  array(ST_CUSTCREDIT,ST_CUSTPAYMENT,ST_BANKDEPOSIT))."), -1, 1)";

	$value = "$sign*(IF(trans.prep_amount, trans.prep_amount,
		ABS(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount)) - trans.alloc)*trans.rate";

	$due = "IF (trans.type=".ST_SALESINVOICE.", trans.due_date, trans.tran_date)";
    $sql = "SELECT Sum($value) AS Balance,
				Sum(IF ((TO_DAYS('$today1') - TO_DAYS($due)) > 0,$value,0)) AS Due,
				Sum(IF ((TO_DAYS('$today1') - TO_DAYS($due)) > $past1,$value,0)) AS Overdue1,
				Sum(IF ((TO_DAYS('$today1') - TO_DAYS($due)) > $past2,$value,0)) AS Overdue2
			FROM ".TB_PREF."debtors_master debtor
				 LEFT JOIN ".TB_PREF."debtor_trans trans ON trans.tran_date <= '$today1' AND debtor.debtor_no = trans.debtor_no AND trans.type <> ".ST_CUSTDELIVERY.","
				 .TB_PREF."payment_terms terms,"
				 .TB_PREF."credit_status credit_status
			WHERE
					debtor.payment_terms = terms.terms_indicator
	 			AND debtor.credit_status = credit_status.id";
		$sql .= " AND ABS(IF(trans.prep_amount, trans.prep_amount, ABS(trans.ov_amount) + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount) - trans.alloc) > ".FLOAT_COMP_DELTA;
    $result = db_query($sql,"The customer details could not be retrieved");

    $row = db_fetch($result);

	$title = _("Total Customers Aged Analysis");
	check_page_security('SA_SALESTRANSVIEW');
	$names = array();
	$past1a = $past1 + 1;
	$past2a = $past2 + 1;
	$names = array(_('Current'),"1-$past1", "$past1a-$past2", "$past2+");
	$balances = array(round($row['Balance']-$row['Due']), round($row['Due'] - $row['Overdue1']), 
		round($row['Overdue1'] - $row['Overdue2']), round($row['Overdue2']));
	$total = round($row['Balance']);
	$pg->setLabels($names);
	$pg->addSerie(_('Balances'), $balances);
	source_graphic($today, $title, _('Days'), $pg);
	return $total;
}

function customer_balance($today)
{
	$today = date2sql($today);
	$sign = "IF(`type` IN(".implode(',',  array(ST_CUSTCREDIT,ST_CUSTPAYMENT,ST_BANKDEPOSIT))."), -1, 1)";
   	$value = "IFNULL($sign*((trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount -
		trans.alloc)*trans.rate),0)";
	$due = "IF (trans.type=".ST_SALESINVOICE.", trans.due_date, trans.tran_date)";
    $sql = "SELECT Sum($value) AS Balance
			FROM ".TB_PREF."debtors_master debtor
				 LEFT JOIN ".TB_PREF."debtor_trans trans ON trans.tran_date <= '$today' AND debtor.debtor_no = trans.debtor_no AND trans.type <> ".ST_CUSTDELIVERY."
			WHERE ABS(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount) - trans.alloc > ".FLOAT_COMP_DELTA;
    $result = db_query($sql,"The customer details could not be retrieved");
    $row = db_fetch($result);
	return $row[0];
}

function supplier_aging($today, $width="33")
{
	$pg = new chart('bar', 's3'); 
	if (isset($_POST['select_s3']))
		$pg->type = $_POST['select_s3'];
	$today1 = date2sql($today);
	$past1 = get_company_pref('past_due_days');
	$past2 = 2 * $past1;
	// removed - debtor_trans.alloc from all summations
   	$value = "IF (trans.type=".ST_SUPPINVOICE." OR trans.type=".ST_BANKDEPOSIT.", 
    	(trans.ov_amount + trans.ov_gst + trans.ov_discount - trans.alloc) * trans.rate,
    	(trans.ov_amount + trans.ov_gst + trans.ov_discount + trans.alloc) * trans.rate)";
	$due = "IF (trans.type=".ST_SUPPINVOICE." OR trans.type=".ST_SUPPCREDIT.",trans.due_date,trans.tran_date)";
    $sql = "SELECT Sum($value) AS Balance,
				Sum(IF ((TO_DAYS('$today1') - TO_DAYS($due)) > 0,$value,0)) AS Due,
				Sum(IF ((TO_DAYS('$today1') - TO_DAYS($due)) > $past1,$value,0)) AS Overdue1,
				Sum(IF ((TO_DAYS('$today1') - TO_DAYS($due)) > $past2,$value,0)) AS Overdue2
			FROM ".TB_PREF."suppliers supp
			 LEFT JOIN ".TB_PREF."supp_trans trans ON supp.supplier_id = trans.supplier_id AND trans.tran_date <= '$today1'
			AND ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount) > ".FLOAT_COMP_DELTA."
			AND ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount) - trans.alloc > ".FLOAT_COMP_DELTA;
    $result = db_query($sql,"The supplier details could not be retrieved");

    $row = db_fetch($result);

	$title = _("Total Suppliers Aged Analysis");
	check_page_security('SA_SUPPLIERANALYTIC');
	$names = array();
	$past1a = $past1 + 1;
	$past2a = $past2 + 1;
	$names = array(_('Current'),"1-$past1", "$past1a-$past2", "$past2+");
	$balances = array(round($row['Balance']-$row['Due']), round($row['Due'] - $row['Overdue1']), 
		round($row['Overdue1'] - $row['Overdue2']), round($row['Overdue2']));
	$total = round($row['Balance']);
	$pg->setLabels($names);
	$pg->addSerie(_('Balances'), $balances);
	source_graphic($today, $title, _('Days'), $pg);
	return $total;
}

function supplier_balance($today)
{
	$today = date2sql($today); 
   	$value = "IF (trans.type=".ST_SUPPINVOICE." OR trans.type=".ST_BANKDEPOSIT.", 
    	(trans.ov_amount + trans.ov_gst + trans.ov_discount - trans.alloc) * trans.rate,
    	(trans.ov_amount + trans.ov_gst + trans.ov_discount + trans.alloc) * trans.rate)";
    $sql = "SELECT Sum($value) AS Balance
			FROM ".TB_PREF."suppliers supplier, ".TB_PREF."supp_trans trans
				WHERE supplier.supplier_id = trans.supplier_id
			AND trans.tran_date <= '$today'
			AND ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount) > ".FLOAT_COMP_DELTA."
			AND ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount) - trans.alloc > ".FLOAT_COMP_DELTA;
    $result = db_query($sql,"The supplier details could not be retrieved");
    $row = db_fetch($result);
	return $row[0];
}

function cash_flow($today)
{
	global $date_system, $SysPrefs;
	$months = 6;
	$pg = new chart('spline', 'g5');
	if (isset($_POST['select_g5']))
		$pg->type = $_POST['select_g5'];
	if (isset($_POST['per_g5']))
		$months = $_POST['per_g5'];
	if (!is_date_in_fiscalyear($today))
		$today = end_fiscalyear();
	$today1 = begin_month($today);
	$today1 = add_months($today, -$months+1);
	list($da, $mo, $yr) = explode_date_to_dmy($today1);
	if ($date_system == 1)
		list($yr, $mo, $da) = jalali_to_gregorian($yr, $mo, $da);
	elseif ($date_system == 2)
		list($yr, $mo, $da) = islamic_to_gregorian($yr, $mo, $da);

	$date = array();

	for ($i = 0; $i < $months; $i++)
		$date[$i] = date('Y-m-d',mktime(0,0,0,$mo + $i,1,$yr));

	$result = get_bank_accounts();
	$total =  array_fill(0, $months, 0);
	$balance = array_fill(0, $months, 0);
	while ($account=db_fetch($result))
	{ 
		$sql = "SELECT";
		for ($i = 0; $i < $months; $i++)
			$sql .= " SUM(CASE WHEN trans_date < '$date[$i]' THEN amount ELSE 0 END) AS per0".($i+1).",";
		$sql = substr($sql, 0, -1);
		$sql .=	" FROM ".TB_PREF."bank_trans
			WHERE bank_act=".$account['id'];
		$res = db_query($sql, "Transactions for bank account could not be calculated");
		$bal = db_fetch($res);
		$is_home = is_company_currency($account['bank_curr_code']);
		for ($i = 1; $i <= $months; $i++)
			$balance[$i - 1] = $bal['per0'.$i];
		for ($i = 0; $i < $months; $i++)
		{
			if (!$is_home)
				$balance[$i] = to_home_currency($balance[$i], $account['bank_curr_code'], sql2date($date[$i])); 
			$total[$i] += $balance[$i];
		}
	}
	$sep = $SysPrefs->dateseps[user_date_sep()];
	for ($i = 0; $i < $months; $i++)
	{
		$y = substr($date[$i], 0, 4);
		$m = substr($date[$i], 5, 2);
		$date[$i] = $y.$sep.$m;
		$total[$i] = round($total[$i]);
	}
	$title = sprintf(_("Last %s Months Cash Flow"), $months);
	check_page_security('SA_BANKREP');
	$pg->setLabels($date);
	$pg->addSerie(_('Balances'), $total, false, true);
	source_graphic($today, $title, _("Month"), $pg, _("Months"), $months);
}

function source_graphic($today, $title, $x_axis, $pg, $per = '', $num = false)
{
	if (!empty($per))
		div_start($pg->id);
	//$today = sql2date($today);
	display_title("$title ($today)", $pg->id, $pg->type, $per, $num);	
	if ($pg->isEmpty())
	{
		display_note(_("No Data available yet!"), 1);
		return;
	}
	if ($pg->type == 'horizontalBar')
	{
		$pg->setYTitle($x_axis);
		$pg->setXTitle(_("Amount"));
	}
	else
	{
		$pg->setXTitle($x_axis);
		$pg->setYTitle(_("Amount"));
	}
	if (empty($per))
		div_start($pg->id);
	$pg->display();
	div_end();
}

//ravi
function source_graphic_for_totals($today, $title, $x_axis, $pg, $per = '', $num = false)
{
	if (!empty($per))
		div_start($pg->id);
	//$today = sql2date($today);
	display_title("$title ($today)", $pg->id, $pg->type, $per, $num);	
	if ($pg->isEmpty())
	{
		display_note(_("No Data available yet!"), 1);
		return;
	}
	if ($pg->type == 'horizontalBar')
	{
		$pg->setYTitle($x_axis);
		$pg->setXTitle(_("Total"));
	}
	else
	{
		$pg->setXTitle($x_axis);
		$pg->setYTitle(_("Total"));
	}
	if (empty($per))
		div_start($pg->id);
	$pg->display();
	div_end();
}


function customer_trans($today)
{
	$today = date2sql($today);

	$sql = "SELECT trans.trans_no, trans.reference,	trans.tran_date, trans.due_date, debtor.debtor_no, 
		debtor.name, branch.br_name, debtor.curr_code,
		(trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount)	AS total,  
		(trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount - trans.alloc) AS remainder,
		DATEDIFF('$today', trans.due_date) AS days 	
		FROM ".TB_PREF."debtor_trans as trans, ".TB_PREF."debtors_master as debtor, 
			".TB_PREF."cust_branch as branch
		WHERE debtor.debtor_no = trans.debtor_no AND trans.branch_code = branch.branch_code
			AND trans.type = ".ST_SALESINVOICE." AND (trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount - trans.alloc) > ".FLOAT_COMP_DELTA." 
			AND DATEDIFF('$today', trans.due_date) > 0 ORDER BY days DESC";
	$result = db_query($sql);
	$title = db_num_rows($result) . _(" overdue Sales Invoices");
  	display_title($title);
	$th = array("#", _("Ref."), _("Date"), _("Due Date"), _("Customer"), _("Branch"), _("Currency"), 
		_("Total"), _("Remainder"),	_("Days"));
	start_table(TABLESTYLE, "width=90%");
	headers($th);
  	$k = 0; //row colour counter
	while ($myrow = db_fetch($result))
	{
		alt_table_row_color($k);
		label_cell(get_trans_view_str(ST_SALESINVOICE, $myrow["trans_no"]));
		label_cell($myrow['reference']);
		label_cell(sql2date($myrow['tran_date']));
		label_cell(sql2date($myrow['due_date']));
		$name = $myrow["debtor_no"]." ".$myrow["name"];
		label_cell($name);
		label_cell($myrow['br_name']);
		label_cell($myrow['curr_code']);
		amount_cell($myrow['total']);
		amount_cell($myrow['remainder']);
		label_cell($myrow['days'], "align='right'");
		end_row();
	}
	end_table(2);
}

function calculate_next_invoice($myrow)
{
	if ($myrow["last_sent"] == '0000-00-00')
		$next = sql2date($myrow["begin"]);
	else
		$next = sql2date($myrow["last_sent"]);
	$next = add_months($next, $myrow['monthly']);
	$next = add_days($next, $myrow['days']);
	return add_days($next,-1);
}

function customer_recurrent_invoices($today)
{
	$result = get_recurrent_invoices($today);
	$title = _("Overdue Recurrent Invoices");
  	display_title($title);
	$th = array(_("Description"), _("Template No"),_("Customer"),_("Branch")."/"._("Group"),_("Next invoice"));
	start_table(TABLESTYLE, "width=90%");
  	headers($th);
	$k = 0;
	while ($myrow = db_fetch($result)) 
	{
		if (!$myrow['overdue'])
			continue;
		alt_table_row_color($k);

		label_cell($myrow["description"]);
		label_cell(get_customer_trans_view_str(ST_SALESORDER, $myrow["order_no"]));
		if ($myrow["debtor_no"] == 0)
		{
			label_cell("");

			label_cell(get_sales_group_name($myrow["group_no"]));
		}
		else
		{
			label_cell(get_customer_name($myrow["debtor_no"]));
			label_cell(get_branch_name($myrow['group_no']));
		}
		label_cell(calculate_next_invoice($myrow),  "align='center'");
		end_row();
	}
	end_table(2);
}

function supplier_trans($today)
{
	$today = date2sql($today);
	$sql = "SELECT trans.trans_no, trans.reference, trans.tran_date, trans.due_date, s.supplier_id, 
		s.supp_name, s.curr_code,
		(trans.ov_amount + trans.ov_gst + trans.ov_discount) AS total,  
		(trans.ov_amount + trans.ov_gst + trans.ov_discount - trans.alloc) AS remainder,
		DATEDIFF('$today', trans.due_date) AS days 	
		FROM ".TB_PREF."supp_trans as trans, ".TB_PREF."suppliers as s 
		WHERE s.supplier_id = trans.supplier_id
			AND trans.type = ".ST_SUPPINVOICE." AND (ABS(trans.ov_amount + trans.ov_gst + 
				trans.ov_discount) - trans.alloc) > ".FLOAT_COMP_DELTA." ORDER BY days DESC";
	$result = db_query($sql);
	$title = db_num_rows($result) . _(" unpaid Purchase Invoices");
	display_title($title);
	$th = array("#", _("Ref."), _("Date"), _("Due Date"), _("Supplier"), _("Currency"), _("Total"), 
		_("Remainder"),	_("Days"));
	start_table(TABLESTYLE, "width=90%");
	headers($th);
	$k = 0; //row colour counter
	$due = false;
	while ($myrow = db_fetch($result))
	{
		if ($myrow['days'] > 0)
		{
			start_row("class='overduebg'");
			$due = true;
		}
		else
			alt_table_row_color($k);
		label_cell(get_trans_view_str(ST_SUPPINVOICE, $myrow["trans_no"]));
		label_cell($myrow['reference']);
		label_cell(sql2date($myrow['tran_date']));
		label_cell(sql2date($myrow['due_date']));
		$name = $myrow["supplier_id"]." ".$myrow["supp_name"];
		label_cell($name);
		label_cell($myrow['curr_code']);
		amount_cell($myrow['total']);
		amount_cell($myrow['remainder']);
		label_cell($myrow['days'], "align='right'");
		end_row();
	}
	end_table();
	if ($due)
		display_note(_("Marked items are overdue."), 1, 0, "class='overduefg'");
	br();
}

function stock_below_reorder($today, $type)
{
	$MB = ($type == 0 ? 'B' : ($type == 1 ? 'M' : 'F'));
	$today = date2sql($today);

    $sql = "SELECT st.loc_code, l.location_name, st.stock_id, sm.description, reorders.reorder_level AS r, SUM(st.qty) AS q
		FROM ".TB_PREF."stock_moves st
   		LEFT JOIN ".TB_PREF."voided v ON st.type=v.type AND st.trans_no=v.id
		LEFT JOIN ".TB_PREF."stock_master sm ON sm.stock_id = st.stock_id 
		LEFT JOIN ".TB_PREF."loc_stock reorders ON reorders.loc_code=st.loc_code AND
			reorders.stock_id = st.stock_id
		LEFT JOIN ".TB_PREF."locations l ON l.loc_code = st.loc_code 
          WHERE ISNULL(v.id) AND tran_date <= '$today' AND sm.mb_flag = 'B' and reorders.reorder_level>0 
			GROUP BY sm.stock_id HAVING q <= r";
	$result = db_query($sql,"an item reorder could not be retreived");
	$title = db_num_rows($result) . _(" items are below Reorder Level");
	display_title($title);
	$th = array("#", _("Location"), _("Stock Id"), _('Description'), _("Reorder"), _("qty")); 
	start_table(TABLESTYLE, "width=90%");
	headers($th);
	$k = 0; //row colour counter
	while ($myrow = db_fetch($result))
	{
		alt_table_row_color($k);
		label_cell($myrow['loc_code']);
		label_cell($myrow['location_name']);
		label_cell($myrow['stock_id']);
		label_cell($myrow['description']);
		$dec = get_qty_dec($myrow['stock_id']);		
		qty_cell($myrow['r'], false, $dec);
		qty_cell($myrow['q'], false, $dec);
		end_row();
	}
	end_table(2);
}

function most_stagnant_items($today)
{
	$begin = begin_fiscalyear();
    $begin1 = date2sql($begin);
    $today1 = date2sql($today);
   
    $sql = "SELECT stock_id,SUM(qty) as tot_qty,st.tran_date as trans_date  FROM ".TB_PREF."stock_moves st
   	LEFT JOIN ".TB_PREF."voided v ON st.type=v.type AND st.trans_no=v.id
    WHERE ISNULL(v.id) AND st.type=".ST_CUSTDELIVERY." AND st.tran_date >= '$begin1' AND st.tran_date <= '$today1' group by st.stock_id ORDER BY st.tran_date asc limit 10";
    $result = db_query($sql, "QOH calculation failed");
	
	$title = _("Most Stagnant 10 Items");
	display_title($title);
	$th = array(_("Item Code"), _("Description"),_("Stock"), _("Sales Qty"), _("Last Sale")); 
	start_table(TABLESTYLE, "width=90%");
	headers($th);
	$k = 0; //row colour counter
	while ($myrow = db_fetch($result))
	{
		$qoh_end = get_qoh_on_date($myrow['stock_id'], '', sql2date($today1));      
        $name = $myrow["stock_id"];
        $fet=get_item($name);
        if($name!=$fet['stock_id'])
			continue;
        $qty=abs($myrow['tot_qty']);
		
		alt_table_row_color($k);
		label_cell($myrow['stock_id']);
		label_cell($fet['description']);
		$dec = get_qty_dec($myrow['stock_id']);
		qty_cell($qoh_end,$dec);
		qty_cell($qty,$dec);
	label_cell(sql2date($myrow['trans_date']));
		end_row();
	}
	end_table(3);
}


//ravi

function auto_deliveries_outstanding_invoices()
{
   
    $sql = "SELECT trans.trans_no,
			debtor.name,
			branch.branch_code,
			branch.br_name,
			sorder.deliver_to,
			trans.reference,
			sorder.customer_ref,
			trans.tran_date,
			trans.due_date,
			(ov_amount+ov_gst+ov_freight+ov_freight_tax) AS DeliveryValue,
			debtor.curr_code,
			Sum(line.quantity-line.qty_done) AND sorder.prep_amount=0 AS Outstanding,
			Sum(line.qty_done) AS Done
		FROM "
		 .TB_PREF."sales_orders as sorder, "
		 .TB_PREF."debtor_trans as trans, "
		 .TB_PREF."debtor_trans_details as line, "
		 .TB_PREF."debtors_master as debtor, "
		 .TB_PREF."cust_branch as branch
			WHERE
			sorder.order_no = trans.order_ AND
			trans.debtor_no = debtor.debtor_no
				AND trans.type = ".ST_CUSTDELIVERY."
				AND line.debtor_trans_no = trans.trans_no
				AND line.debtor_trans_type = trans.type
				AND trans.branch_code = branch.branch_code
				AND trans.debtor_no = branch.debtor_no 
				AND trans.reference='auto'";

		 $sql .= " AND line.qty_done < line.quantity ";
		 $sql .= " GROUP BY trans.trans_no ";
		
    $result = db_query($sql, "QOH calculation failed");
	
	$title = _("Modified Invoices List");
	
	display_title($title);
	$th = array(_("Delivery"), _("Customer"),_("Contact"), _("Cust Ref"), _("Delivery Date"), _("Due By"), _("Delivery Total"), _("Currency"), _("Action")); 
	
	start_table(TABLESTYLE, "width=90%");
	headers($th);
	$k = 0; //row colour counter
	while ($myrow = db_fetch($result))
	{	
		alt_table_row_color($k);
		label_cell(get_customer_trans_view_str(ST_CUSTDELIVERY, $myrow['trans_no']),"align='center'");
		label_cell($myrow['name']);
		label_cell($myrow['deliver_to']);
		label_cell($myrow['customer_ref']);
		label_cell(sql2date($myrow['tran_date']),"align='center'");
		label_cell(sql2date($myrow['due_date']),"align='center'");
		amount_cell($myrow['DeliveryValue']);
		label_cell($myrow['curr_code'],"align='center'");
		label_cell("<a href='../sales/customer_delivery.php?ModifyDelivery=".$myrow['trans_no']."'>Update</a>");
		end_row();
	}
	end_table(4);
}

function pending_material_indent_requests()
{
  
    $sql="SELECT indent.indent_id AS indent_num,indent_header.reference,indent_header.tran_date,loc1.location_name AS l1,loc2.location_name AS l2,indent.quantity,requested_by FROM ".TB_PREF."material_indent_request AS indent_header LEFT JOIN ".TB_PREF."material_indent_request_details AS  indent ON indent.indent_id=indent_header.indent_id LEFT JOIN ".TB_PREF."locations AS loc1 ON indent_header.indent_from_loc =loc1.loc_code LEFT JOIN ".TB_PREF."locations AS loc2 ON
	indent_header.req_loc_code =loc2.loc_code group by reference";
		
    $result = db_query($sql, "QOH calculation failed");
	
	$title = _("Pending Material Indent Requests");
	display_title($title);
	$th = array(_("#"), _("Reference"),_("Date"), _("Requested By"), _("Request From"), _("Request To")); 
	start_table(TABLESTYLE, "width=90%");
	headers($th);
	$k = 0; //row colour counter
	while ($myrow = db_fetch($result))
	{
		alt_table_row_color($k);
		label_cell(get_trans_view_str(ST_MATERIAL_INDENT, $myrow["indent_num"]),"align=center");
		label_cell($myrow['reference'],"align=center");
		label_cell(sql2date($myrow['tran_date']),"align=center");
		label_cell($myrow['requested_by'],"align=center");
		label_cell($myrow['l1'],"align=center");
		label_cell($myrow['l2'],"align=center");
		end_row();
	}
	end_table(3);
}


function pending_po_approvals()
{
    $company_record = get_company_prefs();
    $po_auth_req_amount = str_replace(',', '', $company_record["po_auth_req_amount"]);
	  
	$sql = "SELECT 
		porder.order_no, 
		porder.reference,
		supplier.supp_name, 
		location.location_name,
		porder.requisition_no, 
		porder.ord_date,
		supplier.curr_code,	 Sum((line.unit_price*line.quantity_ordered)-((line.unit_price*line.quantity_ordered)*(line.discount_percent/100))) AS OrderValue,
		Sum(line.delivery_date < '". date2sql(Today()) ."'
		AND (line.quantity_ordered > line.quantity_received)) As OverDue
		FROM ".TB_PREF."purch_orders as porder,"
			.TB_PREF."purch_order_details as line,".TB_PREF."suppliers as supplier, "
			.TB_PREF."locations as location
		WHERE porder.order_no = line.order_no
		AND porder.trans_type ='18'
		AND porder.supplier_id = supplier.supplier_id
		AND location.loc_code = porder.into_stock_location
		AND (line.quantity_ordered > line.quantity_received) 
		AND porder.po_auth_req = 1
		AND porder.reference !='auto'
		AND porder.total >".db_escape($po_auth_req_amount)." GROUP BY porder.order_no";
		
    $result = db_query($sql, "QOH calculation failed");
	
	$title = _("Pending Purchase Order Approvals");
	display_title($title);
	$th = array(_("#"), _("Reference"),_("Supplier"), _("Location"), _("Supplier's Reference"), _("Order Date"),_("Currency"), _("Order Total")); 
	start_table(TABLESTYLE, "width=90%");
	headers($th);
	$k = 0; //row colour counter
	while ($myrow = db_fetch($result))
	{
		alt_table_row_color($k);
		label_cell(get_trans_view_str(ST_PURCHORDER, $myrow["order_no"]),"align=center");
		label_cell($myrow['reference'],"align=center");
		label_cell($myrow['supp_name'],"align=center");
		label_cell($myrow['location_name'],"align=center");
		label_cell($myrow['requisition_no'],"align=center");
		label_cell(sql2date($myrow['ord_date']),"align=center");
		label_cell($myrow['curr_code'],"align=center");
		amount_cell($myrow['OrderValue']);
		end_row();
	}
	end_table(3);
}

function bank_balance($today, $width)
{
	$today = date2sql($today);
	$sql = "SELECT bank_act, bank_account_name, bank_curr_code, SUM(amount) balance FROM ".TB_PREF."bank_trans bt 
	            INNER JOIN ".TB_PREF."bank_accounts ba ON bt.bank_act = ba.id
	            WHERE trans_date <= '$today'
	            AND inactive <> 1
	            GROUP BY bank_act, bank_account_name
				ORDER BY bank_account_name";
	$result = db_query($sql);
	$title = _("Bank Account Balances");
	display_title($title);
	$th = array(_("Account"), _("Currency"), _("Balance"));
	start_table(TABLESTYLE, "width='$width%'");
	headers($th);
	$k = 0; //row colour counter
	while ($myrow = db_fetch($result))
	{
		alt_table_row_color($k);
		label_cell(viewer_link($myrow["bank_account_name"], 'gl/inquiry/bank_inquiry.php?bank_account='.$myrow["bank_act"]));
		label_cell($myrow["bank_curr_code"]);
		amount_cell($myrow['balance']);
		end_row();
	}
	end_table(1);
}

function get_num_customers()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."debtors_master";
	$result = db_query($sql, "Fail in count customers");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_branches()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."cust_branch";
	$result = db_query($sql, "Fail in count branches");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_salesmen()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."salesman";
	$result = db_query($sql, "Fail in count salesmen");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_overdue_sales()
{
	$today = date2sql(calc_today());

	$sql = "SELECT COUNT(trans.trans_no) 	
		FROM ".TB_PREF."debtor_trans as trans
		WHERE trans.type = ".ST_SALESINVOICE." AND (trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount - trans.alloc) > ".FLOAT_COMP_DELTA." 
			AND DATEDIFF('$today', trans.due_date) > 0";
	$result = db_query($sql);
    $row = db_fetch($result);
	return $row[0];
}

function get_num_suppliers()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."suppliers";
	$result = db_query($sql, "Fail in count suppliers");
    $row = db_fetch($result);
	return $row[0];
}

function get_new_purch_orders()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."purch_orders po, ".TB_PREF."purch_order_details pd WHERE po.order_no = pd.order_no AND 
		quantity_received < quantity_ordered";
	$result = db_query($sql, "Fail in count purch orders");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_supp_invoices()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."supp_trans WHERE (ov_amount+ov_discount+ov_gst - alloc) <> 0";
	$result = db_query($sql, "Fail in count purch invoices");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_overdue_purch()
{
	$today = date2sql(calc_today());

	$sql = "SELECT COUNT(*) FROM ".TB_PREF."supp_trans WHERE (ov_amount+ov_discount+ov_gst - alloc) > ".FLOAT_COMP_DELTA." AND DATEDIFF('$today', due_date) > 0";
	$result = db_query($sql, "Fail in count purch invoices");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_items()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."stock_master";
	$result = db_query($sql, "Fail in count stock master");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_locations()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."locations";
	$result = db_query($sql, "Fail in count locations");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_kits()
{
	$sql="SELECT count(*) FROM ".TB_PREF."item_codes";

	$result = db_query($sql, "Fail in count kits");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_reorder()
{
	$MB = 'B';
	$today = date2sql(calc_today());

    $sql = "SELECT st.stock_id, reorders.reorder_level AS r, SUM(st.qty) AS q
		FROM ".TB_PREF."stock_moves st
   		LEFT JOIN ".TB_PREF."voided v ON st.type=v.type AND st.trans_no=v.id
		LEFT JOIN ".TB_PREF."stock_master sm ON sm.stock_id = st.stock_id 
		LEFT JOIN ".TB_PREF."loc_stock reorders ON reorders.loc_code=st.loc_code AND
			reorders.stock_id = st.stock_id
          WHERE ISNULL(v.id) AND tran_date <= '$today' AND sm.mb_flag = 'B' and reorders.reorder_level>0
			GROUP BY st.stock_id HAVING q <= r";
	$result = db_query($sql,"an item reorder could not be retreived");
	return db_num_rows($result);
}

function get_num_assembled()
{
	$begin = date2sql(begin_fiscalyear());
	$today = date2sql(calc_today());
	$sql = "SELECT count(*) FROM ".TB_PREF."workorders WHERE closed = 1 AND type = ".WO_ASSEMBLY." AND released_date >= '$begin'
		AND released_date <= '$today'";
	$result = db_query($sql, "Fail in count workorders");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_manufactured()
{
	$begin = date2sql(begin_fiscalyear());
	$today = date2sql(calc_today());
	$sql = "SELECT count(*) FROM ".TB_PREF."workorders WHERE closed = 1 AND type = ".WO_ADVANCED." AND released_date >= '$begin'
		AND released_date <= '$today'";
	$result = db_query($sql, "Fail in count workorders");
    $row = db_fetch($result);
	return $row[0];
}
	
function get_num_workcentres()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."workcentres";
	$result = db_query($sql, "Fail in count work centres");
    $row = db_fetch($result);
	return $row[0];
}
	
function get_open_workorders()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."workorders WHERE closed = 0";
	$result = db_query($sql, "Fail in count workorders");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_fixed_assets()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."stock_master WHERE mb_flag = 'F'";
	$result = db_query($sql, "Fail in count fixed assets");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_fixed_locations()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."stock_master s LEFT JOIN ".TB_PREF."loc_stock l ON s.stock_id = l.stock_id 
		WHERE mb_flag = 'F'";
	$result = db_query($sql, "Fail in count locations");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_fixed_categories()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."stock_category c LEFT JOIN ".TB_PREF."stock_master s ON c.category_id = s.category_id 
		WHERE mb_flag = 'F'";
	$result = db_query($sql, "Fail in count categories");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_fixed_classes()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."stock_fa_class";
	$result = db_query($sql, "Fail in count fa classes");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_dimensions()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."dimensions";
	$result = db_query($sql, "Fail in count dimensions");
    $row = db_fetch($result);
	return $row[0];
}

function get_dim_type2()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."dimensions WHERE type_ = 2";
	$result = db_query($sql, "Fail in count simensions 2");
    $row = db_fetch($result);
	return $row[0];
}

function get_dim_closed()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."dimensions WHERE closed = 1";
	$result = db_query($sql, "Fail in count closed dimensions");
    $row = db_fetch($result);
	return $row[0];
}

function get_dim_total()
{
	$begin = date2sql(begin_fiscalyear());
	$today = date2sql(calc_today());
	
	$sql = "SELECT SUM(IF(c.ctype > 3, -amount, 0)) AS result, SUM(IF(c.ctype < 4, amount, 0)) AS balance, d.reference, d.name FROM ".TB_PREF."gl_trans g 
		LEFT JOIN ".TB_PREF."chart_master AS a ON g.account = a.account_code
		LEFT JOIN ".TB_PREF."chart_types AS t ON t.id = a.account_type
		LEFT JOIN ".TB_PREF."dimensions AS d ON (g.dimension_id = d.id OR g.dimension2_id = d.id)
		LEFT JOIN ".TB_PREF."chart_class AS c ON t.class_id = c.cid
		WHERE IF(c.ctype > 3, tran_date >= '$begin', tran_date >= '0000-00-00') 
		AND tran_date <= '$today'  AND d.reference IS NOT NULL GROUP BY d.reference"; 	
	$bal = $res = 0.0;
	$result = db_query($sql, "Transactions could not be calculated");
	while ($row = db_fetch($result))
	{
		$bal += $row['balance'];
		$res += $row['result'];
	}	
	return array(round($bal), round($res));	
}
	
function get_today_deposits($today)
{
	$today= date2sql($today);
	$sql="SELECT SUM(gl.amount) FROM ".TB_PREF."gl_trans gl, ".TB_PREF."bank_accounts ba 
		WHERE gl.account = ba.account_code AND gl.tran_date = '$today' AND gl.amount > 0.0";
	$result = db_query($sql, "could not retrieve today deposits");
	if ($result == false)
		return 0;
	else
	{
		$row = db_fetch($result);
		return $row[0];
	}
}

function get_today_payments($today)
{
	$today= date2sql($today);
	$sql="SELECT SUM(gl.amount) FROM ".TB_PREF."gl_trans gl, ".TB_PREF."bank_accounts ba 
		WHERE gl.account = ba.account_code AND gl.tran_date = '$today' AND gl.amount < 0.0";
	$result = db_query($sql, "could not retrieve today deposits");
	if ($result == false)
		return 0;
	else
	{
		$row = db_fetch($result);
		return $row[0];
	}
}

function get_num_users()
{
	$sql = "SELECT count(*) FROM ".TB_PREF."users";
	$result = db_query($sql, "could not get num users");
    $row = db_fetch($result);
	return $row[0];
}

function get_num_extensions()
{
	global $installed_extensions;
	return count($installed_extensions);
}

function get_num_languages()
{
	global $installed_languages;
	return count($installed_languages);
}

function get_database_size()
{
	global $db_connections;
	$c = user_company();
	$dbase = $db_connections[$c]['dbname'];
	$tb_pref = $db_connections[$c]['tbpref'];
	$sql = "SHOW TABLE STATUS FROM $dbase";
	if (!empty($tb_pref))
		$sql .= " LIKE '{$tb_pref}%'";
	$result = db_query($sql, "could not get database size");
    $size = 0;
    while ($row = db_fetch($result)) {
		$size += $row['Data_length'];
		$size += $row['Index_length'];
    }
	return number_format2(($size / 1024 / 1024), 2).' MB';
}

function calc_today()
{
	$today = Today();
	if (!is_date_in_fiscalyear($today))
		$today = end_fiscalyear();
	return $today;
}


//--------------------------------------------------------
// Outstanding & Upcoming Item Deliveries
function sales_outstanding_upcoming_item_deliveries($today)
{
	$today = date2sql($today);
	

	$sql = "SELECT 
			sorder.order_no,
			sorder.reference,
			sorder.delivery_date,
			sorder.debtor_no,
			debtor.name,
			sorder.ord_date,
			debtor.curr_code,
			line.stk_code,
			line.description,
			line.qty_sent AS TotDelivered,
			line.quantity AS TotQuantity,
			sorder.total,
			sorder.trans_type,
			DATEDIFF(sorder.delivery_date,'$today') AS days,
			line.id as order_line_id,
			line.planned_status,
			sorder.delivery_time
		FROM ".TB_PREF."sales_orders as sorder,
		    ".TB_PREF."sales_order_details as line, 
			".TB_PREF."debtors_master as debtor
			WHERE sorder.order_no = line.order_no
			AND sorder.trans_type = line.trans_type
			AND sorder.trans_type = 30
			AND sorder.debtor_no = debtor.debtor_no
			AND line.quantity-line.qty_sent>0
			AND sorder.reference!='auto'
			AND sorder.cancel_status=0
			ORDER BY days DESC";
	$result = db_query($sql);
	$title = _("Outstanding & Upcoming Item Deliveries");
  	display_title($title);
	$th = array(_("Ref"), _("Actual Delivery Date"), _("Actual Delivery Time"),_("Customer"), _("Item Name"), _("Quantity"), _("Days"), 
	_("Planned Delivery Date"),_("Planned Delivery Time"), _("Action"));
	start_table(TABLESTYLE, "width=90%");
	headers($th);
  	$k = 0; //row colour counter
	global $delivery_times;
	
	while ($myrow = db_fetch($result))
	{
		alt_table_row_color($k);
		label_cell($myrow['reference']);
        label_cell(sql2date($myrow['delivery_date']));
		label_cell($delivery_times[$myrow['delivery_time']],'align=center');
		
        $name = $myrow["debtor_no"]." ".$myrow["name"];
        label_cell($name);
        label_cell($myrow['description']);
		$qdec = get_qty_dec($myrow['stk_code']);
	    qty_cell($myrow['TotQuantity'], false, $qdec);
        label_cell($myrow['days'], "align='right'");
		if($myrow['planned_status']==0){
		label_cell(sql2date($myrow['delivery_date']),'align=center');
		
		if($myrow['delivery_time']!=0){
		label_cell($delivery_times[$myrow['delivery_time']],'align=center');
		}
		else{
		label_cell("");
		}
		
	    label_cell(viewer_link(_("Reschedule"), 'sales/inquiry/sales_delivery_plan.php?planned_status=1&
		order_no='.$myrow["order_no"].'&order_line_id='.$myrow["order_line_id"].'&
		planned_from_date='.$myrow["delivery_date"].'&
		planned_from_time='.$myrow["delivery_time"]));
		
		}
		else{
		$planned_date = get_sales_delivery_planned_date($myrow["order_no"],
		$myrow["order_line_id"]);
		label_cell(sql2date($planned_date),'align=center');
		
		$planned_time = get_sales_delivery_planned_time($myrow["order_no"],
		$myrow["order_line_id"]);
		
		if($planned_time!=0){
		label_cell($delivery_times[$planned_time],'align=center');
		}
		else{
		label_cell("");
		}
		
		label_cell(viewer_link(_("Reschedule"), 'sales/inquiry/sales_delivery_plan.php?planned_status=1&order_no='.$myrow["order_no"].'&order_line_id='.$myrow["order_line_id"].'&
		planned_from_date='.$planned_date.'&
		planned_from_time='.$planned_time));
		}
		end_row();
	}
	end_table(2);
}

function get_sales_delivery_planned_date($order_no,$order_line_id)
{
	$sql = "SELECT planned_date FROM ".TB_PREF."sales_delivery_plan 
			WHERE order_no = ".db_escape($order_no)."
			AND order_line_id = ".db_escape($order_line_id)." and has_child=0";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
}	


function get_sales_delivery_planned_time($order_no,$order_line_id)
{
	$sql = "SELECT planned_delivery_time FROM ".TB_PREF."sales_delivery_plan 
			WHERE order_no = ".db_escape($order_no)."
			AND order_line_id = ".db_escape($order_line_id)." and has_child=0";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
}

function get_pending_pdc_recall_transactions_dashboard($title,$today)
{
	$today1 = date2sql($today);
	
	display_title($title);
	$th = array(_("#"), _("Doc No."), _("Doc Date"), _("Cust Code"),_("Customer Name"),
	_("Chq No."),_("Chq Date"),_("Amount"));
	start_table(TABLESTYLE, "width='80%'");
	headers($th);
	$k = 0; //row colour counter
	
	$sql = "SELECT trans.trans_no,trans.reference,trans.tran_date,cust.cust_code,
	cust.name,trans.pdc_cheque_no,trans.pdc_cheque_date,trans.ov_amount
	FROM ".TB_PREF."debtor_trans as trans,".TB_PREF."debtors_master as cust 
	WHERE trans.debtor_no = cust.debtor_no
	AND trans.type = 5
	AND trans.ov_amount!=0 AND current_pdc_status=0
	AND pdc_cheque_date <= '$today1'";
	$sql .= " ORDER BY pdc_cheque_date";
	
	$result = db_query($sql, "document details could not be retreived");
	
	$k = 0;
	while ($myrow = db_fetch($result))
	{
	alt_table_row_color($k);	
	label_cell(get_customer_trans_view_str(ST_CUSTPDC, $myrow['trans_no']),"align=center");
	label_cell($myrow['reference'],"align=center");
	label_cell(sql2date($myrow['tran_date']));
	label_cell($myrow['cust_code']);
	label_cell($myrow['name']);
	label_cell($myrow['pdc_cheque_no']);
	label_cell(sql2date($myrow['pdc_cheque_date']));
	label_cell(number_format2($myrow['ov_amount'], user_price_dec()));
	
	end_row();
	}	
	end_table(1);
}