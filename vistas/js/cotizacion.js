/*=============================================
CARGAR LA TABLA DINÁMICA DE VENTAS
=============================================*/

// $.ajax({

// 	url: "ajax/datatable-ventas.ajax.php",
// 	success:function(respuesta){
		
// 		console.log("respuesta", respuesta);

// 	}

// })// 

if($('.tablaCotizacion').length){
$('.tablaCotizacion').DataTable( {
    "ajax": "ajax/datatable-cotizacion.ajax.php",
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

var productosCotizacionCards = [];
var paginaProductosCotizacion = 1;
var resizeProductosCotizacionTimer = null;
var pasoCotizacionActual = 1;
var totalPasosCotizacion = 5;

function textoPlanoCotizacion(html){
	return $("<div>").html(html || "").text().replace(/\s+/g, " ").trim();
}

function avisoPasoCotizacion(titulo, texto){
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

function mostrarProductoAgregadoCotizacion(descripcion, cantidad){
	if(typeof swal === "function"){
		swal({
			type: "success",
			title: "Producto agregado",
			text: cantidad+" unidad(es) de "+descripcion+" agregadas a la cotizacion.",
			confirmButtonText: "Continuar"
		});
		return;
	}

	alert("Producto agregado\n"+cantidad+" unidad(es) de "+descripcion+" agregadas a la cotizacion.");
}

function formatoPrecioCotizacion(valor){
	var numero = Number(valor) || 0;
	return "Bs " + numero.toLocaleString("es-BO", {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2
	});
}

function escaparHtmlCotizacion(valor){
	return $("<div>").text(valor || "").html();
}

function activarTotalCantidadCotizacion(precio){
	var precioUnitario = Number(precio) || 0;
	var input = $(".swal2-container input.swal2-input");

	function actualizar(){
		var cantidad = parseInt(input.val(), 10) || 0;
		$(".js-total-cantidad-cotizacion").text(formatoPrecioCotizacion(cantidad * precioUnitario));
	}

	input.off("input.tmCantidadCotizacion change.tmCantidadCotizacion")
		.on("input.tmCantidadCotizacion change.tmCantidadCotizacion", actualizar);

	actualizar();
}

function pedirCantidadProductoCotizacion(descripcion, stock, precio){
	stock = Number(stock) || 0;
	precio = Number(precio) || 0;
	var textoStock = stock > 0 ? "Stock actual: "+stock+" unidad(es)" : "Sin stock actual, se cotizara para reposicion o compra.";

	if(typeof swal !== "function"){
		var cantidadPrompt = parseInt(prompt("Cantidad para "+descripcion+" (precio: "+formatoPrecioCotizacion(precio)+")", "1"), 10);
		if(!cantidadPrompt || cantidadPrompt < 1){
			return Promise.resolve(null);
		}
		return Promise.resolve(cantidadPrompt);
	}

	return swal({
		title: "Cantidad a cotizar",
		html: '<div class="swal-producto-cantidad">'+
				'<strong>'+escaparHtmlCotizacion(descripcion)+'</strong>'+
				'<span>'+textoStock+'</span>'+
				'<div style="margin-top:10px;display:grid;grid-template-columns:1fr 1fr;gap:8px;text-align:left">'+
					'<div style="padding:9px 10px;border:1px solid #dbeafe;border-radius:8px;background:#f8fbff">'+
						'<small style="display:block;color:#64748b;font-weight:700;text-transform:uppercase">Precio unitario</small>'+
						'<b>'+formatoPrecioCotizacion(precio)+'</b>'+
					'</div>'+
					'<div style="padding:9px 10px;border:1px solid #bfdbfe;border-radius:8px;background:#eff6ff">'+
						'<small style="display:block;color:#1d4ed8;font-weight:700;text-transform:uppercase">Total</small>'+
						'<b class="js-total-cantidad-cotizacion">'+formatoPrecioCotizacion(precio)+'</b>'+
					'</div>'+
				'</div>'+
			'</div>',
		input: "number",
		inputValue: 1,
		inputAttributes: {
			min: 1,
			step: 1
		},
		showCancelButton: true,
		confirmButtonText: "Agregar",
		cancelButtonText: "Cancelar",
		onOpen: function(){
			activarTotalCantidadCotizacion(precio);
		},
		didOpen: function(){
			activarTotalCantidadCotizacion(precio);
		},
		preConfirm: function(valor){
			var cantidad = parseInt(valor, 10);
			if(!cantidad || cantidad < 1){
				swal.showValidationMessage("Ingrese una cantidad mayor a 0.");
				return false;
			}
			return cantidad;
		}
	}).then(function(resultado){
		return resultado && resultado.value ? Number(resultado.value) : null;
	});
}

function hayClienteCotizacionSeleccionado(){
	return ($("#seleccionarCliente").val() || "") !== "";
}

function hayProductosCotizacionSeleccionados(){
	return $(".formularioCotizacion .nuevoProducto .nuevaDescripcionProducto").length > 0;
}

function validarPasoCotizacionDestino(destino){
	if(destino <= 1){
		return true;
	}

	if(!hayClienteCotizacionSeleccionado()){
		avisoPasoCotizacion("Seleccione un cliente", "Primero debe escoger o registrar el cliente de la cotizacion.");
		return false;
	}

	if(destino >= 4 && !hayProductosCotizacionSeleccionados()){
		avisoPasoCotizacion("Agregue productos", "Para ajustar condiciones y revisar el resumen debe agregar al menos un producto.");
		return false;
	}

	return true;
}

function mostrarPasoCotizacion(paso){
	if(!$(".cotizacion-step[data-cotizacion-step]").length){
		return;
	}

	paso = Math.max(1, Math.min(totalPasosCotizacion, parseInt(paso, 10) || 1));

	if(!validarPasoCotizacionDestino(paso)){
		return;
	}

	pasoCotizacionActual = paso;
	$(".cotizacion-step[data-cotizacion-step]").addClass("cotizacion-step-hidden");
	$(".cotizacion-step[data-cotizacion-step='"+pasoCotizacionActual+"']").removeClass("cotizacion-step-hidden");

	$(".cotizacion-wizard-dot").removeClass("active done").each(function(){
		var pasoBoton = parseInt($(this).attr("data-cotizacion-step-target"), 10) || 1;
		if(pasoBoton < pasoCotizacionActual){
			$(this).addClass("done");
		}
		if(pasoBoton === pasoCotizacionActual){
			$(this).addClass("active");
		}
	});

	$(".btnCotizacionPasoAnterior").prop("disabled", pasoCotizacionActual <= 1);
	$(".btnCotizacionPasoSiguiente").toggle(pasoCotizacionActual < totalPasosCotizacion);
	$(".cotizacion-wizard-help").text(
		pasoCotizacionActual === 1 ? "Seleccione cliente y fecha de validez." :
		pasoCotizacionActual === 2 ? "Busque y agregue productos a la cotizacion." :
		pasoCotizacionActual === 3 ? "Revise cantidades y precios antes de continuar." :
		pasoCotizacionActual === 4 ? "Ajuste las condiciones que saldran en la boleta." :
		"Revise el total y guarde la cotizacion."
	);

	if(pasoCotizacionActual === 2){
		renderProductosCotizacionCards();
	}
}

$(document).on("click", ".btnCotizacionPasoSiguiente", function(){
	mostrarPasoCotizacion(pasoCotizacionActual + 1);
});

$(document).on("click", ".btnCotizacionPasoAnterior", function(){
	mostrarPasoCotizacion(pasoCotizacionActual - 1);
});

$(document).on("click", ".cotizacion-wizard-dot", function(){
	var destino = parseInt($(this).attr("data-cotizacion-step-target"), 10) || 1;
	mostrarPasoCotizacion(destino);
});

$(document).on("change", "#seleccionarCliente", function(){
	if($(".cotizacion-step[data-cotizacion-step]").length){
		mostrarPasoCotizacion(pasoCotizacionActual);
	}
});

function sincronizarBotonesProductosCotizacion(){
	var idsSeleccionados = [];

	$(".nuevoProducto .nuevaDescripcionProducto").each(function(){
		idsSeleccionados.push($(this).attr("idProducto"));
	});

	$(".productosCardsCotizacion button.recuperarBoton").each(function(){
		var idBoton = $(this).attr("idProducto");

		if(idsSeleccionados.indexOf(idBoton) !== -1){
			$(this).removeClass("btn-primary btn-warning agregarProducto").addClass("btn-default");
		}
	});
}

function generarPaginacionProductosCotizacion(totalPaginas){
	var paginas = [];
	var inicioVentana = Math.max(1, paginaProductosCotizacion - 2);
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
			return '<span class="productosCotizacionPuntos">...</span>';
		}
		return '<button type="button" class="productosCotizacionPagina '+(pagina == paginaProductosCotizacion ? 'active' : '')+'" data-page-number="'+pagina+'">'+pagina+'</button>';
	}).join("");
}

function obtenerCantidadProductosCotizacionCards(){
	var valor = $("#cantidadProductoCotizacionCards").val();

	if(valor !== "auto"){
		return parseInt(valor, 10) || 12;
	}

	var grilla = document.querySelector(".productosCardsCotizacion");
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

function renderProductosCotizacionCards(){
	if(!$(".productosCardsCotizacion").length){
		return;
	}

	var busqueda = ($("#buscarProductoCotizacionCards").val() || "").toLowerCase().trim();
	var porPagina = obtenerCantidadProductosCotizacionCards();
	var productosFiltrados = productosCotizacionCards.filter(function(fila){
		var texto = (textoPlanoCotizacion(fila[2]) + " " + textoPlanoCotizacion(fila[3])).toLowerCase();
		return texto.indexOf(busqueda) !== -1;
	});
	var totalPaginas = Math.max(1, Math.ceil(productosFiltrados.length / porPagina));

	if(paginaProductosCotizacion > totalPaginas){
		paginaProductosCotizacion = totalPaginas;
	}

	var inicio = (paginaProductosCotizacion - 1) * porPagina;
	var pagina = productosFiltrados.slice(inicio, inicio + porPagina);

	if(!pagina.length){
		$(".productosCardsCotizacion").html('<div class="cotizacion-products-empty">No se encontraron productos para la busqueda.</div>');
		$(".productosCotizacionInfo").text("Sin productos para mostrar");
		$(".productosCotizacionPaginas").html('<button type="button" class="productosCotizacionPagina active" data-page-number="1">1</button>');
		$(".productosCotizacionPaginacion button").prop("disabled", true);
		return;
	}

	var html = pagina.map(function(fila){
		return '<div class="cotizacion-product-card">'+
			'<div class="cotizacion-product-img">'+fila[1]+'</div>'+
			'<span class="cotizacion-product-code">'+textoPlanoCotizacion(fila[2])+'</span>'+
			'<h4>'+textoPlanoCotizacion(fila[3])+'</h4>'+
			'<div class="cotizacion-product-price">'+(fila[6] || '')+'</div>'+
			'<div class="cotizacion-product-footer">'+
				'<div class="cotizacion-product-stock">'+fila[4]+'</div>'+
				'<div class="cotizacion-product-action">'+fila[5]+'</div>'+
			'</div>'+
		'</div>';
	}).join("");

	$(".productosCardsCotizacion").html(html);
	$(".productosCotizacionInfo").text("Mostrando "+(inicio + 1)+"-"+(inicio + pagina.length)+" de "+productosFiltrados.length+" productos"+($("#cantidadProductoCotizacionCards").val() === "auto" ? " (auto)" : ""));
	$(".productosCotizacionPaginas").html(generarPaginacionProductosCotizacion(totalPaginas));
	$(".productosCotizacionPaginacion button[data-page='prev']").prop("disabled", paginaProductosCotizacion <= 1);
	$(".productosCotizacionPaginacion button[data-page='next']").prop("disabled", paginaProductosCotizacion >= totalPaginas);
	sincronizarBotonesProductosCotizacion();
}

function inicializarProductosCotizacionCards(){
	if(!$(".productosCardsCotizacion").length){
		return;
	}

	$.getJSON("ajax/datatable-cotizacion.ajax.php", function(respuesta){
		productosCotizacionCards = respuesta.data || [];
		paginaProductosCotizacion = 1;
		renderProductosCotizacionCards();
	}).fail(function(){
		$(".productosCardsCotizacion").html('<div class="cotizacion-products-empty">No se pudieron cargar los productos.</div>');
		$(".productosCotizacionInfo").text("Error al cargar productos");
	});
}

$(document).on("input", "#buscarProductoCotizacionCards", function(){
	paginaProductosCotizacion = 1;
	renderProductosCotizacionCards();
});

$(document).on("change", "#cantidadProductoCotizacionCards", function(){
	paginaProductosCotizacion = 1;
	renderProductosCotizacionCards();
});

$(window).on("resize", function(){
	if(!$(".productosCardsCotizacion").length || $("#cantidadProductoCotizacionCards").val() !== "auto"){
		return;
	}

	clearTimeout(resizeProductosCotizacionTimer);
	resizeProductosCotizacionTimer = setTimeout(function(){
		paginaProductosCotizacion = 1;
		renderProductosCotizacionCards();
	}, 160);
});

$(document).on("click", ".productosCotizacionPaginacion button", function(){
	var accion = $(this).attr("data-page");

	if($(this).attr("data-page-number")){
		paginaProductosCotizacion = parseInt($(this).attr("data-page-number"), 10) || 1;
		renderProductosCotizacionCards();
		return;
	}

	paginaProductosCotizacion += (accion == "next") ? 1 : -1;
	renderProductosCotizacionCards();
});

inicializarProductosCotizacionCards();
mostrarPasoCotizacion(1);

/*=============================================
AGREGANDO PRODUCTOS A LA VENTA DESDE LA TABLA
=============================================*/

$(document).on("click", ".productosCardsCotizacion button.agregarProducto, .tablaCotizacion tbody button.agregarProducto", function(e){

	e.preventDefault();

	var idProducto = $(this).attr("idProducto");
	var botonAgregar = $(this);

	botonAgregar.removeClass("btn-primary btn-warning agregarProducto");

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
          	var avisoStock = Number(stock) <= 0 ? '<div class="col-xs-12"><div class="alert alert-warning" style="padding:6px 10px;margin:6px 0 0">Producto sin stock actual. Se puede cotizar para gestionar compra o reposicion.</div></div>' : '';
          	var avisoPrecio = Number(precio) <= 0 ? '<div class="col-xs-12"><div class="alert alert-info" style="padding:6px 10px;margin:6px 0 0">Producto sin precio de venta registrado. Revisar precio antes de entregar la cotizacion final.</div></div>' : '';

          	/*=============================================
          	EVITAR AGREGAR PRODUTO CUANDO EL STOCK ESTÁ EN CERO
          	=============================================*/

          	if(false && stock == 0){

      			swal({
			      title: "No hay stock disponible",
			      type: "error",
			      confirmButtonText: "¡Cerrar!"
			    });

			    $("button[idProducto='"+idProducto+"']").addClass("btn-primary agregarProducto");

			    return;

          	}

          	pedirCantidadProductoCotizacion(descripcion, stock, precio).then(function(cantidadSeleccionada){

          		if(!cantidadSeleccionada){
          			var claseRestaurar = Number(stock) <= 0 ? "btn-warning" : "btn-primary";
          			$("button[idProducto='"+idProducto+"']").removeClass("btn-default").addClass(claseRestaurar+" agregarProducto");
          			return;
          		}

          		var precioFinal = Number(precio) * Number(cantidadSeleccionada);
          		var nuevoStock = Math.max(Number(stock) - Number(cantidadSeleccionada), 0);

          		$(".nuevoProducto").append(

          	'<div class="row" style="padding:5px 15px">'+

			  '<!-- Descripción del producto -->'+
	          
	          '<div class="col-xs-6" style="padding-right:0px">'+
	          
	            '<div class="input-group">'+
	              
	              '<span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarProducto" idProducto="'+idProducto+'"><i class="fa fa-times"></i></button></span>'+

	              '<input type="text" class="form-control nuevaDescripcionProducto" idProducto="'+idProducto+'" name="agregarProducto" value="'+descripcion+'" readonly required>'+

	            '</div>'+

	          '</div>'+

	          '<!-- Cantidad del producto -->'+

	          '<div class="col-xs-3">'+
	            
	             '<input type="number" class="form-control nuevaCantidadProducto" name="nuevaCantidadProducto" min="1" value="'+cantidadSeleccionada+'" stock="'+stock+'" nuevoStock="'+nuevoStock+'" data-permite-sin-stock="1" required>'+

	          '</div>' +

	          '<!-- Precio del producto -->'+

	          '<div class="col-xs-3 ingresoPrecio" style="padding-left:0px">'+

	            '<div class="input-group">'+

				'<span class="input-group-addon"><i><b>Bs</b></i></span>'+
	                 
	              '<input type="text" class="form-control nuevoPrecioProducto" precioReal="'+precio+'" name="nuevoPrecioProducto" value="'+precioFinal+'" readonly required>'+
	 
	            '</div>'+
	             
	          '</div>'+
	          avisoStock+
	          avisoPrecio+

	        '</div>') 

	        // SUMAR TOTAL DE PRECIOS

	        sumarTotalPreciosCo()

	        // AGREGAR IMPUESTO

	        agregarImpuestoCotizacion()

	        // AGRUPAR PRODUCTOS EN FORMATO JSON

	        listarProductosCotizacion()

	        // PONER FORMATO AL PRECIO DE LOS PRODUCTOS

	        $(".nuevoPrecioProducto").number(true, 2);


			localStorage.removeItem("quitarProducto");
			sincronizarBotonesProductosCotizacion();
			mostrarProductoAgregadoCotizacion(descripcion, cantidadSeleccionada);

          	});

      	}

     })

});

