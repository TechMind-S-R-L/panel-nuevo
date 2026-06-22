/*=============================================
CARGAR LA TABLA DINÁMICA DE VENTAS
=============================================*/

// $.ajax({

// 	url: "ajax/datatable-ventas.ajax.php",
// 	success:function(respuesta){
		
// 		console.log("respuesta", respuesta);

// 	}

// })// 

if($('.tablaVentas').length){
$('.tablaVentas').DataTable( {
    "ajax": "ajax/datatable-ventas.ajax.php",
    "deferRender": true,
	"retrieve": true,
	"processing": true,
	"pageLength": 6,
	"lengthMenu": [[6, 10, 25, 50], [6, 10, 25, 50]],
	"autoWidth": false,
	 "language": {

			"sProcessing":     "Procesando...",
			"sLengthMenu":     "Mostrar _MENU_ registros",
			"sZeroRecords":    "No se encontraron resultados",
			"sEmptyTable":     "Ningún dato disponible en esta tabla",
			"sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
			"sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0",
			"sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
			"sInfoPostFix":    "",
			"sSearch":         "Buscar:",
			"sUrl":            "",
			"sInfoThousands":  ",",
			"sLoadingRecords": "Cargando...",
			"oPaginate": {
			"sFirst":    "Primero",
			"sLast":     "Último",
			"sNext":     "Siguiente",
			"sPrevious": "Anterior"
			},
			"oAria": {
				"sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
				"sSortDescending": ": Activar para ordenar la columna de manera descendente"
			}

	}

} );
}

var productosVentaCards = [];
var paginaProductosVenta = 1;
var resizeProductosVentaTimer = null;
var pasoVentaActual = 1;
var totalPasosVenta = 4;

function textoPlanoVenta(html){
	return $("<div>").html(html || "").text().replace(/\s+/g, " ").trim();
}

function avisoPasoVenta(titulo, texto){
	if(typeof swal === "function"){
		swal({
			type: "warning",
			title: titulo,
			text: texto,
			confirmButtonText: "Entendido"
		});
		return;
	}

	alert(titulo + "\n" + texto);
}

function mostrarProductoAgregadoVenta(descripcion, cantidad){
	if(typeof swal === "function"){
		swal({
			type: "success",
			title: "Producto agregado",
			text: cantidad+" unidad(es) de "+descripcion+" agregadas a la venta.",
			confirmButtonText: "Continuar"
		});
		return;
	}

	alert("Producto agregado\n"+cantidad+" unidad(es) de "+descripcion+" agregadas a la venta.");
}

function formatoPrecioVenta(valor){
	var numero = Number(valor) || 0;
	return "Bs " + numero.toLocaleString("es-BO", {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2
	});
}

function escaparHtmlVenta(valor){
	return $("<div>").text(valor || "").html();
}

function activarTotalCantidadVenta(precio){
	var precioUnitario = Number(precio) || 0;
	var input = $(".swal2-container input.swal2-input");

	function actualizar(){
		var cantidad = parseInt(input.val(), 10) || 0;
		$(".js-total-cantidad-venta").text(formatoPrecioVenta(cantidad * precioUnitario));
	}

	input.off("input.tmCantidadVenta change.tmCantidadVenta")
		.on("input.tmCantidadVenta change.tmCantidadVenta", actualizar);

	actualizar();
}

function cerrarDetalleProductoVentaAntes(callback){
	var modal = $("#modalDetalleProductoVenta");
	var ejecutado = false;

	function continuar(){
		if(ejecutado){
			return;
		}
		ejecutado = true;
		callback();
	}

	if(modal.length && modal.is(":visible")){
		modal.one("hidden.bs.modal", continuar);
		modal.modal("hide");
		setTimeout(function(){
			if(!modal.is(":visible")){
				continuar();
			}
		}, 280);
		return;
	}

	continuar();
}

function pedirCantidadProductoVenta(descripcion, stock, precio){
	stock = Number(stock) || 0;
	precio = Number(precio) || 0;

	if(typeof swal !== "function"){
		var cantidadPrompt = parseInt(prompt("Cantidad para "+descripcion+" (precio: "+formatoPrecioVenta(precio)+", stock: "+stock+")", "1"), 10);
		if(!cantidadPrompt || cantidadPrompt < 1 || cantidadPrompt > stock){
			return Promise.resolve(null);
		}
		return Promise.resolve(cantidadPrompt);
	}

	return swal({
		title: "Cantidad a vender",
		html: '<div class="swal-producto-cantidad">'+
				'<strong>'+escaparHtmlVenta(descripcion)+'</strong>'+
				'<span>Stock disponible: '+stock+' unidad(es)</span>'+
				'<div style="margin-top:10px;display:grid;grid-template-columns:1fr 1fr;gap:8px;text-align:left">'+
					'<div style="padding:9px 10px;border:1px solid #dbeafe;border-radius:8px;background:#f8fbff">'+
						'<small style="display:block;color:#64748b;font-weight:700;text-transform:uppercase">Precio unitario</small>'+
						'<b>'+formatoPrecioVenta(precio)+'</b>'+
					'</div>'+
					'<div style="padding:9px 10px;border:1px solid #bfdbfe;border-radius:8px;background:#eff6ff">'+
						'<small style="display:block;color:#1d4ed8;font-weight:700;text-transform:uppercase">Total</small>'+
						'<b class="js-total-cantidad-venta">'+formatoPrecioVenta(precio)+'</b>'+
					'</div>'+
				'</div>'+
			'</div>',
		input: "number",
		inputValue: 1,
		inputAttributes: {
			min: 1,
			max: stock,
			step: 1
		},
		showCancelButton: true,
		confirmButtonText: "Agregar",
		cancelButtonText: "Cancelar",
		onOpen: function(){
			activarTotalCantidadVenta(precio);
		},
		didOpen: function(){
			activarTotalCantidadVenta(precio);
		},
		preConfirm: function(valor){
			var cantidad = parseInt(valor, 10);
			if(!cantidad || cantidad < 1){
				swal.showValidationMessage("Ingrese una cantidad mayor a 0.");
				return false;
			}
			if(cantidad > stock){
				swal.showValidationMessage("Solo hay "+stock+" unidad(es) disponibles.");
				return false;
			}
			return cantidad;
		}
	}).then(function(resultado){
		return resultado && resultado.value ? Number(resultado.value) : null;
	});
}

