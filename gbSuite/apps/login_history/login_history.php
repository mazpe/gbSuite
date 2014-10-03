<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/user.php");

    class LoginsHistory extends Application
	{
		private $currentProfileUID;
		private $currentTitle;
		private $whereClause;
		private $userTitle;
		private $printing = false;

		private $option = null;
		private $sort = null;
		private $order = null;
		private $from = null;
		private $to = null;
		private $params = array();
		private $dealerViewId = 0;

		public function __construct()
		{
			$this->appId = 44;


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


			$this->sort = "date";
			$this->order = "desc";
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

					$this->whereClause = "WHERE date >= '$start' AND date <= '$end' ";
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
						$this->whereClause = "WHERE date between '$start' and '$end'";

					}
				}
			}
			else
			{

			$this->params=$this->connection->get_row("select params from apps where app_id=$this->appId");
			$params=split("=",str_replace(array("[","]"),"",$this->params[0]));
			$this->params=null;
			for($i=0; $i<sizeof($params)/2;$i++){
				$this->params[$params[$i]]=$params[$i+1];
			}

			if(isset($this->params['default_view'])){
				$this->whereClause="where date between %s and %s";
				if($this->params['default_view']=='daily'){
					$this->whereClause=sprintf($this->whereClause,"current_date","now()");
				}elseif($this->params['default_view']=='weekly'){
					$this->whereClause=sprintf($this->whereClause,"current_date-7","now()");
				}elseif($this->params['default_view']=='month'){
					$this->whereClause=sprintf($this->whereClause,"concat(year(current_date),'-',month(current_date)-1,'-',day(current_date))","now()");
				}

			}else{
				$row = null;

				$query = "SELECT id, date_format(`from`, '%Y%m%d') AS `from`, date_format(`to`, '%Y%m%d') AS `to`, date_format(`from`, '%m/%d/%Y') AS `start`, date_format(`to`, '%m/%d/%Y') AS `end` FROM dealer_view WHERE now() BETWEEN `from` AND `to`";

				$dateRange = $this->connection->get_row($query);
				$condition = "";

					$this->dealerViewId = $dateRange['id'];

					$condition = "date BETWEEN '".$dateRange['from']."' AND '".$dateRange['to']."'";

					$this->from = $dateRange['start'];
					$this->to = $dateRange['end'];

				$this->whereClause = "WHERE $condition ";

				$dateRange = $this->from." - ".$this->to;
			}
			}

			//Si lleva el ruid entonces se esta mandando a llamar desde
			$this->currentProfileUID = $this->user->getCurrentProfileAttribute('uid');
			$this->currentTitle = $this->user->getCurrentProfileAttribute('title');

			$this->userTitle = $this->user->getTitle(); //Title of the logged user
			{
				$htmlTable = $this->renderTable();

				$title = "";

				$title = "Login History Display";

				$html .= '<div class="app-report">';
				$html .= "<div style='text-align:center'>";
				$html .= "<div class=report-title>$title<br/>$dateRange</div>";

				$html .= "</div>";

				$selectionBar =
								'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=logins_history">
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

					$link = "/gbSuite/apps/process_application.php?app=logins_history&action=print_report&uid=".$this->user->getUID();

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

			$fields = array("email", "date","ip","Browser",);
			$totalType = array("none","none", "none","none");
			$header = array("Email", "Date","IP Address","Browser");
			$format = array("none", "date", "none","none");
			$align = array("left", "center","center","center");

			$sums = array();
			$query = "";

			for($i = 0; $i < sizeof($fields); $i++)
			{
				if($totalType[$i] != "none")
					$query .= ($query != "" ? "," : "")."SUM(".$fields[$i].") AS ".$fields[$i];
				else
					$query .= ($query != "" ? "," : "").$fields[$i];
			}

		

			$query = "select uid,email, SUBSTR(ip,1,INSTR(ip,\":\")-1) as ip,date, IF( INSTR(context,\"MSIE\")>0, concat(\"IExplorer\", substr( context, INSTR(context,\"MSIE\")+4,4) ) ,
			IF( INSTR(context,\"Firefox\")>0 , concat(\"Firefox \", substr( context, INSTR(context,\"Firefox\")+8,3)),'Others')) as Browser
			from session_log
			$this->whereClause and (email not like '%@gbsuite.com')
			order by $this->sort $this->order ";


			$rs = $this->connection->exec_query($query);
			//echo $query;
			if($rs == null)
				return "";

			$html = '<table class="app-report-table" width="100%" cellspacing=0 cellpadding=0 border=0><tr>';

			$order = "";

			if($this->order == "ASC")
				$order = "DESC";
			else
				if($this->order == "DESC")
					$order = "ASC";

			$link = '/gbSuite/home.php?app=logins_history&order='.$order;

			if(isset($this->params['option']))
				$link .= '&option='.$this->params['option'];

			$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;

			for($i = 0; $i < sizeof($header); $i++)
				$html .= "<th><a href='".$link."&sort=".str_replace("`", "", $fields[$i])."'>".$header[$i]."</a></th>";

			$html .= "</tr>";

			while($row = mysql_fetch_array($rs, MYSQL_ASSOC))
			{
				$html .= '<tr class="report-row" onMouseover=setClass("report-row-over",this) onMouseout=setClass("report-row",this)>';

				for($i = 0; $i < count($fields); $i++)
				{

					$field = str_replace("`", "", $fields[$i]);
					if($field=='email')
						$html .= '<td align="'.$align[$i].'"><a href="/gbSuite/home.php?app=profile&suid='.$row['uid'].'">'.$this->formatNumber($row[$field], $format[$i])."</a></td>";
					else
					$html .= '<td align="'.$align[$i].'">'.$this->formatNumber($row[$field], $format[$i])."</td>";

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
								if($field == "To_Goal")
								{
									$value = ($sum['units_promised'] > 0) ? (($sum['units_delivered'] * 100) / $sum['units_promised']) : 0;
									$html .= $this->formatNumber($value, $format[$i]);
								}
								else
									if($field == "Final_to_Goal")
								{
									$value = ($sum['recommit'] > 0) ? (($sum['units_delivered'] * 100) / $sum['recommit']) : 0;
									$html .= $this->formatNumber($value, $format[$i]);
								}
								if($field == "initial")
								{
									$html .= '';
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