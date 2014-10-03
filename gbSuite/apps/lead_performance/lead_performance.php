<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class LeadPerformance extends Application
	{
		public function __construct()
		{
			$this->appId = 33;
		}
		
		private function defaultView()
		{
			$currentProfileTitle = $this->user->getCurrentProfileAttribute('title');
			
			$html = "";
			
			$query = "";
			
			$showroom = 0;
			$soldSH = 0;
			$closeSH = 0;
			
			$iphone = 0;
			$soldIP = 0;
			$closeIP = 0;
			
			$ilead = 0;
			$soldIL = 0;
			$closeIL = 0;
			
			$row = null;
			$query = "SELECT id, date_format(`from`, '%Y%m%d') AS `from`, date_format(`to`, '%Y%m%d') AS `to` FROM dealer_view WHERE now() BETWEEN `from` AND `to`";
			$dateRange = $this->connection->get_row($query);
			$condition = "";
			$leadControlQuery = "";

			if($dateRange != null)
				$condition = "dl_date BETWEEN '".$dateRange['from']."' AND '".$dateRange['to']."'"; 
			else			
				$condition = "MONTH(dl_date) = MONTH(now())";
				
			if($currentProfileTitle == 'Salesperson')
			{
				$employeeId = $this->user->getCurrentProfileAttribute('employee_id');
				
				if($employeeId != null && $employeeId != "")
				{
					$query = "SELECT SUM(sold_sh) AS sold_sh, SUM(sold_ip) AS sold_ip, SUM(sold_il) AS sold_il 
								FROM app_rpt_sales_department 
									WHERE employee_id = ".$employeeId." AND $condition GROUP BY employee_id";
									
					$leadControlQuery = "SELECT * FROM lead_control WHERE dealer_view_id = ".$dateRange['id']." AND uid = '".$this->user->getCurrentProfileUID('uid')."'";
				}	
			}
			else
				//if($currentProfileTitle == 'Sales Manager' || $currentProfileTitle == 'General Sales Manager' || $currentProfileTitle == 'General Manager' 
				//	|| $currentProfileTitle == 'Finance Manager' || $currentProfileTitle == 'Administrator' || $currentProfileTitle == 'Dealer Principal' || $currentProfileTitle == 'Internet Sales Director')
				{
					$query = "SELECT SUM(sold_sh) AS sold_sh, SUM(sold_ip) AS sold_ip, SUM(sold_il) AS sold_il 
								FROM app_rpt_sales_department WHERE $condition";
								
					$leadControlQuery = "SELECT SUM(showroom) AS showroom, SUM(iphone) AS iphone, SUM(ileads) AS ileads FROM lead_control WHERE dealer_view_id = ".$dateRange['id'];	
				}
							 
			if($query != "")
			{
				$row = $this->connection->get_row($query);
				
				if($row != null)
				{
					$leadControl = $this->connection->get_row($leadControlQuery); 
						
					$showroom = ($leadControl["showroom"] != null ? $leadControl["showroom"] : 0);
					$soldSH = $row["sold_sh"];
					$closeSH = ($showroom > 0 ? ($soldSH * 100 / $showroom) : 0); //$row["close_sh%"];
					
					$iphone = ($leadControl["iphone"] != null ? $leadControl["iphone"] : 0);
					$soldIP = $row["sold_ip"];
					$closeIP = ($iphone > 0 ? ($soldIP * 100 / $iphone) : 0); //$row["close_ip%"];
					
					$ilead = ($leadControl["ileads"] != null ? $leadControl["ileads"] : 0);
					$soldIL = $row["sold_il"];
					$closeIL = ($ilead > 0 ? ($soldIL * 100 / $ilead) : 0); //$row["close_il%"];
				}					
			}
			
			$html .= '<div class="leads-performance-container">
						<div class="lead-performance-label"><label><strong>Lead Performance</strong></label></div>
						<table class="leads-performance-table" cellspacing=0 border=0 cellmargin=0 cellpadding=0>
							<tr><th colspan=2 class="leads-performance-table-header">Showroom</th><th colspan=2 class="leads-performance-table-header">iPhone</th><th colspan=2 class="leads-performance-table-header">iLeads</th></tr>
							<tr>
								<th class="leads-performance-header">Leads:&nbsp;&nbsp;</th><td class="lead-number">'.$this->formatNumber($showroom).'</td>
								<th class="leads-performance-header">Leads:&nbsp;&nbsp;</th><td class="lead-number">'.$this->formatNumber($iphone).'</td>
								<th class="leads-performance-header">Leads:&nbsp;&nbsp;</th><td class="lead-number">'.$this->formatNumber($ilead).'</td>
							</tr>
							<tr>
								<th class="leads-performance-header">Sold:&nbsp;&nbsp;</th><td class="lead-number">'.$this->formatSold($soldSH).'</td>
								<th class="leads-performance-header">Sold:&nbsp;&nbsp;</th><td class="lead-number">'.$this->formatSold($soldIP).'</td>
								<th class="leads-performance-header">Sold:&nbsp;&nbsp;</th><td class="lead-number">'.$this->formatSold($soldIL).'</td>
							</tr>
							<tr>
								<th class="leads-performance-header">Close:&nbsp;&nbsp;</th><td class="lead-number">'.$this->formatNumber($closeSH).'%</td>
								<th class="leads-performance-header">Close:&nbsp;&nbsp;</th><td class="lead-number">'.$this->formatNumber($closeIP).'%</td>
								<th class="leads-performance-header">Close:&nbsp;&nbsp;</th><td class="lead-number">'.$this->formatNumber($closeIL).'%</td>
							</tr>							
						</table>
					</div>';	
					
			echo $html;
			
		}
		
		public function formatSold($value)
		{
			return number_format($value, 1, '.', ',');
		}
		
		public function formatNumber($value)
		{
			return number_format(round($value, 0.0), 0, '.', ',');	
		}

		public function renderHTML()
		{
			if($this->html != "")
				echo $this->html;
			else
				$this->defaultView();
		}
	}
?>