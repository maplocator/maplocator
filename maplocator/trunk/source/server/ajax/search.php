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
This files includes functionality related to search like fetch features to be validated , fetch features for
time line data etc.
**/
require_once 'functions.php';

function getLayerTablenames($layer_names){
	$query = "SELECT layer_tablename from \"Meta_Layer\" where layer_name IN (%s)" ;
	$data = db_query($query, $layer_names);
	$layer_table='';
	while ($layer_obj = db_fetch_object($data)) {
		$layer_table .= $layer_obj->layer_tablename . ',';
	}
	$layer_table = substr_replace($layer_table,"",-1);
	return $layer_table;
}
function getValidationData(){
	  $layers = GetLayerTableNamesForValidation();
	  $query = '';

	  $json = array();
	  $json_indx = 0;

	  if ($layers !='')
	  {
	    if ($layers == 'ALL')
		{
	      $query = "SELECT layer_tablename, layer_name,participation_type,p_nid  FROM \"Meta_Layer\" where participation_type > 0" ;
	    }
		else
		{
	      $query = "SELECT layer_tablename, layer_name,participation_type,p_nid  FROM \"Meta_Layer\" where layer_tablename IN ({$layers})" ;
	    }
	    $data = db_query($query);
	    while ($layer_obj = db_fetch_object($data))
		{
		  $query = 'select '.AUTO_DBCOL_PREFIX.'id from "%s" where '.AUTO_DBCOL_PREFIX.'status is null';
	      $dbdata = db_query($query, $layer_obj->layer_tablename);
	      $count =0;
	      $fids ='';
	      while ($obj = db_fetch_object($dbdata))
		  {
	        $fids .= $obj->{AUTO_DBCOL_PREFIX.'id'} . ',';
	        $count++;
	      }
	      if ($count > 0) {
	        $fids = substr_replace($fids,"",-1);
			$json[$json_indx++] = treeViewEntryJSON($layer_obj->layer_tablename, $layer_obj->layer_name, $layer_obj->participation_type, $layer_obj->p_nid,null, $fids, $count);
	      }
	    }
		if ($json_indx == 0)
		{
			$json[0] = "TEXT_RESP";
			$json[1] = 'All the features in the accessible layers are already validated';
		}
	  }
	  else
	  {
		$json[0] = "TEXT_RESP";
		$json[1] = 'The current user does not have permissions to validate any of the available layers';
	  }
	  $encoded = json_encode($json);
	  return $encoded;

}
function getFeaturesForTimeLine($layer_tablename,$tlCol,$tlStartDate,$tlEndDate,$BBOX){
	$BBOXES = GenerateBBoxes($BBOX,10);
	$cnt = count($BBOXES);
	$fids = array();
	for ($i=0; $i<$cnt; $i++) {
    	$query = "SELECT ".AUTO_DBCOL_PREFIX."id FROM \"%s\" where ".AUTO_DBCOL_PREFIX."topology && setSRID('BOX3D(%s)'::box3d, (select srid from geometry_columns where f_table_name = '%s')) AND %s between '%s' and '%s'";
    	$query_args = array($layer_tablename, $BBOXES[$i], $layer_tablename, $tlCol, $tlStartDate, $tlEndDate);
    	$result_db = db_query($query, $query_args);
    	while ($data = db_fetch_object($result_db)) {
      		$fids[] = $data->{AUTO_DBCOL_PREFIX.'id'};
    	}
  	}
  	return implode(",", $fids);

}

/* not sure whether this code is used or not
else // its a search request
{
	$q = $_GET['param'];
	$q = strtolower($q);
	$srch_data = '';

	$like_codition = '';
	$srch_data = trim($q);

	if (strpos($srch_data, '"') === false)
	{
			$srch_arr = explode(' ',$srch_data);
			$len = count($srch_arr);

			for($i=0; $i< $len; $i++)
			{
				if ($i == $len -1 ) {
					$like_codition.= " '%%" .$srch_arr[$i] ."%%'";
				}
				else {
					$like_codition.=  " '%%" . $srch_arr[$i]. "%%' OR text like";
				}
			}
	}
	else {
		$like_codition = "'%". str_replace('"','',$srch_data) ."%'";
	}

	$query = "SELECT DISTINCT(fid),lid  FROM search_data where text like {$like_codition} GROUP BY lid,fid";

	$features = db_query($query);
	$prevlid='';
	$currentlid='';
	$fids ='';

	$count = 0;

	// json variable
	$json = array();
	$json_indx = 0;

	//db_fetch_object
	while ($record = db_fetch_array($features))
	{
		if ($prevlid == '') {
			$prevlid = $record['lid'];
		}
		$currentlid = $record['lid'];

		if ($currentlid != $prevlid)
		{
			$fids = substr_replace($fids,"",-1);
			$query = "SELECT layer_tablename, layer_name,participation_type,p_nid  FROM \"Meta_Layer\" where layer_id = %d";
			$data = db_query($query, $prevlid);
			while ($layer_obj = db_fetch_object($data))
			{
				// go on adding new array index for each record
				$json[$json_indx++] = treeViewEntryJSON($layer_obj->layer_tablename, $layer_obj->layer_name, $layer_obj->participation_type, $layer_obj->p_nid,null, $fids, $count);
			}
			$fids = '';
			$fids = $record['fid']. ",";
			$prevlid = $currentlid;
			$count = 1;
		}
		else
		{
			$fids.= $record['fid']. ",";
			$count++;
		}
	}
	//The above loop skips the last record
	if ($prevlid == $currentlid)
	{
			$query = "SELECT layer_tablename, layer_name,participation_type,p_nid  FROM \"Meta_Layer\" where layer_id = %d";
			$data = db_query($query, $currentlid);
			$fids = substr_replace($fids,"",-1);
			while ($layer_obj = db_fetch_object($data))
			{
				$json[$json_indx++] = treeViewEntryJSON($layer_obj->layer_tablename, $layer_obj->layer_name, $layer_obj->participation_type, $layer_obj->p_nid,null, $fids, $count);
			}
	}

	$encoded = json_encode($json);
	echo $encoded;
}
*/
?>
