<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class ActionStatus extends Application
	{
		public function __construct()
		{
			$this->appId = 29;
		}
		
		public function gotMessage()
		{
			$query = "SELECT COUNT(message) AS counter FROM action_message WHERE uid = '".$this->user->getUID()."' AND action_status > 0";
			
			$value = $this->connection->get_value($query);
			
			return $value;
		}
		
		public function renderHTML()
		{
			$query = "SELECT message FROM action_message WHERE uid = '".$this->user->getUID()."' AND action_status > 0";
			
			$row = $this->connection->get_row($query);
			
			$query = "UPDATE action_message SET action_status = 0 WHERE uid = '".$this->user->getUID()."'";
			$this->connection->exec_query($query);
			
			echo '<div class="action-status-message">'.$row['message'].'</div>';
		}
	}
?>
