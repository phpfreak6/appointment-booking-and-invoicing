<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div id="bookly-tbs" class="wrap">
    <div class="bookly-tbs-body">
        <div class="page-header text-right clearfix">
            <div class="bookly-page-title">
                <?php _e( 'Invoices', 'bookly' ) ?>
            </div>
            <?php \Bookly\Backend\Modules\Support\Components::getInstance()->renderButtons( $this::page_slug ) ?>
        </div>
        <div class="panel panel-default bookly-main">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <input class="form-control" type="text" id="bookly-filter" placeholder="<?php esc_attr_e( 'Quick search invoice', 'bookly' ) ?>" />
                        </div>
                    </div>
                    <div class="col-md-8 form-inline bookly-margin-bottom-lg  text-right">
                        <div class="form-group">
                            <button type="button" class="btn btn-success bookly-btn-block-xs" id="bookly-add" data-toggle="modal" data-target="#bookly-invoice-dialog"><i class="glyphicon glyphicon-plus"></i> <?php _e( 'New Invoice', 'bookly' ) ?></button>
						 </div>
                    </div>
                </div>

                <table id="bookly-invoices-list" class="table table-striped" width="100%">
                    <thead>
                        <tr>
                            <th><?php _e( 'Invoice No', 'bookly' ) ?></th>
                            <th><?php _e( 'Invoice Date', 'bookly' ) ?></th>
                            <th><?php _e( 'Amount', 'bookly' ) ?></th>
                            <th><?php _e( 'Payed Amount', 'bookly' ) ?></th>
                            <th><?php _e( 'Mandant', 'bookly' ) ?></th>
                            <th><?php _e( 'Customer Name', 'bookly' ) ?></th>
                            <th></th>
                            <th></th>
                            <th width="16"><input type="checkbox" id="bookly-check-all"></th>
                        </tr>
                    </thead>
                </table>

                <div class="text-right bookly-margin-top-lg">
                    <?php \Bookly\Lib\Utils\Common::deleteButton() ?>
                </div>
            </div>
        </div>

        <?php //include '_import.php' ?>
        <?php //include '_export.php' ?>

        <div id="bookly-delete-dialog" class="modal fade" tabindex=-1 role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        <div class="modal-title h2"><?php _e( 'Delete Invoices', 'bookly' ) ?></div>
                    </div>
                    <div class="modal-body">
                        <?php _e( 'You are about to delete invoices.', 'bookly' ) ?>
                        <div class="checkbox">
                            <label>
                                <input id="bookly-delete-remember-choice" type="checkbox" /><?php _e( 'Remember my choice', 'bookly' ) ?>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
						<button type="button" class="btn btn-danger ladda-button" id="bookly-delete-yes" data-spinner-size="40" data-style="zoom-in">
                            <span class="ladda-label"><i class="glyphicon glyphicon-trash"></i> <?php _e( 'Yes', 'bookly' ) ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
		
        <div ng-app="invoice" ng-controller="invoiceCtrl">
            <div invoice-dialog=saveInvoice(invoice) invoice="invoice"></div>
            <?php \Bookly\Backend\Modules\Invoices\Components::getInstance()->renderInvoiceDialog() ?>
        </div>
		
    </div>
</div>