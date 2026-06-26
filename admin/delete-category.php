<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: categories.php");
    exit();
}

$id = (int)$_GET["id"];

$category_sql = "SELECT * FROM categories WHERE id = $id";
$category_result = mysqli_query($conn, $category_sql);

if (!$category_result || mysqli_num_rows($category_result) == 0) {
    $_SESSION["category_message"] = "Categoria nu există.";
    header("Location: categories.php");
    exit();
}

$category = mysqli_fetch_assoc($category_result);
$category_name = mysqli_real_escape_string($conn, $category["name"]);

$check_products_sql = "SELECT * FROM products WHERE category = '$category_name' LIMIT 1";
$check_products_result = mysqli_query($conn, $check_products_sql);

if ($check_products_result && mysqli_num_rows($check_products_result) > 0) {
    $_SESSION["category_message"] = "Nu poți șterge această categorie deoarece există produse asociate.";
    header("Location: categories.php");
    exit();
}

$delete_sql = "DELETE FROM categories WHERE id = $id";

if (mysqli_query($conn, $delete_sql)) {
    $_SESSION["category_message"] = "Categoria a fost ștearsă cu succes.";
} else {
    $_SESSION["category_message"] = "Eroare la ștergerea categoriei.";
}

header("Location: categories.php");
exit();
