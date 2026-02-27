<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Define module configuration
 */
function mp_visual_config_config() {
    return array(
        'name' => 'Mercado Pago (Perú) - Admin Visual',
        'description' => 'Interfaz visual para administrar la pasarela de Mercado Pago de Perú.',
        'author' => 'Developer',
        'language' => 'spanish',
        'version' => '1.0',
        'fields' => array()
    );
}

/**
 * Action upon activation
 */
function mp_visual_config_activate() {
    return array('status' => 'success', 'description' => 'Módulo Visual Activado Correctamente.');
}

/**
 * Action upon deactivation
 */
function mp_visual_config_deactivate() {
    return array('status' => 'success', 'description' => 'Módulo Visual Desactivado.');
}

/**
 * The output function that handles form submission and displays the UI.
 */
function mp_visual_config_output($vars) {
    // Process form submission
    if (isset($_POST['save_mp_settings'])) {
        check_token("WHMCS.admin.default"); // Verify CSRF token
        
        $accessToken = trim($_POST['access_token']);
        $publicKey = trim($_POST['public_key']);
        $testAccessToken = trim($_POST['test_access_token']);
        $testPublicKey = trim($_POST['test_public_key']);
        $sandboxMode = isset($_POST['sandbox_mode']) ? 'on' : '';

        try {
            // Sanitize: strip tags and trim. Keys should be plain ASCII tokens.
            $sanitize = function($val) {
                return trim(strip_tags($val));
            };
            $accessToken     = $sanitize($_POST['access_token']);
            $publicKey       = $sanitize($_POST['public_key']);
            $testAccessToken = $sanitize($_POST['test_access_token']);
            $testPublicKey   = $sanitize($_POST['test_public_key']);
            $sandboxMode     = isset($_POST['sandbox_mode']) ? 'on' : '';

            // Delete existing gateway settings for MP PE to overwrite
            Capsule::table('tblpaymentgateways')
                ->where('gateway', 'mercadopagope')
                ->whereIn('setting', ['AccessToken', 'PublicKey', 'TestAccessToken', 'TestPublicKey', 'SandboxMode'])
                ->delete();

            // Insert new settings
            Capsule::table('tblpaymentgateways')->insert([
                ['gateway' => 'mercadopagope', 'setting' => 'AccessToken',     'value' => $accessToken],
                ['gateway' => 'mercadopagope', 'setting' => 'PublicKey',       'value' => $publicKey],
                ['gateway' => 'mercadopagope', 'setting' => 'TestAccessToken', 'value' => $testAccessToken],
                ['gateway' => 'mercadopagope', 'setting' => 'TestPublicKey',   'value' => $testPublicKey],
                ['gateway' => 'mercadopagope', 'setting' => 'SandboxMode',     'value' => $sandboxMode],
            ]);
            
            // Ensure the gateway is actually "installed" in tblpaymentgateways
            $nameExists = Capsule::table('tblpaymentgateways')->where('gateway', 'mercadopagope')->where('setting', 'name')->count();
            if (!$nameExists) {
                Capsule::table('tblpaymentgateways')->insert([
                    ['gateway' => 'mercadopagope', 'setting' => 'name',    'value' => 'Mercado Pago (Perú)'],
                    ['gateway' => 'mercadopagope', 'setting' => 'type',    'value' => 'System'],
                    ['gateway' => 'mercadopagope', 'setting' => 'visible', 'value' => 'on'],
                ]);
            }
            
            echo '<div class="alert alert-success">Configuración guardada exitosamente y sincronizada con la Pasarela de Pago principal.</div>';
        } catch (\Exception $e) {
            // Do NOT expose the raw exception message to the user — it may contain
            // internal database details, table names, or credentials.
            error_log('[MercadoPago PE] Config save error: ' . $e->getMessage());
            echo '<div class="alert alert-danger">Error al guardar la configuración. Por favor revise los logs del servidor.</div>';
        }
    }

    // Load existing settings
    $settings = Capsule::table('tblpaymentgateways')
        ->where('gateway', 'mercadopagope')
        ->pluck('value', 'setting');
        
    $currentAccessToken = isset($settings['AccessToken']) ? $settings['AccessToken'] : '';
    $currentPublicKey = isset($settings['PublicKey']) ? $settings['PublicKey'] : '';
    $currentTestAccessToken = isset($settings['TestAccessToken']) ? $settings['TestAccessToken'] : '';
    $currentTestPublicKey = isset($settings['TestPublicKey']) ? $settings['TestPublicKey'] : '';
    $currentSandboxMode = isset($settings['SandboxMode']) ? $settings['SandboxMode'] : '';

    $isConfigured = (($currentAccessToken && $currentPublicKey) || ($currentTestAccessToken && $currentTestPublicKey));

    ?>
    <style>
        .mp-admin-container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .mp-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #009ee3;
            padding-bottom: 15px;
        }
        .mp-logo {
            width: 50px;
            height: 50px;
            background: #009ee3;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
            margin-right: 15px;
        }
        .mp-title h2 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        .mp-title p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }
        .mp-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .mp-form-group {
            margin-bottom: 20px;
        }
        .mp-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }
        .mp-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 15px;
            transition: border-color 0.15s ease-in-out;
            box-sizing: border-box;
        }
        .mp-input:focus {
            border-color: #009ee3;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 158, 227, 0.25);
        }
        .mp-btn {
            background: #009ee3;
            color: #fff;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .mp-btn:hover {
            background: #008cd6;
        }
        .mp-help {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
            display: block;
        }
        .mp-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .mp-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .mp-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .mp-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .mp-slider {
            background-color: #009ee3;
        }
        input:checked + .mp-slider:before {
            transform: translateX(26px);
        }
        .mp-status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background: <?php echo $isConfigured ? '#d4edda' : '#fff3cd'; ?>;
            color: <?php echo $isConfigured ? '#155724' : '#856404'; ?>;
        }
        
        .mp-info-box {
            background-color: #e2f3fb;
            border-left: 4px solid #009ee3;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 0 4px 4px 0;
            font-size: 14px;
        }
    </style>

    <div class="mp-admin-container">
        <div class="mp-header">
            <div class="mp-logo">MP</div>
            <div class="mp-title">
                <h2>Administración de Mercado Pago (Perú)</h2>
                <p>Configura las credenciales de tu pasarela de pago de forma visual.</p>
            </div>
        </div>
        
        <div class="mp-info-box">
            <strong>Importante:</strong> Al guardar tus credenciales en este panel visual, se sincronizarán directamente con el Core de WHMCS, por lo que no necesitas configurarlas en el apartado de Ajustes Generales de Pasarelas de Pago.
        </div>
        
        <div class="mp-card" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>Estado de Configuración:</strong> 
                <span class="mp-status-badge">
                    <?php echo $isConfigured ? '✓ Configurado' : '⚠ Faltan Credenciales'; ?>
                </span>
                <?php if ($currentSandboxMode == 'on'): ?>
                    <span class="mp-status-badge" style="background:#fff3cd; color:#856404; margin-left: 10px;">Modo Pruebas (Sandbox) Activo</span>
                <?php else: ?>
                    <span class="mp-status-badge" style="background:#cce5ff; color:#004085; margin-left: 10px;">Modo Producción Activo</span>
                <?php endif; ?>
            </div>
            <div>
                <a href="https://www.mercadopago.com.pe/developers/panel/credentials" target="_blank" style="color: #009ee3; text-decoration: none; font-weight: bold;">Obtener Credenciales de MP &rarr;</a>
            </div>
        </div>

        <form method="post" action="">
            <input type="hidden" name="save_mp_settings" value="1" />
            <?php echo generate_token("form"); ?>
            
            <div class="mp-card" style="border-left: 4px solid #009ee3;">
                <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 18px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px;">Modo de Transacciones</h3>
                <div class="mp-form-group" style="display: flex; align-items: center;">
                    <label class="mp-switch" style="margin-right: 15px;">
                        <input type="checkbox" name="sandbox_mode" id="sandbox_mode" <?php echo ($currentSandboxMode == 'on') ? 'checked' : ''; ?>>
                        <span class="mp-slider"></span>
                    </label>
                    <div>
                        <strong style="display: block;">Activar Modo Pruebas (Sandbox)</strong>
                        <span class="mp-help" style="margin-top: 2px;">Si está activo, las transacciones se realizarán en el entorno de pruebas usando las Credenciales de Pruebas. Ningún cobro será real.</span>
                    </div>
                </div>
            </div>

            <div class="mp-card">
                <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 18px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px;">Credenciales de Producción</h3>
                
                <div class="mp-form-group">
                    <label class="mp-label" for="public_key">Public Key</label>
                    <input type="text" id="public_key" name="public_key" class="mp-input" value="<?php echo htmlspecialchars($currentPublicKey); ?>" placeholder="APP_USR-..." />
                    <span class="mp-help">La clave pública de tu cuenta de Mercado Pago (Perú). Comienza generalmente con APP_USR-...</span>
                </div>
                
                <div class="mp-form-group">
                    <label class="mp-label" for="access_token">Access Token</label>
                    <input type="password" id="access_token" name="access_token" class="mp-input" value="<?php echo htmlspecialchars($currentAccessToken); ?>" placeholder="APP_USR-..." />
                    <span class="mp-help">El token de acceso privado. Mantenlo en secreto.</span>
                </div>
            </div>

            <div class="mp-card">
                <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 18px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px;">Credenciales de Pruebas (Test / Sandbox)</h3>
                
                <div class="mp-form-group">
                    <label class="mp-label" for="test_public_key">Test Public Key</label>
                    <input type="text" id="test_public_key" name="test_public_key" class="mp-input" value="<?php echo htmlspecialchars($currentTestPublicKey); ?>" placeholder="TEST-..." />
                    <span class="mp-help">Tu clave pública para el entorno de pruebas. Comienza generalmente con TEST-...</span>
                </div>
                
                <div class="mp-form-group">
                    <label class="mp-label" for="test_access_token">Test Access Token</label>
                    <input type="password" id="test_access_token" name="test_access_token" class="mp-input" value="<?php echo htmlspecialchars($currentTestAccessToken); ?>" placeholder="TEST-..." />
                    <span class="mp-help">Tu token de acceso para el entorno de pruebas. Comienza generalmente con TEST-...</span>
                </div>
            </div>
            
            <div style="text-align: right;">
                <button type="submit" class="mp-btn">Guardar Cambios</button>
            </div>
        </form>
    </div>
    <?php
}
