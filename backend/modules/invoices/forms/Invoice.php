<?php
namespace Bookly\Backend\Modules\invoices\Forms;

use Bookly\Lib;

/**
 * Class invoice
 * @package Bookly\Backend\Modules\invoices\Forms
 */
class Invoice extends Lib\Base\Form
{
    protected static $entity_class = 'Invoice';

    public function configure()
    {
        $this->setFields( array(
            'staff_id',
            'customer_id',
            'name',
            'type',
            'price',
            'quantity',
        ) );
    }

}
