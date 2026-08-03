<?php
if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "vendedor"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

$clientes = ControladorClientes::ctrMostrarClientes(null, null);
$preciosServicios = ControladorServicios::ctrMostrarPrecios();
$serviciosDisponibles = array(
  "Instalacion de camaras" => array("icono" => "fa-video-camera", "texto" => "Instalaciones nuevas con camaras, cableado, canalizacion y recargos."),
  "Mantenimiento de camaras" => array("icono" => "fa-shield", "texto" => "Revision, limpieza, ajustes y mantenimiento por camara."),
  "Reubicacion de camaras" => array("icono" => "fa-random", "texto" => "Mover camaras existentes, nuevo cableado y canalizacion si aplica."),
  "Diagnostico tecnico" => array("icono" => "fa-search", "texto" => "Visita o revision para emitir diagnostico tecnico al cliente."),
  "Soporte tecnico en taller" => array("icono" => "fa-laptop", "texto" => "Ingreso de CPU, laptops, impresoras, proyectores y equipos electronicos."),
  "Desarrollo de software" => array("icono" => "fa-code", "texto" => "Aplicaciones web, moviles, plataformas y sistemas con contrato, adelanto y seguimiento por proyecto.")
);
$tipoServicioDirigido = $_GET["tipoServicio"] ?? "";
if(!array_key_exists($tipoServicioDirigido, $serviciosDisponibles)){
  $tipoServicioDirigido = "";
}
?>

<style>
  .servicio-paso{
    border:1px solid #dbe5ec;
    border-radius:8px;
    margin-bottom:16px;
    background:#fff;
    box-shadow:0 1px 2px rgba(0,0,0,.05);
    overflow:hidden;
  }
  .servicio-paso h4{
    margin:0;
    font-size:17px;
    font-weight:700;
    color:#1f2d3d;
  }
  .servicio-paso .badge{
    background:#3c8dbc;
    margin-right:6px;
  }
  .servicio-paso-header{
    padding:14px 16px;
    border-bottom:1px solid #e8eef3;
    background:#fbfdff;
    display:flex;
    align-items:center;
    gap:10px;
    justify-content:space-between;
    flex-wrap:wrap;
  }
  .servicio-paso-body{
    padding:16px;
  }
  .servicio-step-number{
    width:32px;
    height:32px;
    border-radius:50%;
    background:#3c8dbc;
    color:#fff;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    margin-right:8px;
  }
  .servicio-workspace{
    display:grid;
    grid-template-columns:minmax(0, 1fr) 360px;
    gap:18px;
    align-items:start;
  }
  .servicio-form-hero{
    background:#163140;
    color:#fff;
    border-radius:6px;
    padding:18px 20px;
    margin-bottom:16px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    flex-wrap:wrap;
  }
  .servicio-form-hero h2{
    margin:0 0 6px;
    font-size:23px;
    font-weight:700;
  }
  .servicio-form-hero p{
    margin:0;
    color:#c8d7df;
  }
  .servicio-selected-chip{
    background:#fff;
    color:#163140;
    border-radius:6px;
    padding:12px 14px;
    min-width:240px;
    display:flex;
    align-items:center;
    gap:10px;
    box-shadow:0 1px 2px rgba(0,0,0,.08);
  }
  .servicio-selected-chip i{
    color:#3c8dbc;
    font-size:22px;
  }
  .servicio-selected-chip span{
    display:block;
    color:#6f7f8a;
    font-size:11px;
    text-transform:uppercase;
    font-weight:700;
  }
  .servicio-selected-chip strong{
    display:block;
    line-height:1.2;
  }
  .servicio-client-grid{
    display:grid;
    grid-template-columns:minmax(0, 1fr) auto;
    gap:10px;
    align-items:end;
  }
  .servicio-client-grid .form-control{
    min-width:0;
  }
  .servicio-service-note{
    background:#f2f8fc;
    color:#35556b;
    border-left:4px solid #3c8dbc;
    border-radius:4px;
    padding:12px;
    margin-top:12px;
  }
  .servicio-resumen{
    background:#fff;
    border:1px solid #dbe5ec;
    border-radius:8px;
    padding:16px;
    margin-bottom:14px;
    box-shadow:0 1px 2px rgba(0,0,0,.06);
    position:sticky;
    top:72px;
  }
  #btnRegistrarServicio{
    white-space:normal;
    line-height:1.25;
    min-height:46px;
    padding-left:10px;
    padding-right:10px;
  }
  .servicio-total{
    font-size:28px;
    font-weight:700;
    color:#00a65a;
  }
  .servicio-desglose{
    margin-top:12px;
    font-size:13px;
  }
  .servicio-desglose .linea{
    display:flex;
    justify-content:space-between;
    border-bottom:1px solid #e5e7eb;
    padding:4px 0;
  }
  .servicio-ayuda{
    min-height:38px;
    color:#666;
  }
  .software-plan-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
    gap:12px;
  }
  .software-cuotas-preview{
    border:1px dashed #9bc7e4;
    border-radius:8px;
    padding:12px;
    background:#f7fbff;
    color:#24485c;
  }
  .software-cuotas-preview .cuota-linea{
    display:flex;
    justify-content:space-between;
    gap:10px;
    border-bottom:1px solid #dbeaf4;
    padding:5px 0;
    font-size:12px;
  }
  .software-cuotas-preview .cuota-linea:last-child{
    border-bottom:0;
  }
  .servicio-card-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
    gap:14px;
  }
  .servicio-card{
    display:block;
    min-height:150px;
    border:1px solid #d2d6de;
    border-radius:6px;
    background:#fff;
    padding:18px;
    color:#333;
    transition:.15s ease;
  }
  .servicio-card:hover{
    border-color:#3c8dbc;
    box-shadow:0 3px 10px rgba(0,0,0,.08);
    color:#333;
  }
  .servicio-card i{
    color:#3c8dbc;
    font-size:28px;
    margin-bottom:14px;
  }
  .servicio-card h3{
    margin:0 0 8px;
    font-size:17px;
    font-weight:600;
  }
  .servicio-card p{
    color:#666;
    margin:0;
    line-height:1.35;
  }
  @media(max-width:1199px){
    .servicio-workspace{
      grid-template-columns:1fr;
    }
    .servicio-resumen{
      position:static;
    }
  }
  @media(max-width:767px){
    .servicio-client-grid{
      grid-template-columns:1fr;
    }
    .servicio-selected-chip{
      width:100%;
    }
  }
