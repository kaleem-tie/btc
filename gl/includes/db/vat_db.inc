<?php
/**********************************************************************
   
***********************************************************************/
//--------------------------------------------------------------------------------



// for 1(a)
function get_domestic_vat_si_transactions_summary($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

    // domestic sales excluding tax
	$sql = "SELECT sum(ov_amount+ov_freight) as value_of_supply ,sum(ov_gst+ov_freight_tax) as vat_amount FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor WHERE trans.debtor_no=debtor.debtor_no and trans.type=10 and debtor.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst!=0 and debtor.business_type_id!=2 ";
	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_ex_sales_taxable_value = 0;
	$domestic_ex_sales_tax_value = 0;
	
	if($domestic_ex_sales=db_fetch($result))
	{
		$domestic_ex_sales_taxable_value = $domestic_ex_sales['value_of_supply'];
		$domestic_ex_sales_tax_value = $domestic_ex_sales['vat_amount'];
	}		
	
	// domestic sales including tax
	$sql = "SELECT sum(ov_amount+ov_freight) as value_of_supply ,sum(amount) as vat_amount FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor,".TB_PREF."trans_tax_details ttd  WHERE trans.debtor_no=debtor.debtor_no and trans.type=10 and debtor.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.type= ttd.trans_type and trans.trans_no=ttd.trans_no and ttd.trans_type=10 and trans.tax_included=1 and debtor.business_type_id!=2 and ttd.tax_type_id=1";
	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_inc_sales_taxable_value = 0;
	$domestic_inc_sales_tax_value = 0;
	
	
	
	if($domestic_inc_sales=db_fetch($result))
	{
		$domestic_inc_sales_taxable_value = $domestic_inc_sales['value_of_supply'];
		$domestic_inc_sales_tax_value = $domestic_inc_sales['vat_amount'];
	}	


	// sales advances with VAT
	/* $sql = "SELECT sum(ov_amount+ov_freight) as value_of_supply ,sum(ov_gst+ov_freight_tax) as vat_amount FROM ".TB_PREF."debtor_trans trans WHERE trans.type=12 and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst!=0 and trans.vat_adv_status=1 and trans.alloc=0 "; */
	
	 $vat_advance_act = get_company_pref('sales_vat_advance');
	 	 
	 $sql = "SELECT sum(amount) as vat_amount FROM ".TB_PREF."gl_trans WHERE account=".db_escape($vat_advance_act)." and tran_date>='$from' and tran_date<='$to'";
	 
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_sales_advance_taxable_value = 0;
	$domestic_sales_advance_tax_value = 0;
	
	
	
	if($domestic_sales_advance=db_fetch($result))
	{
		$domestic_sales_advance_taxable_value = $domestic_sales_advance['vat_amount']*20;
		$domestic_sales_advance_tax_value = $domestic_sales_advance['vat_amount'];
	}	
	
	
	$domestic_sales['value_of_supply']=$domestic_ex_sales_taxable_value +  $domestic_inc_sales_taxable_value - $domestic_inc_sales_tax_value+$domestic_sales_advance_taxable_value;
	$domestic_sales['vat_amount']=$domestic_ex_sales_tax_value + $domestic_inc_sales_tax_value+$domestic_sales_advance_tax_value;
	
	
	//included sales return
	
	// domestic sales return excluding tax
	$sql = "SELECT sum(ov_amount+ov_freight) as value_of_supply ,sum(ov_gst+ov_freight_tax) as vat_amount FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor WHERE trans.debtor_no=debtor.debtor_no and trans.type=11 and debtor.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst!=0 and debtor.business_type_id!=2 ";

	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_ex_salesreturn_taxable_value = 0;
	$domestic_ex_salesreturn_tax_value = 0;
	
	if($domestic_ex_salesreturn=db_fetch($result))
	{
		$domestic_ex_salesreturn_taxable_value = $domestic_ex_salesreturn['value_of_supply'];
		$domestic_ex_salesreturn_tax_value = $domestic_ex_salesreturn['vat_amount'];
	}		
	
	// domestic sales return including tax
	$sql = "SELECT sum(ov_amount+ov_freight) as value_of_supply ,sum(amount) as vat_amount FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor,".TB_PREF."trans_tax_details ttd  WHERE trans.debtor_no=debtor.debtor_no and trans.type=11 and debtor.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.type= ttd.trans_type and trans.trans_no=ttd.trans_no and ttd.trans_type=11 and trans.tax_included=1 and debtor.business_type_id!=2 and ttd.tax_type_id=1";
	
	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_inc_salesreturn_taxable_value = 0;
	$domestic_inc_salesreturn_tax_value = 0;
	
	
	
	if($domestic_inc_salesreturn=db_fetch($result))
	{
		$domestic_inc_salesreturn_taxable_value = $domestic_inc_salesreturn['value_of_supply'];
		$domestic_inc_salesreturn_tax_value = $domestic_inc_salesreturn['vat_amount'];
	}	


		
	$domestic_sales['value_of_supply']-=($domestic_ex_salesreturn_taxable_value +  $domestic_inc_salesreturn_taxable_value - $domestic_inc_salesreturn_tax_value);
	$domestic_sales['vat_amount']-=($domestic_ex_salesreturn_tax_value + $domestic_inc_salesreturn_tax_value);
	
	return $domestic_sales;
	
	
}