/*=============================================
CUANDO CARGUE LA TABLA CADA VEZ QUE NAVEGUE EN ELLA
=============================================*/

$(".tablaCotizacion").on("draw.dt", function(){

	if(localStorage.getItem("quitarProducto") != null){

		var listaIdProductos = JSON.parse(localStorage.getItem("quitarProducto"));

		for(var i = 0; i < listaIdProductos.length; i++){

			$("button.recuperarBoton[idProducto='"+listaIdProductos[i]["idProducto"]+"']").removeClass('btn-default');
			$("button.recuperarBoton[idProducto='"+listaIdProductos[i]["idProducto"]+"']").addClass('btn-primary agregarProducto');

		}


	}

	sincronizarBotonesProductosCotizacion();

})


/*=============================================
QUITAR PRODUCTOS DE LA VENTA Y RECUPERAR BOTÓN
=============================================*/

var idQuitarProducto = [];

localStorage.removeItem("quitarProducto");

$(".formularioCotizacion").on("click", "button.quitarProducto", function(){

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
	renderProductosCotizacionCards();

	if($(".nuevoProducto").children().length == 0){

		$("#nuevoImpuestoCotizacion").val(0);
		$("#nuevoTotalCotizacion").val(0);
		$("#totalCotizacion").val(0);
		$("#nuevoTotalCotizacion").attr("total",0);

	}else{

		// SUMAR TOTAL DE PRECIOS

    	sumarTotalPreciosCo()

    	// AGREGAR IMPUESTO
	        
        agregarImpuestoCotizacion()

        // AGRUPAR PRODUCTOS EN FORMATO JSON

        listarProductosCotizacion()

	}

})

