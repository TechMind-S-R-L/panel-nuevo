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

	static private function ctrEnviarCorreoClienteWeb($cliente, $passwordTemporal){
		if(empty($cliente["email"]) || !filter_var($cliente["email"], FILTER_VALIDATE_EMAIL)){
			return false;
		}

		$protocolo = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
		$host = $_SERVER["HTTP_HOST"] ?? "localhost";
		$script = $_SERVER["SCRIPT_NAME"] ?? "/techmind/index.php";
		$base = rtrim(str_replace("\\", "/", dirname($script)), "/");
		$link = $protocolo."://".$host.$base;

		$mensajeHtml = '
			<h2>Acceso web TechMind</h2>
			<p>Hola <b>'.htmlspecialchars($cliente["nombre"], ENT_QUOTES, "UTF-8").'</b>, se genero una nueva contrasena para tu acceso web.</p>
			<p><b>Usuario / documento:</b> '.htmlspecialchars($cliente["documento"], ENT_QUOTES, "UTF-8").'</p>
			<p><b>Contrasena:</b> '.htmlspecialchars($passwordTemporal, ENT_QUOTES, "UTF-8").'</p>
			<p>Ingresa desde: <a href="'.$link.'">'.$link.'</a></p>
		';

		$cabeceras = "MIME-Version: 1.0\r\n";
		$cabeceras .= "Content-type:text/html;charset=UTF-8\r\n";
		$cabeceras .= "From: TechMind <no-reply@techmind.local>\r\n";

		$enviado = @mail($cliente["email"], "Nueva contrasena web TechMind", $mensajeHtml, $cabeceras);

		if(!$enviado){
			$directorioLog = __DIR__ . "/../extensiones/logs";
			if(!is_dir($directorioLog)){
				mkdir($directorioLog, 0755, true);
			}
			file_put_contents(
				$directorioLog . "/correos-pendientes.log",
				"[".date("Y-m-d H:i:s")."] PARA: ".$cliente["email"]." | ASUNTO: Nueva contrasena web TechMind".PHP_EOL.strip_tags(str_replace(["<br>", "<br/>", "<br />"], PHP_EOL, $mensajeHtml)).PHP_EOL.PHP_EOL,
				FILE_APPEND
			);
		}

		return true;
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

		$modo = $_POST["modoPasswordWeb"] ?? "generar";
		$passwordPlano = "";

		if($modo == "manual"){
			$passwordPlano = trim($_POST["passwordWebManual"] ?? "");
			$confirmar = trim($_POST["passwordWebConfirmar"] ?? "");
			if($passwordPlano !== $confirmar || !preg_match('/^[a-zA-Z0-9]{6,20}$/', $passwordPlano)){
				echo '<script>swal({type:"error",title:"Contrasena invalida",text:"Debe tener de 6 a 20 letras o numeros y coincidir.",confirmButtonText:"Cerrar"});</script>';
				return;
			}
		}else{
			$passwordPlano = self::ctrGenerarPasswordWeb();
		}

		$respuesta = ModeloClientes::mdlActualizarPasswordWeb("clientes", $idCliente, self::ctrHashPasswordWeb($passwordPlano));

		if($respuesta == "ok"){
			self::ctrEnviarCorreoClienteWeb($cliente, $passwordPlano);

			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("password_web", "clientes", "Clave web actualizada para cliente ".$cliente["nombre"]);
			}

			$textoCorreo = (!empty($cliente["email"]) && filter_var($cliente["email"], FILTER_VALIDATE_EMAIL))
				? "Si el correo esta configurado, se envio la nueva clave. Tambien queda registrada en correos pendientes si el servidor no puede enviar."
				: "El cliente no tiene correo valido. Entregue la clave manualmente.";

			echo '<script>
				swal({
					type: "success",
					title: "Clave web actualizada",
					html: "'.$textoCorreo.'<br><br><b>Clave temporal:</b> '.htmlspecialchars($passwordPlano, ENT_QUOTES, "UTF-8").'",
					confirmButtonText: "Cerrar"
				}).then(function(result){
					window.location = "clientes";
				});
			</script>';
		}
	}

}
