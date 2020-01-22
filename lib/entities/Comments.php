<?php
namespace Bookly\Lib\Entities;

use Bookly\Lib;

/**
 * Class Comments
 * @package Bookly\Lib\Entities
 */
class Comments extends Lib\Base\Entity
{
    protected static $table = 'ab_customer_comments';

    protected static $schema = array(
        'id'         => array( 'format' => '%d' ),
        'customer_id' => array( 'format' => '%d' ),
        'created_at'   => array( 'format' => '%s' ),
        'comment'   => array( 'format' => '%s' ),
    );
	
    protected static $cache = array();

    
 

}