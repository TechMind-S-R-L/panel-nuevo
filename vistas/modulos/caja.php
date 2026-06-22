<?php
if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "cajero"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

$resultadoAperturaCaja = ControladorCaja::ctrAbrirCaja();
$resultadoMovimientoCaja = ControladorCaja::ctrRegistrarMovimientoManual();
$resultadoCierreCaja = ControladorCaja::ctrCerrarCaja();

if(is_int($resultadoAperturaCaja)){
  echo '<script>swal({type:"success",title:"Caja abierta",text:"Ya puede registrar cobros y movimientos.",confirmButtonText:"Continuar"}).then(function(){window.location="caja";});</script>';
}else if($resultadoAperturaCaja === "monto_invalido"){
  echo '<script>swal({type:"error",title:"Monto inicial invalido",confirmButtonText:"Cerrar"});</script>';
}

if(is_int($resultadoMovimientoCaja)){
  echo '<script>swal({type:"success",title:"Movimiento registrado",confirmButtonText:"Cerrar"}).then(function(){window.location="caja";});</script>';
}else if($resultadoMovimientoCaja === "datos_invalidos"){
  echo '<script>swal({type:"error",title:"Complete monto y descripcion",confirmButtonText:"Cerrar"});</script>';
}else if($resultadoMovimientoCaja === "saldo_insuficiente"){
  echo '<script>swal({type:"error",title:"Efectivo insuficiente",text:"El egreso supera el efectivo esperado disponible en caja.",confirmButtonText:"Cerrar"});</script>';
}

if($resultadoCierreCaja === "ok"){
  echo '<script>swal({type:"success",title:"Caja cerrada correctamente",text:"El arqueo quedo guardado en el historial.",confirmButtonText:"Cerrar"}).then(function(){window.location="caja";});</script>';
}

$aperturaCaja = ControladorCaja::ctrAperturaActiva();
$resumenCaja = $aperturaCaja ? ControladorCaja::ctrResumenActual() : false;
$movimientosCaja = $aperturaCaja ? ControladorCaja::ctrMovimientosActuales() : array();
$historialCaja = ControladorCaja::ctrHistorial(40);

function tmCajaEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function tmCajaOrigen($origen){
  $nombres = array(
    "venta" => "Venta",
    "servicio" => "Servicio",
    "desembolso_compra" => "Desembolso",
    "manual" => "Manual"
  );
  return $nombres[$origen] ?? ucfirst(str_replace("_", " ", $origen));
}
?>

