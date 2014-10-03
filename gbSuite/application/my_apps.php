<?php
	
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/util/connection.php';
	
	$conn = new Connection();
	
		
	$sql = " select apps.app_id,apps.name,apps.description,apps.status,apps.position, 
			apps.ordering,apps.title,apps.published,apps.params,icon   
			 from user_apps  inner join apps using(app_id) 
			 where user_apps.uid = '". $_POST['me_uid'] ."'";
	
	$result = $conn->exec_query($sql);

	?>
		<br>
		<div class="box-title"></div>
		<fb:config-table title="My Applications" subtitle="Use this page to control application of your profile."> 
	<?			
		while($row = mysql_fetch_assoc($result))
		{
			$image = $row['icon'];
			echo "<fb:config-table-item >";
				echo "<td width=10px><img src='/gbSuite/apps/". $row['name'] ."/$image'></td>";
				echo "<td>". $row['title'] ."&nbsp;<a href='apps/". $row['name'] ."/about.php'>" .
						"<span style='font-size:80%'>(about)</span>" .
						"</a></td>";
				echo "<td><a href=''>Edit Setting</a></td>";
				echo "<td>". $row['status'] ."</td>";
				echo "<td width=10px><a href='' style='vertical-align:middle'><img src='". IMAGES_ROOT."resources/delete.png" ."'></a></td>";
				echo "<td align=left width=1px><a href='' style='vertical-align:middle'>Remove</a></td>";
			echo "</fb:config-table-item >";
		}
	?>
		</fb:config-table>
	<?
?>

