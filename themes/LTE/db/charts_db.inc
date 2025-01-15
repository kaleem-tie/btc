<?php
global $path_to_root;
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/sysnames.inc");
function query_count($table, $condition){
    // $table = table_name and $condition = array(con1, con2, con3, ...)
    $cd;$where='';
    foreach($condition as $cond) {
        $cd .= $cond . ' and ';
    }
    $cd = substr($cd, 0, -5);
    if(count($condition) != 0){$where = " where ";}
    $sst = "SELECT COUNT(*) from ".TB_PREF.$table.$where.$cd;
    $str = db_query($sst, "could not select". $table);
    $cct = db_fetch_row($str);
    return $cct[0];
    // return $sst;
}
function query_data($table, $condition){
    // $table = table_name and $condition = array(con1, con2, con3, ...)
    $cd='';$where='';
    foreach($condition as $cond) {
        $cd .= $cond . ' and ';
    }
    $cd = substr($cd, 0, -5);
    if(count($condition) != 0){$where = " where ";}
    $sst = "SELECT * from ".TB_PREF.$table.$where.$cd;
    $str = db_query($sst, "could not select". $table);
    while($row = db_fetch_assoc($str)){
             $final[] = $row;
    }
    
    return $final;
}




function query_date($table, $condition){
    // $table = table_name and $condition = array(con1, con2, con3, ...)
    $cd;$where='';
    foreach($condition as $cond) {
        $cd .= $cond . ' and ';
    }
    $cd = substr($cd, 0, -5);
    if(count($condition) != 0){$where = " where ";}
    $sst = "SELECT ord_date from ".TB_PREF.$table." where ".$cd;
    $str = db_query($sst, "could not select". $table);
    $cct = db_fetch($str);
    return $cct;
    // return $sst;
}
function fy($get)
{
    //get financial year
    $query = "SELECT * from ".TB_PREF."sys_prefs where name='f_year'"; 
    $sst = db_query($query);
    $rr = db_fetch_row($sst);
    $year = $rr[4];

    $query1 = "SELECT * from ".TB_PREF."fiscal_year where id='$year'"; 
    $sst1 = db_query($query1);
    $rr1 = db_fetch_assoc($sst1);
    
    if($get == 'begin'){
        $ex = $rr1['begin'];
        $exp = explode('-', $rr1['begin']);
        // return $ex[0] = year, $ex[1] = month $ex[2] = day
        return array($ex[0], $ex[1], $ex[2], $exp);
    }elseif($get == 'end'){
        $ex = explode('-', $rr1['end']);
        $exp = $rr1['end'];
        // return $ex[0] = year, $ex[1] = month $ex[2] = day
        return array($ex[0], $ex[1], $ex[2], $exp);
    }else{
        return 0;
    }
    
}
function user_data($id, $name, $user=0) {
    echo '
    <tr>
    <td><a>'.$id.'</a></td>
    <td>'.$name.'</td>
    <td>Active</td>
    <td>
        <a href="javascript:void(0)" class="btn btn-sm btn-primary load-chat" user-id="'.$user.'">Message</a>
    </td>
 </tr>
    ';
}
function get_lastchat($user_id) {    
    $my_id = $_SESSION['wa_current_user']->user;
    $sql ="SELECT * from ".TB_PREF."chats where (from_id=$my_id and to_id=$user_id) or (from_id=$user_id and to_id=$my_id) order by chat_id desc limit 1";
    $sst = db_query($sql);
    $row = db_fetch_row($sst);
    // display_error($sql);
    return $row[4];
}
function count_unread($chat_id) {
    $my_id = $_SESSION['wa_current_user']->user;
    $sql ="SELECT * from ".TB_PREF."chats where from_id=$chat_id and to_id=$my_id and (status=0 or status='')";
    $sst = db_query($sql);
    return db_num_rows($sst);
}
function sendEmail($data) {
    $from_email         = 'info@techintegraerp.com'; //from mail, sender email addrress 
    $recipient_email    = $data['emailto']; //recipient email addrress 
      
    //Load POST data from HTML form 
    $sender_name    = 'TechIntegraERP'; //sender name 
    $reply_to_email = 'info@techintegraerp.com'; //sender email, it will be used in "reply-to" header 
    $subject        = $data["subject"]; //subject for the email 
    $message        = $data["message"]; //body of the email 
      
  
    /*Always remember to validate the form fields like this 
    if(strlen($sender_name)<1) 
    { 
        die('Name is too short or empty!'); 
    }  
    */
      
    //Get uploaded file data using $_FILES array 
    $tmp_name    = $data['my_file']['tmp_name']; // get the temporary file name of the file on the server 
    $name        = $data['my_file']['name'];  // get the name of the file 
    $size        = $data['my_file']['size'];  // get size of the file for size validation 
    $type        = $data['my_file']['type'];  // get type of the file 
    $error       = $data['my_file']['error']; // get the error (if any) 
  
    //validate form field for attaching the file 
    if($file_error > 0) 
    { 
        die('Upload error or No files uploaded'); 
    } 
  
    //read from the uploaded file & base64_encode content 
    $handle = fopen($tmp_name, "r");  // set the file handle only for reading the file 
    $content = fread($handle, $size); // reading the file 
    fclose($handle);                  // close upon completion 
  
    $encoded_content = chunk_split(base64_encode($content)); 
  
    $boundary = md5("random"); // define boundary with a md5 hashed value 
  
    //header 
    $headers = "MIME-Version: 1.0\r\n"; // Defining the MIME version 
    $headers .= "From:".$from_email."\r\n"; // Sender Email 
    $headers .= "Reply-To: ".$reply_to_email."\r\n"; // Email addrress to reach back 
    $headers .= "Content-Type: multipart/mixed;\r\n"; // Defining Content-Type 
    $headers .= "boundary = $boundary\r\n"; //Defining the Boundary 
          
    //plain text  
    $body = "--$boundary\r\n"; 
    $body .= "Content-Type: text/plain; charset=ISO-8859-1\r\n"; 
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";  
    $body .= chunk_split(base64_encode($message));  
          
    //attachment 
    $body .= "--$boundary\r\n"; 
    $body .="Content-Type: $file_type; name=".$file_name."\r\n"; 
    $body .="Content-Disposition: attachment; filename=".$file_name."\r\n"; 
    $body .="Content-Transfer-Encoding: base64\r\n"; 
    $body .="X-Attachment-Id: ".rand(1000, 99999)."\r\n\r\n";  
    $body .= $encoded_content; // Attaching the encoded file with email 
      
    $sentMailResult = mail($recipient_email, $subject, $body, $headers); 
  
    if($sentMailResult )  
    { 
       echo "File Sent Successfully."; 
       unlink($name); // delete the file after attachment sent. 
       // echo $name;
    } 
    else
    { 
        // print_r($data);
       die("Sorry but the email could not be sent. 
                    Please go back and try again!"); 
    } 
}
function get_username($user_id) {
    $sql ="SELECT user_id from ".TB_PREF."users where id=$user_id";
    $sst = db_query($sql);
    $rr = db_fetch_row($sst);
    return $rr[0];
}
function get_onlineuser($user_id) {
    $sql ="SELECT * from ".TB_PREF."useronline where user_name='$user_id'";
    $sst = db_query($sql);
    $count = db_num_rows($sst);
    return $count;
}
function toptenmanuf()
    {
        global $path_to_root;

        $begin = begin_fiscalyear();
        $today = Today();
        $begin1 = date2sql($begin);
        $today1 = date2sql($today);
        $sql = "SELECT SUM((trans.unit_price * trans.quantity) * d.rate) AS total, s.stock_id, s.description, SUM(trans.quantity) AS qty FROM 0_debtor_trans_details AS trans, ".TB_PREF."stock_master AS s, ".TB_PREF."debtor_trans AS d WHERE trans.stock_id=s.stock_id AND trans.debtor_trans_type=d.type AND trans.debtor_trans_no=d.trans_no AND (d.type = ".ST_SALESINVOICE." OR d.type = ".ST_CUSTCREDIT.") AND s.mb_flag='M' AND d.tran_date >= '$begin1' AND d.tran_date <= '$today1' GROUP by s.stock_id ORDER BY total DESC, s.stock_id LIMIT 10";
              // display_error($sql);
        $result = db_query($sql);
        while($row = db_fetch_assoc($result)){
             $final[] = $row;
        }
        return $final;

    }
    function toptensold()
    {
        global $path_to_root;

        $begin = begin_fiscalyear();
        $today = Today();
        $begin1 = date2sql($begin);
        $today1 = date2sql($today);
        $sql = "SELECT SUM((trans.unit_price * trans.quantity) * d.rate) AS total, s.stock_id, s.description, SUM(trans.quantity) AS qty FROM 0_debtor_trans_details AS trans, ".TB_PREF."stock_master AS s, ".TB_PREF."debtor_trans AS d WHERE trans.stock_id=s.stock_id AND trans.debtor_trans_type=d.type AND trans.debtor_trans_no=d.trans_no AND (d.type = ".ST_SALESINVOICE." OR d.type = ".ST_CUSTCREDIT.") AND d.tran_date >= '$begin1' AND d.tran_date <= '$today1' GROUP by s.stock_id ORDER BY total DESC, s.stock_id LIMIT 10";
              // display_error($sql);
        $result = db_query($sql);
        while($row = db_fetch_assoc($result)){
             $final[] = $row;
        }
        return $final;

    }

function toptensupplier() {
    $begin = begin_fiscalyear();
    $today = Today();
    $begin1 = date2sql($begin);
    $today1 = date2sql($today);
    $sql = "SELECT SUM((trans.ov_amount + trans.ov_discount) * rate) AS total, s.supplier_id, s.supp_name FROM
        ".TB_PREF."supp_trans AS trans, ".TB_PREF."suppliers AS s WHERE trans.supplier_id=s.supplier_id
        AND (trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_SUPPCREDIT.")
        AND tran_date >= '$begin1' AND tran_date <= '$today1' GROUP by s.supplier_id ORDER BY total DESC, s.supplier_id 
         LIMIT 10";
         $result = db_query($sql);
         while($row = db_fetch_assoc($result)){
              $final[] = $row;
         }
         return $final;
}
function toptencustomer() {
    $begin = begin_fiscalyear();
    $today = Today();
    $begin1 = date2sql($begin);
    $today1 = date2sql($today);
    $sql = "SELECT SUM((ov_amount + ov_discount) * rate * IF(trans.type = ".ST_CUSTCREDIT.", -1, 1)) AS total,d.debtor_no, d.name"
    ." FROM ".TB_PREF."debtor_trans AS trans, ".TB_PREF."debtors_master AS d"
    ." WHERE trans.debtor_no=d.debtor_no"
    ." AND (trans.type = ".ST_SALESINVOICE." OR trans.type = ".ST_CUSTCREDIT.")"
    ." AND tran_date >= '$begin1' AND tran_date <= '$today1' GROUP by d.debtor_no ORDER BY total DESC, d.debtor_no LIMIT 10";
         $result = db_query($sql);
         while($row = db_fetch_assoc($result)){
              $final[] = $row;
         }
         return $final;
}


function monthlysalesinquiry() {
    $years = get_current_fiscalyear(); 
    $sql = 'SELECT count(order_no) as total_inquiry,DATE_FORMAT(ord_date,"%b") as months FROM '.TB_PREF.'sales_orders WHERE trans_type=37 AND ord_date >="'.$years["begin"].'" AND ord_date <="'.$years["end"].'"
    GROUP BY concat(cast(case when weekofyear(ord_date) = 1 and month(ord_date)=12 then year(ord_date) + 1 else year(ord_date) end as char),cast(month(ord_date) as char)) ORDER BY ord_date limit 10'; 
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_inquiry'];
        }
        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}


function monthlysalesquotation() {
    $years = get_current_fiscalyear(); 
    $sql = 'SELECT count(order_no) as total_quote,DATE_FORMAT(ord_date,"%b") as months FROM '.TB_PREF.'sales_orders WHERE trans_type=32 AND ord_date >="'.$years["begin"].'" AND ord_date <="'.$years["end"].'"
    GROUP BY concat(cast(case when weekofyear(ord_date) = 1 and month(ord_date)=12 then year(ord_date) + 1 else year(ord_date) end as char),cast(month(ord_date) as char)) ORDER BY ord_date limit 10'; 
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_quote'];
        }
        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}

