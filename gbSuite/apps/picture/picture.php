<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
		
    class Picture extends Application
	{
		
		public function __construct()
		{
			parent::__construct();			
						
		}
			
		
		public function renderHTML()
		{	
			
			$uid = "";
			if($this->user->getFriendUID()!="")
			{
				$uid = $this->user->getFriendUID();
			}
			else
			{
				$uid = $this->user->getUID();
			}

			$this->html = "<fb:picture uid='". $uid ."'/>";
			echo $this->html;
		}
		
		public function edit()
		{

			$this->html = "<form action='/gbSuite/apps/picture/process_image.php' enctype='multipart/form-data' method=post>" .
						  "<table ><tr><td>" .
						  "<input type=hidden name='uid' value='". $this->user->getCurrentUID()  ."'/>" .
						  "<input type='file' name='profile_image'>" .
						  "</td></tr>" .
						  "<tr><td><input type=submit value='Save' name='Save'></td></tr></table>" .
						  "</form>";
			
			echo $this->html;
						
		}
	}
?>
