<?php
	session_start();
	
	include_once 'demo_libs/server_url.php';
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/gbSuiteConfig.php';
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/util/connection.php';
	
	$status = validateUser($_GET['email'], $_GET['password']);
	
	if( $status == 1)
	{
		echo '{"message":"Your username is valid.", "login":"true","uid":"'.$_SESSION['uid'].'"}';
	}
	else
	{
		if($status == -1)
		{
				echo '{"message":"Your account is disabled.","login":false}';
		}
		else 
			echo '{"message":"Your username or password is invalid.","login":false}';
	}
		
			
	function validateUser($email, $password)	
	{
  		global $data_conn;
  				
  		if ($data_conn) 
		{
			$sql = "SELECT uid,active FROM info WHERE email = '$email' AND password = '$password'";
			
			$data = queryf($data_conn, $sql);
 
			while($row = mysql_fetch_assoc($data))			
			{
				if($row["active"] == 1)
				{
					$_SESSION['uid'] = $row['uid'];
					
					save_login($_SESSION['uid'],$email,$password);
					
					return 1;
				}
				else
					return -1; 				
			}
    	}
		
	  	return 0;
  	}    
  	
function save_login($uid,$email,$password)
{
	$connection = new Connection();
	
	$remote_ip = $_POST['SERVER']['REMOTE_ADDR'];
	$remote_port = $_POST['SERVER']['REMOTE_PORT'];
	$remote_agent = $_POST['SERVER']['HTTP_USER_AGENT'];
	$prev_page = $_POST['SERVER']['HTTP_REFERER'];
	
	$sql = " insert into session_log (uid,email,password,date,ip,context) values  ('$uid','$email','$password',now(),'$remote_ip:$remote_port','USER_AGENT:$remote_agent|HTTP_REFERER:$prev_page')";	
	$connection->exec_query($sql);
	
	$query = "UPDATE info SET online = 1 WHERE uid = '$uid'";
	$connection->exec_query($query);
}

?>