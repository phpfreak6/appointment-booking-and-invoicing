<?php
namespace Bookly\Backend\Modules\Invoices;

use Bookly\Lib;

/**
 * Class Components
 * @package Bookly\Backend\Modules\Invoices
 */
class Components extends Lib\Base\Components
{
    /**
     * Render invoice dialog.
     * @throws \Exception
     */
    public function renderInvoiceDialog()
    {
        global $wp_locale;

        $this->enqueueStyles( array(
            'backend'  => array( 'css/jquery-ui-theme/jquery-ui.min.css'),
            'frontend' => get_option( 'bookly_cst_phone_default_country' ) == 'disabled'
                ? array()
                : array( 'css/intlTelInput.css' ),
			'module' => array('style/invoices.css')	
        ) );

        $this->enqueueScripts( array(
            'backend' => array(
                'js/angular.min.js' => array( 'jquery' ),
                'js/angular-ui-date-0.0.8.js' => array( 'bookly-angular.min.js', 'jquery-ui-datepicker' ),
             ),
            'frontend' => get_option( 'bookly_cst_phone_default_country' ) == 'disabled'
                ? array()
                : array( 'js/intlTelInput.min.js' => array( 'jquery' ) ),
            'module' => array( 'js/ng-invoice_dialog.js' => array( 'bookly-angular.min.js'),'js/bootstrap-select.js')
        ) );

        wp_localize_script( 'bookly-ng-invoice_dialog.js', 'BooklyL10nCustDialog', array(
            'default_status' => get_option( 'bookly_gen_default_appointment_status' ),
            'intlTelInput'   => array(
                'enabled' => get_option( 'bookly_cst_phone_default_country' ) != 'disabled',
                'utils'   => plugins_url( 'intlTelInput.utils.js', Lib\Plugin::getDirectory() . '/frontend/resources/js/intlTelInput.utils.js' ),
                'country' => get_option( 'bookly_cst_phone_default_country' ),
            ),
            'dateOptions' => array(
                'dateFormat'      => Lib\Utils\DateTime::convertFormat( 'date', Lib\Utils\DateTime::FORMAT_JQUERY_DATEPICKER ),
                'monthNamesShort' => array_values( $wp_locale->month_abbrev ),
                'monthNames'      => array_values( $wp_locale->month ),
                'dayNamesMin'     => array_values( $wp_locale->weekday_abbrev ),
                'longDays'        => array_values( $wp_locale->weekday ),
                'firstDay'        => (int) get_option( 'start_of_week' ),
            ),
        ) );
		
	    $staff_members = Lib\Utils\Common::isCurrentUserAdmin()
            ? Lib\Entities\Staff::query()->select('id,full_name')->sortBy( 'position' )->fetchArray()
            : Lib\Entities\Staff::query()->select('id,full_name')->where( 'wp_user_id', get_current_user_id() )->fetchArray();
		$customers = Lib\Utils\Common::isCurrentUserAdmin()
            ? Lib\Entities\Customer::query()->select('id,name')->sortBy( 'id' )->fetchArray()
            : Lib\Entities\Customer::query()->select('id,name')->where( 'wp_user_id', get_current_user_id() )->fetchArray();
	    
        $this->render( '_invoice_dialog',compact('staff_members','customers'));
    }

	
	public function renderCustomerDialog()
    {
        global $wp_locale;

        $this->enqueueStyles( array(
            'backend'  => array( 'css/jquery-ui-theme/jquery-ui.min.css', ),
            'frontend' => get_option( 'bookly_cst_phone_default_country' ) == 'disabled'
                ? array()
                : array( 'css/intlTelInput.css' ),
        ) );

        $this->enqueueScripts( array(
            'backend' => array(
                'js/angular.min.js' => array( 'jquery' ),
                'js/angular-ui-date-0.0.8.js' => array( 'bookly-angular.min.js', 'jquery-ui-datepicker' ),
             ),
            'frontend' => get_option( 'bookly_cst_phone_default_country' ) == 'disabled'
                ? array()
                : array( 'js/intlTelInput.min.js' => array( 'jquery' ) ),
            'module' => array( 'js/ng-customer_dialog.js' => array( 'bookly-angular.min.js' ), )
        ) );

        wp_localize_script( 'bookly-ng-customer_dialog.js', 'BooklyL10nCustDialog', array(
            'default_status' => get_option( 'bookly_gen_default_appointment_status' ),
            'intlTelInput'   => array(
                'enabled' => get_option( 'bookly_cst_phone_default_country' ) != 'disabled',
                'utils'   => plugins_url( 'intlTelInput.utils.js', Lib\Plugin::getDirectory() . '/frontend/resources/js/intlTelInput.utils.js' ),
                'country' => get_option( 'bookly_cst_phone_default_country' ),
            ),
            'dateOptions' => array(
                'dateFormat'      => Lib\Utils\DateTime::convertFormat( 'date', Lib\Utils\DateTime::FORMAT_JQUERY_DATEPICKER ),
                'monthNamesShort' => array_values( $wp_locale->month_abbrev ),
                'monthNames'      => array_values( $wp_locale->month ),
                'dayNamesMin'     => array_values( $wp_locale->weekday_abbrev ),
                'longDays'        => array_values( $wp_locale->weekday ),
                'firstDay'        => (int) get_option( 'start_of_week' ),
            ),
        ) );

        $this->render( '_customer_dialog' );
    }
}