function monthlysalesorders() {
    $years = get_current_fiscalyear(); 
  $sql = 'SELECT count(order_no) as total_order,DATE_FORMAT(ord_date,"%b") as months FROM '.TB_PREF.'sales_orders WHERE trans_type=30 AND ord_date >='.db_escape($years["begin"]).' AND ord_date <='. db_escape($years["end"]).' 
		GROUP BY concat(cast(case when weekofyear(ord_date) = 1 and month(ord_date)=12 then year(ord_date) + 1 else year(ord_date) end as char),cast(month(ord_date) as char)) ORDER BY ord_date limit 10';  
		
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_order'];
        }
        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}


function monthlysalesdisptches() {
    $years = get_current_fiscalyear(); 
	
	
	$sql = 'SELECT count(trans_no) as total_dispatches,DATE_FORMAT(tran_date,"%b") as months FROM '.TB_PREF.'debtor_trans WHERE type=13  AND ov_amount !=0 AND tran_date >='.db_escape($years["begin"]).' AND tran_date <='. db_escape($years["end"]).'
		GROUP BY concat(cast(case when weekofyear(tran_date) = 1 and month(tran_date)=12 then year(tran_date) + 1 else year(tran_date) end as char),cast(month(tran_date) as char)) order by tran_date limit 10'; 
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_dispatches'];
        }
        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}


