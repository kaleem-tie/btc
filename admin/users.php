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
$page_security = 'SA_USERS';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Users"));

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/admin/db/users_db.inc");

simple_page_mode(true);
//-------------------------------------------------------------------------------------------------

function can_process($new) 
{

	if (strlen($_POST['user_id']) < 4)
	{
		display_error( _("The user login entered must be at least 4 characters long."));
		set_focus('user_id');
		return false;
	}

	if (!$new && ($_POST['password'] != ""))
	{
    	if (strlen($_POST['password']) < 4)
    	{
    		display_error( _("The password entered must be at least 4 characters long."));
			set_focus('password');
    		return false;
    	}

    	if (strstr($_POST['password'], $_POST['user_id']) != false)
    	{
    		display_error( _("The password cannot contain the user login."));
			set_focus('password');
    		return false;
    	}
	}

	return true;
}

//-------------------------------------------------------------------------------------------------

if (($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') && check_csrf_token())
{
	
	$selected_locations='';
			$selected_locations_count=0;
			$act_loc=get_locations();
			while($loc=db_fetch($act_loc))
			{
				
				if(isset($_POST['location_'.$loc['loc_code']]) && ($_POST['location_'.$loc['loc_code']]=='1'))
				{
				  
					$selected_locations_count++;
					$selected_locations=$selected_locations.$loc['loc_code'].",";
				}			
			}
		if($selected_locations_count==0)
		{
			display_error( _("Please select at least one location for the user!"));
			set_focus('location_'.$loc['loc_code']);
    		return false;
		}		
		$selected_dimensions='';
			$selected_dimensions_count=0;
			  $dimensions=get_dimensions_for_users();
			while($dms=db_fetch($dimensions))
			{
				
				if(isset($_POST['dimension_'.$dms['id']]) && ($_POST['dimension_'.$dms['id']]=='1'))
				{
				  
					$selected_dimensions_count++;
					$selected_dimensions=$selected_dimensions.$dms['id'].",";
				}			
			}
	if($selected_dimensions_count==0)
		{
			display_error( _("Please select at least one dimension for the user!"));
			set_focus('dimension_1');
    		return false;
		}	  


	if (can_process($Mode == 'ADD_ITEM'))
	{
    	if ($selected_id != -1) 
    	{
			$_POST['user_location']=trim($selected_locations);
			$_POST['dimensions']=trim($selected_dimensions);
			
    		update_user_prefs($selected_id,
    			get_post(array('user_id', 'real_name', 'phone', 'email', 'role_id', 'language',
					'print_profile', 'rep_popup' => 0, 'pos','user_location', 'dimensions','show_cost_rate','sales_disc_edit','invoice_entry_date_before_do','is_sales_person_login', 'access_all_customers', 'salesman_id')));

    		if ($_POST['password'] != "")
    			update_user_password($selected_id, $_POST['user_id'], md5($_POST['password']));

    		display_notification_centered(_("The selected user has been updated."));
    	} 
    	else 
    	{
    		add_user($_POST['user_id'], $_POST['real_name'], md5($_POST['password']),
				$_POST['phone'], $_POST['email'], $_POST['role_id'], $_POST['language'],
				$_POST['print_profile'], check_value('rep_popup'), $_POST['pos'],trim($selected_locations),trim($selected_dimensions),check_value('show_cost_rate'),check_value('sales_disc_edit'),check_value('invoice_entry_date_before_do'),check_value('is_sales_person_login'),check_value('access_all_customers'), $_POST['salesman_id']);
			$id = db_insert_id();
			// use current user display preferences as start point for new user
			$prefs = $_SESSION['wa_current_user']->prefs->get_all();
			
			update_user_prefs($id, array_merge($prefs, get_post(array('print_profile',
				'rep_popup' => 0, 'language'))));

			display_notification_centered(_("A new user has been added."));
    	}
		$Mode = 'RESET';
	}
}

//-------------------------------------------------------------------------------------------------

if ($Mode == 'Delete' && check_csrf_token())
{
	$cancel_delete = 0;
    if (key_in_foreign_table($selected_id, 'audit_trail', 'user'))
    {
        $cancel_delete = 1;
        display_error(_("Cannot delete this user because entries are associated with this user."));
    }
    if ($cancel_delete == 0) 
    {
    	delete_user($selected_id);
    	display_notification_centered(_("User has been deleted."));
    } //end if Delete group
    $Mode = 'RESET';
}

//-------------------------------------------------------------------------------------------------
if ($Mode == 'RESET')
{
 	$selected_id = -1;
	$sav = get_post('show_inactive', null);
	unset($_POST);	// clean all input fields
	$_POST['show_inactive'] = $sav;
}

$result = get_users(check_value('show_inactive'));
start_form();
start_table(TABLESTYLE);

$th = array(_("User login"), _("Full Name"), _("Phone"),
	_("E-mail"), _("Last Visit"), _("Access Level"),_("Locations"),_("Dimensions"),_("Is Sales Person Login"),_("Sales Person"),_("Access All Customers"), "", "");

inactive_control_column($th);
table_header($th);	

$k = 0; //row colour counter

while ($myrow = db_fetch($result)) 
{

	alt_table_row_color($k);

	$time_format = (user_date_format() == 0 ? "h:i a" : "H:i");
	$last_visit_date = sql2date($myrow["last_visit_date"]). " " . 
		date($time_format, strtotime($myrow["last_visit_date"]));

	/*The security_headings array is defined in config.php */
	$not_me = strcasecmp($myrow["user_id"], $_SESSION["wa_current_user"]->username);

	label_cell($myrow["user_id"]);
	label_cell($myrow["real_name"]);
	label_cell($myrow["phone"]);
	email_cell($myrow["email"]);
	label_cell($last_visit_date, "nowrap");
	label_cell($myrow["role"]);
	
	$locations=get_use_locations(trim($myrow["user_location"]));
	label_cell($locations);
	$dimensions=get_use_dimensions(trim($myrow["dimensions"]));
	label_cell($dimensions);
	label_cell($myrow["is_sales_person_login"]==1?'Yes':'No',"align=center");
	if($myrow["salesman_id"]!=0)
	label_cell(get_salesperson_name($myrow["salesman_id"]),"align=center");	
	else
	label_cell("","align=center");
	label_cell($myrow["access_all_customers"]==1?'Yes':'No',"align=center");
	
    if ($not_me)
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'users', 'id');
	elseif (check_value('show_inactive'))
		label_cell('');

	edit_button_cell("Edit".$myrow["id"], _("Edit"));
    if ($not_me)
 		delete_button_cell("Delete".$myrow["id"], _("Delete"));
	else
		label_cell('');
	end_row();

} //END WHILE LIST LOOP

inactive_control_row($th);
end_table(1);
//-------------------------------------------------------------------------------------------------
start_table(TABLESTYLE2);

$_POST['email'] = "";
if ($selected_id != -1) 
{
  	if ($Mode == 'Edit') {
		//editing an existing User
		$myrow = get_user($selected_id);

		$_POST['id'] = $myrow["id"];
		$_POST['user_id'] = $myrow["user_id"];
		$_POST['real_name'] = $myrow["real_name"];
		$_POST['phone'] = $myrow["phone"];
		$_POST['email'] = $myrow["email"];
		$_POST['role_id'] = $myrow["role_id"];
		$_POST['language'] = $myrow["language"];
		$_POST['print_profile'] = $myrow["print_profile"];
		$_POST['rep_popup'] = $myrow["rep_popup"];
		$_POST['pos'] = $myrow["pos"];
		$_POST['show_cost_rate'] = $myrow["show_cost_rate"];
		$_POST['sales_disc_edit'] = $myrow["sales_disc_edit"];
		$_POST['invoice_entry_date_before_do'] = $myrow["invoice_entry_date_before_do"];
		$_POST['is_sales_person_login'] = $myrow["is_sales_person_login"];
		$_POST['access_all_customers'] = $myrow["access_all_customers"];
		$_POST['salesman_id'] = $myrow["salesman_id"];
		$locations=get_locations();
	
	    $selected_loc_array=explode(",",$myrow['user_location']);
	
		while($loc=db_fetch($locations))
		{
			if(in_array($loc['loc_code'], $selected_loc_array))
		   {
			 $_POST['location_'.$loc['loc_code']]=1;
		   }
		}
	
	$dimensions=get_dimensions_for_users();
	$selected_dms_array=explode(",",$myrow['dimensions']);
		while($dms=db_fetch($dimensions))
		{
			if(in_array($dms['id'], $selected_dms_array))
		   {
			 $_POST['dimension_'.$dms['id']]=1;
		   }
		}
		
	}
	hidden('selected_id', $selected_id);
	hidden('user_id');

	start_row();
	label_row(_("User login:"), $_POST['user_id']);
} 
else 
{ //end of if $selected_id only do the else when a new record is being entered
	text_row(_("User Login:"), "user_id",  null, 22, 20);
	$_POST['language'] = user_language();
	$_POST['print_profile'] = user_print_profile();
	$_POST['rep_popup'] = user_rep_popup();
	$_POST['pos'] = user_pos();
}
$_POST['password'] = "";
password_row(_("Password:"), 'password', $_POST['password']);

if ($selected_id != -1) 
{
	table_section_title(_("Enter a new password to change, leave empty to keep current."));
}

text_row_ex(_("Full Name").":", 'real_name',  50);

text_row_ex(_("Telephone No.:"), 'phone', 30);

email_row_ex(_("Email Address:"), 'email', 50);

security_roles_list_row(_("Access Level:"), 'role_id', null); 

languages_list_row(_("Language:"), 'language', null);

pos_list_row(_("User's POS"). ':', 'pos', null);

print_profiles_list_row(_("Printing profile"). ':', 'print_profile', null,
	_('Browser printing support'));
check_row(_("Is Sales Person Login:"),'is_sales_person_login',$_POST['is_sales_person_login'],true);


if (isset($_POST['is_sales_person_login'])) {
	$Ajax->activate('_page_body');
}

if (check_value('is_sales_person_login')) {
sales_persons_list_row( _("Sales Person:"), 'salesman_id', null);
}
check_row(_("Use popup window for reports:"), 'rep_popup', $_POST['rep_popup'],
	false, _('Set this option to on if your browser directly supports pdf files'));
	
check_row(_("Show Cost Rate:"),'show_cost_rate',$_POST['show_cost_rate'],false);
check_row(_("Sales Discount Edit:"),'sales_disc_edit',$_POST['sales_disc_edit'],false);
check_row(_("Allow Invoice entry date before DO date:"),'invoice_entry_date_before_do',$_POST['invoice_entry_date_before_do'],false);
check_row(_("Access to All the Customers:"),'access_all_customers',$_POST['access_all_customers'],false);
start_row();
	echo "<td bgcolor='aliceblue'>Locations:</td>";
	echo "<td><table><tr>";
	$locations=get_locations();
	$i=0;
	while($loc=db_fetch($locations))
	{
		if($i%4==0)
		{
			echo "</tr>";
			echo "<tr>";
		}
		$checked='';
		if($_POST['location_'.$loc['loc_code']])
		$checked="checked";
		echo "<td><input type='checkbox' name='location_".$loc['loc_code']."' $checked>".$loc['location_name']."</td>";
		$i++;
	}
	echo "</tr></table></td>";

end_row();

start_row();
echo "<td bgcolor='aliceblue'>Dimensions:</td>";
echo "<td><table><tr>";
	$dimensions=get_dimensions_for_users();
	$i=0;
	while($dms=db_fetch($dimensions))
	{
		if($i%4==0)
		{
			echo "</tr>";
			echo "<tr>";
		}
		$checked='';
		if($_POST['dimension_'.$dms['id']])
		$checked="checked";
		echo "<td><input type='checkbox' name='dimension_".$dms['id']."' $checked>".$dms['ref']."</td>";
		$i++;
	}
echo "</tr></table></td>";
end_row();	

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();
end_page();
