<?php
/**
 * Mercado Pago (Perú) - WHMCS Payment Gateway
 *
 * Pasarela de pago Mercado Pago Checkout Pro para WHMCS.
 * Soporta modo Producción y Sandbox (Pruebas).
 * Incluye soporte para campo personalizado DNI (Perú).
 *
 * @package    WHMCS MercadoPago PE
 * @author     MEDIOSeIDEAS.COM S.A.C.
 * @link       https://github.com/medioseideas/whmcs-mercadopago-pe
 * @license    MIT
 * @version    1.0.0
 * @requires   WHMCS 7.0+, PHP 7.4+
 *
 * WHMCS Gateway Module Developer Documentation:
 * https://developers.whmcs.com/payment-gateways/
 *
 * Mercado Pago Checkout Pro Documentation:
 * https://www.mercadopago.com.pe/developers/es/docs/checkout-pro/landing
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Module metadata for WHMCS gateway registration.
 *
 * @return array
 */
function mercadopagope_MetaData()
{
    return array(
        'DisplayName'               => 'Mercado Pago (Perú)',
        'APIVersion'                => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage'          => false,
    );
}

/**
 * Define gateway configuration fields shown in WHMCS admin.
 * These fields are stored in the tblpaymentgateways table.
 *
 * Tip: If you use the Visual Admin addon (mp_visual_config), you can manage
 * credentials from there instead of this standard config screen.
 *
 * @return array
 */
function mercadopagope_config()
{
    return array(
        'FriendlyName' => array(
            'Type'  => 'System',
            'Value' => 'Mercado Pago (Perú)',
        ),
        'AccessToken' => array(
            'FriendlyName' => 'Access Token (Producción)',
            'Type'         => 'password',
            'Size'         => '80',
            'Default'      => '',
            'Description'  => 'Tu Access Token de Producción de Mercado Pago.',
        ),
        'PublicKey' => array(
            'FriendlyName' => 'Public Key (Producción)',
            'Type'         => 'text',
            'Size'         => '80',
            'Default'      => '',
            'Description'  => 'Tu Public Key de Producción de Mercado Pago.',
        ),
        'TestAccessToken' => array(
            'FriendlyName' => 'Test Access Token (Sandbox)',
            'Type'         => 'password',
            'Size'         => '80',
            'Default'      => '',
            'Description'  => 'Tu Test Access Token del entorno Sandbox.',
        ),
        'TestPublicKey' => array(
            'FriendlyName' => 'Test Public Key (Sandbox)',
            'Type'         => 'text',
            'Size'         => '80',
            'Default'      => '',
            'Description'  => 'Tu Test Public Key del entorno Sandbox.',
        ),
        'SandboxMode' => array(
            'FriendlyName' => 'Modo Sandbox (Pruebas)',
            'Type'         => 'yesno',
            'Description'  => 'Marcar para usar las credenciales de prueba (Sandbox). Los cobros NO serán reales.',
        ),
        'DniCustomFieldId' => array(
            'FriendlyName' => 'ID del Campo Personalizado de DNI/RUC',
            'Type'         => 'text',
            'Size'         => '10',
            'Default'      => '',
            'Description'  => 'Opcional. ID del campo personalizado de WHMCS donde se almacena el DNI/RUC del cliente. Recomendado para Perú.',
        ),
    );
}

/**
 * Generate a Mercado Pago Checkout Pro payment link for a WHMCS invoice.
 *
 * Creates a payment preference via the Mercado Pago API and returns an
 * anchor tag (<a>) pointing to the hosted checkout page. WHMCS will render
 * this HTML inside the invoice payment section.
 *
 * Flow:
 *  1. Gather invoice & client details from $params.
 *  2. Optional: lookup client's DNI from a WHMCS custom field for richer payer data.
 *  3. POST a Preference object to https://api.mercadopago.com/checkout/preferences.
 *  4. Return an HTML link to the returned init_point (or sandbox_init_point).
 *  5. After payment, MP calls back our IPN handler (callback/mercadopagope.php).
 *
 * @param array $params Standard WHMCS gateway params array.
 *              See: https://developers.whmcs.com/payment-gateways/third-party-gateway/
 * @return string HTML output to display on the invoice page.
 */
