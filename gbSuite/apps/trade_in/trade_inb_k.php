<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/user.php");
	
    class TradeIn extends Application
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
		private $params = array();
		private $dealerViewId = 0;
		
		private $dateCondition = "";		
				
		public function __construct()
		{
			$this->appId = 50;
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
			
			$this->sort = "dl_date";
			$this->order = "DESC";
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
				
				$title = "";
				
				$title = "Trade In Log";
					
				$html .= '<div class="app-report">';
				$html .= "<div style='text-align:center'>";
				$html .= "<div class=report-title>$title<br/>$dateRange</div>";
				$html .= "</div>";
			
				$selectionBar = 
								'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=trade_in">
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
					
					$link = "/gbSuite/apps/process_application.php?app=trade_in&action=print_report&uid=".$this->user->getUID();
					
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
			$order = "";
			
			if($this->order == "ASC")
				$order = "DESC";
			else
				if($this->order == "DESC")
					$order = "ASC";
							
			$link = '/gbSuite/home.php?app=trade_in&order='.$order;
			
			if(isset($this->params['option']))
				$link .= '&option='.$this->params['option'];
				
			$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
			/*
			$header = array('Date Traded','Year','Make','Model','Mileage','ACV','Manger');
			$fields = array('dl_date','year','make','model','mileage','acv','manager');
			
			
			$html.="<table class='trade-in-report' cellpadding=0 cellspacing=0>
			    <tr>";
			for($i = 0; $i < sizeof($header); $i++)
				$html .= "<th class='trade-header-title'><a href='".$link."&sort=".str_replace("`", "", $fields[$i])."'>".$header[$i]."</a></th>"; 
			$html .= "</tr>";*/
									
			$html .= "<table class='trade-in-report' cellpadding=0 cellspacing=0>
			    <tr>
			    	<td class='trade-header-title'>
			        	<a href='$link&sort=dl_date'>Date Traded
			        </td>
			        <td class='trade-header-title'>
			        	<a href='$link&sort=trade_deal_no'>Deal #
			        </td>
			        <td class='trade-header-title'>
			        	<a href='$link&sort=stock_no'>Stock #
			        </td>
			        <td class='trade-header-title'>
			        	<a href='$link&sort=year'>Year
			        </td>
			        <td class='trade-header-title'>
			        	<a href='$link&sort=make'>Make
			        </td>
			        <td class='trade-header-title'>
			        	<a href='$link&sort=model'>Model
			        </td>
			        <td class='trade-header-title'>
			        	<a href='$link&sort=miliage'>Mileage
			        </td>
			        <td class='trade-header-title'>
			        	<a href='$link&sort=acv'>ACV
			        </td>			        
			    </tr>";
				    
			    $time = time(); 
				$tableName = "trade_in_$time";
								
				$query = "(select 
						   dl_date, stock_no, trade1, sm, trade2, trade3,deal_no,
						   trade1_year, trade1_make, trade1_model, trade1_miliage, trade1_acv, trade1_deal_no, 
						   trade2_year, trade2_make, trade2_model, trade2_miliage, trade2_acv, trade2_deal_no, 
						   trade3_year, trade3_make, trade3_model, trade3_miliage, trade3_acv, trade3_deal_no, 
						   I.initials
						   from app_rpt_desk_log inner join info I on app_rpt_desk_log.sm = I.employee_id  
						   where dl_date between '{$this->dateFrom}'  and  '{$this->dateTo}'  and 
									    	   		(trade1 = 1 or trade2 = 1 or trade3 = 1))";	

				$results = $this->connection->exec_query($query);
				
			    $cont = 0;
			    
			    $sql =  "create table $tableName(dl_date date, stock_no VARCHAR(30), trade_deal_no VARCHAR(20), year int(11), make varchar(20), model varchar(30), miliage float, acv float, initials varchar(2))";
			    $this->connection->exec_query($sql);
			    			    
			    while($row = mysql_fetch_array($results))
			    {
			    	$trades = array();
			  		
			  		for($i = 1 ; $i <=3 ; $i++)
			  		{
			  			if($row["trade$i"] == 1)
			  			{
			  				$trades[$i] = array('year'=>$row["trade". $i ."_year"], 
			  									'make'=>$row["trade". $i ."_make"],
			  									'trade_deal_no'=> $row["trade". $i ."_deal_no"],
			  									'model'=>$row["trade". $i ."_model"],
			  									'miliage'=>$row["trade". $i ."_miliage"],
			  									'acv'=>$row["trade". $i ."_acv"]
			  									);
			  			}
			  		}  	
			  					  								
			  		foreach($trades as $trade => $values)
			  		{
			  			$insert = "insert into $tableName values ('{$row['dl_date']}', '{$row['stock_no']}', '{$values['trade_deal_no']}', '{$values['year']}', '{$values['make']}', '{$values['model']}', '{$values['miliage']}', '{$values['acv']}', '{$row['initials']}')";
			  			
			  			$this->connection->exec_query($insert);			  			
			  		}		  		
			    } 
			
			$query2= "select date_format(dl_date,'%m/%d/%Y') as dl_date, stock_no, trade_deal_no, year, make, model, miliage, acv, initials from $tableName order by $this->sort $this->order";    
			$r = $this->connection->exec_query($query2);
			   
			while($row = mysql_fetch_array($r))
			  		{
			  			$cont++;
			  			if($cont % 2 == 0 )
			  				$class = ' bgcolor="#f2f2f2" ';
			  			else
			  				$class="";
			  				
			  			$html .= "<tr $class >
							    	<td class='trade-info-data' align=center>
							        	{$row['dl_date']}
							        </td> 
							        <td class='trade-info-data'align=center>
							        	{$row['trade_deal_no']}
							        </td>
							        <td class='trade-info-data' align=center>
							        	{$row['stock_no']}
							        </td>
							        <td class='trade-info-data' align=center>
							        	{$row['year']}
							        </td>
							        <td class='trade-model' align=left>
							        	{$row['make']}
							        </td>
							        <td class='trade-model'align=left>
							        	{$row['model']}
							        </td>
							        <td class='trade-info-data' align=right>
							        	$this->formatNumber($row['miliage'],'')
							        </td>
							        <td class='trade-info-data' align=right>
							        	$this->formatNumber($row['acv'],'$')
							        </td>							        							        
							    </tr>";
			  		}
			    
			$html .= "</table>";
			
			$sql = "drop table $tableName";
			$this->connection->exec_query($sql);
			
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
						else
							{
								$value = $format.number_format($value, 0, '.', ',');
							}
							
						
			return $value;	
		}
	}
?>