// for 1(b)
function get_domestic_vat_zero_si_transactions_summary($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

    // domestic Zero rated sales
	$sql = "SELECT sum(ov_amount+ov_freight) as value_of_supply ,sum(ov_gst+ov_freight_tax) as vat_amount FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor,".TB_PREF."cust_branch branch  WHERE trans.debtor_no=debtor.debtor_no and trans.type=10 and debtor.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst=0 and debtor.business_type_id!=2 and debtor.debtor_no=branch.debtor_no and trans.debtor_no=branch.debtor_no and trans.branch_code=branch.branch_code and branch.tax_group_id=4";
	
	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_zero_rated_sales_taxable_value = 0;
	$domestic_zero_rated_sales_tax_value = 0;
	
	if($domestic_zero_rated_sales=db_fetch($result))
	{
		$domestic_zero_rated_sales_taxable_value = $domestic_zero_rated_sales['value_of_supply'];
		$domestic_zero_rated_sales_tax_value = $domestic_zero_rated_sales['vat_amount'];
	}		
			
	$domestic_sales['value_of_supply']=$domestic_zero_rated_sales_taxable_value;
	$domestic_sales['vat_amount']=$domestic_zero_rated_sales_tax_value;
	
	return $domestic_sales;
	
	
}


// for 1(c)
function get_domestic_vat_exempt_si_transactions_summary($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

    // domestic Zero rated sales
	$sql = "SELECT sum(ov_amount+ov_freight) as value_of_supply ,sum(ov_gst+ov_freight_tax) as vat_amount FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor,".TB_PREF."cust_branch branch  WHERE trans.debtor_no=debtor.debtor_no and trans.type=10 and debtor.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst=0 and debtor.business_type_id!=2 and debtor.debtor_no=branch.debtor_no and trans.debtor_no=branch.debtor_no and trans.branch_code=branch.branch_code and branch.tax_group_id=2";
	
	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_vat_exempt_sales_taxable_value = 0;
	$domestic_vat_exempt_sales_tax_value = 0;
	
	if($domestic_vat_exempt_sales=db_fetch($result))
	{
		$domestic_vat_exempt_sales_taxable_value = $domestic_vat_exempt_sales['value_of_supply'];
		$domestic_vat_exempt_sales_tax_value = $domestic_vat_exempt_sales['vat_amount'];
	}		
			
	$domestic_sales['value_of_supply']=$domestic_vat_exempt_sales_taxable_value;
	$domestic_sales['vat_amount']=$domestic_vat_exempt_sales_tax_value;
	
	return $domestic_sales;
	
	
}



