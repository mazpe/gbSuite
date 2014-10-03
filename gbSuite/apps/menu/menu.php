<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class Menu extends Application
	{
		public function __construct()
		{
			parent::__construct();
		}
		
		public function renderHTML()
		{
			$uid = $_POST['me_uid'];
	           
	         $sql = "select * from info where uid='$uid'";
	           
			$user_info = $this->connection->get_row($sql);
				
				/*<a href='/gbSuite/home.php?app=dms' class='menu-button' ><div class='button'>DMS</div></a>*/		
			
			/*<a href='/gbSuite/home.php?app=profile' class='menu-button'><div class='button'>". $user_info['name'] ."</div></a>*/
			
			$this->html = 
			"<div class='menu'>
			   				           
	           <a href='/gbSuite/home.php?app=profile' class='menu-button'><div class='button'>My Profile</div></a>
	           <a href='/gbSuite/home.php?app=applications' class='menu-button'><div class='button'>Applications</div></a>";
	                     	           
			   if($user_info['title'] == "Administrator")
			   {
			   	 $this->html .= 
			   			"<a href='/gbSuite/home.php?app=profile&action=add' class='menu-button'><div class='button'>Add Profile</div></a>";
			   }
			   
	           $this->html .= "<a href='/gbSuite/home.php?app=inbox' class='menu-button'><div class='button'>Inbox</div></a>" .
	           				"<a href='/gbSuite/home.php?app=support' class='menu-button'><div class='button'>Support</div></a>
	           <a href='/gbSuite/logout.php' class='menu-button'><div class='button' > Logout </div></a>
			</div>" ;
			
			echo $this->html;
		}				
	} 
?>
