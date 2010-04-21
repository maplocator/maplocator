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
*This file contains layer functionality to manage theme - layer mapping.
*
***/

require_once('ml_header.php');

function getThemeMapping($xmlDoc, &$rootNode, $paramsNode) {
  $layer_tablename = $paramsNode->getElementsByTagName('layer_tablename')->item(0)->nodeValue;

  if($layer_tablename == null || $layer_tablename == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $query = 'select tm.id, t.theme_id, t.theme_type from "Theme" t, "Theme_Layer_Mapping" tm where t.theme_id = tm.theme_id and tm.layer_id = (select layer_id from "Meta_Layer" where layer_tablename = \'%s\') order by t.theme_type;';
    $result = db_query($query, $layer_tablename);
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error fetching information.");
    } else {
      setNoError($xmlDoc, $rootNode);
      $lyrNode = addXMLChildNode($xmlDoc, $rootNode, "layer", null, array('tablename' => $layer_tablename));

      while($obj = db_fetch_object($result)) {
        addXMLChildNode($xmlDoc, $lyrNode, "mapping", null, array('theme_id' => $obj->theme_id, 'mapping_id' => $obj->id, 'theme_type' => $obj->theme_type));
      }
    }
  }
}

function saveThemeMapping($xmlDoc, &$rootNode, $paramsNode) {
  $layerNode = $paramsNode->getElementsByTagName('layer')->item(0);
  $layer_tablename = $layerNode->getAttribute('tablename');
  if($layer_tablename == null || $layer_tablename == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    if(isUserAuthorizedToEditMetadata($layer_tablename, TABLE_TYPE_LAYER)) {
      $theme_mappingNodes = $layerNode->childNodes;
      $err_flag = false;
      $query = 'update "Theme_Layer_Mapping" set theme_id = %d where id = %d and layer_id = (select layer_id from "Meta_Layer" where layer_tablename = \'%s\')';
      foreach($theme_mappingNodes as $theme_mappingNode) {
        $mapping_id = $theme_mappingNode->getAttribute("id");
        $theme_id = $theme_mappingNode->nodeValue;
        $result = db_query($query, array($theme_id, $mapping_id, $layer_tablename));
        if(!$result) {
          $err_flag = true;
        } else {
        }
      }
      if($err_flag) {
        setError($xmlDoc, $rootNode, "Error saving information. It may have been partially saved.");
      } else {
        setNoError($xmlDoc, $rootNode, "Record saved.");
      }
    } else {
      setError($xmlDoc, $rootNode, "You are not authorized.");
    }
  }
}

function getLayersForTheme($xmlDoc, &$rootNode, $paramsNode) {
  $group_type = $paramsNode->getElementsByTagName('group_type')->item(0)->nodeValue;
  $theme_id = $paramsNode->getElementsByTagName('theme_id')->item(0)->nodeValue;
  if($theme_id == null || $theme_id == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $query = 'select layer_tablename, layer_name, access, p_nid, participation_type from "Meta_Layer" where layer_id in (select layer_id from "Theme_Layer_Mapping" where theme_id = %d) and status = 1 order by layer_name;';
    $result = db_query($query, $theme_id);
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error fetching information.");
    } else {
      setNoError($xmlDoc, $rootNode);
      addXMLChildNode($xmlDoc, $rootNode, "group_type", $group_type);
      $thmNode = addXMLChildNode($xmlDoc, $rootNode, "theme", null, array('id' => $theme_id));
      getLayersList($xmlDoc, $thmNode, $result);
    }
  }
}

function getCategoricalLayersList($xmlDoc, &$rootNode, $paramsNode) {
  $theme_type = $paramsNode->getElementsByTagName('theme_type')->item(0)->nodeValue;

  if($theme_type == null || $theme_type == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $themes = getThemesByType($theme_type);
    if($themes === false) {
      setError($xmlDoc, $rootNode, "Error fetching information.");
    } else {
      $layers = getLayersByThemeType($theme_type);
      if($layers === false) {
        setError($xmlDoc, $rootNode, "Error fetching information.");
      } else {
        setNoError($xmlDoc, $rootNode);
        $thmsNode = addXMLChildNode($xmlDoc, $rootNode, "themes", null, array('theme_type' => $theme_type));
        foreach($themes as $theme) {
          $chldcnt = sizeof($layers[$theme['theme_id']]);
          $theme['icon'] = getThemeIconUrl($theme['icon']);
          $thmNode = addXMLChildNode($xmlDoc, $thmsNode, "theme", null, $theme);
          foreach($layers[$theme['theme_id']] as $lyr) {
            formLayersListXML($xmlDoc, $thmNode, $lyr['layer_tablename'], $lyr['layer_name'], $lyr['access'], $lyr['p_nid'], $lyr['participation_type']);
          }
        }
      }
    }
  }
}
?>
