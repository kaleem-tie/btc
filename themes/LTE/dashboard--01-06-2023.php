<?php

$page_security = 'SA_SETUPDISPLAY'; 

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_trans.inc");
include_once($path_to_root . "/themes/LTE/db/charts_db.inc");
include_once("kvcodes.inc");
//display_error(json_encode($_SESSION['wa_current_user']));
if(kv_get_option('hide_dashboard') == 0){ 
	$sql_cust_count = "SELECT COUNT(*) FROM `".TB_PREF."debtors_master`" ;
	$sql_cust_count_result = db_query($sql_cust_count, "could not get sales type");
	$cust_coubt = db_fetch_row($sql_cust_count_result);
	$sql_supp_count = "SELECT COUNT(*) FROM `".TB_PREF."suppliers`" ;
	$sql_supp_count_result = db_query($sql_supp_count, "could not get sales type");
	$sup_count= db_fetch_row($sql_supp_count_result);
	$class_balances = class_balances();
	if(kv_get_option('color_scheme') == 'dark' ){
		$color_scheme = '#ffffff'; 
	}else{
		$color_scheme= '#000000';
	}
	
	$receivables = get_gl_balance_from_to('', Today(), get_company_pref('debtors_act'));
	$payables = get_gl_balance_from_to('', Today(), get_company_pref('creditors_act'));

	$ShowReceivables = number_format2( empty($receivables) ? 0 : round2($receivables, user_price_dec()));
	$ShowPayables = number_format2( empty($payables) ? 0 : round2($payables, user_price_dec()));
?>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans&display=swap" rel="stylesheet">
    <style>
    html,
    body {
        font-family: 'Nunito Sans', sans-serif;
	}
	h3,h2 {
		margin-top: 10px !important;
		margin-bottom: 5px !important;
		color: #6c757d;
	}
    </style>
<link rel="stylesheet" href='<?php echo $path_to_root."/themes/".user_theme()."/css/morris.css"; ?>'>
<!-- <link rel="stylesheet" href='<?php //echo $path_to_root."/themes/".user_theme()."/css/grid.css"; ?>'> -->
<script src='<?php echo $path_to_root."/themes/".user_theme()."/js/jquery.min.js"; ?>'></script>
<script src="<?php echo $path_to_root."/themes/".user_theme()."/js/raphael-min.js"; ?>"></script>
<script src="<?php echo $path_to_root."/themes/".user_theme()."/js/morris.min.js"; ?>"></script>
<?php
$begin = fy('begin');
$begin_fy = $begin[3];

$end = fy('end');
$end_fy = $end[3];
?>
 <!-- Main content -->
    <section class="content">
<!----- Start --->
<div class="row">
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua">
            <div class="inner">
                
              <h3><?= //query_count("sales_orders", array("trans_type=32", "(ord_date between '".$begin_fy."' and '".$end_fy."')"));
               500 ?></h3>

              <p><?= "Quotations" ?></p>
            </div>
            <div class="icon">
              <i class="fa fa-list-ol"></i>
            </div>
           <!-- <a href="#" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a> -->
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green">
            <div class="inner">
              <h3><?= //query_count("sales_orders", array("trans_type=30", "(ord_date between '".$begin_fy."' and '".$end_fy."')")); 
              250 ?></h3>

              <p><?= "Orders" ?></p>
            </div>
            <div class="icon">
              <i class="fa fa-list"></i>
            </div>
           <!-- <a href="#" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a> -->
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow">
            <div class="inner">
              <h3><?= //query_count("debtor_trans", array("type=13", "(tran_date between '".$begin_fy."' and '".$end_fy."')")); 
              400 ?></h3>

              <p><?= "Dispatches" ?></p>
            </div>
            <div class="icon">
              <i class="fa fa-truck"></i>
            </div>
          <!--  <a href="#" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a> -->
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-red">
            <div class="inner">
              <h3><?= //query_count("debtor_trans", array("type=10", "(tran_date between '".$begin_fy."' and '".$end_fy."')")); 
              350 ?></h3>

              <p><?= "Invoices" ?></p>
            </div>
            <div class="icon">
              <i class="fa fa-list-alt"></i>
            </div>
           <!-- <a href="#" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a> -->
          </div>
        </div>
        <!-- ./col -->
      </div>

<!----- End ---->

      <!-- Info boxes -->
 <div class="row">
   <div class="col-md-3 col-sm-6 col-xs-12">
      <div class="card">
         <div class="card-body ds-info ds-card">
            <i class="fa fa-users ds-icon float-right ds-1"></i>
            <h5 class="text-uppercase mt-0"><?php echo _("CUSTOMERS"); ?></h5>
            <h2 class="my-2" id="active-users-count">
                <?php //echo $cust_coubt[0]; 
               echo  5200 ?></h2>
         </div>
      </div>
   </div>
   <div class="col-md-3 col-sm-6 col-xs-12">
      <div class="card">
         <div class="card-body ds-info ds-card">
            <i class="fa fa-bank ds-icon float-right ds-2"></i>
            <h5 class="text-uppercase mt-0"><?php echo _("CURRENT BALANCE"); ?></h5>
            <h2 class="my-2" id="active-users-count">
                <?php //echo kv_get_current_balance(); 
                echo 54216.146?></h2>
         </div>
      </div>
   </div>
   <div class="col-md-3 col-sm-6 col-xs-12">
      <div class="card">
         <div class="card-body ds-info ds-card">
            <i class="fa fa-truck ds-icon float-right ds-3"></i>
            <h5 class="text-uppercase mt-0"><?php echo _("SUPPLIERS"); ?></h5>
            <h2 class="my-2" id="active-users-count">
                <?php //echo $sup_count[0]; 
                echo 105 ?></h2>
         </div>
      </div>
   </div>
   <div class="col-md-3 col-sm-6 col-xs-12">
      <div class="card">
         <div class="card-body ds-info ds-card">
            <i class="fa fa-database ds-icon float-right ds-4"></i>
            <h5 class="text-uppercase mt-0"><?php echo _("ITEMS AND INVENTORY"); ?></h5>
            <?php 
               $sql_count_items = "SELECT COUNT(*) FROM `".TB_PREF."item_codes`"; 
               $sql_items_count_result = db_query($sql_count_items, "could not get sales type");
               $items_count= db_fetch_row($sql_items_count_result);
               ?>
            <h2 class="my-2" id="active-users-count"><?= //$items_count[0] 
            10000 ?></h2>
         </div>
      </div>
   </div>
</div>


    <div class="row">
        <div class="col-lg-6 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-orange">
            <div class="inner">
			<?php  
			global $path_to_root;
			$pending_dispatches = get_pending_sales_delivery_count();  ?>
              <h3><?= $pending_dispatches;?></h3>
            </div>
            <div class="icon">
              <i class="fa fa-truck" style="font-size:80px;color:red"></i>
			  
            </div>
            <a href="sales/inquiry/sales_orders_view.php?OutstandingOnly=1" class="small-box-footer">Pending Dispatches <i class="fa fa-arrow-circle-right"></i></a> 
			
          </div>
        </div>
		
		<div class="col-lg-6 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-red">
            <div class="inner">
             <?php  
			global $path_to_root;
			$pending_invoices = get_pending_sales_invoices_count();  ?>
            <h3><?= $pending_invoices;?></h3>
            </div>
            <div class="icon">
              <i class="fa fa-list-alt" style="font-size:80px;color:red"></i>
            </div>
            <a href="sales/inquiry/sales_deliveries_view.php?OutstandingOnly=1" class="small-box-footer">Pending Invoices <i class="fa fa-arrow-circle-right"></i></a> 
          </div>
        </div>
		
	  <style>	
	  h3 {
       text-align: center;
      }
      </style>
		
    </div>

<!-- <div class="row">
   
   <div class="col-md-7 col-sm-12 col-xs-12">
      <div class="card">
         <div class="card-body">
            <h3 class="box-title"><?php // echo _("Revenue"); ?> </h3>
           <!-- <div id="chart13"></div> 
         </div>
      </div>
   </div>
   <div class="col-md-5 col-sm-12 col-xs-12">
      <div class="card">
         <div class="card-body">
            <h3 class="box-title"><?php // echo _("Product Overview Pie Chart"); ?> </h3>
            <!-- <div id="chart15"></div>
         </div>
      </div>
   </div>
</div> -->
<?php
$begin = fy('begin');
$begin_fy = $begin[3];

$end = fy('end');
$end_fy = $end[3];
?>
<!--- sales dashboards here---->
<!--<div class="row">
   <div class="col-md-7 col-sm-12 col-xs-12">
      <div class="card">
         <div class="card-body">
            <h3 class="box-title"><?php // echo _("Product Overview"); ?> </h3>
            <div id="chartbar"></div>
         </div>
      </div>
   </div>
   <div class="col-md-5 col-sm-6 col-xs-12">
      <div class="card">
         <div class="card-body">
            <h3 class="box-title"><?php // echo _("Sales"); ?> </h3>
            <div id="chart11"></div>
         </div>
      </div>
   </div>
</div>  -->

<!-- <div class="row">
   <div class="col-md-8 col-sm-12 col-xs-12">
      <div class="card">
         <div class="card-body">
            <h3 class="box-title"><?php // echo _("Comparison"); ?> </h3>
            <!-- <canvas id="barChart"></canvas> 
            <div id="chart12"></div>
         </div>
      </div>
   </div>
   
   <div class="col-md-4 col-sm-12 col-xs-12">
   <div class="card">
   <div class="card-body">
      <h3 class="box-title"><?php // echo _("Employee Overview"); ?> </h3>
      <!-- Info Boxes Style 2
      <div class="info-box bg-yellow">
         <span class="info-box-icon"><i class="fa fa-user-plus"></i></span>
         <div class="info-box-content">
            <span class="info-box-text">Employee Present</span>
            <span class="info-box-number">8</span>
            <div class="progress">
               <div class="progress-bar" style="width: 80%"></div>
            </div>
            <span class="progress-description">
            
            </span>
         </div>
         <!-- /.info-box-content 
      </div>
      <!-- /.info-box 
      <div class="info-box bg-green">
         <span class="info-box-icon"><i class="fa fa-user-times"></i></span>
         <div class="info-box-content">
            <span class="info-box-text">Employee Leave</span>
            <span class="info-box-number">2</span>
            <div class="progress">
               <div class="progress-bar" style="width: 20%"></div>
            </div>
            <span class="progress-description">
            
            </span>
         </div>
         <!-- /.info-box-content 
      </div>
      <!-- /.info-box
      <div class="info-box bg-red">
         <span class="info-box-icon"><i class="fa fa-circle"></i></span>
         <div class="info-box-content">
            <span class="info-box-text">Employee Working</span>
            <span class="info-box-number">8</span>
            <div class="progress">
               <div class="progress-bar" style="width: 80%"></div>
            </div>
            <span class="progress-description">
            
            </span>
         </div>
         <!-- /.info-box-content
      </div>
      <!-- /.info-box 
      <div class="info-box bg-aqua">
         <span class="info-box-icon"><i class="fa fa-users"></i></span>
         <div class="info-box-content">
            <span class="info-box-text">Total Employee</span>
            <span class="info-box-number">10</span>
            <div class="progress">
               <div class="progress-bar" style="width: 100%"></div>
            </div>
            <span class="progress-description">
            
            </span>
         </div>
         <!-- /.info-box-content
      </div>
      <!-- /.info-box
   </div>
</div>
	</div>
	</div> -->
	
	
	
	
	
	
	
    
    
    <div class="row">
			 <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title"><?php echo _("Monthly Sales Orders"); ?> </h3>
                    <div id="toptensoa"></div>
                </div>
            </div>
        </div>
    
    <div class="col-md-6 col-sm-12 col-xs-12">
        <div class="card">
         <div class="card-body">
            <h3 class="box-title"><?php  echo _("Monthly Sales Orders"); ?> </h3>
          <div id="sales_orders_linechart"></div> 
         </div>
       </div>
      </div>
    </div>    
        
        
     <div class="row">    
         <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title"><?php echo _("Monthly Sales Dispatches"); ?> </h3>
                    <div id="toptensaledisp"></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-sm-12 col-xs-12">
         <div class="card">
          <div class="card-body">
            <h3 class="box-title"><?php  echo _("Monthly Sales Dispatches"); ?> </h3>
           <div id="sales_dispatches_linechart"></div> 
          </div>
        </div>
       </div>
    </div>       
        
    <div class="row">  
        <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title"><?php echo _("Monthly Sales Invoices"); ?> </h3>
                    <div id="toptensaleinvcount"></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-sm-12 col-xs-12">
         <div class="card">
          <div class="card-body">
            <h3 class="box-title"><?php  echo _("Monthly Sales Invoices"); ?> </h3>
           <div id="sales_invoices_linechart"></div> 
          </div>
        </div>
       </div>
    </div>
	
	<div class="row">
			 <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title"><?php echo _("Monthly Sales Quotations"); ?> </h3>
                    <div id="toptensq"></div>
                </div>
            </div>
        </div>
        
       <div class="col-md-6 col-sm-12 col-xs-12">
        <div class="card">
         <div class="card-body">
            <h3 class="box-title"><?php  echo _("Monthly Sales Quotations"); ?> </h3>
          <div id="sales_quotations_linechart"></div> 
         </div>
       </div>
      </div> 
    </div>
	
	
	<div class="row">
		<div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title"><?php echo _("Monthly Sales Inquiries"); ?> </h3>
                    <div id="toptensiq"></div>
                </div>
            </div>
        </div>
        
       <div class="col-md-6 col-sm-12 col-xs-12">
        <div class="card">
         <div class="card-body">
            <h3 class="box-title"><?php  echo _("Monthly Sales Inquiries"); ?> </h3>
          <div id="sales_inquiries_linechart"></div> 
         </div>
       </div>
      </div>
       
	</div>	
    
     <div class="row">    
        <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title">
					<?php echo _("Monthly Sales Enquiries and Quotations"); ?> </h3>
                    <div id="topsalesenquote"></div>
                </div>
            </div>
        </div>
        
        
        
        <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title">
					<?php echo _("Monthly Sales Quotations and Orders"); ?> </h3>
                    <div id="topsalesquoteorder"></div>
                </div>
            </div>
        </div>
        
         <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title">
                        <?php echo _("Monthly Sales Enquiries,Quotations and Orders"); ?> </h3>
                    <div id="topsaleeqo"></div> 
                </div>
            </div>
        </div>
        
       </div>
       
        
     <div class="row">
       <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title"><?php echo _("Monthly Sales Order Value"); ?> </h3>
                    <div id="toptensov"></div>
                </div>
            </div>
        </div>
       
          <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title"><?php echo _("Monthly Sales Dispatch Value"); ?> </h3>
                    <div id="toptensdv"></div>
                </div>
            </div>
        </div>
        
          <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title"><?php echo _("Monthly Sales Invoice Value"); ?> </h3>
                    <div id="toptensaleinv"></div>
                </div>
            </div>
        </div>
		
		<div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title"><?php echo _("Monthly Sales Quotation Value"); ?> </h3>
                    <div id="toptensqv"></div>
                </div>
            </div>
        </div>
        
        <!--
          <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title">
					<?php //echo _("Monthly Sales Quotations and Orders Value"); ?> </h3>
                    <div id="topsalesquoteordervalue"></div>
                </div>
            </div>
        </div>
    </div>
	-->
    
	
	
	 <div class="row">
        <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title"><?php echo _("Monthly Purchase Orders"); ?> </h3>
                    <div id="toptenpoa"></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                   <h3 class="box-title"><?php echo _("Monthly Purchase GRN / MRN"); ?> </h3>
                    <div id="toptengrn"></div>
                </div>
            </div>
        </div> 
  
      <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title"><?php echo _("Monthly Purchase Invoices"); ?> </h3>
                    <div id="toptenpurchaseinv"></div>
                </div>
            </div>
      </div>
	  
	    <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                   <h3 class="box-title"><?php echo _("Monthly Purchase Quotations"); ?> </h3>
                    <div id="toptenpqa"></div>
                </div>
            </div>
        </div>  
		
	    <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                   <h3 class="box-title"><?php echo _("Monthly Purchase Inquiries"); ?> </h3>
                    <div id="toptenpea"></div>
                </div>
            </div>
        </div>     	
        
        <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title">
					<?php echo _("Monthly Purchase Enquiries and Quotations"); ?> </h3>
                    <div id="toppurchasesenquote"></div>
                </div>
        </div>
       </div> 
   
        <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title">
					<?php echo _("Monthly Purchase Quotations and Orders"); ?> </h3>
                    <div id="toppurchasesquoteorder"></div>
                </div>
            </div>
        </div>
    
         <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title"><?php echo _("Monthly Purchases Order Value"); ?> </h3>
                    <div id="toptenpov"></div>
                </div>
            </div>
        </div>
    
    </div>	
	
	

<div class="row">
   <div class="col-md-8 col-sm-12 col-xs-12">
      <div class="box box-info">
         <div class="box-header with-border">
            <h3 class="box-title">Users</h3>
            <div class="box-tools pull-right">
               <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
               </button>
               <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
            </div>
         </div>
         <!-- /.box-header -->
         <div class="box-body">
            <div class="table-responsive">
               <table class="table no-margin">
                  <thead>
                     <tr>
                        <th>User ID</th>
                        <th>UserName</th>
                        <th>Status</th>
                        <th>Action</th>
                     </tr>
                  </thead>
                  <tbody>
                <?php
                $users_data = query_data("users", array("inactive=0"));
              
                foreach($users_data as $user_data){
                  user_data($user_data['user_id'], $user_data['real_name']);
                }
					?>
                  </tbody>
               </table>
            </div>
            <!-- /.table-responsive -->
         </div>
         <!-- /.box-body -->
         <div class="box-footer clearfix">
            <a href="javascript:void(0)" class="btn btn-sm btn-info btn-flat pull-left">New Chat</a>
         </div>
         <!-- /.box-footer -->
      </div>
   </div>
   <!-- <div class="col-md-4 col-sm-12 col-xs-12">
      <div class="box box-warning direct-chat direct-chat-warning">
         <div class="box-header with-border">
            <h3 class="box-title">Direct Chat</h3>
            <div class="box-tools pull-right">
               <span data-toggle="tooltip" title="" class="badge bg-yellow" data-original-title="3 New Messages">3</span>
               <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
               </button>
               <button type="button" class="btn btn-box-tool" data-toggle="tooltip" title="" data-widget="chat-pane-toggle" data-original-title="Contacts">
               <i class="fa fa-comments"></i></button>
               <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i>
               </button>
            </div>
         </div>
         <!-- /.box-header 
         <div class="box-body">
            <!-- Conversations are loaded here 
            <div class="direct-chat-messages">
               <!-- Message. Default to the left
               <div class="direct-chat-msg">
                  <div class="direct-chat-info clearfix">
                     <span class="direct-chat-name pull-left">Alexander Pierce</span>
                     <span class="direct-chat-timestamp pull-right">23 Jan 2:00 pm</span>
                  </div>
                  <!-- /.direct-chat-info 
                  <img class="direct-chat-img" src="https://adminlte.io/themes/AdminLTE/dist/img/user1-128x128.jpg" alt="message user image">
                  <!-- /.direct-chat-img 
                  <div class="direct-chat-text">
                     Is this template really for free? That's unbelievable!
                  </div>
                  <!-- /.direct-chat-text 
               </div>
               <!-- /.direct-chat-msg 
               <!-- Message to the right 
               <div class="direct-chat-msg right">
                  <div class="direct-chat-info clearfix">
                     <span class="direct-chat-name pull-right">Sarah Bullock</span>
                     <span class="direct-chat-timestamp pull-left">23 Jan 2:05 pm</span>
                  </div>
                  <!-- /.direct-chat-info
                  <img class="direct-chat-img" src="https://adminlte.io/themes/AdminLTE/dist/img/user3-128x128.jpg" alt="message user image">
                  <!-- /.direct-chat-img 
                  <div class="direct-chat-text">
                     You better believe it!
                  </div>
                  <!-- /.direct-chat-text 
               </div>
               <!-- /.direct-chat-msg
               <!-- Message. Default to the left 
               <div class="direct-chat-msg">
                  <div class="direct-chat-info clearfix">
                     <span class="direct-chat-name pull-left">Alexander Pierce</span>
                     <span class="direct-chat-timestamp pull-right">23 Jan 5:37 pm</span>
                  </div>
                  <!-- /.direct-chat-info 
                  <img class="direct-chat-img" src="https://adminlte.io/themes/AdminLTE/dist/img/user1-128x128.jpg" alt="message user image">
                  <!-- /.direct-chat-img 
                  <div class="direct-chat-text">
                     Working with AdminLTE on a great new app! Wanna join?
                  </div>
                  <!-- /.direct-chat-text 
               </div>
               <!-- /.direct-chat-msg
               <!-- Message to the right 
               <div class="direct-chat-msg right">
                  <div class="direct-chat-info clearfix">
                     <span class="direct-chat-name pull-right">Sarah Bullock</span>
                     <span class="direct-chat-timestamp pull-left">23 Jan 6:10 pm</span>
                  </div>
                  <!-- /.direct-chat-info 
                  <img class="direct-chat-img" src="https://adminlte.io/themes/AdminLTE/dist/img/user3-128x128.jpg" alt="message user image">
                  <!-- /.direct-chat-img 
                  <div class="direct-chat-text">
                     I would love to.
                  </div>
                  <!-- /.direct-chat-text 
               </div>
               <!-- /.direct-chat-msg 
            </div>
            <!--/.direct-chat-messages
            <!-- Contacts are loaded here 
            <div class="direct-chat-contacts">
               <ul class="contacts-list">
                  <li>
                     <a href="#">
                        <img class="contacts-list-img" src="https://adminlte.io/themes/AdminLTE/dist/img/user1-128x128.jpg" alt="User Image">
                        <div class="contacts-list-info">
                           <span class="contacts-list-name">
                           Count Dracula
                           <small class="contacts-list-date pull-right">2/28/2015</small>
                           </span>
                           <span class="contacts-list-msg">How have you been? I was...</span>
                        </div>
                        <!-- /.contacts-list-info 
                     </a>
                  </li>
                  <!-- End Contact Item 
                  <li>
                     <a href="#">
                        <img class="contacts-list-img" src="https://adminlte.io/themes/AdminLTE/dist/img/user7-128x128.jpg" alt="User Image">
                        <div class="contacts-list-info">
                           <span class="contacts-list-name">
                           Sarah Doe
                           <small class="contacts-list-date pull-right">2/23/2015</small>
                           </span>
                           <span class="contacts-list-msg">I will be waiting for...</span>
                        </div>
                        <!-- /.contacts-list-info 
                     </a>
                  </li>
                  <!-- End Contact Item 
                  <li>
                     <a href="#">
                        <img class="contacts-list-img" src="https://adminlte.io/themes/AdminLTE/dist/img/user3-128x128.jpg" alt="User Image">
                        <div class="contacts-list-info">
                           <span class="contacts-list-name">
                           Nadia Jolie
                           <small class="contacts-list-date pull-right">2/20/2015</small>
                           </span>
                           <span class="contacts-list-msg">I'll call you back at...</span>
                        </div>
                        <!-- /.contacts-list-info
                     </a>
                  </li>
                  <!-- End Contact Item 
                  <li>
                     <a href="#">
                        <img class="contacts-list-img" src="https://adminlte.io/themes/AdminLTE/dist/img/user5-128x128.jpg" alt="User Image">
                        <div class="contacts-list-info">
                           <span class="contacts-list-name">
                           Nora S. Vans
                           <small class="contacts-list-date pull-right">2/10/2015</small>
                           </span>
                           <span class="contacts-list-msg">Where is your new...</span>
                        </div>
                        <!-- /.contacts-list-info 
                     </a>
                  </li>
                  <!-- End Contact Item 
                  <li>
                     <a href="#">
                        <img class="contacts-list-img" src="https://adminlte.io/themes/AdminLTE/dist/img/user6-128x128.jpg" alt="User Image">
                        <div class="contacts-list-info">
                           <span class="contacts-list-name">
                           John K.
                           <small class="contacts-list-date pull-right">1/27/2015</small>
                           </span>
                           <span class="contacts-list-msg">Can I take a look at...</span>
                        </div>
                        <!-- /.contacts-list-info 
                     </a>
                  </li>
                  <!-- End Contact Item 
                  <li>
                     <a href="#">
                        <img class="contacts-list-img" src="https://adminlte.io/themes/AdminLTE/dist/img/user8-128x128.jpg" alt="User Image">
                        <div class="contacts-list-info">
                           <span class="contacts-list-name">
                           Kenneth M.
                           <small class="contacts-list-date pull-right">1/4/2015</small>
                           </span>
                           <span class="contacts-list-msg">Never mind I found...</span>
                        </div>
                        <!-- /.contacts-list-info 
                     </a>
                  </li>
                  <!-- End Contact Item 
               </ul>
               <!-- /.contatcts-list 
            </div>
            <!-- /.direct-chat-pane 
         </div>
         <!-- /.box-body 
         <div class="box-footer">
            <form action="#" method="post">
               <div class="input-group">
                  <input type="text" name="message" placeholder="Type Message ..." class="form-control">
                  <span class="input-group-btn">
                  <button type="button" class="btn btn-warning btn-flat">Send</button>
                  </span>
               </div>
            </form>
         </div>
         <!-- /.box-footer
      </div>
   </div>
</div> -->

		
		
	
	
	<?php 
	// Payments
		 $Sales_payment_5 = Top_five_invoices(12); 
		 $payment_lines= '';
		 foreach($Sales_payment_5 as $payment){
			$payment_lines .='<tr><th scope="row">'.$payment['reference'].'</th> <td>'.$payment['name'].'</td> <td><span class="label-info" style="padding:0 5px;">'.$payment['curr_code'].'</span></td> 
			<td style="text-align:right">'.number_format($payment['TotalAmount'],3,'.','') .'</td> </tr>'; 
		 }
		echo'<div class="col-sm-6" id="item-16"><div class="box"> <div class="box-header with-border" data-background-color="orange"><h3 class="box-title">'._('Payments').'</h3><div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								  </div></div><div class="box-body table-responsive"> <table class="table "><thead> <tr> <th>#</th>
								  <th>'._('Name').'</th>
								  
								  <th>'._('Currency').'</th>
								  <th style="text-align:right">'._('Total Amount').'</th>
								  </tr> </thead><tbody>'.$payment_lines.'</tbody></table></div></div></div> '; 
		//Sales Invoice
		$Sales_invoice_5 = Top_five_invoices(); 
		$invoice_lines = ''; 
		foreach($Sales_invoice_5 as $invoice){
			$invoice_lines .= '<tr><th scope="row">'.$invoice['reference'].'</th> <td>'.$invoice['name'].'</td> <td><span class="label-info" style="padding:0 5px;">'.sql2date($invoice['due_date']).'</span></td> <td style="text-align:right">'.number_format($invoice['TotalAmount'],3,'.','').'</td> </tr>';
		}
		echo'<div class="col-sm-6" id="item-16"><div class="box"> <div class="box-header with-border" data-background-color="orange"><h3 class="box-title">'._('Sales Invoices').'</h3><div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								  </div></div><div class="box-body table-responsive"><table class="table "><thead> <tr> <th>#</th><th>'._('Name').'</th> <th>'._('Date').'</th> <th style="text-align:right">'._('Total Amount').'</th></tr> </thead><tbody>'.$invoice_lines.'</tbody></table></div></div></div>';
		 ?>
		 
		 
		 <div class="col-md-6 col-sm-12">
							<div class="box">
								<div class="box-header with-border">
								  <h3 class="box-title"><?php echo _("Class Balances"); ?> </h3>
								  <div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								  </div>
								</div>
								<div class="box-body chart-responsive">
									<?php  kv_gl_top(); 	?>
								</div>
								<!-- /.box-body -->
							</div>								
						</div>
		 
					<!--<div class="row"> -->
						<div class="col-md-6 col-sm-12">
							
							<div class="box">
								<div class="box-header with-border">
								  <h3 class="box-title"><?php echo _("Overdue Sales Invoices"); ?> </h3>
								  <div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								  </div>
								</div>
								
								<div class="box-body chart-responsive">
									<?php  kv_customer_trans(); 	?>
								</div>
								<!-- /.box-body -->
							</div>
						</div>
						
						
						
					
						

						<div class="col-md-6 col-sm-12">
							<div class="box">
								<div class="box-header with-border">
								  <h3 class="box-title"><?php echo _("Bank Account Balances"); ?></h3>
								  <div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								  </div>
								</div>
								<div class="box-body chart-responsive">
									<?php  kv_bank_balance(); 	?>
								</div>
								<!-- /.box-body -->
							</div>
						</div>
					<!--</div>

					<div class="row">-->
						<div class="col-md-6 col-sm-12">
							<div class="box">
								<div class="box-header with-border">
								  <h3 class="box-title"><?php echo _("Overdue Recurrent Sales Invoices"); ?></h3>
								  <div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								  </div>
								</div>
								<div class="box-body chart-responsive">
									<?php  kv_customer_recurrent_invoices(); 	?>
								</div>
								<!-- /.box-body -->
							</div>
						</div>

					
					<!--</div>

					<div class="row"> -->
						
						<div class="col-md-6 col-sm-12">
							<div class="box">
								<div class="box-header with-border">
								  <h3 class="box-title"><?php echo _("Overdue Purchase Invoices"); ?></h3>
								  <div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								  </div>
								</div>
								<div class="box-body chart-responsive">
									<?php  kv_supplier_trans(); 	?>
								</div>
								<!-- /.box-body -->
                     </div>	
                     						
						</div>
						
						 <div class="col-md-6 col-sm-12">
						<div class="box">
							<div class="box-header with-border">
								  <h3 class="box-title">
								  <?php echo _("Pending Purchase Order Approvals"); ?></h3>
								<div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								</div>
							</div>
								<div class="box-body chart-responsive">
									<?php  purchase_order_pending_approvals(); 	?>
								</div>
                        </div>					
					</div>
					
					 <div class="col-md-6 col-sm-12">
						<div class="box">
							<div class="box-header with-border">
								  <h3 class="box-title">
								  <?php echo _("Outstanding Purchase Orders"); ?></h3>
								<div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								</div>
							</div>
								<div class="box-body chart-responsive">
									<?php  outstanding_purchase_orders(); 	?>
								</div>
                        </div>					
					</div>
						
						<div class="col-md-6 col-sm-12">
							<div class="box">
								<div class="box-header with-border">
								  <h3 class="box-title"><?php echo _("Top 10 Selling Items and Inventory"); ?></h3>
								  <div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								  </div>
								</div>
								<div class="box-body chart-responsive">
									<?php  kv_stock_top(); 	?>
								</div>
								<!-- /.box-body -->
							</div>								
						</div>
						
						<div class="col-md-6 col-sm-12">
							<div class="box">
								<div class="box-header with-border">
								  <h3 class="box-title"><?php echo _("Most Stagnant Items"); ?></h3>
								  <div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								  </div>
								</div>
								<div class="box-body chart-responsive">
									<?php $th = array(_("Item Code"), _("Description"),_("Stock"), _("Sales Qty"), _("Last Sale"));

									start_table(TABLESTYLE, "width='100%'");
									table_header($th);
									$stagnants = most_stagnant_items(Today()); 
									if(count($stagnants)>0){
										foreach($stagnants as $single) {
											echo '<tr><td>'.$single[0].'</td><td>'.$single[1].'</td> <td> '.$single[2].'</td><td>'.$single[3].'</td><td> '.$single[4].'</td></tr>';
										}
									} else {
										start_row();
										echo '<td colspan="5"> No Stagnant items </td>';
										end_row();
									}
									end_table(); ?>
								</div>
								<!-- /.box-body -->
							</div>								
						</div>
					
				<div class="row">				
			<div class="col-md-6 col-sm-12">
							<div class="box">
								<div class="box-header with-border">
								  <h3 class="box-title"><?php echo _("Sales Revenue Based on Different Offer Periods"); ?></h3>
								  <div class="box-tools pull-left">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								  </div>
								</div>
								<div class="box-body chart-responsive">
									<?php  sales_revenue_based_on_inventory_offers(); 	?>
								</div>
								<!-- /.box-body -->
							</div>								
				</div>	
				
				
			
				<div class="col-md-6 col-sm-12">
				    <div class="box">
					    <div class="box-header with-border">
								  <h3 class="box-title">
								  <?php echo _("Top 10 Salesman (Monthly, Yearly) "); ?></h3>
						<div class="box-tools pull-right">
							<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
							</button>
						</div>
					    </div>
					    <div class="box-body chart-responsive">
							<?php  kv_salesman_top(); 	?>
					    </div>	
				    </div>								
			    </div>
			    
		
		
	<div class="col-md-5 col-sm-12 col-xs-12">
      <div class="card">
         <div class="card-body">
            <h3 class="box-title">
			<?php  echo _("Sales Revenue by Sales Person"); ?> </h3>
            <div id="sales_revenue_by_sales_person"></div>
         </div>
      </div>
   </div>	    	
    
  
   <div class="col-md-5 col-sm-12 col-xs-12">
      <div class="card">
         <div class="card-body">
            <h3 class="box-title">
			<?php  echo _("Current Inventory Value based on Location"); ?> </h3>
            <div id="location_wise_current_inventory_value"></div>
         </div>
      </div>
   </div>
 </div> 
 
 
     
     
     <div class="row">
     	<div class="col-md-6 col-sm-12">
							<div class="box">
								<div class="box-header with-border">
								  <h3 class="box-title"><?php echo _("Average Daily Sales"); ?></h3>
								  <div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								  </div>
								</div>
								<div class="box-body chart-responsive">
									<?php  kv_weekly_sales(); 	?>
								</div>
								<!-- /.box-body -->
							</div>
						</div>
     
    
                <div class="col-md-6 col-sm-12">
							<div class="box">
								<div class="box-header with-border">
								  <h3 class="box-title"><?php echo _("Daily & Weekly Sales"); ?></h3>
								  <div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
								  </div>
								</div>
								<div class="box-body chart-responsive">
									<?php  kv_Daily_sales(); 	?>
								</div>
								<!-- /.box-body -->
							</div>
				</div>
	</div>
	
	<div class="row">
	<div class="col-md-6 col-sm-12">
							
				<div class="box">
						<div class="box-header with-border">
								  <h3 class="box-title">
								  <?php echo _("Completed Work Order Details"); ?> </h3>
							<div class="box-tools pull-right">
									<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
									</button>
							</div>
							</div>
								
							<div class="box-body chart-responsive">
									<?php  kv_completed_work_order_details(); 	?>
							</div>
								
				</div>
			</div>				
						
     </div> 
		
	<div class="row">
 	        <div class="col-md-8 col-sm-12">
			    <div class="box">
					<div class="box-header with-border">
						 <h3 class="box-title"><?php echo _("Outstanding & Upcoming Item Deliveries"); ?> </h3>
						<div class="box-tools pull-right">
							<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
							</button>
						</div>
					</div>
								
					<div class="box-body chart-responsive">
						<?php  kv_outstanding_upcoming_item_deliveries(); 	?>
					</div>
								
			    </div>
			</div>
	</div>
			
			
    </section>

<?php 

$protocol = $protocol = $_SERVER['REQUEST_SCHEME'] . '://'; //stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
$actual_link = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

$actual_link =  strtok($actual_link, '?');
$actual_link = str_replace("index.php", "", $actual_link);
 ?>

<script>
$(document).ready(function(){
        $('.table').DataTable();
    });
    $('.filter-osi').keyup(function(){
        this_val = $(this).val();
        if(this_val !== '') {
            $.ajax({
                url: '<?php echo $path_to_root; ?>/themes/ott/includes/ajax.php?type=ajax_osi',
                type: "POST",
                data: {this_val:this_val},
                cache:false,
                success:function(data){
                    $(".osi-main").hide();
                    $('.osi-ajax').html(data).show();
                }
            })
        }else {
            $(".osi-main").show();
            $(".osi-ajax").hide();
        }
    })
    $('.frequency').change(function(){
        this_val = $(this).val();
        
        if(this_val == 'daily') {
            $('.recur-every1').show();
            $('.recur-every').html('days').show();
        }
        if(this_val == 'weekly') {
            $('.recur-every1').show();
            $('.recur-every').html('weeks').show();
        }
        if(this_val == 'monthly') {
            $('.recur-every1').show();
            $('.recur-every').html('months').show();
        }
        if(this_val == 'yearly') {
            $('.recur-every1').hide();
            $('.recur-every').html('').show();
        }
        if(this_val == 'once') {
            $('.recur-every1').hide();
            $('.recur-every').html('').show();
        }
    })
        function delete_task(task_id) {
            if(confirm("Are you sure you want to delete it.")){
                $.ajax({
                    url: '<?php echo $path_to_root; ?>/themes/ott/db/chats.php?type=delete_task',
                    data:{task_id:task_id},
                    type:"post",
                    cache:false,
                    success:function(data) {
                        console.log(data);
                        $('.hide-task'+task_id).hide();
                    }
                })
            }
        }
    
    $(".security-role").change(function() {
        var option = $(this).val();
        alert(option);
        $.ajax({
            type: "GET",
            url: "<?php echo $path_to_root; ?>/themes/ott/includes/ajax.php?role=" + option,
            success: function(data) {
                $('.employee-list').html(data).show();
            }
        });
    });
    $(".add-task-reminder").submit(function(e) {
        e.preventDefault();
        var form = $(this);
        // alert(option);
        $.ajax({
            type: "POST",
            url: "<?php echo $path_to_root; ?>/themes/ott/db/chats.php?add-tr=target",
            data: form.serialize(),
            success: function(data) {
                $('#add-reminder').modal('hide');
                window.location.reload();
            }
        });
    });
    $('.task-done').click(function(){
        data_id = $(this).attr('data-id');
        $.ajax({
            type: "POST",
            url: "<?php echo $path_to_root; ?>/themes/ott/db/chats.php?done-tr=target",
            data: {data_id:data_id},
            success: function(data) {
                alert("Status changed successfully!");
                window.location.reload();
            }
        })
    })
if($("#donut-Taxes").length){ //  #################  
  var Tax_Donut_Chart = Morris.Donut({
    element: 'donut-Taxes',
    behaveLikeLine: true,
    parseTime : false,
    data: [{"value":"","label":"", labelColor: '<?php echo $color_scheme; ?>'}],  
    colors: [ '#f26c4f', '#00a651', '#00bff3', '#0072bc','#ff6264', '#455064',  '#707f9b', '#b92527',  '#242d3c', '#d13c3e', '#d13c3e',  '#ff6264', '#ffaaab', '#b92527'],
    redraw: true,
  });
  $("#TaxPeriods").on("change", function(){
       var option = $(this).val(); 
       $.ajax({
          type: "POST",
          url: "<?php echo $actual_link; ?>themes/LTE/includes/ajax.php?Tax_chart="+option,
          data: 0,
          dataType: 'json',
          success: function(taxdata){      
        		//var grandtotal = data.grandtotal;	 // delete data.grandtotal;	  //delete data[4];
        		console.log(taxdata);
            Tax_Donut_Chart.setData(taxdata);
        		// var arr = $.parseJSON(data);  //alert(data.grandtotal);	  //$("#GrandTaxTotal").html(grandtotal);
            /* setCookie('numbers',data,3); $('.flash').show(); $('.flash').html("Template Updated")*/
          }
    });
  }); 
 }

 if($("#expenses_chart").length){ //   ######### 
	 var Expenses_Bar_Chart = Morris.Bar({
	  element: 'expenses_chart',
	  behaveLikeLine: true,
	  parseTime : false,
	  data: [{ "y": "Nothing" , "a" : "0", "labelColor": '<?php echo $color_scheme; ?>'} ],
	  xkey: 'y',
	  ykeys: ['a'],
	  labels: ['Expenses' ],
	  barColors: ['#f26c4f', '#00a651', '#00bff3', '#0072bc', '#707f9b', '#455064', '#242d3c', '#b92527', '#d13c3e', '#ff6264', '#ffaaab'],
	  redraw: true
	});
	 $("#ExpensesPeriods").on("change", function(){
		 var option = $(this).val(); 
		 $.ajax({
				type: "POST",
				url: "<?php echo $actual_link; ?>themes/LTE/includes/ajax.php?Expense_chart="+option,
				data: 0,
				dataType: 'json',
				success: function(data){
				  console.log(data);
				  Expenses_Bar_Chart.setData(data);
				   /* setCookie('numbers',data,3); $('.flash').show(); $('.flash').html("Template Updated")*/
				}
		  });
	  }); 
  }

    //  ##########################################
   if($("#Class_Balance_chart").length){
      var Line_Chart = Morris.Bar({
        element: 'Class_Balance_chart',
        behaveLikeLine: true,
        parseTime : false,
        data: [ <?php foreach($class_balances as $balance) { echo " { class: '".$balance['class_name']."', value: ".abs($balance['total'])." }," ; } ?> ],    
        xkey: 'class',
        ykeys: ['value'],
        labels: ['Value'],
        lineColors: ['#f26c4f', '#00a651', '#00bff3', '#0072bc', '#707f9b', '#455064', '#242d3c', '#b92527', '#d13c3e', '#ff6264', '#ffaaab'],
        redraw: true,
        pointFillColors: ['#455064']
      });

      $("#ClassPeriods").on("change", function(){
         var type = $(this).val(); 
         $.ajax({
            type: "POST",
            url: "<?php echo $actual_link; ?>themes/LTE/includes/ajax.php?Line_chart="+type,
            data: 0,
            dataType: 'json',
            success: function(data){    console.log(data);
              Line_Chart.setData(data);
              /* setCookie('numbers',data,3); $('.flash').show(); $('.flash').html("Template Updated")*/
            }
        });
      }); 
   }	

if($("#Area_chart").length){//  ################   
    var Area_chart = Morris.Area({
      element: 'Area_chart',  
      behaveLikeLine: true,
      parseTime : false, 
      data: [ ],
      xkey: 'y',
      ykeys: ['a', 'b'],
      labels: ['Sales', 'Cost'],
      pointFillColors: ['#707f9b'],
      pointStrokeColors: ['#ffaaab'],
      lineColors: ['#f26c4f', '#00a651', '#00bff3'],
      redraw: true      
    });

    $("#SalesPeriods").on("change", function(){
     var selected_user_ID = $(this).val(); 
     $.ajax({
            type: "POST",
            url: "<?php echo $actual_link; ?>themes/LTE/includes/ajax.php?Area_chart="+selected_user_ID,
            data: 0,
            dataType: 'json',
            success: function(data){
              console.log(data);
              Area_chart.setData(data);
            }
          });
  }); 
}

if($("#donut-customer").length){//  #################  
    var Customer_Donut_Chart = Morris.Donut({
      element: 'donut-customer',
      behaveLikeLine: true,
      parseTime : false,
      data: [{"value":"","label":"", "labelColor": '<?php echo $color_scheme; ?>'}],
      colors: ['#f26c4f', '#00a651', '#00bff3', '#0072bc', '#707f9b', '#455064', '#242d3c', '#b92527', '#d13c3e', '#ff6264', '#ffaaab'],
      redraw: true,
    });
    $("#CustomerPeriods").on("change", function(){
         var option = $(this).val(); 
         $.ajax({
            type: "POST",
            url: "<?php echo $actual_link; ?>themes/LTE/includes/ajax.php?Customer_chart="+option,
            data: 0,
            dataType: 'json',
            success: function(data){
             console.log(data);
              Customer_Donut_Chart.setData(data);
              /* setCookie('numbers',data,3); $('.flash').show(); $('.flash').html("Template Updated")*/
            }
        });
    });
}

if($("#donut-supplier").length){ //  #################  
var Supplier_Donut_Chart = Morris.Donut({
  element: 'donut-supplier',
  behaveLikeLine: true,
  parseTime : false,
  data: [{"value":"","label":"", "labelColor": '<?php echo $color_scheme; ?>'}],  
  colors: [  '#ff6264', '#455064', '#d13c3e', '#d13c3e',  '#ff6264', '#ffaaab', '#f26c4f', '#00a651', '#00bff3', '#0072bc', '#b92527', '#707f9b', '#b92527',  '#242d3c'],
  redraw: true,
});
$("#SupplierPeriods").on("change", function(){
     var option = $(this).val(); 
     $.ajax({
        type: "POST",
        url: "<?php echo $actual_link; ?>themes/LTE/includes/ajax.php?Supplier_chart="+option,
        data: 0,
        dataType: 'json',        
        success: function(data){
          console.log(data);
          Supplier_Donut_Chart.setData(data);
          /* setCookie('numbers',data,3); $('.flash').show(); $('.flash').html("Template Updated")*/
        }
      });
  }); 
 }

 // $(document).ready(function(e){
	 
      $("#SalesPeriods").trigger("change");
      $("#CustomerPeriods").trigger("change");
      $("#SupplierPeriods").trigger("change");
      $("#ExpensesPeriods").trigger("change");
      $("#TaxPeriods").trigger("change");
 // }); 
 </script>
 
 <div style="clear:both;" > </div>
<?php } else { 

echo '<div style="line-height:200px; text-align:center;font-size:24px; vertical-align:middle;" > '._('Page not found').' </div>'; 

} ?>
<script src='https://cdn.jsdelivr.net/npm/apexcharts'></script>
<script src='<?php echo $path_to_root."/themes/LTE/js/ChartData.js"; ?>'></script>
<script id="rendered-js">
var options = {
    series: [{
        name: 'Quotations',
        type: 'area',
        data: [
			<?php 
				$begin = fy('begin');
				$dd = $begin[1];
				for($i=$dd;$i<20;$i++){
					$year = $begin[0];
					$i;if($i==12){break;}
					$bg = $year.'-'.$i.'-01 ';
					$ed = $year.'-'.$i.'-31';
					//echo query_count("sales_orders", array("trans_type = 32", "(ord_date between '".$bg."' and '".$ed."')")).', ';
					// echo $i;
			} ?>
			<?php 
				$end = fy('begin');
				$dd = $end[1];
				for($i=1;$i<$dd+1;$i++){
					$year = $end[0];
					// if($i==$dd || $i==$dd){break;}
					$bg = $year.'-'.$i.'-01 ';
					$ed = $year.'-'.$i.'-31';
					//echo query_count("sales_orders", array("trans_type = 32", "(ord_date between '".$bg."' and '".$ed."')")).', ';
					// echo $i;
         } ?>
         55, 69, 45, 61, 43, 54, 37, 52, 44, 61, 43
		]
    }, {
        name: 'Orders',
        type: 'area',
        data: [
			<?php 
				$begin = fy('begin');
				$dd = $begin[1];
				for($i=$dd;$i<20;$i++){
					$year = $begin[0];
					$i;if($i==12){break;}
					$bg = $year.'-'.$i.'-01';
					$ed = $year.'-'.$i.'-31';
					//echo query_count("sales_orders", array("trans_type = 30", "(ord_date between '".$bg."' and '".$ed."')")).', ';
					// echo $i;
			} ?>
			<?php 
				$end = fy('end');
				$dd = $end[1];
				for($i=1;$i<$dd+1;$i++){
					$year = $end[0];
					// if($i==$dd || $i==$dd){break;}
					$bg = $year.'-'.$i.'-01';
					$ed = $year.'-'.$i.'-31';
					//echo query_count("sales_orders", array("trans_type = 30", "(ord_date between '".$bg."' and '".$ed."')")).', ';
					// echo $i;
         } ?>
         44, 55, 31, 47, 31, 43, 26, 41, 31, 47, 33
		]
    }],
    chart: {
        height: 400,
        type: 'line',
        stacked: false,
    },
    stroke: {
        width: [0, 2, 5],
        curve: 'smooth'
    },
    plotOptions: {
        bar: {
            columnWidth: '50%'
        }
    },
    colors: ['#d2d6de', '#4f98c2'],
    fill: {
        opacity: [1, 0.85],
        gradient: {
            inverseColors: false,
            shade: 'light',
            type: "vertical",
            opacityFrom: 0.85,
            opacityTo: 0.55,
            stops: [0, 100, 100, 100]
        }
	},
    labels: [
		<?php 
				$begin = fy('begin');
				$dd = $begin[1];
				for($i=$dd;$i<20;$i++){
					$year = $begin[0];
					$i;if($i==12){break;}
					$bg = $year.'-'.$i.'-01';
					$ed = $year.'-'.$i.'-31';
					$data = query_date("sales_orders", array("(trans_type = 30 or trans_type=32)", "(ord_date between '".$bg."' and '".$ed."') group by ord_date"));
					foreach($data as $dt){
						$rt = explode('-', $dt);
						// echo '01/' . $rt[1] . '/' . $rt[0].', ';
					}
					
			} ?>
		'01/01/2020', '02/01/2020', '03/01/2020', '04/01/2020', '05/01/2020', '06/01/2020', '07/01/2020',
        '08/01/2020', '09/01/2020', '10/01/2020', '11/01/2020',
	],
	
    markers: {
        size: 0
    },
    xaxis: {
        type: 'datetime'
    },
    yaxis: {
        title: {
            text: 'Comparison',
        },
        min: 0
    },
    tooltip: {
        shared: true,
        intersect: false,
        y: {
            formatter: function (y) {
                if (typeof y !== "undefined") {
                    return y.toFixed(0);
                }
                return y;

            }
        }
    }
};

