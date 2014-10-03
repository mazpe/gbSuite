<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/power_rank/power_rank.php");
	
    class TopSalespersons extends Application
	{
		private $whereClause;
		
		
		public function __construct()
		{
			$this->appId = 53;	
		}

		public function renderHTML()
		{
			$query = "SELECT id, date_format(`from`, '%Y%m%d') AS `from`, date_format(`to`, '%Y%m%d') AS `to` FROM dealer_view WHERE now() BETWEEN `from` AND `to`";
			$dateRange = $this->connection->get_row($query);
				
			$this->whereClause="where dl_date between '".$dateRange['from']."' AND '".$dateRange['to']."'";
			
						
			$result=$this->getRank();
		
			//$query = "select name,uid from info where title='Salesperson' ORDER BY title LIMIT 0, 5";
			//$result = $this->connection->exec_query($query);
			
			echo "<table width='100%'>";
			?>
			<tr>
			<td align='right'><div class='friend-section-label friend-section'><a href='/gbSuite/home.php?app=power_rank'>View All</a></div></td>
			<td class='user-links-url'align='right'>Score</td>
			</tr>
			<?
			
				
			while($row = mysql_fetch_assoc($result))
			{
				$uid = $row['uid'];
				$query = "select substr(initials, 1, 1) as initial, last_name from info where uid = '$uid'";
				$data= $this->connection->get_row($query);
						
				?>
					<tr>
						<td width='70%' class='user-links-url'><?= $row['power_rank'] ?>. <?echo $data['initial'].". ".$data['last_name'];?></td>
						<td width='30%' class='user-links-url' align='right'><?= number_format(ceil($row['total_score']))?></td>
					</tr>
				<?	
			
			}
			echo "</table>";					
		}
		
		private function getRank()
		{	
			$time = time(); 
			$tableName = "power_rank_temp2_$time";
				
			$sql = " CREATE TABLE $tableName AS (select R.sales_person, R.employee_id, SUM(units_sold) AS units_sold, 0 AS us_rank, SUM(total_front_end) AS total_front_end, 0 AS tfe_rank, SUM(total_back_end) AS total_back_end, 0 AS tbe_rank, SUM(total_gross) AS total_gross, 0 AS tg_rank, SUM(total_score) AS total_score, 0 AS power_rank, uid    
					from app_rpt_power_rank R inner join info using(employee_id) 
					".$this->whereClause." GROUP BY R.sales_person) ";
										
			$this->connection->exec_query($sql);
			
			PowerRank::updatePowerRank($tableName,$this->connection);
			
			
			$query = "SELECT * FROM power_rank_setup";
			$powerRankSetup = $this->connection->get_row($query);
			
			$unitsMultiplier = $powerRankSetup['units_multiplier'];
			$unitsPercentage = ($powerRankSetup['units_percentage'] > 0 ? $powerRankSetup['units_percentage'] / 100 : 0);
			$frontPercentage = ($powerRankSetup['front_percentage'] > 0 ? $powerRankSetup['front_percentage'] / 100 : 0);
			$backPercentage = ($powerRankSetup['back_percentage'] > 0 ? $powerRankSetup['back_percentage'] / 100 : 0);
			$totalPercentage = ($powerRankSetup['total_percentage'] > 0 ? $powerRankSetup['total_percentage'] / 100 : 0);
			
			/*$sql = "UPDATE $tableName SET total_score =  ((units * $unitsMultiplier) * $unitsPercentage) + (total_front_end * $frontPercentage) + (total_back_end * $backPercentage) + ((total_front_end + total_back_end) * $totalPercentage) / 4";
			$results = mysql_query($sql);
			*/
			$limit=5;
			
			$sql = " SELECT I.uid , total_score, power_rank FROM $tableName inner join info I using(employee_id) order by power_rank asc limit 0, $limit";
	
			//$results = $this->connection->exec_query($sql);
			$results = $this->connection->exec_query($sql);
			
			$sql="drop table $tableName";
			$this->connection->exec_query($sql);
			return $results;
		}		
	}
?>