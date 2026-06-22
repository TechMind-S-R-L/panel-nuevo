<?php

$cotizaciones = ControladorCotizacion::ctrMostrarCotizacion(null, null);
$cotizaciones = is_array($cotizaciones) ? array_reverse($cotizaciones) : array();

if(!function_exists("tmCotTexto")){
  function tmCotTexto($valor){
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
  }
}

if(!function_exists("tmCotProductos")){
  function tmCotProductos($productos){
    $productos = is_array($productos) ? $productos : array();
    if(count($productos) == 0){
      echo '<div class="cot-productos-vacio">Sin productos registrados.</div>';
      return;
    }

    echo '<div class="cot-productos-lista">';
    foreach($productos as $producto){
      $descripcion = tmCotTexto($producto["descripcion"] ?? "Producto");
      $cantidad = max(1, (int)($producto["cantidad"] ?? 1));
      $precio = (float)($producto["precio"] ?? 0);
      $total = (float)($producto["total"] ?? ($cantidad * $precio));
      echo '<div class="cot-producto-item">
        <div>
          <strong>'.$descripcion.'</strong>
          <span>Cantidad: '.$cantidad.' | P. Unit.: Bs '.number_format($precio, 2).'</span>
        </div>
        <b>Bs '.number_format($total, 2).'</b>
      </div>';
    }
    echo '</div>';
  }
}

if(!function_exists("tmCotAcciones")){
  function tmCotAcciones($cotizacion){
    $codigo = tmCotTexto($cotizacion["codigo"] ?? "");
    $codigoUrl = urlencode((string)($cotizacion["codigo"] ?? ""));
    $id = (int)$cotizacion["id"];
    echo '<div class="cot-acciones">
      <a class="btn btn-info btnImprimirCotizacion" href="extensiones/tcpdf/pdf/cotizacion.php?idCotizacion='.$id.'&codigoCotizacion='.$codigoUrl.'" target="_blank" idCotizacion="'.$id.'" data-id-cotizacion="'.$id.'" codigoCotizacion="'.$codigo.'" data-codigo-cotizacion="'.$codigo.'" title="Imprimir cotizacion">
        <i class="fa fa-print"></i> Imprimir
      </a>';

    if($_SESSION["perfil"] == "Administrador"){
      echo '<a class="btn btn-danger btnEliminarCotizar" href="index.php?ruta=cotizacion&idCotizar='.$id.'" idCotizar="'.$id.'" data-id-cotizar="'.$id.'" title="Eliminar cotizacion">
        <i class="fa fa-times"></i> Eliminar
      </a>';
    }

    echo '</div>';
  }
}

