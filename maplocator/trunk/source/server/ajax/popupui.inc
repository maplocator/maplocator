<?php
/***
*This file contains functionality to generate UI for feature pop-up .
*
***/


$divPopupPaneinnerHTML = <<<EOF
  <h3>{$feature_title}</h3>

  <div class="attribution">
    <p>
      Created by {$created_by_user} on {$created_date}. Last modified by {$modified_by_user} on {$modified_date}.
    </p>
  </div>

  <div class="content">
    <table cellspacing="0">
      <tbody>
        {$content}
      </tbody>
    </table>

    <!--
    <div class="validationIcon"><a title="This data has been validated" href="#"><img alt="Validated" src="validated.png"/></a></div>
    <div class="validation">This data has been validated by <a href="#">User x</a></div>
    -->
    <div title="{$isvalidated_msg}" class="{$isvalidated}">
    </div>

    <div class="clear"><!-- --></div>

  </div>

  <div class="layerAttribution">Attribution:<p title="{$fullattribution}">{$attribution}</p></div>
  <div class="license">{$license}</div>
  <div style="text-align:center; padding: 5px; text-align: center; font-weight: bold; border-top: 1px solid; border-bottom: 1px solid;"><a onclick="javascript:showAjaxLinkPopup(this.href, this.name);return false;" href="{$base_path}external_media.php?layer_tablename={$layer_tablename}&row_id={$row_id}" name="External Media">Fetch Media</a> from image aggregation sites like Flickr and Panaramio</div>
EOF;

$detailsPane = <<<EOF
<div id="detailsPane">
  <div class="tabs">
    <ul id="ulPopupUIMenu">
      {$menu}
    </ul>
  </div>

  <div id="divPopupPane" class="pane">
    {$divPopupPaneinnerHTML}
  </div>
</div>
EOF;

$summaryPopup = <<<EOF
<div id="mlocate_popup">

  <div id="featureName">
    {$layer_name}: {$feature_title}
  </div>


  <div class="popupActions">
    <ul>
      {$menu}
    </ul>
  </div>

  <!--
  <div class="attribution">
    <p>
      Created by {$created_by_user} on {$created_date}
      <br/>
      Last modified by {$modified_by_user} on {$modified_date}
    </p>
  </div>
  -->

  <div class="summary">
    <table cellspacing="0">
      <tbody>
        {$summary}
      </tbody>
    </table>
  </div>

  <!--
  <div class="validationIcon"><a title="This data has been validated" href="#"><img alt="Validated" src="validated.png"/></a></div>
  <div class="validation">This data has been validated by <a href="#">User x</a></div>

  <div class="validationIcon"><a href="#" title="This data has not been validated"><img src="not-validated.png" alt="Not Validated"/></a></div>
  <div class="validation">This data is not validated</div>
  -->
  <div title="{$isvalidated_msg}" class="{$isvalidated}">
  </div>
  <div class="{$show_validate}_validate_control">
    <span>Validate: <input type="checkbox" name="validate" value="Validate" onClick="validateFeature(this.checked, '{$layer_tablename}', '{$row_id}')"></span>
  </div>

  <div class="clear"> </div>

  <div class="layerAttribution">Attribution: <!--<a href="#">--><p title="{$fullattribution}">{$attribution}</p><!--</a>--> </div>
  <div class="license"><!--CC--> {$license}<!-- Please get the corresponding link and image from creativecommons.org --></div>

</div>
EOF;

$layerInfoinnerHTML= <<<EOF
  <div class="summary">

    <div id="layerName">
      <h3>{$layer_name}</h3> <!-- layer_name -->
    </div>
    {$editMetadataUI}

    <div class="attribution">
      <b>Attribution:</b> <span title="{$fullattribution}">{$attribution}</span>
    </div>
    <div class="license">
      {$license}
    </div>

    <div class="themegeo">
      <b>Theme:</b> {$theme_name}
      <br/>
      <b>Geography:</b> {$geo_name}
    </div>

    <table cellspacing="0">
      <tbody>
        {$layer_meta_info}
      </tbody>
    </table>

    <div class="columnDescription">
      <div style="display: none;" class="descriptionShowHide control"><a class="descriptionControl" href="#">-</a></div>
      <div class="descriptionShowHide control"><a class="descriptionControl" href="#">+</a></div>
      <h3>Layer Attributes</h3>
      <ul style="display: none;" id="layerDescription">
        <!-- layer_column_description -->
        {$layer_attr}
      </ul>
    </div>

    <!-- if linked tables exist -->
    <div class="linkTables" style="display: {$haslinktables};">
      <div style="display: none;" class="linkedDataShowHide control"><a class="linkedDataControl" href="#">-</a></div>
      <div class="linkedDataShowHide control"><a class="linkedDataControl" href="#">+</a></div>
      <h3>Linked Data</h3>
      <ul style="display: none;" id="linkedData">
      {$link_tables_info}
      </ul>
    </div>

    <!-- if resource tables referenced -->
    <div class="resourceTables" style="display: {$hasresourcetables};">
      <div style="display: none;" class="resourceTablesShowHide control"><a class="resourceTablesControl" href="#">-</a></div>
      <div class="resourceTablesShowHide control"><a class="resourceTablesControl" href="#">+</a></div>
      <h3>Resource Tables</h3>
      <ul style="display: none;" id="resourceTables">
        {$resource_tables_info}
      </ul>
    </div>

  </div>
EOF;

$layerInfoHTML = <<<EOF
<div id="layerDetailsPane">
  <div class="tabs">
    <ul id="ulLayerPopupUIMenu">
      <!--
      <li class="first active"><a href="#">Summary</a></li>
      <li><a href="#">Details</a></li>
      -->
      {$menu}
    </ul>
  </div>

  <div id="layerinfo">
    {$layerInfoinnerHTML}
    <div class="clear"></div>
  </div>
</div>
EOF;
?>