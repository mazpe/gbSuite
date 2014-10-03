<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/gbSuiteConfig.php';
	
    class AppConfig extends Application
	{
		public function __construct()
		{
			
		}
	
		//The order correspond with the order that comes the appId items.
		public function setProfileApplicationOrder($uid, $appId, $position)		
		{			
			$connection = new Connection();

			$appIdArray = explode(',', $appId);
			
			//Uses orderingArray because appIdArray brings an extra semi colon.
			for($i = 0; $i < count($appIdArray); $i++)
			{
				//Select if the application for the current user exists in the user_profile_layout table
				$query = "SELECT COUNT(id) AS count FROM user_profile_layout WHERE uid = '".$uid."' AND app_id = ".$appIdArray[$i];
		
				$count = $connection->get_value($query);
			
				if($count == 0)
					$query = "INSERT INTO user_profile_layout (app_id, uid, position, ordering) VALUES (".$appIdArray[$i].", '".$uid."', '".$position."', ".$i.")";
				else
				{
					//Verifiy if the currentPosition is as the actual position
					$query = "SELECT count(id) FROM user_profile_layout WHERE uid = '$uid' AND app_id = ".$appIdArray[$i]." AND ordering = ".$i;
					
					$count = $connection->get_value($query);
					 
					if($count == 0)				
						$query = "UPDATE user_profile_layout SET position = '".$position."', ordering = ".$i." WHERE uid = '".$uid."' AND app_id = ".$appIdArray[$i];
					else
						$query = "";
				}
					
				if($query != "")
					$connection->exec_query($query);
			}
			
			echo '{"message":"User Profile layout saved."}';
		}
		
		public function order()
		{
			
			$this->printApplicationsByPosition('MAIN');
			$this->printApplicationsByPosition('LEFT');
			
		}
		
		private function printApplicationsByPosition($position)
		{
			$html = "";
			$sql = "";
			
			/*$sql = " select apps.app_id,apps.name,apps.description,apps.status,apps.position, 
					 apps.ordering,apps.title,apps.published,apps.params,icon,apps.file_name 
			 		 from user_apps  inner join apps using(app_id) 
			 		 where user_apps.uid = '". $_POST['me_uid'] ."' and position = '$position' and apps.type <> 'framework' and status='Active'";*/
			
			/*$sql = "SELECT app_id, name, description, status, ifnull(U.position, A.position) AS position, ifnull(U.ordering, A.ordering) AS ordering, title, published, params, image, icon, default_titles, show_title, auth, class_name, file_name, type, UA.uid ".
				 	"FROM apps A inner join user_apps UA using(app_id) ".
				 	"LEFT JOIN user_profile_layout U using(app_id, uid) ". 
				 	"WHERE ifnull(U.position, A.position) = '$position' AND status = 'Active' AND UA.uid = '".$_POST['me_uid']."' and A.type <> 'framework' ORDER BY ifnull(U.ordering, A.ordering)";*/
			
				if($position == 'MAIN')
				$sql = "SELECT A.app_id, name, description, status, ifnull(U.position, A.position) AS position, ifnull(U.ordering, A.ordering) AS ordering, title, published, params, image, icon, default_titles, show_title, auth, class_name, file_name, type, UA.uid ".
				 	"FROM apps A inner join user_apps UA using(app_id) ".
				 	"LEFT JOIN user_profile_layout U using(app_id, uid) 
					INNER JOIN application_configuration AC ON AC.uid = UA.uid AND AC.app_id = A.app_id ".
				 	"WHERE ifnull(U.position, A.position) = '$position' and order_allowed=1 AND status = 'Active' AND UA.uid = '".$_POST['me_uid']."' AND AC.profile_link = 1 ORDER BY ifnull(U.ordering, A.ordering)";
			else
				 $sql = "SELECT app_id, name, description, status, ifnull(U.position, A.position) AS position, ifnull(U.ordering, A.ordering) AS ordering, title, published, params, image, icon, default_titles, show_title, auth, class_name, file_name, type, UA.uid ".
				 	"FROM apps A inner join user_apps UA using(app_id) ".
				 	"LEFT JOIN user_profile_layout U using(app_id, uid) ".
				 	"WHERE ifnull(U.position, A.position) = '$position' AND status = 'Active' AND UA.uid = '".$_POST['me_uid']."' and order_allowed=1 ORDER BY ifnull(U.ordering, A.ordering)";
			
			$result = $this->connection->exec_query($sql);
		
			$html .= '<div><div class="application-content config-title">'.$position.'</div>';
			$html .= '<ul id="application_list_'.$position.'" class="application-list">';
					
			while($row = mysql_fetch_assoc($result))
			{
				$image = $row['icon'];
				
				$image_dir = $_SERVER['PHP_ROOT']."/gbSuite/apps/". $row['name'] ."/$image";
				
				if(file_exists($image_dir) && !is_dir($image_dir))
					$image = "/gbSuite/apps/". $row['name'] ."/$image";				
				else 
					$image = IMAGES_ROOT."resources/app_default.gif";	

				$html .= '<li id="application_list_item_'.$row['app_id'].'" class="application-list-item"><img src="'.IMAGES_ROOT.'resources/movearrow.gif" />&nbsp;&nbsp<img src="'.$image.'">'.$row['title'].'</li>';
			}
			
			$html .= "</ul>";
			$html .= "</div>";
			$this->html .= $html;						
		}
		
		public function edit_application_settings($params)
		{
			$applicationName = $params['application'];
			$applicationsDirectory = "apps";
						
			$row = $this->connection->get_row("SELECT * FROM apps WHERE name = '".$applicationName."'");
	 	
			include_once($_SERVER['PHP_ROOT']."/gbSuite/".$applicationsDirectory."/".$row['file_name']."/".$row['file_name'].".php");
		
			$applicationClass = $row['class_name'];			 
			$application = new $applicationClass();
				
			$application->setAttributes($row);			
			$application->setConnection($this->connection);	
			$application->setUser($this->user);
			
			$this->html = $application->editSettings($params);
		}
		 
		public function view_more($params)
		{
			$uid = $this->user->getUID();
			
			if($this->user->getTitle() == "Administrator")
			{
				$sql = "select *, 1 as hasAccess,'' as status from apps
					where status = 'Active' and  app_id not in (select app_id from user_apps where uid='". $this->user->getUID() ."') 
					and (type='administration' or default_titles = 'PUBLIC' or default_titles like concat('%','[". $this->user->getTitle() ."]','%') )
					";
			}
			else
			{
				$sql = "select *, if(default_titles = 'PUBLIC' or default_titles like concat('%','[". $this->user->getTitle() ."]','%'),1,0) as hasAccess, 
						 ifnull((select status from air where uid='". $this->user->getUID() ."' and app_id = A.app_id limit 1),'') as status  
						from apps A where status = 'Active' and app_id not in (select app_id from user_apps where uid='" . $this->user->getUID()  . "')
						and type != 'administration'";
						
						///echo $sql;
						
			} 
			
						
			$apps = $this->connection->exec_query($sql);
			
			$this->html .= '<div class="config-table">
					<table cellspacing=0 cellpadding=0 cellmargin=0 border=0 width=80%>
						<tr>
						<td>
							<div class="config-title">Available Applications</div>
							<div class="config-subtitle">Add a new application to your profile</div>
						</td> <td><a href="/gbSuite/home.php?app=applications"><input type="submit" name="Go Back" value="Go Back" class="button-link"/></a></td>
						</tr> 
					</table>';
			
			$rowCount = mysql_num_rows($apps);
			
			if($rowCount == 0)
			{
				$this->html .= "<fb:message>No applications Available!</fb:message>";
			}
			
			$this->html .= '<table class="table-item" cellspacing=0 cellmargin=0 cellpadding=0 border=0>';
			
			while($row = mysql_fetch_array($apps))
			{
				
				$image = $row['icon'];
				$image_dir = $_SERVER['PHP_ROOT']."/gbSuite/apps/". $row['name'] ."/$image";
								
				if(file_exists($image_dir) && !is_dir($image_dir))
				{
					$image = "/gbSuite/apps/". $row['name'] ."/$image";
				}
				else 
				{
					$image = IMAGES_ROOT."resources/app_default.gif";	
				}
				
				
				$this->html .= "<fb:config-table-item >";	
				$this->html .= "<td width=10px><img src='$image' /></td>";
				$this->html .= "<td >".$row["title"] ."&nbsp;&nbsp;<a href='/gbSuite/home.php?app=". $row['name'] ."&action=about'>(about)</a></td>";
				
				if($row['hasAccess'])
				{
					$url = "/gbSuite/apps/process_application.php?app=applications&uid=".$this->user->getUID()."&action=add&new_app=". $row['app_id']."&redirect=applications&method=view_more";
				}
				else
				{
					$url = "/gbSuite/home.php?app=air&action=make&app_id=". $row['app_id'];
				} 
				
				if($row['status'] == "Pending")
					$this->html .= "<td width=10px><img src='". $this->getBaseUrl() ."images/pending.gif' /></td>";
				else
					$this->html .= "<td width=10px><a href='$url'> <img src='/images/resources/add.gif' /></a></td>";
					
				
				
				$this->html .= "<td >".$row["description"] ."</td>";
				$this->html .= "<td >". ($row['status'] == "Pending" ?  $row['status'] : "") ."</td>";
				$this->html .= "</fb:config-table-item >";
			}			 
			
			$this->html .= "</table>";				
			
		}
		
		
		public function add($params)
		{
			$app_id = $params['new_app'];
			
			$sql = "insert into user_apps  (uid,app_id ) values ('". $this->user->getUID() ."','" . $app_id . "')";			
			
			$this->connection->exec_query($sql);
			
			$sql =  "SELECT default_visibility FROM apps WHERE app_id = $app_id";
			$defaultVisibility = $this->connection->get_value($sql);
			
			$sql = "INSERT INTO application_configuration (id, uid, app_id, visibility, profile_link) VALUES (0, '".$this->user->getUID()."', $app_id, '$defaultVisibility', 0)";
			$this->connection->exec_query($sql);
			
			$sql = "SELECT title FROM apps WHERE app_id = $app_id";
			$title = $this->connection->get_value($sql);
			
			$uid = $this->user->getUID();
						
			//$this->notif_apps($title, $uid);
			//$this->renderHTML();
		}
		
		public function remove($params)
		{
			$app_id = $params['rem_app'];
						
			$sql = "delete from user_apps where uid = '". $this->user->getUID() ."'  and app_id = '" . $app_id . "'";
			$this->connection->exec_query($sql);
						
			$sql = "delete from application_configuration where 
					uid = '". $this->user->getUID() ."'  and app_id = '" . $app_id . "'";
			$this->connection->exec_query($sql);
						
			$sql = "delete from application_associate_permission where    
					uid = '". $this->user->getUID() ."'  and app_id = '" . $app_id . "'";
			
			$this->connection->exec_query($sql);
						
			$this->renderHTML();
		}
		
		
			
		public function renderHTML()
		{			
			
			if($this->html != "")
			{
				echo $this->html;
				return;
			}
			else
			{
				
				/* for no reports. */
				$this->html = '';
							
				$sql = " select apps.app_id,apps.name,apps.description,apps.status,apps.position, 
				 apps.ordering,apps.title,apps.published,apps.params,icon,apps.file_name, AC.profile_link, AC.desktop_icon, AC.top_report  
				 from user_apps  inner join apps using(app_id) left join application_configuration AC using(app_id, uid) 
				 where user_apps.uid = '". $_POST['me_uid'] ."' and apps.type <> 'framework' and status='Active' and apps.type != 'report' ORDER BY apps.title";
		
				$result = $this->connection->exec_query($sql);
				
				$uid = 	$this->user->getUID();
							
				echo "<form action='/gbSuite/apps/process_application.php?app=applications&uid=$uid&action=save_settings&redirect=applications' method='post'>";
				//title="My Applications" 
				echo '<fb:config-table title="Applications" subtitle="Applications available in your profile.">'; 
											
				$n=0;
				?>
				<tr>
						<th></th>
						<th width="50%"></th>
						<th align='center'>Profile</th>
						<th align='center'>Desktop Icons</th>
						<th style="padding-left:5px"><input name='save' type='submit' value='Save' class='button-link'/></th>
						<th></th>
						<th></th>
				</tr>							
				<?
								
				while($row = mysql_fetch_assoc($result))
				{
					$image = $row['icon'];
				
					$image_dir = $_SERVER['PHP_ROOT']."/gbSuite/apps/". $row['name'] ."/$image";
					
					
					if(file_exists($image_dir) && !is_dir($image_dir))
					{
						$image = "/gbSuite/apps/". $row['name'] ."/$image";
					}
					else  
					{
						$image = IMAGES_ROOT."resources/app_default.gif";
					} 
					
					echo "<fb:config-table-item >";
						echo "<td width=10px><img src='$image'></td>";
						echo "<td> <a href='/gbSuite/home.php?app=". $row['name'] ."'>". $row['title'] ."</a>" .
								"&nbsp;&nbsp;<a href='/gbSuite/home.php?app=". $row["name"] ."&action=about'>" . 
								"<span style='font-size:80%'>(about)</span>" .
								"</a></td>";
								
								echo "<td align='center'><input name='profile_". $row['app_id'] ."' 	type='checkbox' ". ($row['profile_link']==1?" checked ":"") ." ><input name='app_". $row['app_id'] ."' type='hidden' value='".$row['app_id']."'></td>";
								echo "<td align='center'><input name='desk_icon_". $row['app_id'] ."'  type='checkbox' ". ($row['desktop_icon']==1?" checked ":"") ."></td>";
								//echo "<td align='center'><input name='top5_". $row['app_id'] ."' 		type='checkbox' ". ($row['top_report']==1?"checked":"") ."></td>";
								 
								$n++;
								
						echo '<td><a href="/gbSuite/home.php?app=applications&action=edit_application_settings&application='.$row['name'].'">Edit Settings</a></td>';
						//echo "<td>". $row['status'] ."</td>";
						echo "<td width=10px><a href='/gbSuite/home.php?app=applications&action=remove&rem_app=". $row['app_id'] ."' style='vertical-align:middle'><img src='". IMAGES_ROOT."resources/delete.gif" ."'></a></td>";
						echo "<td align=left width=1px><a href='/gbSuite/home.php?app=applications&action=remove&rem_app=". $row['app_id'] ."' style='vertical-align:middle'>Remove</a></td>";
					echo "</fb:config-table-item >";
				}
				?>
					<tr>
						<th colspan=4></th>
						<th style="padding-left:5px"><input name='save' type='submit' value='Save' class='button-link'/></th>
						<th></th>
						<th></th>
					</tr>
				<?						
				echo "</fb:config-table>";				
				echo "</form>";
				
				
				
				/* for reports */
				echo "<br>";
				echo "<br>";
							
				 $sql = " select apps.app_id,apps.name,apps.description,apps.status,apps.position, 
				 apps.ordering,apps.title,apps.published,apps.params,icon,apps.file_name, AC.profile_link, AC.desktop_icon, AC.top_report  
				 from user_apps  inner join apps using(app_id) left join application_configuration AC using(app_id, uid) 
				 where user_apps.uid = '". $_POST['me_uid'] ."' and apps.type <> 'framework' and status='Active' and apps.type = 'report' ORDER BY apps.title";
		
				$result = $this->connection->exec_query($sql);
				
				$uid = 	$this->user->getUID();
			
				echo "<form action='/gbSuite/apps/process_application.php?app=applications&uid=$uid&action=save_settings&redirect=applications' method='post'>";
				//title="My Applications" 
				echo '<fb:config-table buttons="false" title="Reports" subtitle="Reports available in your profile.">'; 
					
				$n=0;
				?>
				<tr>
						<th></th>
						<th width="50%"></th>
						<th align='center'>Profile</th>
						<th align='center'>Desktop Icons</th>
						<th align='center'>Top 5</th>
						<th style="padding-left:5px"><input name='save' type='submit' value='Save' class='button-link'/></th>
						<th></th>
						<th></th>
				</tr>							
				<?
						
				while($row = mysql_fetch_assoc($result))
				{
					$image = $row['icon'];
					
					$image_dir = $_SERVER['PHP_ROOT']."/gbSuite/apps/". $row['name'] ."/$image";
					
					
					if(file_exists($image_dir) && !is_dir($image_dir))
					{
						$image = "/gbSuite/apps/". $row['name'] ."/$image";
					}
					else  
					{
						$image = IMAGES_ROOT."resources/app_default.gif";
					} 
					
					echo "<fb:config-table-item >";
						echo "<td width=10px><img src='$image'></td>";
						echo "<td> <a href='/gbSuite/home.php?app=". $row['name'] ."'>". $row['title'] ."</a>" .
								"&nbsp;&nbsp;<a href='/gbSuite/home.php?app=". $row["name"] ."&action=about'>" . 
								"<span style='font-size:80%'>(about)</span>" .
								"</a></td>";
								
								echo "<td align='center'><input name='profile_". $row['app_id'] ."' 	type='checkbox' ". ($row['profile_link']==1?" checked ":"") ." ><input name='app_". $row['app_id'] ."' type='hidden' value='".$row['app_id']."'> </td>";
								echo "<td align='center'><input name='desk_icon_". $row['app_id'] ."'  type='checkbox' ". ($row['desktop_icon']==1?" checked ":"") ."></td>";
								echo "<td align='center'><input name='top5_". $row['app_id'] ."' 		type='checkbox' ". ($row['top_report']==1?"checked":"") ."></td>";
								 
								$n++;
						echo '<td><a href="/gbSuite/home.php?app=applications&action=edit_application_settings&application='.$row['name'].'">Edit Settings</a></td>';
						//echo "<td>". $row['status'] ."</td>";
						echo "<td width=10px><a href='/gbSuite/home.php?app=applications&action=remove&rem_app=". $row['app_id'] ."' style='vertical-align:middle'><img src='". IMAGES_ROOT."resources/delete.gif" ."'></a></td>";
						echo "<td align=left width=1px><a href='/gbSuite/home.php?app=applications&action=remove&rem_app=". $row['app_id'] ."' style='vertical-align:middle'>Remove</a></td>";
					echo "</fb:config-table-item >";
				}
				?>
					<tr>
						<th colspan=5></th>
						<th style="padding-left:5px"><input name='save' type='submit' value='Save' class='button-link'/></th>
						<th></th>
						<th></th>
					</tr>
				<?				
				echo "</fb:config-table>";		
				echo "</form>";
				}				
				}
				
				function save_settings()
				{
					$apps = array();
								
					foreach($_POST as $key => $value)
					{
						if(strpos($key,"app_") !== false)	
						{									
							$apps[] = $value;
						}
					}
			
					foreach($apps as $app)
					{
						if(isset($_POST['profile_'.$app]))
							$profile = 1;
						else
							$profile = 0;
							
						if(isset($_POST['desk_icon_'.$app]))
							$desk_icon = 1;
						else
							$desk_icon = 0;
							
						if(isset($_POST['top5_'.$app]))
							$top5 = 1;
						else
							$top5 = 0;
							
						$query= "select count(id) from application_configuration where app_id = '$app' and uid = '".$this->user->getUID()."'";
						$count = $this->connection->get_value($query);
						$sql = "select default_visibility from apps where app_id='$app'";
						$visibility = $this->connection->get_value($sql);
						
						if($count == 0)
						{			 	
							 $query ="insert into application_configuration(uid, app_id, visibility,profile_link, desktop_icon, top_report)".
							 		"VALUES ('".$this->user->getUID()."', '".$app."', '$visibility', '$profile', '$desk_icon', '$top5')";
						}
						else
							$query = "UPDATE application_configuration SET profile_link ='$profile', desktop_icon = '$desk_icon', top_report ='$top5' WHERE uid = '".$this->user->getUID()."' AND app_id = '$app'";
													
						$this->connection->exec_query($query);												
				}
		}
		
		static function notif_apps($title, $uid)
		{
			$sql = "INSERT INTO notification(title, message, created, `type`, `read`, sender, recepient) VALUES ('".$title." Installed!', 'The application ".$title." was installed in your profile.', now(),'information' , 0, 0, '$uid')";
			$this->connection->exec_query($sql);
		}				
}
?>