</style>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Venta de servicios</h1>
  </section>

  <section class="content">
    <?php if($tipoServicioDirigido == ""): ?>
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Seleccione el servicio a vender</h3>
        </div>
        <div class="box-body">
          <div class="servicio-card-grid">
            <?php foreach($serviciosDisponibles as $nombreServicio => $servicioCard): ?>
              <a class="servicio-card" href="index.php?ruta=servicios&tipoServicio=<?php echo urlencode($nombreServicio); ?>">
                <i class="fa <?php echo $servicioCard["icono"]; ?>"></i>
                <h3><?php echo $nombreServicio; ?></h3>
                <p><?php echo $servicioCard["texto"]; ?></p>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php else: ?>
    <form method="post" enctype="multipart/form-data">
      <div class="servicio-form-hero">
        <div>
          <h2>Registrar servicio para caja</h2>
          <p>Complete los datos en orden para generar la boleta y enviar el cobro a caja.</p>
        </div>
        <div class="servicio-selected-chip">
          <i class="fa <?php echo $serviciosDisponibles[$tipoServicioDirigido]["icono"]; ?>"></i>
          <div>
            <span>Servicio seleccionado</span>
            <strong><?php echo htmlspecialchars($tipoServicioDirigido, ENT_QUOTES, "UTF-8"); ?></strong>
          </div>
        </div>
      </div>

      <div class="servicio-workspace">
        <div>
              <input type="hidden" name="nuevoServicio" value="1">
              <input type="hidden" name="tipoServicio" value="<?php echo htmlspecialchars($tipoServicioDirigido, ENT_QUOTES, "UTF-8"); ?>">

              <div class="servicio-paso">
                <div class="servicio-paso-header">
                  <h4><span class="servicio-step-number">1</span> Cliente</h4>
                  <a class="btn btn-default btn-sm" href="servicios"><i class="fa fa-arrow-left"></i> Cambiar servicio</a>
                </div>
                <div class="servicio-paso-body">
                  <div class="servicio-client-grid">
                    <div class="form-group">
                      <label>Cliente</label>
                      <select class="form-control" name="idClienteServicio" required>
                        <option value="">Seleccionar cliente</option>
                        <?php foreach($clientes as $cliente): ?>
                          <option value="<?php echo $cliente["id"]; ?>"><?php echo htmlspecialchars($cliente["nombre"], ENT_QUOTES, "UTF-8"); ?> - <?php echo $cliente["telefono"]; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>&nbsp;</label>
                      <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#modalAgregarClienteServicio">
                        <i class="fa fa-user-plus"></i> Nuevo cliente
                      </button>
                    </div>
                  </div>
                  <div class="servicio-service-note" id="ayudaVentaServicio"></div>
                </div>
              </div>

              <div class="servicio-paso campo-software" style="display:none">
                <div class="servicio-paso-header">
                  <h4><span class="servicio-step-number">2</span> Proyecto de software</h4>
                </div>
                <div class="servicio-paso-body">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Nombre del proyecto</label>
                        <input type="text" class="form-control campoSoftwareRequerido" name="nombreProyectoSoftware" placeholder="Ej: Sistema de ventas para ferreteria">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label>Tipo de software</label>
                        <select class="form-control campoSoftwareRequerido" name="tipoSoftwareProyecto">
                          <option value="">Seleccionar</option>
                          <option value="Aplicacion web">Aplicacion web</option>
                          <option value="Aplicacion movil">Aplicacion movil</option>
                          <option value="Aplicacion movil con panel administrativo web">Aplicacion movil con panel administrativo web</option>
                          <option value="Plataforma web">Plataforma web</option>
                          <option value="Sistema administrativo">Sistema administrativo</option>
                          <option value="Ecommerce">Ecommerce</option>
                          <option value="Integracion / API">Integracion / API</option>
                          <option value="Otro desarrollo">Otro desarrollo</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label>Fecha estimada entrega</label>
                        <input type="date" class="form-control campoSoftwareRequerido" name="fechaEntregaSoftware">
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label>Plazo / condiciones de entrega</label>
                        <input type="text" class="form-control campoSoftwareRequerido" name="plazoEntregaSoftware" placeholder="Ej: 45 dias habiles desde adelanto">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label>Precio total acordado</label>
                        <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control calcServicio campoSoftwareRequerido" name="precioTotalSoftware" min="0" step="0.01" value="0"></div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label>Adelanto a cobrar</label>
                        <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control calcServicio campoSoftwareRequerido" name="montoAdelantoSoftware" min="0" step="0.01" value="0"></div>
                        <input type="hidden" name="porcentajeAdelantoSoftware" value="0">
                        <p class="help-block" id="porcentajeAdelantoSoftwareVista">Equivale al 0.00% del total.</p>
                      </div>
                    </div>
                  </div>
                  <div class="software-plan-grid">
                    <div class="form-group">
                      <label>Cuotas del saldo</label>
                      <select class="form-control calcServicio campoSoftwareRequerido" name="numeroCuotasSoftware">
                        <option value="1">1 cuota</option>
                        <option value="2">2 cuotas</option>
                        <option value="3" selected>3 cuotas</option>
                        <option value="4">4 cuotas</option>
                        <option value="5">5 cuotas</option>
                        <option value="6">6 cuotas</option>
                      </select>
                      <p class="help-block">No registra ingreso en caja hasta que cada cuota sea cobrada.</p>
                    </div>
                    <div class="form-group">
                      <label>Primera cuota vence</label>
                      <input type="date" class="form-control calcServicio campoSoftwareRequerido" name="fechaPrimeraCuotaSoftware">
                      <p class="help-block">Las siguientes se programan mensualmente.</p>
                    </div>
                    <div class="form-group">
                      <label>Propuesta tecnica PDF</label>
                      <input type="file" class="form-control" name="propuestaTecnicaSoftware" accept="application/pdf">
                      <p class="help-block">Al crear el proyecto quedara visible en documentos.</p>
                    </div>
                    <div class="form-group">
                      <label>Propuesta comercial PDF</label>
                      <input type="file" class="form-control" name="propuestaComercialSoftware" accept="application/pdf">
                      <p class="help-block">Cotizacion, alcance economico o documento firmado.</p>
                    </div>
                  </div>
                  <div class="software-cuotas-preview" id="softwareCuotasPreview">
                    Configure precio, adelanto, cuotas y fecha para ver el cronograma.
                  </div>
                  <div class="form-group">
                    <label>Alcance inicial del software</label>
                    <textarea class="form-control campoSoftwareRequerido" name="alcanceSoftware" rows="4" placeholder="Modulos, procesos, usuarios, reportes, pantallas principales y objetivo del sistema."></textarea>
                  </div>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Entregables incluidos</label>
                        <textarea class="form-control" name="entregablesSoftware" rows="3" placeholder="Codigo fuente, instalacion, capacitacion, manual, credenciales, despliegue."></textarea>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Exclusiones / no incluido</label>
                        <textarea class="form-control" name="exclusionesSoftware" rows="3" placeholder="Hosting, dominio, licencias, cambios fuera del alcance, soporte fuera de garantia."></textarea>
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Observaciones comerciales</label>
                    <textarea class="form-control" name="observacionesSoftware" rows="2" placeholder="Acuerdos particulares, horarios de reuniones, responsable del cliente, datos pendientes."></textarea>
                  </div>
                </div>
              </div>

              <div class="servicio-paso campo-taller" style="display:none">
                <div class="servicio-paso-header">
                  <h4><span class="servicio-step-number">2</span> Ingreso de equipo a taller</h4>
                </div>
                <div class="servicio-paso-body">
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Tipo de equipo</label>
                      <select class="form-control campoTallerRequerido" name="tipoEquipoTaller">
                        <option value="">Seleccionar</option>
                        <option value="CPU">CPU</option>
                        <option value="Laptop">Laptop</option>
                        <option value="Impresora">Impresora</option>
                        <option value="Proyector">Proyector</option>
                        <option value="Switch / Router">Switch / Router</option>
                        <option value="Camara / DVR / NVR">Camara / DVR / NVR</option>
                        <option value="Articulo electronico">Articulo electronico</option>
                        <option value="Otro">Otro</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Marca</label>
                      <input type="text" class="form-control" name="marcaEquipoTaller" placeholder="HP, Epson, Lenovo, TP-Link">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Modelo</label>
                      <input type="text" class="form-control" name="modeloEquipoTaller" placeholder="Modelo exacto">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Serie / codigo visible</label>
                      <input type="text" class="form-control" name="serieEquipoTaller" placeholder="Numero de serie o etiqueta">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Foto del equipo</label>
                      <input type="file" class="form-control" name="fotoEquipoTaller" accept="image/png,image/jpeg">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Accesorios recibidos</label>
                      <input type="text" class="form-control" name="accesoriosEquipoTaller" placeholder="Cargador, cable, cartucho, tapa, bolsa">
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label>Falla reportada por el cliente</label>
                  <textarea class="form-control campoTallerRequerido" name="fallaReportadaTaller" rows="3" placeholder="Ej: CPU no enciende, impresora no jala papel, proyector no da imagen"></textarea>
                </div>
                <div class="form-group">
                  <label>Estado fisico al recibir</label>
                  <textarea class="form-control" name="estadoFisicoTaller" rows="2" placeholder="Rayas, golpes, tornillos faltantes, humedad, pantalla rota, cables cortados"></textarea>
                </div>
                <div class="form-group">
                  <label>Observaciones internas</label>
                  <textarea class="form-control" name="observacionesTaller" rows="2" placeholder="Condiciones de recepcion, autorizaciones iniciales, cuidado especial"></textarea>
                </div>
                </div>
              </div>

              <div class="servicio-paso campo-costeo-servicio">
                <div class="servicio-paso-header">
                  <h4><span class="servicio-step-number">2</span> Medidas y recargos</h4>
                </div>
                <div class="servicio-paso-body">
                <div class="row">
                  <div class="col-md-4 campo-instalacion">
                    <div class="form-group">
                      <label>Tipo de instalacion</label>
                      <select class="form-control" name="tipoInstalacion">
                        <option value="Interior">Interior</option>
                        <option value="Exterior">Exterior</option>
                        <option value="Mixta">Mixta</option>
                        <option value="Altura">Altura</option>
                        <option value="Canalizada">Canalizada</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-2 campo-camaras">
                    <div class="form-group">
                      <label>Camaras</label>
                      <input type="number" class="form-control calcServicio" name="cantidadCamaras" min="0" value="0" required>
                    </div>
                  </div>
                  <div class="col-md-3 campo-metros">
                    <div class="form-group">
                      <label>Metros cable</label>
                      <input type="number" class="form-control calcServicio" name="metrosDistancia" min="0" step="0.01" value="0">
                    </div>
                  </div>
                  <div class="col-md-3 campo-canalizacion">
                    <div class="form-group">
                      <label>Metros canalizacion</label>
                      <input type="number" class="form-control calcServicio" name="metrosCanalizacion" min="0" step="0.01" value="0">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-3 campo-altura">
                    <div class="checkbox">
                      <label><input type="checkbox" class="calcServicio" name="requiereAltura"> Aplica recargo por altura</label>
                    </div>
                  </div>
                  <div class="col-md-3 campo-urgente">
                    <div class="checkbox">
                      <label><input type="checkbox" class="calcServicio" name="servicioUrgente"> Servicio urgente</label>
                    </div>
                  </div>
                </div>
                </div>
              </div>

              <div class="servicio-paso campo-ubicacion">
                <div class="servicio-paso-header">
                  <h4><span class="servicio-step-number">3</span> Ubicacion del cliente</h4>
                </div>
                <div class="servicio-paso-body">
                <div class="row">
                  <div class="col-md-7">
                    <div class="form-group">
                      <label>Direccion exacta</label>
                      <input type="text" class="form-control" name="direccionInstalacion" required>
                    </div>
                  </div>
                  <div class="col-md-5">
                    <div class="form-group">
                      <label>Referencia de la casa/local</label>
                      <input type="text" class="form-control" name="referenciaInstalacion" placeholder="Color de puerta, zona, piso, letrero, etc.">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Latitud</label>
                      <input type="text" class="form-control" id="latitudInstalacion" name="latitudInstalacion">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Longitud</label>
                      <input type="text" class="form-control" id="longitudInstalacion" name="longitudInstalacion">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-default btn-block" id="btnAbrirMapaServicio"><i class="fa fa-map-marker"></i> Seleccionar ubicacion en mapa</button>
                  </div>
                </div>
                </div>
              </div>

              <div class="servicio-paso campo-detalle-general">
                <div class="servicio-paso-header">
                  <h4><span class="servicio-step-number">4</span> Detalle para evitar reclamos</h4>
                </div>
                <div class="servicio-paso-body">
                <div class="form-group">
                  <label>Preguntas al cliente antes del servicio</label>
                  <textarea class="form-control" id="preguntasClienteServicio" name="preguntasClienteServicio" rows="5"></textarea>
                </div>
                <div class="form-group">
                  <label>Diagnostico inicial / alcance acordado</label>
                  <textarea class="form-control" name="diagnosticoInicialServicio" rows="3" placeholder="Anotar lo que se cobrara y entregara al cliente. En diagnostico tecnico esto se imprime como informe para el cliente."></textarea>
                </div>
                <div class="form-group">
                  <label>Observaciones tecnicas</label>
                  <textarea class="form-control" name="observacionesServicio" rows="3" placeholder="DVR/NVR, tipo de cable, energia, alturas, horarios, materiales incluidos, condiciones del cliente"></textarea>
                </div>
                </div>
              </div>
            </div>

        <div>
          <div class="servicio-resumen">
            <h4>Resumen de cobro</h4>
            <p class="text-muted" id="ayudaResumenServicio">El tecnico se asigna automaticamente despues del pago en caja.</p>
            <label>Total estimado</label>
            <div class="input-group">
              <span class="input-group-addon">Bs</span>
              <input type="text" class="form-control input-lg" id="totalServicioVista" readonly value="0.00">
            </div>
            <div class="servicio-total" id="totalServicioGrande">Bs 0.00</div>
            <p class="help-block" id="tarifaServicioActual"></p>
            <div class="servicio-desglose" id="desgloseServicio"></div>
            <button type="submit" class="btn btn-primary btn-block btn-lg" id="btnRegistrarServicio" title="Registrar y mandar a caja">Registrar y mandar a caja</button>
          </div>
        </div>
      </div>
      <?php ControladorServicios::ctrCrearServicio(); ?>
    </form>
    <?php endif; ?>

  </section>
