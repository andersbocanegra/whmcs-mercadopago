# Changelog

Todos los cambios notables de este proyecto se documentarán en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Versionado Semántico](https://semver.org/lang/es/).

---

## [1.0.0] - 2025-02-27

### Añadido
- Integración con **Mercado Pago Checkout Pro** para WHMCS usando la API REST oficial.
- Soporte para **Modo Producción** y **Modo Sandbox (Pruebas)** con credenciales separadas.
- **Panel de Administración Visual** (Addon WHMCS) para configurar credenciales sin editar código.
- Manejador de **Webhooks / IPN** que marca facturas como pagadas automáticamente.
- Soporte de campo personalizado para **DNI/RUC** del cliente (recomendado para Perú).
- **Log detallado** de cada llamada a la API en el Gateway Log de WHMCS.
- **X-Idempotency-Key** para prevenir la creación de preferencias duplicadas.
- Modo de sandbox muestra un aviso visual en la página de pago de WHMCS.
- Verificación de pago del lado del servidor antes de aprobar la factura (anti-spoofing).
- Soporte para notificaciones de tipo `payment`, `payment.created`, `payment.updated` y legacy IPN con `topic`.
- Documentación completa en README.md con guía de instalación, solución de problemas y notas de seguridad.
- Licencia MIT.

### Seguridad
- CSRF token en el formulario del panel de administración visual.
- Access Tokens almacenados en la tabla cifrada de WHMCS (`tblpaymentgateways`).
- Los campos de Access Token se muestran como `type="password"` en el formulario.
- Verificación del pago consultando directamente la API de Mercado Pago antes de marcar la factura.
- Nunca se almacenan datos de tarjetas de crédito — todo se procesa en los servidores de Mercado Pago.

---

## Sin lanzar

- Soporte multi-idioma (inglés / español).
- Notificaciones por email al vendedor al recibir un pago.
- Soporte para reembolsos desde el panel de WHMCS.
