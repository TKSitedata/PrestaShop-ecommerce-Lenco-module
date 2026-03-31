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

class LencoValidationModuleFrontController extends ModuleFrontController
{
    public $module;

    public function postProcess()
    {
        $reference = Tools::getValue('reference');
        $lencoReference = Tools::getValue('lenco_reference');

        $this->logDebug('Validation called', [
            'reference' => $reference,
            'lenco_reference' => $lencoReference,
        ]);

        if (empty($reference)) {
            $this->logDebug('No reference provided');
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                ['step' => 1]
            ));
        }

        $transaction = $this->module->getTransactionByReference($reference);

        if (empty($transaction)) {
            $this->logDebug('Transaction not found', ['reference' => $reference]);
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                ['step' => 1]
            ));
        }

        $orderId = (int) $transaction['id_order'];
        $order = new Order($orderId);

        if (!Validate::isLoadedObject($order)) {
            $this->logDebug('Order not found', ['order_id' => $orderId]);
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                ['step' => 1]
            ));
        }

        $paidState = (int) Configuration::get('PS_OS_PAYMENT');
        if ((int) $order->current_state === $paidState) {
            $this->logDebug('Order already paid', ['order_id' => $orderId]);
            $customer = new Customer($order->id_customer);
            Tools::redirect($this->context->link->getPageLink(
                'order-confirmation',
                true,
                (int) $this->context->language->id,
                [
                    'id_cart' => (int) $order->id_cart,
                    'id_module' => (int) $this->module->id,
                    'id_order' => (int) $order->id,
                    'key' => $customer->secure_key,
                ]
            ));
        }

        $verification = $this->module->verifyTransaction($reference);

        $this->logDebug('Verification result', [
            'reference' => $reference,
            'verification' => $verification,
        ]);

        if ($verification && is_array($verification)) {
            $status = null;
            $amountPaid = 0;

            if (isset($verification['data']['status'])) {
                $status = $verification['data']['status'];
                $amountPaid = isset($verification['data']['amount']) ? (float) $verification['data']['amount'] : 0;
            } elseif (isset($verification['status'])) {
                $status = $verification['status'];
                $amountPaid = isset($verification['amount']) ? (float) $verification['amount'] : 0;
            } elseif (isset($verification['success']) && $verification['success'] === true) {
                $status = 'success';
                $amountPaid = isset($verification['data']['amount']) ? (float) $verification['data']['amount'] : 0;
            }

            $this->logDebug('Parsed status', [
                'status' => $status,
                'amount_paid' => $amountPaid,
                'order_total' => (float) $order->total_paid,
            ]);

            if ($status === 'success' || $status === 'completed' || $status === 'paid') {
                $this->updateOrderStatus($order, $reference, $verification);
                $this->module->updateTransaction($reference, 'success', json_encode($verification));

                $customer = new Customer($order->id_customer);
                Tools::redirect($this->context->link->getPageLink(
                    'order-confirmation',
                    true,
                    (int) $this->context->language->id,
                    [
                        'id_cart' => (int) $order->id_cart,
                        'id_module' => (int) $this->module->id,
                        'id_order' => (int) $order->id,
                        'key' => $customer->secure_key,
                    ]
                ));
            } elseif ($status === 'failed' || $status === 'cancelled') {
                $this->handlePaymentError($order, $reference, 'Payment ' . $status);
                Tools::redirect($this->context->link->getPageLink(
                    'order',
                    true,
                    (int) $this->context->language->id,
                    ['step' => 1, 'error' => 'payment_failed']
                ));
            }
        }

        $this->logDebug('Showing pending page', ['reference' => $reference]);

        $this->context->smarty->assign([
            'reference' => $reference,
            'order_id' => $orderId,
            'order_reference' => $order->reference,
        ]);

        $this->setTemplate('module:lenco/views/templates/front/payment_pending.tpl');
    }

    private function updateOrderStatus($order, $reference, $verification)
    {
        $paidState = (int) Configuration::get('PS_OS_PAYMENT');
        $order->setCurrentState($paidState);

        $order->addOrderPayment(
            $order->total_paid,
            null,
            $reference,
            Currency::getCurrencyInstance($order->id_currency),
            date('Y-m-d H:i:s'),
            $this->module->displayName
        );

        $orderHistory = new OrderHistory();
        $orderHistory->id_order = (int) $order->id;
        $orderHistory->changeIdOrderState($paidState, (int) $order->id);
        $orderHistory->addWithemail();

        $this->logDebug('Order updated to paid', [
            'order_id' => $order->id,
            'reference' => $reference,
        ]);
    }

    private function handlePaymentError($order, $reference, $error)
    {
        $errorState = (int) Configuration::get('PS_OS_ERROR');
        $order->setCurrentState($errorState);
        $this->module->updateTransaction($reference, 'failed', json_encode(['error' => $error]));

        $this->logDebug('Payment error', [
            'order_id' => $order->id,
            'reference' => $reference,
            'error' => $error,
        ]);
    }

    private function logDebug($message, $data = [])
    {
        $logDir = _PS_ROOT_DIR_ . '/var/logs/';
        $logFile = $logDir . 'lenco_validation.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logEntry = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        if (!empty($data)) {
            $logEntry .= ' | ' . json_encode($data);
        }
        $logEntry .= "\n";

        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}