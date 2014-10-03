<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	include_once($_SERVER['PHP_ROOT']."/gbSuite/util/connection.php");
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/user.php");
	include_once($_SERVER['PHP_ROOT']."/jsonwrapper.php");
	
		
    class Authorization extends Application
	{
		public function __construct()
		{
			parent::__construct();
			
			$this->appId = 20;									
		} 
			
		/*
		 * This is the first gui for the authorization.
		 * This method shows the current titles listed by department.
		 * By default only the administrator can see this application and
		 * all titles that has Full access permission
		 * 
		 * 
		 * 
		 */	
		public function renderHTML()
		{	
			$auth = new AuthorizationManager($this->user->getUID(),$this->connection);
			
			if($auth->auth_info['full'] == 0)
			{
				echo "You cant view this application";
				return;
			}
			
			$sql = " select *,(select count(department) from title_auth  where ta.department = department) as count from title_auth as ta order by department  ";
		 	$result = $this->connection->exec_query($sql);		
			
			
			?>
			
			<form method=post action="/gbSuite/apps/process_application.php?app=authorization&uid=<?= $this->user->getUID() ?>&action=update">
			<table class="authorization" cellspacing=0 cellpadding=0 border=0 cellmargin=0 >
			<tr>
		    	<td>
					<div class="authorization-title">Titles and Department Authorization</div>
		            <div class="body-content">
		            	<table class="table-content" cellspacing=0 cellpadding=0 border=0 cellmargin=0 align=center >
		                	<tr>
		                    	<td class="header-field">
		                  			Department      
		                        </td>
		                        <td class="header-field">
		                  			Title      
		                        </td>
		                        <td class="header-field">
		                  			Full      
		                        </td>
		                        <td class="header-field">
		                  			Assigned      
		                        </td>
		                        <td class="header-field">
		                  			Full(His Department)      
		                        </td>
		                        <td class="header-field">
		                  			Only Department      
		                        </td>
		                    </tr>
		                    
		                    <?
		                    	$department = "";
		                    	$first = false;
		                    	$rowCount = mysql_num_rows($result); 
		                    	$titleCount = 0;
		                    	
		                    	while($row = mysql_fetch_array($result))
		                    	{
		                    		
		                    		if($row["department"] != $department)
		                    		{
		                    			$first = true;
		                    			?>
			                    			<tr >
						                    	<td class="department-cell" >
						                  			<?= $row["department"] ?>      
						                        </td>
						                        <td>&nbsp;</td>
						                        <td>&nbsp;</td>
						                        <td>&nbsp;</td>
						                        <td>&nbsp;</td>
						                        <td>&nbsp;</td>
			                    			</tr>
		                    			<?
		                    			
		                    			$department = $row["department"];
		                    		}
		                    		else
		                    		{
		                    			?> 
		                    			 <tr >
					                    	<td>&nbsp; 
					                  			      
					                        </td>
					                        <td class="title-cell">
					                  		    <?= $row["title"] ?>   <input type=hidden id=title_<?= $titleCount++ ?> name=title_<?= $titleCount ?> value="<?= $row["title"] ?>" >   
					                        </td>
					                        <td class='cell-check'>
					                  			<input name='auth_<?= $titleCount ?>'  id='auth_<?= $titleCount ?>' <?= ($row["full"] == 1 ? " checked " : "" ) ?>   type="radio" value="FULL" />
					                        </td>
					                        <td class='cell-check'>
					                  			<input name="auth_<?=  $titleCount ?>" id='auth_<?= $titleCount ?>' <?= ($row["assigned"] == 1 ? " checked " : "" ) ?> type="radio" value="ASSIGNED" />
					                        </td >
					                        <td class='cell-check'>
					                  			<input name="auth_<?= $titleCount ?>" id='auth_<?= $titleCount ?>' <?= ($row["full_dep"] == 1 ? " checked " : "" ) ?> type="radio" value="FULL_HD" />      
					                        </td>
					                        <td ><?= $row['to_deps'] ?>
					                        </td>
					                    </tr>
		                    			<?
		                    		}
		                    		
		                    		if($first ) 
		                    		{
		                    			$first = false;
		                    			?>
		                    			 <tr >
					                    	<td>&nbsp;</td>
					                        <td class="title-cell">
					                  		    <?= $row["title"] ?> <input type=hidden  id=title_<?= $titleCount++ ?> name=title_<?= $titleCount ?> value="<?= $row["title"] ?>" >      
					                        </td>
					                        <td class='cell-check'>
					                  			<input name='auth_<?= $titleCount ?>' id='auth_<?= $titleCount ?>'  <?= ($row["full"] == 1 ? " checked " : "" ) ?>   type="radio" value="FULL" />
					                        </td>
					                        <td class='cell-check'>
					                  			<input name="auth_<?= $titleCount ?>" id='auth_<?= $titleCount ?>' <?= ($row["assigned"] == 1 ? " checked " : "" ) ?> type="radio" value="ASSIGNED" />
					                        </td>
					                        <td class='cell-check'> 
					                  			<input name="auth_<?= $titleCount ?>" id='auth_<?= $titleCount ?>' <?= ($row["full_dep"] == 1 ? " checked " : "" ) ?> type="radio" value="FULL_HD" />      
					                        </td>
					                        <td ><?= $row['to_deps'] ?>
					                        </td>
					                    </tr>
		                    			<?
		                    		}	
		                    	}
		                    ?>
		            
					</table>
					
					<div align=center>
						<input type='submit' value="Update" class="button-link"  name='update' onclick='updateAuthorization(<?= $rowCount ?>)' />
					</div> 
					
					<?
						$this->saveFunction();
					?>	
					
            </div>
		</td>
	</tr>
</table>
			<?
		}
		
		public function update($params)
		{
			foreach($params as $key => $value)
			{
				//echo "$key => $value<br>";
				
				$val = "title_";
				
				if( substr($key,0,strlen($val)) == $val)
				{
					$parts = split($val,$key);
					$check = $params["auth_".$parts[1]];
					
					echo $check."<br>";
					
					$full = ($check == "FULL" ? "1" : "0");
					$assigned = ($check == "ASSIGNED" ? "1" : "0");
					$to_dep = ($check == "FULL_HD" ? "1" : "0");
					
					$sql = " update title_auth set full = $full, assigned=$assigned, full_dep=$to_dep " .
							"where title='$value'";
					
					$this->connection->exec_query($sql);
					
					header("location:".$_SERVER['HTTP_REFERER']);
					
				}
				else
				{
					header("location:".$_SERVER['HTTP_REFERER']);
				}
				
			}
		}
		
		public function saveFunction()
		{
			?>
			<script>
				
				function updateAuthorization(titles)
				{
					
				} 
			</script>
			<?
		}
		
		
		public function get_values($params)
		{
			 $sql = " select * from title_auth ";
			 
			 $result = $this->connection->exec_query($sql);
			 
			 while($rows[] = mysql_fetch_array($result));
			 $data[1] = $rows;
			 echo json_encode($rows);
			 
		}
	} 
	
	/*
	 * This class handle the Authorization Model.
	 */
	class AuthorizationManager
	{
		var $uid; //the user to authorize.
		var $connection; //the connection object.
		var $title ; 	//the title of the user.
		var $user;
		var $title_info;	// The information about the title of the user.
		var $auth_info;
		
		public function __construct($uid,$connection = null,$user = null)
		{
			
			$this->uid = $uid;
			
			if($connection != null)
				$this->connection = $connection;
			else
				$this->connection = new Connection();
			
				
			if($user != null)
			{
				$this->user = $user;
			}
			else
			{
				$query = "SELECT * FROM info WHERE uid = '".$_POST['me_uid']."' ";
				
				$row = $connection->get_row($query);
				
				$this->user = new User($row);
			}
			
			$query = "select * from title where title = '". $this->getTitle() ."'";
			$this->title_info = $this->connection->get_row($query);
			
			$query = "select * from title_auth where title = '". $this->getTitle() ."'";
			
			
			$this->auth_info = $this->connection->get_row($query);
			
					 
		}
		
		
		public function getTitle()
		{
			$atts = $this->user->getAttributes();
			return $atts["title"];
		}
		
		public function hasFullAccess()
		{
			return $this->auth_info["full"];
		}
		
		public function getDepartments()
		{
			$atts = $this->user->getAttributes();
			return $atts["department"];
		}
		
		public function getTitleInfo()
		{
			return $this->title_info;
		}
		
		public function getAuthInfo()
		{
			return $this->auth_info;
		}
		
		/*
		 * Returns mixed;
		 * 	if Administrator then ALL is returned
		 *  
		 */
		public function getPermissions($uid)
		{
			
			$query = "SELECT * FROM info WHERE uid = '".$uid."' ";
			$user_info = $this->connection->get_row($query);
			
			
			echo "<br>Me: ".$this->user->getTitle();
			echo "<br>His Department: ".$user_info['department']."<br>";
			
			foreach($this->auth_info as $key => $value)
			{
				if(!is_numeric($key))
				{
					echo $key."  ----   ". $value."<br>";	
				}
			}
									
			if($this->getTitle() == "Administrator" or $this->auth_info['full'] == 1)
			{
				return "FULL";
			}
			else
			{
				if($this->auth_info['assigned'])
				{
					return $this->process_assigned($user_info);
				}
				else
				if($this->auth_info['full_dep'])
				{
					
					if(in_array($this->auth_info['department'],explode("/",$user_info['department'])))
					{
						return "FULL";
					}
					else
					{
						return "NONE ";
					}
					 
				}
				else
					if($this->auth_info['to_deps'] !=  "")
					{
						//If the user has full access to departments listed
						$user_deps = explode("/",$user_info['department']);
						
						echo "Deps: ".$user_deps."-----------".$this->auth_info['department'];
						
						if(in_array($this->auth_info['department'],$user_deps))
						{
							return "FULL";
						}
						else
						{
							return "NONE";
						}
					}
					
				
				return $this->auth_info['to_deps']."  -----  ".$this->auth_info['permission'];
				
			}
		}
		/*
		 * $user_info: The user to know if can read or write.
		 */
		public function process_assigned($user_info)
		{
			return " Assigned. ";	
		}
	}
	
	
	
	/*
	 * 
		                    		
	 */
	
	 
?>



