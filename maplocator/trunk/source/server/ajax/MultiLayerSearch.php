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

/**
This file includes all search realated functionality
**/
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

define("AUTO_DBCOL_PREFIX", "__mlocate__");



// each index stores the featureids(seperated by comma) of each layer corresponding to layersList, used as global variable
$opFeatureids=array();
$opFidIter=0;

function find_layer_type($layer) {
  $query = "SELECT layer_type FROM \"Meta_Layer\" WHERE layer_tablename='%s'";
  $result_db = db_query($query, $layer);
  $result = db_fetch_object($result_db);
  return $result->layer_type;
}

function find_featureids($feature_id, $toplayer, $layer, $searchDist, $spatial_join) {

  $str1 = "SELECT u.".AUTO_DBCOL_PREFIX."id, astext(u.".AUTO_DBCOL_PREFIX."topology) as latlong FROM ".$layer." as u, ".$toplayer." as v";
  $str2 = "where v.".AUTO_DBCOL_PREFIX."id = ".$feature_id;
  $str3="";
  if ($spatial_join == "WITHIN") {
    $str3 = "and ST_DWithin(v.".AUTO_DBCOL_PREFIX."topology, u.".AUTO_DBCOL_PREFIX."topology,".$searchDist.")";
  }elseif ($spatial_join == "INTERSECT") {
    $str3 = "and ST_Intersects(u.".AUTO_DBCOL_PREFIX."topology, v.".AUTO_DBCOL_PREFIX."topology)";
  }
  $query = $str1." ".$str2." ".$str3;
  $result_db = db_query($query);
  $temp="";
  while ($result = db_fetch_object($result_db)) {
    $temp=$result->{AUTO_DBCOL_PREFIX."id"}.",".$temp;
  }
  global $opFeatureids, $opFidIter;
  $opFeatureids[$opFidIter] = $opFeatureids[$opFidIter].",".rtrim($temp,",");
  $opFidIter++;
}

function searchByFeature($feature_id, $toplayerType, $searchDist, $layersList) {

  if ($toplayerType == "POINT") {
    foreach ($layersList as $layer) {
      $layer_type = find_layer_type($layer);
      if ($layer_type == "POINT") {
        find_featureids($feature_id, $layersList[0], $layer, $searchDist, "WITHIN");
      }elseif ($layer_type == "MULTIPOLYGON"){
        find_featureids($feature_id, $layersList[0], $layer, $searchDist, "INTERSECT");
      }else {
        echo "NOT IMPLEMENTED3";
      }
    }
  } elseif ($toplayerType == "MULTIPOLYGON") {
    foreach ($layersList as $layer) {
      $layer_type = find_layer_type($layer);
      if ($layer_type == "POINT"){
        find_featureids($feature_id, $layersList[0], $layer, $searchDist, "INTERSECT");
      }else if ($layer_type == "MULTIPOLYGON"){
        find_featureids($feature_id, $layersList[0], $layer, $searchDist, "INTERSECT");
      }else {
        echo "NOT IMPLEMENTED2";
      }
    }
  } else {
    echo "NOT IMPLEMENTED1";
  }
}

function searchByAttr($whrClause, $layersList) {
  global $opFeatureids, $opFidIter;
  $whrClauseStr="";
  if ($whrClause != "" ) {
    $whrClauseStr = "where ".$whrClause;
  }
  foreach ($layersList as $layer) {
    $query = "SELECT ".AUTO_DBCOL_PREFIX."id FROM ".$layer." ".$whrClauseStr;
	$result_db = db_query($query);
    if($result_db != false) {
      $temp="";
      while ($result = db_fetch_object($result_db)) {
       $temp=$result->{AUTO_DBCOL_PREFIX."id"}.",".$temp;
      }
      $opFeatureids[$opFidIter] = rtrim($temp,",");
    } else {
      $opFeatureids[$opFidIter] = "";
    }
    $opFidIter++;
  }
}

