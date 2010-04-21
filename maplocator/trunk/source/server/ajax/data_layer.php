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
*This file contains layer related functionality.
*
***/

require_once('ml_header.php');

function getLayerRowAttribValues($xmlDoc, &$rootNode, $paramsNode) {
  $layer_tablename = $paramsNode->getElementsByTagName('layer_tablename')->item(0)->nodeValue;
  $row_id = $paramsNode->getElementsByTagName('row_id')->item(0)->nodeValue;
  $attribs = $paramsNode->getElementsByTagName('attribs')->item(0)->nodeValue;

  if(($layer_tablename == null || $layer_tablename == '') || ($row_id == null || $row_id == '') || ($attribs == null || $attribs == '')) {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $col_db_info = getDBColDesc($layer_tablename);

    $query = 'select %s from "%s" where '.AUTO_DBCOL_PREFIX.'id = %d;';
    $result = db_query($query, array($attribs, $layer_tablename, $row_id));
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error fetching information.");
    } else {
      if ($arr = db_fetch_array($result)) {
        setNoError($xmlDoc, $rootNode);
        $lyrNode = addXMLChildNode($xmlDoc, $rootNode, "layer", null, array('tablename' => $layer_tablename, 'row_id' => $row_id));
        foreach ($arr as $key => $val) {
          addXMLChildNode($xmlDoc, $lyrNode, "attrib", null, array('key' => $key, 'value' => $val, 'label' => ($col_db_info[$key] == '' ? $key : $col_db_info[$key])));
        }
      }
    }
  }
}

function getLayerAttribValues($xmlDoc, &$rootNode, $paramsNode) {
  $layer_tablename = $paramsNode->getElementsByTagName('layer_tablename')->item(0)->nodeValue;
  $attribs = $paramsNode->getElementsByTagName('attribs')->item(0)->nodeValue;

  if(($layer_tablename == null || $layer_tablename == '') || ($attribs == null || $attribs == '')) {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $title_column = "";
    $query = 'select title_column from "Meta_Layer" where layer_tablename = \'%s\'';
    $result = db_query($query, array($layer_tablename));
    if (!$result) {
    } else {
      if($obj = db_fetch_object($result)) {
        $title_column = str_replace("'", "", $obj->title_column) . ' as title_column,';
      }
    }

    $col_db_info = getDBColDesc($layer_tablename);

    $query = 'select %s from "%s";';
    $result = db_query($query, array($title_column.$attribs, $layer_tablename));
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error fetching information.");
    } else {
      setNoError($xmlDoc, $rootNode);
      $lyrNode = addXMLChildNode($xmlDoc, $rootNode, "layer", null, array('tablename' => $layer_tablename));
      while($arr = db_fetch_array($result)) {
        $rowNode = addXMLChildNode($xmlDoc, $lyrNode, "row", null, array('label' => $arr['title_column']));
        foreach ($arr as $key => $val) {
          if($key != 'title_column') {
            addXMLChildNode($xmlDoc, $rowNode, "attrib", null, array('key' => $key, 'value' => $val, 'label' => ($col_db_info[$key] == '' ? $key : $col_db_info[$key])));
          }
        }
      }
    }
  }
}

