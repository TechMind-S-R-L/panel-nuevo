<?php

require_once "controladores/plantilla.controlador.php";
require_once "controladores/usuarios.controlador.php";
require_once "controladores/categorias.controlador.php";
require_once "controladores/proveedor.controlador.php";
require_once "controladores/productos.controlador.php";
require_once "controladores/clientes.controlador.php";
require_once "controladores/compras.controlador.php";
require_once "controladores/ventas.controlador.php";
require_once "controladores/servicios.controlador.php";
require_once "controladores/proyectos.controlador.php";
require_once "controladores/cotizacion.controlador.php";
require_once "controladores/logs.controlador.php";
require_once "controladores/caja.controlador.php";
require_once "controladores/web-publicaciones.controlador.php";
require_once "controladores/web-consultas.controlador.php";


require_once "modelos/usuarios.modelo.php";
require_once "modelos/categorias.modelo.php";
require_once "modelos/proveedor.modelo.php";
require_once "modelos/productos.modelo.php";
require_once "modelos/clientes.modelo.php";
require_once "modelos/compras.modelo.php";
require_once "modelos/ventas.modelo.php";
require_once "modelos/servicios.modelo.php";
require_once "modelos/proyectos.modelo.php";
require_once "modelos/cotizacion.modelo.php";
require_once "modelos/ModeloInventario.php";
require_once "modelos/logs.modelo.php";
require_once "modelos/caja.modelo.php";
require_once "modelos/web-publicaciones.modelo.php";
require_once "modelos/web-consultas.modelo.php";


$plantilla = new ControladorPlantilla();
$plantilla -> ctrPlantilla();
