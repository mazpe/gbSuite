<?php
	
	if(isset($_POST['uid']))
	{
		include_once($_SERVER['PHP_ROOT']."/gbSuite/util/image_upload.php");
		include_once($_SERVER['PHP_ROOT']."/gbSuite/util/connection.php");
		$uid = $_POST['uid'];
		
		
		$uploader = new ImageUploader($_SERVER['PHP_ROOT']."/images/user","profile_image",$uid);
		
		$uploader ->move_to($_SERVER['PHP_ROOT']."/images/user/full_size");
		 
		if($uploader->move_image(148,140))
		{
			
			$filename = basename($uploader->uploaded_file);
			
			$connection = new Connection();
			$sql = "update info set pic = '$filename' where uid = '$uid' ";
			$connection->exec_query($sql);
			header("location:/gbSuite/home.php");
				
		}
	}
	else
	{
		echo "Error loading image!";
	}
	
		
?>
