<?php

session_start();

/*Check if the remember me it has been set*/

if( isset($_COOKIE['cookname'])  && isset( $_COOKIE[md5('cookpass')]  ) )
{
	include_once($_SERVER['PHP_ROOT']."/gbSuite/util/connection.php");
	$con =new Connection();
	$sql_query="select uid,count(*) from info where active=1 and " .
			"email='".$_COOKIE['cookname']. "' and md5(password)='".$_COOKIE[md5('cookpass')]."' group by uid";
	
	$resultado=$con->get_row($sql_query);
	
	if($resultado[1]==1){
		$_SESSION['uid']=$resultado[0];
		header("location:/gbSuite/home.php"); 
	}
} 


$_GET['fb_app_name'] = "gbSuite";

$_GET['fb_user_id'] = "12408077";

$_GET['fb_url_suffix'] = "login.html.php";

include_once($_SERVER['PHP_ROOT']."/canvas.php");   	



?>
    