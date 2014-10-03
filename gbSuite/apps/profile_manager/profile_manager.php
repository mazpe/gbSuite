<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");	

    class AdminProfile extends Application
	{
		public function __construct()
		{
			$this->appId = 52;
		}

		public function renderHTML()
		{
			$this->show_profile();
		}
		
		function show_profile()
		{
			$query = "select uid, name, title, active, department, manager_uid from info order by name";
			$result= $this->connection->exec_query($query);
			
			echo "<form action='/gbSuite/apps/process_application.php?app=profile_manager&uid=$uid&action=save_settings&redirect=profile_manager' method='post'>";
			
			echo "<table class='admin-profile' cellpadding='0' cellspacing='0'>";
			
			echo "<tr><td colspan='4'></td>
				 <td><input name='save' type='submit' value='Save' class='button-link'/></td></tr>";
			
			echo "<tr>
				  <th class='admin-profile-header' align='center'>Name</th>
				  <th class='admin-profile-header' align='center'>Active</th>
				  <th class='admin-profile-header' align='center'>Title</th>
				  <th class='admin-profile-header' align='center'>Manager</th>
				  <th class='admin-profile-header'>&nbsp;</th>		
				  </tr>";
				  
			while($row = mysql_fetch_assoc($result))
			{
				echo "<tr class='admin-profile-header'>";
				echo "<td class='admin-profile-username'>".$row['name']
					."</td>
					 <td align='center'><input name='active_". $row['uid'] ."' type='checkbox' ".($row['active']==1?" checked ":"")." ><input name='uid_".$row['uid']."' type='hidden' value='".$row['uid']."'></td>
	     			 <td align='center'><select name='user_title_". $row['uid'] ."' class='admin-profile-select'>
	     			 	<option ".(strpos($row['title'], 'Administrator') === false ? '' : 'selected')." value='Administrator'>Administrator</option>
						<option ".(strpos($row['title'], 'Dealer Principal') === false ? '' : 'selected')." value='Dealer Principal'>Dealer Principal</option>
						<option ".(strpos($row['title'], 'Chief Financial Officer') === false ? '' : 'selected')." value='Chief Financial Officer'>Chief Financial Officer</option>
						<option ".(strpos($row['title'], 'General Manager') === false ? '' : 'selected')." value='General Manager'>General Manager</option>
						<option ".(strpos($row['title'], 'General Sales Manager') === false ? '' : 'selected')." value='General Sales Manager'>General Sales Manager</option>
						<option ".(strpos($row['title'], 'Sales Manager') === false ? '' : 'selected')." value='Sales Manager'>Sales Manager</option>
						<option ".(strpos($row['title'], 'Salesperson') === false ? '' : 'selected')." value='Salesperson'>Salesperson</option>
						<option ".(strpos($row['title'], 'Finance Manager') === false ? '' : 'selected')." value='Finance Manager'>Finance Manager</option>
						<option ".(strpos($row['title'], 'Internet Sales Director') === false ? '' : 'selected')." value='Internet Sales Director'>Internet Sales Director</option>
						<option ".(strpos($row['title'], 'Administrative Assistant') === false ? '' : 'selected')." value='Administrative Assistant'>Administrative Assistant</option>
						<option ".(strpos($row['title'], 'Controller') === false ? '' : 'selected')." value='Controller'>Controller</option>
						<option ".(strpos($row['title'], 'Accounting') === false ? '' : 'selected')." value='Accounting'>Accounting</option>
						<option ".(strpos($row['title'], 'Customer Service Manager') === false ? '' : 'selected')." value='Customer Service Manager'>Customer Service Manager</option>
					  </td>
					  <td align='center'><select name='user_manager_". $row['uid'] ."' class='admin-profile-select'";
					  
					  $manager_uid = $row['manager_uid'];
					  
    				  $admin = $this->get_admin($manager_uid);
					 foreach($admin as $uid => $name)
					 {
					  	echo "<option ".($manager_uid == $uid ? 'selected' : '')." value='$uid'>".$name."</option>";	
					 }													
						
				echo "</select></td>				
					  <td><a href='/gbSuite/home.php?app=profile&action=edit&uid=".$row['uid']."'>Edit Settings</a></td>";					  
			    echo "</tr>";				
			}	
			
			 echo "<tr><td class='admin-bottom' colspan='4'></td>
						   <td class='admin-bottom'><input name='save' type='submit' value='Save' class='button-link'/></td></tr>";						
						
			echo "</table>";
			echo "<form>";
		}
		
		function save_settings()
		{
			$uids = array();
			$titles = array();
					
			foreach($_POST as $key => $value)
			{
				if(strpos($key,"uid_") !== false)	
				{									
					$uids[] = $value;
				}
			}			
			
			foreach($uids as $uid)
			{
				if(isset($_POST['active_'.$uid]))
					$active=1;
				else
					$active=0;
					
				//if(isset($_POST['user_title_'.$uid]))
				$title=	$_POST['user_title_'.$uid];	
				$manager= $_POST['user_manager_'.$uid];			
					
				$query= "UPDATE info SET active='$active', title='$title', manager_uid='$manager' WHERE uid = '$uid'";
				$this->connection->exec_query($query);				
			}												
		}
		
		function get_admin($manager_uid)
		{
		  $query = "";
		  $admin = array();
		  						
		  if($this->user->getTitle() == 'Administrator')
		     $query = "SELECT uid, name FROM info WHERE title = 'Sales Manager' OR title = 'General Manager' OR title = 'General Sales Manager'";
		  else
		     $query = "SELECT uid, name FROM info WHERE title = 'Sales Manager' AND uid = '$manager_uid'";
									
		     $rs = $this->connection->exec_query($query);							
													
		 if($rs !== false)							
		   	while($r = mysql_fetch_assoc($rs))
		     	$admin[$r['uid']] = $r['name'];
			    	
		   return $admin;
		}
	}
?>
