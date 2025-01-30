<?php
namespace NetStar\WooAlphaBank;

/**
 * The WooCommerce Alpha Bank Payment Gateway
 *
 * @author NETSTAR-GR Ltd
 */
final class PaymentGateway extends CardLink
{

    /**
     * The WooCommerce Alpha Bank Payment Gateway
     */
    public function __construct()
    {
        $this->id = "alphabank";
        $this->icon = plugins_url('images/cards.gif', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'plugin.php');
        $this->has_fields = true;
        $this->method_title = "Alpha Bank Payment Gateway";
        $this->method_description = __("Alpha Bank Payment Gateway for WooCommerce. Version ", "alphabank");
        $this->recurringPayments = 28;

        $this->init_form_fields();
        $this->init_settings();

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        $this->url = ($this->live === "no") ? 'https://alphaecommerce-test.cardlink.gr/vpos/shophandlermpi ' : 'https://www.alphaecommerce.gr/vpos/shophandlermpi';

        parent::__construct();
    }

    /**
     *
     * {@inheritdoc}
     * @see \WC_Settings_API::init_form_fields()
     */
    public function init_form_fields(): void
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Alpha Bank Payment Gateway.', 'alphabank'),
                'default' => 'yes'
            ),
            'live' => array(
                'title' => __('Live Server/Test Server', 'alphabank'),
                'type' => 'checkbox',
                'label' => __('Check for live account.', 'alphabank'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default' => __('Alpha Bank', 'alphabank'),
                'desc_tip' => false
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
                'default' => __('Pay securely with credit, debit and prepaid cards of Visa, MasterCard, Maestro, American Express, Diners and Discover through the electronic payment platform "Alpha e-Commerce" of Alpha Bank.', 'alphabank'),
                'desc_tip' => false
            ),
            'merchant_id' => array(
                'title' => __('Merchant ID', 'alphabank'),
                'type' => 'text',
                'description' => __('This id (Merchant ID) is provided by the Alpha Bank.', 'alphabank'),
                'desc_tip' => false,
                'class' => 'required'
            ),
            'salt' => array(
                'title' => __('Shared secret key', 'alphabank'),
                'type' => 'password',
                'description' => __('Given to Merchant by Alpha Bank.', 'alphabank'),
                'desc_tip' => false,
                'class' => 'required'
            ),
            'redirect_nok_page_id' => array(
                'title' => __('Returning Page on Error', 'alphabank'),
                'type' => 'select',
                'options' => $this->getAllWpPages(__('Select Page', 'alphabank')),
                'description' => __("Select the failure page.", 'alphabank') . '<span style="color:red;">*</span>',
                'class' => 'required'
            ),
            'masterpass' => array(
                'title' => __('MasterPass', 'alphabank'),
                'type' => 'checkbox',
                'label' => __('Enable Payment via MasterPass.', 'alphabank'),
                'default' => 'no',
                'desc_tip' => false
            ),
            'preauth' => array(
                'title' => __('Preauthorization', 'alphabank'),
                'type' => 'checkbox',
                'label' => __('Enable Alpha Bank Payment by Preauthorization. Works only with the approval of the Bank.', 'alphabank'),
                'default' => 'no',
                'desc_tip' => false
            ),
            'recurringEndDate' => array(
                'title' => __('Recurring max. choice', 'alphabank'),
                'type' => 'select',
                'options' => $this->getRecuringOptions(),
                'description' => __('Recruing payment once a month.<br>') . __('Enable Alpha Bank Recurring Payment. Works only with the approval of the Bank.', 'alphabank') . __('<br>Either installment or recruing payment. Not both together.'),
                'default' => 'no'
            ),
            'installmentsVariation' => array(
                'title' => __('Custom Installments', 'alphabank'),
                'type' => 'text',
                'placeholder' => '80:2|160:4|300.5:8',
                'description' => '<b>' . __('Example', 'alphabank') . ':</b> <small>(' . __('Works only with the approval of the Bank.', 'alphabank') . ')</small><br>' . __('Total order', 'alphabank') . ' >= 80, ' . __('allow', 'alphabank') . ' 2 ' . __('installments', 'alphabank') . '.<br>' . __('Total order', 'alphabank') . ' >= 160, ' . __('allow', 'alphabank') . ' 4 ' . __('installments', 'alphabank') . '.</br>' . __('Total order', 'alphabank') . ' >= 300.5, ' . __('allow', 'alphabank') . ' 8 ' . __('installments', 'alphabank') . '.</br>' . __('and so on... else, leave the field blank.', 'alphabank')
            ),
            'installmentsFee' => array(
                'title' => __('Installment Fee', 'alphabank'),
                'type' => 'text',
                'placeholder' => __('in %', 'alphabank'),
                'description' => __('Enter a installment fee (%) calculated from the cart total.', 'alphabank') . '<br>' . __('The Bank does not support installment fee\'s.<br>This installment fee is "virtual", you can charge your client whith an additional fee for using installments.', 'alphabank')
            )
        );
    }

    /**
     *
     * {@inheritdoc}
     * @see \WC_Payment_Gateway::admin_options()
     */
    public function admin_options()
    {
        echo '<h3 id="PluginTitle">' . $this->method_title . '</h3>';
        echo '<p>' . $this->method_description . '</p>';
        echo '<img src="' . $this->icon . '" alt="Cards" class="bankimg"><br style="clear:both">';

        echo '<table class="form-table HauptDiv">';
        $this->generate_settings_html();
        echo '</table>';
    }

    /**
     *
     * {@inheritdoc}
     * @see \WC_Payment_Gateway::payment_fields()
     */
    public function payment_fields()
    {
        echo '<!-- ' . $this->method_description . ' - By NETSTAR-GR Ltd -->';
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }

        if ($this->masterpass === "yes") {
            echo $this->getMasterPass();
        }

        if ($this->installmentsVariation != '') {
            echo $this->getFrontendInstallments();
        }

        if (! empty($this->recurringEndDate)) {
            echo $this->getRecurringSelectBox();
        }
        echo '<!-- ' . $this->method_description . ' - By NETSTAR-GR Ltd -->';
    }

    /**
     *
     * {@inheritdoc}
     * @see \WC_Payment_Gateway::process_payment()
     */
    public function process_payment($orderId)
    {
        $order = wc_get_order($orderId);
        $expire = time() + (60 * 15);

        // Erstmal alles leeren um dann Neue Endscheidung zu speichern
        setcookie('my_masterpass_choice', '', 1);
        setcookie('my_installment_choice', '', 1);
        if (isset($_SESSION['my_masterpass_choice'])) {
            unset($_SESSION['my_masterpass_choice']);
        }
        if (isset($_SESSION['my_installment_choice'])) {
            unset($_SESSION['my_installment_choice']);
        }
        if (isset($_SESSION['my_recurring_choice'])) {
            unset($_SESSION['my_recurring_choice']);
        }

        // Save choise in cookies oder Session
        if (array_key_exists('SelRecurringPeriod', $_POST)) {
            setcookie('my_recurring_choice', $_POST['SelRecurringPeriod'], $expire);
            if (! isset($_COOKIE['my_recurring_choice'])) {
                $_SESSION['my_recurring_choice'] = $_POST['SelRecurringPeriod'];
            }
        }

        // Save choise in cookies oder Session
        if (array_key_exists('SelInstallmentperiod', $_POST)) {
            setcookie('my_installment_choice', $_POST['SelInstallmentperiod'], $expire);
            if (! isset($_COOKIE['my_installment_choice'])) {
                $_SESSION['my_installment_choice'] = $_POST['SelInstallmentperiod'];
            }
        }

        // Save choise in cookies oder Session
        if (array_key_exists('MasterPass', $_POST)) {
            setcookie('my_masterpass_choice', $_POST['MasterPass'], $expire);
            if (! isset($_COOKIE['my_masterpass_choice'])) {
                $_SESSION['my_masterpass_choice'] = $_POST['MasterPass'];
            }
        }

        // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    /**
     *
     * {@inheritdoc}
     * @see CardLink::checkApiResponse()
     */
    public function checkApiResponse(): void
    {
        // Get the Post Digest from Response
        $digest = isset($_POST['digest']) ? $_POST['digest'] : '';

        // If CANCELED or REFUSED or ERROR
        if ($digest != "" && ($_POST['status'] === 'CANCELED' || $_POST['status'] === 'REFUSED' || $_POST['status'] === 'ERROR')) {
            // Get the Order Id
            $orderId = stripslashes(trim(substr($_POST["orderid"], 0, - 10)));
            // Prepare the order note
            $note = $this->method_title . '<br>' . __('Order Status:', 'alphabank') . ' ' . $_POST['status'] . '<br>';
            // Add Order note in Backoffice
            $order = wc_get_order($orderId);
            $note .= ((isset($_POST['message'])) ? 'Message: ' . $_POST['message'] : '');
            $order->add_order_note($note);

            // WPML Check Error Page Language
            $link = (function_exists('icl_object_id') && defined('ICL_LANGUAGE_CODE')) ? icl_object_id($this->redirectNotOkPageId, 'page', true, ICL_LANGUAGE_CODE) : $this->redirectNotOkPageId;

            // Set Order
            $this->setOrderStatus($orderId, $_POST['status']);
            wp_redirect(get_permalink($link));
            exit();
        }

        if ($digest != "" && ($_POST['status'] === 'AUTHORIZED' || $_POST['status'] === 'CAPTURED')) {
            $orderId = stripslashes(trim(substr($_POST["orderid"], 0, - 10)));
            $order = wc_get_order($orderId);
            // Prepare the order note
            $note = $this->method_title . '<br>' . __('Order Status:', 'alphabank') . ' ' . $_POST['status'] . '<br>';

            $post_data_array = array();
            if (isset($_POST['mid'])) {
                $post_data_array[] = $_POST['mid'];
            }
            if (isset($_POST['orderid'])) {
                $post_data_array[] = $_POST['orderid'];
            }
            if (isset($_POST['status'])) {
                $post_data_array[] = $_POST['status'];
            }
            if (isset($_POST['orderAmount'])) {
                $post_data_array[] = $_POST['orderAmount'];
            }
            if (isset($_POST['currency'])) {
                $post_data_array[] = $_POST['currency'];
            }
            if (isset($_POST['paymentTotal'])) {
                $post_data_array[] = $_POST['paymentTotal'];
            }
            if (isset($_POST['message'])) {
                $post_data_array[] = $_POST['message'];
            }
            if (isset($_POST['riskScore'])) {
                $post_data_array[] = $_POST['riskScore'];
            }
            if (isset($_POST['payMethod'])) {
                $post_data_array[] = $_POST['payMethod'];
            }
            if (isset($_POST['txId'])) {
                $post_data_array[] = $_POST['txId'];
                $note .= 'Transaction-Id: ' . $_POST['txId'] . '<br>';
            }
            if (isset($_POST['paymentRef'])) {
                $post_data_array[] = $_POST['paymentRef'];
            }
            // Put the shared Secret Key in the array to
            $post_data_array[] = $this->salt;
            // create the digest
            $calculatedDigest = $this->calculateDigest($post_data_array);

            // WPML Check Error Page Language
            $link = (function_exists('icl_object_id') && defined('ICL_LANGUAGE_CODE')) ? icl_object_id($this->redirectNotOkPageId, 'page', true, ICL_LANGUAGE_CODE) : $this->redirectNotOkPageId;

            // Check Digest
            if ($digest != $calculatedDigest) {
                $note .= __('Security Breach: the digest is not equel.', 'alphabank') . ' ' . __('Check payment in the Alpha Bank back office System.', 'alphabank');
                $order->add_order_note($note);
                wp_redirect(get_permalink($link));
                exit();
            }

            if ($this->setOrderStatus($orderId, $_POST['status']) != true) {
                $note .= __('But something went wrong on changing the order status.', 'alphabank') . ' ' . __('Check payment in the Alpha Bank back office System.', 'alphabank');
                $order->add_order_note($note);
                wp_redirect(get_permalink($link));
                exit();
            }

            // Everything is fine note & redirect to the thank you page
            $note .= __('Payment completed.', 'alphabank');
            $order->add_order_note($note);
            wp_redirect($this->getReturnUrl($orderId));
            exit();
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \WC_Payment_Gateway::process_payment()
     */
    protected function calculateDigest(string|array $stingOrArray): string
    {
        if (is_array($stingOrArray)) {
            $postDataString = implode("", $stingOrArray);
        } elseif (is_string($stingOrArray)) {
            $postDataString = $stingOrArray;
        }

        return base64_encode(sha1($postDataString, true));
    }

    /**
     *
     * {@inheritdoc}
     * @see \WC_Payment_Gateway::process_payment()
     */
    protected function getBankForm(int $orderId): string
    {
        $order = wc_get_order($orderId);
        $txnid = $orderId . time();
        $productinfo = "Order:$orderId";
        $MasterPass = '';
        $Inst = 1;

        $EuroAmount = $order->get_total();

        if (isset($_COOKIE['my_masterpass_choice'])) {
            $MasterPass = $_COOKIE['my_masterpass_choice'];
        } elseif (isset($_SESSION['my_masterpass_choice'])) {
            $MasterPass = $_SESSION['my_masterpass_choice'];
        }

        if (isset($_COOKIE['my_installment_choice'])) {
            $Inst = $_COOKIE['my_installment_choice'];
        } elseif (isset($_SESSION['my_installment_choice'])) {
            $Inst = $_SESSION['my_installment_choice'];
        }

        if (isset($_COOKIE['my_recurring_choice'])) {
            $recurring = $_COOKIE['my_recurring_choice'];
        } elseif (isset($_SESSION['my_recurring_choice'])) {
            $recurring = $_SESSION['my_recurring_choice'];
        }

        // Installment Fee?
        if ($Inst >= 2 && $this->installmentsFee != '' && is_numeric($this->installmentsFee)) {
            $EuroAmount = $EuroAmount + ($EuroAmount * ($this->installmentsFee / 100));
        }

        // Country
        $country = $order->get_billing_country();

        // Standarts in Array packen
        $form_args1 = array(
            'mid' => $this->merchant_id,
            'lang' => $this->getLanguageCode(),
            'orderid' => $txnid,
            'orderDesc' => $productinfo,
            'orderAmount' => round($EuroAmount, 2),
            'currency' => 'EUR',
            'payerEmail' => $order->get_billing_email(),
            'payerPhone' => $order->get_billing_phone(),
            'billCountry' => $country
        );

        // Only not GR
        if ($country !== 'GR') {
            $form_args1['billState'] = $order->get_billing_state();
        }

        $form_args0 = array(
            'billZip' => $order->get_billing_postcode(),
            'billCity' => $this->stringSanitize($order->get_billing_city()),
            'billAddress' => $this->stringSanitize($order->get_billing_address_1())
        );

        // Standart Mergen
        $form_args1 = array_merge($form_args1, $form_args0);

        // Nur wenn MasterPass
        if ($MasterPass === "yes") {
            $form_args1['payMethod'] = 'auto:MasterPass';
        }

        // Standarts noch zum schluss packen
        $form_args1['trType'] = $this->getTransactionType();

        if ($Inst >= 2) {
            $form_args2 = array(
                'extInstallmentoffset' => 0,
                'extInstallmentperiod' => $Inst
            );
        }

        if (! empty($this->recurringEndDate) && ! empty($recurring) && $this->recurringEndDate >= $recurring) {
            $form_args2 = array(
                'extRecurringfrequency' => $this->recurringPayments,
                'extRecurringenddate' => $this->getRecurringEndDate($recurring)
            );
        }

        $form_args3 = array(
            'confirmUrl' => rawurldecode(wc_get_endpoint_url('wc-api', $this->id . '/', home_url())),
            'cancelUrl' => rawurldecode(wc_get_endpoint_url('wc-api', $this->id . '/', home_url())),
            'var2' => $this->stringSanitize($order->get_billing_first_name()),
            'var3' => $this->stringSanitize($order->get_billing_last_name())
        );

        $form_args = ! empty($form_args2) ? array_merge($form_args1, $form_args2, $form_args3) : array_merge($form_args1, $form_args3);

        // Digest
        $getDigest = $form_args;
        array_push($getDigest, $this->salt);
        $digest = $this->calculateDigest($getDigest);
        // Digest

        $form_args_array = array();
        foreach ($form_args as $key => $value) {
            $form_args_array[] = "<input type='hidden' id='$key' name='$key' value='$value'/>";
        }
        $form_args_array[] = '<input type="hidden" name="digest" value="' . $digest . '"/>';

        return '<form action="' . $this->url . '" method="POST" name="cardlink" id="cardlinkform" accept-charset="UTF-8" form.enctype="application/x-www-form-urlencoded">
						' . implode('', $form_args_array) . '
					</form>
					<div style="clear:both;"></div>
					<div id="paymentButton" style="display:none;">
						<a class="button cancel" id="subPay" href="javascript:void(0);" onClick="document.cardlink.submit()">' . __('Checkout', 'cardlink') . '</a>
						<a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancel Order &amp; Clear Cart', 'cardlink') . '</a>
					</div>';
    }

    /**
     * Return a Array with the Month
     *
     * @return string[]
     */
    private function getRecuringOptions()
    {
        $getrecuringOptions = [];
        $getrecuringOptions[''] = is_checkout() ? __('One time', 'alphabank') : __('Select the recurrence duration', 'alphabank');
        for ($i = 1; $i <= (12 * 5); $i ++) {
            $getrecuringOptions[$i] = $i . ' ' . ($i === 1 ? __('Month') : __('Months', 'alphabank'));
        }

        return $getrecuringOptions;
    }

    /**
     * Returns the Recurring End Date Format YYYYMMDD
     *
     * @return string
     */
    private function getRecurringEndDate($recurringEndDate)
    {
        if (empty($recurringEndDate)) {
            return '';
        }

        $interval = 'P' . $recurringEndDate . 'M';
        $dateTime = new \DateTime('now');

        return $dateTime->add(new \DateInterval($interval))->format('Ymd');
    }

    /**
     * Give the Master Pass Checkbox
     *
     * @return string html checkbox
     */
    private function getMasterPass()
    {
        return '<label><input type="checkbox" name="MasterPass" value="yes"> ' . __('Or pay with MasterPass.', 'alphabank') . ' <img src="' . plugins_url('images/masterpass.gif', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'plugin.php') . '" alt="MasterPass"  width="30" height="19"></label><br><br>';
    }

    /**
     * Shows the recurring select box.
     */
    private function getRecurringSelectBox(): void
    {
        $recuringText = __('Recurring Payment', 'alphabank');
        echo '<b>' . $recuringText . '</b>';
        $field = '<select name="SelRecurringPeriod" id="SelRecurringPeriod">';
        foreach ($this->getRecuringOptions() as $key => $value) {
            if ($key > $this->recurringEndDate) {
                break;
            }

            $field .= '<option value="' . $key . '">' . $value . '</option>';
        }

        echo $field . '</select>';
    }
}
