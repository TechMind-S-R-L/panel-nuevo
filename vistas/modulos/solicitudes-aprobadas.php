<?php
if ($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "mensajero") {
  echo '<script>window.location = "inicio";</script>';
  return;
}

if (isset($_POST["tomarSolicitudCompra"])) {
  $idSolicitud = (int)$_POST["tomarSolicitudCompra"];
  $respuesta = ControladorCompras::ctrTomarSolicitudMensajero($idSolicitud);
  if ($respuesta == "ok") {
    echo '<script>
      swal({
        type:"success",
        title:"Solicitud tomada",
        text:"Dirijase a caja para solicitar el desembolso correspondiente antes de realizar la compra.",
        confirmButtonText:"Cerrar"
      }).then(function(result){ if(result.value){ window.location = "solicitudes-aprobadas"; } });
    </script>';
  } else {
    echo '<script>
      swal({
        type:"error",
        title:"No se pudo tomar la solicitud",
        text:"La solicitud ya fue tomada, ya cambio de estado o no esta aprobada.",
        confirmButtonText:"Cerrar"
      }).then(function(result){ if(result.value){ window.location = "solicitudes-aprobadas"; } });
    </script>';
  }
}

if (isset($_POST["registrarRendicionCompra"])) {
  $respuestaRendicion = ControladorCompras::ctrRegistrarRendicionMensajero(
    (int)$_POST["registrarRendicionCompra"],
    $_POST["costosCompra"] ?? array(),
    $_FILES["facturaCompra"] ?? array(),
    $_POST["numeroFacturaCompra"] ?? "",
    $_POST["observacionRendicionCompra"] ?? ""
  );
  if (($respuestaRendicion["status"] ?? "") === "ok") {
    echo '<script>
      swal({type:"success",title:"Rendicion enviada",text:"Costo real Bs '.number_format($respuestaRendicion["total"], 2).'. Cambio a devolver Bs '.number_format($respuestaRendicion["cambio"], 2).'. Presente el cambio en caja.",confirmButtonText:"Cerrar"})
      .then(function(){window.location="solicitudes-aprobadas";});
    </script>';
  } else {
    echo '<script>
      swal({type:"error",title:"No se pudo registrar la rendicion",text:'.json_encode($respuestaRendicion["message"] ?? "Revise los datos y la factura.").',confirmButtonText:"Cerrar"});
    </script>';
  }
}

$compras = ControladorCompras::ctrMostrarCompras(null, null);
$compras = is_array($compras) ? $compras : array();

