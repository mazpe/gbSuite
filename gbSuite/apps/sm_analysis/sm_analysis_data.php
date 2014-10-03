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
 
 /*$query = "SELECT I.`name`, SUM(`units`) AS `units`,SUM(`goal`) AS `goal`,SUM(`track`) AS `track`,SUM(`new`) AS `new`,SUM(`used`) AS `used`,SUM(`gross_front`) AS `gross_front`,SUM(`gross_back`) AS `gross_back`,SUM(`gross_total`) AS `gross_total`,SUM(`mtd_avg`) AS `mtd_avg`, I.uid,
									L.showroom, SUM(`sold_sh`) AS `sold_sh`, IF(L.showroom > 0, SUM((`sold_sh` * 100) / L.showroom), 0) AS `close_sh%`, 
									L.iphone, SUM(`sold_ip`) AS `sold_ip`, IF(L.iphone > 0, SUM((`sold_ip` * 100) / L.iphone), 0) AS `close_ip%`,
									L.ileads, SUM(`sold_il`) AS `sold_il`, IF(L.ileads > 0, SUM((`sold_il` * 100) / L.ileads), 0) AS `close_il%`
							FROM app_rpt_sales_department R INNER JOIN info I ON R.employee_id = I.employee_id INNER JOIN lead_control L
							ON L.uid = I.uid 
							$where GROUP BY I.name ORDER BY `$sort` $order";
 */
 
 $query = "SELECT DATEDIFF(`to`, `from`) + 1 AS days 
			FROM dealer_view
			WHERE id = $dealerViewId";
			
 $monthDayCount = $connection->get_value($query);
 $currentDay = date("d");
		
 $sums = array();
 $query = "";

 $query = "SELECT name, I.uid, SUM(`units`) AS `units`, IF(G.`recommit` > 0, G.recommit, G.goals) AS `goal`, SUM((units / $currentDay) * $monthDayCount) AS `track`,SUM(`new`) AS `new`,SUM(`used`) AS `used`, SUM(`gross_front`) AS `gross_front`,SUM(`gross_back`) AS `gross_back`,
					SUM(`gross_total`) AS `gross_total`,SUM(`mtd_avg`) AS `mtd_avg`, L.showroom, SUM(`sold_sh`) AS `sold_sh`, 0 AS `close_sh%`, L.iphone, 
					SUM(`sold_ip`) AS `sold_ip`, 0 AS `close_ip%`, L.ileads, SUM(`sold_il`) AS `sold_il`, 0 AS `close_il%`
			FROM info I JOIN dealer_view D LEFT JOIN app_rpt_sales_department R
			ON I.employee_id = R.employee_id AND dl_date BETWEEN '$dateFrom' AND '$dateTo' LEFT JOIN lead_control L
			ON L.uid = I.uid AND L.dealer_view_id = D.id LEFT JOIN goals_settings G
			ON G.uid = I.uid AND G.dealer_view_id = D.id
			WHERE D.id = $dealerViewId AND I.title = 'Salesperson' AND I.employee_id IS NOT NULL AND I.active = 1   
			GROUP BY I.name, name ORDER BY `$sort` $order";
	 
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