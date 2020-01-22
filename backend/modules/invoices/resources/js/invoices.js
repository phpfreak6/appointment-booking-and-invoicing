jQuery(function($) {
	
    var
        $invoices_list      = $('#bookly-invoices-list'),
        $filter             = $('#bookly-filter'),
        $check_all_button   = $('#bookly-check-all'),
        $invoice_dialog     = $('#bookly-invoice-dialog'),
        $add_button         = $('#bookly-add'),
        $bookly_add_position = $('#bookly-add-position'),
        $bookly_delete_position = $('#bookly-remove-position'),
        $bookly_new_customer = $('#bookly-new-customer'),
        $new_customer_fields = $('.new_customer_fields'),
        $remove_new_customer = $('#remove-new-customer'),
        $billing_array =       $('.billing_array'),
        $remove_billing_array = $('.remove-billing-array'),
        $delete_button      = $('#bookly-delete'),
        $delete_dialog      = $('#bookly-delete-dialog'),
        $delete_button_no   = $('#bookly-delete-no'),
        $delete_button_yes  = $('#bookly-delete-yes'),
        $remember_choice    = $('#bookly-delete-remember-choice'),
        $wksendEmail    	= $(document),
        $ajax   = $.ajax,
        remembered_choice,
        row
        ;

    /**
     * Init DataTables.
     */
    var dt = $invoices_list.DataTable({
        order: [[ 0, 'asc' ]],
        info: false,
        searching: false,
		lengthMenu:[ [10, 20, 40,100,200], [10, 20, 40,100,200] ],
        lengthChange: true,
        pageLength: 10,
        pagingType: 'numbers',
        processing: true,
        responsive: true,
        serverSide: true,
        ajax: {
            url: ajaxurl,
            data: function (d) {
			
                return $.extend({}, d, {
                    action: 'bookly_get_invoices',
                    filter: $filter.val()
                });
            },
	    },
         columns: [
            { data: 'bill_no', render:  function(data, type, row, meta){
            if(type === 'display'){
                data = '<a href="' + site_url + '/invoice/?no=' + data + '">' + data + '</a>';
            }
            return data;
         } },
            { data: 'bill_date', render: $.fn.dataTable.render.text() },
            { data: 'amount', render: $.fn.dataTable.render.text() },
            { data: 'payed' },
            { data: 'mandant' },
            { data: 'customer_name' },
            {
                responsivePriority: 1,
                orderable: false,
                searchable: false,
                render: function ( data, type, row, meta ) {
                    return '<button type="button" class="btn btn-default" data-toggle="modal" data-target="#bookly-invoice-dialog"><i class="glyphicon glyphicon-edit"></i> ' + BooklyL10n.edit + '</button>';
                }
            },
			{
                responsivePriority: 1,
                orderable: false,
                searchable: false,
                render: function ( data, type, row, meta ) {
                    return '<button type="button" data-id="' + row.id + '" class="wksendEmail btn btn-default"><i class="glyphicon glyphicon-envelope"></i>Send Email</button>';
                }
            },
            {
                responsivePriority: 1,
                orderable: false,
                searchable: false,
                render: function ( data, type, row, meta ) {
                    return '<input type="checkbox" value="' + row.id + '">';
                }
            }
        ],
        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row pull-left'<'col-sm-12 bookly-margin-top-lg'p>>",
        language: {
            zeroRecords: BooklyL10n.zeroRecords,
            processing:  BooklyL10n.processing
        }  
    });

	
	/**
     * Send invoice email.
     */
    $wksendEmail.on('click', '.wksendEmail', function () {

		 jQuery.ajax({
			method : 'POST',
			url : ajaxurl,
			data : { action : 'bookly_send_invoices' },
			 data : {
                action       : 'bookly_send_invoices',
                data         : $(this).data('id')
            },
			}).success(function(res) {
				alert('Email sent successfully!');
			});
		
    });
	
    /**
     * Select all invoices.
     */
    $check_all_button.on('change', function () {
        $invoices_list.find('tbody input:checkbox').prop('checked', this.checked);
    });

    /**
     * On invoice select.
     */
    $invoices_list.on('change', 'tbody input:checkbox', function () {
        $check_all_button.prop('checked', $invoices_list.find('tbody input:not(:checked)').length == 0);
    });

    /**
     * Edit invoice.
     */
    $invoices_list.on('click', 'button', function () {
        row = dt.row($(this).closest('td'));
    });

    /**
     * New invoice.
     */
    $add_button.on('click', function () {
        row = null;
    });

    /**
     * On show modal.
     */
    $invoice_dialog.on('show.bs.modal', function () {
		var $title = $invoice_dialog.find('.modal-title');
        var $button = $invoice_dialog.find('.modal-footer button:first');
        var invoice;
        if (row) {
            invoice = $.extend({}, row.data());
            $title.text(BooklyL10n.edit_invoice);
            $button.text(BooklyL10n.save);
			jQuery(".new_customer_fields").addClass('hidden');
        } else {
            invoice = {
                staff_id         : '',
                customer_id : '',
            };
			invoice.billings = {};
			invoice.billings = [{'name':'','type':'','price':'','quantity':''}];
			$title.text(BooklyL10n.new_invoice);
            $button.text(BooklyL10n.create_invoice);
        }
		
        var $scope = angular.element(this).scope();
		
		
        $scope.$apply(function ($scope) {
            $scope.invoice = invoice;
           
			
        });
		jQuery("#wp_customer").chosen();
		/* jQuery.ajax({
			method : 'GET',
			url : ajaxurl,
			data : { action : 'bookly_get_customers_new' },
			}).success(function(res) {
				$scope.invoice.customers = res;
			});
		
		 */
    });
	
    /**
     * Delete invoices.
     */
    $delete_button.on('click', function () {
        if (remembered_choice === undefined) {
            $delete_dialog.modal('show');
        } else {
            deleteinvoices(this, remembered_choice);
        }}
    );

    $delete_button_no.on('click', function () {
        if ($remember_choice.prop('checked')) {
            remembered_choice = false;
        }
        deleteinvoices(this, false);
    });

    $delete_button_yes.on('click', function () {
        if ($remember_choice.prop('checked')) {
            remembered_choice = true;
        }
        deleteinvoices(this, true);
    });
	
	/* 
	$bookly_new_customer.on('click', function () {
		
        $new_customer_fields.removeClass('hidden');
        $new_customer_fields.show();
    });
	$remove_new_customer.on('click', function () {
		
       $new_customer_fields.hide();
    }); */
	
	
    function deleteinvoices(button, with_wp_user) {
        var ladda = Ladda.create(button);
        ladda.start();

        var data = [];
        var $checkboxes = $invoices_list.find('tbody input:checked');
        $checkboxes.each(function () {
            data.push(this.value);
        });
        $.ajax({
            url  : ajaxurl,
            type : 'POST',
            data : {
                action       : 'bookly_delete_invoices',
                data         : data,
                with_wp_user : with_wp_user ? 1 : 0
            },
            dataType : 'json',
            success  : function(response) {
                ladda.stop();
                $delete_dialog.modal('hide');
                if (response.success) {
                    dt.ajax.reload(null, false);
                } else {
                    alert(response.data.message);
                }
            }
        });
    }

    /**
     * On filters change.
     */
    $filter.on('keyup', function () { dt.ajax.reload(); });
});

(function() {
    var module = angular.module('invoice', ['invoiceDialog']);
    module.controller('invoiceCtrl', function($scope,$http) {
        $scope.invoice = {
            staff_id         : '',
            customer_id : '',
        };
		/* console.log(window.customers);
		debugger; */
		$scope.invoice.billings = [];
		$scope.invoice.billings = [{'name':'','type':'','price':'','quantity':''}];
		/* $http({
			method : 'GET',
			url : ajaxurl,
			params : { action : 'bookly_get_customers_new' },
			}).success(function(data) {
			
				$scope.invoice.customers = data;
			
			}); */
		$scope.saveInvoice = function(invoice) {
			 jQuery('#bookly-invoices-list').DataTable().ajax.reload(null, false);
        };
    });
	
})();