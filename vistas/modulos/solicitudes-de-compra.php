<?php
if ($_SESSION["perfil"] != "Administrador" && !in_array($_SESSION["rol"], ["almacen", "cajero"])) {
    echo '<script>window.location = "inicio";</script>';
    return;
}

if (isset($_POST["idSolicitud"]) && isset($_POST["estado"])) {
    $respuesta = ControladorCompras::ctrCambiarEstadoSolicitud($_POST["idSolicitud"], $_POST["estado"]);
    if ($respuesta == "ok") {
        echo '<script>window.location = "solicitudes-de-compra";</script>';
    }
}

if (isset($_POST["idSolicitudDesembolso"]) && isset($_POST["montoDesembolso"])) {
    $idDesembolso = (int)$_POST["idSolicitudDesembolso"];
    $respuesta = ControladorCompras::ctrRegistrarDesembolsoMensajero($idDesembolso, $_POST["montoDesembolso"]);
    if ($respuesta == "ok") {
        echo '<script>
          swal({type:"success",title:"Desembolso registrado",confirmButtonText:"Cerrar"}).then(function(result){
            if(result.value){
              window.open("extensiones/tcpdf/pdf/boleta-desembolso-mensajero.php?idCompra='.$idDesembolso.'", "_blank");
              window.location = "solicitudes-de-compra";
            }
          });
        </script>';
    }else if($respuesta == "sin_apertura"){
        echo '<script>
          swal({type:"warning",title:"Debe abrir su caja",text:"No puede registrar desembolsos sin una apertura activa.",confirmButtonText:"Ir a caja"}).then(function(){window.location="caja";});
        </script>';
    }else if($respuesta == "saldo_insuficiente"){
        echo '<script>
          swal({type:"error",title:"Efectivo insuficiente",text:"El desembolso supera el efectivo esperado disponible en caja.",confirmButtonText:"Revisar caja"}).then(function(){window.location="caja";});
        </script>';
    }else if($respuesta == "monto_invalido"){
        echo '<script>swal({type:"error",title:"Monto invalido",text:"Ingrese un monto de desembolso mayor a cero.",confirmButtonText:"Cerrar"});</script>';
    }
}

if (isset($_POST["confirmarRendicionCompra"])) {
    $respuesta = ControladorCompras::ctrConfirmarRendicionCaja((int)$_POST["confirmarRendicionCompra"]);
    if ($respuesta === "ok") {
        echo '<script>swal({type:"success",title:"Rendicion confirmada",text:"El cambio fue registrado en caja y la compra ya puede entregarse a almacen.",confirmButtonText:"Cerrar"}).then(function(){window.location="solicitudes-de-compra";});</script>';
    } elseif ($respuesta === "sin_apertura") {
        echo '<script>swal({type:"warning",title:"Debe abrir su caja",text:"Abra caja para recibir y registrar el cambio devuelto.",confirmButtonText:"Ir a caja"}).then(function(){window.location="caja";});</script>';
    } else {
        echo '<script>swal({type:"error",title:"No se pudo confirmar la rendicion",confirmButtonText:"Cerrar"});</script>';
    }
}

$eliminarSolicitudCompra = new ControladorCompras();
$eliminarSolicitudCompra->ctrEliminarCompra();

