<?php
session_start();

include '../config/db.php';
/** @var mysqli $conn */
include '../includes/send-order-email.php';

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int)$_GET["id"];

function statusLabel($status) {
    $labels = [
        "comanda noua" => "Comandă nouă",
        "noua" => "Comandă nouă",
        "confirmata" => "Confirmată",
        "in pregatire" => "În pregătire",
        "in procesare" => "În pregătire",
        "in livrare" => "În livrare",
        "livrata" => "Livrată",
        "gata de ridicat" => "Gata de ridicat",
        "ridicata" => "Ridicată",
        "anulata" => "Anulată"
    ];

    return $labels[$status] ?? $status;
}

function statusClass($status) {
    $classes = [
        "comanda noua" => "comanda-noua",
        "noua" => "comanda-noua",
        "confirmata" => "confirmata",
        "in pregatire" => "in-pregatire",
        "in procesare" => "in-pregatire",
        "in livrare" => "in-livrare",
        "livrata" => "livrata",
        "gata de ridicat" => "gata-de-ridicat",
        "ridicata" => "ridicata",
        "anulata" => "anulata"
    ];

    return $classes[$status] ?? "comanda-noua";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["status"])) {
    $status = mysqli_real_escape_string($conn, $_POST["status"]);

    $old_order_result = mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id LIMIT 1");

    if ($old_order_result && mysqli_num_rows($old_order_result) > 0) {
        $old_order = mysqli_fetch_assoc($old_order_result);

        mysqli_query($conn, "UPDATE orders SET status = '$status' WHERE id = $order_id");

        if ($old_order["status"] != $status) {

            $updated_order_result = mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id LIMIT 1");

            if ($updated_order_result && mysqli_num_rows($updated_order_result) > 0) {

                $updated_order = mysqli_fetch_assoc($updated_order_result);

                try {

                    if ($status == "confirmata") {

                        require_once '../includes/generare-factura.php';

                        $pdfPath = genereazaFactura($order_id);

                        sendOrderStatusEmail(
                            $updated_order,
                            $status,
                            $pdfPath
                        );

                    } else {

                        sendOrderStatusEmail(
                            $updated_order,
                            $status
                        );
                    }

                } catch (Exception $e) {

                    $_SESSION["admin_message"] =
                        "Statusul a fost actualizat, dar emailul nu a putut fi trimis.";
                }
            }
        }


    }

    header("Location: order-details.php?id=" . $order_id);
    exit();
}

$order_result = mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id LIMIT 1");

if (!$order_result || mysqli_num_rows($order_result) == 0) {
    header("Location: orders.php");
    exit();
}

$order = mysqli_fetch_assoc($order_result);
$items_result = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = $order_id AND cutie_id IS NULL");

$message = $_SESSION["admin_message"] ?? "";
unset($_SESSION["admin_message"]);

$delivery_method = $order["delivery_method"] ?? "domiciliu";

if ($delivery_method == "ridicare") {
    $status_options = [
        "comanda noua" => "Comandă nouă",
        "confirmata" => "Confirmată",
        "in pregatire" => "În pregătire",
        "gata de ridicat" => "Gata de ridicat",
        "ridicata" => "Ridicată",
        "anulata" => "Anulată"
    ];
} else {
    $status_options = [
        "comanda noua" => "Comandă nouă",
        "confirmata" => "Confirmată",
        "in pregatire" => "În pregătire",
        "in livrare" => "În livrare",
        "livrata" => "Livrată",
        "anulata" => "Anulată"
    ];
}

$delivery_label = "Livrare la domiciliu";
if (($order["delivery_method"] ?? "domiciliu") == "ridicare") {
    $delivery_label = "Ridicare din magazin";
}

