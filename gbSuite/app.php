<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/util/connection.php");
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/user.php");
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/server_url.php';
	
	$applicationName = "";
	$applicationsDirectory = "apps";
	
	if(isset($_GET['app']) && $_GET['app']  != "")
		$applicationName = $_GET['app'];
	
	if($applicationName == "")
		$applicationName = "profile";
		
	$connection = new Connection();
	
	$sql = "SELECT link_id FROM link WHERE name = '". $applicationName ."'";
	
	//The menu id
	$link_id = $connection->get_value($sql);
	
	if($link_id == "")
	{
		$applicationName = "profile";
		
		$_GET['action'] = "";		
				
		$sql = "SELECT link_id FROM link WHERE name = '". $applicationName ."'";
	
		//The menu id
		$link_id = $connection->get_value($sql);
	}
			
	/**Read the information of the current user**/
	$query = "SELECT * FROM info WHERE uid = '".$_POST['me_uid']."' ";
	
	$row = $connection->get_row($query);
	
	$user = new User($row);
	
	$user->setConnection($connection);
	
	//Print the top header bar.
	printHeaderBar($user);

	//This means the user information to display is from a friend of the current user.
	if(isset($_GET['uid']))
		if($_GET['uid'] != $_POST['me_uid'])
			$user->setFriendUID($_GET['uid']);


	
	
	function printContent($position)
	{
		global $applicationsDirectory;
		global $connection;
		global $applicationName;
		global $link_id;
		global $user;
		
		//if($position == 'MAIN' && isset($_GET['action']))
		//	return;
			
		/**Read the information of the application**/				
		/*$query = "SELECT app_id, name, description, status, ifnull(U.position, A.position) AS position, ifnull(U.ordering, A.ordering) AS ordering, ".
				 "title, published, params, image, icon, default_titles, show_title, auth, class_name, file_name, type, link_id, UA.uid ".
				 "FROM apps A inner join link_app using(app_id) inner join user_apps UA using(app_id, uid) LEFT JOIN user_profile_layout U 
				 using(app_id) WHERE (link_id='". $link_id  ."' or link_id=0)  and ifnull(U.position, A.position)  = '$position' AND status = 'Active' AND UA.uid = '".$_POST['me_uid']."' ORDER BY ifnull(U.ordering, A.ordering)";
		*/
		
		$currentProfileUID = $user->getCurrentProfileAttribute('uid'); 
		
		//if($position == 'MAIN' && isset($_GET['action']))		
		//	return;
		
		if(currentProfileUID == $_POST['me_uid'])
		{
			if(($position == 'MAIN' || $position == 'TOP_RIGHT' || $position == 'TOP_CENTER') && isset($_GET['action']) && $_GET['action'] != '')
				/*$query = "SELECT app_id, name, description, status, ifnull(U.position, A.position) AS position, ifnull(U.ordering, A.ordering) AS ordering, title, published, params, image, icon, default_titles, show_title, auth, class_name, file_name, type, link_id, UA.uid ".
						 "FROM apps A inner join link_app using(app_id) ". 
						 "inner join user_apps UA using(app_id) ".
						 "LEFT JOIN user_profile_layout U using(app_id, uid) ".						  
						 "WHERE A.name = '$applicationName' AND (link_id=$link_id or link_id=0) and ifnull(U.position, A.position) = '$position' AND status = 'Active' AND UA.uid = '".$_POST['me_uid']."' ORDER BY ifnull(U.ordering, A.ordering) ";*/
				$query = "SELECT DISTINCT app_id, name, description, status, ifnull(U.position, A.position) AS position, ifnull(U.ordering, A.ordering) AS ordering, title, published, params, image, icon, default_titles, show_title, auth, class_name, file_name, type, UA.uid ".
					 "FROM apps A inner join link_app using(app_id) ". 
					 "INNER join user_apps UA using(app_id) ".
					 "LEFT JOIN user_profile_layout U using(app_id, uid) ".
					 "LEFT JOIN application_configuration AC USING(app_id, uid) ".					 
					 "WHERE A.name = '$applicationName' AND ((((link_id = $link_id AND $link_id != 1) OR (1 = $link_id AND AC.profile_link = 1))) or link_id = 0) and ifnull(U.position, A.position) = '$position' AND status = 'Active' AND (UA.uid = '".$user->getCurrentProfileUID()."') ".
					 //"WHERE A.name = '$applicationName' AND ((((link_id = $link_id AND $link_id != 1) OR (link_id = $link_id OR $link_id = 1 AND AC.profile_link = 1))) or link_id = 0) and ifnull(U.position, A.position) = '$position' AND status = 'Active' AND UA.uid = '".$user->getCurrentProfileUID()."' ". 
					 "AND (ifnull(AC.visibility, A.default_visibility) = 'Everyone' OR (ifnull(AC.visibility, A.default_visibility) = 'My Associates' AND ".$user->isFriend($user->getCurrentProfileUID())." ) ".
					 "OR A.type = 'framework' ".
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Every one in department' AND ".$user->areFromSameDepartment($user->getCurrentProfileUID())." = 1) ".
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Managers' AND ('".$user->getTitle()."' = 'Sales Manager' OR '".$user->getTitle()."' = 'Administrator' OR '".$user->getTitle()."' = 'General Manager' OR '".$user->getTitle()."' = 'General Sales Manager')) ". 
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Only me' AND UA.uid = '".$_POST['me_uid']."') ".
					 "OR ((ifnull(AC.visibility, A.default_visibility) = 'Some associates' OR ifnull(AC.visibility, A.default_visibility) = 'Except these peoples') AND userAllowed('".$_POST['me_uid']."', '".$user->getCurrentProfileUID()."', A.app_id) = 1)) ".
					 "ORDER BY ifnull(U.ordering, A.ordering)";
			else
				$query = "SELECT DISTINCT app_id, name, description, status, ifnull(U.position, A.position) AS position, ifnull(U.ordering, A.ordering) AS ordering, title, published, params, image, icon, default_titles, show_title, auth, class_name, file_name, type, UA.uid ".
					 "FROM apps A inner join link_app using(app_id) ". 
					 "INNER join user_apps UA using(app_id) ".
					 "LEFT JOIN user_profile_layout U using(app_id, uid) ".					 
					 "LEFT JOIN application_configuration AC USING(app_id, uid) ".
					 //"WHERE (link_id = $link_id or link_id = 0 ) and ifnull(U.position, A.position) = '$position' AND status = 'Active' AND UA.uid = '".$user->getCurrentProfileUID()."' ".					 					 
					 "WHERE ((((link_id = $link_id AND $link_id != 1) OR (1 = $link_id AND AC.profile_link = 1))) or link_id = 0) and ifnull(U.position, A.position) = '$position' AND status = 'Active' AND (UA.uid = '".$user->getCurrentProfileUID()."' ) ". 
					 "AND (ifnull(AC.visibility, A.default_visibility) = 'Everyone' OR (ifnull(AC.visibility, A.default_visibility) = 'My Associates' AND ".$user->isFriend($user->getCurrentProfileUID()).") ".
					 "OR A.type = 'framework' ".
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Every one in department' AND ".$user->areFromSameDepartment($user->getCurrentProfileUID())." = 1) ".
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Managers' AND ('".$user->getTitle()."' = 'Sales Manager' OR '".$user->getTitle()."' = 'Administrator' OR '".$user->getTitle()."' = 'General Manager' OR '".$user->getTitle()."' = 'General Sales Manager')) ". 
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Only me' AND UA.uid = '".$_POST['me_uid']."') ".
					 "OR ((ifnull(AC.visibility, A.default_visibility) = 'Some associates' OR ifnull(AC.visibility, A.default_visibility) = 'Except these peoples') AND userAllowed('".$_POST['me_uid']."', '".$user->getCurrentProfileUID()."', A.app_id) = 1)) ".
					 "ORDER BY ifnull(U.ordering, A.ordering)";
					 
			/**
			 * $query = "SELECT DISTINCT app_id, name, description, status, ifnull(U.position, A.position) AS position, ifnull(U.ordering, A.ordering) AS ordering, title, published, params, image, icon, default_titles, show_title, auth, class_name, file_name, type, link_id, UA.uid ".
					 "FROM apps A inner join link_app using(app_id) ". 
					 "inner join user_apps UA using(app_id) ".
					 "LEFT JOIN user_profile_layout U using(app_id, uid) ".					 
					 "LEFT JOIN application_configuration AC USING(app_id, uid) ".					 					 
					 "WHERE (link_id = $link_id or link_id = 0 ) and ifnull(U.position, A.position) = '$position' AND status = 'Active' AND UA.uid = '".$user->getCurrentProfileUID()."' ". 
					 "AND (ifnull(AC.visibility, A.default_visibility) = 'Everyone' OR (ifnull(AC.visibility, A.default_visibility) = 'My Associates' AND ".$user->isFriend($user->getCurrentProfileUID()).") ".
					 "OR A.type = 'framework' ".
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Every one in department' AND ".$user->areFromSameDepartment($user->getCurrentProfileUID())." = 1) ".
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Managers' AND ('".$user->getTitle()."' = 'Sales Manager' OR '".$user->getTitle()."' = 'Administrator')) ". 
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Only me' AND UA.uid = '".$_POST['me_uid']."') ".
					 "OR ((ifnull(AC.visibility, A.default_visibility) = 'Some associates' OR ifnull(AC.visibility, A.default_visibility) = 'Except these peoples') AND userAllowed('".$_POST['me_uid']."', '".$user->getCurrentProfileUID()."', A.app_id) = 1)) ".
					 "ORDER BY ifnull(U.ordering, A.ordering)";
			 */
		}
		else
		{
			if(($position == 'MAIN' || $position == 'TOP_RIGHT' || $position == 'TOP_CENTER') && isset($_GET['action']) && $_GET['action'] != "")
				/*$query = "SELECT app_id, name, description, status, ifnull(U.position, A.position) AS position, ifnull(U.ordering, A.ordering) AS ordering, title, published, params, image, icon, default_titles, show_title, auth, class_name, file_name, type, link_id, UA.uid ".
						 "FROM apps A inner join link_app using(app_id) ". 
						 "inner join user_apps UA using(app_id) ".
						 "LEFT JOIN user_profile_layout U using(app_id, uid) ".
						 "WHERE A.name = '$applicationName' AND (link_id=$link_id or link_id=0) and ifnull(U.position, A.position) = '$position' AND status = 'Active' AND UA.uid = '".$user->getCurrentProfileUID()."' ORDER BY ifnull(U.ordering, A.ordering) ";*/
						 
				$query = "SELECT DISTINCT app_id, name, description, status, ifnull(U.position, A.position) AS position, ifnull(U.ordering, A.ordering) AS ordering, title, published, params, image, icon, default_titles, show_title, auth, class_name, file_name, type, UA.uid ".
					 "FROM apps A inner join link_app using(app_id) ". 
					 "INNER join user_apps UA using(app_id) ".
					 "LEFT JOIN user_profile_layout U using(app_id, uid) ".
					 "LEFT JOIN application_configuration AC USING(app_id, uid) ".					 
					 "WHERE A.name = '$applicationName' AND ((((link_id = $link_id AND $link_id != 1) OR (1 = $link_id AND AC.profile_link = 1))) or link_id = 0) and ifnull(U.position, A.position) = '$position' AND status = 'Active' AND (UA.uid = '".$user->getCurrentProfileUID()."' ) ". 
					 "AND (ifnull(AC.visibility, A.default_visibility) = 'Everyone' OR (ifnull(AC.visibility, A.default_visibility) = 'My Associates' AND ".$user->isFriend($user->getCurrentProfileUID())." ) ".
					 "OR A.type = 'framework' ".
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Every one in department' AND ".$user->areFromSameDepartment($user->getCurrentProfileUID())." = 1) ".
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Managers' AND ('".$user->getTitle()."' = 'Sales Manager' OR '".$user->getTitle()."' = 'Administrator' OR '".$user->getTitle()."' = 'General Manager' OR '".$user->getTitle()."' = 'General Sales Manager')) ". 
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Only me' AND UA.uid = '".$_POST['me_uid']."') ".
					 "OR ((ifnull(AC.visibility, A.default_visibility) = 'Some associates' OR ifnull(AC.visibility, A.default_visibility) = 'Except these peoples') AND userAllowed('".$_POST['me_uid']."', '".$user->getCurrentProfileUID()."', A.app_id) = 1)) ".
					 "ORDER BY ifnull(U.ordering, A.ordering)";

			else
				$query = "SELECT DISTINCT app_id, name, description, status, ifnull(U.position, A.position) AS position, ifnull(U.ordering, A.ordering) AS ordering, title, published, params, image, icon, default_titles, show_title, auth, class_name, file_name, type, UA.uid ".
					 "FROM apps A inner join link_app using(app_id) ". 
					 "INNER join user_apps UA using(app_id) ".
					 "LEFT JOIN user_profile_layout U using(app_id, uid) ".
					 "LEFT JOIN application_configuration AC USING(app_id, uid) ".
					 //"WHERE (link_id = $link_id or link_id = 0 ) and ifnull(U.position, A.position) = '$position' AND status = 'Active' AND UA.uid = '".$user->getCurrentProfileUID()."' ".
					 "WHERE ((((link_id = $link_id AND $link_id != 1) OR (1 = $link_id AND AC.profile_link = 1))) or link_id = 0) and ifnull(U.position, A.position) = '$position' AND status = 'Active' AND (UA.uid = '".$user->getCurrentProfileUID()."' ) ". 
					 "AND (ifnull(AC.visibility, A.default_visibility) = 'Everyone' OR (ifnull(AC.visibility, A.default_visibility) = 'My Associates' AND ".$user->isFriend($user->getCurrentProfileUID())." ) ".
					 "OR A.type = 'framework' ".
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Every one in department' AND ".$user->areFromSameDepartment($user->getCurrentProfileUID())." = 1) ".
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Managers' AND ('".$user->getTitle()."' = 'Sales Manager' OR '".$user->getTitle()."' = 'Administrator' OR '".$user->getTitle()."' = 'General Manager' OR '".$user->getTitle()."' = 'General Sales Manager')) ". 
					 "OR (ifnull(AC.visibility, A.default_visibility) = 'Only me' AND UA.uid = '".$_POST['me_uid']."') ".
					 "OR ((ifnull(AC.visibility, A.default_visibility) = 'Some associates' OR ifnull(AC.visibility, A.default_visibility) = 'Except these peoples') AND userAllowed('".$_POST['me_uid']."', '".$user->getCurrentProfileUID()."', A.app_id) = 1)) ".
					 "ORDER BY ifnull(U.ordering, A.ordering)";
		}
		
		if(isset($_GET['debug']) && $_GET['debug'] == "true")
			echo "<div>".$query."</div>";
		
		//echo '<ul id="ul_'.$position.'" class="draglist">';
		
		$rs = mysql_query($query);
				
		$count = 0;
		
		if($rs !== false)
		{
			while($row = mysql_fetch_array($rs))	
			{
				include_once($_SERVER['PHP_ROOT']."/gbSuite/".$applicationsDirectory."/".$row['file_name']."/".$row['file_name'].".php");
				
				$applicationClass = $row['class_name'];	
				$function = "renderHTML";
				
				$params = array_merge($_POST, $_GET);
				
				$application = new $applicationClass();
				
				$application->setAttributes($row);
				
				$application->setConnection($connection);
				
				$application->setUser($user);
				
				if($row['name'] != 'action_status' || ($row['name'] == 'action_status' && $application->gotMessage() > 0))
				{
					//echo '<li id="'.$position.'_li_'.$count.'" class="'.$row['app_id'].'_mylistitem">';
					echo '<div class="application" id="'.$row['name'].'_id" >';
								
					if($row['show_title'] == 1)
					 	echo '<fb:application-title id="'.$row['name'].'">'.$row['title'].'</fb:application-title>';
					
						
					if($application->isInstalled() == true || $row['type'] == 'framework')
					{							
						//The action must come in the url
						$function = $_GET['action'];
						
						if(isset($function) && $function != "" && $row['name'] == $applicationName)
						{
							try
							{
								$application->$function($params);
								
								//call_user_func(array(&$application, $function), $params);
							}
							catch(Exception $ex)
							{
								 
							}
						}				
							
						$application->renderHTML();
					}
					else
					{
						if($row['show_title'] == 0)
					 		echo '<fb:application-title id="'.$row['name'].'">'.$row['title'].'</fb:application-title>';
							
						echo $application->notInstalledMessage();
					}
							
					
					echo '</div>';			
					//echo '</li>';
					
					++$count;
				}
			}
		}
				
		//if($count == 0 && $position == 'MAIN')
		//	applicationIsNotInstalled();
		
		if($position == 'MAIN')
			checkApplicationInstallation();
		
		//echo '</ul>';
	}
	
	function checkApplicationInstallation()
	{
			global $applicationsDirectory;
			global $connection;
			global $applicationName;
			global $link_id;
			global $user;

			$query = "SELECT * FROM apps WHERE name = '".$applicationName."'";
			
			$row = $connection->get_row($query);
			
			if($row != null && $row !==  false)
			{
				if($row['type'] == 'framework')
					return;
					
				include_once($_SERVER['PHP_ROOT']."/gbSuite/".$applicationsDirectory."/".$row['file_name']."/".$row['file_name'].".php");
				
				$applicationClass = $row['class_name'];	
				$function = "renderHTML";
				
				$params = array_merge($_POST, $_GET);
				
				$application = new $applicationClass();
				
				$application->setAttributes($row);
				
				$application->setConnection($connection);
				
				$application->setUser($user);
		
				if($application->isInstalled() == true)
				{}
				else
				{
					//echo '<li id="'.$position.'_li_'.$count.'" class="'.$row['app_id'].'_mylistitem">';
					echo '<div class="application" id="'.$row['name'].'_id">';
								
					if($row['show_title'] == 0)
					 		echo '<fb:application-title id="'.$row['name'].'">'.$row['title'].'</fb:application-title>';
							
					echo $application->notInstalledMessage();
					
					echo '</div>';
				}
			}	
	}
	
	function applicationIsNotInstalled()
	{
			global $applicationsDirectory;
			global $connection;
			global $applicationName;
			global $link_id;
			global $user;

			$query = "SELECT * FROM apps WHERE name = '".$applicationName."' AND type <> 'framework' ";
			
			$row = $connection->get_row($query);
			
			if($row != null && $row !==  false)
			{
				include_once($_SERVER['PHP_ROOT']."/gbSuite/".$applicationsDirectory."/".$row['file_name']."/".$row['file_name'].".php");
				
				$applicationClass = $row['class_name'];	
				$function = "renderHTML";
				
				$params = array_merge($_POST, $_GET);
				
				$application = new $applicationClass();
				
				$application->setAttributes($row);
				
				$application->setConnection($connection);
				
				$application->setUser($user);
		
				//echo '<li id="'.$position.'_li_'.$count.'" class="'.$row['app_id'].'_mylistitem">';
				echo '<div class="application" id="'.$row['name'].'_id">';
							
				if($row['show_title'] == 1)
				 	echo '<fb:application-title id="'.$row['name'].'">'.$row['title'].'</fb:application-title>';
				
				//The action must come in the url
				$function = $_GET['action'];
				
				if(isset($function) && $row['name'] == $_GET['app'])
					call_user_func(array(&$application, $function), $params);
				
				$application->renderHTML();
				
				echo '</div>';			
				//echo '</li>';
			}			
				
	}
	
	/**This must be the first html code to print**/
	function printHeaderBar($user)
	{ 
		$userInfo = $user->getAttributes();
		
		
		$full_name = $userInfo['first_name'] . " " . ($userInfo['middle_name'] != "" ? $userInfo['middle_name']." " : ""  ) . $userInfo['last_name'];
		
		$html = '<fb:header-bar >
				 	<div class="header-label">&nbsp;&nbsp;<b>'. $full_name .'</b>... Welcome to gbSuite!</div><div class="date-label" id="date-label"></div>
				 </fb:header-bar>';
				 
		echo $html;
	}


	function printFooterBar($user)
	{		
		echo "<fb:footer-bar  />";	
	}

