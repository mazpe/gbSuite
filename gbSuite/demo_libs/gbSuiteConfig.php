<?php
/*
 * Created by: Developer1
 * Created on 22/07/2008
 * 
 *
 * Script to include files to configuration of gbSuite
 * 
 * 
 */
 
 /* Functions defined for gbSuite */
define("TEMPLATES_ROOT", $_SERVER['PHP_ROOT']."/template/");
define("TEMPLATES_FILE_EXT", ".html");

define("IMAGES_ROOT", 	"/images/");
define("CSS_ROOT"	,	"/css/");

include_once($_SERVER['PHP_ROOT']."/lib/core/mysql.php/");
include_once($_SERVER['PHP_ROOT']."/lib/friend.php/");
include_once($_SERVER['PHP_ROOT']."/lib/profile.php/");

if(isset($gbSuiteUser))
{
	return;
}
else
{
	if(isset($_POST['fb_sig_user']))
	{
		$friends = user_get_all_friends($_POST['fb_sig_user']);
		$user_info = profile_dbget_user_info($_POST['fb_sig_user']);
	}
	else
	{
		$friends = user_get_all_friends($_GET['fb_user_id']);
		$user_info = profile_dbget_user_info($_GET['fb_user_id']);
	}
	
	$gbSuiteUser = $user_info;
	$gbSuiteUser['picture'] = $user_info['pic'];
	$gbSuiteUser['name'] = $user_info['name'];
	$gbSuiteUser['status'] = $user_info['status_message'];
	$gbSuiteUser['address'] = $user_info['address'];
	$gbSuiteUser['phone'] = $user_info['phone'];
}
?>
