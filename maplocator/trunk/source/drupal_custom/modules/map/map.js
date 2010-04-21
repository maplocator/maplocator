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
* This file contains functionality which is categorized in following parts
* 1) Map digitization & Map <-> UI interaction functionality
* 2) Map Features Functionality
**/

/***** Global variables start here *****/
var map;
var MapServerURL = '/cgi-bin/mapserv';
var GOOGLE_BASE_URL = 'http://maps.google.com/maps/api/staticmap?';
var popup;
var popupMinSize = new OpenLayers.Size(600, 400); // width, height
var popupMaxSize = new OpenLayers.Size(600, 400); // width, height
var popupAutoSize = true;
var selectedControlEditingToolbar;
var newselectControl, newselectedFeature,selectedFeature;

var SET_COOKIE = true;

// For multiLayer Search to intercept feature click event
var isMultiLayerSearchON = false;
var isBBOXSearchON = false;

// UI elems
var ajaxLoaderImg = "sites/all/modules/map/images/ajax-loader.gif";
var UI_LAYERTREE_ID = "divThemes";
var UI_DOD_ID = "dataOnDemand";
var UI_LEGEND_ID = "legend";

var click;
var vlayer,vectors,legend;
var selectControl,topo_type,mapCenter,featureAddedType;
var panel, zb, md, ctrl_pt, ctrl_poly, ctrl_path; // toolbar controls
var twms1,selectpolywms ='';
var featureAddedFlag = 0;
var mapextent,control,overviewLayer;
var zoomLevel = '';
var overview = '';
var CurrentTabOption,LastTabOption;
var URL_FLAG = false;
var themeTree = [];
var arr_layercolor = new Array();

var arr_colors = new Array(10);
var arr_colorassigned = new Array(10);

var arr_selectControl = new Array();

var lastExtent =  ''; /* This variable is used to find if the extent of map is changed and we need to get new points. */

// These layers need to be ingnored while displaying data on demand( now layer data)
var skipLayersForDOD = [];
skipLayersForDOD[0] = 'toolbarlayer';
skipLayersForDOD[1] = 'OpenLayers.Handler.Point';
skipLayersForDOD[2] = 'OpenLayers.Handler.Polygon';
skipLayersForDOD[3] = 'OpenLayers.Handler.Path';

var arr_layers = new Array();
var layersChecked = new Array();

// For layer groups views.
var lyrGrpSlideShowStarted = false;
var hldrLayerChecked = new Array();

var minZoom = JMinZoom;
if (DEPLOYMENT_FOR == 'IBP') {
  if(screen.height < 960) {
    minZoom = 4;
  } else if(screen.height < 768) {
    minZoom = 3;
  }
}

// blockUI settings
var blockUI_overlayCSS = {
  opacity: '0.2'
};
var blockUI_css = {
  border: 'none',
  padding: '15px',
  '-webkit-border-radius': '10px',
  '-moz-border-radius': '10px',
  opacity: '.4'
};
var blockUI_z_index = 10000;

var user_roles_obj = unserialize(user_roles_ser);
var user_roles = [];
for (x in user_roles_obj) {
  user_roles[user_roles_obj[x]] = parseInt(x);
}
// Object defination for storing layer information
function layerInfo() {
  this.layer_id = -1;
  this.layer_name = "";
  this.layer_tablename = "";
  this.layer_type = "";
  this.participation_type = -1;
  this.nid = -1;
  this.p_nid = -1;
  this.addFeaturePerm = 0;
  this.variation_by_column = ""; // color_by for polygon layers and size_by for point layers
  this.feature_count = 0;
  this.extent = '';
  this.access= 0;
  this.projection = 'EPSG:4326';
  this.max_zoom = 19;
  this.icon = "";
  this.is_timebased = 0;
}

var maxExtent,restrictedExtent;
//from Config.xml
	mapextent = new OpenLayers.Bounds(Jmap_extent[0],Jmap_extent[1],Jmap_extent[2],Jmap_extent[3]);
	// set map center
	mapCenter = new OpenLayers.LonLat(Jmap_center[0],Jmap_center[1]);
	maxExtent = new OpenLayers.Bounds(Jmax_extent[0],Jmax_extent[1],Jmax_extent[2],Jmax_extent[3]); // set the max extent of the map
 	restrictedExtent = new OpenLayers.Bounds(Jrestricted_extent[0],Jrestricted_extent[1],Jrestricted_extent[2],Jrestricted_extent[3]); // restrict the extent of the map to India**/

// variables for search and advanced search functionlaity
var polygonControl,polygonLayer;
var searchWMS;
var mls_layers = null;
//For measurement feature
var measureControls;
/***** Global variables end here *****/


/***** Map <-> UI Related functions start *****/


// This function intializes the map. Starting point for understanding the workflow.
function InitializeMap() {

  // set the onImageLoadErrorColor to transparent
  OpenLayers.Util.onImageLoadErrorColor = "transparent";

  // set map options
  var options = {
    controls: [
      new OpenLayers.Control.Navigation(), // add navigation control
      new OpenLayers.Control.PanZoomBar({zoomWorldIcon:true}), // add pan zoom bar icon
      new OpenLayers.Control.ScaleLine(), // add map scale
      new OpenLayers.Control.MousePosition()//, // add mouse position control
    ],
    numZoomLevels: NUMZOOMLEVEL, // set max number of zoom levels
    projection: new OpenLayers.Projection(base_map_projection), // set projecion
    displayProjection: new OpenLayers.Projection(default_projection), // set the display projection
    units: "m", // set the resolution units
    maxResolution: 156543.0339, // set the max resolution
    mapExtent: mapextent,
    maxExtent: maxExtent, // set the max extent of the map
    restrictedExtent: restrictedExtent // restrict the extent of the map to India
  };

  // create map object with specified options.
  map = new OpenLayers.Map('map',options);

  // register the move end event for function onZoom. This will be used to recalculate points on map
  map.events.register("moveend", map, onZoom);

  // to get the xy values from the event object.mandatory in OL 2.7
  OpenLayers.Events.prototype.includeXY = true;


  // Define base layers as per the configuration.
  defineBaseLayers();


  // Multiple objects can share listeners with the same scope
  var zoomListeners = {
    "activate": onZoomActivate,
    "deactivate": onZoomDeactivate
  };

  // create zoomBox control object
  zb = new OpenLayers.Control.ZoomBox({
    eventListeners: zoomListeners,
    title:"Click and draw a box to zoom into an area"
  });

  // create mouseDefaults(navigation icon) object
  md = new OpenLayers.Control.MouseDefaults({
    title:'Click on a feature to show information or click and drag to pan'
  });

  // create toolbar panel object
  panel = new OpenLayers.Control.Panel({
    defaultControl: md
  });

  // add mouseDefaults and zoomBox controls to the toolbar panel
  panel.addControls([
    md,
    zb
  ]);

  // add the toolbar panel to map.
  map.addControl(panel);

  // customize the location of map scale and attribution control
  jQuery(".olControlScaleLine").css("bottom","40px");
  jQuery(".olControlScaleLine").css("left","5px");
  if (DEPLOYMENT_FOR == 'IBP') {
    var img_arr = document.getElementsByTagName('img');
    var cnt = img_arr.length;
    for(var i=0;i<cnt;i++){
      if(img_arr[i].src.indexOf('zoom-world-mini.png') > -1) {
         img_arr[i].src = '/openlayers/img/zoom-india-mini.png';
         break;
      }
    }
  }

  // if the user is logged in, user may have permissions to add features.
  // So add feature addition controls to the map.
  if(user_id) {
  	EnableControlsForUser();

  } else {
    jQuery('#layerOption5').css("display","none");
    jQuery('#layerOption6').css("display","none");
  }

	// Add Control handlers for map
	AssignControlHandlers();

  // set the center of the map with min zoom
   mapCenter.transform(new OpenLayers.Projection(cur_layer_projection), new OpenLayers.Projection(base_map_projection));
  map.setCenter(mapCenter, minZoom);

  // update the map size
  map.updateSize();

  // initialize array of colors.
  setColor();

  // initialize select baselayer dropdown
  initBaseLayerDropDown();

  //check the base layer and add the corresponding overview layer
  loadBirdsEyeView();

}



function onPointPopupClose(evt) {
  if (selectedFeature != null) {
    onPointUnselect(selectedFeature);
  }
}

function onFeatureSelect(feature) {
  newselectedFeature = feature;
}

function onFeatureUnselect(feature) {
  map.removePopup(feature.popup);
  feature.popup.destroy();
  feature.popup = null;
}

function onPolygonSelect(){
}

function onPolygonUnselect(){
  if (map.popups.length>0) {
    map.removePopup(map.popups[0]);
  }
  if (selectpolywms != '') {
    map.removeLayer(selectpolywms);
  }
  selectpolywms = '';
  if (CurrentTabOption != SEARCH_OPT) {
    var topLayer = getTopLayer();
    if (topLayer.isBaseLayer == false) {
      nm = arr_layers[topLayer.name].layer_name;
    }
  }
}

// This function Defines base layers as per the configuration in config.xml .
function defineBaseLayers(){
	 // Select base map source, projection from the value of configuration variable.

  // Base layers array is ready in readConfig.php.
  var BASE_MAP_SOURCE_sp = BASE_MAP_SOURCE.split(",");
  for (var i = 0; i < BASE_MAP_SOURCE_sp.length; i++) {
    switch (BASE_MAP_SOURCE_sp[i])
    {
      case 'YAHOO':

      var yhyb = new OpenLayers.Layer.Yahoo("Yahoo Hybrid",{type: YAHOO_MAP_HYB,MIN_ZOOM_LEVEL: 0,'sphericalMercator': true});
      var ysat = new OpenLayers.Layer.Yahoo("Yahoo Satellite",{type: YAHOO_MAP_SAT,MIN_ZOOM_LEVEL: 0,'sphericalMercator': true});

      if(BIRDS_EYE_VIEW_ENABLED) {
        //adding yahoo satellite layer to birds eye view panel
        overviewYahooLayer = new OpenLayers.Layer.Yahoo(
          "Yahoo Satellite",
          {
          type: YAHOO_MAP_SAT,
          MIN_ZOOM_LEVEL: 2,
          'sphericalMercator': true,
          maxExtent: new OpenLayers.Bounds(6901808.428222222, -7.081154550627198, 11131949.077777777, 4439106.786632658)
          }
        );
      }
      base_map_projection = 'EPSG:900913';
      break;

      case 'VIRTUALEARTH':

      var vphy = new OpenLayers.Layer.VirtualEarth("VirtualEarth Satellite",{type: VEMapStyle.Aerial,MIN_ZOOM_LEVEL: 0,'sphericalMercator': true});
      var vmap = new OpenLayers.Layer.VirtualEarth("VirtualEarth Streets",{type: VEMapStyle.Road, MIN_ZOOM_LEVEL: 0,'sphericalMercator': true});
      var vhyb = new OpenLayers.Layer.VirtualEarth("VirtualEarth Hybrid",{type: VEMapStyle.Hybrid,MIN_ZOOM_LEVEL: 0,'sphericalMercator': true});

      if(BIRDS_EYE_VIEW_ENABLED) {
        //adding VE birds eye layer to birds eye view panel
        overviewVirtualLayer = new OpenLayers.Layer.VirtualEarth(
          "VirtualEarth BirdsEye",
          {
          type: VEMapStyle.BirdsEye,
          MIN_ZOOM_LEVEL: 2,
          'sphericalMercator': true,
          maxExtent: new OpenLayers.Bounds(6901808.428222222, -7.081154550627198, 11131949.077777777, 4439106.786632658)
          }
        );
      }
      base_map_projection = 'EPSG:900913';
      break;

      case 'GOOGLE':
      default:

      OpenLayers.Layer.Google.prototype.RESOLUTIONS = [1.40625,0.703125,0.3515625,0.17578125,0.087890625,0.0439453125,0.02197265625,0.010986328125,0.0054931640625,0.00274658203125,0.001373291015625,0.0006866455078125,0.00034332275390625,0.000171661376953125,0.0000858306884765625,0.00004291534423828125,0.000021457672119140625,0.0000107288360595703125,0.00000536441802978515625,0.000002682209014892578125,0.0000013411045074462890625,0.00000067055225372314453125];
      var gphy = new OpenLayers.Layer.Google("Google Physical",{type: G_PHYSICAL_MAP,MIN_ZOOM_LEVEL: 0,'sphericalMercator': true});
      var gmap = new OpenLayers.Layer.Google("Google Streets",{MIN_ZOOM_LEVEL: 0,'sphericalMercator': true});
      var ghyb = new OpenLayers.Layer.Google("Google Hybrid",{type: G_HYBRID_MAP,MIN_ZOOM_LEVEL: 0,'sphericalMercator': true});
      var gsat = new OpenLayers.Layer.Google("Google Satellite",{type: G_SATELLITE_MAP,MIN_ZOOM_LEVEL: 0,'sphericalMercator': true});

      if(BIRDS_EYE_VIEW_ENABLED) {
        //adding google physical layer to birds eye view panel
        overviewGoogleLayer = new OpenLayers.Layer.Google(
          "Google Physical",
          {
          type: G_PHYSICAL_MAP,
          MIN_ZOOM_LEVEL: 2,
          'sphericalMercator': true,
          maxExtent: mapextent
          }
        );
      }
      base_map_projection = 'EPSG:900913';
      break;

      case 'CUSTOM':

      base_map_projection = 'EPSG:900913';

      if(DEPLOYMENT_FOR == 'UAP'){  //Adds a base map for UAP

          var twms1 = new OpenLayers.Layer.WMS(
                                               "World Map",
                                               MapServerURL,
                                               {
                                                   map:'lyr_00_worldmap.map',
						   transparent: 'true',
                                                   layers: 'lyr_00_worldmap',
                                                   format: 'image/png',
                                                   projection: base_map_projection,
                                                   reproject: false,
                                                   units: "m"
                                               },
                                               {
                                                   singleTile: 'true',
                                                   isBaseLayer: true
                                               }
                                               );
      }
      else  //base map for IBP
          {
	      var twms1 = new OpenLayers.Layer.WMS(
						   "India",
						   MapServerURL,
						   {
						       map: 'lyr_116_india_states.map',
						       transparent: 'true',
						       layers: 'lyr_116_india_states',
						       format: 'image/png',
						       projection: base_map_projection,
						       reproject: false,
						       units: "m"
						   },
						   {
						       singleTile: 'true',
						       isBaseLayer: true
						   }
						   );
	  }
      break;
    }
  }
  // add base layers
  for (var i = 0; i < BASE_MAP_SOURCE_sp.length; i++) {
    switch (BASE_MAP_SOURCE_sp[i])
    {
      case 'YAHOO':
      map.addLayers([ysat, yhyb]);
      break;
      case 'VIRTUALEARTH':
      map.addLayers([vsat, vhyb, vmap, vphy]);
      break;
      case 'GOOGLE':
      default:
      map.addLayers([gphy, gsat, ghyb, gmap]);
      break;
      case 'CUSTOM':
      map.addLayers([twms1]);
      break;
    }
  }

}

// Function checks if the user is logged in, user may have permissions to add features.
// So add feature addition controls to the map.
function EnableControlsForUser(){
	 // vector layer to add feature
    vlayer = new OpenLayers.Layer.Vector( "toolbarlayer" );

    // add the vector layer to map
    map.addLayer(vlayer);

    // control to add point
    ctrl_pt = new OpenLayers.Control.DrawFeature(vlayer, OpenLayers.Handler.Point, {'displayClass': 'olControlDrawFeaturePoint'});
    ctrl_pt.title = 'DrawFeaturePoint';

    // control to add polygon
    ctrl_poly = new OpenLayers.Control.DrawFeature(vlayer, OpenLayers.Handler.Polygon, {'displayClass': 'olControlDrawFeaturePolygon'});
    ctrl_poly.title = 'DrawFeaturePolygon';

    // control to add path
    ctrl_path = new OpenLayers.Control.DrawFeature(vlayer, OpenLayers.Handler.Path, {'displayClass': 'olControlDrawFeaturePath'});
    ctrl_path.title = 'DrawFeaturePath';

    // add featureadded event for vector layer
    vlayer.events.on({
      "featureadded": FeatureAdded
    });

    //add contribute icon
    jQuery('#btn_contribute').css("display","block");
    jQuery('#btn_contribute').parent().css("display","block");

    // if user is validator for any layer, show the validation tab
    if(isUserValidatorForAnyLayer(user_roles)) {
      jQuery('#layerOption5').css("display","block");
    }else{
      jQuery('#layerOption5').css("display","none");
    }

    if(user_roles[SITE_ADMIN_ROLE]) {
      jQuery('#layerOption6').css("display","block");
    } else {
      jQuery('#layerOption6').css("display","none");
    }
}
//Function to initialize the openlayers map click handler
function AssignControlHandlers(){
	// initialize the openlayers map click handler
  OpenLayers.Control.Click = OpenLayers.Class(
    OpenLayers.Control,
    {
      defaultHandlerOptions: {
        'single': true,
        'double': false,
        'pixelTolerance': 0,
        'stopSingle': false,
        'stopDouble': false
      },
      initialize: function(options) {
        this.handlerOptions = OpenLayers.Util.extend(
          {},
          this.defaultHandlerOptions
        );
        OpenLayers.Control.prototype.initialize.apply(
          this,
          arguments
        );
        this.handler = new OpenLayers.Handler.Click(
          this,
          {
            'click': this.onClick,
            'dblclick': this.onDblclick
          },
          this.handlerOptions
        );
      },
      onClick: function(evt) {
        try {
          var maplayers = map.layers.length-1;
          var currenttoplayer = map.layers[maplayers];
          OpenLayers.Event.stop(evt);
        } catch(e) {
        }
      },
      onDblclick: function(evt) {
        var zoomlevel = map.getZoom();
        if(zoomlevel+1 < map.numZoomLevels) {
          map.zoomTo(Math.round(zoomlevel+1));
        }
      }
    }
  );

  controls = {
    "single": new OpenLayers.Control.Click({
      handlerOptions: {
        "single": true
      }
    }),
    "double": new OpenLayers.Control.Click({
      handlerOptions: {
        "single": false,
        "double": true
      }
    }),
    "both": new OpenLayers.Control.Click({
      handlerOptions: {
        "single": true,
        "double": true
      }
    }),
    "drag": new OpenLayers.Control.Click({
      handlerOptions: {
        "single": true,
        "pixelTolerance": null
      }
    }),
    "stopsingle": new OpenLayers.Control.Click({
      handlerOptions: {
        "single": true,
        "stopSingle": true
      }
    }),
    "stopdouble": new OpenLayers.Control.Click({
      handlerOptions: {
        "single": false,
        "double": true,
        "stopDouble": true
      }
    })
  };

  for(var key in controls) {
    control = controls[key];
    control.key = key;
    map.addControl(control);
    control.activate();
  }

}