function hayClienteVentaSeleccionado(){
	return ($("#seleccionarCliente").val() || "") !== "";
}

function hayProductosVentaSeleccionados(){
	return $(".nuevoProducto .nuevaDescripcionProducto").length > 0;
}

function validarPasoVentaDestino(destino){
	if(destino <= 1){
		return true;
	}

	if(!hayClienteVentaSeleccionado()){
		avisoPasoVenta("Seleccione un cliente", "Primero debe escoger o registrar el cliente de la venta.");
		return false;
	}

	if(destino >= 4 && !hayProductosVentaSeleccionados()){
		avisoPasoVenta("Agregue productos", "Para revisar el resumen debe agregar al menos un producto a la venta.");
		return false;
	}

	return true;
}

function mostrarPasoVenta(paso){
	if(!$(".venta-step[data-venta-step]").length){
		return;
	}

	paso = Math.max(1, Math.min(totalPasosVenta, parseInt(paso, 10) || 1));

	if(!validarPasoVentaDestino(paso)){
		return;
	}

	pasoVentaActual = paso;
	$(".venta-step[data-venta-step]").addClass("venta-step-hidden");
	$(".venta-step[data-venta-step='"+pasoVentaActual+"']").removeClass("venta-step-hidden");

	$(".venta-wizard-dot").removeClass("active done").each(function(){
		var pasoBoton = parseInt($(this).attr("data-step-target"), 10) || 1;
		if(pasoBoton < pasoVentaActual){
			$(this).addClass("done");
		}
		if(pasoBoton === pasoVentaActual){
			$(this).addClass("active");
		}
	});

	$(".btnVentaPasoAnterior").prop("disabled", pasoVentaActual <= 1);
	$(".btnVentaPasoSiguiente").toggle(pasoVentaActual < totalPasosVenta);
	$(".venta-wizard-help").text(
		pasoVentaActual === 1 ? "Seleccione el cliente para continuar." :
		pasoVentaActual === 2 ? "Agregue los productos necesarios y avance." :
		pasoVentaActual === 3 ? "Revise cantidades antes del resumen." :
		"Verifique el total y genere la boleta de cobro."
	);

	if(pasoVentaActual === 2){
		renderProductosVentaCards();
	}
}

$(document).on("click", ".btnVentaPasoSiguiente", function(){
	mostrarPasoVenta(pasoVentaActual + 1);
});

$(document).on("click", ".btnVentaPasoAnterior", function(){
	mostrarPasoVenta(pasoVentaActual - 1);
});

$(document).on("click", ".venta-wizard-dot", function(){
	var destino = parseInt($(this).attr("data-step-target"), 10) || 1;
	mostrarPasoVenta(destino);
});

$(document).on("change", "#seleccionarCliente", function(){
	if($(".venta-step[data-venta-step]").length){
		mostrarPasoVenta(pasoVentaActual);
	}
});

$(document).on("submit", ".formularioVenta", function(event){
	if(!$(".venta-step[data-venta-step]").length){
		return true;
	}

	if(!hayClienteVentaSeleccionado()){
		event.preventDefault();
		mostrarPasoVenta(1);
		avisoPasoVenta("Seleccione un cliente", "La venta necesita un cliente antes de generar la boleta.");
		return false;
	}

	if(!hayProductosVentaSeleccionados()){
		event.preventDefault();
		mostrarPasoVenta(2);
		avisoPasoVenta("Agregue productos", "La venta necesita al menos un producto seleccionado.");
		return false;
	}

	return true;
});

function sincronizarBotonesProductosVenta(){
	var idsSeleccionados = [];

	$(".nuevoProducto .nuevaDescripcionProducto").each(function(){
		idsSeleccionados.push($(this).attr("idProducto"));
	});

	$(".productosCardsVenta button.recuperarBoton, #modalDetalleProductoVenta button.recuperarBoton").each(function(){
		var idBoton = $(this).attr("idProducto");
		if(idsSeleccionados.indexOf(idBoton) !== -1){
			$(this).removeClass("btn-primary agregarProducto").addClass("btn-default");
		}
	});
}

