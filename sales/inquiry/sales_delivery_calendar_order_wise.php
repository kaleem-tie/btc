<?php 
$page_security = 'SA_SALES_DELIVERY_CALENDAR_ORDERWISE';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

$help_context = "Sales Delivery Calendar (Order Wise)";
page(_($help_context));
simple_page_mode(true);

function get_all_sales_delivery_calendar_order_wise_info()
{
	$today = Today(); 
    $today = date2sql($today);
   $sql = "SELECT DISTINCT 
			sorder.order_no,
			sorder.reference,
			sorder.delivery_date,
			debtor.name,
			sorder.ord_date,
			DATEDIFF(sorder.delivery_date,'$today') AS days,
			0 AS has_child,
            'so' as sale_table,
			'A' as order_asc			
		FROM ".TB_PREF."sales_orders as sorder,
		    ".TB_PREF."sales_order_details as line, 
			".TB_PREF."debtors_master as debtor 
			WHERE sorder.order_no = line.order_no
			AND sorder.trans_type = line.trans_type
			AND sorder.trans_type = 30
			AND sorder.debtor_no = debtor.debtor_no
			AND line.quantity-line.qty_sent>0
			AND sorder.reference!='auto'
			AND line.planned_status!=1 and line.quantity>line.qty_sent 
			UNION
		SELECT DISTINCT soplan.order_no,
         sorder.reference,
		 soplan.planned_date AS delivery_date,
		 debtor.name,
		 sorder.ord_date,
		 DATEDIFF(soplan.planned_date,'$today') AS days,
		 soplan.has_child,
        'sd' as sale_table,
        'A' as order_asc  		
    	FROM ".TB_PREF."sales_delivery_plan as soplan,
		".TB_PREF."sales_orders as sorder,
		".TB_PREF."sales_order_details as line,
		".TB_PREF."debtors_master as debtor 
    	WHERE soplan.order_no = sorder.order_no
		AND sorder.order_no = line.order_no
		AND sorder.trans_type = line.trans_type
		AND soplan.order_line_id = line.id
    	AND sorder.debtor_no = debtor.debtor_no
		AND soplan.has_child!=1 and line.quantity>line.qty_sent  
		UNION
		SELECT DISTINCT sorder.order_no,sorder.reference,
		dt.tran_date as delivery_date,
		debtor.name,
		sorder.ord_date,
		'0' as days,
		0 as has_child,
		'do' as sale_table,
		'C' as order_asc		
		FROM 0_debtor_trans dt,
		0_sales_orders sorder,
		0_debtors_master debtor 
		WHERE dt.order_=sorder.order_no 
		and dt.type=13 
		and sorder.trans_type=30 
		and sorder.debtor_no=dt.debtor_no 
		and sorder.debtor_no=debtor.debtor_no 
		and dt.debtor_no=debtor.debtor_no 
		and dt.reference!='auto'
		UNION
		SELECT DISTINCT 
			sorder.order_no,
			sorder.reference,
			sorder.delivery_date,
			debtor.name,
			sorder.ord_date,
			0 AS days,
			0 AS has_child,
            'co' as sale_table,
			'B' as order_asc				
		FROM ".TB_PREF."cancel_sales_orders as sorder,
		    ".TB_PREF."debtors_master as debtor 
			WHERE sorder.trans_type = 30
			AND sorder.debtor_no = debtor.debtor_no
			AND sorder.reference!='auto'
		
		GROUP BY sorder.order_no,
					sorder.debtor_no,
					sorder.customer_ref,
					sorder.ord_date,
					sorder.deliver_to ORDER BY days DESC	";
					
								
        return db_query($sql);
}


function get_item_rescheduled_date($order_no,$order_line_id)
{
	$sql = "SELECT planned_date FROM ".TB_PREF."sales_delivery_plan 
			WHERE order_no = ".db_escape($order_no)."
			AND order_line_id = ".db_escape($order_line_id)."";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
	
}	

function get_so_order_status($order_no)
{
	$sql = "SELECT sum(qty_sent),sum(sod.planned_status) FROM ".TB_PREF."sales_orders so,".TB_PREF."sales_order_details sod WHERE so.order_no=sod.order_no and so.trans_type=sod.trans_type and so.trans_type='30' 
	and so.order_no=".db_escape($order_no)."";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	
	if($result[0]>0)
	return 1;
    else if($result[1]>0)
	return 1;
    else 
	return 0;	
}

function get_reschedule_order_status($order_no,$delivery_date)
{
	$sql = "SELECT sum(sod.qty_sent),sum(sod.quantity) FROM ".TB_PREF."sales_orders so,".TB_PREF."sales_order_details sod WHERE so.order_no=sod.order_no and so.trans_type=sod.trans_type and so.trans_type='30' 
	and so.order_no=".db_escape($order_no)."";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	
	if($result[0]>0)
	return 1;

    $order_qty=$result[1];
     
	$sql = "SELECT sum(sod.quantity) FROM ".TB_PREF."sales_order_details sod,".TB_PREF."sales_delivery_plan sd WHERE sod.order_no=sd.order_no and sod.trans_type=30 and sod.id=sd.order_line_id 
	and sod.order_no=".db_escape($order_no)."";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	
	if($order_qty>$result[0])
	return 1;
    
	return 0;	
}

