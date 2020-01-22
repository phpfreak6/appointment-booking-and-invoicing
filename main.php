<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
Plugin Name: Bookly
Plugin URI: http://booking-wp-plugin.com
Description: Bookly Plugin – is a great easy-to-use and easy-to-manage booking tool for service providers who think about their customers. The plugin supports a wide range of services provided by business and individuals who offer reservations through websites. Set up any reservation quickly, pleasantly and easily with Bookly!
Version: 13.8
Author: Ladela Interactive
Author URI: http://booking-wp-plugin.com
Text Domain: bookly
Domain Path: /languages
License: Commercial
*/

// LOIS CHANGED
add_filter( 'page_template', 'wp_page_template' );
add_shortcode( 'show_bookly_invoice', 'view_invoices' );
add_action( 'wp_ajax_bookly_get_invoices', 'view_invoice_list' );
// LOIS add action - suffix + type + methode  => wp_ajax_bookly_get_invoices

 

function view_invoice_list( $atts ){
	global $wpdb;
	//		print_r($_REQUEST);			print_r($_GET); 
	// try got invoice id
	if ( isset ( $_GET['no'] ) && $_GET['no'] != '' ){
		$id = $_GET['no'];
	}elseif(isset($_REQUEST['data'])){
		$id = $_REQUEST['data'];
	}
	
		$pfx = $wpdb->prefix;
		$invoice_query = "SELECT i.id as invoice_id, i.*,c.*,c.name as customer_name,ipos.*,ipos.name as position_name 
			FROM ".$wpdb->prefix."ab_invoice AS `i` 
			INNER JOIN ".$wpdb->prefix."ab_customers AS `c` 
				ON c.id = i.customer_id 
			INNER JOIN ".$wpdb->prefix."ab_invoice_appointment AS `ipos` 
				ON ipos.aid = i.id 
			WHERE i.id='".$id."'";
		$invoice_query = "SELECT i.id as invoice_id, i.*,c.*,c.name as customer_name
			FROM ".$wpdb->prefix."ab_invoice AS `i` 
			INNER JOIN ".$wpdb->prefix."ab_customers AS `c` 
				ON c.id = i.customer_id  
			WHERE 1"; 

		$invoice_result = $wpdb->get_results( $invoice_query );
		
		//echo json_encode($invoice_result);
		echo '{"draw":3,"recordsTotal":1,"recordsFiltered":1,"data":[{"id":"1","bill_date":"Customer 1 edited"}],"customers":[{"id":"1","name":"Customer 1 edited"},{"id":"2","name":"Custoomer 2"},{"id":"3","name":"test"},{"id":"4","name":"asd"}]}';
		exit;
	//}else{		return false;	}
}

    function wp_page_template( $page_template )
    {
        if ( is_page( 'invoice' ) ) {
            $page_template = plugin_dir_path( __FILE__ ) . 'template/view_invoice.php';
        }
        return $page_template;
    }

function view_invoices( $atts ){
		if ( isset ( $_GET['no'] ) && $_GET['no'] != '' ) {
		    global $wpdb;
				 $query = "SELECT i.id as invoice_id, i.*,wc.*,wc.name as customer_name,ip.*,ip.name as position_name 
				 FROM ".$wpdb->prefix."ab_invoice AS `i` 
				 LEFT JOIN ".$wpdb->prefix."ab_customers AS `wc` 
					ON wc.id = i.customer_id 
				 LEFT JOIN ".$wpdb->prefix."ab_invoice_appointment AS `ip` 
					ON ip.aid = i.id 
				 WHERE i.bill_no='".$_GET['no']."'"; 
				$results = $wpdb->get_results($query);
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'appointment-booking/frontend/invoices.php';
			 }
}
// LOIS CHANGED


if ( version_compare( PHP_VERSION, '5.3.7', '<' ) ) {
    add_action( is_network_admin() ? 'network_admin_notices' : 'admin_notices', create_function( '', 'echo \'<div class="updated"><h3>Bookly</h3><p>To install the plugin - <strong>PHP 5.3.7</strong> or higher is required.</p></div>\';' ) );
} else {
    include_once __DIR__ . '/autoload.php';

    // Fix possible errors (appearing if "Nextgen Gallery" Plugin is installed) when Bookly is being updated.
    add_filter( 'http_request_args', create_function( '$args', '$args[\'reject_unsafe_urls\'] = false; return $args;' ) );

    call_user_func( array( '\Bookly\Lib\Plugin', 'run' ) );
    $app = is_admin() ? '\Bookly\Backend\Backend' : '\Bookly\Frontend\Frontend';
    new $app();
}

add_action( 'wp_ajax_editdetailcustomer', 'editdetailcustomer' );
add_action( 'wp_ajax_nopriv_editdetailcustomer', 'editdetailcustomer' );

function editdetailcustomer(){
	
	global $wpdb;
	if(isset($_REQUEST['data'])){
		$id = $_REQUEST['data'];
		$pfx = $wpdb->prefix;
		
		$user_info = $wpdb->get_results( "Select * from ".$pfx."ab_customers where id=$id" );
		echo json_encode($user_info);
		exit;
	}else{
		return false;
	}
}

add_action( 'wp_ajax_chat_history', 'chat_history' );
add_action( 'wp_ajax_nopriv_chat_history', 'chat_history' );

function chat_history(){
	global $wpdb;
	if(isset($_REQUEST['data'])){
		$id = $_REQUEST['data'];
		$pfx = $wpdb->prefix;
		$chat_result = $wpdb->get_results( "SELECT a.start_date as appointment_date, s.full_name as staff_name, ca.custom_fields as json_query, ser.title, ser.color
		FROM `".$pfx."ab_customer_appointments` as ca
		INNER JOIN ".$pfx."ab_appointments as a
		ON a.id = ca.`appointment_id`
		INNER JOIN ".$pfx."ab_staff as s
		ON a.staff_id = s.id
		INNER JOIN wp_ab_services as ser
		ON ser.id = a.service_id
		WHERE ca.customer_id = $id
		ORDER BY a.start_date" );
		foreach($chat_result as $rkey => $row){
			$json_data = json_decode($row->json_query);
			foreach($json_data as $jkey => $jdata){
				if(isset($jdata->id) && intval($jdata->id) == 1){
					$chat_result[$rkey]->text = $jdata->value;
				}
			}
		} 
		
		echo json_encode($chat_result);
		exit;
	}else{
		return false;
	}
}

