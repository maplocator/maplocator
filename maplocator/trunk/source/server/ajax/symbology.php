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
This file includes functionality for Symbology feature
*/
require_once 'functions.php';
require_once './includes/bootstrap.inc';
require_once 'geoJSON.php';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

$base_path=base_path();

function makeDirectory($path) {
  if (!is_dir($path)) {
    $oldumask = umask(0);
    mkdir($path, 0777);
    umask($oldumask);
    chmod($path, 0777); // octal; correct value of mode
  }
}
function getLUTCount($filter,$layer){
	$query = "select count(*) as total from lut_color where color_by_column ='". $filter ."' and layer_id = ( select layer_id from \"Meta_Layer\" where layer_tablename ='".$layer."' )";

	$data = db_query($query);
	if ($obj = db_fetch_object($data)) {
		return $obj->total;
	}
  	return 0;
}
function getColorByCol($layer_tablename){
	$color_by='';
	$query = "select colorby_cat_col from \"%s\" where layer_tablename = '%s'";
    $result = db_query($query,"Meta_Layer",$layer_tablename);
    if($obj = db_fetch_object($result)){
      $color_by = trim(str_replace("'","",$obj->colorby_cat_col));
    }
	return $color_by;
}
function updateMapfile($layer_tablename,$hex_colors,$expressions,$classitem) {
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

  //global $db_url;

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
  $mapfilename = $layer_tablename;

  // $dbpasswd = substr($db_url,$index2,$index2-$index1);
  // $dbuser = preg_replace('/pgsql:\/\/(.*)@[^@]*/','$1',$db_url);
  // $dbname = substr(strrchr($db_url,'/'),1);
  // if(strpos($dbuser,':') >= 0) {
    // list($dbuser,$dbpasswd) = split(":",$dbuser);
  // } else {
    // $dbpasswd = "";
  // }
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

  $layer->set("connection", getDBConnectionString());

  /*
  if("" == $dbpasswd) {
     $layer->set("connection","user=".$dbuser." dbname=".$dbname." host=localhost");
  } else {
     $layer->set("connection","user=".$dbuser." password=".$dbpasswd." dbname=".$dbname." host=localhost");
  }
  */

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
    $cat = str_replace(' COMMA ',',' ,$expressions[$i] );
    $class->set("name",urldecode($cat));
    $class->setExpression("$cat");

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

  $layer->set("connection", getDBConnectionString());

  /*
  if("" == $dbpasswd) {
     $layer_select->set("connection","user=".$dbuser." dbname=".$dbname." host=localhost");
  } else {
     $layer_select->set("connection","user=".$dbuser." password=".$dbpasswd." dbname=".$dbname." host=localhost");
  }
  */
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

  $layer->set("connection", getDBConnectionString());

  /*
  if("" == $dbpasswd) {
     $layer_search->set("connection","user=".$dbuser." dbname=".$dbname." host=localhost");
  } else {
     $layer_search->set("connection","user=".$dbuser." password=".$dbpasswd." dbname=".$dbname." host=localhost");
  }
  */

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

  $layer->set("connection", getDBConnectionString());



  $class_searchBB = ms_newClassObj($layer_searchBB);
  $style_searchBB = ms_newStyleObj($class_searchBB);
  $style_searchBB->outlinecolor->setRGB(000,000,255);
  $style_searchBB->set("width",3);
  $layer_searchBB->setfilter("'__mlocate__id IN (%pid%)'");
  $layer_searchBB->set("data","__mlocate__topology from ".$layer_tablename . " using unique __mlocate__id");


  $map->save($name);
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

function getDBConnectionString() {
  global $db_url;
  $url = parse_url($db_url);
  $conn_string = '';

  // Decode url-encoded information in the db connection string
  if (isset($url['user'])) {
    $conn_string .= ' user='. urldecode($url['user']);
  }
  if (isset($url['pass'])) {
    $conn_string .= ' password='. urldecode($url['pass']);
  }
  if (isset($url['host'])) {
    $conn_string .= ' host='. urldecode($url['host']);
  }
  if (isset($url['path'])) {
    $conn_string .= ' dbname='. substr(urldecode($url['path']), 1);
  }
  if (isset($url['port'])) {
    $conn_string .= ' port='. urldecode($url['port']);
  }

  return $conn_string;
}

function getlutJSON($layer,$filter){
	$query = "select color_by_value, color from  lut_color where layer_id = ( select layer_id from \"Meta_Layer\" where layer_tablename ='".$layer."') and color_by_column ='". $filter ."'";
	$data = db_query($query);
	$result_json = array();
	while ($obj = db_fetch_object($data))
	{
	   	$result_json[$obj->color_by_value] = $obj->color;
	}
	return json_encode($result_json);
}

function getCategoriesForFilter($layer,$filter){
	$query = "select ".$filter .",count(". $filter .") as total from ".$layer ." group by ".$filter." order by 2 desc";//  limit 5";
	$data = db_query($query);
	$result = '';
	$count =0;
	while ($obj = db_fetch_object($data))
	{
         if($obj->total > 0){
            $result .= $obj->{trim($filter)}. ':'. $obj->total.',';
            $count++;
         }
    }
  if ($count > 0) {
     $result = substr_replace($result,"",-1);
  }
  return $result;
}
function getFilterByColumn($layer_tablename){
	$color_by='';
    $size_by='';
    $cols='';
	$result_json = array();

    $query = "select title_column,colorby_cat_col,size_by from \"%s\" where layer_tablename = '%s'";
    $result = db_query($query,"Meta_Layer",$layer_tablename);
    while($obj = db_fetch_object($result)){
      $color_by = str_replace("'","",$obj->colorby_cat_col);
      $size_by = str_replace("'","",$obj->size_by);
      $result_json['color_by'] = trim($color_by);
      $result_json['size_by'] = trim($size_by);
      $result_json['title'] =  trim($obj->title_column);

    }
    $sql = 'select count(*) as count from "%s"';
	$result = db_query($sql,$layer_tablename);
    $count=0;
	if($obj = db_fetch_object($result)){
    		$count = $obj->count;
    }
	if ($color_by !='') {
		$jsonfile = "json/".$layer_tablename."_color.json";
		if(!file_exists($jsonfile)){
			if ($count < 5000) {
				$cols = $color_by;
				if ($result_json['title']!='') {
			 		$cols .= ','.str_replace("'","",$result_json['title']);
				}
			}
		}
	}
	if ($size_by !='') {
		$jsonfile = "json/".$layer_tablename."_size.json";

		if(!file_exists($jsonfile)){

    		if ($count < 5000) {
    			$projection='900913';
				$cols = $size_by;

				if ($result_json['title']!='') {
				 	$cols .= ','.str_replace("'","",$result_json['title']);
				}

				$Fixed_Columns = AUTO_DBCOL_PREFIX."id, astext(ST_Transform(Centroid(".AUTO_DBCOL_PREFIX."topology),". $projection .")) as topology, ";
				createGeoJSON($layer_tablename,$cols,$jsonfile,$Fixed_Columns);
    		}

		}
	}

  	$filesize;
  	if(file_exists($jsonfile)){
    	$filesize= filesize($jsonfile)/(1024*1024);
  	} else {

	    $filesize= '10';
  	}
	$result_json['file_size'] = $filesize;
	return json_encode($result_json);
}
function getSizeByJSON($filter,$layer,$title){
	$result_json = array();

	$cols = $filter;
	if ($title !='') {
		$cols .= ', '.$title;
	}
	$projection='900913';
	$Fixed_Columns = AUTO_DBCOL_PREFIX."id, ST_AsGeoJson(ST_Transform(Centroid(".AUTO_DBCOL_PREFIX."topology),". $projection .")) as topology, ";
	$query = "select max(%s) , min(%s) from \"%s\"" ;
    $result = db_query($query,$filter,$filter,$layer);
    if($obj = db_fetch_object($result)){
      $result_json['Max'] = $obj->max;
      $result_json['Min'] = $obj->min;
    }

	$result_json['jsonFile'] = $file;
    return json_encode($result_json);
}
function getColorByLUT($filter,$layer){
	$query_type ='';
  	$query = "select count(*) as total from lut_color where color_by_column ='". $filter ."' and layer_id = ( select layer_id from \"Meta_Layer\" where layer_tablename ='".$layer."' )";

  	$data = db_query($query);
  	if ($obj = db_fetch_object($data)) {
    	if ($obj->total > 0) {
      		$query_type = 'UPDATE';
    	} else {
      		$query_type = 'INSERT';
    	}
  	}

  	$result = $query_type . '~';
  	if ($query_type == 'UPDATE') {
    	$query = "select color_by_value, color from  lut_color where layer_id = ( select layer_id from \"Meta_Layer\" where layer_tablename ='".$layer."') and color_by_column ='". $filter ."' order by color_by_value";
    	$data = db_query($query);

    	$categories = array();
    	$colors = array();

    	while ($obj = db_fetch_object($data)) {
      		$cat = $obj->color_by_value;
      		if (strpos($cat,',') !== FALSE) {
        		$cat = str_replace(',',' COMMA ' ,$cat );
      		}
      		$categories[] = $cat;
      		$colors[] = $obj->color;
       }
   	   if (is_numeric_array($categories)) {
       		array_multisort($categories, $colors);
       } elseif (is_range_array($categories)) {
      		rangearray_multisort($categories, $colors);
       }
       $len = sizeof($categories);
       for ($i = 0; $i < $len; $i++) {
      		$result .=  $categories[$i]. ':' . $colors[$i] . ',';
       }
       $result = substr_replace($result,"",-1);

       return $result;
  	} else {
    	$query = "select ".$filter ." from ".$layer ." group by ".$filter;
    	$data = db_query($query);
	    $categories = array();
    	$colors = array();
	    while ($obj = db_fetch_object($data)) {
      		$cat = $obj->{$filter};
      		if (strpos($cat,',') !== FALSE) {
        		$cat = str_replace(',',' COMMA ' ,$cat );
      		}
      	$categories[] = $cat;
      	$colors[] = 'ff0000';
        }
    	if (is_numeric_array($categories)) {
      		array_multisort($categories, $colors);
    	} elseif (is_range_array($categories)) {
      		rangearray_multisort($categories, $colors);
    	}
    	$len = sizeof($categories);
    	for ($i = 0; $i < $len; $i++) {
      		$result .=  $categories[$i]. ':' . $colors[$i] . ',';
    	}
    	$result = substr_replace($result,"",-1);
	    return $result;
  }

}

function setColorByLUT($filter,$layer,$query_type,$values){
	$val_arr = explode(',',$values);
	$count = count($val_arr);
	$query = '';
	$color ='';
	$expressions='';
	if ($query_type == 'UPDATE') {
		for($i=0;$i<$count;$i++){
			$arr = explode(':',$val_arr[$i]);
			$color .= $arr[1] . ',';
			$expression .= $arr[0]. ',';
			$cat = str_replace("'", "''", $arr[0]);
			$cat = str_replace(' COMMA ',',',$cat );
			$query .= "update lut_color set color ='". $arr[1] . "' where ";
			$query .= "color_by_value ='". $cat."' and ";
			$query .= "layer_id = ( select layer_id from \"Meta_Layer\" where layer_tablename ='".$layer."') and ";
			$query .= "color_by_column ='". $filter ."' ;";
		}

		$result = db_query($query);
		if($result){
			// Code to update/create map file

			$color = substr_replace($color,"",-1);
			$expression = substr_replace($expression,"",-1);
			$hex_colors = explode(",",$color);
  			$expressions = explode(",",$expression);
            updateMapfile($layer,$hex_colors,$expressions,$filter);
			return "Record Saved";
		}
	}else{
		$query = "select layer_id from \"Meta_Layer\" where layer_tablename ='".$layer."'";
		$data = db_query($query);
		if($obj = db_fetch_object($data)){
			$layer_id = $obj->layer_id;
		}
		$query='';
		for($i=0;$i<$count;$i++){
			$arr = explode(':',$val_arr[$i]);
			$color .= $arr[1] . ',';
			$expression .= $arr[0]. ',';
			$cat = str_replace("'", "''", $arr[0]);
			$cat = str_replace(' COMMA ',',' ,$cat );
			$query .= "INSERT INTO lut_color(layer_id, color_by_column, color_by_value, color) VALUES (";
			$query .= $layer_id. ",'". $filter ."','" .$cat."','". $arr[1] ."');";
		}
		$result = db_query($query);
		if($result){
			// Code to update/create map file
			$color = substr_replace($color,"",-1);
			$expression = substr_replace($expression,"",-1);
			$hex_colors = explode(",",$color);
      		$expressions = explode(",",$expression);
      		updateMapfile($layer,$hex_colors,$expressions,$filter);
			return "Record Saved";
		} else {
			return "Error saving info";
		}
	}
}

function getLegend($filter,$layer){
	// create a new XML document
  	$responseDoc = new DomDocument('1.0');
	// create root node
  	$rootNode = $responseDoc->createElement('response');
  	$rootNode = $responseDoc->appendChild($rootNode);
	$query = "select count(*) as total from lut_color where color_by_column ='". $filter ."' and layer_id = ( select layer_id from \"Meta_Layer\" where layer_tablename ='".$layer."' )";
  	$data = db_query($query);
  	if ($obj = db_fetch_object($data)) {
    	if ($obj->total > 0) {
      		setNoError($responseDoc, $rootNode);
      		$layerNode = addXMLChildNode($responseDoc, $rootNode, "layer", null, array('color_by_column' => $filter));
      		$query = "select color_by_value, color from lut_color where layer_id = ( select layer_id from \"Meta_Layer\" where layer_tablename ='".$layer."') and color_by_column ='". $filter ."' order by color_by_value";
      		$data = db_query($query);
      		$categories = array();
      		$colors = array();
		    while ($obj = db_fetch_object($data)) {
        		$categories[] = $obj->color_by_value;
        		$colors[] = $obj->color;
            }
      		if (is_numeric_array($categories)) {
        		array_multisort($categories, $colors);
      		} elseif (is_range_array($categories)) {
        		rangearray_multisort($categories, $colors);
      		}
      		$len = sizeof($categories);
      		for ($i = 0; $i < $len; $i++) {
        		addXMLChildNode($responseDoc, $layerNode, "category", null, array('category_name' => $categories[$i], 'category_color' => $colors[$i]));
      		}
    	} else {
      		setError($responseDoc, $rootNode, "Color by column not set for this layer");
    	}
  	}
  	return $responseDoc->saveXML();
}

function getLegendColumns($layer){
	// create a new XML document
	$responseDoc = new DomDocument('1.0');
	// create root node
	$rootNode = $responseDoc->createElement('response');
	$rootNode = $responseDoc->appendChild($rootNode);
	$cat_col = addXMLChildNode($responseDoc, $rootNode, "color_by_columns");
	$query = "select colorby_cat_col from \"%s\" where layer_tablename = '%s'";
    $result = db_query($query,"Meta_Layer",$layer);
    if($obj = db_fetch_object($result)){
    	setNoError($responseDoc, $rootNode);
    	$col_string = str_replace("'","" ,$obj->colorby_cat_col);
    	$col_arr = explode(',',$col_string);
    	$cnt = count($col_arr);
    	for($i=0;$i<$cnt;$i++){
    		addXMLChildNode($responseDoc, $cat_col, "color_by_column", null, array('column_name' => $col_arr[$i]));
		}
    }else{
		setError($responseDoc, $rootNode, "error executing query");
	}
	return $responseDoc->saveXML();
}
function getsymbolgylayer($layer){
	$FILEPATH = $_SERVER['SCRIPT_FILENAME'];
	$sym_mapfile='';
	// remove the file name and append the new directory name
	if (PHP_OS == "WINNT" || PHP_OS == "WIN32") {
		$pos1 = strrpos($FILEPATH,'\\');
		$FILEPATH= substr($FILEPATH,0,$pos1)."\\choropleth\\";
	}else{
		$pos1 = strrpos($FILEPATH,'/');
		$FILEPATH= substr($FILEPATH,0,$pos1)."/choropleth/";
	}
    $mapfilename = session_id(). $layer .'.map';
    if (file_exists('choropleth/'.$mapfilename)) {
		$sym_mapfile = $FILEPATH . $mapfilename;
    }else{
    	//check whether any default color file has been created
		if (file_exists('choropleth/'.$layer.'.map')) {
			$sym_mapfile = $FILEPATH . $layer. '.map';
    	}else{
    		//code to generate map file with default colors from lut goes here

    	}

	}
	return $sym_mapfile;

}
?>
