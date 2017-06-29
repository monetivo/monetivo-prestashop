<?php

/**
 * Monetivo for Prestashop
 *
 *  @author    monetivo <hello@monetivo.com>
 */
use \Monetivo\Api\Transactions as MonetivoTransactions;

/**
 * Validation process
 */
class MonetivoValidationModuleFrontController extends ModuleFrontController {

    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     *
     * @return void
     */
    public function postProcess() {
        /**
         * If the module is not active anymore, no need to process anything.
         */
        if ($this->module->active == false) {
            die;
        }

        // app token
        $app_token = Configuration::get('MONETIVO_APP_TOKEN');

        // merchant's login
        $login = Configuration::get('MONETIVO_LOGIN');

        // merchant's password
        $password = Configuration::get('MONETIVO_PASSWORD');

        // init the library
        $api = new \Monetivo\MerchantApi($app_token);

        // try to authenticate
        $api->auth($login, $password);

        $transaction = $api->handleCallback();

        if ($transaction !== false) {

            $order = new Order(
                    $transaction['order_data']['order_id']
            );

            // Get retrieved status from Monetivo
            $retrievedPaymentStatus = $transaction['status'];

            // Get unique id from Monetivo for future actions
            $identifier = $transaction['identifier'];

            $orderHistory = new OrderHistory();
            $orderHistory->id_order = $order->id;
            if ($order->current_state != Configuration::get('PS_OS_PAYMENT')) {
                switch ($retrievedPaymentStatus) {
                    case MonetivoTransactions::TRAN_STATUS_NEW:
                        break;

                    case MonetivoTransactions::TRAN_STATUS_DECLINED:

                        $orderHistory->changeIdOrderState(
                                Configuration::get('PS_OS_ERROR'), $order->id
                        );
                        $orderHistory->addWithemail(true);
                        break;
                    case MonetivoTransactions::TRAN_STATUS_ACCEPTED:

                        $orderHistory->changeIdOrderState(
                                Configuration::get('PS_OS_PAYMENT'), $order->id
                        );
                        $orderHistory->addWithemail(true);

                        // set transaction id
                        $payments = $order->getOrderPaymentCollection();
                        $payments[0]->transaction_id = $identifier;
                        $payments[0]->update();

                        break;
                    default: {
                            Logger::addLog(
                                    'Invalid payment status retrieved from monetivo'
                            );
                        }
                }
            }
        }
        header("HTTP/1.1 200 OK");
        exit;
    }

}
