<?php
if(($_SESSION['perfil'] ?? '') !== 'Administrador'){
    echo '<script>window.location="inicio";</script>';
    return;
}
ControladorCobros::ctrCorregir();
function tmCorreccionEsc($v){ return htmlspecialchars((string)($v ?? ''),ENT_QUOTES,'UTF-8'); }
$busqueda = trim((string)($_GET['buscar'] ?? ''));
$pagos = ModeloCobros::mdlListar($busqueda);
$seleccion = null;
$historial = array();
$tipo = (string)($_GET['tipo'] ?? '');
$id = (int)($_GET['id'] ?? 0);
if($tipo && $id){
    try{
        $seleccion = ModeloCobros::mdlObtener($tipo,$id);
        $historial = ModeloCobros::mdlHistorial($tipo,$id);
    }catch(DomainException $e){ $errorSeleccion = $e->getMessage(); }
}
?>
<div class="content-wrapper correccion-pagos-wrapper">
  <style>
    .correccion-pagos-wrapper .content{padding-top:10px}
    .correccion-hero{position:relative;border:1px solid rgba(184,205,232,.62);border-radius:18px;background:linear-gradient(135deg,#102b3b,#176b9b);color:#fff;padding:18px 20px;margin-bottom:14px;box-shadow:0 18px 38px rgba(15,23,42,.12)}
    .correccion-hero h2{margin:0 0 5px;font-size:24px;font-weight:900}.correccion-hero p{margin:0;color:#e7f2fa}
    .correccion-panel{border:1px solid rgba(184,205,232,.68);border-radius:17px;background:rgba(255,255,255,.72);box-shadow:0 16px 38px rgba(15,23,42,.07);padding:16px;margin-bottom:14px}
    .correccion-panel h3{font-size:17px;font-weight:900;margin:0 0 14px;color:#176b9b}.correccion-panel .form-control{border-radius:9px}.correccion-panel .btn{border-radius:9px;font-weight:800}
    .correccion-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px}
    .correccion-card{border:1px solid #d5e3ef;border-left:5px solid #3c8dbc;border-radius:14px;background:rgba(255,255,255,.85);padding:14px;display:flex;flex-direction:column;gap:8px;box-shadow:0 12px 28px rgba(15,23,42,.06)}
    .correccion-card h4{margin:0;font-size:15px;font-weight:900;overflow-wrap:anywhere}.correccion-card .codigo{color:#176b9b;font-size:12px;font-weight:900}.correccion-card .importe{border-radius:10px;background:rgba(60,141,188,.10);padding:10px;color:#176b9b;font-size:22px;font-weight:900}.correccion-card small{display:block;font-size:11px;color:#60717f;font-weight:600}.correccion-card .btn{margin-top:auto;border-radius:9px}
    .correccion-resumen{padding:12px;border-radius:10px;background:rgba(60,141,188,.10);margin-bottom:14px}.correccion-resumen strong{font-size:18px;color:#176b9b}.correccion-historial{border-top:1px solid #d5e3ef;padding:12px 0;overflow-wrap:anywhere}
    body.tm-dark-mode .correccion-panel,body.dark-mode .correccion-panel,body.tm-dark-mode .correccion-card,body.dark-mode .correccion-card{background:#132137;color:#e1edf8;border-color:#30445c}
    body.tm-dark-mode .correccion-card small,body.dark-mode .correccion-card small{color:#b9cde0}
    body.tm-dark-mode .correccion-card .importe,body.dark-mode .correccion-card .importe,body.tm-dark-mode .correccion-panel h3,body.dark-mode .correccion-panel h3,body.tm-dark-mode .correccion-resumen strong,body.dark-mode .correccion-resumen strong,body.tm-dark-mode .correccion-card .codigo,body.dark-mode .correccion-card .codigo{color:#7dd3fc}
    @media(max-width:767px){.correccion-grid{grid-template-columns:1fr}.correccion-hero h2{font-size:21px}}
  </style>
  <section class="content-header"><h1>Correccion de pagos</h1><ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li><li>Gestion de Cobros</li><li class="active">Correccion de pagos</li></ol></section>
  <section class="content">
    <div class="correccion-hero"><h2><i class="fa fa-pencil-square-o"></i> Correccion de pagos</h2><p>Solo administradores. Corrija un cobro o una amortizacion y conserve el historial de cada cambio.</p></div>
    <?php if(isset($errorSeleccion)): ?><div class="alert alert-danger"><?php echo tmCorreccionEsc($errorSeleccion); ?></div><?php endif; ?>
    <?php if($seleccion):
      $divisa = $seleccion['divisa'];
      $recibido = $tipo==='pago_servicio' ? (float)$seleccion['monto']+(float)$seleccion['cambio'] : (float)$seleccion['monto_recibido'];
      $moneda = $divisa['moneda'] ?? 'BOB';
      $original = $divisa['monto_original'] ?? $recibido;
      $tasa = $divisa['tipo_cambio'] ?? 1;
      $fecha = $divisa['fecha_cambio'] ?? substr(($seleccion['fecha_pago'] ?? $seleccion['fecha']),0,10);
      if(!$fecha){ $fecha = (new DateTimeImmutable('now',new DateTimeZone('America/La_Paz')))->format('Y-m-d'); }
      $aplicadoAnterior = $tipo==='pago_servicio' ? (float)$seleccion['monto'] : (float)$seleccion['total'];
      $limite = $tipo==='pago_servicio' ? (float)$seleccion['saldo_antes'] : (float)$seleccion['total'];
    ?>
    <div class="correccion-panel" id="editarPago">
      <h3><i class="fa fa-edit"></i> Editar <?php echo $tipo==='venta'?'cobro de venta':'pago de servicio'; ?> #<?php echo $id; ?></h3>
      <div class="correccion-resumen">Pago aplicado actualmente: <strong>Bs <?php echo number_format($aplicadoAnterior,2); ?></strong><br>Recibido: Bs <?php echo number_format($recibido,2); ?> · Cambio: Bs <?php echo number_format((float)$seleccion['cambio'],2); ?></div>
      <form method="post" id="formCorreccionPago">
        <input type="hidden" name="corregirCobro" value="1"><input type="hidden" name="csrf_cobros" value="<?php echo tmCorreccionEsc(ControladorCobros::csrf()); ?>">
        <input type="hidden" name="referencia_tipo" value="<?php echo tmCorreccionEsc($tipo); ?>"><input type="hidden" name="id_referencia" value="<?php echo $id; ?>">
        <input type="hidden" name="version" value="<?php echo tmCorreccionEsc(ModeloCobros::version($seleccion)); ?>">
        <div class="row">
          <div class="col-sm-6"><div class="form-group"><label>Moneda recibida</label><select name="moneda" id="corregirMoneda" class="form-control"><option value="BOB" <?php echo $moneda==='BOB'?'selected':''; ?>>Bolivianos (Bs)</option><option value="USD" <?php echo $moneda==='USD'?'selected':''; ?>>Dolares (USD)</option></select></div></div>
          <div class="col-sm-6"><div class="form-group"><label>Monto recibido en la moneda seleccionada</label><input type="number" name="monto_original" id="corregirOriginal" class="form-control" min="0.01" max="99999999.99" step="0.01" required value="<?php echo tmCorreccionEsc($original); ?>"></div></div>
          <div class="col-sm-6" id="corregirGrupoTasa"><div class="form-group"><label>Tipo de cambio (Bs por USD)</label><input type="number" name="tipo_cambio" id="corregirTasa" class="form-control" min="0.000001" max="99999999.999999" step="0.000001" value="<?php echo tmCorreccionEsc($tasa); ?>"></div></div>
          <div class="col-sm-6"><div class="form-group"><label>Fecha de la cotizacion</label><input type="date" name="fecha_cambio" class="form-control" value="<?php echo tmCorreccionEsc($fecha); ?>" required></div></div>
          <div class="col-sm-6"><div class="form-group"><label>Metodo de pago</label><select name="metodo_pago" class="form-control"><?php foreach(array('Efectivo','QR','Tarjeta Credito','Tarjeta Debito') as $metodo): ?><option <?php echo $metodo===($seleccion['metodo_pago']?:'Efectivo')?'selected':''; ?>><?php echo tmCorreccionEsc($metodo); ?></option><?php endforeach; ?></select></div></div>
          <div class="col-sm-6"><div class="form-group"><label>Codigo / referencia</label><input type="text" name="codigo_transaccion" maxlength="100" class="form-control" value="<?php echo tmCorreccionEsc($seleccion['codigo_transaccion']); ?>"></div></div>
        </div>
        <div class="alert alert-info" id="corregirResumen" aria-live="polite"></div>
        <div class="form-group"><label>Motivo de la correccion</label><textarea name="motivo" class="form-control" minlength="5" maxlength="500" rows="3" required placeholder="Explique que dato se registro incorrectamente y por que se corrige"></textarea></div>
        <p class="help-block">Se actualizaran el pago, los saldos posteriores y el movimiento de caja vinculado. Si la caja esta cerrada, se recalcularan el efectivo esperado y la diferencia, conservando el efectivo contado.</p>
        <a href="correccion-pagos" class="btn btn-default">Cancelar</a> <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar correccion</button>
      </form>
      <?php if($historial): ?><hr><h3><i class="fa fa-history"></i> Historial de correcciones</h3>
        <?php foreach($historial as $h): $a=json_decode($h['antes'],true); $d=json_decode($h['despues'],true); ?>
          <div class="correccion-historial"><strong><?php echo tmCorreccionEsc($h['administrador']); ?></strong> · <?php echo tmCorreccionEsc($h['fecha']); ?><p><?php echo tmCorreccionEsc($h['motivo']); ?></p>
          <small>Metodo: <?php echo tmCorreccionEsc($a['pago']['metodo_pago'] ?? ''); ?> → <?php echo tmCorreccionEsc($d['pago']['metodo_pago'] ?? ''); ?> · Aplicado: Bs <?php echo number_format((float)($a['pago']['monto'] ?? $a['pago']['total'] ?? 0),2); ?> → Bs <?php echo number_format((float)($d['monto_aplicado'] ?? 0),2); ?></small></div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <script>
    (function(){
      function resumen(){
        var usd=$('#corregirMoneda').val()==='USD';
        $('#corregirGrupoTasa').toggle(usd);
        $('#corregirTasa').prop('required',usd);
        var equivalente=Math.round((Number($('#corregirOriginal').val())*(usd?Number($('#corregirTasa').val()):1)+Number.EPSILON)*100)/100;
        var aplicado=Math.min(equivalente,<?php echo json_encode($limite); ?>);
        $('#corregirResumen').text('Equivalente: Bs '+equivalente.toFixed(2)+' · Aplicado: Bs '+aplicado.toFixed(2)+' · Cambio en Bs: '+Math.max(0,equivalente-aplicado).toFixed(2));
      }
      $('#formCorreccionPago').on('input change','input,select',resumen);resumen();
      $('#formCorreccionPago').on('submit',function(e){
        e.preventDefault();var form=this;
        swal({type:'warning',title:'Guardar correccion del pago',text:'Se actualizaran los saldos y caja, dejando constancia del motivo.',showCancelButton:true,confirmButtonText:'Si, guardar',cancelButtonText:'Cancelar'}).then(function(r){if(r.value){$(form).find('button[type=submit]').prop('disabled',true);form.submit();}});
      });
    })();
    </script>
    <?php endif; ?>
    <div class="correccion-panel"><h3><i class="fa fa-search"></i> Buscar un pago registrado</h3>
      <form method="get"><input type="hidden" name="ruta" value="correccion-pagos"><div class="input-group"><input type="search" name="buscar" class="form-control" value="<?php echo tmCorreccionEsc($busqueda); ?>" placeholder="Codigo, cliente o cajero"><span class="input-group-btn"><button class="btn btn-primary" type="submit">Buscar</button></span></div></form>
      <p class="help-block">Se muestran hasta 200 pagos recientes. Busque por codigo o cliente para encontrar pagos anteriores.</p>
    </div>
    <div class="correccion-grid">
      <?php foreach($pagos as $fila): ?>
        <article class="correccion-card"><span class="codigo"><i class="fa <?php echo $fila['referencia_tipo']==='venta'?'fa-shopping-cart':'fa-wrench'; ?>"></i> <?php echo tmCorreccionEsc($fila['codigo']); ?> · <?php echo $fila['referencia_tipo']==='venta'?'Venta':'Servicio'; ?></span>
          <h4><?php echo tmCorreccionEsc($fila['cliente'] ?: 'Sin cliente'); ?></h4><div class="importe">Bs <?php echo number_format((float)$fila['monto'],2); ?><small>Pago aplicado</small></div>
          <small>Fecha: <?php echo tmCorreccionEsc($fila['fecha']); ?><br>Cajero: <?php echo tmCorreccionEsc($fila['cajero'] ?: 'Sin registro'); ?><br>Metodo: <?php echo tmCorreccionEsc($fila['metodo_pago']); ?></small>
          <?php if(($fila['moneda'] ?? '')==='USD'): ?><small>Recibido: USD <?php echo number_format((float)$fila['monto_original'],2); ?> · TC: <?php echo tmCorreccionEsc($fila['tipo_cambio']); ?> Bs/USD</small><?php endif; ?>
          <a class="btn btn-primary" href="index.php?ruta=correccion-pagos&amp;tipo=<?php echo urlencode($fila['referencia_tipo']); ?>&amp;id=<?php echo (int)$fila['id_referencia']; ?>#editarPago"><i class="fa fa-edit"></i> Corregir pago</a>
        </article>
      <?php endforeach; ?>
      <?php if(!$pagos): ?><div class="correccion-panel">No hay pagos que coincidan con la busqueda.</div><?php endif; ?>
    </div>
  </section>
</div>