function mercadopagope_link($params)
{
    // -------------------------------------------------------------------------
    // Determine mode and credentials
    // -------------------------------------------------------------------------
    $sandboxMode = isset($params['SandboxMode']) && $params['SandboxMode'] === 'on';
    $accessToken = $sandboxMode ? $params['TestAccessToken'] : $params['AccessToken'];

    if (empty($accessToken)) {
        return '<p style="color:red;"><strong>Error:</strong> No hay Access Token configurado. Por favor configure el módulo en WHMCS Admin → Pasarelas de Pago.</p>';
    }

    // -------------------------------------------------------------------------
    // Invoice and client details from WHMCS
    // -------------------------------------------------------------------------
    $invoiceId   = $params['invoiceid'];
    $description = $params['description'];
    $amount      = $params['amount'];
    $currency    = $params['currency'];
    $clientId    = $params['clientdetails']['id'];
    $firstname   = $params['clientdetails']['firstname'];
    $lastname    = $params['clientdetails']['lastname'];
    $email       = $params['clientdetails']['email'];
    $phone       = isset($params['clientdetails']['phonenumber'])
                    ? preg_replace('/\D/', '', $params['clientdetails']['phonenumber'])
                    : '';
    $postcode    = isset($params['clientdetails']['postcode'])    ? $params['clientdetails']['postcode']  : '';
    $address1    = isset($params['clientdetails']['address1'])    ? $params['clientdetails']['address1']  : '';

    $companyName = $params['companyname'];
    $systemUrl   = $params['systemurl'];
    $returnUrl   = $params['returnurl'];
    $langPayNow  = $params['langpaynow'];

    // -------------------------------------------------------------------------
    // Optional: Read client's DNI/RUC from a WHMCS Custom Field
    // Configure the field ID in the gateway settings → "DniCustomFieldId"
    // -------------------------------------------------------------------------
    $dniNumber  = '';
    $dniType    = 'DNI'; // Default document type for Peru. Change to 'RUC' if needed.
    $dniFieldId = isset($params['DniCustomFieldId']) ? (int)$params['DniCustomFieldId'] : 0;

    if ($dniFieldId > 0) {
        try {
            $fieldValue = \WHMCS\Database\Capsule::table('tblcustomfieldsvalues')
                ->where('fieldid', $dniFieldId)
                ->where('relid', $clientId)
                ->value('value');
            if (!empty($fieldValue)) {
                $dniNumber = trim($fieldValue);
            }
        } catch (\Exception $e) {
            // Custom field not found or DB error; proceed without DNI
        }
    }

    // -------------------------------------------------------------------------
    // Build the Checkout Pro Preference payload
    // Docs: https://www.mercadopago.com.pe/developers/es/reference/preferences/_checkout_preferences/post
    // -------------------------------------------------------------------------
    $notificationUrl = rtrim($systemUrl, '/') . '/modules/gateways/callback/mercadopagope.php';

    $preference = array(
        'items' => array(
            array(
                'id'          => (string) $invoiceId,
                'title'       => 'Requerimiento de Pago #' . $invoiceId . ' - ' . $companyName,
                'description' => !empty($description) ? (string) $description : 'Pago de Factura #' . $invoiceId,
                'category_id' => 'services',
                'quantity'    => 1,
                'currency_id' => $currency,
                'unit_price'  => (float) $amount,
            ),
        ),
        'payer' => array(
            'name'    => $firstname,
            'surname' => $lastname,
            'email'   => $email,
            'phone'   => array(
                'area_code' => '',
                'number'    => $phone,
            ),
            'address' => array(
                'zip_code'      => $postcode,
                'street_name'   => $address1,
                'street_number' => '',
            ),
            'identification' => array(
                'type'   => !empty($dniNumber) ? $dniType : '',
                'number' => $dniNumber,
            ),
        ),
        'back_urls' => array(
            'success' => $returnUrl,
            'failure' => $returnUrl,
            'pending' => $returnUrl,
        ),
        'auto_return'        => 'approved',
        'external_reference' => (string) $invoiceId,
        'notification_url'   => $notificationUrl,
    );

    // -------------------------------------------------------------------------
    // POST preference to Mercado Pago API
    // -------------------------------------------------------------------------
    $apiUrl = 'https://api.mercadopago.com/checkout/preferences';

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($preference),
        CURLOPT_HTTPHEADER     => array(
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            // Idempotency key prevents duplicate preferences on retries
            'X-Idempotency-Key: whmcs-' . $invoiceId . '-' . date('YmdHi'),
        ),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 30,
    ));

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Log every API interaction for debugging (visible in WHMCS → Billing → Gateway Log)
    logTransaction(
        $params['name'],
        'HTTP ' . $httpCode . ' | SandboxMode: ' . ($sandboxMode ? 'YES' : 'NO') .
        ' | Currency: ' . $currency .
        ' | Amount: ' . $amount .
        ' | DNI: ' . ($dniNumber ?: 'N/A') .
        ' | Request: ' . json_encode($preference) .
        ' | Response: ' . $response,
        'Preference API Call — Invoice #' . $invoiceId
    );

    if ($curlError) {
        return '<p style="color:red;">Error de conexión con Mercado Pago. Por favor intente más tarde. (cURL: ' . htmlspecialchars($curlError) . ')</p>';
    }

    $data = json_decode($response, true);

    // -------------------------------------------------------------------------
    // Build and return the payment button HTML
    // -------------------------------------------------------------------------
    if (!empty($data['init_point'])) {
        $checkoutUrl = ($sandboxMode && !empty($data['sandbox_init_point']))
            ? $data['sandbox_init_point']
            : $data['init_point'];

        // MP's security.js adds extra fraud-prevention metadata to the checkout
        $html  = "<script src='https://www.mercadopago.com/v2/security.js' view='item'></script>\n";
        $html .= "<a href='" . htmlspecialchars($checkoutUrl, ENT_QUOTES, 'UTF-8') . "' ";
        $html .= "class='btn btn-primary btn-lg' rel='noopener'>";
        $html .= "<img src='https://www.mercadopago.com/favicon.ico' ";
        $html .= "style='height:16px;vertical-align:middle;margin-right:6px;' alt='MP'>";
        $html .= htmlspecialchars($langPayNow, ENT_QUOTES, 'UTF-8') . "</a>";

        if ($sandboxMode) {
            $html .= "\n<p style='color:#856404;background:#fff3cd;padding:8px 12px;border-radius:4px;";
            $html .= "font-size:13px;margin-top:8px;'>";
            $html .= "⚠️ <strong>Modo Sandbox activo.</strong> Usa tu cuenta de comprador de prueba en una ventana incógnito.</p>";
        }

        return $html;
    }

    // API returned an error — display a friendly message and log details
    $errMsg  = isset($data['message']) ? $data['message'] : 'Error desconocido';
    $errCode = isset($data['error'])   ? $data['error']   : 'N/A';
    return '<p style="color:red;">Error al generar el enlace de pago: ' . htmlspecialchars($errMsg) . ' [' . htmlspecialchars($errCode) . ']. Contacte a soporte.</p>';
}