<div class="content-wrapper caja-wrapper">
  <style>
    .caja-wrapper .content{padding-top:10px;}
    .caja-hero{
      position:relative;overflow:hidden;border-radius:18px;padding:20px;margin-bottom:14px;color:#fff;
      background:linear-gradient(135deg,#102b3b,#176b9b 64%,#36aee2);
      box-shadow:0 18px 38px rgba(15,23,42,.14);
    }
    .caja-hero:after{content:"";position:absolute;right:-60px;top:-85px;width:230px;height:230px;border-radius:50%;background:rgba(255,255,255,.12);}
    .caja-hero h1{position:relative;z-index:1;margin:0 0 5px;font-size:24px;font-weight:950;}
    .caja-hero p{position:relative;z-index:1;margin:0;color:rgba(255,255,255,.86);font-weight:750;font-size:13px;}
    .caja-state{
      position:relative;z-index:2;display:inline-flex;align-items:center;gap:7px;margin-top:12px;padding:6px 11px;
      border-radius:999px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.22);font-size:11px;font-weight:900;
    }
    .caja-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:12px;}
    .caja-kpi,.caja-panel{
      border:1px solid rgba(184,205,232,.68);background:rgba(255,255,255,.72);border-radius:15px;
      box-shadow:0 12px 28px rgba(15,23,42,.06);
    }
    .caja-kpi{padding:13px;display:flex;gap:10px;align-items:center;}
    .caja-kpi i{width:38px;height:38px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;background:linear-gradient(135deg,#176b9b,#36aee2);}
    .caja-kpi span{display:block;color:#728398;font-size:9.5px;font-weight:900;text-transform:uppercase;}
    .caja-kpi strong{display:block;color:#172033;font-size:18px;font-weight:950;margin-top:2px;}
    .caja-layout{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(280px,.7fr);gap:12px;}
    .caja-panel{overflow:hidden;margin-bottom:12px;}
    .caja-panel-head{padding:13px 15px;border-bottom:1px solid #e3ebf3;display:flex;align-items:center;justify-content:space-between;gap:10px;}
    .caja-panel-head h3{margin:0;color:#1d2b3d;font-size:16px;font-weight:950;}
    .caja-panel-body{padding:14px;}
    .caja-open-card{text-align:center;padding:24px 18px;}
    .caja-open-icon{width:72px;height:72px;margin:0 auto 13px;border-radius:22px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:30px;background:linear-gradient(135deg,#176b9b,#36aee2);box-shadow:0 14px 30px rgba(23,107,155,.25);}
    .caja-open-card h2{font-size:22px;font-weight:950;color:#172033;margin:0 0 7px;}
    .caja-open-card p{max-width:570px;margin:0 auto 18px;color:#66788e;font-weight:700;}
    .caja-form-card{max-width:560px;margin:0 auto;text-align:left;}
    .caja-form-card label{color:#60748b;font-size:10px;font-weight:950;text-transform:uppercase;}
    .caja-form-card .form-control,.caja-form-card .input-group-addon{border-color:#dbe7f0;box-shadow:none;font-weight:850;}
    .caja-form-card .btn{border-radius:10px;padding:9px 16px;font-weight:900;}
    .caja-table-wrap{overflow:auto;}
    .caja-table{width:100%;margin:0;}
    .caja-table th{white-space:nowrap;font-size:9.5px!important;}
    .caja-table td{font-size:11px;white-space:nowrap;}
    .caja-movement-type{display:inline-flex;align-items:center;gap:5px;font-weight:900;}
    .caja-movement-type.ingreso{color:#008d4c}.caja-movement-type.egreso{color:#d73925}
    .caja-chip{display:inline-flex;padding:4px 7px;border-radius:999px;background:#eef5fa;color:#476175;font-size:9px;font-weight:900;}
    .caja-actions .btn{width:100%;margin-bottom:8px;border-radius:9px;font-weight:900;padding:9px;}
    .caja-note{border:1px solid #dce8f1;border-radius:11px;padding:11px;background:#f7fbfe;color:#60748b;font-size:11px;font-weight:750;line-height:1.45;}
    .caja-history{margin-top:12px;}
    .caja-difference.positive{color:#008d4c}.caja-difference.negative{color:#d73925}
    body.tm-dark-mode .caja-kpi,body.dark-mode .caja-kpi,body.tm-dark-mode .caja-panel,body.dark-mode .caja-panel{background:rgba(15,27,48,.76);border-color:rgba(147,197,253,.18);}
    body.tm-dark-mode .caja-kpi strong,body.dark-mode .caja-kpi strong,body.tm-dark-mode .caja-panel-head h3,body.dark-mode .caja-panel-head h3,body.tm-dark-mode .caja-open-card h2,body.dark-mode .caja-open-card h2{color:#fff;}
    body.tm-dark-mode .caja-panel-head,body.dark-mode .caja-panel-head{border-color:rgba(147,197,253,.18);}
    body.tm-dark-mode .caja-note,body.dark-mode .caja-note{background:rgba(15,27,48,.55);border-color:rgba(147,197,253,.18);}
    @media(max-width:991px){.caja-grid{grid-template-columns:repeat(2,1fr)}.caja-layout{grid-template-columns:1fr}}
    @media(max-width:520px){.caja-grid{grid-template-columns:1fr}}
  </style>

  <section class="content-header">
    <h1>Control de caja</h1>
    <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li><li class="active">Caja</li></ol>
  </section>

  <section class="content">
    <div class="caja-hero">
      <h1><i class="fa fa-calculator"></i> Turno y arqueo de caja</h1>
      <p>Controle el fondo inicial, cobros, desembolsos, movimientos manuales y la diferencia al cierre.</p>
      <span class="caja-state"><i class="fa <?php echo $aperturaCaja ? 'fa-unlock' : 'fa-lock'; ?>"></i> Caja <?php echo $aperturaCaja ? 'abierta desde '.tmCajaEsc($aperturaCaja["fecha_apertura"]) : 'cerrada'; ?></span>
    </div>

    <?php if(!$aperturaCaja): ?>
      <div class="caja-panel">
        <div class="caja-open-card">
          <div class="caja-open-icon"><i class="fa fa-unlock-alt"></i></div>
          <h2>Abra su caja para comenzar</h2>
          <p>Registre cuánto efectivo recibe al iniciar el turno. Este importe será la base del arqueo y no se considera una venta.</p>
          <form method="post" class="caja-form-card">
            <input type="hidden" name="abrirCaja" value="1">
            <div class="form-group">
              <label>Efectivo inicial recibido</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control input-lg" name="montoInicialCaja" min="0" step="0.01" required autofocus></div>
            </div>
            <div class="form-group">
              <label>Observación de apertura</label>
              <textarea class="form-control" name="observacionAperturaCaja" rows="2" maxlength="500" placeholder="Ej.: Fondo entregado por administración"></textarea>
            </div>
            <button type="submit" class="btn btn-success btn-block btn-lg"><i class="fa fa-check"></i> Abrir caja e iniciar turno</button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <div class="caja-grid">
        <div class="caja-kpi"><i class="fa fa-flag"></i><div><span>Fondo inicial</span><strong>Bs <?php echo number_format((float)$resumenCaja["monto_inicial"],2); ?></strong></div></div>
        <div class="caja-kpi"><i class="fa fa-arrow-down"></i><div><span>Ingresos totales</span><strong>Bs <?php echo number_format((float)$resumenCaja["total_ingresos"],2); ?></strong></div></div>
        <div class="caja-kpi"><i class="fa fa-arrow-up"></i><div><span>Egresos totales</span><strong>Bs <?php echo number_format((float)$resumenCaja["total_egresos"],2); ?></strong></div></div>
        <div class="caja-kpi"><i class="fa fa-money"></i><div><span>Efectivo esperado</span><strong>Bs <?php echo number_format((float)$resumenCaja["efectivo_esperado"],2); ?></strong></div></div>
      </div>

      <div class="caja-layout">
        <div>
          <div class="caja-panel">
            <div class="caja-panel-head"><h3><i class="fa fa-exchange"></i> Movimientos del turno</h3><span class="badge bg-blue"><?php echo count($movimientosCaja); ?></span></div>
            <div class="caja-table-wrap">
              <table class="table table-hover caja-table">
                <thead><tr><th>Fecha</th><th>Tipo</th><th>Origen</th><th>Detalle</th><th>Método</th><th>Efectivo</th><th class="text-right">Monto</th></tr></thead>
                <tbody>
                <?php if(!$movimientosCaja): ?>
                  <tr><td colspan="7" class="text-center text-muted" style="padding:28px">Todavía no hay movimientos en este turno.</td></tr>
                <?php else: foreach($movimientosCaja as $movimiento): ?>
                  <tr>
                    <td><?php echo tmCajaEsc($movimiento["fecha"]); ?></td>
                    <td><span class="caja-movement-type <?php echo tmCajaEsc($movimiento["tipo"]); ?>"><i class="fa <?php echo $movimiento["tipo"]=="ingreso"?"fa-plus-circle":"fa-minus-circle"; ?>"></i><?php echo ucfirst(tmCajaEsc($movimiento["tipo"])); ?></span></td>
                    <td><?php echo tmCajaEsc(tmCajaOrigen($movimiento["origen"])); ?></td>
                    <td title="<?php echo tmCajaEsc($movimiento["descripcion"]); ?>"><?php echo tmCajaEsc($movimiento["descripcion"]); ?></td>
                    <td><span class="caja-chip"><?php echo tmCajaEsc($movimiento["metodo_pago"]); ?></span></td>
                    <td><?php echo (int)$movimiento["afecta_efectivo"]===1?"Sí":"No"; ?></td>
                    <td class="text-right"><strong>Bs <?php echo number_format((float)$movimiento["monto"],2); ?></strong></td>
                  </tr>
                <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <aside>
          <div class="caja-panel">
            <div class="caja-panel-head"><h3><i class="fa fa-plus-square"></i> Movimiento manual</h3></div>
            <div class="caja-panel-body">
              <form method="post">
                <input type="hidden" name="registrarMovimientoCaja" value="1">
                <div class="form-group"><label>Tipo</label><select class="form-control" name="tipoMovimientoCaja"><option value="ingreso">Ingreso</option><option value="egreso">Egreso</option></select></div>
                <div class="form-group"><label>Método</label><select class="form-control" name="metodoMovimientoCaja"><option value="Efectivo">Efectivo</option><option value="QR">QR</option><option value="Transferencia">Transferencia</option><option value="Tarjeta Credito">Tarjeta crédito</option><option value="Tarjeta Debito">Tarjeta débito</option></select></div>
                <div class="form-group"><label>Monto</label><div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control" name="montoMovimientoCaja" min="0.01" step="0.01" required></div></div>
                <div class="form-group"><label>Motivo / detalle</label><textarea class="form-control" name="descripcionMovimientoCaja" rows="3" maxlength="500" required></textarea></div>
                <button class="btn btn-primary btn-block"><i class="fa fa-save"></i> Registrar movimiento</button>
              </form>
            </div>
          </div>

          <div class="caja-panel">
            <div class="caja-panel-head"><h3><i class="fa fa-lock"></i> Cerrar turno</h3></div>
            <div class="caja-panel-body caja-actions">
              <div class="caja-note">Cuente únicamente el efectivo físico existente en caja. QR, tarjetas y transferencias ya están separados del arqueo.</div>
              <button class="btn btn-danger" data-toggle="modal" data-target="#modalCerrarCaja" style="margin-top:10px"><i class="fa fa-calculator"></i> Realizar arqueo y cerrar</button>
            </div>
          </div>
        </aside>
      </div>
    <?php endif; ?>

    <div class="caja-panel caja-history">
      <div class="caja-panel-head"><h3><i class="fa fa-history"></i> Historial de aperturas</h3></div>
      <div class="caja-table-wrap">
        <table class="table table-hover caja-table">
          <thead><tr><th>Cajero</th><th>Apertura</th><th>Cierre</th><th>Estado</th><th class="text-right">Inicial</th><th class="text-right">Esperado</th><th class="text-right">Contado</th><th class="text-right">Diferencia</th></tr></thead>
          <tbody>
          <?php if(!$historialCaja): ?>
            <tr><td colspan="8" class="text-center text-muted" style="padding:25px">No existen aperturas registradas.</td></tr>
          <?php else: foreach($historialCaja as $turno): $diferencia=(float)($turno["diferencia"]??0); ?>
            <tr>
              <td><?php echo tmCajaEsc($turno["cajero"] ?: "Usuario #".$turno["id_cajero"]); ?></td>
              <td><?php echo tmCajaEsc($turno["fecha_apertura"]); ?></td>
              <td><?php echo tmCajaEsc($turno["fecha_cierre"] ?: "-"); ?></td>
              <td><span class="label <?php echo $turno["estado"]==="abierta"?"label-success":"label-default"; ?>"><?php echo ucfirst(tmCajaEsc($turno["estado"])); ?></span></td>
              <td class="text-right">Bs <?php echo number_format((float)$turno["monto_inicial"],2); ?></td>
              <td class="text-right"><?php echo $turno["monto_esperado_cierre"]===null?"-":"Bs ".number_format((float)$turno["monto_esperado_cierre"],2); ?></td>
              <td class="text-right"><?php echo $turno["monto_contado_cierre"]===null?"-":"Bs ".number_format((float)$turno["monto_contado_cierre"],2); ?></td>
              <td class="text-right caja-difference <?php echo $diferencia<0?"negative":"positive"; ?>"><?php echo $turno["diferencia"]===null?"-":"Bs ".number_format($diferencia,2); ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<?php if($aperturaCaja): ?>
<div id="modalCerrarCaja" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content" style="border:0;border-radius:16px;overflow:hidden">
      <form method="post" id="formCerrarCaja">
        <input type="hidden" name="cerrarCaja" value="1">
        <div class="modal-header" style="background:linear-gradient(135deg,#b52b27,#dd4b39);color:#fff;border:0">
          <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:.9">&times;</button>
          <h4 class="modal-title" style="font-weight:950"><i class="fa fa-calculator"></i> Arqueo y cierre de caja</h4>
        </div>
        <div class="modal-body">
          <div class="alert alert-info"><strong>Efectivo esperado:</strong> Bs <?php echo number_format((float)$resumenCaja["efectivo_esperado"],2); ?></div>
          <div class="form-group"><label>Efectivo contado físicamente</label><div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control input-lg" id="montoContadoCaja" name="montoContadoCaja" min="0" step="0.01" required></div></div>
          <div class="form-group"><label>Diferencia calculada</label><div class="input-group"><span class="input-group-addon">Bs</span><input type="text" class="form-control" id="diferenciaCierreCaja" readonly value="0.00"></div></div>
          <div class="form-group"><label>Observación de cierre</label><textarea class="form-control" name="observacionCierreCaja" rows="3" maxlength="500" placeholder="Explique cualquier sobrante o faltante"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger"><i class="fa fa-lock"></i> Confirmar cierre</button></div>
      </form>
    </div>
  </div>
</div>
<script>
$(document).on("input", "#montoContadoCaja", function(){
  var esperado = <?php echo json_encode((float)$resumenCaja["efectivo_esperado"]); ?>;
  var contado = Number($(this).val()) || 0;
  $("#diferenciaCierreCaja").val((contado - esperado).toFixed(2));
});
$("#formCerrarCaja").on("submit", function(e){
  if($(this).data("confirmado")) return true;
  e.preventDefault();
  var form = this;
  swal({type:"warning",title:"¿Cerrar la caja?",text:"Después del cierre no podrá agregar movimientos a este turno.",showCancelButton:true,confirmButtonText:"Sí, cerrar",cancelButtonText:"Cancelar"}).then(function(result){
    if(result.value){$(form).data("confirmado",true).trigger("submit");}
  });
});
</script>
<?php endif; ?>