function monthlysalesinvoices() {
    $years = get_current_fiscalyear(); 
	
	
	 $sql = 'SELECT count(trans_no) as total_invoices,DATE_FORMAT(tran_date,"%b") as months FROM '.TB_PREF.'debtor_trans WHERE type=10 AND  ov_amount!=0 AND tran_date >='.db_escape($years["begin"]).' AND tran_date <='. db_escape($years["end"]).'
		GROUP BY concat(cast(case when weekofyear(tran_date) = 1 and month(tran_date)=12 then year(tran_date) + 1 else year(tran_date) end as char),cast(month(tran_date) as char)) order by tran_date limit 10';  
		
	
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_invoices'];
        }
        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}


function monthlysalesenqandquote() {

    $years = get_current_fiscalyear(); 
	
    $sql = 'SELECT DATE_FORMAT(ord_date,"%b") from '.TB_PREF.'sales_orders WHERE trans_type=37 OR trans_type=32 AND ord_date >="'.$years["begin"].'" AND ord_date <="'. $years["end"].'" GROUP BY DATE_FORMAT(ord_date,"%b") ORDER BY ord_date LIMIT 10'; 
		
        $result = db_query($sql,'Error getting weekly sales data');
		
        $table = array();

        $rows = array();
        $months = array();
        $orders = array();
        
        while($r = db_fetch($result)) {
            
			$mn=$r[0];
			$sql2 = 'SELECT count(order_no) AS total_enq FROM '.TB_PREF.'sales_orders WHERE  trans_type=37 AND DATE_FORMAT(ord_date,"%b")="'.$mn. '"'; 
            $result2 = db_query($sql2);
		    $enq_data=db_fetch($result2);
		   
			$sql3 = 'SELECT count(order_no) AS total_quote FROM '.TB_PREF.'sales_orders WHERE  trans_type=32 AND DATE_FORMAT(ord_date,"%b")="'.$mn. '"'; 
			$result3 = db_query($sql3,'Error getting weekly sales data');
			$quote_data=db_fetch($result3);
		
            // the following line will used to slice the Pie chart
            $months[] = $r[0];
            $rows[] = $enq_data['total_enq'];
			$quotes[] = $quote_data['total_quote'];
			
        }
	
        $table['rows'] = $rows;
        $table['months'] = $months;
        $table['quotes'] = $quotes;
        $jsonTable = $table;
        return $jsonTable;
}


function monthlysalesenqandquoteandorder() {

    $years = get_current_fiscalyear(); 
	
    $sql = 'SELECT DATE_FORMAT(ord_date,"%b") from '.TB_PREF.'sales_orders WHERE trans_type=37 OR trans_type=32 OR trans_type=30 AND ord_date >="'.$years["begin"].'" AND ord_date <="'. $years["end"].'" GROUP BY DATE_FORMAT(ord_date,"%b") ORDER BY ord_date LIMIT 10'; 
		
        $result = db_query($sql,'Error getting weekly sales data');
		
        $table = array();

        $rows = array();
        $months = array();
		$quotes = array();
        $orders = array();
        
        while($r = db_fetch($result)) {
            
			$mn=$r[0];
			
			
			
			$sql2 = 'SELECT count(order_no) AS total_enq FROM '.TB_PREF.'sales_orders WHERE  trans_type=37 AND DATE_FORMAT(ord_date,"%b")="'.$mn. '"'; 
            $result2 = db_query($sql2);
		    $enq_data=db_fetch($result2);
			
			$sql3 = 'SELECT count(order_no) AS total_quote FROM '.TB_PREF.'sales_orders WHERE  trans_type=32 AND DATE_FORMAT(ord_date,"%b")="'.$mn. '"'; 
            $result3 = db_query($sql3);
		    $quote_data=db_fetch($result3);
		   
			$sql4 = 'SELECT count(order_no) AS total_order FROM '.TB_PREF.'sales_orders WHERE  trans_type=30 AND DATE_FORMAT(ord_date,"%b")="'.$mn. '"'; 
         
			$result4 = db_query($sql4,'Error getting weekly sales data');
			$order_data=db_fetch($result4);

           
		
            // the following line will used to slice the Pie chart
            $months[] = $r[0];
            $rows[] = $enq_data['total_enq'];
			$quotes[] = $quote_data['total_quote'];
			$orders[] = $order_data['total_order'];

                
			
        }
	  
        $table['rows'] = $rows;



        $table['months'] = $months;
        $table['quotes'] = $quotes;
		$table['orders'] = $orders;
        $jsonTable = $table;
        return $jsonTable;
}

