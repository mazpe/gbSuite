<?php
/*
 * Created on Oct 8, 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 include_once($_SERVER['PHP_ROOT']."/gbSuite/util/connection.php");

 $con=new Connection();

	if($_GET['update']=='true'){
			userUpdateApp();
	}



 function setDefaultApp($id,$title){
 	global $con;
 	$query_install=sprintf("insert into user_apps
(
select '%s',app_id from apps where status='Active ' and " .
		" ( install_title like
CONCAT('%%[',(select concat(title) as title" .
		" from info where uid='%s'),']%%') or install_title='[ALL]') ",$id,$id);

$installed_app=getInstalledApp($id);
//Si tiene aplicaciones instaladas
 	if( sizeof($installed_app)>0 ){
		$installed_sql=" and app_id not in(";
		foreach($installed_app as $app_id){
			$installed_sql.=$app_id.',';
		}
		$installed_sql=substr($installed_sql,0,-1).'))';
		$query_install.=$installed_sql;
 	}else{
 		$query_install.=')';
 	}
//echo $query_install;

 $con->exec_query($query_install);

 //echo $query_install."<br><br>";
 //Install the aplication who need to be position into
 //aplication_configuration
 installPositionApps($id,$title);
 }

function getPosition($titles,$title){
	for($i=0; $i<sizeof($titles); $i++){
		if($titles[$i]==$title){
			return $i;
		}
	}
	return -1;
}
 /*Allows to configure applicaton for a specific user discriminated by title*/
 function installPositionApps($id,$title){
 $install_app_sql="select A.app_id,default_visibility,install_title,position_app from apps A
join user_apps UA on UA.app_id = A.app_id and UA.uid='$id'
left join application_configuration AC on A.app_id = AC.app_id and AC.uid = '$id'
where status='Active' and position_app is not NULL
and AC.id is null";
global $con;
$resultado=$con->exec_query($install_app_sql);

while($row=mysql_fetch_array($resultado)){

	//get the position for the aplication configuration position
	$pos=getPosition(explode(",",$row["install_title"]),"[".$title."]");
	//get the configuration
	$positions=explode(",",$row["position_app"]);
	$profile_link=1;
	$desktop_icon=1;
	$top_report=1;

	if($positions[$pos]=='[]')
		continue;

	if(( strpos($positions[$pos], "P"))===false){
		$profile_link=0;
	}
	if(( strpos($positions[$pos], "DI"))===false){
		$desktop_icon=0;
	}
	if(( ( strpos($positions[$pos], "R"))===false)){
		$top_report=0;
	}

	$sql="insert into application_configuration" .
			"(uid, app_id, visibility, profile_link, desktop_icon,top_report) values($id,$row[app_id],'$row[default_visibility]',$profile_link,$desktop_icon,$top_report)";

	$con->exec_query($sql);
//	echo "<br>".$sql."<br>";
}


 }

 /*return an array of applicacion installed to an specific user
  * identify by $id
  * */
 function getInstalledApp($id){
 	global $con;
 	$sql=sprintf("select app_id from user_apps where uid = '%d' ", $id);
 	$rows=$con->exec_query($sql);
 	$installed_app=array();
 	$i=0;

 	while( ($row= mysql_fetch_array($rows)))
 		$installed_app[$i++]=$row[0];

 	return $installed_app;

 }


	function userUpdateApp(){
		global $con;
		$sql_update="select uid,title from info where active=1";
		$rows=$con->exec_query($sql_update);
		while($row=mysql_fetch_array($rows)){
			setDefaultApp($row[0],$row[1]);
		}
	}
	//setDefaultApp('1240123');

?>
