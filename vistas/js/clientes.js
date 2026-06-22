function cargarCliente(idCliente, callback){
	var datos = new FormData();
	datos.append("idCliente", idCliente);

	$.ajax({
		url: "ajax/clientes.ajax.php",
		method: "POST",
		data: datos,
		cache: false,
		contentType: false,
		processData: false,
		dataType: "json",
		success: function(respuesta){
			if(!respuesta || !respuesta.id){
				swal({
					type: "error",
					title: "No se pudo cargar el cliente",
					text: "Verifique que el registro exista.",
					confirmButtonText: "Cerrar"
				});
				return;
			}
			callback(respuesta);
		},
		error: function(){
			swal({
				type: "error",
				title: "Error de conexion",
				text: "No se pudo consultar la informacion del cliente.",
				confirmButtonText: "Cerrar"
			});
		}
	});
}

function textoCliente(valor){
	return valor && String(valor).trim() !== "" ? valor : "-";
}

function escapeCliente(valor){
	return String(textoCliente(valor))
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}

function inicialesCliente(nombre){
	var partes = String(textoCliente(nombre)).trim().split(/\s+/);
	var iniciales = "";
	for(var i = 0; i < partes.length; i++){
		if(partes[i] && partes[i] !== "-"){
			iniciales += partes[i].charAt(0).toUpperCase();
		}
		if(iniciales.length >= 2){
			break;
		}
	}
	return iniciales || "CL";
}

function abrirDetalleCliente(idCliente){
	cargarCliente(idCliente, function(cliente){
		var claveWeb = cliente.password_web
			? '<span class="cliente-status-pill success"><i class="fa fa-check"></i> Web configurada</span>'
			: '<span class="cliente-status-pill warning"><i class="fa fa-exclamation-triangle"></i> Sin clave web</span>';
		var botonEliminar = window.tmClientePuedeEliminar
			? '<button type="button" class="btn btn-danger btnEliminarCliente" title="Eliminar cliente" idCliente="'+escapeCliente(cliente.id)+'"><i class="fa fa-trash"></i> Eliminar</button>'
			: '';
		var html = ''
			+ '<div class="cliente-detail-head">'
				+ '<div class="cliente-detail-identity">'
					+ '<div class="cliente-avatar">'+escapeCliente(inicialesCliente(cliente.nombre))+'</div>'
					+ '<div>'
						+ '<h3>'+escapeCliente(cliente.nombre)+'</h3>'
						+ '<p><i class="fa fa-id-card-o"></i> Documento: '+escapeCliente(cliente.documento)+'</p>'
					+ '</div>'
				+ '</div>'
				+ claveWeb
			+ '</div>'
			+ '<div class="cliente-detail-summary">'
				+ '<div class="cliente-summary-card"><span>Total compras</span><strong>Bs '+Number(cliente.compras || 0).toFixed(2)+'</strong><p>Acumulado comercial</p></div>'
				+ '<div class="cliente-summary-card"><span>Ultima compra</span><p>'+escapeCliente(cliente.ultima_compra)+'</p></div>'
				+ '<div class="cliente-summary-card"><span>Registro</span><p>'+escapeCliente(cliente.fecha)+'</p></div>'
			+ '</div>'
			+ '<div class="cliente-detail-section">'
			+ '<div class="cliente-detail-grid">'
				+ '<div class="cliente-detail-box"><span>Email</span><p>'+escapeCliente(cliente.email)+'</p></div>'
				+ '<div class="cliente-detail-box"><span>Telefono</span><p>'+escapeCliente(cliente.telefono)+'</p></div>'
				+ '<div class="cliente-detail-box"><span>Fecha nacimiento</span><p>'+escapeCliente(cliente.fecha_nacimiento)+'</p></div>'
				+ '<div class="cliente-detail-box"><span>Acceso web</span><p>'+claveWeb+'</p></div>'
				+ '<div class="cliente-detail-box full"><span>Direccion</span><p>'+escapeCliente(cliente.direccion)+'</p></div>'
			+ '</div>'
			+ '</div>'
			+ '<div class="cliente-detail-actions">'
				+ '<button type="button" class="btn btn-warning btnEditarCliente" title="Editar cliente" idCliente="'+escapeCliente(cliente.id)+'"><i class="fa fa-pencil"></i> Editar</button>'
				+ '<button type="button" class="btn btn-primary btnPasswordWebCliente" title="Generar o cambiar clave web" idCliente="'+escapeCliente(cliente.id)+'" cliente="'+escapeCliente(cliente.nombre)+'"><i class="fa fa-key"></i> Clave web</button>'
				+ botonEliminar
			+ '</div>';

		$("#detalleCliente").html(html);
		$("#modalVerCliente").modal("show");
	});
}

