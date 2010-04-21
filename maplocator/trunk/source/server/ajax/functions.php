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
*This file contains utility functions access from other files
*
***/

require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);


 define("AUTO_DBCOL_PREFIX", "__mlocate__");
 define("SITE_ADMIN_ROLE", $_SESSION["SITE_ADMIN_ROLE"]);
 define("TABLE_TYPE_LAYER", "LAYER");
 define("TABLE_TYPE_LINK", "LINK");

function getUserName($userid){
  $res = db_fetch_array(db_query("select name from users where uid = %d", $userid));
  return $res['name'];
}

function getLayerExtent($table,$layertype,$list="") {
  //echo $layertype;
  if('RASTER' != $layertype and $layertype !='' ){
  	// echo 'here'. $layertype;
    if ($list == null || $list == "") {
      $query = 'SELECT astext(extent('.AUTO_DBCOL_PREFIX.'topology)) as extent from "%s"' ;
      $query_args = $table;
    } else{
      $query = 'SELECT astext(extent('.AUTO_DBCOL_PREFIX.'topology)) as extent from "%s" where '.AUTO_DBCOL_PREFIX.'id IN(%s)' ;
      $query_args = array($table, $list);
    }
    $result_db = db_query($query, $query_args);
    $extent = '';
    if(!$result_db) {
      $extent = "Error fetching extent. Please try after sometime.";
    } else {
      while ($user_data = db_fetch_object($result_db)) {
        $extent = $user_data->extent;
      }
      if (strpos($extent, 'POINT') === false){
        $extent = str_replace('POLYGON((','',$extent);
        $extent = str_replace('))','',$extent);
        $arr = explode(",",$extent);
        $extent = $arr[0].",". $arr[2];
        $extent = str_replace(' ',',',$extent);
      } else {
        $extent = str_replace('POINT(','',$extent);
        $extent = str_replace(')','',$extent);
        $extent = $extent.",". $extent;
        $extent = str_replace(' ',',',$extent);
      }
    }
  } else {
     //read metadata and get the upper right and lower left corner
    $char_pos=strpos($upper_right,'d'); //this will be used if .metadata file is for raster layers
     $filename = "metadata/".$table.".metadata";
     if(file_exists($filename)){
       $fcontents = file($filename);
       $len = count($fcontents);

       $upper_right = $fcontents[$len-3];
       $lower_left = $fcontents[$len-4];
       $index1 = strpos($upper_right,'(');
       $index2 = strpos($upper_right,')');

       $upper_right = substr($upper_right,$index1+1,$index2-$index1-1);
       $index1 = strpos($lower_left,'(');
       $index2 = strpos($lower_left,')');
       $lower_left = substr($lower_left,$index1+1,$index2-$index1-1);

       $right_top = split(",",$upper_right);
       $left_bottom = split(",",$lower_left);

       if($char_pos === true) { //if long and lat points are in degrees (for vector layers)

	 $right_top_lon=getLatLon($right_top[0]);
	 $right_top_lat=getLatLon($right_top[1]);

	 $left_bottom_lon=getLatLon($left_bottom[0]);
	 $left_bottom_lat=getLatLon($left_bottom[1]);
	 $extent = $left_bottom_lon.",".$left_bottom_lat.",".$right_top_lon.",".$right_top_lat;
       }
       else {   //for raster layers

	 $extent=$left_bottom[0].",".$left_bottom[1].",".$right_top[0].",".$right_top[1];
       }
    } else {
       $extent = '';
     }
  }
  return $extent;
}

function getLatLon($value){
  $index1 = strpos($value,"d");
  $deg = substr($value,0,$index1);
  $index2 = strpos($value,"'");
  $min = substr($value,$index1+1,$index2-$index1-1);
  $index1 = strpos($value,"\"");
  $sec = substr($value,$index2+1,$index1-$index2);
  $mm=$min+$sec/60;
  $latlon = $deg+$mm/60;

  return $latlon;
}

