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
 This files reads config.xml configuration file and emits a respective javascript which is implicitly included with various config paramters set
***/

require_once('XML2Array.php');

function getDeploymentFor($arr_config) {
    return $arr_config['CONFIG']['DEPLOYMENT_FOR'];
}

function getBaseMapSource($arr_config) {
    $len=sizeof($arr_config['CONFIG']['MAP']['BASE_MAP']);
    $temp="";
    for($i=0;$i<$len;$i++) {
        if( $arr_config['CONFIG']['MAP']['BASE_MAP'][$i]['@']['enabled']=="true") {
            $temp=$temp.",".$arr_config['CONFIG']['MAP']['BASE_MAP'][$i]['@']['source'];
        }
    }
    if($temp != "")
        return ltrim($temp,",");
    return "GOOGLE";
}

function getBaseMapProjection($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['BASE_MAP_PROJECTION']))
        return $arr_config['CONFIG']['MAP']['BASE_MAP_PROJECTION'];
    else
        return "EPSG:900913";
}

function getCurLayerProjection($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['CUR_LAYER_PROJECTION']))
        return $arr_config['CONFIG']['MAP']['CUR_LAYER_PROJECTION'];
    else
        return "EPSG:4326";
}

function getDefaultProjection($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['DEFAULT_PROJECTION']))
        return $arr_config['CONFIG']['MAP']['DEFAULT_PROJECTION'];
    else
        return "EPSG:4326";
}

function getBlockUiZindex($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['BLOCKUI_Z_INDEX']))
        return (int)$arr_config['CONFIG']['MAP']['BLOCKUI_Z_INDEX'];
    else
        return 10000;
}

function getMinZoomLevel($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['MIN_ZOOM_LEVEL']))
        return (int)$arr_config['CONFIG']['MAP']['MIN_ZOOM_LEVEL'];
    else
        return 2;
}

function getPopUpMinSize($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['POPUP_MINSIZE']))
        return $arr_config['CONFIG']['MAP']['POPUP_MINSIZE'];
    else
        return "480,300";
}

function getNumZoomLevel($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['NUM_ZOOM_LEVEL']))
        return (int)$arr_config['CONFIG']['MAP']['NUM_ZOOM_LEVEL'];
    else
        return 19;
}


function getMaxLayers($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['MAX_LAYERS']))
        return (int)$arr_config['CONFIG']['MAP']['MAX_LAYERS'];
    else
        return 10;
}

function getActiveLayerFillopacity($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['ACTIVE_LAYER_FILLOPACITY']))
        return (float)$arr_config['CONFIG']['MAP']['ACTIVE_LAYER_FILLOPACITY'];
    else
        return 1;
}

function getActiveLayerStrokeopacity($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['ACTIVE_LAYER_STROKEOPACITY']))
        return (float)$arr_config['CONFIG']['MAP']['ACTIVE_LAYER_STROKEOPACITY'];
    else
        return 1;
}

function getInactiveLayerFillopacity($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['INACTIVE_LAYER_FILLOPACITY']))
        return (float)$arr_config['CONFIG']['MAP']['INACTIVE_LAYER_FILLOPACITY'];
    else
        return 0.4;
}

function getInactiveLayerStrokeopacity($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['INACTIVE_LAYER_STROKEOPACITY']))
        return (float)$arr_config['CONFIG']['MAP']['INACTIVE_LAYER_STROKEOPACITY'];
    else
        return 0.5;
}

function getGoogleApiKey($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['GOOGLE_MAP_API_KEY']))
        return $arr_config['CONFIG']['MAP']['GOOGLE_MAP_API_KEY'];
    else
        return "ABQIAAAAQUKI635zX8tJCYVCPiLGiRT2yXp_ZAY8_ufC3CFXhHIE1NvwkxQTk5f2TB9OdaLZxfoZDgXnD8O16Q";
}

function getGoogleAjaxSearchApiKey($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['GOOGLE_AJAXSEARCH_API_KEY']))
        return $arr_config['CONFIG']['MAP']['GOOGLE_AJAXSEARCH_API_KEY'];
    else

        return "ABQIAAAAQUKI635zX8tJCYVCPiLGiRT2yXp_ZAY8_ufC3CFXhHIE1NvwkxQTk5f2TB9OdaLZxfoZDgXnD8O16Q";
}

