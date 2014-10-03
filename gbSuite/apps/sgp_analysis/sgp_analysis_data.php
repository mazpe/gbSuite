<?php
/*
 * Created on Jun 19, 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 include("../../util/connection.php");
  
 $reader_root = "data";

 $reader_id = "id";
 
 $totalProperty = "totalCount";
 
 $connection = new Connection();
	
 $sort = str_replace("-", "%", $_GET['sort']);
 $order = $_GET['order'];
 
 //$where = str_replace("-", " ", $_GET['where']);
 //$where = str_replace("\\", "", $where);
 
 $dealerViewId = $_GET['dealer_view_id'];
 $dateFrom = $_GET['date_from'];
 $dateTo = $_GET['date_to'];
 
 /*$query = "SELECT `sales_person_1`,SUM(`(999)+`) AS `(999)+`,SUM(`(999)+%`) AS `(999)+%`,SUM(`(998-500)`) AS `(998-500)`," .
 		"SUM(`(998-500)%`) AS `(998-500)%`,SUM(`(499-0)`) AS `(499-0)`,SUM(`(499-0)%`) AS `(499-0)%`,SUM(`1-499`) AS `1-499`," .
 		"SUM(`1-499%`) AS `1-499%`,SUM(`500-1499`) AS `500-1499`,SUM(`500-1499%`) AS `500-1499%`,SUM(`1500+`) AS `1500+`," .
 		"SUM(`1500+%`) AS `1500+%`,SUM(avgfront) AS avgfront,SUM(avgback) AS avgback,SUM(mtdavg) AS mtdavg, uid " .
 		"FROM app_rpt_gross_profit_analisys R INNER JOIN info I ON R.employee_id = I.employee_id " .
 		"$where GROUP BY sales_person_1 ORDER BY `$sort` $order";
 */
 	$fields = array("`name`", "`(999)+`", "`(999)+%`", "`(998-500)`", "`(998-500)%`", "`(499-0)`", "`(499-0)%`", "`1-499`", "`1-499%`", "`500-1499`", "`500-1499%`", "`1500+`", "`1500+%`", "avgfront", "avgback", "mtdavg");
	$totalType = array("none", "sum", "avg", "sum", "avg", "sum", "avg", "sum", "avg", "sum", "avg", "sum", "avg", "avgUnits", "avgUnits", "mtdavg");			
			
	for($i = 0; $i < sizeof($fields); $i++)
	{
		if($fields[$i] == 'mtdavg')
			$query .= ($query != "" ? "," : "")."((SUM(avgfront) + SUM(avgback))/SUM(`(999)+` + `(998-500)` + `(499-0)` + `1-499` + `500-1499` + `1500+`)) AS mtdavg";
		else 
			if($totalType[$i] != 'none')
				$query .= ($query != "" ? "," : "")."SUM(".$fields[$i].") AS ".$fields[$i];
			else
				$query .= ($query != "" ? "," : "").$fields[$i];
	}				
 
 $query = "SELECT $query, uid  
			FROM info I LEFT JOIN app_rpt_gross_profit_analisys R  
				ON R.employee_id = I.employee_id AND dl_date BETWEEN '$dateFrom' AND '$dateTo'  
			WHERE title = 'Salesperson' AND I.employee_id IS NOT NULL AND I.active = 1 
			GROUP BY name ORDER BY `$sort` $order";					 
 
 $rs = $connection->exec_query($query);
 
 $row_count = mysql_num_rows($rs);
 
 $rows = Array();
 
 for($i = 0;$i < $row_count; $i++)
	$rows[] = mysql_fetch_array($rs, MYSQL_ASSOC);
 
 $json = Array();

 $json[$reader_root] = $rows;
 $json[$reader_id] = 'id'; 
   
 echo "".json_encode($json)."";
?>