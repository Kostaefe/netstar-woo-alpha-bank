<?php

namespace NetStar\WooAlphaBank;

/**
 * Plugin Name: NETSTAR-GR Ltd - WooCommerce Alpha Bank Payment Gateway
 * Description: Alpha Bank Payment gateway for WooCommerce, with installments and MasterPass payment!
 * Version: 5.0.0
 * Author: NETSTAR-GR Ltd
 * Domain Path: /languages/
 * Text Domain: alphabank
 * WC requires at least: 6.3.1
 * WC tested up to: 9.6.0
 */
if (!defined('ABSPATH')) {
	header("Status: 404 Not Found");
	exit();
}

if (is_readable(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}

\NetStar\WooAlphaBank\WooAlphaBank::initialize();