//Function to add bird's eye view for the map
function loadBirdsEyeView(){
	var ddllayer = document.getElementById('ddlBaseLayer');
	var currBaseLayer = ddllayer.value;
  	if(BIRDS_EYE_VIEW_ENABLED) {
    	if((currBaseLayer.indexOf('Google') != -1) || (currBaseLayer.indexOf('India') != -1)) {
      		if(overviewGoogleLayer) {
        		addOverview(overviewGoogleLayer);
      		}
    	} else if(currBaseLayer.indexOf('Yahoo') != -1) {
      		if(overviewYahooLayer) {
        		addOverview(overviewYahooLayer);
      		}
    	} else if(currBaseLayer.indexOf('Virtual') != -1) {
      		if(overviewVirtualLayer) {
        	addOverview(overviewVirtualLayer);
      		}
    	}
  	}
}
function initBaseLayerDropDown() {
  var ddllayer = document.getElementById('ddlBaseLayer');
  for(var i=0; i<baseLayers.length; i++) {
    addOption(ddllayer, baseLayers[i], baseLayers[i]);
  }
}

function resizeMap() {
  var e = window, a = 'inner';
  if(!('innerWidth' in e)) {
    var t = document.documentElement
    e = t && t.clientWidth ? t : document.body
    a = 'client';
  }
  viewportWidth=e[a+'Width'];
  viewportHeight=e[a+'Height'];
  viewportCenterX = viewportWidth/2;
  viewportCenterY = viewportHeight/2;
  viewportPx = new OpenLayers.Pixel(viewportCenterX,viewportCenterY);
  lonlat = new OpenLayers.LonLat();
  lonlat = map.getLonLatFromViewPortPx(viewportPx);
  map.setCenter(lonlat.lon-mapCenter.lon,lonlat.lat-mapCenter.lat,0);

}

function onZoom() {
  var extent = map.getExtent();
  var polylayer;

  // if user selected zoom is less than minZoom set for map, reset the map zoom to minZoom
  var zoom = this.getZoom();
  if ( zoom < minZoom) {
    this.zoomTo(minZoom);
  }

  // if lastExtent is not set, set it to current extent
  if (lastExtent == '') {
    lastExtent =  extent;
  }

  // if the current extent is different set the last extent to the current extent
  if(CompareExtent(extent,lastExtent)) {
    lastExtent.transform(new OpenLayers.Projection(default_projection), new OpenLayers.Projection(base_map_projection));
    return;
  } else {
    lastExtent = extent;
    lastExtent.transform(new OpenLayers.Projection(default_projection), new OpenLayers.Projection(base_map_projection));
  }

  jQuery("#" + UI_DOD_ID)[0].innerHTML = '';
  jQuery("#layerDataPane").css("display", "none");

  for (var i = layersChecked.length - 1; i >= 0; i--) {
    var layer_tablename = layersChecked[i];
    var SearchIds = "";
    if (CurrentTabOption == SEARCH_OPT || CurrentTabOption == VALIDATION_TAB_OPT) {
      SearchIds = mls_getSearchIds(layer_tablename);
    }
    var layer_type = arr_layers[layer_tablename].layer_type;
    if(layer_type == 'POINT' || layer_type == 'MULTIPOINT' ) {
      if (selectedFeature != null) {
        var fid = selectedFeature.id;
        onPointUnselect(selectedFeature);
        DisplayLayer(layer_tablename, layer_type, fid, SearchIds);
      } else {
        DisplayLayer(layer_tablename, layer_type, null, SearchIds);
      }
    } else {
      polylayer = getTopLayer();
      if(layer_tablename == polylayer.name)
        polylayer.setOpacity(ActiveLayerFillOpacity);
    }
  }

  //if zoombox is active, select the navigation(pan) toolbar
  activateNavigationControl();
}

function onZoomActivate(event) {
  $("#map")[0].style.cursor = "url(sites/all/modules/map/images/zoom-to-extent.png),auto";
}

function onZoomDeactivate(event) {
  $("#map")[0].style.cursor = "";
}

function activateNavigationControl() {
  // Deactivate all controls in toolbar.
  var len = panel.controls.length;
  for(var i = 1; i < len; i++) {
    panel.controls[i].deactivate();
  }
  // Activate navigation control
  panel.controls[0].activate();
}

//Function gets called whenever a new feature is added on to the map->layer
function FeatureAdded(event) {
  // Activate navigation control
  activateNavigationControl();

  // Remove toolbar till popup close.
  panel.deactivate();

  // disable the treeview
  jQuery("#divThemes").find("input").attr("disabled", false);

  var pt = event.feature.geometry.clone();
  var topology = pt.transform(new OpenLayers.Projection(base_map_projection), new OpenLayers.Projection(arr_layers[layersChecked[0]].projection));

  var popuphtml = getFeatureAddedPopupUI(topology.toString());

  featureAddedFlag = 1;
  genFeatureAddedPopup(event.feature, popuphtml);
}

function getFeatureAddedPopupUI(topology) {
  var topo_type = getTopoType(topology);
  featureAddedType = topo_type;
  //check if the topmost layer is of the same layer type
  var layer_tablename = layersChecked[0];
  if(arr_layers[layer_tablename].layer_type == topo_type) {
    if(user_id) {
      if((getUserRoleForLayer(layer_tablename) == 'admin') || isParticipatoryLayer(layer_tablename)) {
        popuphtml = '<div id="mlocate_popup"><h3>Adding feature to ' + arr_layers[layer_tablename].layer_name + '</h3>';
        var myurl = base_path+'ml_orchestrator.php?action=getLayerTableSchema&layer_tablename='+layer_tablename+'&topology='+topology;
        popuphtml += '<div id="divLayerPopup" style="height:100%"><iframe src="'+myurl+'" FRAMEBORDER=0 id="ifrLayerPopup" name="ifrLayerPopup" width="100%" height="99%"></iframe></div>';
        popuphtml += '</div>';
        return popuphtml;
      }
    }
  }

  return "<b>This feature cannot be added to any of the selected layers.</b>";
}

function onFeatureAddedPopupClose(evt) {
  //enable checkboxes
  jQuery("#divThemes").find("input").attr("disabled", false);

  this.destroy();
  if(vlayer) {
    vlayer.destroyFeatures(vlayer.features);
    //if feature added redraw the layer
    if(featureAddedFlag) {
      var maplayerArr = map.layers;
      if(featureAddedType == 'POLYGON') {
        if(twms1) {
          twms1.redraw(true);
          //4 base layers + 1 tool bar layer hence len-5
          map.raiseLayer(twms1,maplayerArr.length-5);
          resetControlPanel();
          panel.addControls([ctrl_poly]);
        }
      } else if(featureAddedType == 'POINT') {
        //point layer
        getData_Category(getTopLayer().name,true);
      }
    }
  }
  featureAddedFlag = 0;
  panel.activate();
}

function genFeatureAddedPopup(feature, htmlstr) {
  var popup_div_id = "chicken";
  popup = new OpenLayers.Popup.FramedCloud(
    popup_div_id,
    feature.geometry.getBounds().getCenterLonLat(),
    null,
    htmlstr,
    null, true, onFeatureAddedPopupClose
  );

  popup.autoSize = popupAutoSize;
  popup.minSize = popupMinSize;
  popup.panMapIfOutOfView = true;
  feature.popup = popup;
  map.addPopup(popup);
  jQuery().ready(function() {
    var popupContentDiv = jQuery('#'+popup_div_id+'_contentDiv');
    popupContentDiv.width('95%');
    popupContentDiv.height('83%');
  });
}

function getTopoType(topology){
  j=topology.indexOf('(');
  var topotype=topology.substring(0,j);
  return topotype;
}

function isParticipatoryLayer(layer_tablename) {
  var layer_participation_type = arr_layers[layer_tablename].participation_type;
  if (layer_participation_type == RESTRICTED_PATICIPATION || layer_participation_type == PUBLIC_PARTICIPATION || layer_participation_type == SANDBOX_PARTICIPATION) {
    return true;
  }
  return false;
}

function getBBOX() {
  var extent = map.getExtent();

  if (map.getZoom() > minZoom ) {
    var offset = 50;
    //Setting the BBOX 10 px more
    var px = map.getViewPortPxFromLonLat(new OpenLayers.LonLat(parseFloat(extent.left),parseFloat(extent.bottom)));
    px.x = px.x - offset;
    px.y = px.y - offset;
    var lonlat = map.getLonLatFromPixel(px);
    extent.left = lonlat.lon;
    extent.bottom = lonlat.lat

    px = map.getViewPortPxFromLonLat(new OpenLayers.LonLat(parseFloat(extent.right),parseFloat(extent.top)));
    px.x = px.x + offset;
    px.y = px.y + offset;
    lonlat = map.getLonLatFromPixel(px);

    extent.right = lonlat.lon;
    extent.top = lonlat.lat
  }

  extent.transform(new OpenLayers.Projection(base_map_projection), new OpenLayers.Projection(default_projection));

  var BBOX = extent.left + " " + extent.bottom + "," + extent.right + " " + extent.top;
  return BBOX;
}

function addOverview(overviewLayer){
  if(!BIRDS_EYE_VIEW_ENABLED) {
    return;
  }

  //to remove already existing overviewmap
  if(overview != ''){map.removeControl(overview);}
  var overviewOptions = {
    layers: [overviewLayer],
    projection: default_projection,
    units: "m",
    maxResolution: "0.17578125"
  };
  overview = new OpenLayers.Control.OverviewMap(overviewOptions);
  overview.isSuitableOverview = function(){
    return false;
  };

  map.addControl(overview);
  if (this.ovmap != null) {
    overview.setRectPxBounds(new OpenLayers.Bounds(6901808.428222222,-7.081154550627198,11131949.077777777,4439106.786632658));
  }
  overview.maximizeControl();

  jQuery(".olControlOverviewMapContainer").css("bottom","30px");

}

function setColor() {
  arr_colors[0] = '#817679';
  arr_colors[1] = '#EFAC7F';
  arr_colors[2] = '#CC00FF';
  arr_colors[3] = '#61CD31';
  arr_colors[4] = '#3333FF';
  arr_colors[5] = '#3EA99F';
  arr_colors[6] = '#F2CA2C';
  arr_colors[7] = '#827839';
  arr_colors[8] = '#C48793';
  arr_colors[9] = '#4E387E';
  for(i=0;i<arr_colorassigned.length;i++) {
    //to keep track of which color is assigned
    arr_colorassigned[i] = 0 ;
  }
}

function getColor(layer_tablename) {
  for (i = 0; i < arr_colorassigned.length; i++){
    if (!arr_colorassigned[i]){
      arr_colorassigned[i] = 1;
      return i;
    }
  }
}

// compare the extents and determine if they are changed grater than given delta
function CompareExtent(nextent, nlastextent) {
  var oldpx = map.getViewPortPxFromLonLat(new OpenLayers.LonLat(nlastextent.left,nlastextent.bottom));
  var newpx = map.getViewPortPxFromLonLat(new OpenLayers.LonLat(nextent.left,nextent.bottom));
  nextent.transform(new OpenLayers.Projection(base_map_projection), new OpenLayers.Projection(default_projection));
  nlastextent.transform(new OpenLayers.Projection(base_map_projection), new OpenLayers.Projection(default_projection));
  var newlb = new OpenLayers.LonLat(nextent.left,nextent.bottom);
  var oldlb = new OpenLayers.LonLat(nlastextent.left,nlastextent.bottom);
  var distance = OpenLayers.Util.distVincenty(newlb,oldlb);

  var zoomlevel = map.getZoom();
  var CompareValue = 450/zoomlevel;
  // zoomLevel is a global vaariable used to find previous zoomlevel notice 'L'.
  if (zoomLevel == '') {
    zoomLevel = zoomlevel;
  }
  // If there is a change in zoomlevel then no need to compare extent, just re-fetch points
  if (zoomLevel != zoomlevel) {
    zoomLevel = zoomlevel;
    return false;
  }
  zoomLevel = zoomlevel;

  if (Math.abs(oldpx.x - newpx.x) > 50 || Math.abs(oldpx.y - newpx.y) > 50 ) {
    return false;
  }

  if (distance < CompareValue) {
    return true;
  } else {
    return false;
  }
}

function maxLayersSelected(select, inputbox) {
  if(layersChecked.length >= MAX_LAYERS && select == true) {
    inputbox.checked = false;
    jQuery("#divModalPopup").html("You can view maximum of " + MAX_LAYERS + " layers at a time. Please unselect any of the selected layers and re-select this layer.");
    jQuery('#divModalPopup').dialog({
      modal: true,
      zIndex: 2004,
      overlay: {
        opacity: 0.5,
        background: "black"
      }
    });
    return true;
  }
  return false;
}

function unselectFeature(layer_tablename, select) {
  // if layer is un-checked or a new layer is checked, unselect the selected features if any
  if((layer_tablename == getTopLayer().name && select == false) || (select == true)) {

    //point
    if(selectedFeature != null) {
       onPointUnselect(selectedFeature);
    }
    // polygon
    if(selectpolywms != '') {
      onPolygonUnselect();
    }
  }
}

function getLayerInfo(layer_tablename) {
  var obj_layerInfo;
  var layerurl = base_path+"ml_orchestrator.php?action=getLayerDetails&layer_tablename="+layer_tablename ;
  jQuery.ajax({
    url:  layerurl,
    type: 'GET',
    timeout: 30000,
    async: false,
    error: function(request,errstring){
      jQuery.unblockUI();
    },
    success: function(resp) {
      var jsonObj = eval('(' + resp + ')');
      obj_layerInfo = new layerInfo();
      for(key in jsonObj){
        obj_layerInfo[key] = jsonObj[key];
      }
    }
  });
  if((typeof obj_layerInfo) == 'undefined') {
    return false;
  } else {
    return obj_layerInfo;
  }
}

function setLayerNameInTreeview(layer_tablename, g_len, l_len) {
  return;
  var layerName = arr_layers[layer_tablename].layer_name;
  if(CurrentTabOption == SEARCH_OPT || CurrentTabOption == VALIDATION_TAB_OPT) {
    var elem_lname = document.getElementById("search_" + layer_tablename);
    if (elem_lname != null) {
      if (document.getElementById("picon_" + layer_tablename) == null) {
        if(layerName.length > l_len) {
          elem_lname.innerHTML = layerName.substring(0, (l_len - 3)) + "...";
        }
      } else {
        if(layerName.length > g_len) {
          elem_lname.innerHTML = layerName.substring(0, (g_len - 3)) + "...";
        }
      }
    }
  } else {
    var elem_lname = document.getElementById("anch_" + layer_tablename);
    if (elem_lname != null) {
      if (document.getElementById("picon_" + layer_tablename) == null) {
        if(layerName.length > g_len) {
          elem_lname.innerHTML = layerName.substring(0, (g_len - 3)) + "...";
        }
      } else {
        if(layerName.length > l_len) {
          elem_lname.innerHTML = layerName.substring(0, (l_len - 3)) + "...";
        }
      }
    }
  }
}

