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
$page_security = 'SA_UNALLOC_SUPP_TRANS_REP';

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
include_once($path_to_root . "/gl/includes/gl_db.inc");


//----------------------------------------------------------------------------------------------------

print_unallocated_supplier_transactions();

function get_transactions($fromsupp=0,$reg_type=0)
{
	
  $sql = "SELECT
		trans.type,
		trans.trans_no,
		IF(trans.supp_reference='',trans.reference,trans.supp_reference) as reference,
 		trans.tran_date,
		supplier.supp_name, 
		supplier.curr_code, 
		ov_amount+ov_gst+ov_discount+freight_cost+additional_charges+packing_charges+other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff AS Total,
		trans.alloc,
		trans.due_date,
		trans.supplier_id,
		supplier.address,
		round(abs(ov_amount+ov_gst+ov_discount+freight_cost+additional_charges+packing_charges+other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff)-alloc,6) <= 0 AS settled
	 FROM "
	 	.TB_PREF."supp_trans as trans, "
		.TB_PREF."suppliers as supplier"
	." WHERE trans.supplier_id=supplier.supplier_id
		AND type IN(".ST_SUPPAYMENT.",".ST_SUPPCREDIT.",".ST_BANKPAYMENT.",".ST_JOURNAL.") AND (trans.ov_amount < 0)";
	
	$sql .= " AND (round(abs(ov_amount+ov_gst+ov_discount++freight_cost+additional_charges+packing_charges+other_charges+freight_tax+additional_tax+packing_tax+other_tax+ov_roundoff)-alloc,6) > 0)";

	if ($fromsupp != ALL_TEXT)
		$sql .= " AND supplier.supplier_id = ".db_escape($fromsupp);
	
	 if($reg_type == 1){
			$sql .= " AND supplier.curr_code='OMR'";
	   }	
       elseif($reg_type == 2){
            $sql .= " AND supplier.curr_code!= 'OMR'";
	   }		
	
	return db_query($sql,"No transactions were returned");
}


//----------------------------------------------------------------------------------------------------

function print_unallocated_supplier_transactions()
{
    	global $path_to_root, $systypes_array;
    	
		
    	$fromsupp    = $_POST['PARAM_0'];
		$reg_type    = $_POST['PARAM_1'];
    	$orientation = $_POST['PARAM_2'];
	    $destination = $_POST['PARAM_3'];
		
		
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'P' : 'L');
    

	if ($fromsupp == ALL_TEXT)
		$supp = _('All');
	else
		$supp = get_supplier_name($fromsupp);
	
	if ($reg_type == 0)
		 $rg_type ="All";
	else if ($reg_type == 1)
		 $rg_type ="Local";
	else if ($reg_type == 2)
		 $rg_type ="Import";
	
    	
	$dec = user_price_dec();
	
	
	$cols = array(0, 80,100,160,230,380,410,460,540);

    $headers = array(_('Transaction Type'), _('#'), _('Reference'), _('Date'),
                   _('Supplier'), _('Currency'), _('Total'),  _('Left to Allocate'));
	            
				
    $aligns = array('left','left','left','left','left','left','right','right');
	

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Supplier'), 'from' => $supp,   	'to' => ''),
						2 => array('text' => _('Type'), 'from' => $rg_type,'to' => ''));

    $rep = new FrontReport(_('Unallocated Supplier Transactions'), 
      "UnallocatedSupplierTransactions", user_pagesize(), 9, $orientation);
	
    if ($orientation == 'L')
    	recalculate_cols($cols);
	
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();


  $result=get_transactions($fromsupp,$reg_type);
  $k=1;
  $total_value = $total_alloc = 0;

 
	while ($myrow = db_fetch($result))
	{
		$total =  price_format(-$myrow["Total"]);
       
        $amount_left =  price_format($myrow['type'] == ST_JOURNAL ?  abs($myrow["Total"])-$myrow["alloc"] : -$myrow["Total"]-$myrow["alloc"]);
      
		
	    $rep->TextCol(0, 1, $systypes_array[$myrow['type']]);
		$rep->TextCol(1, 2, $myrow['trans_no']);
		$rep->TextCol(2, 3, $myrow['reference']);
		$rep->TextCol(3, 4, sql2date($myrow['tran_date']));
		if ($destination){
		$rep->TextCol(4, 5, $myrow["supp_name"]);	
		}
		else{
		$oldrow = $rep->row;
		$rep->TextColLines(4, 5, $myrow["supp_name"], -2);
		$newrow = $rep->row;
		$rep->row = $oldrow;
		}
        
		$rep->TextCol(5, 6, $myrow['curr_code']);	
		$rep->TextCol(6, 7, $total);
		$rep->TextCol(7, 8, $amount_left);
        
		if ($destination){
		$rep->NewLine();
		}
		else{
		$rep->row = $newrow;
		}
		$rep->NewLine();
		$k++;

       $total_value +=$myrow['Total']; 
       $total_alloc +=$myrow['alloc'];
	}
    
    $rep->Line($rep->row  - 4);
	$rep->NewLine(2);
	//$rep->TextCol(8, 9, 'Total');
	//$rep->AmountCol(7, 8, $total_value,3);
	$rep->NewLine();
    $rep->End();
}

