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
function InsertComplaint($table_name, $data){
    $sql0 = "INSERT INTO ".TB_PREF.$table_name."(";
    $sql1 = " VALUES (";
    foreach($data as $key=>$value){
        $sql0 .= "`".$key."`,";
		if(is_array($value)) { 
			if($value[1] == 'date')				
				$sql1 .=  db_escape(date2sql($value[0])).",";
			if($value[1] == 'float')
				$sql1 .= $value.",";
		}else 
			$sql1 .= db_escape($value).",";
    }
    $sql0 = substr($sql0, 0, -1).")";
    $sql1 = substr($sql1, 0, -1).")";
	//display_notification($sql0.$sql1);
    db_query($sql0.$sql1, "Could not insert data to table {$table_name}");
	return  db_insert_id();
}

function UpdateComplaint($table_name, $primary_key ,$data ){
    $sql0 = "UPDATE ".TB_PREF.$table_name." SET ";
    foreach($data as $key=>$value){
		if(is_array($value)) { 
			if($value[1] == 'date')				
				$sql0 .= "`".$key."` = ". db_escape(date2sql($value[0])).",";
			if($value[1] == 'float')
				$sql0 .= "`".$key."` = ". $value.",";
		}else 
			$sql0 .= "`".$key."` = ".db_escape($value).",";
    }
    $sql0 = substr($sql0, 0, -1);
    $sql0 .= " where ".$primary_key[0]." = '".$primary_key[1]."'";
	return  db_query($sql0, "Could not update data on table {$table_name}");	 
}

function DeleteComplaint($table_name, $conditions){
    $sql0 = "DELETE FROM ".TB_PREF.$table_name." WHERE 1=1";
    foreach ($conditions as $key=>$value) {
        $sql0 .= " AND ".$key."=".$value;
    }
    $result = db_query($sql0, "Could not delete data from {$table_name}");
    return $result;
}

if(!function_exists('GetComplaintDataJoin1')) {

	function GetComplaintDataJoin1($main_table, $joins, $columns=array(), $conditions=null, $order_by = null,$group_by = null, $single=false, $sql=false){
		$sql0 = "SELECT ";
		if(count($columns)>0){
			foreach ($columns as $value) {
				$sql0 .= $value.",";
			}
			$sql0 = substr($sql0, 0, -1);
		}else{
			$sql0 .= " *";
		}
		$sql0 .= " FROM ".TB_PREF."{$main_table} ";
		foreach ($joins as $value) {
			if(isset($value['join'])){
				$sql0 .= " {$value['join']} JOIN ".TB_PREF.$value['table_name'].' ON '.$value['conditions'];
			}else{
				$sql0 .= " INNER JOIN ".TB_PREF.$value['table_name'].' ON '.$value['conditions'];
			}
		}
		$sql0 .= " WHERE 1=1 ";
		if($conditions != null) {
            foreach($conditions as $key=>$value){

                if(is_array($value)) { 
                    if(isset($value[2]))
                        $operator = $value[2];
                    else
                        $operator = '=';

                    if($value[1] == 'date')             
                        $sql0 .= " AND ".$key." ".$operator." ". db_escape(date2sql($value[0]));
                    elseif($value[1] == 'float')
                        $sql0 .= " AND ".$key." ".$operator." ". $value;
                    else
                        $sql0 .= " AND ".$key." ".$operator." ". db_escape($value[0]);
                }else
                    $sql0 .= " AND {$key} = ".db_escape($value);
            }
        }

        if($group_by != null) {
                $sql0 .=" GROUP BY ";
                foreach($group_by as $value){
                    $sql0 .= " ${value}";
                }
        }
		//echo $sql0;
		if($order_by != null) {
			$sql0 .=" ORDER BY ";
			foreach($order_by as $key=>$value){
				$sql0 .= " {$key} ${value}";
			}
		}
		if($single)
			$sql0 .= ' LIMIT 1'; 
			
	   // display_error($sql0);
			
		if($sql)
			return $sql0;
		$result = db_query($sql0, "Could not get data!");
		$data=array();
		if(db_num_rows($result) > 0 ) {
			if($single){
				$data = db_fetch($result);
			} else { 
				$data = array();	
				while($row = db_fetch($result)) {
					$data[] = $row;
				}
			}
		}	
		return $data;
	}
}

