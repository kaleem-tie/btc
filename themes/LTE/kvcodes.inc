<?php 


function Top_five_invoices($type=10, $options=null){
    if($options == 'Last Month'){
        $today1 = date('Y-m-d', strtotime('last day of previous month'));
        $begin1 = date('Y-m-d', strtotime('first day of previous month'));
    }elseif($options == 'This Month'){
        $begin1 = date("Y-m-d", strtotime("first day of this month"));
        $today1 = date("Y-m-d", strtotime("last day of this month"));
    }elseif($options =='Last Week'){
        $begin1 = date("Y-m-d", strtotime("last week monday"));
        $today1 = date("Y-m-d", strtotime("last week sunday"));
    }elseif($options == 'Last Quarter Year'){
        
    }else{
        $f_year = kv_get_current_fiscalyear();
        $begin1 = $f_year['begin'];
        $today1 = date('Y-m-d');
    }
    $today = date('Y-m-d');

    $sql_pre = "SET sql_mode='';";
    db_query($sql_pre, "Can't Set Mode");
    $sql ="
        SELECT 
        trans.type, 
        trans.trans_no, 
        trans.order_, 
        trans.reference,
        trans.tran_date, 
        trans.due_date, 
        debtor.name, 
        branch.br_name,
        debtor.curr_code,
        debtor.debtor_no,
        (trans.ov_amount + trans.ov_gst + trans.ov_freight 
            + trans.ov_freight_tax + trans.ov_discount) AS TotalAmount, @bal := @bal+(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount), trans.alloc AS Allocated,
        ((trans.type = 10)
            AND trans.due_date < '$today') AS OverDue ,
        Sum(line.quantity-line.qty_done) AS Outstanding,
        Sum(line.qty_done) AS HasChild
        FROM "
            .TB_PREF."debtor_trans as trans
            LEFT JOIN ".TB_PREF."debtor_trans_details as line
                ON trans.trans_no=line.debtor_trans_no AND trans.type=line.debtor_trans_type
            LEFT JOIN ".TB_PREF."voided as v
                ON trans.trans_no=v.id AND trans.type=v.type
            LEFT JOIN ".TB_PREF."cust_branch as branch ON trans.branch_code=branch.branch_code,"
            .TB_PREF."debtors_master as debtor
        WHERE debtor.debtor_no = trans.debtor_no
            AND trans.tran_date >= '$begin1'
            AND trans.tran_date <= '$today1'
            AND trans.branch_code = branch.branch_code
            AND ISNULL(v.date_) AND ";
        if($type== 10)
            $sql .="(trans.type = 10) GROUP BY trans.trans_no, trans.type ORDER BY trans.tran_date DESC LIMIT 5";
        else
            $sql .="( trans.type = 12 OR trans.type = 2 OR trans.type = 1) GROUP BY trans.trans_no, trans.type ORDER BY trans.tran_date DESC LIMIT 5";
    $result = db_query($sql, "Transactions could not be calculated");
    //print_r($result);
    $final= array();
    $total = 0;
    while($row = db_fetch_assoc($result)){
        //if ($row['ctype'] > 3){
        //  $total += $row['total'];
        //}
        $final[] = $row;
    }
    
    return $final;
}


function get_tax_reports($options=null){
    if($options == 'Last Month'){
        $today1 = date('Y-m-d', strtotime('last day of previous month'));
        $begin1 = date('Y-m-d', strtotime('first day of previous month'));
    }elseif($options == 'This Month'){
        $begin1 = date("Y-m-d", strtotime("first day of this month"));
        $today1 = date("Y-m-d", strtotime("last day of this month"));
    }elseif($options == 'Last Quarter Year'){
        
    }elseif($options =='Last Week'){
        $begin1 = date("Y-m-d", strtotime("last week monday"));
        $today1 = date("Y-m-d", strtotime("last week sunday"));
    }else{
        $f_year = kv_get_current_fiscalyear();
        $begin1 = $f_year['begin'];
        $today1 = date('Y-m-d');
    }
    
    $sql= "SELECT tt.name as taxname, taxrec.*, taxrec.amount*ex_rate AS amount,
                taxrec.net_amount*ex_rate AS net_amount,
                IF(ISNULL(supp.supp_name), debt.name, supp.supp_name) as name,
                branch.br_name
        FROM ".TB_PREF."trans_tax_details taxrec
        LEFT JOIN ".TB_PREF."tax_types tt
            ON taxrec.tax_type_id=tt.id
        LEFT JOIN ".TB_PREF."supp_trans strans
            ON taxrec.trans_no=strans.trans_no AND taxrec.trans_type=strans.type
        LEFT JOIN ".TB_PREF."suppliers as supp ON strans.supplier_id=supp.supplier_id
        LEFT JOIN ".TB_PREF."debtor_trans dtrans
            ON taxrec.trans_no=dtrans.trans_no AND taxrec.trans_type=dtrans.type
        LEFT JOIN ".TB_PREF."debtors_master as debt ON dtrans.debtor_no=debt.debtor_no
        LEFT JOIN ".TB_PREF."cust_branch as branch ON dtrans.branch_code=branch.branch_code
        WHERE (taxrec.amount <> 0 OR taxrec.net_amount <> 0)
            AND taxrec.trans_type <> 13
            AND taxrec.tran_date >= '$begin1'
            AND taxrec.tran_date <= '$today1'
        ORDER BY taxrec.trans_type, taxrec.tran_date, taxrec.trans_no, taxrec.ex_rate" ;
    $result = db_query($sql, "could not get Company Details");

    $res = kvgetTaxTypes();

    $taxes[0] = array('in'=>0, 'out'=>0, 'taxin'=>0, 'taxout'=>0);
    while ($tax=db_fetch($res))
        $taxes[$tax['id']] = array('in'=>0, 'out'=>0, 'taxin'=>0, 'taxout'=>0);
                
    $totalnet = $totaltax = 0.0;
    while($trans = db_fetch_assoc($result)){
        if (in_array($trans['trans_type'], array(11,20,0))) {
            $trans['net_amount'] *= -1;
            $trans['amount'] *= -1;
        }
    
        $tax_type = $trans['tax_type_id'];
        if ($trans['trans_type']==ST_JOURNAL && $trans['amount']<0) {
            $taxes[$tax_type]['taxin'] += $trans['amount'];
            $taxes[$tax_type]['in'] += $trans['net_amount'];
        }
        elseif ($trans['trans_type']==ST_JOURNAL && $trans['amount']>=0) {
            $taxes[$tax_type]['taxout'] += $trans['amount'];
            $taxes[$tax_type]['out'] += $trans['net_amount'];
        }
        elseif (in_array($trans['trans_type'], array(ST_BANKDEPOSIT,ST_SALESINVOICE,ST_CUSTCREDIT))) {
            $taxes[$tax_type]['taxout'] += $trans['amount'];
            $taxes[$tax_type]['out'] += $trans['net_amount'];
        } else {
            $taxes[$tax_type]['taxin'] += $trans['amount'];
            $taxes[$tax_type]['in'] += $trans['net_amount'];
        }
        $totalnet += $trans['net_amount'];
        $totaltax += $trans['amount'];
        $totalnet += $trans['net_amount'];
        $totaltax += $trans['amount'];  
    }
    $taxtotal = 0;
    $Taxesarray = array(); 
    foreach( $taxes as $id=>$sum){  
        $tax_array = array(); 
        if ($id){
            $tx = kvgetTaxInfo($id);
            $tax_array['name'] =  $tx['name'];
        } else {
            $tax_array['name'] = _('Exempt');
        }
        $tax_array['out'] = $sum['taxout'];
        $tax_array['in'] = $sum['taxin'];
        $tax_array['total'] = $sum['taxout']+$sum['taxin'];
        $taxtotal += $sum['taxout']+$sum['taxin'];
        $Taxesarray[] = $tax_array;
    }
    $Taxesarray['grandtotal'] = $taxtotal;
    return $Taxesarray; 
}

