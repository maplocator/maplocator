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
This php script is an utility script to merge two images.
Used to overlay one image on other for printing in pdf.
Called/invoked fro printPDF.php
*/

/*The header line informs the server of what to send the output
as. In this case, the server will see the output as a .jpeg
image and send it as such*/
header ("Content-type: image/jpeg");

// Defining the background image.
$base_image = $_REQUEST['baseimage'];
if (get_magic_quotes_gpc()) {
   $base_image = stripslashes($base_image);
}
$background = imagecreatefromjpeg($base_image);

// Defining the overlay image to be added or combined.
$layer = $_REQUEST['overlayimage'];
if (get_magic_quotes_gpc()) {
   $layer = stripslashes($layer);
}
$overlay= imagecreatefromjpeg($layer);

/*Select the first pixel of the overlay image (at 0,0) and use
it's color to define the transparent color*/

imagecolortransparent($overlay,imagecolorat($overlay,0,0));

// Get overlay image width and hight for later use

$insert_x = imagesx($overlay);
$insert_y = imagesy($overlay);

/*Combine the images into a single output image. Some people
prefer to use the imagecopy() function, but more often than
not, it sometimes does not work. (could be a bug)*/

imagecopymerge($background,$overlay,0,0,0,0,$insert_x,$insert_y,100);

/*Output the results as a jpeg image, to be sent to viewer's
browser. The results can be displayed within an HTML document
as an image tag or background image for the document, tables,
or anywhere an image URL may be acceptable.*/

imagejpeg($background,"",100);

?>
