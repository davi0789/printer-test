<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require __DIR__ . '/vendor/autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

// Recibir JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['dataPrint'])) {
    echo json_encode(["status" => "error", "message" => "No se recibieron datos para imprimir"]);
    exit;
}

$dataPrint = $input['dataPrint'];

// Función para imprimir (la misma que tienes, adaptada para array asociativo)
function imprimirTicket($dataPrint) {
    $PRINTER_NAME = $dataPrint['printer'] ?? 'POS-80C';
    $maxDescripcion = 25;

    try {
        $connector = new WindowsPrintConnector($PRINTER_NAME);
        $printer = new Printer($connector);

        // Encabezado
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(2, 2);
        $printer->text($dataPrint['nombre_empresa'] . "\n");
        $printer->setTextSize(1, 1);
        $printer->text($dataPrint['rsocial_empresa'] . "\n");
        $printer->text($dataPrint['giro_empresa'] . "\n");
        $printer->text("NRC: " . $dataPrint['nrc_empresa'] . " | NIT: " . $dataPrint['nit_empresa'] . "\n");
        $printer->text($dataPrint['direcion_empresa'] . "\n");
        $printer->text(str_repeat("-", 45) . "\n");

        if (!empty($dataPrint['documento'])) {
            $printer->setEmphasis(true);
            $printer->text(strtoupper($dataPrint['documento']) . "\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat("-", 45) . "\n");
        }

        // Datos generales
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setEmphasis(true);
        $printer->text("Fecha: ");
        $printer->setEmphasis(false);
        $printer->text($dataPrint['fecha'] . "  ");
        $printer->setEmphasis(true);
        $printer->text("Caja: ");
        $printer->setEmphasis(false);
        $printer->text($dataPrint['caja'] . "\n");

        // Cliente
        if (!empty($dataPrint['cliente'])) {
            $printer->setEmphasis(true);
            $printer->text("Cliente:\n");
            $printer->setEmphasis(false);
            $printer->text($dataPrint['cliente'] . "\n");
        }
        if (!empty($dataPrint['clienteDoc'])) {
            $doc = $dataPrint['clienteDoc'];
            if (!empty($dataPrint['clienteDocNum'])) {
                $doc .= ": " . $dataPrint['clienteDocNum'];
            }
            $printer->text($doc . "\n");
        }
        if (!empty($dataPrint['clienteNRC'])) {
            $printer->text("NRC: " . $dataPrint['clienteNRC'] . "\n");
        }
        $printer->text("\n");

        // Productos
        $printer->setEmphasis(true);
        $printer->text("Cant  Descripcion                 Precio  Total\n");
        $printer->text(str_repeat("-", 45) . "\n");
        $printer->setEmphasis(false);

        foreach ($dataPrint['productos_normal'] as $producto) {
            $totalUnitario = $producto['cant'] * $producto['costo'];
            $cantidad = str_pad(number_format($producto['cant'], 2), 4, " ", STR_PAD_RIGHT);
            $precio = str_pad(number_format($producto['costo'], 2), 7, " ", STR_PAD_LEFT);
            $total = str_pad(number_format($totalUnitario, 2), 7, " ", STR_PAD_LEFT);

            $descripcion = wordwrap($producto['sku'] . ' ' . $producto['desc'], $maxDescripcion, "\n", true);
            $lineasDescripcion = explode("\n", $descripcion);

            $printer->text($cantidad . " " . str_pad($lineasDescripcion[0], $maxDescripcion, " ") . " " . $precio . " " . $total . "\n");

            for ($i = 1; $i < count($lineasDescripcion); $i++) {
                $printer->text("     " . str_pad($lineasDescripcion[$i], $maxDescripcion) . "\n");
            }
        }

        $printer->text(str_repeat("-", 45) . "\n");

        // Totales
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->setEmphasis(true);
        $printer->setTextSize(2, 1);
        $printer->text("TOTAL $" . number_format($dataPrint['totales']['totalTotal'], 2) . "\n");
        $printer->setTextSize(1, 1);
        $printer->setEmphasis(false);
        $printer->text("EFECTIVO       $" . number_format($dataPrint['efectivo'], 2) . "\n");
        $cambio = $dataPrint['efectivo'] - $dataPrint['totales']['totalTotal'];
        $printer->text("CAMBIO         $" . number_format($cambio, 2) . "\n");

        $printer->text(str_repeat("_", 45) . "\n");

        // DTE 
        /* $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("NUMERO DE CONTROL: \n" . $dataPrint['dte_numero_control'] . "\n");
        $printer->text("CÓDIGO DE GENERACION: \n" . $dataPrint['dte_codigo_generacion'] . "\n\n");*/

        // Disclaimer
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $disclaimer = "Este comprobante no es un documento fiscal. Su DTE será enviado al correo electrónico proporcionado.";
        $lineasDisclaimer = wordwrap($disclaimer, 45, "\n", true);
        foreach (explode("\n", $lineasDisclaimer) as $linea) {
            $printer->text($linea . "\n");
        }

        $printer->text("También puedes escanear el código QR para ver y descargar tu factura electrónica en línea.\n");

        // QR 
        /* urlQR = "https://consultadte.factuexpress.com.sv/" . ($dataPrint['isc'] ?? "demo") . "/" . $dataPrint['dte_codigo_generacion'];
        $printer->qrCode($urlQR, Printer::QR_ECLEVEL_L, 6);*/

        $printer->text("\n¡Gracias por su compra!\n");

        // Referencia y código de barras
        $printer->text("\nRef: " . ($dataPrint['referencia'] ?? "") . "\n");
        if (!empty($dataPrint['referencia'])) {
            $printer->setBarcodeHeight(40);
            $printer->setBarcodeWidth(4);
            $printer->barcode($dataPrint['referencia'], Printer::BARCODE_CODE39);
        }

        $printer->feed(2);
        $printer->cut();
        $printer->pulse();
        $printer->close();

        return ["status" => "success", "message" => "Ticket impreso correctamente."];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Error al imprimir: " . $e->getMessage()];
    }
}

$resultado = imprimirTicket($dataPrint);
echo json_encode($resultado);

