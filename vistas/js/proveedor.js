/*=============================================
EDITAR PROVEEDOR
=============================================*/
$(document).on("click", ".btnEditarProveedor", function(e){

	e.preventDefault();
	e.stopPropagation();

	var idProveedor = $(this).attr("idProveedor");
	var datos = new FormData();
	datos.append("idProveedor", idProveedor);

	$.ajax({
		url:"ajax/proveedor.ajax.php",
		method: "POST",
		data: datos,
		cache: false,
		contentType: false,
		processData: false,
		dataType: "json",
		success: function(respuesta){
			if(!respuesta){
				return;
			}

			$("#editarProveedor").val(respuesta["nombre"]);
			$("#editarContacto").val(respuesta["contacto"]);
			$("#editarDireccion").val(respuesta["direccion"]);
			$("#editarTelefono").val(respuesta["telefono"]);
			$("#modalVerProveedor").modal("hide");
			$("#modalEditarProveedor").modal("show");
		}
	});
});

/*=============================================
ACTIVAR PROVEEDOR
=============================================*/
$(document).on("click", ".btnActivar", function(e){

	e.preventDefault();
	e.stopPropagation();

	var $boton = $(this);
	var idProveedor = $boton.attr("idProveedor");
	var estadoProveedor = $boton.attr("estadoProveedor");

	var datos = new FormData();
	datos.append("activarId", idProveedor);
	datos.append("activarProveedor", estadoProveedor);

	$.ajax({
		url:"ajax/proveedor.ajax.php",
		method: "POST",
		data: datos,
		cache: false,
		contentType: false,
		processData: false,
		success: function(){
			var $botonesProveedor = $(".btnActivar[idProveedor='"+idProveedor+"']");

			if(estadoProveedor == 0){
				$botonesProveedor.removeClass("btn-success").addClass("btn-danger");
				$botonesProveedor.html("Desactivado");
				$botonesProveedor.attr("estadoProveedor", 1);
			}else{
				$botonesProveedor.addClass("btn-success").removeClass("btn-danger");
				$botonesProveedor.html("Activado");
				$botonesProveedor.attr("estadoProveedor", 0);
			}

			var $card = $(".proveedor-card[data-id='"+idProveedor+"']");
			if($card.length){
				var activo = estadoProveedor != 0;
				$card.toggleClass("estado-activo", activo).toggleClass("estado-inactivo", !activo);
				$card.attr("data-estado", activo ? "Activado" : "Desactivado");
				$card.find(".proveedor-card-item strong").filter(function(){
					return $(this).closest(".proveedor-card-item").find("span").text().toLowerCase() === "estado";
				}).text(activo ? "Activado" : "Desactivado");
			}
		}
	});
});

/*=============================================
ELIMINAR PROVEEDOR
=============================================*/
$(document).on("click", ".btnEliminarProveedor", function(e){

	e.preventDefault();
	e.stopPropagation();

	var idProveedor = $(this).attr("idProveedor");

	swal({
		title: "Seguro que desea borrar el proveedor?",
		text: "Si no esta seguro puede cancelar la accion.",
		type: "warning",
		showCancelButton: true,
		confirmButtonColor: "#3085d6",
		cancelButtonColor: "#d33",
		cancelButtonText: "Cancelar",
		confirmButtonText: "Si, borrar proveedor"
	}).then(function(result){
		if(result.value){
			window.location = "index.php?ruta=proveedor&idProveedor="+idProveedor;
		}
	});
});
