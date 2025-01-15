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
class suppliers_app extends application 
{
	function __construct() 
	{
		parent::__construct("AP", _($this->help_context = "&Purchases"));

		$this->add_module(_("Transactions"));
		
		
		$this->add_lapp_function(0, _("Purchase Enquiry Entry"),
			"purchasing/po_entry_items.php?NewEnq=Yes", 'SA_PURCHENQ', MENU_TRANSACTION);
		
		$this->add_lapp_function(0, _("Purchase Quotation Entry"),
			"purchasing/po_entry_items.php?NewQuote=Yes", 'SA_PURCHQUOTE', MENU_TRANSACTION);
		
		
		$this->add_lapp_function(0, _("Purchase &Order Entry"),
			"purchasing/po_entry_items.php?NewOrder=Yes", 'SA_PURCHASEORDER', MENU_TRANSACTION);
			
		$this->add_rapp_function(0, _("General Purchase &Order Entry"),
			"purchasing/general_po_entry_items.php?NewOrder=Yes", 'SA_GEN_PURCHASEORDER', MENU_TRANSACTION);	
			
		$this->add_lapp_function(0, _("&Purchase Order Authorizations Maintenance"),
			"purchasing/inquiry/po_search_authorisations.php?", 'SA_PURCHASEORDER_AUTH', MENU_INQUIRY);	 
			
		/* $this->add_lapp_function(0, _("&Outstanding Purchase Orders Maintenance"),
			"purchasing/inquiry/po_search.php?", 'SA_GRN', MENU_TRANSACTION);
		$this->add_lapp_function(0, _("Direct &GRN"),
			"purchasing/po_entry_items.php?NewGRN=Yes", 'SA_GRN', MENU_TRANSACTION); */
		$this->add_lapp_function(0, _("Direct Supplier &Invoice"),
			"purchasing/po_entry_items.php?NewInvoice=Yes", 'SA_SUPPLIERINVOICE', MENU_TRANSACTION);
			
				
		$this->add_lapp_function(0, _("Direct Supplier &Invoice (Against Reverse Charge)"),
			"purchasing/po_entry_items_rev.php?NewInvoice=Yes&rev_status=1", 'SA_SUPPLIERINVOICEREV', MENU_TRANSACTION);
			
		$this->add_lapp_function(0, _("Invoice Against Multiple Purchase Orders"),
			"purchasing/po_invoice_items_multiple_pos.php?NewINVM=Yes", 'SA_INVMULTIPLE', MENU_TRANSACTION);
			
		$this->add_rapp_function(0, _("&Suppliers PDC Entry"),
			"purchasing/supplier_pdc.php?", 'SA_SUPPLIERPDC', MENU_TRANSACTION);	
			
		$this->add_rapp_function(0, _("&Payments to Suppliers"),
			"purchasing/supplier_payment.php?", 'SA_SUPPLIERPAYMNT', MENU_TRANSACTION);
			
			
		$this->add_rapp_function(0, "","");
		/* $this->add_rapp_function(0, _("Supplier &Invoices"),
			"purchasing/supplier_invoice.php?New=1", 'SA_SUPPLIERINVOICE', MENU_TRANSACTION); */
		$this->add_rapp_function(0, _("Supplier &Credit Notes"),
			"purchasing/supplier_credit.php?New=1", 'SA_SUPPLIERCREDIT', MENU_TRANSACTION);
			
		$this->add_rapp_function(0, _("Supplier Direct Credit Note"),
			"purchasing/supplier_direct_credit_note.php?New=1", 'SA_SUPPLIERCREDIT', MENU_TRANSACTION);
			
		$this->add_rapp_function(0, _("&Allocate Supplier Payments or Credit Notes"),
			"purchasing/allocations/supplier_allocation_main.php?", 'SA_SUPPLIERALLOC', MENU_TRANSACTION);

		$this->add_module(_("Inquiries and Reports"));
		
		$this->add_lapp_function(1, _("Purchase Enquiries"),
			"purchasing/inquiry/po_search_completed.php?type=51", 'SA_SUPPTRANSVIEW', MENU_INQUIRY);
		
		$this->add_lapp_function(1, _("Purchase Quotation Inquiry"),
			"purchasing/inquiry/po_search_completed.php?type=52", 'SA_SUPPTRANSVIEW', MENU_INQUIRY);
			
		$this->add_lapp_function(1, _("Purchase Quotation &Comparison"),
			"purchasing/inquiry/po_quote_compare.php", 'SA_SUPPTRANSVIEW', MENU_INQUIRY);		
			
		$this->add_lapp_function(1, _("Purchase Orders &Inquiry"),
			"purchasing/inquiry/po_search_completed.php?type=18", 'SA_SUPPTRANSVIEW', MENU_INQUIRY);
			
		$this->add_rapp_function(1, _("General Purchase Orders &Inquiry"),
			"purchasing/inquiry/gen_po_search_completed.php?type=180", 'SA_SUPPTRANSVIEW', MENU_INQUIRY);

			
		$this->add_lapp_function(1, _("Supplier Transaction &Inquiry"),
			"purchasing/inquiry/supplier_inquiry.php?", 'SA_SUPPTRANSVIEW', MENU_INQUIRY);
			
		$this->add_lapp_function(1, _("Inv GRN &Inquiry"),
			"purchasing/inquiry/inv_grn_inquiry.php?", 'SA_SUPPTRANSVIEW', MENU_INQUIRY);
			
		$this->add_lapp_function(1, _("Supplier Allocation &Inquiry"),
			"purchasing/inquiry/supplier_allocation_inquiry.php?", 'SA_SUPPLIERALLOC', MENU_INQUIRY);
			
        $this->add_rapp_function(1, _("Cheque Transactions &Inquiry"),
			"purchasing/inquiry/cheque_transactions_inquiry.php?", 'SA_CHEQUE_TRANS_INQ', MENU_INQUIRY);		
		$this->add_rapp_function(1, _("Supplier and Purchasing &Reports"),
			"reporting/reports_main.php?Class=1", 'SA_SUPPTRANSVIEW', MENU_REPORT);

		$this->add_module(_("Masters"));
		$this->add_lapp_function(2, _("&Suppliers"),
			"purchasing/manage/suppliers.php?", 'SA_SUPPLIER', MENU_ENTRY);

		$this->add_extensions();
	}
}


