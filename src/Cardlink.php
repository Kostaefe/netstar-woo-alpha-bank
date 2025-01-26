<?php

namespace NetStar\WooAlphaBank;

use WC_Order;
use WC_Payment_Gateway;

/**
 * The Abstract CardLink Class
 *
 * @author NETSTAR-GR Ltd.
 * @version 2.0.0
 */
abstract class CardLink extends WC_Payment_Gateway {

	/**
	 * Is Live Server
	 *
	 * @var boolean
	 */
	protected bool $live;

	protected string $merchantId;

	/**
	 * The shared secret key
	 *
	 * @var string
	 */
	protected string $salt;

	/**
	 * Enable/Disable the Preauthorization
	 *
	 * @var boolean
	 */
	protected bool $preAuth;

	/**
	 * The Installments maximum.
	 * 1 to max. Installments, 1 for one time payment.
	 *
	 * @var string
	 */
	protected string $installmentsFee;

	/**
	 * Maximum number of installments depending on the total order amount.
	 * Example 80:2|160:4|300:8
	 *
	 * @var string
	 */
	protected string $installmentsVariation;

	/**
	 * <li>One Month = 28</li>
	 * <li>Not over 365</li>
	 *
	 * @var integer
	 */
	protected int $recurringPayments;

	/**
	 * The End Date
	 * <li>Not over 1825 Days (5 Years) from now</li>
	 *
	 * @var string Format YYYYMMDD
	 */
	protected string $recurringEndDate;

	/**
	 * The Form Url Live or Test
	 *
	 * @var string
	 */
	protected string $url;

	/**
	 * The Error Page Id
	 *
	 * @var integer
	 */
	protected int $redirectNotOkPageId;

	/**
	 * The trType Payment
	 *
	 * @var integer
	 */
	const PAYMENT = 1;

	/**
	 * The trType Preauthorization
	 *
	 * @var integer
	 */
	const PREAUTHORIZATION = 2;

