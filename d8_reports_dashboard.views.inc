<?php

/**
 * @file
 * d8_reports_dashboard.views.inc
 *
 * Implementation of hook_views_data_alter().
 */

/**
 * Implements hook_views_data_alter().
 *
 * Replaces contextual filter plugins by their corresponding RANGE plugins.
 */
function d8_reports_dashboard_views_data_alter(&$data) {
  $date_field_names = [ 'commerce_order_report:created', 'node_field_data:created_fulldate' ];
  
  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      if (isset($field_data['argument']['id'])) {
        $full_name = "$table_name:$field_name";

        if (in_array($full_name, $date_field_names)) {
          $data[$table_name][$field_name]['argument']['id'] = 'date_range';
        }
      }
    }
  }
}
