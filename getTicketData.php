<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Simulación de datos
$idTicket = $_GET['id'] ?? null;

if (!$idTicket) {
    echo json_encode(["status" => "error", "message" => "No se proporcionó ID"]);
    exit;
}

// Simulación de configuración de la empresa
$configDatos = [
    'gral_info_nombre_empresa' => 'Empresa Demo',
    'gral_info_propietario_empresa' => 'Propietario Demo',
    'gral_info_giro_empresa' => 'Giro de la Empresa',
    'gral_info_nrc_empresa' => 'NRC123456',
    'gral_info_nit_empresa' => 'NIT123456',
    'gral_info_direccion_empresa' => 'Dirección de la Empresa'
];

// Simulación de productos
$productos = [
    ["cant" => 2, "sku" => "P001", "desc" => "Producto A", "costo" => 5.00],
    ["cant" => 1, "sku" => "P002", "desc" => "Producto B", "costo" => 3.50]
];

// Simulación de datos a imprimir
$dataPrint = [
    "printer" => "POS-80C",
    "nombre_empresa" => $configDatos['gral_info_nombre_empresa'],
    "rsocial_empresa" => $configDatos['gral_info_propietario_empresa'],
    "giro_empresa" => $configDatos['gral_info_giro_empresa'],
    "nrc_empresa" => $configDatos['gral_info_nrc_empresa'],
    "nit_empresa" => $configDatos['gral_info_nit_empresa'],
    "direcion_empresa" => $configDatos['gral_info_direccion_empresa'],
    "documento" => "FACTURA",
    "fecha" => date("Y-m-d H:i:s"),
    "caja" => "Caja 1",
    "cliente" => "Cliente Demo",
    "clienteDoc" => "NIT",
    "clienteDocNum" => "12345678-9",
    "clienteNRC" => "123456",
    "productos_normal" => $productos,
    "totalGrabadas" => 13.50,
    "totalExento" => 0,
    "totalNS" => 0,
    "totales" => ["totalTotal" => 13.50],
    "efectivo" => 20.00,
    "dte_numero_control" => "DTE123456",
    "dte_codigo_generacion" => "GEN123456",
    "referencia" => "REF123456",
    "isc" => "demo"
];

echo json_encode(["status" => "success", "dataPrint" => $dataPrint]);
