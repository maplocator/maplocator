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

require_once 'ml_header.php';

$base_path=base_path();

$errmsgstr="Please try after sometime or contact admin.";

$infoEntry=<<<EOF
    <tr>
    <td valign="top" class="key">{%key}</td>
    <td valign="top" class="value">{%value}</td>
  </tr>
EOF;

function return_error($msg) {
  $err=<<<END
        <?xml version="1.0"?>
<mapdata>
<error value='true'>
<message>
END;
  $err.=$msg;
  $err.=<<< END
        </message>
</error>
</mapdata>
END;
  return $err;
}

function get_layer_data_summary($layer_tablename, $row_id, $point) {
  /* Show feature popup */
  $arr_popup=array();
  $summary_cols=array();
  $arr_license=array();
  $table_type="layer";
  $layer_id=0;
  $col_type="summary";

  $layer_info=GetTableColsOfType($layer_tablename, $table_type, $col_type, $layer_id);
  $layer_id=$layer_info['layer_id'];
  $layer_tablename=$layer_info['layer_tablename'];
  $layer_summary_columns=$layer_info['summary_columns'];

  $layer_name="";
  $layer_title_column="";
  $layer_license="";
  $layer_title_column_sql="";

  $columns_arr=array();
  $columns_arr[]='layer_name';
  $columns_arr[]='title_column';
  $columns_arr[]='license';
  $columns_arr[]='attribution';

  $metainfo=get_values_metatable($layer_tablename, $layer_id, TABLE_TYPE_LAYER, $columns_arr);

  $layer_name=$metainfo['layer_name'];
  $layer_title_column=str_replace("'", "", $metainfo['title_column']);
  $layer_license=$metainfo['license'];
  $layer_attribution=$metainfo['attribution'];

  $license=getCCLicenseHTMLForSummary($layer_license);
  if($license != $layer_license) {
    $arr_license['license']=$license[0];
    $arr_license['img_size']=$license[1];
  }
  else {
    $arr_license['license']=$license;
  }

  if($layer_title_column != "") {
    $layer_title_column_sql=", \"{$layer_title_column}\" as __feature_title";
  }

  $global_resource_mapping=getResourceTableMapping($layer_tablename);

  $ret_access=menu_execute_active_handler("node/add/node-mlocate-feature/popup");

  $feature_title="";
  $created_by_user_id="";
  $created_by_user="";
  $created_date="";
  $modified_by_user_id="";
  $modified_by_user="";
  $modified_date="";
  $summary="";
  $nid=0;
  $menu="";

  $fullattribution=$layer_attribution;

  $isvalidated="notvalidated";
  $isvalidated_msg="The content has not been validated";
  $show_validate="hide";

  $validated_by_user_id=0;
  $validated_by_user="";
  $validated_date="";

  if($point != NULL) {
    $query="SELECT " . AUTO_DBCOL_PREFIX . "id FROM \"%s\" where ST_Contains(" . AUTO_DBCOL_PREFIX . "topology,GeomFromText('%s', (select srid from geometry_columns where f_table_name = '%s')))";
    $query_args=array($layer_tablename, $point, $layer_tablename);
    $result_category=db_query($query, $query_args);
    if(!$result_category) {
      //Error occured
      $errmsgstr=$GLOBALS['errmsgstr'];
      die('Error fetching data. ' . $errmsgstr);
    }
    else {
      if($obj=db_fetch_object($result_category)) {
        $row_id=$obj-> {
          AUTO_DBCOL_PREFIX . "id"
        };
      }
    }
  }

  $query="SELECT " . AUTO_DBCOL_PREFIX . "id," . AUTO_DBCOL_PREFIX . "layer_id," . AUTO_DBCOL_PREFIX . "status," . AUTO_DBCOL_PREFIX . "created_by," . AUTO_DBCOL_PREFIX . "created_date," . AUTO_DBCOL_PREFIX . "modified_by," . AUTO_DBCOL_PREFIX . "modified_date," . AUTO_DBCOL_PREFIX . "validated_by," . AUTO_DBCOL_PREFIX . "validated_date, %s," . AUTO_DBCOL_PREFIX . "nid %s FROM \"%s\" where " . AUTO_DBCOL_PREFIX . "id = %d";
  $query_args=array(str_replace("'", '"', $layer_summary_columns), $layer_title_column_sql, $layer_tablename, $row_id);
  $result_category=db_query($query, $query_args);
  if(!$result_category) {
    //Error occured
    $errmsgstr=$GLOBALS['errmsgstr'];
    die('Error fetching layer info. ' . $errmsgstr);
  }
  else {
    $col_info=getDBColDesc($layer_tablename, $layer_summary_columns);

    $table_info=GetTableColsOfType($layer_tablename, 'layer', 'italics');
    $italics_columns=$table_info['italics_columns'];

    while($category_obj=db_fetch_object($result_category)) {
      foreach($category_obj as $key=>$value) {
        switch($key) {
          case AUTO_DBCOL_PREFIX . 'id':
            $row_id=$value;
            break;

          case AUTO_DBCOL_PREFIX . 'layer_id':
            break;

          case AUTO_DBCOL_PREFIX . 'nid':
            break;

          case '__feature_title':
            $feature_title=$value;
            break;

          case AUTO_DBCOL_PREFIX . 'status':
            if($value == 0) {
              $isvalidated="notvalidated";
              $isvalidated_msg="The content has not been validated";
              global $user;
              if($user->uid) {
                $show_validate="show";
              }
            }
            else {
              $isvalidated="validated";
              $isvalidated_msg="The content has been validated";
            }
            break;

          case AUTO_DBCOL_PREFIX . 'created_by':
            $created_by_user=getUserName($value);
            $created_by_user_id=$value;
            break;

          case AUTO_DBCOL_PREFIX . 'modified_by':
            if($value) {
              $modified_by_user=getUserName($value);
              $modified_by_user_id=$value;
            }
            break;

          case AUTO_DBCOL_PREFIX . 'validated_by':
            if($value) {
              $validated_by_user=getUserName($value);
            }
            break;

          case AUTO_DBCOL_PREFIX . 'created_date':
            $created_date=$value;
            break;

          case AUTO_DBCOL_PREFIX . 'modified_date':
            $modified_date=$value;
            break;

          case AUTO_DBCOL_PREFIX . 'validated_date':
            $validated_date=$value;
            break;

          case 'attribution':
            $fullattribution=$value;
            break;

          default:
            if(strpos($italics_columns, "'{$key}'") !== FALSE) {
              $val=($value == '' ? '&nbsp;' : '<i>' . str_replace(" ", "&nbsp;", $value) . '</i>');
            }
            else {
              $val=$value;
            }
            if("" == $val)
              $summary_cols[($col_info[$key] == "" ? $key : $col_info[$key])]="";
            else
              $summary_cols[($col_info[$key] == "" ? $key : $col_info[$key])]=$val;
            break;
        }
      }
    }

    $attribution=$fullattribution;

    if($isvalidated == "validated") {
      $isvalidated_msg.=" by {$validated_by_user} on {$validated_date}";
    }

    $menu='<li class="last"><a href="javascript:showDetailsPopup(\'' . $layer_tablename . '\',\'' . $row_id . '\', \'' . $layer_name . ': ' . $feature_title . '\')">More details...</a></li>';

    $arr_popup['layer_name']=$layer_name;
    if("" == $feature_title)
      $arr_popup['feature_title']="";
    else
      $arr_popup['feature_title']=$feature_title;

    $arr_popup['layer_tablename']=$layer_tablename;
    $arr_popup['feature_id']=$row_id;
    $arr_popup['created_by_user']=$created_by_user;
    $arr_popup['created_by_user_id']=$created_by_user_id;
    $arr_popup['created_date']=$created_date;
    $arr_popup['modified_by_user']=$modified_by_user;
    $arr_popup['modified_by_user_id']=$modified_by_user_id;
    $arr_popup['modified_date']=$modified_date;
    $arr_popup['summary']=$summary_cols;
    if("" == $isvalidated_msg)
      $arr_popup['isvalidated_msg']="";
    else
      $arr_popup['isvalidated_msg']=$isvalidated_msg;
    if("" == $isvalidated)
      $arr_popup['isvalidated']="";
    else
      $arr_popup['isvalidated']=$isvalidated;
    if("" == $show_validate)
      $arr_popup['show_validate']="";
    else
      $arr_popup['show_validate']=$show_validate;
    if("" == $fullattribution)
      $arr_popup['fullattribution']="";
    else
      $arr_popup['fullattribution']=$fullattribution;
    if("" == $attribution)
      $arr_popup['attribution']="";
    else
      $arr_popup['attribution']=$attribution;

    $arr_popup['license']=$arr_license;

    print json_encode($arr_popup);
  }
}

function get_layer_data_details($layer_tablename, $row_id, $hasmenu=false) {
  $layer_name="";
  $layer_title_column="";
  $layer_license="";
  $layer_title_column_sql="";

  $columns_arr=array();
  $columns_arr[]='layer_name';
  $columns_arr[]='title_column';
  $columns_arr[]='license';
  $columns_arr[]='attribution';
  $columns_arr[]='layer_type';

  $metainfo=get_values_metatable($layer_tablename, $layer_id, TABLE_TYPE_LAYER, $columns_arr);

  $layer_name=$metainfo['layer_name'];
  $layer_title_column=str_replace("'", "", $metainfo['title_column']);
  $layer_license=$metainfo['license'];
  $layer_attribution=$metainfo['attribution'];

  $license=getCCLicenseHTMLForSummary($layer_license);
  if($license != $layer_license) {
    $arr_license['license']=$license[0];
    $arr_license['img_size']=$license[1];
  }
  else {
    $arr_license['license']=$license;
  }

  if($layer_title_column != "") {
    $layer_title_column_sql=", \"{$layer_title_column}\" as __feature_title";
  }

  $global_resource_mapping=getResourceTableMapping($layer_tablename);

  $ret_access=menu_execute_active_handler("node/add/node-mlocate-feature/popup");

  $feature_title="";
  $created_by_user_id="";
  $created_by_user="";
  $created_date="";
  $modified_by_user_id="";
  $modified_by_user="";
  $modified_date="";
  $content="";
  $nid=0;
  $menu="";
  $attribution=$layer_attribution;

  $isvalidated="notvalidated";
  $isvalidated_msg="The content has not been validated";
  $show_validate="hide";

  $validated_by_user_id=0;
  $validated_by_user="";
  $validated_date="";

  $query='SELECT * %s FROM "%s" where ' . AUTO_DBCOL_PREFIX . 'id = %d';
  $query_args=array($layer_title_column_sql, $layer_tablename, $row_id);
  $result_category=db_query($query, $query_args);
  if(!$result_category) {
    //Error occured
    $errmsgstr=$GLOBALS['errmsgstr'];
    die('Error fetching layer info. ' . $errmsgstr);
  }
  else {
    $col_info=getDBColDesc($layer_tablename);

    $table_info=GetTableColsOfType($layer_tablename, 'layer', 'italics');
    $italics_columns=$table_info['italics_columns'];

    while($category_obj=db_fetch_object($result_category)) {
      foreach($category_obj as $key=>$value) {
        switch($key) {
          case AUTO_DBCOL_PREFIX . 'id':
            $row_id=$value;
            break;

          case AUTO_DBCOL_PREFIX . 'layer_id':
            break;

          case AUTO_DBCOL_PREFIX . 'nid':
            if($value == 0) {
              if(is_int($ret_access)) {
                if($ret_access == MENU_ACCESS_DENIED) {
                  global $user;
                  if($user->uid < 1) {
                    $details_type="add";
                    $details_lnk="{$base_path}node/add/node-mlocate-feature/popup?layer_tablename={$layer_tablename}&point_id={$row_id}";
                  }
                }
              }
              else {
                $details_type="add";
                $details_lnk="{$base_path}node/add/node-mlocate-feature/popup?layer_tablename={$layer_tablename}&point_id={$row_id}";
              }
            }
            else {
              $details_type="more";
              $details_lnk="{$base_path}node/{$value}/popup";
              $nid=$value;
            }
            break;

          case '__feature_title':
            $feature_title=$value;
            break;

          case AUTO_DBCOL_PREFIX . 'created_by':
            $created_by_user=getUserName($value);
            $created_by_user_id=$value;
            break;

          case AUTO_DBCOL_PREFIX . 'modified_by':
            if($value) {
              $modified_by_user=getUserName($value);
              $modified_by_user_id=$value;
            }
            break;

          case AUTO_DBCOL_PREFIX . 'validated_by':
            if($value) {
              $validated_by_user=getUserName($value);
              $validated_by_user_id=$value;
            }
            break;

          case AUTO_DBCOL_PREFIX . 'created_date':
            $created_date=$value;
            break;

          case AUTO_DBCOL_PREFIX . 'modified_date':
            $modified_date=$value;
            break;

          case AUTO_DBCOL_PREFIX . 'validated_date':
            $validated_date=$value;
            break;

          case AUTO_DBCOL_PREFIX . 'status':
            if($value == 0) {
              $isvalidated="notvalidated";
              $isvalidated_msg="The content has not been validated";
              global $user;
              if($user->uid) {
                $show_validate="show";
              }
            }
            else {
              $isvalidated="validated";
              $isvalidated_msg="The content has been validated";
            }
            break;

          case AUTO_DBCOL_PREFIX . 'topology':
            break;

          case AUTO_DBCOL_PREFIX . 'validated_by':
            break;

          case AUTO_DBCOL_PREFIX . 'validated_date':
            break;

          default:
            $val=$value;
            if(strpos($italics_columns, "'{$key}'") !== FALSE) {
              $val=($value == '' ? '&nbsp;' : '<i>' . str_replace(" ", "&nbsp;", $value) . '</i>');
            }
            if(array_key_exists($key, $global_resource_mapping)) {
              $href="{$base_path}ml_orchestrator.php?action=getResourceTableEntry&resource_tablename=" . $global_resource_mapping[$key]['resource_tablename'] . "&resource_column=" . $global_resource_mapping[$key]['resource_column'] . "&value={$value}";
              $val=($value == '' ? '&nbsp;' : "<a id='a_{$value}' name='" . $global_resource_mapping[$key]['resource_tablename'] . "' href='{$href}'onClick='javascript:showAjaxLinkPopup(this.href, this.name);return false;'>" . str_replace(" ", "&nbsp;", $value) . "</a>");
            }
            if("" == $val)
              $summary_cols[($col_info[$key] == "" ? $key : $col_info[$key])]="";
            else
              $summary_cols[($col_info[$key] == "" ? $key : $col_info[$key])]=$val;
            break;
        }
      }
    }

    $fullattribution=$attribution;

    if($isvalidated == "validated") {
      $isvalidated_msg.=" by {$validated_by_user} on {$validated_date}";
    }

    if(!$hasmenu) {
      $menu=getPopupUIMenu($layer_tablename, $row_id, $layer_name, $feature_title);
    }
    $menu['layer_tablename']=$layer_tablename;
    $menu['layer_name']=$layer_name;
    $menu['row_id']=$row_id;
    $menu['feature_title']=$feature_title;

    if("" == $feature_title)
      $menu['feature_title']="";
    else
      $menu['feature_title']=$feature_title;

    $menu['layer_tablename']=$layer_tablename;
    $menu['feature_id']=$row_id;
    $menu['created_by_user']=$created_by_user;
    $menu['created_by_user_id']=$created_by_user_id;
    $menu['created_date']=$created_date;
    $menu['modified_by_user']=$modified_by_user;
    $menu['modified_by_user_id']=$modified_by_user_id;
    $menu['modified_date']=$modified_date;
    $menu['content']=$summary_cols;
    if("" == $isvalidated_msg)
      $menu['isvalidated_msg']="";
    else
      $menu['isvalidated_msg']=$isvalidated_msg;
    if("" == $isvalidated)
      $menu['isvalidated']="";
    else
      $menu['isvalidated']=$isvalidated;
    if("" == $show_validate)
      $menu['show_validate']="";
    else
      $menu['show_validate']=$show_validate;
    if("" == $fullattribution)
      $menu['fullattribution']="";
    else
      $menu['fullattribution']=$fullattribution;
    if("" == $attribution)
      $menu['attribution']="";
    else
      $menu['attribution']=$attribution;

    $menu['license']=$arr_license;

    print json_encode($menu);
  }
}

