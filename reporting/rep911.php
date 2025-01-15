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

$page_security = 'SA_COMPLAINT_HISTORY_REP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	COMPLAINT INQUIRY
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");

//----------------------------------------------------------------------------------------------------

print_complaint_report();

function getTransactions($from_date,$to_date,$customer_id,$mobile_number='',$status=0,$reference='')
{
	
	$from_date = date2sql($from_date);
	$to_date = date2sql($to_date);
	
	$sql = "SELECT * FROM ".TB_PREF."proj_customer_complaint WHERE 
	date>= '$from_date' AND date <= '$to_date'";
	
	if ($customer_id != ALL_TEXT)
			$sql .= " AND customer_id = ".db_escape($customer_id);
		
	if($mobile_number)
    {
    
      $sql .= " AND mobile_number LIKE ".db_escape($mobile_number);
    }	
	
	if($status==1)
    {
      $sql .= " AND inactive= '0'" ;
    }
	elseif($status==2)
    {
       $sql .= " AND inactive= '2'" ;
    }elseif($status==3)
    {
      $sql .= " AND inactive= '1'" ;
    }
	
	if($reference)
    {
    
      $sql .= " AND reference LIKE ".db_escape($reference);
    }
		
    $sql .= " GROUP BY id ORDER BY id ";
	
	
	
    return db_query($sql,"No transactions were returned");
}



function get_project_ids()
{
	$sql="SELECT id,project_name FROM ".TB_PREF."projects WHERE 1";
	return db_query($sql,"No projects were returned");
	
}


function get_complaint_user_name($id)
{
	$sql = "SELECT * FROM ".TB_PREF."users WHERE id=".db_escape($id);
    
	$result = db_query($sql, "could not get user $id");

	return db_fetch($result);
}


function get_complaint_service_engineer_remarks($complaint_id)
{
	$sql = "SELECT comment,technician_name FROM ".TB_PREF."proj_complaint_replies WHERE complaint_id=".db_escape($complaint_id)." ORDER BY id DESC LIMIT 1";
	

	$result = db_query($sql, "could not get user $complaint_id");

	return db_fetch($result);
}
//----------------------------------------------------------------------------------------------------

