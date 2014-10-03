<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");

    class LaunchPad extends Application
	{
		public function __construct()
		{
			$this->appId = 48;
		}

		public function renderHTML()
		{			
			if($this->html != "")
			{
				echo $this->html;
				return;
			}
				
			$uid 	= $this->user->getUID();	
			$id_link = $_GET['id'];
			
			$contDefault = $this->connection->get_value("select count(id) from user_link where link_default = 1 and uid = $uid");
			
			if(!isset($_GET['id']))
			{
				if($contDefault == 1)
				{
					$id_link = $this->connection->get_value("select id from user_link where uid = $uid  and link_default = 1");					
				}					
				else
				{
					echo "No default Link";				
					return;
				}								
			}		
			
			$link = $this->connection->get_value("select link, window from user_link where id = $id_link");				
				
			echo "<div align='right'><a href='/gbSuite/home.php?app=profile'><input name='close' type='submit' value='Close' class='button-link'/></a></div>";

				if(strpos($link, "http://") !==  false)
					echo "<iframe name='navigator' src='$link' width=99% height=500></iframe>";
				else
					if(strpos($link, "https://") !==  false)
						echo "<iframe name='navigator' src='$link' width=99% height=500></iframe>";
					else
						echo "<iframe name='navigator' src='http://$link' width=99% height=500></iframe>";

			echo "<div align='right'><a href='/gbSuite/home.php?app=profile'><input name='close' type='submit' value='Close' class='button-link'/></a></div>";
				
				$query = "select L.user_name, L.pass_name, U.link, L.other_config, L.action, U.user_log, U.password_log from user_link U inner join link_config L on U.link = L.link where U.id='$id_link'";
				$data = $this->connection->get_row($query);	
								
				/*if(is_array($data))
				{
					?>																
						<form style='' id="send_data" action="<?= $data['action'] ?>" method='post' target='navigator'>
							<label>User</label>
							<input class = "url-edit" name = "<?=$data['user_name']?>" type = "text" value = "<?= $data['user_log'] ?>"/><br>
							<label>Password</label>
							<input class="url-edit" name = "<?=$data['pass_name']?>" type = "password" value = "<?=$data['password_log']?>"/>
							<input class = "url-edit" name = "login" type = "submit" value = "Sign In"/>
							<?=$data['other_config']?>
						</form>		
						
						<script language="JavaScript">
							document.getElementById("send_data").submit();
						</script>												
					<?				
				}*/ 
				
		}
		
		public function add($action)
		{				
			if($action == "edit")
			{
				$action == "edit";
				
				$query = " select name,link, user_log, password_log, window, link_default from user_link  where id = '". $_GET['id'] ."'";
				$link = $this->connection->get_row($query);			
			}
			else
			{
				$action = "add";
			}			
			
			$this->html = "&nbsp;";
			$uid 	= $this->user->getUID();
			$query  = "select U.id, U.name, U.window, U.link, L.user_name, L.pass_name, L.other_config, L.action, U.user_log, U.password_log  from user_link U left join link_config L on U.link = L.link  where uid=$uid or uid=0 order by uid desc, name asc";
			$links  = $this->connection->exec_query($query);
						
			?>
			<fb:js>
				<script>
					function a1234567_loadLink()
					{
						var combo = document.getElementById('app1234567_links_config');
						id = combo.value;
						
						var link_name = document.getElementsByName("link_name")[0];
						link_name.value = document.getElementById("app1234567_name_"+id).value;
						
						var link = document.getElementsByName("url")[0];
						link.value = document.getElementById("app1234567_link_"+id).value;
						
						var window = document.getElementsByName("window")[0];
						window.checked = document.getElementById("app1234567_window_"+id).value == 1 ? true : false;																	
					}
				</script>
			</fb:js>
			<table>
				<tr>
					<form action="/gbSuite/apps/process_application.php?app=launch_pad&action=process_<?= $action ?>&redirect=launch_pad&method=edit&uid=<?= $uid ?>" method="post">
						<td>
							Links
						</td>
						<td>
							<select id='links_config' name='link_config' class='admin-profile-select'>
								<?
									$query2 = "select id, name, link, window from link_config";
									$link_config = $this->connection->exec_query($query2);
									
									while($r = mysql_fetch_assoc($link_config))
									{
										$rows[] = $r;
										echo "<option  value=".$r['id'].">".$r['name']."</option>";
									}
								?>							
							</select>
							<?
								foreach($rows as $r)
								{
									?>
										<input type='hidden' id='name_<?= $r['id'] ?>' value="<?= $r['name'] ?>">
										<input type='hidden' id='link_<?= $r['id'] ?>' value="<?= $r['link'] ?>">
										<input type='hidden' id='window_<?= $r['id'] ?>' value="<?= $r['window'] ?>">
									<?				
								}
							?>
						</td>					
					
						<td>
							<input class="button-link" type="button" value="Add" onClick="loadLink()" />
						</td>
					</form>
				</tr>
			</table>
				
			<table class='config-table-add' cellpadding=0 cellspacing=0 align=center >
			<tr>
				<td class="title-add">
					Link Name
				</td>
				<td class="title-add">
					Url
				</td>
				<td class="title-add">
					User
				</td>				
				<td class="title-add">
					Password
				</td>
				<td class="title-add-window">
					Link Default
				</td>
				<td class="title-add-window">
					New Window
				</td>
				<td colspan="2" class="title-add">
				</td>
			</tr>
			<tr>
							
			<form action="/gbSuite/apps/process_application.php?app=launch_pad&action=process_<?= $action ?>&redirect=launch_pad&method=add&uid=<?= $uid ?>" method="post">
					<td class="table-add-input">
						<input name="link_name" type="text" value="<?= $link['name'] ?>" />
						<input type=hidden name='id' value=<?= $_GET['id']?> >
					</td>
					<td class="table-add-input">
						<input size="27" class="url-edit" name="url" type="text" value="<?= $link['link'] ?>"/>										
					</td>					
					 <td class="table-add-input">
						<input class="link_name" name="user" type="text" value="<?= $link['user_log'] ?>"/>
					</td>
					<td class="table-add-input">
						<input class="password-edit" name="password" type="password" value="<?= $link['password_log'] ?>"/>
					</td>
					<td table-add-check align="center">
						<input type="checkbox" name="default" <?=($link['link_default']==1?" checked ":"")?>/>
					</td>						
					<td table-add-check align="center">
						<input type="checkbox" name="window" <?=($link['window']==1?" checked ":"")?>/>
					</td>
					<td  class="table-add-input">
						<input class="button-link" name="submit" type="submit" value="Save"/>
					</td>										
			</form>
			</tr>
			<?			
				
			$lkNew  = array("gs.reyrey.com", "hotmail.com", "http://dealer.webmakerx.net/Login/Login.aspx");	
						
			while($row = mysql_fetch_assoc($links))
			{	
				if(strpos($row['link'], "http://") !==  false)
					$link = $row['link'];
				else
					if(strpos($row['link'], "https://") !==  false)
						$link = $row['link'];	
					else										
						$link = "http://".$row['link'];
				
				$link = $row['link'];
				$sizeURL = strlen($link);
				$sizeMax = 50;
								
				if($sizeURL >= $sizeMax)
				{					
					$link = substr($link, 0, 30)." ..."; 
				}
			?>		
					<tr class="config-table-item">
						<td width="150px" class="config-table-item-space">
							<?= $row['name'] ?>
						</td>
						<td class="config-table-item-url" colspan="3">
						 <?
						 	if($row['window'] == 1)
						 	{
						 		?>
									<div class='user-links-url'><a onclick="submitForm('submit_form_<?= $row['id'] ?>')"  href="#" ><?= $row['link']?></a></div>
								<?
								
								if(true or $row['user_log'] != null && $row['action'] != null && $row['other_field'] != null)
									{
										?>																
											<form style='display:none' id="submit_form_<?=$row['id']?>" action="<?=$row['action']?>" method="post" target='blank'>
												<label><?= $row['user_name']?></label>
												<input class="url-edit" name="<?= $row['user_name']?>" type="text" value="<?= $row['user_log']?> "/><br>
												<label><?= $row['pass_name'] ?></label>
												<input class="url-edit" name="<?= $row['pass_name'] ?>" type="password" value="<?= $row['password_log']?>" />
												<input class="url-edit" name="Login" type="submit" value="Login" />
												<?= $row['other_config'] ?>
											</form>
										<?
									}					 		
						 	}	
						 	else
						 	{
						 		$inNew = false;
						
								foreach($lkNew as $l)
								{
									if(strpos($row['link'], $l) !== false)
									{
										$inNew = true;
										break;
									}
								}
								
								if($inNew)
								{
									?>
										<div class='user-links-url'><a onclick="submitForm('submit_form_<?= $row['id'] ?>')"  href="#" ><?= $row['link']?></a></div>
									<?
									
									if(true or $row['user_log'] != null && $row['action'] != null && $row['other_field'] != null)
										{
											?>																
												<form style='display:none' id="submit_form_<?=$row['id']?>" action="<?=$row['action']?>" method="post" target='blank'>
													<label><?= $row['user_name']?></label>
													<input class="url-edit" name="<?= $row['user_name']?>" type="text" value="<?= $row['user_log']?> "/><br>
													<label><?= $row['pass_name'] ?></label>
													<input class="url-edit" name="<?= $row['pass_name'] ?>" type="password" value="<?= $row['password_log']?>" />
													<input class="url-edit" name="Login" type="submit" value="Login" />
													<?= $row['other_config'] ?>
												</form>
											<?
										}				
								}									
								else
						 			echo "<a href='/gbSuite/home.php?app=launch_pad&id=".$row['id']."&action=view'>".$link."</a>";
						 	}
						 ?>
							
						</td>
						<td class="config-table-item-space">
						&nbsp
						</td>
						<td align="center" class="config-table-item-space">
							<?
								if($row['window'] == 1)
									echo "<img src='/images/resources/opt_active.gif'/>";
							    else
								  	echo "&nbsp";						
							?>		 
						</td>
						<td>
							<?
								if( $row['uid'] != '0')
								{
							?>
								<a href='/gbSuite/home.php?app=launch_pad&action=edit&id=<?=$row['id']?>'>
									<img src="/images/resources/edit.gif"/>
								</a>
							<?
								}
							?>
						</td>
						
						<td width="20px" class="config-table-item-space">
							<?
								if( $row['uid'] != '0' )
								{
							?>
								<a href='/gbSuite/apps/process_application.php?app=launch_pad&action=delete&redirect=launch_pad&method=add&uid=<?= $uid ?>&id=<?=$row['id']?>'>
									<img src="/images/resources/delete.gif">
								</a>
							<?
								}
							?>
						</td>
					</tr>	
				<?
			}
			echo "</table>";
			
			?>
				<script >
					function submitForm(id_form)
					{
						document.getElementById(id_form).submit();	
					}
				</script>
			<?							
		}
		
		function process_add($params)
		{					
			$link_name = $_POST['link_name'];
			$url = $_POST['url'];
			$user = $_POST['user'];
			$password = $_POST['password'];
			$uid = $this->user->getUID();
			
			if(isset($_POST['default']))
			{
				if(!isset($_POST['window']))
					$link_default = 1;
			}
			else
				$link_default = 0;
			
			if(isset($_POST['window']))
				$window = 1;
			else
				$window = 0;
							
			$query="insert into user_link (link,link_default,name,uid, user_log, password_log, window) values('$url', '$link_default','$link_name', '$uid', '$user', '$password', '$window')";
			$q = "update user_link set link_default = 0 where link != '$url'";			
			$query2= "select count(*) from user_link where (link='$url' or name = '$link_name') and uid=$uid";
			
			$count = $this->connection->get_value($query2);
			
			if ($count == 0)
			{	
				$insert = $this->connection->exec_query($query);
				$r = $this->connection->exec_query($q);
			}					
		}
		
		function delete()
		{
			$id = $_GET['id'];
			$query="delete from user_link where id='$id'";
			$insert = $this->connection->exec_query($query);
		}
		
		function edit()
		{
			$this->add("edit");
		}
		
		function process_edit()
		{
			$link_name=$_POST['link_name'];
			$url=$_POST['url'];
			$id = $_POST['id'];
			$user=$_POST['user'];
			$password=$_POST['password'];	
			
			if(isset($_POST['default']))
			{
				if(!isset($_POST['window']))
					$link_default = 1;
			}				
			else
				$link_default = 0;
						
			if(isset($_POST['window']))
				$window = 1;
			else
				$window = 0;
			
			$query="update user_link set link='$url', link_default = '$link_default', name='$link_name', user_log='$user', password_log='$password', window='$window' where id='$id'";
			$insert = $this->connection->exec_query($query);
			
			$q = "update user_link set link_default = 0 where link != '$url'";
			$r = $this->connection->exec_query($q);						
		}	
		
		function view()
		{
			$this->renderHTML();
			$this->html = "&nbsp";
		}	
	}
?>