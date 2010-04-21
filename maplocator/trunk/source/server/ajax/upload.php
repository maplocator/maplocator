<?php
/*
All MapLocator code is Copyright 2010 by the original authors.

This work is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 3 of the License, or any
later version.

This work is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See version 3 of
the GNU General Public License for more details.

You should have received a copy of the GNU General Public License
Version 3 along with this program as the file LICENSE.txt; if not,
please see http://www.gnu.org/licenses/gpl-3.0.html.

*/

/***
*This file contains functionality to upload layer .
*
***/


require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

require_once 'functions.php';
require_once 'XMLParser.php';

$base_path = base_path();

$errmsgstr = "Please try after sometime or contact admin.";

$infoEntry = <<<EOF
  <tr>
    <td valign="top" class="key">{%key}</td>
    <td valign="top" class="value">{%value}</td>
  </tr>
EOF;

function return_error($msg) {
    $err = <<<END
<?xml version="1.0"?>
<mapdata>
<error value='true'>
<message>
END;
    $err .= $msg;
    $err .= <<< END
</message>
</error>
</mapdata>
END;
    return $err;
}

global $user;
global $table_cols;
global $col_type;

function getSchema($layer_tablename){
  //get column names except custom columns
  global $table_cols;
  global $col_type;

  $query = "select column_name,data_type from information_schema.columns where table_name='%s' AND column_name not like '".AUTO_DBCOL_PREFIX."%'";
  $result = db_query($query,$layer_tablename);
  if(!$result) {
  } else {
     while($obj = db_fetch_object($result)) {
	    $cols .= "'".$obj->column_name."',";
	    $col_type[$obj->column_name] = $obj->data_type;
	 }
  }
  $cols = substr($cols,0,(strlen($cols)-1));
  $col_info = getDBColDesc($layer_tablename,$cols);

  //get layer_type
  $query = "select layer_type from \"Meta_Layer\" where layer_tablename = '%s'";
  $result = db_query($query,$layer_tablename);
  if(!$result) {
  } else {
     while($obj = db_fetch_object($result)) {
	     $layer_type = $obj->layer_type;
	 }
  }
  if(isset($_REQUEST['action'])){
    $table_cols = $col_info;
  } else {
      $table_cols = $cols;
  }
  return $layer_type;
}

if(isset($_REQUEST['action'])){
  $action = $_REQUEST['action'];
  $layer_tablename = $_REQUEST['layer_tablename'];
  $layer_type = getSchema($layer_tablename);
  $table_dtd = getDTD($table_cols,$layer_type,$layer_tablename);
  echo $table_dtd;

} else {
   /* POST */
  /* validate if file is selected or not */
  if ("" === $_FILES['uploadFile']['name']){
     die(return_error("Please select a file to upload"));
  }
  $pathinfo = pathinfo($_FILES['uploadFile']['name']);
  $user = $GLOBALS['user'];
  $layer_tablename = $_POST['layer_name'];

  $filepath = "upload/";
  $file_types = array("kml","kmz","gpx");
  $file = $filepath.$pathinfo['basename'];

  /* if extension is not kml or kmz or gpx and if file size exceeds 5MB throw error */
  if ((!in_array($pathinfo['extension'],$file_types)) || ($_FILES['uploadFile']['size']/(1024*1024) > 5)) {
    die(return_error("File type should be kml"));
  }

  if ($_FILES['uploadFile']['error'] > 0) {
    die(return_error("Error uploading file"));
  } else {
  	  if (move_uploaded_file($_FILES['uploadFile']['tmp_name'],$file)) {
		 $dbuser = preg_replace('/pgsql:\/\/([^:@][^:@]*).*/','$1',$db_url);
		 $dbname = substr(strrchr($db_url,'/'),1);

		 $cmd = "ogr2ogr -update -append -f \"PostgreSQL\" PG:\"host=localhost user=".$dbuser." dbname=".$dbname."\" ".$file." -nln ".$layer_tablename;
		 exec($cmd,$output,$status);
		 if (0 != $status) {
		   die(return_error($output));
		 } else {
		     echo "Data saved successfully";
		 }
	  } else {
		 die(return_error("Error storing file"));
	  }
  }
}
?>
