<?php

require_once __DIR__ . "/../../../controladores/compras.controlador.php";
require_once __DIR__ . "/../../../modelos/compras.modelo.php";

require_once __DIR__ . "/../../../controladores/proveedor.controlador.php";
require_once __DIR__ . "/../../../modelos/proveedor.modelo.php";

require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

require_once __DIR__ . "/../../../controladores/productos.controlador.php";
require_once __DIR__ . "/../../../modelos/productos.modelo.php";

function RotatedText($pdf, $x, $y, $txt, $angle, $size = 52) {
    $pdf->StartTransform();
    $pdf->Rotate($angle, $x, $y);
    $pdf->SetFont('helvetica', 'B', $size);
    $pdf->SetTextColor(50, 50, 50); // Gris mas oscuro
    $pdf->Text($x, $y, $txt);
    $pdf->StopTransform();
}


class ImprimirNotaCompra {

    public $codigo;
    public $idCompra;

    public function traerImpresionNotaCompra() {

        if (!empty($this->idCompra)) {
            $itemCompra = "id";
            $valorCompra = $this->idCompra;
        } else {
            $itemCompra = "codigo";
            $valorCompra = $this->codigo;
        }

        $respuestaCompra = ControladorCompras::ctrMostrarCompras($itemCompra, $valorCompra);
        if (!$respuestaCompra) {
            die("No se encontro la compra.");
        }

        $fechaCompra = substr($respuestaCompra["fecha"], 0, -8);
        $productos = json_decode($respuestaCompra["productos"], true);
        $total = number_format($respuestaCompra["total"], 2);
        $codigoCompra = $respuestaCompra["codigo"];
		$estado = $respuestaCompra["estado"] ;

        // Datos de proveedor y usuario
        $respuestaCliente = ControladorProveedor::ctrMostrarProveedor("id", $respuestaCompra["id_proveedor"]);
        $respuestaVendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $respuestaCompra["id_usuario"]);

        // TCPDF
        chdir(__DIR__);
        require_once('tcpdf_include_notaventa.php');
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->startPageGroup();
        $pdf->AddPage();

		// Marca de agua con el estado (en diagonal)
		$pdf->SetAlpha(0.1);
		$marcaEstado = strtoupper(str_replace("_", " ", $estado));
		$tamanoMarca = strlen($marcaEstado) > 14 ? 38 : 52;
		RotatedText($pdf, 38, 205, $marcaEstado, 45, $tamanoMarca); // X, Y, Texto, Angulo
		$pdf->SetAlpha(1);

        // Marca de agua
        $pdf->SetAlpha(0.1);
        $pdf->Image('images/ICONO.png', 45, 80, 120); // Marca de agua centrada
        $pdf->SetAlpha(1);


