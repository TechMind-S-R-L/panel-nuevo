<?php
// Copia solo estructuras; todos los datos y cambios viven en una BD temporal.
require_once __DIR__.'/../modelos/conexion.php';
require_once __DIR__.'/../modelos/reportes.modelo.php';

$origen = getenv('COBROS_TEST_SCHEMA');
if(!$origen || !preg_match('/^[a-zA-Z0-9_]+$/D', $origen)){ exit("Defina COBROS_TEST_SCHEMA con el esquema local de referencia.\n"); }
$nombre = 'qa_reportes_'.bin2hex(random_bytes(6));
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$admin = new PDO("mysql:host=$host;port=$port;charset=utf8", getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$checks = 0;
function verificarReporte($valor, $mensaje){
    global $checks;
    if(!$valor){ throw new RuntimeException($mensaje); }
    $checks++;
}
function igualReporte($actual, $esperado, $mensaje){ verificarReporte(abs($actual-$esperado)<0.001, $mensaje." ($actual != $esperado)"); }
function cobrosDia($dia){ global $db; return ModeloReportes::mdlCobrosServicios($db, $dia.' 00:00:00', $dia.' 23:59:59'); }
function totalDia($dia){ global $db; return ModeloReportes::mdlTotalServicios($db, $dia.' 00:00:00', $dia.' 23:59:59'); }
try{
    $admin->exec("CREATE DATABASE `$nombre` CHARACTER SET utf8 COLLATE utf8_spanish_ci");
    foreach(array('usuarios','clientes','servicios_ventas','servicios_pagos','proyectos_software') as $tabla){
        $admin->exec("CREATE TABLE `$nombre`.`$tabla` LIKE `$origen`.`$tabla`");
    }
    putenv('DB_NAME='.$nombre);
    $db = Conexion::conectar();
    $db->exec("INSERT INTO usuarios (id,usuario,nombre,password,perfil,estado,foto,ultimo_login,rol) VALUES
        (1,'qa_caja_1','Caja uno','sin-login','Administrador',1,'','2026-10-12','cajero'),
        (2,'qa_caja_2','Caja dos','sin-login','Especial',1,'','2026-10-12','cajero')");
    $db->exec("INSERT INTO clientes (id,nombre,documento,email,telefono,direccion,fecha_nacimiento,compras,ultima_compra)
        VALUES (1,'Cliente sintetico',0,'','','','2000-01-01',0,'2026-10-12')");
    $db->exec("INSERT INTO servicios_ventas (id,codigo,id_cliente,id_vendedor,id_cajero,tipo_servicio,tipo_instalacion,total,direccion_instalacion,estado_pago,estado_servicio,fecha,fecha_pago,metodo_pago) VALUES
        (1,93001,1,1,2,'Desarrollo de software','',1000,'','adelanto_pagado','en_desarrollo','2026-09-01','2026-10-12 18:00:00','QR'),
        (2,93002,1,1,1,'Instalacion de camaras','',80,'','aprobado','asignado','2026-09-01','2026-10-12 00:00:00','Efectivo'),
        (3,93003,1,1,1,'Soporte tecnico en taller','',90,'','aprobado','pagado_retiro','2026-10-12','2026-10-12','Efectivo'),
        (4,93004,1,1,NULL,'Instalacion de camaras','',999,'','pendiente','pendiente','2026-10-12',NULL,NULL),
        (5,93005,1,1,1,'Desarrollo de software','',500,'','pendiente_final','en_desarrollo','2026-09-01','2026-10-12','QR'),
        (6,93006,1,1,1,'Desarrollo de software','',500,'','pendiente_adelanto','pendiente_adelanto','2026-09-01','2026-10-12','Efectivo')");
    $db->exec("INSERT INTO proyectos_software (id,id_servicio,codigo,nombre_proyecto,tipo_software,precio_total,pago_adelanto,pago_final,fecha_adelanto,fecha_pago_final) VALUES
        (1,1,'QA-1','Con historial','Web',1000,300,220,'2026-09-30','2026-10-12'),
        (2,5,'QA-5','Sin historial','Web',500,100,200,'2026-10-11','2026-10-12'),
        (3,6,'QA-6','Historial parcial','Web',500,180,0,'2026-10-11',NULL)");
    $db->exec("INSERT INTO servicios_pagos (id,id_servicio,id_cajero,tipo_pago,monto,cambio,metodo_pago,fecha) VALUES
        (1,1,1,'adelanto_parcial_software',100,0,'Efectivo','2026-09-30 15:00:00'),
        (2,1,1,'amortizacion_software',70,5,'Efectivo','2026-10-12 08:00:00'),
        (3,1,2,'amortizacion_software',350,0,'QR','2026-10-12 23:59:59'),
        (4,3,1,'servicio',90,0,'Efectivo','2026-10-13 00:00:00'),
        (5,6,1,'adelanto_parcial_software',80,0,'Efectivo','2026-10-12 10:00:00')");

    $hoy = cobrosDia('2026-10-12');
    igualReporte(count($hoy),5,'cada cobro del dia y solo ese dia');
    igualReporte(totalDia('2026-10-12'),780,'amortizaciones, legado y equivalente Bs sin cambio');
    igualReporte(array_sum(array_column($hoy,'monto_cobrado')),totalDia('2026-10-12'),'detalle y tablero coinciden');
    igualReporte(totalDia('2026-10-11'),200,'adelanto antiguo y base previa al historial');
    igualReporte(totalDia('2026-09-30'),100,'pago anterior conserva su fecha');
    igualReporte(totalDia('2026-10-13'),90,'se usa fecha individual, no cabecera');
    igualReporte(totalDia('2026-10-14'),0,'rango sin cobros');
    igualReporte(ModeloReportes::mdlTotalServicios($db,'2026-10-01 00:00:00','2026-10-31 23:59:59'),1070,'mes sin sumar precio completo ni duplicar historicos');
    $pago = array_values(array_filter($hoy,function($r){ return $r['id_pago']==2; }))[0];
    verificarReporte($pago['cajero']==='Caja uno','cajero de cada pago, no ultimo cajero');
    verificarReporte($pago['metodo_cobro']==='Efectivo','metodo individual');
    igualReporte($pago['monto_cobrado'],70,'importe aplicado, excluye cambio');
    verificarReporte($pago['fecha']==='2026-09-01 00:00:00','servicio antiguo con pago actual visible');
    $ranking = ModeloReportes::agruparServicios($hoy);
    igualReporte($ranking[0]['cantidad'],3,'ranking cuenta servicios distintos');
    igualReporte($ranking[0]['cobros'],4,'ranking conserva cantidad de pagos');
    igualReporte($ranking[0]['total'],700,'ranking suma pagos parciales');
    igualReporte(array_sum(array_column($ranking,'total')),780,'ranking coincide con reporte');

    // El cierre de caja no interviene: esta BD ni siquiera tiene tablas de caja.
    verificarReporte(!$db->query("SHOW TABLES LIKE 'caja_aperturas'")->fetch(),'reporte disponible sin caja cerrada');

    // Comprueba tambien la integracion real con la vista y el exportador PDF.
    foreach(array('ventas','compra','proveedor','cotizaciones','productos','categorias','caja_aperturas','caja_movimientos') as $tabla){
        $admin->exec("CREATE TABLE `$nombre`.`$tabla` LIKE `$origen`.`$tabla`");
    }
    $_SESSION = array('perfil'=>'Administrador');
    $_GET = array('fechaInicial'=>'2026-10-12','fechaFinal'=>'2026-10-12');
    ob_start();
    include __DIR__.'/../vistas/modulos/reportes.php';
    $html = ob_get_clean();
    verificarReporte(strpos($html,'5 cobro(s) en el periodo')!==false,'vista muestra todos los pagos');
    verificarReporte(strpos($html,'Total cobrado: Bs 780.00')!==false,'vista muestra importe aplicado');
    verificarReporte(strpos($html,'Pago #2')!==false && strpos($html,'12/10/2026 08:00')!==false,'vista muestra pago individual y fecha');
    $script = '$_GET=array("tipo"=>"servicios","fechaInicial"=>"2026-10-12","fechaFinal"=>"2026-10-12");require '.var_export(realpath(__DIR__.'/../extensiones/tcpdf/pdf/reporte-sistema.php'),true).';';
    $archivoPdf = tempnam(sys_get_temp_dir(),'qa_reportes_');
    file_put_contents($archivoPdf,'<?php '.$script);
    $proceso = proc_open(escapeshellarg(PHP_BINARY).' '.escapeshellarg($archivoPdf),array(1=>array('pipe','w'),2=>array('pipe','w')),$pipes,null,null,array('bypass_shell'=>true));
    if(!is_resource($proceso)){ throw new RuntimeException('No se pudo iniciar el exportador PDF.'); }
    $pdf = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errores = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $estadoPdf = proc_close($proceso);
    unlink($archivoPdf);
    verificarReporte($estadoPdf===0 && strpos($pdf,'%PDF-')===0 && $errores==='','exportador genera PDF sin errores: '.$errores);
    if(getenv('REPORTES_TEST_PDF')){ file_put_contents(getenv('REPORTES_TEST_PDF'),$pdf); }

    $db->exec("INSERT INTO servicios_pagos (id,id_servicio,id_cajero,tipo_pago,monto,metodo_pago,fecha)
        VALUES (6,1,2,'pago_final_software',480,'QR','2026-10-13 12:00:00')");
    $db->exec("UPDATE proyectos_software SET pago_final=700,fecha_pago_final='2026-10-13 12:00:00' WHERE id=1");
    $db->exec("UPDATE servicios_ventas SET estado_pago='aprobado',fecha_pago='2026-10-13 12:00:00' WHERE id=1");
    igualReporte(totalDia('2026-10-12'),780,'liquidar no mueve ni duplica amortizaciones anteriores');
    igualReporte(totalDia('2026-10-13'),570,'liquidacion suma solo pago final');
    $db->exec("UPDATE servicios_pagos SET monto=60 WHERE id=2");
    $db->exec("UPDATE proyectos_software SET pago_final=690 WHERE id=1");
    $db->exec("UPDATE servicios_ventas SET estado_pago='pendiente_final' WHERE id=1");
    igualReporte(totalDia('2026-10-12'),770,'correccion administrativa reflejada en reporte');
    igualReporte(totalDia('2026-10-13'),570,'correccion conserva fecha e importe de otros pagos');

    // Instalaciones anteriores a la tabla de historial se pueden consultar sin crear tablas.
    $db->exec('DROP TABLE servicios_pagos');
    igualReporte(totalDia('2026-10-12'),370,'compatibilidad sin tabla de historial usa fechas de cabeceras');
    verificarReporte(!$db->query("SHOW TABLES LIKE 'servicios_pagos'")->fetch(),'consulta no altera esquema');
    echo "OK: $checks verificaciones de reportes, fechas, amortizaciones e importes historicos.\n";
}finally{
    $admin->exec("DROP DATABASE IF EXISTS `$nombre`");
}