/*=============================================
AGREGANDO PRODUCTOS DESDE EL BOTÓN PARA DISPOSITIVOS
=============================================*/

var numProducto = 0;

$(".btnAgregarProductoss").click(function(){

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

	         	if(item.stock != 0){

		         	$("#producto"+numProducto).append(

						'<option idProducto="'+item.id+'" value="'+item.descripcion+'">'+item.descripcion+'</option>'
		         	)

		         
		         }

		         

	         }

        	 // SUMAR TOTAL DE PRECIOS

    		sumarTotalPreciosCo()

    		// AGREGAR IMPUESTO
	        
	        agregarImpuestoCotizacion()

	        // PONER FORMATO AL PRECIO DE LOS PRODUCTOS

	        $(".nuevoPrecioProducto").number(true, 2);


      	}

	})

})

/*=============================================
SELECCIONAR PRODUCTO
=============================================*/

$(".formularioCotizacion").on("change", "select.nuevaDescripcionProducto", function(){

	var nombreProducto = $(this).val();

	var nuevaDescripcionProducto = $(this).parent().parent().parent().children().children().children(".nuevaDescripcionProducto");

	var nuevoPrecioProducto = $(this).parent().parent().parent().children(".ingresoPrecio").children().children(".nuevoPrecioProducto");

	var nuevaCantidadProducto = $(this).parent().parent().parent().children(".ingresoCantidad").children(".nuevaCantidadProducto");

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

	        listarProductosCotizacion()

      	}

      })
})

