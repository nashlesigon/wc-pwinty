<?php
/**
 * Order Related Functions for Pwinty Integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}		

// Create an empty pwinty order with customer info when a sale is processed, append the order id from pwinty to the order in custom field

function pwinty_create_order($order_id) {
	$settings = get_option('woocommerce_woocommerce_pwinty_integration_settings');
    $order = new WC_Order( $order_id );
	$pwinty = new PHPPwinty();
	$recipientName = $order->shipping_first_name . ' ' . $order->shipping_last_name;
	$newPwintyOrderID = $pwinty->createOrder( $recipientName, $order->shipping_address_1, $order->shipping_address_2, $order->shipping_city, $order->shipping_state, $order->shipping_postcode, $settings['pwinty_country'], $order->shipping_country, $settings['pwinty_tracked'], "InvoiceMe", $settings['pwinty_quality'] );
	update_post_meta( $order_id, 'pwinty_order_id', $newPwintyOrderID );
	} 

add_action( 'woocommerce_checkout_order_processed', 'pwinty_create_order', 10, 1);

// When order is marked processing (ie payment received from gateway or acknowledged by store owner marking as processing) add the images
// to the order. Adds order note with success message or error message for each image.

function pwinty_add_photos_to_order($order_id) {
	
	$settings = get_option('woocommerce_woocommerce_pwinty_integration_settings');
    $order = new WC_Order($order_id);
	$orderMeta = get_post_custom($order_id);
	$pwintyOrderId = $orderMeta['pwinty_order_id']['0'];
    $items = $order->get_items(); 
	
    foreach ( $items as $item ){
		
	$name = $item['name'];
	$quantity = $item['qty'];
    $sizeType = $item['pa_print_variations'];
	$downloadURL = get_post_meta($item['product_id'], 'download_url', true);
	
	$data = array(
			"type" => $sizeType,
			"url" => $downloadURL,
			"copies" => $quantity,
			"sizing" => "ShrinkToFit"
		);
		
		$pwinty = new PHPPwinty();

		$response = $pwinty->addPhoto( $pwintyOrderId, $sizeType, $downloadURL, $quantity );	

		  ob_start();
		  
		  if ( isset($response['errorMessage']) ) {
			  $order->add_order_note('Image ' . $name . $sizeType .' failed to add to the Pwinty order. Error: ' . $response['errorMessage'],  0);
		  }
		  else {
			  $order->add_order_note('Image ' . $name .' '. $sizeType . ' added To Pwinty Order',  0);
		  }
		  
		   ob_end_flush();	 
		  }

    }	

add_action( 'woocommerce_order_status_processing', 'pwinty_add_photos_to_order', 20, 1);
		
// Submit the order to pwinty - final approval of order by store owner. 

function pwinty_submit_order( $order_id ) {
    $order = new WC_Order($order_id);
	$orderMeta = get_post_custom($order_id);
	$pwintyOrderId = $orderMeta['pwinty_order_id']['0'];
	$pwinty = new PHPPwinty();
	ob_start();
	$response = $pwinty->getOrderStatus($pwintyOrderId);
	  if ($response["isValid"] == '1') {
		  $pwinty->updateOrderStatus($order_id, 'Submitted');
	  }				 
	  else {
		  wp_mail(ADMIN_EMAIL, 'Pwinty Order Submission Error Report', print_r($response, true));
		  $order->add_order_note( "Pwinty order is not valid for submission. An error report has been sent to the site admin's email address.", 0);
	  }
     ob_end_flush();
}

add_action( 'woocommerce_order_status_submitted', 'pwinty_submit_order', 20, 1);

// Handler for callbacks from Pwinty

function pwinty_callback_handler(){
	 error_log(print_r($_POST, true));
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
     $orderGet = get_posts( $args ); 
	 $order = new WC_Order($orderGet[0]->ID);
	 switch ($status){
		case 'NotYetSubmitted':	
		  $order->add_order_note('Pwinty Order Created', 0);
		break; 
		case 'Submitted':
		  $order->add_order_note('Successfully Submitted to Pwinty',  0);            
		break;				  
		case 'Complete':
		  $pwinty = new PHPPwinty();
		  $pwintyOrder = $pwinty->getOrder($pwintyOrderId);
		  $trackingURL = $pwintyOrder["0"]["shippingInfo"]["trackingUrl"];
		  update_post_meta( $order_id, 'pwinty_tracking_url', $trackingURL );
		  $order->add_order_note('<p>Pwinty order has been dispatched.</p><p><a href="'.$trackingURL.'">'.$trackingURL."</a></p>", 1);
		  $order->update_status('completed'); 
		break;      
	  }
}

add_action( 'woocommerce_api_pwintyhandler', 'pwinty_callback_handler' );