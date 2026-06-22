/*=============================================
CARGAR LA TABLA DINГЃMICA DE VENTAS
=============================================*/
// $.ajax({
// 	url: "ajax/datatable-ventas.ajax.php",
// 	success:function(respuesta){
// 		console.log("respuesta", respuesta);
// 	}
// })// 
if($('.tablaCompras').length){
$('.tablaCompras').DataTable({
	"ajax": {
	 "url": "ajax/datatable-compras.ajax.php" + (window.location.search || ""), // URL del archivo que devuelve los datos
	 "method": "GET",
	 "dataSrc": function(json) {
	  console.log("Datos recibidos:", json); // DepuraciГіn
	  return json.data;
	 },
	 "error": function(xhr, status, error) {
	  console.error("Error en la solicitud AJAX:", xhr.responseText);
	 }
	},
	"deferRender": true,
	"retrieve": true,
	"processing": true,
	"language": {
	 "sProcessing": "Procesando...",
	 "sLengthMenu": "Mostrar _MENU_ registros",
	 "sZeroRecords": "No se encontraron resultados",
	 "sEmptyTable": "NingГєn dato disponible en esta tabla",
	 "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
	 "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0",
	 "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
	 "sSearch": "Buscar:",
	 "oPaginate": {
	  "sFirst": "Primero",
	  "sLast": "Гљltimo",
	  "sNext": "Siguiente",
	  "sPrevious": "Anterior"
	 }
	}
   });
}

var productosCompraCards = [];
var paginaProductosCompra = 1;

function textoPlanoCompra(html){
 return $("<div>").html(html || "").text().replace(/\s+/g, " ").trim();
}

function stockNumeroCompra(html){
 var texto = textoPlanoCompra(html).replace(/[^\d-]/g, "");
 var numero = parseInt(texto, 10);
 return isNaN(numero) ? 0 : numero;
}

function sincronizarBotonesProductosCompra(){
 var idsSeleccionados = [];
 $(".nuevoProducto .nuevaDescripcionProducto").each(function(){
  idsSeleccionados.push($(this).attr("idProducto"));
 });
 $(".productosCardsCompra button.recuperarBoton, #modalDetalleProductoCompra button.recuperarBoton").each(function(){
  var idBoton = $(this).attr("idProducto");
  if(idsSeleccionados.indexOf(idBoton) !== -1){
   $(this).removeClass("btn-primary agregarProducto").addClass("btn-default");
  }
 });
}

function generarPaginacionProductosCompra(totalPaginas){
 var paginas = [];
 var inicioVentana = Math.max(1, paginaProductosCompra - 2);
 var finVentana = Math.min(totalPaginas, inicioVentana + 4);
 if(finVentana - inicioVentana < 4){
  inicioVentana = Math.max(1, finVentana - 4);
 }
 for(var p = inicioVentana; p <= finVentana; p++){
  paginas.push(p);
 }
 if(totalPaginas > 5 && paginas.indexOf(totalPaginas) === -1){
  paginas.push("...");
  paginas.push(totalPaginas);
 }
 return paginas.map(function(pagina){
  if(pagina === "..."){
   return '<span class="productosCompraPuntos">...</span>';
  }
  return '<button type="button" class="productosCompraPagina '+(pagina == paginaProductosCompra ? 'active' : '')+'" data-page-number="'+pagina+'">'+pagina+'</button>';
 }).join("");
}