function GetComplaintSingleValue($tablename, $column_single, $conditions=null){
	$sql0 = "SELECT ".$column_single." FROM ".TB_PREF.$tablename." WHERE 1=1";
    foreach($conditions as $key=>$value){
        $sql0 .= " AND {$key} = '${value}'";
    }
    $result = db_query($sql0, "could not get sales type");

    $row = db_fetch_row($result);
    return $row[0];
}

function GetRow($table_name, $conditions = null){
    $sql0 = "SELECT * FROM ".TB_PREF.$table_name." WHERE 1=1";
    if($conditions != null) {
		foreach($conditions as $key=>$value){
			$sql0 .= " AND {$key} = '${value}'";
		}
    }
	$sql0 .= ' LIMIT 1'; 
    $result = db_query($sql0, "Could not get data from {$table_name}");
	$data = db_fetch($result);
    
    return $data;
}



if(!function_exists('db_Has_Complaint_Data_on_Table1')) {
	function db_Has_Complaint_Data_on_Table1($table_name, $primary_key =false){

		$sql = "SELECT COUNT(*) FROM ".TB_PREF.$table_name." WHERE 1=1";
		if($primary_key){
			foreach($primary_key as $key=>$value){
				if(is_array($value)) { 
					if($value[1] == 'date')             
						$sql .= " AND ".$key." = ". db_escape(date2sql($value[0])).",";
					if($value[1] == 'float')
						$sql .= " AND ".$key." = ". $value.",";
				}else{
					if(is_numeric($value)){
						$sql .=" AND ". $key." = ".$value;
					}else
					   $sql .= " AND ".$key." = ".db_escape($value);
				}
			}
		}
		//display_error($sql);
		return  check_empty_result($sql);
	}
}

if(!function_exists('ComplaintUpdate1')) {
	function ComplaintUpdate1($table_name, $primary_key, $data ){

		if(db_Has_Complaint_Data_on_Table1($table_name, $primary_key)){
			$sql0 = "UPDATE ".TB_PREF.$table_name." SET ";
			foreach($data as $key=>$value){
				if(is_array($value)) { 
					if($value[1] == 'date')             
						$sql0 .= "`".$key."` = ". db_escape(date2sql($value[0])).",";
					if($value[1] == 'float')
						$sql0 .= "`".$key."` = ". $value.",";
					if($value[1] == 'noesc')
						$sql0 .= "`".$key."` = '". $value[0]."',";
				}else {
					$sql0 .= "`".$key."` = ".db_escape($value).",";
				}
			}
			$sql0 = substr($sql0, 0, -1);
			$sql0 .= " where 1=1";
			foreach($primary_key as $key=>$value){
				if(is_array($value)) { 
					if(isset($value[2]))
						$operator = $value[2];
					else
						$operator = '=';

					if($value[1] == 'date')             
						$sql0 .= " AND ".$key." ".$operator." ". db_escape(date2sql($value[0]));
					elseif($value[1] == 'float')
						$sql0 .= " AND ".$key." ".$operator." ". $value;
					else
						$sql0 .= " AND ".$key." ".$operator." ". db_escape($value[0]);
				}else{
					if(is_numeric($value)){
						$sql0 .=" AND ". $key." = ".$value;
					}else
					   $sql0 .= " AND ".$key." = ".db_escape($value);
				}
			}
			
		   return  db_query($sql0, "Could not update data on table {$table_name}");
		}else{
			foreach($primary_key as $key => $value){
				if($key != 'id')
					$data[$key] = $value;
			}
			return InsertComplaint($table_name, $data);
		}     
	}
}

function get_complaint_customer_name($customer_id)
{
	$sql = "SELECT name FROM ".TB_PREF."debtors_master WHERE debtor_no=".$customer_id;
	$result = db_query($sql, "order Retreival");
	$row= db_fetch_row($result);
	return $row[0];
	
}