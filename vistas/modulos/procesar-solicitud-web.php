<?php
$idSolicitudWeb = (int)($_GET["idSolicitudWeb"] ?? 0);
$solicitudWeb = ControladorCotizacion::ctrMostrarCotizacion("id", $idSolicitudWeb);
if(!$solicitudWeb || ($solicitudWeb["origen"] ?? "") != "web"){
  echo '<div class="content-wrapper"><section class="content"><div class="alert alert-danger">Solicitud web no encontrada.</div></section></div>';
  return;
}
$clienteWeb = ControladorClientes::ctrMostrarClientes("id", $solicitudWeb["id_cliente"]);
$productosWeb = json_decode($solicitudWeb["productos"] ?? "[]", true);
$productosWeb = is_array($productosWeb) ? $productosWeb : array();
$validoHasta = !empty($solicitudWeb["valido_hasta"]) ? $solicitudWeb["valido_hasta"] : date("Y-m-d", strtotime("+7 days"));
$condiciones = trim($solicitudWeb["condiciones"] ?? "");
if($condiciones == ""){
  $condiciones = "Forma de pago: efectivo, transferencia o segun acuerdo con el cliente.\nForma de entrega: en instalaciones del cliente o punto acordado.\nPrecios: incluyen impuestos de ley.\nGarantia: segun condiciones del fabricante y servicio contratado.";
}
?>
<div class="content-wrapper procesar-web-page">
<style>
  .procesar-web-page .panel-web{background:#fff;border:1px solid #dbe5ec;border-radius:4px;margin-bottom:16px;}
  .procesar-web-page .panel-web-header{padding:14px 16px;border-bottom:1px solid #e5edf2;background:#fbfdff;}
  .procesar-web-page .panel-web-header h3{margin:0;font-weight:700;font-size:18px;}
  .procesar-web-page .panel-web-body{padding:16px;}
  .procesar-web-page .web-hero{background:#163140;color:#fff;padding:18px 20px;border-radius:4px;margin-bottom:16px;display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap;}
  .procesar-web-page .web-hero h2{margin:0 0 6px;font-weight:700;}
  .procesar-web-page .web-hero p{margin:0;color:#c8d7df;}
  .producto-web-row{display:grid;grid-template-columns:1fr 100px 130px 130px;gap:10px;align-items:end;border-bottom:1px solid #e8eef3;padding:12px 0;}
  .producto-web-row:last-child{border-bottom:0;}
  .producto-web-row label{font-weight:700;color:#34495e;}
  @media(max-width:991px){.producto-web-row{grid-template-columns:1fr;}}
</style>

  <section class="content-header">
    <h1>Procesar solicitud web</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li><a href="solicitudes-web">Solicitudes web</a></li>
      <li class="active">Procesar</li>
    </ol>
  </section>

  <section class="content">
    <div class="web-hero">
      <div>
        <h2>Solicitud Nro <?php echo htmlspecialchars($solicitudWeb["codigo"], ENT_QUOTES, "UTF-8"); ?></h2>
        <p>Cliente: <?php echo htmlspecialchars($clienteWeb["nombre"] ?? "", ENT_QUOTES, "UTF-8"); ?> | Estado: <?php echo htmlspecialchars($solicitudWeb["estado_web"], ENT_QUOTES, "UTF-8"); ?></p>
      </div>
      <div>
        <a class="btn btn-default" href="solicitudes-web"><i class="fa fa-arrow-left"></i> Volver</a>
        <?php if(($solicitudWeb["estado_web"] ?? "") == "cotizada"): ?>
          <a class="btn btn-info" target="_blank" href="extensiones/tcpdf/pdf/cotizacion.php?idCotizacion=<?php echo urlencode($solicitudWeb["id"]); ?>&codigoCotizacion=<?php echo urlencode($solicitudWeb["codigo"]); ?>">
            <i class="fa fa-print"></i> Ver boleta
          </a>
        <?php endif; ?>
      </div>
    </div>

    <form method="post" id="formProcesarSolicitudWeb">
      <input type="hidden" name="procesarSolicitudWeb" value="1">
      <input type="hidden" name="idSolicitudWeb" value="<?php echo (int)$solicitudWeb["id"]; ?>">
      <input type="hidden" name="codigoSolicitudWeb" value="<?php echo htmlspecialchars($solicitudWeb["codigo"], ENT_QUOTES, "UTF-8"); ?>">
      <input type="hidden" id="productosSolicitudWeb" name="productosSolicitudWeb">

      <div class="panel-web">
        <div class="panel-web-header"><h3><i class="fa fa-user"></i> Datos del cliente</h3></div>
        <div class="panel-web-body">
          <div class="row">
            <div class="col-md-3"><strong>Cliente:</strong><br><?php echo htmlspecialchars($clienteWeb["nombre"] ?? "", ENT_QUOTES, "UTF-8"); ?></div>
            <div class="col-md-3"><strong>Documento:</strong><br><?php echo htmlspecialchars($clienteWeb["documento"] ?? "", ENT_QUOTES, "UTF-8"); ?></div>
            <div class="col-md-3"><strong>Telefono:</strong><br><?php echo htmlspecialchars($clienteWeb["telefono"] ?? "", ENT_QUOTES, "UTF-8"); ?></div>
            <div class="col-md-3"><strong>Email:</strong><br><?php echo htmlspecialchars($clienteWeb["email"] ?? "", ENT_QUOTES, "UTF-8"); ?></div>
          </div>
        </div>
      </div>

      <div class="panel-web">
        <div class="panel-web-header"><h3><i class="fa fa-cubes"></i> Productos y precios</h3></div>
        <div class="panel-web-body" id="productosSolicitudWebWrap">
          <?php foreach($productosWeb as $index => $producto): ?>
            <div class="producto-web-row" data-id="<?php echo (int)($producto["id"] ?? 0); ?>">
              <div>
                <label>Producto</label>
                <input class="form-control descripcion-web" value="<?php echo htmlspecialchars($producto["descripcion"] ?? "Producto", ENT_QUOTES, "UTF-8"); ?>" readonly>
              </div>
              <div>
                <label>Cantidad</label>
                <input type="number" class="form-control cantidad-web" min="1" value="<?php echo (int)($producto["cantidad"] ?? 1); ?>">
              </div>
              <div>
                <label>Precio unitario</label>
                <input type="number" step="0.01" min="0" class="form-control precio-web" value="<?php echo number_format((float)($producto["precio"] ?? 0), 2, ".", ""); ?>">
              </div>
              <div>
                <label>Total</label>
                <input class="form-control total-web" readonly value="0.00">
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="panel-web">
        <div class="panel-web-header"><h3><i class="fa fa-calculator"></i> Resumen y condiciones</h3></div>
        <div class="panel-web-body">
          <div class="row">
            <div class="col-md-3">
              <label>Descuento (%)</label>
              <input type="number" step="0.01" min="0" class="form-control" id="descuentoSolicitudWeb" name="descuentoSolicitudWeb" value="0">
            </div>
            <div class="col-md-3">
              <label>Valido hasta</label>
              <input type="date" class="form-control" name="validoHastaSolicitudWeb" value="<?php echo htmlspecialchars($validoHasta, ENT_QUOTES, "UTF-8"); ?>">
            </div>
            <div class="col-md-3">
              <label>Neto</label>
              <input class="form-control" id="netoSolicitudWeb" readonly>
            </div>
            <div class="col-md-3">
              <label>Total final</label>
              <input class="form-control" id="totalSolicitudWeb" readonly>
            </div>
          </div>
          <div class="form-group" style="margin-top:15px">
            <label>Condiciones de la cotizacion</label>
            <textarea class="form-control" rows="6" name="condicionesSolicitudWeb"><?php echo htmlspecialchars($condiciones, ENT_QUOTES, "UTF-8"); ?></textarea>
          </div>
          <button class="btn btn-primary btn-lg btn-block" type="submit"><i class="fa fa-save"></i> Publicar cotizacion para el cliente</button>
        </div>
      </div>
      <?php ControladorCotizacion::ctrActualizarSolicitudWebCotizada(); ?>
    </form>
  </section>
</div>

<script>
function recalcularSolicitudWeb(){
  var productos = [];
  var neto = 0;
  $(".producto-web-row").each(function(){
    var cantidad = Number($(this).find(".cantidad-web").val() || 0);
    var precio = Number($(this).find(".precio-web").val() || 0);
    var total = cantidad * precio;
    neto += total;
    $(this).find(".total-web").val(total.toFixed(2));
    productos.push({
      id: $(this).attr("data-id"),
      descripcion: $(this).find(".descripcion-web").val(),
      cantidad: cantidad,
      precio: precio,
      total: total
    });
  });
  var descuento = Number($("#descuentoSolicitudWeb").val() || 0);
  var totalFinal = neto - (neto * descuento / 100);
  $("#netoSolicitudWeb").val(neto.toFixed(2));
  $("#totalSolicitudWeb").val(totalFinal.toFixed(2));
  $("#productosSolicitudWeb").val(JSON.stringify(productos));
}
$(".procesar-web-page").on("input", ".cantidad-web, .precio-web, #descuentoSolicitudWeb", recalcularSolicitudWeb);
$("#formProcesarSolicitudWeb").on("submit", recalcularSolicitudWeb);
recalcularSolicitudWeb();
</script>
