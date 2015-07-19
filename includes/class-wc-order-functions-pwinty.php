<?php
/**
 * Order Actions for Pwinty Integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


		// Actions.
		add_action( 'init', 'register_submitted_status' );
		add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
		add_action('woocommerce_checkout_order_processed', 'pwinty_create_order', 10, 1);
		add_action( 'woocommerce_order_status_submitted_to_pwinty', 'pwinty_submit_order', 20, 1);
		add_action( 'woocommerce_order_status_processing', 'pwinty_add_photos_to_order', 20, 1);		
        add_action( 'woocommerce_api_pwintyhandler', 'pwinty_callback_handler' );
		
		// Filters.
		add_filter( 'wc_order_statuses', 'add_submitted_to_order_statuses' );
		add_filter( 'woocommerce_admin_order_actions', 'add_submit_to_order_admin_actions', 10, 3 ); 
		

// Register new status
function register_submitted_status() {
    register_post_status( 'wc-submitted', array(
        'label'                     => 'Submitted',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Submitted <span class="count">(%s)</span>', 'Submitted <span class="count">(%s)</span>' )
    ) );
}


// Add to list of WC Order statuses
function add_submitted_to_order_statuses( $order_statuses ) {

    $new_order_statuses = array();

    // add new order status after processing
    foreach ( $order_statuses as $key => $status ) {

        $new_order_statuses[ $key ] = $status;

        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-submitted'] = 'Submitted';
        }
    }

    return $new_order_statuses;
}


function add_submit_to_order_admin_actions($actions) {
			
								global $post;
								global $the_order;  // globals maybe not ideal but unsure how to do better, only way I could get $the_order->has_status and $post->ID populated
								
								$actions = array();
								if ( $the_order->has_status( array( 'pending', 'on-hold' ) ) ) {
									$actions['processing'] = array(
										'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $post->ID ), 'woocommerce-mark-order-status' ),
										'name'      => __( 'Processing', 'woocommerce' ),
										'action'    => "processing"
									);
								}
								if ( $the_order->has_status( array( 'processing' ) ) ) {
									$actions['submitted'] = array(
										'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=submitted&order_id=' . $post->ID ), 'woocommerce-mark-order-status' ),
										'name'      => __( 'Submit for Printing ', 'woocommerce' ),
										'action'    => "submitted"
									);
								}
								if ( $the_order->has_status( array( 'submitted', 'processing' ) ) ) {
									$actions['complete'] = array(
										'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $post->ID ), 'woocommerce-mark-order-status' ),
										'name'      => __( 'Complete', 'woocommerce' ),
										'action'    => "complete"
									);
								} 
								$actions['view'] = array(
									'url'       => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
									'name'      => __( 'View', 'woocommerce' ),
									'action'    => "view"
								);
								return $actions;
		}
		


function pwinty_create_order($order_id) {
	$pwinty = new PHPPwinty();
    $order = new WC_Order( $order_id );
	$settings = get_option('woocommerce_woocommerce_pwinty_integration_settings');
	$test = print_r($settings, true);
	error_log($test, 0);
	$country = $settings['pwinty_country'];
	$quality = $settings['pwinty_quality'];
	$tracking = $settings['pwinty_tracked'];
    $user_id = (int)$order->user_id;
	$first_name = get_user_meta( $user_id, 'shipping_first_name', true );
    $last_name = get_user_meta( $user_id, 'shipping_last_name', true );
	$address1 = $order->shipping_address_1;
	$address2 = $order->shipping_address_2;
	$city = $order->shipping_city;
	$state = $order->shipping_state;
	$postcode = $order->shipping_postcode;
	$countryFull = $order->shipping_country;
	$data = array($first_name, $last_name, $address1, $address2, $city, $state, $postcode, $countryFull, $country, $tracking, $quality );
	$test = print_r($data, false);
	error_log($test, 0);
	$order = $pwinty->createOrder(
	$first_name . ' ' .$last_name,     //name
    $address1,    //address1
    $address2,       //address 2
    $city,        //town
    $state,       //state
    $postcode,            //postcode or zip
    $country,               //country code
    "GB",               //destination code
    $tracking,               //tracked shipping
    "InvoiceMe",        //payment method
    $quality               //quality
                                  );	
				  
						  
	update_post_meta( $order_id, 'pwinty_order_id', $order );
}




function pwinty_add_photos_to_order($order_id) {
    $order = new WC_Order($order_id);
	$orderMeta = get_post_custom($order_id);
	$pwinty_order_id = $orderMeta['pwinty_order_id']['0'];
	$pwinty = new PHPPwinty();
    foreach( $order->get_items() as $item_id => $item ){		
	$downloads = $order->get_item_downloads($item);
	foreach ($downloads as $download_id => $file){
	$downloadURL = $file['download_url'];
	$name = $item['name'];
	$test = print_r($orderMeta, true);
	$test2 = print_r($item['item_meta'], true);
	$test3 = print_r($downloadURL, true);
	error_log($test, 0);
	error_log($test2, 0);
	error_log($test3, 0);
	$quantity = $item['qty'];
    $size = $item['pa_print_size'];
	$response = $pwinty->addPhoto(
    $pwinty_order_id,                             //order id
    $size,                              //print size
    $downloadURL,  //image url
    $quantity,                                //print quantity
    "ShrinkToFit",
	"",
	"",
	""
);	
$test = print_r($response, true);
error_log($test, 0);
if ( isset($response['errorMessage']) ) {
	$order->add_order_note('Image ' . $name . ' ' . $size .' failed to add to the Pwinty order.' . $response['errorMessage'],  0);

}
else {
	$order->add_order_note('Image ' . $name . ' ' . $size . ' successfully added To Pwinty Order',  0);
}}
}
	
$pwintyOrderStatus = $pwinty->getOrderStatus($pwinty_order_id);
$test = print_r($pwintyOrderStatus, true);
error_log($test, 0);
	 
	
}
		

		

function pwinty_submit_order($order_id) {

	$errorLog = plugin_dir_path( __FILE__ ).'debug.html';
    $order = new WC_Order($order_id);
	$orderMeta = get_post_custom($order_id);
	$pwinty_order_id = $orderMeta['pwinty_order_id']['0'];
	$pwinty = new PHPPwinty();
	$response = $pwinty->getOrderStatus($pwinty_order_id);	
					 
$printedResponse = print_r($response, true);
error_log($printedResponse, 0);
file_put_contents($errorLog, $printedResponse, FILE_APPEND);

if (isset($response["isValid"])) {
	$pwinty->updateOrderStatus($pwinty_order_id, 'Submitted');
}				 
elseif (empty($response["isValid"])) {
	$order->add_order_note( "Pwinty order is not valid for submission, <a href='$errorLog'> view debug info here.</a>", 0);
	$order->update_status('processing');
}

}
