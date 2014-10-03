<?php
/*
 * Created on Jun 19, 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 	include($_SERVER['PHP_ROOT']."/gbSuite/util/connection.php");

 	$reader_root = "data";

 	$reader_id = "id";

 	$totalProperty = "totalCount";

 	$connection = new Connection();

 	$sort = str_replace("-", "%", $_GET['sort']);
 	$order = $_GET['order'];
 
 	$dateFrom = "";
 	$dateTo = "";
 
 	$where = str_replace("-", " ", $_GET['where']);
 	$where = str_replace("\\", "", $where);

 	$dealerViewId = $_GET['dealer_view_id'];

	$query = "SELECT id, date_format(`from`, '%Y%m%d') AS `start`, date_format(`to`, '%Y%m%d') AS `end`, date_format(`from`, '%m/%d/%Y') AS `from`, date_format(`to`, '%m/%d/%Y') AS `to` FROM dealer_view WHERE CAST(date_format(now(), '%Y%m%d') AS DATE) BETWEEN `from` AND `to`";

	$dateRange = $connection->get_row($query);
	$condition = "";

	if($dateRange != null)
	{
		$dateFrom = $dateRange['start'];
		$dateTo = $dateRange['end'];
	}

 	$query = " SELECT SUM(new) AS new, SUM(used) AS used, SUM(units) AS units " .
 			 " FROM app_rpt_sales_department $where";

	$rs = $connection->exec_query($query);
	$row_count = mysql_num_rows($rs);

	//to get the goals
 	/*$query_goals="select sum(`units_promised`) as goals from ( select
					IFNULL(G.goals,0) as `units_promised`, IFNULL(sum(R.units_delivered),0) as units_delivered from info I
					left join goals_settings G on G.uid=I.uid and G.dealer_view_id=$dealerViewId left join
					app_rpt_sales_goals R using (employee_id) inner join dealer_view as DW
					on DW.id=G.dealer_view_id $where
					AND dealer_view_id = '$dealerViewId' and title='Salesperson' or dl_date is null GROUP BY
	 				I.name ) as tbl";*/
	 				
	$query_goals = "SELECT (new_units + used_units) as goals FROM dealership_goals where dealer_view_id = $dealerViewId";
 	$goals	= $connection->get_row($query_goals);

	$query = "SELECT SUM(total) AS gross_total FROM app_rpt_desk_log $where AND status <> 'B'";

	$desklogRow = $connection->get_row($query);
	 
	$goal = $goals['goals'];
	$goal_percent=($goals['goals']>0?($row['units']/$goals['goals'])*100:0);
	
 	$rows = Array();

 	for($i = 0;$i < $row_count; $i++)
		$rows[] = mysql_fetch_array($rs, MYSQL_ASSOC);

 	$json = Array();

	$rows[0]['goal'] = $goal;
 	$rows[0]['gross_total'] = $desklogRow['gross_total'];
 	
	$gross_query = "select sum(R.front + R.back + R.other) as used_total from app_rpt_desk_log R $where and nu = 'U' and status <> 'B'";
 	$result = $connection->get_row($gross_query);

 	$new_total = $rows[0]['gross_total'] - $result['used_total'];
 
 	$rows[0]['total_gross_new'] = $new_total;
 	$rows[0]['total_gross_used'] = $result['used_total'];
 	 	
 	$json[$reader_root] = $rows;
 	$json[$reader_id] = 'id';

 	echo "".json_encode($json)."";
?>