function getFeatureInfo($xmlDoc, &$rootNode, $paramsNode) {
  $layer_tablename = $paramsNode->getElementsByTagName('layer_tablename')->item(0)->nodeValue;
  $row_id = $paramsNode->getElementsByTagName('row_id')->item(0)->nodeValue;

  if(($layer_tablename == null || $layer_tablename == '') || ($row_id == null || $row_id == '')) {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $col_db_info = getDBColDesc($layer_tablename);

    $columns_arr = array();
    $columns_arr[] = 'layer_name';
    $columns_arr[] = 'layer_type';
    $columns_arr[] = 'title_column';
    $columns_arr[] = 'license';
    $columns_arr[] = 'attribution';
    $columns_arr[] = 'summary_columns';
    $columns_arr[] = 'media_columns';
    $columns_arr[] = 'video_columns';
    $columns_arr[] = 'nid';

    $metainfo = get_values_metatable($layer_tablename, $layer_id, TABLE_TYPE_LAYER, $columns_arr);

    $layer_name = $metainfo['layer_name'];
    $layer_title_column = str_replace("'", "", $metainfo['title_column']);
    $layer_license = $metainfo['license'];
    $layer_attribution = $metainfo['attribution'];
    $summary_columns_arr = explode(",", str_replace("'", "", $metainfo['summary_columns']));
    $media_columns = $metainfo['media_columns'];
    $media_columns_arr = array();
    if($media_columns != NULL && $media_columns != '') {
      $media_columns_arr = explode(",", str_replace("'", "", $metainfo['media_columns']));
    }
    $video_columns_arr = array();
    $video_columns = $metainfo['video_columns'];
    if($video_columns != NULL && $video_columns != '') {
      $video_columns_arr = explode(",", str_replace("'", "", $metainfo['video_columns']));
    }
    $lyr_nid = $metainfo['nid'];

    $attribs_res = array();
    $images = array();
    $videos = array();
    $title = "";
    $created_by = "";
    $created_date = "";
    $modified_by = "";
    $modified_date = "";
    $validated_by = "";
    $validated_date = "";
    $validated = "";
    $attribution = "";
    $nid = 0;
    $teaser = "";

    $query = 'select * from "%s" where '.AUTO_DBCOL_PREFIX.'id = %d;';
    $result = db_query($query, array($layer_tablename, $row_id));
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error fetching information.");
    } else {
      if ($arr = db_fetch_array($result)) {
        $base_path = base_path();
        foreach ($arr as $key => $val) {
          if($key == $layer_title_column) {
            $title = $val;
          }

          if(in_array($key, $media_columns_arr)) {
            if($val != NULL) {
              $arr = explode(",", $val);
              $label = $col_db_info[$key] == '' ? $key : $col_db_info[$key];
              foreach($arr as $v) {
                $images[] = array('value' => $base_path.'sites/default/files/images/'.$layer_tablename.'/'.$v, 'label' => $label . ': ' . $v);
              }
            }
          }

          if(in_array($key, $video_columns_arr)) {
            if($val != NULL) {
              $arr = explode(",", $val);
              $label = $col_db_info[$key] == '' ? $key : $col_db_info[$key];
              foreach($arr as $v) {
                $videos[] = array('value' => $base_path.'sites/default/files/videos/'.$layer_tablename.'/'.$v, 'label' => $label . ': ' . $v);
              }
            }
          }

          switch($key) {
            case AUTO_DBCOL_PREFIX.'created_by':
              if($val) {
                $created_by = getUserName($val);
              }
              break;
            case AUTO_DBCOL_PREFIX.'created_date':
              $created_date = $val;
              break;
            case AUTO_DBCOL_PREFIX.'modified_by':
              if($val) {
                $modified_by = getUserName($val);
              }
              break;
            case AUTO_DBCOL_PREFIX.'modified_date':
              $modified_date = $val;
              break;
            case AUTO_DBCOL_PREFIX.'validated_by':
              if($val) {
                $validated_by = getUserName($val);
              }
              break;
            case AUTO_DBCOL_PREFIX.'validated_date':
              $validated_date = $val;
              break;
            case AUTO_DBCOL_PREFIX.'status':
              $validated = $val;
              break;
            case AUTO_DBCOL_PREFIX.'attribution':
              $attribution = $val;
              break;
            case AUTO_DBCOL_PREFIX.'nid':
              $nid = $val;
              break;
            case AUTO_DBCOL_PREFIX.'id':
            case AUTO_DBCOL_PREFIX.'layer_id':
            case AUTO_DBCOL_PREFIX.'topology':
              break;
            default:
              if(count($summary_columns_arr) > 0) {
                if(!in_array($key, $media_columns_arr) && !in_array($key, $video_columns_arr)) {
                  $attribs_res[$key] = array('key' => $key, 'value' => $val, 'label' => ($col_db_info[$key] == '' ? $key : $col_db_info[$key]), 'summary_column' => (in_array($key, $summary_columns_arr) == true) ? '1' : '0');
                }
              } else {
                if(!in_array($key, $media_columns_arr) && !in_array($key, $video_columns_arr)) {
                  $attribs_res[$key] = array('key' => $key, 'value' => $val, 'label' => ($col_db_info[$key] == '' ? $key : $col_db_info[$key]));
                }
              }
              break;
          }
        }
      }
    }

    setNoError($xmlDoc, $rootNode);
    $lyrNode = addXMLChildNode($xmlDoc, $rootNode, "layer", null, array('name' => $layer_name, 'tablename' => $layer_tablename, 'row_id' => $row_id, 'nid' => $lyr_nid));

    $infoNode = addXMLChildNode($xmlDoc, $lyrNode, "info");
    addXMLChildNode($xmlDoc, $infoNode, "title", $title);
    if($nid) {
      $node = node_load($nid);
      $ntitle = $node->title;
      $nbody = $node->body;
      $nbody .= ' <font color="#0000FF"><i><a href="'.base_path().'node/'.$nid.'" target="_blank">(more...)</a></i></font>';
      addXMLChildNode($xmlDoc, $infoNode, "narrative", $nbody, array('nid' => $nid, 'title' => $ntitle));
    } else {
      addXMLChildNode($xmlDoc, $infoNode, "narrative", null, array('nid' => $nid));
    }
    addXMLChildNode($xmlDoc, $infoNode, "created", null, array('by' => $created_by, 'date' => $created_date));
    addXMLChildNode($xmlDoc, $infoNode, "modified", null, array('by' => $modified_by, 'date' => $modified_date));
    if($validated) {
      addXMLChildNode($xmlDoc, $infoNode, "validated", null, array('by' => $validated_by, 'date' => $validated_date));
    }

    addXMLChildNode($xmlDoc, $infoNode, "attribution", $attribution);
    $license = getCCLicenseHTMLForSummary($layer_license);
    if($license != $layer_license){
      $url = 'http://i.creativecommons.org/l/' . $license[0] . '/3.0/';
      addXMLChildNode($xmlDoc, $infoNode, "license", $license[0], array('url' => $url, 'img' => $url . $license[1] . '.png'));
    } else {
      addXMLChildNode($xmlDoc, $infoNode, "license", $license);
    }

    if(userHasEditLayerDataPerm($layer_tablename, $row_id)) {
      addXMLChildNode($xmlDoc, $infoNode, "userHasEditLayerDataPerm", "1");
    } else {
      addXMLChildNode($xmlDoc, $infoNode, "userHasEditLayerDataPerm", "0");
    }

    $user = $GLOBALS['user'];
    if($user->uid && user_access("create node_mlocate_feature")) {
      addXMLChildNode($xmlDoc, $infoNode, "userHasEditDrupalNodePerm", "1");
    } else {
      addXMLChildNode($xmlDoc, $infoNode, "userHasEditDrupalNodePerm", "0");
    }

    $base_path = base_path();

    $mediaNode = addXMLChildNode($xmlDoc, $lyrNode, "media");
    if(count($media_columns_arr) > 0) {
      $imagesNode = addXMLChildNode($xmlDoc, $mediaNode, "images");
      foreach ($images as $im) {
        addXMLChildNode($xmlDoc, $imagesNode, "image", null, $im);
      }
    }
    if(count($video_columns_arr) > 0) {
      $videosNode = addXMLChildNode($xmlDoc, $mediaNode, "videos");
      foreach ($videos as $vd) {
        addXMLChildNode($xmlDoc, $videosNode, "video", null, $vd);
      }
    }

    $attribsNode = addXMLChildNode($xmlDoc, $lyrNode, "attribs");
    foreach ($attribs_res as $attrib) {
      addXMLChildNode($xmlDoc, $attribsNode, "attrib", null, $attrib);
    }
  }
}