function kvgetTaxTypes() {
    $sql = "SELECT * FROM ".TB_PREF."tax_types ORDER BY id";
    return db_query($sql,"No transactions were returned");
}
function kvgetTaxInfo($id)
{
    $sql = "SELECT * FROM ".TB_PREF."tax_types WHERE id=$id";
    $result = db_query($sql,"No transactions were returned");
    return db_fetch($result);
}

function kv_get_expenses_chartlists() { 

    $sql_collect_ID = "SELECT id FROM ".TB_PREF."chart_types WHERE class_id IN (5,6,7)";
    $kv_class_ids  = array();
    $ret_id = db_query($sql_collect_ID, "Can't get empl person degrees");
    
    while($cont_id = db_fetch_row($ret_id)) 
        $kv_class_ids[] = $cont_id[0];  

    $sql = "SELECT chart.account_code, chart.account_name FROM ".TB_PREF."chart_master chart,".TB_PREF."chart_types type WHERE chart.account_type=type.id AND type.id IN (".implode(",", $kv_class_ids).")"; 
    $kv_expenses_list  = array();
    $ret = db_query($sql, "Can't get empl person degrees");
    
    while($cont = db_fetch_row($ret)) 
        $kv_expenses_list[] = $cont;
    //echo json_encode($cont);
    return $kv_expenses_list;       
}

function Expenses($options=null){

    if($options == 'Last Month'){
        $today1 = date('Y-m-d', strtotime('last day of previous month'));
        $begin1 = date('Y-m-d', strtotime('first day of previous month'));
    }elseif($options == 'This Month'){
        $begin1 = date("Y-m-d", strtotime("first day of this month"));
        $today1 = date("Y-m-d", strtotime("last day of this month"));
    }elseif($options == 'Last Quarter Year'){
        
    }elseif($options =='Last Week'){
        $begin1 = date("Y-m-d", strtotime("last week monday"));
        $today1 = date("Y-m-d", strtotime("last week sunday"));
    }else{
        $f_year = kv_get_current_fiscalyear();
        $begin1 = $f_year['begin'];
        $today1 = date('Y-m-d');
    }
    
    $charts_list = kv_get_expenses_chartlists();  
    $final= array();    
    foreach($charts_list as $single_char) {
        $sql = "SELECT SUM(IF(amount >= 0, amount, 0)) as debit, 
            SUM(IF(amount < 0, -amount, 0)) as credit, SUM(amount) as balance 
            FROM ".TB_PREF."gl_trans,".TB_PREF."chart_master,".TB_PREF."chart_types, ".TB_PREF."chart_class 
            WHERE ".TB_PREF."gl_trans.account=".TB_PREF."chart_master.account_code AND ".TB_PREF."chart_master.account_type=".TB_PREF."chart_types.id 
            AND ".TB_PREF."chart_types.class_id=".TB_PREF."chart_class.cid AND account='".$single_char[0]."' AND tran_date > IF(ctype>0 AND ctype<4, '0000-00-00', '".$begin1."') AND tran_date <= '".$today1."' "; 
        $result = db_query($sql, "could not get Company Details");
        
        while($row = db_fetch_assoc($result)){
            if($row['balance'] > 0){
                $row['code'] = $single_char[0];
                $row['name'] = $single_char[1];
                $final[] =  $row;
            }
        }       
    }   
    return $final;
}

function get_top_suppliers($options=null){
    if($options == 'Last Month'){
        $today1 = date('Y-m-d', strtotime('last day of previous month'));
        $begin1 = date('Y-m-d', strtotime('first day of previous month'));
    }elseif($options == 'This Month'){
        $begin1 = date("Y-m-d", strtotime("first day of this month"));
        $today1 = date("Y-m-d", strtotime("last day of this month"));
    }elseif($options == 'Last Quarter Year'){
        
    }elseif($options =='Last Week'){
        $begin1 = date("Y-m-d", strtotime("last week monday"));
        $today1 = date("Y-m-d", strtotime("last week sunday"));
    }else{
        $f_year = kv_get_current_fiscalyear();
        $begin1 = $f_year['begin'];
        $today1 = date('Y-m-d');
    }
    $sql ="SELECT SUM((trans.ov_amount + trans.ov_discount) * rate) AS total, s.supplier_id, s.supp_name FROM
        ".TB_PREF."supp_trans AS trans, ".TB_PREF."suppliers AS s WHERE trans.supplier_id=s.supplier_id
        AND (trans.type = 20 OR trans.type = 21)
        AND tran_date >= '$begin1' AND tran_date <= '$today1' GROUP by s.supplier_id ORDER BY total DESC, s.supplier_id 
        LIMIT 10";
    $result = db_query($sql, "could not get Company Details");

    $final= array();
    while($row = db_fetch_assoc($result)){
        $final[] = $row;
    }
    return $final;
}


function get_top_customers($options=null){
    if($options == 'Last Month'){
        $today1 = date('Y-m-d', strtotime('last day of previous month'));
        $begin1 = date('Y-m-d', strtotime('first day of previous month'));
    }elseif($options == 'This Month'){
        $begin1 = date("Y-m-d", strtotime("first day of this month"));
        $today1 = date("Y-m-d", strtotime("last day of this month"));
    }elseif($options == 'Last Quarter Year'){
        
    }elseif($options =='Last Week'){
        $begin1 = date("Y-m-d", strtotime("last week monday"));
        $today1 = date("Y-m-d", strtotime("last week sunday"));
    }else{
        $f_year = kv_get_current_fiscalyear();
        $begin1 = $f_year['begin'];
        $today1 = date('Y-m-d');
    }
    
    $sql ="SELECT SUM((ov_amount + ov_discount) * rate * IF(trans.type = 11, -1, 1)) AS total,d.debtor_no, d.name FROM
        ".TB_PREF."debtor_trans AS trans, ".TB_PREF."debtors_master AS d WHERE trans.debtor_no=d.debtor_no
        AND (trans.type = 10 OR trans.type = 11)
        AND tran_date >= '$begin1' AND tran_date <= '$today1' GROUP by d.debtor_no ORDER BY total DESC, d.debtor_no 
        LIMIT 10";
    $result = db_query($sql, "could not get Company Details");

    $final= array();
    while($row = db_fetch_assoc($result)){
        $final[] = $row;
    }

    return $final;
}

function Top_selling_items($options=null){

    if($options == 'Last Month'){
        $today1 = date('Y-m-d', strtotime('last day of previous month'));
        $begin1 = date('Y-m-d', strtotime('first day of previous month'));
    }elseif($options == 'This Month'){
        $begin1 = date("Y-m-d", strtotime("first day of this month"));
        $today1 = date("Y-m-d", strtotime("last day of this month"));
    }elseif($options == 'Last Quarter Year'){
        
    }elseif($options =='Last Week'){
        $begin1 = date("Y-m-d", strtotime("last week monday"));
        $today1 = date("Y-m-d", strtotime("last week sunday"));
    }else{
        $f_year = kv_get_current_fiscalyear();
        $begin1 = $f_year['begin'];
        $today1 = date('Y-m-d');
    }
        
    $sql = "SELECT SUM((trans.unit_price * trans.quantity) * d.rate) AS total, s.stock_id, s.description, 
            SUM(trans.quantity) AS qty, SUM((s.material_cost + s.overhead_cost + s.labour_cost) * trans.quantity) AS costs FROM
            ".TB_PREF."debtor_trans_details AS trans, ".TB_PREF."stock_master AS s, ".TB_PREF."debtor_trans AS d 
            WHERE trans.stock_id=s.stock_id AND trans.debtor_trans_type=d.type AND trans.debtor_trans_no=d.trans_no
            AND (d.type = 10 OR d.type = 11) AND tran_date >= '$begin1' AND tran_date <= '$today1' GROUP by s.stock_id ORDER BY total DESC, s.stock_id 
        LIMIT 10";
    $result = db_query($sql) or die(_('Error getting weekly sales data'));

     $rows = array();
    while($r = db_fetch_assoc($result)) {
        $rows[] = $r; 
    }

    return $rows;
}