//---------------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Inquiry',
        data: [
            45,34,23,12,33,16,18,21,32,20,29,36
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }

    },
};

var chart = new ApexCharts(document.querySelector("#toptensiq"), options);
chart.render();		

//------------------------------------------------------------------------------------------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Quotation',
        data: [
           50,40,20,30,25,35,45,55,65,50,40,45
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }

    },
};

var chart = new ApexCharts(document.querySelector("#toptensq"), options);
chart.render();

//-----------------------------------------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Order',
        data: [
            35,22,15,14,24,22,19,16,21,17,20,25
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
         categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }
    },
};

var chart = new ApexCharts(document.querySelector("#toptensoa"), options);
chart.render();



//------------------------------------------------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Dispatch',
        data: [
           30,20,34,15,25,35,52,22,32,42,57,36
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
       categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }

    },
};

var chart = new ApexCharts(document.querySelector("#toptensaledisp"), options);
chart.render();	

//------------------------------------------------------------------------------


var options = {
    series: [{
        name: 'Sales Invoice',
        data: [
           25,20,28,15,18,35,42,22,32,38,45,30
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }

    },
};

var chart = new ApexCharts(document.querySelector("#toptensaleinvcount"), options);
chart.render();	

//-----------------------------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Enquiries',
        data: [
             45,34,23,12,33,16,18,21,32,20,29,36
            ]
    }, 
    {
        name: 'Quotations',
        data: [
            50,40,20,30,25,35,45,55,65,50,40,45
            ]
    }],
    chart: {
        type: 'bar',
        height: 350
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
            endingShape: 'rounded'
        },
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent']
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
    },
    yaxis: {
        title: {
            text: 'Sales Enquiries and Quotations'
        }
    },
    fill: {
        opacity: 1
    },
    tooltip: {
        y: {
            formatter: function(val) {
                return val
            }
        }
    }
};

