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
This file includes functionality to fetch external media(images) from flicker and panoramio
**/
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

require_once 'functions.php';

if(isset($_REQUEST['layer_tablename'], $_REQUEST['row_id'])) {
  $layer_tablename = $_REQUEST['layer_tablename'];
  $row_id = $_REQUEST['row_id'];
  $result = getExternalImages($layer_tablename,$row_id);
  print $result;
} else {
  die("Required parameters not set.");
}

function getExternalImages($layer_tablename,$row_id){
	 require_once("phpFlickr/phpFlickr.php");
  $f = new phpFlickr("fcd1d2106cda125b6b87f2e131357f12", "cee841f34bf60a97");

  $flickr_photos = array();

  $query = "select layer_name,layer_type,title_column from \"Meta_Layer\" where layer_tablename = '%s'";
  $result = db_query($query, $layer_tablename);
  if(!$result) {
  } else {
    $obj = db_fetch_object($result);
    $layer_name = $obj->layer_name;
    $layer_type = $obj->layer_type;
    $title_column = $obj->title_column;
    $tags = "india," . $layer_name;

    switch($layer_type) {
      case 'POINT':
      case 'MULTIPOINT':
        $query = 'select %s as title, asText('.AUTO_DBCOL_PREFIX.'topology) as topo from "%s" where '.AUTO_DBCOL_PREFIX.'id = %d';
        $query_args = array(str_replace("'", '"', $title_column), $layer_tablename, $row_id);
        $result = db_query($query, $query_args);
        if(!$result) {
        } else {
          if($obj = db_fetch_object($result)) {
            $title = $obj->title;
            $topo = $obj->topo;
            $topo = strstr($topo, "(");
            $topo = str_replace(array("(", ")"), "", $topo);
            $arr_topo = explode(" ", $topo);
            $lon = $arr_topo[0];
            $lat = $arr_topo[1];

            $tags = $tags . "," . $title;

            $tags = str_replace(" ", ",", $tags);
            $flickr_photos = $f->photos_search(array("api_keys"=>"fcd1d2106cda125b6b87f2e131357f12", "lon"=>$lon, "lat"=>$lat, "radius"=>"0.095", "tags"=>$tags, "per_page"=>30, "privacy_filter"=>1, "content_type"=>1));
            $delta = 0.002;
            $loc[0] = $lon - $delta;
            $loc[1] = $lat - $delta;
            $loc[2] = $lon + $delta;
            $loc[3] = $lat + $delta;
            $url = "http://www.panoramio.com/map/get_panoramas.php?order=popularity&set=popular&from=0&to=30&minx=".$loc[0]."&miny=".$loc[1]."&maxx=".$loc[2]."&maxy=".$loc[3]."&size=square";
            $panoramio_json = json_decode(file_get_contents($url));
          } else {
            die("No record found");
          }
        }
        break;
      default:
        $query = "select %s as title, ST_XMin(box3d(".AUTO_DBCOL_PREFIX."topology)) || ',' || ST_YMin(box3d(".AUTO_DBCOL_PREFIX."topology)) || ',' || ST_XMax(box3d(".AUTO_DBCOL_PREFIX."topology)) || ',' || ST_YMax(box3d(".AUTO_DBCOL_PREFIX."topology)) as bbox from \"%s\" where ".AUTO_DBCOL_PREFIX."id = %d";
        $query_args = array(str_replace("'", "", $title_column), $layer_tablename, $row_id);
        $result = db_query($query, $query_args);
        if(!$result) {
        } else {
          if($obj = db_fetch_object($result)) {
            $title = $obj->title;
            $bbox = $obj->bbox;
            $tags = $tags . "," . $title;
            $tags = str_replace(" ", ",", $tags);
            $flickr_photos = $f->photos_search(array("api_keys"=>"fcd1d2106cda125b6b87f2e131357f12", "bbox"=>$bbox, "tags"=>$tags, "per_page"=>30, "privacy_filter"=>1, "content_type"=>1));
            $loc = explode(",", $bbox);
            $url = "http://www.panoramio.com/map/get_panoramas.php?order=popularity&set=popular&from=0&to=30&minx=".$loc[0]."&miny=".$loc[1]."&maxx=".$loc[2]."&maxy=".$loc[3]."&size=square";
            $panoramio_json = json_decode(file_get_contents($url));
          }
        }
        break;
    }
  }

  return generateUI($flickr_photos,$panoramio_json);

}

