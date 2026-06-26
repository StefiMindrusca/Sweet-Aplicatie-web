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

function statusLabel($status) {
    $labels = [
        "noua" => "Comandă nouă",
        "comanda noua" => "Comandă nouă",
        "confirmata" => "Confirmată",
        "in procesare" => "În pregătire",
        "in pregatire" => "În pregătire",
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
        "noua" => "comanda-noua",
        "comanda noua" => "comanda-noua",
        "confirmata" => "confirmata",
        "in procesare" => "in-pregatire",
        "in pregatire" => "in-pregatire",
        "in livrare" => "in-livrare",
        "livrata" => "livrata",
        "gata de ridicat" => "gata-de-ridicat",
        "ridicata" => "ridicata",
        "anulata" => "anulata"
    ];

    return $classes[$status] ?? "comanda-noua";
}

$order_sql = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id LIMIT 1";
$order_result = mysqli_query($conn, $order_sql);

if (!$order_result || mysqli_num_rows($order_result) == 0) {
    header("Location: profile.php");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

$items_sql = "
    SELECT oi.*, p.image AS product_image, p.id AS product_id
    FROM order_items oi
    LEFT JOIN products p ON oi.product_name = p.name
    WHERE oi.order_id = $order_id AND oi.cutie_id IS NULL
";
$items_result = mysqli_query($conn, $items_sql);

$reviews_existente = [];
$rev_result = mysqli_query($conn, "SELECT product_id FROM reviews WHERE user_id = $user_id AND order_id = $order_id");
if ($rev_result) {
    while ($r = mysqli_fetch_assoc($rev_result)) {
        $reviews_existente[] = (int)$r["product_id"];
    }
}

$status = $order["status"];

if ($order["delivery_method"] == "ridicare") {
    $steps = [
        "comanda noua",
        "confirmata",
        "in pregatire",
        "gata de ridicat",
        "ridicata"
    ];
} else {
    $steps = [
        "comanda noua",
        "confirmata",
        "in pregatire",
        "in livrare",
        "livrata"
    ];
}

if ($status == "noua") {
    $status = "comanda noua";
}

if ($status == "in procesare") {
    $status = "in pregatire";
}

$current_index = array_search($status, $steps);

if ($current_index === false) {
    $current_index = 0;
}

$status_label = statusLabel($order["status"]);
$status_class = statusClass($order["status"]);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Comanda #<?php echo $order["id"]; ?> - Sweet</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main id="main-content" class="client-order-page">

    <section class="client-order-card">

        <a href="profile.php#istoric-comenzi" class="back-link">← Înapoi la comenzi</a>

        <div class="client-order-header">
            <h1>Comanda #<?php echo $order["id"]; ?></h1>

            <?php
            $statusuri_cu_factura = [
                "confirmata",
                "in pregatire",
                "in livrare",
                "livrata",
                "gata de ridicat",
                "ridicata"
            ];
            ?>

            <div class="order-header-actions">

                <?php if (in_array($status, $statusuri_cu_factura)): ?>
                    <a href="descarca-factura.php?id=<?php echo $order["id"]; ?>" class="invoice-download-btn">
                        Descarcă factura
                    </a>
                <?php endif; ?>

                <span class="profile-order-status status-<?php echo $status_class; ?>">
            <?php echo htmlspecialchars($status_label); ?>
        </span>

            </div>
        </div>

        <?php if ($status != "anulata"): ?>
            <div class="order-progress" aria-label="Progresul comenzii">
                <?php foreach ($steps as $index => $step): ?>
                    <div class="order-progress-step <?php echo $index <= $current_index ? 'active' : ''; ?>">
                        <div class="order-progress-line" aria-hidden="true"></div>
                        <strong><?php echo htmlspecialchars(statusLabel($step)); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="client-order-cancelled">
                Această comandă a fost anulată.
            </div>
        <?php endif; ?>

        <div class="client-order-grid">

            <div class="client-order-box">
                <h2>Date client</h2>
                <p><strong><?php echo htmlspecialchars($order["prenume"] . " " . $order["nume"]); ?></strong></p>
                <p><?php echo htmlspecialchars($order["email"]); ?></p>
                <p><?php echo htmlspecialchars($order["telefon"]); ?></p>
            </div>

            <div class="client-order-box">
                <h2>Livrare</h2>

                <?php if ($order["delivery_method"] == "ridicare"): ?>
                    <p><strong>Ridicare din magazin</strong></p>
                    <p>Str. Soarelui nr. 500, Cluj-Napoca</p>
                    <p>Gata în aproximativ 2 ore</p>
                <?php else: ?>
                    <p><strong>Livrare la domiciliu</strong></p>
                    <p><?php echo htmlspecialchars($order["adresa"]); ?></p>
                    <p><?php echo htmlspecialchars($order["oras"] . ", " . $order["judet"]); ?></p>
                    <p>Cod poștal: <?php echo !empty($order["cod_postal"]) ? htmlspecialchars($order["cod_postal"]) : "-"; ?></p>
                <?php endif; ?>
            </div>

        </div>

        <div class="client-order-products">
            <h2>Produse comandate</h2>

            <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                <?php $este_cutie = (int)$item["e_cutie"] == 1; ?>
                <div class="client-order-product">
                    <div class="cart-image-box">
                        <?php
                        $img = $este_cutie
                            ? (strpos($item["product_name"], '12') !== false ? 'imagini/cutie-12.png' : (strpos($item["product_name"], '6') !== false ? 'imagini/cutiei_6.png' : 'imagini/cutiei-4.png'))
                            : (!empty($item["product_image"]) ? 'imagini/' . $item["product_image"] : '');
                        ?>
                        <?php if (!empty($img)): ?><img src="<?php echo htmlspecialchars($img); ?>" alt=""><?php endif; ?>
                    </div>
                    <div>
                        <strong><?php echo htmlspecialchars($item["product_name"]); ?></strong>
                        <p><?php echo number_format($item["price"], 2); ?> lei x <?php echo (int)$item["quantity"]; ?></p>

                        <?php if ($este_cutie): ?>
                            <?php
                            $id_cutie = (int)$item["id"];
                            $continut_result = mysqli_query($conn, "
                                SELECT oi.*, p.id AS product_id
                                FROM order_items oi
                                LEFT JOIN products p ON oi.product_name = p.name
                                WHERE oi.cutie_id = $id_cutie
                            ");
                            ?>
                            <?php if ($continut_result && mysqli_num_rows($continut_result) > 0): ?>
                                <ul class="client-cutie-continut">
                                    <?php while ($produs_cutie = mysqli_fetch_assoc($continut_result)): ?>
                                        <?php $subtotal_cutie = $produs_cutie["price"] * $produs_cutie["quantity"]; ?>
                                        <li>
                                            <span>
                                                <?php echo htmlspecialchars($produs_cutie["product_name"]); ?>
                                                x <?php echo (int)$produs_cutie["quantity"]; ?>
                                            </span>
                                            <span><?php echo number_format($subtotal_cutie, 2); ?> lei</span>
                                            <?php if (in_array($status, ['livrata', 'ridicata']) && !empty($produs_cutie["product_id"])): ?>
                                                <?php if (in_array((int)$produs_cutie["product_id"], $reviews_existente)): ?>
                                                    <span class="recenzie-done">Recenzie trimisă</span>
                                                <?php else: ?>
                                                    <button class="btn-recenzie"
                                                        data-product-id="<?php echo (int)$produs_cutie["product_id"]; ?>"
                                                        data-product-name="<?php echo htmlspecialchars($produs_cutie["product_name"]); ?>"
                                                        onclick="deschideModalRecenzie(this)">
                                                        Lasă recenzie
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="client-order-product-right">
                        <strong><?php echo number_format($item["price"] * $item["quantity"], 2); ?> lei</strong>

                        <?php
                        $poate_recenza = in_array($status, ['livrata', 'ridicata']) && !$este_cutie && !empty($item["product_id"]);
                        $a_recenzat = in_array((int)$item["product_id"], $reviews_existente);
                        ?>
                        <?php if ($poate_recenza): ?>
                            <?php if ($a_recenzat): ?>
                                <span class="recenzie-done">Recenzie trimisă</span>
                            <?php else: ?>
                                <button class="btn-recenzie"
                                    data-product-id="<?php echo (int)$item["product_id"]; ?>"
                                    data-product-name="<?php echo htmlspecialchars($item["product_name"]); ?>"
                                    onclick="deschideModalRecenzie(this)">
                                    Lasă recenzie
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>

            <div class="client-order-product">
                <div>
                    <strong>Transport</strong>
                </div>
                <strong>
                    <?php if ((float)$order["transport"] == 0): ?>
                        GRATUIT
                    <?php else: ?>
                        <?php echo number_format($order["transport"], 2); ?> lei
                    <?php endif; ?>
                </strong>
            </div>

            <div class="client-order-total">
                <span>Total</span>
                <strong><?php echo number_format($order["total"], 2); ?> lei</strong>
            </div>
        </div>

    </section>

</main>

<?php include 'includes/footer.php'; ?>

<div id="modalRecenzie" class="recenzie-modal-overlay">
    <div class="recenzie-modal">
        <button class="recenzie-modal-close" onclick="inchideModalRecenzie()">×</button>
        <h2>Lasă o recenzie</h2>
        <p id="recenzieProductName" class="recenzie-product-name"></p>

        <form action="salveaza-recenzie.php" method="POST">
            <input type="hidden" name="product_id" id="recenzieProductId">
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">

            <div class="recenzie-stele">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="rating" id="stea<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                    <label for="stea<?php echo $i; ?>">★</label>
                <?php endfor; ?>
            </div>

            <textarea name="comentariu" class="recenzie-textarea" placeholder="Scrie un comentariu (opțional)" rows="4"></textarea>

            <button type="submit" class="btn btn-dark">Trimite recenzia</button>
        </form>
    </div>
</div>

<script>
    function deschideModalRecenzie(btn) {
        document.getElementById("recenzieProductId").value = btn.dataset.productId;
        document.getElementById("recenzieProductName").textContent = btn.dataset.productName;
        document.getElementById("modalRecenzie").classList.add("activ");
    }

    function inchideModalRecenzie() {
        document.getElementById("modalRecenzie").classList.remove("activ");
    }

    document.getElementById("modalRecenzie").addEventListener("click", function(e) {
        if (e.target === this) inchideModalRecenzie();
    });
</script>

</body>
</html>
