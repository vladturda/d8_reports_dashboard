<?php

namespace Drupal\d8_reports_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;
use DateTime;

/**
 * Returns responses for Reports Dashboard routes.
 */
class ReportsDashboardController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function buildDashboard( $view = 'orders', Request $request ) {
    $build['dashboard'] = [ '#theme' => 'dashboard' ];

    $build['dashboard']['#filters'] = \Drupal::service( 'plugin.manager.block' )
      ->createInstance( 'd8_reports_dashboard_filters' )
      ->build();

    switch ($view) {
      case 'orders':
        $build['dashboard']['#view'] = $this->buildOrders( $request );
        break;

      case 'subscribers':
        $build['dashboard']['#view'] = $this->buildSubscribers( $request );
        break;
    }

    return $build;
  }

  public function buildOrders( Request $request ) {
    $context_filters = $this->getContextFilters( $request );

    $query = [ 'view' => 'orders' ];

    $interval = $request->query->get( 'interval' ) ? $request->query->get( 'interval' ) : 'day';

    $dates = [];
    if ($request->query->get( 'date' )) { 
      $query['date'] = $request->query->get( 'date' );
      $dates['min'] = $query['date'];

      if (count(explode(',', $query['date'])) == 2) {
        $dates['min'] = explode(',', $query['date'])[0];
        $dates['max'] = explode(',', $query['date'])[1];
      }
    }

    $build = [
      '#type' => 'container',
      '#theme' => 'orders',
      '#attributes' => [ 'class' => [ 'd8-reports-dashboard-orders' ] ],
      '#info' => [ 
        'interval' => $interval,
        'interval_string' => ['day'=>'daily', 'month'=>'monthly', 'week'=>'weekly', 'year'=>'yearly'][$interval],
        'dates' => $dates,
        'query' => $query ],
      '#chart' => [],
      '#table' => []
    ];

    $build['#intervals'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#attributes' => [ 'class' => [ 'intervals-list' ] ]
    ];

    foreach ([ 'day', 'week', 'month', 'year' ] as $int) {
      $interval_url = Url::fromRoute( 'd8_reports_dashboard', array_merge( [ 'interval' => $int ], $query ) );
      $build['#intervals']['#items'][] = [ 
        '#wrapper_attributes' => [ 'class' => [ 'intervals-item' ] ],
        '#children' => \Drupal::l( $this->t( ucfirst( $int ) ), $interval_url )
      ];
    }

    $displays = [
      'chart' => [
        'orders' => [ 'view' => 'd8_reports_dashboard_orders', 'display' => "block_chart_$interval" ],
        'purchases' => [ 'view' => 'd8_reports_dashboard_purchases', 'display' => 'block_chart' ]
      ],
      'table' => [
        'orders' => [ 'view' => 'd8_reports_dashboard_orders', 'display' => "block_table_$interval" ]
      ]
    ];

    foreach ($displays as $type => $display) {
      foreach ($display as $category => $view_display) {
        $view = Views::getView( $view_display['view'] );
        $view->setDisplay( $view_display['display'] );
        $view->setArguments( $context_filters );
        $view->preExecute();
        $view->execute();
        $build["#$type"][$category] = $view->buildRenderable( $view_display['display'] );
      }
    }

    $build['#attached']['library'][] = 'd8_reports_dashboard/orders';

    return $build;
  }

  public function buildSubscribers( Request $request ) {
    $context_filters = $this->getContextFilters( $request );

    $query = ['view' => 'subscribers'];
    
    $interval = $request->query->get( 'interval' ) ? $request->query->get( 'interval' ) : 'month';

    $dates = [];
    if ($request->query->get( 'date' )) { 
      $query['date'] = $request->query->get( 'date' );
      $dates['min'] = $query['date'];

      if (count(explode(',', $query['date'])) == 2) {
        $dates['min'] = explode(',', $query['date'])[0];
        $dates['max'] = explode(',', $query['date'])[1];
      }
    }

    $build = [
      '#type' => 'container',
      '#theme' => 'subscribers',
      '#attributes' => [ 'class' => [ 'd8-reports-dashboard-subscribers' ] ],
      '#info' => [ 
        'interval' => $interval, 
        'interval_string' => ['day'=>'daily', 'month'=>'monthly', 'week'=>'weekly', 'year'=>'yearly'][$interval],
        'dates' => $dates,
        'query' => $query ],
      '#chart' => [],
      '#table' => []
    ];

    $displays = [
      'chart' => [ 
        'subscribers' => [ 'view' => 'd8_reports_dashboard_subscribers', 'display' => 'block_chart' ]
      ],
      'table' => [
        'churn' => [ 'view' => 'd8_reports_dashboard_subscribers', 'display' => 'block_churn' ]
      ]
    ];

    foreach ($displays as $type => $display) {
      foreach ($display as $category => $view_display) {
        $view = Views::getView( $view_display['view'] );
        $view->setDisplay( $view_display['display'] );
        $view->setArguments( $context_filters );
        $view->preExecute();
        $view->execute();
        $build["#$type"][$category] = $view->buildRenderable( $view_display['display'] ); 
      }
    }

    $build['#info']['churn'] = $this->getChurnData( $displays['table']['churn'], $context_filters );

    $build['#attached']['library'][] = 'd8_reports_dashboard/subscribers';

    return $build;
  }

  public function getContextFilters( Request $request ) {
    $context_filters = [];

    $view = $request->get('view');

    $interval = $request->query->get('interval') ? $request->query->get('interval') : 'day';
    $interval = $view == 'subscribers' ? 'month' : $interval;

    $date = $request->query->get( 'date' );

    switch ($interval) {
      case 'day': 
        if (count( explode( ',', $date ) ) == 2) {
          $date_array = explode( ',', $date );
        } else {
          $start_date = $date ? $date : date( 'Y-m-d', strtotime( '-1 months' ) );
          $date_array = [ $start_date, date( 'Y-m-d' ) ];
        }
      break;

      case 'week':
        if (count( explode( ',', $date ) ) == 2) {
          $date_array = explode( ',', $date );
        } else {
          $start_date = $date ? $date : date( 'Y-m-d', strtotime( '-1 months' ) );
          $date_array = [ $start_date, date( 'Y-m-d' ) ];
        }
      break;

      case 'month':
        if (count( explode( ',', $date ) ) == 2) {
          $start_date = explode( ',', $date )[0];
          $end_date = explode( ',', $date )[1];
          $start_month = new DateTime( $start_date );
          $end_month = new DateTime( $end_date );
          $date_array = [ $start_month->format('Y-m-01'), $end_month->format('Y-m-t') ];
        } else {
          $start_date = $date ? $date : date( 'Y-m-d' );
          $start_month = new DateTime( $start_date );
          $date_array = [ $start_month->format('Y-m-01'), $start_month->format('Y-m-t') ];
        }
      break;
      
      case 'year':
        if (count( explode( ',', $date ) ) == 2) {
          $start_date = explode( ',', $date )[0];
          $end_date = explode( ',', $date )[1];
          $start_year = new DateTime( $start_date );
          $end_year = new DateTime( $end_date );
          $date_array = [ $start_year->format('Y-01-01'), $end_year->format('Y-12-31') ];
        } else {
          $start_date = $date ? $date : date( 'Y-m-d' );
          $start_month = new DateTime( $start_date );
          $date_array = [ $start_month->format('Y-01-01'), $start_month->format('Y-12-31') ];
        }
      break;
    }

    sort($date_array);

    $date_range = str_replace('-', '', $date_array[0]) . '--' . str_replace('-', '', $date_array[1]);
    $context_filters['created'] = $date_range;

    return $context_filters;
  }

  public function getChurnData( $view_display, $context_filters = [] ) {
    $view = Views::getView( $view_display['view'] );
    $view->setDisplay( $view_display['display'] );
    $view->setArguments( $context_filters );
    $view->preExecute();
    $view->execute();

    $churn_result = $view->result;

    $churn_data = [];

    if ($churn_result) {
      $churn_data = [
        'initial_subscriber_count' => $churn_result[0]->node__field_integer_field_integer_value,
        'final_subscriber_count' => $churn_result[count($churn_result)-1]->node__field_integer_field_integer_value,
        'day_count' => count( $churn_result ),
        'subscriber_day_total' => 0,
        'lapse_total' => 0,
        'cancel_total' => 0
      ];

      $churn_data['net_gain'] = $churn_data['final_subscriber_count'] - $churn_data['initial_subscriber_count'];

      foreach ($churn_result as $result) {
        $churn_data['subscriber_day_total'] += $result->node__field_integer_field_integer_value;
        $churn_data['lapse_total'] += $result->node__field_subscribers_lapsed_field_subscribers_lapsed_targ;
        $churn_data['cancel_total'] += $result->node__field_subscribers_cancelled_field_subscribers_cancelle;
      }

      $churn_data['churn_total'] = $churn_data['lapse_total'] + $churn_data['cancel_total'];
      $churn_data['churn_subscriber_day'] = number_format( $churn_data['churn_total'] / $churn_data['subscriber_day_total'] * 100, 2 );
      $churn_data['churn_rate'] = number_format( $churn_data['churn_total'] / $churn_data['initial_subscriber_count'] * 100, 2 );
    }

    return $churn_data;
  }
}
