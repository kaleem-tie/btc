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
/*
	Security sections groups various areas on both functionality and privilege levels.
	Often analytic inquires are available only for management, and configuration
	for administration or management staff. This is why we have those three
	section type inside near every FA module.

	Section codes 0-99 are reserved for core FA functionalities.
	Every security section can contain up to 256 different areas.
	External modules can extend security roles system by adding rows to 
	$security_sections and $security_areas using section codes >=100.
	Security areas and sections created by extension modules/plugins
	have dynamically assigned 3-byte integer codes. The highest byte is zero
	for sections/areas defined in this file, and extid+1 for those defined 
	by extensions 
*/
define('SS_SADMIN',	1<<8);	// site admin
define('SS_SETUP',	2<<8);	// company level setup
define('SS_SPEC',	3<<8);	// special administration

define('SS_SALES_C',11<<8); // configuration
define('SS_SALES',	12<<8);	// transactions
define('SS_SALES_A',13<<8); // analytic functions/reports/inquires

define('SS_PURCH_C',21<<8);
define('SS_PURCH',	22<<8);
define('SS_PURCH_A',23<<8);

define('SS_ITEMS_C',31<<8);
define('SS_ITEMS',	32<<8);
define('SS_ITEMS_A',33<<8);

define('SS_ASSETS_C',36<<8);
define('SS_ASSETS',	37<<8);
define('SS_ASSETS_A',38<<8);

define('SS_MANUF_C',41<<8);
define('SS_MANUF',	42<<8);
define('SS_MANUF_A',43<<8);

define('SS_DIM_C',	51<<8);
define('SS_DIM',	52<<8);
define('SS_DIM_A',	53<<8);

define('SS_GL_C',	61<<8);
define('SS_GL',		62<<8);
define('SS_GL_A',	63<<8);

define('SS_COMPLAINTS_C',71<<8);
define('SS_COMPLAINTS',	72<<8);
define('SS_COMPLAINTS_M',73<<8);

define('SS_SALES_DASHBOARDS',74<<8);
define('SS_PURCHASE_DASHBOARDS',75<<8);
define('SS_INVENTORY_DASHBOARDS',76<<8);
define('SS_FINANCE_DASHBOARDS',77<<8);
define('SS_DIMENSION_DASHBOARDS',78<<8);


$security_sections = array(
 	SS_SADMIN => _("System administration"),
 	SS_SETUP => _("Company setup"),
 	SS_SPEC => _("Special maintenance"),
 	SS_SALES_C => _("Sales configuration"),
 	SS_SALES => _("Sales transactions"),
 	SS_SALES_A => _("Sales related reports"),
 	SS_PURCH_C => _("Purchase configuration"),
 	SS_PURCH => _("Purchase transactions"),
 	SS_PURCH_A => _("Purchase analytics"),
 	SS_ITEMS_C => _("Inventory configuration"),
 	SS_ITEMS => _("Inventory operations"),
 	SS_ITEMS_A => _("Inventory analytics"),
	SS_ASSETS_C => _("Fixed Assets configuration"),
	SS_ASSETS => _("Fixed Assets operations"),
	SS_ASSETS_A => _("Fixed Assets analytics"),
 	SS_MANUF_C => _("Manufacturing configuration"),
 	SS_MANUF => _("Manufacturing transactions"),
 	SS_MANUF_A => _("Manufacturing analytics"),
 	SS_DIM_C => _("Dimensions configuration"),
 	SS_DIM => _("Dimensions"),
 	SS_GL_C => _("Banking & GL configuration"),
 	SS_GL => _("Banking & GL transactions"),
 	SS_GL_A => _("Banking & GL analytics"),

    SS_COMPLAINTS_C=> _("Complaint Transactions"),
	SS_COMPLAINTS  => _("Complaint Reports and Inquiries"),
	SS_COMPLAINTS_M=> _("Complaint Masters"),
	
	SS_SALES_DASHBOARDS=> _("Sales Dashboard Widgets"),
	SS_PURCHASE_DASHBOARDS=> _("Purchase Dashboard Widgets"),
	SS_INVENTORY_DASHBOARDS=> _("Inventory Dashboard Widgets"),
	SS_FINANCE_DASHBOARDS=> _("Accounts Dashboard Widgets"),
	SS_DIMENSION_DASHBOARDS=> _("Dimensions Dashboard Widgets"),
	
);

