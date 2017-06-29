<?php

/**
 * Monetivo for Prestashop
 *
 * @author    monetivo <hello@monetivo.com>
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Monetivo payment module
 */
class Monetivo extends PaymentModule
{

    /**
     * Disable config form
     */
    protected $config_form = false;

    /**
     * Set prestashop module pharams
     */
    public function __construct()
    {
        $this->name = 'monetivo';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'monetivo';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('monetivo for Prestashop');
        $this->description = $this->l('monetivo for Prestashop');

        $this->limited_countries = array('PL');

        $this->limited_currencies = array('PLN');

        $this->ps_versions_compliancy = array('min' => '1.6',
            'max' => _PS_VERSION_);

        /**
         * Include API
         *
         * @see https://docs.monetivo.com/#php
         */
        require_once('vendor/monetivo/monetivo-php/autoload.php');

        // admin notices
        $notset = false;
        foreach ($this->getConfigFormValues() as $v)
            if(empty($v)) $notset = true;
        if($notset)
            $this->warning = $this->l('Module needs to be configured before usage');
        elseif(substr(trim(Configuration::get('MONETIVO_APP_TOKEN')),0,4) == 'test')
            $this->warning = $this->l('Module is set in SANDBOX mode');
        else
            $this->warning = null;

        // all is set but
        if(!$notset && empty(Configuration::get('MONETIVO_PAYMENT_STATUS_NEW')))
        {
            // add status - disable module on fail
            if(!Configuration::updateValue(
                'MONETIVO_PAYMENT_STATUS_NEW', $this->_addNewOrderState(
                'MONETIVO_PAYMENT_STATUS_NEW', array('en' => 'Waiting for monetivo payment',
                'pl' => 'Oczekiwanie na płatność monetivo'))
            ))
                $this->disable();

        }

    }

    /**
     * Adding new order state
     *
     * @param $state
     * @param $names
     *
     * @return integer return Order State ID
     */
    private function _addNewOrderState($state, $names)
    {
        error_log('Do we have state '.$state.': '.Configuration::get($state));
        if (!(Validate::isInt(Configuration::get($state))
            && Validate::isLoadedObject(
                $order_state = new OrderState(Configuration::get($state))
            ))
        ) {
            $order_state = new OrderState();

            if (!empty($names)) {
                foreach ($names as $code => $name) {
                    $order_state->name[Language::getIdByIso($code)] = $name;
                }
            }

            $order_state->send_email = false;
            $order_state->invoice = false;
            $order_state->unremovable = true;
            $order_state->color = '#77b3e0';
            $order_state->module_name = 'monetivo';

            if (!$order_state->add() || !Configuration::updateValue(
                    $state, $order_state->id
                )
            ) {
                error_log('State '.$state.' not added');
                return false;
            }

            copy(
                _PS_MODULE_DIR_ . $this->name . '/img/mvo_status.png', _PS_IMG_DIR_ . 'os/' . $order_state->id . '.gif'
            );

            error_log('State id '.$order_state->id);

            return $order_state->id;
        }

        return false;
    }

    /**
     * Instalation of module Monetivo for prestashop
     *
     * @return registerHook|false The Prestashop hooks or false if curl extension not enabled or country limit is set.
     */
    public function install()
    {
        /**
         * cURL extension is require for the API
         */
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l(
                'You have to enable the cURL extension on your server to install this module'
            );

            return false;
        }

        $iso_code = Country::getIsoById(
            Configuration::get('PS_COUNTRY_DEFAULT')
        );

        if (in_array($iso_code, $this->limited_countries) == false) {
            $this->_errors[] = $this->l(
                'This module is not available in your country'
            );

            return false;
        }