function kv_get_current_balance(){

    $f_year = kv_get_current_fiscalyear();
    
    $today1 = date('Y-m-d');
    
    $sql_item_count = "SELECT SUM(amount) FROM `".TB_PREF."bank_trans` WHERE trans_date >= '".$f_year['begin']."' AND trans_date <= '$today1'" ;

    $sql_item_count_result = db_query($sql_item_count, "could not get sales type");

    $item_count= db_fetch_row($sql_item_count_result);

    return round($item_count[0], 3)+kv_get_opening_balance();
}

//  new function for showing the current balance in lte theme dashboard   ## Ramesh
function  kv_get_current_balance_of_default_bank_account()
{
$f_year = kv_get_current_fiscalyear();
    
    $today1 = date('Y-m-d');
    
    $sql_item_count = "SELECT SUM(bt.amount),ba.bank_account_name  FROM `".TB_PREF."bank_trans` bt,".TB_PREF."bank_accounts ba WHERE  bt.bank_act = ba.id  AND ba.dflt_curr_act='1' AND bt.trans_date <= '$today1'" ;

    $sql_item_count_result = db_query($sql_item_count, "could not get sales type");

    $item_count= db_fetch_row($sql_item_count_result);

    return $item_count;
    // return round($item_count[0], 3);
}

function kv_get_opening_balance(){

    $f_year = kv_get_current_fiscalyear();
    
    $sql_item_count = "SELECT SUM(amount) FROM `".TB_PREF."bank_trans` WHERE trans_date < '".$f_year['begin']."'" ;

    $sql_item_count_result = db_query($sql_item_count, "could not get sales type");

    $item_count= db_fetch_row($sql_item_count_result);

    return round($item_count[0], 3);
}

function kv_get_current_fiscalyear()
{
    $year = get_company_pref('f_year');

    $sql = "SELECT * FROM ".TB_PREF."fiscal_year WHERE id=".db_escape($year);

    $result = db_query($sql, "could not get current fiscal year");

    return db_fetch($result);
}

function class_balances($options=null){
    if($options == 'Last Month'){
        $today1 = date('Y-m-d', strtotime('last day of previous month'));
        $begin1 = date('Y-m-d', strtotime('first day of previous month'));
    }elseif($options == 'This Month'){
        $begin1 = date("Y-m-d", strtotime("first day of this month"));
        $today1 = date("Y-m-d", strtotime("last day of this month"));
    }elseif($options == 'Last Quarter Year'){
        
    }elseif($options =='Last Week'){
        $begin1 = date("Y-m-d", strtotime("last week monday"));
        $today1 = date("Y-m-d", strtotime("last week sunday"));
    }else{
        $f_year = kv_get_current_fiscalyear();
        $begin1 = $f_year['begin'];
        $today1 = date('Y-m-d');
    }

    $sql = "SELECT SUM(amount) AS total, c.class_name, c.ctype FROM
        ".TB_PREF."gl_trans,".TB_PREF."chart_master AS a, ".TB_PREF."chart_types AS t, 
        ".TB_PREF."chart_class AS c WHERE
        account = a.account_code AND a.account_type = t.id AND t.class_id = c.cid
        AND IF(c.ctype > 3, tran_date >= '$begin1', tran_date >= '0000-00-00') 
        AND tran_date <= '$today1' GROUP BY c.cid ORDER BY c.cid"; 
    $result = db_query($sql, "Transactions could not be calculated");
    
    $final= array();
    $total = 0;
    while($row = db_fetch_assoc($result)){
        if ($row['ctype'] > 3){
            $total += $row['total'];
        }
        $final[] = $row;
    }
    $final[] = array( 'class_name' => 'Calculated Return', 'total' => $total); 
    return $final;
}


function kv_calculate_next_invoice($myrow){
    if ($myrow["last_sent"] == '0000-00-00')
        $next = sql2date($myrow["begin"]);
    else
        $next = sql2date($myrow["last_sent"]);
    $next = add_months($next, $myrow['monthly']);
    $next = add_days($next, $myrow['days']);
    return add_days($next,-1);
}


if(!function_exists('generate_timezone_list')){
    function generate_timezone_list() {
        static $regions = array(
            DateTimeZone::AFRICA,
            DateTimeZone::AMERICA,
            DateTimeZone::ANTARCTICA,
            DateTimeZone::ASIA,
            DateTimeZone::ATLANTIC,
            DateTimeZone::AUSTRALIA,
            DateTimeZone::EUROPE,
            DateTimeZone::INDIAN,
            DateTimeZone::PACIFIC,
        );

        $timezones = array();
        foreach( $regions as $region )  {
            $timezones = array_merge( $timezones, DateTimeZone::listIdentifiers( $region ) );
        }

        $timezone_offsets = array();
        foreach( $timezones as $timezone ) {
            $tz = new DateTimeZone($timezone);
            $timezone_offsets[$timezone] = $tz->getOffset(new DateTime);
        }

        // sort timezone by offset
        asort($timezone_offsets);

        $timezone_list = array();
        foreach( $timezone_offsets as $timezone => $offset )  {
            $offset_prefix = $offset < 0 ? '-' : '+';
            $offset_formatted = gmdate( 'H:i', abs($offset) );
            $pretty_offset = "UTC${offset_prefix}${offset_formatted}";
            $timezone_list[$timezone] = "(${pretty_offset}) $timezone";
        }
        return $timezone_list;
    }
}

if(!function_exists('kv_image_row')){
    function kv_image_row($label, $name, $id = "")  {
    	echo "<tr><td class='label'>$label</td>";
    	if ($id != "")
    		$id = "id='$id'";
    	label_cells(null, "<input type='file' name='$name'  accept='image/*'' $id >");
    	echo "</tr>\n";
    }
}
if(!function_exists('create_tbl_option')){
    function create_tbl_option(){
		if(db_num_rows(db_query("SHOW TABLES LIKE 'lte_theme_option'", "Table Checking" ))==1) { } 
		else {
			$sql = "CREATE TABLE IF NOT EXISTS `lte_theme_option` (
    			`id` int(10) NOT NULL AUTO_INCREMENT,
      			`option_name` varchar(60) NOT NULL,
      			`option_value` varchar(60) NOT NULL,
      			PRIMARY KEY (`id`)
				) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;"; 
			db_query($sql, 'Basic Theme Table has not Created!');
		}
	}
}
if(!function_exists('kv_get_option')){
	function kv_get_option($option_name){
		if(db_num_rows(db_query("SHOW TABLES LIKE 'lte_theme_option'", "Table Checking" ))==1) {
			$sql = "SELECT option_value FROM lte_theme_option WHERE option_name=".db_escape($option_name)." LIMIT 1";
			$result = db_query($sql, "could not get sales type");
			$row = db_fetch_row($result);
			if($row[0])
				return $row[0];
			else
					return 'false';
		} else 
		return 'false';
	}	
}

