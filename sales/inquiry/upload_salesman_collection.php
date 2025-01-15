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
$page_security = 'SA_SALESMAN_COLLECTION';
$path_to_root = "../..";
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

include_once($path_to_root ."/simplexls/src/SimpleXLSX.php");
use Shuchkin\SimpleXLSX;

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Upload Salesman Collections"), isset($_GET['customer_id']), false, "", $js);

//--------------------------------------------------------------------------------------
if(isset($_POST["validate_cob"]))
{
	$flag=0;
	
	$extension = end(explode(".", $_FILES["filename"]["name"])); // For getting Extension of selected file 
	$allowed_extension = array("xls", "xlsx", "csv"); //allowed extension
	
	if(in_array($extension, $allowed_extension)) //check selected file extension is present in allowed extension array
    {
		   
	$file = $_FILES["filename"]["tmp_name"]; // getting temporary source of excel file

	if ($xlsx =   SimpleXLSX::parse($file)) {
      //   echo '<h2>Parsing Result</h2>';
       //  echo '<table border="1" cellpadding="3" style="border-collapse: collapse">';

        $dim = $xlsx->dimension();
        $cols = $dim[0];
        foreach ($xlsx->readRows() as $k => $r) {
            for ($i = 0; $i < $cols; $i ++) {							
                if($k==0)
					continue;
				if($i==0)
				{					
					if(!is_customer_existed($r[ $i ]))
					{
						$flag=1;
						display_error("Customer Code : ".$r[$i]." not existed!");
						break;
					}	
				}
				$debtor_no=get_debtor_no($r[0]);
				if($r[1]!=''){
				 if(!check_customer_having_invoice($debtor_no,$r[1]))
					{
						$flag=1;
						display_error("Could not find this customer's invoice  : ".$r[0]." invoice number is".$r[1]);
						break;
					} 
					$invoice_info =get_invoice_information($debtor_no,$r[1]);
					if($r[2]>$invoice_info['total']){
						
						$flag=1;
						display_error("The amount that the salesperson collected exceeds the invoice amount. : Collected amount is".$r[2]." invoice amonut is".$invoice_info['total']);
						break;
					}
				}
					
					if(!is_sales_person_existed($r[3]))
					{
						$flag=1;
						display_error($r[3]." not existed!");
						//break;
					}					
				
								
			}
          }
    } else {
        echo SimpleXLSX::parseError();
    }

}
  if($flag==0)
  display_notification("Uploaded File is successfully validated!");
}

if(isset($_POST["add_cob"]))
{
	$flag=0;
	
	$extension = end(explode(".", $_FILES["filename"]["name"])); // For getting Extension of selected file 
	$allowed_extension = array("xls", "xlsx", "csv"); //allowed extension
	
	if(in_array($extension, $allowed_extension)) //check selected file extension is present in allowed extension array
    {
		   
	$file = $_FILES["filename"]["tmp_name"]; // getting temporary source of excel file

	if ($xlsx =   SimpleXLSX::parse($file)) {
  		   $dim = $xlsx->dimension();
        $cols = $dim[0];
        foreach ($xlsx->readRows() as $k => $r) {
	
             for ($i = 0; $i < $cols; $i ++) {	
			 	
                if($k==0)
					continue;
				
				if($i==0)
				{					
					if(!is_customer_existed($r[$i]))
					{
						$flag=1;
						display_error("Customer Code : ".$r[$i]." not existed!");
						break;
					}	
				}
				
				if(!is_sales_person_existed($r[3]))
					{
						$flag=1;
						display_error($r[3]." not existed!");
						//break;
					}		
				$debtor_no = get_debtor_no_by_vendor_code($r[0]);
				if($r[1]!=''){
				if(!check_customer_having_invoice($debtor_no,$r[1]))
					{
						$flag=1;
						display_error("Could not find this customer's invoice  : ".$r[0]." invoice number is".$r[1]);
						break;
					} 
				$invoice_info =get_invoice_information($debtor_no,$r[1]);
					if($r[2]>$invoice_info['total']){
						
						$flag=1;
						display_error("The amount that the salesperson collected exceeds the invoice amount. : Collected amount is".$r[2]." invoice amonut is".$invoice_info['total']);
						break;
					}
				}
			 }
				 if((is_customer_existed($r[0])) && $flag!=1)
				{
					$sales_person_id = get_salesperson_no_by_name($r[3]);
					$trans_no = get_next_trans_no(ST_CUSTPAYMENT);
					$debtor_no = get_debtor_no_by_vendor_code($r[0]);
					$ref = $Refs->get_next(ST_CUSTPAYMENT, null, array(
			'customer' => get_post('debtor_no'), 'date' => get_post('collected_date')));

				$payment_no = write_customer_payment($trans_no, $debtor_no, $debtor_no,$_POST['bank_account'], $_POST['collected_date'], $ref, $r[2], 0,'', 0, 0,$r[2], 0, 0,'cash','','','','','','','',$sales_person_id,$r[4]);
				if($r[1]!=''){
				$invoice_info =get_invoice_information($debtor_no,$r[1]);
				update_debtors_trans($invoice_info['trans_no'],$r[2]);
				add_cust_allocations($debtor_no,$r[2],$_POST['collected_date'],$payment_no,ST_CUSTPAYMENT,$invoice_info['trans_no'],ST_SALESINVOICE);
				}
			 
				} 
        
		}
    } else {
        echo SimpleXLSX::parseError();
    }

}
  if($flag==0)
  display_notification("File Uploaded Successfully!");
}
//------------------------------------------------------------------------------------------------

if (isset($_GET['customer_id']))
{
	$_POST['customer_id'] = $_GET['customer_id'];
}

//------------------------------------------------------------------------------------------------

start_form(true);
start_outer_table(TABLESTYLE, "width='60%'");
	table_section(1);
		  bank_accounts_list_row(_("Into Bank Account:"), 'bank_account', null, false,true); 
		  date_row(_("Collected Date :"),'collected_date');
   		 //  sales_persons_list_row(_("Sales Person:"), 'sales_person_id',$_POST['sales_person_id'],false,false);
		  file_row(_("Upload Salesman Collections ") . ":", 'filename', 'filename'); 
		  start_row();
		  submit_cells('validate_cob', _("Validate"),'',_('Validate'), 'default');
		  submit_cells('add_cob', _("Upload"),'',_('Upload'), 'default');
		  end_row();
	
	table_section(2);
	label_row(_('Sample Format:'),"<a href='".$path_to_root."/samples/SalesmanCollections.xlsx' target='_blank'>Download Here</a>");
	end_outer_table(1); // outer table

end_form();

end_page();
