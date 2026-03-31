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
 * This controller handles webhook notifications from Lenco
 */
class LencoWebhookModuleFrontController extends ModuleFrontController
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
        // Only accept POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }

        // Get raw payload
        $payload = file_get_contents('php://input');

        // Verify signature
        $signature = isset($_SERVER['HTTP_X_LENCO_SIGNATURE']) ? $_SERVER['HTTP_X_LENCO_SIGNATURE'] : '';

        if (!$this->verifySignature($payload, $signature)) {
            http_response_code(401);
            exit('Invalid signature');
        }

        // Decode payload
        $data = json_decode($payload, true);

        if (!$data) {
            http_response_code(400);
            exit('Invalid payload');
        }

        // Log webhook
        $this->logWebhook($payload);

        // Process event
        $event = isset($data['event']) ? $data['event'] : '';
        $transactionData = isset($data['data']) ? $data['data'] : [];

        switch ($event) {
            case 'collection.successful':
                $this->handleSuccessfulPayment($transactionData);
                break;
            case 'collection.failed':
                $this->handleFailedPayment($transactionData);
                break;
        }

        http_response_code(200);
        exit('OK');
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload
     * @param string $signature
     *
     * @return bool
     */
    private function verifySignature($payload, $signature)
    {
        $secretKey = Configuration::get(Lenco::CONFIG_SECRET_KEY);

        if (empty($signature) || empty($secretKey)) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', $payload, $secretKey);

        return hash_equals($computedSignature, $signature);
    }

    /**
     * Handle successful payment webhook
     *
     * @param array $data
     */
    private function handleSuccessfulPayment($data)
    {
        $reference = isset($data['reference']) ? $data['reference'] : null;
        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        $channel = isset($data['channel']) ? $data['channel'] : '';

        if (!$reference) {
            return;
        }

        // Get transaction
        $transaction = $this->module->getTransactionByReference($reference);

        if (!$transaction) {
            return;
        }

        // Already processed?
        if ($transaction['status'] === 'success') {
            return;
        }

        $orderId = (int) $transaction['id_order'];
        $order = new Order($orderId);

        if (!Validate::isLoadedObject($order)) {
            return;
        }

        // Verify amount
        $orderTotal = (float) $order->total_paid;
        if (abs($amount - $orderTotal) > 0.01 && $amount > 0) {
            $this->logError('Amount mismatch', $reference, $amount, $orderTotal);
            return;
        }

        // Update order status
        $paidState = (int) Configuration::get('PS_OS_PAYMENT');
        $order->setCurrentState($paidState);

        // Add payment record
        $order->addOrderPayment(
            $order->total_paid,
            null,
            $reference,
            Currency::getCurrencyInstance($order->id_currency),
            date('Y-m-d H:i:s'),
            $this->module->displayName
        );

        // Update transaction
        $this->module->updateTransaction($reference, 'success', json_encode($data));
    }

    /**
     * Handle failed payment webhook
     *
     * @param array $data
     */
    private function handleFailedPayment($data)
    {
        $reference = isset($data['reference']) ? $data['reference'] : null;

        if (!$reference) {
            return;
        }

        $transaction = $this->module->getTransactionByReference($reference);

        if (!$transaction || $transaction['status'] === 'failed') {
            return;
        }

        $orderId = (int) $transaction['id_order'];
        $order = new Order($orderId);

        if (!Validate::isLoadedObject($order)) {
            return;
        }

        // Update order status
        $errorState = (int) Configuration::get('PS_OS_ERROR');
        $order->setCurrentState($errorState);

        // Update transaction
        $this->module->updateTransaction($reference, 'failed', json_encode($data));
    }

    /**
     * Log webhook data
     *
     * @param string $payload
     */
    private function logWebhook($payload)
    {
        $logDir = _PS_ROOT_DIR_ . '/var/logs/';
        $logFile = $logDir . 'lenco_webhook.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logEntry = '[' . date('Y-m-d H:i:s') . '] ' . $payload . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Log error
     *
     * @param string $message
     * @param string $reference
     * @param float $paidAmount
     * @param float $orderAmount
     */
    private function logError($message, $reference, $paidAmount, $orderAmount)
    {
        $logDir = _PS_ROOT_DIR_ . '/var/logs/';
        $logFile = $logDir . 'lenco_webhook.log';

        $logEntry = '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $message .
            ' | Reference: ' . $reference .
            ' | Paid: ' . $paidAmount .
            ' | Order: ' . $orderAmount . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Get transaction by reference
     * 
     * @param string $reference
     * @return array|false
     */
    private function getTransactionByReference($reference)
    {
        return $this->module->getTransactionByReference($reference);
    }
}
