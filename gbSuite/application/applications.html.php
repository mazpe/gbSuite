<?php
	session_start();
	
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/server_url.php';
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/gbSuiteConfig.php';
	include_once $_SERVER['PHP_ROOT'].'/lib/maxchart/maxChart.class.php';
	
	include_once $_SERVER['PHP_ROOT'].'/lib/api/application.php';
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/facebook-platform/client/facebook.php';
	
	$friends = explode(",",$_POST['fb_sig_friends']);
	
	if(isset($_GET['fid']))
		$sql = "SELECT uid, agency_id, name FROM info I INNER JOIN friend F ON I.uid = F.user2 WHERE F.user1 = '".$_GET['fid']."' AND I.active = 1";
	else
		$sql = "SELECT uid, agency_id, name FROM info I INNER JOIN friend F ON I.uid = F.user2 WHERE F.user1 = '".$_POST['me_uid']."' AND I.active = 1";
	
	$data = queryf($data_conn, $sql);
	$associates = array();

	while($row = mysql_fetch_assoc($data))
	{	
		$associates[$row['uid']] = $row;	
	}
	
	$sql = "SELECT uid, title, name FROM info WHERE uid = '".$_POST['me_uid']."'";
	
	$data = queryf($data_conn, $sql);
	
	$user = mysql_fetch_assoc($data);
	
	$sql = "SELECT uid,agency_id,name FROM info ";
	
	if(trim($_POST['fb_sig_friends']) == "")
	{
		$where = " where 1=0";
	}
	else
	{
		$where = " where uid in (". $_POST['fb_sig_friends'] .")";
	}
	$sql = $sql.$where;
	
	$data = queryf($data_conn, $sql);
	$user_agency = array();
		
	$query = "select title,name from info where uid='".$_POST['me_uid']."'";
	$user_info = mysql_fetch_assoc(queryf($data_conn, $query));				
	
	while($row = mysql_fetch_assoc($data))
	{
		$user_agency[$row['uid']] = $row;	
	}										
?>

<fb:header-bar >
	<div class="header-label" >Welcome to gbSuite <b><?= $user["name"]  ?></b></div>
</fb:header-bar>

<table align=center cellpadding=0 cellspacing=0 border=0>
<tr>
<td align=center>
<div class="page">
	<table width=100% cellpadding=0 cellspacing=0>
	<tr>
		<td>
			<div class="leftcolumn">
				<fb:logo />
				<fb:picture />
					<fb:profile-links  uid="<?= $_POST['me_uid'] ?>" />
					
					<fb:title title="Associates" subtitle="
					 <?
							if(count($friends) > 0 && trim($friends[0]) != "") 
								echo count($friends);
							else
							{
								echo 0;
							} 
						?> 
					 Associates" >
	
					</fb:title>
					
					<fb:friend-section >
						<table align=center width=100%>
					      	<?
								$cont = 0;
								
								if(count($friends) > 0 && trim($friends[0]) != "")
								{
									foreach($friends as $friend)
									{
										if($cont == 0)
										{
											 echo "<tr>";
										}
										$cont++;
										?>
											<td class="friend" align=center valign=top>
							        			<p><fb:friend uid="<?= $friend ?>" name="<?= $user_agency[$friend]['name'] ?>" width="60px" height="60px" /></p>
							        		</td>
										<?	
										if($cont == 2)
										{
											$cont = 0;
											echo "</tr>";
										}
									}
								}
								else
								{
									echo "<tr><td></td>";
									
								}
							?>
					      </tr>
					    </table>
					</fb:friend-section>
			</div>
			
			<div class="content">
				<div class="menu">
						   <a href="/gbSuite/dms.php"><div class="button">DMS</div></a>
				           <a href="/gbSuite/home.php"><div class="button">Profile</div></a>
				           <a href="/gbSuite/applications.php"><div class="button">Applications</div></a>
						   <? if($user_info['title'] == "Administrator")
						   {
						   	 ?>
						   <a href="/gbSuite/new_profile.php">
				           		<div class="button">Add Profile</div>
				           </a>
						   <? 
						   }
						   ?>
				           <a href="/gbSuite/inbox.php"><div class="button">Inbox</div></a>
				           <a href="logout.php"><div class="button">Logout</div></a>
				</div>
								
				<fb:agency-logo agency_id='ag01' /> 
				
				<fb:data-personal />
				<fb:data-employee />
					
				<div>				
			 		<?
			 			
			 			
			 			include_once $_SERVER['PHP_ROOT'].'/gbSuite/application/my_apps.php';
			 						 			

			 		?>
				</div>
			</div>
			</td> 
	</tr>
	</table>
</div>
</td>
</tr>
</table>

<fb:footer-bar />

<?
function test()
{
	$facebook = new Facebook($_POST['api_key'],$_POST['app_secret']);
	
	//print_r($facebook->api_client->auth_getSession($facebook->api_client->auth_createToken()));
		
	//print_r($facebook->api_client->feed_publishStoryToUser("title","BODY"));
	
	//print_r($facebook->api_client->photos_getAlbums(null,null));
	
}

?>