<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/user.php");
	
    class SMAnalysis extends Application
	{
		private $currentProfileUID;
		private $currentTitle;
		private $whereClause;
		private $userTitle;
		private $printing = false;
		
		private $option = null;
		private $sort = null;
		private $order = null;
		private $dateFrom = null;
		private $dateTo = null;
		private $from = null;
		private $to = null;		
		private $params = array();
		private $dealerViewId = 0;
		
		private $dateCondition = "";		
				
		public function __construct()
		{
			$this->appId = 16;
		}
		
		public function print_report()
		{
			?>
				<link rel="stylesheet" href="/css/report_print.css" type="text/css" media="screen, print" charset="utf-8" />				
			<?
			
			$this->printing = true;
				
			$this->renderHTML();
		}

		public function renderHTML()
		{
			$start = "";
			$end = "";
			$dateRange = "";
				
			if(!$this->isInstalled())
			{
				echo $this->notInstalledMessage();
				
				return;
			}								
			
			$this->sort = "units";
			$this->order = "DESC";
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
			{
				$htmlTable = $this->renderTable();			
				
				$title = "";
				
				$title = "Sales Performance by Associate";
					
				$html .= '<div class="app-report">';
				$html .= "<div style='text-align:center'>";
				$html .= "<div class=report-title>$title<br/>$dateRange</div>";
					
				$html .= "</div>";
			
				$selectionBar = 
								'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=sm_analysis">
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
					$html .= $selectionBar;
					
					$link = "/gbSuite/apps/process_application.php?app=sm_analysis&action=print_report&uid=".$this->user->getUID();
					
					$link .= "&sort=$this->sort&order=$this->order";
					
					if(isset($this->params['option']))
						$link .= '&option='.$this->params['option'];
				
					$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
						
					$html .= '<div class="report-tool-bar" style="text-align:right;"><img src="/images/resources/printButton.png" onclick=popUp("'.$link.'"); /></div>';
				}
			
				$html .= "<div>$htmlTable</div>";				
				
				$html .= "</div>";
						  
				echo $html;
			}
		}
		
		private function renderTable()
		{
			$html = "";
			$rowCount = 0;
			
			$fields = array("`name`", "`units`", "`goal`", "`track`", "`new`", "`used`", "`gross_front`", "`gross_back`", "`gross_total`", "`mtd_avg`", "`showroom`", "`sold_sh`", "`close_sh%`", "`iphone`", "`sold_ip`", "`close_ip%`", "`ileads`", "`sold_il`", "`close_il%`");
			$totalType = array("none", "sum", "sum", "sum", "sum", "sum", "sum", "sum", "sum", "avgmtd", "sum", "sum", "close", "sum", "sum", "close", "sum", "sum", "close");			
			$header = array("Associate", "Units", "Goal", "Track", "New", "Used", "Front", "Back", "Total", "MTD AVG", "Showroom", "Sold", "%", "iPhone", "Sold", "%", "iLeads", "Sold", "%");
			$format = array("none", ".", ".", "0", ".", ".", "$", "$", "$", "$", "0", ".", "%", "0", ".", "%", "0", ".", "%");
			$align = array("left", "center", "center", "center", "center", "center", "right", "right", "right", "right", "center", "center", "center", "center", "center", "center", "center", "center", "center");
			
			$query = "SELECT DATEDIFF(`to`, `from`) + 1 AS days 
						FROM dealer_view
						WHERE id = $this->dealerViewId";
						
			$monthDayCount = $this->connection->get_value($query);
			$currentDay = date("d");
					
			$sums = array();
			$query = "";
			
			for($i = 0; $i < sizeof($fields); $i++)
			{
				if($totalType[$i] != "none")
					$query .= ($query != "" ? "," : "")."SUM(".$fields[$i].") AS ".$fields[$i];
				else
					$query .= ($query != "" ? "," : "").$fields[$i];
			}				
			
			$query = "SELECT name, I.uid, SUM(`units`) AS `units`, IF(G.`recommit` > 0, G.recommit, G.goals) AS `goal`, SUM(IF(units > 0, units / $currentDay, 0) * DATEDIFF('$this->dateTo', '$this->dateFrom')) AS `track`,SUM(`new`) AS `new`,SUM(`used`) AS `used`, SUM(`gross_front`) AS `gross_front`,SUM(`gross_back`) AS `gross_back`,
							SUM(`gross_total`) AS `gross_total`,SUM(`mtd_avg`) AS `mtd_avg`, L.showroom, SUM(`sold_sh`) AS `sold_sh`, 0 AS `close_sh%`, L.iphone, 
							SUM(`sold_ip`) AS `sold_ip`, 0 AS `close_ip%`, L.ileads, SUM(`sold_il`) AS `sold_il`, 0 AS `close_il%`
						FROM info I JOIN dealer_view D LEFT JOIN app_rpt_sales_department R
						ON I.employee_id = R.employee_id AND dl_date BETWEEN '$this->dateFrom' AND '$this->dateTo' LEFT JOIN lead_control L
						ON L.uid = I.uid AND L.dealer_view_id = D.id LEFT JOIN goals_settings G
						ON G.uid = I.uid AND G.dealer_view_id = D.id
						WHERE D.id = $this->dealerViewId AND I.title = 'Salesperson' AND I.employee_id IS NOT NULL AND I.active = 1   
						GROUP BY I.name, name ORDER BY `$this->sort` $this->order";
						
			$rs = $this->connection->exec_query($query);
			
			if($rs == null)
				return "";
				
			$html = '<table class="app-report-table" width="100%" cellspacing=0 cellpadding=0 border=0><tr>';
			
			$order = "";
			
			if($this->order == "ASC")
				$order = "DESC";
			else
				if($this->order == "DESC")
					$order = "ASC";
							
			$link = '/gbSuite/home.php?app=sm_analysis&order='.$order;
			
			if(isset($this->params['option']))
				$link .= '&option='.$this->params['option'];
				
			$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
						
			for($i = 0; $i < sizeof($header); $i++)
				$html .= "<th><a href='".$link."&sort=".str_replace("`", "", $fields[$i])."'>".$header[$i]."</a></th>"; 
		
			$html .= "</tr>";
				 
			while($row = mysql_fetch_array($rs, MYSQL_ASSOC))
			{
				$html .= '<tr class="report-row" onMouseover=setClass("report-row-over",this) onMouseout=setClass("report-row",this) >';
				
				for($i = 0; $i < count($fields); $i++)				
				{
					$field = str_replace("`", "", $fields[$i]);

					if($field != "mtd_avg")			
						$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=profile&uid='.$row['uid'].'">'.$this->formatNumber($row[$field], $format[$i])."</a></td>";
					else
					{
						$value = ($row['units'] > 0 ? ($row['gross_front'] + $row['gross_back'])/$row['units'] : 0);
							 
						$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=profile&uid='.$row['uid'].'">'.$this->formatNumber($value, $format[$i])."</a></td>";						
					}
					
					if($i > 0)
						$sum[$field] += $row[$field];
				}					
					
				$html .= "</tr>";
				
				++$rowCount;
			}
			
			$html .= '<tr class="totals2" >';
			
			for($i = 0; $i < count($fields); $i++)	
			{
				$field = str_replace("`", "", $fields[$i]);
								
				$html .= '<td align="'.$align[$i].'">';
				
				if($totalType[$i] == "sum")					
					$html .= $this->formatNumber($sum[$field], $format[$i]);
				else	
					if($rowCount > 0)
					{
						if($totalType[$i] == "avg")
						{
							$field = str_replace("%", "", $field);
							
							$html .= $this->formatNumber(($sum[$field] * 100)/$totalUnits, $format[$i]);																						
						}
						else
							if($totalType[$i] == "close")
							{
								if($field == "close_sh%")
								{
									$value = ($sum['showroom'] > 0) ? (($sum['sold_sh'] * 100) / $sum['showroom']) : 0;
									$html .= $this->formatNumber($value, $format[$i]);									
								}
								else
									if($field == "close_ip%")
								{
									$value = ($sum['iphone'] > 0) ? (($sum['sold_ip'] * 100) / $sum['iphone']) : 0;
									$html .= $this->formatNumber($value, $format[$i]);									
								}
									else
										if($field == "close_il%")
								{
									$value = ($sum['ileads'] > 0) ? (($sum['sold_il'] * 100) / $sum['ileads']) : 0;
									$html .= $this->formatNumber($value, $format[$i]);									
								}								
							}
							else
								if($totalType[$i] == "avgmtd")
								{
									if($sum['units'] > 0)								
										$html .= $this->formatNumber(($sum["gross_front"] + $sum["gross_back"])/$sum['units'], $format[$i]);
									else									
										$html .= $this->formatNumber(0, $format[$i]);																	
								}	
					}				
						
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