$(document).on("click", ".btnVerCliente", function(e){
	e.preventDefault();
	e.stopPropagation();
	abrirDetalleCliente($(this).attr("idCliente"));
});

$(document).on("click", ".clienteCardDetalle", function(e){
	if($(e.target).closest("button, a, .btn").length){
		return;
	}
	abrirDetalleCliente($(this).attr("idCliente"));
});

$(document).on("click", ".btnEditarCliente", function(){
	var idCliente = $(this).attr("idCliente");

	cargarCliente(idCliente, function(respuesta){
		$("#idCliente").val(respuesta.id);
		$("#editarCliente").val(respuesta.nombre);
		$("#editarDocumentoId").val(respuesta.documento);
		$("#editarEmail").val(respuesta.email);
		$("#editarTelefono").val(respuesta.telefono);
		$("#editarDireccion").val(respuesta.direccion);
		$("#editarFechaNacimiento").val(respuesta.fecha_nacimiento);
		$("#modalVerCliente").modal("hide");
		$("#modalEditarCliente").modal("show");
	});
});

$(document).on("click", ".btnEliminarCliente", function(e){
	e.preventDefault();
	e.stopPropagation();
	e.stopImmediatePropagation();

	var idCliente = $(this).attr("idCliente");

	swal({
		title: "Eliminar cliente",
		text: "Esta accion borrara el registro del cliente. Si tiene movimientos asociados, revise antes de continuar.",
		type: "warning",
		showCancelButton: true,
		confirmButtonColor: "#d33",
		cancelButtonColor: "#6c757d",
		cancelButtonText: "Cancelar",
		confirmButtonText: "Si, eliminar"
	}).then(function(result){
		if(result.value){
			window.location = "index.php?ruta=clientes&idCliente=" + idCliente;
		}
	});
});

$(document).on("click", ".btnPasswordWebCliente", function(){
	var idCliente = $(this).attr("idCliente");
	var cliente = $(this).attr("cliente") || "Cliente";

	$("#idClientePasswordWeb").val(idCliente);
	$("#nombreClientePasswordWeb").text(cliente);
	$("#modoPasswordWeb").val("generar").trigger("change");
	$("#passwordWebManual").val("");
	$("#passwordWebConfirmar").val("");
	$("#modalVerCliente").modal("hide");
	$("#modalPasswordWebCliente").modal("show");
});

$(document).on("input", "#buscarClienteCards", function(){
	var busqueda = ($(this).val() || "").toLowerCase().trim();
	var visibles = 0;

	$(".clienteCardDetalle").each(function(){
		var coincide = ($(this).attr("data-search") || "").indexOf(busqueda) !== -1;
		$(this).toggle(coincide);
		if(coincide){
			visibles++;
		}
	});

	$("#clientesCardsCount").text(visibles);
	$(".cliente-empty-busqueda").remove();

	if(visibles === 0 && $(".clienteCardDetalle").length){
		$("#clientesCardGrid").append('<div class="cliente-empty cliente-empty-busqueda">No se encontraron clientes con esa busqueda.</div>');
	}
});

$(document).on("change", "#modoPasswordWeb", function(){
	var manual = $(this).val() === "manual";
	$("#camposPasswordWebManual").toggle(manual);
	$("#passwordWebManual, #passwordWebConfirmar").prop("required", manual);
});

$(function(){
	$("[title]").tooltip({container: "body"});
});
