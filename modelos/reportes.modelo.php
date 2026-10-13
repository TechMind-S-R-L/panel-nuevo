<?php

// Fuente compartida por reportes, PDF y tablero. Solo lee cobros confirmados.
class ModeloReportes{

    private static function tablaExiste($db, $tabla){
        $stmt = $db->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute(array($tabla));
        return (bool)$stmt->fetchColumn();
    }

    private static function fuenteCobrosServicios($db){
        $historial = self::tablaExiste($db, 'servicios_pagos');
        $partes = array();
        if($historial){
            $partes[] = "SELECT id_servicio, id AS id_pago, monto AS monto_cobrado, fecha AS fecha_reporte,
                                id_cajero, tipo_pago, metodo_pago AS metodo_cobro
                         FROM servicios_pagos WHERE monto > 0";
        }

        // Cabeceras antiguas sin historial: se incluyen una sola vez.
        $sinHistorial = $historial ? 'AND NOT EXISTS (SELECT 1 FROM servicios_pagos h WHERE h.id_servicio = s.id)' : '';
        $partes[] = "SELECT s.id AS id_servicio, NULL AS id_pago, s.total AS monto_cobrado,
                            COALESCE(s.fecha_pago, s.fecha) AS fecha_reporte, s.id_cajero,
                            'servicio_historico' AS tipo_pago, s.metodo_pago AS metodo_cobro
                     FROM servicios_ventas s
                     WHERE s.estado_pago = 'aprobado' AND s.tipo_servicio <> 'Desarrollo de software'
                       AND s.total > 0 $sinHistorial";

        if(self::tablaExiste($db, 'proyectos_software')){
            $sumaHistorial = $historial
                ? '(SELECT COALESCE(SUM(h.monto), 0) FROM servicios_pagos h WHERE h.id_servicio = p.id_servicio)'
                : '0';
            // Conserva importes anteriores al historial, sin volver a sumar pagos individuales.
            $base = "SELECT p.*, GREATEST(0, COALESCE(p.pago_adelanto, 0) + COALESCE(p.pago_final, 0) - $sumaHistorial) AS base_historica
                     FROM proyectos_software p";
            $partes[] = "SELECT s.id, NULL, LEAST(COALESCE(p.pago_adelanto, 0), p.base_historica),
                                COALESCE(p.fecha_adelanto, s.fecha_pago, s.fecha), s.id_cajero,
                                'adelanto_historico_software', s.metodo_pago
                         FROM ($base) p JOIN servicios_ventas s ON s.id = p.id_servicio
                         WHERE LEAST(COALESCE(p.pago_adelanto, 0), p.base_historica) > 0";
            $partes[] = "SELECT s.id, NULL, GREATEST(0, p.base_historica - COALESCE(p.pago_adelanto, 0)),
                                COALESCE(p.fecha_pago_final, s.fecha_pago, s.fecha), s.id_cajero,
                                'pago_final_historico_software', s.metodo_pago
                         FROM ($base) p JOIN servicios_ventas s ON s.id = p.id_servicio
                         WHERE p.base_historica > COALESCE(p.pago_adelanto, 0)";
        }
        return implode(' UNION ALL ', $partes);
    }

    public static function mdlCobrosServicios($db, $inicio, $fin){
        $fuente = self::fuenteCobrosServicios($db);
        $stmt = $db->prepare("SELECT s.*, r.id_pago, r.monto_cobrado, r.fecha_reporte, r.tipo_pago, r.metodo_cobro,
                                    c.nombre AS cliente, uv.nombre AS vendedor, uc.nombre AS cajero, ut.nombre AS tecnico
                             FROM ($fuente) r JOIN servicios_ventas s ON s.id = r.id_servicio
                             LEFT JOIN clientes c ON c.id = s.id_cliente
                             LEFT JOIN usuarios uv ON uv.id = s.id_vendedor
                             LEFT JOIN usuarios uc ON uc.id = r.id_cajero
                             LEFT JOIN usuarios ut ON ut.id = s.id_tecnico
                             WHERE r.fecha_reporte BETWEEN :inicio AND :fin
                             ORDER BY r.fecha_reporte DESC, s.id DESC, r.id_pago DESC");
        $stmt->execute(array(':inicio'=>$inicio, ':fin'=>$fin));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlTotalServicios($db, $inicio, $fin){
        $fuente = self::fuenteCobrosServicios($db);
        $stmt = $db->prepare("SELECT COALESCE(SUM(r.monto_cobrado), 0) FROM ($fuente) r WHERE r.fecha_reporte BETWEEN :inicio AND :fin");
        $stmt->execute(array(':inicio'=>$inicio, ':fin'=>$fin));
        return (float)$stmt->fetchColumn();
    }

    public static function agruparServicios($cobros){
        $tipos = array();
        foreach($cobros as $cobro){
            $tipo = $cobro['tipo_servicio'];
            if(!isset($tipos[$tipo])){
                $tipos[$tipo] = array('tipo_servicio'=>$tipo, 'cantidad'=>0, 'cobros'=>0, 'total'=>0, 'ids'=>array());
            }
            $tipos[$tipo]['ids'][$cobro['id']] = true;
            $tipos[$tipo]['cantidad'] = count($tipos[$tipo]['ids']);
            $tipos[$tipo]['cobros']++;
            $tipos[$tipo]['total'] += (float)$cobro['monto_cobrado'];
        }
        foreach($tipos as &$tipo){ unset($tipo['ids']); }
        unset($tipo);
        usort($tipos, function($a, $b){ return $b['total'] <=> $a['total']; });
        return $tipos;
    }
}
