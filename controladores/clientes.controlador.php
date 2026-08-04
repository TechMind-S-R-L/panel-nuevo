<?php

class ControladorClientes{

	static private function ctrHashPasswordWeb($password){
		return crypt($password, '$2a$07$asxx54ahjppf45sd87a5a4dDDGsystemdev$');
	}

	static private function ctrGenerarPasswordWeb(){
		$caracteres = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789";
		$password = "";
		for($i = 0; $i < 10; $i++){
			$password .= $caracteres[random_int(0, strlen($caracteres) - 1)];
		}
		return $password;
	}

	static private function ctrUrlPaginaWeb(){
		$base = trim((string)(getenv("TECHMIND_WEB_URL") ?: ""));
		return $base != "" ? rtrim($base, "/") : "https://techmind.com.bo";
	}

	static private function ctrEnviarCorreoClienteWeb($cliente, $link){
		if(empty($cliente["email"]) || !filter_var($cliente["email"], FILTER_VALIDATE_EMAIL)){
			return false;
		}

		$mensajeHtml = '
			<!doctype html><html><body style="margin:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#14213d">
			<div style="max-width:640px;margin:0 auto;padding:28px 16px">
			<div style="background:#fff;border:1px solid #dbe8f7;border-radius:22px;overflow:hidden;box-shadow:0 20px 55px rgba(25,74,145,.12)">
			<div style="padding:24px;background:linear-gradient(135deg,#eef6ff,#fff)"><img src="'.self::ctrUrlPaginaWeb().'/dist/images/logos/LOGO%20(1).png" alt="TechMind" style="max-width:175px;height:auto"></div>
			<div style="padding:28px">
			<h1 style="margin:0 0 10px;font-size:24px;color:#10233f">Acceso web TechMind</h1>
			<p style="font-size:15px;line-height:1.6;color:#51627a">Hola <b>'.htmlspecialchars($cliente["nombre"], ENT_QUOTES, "UTF-8").'</b>, usa este enlace para crear o cambiar la contrasena de tu cuenta web.</p>
			<p style="font-size:15px;line-height:1.6;color:#51627a">El enlace estara disponible por 60 minutos.</p>
			<p style="margin:28px 0"><a href="'.htmlspecialchars($link, ENT_QUOTES, "UTF-8").'" style="display:inline-block;background:#2478d4;color:#fff;text-decoration:none;font-weight:700;border-radius:14px;padding:14px 22px">Crear o cambiar contrasena</a></p>
			<p style="font-size:12px;line-height:1.5;color:#7a8799">Si no solicitaste este acceso, puedes ignorar este correo.</p>
			</div></div></div></body></html>
		';

		if(class_exists("ControladorUsuarios")){
			return ControladorUsuarios::ctrEnviarCorreoSistema($cliente["email"], "Acceso web TechMind", $mensajeHtml);
		}

		return @mail($cliente["email"], "Acceso web TechMind", $mensajeHtml);
	}

	static private function ctrDatosClienteValidos($datos){
		$nombre = trim($datos["nombre"] ?? "");
		$documento = trim($datos["documento"] ?? "");

		$nombreValido = $nombre !== "";
		$documentoValido = $documento !== "";

		return $nombreValido && $documentoValido;
	}

	static private function ctrAlertaClienteInvalido(){
		echo '<script>
			swal({
				type: "error",
				title: "Revise los datos del cliente",
				text: "Nombre y documento/NIT son obligatorios. Los demas datos pueden quedar vacios.",
				showConfirmButton: true,
				confirmButtonText: "Cerrar"
			}).then(function(result){
				if(result.value){
					window.location = "clientes";
				}
			});
		</script>';
	}

