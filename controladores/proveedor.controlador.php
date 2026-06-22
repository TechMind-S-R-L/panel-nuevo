<?php

class ControladorProveedor{
	/*=============================================
	REGISTRO DE PROVEEDOR
	=============================================*/

	static public function ctrCrearProveedor(){

		if(isset($_POST["nuevoProveedor"])){

			if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nuevoProveedor"])){

			  
	
				$tabla = "proveedor";

			
				$datos = array("nombre" => $_POST["nuevoProveedor"],
					           "contacto" => $_POST["nuevoContacto"],
					           "direccion" => $_POST["nuevaDireccion"],
					           "telefono"=> $_POST["nuevoTelefono"]);

				$respuesta = ModeloProveedor::mdlIngresarProveedor($tabla, $datos);
			
				if($respuesta == "ok"){

					echo '<script>

					swal({

						type: "success",
						title: "¡El proveedor ha sido guardado correctamente!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"

					}).then(function(result){

						if(result.value){
						
							window.location = "proveedor";

						}

					});
				

					</script>';


				}	


			}else{

				echo '<script>

					swal({

						type: "error",
						title: "¡El proveedor no puede ir vacío o llevar caracteres especiales!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"

					}).then(function(result){

						if(result.value){
						
							window.location = "proveedor";

						}

					});
				

				</script>';

			}


		}


	}

	/*=============================================
	MOSTRAR PROVEEDOR
	=============================================*/

	static public function ctrMostrarProveedor($item, $valor){

		$tabla = "proveedor";

		$respuesta = ModeloProveedor::MdlMostrarProveedor($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	EDITAR PROVEEDOR
	=============================================*/

	static public function ctrEditarProveedor(){

		if(isset($_POST["editarProveedor"])){

			if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["editarProveedor"])){

			

				$tabla = "proveedor";


				$datos = array("nombre" => $_POST["editarProveedor"],
					           "contacto" => $_POST["editarContacto"],
					           "direccion" => $_POST["editarDireccion"],
					           "telefono"=> $_POST["editarTelefono"]);

				$respuesta = ModeloProveedor::mdlEditarProveedor($tabla, $datos);

				if($respuesta == "ok"){

					echo'<script>

					swal({
						  type: "success",
						  title: "El proveedor ha sido editado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "proveedor";

									}
								})

					</script>';

				}


			}else{

				echo'<script>

					swal({
						  type: "error",
						  title: "¡El proveedor no puede ir vacío o llevar caracteres especiales!",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
							if (result.value) {

							window.location = "proveedor";

							}
						})

			  	</script>';

			}

		}

	}

	/*=============================================
	BORRAR PROVEEDOR
	=============================================*/

	static public function ctrBorrarProveedor(){

		if(isset($_GET["idProveedor"])){

			$tabla ="proveedor";
			$datos = $_GET["idProveedor"];

			$respuesta = ModeloProveedor::mdlBorrarProveedor($tabla, $datos);

			if($respuesta == "ok"){

				echo'<script>

				swal({
					  type: "success",
					  title: "El proveedor ha sido eliminado correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

								window.location = "proveedor";

								}
							})

				</script>';

			}		

		}

	}


}
	