if(!function_exists('LteGetAll')){
    function LteGetAll(){
        $sql = "SELECT option_name, option_value FROM lte_theme_option";
        $res = db_query($sql, "Can't get theme options");
        $final = array();
        while($row = db_fetch($res))
            $final[$row['option_name']] = $row['option_value'];
        
        return (empty($final) ? false : $final);
    }
}



if(!function_exists('kv_update_option')){
    function kv_update_option($option_name, $option_value){
    	if(db_num_rows(db_query("SHOW TABLES LIKE 'lte_theme_option'", "Table Checking" ))==1) {
	    	if($option_name == 'hide_version'){
	    		if(check_empty_result("SELECT * FROM lte_theme_option WHERE option_name='hide_version'")){
	              	$sql = "UPDATE lte_theme_option SET	option_value=".db_escape($option_value)." WHERE option_name=".db_escape($option_name);
	    			db_query($sql, "could not update option");
	    		}else{
	    			kv_add_option($option_name, $option_value);
	    		}
	    	}elseif($option_name == 'hide_help_link' ){
	    		if(check_empty_result("SELECT * FROM lte_theme_option WHERE option_name='hide_help_link'")){
	              	$sql = "UPDATE lte_theme_option SET	option_value=".db_escape($option_value)." WHERE option_name=".db_escape($option_name);
	    			db_query($sql, "could not update option");
	    		}else{
	    			kv_add_option($option_name, $option_value);
	    		}
	    	}elseif($option_name == 'hide_dashboard' ){
	    		if(check_empty_result("SELECT * FROM lte_theme_option WHERE option_name='hide_dashboard'")){
	              	$sql = "UPDATE lte_theme_option SET	option_value=".db_escape($option_value)." WHERE option_name=".db_escape($option_name);
	    			db_query($sql, "could not update option");
	    		}else{
	    			kv_add_option($option_name, $option_value);
	    		}
	    	}else{
	    		if(kv_get_option($option_name) != 'false'){
	    			$sql = "UPDATE lte_theme_option SET option_value=".db_escape($option_value)." WHERE option_name=".db_escape($option_name);
	    			db_query($sql, "could not update option");
	    		}else{
	    			kv_add_option($option_name, $option_value);
	    		}
	    	} 
    	}   		
    }
}

if(!function_exists('kv_add_option')){
    function kv_add_option($option_name, $option_value){
    	if(db_num_rows(db_query("SHOW TABLES LIKE 'lte_theme_option'", "Table Checking" ))==1) {
    		$sql = "INSERT INTO lte_theme_option (  option_name, option_value) VALUES (".db_escape($option_name).", ".db_escape($option_value).")";
    		db_query($sql, "could not insert Option");
    	}
    }
}
if(!function_exists('kv_get_current_theme')){
	function kv_get_current_theme(){
		return kv_get_option('theme');
	}
}


//Supplier Over Due Invoices
function kv_supplier_trans(){
    $today = Today();
    $today = date2sql($today);
    $sql = "SELECT trans.trans_no, trans.reference, trans.tran_date, trans.due_date, s.supplier_id, 
        s.supp_name, s.curr_code,
        (trans.ov_amount + trans.ov_gst + trans.ov_discount) AS total,  
        (trans.ov_amount + trans.ov_gst + trans.ov_discount - trans.alloc) AS remainder,
        DATEDIFF('$today', trans.due_date) AS days  
        FROM ".TB_PREF."supp_trans as trans, ".TB_PREF."suppliers as s 
        WHERE s.supplier_id = trans.supplier_id
            AND trans.type = ".ST_SUPPINVOICE." AND (ABS(trans.ov_amount + trans.ov_gst + 
                trans.ov_discount) - trans.alloc) > ".FLOAT_COMP_DELTA."
            AND DATEDIFF('$today', trans.due_date) > 0 ORDER BY days DESC";
    $result = db_query($sql);
    //$th = array("#", _("Ref."), _("Date"), _("Due Date"), _("Supplier"), _("Currency"), _("Total"), _("Remainder"),   _("Days"));
    //start_table(TABLESTYLE);
    //table_header($th);
    echo '<table class="table table-hover">
            <thead class="text-warning">
                <tr><th>Ref</th>                    
                    <th>Due Date</th>
                    <th>Supplier</th>
                    <th>Currency</th>
                    <th style="text-align:right">Total</th>
                    <th>Days</th>
                </tr></thead>';
    $k = 0; //row colour counter
    while ($myrow = db_fetch($result)){
        alt_table_row_color($k);
        //label_cell(get_trans_view_str(ST_SUPPINVOICE, $myrow["trans_no"]));
        label_cell($myrow['reference']);
        //label_cell(sql2date($myrow['tran_date']));
        label_cell(sql2date($myrow['due_date']));
        $name = $myrow["supplier_id"]." ".$myrow["supp_name"];
        label_cell($name);
        label_cell($myrow['curr_code']);
        amount_cell($myrow['total']);
        //amount_cell($myrow['remainder']);
        label_cell($myrow['days'], "align='right'");
        end_row();
    }
    end_table(1);
}
//Top 10 Selling items
function kv_stock_top( $limit=10, $width="100", $type=0, &$pg=null){    
    $today =Today();
    if ($type == 2)
        $sec = 'SA_ASSETSANALYTIC';
    elseif ($type == 1)
        $sec = 'SA_WORKORDERANALYTIC';
    else
        $sec = 'SA_ITEMSTRANSVIEW';
    $begin = begin_fiscalyear();
    $begin1 = date2sql($begin);
    $today1 = date2sql($today);
    if ($type == 0){
        $sql = "SELECT SUM((trans.unit_price * trans.quantity) * d.rate) AS total, s.stock_id, s.description, 
            SUM(trans.quantity) AS qty, SUM((s.material_cost + s.overhead_cost + s.labour_cost) * trans.quantity) AS costs FROM
            ".TB_PREF."debtor_trans_details AS trans, ".TB_PREF."stock_master AS s, ".TB_PREF."debtor_trans AS d 
            WHERE trans.stock_id=s.stock_id AND trans.debtor_trans_type=d.type AND trans.debtor_trans_no=d.trans_no
            AND (d.type = ".ST_SALESINVOICE." OR d.type = ".ST_CUSTCREDIT.") ";
    }else{
        $sql = "SELECT SUM(m.qty * (s.material_cost + s.labour_cost + s.overhead_cost)) AS total, s.stock_id, s.description, 
            SUM(qty) AS qty FROM ".TB_PREF."stock_master AS s, ".TB_PREF."stock_moves AS m 
            WHERE s.stock_id=m.stock_id ";
        if ($type == 1)
            $sql .= "AND s.mb_flag='M' ";
        elseif ($type == 2) 
            $sql .= "AND s.mb_flag='F' ";
    }
    if ($type != 2)
        $sql .= "AND tran_date >= '$begin1' ";
    $sql .= "AND tran_date <= '$today1' GROUP by s.stock_id ORDER BY total DESC, s.stock_id 
        LIMIT $limit";
    $result = db_query($sql);
     
    if ($type == 0) 
        $th = array(_("Item"), _("Sales"), _("Costs"), _("Quantity"));
    else    
        $th = array(_("Item"), _("Amount"), _("Quantity"));
    //start_table(TABLESTYLE, "width='$width%'");
    echo '<table class="table table-hover">
            <thead class="text-warning">
                <tr><th>Item</th> <th style="text-align:right">Sales</th> <th style="text-align:right">Costs</th> <th style="text-align:right">Quantity</th>
                </tr> </thead>';

    //table_header($th);
    
    $k = 0; //row colour counter
    $i = 0;
    while ($myrow = db_fetch($result)){
        alt_table_row_color($k);
        $name = $myrow["description"];
        label_cell($name);
        amount_cell($myrow['total']);
        if ($type == 0)
            amount_cell($myrow['costs']);
        qty_cell($myrow['qty']);
        if ($pg != NULL)
        {
            $pg->x[$i] = $name; 
            $pg->y[$i] = $myrow['total'];
            if ($type == 0)
                $pg->z[$i] = $myrow['costs'];
        }   
        $i++;
        end_row();
    }
    end_table(1);
    
}