function get_item_rescheduled_update_reason($order_no,$order_line_id)
{
	$sql = "SELECT update_reason FROM ".TB_PREF."sales_delivery_plan 
			WHERE order_no = ".db_escape($order_no)."
			AND order_line_id = ".db_escape($order_line_id)."";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
}


function get_item_actual_delivery_date($order_no)
{
	$sql = "SELECT delivery_date FROM ".TB_PREF."sales_orders 
			WHERE order_no = ".db_escape($order_no)."";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
	
}	


$result=get_all_sales_delivery_calendar_order_wise_info();
$i=0;
$tasks=array();
while($task=db_fetch($result))
{
	
	if($task['planned_status']==1)
	{
	$planned_date = get_item_rescheduled_date($task['order_no'],$task['order_line_id']);
	$actual_delivery_date = get_item_actual_delivery_date($task['order_no']);
	
	$planned_update_reason = get_item_rescheduled_update_reason($task['order_no'],$task['order_line_id']);
	
	$tasks[$i]['delivery_date'] = sql2date($actual_delivery_date)." (Changes to ".sql2date($planned_date).")" ;
	
	$tasks[$i]['update_reason'] =$planned_update_reason;
	
	}
	else
	{
		$tasks[$i]['delivery_date']=sql2date($task['delivery_date']);
		
		$tasks[$i]['update_reason'] =$task['update_reason'];
	}
	
	$tasks[$i]['reference']=$task['reference'];
	$tasks[$i]['deliverydate']=$task['delivery_date'];
	$tasks[$i]['deliverylocation']=$task['deliverylocation'];
	
    $tasks[$i]['del_address']=strval($task['del_address']);
	$tasks[$i]['debtor_no']=$task["name"];	
	$tasks[$i]['description']=$task['description'];
	$tasks[$i]['TotQuantity']=$task['TotQuantity'];
	$tasks[$i]['days']=$task['days'];
	
	$tasks[$i]['order_no']=$task['order_no'];
	$tasks[$i]['order_line_id']=$task['order_line_id'];
	
	$tasks[$i]['planned_status']=$task['planned_status'];
	
	$tasks[$i]['salesman_name'] =$task['salesman_name'];
	$tasks[$i]['has_child']=$task['has_child'];
	$tasks[$i]['sale_table']=$task['sale_table'];
	$tasks[$i]['order_asc']=$task['order_asc'];

	$i++;
} 

?>
<html>
<head>

    <!-- Bootstrap Core CSS   -->
    <link href="<?php  echo $path_to_root;?>/fullcalendar/bootstrap.min.css" rel="stylesheet"> 
	
    <link href="<?php  echo $path_to_root;?>/themes/LTE/css/bootstrap.min.css" rel="stylesheet"> 
    <link href="<?php  echo $path_to_root;?>/themes/LTE/css/_all-skins.min.css" rel="stylesheet"> 
    <link href="<?php  echo $path_to_root;?>/themes/LTE/css/AdminLTE.css" rel="stylesheet"> 
    

	
	<!-- FullCalendar -->
	<link href='<?php  echo $path_to_root;?>/fullcalendar/fullcalendar.css' rel='stylesheet' /> 

    <!-- Custom CSS -->
    <style>
	#calendar {
		max-width: 900px;
		
	}
	.col-centered{
		float: none;
		margin: 0 auto;
		
	}
	
	th, td {
     padding: 10px;
   }
   
   div.modal-content {
  width: 1000px;
  margin: auto;
  border: 3px solid #73AD21;
}
	
    </style>
</head>
<body>

<center>
<table>
<tr>
  <th bgcolor="#e68a00"><a href="<?php echo $path_to_root;?>/reporting/reports_main.php?Class=0&REP_ID=1025">Upcoming Deliveries (Partial)</a></th>
  <th bgcolor="#4ddbff"><a href="<?php echo $path_to_root;?>/reporting/reports_main.php?Class=0&REP_ID=1025"> Upcoming Deliveries (Full)</a></th>
  <th bgcolor="#ffff00"><a href="<?php echo $path_to_root;?>/reporting/reports_main.php?Class=0&REP_ID=1026"> Delayed Deliveries</a></th>
  <th bgcolor="#ff6666"><a href="<?php echo $path_to_root;?>/reporting/reports_main.php?Class=0&REP_ID=1050"> Cancelled Order</a></th>
  <th bgcolor="#79ff4d"><a href="<?php echo $path_to_root;?>/reporting/reports_main.php?Class=0&REP_ID=1058"> Delivered Orders</a></th>
  
</tr>
</table> 
</center>
</body>

<div class="row">
            <div class="col-lg-12 text-center">
               <div id="calendar" class="col-centered" name="ram">
               </div>
            </div>
        </div>
		
<!-- Modal -->
<style>
	.hor{
	display: flex;
	align-items: left;
	}
