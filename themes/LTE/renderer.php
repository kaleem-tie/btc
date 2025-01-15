<?php
/*-------------------------------------------------------+
| LTE Theme for FrontAccounting
| http://www.kvcodes.com/
+--------------------------------------------------------+
| Author: Kvvaradha  
| Email: admin@kvcodes.com
+--------------------------------------------------------+*/
include_once("kvcodes.inc");

include_once("db/charts_db.inc");
global $lte_options;
create_tbl_option();
$lte_options = LteGetAll();

function addhttp($url) {
		    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
		        $url = "http://" . $url;
		    }
		    return $url;
		}
	class renderer{
		function get_icon($category){
			global  $path_to_root, $SysPrefs;

			if ($SysPrefs->show_menu_category_icons)
				$img = $category == '' ? 'right.gif' : $category.'.png';
			else	
				$img = 'right.gif';
			return "<img src='$path_to_root/themes/".user_theme()."/images/$img' style='vertical-align:middle;' border='0'>&nbsp;&nbsp;";
		}

		function wa_header(){
			if(isset($_GET['application']) && ($_GET['application'] == 'orders' || $_GET['application'] == 'orders#header'))
				page(_($help_context = "Sales"), false, true);
			elseif(isset($_GET['application']) && ($_GET['application'] == 'AP'|| $_GET['application'] == 'AP#header'))
				page(_($help_context = "Purchases"), false, true);
			elseif(isset($_GET['application']) && ($_GET['application'] == 'stock'|| $_GET['application'] == 'stock#header'))
				page(_($help_context = "Items & Inventory"), false, true);
			elseif(isset($_GET['application']) && ($_GET['application'] == 'manuf'|| $_GET['application'] == 'manuf#header'))
				page(_($help_context = "Manufacturing"), false, true);
			elseif(isset($_GET['application']) && ($_GET['application'] == 'proj'|| $_GET['application'] == 'proj#header'))
				page(_($help_context = "Dimensions"), false, true);
			elseif(isset($_GET['application']) && ($_GET['application'] == 'assets'|| $_GET['application'] == 'assets#header'))
				page(_($help_context = "Fixed Assets"), false, true);
			elseif(isset($_GET['application']) && ($_GET['application'] == 'GL'|| $_GET['application'] == 'GL#header'))
				page(_($help_context = "GL & Banking"), false, true);
			elseif(isset($_GET['application']) && ($_GET['application'] == 'extendedhrm'|| $_GET['application'] == 'extendedhrm#header'))
				page(_($help_context = "HRM and Payroll"), false, true);
			elseif(isset($_GET['application']) && ($_GET['application'] == 'system'|| $_GET['application'] == 'system#header'))
				page(_($help_context = "Setup Menu"), false, true);
			elseif(!isset($_GET['application']) || ($_GET['application'] == 'dashboard'|| $_GET['application'] == 'dashboard#header'))
				page(_("Dashboard"), false, true);
			else
				page(_($help_context = "Main Menu"), false, true);
		}

		function wa_footer(){
			end_page(false, true);
		}

		function menu_header($title, $no_menu, $is_index){
			global $path_to_root, $SysPrefs, $db_connections, $icon_root, $version, $lte_options ;			
			
			require_once("ExtraSettings.php"); ?>
			<script> 
			(function() {
			    var link = document.querySelector("link[rel*='icon']") || document.createElement('link');
			    link.type = 'image/x-icon';
			    link.rel = 'shortcut icon';
			    <?php if(isset($lte_options['favicon']) && file_exists(dirname(__FILE__).'/images/'.$lte_options['favicon']) && !is_dir(dirname(__FILE__).'/images/'.$lte_options['favicon'])){
			    	echo " link.href = '$path_to_root/themes/".user_theme()."/images/".$lte_options['favicon']."?".rand(2,5)."'; ";
			    }else {
			    	echo "link.href = '$path_to_root/themes/".user_theme()."/images/favicon.ico?".rand(2,5)."';";
			    } ?>
			    
			    document.getElementsByTagName('head')[0].appendChild(link);
			}());
			</script> 		
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">

		  <link rel="stylesheet" href="<?php echo $path_to_root."/themes/".user_theme(); ?>/css/bootstrap.min.css">
		  <!-- Font Awesome -->
		  <link rel="stylesheet" href="<?php echo $path_to_root."/themes/".user_theme(); ?>/css/font-awesome.min.css"><link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
		  <!-- Ionicons -->
		  <link rel="stylesheet" href="<?php echo $path_to_root."/themes/".user_theme(); ?>/css/ionicons.min.css">
		  <!--Bootstrap Selectpicker-->
		  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">
		  <!-- jvectormap -->
		  <link rel="stylesheet" href="<?php echo $path_to_root."/themes/".user_theme(); ?>/css/jquery-jvectormap.css">
		  <!-- Theme style -->
		  <link rel="stylesheet" href="<?php echo $path_to_root."/themes/".user_theme(); ?>/css/AdminLTE.css">
		  <!-- AdminLTE Skins. Choose a skin from the css/skins
		       folder instead of downloading all of them to reduce the load. -->
		  <link rel="stylesheet" href="<?php echo $path_to_root."/themes/".user_theme(); ?>/css/_all-skins.min.css">

		  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
		  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		  <!--[if lt IE 9]>
		  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
		  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		  <![endif]-->

		  <!-- Google Font -->
    	  <link href="https://fonts.googleapis.com/css?family=Lato:300,300i,400,400i,700|Roboto:300,300i,400,400i,500,500i,700&display=swap" rel="stylesheet">
		  <style>
            body {
              font-family: 'Lato', sans-serif;
            }
            /* H1 - H6 font */
            h1,
            h2,
            h3,
            h4,
            h5,
            h6,
            .h1,
            .h2,
            .h3,
            .h4,
            .h5,
            .h6 {
              font-family: 'Roboto', sans-serif;
            }
		  </style>
		  
		  
        <?php 
 
			
			$color_scheme = (isset($lte_options['color_scheme']) ? $lte_options['color_scheme'] : 'black'); 
			
			//echo '<link rel="stylesheet" href="'.$path_to_root.'/themes/LTE/css/colorschemes/'.$color_scheme.'.css">';
			//require_once("ExtraSettings.php"); 
      if ($no_menu)
        $background='noMenu';
      else
        $background = '';
			echo '<div class="wrapper '.$background.'">'; // tabs

			$indicator = "$path_to_root/themes/".user_theme(). "/images/ajax-loader.gif";
			if (!$no_menu)			{
				add_access_extensions();
				$applications = $_SESSION['App']->applications;
				$local_path_to_root = $path_to_root;
				$sel_app = $_SESSION['sel_app'];
				if(isset($lte_options['logo']) && file_exists(dirname(__FILE__) .'/images/'.$lte_options['logo']) && !is_dir(dirname(__FILE__) .'/images/'.$lte_options['logo'])){
					$logo_img = $lte_options['log'].'?'.rand(2,5);
				}else
					$logo_img = 'LTE.png?'.rand(2,5);
				$logo_img = 'LTE.png?'.rand(2,5);
				$role_sql = "SELECT role FROM ".TB_PREF."security_roles WHERE id=".$_SESSION['wa_current_user']->access." LIMIT 1";
				$role_res = db_query($role_sql, "Can't get current_user Role");
				if(db_num_rows($role_res) > 0 ) {
					if($row = db_fetch($role_res))
						$role_name = $row[0];
				} else
					$role_name  ='';
				
				//Newly Added for advance search  ## Ramesh 15-12-2022
				echo "<script type='text/javascript' src='$path_to_root/ajax_dropdown/select2/dist/js/select2.min.js' defer></script>";
				echo "<link href='$path_to_root/ajax_dropdown/select2/dist/css/select2.min.css' rel='stylesheet' type='text/css'>";
				echo "<script type='text/javascript' src='$path_to_root/ajax_dropdown/select2/jquery-3.0.0.js'></script>";
				//end 
?>
						<style>
							button img, span {padding: 0px;}
							#select2-stock_id-results {	text-align: left;}
							.select2-results {	text-align: left;}

						</style>

<?php 	



				echo ' <header class="main-header" style="position:fixed"> <!-- Logo -->
    
    <a class="logo" href="'.$path_to_root.'"> <img src="'. $path_to_root.'/themes/LTE/images/'.$logo_img.'" style="max-width: 120px;padding-top: 6px;" ><!--'.$db_connections[user_company()]["name"].' --> </a>

    <!-- Header Navbar: style can be found in header.less -->
    <nav class="navbar navbar-static-top" style="position: fixed;left:0;right:0;top:0">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">'._("Toggle navigation").'</span>
      </a>';

      include_once("$path_to_root/themes/".user_theme()."/notification.php");
            $noti = new show_notification();
			
			
	


      echo '<!-- Navbar Right Menu -->
      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">';
		
		
		 echo '<li><a href="'. $path_to_root.'/sales/inquiry/sales_delivery_calendar_order_wise.php?" class="">'._("Sales Delivery -   Order").' <img src="'. $path_to_root.'/themes/LTE/images/cal.gif" style="vertical-align:middle;padding-bottom:4px;width:16px;height:16px;border:0;" alt="Calendar"></a></li>';
		
		echo '<li><a href="'. $path_to_root.'/sales/inquiry/sales_delivery_calendar.php?" class="">'._(" - Item").' <img src="'. $path_to_root.'/themes/LTE/images/cal.gif" style="vertical-align:middle;padding-bottom:4px;width:16px;height:16px;border:0;" alt="Calendar"></a></li>';	
		
          '<!-- Messages: style can be found in dropdown.less-->';
          $noti->get_overdue_invoices();
          
          echo '<!-- Notifications: style can be found in dropdown.less -->';
          $noti->get_inventory_reorder();

          
          echo '<!-- Tasks: style can be found in dropdown.less -->';
          $noti->get_supplier_payments(); 
		  
		  
          
          echo '<!-- User Account: style can be found in dropdown.less -->
          <li class="dropdown user user-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
             
     <i class="fa fa-user"></i> 
              <span class="hidden-xs">'.$_SESSION['wa_current_user']->name.'</span>
            </a>
            <ul class="dropdown-menu">
              <!-- User image -->
              <li class="user-header">
               <img src="'.$path_to_root.'/themes/LTE/images/user-img.jpg" style="height: 100px;object-fit: cover;" class="img-circle" alt="User Image"> 
                <p>
                  <a href="'.$path_to_root.'/admin/display_prefs.php?" >'.$_SESSION['wa_current_user']->name.'</a> <br><small>'.$role_name .'
                  <br> Last Seen -'.gmdate("Y-m-d H:i:s", $_SESSION['wa_current_user']->last_act).'</small>
                </p>
              </li>
          
              <!-- Menu Footer-->
              <li class="user-footer">
                <div class="pull-left">
                  <a href="'. $path_to_root.'/admin/display_prefs.php?" class="btn btn-default btn-flat">'._("Profile").'</a>
                </div>
                <div class="pull-right">
                  <a href="'. $path_to_root.'/themes/LTE/logout.php?" class="btn btn-default btn-flat">'._("Logout").'</a>
                </div>
              </li>
            </ul>
          </li>
          <!-- Control Sidebar Toggle Button --> ';

         // $MasterRole = (isset($lte_options['theme']) ? $lte_options['theme'] : '' );

         // if(!$MasterRole || in_array(770, $_SESSION["wa_current_user"]->role_set)){
        //    echo '<li><a href="'.$path_to_root.'/themes/LTE/theme-options.php" ><i class="fa fa-gears"></i></a> </li>';
      //    } 
		  echo '
        </ul>
      </div>

    </nav>
  </header>

  <!-- Left side column. contains the logo and sidebar -->
  <aside class="main-sidebar" style="position:fixed;height:100%;overflow-y:scroll">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel">
            <div class="pull-left image">
                <img src="'.$path_to_root.'/themes/LTE/images/user-img.jpg" class="img-circle" alt="User Image">
            </div>
            <div class="pull-left info">
                <p>'.$db_connections[user_company()]["name"].'</p> 
                <a href="'. $path_to_root.'/admin/display_prefs.php?"><i class="fa fa-circle text-success"></i> '.$_SESSION['wa_current_user']->name.'</a>
            </div>
        </div>';
		if(isset($lte_options['enable_master_login']) && $lte_options['enable_master_login'] == 1) {
       		include_once($path_to_root."/themes/LTE/includes/users.php");
			$row = get_master_login($_SESSION['wa_current_user']->loginname);
			$companies = unserialize(base64_decode($row['companies']));
			$local_path_to_root = $path_to_root; 
			if(!empty($companies)){
				if(!in_array($_SESSION["wa_current_user"]->company, $companies)){
					$_SESSION['wa_current_user']->company = $companies[0];
					header("location:index.php");
				}
			}
        }
       echo '<div class="user-panel user-panel-dropdown">';
      if(isset($lte_options['enable_master_login']) && $lte_options['enable_master_login'] == 1) {
       echo '<select name="CompanyName" class="ChangeCompany" data-live-search="true">';
							echo '<option value="'.$_SESSION['wa_current_user']->company.'" selected> '.$db_connections[$_SESSION['wa_current_user']->company]['name'].'</option>';
							foreach($db_connections as $cid => $single){
								if(isset($companies) && in_array($cid, $companies) && $cid != $_SESSION['wa_current_user']->company)
								echo '<option value="'.$cid.'">' . $db_connections[$cid]["name"] .'</option>';
							}


        echo ' </select>';
    }
        echo '</div>
      
      <!-- sidebar menu: : style can be found in sidebar.less -->
      <ul class="sidebar-menu" data-widget="tree">';
        if($lte_options['hide_dashboard'] == 0){
				echo '<li class="click-ssmenu '.((isset($_GET['application']) && $_GET['application']=='dashboard') ? 'active' : '').'" >  <a href="'.$path_to_root.'?application=dashboard" accesskey="D"> <i class="fa fa-pie-chart"></i> <span> '._("Dashboard").' </span></a> </li>';
		}

		$n=1;foreach($applications as $app){
                    if ($_SESSION["wa_current_user"]->check_application_access($app))  {
                    	if(trim($app->id) == 'orders')
								$icon_root = 'line-chart';
							elseif(trim($app->id) == 'AP')
								$icon_root = 'money';
							elseif(trim($app->id) == 'stock')
								$icon_root = 'cubes';
							elseif(trim($app->id) == 'manuf')
								$icon_root = 'industry';
							elseif(trim($app->id) == 'assets')
								$icon_root = 'home';
							elseif(trim($app->id) == 'proj')
								$icon_root = 'binoculars';
							elseif(trim($app->id) == 'GL')
								$icon_root = 'institution';
							elseif(trim($app->id) == 'setup')
								$icon_root = 'gear';
								elseif(trim($app->id) == 'plant_maintenance')
								$icon_root = 'wrench';
								elseif(trim($app->id) == 'job_work')
								$icon_root = 'suitcase';
							else
								$icon_root ='suitcase';

						$acc = access_string($app->name);
						$first = $_SESSION['l1first'];
						$ff = 'none';
						$ff1 = 'none';
						if($first == $n){ $ff = "block"; }else{ $ff = "none"; }
						if($first == $n){ $ff1 = "menu-open"; }

						// if ($_SESSION["wa_current_user"]->can_access_page($app->id))  {
							echo "<li class='treeview click-menu ".$ff1."' row-id='".$n."''><a class='".($sel_app == $app->id ? 'active' : '')
                            ."' href='$local_path_to_root/index.php?application=".$app->id
                            ."'$acc[1]> <i class='fa fa-".$icon_root."'></i> <span>" .($app->id == 'GL' ? 'GL & Banking' : $acc[0] ). " </span>
                            	<span class='pull-right-container'> <i class='fa fa-angle-left pull-right'></i> </span> </a> 

                            <ul class='treeview-menu' style='display: ". $ff. "'>";

                            $kv_module_icon = array('credit-card', 'info-circle', 'wrench', 'tag', 'cog' ); 
							$kv_small = 0 ; 
							
							$b=1;foreach ($app->modules as $module)   {
								$second = $_SESSION['l2second'];
								$ss = 'none';
								$ss1 = 'none';
								if($first == $n && $second == $b){ $ss = "block"; }else{ $ss = "none"; }
								if($first == $n && $second == $b){ $ss1 = "menu-open"; }
								if (isset($module->name)) {// If parent
									echo "<li class='treeview click-smenu ".$ss1."' row-id='".$b."'>
									<a href='#'><i class='fa fa-".$kv_module_icon[$kv_small]."'></i>  " . _($module->name) . "
									<span class='pull-right-container'> <i class='fa fa-angle-left pull-right'></i> </span></a>";
									echo "	<ul class='treeview-menu ' style='display: ". $ss. "'>";
								
								$lapps = $rapps = array();
								foreach ($module->lappfunctions as $lappfunction)
									$lapps[] = $lappfunction;
								foreach ($module->rappfunctions as $rappfunction)
									$rapps[] = $rappfunction;
								$lapplication = $rapplication = array();
								echo ' <li> <ul>' ; 
								$c=1;foreach ($lapps as $lapplication)    {
									$third = $_SESSION['l2third'];$cc='';
									if($first == $n && $second == $b && $third == $c){ $cc = "color: #e61937 !important"; }
									$lnk = access_string($lapplication->label);
									if ($_SESSION["wa_current_user"]->can_access_page($lapplication->access)) {
										if ($lapplication->label != "") {
											echo  "<li class='siblingMenus'><a class='click-ssmenu' style='".$cc."' row-one='".$n."' row-two='".$b."' row-three='".$c."' href='".$path_to_root."/".$lapplication->link."' $lnk[1]><i class='fa fa-circle-o' style='font-size:10px' ></i>   "._($lnk[0])."</a></li> \n";
										}
									}elseif (!$_SESSION["wa_current_user"]->hide_inaccessible_menu_items()){}
										//echo "<a href='#' class='disabled'>".$lnk[0]."</a>";																				
								$c++;}

								echo '</ul></li>  <li> <ul>' ; 
								$d=count($lapps)+1;
								foreach ($rapps as $rapplication)    {
									$third = $_SESSION['l2third'];$cc='';
									if($first == $n && $second == $b && $third == $d){ $cc = "color: #f39222 !important"; }
									$lnk = access_string($rapplication->label);
									if ($_SESSION["wa_current_user"]->can_access_page($rapplication->access)) {
										if ($rapplication->label != "") {
											echo  "<li class='siblingMenus'><a class='click-ssmenu' style='".$cc."' row-one='".$n."' row-two='".$b."' row-three='".$d."' href='".$path_to_root."/".$rapplication->link."'$lnk[1]><i class='fa fa-circle-o' style='font-size:10px' ></i>   "._($lnk[0])."</a></li> \n";
										}
									}
									elseif (!$_SESSION["wa_current_user"]->hide_inaccessible_menu_items()){}										
								$d++;}
								echo '</ul></li> '; 
								}
								
								if (isset($module->name)) { // If parent
									echo "</ul>"; 
									echo "</li>";
								}	
								echo '<div style="clear:both"> </div> ' ;
								$kv_small++;
							$b++;}
							echo "</ul> </li>";  
						}
           $n++; }

       echo ($_SESSION['wa_current_user']->company == 0 ? '<li class="click-ssmenu ">  <a href="'.$path_to_root.'/themes/LTE/master-login.php"> <i class="fa fa-power-off"></i> <span> Master Login </span></a> </li>' : '').' </ul> 
    </section>
    <!-- /.sidebar -->
  </aside>';	 		   	
				

				show_users_online($_SESSION["wa_current_user"]->username);
  // echo $_SESSION["wa_current_user"]->username;
			}

       echo '     <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper '.($no_menu ? 'noMenu' : '' ).'">
          <!-- Content Header (Page header) -->
          <section class="content-header">
            <h1>    '.   _($title) .'     </h1>';

             if ($SysPrefs->help_base_url != null && $lte_options['hide_help_link']== 0 ){
              echo  '<ol class="breadcrumb">
                  <li><a href="'. help_url().'"  onclick="javascript:openWindow(this.href,this.target); return false;" ><i class="fa fa-life-ring"></i> </a></li>
                 </ol>' ;
          }
          echo '</section>';    

			
			if ($no_menu){	// ajax indicator for installer and popups
				echo "<center><table class='tablestyle_noborder'>"
					."<tr><td><img id='ajaxmark' src='$indicator' align='center' style='visibility:hidden;' alt='ajaxmark'></td></tr>"
					."</table></center> ";
					echo '<div class="content">';
			} elseif ($title && !$is_index)	{
				/*echo "<center><table id='title'><tr><td width='100%' class='titletext'>$title</td>"
				."<td align=right>"
				.(user_hints() ? "<span id='hints'></span>" : '')
				."</td>"
				."</tr></table></center>";	*/			
			}
			$unread = '';
			echo "<img id='ajaxmark' src='$indicator' align='center' style='visibility:hidden;' alt='ajaxmark'>";
			echo '
<div class="parent">
<div class="chat-box">
<ul class="chat-box-list">
<li style="cursor:pointer;border-top:3px solid #f39222" class="hide-chat-list"><b>Chats</b><i class="fa fa-minus hide-chat-list pull-right"></i>
';
$my_id = $_SESSION['wa_current_user']->user;
$users_data = query_data("users", array("inactive=0", "id!=$my_id"));
/*
foreach($users_data as $user_data){ $unread += count_unread($user_data['id']);$usronl = get_onlineuser($user_data['user_id']);$indicate=0;if($usronl == 0){$indicate = "text-red";}else{$indicate = "text-green";}
	echo "<li class='load-chat' user-id='". $user_data['id'] ."'>".$user_data['user_id']."<i class='fa fa-circle ".$indicate." pull-right'></i></li>";
}
*/
echo '</ul></div>
<div class="chat-bar">Chats <i class="fa fa-circle text-green"></i><span class="pull-right label label-warning">'.$unread.'</span></div>
<div class="chat-wrapper">
<div class="">
            <div class="box box-warning direct-chat direct-chat-warning" style="margin-bottom:0">
                <div class="box-header with-border">
                    <h3 class="box-title chat-title">Direct Chat</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i
                                class="fa fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-box-tool hide-chat"><i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <!-- Conversations are loaded here -->
                    <div class="direct-chat-messages">
                        <div class="show-chat"></div>
                    </div>
                    <!--/.direct-chat-messages-->
                </div>
                <!-- /.box-body -->
                <div class="box-footer">
                    <form action="#" method="post">
                        <div class="input-group">
                            <input type="text" name="message" placeholder="Type Message ..." class="form-control message">
                            
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-warning btn-flat submit-chat">Send</button>
                            </span>
                        </div>
                        <div class="image-upload" title="file upload">
							    <label for="file-input">
							        <img src="'. $path_to_root.'/themes/LTE/images/folder.png" />
							    </label>

							    <input id="file-input" type="file"/>
							</div>
                    </form>
                </div>
                <!-- /.box-footer-->
            </div>
        </div>
</div>
</div>';
		}

		function menu_footer($no_menu, $is_index){
			global $version, $path_to_root, $Pagehelp, $Ajax, $SysPrefs;

			include_once($path_to_root . "/includes/date_functions.inc");

			
			$app_title = (isset( $lte_options['powered_name']) ? $lte_options['powered_name'] : 'FrontAccounting') ;
			

			if(isset($lte_options['powered_url']) && $lte_options['powered_url'] == 1){
				$powered_url = addhttp($lte_options['powered_url']);
			}else 
				$powered_url = 'http://frontaccounting.com';			
			
			echo '</div></div>';

			//if ($no_menu == false){

				if(isset($_GET['application']) && $_GET['application'] == 'stock')
					echo '</div>';
				echo '<footer class="main-footer '.($no_menu ? 'noMenu' : '' ).'"> ';
      if(kv_get_option('hide_version')== 0 ){		echo '<div class="pull-right hidden-xs"> <b>Version</b> '. $version.'</div>';		}
      echo '<strong>'._("Copyright").' &copy; '.date('Y').' <a href="'.$powered_url.'" target="_blank"></a>  '._("All rights  reserved.").'  </footer>';
    //} 
echo '</div>'; ?>
<!-- jQuery 3 -->
<script src="<?php echo $path_to_root."/themes/".user_theme(); ?>/js/jquery.min.js"></script>
<?php 

$color_scheme = (isset($lte_options['color_scheme']) ? $lte_options['color_scheme'] : 'black'); 
if(kv_get_option('color_scheme') != 'false'){
        $color_scheme = kv_get_option('color_scheme'); 
      }else{
        $color_scheme= 'black';
      }
       ?>

<!-- Bootstrap 3.3.7 -->
<script src="<?php echo $path_to_root."/themes/".user_theme(); ?>/js/bootstrap.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
<!-- FastClick -->
<script src="<?php echo $path_to_root."/themes/".user_theme(); ?>/js/fastclick.js"></script>
<!-- AdminLTE App -->
<script src="<?php echo $path_to_root."/themes/".user_theme(); ?>/js/adminlte.min.js"></script>
<!-- Sparkline -->
<script src="<?php echo $path_to_root."/themes/".user_theme(); ?>/js/jquery.sparkline.min.js"></script>
<!-- jvectormap  -->
<script src="<?php echo $path_to_root."/themes/".user_theme(); ?>/js/jquery-jvectormap-1.2.2.min.js"></script>
<script src="<?php echo $path_to_root."/themes/".user_theme(); ?>/js/jquery-jvectormap-world-mill-en.js"></script>
<!-- SlimScroll -->
<script src="<?php echo $path_to_root."/themes/".user_theme(); ?>/js/jquery.slimscroll.min.js"></script>
<!-- ChartJS -->
<script src="<?php echo $path_to_root."/themes/".user_theme(); ?>/js/Chart.js"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="<?php echo $path_to_root."/themes/".user_theme(); ?>/js/dashboard2.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="<?php echo $path_to_root."/themes/".user_theme(); ?>/js/demo.js"></script>

<script  type="text/javascript"> 
	$('body').addClass('skin-<?php echo $color_scheme; ?> sidebar-mini');
</script>
<script  type="text/javascript"> 
		//<![CDATA[
		function myFunction() {
			  var x = document.getElementById("CompanyList");
			  if (x.className.indexOf("w3-show") == -1) {
			    x.className += " w3-show";
			  } else { 
			    x.className = x.className.replace(" w3-show", "");
			  }
			}
				$(".ChangeCompany").on('change', function (e) {  			   		
			   		var Cid = $(this).val();			   		
			   		$.ajax({
				        type: "POST",
				        url: "<?php echo $path_to_root; ?>/themes/LTE/includes/ajax.php?ChangeCompany="+Cid,
				        data: 0,
				        success: function(data){        
				          if(data.trim()){
				          	window.location.reload();				           
				          }else{
				            alert("Failed to Delete". data);
				          }               
				        }
				    });
				   });
				   var aa = '';
		$('.click-menu').click(function() {
			row_id= $(this).attr('row-id');
			aa = row_id;
			// alert(a);
		})
		$('.click-ssmenu').click(function() {
			// return false;
			row_id1= $(this).attr('row-one');
			row_id2= $(this).attr('row-two');
			row_id3= $(this).attr('row-three');
			// a = row_id;
			// alert(row_id1 +' & '+ row_id2 + ' & ' + row_id3);
			dataString = '?first=' + row_id1 + '&second=' + row_id2 + '&third=' + row_id3 + "&type=sidebar";
				$.ajax({
					url: "<?php echo $path_to_root; ?>/themes/LTE/session.php"+dataString,
					type:'get',
					cache:false,
					success:function(data){
						console.log(data);
					}
				})
			})
		$('.chat-bar').click(function(){
			$(this).hide();
			$('.chat-box').css('bottom', '0');
		})
		$('.hide-chat').click(function(){
			$('.chat-wrapper').hide();
		})
		$('.hide-chat-list').click(function(){
			$('.chat-bar').show();
			$('.chat-box').css('bottom', '-100vh');
		})
		$('.load-chat').click(function(){
        user_id = $(this).attr('user-id');
        // alert(user_id);
        $('.show-chat').empty();
        $.ajax({
            url: '<?php echo $path_to_root; ?>/themes/LTE/db/chats.php?type=load_chat',
            data:{user_id:user_id},
            type:"post",
            cache:false,
            success:function(data) {
            	// $('.chat-title').text(get_usermame(user_id)).show();
            	get_username(user_id);
            	$('.chat-wrapper').show();
                $('.show-chat').html(data).show();
                $('.direct-chat-messages').scrollTop($(document).height()); 
            }
        })
    })
		function get_username(user_id) {
			$.ajax({
            url: '<?php echo $path_to_root; ?>/themes/LTE/db/chats.php?type=get_username',
            data:{user_id:user_id},
            type:"post",
            cache:false,
            success:function(data) {
            	console.log(data);
            	$('.chat-title').text(data).show();
            }
        })
		}
    $('.submit-chat').click(function(){
    	var fd = new FormData();
		fd.append('file',$('#file-input')[0].files[0]);
		fd.append('message',$('.message').val());

        // message = $('.message').val();
        // alert(message);
        $.ajax({
            url: '<?php echo $path_to_root; ?>/themes/LTE/db/chats.php?type=insert_chat',
            data:fd,
            type:"post",
		    cache: false,
		    contentType: false,
		    processData: false,   
            success:function(data) {
                refresh_chat();
                // console.log(data);
                $('.message').val("");
                document.getElementById("file-input").value = "";

                $('.direct-chat-messages').scrollTop($(document).height()); 
            }
        })
    })
    $('.message').keyup(function(e){
    	var key = e.which;
    	if(e.keyCode == 13){
    		$('.submit-chat').trigger('click');
    	}
    })
    setInterval(function(){refresh_chat();new_messages();}, 10000);
    function refresh_chat(){
        $.ajax({
            url: '<?php echo $path_to_root; ?>/themes/LTE/db/chats.php?type=refresh_chat',
            type:"get",
            cache:false,
            success:function(data) {
                $('.show-chat').html(data).show();

                $('.direct-chat-messages').scrollTop($(document).height()); 
            }
        })
    }
    function new_messages(){
        $.ajax({
            url: '<?php echo $path_to_root; ?>/themes/LTE/db/chats.php?type=new_messages',
            type:"get",
            cache:false,
            success:function(data) {
            	var obj = JSON.parse(data);
                console.log(obj[0] +" "+ obj[1]);
                const d1 = parseInt(obj[0]);
                const d2 = parseInt(obj[1]);
                if(d1 !== 0 && d2 > 20){
                	console.log("no messages");
                }else{
                	if(d1 === 0 && d2 <= 10){
                		if(d1 !== 0 && d2 !== 0){
		                	var myAudio = new Audio('https://aaa-tierp.com/assets/message-sound.mp3');
							myAudio.play();
						}
					}
                }
            }
        })
    }

			   // $('#ChangeCompany').select2({     // var itemName = $('select[name="itemName"]').val();
			   //      placeholder: "<?php echo _('Select a Company'); ?>",
			   //      allowClear: true,
			   //      ajax: {
			   //        url: "<?php //echo $path_to_root; ?>/uploads/includes/ajax.php?GetCompany=yes",
			   //        dataType: 'json',
			   //        delay: 250,
			   //        processResults: function (data) {            
			   //          return {
			   //            results: data
			   //          };           
			   //        },
			   //      }
			   //  });
		// 	});
		// });
		//]]> 
</script>
<?php 
		}

		function display_applications(&$waapp)	{
			global $path_to_root;

			$selected_app = $waapp->get_selected_application();
			if (!$_SESSION["wa_current_user"]->check_application_access($selected_app))
				return;

			if (method_exists($selected_app, 'render_index'))	{
				$selected_app->render_index();
				return;
			}

			if( !isset($_GET['application']) || $_GET['application'] == 'dashboard'){	
				require("dashboard.php");
			}else{

				echo '<div class="MenuPage"> ';
				foreach ($selected_app->modules as $module)	{
	        		if (!$_SESSION["wa_current_user"]->check_module_access($module))
	        			continue;
					// image
					echo '<div class="MenuPart"><div class="subHeaders"> '.$module->name.'</div>';
					echo '<ul class="left">';

					foreach ($module->lappfunctions as $appfunction){
						$img = $this->get_icon($appfunction->category);
						if ($appfunction->label == "")
							echo "&nbsp;<br>";
						elseif ($_SESSION["wa_current_user"]->can_access_page($appfunction->access)) {
							echo '<li>'.$img.menu_link($appfunction->link, $appfunction->label)."</li>";
						}
						//elseif (!$_SESSION["wa_current_user"]->hide_inaccessible_menu_items())	{
							//echo '<li>'.$img.'<span class="inactive">'.access_string($appfunction->label, true)."</span></li>";
						//}
					}
					echo "</ul>";
					if (sizeof($module->rappfunctions) > 0)	{
						echo "<ul class='right'>";
						foreach ($module->rappfunctions as $appfunction){
							$img = $this->get_icon($appfunction->category);
							if ($appfunction->label == "")
								echo "&nbsp;<br>";
							elseif ($_SESSION["wa_current_user"]->can_access_page($appfunction->access)) {
								echo '<li>'.$img.menu_link($appfunction->link, $appfunction->label)."</li>";
							}
							//elseif (!$_SESSION["wa_current_user"]->hide_inaccessible_menu_items())	{
								//echo '<li>'.$img.'<span class="inactive">'.access_string($appfunction->label, true)."</span></li>";
							//}
						}
						echo "</ul>";
					}
					echo "<div style='clear: both;'></div>";
				}
				echo "</div></div> </div> </div>";
			}			
  		}
	}