function monthlysalesordersvalue() {
    $years = get_current_fiscalyear(); 
    $sql = 'SELECT ROUND(SUM(total),3) as total_order,DATE_FORMAT(ord_date,"%b") as months FROM '.TB_PREF.'sales_orders WHERE trans_type=30 AND ord_date >='.db_escape($years["begin"]).' AND ord_date <='. db_escape($years["end"]).' 
		GROUP BY concat(cast(case when weekofyear(ord_date) = 1 and month(ord_date)=12 then year(ord_date) + 1 else year(ord_date) end as char),cast(month(ord_date) as char)) ORDER BY ord_date limit 10'; 
		
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_order'];
        }
        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}

function monthlysalesquotationvalue() {
    $years = get_current_fiscalyear(); 
    $sql = 'SELECT ROUND(SUM(total),3) as total_quote,DATE_FORMAT(ord_date,"%b") as months FROM '.TB_PREF.'sales_orders WHERE trans_type=32 AND ord_date >='.db_escape($years["begin"]).' AND ord_date <='. db_escape($years["end"]).' 
		GROUP BY concat(cast(case when weekofyear(ord_date) = 1 and month(ord_date)=12 then year(ord_date) + 1 else year(ord_date) end as char),cast(month(ord_date) as char)) ORDER BY ord_date limit 10'; 

    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_quote'];
         
        }

        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}

function monthlysalesdispatchvalue() {
    $years = get_current_fiscalyear(); 
    $sql = 'SELECT ROUND(SUM(ov_amount+ov_gst),3) as total_dispatch,DATE_FORMAT(tran_date,"%b") as months FROM '.TB_PREF.'debtor_trans WHERE type=13 AND ov_amount!=0 AND tran_date >='.db_escape($years["begin"]).' AND tran_date <='. db_escape($years["end"]).'
		GROUP BY concat(cast(case when weekofyear(tran_date) = 1 and month(tran_date)=12 then year(tran_date) + 1 else year(tran_date) end as char),cast(month(tran_date) as char)) order by tran_date limit 10'; 
		
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_dispatch'];
        }
        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}


function monthlysalesinvoicevalue() {

    $years = get_current_fiscalyear(); 
    $sql = 'SELECT ROUND(SUM(ov_amount+ov_gst),3) as total_invoice,DATE_FORMAT(tran_date,"%b") as months 
	FROM '.TB_PREF.'debtor_trans 
	WHERE type=10  AND ov_amount!=0
	AND tran_date >='.db_escape($years["begin"]).' 
	AND tran_date <='. db_escape($years["end"]).'
	GROUP BY concat(cast(case when weekofyear(tran_date) = 1 and month(tran_date)=12 then year(tran_date) + 1 else year(tran_date) end as char),cast(month(tran_date) as char)) order by tran_date limit 10'; 
		
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_invoice'];
        }
        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}

function monthlysalesquotationsorders() {
    $years = get_current_fiscalyear(); 
    $sql = 'SELECT DATE_FORMAT(ord_date,"%b") from '.TB_PREF.'sales_orders WHERE trans_type=32 OR trans_type=30 AND ord_date >="'.$years["begin"].'" AND ord_date <="'. $years["end"].'" GROUP BY DATE_FORMAT(ord_date,"%b") ORDER BY ord_date LIMIT 10'; 
		
        $result = db_query($sql,'Error getting weekly sales data');
		
        $table = array();

        $rows = array();
        $months = array();
        $orders = array();
        
        while($r = db_fetch($result)) {
            
			$mn=$r[0];
			$sql2 = 'SELECT count(order_no) AS total_quote FROM '.TB_PREF.'sales_orders WHERE  trans_type=32 AND DATE_FORMAT(ord_date,"%b")="'.$mn. '"'; 
            $result2 = db_query($sql2);
		    $quote_data=db_fetch($result2);
		   
			$sql3 = 'SELECT count(order_no) AS total_order FROM '.TB_PREF.'sales_orders WHERE  trans_type=30 AND DATE_FORMAT(ord_date,"%b")="'.$mn. '"'; 
			$result3 = db_query($sql3,'Error getting weekly sales data');
			$order_data=db_fetch($result3);
		
            // the following line will used to slice the Pie chart
            $months[] = $r[0];
            $rows[] = $quote_data['total_quote'];
			$orders[] = $order_data['total_order'];
			
        }
       
	
        $table['rows'] = $rows;
        $table['months'] = $months;
        $table['orders'] = $orders;
        $jsonTable = $table;

        

        return $jsonTable;
}

function monthlysalesquotationsordersvalue() {

    $years = get_current_fiscalyear(); 
    $sql = 'SELECT DATE_FORMAT(ord_date,"%b") from '.TB_PREF.'sales_orders WHERE trans_type=32 OR trans_type=30 AND ord_date >="'.$years["begin"].'" AND ord_date <="'. $years["end"].'" GROUP BY DATE_FORMAT(ord_date,"%b") ORDER BY ord_date LIMIT 10'; 
		
        $result = db_query($sql,'Error getting weekly sales data');
		
        $table = array();

        $rows = array();
        $months = array();
        $orders = array();
        
        while($r = db_fetch($result)) {
            
			$mn=$r[0];
			
			$sql2 = 'SELECT ROUND(SUM(total),3) AS total_quote FROM '.TB_PREF.'sales_orders WHERE  trans_type=32 AND DATE_FORMAT(ord_date,"%b")="'.$mn. '"'; 
            $result2 = db_query($sql2);
		    $quote_data=db_fetch($result2);
		   
			$sql3 = 'SELECT ROUND(SUM(total),3) AS total_order FROM '.TB_PREF.'sales_orders WHERE  trans_type=30 AND DATE_FORMAT(ord_date,"%b")="'.$mn. '"'; 
			$result3 = db_query($sql3,'Error getting weekly sales data');
			$order_data=db_fetch($result3);
		
            // the following line will used to slice the Pie chart
            $months[] = $r[0];
            $rows[] = $quote_data['total_quote'];
			$orders[] = $order_data['total_order'];
			
        }
	
        $table['rows'] = $rows;
        $table['months'] = $months;
        $table['orders'] = $orders;
        $jsonTable = $table;
        return $jsonTable;
}

