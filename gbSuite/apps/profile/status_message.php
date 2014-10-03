<?php
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/apps/profile/profile.php';
	
	$profile = new Profile($_GET['uid']);
	
	$profile->setStatusMessage($_GET['status_message']);
?>
