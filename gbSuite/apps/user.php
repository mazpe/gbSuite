<?php
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/util/connection.php';
	
    class User	
	{		
		private $name = null;
		
		private $attributes = null;
		private $friendAttriutes = null;
		private $currentProfileAttributes = null;
		
		private $friendUID = null;
		private $currentProfileUID = null;
		
		private $connection = null;
		
		public function __construct($attributes = null, $uid = null, $connection = null)
		{
			if($attributes != null)
				$this->attributes = $attributes;
			
			if($connection != null)				
				$this->setConnection($connection);
			else
				$this->connection = new Connection();
			
			if($attributes == null && $uid != null)
			{
				/**Read the information of the current user**/
				$query = "SELECT * FROM info WHERE uid = '".$uid."' ";
												 
				$this->attributes = $connection->get_row($query);	
			}	
		}
		
		public function getManagerInfo()
		{
			
			$sql = "select * from info where uid='" . $this->attributes['manager_uid'] . "'";
			
			return $this->connection->get_row($sql);
		}
		
		public function setAttributes($attributes)
		{
			$this->attributes = $attributes;	
		}
		
		public function getUID()
		{
			return $this->attributes['uid'];	
		}
				
		/**Connection must be setup first**/	
		public function setFriendUID($friendUID)
		{
			$this->friendUID = $friendUID;
			
			if($this->connection != null && isset($friendUID))
				$this->friendAttributes = $this->connection->get_row("SELECT * FROM info WHERE uid = '".$friendUID."'");				
		}
		
		public function getFriendUID()
		{
			return $this->friendUID;	
		}
		
		/**friendUID can be used to refer to the current profile when the user is not viewing the 
		 * associates but when is viewing the associates page of any user the friendUID is the associates to add
		 * and the currentProfileUID is the owner of that associates. 
		 * @return 
		 * @param $currentProfileUID Object
		 */
		public function setCurrentProfileUID($currentProfileUID)
		{			
			$this->currentProfileUID = $currentProfileUID;
			
			if($this->connection != null && isset($currentProfileUID))
				$this->currentProfileAttributes = $this->connection->get_row("SELECT * FROM info WHERE uid = '".$currentProfileUID."'");	
		}
		
		public function getCurrentProfileUID()
		{
			if($this->currentProfileUID != null)
				return $this->currentProfileUID;
			else
				if($this->getFriendUID() != null)
					return $this->getFriendUID();
				else
					return $this->getUID();
		}

		public function getCurrentProfileAttribute($attributeName)
		{
			if($this->currentProfileAttribute == null)
				$this->currentProfileAttributes = $this->connection->get_row("SELECT * FROM info WHERE uid = '".$this->getCurrentProfileUID()."'");
			
			return $this->currentProfileAttributes[$attributeName];
		}
				
		public function getCurrentUID()
		{
			if($this->friendUID != null)
				return $this->friendUID;
			else
				if($this->currentProfileUID != null)
					return $this->currentProfileUID;
				else
					return $this->attributes['uid'];	
		}
		
		public function getAttributes()
		{
			return $this->attributes;	
		}
		
		public function getFriendAttributes()
		{
			return $this->friendAttributes;	
		}
		
		public function getCurrentAttributes()
		{
			if(isset($this->friendUID))
				return $this->friendAttributes;
			else
				return $this->attributes;	
		}
		
		public function setName($name)
		{
			$this->attributes['name'];
		}
		
		public function getName()
		{
			return $this->attributes['name'];
		}
		
		public function getTitle()
		{
			return $this->attributes['title'];	
		}
		
		public function setConnection($connection)
		{
			$this->connection = $connection;	
		}
		
		public function getConnection()
		{
			return $this->connection;			
		}
		
		public function getAssociates($title = null)
		{
			if($this->getFriendUID() != null) //Show the friend information
			{
				$sql = "SELECT I.uid, I.title, agency_id, name, !ISNULL(T.user2) as added, (I.uid = '".$this->getUID()."') AS me ".
							"FROM info I INNER JOIN friend F ".
								"ON I.uid = F.user2 LEFT JOIN (SELECT user2, I.uid ".
									"FROM info I INNER JOIN friend F ".
										"ON I.uid = F.user1 ".
											"WHERE I.uid = '".$this->getUID()."') T ". 
								"ON F.user2 = T.user2 ".
							" WHERE I.email not like '%@gbsuite.com' and F.user1 = '".$this->getFriendUID()."' AND I.active = 1 ".($title != null && $title != "" ? "AND I.title = '$title'" : "")
							." ORDER by I.name";
			}		
			else
				$sql = "SELECT uid, I.title, agency_id, name, 1 as added FROM info I INNER JOIN friend F ON I.uid = F.user2 WHERE I.email not like '%@gbsuite.com' and  F.user1 = '".$this->getUID()."' AND I.active = 1 ".($title != null && $title != "" ? "AND I.title = '$title'" : "")." ORDER by I.name";
			
			$data = $this->connection->exec_query($sql);
			
			$associates = array();
		
			while($row = mysql_fetch_assoc($data))
			{	
				$associates[$row['uid']] = $row;	
			}
			
			return $associates;
		}
		
		public function isFriend($friendUID)
		{
			if($this->getUID() != $this->getCurrentProfileUID())
			{
				$query = "SELECT COUNT(user1) AS Count FROM friend WHERE (user1 = '".$this->getUID()."' AND user2 = '".$this->getCurrentProfileUID()."') OR (user2 = '".$this->getUID()."' AND user1 = '".$this->getCurrentProfileUID()."')" ;
				
				$count = $this->connection->get_value($query);
				
				return $count;								
			}
			else
				return 1;			
		}
		
		public function areFromSameDepartment($friendUID)
		{
			$department1 = $this->attributes['department'];
			$department2 = $this->connection->get_value("SELECT department FROM info WHERE uid = '".$friendUID."'");
			
			$array1 = explode("/", $department1);
			
			for($i = 0; $i < count($array1); $i++)
			{
				if(strpos($department2, $array1[$i]) === false)
				{}
				else
					return 1;
			}	
			
			return 0;		
		}
	}
?>
