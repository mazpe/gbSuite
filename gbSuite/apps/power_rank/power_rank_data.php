<?php
/*
 * Created on Jun 19, 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 include("../../util/connection.php");
 include("power_rank.php");
  
 $reader_root = "data";
 $reader_id = "id";
 $totalProperty = "totalCount";
 
 $connection = new Connection();

 $sort = str_replace("-", "%", $_GET['sort']);
 $order = $_GET['order'];
 
 $dateFrom = $_GET['date_from'];
 $dateTo = $_GET['date_to'];
 
 //$where = str_replace("-", " ", $_GET['where']);
 //$where = str_replace("\\", "", $where);
 
 $time = time(); 
 $tableName = "power_rank_temp_$time";
	
/* $query = "CREATE TABLE $tableName AS select R.sales_person, R.employee_id, SUM(units_sold) AS units_sold, 0 AS us_rank, SUM(total_front_end) AS total_front_end, 0 AS tfe_rank, SUM(total_back_end) AS total_back_end, 0 AS tbe_rank, SUM(total_gross) AS total_gross, 0 AS tg_rank, SUM(total_score) AS total_score, 0 AS power_rank, uid  
		from app_rpt_power_rank R inner join info using(employee_id)
		$where GROUP BY R.sales_person";
*/
 $query = "CREATE TABLE $tableName AS 
			SELECT I.name AS sales_person, I.employee_id, SUM(units_sold) AS units_sold, 0 AS us_rank, SUM(total_front_end) AS total_front_end, 0 AS tfe_rank, SUM(total_back_end) AS total_back_end, 0 AS tbe_rank, SUM(total_gross) AS total_gross, 0 AS tg_rank, SUM(total_score) AS total_score, 0 AS power_rank, uid  
				FROM info I LEFT JOIN app_rpt_power_rank R 
					ON I.employee_id = R.employee_id AND dl_date BETWEEN '$dateFrom' AND '$dateTo'  
				WHERE title = 'Salesperson' AND I.employee_id IS NOT NULL AND I.active = 1  
				GROUP BY I.name";

 mysql_query($query);	
 
 $query = "SELECT * FROM power_rank_setup";
 $powerRankSetup = $connection->get_row($query);

 $unitsMultiplier = $powerRankSetup['units_multiplier'];
 $unitsPercentage = ($powerRankSetup['units_percentage'] > 0 ? $powerRankSetup['units_percentage'] / 100 : 0);
 $frontPercentage = ($powerRankSetup['front_percentage'] > 0 ? $powerRankSetup['front_percentage'] / 100 : 0);
 $backPercentage = ($powerRankSetup['back_percentage'] > 0 ? $powerRankSetup['back_percentage'] / 100 : 0);
 $totalPercentage = ($powerRankSetup['total_percentage'] > 0 ? $powerRankSetup['total_percentage'] / 100 : 0);

 $query = "UPDATE $tableName SET total_score =  ((units * $unitsMultiplier) * $unitsPercentage) + (total_front_end * $frontPercentage) + (total_back_end * $backPercentage) + ((total_front_end + total_back_end) * $totalPercentage) / 4";
 $results = mysql_query($query);
 
 PowerRank::updatePowerRank($tableName, $connection);

 $query = "SELECT * FROM $tableName order by $sort $order";
 $rs = $connection->exec_query($query);
 
 $row_count = mysql_num_rows($rs);
 
 $rows = Array();
 
 for($i = 0;$i < $row_count; $i++)
	$rows[] = mysql_fetch_array($rs, MYSQL_ASSOC);

 $query = "DROP TABLE $tableName";
 $connection->exec_query($query);
  
 $json = Array();

 $json[$reader_root] = $rows;
 $json[$reader_id] = 'id'; 
   
 echo "".json_encode($json)."";
?>