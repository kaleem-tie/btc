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
//----------------------------------------------------------------------------------
//	FrontAccounting global system names. They have to be stored separate from
//	sys types defines to enable inclusion after every language change
//	(which happens for multilanguage bulk reports)
//
global $systypes_array, $bank_account_types, $bank_transfer_types, 
	$payment_person_types, $wo_types_array, $wo_cost_types, $class_types,
	$quick_actions, $quick_entry_types, $stock_types, $tag_types, $bank_owner,
	$material_indent_request_type,$stock_transfer_type,$mode_payment_types,
	$invoice_payment_type,$vat_registered,$business_types,$vat_status,$reverse_charge_status, 
    $countries,$complaint_against_types,$delivery_times,$invoice_types, $invoice_collection,
	$inv_adjustment_types, $purchase_types;

$systypes_array = array (
	ST_JOURNAL => _("Journal Entry"),
	ST_BANKPAYMENT => _("Bank Payment"),
	ST_BANKDEPOSIT => _("Bank Deposit"),
	ST_BANKTRANSFER => _("Funds Transfer"),
	ST_SALESINVOICE => _("Sales Invoice"),
	ST_CUSTCREDIT => _("Customer Credit Note"),
	ST_CUSTPAYMENT => _("Customer Payment"),
	ST_CUSTDELIVERY => _("Delivery Note"),
	ST_LOCTRANSFER => _("Location Transfer"),
	ST_INVADJUST => _("Inventory Adjustment"),
	ST_PURCHORDER => _("Purchase Order"),
	ST_GEN_PURCHORDER => _("General Purchase Order"),
	ST_SUPPINVOICE => _("Supplier Invoice"),
	ST_SUPPCREDIT => _("Supplier Credit Note"),
	ST_SUPPAYMENT => _("Supplier Payment"),
	//ST_SUPPRECEIVE => _("Purchase Order Delivery"),
	ST_SUPPRECEIVE => _("GRN"),
	ST_INVSUPPRECEIVE => _("Inventory GRN"),
	//ST_WORKORDER => _("Work Order"),
	//ST_MANUISSUE => _("Work Order Issue"),
	//ST_MANURECEIVE => _("Work Order Production"),
	ST_SALESORDER => _("Sales Order"),
	ST_SALESQUOTE => _("Sales Quotation"),
	ST_COSTUPDATE => _("Cost Update"),
	ST_DIMENSION => _("Dimension"),
	ST_CUSTOMER => _("Customer"),
	ST_SUPPLIER => _("Supplier"),
	ST_MATERIAL_INDENT => _("Material Indent"),
	ST_SALESENQ => _("Sales Enquiry"),
    ST_INSSALESENQ => _("Manual Sales Enquiry"),
    ST_INSSALESQUOTE => _("Manual Sales Quotation"),
	ST_INSSALESORDER => _("Manual Sales Order"),
    ST_PURCHENQ => _("Purchase Enquiry"),
    ST_PURCHQUOTE => _("Purchase Quotation"),
    ST_COMPLAINT_REGISTER => _("Complaint Register"),
	ST_CUSTPDC => _("Customer PDC"),
	ST_SUPPPDC => _("Supplier PDC"),
	 // ST_CUSTRTPDC => _("Customer PDC Return"),
	// ST_CUSTBNPDC => _("Customer PDC Bounce"),
	);

$fa_systypes_array = array (
	ST_INVADJUST => _("Fixed Assets Disposal"),
	ST_COSTUPDATE => _("Fixed Assets Revaluation"),
	);

$type_shortcuts = array(
	ST_JOURNAL => _("GJ"), // general journal
	ST_BANKPAYMENT => _("BP"),
	ST_BANKDEPOSIT => _("BD"),
	ST_BANKTRANSFER => _("BT"),
	ST_SALESINVOICE => _("SI"),
	ST_CUSTCREDIT => _("CN"),
	ST_CUSTPAYMENT => _("CP"),
	ST_CUSTDELIVERY => _("DN"),
	ST_LOCTRANSFER => _("IT"), // inventory transfer
	ST_INVADJUST => _("IA"),
	ST_PURCHORDER => _("PO"),
	ST_GEN_PURCHORDER => _("GPO"),
	ST_SUPPINVOICE => _("PI"), // purchase invoice
	ST_SUPPCREDIT => _("PC"),
	ST_SUPPAYMENT => _("SP"),
	ST_SUPPRECEIVE => _("GRN"),
	ST_WORKORDER => _("WO"),
	ST_MANUISSUE => _("WI"),
	ST_MANURECEIVE => _("WP"),
	ST_SALESORDER => _("SO"),
	ST_SALESQUOTE => _("SQ"),
	ST_COSTUPDATE => _("CU"),
	ST_DIMENSION => _("Dim"),
	ST_SALESENQ => _("SE"),
	ST_MATERIAL_INDENT => _("MI") //Material Indent
);


//----------------------------------------------------------------------------------
//		Bank transaction types
//
$bank_account_types = array (
	BT_TRANSFER => _("Savings Account"),
		_("Chequing Account"),
		_("Credit Account"),
		_("Cash Account")
	);

