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
class customers_app extends application 
{
	function __construct() 
	{
		parent::__construct("orders", _($this->help_context = "&Sales"));
	
		$this->add_module(_("Transactions"));
		/* $this->add_lapp_function(0, _("Sales Enquiry Entry"),
			"sales/sales_order_entry.php?NewEnq=Yes", 'SA_SALESENQ', MENU_TRANSACTION);*/
			 
		 $this->add_lapp_function(0, _("Sales &Enquiry Entry"),
			"sales/sales_order_entry2.php?NewEnquiry=Yes", 'SA_INSSALESENQ', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Sales &Quotation Entry"),
			"sales/sales_order_entry.php?NewQuotation=Yes", 'SA_SALESQUOTE', MENU_TRANSACTION);
		
		/* $this->add_lapp_function(0, _("Sales &Quotation Entry"),
			"sales/sales_order_entry2.php?NewQuotation=Yes", 'SA_INSSALESQUOTE', MENU_TRANSACTION);*/
		$this->add_lapp_function(0, _("Sales &Order Entry"),
			"sales/sales_order_entry.php?NewOrder=Yes", 'SA_SALESORDER', MENU_TRANSACTION);
		 $this->add_lapp_function(0, _("Direct &Delivery"),
			"sales/sales_order_entry.php?NewDelivery=0", 'SA_SALESDELIVERY', MENU_TRANSACTION); 
		$this->add_lapp_function(0, _("Direct &Invoice"),
			"sales/sales_order_entry.php?NewInvoice=0", 'SA_SALESINVOICE', MENU_TRANSACTION);
		$this->add_lapp_function(0, "","");
		$this->add_lapp_function(0, _("&Delivery Against Sales Orders"),
			"sales/inquiry/sales_orders_view.php?OutstandingOnly=1", 'SA_SALESDELIVERY', MENU_TRANSACTION);
			
		$this->add_lapp_function(0, _("&Invoice Against Sales Delivery"),
			"sales/inquiry/sales_deliveries_view.php?OutstandingOnly=1", 'SA_SALESINVOICE', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Customer &PDC Entry"),
			"sales/customer_pdc.php?", 'SA_SALESPDC', MENU_TRANSACTION);
			
	    $this->add_rapp_function(0, _("Customer &Advance With VAT"),
			"sales/customer_advance_payments.php?", 'SA_SALES_ADVNC_PAYMNT', MENU_TRANSACTION);

		$this->add_rapp_function(0, _("Return Customer &Advance With VAT"),
			"gl/advance_return.php?NewPayment=Yes", 'SA_SALES_ADVNC_RTN_PAYMNT', MENU_TRANSACTION);
			
		$this->add_rapp_function(0, _("Customer &Payments"),
			"sales/customer_payments.php?", 'SA_SALESPAYMNT', MENU_TRANSACTION);
		//$this->add_lapp_function(0, _("Invoice &Prepaid Orders"),
			//"sales/inquiry/sales_orders_view.php?PrepaidOrders=Yes", 'SA_SALESINVOICE', MENU_TRANSACTION);
		$this->add_rapp_function(0, _("Customer &Credit Notes"),
			"sales/credit_note_entry.php?NewCredit=Yes", 'SA_SALESCREDIT', MENU_TRANSACTION);
			
		
			
		//$this->add_rapp_function(0, _("Upload Salesman Collection Entry"),
			//"sales/inquiry/upload_salesman_collection.php?", 'SA_SALESMAN_COLLECTION', MENU_TRANSACTION);
			
		$this->add_rapp_function(0, _("SalesMan Collection Entry"),
			"sales/salesman_collection_entry.php?NewCollection=Yes", 'SA_SALESMAN_COLLECTION_ENTRY', MENU_TRANSACTION); 
			
			
			

        $this->add_rapp_function(0, _("&Allocate Customer Payments or Credit Notes"),
			"sales/allocations/customer_allocation_main.php?", 'SA_SALESALLOC', MENU_TRANSACTION);			
	
		$this->add_module(_("Inquiries and Reports"));
		$this->add_lapp_function(1, _("Sales Enquiry I&nquiry"),
			"sales/inquiry/sales_insquote_view.php?type=37", 'SA_SALESTRANSVIEW', MENU_INQUIRY);
		/*$this->add_lapp_function(1, _("Sales Quotation I&nquiry"),
			"sales/inquiry/sales_insquote_view.php?type=34", 'SA_SALESTRANSVIEW', MENU_INQUIRY);
		 $this->add_lapp_function(1, _("Sales Enquiry Inquiry"),
			"sales/inquiry/sales_orders_view.php?type=53", 'SA_SALESTRANSVIEW', MENU_INQUIRY);*/
		$this->add_lapp_function(1, _("Sales Quotation I&nquiry"),
			"sales/inquiry/sales_orders_view.php?type=32", 'SA_SALESTRANSVIEW', MENU_INQUIRY); 
		$this->add_lapp_function(1, _("Sales Order &Inquiry"),
			"sales/inquiry/sales_orders_view.php?type=30", 'SA_SALESTRANSVIEW', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Customer Transaction &Inquiry"),
			"sales/inquiry/customer_inquiry.php?", 'SA_TRANS_INQ_SALES_REP', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Sales Invoices &Inquiry"),
			"sales/inquiry/customer_sales_invoice_view.php?", 'SA_SALES_INV_INQ', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Customer Allocation &Inquiry"),
			"sales/inquiry/customer_allocation_inquiry.php?", 'SA_SALESALLOC', MENU_INQUIRY);
		$this->add_rapp_function(1, _("Invoice - Signed Copy Collection"),
			"sales/inquiry/customer_invoices_view.php?", 'SA_SALES_INVOICE_SIGNED', MENU_INQUIRY);
        $this->add_rapp_function(1, _("Sales Delivery Calendar (Item Wise)"),
			"sales/inquiry/sales_delivery_calendar.php?", 'SA_SALES_DELIVERY_CALENDAR', MENU_INQUIRY);


        $this->add_rapp_function(1, _("Sales Delivery Calendar(Order Wise)"),
			"sales/inquiry/sales_delivery_calendar_order_wise.php?", 'SA_SALES_DELIVERY_CALENDAR_ORDERWISE', MENU_INQUIRY);  

		$this->add_rapp_function(1, _("Customer and Sales &Reports"),
			"reporting/reports_main.php?Class=0", 'SA_SALESTRANSVIEW', MENU_REPORT);

		$this->add_module(_("Masters"));
		$this->add_lapp_function(2, _("Add and Manage &Customers"),
			"sales/manage/customers.php?", 'SA_CUSTOMER', MENU_ENTRY);
		$this->add_lapp_function(2, _("Customer &Branches"),
			"sales/manage/customer_branches.php?", 'SA_CUSTOMER', MENU_ENTRY);
		$this->add_lapp_function(2, _("Sales &Groups"),
			"sales/manage/sales_groups.php?", 'SA_SALESGROUP', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Recurrent &Invoices"),
			"sales/manage/recurrent_invoices.php?", 'SA_SRECURRENT', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Sales T&ypes"),
			"sales/manage/sales_types.php?", 'SA_SALESTYPES', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Sales &Persons"),
			"sales/manage/sales_people.php?", 'SA_SALESMAN', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Sales &Areas"),
			"sales/manage/sales_areas.php?", 'SA_SALESAREA', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Credit &Status Setup"),
			"sales/manage/credit_status.php?", 'SA_CRSTATUS', MENU_MAINTENANCE);
			
		$this->add_rapp_function(2, _("Legal Group &Master"),
			"sales/manage/legal_group_master.php?", 'SA_SALES_LEGAL_GRP', MENU_MAINTENANCE);			
		$this->add_rapp_function(2, _("Customer Type &Master"),
			"sales/manage/sales_customer_type.php?", 'SA_CUST_TYPE', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Customer Category &Master"),
			"sales/manage/sales_customer_category.php?", 'SA_CUST_CATEGORY', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Customer Region &Master"),
			"sales/manage/sales_customer_region.php?", 'SA_CUST_REGION', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Customer Wilayat &Master"),
			"sales/manage/sales_customer_wilayat.php?", 'SA_CUST_WILAYAT', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Customer Class &Master"),
			"sales/manage/sales_customer_class.php?", 'SA_CUST_CLASS', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Customer Group Company &Master"),
			"sales/manage/sales_customer_group_company.php?", 'SA_CUST_GROUP_COMPANY', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Customer &Unlock"),
			"inventory/manage/customer_unlock.php?", 'SA_CUSTOMER_UNLOCK', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Change &Customer Sales Person"),
			"sales/manage/change_customer_sales_person.php?", 'SA_CHANGE_CUSTOMER_SP', MENU_MAINTENANCE);

		$this->add_extensions();
	}
}