// for 3(a)
//sales export invoice
function get_export_transactions_summary($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$sql = "SELECT sum((ov_amount+ov_freight)*rate) as value_of_supply FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor WHERE trans.debtor_no=debtor.debtor_no and trans.type=10 and debtor.curr_code!=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst=0";
	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$export_sales['value_of_supply']=0;
	
	if($exports_sales_data=db_fetch($result))
	{
		$export_sales['value_of_supply']=$exports_sales_data['value_of_supply'];
	}		
			
	return $export_sales;
	
}


// for 4(b)

function get_import_transactions_summary($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;
	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$sql = "SELECT sum(cif_value) as value_of_supply, sum(vat_import_value) as vat_amount FROM ".TB_PREF."supp_trans trans, ".TB_PREF."suppliers supplier  WHERE trans.supplier_id=supplier.supplier_id and trans.type=20 and supplier.curr_code!=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst=0";
	

	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	
	
	$import_sales['value_of_supply']=0;
	$import_sales['vat_amount']=0;
	
	if($import_sales_data=db_fetch($result))
	{
		$import_sales['value_of_supply']=$import_sales_data['value_of_supply'];
		$import_sales['vat_amount']=$import_sales_data['vat_amount'];
	}		
			
	return $import_sales;	
}



// for 5(b)
function get_domestic_vat_sr_transactions_summary($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

    // domestic sales return excluding tax
	$sql = "SELECT sum(ov_amount+ov_freight) as value_of_supply ,sum(ov_gst+ov_freight_tax) as vat_amount FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor WHERE trans.debtor_no=debtor.debtor_no and trans.type=11 and debtor.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst!=0 and debtor.business_type_id!=2 ";

	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_ex_salesreturn_taxable_value = 0;
	$domestic_ex_salesreturn_tax_value = 0;
	
	if($domestic_ex_salesreturn=db_fetch($result))
	{
		$domestic_ex_salesreturn_taxable_value = $domestic_ex_salesreturn['value_of_supply'];
		$domestic_ex_salesreturn_tax_value = $domestic_ex_salesreturn['vat_amount'];
	}		
	
	// domestic sales return including tax
	$sql = "SELECT sum(ov_amount+ov_freight) as value_of_supply ,sum(amount) as vat_amount FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor,".TB_PREF."trans_tax_details ttd  WHERE trans.debtor_no=debtor.debtor_no and trans.type=11 and debtor.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.type= ttd.trans_type and trans.trans_no=ttd.trans_no and ttd.trans_type=11 and trans.tax_included=1 and debtor.business_type_id!=2 and ttd.tax_type_id=1";
	
	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_inc_salesreturn_taxable_value = 0;
	$domestic_inc_salesreturn_tax_value = 0;
	
	
	
	if($domestic_inc_salesreturn=db_fetch($result))
	{
		$domestic_inc_salesreturn_taxable_value = $domestic_inc_salesreturn['value_of_supply'];
		$domestic_inc_salesreturn_tax_value = $domestic_inc_salesreturn['vat_amount'];
	}	


		
	$domestic_salesreturn['value_of_supply']=$domestic_ex_salesreturn_taxable_value +  $domestic_inc_salesreturn_taxable_value - $domestic_inc_salesreturn_tax_value;
	$domestic_salesreturn['vat_amount']=$domestic_ex_salesreturn_tax_value + $domestic_inc_salesreturn_tax_value;
	
	return $domestic_salesreturn;
	
	
}
//for 6a

