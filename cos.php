<?php
session_start();
include 'config/db.php';
/** @var mysqli $conn */

function poateCresteCantitatea($cos, $id, $conn) {
    if (!isset($cos[$id])) {
        return false;
    }

    $item = $cos[$id];

    if ($item["type"] == "simple") {
        $pid = (int)$item["id"];
        $necesar = $item["quantity"] + 1;

        $res = mysqli_query($conn, "SELECT stock FROM products WHERE id = $pid LIMIT 1");
        if (!$res || mysqli_num_rows($res) == 0) {
            return false;
        }
        $row = mysqli_fetch_assoc($res);

        if ((int)$row["stock"] < $necesar) {
            return false;
        }
        return true;
    }

    if ($item["type"] == "box") {
        $box_qty_nou = $item["quantity"] + 1;

        $in_alte_cutii = array();
        foreach ($cos as $alt_key => $alt_item) {
            if ($alt_key == $id) continue;
            if ($alt_item["type"] != "box") continue;
            if (!isset($alt_item["items"])) continue;

            foreach ($alt_item["items"] as $alt_produs) {
                $alt_pid = (int)$alt_produs["id"];
                $alt_qty = (int)$alt_produs["quantity"] * (int)$alt_item["quantity"];
                if (!isset($in_alte_cutii[$alt_pid])) {
                    $in_alte_cutii[$alt_pid] = 0;
                }
                $in_alte_cutii[$alt_pid] += $alt_qty;
            }
        }

        foreach ($item["items"] as $produs) {
            $pid = (int)$produs["id"];
            $necesar = $box_qty_nou * (int)$produs["quantity"];
            $in_alte = isset($in_alte_cutii[$pid]) ? $in_alte_cutii[$pid] : 0;
            $total_necesar = $necesar + $in_alte;

            $res = mysqli_query($conn, "SELECT stock FROM products WHERE id = $pid LIMIT 1");
            if (!$res || mysqli_num_rows($res) == 0) {
                return false;
            }
            $row = mysqli_fetch_assoc($res);

            if ((int)$row["stock"] < $total_necesar) {
                return false;
            }
        }
        return true;
    }

    return false;
}