function getLayerData($opFeatureids, $layersList) {
  $json_arr1 = array();
  for($i=0; $i<count($opFeatureids); $i++) {
    $json_arr2 = array();
    $json_rowIter = 0;
    $query = "SELECT column_name FROM information_schema.columns WHERE table_name='".$layersList[$i]."' AND column_name not like '__mlocate___%'";
    $result_db = db_query($query);
    $temp="";
    while ($result = db_fetch_object($result_db)) {
      $temp='"'.$result->column_name.'",'.$temp;
    }
    $col_list = rtrim($temp,",");
    $col_list .= ',"'.AUTO_DBCOL_PREFIX.'id"';
    if($opFeatureids[$i] != "") {
      $query = "SELECT ".$col_list." FROM ".$layersList[$i]." WHERE ".AUTO_DBCOL_PREFIX."id IN (".trim($opFeatureids[$i],",").")";
      $result_db = db_query($query);

      while ($result = db_fetch_object($result_db)) {
        $col_list_sp = split(",",$col_list);
        $col_list_sp = str_replace('"','' ,$col_list_sp );
        foreach ($col_list_sp as $col_name) {
          $json_arr2[$col_name][$json_rowIter] = $result->$col_name;
        }
        $json_rowIter++;
      }
    }
    $lyr_name = getLayerName($layersList[$i]);
    $json_arr1[$layersList[$i].":". $lyr_name] = $json_arr2;
  }
  $encoded = json_encode($json_arr1);
  echo $encoded;
}
function getSearchDataForMap($layer,$where_clause){
	$col = AUTO_DBCOL_PREFIX."id";
	if ($where_clause != "" ) {
    	$where_clause = "where ".$where_clause;
  	}
	$query = "SELECT ".$col." FROM ".$layer." ".$where_clause;
	$result_db = db_query($query);
	$json_arr = array();
	$fids='';
    while ($result = db_fetch_object($result_db)) {
		$fids .= $result->{$col} . ',';
    }
	if ($fids != '') {
		$fids = substr_replace($fids,"",-1);
	}
	$json_arr[$layer] = $fids;
	return json_encode($json_arr);
}
function getLayerName($layer_tablename){
	$query = 'select layer_name from "Meta_Layer" where layer_tablename =\''. $layer_tablename .'\'';
	$result_db = db_query($query);
	$layer_name ='';
	if($result = db_fetch_object($result_db)){
		$layer_name = $result->layer_name;
	}
	return $layer_name;
}
function getLayerTablename($layer_name){
	$query = 'select layer_tablename from "Meta_Layer" where layer_name =\''. $layer_name .'\'';
	$result_db = db_query($query);
	$layer_tablename ='';
	if($result = db_fetch_object($result_db)){
		$layer_tablename = $result->layer_tablename;
	}
	return $layer_tablename;
}
function getBBOXSearchData($bbox,$layersList){
	$json_arr1 = array();
	$count = count($layersList);
	  for($i=0; $i<$count; $i++) {
	    $json_arr2 = array();
	    $json_rowIter = 0;
	    $query = "SELECT column_name FROM information_schema.columns WHERE table_name='".$layersList[$i]."' AND column_name not like '__mlocate___%'";
	    $result_db = db_query($query);
	    $temp="";
	    while ($result = db_fetch_object($result_db)) {
	      $temp='"'.$result->column_name.'",'.$temp;
	    }
	    $col_list = rtrim($temp,",");
	    $col_list .= ',"'.AUTO_DBCOL_PREFIX.'id"';
		$query = "SELECT ".$col_list." FROM ".$layersList[$i]." WHERE ".AUTO_DBCOL_PREFIX."topology @ setSRID('BOX3D(".trim($bbox,"\"").")'::box3d, (select srid from geometry_columns where f_table_name = '".$layersList[$i]."'))";

	      $result_db = db_query($query);

	      while ($result = db_fetch_object($result_db)) {
	        $col_list_sp = split(",",$col_list);
	        $col_list_sp = str_replace('"','' ,$col_list_sp );
	        foreach ($col_list_sp as $col_name) {
	          $json_arr2[$col_name][$json_rowIter] = $result->$col_name;
	        }
	        $json_rowIter++;
	      }

		$lyr_name = getLayerName($layersList[$i]);
	    $json_arr1[$layersList[$i].":". $lyr_name] = $json_arr2;

	  }
  $encoded = json_encode($json_arr1);
  echo $encoded;
}
function getBBOXfids($bbox,$layer){
	$query = "SELECT ".AUTO_DBCOL_PREFIX."id FROM ".$layer." WHERE ".AUTO_DBCOL_PREFIX."topology @ setSRID('BOX3D(".trim($bbox,"\"").")'::box3d, (select srid from geometry_columns where f_table_name = '".$layer."'))";
	$result_db = db_query($query);
	$fids="";
	while ($result = db_fetch_object($result_db)) {
	     $fids .= $result->{AUTO_DBCOL_PREFIX."id"}.",";
	}
	if ($fids != '') {
		$fids = substr_replace($fids,"",-1);
	}
	echo $fids;
}
function getLayerAttr($layersList) {
  $json_layer_attrs = array();
  foreach ($layersList as $layer) {
    $query = "SELECT column_name,data_type FROM information_schema.columns WHERE table_name='".$layer."' AND column_name not like '__mlocate___%'";
    $result_db = db_query($query);
    $temp="";
    while ($result = db_fetch_object($result_db)) {
      if((stristr($result->data_type,'char')) or (stristr($result->data_type,'text')))
        $temp=$result->column_name.";1,".$temp;
      else
        $temp=$result->column_name.";0,".$temp;
    }
    $json_layer_attrs[$layer] = rtrim($temp,",");
  }
  $encoded = json_encode($json_layer_attrs);
  echo $encoded;
}
function getLayerColumns($layer_tablename){
	$numeric_data_types ="bigint,double precision,integer,numeric,smallint";
	$numeric_array = explode(",",$numeric_data_types);
	$json_arr =  array();
	$query = "SELECT column_name,data_type FROM information_schema.columns WHERE table_name='".$layer_tablename."' AND column_name not like '__mlocate___%' ";
  $result_db = db_query($query);
  $all_cols="";
  $numeric_cols="";
  while ($result = db_fetch_object($result_db)) {
    if(in_array($result->data_type,$numeric_array)){
      $numeric_cols .= $result->column_name. ',';
    }
    $all_cols .= $result->column_name. ',';
  }
	$json_arr['numeric_cols'] = substr_replace($numeric_cols,"" ,-1);
	$json_arr['all_cols'] = substr_replace($all_cols,"" ,-1);
	$encoded = json_encode($json_arr);

  return $encoded;
}

