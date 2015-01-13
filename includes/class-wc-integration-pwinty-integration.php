<?php
/**
 * Integration Demo Integration.
 *
 * @package  WC_Integration_Pwinty_Integration
 * @category Integration
 * @author   Steve Honey
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

if ( ! class_exists( 'WC_Integration_Pwinty_Integration' ) ) {

class WC_Integration_Pwinty_Integration extends WC_Integration {

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		global $woocommerce;

		$this->id                 = 'woocommerce_pwinty_integration';
		$this->method_title       = __( 'Pwinty API Integration', 'woocommerce-pwinty-integration' );
		$this->method_description = __( 'An integration with the Pwinty API', 'woocommerce-pwinty-integration' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->pwinty_api_env          = $this->get_option( 'pwinty_api_env' );
		$this->pwinty_api_key          = $this->get_option( 'pwinty_api_key' );
		$this->pwinty_merchant_id      = $this->get_option( 'pwinty_merchant_id' );
		$this->pwinty_country          = $this->get_option( 'pwinty_country' );
		$this->pwinty_quality          = $this->get_option( 'pwinty_quality' );
		$this->pwinty_tracked          = $this->get_option( 'pwinty_tracked' );
		$this->pwinty_debug            = $this->get_option( 'pwinty_debug' );
					
			

		// Actions.
		add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );

	}


	/**
	 * Initialize integration settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
		'pwinty_api_env' => array(
				'title'             => __( 'API Environment', 'woocommerce-pwinty-integration' ),
				'type'              => 'select',
				'description'       => __( 'You should test extensively with the Sandbox before setting to Production. Once in production mode your orders will be sent to the API and chraged to your Pwinty account.', 'woocommerce-pwinty-integration' ),
				'desc_tip'          => false,
				'default'           => 'sandbox',
				'options' => array(
					'sandbox'     => __( 'Sandbox', 'woocommerce' ),
					'production'      => __( 'Production', 'woocommerce' )
				)
			),
			'pwinty_api_key' => array(
				'title'             => __( 'API Key', 'woocommerce-pwinty-integration' ),
				'type'              => 'text',
				'description'       => __( 'Enter your API Key', 'woocommerce-pwinty-integration' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'pwinty_merchant_id' => array(
				'title'             => __( 'Pwinty Merchant ID', 'woocommerce-pwinty-integration' ),
				'type'              => 'text',
				'description'       => __( 'Enter with your Merchant ID', 'woocommerce-pwinty-integration' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'pwinty_country' => array(
				'title'             => __( 'Country', 'woocommerce-pwinty-integration' ),
				'type'              => 'select',
				'class'             => ' ',
				'autoload'        => false,
				'description'       => __( '' ),
				'desc_tip'          => false,
				'default'           => 'UK',
				'options' => array(
					'AT'     => __( 'Austria', 'woocommerce' ),
					'AU'     => __( 'Australia', 'woocommerce' ),
					'BE'     => __( 'Belgium', 'woocommerce' ),
					'BR'     => __( 'Brazil', 'woocommerce' ),
					'CA'     => __( 'Canada', 'woocommerce' ),
					'CL'     => __( 'Chile', 'woocommerce' ),
					'DK'     => __( 'Denmark', 'woocommerce' ),
					'FR'     => __( 'France', 'woocommerce' ),
					'DE'     => __( 'Germany', 'woocommerce' ),
					'IE'     => __( 'Ireland', 'woocommerce' ),
					'IT'     => __( 'Italy', 'woocommerce' ),
					'MX'     => __( 'Mexico', 'woocommerce' ),
					'NL'     => __( 'Netherlands', 'woocommerce' ),
					'ES'     => __( 'Spain', 'woocommerce' ),
					'SE'     => __( 'Sweden', 'woocommerce' ),
					'CH'     => __( 'Switzerland', 'woocommerce' ),
					'GB'     => __( 'United Kingdom', 'woocommerce' ),
					'US'     => __( 'United States', 'woocommerce' )
				)
			),
			'pwinty_quality' => array(
				'title'             => __( 'Print Quality', 'woocommerce-pwinty-integration' ),
				'type'              => 'select',
				'description'             => __( 'Use Pro level print quality when available?', 'woocommerce-pwinty-integration' ),
				'desc_tip'          => false,
				'default'           => 'Standard',
				'options' => array(
					'Standard'     => __( 'Standard', 'woocommerce' ),
					'Pro'      => __( 'Pro', 'woocommerce' )
				)
			),
			'pwinty_tracked' => array(
				'title'             => __( 'Tracked Shipping', 'woocommerce-pwinty-integration' ),
				'type'              => 'select',
				'description'             => __( 'Use Tracked shipping when available?', 'woocommerce-pwinty-integration' ),
				'desc_tip'          => false,
				'default'           => 'false',
				'options' => array(
					'false'     => __( 'Standard &#40;Cheaper&#44; Slower&#41;', 'woocommerce' ),
					'true'      => __( 'Tracked &#40;Costs More&#44; Faster&#41;', 'woocommerce' )
				)
			),
			'debug' => array(
				'title'             => __( 'Debug Log', 'woocommerce-pwinty-integration' ),
				'type'              => 'checkbox',
				'label'             => __( 'Enable logging', 'woocommerce-pwinty-integration' ),
				'default'           => 'no',
				'description'       => __( 'Log events such as API requests', 'woocommerce-pwinty-integration' ),
			)
		);
	}
		


}
}