$bank_transfer_types = array(
	BT_TRANSFER => _("Transfer"),
			_("Cheque"),
			_("Credit"),
			_("Cash")
	);

//----------------------------------------------------------------------------------
//	Payment types
//

$payment_person_types = array (
	PT_MISC => _("Miscellaneous"),
				_("Work Order"),
				_("Customer"),
				_("Supplier"),
				//_("Quick Entry")
	);

//----------------------------------------------------------------------------------
//	Manufacturing types
//
$wo_types_array = array (
	WO_ASSEMBLY => _("Assemble"),
	WO_UNASSEMBLY => _("Unassemble"),
	WO_ADVANCED => _("Advanced Manufacture")
	);

$wo_cost_types = array(
	WO_LABOUR => _("Labour Cost"),
	WO_OVERHEAD => _("Overhead Cost"),
	WO_MATERIALS => _("Materials")
);

//----------------------------------------------------------------------------------
//	GL account classes
//
$class_types = array(
	CL_ASSETS => _("Assets"),
	CL_LIABILITIES => _("Liabilities"),
	CL_EQUITY => _("Equity"),
	CL_INCOME => _("Income"),
	CL_COGS => _("Cost of Goods Sold"),
	CL_EXPENSE => _("Expense"),
);

//----------------------------------------------------------------------------------
//	Quick entry types
//
$quick_actions = array(
	'=' => _('Remainder'), // post current base amount to GL account
	'a' => _('Amount'), // post amount to GL account
	'a+' => _('Amount, increase base'), // post amount to GL account and increase base
	'a-' => _('Amount, reduce base'), // post amount to GL account and reduce base
	'%' => _('% amount of base'),	// store acc*amount% to GL account
	'%+' => _('% amount of base, increase base'),	// ditto & increase base amount
	'%-' => _('% amount of base, reduce base'),	// ditto & reduce base amount
	'T' => _('Taxes added'), // post taxes calculated on base amount
	'T+' => _('Taxes added, increase base'), // ditto & increase base amount
	'T-' => _('Taxes added, reduce base'), // ditto & reduce base amount
	't' => _('Taxes included'), // post taxes calculated on base amount
	't+' => _('Taxes included, increase base'), // ditto & increase base amount
	't-' => _('Taxes included, reduce base') // ditto & reduce base amount
);

$quick_entry_types = array(
	QE_DEPOSIT => _("Bank Deposit"),
	QE_PAYMENT => _("Bank Payment"),
	QE_JOURNAL => _("Journal Entry"),
	QE_SUPPINV => _("Supplier Invoice/Credit")
);

//----------------------------------------------------------------------------------
// depreciation methods
$depreciation_methods = array(
  'D' => _("Declining balance"),
  'S' => _("Straight line"),
  'N' => _("Sum of the Year Digits"),
  'O' => _("One-time"),
);

//----------------------------------------------------------------------------------
// Types of stock items
$stock_types = array(
	'M' => _("Manufactured"),
	'B' => _("Purchased"),
	'D' => _("Service")
);

//----------------------------------------------------------------------------------

$tag_types = array (
	TAG_ACCOUNT   => _("Account"),
	TAG_DIMENSION => _("Dimension")
);

//----------------------------------------------------------------------------------
// crm contacts categories
$sys_crm_cats = array (
	'cust_branch' => _("Customer branch"),
	'supplier' => _("Supplier"),
	'shipper' => _("Shipper"),
	'company' => _("Company internal")
);
//----------------------------------------------------------------------------------

$pterm_types = array(
	PTT_PRE => _("Prepayment"),
	PTT_CASH => _("Cash"),
	PTT_DAYS => _("After No. of Days"),
	PTT_FOLLOWING => _("Day In Following Month")
);

$tax_algorithms = array( 
	TCA_LINES => _("Sum per line taxes"), 
	TCA_TOTALS => _("Taxes from totals")
);
//----------------------------------------------------------------------------------

$bank_owner_types = array(
	BO_UNKNOWN => _("Unknown"),
	BO_COMPANY => _("Company"),
	BO_CUSTBRANCH => _("Customer"),
	BO_SUPPLIER => _("Supplier")
);

// This month array is for use with the last 3 dateformats. 
$tmonths = array("", _("Jan"),_("Feb"),_("Mar"),_("Apr"),_("May"),_("Jun"),_("Jul"),_("Aug"),_("Sep"),_("Oct"),_("Nov"),_("Dec"));

