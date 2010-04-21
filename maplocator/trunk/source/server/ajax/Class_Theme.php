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
*This file contains defination of class Theme ani its related functionalities.
*
***/
class Theme {
  public $id;
  public $name;
  public $description;
  public $status;
  public $theme_type;
  public $parent_id;
  public $icon;
  public $geolocation;
  public $country_id;
  public $nid;
  public $images;
  public $videos;
  public $created_by;
  public $created_date;
  public $modified_by;
  public $modified_date;

  public function Theme($obj) {
    $this->id = $obj->theme_id;
    $this->name = $obj->theme_name;
    $this->description = $obj->theme_description;
    $this->status = $obj->status;
    $this->theme_type = $obj->theme_type;
    $this->parent_id = $obj->parent_id;
    $this->icon = $obj->icon;
    $this->geolocation = $obj->geolocation;
    $this->country_id = $obj->country_id;
    $this->nid = $obj->nid;
    $this->images = $obj->images;
    $this->videos = $obj->videos;
    $this->created_by = getUserName($obj->created_by);
    $this->created_date = $obj->created_date;
    $this->modified_by = getUserName($obj->modified_by);
    $this->modified_date = $obj->modified_date;
  }

  public static function getThemes($query, $query_args) {
    $themeTree = $GLOBALS['themeTree'];
    $layersCheckedThemes = $GLOBALS['layersCheckedThemes'];
    $arr_theme = array();
    $i = 0;
    $result_theme = db_query($query, $query_args);
    if(!$result_theme) {
      //Error occured
      $errmsgstr = $GLOBALS['errmsgstr'];
      die('Error fetching themes. ' . $errmsgstr);
    } else {
      while($theme_obj = db_fetch_object($result_theme)) {
        $theme_id = $theme_obj->theme_id;
        if ($theme_obj->theme_type == 1) {
          $imgUrl = './'.path_to_theme().'/images/icons/theme-'. $theme_obj->icon .'.png' ;
          if(file_exists($imgUrl)) {
          //switch case to add mouse over text for the themes
            switch($theme_obj->theme_name) {
              case "Abiotic":
                $tooltip = "Soil, water, climate";
                break;
              case "Administrative Units":
                $tooltip = "Administrative units";
                break;
              case "Biogeography":
                $tooltip = "Biogeographical units";
                break;
              case "Conservation":
                $tooltip = "Conservation areas";
                break;
              case "Demography":
                $tooltip = "Census and population distribution";
                break;
              case "Land Use Land Cover":
                $tooltip = "Land use land cover";
                break;
              case "Species":
                $tooltip = "Species distribution";
                break;
              case "General":
                $tooltip = "General sandbox";
                break;
            }
            $arr_theme[$i]["text"] = '<table cellspacing="0" style="border-collapse:separate;"><tr><td><div class="LayerTreeElem"><img src="'. $imgUrl .'"" alt="'.$theme_obj->theme_name.'" /></div></td><td><div class="LayerTreeElem"><b title="' . $tooltip . '">'. str_replace(" ", "&nbsp;", $theme_obj->theme_name) .'</b>';
          } else {
            $arr_theme[$i]["text"] = '<table cellspacing="0" style="border-collapse:separate;"><tr><td><div class="LayerTreeElem"><b>'. str_replace(" ", "&nbsp;", $theme_obj->theme_name) .'</b>';
          }
        } else {
          $arr_theme[$i]["text"] = '<table cellspacing="0" style="border-collapse:separate;"><tr><td><div class="LayerTreeElem"><b>'. str_replace(" ", "&nbsp;", $theme_obj->theme_name) .'</b>';
        }
        $arr_theme[$i]["text"] .= "&nbsp;(".$theme_obj->layer_count.")</div></td></tr></table>";

        $arr_theme[$i]["id"]= $theme_id;
        $arr_theme[$i]["title"] = $theme_obj->theme_name;
        if((isset($themeTree[$theme_id]) && ($themeTree[$theme_id] == 1)) || in_array($theme_id, $layersCheckedThemes) ) {
          $arr_theme[$i]['expanded'] = true;
          $arr_theme[$i]["children"] = array_merge(getSubThemes($theme_id), getLayersForTheme($theme_id));
        } else {
          $arr_theme[$i]["hasChildren"] = true;
        }
        $i++;
      }
    }
    return $arr_theme;
  }


}
?>
