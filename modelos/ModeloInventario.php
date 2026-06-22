<?php

require_once "conexion.php";

class ModeloInventario {

    static private function mdlAsegurarIngresoDirectoAdmin($conexion) {
        $conexion->exec(
            "CREATE TABLE IF NOT EXISTS ingresos_directos_admin (
                id BIGINT NOT NULL AUTO_INCREMENT,
                id_admin INT NOT NULL,
                id_producto INT NOT NULL,
                tipo_producto VARCHAR(20) NOT NULL DEFAULT 'existente',
                cantidad INT NOT NULL,
                stock_anterior INT NOT NULL DEFAULT 0,
                stock_nuevo INT NOT NULL DEFAULT 0,
                precio_compra DECIMAL(12,2) NOT NULL DEFAULT 0,
                precio_venta DECIMAL(12,2) NOT NULL DEFAULT 0,
                codigo_producto VARCHAR(120) NOT NULL,
                codigo_general VARCHAR(120) NOT NULL,
                codigos_unicos LONGTEXT NOT NULL,
                observacion VARCHAR(500) NULL,
                fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_ingreso_admin_producto (id_producto),
                KEY idx_ingreso_admin_usuario (id_admin),
                CONSTRAINT fk_ingreso_admin_producto FOREIGN KEY (id_producto) REFERENCES productos(id),
                CONSTRAINT fk_ingreso_admin_usuario FOREIGN KEY (id_admin) REFERENCES usuarios(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
    }

    static private function mdlCodigoDisponible($conexion, $campo, $codigo, $idExcluir = 0) {
        $camposPermitidos = ["codigo", "codigo_producto_generico", "codigo_barras_unico"];
        if (!in_array($campo, $camposPermitidos, true)) {
            return false;
        }
        $sql = "SELECT id FROM productos WHERE ".$campo." = :codigo";
        if ($idExcluir > 0) {
            $sql .= " AND id <> :id";
        }
        $sql .= " LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bindValue(":codigo", $codigo, PDO::PARAM_STR);
        if ($idExcluir > 0) {
            $stmt->bindValue(":id", $idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        return !$stmt->fetch(PDO::FETCH_ASSOC);
    }

    static private function mdlLimpiarBaseCodigo($valor, $defecto) {
        $valor = strtoupper(trim((string)$valor));
        $valor = preg_replace('/[^A-Z0-9-]+/', '', $valor);
        return $valor !== "" ? $valor : $defecto;
    }

    static private function mdlGenerarCodigoProducto($conexion) {
        do {
            $codigo = "TMADM".date("ymd").str_pad((string)mt_rand(1, 9999), 4, "0", STR_PAD_LEFT);
        } while (!self::mdlCodigoDisponible($conexion, "codigo", $codigo));
        return $codigo;
    }

    static public function mdlRegistrarIngresoDirectoAdmin($datos) {
        $conexion = Conexion::conectar();
        try {
            self::mdlAsegurarIngresoDirectoAdmin($conexion);

            $idAdmin = (int)($datos["id_admin"] ?? 0);
            $idProducto = (int)($datos["id_producto"] ?? 0);
            $cantidad = (int)($datos["cantidad"] ?? 0);
            $precioCompra = round((float)($datos["precio_compra"] ?? 0), 2);
            $precioVenta = round((float)($datos["precio_venta"] ?? 0), 2);
            $esNuevo = !empty($datos["es_nuevo"]);
            $observacion = trim((string)($datos["observacion"] ?? ""));

            if ($idAdmin <= 0) {
                throw new Exception("No se pudo identificar al administrador.");
            }
            if ($cantidad <= 0 || $cantidad > 500) {
                throw new Exception("La cantidad debe estar entre 1 y 500 unidades.");
            }
            if ($precioCompra < 0 || $precioVenta < 0) {
                throw new Exception("Los precios no pueden ser negativos.");
            }
            if ($precioVenta > 0 && $precioCompra > 0 && $precioVenta < $precioCompra) {
                throw new Exception("El precio de venta no puede ser menor al precio de compra.");
            }

            $conexion->beginTransaction();

            if ($esNuevo) {
                $idCategoria = (int)($datos["id_categoria"] ?? 0);
                $idMarca = (int)($datos["id_marca"] ?? 0);
                $descripcion = trim((string)($datos["descripcion"] ?? ""));
                $detalle = trim((string)($datos["detalle"] ?? ""));
                $imagen = trim((string)($datos["imagen"] ?? "vistas/img/productos/default/anonymous.png"));
                $codigoProducto = self::mdlLimpiarBaseCodigo($datos["codigo_producto"] ?? "", "");

                if ($idCategoria <= 0 || $descripcion === "") {
                    throw new Exception("Para crear el producto indique categoría y nombre.");
                }
                if ($codigoProducto === "") {
                    $codigoProducto = self::mdlGenerarCodigoProducto($conexion);
                } else {
                    if (substr($codigoProducto, 0, 2) !== "TM") {
                        $codigoProducto = "TM".$codigoProducto;
                    }
                    if (!self::mdlCodigoDisponible($conexion, "codigo", $codigoProducto)) {
                        throw new Exception("El código de producto ya está registrado.");
                    }
                }

                $descripcion = function_exists("mb_strtoupper")
                    ? mb_strtoupper($descripcion, "UTF-8")
                    : strtoupper($descripcion);

                $stmtProducto = $conexion->prepare(
                    "INSERT INTO productos
                        (id_categoria, id_marca, codigo, codigo_producto_generico, codigo_barras_unico,
                         descripcion, detalle, imagen, stock, precio_compra, precio_venta, requiere_precio, ventas)
                     VALUES
                        (:categoria, :marca, :codigo, NULL, NULL, :descripcion, :detalle, :imagen,
                         0, :compra, :venta, 0, 0)"
                );
                $stmtProducto->bindValue(":categoria", $idCategoria, PDO::PARAM_INT);
                $stmtProducto->bindValue(":marca", $idMarca > 0 ? $idMarca : null, $idMarca > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmtProducto->bindValue(":codigo", $codigoProducto, PDO::PARAM_STR);
                $stmtProducto->bindValue(":descripcion", $descripcion, PDO::PARAM_STR);
                $stmtProducto->bindValue(":detalle", $detalle, PDO::PARAM_STR);
                $stmtProducto->bindValue(":imagen", $imagen, PDO::PARAM_STR);
                $stmtProducto->bindValue(":compra", $precioCompra);
                $stmtProducto->bindValue(":venta", $precioVenta);
                $stmtProducto->execute();
                $idProducto = (int)$conexion->lastInsertId();
            } else {
                if ($idProducto <= 0) {
                    throw new Exception("Seleccione el producto que ingresará al inventario.");
                }
                $stmtBloqueo = $conexion->prepare("SELECT * FROM productos WHERE id = :id FOR UPDATE");
                $stmtBloqueo->bindValue(":id", $idProducto, PDO::PARAM_INT);
                $stmtBloqueo->execute();
                if (!$stmtBloqueo->fetch(PDO::FETCH_ASSOC)) {
                    throw new Exception("El producto seleccionado ya no existe.");
                }
            }

            $stmtProductoActual = $conexion->prepare("SELECT * FROM productos WHERE id = :id FOR UPDATE");
            $stmtProductoActual->bindValue(":id", $idProducto, PDO::PARAM_INT);
            $stmtProductoActual->execute();
            $producto = $stmtProductoActual->fetch(PDO::FETCH_ASSOC);
            if (!$producto) {
                throw new Exception("No se pudo recuperar el producto.");
            }

            $codigoProducto = trim((string)$producto["codigo"]);
            if ($codigoProducto === "") {
                $codigoProducto = self::mdlGenerarCodigoProducto($conexion);
            } elseif (substr(strtoupper($codigoProducto), 0, 2) !== "TM") {
                $codigoProducto = "TM".$codigoProducto;
            }

            $codigoGeneralSolicitado = self::mdlLimpiarBaseCodigo($datos["codigo_general"] ?? "", "");
            $codigoGeneral = $codigoGeneralSolicitado !== ""
                ? $codigoGeneralSolicitado
                : trim((string)($producto["codigo_producto_generico"] ?? ""));
            if ($codigoGeneral === "") {
                $codigoGeneral = "TMG".str_pad((string)$idProducto, 8, "0", STR_PAD_LEFT);
            }
            if (!self::mdlCodigoDisponible($conexion, "codigo_producto_generico", $codigoGeneral, $idProducto)) {
                throw new Exception("El código general pertenece a otro producto.");
            }

            $prefijoUnico = self::mdlLimpiarBaseCodigo(
                $datos["prefijo_unico"] ?? "",
                "TMU".str_pad((string)$idProducto, 7, "0", STR_PAD_LEFT)
            );
            $codigosEscaneados = $datos["codigos_unicos"] ?? [];
            if (!is_array($codigosEscaneados)) {
                $codigosEscaneados = [];
            }
            $codigosEscaneados = array_values(array_filter(array_map(function($codigo) {
                return strtoupper(trim((string)$codigo));
            }, $codigosEscaneados), function($codigo) {
                return $codigo !== "";
            }));

            if ($codigosEscaneados) {
                if (count($codigosEscaneados) !== $cantidad) {
                    throw new Exception("Debe escanear exactamente ".$cantidad." código(s) único(s).");
                }
                if (count(array_unique($codigosEscaneados)) !== count($codigosEscaneados)) {
                    throw new Exception("Hay códigos repetidos dentro del ingreso.");
                }
                foreach ($codigosEscaneados as $codigoEscaneado) {
                    if (strlen($codigoEscaneado) > 255 || !preg_match('/^[A-Z0-9._\/-]+$/', $codigoEscaneado)) {
                        throw new Exception("El código ".$codigoEscaneado." contiene caracteres no permitidos.");
                    }
                }
            }

            $stmtTotalCodigos = $conexion->prepare("SELECT COUNT(*) FROM productos_detalle WHERE id_producto = :id");
            $stmtTotalCodigos->bindValue(":id", $idProducto, PDO::PARAM_INT);
            $stmtTotalCodigos->execute();
            $secuencia = (int)$stmtTotalCodigos->fetchColumn();

            $stmtExisteUnidad = $conexion->prepare("SELECT id FROM productos_detalle WHERE codigo_barras_unico = :codigo LIMIT 1");
            $stmtExisteCodigoProducto = $conexion->prepare(
                "SELECT id FROM productos
                 WHERE codigo_barras_unico = :codigo
                   AND id <> :id_producto
                 LIMIT 1"
            );
            $stmtUnidad = $conexion->prepare(
                "INSERT INTO productos_detalle (id_producto, codigo_barras_unico, estado)
                 VALUES (:id_producto, :codigo, 'disponible')"
            );
            $codigos = [];
            while (count($codigos) < $cantidad) {
                if ($codigosEscaneados) {
                    $codigoUnidad = $codigosEscaneados[count($codigos)];
                } else {
                    $secuencia++;
                    $codigoUnidad = $prefijoUnico."-".str_pad((string)$secuencia, 5, "0", STR_PAD_LEFT);
                }
                $stmtExisteUnidad->execute([":codigo" => $codigoUnidad]);
                if ($stmtExisteUnidad->fetch(PDO::FETCH_ASSOC)) {
                    if ($codigosEscaneados) {
                        throw new Exception("El código ".$codigoUnidad." ya existe en el inventario.");
                    }
                    continue;
                }
                $stmtExisteCodigoProducto->execute([
                    ":codigo" => $codigoUnidad,
                    ":id_producto" => $idProducto
                ]);
                if ($stmtExisteCodigoProducto->fetch(PDO::FETCH_ASSOC)) {
                    if ($codigosEscaneados) {
                        throw new Exception("El código ".$codigoUnidad." está asignado a otro producto.");
                    }
                    continue;
                }
                $stmtUnidad->execute([
                    ":id_producto" => $idProducto,
                    ":codigo" => $codigoUnidad
                ]);
                $codigos[] = $codigoUnidad;
            }

            $stockAnterior = (int)$producto["stock"];
            $stockNuevo = $stockAnterior + $cantidad;
            $primerCodigo = trim((string)($producto["codigo_barras_unico"] ?? ""));
            if ($primerCodigo === "" && $codigos) {
                $primerCodigo = $codigos[0];
            }

            $stmtActualizar = $conexion->prepare(
                "UPDATE productos
                 SET codigo = :codigo,
                     codigo_producto_generico = :general,
                     codigo_barras_unico = :unico,
                     stock = :stock,
                     precio_compra = :compra,
                     precio_venta = :venta,
                     requiere_precio = 0
                 WHERE id = :id"
            );
            $stmtActualizar->execute([
                ":codigo" => $codigoProducto,
                ":general" => $codigoGeneral,
                ":unico" => $primerCodigo,
                ":stock" => $stockNuevo,
                ":compra" => $precioCompra,
                ":venta" => $precioVenta,
                ":id" => $idProducto
            ]);

            if (
                (float)$producto["precio_compra"] !== $precioCompra ||
                (float)$producto["precio_venta"] !== $precioVenta
            ) {
                $stmtPrecio = $conexion->prepare(
                    "INSERT INTO historial_precios
                        (id_producto, precio_compra_anterior, precio_venta_anterior,
                         precio_compra_nuevo, precio_venta_nuevo, id_usuario)
                     VALUES
                        (:producto, :compra_anterior, :venta_anterior, :compra_nueva, :venta_nueva, :usuario)"
                );
                $stmtPrecio->execute([
                    ":producto" => $idProducto,
                    ":compra_anterior" => (float)$producto["precio_compra"],
                    ":venta_anterior" => (float)$producto["precio_venta"],
                    ":compra_nueva" => $precioCompra,
                    ":venta_nueva" => $precioVenta,
                    ":usuario" => $idAdmin
                ]);
            }

            $stmtIngreso = $conexion->prepare(
                "INSERT INTO ingresos_directos_admin
                    (id_admin, id_producto, tipo_producto, cantidad, stock_anterior, stock_nuevo,
                     precio_compra, precio_venta, codigo_producto, codigo_general, codigos_unicos, observacion)
                 VALUES
                    (:admin, :producto, :tipo, :cantidad, :anterior, :nuevo,
                     :compra, :venta, :codigo, :general, :unicos, :observacion)"
            );
            $stmtIngreso->execute([
                ":admin" => $idAdmin,
                ":producto" => $idProducto,
                ":tipo" => $esNuevo ? "nuevo" : "existente",
                ":cantidad" => $cantidad,
                ":anterior" => $stockAnterior,
                ":nuevo" => $stockNuevo,
                ":compra" => $precioCompra,
                ":venta" => $precioVenta,
                ":codigo" => $codigoProducto,
                ":general" => $codigoGeneral,
                ":unicos" => json_encode($codigos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ":observacion" => $observacion
            ]);
            $idIngreso = (int)$conexion->lastInsertId();

            $conexion->commit();
            return [
                "status" => "ok",
                "id_ingreso" => $idIngreso,
                "id_producto" => $idProducto,
                "producto" => $producto["descripcion"],
                "cantidad" => $cantidad,
                "stock_anterior" => $stockAnterior,
                "stock_nuevo" => $stockNuevo,
                "codigo_producto" => $codigoProducto,
                "codigo_general" => $codigoGeneral,
                "codigos" => $codigos
            ];
        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return ["status" => "error", "message" => $e->getMessage()];
        }
    }

    static public function mdlMostrarIngresosDirectosAdmin($limite = 20) {
        $conexion = Conexion::conectar();
        self::mdlAsegurarIngresoDirectoAdmin($conexion);
        $stmt = $conexion->prepare(
            "SELECT i.*, p.descripcion, u.nombre AS administrador
             FROM ingresos_directos_admin i
             INNER JOIN productos p ON p.id = i.id_producto
             INNER JOIN usuarios u ON u.id = i.id_admin
             ORDER BY i.id DESC
             LIMIT :limite"
        );
        $stmt->bindValue(":limite", (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlBuscarProductosIngresoAdmin($termino, $limite = 15) {
        $termino = trim((string)$termino);
        if (strlen($termino) < 2) {
            return [];
        }
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare(
            "SELECT p.id, p.codigo, p.codigo_producto_generico, p.descripcion, p.stock,
                    p.precio_compra, p.precio_venta, p.id_categoria, p.id_marca,
                    CASE WHEN padre.categoria IS NULL THEN c.categoria ELSE CONCAT(padre.categoria, ' > ', c.categoria) END AS categoria
             FROM productos p
             LEFT JOIN categorias c ON c.id = p.id_categoria
             LEFT JOIN categorias padre ON padre.id = c.id_padre
             WHERE p.descripcion LIKE :termino_descripcion
                OR p.codigo LIKE :termino_codigo
                OR p.codigo_producto_generico LIKE :termino_general
                OR c.categoria LIKE :termino_categoria
                OR padre.categoria LIKE :termino_padre
             ORDER BY
                (p.descripcion LIKE :inicio) DESC,
                p.descripcion ASC
             LIMIT :limite"
        );
        $busqueda = "%".$termino."%";
        $stmt->bindValue(":termino_descripcion", $busqueda, PDO::PARAM_STR);
        $stmt->bindValue(":termino_codigo", $busqueda, PDO::PARAM_STR);
        $stmt->bindValue(":termino_general", $busqueda, PDO::PARAM_STR);
        $stmt->bindValue(":termino_categoria", $busqueda, PDO::PARAM_STR);
        $stmt->bindValue(":termino_padre", $busqueda, PDO::PARAM_STR);
        $stmt->bindValue(":inicio", $termino."%", PDO::PARAM_STR);
        $stmt->bindValue(":limite", max(1, min(30, (int)$limite)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlMostrarIngresoDirectoAdmin($idIngreso) {
        $conexion = Conexion::conectar();
        self::mdlAsegurarIngresoDirectoAdmin($conexion);
        $stmt = $conexion->prepare(
            "SELECT i.*, p.descripcion, p.imagen, u.nombre AS administrador
             FROM ingresos_directos_admin i
             INNER JOIN productos p ON p.id = i.id_producto
             INNER JOIN usuarios u ON u.id = i.id_admin
             WHERE i.id = :id
             LIMIT 1"
        );
        $stmt->bindValue(":id", (int)$idIngreso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static private function mdlCodigoEtiquetaDisponible($conexion, $codigo, $idProducto) {
        $stmtDetalle = $conexion->prepare(
            "SELECT id FROM productos_detalle WHERE codigo_barras_unico = :codigo LIMIT 1"
        );
        $stmtDetalle->execute([":codigo" => $codigo]);
        if ($stmtDetalle->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }
        $stmtProducto = $conexion->prepare(
            "SELECT id FROM productos
             WHERE codigo_barras_unico = :codigo
               AND id <> :id_producto
             LIMIT 1"
        );
        $stmtProducto->execute([
            ":codigo" => $codigo,
            ":id_producto" => $idProducto
        ]);
        return !$stmtProducto->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlPrepararEtiquetasProducto($idProducto) {
        $conexion = Conexion::conectar();
        try {
            $idProducto = (int)$idProducto;
            if ($idProducto <= 0) {
                throw new Exception("Producto no válido.");
            }
            $conexion->beginTransaction();
            $stmtProducto = $conexion->prepare(
                "SELECT * FROM productos WHERE id = :id FOR UPDATE"
            );
            $stmtProducto->execute([":id" => $idProducto]);
            $producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);
            if (!$producto) {
                throw new Exception("El producto ya no existe.");
            }

            $stmtCodigos = $conexion->prepare(
                "SELECT codigo_barras_unico, estado
                 FROM productos_detalle
                 WHERE id_producto = :id
                 ORDER BY id ASC"
            );
            $stmtCodigos->execute([":id" => $idProducto]);
            $codigosDetalle = $stmtCodigos->fetchAll(PDO::FETCH_ASSOC);
            $generados = 0;
            $modo = "unidades";

            if (!$codigosDetalle && (int)$producto["stock"] > 0) {
                $cantidad = (int)$producto["stock"];
                $prefijo = "TMU".str_pad((string)$idProducto, 7, "0", STR_PAD_LEFT);
                $stmtInsertar = $conexion->prepare(
                    "INSERT INTO productos_detalle (id_producto, codigo_barras_unico, estado)
                     VALUES (:producto, :codigo, 'disponible')"
                );
                for ($i = 1; $i <= $cantidad; $i++) {
                    $secuencia = $i;
                    do {
                        $codigo = $prefijo."-".str_pad((string)$secuencia, 5, "0", STR_PAD_LEFT);
                        $secuencia++;
                    } while (!self::mdlCodigoEtiquetaDisponible($conexion, $codigo, $idProducto));
                    $stmtInsertar->execute([
                        ":producto" => $idProducto,
                        ":codigo" => $codigo
                    ]);
                    $codigosDetalle[] = [
                        "codigo_barras_unico" => $codigo,
                        "estado" => "disponible"
                    ];
                    $generados++;
                }
                if (trim((string)$producto["codigo_barras_unico"]) === "" && $codigosDetalle) {
                    $conexion->prepare(
                        "UPDATE productos SET codigo_barras_unico = :codigo WHERE id = :id"
                    )->execute([
                        ":codigo" => $codigosDetalle[0]["codigo_barras_unico"],
                        ":id" => $idProducto
                    ]);
                }
            } elseif (!$codigosDetalle) {
                $modo = "general";
                $codigoGeneralEtiqueta = trim((string)$producto["codigo_barras_unico"]);
                if ($codigoGeneralEtiqueta === "") {
                    $codigoGeneralEtiqueta = "TME".str_pad((string)$idProducto, 9, "0", STR_PAD_LEFT);
                    $sufijo = 1;
                    while (!self::mdlCodigoEtiquetaDisponible($conexion, $codigoGeneralEtiqueta, $idProducto)) {
                        $codigoGeneralEtiqueta = "TME".str_pad((string)$idProducto, 7, "0", STR_PAD_LEFT).str_pad((string)$sufijo, 2, "0", STR_PAD_LEFT);
                        $sufijo++;
                    }
                    $conexion->prepare(
                        "UPDATE productos SET codigo_barras_unico = :codigo WHERE id = :id"
                    )->execute([
                        ":codigo" => $codigoGeneralEtiqueta,
                        ":id" => $idProducto
                    ]);
                    $generados = 1;
                }
                $codigosDetalle[] = [
                    "codigo_barras_unico" => $codigoGeneralEtiqueta,
                    "estado" => "etiqueta_general"
                ];
            }

            $conexion->commit();
            return [
                "status" => "ok",
                "id_producto" => $idProducto,
                "producto" => $producto["descripcion"],
                "modo" => $modo,
                "cantidad" => count($codigosDetalle),
                "generados" => $generados,
                "codigos" => $codigosDetalle
            ];
        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return ["status" => "error", "message" => $e->getMessage()];
        }
    }

    static public function mdlDatosEtiquetasProducto($idProducto) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare(
            "SELECT p.*,
                    CASE WHEN padre.categoria IS NULL THEN c.categoria ELSE CONCAT(padre.categoria, ' > ', c.categoria) END AS categoria
             FROM productos p
             LEFT JOIN categorias c ON c.id = p.id_categoria
             LEFT JOIN categorias padre ON padre.id = c.id_padre
             WHERE p.id = :id
             LIMIT 1"
        );
        $stmt->execute([":id" => (int)$idProducto]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$producto) {
            return null;
        }
        $stmtCodigos = $conexion->prepare(
            "SELECT codigo_barras_unico, estado
             FROM productos_detalle
             WHERE id_producto = :id
             ORDER BY id ASC"
        );
        $stmtCodigos->execute([":id" => (int)$idProducto]);
        $codigos = $stmtCodigos->fetchAll(PDO::FETCH_ASSOC);
        if (!$codigos && trim((string)$producto["codigo_barras_unico"]) !== "") {
            $codigos[] = [
                "codigo_barras_unico" => $producto["codigo_barras_unico"],
                "estado" => "etiqueta_general"
            ];
        }
        $producto["codigos_etiqueta"] = $codigos;
        return $producto;
    }

    static public function mdlResumenIngresoCompra($idCompra) {
        $stmtCompra = Conexion::conectar()->prepare("SELECT productos FROM compra WHERE id = :id_compra AND estado IN ('entregado_almacen', 'completado')");
        $stmtCompra->bindParam(":id_compra", $idCompra, PDO::PARAM_INT);
        $stmtCompra->execute();
        $compra = $stmtCompra->fetch(PDO::FETCH_ASSOC);

        if (!$compra) {
            return ["status" => "error", "message" => "La solicitud no existe o aun no fue entregada a almacen."];
        }

        $productos = json_decode($compra["productos"], true);
        if (!is_array($productos)) {
            return ["status" => "error", "message" => "La solicitud no tiene productos validos."];
        }

        $resumen = [];
        $totalRestante = 0;

        foreach ($productos as $producto) {
            $idProducto = (int)($producto["id"] ?? 0);
            $aprobado = (int)($producto["cantidad"] ?? 0);

            if ($idProducto <= 0 || $aprobado <= 0) {
                continue;
            }

            $stmtIngresado = Conexion::conectar()->prepare(
                "SELECT COALESCE(SUM(unidades), 0) AS total
                 FROM orden_ingreso_materiales
                 WHERE id_compra = :id_compra AND id_producto = :id_producto"
            );
            $stmtIngresado->bindParam(":id_compra", $idCompra, PDO::PARAM_INT);
            $stmtIngresado->bindParam(":id_producto", $idProducto, PDO::PARAM_INT);
            $stmtIngresado->execute();
            $ingresado = (int)$stmtIngresado->fetch(PDO::FETCH_ASSOC)["total"];
            $restante = max(0, $aprobado - $ingresado);
            $totalRestante += $restante;

            $resumen[] = [
                "id" => $idProducto,
                "descripcion" => $producto["descripcion"] ?? "SIN DESCRIPCION",
                "aprobado" => $aprobado,
                "ingresado" => $ingresado,
                "restante" => $restante
            ];
        }

        return [
            "status" => "ok",
            "productos" => $resumen,
            "totalRestante" => $totalRestante
        ];
    }

    static private function mdlAsegurarCodigosIngresoCompra($conexion) {
        $conexion->exec(
            "CREATE TABLE IF NOT EXISTS orden_ingreso_codigos (
                id BIGINT NOT NULL AUTO_INCREMENT,
                id_compra INT NOT NULL,
                id_producto INT NOT NULL,
                id_producto_detalle INT NOT NULL,
                codigo_barras_unico VARCHAR(180) NOT NULL,
                origen VARCHAR(20) NOT NULL DEFAULT 'escaneado',
                fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_ingreso_codigo (codigo_barras_unico),
                KEY idx_ingreso_compra_producto (id_compra, id_producto),
                KEY idx_ingreso_detalle (id_producto_detalle)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
    }

    static private function mdlGenerarCodigoUnidadCompra($conexion, $idCompra, $idProducto) {
        $prefijo = "TMC"
            .str_pad((string)$idCompra, 7, "0", STR_PAD_LEFT)
            ."-"
            .str_pad((string)$idProducto, 7, "0", STR_PAD_LEFT);
        $secuencia = 1;
        do {
            $codigo = $prefijo."-".str_pad((string)$secuencia, 4, "0", STR_PAD_LEFT);
            $secuencia++;
        } while (!self::mdlCodigoEtiquetaDisponible($conexion, $codigo, $idProducto));
        return $codigo;
    }

    static public function mdlRegistrarUnidadAprobada($datos) {
        $conexion = Conexion::conectar();

        try {
            self::mdlAsegurarCodigosIngresoCompra($conexion);
            $conexion->beginTransaction();

            $stmtCompra = $conexion->prepare("SELECT id, productos FROM compra WHERE id = :id_compra AND estado = 'entregado_almacen' FOR UPDATE");
            $stmtCompra->bindParam(":id_compra", $datos["id_compra"], PDO::PARAM_INT);
            $stmtCompra->execute();
            $compra = $stmtCompra->fetch(PDO::FETCH_ASSOC);

            if (!$compra) {
                throw new Exception("La solicitud todavia no fue entregada a almacen o no existe.");
            }

            $productos = json_decode($compra["productos"], true);
            if (!is_array($productos)) {
                throw new Exception("La solicitud no tiene productos validos.");
            }

            $cantidadAprobada = 0;
            foreach ($productos as $producto) {
                if ((int)($producto["id"] ?? 0) === (int)$datos["id_producto"]) {
                    $cantidadAprobada = (int)($producto["cantidad"] ?? 0);
                    break;
                }
            }

            if ($cantidadAprobada <= 0) {
                throw new Exception("Este producto no pertenece a la solicitud aprobada.");
            }

            $generarCodigo = !empty($datos["generar_codigo"]);
            $codigoUnidad = trim((string)($datos["codigo_barras_unico"] ?? ""));
            if ($generarCodigo) {
                $codigoUnidad = self::mdlGenerarCodigoUnidadCompra(
                    $conexion,
                    (int)$datos["id_compra"],
                    (int)$datos["id_producto"]
                );
            }
            if ($codigoUnidad === "") {
                throw new Exception("Escanee un codigo o seleccione generar codigo unico.");
            }

            $stmtCodigo = $conexion->prepare("SELECT id FROM productos_detalle WHERE codigo_barras_unico = :codigo LIMIT 1");
            $stmtCodigo->bindParam(":codigo", $codigoUnidad, PDO::PARAM_STR);
            $stmtCodigo->execute();
            if ($stmtCodigo->fetch()) {
                throw new Exception("Este codigo ya fue registrado.");
            }

            $stmtIngresado = $conexion->prepare(
                "SELECT COALESCE(SUM(unidades), 0) AS total
                 FROM orden_ingreso_materiales
                 WHERE id_compra = :id_compra AND id_producto = :id_producto"
            );
            $stmtIngresado->bindParam(":id_compra", $datos["id_compra"], PDO::PARAM_INT);
            $stmtIngresado->bindParam(":id_producto", $datos["id_producto"], PDO::PARAM_INT);
            $stmtIngresado->execute();
            $ingresado = (int)$stmtIngresado->fetch(PDO::FETCH_ASSOC)["total"];

            if ($ingresado >= $cantidadAprobada) {
                throw new Exception("Ya se ingreso toda la cantidad aprobada para este producto.");
            }

            $stmtProducto = $conexion->prepare("SELECT codigo FROM productos WHERE id = :id_producto FOR UPDATE");
            $stmtProducto->bindParam(":id_producto", $datos["id_producto"], PDO::PARAM_INT);
            $stmtProducto->execute();
            $productoActual = $stmtProducto->fetch(PDO::FETCH_ASSOC);

            if (!$productoActual) {
                throw new Exception("El producto no existe.");
            }

            $stmtDetalle = $conexion->prepare(
                "INSERT INTO productos_detalle (id_producto, codigo_barras_unico)
                 VALUES (:id_producto, :codigo_barras_unico)"
            );
            $stmtDetalle->bindParam(":id_producto", $datos["id_producto"], PDO::PARAM_INT);
            $stmtDetalle->bindParam(":codigo_barras_unico", $codigoUnidad, PDO::PARAM_STR);
            $stmtDetalle->execute();
            $idProductoDetalle = (int)$conexion->lastInsertId();

            $stmtCodigoIngreso = $conexion->prepare(
                "INSERT INTO orden_ingreso_codigos
                    (id_compra, id_producto, id_producto_detalle, codigo_barras_unico, origen)
                 VALUES
                    (:id_compra, :id_producto, :id_producto_detalle, :codigo, :origen)"
            );
            $stmtCodigoIngreso->execute([
                ":id_compra" => (int)$datos["id_compra"],
                ":id_producto" => (int)$datos["id_producto"],
                ":id_producto_detalle" => $idProductoDetalle,
                ":codigo" => $codigoUnidad,
                ":origen" => $generarCodigo ? "generado" : "escaneado"
            ]);

            $stmtOrden = $conexion->prepare(
                "SELECT id_orden_ingreso, unidades
                 FROM orden_ingreso_materiales
                 WHERE id_compra = :id_compra AND id_producto = :id_producto
                 LIMIT 1 FOR UPDATE"
            );
            $stmtOrden->bindParam(":id_compra", $datos["id_compra"], PDO::PARAM_INT);
            $stmtOrden->bindParam(":id_producto", $datos["id_producto"], PDO::PARAM_INT);
            $stmtOrden->execute();
            $orden = $stmtOrden->fetch(PDO::FETCH_ASSOC);

            if ($orden) {
                $nuevasUnidades = (int)$orden["unidades"] + 1;
                $estadoOrden = $nuevasUnidades >= $cantidadAprobada ? "completado" : "pendiente";
                $stmtActualizarOrden = $conexion->prepare(
                    "UPDATE orden_ingreso_materiales
                     SET unidades = :unidades,
                         estado = :estado
                     WHERE id_orden_ingreso = :id_orden_ingreso"
                );
                $stmtActualizarOrden->bindParam(":unidades", $nuevasUnidades, PDO::PARAM_INT);
                $stmtActualizarOrden->bindParam(":estado", $estadoOrden, PDO::PARAM_STR);
                $stmtActualizarOrden->bindParam(":id_orden_ingreso", $orden["id_orden_ingreso"], PDO::PARAM_INT);
                $stmtActualizarOrden->execute();
            } else {
                $unidades = 1;
                $estado = $cantidadAprobada === 1 ? "completado" : "pendiente";
                $stmtInsertarOrden = $conexion->prepare(
                    "INSERT INTO orden_ingreso_materiales (id_compra, id_producto, codigo_producto, unidades, estado)
                     VALUES (:id_compra, :id_producto, :codigo_producto, :unidades, :estado)"
                );
                $stmtInsertarOrden->bindParam(":id_compra", $datos["id_compra"], PDO::PARAM_INT);
                $stmtInsertarOrden->bindParam(":id_producto", $datos["id_producto"], PDO::PARAM_INT);
                $stmtInsertarOrden->bindParam(":codigo_producto", $productoActual["codigo"], PDO::PARAM_STR);
                $stmtInsertarOrden->bindParam(":unidades", $unidades, PDO::PARAM_INT);
                $stmtInsertarOrden->bindParam(":estado", $estado, PDO::PARAM_STR);
                $stmtInsertarOrden->execute();
            }

            $stmtStock = $conexion->prepare("UPDATE productos SET stock = stock + 1 WHERE id = :id_producto");
            $stmtStock->bindParam(":id_producto", $datos["id_producto"], PDO::PARAM_INT);
            $stmtStock->execute();

            self::mdlCompletarCompraSiCorresponde($conexion, $datos["id_compra"], $productos);

            $conexion->commit();

            return [
                "status" => "ok",
                "codigo" => $codigoUnidad,
                "generado" => $generarCodigo,
                "ingresado" => $ingresado + 1,
                "aprobado" => $cantidadAprobada,
                "restante" => max(0, $cantidadAprobada - ($ingresado + 1))
            ];
        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }

    static public function mdlEtiquetasIngresoCompra($idCompra, $idProducto) {
        $conexion = Conexion::conectar();
        self::mdlAsegurarCodigosIngresoCompra($conexion);
        $stmt = $conexion->prepare(
            "SELECT p.id, p.codigo, p.codigo_producto_generico, p.descripcion,
                    p.precio_venta, c.codigo AS codigo_compra,
                    o.codigo_barras_unico, o.origen, o.fecha
             FROM orden_ingreso_codigos o
             INNER JOIN productos p ON p.id = o.id_producto
             INNER JOIN compra c ON c.id = o.id_compra
             WHERE o.id_compra = :id_compra
               AND o.id_producto = :id_producto
             ORDER BY o.id ASC"
        );
        $stmt->execute([
            ":id_compra" => (int)$idCompra,
            ":id_producto" => (int)$idProducto
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static private function mdlCompletarCompraSiCorresponde($conexion, $idCompra, $productos) {
        foreach ($productos as $producto) {
            $idProducto = (int)($producto["id"] ?? 0);
            $cantidadAprobada = (int)($producto["cantidad"] ?? 0);

            if ($idProducto <= 0 || $cantidadAprobada <= 0) {
                return;
            }

            $stmtIngresado = $conexion->prepare(
                "SELECT COALESCE(SUM(unidades), 0) AS total
                 FROM orden_ingreso_materiales
                 WHERE id_compra = :id_compra AND id_producto = :id_producto"
            );
            $stmtIngresado->bindParam(":id_compra", $idCompra, PDO::PARAM_INT);
            $stmtIngresado->bindParam(":id_producto", $idProducto, PDO::PARAM_INT);
            $stmtIngresado->execute();
            $ingresado = (int)$stmtIngresado->fetch(PDO::FETCH_ASSOC)["total"];

            if ($ingresado < $cantidadAprobada) {
                return;
            }
        }

        $stmtCompletar = $conexion->prepare("UPDATE compra SET estado = 'completado' WHERE id = :id_compra");
        $stmtCompletar->bindParam(":id_compra", $idCompra, PDO::PARAM_INT);
        $stmtCompletar->execute();
    }

    static public function mdlVerificarCodigoUnico($codigo) {
        $stmt = Conexion::conectar()->prepare(
            "SELECT id FROM productos_detalle WHERE codigo_barras_unico = :codigo"
        );
        $stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    static public function mdlContarUnidadesPorProducto($idProducto) {
        $stmt = Conexion::conectar()->prepare(
            "SELECT COUNT(*) AS total FROM productos_detalle WHERE id_producto = :id_producto"
        );
        $stmt->bindParam(":id_producto", $idProducto, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch();
        return $resultado ? (int)$resultado["total"] : 0;
    }

    static public function mdlActualizarEstadoCompra($idCompra, $nuevoEstado) {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE compra SET estado = :estado WHERE id = :id_compra"
        );
        $stmt->bindParam(":estado", $nuevoEstado, PDO::PARAM_STR);
        $stmt->bindParam(":id_compra", $idCompra, PDO::PARAM_INT);

        return $stmt->execute() ? "ok" : "error";
    }
}