var chart = new ApexCharts(document.querySelector("#topsalesenquote"), options);
chart.render();

//-------------------------------------------------------------------------------------------------------------


var options = {
    series: [{
        name: 'Sales Enquiries',
        data: [
           45,34,23,12,33,16,18,21,32,20,29,36
           ]
    }, {
        name: 'Quotations',
        data: [
            50,40,20,30,25,35,45,55,65,50,40,45
            ]
    }, {
        name: 'Orders',
        data: [
            35,22,15,14,24,22,19,16,21,17,20,25
            ]
    }],
    chart: {
        type: 'bar',
        height: 350
    },
    plotOptions: {
        bar: {
            horizontal : false,
            columnWidth: '55%',
            endingShape: 'rounded'
        },
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent']
    },
    xaxis: {
         categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
    },
    yaxis: {
        title: {
            text: 'Enquiries and Quotations and Orders'
        }
    },
    fill: {
        opacity: 1
    },
    tooltip: {
        y: {
            formatter: function(val) {
                return val
            }
        }
    }
};

var chart = new ApexCharts(document.querySelector("#topsaleeqo"), options);
chart.render();

//--------------------------------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Quotations',
        data: [
            50,40,20,30,25,35,45,55,65,50,40,45
            ]
    }, {
        name: 'Orders',
        data: [
             35,22,15,14,24,22,19,16,21,17,20,25
             ]
    }],
    chart: {
        type: 'bar',
        height: 350
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
            endingShape: 'rounded'
        },
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent']
    },
    xaxis: {
       categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
    },
    yaxis: {
        title: {
            text: 'Sales Quotations and Orders'
        }
    },
    fill: {
        opacity: 1
    },
    tooltip: {
        y: {
            formatter: function(val) {
                return val
            }
        }
    }
};

