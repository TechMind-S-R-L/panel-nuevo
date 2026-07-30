<?php

class ControladorProductos{

	static private function ctrProcesarImagenProducto($campoArchivo, $codigoProducto, $rutaActual = ""){
		$rutaDefault = "vistas/img/productos/default/anonymous.png";

		if(!isset($_FILES[$campoArchivo]) || ($_FILES[$campoArchivo]["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || empty($_FILES[$campoArchivo]["tmp_name"])){
			return $rutaActual !== "" ? $rutaActual : $rutaDefault;
		}

		if(($_FILES[$campoArchivo]["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES[$campoArchivo]["tmp_name"])){
			return false;
		}

		$infoImagen = @getimagesize($_FILES[$campoArchivo]["tmp_name"]);
		$mimeImagen = strtolower((string)($infoImagen["mime"] ?? ""));
		if(!$infoImagen || !in_array($mimeImagen, array("image/jpeg", "image/jpg", "image/pjpeg", "image/png"), true)){
			return false;
		}

		$ancho = (int)$infoImagen[0];
		$alto = (int)$infoImagen[1];
		if($ancho <= 0 || $alto <= 0){
			return false;
		}

		$directorio = "vistas/img/productos/".$codigoProducto;
		if(!is_dir($directorio) && !mkdir($directorio, 0755, true)){
			return false;
		}

		$aleatorio = date("YmdHis")."-".mt_rand(100,999);
		if($mimeImagen === "image/png"){
			$ruta = $directorio."/".$aleatorio.".png";
		}else{
			$ruta = $directorio."/".$aleatorio.".jpg";
		}

		if(!move_uploaded_file($_FILES[$campoArchivo]["tmp_name"], $ruta)){
			return false;
		}

		if($rutaActual !== "" && $rutaActual !== $rutaDefault && $rutaActual !== $ruta && is_file($rutaActual)){
			@unlink($rutaActual);
		}

		return $ruta;
	}

	static public function ctrMostrarMarcasActivas(){
		return ModeloProductos::mdlMostrarMarcasActivas();
	}

	static public function ctrCrearMarcaRapida($nombre, $descripcion = ""){
		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "almacen"){
			return array("status" => "error", "message" => "No tiene permiso para crear marcas.");
		}

		$nombre = trim((string)$nombre);
		$descripcion = trim((string)$descripcion);
		$largoNombre = function_exists("mb_strlen") ? mb_strlen($nombre, "UTF-8") : strlen($nombre);
		if($nombre === "" || $largoNombre > 100){
			return array("status" => "error", "message" => "Ingrese un nombre de marca valido.");
		}

		$nombre = function_exists("mb_strtoupper") ? mb_strtoupper($nombre, "UTF-8") : strtoupper($nombre);
		$respuesta = ModeloProductos::mdlCrearMarcaRapida($nombre, $descripcion);
		if(($respuesta["status"] ?? "") === "exists"){
			$respuesta["message"] = "La marca ya estaba registrada y fue seleccionada.";
		}else if(($respuesta["status"] ?? "") === "ok"){
			$respuesta["message"] = "Marca creada correctamente.";
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("crear", "marcas", "Marca ".$nombre." creada desde productos");
			}
		}else{
			$respuesta["message"] = "No se pudo guardar la marca.";
		}
		return $respuesta;
	}

	/*=============================================
	MOSTRAR PRODUCTOS
	=============================================*/

	static public function ctrMostrarProductos($item, $valor, $orden){

		$tabla = "productos";

		$respuesta = ModeloProductos::mdlMostrarProductos($tabla, $item, $valor, $orden);

		return $respuesta;

	}

	static public function ctrMostrarProductosfiltrados(){
		$tabla = "productos";
		return ModeloProductos::mdlMostrarProductosFiltrados($tabla);
	}

	static public function ctrMostrarProductosAlmacen(){
		return ModeloProductos::mdlMostrarProductosAlmacen();
	}

	static public function ctrMostrarProductosDisponiblesVenta(){
		$tabla = "productos";
		return ModeloProductos::mdlMostrarProductosDisponiblesVenta($tabla);
	}

	static public function ctrMostrarCodigosUnicosProducto($idProducto){
		return ModeloProductos::mdlMostrarCodigosUnicosProducto((int)$idProducto);
	}

	static public function ctrMostrarPendientesPrecio(){
		$tabla = "productos";
		return ModeloProductos::mdlMostrarProductosFiltrados($tabla);
	}
	
	

	/*=============================================
	CREAR PRODUCTO
	=============================================*/

	static public function ctrCrearProducto(){

		if(isset($_POST["nuevaDescripcion"])){
			$codigoTechMind = ModeloProductos::mdlNormalizarCodigoTechMind($_POST["nuevoCodigo"] ?? "");
			$_POST["nuevoCodigo"] = $codigoTechMind;
			$_POST["nuevaDescripcion"] = ModeloProductos::mdlNormalizarNombreProducto($_POST["nuevaDescripcion"]);
			if ($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "almacen") {
				echo '<script>window.location = "inicio";</script>';
				return;
			}

			if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ.,¡!¿?¿()@#$%^&*_\-+=\[\]{}:;"\'<>,\/\|\\ ]+$/', $_POST["nuevaDescripcion"]) &&
			   preg_match('/^[0-9]+$/', $_POST["nuevoStock"]) &&	
			   preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ.,¡!¿?¿()@#$%^&*_\-+=\[\]{}:;"\'<>,\/\|\\ ]+$/', $_POST["nuevoCodigoGenerico"]) &&
			   preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ.,¡!¿?¿()@#$%^&*_\-+=\[\]{}:;"\'<>,\/\|\\ ]+$/', $_POST["nuevoCodigoUnico"]) && 
			   preg_match('/^[0-9.]+$/', $_POST["nuevoPrecioCompra"]) &&
			   preg_match('/^[0-9.]+$/', $_POST["nuevoPrecioVenta"])){


		   		/*=============================================
				VALIDAR IMAGEN
				=============================================*/

			   	$ruta = self::ctrProcesarImagenProducto("nuevaImagen", $codigoTechMind);
				if($ruta === false){
					echo '<script>
						swal({
							type: "error",
							title: "Error al subir la imagen",
							text: "La imagen debe ser JPG, JPEG o PNG y el servidor debe poder guardarla.",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						});
					</script>';
					return;
				}

				$tabla = "productos";

				$datos = array(
					"id_categoria" => $_POST["nuevaCategoria"],
					"id_marca" => (int)($_POST["nuevaMarca"] ?? 0),
					"codigo" => $codigoTechMind,
					"codigo_producto_generico" => $_POST["nuevoCodigoGenerico"],
					"codigo_barras_unico" => $_POST["nuevoCodigoUnico"],
					"descripcion" => $_POST["nuevaDescripcion"],
					"detalle" => trim((string)($_POST["nuevoDetalle"] ?? "")),
					"stock" => 0,
					"precio_compra" => 0,
					"precio_venta" => 0,
					"imagen" => $ruta
				  );

				  $respuesta = ModeloProductos::mdlIngresarProducto($tabla, $datos);

			// 	if($respuesta == "ok"){

			// 		echo'<script>

			// 			swal({
			// 				  type: "success",
			// 				  title: "El producto ha sido guardado correctamente",
			// 				  showConfirmButton: true,
			// 				  confirmButtonText: "Cerrar"
			// 				  }).then(function(result){
			// 							if (result.value) {

			// 							window.location = "productos";

			// 							}
			// 						})

			// 			</script>';

			// 	}


			// }else{

			// 	echo'<script>

			// 		swal({
			// 			  type: "error",
			// 			  title: "¡El producto no puede ir con los campos vacíos o llevar caracteres especiales!",
			// 			  showConfirmButton: true,
			// 			  confirmButtonText: "Cerrar"
			// 			  }).then(function(result){
			// 				if (result.value) {

			// 				window.location = "productos";

			// 				}
			// 			})

			//   	</script>';
			// }

			if ($respuesta == "ok") {

				// Verificar el perfil del usuario desde la sesión
				$retornoProducto = $_POST["retornoProducto"] ?? "";
				$retornosPermitidos = array("productos", "productos-almacen", "ordenes-ingreso-material");
				if(in_array($retornoProducto, $retornosPermitidos, true)){
					$urlRedireccion = $retornoProducto;
				}else if ($_SESSION["rol"] == "almacen") {
					$urlRedireccion = "ordenes-ingreso-material";
				} else {
					$urlRedireccion = "productos";
				}
			
				echo '<script>
						swal({
							type: "success",
							title: "El producto ha sido guardado correctamente",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then(function(result) {
							if (result.value) {
								window.location = "' . $urlRedireccion. '";
							}
						});
					  </script>';
			
			} else {
			
				echo '<script>
						swal({
							type: "error",
							title: "¡El producto no puede ir con los campos vacíos o llevar caracteres especiales!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then(function(result) {
							if (result.value) {
								window.location = "productos.php";
							}
						});
					  </script>';
					}
			}
			
		}

	}

	/*=============================================
	EDITAR PRODUCTO
	=============================================*/

	static public function ctrEditarProducto(){

		if(isset($_POST["editarDescripcion"])){
			$_POST["editarCodigo"] = ModeloProductos::mdlNormalizarCodigoTechMind($_POST["editarCodigo"] ?? "");
			$_POST["editarDescripcion"] = ModeloProductos::mdlNormalizarNombreProducto($_POST["editarDescripcion"]);
			if(isset($_POST["editarNuevoCodigo"])){
				$_POST["editarNuevoCodigo"] = ModeloProductos::mdlNormalizarCodigoTechMind($_POST["editarNuevoCodigo"]);
			}
	
			if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ.,¡!¿?¿()@#$%^&*_\-+=\[\]{}:;"\'<>,\/\|\\ ]+$/',  $_POST["editarCodigo"]) &&
			   preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ.,¡!¿?¿()@#$%^&*_\-+=\[\]{}:;"\'<>,\/\|\\ ]+$/', $_POST["editarDescripcion"]) && 
			   preg_match('/^[a-zA-Z0-9._-]+$/', $_POST["editarCodigoGenerico"]) &&
            	preg_match('/^[a-zA-Z0-9._-]+$/', $_POST["editarCodigoUnico"]) && 
			   preg_match('/^[0-9]+$/', $_POST["editarStock"]) &&   
			   preg_match('/^[0-9.]+$/', $_POST["editarPrecioCompra"]) &&
			   preg_match('/^[0-9.]+$/', $_POST["editarPrecioVenta"])){
	
				/*=============================================
				VALIDAR IMAGEN
				=============================================*/
	
				$codigoImagenProducto = isset($_POST["editarNuevoCodigo"]) ? $_POST["editarNuevoCodigo"] : $_POST["editarCodigo"];
				$ruta = self::ctrProcesarImagenProducto("editarImagen", $codigoImagenProducto, $_POST["imagenActual"]);
				if($ruta === false){
					echo '<script>
						swal({
							type: "error",
							title: "Error al subir la imagen",
							text: "La imagen debe ser JPG, JPEG o PNG y el servidor debe poder guardarla.",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						});
					</script>';
					return;
				}
	
				if(false && isset($_FILES["editarImagen"]["tmp_name"]) && !empty($_FILES["editarImagen"]["tmp_name"])){
	
					list($ancho, $alto) = getimagesize($_FILES["editarImagen"]["tmp_name"]);
	
					$nuevoAncho = 500;
					$nuevoAlto = 500;
	
					/*=============================================
					CREAMOS EL DIRECTORIO DONDE VAMOS A GUARDAR LA FOTO DEL USUARIO
					=============================================*/
	
					if(isset($_POST["editarNuevoCodigo"])){
						// Si existe un nuevo código, lo utilizamos para crear el directorio
						$directorio = "vistas/img/productos/".$_POST["editarNuevoCodigo"];
					} else {
						// Si no hay un nuevo código, utilizamos el código actual
						$directorio = "vistas/img/productos/".$_POST["editarCodigo"];
					}
	
					/*=============================================
					PRIMERO PREGUNTAMOS SI EXISTE OTRA IMAGEN EN LA BD
					=============================================*/
	
					if(!empty($_POST["imagenActual"]) && $_POST["imagenActual"] != "vistas/img/productos/default/anonymous.png"){
	
						unlink($_POST["imagenActual"]);
					}

					if(!is_dir($directorio)){
						mkdir($directorio, 0755, true);
					}
					
					/*=============================================
					DE ACUERDO AL TIPO DE IMAGEN APLICAMOS LAS FUNCIONES POR DEFECTO DE PHP
					=============================================*/
	
					if(in_array($_FILES["editarImagen"]["type"], array("image/jpeg", "image/jpg", "image/pjpeg"), true)){
	
						/*=============================================
						GUARDAMOS LA IMAGEN EN EL DIRECTORIO
						=============================================*/
	
						$aleatorio = mt_rand(100,999);
	
						$ruta = $directorio."/".$aleatorio.".jpg";
	
						$origen = imagecreatefromjpeg($_FILES["editarImagen"]["tmp_name"]);                        
	
						$destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
	
						imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
	
						imagejpeg($destino, $ruta);
	
					}
	
					if($_FILES["editarImagen"]["type"] == "image/png"){
	
						/*=============================================
						GUARDAMOS LA IMAGEN EN EL DIRECTORIO
						=============================================*/
	
						$aleatorio = mt_rand(100,999);
	
						$ruta = $directorio."/".$aleatorio.".png";
	
						$origen = imagecreatefrompng($_FILES["editarImagen"]["tmp_name"]);                        
	
						$destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
	
						imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
	
						imagepng($destino, $ruta);
	
					}
	
				}
	
				$tabla = "productos";
	
				// Verificamos si hay un nuevo código
				if(isset($_POST["editarNuevoCodigo"])){
					$datos = array("codigo" => $_POST["editarNuevoCodigo"]);
				} else {
					$datos = array("codigo" => $_POST["editarCodigo"]);
				}
	
				$datos = array(
					"id" => (int)($_POST["editarIdProducto"] ?? 0),
					"id_categoria" => $_POST["editarCategoria"],
					"id_marca" => (int)($_POST["editarMarca"] ?? 0),
					"codigo" => $_POST["editarCodigo"],
					"codigo_producto_generico" => $_POST["editarCodigoGenerico"],
					"codigo_barras_unico" => $_POST["editarCodigoUnico"],
					"descripcion" => $_POST["editarDescripcion"],
					"detalle" => trim((string)($_POST["editarDetalle"] ?? "")),
					"stock" => $_POST["editarStock"],
					"precio_compra" => $_POST["editarPrecioCompra"],
					"precio_venta" => $_POST["editarPrecioVenta"],
					"imagen" => $ruta
				  );
				  
				  $respuesta = ModeloProductos::mdlEditarProducto($tabla, $datos);
				  
	
			
	
			// 	if($respuesta == "ok"){
	
			// 		echo'<script>
	
			// 			swal({
			// 				  type: "success",
			// 				  title: "El producto ha sido editado correctamente",
			// 				  showConfirmButton: true,
			// 				  confirmButtonText: "Cerrar"
			// 				  }).then(function(result){
			// 							if (result.value) {
	
			// 							window.location = "productos";
	
			// 							}
			// 						})
	
			// 			</script>';
	
			// 	}
	
	
			// }else{
	
			// 	echo'<script>
	
			// 		swal({
			// 			  type: "error",
			// 			  title: "¡El producto no puede ir con los campos vacíos o llevar caracteres especiales!",
			// 			  showConfirmButton: true,
			// 			  confirmButtonText: "Cerrar"
			// 			  }).then(function(result){
			// 				if (result.value) {
	
			// 				window.location = "productos";
	
			// 				}
			// 			})
	
			// 	</script>';
			// }

			if ($respuesta == "ok") {

				// Verificar el perfil del usuario desde la sesión
				$retornoProducto = $_POST["retornoProducto"] ?? "";
				$retornosPermitidos = array("productos", "productos-almacen", "ordenes-ingreso-material");
				if(in_array($retornoProducto, $retornosPermitidos, true)){
					$urlRedireccion = $retornoProducto;
				}else if ($_SESSION["rol"] == "almacen") {
					$urlRedireccion = "productos-almacen";
				} else {
					$urlRedireccion = "productos";
				}
			
				echo '<script>
						swal({
							type: "success",
							title: "El producto ha sido guardado correctamente",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then(function(result) {
							if (result.value) {
								window.location = "' . $urlRedireccion . '";
							}
						});
					  </script>';
			
			} else {
			
				echo '<script>
						swal({
							type: "error",
							title: "¡El producto no puede ir con los campos vacíos o llevar caracteres especiales!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then(function(result) {
							if (result.value) {
								window.location = "productos.php";
							}
						});
					  </script>';
					}
			}
			
		}
	
	}

/* =============================================
       Método para obtener la descripción del producto
    ============================================= */
    static public function ctrMostrarDescripcionProducto($item, $valor) {
        $tabla = "productos"; // Nombre de tu tabla
        $respuesta = ModeloProductos::mdlMostrarDescripcionProducto($tabla, $item, $valor);
        return $respuesta;
    }

		/*=============================================
	EDITAR PRODUCTO CAJERO
	=============================================*/

	public function ctrEditarProductoCajero() {
		if (isset($_POST["nuevoPrecioCompra"]) && isset($_POST["nuevoPrecioVenta"]) && isset($_POST["idProducto"])) {
			$retornoPrecio = $_POST["retornoPrecioProducto"] ?? "productos-cajero";
			$retornoPermitido = in_array($retornoPrecio, array("productos-cajero", "productos-precios"), true) ? $retornoPrecio : "productos-cajero";

			if($retornoPermitido === "productos-precios" && ($_SESSION["perfil"] ?? "") != "Administrador"){
				echo '<script>window.location = "inicio";</script>';
				return;
			}

			if($retornoPermitido === "productos-cajero" && ($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "cajero"){
				echo '<script>window.location = "inicio";</script>';
				return;
			}

			$tabla = "productos";
	
			$datos = array(
				"precio_compra" => $_POST["nuevoPrecioCompra"],
				"precio_venta" => $_POST["nuevoPrecioVenta"],
				"id" => $_POST["idProducto"],
				"id_usuario" => $_SESSION["id"]
			);
	
			$respuesta = ModeloProductos::mdlEditarProductoCajero($tabla, $datos);
	
			if ($respuesta == "ok") {
				if (class_exists("ControladorLogs")) {
					ControladorLogs::ctrRegistrarLog("asignar_precio", "gestion_precios", "Producto ".$_POST["idProducto"]." actualizado con nuevo precio");
				}

				echo "<script>
					Swal.fire({
						icon: 'success',
						title: 'Precio guardado correctamente',
						showConfirmButton: true,
						confirmButtonText: 'Cerrar'
					}).then((result) => {
						if (result.isConfirmed) {
							window.location = '".$retornoPermitido."';
						}
					});
				</script>";
			}
		}
	}
	
	/*=============================================
	BORRAR PRODUCTO
	=============================================*/
	static public function ctrEliminarProducto(){

		if(isset($_GET["idProducto"])){
			if ($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "cajero") {
				echo '<script>window.location = "productos-almacen";</script>';
				return;
			}

			$tabla ="productos";
			$datos = $_GET["idProducto"];

			if($_GET["imagen"] != "" && $_GET["imagen"] != "vistas/img/productos/default/anonymous.png"){

				$rutaImagenProducto = $_GET["imagen"];
				$directorioImagenProducto = dirname($rutaImagenProducto);
				if(is_file($rutaImagenProducto)){
					unlink($rutaImagenProducto);
				}
				if(is_dir($directorioImagenProducto)){
					$archivosRestantes = array_diff(scandir($directorioImagenProducto), array(".", ".."));
					if(count($archivosRestantes) === 0){
						rmdir($directorioImagenProducto);
					}
				}

			}

			$respuesta = ModeloProductos::mdlEliminarProducto($tabla, $datos);

			if($respuesta == "ok"){
				if (class_exists("ControladorLogs")) {
					ControladorLogs::ctrRegistrarLog("eliminar", "productos", "Producto ".$datos." eliminado");
				}
				$retornoProducto = (isset($_GET["retorno"]) && $_GET["retorno"] == "productos-almacen") ? "productos-almacen" : "productos";

				echo'<script>

				swal({
					  type: "success",
					  title: "El producto ha sido borrado correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

								window.location = "'.$retornoProducto.'";

								}
							})

				</script>';

			}		
		}


	}

	/*=============================================
	MOSTRAR SUMA VENTAS
	=============================================*/

	static public function ctrMostrarSumaVentas(){

		$tabla = "productos";

		$respuesta = ModeloProductos::mdlMostrarSumaVentas($tabla);

		return $respuesta;

	}


}
