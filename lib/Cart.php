<?php
namespace Bookly\Lib;

use Bookly\Lib\Entities\Series;
use Bookly\Lib\Utils\Common;

/**
 * Class Cart
 * @package Bookly\Lib
 */
class Cart
{
    /**
     * @var CartItem[]
     */
    private $items = array();

    /**
     * @var UserBookingData
     */
    private $userData = null;

    /**
     * Constructor.
     *
     * @param UserBookingData $userData
     */
    public function __construct( UserBookingData $userData )
    {
        $this->userData = $userData;
    }

    /**
     * Get cart item.
     *
     * @param integer $key
     * @return CartItem|false
     */
    public function get( $key )
    {
        if ( isset ( $this->items[ $key ] ) ) {
            return $this->items[ $key ];
        }

        return false;
    }

    /**
     * Add cart item.
     *
     * @param CartItem $item
     * @return integer
     */
    public function add( CartItem $item )
    {
        $this->items[] = $item;
        end( $this->items );

        return key( $this->items );
    }

    /**
     * Replace given item with other items.
     *
     * @param integer $key
     * @param CartItem[] $items
     * @return array
     */
    public function replace( $key, array $items )
    {
        $new_items = array();
        $new_keys  = array();
        $new_key   = 0;
        foreach ( $this->items as $cart_key => $cart_item ) {
            if ( $cart_key == $key ) {
                foreach ( $items as $item ) {
                    $new_items[ $new_key ] = $item;
                    $new_keys[] = $new_key;
                    ++ $new_key;
                }
            } else {
                $new_items[ $new_key ++ ] = $cart_item;
            }
        }
        $this->items = $new_items;

        return $new_keys;
    }

    /**
     * Drop cart item.
     *
     * @param integer $key
     */
    public function drop( $key )
    {
        unset ( $this->items[ $key ] );
    }

    /**
     * Get cart items.
     *
     * @return CartItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Get items data as array.
     *
     * @return array
     */
    public function getItemsData()
    {
        $data = array();
        foreach ( $this->items as $key => $item ) {
            $data[ $key ] = $item->getData();
        }

        return $data;
    }

    /**
     * Set items data from array.
     *
     * @param array $data
     */
    public function setItemsData( array $data )
    {
        foreach ( $data as $key => $item_data ) {
            $item = new CartItem();
            $item->setData( $item_data );
            $this->items[ $key ] = $item;
        }
    }