function getLayerMetadata(layer_tablename, successCallBack, successCallBackArgs) {
  /* ----- this function fetches the columns from Meta_Layer table for the table and displays that in a popup ------ */
  blockUI();
  var layerurl = base_path+"ml_orchestrator.php?action=getLayerMetadata&layer_tablename=" + layer_tablename ;

  jQuery.ajax({
    url:layerurl,
    type: 'GET',
    timeout: 30000,
    error: function(request,errstring){
      jQuery.unblockUI();
    },
    success: function(ret){
      var title = "";
      if (DEPLOYMENT_FOR == 'UAP') {
        title = "<h6>"+arr_layers[layer_tablename].layer_name+"</h6>";
        toggleSplitWindow('layerInfo');
        jQuery("#layerInfoPanel").html(ret);
      } else {
        jQuery('#metadata_popup').dialog('destroy').remove();
        var infopopup = document.createElement('div');
        infopopup.setAttribute('id','metadata_popup');
        infopopup.setAttribute('style','height: 400px; width: 500px');
        document.body.appendChild(infopopup);
        infopopup.innerHTML = ret;
        var lft = tp = ht = wd = 0;
        var mp = jQuery('#map');
        ht = mp.height() * 0.8;
        wd = mp.width() * 0.85;
        lft = mp.offset().left + ((mp.width()-wd)/2);
        tp = mp.offset().top + ((mp.height()-ht)/2);
        var title = "";
        if (arr_layers[layer_tablename]) {
          title = arr_layers[layer_tablename].layer_name;
        } else {
          title = jQuery("#divThemes").find("input[value='" + layer_tablename + "']").attr('id');
        }
        jQuery('#metadata_popup').dialog({
          height: ht+'px',
          width: wd+'px',
          maxHeight: ht+'px',
          maxWidth: wd+'px',
          position: [lft, tp],
          title: title,
          zIndex: 2004
        });
      }
      if(successCallBack) {
        successCallBack(successCallBackArgs);
      }
      jQuery.unblockUI();
    }
  });
}


function changeTopLayer(topLayer) {
  var layer_tablename = topLayer.name;
  var id,nm;
  if (topLayer.isBaseLayer == false) {
    topLayer.setOpacity(ActiveLayerFillOpacity);
  } else {
    jQuery("#dataStrip").css("display",'none');
  }
}

function getUserRoleForLayer(layer_tablename) {
  if(user_roles[SITE_ADMIN_ROLE]) {
    return 'admin';
  }

  var participation_type = arr_layers[layer_tablename].participation_type;
  if(participation_type == PUBLIC_PARTICIPATION || participation_type == SANDBOX_PARTICIPATION) {
    return 'member';
  }

  var layer_roles = ["admin", "member", "validator"];
  for(i in layer_roles) {
    if(user_roles[layer_tablename + " " + layer_roles[i]]) {
      return layer_roles[i];
    }
  }

  return "";
}

function userHasAddPermission(layer_tablename) {
  if(arr_layers[layer_tablename].addFeaturePerm) {
    return true;
  } else {
    return false;
  }
}

function provideAddFeatureControls(layer_type) {
  resetControlPanel();
  if(layer_type == "POINT") {
    panel.addControls([ctrl_pt]);
  } else if(layer_type == "POLYGON") {
    panel.addControls([ctrl_poly]);
  } else if(layer_type == "LINE") {
    panel.addControls([ctrl_path]);
  }
}

function resetControlPanel() {

  ctrl_pt.deactivate();
  OpenLayers.Util.removeItem(panel.controls, ctrl_pt);
  var len = map.getLayersByName("OpenLayers.Handler.Point").length;
  for(i = 0; i < len; i++) {
    map.getLayersByName("OpenLayers.Handler.Point")[i].destroy();
  }

  ctrl_poly.deactivate();
  OpenLayers.Util.removeItem(panel.controls, ctrl_poly);
  len = map.getLayersByName("OpenLayers.Handler.Polygon").length;
  for(i = 0; i < len; i++) {
    map.getLayersByName("OpenLayers.Handler.Polygon")[i].destroy();
  }

  panel.redraw();

  activateNavigationControl();
}

function setOLControlPanel(layer_tablename) {
  if (layer_tablename == "") {
    return;
  }

  // The add feature facility is not available when in search or validation mode.
  if (CurrentTabOption == SEARCH_OPT || CurrentTabOption == VALIDATION_TAB_OPT) {
    return;
  }

  if (user_id) {
    if (getUserRoleForLayer(layer_tablename) == 'admin') {
      provideAddFeatureControls(arr_layers[layer_tablename].layer_type);
    } else {
      if(isParticipatoryLayer(layer_tablename)){
        if(userHasAddPermission(layer_tablename)) {
          provideAddFeatureControls(arr_layers[layer_tablename].layer_type);
        }
      } else  {
        resetControlPanel();
      }
    }
  }
}

function setLayerNameColor(layer_tablename) {
  if((!arr_layercolor[layer_tablename]) || arr_layercolor[layer_tablename] == '') {
    color_index = getColor(layer_tablename);
    arr_layercolor[layer_tablename] = arr_colors[color_index];
  }
}

function removeLayerNameColor(layer_tablename) {
  //set assigned color to zero
  for (i = 0; i < arr_colors.length; i++) {
    if (arr_colors[i] == arr_layercolor[layer_tablename]) {
      arr_colorassigned[i] = 0;
    }
  }

  //remove color from arr_layercolor
  arr_layercolor[layer_tablename] = '';
}

function addToLayersChecked(layer_tablename) {
  if(jQuery.inArray(layer_tablename, layersChecked) === -1) {
    layersChecked.splice(0, 0, layer_tablename);
    if (CurrentTabOption != SEARCH_OPT && CurrentTabOption != VALIDATION_TAB_OPT ) {
      setLayersCheckedCookie();
    }
  }
}

function removeFromLayersChecked(layer_tablename) {
  var inArr = jQuery.inArray(layer_tablename, layersChecked);
  if(inArr != -1) {
    layersChecked.splice(inArr, 1);
    if (CurrentTabOption != SEARCH_OPT && SET_COOKIE) {
      setLayersCheckedCookie();
    }
  }
}

function layerToolsIconsUI(layer_tablename) {
  var layer_name = arr_layers[layer_tablename].layer_name;

  var layerUI = '<li class="active" id="li_'+layer_tablename+'">\
                  <a class="tableHandleDrag" href="#"></a>\
                  <div class="layerName" title="' +layer_name+ '">' +layer_name+ '</div>';

  layerUI += '<div class="cl"></div> \
                  </li>';

  return layerUI;
}

function highlightLayerNameInTreeview(layer_tablename) {
  return;
  if(CurrentTabOption == SEARCH_OPT || CurrentTabOption == VALIDATION_TAB_OPT) {
    // clear all highlighting
    jQuery("#divThemes").find("a[@id^='search_']").css('font-weight', '');

    // highlight the current layer
    jQuery("#search_"+layer_tablename).css('font-weight', 'bold');
  } else {
    // clear all highlighting
    jQuery("#divThemes").find("li").css('color', '').css('font-weight', '');

    // highlight the current layer
    jQuery("#divThemes").find("li[id='li"+layer_tablename+"']").css('color', 'red').css('font-weight', 'bold');
  }
}

function removeOLLayerFromMap(obj_OL_currentLayer) {
  obj_OL_currentLayer.setVisibility(false);
  map.removeLayer(obj_OL_currentLayer);
}

function removeLayerinfoPopup() {
  if(jQuery('#metadata_popup').dialog('isOpen')) {
    jQuery('#metadata_popup').dialog('destroy').remove();
  }
}

function zoomToExtent(extent) {
  var arr = extent.split(",");
  var ext = new OpenLayers.Bounds(arr[0], arr[1], arr[2], arr[3]);
  ext.transform(new OpenLayers.Projection(default_projection), new OpenLayers.Projection(base_map_projection));
  if (map.getZoomForExtent(ext, true) < 16){
    map.zoomToExtent(ext);
  } else {
    map.setCenter(ext.getCenterLonLat(),15);
  }
}

function DisplayLayer(layer_tablename, layer_type, feature, Searchids, tlCol, tlStartDate, tlEndDate, tlKeepData) {
try{
  blockUI();
  var b_isNewLayer = false;
  var maxZoomLevel;
  var vectorLayer;

  var layer_type = arr_layers[layer_tablename].layer_type;

  // blur the current top layer
  var topLayer = getTopLayer();
  if(topLayer.isBaseLayer == false) {
    topLayer.setOpacity(InActiveLayerFillOpacity);
  }

  var v_layers = map.getLayersByName(layer_tablename);
  var len = v_layers.length;
  if(len == 0) { // if no layer of that name found in OL map, its a new layer
    b_isNewLayer = true;

    // get the maxZoomLevel of the layer
    maxZoomLevel = arr_layers[layer_tablename].max_zoom;
	// if the layer is POINT, add a vector layer
    if(layer_type == 'POINT') {
      vectorLayer = new OpenLayers.Layer.Vector(layer_tablename, {numZoomLevels : maxZoomLevel});
      map.addLayer(vectorLayer);
    }

  } else {
    // Existing layer. This case may arise on pan or zoom of layer.
    b_isNewLayer = false;

    vectorLayer = v_layers[0];

    // set the opacity of topmost layer
    if (topLayer.name == vectorLayer.name) {
      vectorLayer.setOpacity(ActiveLayerFillOpacity);
    }

    maxZoomLevel = vectorLayer.numZoomLevels;

    if(layer_type == 'POINT') {
      if(tlCol == null || (tlKeepData != null && !tlKeepData)) {
        // since points are rendered using OL, they are features which need to be destroyed.
        vectorLayer.destroyFeatures(vectorLayer.features);
      } else {

      }
    } else {
      /*
       * The re-rendering for polygon and line layers is handled by openlayers.
       * We need not do anything manually. So just unblock the UI and return.
       */
      jQuery.unblockUI();
      return;
    }
  }

  switch(layer_type) {
    case 'POINT':
      var url_getLayer, url_layerExtent;
      if(CurrentTabOption == SEARCH_OPT || CurrentTabOption == VALIDATION_TAB_OPT) {
        var fids = Searchids;
        url_getLayer = base_path+"ml_orchestrator.php?action=get&layer_name=" + layer_tablename + "&BBOX=" + getBBOX()+ "&SearchIds=" + fids;
        url_layerExtent = base_path + "ml_orchestrator.php?action=getlayerextent&layer_name=" + layer_tablename + "&fids=" + fids;
      } else {
        if(tlCol == null) {
          url_getLayer = base_path+"ml_orchestrator.php?action=get&layer_name=" + layer_tablename + "&BBOX=" + getBBOX();
        } else {
          url_getLayer = base_path+"ml_orchestrator.php?action=get&layer_name=" + layer_tablename + "&BBOX=" + getBBOX() + "&tlCol=" + tlCol + "&tlStartDate=" + tlStartDate + "&tlEndDate=" + tlEndDate;
        }
        url_layerExtent = base_path + "ml_orchestrator.php?action=getlayerextent&layer_name=" + layer_tablename ;
      }
      jQuery.ajax({
        url: url_getLayer,
        type:'GET',
        timeout: 30000,
        error: function(request,errstring) {
          jQuery.unblockUI();
        },
        success: function(response) {
          /* Response Format: [REAL/VIRTUAL]|[FEATURE_ID]_[LON],[LAT];[REAL/VIRTUAL]|[FEATURE_ID]_[LON],[LAT]; */
          var feature_count = arr_layers[layer_tablename].feature_count ;

          if (feature_count > 0) {
            var bounds = arr_layers[layer_tablename].extent ;
            if (b_isNewLayer) {
              var arr = bounds.split(",");
              var ext = new OpenLayers.Bounds(arr[0], arr[1], arr[2], arr[3]);
              ext.transform(new OpenLayers.Projection(default_projection), new OpenLayers.Projection(base_map_projection));
              if (map.getZoomForExtent(ext, true) > map.getZoom()){
                zoomToExtent(bounds);
              } else {
                if (map.getExtent().intersectsBounds(ext, true) == false) {
                  zoomToExtent(bounds);
                }
              }
            }
         }

          var resp_len = 0;
          resp_len = response.length;
          if(resp_len > 0) { // Features found in the bounding box.
            var start = 0;
            while(1) {
              var pos = response.indexOf(';', start);
              var pointDetails = response.substring(start, pos-1);

              var parts = pointDetails.split("_");
              var ptInfo = parts[0];
              var point_type = ptInfo.split("|")[0];
              var loc = parts[1].split(',');

              var point = new OpenLayers.Geometry.Point(parseFloat(loc[0]),parseFloat(loc[1]));
              point.transform(map.displayProjection,map.getProjectionObject());

              var pointFeature = new OpenLayers.Feature.Vector(point);

              if (point_type == "VIRTUAL") {
                //pointFeature.style = virtual_point_style;
                pointFeature.style = GetStyle("VIRTUAL",layer_tablename)
              }
              else{
                //pointFeature.style = real_point_style;
                pointFeature.style = GetStyle("REAL",layer_tablename)
              }
              pointFeature.id = layer_tablename + ":" + ptInfo;
              vectorLayer.addFeatures([pointFeature]);

              start = pos + 1;
              if(start >= resp_len) {
                break;
              }
            }

            // assign the select control to the layer.
            if (!(arr_selectControl[layer_tablename])) {
              selectControl = new OpenLayers.Control.SelectFeature(vectorLayer, {onSelect: onPointSelect, onUnselect: onPointUnselect, toggle : true});
              map.addControl(selectControl);
              arr_selectControl[layer_tablename] = selectControl;

              // Activate the top layer select control
              if (layer_tablename == layersChecked[0]) {
                 arr_selectControl[layer_tablename].activate();
              }
           }


            // If any feature was selected previously, select it again after redraw.
            if(feature != null) {
              selectedFeature = null;
              var f = vectorLayer.getFeatureById(feature); // Get the redrawn feature from the map
              if(f != null) { // The feature may go out of view port on pan or zoom. So check if the feature was found.
                onPointSelect(f);
              }
            }
          } else {
            /*
             * No features found in bounding box. There may not be any features in the
             * current bounding box, or there may not be any features at all in the layer.
             */

            if(feature_count > 0) { // Layer is not empty.
              if(vectorLayer.name == layersChecked[0]) {
                if (b_isNewLayer) {
                }
                vectorLayer.setOpacity(ActiveLayerFillOpacity);
              }
            }
          }
        }
      });
      break;
    case 'POLYGON':
    case 'LINE':
    case 'RASTER':
      // If the info of the top layer is missing, get it.
      var checked_layer_count = layersChecked.length;

      //deactivate all point layers' selectFeature control.
      for(layer in arr_selectControl){
	    if(typeof arr_selectControl[layer] != "function"){
          arr_selectControl[layer].deactivate();
        }
      }

      var mapfile = layer_tablename + '.map';

      var wmsUrl;
      var maplayer;
      var url_layerExtent;
      if (CurrentTabOption == SEARCH_OPT || CurrentTabOption == VALIDATION_TAB_OPT) {
        //var fids = getLayername(layer_tablename);
        var fids = Searchids;
        wmsUrl = MapServerURL + "?pid=" + fids + "&";
        maplayer = layer_tablename + "_search";
        url_layerExtent = base_path + "ml_orchestrator.php?action=getlayerextent&layer_name=" + layer_tablename + "&fids=" + fids
      } else {
        wmsUrl = MapServerURL + "?";
        maplayer = layer_tablename;
        if(Searchids != null) {
          wmsUrl += "pid=" + Searchids + "&";
          maplayer += '_search';
        }
        url_layerExtent = base_path + "ml_orchestrator.php?action=getlayerextent&layer_name=" + layer_tablename
      }

      var feature_count = arr_layers[layer_tablename].feature_count ;

      // Code added to check if a symbology layer exists if yes then for that uentire user session show the customized map layer else default map layer ( default map file)

	var symbolgy_mapfile = getSymbologyLayer(layer_tablename);
	if (symbolgy_mapfile != '') {
		mapfile = symbolgy_mapfile;
	}

      // add the wms layer to map
      twms1 = new OpenLayers.Layer.WMS(
        layer_tablename,
        wmsUrl,
        {
          map: mapfile,
          transparent: 'true',
          layers: maplayer,
          format: 'image/png',
          projection: arr_layers[layer_tablename].projection,
          reproject: false,
          units: "m"
        },
        {
          numZoomLevels: maxZoomLevel
        }
      );
      map.addLayer(twms1);
      twms1.setOpacity(ActiveLayerFillOpacity);

      if (layersChecked[0] == twms1.name) {
        if (feature_count > 0) {
          var bounds = arr_layers[layer_tablename].extent ;
          var arr = bounds.split(",");
          var lyr_extent = new OpenLayers.Bounds(arr[0], arr[1], arr[2], arr[3]);
          var map_extent = map.getExtent();
          lyr_extent.transform(new OpenLayers.Projection(arr_layers[layer_tablename].projection), new OpenLayers.Projection(base_map_projection));
          if (map.getZoomForExtent(lyr_extent, true) > map.getZoom()){
	            	zoomToExtent(bounds);
	      }else{
			  if (map_extent.intersectsBounds(lyr_extent, true) == false) {
	              zoomToExtent(bounds);
	          }
          }
        }
      }

      var checked_layer_count = layersChecked.length;
      map.raiseLayer(twms1,checked_layer_count);

      /* ===============  Show popup using mapserver =========== */

        twms1.events.register('click', twms1, function (e) {
          var toplayer = getTopLayer();
          var url =  toplayer.getFullRequestString({
            REQUEST: "GetFeatureInfo",
            BBOX: toplayer.map.getExtent().toBBOX(),
            X: e.xy.x,
            Y: e.xy.y,
            INFO_FORMAT: 'text/plain',
            QUERY_LAYERS: toplayer.params.LAYERS,
            WIDTH: toplayer.map.size.w,
            HEIGHT:toplayer.map.size.h
          });
          var index = url.indexOf('?');
          var var_data = url.substring(index+1,url.length);
          var fidstring;
          jQuery.ajax({
            url: MapServerURL ,
            type: 'GET',
            data: var_data,
            timeout: 30000,
            complete: function(XMLHttpRequest, textStatus){
              /* ======= call drawPolygon using feature id ====== */
              if(fidstring){
                var fid = fidstring.match(/\d+/);
                if(fid) {
                  if (isMultiLayerSearchON)
                    mls_addFeatureid(fid, layer_tablename);
                  else
                    drawPolygon(e.xy,layer_tablename,null,fid);
                }
              }
              jQuery.unblockUI();
            },
            error: function(request,err) {
              jQuery.unblockUI();
              alert('Error loading document');
            },
            success: function(resp) {
              if('RASTER' == arr_layers[layer_tablename].layer_type){
                  showRasterPopup(e.xy,resp,layer_tablename);
              } else {
                  var firstindex = resp.indexOf('Feature');
                  var featureindex = resp.indexOf('Feature',firstindex+1);
                  if (featureindex != -1) {
                    firstindex = resp.indexOf(':');
                    var colonindex = resp.indexOf(':',firstindex+1);
                    fidstring = resp.substring(featureindex,colonindex);
                  }
              }
            }
          });
        });//function ends
      twms1.events.register(controls['double'],twms1 , function (e){});
      break;
  }
  jQuery.unblockUI();
}catch(e){jQuery.unblockUI(); }
}

