<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class AssociateSalesLog extends Application
	{
		var $fields;
		var $printHtml = false;
		private $printing = false;
		
		public function __construct()
		{
			$this->appId = 49;
			
			$this->fields = array(
				'deal_no'=>'Deal #',
				'stock_no'=>'Stock #',
				'nu'=>'N/U',	
				'customer'=>'Customer',	
				'sp1'=>'SP1',
				'sp2'=>'SP2', 
				'sm'=>'SM', 
				'fi'=>'F&I',
				'mod_no'=>'Mod #',
				'last7vin'=>'Vin',
				'front'=>'Front',
				'other'=>'Other',
				'back'=>'Back',				
				'total'=>'Total',
				'trade1'=>'TR',
				'dl_date'=>'Date',
				'status' => 'S',
				'notes' => 'N',
				'type' => 'T',
				'source' => 'SRC'
				/*'acv'=>'ACV',
				'tg'=>'tg',
				'1perc'=>'1%',
				'hb'=>'HB'*/
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
				//$html .= '<a href="/gbSuite/apps/process_application.php?app=sales_log&redirect=sales_log&action=update&uid='.$this->user->getUID().'">Update</a>';
				
				$option = "";
				$title = "";
				
				$currentDate = $this->connection->get_value("SELECT date_format(now(), '%m/%d/%Y') AS currentDate");
				
				if(isset($params['option']))
				{
					$option = $params['option'];
					if($option == "")
					{
						$option = 'Today';
						$params['option'] = 'Today';
					}
					if($option == "View" || $option == "Today")
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
					//$where = " WHERE MONTH(dl_date) = MONTH(now()) AND DAY(dl_date) = ".$day;
					
					$row = null;
					
					$query = "SELECT date_format(`from`, '%Y%m%d') AS `from`, date_format(`to`, '%Y%m%d') AS `to`, date_format(`from`, '%m/%d/%Y') AS `start`, date_format(`to`, '%m/%d/%Y') AS `end` FROM dealer_view WHERE now() BETWEEN `from` AND `to`";
					
					$dealerView = $this->connection->get_row($query);
					
					if($dealerView != null)
					{
						$start = $dealerView['from'];
						$end = $dealerView['to'];
						
						$dateRange = $dealerView['start']." - ".$dealerView['end'];
					}	 
					else
					{
						$query = "SELECT DATEDIFF(DATE_ADD('2008".date("m")."01', INTERVAL 1 MONTH), '2008".date("m")."01') AS days";
						$days = $this->connection->get_value($query);
						
						$start = date("Y").date("m")."01".
						$end = date("Y").date("m").$days;					
						
						$dateRange = date("m")."/01/".date("Y")." - ".date("m")."/$days/".date("Y");
					}
					
					$where = " WHERE dl_date BETWEEN '$start' AND '$end'";			
				}
	
				if(isset($params['nu']))
					$nu = $params['nu'];
				else
					$nu = "B";
				
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
											
				$html .= "<div class='report-title'>Sales Log - $filterTitle<br/>$dateRange</div>";
				
				$employeeId = $this->user->getCurrentProfileAttribute('employee_id');
				
				
				
				if($employeeId == "" && isset($params['empid']) && ($params['empid'] != 0 && $this->printing == true))
					$employeeId = $params['empid'];
				else
					if($employeeId == "")
						$employeeId = 0;
				
				$sql = "SELECT id, deal_no, stock_no, customer, nu, 
							(SELECT initials FROM info WHERE employee_id = app_rpt_desk_log.sp1 LIMIT 1) AS sp1,
							(SELECT initials FROM info WHERE employee_id = app_rpt_desk_log.sp2 LIMIT 1) AS sp2,
							(SELECT initials FROM info WHERE employee_id = app_rpt_desk_log.sm LIMIT 1) AS sm,
							(SELECT initials FROM info WHERE employee_id = app_rpt_desk_log.fi LIMIT 1) AS fi,							
							mod_no, last7vin, front, reserve, vsi, cc, gap, cl, aft, finance, total, model, trade1 + trade2 + trade3 AS trade1, trade1_acv, tg, 1perc, hb, back, date_format(dl_date, '%m/%d/%Y') AS dl_date,
							type, status, notes, other, onepercent, toyoguard, source  
						FROM app_rpt_desk_log 
						$where AND (sp1 = $employeeId OR sp2 = $employeeId) ORDER BY $sort $order";
						
				$resuts = $this->connection->exec_query($sql);
				?>
				<script>
					function sendForm()
					{
						var form = document.getElementById('desk-log');
						form.submit();
					}
				</script>
				<?
			
				$sql = "SELECT SUM(total) AS total FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND status = 'B' AND (sp1 = $employeeId OR sp2 = $employeeId)" ;
				$backout = $this->connection->get_value($sql);
				
				$sql = "SELECT COUNT(id) AS backoutCount FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND status = 'B' AND (sp1 = $employeeId OR sp2 = $employeeId)";
				$backoutCount = $this->connection->get_value($sql);
				
				$sql = "SELECT SUM(trade1 + trade2 + trade3) AS trades FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND (trade1 = 1 OR trade2 = 1 OR trade3 = 1) AND status <> 'B' AND (sp1 = $employeeId OR sp2 = $employeeId)";
				$trade = $this->connection->get_value($sql);
				
				$sql = "SELECT COUNT(id) FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND type = 'L' AND status <> 'B' AND (sp1 = $employeeId OR sp2 = $employeeId)";
				$leased = $this->connection->get_value($sql);
				
				$sql = "SELECT SUM(new) AS new, SUM(used) AS used, SUM(units) AS units, SUM(gross_front) AS front, SUM(gross_back) AS back, SUM(showroom) AS showroom, SUM(sold_sh) AS sold_sh, SUM(iphone) AS iphone, SUM(sold_ip) AS sold_ip, SUM(ileads) AS ileads, SUM(sold_il) AS sold_il FROM app_rpt_sales_department WHERE dl_date BETWEEN '$start' AND '$end' AND (employee_id = $employeeId)";
				$salesRow = $this->connection->get_row($sql);
				
				$sql = "SELECT SUM(IF(sp2 = 0, toyoguard, toyoguard / 2)) AS toyoguard, SUM(IF(sp2 = 0, onepercent, onepercent / 2)) AS onepercent FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND status <> 'B' AND (sp1 = $employeeId OR sp2 = $employeeId)";
				$desklogRow = $this->connection->get_row($sql);
				
				$sql = "SELECT COUNT(id) FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND certified = 'Yes' AND status <> 'B' AND (sp1 = $employeeId OR sp2 = $employeeId)";
				$certified = $this->connection->get_value($sql);
				
				$sql = "SELECT COUNT(id) FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND status = 'R' AND (sp1 = $employeeId OR sp2 = $employeeId)";
				$rdr = $this->connection->get_value($sql);
				
				//$sql = "SELECT COUNT(id) AS countTotal FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND (nu = 'N' OR (nu = 'U' AND (make = 'TOYOTA' OR make = 'SCION') AND certified = 'Y')) AND status <> 'B'";
				$sql = "SELECT COUNT(id) AS countTotal FROM app_rpt_desk_log WHERE dl_date BETWEEN '$start' AND '$end' AND (nu = 'N' OR (nu = 'U' AND certified = 'Yes')) AND status <> 'B' AND (sp1 = $employeeId OR sp2 = $employeeId)";			
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
						$html .= 	'<table width=100% border=0 cellmargin=0 cellpadding=0 cellspacing=0 class="desk-log-table">
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
	
					$selectionBar = 
						'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=saleslog_assoc">
							<div id="filter" class="" style="text-align:center;">
								<input id=desklog-sort'.$this->appId.' type=hidden name=sort value="'.$sort.'" />
								<input id=desklog-order'.$this->appId.' type=hidden name=order value="'.$order.'" />
								<input id=desklog-option'.$this->appId.' type=hidden name=option value="'.$option.'" />

								<input type=submit name="option" class="desklog-dealer-button" value="Today" />&nbsp
								<input id="dealer-view-button'.$this->appId.'" class="desklog-dealer-options" type="button" value="Month" onclick="showDealerView('.$this->appId.')" />&nbsp
								<input id="date-range-button'.$this->appId.'" class="desklog-dealer-options" type="button" value="Date Range" onclick="showDateRange('.$this->appId.')" />&nbsp
								<input id="desklog-filter-button'.$this->appId.'" class="desklog-dealer-options" type="button" value="N/U" onclick="showDesklogFilter('.$this->appId.')" />
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
									<div id="desklog-filter'.$this->appId.'" style="display:none;"">
										<br/><label style="color:#3B5998;font-family:lucida grande,tahoma,verdana,arial,sans-serif;font-size:11px;"><strong>N/U:</strong></label>
										<select name="nu" class="desk-log-models" id="desk-log-nu-filter'.$this->appId.'">
											<option value="B" '.($params['nu'] == 'B'? 'selected' : '').'>Both</option>
											<option value="N" '.($params['nu'] == 'N'? 'selected' : '').'>N</option>
											<option value="U" '.($params['nu'] == 'U'? 'selected' : '').'>U</option>											
										</select>
										<br/><br/>
										<input type="button" class="button-link" value="View" onclick=submitOptionsForm('.$this->appId.') />&nbsp;
										<input type="button" class="button-link" onclick="closeFilter('.$this->appId.')" value="Close" />
									</div>
								</div>
							 </div>
						</form>';
			 		
						$html .= $selectionBar."<br/>";
					}
					
					$html .= '<table width=100% border=0 cellmargin=0 cellpadding=0 cellspacing=0 class="desk-log-table">					
					<tr>
						<td class="desk-log-separator-header" colspan=24>&nbsp;</td>						
					</tr>';
					
					$html .= "<tr>";
					
						if($this->printing == false)
							$html .= "<th colspan=2 class='desk-log-header'>Actions</th>";

						$printOrder = $order;
											
						if($order == "ASC")
							$order = "DESC";
						else
							if($order == "DESC")
								$order = "ASC";
						
						$link = "app=saleslog_assoc";	
						
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
								
							$html .= '<div class="report-tool-bar" style="text-align:right;"><img src="/images/resources/printButton.gif" onclick=popUp("/gbSuite/apps/process_application.php?'.$link.'&action=print_report&sort='.$auxSort.'&empid='.$employeeId.'&order='.$printOrder.'"); /></div>';
						}
						
						$link .= '&order='.$order;
						
						$link = "/gbSuite/home.php?".$link;
							 			
						/*headers*/
						foreach($this->fields as $key => $value)
						{
							if($key == "mod_no")
								$html .= '<th class="desk-log-header"><a href="'.$link.'&sort='.$key.'">Model No</a></th>';
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
								//$html .= "<td class='desk-log-cell'><a href='/gbSuite/home.php?app=sales_log&uid".$this->user->getUID()."&action=edit&id=".$row['id']."'><img src='/images/resources/edit.gif' /></a></td>";
								$html .= "<td class='desk-log-cell'><a href='".str_replace("saleslog_assoc", "sales_log", $link)."&uid".$this->user->getUID()."&action=edit&id=".$row['id']."'><img src='/images/resources/edit.gif' /></a></td>";
								$html .= "<td class='desk-log-cell'><a href='/gbSuite/home.php?app=sales_log&uid".$this->user->getUID()."&action=delete&id=".$row['id']."&confirm=true'><img src='/images/resources/delete.gif' /></a></td>";	
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
								{
									if($field == 'front' && $row['sp2'] != "")
										$html .= "<td style='color:".$color."' align='".$align[$i++]."' class='desk-log-cell'>".$this->formatNumber($row[$field] / 2, $format[$i])."</td>";
									else
										if($field == 'back' && $row['sp2'] != "")
											$html .= "<td style='color:".$color."' align='".$align[$i++]."' class='desk-log-cell'>".$this->formatNumber($row[$field] / 2, $format[$i])."</td>";
										else
											if($field == 'other' && $row['sp2'] != "")
												$html .= "<td style='color:".$color."' align='".$align[$i++]."' class='desk-log-cell'>".$this->formatNumber($row[$field] / 2, $format[$i])."</td>";
											else
												if($field == 'total' && $row['sp2'] != "")											
												{
													$auxTotal = $row['front'] /2 + $row['back'] /2 + $row['other'] /2;
													$html .= "<td style='color:".$color."' align='".$align[$i++]."' class='desk-log-cell'>".$this->formatNumber($auxTotal, $format[$i])."</td>";
												}	
												else
													$html .= "<td style='color:".$color."' align='".$align[$i++]."' class='desk-log-cell'>".$this->formatNumber($row[$field], $format[$i])."</td>";
								}									
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
									$front += ($row['sp2'] != "") ? $row[$field] / 2 : $row[$field];
								
								if($field == 'other' && $row['status'] != 'B')
									$other += ($row['sp2'] != "") ? $row[$field] / 2 : $row[$field];
									
								if($field == 'back')
								{
									if($row['status'] != 'B')
										$back += ($row['sp2'] != "") ? $row[$field] / 2 : $row[$field];										
										
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
								$html .= "<th colspan=2 class='desk-log-header'>Actions</th>";
															
							/*headers*/
							foreach($this->fields as $key => $value)
							{
								if($key == "mod_no")
									$html .= '<th class="desk-log-header"><a href="'.$link.'&sort='.$key.'">Model No</a></th>';
								else
						 			$html .= '<th class="desk-log-header"><a href="'.$link.'&sort='.$key.'">'. $value .'</a></th>';
															
								if($key == 'back')
									$html .= "<th class='desk-log-header'>&nbsp;</th>";								
							}
						}						
						
						$html .= "<tr><th class='desk-log-total-row' colspan=".($this->printing == false? 2 : 1).">Totals</th>";
						$html .= "<td class='desk-log-total-row' align=center>".$totalCount."</td>";
						$html .= "<td class='desk-log-total-row' colspan=".($this->printing == false? 9 : 8)."></td>";
						$html .= "<td class='desk-log-total-row' align=right>".$this->formatNumber($front, "$")."</td>";
						$html .= "<td class='desk-log-total-row' align=right>".$this->formatNumber($other, "$")."</td>";
						$html .= "<td class='desk-log-total-row' align=right>".$this->formatNumber($back, "$")."</td>";
						$html .= "<td class='desk-log-total-row' align=right>&nbsp;</td>";
						$html .= "<td class='desk-log-total-row' align=right>".$this->formatNumber($front + $other + $back, "$")."</td>";							
						$html .= "<td class='desk-log-total-row' align=center>".$tradeCount."</td>";
						$html .= "<td class='desk-log-total-row' align=right></td>";
						$html .= "<td class='desk-log-total-row' align=right></td>";
						$html .= "<td class='desk-log-total-row' align=right></td>";
						$html .= "<td class='desk-log-total-row' align=right></td>";
						$html .= "<td class='desk-log-total-row' align=right></td>";
						$html .= "</tr>";					
								
				$html .= "</table>";								
				$html .= "</div>";
				
				echo $html;
			}
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