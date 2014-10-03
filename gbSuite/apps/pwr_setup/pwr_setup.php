<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class PowerRankSetup extends Application
	{
		public function __construct()
		{
			$this->appId = 39;	
		}
		
		private function renderTable()
		{
			$html = "";
			
			$query = "SELECT * FROM power_rank_setup";						
			
			$rs = $this->connection->exec_query($query);
			
			if($rs !== false)
			{
				$html .= '<div class="power-rank-setup-container">
						  <form method="post" id="power-rank-setup-form" action=/gbSuite/apps/process_application.php?app=pwr_setup&action=save&uid='.$this->user->getUID().'&redirect=pwr_setup>';
				$html .= '<table class="power-rank-setup-table" cellpadding=1>';				
				$html .= 	'<th>Units Multiplier</th>
							<th>Units %</th>
							<th>Front %</th>
							<th>Back %</th>
							<th>Total Gross %</th>
							<th>Total %</th>';
				
				$row = mysql_fetch_assoc($rs);
				
				$html .= '<tr>';
				if($row != null)				
				{	
										
					$html .= '<td align=center width=10%><input class="power-setup-input" id="power-rank-setup-units-multiplier" onkeyup="updatePowerRankTotal()" type="text" name="pwr_setup_units_multiplier" value="'.$row['units_multiplier'].'" /></td>';
					$html .= '<td align=center width=10%><input class="power-setup-input" id="power-rank-setup-units-percentage" onkeyup="updatePowerRankTotal()" type="text" name="pwr_setup_units_percentage" value="'.$row['units_percentage'].'" /></td>';
					$html .= '<td align=center width=10%><input class="power-setup-input" id="power-rank-setup-front-percentage" onkeyup="updatePowerRankTotal()" type="text" name="pwr_setup_front_percentage" value="'.$row['front_percentage'].'" /></td>';
					$html .= '<td align=center width=10%><input class="power-setup-input" id="power-rank-setup-back-percentage" onkeyup="updatePowerRankTotal()" type="text" name="pwr_setup_back_percentage" value="'.$row['back_percentage'].'" /></td>';
					$html .= '<td align=center width=10%><input class="power-setup-input" id="power-rank-setup-total-percentage" onkeyup="updatePowerRankTotal()" type="text" name="pwr_setup_total_percentage" value="'.$row['total_percentage'].'" /></td>';
					$html .= '<td align=center width=10%><input class="power-setup-input" id="power-rank-setup-total" type="text" disabled=true name="pwr_setup_total" value="'.($row['units_percentage'] + $row['front_percentage'] + $row['back_percentage'] + $row['total_percentage']).'" /></td>';
				}		
				else
				{
					$html .= '<td align=center width=10%><input class="power-setup-input" id="power-rank-setup-units-multiplier" type="text" onkeyup="updatePowerRankTotal()" name="pwr_setup_units_multiplier" value="0" /></td>';
					$html .= '<td align=center width=10%><input class="power-setup-input" id="power-rank-setup-units-percentage" type="text" onkeyup="updatePowerRankTotal()" name="pwr_setup_units_percentage" value="0" /></td>';
					$html .= '<td align=center width=10%><input class="power-setup-input" id="power-rank-setup-front-percentage" type="text" onkeyup="updatePowerRankTotal()" name="pwr_setup_front_percentage" value="0" /></td>';
					$html .= '<td align=center width=10%><input class="power-setup-input" id="power-rank-setup-back-percentage" type="text" onkeyup="updatePowerRankTotal()" name="pwr_setup_back_percentage" value="0" /></td>';
					$html .= '<td align=center width=10%><input class="power-setup-input" id="power-rank-setup-total-percentage" type="text" onkeyup="updatePowerRankTotal()" name="pwr_setup_total_percentage" value="0" /></td>';
					$html .= '<td align=center width=10%><input class="power-setup-input" id="power-rank-setup-total" type="text" name="pwr_setup_total" value="0" /></td>';
				}
				
				$html .= '</tr></table>';			
				$html .= '<div id="power-rank-setup-error-message" style="display:none;">Total% is not 100%. Please correct the values.</div>';	
				$html .= '<input class="button-link" type=button value="Save" onclick="savePowerRankSetup()">';				
				$html .= '<input class="button-link" type=button value="Go back" onclick=document.setLocation("/gbSuite/home.php")>';
				$html .= '</form></div>';
			}
			
			echo $html;
		}
		
		public function save($params)
		{
			$unitsMultiplier = 0;
			$unitsPercentage = 0;
			$frontPercentage = 0;
			$backPercentage = 0;
			$totalPercentage = 0;
			
			$prefix = "pwr_setup_";
			
			$query = "";
			
			$unitsMultiplier = $params[$prefix.'units_multiplier'];
			$unitsPercentage = $params[$prefix.'units_percentage'];	
			$frontPercentage = $params[$prefix.'front_percentage'];
			$backPercentage = $params[$prefix.'back_percentage'];
			$totalPercentage = $params[$prefix.'total_percentage'];
				
			$query = "DELETE FROM power_rank_setup";
			$this->connection->exec_query($query);
											
			$query = "INSERT INTO power_rank_setup (id, units_multiplier, units_percentage, front_percentage, back_percentage, total_percentage) VALUES (0, $unitsMultiplier, $unitsPercentage, $frontPercentage, $backPercentage, $totalPercentage) ";
			
			$this->connection->exec_query($query);
			
			$this->updatePowerRank();			
		}
		
		 
		public function renderHTML()
		{
			if($this->html != "")
				echo $this->html;	
			else
				$this->renderTable();
		}
		
		private function updatePowerRank()
		{
			$query = "SELECT * FROM power_rank_setup";
			
			$powerRankSetup = $this->connection->get_row($query);
			
			$powerRankSetup['units_percentage'] = ($powerRankSetup['units_percentage'] > 0 ? $powerRankSetup['units_percentage'] / 100 : 0);
			$powerRankSetup['front_percentage'] = ($powerRankSetup['front_percentage'] > 0 ? $powerRankSetup['front_percentage'] / 100 : 0);
			$powerRankSetup['back_percentage'] = ($powerRankSetup['back_percentage'] > 0 ? $powerRankSetup['back_percentage'] / 100 : 0);
			$powerRankSetup['total_percentage'] = ($powerRankSetup['total_percentage'] > 0 ? $powerRankSetup['total_percentage'] / 100 : 0);
			
			$query = "SELECT DISTINCT I.employee_id
						FROM info I INNER JOIN app_rpt_desk_log S
						ON I.employee_id = S.sp1 OR I.employee_id = S.sp2";
						
			$rs = $this->connection->exec_query($query);
			
			while($row = mysql_fetch_array($rs))
				$this->updatePowerRankRow($row['employee_id'], $powerRankSetup);				
		}
		
		function updatePowerRankRow($employeeId, $powerRankSetup)
		{
			$grossFront = 0;
			$grossBack = 0;
			
			$count = 0;
			$units = 0;
						
			$query = "SELECT * FROM app_rpt_desk_log WHERE sp1 = $employeeId OR sp2 = $employeeId";
			$rs = $this->connection->exec_query($query);
			
			if($rs !== false)
			{
				while($row = mysql_fetch_array($rs))
				{
					if($row["sp2"] != "0")
					{
						$grossFront += ($row["front"] != 0 ? $row["front"] / 2 : 0); 
						$grossBack += ($row["back"] != 0 ? $row["back"] / 2 : 0);
						
						$units += 0.5;									
					}
					else
					{
						$grossFront += $row["front"]; 
						$grossBack += $row["back"];
						
						++$units;						
					}
						
					++$count;	
				}
				
				//Verify if the sales person exists in app_rpt_sales_department
				$query = "SELECT COUNT(employee_id) AS recordCount FROM app_rpt_power_rank WHERE employee_id = ".$employeeId;
			
				$count = $this->connection->get_value($query);
				
				//(Units * 1500) * (30%) + (Front * 20%) + (Back * 5%) + (Total * 10%) / 4

				$totalScore = (($units * $powerRankSetup['units_multiplier']) * $powerRankSetup['units_percentage']) + ($grossFront * $powerRankSetup['front_percentage']) + ($grossBack * $powerRankSetup['back_percentage']) + (($grossFront + $grossBack) * $powerRankSetup['total_percentage']) / 4;
				
				//Power rank
				if($count == 0)
				{
					
					$query = "INSERT INTO app_rpt_power_rank (sales_person, units_sold, us_rank, total_front_end, tfe_rank, total_back_end, tbe_rank, 
																total_gross, tg_rank, total_score, power_rank, employee_id)
								SELECT name, $units, 0, $grossFront, 0, $grossBack, 0, ".($grossFront + $grossBack).", 0, $totalScore, 0, $employeeId     
									FROM info 
									WHERE employee_id = ".$employeeId;
				}
				else
				{				
					$query = "UPDATE app_rpt_power_rank SET units_sold = $units, total_front_end = $grossFront, total_back_end = $grossBack, total_gross = ".($grossFront + $grossBack).", 
															  total_score = $totalScore 
								WHERE employee_id = $employeeId";					
				}					 
				
				$this->connection->exec_query($query);
				
				/*$sql = "
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
	}
?>