function getSymbologyLayer(layer_tablename){
	var mapfile = jQuery.ajax({
	            type: "GET",
	            timeout: 30000,
	            url: base_path + "ml_orchestrator.php?action=symbolgylayer&layer=" + layer_tablename,
	            async: false
	          }).responseText
	return mapfile

}


function showRasterPopup(pixel,values,layer_tablename){
  var detailsPopup = '';

  var startindex = values.indexOf('class =');
  var endindex = values.indexOf('red');

  if (map.popups.length>0) {
      map.removePopup(map.popups[0]);
  }
  if(-1 != startindex && -1 != endindex){
    feature_title = values.substring(startindex+7,endindex);

    detailsPopup += '<div id="detailsPane">';
    detailsPopup += '<div id="divPopupPane" class="pane">';
    detailsPopup += '<h3>'+feature_title+'</h3>';
    detailsPopup += '</div></div>';
    var lonlat = getTopLayer().getLonLatFromViewPortPx(pixel);

    popup = new OpenLayers.Popup.FramedCloud("chicken",
      lonlat,
      null,
      detailsPopup,
      null, true, onPolygonUnselect);
    popup.autoSize = popupAutoSize;
    popup.minSize = popupMinSize;
    popup.maxSize = popupMaxSize;
    popup.panMapIfOutOfView = true;
    map.addPopup(popup);
    jQuery().ready(function() {
      var popupContentDiv = ""
      if (DEPLOYMENT_FOR == 'UAP') {
        popupContentDiv = jQuery('#detailsPane');
      } else {
        popupContentDiv = jQuery('#chicken_contentDiv');
      }
      popupContentDiv.width('100%');
      popupContentDiv.height('90%');
    });
  }
}

function onPointSelect(feature) {

  var data = feature.id.split(":");
  var point_type = data[1].split("|");
  if (point_type[0] == "REAL") {
    if (isMultiLayerSearchON) {
      mls_addFeatureid(point_type[1], data[0]);
      return;
    }
  }
  blockUI();
  if (selectedFeature !=null) {
    onPointUnselect(selectedFeature);
  }

  var lnk,var_data;
  var popup_div_id = "chicken";
  switch(point_type[0]) {
    case 'REAL':
      if(DEPLOYMENT_FOR == 'UAP') { // test flash popup
        selectedFeature = feature;
        var html = getFlashPopupHTML(data[0], point_type[1]);
        var popup;
        popup = new OpenLayers.Popup.FramedCloud(popup_div_id,
          feature.geometry.getBounds().getCenterLonLat(),
          null,
          html,
          null, true, onPointPopupClose);
        popup.minSize = popupMinSize;
        popup.maxSize = popupMaxSize;
        popup.autoSize = popupAutoSize;
        popup.panMapIfOutOfView = true;
        feature.popup = popup;
        map.addPopup(popup);
        jQuery.unblockUI();
      } else {
        var action = '';
        if(DEPLOYMENT_FOR == 'UAP') {
           action = "getLayerDataDetails";
        } else {
           action = "getLayerDataSummary";
        }
        var_data = "action="+action+"&row_id=" + point_type[1] + "&layer_tablename=" + data[0];
        lnk = base_path + "ml_orchestrator.php?" + var_data;
        jQuery.ajax({
          url: lnk,
          type: 'GET',
          timeout: 30000,
          error: function(request,err) {
            jQuery.unblockUI();
            alert('Error loading document');
          },
          success: function(resp){
            if("getLayerDataSummary" == action){
              var desc = getSummaryPopupHTML(resp);
            }else if("getLayerDataDetails" == action){
              var desc = getLayerDetailsPopupHTML(resp);
            }

            selectedFeature = feature;
            var location = feature.geometry;
            location = location.toString();
            location = location.replace('POINT(','');
            location = location.replace(')','');
            var loc = location.split(' ');
            var popup_div_id = "chicken";
            var popup;
            popup = new OpenLayers.Popup.FramedCloud(popup_div_id,
              feature.geometry.getBounds().getCenterLonLat(),
              null,
              desc,
              null, true, onPointPopupClose);
            popup.minSize = popupMinSize;
            popup.maxSize = popupMaxSize;
            popup.autoSize = popupAutoSize;
            popup.panMapIfOutOfView = true;
            feature.popup = popup;
            map.addPopup(popup);
            jQuery().ready(function() {
              var popupContentDiv = "";
              if(DEPLOYMENT_FOR == 'UAP') {
                popupContentDiv = jQuery('#detailsPane');
              } else {
                popupContentDiv = jQuery('#'+popup_div_id+'_contentDiv');
              }
              popupContentDiv.linkize();
              jQuery.unblockUI();
            });
          }
        });
      }
      break;
    case 'VIRTUAL':
      lnk = base_path + "ml_orchestrator.php";
      var points = point_type[1].split("!");
      var_data = "action=get&layer_name=" + data[0] + "&BBOX=" + points[1];
      var zoomin = "Please <a id ='zoom' href='#'>Zoom in </a>  to see more points.";
      zoomin += "<br><br> There are " + points[0] + " points under this point.";
      if(CurrentTabOption == SEARCH_OPT){
        var layerelement = document.getElementById("search_" + data[0]);
        if (layerelement != null) {
          var layer_name = layerelement.title.substring(0,(layerelement.title.indexOf('information')-1));
        }
      } else {
        var layer_name = arr_layers[data[0]].layer_name;
      }
      desc = "<div id='mlocate_popup'><h3>Layer : " + layer_name + "</h3>" + zoomin + "</div>" ;
      selectedFeature = feature;
      var location = feature.geometry;
      location = location.toString();
      location = location.replace('POINT(','');
      location = location.replace(')','');
      var loc = location.split(' ');
      var popup_div_id = "chicken";
      var popup;
      popup = new OpenLayers.Popup.FramedCloud(popup_div_id,
        feature.geometry.getBounds().getCenterLonLat(),
        null,
        desc,
        null, true, onPointPopupClose);
      popup.minSize = popupMinSize;
      popup.autoSize = popupAutoSize;
      feature.popup = popup;
      map.addPopup(popup);
      jQuery('#zoom').click(function() {
        ZoomIn(feature,points[1]);
      })
      jQuery().ready(function() {
        var popupContentDiv = jQuery('#'+popup_div_id+'_contentDiv');
        popupContentDiv.width('94%');
        popupContentDiv.height('81%');
        jQuery.unblockUI();
      });
      break;
  }//switch ends
}

function onPointUnselect(feature) {
  if (feature.popup != null) {
    map.removePopup(feature.popup);
    feature.popup.destroy();
    feature.popup = null;
  }
  selectedFeature = null;
}

function GetStyle(pointtype,layername) {
  var style = OpenLayers.Util.extend({}, OpenLayers.Feature.Vector.style['default']);
  if (pointtype == 'REAL') {
    style.graphicOpacity = 0.9;
    style.externalGraphic = base_path + "themeicon.php?theme="+ layername + "&fill=" + arr_layercolor[layername].replace('#','');//base_path + "Images/AQUA.png";
    style.pointRadius =  15;
    style.strokeColor = "#000000";
    style.strokeOpacity = 1;
    style.strokeWidth = 1;
    style.cursor =  "pointer";
    style.graphicXOffset = -(style.pointRadius * 0.66);
    style.graphicYOffset = -(style.pointRadius * 1.9);

  } else {
     style.graphicOpacity = 0.9;
    style.externalGraphic = base_path + "sites/all/modules/map/images/virtual-point.png";
    style.pointRadius =  15;
    style.strokeColor= "#000000";
    style.strokeOpacity = 1;
    style.strokeWidth = 1;
    style.cursor = "pointer";
    style.graphicXOffset = -(style.pointRadius * 0.66);
    style.graphicYOffset = -(style.pointRadius * 1.9);

  }

  return style;
}

function generateLegend(layer_tablename, layer_name) {
  var nopolygon = 1;
  var legendlayername;
  var layer;

  if(CurrentTabOption == SEARCH_OPT) {
    layer = layer_tablename + "_search";
    document.getElementById(UI_LEGEND_ID).innerHTML = '';
    return;
  } else {
     layer = layer_tablename;
  }

  if(layer_name)
    legendlayername = layer_name;
  else
    legendlayername = ((jQuery('#li'+layer_tablename).length != 0)? jQuery('#li'+layer_tablename).find("INPUT")[0].id : layer_tablename);

  if(arr_layers[layer_tablename].layer_type == "POLYGON" || arr_layers[layer_tablename].layer_type == "LINE" || arr_layers[layer_tablename].layer_type == "RASTER") {
    mapfile = layer_tablename+'.map';
    var lyr = map.getLayersByName(layer_tablename)[0];
	if(lyr != null){
		mapfile = lyr.params.MAP;
	}
    legend = document.getElementById(UI_LEGEND_ID);
    legend.innerHTML = '<span style="color: red; font-size: 12pt;font-weight:bold">'+legendlayername+'</span><br><span style="color: red; font-size: 10pt;font-weight:bold">' + arr_layers[layer_tablename].variation_by_column + '</span><br><img src = "' + MapServerURL + '?mode=legend&map='+mapfile+'&layer='+layer+'"</img>';
  } else if(arr_layers[layer_tablename].layer_type == "POINT") {
    jQuery("#"+UI_LEGEND_ID).html('<img src ="'+arr_layers[layer_tablename].icon+'"></img><span style="color: red; font-size: 12pt;font-weight:bold">'+legendlayername+'</span>');
  }
}

function toggleLayer(layer_tablename) {
  if (jQuery.inArray(layer_tablename, layersChecked) == -1) {
    jQuery("#divThemes").find("input[value='" + layer_tablename + "']").attr("checked", true);
    if (CurrentTabOption == SEARCH_OPT || CurrentTabOption == VALIDATION_TAB_OPT) {
      getData_Category(layer_tablename, 1, mls_getSearchIds(layer_tablename));
    } else {
      getData_Category(layer_tablename, 1);
    }
  } else {
    jQuery("#divThemes").find("input[value='" + layer_tablename + "']").attr("checked", false);
    getData_Category(layer_tablename, 0);
  }
}

function getData_Category(layer_tablename, select, Searchids, inputbox) {
  if(Searchids == 'categories') {
    Searchids = null;
  }
  showAdvancedToolSet(false);
  if (maxLayersSelected(select, inputbox)) {
    return;
  }

  unselectFeature(layer_tablename, select);

  // clear DOD
  jQuery("#" + UI_DOD_ID)[0].innerHTML = '';

  // new layer is selected
  if (select == true) {
    if (inputbox)
      inputbox.disabled = true;

    jQuery("#img_" + layer_tablename ).css("display",'inline');
    jQuery("#img_" + layer_tablename ).css("border",'0px');

    // get layer info
    var obj_layerInfo = getLayerInfo(layer_tablename);
    if (obj_layerInfo == false) {
      alert("ERROR: Unable to fetch layer info. Please try after sometime.");
      return;
    }
    if (arr_layers[layer_tablename]) {
      var obj = arr_layers[layer_tablename];
      obj = null;
    }
    arr_layers[layer_tablename] = obj_layerInfo;

    /* ----- remove layerinfo popup ----- */
    removeLayerinfoPopup();

    // set the color for layer name in treeview
    setLayerNameColor(layer_tablename);

    // add the layer to layerschecked array
    addToLayersChecked(layer_tablename);

    // add the layer to layer ordering
    addToLayerOrdering(layer_tablename);

    // set the OpenLayers control panel based on user permission
    setOLControlPanel(layer_tablename);

    if (CurrentTabOption == SEARCH_OPT || CurrentTabOption == VALIDATION_TAB_OPT) {
      DisplayLayer(layer_tablename, obj_layerInfo.layer_type, null, Searchids);
      showLegendForSearch(layer_tablename, obj_layerInfo.layer_name);
    } else {
      DisplayLayer(layer_tablename, obj_layerInfo.layer_type, null, Searchids);
      generateLegend(layer_tablename, obj_layerInfo.layer_name);
    }

    if (URL_FLAG == true) {
      changeLayerOptions(1);
      URL_FLAG = false;
    }

    if (inputbox)
      inputbox.disabled = false;

    if(parseInt(arr_layers[layer_tablename].access)) { // download access
      jQuery("#downloadLayer").css("display", "block");
    } else {
      //no access
      jQuery("#downloadLayer").css("display", "none");
    }

    showAdvancedToolSet(true);
    setActiveLayerInfo(arr_layers[layer_tablename]);

    if(DEPLOYMENT_FOR == 'UAP') {
      jQuery("#iconShowSymbology").show();
    }

  	// TO DO : add if condition whether GE and Chloropleth is to be added based on config settings
    jQuery("#iconShowGE").hide();
    jQuery("#iconShowChloropleth").hide();
    jQuery("#iconShowProportional").hide();
    if (GOOGLE_EARTH_ENABLED) {
      jQuery("#iconShowGE").show();
    }
    if(CHLOROPLETH_ENABLED){
      jQuery("#iconShowChloropleth").show();
      //hard cocded to show demo of propositional symbols
      if (layer_tablename == 'lyr_2_ancient_remains') {
        jQuery("#iconShowProportional").show();
      }
    }

    return;
  } else { // select == false
    var obj_OL_CurrentLayer = map.getLayersByName(layer_tablename)[0];
    if (obj_OL_CurrentLayer != null) {
      // remove ZTE icon
      jQuery("#img_" + layer_tablename).css("display", "none");

      // remove layer from layerChecked
      removeFromLayersChecked(layer_tablename);

      // remove layer from map
      removeOLLayerFromMap(obj_OL_CurrentLayer);

      // remove layer from layer ordering
      removeFromLayerOrdering(layer_tablename);

      /* ----- remove layerinfo popup ----- */
      removeLayerinfoPopup();

      //remove entry from arr_selectControl object and map if point layer
      if ('POINT' == arr_layers[layer_tablename].layer_type) {
         map.removeControl(arr_selectControl[layer_tablename]);
         delete arr_selectControl[layer_tablename];
      }

      //activate topmost point layer's select control
      activateSelectControl();

      // remove the color assigned to layer.
      removeLayerNameColor(layer_tablename);

      jQuery("#divThemes").find("li").css('color', '').css('font-weight', '');

      // clear legend
      legend = document.getElementById(UI_LEGEND_ID);
      legend.innerHTML = '';

      if (user_id) {
        // Reset the OL control panel. This needs to be done only if a user is logged in
        resetControlPanel();
      }

      if (layersChecked.length > 0) {
        var topLayer = getTopLayer();
        var top_layer_tablename = topLayer.name;

		showAdvancedToolSet(true);

        setActiveLayerInfo(arr_layers[top_layer_tablename]);


        if(DEPLOYMENT_FOR == 'UAP') {
          jQuery("#iconShowSymbology").show();
        }
        // TO DO : add if condition whether GE and Chloropleth is to be added based on config settings
        jQuery("#iconShowGE").hide();
        jQuery("#iconShowChloropleth").hide();
        jQuery("#iconShowProportional").hide();
        if (GOOGLE_EARTH_ENABLED) {
          jQuery("#iconShowGE").show();
        }
        if(CHLOROPLETH_ENABLED){
          jQuery("#iconShowChloropleth").show();
          //hard cocded to show demo of propositional symbols
          if (layer_tablename == 'lyr_2_ancient_remains') {
            jQuery("#iconShowProportional").show();
          }
        }

        // change the opacity of top layer.
        changeTopLayer(topLayer);

        // set the OpenLayers control panel based on user permission
        setOLControlPanel(top_layer_tablename);

        if ((jQuery.inArray(top_layer_tablename, skipLayersForDOD) == -1) && (jQuery.inArray(top_layer_tablename, baseLayers) == -1)) {
          if(CurrentTabOption == SEARCH_OPT) {
            showLegendForSearch(top_layer_tablename, arr_layers[top_layer_tablename].layer_name);
          } else {
            generateLegend(top_layer_tablename);
          }
        } else {
          legend = document.getElementById(UI_LEGEND_ID);
          legend.innerHTML = '';
        }

        if(parseInt(arr_layers[top_layer_tablename].access)) {
	       jQuery("#downloadLayer").css("display", "block");
        } else {
           //no access
           jQuery("#downloadLayer").css("display", "none");
        }

      }

      if (layersChecked.length == 0) {
        showAdvancedToolSet(false);
      }
      if(DEPLOYMENT_FOR == 'UAP') {
          jQuery("#iconShowSymbology").hide();
      }
      return;
    }
  }


}
function activateSelectControl(){
  if (layersChecked.length >= 1) {
     var toplayer = layersChecked[0];
     if ('POINT' == arr_layers[toplayer].layer_type) {
        arr_selectControl[toplayer].activate();
     }
  }
}

