/*=============================================
CARGAR LA TABLA DINÁMICA DE PRODUCTOS
=============================================*/
// $.ajax({
// 	url: "ajax/datatable-productos.ajax.php",
// 	success:function(respuesta){
// 		console.log("respuesta", respuesta);
// 	}
// })
var perfilOculto = $("#perfilOculto").val();
var rolOculto = $("#rolOculto").val() || "";
if($('.tablaProductos').length){
$('.tablaProductos').DataTable({
 "ajax": "ajax/datatable-productos.ajax.php?perfilOculto=" + perfilOculto + "&rolOculto=" + rolOculto,
 "deferRender": true,
 "retrieve": true,
 "processing": true,
 "language": {
  "sProcessing": "Procesando...",
  "sLengthMenu": "Mostrar _MENU_ registros",
  "sZeroRecords": "No se encontraron resultados",
  "sEmptyTable": "Ningún dato disponible en esta tabla",
  "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
  "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0",
  "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
  "sInfoPostFix": "",
  "sSearch": "Buscar:",
  "sUrl": "",
  "sInfoThousands": ",",
  "sLoadingRecords": "Cargando...",
  "oPaginate": {
   "sFirst": "Primero",
   "sLast": "Último",
   "sNext": "Siguiente",
   "sPrevious": "Anterior"
  },
  "oAria": {
   "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
   "sSortDescending": ": Activar para ordenar la columna de manera descendente"
  }
 }
});
}
if($('.tablaProductos1').length){
$('.tablaProductos1').DataTable({
 "ajax": "ajax/datatable-productos0.ajax.php?perfilOculto=" + perfilOculto,
 "deferRender": true,
 "retrieve": true,
 "processing": true,
 "language": {
  "sProcessing": "Procesando...",
  "sLengthMenu": "Mostrar _MENU_ registros",
  "sZeroRecords": "No se encontraron resultados",
  "sEmptyTable": "Ningún dato disponible en esta tabla",
  "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
  "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0",
  "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
  "sInfoPostFix": "",
  "sSearch": "Buscar:",
  "sUrl": "",
  "sInfoThousands": ",",
  "sLoadingRecords": "Cargando...",
  "oPaginate": {
   "sFirst": "Primero",
   "sLast": "Último",
   "sNext": "Siguiente",
   "sPrevious": "Anterior"
  },
  "oAria": {
   "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
   "sSortDescending": ": Activar para ordenar la columna de manera descendente"
  }
 }
});
}
if($('.tablaProductos2').length){
$('.tablaProductos2').DataTable({
 "ajax": "ajax/datatable-productos1.ajax.php?perfilOculto=" + perfilOculto,
 "deferRender": true,
 "retrieve": true,
 "processing": true,
 "language": {
  "sProcessing": "Procesando...",
  "sLengthMenu": "Mostrar _MENU_ registros",
  "sZeroRecords": "No se encontraron resultados",
  "sEmptyTable": "Ningún dato disponible en esta tabla",
  "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
  "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0",
  "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
  "sInfoPostFix": "",
  "sSearch": "Buscar:",
  "sUrl": "",
  "sInfoThousands": ",",
  "sLoadingRecords": "Cargando...",
  "oPaginate": {
   "sFirst": "Primero",
   "sLast": "Último",
   "sNext": "Siguiente",
   "sPrevious": "Anterior"
  },
  "oAria": {
   "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
   "sSortDescending": ": Activar para ordenar la columna de manera descendente"
  }
 }
});
}
/*=============================================
CAPTURANDO LA CATEGORIA PARA ASIGNAR CÓDIGO
=============================================*/
$("#nuevaCategoria").change(function() {
  var idCategoria = $(this).val();
  var datos = new FormData();
  datos.append("idCategoria", idCategoria);
  $.ajax({
   url: "ajax/productos.ajax.php",
   method: "POST",
   data: datos,
   cache: false,
   contentType: false,
   processData: false,
   dataType: "json",
   success: function(respuesta) {
    if(!respuesta) {
     var nuevoCodigo = "TM" + idCategoria + "01";
     $("#nuevoCodigo").val(nuevoCodigo);
    } else {
     var codigoBase = String(respuesta["codigo"] || "");
     var nuevoCodigo = codigoBase.substring(0, 2).toUpperCase() === "TM" ? "TM" + codigoBase.substring(2) : "TM" + codigoBase;
     $("#nuevoCodigo").val(nuevoCodigo);
    }
   }
  })
 })
 /*=============================================
 AGREGANDO PRECIO DE VENTA
 =============================================*/
