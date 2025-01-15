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
//display_error("hie");die;
$page_security = 'SA_SAL_MAN_SALES_REP';  //'SA_SALESBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.4 $
// Creator:		Joe Hunt, boxygen
// date_:		2014-05-13
// Title:		Inventory Valuation
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

//----------------------------------------------------------------------------------------------------

print_month_sales_report();

function getSalesPersons()
{
	$sql = "SELECT * FROM ".TB_PREF."salesman ";
	return db_query($sql, "Cant retrieve sales man");
}

function get_salesman_sales($start_date_,$end_date_,$sales_man)
{
	
 	$start_date = date2sql($start_date_);
	$end_date = date2sql($end_date_);

	$sql =  "SELECT SUM(((dtd.quantity*dtd.unit_price))*dt.rate * IF(dt.type=10,1,-1)) AS sale_price,
	                SUM(dtd.disc_amount* IF(dt.type=10,1,-1)) AS discount,
                    SUM(dtd.quantity* IF(dt.type=10,1,-1)) AS qty,
                    SUM(dtd.quantity*dtd.standard_cost* IF(dt.type=10,1,-1)) AS cost_price  					
			FROM ".TB_PREF."debtor_trans_details dtd,".TB_PREF."debtor_trans dt 
		WHERE dt.trans_no=dtd.debtor_trans_no 
		AND dt.type=dtd.debtor_trans_type and dt.type in (10,11) and dtd.debtor_trans_type in (10,11) 
		AND dt.tran_date between '$start_date' AND '$end_date'";
		
		if ($sales_man != 0){
			
			$sql .= " AND dt.sales_person_id = ".db_escape($sales_man);
		}
		/*if($vertical_type!=0)
		$sql.=" AND dt.sales_item_vertical_type=".db_escape($vertical_type);
	
	    if ($included_warranty==0){
		$sql .= " AND dt.ov_amount!=0 ";
	    }*/
		
		//display_error($sql);
		
		$result= db_query($sql,"No transactions were returned");
		$row=db_fetch($result);	

  return $row;		
}


//----------------------------------------------------------------------------------------------------

function print_month_sales_report()
{
    global $path_to_root, $SysPrefs;

	$from = $_POST['PARAM_0'];
	$to=$_POST['PARAM_1'];
   // $vertical_type = $_POST['PARAM_2'];
	//$included_warranty = $_POST['PARAM_3'];
	$orientation = $_POST['PARAM_2'];
	$destination = $_POST['PARAM_3'];
	
	/*if($vertical_type==0)
	$vertical='ALL';
	else
	$vertical= $sales_item_vertical_types[$vertical_type];

    if ($included_warranty == 0)
		$warranty = _('No');
	else
		$warranty = _('Yes');*/
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	
	$orientation = ($orientation ? 'L' : 'P');
	
	$dec = user_price_dec();
	
	$cols = array(0,25,125,200,250,320,390,460,510);

	$headers = array(_('S No'), 'Sales Person', _('Gross Amt.'),_('Discount'),_('Net Amt'), 
	_('Cost Amount'),('Profit'), _('Prof. %'));

	$aligns = array('left',	'left',	'right', 'right', 'right', 'right',	'right', 'right');

    $params =   array( 	0 => $comments,
    					1 => array('text' => _('End Date'), 'from' => $from,'to' =>$to));
    				  //  2 => array('text' => _('Vertical Type'), 'from' => $vertical, 'to' => ''),
						//3 => array('text' => _('Included Warranty'), 'from' => $warranty, 'to' => '')

    $rep = new FrontReport(_('Salesman Sales Report (With Profit)'), "SalesmanSalesReportWithProfit", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();
    $i=1;
	$res = getSalesPersons();
	$total_qty=$total_sale=$total_cost=$total_profit=0;
	while ($salesPerson=db_fetch($res))
	{
		
	$sale_info=get_salesman_sales($from,$to,$salesPerson['salesman_code']);
		
		if ($sale_info['sale_price'] == 0) continue;
	   else{	 
		
		$rep->TextCol(0, 1,	$i);
		$rep->TextCol(1, 2,	$salesPerson['salesman_name']);
		
		
		//$rep->AmountCol(2, 3,$sale_info['qty'],0);
		//$total_qty+=$sale_info['qty'];
				
		$rep->AmountCol(2, 3,$sale_info['sale_price'],$dec);
		$total_sale+=$sale_info['sale_price'];
		$rep->AmountCol(3, 4,$sale_info['discount'],$dec);
		$total_discount+=$sale_info['discount'];
		$net_amount=$sale_info['sale_price']-$sale_info['discount'];
		$rep->AmountCol(4, 5,$net_amount,$dec);
		$total_net_amount+=$net_amount;
		$rep->AmountCol(5, 6,$sale_info['cost_price'],$dec);
		$total_cost+=$sale_info['cost_price'];
		$profit=$net_amount-$sale_info['cost_price'];
		$rep->AmountCol(6,7,$profit,$dec);
		$total_profit+=$profit;
		
		//display_error($profit);
		
		if($profit == 0){
		$rep->AmountCol(7,8,(0),$dec);
		}else{		
		$rep->AmountCol(7,8,($profit/$net_amount)*100,$dec);
		}
		//$rep->AmountCol(7,8,('1'),$dec);
		$i++;
		$rep->NewLine();
		}
	 
	}	
	$rep->NewLine(2); 
		$rep->Line($rep->row  - 4);
		$rep->NewLine(2);
		$rep->TextCol(0, 2, "Total");
		//$rep->AmountCol(2, 3,$total_qty,$dec);
		$rep->AmountCol(2, 3,$total_sale,$dec);
		$rep->AmountCol(3, 4,$total_discount,$dec);
		$rep->AmountCol(4, 5,$total_net_amount,$dec);
		$rep->AmountCol(5, 6,$total_cost,$dec);
		$rep->AmountCol(6, 7,$total_profit,$dec);
		$rep->AmountCol(7, 8,($total_profit/$total_net_amount)*100,$dec);
    $rep->End();
}