var chart = new ApexCharts(document.querySelector("#topsalesquoteorder"), options);
chart.render();


//-------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Quotation',
        data: [
            1210.123,2210.258,1813.128,1413.257,2610.147,2216.369,1916.456,
            1613.368,2101.124,1713.247,2100.147,1853.247
            
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }

    },
};
var chart = new ApexCharts(document.querySelector("#toptensqv"), options);
chart.render();

//-----------------------------------------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Order',
        data: [
             1810.123,2610.258,1513.128,1913.257,2810.147,2316.369,1716.456,
            1413.368,2801.124,1613.247,2700.147,2253.247
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }

    },
};
var chart = new ApexCharts(document.querySelector("#toptensov"), options);
chart.render();

//-------------------------------------------------------------------------------
var options = {
    series: [{
        name: 'Sales Dispatch',
        data: [
             2810.123,2310.258,1813.128,1313.257,2610.147,2116.369,1716.456,
            1313.368,2701.124,1413.247,2900.147,2153.247
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
         categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }

    },
};
var chart = new ApexCharts(document.querySelector("#toptensdv"), options);
chart.render();

//---------------------------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Invoice',
        data: [
            2510.123,2110.258,1313.128,1813.257,2610.147,2216.369,1816.456,
            1413.368,2901.124,1213.247,2800.147,2053.247
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
         categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }

    },
};
var chart = new ApexCharts(document.querySelector("#toptensaleinv"), options);
chart.render();



