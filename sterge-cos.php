<?php
session_start();
include 'config/db.php';
/** @var mysqli $conn */

if (isset($_GET["id"]) && isset($_SESSION["cos"][$_GET["id"]])) {
    unset($_SESSION["cos"][$_GET["id"]]);

    if (isset($_SESSION["user_id"])) {
        $id_utilizator = (int)$_SESSION["user_id"];
        $cos_json = mysqli_real_escape_string($conn, json_encode($_SESSION["cos"]));
        if (empty($_SESSION["cos"])) {
            mysqli_query($conn, "DELETE FROM cos_persistent WHERE user_id = $id_utilizator");
        } else {
            $sql = "INSERT INTO cos_persistent (user_id, cos_client) VALUES ($id_utilizator, '$cos_json') ON DUPLICATE KEY UPDATE cos_client = '$cos_json'";
            mysqli_query($conn, $sql);
        }
    }
}

header("Location: cos.php");
exit();