function abrirModalDetalleProductoVenta(fila){
	if(!fila || !$("#modalDetalleProductoVenta").length){
		return;
	}

	var codigo = textoPlanoVenta(fila[2]);
	var descripcion = textoPlanoVenta(fila[3]);
	var precio = fila[6] || '<span class="label label-default">Sin precio</span>';

	$("#modalDetalleProductoVenta .venta-product-modal-img").html(fila[1] || '');
	$("#modalDetalleProductoVenta .venta-product-modal-code").text(codigo);
	$("#modalDetalleProductoVenta .venta-product-modal-code-value").text(codigo || "-");
	$("#modalDetalleProductoVenta .venta-product-modal-name").text(descripcion || "Detalle del producto");
	$("#modalDetalleProductoVenta .venta-product-modal-description").text(descripcion || "Sin descripcion registrada.");
	$("#modalDetalleProductoVenta .venta-product-modal-stock").html(fila[4] || "-");
	$("#modalDetalleProductoVenta .venta-product-modal-price").html(precio);
	$("#modalDetalleProductoVenta .venta-product-modal-actions").html(fila[5] || "");
	$("#modalDetalleProductoVenta").modal("show");
	sincronizarBotonesProductosVenta();
}

function obtenerCantidadProductosVentaCards(){
	var valor = $("#cantidadProductoVentaCards").val();

	if(valor !== "auto"){
		return parseInt(valor, 10) || 12;
	}

	var grilla = document.querySelector(".productosCardsVenta");
	var columnas = 4;

	if(grilla && window.getComputedStyle){
		var estilos = window.getComputedStyle(grilla);
		var columnasCss = (estilos.gridTemplateColumns || "").split(" ").filter(function(columna){
			return columna && columna !== "none";
		});

		if(columnasCss.length){
			columnas = columnasCss.length;
		}else{
			columnas = Math.max(2, Math.floor(grilla.clientWidth / 155));
		}
	}

	return Math.max(12, Math.min(100, columnas * 3));
}

function renderProductosVentaCards(){
	if(!$(".productosCardsVenta").length){
		return;
	}

	var busqueda = ($("#buscarProductoVentaCards").val() || "").toLowerCase().trim();
	var porPagina = obtenerCantidadProductosVentaCards();
	var productosFiltrados = productosVentaCards.filter(function(fila){
		var texto = (textoPlanoVenta(fila[2]) + " " + textoPlanoVenta(fila[3])).toLowerCase();
		return texto.indexOf(busqueda) !== -1;
	});
	var totalPaginas = Math.max(1, Math.ceil(productosFiltrados.length / porPagina));

	if(paginaProductosVenta > totalPaginas){
		paginaProductosVenta = totalPaginas;
	}

	var inicio = (paginaProductosVenta - 1) * porPagina;
	var pagina = productosFiltrados.slice(inicio, inicio + porPagina);

	if(!pagina.length){
		$(".productosCardsVenta").html('<div class="venta-products-empty">No se encontraron productos para la busqueda.</div>');
		$(".productosVentaInfo").text("Sin productos para mostrar");
		$(".productosVentaPaginas").html('<button type="button" class="productosVentaPagina active" data-page-number="1">1</button>');
		$(".productosVentaPaginacion button").prop("disabled", true);
		return;
	}

	var html = pagina.map(function(fila){
		var indiceOriginal = productosVentaCards.indexOf(fila);
		return '<div class="venta-product-card" data-product-index="'+indiceOriginal+'">'+
			'<div class="venta-product-img">'+fila[1]+'</div>'+
			'<span class="venta-product-code">'+textoPlanoVenta(fila[2])+'</span>'+
			'<h4>'+textoPlanoVenta(fila[3])+'</h4>'+
			'<div class="venta-product-price">'+(fila[6] || '')+'</div>'+
			'<div class="venta-product-footer">'+
				'<div class="venta-product-stock">'+fila[4]+'</div>'+
				'<div class="venta-product-action">'+fila[5]+'</div>'+
			'</div>'+
			'<div class="venta-product-hint"><i class="fa fa-search-plus"></i> Ver detalle</div>'+
		'</div>';
	}).join("");

	$(".productosCardsVenta").html(html);
	$(".productosVentaInfo").text("Mostrando "+(inicio + 1)+"-"+(inicio + pagina.length)+" de "+productosFiltrados.length+" productos"+($("#cantidadProductoVentaCards").val() === "auto" ? " (auto)" : ""));
	$(".productosVentaPaginas").html(generarPaginacionProductosVenta(totalPaginas));
	$(".productosVentaPaginacion button[data-page='prev']").prop("disabled", paginaProductosVenta <= 1);
	$(".productosVentaPaginacion button[data-page='next']").prop("disabled", paginaProductosVenta >= totalPaginas);
	sincronizarBotonesProductosVenta();
}

function generarPaginacionProductosVenta(totalPaginas){
	var paginas = [];
	var inicioVentana = Math.max(1, paginaProductosVenta - 2);
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
			return '<span class="productosVentaPuntos">...</span>';
		}
		return '<button type="button" class="productosVentaPagina '+(pagina == paginaProductosVenta ? 'active' : '')+'" data-page-number="'+pagina+'">'+pagina+'</button>';
	}).join("");
}

function inicializarProductosVentaCards(){
	if(!$(".productosCardsVenta").length){
		return;
	}

	$.getJSON("ajax/datatable-ventas.ajax.php", function(respuesta){
		productosVentaCards = respuesta.data || [];
		paginaProductosVenta = 1;
		renderProductosVentaCards();
	}).fail(function(){
		$(".productosCardsVenta").html('<div class="venta-products-empty">No se pudieron cargar los productos.</div>');
		$(".productosVentaInfo").text("Error al cargar productos");
	});
}