function renderProductosCompraCards(){
 if(!$(".productosCardsCompra").length){
  return;
 }
 var busqueda = ($("#buscarProductoCompraCards").val() || "").toLowerCase().trim();
 var filtroStock = $("#filtroStockCompraCards").val() || "";
 var porPagina = parseInt($("#cantidadProductoCompraCards").val(), 10) || 12;
 var productosFiltrados = productosCompraCards.filter(function(fila){
  var texto = (textoPlanoCompra(fila[2]) + " " + textoPlanoCompra(fila[3])).toLowerCase();
  var stock = stockNumeroCompra(fila[4]);
  var coincideStock = true;
  if(filtroStock === "sin-stock"){
   coincideStock = stock <= 0;
  }else if(filtroStock === "stock-bajo"){
   coincideStock = stock <= 3;
  }
  return texto.indexOf(busqueda) !== -1 && coincideStock;
 });
 var totalPaginas = Math.max(1, Math.ceil(productosFiltrados.length / porPagina));
 if(paginaProductosCompra > totalPaginas){
  paginaProductosCompra = totalPaginas;
 }
 var inicio = (paginaProductosCompra - 1) * porPagina;
 var pagina = productosFiltrados.slice(inicio, inicio + porPagina);
 if(!pagina.length){
  $(".productosCardsCompra").html('<div class="text-center text-muted" style="padding:35px 0;font-weight:800;">No se encontraron productos para solicitar.</div>');
  $(".productosCompraInfo").text("Sin productos para mostrar");
  $(".productosCompraPaginas").html('<button type="button" class="productosCompraPagina active" data-page-number="1">1</button>');
  $(".productosCompraPaginacion button[data-page='prev'], .productosCompraPaginacion button[data-page='next']").prop("disabled", true);
  return;
 }
 var html = pagina.map(function(fila){
  var indiceOriginal = productosCompraCards.indexOf(fila);
  return '<div class="compra-product-card" data-product-index="'+indiceOriginal+'">'+
   '<div class="compra-product-img">'+(fila[1] || '')+'</div>'+
   '<span class="compra-product-code">'+textoPlanoCompra(fila[2])+'</span>'+
   '<h4>'+textoPlanoCompra(fila[3])+'</h4>'+
   '<div class="compra-product-footer">'+
    '<div class="compra-product-stock">'+(fila[4] || '')+'</div>'+
    '<div class="compra-product-action">'+(fila[5] || '')+'</div>'+
   '</div>'+
   '<div class="compra-product-hint"><i class="fa fa-search-plus"></i> Ver detalle</div>'+
  '</div>';
 }).join("");
 $(".productosCardsCompra").html(html);
 $(".productosCompraInfo").text("Mostrando "+(inicio + 1)+"-"+(inicio + pagina.length)+" de "+productosFiltrados.length+" productos");
 $(".productosCompraPaginas").html(generarPaginacionProductosCompra(totalPaginas));
 $(".productosCompraPaginacion button[data-page='prev']").prop("disabled", paginaProductosCompra <= 1);
 $(".productosCompraPaginacion button[data-page='next']").prop("disabled", paginaProductosCompra >= totalPaginas);
 sincronizarBotonesProductosCompra();
}

function abrirModalDetalleProductoCompra(fila){
 if(!fila || !$("#modalDetalleProductoCompra").length){
  return;
 }
 var codigo = textoPlanoCompra(fila[2]);
 var descripcion = textoPlanoCompra(fila[3]);
 $("#modalDetalleProductoCompra .compra-product-modal-img").html(fila[1] || '');
 $("#modalDetalleProductoCompra .compra-product-modal-code").text(codigo || "SIN CODIGO");
 $("#modalDetalleProductoCompra .compra-product-modal-code-value").text(codigo || "-");
 $("#modalDetalleProductoCompra .compra-product-modal-name").text(descripcion || "Detalle del producto");
 $("#modalDetalleProductoCompra .compra-product-modal-description").text(descripcion || "Sin descripcion registrada.");
 $("#modalDetalleProductoCompra .compra-product-modal-stock").html(fila[4] || "-");
 $("#modalDetalleProductoCompra .compra-modal-actions").html(fila[5] || "");
 $("#modalDetalleProductoCompra").modal("show");
 sincronizarBotonesProductosCompra();
}

