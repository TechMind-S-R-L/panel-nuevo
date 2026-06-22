<?php

echo '<script>window.location = "solicitudes-de-compra";</script>';
return;

?>

<div class="content-wrapper">

  <section class="content-header">
    <h1>Administrar compras</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Administrar compras</li>
    </ol>
  </section>

  <section class="content">

    <div class="box">

      <div class="box-header with-border">
        <a href="crear-compra">
          <button class="btn btn-primary">Agregar Compra</button>
        </a>
        <?php
        if (isset($_GET["fechaInicial"])) {
            echo '<a href="vistas/modulos/descargar-reportecompra.php?reporte=reporte&fechaInicial=' . $_GET["fechaInicial"] . '&fechaFinal=' . $_GET["fechaFinal"] . '">';
        } else {
            echo '<a href="vistas/modulos/descargar-reportecompra.php?reporte=reporte">';
        }
        ?>
        <button class="btn btn-success" style="margin-top:5px">Descargar reporte en Excel</button>
        </a>

        <button type="button" class="btn btn-default pull-right" id="daterangecompra-btn">
            <span><i class="fa fa-calendar"></i> Rango de fecha</span>
            <i class="fa fa-caret-down"></i>
        </button>
      </div>

      <div class="box-body">
        <table class="table table-bordered table-striped dt-responsive tablas" width="100%">
          <tbody>
            <?php
            $pendientes = ControladorProductos::ctrMostrarPendientesPrecio();

            foreach ($pendientes as $pendiente) {
                $idProducto = $pendiente['id_producto'] ?? $pendiente['id'] ?? '';
                $nombre = $pendiente['nombre'] ?? $pendiente['descripcion'] ?? '';
                $unidades = $pendiente['unidades'] ?? $pendiente['stock'] ?? 0;
                echo "
                <tr>
                    <td>{$idProducto}</td>
                    <td>{$nombre}</td>
                    <td>{$unidades}</td>
                    <td>
                        <button class='btn btn-warning btnAsignarPrecio' data-id-producto='{$idProducto}'>Asignar Precio</button>
                    </td>
                </tr>";
            }
            
            ?>
            <div id="modalAsignarPrecio" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAsignarPrecio">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Asignar Precios</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idProductoPendiente" name="idProductoPendiente">
                    <div class="form-group">
                        <label>Precio de Compra</label>
                        <input type="number" id="precioCompra" name="precioCompra" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Precio de Venta</label>
                        <input type="number" id="precioVenta" name="precioVenta" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>


          </tbody>
        </table>
      </div>

    </div>

  </section>

</div>

<!-- Modal de respuesta -->
<?php if (isset($mensajeModal)): ?>
<div id="modalEstado" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modalEstadoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #007bff; color: white;">
                <h5 class="modal-title" id="modalEstadoLabel">
                    <i class="fa fa-info-circle"></i> Actualización de Estado
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color: white;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <?php if ($mensajeModal === "Estado actualizado correctamente."): ?>
                    <i class="fa fa-check-circle fa-3x text-success mb-3"></i>
                <?php else: ?>
                    <i class="fa fa-times-circle fa-3x text-danger mb-3"></i>
                <?php endif; ?>
                <p class="lead">
                    <?php echo $mensajeModal; ?>
                </p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    <i class="fa fa-check"></i> Entendido
                </button>
            </div>
        </div>
    </div>
</div>
<script>
$(".tablas").on("click", ".btnImprimirNotaCompra", function(){
var codigoCompra = $(this).attr("codigoCompra");
window.open("extensiones/tcpdf/pdf/notacompra.php?codigoCompra="+codigoCompra, "_blank");
})

$(".tablas").on("click", ".btnEliminarCompra", function(){
var idCompra = $(this).attr("idCompra");
swal({
      title: '¿Está seguro de borrar la compra?',
      text: "¡Si no lo está puede cancelar la accíón!",
      type: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      cancelButtonText: 'Cancelar',
      confirmButtonText: 'Si, borrar Compra!'
    }).then(function(result){
      if (result.value) {
                window.location = "index.php?ruta=solicitudes-de-compra";
      }
})
})

var numProductos = 0;

$(".btnAgregarProductos").click(function(){

	numProducto ++;

	var datos = new FormData();
	datos.append("traerProductos", "ok");

	$.ajax({

		url:"ajax/productos.ajax.php",
      	method: "POST",
      	data: datos,
      	cache: false,
      	contentType: false,
      	processData: false,
      	dataType:"json",
      	success:function(respuesta){
      	    
      	    	$(".nuevoProducto").append(

          	'<div class="row" style="padding:5px 15px">'+

			  '<!-- Descripción del producto -->'+
	          
	          '<div class="col-xs-6" style="padding-right:0px">'+
	          
	            '<div class="input-group">'+
	              
	              '<span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarProducto" idProducto><i class="fa fa-times"></i></button></span>'+

	              '<select class="form-control nuevaDescripcionProducto" id="producto'+numProducto+'" idProducto name="nuevaDescripcionProducto" required>'+

	              '<option>Seleccione el producto</option>'+

	              '</select>'+  

	            '</div>'+

	          '</div>'+

	          '<!-- Cantidad del producto -->'+

	          '<div class="col-xs-3 ingresoCantidad">'+
	            
	             '<input type="number" class="form-control nuevaCantidadProducto" name="nuevaCantidadProducto" min="1" value="0" stock nuevoStock required>'+

	          '</div>' +

	          '<!-- Precio del producto -->'+

	          '<div class="col-xs-3 ingresoPrecio" style="padding-left:0px">'+

	            '<div class="input-group">'+

				'<span class="input-group-addon"><i><b>Bs</b></i></span>'+
	                 
	              '<input type="text" class="form-control nuevoPrecioProducto" precioReal="" name="nuevoPrecioProducto" readonly required>'+
	 
	            '</div>'+
	             
	          '</div>'+

	        '</div>');


	        // AGREGAR LOS PRODUCTOS AL SELECT 

	         respuesta.forEach(funcionForEach);

	         function funcionForEach(item, index){

	         	

		         	$("#producto"+numProducto).append(

						'<option idProducto="'+item.id+'" value="'+item.descripcion+'">'+item.descripcion+'</option>'
		         	)

		         
		         

		         

	         }

        	 // SUMAR TOTAL DE PRECIOS

    		sumarTotalPreciosCompra()

    	
	        // PONER FORMATO AL PRECIO DE LOS PRODUCTOS

	        $(".nuevoPrecioProducto").number(true, 2);


      	}

	})

})

    $(document).ready(function () {
        $('#modalEstado').modal('show');
    });

    $(".tablas").on("click", ".btnAprobarCompra", function () {
    var idCompra = $(this).attr("idCompra");

    swal({
        title: "¿Estás seguro de aprobar esta compra?",
        text: "Se aumentará el stock de los productos.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Sí, aprobar compra"
    }).then(function (result) {
        if (result.value) {
            window.location = "index.php?ruta=solicitudes-de-compra";
        }
    });
});

</script>
<?php endif; ?>