function saveFeatureInfo($xmlDoc, &$rootNode, $paramsNode) {
  $layer_tablename = $paramsNode->getElementsByTagName('layer_tablename')->item(0)->nodeValue;
  $row_id = $paramsNode->getElementsByTagName('row_id')->item(0)->nodeValue;

  if(($layer_tablename == null || $layer_tablename == '') || ($row_id == null || $row_id == '')) {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    if(!userHasEditLayerDataPerm($layer_tablename, $row_id)) {
      setError($xmlDoc, $rootNode, "You are not authorized.");
      return;
    } else {
      $attribs = $paramsNode->getElementsByTagName('attribs')->item(0)->childNodes;
      $setarr = array();
      foreach($attribs as $attribNode) {
        $key = $attribNode->nodeName;
        $val = $attribNode->nodeValue;
        $setarr[] = "{$key}='{$val}'";
      }

      if(count($setarr) == 0) {
        setError($xmlDoc, $rootNode, "No columns specified.");
        return;
      }

      $query = "update " . $layer_tablename . " set " . implode(",", $setarr) . " where " . AUTO_DBCOL_PREFIX . "id=" . $row_id;
      $result = db_query($query);
      if(!$result) {
        setError($xmlDoc, $rootNode, "Error talking to database. Please try again later.");
        return;
      } else {
        setNoError($xmlDoc, $rootNode, "Record saved.");
      }
    }
  }
}
?>
