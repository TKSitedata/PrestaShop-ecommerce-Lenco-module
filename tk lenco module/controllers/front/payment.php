<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Lenco Technologies Inc. <support@lenco.co>
 * @copyright Since 2024 Lenco Technologies Inc.
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

/**
 * This controller redirects customer to Lenco payment page
 */
class LencoPaymentModuleFrontController extends ModuleFrontController
{
    /**
     * @var Lenco
     */
    public $module;

    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        parent::initContent();

        if (false === $this->checkIfContextIsValid() || false === $this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        $customer = new Customer($this->context->cart->id_customer);

        if (false === Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        // Generate reference
        $reference = $this->module->generateReference();

        // Get total amount
        $total = (float) $this->context->cart->getOrderTotal(true, Cart::BOTH);

        // Get currency
        $currency = Configuration::get(Lenco::CONFIG_CURRENCY);

        // Create pending order
        $this->module->validateOrder(
            (int) $this->context->cart->id,
            (int) Configuration::get(Lenco::CONFIG_OS_PENDING),
            $total,
            $this->module->displayName,
            null,
            [],
            (int) $this->context->currency->id,
            false,
            $customer->secure_key
        );

        // Save transaction
        $this->module->saveTransaction(
            (int) $this->module->currentOrder,
            $reference,
            $total,
            $currency
        );

        // Get configuration
        $publicKey = Configuration::get(Lenco::CONFIG_PUBLIC_KEY);
        $channels = json_decode(Configuration::get(Lenco::CONFIG_CHANNELS), true) ?: [];
        $jsUrl = $this->module->getJsUrl();

        // Validation URL
        $validationUrl = $this->context->link->getModuleLink(
            $this->module->name,
            'validation',
            ['reference' => $reference],
            true
        );

        $this->context->smarty->assign([
            'public_key' => $publicKey,
            'reference' => $reference,
            'email' => $customer->email,
            'amount' => $total,
            'currency' => $currency,
            'channels' => $channels,
            'validation_url' => $validationUrl,
            'js_url' => $jsUrl,
            'order_id' => $this->module->currentOrder,
        ]);

        $this->setTemplate('module:lenco/views/templates/front/payment.tpl');
    }

    /**
     * Check if the context is valid
     *
     * @return bool
     */
    private function checkIfContextIsValid()
    {
        return true === Validate::isLoadedObject($this->context->cart)
            && true === Validate::isUnsignedInt($this->context->cart->id_customer)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_invoice);
    }

    /**
     * Check that this payment option is still available
     *
     * @return bool
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        if (!Configuration::get(Lenco::CONFIG_ENABLED)) {
            return false;
        }

        $modules = Module::getPaymentModules();

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }
}
