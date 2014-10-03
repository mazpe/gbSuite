<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	 
    class News extends Application
	{
		public function __construct()
		{
			$this->appId = 1;  
		}
		
		public function renderHTML()
		{	
			
			if($this->html <> "")
			{
				echo $this->html;
				return;
			}
			
			$uid = $this->user->getCurrentUID();		
				
			$this->html = '
				<fb:news-box  uid="'. $uid  .'">
				</fb:news-box>
			';
			
			echo $this->html;
		}
	}
?>