?>
<script>
	function changeCertifiedOption()
	{
		var certified = document.getElementById('desk-log-veh-certified').getValue();
		
		if(certified == 'No')
		{
			document.getElementById('desk-log-veh-certified-no').setValue('');
			
			document.getElementById('desk-log-veh-certified-no').setStyle({display:'none'});
			document.getElementById('desk-log-veh-certified-no-label').setStyle({display:'none'});
		}	
		else
		{
			document.getElementById('desk-log-veh-certified-no').setStyle({display:'block'});
			document.getElementById('desk-log-veh-certified-no-label').setStyle({display:'block'});
		}			
	} 
	
	function onfocus(element)
	{
		element.setStyle({background:'yellow'});
		
		if(element.getValue() == 0)
			element.setValue('');	
			
		var deal_no = 0;
		var e = element.getId();
									
		deal_no = document.getElementById('desk_log_deal_no').getValue();
			
		if(e == 'desk-log-trade1-year' && document.getElementById('desk-log-trade1-deal-no').getValue() == "")
				document.getElementById('desk-log-trade1-deal-no').setValue(deal_no);
		else
			if(e == 'desk-log-trade2-year' && document.getElementById('desk-log-trade2-deal-no').getValue() == "")
				document.getElementById('desk-log-trade2-deal-no').setValue(deal_no);
			else
				 if(e == 'desk-log-trade3-year' && document.getElementById('desk-log-trade3-deal-no').getValue() == "")
					document.getElementById('desk-log-trade3-deal-no').setValue(deal_no);						
			
		
	
	}
	
	function onblur(element, defaultValue)
	{
		element.setStyle({background:'white'});
		
		if(element.getValue() == '' && defaultValue != null)
			element.setValue(defaultValue);	
	}

	function submitOptionsForm(appId)
	{
		var form = document.getElementById('desklog-options-form' + appId);
		
		form.submit();	
	}
	
	function showDealerView(appId)
	{
		document.getElementById('date-range-button' + appId).setDisabled(true);
		document.getElementById('desklog-filter-button' + appId).setDisabled(true);

		document.getElementById('desklog-dealer-view' + appId).setStyle({display:'block'});
				
		showFilter(appId);		
	}
	
	function showDateRange(appId)
	{
		document.getElementById('dealer-view-button' + appId).setDisabled(true);
		document.getElementById('desklog-filter-button' + appId).setDisabled(true);
		
		document.getElementById('desklog-date-range' + appId).setStyle({display:'block'});
		
		showFilter(appId);		
	}
	
	function showDesklogFilter(appId)
	{
		document.getElementById('date-range-button' + appId).setDisabled(true);
		document.getElementById('dealer-view-button' + appId).setDisabled(true);
		
		document.getElementById('desklog-filter' + appId).setStyle({display:'block'});
		
		showFilter(appId);		
	}
	
	function showFilter(appId)
	{
		document.getElementById('desklog-filter-container' + appId).setStyle({display:'block'});
	}
	
	function closeFilter(appId)
	{	
		document.getElementById('desklog-filter-container' + appId).setStyle({display:'none'});
		
		document.getElementById('dealer-view-button' + appId).setDisabled(false);
		document.getElementById('date-range-button' + appId).setDisabled(false);
		document.getElementById('desklog-filter-button' + appId).setDisabled(false);
		
		document.getElementById('desklog-date-range' + appId).setStyle({display:'none'});
		document.getElementById('desklog-dealer-view' + appId).setStyle({display:'none'});
		document.getElementById('desklog-filter' + appId).setStyle({display:'none'});		
	}
	
	function showBack()
	{
		showSandBox();
		
		document.getElementById('desk-log-trade-button').setDisabled(true);
		document.getElementById('desk-log-sales-button').setDisabled(true);
		document.getElementById('desk-log-notes-button').setDisabled(true);
		document.getElementById('desk-log-front-button').setDisabled(true);
		document.getElementById('desk-log-vehicle-button').setDisabled(true);
				
		document.getElementById('back-sbox-content').setStyle({display:'block'});
		document.getElementById('desk-log-reserve').focus();
	}
	
	function updatePowerRankTotal()
	{
		var total = 0;
		var unitsPercentage = document.getElementById('power-rank-setup-units-percentage').getValue();
		var frontPercentage = document.getElementById('power-rank-setup-front-percentage').getValue();
		var backPercentage = document.getElementById('power-rank-setup-back-percentage').getValue();
		var totalPercentage = document.getElementById('power-rank-setup-total-percentage').getValue();
		
		total = Math.floor(unitsPercentage) + Math.floor(frontPercentage) + Math.floor(backPercentage) + Math.floor(totalPercentage);
		document.getElementById('power-rank-setup-total').setValue(total);
	}
		function updateGoalsSettings(max){
		var total_goals=0;					
		var total_recommit=0;
		
		for(var i=0; i<max;i++){
				goals=document.getElementById('goals_settings_goals'+i).getValue();
				recommit=document.getElementById('goals_settings_recommit'+i).getValue();
				total_goals+=Math.floor(goals);
				total_recommit+=Math.floor(recommit);
			}
		document.getElementById('total_goals').setValue(total_goals);
		document.getElementById('total_recommit').setValue(total_recommit);
	}
	
	function updateDealerShipGoals(){
		
		
		var new_units_dealerships_goals=document.getElementById('dealerships_goals_new_units').getValue();
		var used_units_dealerships_goals=document.getElementById('dealerships_goals_used_units').getValue();
	
		var total_dealerships_goals=Math.floor(new_units_dealerships_goals)+Math.floor(used_units_dealerships_goals);
	
		document.getElementById('dealerships_goals_total_units').setValue(total_dealerships_goals);
		
		
	}
	
	
	
	function savePowerRankSetup()
	{
		var total = 0;
		var unitsPercentage = document.getElementById('power-rank-setup-units-percentage').getValue();
		var frontPercentage = document.getElementById('power-rank-setup-front-percentage').getValue();
		var backPercentage = document.getElementById('power-rank-setup-back-percentage').getValue();
		var totalPercentage = document.getElementById('power-rank-setup-total-percentage').getValue();
		
		total = Math.floor(unitsPercentage) + Math.floor(frontPercentage) + Math.floor(backPercentage) + Math.floor(totalPercentage);
		
		if(total == 100)
		{
			document.getElementById('power-rank-setup-error-message').setStyle({display:'none'});
			document.getElementById('power-rank-setup-form').submit();
		}	
		else
			document.getElementById('power-rank-setup-error-message').setStyle({display:'block'}); 	
	}
	
	function changeVehicleNU()
	{
		var nu = document.getElementById('desk-log-nu').getValue();
		var displayStyle = (nu == 'N' ? 'block' : 'none');
		
		document.getElementById('desk-log-other-label').setStyle({display:displayStyle});	
		document.getElementById('desk-log-front-other').setStyle({display:displayStyle});
	
		if(nu == 'U')
		{				
			document.getElementById('desk-log-holdback').setValue(0);
			document.getElementById('desk-log-onepercent-visible').setValue(0);
			document.getElementById('desk-log-onepercent').setValue(0);
			document.getElementById('desk-log-toyoguard').setValue(0);
			document.getElementById('desk-log-total-other').setValue(0);			
			document.getElementById('desk-log-other-input').setValue(0);
			document.getElementById('desk-log-veh-certified').setValue('Y');
			document.getElementById('desk-log-veh-certified-no').setValue('');
		}
		else
		{
			document.getElementById('desk-log-veh-certified').setValue('N');
			document.getElementById('desk-log-veh-certified-no').setValue('');
		}			
	}
	
	function onkeypress(event)
	{
		if(event.keyCode == 13)
			saveSandBox();
	}
	
	function updateOnePercent()
	{
		var holdback = document.getElementById('desk-log-holdback').getValue();
		var onePercent = document.getElementById('desk-log-onepercent-visible');
		var toyoguard = document.getElementById('desk-log-toyoguard').getValue();
		
		if(holdback == "")
			holdback = 0;
		
		/**Required by Mike Hankins, change 1% to 2% which will be the same number as holdback.**/
		/*onePercent.setValue(holdback / 2);						
		document.getElementById('desk-log-onepercent').setValue(holdback / 2 );
		document.getElementById('desk-log-total-other').setValue(Math.floor(holdback / 2) + Math.floor(toyoguard));*/
		
		onePercent.setValue(holdback);						
		document.getElementById('desk-log-onepercent').setValue(holdback);
		document.getElementById('desk-log-total-other').setValue(Math.floor(holdback) + Math.floor(toyoguard));
	}
	
	function saveSandBox()
	{		
		setBackTotal();
		setTradeTotal();
		setFrontTotal();
			
		closeSandBox();
	}
	
	function closeSandBox()
	{	
		document.getElementById('sbox-window').setStyle({display:'none'});
		
		document.getElementById('desk-log-back-button').setDisabled(false);
		document.getElementById('desk-log-front-button').setDisabled(false);
		document.getElementById('desk-log-trade-button').setDisabled(false);
		document.getElementById('desk-log-sales-button').setDisabled(false);
		document.getElementById('desk-log-notes-button').setDisabled(false);
		document.getElementById('desk-log-vehicle-button').setDisabled(false);
		document.getElementById('desk-log-nu').setDisabled(false);
		
		document.getElementById('back-sbox-content').setStyle({display:'none'});
		document.getElementById('front-sbox-content').setStyle({display:'none'});
		document.getElementById('trade-sbox-content').setStyle({display:'none'});
		document.getElementById('sales-sbox-content').setStyle({display:'none'});
		document.getElementById('notes-sbox-content').setStyle({display:'none'});		
		document.getElementById('used-vehicle-sbox-content').setStyle({display:'none'});
		document.getElementById('new-vehicle-sbox-content').setStyle({display:'none'});
	}
		
	function setBackTotal()
	{
		var total = 0, reserve = 0, vsi = 0, cc = 0, gap = 0, cl = 0, aft = 0, deal_no;
		
		reserve = document.getElementById('desk-log-reserve').getValue();
		vsi = document.getElementById('desk-log-vsi').getValue();
		cc = document.getElementById('desk-log-cc').getValue();
		gap = document.getElementById('desk-log-gap').getValue();
		cl = document.getElementById('desk-log-cl').getValue();
		aft = document.getElementById('desk-log-aft').getValue();	
		
		total = Math.round(reserve) + Math.round(vsi) + Math.round(cc) + Math.round(gap) + Math.round(cl) + Math.round(aft);
		document.getElementById('desk-log-back-button').setValue(total + "");
		
		var myelement = document.getElementById('desk-log-back-input');
		myelement.setValue(total);
	}
	
	function setFrontTotal()
	{
		var other = 0, toyoguard = 0, onepercent = 0;
		
		toyoguard = document.getElementById('desk-log-toyoguard').getValue();
		onepercent = document.getElementById('desk-log-onepercent').getValue();
		front = document.getElementById('desk-log-front').getValue();
		
		other = Math.round(toyoguard) + Math.round(onepercent);
		document.getElementById('desk-log-front-button').setValue(front + "");
		
		var myelement = document.getElementById('desk-log-other-input');
		myelement.setValue(other);
	}
	
	function setTradeTotal()
	{
		var trade1 = 0, make1 = "", model1 = "", year1 = "", acv1 = "";
		var trade2 = 0, make2 = "", model2 = "", year2 = "", acv2 = "";
		var trade3 = 0, make3 = "", model3 = "", year3 = "", acv3 = "";
				
		var result = 0;
		
		model1 = document.getElementById('desk-log-trade1-model').getValue();
		make1 = document.getElementById('desk-log-trade1-make').getValue();
		year1 = document.getElementById('desk-log-trade1-year').getValue();
		acv1 = document.getElementById('desk-log-trade1-acv').getValue();
				
		model2 = document.getElementById('desk-log-trade2-model').getValue();
		make2 = document.getElementById('desk-log-trade2-make').getValue();
		year2 = document.getElementById('desk-log-trade2-year').getValue();
		acv2 = document.getElementById('desk-log-trade2-acv').getValue();
		
		model3 = document.getElementById('desk-log-trade3-model').getValue();
		make3 = document.getElementById('desk-log-trade3-make').getValue();
		year3 = document.getElementById('desk-log-trade3-year').getValue();
		acv3 = document.getElementById('desk-log-trade3-acv').getValue();
		
		if((model1 != "" && model1 != "0") || (make1 != "" && make1 != "0") || (year1 != "" && year1 != "0") || (acv1 != "" && acv1 != "0"))
			trade1 += 1;
		
		if((model2 != "" && model2 != "0") || (make2 != "" && make2 != "0") || (year2 != "" && year2 != "0") || (acv2 != "" && acv2 != "0"))
			trade2 += 1;
		
		if((model3 != "" && model3 != "0") || (make3 != "" && make3 != "0") || (year3 != "" && year3 != "0") || (acv3 != "" && acv3 != "0"))
			trade3 += 1;
		
				
		document.getElementById('desk-log-trade-button').setValue(trade1 + trade2 + trade3);
		document.getElementById('desk-log-trade1-input').setValue(trade1);
		document.getElementById('desk-log-trade2-input').setValue(trade2);
		document.getElementById('desk-log-trade3-input').setValue(trade3);
	}
	
	function showBack()
	{
		showSandBox();
		
		document.getElementById('desk-log-trade-button').setDisabled(true);
		document.getElementById('desk-log-sales-button').setDisabled(true);
		document.getElementById('desk-log-notes-button').setDisabled(true);
		document.getElementById('desk-log-front-button').setDisabled(true);
		document.getElementById('desk-log-vehicle-button').setDisabled(true);
				
		document.getElementById('back-sbox-content').setStyle({display:'block'});
		document.getElementById('desk-log-reserve').focus();
	} 
	
	function showVehicle()
	{
		showSandBox();
		
		document.getElementById('desk-log-trade-button').setDisabled(true);
		document.getElementById('desk-log-sales-button').setDisabled(true);
		document.getElementById('desk-log-notes-button').setDisabled(true);
		document.getElementById('desk-log-front-button').setDisabled(true);
		document.getElementById('desk-log-back-button').setDisabled(true);
		
		var nu = document.getElementById('desk-log-nu');
		var selectedValue = nu.getValue();
		
		if(selectedValue == 'U')
		{
			document.getElementById('used-vehicle-sbox-content').setStyle({display:'block'});	
		}
		else
		{
			document.getElementById('new-vehicle-sbox-content').setStyle({display:'block'});
		}
		
		document.getElementById('desk-log-nu').setDisabled(true);
		document.getElementById('desk-log-mod-no').focus();
		document.getElementById('desk-log-veh-year').focus();
	}
	
	function showFront()
	{
		showSandBox();
		
		changeVehicleNU();
		
		document.getElementById('desk-log-trade-button').setDisabled(true);
		document.getElementById('desk-log-sales-button').setDisabled(true);
		document.getElementById('desk-log-notes-button').setDisabled(true);		
		document.getElementById('desk-log-back-button').setDisabled(true);
		document.getElementById('desk-log-vehicle-button').setDisabled(true);
		
		document.getElementById('front-sbox-content').setStyle({display:'block'});
		document.getElementById('desk-log-front').focus();
	}
	
	function showTrade()
	{
		showSandBox();
		
		document.getElementById('desk-log-back-button').setDisabled(true);
		document.getElementById('desk-log-sales-button').setDisabled(true);
		document.getElementById('desk-log-notes-button').setDisabled(true);
		document.getElementById('desk-log-front-button').setDisabled(true);
		document.getElementById('desk-log-vehicle-button').setDisabled(true);
		
		document.getElementById('trade-sbox-content').setStyle({display:'block'});
		document.getElementById('desk-log-trade1-year').focus();
				
	}
	
	function showSales()
	{
		showSandBox();
		
		document.getElementById('desk-log-trade-button').setDisabled(true);
		document.getElementById('desk-log-back-button').setDisabled(true);
		document.getElementById('desk-log-sales-button').setDisabled(true);
		document.getElementById('desk-log-front-button').setDisabled(true);
		document.getElementById('desk-log-vehicle-button').setDisabled(true);
		
		document.getElementById('sales-sbox-content').setStyle({display:'block'});
		document.getElementById('desk-log-sp1').focus();
	}
	
	function showNotes()
	{
		showSandBox();
		
		document.getElementById('desk-log-trade-button').setDisabled(true);
		document.getElementById('desk-log-back-button').setDisabled(true);
		document.getElementById('desk-log-sales-button').setDisabled(true);
		document.getElementById('desk-log-front-button').setDisabled(true);
		document.getElementById('desk-log-vehicle-button').setDisabled(true);
		
		document.getElementById('notes-sbox-content').setStyle({display:'block'});
		
		document.getElementById('desk-log-notes').focus();
	}
	
	function showSandBox()
	{
		document.getElementById('sbox-window').setStyle({display:'block'});
		//document.getElementById('sbox-overlay').setStyle({display:'block'});
	}
		
