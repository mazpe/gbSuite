<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class Support extends Application
	{
		public function __construct()
		{
				
		} 
		
		public function renderHTML()
		{
			?>
			<div class='support-content' style='padding-left:30px;'>
			<div>gbSuite Customer Support</div>
			<div>For support, please email us at </div>
			<div><a href='mailto:support@gbsuite.com'>support@gbsuite.com</a> or call 305-710-1642</div>
			</div>
			<?	
		}
	}
?>