if(!function_exists("tmCotRenderCards")){
  function tmCotRenderCards($cotizaciones){
    if(count($cotizaciones) == 0){
      echo '<div class="cot-vacio">
        <i class="fa fa-file-text-o"></i>
        <strong>No hay cotizaciones registradas</strong>
        <span>Cuando se cree una cotizacion aparecera aqui.</span>
      </div>';
      return;
    }

    echo '<div class="cot-grid">';
    foreach($cotizaciones as $cotizacion){
      $clienteData = ControladorClientes::ctrMostrarClientes("id", $cotizacion["id_cliente"] ?? 0);
      $usuarioData = ControladorUsuarios::ctrMostrarUsuarios("id", $cotizacion["id_user"] ?? 0);
      $productos = json_decode($cotizacion["productos"] ?? "[]", true);
      $productos = is_array($productos) ? $productos : array();
      $idModal = "modalCotizacion".(int)$cotizacion["id"];
      $codigo = tmCotTexto($cotizacion["codigo"] ?? "");
      $cliente = tmCotTexto($clienteData["nombre"] ?? "Sin cliente");
      $cotizador = tmCotTexto($usuarioData["nombre"] ?? "Sin cotizador");
      $fecha = tmCotTexto($cotizacion["fecha"] ?? "");
      $neto = number_format((float)($cotizacion["neto"] ?? 0), 2);
      $descuento = number_format((float)($cotizacion["descuento"] ?? 0), 2);
      $total = number_format((float)($cotizacion["total"] ?? 0), 2);
      $validoHasta = tmCotTexto($cotizacion["valido_hasta"] ?? "-");
      $condiciones = nl2br(tmCotTexto($cotizacion["condiciones"] ?? "Sin condiciones registradas."));

      echo '<div class="cot-card" data-toggle="modal" data-target="#'.$idModal.'">
        <div class="cot-card-top">
          <span>Cotizacion</span>
          <strong>#'.$codigo.'</strong>
        </div>
        <h4>'.$cliente.'</h4>
        <div class="cot-card-meta">
          <span><i class="fa fa-user"></i> '.$cotizador.'</span>
          <span><i class="fa fa-cubes"></i> '.count($productos).' producto(s)</span>
          <span><i class="fa fa-calendar"></i> '.$fecha.'</span>
        </div>
        <div class="cot-card-total">Bs '.$total.'</div>
        <div class="cot-card-hint">Clic para ver detalle y acciones</div>
      </div>

      <div class="modal fade cot-modal" id="'.$idModal.'" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
              <div class="cot-modal-title">
                <div class="cot-modal-icon"><i class="fa fa-file-text-o"></i></div>
                <div>
                  <span>Cotizacion emitida</span>
                  <h4 class="modal-title">Cotizacion #'.$codigo.'</h4>
                </div>
              </div>
            </div>
            <div class="modal-body">
              <div class="cot-resumen">
                <div><span>Cliente</span><strong>'.$cliente.'</strong></div>
                <div><span>Cotizador</span><strong>'.$cotizador.'</strong></div>
                <div><span>Fecha</span><strong>'.$fecha.'</strong></div>
                <div><span>Valido hasta</span><strong>'.$validoHasta.'</strong></div>
                <div><span>Neto</span><strong>Bs '.$neto.'</strong></div>
                <div><span>Descuento</span><strong>Bs '.$descuento.'</strong></div>
                <div><span>Total</span><strong>Bs '.$total.'</strong></div>
                <div><span>Productos</span><strong>'.count($productos).'</strong></div>
              </div>

              <h4 class="cot-modal-subtitle"><i class="fa fa-cubes"></i> Productos cotizados</h4>';
                tmCotProductos($productos);
              echo '<h4 class="cot-modal-subtitle"><i class="fa fa-list-alt"></i> Condiciones</h4>
              <div class="cot-condiciones">'.$condiciones.'</div>
              <h4 class="cot-modal-subtitle"><i class="fa fa-bolt"></i> Acciones</h4>';
                tmCotAcciones($cotizacion);
            echo '</div>
          </div>
        </div>
      </div>';
    }
    echo '</div>';
  }
}

?>

