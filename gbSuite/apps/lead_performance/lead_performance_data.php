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

 $where = str_replace("-", " ", $_GET['where']);
 $where = str_replace("\\", "", $where);

 $query = " SELECT SUM(sold_sh) AS sold_sh, SUM(sold_ip) AS sold_ip, SUM(sold_il) AS sold_il " .
 		" FROM app_rpt_sales_department $where";

 $rs = $connection->exec_query($query);
 $row_count = mysql_num_rows($rs);

 $rows = Array();

 for($i = 0;$i < $row_count; $i++)
	$rows[] = mysql_fetch_array($rs, MYSQL_ASSOC);

 $leadControlQuery = "SELECT SUM(showroom) AS showroom, SUM(iphone) AS iphone, SUM(ileads) AS ileads FROM lead_control WHERE dealer_view_id = ".$_GET['dealer_view_id'];	

 $rs = $connection->exec_query($leadControlQuery);
 $row_count = mysql_num_rows($rs);

 for($i = 0;$i < $row_count; $i++)
	$rows[] = mysql_fetch_array($rs, MYSQL_ASSOC);
	
 $json = Array();

 $json[$reader_root] = $rows;
 $json[$reader_id] = 'id';

 echo "".json_encode($json)."";
?>