function getMinMaxForColumn($layer_tablename,$col){
	$query = 'SELECT min('.$col.') as min, max('.$col.') as max from "'.$layer_tablename.'"';
  $result_db = db_query($query);
  $json_arr =  array();
  $temp="";
  if ($result = db_fetch_object($result_db)) {
    $json_arr['min'] = $result->min;
  $json_arr['max'] = $result->max;
  }
  return json_encode($json_arr);
}

function getAllLayers() {
  $json_alllayers = array();
  $query = "SELECT layer_tablename, layer_name FROM \"Meta_Layer\" where status = 1 AND layer_type <> 'RASTER' ORDER BY layer_name ASC ";
  $result_db = db_query($query);
  while ($result = db_fetch_object($result_db)) {
    $json_alllayers[$result->layer_tablename]= $result->layer_name;
  }
  $encoded = json_encode($json_alllayers);
  echo $encoded;
}
function getBoxLayers($bbox) {
  $json_bboxlayers = array();
  $query = "SELECT layer_tablename, layer_name FROM \"Meta_Layer\" where status = 1 AND layer_type <> 'RASTER' ORDER BY layer_name ASC ";
  $result_db = db_query($query);
  while ($result = db_fetch_object($result_db)) {
    $query = "SELECT count(*) as cnt FROM ".$result->layer_tablename." as v where v.".AUTO_DBCOL_PREFIX."topology && setSRID('BOX3D(".trim($bbox,"\"").")'::box3d, (select srid from geometry_columns where f_table_name = '".$result->layer_tablename."'))";
    $res_db = db_query($query);
    $res = db_fetch_object($res_db);
    if($res->cnt >0) {
        $json_bboxlayers[$result->layer_tablename]= $result->layer_name;
    }
  }
  $encoded = json_encode($json_bboxlayers);
  echo $encoded;
}