//------------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Quotations',
        data: [
            1210.123,2210.258,1813.128,1413.257,2610.147,2216.369,1916.456,
            1613.368,2101.124,1713.247,2100.147,1853.247
            ]
    }, {
        name: 'Orders',
        data: [
             1810.123,2610.258,1513.128,1913.257,2810.1417,2316.369,1716.456,
            1413.368,2801.124,1613.2417,2700.147,2253.247
            ]
    }],
    chart: {
        type: 'bar',
        height: 350
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
            endingShape: 'rounded'
        },
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent']
    },
    xaxis: {
       categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
    },
    yaxis: {
        title: {
            text: 'Sales Quotations and Orders Value'
        }
    },
    fill: {
        opacity: 1
    },
    tooltip: {
        y: {
            formatter: function(val) {
                return val
            }
        }
    }
};

var chart = new ApexCharts(document.querySelector("#topsalesquoteordervalue"), options);
chart.render();

//-----------------------------------------------------------------------------
// Purchase Dashboards
var options = {
    series: [{
        name: 'Purcahse Inquiries',
        data: [
            30,24,18,15,26,23,32,19,27,18,16,22
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
       categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }
    },
};

var chart = new ApexCharts(document.querySelector("#toptenpea"), options);
chart.render();

//--------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Purcahse Quotations',
        data: [
           28,23,16,25,33,28,16,25,34,17,13,30
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }
    },
};

