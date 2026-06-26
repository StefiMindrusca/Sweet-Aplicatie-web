<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: products.php");
    exit();
}

$id = (int) $_GET["id"];

$sql = "SELECT * FROM products WHERE id = $id";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $product = mysqli_fetch_assoc($result);

    if (!empty($product["image"])) {
        $image_path = "../imagini/" . $product["image"];

        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    $delete_sql = "DELETE FROM products WHERE id = $id";
    mysqli_query($conn, $delete_sql);
}

header("Location: products.php");
exit();
?>
