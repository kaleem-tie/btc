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
$page_security = 'SA_SALES_QUOTE_REG_REP';

// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Customer Balances
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

//--------------------------------------------------------------------------------------------

print_sales_quotation_register_details();





function getTransactions($from, $to, $customer=0, $sales_person=0)
{
	$from = date2sql($from);
	$to   = date2sql($to);
	
	$sql = "SELECT DISTINCT trans.order_no,trans.reference,trans.ord_date,debtor.cust_code,
	debtor.name as cust_name,trans.customer_ref,
	trans.sales_person_id,debtor.curr_code,trans.delivery_date
		FROM ".TB_PREF."sales_orders trans,".TB_PREF."debtors_master debtor
		WHERE trans.debtor_no=debtor.debtor_no
		AND trans.ord_date>='$from'
		AND trans.ord_date<='$to'
		AND trans.trans_type = ".ST_SALESQUOTE."";
     
	if($customer != ''){
		$sql .= " AND trans.debtor_no = ".db_escape($customer);
	}
	
	if ($sales_person != 0)
	$sql .= " AND trans.sales_person_id=".db_escape($sales_person);
		
	$sql .= " GROUP BY trans.order_no";
		
	
   return db_query($sql,"No transactions were returned");

}


function get_sales_quote_line_transactions($quote_no, $category, $subcategory) {
	
	$sql = "SELECT item.category_id,
			category.description AS cat_description,
			item.stock_id,
			item.description,
			line.unit_price *(100-line.discount_percent)*(0.01) AS unit_price,
			line.quantity,line.qty_sent,line.unit
			
		FROM ".TB_PREF."stock_master item,
			".TB_PREF."stock_category category,";
		
			
		if($subcategory!='all')
		{
			$sql.=TB_PREF."item_sub_category item_sb,";
		}		
		
		
		$sql.=TB_PREF."sales_orders trans,
			".TB_PREF."sales_order_details line
		WHERE line.stk_code = item.stock_id
		AND item.category_id=category.category_id 
		AND line.order_no=trans.order_no
		AND line.trans_type=trans.trans_type
		AND item.mb_flag <>'F'
		AND line.order_no = ".db_escape($quote_no)."
		AND trans.trans_type = ".ST_SALESQUOTE."";
		
	if ($category != 0)
			$sql .= " AND item.category_id = ".db_escape($category); 
		
	if ($subcategory != 'all')
		$sql .= " AND item.item_sub_category=item_sb.id AND item.item_sub_category = ".db_escape($subcategory);
	
     
	$sql .= " ORDER BY trans.order_no,line.id";
	
	
	 return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------

function print_sales_quotation_register_details()
{
    	global $path_to_root, $systypes_array;

    	$from         = $_POST['PARAM_0'];
    	$to           = $_POST['PARAM_1'];
    	$customer     = $_POST['PARAM_2'];
    	$category     = $_POST['PARAM_3'];
	    $subcategory  = $_POST['PARAM_4'];
		$sales_person = $_POST['PARAM_5'];
		$summaryOnly  = $_POST['PARAM_6'];
    	$comments     = $_POST['PARAM_7'];
	    $orientation  = $_POST['PARAM_8'];
	    $destination  = $_POST['PARAM_9'];
		
	
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	
	
	$dec = user_price_dec();
	

	if ($customer == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($customer);
    
	if ($sales_person == ALL_NUMERIC)
        $sales_person = 0;
    if ($sales_person == 0)
        $salesfolk = _('All Sales Man');
     else
        $salesfolk = get_salesman_name($sales_person);
	
	
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
	
	
	if ($summaryOnly == 1)
		$summary = _('Summary Only');
	else
		$summary = _('Detailed Report');

	
	
	$headers2 = array(_('QU No.'), _('Revised'), _('QU Date'), _('Cust Code'), _('Cust Name'), 
	 _(''),  _('Ref. No.'), _('Salesman'), _('Curr'), _('Appvd By'));

	$cols = array(0, 40, 100, 200, 250, 300, 350,400, 450, 500,550);

    if ($summaryOnly == 0){
	$headers = array(_('Sl. No.'), _('Item Code'), _('Item Description'), _(''), _(''), 
	 _('Units'),  _('Quantity'), _('Rate'), _('Amount'), _('Remarks'));
	}
	
	$aligns = array('left',	'left',	'left',	'left','left', 
	'center', 'right', 'right', 'right', 'right');
	
	
	$aligns2 = $aligns;

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust,   	'to' => ''),
						3 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
						4 => array('text' => _('Sub Category'), 'from' => $subcat, 'to' => ''),
						5 => array('text' => _('Sales Person'), 'from' => $salesfolk, 'to' => ''));

    $rep = new FrontReport(_('Quotation Register'), 
	"QuotationRegister", user_pagesize(), 7, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
	$cols2 = $cols;
	
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
    $rep->NewPage();

	$quote_total  = 0;
	
	

	$result = getTransactions($from, $to, $customer, $sales_person);
	

	while ($myrow = db_fetch($result))
	{
		

			
			$rep->TextCol(0, 1,	$myrow['reference']);
			$rep->TextCol(1, 2,	"");
			$rep->DateCol(2, 3,	$myrow['ord_date'], true);
			$rep->TextCol(3, 4,	$myrow['cust_code']);
			$rep->TextCol(4, 6,	$myrow['cust_name']);
			$rep->TextCol(6, 7,	$myrow['customer_ref']);
			$rep->TextCol(7, 8,	get_salesman_name($myrow['sales_person_id']));
			$rep->TextCol(8, 9,$myrow['curr_code']);
			$rep->TextCol(9, 10,"");
			$rep->TextCol(10, 11,"");
			$rep->NewLine();
			
		  if (!$summaryOnly)
		  {
			 
			$quote_details = get_sales_quote_line_transactions($myrow['order_no'], $category, $subcategory);
    		
    		
			$sl_no=1;
			while ($quote=db_fetch($quote_details))
			{
				
				$DisplayQty = number_format2($quote["quantity"],get_qty_dec($quote['stock_id']));
				$DisplayPrice = number_format2($quote["unit_price"],$dec);
				$line_total =  number_format2($quote["quantity"]*$quote["unit_price"],$dec);	
				
			    $rep->NewLine(1, 2);
				
			    $rep->TextCol(0, 1,	$sl_no);
			    $rep->TextCol(1, 2,	$quote['stock_id']);
			    $rep->TextCol(2, 5,	$quote['description']);
			
			    if($quote['unit']==1){
				 $item_info = get_item_edit_info($quote["stock_id"]);	
				 $rep->TextCol(5, 6,	$item_info["units"], -2);
				}
				else if($quote["unit"]==2){
		         $sec_unit_info = get_item_sec_unit_info($quote["stock_id"]);
	             $rep->TextCol(5, 6,$sec_unit_info["sec_unit_name"], -2);
                }	
			
			    $rep->TextCol(6, 7,	$DisplayQty, -2);
			    $rep->TextCol(7, 8,	$DisplayPrice, -2);
			    $rep->TextCol(8, 9, $line_total);
			    $rep->TextCol(10, 11,"");
			    
			    $sl_no++;	
			}
			
			$rep->NewLine();
			$rep->Text($ccol+450, _('Valid Till : ').sql2date($myrow['delivery_date']));
			
			$rep->Line($rep->row - 8);
			$rep->NewLine(2);
		 }
		
	}
	
	
	
	//$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

