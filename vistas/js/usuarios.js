var TM_USUARIO_FOTO_DEFAULT = "vistas/img/usuarios/default/anonymous.png";

function normalizarUsuarioTechMind(texto){
	var mapa = {
		"\u00e1":"a", "\u00e9":"e", "\u00ed":"i", "\u00f3":"o", "\u00fa":"u", "\u00f1":"n",
		"\u00c1":"a", "\u00c9":"e", "\u00cd":"i", "\u00d3":"o", "\u00da":"u", "\u00d1":"n"
	};

	return (texto || "")
		.replace(/[\u00e1\u00e9\u00ed\u00f3\u00fa\u00f1\u00c1\u00c9\u00cd\u00d3\u00da\u00d1]/g, function(letra){
			return mapa[letra] || letra;
		})
		.toLowerCase()
		.replace(/[^a-z0-9 ]/g, " ")
		.replace(/\s+/g, " ")
		.trim();
}

function generarUsuarioAutomaticoVista(){
	var nombre = normalizarUsuarioTechMind($("#nuevoNombreUsuario").val());
	var documento = ($("#nuevoDocumentoUsuario").val() || "").replace(/\D/g, "");
	var partes = nombre.split(" ").filter(Boolean);
	var usuario = "";

	if(partes.length >= 2){
		for(var i = 0; i < partes.length - 1; i++){
			usuario += partes[i].charAt(0);
		}
		usuario += partes[partes.length - 1];
	}else if(partes.length == 1){
		usuario = partes[0];
	}

	if(documento.length){
		usuario += documento.slice(-3);
	}

	usuario = usuario.substring(0, 35);

	$("#nuevoUsuario").val(usuario);
	$("#vistaUsuarioGenerado").text(usuario || "Complete nombre y documento");
}

function abrirDetalleUsuario(tarjeta){
	if(!tarjeta || !tarjeta.length){
		return;
	}

	$("#detalleUsuarioFoto").attr("src", tarjeta.data("foto") || TM_USUARIO_FOTO_DEFAULT);
	$("#detalleUsuarioNombre").text(tarjeta.data("nombre") || "Sin nombre");
	$("#detalleUsuarioLogin").text("Ultimo login: " + (tarjeta.data("login") || "-"));
	$("#detalleUsuarioUsuario").text(tarjeta.data("usuario") || "-");
	$("#detalleUsuarioEmail").text(tarjeta.data("email") || "-");
	$("#detalleUsuarioEstado").text(tarjeta.data("estado") || "-");
	$("#detalleUsuarioPerfil").text(tarjeta.data("perfil") || "-");
	$("#detalleUsuarioRol").text(tarjeta.data("rol") || "-");
	$("#detalleUsuarioAcciones").html(tarjeta.find(".usuario-card-actions-template").html() || "");
	$("#detalleUsuarioAcciones .btnVerUsuarioDetalle").remove();
	$("#detalleUsuarioAcciones [title]").tooltip({container:"body"});
	$("#modalDetalleUsuario").modal("show");
}

$(document).on("change", ".nuevaFoto", function(){
	var imagen = this.files[0];

	if(!imagen){
		return;
	}

	if(imagen.type != "image/jpeg" && imagen.type != "image/png"){
		$(".nuevaFoto").val("");
		swal({
			title: "Error al subir la imagen",
			text: "La imagen debe estar en formato JPG o PNG.",
			type: "error",
			confirmButtonText: "Cerrar"
		});
		return;
	}

	if(imagen.size > 2000000){
		$(".nuevaFoto").val("");
		swal({
			title: "Error al subir la imagen",
			text: "La imagen no debe pesar mas de 2MB.",
			type: "error",
			confirmButtonText: "Cerrar"
		});
		return;
	}

	var datosImagen = new FileReader();
	datosImagen.readAsDataURL(imagen);

	$(datosImagen).on("load", function(event){
		var rutaImagen = event.target.result;
		$(".previsualizar").attr("src", rutaImagen);
		$(".previsualizarEditar").attr("src", rutaImagen);
	});
});

$(document).on("error", ".tm-user-avatar img, #detalleUsuarioFoto, .previsualizar, .previsualizarEditar", function(){
	this.src = TM_USUARIO_FOTO_DEFAULT;
	$(this).siblings("span").css("display", "flex");
});

$(document).on("input", "#nuevoNombreUsuario, #nuevoDocumentoUsuario", generarUsuarioAutomaticoVista);

$(document).on("change", "#nuevoRol", function(){
	var rol = $(this).val();

	if(!rol){
		return;
	}

	if(rol == "vendedor"){
		$("#nuevoPerfil").val("Vendedor");
	}else if($("#nuevoPerfil").val() == "" || $("#nuevoPerfil").val() == "Vendedor"){
		$("#nuevoPerfil").val("Especial");
	}
});

