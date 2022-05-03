(function( $, Drupal ) {
    'use strict';

    Drupal.behaviors.reportsDatePicker = {
        attach: function( context ) {
            let datepicker = function() {
                let data = drupalSettings.filters;
                //console.log( data );

                let minViewMode = `${data.interval}s`;

                minViewMode = minViewMode == 'weeks' ? 'months' : minViewMode;

                $('#edit-date').datepicker({
                    format: 'yyyy-mm-dd',
                    defaultViewDate: minViewMode,
                    startView: minViewMode,
                    minViewMode: minViewMode,
                    maxViewMode: 'years',
                    multidate: 2,
                    container: '#d8-reports-dashboard-filters',
                    clearBtn: true
                });
            };

            $(document).ready( function() {
                $( '.d8-reports-dashboard-filters-block', context ).once( 'reports-date-picker' ).each( datepicker );
            } );
        }
    };

} ( jQuery, Drupal, drupalSettings ));