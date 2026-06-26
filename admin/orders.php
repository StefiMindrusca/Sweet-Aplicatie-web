<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

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

$search        = trim($_GET["search"] ?? "");
$status_filter = $_GET["status"] ?? "";

$where_parts = [];

if ($search !== "") {
    $safe = mysqli_real_escape_string($conn, $search);
    $where_parts[] = "(id LIKE '%$safe%' OR prenume LIKE '%$safe%' OR nume LIKE '%$safe%' OR email LIKE '%$safe%' OR telefon LIKE '%$safe%')";
}

if ($status_filter !== "") {
    $safe_status   = mysqli_real_escape_string($conn, $status_filter);
    $where_parts[] = "status = '$safe_status'";
}

$where = $where_parts ? "WHERE " . implode(" AND ", $where_parts) : "";

$page         = max(1, (int)($_GET["page"] ?? 1));
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM orders $where"))["n"];
$total_pages  = max(1, (int)ceil($total_orders / 10));
$page         = min($page, $total_pages);
$orders_result = mysqli_query($conn, "SELECT * FROM orders $where ORDER BY created_at DESC LIMIT 10 OFFSET " . ($page - 1) * 10);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Comenzi - Sweet Admin</title>
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

        <div class="admin-top-box">
            <h1>Comenzi</h1>
        </div>

        <section class="orders-page-list">

            <form method="get" class="orders-search-form">
                <div class="products-search-bar">
                    <input type="text" name="search" placeholder="Caută comandă..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">
                        <img src="../imagini/loop.png" alt="Caută">
                    </button>
                </div>
                <select name="status" onchange="this.form.submit()">
                    <option value="">Toate statusurile</option>
                    <option value="comanda noua"    <?php echo $status_filter === "comanda noua"    ? "selected" : ""; ?>>Comandă nouă</option>
                    <option value="confirmata"      <?php echo $status_filter === "confirmata"      ? "selected" : ""; ?>>Confirmată</option>
                    <option value="in pregatire"    <?php echo $status_filter === "in pregatire"    ? "selected" : ""; ?>>În pregătire</option>
                    <option value="in livrare"      <?php echo $status_filter === "in livrare"      ? "selected" : ""; ?>>În livrare</option>
                    <option value="livrata"         <?php echo $status_filter === "livrata"         ? "selected" : ""; ?>>Livrată</option>
                    <option value="gata de ridicat" <?php echo $status_filter === "gata de ridicat" ? "selected" : ""; ?>>Gata de ridicat</option>
                    <option value="ridicata"        <?php echo $status_filter === "ridicata"        ? "selected" : ""; ?>>Ridicată</option>
                    <option value="anulata"         <?php echo $status_filter === "anulata"         ? "selected" : ""; ?>>Anulată</option>
                </select>
            </form>

            <div class="orders-cards-list">
                <?php if ($orders_result && mysqli_num_rows($orders_result) > 0): ?>
                    <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                        <a href="order-details.php?id=<?php echo $order["id"]; ?>" class="order-list-card">
                            <div>
                                <strong>#<?php echo $order["id"]; ?></strong>
                                <p><?php echo htmlspecialchars($order["prenume"] . " " . $order["nume"]); ?></p>
                                <small><?php echo htmlspecialchars($order["email"]); ?></small>
                            </div>

                            <div class="order-list-right">
                                <span><?php echo number_format($order["total"], 2); ?> lei</span>
                                <em class="profile-order-status status-<?php echo statusClass($order["status"]); ?>">
                                    <?php echo htmlspecialchars(statusLabel($order["status"])); ?>
                                </em>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Nu există comenzi.</p>
                <?php endif; ?>
            </div>

            <?php $q = http_build_query(["search" => $search, "status" => $status_filter]); $q = $q ? $q . "&" : ""; ?>
            <div class="admin-pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo $q; ?>page=<?php echo $page - 1; ?>" class="page-arrow">&lt;</a>
                <?php else: ?>
                    <span class="page-arrow disabled">&lt;</span>
                <?php endif; ?>
                <span class="page-num"><?php echo $page; ?>/<?php echo $total_pages; ?></span>
                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo $q; ?>page=<?php echo $page + 1; ?>" class="page-arrow">&gt;</a>
                <?php else: ?>
                    <span class="page-arrow disabled">&gt;</span>
                <?php endif; ?>
            </div>

        </section>

    </main>

</div>

</body>
</html>