</div>

<div id="modalAgregarClienteServicio" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post">
        <div class="modal-header" style="background:#3c8dbc;color:white">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Agregar cliente</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" name="origenCliente" value="servicios">
          <input type="hidden" name="tipoServicioCliente" value="<?php echo htmlspecialchars($tipoServicioDirigido, ENT_QUOTES, "UTF-8"); ?>">
          <div class="form-group">
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-user"></i></span>
              <input type="text" class="form-control input-lg" name="nuevoCliente" placeholder="Nombre completo" required>
            </div>
          </div>
          <div class="form-group">
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-key"></i></span>
              <input type="number" min="0" class="form-control input-lg" name="nuevoDocumentoId" placeholder="Cedula / NIT" required>
            </div>
          </div>
          <div class="form-group">
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-phone"></i></span>
              <input type="text" class="form-control input-lg" name="nuevoTelefono" placeholder="Telefono">
            </div>
          </div>
          <div class="form-group">
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
              <input type="email" class="form-control input-lg" name="nuevoEmail" placeholder="Email">
            </div>
          </div>
          <div class="form-group">
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
              <input type="text" class="form-control input-lg" name="nuevaDireccion" placeholder="Direccion">
            </div>
          </div>
          <input type="hidden" name="nuevaFechaNacimiento" value="">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar cliente</button>
        </div>
      </form>
      <?php ControladorClientes::ctrCrearCliente(); ?>
    </div>
  </div>
