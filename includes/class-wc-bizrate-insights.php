<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Bizrate_Insights extends WC_Integration {
	public function bizrate_insights_tracking_instance( $options = array() ) {
		return WC_Bizrate_Insights_Script::get_instance( $options );
	}

	/**
	 * Constructor
	 * Init and hook in the integration.
	 */
	public function __construct() {
		$this->id                    = 'bizrate_insights';
		$this->method_title          = __( 'Bizrate Insights', 'online-buyer-survey-by-bizrate-insights' );
		$this->method_description    = __( 'Collect, review, and respond to real-time feedback from verified buyers after they have completed an online purchase. Invitations are rendered on the confirmation page and via email to collect feedback about the end-to-end purchase. A second survey is sent post delivery to collect details about the delivery and receipt experience.' );
		$this->dismissed_info_banner = get_option( 'woocommerce_dismissed_info_banner' );

		$this->init_form_fields();
		$this->init_settings();
		$constructor = $this->init_options();

		include_once 'class-wc-bizrate-insights-script.php';
		$this->bizrate_insights_tracking_instance( $constructor );

		add_action( 'woocommerce_update_options_integration_bizrate_insights', array( $this, 'process_admin_options' ) );

		// Tracking code
		add_action( 'wp_head', array( $this, 'wc_bizrate_insights_tracking_code_show' ), 999999 );
	}

	/**
	 * Tells WooCommerce which settings to display under the "integration" tab
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'bizrate_insights_mid'                   => array(
				'title'       => __( 'Bizrate Insights Merchant Id (MID)', 'online-buyer-survey-by-bizrate-insights' ),
				'description' => __( 'Please provide the MID provided by Bizrate Insights.  You can request a Merchant Id here: <a href="https://bizrateinsights.com/register/" target="_blank">https://bizrateinsights.com/register/</a>', 'online-buyer-survey-by-bizrate-insights' ),
				'type'        => 'text',
				'placeholder' => '',
				'default'     => get_option( 'woocommerce_bizrate_insights_mid' ), // Backwards compat
			),
			'bizrate_insights_gtin_enable'           => array(
				'title'         => __( 'Enable Product Reviews', 'online-buyer-survey-by-bizrate-insights' ),
				'label'         => __( 'Enable Product Reviews *', 'online-buyer-survey-by-bizrate-insights' ),
				'description'   => __( 'When this feature is enabled, Product related review questions will appear in your customer\'s survey. *This feature requires a Global Trade Item Number (GTIN).  Please visit the WordPress marketplace to learn more about using extensions that support GTIN.', 'online-buyer-survey-by-bizrate-insights' ),
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'default'       => get_option( 'woocommerce_bizrate_insights_gtin_enable' ), // Backwards compat
			),
			'bizrate_insights_webanalyticsid_enable' => array(
				'title'         => __( 'Enable use of your own Web Analytics Id', 'online-buyer-survey-by-bizrate-insights' ),
				'description'   => __( 'This feature will allow you to pass a Web Analytics Id which can be included in custom reporting available through your account manager.', 'online-buyer-survey-by-bizrate-insights' ),
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'default'       => get_option( 'woocommerce_bizrate_insights_webanalyticsid_enable' ), // Backwards compat
			),
			'bizrate_insights_webanalyticsid'        => array(
				'title'         => __( 'Specify a Web Analytics Id', 'online-buyer-survey-by-bizrate-insights' ),
				'description'   => __( 'Specify the Id you would like to pass to Bizrate Insights.', 'online-buyer-survey-by-bizrate-insights' ),
				'type'          => 'text',
				'checkboxgroup' => '',
				'default'       => get_option( 'woocommerce_bizrate_insights_webanalyticsid' ), // Backwards compat
				'class'         => 'webanalyticsid-setting',
			),
			'bizrate_insights_custom_value1_enable'  => array(
				'title'         => __( 'Enable custom property #1', 'online-buyer-survey-by-bizrate-insights' ),
				'description'   => __( 'This feature will allow you to pass a Custom Property  which can be included in custom reporting available through your account manager.', 'online-buyer-survey-by-bizrate-insights' ),
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'default'       => get_option( 'woocommerce_bizrate_insights_custom_value1_enable' ), // Backwards compat
			),
			'bizrate_insights_custom_value1_key'     => array(
				'title'         => __( 'Specify the name of custom property', 'online-buyer-survey-by-bizrate-insights' ),
				'description'   => __( 'Add the name of of the property that will be passed to Bizrate Insights.', 'online-buyer-survey-by-bizrate-insights' ),
				'type'          => 'text',
				'checkboxgroup' => '',
				'default'       => get_option( 'woocommerce_bizrate_insights_custom_value1_key' ), // Backwards compat
				'class'         => 'custom_value1-setting',
			),
			'bizrate_insights_custom_value1'         => array(
				'title'         => __( 'Specify the value for this custom property.', 'online-buyer-survey-by-bizrate-insights' ),
				'description'   => __( 'Specifiy the value that will be passed with the respective property.', 'online-buyer-survey-by-bizrate-insights' ),
				'type'          => 'text',
				'checkboxgroup' => '',
				'default'       => get_option( 'woocommerce_bizrate_insights_custom_value1' ), // Backwards compat
				'class'         => 'custom_value1-setting',
			),
			'bizrate_insights_custom_value2_enable'  => array(
				'title'         => __( 'Enable custom property #2', 'online-buyer-survey-by-bizrate-insights' ),
				'description'   => __( 'This feature will allow you to pass a Custom Property  which can be included in custom reporting available through your account manager.', 'online-buyer-survey-by-bizrate-insights' ),
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'default'       => get_option( 'woocommerce_bizrate_insights_custom_value2_enable' ), // Backwards compat
			),
			'bizrate_insights_custom_value2_key'     => array(
				'title'         => __( 'Specify the name of custom property', 'online-buyer-survey-by-bizrate-insights' ),
				'description'   => __( 'Add the name of of the property that will be passed to Bizrate Insights.', 'online-buyer-survey-by-bizrate-insights' ),
				'type'          => 'text',
				'checkboxgroup' => '',
				'default'       => get_option( 'woocommerce_bizrate_insights_custom_value2_key' ), // Backwards compat
				'class'         => 'custom_value2-setting',
			),
			'bizrate_insights_custom_value2'         => array(
				'title'         => __( 'Specify the value for this custom property.', 'online-buyer-survey-by-bizrate-insights' ),
				'description'   => __( 'Specifiy the value that will be passed with the respective property.', 'online-buyer-survey-by-bizrate-insights' ),
				'type'          => 'text',
				'checkboxgroup' => '',
				'default'       => get_option( 'woocommerce_bizrate_insights_custom_value2' ), // Backwards compat
				'class'         => 'custom_value2-setting',
			),
		);

		// add Bizrate Insights admin setting page scripts
		wp_enqueue_script( 'wc-bizrate-insights-admin-enhanced-settings', plugins_url( '/assets/js/admin-bizrate-settings.js', dirname( __FILE__ ) ), array(), '1.0', true );
	}

	/**
	 * Loads all of our options for this plugin (stored as properties as well)
	 *
	 * @return array An array of options that can be passed to other classes
	 */
	public function init_options() {
		$options = array(
			'bizrate_insights_mid',
			'bizrate_insights_gtin_enable',
			'bizrate_insights_webanalyticsid_enable',
			'bizrate_insights_webanalyticsid',
			'bizrate_insights_custom_value1_enable',
			'bizrate_insights_custom_value1_key',
			'bizrate_insights_custom_value1',
			'bizrate_insights_custom_value2_enable',
			'bizrate_insights_custom_value2_key',
			'bizrate_insights_custom_value2',
		);

		$constructor = array();
		foreach ( $options as $option ) {
			$constructor[ $option ] = $this->$option = $this->get_option( $option );
		}

		return $constructor;
	}

	/**
	 * Display the tracking codes
	 * Acts as a controller to figure out which code to display
	 */
	public function wc_bizrate_insights_tracking_code_show() {
		global $wp;
		// Check if is order received page and stop when the products and not tracked
		if ( is_order_received_page() ) {
			$order_id = isset( $wp->query_vars['order-received'] ) ? $wp->query_vars['order-received'] : 0;
			if ( 0 < $order_id && 1 !== get_post_meta( $order_id, 'bizrate_insights_tracked', true ) ) {
				$arr = array(
					'script' => array(
						'type'  => array(),
						'async' => array(),
					),
				);
				echo wp_kses( $this->bizrate_insights_track_code( $order_id ), $arr );
			}
		}
	}

	protected function bizrate_insights_track_code( $order_id ) {
		$order = wc_get_order( $order_id );

		// Make sure we have a valid order object.
		if ( ! $order ) {
			return '';
		}
		// Mark the order as tracked.
		update_post_meta( $order_id, 'bizrate_insights_tracked', 1 );
		return $this->bizrate_insights_tracking_instance()->tracking_code_display( $order );
	}
}
