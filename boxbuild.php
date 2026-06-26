<?php
session_start();
include 'config/db.php';
/** @var mysqli $conn */

if (!isset($_GET["name"]) || empty(trim($_GET["name"]))) {
    header("Location: index.php");
    exit();
}

$category_name = trim($_GET["name"]);
$category_safe = mysqli_real_escape_string($conn, $category_name);

$cat_sql = "SELECT * FROM categories WHERE name = '$category_safe' AND type = 'box' LIMIT 1";
$cat_result = mysqli_query($conn, $cat_sql);

if (!$cat_result || mysqli_num_rows($cat_result) == 0) {
    header("Location: index.php");
    exit();
}

$category = mysqli_fetch_assoc($cat_result);

$sql = "SELECT * FROM products WHERE category = '$category_safe' ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

$products = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}

$ratings = [];
if (!empty($products)) {
    $ids = implode(',', array_column($products, 'id'));
    $ratings_result = mysqli_query($conn, "SELECT product_id, ROUND(AVG(rating)) AS medie, COUNT(*) AS total FROM reviews WHERE product_id IN ($ids) GROUP BY product_id");
    if ($ratings_result) {
        while ($r = mysqli_fetch_assoc($ratings_result)) {
            $ratings[(int)$r['product_id']] = $r;
        }
    }
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

$box_sizes = [
    [
        "size" => 4,
        "image" => "imagini/cutie-4.png"
    ],
    [
        "size" => 6,
        "image" => "imagini/cutie-6.png"
    ],
    [
        "size" => 12,
        "image" => "imagini/x.png"
    ]
];
?>

<!DOCTYPE html>

<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personalizează cutia - <?php echo htmlspecialchars($category["name"]); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'includes/header.php'; ?>

<?php if (isset($_GET["error"]) && $_GET["error"] == "stock"): ?>
    <p class="cart-error">Nu mai există stoc suficient pentru produsele alese.</p>
<?php endif; ?>

<?php if (isset($_GET["added"])): ?>
    <div class="box-confirmation-bg" id="boxConfirmationBg">
        <div class="box-confirmation" role="dialog" aria-modal="true" aria-labelledby="boxConfirmationTitle">

            <button
                    type="button"
                    class="box-confirmation-close"
                    onclick="closeBoxConfirmation()"
                    aria-label="Închide mesajul"
            >
                x
            </button>

            <h2 id="boxConfirmationTitle">
                Cutia a fost adăugată în coș
            </h2>

            <div class="box-confirmation-actions">
                <a href="cos.php" class="btn btn-dark">Vezi coșul</a>
            </div>

        </div>
    </div>
<?php endif; ?>

<main id="main-content" class="box-builder-page">

    <div class="box-builder-layout">

        <section class="box-preview-section" aria-labelledby="preview-title">
            <h1 id="preview-title" class="sr-only">Previzualizare cutie personalizată</h1>

            <div class="box-preview-wrapper">
                <div class="box-preview-stage">
                    <img
                            src="imagini/cutie-4.png"
                            alt="Cutie goală pentru produse"
                            id="boxBaseImage"
                            class="box-base-image"
                    >

                    <div class="box-items-layer" id="boxItemsLayer" aria-live="polite"></div>

                    <p class="box-placeholder-text" id="boxPlaceholderText">
                        Alege 4 produse pentru a completa cutia.
                    </p>
                </div>
            </div>
        </section>

        <section class="box-controls-section" aria-labelledby="builder-title">
            <div class="box-controls-card">
                <h2 id="builder-title" class="box-builder-title">Alege produsele</h2>

                <div
                        class="box-size-selector"
                        role="radiogroup"
                        aria-label="Alege mărimea cutiei"
                        id="boxSizeSelector"
                >
                    <?php foreach ($box_sizes as $index => $box): ?>
                        <button
                                type="button"
                                class="box-size-btn <?php echo $index === 0 ? 'active' : ''; ?>"
                                data-size="<?php echo $box["size"]; ?>"
                                data-image="<?php echo htmlspecialchars($box["image"]); ?>"
                                aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                                aria-label="Cutie cu <?php echo $box["size"]; ?> produse"
                        >
                            <?php echo $box["size"]; ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="box-products-list" id="boxProductsList">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $in_cart_qty = isset($in_cos[$product["id"]]) ? $in_cos[$product["id"]] : 0;
                            $stoc_disponibil = (int)$product["stock"] - $in_cart_qty;
                            $fara_stoc = $stoc_disponibil <= 0;
                            ?>
                            <div
                                    class="box-product-card <?php echo $fara_stoc ? 'out-of-stock' : ''; ?>"
                                    data-id="<?php echo $product["id"]; ?>"
                                    data-name="<?php echo htmlspecialchars($product["name"]); ?>"
                                    data-price="<?php echo (float)$product["price"]; ?>"
                                    data-stock="<?php echo (int)$product["stock"]; ?>"
                                    data-in-cart="<?php echo $in_cart_qty; ?>"
                                    data-image="<?php echo !empty($product["image"]) ? 'imagini/' . htmlspecialchars($product["image"]) : ''; ?>"
                                    data-description="<?php echo htmlspecialchars($product["description"] ?? ""); ?>"
                                    data-ingredients="<?php echo htmlspecialchars($product["ingredients"] ?? ""); ?>"
                                    data-rating="<?php echo isset($ratings[$product["id"]]) ? (int)$ratings[$product["id"]]["medie"] : 0; ?>"
                                    data-rating-total="<?php echo isset($ratings[$product["id"]]) ? (int)$ratings[$product["id"]]["total"] : 0; ?>"
                            >
                                <div class="box-product-left">
                                    <?php if (!empty($product["image"])): ?>
                                        <img
                                                src="imagini/<?php echo htmlspecialchars($product["image"]); ?>"
                                                alt="<?php echo htmlspecialchars($product["name"]); ?>"
                                                class="box-product-image"
                                        >
                                    <?php else: ?>
                                        <div class="box-product-image box-product-image-empty" aria-hidden="true"></div>
                                    <?php endif; ?>

                                    <div class="box-product-info">
                                        <div class="box-product-title-row">
                                            <h3><?php echo htmlspecialchars($product["name"]); ?></h3>

                                            <button
                                                    type="button"
                                                    class="box-info-btn"
                                                    aria-label="Vezi detalii despre <?php echo htmlspecialchars($product["name"]); ?>"
                                            >
                                                i
                                            </button>
                                        </div>

                                        <p><?php echo htmlspecialchars($product["description"] ?? ""); ?></p>

                                        <strong><?php echo number_format($product["price"], 2); ?> lei</strong>

                                        <?php if ($fara_stoc): ?>
                                            <span class="stock-badge unavailable">Indisponibil</span>
                                        <?php endif; ?>
                                    </div>
                                </div>


                                <div class="box-qty-controls">
                                    <button
                                            type="button"
                                            class="qty-btn minus-btn"
                                            aria-label="Scade cantitatea pentru <?php echo htmlspecialchars($product["name"]); ?>"
                                            <?php echo $fara_stoc ? 'disabled' : ''; ?>
                                    >
                                        −
                                    </button>

                                    <span
                                            class="qty-value"
                                            aria-live="polite"
                                            aria-label="Cantitate selectată"
                                    >
                                        0
                                    </span>

                                    <button
                                            type="button"
                                            class="qty-btn plus-btn"
                                            aria-label="Crește cantitatea pentru <?php echo htmlspecialchars($product["name"]); ?>"
                                            <?php echo $fara_stoc ? 'disabled' : ''; ?>
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Nu există produse în această categorie.</p>
                    <?php endif; ?>
                </div>

                <div class="box-summary" aria-live="polite">
                    <p><strong>Selectate:</strong> <span id="selectedCount">0</span> / <span id="maxCount">4</span></p>
                    <p><strong>Total:</strong> <span id="totalPrice">0.00</span> lei</p>
                </div>

                <form action="add-box-to-cart.php" method="POST" id="boxForm">
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category["name"]); ?>">
                    <input type="hidden" name="box_size" id="boxSizeInput" value="4">
                    <input type="hidden" name="box_items" id="boxItemsInput" value="">

                    <button type="submit" class="box-submit-btn" id="boxSubmitBtn" disabled>
                        Adaugă în coș
                    </button>
                </form>

            </div>
        </section>

    </div>
