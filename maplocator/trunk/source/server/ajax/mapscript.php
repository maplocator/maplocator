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
?>

/*
This file creates a map file dynamically. Invoked from generatemapfiles.php
*/

<?php

require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

if (PHP_OS == "WINNT" || PHP_OS == "WIN32") {
	define( "MODULE", "php_mapscript.dll" );
	// load the mapscript module
	if (!extension_loaded("MapScript")) dl(MODULE);
} else {
  dl("php_mapscript.so");
}

$FILEPATH = $_SERVER['SCRIPT_FILENAME'];

// remove the file name and append the new directory name
if (PHP_OS == "WINNT" || PHP_OS == "WIN32") {
	$pos1 = strrpos($FILEPATH,'\\');
	$FILEPATH= substr($FILEPATH,0,$pos1)."\\choropleth\\";
}else{
	$pos1 = strrpos($FILEPATH,'/');
	$FILEPATH= substr($FILEPATH,0,$pos1)."/choropleth/";
}
$base_path = base_path();
global $user;
$ret_file= array();

if(!isset($_POST['action']) || !isset($_POST['layer_tablename']) || !isset($_POST['expr']) || !isset($_POST['color']) || !isset($_POST['col']) || !isset($_POST['getinfo'])) {
  die("Required parameters not set");
}

$action = $_POST['action'];

switch($action){
	case 'getmapscript':
	 	$layer_tablename = $_POST['layer_tablename'];
  		$color = $_POST['color'];
  		$expression = $_POST['expr'];
  		$classitem = $_POST['col'];
  		$getfeatureinfocol = $_POST['getinfo'];
		$hex_colors = explode(",",$color);
  		$expressions = explode(",",$expression);
		generateMapfile($layer_tablename,$hex_colors,$expressions,$classitem,$getfeatureinfocol);
	 	break;
	default:

} // switch