function get_layer_data($layer_tablename, $row_id, $point, $tabexists) {
  /* Code to retrieve layer data to display on the page */
  if($point != NULL) {
    $query_on='POINT';
  }
  else {
    $query_on='ID';
  }

  $layer_id=0;
  list($layer_id, $layer_tablename, $layer_summary_columns)=get_layer_summary_columns($layer_tablename);

  switch($query_on) {
    case 'POINT':
      $query="SELECT " . AUTO_DBCOL_PREFIX . "id," . AUTO_DBCOL_PREFIX . "layer_id, %s," . AUTO_DBCOL_PREFIX . "nid FROM \"%s\" where ST_Contains(" . AUTO_DBCOL_PREFIX . "topology,GeomFromText('%s', (select srid from geometry_columns where f_table_name = '%s')))";
      $query_args=array(str_replace("'", '"', $layer_summary_columns), $layer_tablename, $point, $layer_tablename);
      break;

    case 'ID':
      $query="SELECT " . AUTO_DBCOL_PREFIX . "id," . AUTO_DBCOL_PREFIX . "layer_id, %s," . AUTO_DBCOL_PREFIX . "nid FROM \"%s\" where " . AUTO_DBCOL_PREFIX . "id = %d";
      $query_args=array(str_replace("'", '"', $layer_summary_columns), $layer_tablename, $row_id);
      break;
  }

  $result_category=db_query($query, $query_args);
  if(!$result_category) {
    //Error occured
    $errmsgstr=$GLOBALS['errmsgstr'];
    die('Error fetching feature info. ' . $errmsgstr);
  }
  else {
    $global_resource_mapping=getResourceTableMapping($layer_tablename);

    $col_info=getDBColDesc($layer_tablename, $layer_summary_columns);

    $ret_access=menu_execute_active_handler("node/add/node-mlocate-feature/popup");

    $details_lnk="";
    if($category_obj=db_fetch_object($result_category)) {
      $class=$category_obj;
      $ifr_src="";

      $html="";
      $html.='<div id="attribute_div" style="font:arial;display:block;"><table style="border-collapse:separate;">';
      foreach($class as $key=>$value) {
        switch($key) {
          case AUTO_DBCOL_PREFIX . 'id':
            break;

          case AUTO_DBCOL_PREFIX . 'layer_id':
            break;

          case AUTO_DBCOL_PREFIX . 'location':
            break;

          case AUTO_DBCOL_PREFIX . 'topology':
            break;

          case AUTO_DBCOL_PREFIX . 'created_by':
            $html.="<tr><td><b>Created By: </b></td><td>" . getUserLink($value) . "</td></tr>";
            break;

          case AUTO_DBCOL_PREFIX . 'modified_by':
            $html.="<tr><td><b>Modified By: </b></td><td>" . getUserLink($value) . "</td></tr>";
            break;

          case AUTO_DBCOL_PREFIX . 'status':
            $html.="<tr><td><b>Status: </b></td><td>" . (($value == 1) ? "Active" : "In-active") . "</td></tr>";
            break;

          case AUTO_DBCOL_PREFIX . 'nid':
            if($value == 0) {
              if(is_int($ret_access)) {
                switch($ret_access) {
                  case MENU_ACCESS_DENIED:
                    global $user;
                    if($user->uid < 1) {
                      $details_type="add";
                      $details_lnk="{$base_path}node/add/node-mlocate-feature/popup?layer_tablename={$layer_tablename}&point_id={$row_id}";
                    }
                    break;
                }
            }
            else {
              $details_type="add";
              $details_lnk="{$base_path}node/add/node-mlocate-feature/popup?layer_tablename={$layer_tablename}&point_id={$row_id}";
            }
          }
          else {
            $ifr_src="{$base_path}node/{$value}/popup";
            $details_type="more";
            $details_lnk=$ifr_src;
            $nid=$value;
          }
          break;

          default : if(array_key_exists($key, $global_resource_mapping)) {
            $href="{$base_path}ml_orchestrator.php?action=getResourceTableEntry&resource_tablename=" . $global_resource_mapping[$key]['resource_tablename'] . "&resource_column=" . $global_resource_mapping[$key]['resource_column'] . "&value={$value}";
            $html.="<tr><td><b title='" . $col_info[$key] . "'>" . ($col_info[$key] == "" ? $key : $col_info[$key]) . ": </b></td><td>" . ($value == '' ? '&nbsp;' : "<a id='a_{$value}' name='' href='{$href}'onClick='javascript:showAjaxLinkPopup(this.href, this.name);return false;'>" . str_replace(" ", "&nbsp;", $value) . "</a>") . "</td></tr>";
          }
          else {
            $html.="<tr><td><b title='" . $col_info[$key] . "'>" . ($col_info[$key] == "" ? $key : $col_info[$key]) . ": </b></td><td>" . ($value == '' ? '&nbsp;' : $value) . "</td></tr>";
          }
          break;
        }
      }
      $html.='</table></div>';
      if($details_lnk != "") {
        if($details_type == 'add') {
          $html='<div style="padding: 4px;"><a href="javascript:popupTabClicked(\'divPopupPane\',\'ulPopupUIMenu\',\'layerAddDetails\',\'' . $details_lnk . '\');">Add Details <img src="' . base_path() . check_url(path_to_theme()) . '/images/icons/add-details.png" alt="Add Details"></a></div>' . $html;
        }
      }

      if($tabsexist == NULL) {
        $tabs="<div id='tabs-wrapper' class='clear-block'>";
        $tabs.="<ul class='tabs primary' id='layerPopupTabs'>";
        $querystr="action=getLayerData";
        $querystr.="&layer_tablename=" . $layer_tablename;

        if($query_on == 'POINT') {
          $querystr.="&point=" . $point;
        }
        elseif($query_on == 'ID') {
          $querystr.="&id=" . $row_id;
        }

        $tabs.="<li id='layerSummary' class='active'><a href='javascript:popupTabClicked(\"divPopupPane\",\"ulPopupUIMenu\",\"layerSummary\",\"{$base_path}ml_orchestrator.php?{$querystr}&tabsexist=1\");'>Summary</a>";
        if($query_on == 'ID') {
          if(!is_int($ret_access)) {
            $tabs.="<li id='layerEdit'><a href='javascript:popupTabClicked(\"divPopupPane\",\"ulPopupUIMenu\",\"layerEdit\",\"{$base_path}ml_orchestrator.php?action=getLayerTableSchema&layer_id={$layer_id}&id={$row_id}\");'>Edit summary</a>";
          }
          $sql="select link_tablename from \"Meta_LinkTable\" where layer_id = %d";
          $result=db_query($sql, $layer_id);
          if($result) {
            $tablenames=array();
            while($obj=db_fetch_object($result)) {
              $tablenames[]=$obj->link_tablename;
            }
            if(count($tablenames) > 0) {
              $lnk="{$base_path}ml_orchestrator.php?action=getLinkTableEntry&layer_id={$layer_id}&row_id={$row_id}";
              $tabs.="<li id='layerLinkInfo'><a href='javascript:popupTabClicked(\"divPopupPane\",\"ulPopupUIMenu\",\"layerLinkInfo\",\"{$lnk}\");'>Linked Data</a>";
            }
          }
        }

        if($details_lnk != "") {
          if($details_type == 'add') {
          }
          else {
            $tabs.="<li id='layerDetails'><a href='javascript:popupTabClicked(\"divPopupPane\",\"ulPopupUIMenu\",\"layerDetails\",\"{$details_lnk}\");'>Details</a>";
            $tabs.="<li id='layerComments'><a href='javascript:popupTabClicked(\"divPopupPane\",\"ulPopupUIMenu\",\"layerComments\",\"{$base_path}comment/reply/{$nid}#comment-form\");'>Comments</a>";
          }
        }

        $tabs.="</ul>";
        $tabs.="</div>";
        echo $tabs;
        $html="<div id='divLayerPopup'>" . $html . "</div>";
      }

      if($query_on == 'POINT') {
        $query="SELECT " . AUTO_DBCOL_PREFIX . "id FROM \"%s\" where ST_Contains(" . AUTO_DBCOL_PREFIX . "topology,GeomFromText('%s', (select srid from geometry_columns where f_table_name = '%s')))";
        $query_args=array($layer_tablename, $point, $layer_tablename);
        $result_category=db_query($query, $query_args);
        if(!$result_category) {
          //Error occured
          die(return_error('Error fetching layer data'));
        }
        else {
          while($obj=db_fetch_object($result_category))
            $html.='|' . $obj-> {
              AUTO_DBCOL_PREFIX . 'id'
            };
        }
      }

      echo $html;
    }
  }
}

