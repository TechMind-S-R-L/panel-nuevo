<?php
class ControladorCobros {
    static public function csrf(){
        if(empty($_SESSION['csrf_cobros'])){ $_SESSION['csrf_cobros'] = bin2hex(random_bytes(32)); }
        return $_SESSION['csrf_cobros'];
    }

    static private function validarSolicitud($admin = false){
        if(empty($_SESSION['id']) || (($admin || ($_SESSION['rol'] ?? '') !== 'cajero') && ($_SESSION['perfil'] ?? '') !== 'Administrador')){
            throw new DomainException('No tiene permiso para realizar esta operacion.');
        }
        if(!hash_equals(self::csrf(),(string)($_POST['csrf_cobros'] ?? ''))){ throw new DomainException('La sesion del formulario vencio. Recargue la pagina.'); }
    }

    static private function error($e){
        $mensaje = ($e instanceof DomainException || $e instanceof InvalidArgumentException) ? $e->getMessage() : 'No se pudo guardar el pago. Intente nuevamente.';
        if(!($e instanceof DomainException) && !($e instanceof InvalidArgumentException)){ error_log('Cobros: '.$e->getMessage()); }
        echo '<script>swal({type:"error",title:"No se pudo guardar",text:'.json_encode($mensaje).',confirmButtonText:"Cerrar"});</script>';
    }

    static private function log($accion,$detalle){
        if(class_exists('ControladorLogs')){
            try{ ControladorLogs::ctrRegistrarLog($accion,'cobros',$detalle); }
            catch(Throwable $e){ error_log('Log de cobros: '.$e->getMessage()); }
        }
    }

    static public function ctrCobrar($tipo){
        if(!isset($_POST['registrarCobro'])){ return; }
        try{
            self::validarSolicitud();
            $apertura = ControladorCaja::ctrAperturaActiva();
            if(!$apertura){ throw new DomainException('Debe abrir su caja antes de realizar cobros.'); }
            $datos = $_POST;
            $datos['id_usuario'] = (int)$_SESSION['id'];
            $datos['id_apertura'] = (int)$apertura['id'];
            $resultado = ModeloCobros::mdlCobrar($tipo,$datos);
            if(empty($resultado['repetido'])){ self::log('aprobar_pago','Cobro de '.$tipo.' '.$resultado['id'].' por Bs '.$resultado['monto']); }
            $ruta = $tipo==='venta' ? 'pagos-ventas' : 'pagos-servicios';
            $pdf = $tipo==='venta' ? 'boleta-despacho.php?idVenta='.$resultado['id'] : ($resultado['software'] ? 'boleta-software-pago.php?idServicio='.$resultado['id'].'&idPago='.$resultado['id_pago'] : 'nota-venta-servicio.php?idServicio='.$resultado['id'].'&destino=almacen');
            $texto = 'Aplicado: Bs '.number_format($resultado['monto'],2).'. Saldo pendiente: Bs '.number_format($resultado['saldo'],2).'.';
            echo '<script>swal({type:"success",title:"Pago registrado",text:'.json_encode($texto).',confirmButtonText:"Cerrar"}).then(function(){window.open('.json_encode('extensiones/tcpdf/pdf/'.$pdf).',"_blank");window.location='.json_encode($ruta).';});</script>';
        }catch(Throwable $e){ self::error($e); }
    }

    static public function ctrCorregir(){
        if(!isset($_POST['corregirCobro'])){ return; }
        try{
            self::validarSolicitud(true);
            $datos = $_POST;
            $datos['id_usuario'] = (int)$_SESSION['id'];
            ModeloCobros::mdlCorregir($datos);
            self::log('corregir_pago','Correccion de '.$datos['referencia_tipo'].' '.$datos['id_referencia'].': '.$datos['motivo']);
            echo '<script>swal({type:"success",title:"Pago corregido",text:"Se actualizaron los saldos y caja. La correccion quedo registrada en el historial.",confirmButtonText:"Cerrar"}).then(function(){window.location="correccion-pagos";});</script>';
        }catch(Throwable $e){ self::error($e); }
    }
}
