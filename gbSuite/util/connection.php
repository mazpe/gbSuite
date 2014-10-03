<?php


/*
 * This class is for the conecction to database
 */



 // include the variables for db connection
include_once $_SERVER['PHP_ROOT'].'/lib/core/init.php';


class Connection
{
	var $user;
	var $host;
	var $password;
	var $conn;
	
	/*
	 * @params optional associative array whith the configuration
	 * 		user	:	the database user
	 * 		ip		:	The ip 
	 * 		password:	The password of the user in the database.
	 * 		db_name	:	The database name to connect with.
	 */
	
	public function __construct()
	{
		global $DB_USERNAME;
		global $DB_IP;
		global $DB_PWD;
		global $db_name;
		
		$argv = func_get_args();
		
		if(count($argv) >= 1)
		{
			$conf = $argv[0];
		}
			
		
		$config['user'] = $conf['user'] != "" ? $conf['user'] : $DB_USERNAME;
		$config['ip'] = $conf['ip'] != "" ? $conf['ip'] : $DB_IP;
		$config['password'] = $conf['password'] != "" ? $conf['password'] : $DB_PWD;
		$config['db_name'] = $conf['db_name'] != "" ? $conf['db_name'] : $db_name;
		
								
		$this->user = $config["user"];
		$this->host = $config["ip"];
		$this->password = $config["password"]; 
		$this->db_name = $config["db_name"];

		//echo $this->db_name;
				
		$this->conn = mysql_connect($this->host,$this->user,$this->password);
		$select_result = mysql_select_db($this->db_name, $this->conn);
		
	}
	
	public function exec_query($sql)
	{
		return mysql_query($sql);
	}
	
	public function get_row($sql)
	{
		$row = null;
		
		$result = mysql_query($sql);
		
		while($row = mysql_fetch_array($result))
		{
			return $row;
		}
		
		return null;
	}
	
	public function get_value($sql)
	{
		$row = $this->get_row($sql);
		return $row[0];
	}		
	
	public function getConnection()
	{
		return $this->conn;
	}
	
	
	public function fillSelect($sql, $value, $display, $selectedValue = null, $defaultValue)
	{
		$html = "";	
		
		$result = $this->exec_query($sql);
		
		if($defaultValue)
			$html .= "<option value=0>--</option>";
		
		while($row = mysql_fetch_array($result))
		{
			$html .= "<option value='". $row[$value] ."' ".($row[$value] == $selectedValue ? "selected=true": "").">" . $row[$display] . "</option>";
		}
		return $html;
	}
	
	public function fillDesklogSelect($sql, $value, $display, $initials, $selectedValue = null, $defaultValue)
	{
		$html = "";	
		
		$result = $this->exec_query($sql);
		
		if($defaultValue)
			$html .= "<option value=0>--</option>";
		
		while($row = mysql_fetch_array($result))
		{
			$html .= "<option value='". $row[$value] ."' ".($row[$value] == $selectedValue ? "selected=true": "").">".$row[$initials]." - ".$row[$display]."</option>";
		}
		return $html;
	}
}
    
?>
