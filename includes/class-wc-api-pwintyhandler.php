<?php
/**
 * API Handler for Pwinty Integration.
 *
 * @package  WC_API_pwintyhandler
 * @category Pwinty API Callback Handler
 * @author   Steve Honey
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} 

	
class WC_API_pwintyhandler extends WC_API {
	
public function __construct() {
add_action( 'woocommerce_api_pwintyhandler', 'pwinty_callback_handler' );
}
	

public function pwinty_callback_handler(){
	
	 $pwintyOrderId = $_POST["orderId"];
	 $status = $_POST["eventData"];
	 $timeStamp = $_POST["timestamp"];
	 
     $args = array(
	'numberposts' => 1,
	'post_type' => 'shop_order',
	'post_status' => 'processing',
	'meta_key' => 'pwinty_order_id',
	'meta_value' => $pwintyOrderId
                   );
    $orders = get_posts( $args ); 
	
	foreach (  $orders as $order ) { 
	
		$order_id = $order->ID;
		$order = new WC_Order($order_id);
		 
		if ($status == "NotYetSubmitted") {	
		$order->add_order_note('Pwinty Order Created', 0);
		                                      }
		   
		elseif ($status == "Submitted"){
		            $order->add_order_note('Successfully Submitted to Pwinty',  0);            
								  }
		elseif ($status == "Complete"){
					  $pwinty = new PHPPwinty();
					  $pwintyOrder = $pwinty->getOrder($id = $pwintyOrderId);  
					  $trackingURL = $pwintyOrder["shippingInfo"]["trackingUrl"];
					  $order->add_order_note("<a>"  . $trackingURL. "</a>", 1);         
									}
	   
	}
    wp_reset_postdata();
}		
}