function get_link_table_entries($layer_tablename, $row_id, $link_tablename) {
  $arr_result=array();
  $column_names=array();
  $arr_mlocate=array();
  $r=1;

  $participation_type=getLayerParticipationType($layer_tablename);

  $table_info=GetTableColsOfType($link_tablename, 'link', 'italics');
  $italics_columns=$table_info['italics_columns'];

  $query="select description, linked_column, layer_column from \"Meta_LinkTable\" where link_tablename = '%s'";
  $result=db_query($query, $link_tablename);
  if(!$result) {
    //Error occured
    $arr_result['error']='Error fetching data from DB';
  }
  else {

    $obj=db_fetch_object($result);
    $link_description=$obj->description;
    $linked_column=str_replace("'", "", $obj->linked_column);
    $layer_column=str_replace("'", "", $obj->layer_column);

    $linked_value= - 1;

    $col_info=getDBColDesc($link_tablename);

    $global_resource_mapping=getResourceTableMapping($link_tablename);

    $query='SELECT "%s".* FROM "%s", "%s" where "%s"."%s" = "%s"."%s" and "%s".' . AUTO_DBCOL_PREFIX . 'id = %d';
    $query_args=array($link_tablename, $link_tablename, $layer_tablename, $link_tablename, $linked_column, $layer_tablename, $layer_column, $layer_tablename, $row_id);
    $result=db_query($query, $query_args);
    if(!$result) {
      //Error occured
      $arr_result['error']='Error fetching links';
    }
    else {
      $arr_result['description']=($metalink[$link]['description'] == "" ? '' : $metalink[$link]['description']);

      foreach($col_info as $key=>$val) {
        if(substr($key, 0, strlen(AUTO_DBCOL_PREFIX)) != AUTO_DBCOL_PREFIX) {
          $column_names[$key]=($col_info[$key] == "" ? str_replace(" ", "&nbsp;", $key) : str_replace(" ", "&nbsp;", $col_info[$key]));
        }
      }
      if($participation_type == 1 || $participation_type == 2 || $participation_type == 3) {
        $column_names['created_by']='Created By';
        $column_names['created_date']='Created Date';
        $column_names['modified_by']='Modified By';
        $column_names['modified_date']='Modified Date';
      }
      $arr_result['col_names']=$column_names;

      $tbody="";
      $i=0;
      $r=0;
      $data=array();
      while($obj=db_fetch_object($result)) {
        if($i == 0) {
          $linked_value=$obj-> {
            $linked_column
          };
          $i+=1;
        }

        $row=array();
        $rw=array();
        foreach($obj as $key=>$value) {
          if((($participation_type == 1 || $participation_type == 2 || $participation_type == 3) && ($key == AUTO_DBCOL_PREFIX . "created_by" || $key == AUTO_DBCOL_PREFIX . "created_date" || $key == AUTO_DBCOL_PREFIX . "modified_by" || $key == AUTO_DBCOL_PREFIX . "modified_date"))) {
            switch($key) {
              case AUTO_DBCOL_PREFIX . 'created_by':
                $rw['created_by']='<a href="' . $base_path . 'user/' . $value . '" target="_blank">' . getUserName($value) . '</a>';
                break;

              case AUTO_DBCOL_PREFIX . 'modified_by':
                $rw['modified_by']='<a href="' . $base_path . 'user/' . $value . '" target="_blank">' . getUserName($value) . '</a>';
                break;

              case AUTO_DBCOL_PREFIX . 'created_date':
                $rw['created_date']=($value == '' ? '&nbsp;' : str_replace(" ", "&nbsp;", $value));
                break;

              case AUTO_DBCOL_PREFIX . 'modified_date':
                $rw['modified_date']=($value == '' ? '&nbsp;' : str_replace(" ", "&nbsp;", $value));
                break;
            }
          }
          elseif(substr($key, 0, strlen(AUTO_DBCOL_PREFIX)) != AUTO_DBCOL_PREFIX) {
            if(array_key_exists($key, $global_resource_mapping)) {
              if($value == "") {
                $val='&nbsp;';
              }
              else {
                $href="{$base_path}ml_orchestrator.php?action=getResourceTableEntry&resource_tablename=" . urlencode($global_resource_mapping[$key]['resource_tablename']) . "&resource_column=" . urlencode($global_resource_mapping[$key]['resource_column']) . "&value=" . urlencode($value);
                $val=($value == '' ? '&nbsp;' : "<a id='a_{$value}' name='" . $global_resource_mapping[$key]['resource_tablename'] . "' href='{$href}' class='jTip1' onClick='javascript:showAjaxLinkPopup(this.href, this.name);return false;'>" . str_replace(" ", "&nbsp;", $value) . "</a>");
              }
            }
            elseif(strpos($italics_columns, "'{$key}'") !== FALSE) {
              $val=($value == '' ? '&nbsp;' : '<i>' . str_replace(" ", "&nbsp;", $value) . '</i>');
            }
            else {
              $val=($value == '' ? '&nbsp;' : str_replace(" ", "&nbsp;", $value));
            }
            $row[$key]=$val;
          }
        }
        $row=array_merge($row, $rw);
        $data[]=$row;
        $r++;
      }
      $arr_result['data_count']=$r;
      $arr_result['data']=$data;
      if($i == 0) {
        $arr_result['no_record']="Sorry! No records found.";
      }
      if(userHasAddLinkedDataPerm($layer_tablename)) {
        $arr_result['add_linked_data_lnk']=$base_path . 'ml_orchestrator.php?action=getLinkTableSchema&link_tablename=' . $link_tablename . '&linked_column=' . $linked_column . '&linked_value=' . $linked_value;
      }
      $arr_result['link_tablename']=$link_tablename;
      $arr_result['linked_column']=$linked_column;
      $arr_result['linked_value']=$linked_value;

      print json_encode($arr_result);
    }
  }
}

function get_link_table_entry($layer_tablename, $row_id) {
  $html="";

  $query="select mlt.link_tablename, mlt.description, mlt.linked_column, mlt.layer_column from \"Meta_LinkTable\" mlt join \"Meta_Layer\" ml on mlt.layer_id = ml.layer_id and ml.layer_tablename = '%s'";
  $result=db_query($query, $layer_tablename);
  if(!$result) {
    //Error occured
    die(return_error('Error fetching data from DB'));
  }
  else {
    $metalink=array();
    $tablenames=array();
    while($obj=db_fetch_object($result)) {
      $tablenames[]=$obj->link_tablename;
      $metalink[$obj->link_tablename]['description']=$obj->description;
      $metalink[$obj->link_tablename]['linked_column']=$obj->linked_column;
      $metalink[$obj->link_tablename]['layer_column']=$obj->layer_column;
    }
    foreach($tablenames as $link) {
      $linked_column=$metalink[$link]['linked_column'];
      $layer_column=$metalink[$link]['layer_column'];

      $col_info=getDBColDesc($link);

      $global_resource_mapping=getResourceTableMapping($link);

      $query='SELECT "%s".* FROM "%s", "%s" where "%s"."%s" = "%s"."%s" and "%s".' . AUTO_DBCOL_PREFIX . 'id = %d';
      $query_args=array($link, $link, $layer_tablename, $link, $linked_column, $layer_tablename, $layer_column, $layer_tablename, $row_id);
      $result=db_query($query, $query_args);
      if(!$result) {
        //Error occured
        die(return_error('Error fetching links'));
      }
      else {
        $html.='<div id="" style=font:arial><b><u>' . $metalink[$link]['description'] . '</b></u><table id="linkedData">';
        $tbody="";
        $i=0;
        while($obj=db_fetch_object($result)) {
          if($i == 0) {
            $html.='<thead><tr align=center>';
            foreach($obj as $key=>$value) {
              if(array_key_exists($key, $col_info)) {
                $html.='<th align=center>' . ($col_info[$key] == "" ? $key : $col_info[$key]) . '</th>';
              }
              else {
                $html.='<th align=center>' . $key . '</th>';
              }
            }
            $html.='</tr></thead><tbody>';
            $i+=1;
          }

          $tbody.='<tr align=center>';
          foreach($obj as $key=>$value) {
            if(array_key_exists($key, $global_resource_mapping)) {
              if($value == "") {
                $tbody.='<td align=center>&nbsp;</td>';
              }
              else {
                $href="{$base_path}ml_orchestrator.php?action=getResourceTableEntry&resource_tablename=" . $global_resource_mapping[$key]['resource_tablename'] . "&resource_column=" . $global_resource_mapping[$key]['resource_column'] . "&value={$value}";
                $tbody.='<td align=center>' . ($value == '' ? '&nbsp;' : "<a id='a_{$value}' name='" . $global_resource_mapping[$key]['resource_tablename'] . "' href='{$href}' class='jTip1' onClick='javascript:showAjaxLinkPopup(this.href, this.name);return false;'>" . str_replace(" ", "&nbsp;", $value) . "</a>") . '</td>';
              }
            }
            else {
              $tbody.='<td align=center>' . ($value == '' ? '&nbsp;' : $value) . '</td>';
            }
          }
          $tbody.='</tr>';
        }
        $html.=$tbody . '</tbody></table></div>';
      }
    }
  }
  echo $script . $html;
}

function get_layer_table_schema($layer_tablename, $row_id, $topology) {
  $table_type="layer";
  $col_type="editable";

  $layer_info=GetTableColsOfType($layer_tablename, $table_type, $col_type, $layer_id);
  $layer_id=$layer_info['layer_id'];
  $layer_tablename=$layer_info['layer_tablename'];
  $layer_editable_columns=$layer_info['editable_columns'];
  if($layer_editable_columns == "") {
    die("<b>No editable columns specified. Please contact the admin.</b>");
  }

  $query=GetQueryColumnDetails($layer_tablename, $layer_editable_columns, NULL, FALSE);
  $result_cols=db_query($query[0], $query[1]);

  if(!$result_cols) {
    //Error occured
    $errmsgstr=$GLOBALS['errmsgstr'];
    die('Error fetching columns. ' . $errmsgstr);
  }
  else {
    //global $base_url;
    if($row_id != NULL) {
      $query='select %s from "%s" where ' . AUTO_DBCOL_PREFIX . 'id = %d';
      $query_args=array(str_replace("'", '"', $layer_editable_columns), $layer_tablename, $row_id);
      $result_layer_row=db_query($query, $query_args);
      if(!$result_layer_row) {
        $errmsgstr=$GLOBALS['errmsgstr'];
        die('Error fetching editable columns. ' . $errmsgstr);
      }
      else {
        $vals_layer_row=db_fetch_array($result_layer_row);
      }
    }
    else {
      $hidden.="<input type='hidden' name='topology' id='topology' value='" . $topology . "'>";
    }

    $hidden.="<input type='hidden' name='layer_tablename' value='{$layer_tablename}'>";
    $hidden.="<input type='hidden' name='edit-" . AUTO_DBCOL_PREFIX . "layer_id' id='edit-" . AUTO_DBCOL_PREFIX . "layer_id' value='" . $layer_id . "'>";
    $hidden.="<input type='hidden' name='edit-__id' id='edit-__id' value='$id'>";
    $html.="<div id='form_mlocate_error' class='error'></div>";
    if($row_id != NULL) {
      $html.=GetDBTableSchemaTable($layer_tablename, TABLE_TYPE_LAYER, $result_cols, $vals_layer_row);
    }
    else {
      $html.=GetDBTableSchemaTable($layer_tablename, TABLE_TYPE_LAYER, $result_cols);
    }

    echo "<form id='frmLayerInfoSave' method='post' action='{$base_path}save_info.php?action=savelayerfeaturedata'>";
    echo $html . $hidden;
    echo "<div style='margin:10px'><input type='button' value='Submit' onClick='javascript:submitForm(\"#frmLayerInfoSave\",\"#form_mlocate_error\")'></div>";
    echo "</form>";

    $css='<link href="' . $base_path . path_to_theme() . '/css/style.css" media="all" rel="stylesheet" type="text/css">';
    $css.='<link href="' . $base_path . path_to_theme() . '/css/popup.css" media="all" rel="stylesheet" type="text/css">';
    echo $css;

    $js="<script language='javascript' src='{$base_path}misc/jquery.js'></script>";
    $js.="<script language='javascript' src='{$base_path}misc/jquery.form.js'></script>";
    $js.='<script src="' . $base_path . path_to_theme() . '/scripts/jquery.dimensions.js" type="text/javascript"></script>';
    $js.='<script src="' . $base_path . path_to_theme() . '/scripts/jquery.wresize.js" type="text/javascript"></script>';
    $js.='<script src="' . $base_path . path_to_theme() . '/scripts/jquery-ui.js" type="text/javascript"></script>';
    $js.='<script src="' . $base_path . path_to_theme() . '/scripts/ui.datepicker.js" type="text/javascript"></script>';
    $js.='<script src="' . $base_path . path_to_theme() . '/scripts/jquery.cookie.js" type="text/javascript"></script>';
    $js.='<script src="' . $base_path . path_to_theme() . '/scripts/jquery.blockUI.js" type="text/javascript"></script>';
    $js.='<script src="' . $base_path . path_to_theme() . '/scripts/jquery.maskedinput.js" type="text/javascript"></script>';
    $js.='<script src="' . $base_path . path_to_theme() . '/scripts/jquery.expander.js" type="text/javascript"></script>';
    $js.='<script src="' . $base_path . path_to_theme() . '/scripts/jquery-linkize.js" type="text/javascript"></script>';
    $js.="<script language='javascript' src='{$base_path}" . drupal_get_path('module', 'map') . "/functions.js'></script>";
    $js.="<script language='javascript'>maskInputs();</script>";
    echo $js;
  }
}

function get_layers($theme_id) {
  $arr=array();
  $i=0;
  //query modified to check if layer's editable
  $query='select layer_id, layer_name, layer_tablename from "Meta_Layer" where participation_type in (1,2,3) AND layer_id in (select layer_id from "Theme_Layer_Mapping" where theme_id = %d) ';
  $result=db_query($query, $theme_id);
  while($row=db_fetch_array($result)) {
    $arr[$i]['layer_id']=$row['layer_id'];
    $arr[$i]['layer_name']=$row['layer_name'];
    $arr[$i]['layer_tablename']=$row['layer_tablename'];
    $i++;
  }

  print json_encode($arr);
}

