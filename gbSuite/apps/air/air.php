<?php

	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");

    class AuthorizationInstallationRequest extends Application
	{
		public function __construct()
		{
			$this->appId = 24;
		}

		public function make($params)
		{
			global $SERVER_URL;

			$this->html = "&nbsp;";

			$query = "select uid,name from info where like = '%Manager%'";

			/* $combo = "<select name='manager'>".
				$this->connection->fillSelect($query,"uid","name")
			."</select>";
			*/

			$sql = "select * from apps where app_id='". $params['app_id'] ."'";

			$appInfo  = $this->connection->get_row($sql);

			$managerInfo = $this->user->getManagerInfo();

			?>
			<form action='/gbSuite/home.php?app=air&action=save_req' method=post>
				<table class="air-table">
				<input type=hidden name=app_id value='<?= $params['app_id'] ?>' />
				<input type=hidden name=manager_uid value='<?= $managerInfo['uid'] ?>' />
					<tr>
				  		<td>
							<table class="air-request">
								<tr>
									<td class="label-global">
										Application to Install:
									</td>
									<td>&nbsp;&nbsp;&nbsp;</td>
									<td >
										<?= $appInfo['title'] ?>
									</td>
								</tr>
								<tr>
									<td class="label-global">
										Request to:
									</td>
									<td>&nbsp;&nbsp;&nbsp;</td>
									<td >
										<?=  $managerInfo['name'] ?>
									</td>
								</tr>
							</table>
							<br>
							<div class="comments">
								<label class="label-global">Comments:</label>
								<textarea name="comments"></textarea>
							</div>
							<input class="button-link" name="Submit" type="submit" value="Accept">
							<input class="button-link" name="Go back" type="button" value="Go back" onclick="document.setLocation('<?
							echo $SERVER_URL;
							?>/gbSuite/home.php?app=applications')">
						</td>
					</tr>
				</table>
			</form>
			<?
		}

		public function view($params = null)
		{
			$this->html = "&nbsp;";
			global $SERVER_URL;

			if(strpos($this->user->getTitle(),"Manager") !== false or $this->user->getTitle() == "Administrator")
			{

			}
			else
			{
				$this->html = "<fb:error message='Sorry'>You are not allowed to see this information!</fb:error>";
				return;
			}

			if($this->user->getTitle() == "Administrator")
			{

				$sql = "select air.*,  I.name,A.title, A.name as appName, DATE_FORMAT(air.date,'%m/%d/%Y %r' ) as reqDate
				from air
				inner join info I using(uid)
				inner join apps A using(app_id)
				where air.status='Pending' or air.status='Confirmed' order by date desc ";

			}
			else
			{
				$sql = "select air.*,  I.name,A.title, A.name as appName, DATE_FORMAT(air.date,'%m/%d/%Y %r' ) as reqDate
				from air
				inner join info I using(uid)
				inner join apps A using(app_id)
				where air.manager_uid = '". $this->user->getUID() ."' and air.status='Pending' order by date desc";
			}



			$results = $this->connection->exec_query($sql);

			if(mysql_num_rows($results) <= 0)
			{
				//print_r($_GET);

				echo "<fb:error message='No Application Installation Request Found!'></fb:error>";
				return ;
			}


			?>
			<table class="air-table">
			<tr>
				<td>
					<div class="label-global">
							This Applications Installations needs your approve:
					</div>

					<table class="list-app-user" cellpadding=0>
						<tr>
							<td class="fields-app">
								User
							</td>
						    <td class="fields-app">
								Application
							</td>
							<td class="fields-app">
								Date
							</td>
							<td class="fields-app">
								Status
							</td>
							<?
								if($this->user->getTitle() == "Administrator")
								{
									?>
										<td colspan=4 class="fields-app" >Actions</td>
									<?
								}
							?>
						</tr>
						<?
							while($row = mysql_fetch_array($results))
							{
						?>
								<tr <?
									if($this->user->getTitle() != "Administrator")
									{
										?>
											onclick=document.setLocation('<?= $SERVER_URL ?>/gbSuite/home.php?app=air&action=edit_req&req_id=<?= $row['req_id'] ?>')
										<?
									}
								?>>
									<td>
									    <a href='/gbSuite/home.php?app=profile&uid=<?= $row['uid'] ?>' ><?= $row['name'] ?></a>
									</td>
								    <td>
										<?= $row['title'] ?>
									</td>
									<td>
										<?= $row['reqDate'] ?>
									</td>
									<td align=center>
									    <?= $row['status'] ?>
									</td>
									<?
										if($this->user->getTitle() == "Administrator")
										{
												if($row['status'] != "Confirmed")
												{
												?>
													<td width=16px>
														<a href='/gbSuite/home.php?app=air&action=confirm&req_id=<?= $row['req_id'] ?>&option=Deny'>
															<img src='/images/resources/deny.gif' />
														</a>
													</td>
													<td  align=left ><a href='/gbSuite/home.php?app=air&action=confirm&req_id=<?= $row['req_id'] ?>&option=Deny'>
													Deny</td>
												<?
												}
												else
												{
													?>
													<td  ></td>
													<td  ></td>
													<?
												}

												?>
												<td width=16px>
													<a href='/gbSuite/home.php?app=air&action=confirm&req_id=<?= $row['req_id'] ?>&option=Install'>
														<img src='/images/resources/install.gif' />
													</a>
												</td>
												<td  align=left><a href='/gbSuite/home.php?app=air&action=confirm&req_id=<?= $row['req_id'] ?>&option=Install'>
												Install</td>
											<?
										}
									?>

								</tr>
					<?
						}
					?>
					</table>
					</td>
				</tr>
			</table>


			<?
		}

		public function edit_req($params)
		{
			global $SERVER_URL;

			$sql = "select air.*, I.name,A.title, A.name as appName, DATE_FORMAT(air.date,'%m/%d/%Y %r' ) as reqDate
					from air inner join info I using(uid) inner join apps A using(app_id) where req_id = '". $params['req_id'] ."'";

			$air = $this->connection->get_row($sql);

			?>
			<form action='/gbSuite/home.php?app=air&action=confirm' method=post>
			<input type=hidden name='req_id' value='<?= $params['req_id'] ?>' />
				<table class="air-table">
					<tr>
						<td>
							<table class="air-applications-view">
								<tr>
									<td class="label-global">User:</td>
									<td><?= $air['name'] ?></td>

								</tr>
								<tr>
									<td class="label-global">Application:</td>
									<td><?= $air['title'] ?></td>
								</tr>
							</table>
							<br>
							<div class="comment-info">
								<label class="label-global">Comment:</label>
								<div><?= $air['comments'] ?></div>
								<br>
							</div>

						    <div class="comments">
						    	<label class="label-global">Reply Comment:</label>
								<textarea name="reply_comments"></textarea>
						    </div>
							<input class="button-link" name="Submit" type="submit" value="Accept">
							<input class="button-link" name="Submit" type="submit" value="Deny">
							<input class="button-link" name="Cancel" type="button" value="Cancel" onclick=document.setLocation('<?= $SERVER_URL ?>/gbSuite/home.php?app=air&action=view') >

						</td>
					</tr>
				</table>
			</form>
			<?

			$this->html = "&nbsp;";
		}

		public function process($params)
		{
			global $SERVER_URL;
			//print_r($params);


			$sql = "select air.*, I.name,A.title, A.name as appName, DATE_FORMAT(air.date,'%m/%d/%Y %r' ) as reqDate
					from air inner join info I using(uid) inner join apps A using(app_id) where req_id = '". $params['req_id'] ."'";

			$air = $this->connection->get_row($sql);

			if($params['option'] == 'Accept')
			{
				$sql = "update air set status = 'Confirmed', reply_comments='". $params['reply_comments'] ."', confirmed='1' where req_id = '". $params['req_id'] ."'";
			}
			else
			if($params['option'] == "Install")
			{
				$this->install_application($air['uid'],$air['manager_uid'],$air['app_id'],$params['req_id']);
			}
			else
			{
				$sql = "update air set status = 'Deny', reply_comments='". $params['reply_comments'] ."' where req_id = '". $params['req_id'] ."'";
			}
			$this->connection->exec_query($sql);


			if(!isset($_GET['redirect']))
				$this->view($params);

		}


		public function install_application($user,$manager,$app_id,$air_id)
		{

			$sql = "insert into user_apps  (uid,app_id ) values ('$user','" . $app_id . "')";
			$this->connection->exec_query($sql);
			//echo "<br>".$sql."<br>";

			$sql =  "SELECT default_visibility FROM apps WHERE app_id = $app_id";
			$defaultVisibility = $this->connection->get_value($sql);
			//echo $sql."<br>";

			$sql = "INSERT INTO application_configuration (id, uid, app_id, visibility, profile_link) VALUES (0, '$user', $app_id, '$defaultVisibility', 1)";
			$this->connection->exec_query($sql);
			//echo $sql."<br>";

			$sql = "update air set status = 'Installed',installed_by='". $this->user->getUID() ."' where req_id = '". $air_id ."'";
			$this->connection->exec_query($sql);
			//echo $sql."<br>";

		}

		public function confirm($params)
		{
			global $SERVER_URL;

			$this->html = "&nbsp;";

			$sql = "select air.*, I.name,A.title, A.name as appName, DATE_FORMAT(air.date,'%m/%d/%Y %r' ) as reqDate
					from air inner join info I using(uid) inner join apps A using(app_id) where req_id = '". $params['req_id'] ."'";

			$air = $this->connection->get_row($sql);


			if($this->user->getTitle() == "Administrator"  )
			{

				if($params['option'] == 'Accept')
				{
					$action = "Giving the permission ";
				}
				else
					if($params['option'] == 'Install')
					{
						$action = "Installing the application";
					}
					else
					{
						$action = "Denying the permission ";
					}


				if($air['status'] != "Confirmed")
				{
					$message = "You are $action without the Confirmation of a Manager!";
				}

			}


			?>
			<div class="air-table">
				<fb:error message='Are you sure?'>
					<?= $message ?>
					<form action='/gbSuite/apps/process_application.php?app=air&action=process&uid=<?= $this->user->getUID() ?>&redirect=air&method=view'
					   method=post>
							<input type=hidden name='req_id' value='<?= $air['req_id'] ?>' >
							<input type=hidden name='option' value='<?

							if($this->user->getTitle() != "Administrator")
								echo $params['Submit'];
							else
								echo $params["option"];

							?>' >
							<input type='hidden' name='reply_comments' value='<?= $params['reply_comments'] ?>'>



								<table class="air-applications-view">
											<tr>
												<td class="label-global"><?
												if($this->user->getTitle() != "Administrator")
												{
													if($params['Submit'] == 'Accept')
			 										{
														echo "Giving permission to install";
													}
													else
													{
														echo "Denying permission to install";
													}
												}
												else
												{
													if($params['option'] == 'Install')
													{
														echo "Installing";
													}
													else
													{
														echo "Denying permission to install";

													}
												}

												?>  the application:</td>
												<td>&nbsp;&nbsp;&nbsp;</td>
												<td ><?= $air['title'] ?></td>
											</tr>
											<tr>
												<td class="label-global">To this Associate</td>
												<td>&nbsp;&nbsp;&nbsp;</td>
												<td ><?= $air['name'] ?></td>
											</tr>
											<tr>
												<td class="label-global">Date:</td>
												<td>&nbsp;&nbsp;&nbsp;</td>
												<td ><?= $air['reqDate'] ?></td>
											</tr>
											<tr>
												<td colspan=3 class="label-global" align=center>
													<input class="button-link" name="Submit" type="submit" value="Yes">
													<input class="button-link" name="No" type="button" value="No" onclick=document.setLocation('<?= $SERVER_URL ?>/gbSuite/home.php?app=air&action=view') >
													<input class="button-link" name="Cancel" type="button" value="Cancel" onclick=document.setLocation('<?= $SERVER_URL ?>/gbSuite/home.php?app=air&action=view') >
												</td>
											</tr>
										</table>
						</form>
					</fb:error>
			</div>
			<?

		}

		public function save_req($params)
		{
			/*foreach($params as $key=>$value)
			{
				echo "$key => $value<br>";
			}*/

			$sql = "insert into air (uid,app_id,comments,manager_uid,date,status) values (" .
					"'". $this->user->getUID()."'," .
					"'". $params['app_id']."'," .
					"'". $params['comments'] ."'," .
					"'". $params['manager_uid'] ."'," .
					"now(),'Pending')";

			$this->connection->exec_query($sql);

			$this->html = "<fb:error message='Your request has been sent!'></fb:error>";

		}

		public function renderHTML()
		{
			if($this->html != "")
			{
				echo $this->html;

			}
			else
			{
				$this->view();
			}
		}
	}
?>
