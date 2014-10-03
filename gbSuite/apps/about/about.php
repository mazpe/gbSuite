<?php
include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
/*
 * Created on 27/10/2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
 class About extends Application{
 	
 	private $idApp;//Aplication id to find about;
 	
 	public function __construct()
 	{
 		$this->appId=45;
 	}
 	
 	public function isInstalled()
 	{
 		return true;
 	}
 	
 	public function renderHTML()
 	{
 		if(!$this->isInstalled())
		{
			echo $this->notInstalledMessage();
			return;
		}
			
			if(!isset($_GET['idApp'])){
			$this->idApp=$this->appId;			
 			}else{
 			$this->idApp=$_GET['idApp'];	
 			}
 		
 		$info=$this->getApplicationInformation();
 		$this->html.="<table class=about-app><tr>
				<td>
					<div class=about-app-name><a href=/gbSuite/home.php?app=$info[name] >$info[title]</a></div>
				<table>
					<tr>
						<td>
							<div class=about-app-picture>
								<img src=/gbSuite/apps/$info[name]/images/$info[image]>
							</div>
						</td>
						<td class=about-app-content valign=top>
							$info[description]							
						</td>
					</tr>
				</table>";
				$rs=$this->connection->exec_query("select * from user_apps where uid='".$this->user->getUID()."' and app_id=$this->idApp");
				if(mysql_num_rows($rs)==0)
				$this->html.="<div class=about-app-button>
						<input class=add-app name=add-app type=button value=\"Add to gbSuite\"/>
					</div>" ;
				
				$this->html.="</td></tr></table>";
				echo $this->html;		

 }
 
 
 private function getApplicationInformation(){
 	$sql="select name, description, status, title, image, icon type from apps where app_id=$this->idApp";
 	
 	return $this->connection->get_row($sql);
 }
 
 	}
?>