var chart = new ApexCharts(document.querySelector("#toptenpqa"), options);
chart.render();

//--------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Purcahse GRN',
        data: [
            23, 11, 22, 27, 13, 21, 37, 21, 44, 26, 30,26
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
         categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }
    },
};

var chart = new ApexCharts(document.querySelector("#toptengrn"), options);
chart.render();

//----------------------------------------------------------------------------



var options = {
    series: [{
        name: 'Purcahse Orders',
        data: [
            22, 16, 13, 31, 18, 23, 28, 30, 14, 26, 17,24
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
         categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }
    },
};

var chart = new ApexCharts(document.querySelector("#toptenpoa"), options);
chart.render();

//-------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Purcahse Invoices',
        data: [
             18, 22, 16, 24, 31, 21, 14, 19, 25, 32, 17,26
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
         categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }
    },
};

var chart = new ApexCharts(document.querySelector("#toptenpurchaseinv"), options);
chart.render();

//--------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Purchase Enquiries',
        data: [
            30,24,18,15,26,23,32,19,27,18,16,22
            ]
    }, {
        name: 'Quotations',
        data: [
             28,23,16,25,33,28,16,25,34,17,13,30
             ]
    }],
    chart: {
        type: 'bar',
        height: 350
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
            endingShape: 'rounded'
        },
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent']
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
    },
    yaxis: {
        title: {
            text: 'Purchase Enquiries and Quotations'
        }
    },
    fill: {
        opacity: 1
    },
    tooltip: {
        y: {
            formatter: function(val) {
                return val
            }
        }
    }
};