        return parent::install()
            && $this->createHooks()
            && Configuration::updateValue(
                'MONETIVO_PAYMENT_STATUS_NEW', $this->_addNewOrderState(
                'MONETIVO_PAYMENT_STATUS_NEW', array('en' => 'Waiting for monetivo payment',
                    'pl' => 'Oczekiwanie na płatność monetivo'))
            );
    }

    private function createHooks()
    {
        $registerStatus = $this->registerHook('header') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('backOfficeHeader');

        if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
            $registerStatus &= $this->registerHook('payment');
        } else {
            $registerStatus &= $this->registerHook('paymentOptions');
        }

        return $registerStatus;
    }

    /**
     * Uninstall of module Monetivo for prestashop
     *
     * @return bool
     */
    public function uninstall()
    {
        Configuration::deleteByName('MONETIVO_APP_TOKEN');
        Configuration::deleteByName('MONETIVO_LOGIN');
        Configuration::deleteByName('MONETIVO_PASSWORD');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     *
     * @return string renderForm()
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        $output = '';

        if (((bool)Tools::isSubmit('submitMonetivoModule')) == true) {
            $output .= $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output .= $this->context->smarty->fetch(
            $this->local_path . 'views/templates/admin/configure.tpl'
        );

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     *
     * @return rendered form
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get(
            'PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0
        );

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMonetivoModule';
        $helper->currentIndex = $this->context->link->getAdminLink(
                'AdminModules', false
            )
            . '&configure=' . $this->name . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     *
     * @return array form structure
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Enter a valid Monetivo login'),
                        'name' => 'MONETIVO_LOGIN',
                        'label' => $this->l('Monetivo login'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'password',
                        'desc' => $this->l('Enter a valid Monetivo password'),
                        'name' => 'MONETIVO_PASSWORD',
                        'label' => $this->l('Monetivo Password'),
                    ),
                    array(
                        'col' => 5,
                        'type' => 'text',
                        'desc' => $this->l('Enter a valid Monetivo token'),
                        'name' => 'MONETIVO_APP_TOKEN',
                        'label' => $this->l('Monetivo token'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     *
     * @return array of configuration values
     */
    protected function getConfigFormValues()
    {
        return array(
            'MONETIVO_LOGIN' => Configuration::get(
                'MONETIVO_LOGIN'
            ),
            'MONETIVO_PASSWORD' => Configuration::get(
                'MONETIVO_PASSWORD'
            ),
            'MONETIVO_APP_TOKEN' => Configuration::get(
                'MONETIVO_APP_TOKEN'
            ),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        // check data validity
        try {
            $app_token = Tools::getValue('MONETIVO_APP_TOKEN');
            $login = Tools::getValue('MONETIVO_LOGIN');
            $password = Tools::getValue('MONETIVO_PASSWORD');
            $api = new \Monetivo\MerchantApi($app_token);
            $api->auth($login, $password);
            $api->call('get', 'auth/check_token');

        } catch (\Monetivo\Exceptions\MonetivoException $e) {
            $error = $this->l('Cannot save configuration') . ': ';
            if ($e->getHttpCode() == 401)
                $error .= $this->l('Invalid monetivo credentials');
            else
                $error .= $this->l('API error') . ' (' . $e->getMessage() . ')';

            return $this->displayError($error);
        }

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, trim(Tools::getValue($key)));
        }

        return $this->displayConfirmation($this->l('Settings updated'));
    }

    /**
     * The CSS files added on the BO.
     *
     * @return void
     */
    public function hookBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');
    }

    /**
     * The CSS files added on the FO.
     *
     * @return void
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS(
            $this->_path . '/views/css/front.css'
        );
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     *
     * @param $params ['cart']
     *
     * @return string|false Check if currency is allowed
     */
    public function hookPayment($params)
    {
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);

        if (in_array($currency->iso_code, $this->limited_currencies) == false) {
            return false;
        }

        $this->smarty->assign('module_dir', $this->_path);

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * Only for >=1.7
     * @param $params
     * @return array|void
     */
    public function hookPaymentOptions($params)
    {
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);

        if (in_array($currency->iso_code, $this->limited_currencies) == false)
            return;

        $paymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $paymentOption->setCallToActionText($this->l('Pay with monetivo'))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/img/mvo_20.png'))
            ->setModuleName($this->name)
            ->setAction($this->context->link->getModuleLink($this->name, 'redirect'));

        return array($paymentOption);
    }

}
