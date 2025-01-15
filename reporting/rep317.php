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
$page_security = 'SA_INVENTORY_STOCK_TRANSFER_REPORT'; //'SA_STOCK_TRANSFER_REP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Stefan Sotirov, modified slightly by Joe Hunt.
// date_:	01-12-2017
// Title:	Inventory Purchasing - Transaction Based
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");


//----------------------------------------------------------------------------------------------------

print_stock_transfer();

function getTransactions($from,$to,$location)
{
	$from_date = date2sql($from);
	$to_date = date2sql($to);	
	$sql ="SELECT s.description,s.units,sm.* FROM ".TB_PREF."stock_moves sm, ".TB_PREF."stock_master s WHERE sm.tran_date >= '".$from_date."' AND sm.tran_date <= '".$to_date."' AND sm.stock_id=s.stock_id  AND sm.type=".ST_LOCTRANSFER." HAVING sm.qty <'0' ";
	if ($location != "")
		{
		$sql .= " AND sm.loc_code = ".db_escape($location);
		}
		$sql .=" ORDER BY sm.loc_code";
		
    return db_query($sql,"No transactions were returned");
}
function get_to_location_transfer($trans_no, $stock_id)
{
	$sql ="SELECT sm.loc_code as to_loc_code,sm.qty as to_qty FROM ".TB_PREF."stock_moves sm WHERE trans_no=".db_escape($trans_no)."  AND sm.type=".ST_LOCTRANSFER."  AND sm.stock_id=".db_escape($stock_id)." HAVING sm.qty >'0'";
	$res = db_query($sql,"No to transfer were returned");
	return $result = db_fetch($res);
}
//----------------------------------------------------------------------------------------------------
function print_stock_transfer()
{
    global $path_to_root;
    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $location = $_POST['PARAM_2'];
   	$orientation = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];

	if ($destination==0)
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/excel_report.inc");

	$orientation = ($orientation ? 'L' : 'L');
   
	if ($location == "")
	{
	$loc_fr= _('All');
	}
	else
	{
		$loc_fr = get_location_name($location);
	}
	
	
	$cols = array(0, 20 , 60, 120, 200, 330, 360,410,490,520);

	$headers = array(_('CB'),_('Vr. No'),_('Vr. Date'), _('Item Code'), _('Item Description'),_('UOM'),_('Tr.Out Qty.'),_('TO'),_('Tr.In Qty.'));
	
	$aligns = array('left',	'left','center','left','left','left','right','center','right');

    
     $params =   array( 0 =>$comments,
	                    1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
	                   	2 => array('text' => _('Location'), 'from' => $loc_fr, 'to' => ''));
					

    $rep = new FrontReport(_('Stock Transfer Report'), "Stock Transfer Report", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();
	$loc ='';
	$res = getTransactions($from,$to,$location);
	while ($trans=db_fetch($res))
	{	
			
			if ($loc != $trans['loc_code'])
		{
			if ($loc != '')
			{
				$rep->Line($rep->row - 2);
				$rep->NewLine(2, 3);
			}
			$loc_name = get_location_name($trans['loc_code']);
			
			$rep->TextCol(0, 2, $trans['loc_code']);
			$rep->TextCol(2, 3, $loc_name);
			
			$loc = $trans['loc_code'];
			$rep->NewLine();
		}
 			$dec = get_qty_dec($trans['stock_id']);
            
			$rep->TextCol(1, 2, $trans['reference']);
			$rep->TextCol(2, 3, sql2date($trans['tran_date']));
			$rep->TextCol(3, 4, $trans['stock_id']);
			$rep->TextCol(4, 5, $trans['description']);
			$rep->TextCol(5, 6, $trans['units']);
			$rep->AmountCol(6, 7, -$trans['qty'],$dec);
			$to_info = get_to_location_transfer($trans['trans_no'],$trans['stock_id']);
			$rep->TextCol(7, 8, $to_info['to_loc_code']);
			$rep->AmountCol(8, 9, $to_info['to_qty'],$dec);



			$rep->NewLine();
	}
	$rep->Line($rep->row - 4);
	$rep->NewLine();
	$rep->End();
}