// Average Daily Sales
function kv_weekly_sales(){
    $ageSales = 'SELECT week_day, dayname, (gross_output/weeks) gross, weeks '
        .'FROM (SELECT weekday(trans_date) week_day,'
                .'date_format(trans_date, \'%W\') dayname,'
                .'sum(gross_output) gross_output,'
                .'count(*) weeks '
            .'FROM (SELECT dt.tran_date trans_date,'
                    .'sum((dt.ov_amount+dt.ov_gst)*dt.rate*IF(dt.type=10,1,-1)) gross_output '
                    .'FROM '.TB_PREF.'debtor_trans dt where dt.type in (10,11) '
                .'GROUP BY dt.tran_date '
                .') days  '
                .'GROUP BY weekday(trans_date), date_format(trans_date, \'%W\') '
            .') items ORDER BY week_day ' ; 
            
             
            
           // display_error($ageSales);
          
            $result = db_query($ageSales);
           
        //echo  _("Bank Account Balances");//display_title($title); ?>
        <table class="table table-hover">
            <thead class="text-warning">
                <tr>
                    <th>Day</th>
                    <th style="text-align:right">Gross</th>
                    <th style="text-align:right">Weeks</th>                 
                </tr></thead><?php  
                    while ($myrow = db_fetch($result))  {                           
                        label_cell($myrow["dayname"]);
                        amount_cell($myrow['gross']);
                        label_cell($myrow["weeks"],"align='right'");
                        end_row();
                    }
        end_table(1);
}


// Average Daily Sales
function kv_Daily_sales(){
    $ageSales = 'SELECT * FROM (SELECT max(trans_date) `Week End`, '
            .'concat(cast(case when weekofyear(trans_date) = 1 and month(trans_date)=12 then year(trans_date) + 1 else year(trans_date) end as char),cast(weekofyear(trans_date) as char)) `Week No`, '
            .'sum(case when weekday(trans_date)=0 then gross_output else 0 end) Monday, '
            .'sum(case when weekday(trans_date)=1 then gross_output else 0 end) Tuesday, '
            .'sum(case when weekday(trans_date)=2 then gross_output else 0 end) Wednesday, '
            .'sum(case when weekday(trans_date)=3 then gross_output else 0 end) Thursday, '
            .'sum(case when weekday(trans_date)=4 then gross_output else 0 end) Friday, '
            .'sum(case when weekday(trans_date)=5 then gross_output else 0 end) Saturday, '
            .'sum(case when weekday(trans_date)=6 then gross_output else 0 end) Sunday '
            .'FROM (SELECT dt.tran_date trans_date, '
                .'sum((dt.ov_amount+dt.ov_gst)*dt.rate*IF(dt.type=10,1,-1)) gross_output  '
            .'FROM '.TB_PREF.'debtor_trans dt '
            .'INNER JOIN '.TB_PREF.'debtors_master dm '
                .'ON dt.debtor_no = dm.debtor_no where dt.type in (10,11) '
            .' GROUP BY dt.tran_date  '
            .') days '
            .'GROUP BY concat(cast(case when weekofyear(trans_date) = 1 and month(trans_date)=12 then year(trans_date) + 1 else year(trans_date) end as char),cast(weekofyear(trans_date) as char)) '
            .'ORDER BY max(trans_date) desc '
            .' limit 10'
            .") weeks ORDER BY `Week End`"; 
            
            
          
            $result = db_query($ageSales);
           
      //echo  _("Bank Account Balances");//display_title($title); ?>
        <table class="table table-hover">
            <thead class="text-warning">
                <tr><th>Week End</th>
                    <th style="text-align:right">Monday</th>
                    <th style="text-align:right">Tuesday</th>
                    <th style="text-align:right">Wednesday</th>
                    <th style="text-align:right">Thursday</th>
                    <th style="text-align:right">Friday</th> 
                    <th style="text-align:right">Saturday</th> 
                    <th style="text-align:right">Sunday</th>
                    <th style="text-align:right">Week Sale</th>
                </tr></thead><?php  
                    while ($myrow = db_fetch($result))  {                           
                        label_cell(sql2date($myrow["Week End"]));
                        amount_cell($myrow['Monday']);
                        amount_cell($myrow['Tuesday']);
                        amount_cell($myrow['Wednesday']);
                        amount_cell($myrow["Thursday"]);
                        amount_cell($myrow['Friday']);
                        amount_cell($myrow['Saturday']);
                        amount_cell($myrow["Sunday"]);
                         amount_cell($myrow['Monday']+$myrow['Tuesday']+$myrow['Wednesday']+$myrow["Thursday"]+$myrow['Friday']+$myrow['Saturday']+$myrow["Sunday"]);
                        end_row();
                    }
        end_table(1);
}


// Class Balances
function kv_gl_top( &$pg=null){
    $today = Today();
    $begin = begin_fiscalyear();
    $begin1 = date2sql($begin);
    $today1 = date2sql($today);
    $sql = "SELECT SUM(amount) AS total, c.class_name, c.ctype FROM
        ".TB_PREF."gl_trans,".TB_PREF."chart_master AS a, ".TB_PREF."chart_types AS t, 
        ".TB_PREF."chart_class AS c WHERE
        account = a.account_code AND a.account_type = t.id AND t.class_id = c.cid
        AND IF(c.ctype > 3, tran_date >= '$begin1', tran_date >= '0000-00-00') 
        AND tran_date <= '$today1' GROUP BY c.cid ORDER BY c.cid"; 
    $result = db_query($sql, "Transactions could not be calculated"); ?>
    
    <table class="table table-hover">
    <?php 
    $i = 0;
    $total = 0;
    $k = 0; 
    while ($myrow = db_fetch($result)){

        if ($myrow['ctype'] > 3){
            $total += $myrow['total'];
            $myrow['total'] = -$myrow['total'];
            if ($pg != null){
                $pg->x[$i] = $myrow['class_name']; 
                $pg->y[$i] = abs($myrow['total']);
            }   
            $i++;
        }   
         alt_table_row_color($k);
        label_cells($myrow['class_name'], number_format2($myrow['total'], user_price_dec()), 
            " style='font-weight:bold;'", "style='font-weight:bold;' align=right");
            end_row();
    }
    $calculated = _("Calculated Return");
    label_row("&nbsp;", "");
    label_row($calculated, number_format2(-$total, user_price_dec()), 
        "class='label' style='font-weight:bold;'", "style='font-weight:bold;' align=right");
    if ($pg != null){
        $pg->x[$i] = $calculated; 
        $pg->y[$i] = -$total;
    }
    end_table(1);
    //return $title;
}