</main>

<div class="box-info-bg" id="boxInfoBg" hidden>
    <div
            class="box-info-window"
            role="dialog"
            aria-modal="true"
            aria-labelledby="boxInfoTitle"
    >
        <button
                type="button"
                class="box-info-close"
                id="boxInfoClose"
                aria-label="Închide detaliile produsului"
        >
            x
        </button>

        <img src="" alt="" id="boxInfoImage" class="box-info-image">

        <div class="box-info-content">
            <h2 id="boxInfoTitle"></h2>

            <div id="boxInfoStele" class="produs-stele produs-stele-mici" style="display:none; margin-bottom:8px;"></div>

            <p id="boxInfoDescription" class="box-info-description"></p>

            <div class="box-info-ingredients">
                <h3>Ingrediente</h3>
                <p id="boxInfoIngredients"></p>
            </div>

            <p id="boxInfoPrice" class="box-info-price"></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="script.js?v=<?php echo time(); ?>"></script>

<script>
    function closeBoxConfirmation() {
        var box = document.getElementById("boxConfirmationBg");

        if (box) {
            box.style.display = "none";
        }

        window.history.replaceState(
            null,
            "",
            "boxbuild.php?name=<?php echo urlencode($category["name"]); ?>"
        );
    }
</script>

</body>
</html>
