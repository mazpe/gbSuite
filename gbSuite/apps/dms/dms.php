<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class DMS extends Application
	{
		public function __construct()
		{
			
		}
		
		public function renderHTML()
		{
			$this->html = '<div class="application-content">DMS</div>';
			
			echo $this->html;
		}
	}
?>
