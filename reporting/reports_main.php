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
$path_to_root="..";
$page_security = 'SA_OPEN';
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/reporting/includes/reports_classes.inc");
$js = "";
if ($SysPrefs->use_popup_windows && $SysPrefs->use_popup_search)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

add_js_file('reports.js');

page(_($help_context = "Reports and Analysis"), false, false, "", $js);

$reports = new BoxReports;

$dim = get_company_pref('use_dimension');

$reports->addReport(RC_SUPPLIER, 9991, _('PO Register'),
    array(  _('Start Date') => 'DATEBEGIN',
            _('End Date') => 'DATEENDM',
            _('Supplier') => 'SUPPLIERS_NO_FILTER',
            _('Suppress Zeros') => 'YES_NO',
            _('Comments') => 'TEXTBOX',
            _('Orientation') => 'ORIENTATION',
            _('Destination') => 'DESTINATION'),
			'PO');

$reports->addReport(RC_SUPPLIER, 9992, _('PO Summary'),
array(  _('Start Date') => 'DATEBEGIN',
		_('End Date') => 'DATEENDM',
		_('Supplier') => 'SUPPLIERS_NO_FILTER',
		_('Suppress Zeros') => 'YES_NO',
		_('Comments') => 'TEXTBOX',
		_('Orientation') => 'ORIENTATION',
		_('Destination') => 'DESTINATION'),
		'PO');

$reports->addReport(RC_SUPPLIER, 9993, _('Itemwise Inventory PO Register'),
array(  _('Start Date') => 'DATEBEGIN',
		_('End Date') => 'DATEENDM',
		_('Supplier') => 'SUPPLIERS_NO_FILTER',
		_('Suppress Zeros') => 'YES_NO',
		_('Comments') => 'TEXTBOX',
		_('Orientation') => 'ORIENTATION',
		_('Destination') => 'DESTINATION'),
		'PO','SA_DEB_OUT_REP');

$reports->addReport(RC_SUPPLIER, 9994, _('Itemwise Inventory PO Summary'),
array(  _('Start Date') => 'DATEBEGIN',
		_('End Date') => 'DATEENDM',
		_('Supplier') => 'SUPPLIERS_NO_FILTER',
		_('Suppress Zeros') => 'YES_NO',
		_('Comments') => 'TEXTBOX',
		_('Orientation') => 'ORIENTATION',
		_('Destination') => 'DESTINATION'),
		'PO','SA_DEB_OUT_REP');

$reports->addReport(RC_SUPPLIER, 9995, _('Supplierwise Inventory PO Summary'),
array(  _('Start Date') => 'DATEBEGIN',
		_('End Date') => 'DATEENDM',
		_('Supplier') => 'SUPPLIERS_NO_FILTER',
		_('Suppress Zeros') => 'YES_NO',
		_('Comments') => 'TEXTBOX',
		_('Orientation') => 'ORIENTATION',
		_('Destination') => 'DESTINATION'),
		'PO','SA_DEB_OUT_REP');

$reports->addReport(RC_SUPPLIER, 9996, _('Supplierwise Inventory PO Register'),
array(  _('Start Date') => 'DATEBEGIN',
		_('End Date') => 'DATEENDM',
		_('Supplier') => 'SUPPLIERS_NO_FILTER',
		_('Suppress Zeros') => 'YES_NO',
		_('Comments') => 'TEXTBOX',
		_('Orientation') => 'ORIENTATION',
		_('Destination') => 'DESTINATION'),
		'PO','SA_DEB_OUT_REP');