function inicializarProductosCompraCards(){
 if(!$(".productosCardsCompra").length){
  return;
 }
 $.getJSON("ajax/datatable-compras.ajax.php" + (window.location.search || ""), function(respuesta){
  productosCompraCards = respuesta.data || [];
  paginaProductosCompra = 1;
  renderProductosCompraCards();
 }).fail(function(xhr){
  console.error("Error cargando productos de compra:", xhr.responseText);
  $(".productosCardsCompra").html('<div class="text-center text-danger" style="padding:35px 0;font-weight:800;">No se pudieron cargar los productos.</div>');
  $(".productosCompraInfo").text("Error al cargar productos");
 });
}

$(document).on("input", "#buscarProductoCompraCards", function(){
 paginaProductosCompra = 1;
 renderProductosCompraCards();
});

$(document).on("change", "#filtroStockCompraCards, #cantidadProductoCompraCards", function(){
 paginaProductosCompra = 1;
 renderProductosCompraCards();
});

$(document).on("click", ".productosCompraPaginacion button", function(){
 if($(this).prop("disabled")){
  return;
 }
 if($(this).attr("data-page-number")){
  paginaProductosCompra = parseInt($(this).attr("data-page-number"), 10) || 1;
  renderProductosCompraCards();
  return;
 }
 paginaProductosCompra += ($(this).attr("data-page") == "next") ? 1 : -1;
 renderProductosCompraCards();
});

$(document).on("click", ".compra-product-card", function(event){
 if($(event.target).closest("button, a, .btn").length){
  return;
 }
 var indice = parseInt($(this).attr("data-product-index"), 10);
 abrirModalDetalleProductoCompra(productosCompraCards[indice]);
});

