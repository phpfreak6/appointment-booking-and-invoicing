<?php
namespace Bookly\Backend\Modules\Invoices;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Backend\Modules\Invoices
 */
class Controller extends Lib\Base\Controller
{
    const page_slug = 'bookly-invoices';

    protected function getPermissions()
    {
        return array(
            'executeSaveInvoices' => 'user',
        );
    }

    public function index()
    {
        if ( $this->hasParameter( 'import-invoices' ) ) {
            $this->importinvoices();
        }

        $this->enqueueStyles( array(
            'backend'  => array( 'bootstrap/css/bootstrap-theme.min.css', ),
            'frontend' => array( 'css/ladda.min.css', ),
        ) );
		?>
		<script>
		var site_url = "<?php echo site_url(); ?>";
		</script>
       <?php  $this->enqueueScripts( array(
            'backend' => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/datatables.min.js' => array( 'jquery' ),
            ),
            'frontend' => array(
                'js/spin.min.js' => array( 'jquery' ),
                'js/ladda.min.js' => array( 'jquery' ),
            ),
            'module' => array(
                'js/invoices.js' => array( 'bookly-datatables.min.js', 'bookly-ng-invoice_dialog.js' ),
            ),
        ) );

        wp_localize_script( 'bookly-invoices.js', 'BooklyL10n', array(
            'edit'            => __( 'Edit', 'bookly' ),
            'are_you_sure'    => __( 'Are you sure?', 'bookly' ),
            'wp_users'        => get_users( array( 'fields' => array( 'ID', 'display_name' ), 'orderby' => 'display_name' ) ),
            'zeroRecords'     => __( 'No Invoices found.', 'bookly' ),
            'processing'      => __( 'Processing...', 'bookly' ),
            'edit_invoice'   => __( 'Edit invoices', 'bookly' ),
            'new_invoice'    => __( 'New invoices', 'bookly' ),
            'create_invoice' => __( 'Create invoices', 'bookly' ),
            'save'            => __( 'Save', 'bookly' ),
            'search'          => __( 'Quick search invoices', 'bookly' ),
        ) );