function changeTeamName(teamId)
{
	var name = document.getElementById('team-name').getValue(), newString = '';
	var ajax = new Ajax();
	var message=document.getElementById('change_name_message');
	
	ajax.responseType = Ajax.JSON;
		
	ajax.ondone = function(data)
	{	
		message.setStyle('display','');
	};		
		
	for(var i = 0; i < name.length; i++)
	{
		if(name.charAt(i) == ' ')
			newString += '%20'
		else
			newString += name.charAt(i);
	}	
	
	ajax.post('<?=$SERVER_URL;?>/gbSuite/apps/process_application.php?app=team_builder&uid=<?=$_POST['me_uid']?>&action=change_name&team_name=' + newString + '&team_id=' + teamId);
}
function addFriend(fuid)
{
	var ajax = new Ajax();
	
	ajax.responseType = Ajax.JSON;
		
	ajax.ondone = function(data)
	{	
		//Is not neccesary for now.
		var element = document.getElementById('add-remove-associate_' + fuid); 
		element.setTextValue(data.message);
		//document.setLocation(document.getLocation());
	};
		
	ajax.post('<?=$SERVER_URL;?>/gbSuite/apps/process_application.php?app=associates&uid=<?=$_POST['me_uid']?>&action=add&fuid=' + fuid);
}