function get_layer_details($layer_tablename, $fids) {
  $query='select layer_type,layer_id,color_by,size_by,participation_type, nid, p_nid,access,layer_name,max_scale, is_timebased from "Meta_Layer" where layer_tablename= \'%s\'';
  $result=db_query($query, $layer_tablename);
  while($row=db_fetch_object($result)) {
    $layertype;
    $arr_str=array();
    $arr_str['layer_tablename']=$layer_tablename;
    switch($row->layer_type) {
      case "POINT":
      case "MULTIPOINT":
        $layertype='POINT';
        break;

      case "MULTIPOLYGON":
      case "POLYGON":
        $layertype='POLYGON';
        break;

      case "MULTILINESTRING":
      case "LINE":
        $layertype='LINE';
        break;

      default:
        $layertype=$row->layer_type;
        break;
    }
    // switch

    $arr_str['layer_type']=$layertype;
    $arr_str['layer_id']=$row->layer_id;

    if($layertype == 'POLYGON' || $layertype == 'LINE') {
      if($row->color_by == '') {
        $arr_str['variation_by_column']='(No color-by column specified)';
      }
      else {
        $col_info=getDBColDesc($layer_tablename, $row->color_by);
        $color_by=str_replace("'", "", $row->color_by);
        $arr_str['variation_by_column']='(' . ($col_info[$color_by] == "" ? $color_by : $col_info[$color_by]) . ')';
      }
    }
    elseif($layertype == 'POINT') {
      if($row->size_by == '') {
        $arr_str['variation_by_column']='(No size-by column specified)';
      }
      else {
        $col_info=getDBColDesc($layer_tablename, $row->size_by);
        $size_by=str_replace("'", "", $row->size_by);
        $arr_str['variation_by_column']='(' . ($col_info[$size_by] == "" ? $size_by : $col_info[$size_by]) . ')';
      }
    }

    $hasAddPerm=(userHasAddFeaturePerm($layer_tablename) ? 1 : 0);

    $arr_str['participation_type']=$row->participation_type;
    $arr_str['nid']=$row->nid;
    $arr_str['p_nid']=$row->p_nid;
    $arr_str['addFeaturePerm']=$hasAddPerm;
    $arr_str['projection']=getLayerProjection($layer_tablename);
    $arr_str['access']=$row->access;
    $arr_str['layer_name']=$row->layer_name;

    $max_zoom=$row->max_scale;
    if($max_zoom == NULL || $max_zoom == "" || $max_zoom < 5) {
      $max_zoom=19;
    }
    $arr_str['max_zoom']=$max_zoom;

    /*
     * feature count cant be determined for raster layer,so value one
     * is assigned so that the map will be zoomed to the layer extent
     * when the layer is added.
    */

    if('RASTER' == $layertype)
      $f_count=1;
    else
      $f_count=getFeatureCount($layer_tablename);
    $arr_str['feature_count']=$f_count;

    $extent='';
    if($fids != NULL) {
      $extent=getLayerExtent($layer_tablename, $layertype, $fids);
    }
    else {
      $extent=getLayerExtent($layer_tablename, $layertype);
    }
    $arr_str['extent']=$extent;

    $arr_str['is_timebased']=$row->is_timebased;
  }
  print json_encode($arr_str);
}

function get_link_table_schema($link_tablename, $linked_column, $linked_value, $record_type_id, $row_id) {
  // TODO: Make similar logic change for layer schema
  $tab="";

  if($record_type_id != NULL) {
    if($record_type_id == 0) {
      die("");
    }
    else {
      $link_info=GetTableColsOfType($link_tablename, TABLE_TYPE_LINK, 'mandatory');
      $mandatory_columns=$link_info['mandatory_columns'];

      $query="select record_columns from mlocate_data_record_types where id = %d";
      $obj=db_fetch_object(db_query($query, $record_type_id));
      $record_columns=$obj->record_columns;

      $columns=$mandatory_columns . ',' . $record_columns;
      if($columns[0] == ',') {
        $columns=substr($columns, 1);
      }
      elseif($columns[strlen($columns) - 1] == ',') {
        $columns=substr($columns, 0, - 1);
      }

      if($columns != '') {
        $query=GetQueryColumnDetails($link_tablename, $columns, FALSE);
        $result_cols=db_query($query[0], $query[1]);

        if($row_id != NULL) {
          $query='select %s from "%s" where id = %d';
          $query_args=array(str_replace("'", '"', $columns), $link_tablename, $row_id);
          $result_link_row=db_query($query, $query_args);
          $vals_link_row=db_fetch_array($result_link_row);
          $tab=GetDBTableSchemaTable($link_tablename, TABLE_TYPE_LINK, $result_cols, $vals_link_row);
        }
        else {
          $tab=GetDBTableSchemaTable($link_tablename, TABLE_TYPE_LINK, $result_cols);
        }
      }
      else {
        die("Editable columns have not been specified for this record type. Please contact the admin.");
      }

      $query="select record_type_column from \"Meta_LinkTable\" where link_tablename = '%s'";
      $obj=db_fetch_object(db_query($query, $link_tablename));
      $record_type_column=str_replace("'", "", $obj->record_type_column);

      $query="select record_type from mlocate_data_record_types where id = %d";
      $obj=db_fetch_object(db_query($query, $record_type_id));
      $record_type_name=$obj->record_type;

      $hidden.="<input type='hidden' name='edit-{$record_type_column}' value='{$record_type_name}'>";

      /* Hardcoding for India Birds - Trip Record */
      if(ereg("lnk_[0-9]+_india_birdsightings", $link_tablename) && $record_type_name == "Trip Record") {
        $hidden.="<input id='btnAddSpecies' type='button' value='Add species' onClick='javascript:clearSpeciesForAdd(\"divRecordType\")' disabled='true'><br>";
      }
    }
  }
  else {
    $record_types=array();
    $query="select id, record_type, record_columns from mlocate_data_record_types where tablename = '%s' and table_type = '" . TABLE_TYPE_LINK . "' order by record_type";
    $result=db_query($query, $link_tablename);
    $i=0;
    while($obj=db_fetch_object($result)) {
      $record_types[$i]['record_type_id']=$obj->id;
      $record_types[$i]['record_type']=$obj->record_type;
      $record_types[$i]['record_columns']=$obj->record_columns;
      $i++;
    }

    if(sizeof($record_types) == 0) {
      // Its simple data with no special record types.
      $link_info=GetTableColsOfType($link_tablename, TABLE_TYPE_LINK, 'mandatory');
      $mandatory_columns=$link_info['mandatory_columns'];

      $link_info=GetTableColsOfType($link_tablename, TABLE_TYPE_LINK, 'editable');
      $editable_columns=$link_info['editable_columns'];

      if($editable_columns == '') {
        die("No Editable Columns specified");
      }
      else {
        $query=GetQueryColumnDetails($link_tablename, $editable_columns, FALSE);
        $result_cols=db_query($query[0], $query[1]);

        if($row_id != NULL) {
          $query='select %s from "%s" where id = %d';
          $query_args=array(str_replace("'", '"', $editable_columns), $link_tablename, $row_id);
          $result_link_row=db_query($query, $query_args);
          $vals_link_row=db_fetch_array($result_link_row);
          $tab=GetDBTableSchemaTable($link_tablename, TABLE_TYPE_LINK, $result_cols, $vals_link_row);
        }
        else {
          $tab=GetDBTableSchemaTable($link_tablename, TABLE_TYPE_LINK, $result_cols);
        }
      }
    }
    else {
      // The data has record types.
      $lnk=$_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'] . "&record_type_id=";
      $options="<select onchange=\"javascript:getPopupAjaxDiv(jQuery('#divRecordType')[0], '$lnk'+this.value, maskInputs)\">";
      $options.="<option value='0'>Select Record Type</option>";
      foreach($record_types as $type) {
        $record_type_id=$type['record_type_id'];
        $record_type=$type['record_type'];
        $options.="<option value='{$record_type_id}'>$record_type</option>";
      }
      $options.="</select>";
      $html=$options . '<div id="divRecordType"></div>';
      die($html);
    }
  }

  $hidden.="<input type='hidden' name='link_tablename' value='{$link_tablename}'>";
  $hidden.="<input type='hidden' name='edit-{$linked_column}' value='{$linked_value}'>";
  $hidden.="<input type='hidden' name='edit-__id' id='edit-__id' value='$id'>";
  $html="<div id='form_mlocate_error' class='error'></div>";
  $html.=$tab;
  $resp="";
  $resp.="<form id='frmLinkSave' method='post' action='{$base_path}save_info.php?action=saveLinkData'>";
  $resp.=$html . $hidden;
  $resp.="<input id='btnSubmit' type='button' value='Submit' onClick='javascript:submitForm(\"#frmLinkSave\",\"#form_mlocate_error\")'>";
  $resp.="</form>";
  echo $resp;
}

function get_resource_table_entry($resource_tablename, $resource_column, $value) {
  $query="select * from \"%s\" where \"%s\" = '%s'";
  $query_args=array($resource_tablename, $resource_column, $value);
  $result=db_query($query, $query_args);

  if(!$result) {
    //Error occured
    die(return_error('Error fetching resource info'));
  }
  else {
    $col_info=getDBColDesc($resource_tablename);

    $table_info=GetTableColsOfType($resource_tablename, 'resource', - 1, 'italics');
    $italics_columns=$table_info['italics_columns'];

    $i=0;
    $summary="";

    if($obj=db_fetch_object($result)) {
      foreach($obj as $key=>$val) {
        if($key != AUTO_DBCOL_PREFIX . 'id') {
          $value=$val;
          if(strpos($italics_columns, "'{$key}'") !== FALSE) {
            $value=($val == '' ? '&nbsp;' : '<i>' . str_replace(" ", "&nbsp;", $val) . '</i>');
          }
          if($key == AUTO_DBCOL_PREFIX . 'created_by' || $key == AUTO_DBCOL_PREFIX . 'modified_by') {
            $value=getUserLink($val);
          }
          $summary.=getInfoEntry(($col_info[$key] == "" ? $key : $col_info[$key]), $value);
        }
      }
    }
    else {
      $summary="<tr><td><b>No info found.</b></td></tr>";
    }
    $html=<<< EOF

<div id="resource">

  <div class="summary">

    <table cellspacing="0">

      <tbody>{$summary}

      </tbody>

    </table>

  </div>

</div>
EOF;
    echo $html;
  }
}

