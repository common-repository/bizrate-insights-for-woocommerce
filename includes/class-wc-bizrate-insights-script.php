<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


class WC_Bizrate_Insights_Script {

	/**
	 * Insatnce of class will be stored here.
	 *
	 * @var WC_Bizrate_Insights_Script $instance Class Instance
	 */
	protected static $instance;

	/**
	 * Options will be stored here
	 *
	 * @var array $options Inherited Analytics options
	 */
	protected static $options;
	/**
	 * Return one of our options
	 *
	 * @param  string $options Key/name for the option.
	 * @return string Value of the option
	 */
	public static function get_instance( $options = array() ) {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self( $options );
		}
		return self::$instance;
	}

	/**
	 * Return one of our options
	 *
	 * @param  string $option Key/name for the option.
	 * @return string         Value of the option
	 */
	protected static function get( $option ) {
		return self::$options[ $option ];
	}

	/**
	 * Constructor
	 * Init the options
	 *
	 * @param array $options Key/name for the option.
	 */
	public function __construct( $options = array() ) {
		self::$options = $options;
	}

	/**
	 * Generic header script open
	 *
	 * @return string with the script code
	 */
	private function script_open() {
		return '
		<script type="text/javascript" async="true">
			var _cnx = _cnx || [];
		';
	}

	// Generic header script close.

	private function script_close() {
		$bi_url = "('https:' == document.location.protocol ? 'https://' : 'http://') + 'insights.bizrate.com/js/init.js'";

		return '
		(function (w, d, t) {
			var s = d.createElement(t);
			s.async = true;
			s.src = ' . $bi_url . ';
			var h = d.getElementsByTagName(t)[0]; h.parentNode.insertBefore(s, h);
			})(window, document, "script");
		   </script>
		';
	}

	// Set basic settings for tracking

	private function push_setup_vars() {
		return '
		_cnx.push(["mid", "' . esc_js( self::get( 'bizrate_insights_mid' ) ) . '"]); // your unique store MID
		_cnx.push(["surveyType", "pos"]);
		';
	}

	/**
	 * Add order data from $order for tracking
	 *
	 * @param  WC_Product $order  Order to pull info from
	 * @return string             Add order info tracking code
	 */

	private function push_order_data( $order ) {
		$code    = "_cnx.push(['orderId', '" . esc_js( $order->get_order_number() ) . "']);";
		$code   .= ( wp_get_original_referer() ) ? "_cnx.push(['referrerPage', '" . esc_js( wp_get_original_referer() ) . "']);" : '';
		$code   .= ( wp_get_original_referer() ) ? "_cnx.push(['referrerURL', '" . esc_js( wp_get_original_referer() ) . "']);" : '';
		$code   .= "_cnx.push(['deliveryDate', '" . $order->get_date_created() . "']);";
		$coupons = $order->get_coupon_codes();
		$code   .= "_cnx.push(['couponApplied', '" . ( ( count( $coupons ) > 0 ) ? 'true' : 'false' ) . "']);";
		return $code;
	}

	/**
	 * Add product data list from $order for tracking
	 *
	 * @param  WC_Product $order  Order to pull info from
	 * @return string             Add customer data tracking code
	 */

	private function push_customer_data( $order ) {
		$code  = "_cnx.push(['customerId', '" . esc_js( $order->get_customer_id() ) . "']);";
		$code .= "_cnx.push(['zip', '" . esc_js( $order->get_billing_postcode() ) . "']);";
		$code .= "_cnx.push(['emailAddress', '" . esc_js( $order->get_billing_email() ) . "']);";
		$code .= ( self::get( 'bizrate_insights_webanalyticsid_enable' ) === 'yes' ) ? "_cnx.push(['webAnalyticsId', '" . self::get( 'bizrate_insights_webanalyticsid' ) . "']);" : '';
		return $code;
	}

	/**
	 * Add product data list from $order for tracking
	 *
	 * @param  WC_Product $order  Order to pull item from
	 * @return string             Add product list tracking code
	 */

	private function push_products_data( $order ) {
		$code         = '';
		$product_list = array();
		$gtin_list    = array();
		if ( $order->get_items() ) {
			foreach ( $order->get_items() as $item ) {
				$product_list[]       = self::item_push( $order, $item );
				$products_purchased[] = $item['name'];
				$gtin                 = ( self::get( 'bizrate_insights_gtin_enable' ) === 'yes' ) ? self::item_gtin_push( $order, $item ) : null;
				if ( null !== $gtin ) {
					$gtin_list[] = $gtin;
				}
			}
		}
		if ( ! empty( $product_list ) ) {
			$code .= "_cnx.push(['cart', '" . wp_json_encode( $product_list ) . "']);";
		}
		if ( ! empty( $product_list ) ) {
			$code .= "_cnx.push(['cartTotal', '" . esc_js( $order->get_total() ) . "']);";
		}
		if ( ! empty( $gtin_list ) ) {
			$code .= "_cnx.push(['gtin', '" . esc_js( implode( ',', $gtin_list ) ) . "']);";
		}
		if ( ! empty( $products_purchased ) ) {
			$code .= "_cnx.push(['productsPurchased', '" . esc_js( implode( ',', $products_purchased ) ) . "']);";
		}
		return $code;
	}

	/**
	 * Returns a returns itme data from $order based on $item
	 *
	 * @param  WC_Product $order  Order to pull item from
	 * @param  WC_Product $item  Item to pull from $order
	 * @return array             Item data
	 */

	private function item_push( $order, $item ) {
		$_product    = version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_product_from_item( $item ) : $item->get_product();
		$image       = wp_get_attachment_image_src( get_post_thumbnail_id( $_product->get_id() ), 'full' );
		$sales_price = $_product->get_regular_price();
		if ( $_product->is_on_sale() ) {
			$sales_price = $_product->get_sale_price();
		}
		return array(
			'id'            => $_product->get_id(),
			'price'         => esc_js( $sales_price ),
			'originalPrice' => esc_js( $_product->get_regular_price() ),
			'quantity'      => esc_js( $item['qty'] ),
			'title'         => esc_js( $item['name'] ),
			'imageUrl'      => isset( $image[0] ) ? $image[0] : '',
		);
	}

	private function item_gtin_push( $order, $item ) {
		$_product = version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_product_from_item( $item ) : $item->get_product();
		$gtin     = null;
		if ( is_plugin_active( 'wpsso-wc-metadata/wpsso-wc-metadata.php' ) ) {
			$gtin = get_post_meta( $_product->get_id(), '_wpsso_product_gtin', 1 );
		} elseif ( is_plugin_active( 'woo-add-gtin/woocommerce-gtin.php' ) ) {
			$gtin = get_post_meta( $_product->get_id(), 'hwp_product_gtin', 1 );
		} elseif ( is_plugin_active( 'product-gtin-ean-upc-isbn-for-woocommerce/product-gtin-ean-upc-isbn-for-woocommerce.php' ) ) {
			$gtin = get_post_meta( $_product->get_id(), '_wpm_gtin_code', 1 );
		} elseif ( is_plugin_active( 'gtin-schema-for-woo/gtin-schema-for-woo.php' ) ) {
			$gtin = get_post_meta( $_product->get_id(), '_gtin_schema_code', 1 );
		}
		return ( ( '' !== $gtin ) ? $gtin : null );
	}

	private function push_custom_values() {
		$code  = '';
		$code .= ( self::get( 'bizrate_insights_custom_value1_enable' ) === 'yes' ) ? "_cnx.push(['" . self::get( 'bizrate_insights_custom_value1_key' ) . "', '" . self::get( 'bizrate_insights_custom_value1' ) . "']);" : '';
		$code .= ( self::get( 'bizrate_insights_custom_value2_enable' ) === 'yes' ) ? "_cnx.push(['" . self::get( 'bizrate_insights_custom_value2_key' ) . "', '" . self::get( 'bizrate_insights_custom_value2' ) . "']);" : '';
		return $code;
	}

	/**
	 * Returns the complete code for tracking
	 *
	 * @param  WC_Product $order  Order to pull info from
	 * @return string             complete tracking code
	 */

	public function tracking_code_display( $order ) {
		$code_print  = $this->script_open();
		$code_print .= $this->push_setup_vars();
		$code_print .= $this->push_order_data( $order );
		$code_print .= $this->push_products_data( $order );
		$code_print .= $this->push_customer_data( $order );
		$code_print .= $this->push_custom_values();
		$code_print .= $this->script_close();
		$arr         = array(
			'script' => array(
				'type'  => array(),
				'async' => array(),
			),
		);
		return wp_kses( $code_print, $arr );
	}
}
