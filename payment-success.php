<?php
session_start();
include 'config/db.php';
/** @var mysqli $conn */
include 'config/stripe.php';
include 'includes/send-order-email.php';

$is_cash_order = isset($_GET["cash_order"]) && $_GET["cash_order"] == "1";
$order_id_from_url = isset($_GET["order_id"]) ? (int)$_GET["order_id"] : 0;
$payment_intent_id = trim($_GET["payment_intent"] ?? "");

$order_id = 0;
$is_new_order = false;

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

if ($order_id_from_url > 0) {
    $order_id = $order_id_from_url;
} else {

    if (!$is_cash_order && empty($payment_intent_id)) {
        header("Location: index.php");
        exit();
    }

    $metoda_plata = $is_cash_order ? "cash" : "card";
    $pi_escaped = "";

    if (!$is_cash_order) {
        $ch = curl_init("https://api.stripe.com/v1/payment_intents/" . urlencode($payment_intent_id));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ":");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $intent = json_decode($response, true);

        if (!isset($intent["status"]) || $intent["status"] !== "succeeded") {
            header("Location: cos.php?payment_failed=1");
            exit();
        }

        $pi_escaped = mysqli_real_escape_string($conn, $payment_intent_id);

        $existing = mysqli_query($conn, "SELECT id FROM orders WHERE payment_intent_id = '$pi_escaped'");

        if ($existing && mysqli_num_rows($existing) > 0) {
            $order_id = mysqli_fetch_assoc($existing)["id"];
        }
    }

    if (empty($order_id)) {
        $checkout = $_SESSION["checkout_data"] ?? [];
        $cos = $_SESSION["cos"] ?? [];

        if (empty($checkout) || empty($cos)) {
            header("Location: cos.php");
            exit();
        }

        $metoda_livrare = $checkout["metoda_livrare"] ?? "domiciliu";

        $subtotal = 0;
        foreach ($cos as $item) {
            $subtotal += $item["price"] * $item["quantity"];
        }

        if ($metoda_livrare == "ridicare") {
            $transport_valoare = 0;
        } elseif ($subtotal >= 50) {
            $transport_valoare = 0;
        } else {
            $transport_valoare = 15;
        }

        $total = $subtotal + $transport_valoare;

        $user_id = isset($_SESSION["user_id"]) ? (int)$_SESSION["user_id"] : 0;

        $prenume = mysqli_real_escape_string($conn, $checkout["prenume"]);
        $nume = mysqli_real_escape_string($conn, $checkout["nume"]);
        $email = mysqli_real_escape_string($conn, $checkout["email"]);
        $telefon = mysqli_real_escape_string($conn, $checkout["telefon"]);
        $adresa = mysqli_real_escape_string($conn, $checkout["adresa"] ?? "");
        $oras = mysqli_real_escape_string($conn, $checkout["oras"] ?? "");
        $judet = mysqli_real_escape_string($conn, $checkout["judet"] ?? "");
        $cod_postal = mysqli_real_escape_string($conn, $checkout["cod_postal"] ?? "");
        $delivery_method = mysqli_real_escape_string($conn, $metoda_livrare);
        $metoda_plata_sql = mysqli_real_escape_string($conn, $metoda_plata);

        mysqli_query($conn,
            "INSERT INTO orders
            (user_id, total, transport, status, prenume, nume, email, telefon, adresa, oras, judet, cod_postal, payment_intent_id, delivery_method, metoda_plata)
            VALUES
            ($user_id, $total, $transport_valoare, 'comanda noua', '$prenume', '$nume', '$email', '$telefon', '$adresa', '$oras', '$judet', '$cod_postal', '$pi_escaped', '$delivery_method', '$metoda_plata_sql')"
        );

        $order_id = mysqli_insert_id($conn);
        $is_new_order = true;

        foreach ($cos as $item) {
            $name = mysqli_real_escape_string($conn, $item["name"]);
            $price = (float)$item["price"];
            $quantity = (int)$item["quantity"];

            if (isset($item["type"]) && $item["type"] == "box" && isset($item["items"])) {

                mysqli_query($conn,
                    "INSERT INTO order_items
                    (order_id, product_name, price, quantity, cutie_id, e_cutie)
                    VALUES
                    ($order_id, '$name', $price, $quantity, NULL, 1)"
                );

                $id_cutie_parinte = mysqli_insert_id($conn);

                foreach ($item["items"] as $box_item) {
                    $copil_nume = mysqli_real_escape_string($conn, $box_item["name"]);
                    $copil_pret = (float)$box_item["price"];
                    $copil_cantitate = (int)$box_item["quantity"];

                    mysqli_query($conn,
                        "INSERT INTO order_items
                        (order_id, product_name, price, quantity, cutie_id, e_cutie)
                        VALUES
                        ($order_id, '$copil_nume', $copil_pret, $copil_cantitate, $id_cutie_parinte, 0)"
                    );

                    $box_product_id = (int)$box_item["id"];
                    $box_quantity = $copil_cantitate * $quantity;

                    mysqli_query($conn, "UPDATE products SET stock = stock - $box_quantity WHERE id = $box_product_id AND stock >= $box_quantity");
                }

            } else {

                mysqli_query($conn,
                    "INSERT INTO order_items
                    (order_id, product_name, price, quantity, cutie_id, e_cutie)
                    VALUES
                    ($order_id, '$name', $price, $quantity, NULL, 0)"
                );

                if (isset($item["type"]) && $item["type"] == "simple") {
                    $product_id = (int)$item["id"];
                    mysqli_query($conn, "UPDATE products SET stock = stock - $quantity WHERE id = $product_id AND stock >= $quantity");
                }
            }
        }

        $_SESSION["cos"] = [];
        $_SESSION["checkout_data"] = [];

        if (isset($_SESSION["user_id"])) {
            $id_utilizator = (int)$_SESSION["user_id"];
            mysqli_query($conn, "DELETE FROM cos_persistent WHERE user_id = $id_utilizator");
        }

        if ($is_cash_order) {
            $new_order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id"));
            $new_items_res = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = $order_id");
            $new_items = [];
            while ($r = mysqli_fetch_assoc($new_items_res)) { $new_items[] = $r; }
            try { sendOrderConfirmationEmail($new_order, $new_items); } catch (Exception $e) {}

            header("Location: payment-success.php?order_id=" . $order_id);
            exit();
        }
    }
}

