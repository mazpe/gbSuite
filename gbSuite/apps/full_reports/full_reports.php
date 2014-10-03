<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class FullReport extends Application
	{
		public function __construct()
		{
			
		}
		
		public function renderHTML()
		{
			
			include_once($_SERVER['PHP_ROOT']."/gbSuite/report_script.php");
						   
			//echo $this->html;
		}
	}
?>
