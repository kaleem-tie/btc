<?php 
$page_security = 'SA_SALES_DELIVERY_CALENDAR';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

$help_context = "Sales Delivery Calendar (Item Wise)";
page(_($help_context));
simple_page_mode(true);

function get_all_sales_delivery_calendar_info()
{
	$today = Today(); 
    $today = date2sql($today);
   $sql = "SELECT 
			sorder.order_no,
			sorder.reference,
			sorder.delivery_date,
			sorder.debtor_no,
			debtor.name,
			sorder.ord_date,
			line.stk_code,
			line.description,
			line.quantity AS TotQuantity,
			DATEDIFF(sorder.delivery_date,'$today') AS days,
			line.id as order_line_id,
            line.planned_status,
			'' AS update_reason,
			location.location_name as deliverylocation,
		    sorder.deliver_to as del_address,
		    salesman.salesman_name as salesman_name,
            0 AS has_child,
            'so' as sale_table,
            sorder.delivery_time as delivery_time			
		FROM ".TB_PREF."sales_orders as sorder,
		    ".TB_PREF."sales_order_details as line, 
			".TB_PREF."debtors_master as debtor,
			".TB_PREF."locations as location,
			".TB_PREF."salesman as salesman
			WHERE sorder.order_no = line.order_no
			AND sorder.trans_type = line.trans_type
			AND sorder.trans_type = 30
			AND sorder.debtor_no = debtor.debtor_no
			AND line.quantity-line.qty_sent>0
			AND sorder.from_stk_loc=location.loc_code 
			AND sorder.reference!='auto'
			AND salesman.salesman_code = sorder.sales_person_id
			UNION
		SELECT soplan.order_no,
         sorder.reference,
		 soplan.planned_date AS delivery_date,
		 sorder.debtor_no,
		 debtor.name,
		 sorder.ord_date,
		 line.stk_code,
		 line.description,
		 line.quantity AS TotQuantity,
		 DATEDIFF(soplan.planned_date,'$today') AS days,
		 soplan.order_line_id as order_line_id,
		 1 AS planned_status,
		 soplan.update_reason,
		 location.location_name as deliverylocation,
		 sorder.deliver_to as del_address,
		 salesman.salesman_name as salesman_name ,
        soplan.has_child,
        'sd' as sale_table,
        soplan.planned_delivery_time as delivery_time 		
    	FROM ".TB_PREF."sales_delivery_plan as soplan,
		".TB_PREF."sales_orders as sorder,
		".TB_PREF."sales_order_details as line,
		".TB_PREF."debtors_master as debtor,
		".TB_PREF."locations as location,
		".TB_PREF."salesman as salesman
    	WHERE soplan.order_no = sorder.order_no
		AND sorder.order_no = line.order_no
		AND sorder.trans_type = line.trans_type
		AND soplan.order_line_id = line.id
    	AND sorder.debtor_no = debtor.debtor_no
		AND line.quantity-line.qty_sent>0
    	AND sorder.from_stk_loc=location.loc_code 
		AND salesman.salesman_code = sorder.sales_person_id
		ORDER BY days DESC	";
		
        return db_query($sql);
}


function get_item_rescheduled_date($order_no,$order_line_id)
{
	$sql = "SELECT planned_date FROM ".TB_PREF."sales_delivery_plan 
			WHERE order_no = ".db_escape($order_no)."
			AND order_line_id = ".db_escape($order_line_id)." and has_child=0";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
	
}	