</div>

<div id="modalMapaServicio" class="modal fade" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:#3c8dbc;color:white">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Marcar ubicacion del cliente</h4>
      </div>
      <div class="modal-body">
        <p class="help-block">Busque la zona y haga clic sobre la casa/local del cliente. El punto guardara latitud y longitud.</p>
        <div id="mapaServicio" style="height:420px;width:100%;border:1px solid #ddd"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" data-dismiss="modal">Usar esta ubicacion</button>
      </div>
    </div>
  </div>
</div>

<script>
var preguntasPorServicio = {
  "Instalacion de camaras": [
    "Cuantas camaras desea instalar?",
    "La instalacion sera interior, exterior o mixta?",
    "Cuantos metros aproximados de cable se necesitan?",
    "Se necesita canalizacion, tuberia o canaleta?",
    "Hay puntos altos o trabajo en escalera?",
    "Donde estara el DVR/NVR y el monitor?",
    "El cliente ya compro equipos o los comprara en tienda?"
  ],
  "Mantenimiento de camaras": [
    "Cuantas camaras fallan o se revisaran?",
    "El problema es sin imagen, imagen borrosa, no graba o no se ve remoto?",
    "Desde cuando presenta la falla?",
    "Hay internet en el lugar?",
    "El DVR/NVR enciende normalmente?",
    "El cliente autoriza solo diagnostico/mantenimiento o tambien cotizacion de repuestos si aparecen fallas?"
  ],
  "Reubicacion de camaras": [
    "Cuantas camaras se reubicaran?",
    "De donde a donde se movera cada camara?",
    "Se reutilizara cable existente o se necesita cable nuevo?",
    "Se necesita canalizacion nueva?",
    "Hay trabajo en altura?",
    "El cliente autoriza perforaciones o canaletas visibles?"
  ],
  "Diagnostico tecnico": [
    "Que falla reporta el cliente?",
    "Cuantos equipos/camaras se revisaran?",
    "El sistema enciende?",
    "Hay acceso al DVR/NVR, router y energia?",
    "Se debe entregar informe de hallazgos y recomendaciones?",
    "El cliente entiende que trabajos adicionales se cotizan aparte?"
  ],
  "Soporte tecnico en taller": [
    "El equipo enciende o no da ninguna senal?",
    "La falla ocurre siempre o por momentos?",
    "El cliente deja cargador, cables, tintas, cartuchos u otros accesorios?",
    "Tiene informacion importante que deba respaldarse?",
    "El cliente autoriza diagnostico antes de reparar?",
    "Se explico que la reparacion requiere autorizacion despues del diagnostico?"
  ],
  "Desarrollo de software": [
    "Que problema o proceso quiere resolver el cliente?",
    "Que tipo de software necesita: web, movil, plataforma o integracion?",
    "Que modulos son obligatorios para la primera entrega?",
    "Quien aprobara avances y contenido del proyecto?",
    "El cliente entregara logo, textos, usuarios, dominio, hosting o accesos?",
    "El cliente entiende que cambios fuera del alcance se cotizan aparte?",
    "Se explico adelanto, saldo final, plazo y garantia?"
  ]
};