function removeFriend(fuid)
{
	var ajax = new Ajax();
	
	ajax.responseType = Ajax.JSON;
		
	ajax.ondone = function(data)
	{	
		document.setLocation('/gbSuite/home.php?app=associates');
	};
		
	ajax.post('<?=$SERVER_URL;?>/gbSuite/apps/process_application.php?app=associates&uid=<?=$_POST['me_uid']?>&action=remove&fuid=' + fuid);
}

function saveDealerShip(dealerView)
{
		var ajax=new Ajax();
		
		ajax.responseType=Ajax.JSON;
		var message=document.getElementById('dealerships_goals_message');	
		ajax.ondone=function(data){					
			message.setStyle('display','');
		};
		
		var new_units=document.getElementById('dealerships_goals_new_units').getValue();
		var used_units=document.getElementById('dealerships_goals_used_units').getValue();
	
		ajax.post('<?=$SERVER_URL;?>/gbSuite/apps/process_application.php?app=goals_settings&action=saveDealerShips&dv='+dealerView+'&nu='+new_units+'&uu='+used_units);
		
	}


function submitProfile()
{
	var password, confirmPassword, email, error = false;
	
	email = document.getElementById('email').getValue();
	password = document.getElementById('password').getValue();
	confirmPassword = document.getElementById('confirm_password').getValue();
	
	if(email == '')
	{
		error = true;
		document.getElementById('invalid-email-message').setTextValue('Please enter a valid email address.');
	}
				
	if(password == confirmPassword)
	{
		if(password == '')
		{
			error = true
			document.getElementById('invalid-password-message').setTextValue("Password or confirm password can be empty.");
		}
	}	
	else
	{
		error = true;
		document.getElementById('invalid-password-message').setTextValue("Password and confirm password doesnt match.");
	}
	
	if(error == false)
		document.getElementById('edit_profile_form').submit();
}

