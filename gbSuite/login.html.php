<td></td><?

	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/server_url.php';
?>

<html>
<head>
	<title>Login User</title>
	<link href="<?=$YOUR_APP_SERVER_URL.'/css/style.css';?>" rel="stylesheet" type="text/css" />
	<script>
	requested=false;

	function keyPressed(event)
	{
		
		if(event.keyCode == 13)
		{
			do_ajax(Ajax.JSON);
		}
	}


			
	var APP_SERVER_URL = "<?=$YOUR_APP_SERVER_URL;?>/";
	
	function do_ajax(type) 
	{
		var ajax = new Ajax();
		
		ajax.responseType = type;

		
		switch (type) 
		{
			case Ajax.JSON:
				ajax.ondone = function(data)
				{
					document.getElementById('message-login').setTextValue(data.login? '' : data.message);				
					
					if(data.login)
					{
						var remember_login=document.getElementById('remember-login').getChecked();
							document.setLocation(APP_SERVER_URL + 'gbSuite/home.php?app=login&uid=' + data.uid+"&remember="+remember_login);
					}else{
						requested=false;
					}
				};
				break;
		};
		if(!requested){
		var remember_login=document.getElementById('remember-login').getChecked();		
		ajax.post('<?=$YOUR_APP_SERVER_URL;?>/gbSuite/process_login.php?email=' + document.getElementById('user').getValue() + '&password=' + document.getElementById('password').getValue()+'&remember='+remember_login,true);
		requested=true;
		}
	}
	</script>
</head>

<body>

<fb:login title="gbSuite Login">
	<table id="access-user" cellpadding="0" cellspacing="0" border="0" align="center" 
	width=300px cellspacing=5px >
	<tr>
		<td width=30%>
			<fb:editor-label >User</fb:editor-label>
		</td>
		<td>
			<input id="user" name="user" type="text" value="" style="width:100%" />
		</td>
	</tr>
	<tr>
		<td>
			<fb:editor-label >Password</fb:editor-label>
		</td>
		<td>
			<input id="password" name="password" type="password" onkeypress="keyPressed(event)" value="" style="width:100%" />
		</td>
	</tr>
	<tr>
	<td colspan=2>
		<div align=center>
			<input class="button-login" name="accept" type="button" onclick="do_ajax(Ajax.JSON);return false;" value="Login"/>
		 	<input class="button-login" name="accept" type="button" value="Register User"/>
	 	</div>
	 </td>
	</tr>
	</table>
 	<div id="message-login" class="message-login"></div>
</fb:login>

</body>
</html>

<script>
	document.getElementById('password').addEventListener('keypress',keyPressed);
</script>