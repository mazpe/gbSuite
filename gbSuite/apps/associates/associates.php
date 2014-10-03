<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class Associates extends Application
	{
		private $userUID = null;
		
		public function __construct()
		{
			$this->appId = 6;	
		}
		
		/**This function add an user as associate to the current user**/
		public function add($params)
		{
			$uid = $this->user->getUID();
			$fuid = $params['fuid'];//$this->user->getFriendUID();
			//$currentProfileUID = $this->user->getCurrentProfileUID();
			
			$sql = "INSERT INTO friend (user1, user2) VALUES ('".$uid."', '".$fuid."')";							
			$this->connection->exec_query($sql);
			
			$sql = "INSERT INTO friend (user2, user1) VALUES ('".$uid."', '".$fuid."')";							
			$this->connection->exec_query($sql);
			
			/*$html = "";
			$html .= '<label>'.$this->user->getName().' want to add you as associate.</label><br/>';
			$html .= '<input class="link-button" type="button" value="Accept" onclick=document.setLocation("/gbSuite/apps/process_application.php?app=associates&action=accept_friend&uid='.$fuid.'&fuid='.$uid.'&redirect=notification") />';
			$html .= '<input class="link-button" type="button" value="Deny" onclick=readNotification() />';
			
			$sql = "INSERT INTO notification (id, sender, recepient, title, message, created, type, `read`) 
					VALUES (0, '".$uid."', '".$fuid."', 'Associate request', '".$html."', now(), 'request', 0)";
							
			$this->connection->exec_query($sql);
			*/
			echo '{"message":"Associate added"}';
			
			//header("Location: /gbSuite/home.php?app=associates&uid=".$currentProfileUID);
		}
		
		public function accept_friend($params)
		{
			$uid = $params['uid'];
			$fuid = $params['fuid'];
			
			$sql = "INSERT INTO friend (user1, user2) VALUES ('".$uid."', '".$fuid."')";							
			$this->connection->exec_query($sql);
			
			$sql = "INSERT INTO friend (user2, user1) VALUES ('".$uid."', '".$fuid."')";							
			$this->connection->exec_query($sql);
			
			/**Notification**/
			$title = 'You and '.$this->user->getName().' are now associates.';
			$message = $this->user->getName().' accepted your request.'; 
			
			$sql = "INSERT INTO notification (id, sender, recepient, title, message, created, type, `read`) 
					VALUES (0, '".$uid."', '".$fuid."', '".$title."', '".$message."', now(), 'information', 0)";
												
			$this->connection->exec_query($sql);
				
			$sql = "SELECT name FROM info WHERE uid = '".$fuid."'";
				
			$row = $this->connection->get_row($sql);
					
			$friendName = $row['name'];
					
			//The user add a friend
			$sql = "INSERT INTO news (uid, type, value, date, status) ".
					"SELECT uid, 'associate', CONCAT('<fb:name uid=', uid, ' />', ' added ', '<fb:name uid=".$fuid." />', ' as associate.'), now(), 0 ".
					"FROM info ".
					"WHERE uid = '".$uid."'";
				
			$this->connection->exec_query($sql);
			
			//The user add a friend
			/*$sql = "INSERT INTO news (uid, type, value, date, status) ".
					"SELECT '".$fuid."', 'associate', CONCAT($friendName, ' added ', name, ' as associate.'), now(), 0 ".
					"FROM info ".
					"WHERE uid = '".$fuid."'";*/
			
			$sql = "INSERT INTO news (uid, type, value, date, status) ".
					"SELECT '".$fuid."', 'associate', CONCAT('<fb:name uid=".$fuid." />', ' added ', '<fb:name uid=', '".$uid."', ' />', ' as associate.'), now(), 0 ".
					"FROM info ".
					"WHERE uid = '".$fuid."'";
					
			$this->connection->exec_query($sql);
			
			if(isset($params['notification-id']))
			{
				$sql = "UPDATE notification SET `read` = 1 WHERE id = ".$params['notification-id'];
				
				$this->connection->exec_query($sql);
			}
				
			//header("Location: /gbSuite/home.php?app=associates&uid=".$currentProfileUID);
		}
		
		/*public function accept_friend($params)
		{
			$uid = $this->user->getUID();
			$fuid = $this->user->getFriendUID();
			$currentProfileUID = $this->user->getCurrentProfileUID();
			
			$sql = "INSERT INTO friend (user1, user2) VALUES ('".$fuid."', '".$uid."')";
							
			$this->connection->exec_query($sql);
				
			$sql = "INSERT INTO friend (user2, user1) VALUES ('".$fuid."', '".$uid."')";
						
			$this->connection->exec_query($sql);
					
			$sql = "SELECT name FROM info WHERE uid = '".$fuid."'";
				
			$row = $this->connection->get_row($sql);
					
			$friendName = $row['name'];
					
			//The user add a friend
			$sql = "INSERT INTO news (uid, type, value, date, status) ".
					"SELECT uid, 'associate', CONCAT(name, ' added $friendName as associate.'), now(), 0 ".
					"FROM info ".
					"WHERE uid = '".$uid."'";
				
			$this->connection->exec_query($sql);
			
			header("Location: /gbSuite/home.php?app=associates&uid=".$currentProfileUID);
		}*/
				
		public function remove($params)
		{
			$uid = $this->user->getUID();
			$fuid = $params['fuid'];//$this->user->getFriendUID();
			$currentProfileUID = $this->user->getCurrentProfileUID();
			
			$sql = "DELETE FROM friend WHERE (user1 = '".$fuid."' AND user2 = '".$uid."') OR (user2 = '".$fuid."' AND user1 = '".$uid."')";
			
			$this->connection->exec_query($sql);
				
			$sql = "SELECT name FROM info WHERE uid = '".$fuid."'";
				
			$row = $this->connection->get_row($sql);
					
			$friendName = $row['name'];
					
			//The user add a friend
			$sql = "INSERT INTO news (uid, type, value, date, status) ".
					"SELECT uid, 'rm_associate', CONCAT(name, ' removed $friendName from (his/her) list of associates.'), now(), 0 ".
					"FROM info ".
					"WHERE uid = '".$uid."'";
				
			//$this->connection->exec_query($sql);
			
			echo '{"message":"removed"}';
			
//			/header("Location: /gbSuite/home.php?app=associates&uid=".$currentProfileUID);
		}
		
		public function setUserUID($userUID)
		{
			$this->userUID = $userUID;
		}
		
		public function renderHTML()
		{
			$html = "";
			
			$html = '<fb:associate-section >					
						<table align=center width=100%>';
			
			$uid = $this->user->getUID();
			$userUID = $this->user->getCurrentUID();
			$currentUID = $this->user->getCurrentProfileUID();
						
			$currentUserTitle = $this->user->getTitle();
			
			$query = "SELECT DISTINCT title FROM info ORDER by title";
			
			$rs = $this->connection->exec_query($query);
			 	
			if($rs !==  false)
			while($row = mysql_fetch_assoc($rs))
			{			
				$cont = 0;
			
				$associates = $this->user->getAssociates($row['title']);
					
				if(count($associates) > 0)
				{
					$html .= '<tr bgcolor="#F2F2F2"><td colspan=4 align=center style="border-style:solid;border-width:1px;">'.$row['title'].'</td></tr>';
					
					foreach($associates as $associate)
					{
						if($cont == 0)
							 $html .= "<tr>";
	
						$cont++;
						
						$html .= 	'<td class="" align=center>			        			  	
			        						<fb:associate user_profile='.($currentUID == $uid).' associate_title="'.$associate['title'].'" uid="'.$userUID.'" fuid="'.$associate['uid'].'" added="'.$associate['added'].'" name="'.$associate['name'].'" me = "'.$associate['me'].'" title="'.$currentUserTitle.'" width="60px" height="60px" >							        					
			        						</fb:associate>										
			        				</td>';
						
						
						/*$html .= 	'<td class="" align=center>
			        			  		<p>
			        						<fb:associate user_profile='.($currentUID == $uid).' associate_title="'.$associate['title'].'" uid="'.$userUID.'" fuid="'.$associate['uid'].'" added="'.$associate['added'].'" name="'.$associate['name'].'" me = "'.$associate['me'].'" title="'.$currentUserTitle.'" width="60px" height="60px" >							        					
			        						</fb:associate>
										</p>
			        				</td>';
						*/
						if($cont == 4)
						{
							$cont = 0;
							
							$html .= "</tr>";
						}
					}
					
					if($cont != 4)
						$html .= "</tr>";
				}
				//else
					//$html .= '<div class="non-associate">No associates to display</div>';
			}
			
		    $html .= 	'</table></fb:associate-section>';	
			
							
			echo $html;
		}
	}
?>
