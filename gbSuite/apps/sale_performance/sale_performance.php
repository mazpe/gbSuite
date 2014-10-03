<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class SalePerformance extends Application
	{
		public function __construct()
		{
			$this->appId = 32;
		}
		
		private function defaultView()
		{
			$currentProfileTitle = $this->user->getCurrentProfileAttribute('title');
			
			$html = "";
			$query = "";
			
			$newUnits = 0;
			$usedUnits = 0;
			$totalUnits = 0;
			$goal = 0;
			$totalGross = 0;
			
			$row = null;
			$query = "SELECT id, date_format(`from`, '%Y%m%d') AS `from`, date_format(`to`, '%Y%m%d') AS `to` FROM dealer_view WHERE now() BETWEEN `from` AND `to`";
			$dateRange = $this->connection->get_row($query);
			$condition = "";
			
			$value = $dateRange['id'];
					
			if($value != null)
				$dealerViewId = $value;						

			if($dateRange != null)
				$condition = "dl_date BETWEEN '".$dateRange['from']."' AND '".$dateRange['to']."'"; 
			else			
				$condition = "MONTH(dl_date) = MONTH(now())";
			
			if($currentProfileTitle == 'Salesperson')
			{
				$employeeId = $this->user->getCurrentProfileAttribute('employee_id');
				
				if($employeeId != null && $employeeId != "")
					$query = "SELECT SUM(new) AS new, SUM(used) AS used, SUM(units) AS units, 
							SUM(gross_total) AS gross_total FROM app_rpt_sales_department 							
								WHERE employee_id = ".$employeeId." AND $condition GROUP BY employee_id";					
								
					if(isset($_GET['uid']))			
						$query_goals="select IFNULL(IF(recommit>0,recommit,goals),0) as goals from info inner join goals_settings
								using(uid) where uid=".$_GET['uid']." and dealer_view_id=$dealerViewId";
					else
						$query_goals="select IFNULL(IF(recommit>0,recommit,goals),0) as goals from info inner join goals_settings
								using(uid) where uid=".$this->user->getUID()." and dealer_view_id=$dealerViewId";
					
					$rs=$this->connection->get_row($query_goals);
			}
			else
				if($currentProfileTitle == 'Sales Manager' || $currentProfileTitle == 'General Sales Manager' || $currentProfileTitle == 'General Manager'
				   || $currentProfileTitle == 'Finance Manager' || $currentProfileTitle == 'Administrator' || $currentProfileTitle == 'Dealer Principal' || 
				   $currentProfileTitle == 'Internet Sales Director' || $currentProfileTitle == 'Administrative Assistant' || $currentProfileTitle == 'Customer Service Manager' || $currentProfileTitle == 'Accounting'){
					$query = "SELECT SUM(new) AS new, SUM(used) AS used, SUM(units) AS units, SUM(goal) AS goal, SUM(gross_total) AS gross_total 
								FROM app_rpt_sales_department 
								WHERE $condition";
 				
 			$query_goals = "select (new_units+used_units) as goals from dealership_goals where dealer_view_id=$dealerViewId";
 			
 			$rs = $this->connection->get_row($query_goals);
 			
			$gross_query="select sum(R.front + R.back + R.other) as used_total from app_rpt_desk_log R where
			$condition and nu='U' and status<>'B'";
			$result=$this->connection->get_row($gross_query);
			
			$other_total_query="select sum(other) as other from app_rpt_desk_log R where $condition and status<>'B'"; 
			$total_other=$this->connection->get_value($other_total_query);
			 				
 			}
			$user_agent=(isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"");
			
		    if(strpos($user_agent,'MSIE') !== false)
				$colspan = 2;
			else 
				$colspan = 3;
					
			if($query != "")
			{
				$row = $this->connection->get_row($query);
				
				if($row != null)
				{
					$newUnits = $row['new'];
					$usedUnits = $row['used'];
					$totalUnits = $row['units'];
					
					if(isset($rs))
					{
						$goal = $rs['goals'];
						$goal_percent=($rs['goals']>0?($row['units']/$rs['goals'])*100:0);
					}
					else
					{
						$goal = $row['goal'];
						$goal_percent=($row['goal']>0?($row['units']/$row['goal'])*100:0);
					}
					
					$totalGross = $row['gross_total'] + $total_other;
					$new_total = $totalGross - $result['used_total'];					
				}					
			}
			
			$html .= '<div class="sales-performance-container">
						<label><strong>Sales Performance</strong></label>
						 <TABLE class=sales-performance-table cellSpacing=1 cellPadding=0 align=center border=0 cellmargin="0">
              <TBODY>
              <TR>
                  <TH class=sales-performance-header colspan='.$colspan.'>New Units:&nbsp;&nbsp;</TH>
                  <TD align=right>'.$this->formatNumber($newUnits, ".").'</TD>
              </TR>
              <TR>
                  <TH class=sales-performance-header colspan='.$colspan.'>Used Units:&nbsp;&nbsp;</TH>
                  <TD align=right>'.$this->formatNumber($usedUnits, ".").'</TD>
              </TR>
              <TR>
                  <TH class=sales-performance-header colspan='.$colspan.'>Total Units:&nbsp;&nbsp;</TH>
                  <TD align=right>'.$this->formatNumber($totalUnits, ".").'</TD>
              </TR>
              <TR>
                  <TH class=sales-performance-header colspan='.$colspan.'>Goal:&nbsp;&nbsp;</TH>
                  <TD align=right>'.$this->formatNumber($goal, "0").'</TD>
              </TR>
              <TR>
                  <TH class=sales-performance-header colspan='.$colspan.'>% To Goal:&nbsp;&nbsp;</TH>
                  <TD align=right>'.$this->formatNumber($goal_percent, "0").' %</TD>
              </TR>';
												
				
				if($currentProfileTitle!='Salesperson'){/*Condition if is not a salesperson*/
			$html.=' <TR>
                  <TH class=sales-performance-header style="RIGHT: 120%; WIDTH: 50%; BOTTOM: 19%; POSITION: absolute;" align=right><br/>Total Gross New:</TH>
                  <TD style="RIGHT: 95%; WIDTH: 50%; BOTTOM: 19%; POSITION: absolute;" align=right><br><STRONG>'.$this->formatNumber($new_total, "$").'</STRONG></TD>
                  <TH class=sales-performance-header colSpan=2><br><STRONG>AVG&nbsp;per&nbsp;Copy&nbsp;&nbsp;-&nbsp;&nbsp;New:&nbsp;&nbsp;</STRONG></TH>
                  <TD align=right ><br><STRONG>'.$this->formatNumber($newUnits > 0 ? $new_total / $newUnits : 0, "$").'</STRONG></TD>                  
              </TR>
              <TR>
                  <TH class=sales-performance-header style="RIGHT: 120%; WIDTH: 50%; BOTTOM: 10%; position:absolute;">Total Gross Used:</TH>
                  <TD style="RIGHT: 95%; WIDTH: 50%; BOTTOM: 10%; POSITION: absolute" align=right><STRONG>'.$this->formatNumber($result['used_total'], "$").'</STRONG></TD>
                  <TH class=sales-performance-header colSpan=2><STRONG>AVG&nbsp;per&nbsp;Copy&nbsp;&nbsp;-&nbsp;Used:&nbsp;&nbsp;</STRONG></TH>
                  <TD align=right><STRONG>'.$this->formatNumber($usedUnits > 0 ? $result['used_total']/$usedUnits : 0, "$").'</STRONG></TD>
              </TR>
              <TR>
                  <TH class=sales-performance-header style="RIGHT: 120%; WIDTH: 50%; BOTTOM: 1%; POSITION: absolute">Total Gross:</TH>
                  <TD style="RIGHT: 95%; WIDTH: 50%; BOTTOM: 1%; POSITION: absolute" align=right><STRONG>'.$this->formatNumber($totalGross, "$").'</STRONG></TD>
                  <TH class=sales-performance-header colSpan=2><STRONG>AVG&nbsp;per&nbsp;Copy&nbsp;&nbsp;-&nbsp;Total:&nbsp;&nbsp;</STRONG></TH>
                  <TD align=right><STRONG>'.$this->formatNumber($totalUnits > 0 ? $totalGross / $totalUnits : 0, "$").'</STRONG></TD>';
								
				}	
				else
				{	//If is a salesperson other things should be reflected
					$html.='<tr><th colspan=2 class="sales-performance-header"><br/><strong>AVG&nbsp;per&nbsp;Copy&nbsp;-&nbsp;Total:&nbsp;&nbsp;</strong></th><td><br/><strong>'.$this->formatNumber($totalUnits > 0 ? $totalGross / $totalUnits : 0, "$").'<strong></td></tr>
								<tr><th colspan=2 class="sales-performance-header"><strong>Total&nbsp;Gross:&nbsp;&nbsp;</strong></th><td align=right><strong>'.$this->formatNumber($totalGross, "$").'</strong></td>
							</tr>';
				}
							
				$html.='</TBODY></table>
					</div>';	
				
					
			echo $html;
			
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
		
		/*public function formatNumber($value)
		{
			return number_format(round($value, 0.0), 1, '.', ',');	
		}*/
		
		public function renderHTML()
		{
			if($this->html != "")
				echo $this->html;
			else
				$this->defaultView();
		}
	}
?>