$order_id = (int)$order_id;
$order_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id"));

if (!$order_data) {
    header("Location: index.php");
    exit();
}

$items_result = mysqli_query($conn, "
    SELECT oi.*, p.image AS product_image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_name = p.name
    WHERE oi.order_id = $order_id
");
$items_array = [];
while ($r = mysqli_fetch_assoc($items_result)) { $items_array[] = $r; }

if ($is_new_order) {
    try {
        sendOrderConfirmationEmail($order_data, $items_array);
    } catch (Exception $e) {

    }
}

$payment_label = "Card online";

if (($order_data["metoda_plata"] ?? "card") == "cash") {
    $payment_label = "Plată la livrare / ridicare";
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comandă confirmată - Sweet</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main id="main-content" class="success-page">
    <section class="success-card">

        <div class="success-icon">✓</div>

        <h1>Comandă confirmată!</h1>

        <p class="success-msg">
            Îți mulțumim pentru comandă. Vei primi un email de confirmare cu detaliile comenzii.
        </p>

        <p class="success-note">
            Poți urmări detaliile comenzii în profilul tău de client.
        </p>

        <div class="success-grid">

            <div class="success-info-box">
                <span>Număr comandă</span>
                <strong>#<?php echo $order_data["id"]; ?></strong>
            </div>

            <div class="success-info-box">
                <span>Status</span>
                <strong><?php echo htmlspecialchars(statusLabel($order_data["status"])); ?></strong>
            </div>

            <div class="success-info-box">
                <span>Metodă plată</span>
                <strong><?php echo htmlspecialchars($payment_label); ?></strong>
            </div>

        </div>

        <div class="success-details">

            <div class="success-section">
                <h2>Date client</h2>
                <p><strong><?php echo htmlspecialchars($order_data["prenume"] . " " . $order_data["nume"]); ?></strong></p>
                <p><?php echo htmlspecialchars($order_data["email"]); ?></p>
                <p><?php echo htmlspecialchars($order_data["telefon"]); ?></p>
            </div>

            <div class="success-section">
                <h2>Livrare</h2>

                <?php if (($order_data["delivery_method"] ?? "domiciliu") == "ridicare"): ?>
                    <p><strong>Ridicare din magazin</strong></p>
                    <p>Str. Soarelui nr. 500, Cluj-Napoca</p>
                    <p>Gata în aproximativ 2 ore</p>
                <?php else: ?>
                    <p><strong>Livrare la domiciliu</strong></p>
                    <p>
                        <?php echo htmlspecialchars($order_data["adresa"]); ?>,
                        <?php echo htmlspecialchars($order_data["oras"]); ?>,
                        <?php echo htmlspecialchars($order_data["judet"]); ?>
                    </p>
                <?php endif; ?>
            </div>

        </div>

        <div class="success-products">
            <h2>Produse comandate</h2>

            <?php
            $copii_pe_cutie = array();
            foreach ($items_array as $r) {
                $cid = $r["cutie_id"] ?? null;
                if ($cid !== null && $cid !== "") {
                    if (!isset($copii_pe_cutie[$cid])) {
                        $copii_pe_cutie[$cid] = array();
                    }
                    $copii_pe_cutie[$cid][] = $r;
                }
            }
            ?>

            <?php foreach ($items_array as $item): ?>
                <?php
                $cid_item = $item["cutie_id"] ?? null;
                if ($cid_item !== null && $cid_item !== "") continue;

                $este_cutie = (int)($item["e_cutie"] ?? 0) == 1;
                ?>
                <div class="success-product-row">
                    <div class="cart-image-box">
                        <?php
                        $img = $este_cutie
                            ? (strpos($item["product_name"], '12') !== false ? 'imagini/cutie-12.png' : (strpos($item["product_name"], '6') !== false ? 'imagini/cutiei_6.png' : 'imagini/cutiei-4.png'))
                            : (!empty($item["product_image"]) ? 'imagini/' . $item["product_image"] : '');
                        ?>
                        <?php if (!empty($img)): ?><img src="<?php echo htmlspecialchars($img); ?>" alt=""><?php endif; ?>
                    </div>
                    <span>
                        <?php echo htmlspecialchars($item["product_name"]); ?>
                        <em>x<?php echo (int)$item["quantity"]; ?></em>

                        <?php if ($este_cutie && isset($copii_pe_cutie[$item["id"]])): ?>
                            <ul class="success-cutie-continut">
                                <?php foreach ($copii_pe_cutie[$item["id"]] as $produs_cutie): ?>
                                    <?php $subtotal_cutie = $produs_cutie["price"] * $produs_cutie["quantity"]; ?>
                                    <li>
                                        <span>
                                            <?php echo htmlspecialchars($produs_cutie["product_name"]); ?>
                                            x <?php echo (int)$produs_cutie["quantity"]; ?>
                                        </span>
                                        <span><?php echo number_format($subtotal_cutie, 2); ?> lei</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </span>
                    <strong>
                        <?php echo number_format($item["price"] * $item["quantity"], 2); ?> lei
                    </strong>
                </div>
            <?php endforeach; ?>

            <div class="success-product-row">
                <span>Transport</span>
                <strong>
                    <?php if ((float)$order_data["transport"] == 0): ?>
                        <span>GRATUIT</span>
                    <?php else: ?>
                        <?php echo number_format($order_data["transport"], 2); ?> lei
                    <?php endif; ?>
                </strong>
            </div>

            <div class="success-total">
                <span>Total</span>
                <strong><?php echo number_format($order_data["total"], 2); ?> lei</strong>
            </div>
        </div>

        <div class="success-actions">
            <a href="profile.php" class="btn btn-dark">Vezi comenzile mele</a>
            <a href="index.php" class="btn btn-dark">Înapoi acasă</a>
        </div>

    </section>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
