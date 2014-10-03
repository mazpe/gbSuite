<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class TopReport extends Application
	{
		public function __construct()
		{
			$this->appId = 31;	
		}
		
		public function renderHTML()
		{
			$uid = $this->user->getCurrentProfileUID();
			
						
			$title = $this->connection->get_value(" select title from info where uid = '$uid' limit 1");
			
			if($title == 'Salesperson')
			{
			
				$limit = 4;
			}
			else
			{
				$limit = 5;
			}
			
			$query = "SELECT title,name FROM apps A INNER JOIN user_apps U ON A.app_id = U.app_id INNER JOIN application_configuration AC ON A.app_id = AC.app_id AND U.uid = AC.uid WHERE A.type = 'report' AND U.uid = '".$uid."' AND AC.top_report = 1 ORDER BY title LIMIT 0, $limit"; 					 
						
			$rs = $this->connection->exec_query($query);
			
			$rowCount = mysql_num_rows($rs);
			
			if($rs == null or $rowCount == 0 )
			{
				if($title == 'Salesperson')
				{
					$html .= "<ol class='top-report-list'>";
					$html .= "<li><a class=top-report-item href='/gbSuite/home.php?app=power_rank&suid=". $this->user->getCurrentProfileUID()  ."' >Power Rank</a></li>";;
					$html .= "</ol>";			
				}
				else
				{
					$html = "<fb:message> No reports Found!. </fb:message>";
				}
				echo $html;
				return;	
			}
			
			/*$html .= '<div class="config-table">
					<table cellspacing=0 cellpadding=0 cellmargin=0 border=0 width=80%>
						<tr>
						<td>
							<div class="config-title">'. $name .' Reports</div>
							<div class="config-subtitle">Select a report to display</div>
						</td>
						</tr> 
					</table>';
					//<table  cellspacing=0 width=80% align=center> ';*/
			
			$html .= "<ol class='top-report-list'>";
			
			if($title == 'Salesperson')
			{
				$html .= "<li><a class=top-report-item href='/gbSuite/home.php?app=power_rank&suid=". $this->user->getCurrentProfileUID()  ."' >Power Rank</a></li>";;
			}
			
			while($row = mysql_fetch_array($rs, MYSQL_ASSOC))
			{
				
				//$html .=	"<fb:config-table-item >";
				
				$html .=	"<li><a class=top-report-item href='/gbSuite/home.php?app=". $row["name"] ."&suid=". $this->user->getCurrentProfileUID()  ."' >".$row['title']."</a></li>";
			 	
				//$html .=	"</fb:config-table-item >";
				
				//$html .= "<li><a class=config-table-item href='/gbSuite/home.php?app=report&action=view&uid=". $this->user->getCurrentProfileUID()  ."' >".$row['title']."</a></li>"; 		
			}
			
			$html .= "</ol>";
						   
			echo $html;
		}
		
		public function view($parms)
		{
			
			print_r($parms);
			$this->html = "A";
			
		}
	}
?>