function RemoveCheckedLayers() {
  jQuery("#divThemes").find("input").attr("checked", false);
  for( var i = layersChecked.length -1; i >= 0; i--) {
     getData_Category(layersChecked[0],false);
  }
  layersChecked = new Array();
  setLayersCheckedCookie();
}

function getTopLayer() {
  // the topmost layer is the last item in map.layers
  var index=map.layers.length-1;
  while(index > 0) {
    if(map.layers[index].isBaseLayer == false) {
      if(jQuery.inArray(map.layers[index].name, skipLayersForDOD) == -1) {
        break;
      }
    }
    index--;
  }
  var topLayer = map.layers[index];
  return topLayer;
}
// required for flash layerordering
function getTopLayerName()
{
  var obj = new Object();
	obj.id = getTopLayer().name;
	obj.label = "";
  return obj;
}

function getCategory(lastopt,currentopt) {

  var layerOptions = parseInt(jQuery.cookie("layerOptions"));
  // if layerOptions cookie is not set, set it to "Theme" by default
  if(!layerOptions) {
    layerOptions = 1;
  }
  jQuery('#layerOption'+layerOptions).addClass("selected");

  CurrentTabOption = currentopt;

  if (CurrentTabOption == null) {
    CurrentTabOption = layerOptions;
  }

  LastTabOption = lastopt;

  /* if the last option was search or validation tab, remove layers loaded for search or validation and load the normal checked layers. */
  if (lastopt != null) {
    if (lastopt == SEARCH_OPT || lastopt == VALIDATION_TAB_OPT) {
      RemoveCheckedLayers();
      reloadCheckedLayers();
    }
  }

  SET_COOKIE = true;

  // load the layers treeview
  var divdata = document.getElementById(UI_LAYERTREE_ID);
  divdata.innerHTML = '<div id="ajaxLoader" style="display:block"><img src="'+ajaxLoaderImg+'"></img></div>' + "<ul id='ulThemeTree" + layerOptions + "'></ul>";

  var lnk = base_path + "ml_orchestrator.php?action=getcategory&theme_type=" + layerOptions;
  jQuery(document).ready(function(){
    jQuery("#ulThemeTree"+layerOptions).treeview({
      url: lnk
      ,toggle: function() {
        if(/collapsable/.test(this.className)) { // check if this.className contains "collapsable"
          themeTree[this.id] = 1;
        } else if(/expandable/.test(this.className)) { // check if this.className contains "expandable"
          themeTree[this.id] = 0;
        }
        setThemeTreeCookie(layerOptions, themeTree);
      }
      ,ajax: {
        complete: function(resp) {
          if (jQuery("#ajaxLoader").css("display") == 'block') {
            jQuery("#ajaxLoader").css("display", "none");
          }

          if(layersChecked.length>0) {
            jQuery('#li'+layersChecked[0]).css('color','red').css('font-weight','bold');
          }
        }
      }
    });
  });
}

function setLayersCheckedCookie() {
  jQuery.cookie("layersChecked", layersChecked.join(":"));
}

function getLayersCheckedArray() {
  if(jQuery.cookie("layersChecked")) {
    layersChecked = jQuery.cookie("layersChecked").split(":");
  } else {
    layersChecked = [];
  }
  return layersChecked;
}

function setThemeTreeCookie(themeType, themeTree) {
  jQuery.cookie("themeTree"+themeType, serialize(themeTree));
}

function getThemeTreeArray(themeType) {
  if(jQuery.cookie("themeTree"+themeType)) {
    themeTree = unserialize(jQuery.cookie("themeTree"+themeType));
  } else {
    themeTree = [];
  }
}

function genDataTableForLinkedData(resp) {
  if(resp.indexOf("<thead>") != -1) {
    jQuery("#linkedData").dataTable({
      "sDom": 'lpif<"dod_linked"t>r',
      "anLengthMenu" : new Array(10, 15, 20, 25),
      "fnRowCallback":function(nRow) {
        jQuery(nRow).click(function() {
          jQuery('.tblRowHighlight').removeClass('tblRowHighlight');
          jQuery(nRow).addClass('tblRowHighlight');
        });
        return nRow;
      }
    });
  }
}

function fetchPolygon(layer_tablename, polygonid) {
  var layername = layer_tablename +'_select';
  var mapfile = layer_tablename+'.map';
  selectpolywms = new OpenLayers.Layer.WMS(
    layername,
    MapServerURL +"?pid="+polygonid+"&",
    {
      map: mapfile,
      transparent: 'true', layers:layername,
      format: 'image/png',
      projection: arr_layers[layer_tablename].projection,
      reproject:false,
      units: "m"
    },
    {singleTile: true}
  );
  map.addLayer(selectpolywms);
  var maplayers = map.layers.length-2;
  var wmslayer = map.layers[maplayers];
  map.raiseLayer(wmslayer,maplayers);
  twms1.setOpacity(ActiveLayerFillOpacity);

  jQuery.unblockUI();
}

function drawPolygon(pixel,layer_tablename,point,featureid){
  /* ---- layer_tablename is the layer object here -----*/
  onPolygonUnselect();
  blockUI();
  if (point !=null) {
    var lonlat = point;
  } else {
    var lonlat = getTopLayer().getLonLatFromViewPortPx(pixel);
  }
  lonlat.transform(new OpenLayers.Projection(base_map_projection), new OpenLayers.Projection(arr_layers[layer_tablename].projection));
  var point = "POINT(" + lonlat.lon + " " + lonlat.lat + ")";

  if(DEPLOYMENT_FOR == 'UAP') { // test flash popup
    var html = getFlashPopupHTML(layer_tablename, featureid);
    var popup;
    lonlat.transform(new OpenLayers.Projection(arr_layers[layer_tablename].projection), new OpenLayers.Projection(base_map_projection));
    popup = new OpenLayers.Popup.FramedCloud("chicken",
      lonlat,
      null,
      html,
      null, true, onPolygonUnselect);
    popup.minSize = popupMinSize;
    popup.maxSize = popupMaxSize;
    popup.autoSize = popupAutoSize;
    popup.panMapIfOutOfView = true;
    map.addPopup(popup);
    jQuery.unblockUI();
  } else {
    if(DEPLOYMENT_FOR == 'UAP') {
        var action = "getLayerDataDetails";
    } else {
        var action = "getLayerDataSummary";
    }
    var var_data = "action="+action+"&row_id=" + featureid + "&layer_tablename=" + layer_tablename;
    var lnk = base_path + "ml_orchestrator.php?" + var_data;
    jQuery.ajax({
      url: lnk,
      type: 'GET',
      timeout: 30000,
      error: function(request,err) {
        jQuery.unblockUI();
        alert('Error loading document');
      },

      success: function(resp) {
        if (resp.length > 1) {
          /*----- fetch polygon ----- */
          if("getLayerDataSummary" == action)
            var desc = getSummaryPopupHTML(resp);
          else
            var desc = getLayerDetailsPopupHTML(resp);
          fetchPolygon(layer_tablename,featureid);
          /*----- display popup ----- */
          lonlat.transform(new OpenLayers.Projection(arr_layers[layer_tablename].projection), new OpenLayers.Projection(base_map_projection));
          popup = new OpenLayers.Popup.FramedCloud("chicken",
            lonlat,
            null,
            desc,
            null, true, onPolygonUnselect);
          popup.autoSize = popupAutoSize;
          popup.minSize = popupMinSize;
          popup.maxSize = popupMaxSize;
          popup.panMapIfOutOfView = true;
          map.addPopup(popup);
        }
        jQuery.unblockUI();
      }
    });
  }
}
// Returns feature count of the layer in the current view port
function GetFeatureCount(layer) {
  var counter = 0;
  var data, point_type;
  var viewport =  map.getExtent();
  switch(arr_layers[layer.name].layer_type) {
    case 'POINT':
      var features = layer.features;
      var cnt = features.length;
      for (var i = 0; i < cnt; i++) {
        if (viewport.containsLonLat(features[i].geometry.getBounds().getCenterLonLat())) {
          data = features[i].id.split(":");
          point_type = data[1].split("|");
          if (point_type[0] == 'REAL') {
            counter++;
          }
        }
      }
      break;
    case 'POLYGON':
    case 'LINE':
      if (CurrentTabOption != SEARCH_OPT && CurrentTabOption != VALIDATION_TAB_OPT) {
        url = base_path +"ml_orchestrator.php?action=getDODCount&layer_name=" + layer.name + "&BBOX=" + getBBOX();
      } else {
        var fids = getLayername(layer.name);
        url = base_path +"ml_orchestrator.php?action=getDODCount&layer_name=" + layer.name + "&fids=" + fids;
      }
      counter = parseInt(jQuery.ajax({
        type: "GET",
        timeout: 30000,
        url: url,
        async: false
      }).responseText);
      break;
  }
  return counter;
}

function reloadCheckedLayers() {
  getLayersCheckedArray();
  for( var i = layersChecked.length - 1; i >= 0; i--) {
    getData_Category(layersChecked[i], 1);
  }
}

function ResizeWindow() {
  top.window.moveTo(0,0);
  if (document.all) {
    top.window.resizeTo(screen.availWidth,screen.availHeight);
  } else if(document.layers || document.getElementById) {
    if(top.window.outerHeight < screen.availHeight || top.window.outerWidth < screen.availWidth) {
      top.window.outerHeight = top.screen.availHeight;
      top.window.outerWidth = top.screen.availWidth;
    }
  }
}

function showParticipationInfo(layer_tablename, p_nid) {
  var args = new Array();
  args['p_nid'] = p_nid;
  getLayerMetadata(layer_tablename, selectParticipationTab, args);
}

function selectParticipationTab(args) {
  var p_nid = args['p_nid'];
  if(p_nid == 0) {
    alert("No information avialable yet.");
  } else {
    popupTabClicked('layerinfo', 'ulLayerPopupUIMenu', 'DrupalNodeParticipation', base_path + 'node/' + p_nid + '/popup');
  }
}

function showLayerInfo(layer_tablename, l_nid) {
  var args = new Array();
  args['l_nid'] = l_nid;
  getLayerMetadata(layer_tablename, selectLayerInfoTab, args);
}

function selectLayerInfoTab(args) {
  var l_nid = args['l_nid'];
  if(l_nid == 0) {
    alert("No information avialable yet.");
  } else {
    popupTabClicked('layerinfo', 'ulLayerPopupUIMenu', 'DrupalNode', base_path + 'node/' + l_nid + '/popup');
  }
}

function SetBaseLayer(layer) {
  var obj = map.getLayersByName(layer);
  map.setBaseLayer(obj[0]);
  map.updateSize();

  //check the base layer and add the corresponding overview layer
  var ddllayer = document.getElementById('ddlBaseLayer');
  var currBaseLayer = ddllayer.value;
  if(BIRDS_EYE_VIEW_ENABLED) {
    if((currBaseLayer.indexOf('Google') != -1) || (currBaseLayer.indexOf('India') != -1)) {
      if(overviewGoogleLayer) {
        addOverview(overviewGoogleLayer);
      }
    } else if(currBaseLayer.indexOf('Yahoo') != -1) {
      if(overviewYahooLayer) {
        addOverview(overviewYahooLayer);
      }
    } else if(currBaseLayer.indexOf('Virtual') != -1) {
      if(overviewVirtualLayer) {
        addOverview(overviewVirtualLayer);
      }
    }
  }
}

function showDetailsPopup(layer_tablename, row_id, dialogTitle) {
  blockUI();
  var_data = "action=getLayerDataDetails&row_id=" + row_id + "&layer_tablename=" + layer_tablename;
  lnk = base_path + "ml_orchestrator.php?" + var_data ;

  jQuery.ajax({
    url: lnk,
    type: 'GET',
    timeout: 30000,
    error: function(request,err) {
      jQuery.unblockUI();
    },
    success: function(resp){
      jQuery.unblockUI();
      var html = getLayerDetailsPopupHTML(resp);
      showModalPopup(html, dialogTitle, clearPopupUIMenu);
    }
  });
}

function ShowLayersForValidation(prevopt,currentopt){
  blockUI();
  CurrentTabOption = currentopt;
  LastTabOption = prevopt;
  SET_COOKIE = false;
  RemoveCheckedLayers();
  var divdata = document.getElementById(UI_LAYERTREE_ID);
  divdata.innerHTML = '<img src="' + ajaxLoaderImg + '"></img>';
  for(var i = 1; i < 6; i++) {
    jQuery('#layerOption'+i).removeClass("selected");
  }
  jQuery('#layerOption'+5).addClass("selected");
  legend = document.getElementById(UI_LEGEND_ID);
  legend.innerHTML = "";
  jQuery.ajax({
    url:  base_path + "ml_orchestrator.php?action=validation",
    type: 'GET',
    timeout: 30000,
    error: function(request,err){
      jQuery.unblockUI();
    },
    success: function(resp){
		// load response
		var jsonObj = eval('(' + resp + ')');
		if (jsonObj[0] == "TEXT_RESP")
			divdata.innerHTML = jsonObj[1];
		else
		{
			var len  = jsonObj.length;
			var data = "";
			for (var i = 0; i < len; i++)
			{
				htmldata = showSearchResponse(jsonObj[i]);
				data = data + htmldata;
			}
			divdata.innerHTML = 'The list of layers with features to be validated: <br>'+ data;
		}
		jQuery.unblockUI();
    }
  });
}

function validateFeature(checked, layer_tablename, row_id) {
  if(checked) {
    jQuery.ajax({
      url:  base_path + "ml_orchestrator.php",
      type: 'POST',
      timeout: 30000,
      data: "action=validateFeature&layer_tablename=" + layer_tablename + "&id=" + row_id,
      error: function(request,err) {
        jQuery.unblockUI();
      },
      success: function(resp) {
        var jsonObj = eval('(' + resp + ')');
        if("Record saved." == jsonObj['validate']) {
          var obj = map.getLayersByName(layer_tablename);
          if (selectedFeature !=null) {
            onPointUnselect(selectedFeature);
          }
          var fid =  layer_tablename + ":"+ "REAL|" + row_id;
          var feature = obj[0].getFeatureById(fid);
          if(feature!=null){
            onPointSelect(feature);
          }
        } else {
          alert(resp);
        }
        jQuery.unblockUI();
      }
    });
  }
}

function checkLayer(layer_tablename) {
  getData_Category(layer_tablename, 1);
}

function checkLayerAtPosition(layer_tablename, pos) {
  getData_Category(layer_tablename, 1);
  reorderLayer(layer_tablename, 0, pos);
}

function removeLayer(layer_tablename) {
  getData_Category(layer_tablename, 0);
}

// following functions will be invoked from layer pallete


//for layer data icon
function showLayerData(layer_tablename){
	if (arr_layers != null) {
		var toplayer = map.getLayersByName(layer_tablename)[0];
		if(toplayer != null){
		document.getElementById('layerDataPane').style.display="block";
		jQuery("#layerDataPane").css("z-index","3000");
			genDOD(toplayer);
			flash_reorderLayer(layer_tablename);
		}
	}
}
//for layer info icon