function get_domestic_vat_pi_transactions_summary($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);
	
	
	
	$sql = "SELECT sum(ov_amount+freight_cost+additional_charges+packing_charges+other_charges) as value_of_supply,sum(ov_gst+	freight_tax+additional_tax+packing_tax+other_tax) as vat_amount FROM ".TB_PREF."supp_trans trans, ".TB_PREF."suppliers supplier WHERE trans.supplier_id=supplier.supplier_id and trans.type=20 and supplier.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.reverse_charge=0";
	

		
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_purchase_taxable_value=0;
	$domestic_purchase_tax_value=0;
	
	if($domestic_purchase_data=db_fetch($result))
	{
		$domestic_purchase_taxable_value=$domestic_purchase_data['value_of_supply'];
		$domestic_purchase_tax_value=$domestic_purchase_data['vat_amount'];
	}	
	
	// get the included vat amount
	
	$sql = "SELECT sum(amount) as vat_amount FROM ".TB_PREF."supp_trans trans,".TB_PREF."suppliers supplier,".TB_PREF."trans_tax_details ttd  WHERE trans.supplier_id=supplier.supplier_id and trans.type=20 and supplier.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.type= ttd.trans_type and trans.trans_no=ttd.trans_no and ttd.trans_type=20 and trans.tax_included=1 and ttd.tax_type_id=2";
	
	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	if($domestic_purchase_vat_included_data=db_fetch($result))
	{
	   $domestic_purchase_taxable_value=$domestic_purchase_taxable_value-$domestic_purchase_vat_included_data['vat_amount'];
	   $domestic_purchase_tax_value=$domestic_purchase_tax_value+$domestic_purchase_vat_included_data['vat_amount'];
	}
	
	// get data from multiple cash bills entry
	
	$sql = "SELECT sum(CASE WHEN b.is_tax_account=0 THEN b.amount ELSE 0 END) as taxable_amount,sum(CASE WHEN b.is_tax_account=1 THEN b.amount ELSE 0 END) as tax_amount FROM ".TB_PREF."multiple_purch_cash_bill b, ".TB_PREF."bank_trans t WHERE t.trans_no=b.trans_no AND t.type=1 AND t.is_purch_cash_bill=1 AND t.trans_date >='$from' AND t.trans_date <='$to'";
	
	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	if($multiple_purchase_vat_data=db_fetch($result))
	{
	   $domestic_purchase_taxable_value=$domestic_purchase_taxable_value+$multiple_purchase_vat_data['taxable_amount'];
	   $domestic_purchase_tax_value=$domestic_purchase_tax_value+$multiple_purchase_vat_data['tax_amount'];
	}
	
	
	
	$domestic_purchases['value_of_supply']=$domestic_purchase_taxable_value;
	$domestic_purchases['vat_amount']=$domestic_purchase_tax_value;
	
	//including returns
	$sql = "SELECT sum(ov_amount) as value_of_supply ,sum(ov_gst) as vat_amount FROM ".TB_PREF."supp_trans trans,".TB_PREF."suppliers supplier WHERE trans.supplier_id=supplier.supplier_id and trans.type=21 and supplier.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst!=0 ";
	


	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_ex_purchasereturn_taxable_value = 0;
	$domestic_ex_purchasereturn_tax_value = 0;
	
	if($domestic_ex_purchasereturn=db_fetch($result))
	{
		$domestic_ex_purchasereturn_taxable_value = abs($domestic_ex_purchasereturn['value_of_supply']);
		$domestic_ex_purchasereturn_tax_value = abs($domestic_ex_purchasereturn['vat_amount']);
	}		
	
	// domestic purchase return including tax
	$sql = "SELECT sum(ov_amount) as value_of_supply ,sum(amount) as vat_amount FROM ".TB_PREF."supp_trans trans,".TB_PREF."suppliers supplier,".TB_PREF."trans_tax_details ttd  WHERE trans.supplier_id=supplier.supplier_id and trans.type=21 and supplier.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.type= ttd.trans_type and trans.trans_no=ttd.trans_no and ttd.trans_type=21 and trans.tax_included=1 and ttd.tax_type_id=2";
	

	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_inc_purchasereturn_taxable_value = 0;
	$domestic_inc_purchasereturn_tax_value = 0;
	
	
	
	if($domestic_inc_purchasereturn=db_fetch($result))
	{
		$domestic_inc_purchasereturn_taxable_value = abs($domestic_inc_purchasereturn['value_of_supply']);
		$domestic_inc_purchasereturn_tax_value = $domestic_inc_purchasereturn['vat_amount'];
	}	


		
	$domestic_purchases['value_of_supply']-=($domestic_ex_purchasereturn_taxable_value +  $domestic_inc_purchasereturn_taxable_value - $domestic_inc_purchasereturn_tax_value);
	$domestic_purchases['vat_amount']-=($domestic_ex_purchasereturn_tax_value + $domestic_inc_purchasereturn_tax_value);
	
	
	
	return $domestic_purchases;
	
}


