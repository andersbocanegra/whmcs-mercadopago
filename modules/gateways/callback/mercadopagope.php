<?php
/**
 * Mercado Pago (Perú) - WHMCS IPN/Webhook Callback Handler
 *
 * Receives payment status notifications from Mercado Pago (Webhooks / IPN)
 * and automatically marks WHMCS invoices as paid upon successful payments.
 *
 * Supported notification sources:
 *   - Webhooks (POST JSON body with action & data.id)
 *   - Legacy IPN (GET query string with topic & id / data_id)
 *
 * Notification URL to configure in your Mercado Pago app:
 *   https://yourwhmcs.com/modules/gateways/callback/mercadopagope.php
 *
 * @package    WHMCS MercadoPago PE
 * @author     MEDIOSeIDEAS.COM S.A.C.
 * @link       https://github.com/medioseideas/whmcs-mercadopago-pe
 * @license    MIT
 * @version    1.0.0
 *
 * Mercado Pago Notifications Documentation:
 * https://www.mercadopago.com.pe/developers/es/docs/your-integrations/notifications
 */

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// -------------------------------------------------------------------------
// Bootstrap: Load gateway settings and verify the module is active
// -------------------------------------------------------------------------
$gatewayModuleName = 'mercadopagope';
$gatewayParams     = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// -------------------------------------------------------------------------
// Resolve credentials based on Sandbox / Production mode
// -------------------------------------------------------------------------
$sandboxMode = isset($gatewayParams['SandboxMode']) && $gatewayParams['SandboxMode'] === 'on';
$accessToken = $sandboxMode ? $gatewayParams['TestAccessToken'] : $gatewayParams['AccessToken'];

// -------------------------------------------------------------------------
// Parse incoming notification
// Mercado Pago may send webhooks (POST) or legacy IPNs (GET/POST query-string)
// -------------------------------------------------------------------------

// Limit payload size to 1 MB to prevent Denial-of-Service via large payloads
$rawPayload = stream_get_contents(fopen('php://input', 'r'), 1048576);
$payload    = json_decode($rawPayload, true);

// Determine notification type: sanitize to alphanumeric+dot to prevent log injection
$rawAction = isset($_GET['topic'])      ? $_GET['topic']
           : (isset($_GET['type'])      ? $_GET['type']
           : (isset($payload['action']) ? $payload['action']
           : (isset($payload['type'])   ? $payload['type'] : '')));
$action = preg_replace('/[^a-zA-Z0-9._-]/', '', (string) $rawAction);

// Determine payment ID: must be a positive integer
$rawPaymentId = isset($_GET['id'])            ? $_GET['id']
              : (isset($_GET['data_id'])      ? $_GET['data_id']
              : (isset($payload['data']['id']) ? $payload['data']['id'] : null));
$paymentId = (is_numeric($rawPaymentId) && (int) $rawPaymentId > 0) ? (int) $rawPaymentId : null;

// -------------------------------------------------------------------------
// Handle payment notifications
// We only act on "payment" topics/types; other event types are acknowledged.
// -------------------------------------------------------------------------
$paymentTopics = array('payment', 'payment.created', 'payment.updated');

if (in_array($action, $paymentTopics, true) && !empty($paymentId)) {

    // Fetch full payment details from the Mercado Pago Payments API
    // This step is required to prevent spoofed notifications
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => 'https://api.mercadopago.com/v1/payments/' . (int) $paymentId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => array('Authorization: Bearer ' . $accessToken),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ));

    $apiResponse = curl_exec($ch);
    $curlError   = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        logTransaction($gatewayParams['name'], 'cURL Error: ' . $curlError, 'API Fetch Failed');
        http_response_code(500);
        die('Internal Error');
    }

    $paymentData = json_decode($apiResponse, true);

    if (!isset($paymentData['status'])) {
        logTransaction($gatewayParams['name'], $apiResponse, 'Invalid API Response');
        http_response_code(400);
        die('Bad Response');
    }

    $status        = $paymentData['status'];
    $invoiceId     = isset($paymentData['external_reference']) ? $paymentData['external_reference'] : null;
    $transactionId = isset($paymentData['id'])                 ? $paymentData['id']                 : null;
    $paidAmount    = isset($paymentData['transaction_amount']) ? $paymentData['transaction_amount']  : 0;
    $fee           = isset($paymentData['fee_details'][0]['amount']) ? $paymentData['fee_details'][0]['amount'] : 0;

    if (empty($invoiceId) || empty($transactionId)) {
        logTransaction($gatewayParams['name'], $apiResponse, 'Missing Invoice or Transaction ID');
        http_response_code(400);
        die('Bad Request');
    }

    // Use WHMCS helper functions to validate the invoice ID and transaction ID
    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);
    checkCbTransID($transactionId);

    switch ($status) {
        case 'approved':
            // Mark invoice as paid in WHMCS
            logTransaction($gatewayParams['name'], $apiResponse, 'Successful');
            addInvoicePayment($invoiceId, $transactionId, $paidAmount, $fee, $gatewayModuleName);
            http_response_code(200);
            die('OK');

        case 'pending':
        case 'in_process':
        case 'authorized':
            // Payment is being processed; no action needed in WHMCS yet.
            // MP will send another notification when status changes.
            logTransaction($gatewayParams['name'], $apiResponse, 'Pending / In Process');
            http_response_code(200);
            die('OK');

        default:
            // rejected, cancelled, charged_back, refunded, etc.
            logTransaction($gatewayParams['name'], $apiResponse, 'Not Approved: ' . $status);
            http_response_code(200);
            die('OK');
    }
}

// -------------------------------------------------------------------------
// Unhandled notification (e.g., topic=merchant_order, chargebacks, etc.)
// Return 200 so Mercado Pago does not retry endlessly.
// -------------------------------------------------------------------------
logTransaction(
    $gatewayParams['name'],
    'URI: ' . $_SERVER['REQUEST_URI'] . ' | Payload: ' . $rawPayload,
    'Unhandled Notification'
);
http_response_code(200);
die('Acknowledged');
