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
* This is a stand alone page which displays information for a layer
*
***/
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
$q = $_GET['layer_name'];
$sql = "SELECT layer_tablename,layer_name,layer_description,pdf_link,url,tags,comments,nid from \"Meta_Layer\" where layer_tablename = '%s'" ;
$data = db_query($sql, array($q));
$layer_table='';
$layer_name='';
$layer_description='';
$pdf_link='';
$layer_url='';
$layer_tags='';
$layer_comments='';
$layer_node='';
$str='';

if($layer_obj = db_fetch_object($data)){
	$layer_table = $layer_obj->layer_tablename;
	$layer_name = $layer_obj->layer_name;
	$layer_description = $layer_obj->layer_description;
	$layer_tags = $layer_obj->tags;
	$pdf_link = $layer_obj->pdf_link;
	$layer_url = $layer_obj->url;
	$layer_comments = $layer_obj->comments;
	$layer_node = $layer_obj->nid;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
	<head>
		<title>Layer: <?php print $layer_name?> | India Biodiversity Portal</title>
		<meta name="keywords" CONTENT="<?php print $layer_tags;?>"/>
		<meta name="description" CONTENT="<?php print $layer_description?>"/>
		<style type="text/css">
			body {
				background-color: #ECE9B7;
				font-family: Verdana, Helvetica, Sans-Serif;
				font-size: 10pt;
				color: #A75030;
			}

			h2 {
				border-bottom: 1px solid #A75030;
			}

			#wrapper {
				margin: 10px;
				padding: 10px;
				background-color: #fff;
				border: 1px solid #A75030;
			}

			h3 {
				padding; 0;
				margin: 0;
			}

			p {
				border-bottom: 1px solid #ECE9B7;
			}

			a:link {
				color: #BF8815;
				text-decoration: underline;
			}

			a:visited {
				color: #DBA32E;
				text-decoration: underline;
			}

			a:hover {
				color: #BF8815;
				text-decoration: underline;
			}

			a:active {
				color: #BF8815;
				text-decoration: underline;
			}

		</style>
	</head>
	<body>
		<div id="wrapper">
			<h2>India Biodiversity Portal</h2>

			<h3><?php print $layer_name?></h3><a href="<?php print base_path().'map?layername='.str_replace(" ","%20" ,$layer_table) ?>">Show on map </a>

			<p>
			<strong>Description</strong> <?php print $layer_description;?>
			</p>

			<p>
			<strong>PDF Link</strong> <?php print $pdf_link;?>
			</p>

			<p>
			<strong>URL</strong> <?php print $layer_url; ?>
			</p>

			<p>
			<strong>Tags</strong> <?php print $layer_tags; ?>
			</p>

			<p>
			<strong>Comments</strong> <?php print $layer_comments; ?>
			</p>

			<?php if ($layer_node > 0): ?>
				<iframe src="<?php print base_path(); ?>node/<?php print $layer_node; ?>/popup" width="100%" height = "75%"> </iframe>
			<?php endif; ?>

		</div>
	</body>

</html>
