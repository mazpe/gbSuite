<?php
    
    include_once $_SERVER['PHP_ROOT'].'/lib/core/init.php';
    global $PLATFORM_JS_FILES;
    
    $targerDir = $_SERVER['PHP_ROOT']."/js/all_fb.js";
    
    if(file_exists($targerDir))
    	unlink($targerDir);
    
    $target = fopen($targerDir,'w');
    
    foreach($PLATFORM_JS_FILES as $file)
    {
    	fwrite($target,
    	"{/**************  $file  ************/".
    	file_get_contents($_SERVER['PHP_ROOT']."/js/".$file)."\n\n\n}"
    	
    	);		
    }
        
?>
