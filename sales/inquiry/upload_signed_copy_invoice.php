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
$page_security = 'SA_SALES_INVOICE_SIGNED';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include($path_to_root . "/includes/ui.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(_($help_context = "Invoice - Signed Copy Collection"), false, false, "", $js);
//---------------------------------------------------------------------------------------

if (isset($_GET['TransNo']))
{
	$order_no = $_GET['TransNo'];
}
//---------------------------------------------------------------------------------------

if (!isset($_POST['date_']))
{
	$_POST['date_'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['date_']))
		$_POST['date_'] = end_fiscalyear();
}

function can_process()
{
	$filename = basename($_FILES['filename']['name']);
	/* if ( !in_array(strtoupper(substr($filename, strlen($filename) - 3)), array('JPG','PNG','GIF', 'PDF', 'DOC', 'ODT')))
	{
		display_error(_('Only graphics,pdf,doc and odt files are supported.'));
		return false;
	} else 
	if (!isset($_FILES['filename'])){
		display_error(_("Select attachment file."));
				return false;

	}elseif (($_FILES['filename']['error'] > 0)) {
    	if ($_FILES['filename']['error'] == UPLOAD_ERR_INI_SIZE){ 
		  	display_error(_("The file size is over the maximum allowed."));
				return false;
		}
    	else{
		  	display_error(_("Select attachment file."));
					return false;

		}
  	} elseif ( strlen($filename) > 60) {
		display_error(_("File name exceeds maximum of 60 chars. Please change filename and try again."));
				return false;

	}
	*/
	
	
	  if (strlen($_POST['uploaded_remarks']) == 0) 
	  {
		
		display_error( _('The Remarks must be entered.'));
		set_focus('uploaded_remarks');
		return false;
	  } 
	
	return true;
}

//-------------------------------------------------------------------------------------

if (isset($_POST['ADD_ITEM']) && can_process())
{
	
		
	
		$filename = basename($_FILES['filename']['name']);
		
		if(strlen($filename)>0 && $_POST['order_no']>0){
			$filename = basename($_FILES['filename']['name']);
			$tmpname = $_FILES['filename']['tmp_name'];
				$dir =  company_path()."/attachments";
			if (!file_exists($dir))
			{
				mkdir ($dir,0777);
				$index_file = "<?php\nheader(\"Location: ../index.php\");\n";
				$fp = fopen($dir."/index.php", "w");
				fwrite($fp, $index_file);
				fclose($fp);
			}
			$filesize = $_FILES['filename']['size'];
			$filetype = $_FILES['filename']['type'];
		
			$unique_name = random_id();
			
			//save the file
			move_uploaded_file($tmpname, $dir."/".$unique_name);
			
			add_attachment(ST_SALESINVOICE, $_POST['order_no'], 'Direct From Upload Signed Copy of Invoices',$filename, $unique_name, $filesize, $filetype);	
		}
	add_new_signed_copy($_POST['order_no'], $_POST['uploaded_date'], $_POST['uploaded_remarks'], $filename,  $unique_name, $filesize, $filetype);
	display_notification(_('New signed copy has been added'));
	
	update_sales_invoice_signed_copy($_POST['order_no']);
	display_notification(_('Inovice - Signed Copy Collection has been entered..!'));
	
	$path="../inquiry/customer_invoices_view.php?";
		meta_forward($path);
}



//-------------------------------------------------------------------------------------

if (get_post('_type_update')) 
{
  $Ajax->activate('_page_body');
}
//-------------------------------------------------------------------------------------


start_form(true);

start_table(TABLESTYLE2);

$existing_comments = "";
$dec = 0;


hidden('order_no',$_GET['TransNo']);

end_table();
start_table(TABLESTYLE2);
br(2);

    date_row(_("Date :"), 'uploaded_date', '', null, 0, 0, 0);
	textarea_row(_("Remarks :<b style='color:red;'>*</b>"), 'uploaded_remarks', null, 18, 5);
	file_row(_("Signed File :"), 'filename', 'filename');
end_table();
	br();
	submit_center('ADD_ITEM', _("Submit"), true, '', 'default');
	br();
end_form();
end_page();


