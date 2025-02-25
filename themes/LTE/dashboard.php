<?php

$page_security = 'SA_SETUPDISPLAY'; 

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_trans.inc");
include_once($path_to_root . "/themes/LTE/db/charts_db.inc");
include_once("kvcodes.inc");
//display_error(json_encode($_SESSION['wa_current_user']));
//if(kv_get_option('hide_dashboard') == 0){ 
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
              <h3><?= query_count("sales_orders", array("trans_type=32", "(ord_date between '".$begin_fy."' and '".$end_fy."')")); ?></h3>

              <p><?= "Quotations" ?></p>
            </div>
            <div class="icon">
              <i class="fa fa-list-ol"></i>
            </div>
            <a href="<?php echo $path_to_root."/sales/inquiry/sales_orders_view.php?type=32"; ?>" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green">
            <div class="inner">
              <h3><?= query_count("sales_orders", array("trans_type=30", "(ord_date between '".$begin_fy."' and '".$end_fy."')")); ?></h3>

              <p><?= "Orders" ?></p>
            </div>
            <div class="icon">
              <i class="fa fa-list"></i>
            </div>
            <a href="<?php echo $path_to_root."/sales/inquiry/sales_orders_view.php?type=30"; ?>" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow">
            <div class="inner">
              <h3><?= query_count("debtor_trans", array("type=13", "(tran_date between '".$begin_fy."' and '".$end_fy."')")); ?></h3>

              <p><?= "Dispatches" ?></p>
            </div>
            <div class="icon">
              <i class="fa fa-truck"></i>
            </div>
            <a href="<?php echo $path_to_root."/sales/inquiry/customer_inquiry.php?"; ?>" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-red">
            <div class="inner">
              <h3><?= query_count("debtor_trans", array("type=10", "(tran_date between '".$begin_fy."' and '".$end_fy."')")); ?></h3>

              <p><?= "Invoices" ?></p>
            </div>
            <div class="icon">
              <i class="fa fa-list-alt"></i>
            </div>
            <a href="<?php echo $path_to_root."/sales/inquiry/customer_inquiry.php?"; ?>" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
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
            <h2 class="my-2" id="active-users-count"><?php echo $cust_coubt[0]; ?></h2>
         </div>
      </div>
   </div>
   <div class="col-md-3 col-sm-6 col-xs-12">
      <div class="card">
         <div class="card-body ds-info ds-card">
            <i class="fa fa-truck ds-icon float-right ds-3"></i>
            <h5 class="text-uppercase mt-0"><?php echo _("SUPPLIERS"); ?></h5>
            <h2 class="my-2" id="active-users-count"><?php echo $sup_count[0]; ?></h2>
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
            <h2 class="my-2" id="active-users-count"><?= $items_count[0] ?></h2>
         </div>
      </div>
   </div>
     <div class="col-md-3 col-sm-6 col-xs-12">
      <div class="card">
         <div class="card-body ds-info ds-card">
            <i class="fa fa-bank ds-icon float-right ds-2"></i>
            <h5 class="text-uppercase mt-0"><?php 
			
			$item_count= kv_get_current_balance_of_default_bank_account();
			/*echo _("CURRENT BALANCE"); */  echo $item_count[1]; ?></h5>
            <h2 class="my-2" id="active-users-count"><?php   echo round($item_count[0],3); ?></h2>
         </div>
      </div>
   </div>
   
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
                    <h3 class="box-title"><?php echo _("Monthly Sales Order Value"); ?> </h3>
                    <div id="toptensov"></div>
                </div>
            </div>
        </div>
        
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
                    <h3 class="box-title"><?php echo _("Monthly Sales Invoice Value"); ?> </h3>
                    <div id="toptensaleinv"></div>
                </div>
            </div>
        </div>
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
                    <h3 class="box-title"><?php echo _("Monthly Sales Dispatch Value"); ?> </h3>
                    <div id="toptensdv"></div>
                </div>
            </div>
        </div>
		
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
                    <h3 class="box-title"><?php echo _("Monthly Sales Quotation Value"); ?> </h3>
                    <div id="toptensqv"></div>
                </div>
            </div>
        </div>
		
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
                    <h3 class="box-title">
					<?php echo _("Monthly Sales Enquiries and Quotations"); ?> </h3>
                    <div id="topsalesenquote"></div>
                </div>
            </div>
        </div>
	 </div>	
	 
	 <div class="row">
        
         <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title">
                        <?php echo _("Monthly Sales Enquiries,Quotations and Orders"); ?> </h3>
                    <div id="topsaleeqo"></div> 
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
	 </div>	
   
       
        
     <!-- <div class="row">
      
        <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="box-title">
					<?php //echo _("Monthly Sales Quotations and Orders Value"); ?> </h3>
                    <div id="topsalesquoteordervalue"></div>
                </div>
            </div>
        </div>  
    </div> -->
	
	
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
                    <h3 class="box-title"><?php echo _("Monthly Purchases Order Value"); ?> </h3>
                    <div id="toptenpov"></div>
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
                   <h3 class="box-title"><?php echo _("Monthly Purchase GRN / MRN"); ?> </h3>
                    <div id="toptengrn"></div>
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
    
