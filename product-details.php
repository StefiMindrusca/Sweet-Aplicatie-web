<?php
session_start();
include 'config/db.php';
/** @var mysqli $conn */

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET["id"];

$sql = "SELECT * FROM products WHERE id = $id LIMIT 1";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit();
}

$product = mysqli_fetch_assoc($result);

$rating_result = mysqli_query($conn, "SELECT ROUND(AVG(rating)) AS medie, COUNT(*) AS total FROM reviews WHERE product_id = $id");
$rating_data = mysqli_fetch_assoc($rating_result);
$medie_rating = (int)($rating_data["medie"] ?? 0);
$total_recenzii = (int)($rating_data["total"] ?? 0);

$in_cos = isset($_SESSION["cos"][$id]["quantity"]) ? (int)$_SESSION["cos"][$id]["quantity"] : 0;
$stoc_disponibil = max(0, (int)$product["stock"] - $in_cos);

$category_name = mysqli_real_escape_string($conn, $product["category"]);
$category_sql = "SELECT * FROM categories WHERE name = '$category_name' LIMIT 1";
$category_result = mysqli_query($conn, $category_sql);

$category_type = "simple";

if ($category_result && mysqli_num_rows($category_result) > 0) {
    $category = mysqli_fetch_assoc($category_result);
    $category_type = $category["type"];
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product["name"]); ?> - Sweet</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'includes/header.php'; ?>
<?php if (isset($_GET["added"])): ?>
    <div class="box-confirmation-bg" id="boxConfirmationBg">
        <div class="box-confirmation" role="dialog" aria-modal="true" aria-labelledby="boxConfirmationTitle">

            <button type="button" class="box-confirmation-close" onclick="closeBoxConfirmation()" aria-label="Închide mesajul">
                x
            </button>

            <h2 id="boxConfirmationTitle">
                Produs adaugat în coș
            </h2>

            <div class="box-confirmation-actions">
                <a href="cos.php" class="btn btn-dark">Vezi coșul</a>

            </div>

        </div>
    </div>
<?php endif; ?>

<main id="main-content" class="product-page">
    <section class="product-details-box">

        <div class="product-image-area">
            <?php if (!empty($product["image"])): ?>
                <img
                        src="imagini/<?php echo htmlspecialchars($product["image"]); ?>"
                        alt="<?php echo htmlspecialchars($product["name"]); ?>"
                        class="product-image-large"
                >
            <?php else: ?>
                <div class="product-placeholder">Imagine produs</div>
            <?php endif; ?>
        </div>

        <div class="product-info-area">
            <h1><?php echo htmlspecialchars($product["name"]); ?></h1>

            <?php if ($total_recenzii > 0): ?>
                <div class="produs-stele">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="<?php echo $i <= $medie_rating ? 'stea-plina' : 'stea-goala'; ?>">★</span>
                    <?php endfor; ?>
                    <span class="stele-total">(<?php echo $total_recenzii; ?>)</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($product["description"])): ?>
                <p class="product-description">
                    <?php echo htmlspecialchars($product["description"]); ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($product["ingredients"])): ?>
                <div class="ingredients-box">
                    <h3>Ingrediente</h3>
                    <p><?php echo htmlspecialchars($product["ingredients"]); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($category_type == "simple"): ?>

                <p class="product-price"><?php echo number_format($product["price"], 2); ?> lei</p>

                <?php if (isset($_GET["error"]) && $_GET["error"] == "stock"): ?>
                    <p class="cart-error" role="alert">Nu mai există stoc suficient pentru acest produs.</p>
                <?php endif; ?>

                <?php if ($stoc_disponibil <= 0): ?>

                    <p class="stock-badge unavailable">Indisponibil</p>

                    <button type="button" class="btn btn-dark add-cart-btn" disabled>
                        Adaugă în coș
                    </button>

                <?php else: ?>

                    <form action="add-to-cart.php" method="get" class="product-cart-form">
                        <input type="hidden" name="id" value="<?php echo $product["id"]; ?>">

                        <div class="product-buy-row">
                            <div class="quantity-controls-simple">
                                <button type="button" id="btn-decrease" onclick="changeQty(-1)" aria-label="Scade cantitatea">-</button>

                                <input type="number"
                                       name="quantity"
                                       id="quantity"
                                       value="1"
                                       min="1"
                                       max="<?php echo $stoc_disponibil; ?>"
                                       data-stock="<?php echo $stoc_disponibil; ?>"
                                       aria-label="Cantitate produs">

                                <button type="button" id="btn-increase" onclick="changeQty(1)" aria-label="Crește cantitatea">+</button>
                            </div>

                            <button type="submit" class="btn btn-dark add-cart-btn">
                                Adaugă în coș
                            </button>
                        </div>

                    </form>

                <?php endif; ?>

            <?php else: ?>

                <div class="box-product-actions">
                    <p class="product-price box-detail-price">
                        <?php echo number_format($product["price"], 2); ?> lei
                    </p>

                    <a href="boxbuild.php?name=<?php echo urlencode($product["category"]); ?>" class="box-detail-btn">
                        Personalizează-ți cutia
                    </a>
                </div>

            <?php endif; ?>
        </div>

    </section>
</main>

<?php include 'includes/footer.php'; ?>

<script>
    function changeQty(value) {
        const qtyInput = document.getElementById("quantity");

        if (!qtyInput) {
            return;
        }

        const stock = parseInt(qtyInput.dataset.stock) || 0;

        let current = parseInt(qtyInput.value) || 1;
        current += value;

        if (current < 1) {
            current = 1;
        }

        if (current > stock) {
            current = stock;
        }

        qtyInput.value = current;
        updateQtyButtons(current, stock);
    }

    function updateQtyButtons(current, stock) {
        const btnDecrease = document.getElementById("btn-decrease");
        const btnIncrease = document.getElementById("btn-increase");

        if (btnDecrease) btnDecrease.disabled = current <= 1;
        if (btnIncrease) btnIncrease.disabled = current >= stock;
    }

    document.addEventListener("DOMContentLoaded", function () {
        const qtyInput = document.getElementById("quantity");
        if (qtyInput) {
            const stock = parseInt(qtyInput.dataset.stock) || 0;
            updateQtyButtons(parseInt(qtyInput.value) || 1, stock);
        }
    });
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