$(document).on("input", "#buscarProductoVentaCards", function(){
	paginaProductosVenta = 1;
	renderProductosVentaCards();
});

$(document).on("change", "#cantidadProductoVentaCards", function(){
	paginaProductosVenta = 1;
	renderProductosVentaCards();
});

$(window).on("resize", function(){
	if(!$(".productosCardsVenta").length || $("#cantidadProductoVentaCards").val() !== "auto"){
		return;
	}

	clearTimeout(resizeProductosVentaTimer);
	resizeProductosVentaTimer = setTimeout(function(){
		paginaProductosVenta = 1;
		renderProductosVentaCards();
	}, 160);
});

$(document).on("click", ".productosVentaPaginacion button", function(){
	var accion = $(this).attr("data-page");

	if($(this).attr("data-page-number")){
		paginaProductosVenta = parseInt($(this).attr("data-page-number"), 10) || 1;
		renderProductosVentaCards();
		return;
	}

	paginaProductosVenta += (accion == "next") ? 1 : -1;
	renderProductosVentaCards();
});

inicializarProductosVentaCards();
mostrarPasoVenta(1);

$(document).on("click", ".venta-product-card", function(event){
	if($(event.target).closest("button, a, .btn").length){
		return;
	}

	var indice = parseInt($(this).attr("data-product-index"), 10);
	abrirModalDetalleProductoVenta(productosVentaCards[indice]);
});

/*=============================================
AGREGANDO PRODUCTOS A LA VENTA DESDE LA TABLA
=============================================*/

$(document).on("click", ".productosCardsVenta button.agregarProducto, #modalDetalleProductoVenta button.agregarProducto, .tablaVentas tbody button.agregarProducto", function(){

	var idProducto = $(this).attr("idProducto");
	var botonAgregar = $(this);

	botonAgregar.removeClass("btn-primary agregarProducto");

	botonAgregar.addClass("btn-default");

	var datos = new FormData();
    datos.append("idProducto", idProducto);

     $.ajax({

     	url:"ajax/productos.ajax.php",
      	method: "POST",
      	data: datos,
      	cache: false,
      	contentType: false,
      	processData: false,
      	dataType:"json",
      	success:function(respuesta){

      	    var descripcion = respuesta["descripcion"];
          	var stock = respuesta["stock"];
          	var precio = respuesta["precio_venta"];

          	/*=============================================
          	EVITAR AGREGAR PRODUTO CUANDO EL STOCK ESTÁ EN CERO
          	=============================================*/

          	if(Number(stock) <= 0){

      			swal({
			      title: "No hay stock disponible",
			      text: "Este producto esta visible para consulta, pero no se puede agregar a la venta porque no tiene stock.",
			      type: "error",
			      confirmButtonText: "¡Cerrar!"
			    });

			    $("button[idProducto='"+idProducto+"']").removeClass("btn-default").addClass("btn-primary agregarProducto");

			    return;

          	}

          	cerrarDetalleProductoVentaAntes(function(){

          	pedirCantidadProductoVenta(descripcion, stock, precio).then(function(cantidadSeleccionada){

          		if(!cantidadSeleccionada){
          			$("button[idProducto='"+idProducto+"']").removeClass("btn-default").addClass("btn-primary agregarProducto");
          			return;
          		}

          		var precioFinal = Number(precio) * Number(cantidadSeleccionada);
          		var nuevoStock = Number(stock) - Number(cantidadSeleccionada);

          		$(".nuevoProducto").append(

          	'<div class="row" style="padding:5px 15px">'+

			  '<!-- Descripción del producto -->'+
	          
	          '<div class="col-xs-5" style="padding-right:0px">'+
	          
	            '<div class="input-group">'+
	              
	              '<span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarProducto" idProducto="'+idProducto+'"><i class="fa fa-times"></i></button></span>'+

	              '<input type="text" class="form-control nuevaDescripcionProducto" idProducto="'+idProducto+'" name="agregarProducto" value="'+descripcion+'" readonly required>'+

	            '</div>'+

	          '</div>'+

	          '<!-- Cantidad del producto -->'+

	          '<div class="col-xs-4 ingresoCantidad">'+
	             '<div class="input-group input-group-sm controlCantidad">'+
	               '<span class="input-group-btn"><button type="button" class="btn btn-default btnCantidadMenos"><i class="fa fa-minus"></i></button></span>'+
	               '<input type="number" class="form-control text-center nuevaCantidadProducto" style="min-width:46px;padding-left:4px;padding-right:4px" name="nuevaCantidadProducto" min="1" value="'+cantidadSeleccionada+'" stock="'+stock+'" nuevoStock="'+nuevoStock+'" required>'+
	               '<span class="input-group-btn"><button type="button" class="btn btn-default btnCantidadMas"><i class="fa fa-plus"></i></button></span>'+
	             '</div>'+
	          '</div>' +

	          '<!-- Precio del producto -->'+

	          '<div class="col-xs-3 ingresoPrecio" style="padding-left:0px">'+

	            '<div class="input-group">'+

				'<span class="input-group-addon"><i><b>Bs</b></i></span>'+
	                 
	              '<input type="text" class="form-control nuevoPrecioProducto" precioReal="'+precio+'" name="nuevoPrecioProducto" value="'+precioFinal+'" readonly required>'+
	 
	            '</div>'+
	             
	          '</div>'+

	        '</div>') 

	        // SUMAR TOTAL DE PRECIOS

	        sumarTotalPrecios()

	        // AGREGAR IMPUESTO

	        agregarImpuesto()

	        // AGRUPAR PRODUCTOS EN FORMATO JSON

	        listarProductos()

	        // PONER FORMATO AL PRECIO DE LOS PRODUCTOS

	        $(".nuevoPrecioProducto").number(true, 2);


			localStorage.removeItem("quitarProducto");
			sincronizarBotonesProductosVenta();
			$("#modalDetalleProductoVenta").modal("hide");
			mostrarProductoAgregadoVenta(descripcion, cantidadSeleccionada);

          	});

          	});

      	}

     })

});

