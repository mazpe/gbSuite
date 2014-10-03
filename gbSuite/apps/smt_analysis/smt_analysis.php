<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/user.php");
	include_once $_SERVER['PHP_ROOT'].'/lib/maxchart/maxChart.class.php';
	
    class SMTAnalysis extends Application
	{
		private $currentProfileUID;
		private $currentTitle;
		private $whereClause = null;
		private $userTitle;
		private $printing = false;
		private $option = null;
		private $sort = null;
		private $order = null;
		private $dateFrom = null;
		private $dateTo = null;
		private $from = null;
		private $to = null;
		private $dateRange = null;
		private $params = array();
		private $dealerViewId = 0;
		
		public function __construct()
		{
			$this->appId = 28;
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
			$this->sort = "units";
			$this->order = "DESC";
			$this->from = "";
			$this->to = "";			
			$this->dateRange = "";
			
			$this->params = array_merge($_GET, $_POST);
			
			if(isset($this->params['sort']))
				$this->sort = $this->params['sort'];
				
			if(isset($this->params['order']))
				$this->order = $this->params['order'];
			
			if(!$this->isInstalled())
			{
				echo $this->notInstalledMessage();
				
				return;
			}								

			$currentDate = $this->connection->get_value("SELECT date_format(now(), '%m/%d/%Y') AS currentDate");

			if(isset($this->params['option']))
			{
				$this->option = $this->params['option'];

				if($this->option == "View")
				{
					$this->from = $this->params['from'.$this->appId];
					$this->to = $this->params['to'.$this->appId];

					$dateRange = $this->from." - ".$this->to;

					$this->dateFrom = substr($this->from, 6, 4).substr($this->from, 0, 2).substr($this->from, 3, 2);
					$this->dateTo = substr($this->to, 6, 4).substr($this->to, 0, 2).substr($this->to, 3, 2);
					
					$query = "SELECT id FROM dealer_view WHERE '$this->dateFrom' BETWEEN `from` AND `to`";
					
					$value = $this->connection->get_value($query);

					if($value != null)
						$this->dealerViewId = $value;
					else
					{
						//TODO:
						//Should process an exception because the dealer view it doesn't define.
					}
				}
				else //Verify if correspond to a dealer view
				{
					$query = "SELECT id, date_format(`from`, '%Y%m%d') AS `start`, date_format(`to`, '%Y%m%d') AS `end`, date_format(`from`, '%m/%d/%Y')AS `from`, date_format(`to`, '%m/%d/%Y')AS `to` FROM dealer_view WHERE label = '$this->option'";

					$row = $this->connection->get_row($query);

					if($row != null)
					{
						$this->dealerViewId = $row['id'];

						$this->dateFrom = $row['start'];
						$this->dateTo = $row['end'];

						$this->from = $row['from'];
						$this->to = $row['to'];

						$dateRange = $row['from']." - ".$row['to'];						
					}
					else
					{
						//TODO
						//Should throw an exception because the dealerViewId is not define.
					}
				}
			}
			else
			{
				$row = null;

				$query = "SELECT id, date_format(`from`, '%Y%m%d') AS `start`, date_format(`to`, '%Y%m%d') AS `end`, date_format(`from`, '%m/%d/%Y') AS `from`, date_format(`to`, '%m/%d/%Y') AS `to` FROM dealer_view WHERE CAST(date_format(now(), '%Y%m%d') AS DATE) BETWEEN `from` AND `to`";

				$dateRange = $this->connection->get_row($query);
				$condition = "";

				if($dateRange != null)
				{
					$this->dealerViewId = $dateRange['id'];

					$this->dateFrom = $dateRange['start'];
					$this->dateTo = $dateRange['end'];
					
					$this->from = $dateRange['from'];
					$this->to = $dateRange['to'];
					
					$dateRange = $this->from." - ".$this->to;
				}
				else
				{
					//TODO
					//Should throw an excepcion because the delearViewId is not define.					
				}				
			}
			
			//Si lleva el ruid entonces se esta mandando a llamar desde 
			$this->currentProfileUID = $this->user->getCurrentProfileAttribute('uid');
			$this->currentTitle = $this->user->getCurrentProfileAttribute('title');
			
			$this->userTitle = $this->user->getTitle(); //Title of the logged user
								
			$htmlTable = $this->renderTable();			
			
			$title = "";
			
			$title = "Sales Performance by Team";
				
			$html .= '<div class="app-report">';
			$html .= "<div style='text-align:center'>";
			$html .= "<div class=report-title>$title<br/>$dateRange</div>";
			//$html .= "<div class=report-subtitle>September 2008</div>";
			
			//if(!$this->printing)
			//	$html .= '<div class="report-tool-bar" style="text-align:right;"><img src="/images/resources/printButton.png" onclick=popUp("/gbSuite/apps/process_application.php?app=smt_analysis&action=print_report&uid='.$this->user->getUID().'"); /></div>';
			
			$html .= "</div>";
					$selectionBar = 
						'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=smt_analysis">
							<div id="filter" class="" style="text-align:center;">
								<input id=desklog-sort'.$this->appId.' type=hidden name=sort value="'.$this->sort.'" />
								<input id=desklog-order'.$this->appId.' type=hidden name=order value="'.$this->order.'" />
								<input id=desklog-option'.$this->appId.' type=hidden name=option value="'.$this->option.'" />
								
								<input id="dealer-view-button'.$this->appId.'" class="desklog-dealer-options" type="button" value="Month" onclick="showDealerView('.$this->appId.')" />&nbsp
								<input id="date-range-button'.$this->appId.'" class="desklog-dealer-options" type="button" value="Date Range" onclick="showDateRange('.$this->appId.')" />&nbsp 
								<input style="display:none;" id="desklog-filter-button'.$this->appId.'" class="desklog-dealer-options" type="button" value="Filter" onclick="showDesklogFilter('.$this->appId.')" />
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
				$html .= $selectionBar;
				
				$link = "/gbSuite/apps/process_application.php?app=smt_analysis&action=print_report&uid=".$this->user->getUID();
				
				$link .= "&sort=$this->sort&order=$this->order";

				if(isset($this->params['option']))
					$link .= '&option='.$this->params['option'];
				
				$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
					
				$html .= '<div class="report-tool-bar" style="text-align:right;"><img src="/images/resources/printButton.png" onclick=popUp("'.$link.'"); /></div>';
			}
			  
			$html .= "<div>$htmlTable</div>";							
			/*$html .= '<table class="graphic-table" width="100%" cellspacing="0" cellpading="0" border="0">						
						<tr><td>'.$htmlGraphic1.'</td><td>'.$htmlGraphic2.'</td></tr>
					  </table>';
			$html .= "</div>";*/
					  
			echo $html;
		}
		
		private function renderTable()
		{
			$html = "";
			$rowCount = 0;
			
			$fields = array("`team`", "`name`", "`units`", "`goal`", "`track`", "`new`", "`used`", "`gross_front`", "`gross_back`", "`gross_total`", "`mtd_avg`", "`showroom`", "`sold_sh`", "`close_sh%`", "iphone", "sold_ip", "`close_ip%`", "ileads", "sold_il", "`close_il%`");
			$totalType = array("none", "none", "sum", "sum", "sum", "sum", "sum", "sum", "sum", "sum", "mtdavg", "sum", "sum", "avg", "sum", "sum", "avg", "sum", "sum", "avg");			
			$header = array("Team", "Associate", "Units", "Goal", "Track", "N", "U", "Front", "Back", "Total", "MTD<br/>AVG", "Show<br/>Room", "Sold", "%", "iPhone", "Sold", "%", "iLead", "Sold", "%");
			$format = array("none", "none", ".", ".", "0", ".", ".", "$", "$", "$", "$", "0", ".", "%", "0", ".", "%", "0", ".", "%");
			$align = array("left", "left", "center", "center", "center", "center", "center", "right", "right", "right", "right", "center", "center", "center", "center", "center", "center", "center", "center", "center");
			
			$totalSum = array();
			
			if($this->sort == "team")
			{
				$this->sort = "I.name";
				$this->order = "ASC";
			}						
			else
				if($this->sort == "name")
					$this->sort = "name";
				else	
					$this->sort = $this->sort;
					
			$order2 = "";
			
			if($this->order == "ASC")
				$order2 = "DESC";
			else
				if($this->order == "DESC")
					$order2 = "ASC";
			
			$html = '<table class="app-report-table" width="100%" cellspacing=0 cellpading=0 border=0>';
			
			//Header
			$html .= "<tr>";
			
			$link = '/gbSuite/home.php?app=smt_analysis&order='.$order2;
			
			if(isset($this->params['option']))
				$link .= '&option='.$this->params['option'];
			
			$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
			
			for($i = 0; $i < sizeof($header); $i++)
				$html .= "<th><a href='".$link."&sort=".str_replace("`", "", $fields[$i])."'>".$header[$i]."</a></th>"; 
		
			$html .= "</tr>";

			$currentDay = date("d");
			
			//SUM(IF(units > 0, units / $currentDay, 0) * DATEDIFF($this->dateTo, $this->dateFrom)) AS `track`
			
			$query = 	" SELECT SD.team_id, SD.team, SD.uid, SD.name, FORMAT(IFNULL(SD.units, 0), 1) AS units, IFNULL(SD.track, 0) AS track, FORMAT(IFNULL(SD.new, 0), 1) AS new,".
							" FORMAT(IFNULL(SD.used, 0), 1) AS used, IFNULL(SD.gross_front, 0) AS gross_front, IFNULL(SD.gross_back, 0) AS gross_back, IFNULL(SD.gross_total, 0) AS gross_total, FORMAT(IFNULL(SD.sold_sh, 0), 1) AS sold_sh,". 
							" FORMAT(IFNULL(SD.sold_ip, 0), 1) AS sold_ip, FORMAT(IFNULL(SD.sold_il, 0), 1) AS sold_il, IFNULL(SUM(showroom), 0) AS showroom,". 
							" IFNULL(SUM(iphone), 0) AS iphone, IFNULL(SUM(ileads), 0) AS ileads, IF(SUM(GS.`recommit`) > 0, IFNULL(SUM(GS.recommit), 0),". 
							" IFNULL(SUM(GS.goals), 0)) AS `goal`". 
						" FROM(". 
							" SELECT employee_id, team, team_id, name, uid, SUM(units) AS units,". 
								" SUM(IF(units > 0, units / $currentDay, 0) * DATEDIFF('$this->dateTo', '$this->dateFrom')) AS `track`, SUM(`new`) AS `new`, SUM(`used`) AS `used`, SUM(`gross_front`) AS `gross_front`,". 
								" SUM(`gross_back`) AS `gross_back`, SUM(`gross_total`) AS `gross_total`, SUM(`sold_sh`) AS `sold_sh`, SUM(`sold_ip`) AS `sold_ip`, SUM(`sold_il`) AS `sold_il`". 
							" FROM(".
							" SELECT U.uid, U.name, IFNULL(team, 'Other') AS team, IFNULL(team_id, 0) AS team_id, S.*".
							" FROM info U JOIN dealer_view D LEFT JOIN". 
							" (".
								" SELECT T.id, team_id, T.dealer_view_id, T.team, TM.uid".
								" FROM team T INNER JOIN team_member TM". 
								" ON T.id = TM.team_id INNER JOIN dealer_view D".
								" ON T.dealer_view_id = D.id".
								" WHERE `from` <= '$this->dateTo' AND `to` >= '$this->dateFrom'".								
							" ) T". 
							" ON U.uid = T.uid AND D.id = T.dealer_view_id LEFT JOIN app_rpt_sales_department S". 
							" ON U.employee_id = S.employee_id AND dl_date BETWEEN D.`from` AND D.`to`".
							" WHERE D.`from` <= '$this->dateTo' AND D.`to` >= '$this->dateFrom' AND U.title = 'Salesperson' AND U.active = 1 ) S". 
							" GROUP BY S.name) SD LEFT JOIN lead_control LC". 
							" ON SD.uid = LC.uid LEFT JOIN goals_settings GS". 
							" ON SD.uid = GS.uid INNER JOIN dealer_view DS". 
							" ON LC.dealer_view_id = DS.id AND GS.dealer_view_id = DS.id". 
						" WHERE DS.`from` <= '$this->dateTo' AND DS.`to` >= '$this->dateFrom'".
						" GROUP BY team, name". 
						" ORDER BY team, `$this->sort` $this->order";

				$rsTeam = $this->connection->exec_query($query);
					
				$currentTeam = "";
				$groupRowCount = 0;
				$groupSum = array();
							
				while($row = mysql_fetch_array($rsTeam, MYSQL_ASSOC))
				{															
					if($currentTeam != $row['team'])
					{																	
						if($currentTeam != "")
						{																													
							$html .= '<tr class="totals2">';
					
							for($i = 0; $i < count($fields); $i++)	
							{
								$field = str_replace("`", "", $fields[$i]);
												
								$html .= '<td style="color:blue;" align="'.$align[$i].'">';
								
								if($totalType[$i] == "sum")					
									$html .= $this->formatNumber($groupSum[$field], $format[$i]);
								else	
									if($groupRowCount > 0)
									{										
										if($totalType[$i] == "avg")
										{
											$value = 0;
																			
											if($field == "close_sh%")
												$value = ($groupSum['showroom'] > 0) ? (($groupSum['sold_sh'] * 100) / $groupSum['showroom']) : 0;																					       																				 
											else
												if($field == "close_ip%")
												 	$value = ($groupSum['iphone'] > 0) ? (($groupSum['sold_ip'] * 100) / $groupSum['iphone']) : 0;
												else
													if($field == "close_il%")
														$value = ($groupSum['ileads'] > 0) ? (($groupSum['sold_il'] * 100) / $groupSum['ileads']) : 0;	
											 
											$html .= $this->formatNumber($value, $format[$i]);
										}	
										else
											if($totalType[$i] == "mtdavg")
											{
												if($groupSum["units"] > 0)
													$html .= $this->formatNumber(($groupSum["gross_total"]/$groupSum["units"]), $format[$i]);
												else
													$html .= $this->formatNumber(0, $format[$i]);
											}	
									}
									else
										if($totalType[$i] != 'none')
											$html .= $this->formatNumber(0, $format[$i]);
									
								$html .= "</td>";									
							}
							$html .= "</tr>";
						}		
						
						$currentTeam = $row['team'];
							
						$html .= '<tr class="report-row" onMouseover=setClass("report-row-over",this) onMouseout=setClass("report-row",this) >';
						$html .= '<td class="team-row" colspan="'.count($fields).'">'.$row['team'].'</td>';
						$html .= '</tr>';
						
						++$groupRowCount;						
						$groupSum = array();									
					}
						
					//Detail
					$html .= '<tr class="report-row" onMouseover=setClass("report-row-over",this) onMouseout=setClass("report-row",this) >';
					
					for($i = 0; $i < count($fields); $i++)				
					{
						$field = str_replace("`", "", $fields[$i]);
						
												
						if($i == 0)
							$row[$field] = '';
						
						if($field == 'mtd_avg')
						{
							/*if($groupSum["units"] > 0)
								$html .= $this->formatNumber(($groupSum["gross_total"]/$groupSum["units"]), $format[$i]);
							else
								$html .= $this->formatNumber(0, $format[$i]);*/
							$value = ($row['units'] > 0 ? ($row['gross_front'] + $row['gross_back']) / $row['units'] : 0);
							$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=profile&uid='.$row['uid'].'">'.$this->formatNumber($value, $format[$i])."</a></td>";									
						}
						else	
							if($totalType[$i] == "avg")
							{
								$value = 0;
																
								if($field == "close_sh%")
									$value = ($row['showroom'] > 0) ? (($row['sold_sh'] * 100) / $row['showroom']) : 0;																					       																				 
								else
									if($field == "close_ip%")
									 	$value = ($row['iphone'] > 0) ? (($row['sold_ip'] * 100) / $row['iphone']) : 0;
									else
										if($field == "close_il%")
											$value = ($row['ileads'] > 0) ? (($row['sold_il'] * 100) / $row['ileads']) : 0;	
								
								$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=profile&uid='.$row['uid'].'">'.$this->formatNumber($value, $format[$i])."</a></td>";											 
							
							}
							else
								$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=profile&uid='.$row['uid'].'">'.$this->formatNumber($row[$field], $format[$i])."</a></td>";
												
						if($i > 0)
						{										
							$groupSum[$field] += $row[$field];							
							$totalSum[$field] += $row[$field];
						}							 
					}			
						
					$html .= "</tr>";
					
					++$rowCount;
					
					if($currentTeam != $row['team'])
					{																	
						if($currentTeam != "")
						{
							if($rowCount == mysql_num_rows($rsTeam))
							{												
								$html .= '<tr class="totals2">';										
								
								for($i = 0; $i < count($fields); $i++)	
								{									
									$field = str_replace("`", "", $fields[$i]);
													
									$html .= '<td style="color:blue;" align="'.$align[$i].'">';
									
									if($totalType[$i] == "sum")					
										$html .= $this->formatNumber($groupSum[$field], $format[$i]);
									else	
										if($groupRowCount > 0)
										{
											if($totalType[$i] == "avg")
											{
												$value = 0;
									
												if($field == "close_sh%")
													$value = ($groupSum['showroom'] > 0) ? (($groupSum['sold_sh'] * 100) / $groupSum['showroom']) : 0; 
												else
													if($field == "close_ip%")
													 	$value = ($groupSum['iphone'] > 0) ? (($groupSum['sold_ip'] * 100) / $groupSum['iphone']) : 0;
													else
														if($field == "close_il%")
														{
															$value = ($groupSum['ileads'] > 0) ? (($groupSum['sold_il'] * 100) / $groupSum['ileads']) : 0;																										
														}											 		
												 
												$html .= $this->formatNumber($value, $format[$i]);
											}	
											else
												if($totalType[$i] == "mtdavg")
												{
													if($groupSum["units"] > 0)
													{
														$html .= $this->formatNumber(($groupSum["gross_total"]/$groupSum["units"]), $format[$i]);
													}	
													else
														$html .= $this->formatNumber(0, $format[$i]);
												}	
										}
										else
											if($totalType[$i] != 'none')
												$html .= $this->formatNumber(0, $format[$i]);
										
									$html .= "</td>";													
								}					
								$html .= "</tr>";
							}
						}
					}
				}
						
				//Total sum
				$html .= '<tr class="totals2">';
			
				for($i = 0; $i < count($fields); $i++)	
				{
					$field = str_replace("`", "", $fields[$i]);
									
					$html .= '<td align="'.$align[$i].'">';
					
					if($totalType[$i] == "sum")
						$html .= $this->formatNumber($totalSum[$field], $format[$i]);
					else	
						if($groupRowCount > 0)
						{
							if($totalType[$i] == "avg")
							{
								$value = 0;
							
								if($field == "close_sh%")
									$value = ($totalSum['showroom'] > 0) ? (($totalSum['sold_sh'] * 100) /$totalSum['showroom']) : 0; 
								else
									if($field == "close_ip%")
									 	$value = ($totalSum['iphone'] > 0) ? (($totalSum['sold_ip'] * 100) /$totalSum['iphone']) : 0;
									else
										if($field == "close_il%")
									 		{
									 			$value = ($totalSum['ileads'] > 0) ? (($totalSum['sold_il'] * 100) /$totalSum['ileads']) : 0;
									 		}
								 
								$html .= $this->formatNumber($value, $format[$i]);
							}	
							else
								if($totalType[$i] == "mtdavg")
								{
									if($totalSum["units"] > 0)
										$html .= $this->formatNumber(($totalSum["gross_total"]/$totalSum["units"]), $format[$i]);
									else
										$html .= $this->formatNumber(0, $format[$i]);
								}	
						}
						else
							if($totalType[$i] != 'none')
								$html .= $this->formatNumber(0, $format[$i]);
						
					$html .= "</td>";														
				}
			
				$html .= "</tr>";
				
				$html .= "</table>";
			
			return $html;
		}
		
		public function formatNumber($value, $format)
		{
			if($format == ".")
				$value = number_format($value, 1, '.', ',');
			else
				if($format == "%")
					$value = number_format($value, 0, '.', ',')."%";
				else
					if($format == "$")
						$value = "$".number_format($value, 0, '.', ',');
					else
						if($format == "0")
							$value = number_format($value, 0, '.', ',');
						
			return $value;	
		}
	}
?>