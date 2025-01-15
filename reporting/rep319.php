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
$page_security =  'SA_INVENTORY_ITEM_WISE_PENDING_SALES_ORDER_REPORT';  // 'SA_PENDING_SO_ITEMS_REP'; //'SA_PENDING_SO_ITEMS_REP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Chaitanya
// date_:	2005-05-19
// Title:	Sales Summary Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

//----------------------------------------------------------------------------------------------------

print_inventory_sales();

function get_inv_supplier_name1($supplier_id)
{
	$sql = "SELECT supp_name from ".TB_PREF."suppliers where supplier_id=".db_escape($supplier_id);

	$result=db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	return $row[0];
}


function get_cust_name($customer_id){
	
	$sql = "SELECT name from ".TB_PREF."debtors_master where debtor_no=".db_escape($customer_id);
		$result=db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	return $row[0];
}
function get_loc_name($loc_code){
	
	$sql = "SELECT location_name from ".TB_PREF."locations where loc_code=".db_escape($loc_code);
		$result=db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	return $row[0];
}

function get_total_stock($stock_id)
{
	$sql = "SELECT sum(qty) from ".TB_PREF."stock_moves where stock_id=".db_escape($stock_id)."";
	$result=db_query($sql,"No transactions were returned");
	if($row=db_fetch_row($result))
	return $row[0];
	else 
	return 0;
}

function get_onorder_edd($stock_id)
{
	$sql="SELECT delivery_date FROM ".TB_PREF."purch_order_details WHERE item_code=".db_escape($stock_id)." and quantity_ordered-quantity_received>0 and delivery_date!='0000-00-00' order by delivery_date";
	$result=db_query($sql,"No transactions were returned");
	if($row=db_fetch_row($result))
	return $row[0];
	else
	return '0000-00-00';
}

function getTransactions($supplier,$category, $from, $to, $subcategory, $item,$customer,$location,$sales_person,$currency_type)
{
	$from = date2sql($from);
	$to = date2sql($to);
	
	$home_curr = get_company_currency();
	
	$sql = "SELECT trans.reference,trans.ord_date,debtor.name,salesman.salesman_name,item.category_id,
			category.description AS cat_description,
			item.stock_id,
			item.description,
			line.unit_price *(100-line.discount_percent)*(0.01) AS unit_price,
			line.quantity,line.qty_sent,trans.customer_ref
		FROM ".TB_PREF."stock_master item,
			".TB_PREF."stock_category category,".TB_PREF."debtors_master debtor,".TB_PREF."cust_branch branch,".TB_PREF."salesman salesman,";
		if($supplier!='all')
		{
			$sql.=TB_PREF."suppliers supp,";
		}	
			
			
		if($subcategory!='all')
		{
			$sql.=TB_PREF."item_sub_category item_sb,";
		}		
		
		
		$sql.=TB_PREF."sales_orders trans,
			".TB_PREF."sales_order_details line
		WHERE line.stk_code = item.stock_id
		AND item.category_id=category.category_id 
		AND line.order_no=trans.order_no
		AND trans.debtor_no=debtor.debtor_no
		AND debtor.debtor_no=branch.debtor_no
		AND line.trans_type=trans.trans_type
		AND trans.ord_date>='$from'
		AND branch.salesman=salesman.salesman_code 	
		AND trans.ord_date<='$to'
		AND line.quantity-line.qty_sent <> 0
		AND item.mb_flag <>'F'
		AND trans.trans_type = ".ST_SALESORDER."";
		
	if ($category != 0)
			$sql .= " AND item.category_id = ".db_escape($category); 
		
	if ($subcategory != 'all')
		$sql .= " AND item.item_sub_category=item_sb.id AND item.item_sub_category = ".db_escape($subcategory);
     
	
	if ($supplier != 'all')
		$sql .= " AND item.supplier_id = supp.supplier_id AND item.supplier_id = ".db_escape($supplier);
	
		
	if($customer != 'all'){
			$sql .= " AND trans.debtor_no = ".db_escape($customer);
	}
	
	if($location != 'all'){
			$sql .= " AND trans.from_stk_loc = ".db_escape($location)." ";
	}
			
		
	if ($sales_person != 0)
	$sql .= " AND salesman.salesman_code=".db_escape($sales_person);
	
		
    if($item)
	   $sql .= "  AND item.stock_id = ".db_escape($item)."";
   
   
    if ($currency_type == '1'){
		$sql .= " AND debtor.curr_code =".db_escape($home_curr);
	}
	elseif ($currency_type == '2'){
		$sql .= " AND debtor.curr_code != ".db_escape($home_curr);
	}
		
	$sql .= " ORDER BY item.category_id, item.stock_id";
						
	
    return db_query($sql,"No transactions were returned");

}

//----------------------------------------------------------------------------------------------------