/*=============================================
MODIFICAR LA CANTIDAD
=============================================*/

$(".formularioCotizacion").on("change", "input.nuevaCantidadProducto", function(){

	var precio = $(this).parent().parent().children(".ingresoPrecio").children().children(".nuevoPrecioProducto");

	var precioFinal = $(this).val() * precio.attr("precioReal");
	
	precio.val(precioFinal);

	var nuevoStock = Number($(this).attr("stock")) - $(this).val();

	$(this).attr("nuevoStock", nuevoStock);

	if($(this).attr("data-permite-sin-stock") != "1" && Number($(this).val()) > Number($(this).attr("stock"))){

		/*=============================================
		SI LA CANTIDAD ES SUPERIOR AL STOCK REGRESAR VALORES INICIALES
		=============================================*/

		$(this).val(0);

		$(this).attr("nuevoStock", $(this).attr("stock"));

		var precioFinal = $(this).val() * precio.attr("precioReal");

		precio.val(precioFinal);

		sumarTotalPreciosCo();

		swal({
	      title: "La cantidad supera el Stock",
	      text: "¡Sólo hay "+$(this).attr("stock")+" unidades!",
	      type: "error",
	      confirmButtonText: "¡Cerrar!"
	    });

	    return;

	}

	// SUMAR TOTAL DE PRECIOS

	sumarTotalPreciosCo()

	// AGREGAR IMPUESTO
	        
    agregarImpuestoCotizacion()

    // AGRUPAR PRODUCTOS EN FORMATO JSON

    listarProductosCotizacion()

})

