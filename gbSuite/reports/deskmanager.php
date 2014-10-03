<?php
$dbhost = '192.168.1.156';
$dbuser = 'facebook';
$dbpass = 'facebook';

$conn = mysql_connect($dbhost, $dbuser, $dbpass) or die                      ('Error connecting to mysql');

$dbname = 'gbsuite_demo';
mysql_select_db($dbname);

$query  = "SELECT model,year,COUNT(stock_number) as count FROM inventory WHERE new_used = 'NEW' GROUP BY model ORDER BY count DESC";
$result = mysql_query($query);
?>

<table border=1>
<tr>
<td>Model</td>
<td>Year</td>
<td>Units</td>
</tr>
<?php
while($row = mysql_fetch_assoc($result))
{
    echo 	 
		"<tr>".
        "<td>{$row['model']} </td>" .
        "<td>{$row['year']} </td>".
        "<td> {$row['count']} </td>".
        "</tr>";
}
?>
</table>


<?php
mysql_close($conn);
?>