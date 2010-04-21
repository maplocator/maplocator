<?php

// Connecting, selecting database
$dbconn = pg_connect("host=localhost dbname=Test123 user=postgres password=post123")
 or die('Could not connect: ' . pg_last_error());

// Performing SQL query
$query = "SELECT astext(topology) as location FROM \"transect_point\" limit 5 ";
$result = pg_query($query) or die('Query failed: ' . pg_last_error());



$arr = array();
  $i = 0;




while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {

    foreach ($line as $col_value) {
        $arr[$i]['text'] = '<input type="checkbox">'. $col_value .'</input>';

      $i++;
    }

}
$str = json_encode($arr);
  print $str;

// Free resultset
pg_free_result($result);

// Closing connection
pg_close($dbconn);

?>