</div>	
	
	

<div class="row">
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
							$date1=date_create($single[4]);
							$last_sales = date_format($date1,"d/m/Y");
						echo '<tr><td>'.$single[0].'</td><td>'.$single[1].'</td> <td> '.$single[2].'</td><td>'.$single[3].'</td><td> '.$last_sales.'</td></tr>';
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
</div>
<div class="row">
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
	
</div>
<div class="row">
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
		 <div class="col-md-5 col-sm-12 col-xs-12">
			  <div class="card">
				 <div class="card-body">
					<h3 class="box-title">
					<?php  echo _("Current Inventory Value based on Location"); ?> </h3>
					<div id="location_wise_current_inventory_value"></div>
				 </div>
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
 	<div class="col-md-12 col-sm-12">
							
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


<?php //} else { 

//echo '<div style="line-height:200px; text-align:center;font-size:24px; vertical-align:middle;" > '._('Page not found').' </div>'; 

//} ?>

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
            <?php $pp = '';$rows = monthlysalesinquiry()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlysalesinquiry()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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
            <?php $pp = '';$rows = monthlysalesquotation()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlysalesquotation()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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
            <?php $pp = '';$rows = monthlysalesorders()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlysalesorders()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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
            <?php $pp = '';$rows = monthlysalesdisptches()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlysalesdisptches()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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
            <?php $pp = '';$rows = monthlysalesinvoices()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlysalesinvoices()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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
            <?php $ll = '';$rows = monthlysalesenqandquote()['rows'];foreach($rows as $row){$ll .= $row.', ';}echo substr($ll, 0, -2); ?>]
    }, {
        name: 'Quotations',
        data: [
            <?php $ll = '';$rows = monthlysalesenqandquote()['quotes'];foreach($rows as $row){$ll .= $row.', ';}echo substr($ll, 0, -2); ?>]
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
        categories: [
            <?php $ll = '';$rows = monthlysalesenqandquote()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2); ?>
        ],
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
            <?php $ll = '';$rows = monthlysalesenqandquoteandorder()['rows'];foreach($rows as $row){$ll .= $row.', ';}echo substr($ll, 0, -2); ?>]
    }, {
        name: 'Quotations',
        data: [
            <?php $ll = '';$rows = monthlysalesenqandquoteandorder()['quotes'];foreach($rows as $row){$ll .= $row.', ';}echo substr($ll, 0, -2); ?>]
    }, {
        name: 'Orders',
        data: [
            <?php $ll = '';$rows = monthlysalesenqandquoteandorder()['orders'];foreach($rows as $row){$ll .= $row.', ';}echo substr($ll, 0, -2); ?>]
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
        categories: [
            <?php $ll = '';$rows = monthlysalesenqandquoteandorder()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2); ?>
        ],
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

