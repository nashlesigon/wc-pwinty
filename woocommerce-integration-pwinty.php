<?php
/**
 * Plugin Name: WooCommerce Pwinty Integration
 * Plugin URI: http://stevenhoney.com
 * Description: A plugin that integrates the Pwinty API with Woocommerce.
 * Author: Steve Honey
 * Author URI: http://stevenhoney.com
 * Version: 1.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

if ( ! class_exists( 'WC_Integration_Pwinty' ) ) :

class WC_Integration_Pwinty {

	/**
	* Construct the plugin.
	*/
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	* Initialize the plugin.
	*/
	public function init() {
		
		// Checks if WooCommerce is installed.
		if ( class_exists( 'WC_Integration' ) ) {
			
			
		include_once 'includes/class-wc-integration-pwinty-integration.php';
		
		add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
		
		include_once 'includes/pwinty-wc-custom-post-types-taxonomies.php';
		include_once 'includes/wc-pwinty-add-price-to-variations.php';
		
		add_action( 'init', 'register_submitted_status', 0 );
		add_action( 'init', 'pwinty_album_post_type', 0 );
		add_action( 'init', 'pwinty_print_variations_taxonomy', 0 );		
		add_action( 'woocommerce_register_taxonomy', 'woo_print_variations_attribute_check', 5 ); //may need to be 'after'
		add_action( 'woocommerce_after_register_taxonomy', 'pwinty_add_wc_category_tags_to_pwinty_album', 10 );
        add_action( 'admin_menu', 'pwinty_album_remove_woo_menu_pages', 999 );
		add_action( 'manage_pwinty_print_variations_custom_column', 'add_price_column_content', 5, 3 );
		add_action( 'edit_pwinty_print_variation', 'sync_variation_prices' );
        add_filter( 'manage_edit-pwinty_print_variations_columns', 'add_price_column', 5);
		add_filter( 'wc_order_statuses', 'add_submitted_to_order_statuses' );
		add_filter( 'woocommerce_admin_order_actions', 'add_submit_to_order_admin_actions', 10, 3 ); 
		
		
		include_once 'includes/wc-pwinty-add-price-to-variations.php';
		
		add_action( 'add_attachment', 'pwinty_copy_original_image');
		add_action( 'delete_attachment', 'pwinty_delete_original_image_copy');
		add_action( 'save_post', 'create_product_from_image', 20    );
		
		
		include_once 'includes/wc-order-interactions-pwinty.php';
		include_once 'includes/PHPPwinty.php';
		
		add_action( 'woocommerce_checkout_order_processed', 'pwinty_create_order', 10, 1);
		add_action( 'woocommerce_order_status_processing', 'pwinty_add_photos_to_order', 20, 1);
		add_action( 'woocommerce_order_status_submitted', 'pwinty_submit_order', 20, 1);
		add_action( 'woocommerce_api_pwintyhandler', 'pwinty_callback_handler' );
		add_action( 'woocommerce_email_before_order_table', 'display_pwinty_tracking_link' );
		
		
		// Define Plug In Wide Constants	
		define ("ADMIN_EMAIL", get_option( 'admin_email' ));
		define ("SITE_URL", get_site_url());
		
		} 
	}

	/**
	 * Add a new integration to WooCommerce.
	 */
	public function add_integration( $integrations ) {
		$integrations[] = 'WC_Integration_Pwinty_Integration';
		return $integrations;
	}
	

}

$WC_Integration_Pwinty = new WC_Integration_Pwinty( __FILE__ );

endif;
