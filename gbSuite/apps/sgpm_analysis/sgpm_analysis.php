<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/user.php");
	include_once $_SERVER['PHP_ROOT'].'/lib/maxchart/maxChart.class.php';

    class SGPMAnalysis extends Application
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
		private $dateRange = null;
		private $params = array();
		private $dealerViewId = 0;

		public function __construct()
		{
			$this->appId = 40;
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
			$this->sort = "mtdavg";
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
				 $this->notInstalledMessage();

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
			{
				$htmlTable = $this->renderTable();
				$htmlGraphic1 = $this->renderGraphicVolumeByFrontEndGross();
				$htmlGraphic2 = $this->renderMTDAVG();

				$title = "";

				$title = "Gross Profit Performance by Manager";

				$html .= '<div class="app-report">';
				$html .= "<div style='text-align:center'>";
				$html .= "<div class=report-title>$title<br/>$dateRange</div>";

				$html .=  "</div>";

				$selectionBar =
						'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=sgpm_analysis">
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

					$link = "/gbSuite/apps/process_application.php?app=sgpm_analysis&action=print_report&uid=".$this->user->getUID();

					$link .= "&sort=$this->sort&order=$this->order";

					if(isset($this->params['option']))
						$link .= '&option='.$this->params['option'];

					$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;

					$html .= '<div class="report-tool-bar" style="text-align:right;"><img src="/images/resources/printButton.png" onclick=popUp("'.$link.'"); /></div>';
				}

				$html .= "<div>$htmlTable</div>";
				$html .= '<table class="graphic-table" width="100%" cellspacing="0" cellpadding="0" border="0">
							<tr><td>'.$htmlGraphic1.'</td><td>'.$htmlGraphic2.'</td></tr>
						  </table>';
				$html .= "</div>";

				echo $html;
			}
		}

		private function renderTable()
		{
			$html = "";
			$rowCount = 0;

			$fields = array("`team`", "`(999)+`", "`(999)+%`", "`(998-500)`", "`(998-500)%`", "`(499-0)`", "`(499-0)%`", "`1-499`", "`1-499%`", "`500-1499`", "`500-1499%`", "`1500+`", "`1500+%`", "avgfront", "avgback", "mtdavg");
			$totalType = array("none","sum", "avg", "sum", "avg", "sum", "avg", "sum", "avg", "sum", "avg", "sum", "avg", "avgUnits", "avgUnits", "mtdavg");
			$header = array("Team", "(999)+", "%", "(998-500)", "%", "(499-0)", "%", "1-499", "%", "500-1499", "%", "1500+", "%", "AVG FRONT", "AVG BACK", "MTD AVG");
			$format = array("none", "0", "%", "0", "%", "0", "%", "0", "%", "0", "%", "0", "%", "$", "$", "$");
			$align = array("left", "center", "center", "center", "center", "center", "center", "center", "center", "center", "center", "center", "center", "right", "right", "right");

			$totalSum = array();

			if($this->sort == "1500" || $this->sort == "(999)")
				if(strpos($this->sort, "+") === false)
					$this->sort .= "+";

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
						ON I.employee_id = R.employee_id AND dl_date BETWEEN '$this->dateFrom' AND '$this->dateTo'
						LEFT JOIN (SELECT T.id, team_id, T.dealer_view_id, T.team, TM.uid
									FROM team T INNER JOIN team_member TM 
										ON T.id = TM.team_id) T 
						ON I.uid = T.uid AND D.id = T.dealer_view_id 
						WHERE D.id = $this->dealerViewId AND I.department LIKE '%Sales%' AND title IN ('Salesperson','Sales Manager') AND I.employee_id IS NOT NULL  
						GROUP BY team ORDER BY `$this->sort` $this->order, team ASC";
			
			$rsTeam = $this->connection->exec_query($query);

			$html = '<table class="app-report-table" width="100%" cellspacing=0 cellpadding=0 border=0>';

			//Header
			$html .= "<tr>";

			$order2 = "";

			if($this->order == "ASC")
				$order2 = "DESC";
			else
				if($this->order == "DESC")
					$order2 = "ASC";

			$link = '/gbSuite/home.php?app=sgpm_analysis&order='.$order2;

			if(isset($this->params['option']))
				$link .= '&option='.$this->params['option'];

			$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;

			for($i = 0; $i < sizeof($header); $i++)
					$html .= "<th><a href='".$link."&sort=".str_replace("`", "", $fields[$i])."'>".$header[$i]."</a></th>";

			$html .= "</tr>";
			
			if($rsTeam !== false)
			while($row = mysql_fetch_array($rsTeam, MYSQL_ASSOC))
			{
				$html .= '<tr class="report-row" onMouseover=setClass("report-row-over",this) onMouseout=setClass("report-row",this) >';

				for($i = 0; $i < count($fields); $i++)
				{
					$field = str_replace("`", "", $fields[$i]);

					if($field == "avgback")
					{
						$units = $row["(999)+"] + $row["(998-500)"] + $row["(499-0)"] + $row["1-499"] + $row["500-1499"] + $row["1500+"];

						$value = ($units > 0) ? ($row["avgback"]/$units) : 0;

						$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=spgp_analysis&suid='.$row['uid'].'">'.$this->formatNumber($value, $format[$i])."</a></td>";
					}
					else
						if($field == 'avgfront')
						{
							$units = $row["(999)+"] + $row["(998-500)"] + $row["(499-0)"] + $row["1-499"] + $row["500-1499"] + $row["1500+"];
							$value = ($units > 0) ? ($row["avgfront"]/$units) : 0;
							$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=spgp_analysis&suid='.$row['uid'].'">'.$this->formatNumber($value, $format[$i])."</a></td>";
						}
						else
							if($field == 'mtdavg')
							{
								$units = $row["(999)+"] + $row["(998-500)"] + $row["(499-0)"] + $row["1-499"] + $row["500-1499"] + $row["1500+"];

								$value = ($units > 0) ? (($row["avgfront"] + $row["avgback"]) / $units) : 0;
								$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=spgp_analysis&suid='.$row['uid'].'">'.$this->formatNumber($value, $format[$i])."</a></td>";
							}
							else
								if($totalType[$i] == 'avg')
								{
									$units = $row["(999)+"] + $row["(998-500)"] + $row["(499-0)"] + $row["1-499"] + $row["500-1499"] + $row["1500+"];
									$value = ($units > 0) ? (($row[str_replace("%", "", $field)] * 100)/$units) : 0;
									$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=spgp_analysis&suid='.$row['uid'].'">'.$this->formatNumber($value, $format[$i])."</a></td>";
								}
								else
									$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=spgp_analysis&suid='.$row['uid'].'">'.$this->formatNumber($row[$field], $format[$i])."</a></td>";

					if($i > 0)
						$totalSum[$field] += $row[$field];
				}

				$html .= "</tr>";

				++$rowCount;
			}

			$fieldsQuery = "";

			for($i = 0; $i < sizeof($fields); $i++)
			{
				if($totalType[$i] != "none")
					$fieldsQuery .= ($fieldsQuery != "" ? "," : "")." sum(".$fields[$i].") AS ".$fields[$i];
				else
					$fieldsQuery .= ($fieldsQuery != "" ? "," : "").$fields[$i];
			}

			//Total sum
			$html .= '<tr class="totals2">';

			$totalUnits = $totalSum["(999)+"] + $totalSum["(998-500)"] + $totalSum["(499-0)"] + $totalSum["1-499"] + $totalSum["500-1499"] + $totalSum["1500+"];

			for($i = 0; $i < count($fields); $i++)
			{
				$field = str_replace("`", "", $fields[$i]);

				$html .= '<td align="'.$align[$i].'">';

				if($totalType[$i] == "sum")
					$html .= $this->formatNumber($totalSum[$field], $format[$i]);
				else
					if($rowCount > 0)
					{
						if($totalType[$i] == "avg")
						{
							$field = str_replace("%", "", $field);

							if($totalUnits > 0)
								$html .= $this->formatNumber(($totalSum[$field] * 100)/$totalUnits, $format[$i]);
							else
								$html .= $this->formatNumber(0, $format[$i]);
						}
						else
							if($totalType[$i] == "avgUnits")
							{
								if($totalUnits > 0)
									$html .= $this->formatNumber($totalSum[$field]/$totalUnits, $format[$i]);
								else
									$html .= $this->formatNumber(0, $format[$i]);
							}
							else
								if($totalType[$i] == "mtdavg")
								{
									if($totalUnits > 0)
										$html .= $this->formatNumber(($totalSum["avgfront"] + $totalSum["avgback"])/$totalUnits, $format[$i]);
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

		private function renderGraphicVolumeByFrontEndGross()
		{	
			$html = "";
			
			$fields = array("`(999)+`", "`(998-500)`", "`(499-0)`", "`1-499`", "`500-1499`", "`1500+`");
			$header = array("(999)+", "(998-500)", "(499-0)", "1-499", "500-1499", "1500+");
			 
			for($i = 0; $i < sizeof($fields); $i++)				
					$query .= ($query != "" ? "," : "")."SUM(".$fields[$i].") AS ".$fields[$i]; 
			
			$query = "SELECT $query  
						FROM info I JOIN dealer_view D LEFT JOIN app_rpt_gross_profit_analisys R
						ON I.employee_id = R.employee_id AND dl_date BETWEEN '$this->dateFrom' AND '$this->dateTo'
						LEFT JOIN (SELECT T.id, team_id, T.dealer_view_id, T.team, TM.uid
									FROM team T INNER JOIN team_member TM 
										ON T.id = TM.team_id) T 
						ON I.uid = T.uid AND D.id = T.dealer_view_id 
						WHERE D.id = $this->dealerViewId AND I.department LIKE '%Sales%' AND title IN ('Salesperson','Sales Manager') AND I.employee_id IS NOT NULL  
						ORDER BY `$this->sort` $this->order, team ASC";
						
			$rs = $this->connection->exec_query($query);
			
			if($rs == null)
				return ""; 
				
			$data = array();
			
			while($row = mysql_fetch_array($rs, MYSQL_ASSOC))
			{
				for($i = 0; $i < count($fields); $i++)
				{
					$value = $row[str_replace("`", "", $fields[$i])];
					$data[$header[$i]] = $value;
				}	
			}
			
			$mc = new maxChart($data,true);
			$report = $mc->displayChart('Volume by Front-End Gross',1,'auto',170);
			
			return $report;
		}
		
		private function renderMTDAVG()
		{
			$html = "";
			
			$fields = array("`team`", "`mtdavg`");
			$header = array("Team", "MTD AVG");

			$query = "SELECT IFNULL(team, 'Other') AS team, ((SUM(avgfront) + SUM(avgback))/SUM(`(999)+` + `(998-500)` + `(499-0)` + `1-499` + `500-1499` + `1500+`)) AS mtdavg, SUM(avgfront) AS avgfront, SUM(avgback) AS avgback,
							  	SUM(`(999)+`) AS `(999)+`, SUM(`(998-500)`) AS `(998-500)`, SUM(`(499-0)`) AS `(499-0)`, SUM(`1-499`) AS `1-499`, SUM(`500-1499`) AS `500-1499`, SUM(`1500+`) AS `1500+`   
						FROM info I JOIN dealer_view D LEFT JOIN app_rpt_gross_profit_analisys R
						ON I.employee_id = R.employee_id AND dl_date BETWEEN '$this->dateFrom' AND '$this->dateTo'
						LEFT JOIN (SELECT T.id, team_id, T.dealer_view_id, T.team, TM.uid
									FROM team T INNER JOIN team_member TM 
										ON T.id = TM.team_id) T 
						ON I.uid = T.uid AND D.id = T.dealer_view_id 
						WHERE D.id = $this->dealerViewId AND I.department LIKE '%Sales%' AND title IN ('Salesperson','Sales Manager') AND I.employee_id IS NOT NULL  
						GROUP BY team ORDER BY `$this->sort` $this->order, team ASC";
											
			$rs = $this->connection->exec_query($query);
			
			if($rs == null)
				return "";
				
			$data = array();
			
			while($row = mysql_fetch_array($rs, MYSQL_ASSOC))
			{
				$units = $row["(999)+"] + $row["(998-500)"] + $row["(499-0)"] + $row["1-499"] + $row["500-1499"] + $row["1500+"];
				$avgfront = $row['avgfront'];
				$avgback = $row['avgback'];
				
				$mtdavg = ($units > 0 ? ($avgfront + $avgback) / $units : 0);					
				
				$value = $mtdavg;				
				$data[$row[str_replace("`", "", $fields[0])]] = $value;//($value == 0 ? 0.00001 : $value);	
			}
			
			$mc = new maxChart($data,true);
			$mc->setFormat("$");
			
			$report = $mc->displayChart('MTD AVG per Copy By Team',1,'auto',170, true);
            
			return $report;		
		}
		
		private function renderFRONTvsBACK()
		{
			$html = "";

			$fields = array("`avgfront`", "`avgback`");
			$header = array("AVG FRONT+", "AVG BACK");

			for($i = 0; $i < sizeof($fields); $i++)
				$query .= ($query != "" ? "," : "")."SUM(".$fields[$i].") AS ".$fields[$i];

			$query = "SELECT $query FROM app_rpt_gross_profit_analisys R INNER JOIN info I ON R.employee_id = I.employee_id ".$this->whereClause;

			$rs = $this->connection->exec_query($query);

			if($rs == null)
				return "";

			$data = array();

			while($row = mysql_fetch_array($rs, MYSQL_ASSOC))
			{
				for($i = 0; $i < count($fields); $i++)
				{
					$value = $row[str_replace("`", "", $fields[$i])];
					$data[$header[$i]] = $value;
				}
			}

			$mc = new maxChart($data,true);
            $report = $mc->displayChart('AVG FRONT vs AVG BACK',1,'auto',170, true);

			return $report;
		}

		public function formatNumber($value, $format)
		{
			if($format == ".00")
				$value = number_format($value, 1, '.', ',');
			else
				if($format == "%")
					$value = number_format($value, 0, '.', ',')."%";
				else
					if($format == "$")
						$value = "$".number_format($value, 0, '.', ',');
					else
						if($format == "0")
						{
							if(is_numeric($value))
						 	{
						 		if(strpos($value, "."))
						 			$value = number_format  ( $value, 1,'.', ',');
						 		else
						 			$value = number_format  ( $value, 0,'.', ',');
						 	}
						}

			return $value;
		}
	}
?>