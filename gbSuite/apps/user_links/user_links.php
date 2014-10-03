<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");

    class UserLinks extends Application
	{
		public function __construct()
		{

		}

		public function renderHTML()
		{
			$uid 	= $this->user->getUID();
			$query  = "select U.id, U.name, U.window, U.link, L.user_name, L.pass_name, L.other_config, L.action, U.user_log, U.password_log  from user_link U left join link_config L on U.link = L.link  where uid=$uid";
							
			$links  = $this->connection->exec_query($query);
			$lkNew  = array("gs.reyrey.com", "hotmail.com", "http://dealer.webmakerx.net/Login/Login.aspx");
						
			echo "<div class='user-links'>";
			
			echo "<div class = 'add-link'> <a href= '/gbSuite/home.php?app=launch_pad&action=add'>Edit Links</a></div>";
			
			while($row = mysql_fetch_assoc($links))
			{
				if(strpos($row['link'], "http://") !==  false)
					$link = $row['link'];
				else
					if(strpos($row['link'], "https://") !==  false)
						$link = $row['link'];	
					else										
						$link = "http://".$row['link'];
																
				if($row['window'] == 1)
				{
					?>
						<div class='user-links-url'><a onclick="submitForm('submit_form_<?= $row['id'] ?>')"  href="#" ><?= $row['name']?></a></div>
					<?
						
					
					if(true or $row['user_log'] != null && $row['action'] != null && $row['other_field'] != null)
						{
							?>																
								<form style='display:none' id="submit_form_<?=$row['id']?>" action="<?=$row['action']?>" method="post" target='blank'>
									<label><?= $row['user_name']?></label>
									<input class="url-edit" name="<?= $row['user_name']?>" type="text" value="<?= $row['user_log']?>"/><br>
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
							<div class='user-links-url'><a onclick="submitForm('submit_form_<?= $row['id'] ?>')"  href="#" ><?= $row['name']?></a></div>
						<?
						
						if(true or $row['user_log'] != "" && $row['action'] != "" && $row['other_field'] != "")
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
						echo "<div class='user-links-url'><a href='/gbSuite/home.php?app=launch_pad&id=".$row['id']."&action=view'>".$row['name']."</a></div>";
				}
	
			}        
			echo "</div>";
			?>
				<script >
					function submitForm(id_form)
					{
						document.getElementById(id_form).submit();	
					}
				</script>
			<?
		
		}
		
		public function add()
		{	
		}
		
		function view()
		{
			$this->renderHTML();
			$this->html = "&nbsp";
		}			
	}
?>