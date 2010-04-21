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
Serves out required icon in the required color and border.
First it checks for layer specific icon, if not then theme specfic else default icon
**/
require_once './includes/bootstrap.inc';

drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Global variables
$step = 50;
$iconDirectoryPath = './'.path_to_theme().'/images/icons/';
$existingFill = hex2int("00ff00");
$existingDarkBorder = hex2int("474747");
$existingLightBorder = hex2int("B4B4B4");
$fill = hex2int("53E26E");
$lightBorder = hex2int("B4B4B4");
$darkBorder = hex2int("474747");
$themeName = "";
$layerName = "";
$iconName = './'.path_to_theme().'/images/proto.png';

if($_GET["theme"] != "") {
	//Since theme name is not available at the client side currently layertablename is passed.
	$layer = $_GET["theme"];

	//Check first if there is a specific icon for the layer present
	$layericonarr = split("_",$layer,3);

	$layericon = $layericonarr[2]. ".png";

	if( !file_exists($iconDirectoryPath . $layericon )) {
		$layericon = "";
	}
	else{
		$iconName = $layericon ;
	}

	if ($layericon == "") {

		$query = "SELECT icon FROM \"Theme\" where theme_type = 1 and status = 1 and theme_id IN ( select theme_id from \"Theme_Layer_Mapping\" where layer_id =(select layer_id from \"Meta_Layer\" where layer_tablename like '%s'))";
		$result_db = db_query($query, array($layer));
		while ($data = db_fetch_object($result_db)){
		$iconName = $data->icon . ".png";
		}
	}
}


elseif($_GET["layer"] != "") {
	$iconName = $_GET["layer"] . ".png";
}
elseif($_GET["icon"] != "") {
	$iconName = $_GET["icon"] . ".png";
}

if(! file_exists($iconDirectoryPath . $iconName)) {
	$iconName = "proto.png";
}

if($_GET["fill"] != "") {
	$fill = hex2int($_GET["fill"]);
}

// Compute the dark and light shades
$darkBorder['r'] = $fill['r'] - $step;
if($darkBorderBorder['r'] < 0){
	$darkBorder['r'] = 0;
}
$darkBorder['g'] = $fill['g'] - $step;
if($darkBorderBorder['g'] < 0){
	$darkBorder['g'] = 0;
}
$darkBorder['b'] = $fill['b'] - $step;
if($darkBorderBorder['b'] < 0){
	$darkBorder['b'] = 0;
}

$lightBorder['r'] = $fill['r'] + $step;
if($lightBorder['r'] > 255){
	$lightBorder['r'] = 255;
}

$lightBorder['g'] = $fill['g'] + $step;
if($lightBorder['g'] > 255){
	$lightBorder['g'] = 255;
}

$lightBorder['b'] = $fill['b'] + $step;
if($lightBorder['b'] > 255){
	$lightBorder['b'] = 255;
}

$image = imageCreateFromPNG($iconDirectoryPath . $iconName);
$phpFillIndex = imageColorClosest($image,$existingFill['r'],$existingFill['g'],$existingFill['b']);
imageColorSet($image,$phpFillIndex,$fill['r'],$fill['g'],$fill['b']);
$phpDarkBorderIndex = imageColorClosest($image,$existingDarkBorder['r'],$existingDarkBorder['g'],$existingDarkBorder['b']);
imageColorSet($image,$phpDarkBorderIndex,$darkBorder['r'],$darkBorder['g'],$darkBorder['b']);
$phpLightBorderIndex = imageColorClosest($image,$existingLightBorder['r'],$existingLightBorder['g'],$existingLightBorder['b']);
imageColorSet($image,$phpLightBorderIndex,$lightBorder['r'],$lightBorder['g'],$lightBorder['b']);

header('Content-type: image/png');
imagePNG($image);
imageDestroy($image);


/**
 * @param    $hex string        6-digit hexadecimal color
 * @return    array            3 elements 'r', 'g', & 'b' = int color values
 * @desc Converts a 6 digit hexadecimal number into an array of
 *       3 integer values ('r'  => red value, 'g'  => green, 'b'  => blue)
 */
function hex2int($hex) {
        return array( 'r' => hexdec(substr($hex, 0, 2)), // 1st pair of digits
                      'g' => hexdec(substr($hex, 2, 2)), // 2nd pair
                      'b' => hexdec(substr($hex, 4, 2))  // 3rd pair
                    );
}

/**
 * @param $input string     6-digit hexadecimal string to be validated
 * @param $default string   default color to be returned if $input isn't valid
 * @return string           the validated 6-digit hexadecimal color
 * @desc returns $input if it is a valid hexadecimal color,
 *       otherwise returns $default (which defaults to black)
 */
function validHexColor($input = '000000', $default = '000000') {
    // A valid Hexadecimal color is exactly 6 characters long
    // and eigher a digit or letter from a to f
    return (eregi('^[0-9a-f]{6}$', $input)) ? $input : $default ;
}
?>
