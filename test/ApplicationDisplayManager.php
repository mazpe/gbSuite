<?php

include_once $_SERVER['PHP_ROOT'].'/lib/core/init.php';
include_once $_SERVER['PHP_ROOT'].'/gbSuite/util/connection.php';
include_once($_SERVER['PHP_ROOT']."/gbSuite/configuration.php");


/*
 * Created by: Joel Cruz
 * Created on 22/08/2008
 *
 * This class is used to get the application that gonna be displayed by link
 * 
 *
 */

class ApplicationDispayManager
{
	/*
	 * Recieve the name of the application to show. 
	 */
	 
	 var $linkName;
	 var $linkId;
	 var $connection;
	 var $userInfo; //the actual user.
	 var $fields;
	// private $ordered;
	 	 
	public function __construct($linkName,$userInfo,$connection = null, $ordered = null)
	{
		$this->linkName = $linkName;
		$this->userInfo = $userInfo;
					
		$this->ordered = ($ordered == null ? false : $ordered);
					
		if($connection!=null)
			$this->connection = $connection;
		else
			$this->connection = new Connection();
			
		$this->fields = " A.* , if(UPL.ordering is null , A.ordering, UPL.ordering) as appOrdering ";
		$sql = "select link_id from link where name='". $this->linkName ."'";
		$this->linkId = $this->connection->get_value($sql);
	}
	
	/*
	 * @param	position :	the position where the applications must be 
	 * displayed. 
	 */
	public function getApplications($position)
	{	
		
			
		
		/*if($position == 'MAIN')
			echo $sql;*/
		
		if(!$this->ordered)
		{
			$frameworkApps = $this->getFrameworkApps($position);
			$toolsApps = $this->getToolsApps($position);
			$customApps = $this->getCustomApplications($position);
			return $allApps = array_merge($frameworkApps,$toolsApps,$customApps);
		}
		else
		{
			$sql .= $this->getFrameworkApps($position,false)." UNION ";
			$sql .= $this->getToolsApps($position,false)." UNION ";
			$sql .= $this->getCustomApplications($position,false);
			
			/*if($position == 'MAIN')
				echo $sql;*/
			
			$sql = $sql." order by appOrdering ASC";
			
			
			
			/*if($position == 'LEFT') 
				echo $sql;*/
				
			return toArray($this->connection->exec_query($sql));
		}
		
	}		
	
	
	public function getApplicationsSql($position)
	{
		$frameworkApps = $this->getFrameworkApps($position,false);
		$toolsApps = $this->getToolsApps($position,false);
		$customApps = $this->getCustomApplications($position,false);	
		
		return $frameworkApps.$toolsApps.$customApps;
	}
		
	
	/*
	 * Get the framework applications,
	 * this applications are view by all the users.
	 * This selects the application that are not active and are marked as always show.
	 */
	public function getFrameworkApps($position,$row = true)
	{
		
		if($position == "MAIN" and isset($_GET['action']))
		{
			$condition = " and A.name = '". $_GET['app'] ."' ";
		}
		else
		{
			$condition = "";
		}
		
		$sql = "/*there are the applications of the framework that always be displayed in all pages.*/ 
				select ".$this->fields."  
				from apps2 A
				left join user_profile_layout UPL on A.app_id = UPL.app_id and UPL.uid = '". $this->userInfo['uid'] ."'
				left join application_configuration AC on A.app_id = UPL.uid = '". $this->userInfo['uid'] ."'
				where type = 'framework' and always_show and if(AC.profile_link = 1 , 'MAIN',if(UPL.position is null, A.position, UPL.position)) =  '$position'
				and A.status = 'Active' $condition
				/*And the applications that are configured in the link_app table, this are loaded separated for simplicity*/
				UNION
				select distinct ".$this->fields."
				from link_app LA 
				inner join apps2 A using (app_id) 
				left join user_profile_layout UPL on A.app_id = UPL.app_id and UPL.uid = '1240079' 
				left join application_configuration AC on A.app_id = AC.app_id and AC.uid = '1240079' 
				where (LA.link_id = '" . $this->linkId . "' 
				or ( if(/*estatico*/1 = '". $this->linkId ."',AC.profile_link = 1,false)))
				and if(AC.profile_link = 1,/*estatico*/'MAIN',if(UPL.position is not null, UPL.position,A.position)) = '". $position ."'
				and A.status = 'Active' and type = 'framework' and not always_show
				$condition";
		
		/*if($position == 'LEFT')
			echo "<br>Getting Framework Applications for Position:$position <br>".$sql."<br>";*/
		
		if($row)
			return toArray($this->connection->exec_query($sql));
		else
			return $sql;
			
	}
	 
