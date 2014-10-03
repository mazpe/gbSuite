<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class PowerRank extends Application
	{
		var $printing = false;
		private $option = null;
		private $sort = null;
		private $order = null;
		private $from = null;
		private $to = null;		
		private $params = array();
		
		public function __construct()
		{
			$this->appId = 34;
		}
		 
		public function print_report($params)
		{
			?>
				<link rel="stylesheet" href="/css/report_print.css" type="text/css" media="screen, print" charset="utf-8" />				
			<?
			
			$this->printing = true;
				
			$this->renderHTML();
		}
		 
		public function renderHTML()
		{			
			$where = "";
			$start = "";
			$end = "";
			$dateRange = "";
			
			$title = $this->user->getCurrentProfileAttribute("title");
			$my_title = $this->user->getTitle();
			
			if(!$this->isInstalled())
			{
				echo $this->notInstalledMessage();
				return;
			}
		
			$this->sort = "power_rank";
			$this->order = "ASC";
			$this->from = "";
			$this->to = "";			
			
			$this->params = array_merge($_GET, $_POST);
			
			if(isset($this->params['sort']))
				$this->sort = $this->params['sort'];
				
			if(isset($this->params['order']))
				$this->order = $this->params['order'];
			
			$currentDate = $this->connection->get_value("SELECT date_format(now(), '%m/%d/%Y') AS currentDate");
				
			if(isset($this->params['option']))
			{
				$this->option = $this->params['option'];
		
				if($this->option == "View")
				{
					$this->from = $this->params['from'.$this->appId];
					$this->to = $this->params['to'.$this->appId];
					
					$dateRange = $this->from." - ".$this->to;
											
					$start = substr($this->from, 6, 4).substr($this->from, 0, 2).substr($this->from, 3, 2);
					$end = substr($this->to, 6, 4).substr($this->to, 0, 2).substr($this->to, 3, 2);
											
					$where = "WHERE dl_date >= '$start' AND dl_date <= '$end' ";
				}
				else //Verify if correspond to a dealer view
				{	
					$query = "SELECT date_format(`from`, '%Y%m%d') AS `start`, date_format(`to`, '%Y%m%d') AS `end`, date_format(`from`, '%m/%d/%Y')AS `from`, date_format(`to`, '%m/%d/%Y')AS `to` FROM dealer_view WHERE label = '$this->option'";
					$row = $this->connection->get_row($query);
					
					if($row != null)
					{
						$start = $row['start'];
						$end = $row['end'];
						
						$this->from = $row['from'];
						$this->to = $row['to'];
						
						$dateRange = $row['from']." - ".$row['to']; 
						$where = "WHERE dl_date >= '$start' AND dl_date <= '$end' ";
					}	
				}	
			}
			else						
			{
				$row = null;
				
				$query = "SELECT date_format(`from`, '%Y%m%d') AS `from`, date_format(`to`, '%Y%m%d') AS `to`, date_format(`from`, '%m/%d/%Y') AS `start`, date_format(`to`, '%m/%d/%Y') AS `end` FROM dealer_view WHERE now() BETWEEN `from` AND `to`";
				
				$dateRange = $this->connection->get_row($query);
				$condition = "";
			
				if($dateRange != null)
				{
					$condition = "dl_date BETWEEN '".$dateRange['from']."' AND '".$dateRange['to']."'";
					
					$this->from = $dateRange['start'];
					$this->to = $dateRange['end'];
				}	 
				else
				{
					$query = "SELECT DATEDIFF(DATE_ADD('".date("Y").date("m")."01', INTERVAL 1 MONTH), '".date("Y").date("m")."01') AS days";
					$days = $this->connection->get_value($query);
					
					$condition = "MONTH(dl_date) = MONTH(now())";
					$this->from = date("m")."/01/".date("Y");
					$this->to = date("m")."/".$days."/".date("Y");					
				}			
					
				$where = "WHERE $condition";

				$dateRange = $this->from." - ".$this->to;					
			}
			
			echo "<div class='app-content'>";
			echo "<div class='report-title'>Power Rank<br/>$dateRange</div>";
			//echo "<div class='report-subtitle'>September 2008</div>";
			
			$selectionBar = 
				'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=power_rank">
					<div id="filter" class="" style="text-align:center;">
						<input id=desklog-sort'.$this->appId.' type=hidden name=sort value="'.$this->sort.'" />
						<input id=desklog-order'.$this->appId.' type=hidden name=order value="'.$this->order.'" />
						<input id=desklog-option'.$this->appId.' type=hidden name=option value="'.$this->option.'" />
						
						<input id="dealer-view-button'.$this->appId.'" class="desklog-dealer-options" type="button" value="Month" onclick="showDealerView('.$this->appId.')" />&nbsp
						<input id="date-range-button'.$this->appId.'" class="desklog-dealer-options" type="button" value="Date Range" onclick="showDateRange('.$this->appId.')" />&nbsp
						<input id="desklog-filter-button'.$this->appId.'" class="desklog-dealer-options" type="button" style="display:none;" value="Filter" onclick="showDesklogFilter('.$this->appId.')" />
						<div id="desklog-filter-container'.$this->appId.'" style="display:none;">
							<div id="desklog-dealer-view'.$this->appId.'" style="display:none;">';
							
							$query = "SELECT label FROM dealer_view";
							$rs = $this->connection->exec_query($query);
							
							while($row = mysql_fetch_assoc($rs))
								$selectionBar .= '<input type=submit name="option" class="desklog-dealer-button" value="'.$row['label'].'" />&nbsp';
																											
			$selectionBar .= '<br/>										
								<input id="sbox-btn-close'.$this->appId.'" type="button" class="button-link" onclick="closeFilter('.$this->appId.')" value="Close" />
							</div>
							<div id="desklog-date-range'.$this->appId.'" style="display:none;text-align:center;"><br/>
								<table cellpadding=0 cellspacing=0 cellmargin=0 align=center>' .
									'<tr>' .
										'<td><strong>From:&nbsp;</strong></td>' .
										'<td><input style="float:left" class="desk-log-input" id="desklog-date-from'.$this->appId.'" type=input name="from'.$this->appId.'" value="'.$this->from.'"/></td>
										 <td><img src="/images/resources/calendar.gif" onclick=displayDatePicker("from'.$this->appId.'",false,"mdy","/"); style="float:left" /></td>
										 <td>&nbsp;&nbsp;<strong>To:&nbsp;</strong></td>' .
										'<td><input style="float:left" class="desk-log-input" id="desklog-date-to'.$this->appId.'" type=input name="to'.$this->appId.'" value="'.$this->to.'"/></td>
										 <td><img src="/images/resources/calendar.gif" onclick=displayDatePicker("to'.$this->appId.'",false,"mdy","/"); style="float:left" /></td>
									</tr>
								</table>
								<br/>										
								<input type="submit" class="button-link" name="option" value="View" />&nbsp;
								<input type="button" class="button-link" onclick="closeFilter('.$this->appId.')" value="Close" />
							</div>																
							
						</div>
					 </div>
				</form>';
			
			if(!$this->printing)
			{
				echo $selectionBar;
				
				$link = "/gbSuite/apps/process_application.php?app=power_rank&action=print_report&uid=".$this->user->getUID();
				
				$link .= "&sort=$this->sort&order=$this->order";
					
				if(isset($this->params['option']))
					$link .= '&option='.$this->params['option'];
				
				$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
				
				echo '<div class="report-tool-bar" style="text-align:right;"><img src="/images/resources/printButton.png" onclick=popUp("'.$link.'"); /></div>';
			}
			
			$time = time(); 
			$tableName = "power_rank_temp_$time";
				
			$sql = "CREATE TABLE $tableName AS 
						SELECT I.name AS sales_person, I.employee_id, SUM(units_sold) AS units_sold, 0 AS us_rank, SUM(total_front_end) AS total_front_end, 0 AS tfe_rank, SUM(total_back_end) AS total_back_end, 0 AS tbe_rank, SUM(total_gross) AS total_gross, 0 AS tg_rank, SUM(total_score) AS total_score, 0 AS power_rank, uid  
							FROM info I LEFT JOIN app_rpt_power_rank R 
								ON I.employee_id = R.employee_id AND ".str_replace("WHERE", "", $where)." 
							WHERE title = 'Salesperson' AND I.employee_id IS NOT NULL AND I.active = 1  
							GROUP BY I.name";
			//echo $sql;
			mysql_query($sql);
			
			$query = "SELECT * FROM power_rank_setup";
			$powerRankSetup = $this->connection->get_row($query);
			
			$unitsMultiplier = $powerRankSetup['units_multiplier'];
			$unitsPercentage = ($powerRankSetup['units_percentage'] > 0 ? $powerRankSetup['units_percentage'] / 100 : 0);
			$frontPercentage = ($powerRankSetup['front_percentage'] > 0 ? $powerRankSetup['front_percentage'] / 100 : 0);
			$backPercentage = ($powerRankSetup['back_percentage'] > 0 ? $powerRankSetup['back_percentage'] / 100 : 0);
			$totalPercentage = ($powerRankSetup['total_percentage'] > 0 ? $powerRankSetup['total_percentage'] / 100 : 0);
			
			//$totalScore = (($units * $powerRankSetup['units_multiplier']) * $powerRankSetup['units_percentage']) + ($grossFront * $powerRankSetup['front_percentage']) + ($grossBack * $powerRankSetup['back_percentage']) + (($grossFront + $grossBack) * $powerRankSetup['total_percentage']) / 4;
			$sql = "UPDATE $tableName SET total_score =  ((units * $unitsMultiplier) * $unitsPercentage) + (total_front_end * $frontPercentage) + (total_back_end * $backPercentage) + ((total_front_end + total_back_end) * $totalPercentage) / 4";
			$results = mysql_query($sql);
			
			PowerRank::updatePowerRank($tableName,$this->connection); 
			$sql = "SELECT * FROM $tableName order by $this->sort $this->order";
			
			//$results = $this->connection->exec_query($sql);
			$results = mysql_query($sql);
			
			if($this->order == "ASC")
				$newOrder = "DESC"; 
			else
				if($this->order == "DESC")
					$newOrder = "ASC";
			
			$link = "/gbSuite/home.php?app=power_rank&order=".$newOrder;
			
			if(isset($this->params['option']))
				$link .= '&option='.$this->params['option'];
				
			$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
				
			?>
			
			<table align=center cellspacing=0 cellmargin=0 class='power-rank-table' width=95%>
				<tr>
					<th class='power-rank-header' ><a href="<? echo $link."&sort=sales_person"; ?>">Associate</a></th>
					<th class='power-rank-header'><a href="<? echo $link."&sort=units_sold"; ?>">Units Sold</a></th>
					<th class='power-rank-header'><a href="<? echo $link."&sort=us_rank"; ?>">Rank</a></th>
					
					<th class='power-rank-separator'>&nbsp;</th>
					
					<th class='power-rank-header'><a href="<? echo $link."&sort=total_front_end"; ?>">Total Front-End</a></th>
					<th class='power-rank-header'><a href="<? echo $link."&sort=tfe_rank"; ?>">Rank</a></th>
					
					<th class='power-rank-separator'>&nbsp;</th>
										
					<th class='power-rank-header'><a href="<? echo $link."&sort=total_back_end"; ?>">Total Back-End</a></th>
					<th class='power-rank-header'><a href="<? echo $link."&sort=tbe_rank"; ?>">Rank</a></th>
					
					<th class='power-rank-separator'>&nbsp;</th>
					
					<th class='power-rank-header'><a href="<? echo $link."&sort=total_gross"; ?>">Total Gross</a></th>
					<th class='power-rank-header'><a href="<? echo $link."&sort=tg_rank"; ?>">Rank</a></th>
					
					<th class='power-rank-separator'>&nbsp;</th>
					
					<th class='power-rank-header'><a href="<? echo $link."&sort=total_score"; ?>">Total Score</a></th>
					<th class='power-rank-header'><a href="<? echo $link."&sort=power_rank"; ?>">PowerRank</a></th>
					
				</tr>
			<?			
			
			$rowAtt = " onMouseover=setClass('report-row-over',this) onMouseout=setClass('report-row',this) ";
			
			$cellAtt = " class='report-cell-center' onMouseover=setClass('report-cell-over-center',this) onMouseout=setClass('report-cell-center',this) ";
			$cellNumberAtt = " onMouseover=setClass('report-cell-over',this,'report-number') onMouseout=setClass('report-number',this) ";
			$cellUnitsNumberAtt = " onMouseover=setClass('report-cell-over',this,'report-number2') onMouseout=setClass('report-number2',this) ";
				
			$total = array();
			
			while($row = mysql_fetch_array($results))
			{
				$totals['units_sold'] += $row['units_sold']; 
				$totals['total_front_end'] += $row['total_front_end'];
				$totals['total_back_end'] += $row['total_back_end'];
				$totals['total_gross'] += $row['total_gross'];
				
				?>
				<tr class='report-row' <?= $rowAtt ?>>
					<td class='report-cell' onMouseover=setClass('report-cell-over',this) onMouseout=setClass('report-cell',this) >
						<a href='/gbSuite/home.php?app=profile&uid=<?= $row['uid'] ?>'><?= $row['sales_person'] ?></a>
					</td>
					<td <?= $cellUnitsNumberAtt ?> class='report-number2' align="center"><?= $this->formatUnits($row['units_sold']) ?></td>
					<td <?= $cellAtt ?> class='report-number2' align="center"><?= $row['us_rank'] ?></td>
					
					<td class='power-rank-separator'>&nbsp;</td>
					
					<td <?= $cellNumberAtt ?> class='report-number'>$<?= $this->formatNumber($row['total_front_end']) ?></td>
					<td <?= $cellAtt ?> class='report-number2' align="center"><?= $row['tfe_rank'] ?></td>
					
					<td class='power-rank-separator'>&nbsp;</td>
										
					<td <?= $cellNumberAtt ?>class='report-number'>$<?=$this->formatNumber( $row['total_back_end']) ?></td>
					<td <?= $cellAtt ?> class='report-number2' align="center"><?= $row['tbe_rank'] ?></td>
					
					<td class='power-rank-separator'>&nbsp;</td>
					
					<td <?= $cellNumberAtt ?>class='report-number'>$<?= $this->formatNumber( $row['total_gross']) ?></td>
					<td <?= $cellAtt ?> class='report-number2' align="center"><?= $row['tg_rank'] ?></td>
					
					<td class='power-rank-separator'>&nbsp;</td>
					
					<td <?= $cellNumberAtt ?>class='report-number'><?= $this->formatNumber($row['total_score']) ?></td>
					<td <?= $cellAtt ?> class='report-number2' align="center"><?= $row['power_rank'] ?></td>
				</tr>			
				<?
			}
			/*totals*/
			?>
			<tr class='totals2'>
					<td class="report-number">Total</td>
					
					<td class='report-number2' align="center"><?= $this->formatNumber($totals['units_sold']) ?></td>
					<td class='power-rank-none'>&nbsp;</td>
					
					<td class='power-rank-separator'>&nbsp;</td>
					
					<td class='report-number'>$<?= $this->formatNumber($totals['total_front_end']) ?></td>
					<td class='power-rank-none'>&nbsp;</td>
						
					<td class='power-rank-separator'>&nbsp;</td>
					
					<td class='report-number'>$<?= $this->formatNumber($totals['total_back_end']) ?></td>
					<td class='power-rank-none'>&nbsp;</td>
					
					<td class='power-rank-separator'>&nbsp;</td>
										
					<td class='report-number'>$<?= $this->formatNumber($totals['total_gross']) ?></td>
					<td class='power-rank-none'>&nbsp;</td>
					
					<td class='power-rank-separator'>&nbsp;</td>
					<td class='power-rank-none'>&nbsp;</td>
					<td class='power-rank-none'>&nbsp;</td>
				</tr>	
			
			<?
			
			$query = "DROP TABLE $tableName";
			
			$this->connection->exec_query($query);
			echo "</table>"; 
			
			echo "</div>";

		}
		
		public function formatUnits($value)
		{
			return number_format($value, 1, '.', ',');
		}
		
		public function formatNumber($value)
		{
			return number_format($value, 0, '.', ',');
		}
		
		static public function updatePowerRank($tableName,$connection)
		{
			$lastValue = '';
			$rank = 0;
			$values = array();
						
			$fields = array('us_rank'		=> 'units_sold' ,
							'tfe_rank'		=> 'total_front_end',
							'tbe_rank'		=> 'total_back_end',
							'tg_rank'		=> 'total_gross',
							'power_rank'	=> 'total_score'
							);
			
			foreach($fields as $rankField => $field )
			{
				$query = " select uid,ifnull($field,0) as $field from $tableName order by $field DESC ";
				
				$results = $connection->exec_query($query);
				$rank = 0;
				$lastValue = "";
				
				while($row = mysql_fetch_assoc($results))
				{
					if($rank == 0)
					{
						$lastValue = $row[$field];
						//echo "*".$lastValue."*";
						$rank++;
					}
					else
					{
						if($lastValue != $row[$field])
						{
							$rank++;
							$lastValue = $row[$field];	
						}
					}
					
					$values[$row['uid']][$rankField] = $rank;		
				}
			}
			
			foreach($values as $uid => $ranks)
			{
				$rankSql = "";
				foreach($ranks as $field => $rank)
				{
					$rankSql.=" $field='$rank' ,";
				} 
				
				$rankSql = substr( $rankSql,0,strlen($rankSql) -1 );				
				$sql = " update $tableName set  $rankSql where uid='$uid' ";
				
				
				$connection->exec_query($sql);
			}			
		}
		
		static public function updatePowerRank2($tableName,$connection)
		{
			$lastValue = '';
			$rank = 0;
			$values = array();
						
			$fields = array(							
							'mtd_pvr' => 'mtd_pvr',
							);
			
			foreach($fields as $rankField => $field )
			{
				$query = " select uid,ifnull($field,0) as $field from $tableName order by $field DESC ";
				
				$results = $connection->exec_query($query);
				$rank = 0;
				$lastValue = "";
				
				while($row = mysql_fetch_assoc($results))
				{
					if($rank == 0)
					{
						$lastValue = $row[$field];
						$rank++;
					}
					else
					{
						if($lastValue != $row[$field])
						{
							$rank++;
							$lastValue = $row[$field];	
						}
					}
					
					$values[$row['uid']][$rankField] = $rank;		
				}
			}
			
			foreach($values as $uid => $ranks)
			{
				$rankSql = "";
				foreach($ranks as $field => $rank)
				{
					$rankSql.=" '$rank' ,";
				} 
				
				$rankSql = substr( $rankSql,0,strlen($rankSql) -1 );				
				$sql = " update $tableName set  power_rank=$rankSql where uid='$uid' ";
				
				
				$connection->exec_query($sql);
			}			
			
		}
	}
?>