$("#nuevoPrecioCompra, #editarPrecioCompra").change(function() {
  if($(".porcentaje").prop("checked")) {
   var valorPorcentaje = $(".nuevoPorcentaje").val();
   var porcentaje = Number(($("#nuevoPrecioCompra").val() / (1 - valorPorcentaje / 100)));
   var editarPorcentaje = Number(($("#editarPrecioCompra").val() / (1 - valorPorcentaje / 100)));
   $("#nuevoPrecioVenta").val(porcentaje);
   $("#nuevoPrecioVenta").prop("readonly", true);
   $("#editarPrecioVenta").val(editarPorcentaje);
   $("#editarPrecioVenta").prop("readonly", true);
  }
 })
 /*=============================================
 CAMBIO DE PORCENTAJE
 =============================================*/
$(".nuevoPorcentaje").change(function() {
 if($(".porcentaje").prop("checked")) {
  var valorPorcentaje = $(this).val();
  var porcentaje = Number(($("#nuevoPrecioCompra").val() / (1 - valorPorcentaje / 100)));
  var editarPorcentaje = Number(($("#editarPrecioCompra").val() / (1 - valorPorcentaje / 100)));
  $("#nuevoPrecioVenta").val(porcentaje);
  $("#nuevoPrecioVenta").prop("readonly", true);
  $("#editarPrecioVenta").val(editarPorcentaje);
  $("#editarPrecioVenta").prop("readonly", true);
 }
})
$(".porcentaje").on("ifUnchecked", function() {
 $("#nuevoPrecioVenta").prop("readonly", false);
 $("#editarPrecioVenta").prop("readonly", false);
})
$(".porcentaje").on("ifChecked", function() {
  $("#nuevoPrecioVenta").prop("readonly", true);
  $("#editarPrecioVenta").prop("readonly", true);
 })
 /*=============================================
 SUBIENDO LA FOTO DEL PRODUCTO
 =============================================*/
$(".nuevaImagen").change(function() {
  var imagen = this.files[0];
  /*=============================================
  	VALIDAMOS EL FORMATO DE LA IMAGEN SEA JPG O PNG
  	=============================================*/
  if(imagen["type"] != "image/jpg" && imagen["type"] != "image/jpeg" && imagen["type"] != "image/pjpeg" && imagen["type"] != "image/png") {
   $(".nuevaImagen").val("");
   swal({
    title: "Error al subir la imagen",
    text: "¡La imagen debe estar en formato JPG o PNG!",
    type: "error",
    confirmButtonText: "¡Cerrar!"
   });
  } else if(imagen["size"] > 2000000) {
   $(".nuevaImagen").val("");
   swal({
    title: "Error al subir la imagen",
    text: "¡La imagen no debe pesar más de 2MB!",
    type: "error",
    confirmButtonText: "¡Cerrar!"
   });
  } else {
   var datosImagen = new FileReader;
   datosImagen.readAsDataURL(imagen);
   $(datosImagen).on("load", function(event) {
    var rutaImagen = event.target.result;
    $(".previsualizar").attr("src", rutaImagen);
   })
  }
 })
 /*=============================================
 EDITAR PRODUCTO
 =============================================*/
