<?php
    include_once $_SERVER['PHP_ROOT']."/gbSuite/apps/app_config/app_config.php";
	
	$application = new AppConfig();
	
	$application->setProfileApplicationOrder($_GET['uid'], $_GET['app_id'], $_GET['position']);
?>
