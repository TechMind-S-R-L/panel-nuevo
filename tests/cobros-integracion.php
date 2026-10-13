<?php
// Ejecutar con COBROS_TEST_SCHEMA indicando una BD local con el esquema de TechMind.
// Solo copia estructuras. Los datos sinteticos viven en una BD temporal independiente.
require_once __DIR__.'/../modelos/caja.modelo.php';
require_once __DIR__.'/../modelos/servicios.modelo.php';
require_once __DIR__.'/../modelos/cobros.modelo.php';
require_once __DIR__.'/../controladores/cobros.controlador.php';
require_once __DIR__.'/../extensiones/tcpdf/pdf/pdf-cobro-divisa.php';

$origen = getenv('COBROS_TEST_SCHEMA');
if(!$origen || !preg_match('/^[a-zA-Z0-9_]+$/D',$origen)){ exit("Defina COBROS_TEST_SCHEMA con el esquema local de referencia.\n"); }
$nombre = 'qa_cobros_'.bin2hex(random_bytes(6));
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$admin = new PDO("mysql:host=$host;port=$port;charset=utf8",getenv('DB_USER') ?: 'root',getenv('DB_PASS') ?: '',array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$checks=0;
function verificar($condicion,$mensaje){ global $checks; if(!$condicion){ throw new RuntimeException($mensaje); } $checks++; }
function igual($actual,$esperado,$mensaje){ verificar(abs((float)$actual-(float)$esperado)<0.001,$mensaje.' ('.$actual.' != '.$esperado.')'); }
function rechaza($fn,$mensaje){ try{ $fn(); }catch(DomainException|InvalidArgumentException $e){ verificar(true,$mensaje);return; }throw new RuntimeException('No rechazo: '.$mensaje); }
function dato($sql){ global $db; return $db->query($sql)->fetchColumn(); }
function cobro($id,$original,$tasa=1,$moneda='BOB'){
    return array('id'=>$id,'id_usuario'=>1,'id_apertura'=>1,'metodo_pago'=>'Efectivo','monto_original'=>$original,'tipo_cambio'=>$tasa,'moneda'=>$moneda,'fecha_cambio'=>'2026-10-12','codigo_transaccion'=>'TEST','solicitud'=>bin2hex(random_bytes(32)));
}
function corregir($tipo,$id,$original,$tasa=1,$moneda='BOB',$metodo='Efectivo'){
    $p=ModeloCobros::mdlObtener($tipo,$id);
    return array_merge(cobro($id,$original,$tasa,$moneda),array('referencia_tipo'=>$tipo,'id_referencia'=>$id,'version'=>ModeloCobros::version($p),'motivo'=>'Correccion de prueba controlada','metodo_pago'=>$metodo));
}
try{
    $admin->exec("CREATE DATABASE `$nombre` CHARACTER SET utf8 COLLATE utf8_spanish_ci");
    foreach(array('usuarios','clientes','ventas','servicios_ventas','servicios_pagos','proyectos_software','servicio_taller_equipos','servicio_taller_repuestos','productos') as $tabla){ $admin->exec("CREATE TABLE `$nombre`.`$tabla` LIKE `$origen`.`$tabla`"); }
    putenv('DB_NAME='.$nombre);
    $db=Conexion::conectar();
    $db->exec("INSERT INTO usuarios (id,usuario,nombre,password,perfil,estado,foto,ultimo_login,rol) VALUES
        (1,'qa_caja','Caja prueba','sin-login','Administrador',1,'','2026-10-12','cajero'),
        (2,'qa_dev','Desarrollador prueba','sin-login','Especial',1,'','2026-10-12','desarrollador'),
        (3,'qa_tecnico','Tecnico prueba','sin-login','Especial',1,'','2026-10-12','tecnico')");
    $db->exec("INSERT INTO clientes (id,nombre,documento,email,telefono,direccion,fecha_nacimiento,compras,ultima_compra) VALUES (1,'Cliente sintetico',0,'','','','2000-01-01',0,'2026-10-12')");
    $db->exec("INSERT INTO servicios_ventas (id,codigo,id_cliente,id_vendedor,tipo_servicio,tipo_instalacion,total,direccion_instalacion,estado_pago,estado_servicio,costo_mano_obra) VALUES
        (1,91001,1,1,'Desarrollo de software','',1000,'','pendiente_adelanto','pendiente_adelanto',0),
        (2,91002,1,1,'Instalacion de camaras','',80,'','pendiente','asignado',0),
        (3,91003,1,1,'Soporte tecnico en taller','',120,'','pendiente_retiro','listo_cobro',20),
        (4,91004,1,1,'Instalacion de camaras','',55,'','aprobado','asignado',0)");
    $db->exec("UPDATE servicios_ventas SET metodo_pago='Efectivo',monto_recibido=55,cambio=0,fecha_pago=NOW() WHERE id=4");
    $db->exec("INSERT INTO proyectos_software (id,id_servicio,codigo,nombre_proyecto,tipo_software,precio_total,monto_adelanto,saldo_pendiente) VALUES (1,1,'QA-1','Proyecto sintetico','Web',1000,300,1000)");
    $db->exec("INSERT INTO ventas (id,codigo,id_cliente,id_vendedor,productos,descuento,neto,total,metodo_pago) VALUES (1,92001,1,1,'[]',0,100,100,'Efectivo')");
    ModeloCobros::mdlPreparar();
    $db->exec("INSERT INTO caja_aperturas (id,id_cajero,monto_inicial) VALUES (1,1,50)");

    foreach(array(0,-1,'NaN','INF','abc') as $invalido){ rechaza(function()use($invalido){ModeloCobros::normalizarImporte(cobro(1,$invalido));},'importe invalido'); }
    rechaza(function(){ModeloCobros::normalizarImporte(cobro(1,10,0,'USD'));},'tasa cero');
    rechaza(function(){ModeloCobros::normalizarImporte(array_merge(cobro(1,10),array('fecha_cambio'=>'2026-02-30')));},'fecha invalida');
    rechaza(function(){ModeloCobros::normalizarImporte(cobro(1,99999999,100,'USD'));},'fuera de rango');
    $d=cobro(1,10,7,'USD');
    $r=ModeloCobros::mdlCobrar('servicio',$d);
    igual($r['monto'],70,'conversion a Bs');igual($r['saldo'],930,'saldo parcial');
    igual(dato('SELECT pago_adelanto FROM proyectos_software WHERE id=1'),70,'adelanto aplicado');
    verificar(dato('SELECT id_desarrollador FROM proyectos_software WHERE id=1')===null,'sin asignacion antes del adelanto');
    $repetido=ModeloCobros::mdlCobrar('servicio',$d);
    igual($repetido['id_pago'],$r['id_pago'],'reintento mismo comprobante');igual(dato('SELECT COUNT(*) FROM servicios_pagos'),1,'sin doble amortizacion');
    $r2=ModeloCobros::mdlCobrar('servicio',cobro(1,50,7,'USD'));
    igual(dato('SELECT pago_adelanto FROM proyectos_software WHERE id=1'),300,'adelanto completado');
    igual(dato('SELECT pago_final FROM proyectos_software WHERE id=1'),120,'excedente en amortizacion');
    igual(dato('SELECT id_desarrollador FROM proyectos_software WHERE id=1'),2,'asignacion desarrollador');
    $db->exec("UPDATE caja_aperturas SET estado='cerrada',fecha_cierre=NOW(),monto_esperado_cierre=470,monto_contado_cierre=470,diferencia=0 WHERE id=1");
    $c=corregir('pago_servicio',$r['id_pago'],20,7,'USD');
    ModeloCobros::mdlCorregir($c);
    igual(dato('SELECT saldo_pendiente FROM proyectos_software WHERE id=1'),510,'saldo corregido');
    igual(dato('SELECT saldo_antes FROM servicios_pagos WHERE id='.$r2['id_pago']),860,'saldo antes pago posterior');
    igual(dato('SELECT saldo_despues FROM servicios_pagos WHERE id='.$r2['id_pago']),510,'saldo despues pago posterior');
    igual(dato('SELECT monto_recibido FROM servicios_ventas WHERE id=1'),350,'cabecera conserva ultimo pago');
    igual(dato('SELECT monto_esperado_cierre FROM caja_aperturas WHERE id=1'),540,'recalculo caja cerrada');
    igual(dato('SELECT monto_contado_cierre FROM caja_aperturas WHERE id=1'),470,'conserva contado');
    igual(dato('SELECT diferencia FROM caja_aperturas WHERE id=1'),-70,'diferencia recalculada');
    rechaza(function()use($c){ModeloCobros::mdlCorregir($c);},'edicion obsoleta');
    igual(dato('SELECT COUNT(*) FROM cobros_correcciones'),1,'auditoria unica');
    ModeloCobros::mdlCorregir(corregir('pago_servicio',$r['id_pago'],20,7,'USD','QR'));
    igual(dato('SELECT monto_esperado_cierre FROM caja_aperturas WHERE id=1'),400,'cambio efectivo a QR');
    verificar(strpos(tmPdfCobroDivisa('pago_servicio',$r['id_pago']),'USD 20.00')!==false,'comprobante con divisa');
    $db->exec("UPDATE caja_aperturas SET estado='abierta' WHERE id=1");
    rechaza(function(){ModeloCobros::mdlCobrar('venta',cobro(1,10,7,'USD'));},'venta insuficiente');
    verificar(dato('SELECT estado_pago FROM ventas WHERE id=1')==='pendiente','rechazo no modifica venta');
    ModeloCobros::mdlCobrar('venta',cobro(1,15,7,'USD'));
    igual(dato('SELECT cambio FROM ventas WHERE id=1'),5,'cambio en Bs');
    rechaza(function(){ModeloCobros::mdlCobrar('venta',cobro(1,100));},'venta ya cobrada');
    ModeloCobros::mdlCorregir(corregir('venta',1,100,1,'BOB','QR'));
    igual(dato("SELECT afecta_efectivo FROM caja_movimientos WHERE referencia_tipo='venta'"),0,'corrige metodo caja venta');
    ModeloCobros::mdlCobrar('servicio',cobro(2,80));
    $t=cobro(3,10);$t['mano_obra']=50;
    rechaza(function()use($t){ModeloCobros::mdlCobrar('servicio',$t);},'taller insuficiente');
    igual(dato('SELECT costo_mano_obra FROM servicios_ventas WHERE id=3'),20,'rollback costos taller');
    $t['monto_original']=150;ModeloCobros::mdlCobrar('servicio',$t);
    igual(dato('SELECT total FROM servicios_ventas WHERE id=3'),150,'total taller');
    verificar(dato('SELECT estado_servicio FROM servicios_ventas WHERE id=3')==='pagado_retiro','flujo taller');
    ModeloCobros::mdlCorregir(corregir('servicio',4,60));
    igual(dato('SELECT cambio FROM servicios_ventas WHERE id=4'),5,'pago historico sin registro individual');
    $rechazo=corregir('pago_servicio',$r['id_pago'],950);
    rechaza(function()use($rechazo){ModeloCobros::mdlCorregir($rechazo);},'sobrepago con pagos posteriores');
    igual(dato('SELECT saldo_pendiente FROM proyectos_software WHERE id=1'),510,'rollback correccion completa');
    $final=ModeloCobros::mdlCobrar('servicio',cobro(1,515));
    igual($final['saldo'],0,'liquidacion');igual(dato('SELECT cambio FROM servicios_pagos WHERE id='.$final['id_pago']),5,'cambio final');
    verificar(dato('SELECT estado_pago FROM servicios_ventas WHERE id=1')==='aprobado','proyecto pagado');
    $db->exec("UPDATE proyectos_software SET estado='completado' WHERE id=1");
    ModeloCobros::mdlCorregir(corregir('pago_servicio',$final['id_pago'],500));
    verificar(dato('SELECT estado FROM proyectos_software WHERE id=1')==='completado','conserva entrega');
    igual(dato('SELECT saldo_pendiente FROM proyectos_software WHERE id=1'),10,'saldo reabierto');
    $lista=ModeloCobros::mdlListar('Cliente sintetico');verificar(count($lista)>=6,'busqueda unificada');
    verificar(count(ModeloCobros::mdlHistorial('pago_servicio',$r['id_pago']))===2,'historial administrador');
    $esperadoReal = (float)ModeloCaja::mdlResumenApertura(1)['efectivo_esperado'];
    $cerrado = ModeloCaja::mdlCerrarCaja(array('id'=>1,'id_cajero'=>1,'esperado'=>1,'contado'=>$esperadoReal,'diferencia'=>999,'observacion'=>'Prueba de cierre con pantalla obsoleta'));
    verificar($cerrado==='ok','cierre registrado');
    igual(dato('SELECT monto_esperado_cierre FROM caja_aperturas WHERE id=1'),$esperadoReal,'cierre usa movimientos actuales');
    igual(dato('SELECT diferencia FROM caja_aperturas WHERE id=1'),0,'diferencia cierre actual');
    $_SESSION=array('id'=>1,'perfil'=>'Especial','rol'=>'cajero','csrf_cobros'=>'qa');
    $_POST=array_merge(corregir('venta',1,100),array('corregirCobro'=>1,'csrf_cobros'=>'qa'));
    ob_start();ControladorCobros::ctrCorregir();$salida=ob_get_clean();verificar(strpos($salida,'permiso')!==false,'cajero no puede corregir');
    $_SESSION['perfil']='Administrador';$_POST['csrf_cobros']='incorrecto';
    ob_start();ControladorCobros::ctrCorregir();$salida=ob_get_clean();verificar(strpos($salida,'vencio')!==false,'csrf requerido');
    echo "OK: $checks verificaciones de cobros, conversiones, amortizaciones, correcciones y permisos.\n";
}finally{
    // Nombre generado exclusivamente por este proceso; nunca usa el nombre de origen.
    if(getenv('COBROS_TEST_KEEP') === '1'){ echo 'BD sintetica para revision: '.$nombre."\n"; }
    else{ $admin->exec("DROP DATABASE IF EXISTS `$nombre`"); }
}