function setClass(class, row, class2)
{
	row.setClassName(class);
	
	if (class2)
		row.addClassName(class2);
}

function viewEmailMessage(id)
{
	var ajax = new Ajax();
	
	ajax.responseType = Ajax.JSON;
		
	ajax.ondone = function(data)
	{	
		document.getElementById('email-message').setTextValue(data.message);
	};
		
	ajax.post('<?=$SERVER_URL;?>/gbSuite/apps/process_application.php?app=inbox&uid=<?=$_POST['me_uid']?>&action=view_email_message&email_id=' + id);
}

function replyEmail()
{
					
}

function readNotification()
{
	var notificationId = document.getElementById('notification-id').getValue();
	
	document.setLocation("/gbSuite/apps/process_application.php?app=notification&action=read&id=" + notificationId + "&uid=<?=$_POST['me_uid'];?> &redirect=notification");	
}	
							
function do_ajax(type, app, position) 
{
	if(type == null)
		type = Ajax.JSON;
		
	var ajax = new Ajax();
	
	ajax.responseType = Ajax.JSON;
		
	switch (type) 
	{
		case Ajax.JSON:
			ajax.ondone = function(data)
			{
				//document.getElementById('message-login').setTextValue(data.login? '' : data.message);					
			};
			break;
	};
			
	ajax.post('<?=$YOUR_APP_SERVER_URL;?>/gbSuite/apps/profile_user_layout.php?uid=<?=$_POST['me_uid']?>&app=' + app + '&position=' + position);
}