//for zoom to layer icon
function zoomToLayerExtent(layer_tablename){
	if (arr_layers != null) {
		var toplayer = arr_layers[layer_tablename];
		if(toplayer != null){
			zoomToExtent(toplayer.extent);
		}
	}
}
//for setting layer transparency ( value should be in range 0 to 1 eg 0.2 for 20%)
function setLayerTransparency(layer_tablename,value){
	if (arr_layers != null) {
		var toplayer = arr_layers[layer_tablename];
		if(toplayer != null){
			var maplayer = map.getLayersByName(layer_tablename)[0];
			maplayer.setOpacity(1 - value);
		}
	}

}
function applySymbology(layer_tablename,column,expr,hexcolor,title){
    var mapfilename  = jQuery.ajax({
	        type: "POST",
	        timeout: 30000,
	        url: "ml_orchestrator.php",
	        data: "action=getmapscript&layer_tablename=" + layer_tablename + "&expr="+expr+"&color="+hexcolor+"&col=" +column+"&getinfo="+title,
	        async: false
	        }).responseText;
	if (arr_layers != null) {
		var toplayer = map.getLayersByName(layer_tablename)[0];
		if(toplayer != null){
			toplayer.mergeNewParams({map: mapfilename});
			toplayer.redraw();
		}
	}
}


/***** Map <-> UI Related functions end *****/




/***** Map Locator Features Functionality starts *****/

//Search functionality
function search_data(clicked, prevopt, currentopt) {
  blockUI();

  var srchdata = '';

  CurrentTabOption = currentopt;
  LastTabOption = prevopt;
  SET_COOKIE = false;

  // remove all loaded layers
  RemoveCheckedLayers();

  //if (clicked != null) {
  srchdata = document.getElementById('txtSearch').value;
  //}

  // clear the treeview
  var divdata = document.getElementById(UI_LAYERTREE_ID);
  divdata.innerHTML = '';

  // highlight the search tab
  jQuery("#layerOptions").find("li").removeClass("selected");
  jQuery('#layerOption'+SEARCH_OPT).addClass("selected");

  // clear the legend
  legend = document.getElementById(UI_LEGEND_ID);
  legend.innerHTML = "";

  if (srchdata != '') {
    jQuery.ajax({
      url:  base_path + "ml_orchestrator.php?param=" + srchdata,
      type: 'GET',
      timeout: 30000,
      error: function(request,err) {
        jQuery.unblockUI();
      },
      success: function(resp) {
        // load the response
		var jsonObj = eval('(' + resp + ')');
		var len  = jsonObj.length;
		var data = "";
		for (var i = 0; i < len; i++)
	    {
			var str1 = '<br><span title = "' + jsonObj[i].layer_name + ' information">';
			var str2 = '</span>';
			htmldata = showSearchResponse(jsonObj[i]);
			data = data + str1 + htmldata + str2;
		}
        divdata.innerHTML = 'Search results for: <b><I>' + srchdata + '</b></I> <br>' + data;

        if (clicked) {
          changeLayerOptions(SEARCH_OPT,true);
        }
        jQuery.unblockUI();
      }
    });
  } else {
    jQuery.unblockUI();
  }
}

//Layer ordering
function addToLayerOrdering(layer_tablename) {
  if (checkLayerInfoExists(layer_tablename) == false) {
    alert("Error reading layer info.");
    return;
  }
  addLayerOrderingElemAtTop(layer_tablename, arr_layers[layer_tablename].layer_name, arr_layers[layer_tablename].p_nid,arr_layers[layer_tablename].extent,arr_layers[layer_tablename].access);

}

function removeFromLayerOrdering(layer_tablename) {
  if (checkLayerInfoExists(layer_tablename) == false) {
    alert("Error reading layer info.");
    return;
  }
  removeLayerOrderingElem(layer_tablename, arr_layers[layer_tablename].layer_name);
}




// Draw bbox for bounding box search
function mls_DrawBBOX(){
	if (polygonLayer != null) {
		polygonLayer.destroyFeatures();
	}else{
		polygonLayer = new OpenLayers.Layer.Vector("Polygon Layer");
		polygonLayer.events.on({
        "sketchstarted": clearBBOX
        })
	}
    map.addLayers([polygonLayer]);
    polyOptions = {sides: 4,irregular: true};
    polygonControl = new OpenLayers.Control.DrawFeature(polygonLayer,
                                            OpenLayers.Handler.RegularPolygon,
                                            {handlerOptions: polyOptions,featureAdded: getSearchBBOX });

    map.addControl(polygonControl);

    polygonControl.activate();

}

function ActivateBBOX(val){
	if (polygonControl != null && val == true) {
		polygonControl.activate();
	}else if (polygonControl != null && val == false) {
		polygonControl.deactivate();
	}
}
function clearBBOX(obj){
	if (layersChecked.length > 0) {

		resetFeatureStyle();

	}
	if (polygonLayer.features.length > 0 ) {
		polygonLayer.destroyFeatures();
	}
}
function removeBBOX(){
	if (layersChecked.length > 0) {

		resetFeatureStyle();

	}
	if(polygonLayer != null){
		if (polygonLayer.features.length > 0 ) {
			polygonLayer.destroyFeatures();
		}
	}
	if(polygonControl != null){
		polygonControl.deactivate();
    }
	try{
		if (searchWMS != null) {
			map.removeLayer(searchWMS);
			searchWMS = null;
		}
		if(polygonLayer != null){
			map.removeLayer(polygonLayer);
			polygonLayer = null;
		}
		map.removeControl(polygonControl);
		polygonControl = null;
	}catch(e){}
}

function HightlightFeaturesInBBOX(bbox){

 	var layer_type = arr_layers[layersChecked[0]].layer_type;
	var toplayer,features;
	toplayer = map.getLayersByName(layersChecked[0])[0]
    if(layer_type == 'POINT' || layer_type == 'MULTIPOINT' ) {
    	features = toplayer.features;
		var len = features.length;
		var x,y;
		for(var i=0; i<len;i++){
			x = features[i].geometry.x;
			y = features[i].geometry.y;
			if (bbox.contains(x,y)) {
				features[i].style.pointRadius = 20;
			}else{
				features[i].style.pointRadius = 15;
			}

		}
		toplayer.redraw();
	}else{
		  jQuery.blockUI();
		  var layername = toplayer.name +'_searchBB';
		  var mapfile = toplayer.name +'.map';
		  var extent = new OpenLayers.Bounds(bbox.left,bbox.bottom,bbox.right, bbox.top);
		  extent = extent.transform(new OpenLayers.Projection(base_map_projection), new OpenLayers.Projection(arr_layers[layersChecked[0]].projection));
		  var BBOX = extent.left + " " + extent.bottom + "," + extent.right + " " + extent.top;
		  var fids = jQuery.ajax({
		      type: "GET",
		      url: base_path + "ml_orchestrator.php?action=getBBOXfids&layer_name=" + toplayer.name + "&bbox=" + BBOX ,
		      timeout: 30000,
		      async: false
		    }).responseText;
		  searchWMS = new OpenLayers.Layer.WMS(
		    layername,
		    MapServerURL + '?pid=' + fids + "&",
		    {
		      map: mapfile,
		      transparent: 'true', layers:layername,
		      format: 'image/png',
		      projection: arr_layers[layersChecked[0]].projection,
		      reproject:false,
		      units: "m"
		    },
		    {singleTile: true}
		  );
		  map.addLayer(searchWMS);
		  var maplayers = map.layers.length-4;
		  var wmslayer = map.layers[maplayers];
		  map.raiseLayer(wmslayer,maplayers);
		  twms1.setOpacity(ActiveLayerFillOpacity);

		  jQuery.unblockUI();
	}

}

function resetFeatureStyle(){
try{
	var layer_type = arr_layers[layersChecked[0]].layer_type;
	var toplayer,features;
    if(layer_type == 'POINT' || layer_type == 'MULTIPOINT' ) {
		toplayer = map.layers[map.layers.length -3];
		features = toplayer.features;
		var len = features.length;
		for(var i=0; i<len;i++){
			features[i].style.pointRadius = 15;
		}
		toplayer.redraw();
	}else{
		if (searchWMS != null) {
			map.removeLayer(searchWMS);
			searchWMS = null;
		}
	}
}catch(e){}
}
function getSearchBBOX(event){


  var bbx = event.geometry.bounds;
  var proj;
  if (layersChecked.length > 0) {
  	proj = arr_layers[layersChecked[0]].projection;
  	HightlightFeaturesInBBOX(bbx);

  }else{
  	proj = 'EPSG:4326';
  }
  bbx = bbx.transform(new OpenLayers.Projection(base_map_projection), new OpenLayers.Projection(proj));

  var BBOX = bbx.left + " " + bbx.bottom + "," + bbx.right + " " + bbx.top;


  mls_addBBOX(BBOX);

}

function mls_addBBOX(bbox) {

  if(FABridge.FAB_MultiLayerSearch) {
      var f_MultiLayerSearch = FABridge.FAB_MultiLayerSearch.root();

      f_MultiLayerSearch.addBBOX(bbox);
    } else {
      alert("Error connecting to the Multi Layer Search interface");
    }
}


function mls_ShowOnMap(layer,sids){
    var count = layersChecked.length;
    if (isBBOXSearchON) {
		ActivateBBOX(false);
    }
    if (mls_layers == null) {
    	mls_layers = new Array();
    }
    if (count > 0 ) {
		for( var i = count-1; i >= 0; i--) {
				getData_Category(layersChecked[0],false);

  		}
    }
    var url_layerExtent = base_path + "ml_orchestrator.php?action=getlayerextent&layer_name=" + layer ;
	var bounds = jQuery.ajax({
          type: "GET",
          timeout: 30000,
          url: url_layerExtent,
          async: false
        }).responseText;

    zoomToExtent(bounds);
    if(jQuery.inArray(layer, mls_layers) === -1) {
    	mls_layers.push(layer);
    	getData_Category(layer,true,sids);
    }else{
    	getData_Category(layer,false);
    	getData_Category(layer,true,sids);
	}

}

function mls_RemoveFromMap(layer){
if (isBBOXSearchON) {
		ActivateBBOX(true);
}
if (mls_layers == null) {
	return;
}
if (layer != null) {
	try{
		getData_Category(layer,false);
		var j = 0;
		while (j < mls_layers.length) {
			if (mls_layers[j] == layer) {
				originalArray.splice(j, 1);
			} else { j++; }
		}
	}catch(e){}
	return;
}
var count = mls_layers.length;
if (count > 0) {
		for( var i = 0; i < count; i++) {
		 getData_Category(mls_layers[i],false);
		}
	CurrentTabOption = 1 ;
	reloadCheckedLayers();
	CurrentTabOption = 4;
}
mls_layers = null;
}


//Get current map's url based on the layers checked
function getCurrentMapURL(){
    var temp1 = document.location.href.split("?");
	var temp2 = temp1[0].split("#");
	var mapurl =  temp2[0] + "?layername=";

	var cnt = layersChecked.length;
	for(var i = cnt -1; i>=0; i--){
		if (i == 0) {
			mapurl += layersChecked[i];
		}else{
			mapurl += layersChecked[i] + ",";
		}
	}
	var extent = map.getExtent();
	var BBOX = extent.left + "," + extent.bottom + "," + extent.right + "," + extent.top;
	mapurl += "&BBOX=" + BBOX;
	jQuery("#divMapUrl").show(400);
	document.getElementById("txtMapUrl").value = mapurl;
	jQuery("#closemapurl").click(function(){
    jQuery("#divMapUrl").hide(400);
 	});
}

function ShowGE(){
var layer_extent;
if (layersChecked.length > 0) {
	var bounds  = arr_layers[layersChecked[0]].extent;
	var arr = bounds.split(",");
  	layer_extent = new OpenLayers.Bounds(arr[0], arr[1], arr[2], arr[3]);
  	layer_extent.transform(new OpenLayers.Projection(default_projection), new OpenLayers.Projection(base_map_projection));

}else{
	layer_extent  = map.getExtent();
}

center = layer_extent.getCenterLonLat();


jQuery("#map3dContainer").css("display","block");

jQuery("#map3dContainer").dialog({
    modal: true,
    overlay: {
      opacity: 0.5,
      background: "black"
    },
    zIndex: 2001,
    minheight: '275px',
    minwidth: '600px',
    height: '600px',
    width: '600px',
    maxHeight: '600px',
    maxWidth: '600px',
    title: 'Google Earth View',
    close: function() {
      jQuery("#divModalPopup").empty();
	  try{
		map.events.unregister('move', earth.map, earth.onMove)
		map.events.remove('move');
		earth = null;
		var cell = document.getElementById("map3dContainer");
		try{
		if ( cell.hasChildNodes() )
		{
		    while ( cell.childNodes.length >= 1 )
		    {
		    	if (cell.firstChild.className =='dragHandle') {
					cell.removeChild( cell.firstChild.nextSibling );
		    	}else{
		    		cell.removeChild( cell.firstChild );
		    	}

		    }
		}
		}catch(e){}
		map.removeLayer(map.getLayersByName("earthLayer")[0]);

	}catch(e){}
    }

  });

	earth = new mapfish.Earth(map, 'map3dContainer', {lonLat: center,
                                                          altitude: 50,
                                                          heading: -60,
                                                          tilt: 70,
                                                          range: 700});

}

function ShowProportional(){
	var namedAttrib;
	var indicators;
	var url = base_path + layersChecked[0] + ".json";
	document.getElementById("myChoroplethDiv").innerHTML="";
	if(layersChecked[0] == "lyr_2_ancient_remains") {
		namedAttrib = 'detaljtyp';
		indicators = [['fnr_gdb', 'fnr_gdb']];
		var choropleth = new mapfish.widgets.geostat.ProportionalSymbol({
            'map': map,
            'nameAttribute': namedAttrib,
            'indicators': indicators,
            'url': url
        });
        choropleth.render('myChoroplethDiv');
	}
	jQuery(".olPopup").css("top","210px");
	document.getElementById("choropleth_title").innerHTML = "Proportional Symbols";
	jQuery("#chloroplethPane").show(400);
	jQuery("#closecholropleth").click(function(){
	document.getElementById("myChoroplethDiv").innerHTML="";
	jQuery("#chloroplethPane").hide(400);
    try{
		map.removeLayer(map.getLayersByName("geostat")[0]);
	}catch(e){}
 	});

}

function ShowChloropleth(){
	var namedAttrib;
	var indicators;
	var url = base_path + layersChecked[0] + ".json";
	document.getElementById("myChoroplethDiv").innerHTML="";

	if (layersChecked[0] == "lyr_4_municipality_bounds") {
		namedAttrib = 'kommun_nr';
		indicators = [['area', 'Area'],['perimeter', 'Perimeter']];

	}else if(layersChecked[0] == "lyr_2_ancient_remains") {
		namedAttrib = 'detaljtyp';
		indicators = [['fnr_gdb', 'fnr_gdb']];

	}else{
		alert('Chloropleth not available for this layer');
		return;
	}

	var choropleth = new mapfish.widgets.geostat.Choropleth({
            'map': map,
            'nameAttribute': namedAttrib ,

            'indicators': indicators,

            'url': url,

            'legendDiv' : 'myChoroplethLegendDiv'
        });
        choropleth.render('myChoroplethDiv');

	jQuery(".olPopup").css("top","210px");
	document.getElementById("choropleth_title").innerHTML = "Choropleth Analysis";
	jQuery("#chloroplethPane").show(400);
	jQuery("#closecholropleth").click(function(){
	document.getElementById("myChoroplethDiv").innerHTML="";
	document.getElementById("myChoroplethLegendDiv").innerHTML="";
    jQuery("#chloroplethPane").hide(400);
    try{
		map.removeLayer(map.getLayersByName("geostat")[0]);
	}catch(e){}
 	});

}



function loadRESTfulURLLayer() {
  var BBOX = getUrlParam('BBOX');

  if (BBOX != '') {
  		var extent = new OpenLayers.Bounds.fromString(BBOX);
		map.zoomToExtent(extent);
  }
  var paramSearch = getUrlParam('layername');
  if (paramSearch != '') {
  	var layer_tablenames = paramSearch;
    if (layer_tablenames != '') {
      RemoveCheckedLayers();
      jQuery.cookie('layersChecked', null, {expires: -1});
      var arr_layertblname = layer_tablenames.split(",");
	  var cnt = arr_layertblname.length;
	  for ( var i=0; i< cnt; i++){
		getData_Category(arr_layertblname[i] ,true);
      }
      changeLayerOptions(1);
    }
  }
}


