<?php

class ControladorUsuarios{

	static private function ctrHashPassword($password){
		return crypt($password, '$2a$07$asxx54ahjppf45sd87a5a4dDDGsystemdev$');
	}

	static private function ctrGenerarPasswordTemporal(){
		$caracteres = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789";
		$password = "";
		for($i = 0; $i < 10; $i++){
			$password .= $caracteres[random_int(0, strlen($caracteres) - 1)];
		}
		return $password;
	}

	static private function ctrNormalizarTextoUsuario($texto){
		$texto = trim((string)$texto);
		$mapa = array(
			"á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u", "ñ" => "n",
			"Á" => "a", "É" => "e", "Í" => "i", "Ó" => "o", "Ú" => "u", "Ñ" => "n"
		);
		$texto = strtr($texto, $mapa);
		$texto = strtolower($texto);
		return preg_replace('/[^a-z0-9 ]/', '', $texto);
	}

	static private function ctrGenerarUsuarioAutomatico($nombre, $documento = ""){
		$nombreLimpio = self::ctrNormalizarTextoUsuario($nombre);
		$partes = array_values(array_filter(explode(" ", $nombreLimpio)));
		$usuarioBase = "";

		if(count($partes) >= 2){
			for($i = 0; $i < count($partes) - 1; $i++){
				$usuarioBase .= substr($partes[$i], 0, 1);
			}
			$usuarioBase .= $partes[count($partes) - 1];
		}else if(count($partes) == 1){
			$usuarioBase = $partes[0];
		}

		$documentoLimpio = preg_replace('/\D/', '', (string)$documento);
		if($documentoLimpio != ""){
			$usuarioBase .= substr($documentoLimpio, -3);
		}

		$usuarioBase = substr($usuarioBase, 0, 35);
		if($usuarioBase == ""){
			$usuarioBase = "usuario";
		}

		$usuarioFinal = $usuarioBase;
		$contador = 2;
		while(self::ctrMostrarUsuarios("usuario", $usuarioFinal)){
			$sufijo = (string)$contador;
			$usuarioFinal = substr($usuarioBase, 0, 50 - strlen($sufijo)).$sufijo;
			$contador++;
		}

		return $usuarioFinal;
	}

	static private function ctrBaseUrl(){
		$protocolo = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
		$host = $_SERVER["HTTP_HOST"] ?? "localhost";
		$script = $_SERVER["SCRIPT_NAME"] ?? "/techmind/index.php";
		$base = rtrim(str_replace("\\", "/", dirname($script)), "/");
		return $protocolo."://".$host.$base;
	}

	static private function ctrEnviarCorreoSistema($destino, $asunto, $mensajeHtml){
		if(empty($destino) || !filter_var($destino, FILTER_VALIDATE_EMAIL)){
			return false;
		}

		$cabeceras = "MIME-Version: 1.0\r\n";
		$cabeceras .= "Content-type:text/html;charset=UTF-8\r\n";
		$cabeceras .= "From: TechMind <no-reply@techmind.local>\r\n";

		$enviado = @mail($destino, $asunto, $mensajeHtml, $cabeceras);

		if(!$enviado){
			$directorioLog = __DIR__ . "/../extensiones/logs";
			if(!is_dir($directorioLog)){
				mkdir($directorioLog, 0755, true);
			}
			file_put_contents(
				$directorioLog . "/correos-pendientes.log",
				"[".date("Y-m-d H:i:s")."] PARA: ".$destino." | ASUNTO: ".$asunto.PHP_EOL.strip_tags(str_replace(["<br>", "<br/>", "<br />"], PHP_EOL, $mensajeHtml)).PHP_EOL.PHP_EOL,
				FILE_APPEND
			);
		}

		return true;
	}