$(document).on("click", "button.btnEditarProducto", function() {
  var idProducto = $(this).attr("idProducto");
  var datos = new FormData();
  datos.append("idProducto", idProducto);
  $.ajax({
   url: "ajax/productos.ajax.php",
   method: "POST",
   data: datos,
   cache: false,
   contentType: false,
   processData: false,
   dataType: "json",
   success: function(respuesta) {
    var datosCategoria = new FormData();
    datosCategoria.append("idCategoria", respuesta["id_categoria"]);
    $.ajax({
     url: "ajax/categorias.ajax.php",
     method: "POST",
     data: datosCategoria,
     cache: false,
     contentType: false,
     processData: false,
     dataType: "json",
     success: function(respuesta) {
      $("#editarCategoria").val(respuesta["id"]);
      $("#editarCategoria").html(respuesta["ruta_categoria"] || respuesta["categoria"]);
     }
    })
    $("#editarCodigo").val(respuesta["codigo"]);
    $("#editarMarca").val(respuesta["id_marca"] || "0");
     $("#editarDescripcion").val(respuesta["descripcion"]);
     $("#editarDetalle").val(respuesta["detalle"] || "");
    $("#editarCodigoGenerico").val(respuesta["codigo_producto_generico"] || "");
    $("#editarCodigoUnico").val(respuesta["codigo_barras_unico"] || "");
    $("#editarStock").val(respuesta["stock"]);
    $("#editarPrecioCompra").val(respuesta["precio_compra"]);
    $("#editarPrecioVenta").val(respuesta["precio_venta"]);
    if(respuesta["imagen"] != "") {
     $("#imagenActual").val(respuesta["imagen"]);
     $(".previsualizar").attr("src", respuesta["imagen"]);
    }
   }
  })
 })
 /*=============================================
 ELIMINAR PRODUCTO
 =============================================*/
$(document).on("click", "button.btnEliminarProducto", function(event) {
 event.preventDefault();
 event.stopPropagation();
 var idProducto = $(this).attr("idProducto");
 var codigo = $(this).attr("codigo");
 var imagen = $(this).attr("imagen");
 if(window.location.href.indexOf("productos-almacen") !== -1 && $("#modalEliminarProductoAlmacen").length){
  $(".modal.in, .modal.show").modal("hide");
  $(".modal-backdrop").remove();
  $("body").removeClass("modal-open").css("padding-right", "");
  $("#eliminarProductoId").val(idProducto);
  $("#eliminarProductoCodigo").text(codigo || "Producto seleccionado");
  $("#eliminarProductoImagen").val(imagen || "");
  setTimeout(function(){
   $("#modalEliminarProductoAlmacen").modal("show");
  }, 180);
  return;
 }
 $(".modal.in, .modal.show").modal("hide");
 $(".modal-backdrop").remove();
 $("body").removeClass("modal-open").css("padding-right", "");
 swal({
  title: '¿Está seguro de borrar el producto?',
  text: "¡Si no lo está puede cancelar la accíón!",
  type: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#3085d6',
  cancelButtonColor: '#d33',
  cancelButtonText: 'Cancelar',
  confirmButtonText: 'Si, borrar producto!'
 }).then(function(result) {
  if(result.value) {
   var retorno = window.location.href.indexOf("productos-almacen") !== -1 ? "&retorno=productos-almacen" : "";
   window.location = "index.php?ruta=productos&idProducto=" + idProducto + "&imagen=" + imagen + "&codigo=" + codigo + retorno;
  }
 })
})

$(document).on("click", ".btnNuevaMarcaProducto", function(event){
 event.preventDefault();
 event.stopPropagation();
 var selector = $(this).attr("data-selector") || "#nuevaMarca";
 var modalPadre = $(this).closest(".modal");
 $("#selectorMarcaProducto").val(selector);
 $("#formNuevaMarcaProducto")[0].reset();
 $("#selectorMarcaProducto").val(selector);
 $("#modalNuevaMarcaProducto").data("modal-padre", modalPadre.attr("id") || "");
 $("#modalNuevaMarcaProducto").modal("show");
 setTimeout(function(){
  $(".modal-backdrop").last().addClass("tm-marca-backdrop");
  $("#nombreMarcaProducto").focus();
 }, 250);
});

$("#modalNuevaMarcaProducto").on("hidden.bs.modal", function(){
 var idPadre = $(this).data("modal-padre");
 if(idPadre && $("#" + idPadre).hasClass("in")){
  $("body").addClass("modal-open");
 }
});

