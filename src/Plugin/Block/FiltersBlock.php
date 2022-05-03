<?php

namespace Drupal\d8_reports_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;

/**
 * Provides a filter block.
 *
 * @Block(
 *   id = "d8_reports_dashboard_filters",
 *   admin_label = @Translation("Reports Dashboard Filters"),
 *   category = @Translation("DYWM Reports Dashboard")
 * )
 */
class FiltersBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $view = \Drupal::request()->get( 'view' );

    $date = \Drupal::request()->query->get( 'date' );
    $date_query = $date ? [ 'date' => $date ] : [];

    $interval = \Drupal::request()->query->get( 'interval' );// : 'month';
    $interval_query = $interval ? [ 'interval' => $interval ] : []; 

    $query = array_merge( $date_query, $interval_query );

    $build['filters'] = [ '#theme' => 'filters' ];

    foreach (['orders', 'subscribers'] as $v) {
      $query = ($v == 'subscribers') ? $date_query : $query;
      $build['filters']['#views'][$v] = [
        '#type' => 'link',
        '#title' => $this->t( ucfirst( $v ) ),
        '#url' => Url::fromRoute( 'd8_reports_dashboard', array_merge( [ 'view' => $v ], $query ) ),
        '#attributes' => ['class' => ['d8-reports-dashboard-view-tab', ($view == $v ? 'active-tab' : '')]]
      ];
    }

    $build['filters']['#form'] =  \Drupal::formBuilder()->getForm( 'Drupal\d8_reports_dashboard\Form\FiltersForm' );
    
    $build['#attached']['library'][] = 'd8_reports_dashboard/filters';

    $build['#attached']['drupalSettings']['filters'] = [
      'view' => $view,
      'date' => $date, 
      'interval' => $interval ? $interval : ($view == 'subscribers' ? 'month' : 'day')
    ];

    return $build;
  }

}
