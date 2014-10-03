<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class ApplicationLogo extends Application
	{
		public function __construct()
		{
			
		}
		
		public function renderHTML()
		{
			$this->html = '<a href="/gbSuite/home.php"><fb:logo /></a>';
						   
			echo $this->html;
		}
	}
?>
