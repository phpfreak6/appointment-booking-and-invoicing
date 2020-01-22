;(function() {

    angular.module('invoiceDialog', ['ui.date']).directive('invoiceDialog', function() {
        return {
            restrict : 'A',
            replace  : true,
            scope    : {
                callback : '&invoiceDialog',
                form     : '=invoice'
            },
            templateUrl : 'bookly-invoice-dialog.tpl',
            // The linking function will add behavior to the template.
            link: function(scope, element, attrs) {
			    // Init properties.
                // Form fields.
				
                if (!scope.form) {
                    scope.form = {
                        staff_id      : '',
                        customer_id   : '',
                    };
					scope.form.billings = [{'name':'','type':'','price':'','quantity':''}];
					
                }
				
                // Form errors.
                scope.errors = {
                    staff_id: {required: false},
                    customer_id: {required: false},
                    name: {required: false},
                    type: {required: false},
                    price: {required: false},
                    quantity: {required: false},
                    customer_username: {required: false},

                };
				
				scope.addBilling = function() {
					scope.form.billings.push({
								name: '',
								type: '',
								price: '',
								quantity: ''
							});
						},
				scope.removeAllBilling = function(){
					
					scope.form.billings = [];
				}
				
				scope.addNewCustomer = function(){
					element.find(".new_customer_fields").removeClass('hidden');
				}
				scope.removeNewCustomer = function(){
					
					scope.form.customer_wp_user = '';
					scope.form.customer_username = '';
					scope.form.customer_phone = '';
					scope.form.customer_email = '';
					scope.form.customer_notes = '';
					scope.form.customer_birthday = '';
					element.find(".new_customer_fields").addClass('hidden');
				}
				scope.removeBilling = function(index) {
					scope.form.billings.splice(index, 1);
				},		
				
				scope.$watch('form', function(newValue, oldValue) {
					
					if (newValue.staff_id) {
						scope.errors.staff_id.required = false;
					}
					if (newValue.customer_id) {
					   scope.errors.customer_id.required = false;
					 }  
					if (newValue.name) {
					   scope.errors.name.required = false;
					 } 
					if (newValue.type) {
					   scope.errors.type.required = false;
					} 
					if (newValue.price) {
					   scope.errors.price.required = false;
					} 
					if (newValue.quantity) {
					   scope.errors.quantity.required = false;
					} 
					if (newValue.customer_username) {
					   scope.errors.customer_username.required = false;
					} 
					
                });
                // Loading indicator.
				
					
				
                scope.loading = false;
				
                // Init intlTelInput.
                if (BooklyL10nCustDialog.intlTelInput.enabled) {
                    element.find('#phone').intlTelInput({
                        preferredCountries: [BooklyL10nCustDialog.intlTelInput.country],
                        defaultCountry: BooklyL10nCustDialog.intlTelInput.country,
                        geoIpLookup: function (callback) {
                            jQuery.get(ajaxurl, {action: 'bookly_ip_info'}, function () {}, 'json').always(function (resp) {
                                var countryCode = (resp && resp.country) ? resp.country : '';
                                callback(countryCode);
                            });
                        },
                        utilsScript: BooklyL10nCustDialog.intlTelInput.utils
                    });
                }

                // Do stuff on modal hide.
                element.on('hidden.bs.modal', function () {
                    // Fix scroll issues when another modal is shown.
                    if (jQuery('.modal-backdrop').length) {
                        jQuery('body').addClass('modal-open');
                    }
                });
			
                /**
                 * Send form to server.
                 */
                scope.processForm = function() {
					scope.errors  = {};
                    scope.loading = true;
                    jQuery.ajax({
                        url  : ajaxurl,
                        type : 'POST',
                        data : jQuery.extend({ action : 'bookly_save_invoices' }, scope.form),
                        dataType : 'json',
                        success : function ( response ) {
                            scope.$apply(function(scope) {
                                if (response.success) {
                                    response.invoice.custom_fields = [];
                                    response.invoice.extras = [];
                                    response.invoice.status = BooklyL10nCustDialog.default_status;
                                    // Send new invoice to the parent scope.
                                    scope.callback({invoice : response.invoice});
                                    scope.form = {
                                        staff_id         : '',
                                        customer_id 	 : '',
                                    };
									scope.form.billings = [{'name':'','type':'','price':'','quantity':''}];
                                    // Close the dialog.
									element.modal('hide');
                                } else {
                                    // Set errors.
                                    jQuery.each(response.errors, function(field, errors) {
                                        scope.errors[field] = {};
                                        jQuery.each(errors, function(key, error) {
                                            scope.errors[field][error] = true;
                                        });

                                    });
                                }
                                scope.loading = false;
                            });
                        },
                        error : function() {
                            scope.$apply(function(scope) {
                                scope.loading = false;
                            });
                        }
                    });
                };
    
            }
        };
    });

})();