//-----------------------------------------------------------------------------
// Purchase Dashboards  -- 06-03-2023


function monthlypurchaseinquiries() {

    $years = get_current_fiscalyear(); 
	
    $sql = 'SELECT count(order_no) as total_inq,DATE_FORMAT(ord_date,"%b") as months FROM '.TB_PREF.'purch_orders WHERE trans_type=51 AND ord_date >='.db_escape($years["begin"]).' AND ord_date <='. db_escape($years["end"]).' 
		GROUP BY concat(cast(case when weekofyear(ord_date) = 1 and month(ord_date)=12 then year(ord_date) + 1 else year(ord_date) end as char),cast(month(ord_date) as char)) ORDER BY ord_date limit 10'; 
		
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_inq'];
        }
        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}

function monthlypurchaseorders() {

    $years = get_current_fiscalyear(); 
	
    $sql = 'SELECT count(order_no) as total_order,DATE_FORMAT(ord_date,"%b") as months FROM '.TB_PREF.'purch_orders WHERE trans_type=18 AND ord_date >='.db_escape($years["begin"]).' AND ord_date <='. db_escape($years["end"]).' 
		GROUP BY concat(cast(case when weekofyear(ord_date) = 1 and month(ord_date)=12 then year(ord_date) + 1 else year(ord_date) end as char),cast(month(ord_date) as char)) ORDER BY ord_date limit 10'; 
		
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_order'];
        }
        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}

function monthlypurchasequotations() {

    $years = get_current_fiscalyear(); 
	
    $sql = 'SELECT count(order_no) as total_quote,DATE_FORMAT(ord_date,"%b") as months FROM '.TB_PREF.'purch_orders WHERE trans_type=52 AND ord_date >='.db_escape($years["begin"]).' AND ord_date <='. db_escape($years["end"]).' 
		GROUP BY concat(cast(case when weekofyear(ord_date) = 1 and month(ord_date)=12 then year(ord_date) + 1 else year(ord_date) end as char),cast(month(ord_date) as char)) ORDER BY ord_date limit 10'; 
		
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_quote'];
        }
        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}

function monthlypurchasegrns() {

    $years = get_current_fiscalyear(); 
	
    $sql = 'SELECT count(id) as total_grn,DATE_FORMAT(delivery_date,"%b") as months FROM '.TB_PREF.'grn_batch WHERE  delivery_date >='.db_escape($years["begin"]).' AND delivery_date <='. db_escape($years["end"]).' 
		GROUP BY concat(cast(case when weekofyear(delivery_date) = 1 and month(delivery_date)=12 then year(delivery_date) + 1 else year(delivery_date) end as char),cast(month(delivery_date) as char)) ORDER BY delivery_date'; 
		
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_grn'];
        }
        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}

function monthlypurchaseinvoices() {

    $years = get_current_fiscalyear(); 
	
	
 $sql = 'SELECT count(trans_no) as total_invoice,DATE_FORMAT(tran_date,"%b") as months FROM '.TB_PREF.'supp_trans WHERE  type=20 AND tran_date >='.db_escape($years["begin"]).' AND tran_date <='. db_escape($years["end"]).' 
		GROUP BY concat(cast(case when weekofyear(tran_date) = 1 and month(tran_date)=12 then year(tran_date) + 1 else year(tran_date) end as char),cast(month(tran_date) as char)) ORDER BY tran_date'; 
		
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
	$months = array();
    while($r = db_fetch_assoc($result)) {
        $months[] =  $r['months'];
        $rows[] = $r['total_invoice'];
    }
    $table['rows'] = $rows;
    $table['months'] = $months;
    $jsonTable = $table;
    return $jsonTable;
}

function monthlypurchasesenqandquote() {

    $years = get_current_fiscalyear(); 
	
    $sql = 'SELECT DATE_FORMAT(ord_date,"%b") from '.TB_PREF.'purch_orders WHERE trans_type=51 OR trans_type=52 AND ord_date >="'.$years["begin"].'" AND ord_date <="'. $years["end"].'" GROUP BY DATE_FORMAT(ord_date,"%b") ORDER BY ord_date LIMIT 10'; 
		
        $result = db_query($sql,'Error getting weekly sales data');
		
        $table = array();

        $rows = array();
        $months = array();
        $orders = array();
        
        while($r = db_fetch($result)) {
            
			$mn=$r[0];
			$sql2 = 'SELECT count(order_no) AS total_enq FROM '.TB_PREF.'purch_orders WHERE  trans_type=51 AND DATE_FORMAT(ord_date,"%b")="'.$mn. '"'; 
            $result2 = db_query($sql2);
		    $enq_data=db_fetch($result2);
		   
			$sql3 = 'SELECT count(order_no) AS total_quote FROM '.TB_PREF.'purch_orders WHERE  trans_type=52 AND DATE_FORMAT(ord_date,"%b")="'.$mn. '"'; 
			$result3 = db_query($sql3,'Error getting weekly sales data');
			$quote_data=db_fetch($result3);
		
            // the following line will used to slice the Pie chart
            $months[] = $r[0];
            $rows[] = $enq_data['total_enq'];
			$quotes[] = $quote_data['total_quote'];
			
        }
	
        $table['rows'] = $rows;
        $table['months'] = $months;
        $table['quotes'] = $quotes;
        $jsonTable = $table;
        return $jsonTable;
}


