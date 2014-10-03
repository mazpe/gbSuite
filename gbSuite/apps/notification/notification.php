<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class Notification extends Application
	{
		public function __construct()
		{
			$this->appId = 27;	
		}
		
		public function compose()
		{
			$html = "";
			
			$html = '<form id="alert-notification-form" action="/gbSuite/apps/process_application.php?app=notification&action=save&uid='.$this->user->getUID().'&redirect=notification" method="post">
						<table width="100%" class="notification-compose-table">
							<input type="hidden" name="app_id" value="'.$this->appId.'"/>						
							<tr>
				  				<td><label class="label-global">Subject:</label></td><td>
									<input id="notification-title" class="subject-text" type="text" name="title" />
									<div id="invalid-notification-title"></div>
								</td>
							</tr>	
							<tr>
								<td><label class="label-global">Message:</label></td>									
								<td>	
									<textarea id="notification-message" name="message"></textarea>		
									<div id="invalid-notification-message"></div>							
								</td>
							</tr>
							<tr>
								<td colspan=2>';
			
			$html .= 	'<table width=100%;>
							<tr>
								<td width=50% valign="top">									
									<label><input name="notification-to" type="radio" value="everyone" onclick=changeRecipientType("everyone") checked=true/>Everyone</label><br/>
									<label><input name="notification-to" type="radio" value="department" onclick=changeRecipientType("department") />Everyone in department</label><br/>
									<label><input name="notification-to" type="radio" value="title" onclick=changeRecipientType("title") />Title</label><br/>
									<!--label><input name="notification-to" type="radio" onclick=changeRecipientType("associates") />Some associates</label-->
								</td>
								<td width=50% valign="top">';
			$html .=				'<div id="associates-notification-department" style="display:none;">
										<input type="checkbox" name="notification_department_sales" text="Sales" /><label>Sales</label><br/>
										<input type="checkbox" name="notification_department_parts" text="Parts" unchecked/><label>Parts</label><br/>
										<input type="checkbox" name="notification_department_services" text="Services" unchecked/><label>Service</label><br/>
										<input type="checkbox" name="notification_department_finance" text="Finance" unchecked/ ><label>Finance</label><br/>
										<input type="checkbox" name="notification_department_accounting" text="Accounting" unchecked/ ><label>Accounting</label><br/>
										<input type="checkbox" name="notification_department_collision_center" unchecked text="Collision Center"/><label>Collision Center</label><br/>
									 </div>';

			$html .=				'<div id="associates-notification-title" style="display:none;">
										<input type="checkbox" name="notification_title_salesperson" text="Sales Person" /><label>Sales Person</label><br/>
										<input type="checkbox" name="notification_title_generalmanager" text="General Manager" unchecked/><label>General Manager</label><br/>
										<input type="checkbox" name="notification_title_generalsalesmanager" text="General Sales Manager" unchecked/><label>General Sales Manager</label><br/>										
										<input type="checkbox" name="notification_title_salesmanager" text="Sales Manager" unchecked/><label>Sales Manager</label><br/>
										<input type="checkbox" name="notification_title_administrator" text="Administrator" unchecked/><label>Administrator</label><br/>										
									 </div>';
									
			$html .= 				'<div id="associates-notification-table" style="display:none;" class="notification-associates" border=3px cellpadding=10px cellmargin=0 cellspacing=0>'
										.$this->getAssociatesList().											
							 		'</div>';
								
			$html .= 			'</td>
							</tr>
						</table>';
						
			$html .= 	'</td></tr>';
								
			$html .=	'<tr>
								<td colspan=2><input class="button-link" type="button" value="Submit" onclick="submitNotification()" />
								<input class="button-link" type="button" value="Go back" onclick=document.setLocation("/gbSuite/home.php?app=notification") /></td>	
							</tr>
						</table>';
									
			$html .=	'</form>';			
			
						
			$this->html = $html;
			
			$this->printScript();
		}

		public function read($params)
		{
			$query = "UPDATE notification SET `read` = 1 WHERE id = ".$params['id']." AND recepient = '".$params['uid']."'";
			
			$this->connection->exec_query($query);
		}
		
		public function detail($params)
		{			
			if(isset($params['id']) && $params['id'] != "")
			{
				$query = "SELECT id, sender, recepient, title, message, created, type, `read` 
						  FROM notification
						  WHERE `read` = 0 AND id = ".$params['id'];
				
				//echo $query;
						  
				$row = $this->connection->get_row($query);
				
				if($row != null)
				{
					$this->connection->exec_query($query);
					
					$html = "";
					$html .= '<div class="notifications-message">
								<input type="hidden" name="notification-id" id="notification-id" value="'.$row['id'].'" />
								<div class="notifications-title-item">'.$row['title'].'</div>
								<div class="notifications-message-content">
									<label>'.$row['message'].'</label>
								</div>
								<div class="notifications-footer">
									<input class="button-link" name="readed" type="button" value="Mark as Read" onclick=document.setLocation("/gbSuite/apps/process_application.php?app=notification&action=read&id='.$row['id'].'&uid='.$this->user->getUID().'&redirect=notification")>
									<input class="button-link" type="button" value="Go back" onclick=document.setLocation("/gbSuite/home.php?app=notification")>
								</div>	
							</div>';
							
					
					$this->html = $html;	
				}	
			}
		}	
			
		public function save($params)
		{
			$select = "";
			
			if($params['notification-to'] == 'everyone')
				$select = "SELECT 0, '".$this->user->getUID()."', uid, '".$params['title']."', '".$params['message']."', now(), 'information', 0
					  		FROM info";
			else
				if($params['notification-to'] == 'department')
				{
					$deparment = "";
					
					if(isset($_POST['notification_department_sales'])) //Sales
						$department .= "Sales";
					
					if(isset($_POST['notification_department_parts']))	//Parts
						$department .= (($department != "") ? "/" : "")."Parts";
						
					if(isset($_POST['notification_department_services']))	//Services
						$department .= (($department != "") ? "/" : "")."Services";
						
					if(isset($_POST['notification_department_accounting']))	//Accounting
						$department .= (($department != "") ? "/" : "")."Accounting";
								
					if(isset($_POST['notification_department_finance']))	//Finance
						$department .= (($department != "") ? "/" : "")."Finance";
						
					if(isset($_POST['notification_department_collision_center']))	//Collision Center
						$department .= (($department != "") ? "/" : "")."Collision Center";
	 
	 				$where = (strpos($department, "Sales") === false ? "" : "department LIKE '%Sales%'");
					$where .= (strpos($department, "Parts") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Parts%'");
					$where .= (strpos($department, "Finance") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Finance%'");
					$where .= (strpos($department, "Collision Center") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Collision Center%'");
					$where .= (strpos($department, "Services") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Services%'");
					$where .= (strpos($department, "Accounting") === false ? "" : ($where == "" ? "" : " OR ")."department LIKE '%Accounting%'");
					 
					$select = "SELECT 0, '".$this->user->getUID()."', uid, '".$params['title']."', '".$params['message']."', now(), 'information', 0
							   FROM info
							   WHERE $where";					
				}					
				else
					if($params['notification-to'] == 'title')
					{	
						$title = "";
					
						if(isset($_POST['notification_title_salesperson'])) //Salesperson
							$title .= "Salesperson";
						
						if(isset($_POST['notification_title_generalmanager']))	//Sales Manager
							$title .= (($title != "") ? "/" : "")."General Manager";
						
						if(isset($_POST['notification_title_generalsalesmanager']))	//Sales Manager
							$title .= (($title != "") ? "/" : "")."General Sales Manager";
							
						if(isset($_POST['notification_title_salesmanager']))	//Sales Manager
							$title .= (($title != "") ? "/" : "")."Sales Manager";	
							
						if(isset($_POST['notification_title_administrator']))	//Administrator
							$title .= (($title != "") ? "/" : "")."Administrator";
		
		 				$where = (strpos($title, "Salesperson") === false ? "" : "title = 'Salesperson'");
						$where .= (strpos($title, "General Manager") === false ? "" : ($where == "" ? "" : " OR ")."title = 'General Manager'");
						$where .= (strpos($title, "General Sales Manager") === false ? "" : ($where == "" ? "" : " OR ")."title = 'General Sales Manager'");
						$where .= (strpos($title, "Sales Manager") === false ? "" : ($where == "" ? "" : " OR ")."title = 'Sales Manager'");
						$where .= (strpos($title, "Administrator") === false ? "" : ($where == "" ? "" : " OR ")."title = 'Administrator'");
						 
						$select = "SELECT 0, '".$this->user->getUID()."', uid, '".$params['title']."', '".$params['message']."', now(), 'information', 0
								   FROM info
								   WHERE $where";
					}
							   
			//$query = "INSERT INTO notification (id, sender, recepient, title, message, created, `type`, `read`) VALUES (0, '".$this->user->getUID()."', '', '".$params['title']."', '".$params['message']."', now(), 'information', 0)";
			$query = "INSERT INTO notification (id, sender, recepient, title, message, created, `type`, `read`)".
					  $select;
					  	
			$this->connection->exec_query($query);
		}

		public function delete($params)
		{
			$query = "DELETE FROM notification WHERE id = ".$params['id'];
			
			$this->connection->exec_query($query);			
		}		
		
		public function view($params)
		{
			$html = "";
			
			$page = 1;
			$start = 0;
			$finish = 0;
			$previous = 0;
			$next = 0;
			$pages = "";
			$firstPage = 0;
			$lastPage = 0;
						
			if(isset($params['page']) && $params['page'] != "")
				$page = $params['page'];
			
			$start = ($page - 1) * 10;
			
			
			  
				
			$count = $this->connection->get_value("SELECT COUNT(id) FROM notification WHERE `read` = 0 AND recepient = '".$this->user->getCurrentProfileUID()."'");
			//$count = $this->connection->get_value("SELECT COUNT(id) FROM notification");
			
			if($page > 5)
			{
				$firstPage = $page - 5;
				$lastPage = $page + 5;
			}
			else
			{
				$lastPage = 10;
			}
						
			$query = "SELECT id, sender, recepient, title, message, date_format(created, '%m/%d/%Y') AS created, type, `read`
								FROM notification WHERE `read` = 0 AND recepient = '".$this->user->getUID()."'ORDER BY created LIMIT ".$start." , 10 ";
			
			/*$query = "SELECT id, sender, recepient, title, message, date_format(created, '%m/%d/%Y') AS created, type, `read`
								FROM notification ORDER BY id LIMIT ".$start." , 10 ";*/
								
			$rs = $this->connection->exec_query($query);
			
			if($rs !== false)
			{
				//$count = mysql_num_rows($rs);
				
				$html .= '<div class="notifications">';
				//$html .= '<div class="application-title">Notifications</div>';				
							
				$html .= '<table class="notifications-table" cellpadding=0 cellspacing=0>';
				$html .= 	'<tr>
								<td>&nbsp</td>
								<td class="notifications-header">
									<div class="notifications-list">Notifications</div><div class="notifications-view-all"><a href="/gbSuite/home.php?app=notification&action=view_all">view all&nbsp;('.$count.')</a></div>
								</td>
								<td class="notifications-header">Date</td>			
								<td class="notifications-header"></td>
							</tr>';								
					 	
				while($row = mysql_fetch_assoc($rs))
				{
					$html .= '<tr>';
					$html .= '<td><img src="/images/resources/'.$row['type'].'.gif"/></td>';
					$html .= '<td><a href="/gbSuite/home.php?app=notification&action=detail&id='.$row['id'].'">'.$row['title'].'</a></td>';
					$html .= '<td>'.$row['created'].'</td>';
					//$html .= '<td><a href="/gbSuite/apps/process_application.php?app=notification&action=delete&uid='.$this->user->getUID().'&id='.$row['id'].'&redirect=notification">Delete</a></td>';
					$html .= '</tr>';
				}		
				
				for($i = $firstPage; ($i * 10 <= $count && $i < $lastPage); $i++)
				{
					if($i != $page)
						$pages .= '<td><div class="page-number"><a id="page1" href="/gbSuite/home.php?app=notification&action=view&uid='.$this->user->getUID().'&page='.($i + 1).'"> '.($i + 1).' </a></div ></td>';
					else
						$pages .= '<td><div class="page-number"><a class="selected-page" id="page1" href="/gbSuite/home.php?app=notification&action=view&uid='.$this->user->getUID().'&page='.($i + 1).'"> '.($i + 1).' </a></div ></td>';				
				}
									 
				for($j = 0; $j * 10 <= $count; $j++)
					//$pages .= '<td><div class="page-number"><a id="page1" href="/gbSuite/home.php?app=notification&action=view&uid='.$this->user->getUID().'&page='.($i + 1).'"> '.($i + 1).' </a></div ></td>';
			
				if($firstPage > 1)
					$previous = $firstPage - 1;
				else
					$previous = $firstPage;
				
				if($lastPage < $j)
					$next = $lastPage + 1;
				else
					$next = $j;							
					
				/*if($page > 1)
					$previous = $page - 1;
				else
					$previous = $page;
				
				if($page < $i)
					$next = $page + 1;
				else
					$next = $i;*/							
								
				$html .= '</table>';
				
				$html .= 	'<table class="bar-selector" cellpadding=0 cellspacing=0>		
								<tr><td align=left style="width: 0"><input class="button-link" type="button" value="Compose" onclick=document.setLocation("/gbSuite/home.php?app=notification&action=compose") /></td>
									<td align=center >
										<table align=center cellpadding=0 cellspacing=0>
											<tr>
												<td><input class="button-link" name="back" type="button" value="<<" onclick=document.setLocation("/gbSuite/home.php?app=notification&action=view&uid='.$this->user->getUID().'&page=1") /></td>
												<td><input class="button-link" name="start" type="button" value="<" onclick=document.setLocation("/gbSuite/home.php?app=notification&action=view&uid='.$this->user->getUID().'&page='.$previous.'") /></td>';
																																				
												$html .= $pages;
												
												$html .= '<td><input class="button-link" name="next" type="button" value=">" onclick=document.setLocation("/gbSuite/home.php?app=notification&action=view&uid='.$this->user->getUID().'&page='.$next.'") /></td>
														  <td><input class="button-link" name="finish" type="button" value=">>" onclick=document.setLocation("/gbSuite/home.php?app=notification&action=view&uid='.$this->user->getUID().'&page='.($j).'") /></td>
											</tr>
										</table>
									</td>
								</tr>
							</table>';							
				$html .= '</div>';
			}
			
			$this->html = $html;
		}
		
		public function view_all($params)
		{
			$html = "";
						
			$count = $this->connection->get_value("SELECT COUNT(id) FROM notification WHERE `read` = 0 AND recepient = '".$this->user->getUID()."'");
						
			$query = "SELECT id, sender, recepient, title, message, date_format(created, '%m/%d/%Y') AS created, type, `read`
								FROM notification WHERE `read` = 0 AND recepient = '".$this->user->getUID()."'ORDER BY created ";
			
			$rs = $this->connection->exec_query($query);
			
			if($rs !== false)
			{
				//$count = mysql_num_rows($rs);
				
				$html .= '<div class="notifications">';
				//$html .= '<div class="application-title">Notifications</div>';				
				$html .= '<input class="button-link" type="button" value="Compose" onclick=document.setLocation("/gbSuite/home.php?app=notification&action=compose") />';			
				$html .= '<table class="notifications-table" cellpadding=0 cellspacing=0>';
				$html .= 	'<tr>
								<td>&nbsp</td>
								<td class="notifications-header">
									<div class="notifications-list">Notifications</div><div class="notifications-view-all"><a href="/gbSuite/home.php?app=notification&action=view">Back</a></div>
								</td>
								<td class="notifications-header">Date</td>			
								<td class="notifications-header"></td>
							</tr>';								
					 	
				while($row = mysql_fetch_assoc($rs))
				{
					$html .= '<tr>';
					$html .= '<td><img src="/images/resources/'.$row['type'].'.gif"/></td>';
					$html .= '<td><a href="/gbSuite/home.php?app=notification&action=detail&id='.$row['id'].'">'.$row['title'].'</a></td>';
					$html .= '<td>'.$row['created'].'</td>';
					//$html .= '<td><a href="/gbSuite/apps/process_application.php?app=notification&action=delete&uid='.$this->user->getUID().'&id='.$row['id'].'&redirect=notification">Delete</a></td>';
					$html .= '</tr>';
				}		
				$html .= '</table>';
				$html .= '</div>';
			}
			
			$this->html = $html;
		}

		public function renderHTML()
		{
			if($this->html != "")
				echo $this->html;	
			else
			{
				$this->view(null);
				echo $this->html;  
			}
		}					
					
		protected function getAssociatesList()
		{
			$html = "";
			
			$query = "SELECT I.uid, name
						FROM info I INNER JOIN friend F
						ON I.uid = F.user2 WHERE user1 = '".$this->user->getUID()."'";
						
			$rs = $this->connection->exec_query($query);
			
			$html = "";
			$count = 0;
			
			//associates-list-x
			$html = '<select MULTIPLE id="associates-list" name="associates-list">';
			
			while($row = mysql_fetch_assoc($rs))
			{
				//1240077
				$html .= '<option value="'.$row['user2'].'" name="'.$row['user2'].'_mylistitem">'.$row['name'].'</li>';	
			}
			
			$html .= "</select>";
			
			return $html;
		}
	
		public function printScript()
		{
			?>	
			<script>	
				function changeRecipientType(type)
				{		
					var associatesTable = document.getElementById('associates-notification-table');
					var associatesDepartment = document.getElementById('associates-notification-department');
					var associatesTitle = document.getElementById('associates-notification-title');
					
					associatesTable.setStyle({display:(type == 'associates' ? 'block':'none')});
					associatesDepartment.setStyle({display:(type == 'department' ? 'block':'none')});
					associatesTitle.setStyle({display:(type == 'title' ? 'block':'none')});
				}	
				
				function submitNotification()
				{
					var title, message, error = false;
					
					title = document.getElementById('notification-title').getValue();
					message = document.getElementById('notification-message').getValue();
					
					if(title == '')
					{
						error = true;
						document.getElementById('invalid-notification-title').setTextValue('Please enter the subject.');
					}
					else
						document.getElementById('invalid-notification-title').setTextValue('');
					
					if(message == '')
					{
						error = true;
						document.getElementById('invalid-notification-message').setTextValue("Please enter the message.");
					}
					else
						document.getElementById('invalid-notification-message').setTextValue("");
					
					if(error == false)
						document.getElementById('alert-notification-form').submit();
				}	
			</script>
		<?}
	}?>