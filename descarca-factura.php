<?php
session_start();
include 'config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];
$order_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

$is_admin = isset($_SESSION["user_role"]) && $_SESSION["user_role"] == "admin";

if ($is_admin) {
    $result = mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id LIMIT 1");
} else {
    $result = mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id LIMIT 1");
}

$redirect_lipsa = $is_admin ? "admin/orders.php" : "profile.php";
$redirect_detalii = $is_admin
    ? "admin/order-details.php?id=" . $order_id
    : "client-order-details.php?id=" . $order_id;

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: " . $redirect_lipsa);
    exit();
}

$order = mysqli_fetch_assoc($result);

$statusuri_cu_factura = [
    "confirmata",
    "in pregatire",
    "in livrare",
    "livrata",
    "gata de ridicat",
    "ridicata"
];

if (!in_array($order["status"], $statusuri_cu_factura)) {
    header("Location: " . $redirect_detalii);
    exit();
}

$pdfPath = __DIR__ . "/facturi/factura_comanda_" . $order_id . ".pdf";

if (!file_exists($pdfPath)) {
    require_once 'includes/generare-factura.php';
    $pdfPath = genereazaFactura($order_id);
}

if (!$pdfPath || !file_exists($pdfPath)) {
    header("Location: " . $redirect_detalii);
    exit();
}

header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=factura_comanda_" . $order_id . ".pdf");
header("Content-Length: " . filesize($pdfPath));

readfile($pdfPath);
exit();
