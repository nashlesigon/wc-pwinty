<?php
/**
 * Order Actions for Pwinty Integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


		// Actions.
		add_action( 'init', 'register_submitted_status' );
		add_action('woocommerce_checkout_order_processed', 'pwinty_create_order', 10, 1);
		add_action( 'woocommerce_order_status_processing', 'pwinty_add_photos_to_order', 20, 1);
		add_action( 'woocommerce_order_status_submitted', 'pwinty_submit_order', 20, 1);
		
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
	  if ( $the_order->has_status( 'processing' ) ) {
		  $actions['submitted'] = array(
			  'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=submitted&order_id=' . $post->ID ), 'woocommerce-mark-order-status' ),
			  'name'      => __( 'Submit for Printing ', 'woocommerce' ),
			  'action'    => "submitted"
		  );
	  }
	  if ( $the_order->has_status( 'submitted' ) ) {
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
    $items = $order->get_items(); 
	
    foreach ( $items as $item ){
			
	$name = $item['name'];
	$quantity = $item['qty'];
    $size = $item['pa_print-size'];
	$downloadURL = get_post_meta($item['product_id'], 'download_url', true);
	$pwinty = new PHPPwinty();
	
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

	  ob_start();
	  if ( isset($response['errorMessage']) ) {
		  $order->add_order_note('Image ' . $name . $size .' failed to add to the Pwinty order.' . $response['errorMessage'],  0);
	  
	  }
	  else {
		  $order->add_order_note('Image ' . $name . $size . ' successfully added To Pwinty Order',  0);
	  }
	  }
		  
	  // $pwintyOrderStatus = $pwinty->getOrderStatus($pwinty_order_id);
	  
	   ob_end_flush();	 
	
}	

		

function pwinty_submit_order($order_id) {
    $order = new WC_Order($order_id);
	$orderMeta = get_post_custom($order_id);
	$pwinty_order_id = $orderMeta['pwinty_order_id']['0'];
	$pwinty = new PHPPwinty();
	ob_start();
	$response = $pwinty->getOrderStatus($pwinty_order_id);
	ob_clean();	

	  if (isset($response["isValid"])) {
		  ob_start();
		  $pwinty->updateOrderStatus($pwinty_order_id, 'Submitted');
		  ob_clean();	
	  }				 
	  elseif (empty($response["isValid"])) {
		  ob_start();
		  $order->add_order_note( "Pwinty order is not valid for submission, <a href='$errorLog'> view debug info here.</a>", 0);
		  $order->update_status('processing');
		  ob_clean();	
	  }



}