$payment_label = "Card online";
if (($order["metoda_plata"] ?? "card") == "cash") {
    $payment_label = "Cash";
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Comanda #<?php echo $order["id"]; ?></title>
    <link rel="stylesheet" href="admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">

    <aside class="admin-sidebar">
        <h2>Sweet Admin</h2>

        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Produse</a>
        <a href="categories.php">Categorii</a>
        <a href="orders.php" class="active-admin-link">Comenzi</a>
        <a href="users.php">Utilizatori</a>
        <a href="recenzii.php">Recenzii</a>
        <a href="../logout.php"><img src="../imagini/exit.png" alt="" class="admin-logout-icon"> Logout</a>
    </aside>

    <main class="admin-main">

        <?php if (!empty($message)): ?>
            <div class="admin-message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="order-details-layout">

            <section class="order-products-panel">
                <a href="orders.php" class="order-back-link">← Înapoi la comenzi</a>

                <h2>Produse comandate</h2>

                <div class="order-products-list">
                    <?php if ($items_result && mysqli_num_rows($items_result) > 0): ?>
                        <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                            <?php $este_cutie = (int)$item["e_cutie"] == 1; ?>
                            <div class="order-product-card">
                                <div>
                                    <strong>
                                        <?php echo htmlspecialchars($item["product_name"]); ?>
                                        — <?php echo number_format($item["price"] * $item["quantity"], 2); ?> lei
                                    </strong>
                                    <p>
                                        <?php echo number_format($item["price"], 2); ?> lei
                                        x <?php echo (int)$item["quantity"]; ?>
                                    </p>

                                    <?php if ($este_cutie): ?>
                                        <?php
                                        $id_cutie = (int)$item["id"];
                                        $continut_result = mysqli_query($conn, "SELECT * FROM order_items WHERE cutie_id = $id_cutie");
                                        ?>
                                        <?php if ($continut_result && mysqli_num_rows($continut_result) > 0): ?>
                                            <ul class="order-cutie-continut">
                                                <?php while ($produs_cutie = mysqli_fetch_assoc($continut_result)): ?>
                                                    <?php
                                                        $cantitate_reala = (int)$produs_cutie["quantity"] * (int)$item["quantity"];
                                                        $subtotal_cutie = $produs_cutie["price"] * $cantitate_reala;
                                                    ?>
                                                    <li>
                                                        <div class="cutie-produs-rand">
                                                            <span class="cutie-produs-nume"><?php echo htmlspecialchars($produs_cutie["product_name"]); ?></span>
                                                            <span class="cutie-produs-qty">x <?php echo $cantitate_reala; ?></span>
                                                        </div>
                                                        <div class="cutie-produs-rand cutie-produs-preturi">
                                                            <span>Preț unitar: <?php echo number_format($produs_cutie["price"], 2); ?> lei</span>
                                                            <span>Total: <?php echo number_format($subtotal_cutie, 2); ?> lei</span>
                                                        </div>
                                                    </li>
                                                <?php endwhile; ?>
                                            </ul>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <p>Nu există produse pentru această comandă.</p>
                    <?php endif; ?>
                </div>

                <div class="order-transport-row">
                    <span>Transport</span>
                    <span>
                        <?php if ((float)$order["transport"] == 0): ?>
                            GRATUIT
                        <?php else: ?>
                            <?php echo number_format($order["transport"], 2); ?> lei
                        <?php endif; ?>
                    </span>
                </div>

                <div class="order-products-total">
                    <span>Total comandă</span>
                    <strong><?php echo number_format($order["total"], 2); ?> lei</strong>
                </div>
            </section>

            <section class="order-details-box">

                <?php
                $statusuri_cu_factura = [
                    "confirmata",
                    "in pregatire",
                    "in livrare",
                    "livrata",
                    "gata de ridicat",
                    "ridicata"
                ];
                $status_curent = $order["status"];
                if ($status_curent == "noua") { $status_curent = "comanda noua"; }
                if ($status_curent == "in procesare") { $status_curent = "in pregatire"; }
                ?>

                <div class="order-details-header">
                    <h2>Comanda #<?php echo $order["id"]; ?></h2>

                    <div class="order-header-actions">
                        <?php if (in_array($status_curent, $statusuri_cu_factura)): ?>
                            <a href="../descarca-factura.php?id=<?php echo $order["id"]; ?>" class="invoice-download-btn">
                                Descarcă factura
                            </a>
                        <?php endif; ?>

                        <span class="profile-order-status status-<?php echo statusClass($order["status"]); ?>">
                            <?php echo htmlspecialchars(statusLabel($order["status"])); ?>
                        </span>
                    </div>
                </div>

                <div class="order-client-box">
                    <h3>
                        <?php echo htmlspecialchars($order["prenume"] . " " . $order["nume"]); ?>
                    </h3>

                    <p>📧 <?php echo htmlspecialchars($order["email"]); ?></p>
                    <p>📞 <?php echo htmlspecialchars($order["telefon"]); ?></p>
                </div>

                <div class="order-info-grid">
                    <div>
                        <span>Data comenzii</span>
                        <strong><?php echo htmlspecialchars($order["created_at"]); ?></strong>
                    </div>

                    <div>
                        <span>Metodă livrare</span>
                        <strong><?php echo htmlspecialchars($delivery_label); ?></strong>
                    </div>

                    <div>
                        <span>Metodă plată</span>
                        <strong><?php echo htmlspecialchars($payment_label); ?></strong>
                    </div>
                </div>

                <div class="order-address-box">
                    <?php if (($order["delivery_method"] ?? "domiciliu") == "ridicare"): ?>
                        <h3>Ridicare din magazin</h3>
                        <p>
                            Str. Soarelui nr. 500<br>
                            Cluj-Napoca, Cluj<br>
                            Gata în aproximativ 2 ore
                        </p>
                    <?php else: ?>
                        <h3>Adresa de livrare</h3>
                        <p>
                            <?php echo htmlspecialchars($order["adresa"]); ?><br>
                            <?php echo htmlspecialchars($order["oras"]); ?>,
                            <?php echo htmlspecialchars($order["judet"]); ?><br>
                            Cod poștal: <?php echo !empty($order["cod_postal"]) ? htmlspecialchars($order["cod_postal"]) : "-"; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <form method="post" class="order-status-form">
                    <label for="status">Actualizare status</label>

                    <div class="order-status-row">
                        <select name="status" id="status">
                            <?php foreach ($status_options as $value => $label): ?>
                                <option
                                    value="<?php echo htmlspecialchars($value); ?>"
                                    <?php if ($order["status"] == $value) echo "selected"; ?>
                                >
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="admin-btn">Salvează</button>
                    </div>
                </form>

            </section>

        </div>

    </main>

</div>

</body>
</html>
