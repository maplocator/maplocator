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
*This file contains  functionality for layer group mapping.
*
***/
require_once('ml_header.php');

function getLayersForGroup($xmlDoc, &$rootNode, $paramsNode) {
  $group_id = $paramsNode->getElementsByTagName('group_id')->item(0)->nodeValue;

  if($group_id == null || $group_id == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $query = 'select ml.layer_name, ml.layer_tablename, lgm.display_name from "Meta_Layer" ml, "Layer_Group_Mapping" lgm where lgm.group_id = %d and ml.layer_id = lgm.layer_id order by lgm.layer_sequence';
    $query_args = array($group_id);
    $result = db_query($query, $query_args);
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error fetching information.");
    } else {
      setNoError($xmlDoc, $rootNode);
      $lyrGrpNode = addXMLChildNode($xmlDoc, $rootNode, "layer_group", null, array('id' => $group_id));
      while ($arr = db_fetch_array($result)) {
        addXMLChildNode($xmlDoc, $lyrGrpNode, "layer", null, $arr);
      }
    }
  }
}

function getLayersListForManageGroup($xmlDoc, &$rootNode, $paramsNode) {
  $group_id = $paramsNode->getElementsByTagName('group_id')->item(0)->nodeValue;

  if($group_id == null || $group_id == '') {
    setError($xmlDoc, $rootNode, "Required parameters not set.");
    return;
  } else {
    $query = 'select ml.layer_name, ml.layer_id, lgm.display_name from "Meta_Layer" ml, "Layer_Group_Mapping" lgm where lgm.group_id = %d and ml.layer_id = lgm.layer_id order by lgm.layer_sequence';
    $query_args = array($group_id);
    $result = db_query($query, $query_args);

    $query = 'select layer_name, layer_id from "Meta_Layer" where status = 1 and layer_id not in (select layer_id from "Layer_Group_Mapping" where group_id = %d) order by layer_name';
    $query_args = array($group_id);
    $result1 = db_query($query, $query_args);

    if (!$result || !$result1) {
      setError($xmlDoc, $rootNode, "Error fetching information.");
    } else {
      setNoError($xmlDoc, $rootNode);
      $lyrGrpNode = addXMLChildNode($xmlDoc, $rootNode, "layer_group", null, array('id' => $group_id));
      while ($arr = db_fetch_array($result)) {
        addXMLChildNode($xmlDoc, $lyrGrpNode, "layer", null, $arr);
      }
      $lyrsNode = addXMLChildNode($xmlDoc, $rootNode, "layers");
      while ($arr = db_fetch_array($result1)) {
        addXMLChildNode($xmlDoc, $lyrsNode, "layer", null, $arr);
      }
    }
  }
}

function saveLayerGroup($xmlDoc, &$rootNode, $paramsNode) {
  $isAdmin = false;
  $user = $GLOBALS['user'];
  $user_roles = $user->roles;
  if($user->uid) {
    if(in_array(SITE_ADMIN_ROLE, $user_roles)) {
      $isAdmin = true;
    }
  }

  if(!$isAdmin) {
    setError($xmlDoc, $rootNode, "You are not authorized.");
    return;
  }

  $groupNode = $paramsNode->getElementsByTagName('group')->item(0);
  $group_id = $groupNode->getAttribute("id");
  $group_name = trim($groupNode->getAttribute("name"));

  if($group_id == 0) {
    $query = 'select count(*) from "Layer_Group" where group_name = \'%s\'';
    $result = db_query($query, array($group_name));
    if($arr = db_fetch_array($result)) {
      $count = $arr['count'];
    }
    if($count > 0) {
      setError($xmlDoc, $rootNode, "The group name is already in use. Try some other name.");
      return;
    }

    $query = 'insert into "Layer_Group" (group_name, created_by, created_date, modified_by, modified_date) values(\'%s\', %d, now(), %d, now())';
    $result = db_query($query, array($group_name, $user-uid, $user-uid));
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error saving information.");
    } else {
      setNoError($xmlDoc, $rootNode, 'Group has been saved.');
    }
  } else {
    $lyrs = array();
    $layerslist = $groupNode->getElementsByTagName('layer');
    for($i = 0; $i < $layerslist->length; $i++) {
      $lyr = $layerslist->item($i);
      $display_name = $lyr->nodeValue;
      $layer_id = $lyr->getAttribute("id");
      $lyrs[] = array('layer_id' => $lyr->getAttribute("id"), 'display_name' => $lyr->nodeValue);
    }

    $query = "update \"Layer_Group\" set group_name = '%s', modified_by = %d, modified_date = now() where id = %d";
    $query_args = array($group_name, $user->uid, $group_id);
    $result = db_query($query, $query_args);
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error saving information.");
      return;
    }

    $query = 'delete from "Layer_Group_Mapping" where group_id = %d';
    $query_args = array($group_id);
    $result = db_query($query, $query_args);
    if (!$result) {
      setError($xmlDoc, $rootNode, "Error saving information.");
      return;
    }

    $i = 1;
    foreach($lyrs as $lyr) {
      $query = 'insert into "Layer_Group_Mapping" (group_id, layer_id, display_name, layer_sequence) values (%d, %d, \'%s\', %d)';
      $query_args = array($group_id, $lyr['layer_id'], $lyr['display_name'], $i);
      $result = db_query($query, $query_args);
      if (!$result) {
        setError($xmlDoc, $rootNode, "Error saving information.");
        return;
      }
      $i++;
    }
    setNoError($xmlDoc, $rootNode, 'Group has been updated.');
  }
}
?>
