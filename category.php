<?php
include 'config/db.php';
/** @var mysqli $conn */

if (!isset($_GET["name"]) || empty(trim($_GET["name"]))) {
    header("Location: index.php");
    exit();
}

$category_name = trim($_GET["name"]);
$category_safe = mysqli_real_escape_string($conn, $category_name);

$cat_sql = "SELECT * FROM categories WHERE name = '$category_safe' LIMIT 1";
$cat_result = mysqli_query($conn, $cat_sql);

if (!$cat_result || mysqli_num_rows($cat_result) == 0) {
    header("Location: index.php");
    exit();
}

$category = mysqli_fetch_assoc($cat_result);

$sort = isset($_GET["sort"]) ? $_GET["sort"] : "";

$order_by = "id DESC";

if ($sort == "price_asc") {
    $order_by = "price ASC";
}

if ($sort == "price_desc") {
    $order_by = "price DESC";
}

$sql = "SELECT * FROM products WHERE category = '$category_safe' ORDER BY $order_by";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($category["name"]); ?> - Sweet</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main id="main-content" class="category-page">

    <section class="category-topbar">

        <h1 class="category-page-title">
            <?php echo htmlspecialchars($category["name"]); ?>
        </h1>

        <form method="GET" class="category-sort">
            <input type="hidden" name="name" value="<?php echo htmlspecialchars($category["name"]); ?>">

            <label for="sort">Sortează</label>

            <select name="sort" id="sort" onchange="this.form.submit()">
                <option value="" <?php if ($sort == "") echo "selected"; ?>>
                    Implicit
                </option>

                <option value="price_asc" <?php if ($sort == "price_asc") echo "selected"; ?>>
                    Preț crescător
                </option>

                <option value="price_desc" <?php if ($sort == "price_desc") echo "selected"; ?>>
                    Preț descrescător
                </option>
            </select>
        </form>

    </section>

    <?php if ($category["type"] == "box"): ?>

    <?php endif; ?>

    <section class="products-grid">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>

                <?php $fara_stoc = (int)$row["stock"] <= 0; ?>

                <a href="product-details.php?id=<?php echo $row["id"]; ?>" class="product-card <?php echo $fara_stoc ? 'out-of-stock' : ''; ?>">

                    <?php if (!empty($row["image"])): ?>
                        <img
                                src="imagini/<?php echo htmlspecialchars($row["image"]); ?>"
                                alt="<?php echo htmlspecialchars($row["name"]); ?>"
                                class="product-image"
                        >
                    <?php endif; ?>

                    <div class="product-card-bottom">
                        <h2 class="product-name">
                            <?php echo htmlspecialchars($row["name"]); ?>
                        </h2>

                        <div class="product-price-row">
                            <p class="product-price">
                                <?php echo number_format($row["price"], 2); ?> lei
                            </p>
                            <img src="imagini/basket.png" alt="" class="card-basket-icon">
                        </div>

                        <?php if ($fara_stoc): ?>
                            <span class="stock-badge unavailable">Indisponibil</span>
                        <?php endif; ?>
                    </div>

                </a>

            <?php endwhile; ?>
        <?php else: ?>
            <p>Nu există produse în această categorie.</p>
        <?php endif; ?>
    </section>

</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