/*=============================================
CUANDO CARGUE LA TABLA CADA VEZ QUE NAVEGUE EN ELLA
=============================================*/

$(".tablaVentas").on("draw.dt", function(){

	if(localStorage.getItem("quitarProducto") != null){

		var listaIdProductos = JSON.parse(localStorage.getItem("quitarProducto"));

		for(var i = 0; i < listaIdProductos.length; i++){

			$("button.recuperarBoton[idProducto='"+listaIdProductos[i]["idProducto"]+"']").removeClass('btn-default');
			$("button.recuperarBoton[idProducto='"+listaIdProductos[i]["idProducto"]+"']").addClass('btn-primary agregarProducto');

		}


	}


})


/*=============================================
QUITAR PRODUCTOS DE LA VENTA Y RECUPERAR BOTÓN
=============================================*/

var idQuitarProducto = [];

localStorage.removeItem("quitarProducto");

$(".formularioVenta").on("click", "button.quitarProducto", function(){

	$(this).parent().parent().parent().parent().remove();

	var idProducto = $(this).attr("idProducto");

	/*=============================================
	ALMACENAR EN EL LOCALSTORAGE EL ID DEL PRODUCTO A QUITAR
	=============================================*/

	if(localStorage.getItem("quitarProducto") == null){

		idQuitarProducto = [];
	
	}else{

		idQuitarProducto.concat(localStorage.getItem("quitarProducto"))

	}

	idQuitarProducto.push({"idProducto":idProducto});

	localStorage.setItem("quitarProducto", JSON.stringify(idQuitarProducto));

	$("button.recuperarBoton[idProducto='"+idProducto+"']").removeClass('btn-default');

	$("button.recuperarBoton[idProducto='"+idProducto+"']").addClass('btn-primary agregarProducto');
	sincronizarBotonesProductosVenta();

	if($(".nuevoProducto").children().length == 0){

		$("#nuevoImpuestoVenta").val(0);
		$("#nuevoTotalVenta").val(0);
		$("#totalVenta").val(0);
		$("#nuevoTotalVenta").attr("total",0);

	}else{

		// SUMAR TOTAL DE PRECIOS

    	sumarTotalPrecios()

    	// AGREGAR IMPUESTO
	        
        agregarImpuesto()

        // AGRUPAR PRODUCTOS EN FORMATO JSON

        listarProductos()

	}

})

/*=============================================
AGREGANDO PRODUCTOS DESDE EL BOTÓN PARA DISPOSITIVOS
=============================================*/

var numProducto = 0;

$(".btnAgregarProducto").click(function(){

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
	          
	          '<div class="col-xs-5" style="padding-right:0px">'+
	          
	            '<div class="input-group">'+
	              
	              '<span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarProducto" idProducto><i class="fa fa-times"></i></button></span>'+

	              '<select class="form-control nuevaDescripcionProducto" id="producto'+numProducto+'" idProducto name="nuevaDescripcionProducto" required>'+

	              '<option>Seleccione el producto</option>'+

	              '</select>'+  

	            '</div>'+

	          '</div>'+

	          '<!-- Cantidad del producto -->'+

	          '<div class="col-xs-4 ingresoCantidad">'+
	             '<div class="input-group input-group-sm controlCantidad">'+
	               '<span class="input-group-btn"><button type="button" class="btn btn-default btnCantidadMenos"><i class="fa fa-minus"></i></button></span>'+
	               '<input type="number" class="form-control text-center nuevaCantidadProducto" style="min-width:46px;padding-left:4px;padding-right:4px" name="nuevaCantidadProducto" min="1" value="0" stock nuevoStock required>'+
	               '<span class="input-group-btn"><button type="button" class="btn btn-default btnCantidadMas"><i class="fa fa-plus"></i></button></span>'+
	             '</div>'+
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

	         	if(item.stock != 0){

		         	$("#producto"+numProducto).append(

						'<option idProducto="'+item.id+'" value="'+item.descripcion+'">'+item.descripcion+'</option>'
		         	)

		         
		         }

		         

	         }

        	 // SUMAR TOTAL DE PRECIOS

    		sumarTotalPrecios()

    		// AGREGAR IMPUESTO
	        
	        agregarImpuesto()

	        // PONER FORMATO AL PRECIO DE LOS PRODUCTOS

	        $(".nuevoPrecioProducto").number(true, 2);


      	}

	})

})

/*=============================================
SELECCIONAR PRODUCTO
=============================================*/

