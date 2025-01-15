<?php
/*-------------------------------------------------------+
| LTE Theme for FrontAccounting
| http://www.kvcodes.com/
+--------------------------------------------------------+
| Author: Kvvaradha  
| Email: admin@kvcodes.com
+--------------------------------------------------------+*/

class show_notification {

    function get_overdue_invoices() {
        global $path_to_root ;
        $today = date2sql(Today());

        $sql = "SELECT COUNT(debtor.debtor_no) AS Icount FROM ".TB_PREF."debtor_trans as trans, ".TB_PREF."debtors_master as debtor, ".TB_PREF."cust_branch as branch WHERE debtor.debtor_no = trans.debtor_no AND trans.branch_code = branch.branch_code AND trans.type = ".ST_SALESINVOICE." AND (trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount - trans.alloc) > ".FLOAT_COMP_DELTA." AND trans.due_date<'".$today."' ORDER BY due_date DESC ";

        $result = db_query($sql, "could not get sales type");
        $row = db_fetch_row($result);
        
        echo '<li class="dropdown messages-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <i class="fa fa-credit-card"></i>
              <span class="label label-success">'.$row[0].'</span>
            </a>
            <ul class="dropdown-menu">
              <li class="header">You have '.$row[0].' Overdue Payments </li>';
                if($row[0] > 0) {
            $sql = "SELECT  trans.due_date, debtor.debtor_no, debtor.name,(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount) AS total FROM ".TB_PREF."debtor_trans as trans, ".TB_PREF."debtors_master as debtor,".TB_PREF."cust_branch as branch WHERE debtor.debtor_no = trans.debtor_no AND trans.branch_code = branch.branch_code AND trans.type = ".ST_SALESINVOICE." AND (trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount - trans.alloc) > ".FLOAT_COMP_DELTA." AND trans.due_date<'".$today."' ORDER BY due_date DESC LIMIT 10";
            $result = db_query($sql);
            echo '<li>
                <!-- inner menu: contains the actual data -->
                <ul class="menu">';
                 while ($myrow = db_fetch($result)) {
                  echo '<li><!-- start message -->
                    <a href="'.$path_to_root.'/sales/customer_payments.php?customer_id='.$myrow['debtor_no'].'">                      
                      <h4>
                       '.substr($myrow['name'],0, 26).'
                        <small> '.price_format($myrow['total']).'</small>
                      </h4>
                    </a>
                  </li>';
              }
               echo '  <!-- end message -->                   
                </ul>
              </li>
              <li class="footer"><a href="'.$path_to_root.'/sales/inquiry/customer_inquiry.php?filterType=3">See All Payments</a></li>';             
            
          }
            echo '</ul>
          </li>';

        
    }

    function get_inventory_reorder() {
        global $path_to_root;
        include_once($path_to_root . "/includes/session.inc");
        include_once($path_to_root . "/includes/date_functions.inc");
        include($path_to_root . "/includes/ui.inc");

        $sql = "SELECT * FROM (SELECT ".TB_PREF."stock_master.stock_id, ".TB_PREF."stock_master.description, SUM(IF(".TB_PREF."stock_moves.stock_id IS NULL,0,".TB_PREF."stock_moves.qty)) AS QtyOnHand ,".TB_PREF."loc_stock.reorder_level FROM (".TB_PREF."stock_master, ".TB_PREF."stock_category,".TB_PREF."loc_stock) LEFT JOIN ".TB_PREF."stock_moves ON (".TB_PREF."stock_master.stock_id=".TB_PREF."stock_moves.stock_id) WHERE ".TB_PREF."stock_master.category_id=".TB_PREF."stock_category.category_id AND ".TB_PREF."stock_master.stock_id=".TB_PREF."loc_stock.stock_id AND (".TB_PREF."stock_master.mb_flag='B' OR ".TB_PREF."stock_master.mb_flag='M') AND ".TB_PREF."loc_stock.reorder_level!=0 GROUP BY ".TB_PREF."stock_master.category_id, ".TB_PREF."stock_category.description, ".TB_PREF."stock_master.stock_id, ".TB_PREF."stock_master.description ORDER BY QtyOnHand DESC LIMIT 10) reorder WHERE reorder.QtyOnHand < reorder.reorder_level";
        $result = db_query($sql, _('Could not get Items details'));
        echo '<li class="dropdown notifications-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <i class="fa fa-refresh"></i>
              <span class="label label-warning">'.db_num_rows($result).'</span>
            </a>
            <ul class="dropdown-menu">
              <li class="header">You have '.db_num_rows($result).' reorders</li>
              <li>
                <!-- inner menu: contains the actual data -->
                <ul class="menu">';
                while ($row=db_fetch($result)) {
                    echo '<li> <a href="'.$path_to_root.'/inventory/inquiry/stock_status.php?stock_id="'.$row["stock_id"].'"> '. substr($row["description"],0, 26) .' - '. price_format($row['QtyOnHand']) .'in Hand </a>  </li> ';
                   
                } 
                echo'                  
                </ul>
              </li>
              <li class="footer"><a href="'.$path_to_root.'/inventory/inquiry/stock_status.php">View all</a></li>
            </ul>
          </li>';
    }