    /**
     * Save all cart items (customer appointments).
     *
     * @param Entities\Customer $customer
     * @param                   $payment_id
     * @param                   $time_zone_offset
     * @param                   $booking_numbers
     * @return Entities\CustomerAppointment[]
     */
    public function save( Entities\Customer $customer, $payment_id, $time_zone_offset, &$booking_numbers )
    {
        $ca_list = array();
        $stored_series = array();
        foreach ( $this->getItems() as $cart_item ) {
            $series_id = null;
            if ( $cart_item->get( 'series_uniq_id' ) ) {
                if ( !isset( $stored_series[ $cart_item->get( 'series_uniq_id' ) ] ) ) {
                    $series = new Series();
                    $series
                        ->set( 'repeat', '{}' )
                        ->set( 'token', Common::generateToken( get_class( $series ), 'token' ) )
                        ->save();
                    $stored_series[ $cart_item->get( 'series_uniq_id' ) ] = $series;
                }
                $series_id = $stored_series[ $cart_item->get( 'series_uniq_id' ) ]->get( 'id' );
            }

            if (
                $cart_item->get( 'series_uniq_id' )
                && get_option( 'bookly_recurring_appointments_payment' ) === 'first'
                && ( ! $cart_item->get( 'first_in_series' ) )
            ) {
                $item_payment_id = null;
            } else {
                $item_payment_id = $payment_id;
            }

            $ca_entity = null;
            $service   = $cart_item->getService();
            $compound_service_id = null;
            $compound_token      = null;
            if ( $service->get( 'type' ) == Entities\Service::TYPE_COMPOUND ) {
                $compound_service_id = $service->get( 'id' );
                $compound_token = Utils\Common::generateToken( '\Bookly\Lib\Entities\CustomerAppointment', 'compound_token' );
            }

            $extras = json_encode( $cart_item->get( 'extras' ) );
            $custom_fields = json_encode( $cart_item->get( 'custom_fields' ) );
            foreach ( $cart_item->get( 'slots' ) as $slot ) {
                list ( $service_id, $staff_id, $datetime ) = $slot;
                $service = Entities\Service::find( $service_id );

                /*
                 * Get appointment with the same params.
                 * If it exists -> create connection to this appointment,
                 * otherwise create appointment and connect customer to new appointment
                 */
                $appointment = new Entities\Appointment();
                $appointment->loadBy( array(
                    'service_id' => $service_id,
                    'staff_id'   => $staff_id,
                    'start_date' => $datetime,
                ) );
                if ( $appointment->isLoaded() == false ) {
                    $appointment->set( 'service_id', $service_id );
                    $appointment->set( 'staff_id',   $staff_id );
                    $appointment->set( 'start_date', $datetime );
                    $appointment->set( 'end_date',   date( 'Y-m-d H:i:s', strtotime( $datetime ) + $service->get( 'duration' ) ) );
                    $appointment->set( 'series_id',  $series_id );
                    $appointment->save();
                }

                // Create CustomerAppointment record.
                $customer_appointment = new Entities\CustomerAppointment();
                $customer_appointment->set( 'customer_id', $customer->get( 'id' ) )
                    ->set( 'appointment_id',      $appointment->get( 'id' ) )
                    ->set( 'location_id',         $cart_item->get( 'location_id' ) ?: null )
                    ->set( 'payment_id',          $item_payment_id )
                    ->set( 'number_of_persons',   $cart_item->get( 'number_of_persons' ) )
                    ->set( 'extras',              $extras )
                    ->set( 'custom_fields',       $custom_fields )
                    ->set( 'status',              get_option( 'bookly_gen_default_appointment_status' ) )
                    ->set( 'time_zone_offset',    $time_zone_offset )
                    ->set( 'compound_service_id', $compound_service_id )
                    ->set( 'compound_token',      $compound_token )
                    ->set( 'created_from',        'frontend' )
                    ->set( 'created',             current_time( 'mysql' ) )
                    ->save();

				/* custom code start to save data in tables  */
				
			   // Create CustomerInvoices record.
              $customer_invoice = new Entities\Invoice();
                $customer_invoice->set( 'bill_no',date('YmdHis'))
                    ->set( 'customer_id',         $customer->get( 'id' )  )
                    ->set( 'bill_date',          date( 'Y-m-d'))
                    ->set( 'amount',   ($payment->get('total')) ? $payment->get('total') : '')
                    ->set( 'payed',    ($payment->get('paid')) ? $payment->get('paid') : '')
                    ->set( 'mandant',       $staff_id)
                    ->set( 'comment',          $cart_item->get( 'custom_fields' )[0]['value'])
                    ->set( 'send_mail',    date( 'Y-m-d H:i:s') )
                    ->set( 'send_sms',     date( 'Y-m-d H:i:s'))
                    ->set( 'send_post',      date( 'Y-m-d H:i:s') )
                    ->set( 'first_openend',      '' )
                    ->save();
				$customer_invoice_id = $customer_invoice->get( 'id' ); 

			// Create CustomerInvoiceAppointment record.
              $customer_InvoiceAppointment = new Entities\InvoiceAppointment();
                $customer_InvoiceAppointment->set( 'ca_id',    $customer->get( 'id' ) )
                    ->set( 'start_date',        $cart_item->get( 'date_from' ).' '.$cart_item->get( 'time_from' ) )
                    ->set( 'end_date',         $cart_item->get( 'date_from' ).' '.$cart_item->get( 'time_to' ) )
                    ->set( 'price',   ($payment->get('total')) ? $payment->get('total') : '')
                    ->set( 'qty',           '1')
                    ->set( 'aid',       $customer_invoice_id)
                    ->set( 'name',        ($customer->get( 'name' )) ? $customer->get( 'name' ) : '')
                    ->set( 'typ',    ($payment->get('type')) ? $payment->get('type') : '')
                    ->set( 'payed_amount', ($payment->get('paid')) ? $payment->get('paid') : '')
                    ->set( 'payed_date', $payment->get('created') )
                    ->save();
				/* echo '<pre>' ; print_r($customer_InvoiceAppointment);	
					die; */
			/* custom code End to save data in tables  */	
			
                // Handle extras duration.
                if ( Config::serviceExtrasEnabled() ) {
                    $appointment->set( 'extras_duration', $appointment->getMaxExtrasDuration() );
                    $appointment->save();
                }

                // Google Calendar.
                $appointment->handleGoogleCalendar();

                // Add booking number.
                $booking_numbers[] = $appointment->get( 'id' );

                if ( $ca_entity === null ) {
                    $ca_entity = $customer_appointment;
                    $ca_list[ $customer_appointment->get( 'id' ) ] = $customer_appointment;
                }
                // Only firs service have custom fields, extras (compound).
                $custom_fields = $extras = '[]';
            }
        }
        $booking_numbers = array_unique( $booking_numbers );

        return $ca_list;
    }

