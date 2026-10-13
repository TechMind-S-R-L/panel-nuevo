<?php
require_once __DIR__."/conexion.php";

class ModeloCobros {
    static public function mdlPreparar(){
        static $preparado = false;
        if($preparado){ return; }
        ModeloCaja::mdlAsegurarTablas();
        ModeloServicios::mdlMostrarPagosServicio(0);
        $db = Conexion::conectar();
        $db->exec("CREATE TABLE IF NOT EXISTS cobros_divisas (
            referencia_tipo VARCHAR(40) NOT NULL, id_referencia BIGINT NOT NULL,
            moneda VARCHAR(3) NOT NULL DEFAULT 'BOB', monto_original DECIMAL(12,2) NOT NULL,
            tipo_cambio DECIMAL(14,6) NOT NULL DEFAULT 1, fecha_cambio DATE NOT NULL,
            PRIMARY KEY (referencia_tipo, id_referencia)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $db->exec("CREATE TABLE IF NOT EXISTS cobros_correcciones (
            id BIGINT AUTO_INCREMENT PRIMARY KEY, referencia_tipo VARCHAR(40) NOT NULL,
            id_referencia BIGINT NOT NULL, id_administrador INT NOT NULL,
            motivo VARCHAR(500) NOT NULL, antes LONGTEXT NOT NULL, despues LONGTEXT NOT NULL,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cobro_correccion (referencia_tipo, id_referencia)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $db->exec("CREATE TABLE IF NOT EXISTS cobros_solicitudes (
            token CHAR(64) PRIMARY KEY, firma CHAR(64) NOT NULL, resultado LONGTEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $preparado = true;
    }

    static private function consulta($db, $sql, $params = array()){
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    static public function normalizarImporte($datos){
        $moneda = $datos['moneda'] ?? 'BOB';
        $metodo = $datos['metodo_pago'] ?? 'Efectivo';
        if(!in_array($moneda, array('BOB','USD'), true) || !in_array($metodo, array('Efectivo','QR','Tarjeta Credito','Tarjeta Debito'), true)){
            throw new InvalidArgumentException('Seleccione una moneda y un metodo validos.');
        }
        $original = $datos['monto_original'] ?? null;
        $tasa = $moneda === 'USD' ? ($datos['tipo_cambio'] ?? null) : 1;
        if(!is_numeric($original) || !is_numeric($tasa) || !is_finite((float)$original) || !is_finite((float)$tasa)){
            throw new InvalidArgumentException('Ingrese el monto recibido y el tipo de cambio.');
        }
        $original = round((float)$original, 2);
        $tasa = round((float)$tasa, 6);
        $recibido = round($original * $tasa, 2);
        if($original <= 0 || $tasa <= 0 || $original > 99999999.99 || $tasa > 99999999.999999 || $recibido <= 0 || $recibido > 99999999.99){
            throw new InvalidArgumentException('El monto y el tipo de cambio deben ser positivos y estar dentro del limite permitido.');
        }
        $fecha = $datos['fecha_cambio'] ?? (new DateTimeImmutable('now', new DateTimeZone('America/La_Paz')))->format('Y-m-d');
        $dia = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$fecha);
        if(!$dia || $dia->format('Y-m-d') !== $fecha){
            throw new InvalidArgumentException('La fecha del tipo de cambio no es valida.');
        }
        $codigo = trim((string)($datos['codigo_transaccion'] ?? ''));
        if(mb_strlen($codigo) > 100){
            throw new InvalidArgumentException('La referencia admite hasta 100 caracteres.');
        }
        return array('moneda'=>$moneda, 'monto_original'=>$original, 'tipo_cambio'=>$tasa,
            'fecha_cambio'=>$fecha, 'monto_recibido'=>$recibido, 'metodo_pago'=>$metodo, 'codigo_transaccion'=>$codigo);
    }

    static private function divisa($db, $tipo, $id, $pago){
        self::consulta($db, "INSERT INTO cobros_divisas (referencia_tipo,id_referencia,moneda,monto_original,tipo_cambio,fecha_cambio)
            VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE moneda=VALUES(moneda), monto_original=VALUES(monto_original),
            tipo_cambio=VALUES(tipo_cambio), fecha_cambio=VALUES(fecha_cambio)",
            array($tipo,$id,$pago['moneda'],$pago['monto_original'],$pago['tipo_cambio'],$pago['fecha_cambio']));
    }

    static private function responsable($db, $rol, $actual = null){
        if($actual){ return (int)$actual; }
        $sql = $rol === 'desarrollador'
            ? "SELECT u.id FROM usuarios u LEFT JOIN proyectos_software p ON p.id_desarrollador=u.id AND p.estado IN ('en_desarrollo','revision_interna','revision_cliente','pendiente_pago_final') WHERE u.rol='desarrollador' AND u.estado=1 GROUP BY u.id ORDER BY COUNT(p.id),u.id LIMIT 1"
            : "SELECT u.id FROM usuarios u LEFT JOIN servicios_ventas s ON s.id_tecnico=u.id AND s.estado_servicio IN ('asignado','en_almacen','atendiendo','en_proceso','diagnosticado','autorizado','rep_solicitado','rep_entregado','reparado') WHERE u.rol='tecnico' AND u.estado=1 GROUP BY u.id ORDER BY COUNT(s.id),u.id LIMIT 1";
        $id = self::consulta($db,$sql)->fetchColumn();
        if(!$id){ throw new DomainException('No hay un '.$rol.' activo para asignar el servicio.'); }
        return (int)$id;
    }

    // Recalcula finanzas preservando los estados de trabajo y entrega ya alcanzados.
    static private function proyecto($db, $p, $pagado){
        $total = round((float)$p['precio_total'],2);
        $adelanto = min($pagado, (float)$p['monto_adelanto']);
        $final = round($pagado-$adelanto,2);
        $saldo = round($total-$pagado,2);
        $completo = $saldo <= 0;
        $adelantoCompleto = $adelanto >= (float)$p['monto_adelanto'];
        $responsable = $p['id_desarrollador'] ?: null;
        if($adelantoCompleto){ $responsable = self::responsable($db,'desarrollador',$responsable); }
        $estado = $p['estado'];
        $entregado = in_array($estado,array('entregado','completado'),true);
        if($completo && !$entregado){
            $estado = 'pagado_final';
        }elseif(!$completo && $estado === 'pagado_final'){
            $estado = 'pendiente_pago_final';
        }elseif($adelantoCompleto && $estado === 'pendiente_adelanto'){
            $estado = 'en_desarrollo';
        }
        $estadoPago = $completo ? 'aprobado' : ($adelantoCompleto ? (($estado === 'pendiente_pago_final' || $entregado) ? 'pendiente_final' : 'adelanto_pagado') : 'pendiente_adelanto');
        self::consulta($db,"UPDATE proyectos_software SET pago_adelanto=?,pago_final=?,saldo_pendiente=?,id_desarrollador=?,estado=?,
            fecha_adelanto=CASE WHEN ? > 0 THEN COALESCE(fecha_adelanto,NOW()) ELSE NULL END,
            fecha_pago_final=CASE WHEN ?=1 THEN COALESCE(fecha_pago_final,NOW()) ELSE NULL END,
            fecha_inicio=CASE WHEN ?=1 THEN COALESCE(fecha_inicio,CURDATE()) ELSE fecha_inicio END WHERE id=?",
            array($adelanto,$final,$saldo,$responsable,$estado,$adelanto,$completo?1:0,$adelantoCompleto?1:0,$p['id']));
        self::consulta($db,"UPDATE servicios_ventas SET estado_pago=?,estado_servicio=?,id_tecnico=COALESCE(?,id_tecnico) WHERE id=?",
            array($estadoPago,$estado,$responsable,$p['id_servicio']));
        return $completo ? 'pago_final_software' : ($adelantoCompleto ? 'amortizacion_software' : 'adelanto_parcial_software');
    }

    static public function mdlCobrar($tipo, $datos){
        if(!in_array($tipo,array('venta','servicio'),true)){ throw new DomainException('Tipo de cobro no valido.'); }
        self::mdlPreparar();
        $pago = self::normalizarImporte($datos);
        $token = (string)($datos['solicitud'] ?? '');
        if(!preg_match('/^[a-f0-9]{64}$/D',$token)){ throw new DomainException('Recargue el formulario antes de cobrar.'); }
        $firma = hash('sha256',json_encode(array($tipo,(int)$datos['id'],(int)$datos['id_usuario'],(int)$datos['id_apertura'],$pago,$datos['mano_obra'] ?? 0)));
        $db = Conexion::conectar();
        try{
            $db->beginTransaction();
            $caja = self::consulta($db,"SELECT * FROM caja_aperturas WHERE id=? AND id_cajero=? AND estado='abierta' FOR UPDATE",array($datos['id_apertura'],$datos['id_usuario']))->fetch();
            if(!$caja){ throw new DomainException('Debe abrir su caja antes de cobrar.'); }
            $previa = self::consulta($db,'SELECT * FROM cobros_solicitudes WHERE token=?',array($token))->fetch();
            if($previa){
                if(!hash_equals($previa['firma'],$firma)){ throw new DomainException('Este formulario ya se uso para otro pago. Recargue la pagina.'); }
                $db->rollBack();
                return array_merge(json_decode($previa['resultado'],true),array('repetido'=>true));
            }
            $tabla = $tipo === 'venta' ? 'ventas' : 'servicios_ventas';
            $s = self::consulta($db,"SELECT * FROM $tabla WHERE id=? FOR UPDATE",array($datos['id']))->fetch();
            if(!$s){ throw new DomainException('El documento no existe.'); }
            $software = $tipo === 'servicio' && $s['tipo_servicio'] === 'Desarrollo de software';
            $taller = $tipo === 'servicio' && $s['tipo_servicio'] === 'Soporte tecnico en taller';
            $estados = $software ? array('pendiente_adelanto','adelanto_pagado','pendiente_final') : ($taller ? array('pendiente_retiro') : array('pendiente'));
            if(!in_array($s['estado_pago'],$estados,true) || ($taller && $s['estado_servicio'] !== 'listo_cobro')){
                throw new DomainException('Este documento ya fue cobrado o no esta listo para cobrar.');
            }
            $total = round((float)$s['total'],2);
            $p = null;
            $pagado = 0;
            if($software){
                $p = self::consulta($db,"SELECT * FROM proyectos_software WHERE id_servicio=? FOR UPDATE",array($s['id']))->fetch();
                if(!$p){ throw new DomainException('No se encontro el proyecto de software.'); }
                $pagado = round((float)$p['pago_adelanto']+(float)$p['pago_final'],2);
                $total = round((float)$p['precio_total']-$pagado,2);
            }
            if($taller){
                $mano = $datos['mano_obra'] ?? 0;
                if(!is_numeric($mano) || !is_finite((float)$mano) || $mano < 0){ throw new DomainException('La mano de obra no es valida.'); }
                $mano = round((float)$mano,2);
                $total = round(max(0,$total-(float)$s['costo_mano_obra'])+$mano,2);
            }
            if($total <= 0 || $total > 99999999.99 || (!$software && $pago['monto_recibido'] < $total)){
                throw new DomainException('El monto recibido no cubre el total o no hay saldo pendiente.');
            }
            $aplicado = min($pago['monto_recibido'],$total);
            $cambio = round($pago['monto_recibido']-$aplicado,2);
            if($pago['metodo_pago'] !== 'Efectivo' && $cambio > 0){ throw new DomainException('El pago electronico no puede superar el saldo pendiente.'); }
            $referenciaTipo = 'venta';
            $referenciaId = (int)$s['id'];
            $tipoPago = 'servicio';
            if($tipo === 'servicio'){
                $responsable = $software ? null : self::responsable($db,'tecnico',$s['id_tecnico'] ?? null);
                if($software){ $tipoPago = self::proyecto($db,$p,round($pagado+$aplicado,2)); }
                else{
                    self::consulta($db,"UPDATE servicios_ventas SET estado_pago='aprobado',estado_servicio=?,id_tecnico=? WHERE id=?",
                        array($taller?'pagado_retiro':'asignado',$responsable,$s['id']));
                }
                if($taller){ self::consulta($db,"UPDATE servicios_ventas SET costo_mano_obra=?,total=? WHERE id=?",array($mano,$total,$s['id'])); }
                self::consulta($db,"INSERT INTO servicios_pagos (id_servicio,id_cajero,tipo_pago,monto,metodo_pago,cambio,codigo_transaccion,saldo_antes,saldo_despues) VALUES (?,?,?,?,?,?,?,?,?)",
                    array($s['id'],$datos['id_usuario'],$tipoPago,$aplicado,$pago['metodo_pago'],$cambio,$pago['codigo_transaccion'],$total,round($total-$aplicado,2)));
                $referenciaId = (int)$db->lastInsertId();
                $referenciaTipo = 'pago_servicio';
            }
            self::consulta($db,"UPDATE $tabla SET metodo_pago=?,monto_recibido=?,cambio=?,codigo_transaccion=?,id_cajero=?,fecha_pago=NOW()".($tipo==='venta'?",estado_pago='aprobado'":"")." WHERE id=?",
                array($pago['metodo_pago'],$pago['monto_recibido'],$cambio,$pago['codigo_transaccion'],$datos['id_usuario'],$s['id']));
            $descripcion = 'Cobro de '.$tipo.' '.$s['codigo'];
            if($pago['moneda'] === 'USD'){ $descripcion .= ' | USD '.number_format($pago['monto_original'],2,'.','').' x '.$pago['tipo_cambio'].' Bs/USD ('.$pago['fecha_cambio'].')'; }
            self::consulta($db,"INSERT INTO caja_movimientos (id_apertura,id_usuario,tipo,origen,referencia_tipo,id_referencia,codigo_referencia,metodo_pago,monto,afecta_efectivo,descripcion) VALUES (?,?,'ingreso',?,?,?,?,?,?,?,?)",
                array($caja['id'],$datos['id_usuario'],$tipo,$referenciaTipo,$referenciaId,$s['codigo'],$pago['metodo_pago'],$aplicado,$pago['metodo_pago']==='Efectivo'?1:0,$descripcion));
            self::divisa($db,$referenciaTipo,$referenciaId,$pago);
            $resultado = array('id'=>$s['id'],'id_pago'=>$tipo==='servicio'?$referenciaId:null,'software'=>$software,'taller'=>$taller,'monto'=>$aplicado,'saldo'=>round($total-$aplicado,2));
            self::consulta($db,'INSERT INTO cobros_solicitudes (token,firma,resultado) VALUES (?,?,?)',array($token,$firma,json_encode($resultado)));
            $db->commit();
            return $resultado;
        }catch(Throwable $e){
            if($db->inTransaction()){ $db->rollBack(); }
            throw $e;
        }
    }

    static public function mdlListar($busqueda = ''){
        self::mdlPreparar();
        $sql = "SELECT x.*,c.nombre AS cliente,u.nombre AS cajero,d.moneda,d.monto_original,d.tipo_cambio,d.fecha_cambio FROM (
            SELECT 'venta' AS referencia_tipo,v.id AS id_referencia,v.id AS id_documento,v.codigo,v.id_cliente,v.id_cajero,v.total AS monto,v.monto_recibido,v.cambio,v.metodo_pago,v.codigo_transaccion,v.fecha_pago AS fecha FROM ventas v WHERE v.estado_pago='aprobado'
            UNION ALL
            SELECT 'pago_servicio',p.id,s.id,s.codigo,s.id_cliente,p.id_cajero,p.monto,p.monto+p.cambio,p.cambio,p.metodo_pago,p.codigo_transaccion,p.fecha FROM servicios_pagos p JOIN servicios_ventas s ON s.id=p.id_servicio
            UNION ALL
            SELECT 'servicio',s.id,s.id,s.codigo,s.id_cliente,s.id_cajero,s.total,s.monto_recibido,s.cambio,s.metodo_pago,s.codigo_transaccion,s.fecha_pago FROM servicios_ventas s WHERE s.estado_pago='aprobado' AND s.tipo_servicio<>'Desarrollo de software' AND NOT EXISTS (SELECT 1 FROM servicios_pagos p WHERE p.id_servicio=s.id)
        ) x LEFT JOIN clientes c ON c.id=x.id_cliente LEFT JOIN usuarios u ON u.id=x.id_cajero
        LEFT JOIN cobros_divisas d ON d.referencia_tipo=x.referencia_tipo AND d.id_referencia=x.id_referencia
        WHERE (x.codigo LIKE ? OR c.nombre LIKE ? OR u.nombre LIKE ?) ORDER BY x.fecha DESC,x.id_referencia DESC LIMIT 200";
        $like = '%'.trim($busqueda).'%';
        return self::consulta(Conexion::conectar(),$sql,array($like,$like,$like))->fetchAll();
    }

    static private function obtener($db,$tipo,$id,$bloquear = false){
        $tablas = array('venta'=>'ventas','pago_servicio'=>'servicios_pagos','servicio'=>'servicios_ventas');
        if(!isset($tablas[$tipo])){ throw new DomainException('Tipo de pago no valido.'); }
        $pago = self::consulta($db,'SELECT * FROM '.$tablas[$tipo].' WHERE id=?'.($bloquear?' FOR UPDATE':''),array($id))->fetch();
        if(!$pago){ throw new DomainException('Pago no encontrado.'); }
        if($tipo !== 'pago_servicio' && $pago['estado_pago'] !== 'aprobado'){ throw new DomainException('El documento no tiene un pago aprobado.'); }
        if($tipo === 'servicio' && ($pago['tipo_servicio'] === 'Desarrollo de software' || self::consulta($db,'SELECT id FROM servicios_pagos WHERE id_servicio=? LIMIT 1',array($id))->fetch())){
            throw new DomainException('Seleccione el pago individual del servicio.');
        }
        $pago['divisa'] = self::consulta($db,'SELECT * FROM cobros_divisas WHERE referencia_tipo=? AND id_referencia=?',array($tipo,$id))->fetch() ?: null;
        return $pago;
    }

    static public function mdlObtener($tipo,$id){
        self::mdlPreparar();
        return self::obtener(Conexion::conectar(),$tipo,$id);
    }

    static public function version($pago){ return hash('sha256',json_encode($pago,JSON_UNESCAPED_UNICODE)); }

    static public function mdlCorregir($datos){
        self::mdlPreparar();
        $nuevo = self::normalizarImporte($datos);
        $motivo = trim((string)($datos['motivo'] ?? ''));
        if(mb_strlen($motivo) < 5 || mb_strlen($motivo) > 500){ throw new DomainException('Explique el motivo de la correccion (5 a 500 caracteres).'); }
        $db = Conexion::conectar();
        $tipo = $datos['referencia_tipo'];
        $id = (int)$datos['id_referencia'];
        try{
            $db->beginTransaction();
            // Caja primero: mismo orden de bloqueo que los cobros y cierres.
            $mov = self::consulta($db,'SELECT * FROM caja_movimientos WHERE referencia_tipo=? AND id_referencia=? AND tipo=\'ingreso\' AND origen IN (\'venta\',\'servicio\')',array($tipo,$id))->fetchAll();
            $cajas = array_unique(array_column($mov,'id_apertura')); sort($cajas);
            foreach($cajas as $caja){ self::consulta($db,'SELECT id FROM caja_aperturas WHERE id=? FOR UPDATE',array($caja))->fetch(); }
            $pre = self::obtener($db,$tipo,$id);
            $idDocumento = $tipo === 'pago_servicio' ? $pre['id_servicio'] : $id;
            $tabla = $tipo === 'venta' ? 'ventas' : 'servicios_ventas';
            $s = self::consulta($db,"SELECT * FROM $tabla WHERE id=? FOR UPDATE",array($idDocumento))->fetch();
            if(!$s){ throw new DomainException('Documento no encontrado.'); }
            $software = $tipo !== 'venta' && $s['tipo_servicio'] === 'Desarrollo de software';
            $p = $software ? self::consulta($db,'SELECT * FROM proyectos_software WHERE id_servicio=? FOR UPDATE',array($idDocumento))->fetch() : null;
            if($software && !$p){ throw new DomainException('Proyecto no encontrado.'); }
            $antes = self::obtener($db,$tipo,$id,true);
            if(!hash_equals(self::version($antes),(string)($datos['version'] ?? ''))){ throw new DomainException('Otro usuario modifico este pago. Recargue los datos antes de corregirlo.'); }
            $saldoAntes = $software ? round((float)$antes['saldo_antes'],2) : round((float)$s['total'],2);
            $monto = min($nuevo['monto_recibido'],$saldoAntes);
            if(!$software && $nuevo['monto_recibido'] < $saldoAntes){ throw new DomainException('Este cobro debe cubrir el total del documento.'); }
            $cambio = round($nuevo['monto_recibido']-$monto,2);
            if($nuevo['metodo_pago'] !== 'Efectivo' && $cambio > 0){ throw new DomainException('El pago electronico no puede superar el saldo pendiente.'); }
            if($tipo === 'pago_servicio'){
                self::consulta($db,'UPDATE servicios_pagos SET monto=?,metodo_pago=?,cambio=?,codigo_transaccion=? WHERE id=?',array($monto,$nuevo['metodo_pago'],$cambio,$nuevo['codigo_transaccion'],$id));
                if($software){
                    $pagos = self::consulta($db,'SELECT * FROM servicios_pagos WHERE id_servicio=? ORDER BY fecha,id FOR UPDATE',array($idDocumento))->fetchAll();
                    $sumAnterior = array_sum(array_column($pagos,'monto'))-$monto+(float)$antes['monto'];
                    // Conserva pagos antiguos que existian antes del historial individual.
                    $base = round((float)$p['pago_adelanto']+(float)$p['pago_final']-$sumAnterior,2);
                    if($base < -0.01){ throw new DomainException('El historial y el total pagado del proyecto no coinciden. Revise el proyecto antes de corregir.'); }
                    $acumulado = max(0,$base);
                    foreach($pagos as $fila){
                        $saldo = round((float)$p['precio_total']-$acumulado,2);
                        $acumulado = round($acumulado+(float)$fila['monto'],2);
                        if($acumulado > (float)$p['precio_total']){ throw new DomainException('La correccion supera el precio del proyecto al sumar los pagos posteriores.'); }
                        $concepto = $acumulado >= (float)$p['precio_total'] ? 'pago_final_software' : ($acumulado >= (float)$p['monto_adelanto'] ? 'amortizacion_software' : 'adelanto_parcial_software');
                        self::consulta($db,'UPDATE servicios_pagos SET saldo_antes=?,saldo_despues=?,tipo_pago=? WHERE id=?',array($saldo,round((float)$p['precio_total']-$acumulado,2),$concepto,$fila['id']));
                    }
                    self::proyecto($db,$p,$acumulado);
                }else{
                    self::consulta($db,'UPDATE servicios_pagos SET saldo_antes=?,saldo_despues=0 WHERE id=?',array($saldoAntes,$id));
                }
                // La cabecera muestra siempre el ultimo pago, aun al corregir uno anterior.
                $ultimo = self::consulta($db,'SELECT * FROM servicios_pagos WHERE id_servicio=? ORDER BY fecha DESC,id DESC LIMIT 1',array($idDocumento))->fetch();
                self::consulta($db,'UPDATE servicios_ventas SET metodo_pago=?,monto_recibido=?,cambio=?,codigo_transaccion=? WHERE id=?',array($ultimo['metodo_pago'],round((float)$ultimo['monto']+(float)$ultimo['cambio'],2),$ultimo['cambio'],$ultimo['codigo_transaccion'],$idDocumento));
            }else{
                self::consulta($db,"UPDATE $tabla SET metodo_pago=?,monto_recibido=?,cambio=?,codigo_transaccion=?,fecha=fecha WHERE id=?",array($nuevo['metodo_pago'],$nuevo['monto_recibido'],$cambio,$nuevo['codigo_transaccion'],$id));
            }
            if(count($mov)>1){ throw new DomainException('Hay movimientos de caja duplicados para este pago. Revise caja antes de corregir.'); }
            foreach($mov as $fila){
                $descripcion = 'Cobro de '.($tipo==='venta'?'venta':'servicio').' '.$s['codigo'];
                if($nuevo['moneda']==='USD'){ $descripcion .= ' | USD '.$nuevo['monto_original'].' x '.$nuevo['tipo_cambio'].' Bs/USD ('.$nuevo['fecha_cambio'].')'; }
                self::consulta($db,'UPDATE caja_movimientos SET monto=?,metodo_pago=?,afecta_efectivo=?,descripcion=? WHERE id=?',array($monto,$nuevo['metodo_pago'],$nuevo['metodo_pago']==='Efectivo'?1:0,$descripcion,$fila['id']));
            }
            foreach($cajas as $caja){
                // Mantiene el efectivo contado y recalcula lo esperado y la diferencia de cierres afectados.
                self::consulta($db,"UPDATE caja_aperturas SET monto_esperado_cierre=monto_inicial+(SELECT COALESCE(SUM(CASE WHEN tipo='ingreso' THEN monto ELSE -monto END),0) FROM caja_movimientos WHERE id_apertura=? AND afecta_efectivo=1) WHERE id=? AND estado='cerrada'",array($caja,$caja));
                self::consulta($db,"UPDATE caja_aperturas SET diferencia=monto_contado_cierre-monto_esperado_cierre WHERE id=? AND estado='cerrada'",array($caja));
            }
            self::divisa($db,$tipo,$id,$nuevo);
            $despues = self::obtener($db,$tipo,$id);
            self::consulta($db,'INSERT INTO cobros_correcciones (referencia_tipo,id_referencia,id_administrador,motivo,antes,despues) VALUES (?,?,?,?,?,?)',
                array($tipo,$id,$datos['id_usuario'],$motivo,json_encode(array('pago'=>$antes,'movimientos'=>$mov,'proyecto'=>$p),JSON_UNESCAPED_UNICODE),json_encode(array('pago'=>$despues,'monto_aplicado'=>$monto),JSON_UNESCAPED_UNICODE)));
            $db->commit();
            return true;
        }catch(Throwable $e){
            if($db->inTransaction()){ $db->rollBack(); }
            throw $e;
        }
    }

    static public function mdlHistorial($tipo,$id){
        self::mdlPreparar();
        return self::consulta(Conexion::conectar(),'SELECT c.*,u.nombre AS administrador FROM cobros_correcciones c LEFT JOIN usuarios u ON u.id=c.id_administrador WHERE referencia_tipo=? AND id_referencia=? ORDER BY c.id DESC',array($tipo,$id))->fetchAll();
    }
}