	static public function ctrCrearCliente(){
		if(isset($_POST["nuevoCliente"])){
			$datos = array(
				"nombre" => $_POST["nuevoCliente"],
				"documento" => $_POST["nuevoDocumentoId"],
				"email" => $_POST["nuevoEmail"] ?? "",
				"telefono" => $_POST["nuevoTelefono"] ?? "",
				"direccion" => $_POST["nuevaDireccion"] ?? "",
				"fecha_nacimiento" => $_POST["nuevaFechaNacimiento"] ?? ""
			);

			if(!self::ctrDatosClienteValidos($datos)){
				self::ctrAlertaClienteInvalido();
				return;
			}

			$respuesta = ModeloClientes::mdlIngresarCliente("clientes", $datos);

			if($respuesta == "ok"){
				$redireccion = "clientes";
				if(($_POST["origenCliente"] ?? "") == "servicios"){
					$tipoServicio = $_POST["tipoServicioCliente"] ?? "";
					$redireccion = "index.php?ruta=servicios".($tipoServicio != "" ? "&tipoServicio=".urlencode($tipoServicio) : "");
				}else if(($_POST["origenCliente"] ?? "") == "ventas"){
					$redireccion = "crear-venta";
				}else if(($_POST["origenCliente"] ?? "") == "cotizacion"){
					$redireccion = "crear-cotizacion";
				}

				if(class_exists("ControladorLogs")){
					ControladorLogs::ctrRegistrarLog("crear", "clientes", "Cliente ".$datos["nombre"]." creado");
				}

				echo '<script>
					swal({
						type: "success",
						title: "Cliente guardado correctamente",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
					}).then(function(result){
						if(result.value){
							window.location = "'.$redireccion.'";
						}
					});
				</script>';
			}else{
				echo '<script>
					swal({
						type: "error",
						title: "No se pudo guardar el cliente",
						text: "Revise si el documento ya existe o si la base de datos tiene permisos para actualizar la tabla clientes.",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
					});
				</script>';
			}
		}
	}

	static public function ctrMostrarClientes($item, $valor){
		return ModeloClientes::mdlMostrarClientes("clientes", $item, $valor);
	}

	static public function ctrEditarCliente(){
		if(isset($_POST["editarCliente"])){
			$datos = array(
				"id" => $_POST["idCliente"],
				"nombre" => $_POST["editarCliente"],
				"documento" => $_POST["editarDocumentoId"],
				"email" => $_POST["editarEmail"] ?? "",
				"telefono" => $_POST["editarTelefono"] ?? "",
				"direccion" => $_POST["editarDireccion"] ?? "",
				"fecha_nacimiento" => $_POST["editarFechaNacimiento"] ?? ""
			);

			if(!self::ctrDatosClienteValidos($datos)){
				self::ctrAlertaClienteInvalido();
				return;
			}

			$respuesta = ModeloClientes::mdlEditarCliente("clientes", $datos);

			if($respuesta == "ok"){
				if(class_exists("ControladorLogs")){
					ControladorLogs::ctrRegistrarLog("editar", "clientes", "Cliente ".$datos["nombre"]." actualizado");
				}

				echo '<script>
					swal({
						type: "success",
						title: "Cliente actualizado correctamente",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
					}).then(function(result){
						if(result.value){
							window.location = "clientes";
						}
					});
				</script>';
			}else{
				echo '<script>
					swal({
						type: "error",
						title: "No se pudo actualizar el cliente",
						text: "Revise los datos ingresados o la estructura de la tabla clientes.",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
					});
				</script>';
			}
		}
	}