<div class="content-wrapper cotizaciones-page">
<style>
  .cotizaciones-page .cot-hero{
    background:linear-gradient(135deg,#163140,#2b7fb2);
    color:#fff;
    padding:20px;
    border-radius:12px;
    margin-bottom:16px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    flex-wrap:wrap;
    box-shadow:0 16px 36px rgba(22,49,64,.18);
  }
  .cot-hero h2{
    margin:0 0 6px;
    font-weight:900;
    font-size:24px;
  }
  .cot-hero p{
    margin:0;
    color:#d9ecf7;
  }
  .cot-hero .btn{
    border-radius:9px;
    font-weight:800;
    padding:10px 16px;
    border:0;
  }
  .cot-panel{
    background:rgba(255,255,255,.84);
    border:1px solid rgba(180,205,224,.7);
    border-radius:12px;
    padding:16px;
    box-shadow:0 14px 34px rgba(22,49,64,.10);
  }
  .cot-card-toolbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:14px;
  }
  .cot-card-search{
    position:relative;
    flex:1 1 320px;
    max-width:520px;
  }
  .cot-card-search i{
    position:absolute;
    left:13px;
    top:50%;
    transform:translateY(-50%);
    color:#6f8190;
  }
  .cot-card-search input{
    height:42px;
    border-radius:10px;
    border:1px solid #d7e4ed;
    padding-left:38px;
    box-shadow:none;
    font-weight:700;
  }
  .cot-card-search input:focus{
    border-color:#2b9fd4;
    box-shadow:0 0 0 3px rgba(43,159,212,.12);
  }
  .cot-card-counter{
    color:#607484;
    font-weight:800;
  }
  .cot-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(230px,1fr));
    gap:12px;
  }
  .cot-card{
    position:relative;
    min-height:172px;
    border-radius:12px;
    padding:14px;
    background:#fff;
    border:1px solid #dfe9f1;
    box-shadow:0 10px 24px rgba(23,50,72,.08);
    cursor:pointer;
    overflow:hidden;
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  }
  .cot-card:before{
    content:"";
    position:absolute;
    left:0;
    top:0;
    bottom:0;
    width:7px;
    background:#2b9fd4;
  }
  .cot-card:hover{
    transform:translateY(-3px);
    border-color:#8fc5e1;
    box-shadow:0 16px 34px rgba(22,49,64,.14);
  }
  .cot-card-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:9px;
  }
  .cot-card-top span{
    display:inline-flex;
    padding:5px 9px;
    border-radius:999px;
    background:#eaf5fb;
    color:#176b9b;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
  }
  .cot-card-top strong{
    color:#2a7298;
    font-size:14px;
  }
  .cot-card h4{
    margin:0 0 10px;
    color:#102b3b;
    font-size:16px;
    font-weight:900;
    line-height:1.25;
    overflow-wrap:anywhere;
  }
  .cot-card-meta{
    display:grid;
    gap:5px;
    color:#637786;
    font-weight:700;
    font-size:12px;
  }
  .cot-card-meta i{
    width:16px;
    color:#2b9fd4;
  }
  .cot-card-total{
    margin-top:11px;
    font-size:22px;
    font-weight:900;
    color:#163140;
  }
  .cot-card-hint{
    margin-top:6px;
    color:#78909f;
    font-size:12px;
    font-weight:800;
  }
  .cot-vacio{
    min-height:220px;
    border:1px dashed #b8cfdf;
    border-radius:12px;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:8px;
    color:#6b7e8c;
    background:#f8fbfd;
    text-align:center;
  }
  .cot-vacio.busqueda-vacia{
    grid-column:1 / -1;
    display:none;
  }
  .cot-vacio i{
    font-size:34px;
    color:#8fb1c8;
  }
  .cot-modal .modal-dialog{
    margin-top:42px;
  }
  .cot-modal .modal-content{
    border:0;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 28px 80px rgba(10,30,45,.34);
  }
  .cot-modal .modal-header{
    position:relative;
    color:#fff;
    border:0;
    padding:20px 24px;
    background:linear-gradient(135deg,#163140,#2b9fd4);
  }
  .cot-modal .modal-header:after{
    content:"";
    position:absolute;
    right:-40px;
    top:-55px;
    width:180px;
    height:180px;
    border-radius:50%;
    background:rgba(255,255,255,.16);
  }
  .cot-modal .close{
    position:relative;
    z-index:2;
    color:#fff;
    opacity:.9;
    text-shadow:none;
    width:36px;
    height:36px;
    border-radius:50%;
    background:rgba(255,255,255,.16);
    font-size:28px;
    line-height:34px;
  }
  .cot-modal-title{
    position:relative;
    z-index:1;
    display:flex;
    align-items:center;
    gap:14px;
  }
  .cot-modal-icon{
    width:54px;
    height:54px;
    border-radius:14px;
    background:rgba(255,255,255,.18);
    border:1px solid rgba(255,255,255,.28);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
  }
  .cot-modal-title span{
    display:inline-flex;
    padding:4px 10px;
    border-radius:999px;
    background:rgba(255,255,255,.16);
    font-size:12px;
    font-weight:900;
    text-transform:uppercase;
  }
  .cot-modal-title h4{
    margin:5px 0 0;
    font-size:23px;
    font-weight:900;
  }
  .cot-modal .modal-body{
    background:#f4f8fb;
    padding:20px;
  }
  .cot-resumen{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(170px,1fr));
    gap:12px;
    margin-bottom:16px;
  }
  .cot-resumen>div{
    background:#fff;
    border:1px solid #dbe7ef;
    border-radius:12px;
    padding:12px;
    min-height:72px;
    box-shadow:0 10px 24px rgba(22,49,64,.06);
  }
  .cot-resumen span{
    display:block;
    color:#6f8190;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
    margin-bottom:4px;
  }
  .cot-resumen strong{
    color:#1f2d3d;
    overflow-wrap:anywhere;
  }
  .cot-modal-subtitle{
    color:#163140;
    font-size:16px;
    font-weight:900;
    margin:16px 0 10px;
  }
  .cot-productos-lista{
    display:grid;
    gap:8px;
  }
  .cot-producto-item{
    display:flex;
    justify-content:space-between;
    gap:12px;
    padding:10px 12px;
    border:1px solid #dbe7ef;
    border-radius:10px;
    background:#fff;
  }
  .cot-producto-item strong{
    display:block;
    color:#203442;
    overflow-wrap:anywhere;
  }
  .cot-producto-item span{
    color:#6f8190;
    font-size:12px;
    font-weight:700;
  }
  .cot-producto-item b{
    white-space:nowrap;
    color:#176b9b;
  }
  .cot-productos-vacio,
  .cot-condiciones{
    background:#fff;
    border:1px solid #dbe7ef;
    border-radius:10px;
    padding:12px;
    color:#526879;
    font-weight:700;
  }
  .cot-acciones{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    background:#fff;
    border:1px solid #dbe7ef;
    border-radius:12px;
    padding:12px;
  }
  .cot-acciones .btn{
    border-radius:8px;
    font-weight:800;
    padding:9px 14px;
  }
  @media(max-width:767px){
    .cot-grid{
      grid-template-columns:1fr;
    }
    .cot-producto-item{
      flex-direction:column;
    }
  }
