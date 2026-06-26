<?php
session_start();
include 'config/db.php';
/** @var mysqli $conn */

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

$category = "";
$box_size = 0;
$box_items_json = "";

if (isset($_POST["category"])) {
    $category = trim($_POST["category"]);
}

if (isset($_POST["box_size"])) {
    $box_size = (int)$_POST["box_size"];
}

if (isset($_POST["box_items"])) {
    $box_items_json = $_POST["box_items"];
}

if ($box_size <= 0 || empty($box_items_json)) {
    header("Location: index.php");
    exit();
}

$box_data = json_decode($box_items_json, true);

if (!$box_data || !isset($box_data["items"]) || !is_array($box_data["items"])) {
    header("Location: index.php");
    exit();
}

$total_price = 0;
$total_items = 0;

foreach ($box_data["items"] as $item) {
    if (!isset($item["price"]) || !isset($item["quantity"])) {
        continue;
    }

    $price = (float)$item["price"];
    $quantity = (int)$item["quantity"];

    $total_price += $price * $quantity;
    $total_items += $quantity;
}

if ($total_items != $box_size) {
    header("Location: index.php");
    exit();
}

$in_cos = array();
if (isset($_SESSION["cos"])) {
    foreach ($_SESSION["cos"] as $cos_item) {
        if ($cos_item["type"] == "box" && isset($cos_item["items"])) {
            foreach ($cos_item["items"] as $cos_produs) {
                $cos_pid = (int)$cos_produs["id"];
                $cos_qty = (int)$cos_produs["quantity"] * (int)$cos_item["quantity"];
                if (!isset($in_cos[$cos_pid])) {
                    $in_cos[$cos_pid] = 0;
                }
                $in_cos[$cos_pid] += $cos_qty;
            }
        }
    }
}

foreach ($box_data["items"] as $item) {
    if (!isset($item["id"]) || !isset($item["quantity"])) {
        continue;
    }

    $pid = (int)$item["id"];
    $necesar = (int)$item["quantity"];
    $deja_in_cos = isset($in_cos[$pid]) ? $in_cos[$pid] : 0;
    $total_necesar = $necesar + $deja_in_cos;

    $res = mysqli_query($conn, "SELECT stock FROM products WHERE id = $pid LIMIT 1");
    if (!$res || mysqli_num_rows($res) == 0) {
        header("Location: boxbuild.php?name=" . urlencode($category) . "&error=stock");
        exit();
    }

    $row = mysqli_fetch_assoc($res);
    if ((int)$row["stock"] < $total_necesar) {
        header("Location: boxbuild.php?name=" . urlencode($category) . "&error=stock");
        exit();
    }
}

if (!isset($_SESSION["cos"])) {
    $_SESSION["cos"] = array();
}

$cutie_identica_key = null;

foreach ($_SESSION["cos"] as $cos_key => $cos_item) {
    if ($cos_item["type"] != "box") continue;
    if ($cos_item["box_size"] != $box_size) continue;

    $cos_items = $cos_item["items"];
    $new_items = $box_data["items"];

    if (count($cos_items) != count($new_items)) continue;

    $identice = true;

    foreach ($new_items as $new_produs) {
        $new_pid = (string)$new_produs["id"];
        $new_qty = (int)$new_produs["quantity"];

        $gasit = false;
        foreach ($cos_items as $cos_produs) {
            if ((string)$cos_produs["id"] == $new_pid && (int)$cos_produs["quantity"] == $new_qty) {
                $gasit = true;
                break;
            }
        }

        if (!$gasit) {
            $identice = false;
            break;
        }
    }

    if ($identice) {
        $cutie_identica_key = $cos_key;
        break;
    }
}

if ($cutie_identica_key !== null) {
    $_SESSION["cos"][$cutie_identica_key]["quantity"]++;
} else {
    $box_id = "box_" . time() . rand(100, 999);

    $box_images = [
        4  => "imagini/cutiei-4.png",
        6  => "imagini/cutiei_6.png",
        12 => "imagini/cutie-12.png",
    ];
    $box_image = $box_images[$box_size] ?? "imagini/cutie-" . $box_size . ".png";

    $_SESSION["cos"][$box_id] = array(
        "id" => $box_id,
        "type" => "box",
        "name" => "Cutie " . $box_size . " buc - " . $category,
        "price" => $total_price,
        "quantity" => 1,
        "box_size" => $box_size,
        "image" => $box_image,
        "items" => $box_data["items"]
    );
}

if (isset($_SESSION["user_id"])) {
    $id_utilizator = (int)$_SESSION["user_id"];
    $cos_json = mysqli_real_escape_string($conn, json_encode($_SESSION["cos"]));
    $sql = "INSERT INTO cos_persistent (user_id, cos_client) VALUES ($id_utilizator, '$cos_json') ON DUPLICATE KEY UPDATE cos_client = '$cos_json'";
    mysqli_query($conn, $sql);
}

header("Location: boxbuild.php?name=" . urlencode($category) . "&added=1");
exit();