function searchByStr($srchStr) {
  $srchStr = strtolower($srchStr);
	$srch_data = '';
	$like_condition = '';
	$srch_data = trim($srchStr);

  if(strpos($srch_data, '"') === false) {
    $srch_arr = explode(' ',$srch_data);
    $len = count($srch_arr);
    for($i=0; $i< $len; $i++) {
      if ($i == $len -1 ) {
        $like_condition.= " '%%" .$srch_arr[$i] ."%%'";
      } else {
        $like_condition.=  " '%%" . $srch_arr[$i]. "%%' OR text like";
      }
    }
  } else {
    $like_condition = "'%". str_replace('"','',$srch_data) ."%'";
	}

  $json = array();

  $query = "select x.layer_tablename, x.layer_name, y.fid";
  $query = $query." from \"Meta_Layer\" as x, (SELECT DISTINCT(fid),lid FROM search_data where text like {$like_condition} GROUP BY lid,fid) as y";
  $query = $query." where x.layer_id = y.lid";

  $result_db = db_query($query);
  while ($result = db_fetch_object($result_db)) {
      if($json[$result->layer_name] != "") {
        $json[$result->layer_name] = $json[$result->layer_name].",".$result->fid;
      } else {
        $json[$result->layer_name] = $result->layer_tablename.",".$result->fid;
      }
  }

	$encoded = json_encode($json);
	echo $encoded;
}


function getGraphData($layer_tablename,$X,$Y){
	$query = 'select '.$X .', '.$Y .' from "'.$layer_tablename.'"';
	$result_db = db_query($query);
	$json_arr = array();
	while ($result = db_fetch_object($result_db))
	{
		$json_arr[$result->{$X}] = $result->{$Y};
	}
	 return json_encode($json_arr);
}
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
function getCity($cid){

		$query = 'select theme_name from "Theme" where country_id = '.$cid;
	    $result_db = db_query($query);
	    $str ='';
	    while ($result = db_fetch_object($result_db))
		{
			$str.= trim($result->theme_name).",";
		}
		return $str;

}
function getCategory(){
	$query = 'select theme_name from "Theme" where status = 1 AND theme_type = 1';
	$result_db = db_query($query);
	$str ='';
	while ($result = db_fetch_object($result_db)){
		$str.= trim($result->theme_name).",";
	}
	return $str;

}
function getContinent(){
	$query = 'select distinct(continent_name) from "Country_Mapping"';
	$result_db = db_query($query);
	$str = '';
	while ($result = db_fetch_object($result_db)){
		$str.= trim($result->continent_name).",";
	}
	return $str;

}
function getTheme($name,$city_name,$level){
	$json_arr = array();
	if ($level == '0'){
		$query = 'select category_name from "Categories_Structure" where parent_id = category_id';

	}else{
		$query = 'select category_name from "Categories_Structure" where parent_id = (select category_id from "Categories_Structure" where category_name = \''.$name .'\') and parent_id <> category_id ';

	}
	$result_db = db_query($query);
	$themes = '';
	while ($result = db_fetch_object($result_db))
	{
		$themes .= 'T|'.trim($result->category_name).",";
	}

	if ($level != '0') {
		$query = 'select layer_name from "Meta_Layer" where layer_id IN (select layer_id from "Theme_Layer_Mapping" where category_id = (select category_id from "Categories_Structure" where category_name = \''.$name .'\') and theme_id = (select theme_id from "Theme" where theme_name = \''. $city_name .'\' and theme_type = 2) ) ';
  		$result_db = db_query($query);
		$layers = '';
		while ($result = db_fetch_object($result_db))
		{
			$layers .= 'L|'.trim($result->layer_name).",";

		}

	}
	$json_arr['level'] = $level;
	$json_arr['themes'] = $themes;

	$json_arr['layers'] = $layers;

	$encoded = json_encode($json_arr);
    return $encoded;

}
?>
