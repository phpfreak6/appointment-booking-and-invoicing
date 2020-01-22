<?php
namespace Bookly\Lib;

abstract class API
{
    const API_URL = 'https://api.booking-wp-plugin.com/1.0';

    /**
     * Verify envato.com Purchase Code
     *
     * @param $purchase_code
     * @param Base\Plugin $plugin_class
     * @return array
     */
    public static function verifyPurchaseCode( $purchase_code, $plugin_class )
    {
        $options   = array(
            'timeout' => 10, //seconds
            'headers' => array(
                'Accept' => 'application/json'
            ),
        );
        $arguments = array(
            'api'           => '1.0',
            'action'        => 'verify-purchase-code',
            'plugin'        => $plugin_class::getSlug(),
            'purchase_code' => $purchase_code,
            'site_url'      => site_url(),
        );
        $url = add_query_arg( $arguments, 'http://booking-wp-plugin.com/' );
        try {
            $response = wp_remote_get( $url, $options );
            if ( $response instanceof \WP_Error ) {

            } elseif ( isset( $response['body'] ) ) {
                $json = json_decode( $response['body'], true );
                if ( isset( $json['success'] ) ) {
                    if ( (bool) $json['success'] ) {
                        return array(
                            'valid' => true,
                        );
                    } else {
                        return array(
                            'valid' => false,
                            'error' => sprintf(
                                __( '%s is not a valid purchase code for %s.', 'bookly' ),
                                $purchase_code,
                                $plugin_class::getTitle()
                            )
                        );
                    }
                }
            }
        } catch ( \Exception $e ) {

        }

        return array(
            'valid' => false,
            'error' => __( 'Purchase code verification is temporarily unavailable. Please try again later.', 'bookly' )
        );
    }

    /**
     * Register subscriber.
     *
     * @param string $email
     * @return bool
     */
    public static function registerSubscriber( $email )
    {
        try {
            $url  = self::API_URL . '/subscribers';
            $curl = new Curl\Curl();
            $curl->options['CURLOPT_HEADER']  = 0;
            $curl->options['CURLOPT_TIMEOUT'] = 25;
            $data = array( 'email' => $email, 'site_url' => site_url() );
            $response = (array) json_decode( $curl->post( $url, $data ), true );
            if ( isset ( $response['success'] ) && $response['success'] ) {
                return true;
            }
        } catch ( \Exception $e ) {

        }

        return false;
    }

    /**
     * Send Net Promoter Score.
     *
     * @param integer $rate
     * @param string  $msg
     * @param string  $email
     * @return bool
     */
    public static function sendNps( $rate, $msg, $email )
    {
        try {
            $url = self::API_URL . '/nps';
            $curl = new Curl\Curl();
            $curl->options['CURLOPT_HEADER']  = 0;
            $curl->options['CURLOPT_TIMEOUT'] = 25;
            $data = array( 'rate' => $rate, 'msg' => $msg, 'email' => $email, 'site_url' => site_url() );
            $response = (array) json_decode( $curl->post( $url, $data ), true );
            if ( isset ( $response['success'] ) && $response['success'] ) {
                return true;
            }
        } catch ( \Exception $e ) {

        }

        return false;
    }

    /**
     * Send statistics data.
     */
    public static function sendStats()
    {
        /** @global wpdb */
        global $wpdb;

        $interval_end   = current_time( 'mysql' );
        $interval_start = date_create( $interval_end )->modify( '-30 days' )->format( 'Y-m-d H:i:s' );

        // Staff members.
        $staff = array( 'total' => 0, 'admins' => 0, 'non_admins' => 0 );
        foreach ( Entities\Staff::query()->find() as $staff_member ) {
            ++ $staff['total'];
            $wp_user_id = $staff_member->get( 'wp_user_id' );
            if ( $wp_user_id && $user = get_user_by( 'id', $wp_user_id ) ) {
                if ( $user->has_cap( 'manage_options' ) ) {
                    ++ $staff['admins'];
                } else {
                    ++ $staff['non_admins'];
                }
            }
        }

        // Services.
        $services = array();

        // Quantity.
        $services['count'] = Entities\Service::query()->count();

        // Max duration.
        $row = Entities\Service::query()->select( 'MAX(duration) AS max_duration' )->fetchRow();
        $services['max_duration'] = $row['max_duration'];

        // Max capacity.
        $row = Entities\Service::query( 's' )
                ->select( 'MAX(ss.capacity_max) AS max_capacity' )
                ->innerJoin( 'StaffService', 'ss', 'ss.service_id = s.id' )
                ->where( 's.type', Entities\Service::TYPE_SIMPLE )
                ->whereNot( 's.visibility', 'private' )
                ->fetchRow();
        $services['max_capacity'] = $row['max_capacity'];

        // Services list.
        $row = Entities\Service::query()->select( 'id, title' )->fetchArray();
        $services['services'] = $row;

        // Find active customers.
        $sql = $wpdb->prepare( '
             SELECT COUNT(customer_id) AS active_customers
               FROM ( SELECT DISTINCT(customer_id)
                        FROM `' . Entities\CustomerAppointment::getTableName() . '` AS ca
                   LEFT JOIN `' . Entities\Payment::getTableName() . '` AS p
                          ON ca.payment_id = p.id
                       WHERE p.status = %s
                         AND appointment_id IN (
                                SELECT id
                                  FROM `' . Entities\Appointment::getTableName() . '`
                                 WHERE end_date > %s
                                   AND end_date <= %s
                             )
                     ) AS active',
            Entities\Payment::STATUS_COMPLETED, $interval_start, $interval_end );
        $active_clients = (int) $wpdb->get_var( $sql );

        // Payments completed.
        $completed_payments = Entities\Payment::query()
              ->whereGt( 'created', $interval_start )
              ->where( 'status', Entities\Payment::STATUS_COMPLETED )
              ->count();

        // History Data.
        $history = array();

        $history_schema = array( 'bookings_from_frontend' => 0, 'bookings_from_backend' => 0 );

        for ( $offset = - 10; $offset < 0; $offset ++ ) {
            $history[ date_create( current_time( 'mysql' ) )->modify( $offset . ' days' )->format( 'Y-m-d' ) ] = $history_schema;
        }

        // Frontend/Backend Appointments.
        $rows = Entities\CustomerAppointment::query()
            ->select( 'COUNT(*) AS quantity, created_from, DATE_FORMAT(created, \'%%Y-%%m-%%d\') AS cur_date' )
            ->whereGte( 'created', date_create( current_time( 'mysql' ) )->modify( '-10 days' )->format( 'Y-m-d' ) )
            ->whereLt( 'created', date_create( current_time( 'mysql' ) )->format( 'Y-m-d' ) )
            ->groupBy( 'created_from, cur_date' )
            ->fetchArray();

        foreach ( $rows as $record ) {
            $history[ $record['cur_date'] ][ 'bookings_from_' . $record['created_from'] ] = (int) $record['quantity'];
        }
        // Send request.
        wp_remote_post( self::API_URL . '/stats', array(
            'timeout' => 25,
            'body' => array(
                'site_url'           => site_url(),
                'title'              => get_bloginfo( 'name' ),
                'description'        => get_bloginfo( 'description' ),
                'active_clients'     => $active_clients,
                'admin_language'     => get_option( 'bookly_admin_preferred_language' ),
                'completed_payments' => $completed_payments,
                'services'           => $services,
                'staff'              => $staff,
                'history'            => $history
            ),
        ) );
    }
}