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
 This acts like a pre processor, executed before loading the theme
***/
function phptemplate_preprocess_page(&$vars) {
    include_once("readConfig.php");
    $config = readConfig();
    $vars['scripts'] = $config['scripts'] . $vars['scripts'];
    $vars['styles'] = $config['styles'] . $vars['styles'];

    $suggestions = array();
    if (module_exists('path')) {
        $alias = drupal_get_path_alias(str_replace('/edit','',$_GET['q']));

        if(stristr($alias, "layer/")) {
            $suggestions = array();
            $suggestions[] = "page-layer";
            $vars['template_files'] = $suggestions;
        }

        if($alias == "map") {
            $suggestions = array();
            $suggestions[] = "page-map";
            $vars['template_files'] = $suggestions;
        }
    }
}
