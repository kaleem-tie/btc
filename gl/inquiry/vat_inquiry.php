<?php
$page_security = 'SA_GLTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/admin/db/fiscalyears_db.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/reporting/includes/Workbook.php");
include_once($path_to_root . "/gl/includes/db/vat_db.inc");

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

page(_($help_context = "VAT Inquiry"), false, false, '', $js);

start_form();
    start_table(TABLESTYLE_NOBORDER);
	$date = today();
	if (!isset($_POST['TransToDate']))
		$_POST['TransToDate'] = end_month($date);
	if (!isset($_POST['TransFromDate']))
		$_POST['TransFromDate'] = add_days(end_month($date), -user_transaction_days());
	start_row();
	date_cells(_("From:"), 'TransFromDate');
	date_cells(_("To:"), 'TransToDate');
    submit_cells('Show',_("Show"),'','', 'default');
	end_row();
	end_table();

	echo '<hr>';
    end_form();
	


if (get_post('Show')) 
{
	display_notification(_("Please Click on View/Download File to download VAT Report."));
	
	$cmp_result  = get_company_prefs();
	
		
// Creating a workbook
$workbook = new Spreadsheet_Excel_Writer_Workbook('VAT-Report-Details.xls');

// Creating a worksheet
$worksheet =& $workbook->addWorksheet('VAT-Return-Format Oman');


// The actual data
$worksheet->write(0, 0,  htmlspecialchars_decode($cmp_result['coy_name']));
$worksheet->write(1, 0, 'CONTENT OF VAT RETURN');
$worksheet->write(2, 0, 'From '.$_POST['TransFromDate'].' to '.$_POST['TransToDate']);
$worksheet->write(3, 0, '1. Suppliers in the Sultanate of Oman');
$worksheet->write(3, 1, 'Taxable base (OMR)');
$worksheet->write(3, 2, 'VAT Due (OMR)');

$total_sales=0;
$total_vat=0;

// for 1(a)
$domestic_sales_summary_result=get_domestic_vat_si_transactions_summary($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$worksheet->write(4, 0, '1(a) Suppliers of goods / services taxed at 5% ');
$worksheet->write(4, 1, $domestic_sales_summary_result['value_of_supply']);
$worksheet->write(4, 2, $domestic_sales_summary_result['vat_amount']);
$total_sales+=$domestic_sales_summary_result['value_of_supply'];
$total_vat+=$domestic_sales_summary_result['vat_amount']; 
// end 1(a)


// for 1(b)
$domestic_sales_zero_rated_summary_result=get_domestic_vat_zero_si_transactions_summary($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$worksheet->write(5, 0, '1(b) Suppliers of goods / services taxed at 0% ');
$worksheet->write(5, 1, $domestic_sales_zero_rated_summary_result['value_of_supply']);
$worksheet->write(5, 2, $domestic_sales_zero_rated_summary_result['vat_amount']);
$total_sales+=$domestic_sales_zero_rated_summary_result['value_of_supply'];
$total_vat+=$domestic_sales_zero_rated_summary_result['vat_amount']; 
// end 1(b)


// for 1(C)
$domestic_sales_tax_exempted_summary_result=get_domestic_vat_exempt_si_transactions_summary($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$worksheet->write(6, 0, '1(c) Suppliers of goods / services tax exempt ');
$worksheet->write(6, 1, $domestic_sales_tax_exempted_summary_result['value_of_supply']);
$worksheet->write(6, 2, $domestic_sales_tax_exempted_summary_result['vat_amount']);
$total_sales+=$domestic_sales_tax_exempted_summary_result['value_of_supply'];
$total_vat+=$domestic_sales_tax_exempted_summary_result['vat_amount']; 
// end 1(c)


// for 1(d)
$worksheet->write(7, 0, '1(d) Supplies of goods, tax levy shifted to recipient inside GCC (Supplies made by you that are subject to Reverse Charge Mechanism)');
$worksheet->write(7, 1, 0);
$worksheet->write(7, 2, 0);
// end 1(d)


// for 1(e)
$worksheet->write(8, 0, '1(e) Supplies of services, tax levy shifted to recipient inside GCC (Supplies made by you that are subject to Reverse Charge Mechanism)');
$worksheet->write(8, 1, 0);
$worksheet->write(8, 2, 0);
// end 1(e)


// for 1(f)
$worksheet->write(9, 0, '1(f) Taxable goods as per profit margin scheme');
$worksheet->write(9, 1, 0);
$worksheet->write(9, 2, 0);
// end 1(f)



$worksheet->write(10, 0, '2. Purchase subject to Reverse Charge Mechanism');
$worksheet->write(10, 1, 'Taxable base (OMR)');
$worksheet->write(10, 2, 'VAT Due (OMR)');

// for 2(a)
$worksheet->write(11, 0, '2(a) Purchase from the GCC subject to Reverse Charge Mechanism');
$worksheet->write(11, 1, 0);
$worksheet->write(11, 2, 0);
// end 2(a)


// for 2(b)
$worksheet->write(12, 0, '2(b) Purchases from outside of GCC subject to Reverse Charge Mechanism');
$worksheet->write(12, 1, 0);
$worksheet->write(12, 2, 0);
// end 2(b)


$worksheet->write(13, 0, '3. Supplies to countries outside of Oman');
$worksheet->write(13, 1, 'Taxable base (OMR)');
$worksheet->write(13, 2, 'VAT Due (OMR)');

// for 3(a)
$worksheet->write(14, 0, '3(a) Exports');
$export_transactions_summary_result=get_export_transactions_summary($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$worksheet->write(14, 1, $export_transactions_summary_result['value_of_supply']);
// end 3(a)


$worksheet->write(15, 0, '4. Import of Goods');
$worksheet->write(15, 1, 'Taxable base (OMR)');
$worksheet->write(15, 2, 'VAT Due (OMR)');

// for 4(a)
$worksheet->write(16, 0, '4(a) Import of Goods (Postponed Payment)');
$worksheet->write(16, 1, 0);
$worksheet->write(16, 2, 0);
// end 4(a)


// for 4(b)
$worksheet->write(17, 0, '4(b) Total goods imported');
$import_transactions_summary_result=get_import_transactions_summary($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$worksheet->write(17, 1, $import_transactions_summary_result['value_of_supply']);
// end 4(b)



$worksheet->write(18, 0, '5. Total VAT Due');
$worksheet->write(18, 2, 'VAT Due (OMR)');

// for 5(a)
$worksheet->write(19, 0, '5(a) Total VAT due under 1(a) + 1(f) + 2(a) + 2(b)+4(a)');
$worksheet->write(19, 2, $domestic_sales_summary_result['vat_amount']);
// end 5(a)


// for 5(b)
 $worksheet->write(20, 0, '5(b) Adjustment of VAT due');
//$domestic_salesreturn_summary_result=get_domestic_vat_sr_transactions_summary($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
//$worksheet->write(20, 2, $domestic_salesreturn_summary_result['vat_amount']);
// end 5(b)




$worksheet->write(21, 0, '6. Input VAT credit');
$worksheet->write(21, 1, 'Taxable base (OMR)');
$worksheet->write(21, 2, 'Recoverable VAT (OMR)');

// for 6(a)
$worksheet->write(22, 0, '6(a) Purchases (except import of goods)');
$domestic_purchases_summary_result=get_domestic_vat_pi_transactions_summary($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$worksheet->write(22, 1, $domestic_purchases_summary_result['value_of_supply']);
$worksheet->write(22, 2, $domestic_purchases_summary_result['vat_amount']);
// end 6(a)


// for 6(b)
$worksheet->write(23, 0, '6(b) Import of goods');
$worksheet->write(23, 1, $import_transactions_summary_result['value_of_supply']);
$worksheet->write(23, 2, $import_transactions_summary_result['vat_amount']);
// end 6(b)

// for 6(c)
$worksheet->write(24, 0, '6(c) VAT on acquisition of fixed assets');
$worksheet->write(24, 2, 0);
// end 6(c)

// for 6(d)
$worksheet->write(25, 0, '6(d) Adjustment of input VAT credit');
//$domestic_purchasereturn_summary_result=get_domestic_vat_pr_transactions_summary($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
//$worksheet->write(25, 1, $domestic_purchasereturn_summary_result['value_of_supply']);
//$worksheet->write(25, 2, $domestic_purchasereturn_summary_result['vat_amount']);
// end 6(d)




// for 6(e)
$worksheet->write(26, 0, '6(e) VAT incurred Pre-Registration');
$worksheet->write(26, 1, 0);
$worksheet->write(26, 2, 0);
// end 6(e)



$worksheet->write(27, 0, '7. Tax liability calculation');
$worksheet->write(27, 2, '(OMR)');

// for 7(a)
$worksheet->write(28, 0, '7(a) Total VAT due (5(a)+5(b))');
$worksheet->write(28, 2, $domestic_sales_summary_result['vat_amount']+$domestic_salesreturn_summary_result['vat_amount']);
// end 7(a)


// for 7(b)
$worksheet->write(29, 0, '7(b) Total input VAT credit (6(a)+6(b)+6(c)+6(d)+6(e))');
$worksheet->write(29, 2, $domestic_purchases_summary_result['vat_amount']+$import_transactions_summary_result['vat_amount']+$domestic_purchasereturn_summary_result['vat_amount']);
// end 7(b)

// for 7(c)
$worksheet->write(30, 0, '7(c) Total (7(a)-7(b))');
$worksheet->write(30, 2, $domestic_sales_summary_result['vat_amount']-($domestic_purchases_summary_result['vat_amount']+$import_transactions_summary_result['vat_amount']));
// end 7(c)



// Creating a worksheet
$worksheet =& $workbook->addWorksheet('Std Rated Sales - Box 1(a)');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("Supplies of goods / services taxed at 5% - Box 1(a)* - If Group Should be Split By Group Member"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Taxpayer Name / Member Company Name (If applicable)');
$worksheet->write(2, 3, 'Tax Invoice/Tax Credit Note #');
$worksheet->write(2, 4, 'Tax Invoice/Tax credit note Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Reporting period (From DD/MM/YYYY to DD/MM/YYYY format only)');
$worksheet->write(2, 6, 'Tax Invoice/Tax credit note Amount OMR (before VAT)');
$worksheet->write(2, 7, 'VAT Amount OMR');
$worksheet->write(2, 8, 'Customer Name');
$worksheet->write(2, 9, 'Customer VATIN');
$worksheet->write(2, 10, 'Clear description of the supply');


$domestic_vat_si_transactions=get_domestic_vat_si_transactions($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$i=3;
$sno=1;
$total_taxable_value=0;
$total_tax=0;
 while($domestic_vat_si_transaction=db_fetch($domestic_vat_si_transactions))
{

if($domestic_vat_si_transaction['tax_included'])
{
	$worksheet->write($i, 0, $sno);
$worksheet->write($i, 1, $cmp_result['gst_no']);
$worksheet->write($i, 2, $cmp_result['coy_name']);
$worksheet->write($i, 3, $domestic_vat_si_transaction['reference']);
$worksheet->write($i, 4, sql2date($domestic_vat_si_transaction['tran_date']));
$worksheet->write($i, 5, $_POST['TransFromDate']. ' '.$_POST['TransToDate'] );
	
	if($domestic_vat_si_transaction['type']==10)
	{
	$worksheet->write($i, 6, $domestic_vat_si_transaction['ov_amount']+$domestic_vat_si_transaction['ov_freight']-$domestic_vat_si_transaction['tax_amount']);
	$total_taxable_value+=$domestic_vat_si_transaction['ov_amount']+$domestic_vat_si_transaction['ov_freight']-$domestic_vat_si_transaction['tax_amount'];
	$worksheet->write($i, 7, $domestic_vat_si_transaction['tax_amount']);
	$total_tax+=$domestic_vat_si_transaction['tax_amount'];
	}
	if($domestic_vat_si_transaction['type']==11)
	{
		$worksheet->write($i, 6, -($domestic_vat_si_transaction['ov_amount']+$domestic_vat_si_transaction['ov_freight']-$domestic_vat_si_transaction['tax_amount']));
	$total_taxable_value-=($domestic_vat_si_transaction['ov_amount']+$domestic_vat_si_transaction['ov_freight']-$domestic_vat_si_transaction['tax_amount']);
	$worksheet->write($i, 7, -$domestic_vat_si_transaction['tax_amount']);
	$total_tax-=$domestic_vat_si_transaction['tax_amount'];
	$worksheet->write($i, 10, 'Sales Return');
	}
	
	$worksheet->write($i, 8, $domestic_vat_si_transaction['name']);
$worksheet->write($i, 9, $domestic_vat_si_transaction['tax_id']);

$i++;
$sno++;
}
else
{
	/*$worksheet->write($i, 6, $domestic_vat_si_transaction['ov_amount']+$domestic_vat_si_transaction['ov_freight']);
	$total_taxable_value+=$domestic_vat_si_transaction['ov_amount']+$domestic_vat_si_transaction['ov_freight'];
	$worksheet->write($i, 7, $domestic_vat_si_transaction['ov_gst']+$domestic_vat_si_transaction['ov_freight_tax']);
	$total_tax+=$domestic_vat_si_transaction['ov_gst']+$domestic_vat_si_transaction['ov_freight_tax']; */
	
	if($domestic_vat_si_transaction['type']==12)
	{
	
      $advance_outstanding_tax_value=get_advance_outstanding_tax_value($domestic_vat_si_transaction['trans_no'],$_POST['TransFromDate'],$_POST['TransToDate']);	
	  
	  if($advance_outstanding_tax_value!=0)
	  {
	  
	$worksheet->write($i, 0, $sno);
$worksheet->write($i, 1, $cmp_result['gst_no']);
$worksheet->write($i, 2, $cmp_result['coy_name']);
$worksheet->write($i, 3, $domestic_vat_si_transaction['reference']);
$worksheet->write($i, 4, sql2date($domestic_vat_si_transaction['tran_date']));
$worksheet->write($i, 5, $_POST['TransFromDate']. ' '.$_POST['TransToDate'] );	
	$worksheet->write($i, 6, 20*$advance_outstanding_tax_value);
	$total_taxable_value+=(20*$advance_outstanding_tax_value);
	$worksheet->write($i, 7,$advance_outstanding_tax_value );
	$total_tax+=$advance_outstanding_tax_value;
	$worksheet->write($i, 8, $domestic_vat_si_transaction['name']);
$worksheet->write($i, 9, $domestic_vat_si_transaction['tax_id']);
 $worksheet->write($i, 10, 'Advance Collected');
$i++;
$sno++;
   	
	  }
	}
	else
	{
		$worksheet->write($i, 0, $sno);
$worksheet->write($i, 1, $cmp_result['gst_no']);
$worksheet->write($i, 2, $cmp_result['coy_name']);
$worksheet->write($i, 3, $domestic_vat_si_transaction['reference']);
$worksheet->write($i, 4, sql2date($domestic_vat_si_transaction['tran_date']));
$worksheet->write($i, 5, $_POST['TransFromDate']. ' '.$_POST['TransToDate'] );
	if($domestic_vat_si_transaction['type']==10)
	{

	$worksheet->write($i, 6, $domestic_vat_si_transaction['ov_amount']+$domestic_vat_si_transaction['ov_freight']);
	$total_taxable_value+=$domestic_vat_si_transaction['ov_amount']+$domestic_vat_si_transaction['ov_freight'];
	$worksheet->write($i, 7, $domestic_vat_si_transaction['ov_gst']+$domestic_vat_si_transaction['ov_freight_tax']);
	$total_tax+=$domestic_vat_si_transaction['ov_gst']+$domestic_vat_si_transaction['ov_freight_tax'];
	}
	if($domestic_vat_si_transaction['type']==11)
	{
	$worksheet->write($i, 6, -($domestic_vat_si_transaction['ov_amount']+$domestic_vat_si_transaction['ov_freight']));
	$total_taxable_value-=($domestic_vat_si_transaction['ov_amount']+$domestic_vat_si_transaction['ov_freight']);
	$worksheet->write($i, 7, -($domestic_vat_si_transaction['ov_gst']+$domestic_vat_si_transaction['ov_freight_tax']));
	$total_tax-=($domestic_vat_si_transaction['ov_gst']+$domestic_vat_si_transaction['ov_freight_tax']);
	$worksheet->write($i, 10, 'Sales Return');
	}
	$worksheet->write($i, 8, $domestic_vat_si_transaction['name']);
$worksheet->write($i, 9, $domestic_vat_si_transaction['tax_id']);

$i++;
$sno++;
	}
	
}

}

$i++;
$worksheet->write($i, 5, "Total");
$worksheet->write($i, 6, $total_taxable_value);
$worksheet->write($i, 7, $total_tax);



// Creating a worksheet
$worksheet =& $workbook->addWorksheet('Zero Rated Sales - Box 1(b)');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("Supplies of goods / services taxed at 0% - Box 1(b)* - If Group Should be Split By Group Member"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Taxpayer Name / Member Company Name (If applicable)');
$worksheet->write(2, 3, 'Tax Invoice/Tax Credit Note #');
$worksheet->write(2, 4, 'Tax Invoice/Tax credit note Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Reporting period (From DD/MM/YYYY to DD/MM/YYYY format only)');
$worksheet->write(2, 6, 'Tax Invoice/Tax credit note Amount OMR (before VAT)');
$worksheet->write(2, 7, 'Customer Name');
$worksheet->write(2, 8, 'Customer VATIN');
$worksheet->write(2, 9, 'Customer Country');
$worksheet->write(2, 10, 'Clear description of the supply');


$domestic_vat_zerorated_transactions=get_domestic_vat_zerorated_transactions($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$i=3;
$sno=1;
$total_taxable_value=0;
$total_tax=0;
while($domestic_vat_zerorated_transaction=db_fetch($domestic_vat_zerorated_transactions))
{
$worksheet->write($i, 0, $sno);
$worksheet->write($i, 1, $cmp_result['gst_no']);
$worksheet->write($i, 2, $cmp_result['coy_name']);
$worksheet->write($i, 3, $domestic_vat_zerorated_transaction['reference']);
$worksheet->write($i, 4, sql2date($domestic_vat_zerorated_transaction['tran_date']));
$worksheet->write($i, 5, $_POST['TransFromDate']. ' '.$_POST['TransToDate'] );
$worksheet->write($i, 6, $domestic_vat_zerorated_transaction['ov_amount']+$domestic_vat_zerorated_transaction['ov_freight']);

$total_taxable_value+= $domestic_vat_zerorated_transaction['ov_amount']+$domestic_vat_zerorated_transaction['ov_freight'];

$worksheet->write($i, 7, $domestic_vat_zerorated_transaction['name']);
$worksheet->write($i, 8, $domestic_vat_zerorated_transaction['tax_id']);
$i++;
$sno++;
}

$i++;
$worksheet->write($i, 5, "Total");
$worksheet->write($i, 6, $total_taxable_value);




// Creating a worksheet
$worksheet =& $workbook->addWorksheet(' Exempt Supplies - Box 1(c)');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("Supplies of goods / services tax exempt - Box 1(c)* - If Group Should be Split By Group Member"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Taxpayer Name / Member Company Name (If applicable)');
$worksheet->write(2, 3, 'Tax Invoice/Tax Credit Note #');
$worksheet->write(2, 4, 'Tax Invoice/Tax credit note Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Reporting period (From DD/MM/YYYY to DD/MM/YYYY format only)');
$worksheet->write(2, 6, 'Tax Invoice/Tax credit note Amount OMR (before VAT)');
$worksheet->write(2, 7, 'Customer Name');
$worksheet->write(2, 8, 'Customer VATIN');
$worksheet->write(2, 9, 'Customer Country');
$worksheet->write(2, 10, 'Clear description of the supply');


$domestic_vat_exempt_transactions=get_domestic_vat_exempt_transactions($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$i=3;
$sno=1;
$total_taxable_value=0;
$total_tax=0;
 while($domestic_vat_exempt_transaction=db_fetch($domestic_vat_exempt_transactions))
{
$worksheet->write($i, 0, $sno);
$worksheet->write($i, 1, $cmp_result['gst_no']);
$worksheet->write($i, 2, $cmp_result['coy_name']);
$worksheet->write($i, 3, $domestic_vat_exempt_transaction['reference']);
$worksheet->write($i, 4, sql2date($domestic_vat_exempt_transaction['tran_date']));
$worksheet->write($i, 5, $_POST['TransFromDate']. ' '.$_POST['TransToDate'] );
$worksheet->write($i, 6, $domestic_vat_exempt_transaction['ov_amount']+$domestic_vat_exempt_transaction['ov_freight']);

$total_taxable_value+= $domestic_vat_exempt_transaction['ov_amount']+$domestic_vat_exempt_transaction['ov_freight'];

$worksheet->write($i, 7, $domestic_vat_exempt_transaction['name']);
$worksheet->write($i, 8, $domestic_vat_exempt_transaction['tax_id']);
$i++;
$sno++;
}

$i++;
$worksheet->write($i, 5, "Total");
$worksheet->write($i, 6, $total_taxable_value);


// Creating a worksheet
$worksheet =& $workbook->addWorksheet('Profit Margin Sales - Box 1(f)');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("Supply of goods as per profit margin scheme - Box 1(f)* - If Group Should be Split By Group Member"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Taxpayer Name / Member Company Name (If applicable)');
$worksheet->write(2, 3, 'Tax Invoice/Tax Credit Note #');
$worksheet->write(2, 4, 'Tax Invoice/Tax credit note Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Reporting period (From DD/MM/YYYY to DD/MM/YYYY format only)');
$worksheet->write(2, 6, 'Profit Margin Self-Invoice Number');
$worksheet->write(2, 7, 'Cost of Purchase');
$worksheet->write(2, 8, 'Tax Invoice/Tax credit note Amount OMR (before VAT)');
$worksheet->write(2, 9, 'Gross Profit Margin');
$worksheet->write(2, 10, 'VAT-exclusive Profit Margin'); 
$worksheet->write(2, 11, 'Customer Name');
$worksheet->write(2, 12, 'Clear description of the supply');



// Creating a worksheet
$worksheet =& $workbook->addWorksheet('Intra-Group Supplies');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("REPORTING OF SUPPLIES BETWEEN VAT GROUP MEMBERS (if applicable)"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Supplier Member Company Name');
$worksheet->write(2, 3, 'Invoice/Credit Note # (If applicable)');
$worksheet->write(2, 4, 'Transaction Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Transaction Amount OMR');
$worksheet->write(2, 6, 'Customer Member Company Name');
$worksheet->write(2, 7, 'Clear description of the supply');


// Creating a worksheet
$worksheet =& $workbook->addWorksheet('RCM Purchases - Box 2(b)');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("Purchases from outside of GCC subject to Reverse Charge Mechanism - Box 2(b)* - If Group Should be Split By Group Member"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Taxpayer Name / Member Company Name (If applicable)');
$worksheet->write(2, 3, 'Supplier Tax Invoice/Tax Credit Note No');
$worksheet->write(2, 4, 'Invoice/ credit note Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Reporting period (From DD/MM/YYYY to DD/MM/YYYY format only)');
$worksheet->write(2, 6, 'Invoice/Credit Note Amount Foreign Currency');
$worksheet->write(2, 7, 'Currency symbol');
$worksheet->write(2, 8, 'Exchange rate used');
$worksheet->write(2, 9, 'Invoice/credit note Amount OMR');
$worksheet->write(2, 10, 'Reverse Charge VAT Amount OMR');
$worksheet->write(2, 11, 'Supplier Name');
$worksheet->write(2, 12, 'Supplier Country');
$worksheet->write(2, 13, 'Clear description of the transaction');
$worksheet->write(2, 14, 'Deductible Reverse Charge VAT Amount OMR [to Box 6(a)]');


// Creating a worksheet
$worksheet =& $workbook->addWorksheet('Exports - Box 3(a)');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("Exports of Goods and Services - Box 3(a)* - If Group Should be Split By Group Member"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Taxpayer Name / Member Company Name (If applicable)');
$worksheet->write(2, 3, 'Tax Invoice/Tax Credit Note #');
$worksheet->write(2, 4, 'Tax Invoice/Tax credit note Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Reporting period (From DD/MM/YYYY to DD/MM/YYYY format only)');
$worksheet->write(2, 6, 'Tax Invoice/Tax credit note Amount OMR (before VAT)');
$worksheet->write(2, 7, 'Customer Name');
$worksheet->write(2, 8, 'Customer VATIN');
$worksheet->write(2, 9, 'Customer Country');
$worksheet->write(2, 10, 'Clear description of the supply');
$worksheet->write(2, 11, 'VAT Adjustments (if any)');


$export_transactions=get_export_transactions($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$i=3;
$sno=1;

$total_taxable_value=0;
$total_tax=0;

 while($export_transaction=db_fetch($export_transactions))
{
$worksheet->write($i, 0, $sno);
$worksheet->write($i, 1, $cmp_result['gst_no']);
$worksheet->write($i, 2, $cmp_result['coy_name']);
$worksheet->write($i, 3, $export_transaction['reference']);
$worksheet->write($i, 4, sql2date($export_transaction['tran_date']));
$worksheet->write($i, 5, $_POST['TransFromDate']. ' '.$_POST['TransToDate'] );

$worksheet->write($i, 6, (($export_transaction['ov_amount']+$export_transaction['ov_freight'])*$export_transaction['rate']));
$total_taxable_value+=($export_transaction['ov_amount']+$export_transaction['ov_freight'])*$export_transaction['rate'];
$worksheet->write($i, 7, $export_transaction['name']);
$worksheet->write($i, 9, $domestic_vat_si_transaction['tax_id']);
$i++;
$sno++;
}

$i++;
$worksheet->write($i, 5, "Total");
$worksheet->write($i, 6, $total_taxable_value);


// Creating a worksheet
$worksheet =& $workbook->addWorksheet('Deferred Import - Box 4(a)');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("Import of Goods (Postponed payment) - BOX 4(a)* - If Group Should be Split By Group Member"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Company Name / Member Company Name (If applicable)');
$worksheet->write(2, 3, 'Invoice # / Document #');
$worksheet->write(2, 4, 'Invoice/ Document Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Invoice Amount OMR (before VAT)');
$worksheet->write(2, 6, 'VAT Amount OMR');
$worksheet->write(2, 7, 'Supplier Name');
$worksheet->write(2, 8, 'Supplier Country');
$worksheet->write(2, 9, 'Taxable amount to be reported on VAT return Box 4(a)');
$worksheet->write(2, 10, 'Customs Declaration Number');
$worksheet->write(2, 11, 'Clear description of the goods imported');
$worksheet->write(2, 12, 'Deductible VAT OMR Box 6(b)');



// Creating a worksheet
$worksheet =& $workbook->addWorksheet('Goods Imports - Box 4(b)');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("Total goods imported - BOXES 4(b)* - If Group Should be Split By Group Member"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Company Name / Member Company Name (If applicable)');
$worksheet->write(2, 3, 'Invoice # / Document #');
$worksheet->write(2, 4, 'Invoice/ Document Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Reporting period (From DD/MM/YYYY to DD/MM/YYYY format only');
$worksheet->write(2, 6, 'Invoice Amount OMR (before VAT)');
$worksheet->write(2, 7, 'VAT Amount OMR');
$worksheet->write(2, 8, 'Supplier Name');
$worksheet->write(2, 9, 'Supplier Country');
$worksheet->write(2, 10, 'Amount paid to Directorate General for Customs Box 4(b)');
$worksheet->write(2, 11, 'Customs Declaration Number');
$worksheet->write(2, 12, 'Clear description of the goods imported');
$worksheet->write(2, 13, 'Deductible VAT OMR Box 6(b)');


$import_transactions=get_import_transactions($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$i=3;
$sno=1;

$custom_total=0;
$input_vat_total=0;

  while($import_transaction=db_fetch($import_transactions))
{
$worksheet->write($i, 0, $sno);
$worksheet->write($i, 1, $cmp_result['gst_no']);
$worksheet->write($i, 2, $cmp_result['coy_name']);
$worksheet->write($i, 3, $import_transaction['reference']);
$worksheet->write($i, 4, sql2date($import_transaction['tran_date']));
$worksheet->write($i, 5, $_POST['TransFromDate']. ' '.$_POST['TransToDate'] );
/*$worksheet->write($i, 6, ($import_transaction['ov_amount']+$import_transaction['additional_charges']+$import_transaction['packing_charges']+$import_transaction['other_charges']+$import_transaction['freight_cost'])*$import_transaction['rate']); */
$worksheet->write($i, 6, $import_transaction['cif_value']);
$worksheet->write($i, 7, $import_transaction['vat_import_value']);
$worksheet->write($i, 8, $import_transaction['supp_name']);
$worksheet->write($i, 9, $import_transaction['curr_code']);
$worksheet->write($i, 10, $import_transaction['custom_duties']);
$worksheet->write($i, 11, $import_transaction['declaration_no']);
$worksheet->write($i, 13, $import_transaction['vat_import_value']);

$custom_total+=$import_transaction['custom_duties'];
$input_vat_total+=$import_transaction['vat_import_value'];

$i++;
$sno++;
}

$i++;
$worksheet->write($i, 5, "Total");
$worksheet->write($i, 7, $input_vat_total);
$worksheet->write($i, 10, $custom_total);
$worksheet->write($i, 13, $input_vat_total);


// Creating a worksheet
$worksheet =& $workbook->addWorksheet('Output Adjustments - Box 5(b)');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("Adjustment of VAT due - Box 5(b)* - If Group Should be Split By Group Member"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Company Name / Member Company Name (If applicable)');
$worksheet->write(2, 3, 'Invoice # / Document #');
$worksheet->write(2, 4, 'Invoice/ Document Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Reporting period (From DD/MM/YYYY to DD/MM/YYYY format only');
$worksheet->write(2, 6, 'VAT Adjustment Amount OMR');
$worksheet->write(2, 7, 'Clear description of the adjustment');

/*

$domestic_vat_sr_transactions=get_domestic_vat_sr_transactions($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$i=3;
$sno=1;
$tax_amount_total=0;

  while($domestic_vat_sr_transaction=db_fetch($domestic_vat_sr_transactions))
{
$worksheet->write($i, 0, $sno);
$worksheet->write($i, 1, $cmp_result['gst_no']);
$worksheet->write($i, 2, $cmp_result['coy_name']);
$worksheet->write($i, 3, $domestic_vat_sr_transaction['reference']);
$worksheet->write($i, 4, sql2date($domestic_vat_sr_transaction['tran_date']));
$worksheet->write($i, 5, $_POST['TransFromDate']. ' '.$_POST['TransToDate'] );
$worksheet->write($i, 6, $domestic_vat_sr_transaction['vat_amount']);
$tax_amount_total+=$domestic_vat_sr_transaction['vat_amount'];
$i++;
$sno++;
}

$i++;
$worksheet->write($i, 5, "Total");
$worksheet->write($i, 6, $tax_amount_total);


*/

// Creating a worksheet
$worksheet =& $workbook->addWorksheet('Input Tax - Box 6(a)');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("Purchases (except import of goods) - BOX 6(a)* - If Group Should be Split By Group Member"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Taxpayer Name / Member Company Name (If applicable)');
$worksheet->write(2, 3, 'Invoice # / Document #');
$worksheet->write(2, 4, 'Tax Invoice/Tax Credit Note Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Tax Invoice/Tax credit note Received Date - DD/MM/YYYY format only');
$worksheet->write(2, 6, 'Reporting period (From DD/MM/YYYY to DD/MM/YYYY format only)');
$worksheet->write(2, 7, 'Tax Invoice/Tax credit note Amount OMR (before VAT)');
$worksheet->write(2, 8, 'VAT Amount OMR');
$worksheet->write(2, 9, 'VAT Amount Claimed OMR');
$worksheet->write(2, 10, 'Supplier Name');
$worksheet->write(2, 11, 'Supplier VATIN');
$worksheet->write(2, 12, 'Clear description of the supply');


$domestic_vat_pi_transactions=get_domestic_vat_pi_transactions($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$i=3;
$sno=1;


  while($domestic_vat_pi_transaction=db_fetch($domestic_vat_pi_transactions))
{
$worksheet->write($i, 0, $sno);
$worksheet->write($i, 1, $cmp_result['gst_no']);
$worksheet->write($i, 2, $cmp_result['coy_name']);
$worksheet->write($i, 3, $domestic_vat_pi_transaction['reference']);
$worksheet->write($i, 4, sql2date($domestic_vat_pi_transaction['tran_date']));
$worksheet->write($i, 5, $_POST['TransFromDate']. ' '.$_POST['TransToDate'] );
if($domestic_vat_pi_transaction['tax_included'])
{
$worksheet->write($i, 7, $domestic_vat_pi_transaction['ov_amount']+$domestic_vat_pi_transaction['freight_cost']+$domestic_vat_pi_transaction['additional_charges']+$domestic_vat_pi_transaction['packing_charges']+$domestic_vat_pi_transaction['other_charges']-$domestic_vat_pi_transaction['tax_amount']);
}
else
{
$worksheet->write($i, 7, $domestic_vat_pi_transaction['ov_amount']+$domestic_vat_pi_transaction['freight_cost']+$domestic_vat_pi_transaction['additional_charges']+$domestic_vat_pi_transaction['packing_charges']+$domestic_vat_pi_transaction['other_charges']);
}
$worksheet->write($i, 8, $domestic_vat_pi_transaction['tax_amount']);
$worksheet->write($i, 10, $domestic_vat_pi_transaction['supp_name']);
$worksheet->write($i, 11, $domestic_vat_pi_transaction['gst_no']);
$worksheet->write($i, 12, get_domestic_vat_pi_comments($domestic_vat_pi_transaction['trans_no']));

$i++;
$sno++;
}


$vat_on_cash_bills=get_input_vat_on_multiple_purch_cash_bill($_POST['TransFromDate'],$_POST['TransToDate']);

while($vat_on_cash_bill=db_fetch($vat_on_cash_bills))
{
$worksheet->write($i, 0, $sno);
$worksheet->write($i, 1, $cmp_result['gst_no']);
$worksheet->write($i, 2, $cmp_result['coy_name']);
$worksheet->write($i, 3, $vat_on_cash_bill['supp_bill_no']);
$worksheet->write($i, 4, sql2date($vat_on_cash_bill['supp_bill_date']));
$worksheet->write($i, 5, $_POST['TransFromDate']. ' '.$_POST['TransToDate'] );
$taxable_amount = $vat_on_cash_bill['taxable_amount'];
$tax_amount = $vat_on_cash_bill['tax_amount'];
$worksheet->write($i, 7, $taxable_amount);
$worksheet->write($i, 8, $tax_amount);
$worksheet->write($i, 10, $vat_on_cash_bill['supp_name']);
$worksheet->write($i, 11, $vat_on_cash_bill['supp_vat_no']);
$worksheet->write($i, 12, $vat_on_cash_bill['memo_']);

$i++;
$sno++;
}

$domestic_vat_pr_transactions=get_domestic_vat_pr_transactions($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
 while($domestic_vat_pr_transaction=db_fetch($domestic_vat_pr_transactions))
{
$worksheet->write($i, 0, $sno);
$worksheet->write($i, 1, $cmp_result['gst_no']);
$worksheet->write($i, 2, $cmp_result['coy_name']);
$worksheet->write($i, 3, $domestic_vat_pr_transaction['reference']);
$worksheet->write($i, 4, sql2date($domestic_vat_pr_transaction['tran_date']));
$worksheet->write($i, 5, $_POST['TransFromDate']. ' '.$_POST['TransToDate'] );
$worksheet->write($i, 7, $domestic_vat_pr_transaction['ov_amount']+$domestic_vat_pr_transaction['freight_cost']+$domestic_vat_pr_transaction['additional_charges']+$domestic_vat_pr_transaction['packing_charges']+$domestic_vat_pr_transaction['other_charges']);
$worksheet->write($i, 8, -$domestic_vat_pr_transaction['tax_amount']);
$worksheet->write($i, 10, $domestic_vat_pr_transaction['supp_name']);
$worksheet->write($i, 11, $domestic_vat_pr_transaction['gst_no']);
$worksheet->write($i, 12, 'Purchase Return');

$i++;
$sno++;
}


// Creating a worksheet
$worksheet =& $workbook->addWorksheet('Import - Box 6(b)');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("Import of goods - Box 6(b)* - If Group Should be Split By Group Member"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Company Name / Member Company Name (If applicable)');
$worksheet->write(2, 3, 'Invoice # / Document #');
$worksheet->write(2, 4, 'Invoice/ Document Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Reporting period (From DD/MM/YYYY to DD/MM/YYYY format only');
$worksheet->write(2, 6, 'Invoice Amount OMR (before VAT)');
$worksheet->write(2, 7, 'VAT Amount OMR');
$worksheet->write(2, 8, 'Supplier Name');
$worksheet->write(2, 9, 'Supplier Country');
$worksheet->write(2, 10, 'Amount paid to Directorate General for Customs Box 4(b)');
$worksheet->write(2, 11, 'Customs Declaration Number');
$worksheet->write(2, 12, 'Clear description of the goods imported');
$worksheet->write(2, 13, 'Deductible VAT OMR Box 6(b)');


$import_transactions=get_import_transactions($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$i=3;
$sno=1;

  while($import_transaction=db_fetch($import_transactions))
{
$worksheet->write($i, 0, $sno);
$worksheet->write($i, 1, $cmp_result['gst_no']);
$worksheet->write($i, 2, $cmp_result['coy_name']);
$worksheet->write($i, 3, $import_transaction['reference']);
$worksheet->write($i, 4, sql2date($import_transaction['tran_date']));
$worksheet->write($i, 5, $_POST['TransFromDate']. ' '.$_POST['TransToDate'] );
/*$worksheet->write($i, 6, ($import_transaction['ov_amount']+$import_transaction['additional_charges']+$import_transaction['packing_charges']+$import_transaction['other_charges']+$import_transaction['freight_cost'])*$import_transaction['rate']); */
$worksheet->write($i, 7, $import_transaction['cif_value']);

$worksheet->write($i, 7, $import_transaction['vat_import_value']);
$worksheet->write($i, 8, $import_transaction['supp_name']);
$worksheet->write($i, 9, $import_transaction['curr_code']);
$worksheet->write($i, 10, $import_transaction['custom_duties']);
$worksheet->write($i, 11, $import_transaction['declaration_no']);
$worksheet->write($i, 13, $import_transaction['vat_import_value']);
$i++;
$sno++;
}


// Creating a worksheet
$worksheet =& $workbook->addWorksheet('Fixed Assets - Box 6(c)');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("VAT on acquisition of fixed assets - Box 6(c)* - If Group Should be Split By Group Member"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Taxpayer Name / Member Company Name (If applicable)');
$worksheet->write(2, 3, 'Tax Invoice/Tax Credit Note #');
$worksheet->write(2, 4, 'Tax Invoice/Tax Credit Note Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Tax Invoice/Tax credit note Received Date - DD/MM/YYYY format only');
$worksheet->write(2, 6, 'Reporting period (From DD/MM/YYYY to DD/MM/YYYY format only)');
$worksheet->write(2, 7, 'Tax Invoice/Tax credit note Amount OMR (before VAT)');
$worksheet->write(2, 8, 'VAT Amount OMR');
$worksheet->write(2, 9, ' VAT Amount Claimed OMR');
$worksheet->write(2, 10, 'Supplier Name');
$worksheet->write(2, 11, 'Supplier VATIN');
$worksheet->write(2, 12, 'Clear description of the supply');


// Creating a worksheet
$worksheet =& $workbook->addWorksheet('Input Adjustments - Box 6(d)');
// The actual data

$worksheet->write(0, 4, htmlspecialchars_decode("Adjustment of input VAT credit - Box 6(d)* - If Group Should be Split By Group Member"));
$worksheet->write(2, 0, 'S.No');
$worksheet->write(2, 1, 'Taxpayer VATIN');
$worksheet->write(2, 2, 'Taxpayer Name / Member Company Name (If applicable)');
$worksheet->write(2, 3, 'Tax Invoice/Tax Credit Note # (if applicable)');
$worksheet->write(2, 4, 'Transaction / Adjustment Date - DD/MM/YYYY format only');
$worksheet->write(2, 5, 'Reporting period (From DD/MM/YYYY to DD/MM/YYYY format only');
$worksheet->write(2, 6, 'VAT Amount Claimed OMR');
$worksheet->write(2, 7, 'Clear description of the adjustment');


/*
$domestic_vat_pr_transactions=get_domestic_vat_pr_transactions($_POST['TransFromDate'],$_POST['TransToDate'],$cmp_result['curr_default']);
$i=3;
$sno=1;
$tax_amount_total=0;
 while($domestic_vat_pr_transaction=db_fetch($domestic_vat_pr_transactions))
{
$worksheet->write($i, 0, $sno);
$worksheet->write($i, 1, $cmp_result['gst_no']);
$worksheet->write($i, 2, $cmp_result['coy_name']);
$worksheet->write($i, 3, $domestic_vat_pr_transaction['reference']);
$worksheet->write($i, 4, sql2date($domestic_vat_pr_transaction['tran_date']));
$worksheet->write($i, 5, $_POST['TransFromDate']. ' '.$_POST['TransToDate'] );
$worksheet->write($i, 6, $domestic_vat_pr_transaction['tax_amount']);

$tax_amount_total+= $domestic_vat_pr_transaction['tax_amount'];

$i++;
$sno++;
}

$i++;
$worksheet->write($i, 5, "Total");
$worksheet->write($i, 6, $tax_amount_total);

*/

// Let's send the file
$workbook->close();
echo "<a href='$path_to_root/gl/inquiry/VAT-Report-Details.xls'>View/Download file</a>";
//header("Location: ".$path_to_root."/gl/inquiry/GSTR1-Report.xls");

br();
br();
}
?>