// for 6(d)
function get_domestic_vat_pr_transactions_summary($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

    // domestic purchase return excluding tax
	$sql = "SELECT sum(ov_amount) as value_of_supply ,sum(ov_gst) as vat_amount FROM ".TB_PREF."supp_trans trans,".TB_PREF."suppliers supplier WHERE trans.supplier_id=supplier.supplier_id and trans.type=21 and supplier.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst!=0 ";
	


	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_ex_purchasereturn_taxable_value = 0;
	$domestic_ex_purchasereturn_tax_value = 0;
	
	if($domestic_ex_purchasereturn=db_fetch($result))
	{
		$domestic_ex_purchasereturn_taxable_value = abs($domestic_ex_purchasereturn['value_of_supply']);
		$domestic_ex_purchasereturn_tax_value = abs($domestic_ex_purchasereturn['vat_amount']);
	}		
	
	// domestic purchase return including tax
	$sql = "SELECT sum(ov_amount) as value_of_supply ,sum(amount) as vat_amount FROM ".TB_PREF."supp_trans trans,".TB_PREF."suppliers supplier,".TB_PREF."trans_tax_details ttd  WHERE trans.supplier_id=supplier.supplier_id and trans.type=21 and supplier.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.type= ttd.trans_type and trans.trans_no=ttd.trans_no and ttd.trans_type=21 and trans.tax_included=1 and ttd.tax_type_id=2";
	

	
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	$domestic_inc_purchasereturn_taxable_value = 0;
	$domestic_inc_purchasereturn_tax_value = 0;
	
	
	
	if($domestic_inc_purchasereturn=db_fetch($result))
	{
		$domestic_inc_purchasereturn_taxable_value = abs($domestic_inc_purchasereturn['value_of_supply']);
		$domestic_inc_purchasereturn_tax_value = $domestic_inc_purchasereturn['vat_amount'];
	}	


		
	$domestic_purchasereturn['value_of_supply']=$domestic_ex_purchasereturn_taxable_value +  $domestic_inc_purchasereturn_taxable_value - $domestic_inc_purchasereturn_tax_value;
	$domestic_purchasereturn['vat_amount']=$domestic_ex_purchasereturn_tax_value + $domestic_inc_purchasereturn_tax_value;
	
	
	return $domestic_purchasereturn;
	
	
}

//Std Rated Sales - Box 1(a)