var chart = new ApexCharts(document.querySelector("#toppurchasesenquote"), options);
chart.render();

//-----------------------------------------------------------------------------


var options = {
    series: [{
        name: 'Purchase Quotations',
        data: [
            28,23,16,25,33,28,16,25,34,17,13,30
            ]
    }, {
        name: 'Orders',
        data: [
            22, 16, 13, 31, 18, 23, 28, 30, 14, 26, 17,24
            ]
    }],
    chart: {
        type: 'bar',
        height: 350
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
            endingShape: 'rounded'
        },
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent']
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
    },
    yaxis: {
        title: {
            text: 'Purchase Quotations and Orders'
        }
    },
    fill: {
        opacity: 1
    },
    tooltip: {
        y: {
            formatter: function(val) {
                return val
            }
        }
    }
};

var chart = new ApexCharts(document.querySelector("#toppurchasesquoteorder"), options);
chart.render();

//-----------------------------------------------------------------------------


var options = {
    series: [{
        name: 'Purchase Order',
        data: [
            2810.123,1610.258,2513.128,2913.257,1810.147,2816.369,1716.456,
            2413.368,1801.124,2613.247,1700.147,2353.247
        ]
    }],
    chart: {
        height: 350,
        type: 'bar',
    },
    plotOptions: {
        bar: {
            dataLabels: {
                position: 'top', // top, center, bottom
            },
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function(val) {
            return val;
        },
        offsetY: -20,
        style: {
            fontSize: '12px',
            colors: ["#304758"]
        }
    },

    xaxis: {
         categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                      'Sep', 'Oct', 'Nov', 'Dec'],
        position: 'top',
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        },
        crosshairs: {
            fill: {
                type: 'gradient',
                gradient: {
                    colorFrom: '#D8E3F0',
                    colorTo: '#BED1E6',
                    stops: [0, 100],
                    opacityFrom: 0.4,
                    opacityTo: 0.5,
                }
            }
        },
        tooltip: {
            enabled: true,
        }
    },
    yaxis: {
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false,
        },
        labels: {
            show: false,
            formatter: function(val) {
                return val;
            }
        }

    },
};
var chart = new ApexCharts(document.querySelector("#toptenpov"), options);
chart.render();

