<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/server_url.php';
	
    class ApplicationBar extends Application
	{
		public function __construct()
		{
			$this->appId = 30;		
		}
		
		private function renderBar()
		{
			$query = "SELECT A.description, A.name, A.short_name, A.title, A.image, A.file_name
						FROM apps A INNER JOIN application_configuration AC
						ON A.app_id = AC.app_id
						WHERE AC.desktop_icon = 1 AND AC.uid = '".$this->user->getUID()."' AND A.app_id <> 30 ORDER BY short_name LIMIT 0, 12";
		
			$rs = $this->connection->exec_query($query);
									
			$html = '';
			$html .= '<div id="doc">		
						<div id="info">
							<!--a href="#" id="move-left"><img src="'.$YOUR_SERVER_URL.'/gbSuite/apps/application_bar/images/arrow_left.png" /></a-->
							<div class="mod">
								<!--ul style="position: relative; left: -2px; top: 0px;" id="themes"-->
								<table align=center cellpadding=5px><tr>';
								
								$count = 0;
								$currentCount = 0;
								
								if($rs !== false && $rs != null)
									while($row = mysql_fetch_assoc($rs))
									{
										++$currentCount;
										
										if($currentCount == 1)
											$count += 1;
										else
											if($currentCount == 10)
												$currentCount = 0;
										
										//src="'.$YOUR_SERVER_URL.'/gbSuite/apps/'.$row['file_name'].'/images/'.$row['image'].'"
										$filePath = $_SERVER['PHP_ROOT'].'/gbSuite/apps/'.$row['file_name'].'/images/'.$row['image'];
										
										$desc = $row['description'];									
										
										if(file_exists($filePath))
										{
											//$html .= '<li><a class="application-link" href="'.$YOUR_SERVER_URL.'/gbSuite/home.php?app='.$row['name'].'"><div class="desktop-icon"><div class="desktop-icon-image"><img src="'.$YOUR_SERVER_URL.'/gbSuite/apps/'.$row['file_name'].'/images/'.$row['image'].'" width="80px" height="80px" /></div><div class="desktop-icon-short-name">'.$row['short_name'].'</div></div></a></li>';
											$html .= '<td><a onclick="return true;" title="'.$row['short_name'].' :: '.$desc.'" class="Tips1 application-link" href="'.$YOUR_SERVER_URL.'/gbSuite/home.php?app='.$row['name'].'"><div  class="desktop-icon"><div class="desktop-icon-image"><img src="'.$YOUR_SERVER_URL.'/gbSuite/apps/'.$row['file_name'].'/images/'.$row['image'].'" width="48px" height="48px" /></div><div class="desktop-icon-short-name">'.$row['short_name'].'</div></div></td>';											
										}
										else
											$html .= '<td><a onclick="return true;" title="'.$row['short_name'].' :: '.$desc.'"  class="Tips1 application-link" href="'.$YOUR_SERVER_URL.'/gbSuite/home.php?app='.$row['name'].'"><div class="desktop-icon"><div class="desktop-icon-image"><img src="'.$YOUR_SERVER_URL.'/gbSuite/apps/default.gif" width="48px" height="48px" /></div><div class="desktop-icon-short-name">'.$row['short_name'].'</div></div></td>';
																																	
									}
			
			$html .= '</tr></table>';									
			$html .= '<input type="hidden" id="desktop-icon-page-count" value="'.$count.'"/>';
																	
			$html .= '</ul>
							</div>
							<!--a href="#" id="move-right"><img src="'.$YOUR_SERVER_URL.'/gbSuite/apps/application_bar/images/arrow_right.png" /></a-->
							</div>
					</div>';
					
			echo $html;			
		}
		
		public function renderHTML()
		{
			if($this->html != "")						
				echo $this->html;
			else
				$this->renderBar();                                                                                                                                                                                                                                                                                                                                                                                                                                                   
		}
	}
?>