    /**
     * Get total and deposit for cart.
     *
     * @param bool $apply_coupon
     * @return array
     */
    public function getInfo( $apply_coupon = true )
    {
        $total  = $deposit = $item_price = 0;
        $coupon = false;
        $before_coupon   = 0;
        $coupon_services = array();
        if ( $apply_coupon && $coupon = $this->userData->getCoupon() ) {
            $coupon_services = Entities\CouponService::query()->select( 'service_id' )->where( 'coupon_id', $coupon->get( 'id' ) )->indexBy( 'service_id' )->fetchArray();
        }

        foreach ( $this->items as $key => $item ) {
            if (
                $item->get( 'series_uniq_id' )
                && get_option( 'bookly_recurring_appointments_payment' ) === 'first'
                && ( ! $item->get( 'first_in_series' ) )
            ) {
                continue;
            }

            // Cart contains a service that was already removed/deleted from Bookly (WooCommerce BP-224)
            if ( $item->getService() ) {
                $discount   = array_key_exists( $item->getService()->get( 'id' ), $coupon_services );
                $item_price = $item->getServicePrice() * $item->get( 'number_of_persons' );
                if ( $discount ) {
                    $before_coupon += $item_price;
                }
            }

            $total   += $item_price;
            $deposit += Proxy\DepositPayments::prepareAmount( $item_price, $item->getDeposit(), $item->get( 'number_of_persons' ) );
        }

        if ( $coupon ) {
            $total -= ( $before_coupon - $coupon->apply( $before_coupon ) );
            if ( $deposit > $total ) {
                $deposit = $total;
            }
        }

        if ( ! Config::depositPaymentsEnabled() ) {
            $due = 0;
        } else {
            $due = max( $total - $deposit, 0 );
        }

        // coupon discount=10%, deduction=10
        // cart_item price=70, staff_deposit=50, coupon=on
        // cart_item price=30, staff_deposit=20, coupon=off
        //
        //            total deposit due
        // Array like [ 83,   70,   13 ]

        return array( $total, $deposit, $due );
    }

    /**
     * Generate title of cart items (used in payments).
     *
     * @param int  $max_length
     * @param bool $multi_byte
     * @return string
     */
    public function getItemsTitle( $max_length = 255, $multi_byte = true )
    {
        reset( $this->items );
        $title = $this->get( key( $this->items ) )->getService()->getTitle();
        $tail  = '';
        $more  = count( $this->items ) - 1;
        if ( $more > 0 ) {
            $tail = sprintf( _n( ' and %d more item', ' and %d more items', $more, 'bookly' ), $more );
        }

        if ( $multi_byte ) {
            if ( preg_match_all( '/./su', $title . $tail, $matches ) > $max_length ) {
                $length_tail = preg_match_all( '/./su', $tail, $matches );
                $title       = preg_replace( '/^(.{' . ( $max_length - $length_tail - 3 ) . '}).*/su', '$1', $title ) . '...';
            }
        } else {
            if ( strlen( $title . $tail ) > $max_length ) {
                while ( strlen( $title . $tail ) + 3 > $max_length ) {
                    $title = preg_replace( '/.$/su', '', $title );
                }
                $title .= '...';
            }
        }

        return $title . $tail;
    }

    /**
     * Return cart_key for not available appointment or NULL.
     *
     * @return int|null
     */
    public function getFailedKey()
    {
        $max_date  = date_create( '@' . ( current_time( 'timestamp' ) + Config::getMaximumAvailableDaysForBooking() * DAY_IN_SECONDS ) )->setTime( 0, 0 );

        foreach ( $this->items as $cart_key => $cart_item ) {
            if( $cart_item->getService() ) {
                $service     = $cart_item->getService();
                $is_compound = $service->get( 'type' ) == Entities\Service::TYPE_COMPOUND;
                foreach ( $cart_item->get( 'slots' ) as $slot ) {
                    list ( $service_id, $staff_id, $datetime ) = $slot;
                    if ( $is_compound ) {
                        $service = Entities\Service::find( $service_id );
                    }
                    $bound_start = date_create( $datetime )->modify( '-' . (int) $service->get( 'padding_left' ) . ' sec' );
                    $bound_end   = date_create( $datetime )->modify( ( (int) $service->get( 'duration' ) + (int) $service->get( 'padding_right' ) + $cart_item->getExtrasDuration() ) . ' sec' );

                    if ( $bound_end < $max_date ) {
                        $query = Entities\CustomerAppointment::query( 'ca' )
                            ->select( 'ss.capacity_max, SUM(ca.number_of_persons) AS total_number_of_persons,
                                DATE_SUB(a.start_date, INTERVAL (COALESCE(s.padding_left,0) ) SECOND) AS bound_left,
                                DATE_ADD(a.end_date,   INTERVAL (COALESCE(s.padding_right,0) + a.extras_duration ) SECOND) AS bound_right' )
                            ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
                            ->leftJoin( 'StaffService', 'ss', 'ss.staff_id = a.staff_id AND ss.service_id = a.service_id' )
                            ->leftJoin( 'Service', 's', 's.id = a.service_id' )
                            ->where( 'a.staff_id', $staff_id )
                            ->whereNot( 'ca.status', Entities\CustomerAppointment::STATUS_CANCELLED )
                            ->whereNot( 'ca.status', Entities\CustomerAppointment::STATUS_REJECTED )
                            ->groupBy( 'a.service_id, a.start_date' )
                            ->havingRaw( '%s > bound_left AND bound_right > %s AND ( total_number_of_persons + %d ) > ss.capacity_max',
                                array( $bound_end->format( 'Y-m-d H:i:s' ), $bound_start->format( 'Y-m-d H:i:s' ), $cart_item->get( 'number_of_persons' ) ) )
                            ->limit( 1 );
                        $rows  = $query->execute( Query::HYDRATE_NONE );

                        if ( $rows != 0 ) {
                            // Exist intersect appointment, time not available.
                            return $cart_key;
                        }
                    }
                }
            }
        }

        return null;
    }

}