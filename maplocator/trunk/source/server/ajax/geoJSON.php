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

/*
This file includes functions to  create  and fetch goeJSON output --> .json file and
*/
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
require_once 'functions.php';

function getGEOJSON($layer_tablename,$column_names,$file='',$Fixed_Columns='',$projection='900913'){
	if ($Fixed_Columns == '') {
		$Fixed_Columns = AUTO_DBCOL_PREFIX."id, ST_AsGeoJson(ST_Transform(".AUTO_DBCOL_PREFIX."topology,". $projection .")) as topology, ";
	}
	$total_column_names =  $Fixed_Columns . $column_names;
	$sql = 'select '.$total_column_names.' from  "'. $layer_tablename . '"';
	$query_args = array($total_column_names, $layer_tablename);
	$strgeoJSON='{"type": "FeatureCollection", "features": [';
	$str='';
	$arr = explode(",",$column_names);
	$cnt = count($arr);
	$data = db_query($sql);
	while($layer_obj = db_fetch_object($data)){
		$geom = $layer_obj->topology;
		$strgeoJSON .= '{"geometry": ' . $geom . ', ';
		$strgeoJSON .= '"type": "Feature", ';
		$strgeoJSON .= '"id": '.$layer_obj->{AUTO_DBCOL_PREFIX."id"}.', ';
		$strgeoJSON .= '"properties": {';
		for($i=0;$i<$cnt;$i++){
			$strgeoJSON .= '"'. trim($arr[$i]) .'": "' . $layer_obj->$arr[$i] .'",';
		}
		$strgeoJSON = substr_replace($strgeoJSON,"",-1);
		$strgeoJSON .= '}},';
	}
	$strgeoJSON = substr_replace($strgeoJSON,"",-1);
	$strgeoJSON .= ']}';
	if ($file != '') {
		$myFile = $file;
	}else{
		$myFile = "json/".$layer_tablename .".json";
	}

	$fh = fopen($myFile, 'w') or die("can't open file");

	fwrite($fh, $strgeoJSON);
	fclose($fh);

	return $myFile;
}
function createGeoJSON($layer_tablename,$column_names,$file='',$Fixed_Columns='',$projection='900913'){
	require_once 'json/include_geom.php';
	require_once 'json/geojson.php';
	require_once 'json/wkt.php';
	if ($Fixed_Columns == '') {
		$Fixed_Columns = AUTO_DBCOL_PREFIX."id, astext(ST_Transform(".AUTO_DBCOL_PREFIX."topology,". $projection .")) as topology, ";
	}
	$total_column_names =  $Fixed_Columns . $column_names;
	$sql = 'select '.$total_column_names.' from  "'. $layer_tablename . '"';
	$query_args = array($total_column_names, $layer_tablename);
	$strgeoJSON='{"type": "FeatureCollection", "features": [';
	$str='';
	$arr = explode(",",$column_names);
	$cnt = count($arr);
	$data = db_query($sql);
	while($layer_obj = db_fetch_object($data)){
		$geom_wkt = $layer_obj->topology;
		$wkt = new WKT();
		$geom = $wkt->read($geom_wkt);
		$strgeoJSON .= '{"geometry": ' . json_encode($geom->getGeoInterface()) . ', ';
		$strgeoJSON .= '"type": "Feature", ';
		$strgeoJSON .= '"id": '.$layer_obj->{AUTO_DBCOL_PREFIX."id"}.', ';
		$strgeoJSON .= '"properties": {';
		for($i=0;$i<$cnt;$i++){
			$strgeoJSON .= '"'. trim($arr[$i]) .'": "' . $layer_obj->$arr[$i] .'",';
		}
		$strgeoJSON = substr_replace($strgeoJSON,"",-1);
		$strgeoJSON .= '}},';
	}
	$strgeoJSON = substr_replace($strgeoJSON,"",-1);
	$strgeoJSON .= ']}';
	if ($file != '') {
		$myFile = $file;
	}else{
		$myFile = "json/".$layer_tablename .".json";
	}

	$fh = fopen($myFile, 'w') or die("can't open file");

	fwrite($fh, $strgeoJSON);
	fclose($fh);

	return $myFile;
}
?>