function HighlightFeature(a_obj, a_color, layer) {
  switch (arr_layers[layer].layer_type) {
    case 'POINT':
      var obj = map.getLayersByName(layer);
      if (selectedFeature != null) {
        onPointUnselect(selectedFeature);
      }
      var fid =  layer + ":"+ "REAL|" + a_obj.cells[0].innerHTML.replace("&nbsp;"," ");
      var feature = obj[0].getFeatureById(fid);
      if (feature != null) {
        onPointSelect(feature);
      }
      break;
    case 'POLYGON':
      var fid =  a_obj.cells[0].innerHTML;
      var centriod = a_obj.cells[a_obj.cells.length-1].innerHTML.replace("&nbsp;"," ");
      centriod = centriod.replace("POINT(","");
      centriod = centriod.replace(")","");
      var points = centriod.split(" ");
      var point = new OpenLayers.LonLat(parseFloat(points[0]),parseFloat(points[1]));
      point.transform(new OpenLayers.Projection(arr_layers[layer].projection), new OpenLayers.Projection(base_map_projection));
      drawPolygon(null,layer,point,null);
      break;
    case 'LINE':
      var fid =  a_obj.cells[0].innerHTML;
      var centriod = a_obj.cells[a_obj.cells.length-1].innerHTML.replace("&nbsp;"," ");
      centriod = centriod.replace("POINT(","");
      centriod = centriod.replace(")","");
      var points = centriod.split(" ");
      var point = new OpenLayers.LonLat(parseFloat(points[0]),parseFloat(points[1]));
      point.transform(new OpenLayers.Projection(arr_layers[layer].projection), new OpenLayers.Projection(base_map_projection));
      drawPolygon(null,layer,point,fid);
      break;
  }
}

function getURLForDOD(layer){
  var url;
  var req_type;
  var viewport =  map.getExtent();

  switch(arr_layers[layer.name].layer_type) {
    case 'POINT':
      var features = layer.features;
      var cnt = features.length;
      var fids = '';
      var data, point_type;
      for (var i = 0; i < cnt; i++) {
        if (viewport.containsLonLat(features[i].geometry.getBounds().getCenterLonLat())) {
          data = features[i].id.split(":");
          point_type = data[1].split("|");
          if (point_type[0] == 'REAL') {
            if (i == (cnt - 1)) {
              fids += point_type[1];
            } else {
              fids += point_type[1] + ",";
            }
          }
        }
      }

      if (fids.substring(fids.length -1, fids.length) == "," ) {
        fids = fids.substring(0,fids.length -1);
      }

      url = base_path +"ml_orchestrator.php?action=getDOD&layer_name=" + layer.name + "&fids=" + fids;
      break;
    case 'POLYGON':
    case 'LINE':
      if (CurrentTabOption != SEARCH_OPT && CurrentTabOption != VALIDATION_TAB_OPT) {
        url = base_path +"ml_orchestrator.php?action=getDODForPolygon&layer_name=" + layer.name + "&BBOX=" + getBBOX();
      } else {
        var fids = getLayername(layer.name);
        url = base_path +"ml_orchestrator.php?action=getDOD&layer_name=" + layer.name + "&fids=" + fids;
      }
      break;
  }
  return url;
}

function genDOD(topLayer) {
  blockUI();
  if (topLayer == null) {
      topLayer = getTopLayer();
  }
  var divDOD = document.getElementById(UI_DOD_ID);
  if (topLayer.isBaseLayer == false) {

    if (jQuery.inArray(topLayer.name, skipLayersForDOD) == -1) {
	divDOD.style.display = "block";
	var RowCount = GetFeatureCount(topLayer);

	if (RowCount > 250) {
	    jQuery.unblockUI();
	    divDOD.innerHTML = "Data on Demand cannot be shown as more than 250 features are displayed. Zoom in to fewer features to enable Data on Demand";
	    return;
	}

	if (RowCount == 0) {
	    jQuery.unblockUI();
	    divDOD.innerHTML = "Data on Demand cannot be shown as either no features are displayed or further zooming is required for features to be displayed.";
	    return;
	}

	url = getURLForDOD(topLayer);
	//	alert(url);
	jQuery.ajax({
		type: "GET",
		    url: url,
		    timeout: 30000,
		    ifModified: true,
		    error: function(request,errstring) {
		    jQuery.unblockUI();
		},
		    success: function(res) {
		    jQuery.unblockUI();

		    if (res.length < 424) {
			return;
		    }

		    divDOD.innerHTML = res;
		    var cols = getHiddenCols();

		    jQuery("#dataPresentation").dataTable({
			    "sDom": 'lpif<"dod_wrap"t>r',
				"aoData": cols,
				"fnRowCallback": function(nRow) {

				jQuery(nRow).removeClass("even").removeClass("odd");
				jQuery(nRow).click(function() {

					jQuery('.tblRowHighlight').removeClass('tblRowHighlight');
					jQuery(nRow).addClass('tblRowHighlight');
					HighlightFeature(nRow,'#c9cc99',topLayer.name);
				    });

				return nRow;
			    }
			});
		    // To remove the garbage values from drop down
		    var select = $('#dataPresentation_length select')[0];
		    len=select.length;
		    for(i=0;i<len;i++)
			{
			    if(typeof i== "string")
				{
				    select.remove(i);
				}
			}
		}
	    });
    } else {
	divDOD.innerHTML = "";
	jQuery.unblockUI();
    }
  } else {
      divDOD.innerHTML = "";
      jQuery.unblockUI();
  }
}

function loadSelectedLayer(layer_tablename) {
  //select checkbox
  var current_index, len, raise_by, i, temp, toplayer, newtoplayer;
  if(jQuery('#divModalPopup').dialog('isOpen')) {
    jQuery('#divModalPopup').dialog('close');
  }
  //to reload the treeview if layer is not loaded or checked already
  if ((getTopLayer().name != layer_tablename) && (jQuery.inArray(layer_tablename, layersChecked) == -1)) {
    getData_Category(layer_tablename,true);
  } else {
    addLayerOrderingElemAtTop(layer_tablename, arr_layers[layer_tablename].layer_name, arr_layers[layer_tablename].p_nid,arr_layers[layer_tablename].extent,arr_layers[layer_tablename].access);
  }
  //pop up to show information to the user
  jQuery("#divModalPopup").html("<ul><li>Zoom in to the area on the map where you want to add the new feature</li> <li>Select the \"Draw Feature\" icon from the panel on the top left corner of the map</li> <li>Mark the feature on the map</li> <li>Enter corresponding details in the form</li></ul>");
  jQuery('#divModalPopup').dialog({
    modal: false,
    width:360,
    title:'Add Feature',
    zIndex: 2004,
    overlay: {
      opacity: 0.5,
      background: "black"
    }
  });
}

function flash_reorderLayer(layer_tablename) {
  for (i=0; i<layersChecked.length; i++) {
    if (layersChecked[i] == layer_tablename)
      break;
  }
  reorderLayer(layer_tablename, i, 0);
}

function reorderLayer(layer_tablename, pos_layerOrderOrig, pos_layerOrderNew) {
  var i = 0;

  if(pos_layerOrderNew == 0) {
    var len = map.layers.length;
    if(len > 0) {
      while(map.layers[len-i-1].name != layer_tablename) {
        i++;
      }
    }
  }

  // If top layer is going to be changed
  if (pos_layerOrderOrig == 0 || pos_layerOrderNew == 0) {
    var currentTopLayer;
    var newTopLayer;

    // Get current and new top layer
    if (pos_layerOrderOrig == 0) {
      currentTopLayer = layer_tablename;
      newTopLayer = layersChecked[1];
    } else if (pos_layerOrderNew == 0) {
      currentTopLayer = layersChecked[0];
      newTopLayer = layer_tablename;
    }

    setActiveLayerInfo(arr_layers[newTopLayer]);

    // Remove any popups
    if (vlayer && vlayer.features) {
      vlayer.destroyFeatures(vlayer.features);
      if(map.popups.length>0){
        map.removePopup(map.popups[0]);
      }
    }

    // Unselect any selected point
    if (selectedFeature!=null) {
      //point
      onPointUnselect(selectedFeature);
    }

    // Unselect any selected polygon
    if(selectpolywms != '') {
      onPolygonUnselect();
    }

    // Remove the layer name highlighting of current top layer.
    jQuery('#li' + currentTopLayer).css('color', '');
    jQuery('#li' + currentTopLayer).css('font-weight', '');

    // Highlight the new top layer name
    jQuery('#li' + newTopLayer).css('color', 'red');
    jQuery('#li' + newTopLayer).css('font-weight', 'bold');

    /* If current top layer is point layer, deactivate the select control for the current top layer */
    if (arr_layers[currentTopLayer].layer_type == 'POINT' && arr_selectControl[currentTopLayer]) {
      arr_selectControl[currentTopLayer].deactivate();
    }

    /* if the new top layer is point layer, activate the select control for new top layer */
    if (arr_layers[newTopLayer].layer_type =='POINT' && arr_selectControl[newTopLayer]) {
      arr_selectControl[newTopLayer].activate();
    }

    // Blur the current top layer
    (map.getLayersByName(currentTopLayer)[0]).setOpacity(InActiveLayerFillOpacity);

    // Set the opacity of new top layer
    (map.getLayersByName(newTopLayer)[0]).setOpacity(ActiveLayerFillOpacity);

    // Generate legend for new top layer.
    generateLegend(newTopLayer);

    //set download icon
    if(parseInt(arr_layers[newTopLayer].access)) { // download access
       jQuery("#downloadLayer").css("display", "block");
    } else {
         //no access
         jQuery("#downloadLayer").css("display", "none");
    }

    // Set the OpenLayers toolbar control panel based on access rights
    setOLControlPanel(newTopLayer);
  }

  // Reorder in layersChecked array
  layersChecked.splice(pos_layerOrderOrig, 1);
  layersChecked.splice(pos_layerOrderNew, 0, layer_tablename);
  if (CurrentTabOption != SEARCH_OPT && CurrentTabOption != VALIDATION_TAB_OPT ) {
    setLayersCheckedCookie();
  }

  // Reorder the layers in OL map.
  map.raiseLayer((map.getLayersByName(layer_tablename)[0]), pos_layerOrderOrig - pos_layerOrderNew + i);
   //zoom to top layer's extent
   if (pos_layerOrderOrig == 0 || pos_layerOrderNew == 0){
	    var bounds = arr_layers[layersChecked[0]].extent
	    var arr = bounds.split(",");
		var ext = new OpenLayers.Bounds(arr[0], arr[1], arr[2], arr[3]);
		ext.transform(new OpenLayers.Projection(default_projection), new OpenLayers.Projection(base_map_projection));
		if (map.getZoomForExtent(ext, true) > map.getZoom()){
		   	zoomToExtent(bounds);
		}else{
		  if (map.getExtent().intersectsBounds(ext, true) == false) {
		      zoomToExtent(bounds);
	      }
	    }
    }
}

function getHiddenCols() {
  try {
    var col_length = document.getElementById("tbl_cols").childNodes.length;
    var arrCols = new Array();
    for (var i=0; i< col_length; i++) {
      if ((i == 0) || (i == (col_length - 1))) {

      arrCols[i] = { "sClass": "hidecol" };
      } else {
        arrCols[i] = null;
      }
    }

    return arrCols;
  } catch(e) {
  }
}

function contribute() {
  if (selectedFeature != null) {
    //point
    onPointUnselect(selectedFeature);
  }
  if(selectpolywms != '')
    onPolygonUnselect();
  if(user_id){
  var myurl = base_path+'ml_orchestrator.php?action=getParticipatoryLayers';
  jQuery.ajax({
    url:  myurl,
    type: 'GET',
    timeout: 30000,
    error: function(request,err) {
      jQuery.unblockUI();
    },
    success: function(resp) {
      //pass load to differentiate between upload data and add feature
      var popuphtml = getLayerListPopup(resp,'load');
      jQuery("#divModalPopup").html(popuphtml);
    }
  });
  } else {
     jQuery("#divModalPopup").html("You need to login to contribute");
  }
  jQuery('#divModalPopup').dialog({
        modal: false,
        width:360,
        title:'Contribute',
        zIndex: 2004,
        overlay: {
          opacity: 0.5,
          background: "black"
        }
  });
}

function getDownloadFormats(layer_tablename){
  if(user_id){
    blockUI();
    var toplayer = layersChecked[0];
    var lnk = base_path+'ml_orchestrator.php';
    if(layer_tablename)
      var var_data = 'action=getDownloadFormats&layer_tablename='+layer_tablename;
    else
      var var_data = 'action=getDownloadFormats&layer_tablename='+toplayer;
    jQuery.ajax({
      url: lnk,
      type: 'GET',
      timeout: 30000,
      data: var_data,
      error: function(request,err) {
        jQuery.unblockUI();
      },
      success: function(resp){
        var jsonObj = eval('(' + resp + ')');
        for(key in jsonObj) {
          if('error' == key){
           jQuery("#divModalPopup").html(jsonObj[key]);
          } else {
            var format = jsonObj[key];
            var arr_format = format.split(",");
            var arr_format_len = arr_format.length;
            var html = "Choose one of the following format to download the layer <br>";
            for( var i=0; i< arr_format_len;i++){
               html += '<input name = "download" type="radio" value="'+arr_format[i]+'">'+arr_format[i]+'</option><br/>';
            }
            if(layer_tablename)
              html+= '<input type="button" id="downloadLink" value="Download" onClick="downloadLayer(\''+layer_tablename+'\')";';
            else
              html+= '<input type="button" id="downloadLink" value="Download" onClick="downloadLayer(\''+toplayer+'\')";';
            html += '<iframe id = "downloadIframe" src="" style="display:none"/>';
            jQuery("#divModalPopup").html(html);
          }
          jQuery.unblockUI();
        }
      }
    });
  } else {
      jQuery("#divModalPopup").html("You have to login to download this layer");
  }
  jQuery('#divModalPopup').dialog({
    modal: true,
    zIndex: 2004,
    overlay: {
      opacity: 0.5,
      background: "black"
    }
  });
}

function downloadLayer(layer_tablename){
  if(user_id){
    blockUI();
    var toplayer = layersChecked[0];
    var lnk = base_path+'ml_orchestrator.php';
    var radioElement = document.getElementsByName('download');
    var radioElementLen = radioElement.length;
    for (var i=0; i < radioElementLen; i++) {
       if (radioElement[i].checked) {
          var format = radioElement[i].value;
       }
    }
    if(format){
      blockUI();
      if(layer_tablename)
        var var_data = 'action=getDownloadUrl&layer_tablename='+layer_tablename+'&format='+format;
      else
        var var_data = 'action=getDownloadUrl&layer_tablename='+toplayer+'&format='+format;
      jQuery.ajax({
        url: lnk,
        type: 'GET',
        timeout: 30000,
        data: var_data,
        complete: function(XMLHttpRequest, textStatus){
         jQuery.unblockUI();
        },
        error: function(request,err) {
          jQuery.unblockUI();
        },
        success: function(resp){
          var jsonObj = eval('(' + resp + ')');
          for(key in jsonObj) {
            if("error" == key) {
             jQuery("#divModalPopup").html(jsonObj[key]);
            }else {
              //if not error
              document.getElementById('downloadIframe').src = jsonObj['url'];
              if(jQuery('#divModalPopup').dialog('isOpen')) {
                jQuery('#divModalPopup').dialog('close');
              }
            }
          }
        }
     });
    } else {
    	 alert('Please select one of the options');
    }
  } else {
  	 jQuery.unblockUI();
     jQuery("#divModalPopup").html("You have to login to download this layer");
  }
}



function Rowselect(a_obj, a_color) {
  a_obj.style.backgroundColor=a_color;
}

function Rowunselect(a_obj, a_color) {
  a_obj.style.backgroundColor=a_color;
}


function showLegendForSearch(layer_tablename,layer_name) {
  if(arr_layers[layer_tablename].layer_type == "POINT") {
    jQuery("#"+UI_LEGEND_ID).html('<img src ="'+arr_layers[layer_tablename].icon+'"></img><span style="color: red; font-size: 12pt;font-weight:bold">'+layer_name+'</span>');
  } else if (arr_layers[layer_tablename].layer_type == "POLYGON") {
    legend = document.getElementById(UI_LEGEND_ID);
    legend.innerHTML = '<span style="color: red; font-size: 12pt;font-weight:bold">'+layer_name+'</span>';
  }
}

function submitenter(e) {
  var keycode;
  if (window.event) keycode = window.event.keyCode;
  else if (e) keycode = e.which;
  else return true;

  if (keycode == 13) {
    search_data(true);
    return false;
  }
  else
    return true;
}



function showSymbology(){
  /* check if there are filterby columns for the top layer */
  var currentactivelayer = layersChecked[0];
  LoadSymbology(currentactivelayer);
}

function toggleControl(element) {
	for(key in measureControls) {
        var control = measureControls[key];
        if(element.value == key && element.checked) {
           control.activate();
        } else {
           control.deactivate();
    	}
	}
	var element = document.getElementById('output');
	element.innerHTML ='';
}

