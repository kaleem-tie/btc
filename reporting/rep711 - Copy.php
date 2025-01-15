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
$page_security = 'SA_GLANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Audit Trail
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/ui/ui_view.inc");
include_once($path_to_root . "/admin/db/transactions_db.inc");
//----------------------------------------------------------------------------------------------------

print_series_of_documents();

function getTransactions()
{
	$sql = "SELECT trans_type FROM ".TB_PREF."reflines WHERE inactive=0";
    return db_query($sql,"No transactions were returned");
}


function get_sql_for_trans_type_from_reference($filtertype, $from, $to)
{
	$db_info = get_systype_db_info($filtertype);

	if ($db_info == null)
		return "";

	$table_name    = $db_info[0];
	$type_name     = $db_info[1];
	$trans_no_name = $db_info[2];
	$trans_ref     = $db_info[3];
	$trans_date    = $db_info[4];


    $fromdate = date2sql($from);
	$todate = date2sql($to);

	$sql = "SELECT ";

	if ($trans_ref)
		$sql .= " t.$trans_ref as ref ";
	else
		$sql .= " r.reference as ref";
	
 	
	$sql .= " FROM $table_name t LEFT JOIN ".TB_PREF."voided v ON"
		." t.$trans_no_name=v.id AND v.type=".db_escape($filtertype);

	$sql .= " WHERE ISNULL(v.`memo_`) 
         AND t.`$trans_date` between ".db_escape($fromdate)." and ".db_escape($todate)." 
         AND t.$trans_ref!='auto'";

	
	$sql .= " AND t.`$type_name` = ".db_escape($filtertype);

	// the ugly hack below is necessary to exclude old gl_trans records lasting after edition,
	// otherwise old data transaction can be retrieved instead of current one.
	if ($table_name==TB_PREF.'gl_trans')
		$sql .= " AND t.`amount` <> 0";
    
    
    if($filtertype==16){
     $sql .= " ORDER BY t.$trans_date,t.$trans_no_name ASC LIMIT 1";
    }
    else{
	$sql .= " ORDER BY t.$trans_date ASC LIMIT 1";
    } 
    

	$result = db_query($sql,"No transactions were returned");
	$row = db_fetch($result);
	if ($row === false)
	    return 0;
	return $row['ref'];
}


function get_sql_for_trans_type_to_reference($filtertype, $from, $to)
{
	$db_info = get_systype_db_info($filtertype);

	if ($db_info == null)
		return "";

	$table_name    = $db_info[0];
	$type_name     = $db_info[1];
	$trans_no_name = $db_info[2];
	$trans_ref     = $db_info[3];
	$trans_date    = $db_info[4];


    $fromdate = date2sql($from);
	$todate = date2sql($to);

	$sql = "SELECT ";

	if ($trans_ref)
		$sql .= " t.$trans_ref as ref ";
	else
		$sql .= " r.reference as ref";
	
 	
	$sql .= " FROM $table_name t LEFT JOIN ".TB_PREF."voided v ON"
		." t.$trans_no_name=v.id AND v.type=".db_escape($filtertype);

	$sql .= " WHERE ISNULL(v.`memo_`) 
         AND t.`$trans_date` between ".db_escape($fromdate)." and ".db_escape($todate)." AND t.$trans_ref!='auto'";

	
	$sql .= " AND t.`$type_name` = ".db_escape($filtertype);

	// the ugly hack below is necessary to exclude old gl_trans records lasting after edition,
	// otherwise old data transaction can be retrieved instead of current one.
	if ($table_name==TB_PREF.'gl_trans')
		$sql .= " AND t.`amount` <> 0";

	//$sql .= " GROUP BY ".($type_name ? "t.$type_name," : '')." t.$trans_no_name";
	$sql .= " ORDER BY t.$trans_date DESC LIMIT 1";
    
  

	$result = db_query($sql,"No transactions were returned");
	$row = db_fetch($result);
	if ($row === false)
	    return 0;
	return $row['ref'];
}


function get_sql_for_purchase_grn_from_reference($filtertype, $from, $to)
{

    $fromdate = date2sql($from);
	$todate = date2sql($to);

  $sql = " SELECT reference as ref FROM ".TB_PREF."grn_batch 
  WHERE delivery_date between ".db_escape($fromdate)." and ".db_escape($todate)." 
  AND reference!='auto'  ORDER BY delivery_date ASC LIMIT 1";
   
    $result = db_query($sql,"No transactions were returned");
	$row = db_fetch($result);
	if ($row === false)
	    return 0;
	return $row['ref'];
}

function get_sql_for_purchase_grn_to_reference($filtertype, $from, $to)
{

    $fromdate = date2sql($from);
	$todate = date2sql($to);

  $sql = " SELECT reference as ref FROM ".TB_PREF."grn_batch 
  WHERE delivery_date between ".db_escape($fromdate)." and ".db_escape($todate)." 
  AND reference!='auto'  ORDER BY delivery_date DESC LIMIT 1";

    $result = db_query($sql,"No transactions were returned");
	$row = db_fetch($result);
	if ($row === false)
	    return 0;
	return $row['ref'];
}