</style>

  <section class="content-header">
    <h1>Administrar cotizaciones</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Administrar cotizaciones</li>
    </ol>
  </section>

  <section class="content">
    <div class="cot-hero">
      <div>
        <h2>Cotizaciones registradas</h2>
        <p>Revise cotizaciones, productos, condiciones y documentos emitidos para clientes.</p>
      </div>
      <a href="crear-cotizacion" class="btn btn-primary">
        <i class="fa fa-plus"></i> Nueva cotizacion
      </a>
    </div>

    <div class="cot-panel">
      <div class="cot-card-toolbar">
        <div class="cot-card-search">
          <i class="fa fa-search"></i>
          <input type="text" class="form-control" id="buscarCotizacionesCards" placeholder="Buscar por codigo, cliente, cotizador, fecha o total">
        </div>
        <div class="cot-card-counter" id="contadorCotizacionesCards">
          <?php echo count($cotizaciones); ?> cotizacion(es)
        </div>
      </div>
      <?php tmCotRenderCards($cotizaciones); ?>
    </div>
  </section>
</div>

<script>
(function(){
  function normalizarTexto(valor){
    return (valor || "").toString().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  }

  function filtrarCotizacionesCards(){
    var buscador = document.getElementById("buscarCotizacionesCards");
    var termino = normalizarTexto(buscador ? buscador.value : "");
    var grid = document.querySelector(".cot-grid");
    var visibles = 0;

    if(!grid){
      return;
    }

    grid.querySelectorAll(".busqueda-vacia").forEach(function(vacio){ vacio.remove(); });

    grid.querySelectorAll(".cot-card").forEach(function(card){
      var coincide = normalizarTexto(card.textContent).indexOf(termino) !== -1;
      card.style.display = coincide ? "" : "none";
      if(coincide){
        visibles++;
      }
    });

    if(visibles === 0 && grid.querySelectorAll(".cot-card").length > 0){
      var mensaje = document.createElement("div");
      mensaje.className = "cot-vacio busqueda-vacia";
      mensaje.style.display = "flex";
      mensaje.innerHTML = "<i class=\"fa fa-search\"></i><strong>Sin coincidencias</strong><span>No hay cotizaciones que coincidan con la busqueda.</span>";
      grid.appendChild(mensaje);
    }

    var contador = document.getElementById("contadorCotizacionesCards");
    if(contador){
      contador.textContent = visibles + " cotizacion(es) encontradas";
    }
  }

  document.addEventListener("input", function(event){
    if(event.target && event.target.id === "buscarCotizacionesCards"){
      filtrarCotizacionesCards();
    }
  });
})();
</script>

<?php

  $borrarCotizacion = new ControladorCotizacion();
  $borrarCotizacion -> ctrEliminarCotizacion();

?>