//------------------------------------------------------------------------------


   var options = {
    series: [
       4000.000,6000.000
        ],
    chart: {
        type: 'donut',
	},
	labels: [
	    'Location 01','Location 02'],
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                width: 200,
            },
            legend: {
                position: 'bottom'
            }
        }
    }]
};

var chart = new ApexCharts(document.querySelector("#location_wise_current_inventory_value"), options);
chart.render();
//------------------------------------------------------------------------------


var options = {
    series: [<?php $pp = '';$rows = sales_revunue_values_by_salesperson()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>],
    chart: {
        type: 'donut',
	},
	labels: [<?php $ll = '';$rows = sales_revunue_values_by_salesperson()['salesmans'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>],
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                width: 200,
            },
            legend: {
                position: 'bottom'
            }
        }
    }]
};

var chart = new ApexCharts(document.querySelector("#sales_revenue_by_sales_person"), options);
chart.render();

//-----------------------------------------------------------------------------
var options = {
    
    series: [<?php $pp = '';$rows = dailysales()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>],
    
    series: [{
    name: 'PRODUCT A',
    data: [44, 55, 41, 67, 22, 43]
  }, {
    name: 'PRODUCT B',
    data: [13, 23, 20, 8, 13, 27]
  }, {
    name: 'PRODUCT C',
    data: [11, 17, 15, 15, 21, 14]
  }, {
    name: 'PRODUCT D',
    data: [21, 7, 25, 13, 22, 8]
  }],
    chart: {
    type: 'bar',
    height: 280,
    stacked: true,
    toolbar: {
      show: true
    },
    zoom: {
      enabled: true
    }
  },
  responsive: [{
    breakpoint: 480,
    options: {
      legend: {
        position: 'bottom',
        offsetX: -10,
        offsetY: 0
      }
    }
  }],
  plotOptions: {
    bar: {
      horizontal: false,
    },
  },
  xaxis: {
    type: 'datetime',
    categories: ['01/01/2011 GMT', '01/02/2011 GMT', '01/03/2011 GMT', '01/04/2011 GMT',
      '01/05/2011 GMT', '01/06/2011 GMT',
      '01/07/2011 GMT', '01/08/2011 GMT'
    ],
  },
  legend: {
    position: 'right',
    offsetY: 40
  },
  fill: {
    opacity: 1
  }
  };

  var chart = new ApexCharts(document.querySelector("#dailysaleschart"), options);
  chart.render();

//------------------------------------------------------------------------------

var chart = new ApexCharts(document.querySelector("#chart12"), options);
chart.render();

<?php 
$b = query_count("stock_master", array("mb_flag = 'B'"));
$m = query_count("stock_master", array("mb_flag = 'M'"));
$d = query_count("stock_master", array("mb_flag = 'D'"));
$tt = $b+$m+$d;
?>
var options = {
    series: [<?= $b ?>,<?= $m ?>,<?= $d ?>],
    chart: {
        type: 'donut',
	},
	labels: ['Purchased','Manufactured','Non-Stock Items'],
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                width: 200,
            },
            legend: {
                position: 'bottom'
            }
        }
    }]
};

var chart = new ApexCharts(document.querySelector("#chart15"), options);
chart.render();
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>
<script>
var canvas = document.getElementById("barChart");
var ctx = canvas.getContext('2d');

// Global Options:
Chart.defaults.global.defaultFontColor = 'black';
Chart.defaults.global.defaultFontSize = 16;

var data = {
  labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
  datasets: [{
      label: "Orders",
      fill: true,
      lineTension: 0,
      backgroundColor: "rgba(75,148,191,0.8)",
      borderColor: "rgb(75,148,191)", // The main line color
      borderCapStyle: 'square',
      borderDash: [], // try [5, 15] for instance
      borderDashOffset: 0.0,
      borderJoinStyle: 'miter',
      pointBorderColor: "black",
      pointBackgroundColor: "white",
      pointBorderWidth: 1,
      pointHoverRadius: 8,
      pointHoverBackgroundColor: "",
      pointHoverBorderColor: "",
      pointHoverBorderWidth: 2,
      pointRadius: 4,
      pointHitRadius: 10,
      // notice the gap in the data and the spanGaps: true
      data: [
			<?php 
				$end = fy('end');
				$dd = $end[1];
				for($i=1;$i<$dd+1;$i++){
					$year = $end[0];
					// if($i==$dd || $i==$dd){break;}
					$bg = $year.'-'.$i.'-01';
					$ed = $year.'-'.$i.'-31';
					echo query_count("sales_orders", array("trans_type = 30", "(ord_date between '".$bg."' and '".$ed."')")).', ';
					// echo $i;
			} ?>
      ],
      spanGaps: true,
    }, {
      label: "Quotation",
      fill: true,
      lineTension: 0.2,
      backgroundColor: "rgba(210,214,222,0.8)",
      borderColor: "rgb(210,214,222)",
      borderCapStyle: 'butt',
      borderDash: [],
      borderDashOffset: 0.0,
      borderJoinStyle: 'miter',
      pointBorderColor: "white",
      pointBackgroundColor: "black",
      pointBorderWidth: 1,
      pointHoverRadius: 8,
      pointHoverBackgroundColor: "",
      pointHoverBorderColor: "",
      pointHoverBorderWidth: 2,
      pointRadius: 4,
      pointHitRadius: 10,
      // notice the gap in the data and the spanGaps: false
      data: [
         <?php 
				$begin = fy('begin');
				$dd = $begin[1];
				for($i=1;$i<20;$i++){
					$year = $begin[0];
					$i;if($i==12){break;}
					$bg = $year.'-'.$i.'-01';
					$ed = $year.'-'.$i.'-31';
					echo query_count("sales_orders", array("trans_type = 32", "(ord_date between '".$bg."' and '".$ed."')")).', ';
					// echo $i;
			} ?>
      ],
      spanGaps: false,
    }

  ]
};

// Notice the scaleLabel at the same level as Ticks
var options = {
  scales: {
            yAxes: [{
                ticks: {
                    beginAtZero:true
                },
                scaleLabel: {
                     display: true,
                     labelString: 'Comparison',
                     fontSize: 20 
                  }
            }]            
        }
};

// Chart declaration:
var myBarChart = new Chart(ctx, {
  type: 'line',
  data: data,
  options: options
});
</script>