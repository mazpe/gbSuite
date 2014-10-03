<?php
include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/server_url.php';

class maxChart {
   var $data;         // The data array to display
   var $type = 1;     // Vertical:1 or Horizontal:0 chart
   var $title;        // The title of the chart
   var $width = 300;  // The chart box width 
   var $height = 200; // The chart box height
   var $metaSpaceHorizontal = 75; // Total space needed for chart title + bar title + bar value
   var $metaSpaceVertical = 75; // Total space needed for chart title + bar title + bar value
   var $variousColors = false;
   var $percentage = false;
   var $format = "";
   
   function maxChart($data ,$percentage = false)
   {
    	$this->data = $data;  
   		$this->percentage = $percentage;	
   }
   
   function displayChart($title='', $type, $width=300, $height=200, $variousColor=false)
   {
      $this->type   = $type;
      $this->title  = $title;
      $this->width  = $width;
      $this->height = $height;
      $this->variousColors = $variousColor;

	  $html = "";
		
		if($this->percentage)
		{
			
			$html = $html.'<div class="chartbox" style="width:'.$this->width.'%; height:'.($this->height).'px;">
                <h2>'.$this->title.'</h2>'."\r\n";
		}
		else
		{
			$html = $html.'<div class="chartbox" style="width:'.$this->width.'px; height:'.($this->height + 10).'px;">
                <h2>'.$this->title.'</h2>'."\r\n";	
		}
		
		
		
      
	    
      if ($this->type == 1)  
	  	$html .= $this->drawVertical();
      else 
	  	$html .= $this->drawHorizontal();   
    
      $html = $html.'    </div>';
	  
	  return $html;
   }
   
   function getMaxDataValue(){
      $max = 0;
      
      foreach ($this->data as $key=>$value) {
         if ($value > $max) $max = $value;	
      }
      
      return $max;
   }
   
   function getElementNumber(){
      return sizeof($this->data);
   }
   
   public function setFormat($format)
   {
   		$this->format = $format;
   }
   
   function drawVertical()
   {
   	  $maxValue = $this->getMaxDataValue();
	  $elementNumber = $this->getElementNumber();
	  
	  if( $maxValue > 0)
      	$multi = ($this->height -$this->metaSpaceVertical) / $maxValue;
	  else
		$multi = 0;
		
      $max   = $multi * $this->getMaxDataValue();
	  
	  if($elementNumber > 0 )
	  {
		if($this->percentage)
		{
		  	$barw = floor(100 / $elementNumber) . "%";
		}
		else
			$barw = floor($this->width / $elementNumber) - 5;
	  }
	  else
	  	$barw = 0;
               
      
      $i = 1;
	  
      $html = '<table class="graphic-table" width=100%>';
	  $values = "";
	  $labels = "";
	  
      foreach ($this->data as $key=>$value) 
	  {
         $b = floor($max - ($value*$multi));
         $a = $max - $b;
         
         if ($this->variousColors) 
		 	$color = ($i % 5) + 1;
         else 
		 	$color = 1;
			
         $i++;
         
		 $values .= '<td width='. $barw .'  class="percent" valign="bottom"><div>'.$this->formatNumber($value).'</div><div><IMG height='.$a.'px src="/css/images/bar'.$color.'.gif" width="100%" /></div></td>'; 
		 $labels .= '<td class="value-fields">'.$key.'</td>';

	
	/*	 $html = $html.'    <div class="barvvalue" style="margin-top:'.$b.'px; width:'.$barw.'px;">'.$value.'</div>';
	     
		 $html = $html.'    <div '.($value == "0" ? ' style="height:3px"' : '').'><fb:img src="/css/images/bar'.$color.'.gif" width="'.$barw.'px" height="'.$a.'px" /></div>';
         
		 $html = $html.'    <div class="barvvalue" style="width:'.$barw.'px;">'.$key.'</div>';
         
		 $html = $html.'  </div>';*/
      }
	  
	  $html .= "<tr>".$values."</tr>";
	  $html .= "<tr>".$labels."</tr>";
	  $html .= "</table>";
	  
	  return $html;      
   }
   
   /*function drawVertical()
   {
   	  $maxValue = $this->getMaxDataValue();
	  $elementNumber = $this->getElementNumber();
	  
	  if( $maxValue > 0)
      	$multi = ($this->height -$this->metaSpaceHorizontal) / $maxValue;
	  else
		$multi = 0;
		
      $max   = $multi * $this->getMaxDataValue();
	  
	  if($elementNumber > 0)
      	$barw = floor($this->width / $elementNumber) - 5;
	  else
	  	$barw = 0;
      
      $i = 1;
	  
      $html = "";
	  
      foreach ($this->data as $key=>$value) 
	  {
         $b = floor($max - ($value*$multi));
         $a = $max - $b;
         
         if ($this->variousColors) $color = ($i % 5) + 1;
         else $color = 1;
         $i++;
         
         $html = $html.'  <div class="barv">';

		 $html = $html.'    <div class="barvvalue" style="margin-top:'.$b.'px; width:'.$barw.'px;">'.$value.'</div>';
	     
		 $html = $html.'    <div '.($value == "0" ? ' style="height:3px"' : '').'><fb:img src="/css/images/bar'.$color.'.gif" width="'.$barw.'px" height="'.$a.'px" /></div>';
         
		 $html = $html.'    <div class="barvvalue" style="width:'.$barw.'px;">'.$key.'</div>';
         
		 $html = $html.'  </div>';
      }
	  
	  return $html;      
   }*/
   
   function drawHorizontal(){
      $multi = ($this->width-170) / $this->getMaxDataValue();
      $max   = $multi * $this->getMaxDataValue();
      $barh  = floor(($this->height - 35) / $this->getElementNumber());
      
      $i = 1;
      	  
	  $html = "";
	  
      foreach ($this->data as $key=>$value) {
         $b = floor($value*$multi);

         if ($this->variousColors) $color = ($i % 5) + 1;
         else $color = 1;
         $i++;
         
         $html = $html.'  <div class="barh" style="height:'.$barh.'px;">'."\r\n";
         $html = $html.'    <div class="barhcaption" style="line-height:'.$barh.'px; width:90px;">'.$key.'</div>'."\r\n";
         $html = $html.'    <div class="barhimage"><fb:img src="/css/images/barh'.$color.'.gif" width="'.$b.'px" height="'.$barh.'px" /></div>'."\r\n";
         $html = $html.'    <div class="barhvalue" style="line-height:'.$barh.'px; width:30px;">'.$value.'</div>'."\r\n";
         $html = $html.'  </div>';

      }
	  
	  return $html;      
   }
   
   public function formatNumber($value)
	{
		$format = $this->format;
		
		if($format == ".00")
			$value = number_format($value, 1, '.', ',');
		else
			if($format == "%")
				$value = number_format($value, 0, '.', ',')."%";
			else
				if($format == "$")
					$value = "$".number_format($value, 0, '.', ',');
					
		return $value;	
	}
}
?>