function getMapCenter($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['MAP_CENTER']))
        return $arr_config['CONFIG']['MAP']['MAP_CENTER'];
    else
        return "80,23";
}

function getMaxExtent($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['MAX_EXTENT']))
        return $arr_config['CONFIG']['MAP']['MAX_EXTENT'];
    else
        return "5801108.428222222,-7.081154550627198, 12138100.077777777, 4439106.786632658";
}

function getMapExtent($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['MAP_EXTENT']))
        return $arr_config['CONFIG']['MAP']['MAP_EXTENT'];
    else
        return "6567849.955888889,1574216.547942332,11354588.059333334,3763310.626620795";
}

function getRestrictedExtent($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['RESTRICTED_EXTENT']))
        return $arr_config['CONFIG']['MAP']['RESTRICTED_EXTENT'];
    else
        return "5801108.428222222,674216.547942332, 12138100.077777777, 4439106.786632658";
}

function getBirdsEyeViewEnabled($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['BIRDS_EYE_VIEW_ENABLED'])) {
        $val = $arr_config['CONFIG']['MAP']['BIRDS_EYE_VIEW_ENABLED'];
        return ($val == 'true' ? true : false);
    }
    else
        return true;
}

function getChloroplethEnabled($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['CHLOROPLETH_ENABLED'])) {
        $val = $arr_config['CONFIG']['MAP']['CHLOROPLETH_ENABLED'];
        return ($val == 'true' ? true : false);
    }
    else
        return false;

}

function getGoogleEarthEnabled($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['GOOGLE_EARTH_ENABLED'])) {
        $val = $arr_config['CONFIG']['MAP']['GOOGLE_EARTH_ENABLED'];
        return ($val == 'true' ? true : false);
    }
    else
        return false;
}

function getMinZoom($arr_config) {
    if(!empty($arr_config['CONFIG']['MAP']['MIN_ZOOM']))
        return (int)$arr_config['CONFIG']['MAP']['MIN_ZOOM'];
    else
        return 5;

}

function getBaseMapLayers($arr_config) {

    $len=sizeof($arr_config['CONFIG']['MAP']['BASE_MAP']);
    $basemap_arr = array();
    $basemap_arr = '';
    $j = 0;

    for($i=0;$i<$len;$i++) {
        if( $arr_config['CONFIG']['MAP']['BASE_MAP'][$i]['@']['enabled']=="true") {
            $arr = $arr_config['CONFIG']['MAP']['BASE_MAP'][$i]['BASE_LAYER'];
            if(is_array($arr)) {
                $count = sizeof($arr);
                for($k=0;$k<$count;$k++) {
                    $basemap_arr[$j++] = $arr[$k];
                }
            } else {
                $basemap_arr[$j++] = $arr;
            }
        }
    }
    if(sizeof($basemap_arr) > 0)
        return $basemap_arr;
}


function getDefaultLayertreeOpt($arr_config) {
    if(!empty($arr_config['CONFIG']['CUSTOM']['DEFAULT_LAYERTREE_OPT']))
        return (int)$arr_config['CONFIG']['CUSTOM']['DEFAULT_LAYERTREE_OPT'];
    else
        return 1;
}


function getSiteAdminRole($arr_config) {
    if(!empty($arr_config['CONFIG']['CUSTOM']['SITE_ADMIN_ROLE']))
        return $arr_config['CONFIG']['CUSTOM']['SITE_ADMIN_ROLE'];
    else
        return "mlocate_site_admin";
}

function getUpdateEmailId($arr_config) {
    if(!empty($arr_config['CONFIG']['CUSTOM']['UPDATE_MAIL_ID']))
        return $arr_config['CONFIG']['CUSTOM']['UPDATE_MAIL_ID'];
    else
        return "update@indiabiodiversity.org";

}

function getSiteTitle($arr_config) {
    if(!empty($arr_config['CONFIG']['CUSTOM']['SITE_TITLE']))
        return $arr_config['CONFIG']['CUSTOM']['SITE_TITLE'];
    else
        "Map Locator";
}

