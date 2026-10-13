<?php
require_once __DIR__.'/../../../modelos/conexion.php';

function tmPdfCobroDivisa($tipo, $id){
    try{
        $db = Conexion::conectar();
        if($tipo === 'servicio'){
            $stmt = $db->prepare("SELECT d.* FROM cobros_divisas d JOIN servicios_pagos p ON d.referencia_tipo='pago_servicio' AND d.id_referencia=p.id WHERE p.id_servicio=? ORDER BY p.fecha DESC,p.id DESC LIMIT 1");
            $stmt->execute(array((int)$id));
            $d = $stmt->fetch(PDO::FETCH_ASSOC);
        }else{
            $stmt = $db->prepare('SELECT * FROM cobros_divisas WHERE referencia_tipo=? AND id_referencia=?');
            $stmt->execute(array($tipo,(int)$id));
            $d = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        if(!$d || $d['moneda'] !== 'USD'){ return ''; }
        $tasa = rtrim(rtrim(number_format((float)$d['tipo_cambio'],6,'.',''),'0'),'.');
        return '<p style="font-size:9px;color:#176b9b"><b>Recibido: USD '.number_format((float)$d['monto_original'],2).'</b><br>Tipo de cambio: Bs '.$tasa.' por USD · Fecha: '.htmlspecialchars($d['fecha_cambio'],ENT_QUOTES,'UTF-8').'<br>Equivalente recibido: Bs '.number_format(round((float)$d['monto_original']*(float)$d['tipo_cambio'],2),2).'</p>';
    }catch(PDOException $e){
        if($e->getCode() !== '42S02'){ error_log('Divisa de comprobante: '.$e->getMessage()); }
        return '';
    }
}
