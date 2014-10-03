<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class LeadControl extends Application
	{
		private $option = null;
		private $sort = null;
		private $order = null;
		private $from = null;
		private $to = null;		
		private $params = array();
		private $printing = false;
		
		public function __construct()
		{
			$this->appId = 26;	
		}
		
		public function print_report($params)
		{
			?>
				<link rel="stylesheet" href="/css/report_print.css" type="text/css" media="screen, print" charset="utf-8" />				
			<?
			
			$this->printing = true;
				
			$this->renderHTML();
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
						FROM info I JOIN dealer_view D LEFT JOIN lead_control L
							ON I.uid = L.uid AND D.id = L.dealer_view_id 
						WHERE L.id IS NULL AND I.title = 'Salesperson' AND I.employee_id IS NOT NULL AND I.active = 1 AND D.id = ".$dealerView['id'];

			$rs = $this->connection->exec_query($query);
			
			if($rs !== false)
			{
				while($row = mysql_fetch_assoc(($rs)))
				{
					$query = "INSERT INTO lead_control (id, uid, dealer_view_id, showroom, iphone, ileads) VALUES (0, '".$row['uid']."', ".$dealerView['id'].", 0, 0, 0)";
							
					$this->connection->exec_query($query);
				}					
			}	
			
			$html .= "<div class='app-content'>";
			$html .= "<div class='report-title'>Lead Control<br/>$dateRange</div>";
		
			$selectionBar = 
				'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=lead_control">
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
				
				$link = "/gbSuite/apps/process_application.php?app=lead_control&action=print_report&uid=".$this->user->getUID();
				
				$link .= "&sort=$this->sort&order=$this->order";
					
				if(isset($this->params['option']))
					$link .= '&option='.$this->params['option'];
				
				$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
				
				$html .= '<div class="report-tool-bar" style="text-align:right;"><img src="/images/resources/printButton.png" onclick=popUp("'.$link.'"); /></div>';
			}
			
			//$html .= '<div class="lead-control-container">' .
			//		'<form method="post" action=/gbSuite/apps/process_application.php?app=lead_control&action=save&uid='.$this->user->getUID().'&redirect=lead_control>';
			$html .= '<div class="lead-control-container">' .
					'<form method="post" action=/gbSuite/home.php?app=lead_control&action=save&uid='.$this->user->getUID().'>';
			
			$html .= '<table class="lead-control-table" cellpadding=1>';				
			$html .= 	'<th>Name</th>
						<th>Showroom</th>
						<th>iPhone</th>
						<th>iLeads</th>';
			
			$count = 0;			
		
			$query = "SELECT L.id, I.name, I.uid, L.iphone, L.ileads, L.showroom  
						FROM info I INNER JOIN lead_control L
						ON I.uid = L.uid $where AND I.active = 1 ORDER BY I.name ";
			
			$rs = $this->connection->exec_query($query);
			
			if($rs !== false)
			{
				 	
				while($row = mysql_fetch_assoc($rs))
				{
					$html .= '<tr>';
					$html .= '<td width=10%><input type="hidden" name="lead_control_id'.$count.'" value="'.$row['id'].'" />'.str_replace(" ", "&nbsp;", $row['name']).'</td>';
					$html .= '<td align=center width=10%><input class="lead-control-input" type="text" name="lead_control_showroom'.$count.'" value="'.$row['showroom'].'" /></td>';
					$html .= '<td align=center width=10%><input class="lead-control-input" type="text" name="lead_control_iphone'.$count.'" value="'.$row['iphone'].'" /></td>';
					$html .= '<td align=center width=10%><input class="lead-control-input" type="text" name="lead_control_ileads'.$count.'" value="'.$row['ileads'].'" /></td>';
					$html .= '</tr>';					
					
					++$count;
				}		
				
				$html .= '</table>';
				
				$html .= '<input id=desklog-option'.$this->appId.' type=hidden name=option value="'.$this->option.'" />
						  <input type=hidden name="from'.$this->appId.'" value="'.$this->from.'"/>
						  <input type=hidden name="to'.$this->appId.'" value="'.$this->to.'"/>';
																  
				$html .= '<input type="hidden" name="lead_control_row_count" value="'.$count.'" />';
				
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
			$iphone = 0;
			$soldIP = 0;
			$showroom = 0;
			$soldSH = 0;
			$ilead = 0;
			$soldIL = 0;
			$prefix = "lead_control_";
			
			$rowCount = $params[$prefix.'row_count'];
			$query = "";
			
			for($i = 0; $i < $rowCount; $i++)
			{
				$id = $params[$prefix.'id'.$i];
				
				$iphone = $params[$prefix.'iphone'.$i];

				$showroom = $params[$prefix.'showroom'.$i];

				$ilead = $params[$prefix.'ileads'.$i];

				$iphone = ($iphone != "" ? (is_numeric($iphone) ? $iphone : 0) : 0);
				
				$showroom = ($showroom != ""? (is_numeric($showroom) ? $showroom : 0) : 0);
				
				$ilead = ($ilead != ""? (is_numeric($ilead) ? $ilead : 0) : 0);
				
				$query = "UPDATE lead_control SET showroom = $showroom, iphone = $iphone, ileads = $ilead WHERE id = $id";
				
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
