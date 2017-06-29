<?php

/**
 * Monetivo for Prestashop
 *
 * @author    monetivo <hello@monetivo.com>
 */

/**
 * Redirection to monetivo payment process
 */
class MonetivoRedirectModuleFrontController extends ModuleFrontController
{

    /**
     * Connecting with monetivo
     */
    public function postProcess()
    {
        $monetivo = new Monetivo();

        try {
            $cart = Context::getContext()->cart;
            $currency = Context::getContext()->currency->iso_code;
            $lang = Language::getIsoById($cart->id_lang);
            $email = $this->context->customer->email;
            $name = $this->context->customer->firstname . ' '
                . $this->context->customer->lastname;
            $order_total = $cart->getOrderTotal();
            $secure_key = $this->context->customer->secure_key;
            $return_url = $this->context->link->getModuleLink(
                'monetivo', 'confirmation', array('cart_id' => $cart->id)
            );
            $notify_url = $this->context->link->getModuleLink(
                'monetivo', 'validation'
            );

            // app token
            $app_token = Configuration::get('MONETIVO_APP_TOKEN');

            // merchant's login
            $login = Configuration::get('MONETIVO_LOGIN');

            // merchant's password
            $password = Configuration::get('MONETIVO_PASSWORD');

            // init the library
            $api = new \Monetivo\MerchantApi($app_token);

            $api->setPlatform(sprintf('monetivo-prestashop-%s-%s', _PS_VERSION_, $monetivo->version));

            // try to authenticate
            $api->auth($login, $password);

            $payment_status = Configuration::get(
                'MONETIVO_PAYMENT_STATUS_NEW'
            );

            // You can add a comment directly into the order so the merchant will see it in the BO.
            $message = null;

            $this->module->validateOrder(
                $cart->id, $payment_status, $cart->getOrderTotal(), $this->module->displayName, $message, array(), null, false, $secure_key
            );
            $order_id = Order::getOrderByCartId($cart->id);

            $order_reference = Order::getUniqReferenceOf($order_id);

            $params = array(
                'order_data' => [
                    'description' => 'Zamówienie #' . $order_reference,
                    'order_id' => $order_id
                ],
                'buyer' => [
                    'name' => $name,
                    'email' => $email
                ],
                'language' => $lang,
                'currency' => $currency,
                'amount' => $order_total,
                'return_url' => $return_url,
                'notify_url' => $notify_url
            );

            $transaction = $api->transactions()->create($params);

            Tools::redirect($transaction['redirect_url']);
        } catch (\Monetivo\Exceptions\MonetivoException $e) {

            Logger::addLog(
                'HttpCode: ' . $e->getHttpCode() . 'Response: '
                . $e->getResponse(), 3
            );
        }

        /**
         * Oops, an error occured.
         */
        if (Tools::getValue('action') == 'error') {
            return $this->displayError(
                'An error occurred while trying to redirect the customer'
            );
        } else {
            $this->context->smarty->assign(
                array(
                    'cart_id' => Context::getContext()->cart->id,
                    'secure_key' => Context::getContext()->customer->secure_key,
                )
            );

            $template = 'redirect.tpl';
            if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
                $template = 'module:monetivo/views/templates/front/' .$template;
            }

            return $this->setTemplate($template);
        }
    }

    /**
     * Display errors
     *
     * @param $message
     * @param $description
     */
    protected function displayError($message, $description = false)
    {
        /**
         * Create the breadcrumb for your ModuleFrontController.
         */
        $this->context->smarty->assign(
            'path', '
			<a href="' . $this->context->link->getPageLink(
                'order', null, null, 'step=3'
            ) . '">' . $this->module->l('Payment') . '</a>
			<span class="navigation-pipe">&gt;</span>' . $this->module->l(
                'Error'
            )
        );

        /**
         * Set error message and description for the template.
         */
        array_push($this->errors, $this->module->l($message), $description);

        $template = 'error.tpl';
        if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
            $template = 'module:monetivo/views/templates/front/' .$template;
        }

        return $this->setTemplate($template);
    }

}