function generateMapfile($layer_tablename,$hex_colors,$expressions,$classitem,$getfeatureinfocol) {
  global $FILEPATH;
  global $db_url;
  global $ret_file;
  $layer_type ='';
  $query = "select layer_type from \"Meta_Layer\" where layer_tablename='".$layer_tablename."'";
  $result = db_query($query);
  if($obj = db_fetch_object($result)){
  	switch($obj->layer_type){
		case 'POLYGON':
		case 'MULTIPOLYGON':
				$layer_type = 'polygon';
			break;
		case 'LINESTRING':
		case 'MULTILINESTRING':
				$layer_type = 'line';
			break;
		case 'POINT':
		case 'MULTIPOINT':
				$layer_type = 'point';
				break;
	}
  }
  $mapfilename = session_id().$layer_tablename;

  $dbpasswd = substr($db_url,$index2,$index2-$index1);
  $dbuser = preg_replace('/pgsql:\/\/(.*)@[^@]*/','$1',$db_url);
  $dbname = substr(strrchr($db_url,'/'),1);
  if(strpos($dbuser,':') >= 0) {
    list($dbuser,$dbpasswd) = split(":",$dbuser);
  } else {
    $dbpasswd = "";
  }
  $name = $FILEPATH.$mapfilename.".map";

  /* create a file */
  $fh = fopen($name,"w");
  fwrite($fh,"map\n");
  fwrite($fh,"end");
  fclose($fh);

  /* create new map object and set the parameters */
  $map = ms_newMapObj($name);
  $map->setExtent(60,0,100,40);
  $map->setSize(725,800);
  $map->set("units",MS_DD);
  $map->setProjection("init=epsg:4326");
  $map->set("maxsize",4096);
  $map->selectOutputFormat('PNG');
  $map->outputformat->set("transparent", MS_ON);
  $map->outputformat->set("imagemode", "rgba");
  $map->outputformat->setOption("formatoption","INTERLACE=OFF");

  //web object
  $map->setMetaData("wms_srs","epsg:4326 epsg:2805 epsg:24600 epsg:54004 EPSG:900913");

  /* set layer specific parameters */
  $layer = ms_newLayerObj($map);
  $layer->set("name",$layer_tablename);

  switch($layer_type){
		case 'polygon':
				$layer->set("type", MS_LAYER_POLYGON);
				break;
		case 'line':
				$layer->set("type", MS_LAYER_LINE);
				break;
		case 'point':
				$layer->set("type", MS_LAYER_POINT);

  }

  $layer->set("status", MS_ON);
  $layer->set("connectiontype",MS_POSTGIS);
  if("" == $dbpasswd) {
     $layer->set("connection","user=".$dbuser." dbname=".$dbname." host=localhost");
  } else {
     $layer->set("connection","user=".$dbuser." password=".$dbpasswd." dbname=".$dbname." host=localhost");
  }
  $layer->set("data","__mlocate__topology from ".$layer_tablename . " using unique __mlocate__id");
  $layer->set("classitem",trim($classitem));
  $layer->set("transparency",65);
  $layer->set("template","template.html");
  $layer->setMetaData("wms_feature_info_mime_type","text/html");
  $layer->setMetaData("wms_include_items","all");
  // loop through the list of values and create the corresponding classes
  $len = count($expressions);
  for($i = 0;$i<$len;$i++) {
    $class = ms_newClassObj($layer);
    $class->set("status",MS_ON);
    $class->set("name",urldecode($expressions[$i]));
    $class->setExpression("$expressions[$i]");

    $style = ms_newStyleObj($class);
    $rgb_color = hex2rgb($hex_colors[$i]);
    $style->color->setRGB($rgb_color[0],$rgb_color[1],$rgb_color[2]);
    $style->outlinecolor->setRGB(000,000,000);
    $style->set("angle",0);
  }
  /* set layer specific parameters for select layer*/
  $layer_select = ms_newLayerObj($map);
  $layer_select->set("name",$layer_tablename.'_select');
  switch($layer_type){
		case 'polygon':
				$layer_select->set("type", MS_LAYER_POLYGON);
				break;
		case 'line':
				$layer_select->set("type", MS_LAYER_LINE);
				break;
		case 'point':
				$layer_select->set("type", MS_LAYER_POINT);

  }
  $layer_select->set("status", MS_ON);
  //MS_LAYER_POLYGON
  $layer_select->set("connectiontype",MS_POSTGIS);
  if("" == $dbpasswd) {
     $layer_select->set("connection","user=".$dbuser." dbname=".$dbname." host=localhost");
  } else {
     $layer_select->set("connection","user=".$dbuser." password=".$dbpasswd." dbname=".$dbname." host=localhost");
  }
  $class_select = ms_newClassObj($layer_select);
  $style_select = ms_newStyleObj($class_select);
  $style_select->outlinecolor->setRGB(000,000,255);
  $style_select->set("width",3);
  $layer_select->setfilter("'__mlocate__id = %pid%'");
  $layer_select->set("data","__mlocate__topology from ".$layer_tablename . " using unique __mlocate__id");

 /* set layer specific parameters for Search layer*/
  $layer_search = ms_newLayerObj($map);
  $layer_search->set("name",$layer_tablename.'_search');

  switch($layer_type){
		case 'polygon':
				$layer_search->set("type", MS_LAYER_POLYGON);
				break;
		case 'line':
				$layer_search->set("type", MS_LAYER_LINE);
				break;
		case 'point':
				$layer_search->set("type", MS_LAYER_POINT);

  }
  $layer_search->set("status", MS_ON);
  $layer_search->set("connectiontype",MS_POSTGIS);

  if("" == $dbpasswd) {
     $layer_search->set("connection","user=".$dbuser." dbname=".$dbname." host=localhost");
  } else {
     $layer_search->set("connection","user=".$dbuser." password=".$dbpasswd." dbname=".$dbname." host=localhost");
  }
  $layer_search->set("transparency",65);
  $layer_search->set("template","template.html");
  $layer_search->setMetaData("wms_feature_info_mime_type","text/html");
  $layer_search->setMetaData("wms_include_items","all");

  $class_search = ms_newClassObj($layer_search);
  $style_search = ms_newStyleObj($class_search);
  $style_search->color->setRGB(228,245,205);
  $style_search->outlinecolor->setRGB(000,000,000);
  $style_search->set("width",3);
  $layer_search->setfilter("'__mlocate__id IN (%pid%)'");
  $layer_search->set("data","__mlocate__topology from ".$layer_tablename . " using unique __mlocate__id");


/* set layer specific parameters for Search BBOX layer*/
  $layer_searchBB = ms_newLayerObj($map);
  $layer_searchBB->set("name",$layer_tablename.'_searchBB');

  switch($layer_type){
		case 'polygon':
				$layer_searchBB->set("type", MS_LAYER_POLYGON);
				break;
		case 'line':
				$layer_searchBB->set("type", MS_LAYER_LINE);
				break;
		case 'point':
				$layer_searchBB->set("type", MS_LAYER_POINT);

  }
  $layer_searchBB->set("status", MS_ON);
  $layer_searchBB->set("connectiontype",MS_POSTGIS);

  if("" == $dbpasswd) {
     $layer_searchBB->set("connection","user=".$dbuser." dbname=".$dbname." host=localhost");
  } else {
     $layer_searchBB->set("connection","user=".$dbuser." password=".$dbpasswd." dbname=".$dbname." host=localhost");
  }

  $class_searchBB = ms_newClassObj($layer_searchBB);
  $style_searchBB = ms_newStyleObj($class_searchBB);
  $style_searchBB->outlinecolor->setRGB(000,000,255);
  $style_searchBB->set("width",3);
  $layer_searchBB->setfilter("'__mlocate__id IN (%pid%)'");
  $layer_searchBB->set("data","__mlocate__topology from ".$layer_tablename . " using unique __mlocate__id");


  $map->save($name);
  echo $name;
  /* return file path */
}

function hex2rgb($color) {
  if($color[0] == '#')
    $color = substr($color, 1);
  if (strlen($color) == 6)
    list($r, $g, $b) = array($color[0].$color[1],$color[2].$color[3],$color[4].$color[5]);
  elseif (strlen($color) == 3)
    list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
  else
    return false;

  $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
  return array($r, $g, $b);
}

?>