function arrange_application_list(type, app, appId, position) 
{
	if(type == null)
		type = Ajax.JSON;
		
	var ajax = new Ajax();
	
	ajax.responseType = Ajax.JSON;
		
	switch (type) 
	{
		case Ajax.JSON:
			ajax.ondone = function(data)
			{
				//document.getElementById('message-login').setTextValue(data.login? '' : data.message);					
			};
			break;
	};
			
	ajax.post('<?=$SERVER_URL;?>/gbSuite/apps/app_config/process_application_order.php?uid=<?=$_POST['me_uid']?>&app=' + app + '&position=' + position + '&app_id=' + appId);
}

function setFocus(element)
{
	element.focus();
}

//function activeStatusMessage(element)
function activeStatusMessage()
{
	document.getElementById('status-message').setStyle({display:'block'});
	document.getElementById('status-message-label').setStyle({display:'none'});
	//element.focus();
	
	//element.setStyle({background:'white', border:'1px', borderColor: '#E9E9E9', borderStyle:'solid'});
	
	document.getElementById('status-message-button').setStyle({display:'block'});	
}		

function changeStatusMessage()
{
	var statusMessageInput, statusMessage, statusMessageLabel;
	var ajax = new Ajax();
	var newString = "";
	
	statusMessageInput = document.getElementById('status-message');
	statusMessage = statusMessageInput.getValue().toString();	
	statusMessageLabel = document.getElementById('status-message-label');
	
	statusMessageLabel.setTextValue(statusMessage);
	
	for(var i = 0; i < statusMessage.length; i++)
	{
		if(statusMessage.charAt(i) == ' ')
			newString += '%20'
		else
			newString += statusMessage.charAt(i);
	}
	
	statusMessage = newString;
	
	ajax.responseType = Ajax.JSON;
		
	ajax.ondone = function(data)
	{		
		document.getElementById('status-message-label').setStyle({display:'block'});
		document.getElementById('status-message').setStyle({display:'none'});
		document.getElementById('status-message-button').setStyle({display:'none'});		
	};
		
	ajax.post('<?=$SERVER_URL;?>/gbSuite/apps/profile/status_message.php?uid=<?=$_POST['me_uid']?>&status_message=' + statusMessage);
}