/*
	This table stores security areas available in FA. 
	Key is area identifier used to check user rights, values are
	codes stored for each role in security_roles table and description used
	in roles editor.

	Set of allowed access areas codes is retrieved during user login from
	security_roles table, and cached in user profile.

	Special value 'SA_OPEN' is used for publicly available pages like login/logout.
*/
$security_areas =array(
//
//	Site administration
//
	//'SA_CREATECOMPANY' =>array(SS_SADMIN|1, _("Install/update companies")),
	//'SA_CREATELANGUAGE' => array(SS_SADMIN|2, _("Install/update languages")),
	//'SA_CREATEMODULES' => array(SS_SADMIN|3, _("Install/upgrade modules")),
	//'SA_SOFTWAREUPGRADE' => array(SS_SADMIN|4, _("Software upgrades")),
//
//	Company setup
//
	'SA_SETUPCOMPANY' => array(SS_SETUP|1, _("Company parameters")),
	'SA_SECROLES' => array(SS_SETUP|2, _("Access levels edition")),
	'SA_USERS' => array(SS_SETUP|3, _("Users setup")),
	'SA_POSSETUP' => array(SS_SETUP|4, _("Point of sales definitions")),
	'SA_PRINTERS' => array(SS_SETUP|5, _("Printers configuration")),
	'SA_PRINTPROFILE' => array(SS_SETUP|6, _("Print profiles")),
	'SA_PAYTERMS' => array(SS_SETUP|7, _("Payment terms")),
	'SA_SHIPPING' => array(SS_SETUP|8, _("Shipping Company")),
	'SA_CRSTATUS' => array(SS_SETUP|9, _("Credit status definitions changes")),
	'SA_INVENTORYLOCATION' => array(SS_SETUP|10, _("Inventory locations changes")),
	'SA_INVENTORYMOVETYPE'  => array(SS_SETUP|11, _("Inventory movement types")),
	'SA_WORKCENTRES' => array(SS_SETUP|12, _("Manufacture work centres")),
	'SA_FORMSETUP' => array(SS_SETUP|13, _("Forms setup")),
	'SA_CRMCATEGORY' => array(SS_SETUP|14, _("Contact categories")),
	'SA_LOC_DIM_CASHACT' => array(SS_SETUP|15, _("Linking Location with Dimension and Cash Account")),
	'SA_SHIPPING_TERMS' => array(SS_SETUP|16, _("Shipping Terms")),
//
// Special and common functions
//
	'SA_VOIDTRANSACTION' => array(SS_SPEC|1, _("Voiding transactions")),
	'SA_BACKUP' => array(SS_SPEC|2, _("Database backup/restore")),
	'SA_VIEWPRINTTRANSACTION' => array(SS_SPEC|3, _("Common view/print transactions interface")),
	'SA_ATTACHDOCUMENT' => array(SS_SPEC|4, _("Attaching documents")),
	'SA_SETUPDISPLAY' => array(SS_SPEC|5, _("Display preferences")),
	'SA_CHGPASSWD' => array(SS_SPEC|6, _("Password changes")),
	'SA_EDITOTHERSTRANS' => array(SS_SPEC|7, _("Edit other users transactions")),
	
	'SA_VOIDTRANSACTION_INQUIRY' => array(SS_SPEC|8, _("Voiding transactions inquiry")),
	
//
// Sales related functionality
//
	'SA_SALESTYPES' => array(SS_SALES_C|1, _("Sales types")),
	'SA_SALESPRICE' => array(SS_SALES_C|2, _("Sales prices edition")),
	'SA_SALESMAN' => array(SS_SALES_C|3, _("Sales staff maintenance")),
	'SA_SALESAREA' => array(SS_SALES_C|4, _("Sales areas maintenance")),
	'SA_SALESGROUP' => array(SS_SALES_C|5, _("Sales groups changes")),
	'SA_STEMPLATE' => array(SS_SALES_C|6, _("Sales templates")),
	'SA_SRECURRENT' => array(SS_SALES_C|7, _("Recurrent invoices definitions")),
	'SA_SALES_LEGAL_GRP' => array(SS_SALES_C|8, _("Legal Group")),
	'SA_CUST_TYPE' => array(SS_SALES_C|9, _("Sales Customer Types")),
	'SA_CUST_CATEGORY' => array(SS_SALES_C|10, _("Sales Customer Category")),
	'SA_CUST_REGION' => array(SS_SALES_C|11, _("Sales Customer Region")),
	'SA_CUST_WILAYAT' => array(SS_SALES_C|12, _("Sales Customer Wilayat")),
	'SA_CUST_CLASS' => array(SS_SALES_C|13, _("Sales Customer Class")),
	'SA_CUST_GROUP_COMPANY' => array(SS_SALES_C|14, _("Sales Customer Group Company")),
	'SA_CHANGE_CUSTOMER_SP' => array(SS_SALES_C|15, _("Change Customer Sales Person")),

	'SA_SALESTRANSVIEW' => array(SS_SALES|1,  _("Sales transactions view")),
	'SA_CUSTOMER' => array(SS_SALES|2,  _("Sales customer and branches changes")),
	 'SA_SALESENQ' => array(SS_SALES|11, _("Sales enquiries regular")),
	 'SA_SALESQUOTE' => array(SS_SALES|10, _("Sales quotations regular")),
	'SA_INSSALESENQ' => array(SS_SALES|12, _("Sales Enquiry New")),
	'SA_INSSALESQUOTE' => array(SS_SALES|13, _("Sales quotations new")),
	'SA_SALESORDER' => array(SS_SALES|3, _("Sales orders Entry")),
	'SA_SALESORDEREDIT' => array(SS_SALES|14, _("Sales orders Edition")),
	'SA_SALESDELIVERY' => array(SS_SALES|4, _("Sales deliveries Entry / Edition")),
	'SA_SALESINVOICE' => array(SS_SALES|5, _("Sales invoices Entry")),
	'SA_SALESCREDITINV' => array(SS_SALES|6, _("Sales credit notes against invoice")),
	'SA_SALESCREDIT' => array(SS_SALES|7, _("Sales freehand credit notes")),
	'SA_SALESPAYMNT' => array(SS_SALES|8, _("Customer payments entry")),
	'SA_SALESALLOC' => array(SS_SALES|9, _("Customer payments allocation")),
	    'SA_SALES_DELIVERY_PLAN' => array(SS_SALES|15,  _("Sales Delivery Plan")),
	'SA_SALES_DELIVERY_CALENDAR' => array(SS_SALES|16,  _("Sales Delivery Calendar")),
	'SA_SALES_DELIVERY_CALENDAR_ORDERWISE' => array(SS_SALES|17,  _("Sales Delivery Calendar(Order Wise)")),
	'SA_SALESORDER_CANCEL' => array(SS_SALES|18, _("Cancel Sales orders")),
	'SA_SALES_INVOICE_SIGNED' => array(SS_SALES|19, _("Invoice - Signed Copy Collection")),
	'SA_SALESPDC' => array(SS_SALES|20, _("Customer PDC entry")),
	'SA_RECALL_PDC' => array(SS_SALES|21,_("Recall PDC Entry")),
	'SA_RETURN_PDC' => array(SS_SALES|22,_("PDC Return Entry")),
	'SA_BOUNCE_PDC' => array(SS_SALES|23,_("PDC Bounce Entry")),
	'SA_SALES_INV_EDIT' => array(SS_SALES|24,  _("Sales invoices edit after signed copy")),
	'SA_SALES_INV_INQ' => array(SS_SALES|25,  _("Sales Invoice Inquiry")),
	'SA_SALESMAN_COLLECTION' => array(SS_SALES|26, _("Upload Salesman Collection Entry")),
	'SA_SALESMAN_COLLECTION_ENTRY' => array(SS_SALES|27, _("Salesman Collection Entry")),
	'SA_CUSTOMER_UNLOCK' => array(SS_SALES|28,  _("Customer Unlock")),
	'SA_CUSTOMER_UNLOCK_REQUEST' => array(SS_SALES|29,  _("Customer Unlock Request")),
	'SA_SALES_ADVNC_PAYMNT' => array(SS_SALES|30, _("Customer Advance With VAT")),
	'SA_SALES_ADVNC_RTN_PAYMNT' => array(SS_SALES|31, _("Return Customer Advance With VAT")),

	'SA_SALESANALYTIC' => array(SS_SALES_A|1, _("Sales analytical reports")),
	'SA_SALESBULKREP' => array(SS_SALES_A|2, _("Sales document bulk reports")),
	'SA_PRICEREP' => array(SS_SALES_A|3, _("Sales prices listing")),
	'SA_SALESMANREP' => array(SS_SALES_A|4, _("Sales staff listing")),
	'SA_CUSTBULKREP' => array(SS_SALES_A|5, _("Customer bulk listing")),
	'SA_CUSTSTATREP' => array(SS_SALES_A|6, _("Customer status report")),
	'SA_CUSTPAYMREP' => array(SS_SALES_A|7, _("Customer payments report")),
	'SA_SINLISTINGREP' => array(SS_SALES_A|8, _("Sales Invoice Listing")),
	'SA_SOLISTINGREP' => array(SS_SALES_A|9, _("Sales Order Listing")),
	'SA_SCRDLISTINGREP' => array(SS_SALES_A|10, _("Customer Credit Notes Listing")),
    'SA_SALES_ORDER_STATUS_LISTREP' => array(SS_SALES_A|11, _("Sales Order Status Listing report")),
    'SA_SCHEDULE_DELIVERY_REP' => array(SS_SALES_A|12, _("Upcoming Deliveries Report")),
	'SA_SALECANCELORDERLISTING' => array(SS_SALES_A|13, _("Cancel Sales Order Listing")),
    'SA_SALEPRICLISTREP' => array(SS_SALES_A|14, _("Sales Price Listing Report")),
    'SA_UNALLOC_CUST_TRANS_REP' => array(SS_SALES_A|15, _("Unallocated Customer Transactions Report")),
    'SA_SALES_DELIVERY_LISTING_REP' => array(SS_SALES_A|16, _("Sales Delivery Listing")),
	'SA_PENDING_DELIVERY_REP' => array(SS_SALES_A|17, _("Delayed Deliveries Report")),
	'SA_INV_ORDER_VARIATION_REP' => array(SS_SALES_A|18, _("Invoice to Order Price Variation Report")),
	//'SA_GRP_COMP_OUT_REG_REP' => array(SS_SALES_A|19, _("Group Company Wise Outstanding Register Report")),
	'SA_STAT_ACC_PDC_REP' => array(SS_SALES_A|19, _("Statement of Accounts with PDC Match Report")),
	'SA_DEB_OUT_REP' => array(SS_SALES_A|20, _("Debtors Outstanding Report")),
	'SA_SAL_MAN_SALES_REP' => array(SS_SALES_A|21, _("Salesman Sales Report (With Profit)")),
	
	
	'SA_CUST_SA_BAL_CONFIRM_REP' => array(SS_SALES_A|22, _("Statement of Accounts - Balance Confirmation Report")),
	'SA_CUST_SA_PRINT_CONFIRM_REP' => array(SS_SALES_A|23, _("Statement of Accounts - Print Confirmation Report")),
	'SA_CUST_SA_PDC_SHOW_DOWN_REP' => array(SS_SALES_A|24, _("Statement of Accounts-PDC Showing Down")),
	'SA_SALESMAN_COLLECTION_REP' => array(SS_SALES_A|25, _("SalesManwise Collection Summary Report")),
	'SA_SALESMAN_OUTSTAND_REP' => array(SS_SALES_A|26, _("SalesManwise Outstanding Register Report")),
	'SA_SALESMAN_COLLECTION_DETAILS_REP' => array(SS_SALES_A|27, _("SalesManwise Collection Details Report")),
	'SA_SALES_RECEIVE_LEDGER_REP' => array(SS_SALES_A|28, _("Receivables Ledger Report")),
	'SA_BILLWISE_MATCH_DETAILS_REP' => array(SS_SALES_A|29, _("Billwise Matching Details Report")),
	'SA_RECEIPT_SETOFF_DETAILS_REP' => array(SS_SALES_A|30, _("Receipt/Payment SetOff Details Report")),
	'SA_SALESMAN_AGING_REP' => array(SS_SALES_A|31, _("SalesManwise Aging Report")),
	'SA_PDC_REG_REP' => array(SS_SALES_A|32, _("PDC Register Report")),
	'SA_SALES_QUOTE_REG_REP' => array(SS_SALES_A|33, _("Sales Quotation Register")),
	'SA_RETAIL_CREDIT_SALES_REG_REP' => array(SS_SALES_A|34, _("Retail Sales and Credit Sales Register")),
	'SA_SALES_RETURN_REG_REP' => array(SS_SALES_A|35, _("Sales Return Register")),
	'SA_GEN_SALES_SUMMARY_REP' => array(SS_SALES_A|36, _("General Sales Summary Register(Saleswise/Customerwise) ")),
	'SA_SALES_DO_REG_REP' => array(SS_SALES_A|37, _("Delivery Orders Register")),
	
	'SA_SALESMANWISE_SALES_REP' => array(SS_SALES_A|38, _("SalesManwise Sales Reports")),
	
	'SA_TRANS_INQ_SALES_REP' => array(SS_SALES_A|39, _("Customer Tansactions Inquiry")),
	'SA_PRICE_LIST_SALE_REP' => array(SS_SALES_A|40, _("Price Listing Report Salesman")),
	
//
// Purchase related functions
//
	'SA_PURCHASEPRICING' => array(SS_PURCH_C|1, _("Purchase price changes")),

	'SA_SUPPTRANSVIEW' => array(SS_PURCH|1, _("Supplier transactions view")),
	'SA_SUPPLIER' => array(SS_PURCH|2, _("Suppliers changes")),
	
	'SA_PURCHENQ' => array(SS_PURCH|3, _("Purchase Enquiry entry")),
	'SA_PURCHQUOTE' => array(SS_PURCH|4, _("Purchase Quotation entry")),
	
	'SA_PURCHASEORDER' => array(SS_PURCH|5, _("Purchase order entry")),
	'SA_GRN' => array(SS_PURCH|6, _("Purchase receive")),
	'SA_SUPPLIERINVOICE' => array(SS_PURCH|7, _("Supplier invoices")),
	'SA_GRNDELETE' => array(SS_PURCH|8, _("Deleting GRN items during invoice entry")),
	'SA_SUPPLIERCREDIT' => array(SS_PURCH|9, _("Supplier credit notes")),
	'SA_SUPPLIERPAYMNT' => array(SS_PURCH|10, _("Supplier payments")),
	'SA_SUPPLIERALLOC' => array(SS_PURCH|11, _("Supplier payments allocations")),
	
	'SA_PURCHASEORDER_AUTH' => array(SS_PURCH|12, _("Purchase Order Authorizations")),
	'SA_PURCHASEORDER_AUTH_VIEW' => array(SS_PURCH|13, _("Purchase Order Authorizations View")),
	'SA_PURCHASEORDER_AUTH_ACCEPT' => array(SS_PURCH|14, _("Purchase Order Authorizations Accept")),
	'SA_PURCHASEORDER_AUTH_REJECT' => array(SS_PURCH|15, _("Purchase Order Authorizations Reject")),
	'SA_PURCHASEORDER_AUTH_PRINT' => array(SS_PURCH|16, _("Purchase Order Authorizations Print")),
	'SA_PURCHASEORDER_AUTH_CHKBOX' => array(SS_PURCH|17, _("Purchase Order Authorizations Checkbox")),
	'SA_SUPPLIERINVOICEREV' => array(SS_PURCH|18, _("Direct Supplier Invoice (Against Reverse)")),
	'SA_SUPPLIER_BANK_DETAILS' => array(SS_PURCH|19, _("Supplier Bank Details")),
	'SA_INVMULTIPLE' => array(SS_PURCH|20,_("Purchase Invoice Against Mulitple Purchase orders")),
	
	'SA_SUPPLIERPDC' => array(SS_PURCH|21,_("Supplier PDC Entry")),
	'SA_SUPPLIERPDC_ADDNEW' => array(SS_PURCH|22,_("Supplier PDC Entry Add")),
	'SA_SUPP_RECALL_PDC' => array(SS_PURCH|23,_("Recall Supplier PDC Entry")),
	'SA_SUPP_RETURN_PDC' => array(SS_PURCH|24,_("Supplier PDC Return Entry")),
	'SA_SUPP_BOUNCE_PDC' => array(SS_PURCH|25,_("Supplier PDC  Bounce Entry")),
	
	'SA_CHEQUE_TRANS_INQ' => array(SS_PURCH|26,_("Cheque Transactions Inquiry")),
	'SA_GEN_PURCHASEORDER' => array(SS_PURCH|27,_("General Purchase order entry")),
	
	
	'SA_SUPPLIERANALYTIC' => array(SS_PURCH_A|1, _("Supplier analytical reports")),
	'SA_SUPPBULKREP' => array(SS_PURCH_A|2, _("Supplier document bulk reports")),
	'SA_SUPPPAYMREP' => array(SS_PURCH_A|3, _("Supplier payments report")),
    'SA_UNALLOC_SUPP_TRANS_REP' => array(SS_PURCH_A|4, _("Unallocated Supplier Transactions Report")),
	'SA_PURCHASE_REGISTER_REP' => array(SS_PURCH_A|5, _("Purchase Register Report")),
	'SA_SUPP_STATEMENT_ACC_REP' => array(SS_PURCH_A|6, _("Statement of Accounts of Creditors")),
	'SA_SUPP_OUTSTANDING_REP' => array(SS_PURCH_A|7, _("Creditors Outstanding Report")),
	'SA_SUPP_PAYABLES_LEDGER_REP' => array(SS_PURCH_A|8, _("Payables Ledger Report")),
    'SA_CHEQUE_REP' => array(SS_PURCH_A|9, _("Cheque Reports")),
    'SA_SUPP_PDC_REP' => array(SS_PURCH_A|10, _("Supplier PDC Reports")),

//
// Inventory 
//
	'SA_ITEM' => array(SS_ITEMS_C|1, _("Stock items add/edit")),
	'SA_SALESKIT' => array(SS_ITEMS_C|2, _("Sales kits")),
	'SA_ITEMCATEGORY' => array(SS_ITEMS_C|3, _("Item categories")),
	'SA_UOM' => array(SS_ITEMS_C|4, _("Units of measure")),
	'SA_SALESINV' => array(SS_ITEMS_C|5, _("Transactions Inquiry Sales Invoice")),
	'SA_OUTSTANDING_PO' => array(SS_ITEMS_C|6, _("Transactions Inquiry Outstanding PO's")),
	'SA_OUTSTANDING_SO' => array(SS_ITEMS_C|7, _("Transactions Inquiry Outstanding SO's")),
	'SA_SALESINV_CUST' => array(SS_ITEMS_C|8, _("Transactions Inquiry Sales Invoice Customer")),
	'SA_CUST_OUTSTANDING_SO' => array(SS_ITEMS_C|9, _("Transactions Inquiry Customer Outstanding SO's")),
	'SA_PROMO_DISC' => array(SS_ITEMS_C|10, _("Offers")),
	'SA_ITEM_SUB_CATEGORY' => array(SS_ITEMS_C|11, _("Item Subcategories")),
	'SA_STOCK_MONTH_SALES' => array(SS_ITEMS_C|12, _("YTD Sales")),

	'SA_ITEMSSTATVIEW' => array(SS_ITEMS|1, _("Stock status view")),
	'SA_ITEMSTRANSVIEW' => array(SS_ITEMS|2, _("Stock transactions view")),
	'SA_FORITEMCODE' => array(SS_ITEMS|3, _("Foreign item codes entry")),
	'SA_LOCATIONTRANSFER' => array(SS_ITEMS|4, _("Inventory location transfers")),
	'SA_INVENTORYADJUSTMENT' => array(SS_ITEMS|5, _("Inventory adjustments")),
	
	'SA_MATERIAL_INDENT' => array(SS_ITEMS|6, _("Material Indent Request")),
	'SA_RECEIVE_INDENT' => array(SS_ITEMS|7, _("Inventory Receive Against Indent")),

	'SA_REORDER' => array(SS_ITEMS_A|1, _("Reorder levels")),
	'SA_ITEMSANALYTIC' => array(SS_ITEMS_A|2, _("Items analytical reports and inquiries")),
	'SA_ITEMSVALREP' => array(SS_ITEMS_A|3, _("Inventory valuation report")),
	
	'SA_MATERIAL_INDENT_INQUIRY' => array(SS_ITEMS_A|4, _("Material Indent Inquiry")),
	'SA_INVENTORY_ADJUSTMENT_INQUIRY' => array(SS_ITEMS_A|5, _("Inventory Adjustment Enquiry")),
	'SA_ITEMSTRANSINQ' => array(SS_ITEMS_A|6, _("Inventory transactions Inquiry")),	
	
'SA_INVENTORY_ADJUSTMENT_REPORT' => array(SS_ITEMS_A|7, _("Inventory Adjustment Report")),
'SA_INVENTORY_ITEM_WISE_PENDING_PURCHASE_ORDER_REPORT' => array(SS_ITEMS_A|8, _("Item wise Pending Purchase Order Report")),
'SA_INVENTORY_ITEM_WISE_PENDING_SALES_ORDER_REPORT' => array(SS_ITEMS_A|9, _("Item wise Pending Sales Order Report")),
'SA_INVENTORY_AGING_REPORT' => array(SS_ITEMS_A|10, _("Inventory Aging Report")),
'SA_INVENTORY_STOCK_TRANSFER_REPORT' => array(SS_ITEMS_A|11, _("Stock Transfer Report")),
'SA_CATEGORY_MONTHLY_REP' => array(SS_ITEMS_A|12, _("Category Wise Monthly Report")),
'SA_SALES_ORDER_QTY_REPORT' => array(SS_ITEMS_A|13, _("Order Qty Based On Sales With All Details")),			
'SA_INVENTORY_VALUATION_REPORT' => array(SS_ITEMS_A|14, _("Dated Stock Sheet")),
'SA_INVENTORY_STOCK_MOVEMENT_REPORT' => array(SS_ITEMS_A|15, _("Single Stock Movement Report")),

//
// Fixed Assets
//
	'SA_ASSET' => array(SS_ASSETS_C|1, _("Fixed Asset items add/edit")),
	'SA_ASSETCATEGORY' => array(SS_ASSETS_C|2, _("Fixed Asset categories")),
	'SA_ASSETCLASS' => array(SS_ASSETS_C|4, _("Fixed Asset classes")),

	'SA_ASSETSTRANSVIEW' => array(SS_ASSETS|1, _("Fixed Asset transactions view")),
	'SA_ASSETTRANSFER' => array(SS_ASSETS|2, _("Fixed Asset location transfers")),
	'SA_ASSETDISPOSAL' => array(SS_ASSETS|3, _("Fixed Asset disposals")),
    'SA_DEPRECIATION' => array(SS_ASSETS|4, _("Depreciation")),

	'SA_ASSETSANALYTIC' => array(SS_ASSETS_A|1, _("Fixed Asset analytical reports and inquiries")),

//
// Manufacturing module 
//
	'SA_BOM' => array(SS_MANUF_C|1, _("Bill of Materials")),

	'SA_MANUFTRANSVIEW' => array(SS_MANUF|1, _("Manufacturing operations view")),
	'SA_WORKORDERENTRY' => array(SS_MANUF|2, _("Work order entry")),
	'SA_MANUFISSUE' => array(SS_MANUF|3, _("Material issues entry")),
	'SA_MANUFRECEIVE' => array(SS_MANUF|4, _("Final product receive")),
	'SA_MANUFRELEASE' => array(SS_MANUF|5, _("Work order releases")),

	'SA_WORKORDERANALYTIC' => array(SS_MANUF_A|1, _("Work order analytical reports and inquiries")),
	'SA_WORKORDERCOST' => array(SS_MANUF_A|2, _("Manufacturing cost inquiry")),
	'SA_MANUFBULKREP' => array(SS_MANUF_A|3, _("Work order bulk reports")),
	'SA_BOMREP' => array(SS_MANUF_A|4, _("Bill of materials reports")),
//
// Dimensions
//
	'SA_DIMTAGS' => array(SS_DIM_C|1, _("Dimension tags")),

	'SA_DIMTRANSVIEW' => array(SS_DIM|1, _("Dimension view")),

	'SA_DIMENSION' => array(SS_DIM|2, _("Dimension entry")),

	'SA_DIMENSIONREP' => array(SS_DIM|3, _("Dimension reports")),
//
// Banking and General Ledger
//
	'SA_ITEMTAXTYPE' => array(SS_GL_C|1, _("Item tax type definitions")),
	'SA_GLACCOUNT' => array(SS_GL_C|2, _("GL accounts edition")),
	'SA_GLACCOUNTGROUP' => array(SS_GL_C|3, _("GL account groups")),
	'SA_GLACCOUNTCLASS' => array(SS_GL_C|4, _("GL account classes")),
	'SA_QUICKENTRY' => array(SS_GL_C|5, _("Quick GL entry definitions")),
	'SA_CURRENCY' => array(SS_GL_C|6, _("Currencies")),
	'SA_BANKACCOUNT' => array(SS_GL_C|7, _("Bank accounts")),
	'SA_TAXRATES' => array(SS_GL_C|8, _("Tax rates")),
	'SA_TAXGROUPS' => array(SS_GL_C|12, _("Tax groups")),
	'SA_FISCALYEARS' => array(SS_GL_C|9, _("Fiscal years maintenance")),
	'SA_GLSETUP' => array(SS_GL_C|10, _("Company GL setup")),
	'SA_GLACCOUNTTAGS' => array(SS_GL_C|11, _("GL Account tags")),
	'SA_GLCLOSE' => array(SS_GL_C|14, _("Closing GL transactions")),
	'SA_GLREOPEN' => array(SS_GL_C|15, _("Reopening GL transactions")), // see below
	'SA_MULTIFISCALYEARS' => array(SS_GL_C|13, _("Allow entry on non closed Fiscal years")),

	'SA_BANKTRANSVIEW' => array(SS_GL|1, _("Bank transactions view")),
	'SA_GLTRANSVIEW' => array(SS_GL|2, _("GL postings view")),
	'SA_EXCHANGERATE' => array(SS_GL|3, _("Exchange rate table changes")),
	'SA_PAYMENT' => array(SS_GL|4, _("Bank payments")),
	'SA_DEPOSIT' => array(SS_GL|5, _("Bank deposits")),
	'SA_BANKTRANSFER' => array(SS_GL|6, _("Bank account transfers")),
	'SA_RECONCILE' => array(SS_GL|7, _("Bank reconciliation")),
	'SA_JOURNALENTRY' => array(SS_GL|8, _("Manual journal entries")),
	'SA_BANKJOURNAL' => array(SS_GL|11, _("Journal entries to bank related accounts")),
	'SA_BUDGETENTRY' => array(SS_GL|9, _("Budget edition")),
	'SA_STANDARDCOST' => array(SS_GL|10, _("Item standard costs")),
	'SA_ACCRUALS' => array(SS_GL|12, _("Revenue / Cost Accruals")),
	'SA_MULTI_CASH_BILL' => array(SS_GL|13, _("Multiple Purchse Cash Bill Entry")),
	'SA_PETTY_CASH_ENTRY' => array(SS_GL|14, _("Petty Cash Entry")),

	'SA_GLANALYTIC' => array(SS_GL_A|1, _("GL analytical reports and inquiries")),
	'SA_TAXREP' => array(SS_GL_A|2, _("Tax reports and inquiries")),
	'SA_BANKREP' => array(SS_GL_A|3, _("Bank reports and inquiries")),
	'SA_GLREP' => array(SS_GL_A|4, _("GL reports and inquiries")),
	'SA_PETTY_CASH_INQ_VIEW' => array(SS_GL_A|5, _("Petty Cash Inquiry and View")),
	'SA_BANK_PAYMENT_REP' => array(SS_GL_A|6, _("Bank payments report")),
	'SA_BANK_DEPOSIT_REP' => array(SS_GL_A|7, _("Bank Deposits report")),

    //COMPLAINT 
	'SA_COMPLAINT' => array(SS_COMPLAINTS_C|1, _("Register a Complaint")),
	'SA_COMPLAINT_INQUIRY' => array(SS_COMPLAINTS|2, _("View or Update Complaints")),
	'SA_COMPLAINT_REP' => array(SS_COMPLAINTS|3, _("Complaint Reports")),
	'SA_COMPLAINT_INQUIRY_REP' => array(SS_COMPLAINTS|4, _("Complaint Summary Report")),
	'SA_COMPLAINT_HISTORY_REP' => array(SS_COMPLAINTS|5, _("Complaint History Report")),
	
	// Canvas Theme Dashboard Widgets
	'SA_SDB_CUSTOMERS_COUNT' => array(SS_SALES_DASHBOARDS|1, _("Customers Count")),
	'SA_SDB_BRANCHES_COUNT' => array(SS_SALES_DASHBOARDS|2, _("Branches Count")),
	'SA_SDB_SALESMAN_COUNT' => array(SS_SALES_DASHBOARDS|3, _("Salesman Count")),
	'SA_SDB_ODINVOICE_COUNT' => array(SS_SALES_DASHBOARDS|4, _("Overdue Invoices Count")),
	
	'SA_SDB_SALES_ENQUIRIES' => array(SS_SALES_DASHBOARDS|5, _("Enquiries in Fiscal Year")),
	'SA_SDB_SALES_QUOTATIONS' => array(SS_SALES_DASHBOARDS|6, _("Quotations in Fiscal Year")),
	'SA_SDB_SALES_ORDERS' => array(SS_SALES_DASHBOARDS|7, _("Orders in Fiscal Year")),
	'SA_SDB_SALES_DISPATCHES' => array(SS_SALES_DASHBOARDS|8, _("Dispatches in Fiscal Year")),
	'SA_SDB_SALES_INVOICES' => array(SS_SALES_DASHBOARDS|9, _("Invoices in Fiscal Year")),
	'SA_SDB_SALES_RETURNS' => array(SS_SALES_DASHBOARDS|10, _("Returns in Fiscal Year")),
	'SA_SDB_SALES_TOP10_CUSTOMERS' => array(SS_SALES_DASHBOARDS|11, _("Top 10 Customers")),
	'SA_SDB_SALES_CUSTOMER_AGING' => array(SS_SALES_DASHBOARDS|12, _("Customers Aging")),
	
	'SA_SDB_SALES_TOTAL' => array(SS_SALES_DASHBOARDS|13, _("Sales Total")),
	'SA_SDB_SALES_AGING_TOTAL' => array(SS_SALES_DASHBOARDS|14, _("Aging Total")),
	
	'SA_SDB_SALES_OD_INVOICE_LIST' => array(SS_SALES_DASHBOARDS|15, _("Overdue Invoices List")),
	'SA_SDB_SALES_UPCOMING_DISPATCHES' => array(SS_SALES_DASHBOARDS|16, _("Outstanding & Upcoming Dispatches List")),
	
	//Purchase Dashboards
	'SA_PDB_SUPPLIERS_COUNT' => array(SS_PURCHASE_DASHBOARDS|1, _("Suppliers Count")),
	'SA_PDB_PURCH_ORDERS_COUNT' => array(SS_PURCHASE_DASHBOARDS|2, _("New Purchase Orders Count")),
    'SA_PDB_SUPPLIER_INVOICES_COUNT' => array(SS_PURCHASE_DASHBOARDS|3, _("Supplier Invoices Count")),
	'SA_PDB_SUPPLIER_ODINVOICE_COUNT' => array(SS_PURCHASE_DASHBOARDS|4, _("Supplier Overdue Invoices Count")),
	'SA_PDB_PENDING_PO_APPROVALS' => array(SS_PURCHASE_DASHBOARDS|5, _("Pending Purchase Order Approvals")),
	
	'SA_PDB_PURCHASES_ENQUIRIES' => array(SS_PURCHASE_DASHBOARDS|6, _("Enquiries in Fiscal Year")),
	'SA_PDB_PURCHASES_QUOTATIONS' => array(SS_PURCHASE_DASHBOARDS|7, _("Quotations in Fiscal Year")),
	'SA_PDB_PURCHASES_ORDERS' => array(SS_PURCHASE_DASHBOARDS|8, _("Orders in Fiscal Year")),
	'SA_PDB_PURCHASES_DISPATCHES' => array(SS_PURCHASE_DASHBOARDS|9, _("GRN / MRN in Fiscal Year")),
	'SA_PDB_PURCHASES_INVOICES' => array(SS_PURCHASE_DASHBOARDS|10, _("Invoices in Fiscal Year")),
	'SA_PDB_PURCHASES_RETURNS' => array(SS_PURCHASE_DASHBOARDS|11, _("Returns in Fiscal Year")),
	'SA_PDB_PURCHASES_TOP10_SUPPLIERS' => array(SS_PURCHASE_DASHBOARDS|12, _("Top 10 suppliers in fiscal year")),
	'SA_PDB_SUPPLIERS_AGING' => array(SS_PURCHASE_DASHBOARDS|13, _("Total Suppliers Aged Analysis")),
	'SA_PDB_PURCHASES_TOTAL' => array(SS_PURCHASE_DASHBOARDS|14, _("Purchasable Total")),
	'SA_PDB_PURCHASES_AGING_TOTAL' => array(SS_PURCHASE_DASHBOARDS|15, _("Aging Total")),
	'SA_PDB_PURCHASES_OD_INVOICE_LIST' => array(SS_PURCHASE_DASHBOARDS|16, _("Purchase Overdue Invoices List")),
	
	//Inventory Dashboards
	'SA_IDB_ITEMS_COUNT' => array(SS_INVENTORY_DASHBOARDS|1, _("Items Count")),
	'SA_IDB_LOCATIONS_COUNT' => array(SS_INVENTORY_DASHBOARDS|2, _("Locations Count")),
	'SA_IDB_KITS_COUNT' => array(SS_INVENTORY_DASHBOARDS|3, _("Kits Count")),
	'SA_IDB_REORDER_COUNT' => array(SS_INVENTORY_DASHBOARDS|4, _("Below Reorder Level Count")),
	'SA_IDB_ASSEMBLED_ITEMS_COUNT' => array(SS_INVENTORY_DASHBOARDS|5, _("Assembled Items Count")),
	'SA_IDB_MANUF_ITEMS_COUNT' => array(SS_INVENTORY_DASHBOARDS|6, _("Manufactured Items Count")),
	'SA_IDB_WORK_CENTRES_COUNT' => array(SS_INVENTORY_DASHBOARDS|7, _("Work Centres Count")),
	'SA_IDB_OPEN_WO_COUNT' => array(SS_INVENTORY_DASHBOARDS|8, _("Open Workorders")),
	'SA_IDB_FIXED_ASSETS_COUNT' => array(SS_INVENTORY_DASHBOARDS|9, _("Fixed Assets Count")),
	'SA_IDB_FIXED_LOCATIONS_COUNT' => array(SS_INVENTORY_DASHBOARDS|10, _("Fixed Locations Count")),
	'SA_IDB_FIXED_CATEGORIES_COUNT' => array(SS_INVENTORY_DASHBOARDS|11, _("Fixed Categories Count")),
	'SA_IDB_FIXED_ASSET_CLASSES_COUNT' => array(SS_INVENTORY_DASHBOARDS|12, _("Fixed Assets Count")),
	'SA_IDB_INVENTORY_TOP10_ITEMS' => array(SS_INVENTORY_DASHBOARDS|13, _("Top 10 Items")),
	'SA_IDB_INVENTORY_TOTAL_SALES' => array(SS_INVENTORY_DASHBOARDS|14, _("Total Sales")),
	'SA_IDB_INVENTORY_TOTAL_COSTS' => array(SS_INVENTORY_DASHBOARDS|15, _("Total Costs")),
	'SA_IDB_INVENTORY_TOTAL_RESULTS' => array(SS_INVENTORY_DASHBOARDS|16, _("Total Results")),
	'SA_IDB_INVENTORY_ITEMS_BELOW_REORDER' => array(SS_INVENTORY_DASHBOARDS|17, _("Items are Below Reorder Level")),
	'SA_IDB_INVENTORY_TOP10_STAGNANT_ITEMS' => array(SS_INVENTORY_DASHBOARDS|18, _("Most Stagnant 10 Items")),
	'SA_IDB_INVENTORY_PENDING_MATERIAL_INDENT_REQUESTS' => array(SS_INVENTORY_DASHBOARDS|19, _("Pending Material Indent Requests")),
	
	//Dimension Dashboards
	'SA_DDB_DIMENSIONS_COUNT' => array(SS_DIMENSION_DASHBOARDS|1, _("Dimensions Count")),
	'SA_DDB_TYPE2_DIMENSIONS_COUNT' => array(SS_DIMENSION_DASHBOARDS|2, _("Type 2 Dimensions Count")),
	'SA_DDB_DIMENSIONS_TOTAL_BALANCE' => array(SS_DIMENSION_DASHBOARDS|3, _("Dimensions Total Balance")),
	'SA_DDB_DIMENSIONS_TOTAL_RESULT' => array(SS_DIMENSION_DASHBOARDS|4, _("Dimensions Total Result")),
	'SA_DDB_TOP5_DIMENSIONS' => array(SS_DIMENSION_DASHBOARDS|5, _("Top 5 Dimensions in fiscal year")),
	
	//Banking and GL Dashboards
	'SA_FDB_RECEIVABLES_COUNT' => array(SS_FINANCE_DASHBOARDS|1, _("Receivables Count")),
	'SA_FDB_PAYABLES_COUNT' => array(SS_FINANCE_DASHBOARDS|2, _("Payables Count")),
	'SA_FDB_TODAY_DEPOSITS_COUNT' => array(SS_FINANCE_DASHBOARDS|3, _("Todays Deposits Count")),
	'SA_FDB_TODAY_PAYMENTS_COUNT' => array(SS_FINANCE_DASHBOARDS|4, _("Todays Payments Count")),
	'SA_FDB_CLASS_BALANCES' => array(SS_FINANCE_DASHBOARDS|5, _("Class Balances")),
	'SA_FDB_GL_MONTH_PERFORMANCE' => array(SS_FINANCE_DASHBOARDS|6, _("GL Monthly Performance")),
	'SA_FDB_GL_WEEK_PERFORMANCE' => array(SS_FINANCE_DASHBOARDS|7, _("GL Weekly Performance")),
	'SA_FDB_GL_CASH_FLOW' => array(SS_FINANCE_DASHBOARDS|8, _("GL Cash Blow")),
	'SA_FDB_GL_BANK_BALANCE' => array(SS_FINANCE_DASHBOARDS|9, _("GL Bank Account Balances")),
	
	
	
);

