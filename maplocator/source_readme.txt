-----------------------------------------
MapLocator development repository
-----------------------------------------

-----------------------------------------
	Source Code description
-----------------------------------------
Tree based view with description:

+---config 			: Contains config file in xml for setting up various parameters(api keys, Bounding Box, email etc)
+---docs			: Contains documentation for Maplocator(user guide, design doc, installation guide)
+---lib				: Contains thirdparty libraries
|   +---common 				: Commom thirdparty libraries
|   |   +---drupal-6.14 			: Drupal content management system. Required for user administration and managing content.
|   |   +---drupal_modules 			: Drupal modules required by MapLocator
|   |   |   +---captcha 				: Captcha module for drupal
|   |   |   +---fckeditor 				: Rich text editor
|   |   |   +---google_analytics 			: Allows analytics using google api's
|   |   |   +---image 					: Image module
|   |   |   +---nice_menus 				: Enable easy creation of navigation menus
|   |   |   +---views 					: Provides a flexible method for Drupal site designers to control how lists and tables of content (nodes in Views 1, almost anything in Views 2) are presented
|   |   |   +---webform 				: Typical uses for Webform are questionnaires, contact or request/register forms, surveys, polls or a front end to issues tracking systems.
|   |   +---javascript 				: Third party javascript code
|   |   +---openlayers-2.8 			: OpenLayers makes it easy to put a dynamic map in any web page. It can display map tiles and markers loaded from any source.
|   |   +---treeview 				: Enabled tree based view for exploring layers
|   +---linux 				: Contains linux specific packages
|   |   +---mapserver-5.6.1 			: Open Source platform for publishing spatial data and interactive mapping applications to the web.
|   +---windows 			: Contains windows specfic packages
+---scripts 			: Contains custom developed scripts
|   +---database 			: Database dump for maplocator. Required to get started with maplocator
|   +---data_import 			: Scripts to import data from shapefiles
|   +---generateMapfiles 		: Script to create map file required by mapserver
|   +---sanity 				: script to test basic sanity of the system
|   +---deployment 			: deployment scripts for maplocator
+---source 			: Custom developed source for maplocator
    +---drupal_custom			: Drupal specific custom code
    |   +---modules				: Custom developed drupal modules
    |   |   +---map 					: Used to display map. Contains client side processing logic
    |   |   +---node_mlocate_feature 			: Feature node for MapLocator
    |   |   +---node_mlocate_layerinfo 			: Layer info node for maplocator
    |   |   +---node_mlocate_themeinfo			: Theme info node for maplocator
    |   +---theme				: Custom developed drupal theme
    |       +---maplocator 				: Basic theme for MapLocator. Contains rendering logic.
    +---server			: Server specific custom code
        +---ajax 			: Contains ajax endpoints for processing request from the client
        +---flash			: Flash componets
        |   +---LayerOrdering 			: Client side layer ordering
        |   +---ml_metadata
        |   +---MultiLayerSearch 		: Displays multilayer search
        +---standalone_pages 		: Contains standalone php files for performing various activities through the client
