<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<link rel="stylesheet" href="https://harvesthq.github.io/chosen/chosen.css">
<script type="text/ng-template" id="bookly-invoice-dialog.tpl">
<div id="bookly-invoice-dialog" class="modal fade" tabindex=-1 role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <div class="modal-title h2"><?php _e( 'New invoice', 'bookly' ) ?></div>
            </div>
            <div ng-show=loading class="modal-body">
                <div class="bookly-loading"></div>
            </div>
            <div class="modal-body" ng-hide="loading">
                <div class="form-group">
                    <label for="wp_user"><?php _e( 'Mandant', 'bookly' ) ?></label>
                    <select ng-model="form.staff_id" class="form-control" id="wp_staff" required>
                        <option value=""></option>
							<?php foreach ( $staff_members as $_member ) : ?>
                            <option value="<?php echo $_member['id'] ?>">
                                <?php echo $_member['full_name'] ?>
                            </option>
                        <?php endforeach ?>
                    </select>
					<span style="font-size: 11px;color: red" ng-show="errors.staff_id.required"><?php _e( 'Required', 'bookly' ) ?></span>
                </div>

                <div class="form-group">
                    <label for="username"><?php _e( 'Customer', 'bookly' ) ?></label>
					<div class="row">
					<div class="col-md-12">
				    <div class="col-md-9">
                     <select ng-model="form.customer_id" class="form-control"  id="wp_customer" required>
                       <?php foreach ( $customers as $_customer ) : ?>
                            <option  value="<?php echo $_customer['id'] ?>">
                                <?php echo $_customer['name'] ?>
                            </option>
                        <?php endforeach ?>
                    </select>
					<span style="font-size: 11px;color: red" ng-show="errors.customer_id.required"><?php _e( 'Required', 'bookly' ) ?></span>
					</div>
				    <div class="col-md-3">	
					<button type="button" class="btn btn-success bookly-btn-block-xs" ng-click="addNewCustomer()"><i class="glyphicon glyphicon-plus"></i> New Customer</button>
					</div>
				  </div>
			     </div>		
				</div>
			  	<div class="form-group new_customer_fields hidden">	
				<hr>
				<div class="form-group">
					<div class="row">
						<div class="col-md-3">
							<label for="wp_user"><?php _e( 'New Customer', 'bookly' ) ?></label>
						</div>
						<div class="col-md-1">
							<button type="button" class="btn btn-success bookly-btn-block-xs" id="bookly-remove-customer" ng:click="removeNewCustomer()"><i class="glyphicon glyphicon-trash"></i></button>
						</div>
					</div>
				</div>
					<div class="form-group">
                    <label for="wp_user"><?php _e( 'User', 'bookly' ) ?></label>
                    <select class="form-control"  ng-model="form.customer_wp_user" id="wp_user">
                        <option value=""></option>
                        <?php foreach ( get_users( array( 'fields' => array( 'ID', 'display_name' ), 'orderby' => 'display_name' ) ) as $wp_user ) : ?>
                            <option value="<?php echo $wp_user->ID ?>">
                                <?php echo $wp_user->display_name ?>
                            </option>
                        <?php endforeach ?>
                    </select>
					</div>

					<div class="form-group">
						<label for="username"><?php _e( 'Name', 'bookly' ) ?></label>
						<input class="form-control" type="text" ng-model="form.customer_username" id="username" />
						<span style="font-size: 11px;color: red" ng-show="errors.customer_username.required">><?php _e( 'Required', 'bookly' ) ?></span>
					</div>

					<div class="form-group">
						<label for="phone"><?php _e( 'Phone', 'bookly' ) ?></label>
						<input class="form-control" ng-model="form.customer_phone"  type="text" id="phone" />
					</div>

					<div class="form-group">
						<label for="email"><?php _e( 'Email', 'bookly' ) ?></label>
						<input class="form-control" ng-model="form.customer_email"  type="text" id="email" />
					</div>

					<div class="form-group">
						<label for="notes"><?php _e( 'Notes', 'bookly' ) ?></label>
						<textarea class="form-control" ng-model="form.customer_notes" id="notes"></textarea>
					</div>

					<div class="form-group">
						<label for="birthday"><?php _e( 'Date of birth', 'bookly' ) ?></label>
						<input class="form-control" type="text" ng-model="form.customer_birthday" ui-date="dateOptions" ui-date-format="yy-mm-dd" id="birthday" autocomplete="off" />
					</div>
					<hr>
				 </div> 
                <div class="form-group">
					<div class="row">
						<div class="col-md-12">
							<div class="col-md-5">
								<label for="phone"><?php _e( 'Billing Position', 'bookly' ) ?></label>
								<button type="button" class="btn btn-success bookly-btn-block-xs" id="bookly-add-position" ng-click="addBilling()"><i class="glyphicon glyphicon-plus"></i></button>
								<button type="button" class="btn btn-success bookly-btn-block-xs" ng-click="removeAllBilling()"><i class="glyphicon glyphicon-trash"></i></button>
							</div>
						</div>
					</div>
				</div>
				<div class="form-group billing_array">	
					<div class="row billing_array" ng-repeat="billing in form.billings">
						<div class="col-md-12">
						<div class="col-md-3">
							<input class="form-control" placeholder="Name" type="text" ng:required ng-model=billing.name id="name" />
							<span style="font-size: 11px;color: red" ng-show="errors.name.required"><?php _e( 'Required', 'bookly' ) ?></span>
						</div>
						<div class="col-md-3">
							<input class="form-control" placeholder="Type" type="text" ng:required ng-model=billing.type id="type" />
							<span style="font-size: 11px;color: red" ng-show="errors.type.required"><?php _e( 'Required', 'bookly' ) ?></span>
						</div>
						<div class="col-md-2">
							<input class="form-control" placeholder="Price" type="text" ng:required ng-model=billing.price id="price" />
							<span style="font-size: 11px;color: red" ng-show="errors.price.required"><?php _e( 'Required', 'bookly' ) ?></span>
						</div>
						<div class="col-md-3">
							<input class="form-control" placeholder="Quantity" type="text" ng:required ng-model=billing.quantity id="quantity" />
							<span style="font-size: 11px;color: red" ng-show="errors.quantity.required"><?php _e( 'Required', 'bookly' ) ?></span>
						</div>
						<div class="col-md-1">
							<button type="button" class="btn btn-success bookly-btn-block-xs" id="bookly-remove-position" ng:click="removeBilling($index)"><i class="glyphicon glyphicon-trash"></i></button>
						</div>
					  </div>
                  </div>
				 </div> 
            </div>
			<div class="modal-footer">
                <div ng-hide=loading>
                    <?php \Bookly\Lib\Utils\Common::customButton( null, 'btn-success btn-lg', '', array( 'ng-click' => 'processForm()' ) ) ?>
                </div>
            </div>
        </div>
    </div>
</div>
</script>
<script src="https://harvesthq.github.io/chosen/chosen.jquery.js" type="text/javascript"></script>
<style>
#wp_customer_chosen{
	width: 100%!important;
}
</style>