$(".formularioVenta").on("change", "select.nuevaDescripcionProducto", function(){

	var nombreProducto = $(this).val();

	var nuevaDescripcionProducto = $(this).parent().parent().parent().children().children().children(".nuevaDescripcionProducto");

	var nuevoPrecioProducto = $(this).parent().parent().parent().children(".ingresoPrecio").children().children(".nuevoPrecioProducto");

	var nuevaCantidadProducto = $(this).closest(".row").children(".ingresoCantidad").find(".nuevaCantidadProducto");

	var datos = new FormData();
    datos.append("nombreProducto", nombreProducto);


	  $.ajax({

     	url:"ajax/productos.ajax.php",
      	method: "POST",
      	data: datos,
      	cache: false,
      	contentType: false,
      	processData: false,
      	dataType:"json",
      	success:function(respuesta){
      	    
      	     $(nuevaDescripcionProducto).attr("idProducto", respuesta["id"]);
      	    $(nuevaCantidadProducto).attr("stock", respuesta["stock"]);
      	    $(nuevaCantidadProducto).attr("nuevoStock", Number(respuesta["stock"])-1);
      	    $(nuevoPrecioProducto).val(respuesta["precio_venta"]);
      	    $(nuevoPrecioProducto).attr("precioReal", respuesta["precio_venta"]);

  	      // AGRUPAR PRODUCTOS EN FORMATO JSON

	        listarProductos()

      	}

      })
})

/*=============================================
MODIFICAR LA CANTIDAD
=============================================*/

$(".formularioVenta").on("change", "input.nuevaCantidadProducto", function(){

	var precio = $(this).closest(".row").children(".ingresoPrecio").children().children(".nuevoPrecioProducto");

	var precioFinal = $(this).val() * precio.attr("precioReal");
	
	precio.val(precioFinal);

	var nuevoStock = Number($(this).attr("stock")) - $(this).val();

	$(this).attr("nuevoStock", nuevoStock);

	if(Number($(this).val()) > Number($(this).attr("stock"))){

		/*=============================================
		SI LA CANTIDAD ES SUPERIOR AL STOCK REGRESAR VALORES INICIALES
		=============================================*/

		$(this).val(0);

		$(this).attr("nuevoStock", $(this).attr("stock"));

		var precioFinal = $(this).val() * precio.attr("precioReal");

		precio.val(precioFinal);

		sumarTotalPrecios();

		swal({
	      title: "La cantidad supera el Stock",
	      text: "¡Sólo hay "+$(this).attr("stock")+" unidades!",
	      type: "error",
	      confirmButtonText: "¡Cerrar!"
	    });

	    return;

	}

	// SUMAR TOTAL DE PRECIOS

	sumarTotalPrecios()

	// AGREGAR IMPUESTO
	        
    agregarImpuesto()

    // AGRUPAR PRODUCTOS EN FORMATO JSON

    listarProductos()

})

$(".formularioVenta").on("click", ".btnCantidadMas", function(){
	var inputCantidad = $(this).closest(".controlCantidad").find(".nuevaCantidadProducto");
	var stock = Number(inputCantidad.attr("stock"));
	var cantidadActual = Number(inputCantidad.val()) || 0;

	if(cantidadActual < stock){
		inputCantidad.val(cantidadActual + 1).trigger("change");
	}
})

$(".formularioVenta").on("click", ".btnCantidadMenos", function(){
	var inputCantidad = $(this).closest(".controlCantidad").find(".nuevaCantidadProducto");
	var cantidadActual = Number(inputCantidad.val()) || 0;

	if(cantidadActual > 1){
		inputCantidad.val(cantidadActual - 1).trigger("change");
	}
})

/*=============================================
SUMAR TODOS LOS PRECIOS
=============================================*/

function sumarTotalPrecios(){

	var precioItem = $(".nuevoPrecioProducto");
	
	var arraySumaPrecio = [];  

	for(var i = 0; i < precioItem.length; i++){

		 arraySumaPrecio.push(Number($(precioItem[i]).val()));
		
		 
	}

	function sumaArrayPrecios(total, numero){

		return total + numero;

	}

	var sumaTotalPrecio = arraySumaPrecio.reduce(sumaArrayPrecios);
	
	$("#nuevoTotalVenta").val(sumaTotalPrecio);
	$("#totalVenta").val(sumaTotalPrecio);
	$("#nuevoTotalVenta").attr("total",sumaTotalPrecio);


}

/*=============================================
FUNCIÓN AGREGAR IMPUESTO
=============================================*/

function agregarImpuesto(){

	var impuesto = $("#nuevoImpuestoVenta").val();
	var precioTotal = $("#nuevoTotalVenta").attr("total");

	var precioImpuesto = Number(precioTotal * impuesto/100);

	var totalConImpuesto = Number(precioTotal) - Number(precioImpuesto);
	
	$("#nuevoTotalVenta").val(totalConImpuesto);

	$("#totalVenta").val(totalConImpuesto);

	$("#nuevoPrecioImpuesto").val(precioImpuesto);

	$("#nuevoPrecioNeto").val(precioTotal);

}

/*=============================================
CUANDO CAMBIA EL IMPUESTO
=============================================*/

$("#nuevoImpuestoVenta").change(function(){

	agregarImpuesto();

});

/*=============================================
FORMATO AL PRECIO FINAL
=============================================*/

