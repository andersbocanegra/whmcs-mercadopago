# Manual de Instalación: Módulo de Mercado Pago (Perú) para WHMCS

Este manual detalla los pasos para instalar, configurar y probar el módulo de pago de Mercado Pago (Perú) con Administración Visual en tu plataforma WHMCS.

## Requisitos Previos

1.  Tener WHMCS versión 8.8.0 (o superior) instalado.
2.  Tener una cuenta de vendedor en Mercado Pago (Perú).
3.  Tener acceso a las credenciales de Producción (Access Token y Public Key) desde el panel de desarrolladores de Mercado Pago: [https://www.mercadopago.com.pe/developers/panel/credentials](https://www.mercadopago.com.pe/developers/panel/credentials)

---

## Paso 1: Subir los Archivos

Los siguientes archivos ya han sido creados y colocados en sus directorios correspondientes dentro de tu instalación de WHMCS:

1.  **Módulo Principal de Pago:**
    `[Directorio WHMCS]/modules/gateways/mercadopagope.php`
2.  **Archivo de Notificaciones (Webhook):**
    `[Directorio WHMCS]/modules/gateways/callback/mercadopagope.php`
3.  **Panel de Administración Visual (Addon):**
    `[Directorio WHMCS]/modules/addons/mp_visual_config/mp_visual_config.php`

*(No es necesario que muevas nada, los archivos ya están en su lugar).*

---

## Paso 2: Activar el Panel de Administración Visual

Para configurar el módulo de una manera amigable y visual, primero activaremos el panel de control personalizado:

1.  Inicia sesión en el **Área de Administración de WHMCS**.
2.  En el menú superior derecho (ícono de la llave inglesa), ve a **Ajustes del Sistema** (System Settings).
3.  Haz clic en **Módulos Adicionales** (Addon Modules).
4.  Busca en la lista **"Mercado Pago (Perú) - Admin Visual"** y haz clic en el botón verde **Activar** (Activate).
5.  Una vez activado, en esa misma fila, haz clic en el botón **Configurar** (Configure).
6.  En la sección **Control de Acceso** (Access Control), marca la casilla de tu grupo de administrador (generalmente "Full Administrator").
7.  Haz clic en **Guardar Cambios** (Save Changes).

---

## Paso 3: Configurar las Credenciales Visualmente

Ahora que el panel visual está activo y tienes permisos para verlo:

1.  En el menú principal superior de WHMCS, haz clic en **Addons** (Módulos).
2.  Selecciona **Mercado Pago (Perú) - Admin Visual**.
3.  Verás el nuevo panel de control personalizado.
4.  Ingresa tu **Public Key (Producción)** y tu **Access Token (Producción)** en los campos correspondientes.
5.  Haz clic en el botón azul **Guardar Cambios**.

*(Nota importante: Al guardar tus credenciales en este panel visual, el módulo se encargará de configurar y crear automáticamente la pasarela de pago en todo el sistema central de WHMCS. No necesitas hacer nada más).*

---

## Paso 4: Visualización en el Carrito de Compras

Para asegurarte de que el método "Mercado Pago" aparezca como opción de pago disponible para tus clientes al momento de pagar una factura:

1.  Ve nuevamente a **Ajustes del Sistema** (System Settings) (ícono de la llave inglesa).
2.  Haz clic en **Pasarelas de Pago** (Payment Gateways).
3.  Ve a la pestaña **Gestionar Pasarelas Existentes** (Manage Existing Gateways).
4.  Busca **Mercado Pago (Perú)** en la lista.
5.  **Asegúrate de que la casilla "Mostrar en Formulario de Pedido" (Show on Order Form) esté marcada.**
6.  Haz clic en **Guardar Cambios** (Save Changes).

---

## Paso 5: Prueba de Funcionamiento

Es recomendable realizar una prueba para confirmar que la integración funciona correctamente y que los pagos automatizan tus facturas de WHMCS.

### Prueba de Redireccionamiento:
1.  Como cliente (o creando un cliente de prueba), genera una factura o realiza un nuevo pedido en WHMCS.
2.  En la vista de la factura en el Área del Cliente, asegúrate de que el método de pago seleccionado sea **Mercado Pago (Perú)**.
3.  Debería aparecer un botón azul grande que dice "Pagar Ahora" (Pay Now).
4.  Haz clic en el botón. Esto debería redirigirte a la página segura de cobro de Mercado Pago mostrando el monto correcto y los detalles de la compra.

### Prueba de Notificaciones (Webhooks):
*Para que esta prueba funcione, tu WHMCS debe estar instalado en un dominio público accesible desde internet (no "localhost").*

1.  Procede a pagar esa factura de prueba en la ventana de Mercado Pago usando un método de pago real (luego puedes reembolsarlo desde tu panel de MP) o un usuario de pruebas de Mercado Pago si lo tienes configurado.
2.  Una vez que el pago sea exitoso en Mercado Pago, regresa a tu panel de Administración de WHMCS.
3.  Revisa la factura que acabas de pagar. El estado de la factura debería haber cambiado **automáticamente** de "No Pagada" a **"Pagada"**, sin requerir tu intervención manual.

### Solución de Problemas de Pagos que no se marcan como "Pagados":
Si un cliente te informa que ha pagado pero la factura en WHMCS sigue "No Pagada":
1.  Ve a **Facturación** (Billing) en el menú principal de WHMCS.
2.  Haz clic en **Registro de Pasarela** (Gateway Log).
3.  Revisa los mensajes registrados (Successful, Failed, Error, In Process). Estos registros te indicarán si hubo algún problema con las credenciales, si la notificación fue rechazada, o qué estado reportó Mercado Pago sobre esa transacción específica.
