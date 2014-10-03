<?php
    
  include_once $_SERVER['PHP_ROOT'].'gbSuite/util/connection.php';  
  
  
  $connection = new Connection();
  echo $connection->exec_query("select * from news");
    
  
?>