function getFFMpegPath($arr_config) {
    if(!empty($arr_config['CONFIG']['CUSTOM']['FFMPEG_PATH']))
        return $arr_config['CONFIG']['CUSTOM']['FFMPEG_PATH'];
    else
        "ffmpeg";
}

  /*Ends All getter methods*/

function readConfig() {
    $ml_config = array();

    $arr_config = XML2Array("config.xml");
    $ml_config = $arr_config['CONFIG'];

    $_SESSION["SITE_ADMIN_ROLE"] = getSiteAdminRole($arr_config);

    $_SESSION["ffmpeg"] = getFFMpegPath($arr_config);

    $headers['From'] = getUpdateEmailId($arr_config);

    //PHP variables used for creating javascrip arrays of   map extent,map center, max extent etc

    $deploymentFor=getDeploymentFor($arr_config);

    $map_center=getMapCenter($arr_config);
    $map_center = str_replace(" ", "", $map_center);
    $map_center_array=split(",",$map_center);

    $max_extent=getMaxExtent($arr_config);
    $max_extent = str_replace(" ", "", $max_extent);
    $max_extent_array=split(",",$max_extent);

    $map_extent=getMapExtent($arr_config);
    $map_extent = str_replace(" ", "", $map_extent);
    $map_extent_array=split(",",$map_extent);

    $restricted_extent=getRestrictedExtent($arr_config);
    $restricted_extent = str_replace(" ", "", $restricted_extent);
    $restricted_extent_array=split(",",$restricted_extent);

    $base_layer_array=getBaseMapLayers($arr_config);

    $loadcss = "";
    $loadscript = "";

    $js =<<< EOT
  <script language="javascript">

    //Array for creating map extent,map center, max extent etc in map.js

    Jbounds_array= new Array();
    Jbounds_array.push({$bounds_array[0]});
    Jbounds_array.push({$bounds_array[1]});
    Jbounds_array.push({$bounds_array[2]});
    Jbounds_array.push({$bounds_array[3]});


    Jmap_center=new Array();
    Jmap_center.push({$map_center_array[0]});
    Jmap_center.push({$map_center_array[1]});

    Jmax_extent=new Array();
    Jmax_extent.push({$max_extent_array[0]});
    Jmax_extent.push({$max_extent_array[1]});
    Jmax_extent.push({$max_extent_array[2]});
    Jmax_extent.push({$max_extent_array[3]});

    Jmap_extent=new Array();
    Jmap_extent.push({$map_extent_array[0]});
    Jmap_extent.push({$map_extent_array[1]});
    Jmap_extent.push({$map_extent_array[2]});
    Jmap_extent.push({$map_extent_array[3]});

    Jrestricted_extent=new Array();
    Jrestricted_extent.push({$restricted_extent_array[0]});
    Jrestricted_extent.push({$restricted_extent_array[1]});
    Jrestricted_extent.push({$restricted_extent_array[2]});
    Jrestricted_extent.push({$restricted_extent_array[3]});

    //Base layers array...will be used later in map.js
    //Length is hard coded for google..later it will be sizeof($base_layer_array);
    Jbase_layers=new Array();
    Jbase_layers.push('{$base_layer_array[0]}');
    Jbase_layers.push('{$base_layer_array[1]}');
    Jbase_layers.push('{$base_layer_array[2]}');

EOT;

    $len = count($base_layer_array);
    $js .= '    var baseLayers = new Array();
';
    for($i=0;$i<$len;$i++) {
        $js .= '    baseLayers['.$i.'] = \''.$base_layer_array[$i].'\';
';
    }
    $js .= '
';

    /*
     * The new line in the following string concatenations is required.
     * The problem is, if normal string concat is used, all lines are
     * appended to a single line. If there is any comment, rest of the
     * code is commented.
     */
    $js .= '    var JMinZoom = ' . getMinZoom($arr_config) . ';
';
    $js .= '
';

    $js .= '    var BIRDS_EYE_VIEW_ENABLED=' . (getBirdsEyeViewEnabled($arr_config) == true ? 'true' : 'false') . ';
';
    $js .= '    //moved from map.js
';
    $js .= '    var CHLOROPLETH_ENABLED=' . (getChloroplethEnabled($arr_config) == true ? 'true' : 'false') . ';
';
    $js .= '    var GOOGLE_EARTH_ENABLED=' . (getGoogleEarthEnabled($arr_config) == true ? 'true' : 'false') . ';
';
    $js .= '
';

    $js .= '    var NUMZOOMLEVEL = ' . getNumZoomLevel($arr_config) . ';
';
    $js .= '    var LAYER_COUNT = 0;
';
    $js .= '    var MAX_LAYERS = ' . getMaxLayers($arr_config) . ';
';
    $js .= '
';

    $js .= '    var ActiveLayerFillOpacity = ' . getActiveLayerFillopacity($arr_config) . ';
';
    $js .= '    var ActiveLayerStrokeOpacity = ' . getActiveLayerStrokeopacity($arr_config) . ';
';
    $js .= '    var InActiveLayerFillOpacity = ' . getInactiveLayerFillopacity($arr_config) . ';
';
    $js .= '    var InActiveLayerStrokeOpacity = ' . getInactiveLayerStrokeopacity($arr_config) . ';
';
    $js .= '
';

    $js .= '    var BASE_MAP_SOURCE = "' . getBaseMapSource($arr_config) . '";
';
    $js .= '    var base_map_projection = "' . getBaseMapProjection($arr_config) . '";
';
    $js .= '    var cur_layer_projection = "' . getCurLayerProjection($arr_config) . '";
';
    $js .= '    var default_projection = "' . getDefaultProjection($arr_config) . '";
';
    $js .= '    var blockUI_z_index = ' . getBlockUiZindex($arr_config) . ';
';
    $js .= '
';

    $js .= '    var DEPLOYMENT_FOR = "' . getDeploymentFor($arr_config) . '";
';
    $js .= '    var SITE_ADMIN_ROLE = "' . getSiteAdminRole($arr_config) . '";
';
    $js .= '
';

    $js .= '    //moved from ecopradesh.js
';
    $js .= '    var DEFAULT_LAYERTREE_OPT = ' . getDefaultLayertreeOpt($arr_config) . ';
';
    $js .= '
';

    $js .= '    //For includemap.js, replace keys in includemap.js...currently not used...
';
    $js .= '    var googleApiKey = "' . getGoogleApiKey($arr_config) . '";
';
    $js .= '    var googleAjaxSearchApiKey = "' . getGoogleAjaxSearchApiKey($arr_config) . '";
';
    $js .= '
';

    $js .= '</script>
';

    $loadscript .= $js;


    $base_path = base_path();

    $js = '<script language="javascript" src="'.$base_path.path_to_theme().'/scripts/thirdparty/serialize.js"></script>
';

    $loadscript .= $js;

    $default_theme_path = $base_path . path_to_theme() . "/css/styles.css";
    //drupal_add_css($custom_theme_path, 'theme');
    $loadcss .= '<link type="text/css" rel="stylesheet" media="all" href="'.$default_theme_path.'" />
';

    $default_theme_path = $base_path . path_to_theme() . "/css/map.css";
    $loadcss .= '<link type="text/css" rel="stylesheet" media="all" href="'.$default_theme_path.'" />
';

    $custom = $ml_config['CUSTOM'];

    if(isset($custom['CSS']) && $custom['CSS'] != '') {
        $custom_theme_path = $base_path . "sites/default/files/" . $custom['CSS'];
        //drupal_add_css($custom_theme_path, 'theme');
        $loadcss .= "<link href='".$custom_theme_path."' media='all' rel='stylesheet' type='text/css'>
";
    }

    if(isset($custom['SITE_TITLE']) && $custom['SITE_TITLE'] != '') {
        $js = '<script language="javascript">';
        $js .= 'document.title = "'.$custom['SITE_TITLE'].'";';
        $js .= '</script>
';
        $loadscript .= $js;
    }
    return array('styles'=>$loadcss, 'scripts'=>$loadscript);
}
?>