</style>
		<div class="modal fade" id="ModalEdit" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		  <div class="modal-dialog" role="document">
			<div class="modal-content" id='modalContent'>
			</div>
			</div>
			</div>
				
				
</body>
</html>
<script src="<?php echo $path_to_root;?>/fullcalendar/js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="<?php  echo $path_to_root;?>/fullcalendar/js/bootstrap.min.js"></script> 
	
	<!-- FullCalendar -->
	<script src='<?php echo $path_to_root;?>/fullcalendar/js/moment.min.js'></script>
	<script src='<?php echo $path_to_root;?>/fullcalendar/js/fullcalendar.min.js'></script>
	
<script>
$(document).ready(function(){
		display_calendar()
	 });
	 
	 function display_calendar() {
		var colors= ['#ff6666','#ffff00','#e68a00','#79ff4d','#4ddbff'];
//var rand = colors[Math.floor(Math. () * back.length)];

		 $('#calendar').fullCalendar({
			header: {
				left: 'prev,next today',
				center: 'title',
				// right: 'month,basicWeek,basicDay'
			},
			defaultDate: new Date(),
			editable: false,
			eventLimit: true, // allow "more" link when too many events
			selectable: true,
			selectHelper: true,
			select: function(start, end) {
				// $('#calendar').fullCalendar('updateEvent', event);
				
				$('#ModalAdd #start').val(moment(start).format('YYYY-MM-DD HH:mm:ss'));
				$('#ModalAdd #end').val(moment(end).format('YYYY-MM-DD HH:mm:ss'));
				$('#ModalAdd').modal('show');
				
			},
			eventRender: function(task, element) {
				element.bind('click', function() {
					
					var xmlhttp = new XMLHttpRequest();
					xmlhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
					document.getElementById("modalContent").innerHTML = this.responseText;
					}
					}
					xmlhttp.open("GET", "getorderdetails.php?type="+task.sale_table+"&order_no="+task.order_no+"&delivery_date="+task.delivery_date, true);
					xmlhttp.send();
				/*	$('#ModalEdit p#reference').text(task.reference);
					$('#ModalEdit p#deliverydate').text(task.delivery_date);
					$('#ModalEdit p#deliverylocation').text(task.deliverylocation);
					$('#ModalEdit p#del_address').text(task.del_address);
					$('#ModalEdit p#debtor_no').text(task.debtor_no);
					$('#ModalEdit p#description').text(task.description);
					$('#ModalEdit p#TotQuantity').text(task.TotQuantity);
					$('#ModalEdit p#days').text(task.days);
					$('#ModalEdit p#update_reason').text(task.update_reason);
					$('#ModalEdit p#salesman_name').text(task.salesman_name); */
					$('#ModalEdit').modal('show');
				});
			},
			eventDrop: function(task, delta, revertFunc) {        // si changement de position

				edit(task);

			},
			eventResize: function(task,dayDelta,minuteDelta,revertFunc) { // si changement de longueur

				edit(task);

			},
				
  //  console.log(rand);
		 //$('.fc-event').css('background',rand);
			events: [
			<?php $i=0; ?>
				<?php foreach($tasks as $task): 
			  ?>
				{
				    <?php
					$color = 3;
					?>
					<?php
					
					if($task['sale_table']=='so')
					{
					if($task['days']<0 && $task['planned_status']==0)
                      $color = 1;  //yellow color
					/* else if($task['planned_status']==1)
                      $color = 1;  //Gray color  */
					else if($task['days']>=0 && $task['planned_status']==0)
					{
					  $order_status=get_so_order_status($task['order_no']);
				
                      if($order_status==0)					  
                      $color = 4; 
                      else 
                      $color=2;  
					}
					}
					
					if($task['sale_table']=='sd')
					{
					 /*	if($task['has_child']==1)
                        $color = 1;  //Gray color */
					    if($task['has_child']==0 && $task['days']>=0)
						{
												
						$reschudule_order_status= get_reschedule_order_status($task['order_no'],$task['delivery_date']);	
						if($reschudule_order_status==1)
						$color=2;
                        else					
                        $color = 4;
						}
					    else if($task['has_child']==0 && $task['days']<0)
                        $color = 1;
					} 
					if($task['sale_table']=='co')
					$color = 0; 
					
					if($task['sale_table']=='do')
					$color = 3; 
					?>
					
					title: '<?php echo $task['order_asc']." ".$task['reference']." -  ".$task['debtor_no']; ?>',
                    //url: 'http://google.com/',
                    start: '<?php echo $task['deliverydate']; ?>',
					reference : '<?php echo $task['reference'];?>',
					delivery_date : '<?php echo $task['delivery_date'];?>',
					debtor_no : '<?php echo $task['debtor_no'];?>',
					sale_table : '<?php echo $task['sale_table'];?>',
					order_no : '<?php echo $task['order_no'];?>',
					//color:colors[0] 
					 color:colors[<?php echo $color; ?>] 
					
					<?php $i++ ;?>
				
			
			},
			<?php endforeach; ?>
			]
			 
		})
	 };

</script>

