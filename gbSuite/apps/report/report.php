<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class Report extends Application
	{
		public function __construct()
		{
			$this->appId = 5;	
		}
		
		public function renderHTML()
		{
			
			if($this->html <> "" )
			{
				echo $this->html;
				return;
			}
			
			$html = "";
			
			if($this->user->getFriendUID() == "")
			{
				$name = "My";
			}
			else
			{
				$friend_atts = $this->user->getFriendAttributes("first_name");
				$name = $friend_atts["first_name"]."'s";
				
			}
			
			$uid = $this->user->getCurrentProfileUID();
			
			$query = "SELECT title,name FROM apps A INNER JOIN user_apps U ON A.app_id = U.app_id WHERE A.type = 'report' AND U.uid = '".$uid."' ORDER by title"; 					 
			
			$rs = $this->connection->exec_query($query);

			
			$rowCount = mysql_num_rows($rs);
			
			
			
			if($rs == null or $rowCount == 0 )
			{
				$html = "<fb:message> No reports Found!. </fb:message>";
				echo $html;
				return;
			}
				
				
			$html .= '<div class="config-table">
					<table cellspacing=0 cellpadding=0 cellmargin=0 border=0 width=80%>
						<tr>
						<td>
							<div class="config-title">'. $name .' Reports</div>
							<div class="config-subtitle">Select a report to display</div>
						</td>
						</tr> 
					</table>';
					//<table  cellspacing=0 width=80% align=center> ';
			
			$html .= "<ul>";
			
			while($row = mysql_fetch_array($rs, MYSQL_ASSOC))
			{
				
				//$html .=	"<fb:config-table-item >";
				
				$html .=	"<li><a class=config-table-item href='/gbSuite/home.php?app=". $row["name"] ."&suid=". $this->user->getCurrentProfileUID()  ."' >".$row['title']."</a></li>";
			 	
				//$html .=	"</fb:config-table-item >";
				
				//$html .= "<li><a class=config-table-item href='/gbSuite/home.php?app=report&action=view&uid=". $this->user->getCurrentProfileUID()  ."' >".$row['title']."</a></li>"; 		
			}
			
			$html .= "</ul>";
						   
			echo $html;
		}
		
		
		public function view($parms)
		{
			
			print_r($parms);
			$this->html = "A";
			
		}
		
		
	}
?>
