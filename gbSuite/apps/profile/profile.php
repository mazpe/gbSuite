<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");	
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/server_url.php';
		
    class Profile extends Application
	{
		public function __construct($uid = null)
		{
			parent::__construct(null, null, $uid);//This method create a connection and an user.
			
			$this->appId = 12;	
		}
		
		public function setStatusMessage($message)
		{
			$query = "UPDATE info SET status_message = '$message' WHERE uid = '".$this->user->getUID()."'";
			
			$this->connection->exec_query($query);		
			
			echo '{"message":"Status message has been changed."}';
		}
		
		public function add()
		{
			$html = "";
			
			$html = '<div>					
					<form id="edit_profile_form" class="edit_profile_form" method="post" action="'.$MY_APP_SERVER_URL.'/gbSuite/apps/profile/process_profile.php">
						<input type="hidden" name="action" value="add" />
						<input type="hidden" name="user_profile_status" value="1" />
						<table>
							<tr>
								<td rowspan=4><fb:friend width="200px" heigth="200px" uid="9999"></fb:friend></td>
								<td><label for="first_name">First Name:</label></td>
								<td><input type="text" name="user_first_name" id="first_name" value="" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="middle_name">Middle Name:</label></td>
								<td><input type="text" name="user_middle_name" id="middle_name" value="" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="last_name">Last Name:</label></td>
								<td><input type="text" name="user_last_name" id="last_name" value="" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="initials">Initials:</label></td>
								<td><input type="text" name="user_initials" id="initials" value="" /></td>
							</tr>
							<tr>
								<td><label for="user_manager">Manager:</label></td>
								<td><select id="user_manager_uid" name="user_manager_uid">';
									$query = "SELECT uid, name FROM info WHERE title = 'Sales Manager' OR title = 'General Manager' OR title = 'General Sales Manager'";
																	
									$rs = $this->connection->exec_query($query);							
													
									if($rs !== false)							
										while($row = mysql_fetch_assoc($rs))
											$html .= '<option value="'.$row['uid'].'">'.$row['name'].'</option>';									
										
								$html .= '</select>
								</td>
							</tr>
							<tr>
								<td><label for="title">Title:</label></td><td><select name="user_title" id="title" value=""> 
																					<option value="Administrator">Administrator</option>
																					<option value="Dealer Principal">Dealer Principal</option>
																					<option value="Chief Financial Officer">Chief Financial Officer</option>
																					<option value="General Manager">General Manager</option>
																					<option value="General Sales Manager">General Sales Manager</option>
																					<option value="Sales Manager">Sales Manager</option>
																					<option value="Salesperson">Salesperson</option>
																					<option value="Finance Manager">Finance Manager</option>
																					<option value="Internet Sales Director">Internet Sales Director</option>
																					<option value="Administrative Assistant">Administrative Assistant</option>
																					<option value="Controller">Controller</option>
																					<option value="Accounting">Accounting</option>
																					<option value="Customer Service Manager">Customer Service Manager</option>
																					</select> 
								</td>				
							</tr>
							<tr>
								<td><label for="department">Department:</label></td>
								<td>
									<input type="checkbox" name="user_department_sales" text="Sales" /><label>Sales</label><br/>
									<input type="checkbox" name="user_department_parts" text="Parts" unchecked/><label>Parts</label><br/>
									<input type="checkbox" name="user_department_services" text="Services" unchecked/><label>Service</label><br/>
									<input type="checkbox" name="user_department_finance" text="Finance" unchecked/ ><label>Finance</label><br/>
									<input type="checkbox" name="user_department_accounting" text="Accounting" unchecked/ ><label>Accounting</label><br/>
									<input type="checkbox" name="user_department_collision_center" unchecked text="Collision Center"/><label>Collision Center</label><br/>
								</td>
							</tr>
							<tr>
								<td><label for="email">Email:</label></td>
								<td>
									<input type="text" name="user_email" id="email" value="" onclick="setFocus(this)"/>
									<div id="invalid-email-message" class="invalid-password-message"></div>
								</td>
							</tr>
							<tr>
								<td><label for="password">Password:</label></td>
								<td><input type="password" name="user_password" id="password" value="" onclick="setFocus(this)"/></td>								
							</tr>
							<tr>
								<td><label for="confirm_password">Confirm Password:</label></td>
								<td>
									<input type="password" name="confirm_password" id="confirm_password" value="'.$member['password'].'"  onclick=""/>
									<div id="invalid-password-message" class="invalid-password-message"></div>
								</td>
							</tr>

							<tr>
								<td><label for="phone">Telephone:</label></td>
								<td><input type="text" name="user_phone" id="phone" value="" onclick="setFocus(this)"/></td>
							</tr>							
							<tr>
								<td><label for="address1">Address 1:</label></td>
								<td colspan=2><textarea name="user_address1" id="address1" value="" onclick="setFocus(this)"></textarea></td>
							</tr>
							<tr>
								<td><label for="address2">Address 2:</label></td>
								<td colspan=2><textarea name="user_address2" id="address2" value="" onclick="setFocus(this)"></textarea></td>
							</tr>
							<tr>
								<td><label for="city">City:</label></td>
								<td><input type="text" name="user_city" id="city" value="" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="state">State:</label></td>
								<td><input type="text" name="user_state" id="state" value="" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="zip_code">Zip Code:</label>
								</td><td><input type="text" name="user_zip_code" id="zip_code" value="" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="sex">Gender:</label></td><td><select name="user_sex" id="sex" value="">
																					<option value="Male">Male</option>																																							
																					<option value="Female">Female</option>
																					</select> 
								</td>				
							</tr>
							<tr>
								<td><label for="birthday">Birthday:</label>
								</td><td><input type="text" name="user_birthday" id="birthday" value="'.$member['birthday'].'"/></td>
							</tr>
							<tr>
								<td><label for="hometown">Hometown:</label>
								</td><td><input type="text" name="user_hometown" id="hometown" value="'.$member['hometown'].'"/></td>
							</tr>
							<tr>
								<td><label for="cellphone">Cellphone:</label>
								</td><td><input type="text" name="user_cellphone" id="hometown" value="'.$member['cellphone'].'"/></td>
							</tr>
							<tr>
								<td><label for="phone">Home phone:</label>
								</td><td><input type="text" name="user_phone" id="phone" value="'.$member['phone'].'"/></td>
							</tr>
							<tr>
								<td><label for="hiredate">Hire Date:</label>
								</td><td><input type="text" name="user_hiredate" id="hiredate" value="'.$member['hiredate'].'"/></td>
							</tr>
							<tr>
								<td><label for="employee_id">Employee No:</label>
								</td><td><input type="text" name="user_employee_id" id="employee_id" disabled=true value="'.$member['employee_id'].'"/></td>
							</tr>	
							<tr style="visibility:hidden">
								<td><label for="uid">User Id:</label></td>
								<td><input type="text" name="user_uid" id="uid" value="" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="title">Dealership:</label></td><td><select name="user_agency_id" id="agency_id" value="">
																					<option value="ag01">Toyota Mall of Georgia</option>																																							
																					</select> 
								</td>				
							</tr>			
							<tr>	
								<td colspan=2><div id="error-message"></td>
							</tr>
							<tr>				
								<td><input class="button-login" name="save" type="button" value="Submit" onclick="submitProfile();"/></td>
								<td><input class="button-login" type="reset" value="Reset"/></a>
								<td><a href="/gbSuite/home.php?app=profile&uid='.$member['uid'].'"><input type="button" class="button-login" name="cancel" value="Cancel"/></a></td>
							</tr>		
						</table>
					</form>
				</div>';

			$this->html = $html;
		}
		
		public function edit()
		{
			$html = "";
			
			$member = $this->user->getCurrentAttributes();
			 
			//Formulario para la edicion del profile
			$html = '<div >					
					<form id="edit_profile_form" class="edit_profile_form" enctype="multipart/form-data" method="post" action="'.$MY_APP_SERVER_URL.'/gbSuite/apps/profile/process_profile.php">
						<input type="hidden" name="action" value="edit" />
						<input type="hidden" name="user_profile_status" value="2" />
						<table border=0>
							<tr>
								<td rowspan=3><fb:friend width="200px" heigth="200px" uid="'.$member['uid'].'"></fb:friend></td>
								<td><label for="first_name">First Name:</label></td>
								<td><input type="text" name="user_first_name" id="first_name" value="'.$member['first_name'].'" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="middle_name">Middle Name:</label></td>
								<td><input type="text" name="user_middle_name" id="middle_name" value="'.$member['middle_name'].'" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="last_name">Last Name:</label></td>
								<td><input type="text" name="user_last_name" id="last_name" value="'.$member['last_name'].'" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="initials">Initials:</label></td>
								<td><input type="text" name="user_initials" id="initials" value="'.$member['initials'].'" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="user_manager">Manager:</label></td>
								<td><select id="user_manager_uid" name="user_manager_uid">';
								
								$query = "";
								
								if($this->user->getTitle() == 'Administrator')
									$query = "SELECT uid, name FROM info WHERE title = 'Sales Manager' OR title = 'General Manager' OR title = 'General Sales Manager'";
								else
									$query = "SELECT uid, name FROM info WHERE title = 'Sales Manager' AND uid = '".$member['manager_uid']."'";
									
									$rs = $this->connection->exec_query($query);							
													
									if($rs !== false)							
										while($row = mysql_fetch_assoc($rs))
											$html .= '<option '.($member['manager_uid'] == $row['uid'] ? 'selected' : '').' value="'.$row['uid'].'">'.$row['name'].'</option>';									
								
								$html .= '</select>
								</td>
							</tr>
							<tr> 								
								<td><label for="title">Title:</label></td><td><select name="user_title" id="title">';
								
									if($this->user->getTitle() == 'Administrator')
										$html .= '<option '.(strpos($member['title'], 'Administrator') === false ? '' : 'selected').' value="Administrator">Administrator</option>
												  <option '.(strpos($member['title'], 'Dealer Principal') === false ? '' : 'selected').' value="Dealer Principal">Dealer Principal</option>
												  <option '.(strpos($member['title'], 'Chief Financial Officer') === false ? '' : 'selected').' value="Chief Financial Officer">Chief Financial Officer</option>
												  <option '.(strpos($member['title'], 'General Manager') === false ? '' : 'selected').' value="General Manager">General Manager</option>
												  <option '.(strpos($member['title'], 'General Sales Manager') === false ? '' : 'selected').' value="General Sales Manager">General Sales Manager</option>
											 	  <option '.(strpos($member['title'], 'Sales Manager') === false ? '' : 'selected').' value="Sales Manager">Sales Manager</option>
											 	  <option '.(strpos($member['title'], 'Salesperson') === false ? '' : 'selected').' value="Salesperson">Salesperson</option>
												  <option '.(strpos($member['title'], 'Finance Manager') === false ? '' : 'selected').' value="Finance Manager">Finance Manager</option>
										  		  <option '.(strpos($member['title'], 'Internet Sales Director') === false ? '' : 'selected').' value="Internet Sales Director">Internet Sales Director</option>
												  <option '.(strpos($member['title'], 'Administrative Assistant') === false ? '' : 'selected').' value="Administrative Assistant">Administrative Assistant</option>
												  <option '.(strpos($member['title'], 'Controller') === false ? '' : 'selected').' value="Controller">Controller</option>
												  <option '.(strpos($member['title'], 'Accounting') === false ? '' : 'selected').' value="Accounting">Accounting</option>
												  <option '.(strpos($member['title'], 'Customer Service Manager') === false ? '' : 'selected').' value="Customer Service Manager">Customer Service Manager</option>';
											 	  
									else
										$html .= '<option value="'.$member['title'].'">'.$member['title'].'</option>';
										
								$html .= '</select> 
								</td>				
							</tr>
							<tr>
								<td><label for="department">Department:</label></td>
								<td>
									<input type="checkbox" name="user_department_sales" text="Sales"'.(strpos($member['department'], 'Sales') === false ? 'unchecked' : 'checked').' /><label>Sales</label><br/>
									<input type="checkbox" name="user_department_parts" text="Parts"'.(strpos($member['department'], 'Parts') === false ? 'unchecked' : 'checked').' /><label>Parts</label><br/>
									<input type="checkbox" name="user_department_services" text="Services"'.(strpos($member['department'], 'Services') === false? 'unchecked' : 'checked').' /><label>Service</label><br/>
									<input type="checkbox" name="user_department_finance" text="Finance"'.(strpos($member['department'], 'Finance') === false? 'unchecked' : 'checked').' / ><label>Finance</label><br/>
									<input type="checkbox" name="user_department_accounting" text="Accounting"'.(strpos($member['department'], 'Accounting') === false? 'unchecked' : 'checked').' / ><label>Accounting</label><br/>
									<input type="checkbox" name="user_department_collision_center"'.(strpos($member['department'], 'Collision Center') === false? 'unchecked' : 'checked').' /><label>Collision Center</label><br/>
								</td>
							</tr>
							<tr>
								<td><label for="email">Email:</label></td>
								<td>
									<input type="text" name="user_email" id="email" value="'.$member['email'].'" onclick="setFocus(this)"/>
									<div id="invalid-email-message" class="invalid-password-message"></div>
								</td>								
							</tr>
							<tr>
								<td><label for="password">Password:</label></td>
								<td><input type="password" name="user_password" id="password" value="'.$member['password'].'"  onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="confirm_password">Confirm Password:</label></td>
								<td>
									<input type="password" name="confirm_password" id="confirm_password" value="'.$member['password'].'"  onclick=""/>
									<div id="invalid-password-message" class="invalid-password-message"></div>
								</td>
							</tr>
							<tr>
								<td><label for="phone">Telephone:</label></td>
								<td><input type="text" name="user_phone" id="phone" value="'.$member['phone'].'" onclick="setFocus(this)"/></td>
							</tr>							
							<tr>
								<td><label for="address1">Address 1:</label></td>
								<td colspan=2><textarea name="user_address1" id="address1" onclick="setFocus(this)">'.$member['address1'].'</textarea></td>
							</tr>
							<tr>
								<td><label for="address2">Address 2:</label></td>
								<td colspan=2><textarea name="user_address2" id="address2" onclick="setFocus(this)">'.$member['address2'].'</textarea></td>
							</tr>
							<tr>
								<td><label for="city">City:</label></td>
								<td><input type="text" name="user_city" id="city" value="'.$member['city'].'" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="state">State:</label></td>
								<td><input type="text" name="user_state" id="state" value="'.$member['state'].'" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="zip_code">Zip Code:</label>
								</td><td><input type="text" name="user_zip_code" id="zip_code" value="'.$member['zip_code'].'" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="sex">Gender:</label></td><td><select name="user_sex" id="sex" value="'.$member['sex'].'">
																					<option '.(strpos($member['sex'], 'Male') === false ? '' : 'selected').' value="Male">Male</option>																	
																					<option '.(strpos($member['sex'], 'Female') === false ? '' : 'selected').' value="Female">Female</option>
																					</select> 
								</td>				
							</tr>
							<tr>
								<td><label for="birthday">Birthday:</label>
								</td><td><input type="text" name="user_birthday" id="birthday" value="'.$member['birthday'].'"/></td>
							</tr>
							<tr>
								<td><label for="hometown">Hometown:</label>
								</td><td><input type="text" name="user_hometown" id="hometown" value="'.$member['hometown'].'"/></td>
							</tr>
							<tr>
								<td><label for="cellphone">Cellphone:</label>
								</td><td><input type="text" name="user_cellphone" id="hometown" value="'.$member['cellphone'].'"/></td>
							</tr>
							<tr>
								<td><label for="phone">Home phone:</label>
								</td><td><input type="text" name="user_phone" id="phone" value="'.$member['phone'].'"/></td>
							</tr>
							<tr>
								<td><label for="hiredate">Hire Date:</label>
								</td><td><input type="text" name="user_hiredate" id="hiredate" value="'.$member['hiredate'].'"/></td>
							</tr>							
							<tr>
								<td><label for="employee_id">Employee No:</label>
								</td><td><input type="text" name="user_employee_id" id="employee_id" disabled=true value="'.$member['employee_id'].'"/></td>
							</tr>
							<tr style="visibility:hidden">
								<td><label for="uid">User Id:</label></td>
								<td><input type="text" name="user_uid" id="uid" value="'.$member['uid'].'" onclick="setFocus(this)"/></td>
							</tr>
							<tr>
								<td><label for="title">Dealership:</label></td><td><select name="user_agency_id" id="agency_id" value="'.$member['agency_id'].'">
																					<option value="ag01">Toyota Mall of Georgia</option>																	
																					</select> 
								</td>				
							</tr>			
							<tr>	
								<td colspan=2><div id="error-message"></td>
							</tr>
							<tr>				
								<td><input class="button-login" name="save" type="button" value="Submit" onclick="submitProfile();"/></td>
								<td><input class="button-login" type="reset" value="Reset"/></td>
								<td><a class="button-link" href="/gbSuite/home.php?app=profile&uid='.$member['uid'].'">Cancel
									<!--input type="button" class="button-login" name="cancel" value="Cancel"/-->
								</a></td>				
							</tr>		
						</table>
					</form>
				</div>';
			
			$this->html = $html;
		}
		
		public function delete($params)
		{
			$uid = $this->user->getUID();
			//$fuid = $this->user->getFriendUID();
			$fuid = $_GET['fuid'];
			
			
			//$currentProfileUID = $this->user->getCurrentProfileUID();
			
			//$sql = "DELETE FROM friend WHERE (user1 = '".$fuid."' OR user2 = '".$fuid."')";
							
			//$this->connection->exec_query($sql);
				
			$sql = "UPDATE info SET active = 0, deleted_date = now() WHERE uid = '".$fuid."'";
			
			$this->connection->exec_query($sql);
			
			$sql = "SELECT name FROM info WHERE uid = '".$fuid."'";
				
			$row = $this->connection->get_row($sql);
	
			$friendName = $row['name'];
					
			//The user add a friend
			$sql = "INSERT INTO news (uid, type, value, date, status) ".
					"SELECT uid, 'associate', CONCAT(name, ' delete $friendName.'), now(), 0 ".
					"FROM info ".
					"WHERE uid = '".$uid."'";
				
			//$this->connection->exec_query($sql);
			
			echo ("<fb:js><script>window.location='/gbSuite/home.php?app=associates&uid=".$uid."' </script></fb:js>");
		}
		
		public function renderHTML()
		{
			if(!isset($this->html))
			{
				$firstName = $this->user->getCurrentProfileAttribute('first_name');
				$name = $this->user->getCurrentProfileAttribute('name');
				$title = $this->user->getCurrentProfileAttribute('title');
				$department = $this->user->getCurrentProfileAttribute('department');
				$statusMessage = $this->user->getCurrentProfileAttribute('status_message');
				$email = $this->user->getCurrentProfileAttribute('email');
				$sex = $this->user->getCurrentProfileAttribute('sex');
				$employeeId = $this->user->getCurrentProfileAttribute('employee_id'); 
								
				$loggedUserTitle = $this->user->getTitle();
				$currentProfileUID =  $this->user->getCurrentProfileUID();
				$userUID = $this->user->getUID();
				
				$ranking = 0;
				
				if($employeeId != "" || $employeeId != null)
				{
					$time = time(); 
					$tableName = "power_rank_temp_$time";
					
					$row = null;
			
					$query = "SELECT id, date_format(`from`, '%Y%m%d') AS `from`, date_format(`to`, '%Y%m%d') AS `to`, date_format(`from`, '%m/%d/%Y') AS `start`, date_format(`to`, '%m/%d/%Y') AS `end` FROM dealer_view WHERE now() BETWEEN `from` AND `to`";
					
					$dateRange = $this->connection->get_row($query);
					$condition = "";
				
					if($dateRange != null)
						$this->dealerViewId = $dateRange['id'];
					
					$sql = "CREATE TABLE $tableName AS select R.sales_person, R.employee_id, SUM(units_sold) AS units_sold, 0 AS us_rank, SUM(total_front_end) AS total_front_end, 0 AS tfe_rank, SUM(total_back_end) AS total_back_end, 0 AS tbe_rank, SUM(total_gross) AS total_gross, 0 AS tg_rank, SUM(total_score) AS total_score, 0 AS power_rank, uid  
							from app_rpt_power_rank R inner join info using(employee_id)
							WHERE dl_date BETWEEN '".$dateRange['from']."' AND '".$dateRange['to']."' GROUP BY R.sales_person";
					
					mysql_query($sql);
						
					$sql = "
									set @us_rank = 0;
									set @tfe_rank = 0;
									set @tbe_rank = 0;
									set @tg_rank = 0;
									set @power_rank = 0;
		
									update $tableName PR
									inner join (select employee_id, @us_rank := ifnull(@us_rank,0) +1 as us_rank from $tableName order by units_sold DESC) as T1 using(employee_id)
									inner join (select employee_id, @tfe_rank := ifnull(@tfe_rank,0) +1 as tfe_rank from $tableName order by total_front_end DESC) as T2 using(employee_id)
									inner join (select employee_id, @tbe_rank := ifnull(@tbe_rank,0) +1 as tbe_rank from $tableName order by total_back_end DESC) as T3 using(employee_id)
									inner join (select employee_id, @tg_rank := ifnull(@tg_rank,0) +1 as tg_rank from $tableName order by total_gross DESC) as T4 using(employee_id)
									inner join (select employee_id, @power_rank := ifnull(@power_rank,0) +1 as power_rank from $tableName order by total_score DESC) as T5 using(employee_id)
									set PR.us_rank = T1.us_rank , PR.tfe_rank = T2.tfe_rank , PR.tbe_rank = T3.tbe_rank, PR.tg_rank = T4.tg_rank , PR.power_rank = T5.power_rank;";
		 
									 foreach(split(';',$sql) as $query)
									 { 	 	
									 	if(trim($query) <> "" )
									 		//$this->connection->exec_query($query);
									 		mysql_query($query);
									 }
					
					$query = "SELECT power_rank FROM $tableName WHERE employee_id = ".$employeeId;
					
					$ranking = $this->connection->get_value($query);
					
					$query = "DROP TABLE $tableName";
					$this->connection->exec_query($query);
				}
				 
				$canEdit = $currentProfileUID == $userUID; //|| $this->user->getTitle() == 'Administrator';
				
				$this->html = '<div class="data-employee">
								<table width=100% align=left>
									<tr>
										<td colspan=2>
											<table>
												<tr>
													<td>
														<label class="user-name-label">'.$name.'</label>
														<label class="user-status-message"> is </label>
													</td>';
												
							if($canEdit == 1)
							{
								$this->html .=  '<td>
													<span onclick=activeStatusMessage()> 
													<label id="status-message-label">'.$statusMessage.'</label>
												</td>							
												<td>
													<select style="display:none;" id="status-message" class="dstatus-message" onchange="activeStatusMessage()" onkeypress="if(event.keyCode == 13)changeStatusMessage();">';
												
												$query = "SELECT message FROM status_messages";
																	
												$rs = $this->connection->exec_query($query);							
													
												if($rs !== false)							
													while($row = mysql_fetch_assoc($rs))
														$this->html .= '<option '.($statusMessage == $row['message'] ? 'selected' : '').' value="'.$row['message'].'">'.$row['message'].'</option>';									
													
								$this->html .= '</select></span></td>
												<td><span style="text-align:right"><input id="status-message-button" style="display:none;" type="button" value="Change" onclick="changeStatusMessage(this)"/></span></td>';
							}
							else
								$this->html .= "<td>".$statusMessage;  
							
								
				$this->html .= '</td></tr></table></td></tr>';

				$this->html .= '
							<tr><td width="70px" style="color:#3B5998"><b>Title:</b></td><td>'.$title.'</td></tr>
							<tr><td width="70px" style="color:#3B5998"><b>Department:</b></td><td>'.$department.'</td></tr>							
							<tr><td width="70px" style="color:#3B5998"><b>Email:</b></td><td>'.$email.'</td></tr>
							<tr><td width="70px" style="color:#3B5998"><br/><b>Ranking:</b></td><td></br>'.$ranking.'</td></tr>';
				
				$canEdit = $currentProfileUID == $userUID || $this->user->getTitle() == 'Administrator';
									
				if($canEdit)				
					$this->html .= '<tr><td colspan='.($loggedUserTitle != 'Salesperson' && $title == 'Salesperson'? 1 : 2).'><a class="button-link" href="/gbSuite/home.php?app=profile&action=edit&uid='.$currentProfileUID.'">Edit</a></td>';
				
				if($loggedUserTitle != 'Salesperson' && $title == 'Salesperson')				
					$this->html .= '<td><a class="button-link" href="/gbSuite/home.php?app=coach_report&uid_sp='.$currentProfileUID.'">Coach</a></td>';
															 
				$this->html .= '</tr>';
									
				$this->html .= '</table></div>';				
			}
														
			echo $this->html;			
		}
		
		public function edit_settings($params)
		{
			/*$this->editSettingsHTMLCode = '<table class="edit-setting">
	<tr>
    	<td>
			<div class="edit-setting-title">Edit Setting for Events</div>
  			<div class="dialog-body">
            	<table class="dialog-table">
                	<tr>
                    	<td class="label-item">
                        	Profile Box:
                        </td>
                        <td>
                        	<div class="question">who can see this?</div>
							<div class="sumary-simple">
                                <select class="list">
                                    <option id="1">Friends of Friends</option>
                                    <option id="2">Only Friends</option>
                                    <option id="3">No One</option>
                                    <option id="4">Custom</option>
                                </select>                        
                        	</div>
                            <div class="element-friend">Only Me</div>
                            <div class="button-edit">Edit Custom Setting</div>
                        </td>
                    </tr>
                    <tr>
						<td class="label-item">                    
                    		Left Menu:
                    	</td>
                        <td>                    
                    		<input id="left-menu" name="check-left-menu" type="checkbox" value="" />
                            <label class="label">Show this in my left-hand menu.</label>
                    	</td>
                    </tr>
                    <tr>
						<td class="label-item">                    
                    		News Feed:
                    	</td>
                        <td>                    
                    		<input id="news-feed" name="check-news-feed" type="checkbox" value="" />
                            <label class="label">Show me stories about this in my News Feed.</label>
                    	</td>
                    </tr>
                    <tr>
						<td class="label-item">                    
                    		mini-feed:
                    	</td>
                        <td>                    
                    		<input id="mini-feed" name="check-mini-feed" type="checkbox" value="" />
                            <label class="label">Show my friends stories about this through News Feed and my Mini-Feed.</label>
                    	</td>
                    </tr>
                    <tr>
						<td class="label-item">                    
                    		Profile Links:
                    	</td>
                        <td>                    
                    		<input id="profile-links" name="profile-links" type="checkbox" value="" />
                            <label class="label">Add a link below the profile picture to any profile.</label>
                    	</td>
                    </tr>
                    <tr>
						<td class="label-item">                    
                    		Email:
                    	</td>
                        <td>                    
                    		<input id="email" name="email" type="checkbox" value="" />
                            <label class="label">Allow this application to contact me via email.</label>
                    	</td>
                    </tr>
                </table>
                		<div class="buttons">
            				<input class="button-submit" name="button-save" type="button" value="Save Changes" />
                            <input class="button-submit" name="button-cancel" type="button" value="Cancel" />
            			</div>
            </div> 
			    
					</td>
				</tr>
			</table>';*/

			parent::edit_settings($params);
		}
	}
?>