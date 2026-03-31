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
 * This controller receives customer on cancellation from Lenco payment page
 */
class LencoCancelModuleFrontController extends ModuleFrontController
{
    /**
     * @var Lenco
     */
    public $module;

    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        $idOrder = (int) Tools::getValue('id_order');
        $reference = Tools::getValue('reference');

        // Order already saved in PrestaShop
        if (!empty($idOrder)) {
            $order = new Order($idOrder);

            if (!Validate::isLoadedObject($order)) {
                Tools::redirect($this->context->link->getPageLink('index'));
            }

            $currentOrderStateId = (int) $order->getCurrentState();
            $newOrderStateId = (int) $this->getNewState($order);

            // Prevent duplicate state entry
            if ($currentOrderStateId !== $newOrderStateId
                && !$order->hasBeenShipped()
                && !$order->hasBeenDelivered()
            ) {
                $orderHistory = new OrderHistory();
                $orderHistory->id_order = $idOrder;
                $orderHistory->changeIdOrderState($newOrderStateId, $idOrder);
                $orderHistory->addWithemail();
            }

            // Update transaction
            if (!empty($reference)) {
                $this->module->updateTransaction($reference, 'cancelled');
            }

            Tools::redirect($this->context->link->getPageLink('index'));
        }

        // Order not saved, redirect to payment step
        Tools::redirect($this->context->link->getPageLink(
            'order',
            true,
            (int) $this->context->language->id,
            [
                'step' => 4,
            ]
        ));
    }

    /**
     * Get new order state after cancellation
     *
     * @param Order $order
     *
     * @return int
     */
    private function getNewState(Order $order)
    {
        if ($order->hasBeenPaid() || $order->isInPreparation()) {
            return (int) Configuration::get('PS_OS_CANCELED');
        }

        return (int) Configuration::get('PS_OS_ERROR');
    }
}
