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

$page_security = 'SA_COMPLAINT_INQUIRY';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/complaints/includes/db/complaints_attachments_db.inc");
include($path_to_root . "/includes/ui.inc");
if (isset($_GET['dl']))
	$download_id = $_GET['dl'];
else
	$download_id = find_submit('download');

if ($download_id!= -1)
{
	$row = get_complaints_attachments($download_id);
	if ($row['filename'] != "")
	{
		if(in_ajax()) {
			$Ajax->redirect($_SERVER['PHP_SELF'].'?dl='.$download_id);
		} else {
			$type = ($row['filetype']) ? $row['filetype'] : 'application/octet-stream';	
			
    		header("Content-type: ".$type);
	    	header('Content-Length: '.$row['filesize']);
    		header('Content-Disposition: attachment; filename='.$row['filename']);
    		echo file_get_contents($path_to_root."/complaints/attachments/".$row['unique_name']);
	    	exit();
		}
	}	
}

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
simple_page_mode(true);
page(_($help_context = "Complaint Attachments"));
//-----------------------------------------------------------------------------------


if(isset($_GET['complaint_id']) && $_GET['complaint_id'] > 0 ){
	$_POST['complaint_id'] = $_GET['complaint_id'];
}

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	//initialise no input errors assumed initially before we test
	$input_error = 0;
if($Mode=='ADD_ITEM'){

	  if(empty($_FILES['filename']['name'])){
		display_error(_("Select attachment file."));
		set_focus('filename');
		return false;	
	 }
	  else if($_FILES['filename']['error'] > 0)
		{
    	if ($_FILES['filename']['error'] == UPLOAD_ERR_INI_SIZE) 
		  	display_error(_("The file size is over the maximum allowed."));
    	else
		  	display_error(_("Select attachment file."));
		}
}

if($Mode=='UPDATE_ITEM'){
	
	
	 
	  if(empty($_FILES['filename']['name'])){
		display_error(_("Select attachment file."));
		set_focus('filename');
		return false;	
	 }
	  else if($_FILES['filename']['error'] > 0)
		{
    	if ($_FILES['filename']['error'] == UPLOAD_ERR_INI_SIZE) 
		  	display_error(_("The file size is over the maximum allowed."));
    	else
		  	display_error(_("Select attachment file."));
		}
	
}	

	if ($input_error != 1) 
	{
		
    	if ($selected_id != -1) 
    	{
			
		$tmpname = $_FILES['filename']['tmp_name'];
	
		$dir =   $path_to_root ."/complaints/attachments";
		if (!file_exists($dir))
		{
			mkdir ($dir,0777);
			$index_file = "<?php\nheader(\"Location: ../index.php\");\n";
			$fp = fopen($dir."/index.php", "w");
			fwrite($fp, $index_file);
			fclose($fp);
		}

		$filename = basename($_FILES['filename']['name']);
		$filesize = $_FILES['filename']['size'];
		$filetype = $_FILES['filename']['type'];
		
		 $row = get_complaints_attachments($_POST['selected_id']);
		   
			$unique_name = $row['unique_name'];
			if ($filename && file_exists($dir."/".$unique_name))
				unlink($dir."/".$unique_name);
		$unique_name = uniqid('');

    		//save the file
		move_uploaded_file($tmpname, $dir."/".$unique_name);
		
			display_notification(_('Selected comaplaint attachment has been updated'));
			
    	} 
    	else 
    	{
			
		
		$tmpname = $_FILES['filename']['tmp_name'];
	
		$dir =  $path_to_root ."/complaints/attachments";
		if (!file_exists($dir))
		{
			mkdir ($dir,0777);
			$index_file = "<?php\nheader(\"Location: ../index.php\");\n";
			$fp = fopen($dir."/index.php", "w");
			fwrite($fp, $index_file);
			fclose($fp);
		}

		$filename = basename($_FILES['filename']['name']);
		$filesize = $_FILES['filename']['size'];
		$filetype = $_FILES['filename']['type'];
		
		 $row = get_complaints_attachments($_POST['selected_id']);
		   
			$unique_name = $row['unique_name'];
			if ($filename && file_exists($dir."/".$unique_name))
				unlink($dir."/".$unique_name);
		$unique_name = uniqid('');

		//save the file
		move_uploaded_file($tmpname, $dir."/".$unique_name);
		
    	add_complaints_attachments($_POST['complaint_id'],$filename,$unique_name,$filetype,$filesize);
		display_notification(_('New complaint attachment has been added'));
			
			meta_forward( $path_to_root.'/complaints/inquiry/complaints_inquiry.php');
    	}
		$Mode = 'RESET';
	}
	
	//return true;
} 

//-----------------------------------------------------------------------------------

function can_delete($selected_id)
{
	
	
		if (key_in_foreign_table($selected_id, 'sales_orders', 'project_id'))
	{
		display_error(_("Cannot delete this topic because Sales Orders have been created referring to it."));
		return false;
	}

	
	return true;
}


//-----------------------------------------------------------------------------------

if ($Mode == 'Delete')
{

	if (can_delete($selected_id))
	{
		
		display_notification(_('Selected attachment has been deleted'));
	}
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}
//-----------------------------------------------------------------------------------
global $project_status;
//$result = get_all_projects(check_value('show_inactive'));
start_form(true);

//-----------------------------------------------------------------------------------

start_table(TABLESTYLE2);

if ($selected_id != -1) 
{
 	if ($Mode == 'Edit') {
		//editing an existing status code
		$myrow = get_project($selected_id);
		
		
	//	display_error($project_status[$myrow["project_status"]]);
		$_POST['project_status']  = $myrow["project_status"];
	}
	hidden('selected_id', $selected_id);
    hidden('complaint_id', $_POST['complaint_id']);
    
	
} 
hidden('complaint_id', $_POST['complaint_id']);

file_row(_("Attached File") . ":", 'filename', 'filename');


end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');
echo "<br>";
echo "<br>";

end_table(1);
end_form();

//------------------------------------------------------------------------------------

end_page();

