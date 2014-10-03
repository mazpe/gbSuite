<?php

/******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/


// FBOPEN:SETUP - Included for convenience.  YOUR_APP_SERVER_URL is meant to be the base url
// for your your canvas pages (application), likely different from YOUR_PLATFORM_SERVER_URL, your
// platform URL (for parsing FBML into served HTML, and servicing api requests).
// Note that YOUR_PLATFORM_SERVER_URL is also included in the platform tree under
// lib/core/init.php, as application and platform trees are typically on entirely
// different servers.

if(!isset($SERVER_URL))
	include_once($_SERVER['PHP_ROOT']."/gbSuite/configuration.php");


$YOUR_PLATFORM_SERVER_URL = $SERVER_URL;
$YOUR_APP_SERVER_URL = $SERVER_URL;

include_once $_SERVER['PHP_ROOT'].'/lib/core/init.php';
include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/config.php';

?>
