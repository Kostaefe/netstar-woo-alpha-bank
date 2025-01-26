<?php

namespace NetStar\WooAlphaBank;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WooAlphaBankBlocks extends AbstractPaymentMethodType {

	/**
	 * The Alpha Payment Gateway
	 *
	 * @var \NetStar\WooAlphaBank\PaymentGateway
	 */
	private $gateway;

	/**
	 * The Name
	 *
	 * @var string
	 */
	protected $name = 'alpha_bank_type';

	/**
	 *
	 * {@inheritdoc}
	 * @see \Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface::initialize()
	 */
	public function initialize() {
		$this->settings = get_option('woocommerce_alpha_bank_type_settings', []);
		$this->gateway = new PaymentGateway();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::is_active()
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::get_payment_method_script_handles()
	 */
	public function get_payment_method_script_handles() {
		wp_register_script('wc-alpha-block-init', plugin_dir_url(__FILE__) . '../js/checkout.js', [
			'wc-blocks-registry',
			'wc-settings',
			'wp-element',
			'wp-html-entities',
			'wp-i18n'
		], null, true);

		if (function_exists('wp_set_script_translations')) {
			wp_set_script_translations('wc-alpha-block-init', 'alphabank', plugin_dir_url(__FILE__) . '../languages');
		}

		return [
			'wc-alpha-block-init'
		];
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::get_payment_method_data()
	 */
	public function get_payment_method_data() {
		return [
			'title' => $this->gateway->get_title(),
			'description' => $this->gateway->get_description(),
			'supports' => array_filter($this->gateway->supports, [
				$this->gateway,
				'supports'
			])
		];
	}

}

