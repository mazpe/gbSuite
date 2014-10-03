<?php
	include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/application.php");
	
    class DealerViewSetup extends Application
	{
		public function __construct()
		{
			$this->appId = 41;	
		}
		
		private function renderTable()
		{
			$html = "";
			
			$query = "SELECT id, label, date_format(`from`, '%m/%d/%Y') AS `from`, date_format(`to`, '%m/%d/%Y') AS `to` FROM dealer_view";						
			
			$rs = $this->connection->exec_query($query);
			
			if($rs !== false)
			{
				//app=team_builder&action=save&uid='.$this->user->getUID().'&redirect=team_builder>';
				$html .= '<div class="lead-control-container"><form method="post" action=/gbSuite/apps/process_application.php?app=dv_setup&action=save&uid='.$this->user->getUID().'&redirect=dv_setup>';
				$html .= '<table class="lead-control-table" cellpadding=1>';				
				$html .= 	'<th></th><th>Label</th>
							<th>From</th>
							<th>To</th>
							<th></th>';
				
				$count = 1;			
					 	
				while($row = mysql_fetch_assoc($rs))
				{
					$html .= '<tr>';										
					$html .= '<td width=1%></td><td align=center width=5%><input type="hidden" name="dv_setup_id'.$count.'" value="'.$row['id'].'" /><input type="text" name="dv_setup_label'.$count.'" value="'.$row['label'].'" /></td>';
					$html .= '<td align=center width=10%><input class="lead-control-input" type="text" name="dv_setup_from'.$count.'" value="'.$row['from'].'" /><img src="/images/resources/calendar.gif" onclick=displayDatePicker("dv_setup_from'.$count.'",false,"mdy","/"); /></td>';
					$html .= '<td align=center width=10%><input class="lead-control-input" type="text" name="dv_setup_to'.$count.'" value="'.$row['to'].'" /><img src="/images/resources/calendar.gif" onclick=displayDatePicker("dv_setup_to'.$count.'",false,"mdy","/"); /></td>';
					$html .= '<td align=center width=1%><a href="/gbSuite/home.php?app=dv_setup&uid='.$this->user->getUID().'&action=delete&id='.$row['id'].'&confirm=true"><img src="/images/resources/delete.gif" /></a></td>';
					$html .= '</tr>';					
					
					++$count;
				}		

				$html .= '<tr>';										
				$html .= '<td width=1% align=right><strong>New:</strong></td><td align=center width=5%><input type="hidden" name="dv_setup_id0" value="0" />&nbsp;<input type="text" name="dv_setup_label0" /></td>';
				$html .= '<td align=center width=10%><input class="lead-control-input" type="text" name="dv_setup_from0" /><img src="/images/resources/calendar.gif" onclick=displayDatePicker("dv_setup_from0",false,"mdy","/"); /></td>';
				$html .= '<td align=center width=10%><input class="lead-control-input" type="text" name="dv_setup_to0" /><img src="/images/resources/calendar.gif" onclick=displayDatePicker("dv_setup_to0",false,"mdy","/"); /></td>';
				$html .= '<td align=center width=1%>&nbsp;</td>';
				$html .= '</tr>';					
				
				$html .= '</table>';
				$html .= '<input type="hidden" name="dv_setup_row_count" value="'.$count.'" />';
				$html .= '<input class="button-link" type=submit value="Save">';				
				$html .= '<input class="button-link" type=button value="Go back" onclick=document.setLocation("/gbSuite/home.php")>';
				$html .= '</form></div>';
			}
			
			echo $html;
		}
		
		public function save($params)
		{
			$rowCount = 0;
			
			$prefix = "dv_setup_";
			
			$rowCount = $params[$prefix.'row_count'];
			
			$query = "";
			
			for($i = 0; $i < $rowCount; $i++)
			{
				$label = $params[$prefix.'label'.$i];
				$id = $params[$prefix.'id'.$i];
				$from = $params[$prefix.'from'.$i];
				$to = $params[$prefix.'to'.$i];
			
				if($from != "" && $to != "" && $label != "")
				{
					$from = substr($from, 6, 4).substr($from, 0, 2).substr($from, 3, 2);
					$to = substr($to, 6, 4).substr($to, 0, 2).substr($to, 3, 2)."235959";
				
					if($id == "0")		
						$query = "INSERT INTO dealer_view (id, label, `from`, `to`, `type`, ordering ) VALUES (0, '".$label."', '".$from."', '".$to."', 'user', 0) ";
					else
						$query = "UPDATE dealer_view SET label = '$label', `from` = '$from', `to` = '$to' WHERE id = $id";
					
					$this->connection->exec_query($query);
				}
			}
		}
		
		public function delete($params)
		{
			if(!isset($params['confirm']))
			{
				$query = "DELETE FROM dealer_view WHERE id = ".$params['id'];
				
				$this->connection->exec_query($query);	
			}
			else
				if($params['confirm'] == 'true')
				{
					$html = "";
					$html .= "<div>Are you sure you want to delete this record?</div>";
					$html .= '<div><a class="button-link" href="/gbSuite/apps/process_application.php?app=dv_setup&uid='.$this->user->getUID().'&action=delete&id='.$params['id'].'&redirect=dv_setup" >Yes</a>&nbsp;<a class="button-link" href="/gbSuite/home.php?app=dv_setup">Go back</a></div>';
				}
			
			$this->html = $html;
			
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