$("#nuevoTotalVenta").number(true, 2);

/*=============================================
SELECCIONAR MÉTODO DE PAGO
=============================================*/

$("#nuevoMetodoPago").change(function(){

	var metodo = $(this).val();

	if(metodo == "Efectivo"){

		$(this).parent().parent().removeClass("col-xs-6");

		$(this).parent().parent().addClass("col-xs-4");

		$(this).parent().parent().parent().children(".cajasMetodoPago").html(

			 '<div class="col-xs-4">'+ 

			 	'<div class="input-group">'+ 

				 '<span class="input-group-addon"><i><b>Bs</b></i></span>'+

			 		'<input type="text" class="form-control" id="nuevoValorEfectivo" placeholder="000000" required>'+

			 	'</div>'+

			 '</div>'+

			 '<div class="col-xs-4" id="capturarCambioEfectivo" style="padding-left:0px">'+

			 	'<div class="input-group">'+

				 '<span class="input-group-addon"><i><b>Bs</b></i></span>'+

			 		'<input type="text" class="form-control" id="nuevoCambioEfectivo" placeholder="000000" readonly required>'+

			 	'</div>'+

			 '</div>'

		 )

		// Agregar formato al precio

		$('#nuevoValorEfectivo').number( true, 2);
      	$('#nuevoCambioEfectivo').number( true, 2);


      	// Listar método en la entrada
      	listarMetodos()

	}else{

		$(this).parent().parent().removeClass('col-xs-4');

		$(this).parent().parent().addClass('col-xs-6');

		 $(this).parent().parent().parent().children('.cajasMetodoPago').html(

		 	'<div class="col-xs-6" style="padding-left:0px">'+
                        
                '<div class="input-group">'+
                     
                  '<input type="number" min="0" class="form-control" id="nuevoCodigoTransaccion" placeholder="Código transacción"  required>'+
                       
                  '<span class="input-group-addon"><i class="fa fa-lock"></i></span>'+
                  
                '</div>'+

              '</div>')

	}

	

})

/*=============================================
CAMBIO EN EFECTIVO
=============================================*/
$(".formularioVenta").on("change", "input#nuevoValorEfectivo", function(){

	var efectivo = $(this).val();

	var cambio =  Number(efectivo) - Number($('#nuevoTotalVenta').val());

	var nuevoCambioEfectivo = $(this).parent().parent().parent().children('#capturarCambioEfectivo').children().children('#nuevoCambioEfectivo');

	nuevoCambioEfectivo.val(cambio);

})

/*=============================================
CAMBIO TRANSACCIÓN
=============================================*/
$(".formularioVenta").on("change", "input#nuevoCodigoTransaccion", function(){

	// Listar método en la entrada
     listarMetodos()


})


/*=============================================
LISTAR TODOS LOS PRODUCTOS
=============================================*/

function listarProductos(){

	var listaProductos = [];

	var descripcion = $(".nuevaDescripcionProducto");

	var cantidad = $(".nuevaCantidadProducto");

	var precio = $(".nuevoPrecioProducto");

	for(var i = 0; i < descripcion.length; i++){

		listaProductos.push({ "id" : $(descripcion[i]).attr("idProducto"), 
							  "descripcion" : $(descripcion[i]).val(),
							  "cantidad" : $(cantidad[i]).val(),
							  "stock" : $(cantidad[i]).attr("nuevoStock"),
							  "precio" : $(precio[i]).attr("precioReal"),
							  "total" : $(precio[i]).val()})

	}

	$("#listaProductos").val(JSON.stringify(listaProductos)); 

}

/*=============================================
LISTAR MÉTODO DE PAGO
=============================================*/

function listarMetodos(){

	var listaMetodos = "";

	if($("#nuevoMetodoPago").val() == "Efectivo"){

		$("#listaMetodoPago").val("Efectivo");

	}else{

		$("#listaMetodoPago").val($("#nuevoMetodoPago").val()+"-"+$("#nuevoCodigoTransaccion").val());

	}

}

/*=============================================
FUNCIÓN PARA DESACTIVAR LOS BOTONES AGREGAR CUANDO EL PRODUCTO YA HABÍA SIDO SELECCIONADO EN LA CARPETA
=============================================*/

function quitarAgregarProducto(){

	//Capturamos todos los id de productos que fueron elegidos en la venta
	var idProductos = $(".quitarProducto");

	//Capturamos todos los botones de agregar que aparecen en la tabla
	var botonesTabla = $(".tablaVentas tbody button.agregarProducto");

	//Recorremos en un ciclo para obtener los diferentes idProductos que fueron agregados a la venta
	for(var i = 0; i < idProductos.length; i++){

		//Capturamos los Id de los productos agregados a la venta
		var boton = $(idProductos[i]).attr("idProducto");
		
		//Hacemos un recorrido por la tabla que aparece para desactivar los botones de agregar
		for(var j = 0; j < botonesTabla.length; j ++){

			if($(botonesTabla[j]).attr("idProducto") == boton){

				$(botonesTabla[j]).removeClass("btn-primary agregarProducto");
				$(botonesTabla[j]).addClass("btn-default");

			}
		}

	}
	
}

/*=============================================
CADA VEZ QUE CARGUE LA TABLA CUANDO NAVEGAMOS EN ELLA EJECUTAR LA FUNCIÓN:
=============================================*/