inicializarProductosCompraCards();
   /*=============================================
   AGREGANDO PRODUCTOS A LA VENTA DESDE LA TABLA
   =============================================*/
   $(document).on("click", ".productosCardsCompra button.agregarProducto, #modalDetalleProductoCompra button.agregarProducto, .tablaCompras tbody button.agregarProducto", function() {
	var idProducto = $(this).attr("idProducto");
	$(this).removeClass("btn-primary agregarProducto");
	$(this).addClass("btn-default");
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
	  var descripcion = respuesta["descripcion"];
	  var stock = respuesta["stock"];
	  var precio = respuesta["precio_compra"];
	  $(".nuevoProducto").append('<div class="row" style="padding:5px 15px">' + // Comillas simples correctas
		'<!-- DescripciГіn del producto -->' + '<div class="col-xs-6" style="padding-right:0px">' + '<div class="input-group">' + '<span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarProducto" idProducto="' + idProducto + '"><i class="fa fa-times"></i></button></span>' + '<input type="text" class="form-control nuevaDescripcionProducto" idProducto="' + idProducto + '" name="agregarProducto" value="' + descripcion + '" readonly required>' + '</div>' + '</div>' + '<!-- Cantidad del producto -->' + '<div class="col-xs-3">' + '<input type="number" class="form-control nuevaCantidadProducto" name="nuevaCantidadProducto" min="1" value="1" stock="' + stock + '" nuevoStock="' + (Number(stock) + 1) + '" required>' + '</div>' + '<!-- Precio del producto -->' + '<div class="col-xs-3 ingresoPrecio" style="padding-left:0px">' + '<div class="input-group">' + '<span class="input-group-addon"><i><b>Bs</b></i></span>' + '<input type="text" class="form-control nuevoPrecioProducto" precioReal="' + precio + '" name="nuevoPrecioProducto" value="' + precio + '" readonly required>' + '</div>' + '</div>' + '</div>')
	   // SUMAR TOTAL DE PRECIOS
	  sumarTotalPreciosCompra()
	   // AGRUPAR PRODUCTOS EN FORMATO JSON
	  listarProductoscompra()
	  // PONER FORMATO AL PRECIO DE LOS PRODUCTOS
	  $(".nuevoPrecioProducto").number(true, 2);
	  localStorage.removeItem("quitarProducto");
	  sincronizarBotonesProductosCompra();
	 }
	})
   });
   /*=============================================
   CUANDO CARGUE LA TABLA CADA VEZ QUE NAVEGUE EN ELLA
   =============================================*/
   $(".tablaCompras").on("draw.dt", function() {
	 if(localStorage.getItem("quitarProducto") != null) {
	  var listaIdProductos = JSON.parse(localStorage.getItem("quitarProducto"));
	  for(var i = 0; i < listaIdProductos.length; i++) {
	   $("button.recuperarBoton[idProducto='" + listaIdProductos[i]["idProducto"] + "']").removeClass('btn-default');
	   $("button.recuperarBoton[idProducto='" + listaIdProductos[i]["idProducto"] + "']").addClass('btn-primary agregarProducto');
	  }
	 }
	})
	/*=============================================
	QUITAR PRODUCTOS DE LA VENTA Y RECUPERAR BOTГ“N
	=============================================*/
   var idQuitarProducto = [];
   localStorage.removeItem("quitarProducto");
   $(".formularioCompra").on("click", "button.quitarProducto", function() {
	 $(this).parent().parent().parent().parent().remove();
	 var idProducto = $(this).attr("idProducto");
	 /*=============================================
	 ALMACENAR EN EL LOCALSTORAGE EL ID DEL PRODUCTO A QUITAR
	 =============================================*/
	 if(localStorage.getItem("quitarProducto") == null) {
	  idQuitarProducto = [];
	 } else {
	  idQuitarProducto.concat(localStorage.getItem("quitarProducto"))
	 }
	 idQuitarProducto.push({
	  "idProducto": idProducto
	 });
	 localStorage.setItem("quitarProducto", JSON.stringify(idQuitarProducto));
	 $("button.recuperarBoton[idProducto='" + idProducto + "']").removeClass('btn-default');
	 $("button.recuperarBoton[idProducto='" + idProducto + "']").addClass('btn-primary agregarProducto');
	 if($(".nuevoProducto").children().length == 0) {
	  $("#nuevoImpuestoCompra").val(0);
	  $("#nuevoTotalCompra").val(0);
	  $("#totalCompra").val(0);
	  $("#nuevoTotalCompra").attr("total", 0);
	 } else {
	  // SUMAR TOTAL DE PRECIOS
	  sumarTotalPreciosCompra()
	   // AGRUPAR PRODUCTOS EN FORMATO JSON
	  listarProductoscompra()
	 }
	})
	/*=============================================
	AGREGANDO PRODUCTOS DESDE EL BOTГ“N PARA DISPOSITIVOS
	=============================================*/
   var numProductos = 0;
   $(".btnAgregarProductos, .btnAgregarProductoCompra").click(function() {
	 numProductos++;
	 var datos = new FormData();
	 datos.append("traerProductos", "ok");
	 $.ajax({
	  url: "ajax/productos.ajax.php",
	  method: "POST",
	  data: datos,
	  cache: false,
	  contentType: false,
	  processData: false,
	  dataType: "json",
	  success: function(respuesta) {
	   $(".nuevoProducto").append('<div class="row" style="padding:5px 15px">' + '<!-- DescripciГіn del producto -->' + '<div class="col-xs-6" style="padding-right:0px">' + '<div class="input-group">' + '<span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarProducto" idProducto><i class="fa fa-times"></i></button></span>' + '<select class="form-control nuevaDescripcionProducto" id="producto' + numProductos + '" idProducto name="nuevaDescripcionProducto" required>' + '<option>Seleccione el producto</option>' + '</select>' + '</div>' + '</div>' + '<!-- Cantidad del producto -->' + '<div class="col-xs-3 ingresoCantidad">' + '<input type="number" class="form-control nuevaCantidadProducto" name="nuevaCantidadProducto" min="1" value="0" stock nuevoStock required>' + '</div>' + '<!-- Precio del producto -->' + '<div class="col-xs-3 ingresoPrecio" style="padding-left:0px">' + '<div class="input-group">' + '<span class="input-group-addon"><i><b>Bs</b></i></span>' + '<input type="text" class="form-control nuevoPrecioProducto" precioReal="" name="nuevoPrecioProducto" readonly required>' + '</div>' + '</div>' + '</div>');
	   // AGREGAR LOS PRODUCTOS AL SELECT 
	   respuesta.forEach(funcionForEach);
   
	   function funcionForEach(item, index) {
		$("#producto" + numProductos).append('<option idProducto="' + item.id + '" value="' + item.descripcion + '">' + item.descripcion + '</option>')
	   }
	   // SUMAR TOTAL DE PRECIOS
	   sumarTotalPreciosCompra()
		// PONER FORMATO AL PRECIO DE LOS PRODUCTOS
	   $(".nuevoPrecioProducto").number(true, 2);
	  }
	 })
	})
	/*=============================================
	SELECCIONAR PRODUCTO
	=============================================*/
   $(".formularioCompra").on("change", "select.nuevaDescripcionProducto", function() {
	 var nombreProducto = $(this).val();
	 var nuevaDescripcionProducto = $(this).parent().parent().parent().children().children().children(".nuevaDescripcionProducto");
	 var nuevoPrecioProducto = $(this).parent().parent().parent().children(".ingresoPrecio").children().children(".nuevoPrecioProducto");
	 var nuevaCantidadProducto = $(this).parent().parent().parent().children(".ingresoCantidad").children(".nuevaCantidadProducto");
	 var datos = new FormData();
	 datos.append("nombreProducto", nombreProducto);
	 $.ajax({
	  url: "ajax/productos.ajax.php",
	  method: "POST",
	  data: datos,
	  cache: false,
	  contentType: false,
	  processData: false,
	  dataType: "json",
	  success: function(respuesta) {
	   $(nuevaDescripcionProducto).attr("idProducto", respuesta["id"]);
	   $(nuevaCantidadProducto).attr("stock", respuesta["stock"]);
	   $(nuevaCantidadProducto).attr("nuevoStock", Number(respuesta["stock"]) + 1);
	   $(nuevoPrecioProducto).val(respuesta["precio_compra"]);
	   $(nuevoPrecioProducto).attr("precioReal", respuesta["precio_compra"]);
	   // AGRUPAR PRODUCTOS EN FORMATO JSON
	   listarProductoscompra()
	  }
	 })
	})
	/*=============================================
	MODIFICAR LA CANTIDAD
	=============================================*/
   $(".formularioCompra").on("change", "input.nuevaCantidadProducto", function() {
	 var precio = $(this).parent().parent().children(".ingresoPrecio").children().children(".nuevoPrecioProducto");
	 var precioFinal = $(this).val() * precio.attr("precioReal");
	 precio.val(precioFinal);
	 var nuevoStock = Number($(this).attr("stock")) + Number($(this).val());
	 $(this).attr("nuevoStock", nuevoStock);
	 // SUMAR TOTAL DE PRECIOS
	 sumarTotalPreciosCompra()
	  // AGRUPAR PRODUCTOS EN FORMATO JSON
	 listarProductoscompra()
	})
	/*=============================================
	SUMAR TODOS LOS PRECIOS
	=============================================*/
   function sumarTotalPreciosCompra() {
	var precioItem = $(".nuevoPrecioProducto");
	var arraySumaPrecio = [];
	for(var i = 0; i < precioItem.length; i++) {
	 arraySumaPrecio.push(Number($(precioItem[i]).val()));
	}
   
	function sumaArrayPrecios(total, numero) {
	 return total + numero;
	}
	var sumaTotalPrecio = arraySumaPrecio.reduce(sumaArrayPrecios);
	$("#nuevoTotalCompra").val(sumaTotalPrecio);
	$("#totalCompra").val(sumaTotalPrecio);
	$("#nuevoTotalCompra").attr("total", sumaTotalPrecio);
   }
   /*=============================================
   FORMATO AL PRECIO FINAL
   =============================================*/
   $("#nuevoTotalCompra").number(true, 2);
   /*=============================================
   LISTAR TODOS LOS PRODUCTOS
   =============================================*/
   function listarProductoscompra() {
	var listaProductos = [];
	var descripcion = $(".nuevaDescripcionProducto");
	var cantidad = $(".nuevaCantidadProducto");
	var precio = $(".nuevoPrecioProducto");
	for(var i = 0; i < descripcion.length; i++) {
	 listaProductos.push({
	  "id": $(descripcion[i]).attr("idProducto"),
	  "descripcion": $(descripcion[i]).val(),
	  "cantidad": $(cantidad[i]).val(),
	  "stock": $(cantidad[i]).attr("nuevoStock"),
	  "precio": $(precio[i]).attr("precioReal"),
	  "total": $(precio[i]).val(),
	 });
	}
	console.log("Lista de productos generada:", listaProductos); // Verifica en la consola
	$("#listaProductos").val(JSON.stringify(listaProductos)); // Rellena el campo oculto
   }
   /*=============================================
   BOTON EDITAR Compra
   =============================================*/
   $(".tablas").on("click", ".btnEditarCompra", function() {
	 var idCompra = $(this).attr("idCompra");
	 window.location = "index.php?ruta=editar-compra&idCompra=" + idCompra;
	})
	/*=============================================
	FUNCIГ“N PARA DESACTIVAR LOS BOTONES AGREGAR CUANDO EL PRODUCTO YA HABГЌA SIDO SELECCIONADO EN LA CARPETA
	=============================================*/
   function quitarAgregarProducto() {
	//Capturamos todos los id de productos que fueron elegidos en la venta
	var idProductos = $(".quitarProducto");
	//Capturamos todos los botones de agregar que aparecen en la tabla
	var botonesTabla = $(".tablaCompras tbody button.agregarProducto, .productosCardsCompra button.agregarProducto, #modalDetalleProductoCompra button.agregarProducto");
	//Recorremos en un ciclo para obtener los diferentes idProductos que fueron agregados a la venta
	for(var i = 0; i < idProductos.length; i++) {
	 //Capturamos los Id de los productos agregados a la venta
	 var boton = $(idProductos[i]).attr("idProducto");
	 //Hacemos un recorrido por la tabla que aparece para desactivar los botones de agregar
	 for(var j = 0; j < botonesTabla.length; j++) {
	  if($(botonesTabla[j]).attr("idProducto") == boton) {
	   $(botonesTabla[j]).removeClass("btn-primary agregarProducto");
	   $(botonesTabla[j]).addClass("btn-default");
	  }
	 }
	}
   }
   /*=============================================
   CADA VEZ QUE CARGUE LA TABLA CUANDO NAVEGAMOS EN ELLA EJECUTAR LA FUNCIГ“N:
   =============================================*/
   $('.tablaCompras').on('draw.dt', function() {
	 quitarAgregarProducto();
	})
	/*=============================================
	BORRAR VENTA
	=============================================*/
   $(".tablas").on("click", ".btnEliminarCompra", function() {
	 var idCompra = $(this).attr("idCompra");
	 swal({
	  title: 'ВїEstГЎ seguro de borrar la compra?',
	  text: "ВЎSi no lo estГЎ puede cancelar la accГ­Гіn!",
	  type: 'warning',
	  showCancelButton: true,
	  confirmButtonColor: '#3085d6',
	  cancelButtonColor: '#d33',
	  cancelButtonText: 'Cancelar',
	  confirmButtonText: 'Si, borrar Compra!'
	 }).then(function(result) {
	  if(result.value) {
	   window.location = "index.php?ruta=solicitudes-de-compra";
	  }
	 })
	})
	/*=============================================
	IMPRIMIR FACTURA
	=============================================*/
   $(".tablas").on("click", ".btnImprimirNotaCompra", function() {
	 var codigoCompra = $(this).attr("codigoCompra");
	 window.open("extensiones/tcpdf/pdf/notacompra.php?codigoCompra=" + codigoCompra, "_blank");
	})
	/*=============================================
	RANGO DE FECHAS
	=============================================*/
   $('#daterangecompra-btn').daterangepicker({
	 ranges: {
	  'Hoy': [moment(), moment()],
	  'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
	  'Гљltimos 7 dГ­as': [moment().subtract(6, 'days'), moment()],
	  'Гљltimos 30 dГ­as': [moment().subtract(29, 'days'), moment()],
	  'Este mes': [moment().startOf('month'), moment().endOf('month')],
	  'Гљltimo mes': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
	 },
	 startDate: moment(),
	 endDate: moment()
	}, function(start, end) {
	 $('#daterangecompra-btn span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
	 var fechaInicial = start.format('YYYY-MM-DD');
	 var fechaFinal = end.format('YYYY-MM-DD');
	 var capturarRango = $("#daterange-btn span").html();
	 localStorage.setItem("capturarRango", capturarRango);
	 window.location = "index.php?ruta=solicitudes-de-compra&fechaInicial=" + fechaInicial + "&fechaFinal=" + fechaFinal;
	})
	/*=============================================
	CANCELAR RANGO DE FECHAS
	=============================================*/
   $(".daterangepicker.opensleft .range_inputs .cancelBtn").on("click", function() {
	 localStorage.removeItem("capturarRango");
	 localStorage.clear();
	 window.location = "solicitudes-de-compra";
	})
	/*=============================================
	CAPTURAR HOY
	=============================================*/
   $(".daterangepicker.opensleft .ranges li").on("click", function() {
	var textoHoy = $(this).attr("data-range-key");
	if(textoHoy == "Hoy") {
	 var d = new Date();
	 var dia = d.getDate();
	 var mes = d.getMonth() + 1;
	 var anio = d.getFullYear();
	 if(mes < 10) {
	  var fechaInicial = anio + "-0" + mes + "-" + dia;
	  var fechaFinal = anio + "-0" + mes + "-" + dia;
	 } else if(dia < 10) {
	  var fechaInicial = anio + "-" + mes + "-0" + dia;
	  var fechaFinal = anio + "-" + mes + "-0" + dia;
	 } else if(mes < 10 && dia < 10) {
	  var fechaInicial = anio + "-0" + mes + "-0" + dia;
	  var fechaFinal = anio + "-0" + mes + "-0" + dia;
	 } else {
	  var fechaInicial = anio + "-" + mes + "-" + dia;
	  var fechaFinal = anio + "-" + mes + "-" + dia;
	 }
	 localStorage.setItem("capturarRango", "Hoy");
	 window.location = "index.php?ruta=solicitudes-de-compra&fechaInicial=" + fechaInicial + "&fechaFinal=" + fechaFinal;
	}
   });

if($('#tablaProductosCompras').length){
   $('#tablaProductosCompras').DataTable({
    "ajax": {
        "url": "ajax/datatable-compras.ajax.php",
        "dataSrc": "data"
    },
    "deferRender": true,
    "retrieve": true,
    "processing": true,
    "language": {
        "sProcessing": "Procesando...",
        "sSearch": "Buscar:"
    }
});
}

//   $(document).on('click', '.agregarProducto', function() {
//     var precio = $(this).data('precio');
//     if (!precio || precio <= 0) {
//         // Mostrar modal de error
//         $('#modalErrorPrecio').modal('show');
//         return false; // evitar agregar
//     }
//     // Si tiene precio vГЎlido, continuar con agregar producto normalmente
// });