function toggleGeodesic(element) {
    for(key in measureControls) {
        var control = measureControls[key];
        control.geodesic = element.checked;
    }
}
function handleMeasurements(event) {
            var geometry = event.geometry;
            var units = event.units;
            var order = event.order;
            var measure = event.measure;
            if (units == "m") {
            	if (order == 1) {
            		measure = parseFloat(measure)/1000;
            		units = "km";
            	}else{
            		measure = parseFloat(measure)/1000000;
            		units = "km";
            	}

            }
            var element = document.getElementById('output');
            var out = "<table width='100%' class='measurement' >";
            var val;
            out += "<thead ><tr><th><b>Unit</b></th><th><b>Measure</b></th></tr></thead>";
            out += "<tbody >";
            if(order == 1) {
            	// indicates distance and unit km

            		out += "<tr>";
            			out += "<td>" + units + "</td>";
            			out += "<td>" + measure.toFixed(3) + "</td>" ;
	           		out += "</tr>";
	           		out += "<tr>";
            			out += "<td>yards</td>";
            			val = parseFloat(measure) * 1093.613;
            			out += "<td>" + val.toFixed(3) + "</td>" ;
	           		out += "</tr>";
	           		out += "<tr>";
            			out += "<td>miles</td>";
            			val = parseFloat(measure) * 0.6213712;
            			out += "<td>" + val.toFixed(3) + "</td>" ;
	           		out += "</tr>";

            } else {
            	// indicates area and unit sq.km
            		out += "<tr>";
            			out += "<td>" + units + "<sup>2</sup></td>";
            			out += "<td>" + measure.toFixed(3) + "</td>" ;
	           		out += "</tr>";
	           		out += "<tr>";
            			out += "<td>miles<sup>2</sup></td>";
            			val = parseFloat(measure) * 0.3861021;
            			out += "<td>" + val.toFixed(3) + "</td>" ;
	           		out += "</tr>";
	           		out += "<tr>";
            			out += "<td>Acres</td>";
            			val = parseFloat(measure) * 247.1053814;
            			out += "<td>" + val.toFixed(3) + "</td>" ;
	           		out += "</tr>";
	           		out += "<tr>";
            			out += "<td>Hectares</td>";
            			val = parseFloat(measure) * 100;
            			out += "<td>" + val.toFixed(3) + "</td>" ;
	           		out += "</tr>";

            }
            out += "</tbody>";
			out += "</table>";
            element.innerHTML = out;
}


function showMeasurementTool(){
  var strMeasurement='';
  var str = '';

  strMeasurement += '<table height="80" width="100%" style="margin-top: 10px; padding-bottom: 10px; "><tr><td>';
  strMeasurement += '<ul id="controlToggle">';
  strMeasurement += '</td></tr>';
  strMeasurement += '<tr><td>';
  strMeasurement += '<li>';
  strMeasurement += '<input type="radio" name="type" value="none" id="noneToggle" onclick="toggleControl(this);"  />';
  strMeasurement += '<label for="noneToggle">&nbsp;Navigate(change Map view)</label>';
  strMeasurement += '</li>';
  strMeasurement += '</td></tr>';
  strMeasurement += '<tr><td>';
  strMeasurement += '<li>';
  strMeasurement += '<input type="radio" name="type" value="line" id="lineToggle" onclick="toggleControl(this);" checked="checked"/>';
  strMeasurement += '<label for="lineToggle">&nbsp;Measure Distance</label>';
  strMeasurement += '</li>';
  strMeasurement += '</td></tr>';
  strMeasurement += '<tr><td>';
  strMeasurement += '<li>';
  strMeasurement += '<input type="radio" name="type" value="polygon" id="polygonToggle" onclick="toggleControl(this);" />';
  strMeasurement += '<label for="polygonToggle">&nbsp;Measure Area</label>';
  strMeasurement += '</li>';
  strMeasurement += '</td></tr>';
  strMeasurement += '<tr><td>';
  strMeasurement += '</ul>';
  strMeasurement += '</td></tr></table>';
  var stroutput = '<div  id="output"></div>';
  str += "<table width='100%'>";
		str += "<tr>";
			str += "<td>";
				str += "<div style='font-size:10px'><I>";
				str += "To measure distance/area, single click on the map to start the marking and double click to stop, after selecting an appropriate option from the list below";
				str += "</I></div>";
			str +="</td>"
		str += "</tr>";
		str += "<tr>";
			str += "<td>";
				str += strMeasurement;
			str +="</td>"
		str += "</tr>";
		str += "<tr>";
			str += "<td align='center'>";
				str += "<hr>";
				str += stroutput;
			str +="</td>"
		str += "</tr>";
  str += "</table>";

  jQuery("#measurement").html(str);
  jQuery("#output").css("float", "left");
  jQuery("#output").css("width","360px");

  jQuery("#measurement").css("display","block");
	jQuery("#measurement").css("top","20px");
	jQuery("#measurement").css("left","0px");
	jQuery("#measurement").dialog({
	    modal: false,
	    zIndex: 2001,
	    height: '350px',
	    width: '400px',
	    position: [850, 140],
	    title: 'Measurement Tool',
	    close: function() {
	    			closeMeasurementTool();
    	       }
    });
            var sketchSymbolizers = {
                "Point": {
                    pointRadius: 4,
                    graphicName: "square",
                    fillColor: "white",
                    fillOpacity: 1,
                    strokeWidth: 1,
                    strokeOpacity: 1,
                    strokeColor: "#333333"
                },
                "Line": {
                    strokeWidth: 3,
                    strokeOpacity: 1,
                    strokeColor: "#666666",
                    strokeDashstyle: "dash"
                },
                "Polygon": {
                    strokeWidth: 2,
                    strokeOpacity: 1,
                    strokeColor: "#666666",
                    fillColor: "white",
                    fillOpacity: 0.3
                }
            };
            var style = new OpenLayers.Style();
            style.addRules([
                new OpenLayers.Rule({symbolizer: sketchSymbolizers})
            ]);
            var styleMap = new OpenLayers.StyleMap({"default": style});


            measureControls = {
                line: new OpenLayers.Control.Measure(
                    OpenLayers.Handler.Path, {
                        persist: true,
                        handlerOptions: {
                            layerOptions: {styleMap: styleMap}
                        }
                    }
                ),
                polygon: new OpenLayers.Control.Measure(
                    OpenLayers.Handler.Polygon, {
                        persist: true,
                        handlerOptions: {
                            layerOptions: {styleMap: styleMap}
                        }
                    }
                )
            };

            var control;
            for(var key in measureControls) {
                control = measureControls[key];
                control.events.on({
                    "measure": handleMeasurements,
                    "measurepartial": handleMeasurements
                });
                map.addControl(control);
                control.geodesic = true;
            }
	    //set Measure Distance option by default instead of navigate
            var c= document.getElementById('lineToggle');
            toggleControl(c);
}

function closeMeasurementTool(){
	jQuery("#measurement").css("display","none");
	 for(key in measureControls) {
       var control = measureControls[key];
	   control.deactivate();

     }
}

function ZoomIn(feature,BBOX) {
  var vpoint = feature.geometry.getBounds().getCenterLonLat();
  onPointUnselect(feature);
  var points = BBOX.split(",");
  var coord =  new Array(2);
  var x = points[0].split(" ");
  var y = points[1].split(" ");
  var str = x[0] + "," + x[1] + "," + y[0] + "," + y[1];
  var extent = new OpenLayers.Bounds(parseFloat(x[0]),parseFloat(x[1]), parseFloat(y[0]), parseFloat(y[1]));
  extent.transform(new OpenLayers.Projection(default_projection), new OpenLayers.Projection(base_map_projection));
  map.zoomIn();
  map.setCenter(vpoint);
}

var clearPopupUIMenu = function() {
  lnk = "ml_orchestrator.php?action=clearPopupUIMenu";
  jQuery.ajax({
    url: lnk,
    timeout: 30000,
    error: function(request,err) {
    },
    success: function(resp) {
    }
  });
}

function getLayername(layer_tablename) {
  return jQuery("#divThemes").find("input[value='" + layer_tablename + "']").attr('name');
}

function checkLayerInfoExists(layer_tablename) {
  var obj_layerInfo;
	if (!arr_layers[layer_tablename]) {
	  obj_layerInfo = getLayerInfo(layer_tablename);
	  if (obj_layerInfo == false) {
      alert("ERROR: Unable to fetch layer info. Please try after sometime.");
      return false;
	  }

	  arr_layers[layer_tablename] = obj_layerInfo;
  } else {
    obj_layerInfo = arr_layers[layer_tablename];
  }
  return obj_layerInfo;
}

function showTimeLineDataUI(layer_tablename, layer_name) {
  blockUI();

  src = base_path + "TimeLineDataSelector/TimeLineDataSelector.swf";
  flashVars = 'basePath=' + base_path;
  flashVars += '&dataFile=ml_data.php';
  flashVars += '&layer_tablename=' + layer_tablename;
  flashVars += '&layer_name=' + layer_name;
  flashVars += '&JSresizeFunc=resizeFlashParentDiv';
  flashVars += '&divid=layerSelectPane';
  flashVars += '&deltaw=200';
  flashVars += '&deltah=200';

  var html = getFlashAppAddScript(src, "fTimeLineDataSelector", flashVars);
  jQuery('#timelinedata_popup').dialog('destroy').remove();
  var infopopup = document.createElement('div');
  infopopup.setAttribute('id','timelinedata_popup');
  infopopup.setAttribute('style','height: 400px; width: 500px');
  document.body.appendChild(infopopup);
  infopopup.innerHTML = html;
  var lft = tp = ht = wd = 0;
  var mp = jQuery('#map');
  ht = mp.height() * 0.8;
  wd = mp.width() * 0.85;
  lft = mp.offset().left + ((mp.width()-wd)/2);
  tp = mp.offset().top + ((mp.height()-ht)/2);
  var title = "";
  if (arr_layers[layer_tablename]) {
    title = arr_layers[layer_tablename].layer_name;
  } else {
    title = jQuery("#divThemes").find("input[value='" + layer_tablename + "']").attr('id');
  }
  jQuery('#timelinedata_popup').dialog({
    height: ht+'px',
    width: wd+'px',
    maxHeight: ht+'px',
    maxWidth: wd+'px',
    position: [lft, tp],
    title: title,
    zIndex: 2004
  });
  jQuery.unblockUI();
}

function showTimeLineData(layer_tablename, col_name, start_date, end_date, keep_data) {
  jQuery('#timelinedata_popup').dialog('destroy').remove();
  var sids = null;
  var url = base_path+'ml_orchestrator.php?action=getFeaturesForTimeLine&layer_tablename='+layer_tablename+ "&BBOX=" + getBBOX()+'&tlCol='+col_name+'&tlStartDate='+start_date+'&tlEndDate='+end_date;

  if(arr_layers[layer_tablename].layer_type != 'POINT') {
     getData_Category(layer_tablename,false);
     sids = jQuery.ajax({
              type: "GET",
              timeout: 30000,
              url: url,
              async: false
     }).responseText;
     getData_Category(layer_tablename,true,sids);
  } else {
    DisplayLayer(layer_tablename, null, null, sids, col_name, start_date, end_date, keep_data);
  }

}

function startLayerGroupSlideShow() {
  disableTools(true);
  lyrGrpSlideShowStarted = true;
  hldrLayerChecked = copyArray(layersChecked);
  RemoveCheckedLayers();
}

function endLayerGroupSlideShow() {
  disableTools(false);
  if(lyrGrpSlideShowStarted) {
    RemoveCheckedLayers();
    lyrGrpSlideShowStarted = false;
    layersChecked = copyArray(hldrLayerChecked);
    setLayersCheckedCookie();
    reloadCheckedLayers();
  }
}


function getBaseMapURL(){
	var base_layer = document.getElementById('ddlBaseLayer').value;
	var google_layer_type = '';
	var IsGoogle = true ;
	var base_map_url = '';
	switch(base_layer){
		case 'Google Physical':
			google_layer_type = 'terrain';
			break;
		case 'Google Satellite':
			google_layer_type = 'satellite';
			break;
		case 'Google Hybrid':
			google_layer_type = 'hybrid';
			break;
		case 'Google Streets':
			google_layer_type = 'roadmap';
			break;
		default:
			IsGoogle = false;

	}
	if (IsGoogle) {
		var toplayer = getTopLayer();
		var center_coord = toplayer.map.getExtent().getCenterLonLat();
		center_coord.transform(new OpenLayers.Projection(base_map_projection), new OpenLayers.Projection(default_projection));
		var zoomlevel = map.getZoomForExtent(toplayer.map.getExtent(), true);
		if (zoomlevel>1) {
			zoomlevel  = zoomlevel - 1;
		}else{
			zoomlevel = 1;
		}

		base_map_url += GOOGLE_BASE_URL;
		base_map_url += '&center=' +  center_coord.lat + ',' + center_coord.lon;
		base_map_url += '&zoom=' + zoomlevel  ;
		base_map_url += '&size=640x366';
		base_map_url += '&maptype=' + google_layer_type;
		base_map_url += '&key=' + googleApiKey ; // fetched from config.xml
		base_map_url += '&sensor=false';
	}else{
		// to do : instead of hard coding this name it should be picked from config.xml/read config and stored in a variable
		var base_layer = map.getLayersByName('lyr_00_worldmap')[0];
		base_map_url =  base_layer.getFullRequestString({
	            REQUEST: "GetMap",
	            BBOX: toplayer.map.getExtent().toBBOX(),
	            singleTile: true,
	            WIDTH: 640,
	            HEIGHT:366
	        });

	}
	return base_map_url;

}
function createPDF(){
	//
	var tempHTML = '<table align="center"><tr><td>Plese wait while the pdf is being generated ....</td></tr>';
	tempHTML += '<tr><td><img src="'+ base_path +'sites/all/modules/map/images/ajax-loader.gif" /></td></tr>';
	tempHTML += '</table>';
	jQuery("#pdf").html(tempHTML);
	var toplayer = getTopLayer();
    var url =  toplayer.getFullRequestString({
            REQUEST: "GetMap",
            BBOX: toplayer.map.getExtent().toBBOX(),
            singleTile: true,
            WIDTH: 640,
            HEIGHT:366
        });


	var JSONObject = new Object();
	JSONObject.BaseUrl = getBaseMapURL();
   	JSONObject.Layer = layersChecked[0];
   	JSONObject.LayerName = arr_layers[layersChecked[0]].layer_name;
   	JSONObject.TopScale = jQuery(".olControlScaleLineTop")[0].innerHTML;
   	JSONObject.BottomScale = jQuery(".olControlScaleLineBottom")[0].innerHTML;
   	JSONObject.LayerURL = url;
    JSONObject.LegendURL = MapServerURL + '?mode=legend&map=' + map.getLayersByName(toplayer.name)[0].params.MAP + '&layer=' + toplayer.name;
   	JSONObject.LayerURL = JSONObject.LayerURL.replace(/&/g, ' and ');
   	JSONObject.LegendURL = JSONObject.LegendURL.replace(/&/g, ' and ');
   	JSONObject.BaseUrl = JSONObject.BaseUrl.replace(/&/g, ' and ');
    JSONstring = JSON.stringify(JSONObject);
    jQuery.ajax({
      		url:  base_path + "printPDF.php",
      		type: 'GET',
      		timeout: 30000,
      		data: "action=printPDF&json_PDF=" + JSONstring ,
      		error: function(request,err) {
        		//jQuery.unblockUI();
      		},
      		success: function(resp) {
				var jsonObj = eval('(' + resp + ')');
				var strhtml = '<A align="center" HREF="'+ jsonObj['pdf_url'] +'" TARGET="_blank">Open pdf in a new window </A>';
        		jQuery("#pdf").html(strhtml);

            }

	});
}
//function for showing print pdf feature
function showpdf(){
	var strHTML = '';
	strHTML += '<table align="center">';
		strHTML += '<tr>';
			strHTML += '<td>';
				strHTML += '<b>Layer: '+ arr_layers[layersChecked[0]].layer_name +'  </b>';
			strHTML += '</td>';
		strHTML += '</tr>';
		strHTML += '<tr>';
			strHTML += '<td>';
			strHTML += '&nbsp;';
			strHTML += '</td>';
		strHTML += '</tr>';
	if(arr_layers[layersChecked[0]].layer_type == 'POINT') {
	    strHTML += '<tr>';
			strHTML += '<td>';
				strHTML += 'Print PDF option is not supported for point layers currently...';
			strHTML += '</td>';
		strHTML += '</tr>';
	}else{
		strHTML += '<tr>';
			strHTML += '<td>';
				strHTML += 'Click';
				strHTML += '&nbsp;<input type="button" onclick="createPDF();" value="Print">&nbsp;';
				strHTML += 'to create a pdf';
			strHTML += '</td>';
		strHTML += '</tr>';
	}
	strHTML += '<table>';
	jQuery("#pdf").html(strHTML);
	jQuery('#pdf').dialog({
				    height: '150px',
				    width: '350px',
				    position: [850, 140],
				    title: 'Print PDF',
				    zIndex: 2004
	});


}

/***** Map Locator Features Functionality ends *****/

// call InitializeMap on document load . This code has been added at the end of the file in order make it work in IE
blockUI();
jQuery(document).ready (
  function() {
    if(jQuery('#map').length != 0) {
      InitializeMap();
    }
    window.onresize = resizeMap;

    jQuery("#layerData-1").click(function() {
        genDOD();
    });

    clearLayerOrdering();

    reloadCheckedLayers();
    loadRESTfulURLLayer();
    jQuery.unblockUI();
  }
);

