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
			// Include our integration class.
			include_once 'includes/class-wc-integration-pwinty-integration.php';
			include_once 'includes/wc-order-functions-pwinty.php';
			include_once 'includes/PHPPwinty.php';


			// Register the integration.
			add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
			add_action( 'woocommerce_api_pwintyhandler', 'pwinty_callback_handler' );
		} else {
			// throw an admin error if you like
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
