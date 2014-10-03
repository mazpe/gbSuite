<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/gbSuiteConfig.php';
	
    class Inbox extends Application
	{
		public function __construct()
		{
			$this->appId = 19;
		}
		
		public function view_email_message($params)
		{
			$query = "SELECT message FROM inbox WHERE id = ".$params['email_id'];
			
			$message = $this->connection->get_value($query);
			
			echo '{"message":"'.$message.'"}';
		}
		
		private function view($params)
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
				
			$count = $this->connection->get_value("SELECT COUNT(id) FROM inbox WHERE recipient = '".$this->user->getUID()."'");
			
			if($page > 5)
			{
				$firstPage = $page - 5;
				$lastPage = $page + 5;
			}
			else
			{
				$lastPage = 10;
			}
						
			$query = "SELECT I.id, I.sender, I.recipient, I.title, I.message, date_format(I.created, '%m/%d/%Y') AS created, I.`read`
								FROM inbox I WHERE I.recipient = '".$this->user->getUID()."' ORDER BY I.created LIMIT ".$start." , 10 ";
			
			$rs = $this->connection->exec_query($query);
			
			$html .= '<div class="inbox">
			    		<div class="tool-bar">
			            	<table width="100%" cellpadding="0" cellspacing="0" class="options">
			                	<tr>
									<td>
			                        	<li><div class="delete">&nbsp;</div>Delete</li>
			                        	<li><div class="reply">&nbsp;</div>Reply</li>
			                        	<li><div class="forward">&nbsp;</div>forward</li>
			                        	<li><div class="print">&nbsp;</div>Print</li>
			                    	</td>
			                	</tr>
			            	</table>
			  			</div>
			        	<div class="inbox-element">
			        		<ul>
				            	<li>Email Elements</li>
				            	<li>inbox</li>
				         	</ul>
				        </div>
				    	<div class="list-inbox">
				    		<table class="table-list-inbox" cellpadding="0" cellspacing="0">
			           			<tr>
				                   	<th class="check-delete"><input name="check" type="checkbox" value=""></input></th>
				                   	<th><div class="order-up">&nbsp;</div>From</th>
				                   	<th><div class="order-up">&nbsp;</div>Subject</th>
				                   	<th><div class="order-up">&nbsp;</div>Date</th>
				                   	<th class="attach"><img src="/gbSuite/apps/inbox/images/attach.gif"/></th>
			                	</tr>';

			if($rs !== false)
			{
				while($row = mysql_fetch_assoc($rs))
				{
					$html .= '<tr>';
					$html .= '<td class="check-email"><input name="check" type="checkbox" value=""></td>';
					$html .= '<td>'.$row['sender'].'</td>';
					//$html .= '<td><a href="/gbSuite/home.php?app=inbox&action=detail&id='.$row['id'].'">'.$row['title'].'</a></td>';
					//$html .= '<td><a href="#" onclick="viewEmailMessage('.$row['id'].')">'.$row['title'].'</a></td>';
					$html .= '<td><a href="#">'.$row['title'].'</a></td>';
					$html .= '<td>'.$row['created'].'</td>';
					//$html .= '<td><a href="/gbSuite/apps/process_application.php?app=notification&action=delete&uid='.$this->user->getUID().'&id='.$row['id'].'&redirect=notification">Delete</a></td>';
					$html .= '</tr>';
				}		
				
				$html .= '</table>';
				$html .= '</div>';
				
				for($i = $firstPage; ($i * 10 <= $count && $i < $lastPage); $i++)
				{
					if($i != $page)
						$pages .= '<div class="page-number"><a id="page1" href="/gbSuite/home.php?app=inbox&action=view&uid='.$this->user->getUID().'&page='.($i + 1).'"> '.($i + 1).' </a></div >';
					else
						$pages .= '<div class="page-number"><a class="selected-page" id="page1" href="/gbSuite/home.php?app=inbox&action=view&uid='.$this->user->getUID().'&page='.($i + 1).'"> '.($i + 1).' </a></div >';				
				}
									 
				for($j = 0; $j * 10 <= $count; $j++)
			
				if($firstPage > 1)
					$previous = $firstPage - 1;
				else
					$previous = $firstPage;
				
				if($lastPage < $j)
					$next = $lastPage + 1;
				else
					$next = $j;							
				
				/*$html .= 	'<table class="bar-selector" cellpadding=0 cellspacing=0>		
								<tr>
									<td align=center >
										<table align=center cellpadding=0 cellspacing=0>
											<tr>
												<td><input class="button-link" name="back" type="button" value="<<" onclick=document.setLocation("/gbSuite/home.php?app=inbox&action=view&uid='.$this->user->getUID().'&page=1") /></td>
												<td><input class="button-link" name="start" type="button" value="<" onclick=document.setLocation("/gbSuite/home.php?app=inbox&action=view&uid='.$this->user->getUID().'&page='.$previous.'") /></td>';
												
												$html .= '<td>'.$pages.'</td>';
												$html .= '<td><input class="button-link" name="next" type="button" value=">" onclick=document.setLocation("/gbSuite/home.php?app=inbox&action=view&uid='.$this->user->getUID().'&page='.$next.'") /></td>
														  <td><input class="button-link" name="finish" type="button" value=">>" onclick=document.setLocation("/gbSuite/home.php?app=inbox&action=view&uid='.$this->user->getUID().'&page='.($j).'") /></td>
											</tr>
										</table>
									</td>
								</tr>
							</table>';*/							
				$html .= '</div>';
				//$html .= '<div><textarea style="width:100%;" id="email-message" name="message"></textarea></div>';				
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
	}
?>
