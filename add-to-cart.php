<?php
session_start();
include 'config/db.php';
/** @var mysqli $conn */

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET["id"];
$quantity = isset($_GET["quantity"]) ? (int)$_GET["quantity"] : 1;

if ($quantity < 1) {
    $quantity = 1;
}

$sql = "SELECT * FROM products WHERE id = $id LIMIT 1";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit();
}

$product = mysqli_fetch_assoc($result);

if (!isset($_SESSION["cos"])) {
    $_SESSION["cos"] = [];
}

$cantitate_existenta = isset($_SESSION["cos"][$id]) ? $_SESSION["cos"][$id]["quantity"] : 0;
$cantitate_totala = $cantitate_existenta + $quantity;

if ($cantitate_totala > (int)$product["stock"]) {
    header("Location: product-details.php?id=" . $id . "&error=stock");
    exit();
}

if (isset($_SESSION["cos"][$id])) {
    $_SESSION["cos"][$id]["quantity"] += $quantity;
} else {
    $_SESSION["cos"][$id] = [
        "id" => $product["id"],
        "type" => "simple",
        "name" => $product["name"],
        "price" => (float)$product["price"],
        "image" => !empty($product["image"]) ? "imagini/" . $product["image"] : "",
        "quantity" => $quantity
    ];
}

if (isset($_SESSION["user_id"])) {
    $id_utilizator = (int)$_SESSION["user_id"];
    $cos_json = mysqli_real_escape_string($conn, json_encode($_SESSION["cos"]));
    $sql = "INSERT INTO cos_persistent (user_id, cos_client) VALUES ($id_utilizator, '$cos_json') ON DUPLICATE KEY UPDATE cos_client = '$cos_json'";
    mysqli_query($conn, $sql);
}

header("Location: product-details.php?id=" . $id . "&added=1");
exit();
