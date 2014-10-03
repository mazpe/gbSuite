<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class DeskLog extends Application
	{
		var $fields;
		var $printHtml = false;
		
		public function __construct()
		{
			$this->appId = 37;
			
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
				/*'reserve'=>'Reserve',
				'vsi'=>'VSI',
				'cc'=>'CC',	
				'gap'=>'GAP',	
				'cl'=>'C/L',
				'aft'=>'AFT',
				'finance'=>'Finance',*/
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
		
		public function renderHTML()
		{
			$sort = "dl_date DESC, id";
			$order = "DESC";
			$from = "";
			$to = "";
			$nu = "";
			$dateRange = "";
					
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
							
							$dateRange = $row['from']." - ".$row['to']; 
							$where = "WHERE dl_date >= '$start' AND dl_date <= '$end' ";
						}	
					}	
				}
				else						
				{
					$where = "WHERE MONTH(dl_date) = MONTH(now()) AND DAY(dl_date) = ".date("d");
					
					$date = date("m")."/".date("d")."/".date("Y");
					
					$from = $date;
					$to = $date;
					
					$dateRange = $date." - ".$date;					
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
				 		if($nu == 'B')
							$filterTitle .= "New and Used Vehicle Sales";
											
				$html .= "<div class='report-title'>Sales Log - $filterTitle<br/>$dateRange</div>";
				
				//$sql = "select * from app_rpt_desk_log order by id DESC";
				$sql = "SELECT id, deal_no, stock_no, customer, nu, 
							(SELECT initials FROM info WHERE employee_id = app_rpt_desk_log.sp1 LIMIT 1) AS sp1,
							(SELECT initials FROM info WHERE employee_id = app_rpt_desk_log.sp2 LIMIT 1) AS sp2,
							(SELECT initials FROM info WHERE employee_id = app_rpt_desk_log.sm LIMIT 1) AS sm,
							(SELECT initials FROM info WHERE employee_id = app_rpt_desk_log.fi LIMIT 1) AS fi,							
							mod_no, last7vin, front, reserve, vsi, cc, gap, cl, aft, finance, total, model, trade1, trade1_acv, tg, 1perc, hb, back, date_format(dl_date, '%m/%d/%Y') AS dl_date,
							type, status, notes, other, onepercent, toyoguard, source  
						FROM app_rpt_desk_log 
						$where ORDER BY $sort $order";

				$resuts = $this->connection->exec_query($sql);
				
				$sql = 'select model from models order by model';
				$models = $this->connection->fillSelect($sql,'model','model', null, false);
				
				$sql = "select initials, employee_id, name from info WHERE (title = 'Salesperson' OR name = 'House') AND employee_id IS NOT NULL ORDER BY initials";
				$sp1 = $this->connection->fillDesklogSelect($sql,'employee_id','name', 'initials', null, false);
				
				//$sql = "select initials, employee_id from info WHERE title = 'Salesperson' AND employee_id IS NOT NULL ORDER BY initials";
				$sp2 = $this->connection->fillDesklogSelect($sql,'employee_id', 'name', 'initials', null, true);
				
				$sql = "select initials, employee_id, name from info WHERE (title = 'Sales Manager' OR title = 'General Manager' OR title = 'General Sales Manager') AND employee_id IS NOT NULL ORDER BY initials";
				$sm = $this->connection->fillDesklogSelect($sql,'employee_id','name', 'initials', null, false);
				
				$sql = "select initials, employee_id, name from info WHERE (title = 'Finance Manager' OR name = 'House') AND employee_id IS NOT NULL ORDER BY initials";
				
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
			
				//$html .= '<a href="/gbSuite/apps/process_application.php?app=desk_log&action=update&uid='.$this->user->getUID().'&redirect=desk_log">Update All</a>';	
				$html .= '<form method=post action="/gbSuite/apps/process_application.php?app=desk_log&action=add&uid='.$this->user->getUID().'&redirect=desk_log" id="desk-log">';
				$html .= '<table width=100% border=0 cellmargin=0 cellpadding=0 cellspacing=0 class="desk-log-table">
							<tr>';
												
								/*headers*/
								foreach($this->fields as $key => $value)
								{
									if($key != 'total' && $key != 'sp2' && $key != 'sm' && $key != 'fi' && $key != 'other')
									{
										if($key == "sp1")
											$html .= "<th class='desk-log-header'>Sales</th>";
										else
											if($key == 'mod_no')
												$html .= "<th class='desk-log-header'>Vehicle</th>";
											else
											if($key == 'trade1')
												$html .= "<th class='desk-log-header'>Trade</th>";
											else	
											if($key == 'status')
												$html .= "<th class='desk-log-header'>Status</th>";
											else
											if($key == 'notes')
												$html .= "<th class='desk-log-header'>Notes</th>";
											else
											if($key == 'type')
												$html .= "<th class='desk-log-header'>Type</th>";
											else
											if($key == 'source')
												$html .= "<th class='desk-log-header'>Source</th>";
											else
								 				$html .= "<th class='desk-log-header'>". $value ."</th>";								 				
									}													
								}
					
				$html .= 	'</tr>
							 <tr>';
								
							/*form*/
							foreach($this->fields as $key => $value)
							{
								if($key != 'total')
								{	
									if($key != 'sp2' && $key != 'sm' && $key != 'fi' && $key != 'other')
										$html .= '<td class="desk-log-cell-form">';									
									
									if($key == 'mod_no')
									{
										$html .= '<input class="desk-log-input" type="button" id="desk-log-vehicle-button" value="ADD" onclick="showVehicle()" />';
									}
									else
										if($key == 'nu')
										{
											$html .= 	'<select name="desk_log_nu" class="desk-log-models" id="desk-log-nu" onchange=changeVehicleNU()>
															<option value="N">N</option>
															<option value="U">U</option>
														</select>';
										}
										else										
											if($key == 'status')
											{
												$html .= 	'<select name="desk_log_status" class="desk-log-models">
																<option value="S">Sold</option>
																<option value="R">RDR</option>
																<option value="B">Backout</option>
																<option value="A">Accounting</option>
																<option value="M">Money Received</option>
															</select>';
											}
											else
												if($key == 'type')
												{
													$html .= 	'<select name="desk_log_type" class="desk-log-models">
																	<option value="R">R</option>
																	<option value="L">L</option>
																</select>';
												}
												else
												if($key == 'source')
												{
													$html .= 	'<select name="desk_log_source" class="desk-log-models">
																	<option value="S">S</option>
																	<option value="I">I</option>
																	<option value="P">P</option>
																</select>';
												}
												else	
													if($key != 'back' && $key != 'trade1' && $key != 'front' && $key != 'sp1' && $key != 'sp2' && $key != 'sm' && $key != 'fi' && $key != 'notes' && $key != 'other' && $key != 'mod_no')
													{
														if($key != "dl_date")
															$html .= "<div><input class='desk-log-input' type=input name='desk_log_". $key ."' />";
														else
															$html .= "<table cellpadding=0 cellspacing=0 cellmargin=0><tr><td><input style='float:left' class='desk-log-input' id='dl_date' type=input name='desk_log_". $key ."' value='".$currentDate."'/></td><td><img src='/images/resources/calendar.gif' onclick=displayDatePicker('desk_log_dl_date',false,'mdy','/'); style='float:left' /></td></tr></table>";
													}										
																									
																	
									if($key == 'front')
										$html .= '<input class="desk-log-input" type="button" id="desk-log-front-button" value="0" onclick="showFront()" />';
									else
									if($key == 'back')
										$html .= '<input class="desk-log-input" type="button" id="desk-log-back-button" value="0" onclick="showBack()" />';
									else
										if($key == 'trade1')
											$html .= '<input class="desk-log-input" type="button" id="desk-log-trade-button" value="N" onclick="showTrade()" />';
										else
											if($key == 'sp1')
												$html .= '<input class="desk-log-input" type="button" id="desk-log-sales-button" value="View" onclick="showSales()" />';
											else
												if($key == 'notes')
													$html .= '<input class="desk-log-input" type="button" id="desk-log-notes-button" value="View" onclick="showNotes()" />';
												
													if($key != 'sp2' && $key != 'sm' && $key != 'fi')																									
														$html .= "</td>";
								}
							}
										
					$html .= '<td class="desk-log-cell-form"><a onclick="sendForm()"><img src="/images/resources/save.gif" /></a></td>
										
					</tr>
					<tr>
						<td colspan=19>
							<div class="sbox-window" style="display:none;" id="sbox-window">								
								<div class="sbox-content-iframe" id="sbox-content">
									<div id="back-sbox-content" style="display:none;">
										<table class="sand-box-table" cellspacing=0 border=0>
											<tr><td><label for="desk-log-reserve">Reserve:</label></td><td><input class="sand-box-input" id="desk-log-reserve" name="desk_log_reserve" onkeypress="onkeypress(event)" type="text" value="0" /></td>	    						
											<td><label for="desk-log-vsi">VSI:</label></td><td><input class="sand-box-input" id="desk-log-vsi" name="desk_log_vsi" type="text" onkeypress="onkeypress(event)" value="0" /></td>
											<td><label for="desk-log-cc">CC:</label></td><td><input class="sand-box-input" id="desk-log-cc" name="desk_log_cc" type="text" onkeypress="onkeypress(event)" value="0" /></td>
											<td><label for="desk-log-gap">GAP:</label></td><td><input class="sand-box-input" id="desk-log-gap" name="desk_log_gap" type="text" onkeypress="onkeypress(event)" value="0" /></td>
											<td><label for="desk-log-cl">CL:</label></td><td><input class="sand-box-input" id="desk-log-cl" name="desk_log_cl" type="text" onkeypress="onkeypress(event)" value="0" /></td>
											<td><label for="desk-log-aft">AFT:</label></td><td><input class="sand-box-input" id="desk-log-aft" name="desk_log_aft" type="text" onkeypress="onkeypress(event)" value="0" /></td></tr>
										</table>
										<input id="desk-log-back-input" name="desk_log_back" type="hidden" value="0" /></br>										
									</div>
									<div id="front-sbox-content" style="display:none;">
										<table class="sand-box-table" cellspacing=0 border=0>											
											<tr>
												<td><label for="desk-log-front">Front Gross:</label>&nbsp;<input class="sand-box-input" id="desk-log-front" name="desk_log_front" type="text" onkeypress="onkeypress(event)" value="0" /></td>									
											</tr>
											<tr id="desk-log-other-label">
												<td colspan=8><label>Other:</label></td>
											</tr>
											<tr id="desk-log-front-other">												    						
												<td><label for="desk-log-holdback">Holdback:</label></td><td><input class="sand-box-input" id="desk-log-holdback" name="desk_log_holdback" type="text" onkeyup="updateOnePercent()" onkeypress="onkeypress(event)" value="0" /></td>												
												<td><label for="desk-log-onepercent">1%:</label></td><td><input class="sand-box-input" id="desk-log-onepercent-visible" disabled="true" type="text" onkeypress="onkeypress(event)" value="0" /></td>
												<td><label for="desk-log-toyoguard">Toyoguard:</label></td><td><select id="desk-log-toyoguard" class="desk-log-models" name="desk_log_toyoguard" onchange="updateOnePercent()"><option value="250">Y</option><option value="0" selected>N</option></select></td>
												<td><label for="desk-log-total-other">Total Other:</label></td><td><input class="sand-box-input" id="desk-log-total-other" disabled="true" type="text" onkeypress="onkeypress(event)" value="0" /></td>
											</tr>
										</table>
										<input id="desk-log-onepercent" name="desk_log_onepercent" type="hidden" value="0" /></br>
										<input id="desk-log-other-input" name="desk_log_other" type="hidden" value="0" /></br>																				
									</div>
									<div id="trade-sbox-content" style="display:none;">
										<table class="sand-box-table" cellspacing=0 border=0>
											<tr><td><label for="desk-log-trade1-make">Make:</label></td><td><input class="sand-box-input" id="desk-log-trade1-make" name="desk_log_trade1_make" type="text" onkeypress="onkeypress(event)" value="" /></td>	    						
											<td><label for="desk-log-trade1-model">Model:</label></td><td><input class="sand-box-input" id="desk-log-trade1-model" name="desk_log_trade1_model" onkeypress="onkeypress(event)" value=""></td>
											<td><label for="desk-log-trade1-year">Year:</label></td><td><input class="sand-box-input" id="desk-log-trade1-year" name="desk_log_trade1_year" type="text" onkeypress="onkeypress(event)" value="" /></td>
											<td><label for="desk-log-trade1-acv">ACV:</label></td><td><input class="sand-box-input" id="desk-log-trade1-acv" name="desk_log_trade1_acv" type="text" onkeypress="onkeypress(event)" value="0" /></td></tr>											
										</table>										
										<input id="desk-log-trade-input" name="desk_log_trade1" type="hidden" value="N" />
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
												<td><label for="desk-log-veh-make">Make:</label></td><td><input class="sand-box-input" id="desk-log-veh-make" name="desk_log_make" type="text" onkeypress="onkeypress(event)" value="" /></td>	    						
												<td><label for="desk-log-veh-model">Model:</label></td><td><input class="sand-box-input" id="desk-log-veh-model" name="desk_log_model" onkeypress="onkeypress(event)" value=""></td>
												<td><label for="desk-log-veh-year">Year:</label></td><td><input class="sand-box-input" id="desk-log-veh-year" name="desk_log_year" type="text" onkeypress="onkeypress(event)" value="" /></td>
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
										<table class="sand-box-table" style="width:100%;" cellspacing=0 border=0>
											<tr><td width="10%"><label for="desk-log-notes">Notes:</label></td>
											<td width="90%"><textarea id="desk-log-notes" name="desk_log_notes" style="width:80%;"></textarea></td></tr>
										</table>																				
									</div>
									<input type="button" class="button-link" value="Save" onclick="saveSandBox()" />&nbsp;
									<input id="sbox-btn-close" type="button" class="button-link" onclick="closeSandBox()" value="Close" />									
								</div>								
							</div>
						</td>
					</tr>
					</table>
					</form>';
					
					$selectionBar = 
						'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=desk_log">
							<div id="filter" class="" style="text-align:center;">
								<input id=desklog-sort'.$this->appId.' type=hidden name=sort value="'.$sort.'" />
								<input id=desklog-order'.$this->appId.' type=hidden name=order value="'.$order.'" />
								<input id=desklog-option'.$this->appId.' type=hidden name=option value="'.$option.'" />
								
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
										<br/><label>N/U:</label>
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
			 
					$sql = "SELECT SUM(total) AS total FROM app_rpt_desk_log WHERE status = 'B'";
					$backout = $this->connection->get_value($sql);
					
					$sql = "SELECT COUNT(id) FROM app_rpt_desk_log WHERE trade1 = 'Y' AND status <> 'B'";
					$trade = $this->connection->get_value($sql);
					
					$sql = "SELECT COUNT(id) FROM app_rpt_desk_log WHERE type = 'L' AND status <> 'B'";
					$leased = $this->connection->get_value($sql);
					
					$sql = "SELECT SUM(new) AS new, SUM(used) AS used, SUM(units) AS units, SUM(gross_front) AS front, SUM(gross_back) AS back, SUM(showroom) AS showroom, SUM(sold_sh) AS sold_sh, SUM(iphone) AS iphone, SUM(sold_ip) AS sold_ip, SUM(ileads) AS ileads, SUM(sold_il) AS sold_il FROM app_rpt_sales_department";
					$salesRow = $this->connection->get_row($sql);
					
					$sql = "SELECT SUM(toyoguard) AS toyoguard, SUM(onepercent) AS onepercent FROM app_rpt_desk_log WHERE status <> 'B'";
					$desklogRow = $this->connection->get_row($sql);
					
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
					$html .= 	'<table width=100% border=0 cellmargin=0 cellpadding=0 cellspacing=0 class="desk-log-table">
									<tr>
										<td class="desk-log-summary-label">New:</td><td>'.$this->formatNumber($new, ".").'</td><td class="desk-log-summary-label">Used:</td><td>'.$this->formatNumber($used, ".").'</td><td class="desk-log-summary-label">Total Units:</td><td>'.$this->formatNumber($units, ".").'</td><td class="desk-log-summary-label">Trade-Ins:</td><td>'.$this->formatNumber($trade, "0").'</td><td class="desk-log-summary-label">Leased:</td><td>'.$this->formatNumber($leased, "0").'</td>
									</tr>
									<tr>
										<td class="desk-log-summary-label">Front:</td><td>'.$this->formatNumber($front, "$").'</td><td class="desk-log-summary-label">Toyoguard:</td><td>'.$this->formatNumber($toyoguard, "$").'</td><td class="desk-log-summary-label">1%:</td><td>'.$this->formatNumber($onepercent, "$").'</td><td class="desk-log-summary-label">Back:</td><td>'.$this->formatNumber($back, "$").'</td><td class="desk-log-summary-label">MTD AVG:</td><td>'.$this->formatNumber($mtdavg, "$").'</td>
									</tr>
									<tr>
										<td class="desk-log-summary-label">Showroom:</td><td>'.$this->formatNumber($soldSH, "0").'</td><td class="desk-log-summary-label">Phone:</td><td>'.$this->formatNumber($soldIP, "0").'</td><td class="desk-log-summary-label">Internet:</td><td>'.$this->formatNumber($soldIL, "0").'</td><td class="desk-log-summary-label">Backouts:</td><td>'.$this->formatNumber($backout, "$").'</td><td></td>
									</tr>
								</table>';
					
					$html .= "<br/>".$selectionBar;
					
					$html .= '<table width=100% border=0 cellmargin=0 cellpadding=0 cellspacing=0 class="desk-log-table">					
					<tr>
						<td class="desk-log-separator-header" colspan=24>&nbsp;</td>						
					</tr>';
					
						$html .= "<tr><th colspan=2 class='desk-log-header'>Actions</th>";
					
						if($order == "ASC")
							$order = "DESC";
						else
							if($order == "DESC")
								$order = "ASC";
							
						$link = '/gbSuite/home.php?app=desk_log&order='.$order;	
						
						if(isset($params['option']))
							$link .= '&option='.$params['option'];
						
						$link .= '&from'.$this->appId.'='.$from.'&to'.$this->appId.'='.$to;
						
						if($nu != 'B')
							$link .= '&nu='.$nu;
							 			
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
							
							$html .= "<td class='desk-log-cell'><a href='/gbSuite/home.php?app=desk_log&uid".$this->user->getUID()."&action=edit&id=".$row['id']."'><img src='/images/resources/edit.gif' /></a></td>";
							$html .= "<td class='desk-log-cell'><a href='/gbSuite/home.php?app=desk_log&uid".$this->user->getUID()."&action=delete&id=".$row['id']."&confirm=true'><img src='/images/resources/delete.gif' /></a></td>";
							
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
								{
									if($row[$field] == 'Y')
										++$tradeCount;
								}
												
							}
							
							$html .= "</tr>";
							
							++$count;
							
							if($row['status'] != 'B')
								++$totalCount;
						}
						
						if($count > 0)
						{
							$html .= "<tr><th colspan=2 class='desk-log-header'>Actions</th>";
															
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
						
						$html .= "<tr><th class='desk-log-total-row' colspan=2>Totals</th>";
						$html .= "<td class='desk-log-total-row' align=center>".$totalCount."</td>";
						$html .= "<td class='desk-log-total-row' colspan=9></td>";
						$html .= "<td class='desk-log-total-row' align=right>".$this->formatNumber($front, "$")."</td>";
						$html .= "<td class='desk-log-total-row' align=right>".$this->formatNumber($other, "$")."</td>";
						$html .= "<td class='desk-log-total-row' align=right>".$this->formatNumber($back, "$")."</td>";
						$html .= "<td class='desk-log-total-row' align=right>&nbsp;</td>";
						$html .= "<td class='desk-log-total-row' align=right>".$this->formatNumber($total, "$")."</td>";							
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
					$html .= '<div><a class="button-link" href="/gbSuite/apps/process_application.php?app=desk_log&uid='.$this->user->getUID().'&action=delete&id='.$params['id'].'&redirect=desk_log" >Yes</a>&nbsp;<a class="button-link" href="/gbSuite/home.php?app=desk_log">Go back</a></div>';
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
							$values = array();
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
			
				//$count = $this->connection->get_value($query);
								
				$mtdAVG = ($units > 0 ? (($grossFront + $grossBack)/$units) : 0);
				
				$mtdAVGOther = ($units > 0 ? (($grossFront + $grossBack + $other)/$units) : 0);
				
				
				//Sales department		
				/*if($count == 0)
				{*/
					
					$query = "INSERT INTO app_rpt_sales_department (employee_id, sales_person_1, units, track, new, used, gross_front, 
																gross_back, gross_total, mtd_avg, showroom, sold_sh, `close_sh%`, iphone, sold_ip, `close_ip%`, ileads, sold_il, `close_il%`, dl_date)
								SELECT employee_id, name, $units, $track, ".($new).", ".($used).", ".$grossFront.", ".$grossBack.", ".($grossFront + $grossBack).", ".$mtdAVG.", 0, $soldSH, 0, 0, $soldIP, 0, 0, $soldIL, 0, '$date'   
									FROM info 
									WHERE employee_id = ".$employeeId;
				/*}
				else
				{
					$sql = "SELECT showroom, iphone, ileads FROM app_rpt_sales_department WHERE employee_id = $employeeId AND dl_date = '$date'";
					$reportRow = $this->connection->get_row($sql);
					
					$showroom = $reportRow['showroom'];
					$iphone = $reportRow['iphone'];
					$ileads = $reportRow['ileads'];
					
					$iphone = ($iphone != "" ? (is_numeric($iphone) ? $iphone : 0) : 0);					
					$showroom = ($showroom != ""? (is_numeric($showroom) ? $showroom : 0) : 0);					
					$ileads = ($ileads != ""? (is_numeric($ileads) ? $ileads : 0) : 0);
					
					//Showroom
					$closeSH = "`close_sh%` = 0";
					
					if($showroom > 0)
						$closeSH = "`close_sh%` = ($soldSH * 100 / $showroom)";
						
					//Iphone
					$closeIP = "`close_ip%` = 0";
					
					if($iphone > 0)
						$closeIP = "`close_ip%` = ($soldIP * 100 / $iphone)";
							
					//Ileads
					$closeIL = "`close_il%` = 0";
					
					if($ileads > 0)
						$closeIL = "`close_il%` = ($soldIL * 100 / $ileads)";

					$query = "UPDATE app_rpt_sales_department SET units = $units, track = $track, `new` = $new, used = $used, gross_front = $grossFront, 
															  gross_back = $grossBack, gross_total = ".($grossFront + $grossBack).", mtd_avg = ".$mtdAVG.",
															  showroom = $showroom, sold_sh = $soldSH, $closeSH, iphone = $iphone, sold_ip = $soldIP, $closeIP, ileads = $ileads, sold_il = $soldIL, $closeIL    						
								WHERE employee_id = $employeeId AND dl_date = '$date'";					
				}*/					 
				
				$this->connection->exec_query($query);
				
				//Gross profit analysis
				//Verify if the sales person exists in app_rpt_gross_profit_analysis
				//$query = "SELECT COUNT(employee_id) AS recordCount FROM app_rpt_gross_profit_analisys WHERE employee_id = ".$employeeId;
			
				//$count = $this->connection->get_value($query);

				$totalUnits = $grossPA["`(999)+`"] + $grossPA["`(998-500)`"] + $grossPA["`(499-0)`"] + $grossPA["`1-499`"] + $grossPA["`500-1499`"] + $grossPA["`1500+`"];
				
				$percentages = array("`(999)+%`" => 0, "`(998-500)%`" => 0, "`(499-0)%`" => 0, "`1-499%`" => 0, "`500-1499%`" => 0, "`1500+%`" => 0);
				$percentages["`(999)+%`"] = ($totalUnits > 0 ? (($grossPA["`(999)+`"] * 100)/$totalUnits) : 0);
				$percentages["`(998-500)%`"] = ($totalUnits > 0 ? (($grossPA["`(998-500)`"] * 100)/$totalUnits) : 0);
				$percentages["`(499-0)%`"] = ($totalUnits > 0 ? (($grossPA["`(499-0)`"] * 100)/$totalUnits) : 0);
				$percentages["`1-499%`"] = ($totalUnits > 0 ? (($grossPA["`1-499`"] * 100)/$totalUnits) : 0);
			    $percentages["`500-1499%`"] = ($totalUnits > 0 ? (($grossPA["`500-1499`"] * 100)/$totalUnits) : 0);
				$percentages["`1500+%`"] = ($totalUnits > 0 ? (($grossPA["`1500+`"] * 100)/$totalUnits) : 0);
			    
				/*if($count == 0)
				{*/
					$query = "INSERT INTO app_rpt_gross_profit_analisys (employee_id, sales_person_1, `(999)+`, `(998-500)`, `(499-0)`, `1-499`, `500-1499`, `1500+`, `(999)+%`, `(998-500)%`, `(499-0)%`, `1-499%`, `500-1499%`, `1500+%`, avgfront, avgback, mtdavg, dl_date)
								SELECT employee_id, name, ".$grossPA["`(999)+`"].", ".$grossPA["`(998-500)`"].", ".$grossPA["`(499-0)`"].", ".$grossPA["`1-499`"].", ".$grossPA["`500-1499`"].", ".$grossPA["`1500+`"].",   
									".$percentages["`(999)+%`"].", ".$percentages["`(998-500)%`"].", ".$percentages["`(499-0)%`"].", ".$percentages["`1-499%`"].", ".$percentages["`500-1499%`"].", ".$percentages["`1500+%`"].", 
									$grossFront, $grossBack, ".$mtdAVG.", '$date' 
									FROM info 
									WHERE employee_id = ".$employeeId;
				/*}
				else
				{
					$query = "UPDATE app_rpt_gross_profit_analisys SET `(999)+` = ".$grossPA["`(999)+`"].", `(998-500)` = ".$grossPA["`(998-500)`"].", `(499-0)` = ".$grossPA["`(499-0)`"].", `1-499` = ".$grossPA["`1-499`"].", `500-1499` = ".$grossPA["`500-1499`"].", `1500+` = ".$grossPA["`1500+`"].", 
																	   `(999)+%` = ".$percentages["`(999)+%`"].", 
																	   `(998-500)%` = ".$percentages["`(998-500)%`"].",  
																	   `(499-0)%` = ".$percentages["`(499-0)%`"].",  
																	   `1-499%` = ".$percentages["`1-499%`"].", 
																	   `500-1499%` = ".$percentages["`500-1499%`"].", 
																	   `1500+%` = ".$percentages["`1500+%`"].",    
																	   avgfront = $grossFront, avgback = $grossBack, mtdavg = ".$mtdAVG."
								WHERE employee_id = ".$employeeId;					
				}*/					 
					
				$this->connection->exec_query($query);

				//Verify if the sales person exists in app_rpt_sales_department
				//$query = "SELECT COUNT(employee_id) AS recordCount FROM app_rpt_power_rank WHERE employee_id = ".$employeeId;
			
				//$count = $this->connection->get_value($query);
				
				//(Units * 1500) * (30%) + (Front * 20%) + (Back * 5%) + (Total * 10%) / 4

				//$totalScore = (($units * 1500) * 0.3) + ($grossFront * 0.20) + ($grossBack * 0.05) + (($grossFront + $grossBack) * 0.1) / 4;
				$totalScore = (($units * $powerRankSetup['units_multiplier']) * $powerRankSetup['units_percentage']) + ($grossFront * $powerRankSetup['front_percentage']) + ($grossBack * $powerRankSetup['back_percentage']) + (($grossFront + $grossBack) * $powerRankSetup['total_percentage']) / 4;
				 
				//Power rank
				/*if($count == 0)
				{*/
					
					$query = "INSERT INTO app_rpt_power_rank (sales_person, units_sold, us_rank, total_front_end, tfe_rank, total_back_end, tbe_rank, 
																total_gross, tg_rank, total_score, power_rank, employee_id, dl_date)
								SELECT name, $units, 0, $grossFront, 0, $grossBack, 0, ".($grossFront + $grossBack).", 0, $totalScore, 0, $employeeId, '$date'     
									FROM info 
									WHERE employee_id = ".$employeeId;
									
					$this->connection->exec_query($query);
				/*}
				else
				{				
					$query = "UPDATE app_rpt_power_rank SET units_sold = $units, total_front_end = $grossFront, total_back_end = $grossBack, total_gross = ".($grossFront + $grossBack).", 
															  total_score = $totalScore 
								WHERE employee_id = $employeeId";					
				}*/				 
				
				/*$this->connection->exec_query($query);
				
				$sql = "
							set @us_rank = 0;
							set @tfe_rank = 0;
							set @tbe_rank = 0;
							set @tg_rank = 0;
							set @power_rank = 0;

							update app_rpt_power_rank PR
							inner join (select employee_id, @us_rank := ifnull(@us_rank,0) +1 as us_rank from app_rpt_power_rank order by units_sold DESC) as T1 using(employee_id)
							inner join (select employee_id, @tfe_rank := ifnull(@tfe_rank,0) +1 as tfe_rank from app_rpt_power_rank order by total_front_end DESC) as T2 using(employee_id)
							inner join (select employee_id, @tbe_rank := ifnull(@tbe_rank,0) +1 as tbe_rank from app_rpt_power_rank order by total_back_end DESC) as T3 using(employee_id)
							inner join (select employee_id, @tg_rank := ifnull(@tg_rank,0) +1 as tg_rank from app_rpt_power_rank order by total_gross DESC) as T4 using(employee_id)
							inner join (select employee_id, @power_rank := ifnull(@power_rank,0) +1 as power_rank from app_rpt_power_rank order by total_score DESC) as T5 using(employee_id)
							set PR.us_rank = T1.us_rank , PR.tfe_rank = T2.tfe_rank , PR.tbe_rank = T3.tbe_rank, PR.tg_rank = T4.tg_rank , PR.power_rank = T5.power_rank;";
 
							 foreach(split(';',$sql) as $query)
							 { 	 	
							 	if(trim($query) <> "" )
							 	{
							 		$this->connection->exec_query($query);
							 		//echo mysql_error()."<br><br>";
							 	}	
							 }
				*/
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
			echo "<div>";
			echo "<div class='report-title'>Sales Log</div>";
					
			$sql = "select id, deal_no, stock_no, customer, sp1, sp2, sm, fi, mod_no, last7vin, front, reserve, vsi, cc, gap, cl, 
							aft, finance, total, trade1, trade1_acv, tg, 1perc, hb, back, date_format(dl_date, '%m/%d/%Y') AS dl_date, trade1_make, trade1_model, trade1_year, nu,
							type, status, notes, other, toyoguard, onepercent, source, model, year, make, holdback   
					from app_rpt_desk_log WHERE id = ".$params['id'];
					
			$resuts = $this->connection->exec_query($sql);
			$row = null;
			$row = null;
			$sp1 = null;
			$sp2 = null;
			$sm = null;
			$fi = null;
			$notes = null;
						
			?>
			<script>
			function saveDeskLogChanges()
			{
				var form = document.getElementById('desk-log');
				form.submit();
			}
			</script>
			<form method=post action='/gbSuite/apps/process_application.php?app=desk_log&redirect=desk_log&action=save&uid=<?= $this->user->getUID() ?>' id='desk-log'>
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
					echo "<input class='button-link' type='button' value='Go back' onclick=document.setLocation('/gbSuite/home.php?app=desk_log') />";
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
									$sql = "select initials, employee_id, name from info WHERE (title = 'Salesperson' OR name = 'House') AND employee_id IS NOT NULL ORDER BY initials";
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
									$sql = "select initials, employee_id, name from info WHERE (title = 'Salesperson' OR name = 'House') AND employee_id IS NOT NULL ORDER BY initials";
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
										$sql = "select initials, employee_id, name from info WHERE (title = 'Sales Manager' OR title = 'General Manager' OR title = 'General Sales Manager') AND employee_id IS NOT NULL ORDER BY initials";
										$sm = $this->connection->fillDesklogSelect($sql,'employee_id','name', 'initials', $row[$field], false);										
																			
										//echo "<td class='desk-log-cell'>";
										
										//echo "<select name='desk_log_$field' class='desk-log-models'>" . $sm.
										//	"</select>";
										
										//echo "</td>";
									}
									else
									if($field == 'fi')
									{
										$sql = "select initials, employee_id, name from info WHERE (title = 'Finance Manager' OR name = 'House') AND employee_id IS NOT NULL ORDER BY initials";
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
										<tr><td><label for="desk-log-reserve">Reserve:</label></td><td><input class="sand-box-input" id="desk-log-reserve" name="desk_log_reserve" type="text" onkeypress="onkeypress(event)" value="'.$row['reserve'].'" /></td>	    						
										<td><label for="desk-log-vsi">VSI:</label></td><td><input class="sand-box-input" id="desk-log-vsi" name="desk_log_vsi" type="text" onkeypress="onkeypress(event)" value="'.$row['vsi'].'" /></td>
										<td><label for="desk-log-cc">CC:</label></td><td><input id="desk-log-cc" class="sand-box-input" name="desk_log_cc" type="text" onkeypress="onkeypress(event)" value="'.$row['cc'].'" /></td>
										<td><label for="desk-log-gap">GAP:</label></td><td><input id="desk-log-gap" name="desk_log_gap" class="sand-box-input" type="text" onkeypress="onkeypress(event)" value="'.$row['gap'].'" /></td>
										<td><label for="desk-log-cl">CL:</label></td><td><input id="desk-log-cl" class="sand-box-input" name="desk_log_cl" type="text" onkeypress="onkeypress(event)" value="'.$row['cl'].'" /></td>
										<td><label for="desk-log-aft">AFT:</label></td><td><input id="desk-log-aft" class="sand-box-input" name="desk_log_aft" type="text" onkeypress="onkeypress(event)" value="'.$row['aft'].'" /></td></tr>
									</table>
										<input id="desk-log-back-input" name="desk_log_back" type="hidden" editable=false value="'.$row['back'].'" /></br>
								</div>
								<div id="front-sbox-content" style="display:none;">
									<table class="sand-box-table" cellspacing=0 border=0>
										<tr>
											<td><label for="desk-log-front">Front Gross:</label>&nbsp;<input class="sand-box-input" id="desk-log-front" name="desk_log_front" type="text" onkeypress="onkeypress(event)" value="'.$row['front'].'" /></td>											
										</tr>
										<tr id="desk-log-other-label">
											<td colspan=8><label>Other:</label></td>
										</tr>
										<tr id="desk-log-front-other">											    						
											<td><label for="desk-log-holdback">Holdback:</label></td><td><input class="sand-box-input" id="desk-log-holdback" name="desk_log_holdback" onkeyup="updateOnePercent()" type="text" onkeypress="onkeypress(event)" value="'.$row['holdback'].'" /></td>												
											<td><label for="desk-log-onepercent">1%:</label></td><td><input class="sand-box-input" id="desk-log-onepercent-visible" disabled="true" type="text" onkeypress="onkeypress(event)" value="'.$row['onepercent'].'" /></td>
											<td><label for="desk-log-toyoguard">Toyoguard:</label></td><td><select id="desk-log-toyoguard" class="desk-log-models" name="desk_log_toyoguard" onkeypress="onkeypress(event)" onchange="updateOnePercent()"><option value="250" '.($row['toyoguard'] == '250' ? 'selected' : '').'>Y</option><option value="0" '.($row['toyoguard'] == '0' ? 'selected' : '').'>N</option></select></td>
											<td><label for="desk-log-total-other">Total Other:</label></td><td><input class="sand-box-input" id="desk-log-total-other" disabled="true" type="text" onkeypress="onkeypress(event)" value="'.($row['onepercent'] + $row['toyoguard']).'" /></td>
										</tr>
									</table>
									<input id="desk-log-onepercent" name="desk_log_onepercent" type="hidden" value="'.$row['onepercent'].'" /></br>
									<input id="desk-log-other-input" name="desk_log_other" type="hidden" value="'.$row['other'].'" /></br>																				
								</div>
								<div id="trade-sbox-content" style="display:none;">
									<table class="sand-box-table" cellspacing=0 border=0>
										<tr><td><label for="desk-log-trade1-make">Make:</label></td><td><input id="desk-log-trade1-make" class="sand-box-input" name="desk_log_trade1_make" type="text" onkeypress="onkeypress(event)" value="'.$row['trade1_make'].'" /></td>	    						
										<td><label for="desk-log-trade1-model">Model:</label></td><td><input id="desk-log-trade1-model" class="sand-box-input" name="desk_log_trade1_model" onkeypress="onkeypress(event)" value="'.$row['trade1_model'].'"></td>
										<td><label for="desk-log-trade1-year">Year:</label></td><td><input id="desk-log-trade1-year" name="desk_log_trade1_year" class="sand-box-input" onkeypress="onkeypress(event)" type="text" value="'.$row['trade1_year'].'" /></td>
										<td><label for="desk-log-trade1-acv">ACV:</label></td><td><input id="desk-log-trade1-acv" name="desk_log_trade1_acv" class="sand-box-input" type="text" onkeypress="onkeypress(event)" value="'.$row['trade1_acv'].'" /></td></tr>
										<input id="desk-log-trade-input" name="desk_log_trade1" type="hidden" editable=false value="'.$row['trade1'].'" /></br>
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
											<td><label for="desk-log-veh-make">Make:</label></td><td><input class="sand-box-input" id="desk-log-veh-make" name="desk_log_make" onkeypress="onkeypress(event)" type="text" value="'.$row['make'].'" /></td>	    						
											<td><label for="desk-log-veh-model">Model:</label></td><td><input class="sand-box-input" id="desk-log-veh-model" name="desk_log_model" onkeypress="onkeypress(event)" value="'.$row['model'].'"></td>
											<td><label for="desk-log-veh-year">Year:</label></td><td><input class="sand-box-input" id="desk-log-veh-year" name="desk_log_year" onkeypress="onkeypress(event)" type="text" value="'.$row['year'].'" /></td>
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