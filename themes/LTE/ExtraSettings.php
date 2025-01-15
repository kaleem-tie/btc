<?php 
$current_file_name = basename($_SERVER['PHP_SELF']) ;  ?>
		<style>
		<?php if( $current_file_name == 'gl_journal.php' || $current_file_name == 'bank_transfer.php' || $current_file_name == 'gl_bank.php' || $current_file_name== 'customer_payments.php'  || $current_file_name== 'sales_order_entry.php'  || $current_file_name == 'po_entry_items.php'  ){	

				?>
				@media (min-width: 768px){	
					input[name="item_description"]{ max-width: 250px; }
					table.tablestyle_inner td { border:0px; }
					table.tablestyle2 , table.tablestyle { width: 100% !important; }                             
                                        <?php if( $current_file_name == 'gl_journal.php' ) {?>
					    table.tablestyle2 td:first-child, table.tablestyle2 td:nth-child(3) {  width:10%; }
                                       <?php } else { ?>
                                           table.tablestyle2 td:first-child, table.tablestyle2 td:nth-child(3) {  width: 28%; } <?php } ?>
					table.tablestyle2 td:nth-child(2) {  width: 18%; }
					table.tablestyle2 td:first-child .tablestyle_inner td:first-child ,
					table.tablestyle2 td:nth-child(3) .tablestyle_inner td:first-child { width: 5% ; }
					table.tablestyle td {  border:0px; }
					table.tablestyle_inner{ border:0px; }
					#delivery table { width: auto !important; }
					#delivery table.tablestyle_inner { width: 100% !important; }
					#delivery table.tablestyle2 input, #delivery table.tablestyle2 textarea { width: 90%; }
					#delivery table.tablestyle2 td { width: 25%;   display: table-cell;   text-align: left;}
					
				}
				@media (max-width: 768px){					
					table, table.tablestyle2 td:nth-child(2) { width: 100%; border:0px !important; }
					<?php  if($current_file_name != 'gl_bank.php') { ?>
					table.tablestyle2 td:first-child, table.tablestyle2 td:nth-child(3){
						width: 100%;
					}
					<?php } ?>
					
					input[type="text"]{	width: 100%; }
					div#items_table table tr:first-child {  background-color: #eeeeee; }
					/*div#items_table table td {   display: inline-block; width:33%; float:left; }
					div#items_table table tr:nth-last-child(-n+3) td { display: inline-block; width:33%; }*/
					div#items_table center table { width: 700px; } 
					div#items_table table.tablestyle td {  padding:1px; } 
					div#items_table table td input[type="text"]{ width: 90% /*calc(100% - 33px)*/; float: left; margin: 5px 0; }
					div#items_table table td .combo {   width: 90% /*calc(100% - 33px)*/ ;  }
					div#items_table table td span {   width: 90% /*calc(100% - 43px)*/ ;  }
					div#items_table table td#units {   text-align: right;  }
					div#items_table table td .editbutton img { /*background: #2ecc71;  padding: 5px*/ }

					div#delivery table.tablestyle2 { width: 100%; float:left; }
					div#delivery table.tablestyle2 tbody tr td:first-child { width: 20% !important; }
					div#delivery table.tablestyle2 tbody tr td:nth-child(2) { width: 50% !important; }
					div#delivery table.tablestyle2 tbody tr[valign="top"] td{ width: 100% !important; }
					div#delivery table.tablestyle2 tbody tr[valign="top"] td table.tablestyle_inner tr td:first-child{ width: 40% !important; }
					div#delivery table.tablestyle2 tbody tr[valign="top"] td table.tablestyle_inner tr td:nth-child(2){ width: 50% !important; }
					div#delivery table.tablestyle2 td{ width: 50%; float:left; text-align: left;}
					
					table.tablestyle_inner td{   width: 45%;   float: left; }
					#pmt_header table.tablestyle2 td, #_page_body > form > center:first-child table.tablestyle2 tr td{ width: calc(100% - 10px); float:left; border-left:0px !important;}
					 #_page_body > form > center:first-child table.tablestyle2 tr td table.tablestyle_inner td{ width:45%; }
					/*table.tablestyle2 td div#items_table table.tablestyle td{ width: auto !important; float:left; border-left:0px !important;}*/
					table.tablestyle2 td table.tablestyle_inner{ width: 100%; float:left; border:0px;}
					table.tablestyle2 td table.tablestyle_inner td{ width: 40%; float:left; text-align:	left;}
					.tablestyle_inner .combo2, .tablestyle_inner .combo { width: calc(100% - 33px) !important; }
					.tablestyle_inner .combo2 + img, .tablestyle_inner #_customer_id_sel + img, .tablestyle2 .tablestyle_inner img { width: 25px !important; height: 25px !important; }
					.tablestyle2 .tablestyle_inner { margin: 5px 0; }
					table.tablestyle td {border-bottom:0px; }
					<?php if($current_file_name == 'po_entry_items.php' ) { ?>
						input.date {   width: calc(100% - 30px) !important;}
						input.date + a > img { padding-top: 10px;    padding-left: 3px; }
					<?php } ?>
				}
				@media (max-width: 500px){
					.pull-right {  float: none;}
					/*div#items_table table td {   display: inline-block; width:30%; float:left; text-align: left;}*/
					
				} <?php if($current_file_name == 'gl_journal.php' || $current_file_name == 'gl_bank.php') { ?>
					/*table.tablestyle2 td:nth-child(2) {  width: 32% !important; }
					table.tablestyle2 td:nth-child(2) select#code_id { width: 100% !important; max-width:100%;} 
					table.tablestyle2 td:first-child, table.tablestyle2 td:nth-child(3) {  width: 10% !important; }*/
					@media (max-width: 768px){
						table.tablestyle2 td table.tablestyle_inner td {   display: inline-block; width:40% !important; float:left; text-align: left;}
						#items_table table.tablestyle td:nth-child(2) { width: 30%; }
						<?php  if($current_file_name != 'gl_bank.php') { ?>
						
						div#items_table table td {   display: inline-block; width:45% !important; float:left; }
					<?php } ?>
					}
				<?php }  ?>
					
		<?php }else if( $current_file_name== 'transfers.php' || $current_file_name== 'adjustments.php') { ?>
				@media (max-width: 500px){
					table.tablestyle tr[valign="top"] td, table.tablestyle2 tr[valign="top"] td { width:100%; display: block; }
					div#items_table table tr:first-child {  background-color: #eeeeee; }
					table.tablestyle tr[valign="top"] td table.tablestyle_inner tr td, 
					table.tablestyle2 tr[valign="top"] td table.tablestyle_inner tr td,div#items_table table td {  display: inline-block; width:33%; float:left; }
					table { width: 100%; }
				}
		<?php }else if( $current_file_name== 'credit_note_entry.php') { ?>
			@media (max-width: 768px){ 
				table { width: 100%; }
				#_page_body > form > center:first-child table.tablestyle tr td { width:100%; display: block; }
				table.tablestyle tr[valign="top"] td table.tablestyle_inner{ width: 100%; margin: 5px 0;}
				/*table.tablestyle tr[valign="top"] td table.tablestyle_inner tr td:first-child { width: 40%;  float: left; text-align: left;}
				table.tablestyle tr[valign="top"] td table.tablestyle_inner tr td { width: 50%;  float: left; text-align: left;}*/
				.tablestyle_inner .combo2, .tablestyle_inner .combo { width: calc( 100% - 25px) !important;}
				/*div#items_table table { width: 700px; } */
				div#items_table table tr:first-child {  background-color: #eeeeee; }
					/*	div#items_table table tr:nth-last-child(-n+3) td { display: inline-block; width:33%; }*/
					div#items_table table td {  padding : 4px; }
					div#items_table table td input[type="text"]{ width: 100% /*calc(100% - 33px)*/; float: left; margin: 5px 0; }
					/*div#items_table table td .combo {   width: calc(100% - 33px) ;  }
					div#items_table table td span {   width: calc(100% - 43px) ;  }*/
					div#items_table table td#units {   text-align: right;  }
					div#items_table table td .editbutton img { background: #2ecc71;  padding: 5px }
				#options table.tablestyle2 tr td:first-child { width: 40%;  text-align: left; float:left;}
				#options table.tablestyle2 tr td { width: 50%;  text-align: left; float:left; padding:5px;}
				select{  width: 100%; }
				div#items_table button {  padding:4px 4px; }
			}
			@media (max-width: 500px){
					#_page_body > form > center:first-child table td {   display: inline-block; width:50%; float:left; text-align: left;}
					#_page_body > form > center:first-child table tr[valign="top"] td {   display: inline-block; width:90%; float:left; text-align: left; border-left:0px;}
					#_page_body > form > center:first-child table tr[valign="top"] td table.tablestyle_inner {   border: none;}
					#_page_body > form > center:first-child table tr[valign="top"] td table.tablestyle_inner td {   display: inline-block; width:45%; float:left; text-align: left;}
				}
		<?php } elseif($current_file_name == 'profit_loss.php' || $current_file_name == 'balance_sheet.php' || $current_file_name == 'gl_trial_balance.php' || $current_file_name == 'bank_inquiry.php' || $current_file_name == 'gl_account_inquiry.php' ||  $current_file_name == 'journal_inquiry.php' || $current_file_name == 'bank_account_reconcile.php' ||  $current_file_name == 'search_dimensions.php'|| $current_file_name == 'bom_edit.php'|| $current_file_name == 'bom_cost_inquiry.php'|| $current_file_name == 'search_work_orders.php'|| $current_file_name == 'stock_status.php'|| $current_file_name == 'stock_movements.php'|| $current_file_name == 'supplier_allocation_inquiry.php'||  $current_file_name == 'supplier_inquiry.php'||  $current_file_name == 'supplier_allocation_main.php'|| $current_file_name == 'customer_allocation_main.php'  || $current_file_name == 'sales_orders_view.php'  || $current_file_name == 'customer_inquiry.php'|| $current_file_name == 'customer_allocation_inquiry.php' || $current_file_name == 'sales_deliveries_view.php' || $current_file_name == 'create_recurrent_invoices.php'){?>
			@media (max-width: 768px){
				#_orders_tbl_span table.tablestyle { width: 95%; }
				input[type="text"]{ width:30%; }
				input.date { width: calc(100% - 20px); }
				.tablestyle_noborder .combo2, .tablestyle_noborder .combo { width: calc(100% - 25px) !important; }
				 table.tablestyle tr:first-child{ background-color: #eeeeee; }
				 <?php if( $current_file_name != 'bank_account_reconcile.php' ) { ?>
				table td {   display: block;   padding: 5px;  float: left;   width: 30%; text-align: left;}
				 <?php }?>
				table.tablestyle_noborder:nth-child(2) tr td:nth-last-child(-n+3) { display: inline-block; float: left; width:33%; } 
				table.tablestyle_noborder { width: 80%; }
				table tr td.navibar{ width: 100%; line-height: 35px; }
				
				
				<?php if($current_file_name == 'customer_allocation_inquiry.php'){ ?>
					.tablestyle_noborder tr td { width:45%; }
				<?php }?>
				 
				#_customer_id_sel + img { width: 23px !important; height: 23px !important; }
			}
			@media (max-width: 500px){
					table.tablestyle { width: 100%; }
					.ajaxsubmit, .inputsubmit {  margin:0; line-height: 24px; }
					table td {   display: inline-block; width:100%; float:left; text-align: left;}
					.tabelstyle tr td:nth-last-child(-n+3) { width:33% !important; }
				}
		<?php }elseif($current_file_name == 'attachments.php' || $current_file_name == 'view_print_transaction.php' || $current_file_name == 'void_transaction.php' || $current_file_name == 'shipping_companies.php' || $current_file_name == 'shipping_companies.php' ||$current_file_name == 'item_tax_types.php' || $current_file_name == 'tax_groups.php' || $current_file_name == 'tax_types.php' || $current_file_name == 'security_roles.php' || $current_file_name == 'users.php' || $current_file_name == 'gl_account_classes.php' || $current_file_name == 'gl_account_types.php' || $current_file_name == 'gl_accounts.php' || $current_file_name == 'exchange_rates.php' || $current_file_name == 'currencies.php' || $current_file_name == 'gl_quick_entries.php' || $current_file_name == 'bank_accounts.php' || $current_file_name == 'tags.php' || $current_file_name == 'dimension_entry.php' || $current_file_name == 'work_centres.php' || $current_file_name == 'where_used_inquiry.php' || $current_file_name == 'purchasing_data.php' || $current_file_name == 'prices.php' || $current_file_name == 'item_units.php' || $current_file_name == 'item_categories.php' || $current_file_name == 'sales_kits.php' || $current_file_name == 'locations.php' || $current_file_name == 'item_codes.php'){?>
			@media (max-width: 768px){
				table { width: 100%; }
				input[type="text"]{ max-width: 220px;  }				
				table.tablestyle2 tr td:first-child { width:35%; }
				<?php  if($current_file_name != 'tax_groups.php'){?>
				table.tablestyle2 tr td { width:55%; }
				table tr td { display:inline-block; float:left; width:47%; text-align:left !important; padding-left:3%; }
				<?php } if($current_file_name != 'sales_kits.php'){?>
				 table tr:first-child { background: #eeeeee; }
				<?php } ?>
				table tr td.navibar{ width: 100%; line-height: 35px; }
				.navibar table.tablestyle tr:first-child{ background-color: transparent; }
				span.currentfg{ line-height: 40px; }
			}
			<?php  if($current_file_name == 'attachments.php'){?>
				table.tablestyle{ width: 100%; }

				 table.tablestyle tr td:nth-child(2) { width:40% ; }
				 table.tablestyle tr td:nth-child(7), table.tablestyle tr td:nth-child(8),table.tablestyle tr td:nth-child(9), table.tablestyle tr td:last-child { width:5% ; }
				<?php } ?>
		<?php }elseif($current_file_name == 'backups.php' || $current_file_name == 'gl_setup.php' || $current_file_name == 'display_prefs.php' || $current_file_name == 'company_preferences.php' ){?>
				@media (max-width: 768px){
					table { width: 100%; }
					table.tablestyle2 tr[valign="top"] td {  width: 100%; display: block; float: left; text-align: left; }
					table.tablestyle2 tr[valign="top"] td table  tr td{ width:45%; float: left; }
					table.tablestyle2 tr[valign="top"] td table  tr td.tableheader{ width:100%; float: left; }
					input[type="text"], select {	width: 95%; }
				}
				input[type="text"] { max-width : 250px; }
		<?php }elseif($current_file_name == 'reports_main.php'){?>
			@media (max-width: 768px){
				table td { width: 100%; display: block;  }
				td:first-child { line-height: 40px; }
				#rep_form table { width: 100%; }
			}
			#_page_body a {    line-height: 30px; padding-left: 8px;	}
			#_page_body td b { padding-left: 8px; }
		<?php }elseif( $current_file_name == 'supplier_payment.php' || $current_file_name == 'supplier_credit.php'|| $current_file_name == 'supplier_invoice.php'){?>
			@media (max-width: 768px){
				table.tablestyle2, #alloc_tbl table.tablestyle, table.tablestyle { width: 100%; }
				table.tablestyle2 tr[valign="top"] td {	width: 100%; display: block;}
				table.tablestyle2 tr[valign="top"] td table.tablestyle_inner { width: 100%; }
				table.tablestyle2 tr[valign="top"] td table.tablestyle_inner tr td { width: 45%; float:left; text-align: left;}
				
				input[type="text"]{	width: 95%; }
				<?php if($current_file_name == 'supplier_invoice.php'|| $current_file_name == 'supplier_credit.php'){?>
					table tr[valign="top"] td {	width: 100%; display: block; text-align: center;}
				 <?php } ?>
 				#alloc_tbl table.tablestyle tr, #grn_items table.tablestyle tr{ background: #eeeeee; }
				#alloc_tbl table.tablestyle tr td{ display:inline-block; float:left; width:33%; text-align:left; }

			}
		<?php }elseif($current_file_name == 'items.php' || $current_file_name == 'customer_branches.php' || $current_file_name == 'customers.php' || $current_file_name == 'suppliers.php'){
				
				if($current_file_name == 'items.php'){ ?>
			ul.ajaxtabs li button { padding : 3px 10px !important; }
			#price_table table.tablestyle{ width: 80%; }
			#price_table table.tablestyle tr td:first-child, #price_table table.tablestyle tr td:nth-child(6) {  width: 30%; }
			#price_table + table.tablestyle2 td{ border-bottom: none !important; }
		<?php 	} ?>

			@media (max-width: 768px){
				#_branch_tbl_span table tr, #_trans_tbl_span table.tablestyle tr, #_orders_tbl_span table.tablestyle tr, #price_table table.tablestyle tr:first-child { background: #eeeeee; }
				#doc_tbl table.tablestyle tr td, #_branch_tbl_span table.tablestyle tr td, #_trans_tbl_span table.tablestyle tr td,  #_orders_tbl_span table.tablestyle tr td  { display: inline-block; float: left; width:45%; text-align: left; }
				img#item_img {    height: 100px;}
				#_tabs_div table.tablestyle2 .tablestyle_inner tr td:first-child{width: 30%; display: block; float:left;}
				#_tabs_div table.tablestyle2 .tablestyle_inner tr td:nth-child(2){	width: 60%; display: block; float:left;}
				input[type="text"]{	width: 100%; }
				#_tabs_div table.tablestyle2 tr[valign="top"] td , #_tabs_div table.tablestyle2 .tablestyle_inner tr td[colspan="2"]{	width: 100%; display: block;}
				#price_table table.tablestyle { width: 90%; }
				#price_table table.tablestyle tr td {width: 30%; display: block; text-align: left; float:left;}
				#price_table table.tablestyle2 { width: 90%; }
				#price_details table.tablestyle2{ width: 80%; }
				#price_details table.tablestyle2 tr td { width: 50%; float: left;  text-align: left;}
				#price_details table.tablestyle2 tr td:first-child { width: 35%; float: left;  text-align: left;}
				#_tabs_div #price_table + table.tablestyle2 tr td { width: 50% !important; float: left;  text-align: left;}
				div#reorders table {  width: 80%;}
				div#status_tbl table.tablestyle{ width: 90%; }
				div#contacts_div table.tablestyle { width: 95%;}
				div#contacts_div table.tablestyle tr td{ width: 45%; display: block; float:left;}
				table tr td.navibar{ width: 100% !important; line-height: 35px; }
				.navibar table.tablestyle tr:first-child{ background-color: transparent; }
				table tr td.navibar table tr td{ width:25% !important; } 
				table.tablestyle tr[valign="top"] td{ width: 100% !important; text-align: left;}
				table.tablestyle tr[valign="top"] td table.tablestyle_inner{ width: 100%; margin: 5px 0;}
				#_tabs_div table.tablestyle_noborder tr td { width: 45%; display: block; float: left;}
				input.date { width: calc(100% - 20px); }
		<?php } 

		if($current_file_name == 'sales_groups.php' || $current_file_name == 'credit_status.php' || $current_file_name == 'sales_areas.php' || $current_file_name == 'sales_types.php' || $current_file_name == 'sales_people.php') { ?>
			@media (max-width: 768px){
				table { width: 100%; }
				table.tablestyle tr { background : #eeeeee; }
				table.tablestyle tr td {  display: inline-block; width:33%; float:left; text-align:left; }
			}
		<?php }
		if($current_file_name == 'purchasing_data.php'){ ?>
			#price_table table.tablestyle{ width: 80%; }
			#price_table table.tablestyle tr td:first-child, #price_table table.tablestyle tr td:nth-child(6) {  width: 30%; }
			#price_table + table.tablestyle2 td{ border-bottom: none !important; }
		<?php } 

		if($current_file_name == 'system_diagnostics.php'){ ?>
			table.tablestyle{ width: 100%; }
		<?php }
		
		if($current_file_name == 'dimension_entry.php' || $current_file_name == 'change_current_user_password.php' || $current_file_name == 'theme-options.php' || $current_file_name == 'sales_types.php' || 
		$current_file_name == 'sales_areas.php' || $current_file_name == 'revaluate_currencies.php' || $current_file_name == 'tags.php' || $current_file_name == 'credit_status.php' || $current_file_name == 'recurrent_invoices.php' || $current_file_name == 'prices.php' || $current_file_name == 'purchasing_data.php' ||$current_file_name == 'work_order_entry.php' || $current_file_name == 'work_centres.php' || $current_file_name == 'fiscalyears.php' || $current_file_name == 'item_tax_types.php' || $current_file_name == 'sales_groups.php' || $current_file_name == 'tags.php') {?>
			table {  width: auto; }
			
			@media (max-width: 768px){
				table {  width: 100%; }
				#price_details table { width:80%; }
				#price_details table.tablestyle2 tr td{ width:50%; }
				#price_details table.tablestyle2 tr td:first-child{ width:35%; }
			}
		<?php } ?>
		</style>