function cargarPreguntasServicio(){
  var tipo = $('[name="tipoServicio"]').val();
  var preguntas = preguntasPorServicio[tipo] || [];
  $("#preguntasClienteServicio").val(preguntas.map(function(p){ return "- " + p; }).join("\n"));
}

function aplicarCamposVentaServicio(){
  var tipo = $('[name="tipoServicio"]').val();
  var esTaller = tipo === "Soporte tecnico en taller";
  var esSoftware = tipo === "Desarrollo de software";

  $(".campo-instalacion, .campo-camaras, .campo-metros, .campo-canalizacion, .campo-altura, .campo-urgente, .campo-taller, .campo-software").hide();
  $(".campoTallerRequerido").prop("required", false);
  $(".campoSoftwareRequerido").prop("required", false);
  $('[name="direccionInstalacion"]').prop("required", !esTaller && !esSoftware);
  $(".campo-ubicacion, .campo-costeo-servicio").toggle(!esTaller && !esSoftware);
  $(".campo-detalle-general").toggle(!esSoftware);
  $("#ayudaResumenServicio").text(esTaller ? "El equipo se registra en taller y se asigna automaticamente al tecnico. El cobro se realiza cuando el cliente recoja el equipo." : (esSoftware ? "Se genera contrato. Caja cobra el adelanto y recien ahi se asigna el desarrollador." : "El tecnico se asigna automaticamente despues del pago en caja."));

  if(esTaller){
    $(".campo-taller").show();
    $(".campoTallerRequerido").prop("required", true);
    $('[name="tipoInstalacion"]').val("Interior");
    $('[name="cantidadCamaras"], [name="metrosDistancia"], [name="metrosCanalizacion"]').val("0");
    $('[name="requiereAltura"], [name="servicioUrgente"]').prop("checked", false);
    $("#ayudaVentaServicio").text("Soporte en taller: reciba el equipo, registre foto, datos clave, accesorios y falla reportada. Se generara una boleta de ingreso con codigo unico.");
    $("#btnRegistrarServicio").text("Registrar ingreso de equipo").attr("title", "Registrar ingreso de equipo");
  }else if(esSoftware){
    $(".campo-software").show();
    $(".campoSoftwareRequerido").prop("required", true);
    $('[name="tipoInstalacion"]').val("Interior");
    $('[name="cantidadCamaras"], [name="metrosDistancia"], [name="metrosCanalizacion"]').val("0");
    $('[name="requiereAltura"], [name="servicioUrgente"]').prop("checked", false);
    $("#ayudaVentaServicio").text("Software: registre alcance, entregables, plazo, precio total y monto de adelanto. El sistema calculara el porcentaje para contrato y caja.");
    $("#btnRegistrarServicio").text("Registrar contrato").attr("title", "Registrar contrato y mandar adelanto a caja");
  }else if(tipo === "Instalacion de camaras"){
    $(".campo-instalacion, .campo-camaras, .campo-metros, .campo-canalizacion, .campo-altura, .campo-urgente").show();
    $("#btnRegistrarServicio").text("Registrar y mandar a caja").attr("title", "Registrar y mandar a caja");
    $("#ayudaVentaServicio").text("Instalacion: cobre camaras, metros de cable, canalizacion si corresponde y recargo de altura si aplica.");
  }else if(tipo === "Mantenimiento de camaras"){
    $(".campo-camaras, .campo-urgente").show();
    $('[name="tipoInstalacion"]').val("Interior");
    $('[name="metrosDistancia"], [name="metrosCanalizacion"]').val("0");
    $('[name="requiereAltura"]').prop("checked", false);
    $("#ayudaVentaServicio").text("Mantenimiento: cobre mano de obra y cantidad de camaras revisadas. Las fallas extra se anotan en el informe tecnico.");
    $("#btnRegistrarServicio").text("Registrar y mandar a caja").attr("title", "Registrar y mandar a caja");
  }else if(tipo === "Reubicacion de camaras"){
    $(".campo-instalacion, .campo-camaras, .campo-metros, .campo-canalizacion, .campo-altura, .campo-urgente").show();
    $("#ayudaVentaServicio").text("Reubicacion: cobre mano de obra, camaras movidas, cable/canalizacion y altura si aplica.");
    $("#btnRegistrarServicio").text("Registrar y mandar a caja").attr("title", "Registrar y mandar a caja");
  }else if(tipo === "Diagnostico tecnico"){
    $(".campo-urgente").show();
    $('[name="tipoInstalacion"]').val("Interior");
    $('[name="cantidadCamaras"]').val("0");
    $('[name="metrosDistancia"], [name="metrosCanalizacion"]').val("0");
    $('[name="requiereAltura"]').prop("checked", false);
    $("#ayudaVentaServicio").text("Diagnostico: cobre visita y diagnostico. El resultado se imprime como informe para el cliente.");
    $("#btnRegistrarServicio").text("Registrar y mandar a caja").attr("title", "Registrar y mandar a caja");
  }
}

