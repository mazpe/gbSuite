<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class GoalsSettings extends Application
	{
		private $option = null;
		private $sort = null;
		private $order = null;
		private $from = null;
		private $to = null;		
		private $params = array();
		private $printing = false;
		
		private $dealerView=null;
		
		public function __construct()
		{
			$this->appId = 42;	
		}
		
		public function print_report($params)
		{
			?>
				<link rel="stylesheet" href="/css/report_print.css" type="text/css" media="screen, print" charset="utf-8" />				
			<?
			
			$this->printing = true;
				
			$this->renderHTML();
		}
		
		private function setupGoals(){
			
			$sql="select * from dealership_goals where dealer_view_id=$this->dealerView";
			$result=$this->connection->get_row($sql);
			if(count($result)==0){
				$sql="insert into dealership_goals(new_units, used_units,dealer_view_id) values (0,0,$this->dealerView)";
				$this->connection->exec_query($sql);
				$new_units=0;
				$used_units=0;
									
			}else{
				$new_units=$result['new_units'];
				$used_units=$result['used_units'];
			}
			
			$html .= "<div class='report-title'>Dealership Goals<br/>$dateRange</br></div>";
			$html.='<div align=center>';
			
			$html .= "<table><tr><td>New Units</td><td><input id=dealerships_goals_new_units onkeyup=updateDealerShipGoals() type=text value=$new_units></td>";
			
			$html.="<td>Used Units</td><td><input id=dealerships_goals_used_units onkeyup=updateDealerShipGoals() type=text value=$used_units></td>";
			$html.="<td>Total Units</td><td><input id=dealerships_goals_total_units type=text value=".($used_units+$new_units)." disabled></td>";
			
			if(!$this->printing)
			$html.="<td><input type=submit class=button-link name=option value=Save onClick=saveDealerShip($this->dealerView);return false; /></td></tr>";			

			$html.='</table>';
			$html.='<div id=dealerships_goals_message style="display:none;border: solid 1px #DEDEDE; background:#FFFFCC;font-size:10px;color:#222222;padding:4px;text-align:center; width:20%">Data Saved</div><br>';
			$html.='</div>';
				
			return $html;
		}
		
		public function saveDealerShips()
		{
			$sql="update dealership_goals set new_units=$_GET[nu], used_units=$_GET[uu] where dealer_view_id=$_GET[dv]";
			$this->connection->exec_query($sql);
			
			echo '{"message": "DealerShip Goal Changed"}';			
		}
		
		private function renderTable()
		{
			$where = "";
			$start = "";
			$end = "";
			$dateRange = "";
						
			$this->from = "";
			$this->to = "";			
			
			$this->params = array_merge($_GET, $_POST);
			
			if(isset($this->params['sort']))
				$this->sort = $this->params['sort'];
				
			if(isset($this->params['order']))
				$this->order = $this->params['order'];
			
			$currentDate = $this->connection->get_value("SELECT date_format(now(), '%m/%d/%Y') AS currentDate");
			
			$query = "";
				
			if(isset($this->params['option']) && $this->params['option'] != "")
			{
				$this->option = $this->params['option'];
				
				$query = "SELECT id, date_format(`from`, '%Y%m%d') AS `start`, date_format(`to`, '%Y%m%d') AS `end`, date_format(`from`, '%m/%d/%Y')AS `from`, date_format(`to`, '%m/%d/%Y')AS `to` FROM dealer_view WHERE label = '$this->option'";
			}
			else
				$query = "SELECT id, date_format(`from`, '%Y%m%d') AS `start`, date_format(`to`, '%Y%m%d') AS `end`, date_format(`from`, '%m/%d/%Y')AS `from`, date_format(`to`, '%m/%d/%Y')AS `to` FROM dealer_view WHERE now() BETWEEN `from` AND `to`";			

			$dealerView = $this->connection->get_row($query);
			
			if($dealerView != null)
			{
				$start = $dealerView['start'];
				$end = $dealerView['end'];
				
				$this->from = $dealerView['from'];
				$this->to = $dealerView['to'];
				
				$dateRange = $dealerView['from']." - ".$dealerView['to']; 
				$where = "WHERE dealer_view_id = ".$dealerView['id'];
				$this->dealerView=$dealerView['id'];
			}	
						
			$html = "";
			
			/**SELECT ALL RECORDS**/
			/*$query = "SELECT DISTINCT I.uid   
						FROM app_rpt_sales_department R INNER JOIN info I
						ON R.employee_id = I.employee_id LEFT JOIN lead_control L
						ON I.uid = L.uid
						WHERE R.dl_date BETWEEN '$start' AND '$end' AND L.id IS NULL";
			*/
			$query = "SELECT DISTINCT I.uid, D.id, label, L.id
						FROM info I JOIN dealer_view D LEFT JOIN goals_settings L
							ON I.uid = L.uid AND D.id = L.dealer_view_id 
						WHERE L.id IS NULL AND I.title = 'Salesperson' AND D.id = ".$dealerView['id'];

			$rs = $this->connection->exec_query($query);
			
			if($rs !== false)
			{
				while($row = mysql_fetch_assoc(($rs)))
				{
					$query = "INSERT INTO goals_settings (uid, dealer_view_id, goals,recommit) VALUES ('".$row['uid']."', ".$dealerView['id'].", 0, 0)";
							
					$this->connection->exec_query($query);
				}					
			}	
			
			$html .= "<div class='app-content'>";
			$html.=$this->setupGoals();
			if(!$this->printing)			
			$html .= "<div class='report-title'>Goals Setup<br/>$dateRange</div>";
			else
			$html .= "<div class='report-title'>Goals<br/>$dateRange</div>";
			
			$selectionBar = 
				'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=goals_settings">
					<div id="filter" class="" style="text-align:center;">
						<input id=desklog-sort'.$this->appId.' type=hidden name=sort value="'.$this->sort.'" />
						<input id=desklog-order'.$this->appId.' type=hidden name=order value="'.$this->order.'" />
						<input id=desklog-option'.$this->appId.' type=hidden name=option value="'.$this->option.'" />
						
						<input id="dealer-view-button'.$this->appId.'" class="desklog-dealer-options" type="button" value="Month" onclick="showDealerView('.$this->appId.')" />&nbsp
						<input id="date-range-button'.$this->appId.'" class="desklog-dealer-options" type="button" style="display:none;" value="Date Range" onclick="showDateRange('.$this->appId.')" />&nbsp
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
				
				$link = "/gbSuite/apps/process_application.php?app=goals_settings&action=print_report&uid=".$this->user->getUID();
				
				$link .= "&sort=$this->sort&order=$this->order";
					
				if(isset($this->params['option']))
					$link .= '&option='.$this->params['option'];
				
				$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
			
				$html .= '<div class="report-tool-bar" style="text-align:right;"><img src="/images/resources/printButton.png" onclick=popUp("'.$link.'"); /></div>';
			
			}
				
				
									
				
			//$html .= '<div class="lead-control-container">' .
			//		'<form method="post" action=/gbSuite/apps/process_application.php?app=lead_control&action=save&uid='.$this->user->getUID().'&redirect=lead_control>';
			$html .= '<div class="lead-control-container">' .
					'<form method="post" action=/gbSuite/home.php?app=goals_settings&action=save&uid='.$this->user->getUID().'>';
			if(!$this->printing){					
			$html .= '<input class="button-link" type=submit value="Save">';				
			$html .= '<input class="button-link" type=button value="Go back" onclick=document.setLocation("/gbSuite/home.php")>';
			}
			$html .= '<table class="lead-control-table" cellpadding=1>';				
			$html .= 	'<th>Name</th>
						<th>Goal</th>
						<th>ReCommit</th>';
			
			$count = 0;			
		
			$query = "SELECT L.id, I.name, I.uid, L.goals,L.recommit    
						FROM info I INNER JOIN goals_settings L
						ON I.uid = L.uid $where and active=1 ORDER BY I.name";
			
			$rs = $this->connection->exec_query($query);
			$rows=array("total_goals"=>0, "total_recommit"=>0);
			if($rs !== false)
			{
				 $rows_count=mysql_num_rows($rs);	
				while($row = mysql_fetch_assoc($rs))
				{
					$uid=$row['uid'];
					$rows['total_goals']+=$row['goals'];
					$rows['total_recommit']+=$row['recommit'];
					$html .= '<tr>';
					if(!$this->printing)
					$html .= '<td width=10%><input type="hidden"  name="goals_settings_id'.$count.'" value="'.$row['id'].'" /><fb:name uid='.$uid.' /></td>';
					else
					$html .= '<td width=10%><input type="hidden"  name="goals_settings_id'.$count.'" value="'.$row['id'].'" />'.$row['name'].'</td>';
					$html .= '<td align=center width=10%><input id="goals_settings_goals'.$count.'" onkeyup=updateGoalsSettings('.$rows_count.') class="lead-control-input" type="text" name="goals_settings_goals'.$count.'" value="'.$row['goals'].'" '.($this->printing?"disabled":"").' /></td>';
					$html .= '<td align=center width=10%><input id="goals_settings_recommit'.$count.'" onkeyup=updateGoalsSettings('.$rows_count.') class="lead-control-input" type="text" name="goals_settings_recommit'.$count.'" value="'.$row['recommit'].'" '.($this->printing?"disabled":"").' /></td>';
					$html .= '</tr>';					
					
					++$count;
				}		

					$html .= '<tr>';
					$html .= '<td width=10%><input type="hidden" name="goals_settings_id"/>Total</td>';
					$html .= '<td align=center width=10%><input id=total_goals class="lead-control-input" type="text" value='.$rows['total_goals'].' disabled=true /></td>';
					$html .= '<td align=center width=10%><input id=total_recommit class="lead-control-input" type="text" value='.$rows['total_recommit'].' disabled=true  /></td>';
					$html .= '</tr>';
					
				$html .= '</table>';
				
				$html .= '<input id=desklog-option'.$this->appId.' type=hidden name=option value="'.$this->option.'" />
						  <input type=hidden name="from'.$this->appId.'" value="'.$this->from.'"/>
						  <input type=hidden name="to'.$this->appId.'" value="'.$this->to.'"/>';
																  
				$html .= '<input type="hidden" name="goals_settings_row_count" value="'.$count.'" />';
				
				if(!$this->printing)
				{
					$html .= '<input class="button-link" type=submit value="Save">';				
					$html .= '<input class="button-link" type=button value="Go back" onclick=document.setLocation("/gbSuite/home.php")>';					
				}
				
				$html .= '</form></div>';
			}
			
			echo $html;
		}
		
		public function save($params)
		{
			$rowCount = 0;
			$id = "";
			$goals= 0;
			$soldIP = 0;
			$recommit = 0;
			$soldSH = 0;			
			$soldIL = 0;
			$prefix = "goals_settings_";
			
			$rowCount = $params[$prefix.'row_count'];
			$query = "";
			
			
				
			for($i = 0; $i < $rowCount; $i++)
			{
				$id = $params[$prefix.'id'.$i];
				
				$goals = $params[$prefix.'goals'.$i];

				$recommit = $params[$prefix.'recommit'.$i];

				$goals= ($goals != "" ? (is_numeric($goals) ? $goals: 0) : 0);
				
				$recommit= ($recommit!= ""? (is_numeric($recommit) ? $recommit: 0) : 0);
				
				$query = "UPDATE goals_settings SET goals= $goals, recommit= $recommit WHERE id = $id";
				
				
				
				$this->connection->exec_query($query);
				
			}
		}
		
		public function renderHTML()
		{
			if($this->html != "")
				echo $this->html;	
			else
				$this->renderTable();
		}
	}
?>
