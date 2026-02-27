# Mercado Pago (Perú) - Pasarela de Pago para WHMCS

[![WHMCS](https://img.shields.io/badge/WHMCS-8.x-blue.svg)](https://www.whmcs.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Mercado Pago](https://img.shields.io/badge/Checkout-Pro-009ee3.svg)](https://www.mercadopago.com.pe/developers)

Pasarela de pago **Mercado Pago Checkout Pro** para [WHMCS](https://www.whmcs.com/), optimizada para **Perú (PEN)**. Incluye soporte para modo Sandbox y Producción, manejo automático de webhooks, panel de administración visual y soporte para identificación DNI/RUC del cliente.

---

## ✨ Características

- ✅ **Checkout Pro** — Redirige al cliente al checkout oficial de Mercado Pago (máxima seguridad y conversión)
- 🔄 **Modo Producción y Sandbox** — Conmutable desde la interfaz de administración
- 🎛️ **Panel de Administración Visual** — UI moderna para gestionar credenciales sin tocar código
- 🔔 **Webhook / IPN automático** — Marca facturas como pagadas automáticamente al aprobarse el pago
- 🆔 **Campo DNI/RUC** — Soporte para mapear el número de documento del cliente (recomendado para Perú)
- 📋 **Log de transacciones** — Registros detallados de cada llamada a la API en el Gateway Log de WHMCS
- 🔑 **Idempotency Key** — Evita la creación de preferencias duplicadas en reintentos
- 🛡️ **Verificación de Webhook** — Consulta la API de MP para verificar el pago antes de marcarlo como pagado

---

## 📋 Requisitos

| Componente | Versión mínima |
|---|---|
| WHMCS | 7.0+ (Probado en 8.8.0) |
| PHP | 7.4+ |
| Extensión cURL | Activa |
| SSL/HTTPS | Requerido para webhooks |
| Cuenta Mercado Pago | Perú (cuenta de vendedor activa) |

---

## 📁 Estructura de Archivos

```
whmcs-mercadopago-pe/
├── modules/
│   ├── gateways/
│   │   ├── mercadopagope.php          # Pasarela principal (lógica de pago)
│   │   └── callback/
│   │       └── mercadopagope.php      # Manejador de Webhooks / IPN
│   └── addons/
│       └── mp_visual_config/
│           └── mp_visual_config.php   # Panel de administración visual
├── README.md
├── CHANGELOG.md
└── LICENSE
```

---

## 🚀 Instalación

### Paso 1: Copiar los archivos

Copia los archivos al servidor donde está instalado WHMCS, respetando la estructura de carpetas:

```bash
# Pasarela principal
cp modules/gateways/mercadopagope.php       /ruta/whmcs/modules/gateways/
cp modules/gateways/callback/mercadopagope.php  /ruta/whmcs/modules/gateways/callback/

# Panel de administración visual (opcional pero recomendado)
cp -r modules/addons/mp_visual_config/      /ruta/whmcs/modules/addons/
```

### Paso 2: Activar la pasarela en WHMCS

1. Inicia sesión en tu WHMCS Admin.
2. Ve a **Configuración → Pasarelas de Pago → Todas las Pasarelas**.
3. Busca **"Mercado Pago (Perú)"** y haz clic en **Activar**.

### Paso 3: Activar el módulo de administración visual (opcional)

1. Ve a **Addons → Módulos Addon → Administrar Módulos Addon**.
2. Busca **"Mercado Pago (Perú) - Admin Visual"** y haz clic en **Activar**.
3. Configura el acceso de roles de administrador si es necesario.

### Paso 4: Configurar las credenciales

**Opción A (Recomendada): Panel Visual**

1. Ve a **Addons → Mercado Pago (Perú) - Admin Visual**.
2. Ingresa tu **Public Key** y **Access Token** de Producción.
3. Si quieres probar primero, ingresa también tus credenciales de Sandbox y activa el interruptor.
4. Haz clic en **Guardar Cambios**.

**Opción B: Configuración estándar de WHMCS**

1. Ve a **Configuración → Pasarelas de Pago → Mercado Pago (Perú)**.
2. Completa los campos de Access Token, Public Key, etc.

### Paso 5 (Opcional): Configurar el campo DNI

Para enviar el DNI de tus clientes a la API de Mercado Pago (mejora la tasa de aprobación):

1. Asegúrate de tener un **Campo Personalizado de Cliente** en WHMCS para el DNI/RUC.
2. Anota el **ID** de ese campo (visible en la URL al editarlo).
3. En la configuración de la pasarela, ingresa ese número en el campo **"ID del Campo Personalizado de DNI/RUC"**.

---

## ⚙️ Configuración de Webhooks en Mercado Pago

1. Ve al panel de [Desarrolladores de Mercado Pago (Perú)](https://www.mercadopago.com.pe/developers/panel).
2. Selecciona tu aplicación.
3. En la sección **Webhooks** o **Notificaciones IPN**, agrega la URL:
   ```
   https://TU-WHMCS.com/modules/gateways/callback/mercadopagope.php
   ```
4. Selecciona el evento **`payment`**.

> **Importante:** Tu servidor debe ser accesible públicamente y tener un certificado SSL válido (HTTPS).

---

## 🧪 Modo Sandbox (Pruebas)

> ⚠️ El entorno Sandbox de Mercado Pago tiene restricciones estrictas. Sigue estos pasos para evitar errores comunes.

1. Ve al [Panel de Desarrolladores](https://www.mercadopago.com.pe/developers/panel) y crea una **Cuenta de Prueba de Comprador**.
2. En WHMCS, activa el **Modo Sandbox** en la configuración de la pasarela.
3. Realiza el pago de prueba desde una **ventana de incógnito**, iniciando sesión con la cuenta de comprador de prueba (NO con tu cuenta real de vendedor).
4. Usa las [tarjetas de prueba oficiales](https://www.mercadopago.com.pe/developers/es/docs/your-integrations/test/cards) de Mercado Pago.

---

## 🔍 Diagnóstico y Solución de Problemas

### Ver los logs de la pasarela

Ve a **WHMCS Admin → Facturación → Registro de Pasarela de Pago** y filtra por "Mercado Pago (Perú)".

Cada intento de pago registra:
- Modo (Sandbox/Producción)
- Moneda y monto
- DNI enviado (si está configurado)
- JSON completo del Request
- JSON completo del Response de la API

### Errores comunes

| Error | Causa probable | Solución |
|---|---|---|
| `No hay Access Token` | Credenciales no guardadas | Configura la pasarela desde el Panel Visual |
| `Algo salió mal` (orange MP screen) | Tarjeta rechazada por antifraude o auto-cobro | No uses tu tarjeta propia; usa otra persona para probar |
| `ERR_TOO_MANY_REDIRECTS` en Sandbox | Cruce de sesiones de navegador | Usa una ventana de incógnito |
| `Una de las partes es de prueba` | Mezcla de cuentas reales y de prueba | En Sandbox, comprador y vendedor deben ser cuentas de prueba |
| Webhook no recibido | URL bloqueada o sin HTTPS | Verifica que tu URL sea pública y tenga SSL |

---

## 🔒 Seguridad

- Los Access Tokens se almacenan en la tabla `tblpaymentgateways` de WHMCS (cifrada por WHMCS).
- El formulario de configuración visual usa CSRF token nativo de WHMCS (`check_token` / `generate_token`).
- El callback verifica el pago llamando directamente a la API de Mercado Pago antes de marcar la factura como pagada.
- Nunca se almacenan datos de tarjetas — todo se procesa en la infraestructura segura de Mercado Pago.
- Se incluye `X-Idempotency-Key` en cada creación de preferencia para prevenir pagos duplicados.

---

## 📝 Contribuciones

¡Las contribuciones son bienvenidas! Por favor:

1. Haz un fork del repositorio.
2. Crea una rama para tu feature: `git checkout -b feature/nueva-funcionalidad`
3. Haz commit de tus cambios con mensajes descriptivos.
4. Abre un Pull Request explicando los cambios.

---

## 📄 Licencia

Este proyecto está bajo la licencia [MIT](LICENSE). Eres libre de usar, modificar y distribuir este módulo, incluso para uso comercial, con atribución.

---

## 🙋 Soporte

Si tienes dudas o encuentras un bug:
- Abre un [Issue en GitHub](https://github.com/medioseideas/whmcs-mercadopago-pe/issues)
- Revisa los logs de la pasarela en WHMCS antes de reportar

Desarrollado con ❤️ por ** Anders Bocanegra [MEDIOSeIDEAS.COM S.A.C.](https://medioseideas.com)** — Chiclayo, Perú.
