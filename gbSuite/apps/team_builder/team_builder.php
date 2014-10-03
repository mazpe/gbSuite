<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/gbSuiteConfig.php';
	
    class TeamBuilder extends Application 
	{
		private $printing = false;
		private $option = null;		
		private $from = null;
		private $to = null;
		private $dateRange = null;
		private $params = array();
		private $dealerViewId = 0;
		
		public function __construct()
		{
			$this->appId = 23;			
		}	
		
		public function print_report($params)
		{
			?>			
				<link rel="stylesheet" href="/css/report_print.css" type="text/css" media="screen, print" charset="utf-8" />
				<link rel="stylesheet" href="/css/team_builder.css" type="text/css" media="screen, print" charset="utf-8" />
			<?
			
			$this->printing = true;
			$this->renderTable();	
		}
		
		private function renderTable()
		{
			$this->from = "";
			$this->to = "";			
			$this->dateRange = "";
			
			$this->params = array_merge($_GET, $_POST);
			
			$currentDate = $this->connection->get_value("SELECT date_format(now(), '%m/%d/%Y') AS currentDate");
				
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
				
				$this->dealerViewId = $dealerView['id'];
				
				$this->from = $dealerView['from'];
				$this->to = $dealerView['to'];
				
				$dateRange = $dealerView['from']." - ".$dealerView['to']; 
				$where = "WHERE dealer_view_id = ".$dealerView['id'];
				
				
			}	
			
			$html = "";
			
			$title = "Team Builder";
				
			$html .= '<div class="app-report">';
			$html .= "<div style='text-align:center'>";
			$html .= "<div class=report-title>$title<br/>$dateRange</div>";
			
			$html .= "</div>";
			
			$selectionBar = 
				'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=team_builder">
					<div id="filter" class="" style="text-align:center;">
						<input id=desklog-sort'.$this->appId.' type=hidden name=sort value="'.$this->sort.'" />
						<input id=desklog-order'.$this->appId.' type=hidden name=order value="'.$this->order.'" />
						<input id=desklog-option'.$this->appId.' type=hidden name=option value="'.$this->option.'" />
						
						<input id="dealer-view-button'.$this->appId.'" class="desklog-dealer-options" type="button" value="Month" onclick="showDealerView('.$this->appId.')" />&nbsp
						<input style="display:none;" id="date-range-button'.$this->appId.'" class="desklog-dealer-options" type="button" value="Date Range" onclick="showDateRange('.$this->appId.')" />&nbsp 
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
				
				$link = "/gbSuite/apps/process_application.php?app=team_builder&action=print_report&uid=".$this->user->getUID();
				
				$link .= "&sort=$this->sort&order=$this->order";

				if(isset($this->params['option']))
					$link .= '&option='.$this->params['option'];
				
				$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
					
				$html .= '<div class="report-tool-bar" style="text-align:right;"><img src="/images/resources/printButton.png" onclick=popUp("'.$link.'"); /></div>';
			}
			
			$html .= '<div class="team-builder">
						<div class="team-builder-bar">
							'.(!$this->printing ? '<label><a href="/gbSuite/home.php?app=team_builder&action=add">Add</a></label>' : '').'
						</div>';

			$query = "SELECT COUNT(id) FROM team WHERE dealer_view_id = ".$this->dealerViewId;
			$teamCount = $this->connection->get_value($query);
			
			//Insert teams of the past month
			if($teamCount == 0)
			{
				//Get the max dealerViewId
				$query = "SELECT MAX(dealer_view_id) AS maxid FROM team";				
				$maxDealerViewId = $this->connection->get_value($query); 
				
				$query = "SELECT * FROM team WHERE dealer_view_id = $maxDealerViewId";				
				$rs = $this->connection->exec_query($query);  
				
				if($rs !== false)
					while($row = mysql_fetch_assoc($rs))
					{
						$query = "INSERT INTO team VALUES (0, '".$row['team']."', $this->dealerViewId)";					
						$this->connection->exec_query($query);
						
						$id = mysql_insert_id();
						$query = "INSERT INTO team_member 
									SELECT 0, ".$id.", uid
										FROM team_member 
											WHERE team_id = ".$row['id'];
						
						$this->connection->exec_query($query);
					}
			}	
			
			$query = "SELECT id, team FROM team $where ORDER by team ";
			
			$rs = $this->connection->exec_query($query);
			
			if($rs !== false )	
			{
				while($row = mysql_fetch_assoc($rs))
				{	
					$editLink = '/gbSuite/home.php?app=team_builder&action=edit&team_id='.$row['id'];
					
					$html .= '<div class="team">
								<div><label>'.$row['team'].'</label>'.(!$this->printing ? '<label><a href="'.$editLink.'"><img src="/images/resources/vineta.gif"/>&nbsp;Edit</a></label>&nbsp;<label><a href="/gbSuite/home.php?app=team_builder&uid='.$this->user->getUID().'&action=delete&team_id='.$row['id'].'&confirm=true"><img src="/images/resources/vineta.gif"/>&nbsp; Delete</a></label>' : '').'</div>';
								
					$membersHTML = "";
						
					$members = $this->getTeamMembers($row['id']);
					
					if($members !== false )	
					{
						while($member = mysql_fetch_assoc($members))
							$membersHTML .= '<a href="/gbSuite/home.php?app=profile&uid='.$member['uid'].'">'.$member['name'].'</a>&nbsp;&nbsp;&nbsp';		
					}
					
					$html .= '<div class="members">'.$membersHTML.'</div>';
					
					$html .= '</div>'; //Close div team
				}
			}			
			
			$html .= '<div class="team"
						<div><label>Other</label></div>';
							
			$membersHTML = "";
				
			$members = $this->getAvailableAssociates();
			
			if($members !== false )	
			{
				while($member = mysql_fetch_assoc($members))
					$membersHTML .= '<a href="/gbSuite/home.php?app=profile&uid='.$member['uid'].'">'.$member['name'].'</a>&nbsp;&nbsp;&nbsp';		
			}
			
			$html .= '<div class="members">'.$membersHTML.'</div>';
			
			$html .= '</div>'; //close div team
			$html .= '</div>'; //close div team-builder
			
			echo $html;
		}
		
		public function renderHTML()
		{
			if($this->html != "")
				echo $this->html;
			else
				$this->renderTable();
		}
		
		//The order correspond with the order that comes the appId items.
		public function add()
		{
			$this->from = "";
			$this->to = "";			
			$this->dateRange = "";
			
			$this->params = array_merge($_GET, $_POST);
			
			$currentDate = $this->connection->get_value("SELECT date_format(now(), '%m/%d/%Y') AS currentDate");
				
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
				
				$this->dealerViewId = $dealerView['id'];
				
				$this->from = $dealerView['from'];
				$this->to = $dealerView['to'];
				
				$dateRange = $dealerView['from']." - ".$dealerView['to']; 
				$where = "WHERE dealer_view_id = ".$dealerView['id'];
			}	
			
			$html = "";
			
			$html .= 	'<div class="team-builder-title">Add Team</div>';
			$html .=	'<form method=post action=/gbSuite/apps/process_application.php?app=team_builder&action=save&uid='.$this->user->getUID().'&redirect=team_builder>';
			$html .= 	'<input type="hidden" name="dealer_view_id" value="'.$this->dealerViewId.'" />';
			$html .= 	'<table class="team-name-add">
							<tr>
								<td>
									Team Name:		
								</td>
								<td>
									<input name="team" type="text" value="">		
								</td>
							</tr>
						</table>
						<input class="button-link" name="team-name" type="submit" value="Save">
						<input class="button-link" name="team-name" type="button" value="Cancel" onclick=document.setLocation("/gbSuite/home.php?app=team_builder") />';
			$html .= 	'</form>';
				
			//$html = "<div class=team-add><label class=team-add-label>Team</label><input class=team-add-input name=team type=text><input type=submit value='Submit'/><a href=/gbSuite/home.php?app=team_builder>Go back</a></div></form>";
			
			$this->html = $html;			
		}
		
		public function edit($params)
		{
			$row = null;
			
			$query = "SELECT id, date_format(`from`, '%Y%m%d') AS `from`, date_format(`to`, '%Y%m%d') AS `to`, date_format(`from`, '%m/%d/%Y') AS `start`, date_format(`to`, '%m/%d/%Y') AS `end` FROM dealer_view WHERE now() BETWEEN `from` AND `to`";
			
			$dateRange = $this->connection->get_row($query);
			$condition = "";
		
			if($dateRange != null)
				$this->dealerViewId = $dateRange['id'];

			$query = "SELECT team FROM team WHERE id = ".$params['team_id'];
			
			$teamName = $this->connection->get_value($query);
			
			$html = "<input type=hidden id=team-id value=".$params['team_id'].">";
			
			$html .= 	'<div class=team-title><input type="text" value="'.$teamName.'" id="team-name" />
							<input class="button-link" type="button" value="Change name" onclick="changeTeamName('.$params['team_id'].')" />
							<input class="button-link" type=button onclick=saveTeam() value="Save team members" />
							<input class="button-link" type="button" value="Go back" onclick=document.setLocation("/gbSuite/home.php?app=team_builder") />							
						</div>';
			
			$html .= '<table class="team-members-table" border=1px cellpadding=10px cellmargin=0 cellspacing=0>
						<tr><th>Available</th><th>Members</th></tr>
						<tr>						
							<td width=50% height="100%">'.$this->availableAssociates($params['team_id']).'</td>
							<!--td>
								<input type="button" value=">>" onclick="addItems()" />
								<br/>
							    <input type="button" value="<<" onclick="removeItems()" />
							</td-->
							<td width=50% height="100%">'.$this->members($params['team_id']).'</td>
						</tr>	
					 </table>';
					 
			$this->html = $html;		
		}
		
		public function update_team($params)
		{
			$members = explode(",", $params['uids']);
			
			$query = "SELECT uid FROM team_member WHERE team_id = ".$params['team_id'];			
			$rsMembers = $this->connection->exec_query($query);
			
			$existingMembers = array();
			
			while($row = mysql_fetch_assoc($rsMembers))
				$existingMembers[] = $row['uid'];
			 
			$query = "DELETE FROM team_member WHERE team_id = ".$params['team_id'];			
			$this->connection->exec_query($query);
			
			
			$query = "SELECT team FROM team WHERE id = ".$params['team_id'];
			
			$teamName = $this->connection->get_value($query);
			 
			for($i = 0; $i < count($members); $i++)
			{
				$query = "INSERT INTO team_member (id, team_id, uid) VALUES (0, ".$params['team_id'].", '".$members[$i]."')";
				
				$this->connection->exec_query($query);
				
				if(!in_array($members[$i], $existingMembers))
				{
					$query = "INSERT INTO news (uid, type, value, date, status) ".						  	
								"SELECT uid, 'associate', CONCAT('<fb:name uid=', '".$members[$i]."', ' />', ' just joined the ".$teamName." team.'), now(), 0 ".
				  				"FROM info ";
					
					$this->connection->exec_query($query);
				}
			}
					
			echo '{"query":"true"}';
		}
		
		public function change_name($params)
		{
			$query = "UPDATE team SET team = '".$params['team_name']."' WHERE id = ".$params['team_id'];
			
			$this->connection->exec_query($query);
			
			echo '{"message":"'.$query.'"}';
		}
		
		public function delete($params)
		{
			if(!isset($params['confirm']))
			{
				$query = "DELETE FROM team WHERE id = ".$params['team_id'];
				
				$this->connection->exec_query($query);
				
				$query = "DELETE FROM team_member WHERE team_id = ".$params['team_id'];
				$this->connection->exec_query($query);	
			}
			else
				if($params['confirm'] == 'true')
				{
					$html = "";
					$html .= "<div>Are you sure you want to delete this team?</div>";
					$html .= '<div><a class="button-link" href="/gbSuite/apps/process_application.php?app=team_builder&uid='.$this->user->getUID().'&action=delete&team_id='.$params['team_id'].'&redirect=team_builder" >Yes</a>&nbsp;<a class="button-link" href="/gbSuite/home.php?app=team_builder">Go back</a></div>';
				}
			
			$this->html = $html;
			
		}
		
		/**
		 * @return 
		 * @param $params Object
		 */
		public function save($params)
		{		
			$query = "INSERT INTO team (id, team, dealer_view_id) ". 
						 "VALUES (0, '".$params['team']."', ".$params['dealer_view_id'].")";			

			$this->connection->exec_query($query);
		}
		
		protected function createTeamBuilderList($rs, $title, $listId, $listItemId, $listClassName, $listItemClassName)
		{
			$html = "";

			if($rs !== false)	
			{
				$html = "";
				$count = 0;
				
				//associates-list-x
				$html = '<ul id="'.$listId.'" class="'.$listClassName.'" name="'.$listClassName.'">';
				
				while($row = mysql_fetch_assoc($rs))
				{
					//1240077
					$html .= '<li id="'.$row['uid'].'_mylistitem" class="'.$listItemClassName.'"><div style="width:100%">'.$row['name'].'</div></li>';	
				}
			}				
			$html .= "</ul>";
			
			return $html;
		}
		
		
		private function getAvailableAssociates()
		{
			//Users that not been in a team
			$query = "SELECT I.name, D.label, I.uid, T.team, D.id, T.dealer_view_id
						FROM info I JOIN dealer_view D LEFT JOIN (SELECT T.id, T.dealer_view_id, TM.uid, T.team
							FROM team T INNER JOIN team_member TM 
							ON T.id = TM.team_id 
							WHERE T.dealer_view_id = $this->dealerViewId) T
						ON D.id = T.dealer_view_id AND I.uid = T.uid
						WHERE T.team IS NULL AND D.id = $this->dealerViewId AND I.department LIKE '%Sales%' AND I.title IN ('Salesperson', 'Sales Manager') AND employee_id IS NOT NULL 
						ORDER BY name ASC"; 
 			
 			return $this->connection->exec_query($query);
		}
		
		protected function availableAssociates()
		{	
			$rs = $this->getAvailableAssociates();
			
			//associates-list-1" class="associates-list
			return $this->createTeamBuilderList($rs, "Available Associates", 'team-builder-available', null, "team-builder-list", "team-builder-available-list-item");						
		}
		
		protected function getTeamMembers($team)
		{
			$query = "SELECT I.uid, name 
						FROM info I INNER JOIN team_member TM
						ON I.uid = TM.uid INNER JOIN team T
						ON TM.team_id = T.id
						WHERE I.department LIKE '%Sales%' AND TM.team_id = $team AND T.dealer_view_id = $this->dealerViewId ORDER BY name ASC";
						
			return $this->connection->exec_query($query);
		}
		
		protected function members($team)
		{
			$rs = $this->getTeamMembers($team);
			
			//associates-list-1" class="associates-list
			return $this->createTeamBuilderList($rs, "Members", 'team-builder-member', null, "team-builder-list", "team-builder-member-list-item");						
		}
	}	
?>