if (isset($_GET["id"]) && isset($_GET["action"]) && isset($_SESSION["cos"][$_GET["id"]])) {
    $id = $_GET["id"];
    $action = $_GET["action"];

    if ($action == "plus") {
        if (poateCresteCantitatea($_SESSION["cos"], $id, $conn)) {
            $_SESSION["cos"][$id]["quantity"]++;
        } else {
            header("Location: cos.php?error=stock");
            exit();
        }
    }

    if ($action == "minus") {
        $_SESSION["cos"][$id]["quantity"]--;

        if ($_SESSION["cos"][$id]["quantity"] <= 0) {
            unset($_SESSION["cos"][$id]);
        }
    }

    if (isset($_SESSION["user_id"])) {
        $id_utilizator = (int)$_SESSION["user_id"];
        $cos_json = mysqli_real_escape_string($conn, json_encode($_SESSION["cos"]));

        mysqli_query($conn, "INSERT INTO cos_persistent (user_id, cos_client)
                             VALUES ($id_utilizator, '$cos_json')
                             ON DUPLICATE KEY UPDATE cos_client = '$cos_json'");
    }

    header("Location: cos.php");
    exit();
}

$cos = isset($_SESSION["cos"]) ? $_SESSION["cos"] : [];
$total_general = 0;

$produse_fara_stoc = [];
foreach ($cos as $item) {
    if ($item["type"] == "simple") {
        $pid = (int)$item["id"];
        $res = mysqli_query($conn, "SELECT stock FROM products WHERE id = $pid LIMIT 1");
        $row = mysqli_fetch_assoc($res);
        if ((int)$row["stock"] < (int)$item["quantity"]) {
            $produse_fara_stoc[] = $item["name"];
        }
    }
    if ($item["type"] == "box" && isset($item["items"])) {
        $cutie_indisponibila = false;
        foreach ($item["items"] as $produs) {
            $pid = (int)$produs["id"];
            $necesar = (int)$produs["quantity"] * (int)$item["quantity"];
            $res = mysqli_query($conn, "SELECT stock FROM products WHERE id = $pid LIMIT 1");
            $row = mysqli_fetch_assoc($res);
            if ((int)$row["stock"] < $necesar) {
                $cutie_indisponibila = true;
            }
        }
        if ($cutie_indisponibila) {
            $produse_fara_stoc[] = $item["name"] . " — unul sau mai multe produse nu mai sunt disponibile";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coș - Sweet</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main id="main-content" class="cart-page">
    <div class="cart-container">
        <h1 class="cart-title">Coșul meu</h1>

        <?php if (isset($_GET["error"]) && $_GET["error"] == "stock"): ?>
            <p class="cart-error">Nu mai există stoc suficient pentru a crește cantitatea.</p>
        <?php endif; ?>

        <?php if (isset($_GET["error"]) && $_GET["error"] == "stoc_insuficient"): ?>
            <p class="cart-error">Unul sau mai multe produse nu mai sunt disponibile în cantitatea dorită. Te rugăm să actualizezi coșul.</p>
        <?php endif; ?>

        <?php foreach ($produse_fara_stoc as $nume): ?>
            <p class="cart-error">"<?php echo htmlspecialchars($nume); ?>" nu mai este disponibil în cantitatea dorită.</p>
        <?php endforeach; ?>

        <?php if (empty($cos)): ?>

            <p class="cart-empty">Coșul este gol.</p>

        <?php else: ?>

            <?php
            foreach ($cos as $item) {
                $total_general += $item["price"] * $item["quantity"];
            }

            if ($total_general >= 50) {
                $transport = 0;
            } else {
                $transport = 15;
            }

            $total_final = $total_general + $transport;
            ?>

            <div class="cart-layout">

                <section class="cart-products">
                    <h2 class="cart-subtitle">Produse adăugate</h2>

                    <?php foreach ($cos as $key => $item): ?>
                        <?php
                        $subtotal = $item["price"] * $item["quantity"];
                        $poate_creste = poateCresteCantitatea($cos, $key, $conn);
                        ?>

                        <article class="cart-card">

                            <div class="cart-image-box">
                                <?php if (!empty($item["image"])): ?>
                                    <img
                                            src="<?php echo htmlspecialchars($item["image"]); ?>"
                                            alt="<?php echo htmlspecialchars($item["name"]); ?>"
                                    >
                                <?php else: ?>
                                    <div class="cart-image-empty"></div>
                                <?php endif; ?>
                            </div>

                            <div class="cart-info">
                                <h3><?php echo htmlspecialchars($item["name"]); ?></h3>

                                <?php if (isset($item["type"]) && $item["type"] === "box" && !empty($item["items"])): ?>
                                    <p class="cart-muted">
                                        Cutie cu <?php echo (int)$item["box_size"]; ?> produse
                                    </p>

                                    <ul class="cart-box-items">
                                        <?php foreach ($item["items"] as $produs): ?>
                                            <li>
                                                <?php echo htmlspecialchars($produs["name"]); ?>
                                                x <?php echo (int)$produs["quantity"]; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>

                            <div class="cart-price-area">
                                <p class="cart-price">
                                    <?php echo number_format($subtotal, 2); ?> lei
                                </p>

                                <div class="cart-quantity-controls">
                                    <a
                                            href="cos.php?id=<?php echo urlencode($key); ?>&action=minus"
                                            class="cart-qty-btn"
                                            aria-label="Scade cantitatea"
                                    >
                                        −
                                    </a>

                                    <span class="cart-qty-number">
                                        <?php echo (int)$item["quantity"]; ?>
                                    </span>

                                    <?php if ($poate_creste): ?>
                                        <a
                                                href="cos.php?id=<?php echo urlencode($key); ?>&action=plus"
                                                class="cart-qty-btn"
                                                aria-label="Crește cantitatea"
                                        >
                                            +
                                        </a>
                                    <?php else: ?>
                                        <span
                                                class="cart-qty-btn disabled"
                                                aria-label="Stoc insuficient"
                                                title="Stoc insuficient"
                                        >
                                            +
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <a href="sterge-cos.php?id=<?php echo urlencode($key); ?>" class="cart-remove-btn">
                                    Șterge
                                </a>
                            </div>

                        </article>
                    <?php endforeach; ?>
                </section>

                <aside class="cart-summary">
                    <h2>Sumar comandă</h2>

                    <div class="cart-summary-row">
                        <span>Cost produse:</span>
                        <strong><?php echo number_format($total_general, 2); ?> lei</strong>
                    </div>

                    <div class="cart-summary-row">
                        <span>Cost livrare:</span>

                        <?php if ($transport == 0): ?>
                            <strong class="cart-free">GRATUIT</strong>
                        <?php else: ?>
                            <strong><?php echo number_format($transport, 2); ?> lei</strong>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <div class="cart-summary-total">
                        <span>Total:</span>
                        <strong><?php echo number_format($total_final, 2); ?> lei</strong>
                    </div>

                    <a href="checkout.php" class="cart-checkout-btn">Continuă</a>
                </aside>

            </div>

        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
