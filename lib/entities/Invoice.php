<?php
namespace Bookly\Lib\Entities;

use Bookly\Lib;

/**
 * Class Customer
 * @package Bookly\Lib\Entities
 */
class Invoice extends Lib\Base\Entity
{
    protected static $table = 'ab_invoice';

    protected static $schema = array(
        'id'         => array( 'format' => '%d' ),
		'bill_no'       => array( 'format' => '%s', 'default' => '' ),
        'customer_id' => array( 'format' => '%d' ),
        'bill_date'   => array( 'format' => '%s' ),
        'amount'      => array( 'format' => '%s', 'default' => '' ),
        'payed'      => array( 'format' => '%s', 'default' => '' ),
        'mandant'      => array( 'format' => '%s', 'default' => '' ),
        'comment'      => array( 'format' => '%s', 'default' => '' ),
        'send_mail'   => array( 'format' => '%s' ),
        'send_sms'   => array( 'format' => '%s' ),
        'send_post'   => array( 'format' => '%s' ),
        'first_openend'   => array( 'format' => '%s' ),
        'token'   =>  array( 'format' => '%s', 'default' => '' )
    );
	
    protected static $cache = array();

    /** @var Customer */
    public $customer = null;

    /**
     * Save entity to database.
     * Generate token before saving.
     *
     * @return int|false
     */
    public function save()
    {
        // Generate new token if it is not set.
        if ( $this->get( 'token' ) == '' ) {
            $this->set( 'token', Lib\Utils\Common::generateToken( get_class( $this ), 'token' ) );
        }
        //$this->set( 'locale', apply_filters( 'wpml_current_language', null ) );

        return parent::save();
    }


    /**
     * Get array of custom fields with labels and values.
     *
     * @return array
     */
    public function getCustomFields()
    {
        $service_id = null;
        if ( Lib\Config::customFieldsPerService() ) {
            $service_id = Appointment::find( $this->get( 'appointment_id' ) )->get( 'service_id' );
        }
        $result = array();
        if ( $this->get( 'custom_fields' ) != '[]' ) {
            $custom_fields = array();
            foreach ( Lib\Utils\Common::getTranslatedCustomFields( $service_id ) as $field ) {
                $custom_fields[ $field->id ] = $field;
            }
            $data = json_decode( $this->get( 'custom_fields' ), true );
            if ( is_array( $data ) ) {
                foreach ( $data as $customer_custom_field ) {
                    if ( array_key_exists( $customer_custom_field['id'], $custom_fields ) ) {
                        $field = $custom_fields[ $customer_custom_field['id'] ];
                        $translated_value = array();
                        if ( array_key_exists( 'value', $customer_custom_field ) ) {
                            // Custom field have items ( radio group, etc. )
                            if ( property_exists( $field, 'items' ) ) {
                                foreach ( $field->items as $item ) {
                                    // Customer select many values ( checkbox )
                                    if ( is_array( $customer_custom_field['value'] ) ) {
                                        foreach ( $customer_custom_field['value'] as $field_value ) {
                                            if ( $item['value'] == $field_value ) {
                                                $translated_value[] = $item['label'];
                                            }
                                        }
                                    } elseif ( $item['value'] == $customer_custom_field['value'] ) {
                                        $translated_value[] = $item['label'];
                                    }
                                }
                            } else {
                                $translated_value[] = $customer_custom_field['value'];
                            }
                        }
                        $result[] = array(
                            'id'    => $customer_custom_field['id'],
                            'label' => $field->label,
                            'value' => implode( ', ', $translated_value )
                        );
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get formatted custom fields.
     *
     * @param string $format
     * @return string
     */
    public function getFormattedCustomFields( $format )
    {
        $result = '';
        switch ( $format ) {
            case 'html':
                foreach ( $this->getCustomFields() as $custom_field ) {
                    if ( $custom_field['value'] != '' ) {
                        $result .= sprintf(
                            '<tr valign=top><td>%s:&nbsp;</td><td>%s</td></tr>',
                            $custom_field['label'], $custom_field['value']
                        );
                    }
                }
                if ( $result != '' ) {
                    $result = "<table cellspacing=0 cellpadding=0 border=0>$result</table>";
                }
                break;

            case 'text':
                foreach ( $this->getCustomFields() as $custom_field ) {
                    if ( $custom_field['value'] != '' ) {
                        $result .= sprintf(
                            "%s: %s\n",
                            $custom_field['label'], $custom_field['value']
                        );
                    }
                }
                break;
        }

        return $result;
    }

 

    static public function statusToString( $status )
    {
        switch ( $status ) {
            case self::STATUS_PENDING:   return __( 'Pending',   'bookly' );
            case self::STATUS_APPROVED:  return __( 'Approved',  'bookly' );
            case self::STATUS_CANCELLED: return __( 'Cancelled', 'bookly' );
            case self::STATUS_REJECTED:  return __( 'Rejected',  'bookly' );
            default: return '';
        }
    }
	

}