<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class AssociatesOverview extends Application
	{
		public function __construct()
		{
			$this->appId = 2;	
		}
		
		public function renderHTML()
		{
			
			if($this->html <> "")
			{
				echo $this->html;
				return;
			}
		
			if($this->user->getCurrentUID() == $this->user->getUID())
			{
				$uid = $this->user->getCurrentUID();
			}
			else
			{
				$uid = $this->user->getCurrentUID();
				$view_all_link = "&uid=$uid";
			}
						
			$friends = $this->user->getAssociates();
			$friends = join(array_keys($friends),",");
						
			$sql = "SELECT uid,agency_id,name,rand() * 100 as ordering,title FROM info ";
			
			if(trim($friends) == "")
			{
				$where = " where 1=0";
			}
			else
			{
				$where = " where uid in (". $friends .") and email not like '%@gbsuite.com' order by ordering limit 6";
			}
					
			$sql .= $where;
						
			$data = $this->connection->exec_query($sql);
			$user_agency = array();
			
			while($row = mysql_fetch_assoc($data))
			{
				$user_agency[$row['uid']] = $row;	
			}
														
			$query = "select title,name from info where uid='".$uid."'";
			$user_info = $this->connection->get_row($query);				
					
			$asso = 0; 	
			
			$friends = array_keys($this->user->getAssociates());	
			
			if(count($friends) >= 1 && $friends[0] != "")
			{
				$asso = count($friends);	
			}
			?>
			 
			<table width=100% border=0 cellspacing=0 cellpadding=0 cellmargin=0>
			<tr> 
			<td >
			
				<fb:friend-section >
					<table border=0 cellspacing=0 cellmargin=0 cellpadding=0 width=100%> 
						<tr>
							<td ><div  class="friend-section-label"><?= $asso ?>&nbsp;Associates</div></td>
							<td><div  class="friend-section-label"><a href="/gbSuite/home.php?app=associates<?= $view_all_link ?>">View All</a></div></td>
						</tr>
					</table>
								 	
					<table align=left width=100%  border=0 cellspacing=0 cellpadding=0 cellmargin=0 class='friends-table'> 
					      	<?
								$cont = 0;
								if(count($user_agency) > 0)
								{
									foreach($user_agency as $user => $friend_info)
									{			
										if($cont == 0)
										{
											 echo "<tr>";
										}
										$cont++;
							
							
									if($this->user->getTitle()=='Salesperson' && $friend_info['title']!="Salesperson"){
							
							 
										echo "<td align=center valign=top width=50% ><div class=friend >
											<img [atts]=\"\" src=\"/images/user/".strtolower(str_replace(" ","",$friend_info['title'])).".gif\"  /><br>
											<label class=\"label\" style=\"font-size:10px;\" ><a href='#'>".$friend_info['name']."</a></label>
											</div></td>";
										?><?
										}else{
										?>										
											<td align=center valign=top width=50%>
							        			<fb:friend uid="<?= $user ?>" name="<?= $friend_info['name'] ?>" width="60px" height="60px" ></fb:friend>
							        		</td>
										<?	
										}
										if($cont == 2)
										{
											$cont = 0;
											echo "</tr>";
										}
									}
								}
								else
								{
										echo "<td>&nbsp;</td>";
								}
								
							?>
					      
					    </table>
					    
						
				</fb:friend-section>
			</td>
			</tr>
			</table>
			<?
		}
	}
?>
