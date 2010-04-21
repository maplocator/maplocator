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
*This file contains functionality to manage all the meta data works as arouter.
*
***/

require_once('ml_header.php');
header("Content-Type: text/xml");

if(!isset($_REQUEST['request'])) {
  die(sendErrorResponse("Required parameters are not set."));
}

$request = $_REQUEST['request'];

$requestDoc = new DOMDocument();
$requestDoc->preserveWhiteSpace = FALSE;
if(!$requestDoc->loadXML($request)) {
  die(sendErrorResponse("Error reading request."));
}

$action = $requestDoc->getElementsByTagName('action')->item(0)->nodeValue;
if($action == null || $action == '' || !$action) {
  die(sendErrorResponse("Required parameters are not set."));
}

// create a new XML document
$responseDoc = new DomDocument('1.0');

// create root node
$rootNode = $responseDoc->createElement('response');
$rootNode = $responseDoc->appendChild($rootNode);

switch($action) {
  case 'getMetaLayer':
    require_once('metadata_layer.php');
    getMetaLayer($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'saveMetaLayer':
    require_once('metadata_layer.php');
    saveMetaLayer($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getLayerColDesc':
    require_once('metadata_layer.php');
    getLayerColDesc($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'saveLayerColDesc':
    require_once('metadata_layer.php');
    saveLayerColDesc($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getColumnsForLayerOfType':
    require_once('metadata_layer.php');
    getColumnsForLayerOfType($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getAllThemes':
    require_once('metadata_themes.php');
    getAllThemes($responseDoc, $rootNode);
    break;
  case 'getThemesOfType':
    require_once('metadata_themes.php');
    getThemesOfType($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getThemesChildNodes':
    if($requestDoc->getElementsByTagName('params')->length == 0) {
      setError($responseDoc, $rootNode, "Required parameters not set.");
    } else {
      require_once('metadata_themes.php');
      getThemesChildNodes($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    }
    break;
  case 'getCategoricalLayersList':
    require_once('metadata_themelayermapping.php');
    getCategoricalLayersList($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getThemeMapping':
    require_once('metadata_themelayermapping.php');
    getThemeMapping($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'saveThemeMapping':
    require_once('metadata_themelayermapping.php');
    saveThemeMapping($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getLayersForGroupType':
    $paramsNode = $requestDoc->getElementsByTagName('params')->item(0);
    $group_type = $paramsNode->getElementsByTagName('group_type')->item(0)->nodeValue;
    if($group_type == null || $group_type == '') {
      die(sendErrorResponse("Required parameters are not set."));
    }
    if($group_type == 1 || $group_type == 2) {
      require_once('metadata_themelayermapping.php');
      getLayersForTheme($responseDoc, $rootNode, $paramsNode);
    } else {
      require_once('metadata_layer.php');
      getLayersOfType($responseDoc, $rootNode, $group_type);
    }
    break;
  case 'getLinkTables':
    require_once('metadata_link.php');
    getLinkTables($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getMetaLinkTable':
    require_once('metadata_link.php');
    getMetaLinkTable($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'saveMetaLinkTable':
    require_once('metadata_link.php');
    saveMetaLinkTable($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getLinkColDesc':
    require_once('metadata_link.php');
    getLinkColDesc($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'saveLinkColDesc':
    require_once('metadata_link.php');
    saveLinkColDesc($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getAllGlobalResources':
    require_once('metadata_globalresources.php');
    getAllGlobalResources($responseDoc, $rootNode);
    break;
  case 'getGlobalResourceMapping':
    require_once('metadata_globalresources.php');
    getGlobalResourceMapping($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'saveGlobalResourceMapping':
    require_once('metadata_globalresources.php');
    saveGlobalResourceMapping($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'deleteGlobalResourceMapping':
    require_once('metadata_globalresources.php');
    deleteGlobalResourceMapping($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getGRMappedTableColumns':
    require_once('metadata_globalresources.php');
    getGRMappedTableColumns($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getLayerRowAttribValues':
    require_once('data_layer.php');
    getLayerRowAttribValues($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getLayerAttribValues':
    require_once('data_layer.php');
    getLayerAttribValues($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getFeatureInfo':
    require_once('data_layer.php');
    getFeatureInfo($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'saveFeatureInfo':
    require_once('data_layer.php');
    saveFeatureInfo($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getLayerGroups':
    require_once('metadata_layergroups.php');
    getLayerGroups($responseDoc, $rootNode);
    break;
  case 'deleteLayerGroup':
    require_once('metadata_layergroups.php');
    deleteLayerGroup($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getLayersForGroup':
    require_once('metadata_layergroupsmapping.php');
    getLayersForGroup($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'getLayersListForManageGroup':
    require_once('metadata_layergroupsmapping.php');
    getLayersListForManageGroup($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'saveLayerGroup':
    require_once('metadata_layergroupsmapping.php');
    saveLayerGroup($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  case 'deleteMedia':
    require_once('ml_deletemedia.php');
    deleteMedia($responseDoc, $rootNode, $requestDoc->getElementsByTagName('params')->item(0));
    break;
  default:
    die(sendErrorResponse("Incorrect parameters set."));
    break;
}

echo $responseDoc->saveXML();
?>