//--------------------------------------------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Quotation',
        data: [
            <?php $pp = '';$rows = monthlysalesquotationvalue()['rows'];foreach($rows as $row){$pp .= $row.', ';  }  echo $pp; ?>
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
        categories: [
            <?php $ll = '';$rows = monthlysalesquotationvalue()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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
            <?php $pp = '';$rows = monthlysalesordersvalue()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlysalesordersvalue()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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
            <?php $pp = '';$rows = monthlysalesdispatchvalue()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlysalesdispatchvalue()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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
            <?php $pp = '';$rows = monthlysalesinvoicevalue()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlysalesinvoicevalue()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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

//--------------------------------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Quotations',
        data: [
            <?php $ll = '';$rows = monthlysalesquotationsorders()['rows'];foreach($rows as $row){$ll .= $row.', ';}echo substr($ll, 0, -2); ?>]
    }, {
        name: 'Orders',
        data: [
            <?php $ll = '';$rows = monthlysalesquotationsorders()['orders'];foreach($rows as $row){$ll .= $row.', ';}echo substr($ll, 0, -2); ?>]
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
        categories: [
            <?php $ll = '';$rows = monthlysalesquotationsorders()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2); ?>
        ],
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

//------------------------------------------------------------------------------

var options = {
    series: [{
        name: 'Sales Quotations',
        data: [
            <?php $ll = '';$rows = monthlysalesquotationsordersvalue()['rows'];foreach($rows as $row){$ll .= $row.', ';}echo substr($ll, 0, -2); ?>]
    }, {
        name: 'Orders',
        data: [
            <?php $ll = '';$rows = monthlysalesquotationsordersvalue()['orders'];foreach($rows as $row){$ll .= $row.', ';}echo substr($ll, 0, -2); ?>]
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
        categories: [
            <?php $ll = '';$rows = monthlysalesquotationsordersvalue()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2); ?>
        ],
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
            <?php $pp = '';$rows = monthlypurchaseinquiries()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlypurchaseinquiries()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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
            <?php $pp = '';$rows = monthlypurchasequotations()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlypurchasequotations()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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
            <?php $pp = '';$rows = monthlypurchasegrns()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlypurchasegrns()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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
            <?php $pp = '';$rows = monthlypurchaseorders()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlypurchaseorders()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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
            <?php $pp = '';$rows = monthlypurchaseinvoices()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlypurchaseinvoices()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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
            <?php $ll = '';$rows = monthlypurchasesenqandquote()['rows'];foreach($rows as $row){$ll .= $row.', ';}echo substr($ll, 0, -2); ?>]
    }, {
        name: 'Quotations',
        data: [
            <?php $ll = '';$rows = monthlypurchasesenqandquote()['quotes'];foreach($rows as $row){$ll .= $row.', ';}echo substr($ll, 0, -2); ?>]
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
        categories: [
            <?php $ll = '';$rows = monthlypurchasesenqandquote()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2); ?>
        ],
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
            <?php $ll = '';$rows = monthlypurchasesquotationsorders()['rows'];foreach($rows as $row){$ll .= $row.', ';}echo substr($ll, 0, -2); ?>]
    }, {
        name: 'Orders',
        data: [
            <?php $ll = '';$rows = monthlypurchasesquotationsorders()['orders'];foreach($rows as $row){$ll .= $row.', ';}echo substr($ll, 0, -2); ?>]
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
        categories: [
            <?php $ll = '';$rows = monthlypurchasesquotationsorders()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2); ?>
        ],
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
            <?php $pp = '';$rows = monthlypurchasesordersvalue()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>
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
        categories: [
            <?php $ll = '';$rows = monthlypurchasesordersvalue()['months'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>
        ],
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




<?php 
$b = query_count("stock_master", array("mb_flag = 'B'"));
$m = query_count("stock_master", array("mb_flag = 'M'"));

$tt = $b+$m+$d;


?>
   var options = {
    series: [<?php $pp = '';$rows = location_wise_current_inventory_values()['rows'];foreach($rows as $row){$pp .= $row.', ';}echo substr($pp, 0, -2); ?>],
    chart: {
        type: 'donut',
	},
	labels: [<?php $ll = '';$rows = location_wise_current_inventory_values()['locations'];foreach($rows as $row){$ll .= '"'. $row.'", ';}echo substr($ll, 0, -2);; ?>],
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