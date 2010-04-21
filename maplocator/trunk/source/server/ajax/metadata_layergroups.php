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
*This file contains functionality to manage layer groups.
*
***/

require_once('ml_header.php');

function getLayerGroups($xmlDoc, &$rootNode) {
  $query = 'select id, group_name from "Layer_Group" order by group_name';
  $result = db_query($query);

  if (!$result) {
    setError($xmlDoc, $rootNode, "Error fetching information.");
  } else {
    setNoError($xmlDoc, $rootNode);
    $isAdmin = "0";
    $user = $GLOBALS['user'];
    $user_roles = $user->roles;
    if($user->uid) {
      if(in_array(SITE_ADMIN_ROLE, $user_roles)) {
        $isAdmin = "1";
      }
    }

    addXMLChildNode($xmlDoc, $rootNode, "user_is_admin", $isAdmin);
    $lyrGrpNode = addXMLChildNode($xmlDoc, $rootNode, "layer_groups");
    while ($arr = db_fetch_array($result)) {
      addXMLChildNode($xmlDoc, $lyrGrpNode, "layer_group", null, array('id' => $arr['id'], 'name' => $arr['group_name']));
    }
  }
}

function deleteLayerGroup($xmlDoc, &$rootNode, $paramsNode) {
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

  if($group_id == NULL || $group_id == '' || $group_id == 0) {
    setError($xmlDoc, $rootNode, "Select a layer group to delete.");
    return;
  }

  $query = 'delete from "Layer_Group" where id = %d';
  $query_args = array($group_id);
  $result = db_query($query, $query_args);
  if(!$result) {
    setError($xmlDoc, $rootNode, "Could not delete the group. Please try after sometime.");
  } else {
    setNoError($xmlDoc, $rootNode, "The group has been deleted successfully.");
  }
}
?>
