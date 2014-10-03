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

if($sort == "1500" || $sort == "(999)")			
	if(strpos($sort, "+") === false)				
		$sort .= "+";
 $sort = str_replace("`", "", $sort);

 $dealerViewId = $_GET['dealer_view_id'];
 $dateFrom = $_GET['date_from'];
 $dateTo = $_GET['date_to'];
  		
 //$where = str_replace("-", " ", $_GET['where']);
 //$where = str_replace("\\", "", $where);


	$fields = array("`team`", "`(999)+`", "`(999)+%`", "`(998-500)`", "`(998-500)%`", "`(499-0)`", "`(499-0)%`", "`1-499`", "`1-499%`", "`500-1499`", "`500-1499%`", "`1500+`", "`1500+%`", "avgfront", "avgback", "mtdavg");
	$totalType = array("none","sum", "avg", "sum", "avg", "sum", "avg", "sum", "avg", "sum", "avg", "sum", "avg", "avgUnits", "avgUnits", "mtdavg");

	$fieldsQuery = "";

	for($i = 0; $i < sizeof($fields); $i++)
	{
		if($fields[$i] == 'mtdavg')
			$fieldsQuery .= ($fieldsQuery != "" ? "," : "")."((SUM(avgfront) + SUM(avgback))/SUM(`(999)+` + `(998-500)` + `(499-0)` + `1-499` + `500-1499` + `1500+`)) AS mtdavg";
		else
			if($totalType[$i] != "none")
				$fieldsQuery .= ($fieldsQuery != "" ? "," : "")." SUM(".$fields[$i].") AS ".$fields[$i];
			else
			{
				if($fields[$i] == '`team`')
					$fieldsQuery .= ($fieldsQuery != "" ? "," : "")."IFNULL(".$fields[$i].", 'Other') AS team";
				else
					$fieldsQuery .= ($fieldsQuery != "" ? "," : "").$fields[$i];
			}
		}

	$query = "SELECT $fieldsQuery 
				FROM info I JOIN dealer_view D LEFT JOIN app_rpt_gross_profit_analisys R
				ON I.employee_id = R.employee_id AND dl_date BETWEEN '$dateFrom' AND '$dateTo'
				LEFT JOIN (SELECT T.id, team_id, T.dealer_view_id, T.team, TM.uid
							FROM team T INNER JOIN team_member TM 
								ON T.id = TM.team_id) T 
				ON I.uid = T.uid AND D.id = T.dealer_view_id 
				WHERE D.id = $dealerViewId AND I.department LIKE '%Sales%' AND title IN ('Salesperson','Sales Manager') AND I.employee_id IS NOT NULL  
				GROUP BY team ORDER BY `$sort` $order, team ASC";

/* $query = " SELECT IFNULL(`team`, 'Other') AS team, SUM(`(999)+`) AS `(999)+`, SUM(`(999)+%`) AS `(999)+%`, SUM(`(998-500)`) AS `(998-500)`, " .
 		" SUM(`(998-500)%`) AS `(998-500)%`, SUM(`(499-0)`) AS `(499-0)`, SUM(`(499-0)%`) AS `(499-0)%`, SUM(`1-499`) AS `1-499`, SUM(`1-499%`) AS `1-499%`, " .
 		" SUM(`500-1499`) AS `500-1499`, SUM(`500-1499%`) AS `500-1499%`, SUM(`1500+`) AS `1500+`, SUM(`1500+%`) AS `1500+%`, SUM(avgfront) AS avgfront, SUM(avgback) AS avgback, SUM(mtdavg) AS mtdavg FROM info I JOIN dealer_view D " .
 		" LEFT JOIN team_member TM ON I.uid = TM.uid LEFT JOIN team T ON TM.team_id = T.id AND D.id = T.dealer_view_id LEFT JOIN app_rpt_gross_profit_analisys R ON I.employee_id = " .
 		" R.employee_id " .
 		" $where " .
 		" GROUP BY team " .
 		" ORDER BY `$sort` $order";
*/
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