function get_layer_metadata($layer_tablename, $hasmenu) {

  /* ----- action retireves layer data to show on popup when info icon is clicked ------ */
  $resourcetables_referrenced=array();
  $html="";
  $html.="<html><head>";

  $html.=<<<EOF

        <script type="javascript">

      jQuery(document).ready(function(){

        jQuery(".descriptionControl").click(function(){

          jQuery(".descriptionShowHide").toggle();

          jQuery("#layerDescription").toggle();

          return false;

        });


        jQuery(".linkedDataControl").click(function(){

          jQuery(".linkedDataShowHide").toggle();

          jQuery("#linkedData").toggle();

          return false;

        });


        jQuery(".resourceTablesControl").click(function(){

          jQuery(".resourceTablesShowHide").toggle();

          jQuery("#resourceTables").toggle();

          return false;

        });


      });

    </script>
EOF;

  $html.="</head><body>";
  $cols="layer_name,attribution,license,nid,p_nid,layer_description,layer_type,status,pdf_link,url,comments,participation_type,created_by,created_date,modified_by,modified_date";
  $query="select %s from \"Meta_Layer\" where layer_tablename='%s'";
  $query_args=array($cols, $layer_tablename);
  $result_metadata=db_query($query, $query_args);
  if(!$result_metadata) {
    //Error occured
    die(return_error('Error fetching layer data'));
  }
  else {
    $meta_col_info=getDBColDesc('Meta_Layer');

    $layer_nid=0;
    $participation_nid=0;
    $layer_meta_info="";
    if($metadata_obj=db_fetch_object($result_metadata)) {
      foreach($metadata_obj as $key=>$value) {
        switch($key) {
          case 'layer_name':
            $layer_name=$value;
            break;

          case 'attribution':
            $fullattribution=$value;
            $attribution=$fullattribution;
            break;

          case 'license':
            $layer_license=$value;
            $license=getCCLicenseHTML($layer_license, 'large');
            break;

          case 'nid':
            $layer_nid=$value;
            break;

          case 'p_nid':
            $participation_nid=$value;
            break;

          case 'layer_description':
            $layer_meta_info.=getInfoEntry('Description', $value);
            break;

          case 'status':
            $query="select description from layer_status where id = %d";
            $result=db_query($query, $value);
            if(!$result) {
            }
            else {
              if($obj=db_fetch_object($result)) {
                $layer_meta_info.=getInfoEntry('Status', $obj->description);
              }
            }
            break;

          case 'participation_type':
            $query="select name from mlocate_participation_type where id = %d";
            $result=db_query($query, $value);
            if(!$result) {
            }
            else {
              if($obj=db_fetch_object($result)) {
                $layer_meta_info.=getInfoEntry('Participation Type', $obj->name);
              }
            }
            break;

          case 'pdf_link':
          case 'url':
            $lnk="";
            if($value != "") {
              $lnk="<a href='{$value}' target='_blank'>{$value}</a>";
            }
            $layer_meta_info.=getInfoEntry(($meta_col_info[$key] == "" ? $key : str_replace(" ", "&nbsp;", $meta_col_info[$key])), $lnk);
            break;

          case 'comments':
            $layer_meta_info.=getInfoEntry(($meta_col_info[$key] == "" ? $key : str_replace(" ", "&nbsp;", $meta_col_info[$key])), $value);
            break;

          case 'created_by':
            $layer_meta_info.=getInfoEntry(($meta_col_info[$key] == "" ? $key : str_replace(" ", "&nbsp;", $meta_col_info[$key])), $value);
            break;

          case 'created_date':
            $layer_meta_info.=getInfoEntry(($meta_col_info[$key] == "" ? $key : str_replace(" ", "&nbsp;", $meta_col_info[$key])), $value);
            break;

          case 'modified_by':
            $layer_meta_info.=getInfoEntry(($meta_col_info[$key] == "" ? $key : str_replace(" ", "&nbsp;", $meta_col_info[$key])), $value);
            break;

          case 'modified_date':
            $layer_meta_info.=getInfoEntry(($meta_col_info[$key] == "" ? $key : str_replace(" ", "&nbsp;", $meta_col_info[$key])), $value);
            break;

          case 'is_filterable':
            $layer_meta_info.=getInfoEntry(($meta_col_info[$key] == "" ? $key : str_replace(" ", "&nbsp;", $meta_col_info[$key])), ($value == 0 ? 'No' : 'Yes'));
            break;

          case 'layer_id':
            break;

          case 'layer_tablename':
            break;

          default:
            $layer_meta_info.=getInfoEntry(($meta_col_info[$key] == "" ? $key : str_replace(" ", "&nbsp;", $meta_col_info[$key])), $value);
            break;
        }
      }
    }
  }

  $global_resources=getResourceTableNamesForTables(array($layer_tablename));
  if(sizeof($global_resources) > 0) {
    $layer_meta_info.=getInfoEntry('Resource Tables', implode(", ", $global_resources));
    $resourcetables_referrenced=array_merge($resourcetables_referrenced, $global_resources);
  }

  $skip_cols=array();
  $layer_attr=getTableAttribForLayerInfo($layer_tablename, $skip_cols);

  $theme_name="";
  $geo_name="";
  $query='select theme_name, theme_type from "Theme" where theme_id in (select theme_id from "Theme_Layer_Mapping" where layer_id = (SELECT layer_id  FROM "Meta_Layer"  where layer_tablename = \'%s\'))';
  $result=db_query($query, $layer_tablename);
  if(!$result) {
    //Error occured
    die(return_error('Error fetching theme info for layer.'));
  }
  else {
    while($obj=db_fetch_array($result)) {
      if($obj['theme_type'] == 1) {
        $theme_name=$obj['theme_name'];
      }
      elseif($obj['theme_type'] == 2) {
        $geo_name=$obj['theme_name'];
      }
    }
  }

  $table_info_html=<<<EOF
        <li>{%table_name}</li>
  <div class="{%table_type}_table_info">
    <table cellspacing="0">
      <tbody>
        {%table_info}
      </tbody>
    </table>
  </div>
  <b style="margin:20px;"><u>Attributes:</u></b>
  <ul class="nested">
    {%table_attr}
  </ul>
  <hr>
EOF;

  $haslinktables="none";
  $link_tables_info="";
  $links=getLinkTableNames($layer_tablename);
  if(sizeof($links) > 0) {
    $haslinktables="block";
    foreach($links as $link) {
      $link_name=$link['name'];
      $link_tablename=$link['tablename'];
      if($link_name == "") {
        $link_name=$link_tablename;
      }

      $link_table_info="";
      $cols="created_by, created_date, modified_by, modified_date";
      $query="select %s from \"Meta_LinkTable\" where link_tablename = '%s'";
      $query_args=array($cols, $link_tablename);
      $result=db_query($query, $query_args);
      if(!$result) {
        //Error occured
        die(return_error('Error fetching linked info for layer.'));
      }
      else {
        if($obj=db_fetch_array($result)) {
          $link_col_info=getDBColDesc('Meta_LinkTable');
          foreach($obj as $key=>$value) {
            switch($key) {
              case 'id':
              case 'layer_id':
              case 'link_tablename':
              case 'link_name':
                break;

              case 'status':
                $query="select description from layer_status where id = %d";
                $result=db_query($query, $value);
                if(!$result) {
                }
                else {
                  if($obj=db_fetch_object($result)) {
                    $link_table_info.=getInfoEntry('Status', $obj->description);
                  }
                }
                break;

              case 'is_filterable':
                $link_table_info.=getInfoEntry(($link_col_info[$key] == "" ? $key : str_replace(" ", "&nbsp;", $link_col_info[$key])), ($value == 0 ? 'No' : 'Yes'));
                break;

              default:
                $link_table_info.=getInfoEntry(($link_col_info[$key] == "" ? $key : str_replace(" ", "&nbsp;", $link_col_info[$key])), $value);
                break;
            }
          }
        }
      }

      $skip_cols=array();
      $link_table_attr=getTableAttribForLayerInfo($link_tablename, $skip_cols);

      $global_resources=getResourceTableNamesForTables(array($link_tablename));
      if(sizeof($global_resources) > 0) {
        $resourceTables=implode(", ", $global_resources);
        $link_table_info.=getInfoEntry('Resource Tables', $resourceTables);
        $resourcetables_referrenced=array_merge($resourcetables_referrenced, $global_resources);
      }

      $link_info=$table_info_html;
      $link_info=str_replace("{%table_name}", $link_name, $link_info);
      $link_info=str_replace("{%table_type}", 'link', $link_info);
      $link_info=str_replace("{%table_attr}", $link_table_attr, $link_info);
      $link_info=str_replace("{%table_info}", $link_table_info, $link_info);

      $link_tables_info.=$link_info;
    }
  }

  $hasresourcetables='none';
  $resource_tables_info='';
  if(sizeof($resourcetables_referrenced) > 0) {
    $hasresourcetables='block';
    $resourcetables_referrenced=array_unique($resourcetables_referrenced);

    foreach($resourcetables_referrenced as $resourcetable) {
      $resource_table_info="";
      $resource_table_attr=getTableAttribForLayerInfo($resourcetable, $skip_cols);

      $resource_info=$table_info_html;
      $resource_info=str_replace("{%table_name}", $resourcetable, $resource_info);
      $resource_info=str_replace("{%table_type}", 'resource', $resource_info);
      $resource_info=str_replace("{%table_attr}", $resource_table_attr, $resource_info);
      $resource_info=str_replace("{%table_info}", $resource_table_info, $resource_info);

      $resource_tables_info.=$resource_info;
    }
  }

  $editMetadataUI='';
  if(isUserAuthorizedToEditMetadata($layer_tablename, TABLE_TYPE_LAYER)) {
    $editMetadataUI="<div id=\"editMetadata\"><a href=\"#\" onclick=\"showModalPopup('<iframe width=100% height=100% src={$base_path}ml_metadata/ml_metadata.html?layer_tablename={$layer_tablename}></iframe>', 'Edit metadata {$layer_name}', reloadParentTab, new Array('ulLayerPopupUIMenu'));\">Edit Metadata</a></div>";
  }

  if(!$hasmenu) {
    $user_role=getUserRoleForLayer($layer_tablename);

    $lnk=$_SERVER['REQUEST_URI'] . "&hasmenu=1";
    $id="layerInfoSummary";
    $menu='<li id="' . $id . '" class="first active"><a href="javascript:popupTabClicked(\'layerinfo\',\'ulLayerPopupUIMenu\',\'' . $id . '\',\'' . $lnk . '\')">Summary</a></li>';

    if($layer_nid) {
      $lnk="{$base_path}node/{$layer_nid}/popup";
      $id="DrupalNode";
      $menu.='<li id="' . $id . '"><a href="javascript:popupTabClicked(\'layerinfo\', \'ulLayerPopupUIMenu\', \'' . $id . '\',\'' . $lnk . '\')">Details</a></li>';
      if(node_access('update', node_load($layer_nid))) {
        $lnk="{$base_path}node/{$layer_nid}/edit/popup";
        $id="drupalEditDetails";
        $menu.='<li id="' . $id . '"><a href="javascript:popupTabClicked(\'layerinfo\', \'ulLayerPopupUIMenu\', \'' . $id . '\',\'' . $lnk . '\')">Edit Details</a></li>';
      }
    }
    else {
      switch($user_role) {
        case 'admin':
        case 'member':
        case 'validator':
          $lnk="{$base_path}node/add/node-mlocate-layerinfo/popup?layer_tablename={$layer_tablename}";
          $id="drupalAddMoreDetails";
          $menu.='<li id="' . $id . '"><a href="javascript:popupTabClicked(\'layerinfo\', \'ulLayerPopupUIMenu\', \'' . $id . '\',\'' . $lnk . '\')">Add More Details</a></li>';
          break;
      }
    }

    if($participation_nid) {
      $lnk="{$base_path}node/{$participation_nid}/popup";
      $id="DrupalNodeParticipation";
      $menu.='<li id="' . $id . '"><a href="javascript:popupTabClicked(\'layerinfo\', \'ulLayerPopupUIMenu\', \'' . $id . '\',\'' . $lnk . '\')">Participation</a></li>';
      switch($user_role) {
        case 'admin':
          $lnk="{$base_path}node/{$participation_nid}/edit/popup";
          $id="drupalEditParticipationInfo";
          $menu.='<li id="' . $id . '"><a href="javascript:popupTabClicked(\'layerinfo\', \'ulLayerPopupUIMenu\', \'' . $id . '\',\'' . $lnk . '\')">Edit Participation</a></li>';
          break;
      }
    }
    else {
      switch($user_role) {
        case 'admin':
          $lnk="{$base_path}node/add/node-mlocate-participation/popup?layer_tablename={$layer_tablename}";
          $id="drupalAddParticipationInfo";
          $menu.='<li id="' . $id . '"><a href="javascript:popupTabClicked(\'layerinfo\', \'ulLayerPopupUIMenu\', \'' . $id . '\',\'' . $lnk . '\')">Add Participation</a></li>';
          break;
      }
    }

    if($user->uid) {
      $user_roles=$user->roles;

      // site admin and layer admin have permissions to edit layer permissions
      if(in_array(SITE_ADMIN_ROLE, $user_roles) || in_array($layer_tablename . ' admin', $user_roles)) {
        $divID='layerinfo';
        $ulID='ulLayerPopupUIMenu';
        $liID="editPermissions";
        $lnk="{$base_path}ml_orchestrator.php?action=editLayerPermissions&layer_tablename={$layer_tablename}";
        $title="Edit Permissions";
        $menu.=getMenuLI($liID, $divID, $ulID, $lnk, $title);
      }
    }

    include("popupui.inc");
    $html.=$layerInfoHTML;
  }
  else {
    include("popupui.inc");
    $html.=$layerInfoinnerHTML;
  }
  $html.="</body></html>";
  echo $html;
}

function edit_layer_permissions($layer_tablename, $for_role) {
  $arr_result=array();

  $user=$GLOBALS['user'];
  $user_roles=$user->roles;

  $arr_roles=array();
  $arr_available_perms=array();

  // site admin and layer admin have permissions to edit layer permissions
  if(in_array(SITE_ADMIN_ROLE, $user_roles) || in_array($layer_tablename . ' admin', $user_roles)) {
    if($for_role != NULL) {
      if($for_role == "0") {
        echo "";
      }
      else {
        $mlocate_available_perms=array();
        $query="select perm from mlocate_available_permissions";
        $result=db_query($query);
        if(!$result) {
          die("Error fetching data. Please try after some time or contact the admin.");
          $arr_result['error']="Error fetching data. Please try after some time or contact the admin.";
        }
        else {
          while($obj=db_fetch_object($result)) {
            $mlocate_available_perms[]=$obj->perm;
          }
        }

        $layer_perms=getRoleMLOCATEPerms($for_role);

        foreach($mlocate_available_perms as $mlocate_available_perm) {
          $checked="";
          if(in_array($mlocate_available_perm, $layer_perms)) {
            $checked="checked";
          }
          $arr_roles[$mlocate_available_perm]=$checked;
        }
        $arr_result['roles']=$arr_roles;
        $arr_result['for_role']=$for_role;
      }
    }
    else {
      $roles=array();

      $query="select name from role where name like '%s %'";
      $result=db_query($query, $layer_tablename);
      if(!$result) {
        $arr_result['error']="Error fetching data. Please try after some time or contact the admin.";
      }
      else {
        $lnk="{$base_path}ml_orchestrator.php?action=editLayerPermissions&layer_tablename={$layer_tablename}&for_role=";
        $arr_result['lnk']=$lnk;
        while($obj=db_fetch_object($result)) {
          $arr_roles['key_' . $obj->name]=$obj->name;
        }
        $arr_result['roles']=$arr_roles;
      }
    }
  }
  else {
    $arr_result['error']="You do not have permissions for this operation";
  }
  print json_encode($arr_result);
}

function edit_meta_layer($layer_tablename) {
  $query='select * from "Meta_Layer" where layer_tablename = \'%s\'';
  $result=db_query($query, $layer_tablename);
  if(!$result) {
    echo "Error fetching data. Please try after some time or contact the admin.";
  }
  else {
    $obj=db_fetch_array($result);

    $html="<form id='frmMetaLayer' method='post' action='{$base_path}save_info.php?action=saveMetaLayer'>";

    $html.="<div id='form_mlocate_error' class='error'></div>";

    $col_info=getDBColDesc("Meta_Layer");
    $table="<table id='tab_schema' border=1 cellspacing=0 style='font-size:12px;'>";
    $table.="<tr><th width=\"200px\">Key</th><th width=\"400px\">Value</th></tr>";
    foreach($obj as $key=>$value) {
      switch($key) {
        case 'layer_id':
        case 'nid':
        case 'p_nid':
        case 'layer_tablename':
        case 'layer_type':
          break;

        default:
          $table.="<tr>";
          $table.="<td>";
          if(isset($col_info[$key]) && $col_info[$key] != '') {
            $table.=$col_info[$key];
          }
          else {
            $table.=$key;
          }
          $table.="</td>";
          $table.="<td>";
          $table.='<input type="text" id="edit-' . $key . '" name="edit-' . $key . '" value="' . $value . '" style="width: 400px">';
          $table.="</td>";
          $table.="</tr>";
          break;
      }
    }
    $table.="</table>";
    $html.=$table;

    $html.="<input type='hidden' name='layer_tablename' value='{$layer_tablename}'>";
    $html.="<input type='button' value='Submit' onClick='javascript:submitForm(\"#frmMetaLayer\",\"#form_mlocate_error\")'>";
    $html.="</form>";

    $js="<script language='javascript' src='{$base_path}misc/jquery.js'></script>";
    $js.="<script language='javascript' src='{$base_path}misc/jquery.form.js'></script>";
    $js.="<script language='javascript' src='{$base_path}" . drupal_get_path('module', 'map') . "/functions.js'></script>";
    echo $html . $js;
  }
}