if (!@$SysPrefs->allow_gl_reopen)
	unset($security_areas['SA_GLREOPEN']);
/*
	This function should be called whenever we want to extend core access level system
	with new security areas and/or sections i.e.: 
	. on any page with non-standard security areas
	. in security roles editor
	The call should be placed between session.inc inclusion and page() call.
	Up to 155 security sections and 155 security areas for any extension can be installed.
*/
function add_access_extensions()
{
	global $security_areas, $security_sections, $installed_extensions;

	foreach($installed_extensions as $extid => $ext) {
		$accext = hook_invoke($ext['package'], 'install_access', $dummy);
		if ($accext == null) continue;

		$scode = 100;
		$acode = 100;
		$extsections = $accext[1];
		$extareas = $accext[0];
		$extcode = $extid<<16;
		
		$trans = array();
		foreach($extsections as $code =>$name) {
			$trans[$code] = $scode<<8;
			// reassign section codes
			$security_sections[$trans[$code]|$extcode] = $name;
			$scode++;
		}
		foreach($extareas as $code => $area) {
			$section = $area[0]&0xff00;
			// extension modules:
			// if area belongs to nonstandard section
			// use translated section codes and
			// preserve lower part of area code
			if (isset($trans[$section])) {
				$section = $trans[$section];
			} 
				// otherwise assign next available
				// area code >99
			$area[0] = $extcode | $section | ($acode++);
			$security_areas[$code] = $area;
		}
	}
}

function check_edit_access($name)
{
	global $input_security;

	$access = @$input_security[$name];

	if (!$access)
		$access = @$input_security['']; // default access level

	if (!$access)
		return true; // if constraint is not defined edit access is allowed

	return  user_check_access($access);
}
/*
	Returns POST value or null if edit access to $name control is forbidden.
*/
function access_post($name, $dflt=null)
{
	if (!check_edit_access($name))
		return $dflt;
	else
		return get_post($name, $dflt);
}

/*
	Returns numeric input value or null if edit access to $name control is forbidden.
*/
function access_num($name, $dflt=null)
{
	if (!check_edit_access($name))
		return $dflt;
	else
		return input_num($name, $dflt);
}
