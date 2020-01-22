<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div id="bookly-tbs" class="wrap">
    <div class="bookly-tbs-body">
        <div class="page-header text-right clearfix">
            <div class="bookly-page-title">
                <?php _e( 'Invoice No:', 'bookly' ) ?>
                <?php echo $_GET['invoice_no'];  ?>
            </div>
            <?php \Bookly\Backend\Modules\Support\Components::getInstance()->renderButtons( $this::page_slug ) ?>
        </div>
        <div class="panel panel-default bookly-main">
            <div class="panel-body">
              <div class="modal-dialog11" role="">
        <div class="modal-content1">
            <div class="modal-header">
                
                <div class="modal-title h2">Payment</div>
            </div>
            <div class="modal-body">    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th width="50%">Customer</th>
                    <th width="50%">Payment</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Rakesh</td>
                    <td>
                        <div>Date: 2. August 2017 11:55</div>
                        <div>Type: Local</div>
                        <div>Status: Pending</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

		<div class="table-responsive">
			<table class="table table-bordered">
				<thead>
					<tr>
						<th>Service</th>
						<th>Date</th>
						<th>Provider</th>
											<th class="text-right">Price</th>
					</tr>
				</thead>
				<tbody>
										<tr>
							<td>
								Service 1                                                    </td>
							<td>16. August 2017 08:00</td>
							<td>Testuser</td>
																			<td class="text-right">
																						$2.00                            <ul class="bookly-list">
															</ul>
							</td>
						</tr>
																		</tbody>
				<tfoot>
					<tr>
						<th rowspan="3" style="border-left-color: white; border-bottom-color: white;"></th>
						<th colspan="2">Subtotal</th>
											<th class="text-right">$2.00</th>
					</tr>
					<tr>
						<th colspan="2">
							Discount                                            </th>
						<th class="text-right">
														$0.00                                            </th>
					</tr>
					<tr>
						<th colspan="2">Total</th>
						<th class="text-right">$2.00</th>
					</tr>
										<tr>
							<td rowspan="2" style="border-left-color:#fff;border-bottom-color:#fff;"></td>
							<td colspan="2"><i>Paid</i></td>
							<td class="text-right"><i>$0.00</i></td>
						</tr>
						<tr>
							<td colspan="2"><i>Due</i></td>
							<td class="text-right"><i>$2.00</i></td>
						</tr>
						<tr>
							<td style="border-left-color:#fff;border-bottom-color:#fff;"></td>
							<td colspan="3" class="text-right"><button type="button" class="btn btn-success ladda-button" id="bookly-complete-payment" data-spinner-size="40" data-style="zoom-in"><i>Complete payment</i></button></td>
						</tr>
								</tfoot>
			</table>
		</div>
	</div>
				<div class="modal-footer">
					<button type="button" class="btn ladda-button btn-lg btn-default" data-spinner-size="40" data-style="zoom-in" data-dismiss="modal"><span class="ladda-label">Close</span></button>            </div>
			</div>
		</div>  
            </div>
        </div>

    </div>
</div>