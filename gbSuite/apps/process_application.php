<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/util/connection.php");
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/user.php");
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/server_url.php';
	
	$applicationName = "";
	$applicationsDirectory = "apps";
	
	//print_r($_GET);
	
	if(isset($_GET['app']) && $_GET['app']  != "")
		$applicationName = $_GET['app'];
	
	if($applicationName == "")
		$applicationName = "profile";
		
	$connection = new Connection();
	
	//$sql = "SELECT link_id FROM link WHERE name = '". $applicationName ."'";
	
	//The menu id
	//$link_id = $connection->get_value($sql);
			
	/**Read the information of the current user**/
	//$query = "SELECT * FROM info WHERE uid = '".$_POST['me_uid']."' ";
	$query = "SELECT * FROM info WHERE uid = '".$_GET['uid']."' ";
	
	$row = $connection->get_row($query);
	
	$user = new User($row);
	
	$user->setConnection($connection);

	$query = "SELECT * FROM apps WHERE name = '".$applicationName."'";
	
	$row = $connection->get_row($query);
	 	
	include_once($_SERVER['PHP_ROOT']."/gbSuite/".$applicationsDirectory."/".$row['file_name']."/".$row['file_name'].".php");
		
	$applicationClass = $row['class_name'];
	$function = $_GET['action'];
	$application = new $applicationClass();	
	$application->setAttributes($row);
	
	$application->setConnection($connection);
	
	$application->setUser($user);
	
	$params = array_merge($_POST, $_GET);
	
	if(isset($function) && $row['name'] == $_GET['app'])
		call_user_func(array(&$application, $function), $params);
	//else
		//echo '{"message":"Cannot process the function."}';
	
	if(!isset($_GET['redirect']) || $_GET['redirect'] == 'true')
	{
		//When an application is saving it's settings
		if($_GET['action'] == 'save_settings')		
			header("Location:/gbSuite/home.php?app=applications&action=edit_application_settings&application=".$_GET['app']);
	}
	else
		if(isset($_GET['redirect']))
		{
			if(isset($_GET['method']))
				$action = "&action=".$_GET['method'];
			
			if($_GET['app'] == 'sales_log' && $_GET['action'] == 'save')
			{
				if($_GET['redirect'] == 'sales_log')
					header("Location:/gbSuite/home.php?app=sales_log&option=".$_GET['option']."&from37=".$_GET['from37']."&to37=".$_GET['to37']."&order=".$_GET['order']."&id=".$_GET['id']."&nu=".$_GET['nu']);
				else
					if($_GET['redirect'] == 'ssales_log')
						header("Location:/gbSuite/home.php?app=ssales_log&option=".$_GET['option']."&from46=".$_GET['from37']."&to46=".$_GET['to37']."&order=".$_GET['order']."&id=".$_GET['id']);				
			}	
			else
				if($_GET['app'] == 'ssales_log' && $_GET['action'] == 'set_rdr')
					header("Location:/gbSuite/home.php?app=ssales_log&option=".$_GET['option']."&from46=".$_GET['from46']."&to46=".$_GET['to46']."&order=".$_GET['order']."&id=".$_GET['id']);
				else
					header("Location:/gbSuite/home.php?app=".$_GET['redirect'].$action);
		}	
		else
			header("Location:/gbSuite/home.php?app=applications");
?>