	static public function ctrGuardarSeguimientoCliente(){
		if(!isset($_POST["idClienteCrm"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") == "Especial"){
			echo '<script>window.location = "clientes";</script>';
			return;
		}

		$estadosPermitidos = array("nuevo", "contactado", "cotizando", "cliente_activo", "seguimiento", "inactivo");
		$prioridadesPermitidas = array("baja", "media", "alta", "urgente");
		$estado = $_POST["estadoClienteCrm"] ?? "nuevo";
		$prioridad = $_POST["prioridadClienteCrm"] ?? "media";

		if(!in_array($estado, $estadosPermitidos, true)){
			$estado = "nuevo";
		}
		if(!in_array($prioridad, $prioridadesPermitidas, true)){
			$prioridad = "media";
		}

		$datos = array(
			"id_cliente" => (int)$_POST["idClienteCrm"],
			"estado" => $estado,
			"prioridad" => $prioridad,
			"proxima_accion" => $_POST["proximaAccionClienteCrm"] ?? "",
			"nota" => trim((string)($_POST["notaClienteCrm"] ?? "")),
			"id_usuario" => (int)($_SESSION["id"] ?? 0)
		);

		$respuesta = ModeloClientes::mdlGuardarSeguimientoCliente($datos);

		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("crm", "clientes", "Seguimiento CRM actualizado para cliente ".$datos["id_cliente"]);
			}

			echo '<script>
				swal({
					type: "success",
					title: "Seguimiento guardado",
					text: "El estado CRM del cliente fue actualizado correctamente.",
					confirmButtonText: "Cerrar"
				}).then(function(){
					window.location = "clientes";
				});
			</script>';
		}else{
			echo '<script>
				swal({
					type: "error",
					title: "No se pudo guardar el seguimiento",
					text: "Revise permisos de base de datos para la tabla cliente_crm.",
					confirmButtonText: "Cerrar"
				});
			</script>';
		}
	}

	static public function ctrEliminarCliente(){
		if(isset($_GET["idCliente"])){
			if(($_SESSION["perfil"] ?? "") != "Administrador"){
				echo '<script>window.location = "clientes";</script>';
				return;
			}

			$datos = $_GET["idCliente"];
			$respuesta = ModeloClientes::mdlEliminarCliente("clientes", $datos);

			if($respuesta == "ok"){
				if(class_exists("ControladorLogs")){
					ControladorLogs::ctrRegistrarLog("eliminar", "clientes", "Cliente ".$datos." eliminado");
				}

				echo '<script>
					swal({
						type: "success",
						title: "Cliente eliminado correctamente",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
					}).then(function(result){
						if(result.value){
							window.location = "clientes";
						}
					});
				</script>';
			}
		}
	}

	static public function ctrActualizarPasswordWebCliente(){
		if(!isset($_POST["idClientePasswordWeb"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "vendedor" && ($_SESSION["rol"] ?? "") != "cajero"){
			echo '<script>window.location = "clientes";</script>';
			return;
		}

		$idCliente = (int)$_POST["idClientePasswordWeb"];
		$cliente = self::ctrMostrarClientes("id", $idCliente);

		if(!$cliente){
			echo '<script>swal({type:"error",title:"Cliente no encontrado",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		if(empty($cliente["email"]) || !filter_var($cliente["email"], FILTER_VALIDATE_EMAIL)){
			echo '<script>swal({type:"error",title:"Correo requerido",text:"El cliente necesita un correo valido para recibir el enlace de acceso web.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$token = bin2hex(random_bytes(32));
		$tokenHash = hash("sha256", $token);
		$expira = date("Y-m-d H:i:s", strtotime("+1 hour"));
		$respuesta = ModeloClientes::mdlGuardarTokenPasswordWeb($idCliente, $tokenHash, $expira);

		if($respuesta == "ok"){
			$link = self::ctrUrlPaginaWeb()."/tienda.php?modulo=restablecer-password&token=".$token;
			$enviado = self::ctrEnviarCorreoClienteWeb($cliente, $link);

			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("password_web", "clientes", "Enlace de clave web generado para cliente ".$cliente["nombre"]);
			}

			$textoCorreo = $enviado
				? "Se envio un enlace al correo del cliente para crear o cambiar su contrasena web."
				: "Se genero el enlace, pero el servidor no confirmo el envio. Revise la configuracion SMTP o el log de correos pendientes.";

			echo '<script>
				swal({
					type: "success",
					title: "Enlace web generado",
					text: "'.$textoCorreo.'",
					confirmButtonText: "Cerrar"
				}).then(function(result){
					window.location = "clientes";
				});
			</script>';
		}else{
			echo '<script>swal({type:"error",title:"No se pudo generar el enlace",text:"Revise la base de datos o permisos para crear la tabla de tokens web.",confirmButtonText:"Cerrar"});</script>';
		}
	}

}