function tmSolicitudCompraEsc($valor) {
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function estadoSolicitudCompraTexto($estado) {
    $estado = trim((string)$estado);
    $textos = array(
        "pendiente" => "Pendiente",
        "aprobado" => "Aprobado",
        "en_compra" => "En desembolso",
        "desembolsado" => "En proceso de compra",
        "rendicion_pendiente" => "Rendicion pendiente de caja",
        "compra_rendida" => "Compra rendida",
        "entregado_almacen" => "Entregado a almacen",
        "completado" => "Completado con exito",
        "rechazado" => "Rechazado"
    );
    return $textos[$estado] ?? ucwords(str_replace("_", " ", $estado));
}

function estadoSolicitudCompraClase($estado) {
    $estado = trim((string)$estado);
    $clases = array(
        "pendiente" => "neutral",
        "aprobado" => "primary",
        "en_compra" => "warning",
        "desembolsado" => "info",
        "rendicion_pendiente" => "warning",
        "compra_rendida" => "success",
        "entregado_almacen" => "success",
        "completado" => "success",
        "rechazado" => "danger"
    );
    return $clases[$estado] ?? "neutral";
}

function etiquetaEstadoSolicitudCompra($estado) {
    $clase = estadoSolicitudCompraClase($estado);
    return '<span class="tm-compra-status status-'.$clase.'">'.tmSolicitudCompraEsc(estadoSolicitudCompraTexto($estado)).'</span>';
}

function botonesReimpresionCompra($solicitud) {
    $estado = trim((string)$solicitud["estado"]);
    $html = '';

    if ($estado != "pendiente" && $estado != "rechazado") {
        $html .= '<button type="button" class="btn btn-default btnImprimirNotaCompra" idCompra="'.(int)$solicitud["id"].'" title="Reimprimir solicitud aprobada">
                    <i class="fa fa-file-text-o"></i> Solicitud
                  </button>';
    }

    if (!empty($solicitud["id_cajero_desembolso"]) || in_array($estado, array("desembolsado", "entregado_almacen", "completado"))) {
        $html .= '<button type="button" class="btn btn-default btnImprimirDesembolso" idCompra="'.(int)$solicitud["id"].'" title="Reimprimir constancia de desembolso">
                    <i class="fa fa-money"></i> Desembolso
                  </button>';
    }

    if (!empty($solicitud["fecha_entrega_almacen"]) || in_array($estado, array("entregado_almacen", "completado"))) {
        $html .= '<button type="button" class="btn btn-default btnImprimirEntregaAlmacen" idCompra="'.(int)$solicitud["id"].'" title="Reimprimir constancia de entrega a almacen">
                    <i class="fa fa-handshake-o"></i> Entrega almacen
                  </button>';
    }
    if (!empty($solicitud["factura_compra"])) {
        $html .= '<a class="btn btn-default" href="'.tmSolicitudCompraEsc($solicitud["factura_compra"]).'" target="_blank" title="Ver factura de compra">
                    <i class="fa fa-file-image-o"></i> Factura
                  </a>';
    }

    return $html;
}

function productosSolicitudCompraData($productosJson) {
    $productosSolicitud = json_decode($productosJson, true);
    $html = "";
    $cantidadItems = 0;
    $cantidadUnidades = 0;

    if (is_array($productosSolicitud)) {
        foreach ($productosSolicitud as $producto) {
            $descripcion = tmSolicitudCompraEsc($producto["descripcion"] ?? "Producto");
            $cantidad = (float)($producto["cantidad"] ?? 0);
            $cantidadVista = tmSolicitudCompraEsc($producto["cantidad"] ?? "0");
            $cantidadItems++;
            $cantidadUnidades += $cantidad;
            $html .= '<div class="tm-compra-producto">
                        <div>
                          <strong>'.$descripcion.'</strong>
                          <span>Producto solicitado</span>
                        </div>
                        <b>x '.$cantidadVista.'</b>
                      </div>';
        }
    } else if (trim((string)$productosJson) !== "") {
        $cantidadItems = 1;
        $cantidadUnidades = 1;
        $html = '<div class="tm-compra-producto"><div><strong>'.tmSolicitudCompraEsc($productosJson).'</strong><span>Detalle registrado</span></div></div>';
    }

    if ($html === "") {
        $html = '<div class="tm-compra-producto empty">Sin productos registrados.</div>';
    }

    return array(
        "html" => $html,
        "items" => $cantidadItems,
        "unidades" => $cantidadUnidades
    );
}

function rendicionSolicitudCompraHtml($idCompra) {
    $detalles = ModeloCompras::mdlDetalleRendicion((int)$idCompra);
    if (empty($detalles)) {
        return '<div class="tm-compra-producto empty">La compra aun no tiene costos rendidos.</div>';
    }
    $html = "";
    foreach ($detalles as $detalle) {
        $html .= '<div class="tm-compra-producto">
                    <div>
                      <strong>'.tmSolicitudCompraEsc($detalle["descripcion"] ?? "Producto").'</strong>
                      <span>'.(int)$detalle["cantidad"].' unidad(es) x Bs '.number_format((float)$detalle["costo_unitario"], 2).'</span>
                    </div>
                    <b>Bs '.number_format((float)$detalle["subtotal"], 2).'</b>
                  </div>';
    }
    return $html;
}

function datosUsuarioSolicitudCompra($idUsuario, $fallback) {
    if (empty($idUsuario)) {
        return array("nombre" => $fallback, "usuario" => "");
    }

    $usuario = ControladorUsuarios::ctrMostrarUsuarios("id", $idUsuario);
    return array(
        "nombre" => $usuario["nombre"] ?? $fallback,
        "usuario" => $usuario["usuario"] ?? ""
    );
}

function accionesSolicitudCompra($solicitud, $puedeAprobar) {
    $estado = trim((string)$solicitud["estado"]);
    $idSolicitud = (int)$solicitud["id"];
    $html = "";

    if ($puedeAprobar && $estado == "pendiente") {
        $html .= '<form method="POST" class="tm-compra-action-form">
                    <input type="hidden" name="idSolicitud" value="'.$idSolicitud.'">
                    <input type="hidden" name="estado" value="aprobado">
                    <button type="submit" class="btn btn-success" title="Aprobar solicitud">
                      <i class="fa fa-check"></i> Aprobar
                    </button>
                  </form>
                  <form method="POST" class="tm-compra-action-form">
                    <input type="hidden" name="idSolicitud" value="'.$idSolicitud.'">
                    <input type="hidden" name="estado" value="rechazado">
                    <button type="submit" class="btn btn-danger" title="Rechazar solicitud">
                      <i class="fa fa-times"></i> Rechazar
                    </button>
                  </form>';
    } else if ($puedeAprobar && $estado == "en_compra") {
        $html .= '<form method="POST" class="tm-compra-action-form tm-desembolso-form formConfirmarDesembolso">
                    <input type="hidden" name="idSolicitudDesembolso" value="'.$idSolicitud.'">
                    <div class="tm-desembolso-copy">
                      <span><i class="fa fa-money"></i> Monto autorizado</span>
                      <small>Ingrese el efectivo que recibira el mensajero para realizar la compra.</small>
                    </div>
                    <div class="tm-desembolso-control">
                      <label for="montoDesembolso'.$idSolicitud.'">Desembolso en bolivianos</label>
                      <div class="input-group">
                        <span class="input-group-addon">Bs</span>
                        <input type="number" class="form-control" id="montoDesembolso'.$idSolicitud.'" name="montoDesembolso" min="0.01" step="0.01" inputmode="decimal" placeholder="Ej.: 100.00" required>
                      </div>
                    </div>
                    <button type="submit" class="btn btn-primary tm-desembolso-submit" title="Registrar desembolso">
                      <i class="fa fa-check-circle"></i>
                      <span>Registrar desembolso</span>
                    </button>
                  </form>';
    } else if ($puedeAprobar && $estado == "rendicion_pendiente") {
        $html .= '<div class="tm-rendicion-caja-card">
                    <div class="tm-rendicion-caja-head">
                      <span class="tm-rendicion-caja-icon"><i class="fa fa-file-text-o"></i></span>
                      <div>
                        <strong>Rendicion recibida del mensajero</strong>
                        <small>Revise la factura, el costo real y el cambio antes de confirmar.</small>
                      </div>
                    </div>
                    <div class="tm-rendicion-caja-values">
                      <div><span>Desembolsado</span><b>Bs '.number_format((float)($solicitud["monto_desembolsado"] ?? 0), 2).'</b></div>
                      <div><span>Costo real</span><b>Bs '.number_format((float)($solicitud["costo_real_total"] ?? 0), 2).'</b></div>
                      <div class="cambio"><span>Cambio a recibir</span><b>Bs '.number_format((float)($solicitud["cambio_calculado"] ?? 0), 2).'</b></div>
                    </div>
                    <form method="POST" class="tm-rendicion-confirm-form formConfirmarRendicionCaja">
                      <input type="hidden" name="confirmarRendicionCompra" value="'.$idSolicitud.'">
                      <button type="submit" class="btn btn-success">
                        <i class="fa fa-check-circle"></i>
                        <span>Confirmar cambio y cerrar rendicion</span>
                      </button>
                    </form>
                  </div>';
    }

    $constancias = botonesReimpresionCompra($solicitud);
    if ($constancias !== "") {
        $html .= '<div class="tm-compra-constancias">
                    <div class="tm-compra-constancias-title">
                      <i class="fa fa-folder-open-o"></i>
                      <span>Documentos y constancias</span>
                    </div>
                    <div class="tm-compra-constancias-grid">'.$constancias.'</div>
                  </div>';
    }

    if (($_SESSION["perfil"] ?? "") == "Administrador") {
        $html .= '<div class="tm-compra-danger-zone">
                    <button type="button" class="btn btn-danger btnEliminarSolicitudCompra"
                      idSolicitud="'.$idSolicitud.'"
                      codigoSolicitud="'.tmSolicitudCompraEsc($solicitud["codigo"] ?? $idSolicitud).'"
                      estadoSolicitud="'.tmSolicitudCompraEsc(estadoSolicitudCompraTexto($estado)).'"
                      title="Eliminar solicitud de compra">
                      <i class="fa fa-trash"></i> Eliminar solicitud
                    </button>
                  </div>';
    }

    if ($html === "") {
        $html = '<span class="tm-compra-no-actions"><i class="fa fa-info-circle"></i> Sin acciones disponibles para este estado.</span>';
    }

    return $html;
}

function renderTarjetasSolicitudesCompra($solicitudes, $estados, $puedeAprobar) {
    $contador = 0;

    foreach ($solicitudes as $solicitud) {
        $estado = trim((string)$solicitud["estado"]);
        if (!in_array($estado, $estados)) {
            continue;
        }

        $contador++;
        $solicitante = datosUsuarioSolicitudCompra($solicitud["id_usuario"] ?? null, "Usuario no encontrado");
        $mensajero = datosUsuarioSolicitudCompra($solicitud["id_mensajero"] ?? null, "Sin tomar");
        $productos = productosSolicitudCompraData($solicitud["productos"] ?? "");
        $montoDesembolsado = (float)($solicitud["monto_desembolsado"] ?? 0);
        $totalFormateado = $montoDesembolsado > 0 ? "Bs ".number_format($montoDesembolsado, 2) : "Por definir";
        $estadoClase = estadoSolicitudCompraClase($estado);
        $estadoTexto = estadoSolicitudCompraTexto($estado);
        $nota = $solicitud["codigo"] ?? $solicitud["id"];
        $fecha = $solicitud["fecha"] ?? ($solicitud["fecha_creacion"] ?? "");
        $mensajeroNombre = $mensajero["nombre"];
        $mensajeroUsuario = $mensajero["usuario"];
        $costoReal = (float)($solicitud["costo_real_total"] ?? 0);
        $cambio = (float)($solicitud["cambio_calculado"] ?? 0);
        $rendicionHtml = rendicionSolicitudCompraHtml($solicitud["id"]);
        $busqueda = strtolower($nota." ".$solicitante["nombre"]." ".$solicitante["usuario"]." ".$mensajeroNombre." ".$mensajeroUsuario." ".$estadoTexto." ".$totalFormateado." ".strip_tags($productos["html"]));

        echo '<article class="tm-compra-card estado-'.$estadoClase.'" tabindex="0"
            data-search="'.tmSolicitudCompraEsc($busqueda).'"
            data-nota="'.tmSolicitudCompraEsc($nota).'"
            data-solicitante="'.tmSolicitudCompraEsc($solicitante["nombre"]).'"
            data-usuario="'.tmSolicitudCompraEsc($solicitante["usuario"]).'"
            data-mensajero="'.tmSolicitudCompraEsc($mensajeroNombre).'"
            data-mensajero-usuario="'.tmSolicitudCompraEsc($mensajeroUsuario).'"
            data-total="'.tmSolicitudCompraEsc($totalFormateado).'"
            data-estado="'.tmSolicitudCompraEsc($estadoTexto).'"
            data-estado-clase="'.tmSolicitudCompraEsc($estadoClase).'"
            data-fecha="'.tmSolicitudCompraEsc($fecha).'"
            data-costo-real="Bs '.number_format($costoReal, 2).'"
            data-cambio="Bs '.number_format($cambio, 2).'"
            data-factura="'.tmSolicitudCompraEsc($solicitud["factura_compra"] ?? "").'"
            data-items="'.(int)$productos["items"].'"
            data-unidades="'.tmSolicitudCompraEsc($productos["unidades"]).'">
            <div class="tm-compra-card-head">
              <div>
                <span class="tm-compra-code"><i class="fa fa-shopping-basket"></i> Nota '.tmSolicitudCompraEsc($nota).'</span>
                <h3>'.tmSolicitudCompraEsc($solicitante["nombre"]).'</h3>
              </div>
              '.etiquetaEstadoSolicitudCompra($estado).'
            </div>

            <div class="tm-compra-card-total">
              <strong>'.$totalFormateado.'</strong>
              <span>Monto desembolsado</span>
            </div>

            <div class="tm-compra-card-grid">
              <div><span>Productos</span><b>'.(int)$productos["items"].' item(s)</b></div>
              <div><span>Unidades</span><b>'.tmSolicitudCompraEsc($productos["unidades"]).'</b></div>
              <div><span>Mensajero</span><b>'.tmSolicitudCompraEsc($mensajeroNombre).'</b></div>
              <div><span>Fecha</span><b>'.tmSolicitudCompraEsc($fecha ?: "-").'</b></div>
              '.($costoReal > 0 ? '<div><span>Costo real</span><b>Bs '.number_format($costoReal, 2).'</b></div>
              <div><span>Cambio</span><b>Bs '.number_format($cambio, 2).'</b></div>' : '').'
            </div>

            <div class="tm-compra-card-foot">
              <span><i class="fa fa-mouse-pointer"></i> Ver detalle y acciones</span>
              <i class="fa fa-chevron-right"></i>
            </div>
            '.(($_SESSION["perfil"] ?? "") == "Administrador" ? '<div class="tm-compra-admin-actions">
              <button type="button" class="btn btn-danger btnEliminarSolicitudCompra"
                      idSolicitud="'.(int)$solicitud["id"].'"
                      codigoSolicitud="'.tmSolicitudCompraEsc($nota).'"
                      estadoSolicitud="'.tmSolicitudCompraEsc($estadoTexto).'">
                <i class="fa fa-trash"></i> Eliminar solicitud
              </button>
            </div>' : '').'

            <div class="tm-compra-products-template" style="display:none">'.$productos["html"].'</div>
            <div class="tm-compra-rendicion-template" style="display:none">'.$rendicionHtml.'</div>
            <div class="tm-compra-actions-template" style="display:none">'.accionesSolicitudCompra($solicitud, $puedeAprobar).'</div>
          </article>';
    }

    if ($contador === 0) {
        echo '<div class="tm-compra-empty">
                <i class="fa fa-inbox"></i>
                <strong>No hay solicitudes en esta pestana.</strong>
                <span>Cuando exista movimiento, aparecera aqui en forma de tarjeta.</span>
              </div>';
    }
}

$puedeAprobar = $_SESSION["perfil"] == "Administrador" || $_SESSION["rol"] == "cajero";
$solicitudes = ControladorCompras::ctrMostrarSolicitudesCompra();
$solicitudes = is_array($solicitudes) ? $solicitudes : array();
$solicitudesPendientes = array_values(array_filter($solicitudes, function($solicitud){
    return trim((string)$solicitud["estado"]) == "pendiente";
}));
$solicitudesEnDesembolso = array_values(array_filter($solicitudes, function($solicitud){
    return trim((string)$solicitud["estado"]) == "en_compra";
}));
$solicitudesEnProceso = array_values(array_filter($solicitudes, function($solicitud){
    return in_array(trim((string)$solicitud["estado"]), array("aprobado", "desembolsado", "compra_rendida", "entregado_almacen"));
}));
$solicitudesRendicionPendiente = array_values(array_filter($solicitudes, function($solicitud){
    return trim((string)$solicitud["estado"]) == "rendicion_pendiente";
}));
$solicitudesCompletadas = array_values(array_filter($solicitudes, function($solicitud){
    return trim((string)$solicitud["estado"]) == "completado";
}));
$solicitudesRechazadas = array_values(array_filter($solicitudes, function($solicitud){
    return trim((string)$solicitud["estado"]) == "rechazado";
}));

$totalPendientesCompra = array_sum(array_map(function($solicitud){
    return (float)($solicitud["total"] ?? 0);
}, $solicitudesPendientes));

$totalEnProcesoCompra = array_sum(array_map(function($solicitud){
    return (float)($solicitud["total"] ?? 0);
}, $solicitudesEnProceso));
$totalEnDesembolsoCompra = array_sum(array_map(function($solicitud){
    return (float)($solicitud["monto_desembolsado"] ?? 0);
}, $solicitudesEnDesembolso));
$totalRendicionPendienteCompra = array_sum(array_map(function($solicitud){
    return (float)($solicitud["costo_real_total"] ?? 0);
}, $solicitudesRendicionPendiente));

$totalCompletadasCompra = array_sum(array_map(function($solicitud){
    return (float)($solicitud["total"] ?? 0);
}, $solicitudesCompletadas));
?>

<div class="content-wrapper solicitudes-compra-wrapper">
  <style>
    .solicitudes-compra-panel{
      border:1px solid rgba(184,205,232,.68);
      border-radius:15px;
      background:rgba(255,255,255,.70);
      box-shadow:0 18px 40px rgba(15,23,42,.08);
      overflow:hidden;
    }
    .solicitudes-compra-panel .nav-tabs{
      border-bottom:1px solid rgba(184,205,232,.62);
      padding:0 14px;
      background:rgba(255,255,255,.62);
    }
    .solicitudes-compra-panel .nav-tabs>li>a{
      border:0;
      border-radius:0;
      color:#52657a;
      font-weight:850;
      padding:14px 16px;
    }
    .solicitudes-compra-panel .nav-tabs>li.active>a,
    .solicitudes-compra-panel .nav-tabs>li.active>a:hover,
    .solicitudes-compra-panel .nav-tabs>li.active>a:focus{
      border:0;
      border-bottom:3px solid #3c8dbc;
      color:#173b5d;
      background:transparent;
    }
    .solicitudes-compra-panel .tab-content{padding:14px;}
    .tm-compra-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(255px, 1fr));
      gap:12px;
    }
    .tm-compra-card{
      position:relative;
      min-height:232px;
      border:1px solid rgba(184,205,232,.72);
      border-radius:13px;
      background:rgba(255,255,255,.84);
      padding:12px;
      display:flex;
      flex-direction:column;
      gap:10px;
      cursor:pointer;
      overflow:hidden;
      box-shadow:0 14px 30px rgba(15,23,42,.07);
      transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .tm-compra-card:before{
      content:"";
      position:absolute;
      inset:0 auto 0 0;
      width:5px;
      background:#9aa8b6;
    }
    .tm-compra-card.estado-primary:before{background:#3c8dbc;}
    .tm-compra-card.estado-warning:before{background:#f39c12;}
    .tm-compra-card.estado-info:before{background:#00c0ef;}
    .tm-compra-card.estado-success:before{background:#00a65a;}
    .tm-compra-card.estado-danger:before{background:#dd4b39;}
    .tm-compra-card:hover{
      transform:translateY(-2px);
      border-color:#3c8dbc;
      box-shadow:0 20px 38px rgba(15,23,42,.13);
    }
    .tm-compra-card-head{
      display:flex;
      justify-content:space-between;
      gap:10px;
      align-items:flex-start;
      padding-left:4px;
    }
    .tm-compra-code{
      display:inline-flex;
      align-items:center;
      gap:6px;
      color:#176b9b;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
      letter-spacing:.02em;
    }
    .tm-compra-card h3{
      margin:5px 0 0;
      color:#1f2d3d;
      font-size:15px;
      font-weight:900;
      line-height:1.22;
      overflow-wrap:anywhere;
    }
    .tm-compra-status{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      max-width:118px;
      min-height:24px;
      white-space:normal;
      line-height:1.15;
      padding:5px 8px;
      border-radius:999px;
      font-size:10px;
      font-weight:900;
      text-align:center;
      color:#fff !important;
      background:#9aa8b6;
      text-transform:uppercase;
    }
    .tm-compra-status.status-primary{background:#3c8dbc;}
    .tm-compra-status.status-warning{background:#f39c12;}
    .tm-compra-status.status-info{background:#00a7d0;}
    .tm-compra-status.status-success{background:#00a65a;}
    .tm-compra-status.status-danger{background:#dd4b39;}
    .tm-compra-card-total{
      border-radius:12px;
      background:linear-gradient(135deg, rgba(60,141,188,.14), rgba(0,192,239,.08));
      padding:10px 12px;
      display:flex;
      align-items:flex-end;
      justify-content:space-between;
      gap:8px;
    }
    .tm-compra-card-total strong{
      color:#0b4e78;
      font-size:20px;
      font-weight:950;
    }
    .tm-compra-card-total span{
      color:#60748b;
      font-size:10.5px;
      font-weight:800;
      text-transform:uppercase;
    }
    .tm-compra-card-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:8px;
      flex:1;
    }
    .tm-compra-card-grid div{
      border:1px solid rgba(184,205,232,.58);
      border-radius:10px;
      background:rgba(248,251,255,.72);
      padding:8px;
      min-width:0;
    }
    .tm-compra-card-grid span{
      display:block;
      color:#718299;
      font-size:9.5px;
      font-weight:900;
      text-transform:uppercase;
    }
    .tm-compra-card-grid b{
      display:block;
      margin-top:3px;
      color:#25364a;
      font-size:12px;
      line-height:1.2;
      overflow-wrap:anywhere;
    }
    .tm-compra-card-foot{
      display:flex;
      justify-content:space-between;
      align-items:center;
      color:#176b9b;
      font-size:11px;
      font-weight:900;
      border-top:1px dashed rgba(184,205,232,.75);
      padding-top:9px;
    }
    .tm-compra-admin-actions{
      position:relative;
      z-index:2;
    }
    .tm-compra-admin-actions .btn{
      width:100%;
      border-radius:9px;
      padding:8px 10px;
      font-size:11px;
      font-weight:900;
      box-shadow:0 7px 14px rgba(221,75,57,.14);
    }
    .tm-compra-empty{
      min-height:180px;
      border:1px dashed rgba(60,141,188,.35);
      border-radius:14px;
      color:#6d7f93;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:7px;
      background:rgba(255,255,255,.56);
      text-align:center;
    }
    .tm-compra-empty i{font-size:28px;color:#3c8dbc;}
    .tm-compra-modal .modal-dialog{width:min(900px, calc(100vw - 28px));}
    .tm-compra-modal .modal-content{
      border:0;
      border-radius:18px;
      overflow:hidden;
      box-shadow:0 28px 70px rgba(15,23,42,.28);
    }
    .tm-compra-modal .modal-header{
      position:relative;
      border:0;
      color:#fff;
      background:linear-gradient(135deg,#12384f,#3c8dbc);
      padding:18px 22px;
    }
    .tm-compra-modal .modal-header:after{
      content:"";
      position:absolute;
      width:155px;
      height:155px;
      border-radius:50%;
      right:-46px;
      top:-64px;
      background:rgba(255,255,255,.13);
    }
    .tm-compra-modal .modal-title{
      font-size:22px;
      font-weight:950;
      line-height:1.15;
      position:relative;
      z-index:1;
    }
    .tm-compra-modal .modal-title small{
      display:block;
      margin-top:5px;
      color:rgba(255,255,255,.85);
      font-size:12px;
      font-weight:800;
    }
    .tm-compra-modal .close{
      position:relative;
      z-index:2;
      color:#fff;
      opacity:.9;
      text-shadow:none;
    }
    .tm-compra-modal-body{padding:16px;background:#f5f8fc;}
    .tm-compra-detail-grid{
      display:grid;
      grid-template-columns:repeat(3, 1fr);
      gap:10px;
      margin-bottom:12px;
    }
    .tm-compra-detail-box{
      border:1px solid rgba(184,205,232,.76);
      border-radius:12px;
      background:#fff;
      padding:10px 12px;
      min-width:0;
    }
    .tm-compra-detail-box span{
      display:block;
      color:#728299;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .tm-compra-detail-box strong{
      display:block;
      margin-top:4px;
      color:#203047;
      font-size:13px;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .tm-compra-section{
      border:1px solid rgba(184,205,232,.76);
      border-radius:14px;
      background:#fff;
      padding:12px;
      margin-bottom:12px;
    }
    .tm-compra-section h4{
      margin:0 0 10px;
      color:#203047;
      font-size:14px;
      font-weight:950;
    }
    .tm-compra-producto{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      border:1px solid rgba(184,205,232,.60);
      border-radius:11px;
      padding:9px 10px;
      margin-bottom:8px;
      background:rgba(248,251,255,.85);
    }
    .tm-compra-producto:last-child{margin-bottom:0;}
    .tm-compra-producto strong{
      color:#23344a;
      font-size:13px;
      line-height:1.25;
    }
    .tm-compra-producto span{
      display:block;
      color:#718299;
      font-size:10px;
      font-weight:800;
      text-transform:uppercase;
      margin-top:2px;
    }
    .tm-compra-producto b{
      color:#0b4e78;
      font-size:14px;
      white-space:nowrap;
    }
    .tm-compra-actions{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
    }
    .tm-compra-actions .btn,
    .tm-compra-action-form .btn{
      border-radius:8px;
      font-weight:850;
      white-space:normal;
      line-height:1.15;
    }
    .tm-compra-action-form{display:inline-flex;margin:0;}
    .tm-compra-no-actions{
      color:#7b8794;
      font-weight:800;
      padding:8px 0;
    }
    body.tm-dark-mode .solicitudes-compra-panel,
    body.dark-mode .solicitudes-compra-panel{
      background:rgba(15,23,42,.72);
      border-color:rgba(99,135,184,.45);
    }
    body.tm-dark-mode .tm-compra-card,
    body.dark-mode .tm-compra-card{
      background:rgba(15,23,42,.76);
      border-color:rgba(99,135,184,.48);
    }
    body.tm-dark-mode .tm-compra-card h3,
    body.tm-dark-mode .tm-compra-card-grid b,
    body.dark-mode .tm-compra-card h3,
    body.dark-mode .tm-compra-card-grid b{color:#f8fbff;}
    body.tm-dark-mode .tm-compra-card-grid div,
    body.dark-mode .tm-compra-card-grid div{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.12);}
    @media (max-width: 767px){
      .tm-compra-grid{grid-template-columns:1fr;}
      .tm-compra-detail-grid{grid-template-columns:1fr;}
      .solicitudes-compra-panel .nav-tabs>li>a{padding:11px 9px;font-size:12px;}
    }
    .solicitudes-compra-wrapper .content{padding-top:10px;}
    .tm-compra-hero{
      position:relative;
      margin-bottom:14px;
      padding:18px 20px;
      border:1px solid rgba(184,205,232,.62);
      border-radius:18px;
      color:#fff;
      background:linear-gradient(135deg, rgba(16,43,59,.94), rgba(23,107,155,.86));
      box-shadow:0 18px 38px rgba(15,23,42,.12);
      overflow:hidden;
    }
    .tm-compra-hero:after{
      content:"";
      position:absolute;
      right:-64px;
      top:-82px;
      width:220px;
      height:220px;
      border-radius:50%;
      background:rgba(255,255,255,.12);
    }
    .tm-compra-hero h2{
      position:relative;
      z-index:1;
      margin:0 0 5px;
      font-size:24px;
      font-weight:950;
    }
    .tm-compra-hero p{
      position:relative;
      z-index:1;
      margin:0;
      max-width:820px;
      color:rgba(255,255,255,.86);
      font-size:13px;
      font-weight:750;
      line-height:1.35;
    }
    .tm-compra-kpis{
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(165px, 1fr));
      gap:10px;
      margin-bottom:12px;
    }
    .tm-compra-kpi{
      display:flex;
      align-items:center;
      gap:10px;
      min-width:0;
      border:1px solid rgba(184,205,232,.66);
      border-radius:15px;
      background:rgba(255,255,255,.70);
      padding:12px;
      box-shadow:0 12px 26px rgba(15,23,42,.06);
    }
    .tm-compra-kpi i{
      width:36px;
      height:36px;
      border-radius:12px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      background:linear-gradient(135deg,#176b9b,#36aee2);
      box-shadow:0 8px 18px rgba(23,107,155,.22);
      flex:0 0 auto;
    }
    .tm-compra-kpi span{
      display:block;
      color:#6b7d91;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .tm-compra-kpi strong{
      display:block;
      margin-top:2px;
      color:#162235;
      font-size:17px;
      font-weight:950;
      line-height:1.15;
      overflow-wrap:anywhere;
    }
    .solicitudes-compra-panel{
      border-radius:17px;
      background:rgba(255,255,255,.62);
    }
    .tm-compra-toolbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      padding:12px;
      border-bottom:1px solid rgba(184,205,232,.58);
      background:rgba(255,255,255,.45);
    }
    .tm-compra-toolbar h3{
      margin:0;
      color:#1d2b3d;
      font-size:16px;
      font-weight:950;
    }
    .tm-compra-search{
      position:relative;
      width:min(380px, 100%);
    }
    .tm-compra-search i{
      position:absolute;
      left:12px;
      top:50%;
      transform:translateY(-50%);
      color:#7b8fa5;
    }
    .tm-compra-search input{
      width:100%;
      height:36px;
      border:1px solid rgba(184,205,232,.82);
      border-radius:11px;
      background:rgba(255,255,255,.80);
      padding:0 12px 0 34px;
      outline:none;
      color:#25364a;
      font-weight:850;
    }
    .solicitudes-compra-panel .nav-tabs{
      padding:0 12px;
      background:rgba(255,255,255,.38);
    }
    .solicitudes-compra-panel .nav-tabs>li>a{
      padding:12px 14px;
      font-weight:900;
    }
    .solicitudes-compra-panel .tab-content{padding:12px;}
    .tm-compra-grid{
      grid-template-columns:repeat(auto-fill, minmax(218px, 1fr));
      gap:10px;
    }
    .tm-compra-card{
      min-height:214px;
      border-radius:14px;
      padding:11px;
      gap:8px;
      background:rgba(255,255,255,.82);
    }
    .tm-compra-card-head{
      gap:8px;
      padding-left:3px;
    }
    .tm-compra-code{
      font-size:10px;
      letter-spacing:0;
    }
    .tm-compra-card h3{
      margin:4px 0 0;
      font-size:14px;
      font-weight:950;
    }
    .tm-compra-status{
      max-width:96px;
      min-height:22px;
      padding:4px 7px;
      font-size:8.8px;
    }
    .tm-compra-card-total{
      border-radius:10px;
      padding:8px 10px;
    }
    .tm-compra-card-total strong{font-size:18px;}
    .tm-compra-card-total span{font-size:9.5px;}
    .tm-compra-card-grid{gap:6px;}
    .tm-compra-card-grid div{
      border-radius:10px;
      padding:6px 7px;
    }
    .tm-compra-card-grid span{font-size:8.8px;}
    .tm-compra-card-grid b{font-size:10.5px;}
    .tm-compra-card-foot{
      padding-top:7px;
      font-size:10px;
    }
    .tm-compra-empty{grid-column:1 / -1;}
    .tm-compra-modal .modal-dialog{width:min(820px, calc(100vw - 36px));}
    .tm-compra-modal .modal-content{border-radius:18px;}
    .tm-compra-modal .modal-header{
      padding:14px 18px;
      background:linear-gradient(135deg,#176b9b,#36aee2);
    }
    .tm-compra-modal .modal-title{
      display:flex;
      align-items:center;
      gap:10px;
      font-size:20px;
    }
    .tm-compra-modal-icon{
      width:40px;
      height:40px;
      border-radius:13px;
      background:rgba(255,255,255,.18);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:18px;
      flex:0 0 auto;
    }
    .tm-compra-modal .modal-title small{
      margin-top:3px;
      font-size:11px;
    }
    .tm-compra-modal-body{padding:13px;}
    .tm-compra-detail-grid{
      gap:8px;
      margin-bottom:10px;
    }
    .tm-compra-detail-box{
      border-radius:10px;
      padding:8px 10px;
      min-height:58px;
    }
    .tm-compra-detail-box span{font-size:9px;}
    .tm-compra-detail-box strong{font-size:12px;}
    .tm-compra-section{
      border-radius:13px;
      padding:10px;
      margin-bottom:10px;
    }
    .tm-compra-producto{
      border-radius:10px;
      padding:8px 9px;
      margin-bottom:7px;
    }
    .tm-compra-actions .btn,
    .tm-compra-action-form .btn{
      border-radius:9px;
      padding:6px 9px;
      font-size:12px;
      font-weight:900;
    }
    body.tm-dark-mode .tm-compra-kpi,
    body.dark-mode .tm-compra-kpi,
    body.tm-dark-mode .tm-compra-toolbar,
    body.dark-mode .tm-compra-toolbar{
      background:rgba(15,23,42,.58);
      border-color:rgba(99,135,184,.45);
    }
    body.tm-dark-mode .tm-compra-toolbar h3,
    body.dark-mode .tm-compra-toolbar h3,
    body.tm-dark-mode .tm-compra-kpi strong,
    body.dark-mode .tm-compra-kpi strong{color:#f8fbff;}
    @media (max-width: 991px){
      .tm-compra-kpis{grid-template-columns:repeat(2, minmax(0, 1fr));}
    }
    @media (max-width: 767px){
      .tm-compra-kpis{grid-template-columns:1fr;}
      .tm-compra-toolbar{align-items:flex-start;flex-direction:column;}
    }
    .tm-compra-modal-redesign .modal-dialog{
      width:min(900px, calc(100vw - 34px));
      margin-top:30px;
    }
    .tm-compra-modal-redesign .modal-content{
      border:0;
      border-radius:22px;
      overflow:hidden;
      background:#eef5fb;
      box-shadow:0 30px 80px rgba(15,23,42,.32);
    }
    .tm-compra-modal-redesign .modal-header{
      position:relative;
      padding:0;
      border:0;
      color:#fff;
      background:linear-gradient(135deg,#12384f 0%,#176b9b 100%);
      overflow:hidden;
    }
    .tm-compra-modal-redesign .modal-header:before{
      content:"";
      position:absolute;
      width:170px;
      height:170px;
      border-radius:50%;
      right:-58px;
      top:-82px;
      background:rgba(255,255,255,.10);
    }
    .tm-compra-modal-redesign .modal-header:after{
      content:"";
      position:absolute;
      width:90px;
      height:90px;
      border-radius:50%;
      left:42%;
      bottom:-64px;
      background:rgba(255,255,255,.06);
    }
    .tm-compra-modal-close{
      position:absolute;
      z-index:3;
      right:16px;
      top:12px;
      width:30px;
      height:30px;
      border:0;
      border-radius:50%;
      background:rgba(255,255,255,.16);
      color:#fff;
      opacity:1;
      text-shadow:none;
      font-size:20px;
      line-height:30px;
    }
    .tm-compra-modal-hero{
      position:relative;
      z-index:1;
      display:grid;
      grid-template-columns:minmax(0,1fr) 170px;
      gap:12px;
      align-items:center;
      padding:14px 58px 12px 18px;
    }
    .tm-compra-modal-title{
      display:flex;
      gap:10px;
      align-items:center;
      min-width:0;
    }
    .tm-compra-modal-icon{
      width:40px;
      height:40px;
      border-radius:12px;
      display:flex;
      align-items:center;
      justify-content:center;
      flex:0 0 auto;
      background:rgba(255,255,255,.18);
      box-shadow:inset 0 0 0 1px rgba(255,255,255,.16);
      font-size:18px;
    }
    .tm-compra-modal-title h3{
      margin:0;
      font-size:20px;
      font-weight:950;
      line-height:1.08;
    }
    .tm-compra-modal-title small{
      display:block;
      margin-top:3px;
      color:rgba(255,255,255,.82);
      font-weight:800;
      font-size:11px;
    }
    .tm-compra-modal-total{
      border-radius:13px;
      padding:9px 12px;
      background:rgba(255,255,255,.13);
      box-shadow:inset 0 0 0 1px rgba(255,255,255,.16);
      text-align:right;
    }
    .tm-compra-modal-total span{
      display:block;
      text-transform:uppercase;
      font-size:9px;
      letter-spacing:.04em;
      font-weight:900;
      color:rgba(255,255,255,.72);
    }
    .tm-compra-modal-total strong{
      display:block;
      margin-top:2px;
      font-size:20px;
      font-weight:950;
      color:#fff;
    }
    .tm-compra-modal-status-row{
      position:relative;
      z-index:1;
      display:flex;
      align-items:center;
      justify-content:flex-start;
      gap:12px;
      padding:0 58px 14px 18px;
      flex-wrap:wrap;
    }
    .tm-compra-modal-status-row .tm-compra-status{
      max-width:none;
      border-radius:10px;
      padding:6px 10px;
      font-size:10px;
      box-shadow:0 8px 18px rgba(0,0,0,.14);
    }
    .tm-compra-modal-flow{
      display:flex;
      gap:6px;
      align-items:center;
      flex-wrap:wrap;
      color:rgba(255,255,255,.82);
      font-size:10px;
      font-weight:900;
    }
    .tm-compra-modal-flow span{
      display:inline-flex;
      align-items:center;
      gap:5px;
      padding:5px 8px;
      border-radius:999px;
      background:rgba(255,255,255,.12);
    }
    .tm-compra-modal-body{
      padding:16px;
      background:linear-gradient(180deg,#f6fafe,#edf4fa);
    }
    .tm-compra-modal-layout{
      display:grid;
      grid-template-columns:310px minmax(0,1fr);
      gap:14px;
    }
    .tm-compra-modal-panel{
      border:1px solid rgba(184,205,232,.78);
      border-radius:18px;
      background:rgba(255,255,255,.88);
      padding:14px;
      box-shadow:0 14px 28px rgba(15,23,42,.06);
    }
    .tm-compra-modal-panel h4{
      display:flex;
      align-items:center;
      gap:8px;
      margin:0 0 12px;
      color:#173b5d;
      font-size:15px;
      font-weight:950;
    }
    .tm-compra-modal-panel h4 i{
      width:30px;
      height:30px;
      border-radius:10px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      background:#e8f3fb;
      color:#176b9b;
    }
    .tm-compra-person-card{
      border:1px solid rgba(184,205,232,.72);
      border-radius:14px;
      background:#f8fbff;
      padding:11px 12px;
      margin-bottom:10px;
    }
    .tm-compra-person-card span,
    .tm-compra-metric span{
      display:block;
      color:#70849a;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
    }
    .tm-compra-person-card strong,
    .tm-compra-metric strong{
      display:block;
      margin-top:4px;
      color:#203047;
      font-size:13px;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .tm-compra-metrics{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:10px;
    }
    .tm-compra-metric{
      border:1px solid rgba(184,205,232,.72);
      border-radius:14px;
      background:#f8fbff;
      padding:11px 12px;
      min-width:0;
    }
    .tm-compra-modal-products{
      max-height:280px;
      overflow-y:auto;
      padding-right:4px;
    }
    .tm-compra-modal-products .tm-compra-producto{
      border-radius:14px;
      padding:10px 12px;
      background:#f8fbff;
    }
    .tm-compra-modal-products .tm-compra-producto strong{
      font-size:13px;
    }
    .tm-compra-modal-actions-wrap{
      margin-top:14px;
    }
    .tm-compra-modal-actions-wrap .tm-compra-actions{
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));
      gap:9px;
    }
    .tm-compra-modal-actions-wrap .tm-compra-action-form,
    .tm-compra-modal-actions-wrap .tm-compra-actions .btn{
      width:100%;
    }
    .tm-compra-modal-actions-wrap .tm-desembolso-form{
      grid-column:1/-1;
      display:grid;
      grid-template-columns:minmax(150px,.8fr) minmax(220px,1.2fr);
      align-items:end;
      gap:12px;
      padding:13px;
      border:1px solid rgba(27,116,178,.20);
      border-radius:16px;
      background:linear-gradient(135deg,rgba(232,245,255,.92),rgba(255,255,255,.98));
      box-shadow:0 10px 24px rgba(20,89,139,.08);
    }
    .tm-desembolso-copy span{
      display:flex;
      align-items:center;
      gap:7px;
      color:#164e78;
      font-size:13px;
      font-weight:950;
    }
    .tm-desembolso-copy small{
      display:block;
      margin-top:5px;
      color:#687f93;
      font-size:10.5px;
      font-weight:750;
      line-height:1.3;
    }
    .tm-desembolso-control label{
      display:block;
      margin:0 0 6px;
      color:#29445d;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
    }
    .tm-desembolso-control .input-group{width:100%;}
    .tm-desembolso-control .input-group-addon{
      min-width:48px;
      border-color:#bdd7ea;
      border-radius:11px 0 0 11px;
      background:#e8f3fb;
      color:#155b8a;
      font-size:14px;
      font-weight:950;
    }
    .tm-desembolso-control .form-control{
      width:100%;
      height:42px;
      border-color:#bdd7ea;
      border-radius:0 11px 11px 0;
      background:#fff;
      color:#162f46;
      font-size:17px;
      font-weight:900;
      text-align:right;
      box-shadow:none;
    }
    .tm-desembolso-control .form-control:focus{
      border-color:#2795d0;
      box-shadow:0 0 0 3px rgba(39,149,208,.12);
    }
    .tm-compra-modal-actions-wrap .tm-desembolso-submit{
      grid-column:1/-1;
      width:100%;
      min-height:42px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:7px;
      background:linear-gradient(135deg,#176ea8,#168fd0);
      border-color:#176ea8;
      white-space:nowrap;
    }
    .tm-compra-modal-actions-wrap .btn{
      min-height:38px;
      border-radius:12px;
      font-size:12px;
      font-weight:950;
      white-space:normal;
      box-shadow:0 8px 16px rgba(15,23,42,.08);
    }
    .tm-rendicion-caja-card{
      grid-column:1/-1;
      padding:14px;
      border:1px solid rgba(22,143,208,.25);
      border-radius:16px;
      background:linear-gradient(135deg,#eef9ff,#fff);
      box-shadow:0 12px 25px rgba(20,89,139,.08);
    }
    .tm-rendicion-caja-head{
      display:flex;
      align-items:center;
      gap:10px;
      margin-bottom:12px;
    }
    .tm-rendicion-caja-icon{
      width:40px;
      height:40px;
      border-radius:13px;
      display:flex;
      align-items:center;
      justify-content:center;
      flex:0 0 auto;
      color:#fff;
      background:linear-gradient(135deg,#176ea8,#18a9dc);
      font-size:17px;
    }
    .tm-rendicion-caja-head strong{
      display:block;
      color:#173b5d;
      font-size:14px;
      font-weight:950;
    }
    .tm-rendicion-caja-head small{
      display:block;
      margin-top:3px;
      color:#698097;
      font-size:10.5px;
      font-weight:750;
    }
    .tm-rendicion-caja-values{
      display:grid;
      grid-template-columns:repeat(3,minmax(0,1fr));
      gap:8px;
      margin-bottom:11px;
    }
    .tm-rendicion-caja-values div{
      padding:10px;
      border:1px solid #d9e9f5;
      border-radius:12px;
      background:#fff;
      min-width:0;
    }
    .tm-rendicion-caja-values div.cambio{
      border-color:#b9ead3;
      background:#effbf5;
    }
    .tm-rendicion-caja-values span{
      display:block;
      color:#71859a;
      font-size:9px;
      font-weight:950;
      text-transform:uppercase;
    }
    .tm-rendicion-caja-values b{
      display:block;
      margin-top:4px;
      color:#173b5d;
      font-size:16px;
      font-weight:950;
    }
    .tm-rendicion-caja-values .cambio b{color:#078454;}
    .tm-rendicion-confirm-form{display:block;width:100%;margin:0;}
    .tm-rendicion-confirm-form .btn{
      width:100%;
      min-height:43px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:7px;
      background:linear-gradient(135deg,#0b9b61,#17b978);
      border-color:#0b9b61;
    }
    .tm-compra-constancias{
      grid-column:1/-1;
      padding:12px;
      border:1px solid #dfeaf4;
      border-radius:15px;
      background:#f8fbfe;
    }
    .tm-compra-constancias-title{
      display:flex;
      align-items:center;
      gap:7px;
      margin-bottom:9px;
      color:#506b84;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
    }
    .tm-compra-constancias-grid{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(125px,1fr));
      gap:8px;
    }
    .tm-compra-constancias-grid .btn{
      width:100%;
      margin:0;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:6px;
      color:#31516d;
      background:#fff;
      border-color:#d5e3ef;
      box-shadow:none;
    }
    .tm-compra-danger-zone{
      grid-column:1/-1;
      padding-top:10px;
      border-top:1px dashed #efc2bd;
    }
    .tm-compra-danger-zone .btn{width:100%;box-shadow:none;}
    body.tm-dark-mode .tm-compra-modal-redesign .modal-content,
    body.dark-mode .tm-compra-modal-redesign .modal-content{
      background:#0f172a;
    }
    body.tm-dark-mode .tm-compra-modal-body,
    body.dark-mode .tm-compra-modal-body{
      background:linear-gradient(180deg,#111c32,#0f172a);
    }
    body.tm-dark-mode .tm-compra-modal-panel,
    body.tm-dark-mode .tm-compra-person-card,
    body.tm-dark-mode .tm-compra-metric,
    body.dark-mode .tm-compra-modal-panel,
    body.dark-mode .tm-compra-person-card,
    body.dark-mode .tm-compra-metric{
      background:rgba(15,23,42,.84);
      border-color:rgba(99,135,184,.42);
    }
    body.tm-dark-mode .tm-compra-modal-panel h4,
    body.tm-dark-mode .tm-compra-person-card strong,
    body.tm-dark-mode .tm-compra-metric strong,
    body.dark-mode .tm-compra-modal-panel h4,
    body.dark-mode .tm-compra-person-card strong,
    body.dark-mode .tm-compra-metric strong{
      color:#f8fbff;
    }
    body.tm-dark-mode .tm-desembolso-form,
    body.dark-mode .tm-desembolso-form{
      background:linear-gradient(135deg,rgba(18,51,78,.96),rgba(20,37,61,.96));
      border-color:rgba(89,175,227,.28);
    }
    body.tm-dark-mode .tm-desembolso-copy span,
    body.dark-mode .tm-desembolso-copy span,
    body.tm-dark-mode .tm-desembolso-control label,
    body.dark-mode .tm-desembolso-control label{
      color:#d9efff;
    }
    @media (max-width: 900px){
      .tm-compra-modal-hero,
      .tm-compra-modal-layout{
        grid-template-columns:1fr;
      }
      .tm-compra-modal-total{
        text-align:left;
      }
      .tm-compra-modal-redesign .modal-dialog{
        width:calc(100vw - 18px);
        margin-top:10px;
      }
      .tm-compra-modal-actions-wrap .tm-desembolso-form{
        grid-template-columns:1fr;
        align-items:stretch;
      }
      .tm-rendicion-caja-values{
        grid-template-columns:1fr;
      }
    }
  </style>

  <section class="content-header">
    <h1>Solicitudes de Compra</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Solicitudes de compra</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-compra-hero">
      <h2><i class="fa fa-shopping-basket"></i> Control de solicitudes de compra</h2>
      <p>Revise solicitudes pendientes, aprobaciones, desembolsos y constancias de compra desde una vista compacta por tarjetas.</p>
    </div>

    <div class="tm-compra-kpis">
      <div class="tm-compra-kpi">
        <i class="fa fa-clock-o"></i>
        <div><span>Pendientes</span><strong><?php echo count($solicitudesPendientes); ?> / Bs <?php echo number_format($totalPendientesCompra, 2); ?></strong></div>
      </div>
      <div class="tm-compra-kpi">
        <i class="fa fa-money"></i>
        <div><span>En desembolso</span><strong><?php echo count($solicitudesEnDesembolso); ?> esperando caja</strong></div>
      </div>
      <div class="tm-compra-kpi">
        <i class="fa fa-refresh"></i>
        <div><span>En proceso</span><strong><?php echo count($solicitudesEnProceso); ?> / Bs <?php echo number_format($totalEnProcesoCompra, 2); ?></strong></div>
      </div>
      <div class="tm-compra-kpi">
        <i class="fa fa-file-text-o"></i>
        <div><span>Rendicion pendiente</span><strong><?php echo count($solicitudesRendicionPendiente); ?> / Bs <?php echo number_format($totalRendicionPendienteCompra, 2); ?></strong></div>
      </div>
      <div class="tm-compra-kpi">
        <i class="fa fa-check-circle"></i>
        <div><span>Completadas</span><strong><?php echo count($solicitudesCompletadas); ?> / Bs <?php echo number_format($totalCompletadasCompra, 2); ?></strong></div>
      </div>
      <div class="tm-compra-kpi">
        <i class="fa fa-ban"></i>
        <div><span>Rechazadas</span><strong><?php echo count($solicitudesRechazadas); ?> solicitud(es)</strong></div>
      </div>
    </div>

    <div class="solicitudes-compra-panel">
      <div class="tm-compra-toolbar">
        <h3><i class="fa fa-list-alt"></i> Seguimiento de compras</h3>
        <div class="tm-compra-search">
          <i class="fa fa-search"></i>
          <input type="text" id="buscarSolicitudesCompraCards" placeholder="Buscar nota, usuario, mensajero, producto o estado">
        </div>
      </div>
      <ul class="nav nav-tabs">
        <li class="active"><a href="#tabSolicitudesPendientes" data-toggle="tab">Pendientes <span class="badge bg-yellow"><?php echo count($solicitudesPendientes); ?></span></a></li>
        <li><a href="#tabSolicitudesDesembolso" data-toggle="tab">En desembolso <span class="badge bg-aqua"><?php echo count($solicitudesEnDesembolso); ?></span></a></li>
        <li><a href="#tabSolicitudesProceso" data-toggle="tab">En proceso <span class="badge bg-blue"><?php echo count($solicitudesEnProceso); ?></span></a></li>
        <li><a href="#tabSolicitudesRendicion" data-toggle="tab">Rendicion pendiente <span class="badge bg-yellow"><?php echo count($solicitudesRendicionPendiente); ?></span></a></li>
        <li><a href="#tabSolicitudesCompletadas" data-toggle="tab">Completadas con exito <span class="badge bg-green"><?php echo count($solicitudesCompletadas); ?></span></a></li>
        <li><a href="#tabSolicitudesRechazadas" data-toggle="tab">Rechazadas <span class="badge bg-red"><?php echo count($solicitudesRechazadas); ?></span></a></li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane active" id="tabSolicitudesPendientes">
          <div class="tm-compra-grid">
            <?php renderTarjetasSolicitudesCompra($solicitudes, array("pendiente"), $puedeAprobar); ?>
          </div>
        </div>
        <div class="tab-pane" id="tabSolicitudesDesembolso">
          <div class="tm-compra-grid">
            <?php renderTarjetasSolicitudesCompra($solicitudes, array("en_compra"), $puedeAprobar); ?>
          </div>
        </div>
        <div class="tab-pane" id="tabSolicitudesProceso">
          <div class="tm-compra-grid">
            <?php renderTarjetasSolicitudesCompra($solicitudes, array("aprobado", "desembolsado", "compra_rendida", "entregado_almacen"), $puedeAprobar); ?>
          </div>
        </div>
        <div class="tab-pane" id="tabSolicitudesRendicion">
          <div class="tm-compra-grid">
            <?php renderTarjetasSolicitudesCompra($solicitudes, array("rendicion_pendiente"), $puedeAprobar); ?>
          </div>
        </div>
        <div class="tab-pane" id="tabSolicitudesCompletadas">
          <div class="tm-compra-grid">
            <?php renderTarjetasSolicitudesCompra($solicitudes, array("completado"), $puedeAprobar); ?>
          </div>
        </div>
        <div class="tab-pane" id="tabSolicitudesRechazadas">
          <div class="tm-compra-grid">
            <?php renderTarjetasSolicitudesCompra($solicitudes, array("rechazado"), $puedeAprobar); ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div id="modalDetalleSolicitudCompra" class="modal fade tm-compra-modal tm-compra-modal-redesign" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close tm-compra-modal-close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
        <div class="tm-compra-modal-hero">
          <div class="tm-compra-modal-title">
            <span class="tm-compra-modal-icon"><i class="fa fa-shopping-basket"></i></span>
            <div>
              <h3>Solicitud <span id="modalCompraNota"></span></h3>
              <small id="modalCompraResumen"></small>
            </div>
          </div>
          <div class="tm-compra-modal-total">
            <span>Monto / desembolso</span>
            <strong id="modalCompraTotal"></strong>
          </div>
        </div>
        <div class="tm-compra-modal-status-row">
          <div id="modalCompraEstado"></div>
          <div class="tm-compra-modal-flow">
            <span><i class="fa fa-check-circle-o"></i> Aprobacion</span>
            <span><i class="fa fa-money"></i> Desembolso</span>
            <span><i class="fa fa-truck"></i> Compra</span>
            <span><i class="fa fa-archive"></i> Almacen</span>
          </div>
        </div>
      </div>
      <div class="tm-compra-modal-body">
        <div class="tm-compra-modal-layout">
          <aside class="tm-compra-modal-panel">
            <h4><i class="fa fa-users"></i> Responsables</h4>
            <div class="tm-compra-person-card">
              <span>Solicitante</span>
              <strong id="modalCompraSolicitante"></strong>
            </div>
            <div class="tm-compra-person-card">
              <span>Mensajero asignado</span>
              <strong id="modalCompraMensajero"></strong>
            </div>
            <div class="tm-compra-metrics">
              <div class="tm-compra-metric">
                <span>Fecha</span>
                <strong id="modalCompraFecha"></strong>
              </div>
              <div class="tm-compra-metric">
                <span>Cantidad</span>
                <strong id="modalCompraCantidad"></strong>
              </div>
            </div>
            <div class="tm-compra-metrics">
              <div class="tm-compra-metric">
                <span>Costo real</span>
                <strong id="modalCompraCostoReal">-</strong>
              </div>
              <div class="tm-compra-metric">
                <span>Cambio</span>
                <strong id="modalCompraCambio">-</strong>
              </div>
            </div>
            <a id="modalCompraFactura" class="btn btn-default btn-block" target="_blank" style="display:none">
              <i class="fa fa-file-image-o"></i> Ver factura o comprobante
            </a>
          </aside>

          <main>
            <section class="tm-compra-modal-panel">
              <h4><i class="fa fa-cubes"></i> Productos solicitados</h4>
              <div id="modalCompraProductos" class="tm-compra-modal-products"></div>
            </section>

            <section class="tm-compra-modal-panel">
              <h4><i class="fa fa-money"></i> Costos reales facturados</h4>
              <div id="modalCompraRendicion" class="tm-compra-modal-products"></div>
            </section>

            <section class="tm-compra-modal-panel tm-compra-modal-actions-wrap">
              <h4><i class="fa fa-bolt"></i> Acciones y constancias</h4>
              <div id="modalCompraAcciones" class="tm-compra-actions"></div>
            </section>
          </main>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(function(){
  $('[title]').tooltip({container:'body'});
});

function filtrarSolicitudesCompraCards(){
  var termino = ($("#buscarSolicitudesCompraCards").val() || "").toString().toLowerCase();

  $(".tm-compra-grid").each(function(){
    var grid = $(this);
    var visibles = 0;
    grid.find(".tm-compra-empty.busqueda-vacia").remove();

    grid.find(".tm-compra-card").each(function(){
      var card = $(this);
      var texto = (card.data("search") || card.text()).toString().toLowerCase();
      var coincide = !termino || texto.indexOf(termino) !== -1;
      card.toggle(coincide);
      if(coincide){
        visibles++;
      }
    });

    if(visibles === 0 && grid.find(".tm-compra-card").length > 0){
      grid.append('<div class="tm-compra-empty busqueda-vacia"><i class="fa fa-search"></i><strong>No hay solicitudes que coincidan.</strong><span>Prueba con otra nota, usuario, producto o estado.</span></div>');
    }
  });
}

$(document).on("input", "#buscarSolicitudesCompraCards", filtrarSolicitudesCompraCards);
$(document).on("shown.bs.tab", 'a[data-toggle="tab"]', filtrarSolicitudesCompraCards);

$(document).on("click keypress", ".tm-compra-card", function(event){
  if(event.type === "keypress" && event.which !== 13 && event.which !== 32){
    return;
  }
  if($(event.target).closest("button, a, input, form, .tm-compra-actions-template").length){
    return;
  }

  var card = $(this);
  var solicitanteUsuario = card.data("usuario") ? "Usuario: " + card.data("usuario") : "Usuario sin dato";
  var mensajeroUsuario = card.data("mensajeroUsuario") ? "Usuario: " + card.data("mensajeroUsuario") : "Sin usuario asignado";
  var estadoClase = card.data("estadoClase") || "neutral";

  $("#modalCompraNota").text(card.data("nota") || "");
  $("#modalCompraResumen").text((card.data("items") || 0) + " item(s) / " + card.data("total"));
  $("#modalCompraSolicitante").html($("<div>").text(card.data("solicitante") || "-").html() + "<br><small>" + $("<div>").text(solicitanteUsuario).html() + "</small>");
  $("#modalCompraMensajero").html($("<div>").text(card.data("mensajero") || "-").html() + "<br><small>" + $("<div>").text(mensajeroUsuario).html() + "</small>");
  $("#modalCompraTotal").text(card.data("total") || "Bs 0.00");
  $("#modalCompraEstado").html('<span class="tm-compra-status status-' + estadoClase + '">' + $("<div>").text(card.data("estado") || "-").html() + '</span>');
  $("#modalCompraFecha").text(card.data("fecha") || "-");
  $("#modalCompraCostoReal").text(card.data("costoReal") || "Bs 0.00");
  $("#modalCompraCambio").text(card.data("cambio") || "Bs 0.00");
  var factura = card.data("factura") || "";
  $("#modalCompraFactura").attr("href", factura || "#").toggle(!!factura);
  $("#modalCompraCantidad").text((card.data("items") || 0) + " item(s) / " + (card.data("unidades") || 0) + " unidad(es)");
  $("#modalCompraProductos").html(card.find(".tm-compra-products-template").html());
  $("#modalCompraRendicion").html(card.find(".tm-compra-rendicion-template").html());
  $("#modalCompraAcciones").html(card.find(".tm-compra-actions-template").html());

  $("#modalDetalleSolicitudCompra").modal("show");
});

$(document).on("click", ".btnImprimirNotaCompra", function(event){
  event.stopPropagation();
  window.open("extensiones/tcpdf/pdf/notacompra.php?idCompra=" + $(this).attr("idCompra"), "_blank");
});

$(document).on("click", ".btnImprimirDesembolso", function(event){
  event.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-desembolso-mensajero.php?idCompra=" + $(this).attr("idCompra"), "_blank");
});

$(document).on("click", ".btnImprimirEntregaAlmacen", function(event){
  event.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-entrega-compra-almacen.php?idCompra=" + $(this).attr("idCompra"), "_blank");
});

$(document).on("submit", ".formConfirmarDesembolso", function(event){
  event.preventDefault();

  var formulario = this;
  var monto = Number($(formulario).find('[name="montoDesembolso"]').val() || 0);

  if(monto <= 0){
    swal({
      type:"error",
      title:"Monto no válido",
      text:"Ingrese un monto mayor a cero para registrar el desembolso.",
      confirmButtonText:"Cerrar"
    });
    return;
  }

  swal({
    type:"question",
    title:"Confirmar desembolso",
    html:
      '<div style="padding:4px 0 2px">' +
        '<div style="width:58px;height:58px;margin:0 auto 12px;border-radius:18px;background:#e8f4fc;color:#1687c4;display:flex;align-items:center;justify-content:center;font-size:25px">' +
          '<i class="fa fa-money"></i>' +
        '</div>' +
        '<p style="margin:0 0 10px;color:#42566b;font-size:15px;font-weight:700">¿Confirmar desembolso de efectivo al mensajero?</p>' +
        '<div style="display:inline-block;padding:10px 20px;border-radius:12px;background:#eef7fd;color:#145f8c;font-size:22px;font-weight:900">Bs ' +
          monto.toFixed(2) +
        '</div>' +
        '<p style="margin:12px 0 0;color:#718398;font-size:12px">Este monto quedará registrado como egreso de caja.</p>' +
      '</div>',
    showCancelButton:true,
    confirmButtonColor:"#1687c4",
    cancelButtonColor:"#8392a3",
    confirmButtonText:'<i class="fa fa-check-circle"></i> Confirmar desembolso',
    cancelButtonText:"Cancelar",
    reverseButtons:true
  }).then(function(result){
    if(result.value){
      $(formulario).find('button[type="submit"]').prop("disabled", true);
      formulario.submit();
    }
  });
});

$(document).on("submit", ".formConfirmarRendicionCaja", function(event){
  event.preventDefault();
  var formulario = this;
  swal({
    type:"question",
    title:"Confirmar rendicion de compra",
    html:
      '<div style="padding:3px 0">' +
        '<div style="width:58px;height:58px;margin:0 auto 12px;border-radius:18px;background:#e9faf2;color:#0b9b61;display:flex;align-items:center;justify-content:center;font-size:25px">' +
          '<i class="fa fa-check-circle"></i>' +
        '</div>' +
        '<p style="margin:0 0 7px;color:#354b60;font-size:15px;font-weight:800">¿Confirma que recibio el cambio indicado?</p>' +
        '<p style="margin:0;color:#718398;font-size:12px">La devolucion se registrara como ingreso de caja y la compra quedara lista para entregar a almacen.</p>' +
      '</div>',
    showCancelButton:true,
    confirmButtonColor:"#0b9b61",
    cancelButtonColor:"#8392a3",
    confirmButtonText:'<i class="fa fa-check-circle"></i> Confirmar rendicion',
    cancelButtonText:"Cancelar",
    reverseButtons:true
  }).then(function(result){
    if(result.value){
      $(formulario).find('button[type="submit"]').prop("disabled", true);
      formulario.submit();
    }
  });
});

$(document).on("click", ".btnEliminarSolicitudCompra", function(event){
  event.preventDefault();
  event.stopPropagation();
  event.stopImmediatePropagation();

  var boton = $(this);
  var idSolicitud = boton.attr("idSolicitud") || "";
  var codigo = boton.attr("codigoSolicitud") || idSolicitud;
  var estado = boton.attr("estadoSolicitud") || "";

  if(!idSolicitud){
    swal({type:"error",title:"No se pudo identificar la solicitud",confirmButtonText:"Cerrar"});
    return;
  }

  swal({
    type:"warning",
    title:"¿Eliminar la solicitud " + codigo + "?",
    html:"<p>Estado actual: <b>" + $("<div>").text(estado).html() + "</b>.</p>" +
         "<p>Se eliminará la solicitud y su detalle. Si ya existen materiales ingresados al inventario, el sistema bloqueará la operación.</p>" +
         "<p><b>Esta acción no se puede deshacer.</b></p>",
    showCancelButton:true,
    confirmButtonColor:"#d33",
    cancelButtonText:"Cancelar",
    confirmButtonText:"Sí, eliminar"
  }).then(function(result){
    if(result.value){
      boton.prop("disabled", true);
      window.location = "index.php?ruta=solicitudes-de-compra&idCompra=" + encodeURIComponent(idSolicitud);
    }
  });
});
</script>
