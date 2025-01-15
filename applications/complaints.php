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
class complaints_app extends application 
{
	function __construct() 
	{
		parent::__construct("complaints", _($this->help_context = "&Complaints"));
		
		$this->add_module(_("Transactions"));
		$this->add_lapp_function(0, _("Register a Complaint"),
			"complaints/manage/complaint_raise.php?", 'SA_COMPLAINT', MENU_TRANSACTION);		

		$this->add_module(_("Inquiries and Reports"));
		$this->add_lapp_function(1, _("View or Update Complaints"),
			"complaints/inquiry/complaints_inquiry.php?", 'SA_COMPLAINT_INQUIRY', MENU_INQUIRY);
		
		$this->add_lapp_function(1, _("Complaints Drilldown"),
			"complaints/inquiry/complaints_drilldown.php?", 'SA_COMPLAINT_INQUIRY', MENU_INQUIRY);
			
		$this->add_rapp_function(1, _("Complaints &Reports"),
			"reporting/reports_main.php?Class=8", 'SA_COMPLAINT_REP', MENU_REPORT);	
		
		
		$this->add_extensions();
	}
}


