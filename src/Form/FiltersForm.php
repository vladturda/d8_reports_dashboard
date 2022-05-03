<?php

namespace Drupal\d8_reports_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a Reports Dashboard form.
 */
class FiltersForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'd8_reports_dashboard_filters';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $view = \Drupal::request()->get( 'view' );
    $date = \Drupal::request()->query->get( 'date' );
    $interval = \Drupal::request()->query->get( 'interval' );

    $form['date'] = [
      '#type' => 'textfield',
      //'#title' => $this->t( 'Dates' ),
      '#default_value' => $date,
      '#required' => FALSE,
      '#attributes' => ['autocomplete' => 'off', 'placeholder' => 'Dates']
    ];

    $form['view'] = [
      '#type' => 'hidden',
      '#value' => $view
    ];

    $form['interval'] = [
      '#type' => 'hidden',
      '#value' => $interval
    ];

    $form['actions'] = [
      '#type' => 'actions'
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t( 'Filter' )
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $view = $form_state->getValue( 'view' );
    $query = [];

    if ($form_state->getValue( 'date' )) { 
      $dates = explode(',', $form_state->getValue( 'date' ) );
      sort( $dates );
      $query['date'] = join( ',', $dates );
    }

    if ($form_state->getValue( 'interval' )) {
      $query['interval'] = $form_state->getValue( 'interval' );
    }

    $form_state->setRedirect( 'd8_reports_dashboard', ['view' => $view], ['query' => $query] );
  }

}
