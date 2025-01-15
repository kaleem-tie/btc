<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root="../..";
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
$js = "";
page(_($help_context = "Purchase Quote Comparison"), false, false, "", $js);
	
	
	start_form();
	$dec = user_price_dec();
		?>
	<center>
	<label>Enter Code/RFQ Reference:</label>
	<input type="text" name="comparison_code" id="comparison_code" value="<?php echo $_POST['comparison_code'];?>" required>
	<input type="submit" name="search" value="Compare">
	</center>
	<?php
	end_form();

if($_POST['comparison_code'])
{
	if(empty($_POST['comparison_code']) || $_POST['comparison_code']=='')
	{
		display_error("Invalid Code!");
	}
	else
	{
		$comparison_code=$_POST['comparison_code'];
		
		$sql="select count(*) as count from ".TB_PREF."purch_orders where comparison_code='$comparison_code' and trans_type=52";
		$result = db_query($sql, "could not get result");
	    $row = db_fetch_row($result);
		
		if($row[0]>0)
		{
		  $sql="SELECT distinct supplier_id FROM ".TB_PREF."purch_orders where comparison_code='$comparison_code' and trans_type=52;";
		  $result = db_query($sql, "could not get result");
           		  
		  while($row=db_fetch($result))
          {
			$supplier_ids[]=$row['supplier_id'];
		  }
			?>
			<br><br>
			<center>
		<table class="tablestyle2" width="80%">
		<tr>
		<th class="tableheader">S.No.</th>
		<th class="tableheader">Item Description</th>
		<?php
		 for($i=0;$i<count($supplier_ids);$i++)
		 {
			 ?>
			 <th class="tableheader"><?php echo display_supp_name($supplier_ids[$i]);?></th>
			 <!-- <th class="tableheader">No.of Days</th> -->
			 <?php
		 }
		?>
		</tr>
		<?php
		$sql="SELECT distinct pod.item_code,pod.description FROM ".TB_PREF."purch_orders po,".TB_PREF."purch_order_details pod WHERE po.order_no=pod.order_no and po.trans_type=pod.trans_type and po.trans_type=52 and po.comparison_code='$comparison_code'";
		$result = db_query($sql, "could not get result");
           		  
		  while($row=db_fetch($result))
          {
			$items[]=$row['item_code'];
			$items_name[]=$row['description'];
		  }
		  
		 
		  for($i=0;$i<count($items);$i++)
		  {
			  ?>
			  </tr>
			  <td align="center"><?php echo $i+1;?></td>
			  <td><?php echo $items_name[$i];?></td>
			  <?php
			  for($j=0;$j<count($supplier_ids);$j++)
		      {
				  $supplier_quote_amount[$supplier_ids[$j]][]=get_quote_price($comparison_code,$supplier_ids[$j],$items[$i]);
			 ?>
			 <td align="right"><?php echo number_format(get_quote_price($comparison_code,$supplier_ids[$j],$items[$i]),$dec);?></td>
			<!-- <td align="right"><?php //echo get_quote_delivery_days($comparison_code,$supplier_ids[$j],$items[$i]);?></td> -->

			 
			 <?php
		    }
			?></tr><?php
		  }
		
		?>
	    <tr>
		<td></td>
		<td style="text-align:right">Total: </td>
		  <?php
		   for($l=0;$l<count($supplier_ids);$l++)
		      {
				  $total=0;
				  for($m=0;$m<count($supplier_quote_amount[$supplier_ids[$l]]);$m++)
				  {
					  if(is_numeric($supplier_quote_amount[$supplier_ids[$l]][$m]))
					  {
						  $total+=$supplier_quote_amount[$supplier_ids[$l]][$m];
					  }						  
				  }
				  ?>
				  <td style="text-align:right;color:green;">
				  <?php 
				  $dec = user_price_dec();
				  echo number_format($total,$dec);
				  ?></td>

				  <?php
			  }
		  ?>
		</tr>
		</table>
		<br><br>
		
		<a href="compare_quote_report.php?comparison_code=<?php echo $comparison_code; ?>" target="_blank">
<input type="button" value="View" style="background-color:#9ec4c2;"> 
</center>
		<?php
		}
		else
		{
			display_error("Invalid Code!");
		}
		
		
		
	}
}	

function display_supp_name($id)
{
	 $sql="select supp_ref from ".TB_PREF."suppliers where supplier_id='$id'";
	 $result = db_query($sql, "could not get result");
	 $row = db_fetch_row($result);
     return $row[0];		
}

function get_quote_price($code,$sup_id,$item_code)
{
	 $sql="SELECT min(pod.unit_price*(1-(discount_percent/100))) FROM ".TB_PREF."purch_orders po, ".TB_PREF."purch_order_details pod where comparison_code='$code' and po.trans_type=52 and po.order_no=pod.order_no and supplier_id=$sup_id and pod.item_code='$item_code' and po.trans_type=pod.trans_type";
	 
	 $result = db_query($sql, "could not get result");
	 $row = db_fetch_row($result);
    if(isset($row[0]))
	{
		return $row[0];
	}
	else
	{
		return "-";
	}

}
function get_quote_delivery_days($code,$sup_id,$item_code)
{
	 $sql="SELECT pod.delivery_days FROM ".TB_PREF."purch_orders po, ".TB_PREF."purch_order_details pod where comparison_code='$code' and po.trans_type=37 and po.order_no=pod.order_no and supplier_id=$sup_id and pod.item_code='$item_code'";
	 $result = db_query($sql, "could not get result");
	 $row = db_fetch_row($result);
    if(isset($row[0]))
	{
		return $row[0];
	}
	else
	{
		return "-";
	}

}		
?>