/*=============================================
SUMAR TODOS LOS PRECIOS
=============================================*/

function sumarTotalPreciosCo(){

	var precioItem = $(".nuevoPrecioProducto");
	
	var arraySumaPrecio = [];  

	for(var i = 0; i < precioItem.length; i++){

		 arraySumaPrecio.push(Number($(precioItem[i]).val()));
		
		 
	}

	function sumaArrayPrecios(total, numero){

		return total + numero;

	}

	var sumaTotalPrecio = arraySumaPrecio.length ? arraySumaPrecio.reduce(sumaArrayPrecios) : 0;
	
	$("#nuevoTotalCotizacion").val(sumaTotalPrecio);
	$("#totalCotizacion").val(sumaTotalPrecio);
	$("#nuevoTotalCotizacion").attr("total",sumaTotalPrecio);


}

/*=============================================
FUNCIÓN AGREGAR DESCUENTO
=============================================*/

function agregarImpuestoCotizacion(){

	var impuesto = $("#nuevoImpuestoCotizacion").val();
	var precioTotal = $("#nuevoTotalCotizacion").attr("total");

	var precioImpuesto = Number(precioTotal * impuesto/100);

	var totalConImpuesto = Number(precioTotal) - Number(precioImpuesto);
	
	$("#nuevoTotalCotizacion").val(totalConImpuesto);

	$("#totalCotizacion").val(totalConImpuesto);

	$("#nuevoPrecioImpuesto").val(precioImpuesto);

	$("#nuevoPrecioNeto").val(precioTotal);

}