function clear_popup_ui_menu() {
  unset($_SESSION['popup_layer_tablename']);
  unset($_SESSION['popup_row_id']);
  unset($_SESSION['popup_menu']);
}

function get_media($layer_tablename, $row_id, $media_column) {

  $query='SELECT "%s" FROM "%s" WHERE ' . AUTO_DBCOL_PREFIX . 'id = %d';
  $result=db_query($query, $media_column, $layer_tablename, $row_id);
  if(!$result) {
  }
  else {
    while($obj=db_fetch_array($result)) {
      $img=$obj[$media_column];
      if($img != "") {
        $src=base_path() . file_directory_path() . '/' . $layer_tablename . '/' . $img;
        echo '<p><img src="' . $src . '" alt="' . $media_column . '" style="border:1px solid"/></p>';
      }
      else {
        echo "<b> No media found.</b>";
      }
    }
  }
}

function validate_feature($layer_tablename, $row_id) {
  $result=array();
  $result['validate']=validateFeature($layer_tablename, $row_id);
  print json_encode($result);
}

function get_participatory_layers() {
  $html="";
  $user=$GLOBALS['user'];
  if($user->uid) {
    $lyrs=array();
    $sql="select layer_name, layer_tablename from \"Meta_Layer\" where participation_type in (2,3) order by layer_name";
    $result=db_query($sql);
    if(!$result) {
    }
    else {
      while($obj=db_fetch_object($result)) {
        $lyrs[$obj->layer_tablename]=$obj->layer_name;
      }
    }

    $user_roles=$user->roles;
    $lyrs1=array();
    foreach($user_roles as $role) {
      if(substr($role, - 6) == ' admin') {
        $lyrs1[]=substr($role, 0, - 6);
      }
      elseif(substr($role, - 10) == ' validator') {
        $lyrs1[]=substr($role, 0, - 10);
      }
      elseif(substr($role, - 7) == ' member') {
        $lyrs1[]=substr($role, 0, - 7);
      }
    }

    array_walk($lyrs1, "singleQuoteString");
    $str_lyrs=implode(",", $lyrs1);

    $sql='select layer_tablename, layer_name  from "Meta_Layer" where layer_tablename in (%s) and status = 1 order by layer_name;';
    $result=db_query($sql, $str_lyrs);
    if(!$result) {
    }
    else {
      while($obj=db_fetch_object($result)) {
        $lyrs[$obj->layer_tablename]=$obj->layer_name;
      }
    }

    asort($lyrs);

    print json_encode($lyrs);
  }
}

function get_download_url($layer_tablename, $format) {
  $arr_url=array();
  $user = $GLOBALS['user'];
  if($user->uid) {
    $cmd='';
    $query="select layer_id,layer_name,access,layer_type from \"Meta_Layer\" where layer_tablename = '%s'";
    $result=db_query($query, $layer_tablename);
    if(!$result) {
    }
    else {
      while($obj=db_fetch_object($result)) {
        $layer_id=$obj->layer_id;
        $layer_name=$obj->layer_name;
        $isdownloadable=$obj->access;
        $layer_type=$obj->layer_type;
      }
      // 1 => downloadable
      if($isdownloadable) {
        $file_path=$_SERVER['SCRIPT_FILENAME'];
        $pos1=strrpos($file_path, '/');
        $file_path=substr($file_path, 0, $pos1) . "/shapefiles/";
        $file=$user->uid . "_" . $layer_name;
        $file=str_replace(' ', '_', $file) . "_" . date(Ymd);
        $dir_name=str_replace(' ', '_', $layer_name);

        $dbuser=preg_replace('/pgsql:\/\/([^:@][^:@]*).*/', '$1', $db_url);
        $dbname=substr(strrchr($db_url, '/'), 1);

        $shp_cols='';

        /* remove if zip file and dir exists already and create a new one */
        if(file_exists($file_path . $file . ".zip")) {
          $cmd="rm -f " . $file_path . $file . ".zip;";
        }
        if(file_exists($file_path . $dir_name)) {
          $cmd.="cd " . $file_path . ";rm -r " . $dir_name . ";mkdir " . $dir_name . ";";
        }
        else {
          $cmd.="cd " . $file_path . ";mkdir " . $dir_name . ";";
        }

        $query="select column_name from information_schema.columns where table_name='%s' AND column_name not like '" . AUTO_DBCOL_PREFIX . "%'";
        $result=db_query($query, $layer_tablename);
        if(!$result) {
        }
        else {
          while($obj=db_fetch_object($result)) {
            $shp_cols.=$obj->column_name . ",";
          }
        }

        if($format != 'TXT') {
          $shp_cols.=AUTO_DBCOL_PREFIX . "topology";
          $cols=split(",", $shp_cols);
          $shp_query="select " . $shp_cols . " from " . $layer_tablename;
          $cmd.=" pgsql2shp -u " . $dbuser . " -f " . $file_path . $dir_name . "/" . $file . ".shp " . $dbname . " '" . $shp_query . "';";
          if($format != 'SHAPE') {
            $cmd.=" ogr2ogr -f '" . $format . "' " . $dir_name . "/" . $file . "." . strtolower($format) . " " . $dir_name . "/" . $file . ".shp;rm " . $dir_name . "/" . $file . ".sh*;rm " . $dir_name . "/" . $file . ".dbf;";
          }
        }
        else {
          exec($cmd, $output);
          $fh=fopen($file_path . $dir_name . "/" . $file . ".txt", 'w+');
          if($layer_type != 'POINT') {
            $shp_cols.="X(centroid(" . AUTO_DBCOL_PREFIX . "topology)),Y(centroid(" . AUTO_DBCOL_PREFIX . "topology))";
          }
          else {
            $shp_cols.="X(" . AUTO_DBCOL_PREFIX . "topology),Y(" . AUTO_DBCOL_PREFIX . "topology)";
          }
          $cols=split(",", $shp_cols);
          for($i=0; $i < count($cols); $i++) {
            if(($cols[$i] == 'X(' . AUTO_DBCOL_PREFIX . 'topology)') || ($cols[$i] == 'X(centroid(' . AUTO_DBCOL_PREFIX . 'topology))')) {
              //if it is getX col
              fwrite($fh, "X\t");
            }
            elseif(($cols[$i] == 'Y(' . AUTO_DBCOL_PREFIX . 'topology)') || ($cols[$i] == 'Y(centroid(' . AUTO_DBCOL_PREFIX . 'topology))')) {
              //if it is getY col
              fwrite($fh, "Y\t");
            }
            else {
              fwrite($fh, $cols[$i] . "\t");
            }
          }
          fwrite($fh, "\n");
          $query="select $shp_cols from $layer_tablename";
          $result=db_query($query);
          if(!$result) {
          }
          else {
            while($obj=db_fetch_object($result)) {
              for($i=0; $i < count($cols); $i++) {
                if(($cols[$i] == 'X(' . AUTO_DBCOL_PREFIX . 'topology)') || ($cols[$i] == 'X(centroid(' . AUTO_DBCOL_PREFIX . 'topology))')) {
                  fwrite($fh, $obj->x . "\t");
                }
                elseif(($cols[$i] == 'Y(' . AUTO_DBCOL_PREFIX . 'topology)') || ($cols[$i] == 'Y(centroid(' . AUTO_DBCOL_PREFIX . 'topology))')) {
                  fwrite($fh, $obj->y . "\t");
                }
                else {
                  fwrite($fh, $obj->$cols[$i] . "\t");
                }
              }
              fwrite($fh, "\n");
            }
            $cmd="cd " . $file_path;
          }
        }
        exec($cmd, $output);

        /* create a read me file with meta data */
        $readme_file=fopen($file_path . $dir_name . "/REAMDE.txt", 'w+');
        fwrite($readme_file, "Indemnity of MLOCATE:\n");
        fwrite($readme_file, "1.The data is provided as available on the portal.\n");
        fwrite($readme_file, "2.MLOCATE strive to provide accuracy of locations and data as far as possible but cannot be held responsible for the accuracy and validity of the data.\n");
        fwrite($readme_file, "3.Any use of the data should be done with proper attribution of the data as provided above.\n");

        // meta data

        $cols="layer_name,layer_tablename,layer_description,created_by,created_date,modified_by,modified_date,min_scale,max_scale,pdf_link,url,aggregation,attribution,license,lineage,tags,comments,layer_type,participation_type";
        $cols_arr=split(',', $cols);
        $cols_arr_len=count($cols_arr);
        $query="select %s from \"Meta_Layer\" where layer_tablename='%s'";
        $query_args=array($cols, $layer_tablename);
        $result_metadata=db_query($query, $query_args);
        if(!$result_metadata) {
          //Error occured
          die(return_error('Error fetching layer data'));
        }
        else {
          $col_info=getDBColDesc("Meta_Layer");
          while($obj=db_fetch_object($result_metadata)) {
            for($j=0; $j < $cols_arr_len; $j++) {
              if($cols_arr[$j] == 'participation_type') {
                $query="select name from mlocate_participation_type where id = %d";
                $p_result=db_query($query, $obj->$cols_arr[$j]);
                while($participation_obj=db_fetch_object($p_result)) {
                  $participation_type=$participation_obj->name;
                  fwrite($readme_file, $cols_arr[$j] . "\t" . $participation_type . "\n");
                }
              }
              else {
                fwrite($readme_file, $col_info[$cols_arr[$j]] . "\t" . $obj->$cols_arr[$j] . "\n");
              }
            }
          }
        }

        /* Theme information */
        $theme_query="select theme_name,theme_type from \"Theme\" where theme_id in (select theme_id from \"Theme_Layer_Mapping\" where layer_id = %d )";
        $result_theme=db_query($theme_query, $layer_id);
        if(!$result_theme) {
          //Error occured
          die(return_error('Error fetching theme'));
        }
        else {
          while($theme_obj=db_fetch_object($result_theme)) {
            if($theme_obj->theme_type == 1)
              fwrite($readme_file, "Theme\t" . $theme_obj->theme_name . "\n");
            if($theme_obj->theme_type == 2)
              fwrite($readme_file, "Geography\t" . $theme_obj->theme_name . "\n");
          }
        }

        //link table generation
        $query="select link_tablename from \"Meta_LinkTable\" where layer_id = " . $layer_id;
        $result=db_query($query);
        if(!$result) {
        }
        else {
          $i=0;
          while($obj=db_fetch_object($result)) {
            $link_tablename[$i]=$obj->link_tablename;
            $i++;
          }
        }
        $length=count($link_tablename);
        if($length > 0) {
          for($i=0; $i < $length; $i++) {
            $query="select column_name from information_schema.columns where table_name='%s' AND column_name not like '" . AUTO_DBCOL_PREFIX . "%'";
            $result=db_query($query, $link_tablename[$i]);
            if(!$result) {
            }
            else {
              while($obj=db_fetch_object($result)) {
                $link_cols.=$obj->column_name . ",";
              }
            }
            //write to a file dir_name contains layer name without spaces
            $link_cols=substr($link_cols, 0, strlen($link_cols) - 1);
            $link_name=preg_replace('/lnk_[0-9]*_(.*)/', '$1', $link_tablename[$i]);
            $link_file=$file_path . $dir_name . "/" . $user->uid . "_" . $link_name . ".txt";
            $link_fh=fopen($link_file, 'w+');
            $link_cols_arr=split(',', $link_cols);
            $link_cols_arr_len=count($link_cols_arr);

            /* -- write link table names to readme file -- */
            fwrite($readme_file, "\n Associated linktables: \n");
            fwrite($readme_file, $link_name . "\n");

            /* get linked and layer columns */
            $query="select description, linked_column, layer_column from \"Meta_LinkTable\" where link_tablename = '%s'";
            $link_result=db_query($query, $link_tablename[$i]);
            if(!$link_result) {
              //Error occured
              die(return_error('Error fetching data from DB'));
            }
            else {
              $link_obj=db_fetch_object($link_result);
              fwrite($readme_file, "Description\t" . $link_obj->description . "\n");
              fwrite($readme_file, "Linked Column\t" . $link_obj->linked_column . "\n");
              fwrite($readme_file, "Layer Column\t" . $link_obj->layer_column . "\n");
            }

            for($j=0; $j < $link_cols_arr_len; $j++) {
              fwrite($link_fh, $link_cols_arr[$j] . "\t");
            }
            fwrite($link_fh, "\n");
            $query="select " . $link_cols . " from \"" . $link_tablename[$i] . "\"";
            $result=db_query($query);
            if(!$result) {
            }
            else {
              while($obj=db_fetch_object($result)) {
                for($j=0; $j < $link_cols_arr_len; $j++) {
                  fwrite($link_fh, $obj->$link_cols_arr[$j] . "\t");
                }
                fwrite($link_fh, "\n");
              }
            }
            fclose($link_fh);
          }
        }
        fclose($readme_file);
        //finally create zip file
        $cmd="cd " . $file_path . ";zip -r " . $file . " " . $dir_name;
        exec($cmd, $output);

        //return file name to download file
        $base_path=$GLOBALS['base_path'];
        $arr_url['url']=$base_path . "download.php?file=" . $file . ".zip&layer_name=" . $layer_name;
        print json_encode($arr_url);
      }
      else {
        $arr_url['error']="Error:Layer is not downloable";
        print json_encode($arr_url);
      }
    }
  }
  else {
    //redirect to log in page
    $arr_url['error']="Error:You must log in to download the layer";
    print json_encode($arr_url);
  }
}

