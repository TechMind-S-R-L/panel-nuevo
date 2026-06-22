<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "almacen") {
    echo '<script>window.location = "inicio";</script>';
    return;
}

if (isset($_POST["confirmarEntregaAlmacen"])) {
    $idEntregaConfirmada = (int)$_POST["confirmarEntregaAlmacen"];
    $respuesta = ControladorCompras::ctrConfirmarEntregaAlmacen($_POST["confirmarEntregaAlmacen"]);
    if ($respuesta == "ok") {
        echo '<script>
          swal({type:"success",title:"Entrega confirmada",confirmButtonText:"Cerrar"}).then(function(result){
            if(result.value){
              window.open("extensiones/tcpdf/pdf/boleta-entrega-compra-almacen.php?idCompra='.$idEntregaConfirmada.'", "_blank");
              window.location = "ordenes-ingreso-material";
            }
          });
        </script>';
    }
}

function oiEsc($valor) {
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function oiEstadoTexto($estado) {
    $mapa = array(
        "compra_rendida" => "Pendiente de entrega",
        "entregado_almacen" => "Listo para ingresar",
        "completado" => "Completado"
    );
    return $mapa[$estado] ?? ucwords(str_replace("_", " ", (string)$estado));
}

function oiProductosData($productosJson) {
    $productos = json_decode($productosJson ?? "[]", true);
    $items = 0;
    $unidades = 0;
    $html = "";

    if (is_array($productos)) {
        foreach ($productos as $producto) {
            $descripcion = oiEsc($producto["descripcion"] ?? "SIN DESCRIPCION");
            $cantidad = (int)($producto["cantidad"] ?? 0);
            $items++;
            $unidades += $cantidad;
            $html .= '<div class="oi-product-pill">
                        <div>
                          <strong>'.$descripcion.'</strong>
                          <span>Producto aprobado para ingreso</span>
                        </div>
                        <b>x '.$cantidad.'</b>
                      </div>';
        }
    }

    if ($html === "") {
        $html = '<div class="oi-product-pill empty">Sin productos registrados.</div>';
    }

    return array(
        "items" => $items,
        "unidades" => $unidades,
        "html" => $html
    );
}

function oiRenderCards($compras, $estados) {
    $contador = 0;

    if (!is_array($compras)) {
        $compras = array();
    }

    foreach ($compras as $compra) {
        $estado = trim((string)($compra["estado"] ?? ""));
        if (!in_array($estado, $estados)) {
            continue;
        }

        $contador++;
        $productos = oiProductosData($compra["productos"] ?? "[]");
        $proveedor = ControladorProveedor::ctrMostrarProveedor("id", $compra["id_proveedor"] ?? null);
        $proveedorNombre = $proveedor["nombre"] ?? "N/A";
        $codigo = $compra["codigo"] ?? $compra["id"];
        $fecha = $compra["fecha"] ?? "";
        $total = "Bs ".number_format((float)($compra["total"] ?? 0), 2);
        $estadoClase = $estado === "compra_rendida" ? "warning" : ($estado === "completado" ? "success" : "info");
        $busqueda = strtolower($codigo." ".$proveedorNombre." ".$estado." ".$fecha." ".strip_tags($productos["html"]));

        echo '<article class="oi-card estado-'.$estadoClase.'" data-search="'.oiEsc($busqueda).'">
                <div class="oi-card-top">
                  <div>
                    <span class="oi-code"><i class="fa fa-file-text-o"></i> Nota '.oiEsc($codigo).'</span>
                    <h3>'.oiEsc($proveedorNombre).'</h3>
                  </div>
                  <span class="oi-state">'.oiEsc(oiEstadoTexto($estado)).'</span>
                </div>

                <div class="oi-total-box">
                  <strong>'.$total.'</strong>
                  <span>Total aprobado</span>
                </div>

                <div class="oi-card-grid">
                  <div><span>Productos</span><b>'.(int)$productos["items"].' item(s)</b></div>
                  <div><span>Unidades</span><b>'.(int)$productos["unidades"].'</b></div>
                  <div><span>Fecha</span><b>'.oiEsc($fecha ?: "-").'</b></div>
                  <div><span>Estado</span><b>'.oiEsc(oiEstadoTexto($estado)).'</b></div>
                </div>

                <div class="oi-products-preview">'.$productos["html"].'</div>

                <div class="oi-actions">';

        if ($estado === "compra_rendida") {
            echo '<button type="button" class="btn btn-warning abrir-confirmar-entrega"
                    data-idcompra="'.(int)$compra["id"].'"
                    data-nota="'.oiEsc($codigo).'"
                    data-proveedor="'.oiEsc($proveedorNombre).'"
                    data-unidades="'.(int)$productos["unidades"].'"
                    title="Confirmar entrega del mensajero">
                    <i class="fa fa-handshake-o"></i> Confirmar entrega
                  </button>';
        } else if ($estado === "entregado_almacen") {
            echo '<button type="button" class="btn btn-primary agregar-producto"
                    data-idcompra="'.(int)$compra["id"].'"
                    title="Ingresar productos a stock">
                    <i class="fa fa-cart-plus"></i> Ingresar material
                    <span class="badge contador-productos">'.(int)$productos["unidades"].'</span>
                  </button>';
        } else {
            echo '<span class="oi-no-action"><i class="fa fa-check-circle"></i> Orden completada</span>';
        }

        echo '  </div>
              </article>';
    }

    if ($contador === 0) {
        echo '<div class="oi-empty">
                <i class="fa fa-inbox"></i>
                <strong>No hay ordenes en esta pestana.</strong>
                <span>Cuando exista material pendiente, aparecera aqui.</span>
              </div>';
    }
}

$compras = ControladorCompras::ctrRangoFechasCompras($_GET["fechaInicial"] ?? null, $_GET["fechaFinal"] ?? null);
$compras = is_array($compras) ? $compras : array();

$pendientesEntrega = array_values(array_filter($compras, function($compra){
    return trim((string)($compra["estado"] ?? "")) === "compra_rendida";
}));
$listasIngreso = array_values(array_filter($compras, function($compra){
    return trim((string)($compra["estado"] ?? "")) === "entregado_almacen";
}));
$completadas = array_values(array_filter($compras, function($compra){
    return trim((string)($compra["estado"] ?? "")) === "completado";
}));
?>

<div class="content-wrapper ordenes-ingreso-page">
  <style>
    .ordenes-ingreso-page .content{padding-top:10px;}
    .oi-hero{
      position:relative;
      margin-bottom:14px;
      padding:18px 20px;
      border-radius:18px;
      color:#fff;
      background:linear-gradient(135deg, rgba(16,49,67,.96), rgba(23,107,155,.88));
      box-shadow:0 18px 42px rgba(15,23,42,.13);
      overflow:hidden;
    }
    .oi-hero:after{
      content:"";
      position:absolute;
      right:-70px;
      top:-90px;
      width:230px;
      height:230px;
      border-radius:50%;
      background:rgba(255,255,255,.12);
    }
    .oi-hero h2{
      position:relative;
      z-index:1;
      margin:0 0 6px;
      font-size:25px;
      font-weight:950;
    }
    .oi-hero p{
      position:relative;
      z-index:1;
      margin:0;
      max-width:850px;
      color:rgba(255,255,255,.84);
      font-weight:700;
    }
    .oi-kpis{
      display:grid;
      grid-template-columns:repeat(3, minmax(0, 1fr));
      gap:12px;
      margin-bottom:14px;
    }
    .oi-kpi{
      display:flex;
      align-items:center;
      gap:12px;
      border:1px solid rgba(184,205,232,.65);
      border-radius:15px;
      background:rgba(255,255,255,.72);
      padding:13px 14px;
      box-shadow:0 14px 30px rgba(15,23,42,.07);
      min-width:0;
    }
    .oi-kpi i{
      width:42px;
      height:42px;
      border-radius:13px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      background:#3c8dbc;
      flex:0 0 auto;
      font-size:18px;
    }
    .oi-kpi span{
      display:block;
      color:#64788f;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
    }
    .oi-kpi strong{
      display:block;
      margin-top:2px;
      color:#1f2d3d;
      font-size:20px;
      font-weight:950;
    }
    .oi-panel{
      border:1px solid rgba(184,205,232,.68);
      border-radius:17px;
      background:rgba(255,255,255,.72);
      box-shadow:0 18px 42px rgba(15,23,42,.08);
      overflow:hidden;
    }
    .oi-toolbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      padding:14px 16px;
      border-bottom:1px solid rgba(184,205,232,.62);
      flex-wrap:wrap;
    }
    .oi-toolbar h3{
      margin:0;
      color:#203047;
      font-size:18px;
      font-weight:950;
    }
    .oi-search{
      position:relative;
      min-width:min(360px, 100%);
      flex:0 1 420px;
    }
    .oi-search i{
      position:absolute;
      left:13px;
      top:50%;
      transform:translateY(-50%);
      color:#6f8399;
    }
    .oi-search input{
      width:100%;
      height:39px;
      border:1px solid rgba(184,205,232,.88);
      border-radius:12px;
      padding:0 12px 0 38px;
      outline:0;
      font-weight:800;
      background:rgba(255,255,255,.9);
    }
    .oi-panel .nav-tabs{
      border-bottom:1px solid rgba(184,205,232,.62);
      padding:0 14px;
      background:rgba(255,255,255,.58);
    }
    .oi-panel .nav-tabs>li>a{
      border:0;
      border-radius:0;
      color:#52657a;
      font-weight:900;
      padding:13px 16px;
    }
    .oi-panel .nav-tabs>li.active>a,
    .oi-panel .nav-tabs>li.active>a:hover,
    .oi-panel .nav-tabs>li.active>a:focus{
      border:0;
      border-bottom:3px solid #3c8dbc;
      color:#173b5d;
      background:transparent;
    }
    .oi-panel .tab-content{padding:14px;}
    .oi-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(270px, 1fr));
      gap:12px;
    }
    .oi-card{
      position:relative;
      border:1px solid rgba(184,205,232,.72);
      border-radius:15px;
      background:rgba(255,255,255,.86);
      padding:13px;
      min-height:295px;
      display:flex;
      flex-direction:column;
      gap:10px;
      overflow:hidden;
      box-shadow:0 14px 30px rgba(15,23,42,.07);
    }
    .oi-card:before{
      content:"";
      position:absolute;
      inset:0 auto 0 0;
      width:5px;
      background:#9aa8b6;
    }
    .oi-card.estado-warning:before{background:#f39c12;}
    .oi-card.estado-info:before{background:#00a7d0;}
    .oi-card.estado-success:before{background:#00a65a;}
    .oi-card-top{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:10px;
      padding-left:4px;
    }
    .oi-code{
      color:#176b9b;
      font-size:11px;
      font-weight:950;
      text-transform:uppercase;
    }
    .oi-card h3{
      margin:5px 0 0;
      color:#1f2d3d;
      font-size:16px;
      font-weight:950;
      line-height:1.2;
      overflow-wrap:anywhere;
    }
    .oi-state{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-height:25px;
      max-width:116px;
      padding:5px 8px;
      border-radius:999px;
      background:#3c8dbc;
      color:#fff;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
      text-align:center;
      line-height:1.14;
    }
    .oi-card.estado-warning .oi-state{background:#f39c12;}
    .oi-card.estado-success .oi-state{background:#00a65a;}
    .oi-total-box{
      border-radius:13px;
      background:linear-gradient(135deg, rgba(60,141,188,.13), rgba(0,192,239,.08));
      padding:10px 12px;
      display:flex;
      align-items:flex-end;
      justify-content:space-between;
      gap:8px;
    }
    .oi-total-box strong{
      color:#0b4e78;
      font-size:19px;
      font-weight:950;
    }
    .oi-total-box span{
      color:#60748b;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .oi-card-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:8px;
    }
    .oi-card-grid div{
      border:1px solid rgba(184,205,232,.58);
      border-radius:11px;
      background:rgba(248,251,255,.76);
      padding:8px;
      min-width:0;
    }
    .oi-card-grid span{
      display:block;
      color:#718299;
      font-size:9.5px;
      font-weight:950;
      text-transform:uppercase;
    }
    .oi-card-grid b{
      display:block;
      margin-top:3px;
      color:#25364a;
      font-size:12px;
      line-height:1.2;
      overflow-wrap:anywhere;
    }
    .oi-products-preview{
      max-height:92px;
      overflow:auto;
      padding-right:3px;
      flex:1;
    }
    .oi-product-pill{
      display:flex;
      justify-content:space-between;
      gap:10px;
      border:1px solid rgba(184,205,232,.60);
      border-radius:11px;
      background:rgba(248,251,255,.85);
      padding:8px 9px;
      margin-bottom:7px;
    }
    .oi-product-pill strong{
      display:block;
      color:#23344a;
      font-size:12px;
      line-height:1.22;
      overflow-wrap:anywhere;
    }
    .oi-product-pill span{
      display:block;
      color:#718299;
      font-size:9px;
      font-weight:850;
      text-transform:uppercase;
      margin-top:2px;
    }
    .oi-product-pill b{
      color:#0b4e78;
      white-space:nowrap;
      font-weight:950;
    }
    .oi-actions{
      border-top:1px dashed rgba(184,205,232,.75);
      padding-top:10px;
      margin-top:auto;
    }
    .oi-actions .btn,
    .oi-action-form .btn{
      width:100%;
      min-height:39px;
      border-radius:11px;
      font-weight:950;
      white-space:normal;
      line-height:1.14;
    }
    .oi-action-form{margin:0;}
    .oi-no-action{
      display:flex;
      align-items:center;
      justify-content:center;
      gap:7px;
      color:#00a65a;
      font-weight:950;
      min-height:38px;
    }
    .oi-empty{
      grid-column:1 / -1;
      min-height:190px;
      border:1px dashed rgba(60,141,188,.35);
      border-radius:16px;
      color:#6d7f93;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:7px;
      background:rgba(255,255,255,.58);
      text-align:center;
    }
    .oi-empty i{font-size:30px;color:#3c8dbc;}
    .oi-confirm-modal .modal-dialog{width:min(560px, calc(100vw - 34px));}
    .oi-confirm-modal .modal-content{
      border:0;
      border-radius:20px;
      overflow:hidden;
      box-shadow:0 28px 70px rgba(15,23,42,.28);
      background:#f6f9fd;
    }
    .oi-confirm-head{
      display:flex;
      align-items:center;
      gap:13px;
      padding:18px 20px;
      color:#fff;
      background:linear-gradient(135deg,#12384f,#1f84bd);
    }
    .oi-confirm-head i{
      width:45px;
      height:45px;
      border-radius:14px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.18);
      font-size:21px;
      flex:0 0 auto;
    }
    .oi-confirm-head h4{
      margin:0 0 3px;
      font-size:19px;
      font-weight:950;
    }
    .oi-confirm-head p{
      margin:0;
      color:rgba(255,255,255,.82);
      font-weight:750;
    }
    .oi-confirm-body{padding:18px 20px;}
    .oi-confirm-question{
      margin:0 0 14px;
      color:#21344d;
      font-size:16px;
      font-weight:900;
      line-height:1.35;
    }
    .oi-confirm-summary{
      display:grid;
      grid-template-columns:repeat(3, minmax(0, 1fr));
      gap:8px;
      margin-bottom:14px;
    }
    .oi-confirm-summary div{
      border:1px solid rgba(184,205,232,.72);
      border-radius:13px;
      background:rgba(255,255,255,.82);
      padding:10px;
      min-width:0;
    }
    .oi-confirm-summary span{
      display:block;
      color:#6e8198;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
    }
    .oi-confirm-summary b{
      display:block;
      margin-top:4px;
      color:#173b5d;
      font-size:13px;
      font-weight:950;
      overflow-wrap:anywhere;
    }
    .oi-confirm-alert{
      display:flex;
      gap:10px;
      border-radius:13px;
      background:#fff7e6;
      border:1px solid #ffd487;
      color:#8a5b00;
      padding:11px 12px;
      font-weight:800;
      line-height:1.3;
    }
    .oi-confirm-actions{
      display:flex;
      justify-content:flex-end;
      gap:9px;
      padding:0 20px 18px;
    }
    .oi-confirm-actions .btn{
      border-radius:11px;
      min-width:132px;
      font-weight:950;
    }
    .oi-modal .modal-dialog{width:min(980px, calc(100vw - 34px));}
    .oi-modal .modal-content{
      border:0;
      border-radius:20px;
      overflow:hidden;
      box-shadow:0 28px 75px rgba(15,23,42,.30);
      background:#f4f8fc;
    }
    .oi-modal .modal-header{
      position:relative;
      border:0;
      color:#fff;
      padding:15px 20px;
      background:linear-gradient(135deg,#12384f,#176b9b);
    }
    .oi-modal .modal-title{
      display:flex;
      align-items:center;
      gap:11px;
      font-size:20px;
      font-weight:950;
    }
    .oi-modal .modal-title i{
      width:39px;
      height:39px;
      border-radius:12px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.18);
    }
    .oi-modal .close{
      color:#fff;
      opacity:.95;
      text-shadow:none;
      font-size:26px;
    }
    .oi-modal .modal-body{padding:15px;background:#f4f8fc;}
    .oi-modal-layout{
      display:grid;
      grid-template-columns:330px minmax(0, 1fr);
      gap:14px;
    }
    .oi-modal-panel{
      border:1px solid rgba(184,205,232,.75);
      border-radius:16px;
      background:rgba(255,255,255,.9);
      padding:13px;
      box-shadow:0 14px 28px rgba(15,23,42,.06);
    }
    .oi-modal-panel h4{
      margin:0 0 11px;
      color:#173b5d;
      font-size:15px;
      font-weight:950;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .oi-pending-list{
      max-height:420px;
      overflow:auto;
      padding-right:3px;
    }
    .producto-click{
      border:1px solid rgba(184,205,232,.72);
      border-radius:13px;
      padding:10px;
      margin-bottom:8px;
      cursor:pointer;
      background:#f8fbff;
      transition:.16s ease;
    }
    .producto-click:hover,
    .producto-click.seleccionado{
      border-color:#3c8dbc;
      background:#edf8ff;
      box-shadow:0 10px 20px rgba(60,141,188,.12);
    }
    .producto-click strong{
      display:block;
      color:#203047;
      font-size:13px;
      font-weight:950;
      line-height:1.25;
    }
    .producto-click .oi-pending-meta{
      display:block;
      margin-top:7px;
      color:#61758b;
      font-size:11px;
      font-weight:850;
    }
    .oi-form-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0,1fr));
      gap:10px;
    }
    .oi-field label{
      display:block;
      color:#61758b;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
      margin-bottom:5px;
    }
    .oi-field .form-control{
      height:39px;
      border-radius:11px;
      border-color:#d6e3ee;
      box-shadow:none;
      font-weight:800;
    }
    .oi-code-entry{
      display:grid;
      grid-template-columns:minmax(0, 1fr) auto;
      gap:8px;
    }
    .oi-code-entry .btn{
      height:39px;
      border-radius:11px;
      font-weight:950;
    }
    .oi-code-options{
      display:grid;
      grid-template-columns:minmax(0,1fr) auto;
      gap:9px;
      margin-top:10px;
    }
    .oi-code-options .btn,
    .oi-print-reception{
      border-radius:10px;
      font-weight:900;
      white-space:normal;
    }
    .oi-print-reception{
      display:none;
      width:100%;
      margin-top:10px;
    }
    #listaUnidades{
      max-height:160px;
      overflow:auto;
      margin-top:10px;
      margin-bottom:0;
    }
    #listaUnidades .list-group-item{
      border-radius:10px;
      margin-bottom:6px;
      border-color:#d6e3ee;
      font-weight:850;
    }
    body.tm-dark-mode .oi-panel,
    body.tm-dark-mode .oi-card,
    body.tm-dark-mode .oi-kpi,
    body.tm-dark-mode .oi-confirm-modal .modal-content,
    body.dark-mode .oi-panel,
    body.dark-mode .oi-card,
    body.dark-mode .oi-kpi,
    body.dark-mode .oi-confirm-modal .modal-content{
      background:rgba(15,23,42,.72);
      border-color:rgba(99,135,184,.45);
    }
    body.tm-dark-mode .oi-card h3,
    body.tm-dark-mode .oi-card-grid b,
    body.tm-dark-mode .oi-kpi strong,
    body.dark-mode .oi-card h3,
    body.dark-mode .oi-card-grid b,
    body.dark-mode .oi-kpi strong,
    body.tm-dark-mode .oi-confirm-question,
    body.dark-mode .oi-confirm-question{color:#f8fbff;}
    @media (max-width: 991px){
      .oi-kpis{grid-template-columns:1fr;}
      .oi-modal-layout{grid-template-columns:1fr;}
      .oi-form-grid{grid-template-columns:1fr;}
    }
  </style>

  <section class="content-header">
    <h1>Ordenes de Ingreso de Material</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Ordenes de Ingreso de Material</li>
    </ol>
  </section>

  <section class="content">
    <div class="oi-hero">
      <h2><i class="fa fa-archive"></i> Ingreso de material a almacen</h2>
      <p>Confirma la entrega del mensajero y registra los codigos unicos de cada unidad antes de sumar el stock.</p>
    </div>

    <div class="oi-kpis">
      <div class="oi-kpi">
        <i class="fa fa-handshake-o"></i>
        <div><span>Por confirmar</span><strong><?php echo count($pendientesEntrega); ?></strong></div>
      </div>
      <div class="oi-kpi">
        <i class="fa fa-cart-plus"></i>
        <div><span>Para ingresar</span><strong><?php echo count($listasIngreso); ?></strong></div>
      </div>
      <div class="oi-kpi">
        <i class="fa fa-check-circle"></i>
        <div><span>Completadas</span><strong><?php echo count($completadas); ?></strong></div>
      </div>
    </div>

    <div class="oi-panel">
      <div class="oi-toolbar">
        <h3><i class="fa fa-list-alt"></i> Seguimiento de ingresos</h3>
        <div class="oi-search">
          <i class="fa fa-search"></i>
          <input type="text" id="buscarOrdenIngresoCards" placeholder="Buscar nota, proveedor, producto o estado">
        </div>
      </div>

      <ul class="nav nav-tabs">
        <li class="active"><a href="#tabOiEntrega" data-toggle="tab">Por confirmar <span class="badge bg-yellow"><?php echo count($pendientesEntrega); ?></span></a></li>
        <li><a href="#tabOiIngreso" data-toggle="tab">Para ingresar <span class="badge bg-blue"><?php echo count($listasIngreso); ?></span></a></li>
        <li><a href="#tabOiCompletadas" data-toggle="tab">Completadas <span class="badge bg-green"><?php echo count($completadas); ?></span></a></li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane active" id="tabOiEntrega">
          <div class="oi-grid">
            <?php oiRenderCards($compras, array("compra_rendida")); ?>
          </div>
        </div>
        <div class="tab-pane" id="tabOiIngreso">
          <div class="oi-grid">
            <?php oiRenderCards($compras, array("entregado_almacen")); ?>
          </div>
        </div>
        <div class="tab-pane" id="tabOiCompletadas">
          <div class="oi-grid">
            <?php oiRenderCards($compras, array("completado")); ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div id="modalConfirmarEntregaAlmacen" class="modal fade oi-confirm-modal" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="formConfirmarEntregaAlmacen">
        <div class="oi-confirm-head">
          <i class="fa fa-handshake-o"></i>
          <div>
            <h4>Confirmar entrega a almacen</h4>
            <p>Valida la constancia antes de habilitar el ingreso de productos.</p>
          </div>
        </div>

        <div class="oi-confirm-body">
          <p class="oi-confirm-question">¿Confirmas que el mensajero entrego los productos a almacen?</p>

          <div class="oi-confirm-summary">
            <div>
              <span>Nota</span>
              <b id="confirmEntregaNota">-</b>
            </div>
            <div>
              <span>Proveedor</span>
              <b id="confirmEntregaProveedor">-</b>
            </div>
            <div>
              <span>Unidades</span>
              <b id="confirmEntregaUnidades">0</b>
            </div>
          </div>

          <div class="oi-confirm-alert">
            <i class="fa fa-info-circle"></i>
            <span>Al confirmar, se imprimira la boleta de conformidad y esta solicitud pasara a la pestana Para ingresar.</span>
          </div>
        </div>

        <div class="oi-confirm-actions">
          <button type="button" class="btn btn-default" data-dismiss="modal">
            <i class="fa fa-times"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-warning">
            <i class="fa fa-check"></i> Si, confirmar
          </button>
        </div>

        <input type="hidden" name="confirmarEntregaAlmacen" id="confirmEntregaId">
      </form>
    </div>
  </div>
</div>

<div id="modalEditarProducto" class="modal fade oi-modal" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEditarProducto" method="post" enctype="multipart/form-data">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-cart-plus"></i> Ingresar material a stock</h4>
        </div>

        <div class="modal-body">
          <div class="oi-modal-layout">
            <aside class="oi-modal-panel">
              <h4><i class="fa fa-list"></i> Productos pendientes</h4>
              <div id="listaProductosEditar" class="oi-pending-list">
                <em>Seleccione una orden para ver los productos...</em>
              </div>
            </aside>

            <main class="oi-modal-panel">
              <h4><i class="fa fa-barcode"></i> Registro de unidad</h4>

              <input type="hidden" id="idProductoHidden" name="idProducto">
              <input type="hidden" name="idCategoria">

              <div class="oi-form-grid">
                <div class="oi-field">
                  <label>Categoria</label>
                  <input type="text" class="form-control" id="editarCategoria" readonly>
                </div>
                <div class="oi-field">
                  <label>Codigo</label>
                  <input type="text" class="form-control" id="editarCodigo" name="editarCodigo" readonly>
                </div>
                <div class="oi-field">
                  <label>Descripcion</label>
                  <input type="text" class="form-control" id="editarDescripcion" name="editarDescripcion" readonly>
                </div>
                <div class="oi-field">
                  <label>Codigo generico</label>
                  <input type="text" class="form-control" id="editarCodigoGenerico" readonly>
                </div>
                <div class="oi-field">
                  <label>Stock actual</label>
                  <input type="number" class="form-control" id="editarStock" min="0" readonly>
                </div>
              </div>

              <hr>

              <div class="oi-field">
                <label>Código único de la unidad</label>
                <div class="oi-code-entry">
                  <input type="text" class="form-control" id="nuevoCodigoUnico" placeholder="Escanea o escribe el código del producto">
                  <button type="button" class="btn btn-success" onclick="agregarUnidad()">
                    <i class="fa fa-barcode"></i> Registrar
                  </button>
                </div>
                <div class="oi-code-options">
                  <div class="alert alert-info" style="margin:0;padding:9px 11px;border-radius:10px">
                    <i class="fa fa-info-circle"></i>
                    Si la unidad no tiene código, TechMind generará uno único.
                  </div>
                  <button type="button" class="btn btn-primary" onclick="agregarUnidad(true)">
                    <i class="fa fa-magic"></i> Generar código único
                  </button>
                </div>
                <button type="button" id="imprimirCodigosRecepcion" class="btn btn-info oi-print-reception">
                  <i class="fa fa-print"></i> Imprimir códigos de esta recepción
                </button>
              </div>

              <ul id="listaUnidades" class="list-group"></ul>
            </main>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let cantidadRestante = 0;
let unidadesIngresadas = [];
let productoSeleccionado = null;
let botonCompraActivo = null;

$(document).ready(function () {
    $('.agregar-producto').each(function () {
        actualizarBotonCompra($(this));
    });

    $(document).on('input', '#buscarOrdenIngresoCards', function () {
        const termino = ($(this).val() || '').toLowerCase();
        $('.oi-grid').each(function () {
            let visibles = 0;
            const grid = $(this);
            grid.find('.oi-empty.busqueda-vacia').remove();
            grid.find('.oi-card').each(function () {
                const card = $(this);
                const coincide = !termino || (card.data('search') || card.text()).toString().toLowerCase().indexOf(termino) !== -1;
                card.toggle(coincide);
                if (coincide) visibles++;
            });
            if (visibles === 0 && grid.find('.oi-card').length > 0) {
                grid.append('<div class="oi-empty busqueda-vacia"><i class="fa fa-search"></i><strong>No hay ordenes que coincidan.</strong><span>Prueba con otra nota, proveedor o producto.</span></div>');
            }
        });
    });

    $(document).on('click', '.agregar-producto', function () {
        botonCompraActivo = $(this);
        const idCompra = botonCompraActivo.data('idcompra');
        $('#modalEditarProducto').modal('show').data('idcompra', idCompra);
        cargarResumenIngreso(idCompra);
    });

    $(document).on('click', '.abrir-confirmar-entrega', function () {
        const boton = $(this);
        $('#confirmEntregaId').val(boton.data('idcompra'));
        $('#confirmEntregaNota').text(boton.data('nota') || '-');
        $('#confirmEntregaProveedor').text(boton.data('proveedor') || '-');
        $('#confirmEntregaUnidades').text(boton.data('unidades') || '0');
        $('#modalConfirmarEntregaAlmacen').modal('show');
    });

    $(document).on('click', '.producto-click', function () {
        $('.producto-click').removeClass('seleccionado');
        productoSeleccionado = $(this).addClass('seleccionado');
        const idProducto = productoSeleccionado.data('idproducto');
        cantidadRestante = parseInt(productoSeleccionado.data('restante'), 10);
        $('#idProductoHidden').val(idProducto);
        unidadesIngresadas = [];
        $('#listaUnidades').html('');
        $('#imprimirCodigosRecepcion').hide().off('click');

        $.ajax({
            url: 'ajax/productos.ajax.php',
            method: 'POST',
            data: { idProducto: idProducto },
            dataType: 'json',
            success: function (res) {
                if (!res) return;

                $('#editarCodigo').val(res.codigo);
                $('#idProductoHidden').val(res.id);
                $('#editarDescripcion').val(res.descripcion);
                $('#editarCodigoGenerico').val(res.codigo_producto_generico);
                $('#editarStock').val(res.stock);

                $.ajax({
                    url: 'ajax/categorias.ajax.php',
                    method: 'POST',
                    data: { idCategoria: res.id_categoria },
                    dataType: 'json',
                    success: function (cat) {
                        if (cat) {
                            $('#editarCategoria').val(cat.categoria);
                            $('input[name="idCategoria"]').val(cat.id);
                        }
                    }
                });
            },
            error: function () {
                swal({type:'error', title:'No se pudo cargar el producto', text:'Actualice la pagina e intente nuevamente.', confirmButtonText:'Cerrar'});
            }
        });
    });

    $('#formEditarProducto').submit(function (e) {
        e.preventDefault();
    });

    $('#modalEditarProducto').on('hidden.bs.modal', function () {
        $('#formEditarProducto')[0].reset();
        $('#listaProductosEditar').html('<em>Seleccione una orden para ver los productos...</em>');
        $('#listaUnidades').html('');
        $('#nuevoCodigoUnico').val('');
        $('#imprimirCodigosRecepcion').hide().off('click');
        cantidadRestante = 0;
        unidadesIngresadas = [];
        productoSeleccionado = null;
    });
});

function actualizarBotonCompra($boton) {
    const idCompra = $boton.data('idcompra');

    $.ajax({
        url: 'ajax/inventario.ajax.php',
        method: 'POST',
        data: { accion: 'resumenIngreso', idCompra: idCompra },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'ok') {
                const restante = parseInt(response.totalRestante, 10);
                $boton.find('.contador-productos').text(restante);
                $boton.prop('disabled', restante <= 0);
            }
        }
    });
}

function cargarResumenIngreso(idCompra) {
    $('#listaProductosEditar').html('<em>Cargando productos pendientes...</em>');

    $.ajax({
        url: 'ajax/inventario.ajax.php',
        method: 'POST',
        data: { accion: 'resumenIngreso', idCompra: idCompra },
        dataType: 'json',
        success: function (response) {
            if (response.status !== 'ok') {
                $('#listaProductosEditar').html('<em>' + (response.message || 'No se pudo cargar la orden.') + '</em>');
                return;
            }

            if (botonCompraActivo) {
                const restanteTotal = parseInt(response.totalRestante, 10);
                botonCompraActivo.find('.contador-productos').text(restanteTotal);
                botonCompraActivo.prop('disabled', restanteTotal <= 0);
            }

            let html = '';
            response.productos.forEach(function (producto) {
                if (parseInt(producto.restante, 10) <= 0) return;

                html += `
                    <div class="producto-click"
                         data-idproducto="${producto.id}"
                         data-aprobado="${producto.aprobado}"
                         data-ingresado="${producto.ingresado}"
                         data-restante="${producto.restante}">
                        <strong>${producto.descripcion}</strong>
                        <span class="oi-pending-meta">
                            Pendiente: <b>${producto.restante}</b> / Aprobado: ${producto.aprobado}
                        </span>
                    </div>`;
            });

            $('#listaProductosEditar').html(html || '<em>Todos los productos de esta solicitud ya fueron ingresados.</em>');
        },
        error: function () {
            $('#listaProductosEditar').html('<em>Error al cargar productos pendientes.</em>');
        }
    });
}

function agregarUnidad(generarCodigo) {
    const idProducto = $('#idProductoHidden').val();
    const codigoUnico = ($('#nuevoCodigoUnico').val() || '').trim();
    generarCodigo = generarCodigo === true;

    if (!idProducto) {
        swal({type:'warning', title:'Seleccione un producto', text:'Primero elija uno de los productos pendientes de la solicitud.', confirmButtonText:'Entendido'});
        return;
    }

    if (!generarCodigo && !codigoUnico) {
        swal({type:'warning', title:'Código requerido', text:'Escanee el código de la unidad o use Generar código único.', confirmButtonText:'Entendido'});
        return;
    }

    if (!generarCodigo && unidadesIngresadas.includes(codigoUnico)) {
        swal({type:'warning', title:'Código duplicado', text:'Este código único ya fue registrado en la orden.', confirmButtonText:'Revisar'});
        return;
    }

    if (cantidadRestante <= 0) {
        swal({type:'info', title:'Producto completado', text:'Todas las unidades requeridas de este producto ya fueron ingresadas.', confirmButtonText:'Cerrar'});
        return;
    }

    $.ajax({
        url: 'ajax/inventario.ajax.php',
        method: 'POST',
        data: {
            idCompra: $('#modalEditarProducto').data('idcompra'),
            idProducto: idProducto,
            codigoBarrasUnico: codigoUnico,
            generarCodigo: generarCodigo ? 1 : 0
        },
        dataType: 'json',
        success: function (response) {
            if (response.status !== 'ok') {
                swal({type:'error', title:'No se pudo registrar la unidad', text:response.message || 'Revise el codigo e intente nuevamente.', confirmButtonText:'Cerrar'});
                return;
            }

            const codigoRegistrado = response.codigo || codigoUnico;
            unidadesIngresadas.push(codigoRegistrado);
            $('#listaUnidades').append(
              `<li class="list-group-item">
                <i class="fa fa-check text-green"></i>
                <strong>${$('<div>').text(codigoRegistrado).html()}</strong>
                <span class="pull-right label ${response.generado ? 'label-primary' : 'label-info'}">
                  ${response.generado ? 'Generado' : 'Escaneado'}
                </span>
              </li>`
            );
            $('#nuevoCodigoUnico').val('').focus();

            const idCompra = $('#modalEditarProducto').data('idcompra');
            $('#imprimirCodigosRecepcion')
              .show()
              .off('click')
              .on('click', function(){
                  window.open(
                    'extensiones/tcpdf/pdf/etiquetas-ingreso-compra.php?compra='+
                    encodeURIComponent(idCompra)+'&producto='+encodeURIComponent(idProducto),
                    '_blank'
                  );
              });

            const stock = parseInt($('#editarStock').val(), 10) || 0;
            $('#editarStock').val(stock + 1);

            cantidadRestante = parseInt(response.restante, 10);

            if (productoSeleccionado) {
                productoSeleccionado.attr('data-ingresado', response.ingresado);
                productoSeleccionado.attr('data-restante', response.restante);
                productoSeleccionado.data('ingresado', response.ingresado);
                productoSeleccionado.data('restante', response.restante);
                productoSeleccionado.find('.oi-pending-meta').html(`Pendiente: <b>${response.restante}</b> / Aprobado: ${response.aprobado}`);
            }

            if (botonCompraActivo) {
                actualizarBotonCompra(botonCompraActivo);
            }

            if (cantidadRestante <= 0) {
                const productoCompletado = $('#editarDescripcion').val() || 'Producto';
                if (productoSeleccionado) {
                    productoSeleccionado.fadeOut(200, function () {
                        $(this).remove();
                        const solicitudCompleta = $('#listaProductosEditar .producto-click:visible').length === 0;
                        if (solicitudCompleta) {
                            $('#listaProductosEditar').html('<em>Todos los productos de esta solicitud ya fueron ingresados.</em>');
                        }

                        swal({
                            type:'success',
                            title:'Producto ingresado a almacen',
                            html:
                              '<div style="padding:3px 0">' +
                                '<div style="width:62px;height:62px;margin:0 auto 13px;border-radius:20px;background:#e9faf2;color:#0b9b61;display:flex;align-items:center;justify-content:center;font-size:27px">' +
                                  '<i class="fa fa-check-circle"></i>' +
                                '</div>' +
                                '<p style="margin:0;color:#344b60;font-size:14px;font-weight:850">' + $('<div>').text(productoCompletado).html() + '</p>' +
                                '<p style="margin:8px 0 0;color:#718398;font-size:12px">Se registraron todas las unidades y el stock fue actualizado correctamente.</p>' +
                              '</div>',
                            showCancelButton:true,
                            confirmButtonColor:'#0b9b61',
                            confirmButtonText:'<i class="fa fa-print"></i> Imprimir códigos',
                            cancelButtonText:'Continuar'
                        }).then(function(result){
                            if (result.value) {
                                window.open(
                                  'extensiones/tcpdf/pdf/etiquetas-ingreso-compra.php?compra='+
                                  encodeURIComponent(idCompra)+'&producto='+encodeURIComponent(idProducto),
                                  '_blank'
                                );
                            }
                            if (solicitudCompleta && !result.value) {
                                $('#modalEditarProducto').modal('hide');
                                location.reload();
                            }
                        });
                    });
                }
            }
        },
        error: function () {
            swal({type:'error', title:'Error de conexion', text:'No se pudo registrar la unidad. Intente nuevamente.', confirmButtonText:'Cerrar'});
        }
    });
}
</script>