	/**
	 * The CardLink Class Construct
	 */
	public function __construct() {
		if (is_admin()) {
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			));
		}

		add_action('woocommerce_receipt_' . $this->id, array(
			$this,
			'getReceiptPage'
		));

		// Payment listener/API hook
		add_action('woocommerce_api_' . $this->id, array(
			$this,
			'checkApiResponse'
		));

		// Ad the print Button on the WooCommerce thank you page
		add_action('woocommerce_thankyou', array(
			$this,
			'getPrintButton'
		), 1);
	}

	/**
	 * Verify a successful Payment!
	 * Writes Order Note and set Payment
	 */
	abstract public function checkApiResponse(): void;

	/**
	 * Calculates the Digest from a string or an array
	 *
	 * @param string|array $stingOrArray
	 *
	 * @return string the calculated Digest
	 */
	abstract protected function calculateDigest(string|array $stingOrArray): string;

	/**
	 * Return the Bank HTML Form
	 *
	 * @param int $orderId
	 *
	 * @return string
	 */
	abstract protected function getBankForm(int $orderId): string;

	/**
	 * Add "Print Button" to Order Received page, Woocommerce
	 */
	final public function getPrintButton(): void {
		echo '<button onClick="window.print();" style="margin:0px;float:right;" class="button">' . __('Print', 'alphabank') . '</button>';
	}

	/**
	 * Filter all Quotes from Strings
	 *
	 * @param string $string
	 * @return string
	 */
	final protected function stringSanitize(string $string): string {
		return html_entity_decode($string, ENT_QUOTES);
	}

	/**
	 * Receipt Page
	 *
	 * @param int $orderId
	 */
	final public function getReceiptPage(int $orderId): void {
		echo '<p>' . __('Thank you for your order, please continue payment.', 'alphabank') . '</p>';
		echo $this->getBankForm($orderId);
		echo '<script>document.getElementById("cardlinkform").submit();</script>';
	}

	/**
	 * Return a Slectbox with the Installment Options
	 *
	 * @param integer $maxInstallments
	 * @return string
	 */
	final protected function getFrontendInstallments(): string {
		$orderTotal = $this->get_order_total();
		$maxInstallments = 1;
		$instText = __('Pay with installments', 'alphabank');

		// Installment Fee?
		if ($this->installmentsFee != '' && is_numeric($this->installmentsFee)) {
			$orderTotal = $orderTotal + ($orderTotal * ($this->installmentsFee / 100));
			$instText .= ' (+' . $this->installmentsFee . '% ' . __('fee', 'alphabank') . ')';
		}

		// Check First the Variable Option (80:2|150:3...)
		if (is_string($this->installmentsVariation) && $this->installmentsVariation != '') {
			// First Explode
			$arrayInstallVariations = explode('|', $this->installmentsVariation);
			foreach ($arrayInstallVariations as $installVaiationString) {
				// Second Explode
				$installment = explode(':', $installVaiationString);
				if (is_array($installment) && count($installment) != 2) {
					continue;
				}

				if (wc_get_price_decimal_separator() == '.') {
					// If there is a string with , make it .
					$installment [0] = str_replace(',', '.', $installment [0]);
				}

				if (!is_numeric($installment [0]) || !is_numeric($installment [1])) {
					continue;
				}

				if ($orderTotal >= ($installment [0])) {
					$maxInstallments = $installment [1];
				}
			}
		}

		$InstMonths = '<label>' . $instText . '<select name="SelInstallmentperiod" id="SelInstallmentperiod">';
		foreach (range(1, $maxInstallments) as $number) {
			if ($number == 1)
				$InstMonths .= '<option value="1">' . __("Continue without installments", 'alphabank') . '</option>';
			else if ($number > 1)
				$InstMonths .= '<option value="' . $number . '">' . $number . ' ' . __('Installments', 'alphabank') . ' (' . $number . 'x ' . round(($orderTotal / $number), 2) . ' &euro;) </option>';
		}
		$InstMonths .= '</select></label>';
		return $InstMonths;
	}

	/**
	 * Returns the WordPress Language Code
	 *
	 * @return string
	 */
	final protected function getLanguageCode(): string {
		return (get_bloginfo("language") == "el") ? "el" : substr(get_bloginfo('language'), 0, 2);
	}

	/**
	 * Returns the Transaction Type
	 *
	 * @return int <li>1 = Payment</li><li>2 = Preauthorization</li>
	 */
	final protected function getTransactionType(): int {
		return (isset($this->preAuth) && $this->preAuth != "no") ? self::PREAUTHORIZATION : self::PAYMENT;
	}

	/**
	 * Get all Wp pages
	 *
	 * @param boolean $title
	 * @param boolean $indent
	 * @return string[]
	 */
	final protected function getAllWpPages(bool $title = false, bool $indent = true): array {
		$allWpPages = get_pages('sort_column=menu_order');
		$pageList = array();

		if ($title != false) {
			$pageList [] = $title;
		}

		foreach ($allWpPages as $page) {
			$prefix = '';
			if ($indent === true) {
				$hasParent = $page->post_parent;
				while ($hasParent) {
					$prefix .= ' - ';
					$next_page = get_page($hasParent);
					$hasParent = $next_page->post_parent;
				}
			}
			$pageList [$page->ID] = $prefix . $page->post_title;
		}

		return $pageList;
	}

	/**
	 * Set the Order Status and/or Complete the Order
	 *
	 * @param int $orderID
	 *        	the WooCommerce Order-Id
	 * @param string $status
	 *        	<li>AUTHORIZED</li><li>CAPTURED</li><li>CANCELED</li><li>REFUSED</li><li>ERROR</li>
	 *        	
	 * @return boolean <li>true = Payment Complete/cancelled/failed</li><li>false = something went wrong</li>
	 */
	final protected function setOrderStatus(int $orderID, string $status): bool {
		$order = new WC_Order($orderID);
		if (!$order) {
			return false;
		}

		switch ($status) {
			case 'AUTHORIZED':
			case 'CAPTURED':
				return $order->payment_complete();

			case 'CANCELED':
				$newStatus = 'cancelled';
				break;

			default:
				$newStatus = 'failed';
				break;
		}

		return $order->update_status($newStatus);
	}

	/**
	 * Get order-received in SSL or not url
	 *
	 * @param integer $οrderID
	 *        	the WooCommerce Order-Id
	 *        	
	 * @return mixed The filtered value after all hooked functions are applied to it.
	 */
	final protected function getReturnUrl(int $orderID) {
		$order = new WC_Order($orderID);

		$returnUrl = ($order) ? $order->get_checkout_order_received_url() : wc_get_endpoint_url('order-received', '', wc_get_page_permalink('checkout'));

		if (is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes') {
			$returnUrl = str_replace('http:', 'https:', $returnUrl);
		}

		return apply_filters('woocommerce_get_return_url', $returnUrl, $order);
	}

}
