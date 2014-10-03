<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class AgencyLogo extends Application
	{
		public function __construct()
		{
			
		}
		
		public function renderHTML()
		{
			$this->html = '<fb:agency-logo agency_id="ag01" />';
						   
			echo $this->html;
		}
	}
?>