function get_item_rescheduled_update_reason($order_no,$order_line_id)
{
	$sql = "SELECT update_reason FROM ".TB_PREF."sales_delivery_plan 
			WHERE order_no = ".db_escape($order_no)."
			AND order_line_id = ".db_escape($order_line_id)." and has_child=0";
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

function get_item_actual_delivery_time($order_no)
{
	$sql = "SELECT delivery_time FROM ".TB_PREF."sales_orders 
			WHERE order_no = ".db_escape($order_no)."";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
	
}


function get_item_rescheduled_time($order_no,$order_line_id)
{
	$sql = "SELECT planned_delivery_time FROM ".TB_PREF."sales_delivery_plan 
			WHERE order_no = ".db_escape($order_no)."
			AND order_line_id = ".db_escape($order_line_id)." and has_child=0";
	$res = db_query($sql);
	$result = db_fetch_row($res);
	return $result['0'];
	
}

//-----------------------------------------

$result=get_all_sales_delivery_calendar_info();
$i=0;
$tasks=array();
global $delivery_times;
while($task=db_fetch($result))
{
	
	if($task['planned_status']==1)
	{
	$planned_date = get_item_rescheduled_date($task['order_no'],$task['order_line_id']);
	$actual_delivery_date = get_item_actual_delivery_date($task['order_no']);
	
	$actual_delivery_time = get_item_actual_delivery_time($task['order_no']);
	$planned_delivery_time = get_item_rescheduled_time($task['order_no'],$task['order_line_id']);
	
	
	$planned_update_reason = get_item_rescheduled_update_reason($task['order_no'],$task['order_line_id']);
	
	$tasks[$i]['delivery_date'] = sql2date($actual_delivery_date)." (Changes to ".sql2date($planned_date).")" ;
	
	$tasks[$i]['update_reason'] =$planned_update_reason;
	
	$tasks[$i]['delivery_time'] = $delivery_times[$actual_delivery_time]." (Changes to ".$delivery_times[$planned_delivery_time].")" ;
	
	}
	else
	{
		$tasks[$i]['delivery_date']=sql2date($task['delivery_date']);
		
		$tasks[$i]['update_reason'] =$task['update_reason'];
		
		$tasks[$i]['delivery_time'] =$delivery_times[$task['delivery_time']];
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
	
	$tasks[$i]['deliverytime']=$delivery_times[$task['delivery_time']];

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
    <link href="<?php  echo $path_to_root;?>/themes/LTE/default.css" rel="stylesheet"> 

	
	<!-- FullCalendar -->
	<link href='<?php  echo $path_to_root;?>/fullcalendar/fullcalendar.css' rel='stylesheet' /> 

    <!-- Custom CSS -->
    <style>
	#calendar {
		max-width: 800px;
		
	}
	.col-centered{
		float: none;
		margin: 0 auto;
		
	}
	
	th, td {
     padding: 10px;
   }
	
    </style>
</head>
<body>

<center>
<table>
<tr>
  <th bgcolor="#79ff4d"> Upcoming Deliveries </th>
  <th bgcolor="#66d9ff"> Upcoming (Rescheduled) Deliveries</th>
  <th bgcolor="#cccccc"> Rescheduled Deliveries</th>
  <th bgcolor="#ff6666"> Delayed Deliveries  </th>
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
			<div class="modal-content">
			<form class="form-horizontal" method="POST" action="editEventTitle.php">
			  <div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel">Delivery Info</h4>
			  </div>
			  <div class="modal-body" style="margin-left:18%;" name="task_info">
				<div class="hor"><b>Reference:</b>&nbsp &nbsp <p name="reference" id="reference"></p></div>
				<br>
				<div class="hor"><b>Delivery Date :</b>&nbsp &nbsp <p name="deliverydate" id="deliverydate"></p></div>
				<br>
				<div class="hor"><b>Delivery Time :</b>&nbsp &nbsp <p name="deliverytime" id="deliverytime"></p></div>
				<br>
				<div class="hor"><b>Dispatch Location :</b>&nbsp &nbsp <p name="deliverylocation" id="deliverylocation"></p></div>
				<br>
				<div class="hor"><b>Customer:</b>&nbsp &nbsp <p name="debtor_no" id="debtor_no"></p></div>
				<br>
				<div class="hor"><b>Item Name :</b>&nbsp &nbsp <p name="description" id="description"></p></div>
                <br>
				<div class="hor"><b>Quantity :</b>&nbsp &nbsp <p name="TotQuantity" id="TotQuantity"></p></div>
				<br>
				<div class="hor"><b>Sales Person :</b>&nbsp &nbsp <p name="salesman_name" id="salesman_name"></p></div>
				<br>
				<div class="hor"><b>Delivery To :</b>&nbsp &nbsp <p name="del_address" id="del_address"></p></div>
				<br>
				<div class="hor"><b>Rescheduled Reason :</b>&nbsp &nbsp <p name="update_reason" id="update_reason"></p></div>
				<br>
				
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
		var colors= ['#ff6666','#cccccc','#66d9ff','#79ff4d'];
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
					$('#ModalEdit p#reference').text(task.reference);
					$('#ModalEdit p#deliverydate').text(task.delivery_date);
					$('#ModalEdit p#deliverytime').text(task.delivery_time);
					$('#ModalEdit p#deliverylocation').text(task.deliverylocation);
					$('#ModalEdit p#del_address').text(task.del_address);
					$('#ModalEdit p#debtor_no').text(task.debtor_no);
					$('#ModalEdit p#description').text(task.description);
					$('#ModalEdit p#TotQuantity').text(task.TotQuantity);
					$('#ModalEdit p#days').text(task.days);
					$('#ModalEdit p#update_reason').text(task.update_reason);
					$('#ModalEdit p#salesman_name').text(task.salesman_name);
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
                      $color = 0;  //Red color
					else if($task['planned_status']==1)
                      $color = 1;  //Gray color 
					else if($task['days']>=0 && $task['planned_status']==0)
                      $color = 3;  //green color   
					}
					if($task['sale_table']=='sd')
					{
						if($task['has_child']==1)
                        $color = 1;  //Gray color
					    else if($task['has_child']==0 && $task['days']>=0)
                        $color = 2;  //Sky blue color
					    else if($task['has_child']==0 && $task['days']<0)
                        $color = 0;  //Red color
					}                 					
					?>
					
					title: '<?php echo $task['reference']." -  ".$task['debtor_no']; ?>',
                    //url: 'http://google.com/',
                    start: '<?php echo $task['deliverydate']; ?>',
					reference : '<?php echo $task['reference'];?>',
					delivery_date : '<?php echo $task['delivery_date'];?>',
					delivery_time : '<?php echo $task['delivery_time'];?>',
					deliverylocation : '<?php echo $task['deliverylocation'];?>',
					update_reason : '<?php echo $task['update_reason'];?>',
					debtor_no : '<?php echo $task['debtor_no'];?>',
					description : '<?php echo $task['description'];?>',
					TotQuantity : '<?php echo $task['TotQuantity']; ?>',
				    salesman_name : '<?php echo $task['salesman_name'];?>',
				    del_address : '<?php echo $task['del_address'];?>',
					
					//color:colors[0] 
					 color:colors[<?php echo $color; ?>] 
					
					<?php $i++ ;?>
				
			
			},
			<?php endforeach; ?>
			]
			 
		})
	 };

</script>

