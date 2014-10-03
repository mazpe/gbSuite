<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class ProfileLinks extends Application
	{
		public function __construct()
		{
			parent::__construct();
			
			$this->appId = 12;
		}
		
		public function renderHTML()
		{
			
			$uid = $this->user->getCurrentUID();
			
			if($this->user->getCurrentUID() == $this->user->getUID())
			{
				$name = " My ";
			}
			else
			{ 
				$name = $this->user->getCurrentProfileAttribute("first_name")."'s ";
			}
					
			$this->html = '<fb:profile-links uid="'. $uid .'" name="'. $name .'"  />';

			echo $this->html;
		}		
	}
?>