        $this->render( 'index' );
    }
    /**
     * Get list of invoices.
     */
    public function executeGetInvoices()
    {		
        global $wpdb;

        $columns = $this->getParameter( 'columns' );
        $order   = $this->getParameter( 'order' );
        $filter  = $this->getParameter( 'filter' );

        $query = Lib\Entities\Invoice::query( 'i' );
		$total = $query->count();
		$query->select('i.id as invoice_id,i.*,wc.*,wc.name as customer_name,st.full_name')
			//  ->CustomtableJoin( 'InvoiceAppointment' , 'wia', 'wia.aid = i.aid' )
			  ->CustomtableJoin( 'Customer', 'wc', 'wc.id = i.customer_id' )
			  ->CustomtableJoin( 'Staff', 'st', 'st.id = i.mandant' );
		 if ($filter != '') {
            $search_value = Lib\Query::escape( $filter );
            $query
                ->whereLike( 'st.full_name', "%{$search_value}%")
                ->whereLike( 'i.bill_no', "%{$search_value}%", 'OR' )
				->whereLike( 'wc.name', "%{$search_value}%",'OR');
         }
		
        foreach ( $order as $sort_by ) {
            $query->sortBy( str_replace( '.', '_', $columns[ $sort_by['column'] ]['data'] ) )
                ->order( $sort_by['dir'] == 'desc' ? Lib\Query::ORDER_DESCENDING : Lib\Query::ORDER_ASCENDING );
        }
		
        $query->limit( $this->getParameter( 'length' ) )->offset( $this->getParameter( 'start' ) );
        $data = array();
        foreach ( $query->fetchArray() as $row ) {
			$row_id = $row['id']; 
			$in_app_query = Lib\Entities\InvoiceAppointment::query( 'wia' )
			->select('*,wia.typ as type,wia.qty as quantity,wia.id as billing_id')
			->where( 'wia.aid', $row_id);
			
			$billingdata = $in_app_query->fetchArray();
			
            $data[] = array(
                'id'                 => $row['invoice_id'],
                'aid'                 => $row['invoice_id'],
                'bill_no'            => $row['bill_no'],
				'staff_id'			 => $row['mandant'],
                'customer_id'        => $row['customer_id'],
                'bill_date'          => $row['bill_date'] ? Lib\Utils\DateTime::formatDateTime( $row['bill_date'] ) : '',
                'amount'             => Lib\Utils\Common::formatPrice( $row['amount'] ),
                'payed'              => Lib\Utils\Common::formatPrice( $row['payed'] ),
                'mandant'            => $row['full_name'],
                'customer_name'      => $row['name'],
				'billings'			 => $billingdata
            );
        }
		$customers = Lib\Utils\Common::isCurrentUserAdmin()
            ? Lib\Entities\Customer::query()->select('id,name')->sortBy( 'id' )->fetchArray()
            : Lib\Entities\Customer::query()->select('id,name')->where( 'wp_user_id', get_current_user_id() )->fetchArray();
		//echo '<pre>' ; print_r($data) ; die;
        wp_send_json( array(
            'draw'            => ( int ) $this->getParameter( 'draw' ),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
			'customers'		  => $customers	
        ) ); 	
		
    }
	

    /**
     * Create or edit a invoice.
     */
    public function executeSaveInvoices()
    {
		global $wpdb;
        $response = array();
        
	$invoice_id = ($this->getParameter( 'id' ) != null ) ? $this->getParameter( 'id' ) : '';
	 if($invoice_id){
		 $params = $this->getPostParameters();
		 $amount = 0;
		 $prerecords = array();
		 foreach($params['billings'] as $key => $billing){
					
					$amount +=$billing['price'];
					if(isset($billing['id'])){
						
						$prerecords[] = $billing['id'];
					}
				}
			
		$delete_invoice_apoointments = $wpdb->query("delete from  ".Lib\Entities\InvoiceAppointment::getTableName()." where id NOT IN ('".implode("','",$prerecords)."') and aid = $invoice_id");
		
		$inoice_update = $wpdb->query("update ".Lib\Entities\Invoice::getTableName()." set amount = $amount where id = $invoice_id");
		
			 foreach($params['billings'] as $key => $billing){
				$billing_name = $billing['name'];
				$billing_type = $billing['type'];
				$billing_price = $billing['price'];
				$billing_quantity = $billing['quantity'];
				$amount +=$billing['price'];
				if(isset($billing['id'])){
					
					$inoice_appoint_update = $wpdb->query("update ".Lib\Entities\InvoiceAppointment::getTableName()." set name = '$billing_name',typ = '$billing_type', price = '$billing_price',qty = '$billing_quantity' where aid = $invoice_id");
	
				}else{
					
					$customer_InvoiceAppointment = new Lib\Entities\InvoiceAppointment();
					$customer_InvoiceAppointment->set( 'ca_id',$params['customer_id'])
						->set( 'start_date','')
						->set( 'end_date','')
						->set( 'price',$billing['price'])
						->set( 'qty',$billing['quantity'])
						->set( 'aid',$invoice_id)
						->set( 'name',$billing['name'])
						->set( 'typ',$billing['type'])
						->set( 'payed_amount','')
						->set( 'payed_date','')
					   ->save();
				}
			}
		$response['success'] = true;
		$response['invoice'] = $params;
	
	}else{
			$params = $this->getPostParameters();
		if ( $params['staff_id'] !== '' && ($params['customer_id'] || ($params['customer_username'] && $params['customer_email']))) {
                if(isset($params['billings'])){
				
				if($params['customer_username'] && $params['customer_email']){
				
						$Customer = new Lib\Entities\Customer();
						$Customer->set( 'name', $params['customer_username'])
						->set( 'email',$params['customer_email'])
						->set( 'phone',isset($params['customer_phone']) ? $this->getParameter( 'customer_phone' ) : '')
						->set( 'wp_user_id',isset($params['customer_wp_user']) ? $params['customer_wp_user'] : '')
						->set( 'notes',isset($params['customer_notes']) ? $params['customer_notes'] : '')
						->set( 'birthday',isset($params['customer_birthday']) ? $params['customer_birthday'] : '') 
						->save();
					
					$customer_id = $Customer->get( 'id' );
				
				}else{
					
					$customer_id = $this->getParameter( 'customer_id' );
					
				}
				
				$amount = 0;
				
				foreach($params['billings'] as $key => $billing){
					
					$amount +=$billing['price']*$billing['quantity'];
					
				}
				
				$customer_invoice = new Lib\Entities\Invoice();
                $customer_invoice->set( 'bill_no',date('YmdHis').rand(10,100))
					->set( 'customer_id',$customer_id)
					->set( 'bill_date','')
                    ->set( 'amount',$amount)
                    ->set( 'payed','')
                    ->set( 'mandant',$params['staff_id'])
                    ->set( 'comment','')
                    ->set( 'send_mail',date( 'Y-m-d H:i:s') )
                    ->set( 'send_sms',date( 'Y-m-d H:i:s'))
                    ->set( 'send_post',date( 'Y-m-d H:i:s'))
                    ->set( 'first_openend','')
                    ->save();
				$customer_invoice_id = $customer_invoice->get('id');
				foreach($params['billings'] as $key => $billing){
				
			// Create CustomerInvoiceAppointment record.
               $customer_InvoiceAppointment = new Lib\Entities\InvoiceAppointment();
                $customer_InvoiceAppointment->set( 'ca_id',$customer_id)
                    ->set( 'start_date','')
                    ->set( 'end_date','')
                    ->set( 'price',$billing['price'])
                    ->set( 'qty',$billing['quantity'])
                    ->set( 'aid',$customer_invoice_id)
                    ->set( 'name',$billing['name'])
                    ->set( 'typ',$billing['type'])
                    ->set( 'payed_amount','')
                    ->set( 'payed_date','')
                   ->save();
				
				}
					$response['success'] = true;
					$response['invoice'] = $params;
			   } else{
					   
						$response['success'] = false;
				
						$response['errors']  = array( 'billings' => array( 'required' ));
			   }
            }else{			
			
				$response['success'] = false;
				
				foreach($this->getPostParameters() as $key => $val){
					
					if($val == ''){
						$response['errors']  = array( $key => array( 'required' ));
						break;
					}
				}
			}
	      }
        
        wp_send_json( $response );
    }

    /**
     * Import invoices from CSV.
     */
    private function importInvoices()
    {
        @ini_set( 'auto_detect_line_endings', true );

        $file = fopen( $_FILES['import_invoices_file']['tmp_name'], 'r' );
        while ( $line = fgetcsv( $file, null, $this->getParameter( 'import_invoices_delimiter' ) ) ) {
            if ( $line[0] != '' ) {
                $invoice = new Lib\Entities\Invoice();
                $invoice->set( 'name', $line[0] );
                if ( isset( $line[1] ) ) {
                    $invoice->set( 'phone', $line[1] );
                }
                if ( isset( $line[2] ) ) {
                    $invoice->set( 'email', $line[2] );
                }
                if ( isset( $line[3] ) && $line[3] != '' ) {
                    $dob = date_create( $line[3] );
                    if ( $dob !== false ) {
                        $invoice->set( 'birthday', $dob->format( 'Y-m-d' ) );
                    }
                }
                $invoice->save();
            }
        }
    }

	
  /**
 * Get Customers.
 */
    public function executeGetCustomersNew()
    {	
       $customers = Lib\Utils\Common::isCurrentUserAdmin()
            ? Lib\Entities\Customer::query()->select('id,name')->sortBy( 'id' )->fetchArray()
            : Lib\Entities\Customer::query()->select('id,name')->where( 'wp_user_id', get_current_user_id() )->fetchArray();
			
			wp_send_json( $customers );
    }
    /**
     * Delete invoices.
     */
    public function executeDeleteInvoices()
    {
		global $wpdb;
        foreach ( $this->getParameter( 'data', array() ) as $id ) {
		
            $invoice = new Lib\Entities\Invoice();
            $invoice->load( $id );
			$invoice->delete();
			
			$invoiceappointment = new Lib\Entities\InvoiceAppointment();
            $invoiceappointment->load( $id );
			$invoiceappointment->delete("aid");
		}
        wp_send_json_success();
    }
	/**
     * Send invoice email.
     */
    public function executeSendInvoices()
    {
		global $wpdb;
		$id = $this->getParameter( 'data', array() );
		$query = "SELECT i.*,wc.* FROM ".$wpdb->prefix."ab_invoice AS `i` LEFT JOIN ".$wpdb->prefix."ab_customers AS `wc` ON wc.id = i.customer_id WHERE i.id='".$id."'";
		$results = $wpdb->get_row($query);
		if(!empty($results)){
			$to = $results->email;
			$subject = 'Mxt-01- Invoice Email';
			$message = "";
			$message .='Send mail :'.$results->send_mail.','."<br><br>";
			$message .='Send Sms :'.$results->send_sms.','."<br><br>";
			$message .='Send Post :'.$results->send_post.','."<br><br>";
			$message .='First Opened :'.$results->first_openend.','."<br><br>";
			$headers = 'Content-Type: text/html; charset=UTF-8';
			wp_mail( $to, $subject, $message, $headers );
		}
    }

    /**
     * Export invoices to CSV
     */
    public function executeExportInvoices()
    {
        global $wpdb;
        $delimiter = $this->getParameter( 'export_invoices_delimiter', ',' );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=invoices.csv' );

        $titles = array(
            'name'     => \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_name' ),
            'wp_user'  => __( 'User', 'bookly' ),
            'phone'    => \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_phone' ),
            'email'    => \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_email' ),
            'notes'    => __( 'Notes', 'bookly' ),
            'last_appointment'   => __( 'Last appointment', 'bookly' ),
            'total_appointments' => __( 'Total appointments', 'bookly' ),
            'payments' => __( 'Payments', 'bookly' ),
            'birthday' => __( 'Date of birth', 'bookly' ),
        );
        $header = array();
        $column = array();

        foreach ( $this->getParameter( 'exp' ) as $key => $value ) {
            $header[] = $titles[ $key ];
            $column[] = $key;
        }

        $output = fopen( 'php://output', 'w' );
        fwrite( $output, pack( 'CCC', 0xef, 0xbb, 0xbf ) );
        fputcsv( $output, $header, $delimiter );

        $rows = Lib\Entities\Invoice::query( 'c' )
            ->select( 'c.*, MAX(a.start_date) AS last_appointment,
                COUNT(a.id) AS total_appointments,
                COALESCE(SUM(p.total),0) AS payments,
                wpu.display_name AS wp_user' )
            ->leftJoin( 'invoiceAppointment', 'ca', 'ca.invoice_id = c.id' )
            ->leftJoin( 'Appointment', 'a', 'a.id = ca.appointment_id' )
            ->leftJoin( 'Payment', 'p', 'p.id = ca.payment_id' )
            ->tableJoin( $wpdb->users, 'wpu', 'wpu.ID = c.wp_user_id' )
            ->groupBy( 'c.id' )
            ->fetchArray();

        foreach ( $rows as $row ) {
            $row_data = array_fill( 0, count( $column ), '' );
            foreach ( $row as $key => $value ) {
                $pos = array_search( $key, $column );
                if ( $pos !== false ) {
                    $row_data[ $pos ] = $value;
                }
            }
            fputcsv( $output, $row_data, $delimiter );
        }

        fclose( $output );

        exit;
    }
}