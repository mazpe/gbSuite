<?php
include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/user.php");
include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/power_rank/power_rank.php");

/*
 * Created on 11/12/2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
 class FiPerformance extends Application
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
		
 	public function __construct()
 	{
 		$this->appId=59;
 	}

	public function print_report()
	{
		?>
			<link rel="stylesheet" href="/css/report_print.css" type="text/css" media="screen, print" charset="utf-8" />				
			<link rel="stylesheet" href="/css/style.css" type="text/css" media="screen, print" charset="utf-8" />
			<link rel="stylesheet" href="/css/desk_log.css" type="text/css" media="screen, print" charset="utf-8" />
			<link rel="stylesheet" href="/css/team_builder.css" type="text/css" media="screen, print" charset="utf-8" />
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
		
		$this->sort = "mtd_pvr";
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
			
		$selectionBar = 
							'<form method=post id="desklog-options-form'.$this->appId.'" action="/gbSuite/home.php?app=fi_performance">
								<div id="filter" class="" style="text-align:center;">
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
			
			$link = "/gbSuite/apps/process_application.php?app=fi_performance&action=print_report&uid=".$this->user->getUID();
													
			if(isset($this->params['option']))
				$link .= '&option='.$this->params['option'];
		
			$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
				
			$html .= '<div class="report-tool-bar" style="text-align:right;"><img src="/images/resources/printButton.png" onclick=popUp("'.$link.'"); /></div>';
		}	
 		
	    $order = "";
			
		if($this->order == "ASC")
			$order = "DESC";
		else
			if($this->order == "DESC")
				$order = "ASC";
				
		$link = '/gbSuite/home.php?app=fi_performance&order='.$order; 		
 		
 		if(isset($this->params['option']))
				$link .= '&option='.$this->params['option'];
				
			$link .= '&from'.$this->appId.'='.$this->from.'&to'.$this->appId.'='.$this->to;
			
 		echo "<div class='report-title'>Finance Manager Performance <br/> $dateRange </div>";
 		
 		echo $html;		
		 		
 		 				 	
 		?> 		
 		<table cellspacing="0" cellpadding="0" border="0" width="100%" class="desk-log-table" cellmargin="0">
 			  <tr>
 				<td class="desk-log-header" colspan = 6></td>
 				<td class="desk-log-header" colspan = 3 align = "center" style="background: #faefe3"><b>Warranty (VSI)</></td>
 				<td class="desk-log-header" colspan = 3 align = "center" style="background: #FFFFCC"><b>Car Care (CC)</></td>
 				<td class="desk-log-header" colspan = 3 align = "center" style="background: #CCFFCC"><b>Gap Insurance</></td> 	
 				<td class="desk-log-header" colspan = 3 align = "center" style="background: #ffe6fd"><b>Credit Life Insurance</></td>	
 				<td class="desk-log-header" colspan = 3 align = "center" style="background: #CCFFFF"><b>Aftermarket</></td>				
 			  </tr>
 		
 			  <tr>
 				<th class="desk-log-header"><a href="<?=$link?>&sort=name">Finance<br>Manager</th>
 				<th class="desk-log-header"><a href="<?=$link?>&sort=deals">Deals</th>
 				<th class="desk-log-header"><a href="<?=$link?>&sort=total_produced">Total<br>Produced</th>
 				<th class="desk-log-header"><a href="<?=$link?>&sort=mtd_pvr">MTD<br>PVR</th>
 				<th class="desk-log-header"><a href="<?=$link?>&sort=rank">Rank</th>
 				<th class="desk-log-header"><a href="<?=$link?>&sort=rank">Product<br>Penetration</th>
 				<th class="desk-log-header" style="background: #faefe3"><a href="<?=$link?>&sort=units_vsi">#</th>
 				<th class="desk-log-header" style="background: #faefe3"><a href="<?=$link?>&sort=amount_vsi">$</th>
 				<th class="desk-log-header" style="background: #faefe3"><a href="<?=$link?>&sort=percent_vsi">%</th>	
 				<th class="desk-log-header" style="background: #FFFFCC"><a href="<?=$link?>&sort=units_cc">#</th>
 				<th class="desk-log-header" style="background: #FFFFCC"><a href="<?=$link?>&sort=amount_cc">$</th>
 				<th class="desk-log-header" style="background: #FFFFCC"><a href="<?=$link?>&sort=percent_cc">%</th>	
 				<th class="desk-log-header" style="background: #CCFFCC"><a href="<?=$link?>&sort=units_gap">#</th>
 				<th class="desk-log-header" style="background: #CCFFCC"><a href="<?=$link?>&sort=amount_gap">$</th>
 				<th class="desk-log-header" style="background: #CCFFCC"><a href="<?=$link?>&sort=percent_gap">%</th>
 				<th class="desk-log-header" style="background: #ffe6fd"><a href="<?=$link?>&sort=units_cl">#</th>
 				<th class="desk-log-header" style="background: #ffe6fd"><a href="<?=$link?>&sort=amount_cl">$</th>
 				<th class="desk-log-header" style="background: #ffe6fd"><a href="<?=$link?>&sort=percent_cl">%</th>
 				<th class="desk-log-header" style="background: #CCFFFF"><a href="<?=$link?>&sort=units_aft">#</th>
 				<th class="desk-log-header" style="background: #CCFFFF"><a href="<?=$link?>&sort=amount_aft">$</th>
 				<th class="desk-log-header" style="background: #CCFFFF"><a href="<?=$link?>&sort=percent_aft">%</th> 				
 			  </tr> 			
 		<?
 		
		$n = 0;
		$aux = 0;
		$cont = 0;
		$prod_penetr = 0;
		$total_deals = 0;
		$total_produced = 0;
		$total_mtd = 0;
		$total_product = 0;
		
		$total_u_vsi = 0;
		$total_amount_vsi = 0;
		$total_perc_vsi = 0;
		
		$total_u_cc = 0;
		$total_amount_cc = 0;
		$total_perc_cc = 0;
		
		$total_u_gap = 0;
		$total_amount_gap = 0;
		$total_perc_gap = 0;
		
		$total_u_cl = 0;
		$total_amount_cl = 0;
		$total_perc_cl = 0;	
		
		$total_u_aft = 0;
		$total_amount_aft = 0;
		$total_perc_aft = 0;	
		
		$result = $this->getRank();		

		$time = time(); 
		$tableName = "fi_performance_tempdata_$time";
		$queryT = "CREATE TABLE $tableName(name varchar(100), deals int(10), total_produced float, mtd_pvr float, rank int(10), prod_pentr float, units_vsi int(10), amount_vsi float, percent_vsi float, units_cc int(10), amount_cc float, percent_cc float, units_gap int(10), amount_gap float, percent_gap float, units_cl int(10), amount_cl float, percent_cl float, units_aft int(10), amount_aft float, percent_aft float)";
		$this->connection->exec_query($queryT);
				 		 	
 		while($row = mysql_fetch_array($result))		
 		{ 	 				
 			$percent_vsi = 0;
 			$percent_cc = 0;
 			$percent_aft = 0;
 			$percent_cl = 0;
 			$percent_gap = 0;
 			
 			//VSI	
 			$query2 = "select count(vsi) as units, sum(vsi) as amount from app_rpt_desk_log A inner join info I on  A.fi = I.employee_id where A.status <> 'B' and A.vsi <> 0 and I.name = '".$row['name']."' AND dl_date BETWEEN '$this->dateFrom' AND '$this->dateTo'";
 			$vsi = $this->connection->get_row($query2);
 			
 			
 			if($vsi['amount'] == '')
 				$vsi['amount'] = 0;
 				
 			if($row['total_deals'] != 0)
 			   $percent_vsi = ($vsi['units'] / $row['total_deals'])*100;
 			
 			//CC
 			$query3 = "select count(cc) as units, sum(cc) as amount from app_rpt_desk_log A inner join info I on  A.fi = I.employee_id where A.status <> 'B' and A.cc <> 0 and I.name = '".$row['name']."' AND dl_date BETWEEN '$this->dateFrom' AND '$this->dateTo'";
 			$cc = $this->connection->get_row($query3);
 			
 			
 			if($cc['amount'] == '')
 				$cc['amount'] = 0;
 			
 			if($row['total_deals'] != 0)
 			   $percent_cc = ($cc['units'] / $row['total_deals'])*100;
 			   
 			//Gap
 			$query4 = "select count(gap) as units, sum(gap) as amount from app_rpt_desk_log A inner join info I on  A.fi = I.employee_id where A.status <> 'B' and A.gap <> 0 and I.name = '".$row['name']."' AND dl_date BETWEEN '$this->dateFrom' AND '$this->dateTo'";
 			$gap = $this->connection->get_row($query4);		

 			if($gap['amount'] == '')
 				$gap['amount'] = 0;
 			
 			if($row['total_deals'] != 0)
 			   $percent_gap = ($gap['units'] / $row['total_deals'])*100;
 			   
 			//CL
 			$query5 = "select count(cl) as units, sum(cl) as amount from app_rpt_desk_log A inner join info I on  A.fi = I.employee_id where A.status <> 'B' and A.cl <> 0 and I.name = '".$row['name']."' AND dl_date BETWEEN '$this->dateFrom' AND '$this->dateTo'";
 			$cl = $this->connection->get_row($query5);
 			

 			if($cl['amount'] == '')
 				$cl['amount'] = 0;
 			
 			if($row['total_deals'] != 0)
 			   $percent_cl = ($cl['units'] / $row['total_deals'])*100; 			 			
 			
 			//aft
 			$query6 = "select count(aft) as units, sum(aft) as amount from app_rpt_desk_log A inner join info I on  A.fi = I.employee_id where A.status <> 'B' and A.aft <> 0 and I.name = '".$row['name']."' AND dl_date BETWEEN '$this->dateFrom' AND '$this->dateTo'";
 			$aft = $this->connection->get_row($query6);
 			
 			if($aft['amount'] == '')
 				$aft['amount'] = 0;
 			
 			if($row['total_deals'] != 0)
 			   $percent_aft = ($aft['units'] / $row['total_deals'])*100;
 			 
 			 //Product Penetration
 			 $prod_penetr = (($vsi['units'] + $cc['units'] + $gap['units'] + $cl['units'] + $aft['units'])/$row['total_deals'])*100; 
 			 			 
 			 //
 			 $total_deals += $row['total_deals'];	
 			 $total_produced += $row['total_produced'];
 			  
 			 $total_u_vsi += $vsi['units'];
 			 $total_amount_vsi += $vsi['amount'];	
 			 
 			 $total_u_cc += $cc['units'];
 			 $total_amount_cc += $cc['amount'];	
 			 
 			 $total_u_gap += $gap['units'];
 			 $total_amount_gap += $gap['amount'];	
 			 
 			 $total_u_cl += $cl['units'];
 			 $total_amount_cl += $cl['amount'];	
 			 
 			 $total_u_aft += $aft['units'];
 			 $total_amount_aft += $aft['amount'];	
 			 
 			 $cont++;			 
			 
			 $query7 = "INSERT INTO $tableName (name, deals, total_produced, mtd_pvr, rank, prod_pentr, units_vsi, amount_vsi, percent_vsi, units_cc, amount_cc, percent_cc, units_gap, amount_gap, percent_gap, units_cl, amount_cl, percent_cl, units_aft, amount_aft, percent_aft)
			 		    VALUES('".$row['name']."', ".$row['total_deals'].", ".$row['total_produced'].", ".$row['mtd_pvr'].", ".$row['power_rank'].", $prod_penetr, ".$vsi['units'].", ".$vsi['amount'].", $percent_vsi, ".$cc['units'].", ".$cc['amount'].", $percent_cc, ".$gap['units'].", ".$gap['amount'].", $percent_gap, ".$cl['units'].", ".$cl['amount'].", ".$percent_cl.", ".$aft['units'].", ".$aft['amount'].", ".$percent_aft.")";
						 
			 $this->connection->exec_query($query7);
 		}

		if($total_deals > 0)
		{
			$total_mtd = $total_produced / $total_deals;
			$total_product = (($total_u_vsi + $total_u_cc + $total_u_gap + $total_u_cl + $total_u_aft)/$total_deals)*100;
			$total_perc_vsi = ($total_u_vsi / $total_deals) * 100;
			$total_perc_cc = ($total_u_cc / $total_deals) * 100;
			$total_perc_gap = ($total_u_gap / $total_deals) * 100;
			$total_perc_cl = ($total_u_cl / $total_deals) * 100;
			$total_perc_aft = ($total_u_aft / $total_deals) * 100;
		}
		else
		{
			$total_mtd = 0;
			$total_product = 0;
			$total_perc_vsi = 0;
			$total_perc_cc = 0;
			$total_perc_gap = 0;
			$total_perc_cl = 0;
			$total_perc_aft = 0;
		}
		
		$sql1 = "SELECT * FROM $tableName order by $this->sort $this->order";
		$r = $this->connection->exec_query($sql1);
		
		$cont = 0;		  		
		
 		while($row = mysql_fetch_array($r))
 		{
 			$cont++;
			 
			 if($cont % 2 == 0 )
			 	$class = ' bgcolor="#f2f2f2" ';
			 else
			 	$class="";
 			 			 
 			?>
 				<tr>
 					<td <?=$class?>> <?= $row['name'] ?> </td>
 					<td <?=$class?> align = "center"> <?= $row['deals'] ?> </td>
 					<td <?=$class?> align = "right"> <?= $this->formatNumber($row['total_produced'], "$") ?> </td>	
 					<td <?=$class?> align = "right"> <?= $this->formatNumber($row['mtd_pvr'], "$") ?> </td>
 					<td <?=$class?> align = "center"> <?= $row['rank'] ?></td>
 					<td <?=$class?> align=center> <?= $this->formatNumber($row['prod_pentr'], "%") ?> </td>
 					<td <?=$class?> align = "center" style="background: #faefe3"> <?= $row['units_vsi'] ?> </td>
 					<td <?=$class?> align = "right" style="background: #faefe3"> <?= $this->formatNumber($row['amount_vsi'], "$") ?> </td>
 					<td <?=$class?> align=center style="background: #faefe3"> <?= $this->formatNumber($row['percent_vsi'], "%") ?> </td>
 					<td <?=$class?> align = "center" style="background: #FFFFCC"> <?= $row['units_cc'] ?> </td>
 					<td <?=$class?> align = "right" style="background: #FFFFCC"> <?= $this->formatNumber($row['amount_cc'], "$") ?> </td>
 					<td <?=$class?> align=center style="background: #FFFFCC"> <?= $this->formatNumber($row['percent_cc'], "%") ?> </td> 	
 					<td <?=$class?> align = "center" style="background: #CCFFCC"> <?= $row['units_gap'] ?> </td>
 					<td <?=$class?> align = "right" style="background: #CCFFCC"> <?= $this->formatNumber($row['amount_gap'], "$") ?> </td>
 					<td <?=$class?> align=center style="background: #CCFFCC"> <?= $this->formatNumber($row['percent_gap'], "%") ?> </td>
 					<td <?=$class?> align = "center" style="background: #ffe6fd"> <?= $row['units_cl'] ?> </td>
 					<td <?=$class?> align = "right" style="background: #ffe6fd"> <?= $this->formatNumber($row['amount_cl'], "$") ?> </td>
 					<td <?=$class?> align=center style="background: #ffe6fd"> <?= $this->formatNumber($row['percent_cl'], "%") ?> </td>
 					<td <?=$class?> align = "center" style="background: #CCFFFF"> <?= $row['units_aft'] ?> </td>
 					<td <?=$class?> align = "right" style="background: #CCFFFF"> <?= $this->formatNumber($row['amount_aft'], "$") ?> </td>
 					<td <?=$class?> align=center style="background: #CCFFFF"> <?= $this->formatNumber($row['percent_aft'], "%") ?> </td>								 	
 				</tr> 				
 			<?
 		}		
 		?>
 			<tr>
 				<td class="desk-log-header"><b>Total</></td>
 				<td class="desk-log-header" align = "center"><b><?= $total_deals ?></></td>
 				<td class="desk-log-header" align = "right"><b><?= $this->formatNumber($total_produced, "$") ?></></td>
 				<td class="desk-log-header" align = "right"><b><?= $this->formatNumber($total_mtd, "$") ?></></td>
 				<td class="desk-log-header"></td>
 				<td class="desk-log-header" align=center><b><?= $this->formatNumber($total_product, "%") ?></></td>
 				<td class="desk-log-header" align=center style="background: #faefe3"><b><?= $total_u_vsi ?></></td>
 				<td class="desk-log-header" align = "right" style="background: #faefe3"><b><?= $this->formatNumber($total_amount_vsi, "$") ?></></td>
 				<td class="desk-log-header" align=center style="background: #faefe3"><b><?= $this->formatNumber($total_perc_vsi, "%") ?></></td>
 				<td class="desk-log-header" align=center style="background: #FFFFCC"><b><?= $total_u_cc ?></></td>
 				<td class="desk-log-header" align = "right" style="background: #FFFFCC"><b><?= $this->formatNumber($total_amount_cc, "$") ?></></td>
 				<td class="desk-log-header" align=center style="background: #FFFFCC"><b><?= $this->formatNumber($total_perc_cc, "%") ?></></td>
 				<td class="desk-log-header" align=center style="background: #CCFFCC"><b><?= $total_u_gap ?></></td>
 				<td class="desk-log-header" align = "right" style="background: #CCFFCC"><b><?= $this->formatNumber($total_amount_gap, "$") ?></></td>
 				<td class="desk-log-header" align=center style="background: #CCFFCC"><b><?= $this->formatNumber($total_perc_gap, "%") ?></></td> 		
 				<td class="desk-log-header" align=center style="background: #ffe6fd"><b><?= $total_u_cl ?></></td>
 				<td class="desk-log-header" align = "right" style="background: #ffe6fd"><b><?= $this->formatNumber($total_amount_cl, "$") ?></></td>
 				<td class="desk-log-header" align=center style="background: #ffe6fd"><b><?= $this->formatNumber($total_perc_cl, "%") ?></></td>
 				<td class="desk-log-header" align=center style="background: #CCFFFF"><b><?= $total_u_aft ?></></td>
 				<td class="desk-log-header" align = "right" style="background: #CCFFFF"><b><?= $this->formatNumber($total_amount_aft, "$") ?></></td>
 				<td class="desk-log-header" align=center style="background: #CCFFFF"><b><?= $this->formatNumber($total_perc_aft, "%") ?></></td>
 			<tr/>
 		<?
 		
 		echo "</table>";
 		
 		$sql="drop table $tableName";
		$this->connection->exec_query($sql);
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
	
	private function getRank()
	{	
			$time = time(); 
			$tableName = "fi_performance_temp_$time";
				
			$sql = "CREATE TABLE $tableName AS (select I.uid, I.name, I.employee_id, count(A.deal_no) as total_deals, sum(back) as total_produced, if(count(A.deal_no) <> 0, (sum(A.back) / count(A.deal_no)), 0) as mtd_pvr, 0 as power_rank
					from info I inner join app_rpt_desk_log A where I.employee_id = A.fi and A.status <> 'B' and title = 'Finance Manager' AND dl_date BETWEEN '$this->dateFrom' AND '$this->dateTo' 
					group by I.name)";
										
			$this->connection->exec_query($sql);
			
			PowerRank::updatePowerRank2($tableName,$this->connection);
					
			$sql = "SELECT * FROM $tableName inner join info I using(employee_id)";
				
			$results = $this->connection->exec_query($sql);
			
			$sql="drop table $tableName";
			$this->connection->exec_query($sql);
			return $results;
	}	
 }
?>
