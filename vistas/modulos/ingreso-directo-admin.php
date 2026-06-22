<?php

if (($_SESSION["perfil"] ?? "") !== "Administrador") {
  echo '<script>window.location = "inicio";</script>';
  return;
}

function tmIngresoAdminEsc($valor) {
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

$dbIngresoAdmin = Conexion::conectar();
$categoriasIngresoAdmin = $dbIngresoAdmin->query(
  "SELECT c.id, c.categoria, c.id_padre,
          CASE WHEN p.categoria IS NULL THEN c.categoria ELSE CONCAT(p.categoria, ' > ', c.categoria) END AS ruta
   FROM categorias c
   LEFT JOIN categorias p ON p.id = c.id_padre
   ORDER BY COALESCE(p.categoria, c.categoria), c.id_padre IS NULL DESC, c.categoria"
)->fetchAll(PDO::FETCH_ASSOC);
$marcasIngresoAdmin = $dbIngresoAdmin->query(
  "SELECT id_marca, nombre FROM marcas WHERE estado = 1 ORDER BY nombre"
)->fetchAll(PDO::FETCH_ASSOC);
$historialIngresoAdmin = ModeloInventario::mdlMostrarIngresosDirectosAdmin(12);
?>

<div class="content-wrapper tm-direct-entry-page">
  <style>
    .tm-direct-entry-page .content{padding-top:10px}
    .tm-direct-hero{position:relative;overflow:hidden;display:flex;justify-content:space-between;align-items:center;gap:22px;margin-bottom:16px;padding:22px 24px;border-radius:24px;color:#fff;background:linear-gradient(135deg,#102f4b,#176b9b 56%,#25b8dd);box-shadow:0 22px 48px rgba(15,23,42,.16)}
    .tm-direct-hero:after{content:"";position:absolute;right:-80px;top:-120px;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,.12)}
    .tm-direct-hero-copy{position:relative;z-index:1;display:flex;align-items:center;gap:16px}
    .tm-direct-hero-icon{width:62px;height:62px;display:flex;align-items:center;justify-content:center;flex:0 0 62px;border-radius:20px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.24);font-size:28px}
    .tm-direct-hero h2{margin:0;font-size:27px;font-weight:950}.tm-direct-hero p{margin:6px 0 0;max-width:760px;color:rgba(255,255,255,.86);font-weight:700}
    .tm-direct-lock{position:relative;z-index:1;min-width:170px;padding:13px 15px;border:1px solid rgba(255,255,255,.24);border-radius:16px;background:rgba(255,255,255,.11);text-align:center}.tm-direct-lock i{display:block;font-size:22px;margin-bottom:5px}.tm-direct-lock strong{display:block;font-size:12px;text-transform:uppercase}
    .tm-direct-layout{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(300px,.65fr);gap:16px;align-items:start}
    .tm-direct-panel{border:1px solid rgba(180,204,230,.72);border-radius:22px;background:rgba(255,255,255,.76);box-shadow:0 18px 42px rgba(15,23,42,.08);overflow:hidden}
    .tm-direct-panel-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:17px 19px;border-bottom:1px solid rgba(180,204,230,.58);background:rgba(239,247,253,.58)}
    .tm-direct-panel-title{display:flex;align-items:center;gap:12px}.tm-direct-panel-title>span{width:42px;height:42px;display:flex;align-items:center;justify-content:center;border-radius:14px;color:#176b9b;background:#e5f4fc;font-size:19px}
    .tm-direct-panel-title h3{margin:0;font-size:17px;font-weight:950;color:#18364c}.tm-direct-panel-title small{display:block;margin-top:3px;color:#71859a;font-weight:700}
    .tm-direct-body{padding:18px}
    .tm-mode-switch{display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:5px;border-radius:15px;background:#edf4fa;margin-bottom:17px}
    .tm-mode-btn{border:0;border-radius:11px;padding:11px 12px;background:transparent;color:#597086;font-weight:900}.tm-mode-btn.is-active{color:#fff;background:#176b9b;box-shadow:0 8px 18px rgba(23,107,155,.22)}
    .tm-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.tm-form-span{grid-column:1/-1}
    .tm-field label{display:block;margin-bottom:6px;color:#29465e;font-size:11px;font-weight:950;text-transform:uppercase;letter-spacing:.04em}
    .tm-field input,.tm-field select,.tm-field textarea{width:100%;border:1px solid #cbdbea;border-radius:12px;background:rgba(255,255,255,.9);padding:11px 12px;color:#1d3448;font-weight:750;outline:0;transition:.18s}.tm-field textarea{min-height:92px;resize:vertical}
    .tm-field input:focus,.tm-field select:focus,.tm-field textarea:focus{border-color:#3c8dbc;box-shadow:0 0 0 3px rgba(60,141,188,.11)}
    .tm-field-help{display:block;margin-top:5px;color:#8294a5;font-size:11px;font-weight:650}
    .tm-product-picker{position:relative}.tm-product-results{position:absolute;left:0;right:0;top:calc(100% + 5px);z-index:20;display:none;max-height:310px;overflow:auto;border:1px solid #c7d9e9;border-radius:14px;background:#fff;box-shadow:0 18px 38px rgba(15,23,42,.16);padding:6px}
    .tm-product-result{width:100%;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:5px 12px;padding:10px;border:0;border-radius:10px;background:#fff;text-align:left}.tm-product-result:hover{background:#eef7fc}.tm-product-result strong{color:#18364c;font-size:12px}.tm-product-result span{color:#73879a;font-size:11px}.tm-product-result b{grid-row:1/3;grid-column:2;align-self:center;color:#176b9b;background:#e7f4fb;border-radius:8px;padding:5px 7px;font-size:11px}
    .tm-selected-product{display:none;margin-top:10px;padding:12px 13px;border:1px solid #bde4d5;border-radius:13px;background:#effbf6}.tm-selected-product strong{display:block;color:#116845}.tm-selected-product span{display:block;margin-top:3px;color:#537568;font-size:11px;font-weight:750}
    .tm-code-zone{grid-column:1/-1;padding:14px;border:1px dashed #a8c9e0;border-radius:16px;background:rgba(238,247,253,.7)}.tm-code-zone h4{margin:0 0 4px;color:#1d4e70;font-size:14px;font-weight:950}.tm-code-zone p{margin:0 0 12px;color:#70869a;font-size:11px;font-weight:700}
    .tm-code-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
    .tm-code-mode{display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-bottom:13px;padding:4px;border-radius:12px;background:#e3eef6}.tm-code-mode button{border:0;border-radius:9px;padding:9px;background:transparent;color:#587186;font-size:11px;font-weight:900}.tm-code-mode button.is-active{color:#fff;background:#176b9b}
    .tm-scanner-zone{display:none;padding:13px;border:1px solid #b9d5e7;border-radius:14px;background:rgba(255,255,255,.74)}.tm-scanner-input{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px}.tm-scanner-input button{border-radius:11px;font-weight:900}
    .tm-scanner-status{display:flex;justify-content:space-between;align-items:center;gap:10px;margin:10px 0 8px;color:#5d7488;font-size:11px;font-weight:850}.tm-scanner-status b{color:#176b9b;font-size:13px}
    .tm-scanned-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px;max-height:190px;overflow:auto}.tm-scanned-code{display:flex;align-items:center;justify-content:space-between;gap:7px;padding:8px 9px;border:1px solid #d4e4ef;border-radius:10px;background:#fff;color:#29485f;font-size:11px;font-weight:850}.tm-scanned-code button{width:23px;height:23px;padding:0;border:0;border-radius:7px;background:#fff0f0;color:#d9534f}
    .tm-price-summary{grid-column:1/-1;display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
    .tm-price-card{padding:12px;border:1px solid #d6e4ef;border-radius:14px;background:rgba(248,252,255,.88)}.tm-price-card span{display:block;color:#7890a4;font-size:10px;font-weight:900;text-transform:uppercase}.tm-price-card strong{display:block;margin-top:4px;color:#173d59;font-size:19px;font-weight:950}
    .tm-direct-actions{display:flex;justify-content:flex-end;gap:9px;margin-top:17px;padding-top:16px;border-top:1px solid #e0e9f1}.tm-direct-actions .btn{border-radius:12px;padding:11px 17px;font-weight:900}
    .tm-flow-list{display:grid;gap:10px}.tm-flow-step{display:grid;grid-template-columns:38px minmax(0,1fr);gap:10px;padding:12px;border:1px solid #d7e5f0;border-radius:15px;background:rgba(248,252,255,.82)}.tm-flow-step>span{width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:12px;color:#176b9b;background:#e5f4fc;font-weight:950}.tm-flow-step strong{display:block;color:#203f57}.tm-flow-step small{display:block;margin-top:3px;color:#73889b;line-height:1.35}
    .tm-history-list{display:grid;gap:9px}.tm-history-item{padding:12px;border:1px solid #d7e4ef;border-radius:14px;background:rgba(255,255,255,.72)}.tm-history-top{display:flex;justify-content:space-between;gap:8px}.tm-history-top strong{color:#183b55;font-size:12px}.tm-history-top b{color:#176b9b}.tm-history-item p{margin:5px 0;color:#5f7589;font-size:11px;font-weight:700}.tm-history-actions{display:flex;justify-content:space-between;align-items:center;gap:8px}.tm-history-actions span{font-size:10px;color:#8a9baa}.tm-history-actions a{font-size:11px;font-weight:900}
    .tm-empty-history{padding:25px;text-align:center;color:#7890a4}.tm-hidden{display:none!important}
    @media(max-width:1100px){.tm-direct-layout{grid-template-columns:1fr}.tm-direct-side{display:grid;grid-template-columns:1fr 1fr;gap:16px}}
    @media(max-width:767px){.tm-direct-hero{align-items:flex-start;flex-direction:column}.tm-direct-lock{width:100%}.tm-form-grid,.tm-code-grid,.tm-price-summary,.tm-direct-side{grid-template-columns:1fr}.tm-form-span,.tm-code-zone,.tm-price-summary{grid-column:auto}.tm-direct-actions{flex-direction:column}.tm-direct-actions .btn{width:100%}}
  </style>

  <section class="content-header">
    <h1>Ingreso directo de inventario <small>Proceso exclusivo del administrador</small></h1>
    <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Ingreso directo</li></ol>
  </section>

  <section class="content">
    <div class="tm-direct-hero">
      <div class="tm-direct-hero-copy">
        <span class="tm-direct-hero-icon"><i class="fa fa-cubes"></i></span>
        <div><h2>Inventario rápido del administrador</h2><p>Registra productos, precios, stock y etiquetas únicas en una sola operación, sin pasar por el flujo general de compras.</p></div>
      </div>
      <div class="tm-direct-lock"><i class="fa fa-lock"></i><strong>Solo administrador</strong><span>Operación registrada</span></div>
    </div>

    <div class="tm-direct-layout">
      <section class="tm-direct-panel">
        <div class="tm-direct-panel-head">
          <div class="tm-direct-panel-title"><span><i class="fa fa-sign-in"></i></span><div><h3>Nuevo ingreso directo</h3><small>Completa los datos y el sistema hará el resto.</small></div></div>
        </div>
        <div class="tm-direct-body">
          <form id="formIngresoDirectoAdmin" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="registrarIngresoDirecto">
            <input type="hidden" name="tipoProducto" id="tipoProductoIngreso" value="existente">
            <input type="hidden" name="idProducto" id="idProductoIngreso">
            <input type="hidden" name="codigosUnicos" id="codigosUnicosIngreso" value="[]">

            <div class="tm-mode-switch">
              <button type="button" class="tm-mode-btn is-active" data-mode="existente"><i class="fa fa-search"></i> Producto existente</button>
              <button type="button" class="tm-mode-btn" data-mode="nuevo"><i class="fa fa-plus-circle"></i> Crear producto nuevo</button>
            </div>

            <div class="tm-form-grid">
              <div class="tm-field tm-form-span" id="zonaProductoExistente">
                <label for="buscarProductoIngreso">Buscar producto</label>
                <div class="tm-product-picker">
                  <input type="search" id="buscarProductoIngreso" placeholder="Escribe nombre, código o categoría..." autocomplete="off">
                  <div class="tm-product-results" id="resultadosProductoIngreso"></div>
                </div>
                <div class="tm-selected-product" id="productoSeleccionadoIngreso"><strong></strong><span></span></div>
              </div>

              <div class="tm-field tm-form-span tm-hidden" data-new-field>
                <label>Nombre del producto *</label>
                <input type="text" name="descripcion" placeholder="Ej.: MOUSE INALÁMBRICO LOGITECH M185">
              </div>
              <div class="tm-field tm-hidden" data-new-field>
                <label>Categoría *</label>
                <select name="idCategoria">
                  <option value="">Seleccionar categoría</option>
                  <?php foreach($categoriasIngresoAdmin as $categoria): ?>
                    <option value="<?php echo (int)$categoria["id"]; ?>"><?php echo tmIngresoAdminEsc($categoria["ruta"]); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="tm-field tm-hidden" data-new-field>
                <label>Marca</label>
                <div style="display:grid;grid-template-columns:minmax(0,1fr) 42px;gap:7px">
                  <select name="idMarca" id="marcaIngresoAdmin">
                    <option value="">Sin marca</option>
                    <?php foreach($marcasIngresoAdmin as $marca): ?>
                      <option value="<?php echo (int)$marca["id_marca"]; ?>"><?php echo tmIngresoAdminEsc($marca["nombre"]); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="button" class="btn btn-info" id="btnNuevaMarcaIngreso" title="Agregar nueva marca" style="border-radius:12px"><i class="fa fa-plus"></i></button>
                </div>
              </div>
              <div class="tm-field tm-form-span tm-hidden" data-new-field>
                <label>Características principales</label>
                <textarea name="detalle" placeholder="Una característica por línea..."></textarea>
              </div>
              <div class="tm-field tm-form-span tm-hidden" data-new-field>
                <label>Imagen del producto</label>
                <input type="file" name="imagenProducto" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <span class="tm-field-help">Opcional. Máximo 5 MB.</span>
              </div>

              <div class="tm-field">
                <label>Cantidad a ingresar *</label>
                <input type="number" name="cantidad" id="cantidadIngresoAdmin" min="1" max="500" value="1" required>
              </div>
              <div class="tm-field">
                <label>Costo total estimado</label>
                <input type="text" id="costoTotalIngreso" value="Bs 0.00" readonly>
              </div>
              <div class="tm-field">
                <label>Precio de compra unitario *</label>
                <input type="number" name="precioCompra" id="precioCompraIngreso" min="0.01" step="0.01" required placeholder="0.00">
              </div>
              <div class="tm-field">
                <label>Precio de venta unitario *</label>
                <input type="number" name="precioVenta" id="precioVentaIngreso" min="0.01" step="0.01" required placeholder="0.00">
              </div>

              <div class="tm-code-zone">
                <h4><i class="fa fa-barcode"></i> Generación inteligente de códigos</h4>
                <p>Elige si TechMind debe generar las etiquetas o si las unidades ya traen un código que debes escanear.</p>
                <div class="tm-code-mode">
                  <button type="button" class="is-active" data-code-mode="automatico"><i class="fa fa-magic"></i> Generar automáticamente</button>
                  <button type="button" data-code-mode="escanear"><i class="fa fa-barcode"></i> Escanear códigos existentes</button>
                </div>
                <div class="tm-code-grid" id="zonaCodigosProducto">
                  <div class="tm-field">
                    <label>Código del producto</label>
                    <input type="text" name="codigoProducto" placeholder="Automático si se deja vacío">
                    <span class="tm-field-help">Para productos nuevos se generará con prefijo TM. En productos existentes se conserva el actual.</span>
                  </div>
                  <div class="tm-field">
                    <label>Código general</label>
                    <input type="text" name="codigoGeneral" placeholder="Automático si se deja vacío">
                    <span class="tm-field-help">Identifica el modelo o grupo completo, no una unidad individual.</span>
                  </div>
                  <div class="tm-field" id="zonaPrefijoAutomatico">
                    <label>Prefijo códigos únicos</label>
                    <input type="text" name="prefijoUnico" placeholder="Automático: TMU...">
                    <span class="tm-field-help">Solo se utiliza cuando TechMind genera las etiquetas.</span>
                  </div>
                </div>
                <div class="tm-scanner-zone" id="zonaEscanerCodigos">
                  <div class="tm-scanner-input">
                    <input type="text" id="lectorCodigoUnico" placeholder="Escanea el código y presiona Enter..." autocomplete="off">
                    <button type="button" class="btn btn-info" id="btnAgregarCodigoEscaneado"><i class="fa fa-plus"></i> Agregar</button>
                  </div>
                  <div class="tm-scanner-status">
                    <span>Códigos capturados: <b id="contadorCodigosEscaneados">0 / 1</b></span>
                    <button type="button" class="btn btn-xs btn-default" id="btnLimpiarCodigos"><i class="fa fa-trash"></i> Limpiar lista</button>
                  </div>
                  <div class="tm-scanned-list" id="listaCodigosEscaneados"></div>
                </div>
              </div>

              <div class="tm-field tm-form-span">
                <label>Observación del ingreso</label>
                <textarea name="observacion" placeholder="Ej.: Compra directa del administrador, proveedor, factura o referencia..."></textarea>
              </div>

              <div class="tm-price-summary">
                <div class="tm-price-card"><span>Unidades</span><strong id="resumenCantidadIngreso">1</strong></div>
                <div class="tm-price-card"><span>Inversión</span><strong id="resumenCompraIngreso">Bs 0.00</strong></div>
                <div class="tm-price-card"><span>Venta potencial</span><strong id="resumenVentaIngreso">Bs 0.00</strong></div>
              </div>
            </div>

            <div class="tm-direct-actions">
              <button type="reset" class="btn btn-default"><i class="fa fa-eraser"></i> Limpiar</button>
              <button type="submit" class="btn btn-primary" id="btnRegistrarIngresoAdmin"><i class="fa fa-check-circle"></i> Registrar stock y generar etiquetas</button>
            </div>
          </form>
        </div>
      </section>

      <aside class="tm-direct-side">
        <section class="tm-direct-panel">
          <div class="tm-direct-panel-head"><div class="tm-direct-panel-title"><span><i class="fa fa-magic"></i></span><div><h3>Qué hará el sistema</h3><small>Una sola confirmación.</small></div></div></div>
          <div class="tm-direct-body tm-flow-list">
            <div class="tm-flow-step"><span>1</span><div><strong>Producto y precios</strong><small>Crea o actualiza el producto con costo y precio de venta.</small></div></div>
            <div class="tm-flow-step"><span>2</span><div><strong>Stock inmediato</strong><small>Suma todas las unidades directamente al inventario disponible.</small></div></div>
            <div class="tm-flow-step"><span>3</span><div><strong>Códigos por unidad</strong><small>Genera una identificación irrepetible para cada producto físico.</small></div></div>
            <div class="tm-flow-step"><span>4</span><div><strong>Etiquetas listas</strong><small>Produce un PDF con códigos de barras para imprimir y pegar.</small></div></div>
          </div>
        </section>

        <section class="tm-direct-panel" style="margin-top:16px">
          <div class="tm-direct-panel-head"><div class="tm-direct-panel-title"><span><i class="fa fa-history"></i></span><div><h3>Últimos ingresos</h3><small>Trazabilidad administrativa.</small></div></div></div>
          <div class="tm-direct-body">
            <div class="tm-history-list">
              <?php foreach($historialIngresoAdmin as $ingreso): ?>
                <article class="tm-history-item">
                  <div class="tm-history-top"><strong><?php echo tmIngresoAdminEsc($ingreso["descripcion"]); ?></strong><b>+<?php echo (int)$ingreso["cantidad"]; ?></b></div>
                  <p><?php echo tmIngresoAdminEsc($ingreso["codigo_producto"]); ?> · Stock <?php echo (int)$ingreso["stock_anterior"]; ?> → <?php echo (int)$ingreso["stock_nuevo"]; ?></p>
                  <div class="tm-history-actions"><span><?php echo tmIngresoAdminEsc(date("d/m/Y H:i", strtotime($ingreso["fecha"]))); ?></span><a target="_blank" href="extensiones/tcpdf/pdf/etiquetas-ingreso-admin.php?id=<?php echo (int)$ingreso["id"]; ?>"><i class="fa fa-print"></i> Etiquetas</a></div>
                </article>
              <?php endforeach; ?>
              <?php if(!$historialIngresoAdmin): ?><div class="tm-empty-history"><i class="fa fa-inbox fa-2x"></i><p>Aún no existen ingresos directos.</p></div><?php endif; ?>
            </div>
          </div>
        </section>
      </aside>
    </div>
  </section>
</div>

<script>
(function(){
  var productosResultado = [];
  var temporizadorBusqueda = null;
  var modoCodigos = "automatico";
  var codigosEscaneados = [];
  var modo = "existente";
  var buscador = document.getElementById("buscarProductoIngreso");
  var resultados = document.getElementById("resultadosProductoIngreso");
  var seleccionado = document.getElementById("productoSeleccionadoIngreso");
  var form = document.getElementById("formIngresoDirectoAdmin");

  function esc(texto){ return $("<div>").text(texto == null ? "" : texto).html(); }
  function normal(texto){ return (texto || "").toString().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, ""); }
  function dinero(valor){ return "Bs " + (parseFloat(valor || 0)).toFixed(2); }

  function actualizarResumen(){
    var cantidad = Math.max(0, parseInt($("#cantidadIngresoAdmin").val(), 10) || 0);
    var compra = Math.max(0, parseFloat($("#precioCompraIngreso").val()) || 0);
    var venta = Math.max(0, parseFloat($("#precioVentaIngreso").val()) || 0);
    $("#resumenCantidadIngreso").text(cantidad);
    $("#resumenCompraIngreso").text(dinero(cantidad * compra));
    $("#costoTotalIngreso").val(dinero(cantidad * compra));
    $("#resumenVentaIngreso").text(dinero(cantidad * venta));
    $("#contadorCodigosEscaneados").text(codigosEscaneados.length + " / " + cantidad);
  }

  function renderCodigosEscaneados(){
    $("#codigosUnicosIngreso").val(JSON.stringify(codigosEscaneados));
    $("#listaCodigosEscaneados").html(codigosEscaneados.map(function(codigo, indice){
      return '<div class="tm-scanned-code"><span><i class="fa fa-barcode"></i> '+esc(codigo)+'</span><button type="button" data-remove-code="'+indice+'" title="Quitar"><i class="fa fa-times"></i></button></div>';
    }).join(""));
    actualizarResumen();
  }

  function agregarCodigoEscaneado(){
    var campo = document.getElementById("lectorCodigoUnico");
    var codigo = (campo.value || "").trim().toUpperCase();
    var cantidad = Math.max(0, parseInt($("#cantidadIngresoAdmin").val(),10) || 0);
    if(!codigo){ return; }
    if(!/^[A-Z0-9._\/-]+$/.test(codigo)){
      swal({type:"warning",title:"Código no válido",text:"Use solamente letras, números, puntos, guiones o barras.",confirmButtonText:"Revisar"}); return;
    }
    if(codigosEscaneados.indexOf(codigo) !== -1){
      swal({type:"warning",title:"Código repetido",text:"Este código ya fue capturado en la lista.",confirmButtonText:"Revisar"}); campo.select(); return;
    }
    if(codigosEscaneados.length >= cantidad){
      swal({type:"info",title:"Cantidad completa",text:"Ya capturó los "+cantidad+" códigos requeridos.",confirmButtonText:"Cerrar"}); return;
    }
    codigosEscaneados.push(codigo);
    campo.value = "";
    renderCodigosEscaneados();
    campo.focus();
  }

  function seleccionarProducto(producto){
    $("#idProductoIngreso").val(producto.id);
    buscador.value = producto.descripcion;
    seleccionado.style.display = "block";
    seleccionado.querySelector("strong").textContent = producto.descripcion;
    seleccionado.querySelector("span").textContent = (producto.codigo || "Sin código") + " · " + (producto.categoria || "Sin categoría") + " · Stock actual: " + producto.stock;
    $("#precioCompraIngreso").val(parseFloat(producto.precio_compra || 0) > 0 ? producto.precio_compra : "");
    $("#precioVentaIngreso").val(parseFloat(producto.precio_venta || 0) > 0 ? producto.precio_venta : "");
    form.elements.codigoProducto.value = producto.codigo || "";
    form.elements.codigoGeneral.value = producto.codigo_producto_generico || "";
    resultados.style.display = "none";
    actualizarResumen();
  }

  function buscarProductos(){
    var q = normal(buscador.value.trim());
    $("#idProductoIngreso").val("");
    seleccionado.style.display = "none";
    if(q.length < 2){ resultados.style.display = "none"; return; }
    resultados.innerHTML = '<div style="padding:15px;color:#7890a4;text-align:center"><i class="fa fa-spinner fa-spin"></i> Buscando...</div>';
    resultados.style.display = "block";
    clearTimeout(temporizadorBusqueda);
    temporizadorBusqueda = setTimeout(function(){
      $.ajax({
        url:"ajax/inventario-admin.ajax.php",
        method:"POST",
        dataType:"json",
        data:{accion:"buscarProductos",termino:buscador.value.trim()}
      }).done(function(res){
        productosResultado = res && res.productos ? res.productos : [];
        if(!productosResultado.length){
          resultados.innerHTML = '<div style="padding:15px;color:#7890a4;text-align:center">No se encontró el producto.</div>';
        }else{
          resultados.innerHTML = productosResultado.map(function(p){
            return '<button type="button" class="tm-product-result" data-id="'+p.id+'"><strong>'+esc(p.descripcion)+'</strong><span>'+esc(p.codigo+" · "+(p.categoria||"Sin categoría"))+'</span><b>Stock '+p.stock+'</b></button>';
          }).join("");
        }
      }).fail(function(){
        resultados.innerHTML = '<div style="padding:15px;color:#b23b3b;text-align:center">No se pudo consultar el inventario.</div>';
      });
    },180);
  }

  $(".tm-mode-btn").on("click", function(){
    modo = $(this).data("mode");
    $(".tm-mode-btn").removeClass("is-active");
    $(this).addClass("is-active");
    $("#tipoProductoIngreso").val(modo);
    $("[data-new-field]").toggleClass("tm-hidden", modo !== "nuevo");
    $("#zonaProductoExistente").toggleClass("tm-hidden", modo === "nuevo");
    if(modo === "nuevo"){
      $("#idProductoIngreso").val("");
      seleccionado.style.display = "none";
      buscador.value = "";
      form.elements.codigoProducto.value = "";
      form.elements.codigoGeneral.value = "";
      $("#precioCompraIngreso,#precioVentaIngreso").val("");
    }
    actualizarResumen();
  });

  $(buscador).on("input", buscarProductos);
  $(resultados).on("click", ".tm-product-result", function(){
    var id = parseInt($(this).data("id"), 10);
    var producto = productosResultado.find(function(p){ return parseInt(p.id,10) === id; });
    if(producto){ seleccionarProducto(producto); }
  });
  $(document).on("click", function(e){
    if(!$(e.target).closest(".tm-product-picker").length){ resultados.style.display = "none"; }
  });
  $("#cantidadIngresoAdmin,#precioCompraIngreso,#precioVentaIngreso").on("input", actualizarResumen);

  $("[data-code-mode]").on("click", function(){
    modoCodigos = $(this).data("code-mode");
    $("[data-code-mode]").removeClass("is-active");
    $(this).addClass("is-active");
    $("#zonaPrefijoAutomatico").toggle(modoCodigos === "automatico");
    $("#zonaEscanerCodigos").toggle(modoCodigos === "escanear");
    if(modoCodigos === "automatico"){
      codigosEscaneados = [];
      renderCodigosEscaneados();
    }else{
      setTimeout(function(){ $("#lectorCodigoUnico").focus(); },80);
    }
  });
  $("#btnAgregarCodigoEscaneado").on("click", agregarCodigoEscaneado);
  $("#lectorCodigoUnico").on("keydown", function(e){
    if(e.key === "Enter"){
      e.preventDefault();
      agregarCodigoEscaneado();
    }
  });
  $("#listaCodigosEscaneados").on("click", "[data-remove-code]", function(){
    codigosEscaneados.splice(parseInt($(this).data("remove-code"),10),1);
    renderCodigosEscaneados();
    $("#lectorCodigoUnico").focus();
  });
  $("#btnLimpiarCodigos").on("click", function(){
    codigosEscaneados = [];
    renderCodigosEscaneados();
    $("#lectorCodigoUnico").focus();
  });

  $("#btnNuevaMarcaIngreso").on("click", function(){
    swal({
      title:"Nueva marca",
      text:"Escriba el nombre de la marca que desea agregar.",
      input:"text",
      inputPlaceholder:"Ej.: Logitech",
      showCancelButton:true,
      confirmButtonText:"Agregar marca",
      cancelButtonText:"Cancelar",
      inputValidator:function(valor){
        return !valor || !valor.trim() ? "Ingrese el nombre de la marca." : null;
      }
    }).then(function(result){
      if(!result.value){ return; }
      $.ajax({
        url:"ajax/productos.ajax.php",
        method:"POST",
        dataType:"json",
        data:{crearMarcaRapida:1,nombreMarcaProducto:result.value,descripcionMarcaProducto:"Creada desde ingreso directo del administrador"}
      }).done(function(res){
        if(!res || !res.marca){
          swal({type:"error",title:"No se pudo agregar",text:"Revise el nombre e intente nuevamente.",confirmButtonText:"Cerrar"});
          return;
        }
        var opcion = new Option(res.marca.nombre, res.marca.id_marca, true, true);
        document.getElementById("marcaIngresoAdmin").appendChild(opcion);
        swal({type:"success",title:res.status === "exists" ? "Marca seleccionada" : "Marca agregada",text:res.marca.nombre,confirmButtonText:"Continuar"});
      }).fail(function(){
        swal({type:"error",title:"No se pudo agregar",text:"Ocurrió un problema al registrar la marca.",confirmButtonText:"Cerrar"});
      });
    });
  });

  $(form).on("reset", function(){
    setTimeout(function(){
      $("#idProductoIngreso").val("");
      seleccionado.style.display = "none";
      codigosEscaneados = [];
      renderCodigosEscaneados();
      $("[data-code-mode=automatico]").trigger("click");
      $(".tm-mode-btn[data-mode=existente]").trigger("click");
      actualizarResumen();
    },0);
  });

  $(form).on("submit", function(e){
    e.preventDefault();
    if(modo === "existente" && !$("#idProductoIngreso").val()){
      swal({type:"warning",title:"Seleccione un producto",text:"Busque y elija el producto que ingresará.",confirmButtonText:"Entendido"}); return;
    }
    if(modo === "nuevo" && (!form.elements.descripcion.value.trim() || !form.elements.idCategoria.value)){
      swal({type:"warning",title:"Producto incompleto",text:"Indique nombre y categoría para crear el producto.",confirmButtonText:"Entendido"}); return;
    }
    var compra = parseFloat($("#precioCompraIngreso").val()) || 0;
    var venta = parseFloat($("#precioVentaIngreso").val()) || 0;
    if(compra <= 0 || venta <= 0){
      swal({type:"warning",title:"Precios requeridos",text:"Registre precio de compra y precio de venta.",confirmButtonText:"Entendido"}); return;
    }
    if(venta < compra){
      swal({type:"warning",title:"Revise el precio",text:"El precio de venta no puede ser menor al costo de compra.",confirmButtonText:"Revisar"}); return;
    }

    var cantidad = parseInt($("#cantidadIngresoAdmin").val(),10) || 0;
    if(modoCodigos === "escanear" && codigosEscaneados.length !== cantidad){
      swal({
        type:"warning",
        title:"Faltan códigos por escanear",
        text:"La cantidad es "+cantidad+" y se capturaron "+codigosEscaneados.length+". Debe registrar un código por cada unidad.",
        confirmButtonText:"Continuar escaneando"
      });
      $("#lectorCodigoUnico").focus();
      return;
    }
    swal({
      type:"question",
      title:"¿Confirmar ingreso directo?",
      html:"Se agregarán <b>"+cantidad+" unidad(es)</b> al inventario y se generará una etiqueta única para cada una.",
      showCancelButton:true,
      confirmButtonText:"Sí, registrar",
      cancelButtonText:"Cancelar"
    }).then(function(result){
      if(!result.value){ return; }
      var boton = $("#btnRegistrarIngresoAdmin");
      boton.prop("disabled",true).html('<i class="fa fa-spinner fa-spin"></i> Registrando...');
      $.ajax({
        url:"ajax/inventario-admin.ajax.php",
        method:"POST",
        data:new FormData(form),
        processData:false,
        contentType:false,
        dataType:"json"
      }).done(function(res){
        if(res.status !== "ok"){
          swal({type:"error",title:"Ingreso cancelado",text:res.message || "No se pudo registrar el ingreso.",confirmButtonText:"Cerrar"});
          return;
        }
        swal({
          type:"success",
          title:"Inventario actualizado",
          html:
            '<div style="text-align:left;padding:0 10px">'+
              '<p><b>Producto:</b> '+esc(res.producto)+'</p>'+
              '<p><b>Ingreso:</b> '+res.cantidad+' unidad(es)</p>'+
              '<p><b>Stock:</b> '+res.stock_anterior+' → '+res.stock_nuevo+'</p>'+
              '<p><b>Código general:</b> '+esc(res.codigo_general)+'</p>'+
            '</div>',
          showCancelButton:true,
          confirmButtonText:"Imprimir etiquetas",
          cancelButtonText:"Cerrar"
        }).then(function(impresion){
          if(impresion.value){
            window.open("extensiones/tcpdf/pdf/etiquetas-ingreso-admin.php?id="+encodeURIComponent(res.id_ingreso),"_blank");
          }
          window.location.reload();
        });
      }).fail(function(xhr){
        var mensaje = "No se pudo registrar el ingreso.";
        try{ var r=JSON.parse(xhr.responseText); mensaje=r.message||mensaje; }catch(e){ if(xhr.responseJSON){mensaje=xhr.responseJSON.message||mensaje;} }
        swal({type:"error",title:"Ingreso cancelado",text:mensaje,confirmButtonText:"Cerrar"});
      }).always(function(){
        boton.prop("disabled",false).html('<i class="fa fa-check-circle"></i> Registrar stock y generar etiquetas');
      });
    });
  });

  actualizarResumen();
})();
</script>
