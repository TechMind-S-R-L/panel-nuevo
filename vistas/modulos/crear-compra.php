<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$nombreUsuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : '';
$idUsuario = isset($_SESSION['id']) ? $_SESSION['id'] : '';

?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Crear Solicitud de Compra</h1>
    <ol class="breadcrumb">
      <li><a href="#"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Crear Compra</li>
    </ol>
  </section>

  <section class="content">
    <div class="row">
      <div class="col-lg-5 col-xs-12">
        <div class="box box-success">
          <div class="box-header with-border"></div>
          <form role="form" method="post" class="formularioCompra">
            <div class="box-body">
              <div class="box">
                <!-- ENTRADA DEL USUARIO -->
                <div class="form-group">
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-user"></i></span>
                    <input type="text" class="form-control" id="nuevoUsuario" value="<?php echo $nombreUsuario; ?>" readonly>
                    <input type="hidden" name="idUsuario" value="<?php echo $idUsuario; ?>">
                  </div>
                </div>

                <!-- ENTRADA DEL CÓDIGO -->
                <div class="form-group">
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-key"></i></span>
                    <?php
                    $codigo = ControladorCompras::ctrSiguienteCodigoCompra();

                    echo '<input type="text" class="form-control" id="nuevaCompra" name="nuevaCompra" value="'.$codigo.'" readonly>';
                    ?>
                  </div>
                </div>

                <!-- ENTRADA DEL CLIENTE -->
                <div class="form-group">
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-users"></i></span>
                    <select class="form-control" id="seleccionarProveedor" name="seleccionarProveedor" required>
                      <option value="">Seleccionar proveedor</option>
                      <?php
                      $item = null;
                      $valor = null;
                      $categorias = ControladorProveedor::ctrMostrarProveedor($item, $valor);
                      foreach ($categorias as $key => $value) {
                        echo '<option value="'.$value["id"].'">'.$value["nombre"].'</option>';
                      }
                      ?>
                    </select>
                    <span class="input-group-addon">
                      <button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#modalAgregarProveedor" data-dismiss="modal">Agregar proveedor</button>
                    </span>
                  </div>
                </div>

                <!-- ENTRADA PARA AGREGAR PRODUCTO -->
                <div class="form-group row nuevoProducto"></div>
                <input type="hidden" id="listaProductos" name="listaProductos">

                <!-- BOTÓN PARA AGREGAR PRODUCTO -->
                <button type="button" class="btn btn-default hidden-lg btnAgregarProductoCompra">Agregar producto</button>
                <hr>

                <div class="row">
                  <!-- ENTRADA DEL TOTAL -->
                  <div class="col-xs-8 pull-right">
                    <table class="table">
                      <thead>
                        <tr><th>Total</th></tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td style="width: 50%">
                            <div class="input-group">
                              <span class="input-group-addon"><i><b>Bs</b></i></span>
                              <input type="text" class="form-control input-lg" id="nuevoTotalCompra" name="nuevoTotalCompra" total="" placeholder="00000" readonly required>
                              <input type="hidden" name="totalCompra" id="totalCompra">
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <div class="box-footer">
              <button type="submit" class="btn btn-primary pull-right">Guardar compra</button>
            </div>
          </form>
          <?php
          $guardarCompra = new ControladorCompras();
          $guardarCompra -> ctrCrearCompra();
          ?>
        </div>
      </div>

      <!-- TABLA DE PRODUCTOS -->
      <div class="col-lg-7 hidden-md hidden-sm hidden-xs">
        <div class="box">
          <div class="box box-warning">
            <div class="box-header with-border"></div>
            <div class="box-body">
              <table class="table table-bordered table-striped dt-responsive tablaCompras">
                <thead>
                  <tr>
                    <th style="width: 10px">#</th>
                    <th>Imagen</th>
                    <th>Código</th>
                    <th>Descripcion</th>
                    <th>Stock</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- MODAL AGREGAR PROVEEDOR -->
<div id="modalAgregarProveedor" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post" enctype="multipart/form-data">
        <div class="modal-header" style="background:#3c8dbc; color:white">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Agregar Proveedor</h4>
        </div>

        <div class="modal-body">
          <div class="box-body">
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-truck"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoProveedor" placeholder="Ingresar proveedor" required>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoContacto" placeholder="Ingresar contacto" id="nuevoContacto" required>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-map"></i></span>
                <input type="text" class="form-control input-lg" name="nuevaDireccion" placeholder="Ingresar dirección" required>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                <input type="number" class="form-control input-lg" name="nuevoTelefono" placeholder="Ingresar telefono" required>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          <button type="submit" class="btn btn-primary">Guardar Proveedor</button>
        </div>

        <?php
        $crearProveedor = new ControladorProveedor();
        $crearProveedor -> ctrCrearProveedor();
        ?>
      </form>
    </div>
  </div>
</div>
<div class="modal fade" id="modalErrorPrecio" tabindex="-1" aria-labelledby="modalErrorPrecioLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalErrorPrecioLabel">Error</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        Este producto no tiene precio definido y no puede ser agregado.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<script>

</script>