function get_domestic_vat_si_transactions($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

	 $sql = "SELECT DISTINCT trans.*,debtor.tax_id,debtor.name,debtor.vat_status,debtor.curr_code,tax.amount as tax_amount FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor,".TB_PREF."trans_tax_details tax WHERE trans.debtor_no=debtor.debtor_no and trans.trans_no=tax.trans_no and trans.type=tax.trans_type and trans.type=10 and tax.trans_type=10 and debtor.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and tax.amount!=0 and debtor.business_type_id!=2 ";
	
		
	 $sql.=" UNION "; 
	
		
	 $vat_advance_act = get_company_pref('sales_vat_advance');
	
	 $sql.=" SELECT DISTINCT trans.*,debtor.tax_id,debtor.name,debtor.vat_status,debtor.curr_code,trans.ov_gst as tax_amount FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor WHERE trans.debtor_no=debtor.debtor_no and trans.type=12 and  debtor.curr_code=".db_escape($company_curr_code)."  and debtor.business_type_id!=2  and trans.ov_gst!=0 and trans.trans_no in (select gl.type_no from ".TB_PREF."gl_trans gl where gl.type=12 and gl.tran_date>='$from' and gl.tran_date<='$to' and gl.account =".db_escape($vat_advance_act).")"; 
	 
	  $sql.=" UNION "; 
	  
	 $sql.= "SELECT DISTINCT trans.*,debtor.tax_id,debtor.name,debtor.vat_status,debtor.curr_code,ttd.amount as tax_amount FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor,".TB_PREF."trans_tax_details ttd  WHERE trans.debtor_no=debtor.debtor_no and trans.type=11 and debtor.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.type= ttd.trans_type and trans.trans_no=ttd.trans_no and ttd.trans_type=11 and debtor.business_type_id!=2 and ttd.amount!=0";	
	
	
	 $sql.=" order by tran_date ";  
	 
	 
	
	
	return db_query($sql, "The transactions for could not be retrieved");
}



// Zero Rated Sales - Box 1(b)
function get_domestic_vat_zerorated_transactions($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$sql = "SELECT trans.*,debtor.tax_id,debtor.name,debtor.vat_status,debtor.curr_code FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor,".TB_PREF."cust_branch branch WHERE trans.debtor_no=debtor.debtor_no and trans.type=10 and debtor.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst=0 and trans.branch_code=branch.branch_code and branch.tax_group_id=4 and debtor.business_type_id!=2";
	
	return db_query($sql, "The transactions for could not be retrieved");
}



//  Exempt Supplies - Box 1(c)
function get_domestic_vat_exempt_transactions($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$sql = "SELECT trans.*,debtor.tax_id,debtor.name,debtor.vat_status,debtor.curr_code FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor,".TB_PREF."cust_branch branch WHERE trans.debtor_no=debtor.debtor_no and trans.type=10 and debtor.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst=0 and trans.branch_code=branch.branch_code and branch.tax_group_id=2 and debtor.business_type_id!=2";
	
	return db_query($sql, "The transactions for could not be retrieved");
}


//  Exports - Box 3(a)
function get_export_transactions($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$sql = "SELECT trans.*,debtor.tax_id,debtor.name,debtor.vat_status,debtor.curr_code FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor WHERE trans.debtor_no=debtor.debtor_no  and trans.type=10 and debtor.curr_code!=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst=0";
	
	return db_query($sql, "The transactions for could not be retrieved");
}

// Goods Imports - Box 4(b)

function get_import_transactions($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$sql = "SELECT distinct trans.*,supplier.gst_no,supplier.supp_name,supplier.vat_status,supplier.curr_code FROM ".TB_PREF."supp_trans trans,".TB_PREF."suppliers supplier WHERE trans.supplier_id=supplier.supplier_id and trans.type=20 and supplier.curr_code!=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.ov_gst=0";
		
	return db_query($sql, "The transactions for could not be retrieved");
}



// Output Adjustments - Box 5(b)
function get_domestic_vat_sr_transactions($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

   	$sql = "SELECT trans.*,ttd.amount as vat_amount,debtor.tax_id,debtor.name,debtor.vat_status,debtor.curr_code FROM ".TB_PREF."debtor_trans trans,".TB_PREF."debtors_master debtor,".TB_PREF."trans_tax_details ttd  WHERE trans.debtor_no=debtor.debtor_no and trans.type=11 and debtor.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and trans.type= ttd.trans_type and trans.trans_no=ttd.trans_no and ttd.trans_type=11 and debtor.business_type_id!=2 and ttd.amount!=0";
		
	return db_query($sql, "The transactions for could not be retrieved");
	
}