function monthlypurchasesquotationsorders() {
    $years = get_current_fiscalyear(); 
    $sql = 'SELECT DATE_FORMAT(ord_date,"%b") from '.TB_PREF.'purch_orders WHERE trans_type=52 OR trans_type=18 AND ord_date >="'.$years["begin"].'" AND ord_date <="'. $years["end"].'" GROUP BY DATE_FORMAT(ord_date,"%b") ORDER BY ord_date LIMIT 10'; 
		
        $result = db_query($sql,'Error getting weekly sales data');
		
        $table = array();

        $rows = array();
        $months = array();
        $orders = array();
        
        while($r = db_fetch($result)) {
            
			$mn=$r[0];
			$sql2 = 'SELECT count(order_no) AS total_quote FROM '.TB_PREF.'purch_orders WHERE  trans_type=52 AND DATE_FORMAT(ord_date,"%b")="'.$mn. '"'; 
            $result2 = db_query($sql2);
		    $quote_data=db_fetch($result2);
		   
			$sql3 = 'SELECT count(order_no) AS total_order FROM '.TB_PREF.'purch_orders WHERE  trans_type=18 AND DATE_FORMAT(ord_date,"%b")="'.$mn. '"'; 
			$result3 = db_query($sql3,'Error getting weekly sales data');
			$order_data=db_fetch($result3);
		
            // the following line will used to slice the Pie chart
            $months[] = $r[0];
            $rows[] = $quote_data['total_quote'];
			$orders[] = $order_data['total_order'];
			
        }
        
         
	
        $table['rows'] = $rows;
        $table['months'] = $months;
        $table['orders'] = $orders;
        $jsonTable = $table;
        return $jsonTable;
}


function monthlypurchasesordersvalue() {
    $years = get_current_fiscalyear(); 
    $sql = 'SELECT ROUND(SUM(total),3) as total_order,
	DATE_FORMAT(ord_date,"%b") as months FROM '.TB_PREF.'purch_orders 
	WHERE trans_type=18 AND ord_date >='.db_escape($years["begin"]).' 
	AND ord_date <='. db_escape($years["end"]).' 
	GROUP BY concat(cast(case when weekofyear(ord_date) = 1 and month(ord_date)=12 then year(ord_date) + 1 else year(ord_date) end as char),cast(month(ord_date) as char)) ORDER BY ord_date limit 10'; 
		
    $result = db_query($sql) or die(_('Error getting weekly sales data'));
    $rows = array();
		$months = array();
        while($r = db_fetch_assoc($result)) {
            $months[] =  $r['months'];
            $rows[] = $r['total_order'];
        }
        $table['rows'] = $rows;
        $table['months'] = $months;
        $jsonTable = $table;
        return $jsonTable;
}
//----------------------------------------------------------------------------

function banktransaction() {
    $years = get_current_fiscalyear(); 

    $sql = 'SELECT trans_date from '.TB_PREF.'bank_trans where trans_date >="'.$years["begin"].'" AND trans_date <="'. $years["end"].'" group by trans_date';
    $result = db_query($sql);
    $table = array();
    $dates = array();
    $data = array();

    while($r = db_fetch($result)) {
        $td = $r['trans_date'];
        $sql2 = 'SELECT SUM(amount) as trt from '.TB_PREF.'bank_trans where trans_date="'.$td.'"';

        $result2 = db_query($sql2);
        $ff_table = db_fetch($result2);

        $data[] = $ff_table['trt'];
        $dates[] = $td;
    }
    $table['data'] = $data;
    $table['dates'] = $dates;
    return $table;
}
function bankbalance() {
    $years = get_current_fiscalyear(); 
    $sql = 'SELECT DATE_FORMAT(trans_date,"%b"), bank_act from '.TB_PREF.'bank_trans WHERE trans_date >="'.$years["begin"].'" AND trans_date <="'. $years["end"].'" GROUP BY DATE_FORMAT(trans_date,"%b")'; 
    $result = db_query($sql);

    $amount = array();
    $dates = array();
    $accounts = array();
    $table = array();
    $ac_table=array();
    while ($r = db_fetch($result)) {
        $bb = $r[0];
        $sst = db_query("SELECT * from ".TB_PREF."bank_accounts");
        $bb_table=array();
        while ($b = db_fetch($sst)) {
            $sql2 = "SELECT bank_act, bank_account_name, trans_date, SUM(amount) balance FROM ".TB_PREF."bank_trans bt"
              ." INNER JOIN ".TB_PREF."bank_accounts ba ON bt.bank_act = ba.id"
              .' WHERE DATE_FORMAT(bt.trans_date,"%b") = "'.$bb.'" and  trans_date >="'.$years["begin"].'" AND trans_date <="'. $years["end"].'"'
              ." AND inactive <> 1 and bank_act=".$b['id']."";
            $result2 = db_query($sql2);
            $bb_table[] = db_fetch($result2);
        }

        $amount[] = $bb_table;
        $dates[] = $bb;
    }
    $sst = db_query("SELECT * from ".TB_PREF."bank_accounts");
        while ($b = db_fetch_assoc($sst)) {
            $ac_table[] = $b['bank_account_name'];
        }
    $table['amount'] = $amount;
    $table['dates'] = $dates;
    $table['act'] = $ac_table;
    return $table;
    
}