function GetQueryColumnDetails($tablename, $columns = NULL, $column_type = NULL, $onlyDesc = TRUE) {
  $query = <<<END
    SELECT pg_catalog.quote_ident(attname) as column_name
END;
  if(!$onlyDesc) {
    $query .= <<<END
         , attlen as column_length
         , pg_type.typname
         , CASE
           WHEN pg_type.typname = 'int4'
                AND EXISTS (SELECT TRUE
                              FROM pg_catalog.pg_depend
                              JOIN pg_catalog.pg_class ON (pg_class.oid = objid)
                             WHERE refobjsubid = attnum
                               AND refobjid = attrelid
                               AND relkind = 'S') THEN
             'serial'
           WHEN pg_type.typname = 'int8'
                AND EXISTS (SELECT TRUE
                              FROM pg_catalog.pg_depend
                              JOIN pg_catalog.pg_class ON (pg_class.oid = objid)
                             WHERE refobjsubid = attnum
                               AND refobjid = attrelid
                               AND relkind = 'S') THEN
             'bigserial'
           ELSE
             pg_catalog.format_type(atttypid, atttypmod)
           END as column_type
         , CASE
           WHEN attnotnull THEN
             cast('NOT NULL' as text)
           ELSE
             cast('' as text)
           END as column_null
         , CASE
           WHEN pg_type.typname IN ('int4', 'int8')
                AND EXISTS (SELECT TRUE
                              FROM pg_catalog.pg_depend
                              JOIN pg_catalog.pg_class ON (pg_class.oid = objid)
                             WHERE refobjsubid = attnum
                               AND refobjid = attrelid
                               AND relkind = 'S') THEN
             NULL
           ELSE
             adsrc
           END as column_default
         , attnum
         , attrelid
END;
  }
  $query .= <<<END
         , pg_catalog.col_description(attrelid, attnum) as column_description
      FROM pg_catalog.pg_attribute
END;
  if(!$onlyDesc) {
    $query .= <<<END
                 JOIN pg_catalog.pg_type ON (pg_type.oid = atttypid)
      LEFT OUTER JOIN pg_catalog.pg_attrdef ON (   attrelid = adrelid
                                               AND attnum = adnum)
END;
  }
  $query .= <<<END
     WHERE attnum > 0
       AND attisdropped IS FALSE
       AND attrelid = (SELECT oid
       FROM pg_class
END;
  $query .= " WHERE relname = '%s')";
  if($column_type) {
    $query .= " AND pg_type.typname = '{$column_type}'";
  }
  if(isset($columns)) {
    $query .= " AND pg_catalog.quote_ident(attname) in ({$columns})";
  }
  return array($query, array($tablename));
}

function getDBColDesc($tablename, $cols = NULL, $column_type = null, $onlyDesc = TRUE) {
  $col_info = array();
  $query = GetQueryColumnDetails($tablename, $cols, $column_type, $onlyDesc);
  $result_cols = db_query($query[0], $query[1]);
  if(!$result_cols) {
  } else {
    if($onlyDesc) {
      while($col = db_fetch_array($result_cols)) {
        $col_info[$col['column_name']] = $col['column_description'];
      }
    } else {
      while($col = db_fetch_array($result_cols)) {
        $col_name = $col['column_name'];
        $col_info[$col_name]['description'] = $col['column_description'];
        $col_info[$col_name]['type'] = $col['column_type'];
      }
    }
  }
  return $col_info;
}

function notify_admin_of_node_update($node_type, $layer_tablename, $nid, $op) {
  $query = "select layer_name, layer_tablename from \"Meta_Layer\" where layer_tablename = '%s'";

  $result = db_query($query, $layer_tablename);
  if(!$result) {
    return FALSE;
  } else {
    $obj = db_fetch_object($result);
    if($obj->layer_tablename == "") {
      return FALSE;
    }

    $layer_tablename = $obj->layer_tablename;
    $layer_name = $obj->layer_name;
  }

  $name .= '"' . $layer_name . '"';

  global $user;
  $username = $user->name . "(" . $user->mail . ")";

  $body = "";
  switch($op) {
    case 'insert':
      $subject = "{$name} layerinfo created";
      $body = "{$node_type} (" . url("node/$nid", array('absolute' => TRUE)) . ") for {$name} has been created by {$username}.";
      break;
    case 'update':
      $subject = "{$name} layerinfo updated";
      $body = "{$node_type} (" . url("node/$nid", array('absolute' => TRUE)) . ") for {$name} has been updated by {$username}.";
      break;
    case 'delete':
      $subject = "{$name} layerinfo deleted";
      $body = "{$node_type} (" . url("node/$nid", array('absolute' => TRUE)) . ") for {$name} has been deleted by {$username}.";
      break;
  }

  // Get the layer admins email IDs.
  $query = "select mail from users where uid in (select uid from users_roles where rid in (select rid from role where name = '%s admin' OR name = '%s validator' OR name = '".SITE_ADMIN_ROLE."'));";
  $result = db_query($query, $layer_tablename);
  if(!$result) {
    return FALSE;
  } else {
    $admin_ids = array();
    while($obj = db_fetch_object($result)) {
      $admin_ids[] = $obj->mail;
    }

    if(sizeof($admin_ids) == 0) {
      return FALSE;
    } else {
      $to = implode(",", $admin_ids);
      if(!in_array($user->mail,$admin_ids)){
         //user mail id not present in the list already
         $to.= ",".$user->mail;
      }
    }
  }

  $default_from = variable_get('site_mail', ini_get('sendmail_from'));

  // Additional headers
  $headers['To'] = $to;

  // Bundle up the variables into a structured array for altering.
  $message = array(
    'to'       => $to,
    'from'     => isset($from) ? $from : $default_from,
    'subject'  => $subject,
    'body'     => $body,
    'headers'  => $headers
  );

  return drupal_mail_send($message);
}

function singleQuoteString(&$val, $key=NULL) {
  $val = "'" . $val . "'";
}
function doubleQuoteString(&$val, $key=NULL) {
  $val = '"' . $val . '"';
}
function GetLayerTableNamesForValidation(){
  $user = $GLOBALS['user'];
  $user_id = $user->uid;
  if ($user_id > 0) {
    $roles = $user->roles;
    $layertablenames ='';
    foreach($roles as $role) {
      switch($role){
        case SITE_ADMIN_ROLE:
            $layertablenames = 'ALL';
            break;
        case 'authenticated user':
            break;
        default:
             $role_array = explode(' ',$role);
                         if (count($role_array) > 0) {
                            if ($role_array[1] == 'admin' || $role_array[1] == 'validator' ) {
                $layertablenames .= "'".$role_array[0]. "',";

                            }
                          }
      } // switch

    }
    if ( strpos($layertablenames, ',') == true) {
      $layertablenames =  substr_replace($layertablenames,"",-1);

    }
    if ( strpos($layertablenames, 'ALL') == true) {
      $layertablenames =  'ALL';
    }

  }
  Return $layertablenames;
}

function getDefaultLayerPerms($for_role) {
  $perms = "";
  if($for_role == 'authenticated user') {
    $default_role = $for_role;
  } else {
    $default_role = 'layer' . strstr($for_role, ' ');
  }
  $default_perms = array();
  $query = "select perm from mlocate_default_permissions where role_type = '%s'";
  $result = db_query($query, $default_role);
  if(!$result) {
    die("Error fetching data. Please try after some time or contact the admin.");
  } else {
    if($obj = db_fetch_object($result)) {
      $perms = $obj->perm;
    }
  }
  return $perms;
}

function getExceptionLayerPerms($for_role) {
  $perms = "";
  $exception_perms = array();
  $query = "select perm from mlocate_permission where role_name = '%s'";
  $result = db_query($query, $for_role);
  if(!$result) {
    die("Error fetching data. Please try after some time or contact the admin.");
  } else {
    if($obj = db_fetch_object($result)) {
      $perms = $obj->perm;
    }
  }
  return $perms;
}

function getRoleMLOCATEPerms($for_role) {
  $layer_perms = array();

  $default_perms = array();
  $str_default_perms = getDefaultLayerPerms($for_role);
  if(strlen($str_default_perms) > 0) {
    $default_perms = explode(",", $str_default_perms);
  }

  $exception_perms = array();
  $str_exception_perms = getExceptionLayerPerms($for_role);
  if(strlen($str_exception_perms) > 0) {
    $exception_perms = explode(",", $str_exception_perms);
  }

  if(sizeof($exception_perms) == 0) {
    $layer_perms = $default_perms;
  } else {
    $layer_perms = $exception_perms;
  }
  return $layer_perms;
}

function treeViewEntryHTML($layer_tablename, $layer_name, $participation_type,$layer_type, $p_nid, $access, $fids = null, $feature_count = null) {

  $layersChecked = $GLOBALS['layersChecked'];

  if ($fids == null) {
    $chkBxName = "categories";
  } else { // It is either search or validate tab
    $chkBxName = $fids;
  }

  $checked = "";
  $display_extent = "none";

  if (in_array($layer_tablename, $layersChecked)) {
    $checked = "checked";
    $display_extent = "inline";
  }

  $inputBox = '<td><div class="LayerTreeElem"><input type="checkbox" name="' . $chkBxName . '" title = "Display ' . $layer_name.'" id = "'.$layer_name . '" value="' . $layer_tablename . '" onclick="getData_Category(this.value,this.checked,this.name,this);" ' . $checked . '></input></div></td>';

  $imgUrl = base_path() . path_to_theme().'/images/icons/information.png';
  $layer_info = '<td><div class="LayerTreeElem"><a href="#" title="'.$layer_name.' Information" onClick="javascript:getLayerMetadata(\'' . $layer_tablename . '\');"><img alt="" src="' . $imgUrl . '"/></a></div></td>';

  $imgUrl = base_path().path_to_theme().'/images/icons/participate.png';
  $prtcptn = "";
  if ($participation_type > 0) {
    $prtcptn = '<td><div class="LayerTreeElem"><a style="text-decoration: none" title="Participation Info for ' . $layer_name . '" href="javascript:showParticipationInfo(\'' . $layer_tablename . '\',' . $p_nid . ');"><img id =picon_'. $layer_tablename .' alt="Participate" src="'. $imgUrl .'"/></a></div></td>';
  }

  if($fids == null){
    $imgUrl = base_path().path_to_theme().'/images/icons/download-layertree.png';
    if($access) {
  	  $download = '<td><div class="LayerTreeElem"><a href="#" title="Download '.$layer_name.'" onClick="javascript:getDownloadFormats(\'' .$layer_tablename. '\');"><img alt="" src="' . $imgUrl . '"/></a></div></td>';
    }
  }
 if($fids == null){
  $imgUrl = base_path().path_to_theme().'/images/icons/zoom-to-extent.png';
  $extent = '<td><div id="img_'. $layer_tablename .'" style="display:'.$display_extent.'" class="LayerTreeElem"><a href="#" title="Zoom to layer extent" onclick="javascript:zoomToExtent(\''. getLayerExtent($layer_tablename,$layer_type,$fids). '\');"><img src="'. $imgUrl .'" alt="Zoom to layer extent"/></a></div></td>';
  }
  $feature_count_text = "";
  if ($feature_count != null) {
    $feature_count_text = '&nbsp;<b color="blue"> ('. $feature_count .')</b>';
  }
  $layerNameText = '<td><div class="LayerTreeElem"><a id = "anch_'. $layer_tablename . '" href="#" title="Display ' . $layer_name . '" onclick = "javascript:toggleLayer(\'' . $layer_tablename . '\');">' . str_replace(" ", "&nbsp;", $layer_name) . $feature_count_text . '</a></div></td>';

  $html = '<table cellspacing="0" style="border-collapse:separate;"><tr>' . $inputBox . $layer_info . $prtcptn . $download. $extent . $layerNameText . '</tr></table>';

  return $html;
}

function treeViewEntryJSON($layer_tablename, $layer_name, $participation_type, $p_nid, $access, $fids = null, $feature_count = null)
{
	// temporary json variable whose value is returned to the calling function
	$json = array();

	$layersChecked = $GLOBALS['layersChecked'];

	if ($fids == null) {
		$chkBxName = "categories";
	} else { // It is either search or validate tab
		$chkBxName = $fids;
	}

	$checked = "";
	$display_extent = "none";

	if (in_array($layer_tablename, $layersChecked))
	{
		$checked = "checked";
		$display_extent = "inline";
	}

	$json['layer_name'] = $layer_name;
	$json['layer_tablename'] = $layer_tablename;
	$json['chkBxName'] = $chkBxName;
	$json['checked'] = $checked;
	$json['display_extent'] = $display_extent;
	$json['feature_count'] = $feature_count;
	$json['p_nid'] = $p_nid;

	$imgUrl = base_path() . path_to_theme().'/images/icons/information.png';
	$json['info_imgUrl'] = $imgUrl;


	$imgUrl = base_path().path_to_theme().'/images/icons/participate.png';
	if ($participation_type > 0) {
		$json['prtcptn_imgUrl'] = $imgUrl;
	}

	if($fids == null)
	{
		$imgUrl = base_path().path_to_theme().'/images/icons/download-layertree.png';
		if($access) {
			$json['downl_imgUrl'] = $imgUrl;
		}
	}
	if($fids == null){
		$imgUrl = base_path().path_to_theme().'/images/icons/zoom-to-extent.png';
		$json['layer_extent'] = getLayerExtent($layer_tablename, $fids);
		$json['zoomtoext_imgUrl'] = $imgUrl;
	}

	return $json;
}

function getLayerProjection($layer_tablename) {
  $projection = 'EPSG:4326';

  $query = "select srid from geometry_columns where f_table_name = '%s'";
  $result = db_query($query, $layer_tablename);
  if (!$result) {
  } else {
    if ($obj = db_fetch_object($result)) {
      $srid = $obj->srid;
      if ($srid != -1) {
        $projection = 'EPSG:' . $srid;
      }
    }
  }

  return $projection;
}

function getThemesByType($theme_type) {
  $i = 0;
  $themes = array();
  $query = 'SELECT theme_id, theme_name, icon, astext(geolocation) as geolocation FROM "Theme" where theme_id = parent_id and theme_type = %d and status = 1 order by theme_name';
  $result = db_query($query, $theme_type);
  if (!$result) {
    return false;
  } else {
    while($obj = db_fetch_object($result)) {
      $themes[$i]['theme_id'] = $obj->theme_id;
      $themes[$i]['theme_name'] = $obj->theme_name;
      $themes[$i]['icon'] = $obj->icon;
      $loc = $obj->geolocation;
      $loc = str_replace("POINT(", "", $loc);
      $loc = str_replace(")", "", $loc);
      $loc = str_replace(" ", ",", $loc);
      $themes[$i]['geolocation'] = $loc;
      $i++;
    }
  }
  return $themes;
}

function _getThemeChildNodes($theme_id, $cat_id, $level) {
  $theme_type = 0;
  $query = 'select theme_type from "Theme" where theme_id = %d';
  $query_args = array($theme_id);
  $result = db_query($query, $query_args);
  if (!$result) {
    return false;
  } else {
    if($obj = db_fetch_object($result)) {
      $theme_type = $obj->theme_type;
    }
  }

  $themes = array();
  if($theme_type == 1) {
    $query = 'SELECT theme_id, theme_name, icon FROM "Theme" where theme_id != parent_id and parent_id = %d and status = 1 order by theme_name';
    $result = db_query($query, $theme_id);
    if (!$result) {
      return false;
    } else {
      while($obj = db_fetch_object($result)) {
        $thm = array();
        $thm['type'] = 'theme';
        $thm['id'] = $obj->theme_id;
        $thm['name'] = $obj->theme_name;
        $thm['icon'] = $obj->icon;
        if($level > 1) {
          $t = _getThemeChildNodes($theme_id, $cat_id, $level - 1);
          if($t === false) {
            return false;
          }
          if(count($t) > 0) {
            $thm['children'] = $t;
          }
        }
        $themes[] = $thm;
      }
    }
  } elseif($theme_type == 2) {
    if($cat_id == 0) {
      $query = 'select category_id, category_name from "Categories_Structure" where category_id = parent_id';
      $result = db_query($query);
    } else {
      $query = 'select category_id, category_name from "Categories_Structure" where category_id != parent_id and parent_id = %d';
      $query_args = array($cat_id);
      $result = db_query($query, $query_args);
    }
    if (!$result) {
      return false;
    } else {
      while($obj = db_fetch_object($result)) {
        $thm = array();
        $thm['type'] = 'category';
        $thm['id'] = $obj->category_id;
        $thm['name'] = $obj->category_name;
        $thm['icon'] = $obj->icon;

        $children = array();
        if($level > 1) {
          $t = _getThemeChildNodes($theme_id, $obj->category_id, $level - 1);
          if($t === false) {
            return false;
          }
          if(count($t) > 0) {
            //$thm['children'] = $t;
            $children = $t;
          }
        }

        if(count($children) > 0) {
          $thm['children'] = $children;
        }

        $themes[] = $thm;
      }

      // Check if any layers are assigned to this theme_id and category_id;
      $query1 = 'select layer_tablename, layer_name, layer_type, nid from "Meta_Layer" where status = 1 and layer_id in (select layer_id from "Theme_Layer_Mapping" where status = 1 and theme_id = %d and category_id = %d)';
      $query_args1 = array($theme_id,  $cat_id);
      $result1 = db_query($query1, $query_args1);
      $layers = array();
      if (!$result1) {
        return false;
      } else {
        while($obj1 = db_fetch_object($result1)) {
          $lyr = array();
          $lyr['type'] = 'layer';
          $lyr['id'] = $obj1->layer_tablename;
          $lyr['name'] = $obj1->layer_name;
          switch($obj1->layer_type) {
            case 'POINT':
            case 'MULTIPOINT':
              $lyr['layer_type'] = 'POINT';
              break;
            case 'POLYGON':
            case 'MULTIPOLYGON':
              $lyr['layer_type'] = 'POLYGON';
              break;
            case 'LINE':
            case 'LINESTRING':
            case 'MULTILINESTRING':
              $lyr['layer_type'] = 'LINE';
              break;
            case 'RASTER':
              $lyr['layer_type'] = 'RASTER';
              break;
            default:
              $lyr['layer_type'] = $obj1->layer_type;
              break;
          }
          $lyr['nid'] = $obj1->nid;
          $layers[] = $lyr;
        }
      }
      if(count($layers) > 0) {
        $themes = array_merge($themes, $layers);
      }

    }
  }

  return $themes;
}

function getLayersByThemeType($theme_type) {
  $i = 0;
  $layers = array();
  $query = 'select tlm.theme_id, ml.layer_id, ml.layer_tablename, ml.layer_name, ml.access, ml.p_nid, ml.participation_type from "Theme_Layer_Mapping" tlm, "Theme" tm, "Meta_Layer" ml where tlm.theme_id = tm.theme_id and tlm.layer_id = ml.layer_id and tlm.status = 1 and tm.status = 1 and ml.status = 1 and tm.theme_id = tm.parent_id and tm.theme_type = %d order by tm.theme_name, ml.layer_name;';
  $result = db_query($query, $theme_type);
  if (!$result) {
    return false;
  } else {
    while($obj = db_fetch_object($result)) {
      $layer = array();
      $layer['layer_id'] = $obj->layer_id;
      $layer['layer_tablename'] = $obj->layer_tablename;
      $layer['layer_name'] = $obj->layer_name;
      $layer['access'] = $obj->access;
      $layer['p_nid'] = $obj->p_nid;
      $layer['participation_type'] = $obj->participation_type;
      $layers[$obj->theme_id][] = $layer;
      $i++;
    }
  }
  return $layers;
}

function isUserAuthorizedToEditMetadata($tablename, $table_type) {
  $user = $GLOBALS['user'];
  if($user->uid) {
    $user_roles = $user->roles;
    if(in_array(SITE_ADMIN_ROLE, $user_roles)) {
      return TRUE;
    }
    if($table_type == TABLE_TYPE_LAYER || $table_type == TABLE_TYPE_LINK) {
      $layer_tablename = $tablename;
      if($table_type == TABLE_TYPE_LINK) {
        $query = 'select layer_tablename from "Meta_Layer" where layer_id = (select layer_id from "Meta_LinkTable" where link_tablename = \'%s\')';
        $result = db_query($query, $tablename);
        if(!$result) {
          return FALSE;
        } else {
          if($obj = db_fetch_object($result)) {
            $layer_tablename = $obj->layer_tablename;
          } else {
            return FALSE;
          }
        }
      }

      if(in_array($tablename . ' admin', $user_roles)) {
        return TRUE;
      } else {
        return FALSE;
      }
    }
  }
  return FALSE;
}

function getDTD($col_info,$layer_type,$layer_tablename){
  if(count($col_info) > 0) {
    $dtd = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
    $dtd .= "<!ELEMENT kml    (Document)>\n";
    $dtd .= "<!ATTLIST kml xmlns CDATA \"\">\n";
    $dtd .= "<!ELEMENT Document  (Folder+)>\n";
    $dtd .= "<!ELEMENT Folder  (name,Placemark+)>\n";
    $dtd .= "<!ELEMENT Placemark  (description,Point)>\n";
    $dtd .= "<!ELEMENT description (";
    foreach($col_info as $col_name => $col_desc){
      $dtd .= $col_name .",";
    }
    $dtd .= ")>\n<!ELEMENT ".strtolower($layer_type)."  (coordinates)>\n";
    $dtd .= "<!ELEMENT coordinates  (#PCDATA)>\n";
    $dtd .= "<!ELEMENT name (#PCDATA)>";
    foreach($col_info as $col_name => $col_desc){
      $dtd .= "<!ELEMENT ".$col_name." (#PCDATA)>\n";
      $dtd .= "<!-- ".$col_name." ".$col_desc." -->\n";
    }

  } else {
     $dtd = "";
  }
  $file = "upload/dtd_".$layer_tablename.".xml";
  if (!($fp = fopen($file, "w+"))) {
       die(return_error("Error opening DTD file"));
  }
  fwrite($fp,$dtd);
  fclose($fp);
  return htmlentities($dtd);
}

function getThemeIconUrl($icon_name) {
  if($icon_name == "") {
    return "";
  }
  $themeimgUrl =  base_path().path_to_theme().'/images/icons/theme-';
  $url = $themeimgUrl . $icon_name . ".png";

  return $url;
}

function getLayerIconUrl($layer){
  $themeimgUrl =  base_path().path_to_theme().'/images/icons/theme-';
  $layerimgUrl = base_path().path_to_theme().'/images/icons/layer-';
  $layericonarr = split("_",$layer,3);

  $layericon = $layericonarr[2]. ".png";

  if ( !file_exists($layerimgUrl . $layericon )) {
    $layericon = "";
  } else {
    $img = $layerimgUrl . $layericon ;
  }

  if ($layericon == "") {
    $img = "";
    $query = "SELECT icon FROM \"Theme\" where theme_type = 1 and status = 1 and theme_id IN ( select theme_id from \"Theme_Layer_Mapping\" where layer_id =(select layer_id from \"Meta_Layer\" where layer_tablename like '%s'))";
    $result_db = db_query($query, $layer);
    if ($data = db_fetch_object($result_db)) {
      if($data->icon != "") {
        $img = $themeimgUrl. $data->icon . ".png";
      }
    }
  }
  return $img;
}

function getFeatureCount($table){
	$query = 'SELECT count(*) as count from "%s"';
	$result_db = db_query($query, $table);
	$count = '';
	if ($user_data = db_fetch_object($result_db)) {
		$count = $user_data->count;
	}

	return $count;

}

function getStartEndDatesForColumns($layer_tablename, $datecolumns) {
  $colsinfo = array();
  foreach($datecolumns as $col) {
    $query = "select min(%s), max(%s) from %s";
    $result = db_query($query, array($col, $col, $layer_tablename));
    if(!$result) {
    } else {
      if($info = db_fetch_array($result)) {
        $colsinfo[$col]['startdate'] = $info['min'];
        $colsinfo[$col]['enddate'] = $info['max'];
      }
    }
  }
  return $colsinfo;
}

function GenerateBBoxes($BBOX,$GRID_SIZE)
{
	$coords = explode(",",$BBOX );
	$left_bottom = explode(" ",$coords[0]);
	$right_top = explode(" ",$coords[1]);
	$left = $left_bottom[0] ;
	$bottom = $left_bottom[1] ;
	$right = $right_top[0] ;
	$top = $right_top[1] ;
	$grid_width = ($right - $left) / $GRID_SIZE;
	$grid_height = ($top - $bottom) / $GRID_SIZE;
	$BBOXES = array();
	$cnt = 0;
	for( $i= 0; $i <$GRID_SIZE; $i++ )
	{
		$BBOXES[$cnt] = $left. " ". $bottom. ",". ($left + $grid_width). " ". ($bottom + $grid_height);
		$str.= '1_'. $left. ",". $bottom. ";1_". ($left + $grid_width). ",". ($bottom + $grid_height).";";
		for( $j= 0; $j <$GRID_SIZE -1; $j++ )
		{
			$left = $left + $grid_width;
			$cnt++;
			$BBOXES[$cnt] = $left. " ". $bottom. ",". ($left + $grid_width). " ". ($bottom + $grid_height);
			$str.= '1_'. $left. ",". $bottom. ";1_". ($left + $grid_width). ",". ($bottom + $grid_height).";";

		}
		$cnt++;
		$left = $left - ($grid_width * ($GRID_SIZE - 1));
		$bottom = $bottom + $grid_height;

	}
	if ($GRID_SIZE == 2) {
	}else{
	}
	return $BBOXES;
}

/*
Get values for specific columns from meta tables for layer or linktable.
Parameters:
1. the tablename or id in meta table
2. table type
3. array of column names
*/
function get_values_metatable($tablename, $id, $table_type, $columns_arr) {
  array_walk($columns_arr, "doubleQuoteString");
  $columns = implode(",", $columns_arr);

  $meta_table = "";
  $id_col = "";
  $tablename_col = "";
  $table_type = strtoupper($table_type);
  switch($table_type) {
    case TABLE_TYPE_LAYER:
      $meta_table = "Meta_Layer";
      $id_col = "layer_id";
      $tablename_col = "layer_tablename";
      break;
    case TABLE_TYPE_LINK:
      $meta_table = "Meta_LinkTable";
      $id_col = "id";
      $tablename_col = "link_tablename";
      break;
    default:
      break;
  }
  $query = "SELECT {$columns} FROM \"{$meta_table}\" WHERE  ";
  if($tablename == "") {
    $query .= " {$id_col} = {$layer_id}";
  } else {
    $query .= "{$tablename_col} = '{$tablename}'";
  }
  $result = db_fetch_array(db_query($query));
  if(!$result) {
  } else {
    $metainfo = $result;
  }
  return $metainfo;
}

function getCCLicenseHTMLForSummary($layer_license, $size = 'small') {
  if($layer_license == "") {
    return "";
  } else {
    $query = "select count(code) as cnt from licenses where code = '%s'";
    $result = db_query($query, $layer_license);
    $cnt = 0;
    if($result) {
      $obj = db_fetch_object($result);
      $cnt = $obj->cnt;
    }
    if($cnt == 0) {
      return $layer_license;
    } else {
      if($size == 'large') {
        $img_size = "88x31";
      } else {
        $img_size = "80x15";
      }
      $lcode = $layer_license;
      $lcode = str_replace("(", "", $lcode);
      $lcode = str_replace(")", "", $lcode);
      $license[0] = $lcode;
      $license[1] = $img_size;
      return $license;
    }
  }
}

function userHasEditLayerDataPerm($layer_tablename, $row_id) {
  $user = $GLOBALS['user'];
  if($user->uid) {

    if(in_array(SITE_ADMIN_ROLE, $user->roles)) {
      return TRUE;
    }

    $user_role = getUserRoleForLayer($layer_tablename);
    $for_role = $layer_tablename . ' ' . $user_role;
    $arr_perms = getRoleMLOCATEPerms($for_role);
    if(in_array("edit any feature", $arr_perms)) {
      return TRUE;
    } elseif(in_array("edit own feature", $arr_perms)) {
      $query = 'SELECT '.AUTO_DBCOL_PREFIX.'created_by FROM "%s" WHERE '.AUTO_DBCOL_PREFIX.'id = %d';
      $result = db_query($query, $layer_tablename, $row_id);
      if(!$result) {
        return FALSE;
      } else {
        $obj = db_fetch_object($result);
        if($user->uid == $obj->{AUTO_DBCOL_PREFIX.'created_by'}) {
          return TRUE;
        } else {
          return FALSE;
        }
      }
    }
  }
  return FALSE;
}

function getUserRoleForLayer($layer_tablename) {
  $user = $GLOBALS['user'];
  $user_roles = $user->roles;
  $layer_roles = array("admin", "member", "validator");
  if($user->uid) {
    if(in_array(SITE_ADMIN_ROLE, $user_roles)) {
      return "admin";
    }
    foreach($layer_roles as $layer_role) {
      $role = $layer_tablename . " " . $layer_role;
      if(in_array($role, $user_roles)) {
        return $layer_role;
      }
    }
    $participation_type = getLayerParticipationType($layer_tablename);
    if($participation_type == 2 || $participation_type == 3) {
      return "member";
    }
  }
  return "";
}

function getLayerParticipationType($layer_tablename) {
  $participation_type = 0;
  $query = "select participation_type from \"Meta_Layer\" where layer_tablename = '%s'";
  $result = db_query($query, $layer_tablename);
  if(!$result) {
  } else {
    $obj = db_fetch_object($result);
    $participation_type = $obj->participation_type;
  }
  return $participation_type;
}

function checkIfAdministrator()
{
	$user = $GLOBALS['user'];
	$user_id = $user>uid;
	$flag = FALSE;
	if ($user_id > 0)
	{
		$user_roles = $user->roles;
		if(in_array(SITE_ADMIN_ROLE, $user_roles))
		{
		  $flag = TRUE;
		}
	}
	return $flag;
}

function checkIfAuthorisedUser()
{
	$user = $GLOBALS['user'];
	$user_id = $user->uid;
	if ($user_id > 0)
	{
		return TRUE;
	}
	else
	{
		return FALSE;
	}
}

function notify_admin_of_layer_updation($layer_name , $operation , $city )
{
  global $user;
  $username = $user->name . "(" . $user->mail . ")";

  $body = "";
  switch($operation ) {
    case 'insert':
      $subject = "Layer added";
      $body = "Layer is added in the {$city}";
      break;
    case 'delete':
      $subject = "{$layer_name} deleted";
      $body = "Layer named '{$layer_name}' from {$city} is deleted";
      break;
  }

  // Get the layer admins email IDs.
  $query = "select mail from users where uid in (select uid from users_roles where rid in (select rid from role where name = '%s admin' OR name = '%s validator' OR name = '".SITE_ADMIN_ROLE."'));";
  $result = db_query($query, $layer_tablename);
  if(!$result) {
    return FALSE;
  } else {
    $admin_ids = array();
    while($obj = db_fetch_object($result)) {
      $admin_ids[] = $obj->mail;
    }

    if(sizeof($admin_ids) == 0) {
      return FALSE;
    } else {
      $to = implode(",", $admin_ids);
      if(!in_array($user->mail,$admin_ids)){
         //user mail id not present in the list already
         $to.= ",".$user->mail;
      }
    }
  }

  $default_from = variable_get('site_mail', ini_get('sendmail_from'));

  // Additional headers
  $headers['To'] = $to;

  // Bundle up the variables into a structured array for altering.
  $message = array(
    'to'       => $to,
    'from'     => isset($from) ? $from : $default_from,
    'subject'  => $subject,
    'body'     => $body,
    'headers'  => $headers
  );
  drupal_mail_send($message);
}

function getReadMoreDrupalNodeTeaser($nid, $len = 200) {
  $teaser = '';
  if($nid) {
    $node = node_load($nid);
    $teaser = $node->teaser;

    $newlines   = array("\r\n", "\n", "\r");
    $teaser = trim(str_replace($newlines, ' ', $teaser));

    if(strlen($teaser) > $len) {
      $teaser = substr($teaser, 0, $len - 3) . "...";
    }
    $teaser .= ' <font color="#0000FF"><i><a href="'.base_path().'node/'.$nid.'" target="_blank">(read more...)</a></i></font>';
  }
  return $teaser;
}

function is_numeric_array($array) {
  $r = false;
  if (is_array($array)) {
    foreach($array as $n=>$v) {
      if (is_array( $array[$n] )) {
        $r = is_numeric_array( $array[$n] );
        if ($r==false) break;
      } else {
        if (!is_numeric($v)) {
          $r = false;
          break;
        } else {
          $r = true;
        }
      }
    }
  }
  return $r;
}

function is_range_array($array) {
  $r = false;
  if (is_array($array)) {
    foreach($array as $n=>$v) {
      if (is_array( $array[$n] )) {
        $r = is_range_array( $array[$n] );
        if ($r==false) break;
      } else {
        $vals = explode("-", $v);
        foreach($vals as $v) {
          if (!is_numeric($v)) {
            $r = false;
            break;
          } else {
            $r = true;
          }
        }
      }
    }
  }
  return $r;
}

function rangearray_multisort(&$arr1, &$arr2) {
  $count = count($arr1);
  $seqarr = range(0, $count - 1);
  array_multisort($arr1, $seqarr);
  $arr3 = array();
  foreach ($seqarr as $seq) {
    $arr3[] = $arr2[$seq];
  }
  $arr2 = $arr3;
}
?>
