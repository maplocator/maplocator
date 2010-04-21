<?php
// $Id: image_attach.views.inc,v 1.5 2009/09/05 11:41:28 joachim Exp $

/**
 * Implementation of hook_views_data().
 */
/* 
// The field is defined in hook_views_data_alter below.
// But this code is likely to be needed for relationships or filters
// So commenting out for now.
function image_attach_views_data() {
  // Basic table information.
  $data = array();

  // Define the base group of this table. Fields that don't
  // have a group defined will go into this field by default.
  $data['image_attach']['table']['group']  = t('Image attach');

  // For other base tables, explain how we join
  // LEFT is the default, but let's be explicit
  $data['image_attach']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
      'type' => 'LEFT',
    ),
  );

  // changed field
  $data['image_attach']['iid'] = array(
    'title' => t('Attached image'), // The item it appears as on the UI,
    // TODO: relationship to attached image nodes.
    'help' => t('An image attached to a node.'),
  );

  return $data;
}
*/

/**
 * Implementation of hook_views_data_alter().
 */
function image_attach_views_data_alter(&$data) {
  // {node} table, prefixed with 'image_attach' to avoid potential clashes.
  // The images for attached image nodes.
  $data['node']['image_attach_images'] = array(
    'group' => t('Image attach'),
    'field' => array(
      'title' => t('Attached images'),
      'help' => t('The attached images, shown at a chosen size. This field can be added without a relationship.'),
      'handler' => 'image_attach_views_handler_field_attached_images',
    ),
  );
}

/**
 * Implementation of hook_views_handlers().
 */
function image_attach_views_handlers() {
  return array(
    'info' => array(
      'path' => drupal_get_path('module', 'image_attach'),
      ),
    'handlers' => array(
      'image_attach_views_handler_field_attached_images' => array(
        'parent' => 'image_handler_field_image_node_image',
      ),
    ),
  );
}