//Bank Account balances Table
function kv_bank_balance(){
        $today = Today(); 
        $today = date2sql($today);
        $sql = "SELECT bank_act, bank_account_name, bank_curr_code, SUM(amount) balance FROM ".TB_PREF."bank_trans bt 
                INNER JOIN ".TB_PREF."bank_accounts ba ON bt.bank_act = ba.id    WHERE trans_date <= '$today'  AND inactive <> 1    GROUP BY bank_act, bank_account_name    ORDER BY bank_account_name";
        $result = db_query($sql);
        //echo  _("Bank Account Balances");//display_title($title); ?>
        <table class="table table-hover">
            <thead class="text-warning">
                <tr><th>Account</th>
                    <th>Currency</th>
                    <th style="text-align: right;">Balance</th>
                </tr></thead><?php  $k = 0; //row colour counter
                    while ($myrow = db_fetch($result))  {
                            alt_table_row_color($k);
                            label_cell(viewer_link($myrow["bank_account_name"], 'gl/inquiry/bank_inquiry.php?bank_account='.$myrow["bank_act"]));
                            label_cell($myrow["bank_curr_code"]);
                            amount_cell($myrow['balance']);
                            end_row();
                        }
        end_table(1);
    } 
function kv_customer_trans() {
    $today = Today(); 
    $today = date2sql($today);

    $sql = "SELECT trans.trans_no, trans.reference, trans.tran_date, trans.due_date, debtor.debtor_no, 
        debtor.name, branch.br_name, debtor.curr_code,
        (trans.ov_amount + trans.ov_gst + trans.ov_freight 
            + trans.ov_freight_tax + trans.ov_discount) AS total,  
        (trans.ov_amount + trans.ov_gst + trans.ov_freight 
            + trans.ov_freight_tax + trans.ov_discount - trans.alloc) AS remainder,
        DATEDIFF('$today', trans.due_date) AS days  
        FROM ".TB_PREF."debtor_trans as trans, ".TB_PREF."debtors_master as debtor, 
            ".TB_PREF."cust_branch as branch
        WHERE debtor.debtor_no = trans.debtor_no AND trans.branch_code = branch.branch_code
            AND trans.type = ".ST_SALESINVOICE." AND (trans.ov_amount + trans.ov_gst + trans.ov_freight 
            + trans.ov_freight_tax + trans.ov_discount - trans.alloc) > ".FLOAT_COMP_DELTA." 
            AND DATEDIFF('$today', trans.due_date) > 0 ORDER BY days DESC";
    $result = db_query($sql);   ?>
    
    <table class="table table-hover">
            <thead class="text-warning">
                <tr> <th>Ref</th>
                    <th>Due Date</th>
                    <th>Customer</th>                   
                    <th>Currency</th>
                    <th style="text-align:right">Total</th>                  
                    <th>Days</th>
                </tr></thead>
    <?php $k = 0; //row colour counter
    while ($myrow = db_fetch($result)){
        alt_table_row_color($k);
       // label_cell(get_trans_view_str(ST_SALESINVOICE, $myrow["trans_no"]));
        label_cell($myrow['reference']);
       // label_cell(sql2date($myrow['tran_date']));
        label_cell(sql2date($myrow['due_date']));
        $name = $myrow["debtor_no"]." ".$myrow["name"];
        label_cell($name);
      //  label_cell($myrow['br_name']);
        label_cell($myrow['curr_code']);
        amount_cell($myrow['total']);
       // amount_cell($myrow['remainder']);
        label_cell($myrow['days'], "align='right'");
        end_row();
    }
    end_table(1);
}

//Over due Recurrent Invoice
function kv_get_recurrent_invoices($date=null){
    // last_sent stores end date of last generated invoice _plus_ 1 (or is empty)
    if ($date) {
        $date = date2sql($date);
        // we can issue invoice after or at last day of covered period
        $sql = "SELECT *, DATE_ADD(DATE_ADD(IF(`last_sent`='0000-00-00', `begin`, `last_sent`), INTERVAL `monthly` MONTH), INTERVAL `days` DAY) <= '$date'
            AND last_sent < DATE_ADD(`end`, INTERVAL 1 DAY) as overdue";
    } else
        $sql = "SELECT * ";

    $sql .= " FROM ".TB_PREF."recurrent_invoices ORDER BY description, group_no, debtor_no";

    return db_query($sql,"could not get recurrent invoices");
}

function kv_customer_recurrent_invoices(){
    $today = Today();
    $result = kv_get_recurrent_invoices($today);

    echo '<table class="table table-hover">
            <thead class="text-warning">
                <tr><th>Description</th>
                    <th>Template No</th>
                    <th>Customer</th>
                    <th>Branch/Group</th>
                    <th>Next invoice</th>
                </tr></thead>'; 
    $k = 0;
    while ($myrow = db_fetch($result)) {
        if (!$myrow['overdue'])
            continue;
        alt_table_row_color($k);

        label_cell($myrow["description"]);
        label_cell(get_customer_trans_view_str(ST_SALESORDER, $myrow["order_no"]));
        if ($myrow["debtor_no"] == 0){
            label_cell("");

            label_cell(get_sales_group_name($myrow["group_no"]));
        }   else {
            label_cell(get_customer_name($myrow["debtor_no"]));
            label_cell(get_branch_name($myrow['group_no']));
        }
        label_cell(kv_calculate_next_invoice($myrow),  "align='center'");
        end_row();
    }
    end_table(1);
}

//------------------------------------------------------------
function LTE_color_schemes($label, $name, $selected_id=null, $submit_on_change=false) {
    $hrm_empl_ref_type = array(
        'black' => _('Default'),  
        'blue' => _('Blue'),
        'green' => _('Green'),
        'purple' => _('Purple'),
        'yellow' => _('Yellow')  
    );
        
    echo "<tr><td class='label'>$label</td><td>";
    $options = array(
        'select_submit'=> $submit_on_change
    );
    echo array_selector($name, $selected_id, $hrm_empl_ref_type, $options);
    echo "</td></tr>\n";
}

function most_stagnant_items($today, $limit=10, $width="33", $type=0) {
   
    $begin = begin_fiscalyear();
    $begin1 = date2sql($begin);
    $today1 = date2sql($today);
   
    $sql = "SELECT stock_id,SUM(qty) as tot_qty,st.tran_date as trans_date  FROM ".TB_PREF."stock_moves st
   	LEFT JOIN ".TB_PREF."voided v ON st.type=v.type AND st.trans_no=v.id
    WHERE ISNULL(v.id) AND st.type=".ST_CUSTDELIVERY." AND st.tran_date >= '$begin1' AND st.tran_date <= '$today1' group by st.stock_id ORDER BY st.tran_date asc limit $limit";
    $result = db_query($sql, "QOH calculation failed");
	$return = array();
    while ($myrow = db_fetch($result))  {		
		$qoh_end = get_qoh_on_date($myrow['stock_id'], '', sql2date($today1));      
        $name = $myrow["stock_id"];
        $fet=get_item($name);
        if($name!=$fet['stock_id'])
			continue;
        $qty=abs($myrow['tot_qty']);
		$return[] = array($myrow["stock_id"], $fet["description"], $qoh_end, $qty, $myrow['trans_date']); 
    }
    
    return $return;
}