function get_download_formats($layer_tablename) {
  $download_formats=array();
  $user = $GLOBALS['user'];
  if($user->uid) {
    $query="select layer_type, download_formats,access from \"Meta_Layer\" where layer_tablename = '%s'";
    $r_result=db_query($query, $layer_tablename);
    if(!$r_result) {
      $download_formats['error']="Error: Could not connect to DB";
      print json_encode($download_formats);
    }
    else {
      if($r_obj=db_fetch_object($r_result)) {
        $layer_type=$r_obj->layer_type;
        $formats_csv=$r_obj->download_formats;
        $isdownloadable=$r_obj->access;
      }
      // Quick hack as RASTER download is not supported currently
      if($layer_type == 'RASTER') {
        $download_formats['error']="Error: RASTER layers are not currently supported";
        print json_encode($download_formats);
      }
      elseif($isdownloadable) {
        $download_formats['format']=$formats_csv;
        print json_encode($download_formats);
      }
      else {
        $download_formats['error']="Error:Layer cannot be downloaded";
        print json_encode($download_formats);
      }
    }
  }
  else {
    //redirect to log in page
    $download_formats['error']="Error:You must log in to download the layer";
    print json_encode($download_formats);
  }
}

function get_layer_summary_columns($layer_tablename) {
  $table_type="layer";
  $col_type="summary";

  // Get layer summary columns. If layer_id is passed get layer_tablename, else, other way round.
  return GetTableColsOfType($layer_tablename, $table_type, $col_type, $layer_id);
}

function get_layer_metainfo_for_popup($layer_tablename, $layer_id=NULL) {
  $columns_arr=array();
  $columns_arr[]='layer_name';
  $columns_arr[]='title_column';
  $columns_arr[]='license';
  $columns_arr[]='attribution';

  $metainfo=get_values_metatable($layer_tablename, $layer_id, TABLE_TYPE_LAYER, $columns_arr);

  $layer_name=$metainfo['layer_name'];
  $layer_title_column=str_replace("'", "", $metainfo['title_column']);
  $layer_license=$metainfo['license'];
  $layer_attribution=$metainfo['attribution'];

  return array($layer_name, $layer_title_column, $layer_license, $layer_attribution);
}

function get_license_info_for_popup($layer_license) {
  $license=getCCLicenseHTMLForSummary($layer_license);
  if($layer_license == NULL) {
    $layer_license='';
  }
  if($license != $layer_license) {
    $arr_license['license']=$license[0];
    $arr_license['img_size']=$license[1];
  }
  else {
    $arr_license['license']=$license;
  }

  return $arr_license;
}

function GetDBTableSchemaTable($tablename, $table_type, $result_cols, $vals_row=null) {
  $resource_tables=array();
  if($table_type == TABLE_TYPE_LINK || $table_type == TABLE_TYPE_LAYER) {
    $resource_tables=getResourceTableMapping($tablename);
  }

  $html="";
  $html.="<table id='tab_schema' cellspacing=0>";

  $participation_type=0;
  if($table_type == TABLE_TYPE_LAYER) {
    $query="select participation_type from \"Meta_Layer\" where layer_tablename = '%s'";
    $result=db_query($query, $tablename);
    if(!$result) {
    }
    else {
      if($obj=db_fetch_object($result)) {
        $participation_type=$obj->participation_type;
      }
    }
  }

  while($col_obj=db_fetch_object($result_cols)) {
    $resource_table=array();
    if(isset($resource_tables[$col_obj->column_name])) {
      $resource_table=$resource_tables[$col_obj->column_name];
    }
    $col_name=($col_obj->column_description == "") ? ($col_obj->column_name) : ($col_obj->column_description);

    if(substr($col_obj->column_type, 0, 17) == "character varying") {
      $col_type="character varying";
      $col_length=substr($col_obj->column_type, 18, - 1);
    }
    elseif(substr($col_obj->column_type, 0, 8) == "numeric(") {
      $col_type="numeric";
      $col_length=substr($col_obj->column_type, 8, - 1);
    }
    else {
      $col_type=$col_obj->column_type;
      $col_length=$col_obj->column_length;
    }

    $col_null=(($col_obj->column_null == "") ? 0 : 1);
    $col_default=$col_obj->column_default;

    $val="";
    if(isset($vals_row)) {
      $val=$vals_row[$col_obj->column_name];
    }

    $html.="<tr>";
    $html.="<td>" . str_replace(" ", "&nbsp;", $col_name) . "</td>";

    /* Hardcoded value of column */
    if($participation_type == 3 && $col_obj->column_name == 're_layer') {
      $layers_checked=$_COOKIE['layersChecked'];
      $arr_layers_checked=explode(":", $layers_checked);
      array_walk($arr_layers_checked, "singleQuoteString");
      $layers_checked=implode(",", $arr_layers_checked);

      $query="select layer_name, layer_tablename, layer_type from \"Meta_Layer\" where layer_tablename in (%s) and layer_type = (select layer_type from \"Meta_Layer\" where layer_tablename = '%s')";
      $query_args=array($layers_checked, $tablename);
      $r_result=db_query($query, $query_args);
      if(!$r_result) {
      }
      else {
        $select='<select id="edit-' . $col_obj->column_name . '" name="edit-' . $col_obj->column_name . '" >';
        while($r_obj=db_fetch_object($r_result)) {
          $select.='<option value="' . $r_obj->layer_name . '">' . $r_obj->layer_name . '</option>';
        }
        $select.='</select>';
      }

      $html.='<td>' . $select . '</td>';
    }
    elseif(isset($resource_table) && sizeof($resource_table) > 0) {
      $resource_tablename=$resource_table['resource_tablename'];
      $resource_info=GetTableColsOfType($resource_tablename, 'RESOURCE', 'displayed');
      $r_disp_cols=str_replace("'", "", $resource_info['displayed_columns']);
      $arr_dcols=explode(",", $r_disp_cols);
      $disp_cols="(";
      $disp_cols.=$arr_dcols[0];
      if(sizeof($arr_dcols) > 1) {
        $disp_cols.=" || ' (' || " . implode(" || ', ' || ", array_slice($arr_dcols, 1)) . " || ')'";
      }
      $disp_cols.=")";

      $query='select %s as id, ' . $disp_cols . ' as disp_col from "%s" order by %s;';
      $query_args=array($resource_table['resource_column'], $resource_tablename, $arr_dcols[0]);
      $r_result=db_query($query, $query_args);
      if(!$r_result) {
      }
      else {
        $select='<select id="edit-' . $col_obj->column_name . '" name="edit-' . $col_obj->column_name . '" >';
        while($r_obj=db_fetch_object($r_result)) {
          $select.='<option value="' . $r_obj->id . '">' . $r_obj->disp_col . '</option>';
        }
        $select.='</select>';
      }

      $html.='<td>' . $select . '</td>';
    }
    else {
      switch($col_type) {
        case 'date':
          $html.='<td><input id="edit-' . $col_obj->column_name . '" name="edit-' . $col_obj->column_name . '" type="text" value="' . $val . '" class="maskedDateInput" title="Date"/></td>';
          break;

        case 'smallint':
          $html.="<td><input type='text' id='edit-" . $col_obj->column_name . "' title='Integer' name='edit-" . $col_obj->column_name . "' class='form-text' value='{$val}' onKeyPress='return AllowOnlyNumbers_KeyDownHandler(this, event, true, false);' onBlur='db_validate(this,\"$col_type\",$col_length,$col_null);'></td>";
          break;

        case 'bigint':
          $html.="<td><input type='text' id='edit-" . $col_obj->column_name . "' title='Integer' name='edit-" . $col_obj->column_name . "' class='form-text' value='{$val}' onKeyPress='return AllowOnlyNumbers_KeyDownHandler(this, event, true, false);' onBlur='db_validate(this,\"$col_type\",$col_length,$col_null);'></td>";
          break;

        case 'integer':
          $html.="<td><input type='text' id='edit-" . $col_obj->column_name . "' title='Integer' name='edit-" . $col_obj->column_name . "' class='form-text' value='{$val}' onKeyPress='return AllowOnlyNumbers_KeyDownHandler(this, event, true, false);' onBlur='db_validate(this,\"$col_type\",$col_length,$col_null);'></td>";
          break;

        case 'numeric':
          $html.="<td><input type='text' id='edit-" . $col_obj->column_name . "' title='Integer' name='edit-" . $col_obj->column_name . "' class='form-text' value='{$val}' onKeyPress='return AllowOnlyNumbers_KeyDownHandler(this, event, true, false);' onBlur='db_validate(this,\"$col_type\",$col_length,$col_null);'></td>";
          break;

        default:
          /* Hardcoding for India Habitats Layer */
          if($col_obj->column_name == 'license') {
            $query="select code from licenses";
            $result=db_query($query);
            if(!$result) {
              $html.="<td><input type='text' id='edit-" . $col_obj->column_name . "' title='{$col_type}' name='edit-" . $col_obj->column_name . "' class='form-text' value='{$val}'></td>";
            }
            else {
              $select="<select id='edit-" . $col_obj->column_name . "' name='edit-" . $col_obj->column_name . "'>";
              while($obj=db_fetch_object($result)) {
                $code=$obj->code;
                if($code == $val) {
                  $select.="<option value='{$code}' selected>{$code}</option>";
                }
                else {
                  $select.="<option value='{$code}'>{$code}</option>";
                }
              }
              $select.="</select>";
              $html.="<td>" . $select . "</td>";
            }
          }
          else {
            $html.="<td><input type='text' id='edit-" . $col_obj->column_name . "' title='{$col_type}' name='edit-" . $col_obj->column_name . "' class='form-text' value='{$val}'></td>";
          }
          break;
      }
    }
    $html.="</tr>";
  }

  $html.="</table>";
  return $html;
}

function GetDBTableSchemaTableOld($result_cols, $vals_row=null) {
  $html.="<table id='tab_schema' border=1 cellspacing=0 style='font-size:12px;'>";
  $html.="<tr><th>Column Name</th><th>Column Description</th><th>Column Type</th><th>Column Length</th><th>NOT NULL</th><th>Column Default</th><th>Value</th></tr>";

  while($col_obj=db_fetch_object($result_cols)) {
    $html.="<tr>";
    $html.="<td>" . $col_obj->column_name . "</td>";
    $html.="<td>" . (($col_obj->column_description == "") ? $col_obj->column_name : ($col_obj->column_description)) . "</td>";
    $col_type=$col_obj->column_type;
    $col_length=$col_obj->column_length;
    if(substr($col_obj->column_type, 0, 17) == "character varying") {
      $col_type="character varying";
      $col_length=substr($col_obj->column_type, 18, - 1);
      $html.="<td>{$col_type}</td>";
      $html.="<td>{$col_length}</td>";
    }
    elseif(substr($col_obj->column_type, 0, 8) == "numeric(") {
      $col_type="numeric";
      $col_length=substr($col_obj->column_type, 8, - 1);
      $html.="<td>{$col_type}</td>";
      $html.="<td>&nbsp;</td>";
    }
    else {
      $col_type=$col_obj->column_type;
      $col_length=$col_obj->column_length;
      $html.="<td>{$col_type}</td>";
      $html.="<td>" . (($col_length == - 1) ? "&nbsp;" : ($col_length)) . "</td>";
    }

    $col_null=(($col_obj->column_null == "") ? 1 : 0);
    $html.="<td>" . (($col_obj->column_null == "") ? "&nbsp" : ($col_obj->column_null)) . "</td>";

    $col_default=(($col_obj->column_default == "") ? "" : ($col_obj->column_default));
    $html.="<td>" . (($col_obj->column_default == "") ? "&nbsp" : ($col_obj->column_default)) . "</td>";
    $val="";
    if(isset($vals_row)) {
      $val=$vals_row[$col_obj->column_name];
    }
    switch($col_type) {
      case 'smallint':
        $html.="<td><input type='text' id='edit-" . $col_obj->column_name . "' name='edit-" . $col_obj->column_name . "' class='form-text' value='{$val}' onKeyPress='return AllowOnlyNumbers_KeyDownHandler(this, event, true, false);' onBlur='db_validate(this,\"$col_type\",$col_length,$col_null);'></td>";
        break;

      case 'bigint':
        $html.="<td><input type='text' id='edit-" . $col_obj->column_name . "' name='edit-" . $col_obj->column_name . "' class='form-text' value='{$val}' onKeyPress='return AllowOnlyNumbers_KeyDownHandler(this, event, true, false);' onBlur='db_validate(this,\"$col_type\",$col_length,$col_null);'></td>";
        break;

      default:
        $html.="<td><input type='text' id='edit-" . $col_obj->column_name . "' name='edit-" . $col_obj->column_name . "' class='form-text' value='{$val}' onBlur='db_validate(this,\"$col_type\",$col_length,$col_null);'></td>";
        break;
    }
    $html.="</tr>";
  }
  $html.="</table>";
  return $html;
}

