<?php

require_once "../controladores/productos.controlador.php";
require_once "../modelos/productos.modelo.php";

require_once "../controladores/categorias.controlador.php";
require_once "../modelos/categorias.modelo.php";

class TablaProductosFiltrados{

    /*=============================================
    MOSTRAR LA TABLA DE PRODUCTOS
    =============================================*/

    
        public function mostrarTablaProductosfiltrados(){

            $item = null;
            $valor = null;
            $orden = "id";
    
            // $productos = ModeloProductos::mdlMostrarProductosFiltrados("productos");	
            $productos = ControladorProductos::ctrMostrarProductosfiltrados();

            if (empty($productos)) {
                echo '{"data": []}';
                return;
            }
            
            // $datosJson = '{
            // "data": [';
            
            // foreach ($productos as $i => $producto) {
            //     $imagen = "<img src='".$producto["imagen"]."' width='40px'>";
            //     $stock = $producto["stock"] <= 10 
            //         ? "<button class='btn btn-danger'>".$producto["stock"]."</button>"
            //         : ($producto["stock"] <= 15
            //             ? "<button class='btn btn-warning'>".$producto["stock"]."</button>"
            //             : "<button class='btn btn-success'>".$producto["stock"]."</button>");
            
            //     $categorias = ControladorCategorias::ctrMostrarCategorias("id", $producto["id_categoria"]);
            //     $categoria = $categorias ? $categorias["categoria"] : "Sin categoría";
            
            //     $botones = "<div class='btn-group'>
            //         <button class='btn btn-warning btnEditarProducto' idProducto='".$producto["id"]."' data-toggle='modal' data-target='#modalEditarProducto'>
            //             <i class='fa fa-pencil'></i>
            //         </button>
            //         <button class='btn btn-danger btnEliminarProducto' idProducto='".$producto["id"]."' codigo='".$producto["codigo"]."' imagen='".$producto["imagen"]."'>
            //             <i class='fa fa-times'></i>
            //         </button>
            //     </div>";
            $datos = array();
      
                for($i = 0; $i < count($productos); $i++){
      
                    /*=============================================
                    TRAEMOS LA IMAGEN
                    =============================================*/ 
      
                    $imagen = "<img src='".htmlspecialchars($productos[$i]["imagen"] ?? "", ENT_QUOTES, "UTF-8")."' width='40px'>";
                  
      
                    /*=============================================
                    TRAEMOS LA CATEGORÍA
                    =============================================*/ 
      
                    $item = "id";
                    $valor = $productos[$i]["id_categoria"];
      
                    $categorias = ControladorCategorias::ctrMostrarCategorias($item, $valor);
      
                    /*=============================================
                    STOCK
                    =============================================*/ 
      
                    if($productos[$i]["stock"] <= 10){
      
                        $stock = "<button class='btn btn-danger'>".$productos[$i]["stock"]."</button>";
      
                    }else if($productos[$i]["stock"] > 11 && $productos[$i]["stock"] <= 15){
      
                        $stock = "<button class='btn btn-warning'>".$productos[$i]["stock"]."</button>";
      
                    }else{
      
                        $stock = "<button class='btn btn-success'>".$productos[$i]["stock"]."</button>";
      
                    }
      
                    /*=============================================
                    TRAEMOS LAS ACCIONES
                    =============================================*/ 
      
                    $botones = "<button class='btn btn-primary btnEditarProducto' idProducto='".(int)$productos[$i]["id"]."' data-toggle='modal' data-target='#modalEditarProducto'><i class='fa fa-tag'></i> Poner precio</button>";
                    $categoria = $categorias ? ($categorias["ruta_categoria"] ?? $categorias["categoria"]) : "Sin categoria";
                    $datos[] = array(
                        (string)($i + 1),
                        (string)($productos[$i]["codigo"] ?? ""),
                        (string)($productos[$i]["codigo_producto_generico"] ?? ""),
                        (string)($productos[$i]["codigo_barras_unico"] ?? ""),
                        (string)($productos[$i]["descripcion"] ?? ""),
                        (string)$categoria,
                        $stock,
                        $imagen,
                        (string)($productos[$i]["precio_compra"] ?? "0"),
                        (string)($productos[$i]["precio_venta"] ?? "0"),
                        (string)($productos[$i]["fecha"] ?? ""),
                        $botones
                    );
            }
            
           echo json_encode(array("data" => $datos), JSON_UNESCAPED_UNICODE);
    
    
    }
}
/*=============================================
ACTIVAR TABLA DE PRODUCTOS
=============================================*/
$activarProductos = new TablaProductosFiltrados();
$activarProductos->mostrarTablaProductosfiltrados();

?>