//Outstanding & Upcoming Item Deliveries
function kv_outstanding_upcoming_item_deliveries() {

    $today = Today(); 
    $today = date2sql($today);
  $sql = "SELECT 
			sorder.order_no,
			sorder.reference,
			sorder.delivery_date,
			sorder.debtor_no,
			debtor.name,
			sorder.ord_date,
			debtor.curr_code,
			line.stk_code,
			line.description,
			line.qty_sent AS TotDelivered,
			line.quantity AS TotQuantity,
			sorder.total,
			sorder.trans_type,
			DATEDIFF(sorder.delivery_date,'$today') AS days,
			line.id as order_line_id,
			line.planned_status,
			sorder.delivery_time
		FROM ".TB_PREF."sales_orders as sorder,
		    ".TB_PREF."sales_order_details as line, 
			".TB_PREF."debtors_master as debtor
			WHERE sorder.order_no = line.order_no
			AND sorder.trans_type = line.trans_type
			AND sorder.trans_type = 30
			AND sorder.debtor_no = debtor.debtor_no
			AND line.quantity-line.qty_sent>0
			AND sorder.reference!='auto'
			AND sorder.cancel_status=0
			ORDER BY days DESC";
			//display_error($sql);
    $result = db_query($sql);   ?>
    
    <table class="table table-hover">
            <thead class="text-warning">
                <tr><th>Ref</th>
                    <th>Actual Delivery Date</th>
					<th>Actual Delivery Time</th>
                    <th>Customer</th>                   
                    <th>Item Name</th>
                    <th>Quantity</th>                  
                    <th>Days</th>
					<th>Planned Delivery Date</th>
					<th>Planned Delivery Time</th>
					<th>Action</th>
                </tr></thead>
    <?php $k = 0; //row colour counter
	
	global $delivery_times;
	
    while ($myrow = db_fetch($result)){
        alt_table_row_color($k);
		
        label_cell($myrow['reference']);
        label_cell(sql2date($myrow['delivery_date']));
		label_cell($delivery_times[$myrow['delivery_time']],'align=center');
		
        $name = $myrow["debtor_no"]." ".$myrow["name"];
        label_cell($name);
        label_cell($myrow['description']);
		$qdec = get_qty_dec($myrow['stk_code']);
	    qty_cell($myrow['TotQuantity'], false, $qdec);
        label_cell($myrow['days'], "align='right'");
	 	
		if($myrow['planned_status']==0){
		label_cell(sql2date($myrow['delivery_date']),'align=center');
		
		if($myrow['delivery_time']!=0){
		label_cell($delivery_times[$myrow['delivery_time']],'align=center');
		}
		else{
		label_cell("");
		}
		
	    label_cell(viewer_link(_("Reschedule"), 'sales/inquiry/sales_delivery_plan.php?planned_status=1&
		order_no='.$myrow["order_no"].'&order_line_id='.$myrow["order_line_id"].'&
		planned_from_date='.$myrow["delivery_date"].'&
		planned_from_time='.$myrow["delivery_time"]));
		
		}
		else{
		$planned_date = get_sales_delivery_planned_date($myrow["order_no"],
		$myrow["order_line_id"]);
		label_cell(sql2date($planned_date),'align=center');
		
		$planned_time = get_sales_delivery_planned_time($myrow["order_no"],
		$myrow["order_line_id"]);
		
		if($planned_time!=0){
		label_cell($delivery_times[$planned_time],'align=center');
		}
		else{
		label_cell("");
		}
		
		label_cell(viewer_link(_("Reschedule"), 'sales/inquiry/sales_delivery_plan.php?planned_status=1&order_no='.$myrow["order_no"].'&order_line_id='.$myrow["order_line_id"].'&
		planned_from_date='.$planned_date.'&planned_from_time='.$planned_time));
		
		}
        end_row();
    }
    end_table(1);
}

function get_sales_delivery_planned_date($order_no,$order_line_id)
{
	$sql = "SELECT planned_date FROM ".TB_PREF."sales_delivery_plan 
			WHERE order_no = ".db_escape($order_no)."
			AND order_line_id = ".db_escape($order_line_id)."";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
	
}	


function get_sales_delivery_planned_time($order_no,$order_line_id)
{
	$sql = "SELECT planned_delivery_time FROM ".TB_PREF."sales_delivery_plan 
			WHERE order_no = ".db_escape($order_no)."
			AND order_line_id = ".db_escape($order_line_id)." and has_child=0";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
}

//---------------------------------------------


function purchase_order_pending_approvals(){

   $company_record = get_company_prefs();
    $po_auth_req_amount = str_replace(',', '', $company_record["po_auth_req_amount"]);
	  
	$sql = "SELECT 
		porder.order_no, 
		porder.reference,
		supplier.supp_name, 
		location.location_name,
		porder.requisition_no, 
		porder.ord_date,
		supplier.curr_code,	 Sum((line.unit_price*line.quantity_ordered)-((line.unit_price*line.quantity_ordered)*(line.discount_percent/100))) AS OrderValue,
		Sum(line.delivery_date < '". date2sql(Today()) ."'
		AND (line.quantity_ordered > line.quantity_received)) As OverDue
		FROM ".TB_PREF."purch_orders as porder,"
			.TB_PREF."purch_order_details as line,".TB_PREF."suppliers as supplier, "
			.TB_PREF."locations as location
		WHERE porder.order_no = line.order_no
		AND porder.trans_type ='18'
		AND porder.supplier_id = supplier.supplier_id
		AND location.loc_code = porder.into_stock_location
		AND (line.quantity_ordered > line.quantity_received) 
		AND porder.po_auth_req = 1
		AND porder.reference !='auto'
		AND porder.total >".db_escape($po_auth_req_amount)." GROUP BY porder.order_no";
		//display_error($sql);
    $result = db_query($sql);
	
    
    echo '<table class="table table-hover">
            <thead class="text-warning">
                <tr><th>#</th>                    
                    <th>Reference</th>
                    <th style="text-align:left">Supplier</th>
                    <th style="text-align:left">Location</th>
                    <th style="text-align:left">Suppliers Reference</th>
                    <th>Order Date</th>
					<th>Currency</th>
					<th style="text-align:right">Order Total</th>
                </tr></thead>';
    $k = 0; //row colour counter
    while ($myrow = db_fetch($result)){
        alt_table_row_color($k);
        label_cell(get_trans_view_str(ST_PURCHORDER, $myrow["order_no"]),"align=center");
		label_cell($myrow['reference'],"align=center");
		label_cell($myrow['supp_name'],"align=left");
		label_cell($myrow['location_name'],"align=left");
		label_cell($myrow['requisition_no'],"align=left");
		label_cell(sql2date($myrow['ord_date']),"align=center");
		label_cell($myrow['curr_code'],"align=center");
		amount_cell($myrow['OrderValue']);
        end_row();
    }
    end_table(1);
}


function outstanding_purchase_orders(){

	  
	$sql = "SELECT 
		porder.order_no, 
		porder.reference,
		supplier.supp_name, 
		location.location_name,
		porder.requisition_no, 
		porder.ord_date,
		supplier.curr_code,
		Sum((line.unit_price*line.quantity_ordered)-((line.unit_price*line.quantity_ordered)*(line.discount_percent/100))) AS OrderValue,
		Sum(line.delivery_date < '". date2sql(Today()) ."'
		AND (line.quantity_ordered > line.quantity_received)) As OverDue
		FROM ".TB_PREF."purch_orders as porder,"
			.TB_PREF."purch_order_details as line, "
			.TB_PREF."suppliers as supplier, "
			.TB_PREF."locations as location
		WHERE porder.order_no = line.order_no
		AND porder.trans_type = ".ST_PURCHORDER."
		AND porder.supplier_id = supplier.supplier_id
		AND location.loc_code = porder.into_stock_location
		AND porder.reference !='auto'
		AND (line.quantity_ordered > line.quantity_received)
		AND porder.po_auth_req = '2' GROUP BY porder.order_no";	
		
    $result = db_query($sql);
	
    
    echo '<table class="table table-hover">
            <thead class="text-warning">
                <tr><th>#</th>                    
                    <th>Reference</th>
                    <th style="text-align:left">Supplier</th>
                    <th style="text-align:left">Location</th>
                    <th style="text-align:left">Suppliers Reference</th>
                    <th>Order Date</th>
					<th>Currency</th>
					<th style="text-align:right">Order Total</th>
                </tr></thead>';
    $k = 0; //row colour counter
    while ($myrow = db_fetch($result)){
        alt_table_row_color($k);
        label_cell(get_trans_view_str(ST_PURCHORDER, $myrow["order_no"]),"align=center");
		label_cell($myrow['reference'],"align=center");
		label_cell($myrow['supp_name'],"align=left");
		label_cell($myrow['location_name'],"align=left");
		label_cell($myrow['requisition_no'],"align=left");
		label_cell(sql2date($myrow['ord_date']),"align=center");
		label_cell($myrow['curr_code'],"align=center");
		amount_cell($myrow['OrderValue']);
        end_row();
    }
    end_table(1);
}


//Top 10 salesman
function kv_salesman_top($today="", $limit=10, $width="33", &$pg=null)
	{
	
	    $today =Today();
		$begin = begin_fiscalyear();
		$begin1 = date2sql($begin);
		$today1 = date2sql($today);
		
		$sql = "SELECT salesman.salesman_name ,SUM((ov_amount + ov_discount) * rate * IF(trans.type = ".ST_CUSTCREDIT.", -1, 1)) AS total,
		trans.sales_person_id
		FROM ".TB_PREF."debtor_trans AS trans, 
			 ".TB_PREF."salesman AS salesman 
			WHERE trans.sales_person_id=salesman.salesman_code
			AND (trans.type = ".ST_SALESINVOICE." OR trans.type = ".ST_CUSTCREDIT.")
			AND tran_date >= '$begin1' AND tran_date <= '$today1' 
			GROUP by trans.sales_person_id ORDER BY total DESC, trans.sales_person_id
			LIMIT $limit";
			
		//display_error($sql);	
			
		$result = db_query($sql);
	
		
		 echo '<table class="table table-hover">
            <thead class="text-warning">
                <tr>
                <th style="text-align:left">Sales Man</th> 
                <th style="text-align:right">Monthly</th> 
                <th style="text-align:right">Yearly</th>
                </tr> 
                </thead>';
                
	
		$k = 0; //row colour counter
		$i = 0;
		while ($myrow = db_fetch($result))
		{
	    	alt_table_row_color($k);
	    	$name = $myrow["salesman_name"];
    		label_cell($name);
    		$salesman_monthly_tot =get_salesman_monthly_total($myrow["sales_person_id"]);
    		amount_cell($salesman_monthly_tot);
		    amount_cell($myrow['total']);
		   
		    $i++;
			end_row();
		}
		end_table(2);
		return $title;
	}
	
	//Sales Revenue based on different Offer Periods
function sales_revenue_based_on_inventory_offers(){

        $today = Today(); 
        $today = date2sql($today);
		
		
        $sql = "SELECT id,offer_name,from_date,to_date FROM ".TB_PREF."promotion_discounts  
                WHERE 1=1 GROUP BY id  ORDER BY id";
				
        $result = db_query($sql);
        
        
         echo '<table class="table table-hover">
            <thead class="text-warning">
                <tr>
                <th style="text-align:left">Offer Name</th> 
                <th style="text-align:left">From Date</th> 
                <th style="text-align:left">To Date</th> 
                <th style="text-align:right">Total Sales Revenue</th>
                </tr> 
                </thead>';
        
        $k = 0; //row colour counter
		$i = 0;
		while ($myrow = db_fetch($result))
		{
	    	alt_table_row_color($k);
	    	
    		label_cell($myrow["offer_name"]);
    	    label_cell(sql2date($myrow["from_date"]));
    		label_cell(sql2date($myrow["to_date"]));	
    		
    		$sales_revenue_tot=get_sale_revenue_based_on_offer($myrow["from_date"],$myrow["to_date"]);
			amount_cell($sales_revenue_tot);
		    
		   
		    $i++;
			end_row();
		}
		end_table(2);
		return $title;
}
	
//----------------------------------------------------------------------------------	
	
function get_salesman_monthly_total($salesman){

        $today =Today();
		
		$begin1 = date("Y-m-d", strtotime("first day of this month"));
		$today1 = date2sql($today);
		
	
	$sql = "SELECT SUM((ov_amount + ov_discount) * rate * IF(trans.type = ".ST_CUSTCREDIT.", -1, 1)) AS monthly_total
		FROM ".TB_PREF."debtor_trans AS trans, 
			 ".TB_PREF."salesman AS salesman 
			WHERE trans.sales_person_id=salesman.salesman_code
			AND (trans.type = ".ST_SALESINVOICE." OR trans.type = ".ST_CUSTCREDIT.")
			AND tran_date >= '$begin1' AND tran_date <= '$today1' 
			AND trans.sales_person_id = ".db_escape($salesman)."
			GROUP by trans.sales_person_id ORDER BY monthly_total DESC, trans.sales_person_id";
			
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
	
}	



function get_sale_revenue_based_on_offer($from_date,$to_date)
{
	
	
	$sql = "SELECT SUM((ov_amount + ov_discount + ov_gst) * rate * IF(type = ".ST_CUSTCREDIT.", -1, 1)) AS total
		FROM ".TB_PREF."debtor_trans  
			WHERE (type = ".ST_SALESINVOICE." OR type = ".ST_CUSTCREDIT.")
			AND tran_date >=".db_escape($from_date)." 
			AND tran_date <= ".db_escape($to_date)."";
		
	$result= db_query($sql,"The related sales revenue could not be retreived");
	
	$row=db_fetch_row($result);
	if($row[0]!='')
		return $row[0];
	else
		return 0;
}

//-------------------------------------------------------------------------
function get_sales_order_reference_and_customer($sales_order_id)
{
	$sql = "SELECT so.reference,cust.name
			FROM ".TB_PREF."sales_orders so,"
				.TB_PREF."debtors_master cust
			WHERE so.debtor_no = cust.debtor_no
			AND so.trans_type = 30
			AND so.order_no = ".db_escape($sales_order_id)."
			GROUP BY so.order_no";

	$result = db_query($sql, "could not query customers");

	return db_fetch($result);
}


//Completed Work Order Details
function kv_completed_work_order_details() {

  
   $sql = "SELECT 
			wo.sales_order_id,
			wo.wo_ref,
			sod.description,
			wo.units_reqd,
			wo.date_,
			wo.stock_id
		FROM ".TB_PREF."workorders as wo,
		    ".TB_PREF."sales_order_details as sod
			WHERE wo.sales_order_id = sod.order_no
			AND wo.stock_id         = sod.stk_code
			AND sod.trans_type      = 30
			AND sod.qty_sent < sod.quantity
			AND wo.closed = 1
			ORDER BY wo.id";
			//display_error($sql);
    $result = db_query($sql);   ?>
    
    <table class="table table-hover">
            <thead class="text-warning">
                <tr><th>Sales Order Reference</th>
                    <th>Customer</th>
					<th>Date</th>    
                    <th>Work Order Reference</th>                   
                    <th>Item Name</th>
                    <th>Quantity</th> 
                </tr></thead>
    <?php $k = 0; //row colour counter
    while ($myrow = db_fetch($result)){
        alt_table_row_color($k);
        
    $so_cust = get_sales_order_reference_and_customer($myrow['sales_order_id']);
       
        label_cell($so_cust['reference']);
		label_cell($so_cust['name']);
        label_cell(sql2date($myrow['date_']));
		label_cell($myrow['wo_ref']);
        label_cell($myrow['description']);
		$qdec = get_qty_dec($myrow['stock_id']);
	    qty_cell($myrow['units_reqd'], false, $qdec);
        end_row();
    }
    end_table(1);
}


?>