//Inventory GRN
function get_sql_for_inventory_grn_from_reference($filtertype, $from, $to)
{

    $fromdate = date2sql($from);
	$todate = date2sql($to);

  $sql = " SELECT reference as ref FROM ".TB_PREF."inv_grn_batch 
  WHERE delivery_date between ".db_escape($fromdate)." and ".db_escape($todate)." 
  AND reference!='auto'  ORDER BY delivery_date ASC LIMIT 1";
   
    $result = db_query($sql,"No transactions were returned");
	$row = db_fetch($result);
	if ($row === false)
	    return 0;
	return $row['ref'];
}

function get_sql_for_inventory_grn_to_reference($filtertype, $from, $to)
{

    $fromdate = date2sql($from);
	$todate = date2sql($to);

  $sql = " SELECT reference as ref FROM ".TB_PREF."inv_grn_batch 
  WHERE delivery_date between ".db_escape($fromdate)." and ".db_escape($todate)." 
  AND reference!='auto'  ORDER BY delivery_date DESC LIMIT 1";

    $result = db_query($sql,"No transactions were returned");
	$row = db_fetch($result);
	if ($row === false)
	    return 0;
	return $row['ref'];
}


function get_sql_for_dimension_from_reference($filtertype, $from, $to)
{

    $fromdate = date2sql($from);
	$todate = date2sql($to);

  $sql = " SELECT reference as ref FROM ".TB_PREF."dimensions 
  WHERE date_ between ".db_escape($fromdate)." and ".db_escape($todate)." 
  AND reference!='auto'  ORDER BY date_ ASC LIMIT 1";
   
    $result = db_query($sql,"No transactions were returned");
	$row = db_fetch($result);
	if ($row === false)
	    return 0;
	return $row['ref'];
}

function get_sql_for_dimension_to_reference($filtertype, $from, $to)
{

    $fromdate = date2sql($from);
	$todate = date2sql($to);

  $sql = " SELECT reference as ref FROM ".TB_PREF."dimensions 
  WHERE date_ between ".db_escape($fromdate)." and ".db_escape($todate)." 
  AND reference!='auto'  ORDER BY date_ DESC LIMIT 1";

    $result = db_query($sql,"No transactions were returned");
	$row = db_fetch($result);
	if ($row === false)
	    return 0;
	return $row['ref'];
}
//----------------------------------------------------------------------------------------------------

function print_series_of_documents()
{
    global $path_to_root, $systypes_array;

    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
	$comments = $_POST['PARAM_2'];
	$orientation = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
   

    $cols = array(0, 240, 400, 520);

    $headers = array(_('Transaction Type'), _('From Reference'), _('To Reference'));

    $aligns = array('left', 'left', 'left');

	
    $params =   array( 	0 => $comments,
	                    1 => array('text' => _('Period'), 'from' => $from,'to' => $to));

    $rep = new FrontReport(_('Series of Documents'), "SeriesofDocuments", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

    $trans = getTransactions();

	$tot_amount = 0;
    while ($myrow=db_fetch($trans))
    {
     
     
     $rep->TextCol(0, 1, $systypes_array[$myrow['trans_type']]);

     if (!in_array($myrow['trans_type'], array(ST_SUPPRECEIVE,ST_DIMENSION,
     ST_INVSUPPRECEIVE))){
    $from_reference = get_sql_for_trans_type_from_reference($myrow['trans_type'], $from, $to);
    $to_reference = get_sql_for_trans_type_to_reference($myrow['trans_type'], $from, $to);
    }
    else if($myrow['trans_type']==ST_SUPPRECEIVE){
     $from_reference = get_sql_for_purchase_grn_from_reference($myrow['trans_type'], $from, $to);
     $to_reference = get_sql_for_purchase_grn_to_reference($myrow['trans_type'], $from, $to);
    }
    else if($myrow['trans_type']==ST_INVSUPPRECEIVE){
     $from_reference = get_sql_for_inventory_grn_from_reference($myrow['trans_type'], $from, $to);
     $to_reference = get_sql_for_inventory_grn_to_reference($myrow['trans_type'], $from, $to);
    }
    else if($myrow['trans_type']==ST_DIMENSION){
     $from_reference = get_sql_for_dimension_from_reference($myrow['trans_type'], $from, $to);
     $to_reference = get_sql_for_dimension_to_reference($myrow['trans_type'], $from, $to);
    }

       
        $rep->TextCol(1, 2, $from_reference);
        $rep->TextCol(2, 3, $to_reference);
       
	   $rep->NewLine(1);
	}
        
	$rep->Line($rep->row  - 4);
    $rep->End();
}

