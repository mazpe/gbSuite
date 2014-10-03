<?php



if(count($_POST) > 0)
{
	include_once "../util/image_upload.php";
	
	$image_uploader = new ImageUploader(".","profile_image","my_profile_image");
	
	
	
	$image_uploader->move_image($_POST['ancho'],$_POST['alto']);	
}
	   
?>

<html>
<head>
<meta http-equiv="Content-Language" content="en" />
<meta name="GENERATOR" content="PHPEclipse 1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>title</title>
</head>
<body bgcolor="#FFFFFF" text="#000000" link="#FF9966" vlink="#FF9966" alink="#FFCC99">

<img src="../util/my_profile_image.jpg" />

<form action="#" method=post enctype="multipart/form-data">
	<label>Upload a Image</label>
	<input type="file" name='profile_image' />
	<label>Ancho</label>
	<input type="text" name='ancho' />
	<label>Alto</label>
	<input type="text" name='alto' />
	<input type=submit name=a />
</form>

</body>
</html>
  