function editSettings(applicationName)
{
	var ajax = new Ajax();
	
	ajax.responseType = Ajax.JSON;
		
	ajax.ondone = function(data)
	{		
		document.getElementById('main-setting-div').setStyle({display:'block'});
		document.getElementById('application-setting-div').setInnerXHTML(data.content);
	};
		
	ajax.post('<?=$SERVER_URL;?>/gbSuite/apps/process_application.php?app=' + applicationName + '&uid=<?=$_POST['me_uid']?>&action=edit_settings');
}

function closeDialog(elementId)
{
	document.getElementById('edit_settings_dialog').setStyle({display:'none'});
	document.getElementById('main-setting-div').setStyle({display:'none'});
}


function saveApplicationSettings()
{
	var ajax = new Ajax();
	var newString = '', visibility = '', leftMenu, appId, applicationName;
		
	visibility = document.getElementById('visibility-list').getValue();
	leftMenu = document.getElementById('left-menu').getValue();
	appId = document.getElementById('application-id').getValue();
	applicationName = document.getElementById('application-name').getValue();
	
	for(var i = 0; i < visibility.length; i++)
	{
		if(visibility.charAt(i) == ' ')
			newString += '%20'
		else
			newString += visibility.charAt(i);
	}
	
	visibility = newString;
	
	ajax.responseType = Ajax.JSON;
		
	ajax.ondone = function(data)
	{		
		//closeDialog();
	};
		
	ajax.post('<?=$SERVER_URL;?>/gbSuite/apps/process_application.php?app=' + applicationName + '&action=save_settings&uid=<?=$_POST['me_uid']?>&app_id=' + appId + '&visibility=' + visibility + '&profile_link=' + leftMenu);
}

function saveApplicationAssociatePermission(type, appId, uids) 
{
	var ajax = new Ajax();

	ajax.responseType = Ajax.JSON;

	var type = document.getElementById('visibility-list').getValue();
	
	if(type == 'Some associates')
		type = 'allowed';
	else
		type = 'denied';
		
	ajax.ondone = function(data)
	{
		//document.getElementById('message-login').setTextValue(data.login? '' : data.message);					
	};
		
	ajax.post('<?=$SERVER_URL;?>/gbSuite/apps/process_application_associate_permission.php?uid=<?=$_POST['me_uid']?>&uids=' + uids + '&app_id=' + appId + '&type=' + type);
}

function addItems()
{
	var uids, uids2, type, appId, list1, applicationName;
	 
	var ajax = new Ajax();

	ajax.responseType = Ajax.JSON;

	type = document.getElementById('visibility-list').getValue();
	appId = document.getElementById('application-id').getValue();
	applicationName = document.getElementById('application-name').getValue();
	
	if(type == 'Some associates')
		type = 'allowed';
	else
		if(type == 'Except these peoples')
			type = 'denied';
		
	//get the selected items
	uids = getSelectedItems("associates-list1");
	
	//read the current items
	uids2 = getListItems("associates-list2");
		
	/*if(uids.charAt(uids.length - 1) == ',')
		uids = uids.substring(0, uids.length - 2);
	
	if(uids2.charAt(uids2.length - 1) == ',')
		uids2 = uids2.substring(0, uids2.length - 2);*/
	
	if(uids2 != '')
		uids = uids + ',' + uids2;
			
	ajax.ondone = function(data)
	{	
		document.setLocation('<?=$YOUR_APP_SERVER_URL;?>/gbSuite/home.php?app=applications&action=edit_application_settings&application=' + applicationName);
		//document.getElementById('message-login').setTextValue(data.login? '' : data.message);					
	};
		
	ajax.post('<?=$SERVER_URL;?>/gbSuite/apps/process_application_associate_permission.php?uid=<?=$_POST['me_uid']?>&uids=' + uids + '&app_id=' + appId + '&type=' + type);				
}

function removeItems()
{
	var uids, uids2, type, appId, list1, applicationName;
	 
	var ajax = new Ajax();

	ajax.responseType = Ajax.JSON;

	type = document.getElementById('visibility-list').getValue();
	appId = document.getElementById('application-id').getValue();
	applicationName = document.getElementById('application-name').getValue();
	
	if(type == 'Some associates')
		type = 'allowed';
	else
		if(type == 'Except these peoples')
			type = 'denied';
		
	//get the selected items
	uids = getNotSelectedItems("associates-list2");
	
	ajax.ondone = function(data)
	{	
		document.setLocation('<?=$YOUR_APP_SERVER_URL;?>/gbSuite/home.php?app=applications&action=edit_application_settings&application=' + applicationName);
		//document.getElementById('message-login').setTextValue(data.login? '' : data.message);					
	};
		
	ajax.post('<?=$SERVER_URL;?>/gbSuite/apps/process_application_associate_permission.php?uid=<?=$_POST['me_uid']?>&uids=' + uids + '&app_id=' + appId + '&type=' + type);				

}

