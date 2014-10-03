<?php
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/util/connection.php';
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/apps/user.php';
	
    abstract class Application 
	{
		protected $html = null;
		protected $uid = null;
		protected $connection = null;
		protected $attributes = null;
		protected $name = null;
		protected $appId = null;
			
		protected $editSettingsHTMLCode = null;
				
		protected $user = null;
		protected $userSettings = null;
		protected $query = "";
					
		public function __construct($user = null, $connection = null, $uid = null)
		{
			if($connection != null)
				$this->connection = $connection;
			else	
				$this->connection = new Connection();
			
			if($user == null && $uid != null)	
				$this->user = new User(null, $uid, $this->connection);			
		}	
		 
		
		/*
		 * This method is create to view if the application is installed in the 
		 * current profile.
		 */
		
		public function isInstalled()
		{
			$uid = $this->user->getUID();
			
			$sql = "select count(*) from user_apps where uid='$uid' and app_id='".$this->appId."'";
			
			$count = $this->connection->get_value($sql);
			
			//echo $sql;
			
			if($count > 0)
				return true;
			else 
				return false;
		}
		
		public function notInstalledMessage()
		{
			return "You dont have the application <strong>" . $this->attributes['title'] . "</strong> installed, to install click <a href='/gbSuite/home.php?app=applications&action=view_more'>here</a>";
		}
		
		public function setAttributes($attributes)
		{
			$this->attributes = $attributes;	
		}
		
		public function getAttribute($attributeName)
		{
			return $this->attributes[$attributeName];	
		}
		
		public function getUserSettings()
		{			
			$this->query = "SELECT A.app_id, A.name, A.description, A.status, A.position, A.ordering, A.title, A.published, A.params, A.image, A.icon, A.default_titles, A.show_title, A.auth, A.class_name, A.file_name, A.type, ifnull(AC.visibility, A.default_visibility) AS visibility, AC.profile_link, AC.desktop_icon, AC.top_report   
																FROM info I CROSS JOIN apps A LEFT JOIN application_configuration AC
																	ON I.uid = AC.uid AND A.app_id = AC.app_id 
																WHERE (I.uid = '".$this->user->getUID()."') AND A.app_id = ".$this->appId;

			$this->userSettings = $this->connection->get_row($this->query);
			
			//get the settins for the current user.
			return $this->userSettings;
		}
		
		public function setConnection($connection)
		{
			$this->connection = $connection;
		}
		
		public function setUser($user)
		{
			$this->user = $user;
		}
		
		public function getName()
		{
			return $this->name;			
		}
		
		public abstract function renderHTML();
		
		//The order correspond with the order that comes the appId items.
		public function setApplicationAssociatePermission($type, $uid, $appId, $uids)		
		{			
			$connection = new Connection();
			
			$query = "SELECT COUNT(id) AS rowCount FROM application_associate_permission WHERE uid = '$uid' AND app_id = $appId";
			
			$count = $connection->get_value($query);
			
			if($count > 0)
			{
				if($type == 'allowed')
					$query = "UPDATE application_associate_permission SET allowed = '$uids', denied = '' WHERE uid = '$uid' AND app_id = $appId";
				else
					if($type == 'denied')
						$query = "UPDATE application_associate_permission SET denied = '$uids', allowed = '' WHERE uid = '$uid' AND app_id = $appId";				
			}				
			else
			{
				if($type == 'denied')
					$query = "INSERT INTO application_associate_permission (id, uid, app_id, allowed, denied) 
								VALUES (0, $uid, $appId, '', '$uids')";
				else
					if($type == 'allowed')
						$query = "INSERT INTO application_associate_permission (id, uid, app_id, allowed, denied) 
									VALUES (0, $uid, $appId, '$uids', '')";								
			}
				
						
			if($query != "")
				$connection->exec_query($query);
			
			echo '{"query":"'.$query.' type='.$type.'"}';
		}
		
		/**
		 * @return 
		 * @param $params Object
		 */
		public function save_settings($params)
		{		
			//print_r($params);
			
			$query = "SELECT COUNT(id) FROM application_configuration WHERE app_id = ".$params['app_id']." AND uid = '".$params['uid']."'";
			
			$count = $this->connection->get_value($query);

			$profileLink = 0;
			$desktopIcon = 0;
			$topReport = 0;
			
			if(isset($params['profile_link']))
				$profileLink = 1;
			
			if(isset($params['desktop_icon']))
				$desktopIcon = 1;
			
			if(isset($params['top_report']))
				$topReport = 1;
								
			if($count == 0)
				$query = "INSERT INTO application_configuration (id, uid, app_id, visibility, profile_link, desktop_icon, top_report) ". 
						 "VALUES (0, '".$this->user->getUID()."', ".$this->appId.", '".$params['visibility']."', ".$profileLink.", $desktopIcon, $topReport)";			
			else
				$query = "UPDATE application_configuration SET visibility = '".$params['visibility']."', profile_link = ".$profileLink.", desktop_icon = $desktopIcon, top_report = $topReport WHERE uid = '".$params['uid']."' AND app_id = ".$params['app_id'];

			$this->connection->exec_query($query);
		}
		
		public function editSettings($params)
		{
			//<tr><td>'.$this->query." appid = ".$this->appId.'</td></tr>
			$settings = $this->getUserSettings();
			
			$this->editSettingsHTMLCode = '<form method="post" action=/gbSuite/apps/process_application.php?app='.$settings['name'].'&action=save_settings&uid='.$this->user->getUID().'>
			<table id="edit_settings_dialog" class="edit-setting">
			<input id="application-id" type="hidden" name="app_id" value="'.$this->appId.'"/>
			<input id="application-name" type="hidden" name="app" value="'.$settings['name'].'"/>
				<tr>
    				<td >					
						<div class="edit-setting-title">Edit Setting for '.$settings['title'].'</div>
  						<div class="dialog-body">
			            	<table class="dialog-table">
			                	<tr>
			                    	<td class="label-item">
			                        	Profile Box:
			                        </td>
			                        <td>
			                        	<div class="question">Who can see this?</div>
										<div class="sumary-simple">
			                                <select id="visibility-list" name="visibility" class="list" onchange="loadApplicationAssociatePermission(this)">
			                                    <option id="1" '.($settings['visibility'] != 'My Associates' ? '' : 'selected="true"').' value="My Associates">My Associates</option>
			                                    <option id="2" '.($settings['visibility'] != 'Every one in department' ? '' : 'selected="true"').' value="Every one in department">Every one in department</option>
												<option id="3" '.($settings['visibility'] != 'Managers'? '' : 'selected="true"').' value="Managers">Managers</option>
												<option id="4" '.($settings['visibility'] != 'Everyone'? '' : 'selected="true"').' value="Everyone">Everyone</option>
												<option id="5" '.($settings['visibility'] != 'Some associates'? '' : 'selected="true"').' value="Some associates">Some associates</option>
												<option id="6" '.($settings['visibility'] != 'Except these peoples'? '' : 'selected="true"').' value="Except these peoples">Except these peoples</option>
			                                    <option id="7" '.($settings['visibility'] != 'Only me'? '' : 'selected="true"').' value="Only me">Only me</option>
			                                </select>                        
			                        	</div>			                            
			                        </td>
			                    </tr>';
								
				                    $this->editSettingsHTMLCode .= '<tr>
											<td class="label-item">      
												Main section:
					                    	</td>
					                        <td>                    
					                    		<input id="left-menu" name="profile_link" type="checkbox" '.(($settings['profile_link'] == 1) ? checked : unchecked).' />
					                            <label class="label">Show in profile</label><br/>';
												
										if($settings['position'] == 'MAIN')		
											$this->editSettingsHTMLCode .= '<input id="left-menu" name="desktop_icon" type="checkbox" '.(($settings['desktop_icon'] == 1) ? checked : unchecked).' />
					                            <label class="label">Add to desktop icons</label>';
												
										if($settings['type'] == 'report')
											$this->editSettingsHTMLCode .=	'<br/>
												<input id="left-menu" name="top_report" type="checkbox" '.(($settings['top_report'] == 1) ? checked : unchecked).' />
					                            <label class="label">Add to my top reports</label>';
														
					               	$this->editSettingsHTMLCode .= '</td></tr>';
								
								
												                    
			                $this->editSettingsHTMLCode .= '</table>
	                		<div class="buttons">
	            				<input class="button-link" name="button-save" type="submit" value="Save Changes" />
	                            <input class="button-link" type="button" onclick=document.setLocation("/gbSuite/home.php?app=applications") value="Go back"/>							
	            			</div>
            			</div>			    
					</td>
				</tr>				
			</table><div id="application-associates-permission" class="associates_application">';
			
			if($settings['visibility'] == 'Some associates')
				$this->editSettingsHTMLCode .= $this->getCodeByType('allowed');
			else
				if($settings['visibility'] == 'Except these peoples')
					$this->editSettingsHTMLCode .= $this->getCodeByType('denied');
						
			$this->editSettingsHTMLCode .= '</div></form>';
			
			return $this->editSettingsHTMLCode;
						
			//if($this->editSettingsHTMLCode != null)
			//	$this->editSettingsHTMLCode .= "";
			
			//echo '{"content":"'.$this->editSettingsHTMLCode.'"}';
		}		
		
		public function associates_list($params)
		{
			//$this->appId = $params['app_id'];
			
			echo '{"content":"'.$this->getCodeByType($params['type']).'"}';		
		}	
		
		public function getCodeByType($type)
		{
			$html = "";
			
			if($type == 'allowed')
				$html .= 
						'<table>
							<tr><th>Availables</th><th></th><th>Allowed</th></tr>
							<tr>						
								<td>'.$this->availableAssociatesVsAllowed().'</td>
								<td>
									<input type="button" value=">>" onclick="addItems()" />
									<br/>
								    <input type="button" value="<<" onclick="removeItems()" />
								</td>
								<td>'.$this->allowedAssociates().'</td>
							</tr>	
						</table>';
			else
				if($type == 'denied')
				$html .= 
						'<table>
							<tr><th>Availables</th><th></th><th>Denied</th></tr>
							<tr>						
								<td>'.$this->availableAssociatesVsDenied().'</td>
								<td>
									<input type="button" value=">>" onclick="addItems()" />
									<br/>
								    <input type="button" value="<<" onclick="removeItems()" />
								</td>
								<td>'.$this->deniedAssociates().'</td>
							</tr>	
						</table>';
						
			return $html;
		}
		
		/*protected function createList($query, $title, $listId, $listItemId, $listClassName, $listItemClassName)
		{
			echo $query;
			
			$html = "";
			$rs = $this->connection->exec_query($query);
			
			$html = "";
			$count = 0;
			
			//associates-list-x
			$html = '<ul id="'.$listId.'" class="'.$listClassName.'" name="'.$listClassName.'">';
			
			while($row = mysql_fetch_assoc($rs))
			{
				//1240077
				$html .= '<li id="'.$row['user2'].'_mylistitem" class="'.$listItemClassName.'">'.$row['name'].'</li>';	
			}
			
			$html .= "</ul>";
			
			return $html;
		}*/
		
		protected function createList($query, $title, $listId, $listItemId, $listClassName, $listItemClassName)
		{
			$html = "";
			
			$rs = $this->connection->exec_query($query);
			
			$html = "";
			$count = 0;
			
			//associates-list-x
			$html = '<select MULTIPLE id="'.$listId.'" class="'.$listClassName.'" name="'.$listClassName.'">';
			
			while($row = mysql_fetch_assoc($rs))
			{
				//1240077
				$html .= '<option value="'.$row['user2'].'" id="'.$row['user2'].'_mylistitem" class="'.$listItemClassName.'">'.$row['name'].'</li>';	
			}
			
			$html .= "</select>";
			
			return $html;
		}
		
		protected function availableAssociatesVsAllowed()
		{
			/*$query = "SELECT F.user1, F.user2, A.uid, allowed, name 
						FROM friend F INNER JOIN application_associate_permission A 
						ON A.allowed NOT LIKE CONCAT('%', F.user2, '%') 
						INNER JOIN info I 
						ON F.user2 = I.uid 
						WHERE F.user1 = A.uid AND F.user1 = '".$this->user->getUID()."'";*/
						
			$query = "SELECT F.user2, I.name
					  	FROM info I INNER JOIN friend F 
					  		ON I.uid = F.user2 CROSS JOIN apps A LEFT JOIN application_associate_permission AC
					  		ON A.app_id = AC.app_id AND AC.uid = F.user1
						WHERE F.user1 = '".$this->user->getUID()."' AND IFNULL(AC.allowed,'') NOT LIKE CONCAT('%', F.user2, '%')  AND A.app_id = ".$this->appId;  
									
			//associates-list-1" class="associates-list
			return $this->createList($query, "Available Associates", 'associates-list1', null, "associates-list", "associates-list-item1");						
		}
		

		protected function availableAssociatesVsDenied()
		{
			/*$query = "SELECT F.user1, F.user2, A.uid, denied, name
						FROM friend F INNER JOIN application_associate_permission A
						ON A.denied NOT LIKE CONCAT('%', F.user2, '%')
						INNER JOIN info I 
						ON F.user2 = I.uid 
						WHERE F.user1 = A.uid AND F.user1 = '".$this->user->getUID()."'";*/
			
			$query = "SELECT F.user2, I.name
					  	FROM info I INNER JOIN friend F 
					  		ON I.uid = F.user2 CROSS JOIN apps A LEFT JOIN application_associate_permission AC
					  		ON A.app_id = AC.app_id AND AC.uid = F.user1
						WHERE F.user1 = '".$this->user->getUID()."' AND IFNULL(AC.denied,'') NOT LIKE CONCAT('%', F.user2, '%')  AND A.app_id = ".$this->appId;			
						
			return $this->createList($query, "Available Associates", 'associates-list1', null, "associates-list", "associates-list-item1"); 
		}
		
		protected function allowedAssociates()
		{
			/*$query = "SELECT F.user1, F.user2, A.uid, allowed, name
						FROM friend F INNER JOIN application_associate_permission A
						ON A.allowed LIKE CONCAT('%', F.user2, '%')
						INNER JOIN info I
						ON F.user2 = I.uid
						WHERE F.user1 = A.uid AND F.user1 = '".$this->user->getUID()."'";*/
			
			$query = "SELECT F.user2, I.name
					  	FROM info I INNER JOIN friend F 
					  		ON I.uid = F.user2 CROSS JOIN apps A LEFT JOIN application_associate_permission AC
					  		ON A.app_id = AC.app_id AND AC.uid = F.user1
						WHERE F.user1 = '".$this->user->getUID()."' AND IFNULL(AC.allowed,'') LIKE CONCAT('%', F.user2, '%')  AND A.app_id = ".$this->appId;
									
			//associates-list-1" class="associates-list
			return $this->createList($query, "Allowed Associates", 'associates-list2', null, "associates-list", "associates-list-item2");
		}
		
		public function about($params)
		{
			
			$this->html .= $this->attributes['description']; 
		}  
				
		protected function deniedAssociates()
		{
			/*$query = "SELECT F.user1, F.user2, A.uid, denied, name
						FROM friend F INNER JOIN application_associate_permission A
						ON F.user2 LIKE CONCAT('%', A.denied, '%')
						INNER JOIN info I
						ON F.user2 = I.uid
						WHERE F.user1 = A.uid AND F.user1 = '".$this->user->getUID()."'";*/
			
			$query = "SELECT F.user2, I.name
					  	FROM info I INNER JOIN friend F 
					  		ON I.uid = F.user2 CROSS JOIN apps A LEFT JOIN application_associate_permission AC
					  		ON A.app_id = AC.app_id AND AC.uid = F.user1
						WHERE F.user1 = '".$this->user->getUID()."' AND IFNULL(AC.denied,'') LIKE CONCAT('%', F.user2, '%')  AND A.app_id = ".$this->appId;
															
			return $this->createList($query, "Denied Associates", 'associates-list2', null, "associates-list", "associates-list-item2");
		}
		
		public function getBaseUrl($app=null)
		{
			if($app == null)
				return "/gbSuite/apps/".$this->attributes['file_name']."/";
			else
				return "/gbSuite/apps/$app/";
		}
	}	
?>