function print_inventory_sales()
{
    global $path_to_root,$systypes_array;

    $supplier      = $_POST['PARAM_0'];
	$from          = $_POST['PARAM_1'];
	$to            = $_POST['PARAM_2'];
    $category      = $_POST['PARAM_3'];
	$subcategory   = $_POST['PARAM_4'];
	$customer      = $_POST['PARAM_5'];
	$sales_person  = $_POST['PARAM_6'];
	$currency_type = $_POST['PARAM_7'];
	$location      = $_POST['PARAM_8'];
	$item          = $_POST['PARAM_9'];
	$comments      = $_POST['PARAM_10'];
	$orientation   = $_POST['PARAM_11'];
	$destination   = $_POST['PARAM_12'];
	
	
	//ravi
	$destination=1;
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
    $dec = user_price_dec();

	if ($category == ALL_NUMERIC)
		$category = 0;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);
	
	if ($subcategory == ALL_TEXT)
		$subcategory = 'all';
	if ($subcategory == 'all')
		$subcat = _('All');
	else
		$subcat = get_stock_subcategory_name($subcategory);
	
	
	if ($supplier == ALL_TEXT)
		$supplier = 'all';
	if ($supplier== 'all')
		$sup = _('All');
	else
		$sup = get_inv_supplier_name1($supplier);
	

		
	if ($customer == ALL_TEXT)
		$customer = 'all';
	if ($customer == 'all')
		$cust_name = _('All');
	else
		$cust_name = get_cust_name($customer);	
		
	if ($location == ALL_TEXT)
		$location = 'all';
	if ($location == 'all')
		$loc = _('All');
	else
		$loc = get_location_name($location);	
	
	if ($sales_person == ALL_NUMERIC)
		$sales_person = 0;
	if ($sales_person == 0)
		$sales_person_name = _('All Sales person');
	else
		$sales_person_name = get_salesman_name($sales_person);
		

	if($currency_type == '0'){
       $cur_type = "All";
    } elseif($currency_type == '1'){
       $cur_type = "Local";
    }elseif($currency_type == '2'){
       $cur_type = "Foreign";
    }		
	

	$cols = array(0, 50, 90, 130,240,320,425,450,500,550,600,650,700,750,800,850);

	$headers = array(_('Reference'),_('Date'),_('Customer'),_('Item/Category'), _('Description'), _('Ordered Qty'), _('Dispatch Qty'), _('Pendig Qty'), _('Unit Cost'), _('Outstanding Value'), _('Sales Person'), _('Total Available Qty'), _('On Order'), _('EDD'),_('Last GRN Date'));	

	$aligns = array('left',	'left','left',	'left','left',	'right',	'right', 'right', 'right','right', 'left','right', 'right','right',	'center');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
                        2 => array('text' => _('Supplier'), 'from' => $sup, 'to' => ''),
    				    3 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
						4 => array('text' => _('Sub Category'), 'from' => $subcat, 'to' => ''),
						5 => array('text' => _('Customer'), 'from' => $cust_name, 'to' => ''),
						6 => array('text' => _('Location'), 'from' => $loc, 'to' => ''),
						7 => array('text' => _('Sales Person'), 'from' => $sales_person_name, 'to' => ''),
						8 => array('text' => _('Currency Type'), 'from' => $cur_type, 'to' => ''));
						

    $rep = new FrontReport(_('Item wise Pending Sales Order Report'), "ItemWisePendingSalesReport", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();
	 
     
  
	$res = getTransactions($supplier,$category, $from, $to ,$subcategory, $item,$customer,$location,$sales_person,$currency_type);
	
	$total = $grandtotal =$outstanding_value=0.0;
	$catt = '';
	while ($trans=db_fetch($res))
	{
		$rep->TextCol(0, 1, $trans['reference']);
		$rep->TextCol(1, 2, sql2date($trans['ord_date']));
		$rep->TextCol(2, 3, $trans['name']);
		$rep->TextCol(3, 4, $trans['stock_id']);
		$rep->TextCol(4, 5, $trans['description']);
		$rep->AmountCol(5, 6, $trans['quantity'], get_qty_dec($trans['stock_id']));
		$rep->AmountCol(6, 7, $trans['qty_sent'], get_qty_dec($trans['stock_id']));
		$rep->AmountCol(7, 8, $trans['quantity']-$trans['qty_sent'], get_qty_dec($trans['stock_id']));
		$total+= $trans['quantity']-$trans['qty_sent'];
		$rep->AmountCol(8, 9, $trans['unit_price'],$dec);
		$rep->AmountCol(9, 10, ($trans['quantity']-$trans['qty_sent'])*$trans['unit_price'],$dec);
		$outstanding_value+=($trans['quantity']-$trans['qty_sent'])*($trans['unit_price']);
		$rep->TextCol(10, 11, $trans['salesman_name']);
		$rep->AmountCol(11, 12, get_total_stock($trans['stock_id']),$dec);
	    $rep->AmountCol(12, 13, get_on_porder_qty($trans['stock_id'], ""),$dec);
	   $rep->TextCol(13, 14, sql2date(get_onorder_edd($trans['stock_id'])));
	    $rep->TextCol(14, 15, get_last_grn_date($trans['stock_id']));
	   $rep->NewLine();
	}
	$rep->NewLine(2, 3);
	$rep->TextCol(0, 7, _('Total'));
	$rep->AmountCol(7, 8, $total, $dec);
	$rep->AmountCol(9, 10, $outstanding_value, $dec);
	$rep->Line($rep->row - 2);
	$rep->NewLine();
    $rep->End();
}