$('.tablaVentas').on( 'draw.dt', function(){

	quitarAgregarProducto();

})


/*=============================================
BORRAR VENTA
=============================================*/
$(document).on("click", ".btnEliminarVenta", function(e){

  e.preventDefault();
  e.stopPropagation();
  e.stopImmediatePropagation();

  var botonEliminar = $(this);
  var idVenta = botonEliminar.attr("idVenta") || botonEliminar.attr("idventa") || "";
  var codigoVenta = botonEliminar.attr("codigoVenta") || botonEliminar.attr("codigoventa") || idVenta;
  var estadoVenta = botonEliminar.attr("estadoVenta") || botonEliminar.attr("estadoventa") || "";

  if(!idVenta){
    swal({
      type:"error",
      title:"No se pudo identificar la venta",
      confirmButtonText:"Cerrar"
    });
    return;
  }

  swal({
        title: '¿Eliminar la venta #'+codigoVenta+'?',
        html: '<p>Esta acción eliminará la venta'+(estadoVenta ? ' en estado <b>'+estadoVenta+'</b>' : '')+'.</p>'+
              '<p>El sistema devolverá el stock, liberará los códigos únicos despachados y actualizará las compras del cliente.</p>'+
              '<p><b>Esta operación no se puede deshacer.</b></p>',
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, eliminar venta'
      }).then(function(result){
        if (result.value) {
            botonEliminar.prop("disabled", true);
            window.location = "index.php?ruta=ventas&idVenta="+encodeURIComponent(idVenta);
        }

  })

})

/*=============================================
IMPRIMIR FACTURA
=============================================*/

$(document).on("click", ".btnImprimirFactura", function(){

	var idVenta = $(this).attr("idVenta");
	var codigoVenta = $(this).attr("codigoVenta");

	window.open("extensiones/tcpdf/pdf/factura.php?idVenta="+idVenta+"&codigo="+encodeURIComponent(codigoVenta), "_blank");

})

$(document).on("click", ".btnImprimirBoletaCaja", function(){
	var idVenta = $(this).attr("idVenta");
	var codigoVenta = $(this).attr("codigoVenta");
	window.open("extensiones/tcpdf/pdf/boleta-caja.php?idVenta="+idVenta+"&codigo="+encodeURIComponent(codigoVenta), "_blank");
})

$(document).on("click", ".btnImprimirBoletaDespacho", function(){
	var idVenta = $(this).attr("idVenta");
	var codigoVenta = $(this).attr("codigoVenta");
	window.open("extensiones/tcpdf/pdf/boleta-despacho.php?idVenta="+idVenta+"&codigo="+encodeURIComponent(codigoVenta), "_blank");
})

$(document).on("click", ".btnImprimirControlEntrega", function(){
	var idVenta = $(this).attr("idVenta");
	window.open("extensiones/tcpdf/pdf/conformidad.php?idVenta="+idVenta, "_blank");
})

/*=============================================
RANGO DE FECHAS
=============================================*/

$('#daterange-btn').daterangepicker(
  {
    ranges   : {
      'Hoy'       : [moment(), moment()],
      'Ayer'   : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
      'Últimos 7 días' : [moment().subtract(6, 'days'), moment()],
      'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
      'Este mes'  : [moment().startOf('month'), moment().endOf('month')],
      'Último mes'  : [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    },
    startDate: moment(),
    endDate  : moment()
  },
  function (start, end) {
    $('#daterange-btn span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));

    var fechaInicial = start.format('YYYY-MM-DD');

    var fechaFinal = end.format('YYYY-MM-DD');

    var capturarRango = $("#daterange-btn span").html();
   
   	localStorage.setItem("capturarRango", capturarRango);

   	window.location = "index.php?ruta=ventas&fechaInicial="+fechaInicial+"&fechaFinal="+fechaFinal;

  }

)

/*=============================================
CANCELAR RANGO DE FECHAS
=============================================*/

$(".daterangepicker.opensleft .range_inputs .cancelBtn").on("click", function(){

	localStorage.removeItem("capturarRango");
	localStorage.clear();
	window.location = "ventas";
})

/*=============================================
CAPTURAR HOY
=============================================*/

$(".daterangepicker.opensleft .ranges li").on("click", function(){

	var textoHoy = $(this).attr("data-range-key");

	if(textoHoy == "Hoy"){

		var d = new Date();
		
		var dia = d.getDate();
		var mes = d.getMonth()+1;
		var anio = d.getFullYear();

		if(mes < 10){

			var fechaInicial = anio+"-0"+mes+"-"+dia;
			var fechaFinal = anio+"-0"+mes+"-"+dia;

		}else if(dia < 10){

			var fechaInicial = anio+"-"+mes+"-0"+dia;
			var fechaFinal = anio+"-"+mes+"-0"+dia;

		}else if(mes < 10 && dia < 10){

			var fechaInicial = anio+"-0"+mes+"-0"+dia;
			var fechaFinal = anio+"-0"+mes+"-0"+dia;

		}else{

			var fechaInicial = anio+"-"+mes+"-"+dia;
	    	var fechaFinal = anio+"-"+mes+"-"+dia;

		}	

    	localStorage.setItem("capturarRango", "Hoy");

    	window.location = "index.php?ruta=ventas&fechaInicial="+fechaInicial+"&fechaFinal="+fechaFinal;

	}

})