/*=============================================
CUANDO CAMBIA EL IMPUESTO
=============================================*/

$("#nuevoImpuestoCotizacion").change(function(){

	agregarImpuestoCotizacion();

});

/*=============================================
FORMATO AL PRECIO FINAL
=============================================*/

$("#nuevoTotalCotizacion").number(true, 2);

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
$(".formularioCotizacion").on("change", "input#nuevoValorEfectivo", function(){

	var efectivo = $(this).val();

	var cambio =  Number(efectivo) - Number($('#nuevoTotalCotizacion').val());

	var nuevoCambioEfectivo = $(this).parent().parent().parent().children('#capturarCambioEfectivo').children().children('#nuevoCambioEfectivo');

	nuevoCambioEfectivo.val(cambio);

})

/*=============================================
CAMBIO TRANSACCIÓN
=============================================*/
$(".formularioCotizacion").on("change", "input#nuevoCodigoTransaccion", function(){

	// Listar método en la entrada
     listarMetodos()


})


/*=============================================
LISTAR TODOS LOS PRODUCTOS
=============================================*/

function listarProductosCotizacion(){

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

	$("#listaProductosCotizacion").val(JSON.stringify(listaProductos)); 

}

$(".formularioCotizacion").on("submit", function(event){
	if($(".cotizacion-step[data-cotizacion-step]").length){
		if(!hayClienteCotizacionSeleccionado()){
			event.preventDefault();
			mostrarPasoCotizacion(1);
			avisoPasoCotizacion("Seleccione un cliente", "La cotizacion necesita un cliente antes de guardarse.");
			return false;
		}

		if(!hayProductosCotizacionSeleccionados()){
			event.preventDefault();
			mostrarPasoCotizacion(2);
			avisoPasoCotizacion("Agregue productos", "La cotizacion necesita al menos un producto seleccionado.");
			return false;
		}
	}

	listarProductosCotizacion();
	return true;
});

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
BOTON EDITAR VENTA
=============================================
$(".tablas").on("click", ".btnEditarVenta", function(){

	var idVenta = $(this).attr("idVenta");

	window.location = "index.php?ruta=editar-venta&idVenta="+idVenta;


})*/

