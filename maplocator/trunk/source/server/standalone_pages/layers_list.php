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
* This is a stand alone page which displays a list of all layers available in the system.
*
***/

require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

$sql = "SELECT layer_name,layer_tablename,layer_description, access from \"Meta_Layer\" where status = 1 order by layer_name";
$data = db_query($sql);
$layer_table='';
$layer_name='';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
	<head>
		<title>Layers | India Biodiversity Portal</title>
		<meta name="keywords" CONTENT="India,Biodiversity,map,science,conservation,society,mammals,birds,natural resources">
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
			<h2>India Biodiversity Portal: Layer List</h2>
			<?php $strurlList=''; while($layer_obj = db_fetch_object($data)): ?>

			<?php
			$layer_table = $layer_obj->layer_tablename;
      		$layer_name = $layer_obj->layer_name;
      		$strurlList .= "http://www.indiabiodiversity.org/layer_info.php?layer_name=" .$layer_table." changefreq=weekly priority=0.9 \n";
			?>

			<h3>
        <a href="layer_info.php?layer_name=<?php print str_replace(" ","%20" ,$layer_table)?>"><?php print str_replace(" ","&nbsp;" ,$layer_name)?></a>
        <?php
          if($layer_obj->access) {
            echo '<img src="'.base_path().path_to_theme().'/images/icons/download-layertree.png" alt="Downloadable Layer" title="Downloadable Layer"></img>';
          }
        ?>
      </h3>
			<p>
			<?php print $layer_obj->layer_description; ?>
			</p>
			<?php endwhile;
				$strurlList = substr_replace($strurlList,"",-1);
				$fh = fopen('urllist.txt', 'w') or die("can't open file");
				fwrite($fh, $strurlList);
				fclose($fh);

			?>

		</div>
	</body>

</html>
