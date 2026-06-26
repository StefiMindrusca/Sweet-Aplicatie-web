<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

$search = trim($_GET["search"] ?? "");
$category_filter = $_GET["category"] ?? "";

$where = [];
if ($search !== "") {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(name LIKE '%$s%' OR description LIKE '%$s%')";
}
if ($category_filter !== "") {
    $c = mysqli_real_escape_string($conn, $category_filter);
    $where[] = "category = '$c'";
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$page = max(1, (int)($_GET["page"] ?? 1));
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM products $where_sql"))["n"];
$total_pages = max(1, (int)ceil($total_products / 10));
$page = min($page, $total_pages);
$result = mysqli_query($conn, "SELECT * FROM products $where_sql ORDER BY id DESC LIMIT 10 OFFSET " . ($page - 1) * 10);

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produse Admin - Sweet</title>
    <link rel="stylesheet" href="admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">

    <aside class="admin-sidebar">
        <h2>Sweet Admin</h2>

        <a href="dashboard.php">Dashboard</a>
        <a href="products.php" class="active-admin-link">Produse</a>
        <a href="categories.php">Categorii</a>
        <a href="orders.php">Comenzi</a>
        <a href="users.php">Utilizatori</a>
        <a href="recenzii.php">Recenzii</a>
        <a href="../logout.php"><img src="../imagini/exit.png" alt="" class="admin-logout-icon"> Logout</a>
    </aside>

    <main class="admin-main">
        <div class="admin-top-box admin-top-flex">
            <div>
                <h1>Produse</h1>
            </div>

            <div class="admin-actions-buttons">
                <a href="add-product.php" class="admin-btn">+ Adaugă produs</a>
                <a href="add-category.php" class="admin-btn admin-btn-secondary">+ Adaugă categorie</a>
            </div>
        </div>
        <form method="get" class="products-filter-form">
            <div class="products-search-bar">
                <input type="text" name="search" placeholder="Caută produs..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">
                    <img src="../imagini/loop.png" alt="Caută">
                </button>
            </div>
            <select name="category" onchange="this.form.submit()">
                <option value="">Toate categoriile</option>
                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                    <option value="<?php echo htmlspecialchars($cat["name"]); ?>" <?php echo $category_filter === $cat["name"] ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($cat["name"]); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>

        <div class="admin-table-box">
            <table class="admin-table">
                <tr>
                    <th>ID</th>
                    <th>Nume</th>
                    <th>Categorie</th>
                    <th>Descriere</th>
                    <th>Ingrediente</th>
                    <th>Preț</th>
                    <th>Stoc</th>
                    <th>Imagine</th>
                    <th>Acțiuni</th>
                </tr>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row["id"]; ?></td>
                            <td><?php echo htmlspecialchars($row["name"]); ?></td>
                            <td><?php echo htmlspecialchars($row["category"]); ?></td>
                            <td><?php echo htmlspecialchars($row["description"]); ?></td>
                            <td><?php echo htmlspecialchars($row["ingredients"]); ?></td>
                            <td><?php echo number_format($row["price"], 2); ?> lei</td>
                            <td><?php echo (int)$row["stock"]; ?></td>
                            <td>
                                <?php if (!empty($row["image"])): ?>
                                    <img src="../imagini/<?php echo htmlspecialchars($row["image"]); ?>" class="admin-img">
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_product.php?id=<?php echo $row["id"]; ?>" class="table-btn edit-btn">Editează</a>
                                <a href="delete-product.php?id=<?php echo $row["id"]; ?>" class="table-btn delete-btn" onclick="return confirm('Sigur vrei să ștergi acest produs?')">Șterge</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">Nu există produse în baza de date.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

        <?php
        $pagination_params = http_build_query(["search" => $search, "category" => $category_filter]);
        ?>
        <div class="admin-pagination">
            <?php if ($page > 1): ?>
                <a href="?<?php echo $pagination_params; ?>&page=<?php echo $page - 1; ?>" class="page-arrow">&lt;</a>
            <?php else: ?>
                <span class="page-arrow disabled">&lt;</span>
            <?php endif; ?>
            <span class="page-num"><?php echo $page; ?>/<?php echo $total_pages; ?></span>
            <?php if ($page < $total_pages): ?>
                <a href="?<?php echo $pagination_params; ?>&page=<?php echo $page + 1; ?>" class="page-arrow">&gt;</a>
            <?php else: ?>
                <span class="page-arrow disabled">&gt;</span>
            <?php endif; ?>
        </div>

    </main>

</div>

</body>
</html>
