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
 Contains code to generate UI for map page
***/

// $Id: page.tpl.php,v 1.18 2008/01/24 09:42:53 goba Exp $
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language ?>" lang="<?php print $language->language ?>" dir="<?php print $language->dir ?>">
  <head>
    <title>Map Locator</title>
    <?php print $head ?>
    <?php print $styles ?>
    <?php print $scripts ?>
    <?php flush(); ?>
  </head>
	<body id="mapPage">
    <!-- popups -->

    <!-- movable popups -->

    <!-- Layer select -->
    <div id="layerSelectPane" class="mapPane">
      <div class="dragHandle">
        <div class="closePane"><a href="#" class="flashPanelToggler" id="layerSelect-2"><img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/icons/close.png" alt="Close" title="Close" class="close"/></a></div>
        <h2>Show / Hide Layers</h2>
      </div>
      <div>
        <ul id="layerGroupType">
          <li id="layerOption1"><a href="javascript:changeLayerOptions(1)" title="View thematic organization of maps">Layers By Theme</a></li>
          <li id="layerOption2"><a href="javascript:changeLayerOptions(2)" title="View location-wise organization of maps">Layers By Geography</a></li>
          <li id="layerOption3"><a href="javascript:changeLayerOptions(3)">Participative Layers</a></li>
          <!--<li id="layerOption4"><a href="javascript:changeLayerOptions(4)">Search</a></li>-->
          <li id="layerOption5" style="display:none"><a href="javascript:changeLayerOptions(5)">Validate Layers</a></li>
          <li id="layerOption6" style="display:none"><a href="javascript:changeLayerOptions(6)">Inactive Layers</a></li>
        </ul>
        <div id="layerTree">
          <div id="divThemes"></div>
        </div>
      </div>
    </div>
    <!-- Layer select ends -->

    <!-- Layer Ordering -->
    <div id="layerOrderPane" class="mapPane" style="display:block;z-index:0">
      <div class="dragHandle">
        <div class="closePane"><a href="#" class="flashPanelToggler" id="layerOrder-2"><img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/icons/close.png" alt="Close" class="close"/></a></div>
        <h2>Layer Ordering</h2>
      </div>
      <!--<div id="layerOrder" class="displayPane">-->
      <div style="margin:20px;overflow:hidden;">
        <?PHP
          $src = base_path() . "LayerOrdering/LayerOrdering.swf";
          $js = '<script type="text/javascript">';
          $js .= 'document.write(getFlashAppAddScript("'.$src.'", "fLayerOrdering", "bridgeName=FAB_LayerOrdering"));';
          $js .= '</script>';
          print $js;
          flush();
        ?>
      </div>
    </div>
    <!-- Layer Ordering ends -->

    <!-- Search -->
    <!--
    <div id="mainMapSearchPane" class="mapPane resizeablePane">
      <div class="dragHandle">
        <div class="closePane"><a href="#" class="panelToggler" id="mainMapSearch-2"><img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/icons/close.png" alt="Close" class="close"/></a></div>
        <h2>Search</h2>
      </div>

      <div id="search" style="display: block;" class="displayPane">
        <input type="text" class="searchInput" onkeypress="submitenter(event)" id="txtSearch"/> <input type="image" class="searchButton" alt="Find" src="/MLOCATE/sites/all/themes/mlocate/images/icons/search.png" onclick="search_data(this)"/>
      </div>
      <div id="mainMapSearchResults"></div>

    </div>
    -->
    <!-- Search ends -->

    <!-- Legend -->
    <div id="mapLegendPane" class="mapPane resizeablePane">
      <div class="dragHandle">
        <div class="closePane"><a href="#" class="panelToggler" id="mapLegend-2"><img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/icons/close.png" alt="Close" class="close"/></a></div>
        <h2>Legend</h2>
      </div>
      <div id="legend" class="displayPane" style="display:block;"></div>
    </div>
    <!-- Legend ends -->

    <!-- Layer Data -->
    <div id="layerDataPane" class="mapPane">
      <div class="dragHandle">
        <div class="closePane"><a href="#" class="panelToggler" id="layerData-2"><img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/icons/close.png" alt="Close" class="close"/></a></div>
        <h2>Layer Data</h2>
      </div>
      <div id="dataOnDemand" class="displayPane"></div>
    </div>
    <!-- Layer Data ends -->

    <div id="divModalPopup">
    </div>
    <div id="divMultiLayerSearchPopup">
    </div>
    <!-- movable popups end -->

    <!-- User login -->
    <?php if(! $user->uid): ?>
      <div id="userLogin">
        <div id="loginClose"><a href="#" id="loginCloseButton"><img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/icons/close.png" alt="Close"/></a></div>
        <?php if($user_login): ?>
          <?php print $user_login;?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <!-- User login ends -->

    <!-- Messages -->
    <?php if($show_messages && $messages): ?>
      <div id="messages">
        <div id="closeMessagesButton"><a href="#" id="closeMessage"><img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/icons/close.png" alt="Close"/></a></div>
        <?php print $messages;?>
      </div>
    <?php endif; ?>
    <!-- Messages ends -->

    <!-- divChloropleth -->
    <div id="chloroplethPane" class="mapPane">
      <div class="dragHandle">
        <div class="closePane"><a href="#" class="panelToggler" id="chloropleth-2"><img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/icons/close.png" alt="Close" class="close"/></a></div>
        <h2>Chloropleth Analysis</h2>
      </div>
      <div style="padding-top:20px font-color:#A75030">
        <div id="myChoroplethDiv"></div>
        <div id="myChoroplethLegendDiv"></div>
      </div>
    </div>
    <!-- divChloropleth ends -->

    <div id="symbology"></div>
    <div id="measurement"></div>
    <div id="colorpicker301" class="colorpicker301"></div>
    <!-- map3dContainer -->
    <div id="map3dContainer" >
      <!--
      <div class="dragHandle">
        <div class="closePane"><a href="#" class="panelToggler" id="closeGE"><img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/icons/close.png" alt="Close" class="close"/></a></div>
      </div>
      -->
    </div>
    <!-- map3dContainer ends -->

    <!-- divMapUrl -->
    <div id="divMapUrl" >
      <div class="closePane" style="float:right"><a href="#" class="panelToggler" id="closemapurl"><img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/icons/close.png" alt="Close" class="close"/></a></div>
      <div style="padding-top:10px">
        <label for='txtMapUrl' color="#A75030" style="font-size:10pt"> <b><font color="#A75030">URL:</font> </b> </label>
        <input id="txtMapUrl" type="text" color ="#A75030" style="font-size:10pt;width:540px">
      </div>
    </div>
    <!-- divMapUrl ends -->

    <!-- popups end -->

    <!-- wrapper -->
		<div id="wrapper">
      <!-- tools -->
      <div id="tools">
        <!-- Branding -->
        <div id="mapBranding" style="width:0px;">
          <!-- Logo -->
          <div id="logo">
            <a href="<?php print check_url($front_page)?>">
              <div class="icon"></div>
            </a>
          </div>
          <!-- Logo ends -->
        </div>
        <!-- Branding ends -->

        <!-- mainToolBar -->
        <div id="mainToolBar" class="toolbar">
          <div class="tool" title="Explore Layers">
            <a href="#" class="flashPanelToggler" id="layerSelect-1">
              <div style="float: left;">
                <div class="icon"></div>
              </div>
              <div>
                Explore
              </div>
              <div style="clear: both;"> </div>
            </a>
          </div>
          <div class="tool" id="btn_contribute" title="Contribute">
            <a href="#">
              <div style="float: left;">
                <div class="icon"></div>
              </div>
              <div>
                Contribute
              </div>
              <div style="clear: both;"> </div>
            </a>
          </div>
          <!--
          <div class="tool" title="To be implemented" style="display:none;">
            <a href="#">Search</a>
          </div>
          -->
          <div class="tool" id="btn_search" title="Search">
            <a href="#">
              <div style="float: left;">
                <div class="icon"></div>
              </div>
              <div>
                Search
              </div>
              <div style="clear: both;"> </div>
            </a>
          </div>

          <div class="tool" id="lnkmeasurement" title="Measurement Tool">
            <a href="#">
              <div style="float: left;">
                <div class="icon"></div>
              </div>
              <div>
                 Measure
              </div>
              <div style="clear: both;"> </div>
            </a>
          </div>
          <!--<div class="icon" title="Map Search"><a href="#" class="panelToggler" id="mainMapSearch-1"><img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/icons/search-icon.png" alt=""/>Search</a></div>-->
        </div>
        <!-- mainToolBar ends -->

        <!-- advancedToolSet -->
        <div id="advancedToolSet" style="display:none;">
          <!-- activeLayer -->
          <div id="activeLayer" class="toolbar">
            <div class="tool first last">
              <img src="" alt=""/>
              <a href="#" title="Zoom to Layer Extent"><img src="<?php print check_url($front_page)?><?php print check_url(path_to_theme())?>/images/icons/zoom-to-extent-icon.png" alt="Zoom to Layer Extent"/></a>
              <a href="#" title="Show layer information"></a>
            </div>
          </div>
          <!-- activeLayer end -->


          <!-- advanced -->
          <div id="advanced" class="toolbar">
            <div class="tool first more">
              <a href="#" >More</a>
            </div>
            <div class="tool optional" title="Legend">
              <a href="#" class="panelToggler" id="mapLegend-1">
                <div style="float: left;">
                  <div class="icon"></div>
                </div>
                <div>
                  Legend
                </div>
                <div style="clear: both;"> </div>
              </a>
            </div>
            <div class="tool optional" title="Layer Data">
              <a href="#" class="panelToggler" id="layerData-1">
                <div style="float: left;">
                  <div class="icon"></div>
                </div>
                <div>
                  Layer Data
                </div>
                <div style="clear: both;"> </div>
              </a>
            </div>
            <div class="tool optional" title="Layer Info">
              <a href="#" id="anc_LayerInfo">
                <div style="float: left;">
                  <div class="icon"></div>
                </div>
                <div>
                  Layer Info
                </div>
                <div style="clear: both;"> </div>
              </a>
            </div>
            <!--
            <div class="tool optional" title="Plot Charts" style="display:none">
              <a href="#" id="anc_LayerCharts">
                <div style="float: left;">
                  <div class="icon"></div>
                </div>
                <div>
                  Plot Charts
                </div>
                <div style="clear: both;"> </div>
              </a>
            </div>
            -->
            <div class="tool optional">
              <div title="View in Google Earth" id="iconShowGE" style="display:none">
                <a href="#" onclick="ShowGE()">
                  <div style="float: left;">
                    <div class="icon"></div>
                  </div>
                  <div>
                   Earth View
                  </div>
                  <div style="clear: both;"> </div>
                </a>
              </div>
            </div>
            <!--
            <div class="tool optional">
              <div title="Chloropleth" id="iconShowChloropleth" style="display:none">
                <a href="#" onclick="ShowChloropleth()">
                  <div style="float: left;">
                    <div class="icon"></div>
                  </div>
                  <div>
                    Chloropleth
                  </div>
                  <div style="clear: both;"> </div>
                </a>
              </div>
            </div>
            -->
            <div class="tool optional">
              <div title="Proportional Symbols" id="iconShowProportional" style="display:none">
                <a href="#" onclick="ShowProportional()">
                  <div style="float: left;">
                  <div>
                    <div class="icon"></div>
                  </div>
                    Proportional Symbols
                  </div>
                  <div style="clear: both;"> </div>
                </a>
              </div>
            </div>
            <div class="tool optional">
              <div title="Time Line" id="timeline" style="display:none">
                <a href="#">
                  <div style="float: left;">
                    <div class="icon"></div>
                  </div>
                  <div>
                    Time Line
                  </div>
                  <div style="clear: both;"> </div>
                </a>
              </div>
            </div>
            <div class="tool optional">
              <div title="Download Layer" id="downloadLayer" style="display:none">
                <a href="#">
                  <div style="float: left;">
                    <div class="icon"></div>
                  </div>
                  <div>
                    Download
                  </div>
                  <div style="clear: both;"> </div>
                </a>
              </div>
            </div>
            <div class="tool optional">
              <div title="Symbology" id="symbologylink" style="display:none">
                <a href="#">
                  <div style="float: left;">
                    <div class="icon"></div>
                  </div>
                  <div>
                    Symbology
                  </div>
                  <div style="clear: both;"> </div>
                </a>
              </div>
            </div>
          </div>
          <!-- advanced ends -->

          <!-- layerTools -->
          <div id="layerTools" class="toolbar">
            <div class="tool first" title="Layer Ordering">
              <a href="#" class="flashPanelToggler" id="layerOrder-1">
                <div style="float: left;">
                  <div class="icon"></div>
                </div>
                <div>
                  Layer Ordering
                </div>
                <div style="clear: both;"> </div>
              </a>
            </div>
            <div class="tool" id="mapurl">
              <a href="#" onclick="getCurrentMapURL()" title="Get Map Link">
                <div style="float: left;">
                  <div class="icon"></div>
                </div>
                <div>
                  Map Link
                </div>
                <div style="clear: both;"> </div>
              </a>
            </div>
            <div class="tool last" >
              <a href="#" id="resetTreeViewControl" title="Reset View">
                <div style="float: left;">
                  <div class="icon"></div>
                </div>
                <div>
                  Reset
                </div>
                <div style="clear: both;"> </div>
              </a>
            </div>
          </div>
          <!-- layerTools ends -->


        </div>
        <!-- advancedToolSet ends -->

        <!-- Basemap -->
        <div class="baseMap">
          <label for='ddlBaseLayer'> <b>Base Map:</b> </label><SELECT id='ddlBaseLayer' onChange='SetBaseLayer(options[selectedIndex].value)'></SELECT>
        </div>
        <!-- Basemep ends -->

        <!-- login -->
        <?php global $user; ?>
        <div id="login" class="loginRegister">
          <?php if($user->uid): ?>
            Welcome <?php echo l($user->name, "user");?>
            <?php if($is_admin): ?>
              | <a href="<?php print check_url($front_page);?>admin">Administration</a>
            <?php endif; ?>
            | <a href="<?php print check_url($front_page);?>logout">Logout</a>
          <?php else: ?>
            <a href="#">Login</a> | <a href="<?php print check_url($front_page);?>user/register">Register</a>
          <?php endif; ?>
        </div>
        <!-- login ends -->
      </div>
      <!-- tools ends -->

      <!-- mapArea -->
      <div style="display:none">
        <?php if($map_area): ?>
          <?php print $map_area;?>
        <?php endif; ?>
      </div>

			<div id="mapArea" style="height:0; width:100%;">
        <!-- map -->
				<div id="map" style="height:100%; width:100%;">

				</div>
        <!-- map ends -->
			</div>
      <!-- mapArea ends -->

    </div>
    <!-- wrapper ends -->

    <?php print $closure;?>
    <script language="javascript">
      jQuery(document).ready(function(){
        chkFlashVersion();
      });
    </script>
  </body>
</html>
