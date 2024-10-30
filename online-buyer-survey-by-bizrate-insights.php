<?php
/**
 * Plugin Name: Online Buyer Survey by Bizrate Insights
 * Plugin URI: http://woocommerce.com/products/woocommerce-extension/
 * Description: An extension to push analytics to bizrate when order placed.
 * Author: Bizrate Insights
 * Author URI: https://bizrateinsights.com/
 * Version: 1.0.0
 * WC requires at least: 5.0
 * WC tested up to: 6.3.1
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: online-buyer-survey-by-bizrate-insights
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bizrate_Insights_Integration' ) ) {
	define( 'BIZRATE_INSIGHTS_INTEGRATION_VERSION', '1.0.0' ); // WRCS: DEFINED_VERSION.

	// Maybe show the Bizrate Insights notice on plugin activation.
	register_activation_hook(
		__FILE__,
		function () {
			Bizrate_Insights_Integration::get_instance()->maybe_show_bizrate_notices();
		}
	);

	/**
	 * Online Buyer Survey by Bizrate Insights Integration main class.
	 */
	class Bizrate_Insights_Integration {
		/**
		 * Class instance will be stored here
		 *
		 *  @var Bizrate_Insights_Integration $instance Instance of this class.
		*/
		protected static $instance = null;

		/**
		 * Initialize the plugin.
		 */
		public function __construct() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
				return;
			}

			// Load plugin text domain
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

			// Track completed orders and determine whether the Bizrate Insights notice should be displayed.
			add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_show_bizrate_notices' ) );

			// Checks which WooCommerce is installed.
			if ( class_exists( 'WC_Integration' ) && defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '5.0', '>=' ) ) {
				include_once 'includes/class-wc-bizrate-insights.php';

				// Register the integration.
				add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			}

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_links' ) );

			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
		}

		/**
		 * Return an instance of this class.
		 *
		 * @return Bizrate_Insights_Integration A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * WooCommerce fallback notice.
		 */
		public function woocommerce_missing_notice() {
			/* translators: %s: Link tag */
			echo '<div class="notice notice-error"><p>' . sprintf( 'Online Buyer Survey by Bizrate Insights requires WooCommerce to be installed and active. You can download %s from here.', '<a href="https://woocommerce.com/" target="_blank"> WooCommerce </a>' ) . '</p></div>';
		}

		/**
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'online-buyer-survey-by-bizrate-insights' );

			load_textdomain( 'online-buyer-survey-by-bizrate-insights', trailingslashit( WP_LANG_DIR ) . 'online-buyer-survey-by-bizrate-insights/online-buyer-survey-by-bizrate-insights-' . $locale . '.mo' );
			load_plugin_textdomain( 'online-buyer-survey-by-bizrate-insights', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}


		/**
		 * Add a new integration to WooCommerce.
		 *
		 * @param  array $integrations WooCommerce integrations.
		 * @return array               Bizrate Insights integration added.
		 */
		public function add_integration( $integrations ) {
			$integrations[] = 'WC_Bizrate_Insights';

			return $integrations;
		}

		/**
		 * Logic for Bizrate Insights notices.
		 */
		public function maybe_show_bizrate_notices() {
			// Notice was already shown
			if ( ! class_exists( 'WooCommerce' ) || get_option( 'woocommerce_bizrate_insights_notice_shown', false ) ) {
				return;
			}

			$completed_orders = wc_orders_count( 'completed' );

			// Only show the notice if there are 10 <= completed orders <= 100.
			if ( $completed_orders < 10 || $completed_orders > 100 ) {
				update_option( 'woocommerce_bizrate_insights_notice_shown', true );

				return;
			}

			$notice_html = '<strong>' . esc_html__( 'Get detailed insights into your sales with Bizrate Insights', 'online-buyer-survey-by-bizrate-insights' ) . '</strong><br><br>';

			/* translators: 1: href link to Bizrate Insights */
			$notice_html .= sprintf( __( 'Add advanced tracking for your sales funnel, coupons and more. [<a href="%s" target="_blank">Learn more</a> &gt;]', 'online-buyer-survey-by-bizrate-insights' ), 'Woocommerce plugin link' );

			WC_Admin_Notices::add_custom_notice( 'woocommerce_bizrate_notice', $notice_html );
			update_option( 'woocommerce_bizrate_insights_notice_shown', true );
		}

		/**
		 * Add links on the plugins page (Settings & Support)
		 *
		 * @param  array $links Default links
		 * @return array        Default + added links
		 */
		public function plugin_links( $links ) {
			$settings_url = add_query_arg(
				array(
					'page'    => 'wc-settings',
					'tab'     => 'integration',
					'section' => 'bizrate_insights',
				),
				admin_url( 'admin.php' )
			);

			$plugin_links = array(
				'<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'online-buyer-survey-by-bizrate-insights' ) . '</a>',
				'<a href="support link of plugin">' . __( 'Support', 'online-buyer-survey-by-bizrate-insights' ) . '</a>',
			);

			return array_merge( $plugin_links, $links );
		}
	}
	add_action( 'plugins_loaded', array( 'Bizrate_Insights_Integration', 'get_instance' ), 0 );
}
