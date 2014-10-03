<?php
    include_once $_SERVER['PHP_ROOT']."/gbSuite/apps/app_config/app_config.php";
	
	$application = new AppConfig();
	
	$application->setApplicationAssociatePermission($_GET['type'], $_GET['uid'], $_GET['app_id'], $_GET['uids']);
?>