$reports->addReportClass(_('Customer'), RC_CUSTOMER);
$reports->addReport(RC_CUSTOMER, 101, _('Customer &Balances'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Group Company') => 'CUSTOMERGROUPCMPNY',
			_('Legal Group') => 'LEGALGROUP',
			_('Customer Class') => 'CUSTOMERCLASS',
		    _('Sales Areas') => 'AREAS',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Show Balance') => 'YES_NO',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
	'Customer','SA_CUSTPAYMREP'
		);
			
/*$reports->addReport(RC_CUSTOMER, 1010, _('Group Company Wise Outstanding Register Report'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Group Company') => 'CUSTOMERGROUPCMPNY',
			//_('Customer') => 'CUSTOMERS_NO_FILTER',
			//_('Sales Person') => 'SALESMEN',
			//_('Show Balance') => 'YES_NO',
			//_('Currency Filter') => 'CURRENCY',
			//_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'));
*/			
$reports->addReport(RC_CUSTOMER, 102, _('&Aged Customer Analysis'),
	array(	_('End Date') => 'DATE',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Show Also Allocated') => 'YES_NO',
			_('Summary Only') => 'YES_NO',
			_('Suppress Zeros') => 'YES_NO',
			_('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
	'Customer','SA_CUSTPAYMREP');
$reports->addReport(RC_CUSTOMER, 115, _('Customer Trial Balance'),
    array(  _('Start Date') => 'DATEBEGIN',
            _('End Date') => 'DATEENDM',
            _('Customer') => 'CUSTOMERS_NO_FILTER',
            _('Sales Areas') => 'AREAS',
            _('Sales Person') => 'SALESMEN',
            _('Currency Filter') => 'CURRENCY',
            _('Suppress Zeros') => 'YES_NO',
            _('Comments') => 'TEXTBOX',
            _('Orientation') => 'ORIENTATION',
            _('Destination') => 'DESTINATION'),
			'Customer','SA_CUSTPAYMREP');
$reports->addReport(RC_CUSTOMER, 103, _('Customer &Detail Listing'),
	array(	_('Activity Since') => 'DATEBEGIN',
			_('CR No. / Sponsor Name') => 'TEXT',
			_('Sales Areas') => 'AREAS',
			_('Sales Person') => 'SALESMEN',
			_('Activity Greater Than') => 'TEXT',
			_('Activity Less Than') => 'TEXT',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Customer','SA_CUSTBULKREP');
			
$reports->addReport(RC_CUSTOMER, 1030, _('Customer &Contact Listing'),
	array(	_('Activity Since') => 'DATEBEGIN',
			_('Sales Areas') => 'AREAS',
			_('Sales Person') => 'SALESMEN',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Customer','SA_DEB_OUT_REP');
			
			
$reports->addReport(RC_CUSTOMER, 114, _('Sales &Summary Report'),
	array(	_('Start Date') => 'DATEBEGINTAX',
			_('End Date') => 'DATEENDTAX',
			_('Tax Id Only') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_TAXREP');
			
$reports->addReport(RC_CUSTOMER, 104, _('&Price Listing'),
	array(	_('Currency Filter') => 'CURRENCY',
			_('Inventory Category') => 'CATEGORIES',
			_('Sales Types') => 'SALESTYPES',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Show Pictures') => 'YES_NO',
			_('Show GP %') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_PRICEREP');

$reports->addReport(RC_CUSTOMER, 1040, _('&Price Listing - Salesman'),
	array(	_('Currency Filter') => 'CURRENCY',
			_('Inventory Category') => 'CATEGORIES',
			_('Sales Types') => 'SALESTYPES',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Show Pictures') => 'YES_NO',
			//_('Show GP %') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_PRICE_LIST_SALE_REP');
			
$reports->addReport(RC_CUSTOMER, 105, _('&Order Status Listing'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Stock Location') => 'LOCATIONS',
			_('Back Orders Only') => 'YES_NO',
			_('Sales Person') => 'SALESMEN',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SALES_ORDER_STATUS_LISTREP');
$reports->addReport(RC_CUSTOMER, 106, _('&Salesman Listing (Invoices)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Sales Person') => 'SALESMEN', 
			_('Summary Only') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Salesman','SA_SALESMANREP');

$reports->addReport(RC_CUSTOMER, 1060, _('&Salesman Listing (Orders)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Sales Person') => 'SALESMEN', 
			_('Summary Only') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Salesman','SA_SALESMANREP');			
$reports->addReport(RC_CUSTOMER, 107, _('Print &Invoices'),
	array(	_('From') => 'INVOICE',
			_('To') => 'INVOICE',
			_('Currency Filter') => 'CURRENCY',
			_('email Customers') => 'YES_NO',
			_('Payment Link') => 'PAYMENT_LINK',
			_('Comments') => 'TEXTBOX',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Orientation') => 'ORIENTATION'
),
			'Sales', 'SA_SALESTRANSVIEW');
$reports->addReport(RC_CUSTOMER, 113, _('Print &Credit Notes'),
	array(	_('From') => 'CREDIT',
			_('To') => 'CREDIT',
			_('Currency Filter') => 'CURRENCY',
			_('email Customers') => 'YES_NO',
			_('Payment Link') => 'PAYMENT_LINK',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION'),
			'Sales','SA_SALESTRANSVIEW');
$reports->addReport(RC_CUSTOMER, 110, _('Print &Deliveries'),
	array(	_('From') => 'DELIVERY',
			_('To') => 'DELIVERY',
			_('email Customers') => 'YES_NO',
			_('Print as Packing Slip') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION'),
			'Sales','SA_SALESTRANSVIEW');
$reports->addReport(RC_CUSTOMER, 108, _('Print &Statements'),
	array(	_('Customer') => 'CUSTOMERS_NO_FILTER',
	        _('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Show Also Allocated') => 'YES_NO',
			_('Email Customers') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION'),
			'Sales','SA_CUSTSTATREP');
$reports->addReport(RC_CUSTOMER, 109, _('&Print Sales Orders'),
	array(	_('From') => 'ORDERS',
			_('To') => 'ORDERS',
			_('Currency Filter') => 'CURRENCY',
			_('Email Customers') => 'YES_NO',
			_('Print as Quote') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION'),
			'Sales','SA_CUSTSTATREP');
$reports->addReport(RC_CUSTOMER, 111, _('&Print Sales Quotations'),
	array(	_('From') => 'QUOTATIONS',
			_('To') => 'QUOTATIONS',
			_('Currency Filter') => 'CURRENCY',
			_('Email Customers') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION'),
			'Sales','SA_SALESTRANSVIEW');
$reports->addReport(RC_CUSTOMER, 112, _('Print Receipts'),
	array(	_('From') => 'RECEIPT',
			_('To') => 'RECEIPT',
			_('Currency Filter') => 'CURRENCY',
            _('Email Customers') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION'),
			'Sales','SA_SALESTRANSVIEW');
			
			
$reports->addReport(RC_CUSTOMER, 1012, _('Sales Order &Listing'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Dimension')." 1" =>  'USER_DIMENSIONS1',
			 _('Sales Person') => 'SALESMEN',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SALESORDER');
			
$reports->addReport(RC_CUSTOMER, 1017, _('Sales Order Items &Listing'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Dimension')." 1" =>  'USER_DIMENSIONS1',
			 _('Sales Person') => 'SALESMEN',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SALESORDER');
			
			
$reports->addReport(RC_CUSTOMER, 1024, _('Day wise Sales Order &Listing'),
	array(	_('Start Date')      => 'DATEBEGIN',
			_('End Date')        => 'DATEENDM',
			_('Customer')        => 'CUSTOMERS_NO_FILTER',
			_('Location')        => 'LOCATIONS',
			_('Dimension')." 1"  => 'USER_DIMENSIONS1',
			 _('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Orientation')     => 'ORIENTATION',
			_('Destination')     => 'DESTINATION'),
			'Sales','SA_SALESORDER');				

$reports->addReport(RC_CUSTOMER, 1018, _('Pending DO Items &Listing'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Dimension')." 1" =>  'USER_DIMENSIONS1',
			 _('Sales Person') => 'SALESMEN',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'DO','SA_SALESDELIVERY');			

$reports->addReport(RC_CUSTOMER, 1013, _('Outstanding DO &Listing (DO Done not Invoiced)'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Dimension')." 1" =>  'USER_DIMENSIONS1',
			_('Sales Person') => 'SALESMEN',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
		'DO','SA_SALESDELIVERY');	

$reports->addReport(RC_CUSTOMER, 1019, _('Sales Register with SO and DO Details'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Dimension')." 1" =>  'USER_DIMENSIONS1',
			_('Sales Person') => 'SALESMEN',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SALESDELIVERY');

			

$reports->addReport(RC_CUSTOMER, 1016, _('Sales Invoice &Listing'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Location') => 'LOCATIONS',
			_('Dimension')." 1" =>  'USER_DIMENSIONS1',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SINLISTINGREP');
			

			
$reports->addReport(RC_CUSTOMER, 1027, _('Monthly Sales Summary Report'),
	array(	_('Year') => 'TRANS_YEARS',
			_('Dimension')." 1" =>  'DIMENSIONS1',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SINLISTINGREP');
			
$reports->addReport(RC_CUSTOMER, 1022, _('Day wise Sales Invoice &Listing'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Location') => 'LOCATIONS',
			_('Dimension')." 1" =>  'USER_DIMENSIONS1',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SINLISTINGREP');			
			

$reports->addReport(RC_CUSTOMER, 1021, _('Customer Credit Notes &Listing'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Dimension')." 1" =>  'USER_DIMENSIONS1',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Customer','SA_SCRDLISTINGREP');
			

$reports->addReport(RC_CUSTOMER, 1023, _('Day wise Customer Credit Notes &Listing'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Dimension')." 1" =>  'USER_DIMENSIONS1',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Customer','SA_SCRDLISTINGREP');			
			
			
$reports->addReport(RC_CUSTOMER, 1020, _('Receipt &Register'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Currency Filter') => 'CURRENCY',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SALESPAYMNT');


$reports->addReport(RC_CUSTOMER, 1116, _('Download Sales Quotation Template'),
	array(	_('Reference') => 'TEXT'),
	'Sales','SA_SALESBULKREP');		





$reports->addReport(RC_CUSTOMER,  1057, _('Unallocated &Customer Transactions'),
	array(	_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Customer','SA_UNALLOC_CUST_TRANS_REP');

$reports->addReport(RC_CUSTOMER,  1056, _('&Sales Price Listing Report'),
	array(	_('Inventory Category') => 'CATEGORIES',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SALEPRICLISTREP');

$reports->addReport(RC_CUSTOMER, 1025, _('Upcoming Deliveries Report'),
	array(	_('From Date') => 'DATE',
	        _('To Date')   => 'DATEENDM',
	        _('Customer')  => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SCHEDULE_DELIVERY_REP');

$reports->addReport(RC_CUSTOMER, 1026, _('Delayed Deliveries Report'),
	array(	_('End Date') => 'DATE',
	        _('Customer')  => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_PENDING_DELIVERY_REP');			
			
$reports->addReport(RC_CUSTOMER, 1058, _('Sales Delivery &Listing'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Location') => 'LOCATIONS',
			_('Dimension')." 1" =>  'USER_DIMENSIONS1',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SALES_DELIVERY_LISTING_REP');		


$reports->addReport(RC_CUSTOMER, 1050, _('&Cancel Sales Order Listing'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Stock Location') => 'LOCATIONS',
			_('Back Orders Only') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SALECANCELORDERLISTING');			
		
$reports->addReport(RC_CUSTOMER, 1061, _('Unsigned Invoices &List'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Location') => 'LOCATIONS',
			_('Dimension')." 1" =>  'USER_DIMENSIONS1',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SINLISTINGREP');
			
$reports->addReport(RC_CUSTOMER, 1062, _('Signed Invoice Collection &Register'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Location') => 'LOCATIONS',
			_('Dimension')." 1" =>  'USER_DIMENSIONS1',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SINLISTINGREP');
			
			
$reports->addReport(RC_CUSTOMER, 1063, _('Invoice to Order Price Variation Report'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_INV_ORDER_VARIATION_REP');	

$reports->addReport(RC_CUSTOMER, 1220, _('&Salesman Sales Report (With Profit)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Salesman','SA_SAL_MAN_SALES_REP');
			
$reports->addReport(RC_CUSTOMER, 1101, _('Statement of Accounts with PDC Match'),
	array(  _('End Date') => 'DATE',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'SoA','SA_STAT_ACC_PDC_REP');
			
$reports->addReport(RC_CUSTOMER, 131, _('Statement of Accounts-PDC Showing Down'),
	array(  _('End Date') => 'DATE',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'SoA','SA_CUST_SA_PDC_SHOW_DOWN_REP');	


$reports->addReport(RC_CUSTOMER, 132, _('&Statement of Accounts - Balance Confirmation'),
	array(	_('End Date') => 'DATE',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
		'SoA','SA_CUST_SA_BAL_CONFIRM_REP');

$reports->addReport(RC_CUSTOMER, 133, _('Statement of Accounts - Balance Confirmation for the Purpose of Audit'),
	array(  _('End Date') => 'DATE',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
		'SoA','SA_CUST_SA_PRINT_CONFIRM_REP');


$reports->addReport(RC_CUSTOMER, 1103, _('Debtors Outstanding Report - Summary'),
	array(	_('End Date') => 'DATE',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Legal Group') => 'LEGALGROUP',
            _('Customer Class') => 'CUSTOMERCLASS',
            _('Customer Group Company') => 'CUSTOMERGROUPCMPNY',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
		'Customer','SA_DEB_OUT_REP');			
			
$reports->addReport(RC_CUSTOMER, 1102, _('Debtors Outstanding Report - Detailed'),
	array(  _('End Date') => 'DATE',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Legal Group') => 'LEGALGROUP',
            _('Customer Class') => 'CUSTOMERCLASS',
            _('Customer Group Company') => 'CUSTOMERGROUPCMPNY',
			_('Sales Person') => 'SALESMEN',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
		'Customer','SA_DEB_OUT_REP');	
			
			

$reports->addReport(RC_CUSTOMER, 135, _('SalesManwise Outstanding Register - Summary'),
	array(  _('End Date') => 'DATE',
			_('Sales Person') => 'SALESMEN',
			_('Legal Group') => 'LEGALGROUP',
			_('Customer Class') => 'CUSTOMERCLASS',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Salesman','SA_SALESMAN_OUTSTAND_REP');
			
		
$reports->addReport(RC_CUSTOMER, 137, _('SalesManwise Outstanding Register - Detailed'),
	array(  _('End Date') => 'DATE',
			_('Sales Person') => 'SALESMEN',
			_('Legal Group') => 'LEGALGROUP',
			_('Customer Class') => 'CUSTOMERCLASS',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Salesman','SA_SALESMAN_OUTSTAND_REP');	
			
			

$reports->addReport(RC_CUSTOMER, 134, _('SalesManwise Collection Register - Summary'),
	array(  _('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Sales Person') => 'SALESMEN',
			_('Legal Group') => 'LEGALGROUP',
            _('Customer Class') => 'CUSTOMERCLASS',
			// _('Currency Filter') => 'CURRENCY',
			// _('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Salesman','SA_SALESMAN_COLLECTION_REP');
			
			
		
$reports->addReport(RC_CUSTOMER, 136, _('SalesManwise Collection Register - Details'),
	array(  _('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Sales Person') => 'SALESMEN',
			_('Legal Group') => 'LEGALGROUP',
            _('Customer Class') => 'CUSTOMERCLASS',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Salesman','SA_SALESMAN_COLLECTION_DETAILS_REP');			




$reports->addReport(RC_CUSTOMER, 138, _('Receivables &Ledger'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
		'Payments','SA_SALES_RECEIVE_LEDGER_REP');
			
			
$reports->addReport(RC_CUSTOMER, 139, _('Billwise Matching Details Report'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_BILLWISE_MATCH_DETAILS_REP');


$reports->addReport(RC_CUSTOMER, 140, _('Receipt/Payment SetOff Details'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_RECEIPT_SETOFF_DETAILS_REP');	


$reports->addReport(RC_CUSTOMER, 141, _('SalesManwise Aging Report'),
	array(	_('End Date') => 'DATE',
			_('Sales Person') => 'SALESMEN',
			_('Legal Group') => 'LEGALGROUP',
			_('Customer Class') => 'CUSTOMERCLASS',
			_('Customer Group Company') => 'CUSTOMERGROUPCMPNY', 
			_('Sales Areas') => 'AREAS',			
			_('Currency Filter') => 'CURRENCY',
			_('Summary Only') => 'YES_NO',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Salesman','SA_SALESMAN_AGING_REP');


$reports->addReport(RC_CUSTOMER, 142, _('PDC Register'),
	array(	_('Bank Accounts') => 'BANK_ACCOUNTS_NO_FILTER',
			_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Cheques Status') => 'PDC_CHEQUE_TYPES',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
		'SoA','SA_PDC_REG_REP');	
		
$reports->addReport(RC_CUSTOMER, 158, _('PDC Recall Alerts'),
	array(	_('End Date') => 'DATE',
			_('Bank Accounts') => 'BANK_ACCOUNTS_NO_FILTER',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
		'SoA','SA_PDC_REG_REP');			


$reports->addReport(RC_CUSTOMER, 143, _('Sales Quotation Register'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Inventory Category') => 'CATEGORIES',
			_('Sub Category') => 'ONCHANGESTOCKSUBCATEGORY_P3',
			_('Sales Person') => 'SALESMEN',
			_('Summary Only') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SALES_QUOTE_REG_REP');


$reports->addReport(RC_CUSTOMER, 144, _('Retail Sales and Credit Sales Register'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Summary Only') => 'YES_NO',
			_('Types') => 'RETAIL_CREDIT_SALES',
			_('FOC Sales Register') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_RETAIL_CREDIT_SALES_REG_REP');


$reports->addReport(RC_CUSTOMER, 145, _('Sales Return Register'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Summary Only') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_SALES_RETURN_REG_REP');



$reports->addReport(RC_CUSTOMER, 146, _('General Sales Summary Register (Salesmanwise/Customerwise)'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Summary Only') => 'YES_NO',
			_('Types') => 'RETAIL_CREDIT_SALES',
			_('FOC Sales Register') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Sales','SA_GEN_SALES_SUMMARY_REP');


$reports->addReport(RC_CUSTOMER, 147, _('DO Register (Detailed & Summary)'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Location') => 'LOCATIONS',
			_('Summary Only') => 'YES_NO',
			_('FOC Sales Register') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
		'DO','SA_SALES_DO_REG_REP');	


$reports->addReport(RC_CUSTOMER, 148, _('Customer Wise DO Register (Detailed & Summary)'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Location') => 'LOCATIONS',
			_('Summary Only') => 'YES_NO',
			_('FOC Sales Register') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'DO','SA_SALES_DO_REG_REP');	


$reports->addReport(RC_CUSTOMER, 149, _('SalesManwise DO Register (Detailed & Summary)'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Sales Person') => 'SALESMEN',
			_('Location') => 'LOCATIONS',
			_('Summary Only') => 'YES_NO',
			_('FOC Sales Register') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'DO','SA_SALES_DO_REG_REP');
			
			

$reports->addReport(RC_CUSTOMER, 150, _('SalesManwise DO Summary (WP)'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Sales Person') => 'SALESMEN',
			_('Location') => 'LOCATIONS',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'DO','SA_SALES_DO_REG_REP');	


$reports->addReport(RC_CUSTOMER, 151, _('SalesManwise Monthly Sales Summary'),
	array(	_('Year') => 'TRANS_YEARS',
			_('Sales Person') => 'SALESMEN',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Salesman','SA_SALESMANWISE_SALES_REP');


$reports->addReport(RC_CUSTOMER, 152, _('SalesManwise Customerwise Sales Summary'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Salesman','SA_SALESMANWISE_SALES_REP');	


$reports->addReport(RC_CUSTOMER, 153, _('SalesManwise Sales Register (Detailed & Summary)'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Sales Person') => 'SALESMEN',
			_('Summary Only') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Salesman','SA_SALESMANWISE_SALES_REP');	


$reports->addReport(RC_CUSTOMER, 154, _('SalesManwise ItemWise Sales Register - (Detailed & Summary)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Inventory Category') => 'CATEGORIES',
			_('Inventory sub Category') => 'ONCHANGESTOCKSUBCATEGORY_P3',
			_('Sales Person') => 'SALESMEN', 
			_('Location') => 'LOCATIONS',
			_('Summary Only') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Salesman','SA_SALESMANWISE_SALES_REP');	


$reports->addReport(RC_CUSTOMER, 155, _('SalesManwise Cash Receipt'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Sales Person') => 'SALESMEN', 
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),
			'Salesman','SA_SALESMANWISE_SALES_REP');			


$reports->addReportClass(_('Supplier'), RC_SUPPLIER);
$reports->addReport(RC_SUPPLIER, 201, _('Supplier &Balances'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Type') => 'PUR_REG_TYPE',
			_('Show Balance') => 'YES_NO',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPPLIERANALYTIC');
			
$reports->addReport(RC_SUPPLIER, 2010, _('Supplier &Outstanding - Billwise'),
	array(	_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Type') => 'PUR_REG_TYPE',
			_('Show Balance') => 'YES_NO',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPPLIERANALYTIC');
			
$reports->addReport(RC_SUPPLIER, 202, _('&Aged Supplier Analyses'),
	array(	_('End Date') => 'DATE',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Type') => 'PUR_REG_TYPE',
			_('Currency Filter') => 'CURRENCY',
			_('Show Also Allocated') => 'YES_NO',
			_('Summary Only') => 'YES_NO',
			_('Suppress Zeros') => 'YES_NO',
			_('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPPLIERANALYTIC');
$reports->addReport(RC_SUPPLIER, 206, _('Supplier &Trial Balances'),
    array(  _('Start Date') => 'DATEBEGIN',
            _('End Date') => 'DATEENDM',
            _('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Type') => 'PUR_REG_TYPE',
            _('Currency Filter') => 'CURRENCY',
            _('Suppress Zeros') => 'YES_NO',
            _('Comments') => 'TEXTBOX',
            _('Orientation') => 'ORIENTATION',
            _('Destination') => 'DESTINATION'),'','SA_SUPPLIERANALYTIC');
$reports->addReport(RC_SUPPLIER, 203, _('&Payment Report'),
	array(	_('End Date') => 'DATE',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Type') => 'PUR_REG_TYPE',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPPPAYMREP');
$reports->addReport(RC_SUPPLIER, 204, _('Outstanding &GRNs Report'),
	array(	_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPPLIERANALYTIC');
$reports->addReport(RC_SUPPLIER, 205, _('Supplier &Detail Listing'),
	array(	_('Activity Since') => 'DATEBEGIN',
			_('Activity Greater Than') => 'TEXT',
			_('Activity Less Than') => 'TEXT',
			_('Type') => 'PUR_REG_TYPE',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPPLIERANALYTIC');
$reports->addReport(RC_SUPPLIER, 209, _('Print Purchase &Orders'),
	array(	_('From') => 'PO',
			_('To') => 'PO',
			_('Currency Filter') => 'CURRENCY',
			_('Email Suppliers') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION'),'','SA_SUPPTRANSVIEW');
$reports->addReport(RC_SUPPLIER, 210, _('Print Remittances'),
	array(	_('From') => 'REMITTANCE',
			_('To') => 'REMITTANCE',
			_('Currency Filter') => 'CURRENCY',
			_('Email Suppliers') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION'),'','SA_SUPPTRANSVIEW');
			

$reports->addReport(RC_SUPPLIER, 1112, _('Pending PO &Report'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Type') => 'PUR_REG_TYPE',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPPLIERANALYTIC');

$reports->addReport(RC_SUPPLIER, 1114, _('Purchase Register Report'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Type') => 'PUR_REG_TYPE',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_PURCHASE_REGISTER_REP');
			
			
$reports->addReport(RC_SUPPLIER, 1113, _('Supplier Invoice &Listing'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Type') => 'PUR_REG_TYPE',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPPLIERANALYTIC');

$reports->addReport(RC_SUPPLIER, 1115, _('Unallocated Supplier &Transactions'),
	array(	_('Supplier') => 'SUPPLIERS_NO_FILTER',
	        _('Type') => 'PUR_REG_TYPE',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_UNALLOC_SUPP_TRANS_REP');
			
			
$reports->addReport(RC_SUPPLIER, 211, _('Statement of Accounts of Creditors'),
	array(  _('End Date') => 'DATE',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Type') => 'PUR_REG_TYPE',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPP_STATEMENT_ACC_REP');



$reports->addReport(RC_SUPPLIER, 212, _('Creditors Outstanding Report'),
	array(  _('End Date') => 'DATE',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Type') => 'PUR_REG_TYPE',
			_('Currency Filter') => 'CURRENCY',
			_('Summary Only') => 'YES_NO',
			_('Suppress Zeros') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPP_OUTSTANDING_REP');			
			
$reports->addReport(RC_SUPPLIER, 213, _('Payables &Ledger'),
	array(	_('Start Date')      => 'DATEBEGIN',
			_('End Date')        => 'DATEENDM',
			_('Supplier')        => 'SUPPLIERS_NO_FILTER',
			_('Type')            => 'PUR_REG_TYPE',
			_('Currency Filter') => 'CURRENCY',
			_('Suppress Zeros')  => 'YES_NO',
			_('Comments')        => 'TEXTBOX',
			_('Orientation')     => 'ORIENTATION',
			_('Destination')     => 'DESTINATION'),'','SA_SUPP_PAYABLES_LEDGER_REP');			
			
//$reports->addReport(RC_SUPPLIER, 2111, _('Download Purchase Order Template'),
	//array(	_('Reference') => 'TEXT'));				
			

$reports->addReportClass(_('Inventory'), RC_INVENTORY);
$reports->addReport(RC_INVENTORY,  301, _('Inventory &Valuation Report'),
	array(	_('End Date') => 'DATE',
			_('Inventory Category') => 'CATEGORIES',
			_('Inventory sub Category') => 'ONCHANGESTOCKSUBCATEGORY2',
			_('Location') => 'LOCATIONS',
			_('Summary Only') => 'YES_NO',
			//_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_ITEMSVALREP');
			
$reports->addReport(RC_INVENTORY,  302, _('Inventory &Planning Report'),
	array(	_('Inventory Category') => 'CATEGORIES',
			_('Location') => 'LOCATIONS',
			_('Comments') => 'TEXTBOX',			
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_ITEMSANALYTIC');
$reports->addReport(RC_INVENTORY, 303, _('Stock &Check Sheets'),
	array(	_('Inventory Category') => 'CATEGORIES',
			_('Location') => 'LOCATIONS',
			//_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Show Pictures') => 'YES_NO',
			_('Inventory Column') => 'YES_NO',
			_('Show Only Shortages') => 'YES_NO',
			_('Suppress Zeros') => 'YES_NO',
			_('Item Like') => 'TEXT',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_ITEMSVALREP');
$reports->addReport(RC_INVENTORY, 304, _('Item Wise  &Profitability  Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Inventory sub Category') => 'ONCHANGESTOCKSUBCATEGORY3',
			_('Location') => 'LOCATIONS',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			//_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Show Service Items') => 'YES_NO',
			_('Summary') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SALESANALYTIC');
$reports->addReport(RC_INVENTORY, 305, _('&GRN Valuation Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			//_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPPLIERANALYTIC');
$reports->addReport(RC_INVENTORY, 306, _('Inventory Purchasing Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Location') => 'LOCATIONS',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Items') => 'ITEMS_P',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPPLIERANALYTIC');
$reports->addReport(RC_INVENTORY, 3060, _('Service Items Purchasing Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPPLIERANALYTIC');			
$reports->addReport(RC_INVENTORY, 307, _('Inventory &Movement Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Location') => 'LOCATIONS',
			//_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_ITEMSVALREP');				
$reports->addReport(RC_INVENTORY, 308, _('Costed Inventory Movement Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Location') => 'LOCATIONS',
			//_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_ITEMSVALREP');	


			
$reports->addReport(RC_INVENTORY, 309,_('Item &Sales Summary Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			//_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SALESANALYTIC');				
$reports->addReport(RC_INVENTORY, 310, _('Inventory Purchasing Transaction Based'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Location') => 'LOCATIONS',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Items') => 'ITEMS_P',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_SUPPLIERANALYTIC');
			
//Inventory Customized Reports -- 20-12-2022 //Rajesh			
$reports->addReport(RC_INVENTORY, 3070, _('Inventory &Adjustment Report'),
	array(	//_('Supplier') => 'SUPPLIERS_NO_FILTER',
	        _('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Sub Category') => 'ONCHANGESTOCKSUBCATEGORY3',
			_('Location') => 'LOCATIONS',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_INVENTORY_ADJUSTMENT_REPORT');	

$reports->addReport(RC_INVENTORY, 318,_('Item wise Pending &Purchase Order Report'),
	array(	_('Supplier') => 'SUPPLIERS_NO_FILTER',
	        _('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Sub Category') => 'ONCHANGESTOCKSUBCATEGORY_P3',
			_('Currency Type') => 'SUPP_CURRENCY_TYPE',
			_('Items') => 'ITEMS_PR',
			_('Comments') => 'TEXTBOX',
			_('Type') => 'REPORTTYPE',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_INVENTORY_ITEM_WISE_PENDING_PURCHASE_ORDER_REPORT');	

$reports->addReport(RC_INVENTORY, 319,_('Item wise Pending &Sales Order Report'),
	array(	_('Supplier') => 'SUPPLIERS_NO_FILTER',
	        _('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Sub Category') => 'ONCHANGESTOCKSUBCATEGORY_P3',
			_('Customer') => 'CUSTOMERS_NO_FILTER', 
			_('Sales Person') => 'SALESMEN', 
			_('Currency Type') => 'SUPP_CURRENCY_TYPE',
			_('Location') => 'LOCATIONS',
			_('Items') => 'ITEMS_PR',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_INVENTORY_ITEM_WISE_PENDING_SALES_ORDER_REPORT');

$reports->addReport(RC_INVENTORY, 311, _('Inventory Aging Report'),
			array(_('End Date') => 'DATEBEGINM',
			      _('Supplier') => 'SUPPLIERS_NO_FILTER',
			      _('Inventory Category') => 'CATEGORIES',
			      _('Sub Category') => 'ONCHANGESTOCKSUBCATEGORY3',
			      _('Location') => 'LOCATIONS',
			      _('Comments') => 'TEXTBOX',
			      _('Orientation') => 'ORIENTATION',
			      _('Destination') => 'DESTINATION'),'','SA_INVENTORY_AGING_REPORT');	

 $reports->addReport(RC_INVENTORY, 317, _('Stock Transfer Report'),
	         array(	_('Start Date') => 'DATEBEGINM',
			        _('End Date') => 'DATEENDM',
                    _('Location') => 'LOCATIONS',				
                    _('Orientation') => 'ORIENTATION',
			        _('Destination') => 'DESTINATION'),'','SA_INVENTORY_STOCK_TRANSFER_REPORT');					  

$reports->addReport(RC_INVENTORY, 331, _('Category Wise Monthly Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
	        //_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Inventory Category') => 'CATEGORIES',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_CATEGORY_MONTHLY_REP');
	
$reports->addReport(RC_INVENTORY, 334, _('Order Qty Based On Sales With All Details'),
	array(	_('Date') => 'DATE',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Inventory Category') => 'CATEGORIES',
			_('Sub Category') => 'ONCHANGESTOCKSUBCATEGORY3',
			_('Location') => 'LOCATIONS',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'),'','SA_SALES_ORDER_QTY_REPORT');

$reports->addReport(RC_INVENTORY, 335, _('Single Stock Movement Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Sub Category') => 'ONCHANGESTOCKSUBCATEGORY3',
			_('Items') => 'CATEGORY_ITEMS',
			_('Location') => 'LOCATIONS',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_INVENTORY_STOCK_MOVEMENT_REPORT');

$reports->addReport(RC_INVENTORY, 3081, _('Stock Ledger Summary - 1'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Inventory sub Category') => 'ONCHANGESTOCKSUBCATEGORY3',
			_('Location') => 'LOCATIONS',
			_('Items') => 'ITEMS_P',
			//_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_ITEMSVALREP');			


$reports->addReport(RC_INVENTORY, 3080, _('Stock Ledger Summary - 2'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Inventory Category') => 'CATEGORIES',
			_('Inventory sub Category') => 'ONCHANGESTOCKSUBCATEGORY3',
			_('Location') => 'LOCATIONS',
			_('Items') => 'ITEMS_P',
			//_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_ITEMSVALREP');	

$reports->addReport(RC_INVENTORY,  3010, _('Stock &Valuation Report'),
	array(	_('End Date') => 'DATE',
			_('Inventory Category') => 'CATEGORIES',
			_('Inventory sub Category') => 'ONCHANGESTOCKSUBCATEGORY2',
			_('Location') => 'LOCATIONS',
			_('Items') => 'ITEMS_P',
			//_('Summary Only') => 'YES_NO',
			//_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_ITEMSVALREP');			
			


if (get_company_pref('use_manufacturing'))
{
	$reports->addReportClass(_('Manufacturing'), RC_MANUFACTURE);
	$reports->addReport(RC_MANUFACTURE, 401, _('&Bill of Material Listing'),
		array(	_('From product') => 'ITEMS',
				_('To product') => 'ITEMS',
				_('Comments') => 'TEXTBOX',
				_('Orientation') => 'ORIENTATION',
				_('Destination') => 'DESTINATION'));
	$reports->addReport(RC_MANUFACTURE, 402, _('Work Order &Listing'),
		array(	_('Items') => 'ITEMS_ALL',
				_('Location') => 'LOCATIONS',
				_('Outstanding Only') => 'YES_NO',
				_('Show GL Rows') => 'YES_NO',
				_('Comments') => 'TEXTBOX',
				_('Orientation') => 'ORIENTATION',
				_('Destination') => 'DESTINATION'));
	$reports->addReport(RC_MANUFACTURE, 409, _('Print &Work Orders'),
		array(	_('From') => 'WORKORDER',
				_('To') => 'WORKORDER',
				_('Email Locations') => 'YES_NO',
				_('Comments') => 'TEXTBOX',
				_('Orientation') => 'ORIENTATION'));
}
if (get_company_pref('use_fixed_assets'))
{
	$reports->addReportClass(_('Fixed Assets'), RC_FIXEDASSETS);
	$reports->addReport(RC_FIXEDASSETS, 451, _('&Fixed Assets Valuation'),
		array(	_('End Date') => 'DATE',
				_('Fixed Assets Class') => 'FCLASS',
				_('Fixed Assets Location') => 'FLOCATIONS',
				_('Summary Only') => 'YES_NO',
				_('Comments') => 'TEXTBOX',
				_('Orientation') => 'ORIENTATION',
				_('Destination') => 'DESTINATION'),'','SA_ASSETSANALYTIC');
}				
$reports->addReportClass(_('Dimensions'), RC_DIMENSIONS);
if ($dim > 0)
{
	$reports->addReport(RC_DIMENSIONS, 501, _('Dimension &Summary'),
	array(	_('From Dimension') => 'DIMENSION',
			_('To Dimension') => 'DIMENSION',
			_('Show Balance') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_DIMENSIONREP');
}
$reports->addReportClass(_('Banking'), RC_BANKING);
	$reports->addReport(RC_BANKING,  601, _('Bank &Statement'),
	array(	_('Bank Accounts') => 'BANK_ACCOUNTS_NO_FILTER',
			_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Zero values') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_BANKREP');
	$reports->addReport(RC_BANKING,  603, _('Bank &Statement - Day wise'),
	array(	_('Bank Accounts') => 'BANK_ACCOUNTS_NO_FILTER',
			_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Zero values') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_BANKREP');		
	$reports->addReport(RC_BANKING,  602, _('Bank Statement w/ &Reconcile'),
	array(	_('Bank Accounts') => 'BANK_ACCOUNTS',
			_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'),'','SA_BANKREP');

$reports->addReportClass(_('General Ledger'), RC_GL);
$reports->addReport(RC_GL, 701, _('Chart of &Accounts'),
	array(	_('Show Balances') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLREP');
$reports->addReport(RC_GL, 702, _('List of &Journal Entries'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Type') => 'SYS_TYPES',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLANALYTIC');

if ($dim == 2)
{
	$reports->addReport(RC_GL, 704, _('GL Account &Transactions'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('From Account') => 'GL_ACCOUNTS',
			_('To Account') => 'GL_ACCOUNTS',
			_('Dimension')." 1" =>  'DIMENSIONS1',
			_('Dimension')." 2" =>  'DIMENSIONS2',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLREP');
	$reports->addReport(RC_GL, 705, _('Annual &Expense Breakdown'),
	array(	_('Year') => 'TRANS_YEARS',
			_('Dimension')." 1" =>  'DIMENSIONS1',
			_('Dimension')." 2" =>  'DIMENSIONS2',
			_('Account Tags') =>  'ACCOUNTTAGS',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Amounts in thousands') => 'YES_NO',
			_('Destination') => 'DESTINATION'),'','SA_GLANALYTIC');
	$reports->addReport(RC_GL, 706, _('&Balance Sheet'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Dimension')." 1" => 'DIMENSIONS1',
			_('Dimension')." 2" => 'DIMENSIONS2',
			_('Account Tags') =>  'ACCOUNTTAGS',
			_('Decimal values') => 'YES_NO',
			_('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLANALYTIC');
	$reports->addReport(RC_GL, 707, _('&Profit and Loss Statement'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Compare to') => 'COMPARE',
			_('Dimension')." 1" =>  'DIMENSIONS1',
			_('Dimension')." 2" =>  'DIMENSIONS2',
			_('Account Tags') =>  'ACCOUNTTAGS',
			_('Decimal values') => 'YES_NO',
			_('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLANALYTIC');
	$reports->addReport(RC_GL, 708, _('Trial &Balance'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Zero values') => 'YES_NO',
			_('Only balances') => 'YES_NO',
			_('Dimension')." 1" =>  'DIMENSIONS1',
			_('Dimension')." 2" =>  'DIMENSIONS2',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'));
}
elseif ($dim == 1)
{
	$reports->addReport(RC_GL, 704, _('GL Account &Transactions'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('From Account') => 'GL_ACCOUNTS',
			_('To Account') => 'GL_ACCOUNTS',
			_('Dimension') =>  'DIMENSIONS1',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLREP');
	$reports->addReport(RC_GL, 705, _('Annual &Expense Breakdown'),
	array(	_('Year') => 'TRANS_YEARS',
			_('Dimension') =>  'DIMENSIONS1',
			_('Account Tags') =>  'ACCOUNTTAGS',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Amounts in thousands') => 'YES_NO',
			_('Destination') => 'DESTINATION'),'','SA_GLANALYTIC');
	$reports->addReport(RC_GL, 706, _('&Balance Sheet'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Dimension') => 'DIMENSIONS1',
			_('Account Tags') =>  'ACCOUNTTAGS',
			_('Decimal values') => 'YES_NO',
			_('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLANALYTIC');
	$reports->addReport(RC_GL, 707, _('&Profit and Loss Statement'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Compare to') => 'COMPARE',
			_('Dimension') => 'DIMENSIONS1',
			_('Account Tags') =>  'ACCOUNTTAGS',
			_('Decimal values') => 'YES_NO',
			_('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLANALYTIC');
	$reports->addReport(RC_GL, 708, _('Trial &Balance'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Zero values') => 'YES_NO',
			_('Only balances') => 'YES_NO',
			_('Dimension') => 'DIMENSIONS1',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLANALYTIC');
}
else
{
	$reports->addReport(RC_GL, 704, _('GL Account &Transactions'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('From Account') => 'GL_ACCOUNTS',
			_('To Account') => 'GL_ACCOUNTS',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 705, _('Annual &Expense Breakdown'),
	array(	_('Year') => 'TRANS_YEARS',
			_('Account Tags') =>  'ACCOUNTTAGS',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Amounts in thousands') => 'YES_NO',
			_('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 706, _('&Balance Sheet'),
	array(	_('Start Date') => 'DATEBEGIN',
			_('End Date') => 'DATEENDM',
			_('Account Tags') =>  'ACCOUNTTAGS',
			_('Decimal values') => 'YES_NO',
			_('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 707, _('&Profit and Loss Statement'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Compare to') => 'COMPARE',
			_('Account Tags') =>  'ACCOUNTTAGS',
			_('Decimal values') => 'YES_NO',
			_('Graphics') => 'GRAPHIC',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'));
	$reports->addReport(RC_GL, 708, _('Trial &Balance'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Zero values') => 'YES_NO',
			_('Only balances') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLANALYTIC');
}
$reports->addReport(RC_GL, 709, _('Ta&x Report'),
	array(	_('Start Date') => 'DATEBEGINTAX',
			_('End Date') => 'DATEENDTAX',
			_('Summary Only') => 'YES_NO',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_TAXREP');
$reports->addReport(RC_GL, 710, _('Audit Trail'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Type') => 'SYS_TYPES_ALL',
			_('User') => 'USERS',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLANALYTIC');


$reports->addReport(RC_GL, 711, _('Series of Documents'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
	        _('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLANALYTIC');
			
			
$reports->addReport(RC_GL, 712, _('GL Account &Transactions(New Format)'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('From Account') => 'GL_ACCOUNTS',
			_('To Account') => 'GL_ACCOUNTS',
			_('Dimension') =>  'DIMENSIONS1',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLREP');
			
$reports->addReport(RC_GL, 713, _('Petty Cash Payment Report'),
	array(	_('Start Date') => 'DATEBEGINM',
			_('End Date') => 'DATEENDM',
			_('Bank Accounts') => 'CASH_BANK_ACCOUNTS',
			_('Dimension') =>  'DIMENSIONS1',
			_('Comments') => 'TEXTBOX',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_GLREP');		
		
			

$reports->addReportClass(_('Complaints'), RC_COMPLAINTS);	
$reports->addReport(RC_COMPLAINTS,  910, _('Complaint Summary Report'),
	array(	_('From Date') => 'DATEBEGINM',
			_('To Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Mobile Number') => 'TEXT',
			_('Status') => 'COMPLAINT_STATUS',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_COMPLAINT_INQUIRY_REP');	

$reports->addReport(RC_COMPLAINTS,  911, _('Complaint History Report'),
	array(	_('From Date') => 'DATEBEGINM',
			_('To Date') => 'DATEENDM',
			_('Customer') => 'CUSTOMERS_NO_FILTER',
			_('Mobile Number') => 'TEXT',
			_('Status') => 'COMPLAINT_STATUS',
			_('Reference') => 'TEXT',
			_('Orientation') => 'ORIENTATION',
			_('Destination') => 'DESTINATION'),'','SA_COMPLAINT_HISTORY_REP');	

add_custom_reports($reports);

echo $reports->getDisplay();

end_page();
