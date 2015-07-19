<?php
/**
 * API Handler for Pwinty Integration.
 *
 * @package  WC_API_Handler_Pwintyhandler
 * @category API Handler
 * @author   Steve Honey
 */

if ( ! class_exists( 'WC_API_Handler_Pwintyhandler' ) ) {

class WC_API_Handler_Pwintyhandler {

public function __construct() {
           add_action( 'woocommerce_api_pwintyhandler', 'pwinty_callback_handler' );
}	

public function pwinty_callback_handler(){
	 $test = print_r($_POST, true);
	 error_log($test, 0);
	 
	 $pwintyOrderId = $_POST["orderId"];
	 $status = $_POST["eventData"];
	 $timeStamp = $_POST["timestamp"];
	 
     $args = array(
	'numberposts' => 1,
	'post_type' => 'shop_order',
	'meta_key' => 'pwinty_order_id',
	'meta_value' => $pwintyOrderId
                   );
    $orders = get_posts( $args ); 
	
	$test = print_r($orders, true);
	
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
					  error_log($pwintyOrder, 0); 
					  $order->add_order_note("<a>"  . $trackingURL. "</a>", 1);         
									}
	   
	}
    wp_reset_postdata();
}		
}
}