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
*This file contains functionality related theme/catgeory .
*
***/
  require_once './includes/bootstrap.inc';
  require_once 'functions.php';
  require_once 'XMLParser.php';
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

  $action = $_REQUEST['action'];

  if($action == 'getContinent')
  {
		$query = 'select distinct(continent_name) from "Country_Mapping"';
	    $result_db = db_query($query);
		$arr_result = array();
	    while ($result = db_fetch_object($result_db))
		{
			$arr_result[$result->continent_name] = $result->continent_name;
		}
		print json_encode($arr_result);
  }

  if($action == 'getCity')
	{
		$countryId = $_REQUEST['countryId'];
		$query = 'select theme_name from "Theme" where country_id = '.$countryId;
	    $result_db = db_query($query);
		$arr_result = array();
	    while ($result = db_fetch_object($result_db))
		{
			$arr_result[$result->theme_name] = $result->theme_name;
		}
		print json_encode($arr_result);
	}

	if($action == 'getCountry')
	{
		$cName = $_REQUEST['continentName'];
		$query = 'select  country_id , country_name  from "Country_Mapping" where continent_name = \''.$cName.'\'';
	    $result_db = db_query($query);
		$arr_result = array();
	    while ($result = db_fetch_object($result_db))
		{
			$arr_result[$result->country_id] = $result->country_name;
		}
		print json_encode($arr_result);
	}

	 if($action == 'getLayer')
	  {
		$themeNames = "";
		$cityName = $_REQUEST['cityName'];
		$cityThemeName = '\''.$cityName.'\'';
		$cityThemeIdquery = 'select theme_id from "Theme" where trim(theme_name) = '.$cityThemeName;
		$result_db = db_query($cityThemeIdquery);
		$result = db_fetch_object($result_db);
		$cityThemeId = $result->theme_id;

		$categories = $_REQUEST['categoryId'];
		$catNames = split(':', $categories);
		$categoryIds = "";
		$isFirst = True;
		foreach ($catNames as $category)
		{
			if($isFirst)
			{
				$categoryIds = $category;
				$isFirst = False;
			}
			else
			{
				if ($category!= "")
				$categoryIds = $categoryIds.",".$category;
			}
		}
		$layerNamesQuery = 'select layer_id , layer_name from "Meta_Layer" where layer_id in (select distinct layer_id from "Theme_Layer_Mapping" where theme_id = '.$cityThemeId.' and category_id in ('.$categoryIds.'))';
		$result_db = db_query($layerNamesQuery);
		$arr_result = array();
	    while ($result = db_fetch_object($result_db))
		{
			$arr_result[$result->layer_id] = $result->layer_name;
		}
		print json_encode($arr_result);
	 }

	 if ($action == 'getBasicCategories')
	 {
		$query = 'select category_id , category_name from "Categories_Structure" where parent_id = category_id';
	    $result_db = db_query($query);
		$arr_result = array();
		while ($result = db_fetch_object($result_db))
		{

			$arr_result[$result->category_id] = $result->category_name;
		}

		print json_encode($arr_result);
	 }

	 if($action == 'getCategories')
	 {
		$categoryId = $_REQUEST['categoryId'];
		$query = 'select category_id , category_name from "Categories_Structure" where parent_id = '.$categoryId.' and category_id != '.$categoryId;
		$result_db = db_query($query);
		$arr_result = array();
		while ($result = db_fetch_object($result_db))
		{
			$arr_result[$result->category_id] = $result->category_name;
		}
		print json_encode($arr_result);
	 }


?>