function generateUI($flickr_photos,$panoramio_json){
	$html = "";
  if(sizeof($flickr_photos['photo']) == 0) {
    $html .= 'No media found on <a href="http://www.flickr.com">Flickr</a>.<br>';
  } else {
    $css = <<<EOF
<style type="text/css">
  .f_img {
    border:1px solid;
    float:left;
    height:75px;
    margin:8px;
    overflow:auto;
    padding:17px;
    width:75px;
    text-align:center;
  }

  .f_img:hover {
    background-color: #F0FAFF;
    /*cursor: pointer;*/
    /*cursor: hand;*/
  }
</style>
EOF;

    $html .= $css;

    $html .= 'Photos provided by <a href="http://www.flickr.com">Flickr</a> are under the copyright of their owners.';
    $html .= '<hr>';
    $html .= '<div style="border: 1px solid; overflow: auto;">';
    $i = 0;
    foreach ($flickr_photos['photo'] as $photo) {
      $url = "http://farm".$photo['farm'].".static.flickr.com/".$photo['server']."/".$photo['id']."_".$photo['secret']."_s.jpg";
      if($i == 6) {
        $html .= '<div class="f_img" style="clear:left;">';
        $i = 0;
      } else {
        $html .= '<div class="f_img">';
      }

      $html .= "<a target=_ href='http://www.flickr.com/photos/" . $photo['owner'] . "/" . $photo['id'] . "/'>";
      $html .= '<img src="'.$url.'" alt="'.($photo['title']?$photo['title']:$photo['id']).'" title="'.($photo['title']?$photo['title']:$photo['id']).'"/>';
      $html .= "</a>";
      $html .= "</div>";
      $i++;
    }
    $html .= '</div>';
    $html .= '<div style="clear:both;"></div>';
    $html .= '<hr><br><hr>';
  }

  $count = $panoramio_json->count;
  if($count == 0) {
      $html .= 'No media found on <a href="http://www.panoramio.com">Panoramio</a>.<br>';
  } else {
    $panoramio_photos = $panoramio_json->photos;

    $css = <<<EOF
<style type="text/css">
  .p_img_wth_owner {
    border:1px solid;
    float:left;
    height:110px;
    margin:8px;
    overflow:auto;
    padding:5px;
    width:125px;
    text-align:center;
  }

  .p_img_wth_owner:hover {
    background-color: #F0FAFF;
    /*cursor: pointer;*/
    /*cursor: hand;*/
  }
</style>
EOF;

    $html .= $css;

    $html .= 'Photos provided by <a href="http://www.panoramio.com">Panoramio</a> are under the copyright of their owners.';
    $html .= '<hr>';
    $html .= '<div style="border:1px solid; overflow: auto;" id="divPanoramia">';
    $i = 0;
    foreach($panoramio_photos as $photo) {
      $name = $photo->photo_title?$photo->photo_title:$photo->photo_id;

      if($i == 5) {
        $html .= '<div class="p_img_wth_owner" style="clear:left;">';
        $i = 0;
      } else {
        $html .= '<div class="p_img_wth_owner">';
      }

      $html .= "<a target='_' href='".$photo->photo_url."'>";
      $html .= '<img src="'.$photo->photo_file_url.'" alt="'.($name).'" title="'.($name).'"/>';
      $html .= "</a>";
      $html .= '<br>';
      $html .= "Owner:<br><a target='_' href='".$photo->owner_url."'>";
      $html .= $photo->owner_name;
      $html .= "</a>";
      $html .= '</div>';

      $i++;
    }
    $html .= '</div>';
  }

  $disclaimer = <<<EOF
      <strong>Disclaimer:</strong>
      <div style="padding: 5px 5px 5px 25px;">
        We are not responsible for the content or reliability of external websites which are linked to <br>
        from this website. Links should not be taken as an endorsement of any kind. We cannot guarantee<br>
        that these links will work at all times and we have no control over the availability of linked pages.
      </div>
    <hr>
EOF;

  return $disclaimer.$html;

}

?>
