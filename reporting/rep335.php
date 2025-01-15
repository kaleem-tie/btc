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
$page_security = 'SA_INVENTORY_STOCK_MOVEMENT_REPORT'; 
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Jujuk
// date_:	2011-05-24
// Title:	Stock Movements
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui/ui_input.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/includes/ui.inc");

//----------------------------------------------------------------------------------------------------

inventory_movements();


function get_item_name($item)
{
	$sql = "SELECT description FROM ".TB_PREF."stock_master
		WHERE stock_id=".db_escape($item);
   
	$result = db_query($sql, "could not retreive the item name for $stock_id");

	if (db_num_rows($result) == 1)
	{
		$row = db_fetch_row($result);
		return $row[0];
	}

	display_db_error("could not retreive the item name for $stock_id", $sql, true);
}

function getTransUser($trans_no)
{
	$sql = "SELECT users.user_id from ".TB_PREF."users users,".TB_PREF."audit_trail at where at.user=users.id and type=17 and trans_no=".db_escape($trans_no);

	$result=db_query($sql,"No transactions were returned");
	$row=db_fetch_row($result);
	return $row[0];
}


function get_qoh_on_date_rep($stock_id, $location=null, $date_=null)
{
    if ($date_ == null)
        $date_ = Today();

     $date = date2sql($date_);
     $sql = "SELECT SUM(qty)
     	FROM ".TB_PREF."stock_moves st
   		LEFT JOIN ".TB_PREF."voided v ON st.type=v.type AND st.trans_no=v.id
          WHERE ISNULL(v.id)
          AND stock_id=".db_escape($stock_id)."
          AND tran_date <= '$date'"; 

    if ($location != 'all')
        $sql .= " AND loc_code = ".db_escape($location);

    
    $result = db_query($sql, "QOH calculation failed");

    $myrow = db_fetch_row($result);

    $qoh =  $myrow[0];
		return $qoh ? $qoh : 0;
}

function get_stock_movement_transactions($stock_id, $StockLocation,	$BeforeDate, $AfterDate)
{
	$before_date = date2sql($BeforeDate);
	$after_date = date2sql($AfterDate);
	
  	$sql = "SELECT move.*, IF(ISNULL(supplier.supplier_id), debtor.name, supplier.supp_name) name,
		IF(move.type=".ST_SUPPRECEIVE.", grn.reference, IF(move.type=".ST_CUSTCREDIT.", cust_trans.reference, move.reference)) reference";

	if($StockLocation== 'all') {
		 $sql .= ", move.loc_code";
	}
  	$sql.=    " FROM ".TB_PREF."stock_moves move
				LEFT JOIN ".TB_PREF."supp_trans credit ON credit.trans_no=move.trans_no AND credit.type=move.type
				LEFT JOIN ".TB_PREF."grn_batch grn ON grn.id=move.trans_no AND move.type=".ST_SUPPRECEIVE."
				LEFT JOIN ".TB_PREF."suppliers supplier ON IFNULL(grn.supplier_id, credit.supplier_id)=supplier.supplier_id
				LEFT JOIN ".TB_PREF."debtor_trans cust_trans ON cust_trans.trans_no=move.trans_no AND cust_trans.type=move.type
				LEFT JOIN ".TB_PREF."debtors_master debtor ON cust_trans.debtor_no=debtor.debtor_no
		WHERE";

  	if ($StockLocation!= 'all') {
    	$sql.= " move.loc_code=".db_escape($StockLocation)." AND";
	}

	$sql.= " move.tran_date >= '". $after_date . "'
		AND move.tran_date <= '" . $before_date . "'
		AND move.stock_id = ".db_escape($stock_id) . " ORDER BY move.tran_date, move.trans_id";
		

  	return db_query($sql, "could not query stock moves");
}

//----------------------------------------------------------------------------------------------------

