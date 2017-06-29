<?php

/**
 * Monetivo for Prestashop
 *
 * @author    monetivo <hello@monetivo.com>
 */

/**
 * Dispaly confiration page
 */
class MonetivoConfirmationModuleFrontController extends ModuleFrontController
{

    /**
     * Initialize page content and disable left and right columns
     */
    public function initContent()
    {
        // Hide left and right columns
        $this->display_column_left = false;
        $this->display_column_right = false;
        parent::initContent();

        $cart_id = (int)Tools::getValue('cart_id');
        $cart = $this->context->cart;
        if (!empty($cart_id))
            $cart = new Cart($cart_id);

        $customer = new Customer($cart->id_customer);
        $order = new Order(Order::getOrderByCartId($cart->id));

        if ($cart->OrderExists() == true) {
            $url = 'index.php?controller=order-detail&id_order=' . $order->id;
            if (Cart::isGuestCartByCartId($cart->id))
                $url = 'index.php?controller=guest-tracking&id_order=' . $order->reference . '&order_reference=' . $order->reference . '&email=' . urlencode($customer->email);

            Tools::redirect($url, __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');

        }
    }

}