//
//	FA supported db backend language settings.
//
$supported_collations = array(
  'utf8_xx' => _('Unicode (multilanguage)'),
  'utf8_is' => _('Icelandic'),
  'utf8_lv' => _('Latvian'),
  'utf8_ro' => _('Romanian'),
  'utf8_sl' => _('Slovenian'),
  'utf8_pl' => _('Polish'),
  'utf8_et' => _('Estonian'),
  'utf8_es' => _('Spanish'), // or 'spanish2',
  'utf8_sw' => _('Swedish'),
  'utf8_tr' => _('Turkish'),
  'utf8_cs' => _('Czech'),
  'utf8_da' => _('Danish'),
  'utf8_lt' => _('Lithuanian'),
  'utf8_sk' => _('Slovak'),
  'utf8_sp' => _('Spanish (alternative)'),
  'utf8_fa' => _('Persian'),
  'utf8_hu' => _('Hungarian'),
  'utf8_fr' => _('French'),
  'utf8_it' => _('Italian'),
);



$material_indent_request_type=array(
//0 => _("Select a Indent Request Type"),
1 => _("Direct")
//2 => _("Against Job Card")
);

$stock_transfer_type=array(
1 => _("Direct"),
2 => _("Against Indent")
);

$mode_payment_types = array(
	"card" => _("Visa Card"),
	"cash" => _("Cash"),
	"cheque" => _("Cheque"), 
	//"dd" => _("DD"),
	//"rtgs" => _("RTGS"),
	"ot" => _("Online Transfer")
	//"neft" => _("NEFT"),
	);
$invoice_payment_type = array(
    1   => _("Cash"),
	2   => _("Credit")
); 	

$vat_registered = array(1=>_("Yes"),2=>_("No"));

$business_types = array(1=>_("N/A"),2=>_("Indirect Exports"));

$vat_status = array(1=>_('Valid'),2=>_('Invalid'));

$reverse_charge_status = array(
'1' => _("Yes")
);


$complaint_against_types = array(
1=>_('Order'),
2=>_('Invoice'),
3=>_('Receipt'),
4=>_('Others'));

$countries = array(
    0 => _("Select Country"),
	1 => _("AFRICA"),
	2 => _("BANGLADESH"),
	3 => _("BELGIUM"),
	4 => _("BRASIL"),
	5 => _("CANADA"),
	6 => _("CHINA"),
	7 => _("CYPRUS"),
	8 => _("CZECH REPUBLIC"),
	9 => _("EGYPT"),
	10 => _("ENGLAND"),
	11 => _("FRANCE"),
	12 => _("GERMANY"),
	13 => _("HONGKONG"),
	14=> _("HUNGARY"),
	15 => _("INDIA"),
	16 => _("IRAN"),
	17 => _("ITALY"),
	18 => _("JAPAN"),
	19 => _("JORDAN"),
	20 => _("KENYA"),
	21 => _("KUWAIT"),
	22 => _("LEBANON"),
	46 => _("LITHUANIA"),
	23 => _("MALAYSIA"),
	24 => _("MEXICO"),
	36 => _("OMAN"),
	25 => _("PAKISTAN"),
	26 => _("PILIPINES"),
	45 => _("POLAND"),
	27 => _("PORTUGAL"),
	28 => _("QATAR"),
	29 => _("ROMANIA"),
	30 => _("RUSSIAN FED"),
	31 => _("SAUDI ARABIA"),
	32 => _("SINGAPORE"),
	33 => _("SOLVENIA"),
	34 => _("SOUTH AFRICA"),
	35 => _("SPAIN"),
	37 => _("SWITZERLAND"),
	38 => _("TAIWAN"),
	39 => _("TURKEY"),
	40 => _("UKRAINE"),
	41 => _("UNITED ARAB EMIRATES"),
	42 => _("UNITED KINGDOM"), 
	44 => _("UNITED STATES OF AMERICA"), 
    43 => _("YEMEN") 
);



 $delivery_times = array(
 1=>_('8.00 AM'),
 2=>_('8.30 AM'),
 3=>_('9.00 AM'),
 4=>_('9.30 AM'),
 5=>_('10.00 AM'),
 6=>_('10.30 AM'),
 7=>_('11.00 AM'),
 8=>_('11.30 AM'),
 9=>_('12.00 PM'),
 10=>_('12.30 PM'),
 11=>_('1.00 PM'),
 12=>_('1.30 PM'),
 13=>_('2.00 PM'),
 14=>_('2.30 PM'),
 15=>_('3.00 PM'),
 16=>_('3.30 PM'),
 17=>_('4.00 PM'),
 18=>_('4.30 PM'),
 19=>_('5.00 PM'),
 20=>_('5.30 PM'),
 21=>_('6.00 PM'),
 22=>_('6.30 PM'),
 23=>_('7.00 PM'),
 24=>_('7.30 PM'),
 25=>_('8.00 PM')
 );
 
 $credit_limit_tag = array(
1=>_('Open'),
2=>_('Credit Limit'),
3=>_('Credit Limit + Due Amount'));


$invoice_types = array(
'SI'=>_('Credit'),
'SC'=>_('Cash'));

$invoice_collection = array(
   	-1  => _("All"),
	0   => _("Not Collected"),
	1   => _("Collected")
);


$inv_adjustment_types = array(
   	0  => _("Select a Type"),
	1   => _("Inventory Adjustment"),
	2   => _("Physical Stock")
);

$purchase_types = array(
   	1   => _("Local Purchase"),
	2   => _("Import Purchase")
);