function tmCompraMsgEsc($valor) {
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function tmCompraMsgProductos($compra) {
  $productos = json_decode($compra["productos"] ?? "[]", true);
  return is_array($productos) ? $productos : array();
}

function tmCompraMsgCantidad($productos) {
  $cantidad = 0;
  foreach ($productos as $producto) {
    $cantidad += (int)($producto["cantidad"] ?? 0);
  }
  return $cantidad;
}

function tmCompraMsgEstadoTexto($estado) {
  $textos = array(
    "aprobado" => "Aprobada",
    "en_compra" => "En desembolso",
    "desembolsado" => "En proceso de compra",
    "rendicion_pendiente" => "Rendicion en caja",
    "compra_rendida" => "Lista para entregar",
    "entregado_almacen" => "Entregado a almacen",
    "completado" => "Completada"
  );
  return $textos[trim((string)$estado)] ?? ucfirst(str_replace("_", " ", (string)$estado));
}

function tmCompraMsgEstadoClase($estado) {
  $clases = array(
    "aprobado" => "primary",
    "en_compra" => "info",
    "desembolsado" => "warning",
    "rendicion_pendiente" => "info",
    "compra_rendida" => "success",
    "entregado_almacen" => "success",
    "completado" => "success"
  );
  return $clases[trim((string)$estado)] ?? "default";
}

function tmCompraMsgProductosHtml($productos) {
  if (empty($productos)) {
    return '<div class="tm-msg-product empty">Sin productos registrados</div>';
  }

  ob_start();
  foreach ($productos as $producto):
    $descripcion = $producto["descripcion"] ?? "Producto";
    $cantidad = (int)($producto["cantidad"] ?? 0);
    $precio = isset($producto["precio"]) ? (float)$producto["precio"] : 0;
    $subtotal = isset($producto["total"]) ? (float)$producto["total"] : ($precio * $cantidad);
  ?>
    <div class="tm-msg-product">
      <div>
        <strong><?php echo tmCompraMsgEsc($descripcion); ?></strong>
        <span>Cantidad: <?php echo $cantidad; ?> unidad(es)</span>
      </div>
      <b><?php echo $subtotal > 0 ? "Bs ".number_format($subtotal, 2) : "Sin precio"; ?></b>
    </div>
  <?php
  endforeach;
  return ob_get_clean();
}

function tmCompraMsgPuedeVer($compra) {
  if ($_SESSION["rol"] != "mensajero") {
    return true;
  }

  $estado = trim((string)($compra["estado"] ?? ""));
  $idMensajero = (int)($compra["id_mensajero"] ?? 0);
  if ($estado == "aprobado" && $idMensajero === 0) {
    return true;
  }

  return $idMensajero === (int)$_SESSION["id"];
}

function tmCompraMsgFiltrar($compras, $estados) {
  $filtradas = array();
  foreach ($compras as $compra) {
    if (!tmCompraMsgPuedeVer($compra)) {
      continue;
    }

    if (in_array(trim((string)($compra["estado"] ?? "")), $estados)) {
      $filtradas[] = $compra;
    }
  }
  return $filtradas;
}

function tmCompraMsgAcciones($compra) {
  $idCompra = (int)($compra["id"] ?? 0);
  $estado = trim((string)($compra["estado"] ?? ""));
  $idMensajero = (int)($compra["id_mensajero"] ?? 0);

  ob_start();
  ?>
    <button type="button" class="tm-msg-btn light btnImprimirNotaCompraMsg" idCompra="<?php echo $idCompra; ?>" title="Imprimir solicitud aprobada">
      <i class="fa fa-print"></i><span>Solicitud</span>
    </button>

    <?php if (!empty($compra["id_cajero_desembolso"]) || in_array($estado, array("desembolsado", "entregado_almacen", "completado"))): ?>
      <button type="button" class="tm-msg-btn light btnImprimirDesembolsoMsg" idCompra="<?php echo $idCompra; ?>" title="Imprimir constancia de desembolso">
        <i class="fa fa-money"></i><span>Desembolso</span>
      </button>
    <?php endif; ?>

    <?php if (!empty($compra["fecha_entrega_almacen"]) || in_array($estado, array("entregado_almacen", "completado"))): ?>
      <button type="button" class="tm-msg-btn light btnImprimirEntregaAlmacenMsg" idCompra="<?php echo $idCompra; ?>" title="Imprimir constancia de entrega a almacen">
        <i class="fa fa-handshake-o"></i><span>Entrega</span>
      </button>
    <?php endif; ?>

    <?php if (!empty($compra["factura_compra"])): ?>
      <a class="tm-msg-btn light" href="<?php echo tmCompraMsgEsc($compra["factura_compra"]); ?>" target="_blank">
        <i class="fa fa-file-image-o"></i><span>Factura</span>
      </a>
    <?php endif; ?>

    <?php if ($estado === "aprobado" && $idMensajero === 0): ?>
      <form method="POST" class="formTomarSolicitudCompra tm-msg-take-form">
        <input type="hidden" name="tomarSolicitudCompra" value="<?php echo $idCompra; ?>">
        <button type="submit" class="tm-msg-btn primary" title="Tomar solicitud y pasar por caja">
          <i class="fa fa-motorcycle"></i><span>Tomar</span>
        </button>
      </form>
    <?php elseif ($estado === "aprobado"): ?>
      <span class="tm-msg-muted-chip"><i class="fa fa-lock"></i> Ya tomada</span>
    <?php endif; ?>

    <?php if ($estado === "desembolsado"): ?>
      <button type="button"
        class="tm-msg-btn primary btnRendirCompraMsg"
        data-idcompra="<?php echo $idCompra; ?>"
        data-productos-json="<?php echo tmCompraMsgEsc(base64_encode(json_encode(tmCompraMsgProductos($compra), JSON_UNESCAPED_UNICODE))); ?>"
        data-desembolsado="<?php echo tmCompraMsgEsc($compra["monto_desembolsado"] ?? 0); ?>">
        <i class="fa fa-file-image-o"></i><span>Rendir compra</span>
      </button>
    <?php elseif ($estado === "rendicion_pendiente"): ?>
      <span class="tm-msg-muted-chip"><i class="fa fa-hourglass-half"></i> Caja verifica cambio</span>
    <?php endif; ?>
  <?php
  return ob_get_clean();
}

function tmCompraMsgRenderCards($compras) {
  if (empty($compras)) {
    echo '<div class="tm-msg-empty"><i class="fa fa-check-circle-o"></i><h4>Sin solicitudes en esta etapa</h4><p>No hay compras para mostrar aqui.</p></div>';
    return;
  }

  foreach ($compras as $compra) {
    $proveedor = ControladorProveedor::ctrMostrarProveedor("id", $compra["id_proveedor"]);
    $mensajero = !empty($compra["id_mensajero"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $compra["id_mensajero"]) : null;
    $productos = tmCompraMsgProductos($compra);
    $productosHtml = tmCompraMsgProductosHtml($productos);
    $estado = trim((string)($compra["estado"] ?? ""));
    $estadoTexto = tmCompraMsgEstadoTexto($estado);
    $estadoClase = tmCompraMsgEstadoClase($estado);
    $proveedorNombre = $proveedor["nombre"] ?? "Sin proveedor";
    $mensajeroNombre = $mensajero["nombre"] ?? "Libre sin asignacion";
    $cantidadProductos = tmCompraMsgCantidad($productos);
    $total = !empty($compra["monto_desembolsado"])
      ? (float)$compra["monto_desembolsado"]
      : (float)($compra["total"] ?? 0);
    $acciones = tmCompraMsgAcciones($compra);
    $busqueda = strtolower(($compra["codigo"] ?? "")." ".$proveedorNombre." ".$mensajeroNombre." ".$estadoTexto);
    ?>
    <article class="tm-msg-card tm-msg-status-<?php echo tmCompraMsgEsc($estadoClase); ?>"
      data-idcompra="<?php echo $idCompra; ?>"
      data-search="<?php echo tmCompraMsgEsc($busqueda); ?>"
      data-codigo="<?php echo tmCompraMsgEsc($compra["codigo"] ?? ""); ?>"
      data-proveedor="<?php echo tmCompraMsgEsc($proveedorNombre); ?>"
      data-mensajero="<?php echo tmCompraMsgEsc($mensajeroNombre); ?>"
      data-estado="<?php echo tmCompraMsgEsc($estadoTexto); ?>"
      data-estado-clase="<?php echo tmCompraMsgEsc($estadoClase); ?>"
      data-total="Bs <?php echo number_format($total, 2); ?>"
      data-fecha="<?php echo tmCompraMsgEsc($compra["fecha"] ?? ""); ?>"
      data-productos-json="<?php echo tmCompraMsgEsc(base64_encode(json_encode($productos, JSON_UNESCAPED_UNICODE))); ?>"
      data-desembolsado="<?php echo tmCompraMsgEsc($compra["monto_desembolsado"] ?? 0); ?>"
      data-cantidad="<?php echo (int)$cantidadProductos; ?>">
      <div class="tm-msg-card-head">
        <div class="tm-msg-icon"><i class="fa fa-shopping-bag"></i></div>
        <div class="tm-msg-title">
          <span><i class="fa fa-file-text-o"></i> Nota <?php echo tmCompraMsgEsc($compra["codigo"] ?? ""); ?></span>
          <h3><?php echo tmCompraMsgEsc($proveedorNombre); ?></h3>
        </div>
        <strong class="tm-msg-state <?php echo tmCompraMsgEsc($estadoClase); ?>"><?php echo tmCompraMsgEsc($estadoTexto); ?></strong>
      </div>

      <div class="tm-msg-steps">
        <div class="<?php echo in_array($estado, array("aprobado", "en_compra", "desembolsado", "rendicion_pendiente", "compra_rendida", "entregado_almacen", "completado")) ? "done" : ""; ?>"><i class="fa fa-check"></i><span>Aprobada</span></div>
        <div class="<?php echo in_array($estado, array("en_compra", "desembolsado", "rendicion_pendiente", "compra_rendida", "entregado_almacen", "completado")) ? "done" : ""; ?>"><i class="fa fa-money"></i><span>Caja</span></div>
        <div class="<?php echo in_array($estado, array("rendicion_pendiente", "compra_rendida", "entregado_almacen", "completado")) ? "done" : ""; ?>"><i class="fa fa-motorcycle"></i><span>Rendicion</span></div>
        <div class="<?php echo in_array($estado, array("entregado_almacen", "completado")) ? "done" : ""; ?>"><i class="fa fa-archive"></i><span>Almacen</span></div>
      </div>

      <div class="tm-msg-info">
        <div><span>Total</span><strong>Bs <?php echo number_format($total, 2); ?></strong></div>
        <div><span>Productos</span><strong><?php echo (int)$cantidadProductos; ?></strong></div>
        <div><span>Mensajero</span><strong><?php echo tmCompraMsgEsc($mensajeroNombre); ?></strong></div>
      </div>

      <div class="tm-msg-preview">
        <span>Productos solicitados</span>
        <?php echo $productosHtml; ?>
      </div>

      <div class="tm-msg-actions"><?php echo $acciones; ?></div>
      <div class="tm-msg-products-template" style="display:none"><?php echo $productosHtml; ?></div>
      <div class="tm-msg-actions-template" style="display:none"><?php echo $acciones; ?></div>
    </article>
    <?php
  }
}

$aprobadas = tmCompraMsgFiltrar($compras, array("aprobado"));
$enDesembolso = tmCompraMsgFiltrar($compras, array("en_compra"));
$enCompra = tmCompraMsgFiltrar($compras, array("desembolsado", "rendicion_pendiente", "compra_rendida", "entregado_almacen"));
$completadas = tmCompraMsgFiltrar($compras, array("completado"));
?>

<style>
.tm-msg-page .content{padding-top:12px}
.tm-msg-hero{background:linear-gradient(135deg,#12384a,#1d86c8);color:#fff;border-radius:18px;padding:21px 24px;margin-bottom:16px;box-shadow:0 16px 35px rgba(18,56,74,.16);display:flex;align-items:center;justify-content:space-between;gap:18px}
.tm-msg-hero h1{margin:0;font-size:25px;font-weight:900}
.tm-msg-hero p{margin:6px 0 0;opacity:.93;max-width:820px}
.tm-msg-hero-icon{width:58px;height:58px;border-radius:18px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:27px}
.tm-msg-metrics{display:grid;grid-template-columns:repeat(4,minmax(140px,1fr));gap:12px;margin-bottom:16px}
.tm-msg-metric{background:rgba(255,255,255,.76);border:1px solid rgba(45,111,181,.14);border-radius:16px;padding:13px 15px;box-shadow:0 12px 25px rgba(30,80,120,.08)}
.tm-msg-metric span{display:block;color:#668099;font-size:11px;font-weight:900;text-transform:uppercase}
.tm-msg-metric strong{display:block;color:#12384a;font-size:26px;line-height:1;margin-top:6px}
.tm-msg-panel{background:rgba(255,255,255,.74);border:1px solid rgba(45,111,181,.16);border-radius:18px;box-shadow:0 14px 35px rgba(30,80,120,.09);overflow:hidden}
.tm-msg-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:16px 18px;border-bottom:1px solid rgba(45,111,181,.12)}
.tm-msg-toolbar h3{margin:0;font-size:18px;font-weight:900;color:#16324a}
.tm-msg-search{max-width:390px;width:100%;position:relative}
.tm-msg-search i{position:absolute;left:13px;top:12px;color:#5c7da0}
.tm-msg-search input{width:100%;height:40px;border:1px solid rgba(45,111,181,.18);border-radius:12px;padding:0 14px 0 36px;background:rgba(255,255,255,.86);outline:0}
.tm-msg-tabs{padding:0 18px;border-bottom:1px solid rgba(45,111,181,.12)}
.tm-msg-tabs.nav-tabs>li>a{border:0!important;border-radius:12px 12px 0 0;color:#51697f;font-weight:900;padding:12px 15px}
.tm-msg-tabs.nav-tabs>li.active>a,.tm-msg-tabs.nav-tabs>li.active>a:focus,.tm-msg-tabs.nav-tabs>li.active>a:hover{color:#0d5ea3;background:#fff;border-bottom:3px solid #16a9e0!important}
.tm-msg-tabs .badge{margin-left:5px}
.tm-msg-panel .tab-content{padding:16px 18px 18px}
.tm-msg-note{background:rgba(22,169,224,.09);border:1px solid rgba(22,169,224,.18);color:#245066;border-radius:14px;padding:12px 14px;margin-bottom:14px;font-weight:800}
.tm-msg-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(292px,1fr));gap:13px}
.tm-msg-card{background:rgba(255,255,255,.86);border:1px solid rgba(45,111,181,.16);border-radius:16px;box-shadow:0 12px 25px rgba(30,80,120,.08);cursor:pointer;transition:.18s ease;position:relative;overflow:hidden;display:flex;flex-direction:column;min-height:328px}
.tm-msg-card:hover{transform:translateY(-2px);border-color:rgba(22,169,224,.42);box-shadow:0 16px 34px rgba(30,80,120,.14)}
.tm-msg-card:after{content:"";position:absolute;right:-36px;bottom:-42px;width:118px;height:118px;border-radius:50%;background:rgba(36,143,206,.12);pointer-events:none}
.tm-msg-card-head{display:grid;grid-template-columns:40px minmax(0,1fr) minmax(88px,116px);gap:9px;align-items:start;padding:12px;background:linear-gradient(135deg,rgba(18,56,74,.08),rgba(22,169,224,.07));border-bottom:1px solid rgba(45,111,181,.12)}
.tm-msg-icon{width:38px;height:38px;border-radius:13px;background:linear-gradient(135deg,#155a9c,#19aee8);color:#fff;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:0 10px 22px rgba(24,113,177,.2)}
.tm-msg-title{min-width:0}
.tm-msg-title span{display:inline-flex;align-items:center;gap:5px;max-width:100%;font-size:10px;font-weight:900;color:#114d85;background:#edf7ff;border:1px solid rgba(45,111,181,.14);border-radius:999px;padding:5px 7px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tm-msg-title h3{margin:6px 0 0;font-size:14px;font-weight:900;color:#142b3f;line-height:1.18;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.tm-msg-state{display:inline-flex;align-items:center;justify-content:center;text-align:center;border-radius:999px;color:#fff;font-size:9.5px;font-weight:900;line-height:1.08;padding:6px 7px;min-width:0;white-space:normal}
.tm-msg-state.primary{background:#248fce}.tm-msg-state.info{background:#00c0ef}.tm-msg-state.warning{background:#f39c12}.tm-msg-state.success{background:#00a65a}.tm-msg-state.default{background:#6b7280}
.tm-msg-steps{display:grid;grid-template-columns:repeat(4,1fr);gap:5px;margin:10px 12px 8px}
.tm-msg-steps div{background:#f3f7fb;border:1px solid rgba(45,111,181,.11);border-radius:11px;padding:7px 4px;text-align:center;color:#8193a6;font-size:9px;font-weight:900;line-height:1.1}
.tm-msg-steps div.done{background:#eaf7ff;color:#176ca9;border-color:rgba(22,169,224,.22)}
.tm-msg-steps i{display:block;margin-bottom:3px}
.tm-msg-info{display:grid;grid-template-columns:1fr 70px 1fr;gap:7px;margin:0 12px 8px}
.tm-msg-info div{background:#f6f9fc;border:1px solid rgba(45,111,181,.1);border-radius:12px;padding:8px;min-width:0}
.tm-msg-info span{display:block;font-size:8.5px;text-transform:uppercase;color:#6b8299;font-weight:900;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tm-msg-info strong{display:block;color:#1d3348;font-size:11px;line-height:1.15;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tm-msg-preview{margin:0 12px;display:flex;flex-direction:column;gap:6px;max-height:96px;overflow:auto}
.tm-msg-preview>span{font-size:9px;text-transform:uppercase;color:#6b8299;font-weight:900}
.tm-msg-product{display:flex;justify-content:space-between;gap:8px;background:#fff;border:1px solid rgba(45,111,181,.1);border-radius:11px;padding:7px}
.tm-msg-product.empty{color:#8a5b00;background:#fff7e6}
.tm-msg-product strong{display:block;color:#1d3348;font-size:11px;line-height:1.22;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.tm-msg-product span{display:block;color:#657c93;font-size:10px;margin-top:2px}
.tm-msg-product b{color:#114d85;font-size:10px;white-space:nowrap}
.tm-msg-actions{display:flex;flex-wrap:wrap;gap:6px;margin-top:auto;padding:10px 12px 12px;border-top:1px solid rgba(45,111,181,.1)}
.tm-msg-btn{border:0;border-radius:10px;padding:8px 9px;display:inline-flex;align-items:center;justify-content:center;gap:6px;font-size:10.5px;font-weight:900;line-height:1;text-decoration:none!important;white-space:normal;min-height:32px;flex:1 1 104px}
.tm-msg-btn.primary{background:#248fce;color:#fff}.tm-msg-btn.light{background:#eef5fb;color:#184a78;border:1px solid rgba(45,111,181,.16)}
.tm-msg-take-form{display:flex;flex:1 1 104px;margin:0}.tm-msg-take-form .tm-msg-btn{width:100%}
.tm-msg-muted-chip{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:#eef2f7;color:#60758b;border-radius:10px;padding:8px 9px;font-size:10.5px;font-weight:900;min-height:32px;flex:1 1 104px}
.tm-msg-empty{grid-column:1/-1;text-align:center;border:1px dashed rgba(45,111,181,.24);border-radius:16px;padding:34px;background:rgba(255,255,255,.58);color:#5f7690}
.tm-msg-empty i{font-size:38px;color:#00a65a}.tm-msg-empty h4{font-weight:900;color:#17344c}
.tm-msg-modal .modal-dialog{width:min(820px,calc(100vw - 34px))}
.tm-msg-modal .modal-content{border:0;border-radius:20px;overflow:hidden;background:rgba(255,255,255,.98);box-shadow:0 24px 60px rgba(11,42,68,.28)}
.tm-msg-modal .modal-header{position:relative;border:0;padding:0;background:linear-gradient(135deg,#12384a,#1d86c8);color:#fff;overflow:hidden}
.tm-msg-modal .close{position:absolute;right:13px;top:10px;z-index:50;color:#fff;opacity:.96;text-shadow:none;width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center}
.tm-msg-modal-head{display:grid;grid-template-columns:46px minmax(0,1fr) auto;gap:12px;align-items:center;padding:16px 58px 16px 18px;position:relative}
.tm-msg-modal-head:after{content:"";position:absolute;right:-40px;top:-50px;width:132px;height:132px;border-radius:50%;background:rgba(255,255,255,.14);pointer-events:none}
.tm-msg-modal-icon{width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:19px}
.tm-msg-modal-head span{display:block;text-transform:uppercase;font-size:11px;font-weight:900;opacity:.84;letter-spacing:.03em}
.tm-msg-modal-head h4{font-size:19px!important;line-height:1.15;margin:0!important;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tm-msg-modal-head strong{position:relative;z-index:1;display:inline-flex;align-items:center;justify-content:center;min-width:94px;border-radius:999px;background:rgba(255,255,255,.22);padding:8px 10px;font-size:11px;text-align:center}
.tm-msg-modal-summary{display:grid;grid-template-columns:1.1fr .9fr .7fr;gap:10px;margin-bottom:12px}
.tm-msg-modal-summary div,.tm-msg-detail{border:1px solid rgba(45,111,181,.13);background:linear-gradient(135deg,#f8fbfd,#eef7ff);border-radius:14px;padding:12px}
.tm-msg-modal-summary span,.tm-msg-detail span{display:block;color:#6b8299;font-size:10px;text-transform:uppercase;font-weight:900;margin-bottom:5px}
.tm-msg-modal-summary strong,.tm-msg-detail strong{display:block;color:#153047;font-size:14px;line-height:1.25}
.tm-msg-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.tm-msg-detail.full{grid-column:1/-1}
.tm-msg-detail-items{display:flex;flex-direction:column;gap:8px;max-height:230px;overflow:auto}
.tm-msg-modal .modal-footer{border-top:1px solid rgba(45,111,181,.12);background:#f7fbfe}
.tm-msg-modal-actions{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}
.tm-msg-modal-actions .tm-msg-btn{flex:0 1 auto;min-width:130px}
body.dark-mode .tm-msg-panel,body.tm-dark .tm-msg-panel,body.dark-mode .tm-msg-card,body.tm-dark .tm-msg-card,body.dark-mode .tm-msg-metric,body.tm-dark .tm-msg-metric{background:rgba(15,27,48,.78);border-color:rgba(255,255,255,.12);color:#eaf3ff}
body.dark-mode .tm-msg-toolbar h3,body.dark-mode .tm-msg-metric strong,body.dark-mode .tm-msg-title h3,body.tm-dark .tm-msg-toolbar h3,body.tm-dark .tm-msg-metric strong,body.tm-dark .tm-msg-title h3{color:#fff}
body.dark-mode .tm-msg-info div,body.dark-mode .tm-msg-product,body.dark-mode .tm-msg-modal-summary div,body.dark-mode .tm-msg-detail,body.tm-dark .tm-msg-info div,body.tm-dark .tm-msg-product,body.tm-dark .tm-msg-modal-summary div,body.tm-dark .tm-msg-detail{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.1)}
body.dark-mode .tm-msg-info strong,body.dark-mode .tm-msg-product strong,body.dark-mode .tm-msg-modal-summary strong,body.dark-mode .tm-msg-detail strong,body.tm-dark .tm-msg-info strong,body.tm-dark .tm-msg-product strong,body.tm-dark .tm-msg-modal-summary strong,body.tm-dark .tm-msg-detail strong{color:#fff}
@media(max-width:900px){.tm-msg-hero,.tm-msg-toolbar{flex-direction:column;align-items:flex-start}.tm-msg-metrics{grid-template-columns:repeat(2,1fr)}.tm-msg-modal-summary,.tm-msg-detail-grid{grid-template-columns:1fr}}
@media(max-width:520px){.tm-msg-metrics{grid-template-columns:1fr}.tm-msg-grid{grid-template-columns:1fr}.tm-msg-tabs.nav-tabs>li{float:none}.tm-msg-tabs.nav-tabs>li>a{border-radius:10px}.tm-msg-info{grid-template-columns:1fr}}
</style>

<div class="content-wrapper tm-msg-page">
  <section class="content-header">
    <h1>Compras mensajero</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Compras mensajero</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-msg-hero">
      <div>
        <h1>Solicitudes aprobadas para mensajeria</h1>
        <p>Tome solicitudes aprobadas, pase por caja para el desembolso y entregue los productos a almacen con constancia.</p>
      </div>
      <div class="tm-msg-hero-icon"><i class="fa fa-motorcycle"></i></div>
    </div>

    <div class="tm-msg-metrics">
      <div class="tm-msg-metric"><span>Aprobadas libres</span><strong><?php echo count($aprobadas); ?></strong></div>
      <div class="tm-msg-metric"><span>En desembolso</span><strong><?php echo count($enDesembolso); ?></strong></div>
      <div class="tm-msg-metric"><span>En compra</span><strong><?php echo count($enCompra); ?></strong></div>
      <div class="tm-msg-metric"><span>Completadas</span><strong><?php echo count($completadas); ?></strong></div>
    </div>

    <div class="tm-msg-panel">
      <div class="tm-msg-toolbar">
        <h3><i class="fa fa-shopping-bag"></i> Seguimiento de compras</h3>
        <div class="tm-msg-search">
          <i class="fa fa-search"></i>
          <input type="text" id="buscarCompraMensajero" placeholder="Buscar por nota, proveedor, mensajero o estado">
        </div>
      </div>

      <ul class="nav nav-tabs tm-msg-tabs">
        <li class="active"><a href="#tabMsgAprobadas" data-toggle="tab">Aprobadas <span class="badge bg-blue"><?php echo count($aprobadas); ?></span></a></li>
        <li><a href="#tabMsgDesembolso" data-toggle="tab">En desembolso <span class="badge bg-aqua"><?php echo count($enDesembolso); ?></span></a></li>
        <li><a href="#tabMsgCompra" data-toggle="tab">En compra <span class="badge bg-yellow"><?php echo count($enCompra); ?></span></a></li>
        <li><a href="#tabMsgCompletadas" data-toggle="tab">Completadas <span class="badge bg-green"><?php echo count($completadas); ?></span></a></li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane active" id="tabMsgAprobadas">
          <div class="tm-msg-note">Solicitudes aprobadas por caja/admin. Tome una solicitud para que quede asignada a usted y pase por caja para el desembolso.</div>
          <div class="tm-msg-grid"><?php tmCompraMsgRenderCards($aprobadas); ?></div>
        </div>
        <div class="tab-pane" id="tabMsgDesembolso">
          <div class="tm-msg-note">Solicitudes tomadas por mensajeria. Caja debe registrar el desembolso antes de salir a comprar.</div>
          <div class="tm-msg-grid"><?php tmCompraMsgRenderCards($enDesembolso); ?></div>
        </div>
        <div class="tab-pane" id="tabMsgCompra">
          <div class="tm-msg-note">Compras con dinero desembolsado o productos ya entregados a almacen. Complete el ciclo con la entrega e ingreso correspondiente.</div>
          <div class="tm-msg-grid"><?php tmCompraMsgRenderCards($enCompra); ?></div>
        </div>
        <div class="tab-pane" id="tabMsgCompletadas">
          <div class="tm-msg-note">Compras finalizadas. Desde aqui puede reimprimir solicitud, desembolso y constancias disponibles.</div>
          <div class="tm-msg-grid"><?php tmCompraMsgRenderCards($completadas); ?></div>
        </div>
      </div>
    </div>
  </section>
</div>

<div id="modalDetalleCompraMensajero" class="modal fade tm-msg-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <div class="tm-msg-modal-head">
          <div class="tm-msg-modal-icon"><i class="fa fa-shopping-bag"></i></div>
          <div>
            <span>Detalle de compra</span>
            <h4>Nota <b id="detalleMsgCodigo"></b></h4>
          </div>
          <strong id="detalleMsgEstado">Estado</strong>
        </div>
      </div>
      <div class="modal-body">
        <div class="tm-msg-modal-summary">
          <div><span>Proveedor</span><strong id="detalleMsgProveedor"></strong></div>
          <div><span>Total</span><strong id="detalleMsgTotal"></strong></div>
          <div><span>Productos</span><strong id="detalleMsgCantidad"></strong></div>
        </div>
        <div class="tm-msg-detail-grid">
          <div class="tm-msg-detail"><span>Mensajero asignado</span><strong id="detalleMsgMensajero"></strong></div>
          <div class="tm-msg-detail"><span>Fecha solicitud</span><strong id="detalleMsgFecha"></strong></div>
          <div class="tm-msg-detail full"><span>Productos solicitados</span><div class="tm-msg-detail-items" id="detalleMsgProductos"></div></div>
        </div>
      </div>
      <div class="modal-footer">
        <div class="tm-msg-modal-actions" id="detalleMsgAcciones"></div>
      </div>
    </div>
  </div>
</div>

<div id="modalRendirCompraMensajero" class="modal fade tm-msg-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data" id="formRendirCompraMensajero">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <div class="tm-msg-modal-head">
            <div class="tm-msg-modal-icon"><i class="fa fa-file-text-o"></i></div>
            <div><span>Rendicion obligatoria</span><h4>Registrar compra y factura</h4></div>
          </div>
        </div>
        <div class="modal-body">
          <div class="alert alert-info">
            Registre el costo unitario realmente pagado. El sistema calculara el total y el cambio que debe devolver en caja.
          </div>
          <div id="rendicionProductosMsg" class="tm-msg-detail-items"></div>
          <hr>
          <div class="row">
            <div class="col-sm-6 form-group">
              <label>Numero de factura o comprobante</label>
              <input type="text" class="form-control" name="numeroFacturaCompra" maxlength="100">
            </div>
            <div class="col-sm-6 form-group">
              <label>Factura escaneada o fotografiada *</label>
              <input type="file" class="form-control" name="facturaCompra" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
            </div>
          </div>
          <div class="form-group">
            <label>Observacion</label>
            <textarea class="form-control" name="observacionRendicionCompra" rows="2"></textarea>
          </div>
          <div class="tm-msg-modal-summary">
            <div><span>Desembolsado</span><strong id="rendicionDesembolsadoMsg">Bs 0.00</strong></div>
            <div><span>Costo real</span><strong id="rendicionTotalMsg">Bs 0.00</strong></div>
            <div><span>Cambio</span><strong id="rendicionCambioMsg">Bs 0.00</strong></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          <input type="hidden" name="registrarRendicionCompra" id="rendicionIdCompraMsg">
          <button type="submit" class="btn btn-primary"><i class="fa fa-send"></i> Enviar rendicion</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(function(){
  $('[title]').tooltip({container:'body'});

  $("#buscarCompraMensajero").on("input", function(){
    var term = ($(this).val() || "").toLowerCase().trim();
    $(".tm-msg-card").each(function(){
      var text = ($(this).attr("data-search") || "").toLowerCase();
      $(this).toggle(text.indexOf(term) !== -1);
    });
  });
});

$(document).on("click", ".tm-msg-card", function(event){
  if ($(event.target).closest("a,button,.tm-msg-actions,.tm-msg-take-form").length) {
    return;
  }

  var card = $(this);
  $("#detalleMsgCodigo").text(card.data("codigo") || "-");
  $("#detalleMsgProveedor").text(card.data("proveedor") || "-");
  $("#detalleMsgMensajero").text(card.data("mensajero") || "-");
  $("#detalleMsgEstado").text(card.data("estado") || "-");
  $("#detalleMsgTotal").text(card.data("total") || "-");
  $("#detalleMsgCantidad").text(card.data("cantidad") || "0");
  $("#detalleMsgFecha").text(card.data("fecha") || "-");
  $("#detalleMsgProductos").html(card.find(".tm-msg-products-template").html());
  $("#detalleMsgAcciones").html(card.find(".tm-msg-actions-template").html());
  $("#detalleMsgAcciones [title]").tooltip({container:"body"});
  $("#modalDetalleCompraMensajero").modal("show");
});

$(document).on("submit", ".formTomarSolicitudCompra", function(e){
  e.preventDefault();
  var form = this;
  swal({
    type: "info",
    title: "Tomar solicitud de compra",
    text: "Al tomar esta solicitud quedara a su cargo. Luego debe dirigirse a caja para que registren el desembolso antes de ir a comprar.",
    showCancelButton: true,
    confirmButtonText: "Tomar solicitud",
    cancelButtonText: "Cancelar"
  }).then(function(result){
    if(result.value){
      form.submit();
    }
  });
});

$(document).on("click", ".btnImprimirNotaCompraMsg", function(event){
  event.preventDefault();
  event.stopPropagation();
  window.open("extensiones/tcpdf/pdf/notacompra.php?idCompra=" + $(this).attr("idCompra"), "_blank");
});

$(document).on("click", ".btnImprimirDesembolsoMsg", function(event){
  event.preventDefault();
  event.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-desembolso-mensajero.php?idCompra=" + $(this).attr("idCompra"), "_blank");
});

$(document).on("click", ".btnImprimirEntregaAlmacenMsg", function(event){
  event.preventDefault();
  event.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-entrega-compra-almacen.php?idCompra=" + $(this).attr("idCompra"), "_blank");
});

function recalcularRendicionMsg(){
  var total = 0;
  $("#rendicionProductosMsg .costo-real-msg").each(function(){
    total += Number($(this).val() || 0) * Number($(this).data("cantidad") || 0);
  });
  var desembolsado = Number($("#modalRendirCompraMensajero").data("desembolsado") || 0);
  $("#rendicionTotalMsg").text("Bs " + total.toFixed(2));
  $("#rendicionCambioMsg").text("Bs " + (desembolsado - total).toFixed(2));
}

$(document).on("click", ".btnRendirCompraMsg", function(event){
  event.preventDefault();
  event.stopPropagation();
  var trigger = $(this);
  var idCompra = Number(trigger.attr("data-idcompra") || 0);
  var productosCodificados = trigger.attr("data-productos-json") || "";
  var desembolsado = Number(trigger.attr("data-desembolsado") || 0);
  var card = trigger.closest(".tm-msg-card");

  if ((!productosCodificados || desembolsado <= 0) && card.length) {
    productosCodificados = card.attr("data-productos-json") || productosCodificados;
    desembolsado = Number(card.attr("data-desembolsado") || desembolsado);
  }

  var productos = [];
  try {
    productos = JSON.parse(atob(productosCodificados));
  } catch(e) {}
  var html = productos.map(function(producto){
    var id = Number(producto.id || 0);
    var cantidad = Number(producto.cantidad || 0);
    return '<div class="tm-msg-product">' +
      '<div><strong>' + $("<div>").text(producto.descripcion || "Producto").html() + '</strong>' +
      '<span>Cantidad comprada: ' + cantidad + '</span></div>' +
      '<div style="min-width:150px"><label style="font-size:10px">Costo unitario Bs</label>' +
      '<input type="number" min="0.01" step="0.01" required class="form-control costo-real-msg" data-cantidad="' + cantidad + '" name="costosCompra[' + id + ']"></div>' +
      '</div>';
  }).join("");
  if (!html || desembolsado <= 0) {
    swal({
      type:"error",
      title:"Datos de compra incompletos",
      text:"No se encontraron los productos o el monto desembolsado. Actualice la pagina y vuelva a intentar.",
      confirmButtonText:"Cerrar"
    });
    return;
  }
  $("#rendicionProductosMsg").html(html);
  $("#rendicionIdCompraMsg").val(idCompra);
  $("#modalRendirCompraMensajero").data("desembolsado", desembolsado);
  $("#rendicionDesembolsadoMsg").text("Bs " + desembolsado.toFixed(2));
  recalcularRendicionMsg();
  $("#modalDetalleCompraMensajero").modal("hide");
  $("#modalRendirCompraMensajero").modal("show");
});

$(document).on("input", ".costo-real-msg", recalcularRendicionMsg);

$("#formRendirCompraMensajero").on("submit", function(event){
  var total = Number($("#rendicionTotalMsg").text().replace(/[^0-9.-]/g, "")) || 0;
  var desembolsado = Number($("#modalRendirCompraMensajero").data("desembolsado") || 0);
  if(total <= 0 || total > desembolsado + 0.001){
    event.preventDefault();
    swal({type:"error",title:"Costo no valido",text:"El costo real debe ser mayor a cero y no superar el desembolso autorizado.",confirmButtonText:"Cerrar"});
  }
});
</script>