function calcularServicio(){
  var tarifas = <?php echo json_encode($preciosServicios); ?>;
  var tipoServicio = $('[name="tipoServicio"]').val();
  var tipoInstalacion = $('[name="tipoInstalacion"]').val();

  if(tipoServicio === "Soporte tecnico en taller"){
    $("#totalServicioVista").val("0.00");
    $("#totalServicioGrande").text("Bs 0.00");
    $("#tarifaServicioActual").text("Ingreso de equipo sin cobro inicial. El pago se genera al recoger el equipo.");
    $("#desgloseServicio").html('<span class="text-muted">Diagnostico, repuestos y mano de obra se definen despues de la revision tecnica.</span>');
    return;
  }
  if(tipoServicio === "Desarrollo de software"){
    var totalSoftware = Number($('[name="precioTotalSoftware"]').val()) || 0;
    var montoAdelanto = Number($('[name="montoAdelantoSoftware"]').val()) || 0;
    var numeroCuotas = Number($('[name="numeroCuotasSoftware"]').val()) || 1;
    var fechaPrimeraCuota = $('[name="fechaPrimeraCuotaSoftware"]').val() || "";
    if(totalSoftware > 0 && montoAdelanto > totalSoftware){
      montoAdelanto = totalSoftware;
      $('[name="montoAdelantoSoftware"]').val(montoAdelanto.toFixed(2));
    }
    var adelanto = totalSoftware > 0 ? (montoAdelanto / totalSoftware) * 100 : 0;
    $('[name="porcentajeAdelantoSoftware"]').val(adelanto.toFixed(2));
    $("#porcentajeAdelantoSoftwareVista").text("Equivale al " + adelanto.toFixed(2) + "% del total.");
    $("#totalServicioVista").val(totalSoftware.toFixed(2));
    $("#totalServicioGrande").text("Bs " + totalSoftware.toFixed(2));
    $("#tarifaServicioActual").text("Contrato de software: adelanto Bs " + montoAdelanto.toFixed(2) + " (" + adelanto.toFixed(2) + "%)");
    $("#desgloseServicio").html(
      '<div class="linea"><span>Adelanto a cobrar en caja</span><strong>Bs ' + montoAdelanto.toFixed(2) + '</strong></div>' +
      '<div class="linea"><span>Saldo a la entrega</span><strong>Bs ' + Math.max(0, totalSoftware - montoAdelanto).toFixed(2) + '</strong></div>'
    );
    var saldoSoftware = Math.max(0, totalSoftware - montoAdelanto);
    if(saldoSoftware <= 0 || !fechaPrimeraCuota){
      $("#softwareCuotasPreview").html("Configure saldo pendiente y fecha de primera cuota para ver el cronograma.");
    }else{
      var montoBase = Math.floor((saldoSoftware / numeroCuotas) * 100) / 100;
      var acumulado = 0;
      var htmlCuotas = '<strong>Plan de cobro del saldo pendiente</strong>';
      for(var i = 1; i <= numeroCuotas; i++){
        var montoCuota = (i === numeroCuotas) ? (saldoSoftware - acumulado) : montoBase;
        acumulado += montoCuota;
        var fechaCuota = new Date(fechaPrimeraCuota + "T00:00:00");
        fechaCuota.setMonth(fechaCuota.getMonth() + (i - 1));
        var fechaTexto = fechaCuota.toLocaleDateString("es-BO");
        htmlCuotas += '<div class="cuota-linea"><span>Cuota ' + i + ' / ' + numeroCuotas + ' - ' + fechaTexto + '</span><strong>Bs ' + montoCuota.toFixed(2) + '</strong></div>';
      }
      $("#softwareCuotasPreview").html(htmlCuotas);
    }
    return;
  }
  var tarifa = tarifas.find(function(item){
    return item.tipo_servicio === tipoServicio && item.tipo_instalacion === tipoInstalacion && Number(item.estado) === 1;
  });

  if(!tarifa){
    $("#totalServicioVista").val("Sin tarifa");
    $("#totalServicioGrande").text("Sin tarifa");
    $("#tarifaServicioActual").text("Configure esta tarifa en Gestion de Precios > Precios de Servicios.");
    $("#desgloseServicio").html("");
    return;
  }

  var camaras = Number($('[name="cantidadCamaras"]').val()) || 0;
  var metros = Number($('[name="metrosDistancia"]').val()) || 0;
  var metrosCanalizacion = Number($('[name="metrosCanalizacion"]').val()) || 0;
  var desglose = [];
  var total = 0;

  function agregarConcepto(nombre, monto){
    monto = Number(monto) || 0;
    if(monto > 0){
      desglose.push({nombre: nombre, monto: monto});
      total += monto;
    }
  }

  agregarConcepto(camaras + " camara(s) x Bs " + Number(tarifa.precio_por_camara || 0).toFixed(2), camaras * Number(tarifa.precio_por_camara || 0));
  agregarConcepto(metros + " m cable x Bs " + Number(tarifa.precio_por_metro || 0).toFixed(2), metros * Number(tarifa.precio_por_metro || 0));
  agregarConcepto(metrosCanalizacion + " m canalizacion x Bs " + Number(tarifa.precio_canalizacion_metro || 0).toFixed(2), metrosCanalizacion * Number(tarifa.precio_canalizacion_metro || 0));
  agregarConcepto("Mano de obra", tarifa.mano_obra_base);
  agregarConcepto("Visita tecnica", tarifa.costo_visita);
  agregarConcepto("Diagnostico", tarifa.costo_diagnostico);
  agregarConcepto("Transporte", tarifa.costo_transporte);
  if($('[name="requiereAltura"]').is(":checked")) agregarConcepto("Recargo altura", tarifa.recargo_altura);
  if($('[name="servicioUrgente"]').is(":checked")) agregarConcepto("Recargo urgencia", tarifa.recargo_urgencia);

  $("#totalServicioVista").val(total.toFixed(2));
  $("#totalServicioGrande").text("Bs " + total.toFixed(2));
  $("#tarifaServicioActual").text("Tarifa: " + tipoServicio + " / " + tipoInstalacion);
  $("#desgloseServicio").html(desglose.length ? desglose.map(function(item){
    return '<div class="linea"><span>' + item.nombre + '</span><strong>Bs ' + item.monto.toFixed(2) + '</strong></div>';
  }).join("") : '<span class="text-muted">Sin conceptos con precio.</span>');
}
$(".calcServicio").on("input change", calcularServicio);
$('[name="tipoServicio"]').on("change", function(){
  aplicarCamposVentaServicio();
  cargarPreguntasServicio();
  calcularServicio();
});
$('[name="tipoInstalacion"]').on("change", function(){
  aplicarCamposVentaServicio();
  calcularServicio();
});
aplicarCamposVentaServicio();
cargarPreguntasServicio();
calcularServicio();