	static private function ctrEnviarCredencialesUsuario($usuario, $passwordTemporal){
		$link = self::ctrBaseUrl()."/";
		$mensaje = '
			<h2>Acceso a TechMind</h2>
			<p>Hola <b>'.htmlspecialchars($usuario["nombre"], ENT_QUOTES, "UTF-8").'</b>, se creo tu usuario en el sistema.</p>
			<p><b>Usuario:</b> '.htmlspecialchars($usuario["usuario"], ENT_QUOTES, "UTF-8").'</p>
			<p><b>Contrasena temporal:</b> '.htmlspecialchars($passwordTemporal, ENT_QUOTES, "UTF-8").'</p>
			<p>Ingresa desde: <a href="'.$link.'">'.$link.'</a></p>
			<p>Al iniciar sesion el sistema te pedira cambiar esta contrasena por una personal.</p>
		';

		return self::ctrEnviarCorreoSistema($usuario["email"], "Acceso a TechMind", $mensaje);
	}

	static private function ctrIpCliente(){
		if(!empty($_SERVER["HTTP_CLIENT_IP"])){
			return $_SERVER["HTTP_CLIENT_IP"];
		}
		if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
			$ips = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
			return trim($ips[0]);
		}
		return $_SERVER["REMOTE_ADDR"] ?? "desconocida";
	}

	static private function ctrSesionVigente($fechaUltimaActividad){
		if(empty($fechaUltimaActividad)){
			return false;
		}
		date_default_timezone_set("America/La_Paz");
		return strtotime($fechaUltimaActividad) >= strtotime("-30 minutes");
	}

	static private function ctrModalSesionCerrada($titulo, $texto, $tipo = "warning"){
		echo '<script>
			swal({
				type: "'.$tipo.'",
				title: "'.$titulo.'",
				text: "'.$texto.'",
				confirmButtonText: "Ingresar nuevamente",
				allowOutsideClick: false,
				allowEscapeKey: false
			}).then(function(){
				window.location = "ingreso";
			});
		</script>';
	}

	static public function ctrValidarSesionActual(){
		if(!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok"){
			return true;
		}

		if(!isset($_SESSION["id"]) || !isset($_SESSION["session_token"])){
			session_destroy();
			self::ctrModalSesionCerrada("Sesion finalizada", "Debe ingresar nuevamente para continuar.");
			return false;
		}

		$tabla = "usuarios";
		ModeloUsuarios::mdlAsegurarColumnasSesion($tabla);
		$usuario = ModeloUsuarios::mdlMostrarUsuarios($tabla, "id", $_SESSION["id"]);

		if(!$usuario ||
		   (int)($usuario["sesion_activa"] ?? 0) !== 1 ||
		   empty($usuario["session_token"]) ||
		   $usuario["session_token"] !== $_SESSION["session_token"]){

			session_destroy();
			self::ctrModalSesionCerrada("Sesion cerrada", "Tu sesion ya no esta activa o fue cerrada desde otro proceso.");
			return false;
		}

		if(!self::ctrSesionVigente($usuario["session_last_activity"] ?? null)){
			ModeloUsuarios::mdlLiberarSesion($tabla, (int)$_SESSION["id"], $_SESSION["session_token"]);
			session_destroy();
			self::ctrModalSesionCerrada("Sesion expirada", "Tu sesion expiro por inactividad. Vuelve a ingresar.");
			return false;
		}

		date_default_timezone_set("America/La_Paz");
		ModeloUsuarios::mdlActualizarActividadSesion($tabla, (int)$_SESSION["id"], $_SESSION["session_token"], date("Y-m-d H:i:s"));

		return true;
	}

	static public function ctrCerrarSesionActual(){
		if(isset($_SESSION["id"]) && isset($_SESSION["session_token"])){
			ModeloUsuarios::mdlAsegurarColumnasSesion("usuarios");
			ModeloUsuarios::mdlLiberarSesion("usuarios", (int)$_SESSION["id"], $_SESSION["session_token"]);
		}
		session_destroy();
	}

	/*=============================================
	INGRESO DE USUARIO
	=============================================*/

	static public function ctrIngresoUsuario(){

		if(isset($_POST["ingUsuario"])){

			if(preg_match('/^[a-zA-Z0-9._@+-]+$/', $_POST["ingUsuario"]) &&
			   preg_match('/^[a-zA-Z0-9]+$/', $_POST["ingPassword"])){

			   	$encriptar = self::ctrHashPassword($_POST["ingPassword"]);

				$tabla = "usuarios";
				ModeloUsuarios::mdlAsegurarColumnasSesion($tabla);

				$valor = trim($_POST["ingUsuario"]);

				$respuesta = ModeloUsuarios::mdlMostrarUsuarioPorLogin($tabla, $valor);

				if($respuesta && $respuesta["password"] == $encriptar){

					if($respuesta["estado"] == 1){

						date_default_timezone_set('America/La_Paz');
						$fechaActual = date('Y-m-d H:i:s');
						$tokenSesionActual = $respuesta["session_token"] ?? "";
						$sesionActiva = (int)($respuesta["sesion_activa"] ?? 0) === 1;

						if($sesionActiva && !empty($tokenSesionActual) && self::ctrSesionVigente($respuesta["session_last_activity"] ?? null)){
							$ipActiva = htmlspecialchars($respuesta["session_ip"] ?? "otra maquina", ENT_QUOTES, "UTF-8");
							echo '<br><div class="alert alert-warning">
								Este usuario ya tiene una sesion activa. Cierre sesion en el otro equipo o espere 30 minutos de inactividad.
								<br><b>IP registrada:</b> '.$ipActiva.'
							</div>';
							if(class_exists("ControladorLogs")){
								ControladorLogs::ctrRegistrarLog("login_bloqueado", "login", "Intento de ingreso bloqueado por sesion activa de ".$respuesta["usuario"]);
							}
							return;
						}

						if($sesionActiva){
							ModeloUsuarios::mdlLiberarSesion($tabla, (int)$respuesta["id"]);
						}

						$tokenSesion = bin2hex(random_bytes(32));
						$ipSesion = self::ctrIpCliente();
						$userAgent = substr($_SERVER["HTTP_USER_AGENT"] ?? "", 0, 250);
						ModeloUsuarios::mdlRegistrarSesionActiva($tabla, (int)$respuesta["id"], $tokenSesion, $ipSesion, $userAgent, $fechaActual);

						$_SESSION["iniciarSesion"] = "ok";
						$_SESSION["id"] = $respuesta["id"];
						$_SESSION["nombre"] = $respuesta["nombre"];
						$_SESSION["usuario"] = $respuesta["usuario"];
						$_SESSION["foto"] = $respuesta["foto"];
						$_SESSION["perfil"] = $respuesta["perfil"];
						$_SESSION["debe_cambiar_password"] = (int)($respuesta["debe_cambiar_password"] ?? 0);
						$_SESSION["rol"] = $respuesta["rol"]; // Guarda el rol en la sesión
						$_SESSION["session_token"] = $tokenSesion;
						$_SESSION["session_ip"] = $ipSesion;

						/*=============================================
						REGISTRAR FECHA PARA SABER EL ÚLTIMO LOGIN
						=============================================*/

						date_default_timezone_set('America/La_Paz');

						$fecha = date('Y-m-d');
						$hora = date('H:i:s');

						$fechaActual = $fecha.' '.$hora;

						$item1 = "ultimo_login";
						$valor1 = $fechaActual;

						$item2 = "id";
						$valor2 = $respuesta["id"];

						$ultimoLogin = ModeloUsuarios::mdlActualizarUsuario($tabla, $item1, $valor1, $item2, $valor2);

						if($ultimoLogin == "ok"){
							if (class_exists("ControladorLogs")) {
								ControladorLogs::ctrRegistrarLog("inicio_sesion", "login", "Inicio de sesión correcto");
							}

							$rutaIngreso = ((int)($respuesta["debe_cambiar_password"] ?? 0) === 1)
								? "cambiar-password"
								: (($respuesta["rol"] ?? "") === "cajero" ? "caja" : "inicio");

							echo '<script>

								window.location = "'.$rutaIngreso.'";

							</script>';

						}				
						
					}else{

						echo '<br>
							<div class="alert alert-danger">El usuario aún no está activado</div>';

					}		

				}else{

					echo '<br><div class="alert alert-danger">Error al ingresar, vuelve a intentarlo</div>';

				}

			}	

		}

	}

	/*=============================================
	/*=============================================
REGISTRO DE USUARIO
=============================================*/
static public function ctrCrearUsuario() {

    if (isset($_POST["nuevoUsuario"])) {

        $_POST["nuevoUsuario"] = self::ctrGenerarUsuarioAutomatico($_POST["nuevoNombre"] ?? "", $_POST["nuevoDocumentoUsuario"] ?? "");

        if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nuevoNombre"]) &&
            preg_match('/^[a-zA-Z0-9]+$/', $_POST["nuevoUsuario"]) &&
            in_array($_POST["nuevoPerfil"], ['Administrador', 'Especial', 'Vendedor']) &&
            in_array($_POST["nuevoRol"], ['vendedor', 'cajero', 'almacen', 'mensajero', 'tecnico', 'desarrollador']) &&
            filter_var($_POST["nuevoEmail"], FILTER_VALIDATE_EMAIL)) {

            /*=============================================
            VALIDAR IMAGEN
            =============================================*/
            $ruta = "";

            if (isset($_FILES["nuevaFoto"]["tmp_name"]) && !empty($_FILES["nuevaFoto"]["tmp_name"])) {

                list($ancho, $alto) = getimagesize($_FILES["nuevaFoto"]["tmp_name"]);

                $nuevoAncho = 500;
                $nuevoAlto = 500;

                /*=============================================
                CREAMOS EL DIRECTORIO DONDE VAMOS A GUARDAR LA FOTO DEL USUARIO
                =============================================*/
                $directorio = "vistas/img/usuarios/" . $_POST["nuevoUsuario"];
                if(!is_dir($directorio)){
                    mkdir($directorio, 0755);
                }

                /*=============================================
                PROCESAMOS LA IMAGEN SEGÚN SU TIPO
                =============================================*/
                if ($_FILES["nuevaFoto"]["type"] == "image/jpeg") {
                    $aleatorio = mt_rand(100, 999);
                    $ruta = "vistas/img/usuarios/" . $_POST["nuevoUsuario"] . "/" . $aleatorio . ".jpg";
                    $origen = imagecreatefromjpeg($_FILES["nuevaFoto"]["tmp_name"]);
                    $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                    imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
                    imagejpeg($destino, $ruta);
                }

                if ($_FILES["nuevaFoto"]["type"] == "image/png") {
                    $aleatorio = mt_rand(100, 999);
                    $ruta = "vistas/img/usuarios/" . $_POST["nuevoUsuario"] . "/" . $aleatorio . ".png";
                    $origen = imagecreatefrompng($_FILES["nuevaFoto"]["tmp_name"]);
                    $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                    imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
                    imagepng($destino, $ruta);
                }
            }

            $tabla = "usuarios";
            $passwordTemporal = self::ctrGenerarPasswordTemporal();
            $encriptar = self::ctrHashPassword($passwordTemporal);

            $datos = array(
                "nombre" => $_POST["nuevoNombre"],
                "email" => trim($_POST["nuevoEmail"]),
                "usuario" => $_POST["nuevoUsuario"],
                "password" => $encriptar,
                "debe_cambiar_password" => 1,
                "perfil" => $_POST["nuevoPerfil"],
                "rol" => $_POST["nuevoRol"], // Nuevo campo rol
                "foto" => $ruta
            );

            $respuesta = ModeloUsuarios::mdlIngresarUsuario($tabla, $datos);

            if ($respuesta == "ok") {
                self::ctrEnviarCredencialesUsuario($datos, $passwordTemporal);
                if (class_exists("ControladorLogs")) {
                    ControladorLogs::ctrRegistrarLog("crear", "usuarios", "Usuario ".$_POST["nuevoUsuario"]." creado");
                }
                echo '<script>
                swal({
                    type: "success",
                    title: "¡El usuario ha sido guardado correctamente!",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then(function(result){
                    if(result.value){
                        window.location = "usuarios";
                    }
                });
                </script>';
            }
        } else {
            echo '<script>
            swal({
                type: "error",
                title: "¡El usuario no puede ir vacío o llevar caracteres especiales!",
                showConfirmButton: true,
                confirmButtonText: "Cerrar"
            }).then(function(result){
                if(result.value){
                    window.location = "usuarios";
                }
            });
            </script>';
        }
    }
}

	static public function ctrSolicitarRecuperacionPassword(){

		if(!isset($_POST["recuperarEmail"])){
			return;
		}

		$email = trim($_POST["recuperarEmail"]);

		if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
			echo '<div class="alert alert-danger">Ingrese un correo valido.</div>';
			return;
		}

		$tabla = "usuarios";
		$usuario = ModeloUsuarios::mdlMostrarUsuarioPorLogin($tabla, $email);

		if($usuario && !empty($usuario["email"])){
			$token = bin2hex(random_bytes(32));
			$tokenHash = hash("sha256", $token);
			$expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

			ModeloUsuarios::mdlGuardarTokenRecuperacion($tabla, (int)$usuario["id"], $tokenHash, $expira);

			$link = self::ctrBaseUrl()."/index.php?recuperarPassword=".$token;
			$mensaje = '
				<h2>Recuperar contrasena</h2>
				<p>Hola <b>'.htmlspecialchars($usuario["nombre"], ENT_QUOTES, "UTF-8").'</b>, recibimos una solicitud para cambiar tu contrasena.</p>
				<p>Usa este enlace durante la siguiente hora:</p>
				<p><a href="'.$link.'">'.$link.'</a></p>
				<p>Si no solicitaste este cambio, ignora este mensaje.</p>
			';

			self::ctrEnviarCorreoSistema($usuario["email"], "Recuperar contrasena TechMind", $mensaje);

			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("recuperacion_password", "usuarios", "Solicitud de recuperacion para ".$usuario["usuario"]);
			}
		}

		echo '<div class="alert alert-success">Si el correo esta registrado, se envio un enlace para cambiar la contrasena.</div>';
	}

	static public function ctrRestablecerPassword(){

		if(!isset($_POST["tokenPassword"]) || !isset($_POST["nuevoPasswordReset"])){
			return;
		}

		$password = $_POST["nuevoPasswordReset"];
		$confirmar = $_POST["confirmarPasswordReset"] ?? "";

		if($password !== $confirmar || !preg_match('/^[a-zA-Z0-9]{6,20}$/', $password)){
			echo '<div class="alert alert-danger">Las contrasenas deben coincidir y tener de 6 a 20 letras o numeros.</div>';
			return;
		}

		$tabla = "usuarios";
		$tokenHash = hash("sha256", $_POST["tokenPassword"]);
		$usuario = ModeloUsuarios::mdlMostrarUsuarioPorToken($tabla, $tokenHash);

		if(!$usuario){
			echo '<div class="alert alert-danger">El enlace ya expiro o no es valido. Solicite uno nuevo.</div>';
			return;
		}

		$hash = self::ctrHashPassword($password);
		$respuesta = ModeloUsuarios::mdlActualizarPasswordUsuario($tabla, (int)$usuario["id"], $hash, 0);

		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("cambio_password", "usuarios", "Password restablecido para ".$usuario["usuario"]);
			}

			echo '<script>
				swal({
					type: "success",
					title: "Contrasena actualizada",
					text: "Ya puede ingresar con su nueva contrasena.",
					confirmButtonText: "Ingresar"
				}).then(function(result){
					window.location = "index.php";
				});
			</script>';
		}
	}

	static public function ctrCambiarPasswordObligatorio(){

		if(!isset($_POST["passwordActualObligatorio"])){
			return;
		}

		if(!isset($_SESSION["id"])){
			echo '<script>window.location = "salir";</script>';
			return;
		}

		$actual = $_POST["passwordActualObligatorio"];
		$nueva = $_POST["nuevaPasswordObligatoria"] ?? "";
		$confirmar = $_POST["confirmarPasswordObligatoria"] ?? "";

		if($nueva !== $confirmar || !preg_match('/^[a-zA-Z0-9]{6,20}$/', $nueva)){
			echo '<script>swal({type:"error",title:"Contrasena invalida",text:"Debe tener de 6 a 20 letras o numeros y coincidir.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$tabla = "usuarios";
		$usuario = ModeloUsuarios::mdlMostrarUsuarios($tabla, "id", $_SESSION["id"]);

		if(!$usuario || $usuario["password"] !== self::ctrHashPassword($actual)){
			echo '<script>swal({type:"error",title:"La contrasena temporal no coincide",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$respuesta = ModeloUsuarios::mdlActualizarPasswordUsuario($tabla, (int)$_SESSION["id"], self::ctrHashPassword($nueva), 0);

		if($respuesta == "ok"){
			$_SESSION["debe_cambiar_password"] = 0;
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("cambio_password", "usuarios", "Cambio obligatorio de password completado");
			}
			echo '<script>
				swal({
					type:"success",
					title:"Contrasena actualizada",
					confirmButtonText:"Continuar"
				}).then(function(result){
					window.location = "inicio";
				});
			</script>';
		}
	}


	/*=============================================
	MOSTRAR USUARIO
	=============================================*/

	static public function ctrMostrarUsuarios($item, $valor){

		$tabla = "usuarios";

		$respuesta = ModeloUsuarios::MdlMostrarUsuarios($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
EDITAR USUARIO
=============================================*/
static public function ctrEditarUsuario() {

    if (isset($_POST["editarUsuario"])) {

        if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["editarNombre"]) &&
            in_array($_POST["editarRol"], ['vendedor', 'cajero', 'almacen', 'mensajero', 'tecnico', 'desarrollador']) &&
            ($_POST["editarEmail"] == "" || filter_var($_POST["editarEmail"], FILTER_VALIDATE_EMAIL))) {

            /*=============================================
            VALIDAR IMAGEN
            =============================================*/
            $ruta = $_POST["fotoActual"];

            if (isset($_FILES["editarFoto"]["tmp_name"]) && !empty($_FILES["editarFoto"]["tmp_name"])) {

                list($ancho, $alto) = getimagesize($_FILES["editarFoto"]["tmp_name"]);

                $nuevoAncho = 500;
                $nuevoAlto = 500;

                /*=============================================
                CREAMOS EL DIRECTORIO DONDE VAMOS A GUARDAR LA FOTO DEL USUARIO
                =============================================*/
                $directorio = "vistas/img/usuarios/" . $_POST["editarUsuario"];

                /*=============================================
                PRIMERO PREGUNTAMOS SI EXISTE OTRA IMAGEN EN LA BD
                =============================================*/
                if (!empty($_POST["fotoActual"])) {
                    unlink($_POST["fotoActual"]);
                } else {
                    mkdir($directorio, 0755);
                }

                /*=============================================
                PROCESAMOS LA IMAGEN SEGÚN SU TIPO
                =============================================*/
                if ($_FILES["editarFoto"]["type"] == "image/jpeg") {
                    $aleatorio = mt_rand(100, 999);
                    $ruta = "vistas/img/usuarios/" . $_POST["editarUsuario"] . "/" . $aleatorio . ".jpg";
                    $origen = imagecreatefromjpeg($_FILES["editarFoto"]["tmp_name"]);
                    $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                    imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
                    imagejpeg($destino, $ruta);
                }

                if ($_FILES["editarFoto"]["type"] == "image/png") {
                    $aleatorio = mt_rand(100, 999);
                    $ruta = "vistas/img/usuarios/" . $_POST["editarUsuario"] . "/" . $aleatorio . ".png";
                    $origen = imagecreatefrompng($_FILES["editarFoto"]["tmp_name"]);
                    $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                    imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
                    imagepng($destino, $ruta);
                }
            }

            $tabla = "usuarios";

            if ($_POST["editarPassword"] != "") {

                if (preg_match('/^[a-zA-Z0-9]+$/', $_POST["editarPassword"])) {
                    $encriptar = self::ctrHashPassword($_POST["editarPassword"]);
                } else {
                    echo '<script>
                        swal({
                            type: "error",
                            title: "¡La contraseña no puede ir vacía o llevar caracteres especiales!",
                            showConfirmButton: true,
                            confirmButtonText: "Cerrar"
                        }).then(function(result) {
                            if (result.value) {
                                window.location = "usuarios";
                            }
                        })
                    </script>';
                    return;
                }

            } else {
                $encriptar = $_POST["passwordActual"];
            }

            $datos = array(
                "id" => $_POST["idUsuario"],
                "nombre" => $_POST["editarNombre"],
                "email" => trim($_POST["editarEmail"]),
                "usuario" => $_POST["editarUsuario"],
                "password" => $encriptar,
                "perfil" => $_POST["editarPerfil"],
                "rol" => $_POST["editarRol"], // Nuevo campo rol
                "foto" => $ruta
            );

            $respuesta = ModeloUsuarios::mdlEditarUsuario($tabla, $datos);

            if ($respuesta == "ok") {
                if (class_exists("ControladorLogs")) {
                    ControladorLogs::ctrRegistrarLog("editar", "usuarios", "Usuario ".$_POST["editarUsuario"]." actualizado");
                }
                echo '<script>
                    swal({
                        type: "success",
                        title: "El usuario ha sido editado correctamente",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then(function(result) {
                        if (result.value) {
                            window.location = "usuarios";
                        }
                    })
                </script>';
            }

        } else {
            echo '<script>
                swal({
                    type: "error",
                    title: "¡Error en los datos ingresados!",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then(function(result) {
                    if (result.value) {
                        window.location = "usuarios";
                    }
                })
            </script>';
        }
    }
}


	/*=============================================
	BORRAR USUARIO
	=============================================*/

	static public function ctrBorrarUsuario(){

		if(isset($_GET["idUsuario"])){

			$tabla ="usuarios";
			$datos = $_GET["idUsuario"];

			if($_GET["fotoUsuario"] != ""){

				unlink($_GET["fotoUsuario"]);
				rmdir('vistas/img/usuarios/'.$_GET["usuario"]);

			}

			$respuesta = ModeloUsuarios::mdlBorrarUsuario($tabla, $datos);

			if($respuesta == "ok"){
				if (class_exists("ControladorLogs")) {
					ControladorLogs::ctrRegistrarLog("eliminar", "usuarios", "Usuario ".$_GET["usuario"]." eliminado");
				}

				echo'<script>

				swal({
					  type: "success",
					  title: "El usuario ha sido borrado correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar",
					  closeOnConfirm: false
					  }).then(function(result) {
								if (result.value) {

								window.location = "usuarios";

								}
							})

				</script>';

			}else{

				echo'<script>

					swal({
						  type: "error",
						  title: "¡Este usuario no se puede borrar, tiene ventas registradas, puedes desactivarlo",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result) {
							if (result.value) {

							window.location = "usuarios";

							}
						})

			  	</script>';

			}		

		}

	}


}
	

