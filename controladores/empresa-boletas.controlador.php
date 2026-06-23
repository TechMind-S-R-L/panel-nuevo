<?php

class ControladorEmpresaBoletas{

	static public function ctrGuardarDatosBoletas(){
		if(($_SESSION["perfil"] ?? "") !== "Administrador"){
			echo '<script>window.location="inicio";</script>';
			return;
		}

		if(!isset($_POST["guardarDatosBoletas"])){
			return;
		}

		$nombre = trim((string)($_POST["boletasEmpresaNombre"] ?? ""));
		$direccion = trim((string)($_POST["boletasEmpresaDireccion"] ?? ""));
		$telefono = trim((string)($_POST["boletasEmpresaTelefono"] ?? ""));
		$correo = strtolower(trim((string)($_POST["boletasEmpresaCorreo"] ?? "")));

		if($nombre === "" || $direccion === "" || $telefono === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)){
			echo '<script>
				swal({
					type:"warning",
					title:"Revise los datos",
					text:"Nombre, direccion, telefonos y correo son obligatorios. El correo debe ser valido.",
					confirmButtonText:"Cerrar"
				}).then(function(){ window.location="datos-boletas"; });
			</script>';
			return;
		}

		$idUsuario = (int)($_SESSION["id"] ?? 0);
		$respuestas = array(
			ModeloWebPublicaciones::mdlGuardarConfiguracion("boletas_empresa_nombre", $nombre, $idUsuario),
			ModeloWebPublicaciones::mdlGuardarConfiguracion("boletas_empresa_direccion", $direccion, $idUsuario),
			ModeloWebPublicaciones::mdlGuardarConfiguracion("boletas_empresa_telefono", $telefono, $idUsuario),
			ModeloWebPublicaciones::mdlGuardarConfiguracion("boletas_empresa_correo", $correo, $idUsuario)
		);

		if(!in_array("error", $respuestas, true)){
			echo '<script>
				swal({
					type:"success",
					title:"Datos actualizados",
					text:"El encabezado de las boletas ya usara esta informacion.",
					confirmButtonText:"Cerrar"
				}).then(function(){ window.location="datos-boletas"; });
			</script>';
		}else{
			echo '<script>
				swal({
					type:"error",
					title:"No se pudo guardar",
					text:"Intente nuevamente o revise la conexion a la base de datos.",
					confirmButtonText:"Cerrar"
				}).then(function(){ window.location="datos-boletas"; });
			</script>';
		}
	}
}