function inventory_movements()
{
    global $path_to_root,$fa_systypes_array,$systypes_array;

   
    $after_date   = $_POST['PARAM_0'];
    $before_date     = $_POST['PARAM_1'];
    $category    = $_POST['PARAM_2'];
	$subcategory = $_POST['PARAM_3'];
	$item        = $_POST['PARAM_4'];
	$location    = $_POST['PARAM_5'];
    $comments    = $_POST['PARAM_6'];
	$orientation = $_POST['PARAM_7'];
	$destination = $_POST['PARAM_8'];
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
	
	$dec = user_price_dec();
	
	
    if($category==-1){
		display_error( _('Category should be selected'));
		set_focus('category');
		return false;
	}
	
	if($subcategory==ALL_TEXT){
		display_error( _('SubCategory should be selected'));
		set_focus('subcategory');
		return false;
	}
	

	
	
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
	
	if ($item == ALL_NUMERIC)
		$item = 0;
	if ($item == 0)
		$itm = _('All');
	else
		$itm = get_item_name($item);
	
	if ($location == ALL_TEXT)
		$location = 'all';
	if ($location == 'all')
		$loc = _('All');
	else
		$loc = get_location_name($location);
	


	$cols = array(0, 90, 115,180, 220,270,370,420,470,550);

	$headers = array(_('Type'), _('#'),_('Reference'), _('Location'),_('Date'),	
	_('Detail'), _('Qty In'), _('Qty Out'), _('Qty On Hand'));

	$aligns = array('left',	'left',	'left','left',	'left', 'left', 
	'right', 'right', 'right');

    $params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $after_date, 'to' => $before_date),
    				    2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
						3 => array('text' => _('Sub Category'), 'from' => $subcat, 'to' => ''),
						4 => array('text' => _('Item Name'), 'from' => $itm, 'to' => ''),
						5 => array('text' => _('Location'), 'from' => $loc, 'to' => ''));

    $rep = new FrontReport(_('Single Stock Movement Report'), "SingleStockMovementReport", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();




$before_qty = get_qoh_on_date_rep($item, $location, add_days($after_date, -1));

$after_qty = $before_qty;


$result = get_stock_movement_transactions($item, $location,	$before_date, $after_date);

$j = 1;
$k = 0; //row colour counter

$total_in = 0;
$total_out = 0;
    $rep->SetFont('helvetica', 'B', 9);
    $rep->TextCol(3 , 6, _("Quantity on hand before ") . $after_date);
	$rep->TextCol(8, 9, $before_qty);
	$rep->SetFont('', '', 0);
	
	
	$rep->NewLine(2);
	while ($myrow=db_fetch($result))
	{
		
		$trandate = sql2date($myrow["tran_date"]);
		
		
		$type_name = $systypes_array[$myrow["type"]];
	
	    if ($myrow["qty"] > 0)
	{
		$quantity_formatted = number_format2($myrow["qty"], $dec);
		$total_in += $myrow["qty"];
	}
	else
	{
		$quantity_formatted = number_format2(-$myrow["qty"], $dec);
		$total_out += -$myrow["qty"];
	}
	$after_qty += $myrow["qty"];
	
	
	
	$rep->TextCol(0, 1, $type_name);
	$rep->TextCol(1, 2, $myrow["trans_no"]);
	$rep->TextCol(2, 3, $myrow["reference"]);
    $rep->TextCol(3, 4, $myrow["loc_code"]);
	$rep->TextCol(4, 5, $trandate);
	$rep->TextCol(5, 6, $myrow["name"]);
	
	$rep->TextCol(6, 7,(($myrow["qty"] >= 0) ? $quantity_formatted : ""));
	$rep->TextCol(7, 8,(($myrow["qty"] < 0) ? $quantity_formatted : ""));
	$rep->TextCol(8, 9, $after_qty);
	 $j++;
	 if ($j == 12)
	 {
		$j = 1;
		table_header($th);
	 }
		
	 $rep->NewLine();
	}
	
	$rep->Line($rep->row  - 2);
	$rep->NewLine();
	
	$rep->SetFont('helvetica', 'B', 9);
	$rep->TextCol(3, 6, _("Quantity on hand after ") . $before_date);
	$rep->TextCol(6, 7, $total_in);
	$rep->TextCol(7, 8, $total_out);
	$rep->TextCol(8, 9, $after_qty);
	$rep->SetFont('', '', 0);
	
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