var mapaServicio = null;
var marcadorServicio = null;

$("#btnAbrirMapaServicio").on("click", function(){
  $("#modalMapaServicio").modal("show");
});

$("#modalMapaServicio").on("shown.bs.modal", function(){
  var lat = Number($("#latitudInstalacion").val()) || -17.7833;
  var lng = Number($("#longitudInstalacion").val()) || -63.1821;

  if(!window.L){
    swal({type:"error", title:"No se pudo cargar el mapa", text:"Revise conexion a internet.", confirmButtonText:"Cerrar"});
    return;
  }

  if(!mapaServicio){
    mapaServicio = L.map("mapaServicio").setView([lat, lng], 13);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: "&copy; OpenStreetMap"
    }).addTo(mapaServicio);

    mapaServicio.on("click", function(e){
      $("#latitudInstalacion").val(e.latlng.lat.toFixed(7));
      $("#longitudInstalacion").val(e.latlng.lng.toFixed(7));
      if(marcadorServicio){
        marcadorServicio.setLatLng(e.latlng);
      }else{
        marcadorServicio = L.marker(e.latlng).addTo(mapaServicio);
      }
    });
  }

  setTimeout(function(){
    mapaServicio.invalidateSize();
    mapaServicio.setView([lat, lng], 13);
    if($("#latitudInstalacion").val() && $("#longitudInstalacion").val()){
      var punto = [lat, lng];
      if(marcadorServicio){
        marcadorServicio.setLatLng(punto);
      }else{
        marcadorServicio = L.marker(punto).addTo(mapaServicio);
      }
    }
  }, 250);
});

$(".tablas").on("click", ".btnImprimirBoletaServicio", function(){
  window.open("extensiones/tcpdf/pdf/boleta-servicio.php?idServicio=" + $(this).attr("idServicio"), "_blank");
});
$(".tablas").on("click", ".btnImprimirOrdenServicio", function(){
  window.open("extensiones/tcpdf/pdf/orden-servicio.php?idServicio=" + $(this).attr("idServicio"), "_blank");
});
</script>
