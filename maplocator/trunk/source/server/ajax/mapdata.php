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

/*
This file includes functionality to fetch point layer(points), Data on Demand and util functions for layer
*/
require_once './includes/bootstrap.inc';
require_once 'functions.php';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);



global $user;

function return_error($msg) {
    $err = <<<END
<?xml version="1.0"?>
<mapdata>
<error value='true'>
<message>
END;
    $err .= $msg;
    $err .= <<< END
</message>
</error>
</mapdata>
END;
    return $err;
}

function GetMedianPoint($List)
{
	$len = count($List);
	$X= 0;
	$Y= 0;

	for($i=0;$i<$len; $i++)
	{
		$arr = split(",",$List[$i]);
		$X = ( $X + $arr[0] );
		$Y = ( $Y + $arr[1]);
	}
	$median_x = $X / $len;
	$median_y = $Y / $len;
	return $median_x. ",". $median_y. ";";
}
function getMapPoints($table,$BBOX,$search_ids,$tl_col,$tlStartDate,$tlEndDate){
	$BBOXES = GenerateBBoxes($BBOX,10);
	$cnt = count($BBOXES);
	$pointlist = array();
	for ($i=0; $i<$cnt; $i++) {
		if ($search_ids != '') {
			$query = "SELECT ".AUTO_DBCOL_PREFIX."id,astext(".AUTO_DBCOL_PREFIX."topology) as location FROM \"%s\" where ".AUTO_DBCOL_PREFIX."topology && setSRID('BOX3D(%s)'::box3d, (select srid from geometry_columns where f_table_name = '%s')) AND ".AUTO_DBCOL_PREFIX."id in(%s) ";
      $query_args = array($table, $BBOXES[$i], $table, $search_ids);
		} else {
      if($tl_col != '') {



        $query = "SELECT ".AUTO_DBCOL_PREFIX."id,astext(".AUTO_DBCOL_PREFIX."topology) as location FROM \"%s\" where ".AUTO_DBCOL_PREFIX."topology && setSRID('BOX3D(%s)'::box3d, (select srid from geometry_columns where f_table_name = '%s')) AND %s between '%s' and '%s'";
        $query_args = array($table, $BBOXES[$i], $table, $tlCol, $tlStartDate, $tlEndDate);
      } else {
        $query = "SELECT ".AUTO_DBCOL_PREFIX."id,astext(".AUTO_DBCOL_PREFIX."topology) as location FROM \"%s\" where ".AUTO_DBCOL_PREFIX."topology && setSRID('BOX3D(%s)'::box3d, (select srid from geometry_columns where f_table_name = '%s'))";
        $query_args = array($table, $BBOXES[$i], $table);
      }
		}

		$result_db = db_query($query, $query_args);
		$no =0;
		$point ='';
		$loc='';
		while ($user_data = db_fetch_object($result_db))
		{
				$id= 'REAL|'.$user_data->{AUTO_DBCOL_PREFIX.'id'}.'_';
				$str = $id;
				$loc='';
				$loc = $user_data->location;
				$loc = str_replace('MULTIPOINT(','',$loc);
				$loc = str_replace('POINT(','',$loc);
				$loc = str_replace(' ', ',', $loc);
				$loc = str_replace(')', ';', $loc);
				$str .= $loc;
				$point .= $str;
				$no++;
		}

		if ($no < 5)
		{

			print_r($point);
		}
		else
		{
			$point ='';
			$BBOX = $BBOXES[$i];
			//Do one more iteration
			$NewBBOXES = GenerateBBoxes($BBOX,2);

			$counter = count($NewBBOXES);
			$Newpointlist = array();
			for ($j=0; $j<$counter; $j++) {

				if($search_ids != '') {

          $query = "SELECT ".AUTO_DBCOL_PREFIX."id,astext(".AUTO_DBCOL_PREFIX."topology) as location FROM \"%s\" where ".AUTO_DBCOL_PREFIX."topology && setSRID('BOX3D(%s)'::box3d, (select srid from geometry_columns where f_table_name = '%s')) AND ".AUTO_DBCOL_PREFIX."id in(%s)";
          $query_args = array($table, $NewBBOXES[$j], $table, $search_ids);
				} else {

          $query = "SELECT ".AUTO_DBCOL_PREFIX."id,astext(".AUTO_DBCOL_PREFIX."topology) as location FROM \"%s\" where ".AUTO_DBCOL_PREFIX."topology && setSRID('BOX3D(%s)'::box3d, (select srid from geometry_columns where f_table_name = '%s'))";
          $query_args = array($table, $NewBBOXES[$j], $table);
				}

				$newresult_db = db_query($query, $query_args);
				$newno =0;
				$newpoint ='';
				$newloc='';
				while ($newuser_data = db_fetch_object($newresult_db))
				{

					    $newid= 'REAL|'.$newuser_data->{AUTO_DBCOL_PREFIX.'id'}.'_';
						$newstr = $newid;
						$newloc='';
						$newloc = $newuser_data->location;
						$newloc = str_replace('MULTIPOINT(','',$newloc);
						$newloc = str_replace('POINT(','',$newloc);
						$newloc = str_replace(' ', ',', $newloc);
						$newloc = str_replace(')', ';', $newloc);
						$newstr .= $newloc;
						$newpoint .= $newstr;
						$Newpointlist[$newno] = $newloc;
						$newno++;
				}
				if ($newno < 4)
				{
					print_r($newpoint);

				}
				else
				{

					$newpoint = '';
					$newpoint.= "VIRTUAL|".$newno. "!". $NewBBOXES[$j];
					$newpoint.= "_". GetMedianPoint($Newpointlist);
					print_r($newpoint);
				}

			}
		}
	}

}
function getDOD($table,$fids){
	$col_info = getDBColDesc($table);

	$query = "SELECT *,astext(Centroid(".AUTO_DBCOL_PREFIX."topology)) as centroid  FROM \"%s\" where ".AUTO_DBCOL_PREFIX."id IN(%s)";
    $query_args = array($table, $fids);
	$result_db = db_query($query, $query_args);
	$j=0;
	$str = '';
	echo '<table id="dataPresentation">';
	while ($obj = db_fetch_object($result_db))
	{
		if($j==0)
		{
			echo '<thead align=center>';
			$firstrow ='<tr id="tbl_cols" align=center onClick=HighlightFeature(this,\'#c9cc99\',\''. $table .'\'); >';
			foreach($obj as $key => $value)
			{
				switch($key)
				{
			   		case AUTO_DBCOL_PREFIX.'topology' :
						break;
					case AUTO_DBCOL_PREFIX.'created_by':
						break;
					case AUTO_DBCOL_PREFIX.'modified_by':
						break;
					case AUTO_DBCOL_PREFIX.'layer_id' :
							break;
					case AUTO_DBCOL_PREFIX.'validated_by':
							break;
					case AUTO_DBCOL_PREFIX.'validated_date':
							break;
					case AUTO_DBCOL_PREFIX.'status' :
							break;
					case AUTO_DBCOL_PREFIX.'topology' :
						break;
					case AUTO_DBCOL_PREFIX.'nid':
						break;
					case AUTO_DBCOL_PREFIX.'created_date':
						break;
					case AUTO_DBCOL_PREFIX.'modified_date':
						break;
					case AUTO_DBCOL_PREFIX.'id':
						echo '<th class ="hidecol" align=center>'.($col_info[$key] == "" ? str_replace(" ", "&nbsp;", $key) : str_replace(" ", "&nbsp;",$col_info[$key])).'</th>';
						$firstrow.= '<td align=center>'.str_replace(" ","&nbsp;" ,$value ).'</td>';
						break;
					case'centroid':
						echo '<th class ="hidecol" align=center>'.($col_info[$key] == "" ? str_replace(" ", "&nbsp;", $key) : str_replace(" ", "&nbsp;",$col_info[$key])).'</th>';
						$firstrow.= '<td align=center>'.str_replace(" ","&nbsp;" ,$value ).'</td>';
						break;
					default:
	    				echo '<th align=center>'.($col_info[$key] == "" ? str_replace(" ", "&nbsp;", $key) : str_replace(" ", "&nbsp;",$col_info[$key])).'</th>';
	    				$firstrow.= '<td align=center>'.str_replace(" ","&nbsp;" ,$value ).'</td>';
						break;
			  	}


			}
			$firstrow .= '</tr>';
			echo '</thead>';
			echo $firstrow;
			$j += 1;
		}
		else
		{
			$str.= '<tr align=center onClick=HighlightFeature(this,\'#c9cc99\',\''. $table .'\'); >';
			foreach($obj as $key => $value)
			{
				switch($key)
				{
			    	case AUTO_DBCOL_PREFIX.'created_by':
								break;
					case AUTO_DBCOL_PREFIX.'modified_by':
								break;
					case AUTO_DBCOL_PREFIX.'topology':
								break;
					case AUTO_DBCOL_PREFIX.'layer_id' :
							break;
					case AUTO_DBCOL_PREFIX.'validated_by':
							break;
					case AUTO_DBCOL_PREFIX.'validated_date':
							break;
					case AUTO_DBCOL_PREFIX.'status' :
							break;
					case 'topology' :
								break;
					case AUTO_DBCOL_PREFIX.'nid':
								break;
					case AUTO_DBCOL_PREFIX.'created_date':
								break;
					case AUTO_DBCOL_PREFIX.'modified_date':
								break;
					default:
			    				$str.= '<td align=center>'.str_replace(" ","&nbsp;" ,$value ).'</td>';
								break;
				}
			}
			$str.= '</tr>';
			print_r($str);
			$str='';
		}
	}
	echo '</table>';

}
function getMaxZoomLevel($table){
	$query = "SELECT max_scale  from \"Meta_Layer\" where layer_tablename like '%s'";
	$result_db = db_query($query, $table);

	$max_zoom='';
	while ($user_data = db_fetch_object($result_db)) {
		$max_zoom = $user_data->max_scale;
	}
	if ($max_zoom == NULL || $max_zoom == "" || $max_zoom < 5) {
		$max_zoom = 19;
	}
	return $max_zoom;
}
function getLayerType($table){
	$layertype = '';
	$query = 'select layer_type from "Meta_Layer" where layer_tablename= \'%s\'';
	$result = db_query($query, $table);
  	while($row = db_fetch_object($result)) {
    	$layertype = $row->layer_type;
  	}
	return $layertype;

}
function getFeatureCountForLayer($table){
	$query = 'SELECT count(*) as count from "%s"';
	$result_db = db_query($query, $table);
	$count = '';
	while ($user_data = db_fetch_object($result_db)) {
		$count = $user_data->count;
	}
	return $count;
}
function getDODCount($table,$BBOX,$fids){
	if ($fids != '') {
		$query = 'SELECT count(*) FROM "%s" where '.AUTO_DBCOL_PREFIX.'id IN(%s)';
    $query_args = array($table, $fids);
	} else {
		$query = "SELECT count(*) as count FROM \"%s\" where ".AUTO_DBCOL_PREFIX."topology && setSRID('BOX3D(%s)'::box3d, (select srid from geometry_columns where f_table_name = '%s')) ";
    $query_args = array($table, $BBOX, $table);
	}
	$result_db = db_query($query, $query_args);
	if($obj = db_fetch_object($result_db)){
		return $obj->count;
	}
}
function getDODForPolygon($table,$BBOX){
	$col_info = getDBColDesc($table);
	echo '<table id="dataPresentation">';
	$j=0;
	$str='';

	$query = "SELECT *,astext(ST_PointOnSurface(".AUTO_DBCOL_PREFIX."topology)) as centroid FROM \"%s\" where ".AUTO_DBCOL_PREFIX."topology && setSRID('BOX3D(%s)'::box3d, (select srid from geometry_columns where f_table_name = '%s')) ";
  $query_args = array($table, $BBOX, $table);

	$result_db = db_query($query, $query_args);

	while ($obj = db_fetch_object($result_db))
	{
		if($j==0)
		{
				echo '<thead align=center>';
				$firstrow ='<tr id="tbl_cols" align=center onClick=HighlightFeature(this,\'#c9cc99\',\''. $table .'\'); >';
				foreach($obj as $key => $value)
				{
					switch($key)
					{
			    		case AUTO_DBCOL_PREFIX.'topology' :
							break;
						case AUTO_DBCOL_PREFIX.'created_by':
							break;
						case AUTO_DBCOL_PREFIX.'modified_by':
							break;
						case AUTO_DBCOL_PREFIX.'layer_id' :

	    					break;
						case AUTO_DBCOL_PREFIX.'validated_by':
							break;
						case AUTO_DBCOL_PREFIX.'validated_date':
							break;
						case AUTO_DBCOL_PREFIX.'status' :
							break;
						case AUTO_DBCOL_PREFIX.'nid':
							break;
						case AUTO_DBCOL_PREFIX.'topology' :
							break;
						case AUTO_DBCOL_PREFIX.'created_date':
							break;
						case AUTO_DBCOL_PREFIX.'modified_date':
							break;
						case AUTO_DBCOL_PREFIX.'id':
							echo '<th class ="hidecol" align=center>'.($col_info[$key] == "" ? str_replace(" ", "&nbsp;", $key) : str_replace(" ", "&nbsp;",$col_info[$key])).'</th>';
							$firstrow.= '<td align=center>'.str_replace(" ","&nbsp;" ,$value ).'</td>';
							break;
						case'centroid':
							echo '<th class ="hidecol" align=center>'.($col_info[$key] == "" ? str_replace(" ", "&nbsp;", $key) : str_replace(" ", "&nbsp;",$col_info[$key])).'</th>';
							$firstrow.= '<td align=center>'.str_replace(" ","&nbsp;" ,$value ).'</td>';
							break;
						default:
	    					echo '<th align=center>'.($col_info[$key] == "" ? str_replace(" ", "&nbsp;", $key) : str_replace(" ", "&nbsp;",$col_info[$key])).'</th>';
	    					$firstrow.= '<td align=center>'.str_replace(" ","&nbsp;" ,$value ).'</td>';
							break;
			  		}


				}
				$firstrow .= '</tr>';
				echo '</thead>';
				echo $firstrow;
				$j += 1;
		}
		else
		{
				$str.= '<tr align=center onClick=HighlightFeature(this,\'#c9cc99\',\''. $table .'\'); >';
				foreach($obj as $key => $value)
				{
					switch($key)
					{
				    	case AUTO_DBCOL_PREFIX.'created_by':
							break;
						case AUTO_DBCOL_PREFIX.'modified_by':
							break;
						case AUTO_DBCOL_PREFIX.'topology':
							break;
						case AUTO_DBCOL_PREFIX.'layer_id' :
							break;
						case AUTO_DBCOL_PREFIX.'validated_by':
							break;
						case AUTO_DBCOL_PREFIX.'validated_date':
							break;
						case AUTO_DBCOL_PREFIX.'status' :
							break;
						case AUTO_DBCOL_PREFIX.'topology' :
							break;
						case AUTO_DBCOL_PREFIX.'nid':
							break;
						case AUTO_DBCOL_PREFIX.'created_date':
							break;
						case AUTO_DBCOL_PREFIX.'modified_date':
							break;
						case'centroid':
	    					$str.= '<td align=center>'.str_replace(" ","&nbsp;" ,$value ).'</td>';
	    					break;
						default:
		    				$str.= '<td align=center>'.str_replace(" ","&nbsp;" ,$value ).'</td>';
						break;
			  		}

    			}
    			$str.= '</tr>';
		}
	}
	print_r($str);
	echo '</table> ';

}

?>
