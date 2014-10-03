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
 
 $dealerViewId = $_GET['dealer_view_id'];
 $dateFrom = $_GET['date_from'];
 $dateTo = $_GET['date_to'];
 
 //$where = str_replace("-", " ", $_GET['where']);
 //$where = str_replace("\\", "", $where);
 
 /*$query = "SELECT team, SUM(`units`) AS `units`,SUM(`goal`) AS `goal`,SUM(`track`) AS `track`,SUM(`new`) AS `new`,SUM(`used`) AS `used`,SUM(`gross_front`) AS `gross_front`,SUM(`gross_back`) AS `gross_back`,SUM(`gross_total`) AS `gross_total`, SUM(`mtd_avg`) AS `mtd_avg`, 
			SUM(showroom) AS showroom, SUM(`sold_sh`) AS `sold_sh`, IF(SUM(showroom) > 0, (SUM(`sold_sh`) * 100) / SUM(DISTINCT showroom), 0) AS `close_sh%`, 
			SUM(iphone) AS iphone, SUM(`sold_ip`) AS `sold_ip`, IF(SUM(iphone) > 0, (SUM(`sold_ip`) * 100) / SUM(DISTINCT iphone), 0) AS `close_ip%`, 
			SUM(ileads) AS ileads, SUM(`sold_il`) AS `sold_il`, IF(SUM(ileads) > 0, (SUM(`sold_il`) * 100) / SUM(DISTINCT ileads), 0) AS `close_il%`, uid 
			FROM (
			SELECT IFNULL(`team`, 'Other') AS team, SUM(`units`) AS `units`,SUM(`goal`) AS `goal`,SUM(`track`) AS `track`,SUM(`new`) AS `new`,SUM(`used`) AS `used`,SUM(`gross_front`) AS `gross_front`,SUM(`gross_back`) AS `gross_back`,SUM(`gross_total`) AS `gross_total`, SUM(`mtd_avg`) AS `mtd_avg`, 
			SUM(DISTINCT L.showroom) AS showroom, SUM(`sold_sh`) AS `sold_sh`, IF(L.showroom > 0, (SUM(`sold_sh`) * 100) / SUM(DISTINCT L.showroom), 0) AS `close_sh%`, SUM(DISTINCT L.iphone) AS iphone, SUM(`sold_ip`) AS `sold_ip`, IF(L.iphone > 0, (SUM(`sold_ip`) * 100) / SUM(DISTINCT L.iphone), 0) AS `close_ip%`, 
			SUM(DISTINCT L.ileads) AS ileads, SUM(`sold_il`) AS `sold_il`, IF(L.ileads > 0, (SUM(`sold_il`) * 100) / SUM(DISTINCT L.ileads), 0) AS `close_il%`, I.uid FROM info I JOIN dealer_view D LEFT JOIN team_member TM ON I.uid = TM.uid LEFT JOIN team T ON TM.team_id = T.id AND D.id = T.dealer_view_id LEFT JOIN app_rpt_sales_department R ON I.employee_id = R.employee_id LEFT JOIN lead_control L ON L.uid = I.uid AND D.id = L.dealer_view_id 
			$where AND I.department LIKE '%Sales%' AND title IN ('Salesperson','Sales Manager') AND I.employee_id IS NOT NULL GROUP BY team, name) T
			GROUP BY team ORDER BY `$sort` $order";
 	*/
 	
 $query = "SELECT DATEDIFF(`to`, `from`) + 1 AS days 
						FROM dealer_view
						WHERE id = $dealerViewId";
						
 $monthDayCount = $connection->get_value($query);
 $currentDay = date("d");

