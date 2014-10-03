<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/gbSuiteConfig.php';
	
    class TeamBuilder extends Application 
	{
		public function __construct()
		{
			$this->appId = 23;			
		}	
		
		private function renderTable()
		{
			$html = "";
			
			$html .= '<div class="team-builder">
						<div class="team-builder-bar">
							<label><a href="/gbSuite/home.php?app=team_builder&action=add">Add</a></label>
						</div>';

			$query = "SELECT id, team FROM team ORDER by team";
			
			$rs = $this->connection->exec_query($query);
			
			if($rs !== false )	
			{
				while($row = mysql_fetch_assoc($rs))
				{	
					$editLink = '/gbSuite/home.php?app=team_builder&action=edit&team_id='.$row['id'];
					
					$html .= '<div class="team">
								<div><label>'.$row['team'].'</label><label><a href="'.$editLink.'"><img src="/images/resources/vineta.gif"/>&nbsp;Edit</a></label>&nbsp;<label><a href="/gbSuite/home.php?app=team_builder&uid='.$this->user->getUID().'&action=delete&team_id='.$row['id'].'&confirm=true"><img src="/images/resources/vineta.gif"/>&nbsp; Delete</a></label></div>';
								
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
			$html = "";
			
			$html .= 	'<div class="team-builder-title">Add Team</div>';
			$html .=	'<form method=post action=/gbSuite/apps/process_application.php?app=team_builder&action=save&uid='.$this->user->getUID().'&redirect=team_builder>';
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
					 
			/*$html .= 	"<div>
							<!--a class='button-link' href='/gbSuite/home.php?app=team_builder'>Go Back</a-->
						</div>";
			*/		 
			$this->html = $html;		
		}
		
		public function update_team($params)
		{
			$members = explode(",", $params['uids']);
			
			//$query = "UPDATE team SET members = '".$params['uids']."' WHERE id = ".$params['team_id'];
			$query = "DELETE FROM team_member WHERE team_id = ".$params['team_id'];
			
			$this->connection->exec_query($query);
			
			for($i = 0; $i < count($members); $i++)
			{
				$query = "INSERT INTO team_member (id, team_id, uid) VALUES (0, ".$params['team_id'].", '".$members[$i]."')";
				
				$this->connection->exec_query($query);
			}
					
			echo '{"query":"'.$query.' type='.$type.'"}';
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
			}
			else
				if($params['confirm'] == 'true')
				{
					$html = "";
					$html .= "<div>Are you want to delete this team?</div>";
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
			$query = "INSERT INTO team (id, team) ". 
						 "VALUES (0, '".$params['team']."')";			
			
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
			$query = "SELECT I.uid, name
						FROM info I LEFT JOIN team_member TM
						ON I.uid = TM.uid LEFT JOIN team T
						ON TM.team_id = T.id 	
						WHERE I.department LIKE '%Sales%'
								AND team IS NULL
								AND I.title IN  ('Salesperson', 'Sales Manager') 
								AND employee_id IS NOT NULL
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
						WHERE I.department LIKE '%Sales%' AND TM.team_id = $team ORDER BY name ASC";
						
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
