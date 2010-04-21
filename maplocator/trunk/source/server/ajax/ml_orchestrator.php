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
 This is the landing page for every ajax request. The orchestrator, based on the request, would include appropriate php file and delegate the request and return the result.
 **/
require_once('ml_header.php');

// Auto load used classes
function __autoload($class_name) {
  require_once 'Class_' . $class_name . '.php';
}

$action='';
if (isset($_REQUEST['action'])) {
  $action=$_REQUEST['action'];
}
else {
  die('Error: Action parameter not set in the request');
}

// Depending on the request include appripriate php file and return the result
switch ($action) {
  // save_info.php starts
  case 'savelayerfeaturedata':
    require_once("save_info.php");
    if (isset($_REQUEST['edit-__id']) && $_REQUEST['edit-__id'] == "") {
      runtimeSave($_REQUEST['layer_tablename'], TABLE_TYPE_LAYER, $_REQUEST['topology']);
    }
    else {
      runtimeSave($_REQUEST['layer_tablename'], TABLE_TYPE_LAYER);
    }
    break;

  case 'saveLinkData':
    require_once("save_info.php");
    runtimeSave($_REQUEST['link_tablename'], TABLE_TYPE_LINK);
    break;

  case 'saveMetaLayer':
    require_once("save_info.php");
    $fields=array();
    $layer_tablename=$_REQUEST['layer_tablename'];
    $Request=$_REQUEST;
    $result=Meta_Layer::saveMetaLayer($Request, $layer_tablename);
    print $result;
    break;

  case 'saveLayerPermissions':
    require_once("save_info.php");
    $for_role=$_REQUEST['for_role'];
    if ($for_role == '') {
      die("Error: Required parameters not set");
    }
    $Request=$_REQUEST;
    print saveLayerPermissions($Request, $for_role);
    break;

  // save_info.php ends

  //category.php starts
  case 'getcategory':
    require_once("category.php");
    $theme_type=1;
    if (isset($_REQUEST['theme_type'])) {
      $theme_type=$_REQUEST['theme_type'];
    }
    $themeTreeId='themeTree' . $theme_type;
    $themeTree=array();
    if (isset($_COOKIE[$themeTreeId])) {
      $themeTree=unserialize($_COOKIE[$themeTreeId]);
    }
    $layersChecked=array();
    if (isset($_COOKIE['layersChecked'])) {
      $layersChecked=explode(":", $_COOKIE['layersChecked']);
    }
    $source=$_REQUEST['root'];
    $result=getTheme($theme_type, $themeTree, $source);
    print $result;
    break;

  case 'legend':
    require_once("category.php");
    if (isset($_REQUEST['legend'])) {
      $legendlayer=$_REQUEST['legend'];
      print getLayerIconUrl($legendlayer);
    }
    break;

  // category.php ends
  //MultiLayerSearch.php starts
  case 'searchByFeature':
    require_once("MultiLayerSearch.php");
    $feature_ids="";
    $searchDist="";
    $layers_list="";
    if (isset($_REQUEST['featureids'])) {
      $feature_ids=$_REQUEST['featureids'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    if (isset($_REQUEST['searchDist'])) {
      $searchDist=$_REQUEST['searchDist'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    if (isset($_REQUEST['layers_list'])) {
      $layers_list=$_REQUEST['layers_list'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    # layer_tablenames are seperated by comma
    $layersList=split(",", $layers_list);
    $featureIDs=split(",", $feature_ids);
    foreach ($featureIDs as $fid) {
      $opFidIter=0;
      searchByFeature($fid, find_layer_type($layersList[0]), $searchDist, $layersList);
    }
    getLayerData($opFeatureids, $layersList);
    break;

  case 'searchByBBOX':
    require_once("MultiLayerSearch.php");
    $bbox="";
    $layers_list="";
    if (isset($_REQUEST['bbox'])) {
      $bbox=$_REQUEST['bbox'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    if (isset($_REQUEST['layers_list'])) {
      $layers_list=$_REQUEST['layers_list'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    $layersList=split(",", $layers_list);
    getBBOXSearchData($bbox, $layersList);
    break;
  case 'getBBOXfids':
    require_once("MultiLayerSearch.php");
    $bbox="";
    $layer="";
    if (isset($_REQUEST['bbox'])) {
      $bbox=$_REQUEST['bbox'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    if (isset($_REQUEST['layer_name'])) {
      $layer=$_REQUEST['layer_name'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    getBBOXfids($bbox, $layer);
    break;

  case 'searchByAttr':
    require_once("MultiLayerSearch.php");
    $whrClause="";
    $layers_list="";
    if (isset($_REQUEST['whrClause'])) {
      $whrClause=$_REQUEST['whrClause'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    if (isset($_REQUEST['layers_list'])) {
      $layers_list=$_REQUEST['layers_list'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    # layer_tablenames are seperated by comma
    $layersList=split(",", $layers_list);

    searchByAttr($whrClause, $layersList);
    getLayerData($opFeatureids, $layersList);
    break;

  case 'searchByQuery':
    require_once("MultiLayerSearch.php");
    $whrClause="";
    $layers_list="";
    if (isset($_REQUEST['whrClause'])) {
      $whrClause=$_REQUEST['whrClause'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    if (isset($_REQUEST['layer'])) {
      $layers_list=getLayerTablename($_REQUEST['layer']);
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    # layer_tablenames are seperated by comma
    $layersList=split(",", $layers_list);
    searchByAttr($whrClause, $layersList);
    getLayerData($opFeatureids, $layersList);
    break;

  case 'searchByStr':
    require_once("MultiLayerSearch.php");
    $srchStr="";
    if (isset($_REQUEST['srchStr'])) {
      $srchStr=$_REQUEST['srchStr'];
    }
    searchByStr($srchStr);
    break;

  case 'getLayerAttr':
    $layers_list="";
    if (isset($_REQUEST['layers_list'])) {
      $layers_list=$_REQUEST['layers_list'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    # layer_tablenames are seperated by comma
    $layersList=split(",", $layers_list);
    getLayerAttr($layersList);
    break;

  case 'getAllLayers':
    require_once("MultiLayerSearch.php");
    getAllLayers();
    break;

  case 'getLayerName':
    require_once("MultiLayerSearch.php");
    $layer='';
    $json_arr=Array();
    if (isset($_REQUEST['layer'])) {
      $layer=$_REQUEST['layer'];
    }
    $layer_name=getLayerName($layer);
    $json_arr['name']=$layer_name;
    $json_arr['layer']=$layer;
    $encoded=json_encode($json_arr);
    print $encoded;
    break;

  case 'getBoxLayers':
    require_once("MultiLayerSearch.php");
    $bbox="";
    if (isset($_REQUEST['bbox'])) {
      $bbox=$_REQUEST['bbox'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    getBoxLayers($bbox);
    break;

  case 'getLayerColumns':
    require_once("MultiLayerSearch.php");
    $layer='';
    if (isset($_REQUEST['layer'])) {
      $layer=$_REQUEST['layer'];
    }
    $layer=str_replace('_and_', '&', $layer);
    $layer_tablename=getLayerTablename($layer);
    $output=getLayerColumns($layer_tablename);
    print $output;
    break;

  case 'getMinMaxForColumn':
    require_once("MultiLayerSearch.php");
    $layer='';
    $json_arr=Array();
    $col='';
    if (isset($_REQUEST['layer'])) {
      $layer=$_REQUEST['layer'];
    }
    if (isset($_REQUEST['column'])) {
      $col=$_REQUEST['column'];
    }
    $layer=str_replace('_and_', '&', $layer);
    $layer_tablename=getLayerTablename($layer);
    $output=getMinMaxForColumn($layer_tablename, $col);
    print $output;
    break;

  case 'getDataForMapSearch':
    require_once("MultiLayerSearch.php");
    $layer='';
    if (isset($_REQUEST['layer'])) {
      $layer=$_REQUEST['layer'];
    }
    $whrClause="";
    if (isset($_REQUEST['whrClause'])) {
      $whrClause=$_REQUEST['whrClause'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    $layer=str_replace('_and_', '&', $layer);
    $layer_tablename=getLayerTablename($layer);
    $output=getSearchDataForMap($layer_tablename, $whrClause);
    print $output;
    break;

  case 'getGraph':
    require_once("MultiLayerSearch.php");
    $layer='';
    if (isset($_REQUEST['layer'])) {
      $layer=$_REQUEST['layer'];
    }
    $X="";
    $Y="";
    if (isset($_REQUEST['X'])) {
      $X=$_REQUEST['X'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    if (isset($_REQUEST['Y'])) {
      $Y=$_REQUEST['Y'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    $layer=str_replace('_and_', '&', $layer);
    $layer_tablename=getLayerTablename($layer);
    $output=getGraphData($layer_tablename, $X, $Y);
    print $output;
    break;

  // /*start*/ These requests are specific to UAP (category structure)
  case 'getCity':
    require_once("MultiLayerSearch.php");
    $cid=$_REQUEST['cityName'];
    $result=getCity($cid);
    print $result;
    break;

  case 'getCountry':
    require_once("MultiLayerSearch.php");
    $cName=$_REQUEST['continentName'];
    $result=getCountry($cName);
    print $result;
    break;

  case 'getCategory':
    require_once("MultiLayerSearch.php");
    print getCategory();
    break;

  case 'getContinent':
    require_once("MultiLayerSearch.php");
    print getContinent();
    break;

  case 'getTheme':
    require_once("MultiLayerSearch.php");
    $level=$_REQUEST['level'];
    $name=$_REQUEST['themeName'];
    $city_name=$_REQUEST['cityName'];
    $result=getTheme($name, $city_name, $level);
    print $result;
    break;

  //MultiLayerSearch.php ends
  //printPDF.php starts
  case 'printPDF':
    require_once("printPDF.php");
    $json_pdf=$_REQUEST['json_PDF'];
    $pdfobj=_json_decode($json_pdf);
    $pdf_file_name=printFPDF($pdfobj);
    $pdf_URL=base_path() . 'pdf/' . $pdf_file_name;
    $json_arr=array();
    $json_arr['pdf_url']=$pdf_URL;
    $json=json_encode($json_arr);
    print $json;
    break;

  //printPDF.php ends
  //search.php starts
  case 'getlayertablename':
    require_once("search.php");
    $q=$_GET['layername'];
    $layer_names="'";
    $layer_names.=str_replace(",", "','", $q);
    $layer_names.="'";
    $result=getLayerTablenames($layer_names);
    print $result;
    break;

  case 'validation':
    require_once("search.php");
    $result=getValidationData();
    print $result;
    break;

  case 'getFeaturesForTimeLine':
    require_once("search.php");
    $layer_tablename=$_REQUEST['layer_tablename'];
    $tlCol=$_REQUEST['tlCol'];
    $tlStartDate=$_REQUEST['tlStartDate'];
    $tlEndDate=$_REQUEST['tlEndDate'];
    $BBOX=$_REQUEST['BBOX'];
    $result=getFeaturesForTimeLine($layer_tablename, $tlCol, $tlStartDate, $tlEndDate, $BBOX);
    print $result;
    break;

  //search.php ends
  //symbology.php starts
  case 'getCategories':
    require_once("symbology.php");
    $filter=$_GET['filter'];
    $layer=$_GET['layer'];
    $filter=str_replace("'", "", $filter);
    $result=getCategoriesForFilter($layer, $filter);
    echo $result;
    break;

  case 'getlut_color':
    require_once("symbology.php");
    $filter=$_GET['filter'];
    $layer=$_GET['layer'];
    $filter=str_replace("'", "", $filter);
    print getlutJSON($layer, $filter);
    break;

  case 'getFilterByColumn':
    require_once("symbology.php");
    $layer_tablename=$_GET['layer_tablename'];
    $result=getFilterByColumn($layer_tablename);
    print $result;
    break;

  case 'getSizeByJSON':
    require_once("symbology.php");
    $filter=$_GET['filter'];
    $layer=$_GET['layer'];
    $title=$_GET['title'];
    $result=getSizeByJSON($filter, $layer, $title);
    print $result;
    break;

  case 'getColorByLUT':
    require_once("symbology.php");
    $filter=$_GET['filter'];
    $layer=$_GET['layer'];
    $filter=str_replace("'", "", $filter);
    $result=getColorByLUT($filter, $layer);
    print $result;
    break;

  case 'setColorByLUT':
    require_once("symbology.php");
    $filter=$_REQUEST['filter'];
    $filter=str_replace("'", "", $filter);
    $layer=$_REQUEST['layer'];
    $query_type=$_REQUEST['qtype'];
    $values=utf8_encode($_REQUEST['values']);
    $values=str_replace(" AND ", " & ", $values);
    $result=setColorByLUT($filter, $layer, $query_type, $values);
    print $result;
    break;

  case 'getLegend':
    require_once("symbology.php");
    require_once 'xmlfunctions.php';
    header("Content-Type: text/xml");
    $layer=$_REQUEST['layer'];
    $result=getLegend($filter, $layer);
    print $result;
    break;

  case 'getLegendColumns':
    require_once("symbology.php");
    require_once 'xmlfunctions.php';
    header("Content-Type: text/xml");
    $layer=$_REQUEST['layer'];
    $result=getLegendColumns($layer);
    print $result;
    break;

  case 'symbolgylayer':
    require_once("symbology.php");
    $layer=$_REQUEST['layer'];
    $result=getsymbolgylayer($layer);
    print $result;
    break;

  //symbology.php ends
  //mapscript.php starts
  case 'getmapscript':
    require_once("mapscript.php");
    $layer_tablename=$_POST['layer_tablename'];
    $color=$_POST['color'];
    $expression=$_POST['expr'];
    $classitem=$_POST['col'];
    $getfeatureinfocol=$_POST['getinfo'];
    $hex_colors=explode(",", $color);
    $expressions=explode(",", $expression);
    generateMapfile($layer_tablename, $hex_colors, $expressions, $classitem, $getfeatureinfocol);
    break;

  //mapscript.php ends
  //mapdata.php starts
  case 'get':
    require_once("mapdata.php");
    $table='';
    if (isset($_REQUEST['layer_name'])) {
      $table=$_REQUEST['layer_name'];
    }
    $BBOX=$_REQUEST['BBOX'];

    $search_ids='';
    $tlStartDate='';
    $tlEndDate='';
    if (isset($_REQUEST['SearchIds'])) {
      $search_ids=$_REQUEST['SearchIds'];
    }
    $tl_col='';
    if (isset($_REQUEST['tlCol'])) {
      $tl_col=$_REQUEST['tlCol'];
      $tlStartDate=$_REQUEST['tlStartDate'];
      $tlEndDate=$_REQUEST['tlEndDate'];
    }
    getMapPoints($table, $BBOX, $search_ids, $tl_col, $tlStartDate, $tlEndDate);
    //print $result;
    break;

  case 'getDOD':
    require_once("mapdata.php");
    $table=$_REQUEST['layer_name'];
    $fids=$_REQUEST['fids'];
    getDOD($table, $fids);
    break;

  case 'getmaxzoom':
    require_once("mapdata.php");
    $table=$_REQUEST['layer_name'];
    $result=getMaxZoomLevel($table);
    print $result;
    break;

  case 'getlayerextent':
    require_once("mapdata.php");
    $table=$_REQUEST['layer_name'];
    $fids='';
    if (isset($_REQUEST['fids'])) {
      $fids=$_REQUEST['fids'];
    }
    $layertype=getLayerType($table);
    if ($fids != '') {
      print getLayerExtent($table, $layertype, $fids);
    }
    else {
      print getLayerExtent($table, $layertype);
    }

    break;

  case 'getfeaturecount':
    require_once("mapdata.php");
    $table=$_REQUEST['layer_name'];
    $result=getFeatureCountForLayer($table);
    print $result;
    break;

  case 'getDODCount':
    require_once("mapdata.php");
    $table=$_REQUEST['layer_name'];
    $BBOX=$_REQUEST['BBOX'];
    $fids='';
    if (isset($_REQUEST['fids'])) {
      $fids=$_REQUEST['fids'];
    }
    $result=getDODCount($table, $BBOX, $fids);
    print $result;
    break;

  case 'getDODForPolygon':
    require_once("mapdata.php");
    $table=$_REQUEST['layer_name'];
    $BBOX=$_REQUEST['BBOX'];
    getDODForPolygon($table, $BBOX);
    break;

  //mapdata.php ends

  // LayerData.php starts
  case 'getLayerDataSummary':
    /* Show feature popup */
    $layer_tablename=check_layer_tablename_provided();

    $row_id=NULL;
    if (isset($_REQUEST['row_id'])) {
      $row_id=$_REQUEST['row_id'];
    }

    $point=NULL;
    if (isset($_REQUEST['point'])) {
      $point=$_REQUEST['point'];
    }

    if ($row_id === NULL && $point === NULL) {
      die('Required parameters are not set');
    }

    require_once("LayerData.php");
    get_layer_data_summary($layer_tablename, $row_id, $point);
    break;

  case 'getLayerDataDetails':
    list($layer_tablename, $row_id)=check_layer_tablename_and_row_id_provided();

    $hasmenu=false;
    if (isset($_REQUEST['hasmenu'])) {
      $hasmenu=$_REQUEST['hasmenu'];
    }

    require_once("LayerData.php");
    get_layer_data_details($layer_tablename, $row_id, $hasmenu);
    break;

  case 'getLayerData':
    /* Code to retrieve layer data to display on the page */
    $layer_tablename=check_layer_tablename_provided();

    $row_id=NULL;
    if (isset($_REQUEST['row_id'])) {
      $row_id=$_REQUEST['row_id'];
    }

    $point=NULL;
    if (isset($_REQUEST['point'])) {
      $point=$_REQUEST['point'];
    }

    $tabexists=false;
    if (isset($_REQUEST['tabsexist'])) {
      $tabexists=$_REQUEST['tabsexist'];
    }

    require_once("LayerData.php");
    get_layer_data($layer_tablename, $row_id, $point, $tabexists);
    break;

  case 'getLinkTableEntries':
    if (isset($_REQUEST['layer_tablename'], $_REQUEST['row_id'], $_REQUEST['link_tablename'])) {
      $layer_tablename=$_REQUEST['layer_tablename'];
      $row_id=$_REQUEST['row_id'];
      $link_tablename=$_REQUEST['link_tablename'];
    }
    else {
      die('Required parameters are not set');
    }
    require_once("LayerData.php");
    get_link_table_entries($layer_tablename, $row_id, $link_tablename);
    break;

  case 'getLinkTableEntry':
    list($layer_tablename, $row_id)=check_layer_tablename_and_row_id_provided();

    require_once("LayerData.php");
    get_link_table_entry($layer_tablename, $row_id);
    break;

  case 'getLayerTableSchema':
    $layer_tablename=check_layer_tablename_provided();

    $row_id=NULL;
    if (isset($_REQUEST['row_id'])) {
      $row_id=$_REQUEST['row_id'];
    }

    $topology=NULL;
    if (isset($_REQUEST['topology'])) {
      $topology=$_REQUEST['topology'];
    }

    require_once("LayerData.php");
    get_layer_table_schema($layer_tablename, $row_id, $topology);
    break;

  case 'getLayers':
    if (isset($_REQUEST['theme_id'])) {
      $theme_id=$_REQUEST['theme_id'];
    }

    require_once("LayerData.php");
    get_layers($theme_id);
    break;

  case 'getLayerDetails':
    $layer_tablename=check_layer_tablename_provided();

    $fids=NULL;
    if (isset($_REQUEST['fids'])) {
      $fids=$_REQUEST['fids'];
    }

    require_once("LayerData.php");
    get_layer_details($layer_tablename, $fids);
    break;

  case 'getLinkTableSchema':
    if (isset($_REQUEST['link_tablename'], $_REQUEST['linked_column'], $_REQUEST['linked_value'])) {
      $link_tablename=$_REQUEST['link_tablename'];
      $linked_column=$_REQUEST['linked_column'];
      $linked_value=$_REQUEST['linked_value'];
    }
    else {
      die('Required parameters not set.');
    }

    $record_type_id=NULL;
    if (isset($_REQUEST['record_type_id'])) {
      $record_type_id=$_REQUEST['record_type_id'];
    }

    $row_id=NULL;
    if (isset($_REQUEST['row_id'])) {
      $row_id=$_REQUEST['row_id'];
    }

    require_once("LayerData.php");
    get_link_table_schema($link_tablename, $linked_column, $linked_value, $record_type_id, $row_id);
    break;

  case 'getResourceTableEntry':
    if (isset($_REQUEST['link_tablename'], $_REQUEST['linked_column'], $_REQUEST['linked_value'])) {
      $resource_tablename=$_REQUEST['resource_tablename'];
      $resource_column=$_REQUEST['resource_column'];
      $value=str_replace("'", "''", $_REQUEST['value']);
    }
    else {
      die('Required parameters not set.');
    }

    require_once("LayerData.php");
    get_resource_table_entry($resource_tablename, $resource_column, $value);
    break;

  case 'getLayerMetadata':
    if (isset($_REQUEST['layer_tablename'])) {
      $layer_tablename=$_REQUEST['layer_tablename'];
    }
    else {
      die('Required parameters not set.');
    }

    $hasmenu=0;
    if (isset($_REQUEST['hasmenu'])) {
      $hasmenu=$_REQUEST['hasmenu'];
    }

    require_once("LayerData.php");
    get_layer_metadata($layer_tablename, $hasmenu);
    break;

  case "editLayerPermissions":
    if (isset($_REQUEST['layer_tablename'])) {
      $layer_tablename=$_REQUEST['layer_tablename'];
    }
    else {
      die('Required parameters not set.');
    }

    $for_role=NULL;
    if (isset($_REQUEST['for_role'])) {
      $for_role=$_REQUEST['for_role'];
    }

    require_once("LayerData.php");
    edit_layer_permissions($layer_tablename, $for_role);
    break;

  case "editMetaLayer":
    if (isset($_REQUEST['layer_tablename'])) {
      $layer_tablename=$_REQUEST['layer_tablename'];
    }
    else {
      die('Required parameters not set.');
    }
    require_once("LayerData.php");
    edit_meta_layer($layer_tablename);
    break;

  case "clearPopupUIMenu":
    require_once("LayerData.php");
    clear_popup_ui_menu();
    break;

  case "getMedia":
    if (isset($_REQUEST['layer_tablename'], $_REQUEST['id'], $_REQUEST['media_column'])) {
      $layer_tablename=$_REQUEST['layer_tablename'];
      $row_id=$_REQUEST['id'];
      $media_column=$_REQUEST['media_column'];
    }
    else {
      die('Required parameters not set.');
    }
    require_once("LayerData.php");
    get_media($layer_tablename, $row_id, $media_column);
    break;

  case "validateFeature":
    if (isset($_REQUEST['layer_tablename'], $_REQUEST['id']) && $_REQUEST['layer_tablename'] != "" && $_REQUEST['id'] != "") {
      $layer_tablename=$_REQUEST['layer_tablename'];
      $row_id=$_REQUEST['id'];
    }
    else {
      die("Required parameters not set.");
    }

    require_once("LayerData.php");
    validate_feature($layer_tablename, $row_id);
    break;

  case "getParticipatoryLayers":
    require_once("LayerData.php");
    get_participatory_layers();
    break;

  case "getDownloadUrl":
    if (isset($_REQUEST['layer_tablename'], $_REQUEST['format'])) {
      $layer_tablename=$_REQUEST['layer_tablename'];
      $format=$_REQUEST['format'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    require_once("LayerData.php");
    get_download_url($layer_tablename, $format);
    break;

  case "getDownloadFormats":
    if (isset($_REQUEST['layer_tablename'])) {
      $layer_tablename=$_REQUEST['layer_tablename'];
    }
    else {
      die(return_error('Required parameters are not set'));
    }
    require_once("LayerData.php");
    get_download_formats($layer_tablename);
    break;

  // LayerData.php ends
  default:
    die('Error: Action not implemented');
}
// switch
function check_layer_tablename_provided() {
  if (isset($_REQUEST['layer_tablename']) && !empty($_REQUEST['layer_tablename'])) {
    return $_REQUEST['layer_tablename'];
  }
  else {
    die('Required parameters not set');
  }
}

function check_layer_tablename_and_row_id_provided() {
  if (isset($_REQUEST['layer_tablename'], $_REQUEST['row_id']) && !empty($_REQUEST['layer_tablename']) && !empty($_REQUEST['row_id'])) {
    return array($_REQUEST['layer_tablename'], $_REQUEST['row_id']);
  }
  else {
    die('Required parameters not set');
  }
}
?>
