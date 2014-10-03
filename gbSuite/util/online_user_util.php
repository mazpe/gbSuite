<?php
/*
 * Created on 24/10/2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
include_once($_SERVER['PHP_ROOT']."/gbSuite/util/connection.php");

if($_GET['verify']==true)
{
	//setCurrentUserOnline(new Connection());
	
	$conn = new Connection();
	
	$sql="update info set last_time_activity=now() where uid='". $_POST['SESSION']['uid'] ."'";
	
	$conn->exec_query($sql);
	
	$online_user=countUserOnline($conn,$_POST['SESSION']['uid']);
	 
	echo '{"message":"'. $online_user .'"}';
}

/*Here is to check more detail info for the online users*/

if(isset($_GET['withDetail']))
{
	$conn = new Connection();
	 
	echo countUserOnlineDetail($conn, $_POST['SESSION']['uid']);
}


/**************************************************************/

function setCurrentUserOnline($conn){
	$sql="update info set last_time_activity=now() where uid='".$_SESSION['uid']."'";
	$conn->exec_query($sql);
}

function countUserOnline($conn,$current_uid=-1)
{
	if($current_uid==-1)
		$current_uid=$_SESSION['uid'];
	
	//If the user close the page and pass 10 min the system automatically will set the online status to offline...	
	$sql = "UPDATE info SET online = 0 WHERE online = 1 AND timediff(now(),last_time_activity) >= '00:10:00'";
	$conn->exec_query($sql);
	
	//$sql="select count(last_time_activity) as online from friend F inner join info I on I.uid=F.user2 
	//where F.user1=$current_uid and last_time_activity is not null and timediff(now(),last_time_activity)<='00:10:00'";
	
	$sql="select count(last_time_activity) as online from friend F inner join info I on I.uid=F.user2 
	where F.user1=$current_uid and online = 1 AND I.email NOT LIKE '%@gbsuite.com%'";
	
	$online_user=$conn->get_row($sql);
	
	return $online_user[0];
}

function countUserOnlineDetail($conn,$current_uid=-1)
{
	if($current_uid == -1)
		$current_uid = $_SESSION['uid'];
		
	//$sql = "select uid,title,name from friend F inner join info I on I.uid=F.user2 
	//			where F.user1='$current_uid' and last_time_activity is not null and timediff(now(),last_time_activity)<='00:10:00'";
	
	$sql = "select uid, title, name from friend F inner join info I on I.uid=F.user2 
				where F.user1 = '$current_uid' and online = 1 AND email NOT LIKE '%@gbsuite.com%'";
	
	$rs = $conn->exec_query($sql);
	
	$online_user = "";
	
	$users = "";
	
	while($row = mysql_fetch_assoc($rs))
	{
		$online_user .= ($online_user != "" ? "," : "").$row['uid'].',"'.$row['title'].'"';
		$users .= ($users != "" ? "," : "").'"'.$row['name'].'"';
	}

	return '{"message":['.$online_user.'], "names":['.$users.']}';
}
	
 
function setCurrentUserOffline()
{
	//$sql="update info set last_time_activity=null where uid='".$_SESSION['uid']."'";
	$sql="update info set online = 0 where uid='".$_SESSION['uid']."'";
	
	$conn=new Connection();
	$conn->exec_query($sql);
}

function save_login($uid,$email,$password)
{
	$connection = new Connection();
	
	$remote_ip = $_SERVER['REMOTE_ADDR'];
	$remote_port = $_SERVER['REMOTE_PORT'];
	$remote_agent = $_SERVER['HTTP_USER_AGENT'];
	$prev_page = $_SERVER['HTTP_REFERER'];
	
	$sql = " insert into session_log (uid,email,password,date,ip,context) values  ('$uid','$email','$password',now(),'$remote_ip:$remote_port','USER_AGENT:$remote_agent|HTTP_REFERER:$prev_page')";	
	$connection->exec_query($sql);
	
}

?>