$(document).on("submit", "#formAgregarUsuario", function(e){
	generarUsuarioAutomaticoVista();

	if($("#nuevoUsuario").val() == ""){
		e.preventDefault();
		swal({
			title: "Falta generar el usuario",
			text: "Ingrese al menos el nombre completo del usuario.",
			type: "warning",
			confirmButtonText: "Cerrar"
		});
	}
});

$(document).on("input", "#buscarUsuarioCard", function(){
	var texto = ($(this).val() || "").toLowerCase();

	$(".usuario-card").each(function(){
		$(this).toggle(($(this).data("search") || "").indexOf(texto) !== -1);
	});
});

$(document).on("click", ".usuario-card", function(e){
	if($(e.target).closest("button, a, input, select, textarea").length){
		return;
	}

	abrirDetalleUsuario($(this));
});

$(document).on("click", ".btnVerUsuarioDetalle", function(e){
	e.preventDefault();
	e.stopPropagation();

	abrirDetalleUsuario($(this).closest(".usuario-card"));
});

$(document).on("click", ".btnEditarUsuario", function(e){
	e.preventDefault();
	e.stopPropagation();

	$("#modalDetalleUsuario").modal("hide");

	var idUsuario = $(this).attr("idUsuario");
	var datos = new FormData();
	datos.append("idUsuario", idUsuario);

	$.ajax({
		url:"ajax/usuarios.ajax.php",
		method:"POST",
		data:datos,
		cache:false,
		contentType:false,
		processData:false,
		dataType:"json",
		success:function(respuesta){
			var perfil = $.trim(respuesta.perfil || "");
			var rol = $.trim(respuesta.rol || "");

			$("#editarNombre").val(respuesta.nombre || "");
			$("#editarUsuario").val(respuesta.usuario || "");
			$("#editarEmail").val(respuesta.email || "");
			$("#idUsuario").val(respuesta.id || "");
			$("#editarPerfil").val(perfil);
			$("#editarRol").val(rol);
			$("#fotoActual").val(respuesta.foto || "");
			$("#passwordActual").val(respuesta.password || "");
			$(".previsualizarEditar").attr("src", respuesta.foto || TM_USUARIO_FOTO_DEFAULT);
		}
	});
});

$(document).on("click", ".btnActivar", function(e){
	e.preventDefault();
	e.stopPropagation();

	var boton = $(this);
	var idUsuario = boton.attr("idUsuario");
	var estadoUsuario = boton.attr("estadoUsuario");
	var datos = new FormData();

	datos.append("activarId", idUsuario);
	datos.append("activarUsuario", estadoUsuario);

	$.ajax({
		url:"ajax/usuarios.ajax.php",
		method:"POST",
		data:datos,
		cache:false,
		contentType:false,
		processData:false,
		success:function(){
			if(window.matchMedia("(max-width:767px)").matches){
				swal({
					title:"El usuario ha sido actualizado",
					type:"success",
					confirmButtonText:"Cerrar"
				}).then(function(result){
					if(result.value){
						window.location = "usuarios";
					}
				});
			}
		}
	});

	if(estadoUsuario == 0){
		boton.removeClass("active").addClass("inactive");
		boton.html('<i class="fa fa-ban"></i><span>Inactivo</span>');
		boton.attr("estadoUsuario", 1);
	}else{
		boton.removeClass("inactive").addClass("active");
		boton.html('<i class="fa fa-check-circle"></i><span>Activo</span>');
		boton.attr("estadoUsuario", 0);
	}
});

$(document).on("change", "#nuevoUsuario", function(){
	$(".alert").remove();

	var usuario = $(this).val();
	var datos = new FormData();
	datos.append("validarUsuario", usuario);

	$.ajax({
		url:"ajax/usuarios.ajax.php",
		method:"POST",
		data:datos,
		cache:false,
		contentType:false,
		processData:false,
		dataType:"json",
		success:function(respuesta){
			if(respuesta){
				$("#nuevoUsuario").parent().after('<div class="alert alert-warning">Este usuario ya existe en la base de datos</div>');
				$("#nuevoUsuario").val("");
			}
		}
	});
});

$(document).on("click", ".btnEliminarUsuario", function(e){
	e.preventDefault();
	e.stopPropagation();

	var idUsuario = $(this).attr("idUsuario");
	var fotoUsuario = $(this).attr("fotoUsuario");
	var usuario = $(this).attr("usuario");

	swal({
		title:"Seguro que desea borrar el usuario?",
		text:"Si no lo esta puede cancelar la accion.",
		type:"warning",
		showCancelButton:true,
		confirmButtonColor:"#3085d6",
		cancelButtonColor:"#d33",
		cancelButtonText:"Cancelar",
		confirmButtonText:"Si, borrar usuario"
	}).then(function(result){
		if(result.value){
			window.location = "index.php?ruta=usuarios&idUsuario="+idUsuario+"&usuario="+usuario+"&fotoUsuario="+fotoUsuario;
		}
	});
});
