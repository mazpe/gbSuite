<?php
    session_start();

	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/server_url.php';
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/util/connection.php';
	
	$connection = new Connection();
	
	$query = "UPDATE info SET online = 0 WHERE uid = '".$_SESSION['uid']."'";
	
	$connection->exec_query($query);
	
	unset($_SESSION['uid']);
	 	 	
 	setcookie("cookname", null, time()+60*60*24*2, "/");
    setcookie(md5("cookpass"),null , time()+60*60*24*2, "/");
    
		
	header("Location: ".$YOUR_APP_SERVER_URL."/gbSuite/home.php");
?>