$(document).on("submit", "#formNuevaMarcaProducto", function(event){
 event.preventDefault();
 var nombre = ($("#nombreMarcaProducto").val() || "").trim();
 var descripcion = ($("#descripcionMarcaProducto").val() || "").trim();
 var selector = $("#selectorMarcaProducto").val() || "#nuevaMarca";

 if(!nombre){
  swal({type:"warning",title:"Nombre requerido",text:"Ingrese el nombre de la nueva marca.",confirmButtonText:"Cerrar"});
  return;
 }

 var boton = $("#guardarNuevaMarcaProducto");
 boton.prop("disabled", true);
 $.ajax({
  url:"ajax/productos.ajax.php",
  method:"POST",
  data:{
   crearMarcaRapida:1,
   nombreMarcaProducto:nombre,
   descripcionMarcaProducto:descripcion
  },
  dataType:"json"
 }).done(function(respuesta){
  if(!respuesta || !respuesta.marca || !respuesta.marca.id_marca){
   swal({type:"error",title:"No se pudo guardar la marca",text:(respuesta && respuesta.message) || "Intente nuevamente.",confirmButtonText:"Cerrar"});
   return;
  }

  var idMarca = String(respuesta.marca.id_marca);
  var nombreMarca = respuesta.marca.nombre;
  $("#nuevaMarca, #editarMarca").each(function(){
   if($(this).find('option[value="' + idMarca + '"]').length === 0){
    $(this).append($("<option>", {value:idMarca, text:nombreMarca}));
   }
  });
  $(selector).val(idMarca);
  $("#modalNuevaMarcaProducto").modal("hide");
  swal({
   type:"success",
   title:respuesta.status === "exists" ? "Marca ya registrada" : "Marca creada",
   text:respuesta.message || "La marca fue seleccionada en el producto.",
   confirmButtonText:"Continuar"
  });
 }).fail(function(){
  swal({type:"error",title:"Error de conexion",text:"No se pudo registrar la marca.",confirmButtonText:"Cerrar"});
 }).always(function(){
  boton.prop("disabled", false);
 });
});

$(document).on("click", ".btnVerCodigosUnicos", function(){
 var idProducto = $(this).attr("idProducto");
 var codigo = $(this).attr("codigo") || "";
 var datos = new FormData();
 datos.append("idProducto", idProducto);

 $("#tituloCodigosUnicos").text(codigo);
 $("#tbodyCodigosUnicosProducto").html('<tr><td colspan="3" class="text-center">Cargando...</td></tr>');
 $("#listaCodigosUnicosProducto").html('<div class="tm-code-unit"><strong>Cargando...</strong><small>Espere un momento</small></div>');

 $.ajax({
  url: "ajax/productos-codigos.ajax.php",
  method: "POST",
  data: datos,
  cache: false,
  contentType: false,
  processData: false,
  dataType: "json",
  success: function(respuesta){
   var filas = "";
   if(!respuesta.ok || !respuesta.codigos || respuesta.codigos.length === 0){
    $("#tbodyCodigosUnicosProducto").html('<tr><td colspan="3" class="text-center text-muted">Este producto aun no tiene codigos unicos registrados.</td></tr>');
    $("#listaCodigosUnicosProducto").html('<div class="tm-code-unit"><strong>Sin codigos unicos</strong><small>Este producto aun no tiene unidades registradas.</small></div>');
    return;
   }
   respuesta.codigos.forEach(function(item){
    var estado = item.estado || "";
    var clase = estado == "disponible" ? "success" : (estado == "vendido" ? "danger" : "warning");
    filas += '<tr>'+
     '<td><strong>'+item.codigo_barras_unico+'</strong></td>'+
     '<td><span class="label label-'+clase+'">'+estado+'</span></td>'+
     '<td>'+(item.fecha_ingreso || "")+'</td>'+
    '</tr>';
    if($("#listaCodigosUnicosProducto").length){
     filas += '';
    }
   });
   $("#tbodyCodigosUnicosProducto").html(filas);
   if($("#listaCodigosUnicosProducto").length){
    var tarjetas = respuesta.codigos.map(function(item){
     var estado = item.estado || "";
     var clase = estado == "disponible" ? "success" : (estado == "vendido" ? "danger" : "warning");
     return '<div class="tm-code-unit">'+
      '<div><strong>'+item.codigo_barras_unico+'</strong><small>'+(item.fecha_ingreso || "")+'</small></div>'+
      '<span class="label label-'+clase+'">'+estado+'</span>'+
     '</div>';
    }).join("");
    $("#listaCodigosUnicosProducto").html(tarjetas);
   }
  }
 });
});
