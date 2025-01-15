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
$page_security = 'SA_SUPPLIERANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Inventory Sales Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

//----------------------------------------------------------------------------------------------------

print_inventory_purchase();

function getTransactions($fromsupp,$from, $to)
{
	$from = date2sql($from);
	$to = date2sql($to);
	$sql = "SELECT item.category_id,
			category.description AS cat_description,
			item.stock_id,
			item.description, item.inactive,trans.supp_reference,
			supplier.supplier_id , trans.rate as ex_rate,
			supplier.supp_name AS supplier_name,
			trans.tran_date,
			sitems.quantity AS qty,
			sitems.unit_price+sitems.unit_tax as price
		FROM ".TB_PREF."supp_trans trans
				LEFT JOIN ".TB_PREF."supp_invoice_items sitems ON trans.trans_no=sitems.supp_trans_no AND trans.type=sitems.supp_trans_type
				LEFT JOIN ".TB_PREF."suppliers supplier ON trans.supplier_id=supplier.supplier_id,
			".TB_PREF."stock_master item,
			".TB_PREF."stock_category category
		WHERE item.stock_id=sitems.stock_id
		AND item.category_id=category.category_id
		AND trans.tran_date>='$from'
		AND trans.tran_date<='$to'
		AND (trans.type=".ST_SUPPINVOICE." AND  sitems.supp_trans_type=".ST_SUPPINVOICE.")
		AND (item.mb_flag='D')";
		if ($fromsupp != '')
			$sql .= " AND supplier.supplier_id = ".db_escape($fromsupp);
		
		$sql .= " GROUP BY item.category_id,item.stock_id,supplier.supp_name 
		ORDER BY item.category_id,supplier.supp_name";
    return db_query($sql,"No transactions were returned");

}


//----------------------------------------------------------------------------------------------------

function print_inventory_purchase()
{
    global $path_to_root;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
    $fromsupp = $_POST['PARAM_2'];
	$comments = $_POST['PARAM_3'];
	$orientation = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
    $dec = user_price_dec();


	if ($fromsupp == '')
		$froms = _('All');
	else
		$froms = get_supplier_name($fromsupp);

	
	$cols = array(0, 60, 180, 225, 275, 400, 420, 465,	520);

	$headers = array(_('Category'), _('Description'), _('Date'), _('#'), _('Supplier'), _('Qty'), _('Unit Price'), _('Total'));
	if ($fromsupp != '')
		$headers[4] = '';

	$aligns = array('left',	'left',	'left', 'left', 'left', 'left', 'right', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Supplier'), 'from' => $froms, 'to' => ''));

    $rep = new FrontReport(_('Service Items Purchasing Report'), "ServiceItemsPurchasingReport", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$res = getTransactions($fromsupp, $from, $to);

	$total = $total_supp = $grandtotal = 0.0;
	$total_qty = 0.0;
	$catt = $stock_description = $stock_id = $supplier_name = $supp_names = '';
	while ($trans=db_fetch($res))
	{
		
		   
			
		
		if ($supplier_name != $trans['supplier_name'])
		{
			
			
			if ($supplier_name != '')
			{
				
				$rep->NewLine(2, 3);
				$rep->TextCol(0, 1, _('Supplier Total'));
				$rep->TextCol(1, 4, $stock_description);
				$rep->TextCol(4, 5, $supplier_name);
				$rep->AmountCol(5, 7, $total_qty, get_qty_dec($stock_id));
				$rep->AmountCol(7, 8, $total_supp, $dec);
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				
				$total_supp = $total_qty = 0.0;
			}
			
			$supplier_name = $trans['supplier_name'];
		}
		
		
		if ($catt != $trans['cat_description'])
		{
			if ($catt != '')
			{
				$rep->NewLine(2, 3);
				$rep->TextCol(0, 1, _('Totals'));
				$rep->TextCol(1, 7, $catt);
				$rep->AmountCol(7, 8, $total, $dec);
				$rep->Line($rep->row - 2);
				$rep->NewLine();
				$rep->NewLine();
				$total = 0.0;
			}
			$rep->TextCol(0, 1, $trans['category_id']);
			$rep->TextCol(1, 6, $trans['cat_description']);
			$catt = $trans['cat_description'];
			$rep->NewLine();
			
			
		}
		
		
		 if ($supp_names != $trans['supplier_name'])
		{
			
			$rep->TextCol(0, 5, $trans['supplier_name']);
			$supp_names = $trans['supplier_name'];
			$rep->NewLine();
			
		}
		
		
		$curr = get_supplier_currency($trans['supplier_id']);
		$trans['price'] *= $trans['ex_rate'];
		$rep->NewLine();
		$rep->fontSize -= 1;
		$rep->TextCol(0, 1, $trans['stock_id']);
		if ($fromsupp == ALL_TEXT)
		{
			$rep->TextCol(1, 2, $trans['description'].($trans['inactive']==1 ? " ("._("Inactive").")" : ""), -1);
			$rep->TextCol(2, 3, sql2date($trans['tran_date']));
			$rep->TextCol(3, 4, $trans['supp_reference']);
			$rep->TextCol(4, 5, $trans['supplier_name']);
		}
		else
		{
			$rep->TextCol(1, 2, $trans['description'].($trans['inactive']==1 ? " ("._("Inactive").")" : ""), -1);
			$rep->TextCol(2, 3, sql2date($trans['tran_date']));
			$rep->TextCol(3, 4, $trans['supp_reference']);
		}	
		$rep->AmountCol(5, 6, $trans['qty'], get_qty_dec($trans['stock_id']));
		$rep->AmountCol(6, 7, $trans['price'], $dec);
		$amt = $trans['qty'] * $trans['price'];
		$rep->AmountCol(7, 8, $amt, $dec);
		$rep->fontSize += 1;
		$total += $amt;
		$total_supp += $amt;
		$grandtotal += $amt;
		$total_qty += $trans['qty'];
	}
	
	
	if ($supplier_name != '')
	{
		$rep->NewLine(2, 3);
		$rep->TextCol(0, 1, _('Total Supp'));
		$rep->TextCol(1, 4, $stock_description);
		$rep->TextCol(4, 5, $supplier_name);
		$rep->AmountCol(5, 7, $total_qty, get_qty_dec($stock_id));
		$rep->AmountCol(7, 8, $total_supp, $dec);
		$rep->Line($rep->row - 2);
		$rep->NewLine();
		
		
		$rep->NewLine();
	}




	$rep->NewLine(2, 3);
	$rep->TextCol(0, 1, _('Totals'));
	$rep->TextCol(1, 7, $catt);
	$rep->AmountCol(7, 8, $total, $dec);
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->NewLine(2, 1);
	$rep->TextCol(0, 7, _('Grand Total'));
	$rep->AmountCol(7, 8, $grandtotal, $dec);

	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

