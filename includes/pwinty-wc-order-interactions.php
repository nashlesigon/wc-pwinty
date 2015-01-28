<?php
/**
 * Order Related Functions for Pwinty Integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}		

// Create an empty pwinty order with customer info when a sale is processed, append the order id from pwinty to the order
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
	
	$data = array($first_name, $last_name, $address1, $address2, $city, $state, $postcode, $countryFull, $country, $tracking, $quality );;
	
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



// When order is marked processing (ie payment received from gateway or acknowledged by store owner marking as processing) add the images
// to the order. Adds order note with success message or error message.
function pwinty_add_photos_to_order($order_id) {
	
    $order = new WC_Order($order_id);
	$orderMeta = get_post_custom($order_id);
	$pwinty_order_id = $orderMeta['pwinty_order_id']['0'];
    $items = $order->get_items(); 
	
    foreach ( $items as $item ){
			$test = print_r($item, true);
			error_log($test, 0);
	$name = $item['name'];
	$quantity = $item['qty'];
    $size = $item['pa_print_variations'];
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
		  $order->add_order_note('Image ' . $name . $size .' failed to add to the Pwinty order. Error: ' . $response['errorMessage'],  0);
	  
	  }
	  else {
		  $order->add_order_note('Image ' . $name . $size . ' successfully added To Pwinty Order',  0);
	  }
	  }
	  
	   ob_end_flush();	 
	
}	

		
// Submit the order to pwinty - final approval of order by store owner. 
function pwinty_submit_order($order_id) {
    $order = new WC_Order($order_id);
	$orderMeta = get_post_custom($order_id);
	$pwinty_order_id = $orderMeta['pwinty_order_id']['0'];
	$pwinty = new PHPPwinty();
	ob_start();
	$response = $pwinty->getOrderStatus($pwinty_order_id);	
	$errorReport = print_r($response, true);

	  if ($response["isValid"] == '1') {
		  $pwinty->updateOrderStatus($pwinty_order_id, 'Submitted');
	  }				 
	  else {
		  wp_mail(ADMIN_EMAIL, 'Pwinty Order Submission Error Report', $errorReport);
		  $order->add_order_note( "Pwinty order is not valid for submission. An error report has been sent to the site admin's email address.", 0);
	  }
     ob_end_flush();


}

// Handler for callbacks from Pwinty
function pwinty_callback_handler(){

	 $pwintyOrderId = $_POST["orderId"];
	 $status = $_POST["eventData"];
	 $timeStamp = $_POST["timestamp"];
	 
     $args = array(
	'numberposts' => 1,
	'post_type' => 'shop_order',
	'post_status' => 'any',
	'meta_key' => 'pwinty_order_id',
	'meta_value' => $pwintyOrderId
                   );
    $orders = get_posts( $args ); 
	
	foreach (  $orders as $order ) { 
	
		$order = new WC_Order($order->ID);
		 
		if ($status == "NotYetSubmitted") {	
		$order->add_order_note('Pwinty Order Created', 0);
		                                      }
		   
		elseif ($status == "Submitted"){
		$order->add_order_note('Successfully Submitted to Pwinty',  0);            
								  }
		elseif ($status == "Complete"){
		$pwinty = new PHPPwinty();
		$pwintyOrder = $pwinty->getOrder($pwintyOrderId);
		$trackingURL = $pwintyOrder["0"]["shippingInfo"]["trackingUrl"];
		update_post_meta( $order_id, 'pwinty_tracking_url', $trackingURL );
		$order->add_order_note('<p>Pwinty order has been dispatched.</p><p><a href="'.$trackingURL.'">'.$trackingURL."</a></p>", 0);
		$order->update_status('completed');        
									}
	   
	}
    wp_reset_postdata();
}		


