<?php
date_default_timezone_set('Asia/Kolkata');
$path_to_root = '../../..';
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/sysnames.inc");

if($_GET['type']=='load_chat') {
    if(!empty($_POST['user_id'])) {
        load_chat($_POST['user_id']);
        // print_r($_POST);
    }
}
function load_chat($chat_id) {
    $my_id = $_SESSION['wa_current_user']->user;
    $_SESSION['current_chat_id'] = _($help_context = $chat_id);
    $sql ="SELECT * from ".TB_PREF."chats where (from_id=$my_id and to_id=$chat_id) or (from_id=$chat_id and to_id=$my_id)";
    $sst = db_query($sql);
    $ssl = db_query("UPDATE ".TB_PREF."chats set status=1 where (from_id=$my_id and to_id=$chat_id) or (from_id=$chat_id and to_id=$my_id)");
    $final = '';
    while($row = db_fetch_assoc($sst)){
        $figure = '';
        $chat_name = '';
        $chat_time = '';
        if($row['main_id'] !== $chat_id){$figure = "right";}
        if($row['main_id'] !== $chat_id){$chat_name = "pull-right";}else {$chat_name = "pull-left";}
        if($row['main_id'] == $chat_id){$chat_time = "pull-right";}else {$chat_time = "pull-left";}
        $dt = substr($row['date_time'], 0, -9);$exp = explode('-', $dt);
        $tt = substr($row['date_time'], 11, 25);$exptt = explode(':', $tt);
        $ampm = '';if($exptt[0]<12){$ampm = 'am';}else{$ampm = 'pm';}
        $final .= '
            <div class="direct-chat-msg '. $figure .'">
                <div class="direct-chat-info clearfix">
                    <span class="direct-chat-name '.$chat_name.'">'. get_username($row['main_id']) .'</span>
                    <span class="direct-chat-timestamp '.$chat_time.'">'.$exp[2].' '.get_month($exp[1]).' '.get_hour($exptt[0]).':'.$exptt[1].' '.$ampm.'</span>
                </div>
                <img class="direct-chat-img" src="https://adminlte.io/themes/AdminLTE/dist/img/user1-128x128.jpg" alt="message user image">
                <div class="direct-chat-text">
                    '.$row['message'].'
                </div>
            </div>
        ';

    }
    echo $final;
}
if($_GET['type'] == 'get_username'){
    echo get_username($_POST['user_id']);
    // echo "kaleem";
}
function get_username($user_id) {
    $sql ="SELECT user_id from ".TB_PREF."users where id=$user_id";
    $sst = db_query($sql);
    $rr = db_fetch_row($sst);
    return $rr[0];
}
if($_GET['type'] == 'new_messages'){
    echo get_newmessage();
}
function get_newmessage() {
    $my_id = $_SESSION['wa_current_user']->user;$now = date('Y-m-d h:i:s');
    $sql ="SELECT * from ".TB_PREF."chats where to_id=$my_id and status=0 order by chat_id desc";
    $sst = db_query($sql);
    $data = db_fetch($sst);
    $date = $data['date_time'];

    $datetime1 = new DateTime($now);
    $datetime2 = new DateTime($date);
    $diff = $datetime1->diff($datetime2);
    // return print_r($diff);
    $rr = array($diff->i, $diff->s);
    return json_encode($rr);

    // return $diff;
}
if($_GET['type'] == 'delete_task'){
    echo delete_task($_POST['task_id']);
}
function delete_task($task_id) {
    $sql ="DELETE FROM ".TB_PREF."tasks where task_id=$task_id";
    $sst = db_query($sql);
    return db_num_rows($sst);
}
function get_month($month) {
    if($month == 01){
        return "Jan";
    }elseif($month == 02){
        return "Feb";
    }elseif($month == 03){
        return "Mar";
    }elseif($month == 04){
        return "Apr";
    }elseif($month == 05){
        return "May";
    }elseif($month == 06){
        return "Jun";
    }elseif($month == 07){
        return "Jul";
    }elseif($month == 08){
        return "Aug";
    }elseif($month == 09){
        return "Sep";
    }elseif($month == 10){
        return "Oct";
    }elseif($month == 11){
        return "Nov";
    }elseif($month == 12){
        return "Dec";
    }else {
        return "";
    }
}
function get_hour($hour) {
    if($hour == 13){
        return 01;
    }elseif($hour == 14){
        return 02;
    }elseif($hour == 15){
        return 03;
    }elseif($hour == 16){
        return 04;
    }elseif($hour == 17){
        return 05;
    }elseif($hour == 18){
        return 06;
    }elseif($hour == 19){
        return 07;
    }elseif($hour == 20){
        return 08;
    }elseif($hour == 21){
        return 09;
    }elseif($hour == 22){
        return 10;
    }elseif($hour == 23){
        return 11;
    }elseif($hour == 24){
        return 12;
    }else {
        return $hour;
    }
}
if($_GET['type']=='insert_chat') {
    $attachment = '';
    if(!empty($_POST['message'])) {
        if(!empty($_FILES['file']['name'])) {
            $attachment = $_FILES['file']['name'];
            $target_dir = "../attachment/";
            $target_file = $target_dir . basename($_FILES["file"]["name"]);
            move_uploaded_file($_FILES['file']['tmp_name'] , $target_file);
        }
        insert_chat($_POST['message'], $attachment);
    }
    
        // echo $attachment;
        // print_r($_POST);
}
function insert_chat($message, $attachment) {
    $my_id = $_SESSION['wa_current_user']->user;
    $chat_id = $_SESSION['current_chat_id'];
    // $message = $message;
    $sql = "INSERT INTO ".TB_PREF."chats(`main_id`, `from_id`, `to_id`, `message`, `attachment`, `status`) VALUES ('$my_id','$my_id','$chat_id','$message','$attachment','0')";
    $sst = db_query($sql);
    // return display_error($sql);
}
if($_GET['type']=='refresh_chat') {
    refresh_chat();
}
function refresh_chat() {
    $my_id = $_SESSION['wa_current_user']->user;
    $chat_id = $_SESSION['current_chat_id'];
    $sql ="SELECT * from ".TB_PREF."chats where (from_id=$my_id and to_id=$chat_id) or (from_id=$chat_id and to_id=$my_id)";
    $sst = db_query($sql);
    $final = '';
    while($row = db_fetch_assoc($sst)){
        $figure = '';
        $chat_name = '';
        $chat_time = '';
        if($row['main_id'] !== $chat_id){$figure = "right";}
        if($row['main_id'] !== $chat_id){$chat_name = "pull-right";}else {$chat_name = "pull-left";}
        if($row['main_id'] == $chat_id){$chat_time = "pull-right";}else {$chat_time = "pull-left";}
        $dt = substr($row['date_time'], 0, -9);$exp = explode('-', $dt);
        $tt = substr($row['date_time'], 11, 25);$exptt = explode(':', $tt);
        $ampm = '';if($exptt[0]<12){$ampm = 'am';}else{$ampm = 'pm';}
        $att = '';if($row['attachment'] !== null && $row['attachment'] !== ''){$att = '<div class="chat-attachment"><a target="_blank" href="/ottdemo/themes/ott/attachment/'.$row['attachment'].'"><img src="https://i.imgur.com/S7FePyS.png" height="15" /></a></div>';}
        $final .= '
            <div class="direct-chat-msg '. $figure .'">
                <div class="direct-chat-info clearfix">
                    <span class="direct-chat-name '.$chat_name.'">'. get_username($row['main_id']) .'</span>
                    <span class="direct-chat-timestamp '.$chat_time.'">'.$exp[2].' '.get_month($exp[1]).' '.get_hour($exptt[0]).':'.$exptt[1].' '.$ampm.'</span>
                </div>
                <img class="direct-chat-img" src="https://adminlte.io/themes/AdminLTE/dist/img/user1-128x128.jpg" alt="message user image">
                <div class="direct-chat-text">
                    '.$row['message'].$att.'
                    
                </div>
            </div>
        ';

    }
    echo $final;
}


if(isset($_GET['done-tr'])) {
    $data_id = $_POST['data_id'];
    $sql = "UPDATE `".TB_PREF."tasks` set status=1 where task_id=$data_id";
    db_query($sql);
}