	/*
	 *	This method loads all the applications that are tools for each 
	 *	user. this methos vary the results based in the title of the actual logged user. 
	 */
	 public function getToolsApps($position,$row = true)
	 {
	 	
	 	if($position == "MAIN" and isset($_GET['action']))
		{
			$condition = " and A.name = '". $_GET['app'] ."' ";
		}
		else
		{
			$condition = "";
		}
	 	
	 	$sql = "
		select ". $this->fields ." 
		from apps2 A 
		left join user_profile_layout UPL on A.app_id = UPL.app_id and UPL.uid = '" . $this->userInfo['uid']  . "' 
		inner join link_app LA on LA.app_id = A.app_id 
		where type = 'tool' and (if(UPL.position is null , A.position , UPL.position ) = '$position' ) and status ='Active' and default_titles 
		like concat('%','[". $this->userInfo['title'] ."]','%') and LA.link_id = '". $this->linkId ."' or (always_show and status ='Active' and (if(UPL.position is null , A.position , UPL.position )) = '$position' 
		and type = 'tool' and (default_titles like concat('%','[". $this->userInfo['title'] ."]','%') or default_titles = 'PUBLIC')) 
		UNION
		/*there are the applications loaded from application_configuration*/
		select ".$this->fields." 
		from application_configuration AC 		 
		inner join apps2 A using(app_id) 
		left join user_profile_layout UPL on UPL.app_id = A.app_id and UPL.uid = '". $this->userInfo['uid'] ."'
		where AC.uid = '". $this->userInfo['uid'] ."' and A.type = 'tool' and A.status = 'Active'
		and not always_show and (default_titles like concat('%','[". $this->userInfo['title'] ."]','%') or default_titles = 'PUBLIC')
		and (/*estatico*/1 = '". $this->linkId ."' and AC.profile_link = '". $this->linkId ."') and 'MAIN' = '$position'";

		//if($position == 'MAIN')
		//	echo $sql;
		
	 	if($row)
	 		return toArray($this->connection->exec_query($sql));	
	 	else
	 		return $sql;
	 }
	 
	 public function getCustomApplications($position,$row = true)
	 {
	 	
	 	if($position == "MAIN" and isset($_GET['action']))
		{
			$condition = " and A.name = '". $_GET['app'] ."' ";
		}
		else
		{
			$condition = "";
		}
	 	
	 	$sql = "/*there are the applications configured in link_app table*/
					select ". $this->fields ."
					from link L  
					inner join link_app LA using(link_id)
					inner join apps2 A using(app_id) 
					inner join user_apps UA using(app_id)
					left join user_profile_layout UPL using(uid,app_id)
					where L.name = '". $this->linkName ."' and UA.uid = '". $this->userInfo['uid'] ."' and A.status = 'Active' and 
					(if(UPL.position is null, A.position,UPL.position) = '$position') and (type = 'report' or type = 'custom') $condition
					/*there are the applications of profile_layout_application , this querys are separated by simplicity */
					UNION
					select ". $this->fields ." 
					from apps2 A
					left join application_configuration AC on AC.app_id = A.app_id and AC.uid = '". $this->userInfo['uid'] ."'
					left join user_profile_layout UPL on UPL.app_id = A.app_id and UPL.uid = '". $this->userInfo['uid'] ."' 
					where AC.profile_link = '1' and (1/*estatico*/ = '". $this->linkId ."' and /*estatico*/'MAIN' = '$position') and (type = 'custom' or type = 'report') 
					$condition";
	 	
	 	//if($position == 'MAIN')
	 	//	echo "Getting Custom Applications for Position:$position <br>".$sql."<br>"; 
	 	
	 	if($row)
	 		return toArray($this->connection->exec_query($sql));
	 	else
	 		return $sql;
	 }	
}

function toArray($result)
{
	$array = array(); 
		
	while($row = mysql_fetch_array($result))
	{
		$array[] =$row; 
	}
	
	return $array;
}
?>