function dailysales() {
    $sql = 'SELECT * FROM (SELECT max(trans_date) `Week End`, '
            .'concat(cast(case when weekofyear(trans_date) = 1 and month(trans_date)=12 then year(trans_date) + 1 else year(trans_date) end as char),cast(weekofyear(trans_date) as char)) `Week No`, '
            .'sum(case when weekday(trans_date)=0 then gross_output else 0 end) Monday, '
            .'sum(case when weekday(trans_date)=1 then gross_output else 0 end) Tuesday, '
            .'sum(case when weekday(trans_date)=2 then gross_output else 0 end) Wednesday, '
            .'sum(case when weekday(trans_date)=3 then gross_output else 0 end) Thursday, '
            .'sum(case when weekday(trans_date)=4 then gross_output else 0 end) Friday, '
            .'sum(case when weekday(trans_date)=5 then gross_output else 0 end) Saturday, '
            .'sum(case when weekday(trans_date)=6 then gross_output else 0 end) Sunday '
            .'FROM (SELECT bt.trans_date trans_date, '
                .'sum((ttd.net_amount+ttd.amount)*ttd.ex_rate) gross_output, '
                .'sum(ttd.net_amount*ttd.ex_rate) net_output, '
                .'sum(ttd.amount*ttd.ex_rate) payable  '
            .'FROM '.TB_PREF.'bank_trans bt  '
            .'INNER JOIN '.TB_PREF.'cust_allocations ca  '
                .'ON bt.type = ca.trans_type_from  '
                .'AND bt.trans_no = ca.trans_no_from  '
            .'INNER JOIN '.TB_PREF.'debtor_trans dt  '
                .'ON dt.type = ca.trans_type_from  '
                .'AND dt.trans_no = ca.trans_no_from  '
            .'INNER JOIN '.TB_PREF.'debtors_master dm '
                .'ON dt.debtor_no = dm.debtor_no '
            .'INNER JOIN '.TB_PREF.'trans_tax_details ttd  '
                .'ON ttd.trans_type = ca.trans_type_to  '
                .'AND ttd.trans_no = ca.trans_no_to  '
            .'INNER JOIN '.TB_PREF.'tax_types tt  '
                .'ON tt.id = ttd.tax_type_id  '
            .' GROUP BY bt.trans_date  '
            .') days '
            .'GROUP BY concat(cast(case when weekofyear(trans_date) = 1 and month(trans_date)=12 then year(trans_date) + 1 else year(trans_date) end as char),cast(weekofyear(trans_date) as char)) '
            .'ORDER BY max(trans_date) desc '
            .' limit 10'
            .") weeks ORDER BY `Week End`";
            
            
    
        $result = db_query($sql) or die(_('Error getting daily sales data'));

       
        //flag is not needed
        $flag = true;
        $table = array();
        $table['cols'] = array(
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        );
        
        $rows = array();
        while($r = db_fetch_assoc($result)) {
            $temp = array();
            // the following line will used to slice the Pie chart
            $temp[] = array('Monday', $r['Monday']);
            $temp[] = array('Tuesday', $r['Tuesday']);
            $temp[] = array('Wednesday', $r['Wednesday']);
            $temp[] = array('Thursday', $r['Thursday']);
            $temp[] = array('Friday', $r['Friday']);
            $temp[] = array('Saturday', $r['Saturday']);
            $temp[] = array('Sunday', $r['Sunday']);
            $rows[] = $temp;
            
             
        }
        
      //  display_error(json_encode($rows));

       $table['rows'] = $rows;
       $jsonTable = $table;
       return $jsonTable;
       
}


function weeklysales() {
    $sql .= 'SELECT max(bt.trans_date) `Week End`, '
            .'concat(cast(case when weekofyear(bt.trans_date) = 1 and month(bt.trans_date)=12 then year(bt.trans_date) + 1 else year(bt.trans_date) end as char),cast(weekofyear(bt.trans_date) as char)) `Week no`, '
            .'sum((ttd.net_amount+ttd.amount)*ttd.ex_rate) `Gross Sales`, '
            .'sum(ttd.net_amount*ttd.ex_rate) `Net Sales`, '
            .'sum(ttd.amount*ttd.ex_rate) `Tax` '
        .'FROM '.TB_PREF.'bank_trans bt '
        .'INNER JOIN '.TB_PREF.'cust_allocations ca '
            .'ON bt.type = ca.trans_type_from '
            .'AND bt.trans_no = ca.trans_no_from '
        .'INNER JOIN '.TB_PREF.'debtor_trans dt '
            .'ON dt.type = ca.trans_type_from '
            .'AND dt.trans_no = ca.trans_no_from '
        .'INNER JOIN '.TB_PREF.'debtors_master dm '
            .'ON dt.debtor_no = dm.debtor_no '
        .'INNER JOIN '.TB_PREF.'trans_tax_details ttd '
            .'ON ttd.trans_type = ca.trans_type_to '
            .'AND ttd.trans_no = ca.trans_no_to '
        .'INNER JOIN '.TB_PREF.'tax_types tt '
            .'ON tt.id = ttd.tax_type_id '
        .' GROUP BY concat(cast(case when weekofyear(bt.trans_date) = 1 and month(bt.trans_date)=12 then year(bt.trans_date) + 1 else year(bt.trans_date) end as char),cast(weekofyear(bt.trans_date) as char)) ';
            // .') items order by max(bt.trans_date) desc';
        $result = db_query($sql) or die(_('Error getting daily sales data'));

        $rows = array();
        //flag is not needed
        $flag = true;
        $table = array();
        $table['cols'] = array(
            array('label' => 'Week End', 'type' => 'string'),
            array('label' => 'Gross Sales', 'type' => 'number')
        );

        $rows = array();
        while($r = db_fetch_assoc($result)) {
            $temp = array();
            // the following line will used to slice the Pie chart
            $temp[] = $r['Week End'];
            $temp[] = $r['Gross Sales'];
            $rows[] = $temp;
        }

        $table['rows'] = $rows;
        return $table;
}
function glreturn() {
    $begin = begin_fiscalyear();
        $today = Today();
        $begin1 = date2sql($begin);
        $today1 = date2sql($today);
        $sql = "SELECT SUM(amount) AS total, c.class_name, c.ctype FROM
            ".TB_PREF."gl_trans,".TB_PREF."chart_master AS a, ".TB_PREF."chart_types AS t,
            ".TB_PREF."chart_class AS c WHERE
            account = a.account_code AND a.account_type = t.id AND t.class_id = c.cid
            AND IF(c.ctype > 3, tran_date >= '$begin1', tran_date >= '0000-00-00')
            AND tran_date <= '$today1' "
            ." GROUP BY c.cid ORDER BY c.cid";
        $result = db_query($sql, "Transactions could not be calculated");

        $table = array();
        $rows = array();

        while ($myrow = db_fetch($result))
            {
                $temp = array();
                    $temp[] = $myrow['class_name'];
                    $temp[] = -$myrow['total'];
                    $rows[] = $temp;
            }
            $table = $rows;
        return $table;
}
function overduesalesinvoice() {
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
    $result = db_query($sql);
    $table = array();
    $rows = array();

    while ($myrow = db_fetch($result)){
        $temp = array();
        $temp[] = $myrow['due_date'];
        $ex = explode('.', $myrow['total']);
        $temp[] = $ex[0];
        $rows[] = $temp;
    }
    $table = $rows;
    return $table;
}