/* $query = "SELECT IFNULL(team, 'Other') AS team, I.uid, SUM(`units`) AS `units`, IF(G.`recommit` > 0, G.recommit, G.goals) AS `goal`, SUM((units / $currentDay) * $monthDayCount) AS `track`,SUM(`new`) AS `new`,SUM(`used`) AS `used`, SUM(`gross_front`) AS `gross_front`,SUM(`gross_back`) AS `gross_back`,
					SUM(`gross_total`) AS `gross_total`,SUM(`mtd_avg`) AS `mtd_avg`, L.showroom, SUM(`sold_sh`) AS `sold_sh`, 0 AS `close_sh%`, L.iphone, 
					SUM(`sold_ip`) AS `sold_ip`, 0 AS `close_ip%`, L.ileads, SUM(`sold_il`) AS `sold_il`, 0 AS `close_il%`
				FROM info I JOIN dealer_view D LEFT JOIN app_rpt_sales_department R
				ON I.employee_id = R.employee_id AND dl_date BETWEEN '$dateFrom' AND '$dateTo'
				LEFT JOIN (SELECT T.id, team_id, T.dealer_view_id, T.team, TM.uid
						FROM team T INNER JOIN team_member TM 
						ON T.id = TM.team_id) T 
				ON I.uid = T.uid AND D.id = T.dealer_view_id LEFT JOIN lead_control L
				ON L.uid = I.uid AND L.dealer_view_id = D.id LEFT JOIN goals_settings G
				ON G.uid = I.uid AND G.dealer_view_id = D.id
				WHERE D.id = $dealerViewId AND I.title = 'Salesperson' AND I.employee_id IS NOT NULL and I.active = 1  
				GROUP BY team ORDER BY team, `$sort` $order";
*/

	$query = "SELECT team, SUM(`units`) AS `units`, SUM(`goal`) AS goal, SUM(`track`) AS `track`,SUM(`new`) AS `new`,SUM(`used`) AS `used`, SUM(`gross_front`) AS `gross_front`,SUM(`gross_back`) AS `gross_back`,
							SUM(`gross_total`) AS `gross_total`,SUM(`mtd_avg`) AS `mtd_avg`, SUM(showroom) AS showroom, SUM(`sold_sh`) AS `sold_sh`, 0 AS `close_sh%`, SUM(iphone) AS iphone, 
							SUM(`sold_ip`) AS `sold_ip`, 0 AS `close_ip%`, SUM(ileads) AS ileads, SUM(`sold_il`) AS `sold_il`, 0 AS `close_il%`
						FROM
						(SELECT IFNULL(team, 'Other') AS team, SUM(`units`) AS `units`, SUM( DISTINCT IF(G.`recommit` > 0, G.recommit, G.goals)) AS `goal`, SUM((units / $currentDay) * $monthDayCount) AS `track`,SUM(`new`) AS `new`,SUM(`used`) AS `used`, SUM(`gross_front`) AS `gross_front`,SUM(`gross_back`) AS `gross_back`,
							SUM(`gross_total`) AS `gross_total`,SUM(`mtd_avg`) AS `mtd_avg`, L.showroom, SUM(`sold_sh`) AS `sold_sh`, 0 AS `close_sh%`, L.iphone, 
							SUM(`sold_ip`) AS `sold_ip`, 0 AS `close_ip%`, L.ileads, SUM(`sold_il`) AS `sold_il`, 0 AS `close_il%`
						FROM info I JOIN dealer_view D LEFT JOIN app_rpt_sales_department R
						ON I.employee_id = R.employee_id AND dl_date BETWEEN '$dateFrom' AND '$dateTo'
						LEFT JOIN (SELECT T.id, team_id, T.dealer_view_id, T.team, TM.uid
								FROM team T INNER JOIN team_member TM 
								ON T.id = TM.team_id) T 
						ON I.uid = T.uid AND D.id = T.dealer_view_id LEFT JOIN lead_control L
						ON L.uid = I.uid AND L.dealer_view_id = D.id LEFT JOIN goals_settings G
						ON G.uid = I.uid AND G.dealer_view_id = D.id
						WHERE D.id = $dealerViewId AND I.title = 'Salesperson' AND I.employee_id IS NOT NULL AND I.active
						GROUP BY team, name ORDER BY team) T
						GROUP BY team ORDER BY `$sort` $order";
						
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