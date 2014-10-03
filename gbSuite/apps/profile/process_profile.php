<?php
	session_start();
	
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/server_url.php';
	
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/gbSuiteConfig.php';
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/apps/profile/default_apps_install.php';
	global $data_conn;
	
	$sql = "";
	$friendQuery = "";
	$friendQuery2 = "";
	$news = "";
	$news2 = "";
	$news3 = "";
	$appQuery = "";
	
	$departemnts = "";
		
	if(isset($_POST['user_department_sales'])) //Sales
		$department .= "Sales";
		
	if(isset($_POST['user_department_parts']))	//Parts
		$department .= (($department != "") ? "/" : "")."Parts";
		
	if(isset($_POST['user_department_services']))	//Services
		$department .= (($department != "") ? "/" : "")."Services";
		
	if(isset($_POST['user_department_accounting']))	//Accounting
		$department .= (($department != "") ? "/" : "")."Accounting";
			
	if(isset($_POST['user_department_finance']))	//Finance
		$department .= (($department != "") ? "/" : "")."Finance";
		
	if(isset($_POST['user_department_collision_center']))	//Collision Center
		$department .= (($department != "") ? "/" : "")."Collision Center";

	
	if($_POST['action'] == 'add')
	{
		/**Para la inserccion**/
		$insertString = "";
		$fieldsString = "";
		$valueString = "";

		$sql = "SELECT MAX(uid) + 1 AS uid FROM info";
	
		$data = queryf($data_conn, $sql);
		$row = mysql_fetch_assoc($data);
		
		$_POST['user_uid'] = $row['uid'];
	
		/**Para la inserccion**/
		foreach($_POST as $fieldName => $value)	
		{
			if((substr($fieldName, 0, 5) == "user_") && (strpos($fieldName, "user_department") === false ))
			{
				$fieldString .= (($fieldString == "")? "" : ", ").substr($fieldName, 5, strlen($fieldName) - 5);
				$valueString .= (($valueString == "")? "" : ", ")."'$value'";
			}					
		}
		
		$sql = "INSERT INTO info ($fieldString, department, name, status_message) VALUES ($valueString, '$department', '".$_POST['user_first_name']." ".$_POST['user_last_name']."', 'feeling great!')";
		
		$friendQuery = "";
		$friendQuery = "INSERT INTO friend (user1, user2) ".
		    			"SELECT '".$_POST['user_uid']."', uid ". 
						"FROM info I LEFT JOIN friend F ".
						"ON F.user2 IS NULL WHERE '".$_POST['user_uid']."' <> uid";
		
		$friendQuery2 = "INSERT INTO friend (user2, user1) ".
		    			"SELECT '".$_POST['user_uid']."', uid ". 
						"FROM info I LEFT JOIN friend F ".
						"ON F.user2 IS NULL WHERE '".$_POST['user_uid']."' <> uid";
						
		//$news = "INSERT INTO news (uid, type, value, date, status) VALUES ('".$_POST['user_uid']."', 'associate', '".$_POST['user_first_name']." ".$_POST['user_last_name']." joined to gbSuite.', now(), 0)";
		$news = "INSERT INTO news (uid, type, value, date, status) 
				VALUES ('".$_POST['user_uid']."', 'associate', '<fb:name uid=".$_POST['user_uid']." /> joined gbSuite.', now(), 0)";
		

					
		if(isset($_POST['fb_sig_user']) && isset($_POST['user_uid']))
		{		
			if($_POST['fb_sig_user'] != $_POST['user_uid'])
			{
				//$friendQuery = "INSERT INTO friend (user1, user2) VALUES ('".$_POST['fb_sig_user']."', '".$_POST['user_uid']."')";
				
				$news2 = "INSERT INTO news (uid, type, value, date, status) ".
				//CONCAT('<fb:name uid=', uid, '/>', ' added <fb:name uid=".$_POST['user_uid']." /> as associate.'),
						  //"SELECT '".$_POST['fb_sig_user']."', 'associate', CONCAT(name, ' added ".$_POST['user_first_name']." ".$_POST['user_last_name']." as associate.'), now(), 0 ". 
						  "SELECT '".$_POST['fb_sig_user']."', 'associate', CONCAT('<fb:name uid=', uid, ' />', ' added <fb:name uid=".$_POST['user_uid']." /> as associate.'), now(), 0 ".
						  "FROM info WHERE uid = '".$_POST['fb_sig_user']."'";
						  
				//echo $news2;
			}			
		}
		
		/*$where = "";	
		$where = (strpos($department, "Sales") === false ? "" : "department LIKE '%Sales%'");
		$where .= (strpos($department, "Parts") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Parts%'");
		$where .= (strpos($department, "Finance") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Finance%'");
		$where .= (strpos($department, "Collision Center") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Collision Center%'");
		$where .= (strpos($department, "Services") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Services%'");
		$where .= (strpos($department, "Accounting") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Accounting%'");		
		
		if($where != "")
		{
			$friendQuery2 = "INSERT INTO friend (user1, user2) ".
								"SELECT '".$_POST['user_uid']."', uid ". 
								"FROM info I LEFT JOIN friend F ".
								"ON F.user2 IS NULL WHERE ($where) AND '".$_POST['user_uid']."' <> uid";
								
			$friendQuery4 = "INSERT INTO friend (user2, user1) ".
								"SELECT '".$_POST['user_uid']."', uid ". 
								"FROM info I LEFT JOIN friend F ".
								"ON F.user2 IS NULL WHERE ($where) AND '".$_POST['user_uid']."' <> uid AND '".$_POST['me_uid']."' <> uid;";
	
			//echo $friendQuery2."<br/><br/>";
			//echo $friendQuery4."<br/>";
										
			/*$news3 = "INSERT INTO news (uid, type, value, date, status) ".
						//"SELECT '<fb:name uid=".$_POST['user_uid']." />', 'associate', CONCAT(name, ' added ".$_POST['user_first_name']." ".$_POST['user_last_name']." as associate.'), now(), 0 ". 
						"SELECT '".$_POST['user_uid']."', 'associate', CONCAT('<fb:name uid=', uid, ' />', ' added <fb:name uid=".$_POST['user_uid']." /> as associate.'), now(), 0 ".
						"FROM info I LEFT JOIN friend F ".
						"ON F.user2 IS NULL WHERE $where AND '".$_POST['user_uid']."' <> uid";
						
			$appQuery = "INSERT INTO user_apps ". 
							"SELECT '".$_POST['user_uid']."', app_id ".
							"FROM apps WHERE type = 'framework'";						
		}*/
		
		$appQuery = "INSERT INTO user_apps ". 
					"SELECT '".$_POST['user_uid']."', app_id ".
					"FROM apps WHERE type = 'framework'";		
	}
	else
		if($_POST['action'] == 'edit')
		{			 
			$depart = split("/", $department);			
								
			$query = "SELECT department FROM info where uid='".$_POST['user_uid']."' limit 1";
			$data = queryf($data_conn, $query);		           
	        $row = mysql_fetch_assoc($data);	            
	        $departments = split("/", $row['department']);
			
			$delDepart = array_diff($departments,$depart);	
			
			$updateString = "";
			
			/**Para la actualizacion**/
			foreach($_POST as $fieldName => $value)	
			{
				if((substr($fieldName, 0, 5) == "user_") && (strpos($fieldName, "user_department") === false ))
				{
					$updateString .= (($updateString == "")? "" : ", ").substr($fieldName, 5, strlen($fieldName) - 5)." = '".$value."'";
				}					
			}
											
			$updateString .= (($updateString == "")? "" : ", ")."department = '$department', name= '".$_POST['user_first_name']." ".$_POST['user_last_name']."'";
			
			$sql = "UPDATE info SET $updateString WHERE uid = '".$_POST['user_uid']."'";
						
			$result = queryf($data_conn, $sql);
			
			$sql = "INSERT INTO action_message (id, uid, app_id, message, context, action_status)VALUES (0, '".$_POST['fb_sig_user']."', 15, 'Profile has been updated.', 'user=".$_POST['user_uid']."', 1)";
			$result = queryf($data_conn, $sql);
			$sql = "";		
				
		    /*$where = "";
			
			$where = (strpos($department, "Sales") === false ? "" : "department LIKE '%Sales%'");
			$where .= (strpos($department, "Parts") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Parts%'");
			$where .= (strpos($department, "Finance") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Finance%'");
			$where .= (strpos($department, "Collision Center") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Collision Center%'");
			$where .= (strpos($department, "Services") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Services%'");
			$where .= (strpos($department, "Accounting") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Accounting%'");	
				
									
			if($where != "")
			{		
						
				$friendQuery5 = "insert into friend (user1, user2)				        
								select ".$_POST['user_uid'].", uid  from info where ($where) and uid != '".$_POST['user_uid']."' and 
								uid not in (select user2 from friend where user1 = '".$_POST['user_uid']."')";	
					
		        $friendQuery6 = "insert into friend (user2, user1)				        
								select uid, ".$_POST['user_uid']." from info where ($where) and uid != '".$_POST['user_uid']."' and 
								uid not in (select user2 from friend where user1 = '".$_POST['user_uid']."')";
				
								
				
				foreach($delDepart as $d)
				{
					$w = "";
			
				$w = (strpos($d, "Sales") === false ? "" : "department LIKE '%Sales%'");
				$w .= (strpos($d, "Parts") === false ? "" : ($w == "" ? "" : " OR ")."department LIKE '%Parts%'");
				$w .= (strpos($d, "Finance") === false ? "" : ($w == "" ? "" : " OR ")."department LIKE '%Finance%'");
				$w .= (strpos($d, "Collision Center") === false ? "" : ($w == "" ? "" : " OR ")."department LIKE '%Collision Center%'");
				$w .= (strpos($d, "Services") === false ? "" : ($w == "" ? "" : " OR ")."department LIKE '%Services%'");
				$w .= (strpos($d, "Accounting") === false ? "" : ($w == "" ? "" : " OR ")."department LIKE '%Accounting%'");	
			
					$deleteFriend = "DELETE FROM friend 
		   							 WHERE user1 = '".$_POST['user_uid']."' AND user2 IN (
								 	 select uid  from (SELECT uid FROM info I INNER JOIN friend F ON I.uid = F.user2 
									 WHERE F.user1 = '".$_POST['user_uid']."' AND ($w)) as T)";
				
					$result = queryf($data_conn, $deleteFriend);						
				}															   
				}*/			
		}	
	
	//echo $news1."<br/>";
	//echo $news2."<br/>";
	//echo $news3."<br/>";		
	//echo $sql;
	
	if($sql != "")
	{
		$result = queryf($data_conn, $sql);
		$sql = "INSERT INTO action_message (id, uid, app_id, message, context, action_status)VALUES (0, '".$_POST['fb_sig_user']."', 15, 'Profile was created.', 'user=".$_POST['user_uid']."', 1)";
		$result = queryf($data_conn, $sql);
	}
	
	
	if($friendQuery != "")
		$result = queryf($data_conn, $friendQuery);
	
	if($friendQuery2 != "")
		$result = queryf($data_conn, $friendQuery2);
	
	if($friendQuery4 != "")
		$result = queryf($data_conn, $friendQuery4);
			
	if($friendQuery5 != "")
		$result = queryf($data_conn, $friendQuery5);
		
	if($friendQuery6 != "")
		$result = queryf($data_conn, $friendQuery6);
				
	if($news != "")
		$result = queryf($data_conn, $news);
		
	if($news2 != "")
		$result = queryf($data_conn, $news2);
		
	if($news3 != "")
		$result = queryf($data_conn, $news3);
		
	if($appQuery != "")
		$result = queryf($data_conn, $appQuery);
	
	setDefaultApp($_POST['user_uid'],$_POST['user_title'] );
		
	header("Location:/gbSuite/home.php?app=profile&uid=".$_POST['user_uid']);
?>