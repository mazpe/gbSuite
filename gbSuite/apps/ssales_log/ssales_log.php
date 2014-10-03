<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class SSalesLog extends Application
	{
		var $fields;
		var $printHtml = false;
		private $printing = false;
		
		public function __construct()
		{
			$this->appId = 46;
			
			$this->fields = array(
				'deal_no'=>'Deal #',
				'stock_no'=>'Stock #',
				'customer'=>'Customer',	
				'mod_no'=>'Mod #',
				'nu'=>'N/U',
				'last7vin'=>'Vin',		
				'dl_date'=>'Date',				
				'notes' => 'N'
			);			
		}
		
		public function print_report()
		{
			?>
				<link rel="stylesheet" href="/css/report_print.css" type="text/css" media="screen, print" charset="utf-8" />				
				<link rel="stylesheet" href="/css/desk_log.css" type="text/css" media="screen, print" charset="utf-8" />
			<?
			
			$this->printing = true;
				
			$this->renderHTML();
		}
		
		public function renderHTML()
		{
			$sort = "dl_date DESC, id";
			$order = "DESC";
			$from = "";
			$to = "";
			$nu = "";
			$dateRange = "";
			$start = "";
			$end = "";
			
			$params = array();
			
			$params = array_merge($_GET, $_POST);
			
			if(isset($params['sort']))
				$sort = $params['sort'];
				
			if(isset($params['order']))
				$order = $params['order'];
				
			if($this->html != "")
				echo $this->html;
			else
			if($this->printHtml == false)
			{
				$html = "";
				
				$html .= "<div class='body-overlayed'>";
				
				$option = "";
				$title = "";
				
				$currentDate = $this->connection->get_value("SELECT date_format(now(), '%m/%d/%Y') AS currentDate");
				
				if(isset($params['option']))
				{
					$option = $params['option'];
			
					if($option == "View")
					{
						$from = $params['from'.$this->appId];
						$to = $params['to'.$this->appId];
						
						$dateRange = $from." - ".$to;
												
						$start = substr($from, 6, 4).substr($from, 0, 2).substr($from, 3, 2);
						$end = substr($to, 6, 4).substr($to, 0, 2).substr($to, 3, 2);
												
						$where = "WHERE dl_date >= '$start' AND dl_date <= '$end' ";
					}
					else //Verify if correspond to a dealer view
					{	
						$query = "SELECT date_format(`from`, '%Y%m%d') AS `start`, date_format(`to`, '%Y%m%d') AS `end`, date_format(`from`, '%m/%d/%Y')AS `from`, date_format(`to`, '%m/%d/%Y')AS `to` FROM dealer_view WHERE label = '$option'";
						$row = $this->connection->get_row($query);
						
						if($row != null)
						{
							$start = $row['start'];
							$end = $row['end'];
							
							$from = $row['from'];
							$to = $row['to'];
							
							$dateRange = "$from - $to"; 
							$where = "WHERE dl_date >= '$start' AND dl_date <= '$end' ";
						}	
					}	
				}
				else
				{	
					$day = date("d");			
					$where = " WHERE MONTH(dl_date) = MONTH(now()) AND DAY(dl_date) = ".$day;
					
					$date = date("m")."/".$day."/".date("Y");
					$dateRange = $date." - ".$date;
					
					$from = $date;
					$to = $date;
					
					$row = null;
					
					$query = "SELECT date_format(`from`, '%Y%m%d') AS `from`, date_format(`to`, '%Y%m%d') AS `to`, date_format(`from`, '%m/%d/%Y') AS `start`, date_format(`to`, '%m/%d/%Y') AS `end` FROM dealer_view WHERE now() BETWEEN `from` AND `to`";
					
					$dealerView = $this->connection->get_row($query);
					
					if($dealerView != null)
					{
						$start = $dealerView['from'];
						$end = $dealerView['to'];
					}	 
					else
					{
						$query = "SELECT DATEDIFF(DATE_ADD('2008".date("m")."01', INTERVAL 1 MONTH), '2008".date("m")."01') AS days";
						$days = $this->connection->get_value($query);
						
						$start = date("Y").date("m")."01".
						$end = date("Y").date("m").$days;					
					}			
				}
	
				$nu = "";
				
				if($nu == 'U' || $nu == 'N')
					$where .= " AND nu = '$nu'";
				
				//Filter title
				$filterTitle = "";
						
				if($nu == 'N')
					$filterTitle .= "New Vehicle Sales";
				else
					if($nu == 'U')
						$filterTitle .= "Used Vehicle Sales";
					else
				 		if($nu == 'B' || $nu == '')
							$filterTitle .= "New and Used Vehicle Sales";
											
				$html .= "<div class='report-title'>Status Sales Log - $filterTitle<br/>$dateRange</div>";
				
				$sql = "SELECT id, deal_no, stock_no, customer, nu, 
							(SELECT initials FROM info WHERE employee_id = app_rpt_desk_log.sp1 LIMIT 1) AS sp1,
							(SELECT initials FROM info WHERE employee_id = app_rpt_desk_log.sp2 LIMIT 1) AS sp2,
							(SELECT initials FROM info WHERE employee_id = app_rpt_desk_log.sm LIMIT 1) AS sm,
							(SELECT initials FROM info WHERE employee_id = app_rpt_desk_log.fi LIMIT 1) AS fi,							
							mod_no, last7vin, front, reserve, vsi, cc, gap, cl, aft, finance, total, model, trade1 + trade2 + trade3 AS trade1, trade1_acv, tg, 1perc, hb, back, date_format(dl_date, '%m/%d/%Y') AS dl_date,
							type, status, notes, other, onepercent, toyoguard, source  
						FROM app_rpt_desk_log 
						$where ORDER BY $sort $order";
				
				$resuts = $this->connection->exec_query($sql);
				
				$sql = 'select model from models order by model';
				$models = $this->connection->fillSelect($sql,'model','model', null, false);
				
				$sql = "select initials, employee_id, name from info WHERE (title = 'Salesperson' OR name = 'House') AND employee_id IS NOT NULL AND active = 1 ORDER BY initials";
				$sp1 = $this->connection->fillDesklogSelect($sql,'employee_id','name', 'initials', null, false);
				
				$sp2 = $this->connection->fillDesklogSelect($sql,'employee_id', 'name', 'initials', null, true);
				
				$sql = "select initials, employee_id, name from info WHERE (title = 'Sales Manager' OR title = 'General Manager' OR title = 'General Sales Manager') AND employee_id IS NOT NULL  AND active = 1 ORDER BY initials";
				$sm = $this->connection->fillDesklogSelect($sql,'employee_id','name', 'initials', null, false);
				
				$sql = "select initials, employee_id, name from info WHERE (title = 'Finance Manager' OR name = 'House') AND employee_id IS NOT NULL  AND active = 1 ORDER BY initials";
				
				$fi = $this->connection->fillDesklogSelect($sql,'employee_id', 'name', 'initials', null, false);
				
				?>
				<script>
					function sendForm()
					{
						var form = document.getElementById('desk-log');
						form.submit();
					}
				</script>
				<?
			
				$sql = "SELECT SUM(total) AS total FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND status = 'B'";
				$backout = $this->connection->get_value($sql);
				
				$sql = "SELECT COUNT(id) AS backoutCount FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND status = 'B'";
				$backoutCount = $this->connection->get_value($sql);
				
				$sql = "SELECT SUM(trade1 + trade2 + trade3) AS trades FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND (trade1 = 1 OR trade2 = 1 OR trade3 = 1) AND status <> 'B'";
				$trade = $this->connection->get_value($sql);
				
				$sql = "SELECT COUNT(id) FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND type = 'L' AND status <> 'B'";
				$leased = $this->connection->get_value($sql);
				
				$sql = "SELECT SUM(new) AS new, SUM(used) AS used, SUM(units) AS units, SUM(gross_front) AS front, SUM(gross_back) AS back, SUM(showroom) AS showroom, SUM(sold_sh) AS sold_sh, SUM(iphone) AS iphone, SUM(sold_ip) AS sold_ip, SUM(ileads) AS ileads, SUM(sold_il) AS sold_il FROM app_rpt_sales_department WHERE dl_date BETWEEN '$start' AND '$end'";
				$salesRow = $this->connection->get_row($sql);
				
				$sql = "SELECT SUM(toyoguard) AS toyoguard, SUM(onepercent) AS onepercent FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND status <> 'B'";
				$desklogRow = $this->connection->get_row($sql);
				
				$sql = "SELECT COUNT(id) FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND certified = 'Yes' AND status <> 'B'";
				$certified = $this->connection->get_value($sql);
				
				$sql = "SELECT COUNT(id) FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND status = 'R'";
				$rdr = $this->connection->get_value($sql);
				
				//$sql = "SELECT COUNT(id) AS countTotal FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND (nu = 'N' OR (nu = 'U' AND (make = 'TOYOTA' OR make = 'SCION') AND certified = 'Y')) AND status <> 'B'";
				$sql = "SELECT COUNT(id) AS countTotal FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND (nu = 'N' OR (nu = 'U' AND certified = 'Yes')) AND status <> 'B'";
				$countTotal = $this->connection->get_value($sql);
				
				$rdrPercentage = ($countTotal > 0 ? (($rdr * 100)/$countTotal) : 0);
				 
				$new = $salesRow['new'];
				$used = $salesRow['used'];
				$units = $salesRow['units'];
									
				$showroom = $salesRow['showroom'];
				$soldSH = $salesRow['sold_sh'];
				$iphone = $salesRow['iphone'];
				$soldIP = $salesRow['sold_ip'];
				$ileads = $salesRow['ileads'];
				$soldIL = $salesRow['sold_il'];
				
				$closeSH = ($showroom > 0 ? ($soldSH * 100) / $showroom : 0);					
				$closeIP = ($iphone > 0 ? ($soldIP * 100) / $iphone : 0);
				$closeIL = ($ileads > 0 ? ($soldIL * 100) / $ileads : 0);
				
				$front = $salesRow['front'];
				$back = $salesRow['back'];
				$toyoguard = $desklogRow['toyoguard'];
				$onepercent = $desklogRow['onepercent'];
				
				$mtdavg = ($units > 0 ? (($front + $back + $toyoguard + $onepercent)/$units) : 0);
				
				if($this->printing == false)
				{
							$html .='<table width=100% border=0 cellmargin=0 cellpadding=0 cellspacing=0 class="desk-log-table">
									<tr>
										<td class="desk-log-summary-label">New:</td><td>'.$this->formatNumber($new, ".").'</td><td class="desk-log-summary-label">Used:</td><td>'.$this->formatNumber($used, ".").'</td><td class="desk-log-summary-label">Total Units:</td><td>'.$this->formatNumber($units, ".").'</td><td class="desk-log-summary-label">Trade-Ins:</td><td>'.$this->formatNumber($trade, "0").'</td><td class="desk-log-summary-label">Leased:</td><td>'.$this->formatNumber($leased, "0").'</td>
									</tr>
									<tr>
										<td class="desk-log-summary-label">Front:</td><td>'.$this->formatNumber($front, "$").'</td><td class="desk-log-summary-label">Toyoguard:</td><td>'.$this->formatNumber($toyoguard, "$").'</td><td class="desk-log-summary-label">1%:</td><td>'.$this->formatNumber($onepercent, "$").'</td><td class="desk-log-summary-label">Back:</td><td>'.$this->formatNumber($back, "$").'</td><td class="desk-log-summary-label">MTD AVG:</td><td>'.$this->formatNumber($mtdavg, "$").'</td>
									</tr>
									<tr>
										<td class="desk-log-summary-label">Showroom:</td><td>'.$this->formatNumber($soldSH, "0").'</td><td class="desk-log-summary-label">Phone:</td><td>'.$this->formatNumber($soldIP, "0").'</td><td class="desk-log-summary-label">Internet:</td><td>'.$this->formatNumber($soldIL, "0").'</td><td class="desk-log-summary-label">Backout #:</td><td>'.$this->formatNumber($backoutCount, "").'</td><td class="desk-log-summary-label">Backout:</td><td>'.$this->formatNumber($backout, "$").'</td>
									</tr>
									<tr>
										<td class="desk-log-summary-label"></td><td></td><td class="desk-log-summary-label">Certified:</td><td>'.$this->formatNumber($certified, "0").'</td><td class="desk-log-summary-label">RDR:</td><td>'.$this->formatNumber($rdr, "0").'</td><td class="desk-log-summary-label">RDR %:</td><td>'.$this->formatNumber($rdrPercentage, "%").'</td><td class="desk-log-summary-label"></td><td></td>
									</tr>
								</table>';
				
					$html .= "<br/>";
				
					$selectionBar = 
						'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=ssales_log">
							<div id="filter" class="" style="text-align:center;">
								<input id=desklog-sort'.$this->appId.' type=hidden name=sort value="'.$sort.'" />
								<input id=desklog-order'.$this->appId.' type=hidden name=order value="'.$order.'" />
								<input id=desklog-option'.$this->appId.' type=hidden name=option value="'.$option.'" />								
								<input type=button name="option" class="desklog-dealer-button" value="Today" onclick=document.setLocation("/gbSuite/home.php?app=ssales_log") />&nbsp
								<input id="dealer-view-button'.$this->appId.'" class="desklog-dealer-options" type="button" value="Month" onclick="showDealerView('.$this->appId.')" />&nbsp
								<input id="date-range-button'.$this->appId.'" class="desklog-dealer-options" type="button" value="Date Range" onclick="showDateRange('.$this->appId.')" />&nbsp
								<input id="desklog-filter-button'.$this->appId.'" class="desklog-dealer-options" style="display:none;" type="button" value="N/U" onclick="showDesklogFilter('.$this->appId.')" />
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
												'<td><input style="float:left" class="desk-log-input" id="desklog-date-from'.$this->appId.'" type=input name="from'.$this->appId.'" value="'.$from.'"/></td>
												 <td><img src="/images/resources/calendar.gif" onclick=displayDatePicker("from'.$this->appId.'",false,"mdy","/"); style="float:left" /></td>
												 <td>&nbsp;&nbsp;<strong>To:&nbsp;</strong></td>' .
												'<td><input style="float:left" class="desk-log-input" id="desklog-date-to'.$this->appId.'" type=input name="to'.$this->appId.'" value="'.$to.'"/></td>
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
			 		
						$html .= $selectionBar."<br/>";
					}
					
					$printOrder = $order;
											
						if($order == "ASC")
							$order = "DESC";
						else
							if($order == "DESC")
								$order = "ASC";
						
						$link = "app=ssales_log";	
						
						if(isset($params['option']))
							$link .= '&option='.$params['option'];
						
						$link .= '&from'.$this->appId.'='.$from.'&to'.$this->appId.'='.$to;
						
						if($nu != 'B')
							$link .= '&nu='.$nu;
							
					$link .= '&order='.$order;
					$processLink = $link;
												
					$link = "/gbSuite/home.php?".$link;
					
					$html .= '<form method=post action="/gbSuite/apps/process_application.php?uid='.$this->user->getUID().'&action=set_rdr&redirect=ssales_log&'.$processLink.'">';
					$html .= '<div style="text-align:center;"><input type="submit" value="Save Changes" /></div>';	
					$html .= '<table width=100% border=0 cellmargin=0 cellpadding=0 cellspacing=0 class="desk-log-table">					
					<tr>
						<td class="desk-log-separator-header" colspan=24>&nbsp;</td>						
					</tr>';
				
					$html .= "<tr>";
					
						if($this->printing == false)
							$html .= "<th colspan=2 class='desk-log-header'>Actions</th><th class='desk-log-header'>Status</th>";

						
						if($this->printing == false)
						{
							$auxSort = $sort;
							
							if($auxSort == "dl_date DESC, id")
								$auxSort = "dl_date";
								
							//$html .= '<div class="report-tool-bar" style="text-align:right;"><img src="/images/resources/printButton.gif" onclick=popUp("/gbSuite/apps/process_application.php?'.$link.'&action=print_report&sort='.$auxSort.'&order='.$printOrder.'"); /></div>';
						}
							 			
						/*headers*/
						foreach($this->fields as $key => $value)
						{
							if($key == "mod_no")
								$html .= '<th class="desk-log-header"><a href="'.$link.'&sort='.$key.'">Vehicle</a></th>';
							else
					 			$html .= '<th class="desk-log-header"><a href="'.$link.'&sort='.$key.'">'. $value .'</a></th>';
														
							if($key == 'back')
								$html .= "<th class='desk-log-header'>&nbsp;</th>";								
						}

						/*View all the data. */
						$front = 0;
						$back = 0;
						$other = 0;
						$total = 0;
						$tradeCount = 0;
												
						$align = array("center", "center", "center", "left", "center", "center", "center", "center", "center", "center", 
										"right", "right", "right", "right", "center", "center", "center", "center", "center", "center", "center");	
						
						$format = array("none", "none", "none", "none", "none", "none", "none", "none", "none", "none", 
										"$", "$", "$", "$", "$", "none", "none", "none", "none", "none", "none");
						
						$count = 0;
						$totalCount = 0;
						
						if($resuts !== false)									
						while($row = mysql_fetch_array($resuts))
						{
							$color = "#3B5998";
							
							if($row['status'] == 'B')
							{
								$html .= "<tr color='RED'>";
								$color = "red";	
							}	
							else
								if($count % 2 == 0 )
									$html .= "<tr>";
								else								
									$html .= "<tr bgcolor='#F2F2F2'>";

							if($this->printing == false)
							{
								$editLink = str_replace("ssales_log", "sales_log", $link);
								$editLink = str_replace("46", "37", $editLink);
																
								$html .= "<td class='desk-log-cell'><a href='".$editLink."&uid".$this->user->getUID()."&action=edit&id=".$row['id']."&redirect=ssales_log'><img src='/images/resources/edit.gif' /></a></td>";
								$html .= "<td class='desk-log-cell'><a href='/gbSuite/home.php?app=sales_log&uid".$this->user->getUID()."&action=delete&id=".$row['id']."&confirm=true'><img src='/images/resources/delete.gif' /></a></td>";	
								$html .= "<td class='desk-log-cell' align=center>
												<select name=ssales_log_".$row['id']." class=desk-log-models>
													<option value=S ".($row['status'] == 'S'? "selected" : "").">Sold</option>
													<option value=R ".($row['status'] == 'R'? "selected" : "").">RDR</option>
													<option value=B ".($row['status'] == 'B'? "selected" : "").">Backout</option>
													<option value=A ".($row['status'] == 'A'? "selected" : "").">Accounting</option>
													<option value=M ".($row['status'] == 'M'? "selected" : "").">Money Received</option>
												</select>								
										</td>";
							}							
							
							$i = 0;
							
							foreach($this->fields as $field => $title)
							{
								if($field == "mod_no")
								{
									if($row["nu"] == "U")
										$row["mod_no"] = $row["model"];										 
								}
								
								if($format[$i] != 'none')
									$html .= "<td style='color:".$color."' align='".$align[$i++]."' class='desk-log-cell'>".$this->formatNumber($row[$field], $format[$i])."</td>";									
								else
								{
									if($field != "notes")
									{
										if($row[$field] != null)
											$html .= "<td style='color:".$color."' align='".$align[$i++]."' class='desk-log-cell'>". $row[$field] ."</td>";
										else
											$html .= "<td style='color:".$color."' align='".$align[$i++]."' class='desk-log-cell'>&nbsp;</td>";	
									}
									else
									{
										if($row[$field] != null && $row[$field] != "" )
											$html .= "<td style='color:".$color."' align='".$align[$i++]."' class='desk-log-cell'>Y</td>";
										else
											$html .= "<td style='color:".$color."' align='".$align[$i++]."' class='desk-log-cell'>&nbsp;</td>";
									}
																			
								}
								
								if($field == 'front' && $row['status'] != 'B')
									$front += $row[$field];
								
								if($field == 'other' && $row['status'] != 'B')
									$other += $row[$field];
									
								if($field == 'back')
								{
									if($row['status'] != 'B')
										$back += $row[$field];
										
									$html .= "<td class='desk-log-cell'>&nbsp;</td>";									
								}
								
								if($field == 'total' && $row['status'] != 'B')
									$total += $row[$field];
									
								if($field == 'trade1' && $row['status'] != 'B')
									$tradeCount += $row['trade1'];												
							}
							
							$html .= "</tr>";
							
							++$count;
							
							if($row['status'] != 'B')
								++$totalCount;
						}
						
						if($count > 0)
						{
							$html .= "<tr>";
							
							if($this->printing == false)
								$html .= "<th colspan=2 class='desk-log-header'>Actions</th><th class='desk-log-header'>Status</th>";
															
							/*headers*/
							foreach($this->fields as $key => $value)
							{
								if($key == "mod_no")
									$html .= '<th class="desk-log-header"><a href="'.$link.'&sort='.$key.'">Vehicle</a></th>';
								else
						 			$html .= '<th class="desk-log-header"><a href="'.$link.'&sort='.$key.'">'. $value .'</a></th>';
															
								if($key == 'back')
									$html .= "<th class='desk-log-header'>&nbsp;</th>";								
							}
						}						
						
						$html .= "<tr><th class='desk-log-total-row' colspan=".($this->printing == false? 3 : 1).">Totals</th>";
						$html .= "<td class='desk-log-total-row' align=center>".$totalCount."</td>";													
						$html .= "<td class='desk-log-total-row' align=center colspan=8></td>";						
						$html .= "</tr>";					
								
				$html .= "</table></form>";								
				$html .= "</div>";
				
				echo $html;
			}
		}
		
		public function delete($params)
		{
			if(!isset($params['confirm']))
			{
				$query = "SELECT * FROM app_rpt_desk_log WHERE id = ".$params['id'];
				$row = $this->connection->get_row($query);
				
				$query = "DELETE FROM app_rpt_desk_log WHERE id = ".$params['id'];
			
				$this->connection->exec_query($query);	
				
				$query = "SELECT * FROM power_rank_setup";
			$powerRankSetup = $this->connection->get_row($query);
			
			$powerRankSetup['units_percentage'] = ($powerRankSetup['units_percentage'] > 0 ? $powerRankSetup['units_percentage'] / 100 : 0);
			$powerRankSetup['front_percentage'] = ($powerRankSetup['front_percentage'] > 0 ? $powerRankSetup['front_percentage'] / 100 : 0);
			$powerRankSetup['back_percentage'] = ($powerRankSetup['back_percentage'] > 0 ? $powerRankSetup['back_percentage'] / 100 : 0);
			$powerRankSetup['total_percentage'] = ($powerRankSetup['total_percentage'] > 0 ? $powerRankSetup['total_percentage'] / 100 : 0);
				
				
				$this->updateSalesDepartmentRow($row['sp1'],$powerRankSetup);
				
				if($row['sp2'] != "0")
					$this->updateSalesDepartmentRow($row['sp2'],$powerRankSetup);
			}
			else
				if($params['confirm'] == 'true')
				{
					$html = "";
					$html .= "<div>Are you sure you want to delete this record?</div>";
					$html .= '<div><a class="button-link" href="/gbSuite/apps/process_application.php?app=ssales_log&uid='.$this->user->getUID().'&action=delete&id='.$params['id'].'&redirect=ssales_log" >Yes</a>&nbsp;<a class="button-link" href="/gbSuite/home.php?app=ssales_log">Go back</a></div>';
				}
			
			$this->html = $html;
		}
		
		public function save($params)
		{
			$updateString = "";
			
			/**To update**/			
			foreach($params as $fieldName => $value)	
			{	
				//if((substr($fieldName, 0, 5) == "user_") && (strpos($fieldName, "desk_log_") === false ))
				if((substr($fieldName, 0, 9) == "desk_log_") && $fieldName != "desk_log_sp1_1" && $fieldName != "desk_log_sp2_1" )
				{
					if($fieldName == 'desk_log_dl_date')
					{
						if($value != "")
						{
							$values = explode('/', $value);
							$value = $values[2].$values[0].$values[1];	
							
							$updateString .= (($updateString == "")? "" : ", ").substr($fieldName, 9, strlen($fieldName) - 9)." = '".$value."'";
						}						
						else
							$updateString .= (($updateString == "")? "" : ", ").substr($fieldName, 9, strlen($fieldName) - 9)." = now()";
					}
					else					
						$updateString .= (($updateString == "")? "" : ", ").substr($fieldName, 9, strlen($fieldName) - 9)." = '".$value."'";
				}					
			}
			
			$query = "UPDATE app_rpt_desk_log SET $updateString, total = front + back + other WHERE id = ".$params['desk_log_id'];
						 
			$this->connection->exec_query($query);
			
			$field = "desk_log_sp1";
			
			$query = "SELECT * FROM power_rank_setup";
			$powerRankSetup = $this->connection->get_row($query);
			
			$powerRankSetup['units_percentage'] = ($powerRankSetup['units_percentage'] > 0 ? $powerRankSetup['units_percentage'] / 100 : 0);
			$powerRankSetup['front_percentage'] = ($powerRankSetup['front_percentage'] > 0 ? $powerRankSetup['front_percentage'] / 100 : 0);
			$powerRankSetup['back_percentage'] = ($powerRankSetup['back_percentage'] > 0 ? $powerRankSetup['back_percentage'] / 100 : 0);
			$powerRankSetup['total_percentage'] = ($powerRankSetup['total_percentage'] > 0 ? $powerRankSetup['total_percentage'] / 100 : 0);
			
			if($params['desk_log_sp1_1'] == $params['desk_log_sp1'])
				$this->updateSalesDepartmentRow($params['desk_log_sp1'], $powerRankSetup);	
			else
			{
				if($params['desk_log_sp1'] != "0")
					$this->updateSalesDepartmentRow($params['desk_log_sp1'], $powerRankSetup);
				
				if($params['desk_log_sp1_1'] != "0")
					$this->updateSalesDepartmentRow($params['desk_log_sp1_1'], $powerRankSetup);
			}
			
			if($params['desk_log_sp2_1'] == $params['desk_log_sp2'])
				$this->updateSalesDepartmentRow($params['desk_log_sp2'], $powerRankSetup);
			else
			{
				if($params['desk_log_sp2_1'] != "0")
					$this->updateSalesDepartmentRow($params['desk_log_sp2_1'], $powerRankSetup);
					
				if($params['desk_log_sp2'] != "0")
					$this->updateSalesDepartmentRow($params['desk_log_sp2'], $powerRankSetup);
			}
		}
		
		function updateSalesDepartmentRow($employeeId, $powerRankSetup)
		{
			/***/
			$query = "DELETE FROM app_rpt_sales_department WHERE employee_id = $employeeId";
			$rs = $this->connection->exec_query($query);
			
			/***/
			$query = "DELETE FROM app_rpt_sales_goals WHERE employee_id = $employeeId";
			$rs = $this->connection->exec_query($query);
			
			/***/
			$query = "DELETE FROM app_rpt_gross_profit_analisys WHERE employee_id = $employeeId";
			$rs = $this->connection->exec_query($query);
			
			/***/
			$query = "DELETE FROM app_rpt_power_rank WHERE employee_id = $employeeId";
			$rs = $this->connection->exec_query($query);
			
			$query = "SELECT DISTINCT dl_date FROM app_rpt_desk_log WHERE (sp1 = $employeeId OR sp2 = $employeeId) AND status <> 'B'";
			
			$rs = $this->connection->exec_query($query);
			
			if($rs !== false)
			{
				while($row = mysql_fetch_array($rs))
					$this->updateReports($employeeId, $powerRankSetup, $row['dl_date']);
			}
		}
		
		function updateReports($employeeId, $powerRankSetup, $date)
		{
			$grossFront = 0;
			$grossBack = 0;
			$other = 0;
			$new = 0;
			$used = 0;
			$count = 0;
			$units = 0;
			$track = 0;
			
			$soldSH = 0;
			$soldIP = 0;
			$soldIL = 0;
			
			$grossPA = array("`(999)+`" => 0, "`(998-500)`" => 0, "`(499-0)`" => 0, "`1-499`" => 0, "`500-1499`" => 0, "`1500+`" => 0);
						
			$query = "SELECT * FROM app_rpt_desk_log WHERE (sp1 = $employeeId OR sp2 = $employeeId) AND status <> 'B' AND dl_date = '$date'";
			
			$rs = $this->connection->exec_query($query);
						
			if($rs !== false)
			{
				while($row = mysql_fetch_array($rs))
				{
					if($row["sp2"] != "0")
					{
						$grossFront += ($row["front"] != 0 ? $row["front"] / 2 : 0); 
						$grossBack += ($row["back"] != 0 ? $row["back"] / 2 : 0);
						$other += ($row["other"] != 0 ? $row["other"] / 2 : 0);
						
						$units += 0.5;
						$track += 0.5;
						
						if($row['source'] == 'S')
							$soldSH += 0.5;
						else
							if($row['source'] == 'P')
								$soldIP += 0.5;
							else
								if($row['source'] == 'I')
									$soldIP += 0.5;
								
						if($row['nu'] == 'N')
							$new += 0.5;
						else
							if($row['nu'] == 'U')
								$used += 0.5;			
									
					}
					else
					{
						$grossFront += $row["front"]; 
						$grossBack += $row["back"];
						$other += $row["other"];
						
						++$units;
						++$track;
						 
						if($row['source'] == 'S')
							++$soldSH;
						else
							if($row['source'] == 'P')
								++$soldIP;
							else
								if($row['source'] == 'I')
									++$soldIL;
									
						if($row['nu'] == 'N')
							++$new;
						else
							if($row['nu'] == 'U')
								++$used;						
					}
					
					$grossPA = $this->setGrossProfitAnalysisRangeValue($row['front'], $grossPA, $row["sp2"] != "0");
						
					++$count;	
				}
					
				//New implementation
				//Verify if the sales person exists in app_rpt_sales_department
				//$query = "SELECT COUNT(employee_id) AS recordCount FROM app_rpt_sales_department WHERE employee_id = ".$employeeId." AND dl_date = '$date'";
			
				$mtdAVG = ($units > 0 ? (($grossFront + $grossBack)/$units) : 0);
				
				$mtdAVGOther = ($units > 0 ? (($grossFront + $grossBack + $other)/$units) : 0);
				
				
				$query = "INSERT INTO app_rpt_sales_department (employee_id, sales_person_1, units, track, new, used, gross_front, 
																gross_back, gross_total, mtd_avg, showroom, sold_sh, `close_sh%`, iphone, sold_ip, `close_ip%`, ileads, sold_il, `close_il%`, dl_date)
								SELECT employee_id, name, $units, $track, ".($new).", ".($used).", ".$grossFront.", ".$grossBack.", ".($grossFront + $grossBack).", ".$mtdAVG.", 0, $soldSH, 0, 0, $soldIP, 0, 0, $soldIL, 0, '$date'   
									FROM info 
									WHERE employee_id = ".$employeeId;
			
			
			

										
				$this->connection->exec_query($query);

		
				//echo $query;
				
				//Gross profit analysis
				//Verify if the sales person exists in app_rpt_gross_profit_analysis
				$totalUnits = $grossPA["`(999)+`"] + $grossPA["`(998-500)`"] + $grossPA["`(499-0)`"] + $grossPA["`1-499`"] + $grossPA["`500-1499`"] + $grossPA["`1500+`"];
				
				$percentages = array("`(999)+%`" => 0, "`(998-500)%`" => 0, "`(499-0)%`" => 0, "`1-499%`" => 0, "`500-1499%`" => 0, "`1500+%`" => 0);
				$percentages["`(999)+%`"] = ($totalUnits > 0 ? (($grossPA["`(999)+`"] * 100)/$totalUnits) : 0);
				$percentages["`(998-500)%`"] = ($totalUnits > 0 ? (($grossPA["`(998-500)`"] * 100)/$totalUnits) : 0);
				$percentages["`(499-0)%`"] = ($totalUnits > 0 ? (($grossPA["`(499-0)`"] * 100)/$totalUnits) : 0);
				$percentages["`1-499%`"] = ($totalUnits > 0 ? (($grossPA["`1-499`"] * 100)/$totalUnits) : 0);
			    $percentages["`500-1499%`"] = ($totalUnits > 0 ? (($grossPA["`500-1499`"] * 100)/$totalUnits) : 0);
				$percentages["`1500+%`"] = ($totalUnits > 0 ? (($grossPA["`1500+`"] * 100)/$totalUnits) : 0);
			    
				$query = "INSERT INTO app_rpt_gross_profit_analisys (employee_id, sales_person_1, `(999)+`, `(998-500)`, `(499-0)`, `1-499`, `500-1499`, `1500+`, `(999)+%`, `(998-500)%`, `(499-0)%`, `1-499%`, `500-1499%`, `1500+%`, avgfront, avgback, mtdavg, dl_date)
							SELECT employee_id, name, ".$grossPA["`(999)+`"].", ".$grossPA["`(998-500)`"].", ".$grossPA["`(499-0)`"].", ".$grossPA["`1-499`"].", ".$grossPA["`500-1499`"].", ".$grossPA["`1500+`"].",   
								".$percentages["`(999)+%`"].", ".$percentages["`(998-500)%`"].", ".$percentages["`(499-0)%`"].", ".$percentages["`1-499%`"].", ".$percentages["`500-1499%`"].", ".$percentages["`1500+%`"].", 
								$grossFront, $grossBack, ".$mtdAVG.", '$date' 
								FROM info 
								WHERE employee_id = ".$employeeId;
					
				$this->connection->exec_query($query);

				//PONER VALORES AQUI.			
				$query = "INSERT INTO app_rpt_sales_goals (employee_id, units_promised, to_goal, units_diference, re_commit, initial, units_delivered2, final_to_goal, units_delivered, dl_date)" .
						" values ($employeeId, 0, 0,0,0,0,$units,0,$units,'$date')";
				$this->connection->exec_query($query);

				//Verify if the sales person exists in app_rpt_sales_department
				
				//(Units * 1500) * (30%) + (Front * 20%) + (Back * 5%) + (Total * 10%) / 4

				$totalScore = (($units * $powerRankSetup['units_multiplier']) * $powerRankSetup['units_percentage']) + ($grossFront * $powerRankSetup['front_percentage']) + ($grossBack * $powerRankSetup['back_percentage']) + (($grossFront + $grossBack) * $powerRankSetup['total_percentage']) / 4;
				 
					$query = "INSERT INTO app_rpt_power_rank (sales_person, units_sold, us_rank, total_front_end, tfe_rank, total_back_end, tbe_rank, 
																total_gross, tg_rank, total_score, power_rank, employee_id, dl_date)
								SELECT name, $units, 0, $grossFront, 0, $grossBack, 0, ".($grossFront + $grossBack).", 0, $totalScore, 0, $employeeId, '$date'     
									FROM info 
									WHERE employee_id = ".$employeeId;
									
					$this->connection->exec_query($query);
 			}			
		}
		
		function setGrossProfitAnalysisRangeValue($value, $array, $shared)
		{
			$unit = 0;
			
			if($shared == false)
				$unit = 1;
			else
				$unit = 0.5;
				
			if($value <= -999)
				$array['`(999)+`'] += $unit;
			else
			 	if($value >= -998 && $value <= -500)
					$array['`(998-500)`'] += $unit;
				else
					if($value >= -499 && $value <= 0)
						$array['`(499-0)`'] += $unit;
					else
						if($value >= 1 && $value <= 499)
							$array['`1-499`'] += $unit;
						else
							if($value >= 500 && $value <= 1499)
								$array['`500-1499`'] += $unit;
							else
								if($value >= 1500)
									$array['`1500+`'] += $unit;
				
				return $array;			
		}
		
		public function edit($params)
		{
			$sort = "dl_date DESC, id";
			$order = "DESC";
			$from = "";
			$to = "";
			$nu = "";
			$dateRange = "";
			$start = "";
			$end = "";
			
			if(isset($params['sort']))
				$sort = $params['sort'];
				
			if(isset($params['order']))
				$order = $params['order'];
			
			$option = "";
			$title = "";
			
			$currentDate = $this->connection->get_value("SELECT date_format(now(), '%m/%d/%Y') AS currentDate");
			
			if(isset($params['option']))
			{
				$option = $params['option'];
		
				if($option == "View")
				{
					$from = $params['from'.$this->appId];
					$to = $params['to'.$this->appId];
					
					$dateRange = $from." - ".$to;
											
					$start = substr($from, 6, 4).substr($from, 0, 2).substr($from, 3, 2);
					$end = substr($to, 6, 4).substr($to, 0, 2).substr($to, 3, 2);
				}
				else //Verify if correspond to a dealer view
				{	
					$query = "SELECT date_format(`from`, '%Y%m%d') AS `start`, date_format(`to`, '%Y%m%d') AS `end`, date_format(`from`, '%m/%d/%Y')AS `from`, date_format(`to`, '%m/%d/%Y')AS `to` FROM dealer_view WHERE label = '$option'";
					$row = $this->connection->get_row($query);
					
					if($row != null)
					{
						$start = $row['start'];
						$end = $row['end'];
						
						$from = $row['from'];
						$to = $row['to'];
						
						$dateRange = "$from - $to"; 
					}	
				}	
			}
			else						
			{	
				$day = date("d");			
				
				$date = date("m")."/".$day."/".date("Y");
				$dateRange = $date." - ".$date;
				
				$from = $date;
				$to = $date;
				
				$row = null;
				
				$query = "SELECT date_format(`from`, '%Y%m%d') AS `from`, date_format(`to`, '%Y%m%d') AS `to`, date_format(`from`, '%m/%d/%Y') AS `start`, date_format(`to`, '%m/%d/%Y') AS `end` FROM dealer_view WHERE now() BETWEEN `from` AND `to`";
				
				$dealerView = $this->connection->get_row($query);
				
				if($dealerView != null)
				{
					$start = $dealerView['from'];
					$end = $dealerView['to'];
				}	 
				else
				{
					$query = "SELECT DATEDIFF(DATE_ADD('2008".date("m")."01', INTERVAL 1 MONTH), '2008".date("m")."01') AS days";
					$days = $this->connection->get_value($query);
					
					$start = date("Y").date("m")."01".
					$end = date("Y").date("m").$days;					
				}			
			}
			
			if($order == "ASC")
				$order = "DESC";
			else
				if($order == "DESC")
					$order = "ASC";
			
			$link = "app=ssales_log";	
			
			if(isset($params['option']))
				$link .= '&option='.$params['option'];
			
			$link .= '&from'.$this->appId.'='.$from.'&to'.$this->appId.'='.$to;
			
			if($nu != 'B')
				$link .= '&nu='.$nu;
			
			if($this->printing == false)
			{
				$auxSort = $sort;
				
				if($auxSort == "dl_date DESC, id")
					$auxSort = "dl_date";			
			}
			
			$link .= '&order='.$order;
			$processLink = $link;
			
			$link = "/gbSuite/home.php?".$link;
			
			echo "<div>";
			echo "<div class='report-title'>Sales Log</div>";
					
			$sql = "select id, deal_no, stock_no, customer, sp1, sp2, sm, fi, mod_no, last7vin, front, reserve, vsi, cc, gap, cl, 
							aft, finance, total, model, year, make, miliage, certified, certified_no, age, 
							trade1, trade1_acv, trade1_make, trade1_model, trade1_year, trade1_miliage, 
							trade2, trade2_acv, trade2_make, trade2_model, trade2_year, trade2_miliage, 
							trade3, trade3_acv, trade3_make, trade3_model, trade3_year, trade3_miliage, 
							tg, 1perc, hb, back, date_format(dl_date, '%m/%d/%Y') AS dl_date, nu,
							type, status, notes, other, toyoguard, onepercent, source, holdback   
					from app_rpt_desk_log WHERE id = ".$params['id'];

			$resuts = $this->connection->exec_query($sql);
			$row = null;
			$row = null;
			$sp1 = null;
			$sp2 = null;
			$sm = null;
			$fi = null;
			$notes = null;
						
			///gbSuite/apps/process_application.php?app=ssales_log&redirect=ssales_log&action=save&uid=<?= $this->user->getUID()			
			?>
			<script>
			function saveDeskLogChanges()
			{
				var form = document.getElementById('desk-log');
				form.submit();
			}
			</script>
			<form method=post action='/gbSuite/apps/process_application.php?<? echo $processLink."&redirect=ssales_log&action=save&uid=".$this->user->getUID(); ?>' id='desk-log'>
			<table width=100% border=0 cellmargin=0 cellpadding=0 cellspacing=0 class='desk-log-table'>
				<tr>
				<?
					/*headers*/
					foreach($this->fields as $key => $value)
					{
						if($key != 'id' && $key != 'sp2' && $key != 'sm' && $key != 'fi' && $key != 'other')
						{
							if($key == "sp1")
								echo "<th class='desk-log-header'>Sales</th>";
							else
								if($key == 'mod_no')
									echo "<th class='desk-log-header'>Vehicle</th>";
								else
					 				echo "<th class='desk-log-header'>". $value ."</th>";
						}													

						/*if($key != 'id')
					 		echo  "<th class='desk-log-header'>". $value ."</th>";*/
					}
				?>
				</tr>
				<tr>				
				<?
					//echo "<a onclick='saveDeskLogChanges()'><img src='/images/resources/save.gif' /></a>";
					echo "<input class='button-link' type='submit' value='Save' />";
					//echo "<input class='button-link' type='button' value='Go back' onclick=document.setLocation('/gbSuite/home.php?app=ssales_log') />";
					echo "<input class='button-link' type='button' value='Go back' onclick=document.setLocation('".$link."') />";
					echo "<input type='hidden' name='desk_log_id' value=".$params['id']." />";					
				?>
				</tr>
				<?
					/*View all the data. */
					
					while($row = mysql_fetch_array($resuts))
					{
						$row2 = $row;
						
						echo "<tr>";
												
						foreach($this->fields as $field => $title)
						{							
							if($field == 'mod_no')
							{
								$sql = 'select model from models order by model';
								$models = $this->connection->fillSelect($sql,'model','model', $row[$field], false);

								echo "<td class='desk-log-cell'>";
								echo '<input class="desk-log-input" type="button" id="desk-log-vehicle-button" value="ADD" onclick="showVehicle()" />';								
								
								//echo "<select name='desk_log_$field' class='desk-log-models'>" . $models.
								//	"</select>";
								
								echo "</td>";
							}
							else
								if($field == 'sp1')
								{
									$sql = "select initials, employee_id, name from info WHERE (title = 'Salesperson' OR name = 'House') AND employee_id IS NOT NULL  AND active = 1 ORDER BY initials";
									$sp1 = $this->connection->fillDesklogSelect($sql,'employee_id','name', 'initials', $row[$field], false);									

									echo "<td class='desk-log-cell'>";		
									echo '<input class="desk-log-input" type="button" id="desk-log-sales-button" value="View" onclick="showSales()" />';
																		
									//echo "<select name='desk_log_$field' class='desk-log-models'>" . $sp.
									//	"</select>";
										
									echo "<input type='hidden' name='desk_log_sp1_1' value='".$row[$field]."'/>";
									echo "</td>";
								}
								else
								if($field == 'sp2')
								{
									$sql = "select initials, employee_id, name from info WHERE (title = 'Salesperson' OR name = 'House') AND employee_id IS NOT NULL  AND active = 1 ORDER BY initials";
									$sp2 = $this->connection->fillDesklogSelect($sql,'employee_id','name', 'initials', $row[$field], true);									
									
									//echo "<td class='desk-log-cell'>";
									
									//echo "<select name='desk_log_$field' class='desk-log-models'>" . $sp.
									//	"</select>";
										
									echo "<input type='hidden' name='desk_log_sp2_1' value='".$row[$field]."'/>";
									
									//echo "</td>";
								}
								else
									if($field == 'sm')
									{
										$sql = "select initials, employee_id, name from info WHERE (title = 'Sales Manager' OR title = 'General Manager' OR title = 'General Sales Manager') AND employee_id IS NOT NULL  AND active = 1 ORDER BY initials";
										$sm = $this->connection->fillDesklogSelect($sql,'employee_id','name', 'initials', $row[$field], false);										
																			
										//echo "<td class='desk-log-cell'>";
										
										//echo "<select name='desk_log_$field' class='desk-log-models'>" . $sm.
										//	"</select>";
										
										//echo "</td>";
									}
									else
									if($field == 'fi')
									{
										$sql = "select initials, employee_id, name from info WHERE (title = 'Finance Manager' OR name = 'House') AND employee_id IS NOT NULL  AND active = 1 ORDER BY initials";
										$fi = $this->connection->fillDesklogSelect($sql,'employee_id','name', 'initials', $row[$field], false);										
																			
										//echo "<td class='desk-log-cell'>";
										
										//echo "<select name='desk_log_$field' class='desk-log-models'>" . $sm.
										//	"</select>";
										
										//echo "</td>";
									}
									else
										if($field == 'nu')
										{
											echo "<td class='desk-log-cell'>";
											echo	'<select id="desk-log-nu" name="desk_log_nu" class="desk-log-models" onchange="changeVehicleNU()">
															<option value="N" '.($row[$field] == 'N' ? 'selected' : '').'>N</option>
															<option value="U" '.($row[$field] == 'U' ? 'selected' : '').'>U</option>
														</select>';
											echo "</td>";
										}
										else
										if($field == 'status')
										{
											echo "<td class='desk-log-cell'>";
											echo	'<select name="desk_log_status" class="desk-log-models">
															<option value="S" '.($row[$field] == 'S' ? 'selected' : '').'>S</option>
															<option value="R" '.($row[$field] == 'R' ? 'selected' : '').'>R</option>
															<option value="B" '.($row[$field] == 'B' ? 'selected' : '').'>B</option>
															<option value="A" '.($row[$field] == 'A' ? 'selected' : '').'>A</option>
															<option value="M" '.($row[$field] == 'M' ? 'selected' : '').'>M</option>
														</select>';
											echo "</td>";
																						
										}
										else
										if($field == 'type')
										{
											echo "<td class='desk-log-cell'>";
											echo	'<select name="desk_log_type" class="desk-log-models">
															<option value="R" '.($row[$field] == 'R' ? 'selected' : '').'>R</option>
															<option value="L" '.($row[$field] == 'L' ? 'selected' : '').'>L</option>
														</select>';
											echo "</td>";											
										}
										else
										if($field == 'source')
										{
											echo "<td class='desk-log-cell'>";
											echo	'<select name="desk_log_source" class="desk-log-models">
															<option value="S" '.($row[$field] == 'S' ? 'selected' : '').'>S</option>
															<option value="I" '.($row[$field] == 'I' ? 'selected' : '').'>I</option>
															<option value="P" '.($row[$field] == 'P' ? 'selected' : '').'>P</option>
														</select>';
											echo "</td>";											
										}										
										else
											if($field == 'notes')
											{
												$notes = $row['notes'];									
			
												echo "<td class='desk-log-cell'>";		
												echo '<input class="desk-log-input" type="button" id="desk-log-notes-button" value="View" onclick="showNotes()" />';
												
												echo "</td>";
											}
											else	
												if($field == 'front')
													echo '<td class="desk-log-cell"><input class="desk-log-input" type="button" id="desk-log-front-button" value="'.$row[$field].'" onclick="showFront()"/></td>';
											else
												if($field != 'id')
												{
													if($field != 'total')
													{
														if($field == 'dl_date')
															echo "<table cellpadding=0 cellspacing=0 cellmargin=0><tr><td><input style='float:left' class='desk-log-input' id='dl_date' type=input name='desk_log_". $field ."' value='".$row[$field]."'/></td><td><img src='/images/resources/calendar.gif' onclick=displayDatePicker('desk_log_dl_date',false,'mdy','/'); style='float:left' /></td></tr></table>";
														else
														if($field != 'back' && $field != 'trade1' && $field != 'notes' && $field != 'front' && $field != 'other')
															echo "<td class='desk-log-cell'><input class='desk-log-input' type=input name='desk_log_". $field."' value='".$row[$field]."'/></td>";
														else	
															if($field == 'back')
															{	
																echo '<td class="desk-log-cell"><input class="desk-log-input" type="button" id="desk-log-back-button" value="'.$row[$field].'" onclick="showBack()"/></td>';
															}
															else
																if($field == 'trade1')															 
																	echo '<td class="desk-log-cell"><input class="desk-log-input" type="button" id="desk-log-trade-button" value="'.$row[$field].'" onclick="showTrade()"/></td>';
													}												
													else												
														echo "<td class='desk-log-cell'>".$row[$field]."</td>";
												}
													
										/*else								
											echo "<td class='desk-log-cell'>". $row[$field] ."</td>";*/							
						}
									
						echo "</tr>";
					}
					?>	
			
			<tr>
				<td colspan=15>			
			<?
			$row = $row2;
			
					echo '<div class="sbox-window" style="display:none;" id="sbox-window">									
							<div class="sbox-content-iframe" id="sbox-content">
								<div id="back-sbox-content" style="display:none;">
									<table class="sand-box-table" cellspacing=0 border=0>
										<tr><td><label for="desk-log-reserve">Reserve:</label></td><td><input class="sand-box-input" id="desk-log-reserve" name="desk_log_reserve" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" value="'.$row['reserve'].'" /></td>	    						
										<td><label for="desk-log-vsi">VSI:</label></td><td><input class="sand-box-input" id="desk-log-vsi" name="desk_log_vsi" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" value="'.$row['vsi'].'" /></td>
										<td><label for="desk-log-cc">CC:</label></td><td><input id="desk-log-cc" class="sand-box-input" name="desk_log_cc" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" value="'.$row['cc'].'" /></td>
										<td><label for="desk-log-gap">GAP:</label></td><td><input id="desk-log-gap" name="desk_log_gap" class="sand-box-input" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" value="'.$row['gap'].'" /></td>
										<td><label for="desk-log-cl">CL:</label></td><td><input id="desk-log-cl" class="sand-box-input" name="desk_log_cl" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" value="'.$row['cl'].'" /></td>
										<td><label for="desk-log-aft">AFT:</label></td><td><input id="desk-log-aft" class="sand-box-input" name="desk_log_aft" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" value="'.$row['aft'].'" /></td></tr>
									</table>
										<input id="desk-log-back-input" name="desk_log_back" type="hidden" editable=false value="'.$row['back'].'" /></br>
								</div>
								<div id="front-sbox-content" style="display:none;">
									<table class="sand-box-table" cellspacing=0 border=0>
										<tr>
											<td><label for="desk-log-front">Front Gross:</label>&nbsp;<input class="sand-box-input" id="desk-log-front" name="desk_log_front" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" value="'.$row['front'].'" /></td>											
										</tr>
										<tr id="desk-log-other-label">
											<td colspan=8><label>Other:</label></td>
										</tr>
										<tr id="desk-log-front-other">											    						
											<td><label for="desk-log-holdback">Holdback:</label></td><td><input class="sand-box-input" id="desk-log-holdback" name="desk_log_holdback" onkeyup="updateOnePercent()" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" value="'.$row['holdback'].'" /></td>												
											<td><label for="desk-log-onepercent">1%:</label></td><td><input class="sand-box-input" id="desk-log-onepercent-visible" disabled="true" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" value="'.$row['onepercent'].'" /></td>
											<td><label for="desk-log-toyoguard">Toyoguard:</label></td><td><select id="desk-log-toyoguard" class="desk-log-models" name="desk_log_toyoguard" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" onchange="updateOnePercent()"><option value="250" '.($row['toyoguard'] == '250' ? 'selected' : '').'>Y</option><option value="0" '.($row['toyoguard'] == '0' ? 'selected' : '').'>N</option></select></td>
											<td><label for="desk-log-total-other">Total Other:</label></td><td><input class="sand-box-input" id="desk-log-total-other" disabled="true" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" value="'.($row['onepercent'] + $row['toyoguard']).'" /></td>
										</tr>
									</table>
									<input id="desk-log-onepercent" name="desk_log_onepercent" type="hidden" value="'.$row['onepercent'].'" /></br>
									<input id="desk-log-other-input" name="desk_log_other" type="hidden" value="'.$row['other'].'" /></br>																				
								</div>
								<div id="trade-sbox-content" style="display:none;">
									<table class="sand-box-table" cellspacing=0 border=0>
										<tr>
										<td><label for="desk-log-trade1-year">Year:</label></td><td><input id="desk-log-trade1-year" name="desk_log_trade1_year" class="sand-box-input" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this)" type="text" value="'.$row['trade1_year'].'" /></td>
										<td><label for="desk-log-trade1-make">Make:</label></td><td><input id="desk-log-trade1-make" class="sand-box-input" name="desk_log_trade1_make" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this)" value="'.$row['trade1_make'].'" /></td>	    						
										<td><label for="desk-log-trade1-model">Model:</label></td><td><input id="desk-log-trade1-model" class="sand-box-input" name="desk_log_trade1_model" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this)" value="'.$row['trade1_model'].'"></td>
										<td><label for="desk-log-trade1-miliage">Mileage:</label></td><td><input id="desk-log-trade1-miliage" class="sand-box-input" name="desk_log_trade1_miliage" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this)" value="'.$row['trade1_miliage'].'"></td>
										<td><label for="desk-log-trade1-acv">ACV:</label></td><td><input id="desk-log-trade1-acv" name="desk_log_trade1_acv" class="sand-box-input" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" value="'.$row['trade1_acv'].'" /></td></tr>
										<tr>
										<td><label for="desk-log-trade2-year">Year:</label></td><td><input id="desk-log-trade2-year" name="desk_log_trade2_year" class="sand-box-input" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this)" type="text" value="'.$row['trade2_year'].'" /></td>
										<td><label for="desk-log-trade2-make">Make:</label></td><td><input id="desk-log-trade2-make" class="sand-box-input" name="desk_log_trade2_make" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this)" value="'.$row['trade2_make'].'" /></td>	    						
										<td><label for="desk-log-trade2-model">Model:</label></td><td><input id="desk-log-trade2-model" class="sand-box-input" name="desk_log_trade2_model" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this)" value="'.$row['trade2_model'].'"></td>
										<td><label for="desk-log-trade2-miliage">Mileage:</label></td><td><input id="desk-log-trade2-miliage" class="sand-box-input" name="desk_log_trade2_miliage" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this)" value="'.$row['trade2_miliage'].'"></td>										
										<td><label for="desk-log-trade2-acv">ACV:</label></td><td><input id="desk-log-trade2-acv" name="desk_log_trade2_acv" class="sand-box-input" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" value="'.$row['trade2_acv'].'" /></td></tr>
										<tr>
										<td><label for="desk-log-trade3-year">Year:</label></td><td><input id="desk-log-trade3-year" name="desk_log_trade3_year" class="sand-box-input" onkeypress="onkeypress(event)" type="text" onfocus="onfocus(this)" onblur="onblur(this)" value="'.$row['trade3_year'].'" /></td>
										<td><label for="desk-log-trade3-make">Make:</label></td><td><input id="desk-log-trade3-make" class="sand-box-input" name="desk_log_trade3_make" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this)" value="'.$row['trade3_make'].'" /></td>	    						
										<td><label for="desk-log-trade3-model">Model:</label></td><td><input id="desk-log-trade3-model" class="sand-box-input" name="desk_log_trade3_model" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this)" value="'.$row['trade3_model'].'"></td>
										<td><label for="desk-log-trade3-miliage">Mileage:</label></td><td><input id="desk-log-trade3-miliage" class="sand-box-input" name="desk_log_trade3_miliage" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this)" value="'.$row['trade3_miliage'].'"></td>										
										<td><label for="desk-log-trade3-acv">ACV:</label></td><td><input id="desk-log-trade3-acv" name="desk_log_trade3_acv" class="sand-box-input" type="text" onkeypress="onkeypress(event)" onfocus="onfocus(this)" onblur="onblur(this, 0)" value="'.$row['trade3_acv'].'" /></td>
										</tr>
										<input id="desk-log-trade1-input" name="desk_log_trade1" type="hidden" editable=false value="'.$row['trade1'].'" />
										<input id="desk-log-trade2-input" name="desk_log_trade2" type="hidden" editable=false value="'.$row['trade2'].'" />
										<input id="desk-log-trade3-input" name="desk_log_trade3" type="hidden" editable=false value="'.$row['trade3'].'" /></br>
									</table>										
								</div>
								<div id="new-vehicle-sbox-content" style="display:none;">
									<table class="sand-box-table" cellspacing=0 border=0>
										<tr>
											<td>Model #</td><td><select id="desk-log-mod-no" name="desk_log_mod_no" class="desk-log-models">'.$models.'</select></td>												
										</tr>																	
									</table>																												
								</div>
								<div id="used-vehicle-sbox-content" style="display:none;">
									<table class="sand-box-table" cellspacing=0 border=0>
										<tr>
											<td><label for="desk-log-veh-year">Year:</label></td><td><input class="sand-box-input" id="desk-log-veh-year" name="desk_log_year" onkeypress="onkeypress(event)" type="text" value="'.$row['year'].'" /></td>
											<td><label for="desk-log-veh-make">Make:</label></td><td><input class="sand-box-input" id="desk-log-veh-make" name="desk_log_make" onkeypress="onkeypress(event)" type="text" value="'.$row['make'].'" /></td>	    						
											<td><label for="desk-log-veh-model">Model:</label></td><td><input class="sand-box-input" id="desk-log-veh-model" name="desk_log_model" onkeypress="onkeypress(event)" value="'.$row['model'].'" /></td>											
											<td><label for="desk-log-veh-miliage">Mileage:</label></td><td><input class="sand-box-input" id="desk-log-veh-miliage" name="desk_log_miliage" onkeypress="onkeypress(event)" value="'.$row['miliage'].'" /></td>
											<td><label for="desk-log-veh-certified">Certified:</label></td>
											<td> 
													<select id="desk-log-veh-certified" name="desk_log_certified" class="desk-log-models" onchange="changeCertifiedOption()">
														<option value="Y" '.($row['certified'] == 'Y' ? 'selected' : '').'>Y</option>
														<option value="N" '.($row['certified'] == 'N' ? 'selected' : '').'>N</option>
													</select>
											</td>
											<td><label id="desk-log-veh-certified-no-label" for="desk-log-veh-certified-no">Certified #:</label></td><td><input class="sand-box-input" id="desk-log-veh-certified-no" name="desk_log_certified_no" onkeypress="onkeypress(event)" value="'.$row['certified_no'].'" /></td>
										</tr>																	
										<tr>
											<td><label for="desk-log-veh-age">Age:</label></td><td><input class="sand-box-input" id="desk-log-veh-age" name="desk_log_age" onkeypress="onkeypress(event)" value="'.$row['age'].'" /></td><td colspan=10></td>
										</tr>
									</table>																												
								</div>
								<div id="sales-sbox-content" style="display:none;">
									<table class="sand-box-table" cellspacing=0 border=0>
										<tr><td><label for="desk-log-sp1">SP1:</label></td><td><select id="desk-log-sp1" name="desk_log_sp1" class="desk-log-models">'.$sp1.'</select></td>	    						
										<td><label for="desk-log-sp2">SP2:</label></td><td><select id="desk-log-sp2" name="desk_log_sp2" class="desk-log-models">'.$sp2.'</select></td>
										<td><label for="desk-log-sm">SM:</label></td><td><select id="desk-log-sm" name="desk_log_sm" class="desk-log-models">'.$sm.'</select></td>
										<td><label for="desk-log-fi">F&I:</label></td><td><select id="desk-log-fi" name="desk_log_fi" class="desk-log-models">'.$fi.'</select></td></tr>
									</table>																					
								</div>
								<div id="notes-sbox-content" style="display:none;">
									<table class="sand-box-table" cellspacing=0 style="width:100%;" border=0>
										<t' .
												'r><td><label for="desk-log-notes">Notes:</label></td>
										<td><textarea id="desk-log-notes" name="desk_log_notes" style="width:80%;">'.$notes.'</textarea></td></tr>
									</table>																				
								</div>
								<input type="button" class="button-link" value="Save" onclick="saveSandBox()" />&nbsp;
								<input id="sbox-btn-close" type="button" class="button-link" onclick="closeSandBox()" value="Close" />
							</div>								
						</div>';?>
						</td>
					</tr>
				</table>				
			</form>
			<?
			echo "</div>";
			
			$this->printHtml = true;			
		}
		
		public function update($params)
		{
			$query = "SELECT * FROM power_rank_setup";
			$powerRankSetup = $this->connection->get_row($query);
			
			$powerRankSetup['units_percentage'] = ($powerRankSetup['units_percentage'] > 0 ? $powerRankSetup['units_percentage'] / 100 : 0);
			$powerRankSetup['front_percentage'] = ($powerRankSetup['front_percentage'] > 0 ? $powerRankSetup['front_percentage'] / 100 : 0);
			$powerRankSetup['back_percentage'] = ($powerRankSetup['back_percentage'] > 0 ? $powerRankSetup['back_percentage'] / 100 : 0);
			$powerRankSetup['total_percentage'] = ($powerRankSetup['total_percentage'] > 0 ? $powerRankSetup['total_percentage'] / 100 : 0);
			
			$query = "SELECT DISTINCT sp1 FROM app_rpt_desk_log";
			
			$rs = $this->connection->exec_query($query);
			
			while($row = mysql_fetch_assoc($rs))
			{
				$this->updateSalesDepartmentRow($row['sp1'], $powerRankSetup);
			}
			
			$query = "SELECT DISTINCT sp2 FROM app_rpt_desk_log";
			
			$rs = $this->connection->exec_query($query);
			
			while($row = mysql_fetch_assoc($rs))
			{
				$this->updateSalesDepartmentRow($row['sp2'], $powerRankSetup);
			}
			
			echo "Finish!";
			
			$this->printHtml = true;
		}
		
		public function set_rdr($params)
		{
			$prefix = "ssales_log_";
			
			foreach($params as $param => $value)
			{
				if(strpos($param, $prefix) !== false)				
				{
					$query = "UPDATE app_rpt_desk_log SET status = '".$value."' WHERE id = ".str_replace($prefix, "", $param);
					
					$this->connection->exec_query($query);	
				}
			}
		}	
		
		public function add($params)
		{
			$fields = array();
			$values = array();
			$front = 0;
			$back = 0;
			$other = 0;
			$total = 0;
			$units = 0;
			$track = 0;
			$new = 0;
			$used = 0;
			
			foreach($params as $field => $value )
			{
				if(strpos($field,"desk_log_") !== false)
				{
					$field = str_replace("desk_log_","",$field);
					
					if($field == 'dl_date')
					{
						if($value != "")
						{
							$values2 = array();
							$values2 = explode('/', $value);
							$value = $values2[2].$values2[0].$values2[1];	
							
							$values[] = "'".$value."'";
						}						
						else
							$values[] = "now()";
							
					}
					else					
						$values[] = "'".$value."'";
					
					$fields[] = $field;
					
					if($field == 'front')
						$front = $value; 
					else
						if($field == 'back')
							$back = $value;
						else
							if($field == 'other')
							$other = $value;
							
				}
			}
			
			$fields[] = 'total';
			$values[] = $front + $back + $other;
			 	
			$sql = "insert into app_rpt_desk_log (". join($fields,',')  .") values (". join($values,',') .")";
						
			$this->connection->exec_query($sql);
			
			$query = "SELECT * FROM app_rpt_desk_log ORDER BY id DESC LIMIT 1";
			
			$row = $this->connection->get_row($query);
			$model = "";
			if($row['nu'] == 'N')
				$model = $row['mod_no'];
			else
				$model = $row['model'];
			
			$query = "SELECT * FROM power_rank_setup";
			
			$powerRankSetup = $this->connection->get_row($query);
			
			$powerRankSetup['units_percentage'] = ($powerRankSetup['units_percentage'] > 0 ? $powerRankSetup['units_percentage'] / 100 : 0);
			$powerRankSetup['front_percentage'] = ($powerRankSetup['front_percentage'] > 0 ? $powerRankSetup['front_percentage'] / 100 : 0);
			$powerRankSetup['back_percentage'] = ($powerRankSetup['back_percentage'] > 0 ? $powerRankSetup['back_percentage'] / 100 : 0);
			$powerRankSetup['total_percentage'] = ($powerRankSetup['total_percentage'] > 0 ? $powerRankSetup['total_percentage'] / 100 : 0);
			 
			$this->updateSalesDepartmentRow($row['sp1'],$powerRankSetup);
			
			$sp2 = $row['sp2'];
			
			if($row['sp2'] != "0")
				$this->updateSalesDepartmentRow($row['sp2'],$powerRankSetup);
			
			$query = "SELECT uid, name, first_name FROM info WHERE employee_id = ".$row['sp1'];
			$sp1 = $this->connection->get_row($query);
			
			$uid1 = $sp1['uid'];
			$firstName1 = $sp1['first_name'];
			$name1 = $sp1['name'];
			
			$query = "";
			
			if($sp2 == "0")
				$query = "INSERT INTO news (id, uid, type, value, date, status) " .
						"SELECT 0, uid, 'sold_car', '$name1 just sold a $model - congratulations!', now(), 0 " .
						"FROM info ";
			else
			{
				$sp2 = $this->connection->get_row("SELECT uid, name, first_name FROM info WHERE employee_id = ".$sp2);
				$uid2 = $sp2['uid'];
				$firstName2 = $sp2['first_name'];
				$name2 = $sp2['name'];
						
				$query = "INSERT INTO news (id, uid, type, value, date, status) " .
							"SELECT 0, uid, 'sold_car', '$name1 and $name2 just sold a $model - congratulations!', now(), 0 " .
							"FROM info ";
			}			

			if($query != "")				
				$this->connection->exec_query($query);
		}		
		
		public function formatNumber($value, $format)
		{
			if($format == "0")
				$value = number_format($value, 0, '.', ',');
			else
				if($format == "%")
					$value = number_format($value, 0, '.', ',')."%";
				else
					if($format == "$")
						$value = "$".number_format($value, 0, '.', ',');
					else
						if($format == ".")
							$value = number_format  ( $value, 1,'.', ',');						
						
			return $value;	
		}
	}
?>