  function get_supplier_payments() {
        
        global $path_to_root ;
        $today = date2sql(Today());
        $sql1 = "SELECT COUNT(s.supplier_id) AS Bcount FROM ".TB_PREF."supp_trans as trans, ".TB_PREF."suppliers as s WHERE s.supplier_id = trans.supplier_id AND trans.type = ".ST_SUPPINVOICE." AND due_date>=".$today." AND (ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount) - trans.alloc) > ".FLOAT_COMP_DELTA." "; 
        $sql1 .= " AND DATEDIFF('$today', trans.due_date) > 0  "; 
        $result1 = db_query($sql1); 
        $row = db_fetch_row($result1);

        // $next = date("Y/m/d", mktime(0, 0, 0, date("m")+1 , date("d"),date("Y")));
        // $month=date2sql($next);
        $sql1 = "SELECT   trans.tran_date, trans.due_date,s.supp_name,s.supplier_id,(trans.ov_amount + trans.ov_gst + trans.ov_discount) AS total FROM ".TB_PREF."supp_trans as trans, ".TB_PREF."suppliers as s WHERE s.supplier_id = trans.supplier_id AND trans.type = ".ST_SUPPINVOICE." AND due_date>=".$today." AND (ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount) - trans.alloc) > ".FLOAT_COMP_DELTA."";
        $sql1 .= " AND DATEDIFF('$today', trans.due_date) > 0 ORDER BY total DESC LIMIT 10";
        $result1 = db_query($sql1);

        /*    echo "<li>";
                while ($myrow1 = db_fetch($result1)) {
                    echo "<a href = '$path_to_root/purchasing/supplier_payment.php?supplier_id=".$myrow1['supplier_id']."' class='dropdown_content'><span class = 'pull-left'>".$myrow1['supp_name']. "</span><span class='pull-right'>".price_format($myrow1['total'])."</span></a>";
                }
            echo "</li>";*/


        echo '<li class="dropdown messages-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <i class="fa fa-send"></i>
              <span class="label label-danger">'.$row[0].'</span>
            </a>
            <ul class="dropdown-menu">
              <li class="header">You have '.$row[0].' Supplier Payment</li>
             <li> <ul class="menu" > ';

              while($myrow1 = db_fetch($result1)) {
              echo '<li><!-- start message -->
                    <a href="'.$path_to_root.'/purchasing/supplier_payment.php?supplier_id='.$myrow1['supplier_id'].'">                      
                      <h4>'.substr($myrow1['supp_name'],0, 24).'<small> '.price_format($myrow1['total']).'</small>
                      </h4>
                    </a>
                  </li>';
                }                  
               echo '</ul> </li> <li class="footer">  <a href="'.$path_to_root.'/purchasing/supplier_payment.php">View All</a> </li>
            </ul>
          </li>'; 
    }
}