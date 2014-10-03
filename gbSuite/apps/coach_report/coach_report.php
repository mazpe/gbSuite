<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/power_rank/power_rank.php");
	//include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/user.php");
	
    class CoachReport extends Application
	{
		private $currentProfileUID;
		private $currentTitle;
		private $whereClause;
		private $userTitle;
		private $printing = false;
				
		private $uid_sp=null;
		private $option = null;
		private $sort = null;
		private $order = null;
		private $dateFrom = null;
		private $dateTo = null;
		private $from = null;
		private $to = null;		
		private $params = array();
		private $dealerViewId = 0;
		
		public function __construct(){
		$this->appId = 43;
		}
		
			public function print_report()
		{
			?>
			<link rel="stylesheet" href="/css/report.css" rel="stylesheet" type="text/css" />
			<link rel="stylesheet" href="/css/reports_style.css" rel="stylesheet" type="text/css" />
			<link rel="stylesheet" href="/css/report_print.css" type="text/css" media="screen, print" charset="utf-8" />			
			<link rel="stylesheet" href="/css/style.css" type="text/css" media="screen, print" charset="utf-8" />				
			<?
			
			$this->printing = true;
				
			$this->renderHTML();
		}
		
		public function renderHTML()
		{
			$start = "";
			$end = "";
			$dateRange = "";
			
			$currentTitle = $this->user->getCurrentProfileAttribute('title'); //The profile that is being seen
			
			
			//if($currentTitle!='Administrator' && strstr($currentTitle,"Manager"))
			if($currentTitle=='Salesperson')
			{
				return;
			}
		
				
			if(!$this->isInstalled())
			{
				echo $this->notInstalledMessage();
				
				return;
			}								
			
			$this->sort = "name";
			$this->order = "ASC";
			$this->from = "";
			$this->to = "";			
			
			$this->params = array_merge($_GET, $_POST);
			
			if(isset($this->params['sort']))
				$this->sort = $this->params['sort'];
				
			if(isset($this->params['order']))
				$this->order = $this->params['order'];
			if(isset($this->params['uid_sp']))
				$this->uid_sp=$this->params['uid_sp'];
			else
				$this->uid_sp=0;
				
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
		
					$this->dateFrom = $start;
					$this->dateTo = $end;
								
					$query = "SELECT id FROM dealer_view WHERE MONTH(`from`) = MONTH('$start')";
						
					$value = $this->connection->get_value($query);
					
					if($value != null)
					{
						$this->dealerViewId = $value;						
						$this->whereClause = "WHERE dl_date >= '$start' AND dl_date <= '$end' AND I.title='salesperson' and dealer_view_id = $this->dealerViewId";
					}	
					else										
						$this->whereClause = "WHERE dl_date >= '$start' AND dl_date <= '$end' and I.title='salesperson'";
				}
				else //Verify if correspond to a dealer view
				{	
					$query = "SELECT id, date_format(`from`, '%Y%m%d') AS `start`, date_format(`to`, '%Y%m%d') AS `end`, date_format(`from`, '%m/%d/%Y')AS `from`, date_format(`to`, '%m/%d/%Y')AS `to` FROM dealer_view T WHERE label = '$this->option'";
					
					$row = $this->connection->get_row($query);
					
					if($row != null)
					{
						$start = $row['start'];
						$end = $row['end'];
						$this->dealerViewId = $row['id'];
						
						$this->dateFrom = $row['start'];
						$this->dateTo = $row['end'];
												
						$this->from = $row['from'];
						$this->to = $row['to'];
						
						$dateRange = $row['from']." - ".$row['to'];
						 
						$this->whereClause = "WHERE dl_date >= '$start' AND dl_date <= '$end' AND I.title='salesperson' AND dealer_view_id = $this->dealerViewId ";
						
					}	
				}	
			}
			else						
			
			{
				$row = null;
				
				$query = "SELECT id, date_format(`from`, '%Y%m%d') AS `from`, date_format(`to`, '%Y%m%d') AS `to`, date_format(`from`, '%m/%d/%Y') AS `start`, date_format(`to`, '%m/%d/%Y') AS `end` FROM dealer_view WHERE now() BETWEEN `from` AND `to`";
				
				$dateRange = $this->connection->get_row($query);
				$condition = "";
			
				if($dateRange != null)
				{
					$this->dealerViewId = $dateRange['id'];
					
					$condition = "dl_date BETWEEN '".$dateRange['from']."' AND '".$dateRange['to']."' AND dealer_view_id = ".$this->dealerViewId;
					
					$this->from = $dateRange['start'];
					$this->to = $dateRange['end'];
					
					$this->dateFrom = $dateRange['from'];
					$this->dateTo = $dateRange['to'];
				}	 
				else
				{
					$query = "SELECT DATEDIFF(DATE_ADD('2008".date("m")."01', INTERVAL 1 MONTH), '2008".date("m")."01') AS days";
					$days = $this->connection->get_value($query);
					
					$this->from = date("m")."/01/".date("Y");
					$this->to = date("m")."/".$days."/".date("Y");
					 
					$this->dateFrom = date("Y").date("Y")."01";
					$this->dateTo = date("Y").date("m")."$days";
						 
					$condition = "MONTH(dl_date) = MONTH(now())";										
				}			
					
				$this->whereClause = "WHERE $condition and I.title='salesperson' ";

				$dateRange = $this->from." - ".$this->to;					
			}
			
			
			//Si lleva el ruid entonces se esta mandando a llamar desde 
			$this->currentProfileUID = $this->user->getCurrentProfileAttribute('uid');
			$this->currentTitle = $this->user->getCurrentProfileAttribute('title');
			
			$this->userTitle = $this->user->getTitle(); //Title of the logged user
			{
				$htmlTable .= $this->renderTableUnits();
				$htmlTable .= $this->renderTableGrossProfit();
				$htmlTable .= $this->renderTableLeads();
				
				$query="select name from info where uid='$this->uid_sp'";
				$salesperson=$this->connection->get_row($query);
				$datetitle=null;
				
				if($this->to!=null)
				{		
					$row=$this->connection->get_row("SELECT label FROM dealer_view where id=$this->dealerViewId");
					$datetitle=$row[0]. " 2008";
		
				}
				else
				{
					$datetitle=$this->params['option']. " 2008";
				}
							
				$html .= '<div class="app-report">';

				$html.="<table class=header-report align=center>
							<tr><td class=name-report colspan=3>30/30</tr>
							<tr><td width='33%' align=center><strong>$salesperson[0]</strong></td><td width='33%' align=center><strong>$datetitle</strong></td><td width='33%' align=center><strong>".date('m/d/y',time())."</strong></td></tr>";
						
				$selectionBar = 
								'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=coach_report&uid_sp='.$this->uid_sp.'">
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
					$html .= "<tr><td colspan=3>$selectionBar</td></tr>";
					$html .= "</table>";
					
					$link = "/gbSuite/apps/process_application.php?app=coach_report&action=print_report&uid=".$this->user->getUID()."&uid_sp=".$this->uid_sp;
					
					$link .= "&sort=$this->sort&order=$this->order";
					
					if(isset($this->params['option']))
						$link .= '&option='.$this->params['option'];
				
					$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
						
					$html .= '<div class="report-tool-bar" style="text-align:right;"><img src="/images/resources/printButton.png" onclick=popUp("'.$link.'"); /></div>';
				}
				else
					$html .= "</table>";
			
				$html .= "<div>$htmlTable</div>";				
				
				$html.="
				<table class=positive-performance align=center>
					<tr>
						<td class=title-list>
							<label>Positive Performance</label>
						</td>
					</tr>
					<tr>
						<td class=list>
							<label>1)</label>
						</td>
					</tr>
					<tr>	
						<td class=list>
							<label>2)</label>
						</td>
					</tr>
					<tr>	
						<td class=list>
							<label>3)</label>
						</td>
					</tr>
				</table>
				<table class=opportunities align=center>
					<tr>
						<td class=title-list>
							<label>Opportunities</label>
						</td>
					</tr>
					<tr>	
						<td class=list>
							<label>1)</label>
						</td>
					</tr>
					<tr>
						<td class=list>
							<label>2)</label>
						</td>
					</tr>
					<tr>	
						<td class=list>
							<label>3)</label>
						</td>
					</tr>
				</table>
				
				<table class=action-plan-items align=center>
					<tr>
						<td class=title-list>
							<label>Action Plan Items</label>
						</td>
					</tr>
					<tr>
						<td class=list>
							<label>1)</label>
						</td>
					</tr>
					<tr>
						<td class=list>
							<label>2)</label>
						</td>
					</tr>
					<tr>
						<td class=list>
							<label>3)</label>
						</td>
					</tr>
				</table>
				
				<table class=follow-up-prior-month align=center>
					<tr>
						<td class=title-list>
							<label>Follow Up Prior Month</label>
						</td>
					</tr>
					<tr>
						<td class=list>
							<label>1)</label>
						</td>
					</tr>
					<tr>	
						<td class=list>
							<label>2)</label>
						</td>
					</tr>
					<tr>
						<td class=list>
							<label>3)</label>
						</td>
					</tr>
				</table>
				<br>
				<br>
				<br>
				
				<table class=sign-table align=center>
					<tr>
						<td class=sign-row>
							<div class=sign>Associate</div>
						</td>
						<td class=sign-row>
							<div class=sign>Manager</div>
						</td>
					</tr>
					
				</table><br><br>
				";
				
				$html .= "</div>";
						  
				echo $html;
			}
						
			
		}
		
		private function renderTableUnits(){
			$rowCount = 0;
			$html = "";
			$fields = array("`mtdunits`", "`mtdgoals`", "`Units_moreless`", "`teamtotal`", "`sellnews`", "`team_sells_new`", "`rank1`","selluse","team_sells_use","rank2","power_rank" );
			//$fields = array("`avgfront`", "`team`", "`avgback`", "`team`", "`Total_gross`", "`team`", "`mtdavg`" );
			//$totalType = array("sum", "sum", "close", "sum", "sum", "close","sum", "sum", "close");			
			$header = array("Mtd Units", "Mtd Goal", "+/-", "Team %","N", "Team %", "Rank", "U", "Team %", "Rank", "Power Rank");
			$format = array(".", ".", ".", "%", ".", "%", ".", ".", "%", ".", "." );
			$align = array("center", "center", "center", "center", "center", "center","center","center", "center", "center","center");
			
					
			$sums = array();
			$query = "";
			
			for($i = 0; $i < sizeof($fields); $i++)
			{
					$query .= ($query != "" ? "," : "").$fields[$i];
			}				
			
			if($this->dealerViewId != 0)
				$query = "select TM.team_id as id,
						sum(distinct IFNULL(sold_ip,0)+IFNULL(sold_il,0)+IFNULL(sold_sh,0)) as mtdunits,
						IFNULL(goals,0) as mtdgoals,
						sum(distinct IFNULL(sold_ip,0)+IFNULL(sold_il,0)+IFNULL(sold_sh,0)) - IFNULL(goals,0)  as Units_moreless
						from info I 
						left join app_rpt_sales_department RPT using(employee_id) 
						left join goals_settings GS using(uid)
						inner join team_member TM on TM.uid=I.uid
						inner join team T on TM.team_id=T.id ".
						str_replace("dealer_view_id", "GS.dealer_view_id ",$this->whereClause)." and I.uid=$this->uid_sp GROUP BY I.name ORDER BY `$this->sort` $this->order";
			else
			{
				$query = "select TM.team_id as id,
							sum(distinct IFNULL(sold_ip,0)+IFNULL(sold_il,0)+IFNULL(sold_sh,0)) as mtdunits,
							IFNULL(goals,0) as mtdgoals,
							sum(distinct IFNULL(sold_ip,0)+IFNULL(sold_il,0)+IFNULL(sold_sh,0)) - IFNULL(goals,0)  as Units_moreless
							from info I 
							left join app_rpt_sales_department RPT using(employee_id) 
							left join goals_settings GS using(uid)
							inner join team_member TM on TM.uid=I.uid
							inner join team T on TM.team_id=T.id ".
							str_replace("dealer_view_id", "GS.dealer_view_id ",$this->whereClause)." and I.uid=$this->uid_sp GROUP BY I.name ORDER BY `$this->sort` $this->order";
			}
			
			$row = $this->connection->get_row($query);
			//$query="select sum( IF(sp1=0 or sp2=0 ,1,0.5)) as total from info I left join app_rpt_desk_log R on employee_id=sp1 or 
			//employee_id=sp2 ". str_replace(" dealer_view_id = "," ",$this->whereClause)." and I.uid=$this->uid_sp and nu='N'";
			
			$query = "SELECT SUM( new ) AS new, SUM( used ) AS used 
						FROM app_rpt_sales_department
						WHERE employee_id = (SELECT employee_id
												FROM info
													WHERE uid = '$this->uid_sp' ) 
															AND dl_date	BETWEEN '$this->dateFrom' AND '$this->dateTo'";

			$sell_new = $this->connection->get_row($query);
			
			//$row['sellnews'] = $sell_new['total'];					
			//$row['selluse'] = $row['mtdunits'] - $row['sellnews'];

			$row['sellnews'] = $sell_new['new'];					
			$row['selluse'] = $sell_new['used'];
			
			$row['mtdunits'] = $sell_new['new'] + $sell_new['used'];
			$row['Units_moreless'] = $row['mtdunits'] - $row['mtdgoals']; 
			
			/*$query="select count(sp1) from info I
			left join app_rpt_desk_log R on employee_id=sp1 or employee_id=sp2 
			inner join team_member TM using(uid)
			". str_replace("AND dealer_view_id = 2 ","",$this->whereClause)."
			and TM.team_id=$row[id] and nu='N'";
			$sell_team_new=$this->connection->get_row($query);
			$row['team_sells_new']=$sell_team_new[0]==0?0:($row['sellnews']/$sell_team_new[0])*100;
		
		*/
			/*POWER RANK*/
			$ranks=$this->getRank();
			$row['power_rank']=$ranks['power_rank'];			
			 
			 if(!isset($row[0]))
			 	$row[0]=-1;
			$query="select sum(IFNULL(RPT.units,0)) as teamtotal,sum(IFNULL(RPT.new,0)) as team_sells_new, 
			sum(IFNULL(RPT.used,0)) as team_sells_use from info I left join app_rpt_sales_department RPT
			using(employee_id) inner join team_member TM on TM.uid=I.uid " .
			str_replace("dealer_view_id = ", "",$this->whereClause).
			" and TM.team_id=$row[0] GROUP BY TM.team_id ORDER BY `$this->sort` $this->order";
			$total_team=$this->connection->get_row($query);
			
			
			$row['teamtotal']=$total_team[0]==0 || !isset($total_team[0])?0: ($row['mtdunits']/$total_team[0])*100;
			$row['team_sells_new']=$total_team[1]==0 || !isset($total_team[1])?0:($row['sellnews']/$total_team[1])*100;
			$row['team_sells_use']=$total_team[2]==0 || !isset($total_team[2])?0:($row['selluse']/$total_team[2])*100;
			//AQUI validacion al estar vacio
			if(sizeof($row) == 0)
				$row=array(0,0,0,0,0,0,0,0);


			$html = '<br><table class="units" width="100%" align="center" cellspacing=0 cellpadding=0 border=0>
						<tr><td colspan=14><div class="title-table">Units</div></td></tr><tr>';
			
			$order = "";
			
			if($this->order == "ASC")
				$order = "DESC";
			else
				if($this->order == "DESC")
					$order = "ASC";
							
			$link = '/gbSuite/home.php?app=coach_report&order='.$order;
			
			if(isset($this->params['option']))
				$link .= '&option='.$this->params['option'];
				
			$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
			$count=-1;
			for($i = 0; $i < sizeof($header); $count++,$i++){
				if($count==3 || $count==7 || $count==11){
				$html .= "<td class=separator>&nbsp</td>";
				$i--;				
				}
				else{
				$html .= "<td><div class=header-field>".$header[$i]."</div></td>";
				} 
			}
			$html .= "</tr>";
				 

			//$html .= '<tr class="report-row" onMouseover=setClass("report-row-over",this) onMouseout=setClass("report-row",this) >';
			$html .= '<tr class="report-row" onMouseover=setClass("report-row-over",this) onMouseout=setClass("report-row",this) >';
			$count=-1;
				for($i = 0; $i < count($fields); $count++,$i++)				
				{
				if($count==3 || $count==7 || $count==11){
					$html .= "<td class=separator>&nbsp</td>";
					$i--;		
				}else{
					$field = str_replace("`", "", $fields[$i]);
					
					if($field=="team_f"){
					//$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=coach_report&suid='.$row['uid'].'">'.$this->formatNumber("0", "%")."</a></td>";
					$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=coach_report&suid='.$row['uid'].'"><div class=data-field>'.$this->formatNumber("0", "%")."</div></a></td>";
					}
					else{
						if($field=="rank1" || $field=="rank2"){
							$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=coach_report&suid='.$row['uid'].'"><div class=data-field>'.""."</div></a></td>";
						}else
							$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=coach_report&suid='.$row['uid'].'"><div class=data-field>'.$this->formatNumber($row[$field], $format[$i])."</div></a></td>";
					}						
				}					
				}
				$html .= "</tr>";
			
			$html .= "</table><br>";
						
			return $html;
			
		}
		
		private function getRank(){
			$tableName = "power_rank_temp_".$this->appId."_$time";
			
			$whereClause=str_replace("AND I.title='salesperson'","",$this->whereClause);
			$whereClause=substr($whereClause,0,strpos(strtolower($whereClause),"and dealer_view_id"));
			//$whereClause=str_replace("dealer_view_id","",$this->whereClause);
										
			$sql = "CREATE TABLE $tableName Engine=Heap AS select R.sales_person, R.employee_id, SUM(units_sold) AS units_sold, 0 AS us_rank, SUM(total_front_end) AS total_front_end, 0 AS tfe_rank, SUM(total_back_end) AS total_back_end, 0 AS tbe_rank, SUM(total_gross) AS total_gross, 0 AS tg_rank, SUM(total_score) AS total_score, 0 AS power_rank, uid  
					from app_rpt_power_rank R inner join info using(employee_id)
					".$whereClause." GROUP BY R.sales_person;LOCK TABLE $tableName WRITE,$tableName as PR write,$tableName as t1 write" .
							",$tableName as t2 write ,$tableName as t3 write ,$tableName as t4 write,$tableName as t5 write,power_rank_setup as pw read; ";
			
			
		 foreach(split(';',$sql) as $query)
		 { 	 	
		 	if(trim($query) <> "" )		 	
		 		$this->connection->exec_query($query);
		 }
		 	PowerRank::updatePowerRank($tableName,$this->connection);		
		
			$query = "SELECT * FROM power_rank_setup as pw";
			$powerRankSetup = $this->connection->get_row($query);
			
			$unitsMultiplier = $powerRankSetup['units_multiplier'];
			$unitsPercentage = ($powerRankSetup['units_percentage'] > 0 ? $powerRankSetup['units_percentage'] / 100 : 0);
			$frontPercentage = ($powerRankSetup['front_percentage'] > 0 ? $powerRankSetup['front_percentage'] / 100 : 0);
			$backPercentage = ($powerRankSetup['back_percentage'] > 0 ? $powerRankSetup['back_percentage'] / 100 : 0);
			$totalPercentage = ($powerRankSetup['total_percentage'] > 0 ? $powerRankSetup['total_percentage'] / 100 : 0);
			
			
			
			$sql = "SELECT power_rank FROM $tableName where uid=$this->uid_sp";
			
			//$results = $this->connection->exec_query($sql);
			$results = $this->connection->get_row($sql);
			
			
			$sql="DROP TABLE $tableName";
			$this->connection->exec_query($sql);
			$this->connection->exec_query('UNLOCK TABLES');
			return $results;
		}
		
		private function renderTableLeads()
		{	
			$rowCount = 0;
			
			$fields = array("`showroom`", "`sold_sh`", "`close_sh`", "`iphone`", "`sold_ip`", "`close_ip`", "`ileads`", "`sold_il`", "`close_il`");
		//	$totalType = array("sum", "sum", "close", "sum", "sum", "close","sum", "sum", "close");			
			$header = array("ShowRoom", "Sold", "Close", "Phone", "Sold", "Close", "Internet", "Sold", "Close");
			$format = array(".", ".", "%", ".", ".", "%", ".", ".", "%");
			$align = array("center", "center", "center", "center", "center", "center","center", "center", "center");
					
			$sums = array();
			$query = "";
			
			for($i = 0; $i < sizeof($fields); $i++)
			{
					$query .= ($query != "" ? "," : "").$fields[$i];
			}				
			
			if($this->dealerViewId != 0)
				$query = "select I.name as salesperson, IFNULL(L.showroom,0) as showroom,
							sum(distinct IFNULL(RPT.sold_sh,0)) as sold_sh, 
							(sum(distinct IFNULL(RPT.sold_sh,0)))/(IFNULL(L.showroom,0))*100  as close_sh,
							IFNULL(L.iphone,0) as iphone,
							sum(distinct IFNULL(RPT.sold_ip,0)) as sold_ip,
							IFNULL((sum(distinct IFNULL(RPT.sold_ip,0)))/(IFNULL(L.iphone,0)),0)*100  as close_ip,
							IFNULL(L.ileads,0) as ileads,
							sum(distinct IFNULL(RPT.sold_il,0)) as sold_il,
							IFNULL((sum(distinct IFNULL(RPT.sold_il,0)))/(IFNULL(L.ileads,0)),0)*100  as close_il
							from info I 
							left join app_rpt_sales_department RPT using(employee_id)
							left join lead_control L on L.uid=I.uid inner join team_member TM on TM.uid=I.uid
							inner join team T on T.id=TM.team_id
							".str_replace("dealer_view_id", "L.dealer_view_id",$this->whereClause)." and I.uid=$this->uid_sp GROUP BY I.name ORDER BY `$this->sort` $this->order";
			else
			{
				$query = "select I.name as salesperson, IFNULL(L.showroom,0) as showroom,
							sum(distinct IFNULL(RPT.sold_sh,0)) as sold_sh, 
							(sum(distinct IFNULL(RPT.sold_sh,0)))/(IFNULL(L.showroom,0))*100  as close_sh,
							IFNULL(L.iphone,0) as iphone,
							sum(distinct IFNULL(RPT.sold_ip,0)) as sold_ip,
							IFNULL((sum(distinct IFNULL(RPT.sold_ip,0)))/(IFNULL(L.iphone,0)),0)*100  as close_ip,
							IFNULL(L.ileads,0) as ileads,
							sum(distinct IFNULL(RPT.sold_il,0)) as sold_il,
							IFNULL((sum(distinct IFNULL(RPT.sold_il,0)))/(IFNULL(L.ileads,0)),0)*100  as close_il
							from info I 
							left join app_rpt_sales_department RPT using(employee_id)
							left join lead_control L on L.uid=I.uid inner join team_member TM on TM.uid=I.uid
							inner join team T on T.id=TM.team_id
							".str_replace("dealer_view_id", "L.dealer_view_id",$this->whereClause)." and I.uid=$this->uid_sp GROUP BY I.name ORDER BY `$this->sort` $this->order";
			}
			
			//$rs = $this->connection->exec_query($query);
			
			$query = "SELECT SUM(sold_ip) AS sold_ip, SUM(sold_il) AS sold_il, SUM(sold_sh) AS sold_sh 
						FROM app_rpt_sales_department  
							WHERE employee_id = (SELECT employee_id FROM info WHERE uid = '$this->uid_sp') AND dl_date BETWEEN '$this->dateFrom' AND '$this->dateTo'";
			
			$rsSales = $this->connection->get_row($query); 

			$query = "SELECT showroom, iphone, ileads FROM lead_control WHERE dealer_view_id = $this->dealerViewId AND uid = '$this->uid_sp'";
			$rsLeads = $this->connection->get_row($query);

			$rsSales = array_merge($rsLeads, $rsSales);
			
			$rsSales['close_sh'] = ($rsSales['showroom'] > 0 ? ($rsSales['sold_sh'] * 100)/$rsSales['showroom'] : 0);
			$rsSales['close_ip'] = ($rsSales['iphone'] > 0 ? ($rsSales['sold_ip'] * 100)/$rsSales['iphone'] : 0);
			$rsSales['close_il'] = ($rsSales['ileads'] > 0 ? ($rsSales['sold_il'] * 100)/$rsSales['ileads'] : 0);
						
			$html = '<br><table class="leads" align="center" cellspacing=0 cellpadding=0 >
			<tr><td colspan=12><div class="title-table">Leads</div></td></tr><tr>';
				
			//$html = '<br><table class="app-report-table" width="100%" cellspacing=0 cellpadding=0 border=0><tr><td class="team-row" colspan=9>Leads</td></tr><tr>';
			
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
						
			/*for($i = 0; $i < sizeof($header); $i++)
				$html .= "<th><a href='".$link."&sort=".str_replace("`", "", $fields[$i])."'>".$header[$i]."</a></th>";*/
				
			$count = 0;
			 
			for($i = 0; $i < sizeof($header); $count++,$i++)
			{
				if($count==3 || $count==7 || $count==10)
				{
					$html .= "<td class=separator>&nbsp</td>";
					$i--;				
				}
				else
				{
					$html .= "<td><div class=header-field>".$header[$i]."</div></td>";
				} 
			}
			
			$html .= "</tr>";
			
			$html .= '<tr class="report-row" onMouseover=setClass("report-row-over",this) onMouseout=setClass("report-row",this) >';
		
			for($i = 0, $count=0; $i < count($fields); $count++,$i++)				
			{
				if($count==3 || $count==7 || $count==10)
				{
					$html .= "<td class=separator>&nbsp</td>";
					$i--;				
				}
				else
				{
					$field = str_replace("`", "", $fields[$i]);

					$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=sp_analysis&suid='.$this->uid_sp.'">'.$this->formatNumber($rsSales[$field], $format[$i])."</a></td>";
				}					
			}
			
			$html .= "</tr>";
		
			$html .= "</table><br>";
						
			return $html;
		}
		
		private function renderTableGrossProfit()
		{
			
			$rowCount = 0;
			
			$fields = array("`avgfront`", "`teamfront`", "`avgback`", "`teamback`", "`Total_gross`", "`team_totalgross`", "`mtdavg`" );
			//$fields = array("`avgfront`", "`team`", "`avgback`", "`team`", "`Total_gross`", "`team`", "`mtdavg`" );
		//	$totalType = array("sum", "sum", "close", "sum", "sum", "close","sum", "sum", "close");			
			$header = array("Front End Total", "Team %", "Back End Total", "Team %", "Total Gross", "Team %", "Unit Average");
			$format = array("$", "%", "$", "%", "$", "%", "$");
			$align = array("center", "center", "center", "center", "center", "center","center");
					
			$sums = array();
			$query = "";
			
			for($i = 0; $i < sizeof($fields); $i++)
			{
					$query .= ($query != "" ? "," : "").$fields[$i];
			}				
			
			if($this->dealerViewId != 0)
			$query = "SELECT TM.team_id as id, sum(distinct gross_front) as avgfront, sum(distinct gross_back) as avgback, 
			sum(distinct gross_total) as Total_gross,sum(distinct gross_total)/sum(distinct units) as mtdavg 
			FROM app_rpt_sales_department R INNER JOIN info I 
			ON R.employee_id = I.employee_id INNER JOIN team_member TM 
			ON I.uid = TM.uid 
				".str_replace(" dealer_view_id = "," ",$this->whereClause)." and I.uid=$this->uid_sp GROUP BY I.name ORDER BY `$this->sort` $this->order";
			else
			$query = "SELECT TM.team_id as id, sum(distinct gross_front) as avgfront, sum(distinct gross_back) as avgback, 
					sum(distinct gross_total) as Total_gross,sum(distinct gross_total)/sum(distinct units) as mtdavg
					FROM app_rpt_sales_department R INNER JOIN info I 
					ON R.employee_id = I.employee_id INNER JOIN team_member TM 
					ON I.uid = TM.uid 
					".str_replace(" dealer_view_id = "," ",$this->whereClause)." and I.uid=$this->uid_sp GROUP BY I.name ORDER BY `$this->sort` $this->order";

			$row = $this->connection->get_row($query);

			
			if($row[0]=="")
				$row[0]=-1;
			
			$query="SELECT SUM(gross_back) as teamback, SUM(gross_front) as teamfront, SUM(gross_total) as team_totalgross
			FROM app_rpt_sales_department R INNER JOIN info I
			ON R.employee_id = I.employee_id INNER JOIN team_member TM
			ON I.uid = TM.uid 
			". str_replace(" dealer_view_id = "," ",$this->whereClause)." and TM.team_id=$row[0] group by TM.team_id";
			$row_team=$this->connection->get_row($query);
		
				
			//AQUI validacion al estar vacio
			if(sizeof($row) == 0)
				$row=array(0,0,0,0,0,0,0,0);
			$row['teamfront']=$row_team['teamfront']==0?0:($row['avgfront']/$row_team['teamfront'])*100;
			$row['teamback']=$row_team['teamback']==0?0:($row['avgback']/$row_team['teamback'])*100;
			$row['team_totalgross']=$row_team['team_totalgross']==0?0:($row['Total_gross']/$row_team['team_totalgross'])*100;
				
			//$html = '<br><table class="app-report-table" width="100%" cellspacing=0 cellpadding=0 border=0><tr><td class="team-row" colspan=9>Gross Profit</td></tr><tr>';
			$html = '<br><table class="gross-profit" align="center" cellspacing=0 cellpadding=0>
						<tr><td colspan=10><div class="title-table">Gross Profit</div></td></tr><tr>';
						
			$order = "";
			
			if($this->order == "ASC")
				$order = "DESC";
			else
				if($this->order == "DESC")
					$order = "ASC";
							
			$link = '/gbSuite/home.php?app=coach_report&order='.$order;
			
			if(isset($this->params['option']))
				$link .= '&option='.$this->params['option'];
				
			$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
			$count=0;
			for($i = 0; $i < sizeof($header); $count++,$i++){
				if($count==2 || $count==5 || $count==7){
				$html .= "<td class=separator>&nbsp</td>";
				$i--;				
				}
				else{
				$html .= "<td><div class=header-field>".$header[$i]."</div></td>";
				} 
			}			
				
			$html .= "</tr>";
				 

			$html .= '<tr class="report-row" onMouseover=setClass("report-row-over",this) onMouseout=setClass("report-row",this) >';
			$count=0;
				for($i = 0; $i < count($fields); $count++,$i++)				
				{
				if($count==2 || $count==5 || $count==7){
				$html .= "<td class=separator>&nbsp</td>";
				$i--;				
				}
				else{
					$field = str_replace("`", "", $fields[$i]);
					
					if($field=="team"){
					$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=sp_analysis&suid='.$row['uid'].'">'.$this->formatNumber("0", "%")."</a></td>";
					}
					else{
					$html .= '<td onMouseover=setClass("report-cell-over",this) onMouseout=setClass("report-cell",this) align="'.$align[$i].'"><a href="/gbSuite/home.php?app=sp_analysis&suid='.$row['uid'].'">'.$this->formatNumber($row[$field], $format[$i])."</a></td>";
					}						
				}					
				}
				$html .= "</tr>";
			
			$html .= "</table><br>";
						
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