function GetTableColsOfType($tablename, $table_type, $col_type, $layer_id=NULL) {
  $table_info=array();
  $Meta_tablename="";
  $table_type=strtolower($table_type);

  if($table_type == 'layer' || $table_type == 'link') {
    if($table_type == 'layer') {
      $Meta_tablename="Meta_Layer";
    }
    elseif($table_type == 'link') {
      $Meta_tablename="Meta_LinkTable";
    }
    if($tablename == "") {
      $query='select %s_tablename, %s_columns from "%s" where layer_id = %d';
      $query_args=array($table_type, $col_type, $Meta_tablename, $layer_id);
      $result=db_fetch_array(db_query($query, $query_args));
      $table_info['layer_id']=$layer_id;
      $table_info["{$table_type}_tablename"]=$result["{$table_type}_tablename"];
    }
    else {
      $query="select layer_id, %s_columns from \"%s\" where %s_tablename = '%s'";
      $query_args=array($col_type, $Meta_tablename, $table_type, $tablename);
      $result=db_fetch_array(db_query($query, $query_args));
      $table_info['layer_id']=$result['layer_id'];
      $table_info["{$table_type}_tablename"]=$tablename;
    }
    $table_info["{$col_type}_columns"]=$result["{$col_type}_columns"];
  }
  elseif($table_type == 'resource') {
    $Meta_tablename="Meta_Global_Resource";
    $query="select %s_columns from \"%s\" where %s_tablename = '%s'";
    $query_args=array($col_type, $Meta_tablename, $table_type, $tablename);
    $result=db_fetch_array(db_query($query, $query_args));
    $table_info["{$table_type}_tablename"]=$tablename;
    $table_info["{$col_type}_columns"]=$result["{$col_type}_columns"];
  }

  return $table_info;
}

function GetLinkTableSummaryCols() {
  $layer_info=array();
  if(isset($_REQUEST['link_tablename'])) {
    $link_info['link_tablename']=$_REQUEST['link_tablename'];
    $query='select layer_id, summary_columns from "Meta_LinkTable" where link_tablename = \'%s\'';
    $result=db_fetch_object(db_query($query, $link_info['link_tablename']));
    $link_info['layer_id']=$result->layer_id;
    $link_info['summary_columns']=$result->summary_columns;
  }
  else {
    $link_info['layer_id']=$_REQUEST['layer_id'];
    $query='select link_tablename, summary_columns from "Meta_LinkTable" where layer_id = %d';
    $result=db_fetch_object(db_query($query, $link_info['layer_id']));
    $link_info['link_tablename']=$result->link_tablename;
    $link_info['summary_columns']=$result->summary_columns;
  }
  return $link_info;
}

function getResourceTableMapping($tablename) {
  $global_resource_mapping=array();
  $sql="select resource_tablename, resource_column, table_column from \"Global_Resource_Mapping\" where tablename = '%s'";
  $result=db_query($sql, $tablename);
  if(!$result) {
  }
  else {
    while($obj=db_fetch_object($result)) {
      $table_column=str_replace("'", "", $obj->table_column);
      $global_resource_mapping[$table_column]['resource_tablename']=$obj->resource_tablename;
      $global_resource_mapping[$table_column]['resource_column']=str_replace("'", "", $obj->resource_column);
    }
  }
  return $global_resource_mapping;
}

function getResourceTableNamesForTables($tables_arr) {
  $global_resource=array();

  array_walk($tables_arr, "singleQuoteString");
  $tables=implode(",", $tables_arr);

  $sql='select distinct resource_tablename from "Global_Resource_Mapping" where tablename in (' . $tables . ')';
  $result=db_query($sql);
  if(!$result) {
  }
  else {
    while($obj=db_fetch_object($result)) {
      $global_resource[]=$obj->resource_tablename;
    }
  }
  return $global_resource;
}

function getPopupUIMenu($layer_tablename, $row_id, $layer_name, $feature_title) {
  $menu=array();
  $base_path=$GLOBALS['base_path'];
  if((isset($_SESSION['popup_layer_tablename']) && $_SESSION['popup_layer_tablename'] != $layer_tablename) || ((isset($_SESSION['popup_row_id']) && $_SESSION['popup_row_id'] != $row_id))) {
    if(isset($_SESSION['popup_menu'])) {
      unset($_SESSION['popup_menu']);
    }
    unset($_SESSION['popup_layer_tablename']);
    unset($_SESSION['popup_row_id']);
  }
  $_SESSION['popup_layer_tablename']=$layer_tablename;
  $_SESSION['popup_row_id']=$row_id;
  if(!isset($_SESSION['popup_menu']) || $_SESSION['popup_menu'] != "") {
    $user=$GLOBALS['user'];

    $menu['divId']="divPopupPane";
    $menu['ulId']="ulPopupUIMenu";

    /* details tab */
    $details_lnk="{$base_path}ml_orchestrator.php?action=getLayerDataDetails&row_id={$row_id}&layer_tablename={$layer_tablename}&hasmenu=1";
    $menu['details_id']='layerDetails';
    $menu['details_lnk']=$details_lnk;
    $menu['details_title']='Details';

    /* end details tab */

    /* edit details */
    if(userHasEditLayerDataPerm($layer_tablename, $row_id)) {
      $liID="editFeatureDetails";
      $lnk="{$base_path}ml_orchestrator.php?action=getLayerTableSchema&layer_tablename={$layer_tablename}&id={$row_id}";
      $title="Edit Details";
      $menu['editDetails_id']=$liID;
      $menu['editDetails_lnk']=$lnk;
      $menu['editDetails_title']=$title;
    }
    /* end edit details */

    /* drupal node */
    $query='SELECT ' . AUTO_DBCOL_PREFIX . 'nid FROM "%s" where ' . AUTO_DBCOL_PREFIX . 'id = %d';
    $result=db_query($query, $layer_tablename, $row_id);
    if(!$result) {
    }
    else {
      $obj=db_fetch_object($result);
      $nid=$obj-> {
        AUTO_DBCOL_PREFIX . 'nid'
      };
      if($nid) {
        /* if drupal node is assigned, show it and comments. */
        $liID="drupalNode";
        $lnk="{$base_path}node/" . $nid . "/popup";
        $title="More Details";
        $menu['moreDetails_id']=$liID;
        $menu['moreDetails_lnk']=$lnk;
        $menu['moreDetails_title']=$title;
        if(node_access('update', node_load($nid))) {
          $liID="drupalEditDetails";
          $lnk="{$base_path}node/{$nid}/edit/popup";
          $title="Edit More Details";

          $menu['editMoreDetails_id']=$liID;
          $menu['editMoreDetails_lnk']=$lnk;
          $menu['editMoreDetails_title']=$title;
        }
        if($user->uid) {
          $liID="drupalAddComments";
          $lnk="{$base_path}comment/reply/$nid#comment-form";
          $title="Add Comments";
          $menu['addComments_id']=$liID;
          $menu['addComments_lnk']=$lnk;
          $menu['addComments_title']=$title;
        }
      }
      else {
        if($user->uid && user_access("create node_mlocate_feature")) {
          $liID="drupalAddMoreDetails";
          $lnk="{$base_path}node/add/node-mlocate-feature/popup?layer_tablename={$layer_tablename}&point_id={$row_id}";
          $title="Add More Details";
          $menu['addMoreDetails_id']=$liID;
          $menu['addMoreDetails_lnk']=$lnk;
          $menu['addMoreDetails_title']=$title;
        }
      }
    }
    /* end drupal node */

    /* charts */
    $liID="featureCharts";
    $menu['charts_id']=$liID;
    /* end charts */

    /* media */
    $columns_arr=array();
    $columns_arr[]="media_columns";
    $metainfo=get_values_metatable($layer_tablename, $row_id, TABLE_TYPE_LAYER, $columns_arr);
    $media_columns=$metainfo['media_columns'];
    if($media_columns != "") {
      $cols=explode(",", $media_columns);
      foreach($cols as $col) {
        $col=str_replace("'", "", $col);
        $col=trim($col);
        $title=strtoupper(substr($col, 0, 1)) . substr($col, 1);
        $lnk="{$base_path}ml_orchestrator.php?action=getMedia&id={$row_id}&layer_tablename={$layer_tablename}&media_column={$col}";
        $liID='media_' . $col;
        $menu[$col . '_media_id']=$liID;
        $menu[$col . '_media_lnk']=$lnk;
        $menu[$col . '_media_title']=$title;
      }
    }

    /* end media */

    /* link tables info */
    $link_tablenames=getLinkTableNames($layer_tablename);
    $i=1;
    foreach($link_tablenames as $link) {
      $tablename=$link['tablename'];
      $liID="linkedData_{$i}";
      $title=$link['name'];
      if($title == "") {
        $title="Linked Data {$i}";
      }
      $lnk="{$base_path}ml_orchestrator.php?action=getLinkTableEntries&layerdata_id={$row_id}&layer_tablename={$layer_tablename}&link_tablename={$tablename}";
      $arr_linktable[$title . '_liId']=$liID;
      $arr_linktable[$title . '_lnk']=$lnk;
      $arr_linktable[$title . '_title']=$title;
      $i++;
    }
    if(sizeof($link_tablenames) != 0) {
      $menu['linktable']=$link_tablenames;
    }
    else {
      $menu['linktable']="";
    }
    /* end link tables info */

    $_SESSION['popup_menu']=$menu;
  }
  else {
    $menu=$_SESSION['popup_menu'];
  }
  return $menu;
}

function getLinkTableNames($layer_tablename) {
  $tablenames=array();
  $query='select mlt.link_name, mlt.link_tablename from "Meta_LinkTable" mlt join "Meta_Layer" ml on mlt.layer_id = ml.layer_id and ml.layer_tablename = \'%s\'';
  $result=db_query($query, $layer_tablename);
  if($result) {
    $i=0;
    while($obj=db_fetch_object($result)) {
      $tablenames[$i]['name']=$obj->link_name;
      $tablenames[$i]['tablename']=$obj->link_tablename;
      $i++;
    }
  }
  return $tablenames;
}

function userHasAddFeaturePerm($layer_tablename) {
  $user=$GLOBALS['user'];
  if($user->uid) {

    $user_role=getUserRoleForLayer($layer_tablename);
    if($user_role != "") {
      $for_role=$layer_tablename . ' ' . $user_role;
      $arr_perms=getRoleMLOCATEPerms($for_role);
      if(in_array("add feature on map", $arr_perms)) {
        return TRUE;
      }
    }
  }
  return FALSE;
}

function userHasAddLinkedDataPerm($layer_tablename) {
  $user=$GLOBALS['user'];
  if($user->uid) {
    $user_role=getUserRoleForLayer($layer_tablename);
    $for_role=$layer_tablename . ' ' . $user_role;
    $arr_perms=getRoleMLOCATEPerms($for_role);
    if(in_array("add linked table entry", $arr_perms)) {
      return TRUE;
    }
  }
  return FALSE;
}

function validateFeature($layer_tablename, $row_id) {
  $user=$GLOBALS['user'];
  $user_role=getUserRoleForLayer($layer_tablename);
  if($user->uid && ($user_role == "admin" || $user_role == "validator")) {
    $query='UPDATE "%s" SET ' . AUTO_DBCOL_PREFIX . 'status = 1, ' . AUTO_DBCOL_PREFIX . 'validated_by = %d, ' . AUTO_DBCOL_PREFIX . 'validated_date = now() WHERE ' . AUTO_DBCOL_PREFIX . 'id = %d';
    $result=db_query($query, $layer_tablename, $user->uid, $row_id);
    if(!$result) {
      return "Error. Record could not be saved.";
    }
    else {
      return "Record saved.";
    }
  }
  else {
    return "Error. Your are not authorized.";
  }
}

function getCCLicenseHTML($layer_license, $size='small') {
  if($layer_license == "") {
    return "";
  }
  else {
    $query="select count(code) as cnt from licenses where code = '%s'";
    $result=db_query($query, $layer_license);
    $cnt=0;
    if($result) {
      $obj=db_fetch_object($result);
      $cnt=$obj->cnt;
    }
    if($cnt == 0) {
      return "<b>License: </b>" . $layer_license;
    }
    else {
      if($size == 'large') {
        $img_size="88x31";
      }
      else {
        $img_size="80x15";
      }
      $lcode=$layer_license;
      $lcode=str_replace("(", "", $lcode);
      $lcode=str_replace(")", "", $lcode);
      $license='<a title="Creative Commons License" href="http://creativecommons.org/licenses/' . $lcode . '/3.0/" target="_blank"><img src="http://i.creativecommons.org/l/' . $lcode . '/3.0/' . $img_size . '.png"></img></a>';
      return $license;
    }
  }
}

function getInfoEntry($key, $value) {
  $se=$GLOBALS['infoEntry'];
  $se=str_replace("{%key}", $key, $se);
  $se=str_replace("{%value}", ($value == "" ? '&nbsp;' : $value), $se);
  return $se;
}

function getTableAttribForLayerInfo($tablename, $skip_cols=array()) {
  $attr="";
  $col_info=getDBColDesc($tablename);
  foreach($col_info as $col=>$desc) {
    if(!in_array($col, $skip_cols)) {
      if(substr($col, 0, 7) != AUTO_DBCOL_PREFIX) {
        $attr.="<li>" . (($desc == "" ? $col : $desc)) . "</li>";
      }
    }
  }
  return $attr;
}

function getUserLink($userid) {
  $res=db_fetch_array(db_query("select name from users where uid = %d", $userid));
  return "<a href='{$base_path}user/{$userid}/popup' target='_blank'>" . $res['name'] . "</a>";
}

function getMenuLI($liID, $divID, $ulID, $lnk, $title) {
  return '<li id="' . $liID . '"><a href="javascript:popupTabClicked(\'' . $divID . '\',\'' . $ulID . '\',\'' . $liID . '\',\'' . $lnk . '\')">' . $title . '</a></li>';
}
