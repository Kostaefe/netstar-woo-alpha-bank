<?php

namespace NetStar\WooAlphaBank;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

/**
 * The Static WooAlphaBank Class
 *
 * @author NETSTAR-GR Ltd
 */
final class WooAlphaBank {

	private static string $pluginFile = '';

	public static function initialize(): void {
		self::$pluginFile = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'plugin.php';

		add_action('plugins_loaded', [
			__CLASS__,
			'loadTextdomain'
		]);

		add_action('before_woocommerce_init', [
			__CLASS__,
			'declareCompatibility'
		]);

		add_filter('woocommerce_payment_gateways', [
			__CLASS__,
			'addToWooCommercePaymentGateway'
		]);

		add_action('woocommerce_blocks_loaded', [
			__CLASS__,
			'registerBlocks'
		]);

		add_filter('plugin_row_meta', [
			__CLASS__,
			'setWooAlphaBankPluginMeta'
		], 10, 2);
	}

	public static function loadTextdomain() {
		load_plugin_textdomain('alphabank', false, dirname(plugin_basename(self::$pluginFile)) . '/languages/');
	}

	/**
	 * Compatibility Declaration for the custom order Tables
	 */
	public static function declareCompatibility() {
		if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
			FeaturesUtil::declare_compatibility('custom_order_tables', self::$pluginFile, false);
		}
	}

	public static function registerBlocks(): void {
		if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
			return;
		}

		add_action('woocommerce_blocks_payment_method_type_registration', [
			__CLASS__,
			'registerWooAlphaBankBlockType'
		]);
	}

	/**
	 * Register the WooAlphaBank Block Type
	 *
	 * @param \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $paymentMethodRegistry
	 */
	public static function registerWooAlphaBankBlockType(PaymentMethodRegistry $paymentMethodRegistry): void {
		$paymentMethodRegistry->register(new WooAlphaBankBlocks());
	}

	/**
	 * Add Alpha Bank to the WooCommerce Payment Methods.
	 *
	 * @param array $methods
	 *        	The Payment Gateway Methods.
	 *        	
	 * @return array The Payment Gateway Methods.
	 */
	public static function addToWooCommercePaymentGateway(array $methods): array {
		$methods [] = PaymentGateway::class;

		return $methods;
	}

	/**
	 * Plugin meta options.
	 * Adds a link to plugin meta row.
	 *
	 * @param array $links
	 *        	The Links to show.
	 * @param string $file
	 *        	the current plugin file.
	 *        	
	 * @return array $links
	 */
	public static function setWooAlphaBankPluginMeta(array $links, string $file): array {
		if ($file === plugin_basename(self::$pluginFile)) {
			$row_meta = array(
				'support' => '<a href="admin.php?page=wc-settings&tab=checkout&section=alphabank"><span class="dashicons dashicons-admin-generic"></span> ' . __('Options', 'netstar') . '</a>'
			);
			$links = array_merge($links, $row_meta);
		}

		return (array) $links;
	}

}