// Input Tax - Box 6(a)
function get_domestic_vat_pi_transactions($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$sql = "SELECT DISTINCT trans.trans_no,trans.reference,trans.tran_date,trans.tax_included,trans.ov_amount,trans.freight_cost,trans.additional_charges,trans.packing_charges,trans.other_charges,supplier.gst_no,supplier.supp_name,supplier.curr_code,tax.rate as tax_rate,tax.amount as tax_amount FROM ".TB_PREF."supp_trans trans,".TB_PREF."suppliers supplier,".TB_PREF."trans_tax_details tax WHERE trans.supplier_id=supplier.supplier_id and trans.trans_no=tax.trans_no and trans.type=tax.trans_type and trans.type=20 and tax.trans_type=20 and supplier.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' group by trans.trans_no";
		
	return db_query($sql, "The transactions for could not be retrieved");
}

//vat 5% on multiple purchase cash bill
 function get_input_vat_on_multiple_purch_cash_bill($from_date,$to_date)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$sql = "SELECT b.supp_bill_no,b.supp_bill_date,b.supp_vat_no,b.supp_name,sum(CASE WHEN b.is_tax_account=0 THEN b.amount ELSE 0 END) as taxable_amount,sum(CASE WHEN b.is_tax_account=1 THEN b.amount ELSE 0 END) as tax_amount,b.memo_ FROM ".TB_PREF."multiple_purch_cash_bill b, ".TB_PREF."bank_trans t WHERE  t.trans_no=b.trans_no AND t.type=1 AND t.is_purch_cash_bill=1  AND t.trans_date >='$from' AND t.trans_date <='$to' GROUP BY b.trans_no,b.supp_vat_no,b.supp_bill_no ORDER BY b.supp_bill_date";

	
	return db_query($sql, "The multiple purchase cash bill could not be retrieved");

} 


function get_domestic_vat_pi_comments($trans_no)
{
   	$sql = "SELECT memo_ FROM ".TB_PREF."comments  WHERE type=20 and id=".db_escape($trans_no);
	
	$result= db_query($sql, "The transactions for could not be retrieved");
    $row=db_fetch($result);
    return $row['memo_'];
  
}


// Input Tax - Box 6(d)
function get_domestic_vat_pr_transactions($from_date,$to_date,$company_curr_code)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$sql = "SELECT DISTINCT trans.*,supplier.gst_no,supplier.supp_name,supplier.curr_code,tax.rate as tax_rate,tax.amount as tax_amount FROM ".TB_PREF."supp_trans trans,".TB_PREF."suppliers supplier,".TB_PREF."trans_tax_details tax WHERE trans.supplier_id=supplier.supplier_id and trans.trans_no=tax.trans_no and trans.type=tax.trans_type and trans.type=21 and tax.trans_type=21 and supplier.curr_code=".db_escape($company_curr_code)." and trans.tran_date>='$from' and trans.tran_date<='$to' and tax.amount!=0 ";
	
	return db_query($sql, "The transactions for could not be retrieved");
}


//ravi for advance outstanding vat value_of_supply
// for 1(a)
function get_advance_outstanding_tax_value($trans_no,$from_date,$to_date)
{
	global $SysPrefs;

	$from = date2sql($from_date);
	$to = date2sql($to_date);

     $vat_advance_act = get_company_pref('sales_vat_advance');
	 	 
	 $sql = "SELECT round(sum(amount),3) as vat_amount FROM ".TB_PREF."gl_trans WHERE account=".db_escape($vat_advance_act)." and tran_date>='$from' and tran_date<='$to' and type_no=".db_escape($trans_no)."";
		
	$result= db_query($sql, "The transactions for could not be retrieved");
	
	if($outstanding_sales_advance=db_fetch($result))
	{
		return $outstanding_sales_advance['vat_amount'];
	}	
		
	return 0;
	
	
}
?>