function saveList()
{
	var uids, type, appId, list1, applicationName;
	 
	var ajax = new Ajax();

	ajax.responseType = Ajax.JSON;

	type = document.getElementById('visibility-list').getValue();
	appId = document.getElementById('application-id').getValue();
	applicationName = document.getElementById('application-name').getValue();
	
	if(type == 'Some associates')
		type = 'allowed';
	else
		if(type == 'Except this peoples')
			type = 'denied';
		
	uids = getSelectedItems("associates-list2");
		
	if(uids.charAt(uids.length - 1) == ',')
		uids = uids.substring(0, uids.length - 2);
		
	ajax.ondone = function(data)
	{	
		document.setLocation('<?=$YOUR_APP_SERVER_URL;?>/gbSuite/home.php?app=applications&action=edit_application_settings&application=' + applicationName);
		//document.getElementById('message-login').setTextValue(data.login? '' : data.message);					
	};
		
	ajax.post('<?=$SERVER_URL;?>/gbSuite/apps/process_application_associate_permission.php?uid=<?=$_POST['me_uid']?>&uids=' + uids + '&app_id=' + appId + '&type=' + type);
}

function getUListItems(ul) 
{
	var uids = "";
	
	//var items = document.getElementById('app1234567_team-builder-member').getChildNodes();
	var items = ul.getChildNodes();
	
	for (i = 0; i < items.length; i = i + 1) 
	{		
		uids += items[i].getId().replace("_mylistitem", "");
		
		if(i + 1 < items.length)
			uids += ",";
	}

	if(uids.charAt(uids.length - 1) == ',')
		uids = uids.substring(0, uids.length - 2);
	
	return uids;	
}

function saveTeam() 
{
	var ul2 = document.getElementById("team-builder-member");
	var uids = getUListItems(ul2);
	var teamId = document.getElementById('team-id').getValue();
	
	var ajax = new Ajax();

	ajax.responseType = Ajax.JSON;

	ajax.ondone = function(data)
	{
		document.setLocation('<?=$YOUR_APP_SERVER_URL;?>/gbSuite/home.php?app=team_builder');
		
		//document.getElementById('message-login').setTextValue(data.login? '' : data.message);					
	};
		
	ajax.post('<?=$YOUR_APP_SERVER_URL;?>/gbSuite/apps/process_application.php?app=team_builder&action=update_team&uid=<?=$_POST['me_uid']?>&uids=' + uids + '&team_id=' + teamId);
}

function getListItems(listId)
{
	var uids = "";
	var nodes = document.getElementById(listId).getChildNodes();
	
	for (i = 0; i < nodes.length; i = i + 1) 
	{
		uids += nodes[i].getValue();
		
		if (i + 1 < nodes.length) 
			uids += ",";	
	}
	
	return uids;
}

function getSelectedItems(listId) 
{
	var uids = "";
	var nodes = document.getElementById(listId).getChildNodes();
	
	for (i = 0; i < nodes.length; i = i + 1) 
	{
		if (nodes[i].getSelected()) 
		{
			uids += nodes[i].getValue();
			
			if (i + 1 < nodes.length) 
				uids += ",";
		}
	}
	
	return uids;
}

function getNotSelectedItems(listId) 
{
	var uids = "";
	var nodes = document.getElementById(listId).getChildNodes();
	
	for (i = 0; i < nodes.length; i = i + 1) 
	{
		if (nodes[i].getSelected() == false) 
		{
			uids += nodes[i].getValue();
			
			if (i + 1 < nodes.length) 
				uids += ",";
		}
	}
	
	return uids;
}
		
function loadApplicationAssociatePermission(dropDownList)
{
	
	
	/*var ajax = new Ajax();
	var type = dropDownList.getValue();
	var uid = '<?=$_POST['me_uid']?>';
	 
	if(type == 'Some associates')
		type = 'allowed';
	else
		if(type == 'Except these peoples')
			type = 'denied';
	
	if(type != 'allowed' && type != 'denied')
		uid = '000000';
		
	ajax.responseType = Ajax.JSON;
		
	ajax.ondone = function(data)
	{
		if(data.content != '')
		{
			//document.getElementById('application-associates-permission').setStyle({display:'block'});
			
			document.getElementById('application-associates-permission').setInnerXHTML(data.content);
			
			loadYahoo();	
		}		
		else
		{
			//document.getElementById('application-associates-permission').setStyle({display:'none'});
		}
			
	};
	
	ajax.post('<?=$YOUR_APP_SERVER_URL;?>gbSuite/apps/process_application.php?app=applications&redirect=false&uid=' + uid + '&action=associates_list&type=' + type);		
	//ajax.post('<?=$YOUR_APP_SERVER_URL;?>gbSuite/apps/process_application_associate_permission.php?uid=<?=$_POST['me_uid']?>&uids=' + uids + '&app_id=' + appId);

	/*else
		document.getElementById('application-associates-permission').setStyle({display:'none'});*/
}


function checkUserState(){
	var ajax = new Ajax();
	var element=document.getElementById('associated-online')
	ajax.responseType = Ajax.JSON;		
	ajax.ondone = function(data)
	{	
		//element.setInnerXHTML(data.message);		
		element.setTextValue("Online Associates ("+data.message+")");
	};
	ajax.onerror= function (data){};
	ajax.post('<?=$SERVER_URL;?>/gbSuite/util/online_user_util.php?verify=true');
	
}

function showOnlineUserDetail(){
	var ajax= new Ajax();
	ajax.responseType=Ajax.JSON;
		
	ajax.ondone=function(data)
	{
		setUserOnline(data.message,data.names);		
	};
	
	ajax.post('<?=$SERVER_URL;?>/gbSuite/util/online_user_util.php?withDetail=true');
}

</script>



<table class="table-page" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td>
			<div class="page">
				<div class="leftcolumn" id="leftcolumn">
					<div class="dropzone">
						<?
							printContent('LEFT');
						?>
					</div>
				</div>
				<div class="content">					
					<div id="menu" class="menu">
						<div class="dropzone">	
							<?	
								printContent('MENU');
							?>
						</div>		
					</div>
					<div id="top" class="top">
						<div class="dropzone">
							<table class="top-table" width="100%" cellspacing="0" cellpadding="0" border="0">
								<tr>
									<td id="top-left" class="top-left">										
										<div class="dropzone">																					
											<?	
												printContent('TOP_LEFT');
											?>											
										</div>
									</td>
									<td id="top-center" class="top-center">
										<div class="dropzone">						
											<?	
												printContent('TOP_CENTER');
											?>	
										</div>		
									</td>
									<td id="top-right" class="top-right">
										<div class="dropzone">						
											<?	
												printContent('TOP_RIGHT');
											?>
										</div>				
									</td>
								</tr>
							</table>
						</div> 						
					</div>
					<div id="main" class="main">
						<div class="dropzone">							
								<?
									printContent('MAIN'); 	
								?>							
						</div>
					</div>	
				</div>	
			</div>
		</td>
	</tr>
	
</table>

<?
				printFooterBar($user); 
?>