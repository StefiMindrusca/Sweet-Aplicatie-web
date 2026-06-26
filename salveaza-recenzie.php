<?php
session_start();
include 'config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];
$product_id = (int)($_POST["product_id"] ?? 0);
$order_id = (int)($_POST["order_id"] ?? 0);
$rating = (int)($_POST["rating"] ?? 0);
$comentariu = mysqli_real_escape_string($conn, trim($_POST["comentariu"] ?? ""));

if (!$product_id || !$order_id || $rating < 1 || $rating > 5) {
    header("Location: client-order-details.php?id=$order_id");
    exit();
}

$check = mysqli_query($conn, "SELECT id FROM orders WHERE id = $order_id AND user_id = $user_id LIMIT 1");
if (!$check || mysqli_num_rows($check) == 0) {
    header("Location: profile.php");
    exit();
}

$exists = mysqli_query($conn, "SELECT id FROM reviews WHERE user_id = $user_id AND product_id = $product_id AND order_id = $order_id LIMIT 1");
if ($exists && mysqli_num_rows($exists) > 0) {
    header("Location: client-order-details.php?id=$order_id");
    exit();
}

mysqli_query($conn, "INSERT INTO reviews (user_id, product_id, order_id, rating, comentariu) VALUES ($user_id, $product_id, $order_id, $rating, '$comentariu')");

header("Location: client-order-details.php?id=$order_id&recenzie=trimisa");
exit();