/*=============================================
FUNCIÓN PARA DESACTIVAR LOS BOTONES AGREGAR CUANDO EL PRODUCTO YA HABÍA SIDO SELECCIONADO EN LA CARPETA
=============================================*/

function quitarAgregarProducto(){

	//Capturamos todos los id de productos que fueron elegidos en la venta
	var idProductos = $(".quitarProducto");

	//Capturamos todos los botones de agregar que aparecen en la tabla
	var botonesTabla = $(".tablaCotizacion tbody button.agregarProducto");

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

$('.tablaCotizacion').on( 'draw.dt', function(){

	quitarAgregarProducto();
	sincronizarBotonesProductosCotizacion();

})

/*=============================================
ACCIONES DE COTIZACION EN TARJETAS / MODALES
=============================================*/
$(document).on("click", ".cot-modal .btnImprimirCotizacion, .cot-card .btnImprimirCotizacion, .cot-acciones .btnImprimirCotizacion", function(e){

	e.preventDefault();
	e.stopPropagation();
	e.stopImmediatePropagation();

	var codigoCotizacion = $(this).attr("codigoCotizacion") || $(this).data("codigoCotizacion");
	var idCotizacion = $(this).attr("idCotizacion") || $(this).data("idCotizacion");
	var urlImpresion = $(this).attr("href") || ("extensiones/tcpdf/pdf/cotizacion.php?idCotizacion="+idCotizacion+"&codigoCotizacion="+codigoCotizacion);

	window.open(urlImpresion, "_blank");

})

$(document).on("click", ".cot-modal .btnEliminarCotizar, .cot-card .btnEliminarCotizar, .cot-acciones .btnEliminarCotizar", function(e){

	e.preventDefault();
	e.stopPropagation();
	e.stopImmediatePropagation();

	var idCotizar = $(this).attr("idCotizar") || $(this).data("idCotizar");
	var urlEliminar = $(this).attr("href") || ("index.php?ruta=cotizacion&idCotizar="+idCotizar);

	swal({
		title: "Esta seguro de borrar la cotizacion?",
		text: "Si no lo esta puede cancelar la accion.",
		type: "warning",
		showCancelButton: true,
		confirmButtonColor: "#3085d6",
		cancelButtonColor: "#d33",
		cancelButtonText: "Cancelar",
		confirmButtonText: "Si, borrar cotizacion"
	}).then(function(result){
		if(result.value){
			window.location = urlEliminar;
		}
	})

})


/*=============================================
BORRAR VENTA
=============================================*/
$(document).on("click", ".btnEliminarCotizar", function(e){

  e.preventDefault();
  e.stopPropagation();
  e.stopImmediatePropagation();

  var idCotizar= $(this).attr("idCotizar");

  swal({
        title: '¿Está seguro de borrar la cotización?',
        text: "¡Si no lo está puede cancelar la accíón!",
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Si, borrar contización!'
      }).then(function(result){
        if (result.value) {
          
            window.location = "index.php?ruta=cotizacion&idCotizar="+idCotizar;
        }

  })

})

/*=============================================
IMPRIMIR COTIZACION
=============================================*/

$(document).on("click", ".btnImprimirCotizacion", function(){

	var codigoCotizacion= $(this).attr("codigoCotizacion");
	var idCotizacion= $(this).attr("idCotizacion");

	window.open("extensiones/tcpdf/pdf/cotizacion.php?idCotizacion="+idCotizacion+"&codigoCotizacion="+codigoCotizacion, "_blank");

})

/*=============================================
RANGO DE FECHAS
=============================================

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

)*/

/*=============================================
CANCELAR RANGO DE FECHAS
=============================================

$(".daterangepicker.opensleft .range_inputs .cancelBtn").on("click", function(){

	localStorage.removeItem("capturarRango");
	localStorage.clear();
	window.location = "ventas";
})
*/
/*=============================================
CAPTURAR HOY
=============================================

$(".daterangepicker.opensleft .ranges li").on("click", function(){

	var textoHoy = $(this).attr("data-range-key");

	if(textoHoy == "Hoy"){

		var d = new Date();
		
		var dia = d.getDate();
		var mes = d.getMonth()+1;
		var año = d.getFullYear();

		if(mes < 10){

			var fechaInicial = año+"-0"+mes+"-"+dia;
			var fechaFinal = año+"-0"+mes+"-"+dia;

		}else if(dia < 10){

			var fechaInicial = año+"-"+mes+"-0"+dia;
			var fechaFinal = año+"-"+mes+"-0"+dia;

		}else if(mes < 10 && dia < 10){

			var fechaInicial = año+"-0"+mes+"-0"+dia;
			var fechaFinal = año+"-0"+mes+"-0"+dia;

		}else{

			var fechaInicial = año+"-"+mes+"-"+dia;
	    	var fechaFinal = año+"-"+mes+"-"+dia;

		}	

    	localStorage.setItem("capturarRango", "Hoy");

    	window.location = "index.php?ruta=ventas&fechaInicial="+fechaInicial+"&fechaFinal="+fechaFinal;

	}

})*/




