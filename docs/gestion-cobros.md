# Gestion de cobros

En **Gestion de Cobros → Correccion de Pagos**, los usuarios con perfil `Administrador` pueden buscar cobros de ventas, servicios y amortizaciones de software, corregir moneda, recibido, tipo de cambio, metodo y referencia, e indicar un motivo obligatorio. El control de acceso tambien se aplica en el servidor.

La correccion conserva el cajero y la fecha originales. Registra los valores anteriores y nuevos, el administrador, la fecha y el motivo en `cobros_correcciones`. Las amortizaciones posteriores se recalculan en orden; los pagos anteriores al historial individual se conservan. No se cambia el precio contratado ni se retroceden los estados de trabajo o entrega. Un importe que exceda el precio al sumarse a pagos posteriores se rechaza.

En **Cobro de Ventas** y **Cobro de Servicios** se puede elegir BOB o USD. Caja ingresa el monto en USD y la cotizacion del dia en Bs por USD, con hasta seis decimales. El sistema redondea el equivalente a dos decimales y lo aplica al cobro o amortizacion. La cotizacion es manual; no se consulta un proveedor externo. El cambio se calcula y entrega en Bs. Los pagos electronicos no admiten cambio.

`cobros_divisas` conserva la moneda, el importe original, la cotizacion y su fecha. Las cabeceras, amortizaciones, movimientos y reportes existentes siguen expresados en Bs. Los comprobantes de despacho, servicio y software muestran los datos USD cuando corresponda. Al cerrar caja se suma el equivalente de los dolares segun la cotizacion registrada en cada cobro.

Los cambios de pago y su movimiento vinculado se guardan en una transaccion. Si afectan una caja cerrada, se recalculan el esperado y la diferencia conservando el contado. Los pagos historicos sin movimiento vinculado no generan un ingreso nuevo. El cierre calcula el esperado dentro de su transaccion para evitar utilizar un resumen anterior a un cobro o correccion.

Los cobros se envian por POST con CSRF y una solicitud unica almacenada en `cobros_solicitudes`, para impedir amortizaciones duplicadas al reenviar el formulario. Las correcciones verifican que el registro no haya cambiado desde que se abrio el editor.

Las tres tablas nuevas se crean automaticamente al usar el modulo, siguiendo el mecanismo existente del panel. El usuario MySQL necesita permiso `CREATE TABLE`, como para caja e historial de pagos. No es necesario modificar datos existentes ni ejecutar una migracion manual.

## Validacion local

Con PHP, PDO MySQL, mbstring y una instancia local de MySQL/MariaDB, indicar una base que tenga el esquema actual del panel:

```powershell
$env:COBROS_TEST_SCHEMA = 'bd_techmind'
& C:\xampp\php\php.exe tests/cobros-integracion.php
```

El test copia exclusivamente estructuras a una base temporal `qa_cobros_*`, usa datos sinteticos y elimina esa base al terminar. No modifica la base de referencia. Se pueden definir `DB_HOST`, `DB_PORT`, `DB_USER` y `DB_PASS`. La cuenta de pruebas necesita crear y eliminar bases. `COBROS_TEST_KEEP=1` conserva la base sintetica para revision visual; el test imprime su nombre para poder limpiarla al terminar.