function print_complaint_report()
{
    global $path_to_root, $SysPrefs,$project_status;

	
    $from_date     = $_POST['PARAM_0'];
   	$to_date       = $_POST['PARAM_1'];
   	$customer_id   = $_POST['PARAM_2'];
	$mobile_number = $_POST['PARAM_3'];
	$status        = $_POST['PARAM_4'];
	$reference        = $_POST['PARAM_5'];
	$orientation   = $_POST['PARAM_6'];
	$destination   = $_POST['PARAM_7'];
	
	//$destination=1;
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	
    $dec = user_price_dec();

	//$orientation = ($orientation ? 'P' : 'L');
      $orientation =  'L';
	
	
   if ($customer_id == ALL_TEXT)
		$cust = _('All');
	else
		$cust = get_customer_name($customer_id);
	
	
	if($status==0)
	{
     $comp_status = "All";
    }
	elseif($status==1)
	{
     $comp_status = "Open";
    }
	elseif($status==2)
	{
     $comp_status = "Inprogress";
    }		
		elseif($status==3)
	{
     $comp_status = "Closed";
    }		
		
	
	

    //$cols = array(0,100,200,300,400,500,600,700,800,900);
	
	$cols = array(0,40,85,180,230,270,370,450,500,540,600);

	$headers = array(_('Date'),_('Complaint No'),  _('Customer'),  
	_('Complaint Against'), _('Reference'),
	_('Product'),_('Complaint Summary'),_('Attended By'),  _('Remarks'),_('Status'));

	$aligns = array('left',	'left',	'left','left','left','left','left','left','left','left');
	
    $params =   array( 	0 => '',
    				    1 => array('text' => _('Date'), 'from' => $from_date, 'to' => $to_date),
						2 => array('text' => _('Customer'), 'from' => $cust, 'to' => ''),
						3 => array('text' => _('Mobile Number'), 'from' => $mobile_number, 'to' => ''),
						4 => array('text' => _('Status'), 'from' => $comp_status, 'to' => ''),
						5 => array('text' => _('Reference'), 'from' => $reference, 'to' => ''),);

    $rep = new FrontReport(_('Complaint History Report'), "ComplaintHistoryReport", user_pagesize(), 7, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

    $summary_start_row = $rep->bottomMargin + (5 * $rep->lineHeight);

	$res = getTransactions($from_date, $to_date, $customer_id, $mobile_number, $status, $reference);
	
	$k=1;
	while ($trans=db_fetch($res))
	{
		global $complaint_against_types;
		
		$complaint_customer = get_customer_name($trans['customer_id']);
		$complaint_user_name = get_complaint_user_name($trans['current_user_id']);
		
		$service_engineer_remarks = get_complaint_service_engineer_remarks($trans['id']);
		
		if($trans['inactive']==0){
			$complaint_status = "Open";
		}
		else if($trans['inactive']==1){
			$complaint_status = "Closed";
		}
		else{
			$complaint_status = "Inprogress";
		}
			
			$rep->TextCol(0, 1, sql2date($trans['date']));
			$rep->TextCol(1, 2, $trans['complaint_number']);
			$rep->TextCol(2, 3, $complaint_customer);
			$rep->TextCol(3, 4, $complaint_against_types[$trans['complaint_against']]);
			$rep->TextCol(4, 5, $trans['reference']);

			if ($destination){
		    $rep->TextCol(5, 6, $trans['subject']);	
		    }
		    else{
		    $oldrow   = $rep->row;
		    $rep->TextColLines(5, 6, $trans['subject'], -2);
		    $newrow   = $rep->row;
            $product_subject_row_no = $newrow;
			$rep->row = $oldrow;
		    }
			
			if ($destination){
		    $rep->TextCol(6, 7, $trans['description']);	
		    }
		    else{
		    $oldrow   = $rep->row;
		    $rep->TextColLines(6, 7, $trans['description'], -2);
		    $newrow   = $rep->row;
            $product_description_row_no = $newrow;
		    $rep->row = $oldrow;
		    }
			$rep->TextCol(7, 8, $service_engineer_remarks['technician_name']);
			//$rep->TextCol(8, 9, $service_engineer_remarks['comment']);
            $rep->TextCol(8, 9, "");
			$rep->TextCol(9, 10, $complaint_status);
			
			if ($destination){
		    $rep->NewLine();
		    }
		    else{
		    if($product_subject_row_no<=$product_description_row_no){
              $rep->row = $product_subject_row_no;
			 
             }
            else if($product_description_row_no<=$product_subject_row_no ){
               $rep->row = $product_description_row_no;
             }
		    }
			
			if($trans['inactive']!=0){
			$rep->TextCol(1, 2, _("User Name"));
			$rep->TextCol(2, 3, _("Date"));
			$rep->TextCol(3, 4, _("Handled By"));
			$rep->TextCol(4, 7, _("Remarks"));
			}
			
			$replies_sql = "SELECT replies.id,replies.comment,replies.date,replies.current_user_id,replies.technician_name FROM ".TB_PREF."proj_complaint_replies replies,  
            ".TB_PREF."proj_customer_complaint complaint 
            WHERE replies.complaint_id=complaint.id 
			AND replies.complaint_id=".$trans['id']." 
            ORDER BY replies.id";
			
			$replies_list= db_query($replies_sql,"No transactions were returned");
			
			while ($replies=db_fetch($replies_list))
	        {
			
			$rep->NewLine();
			
            $replies_user = get_complaint_user_name($replies['current_user_id']);		
			$rep->TextCol(1, 2, $replies_user['real_name']);
		    $rep->TextCol(2, 3, sql2date($replies['date']));
			$rep->TextCol(3, 4, $replies['technician_name']);
           
			$rep->TextCol(4, 10, $replies['comment']);
			
			}

            $rep->Line($rep->row - 4);
			$rep->NewLine();

         if ($rep->row < $summary_start_row)
			$rep->NewPage();
	}
	
	$rep->NewLine();
	//$rep->Line($rep->row - 2);
	$rep->NewLine();
    $rep->End();
}