function currentworkorders($id, $title)
{

       
       $sql = "SELECT
		workorder.id,
		workorder.wo_ref,
		item.description,
		item.long_description,
		workorder.units_reqd,
		workorder.units_issued,
		workorder.date_,
		workorder.required_by,
		workorder.released_date,
		workorder.closed,
		workorder.released,
		workorder.stock_id,
		unit.decimals
		FROM ".TB_PREF."workorders as workorder,"
			.TB_PREF."stock_master as item,"
			.TB_PREF."item_units as unit,"
			.TB_PREF."locations as location
		WHERE workorder.stock_id=item.stock_id 
			AND workorder.loc_code=location.loc_code
			AND item.units=unit.abbr
			AND workorder.released='1'
			AND workorder.closed='0'"
	    ." ORDER BY workorder.id DESC";
	
        $result = db_query($sql);
        $table = array();
        $rows = array();
        while ($myrow = db_fetch($result))
        {
            $temp = array();
            $temp[] = $myrow['units_reqd'];
            $temp[] = $myrow['required_by'];
            $rows[] = $temp;
        }
        $table = $rows;
        return $table;
}
    
    
//Current Inventory Value based on Location    
function location_wise_current_inventory_values(){
    
    $sql = "SELECT move.loc_code,loc.location_name,
	       ROUND(SUM(move.qty*stock.material_cost),3) as inv_value
           FROM ".TB_PREF."stock_moves as move,".TB_PREF."stock_master as stock,".TB_PREF."locations as loc
           WHERE move.stock_id = stock.stock_id
           AND move.loc_code = loc.loc_code
           GROUP by move.loc_code";
	
    $result = db_query($sql) or die(_('Error getting pie chart data'));
	
        $rows = array();
		$locations = array();
        while($r = db_fetch_assoc($result)) {
            $locations[] =  $r['location_name'];
            $rows[] = $r['inv_value'];
        }
       
        
        $table['rows'] = $rows;
        $table['locations'] = $locations;
        $jsonTable = $table;
        return $jsonTable;
}

//--------------------------------------------------------------

//Salesman Revenue     
function sales_revunue_values_by_salesperson(){

        $today =Today();
		$begin = begin_fiscalyear();
		$begin1 = date2sql($begin);
		$today1 = date2sql($today);
		
		$sql = "SELECT salesman.salesman_name ,ROUND(SUM((ov_amount + ov_discount) * rate * IF(trans.type = ".ST_CUSTCREDIT.", -1, 1)),3) AS total,
		trans.sales_person_id
		FROM ".TB_PREF."debtor_trans AS trans, 
			 ".TB_PREF."salesman AS salesman 
			WHERE trans.sales_person_id=salesman.salesman_code
			AND (trans.type = ".ST_SALESINVOICE." OR trans.type = ".ST_CUSTCREDIT.")
			AND tran_date >= '$begin1' AND tran_date <= '$today1' 
			GROUP by trans.sales_person_id 
			ORDER BY total DESC, trans.sales_person_id";
	    //display_error($sql);
        $result = db_query($sql) or die(_('Error getting pie chart data'));
	
        $rows = array();
		$salesmans = array();
        while($r = db_fetch_assoc($result)) {
            $salesmans[] =  $r['salesman_name'];
            $rows[] = $r['total'];
        }
		
        $table['rows'] = $rows;
        $table['salesmans'] = $salesmans;
        $jsonTable = $table;
        return $jsonTable;
}

//---------------------------------------

function get_pending_sales_delivery_count()
{
	$sql = "SELECT DISTINCT sorder.order_no
		FROM ".TB_PREF."sales_orders as sorder
		LEFT JOIN (SELECT trans_no_to, sum(amt) ord_payments FROM ".TB_PREF."cust_allocations WHERE trans_type_to=".ST_SALESORDER." GROUP BY trans_no_to)
			 allocs ON sorder.trans_type=".ST_SALESORDER." AND allocs.trans_no_to=sorder.order_no
		LEFT JOIN (SELECT order_, sum(prep_amount) inv_payments	FROM ".TB_PREF."debtor_trans WHERE type=".ST_SALESINVOICE." GROUP BY order_)
				 inv ON sorder.trans_type=".ST_SALESORDER." AND inv.order_=sorder.order_no,"
			.TB_PREF."sales_order_details as line, "
			.TB_PREF."debtors_master as debtor, "
			.TB_PREF."cust_branch as branch
			WHERE sorder.order_no = line.order_no
			AND sorder.trans_type = line.trans_type
			AND sorder.trans_type = '30'
			AND sorder.debtor_no = debtor.debtor_no
			AND sorder.branch_code = branch.branch_code
			AND debtor.debtor_no = branch.debtor_no
			AND line.qty_sent < line.quantity";
			
		$sql .= " GROUP BY sorder.order_no,
					sorder.debtor_no,
					sorder.branch_code,
					sorder.customer_ref,
					sorder.ord_date,
					sorder.deliver_to";	
		
    $result = db_query($sql,"an pending dispatches could not be retreived");
	return db_num_rows($result);
 
}

function get_pending_sales_invoices_count()
{

    $begin = begin_fiscalyear();
	$end   = end_fiscalyear();

	$sql = "SELECT DISTINCT trans.trans_no  FROM &TB_PREF&sales_orders as sorder, &TB_PREF&debtor_trans as trans, &TB_PREF&debtor_trans_details as line, &TB_PREF&debtors_master as debtor, &TB_PREF&cust_branch as branch 
	WHERE sorder.order_no = trans.order_ 
	AND trans.debtor_no = debtor.debtor_no 
	AND trans.type = 13 
	AND line.debtor_trans_no = trans.trans_no 
	AND line.debtor_trans_type = trans.type 
	AND trans.branch_code = branch.branch_code 
	AND trans.debtor_no = branch.debtor_no 
	AND line.qty_done < line.quantity";
	$sql .= " AND trans.tran_date >= '".date2sql($begin)."'";
	$sql .= " AND trans.tran_date <= '".date2sql($end)."'";
	$sql .= " GROUP BY trans.trans_no ";
	
    $result = db_query($sql,"an pending dispatches could not be retreived");
	return db_num_rows($result);
 
}



