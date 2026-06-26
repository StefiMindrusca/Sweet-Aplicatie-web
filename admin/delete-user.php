<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: users.php");
    exit();
}

$id = (int) $_GET["id"];

$delete_sql = "DELETE FROM users WHERE id = $id";
mysqli_query($conn, $delete_sql);

header("Location: users.php");
exit();
?>