        // Cabecera
        $pdf->SetFillColor(230, 240, 255);
        $pdf->Rect(10, 10, 190, 30, 'F');
        $pdf->Image('images/ICONO.png', 17, 13, 21);
        $pdf->SetXY(40, 14);
        $pdf->SetFont('helvetica', 'B', 14);
		$pdf->SetTextColor(70, 130, 180);
        $pdf->Cell(90, 7, 'TECHMIND S.R.L.', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetX(40);
        $pdf->Cell(90, 5, 'Km 6 doble via la guardia, calle paraiso Nro 6387', 0, 1, 'L');
        $pdf->SetX(40);
        $pdf->Cell(90, 5, '(+591) 75556540 | (+591) 78572656', 0, 1, 'L');
        $pdf->SetX(40);
        $pdf->Cell(90, 5, 'techmind.srl.bo@gmail.com', 0, 1, 'L');

        $pdf->SetXY(135, 14);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(70, 130, 180);
        $pdf->Cell(60, 7, 'NOTA DE COMPRA', 0, 1, 'R');
        $pdf->SetFont('helvetica', '', 10);
		$pdf->SetXY(135, 22);
        $pdf->Cell(60, 6, 'NRO: ' . $codigoCompra, 0, 1, 'R');
        $pdf->SetXY(135, 28);
        $pdf->Cell(60, 6, 'FECHA: ' . $fechaCompra, 0, 1, 'R');
        $pdf->SetTextColor(0);


        $pdf->Ln(20);

        // Informacion de proveedor y usuario
        $nombreProveedor = $respuestaCliente["nombre"] ?? '';
        $contactoProveedor = $respuestaCliente["contacto"] ?? '';
        $nombreUsuario = $respuestaVendedor["nombre"] ?? '';

        $infoTable = <<<EOF
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1">
  <tr>
    <td style="border:1px solid #000; width:180px;">Proveedor: <b>$nombreProveedor</b></td>
    <td style="border:1px solid #000; width:180px;">Promotor: <b>$contactoProveedor</b></td>
    <td style="border:1px solid #000; width:180px;">Fecha: <b>$fechaCompra</b></td>
  </tr>
  <tr>
<td style="border:1px solid #000; width:270px;">Usuario: <b>$nombreUsuario</b></td>
    <td colspan="2" style="border:1px solid #000; width:270px;">Estado: <b>$estado</b></td>
  </tr>
</table>
EOF;
        $pdf->writeHTML($infoTable, false, false, false, false, '');

        // Encabezado tabla
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(100, 7, 'Producto', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Cantidad', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Valor Unit.', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Valor Total', 1, 1, 'C', true);

        // Detalle productos
        $pdf->SetFont('helvetica', '', 9);
        foreach ($productos as $item) {
            $descripcion = trim((string)($item["descripcion"] ?? "Producto"));
            $cantidad = (int)($item["cantidad"] ?? 0);
            $precio = (float)($item["precio"] ?? 0);
            $subtotal = (float)($item["total"] ?? 0);
            $valorUnitario = $precio > 0 ? "Bs ".number_format($precio, 2) : "Por definir";
            $precioTotal = $subtotal > 0 ? "Bs ".number_format($subtotal, 2) : "Por definir";

            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $altoTexto = $pdf->getStringHeight(100, $descripcion);
            $altoFila = max(8, $altoTexto + 2);

            if ($y + $altoFila > $pdf->getPageHeight() - 28) {
                $pdf->AddPage();
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell(100, 7, 'Producto', 1, 0, 'C', true);
                $pdf->Cell(30, 7, 'Cantidad', 1, 0, 'C', true);
                $pdf->Cell(30, 7, 'Valor Unit.', 1, 0, 'C', true);
                $pdf->Cell(30, 7, 'Valor Total', 1, 1, 'C', true);
                $pdf->SetFont('helvetica', '', 9);
                $x = $pdf->GetX();
                $y = $pdf->GetY();
            }

            $pdf->MultiCell(100, $altoFila, $descripcion, 1, 'L', false, 0, $x, $y, true, 0, false, true, $altoFila, 'M');
            $pdf->MultiCell(30, $altoFila, (string)$cantidad, 1, 'C', false, 0, $x + 100, $y, true, 0, false, true, $altoFila, 'M');
            $pdf->MultiCell(30, $altoFila, $valorUnitario, 1, 'C', false, 0, $x + 130, $y, true, 0, false, true, $altoFila, 'M');
            $pdf->MultiCell(30, $altoFila, $precioTotal, 1, 'C', false, 1, $x + 160, $y, true, 0, false, true, $altoFila, 'M');
            $pdf->SetY($y + $altoFila);
        }

        // Total
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(160, 7, 'TOTAL', 1, 0, 'R');
        $pdf->Cell(30, 7, "Bs $total", 1, 1, 'C');

        // Firma
        $pdf->Ln(28);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(90, 5, '___________________________', 0, 1, 'L');
        $pdf->Cell(90, 5, 'Firma del mensajero / Proveedor', 0, 1, 'L');

        // Salida
        $pdf->Output('NotaCompra.pdf', 'I');
    }
}

$factura = new ImprimirNotaCompra();
$factura->idCompra = $_GET["idCompra"] ?? '';
$factura->codigo = $_GET["codigoCompra"] ?? '';
$factura->traerImpresionNotaCompra();



