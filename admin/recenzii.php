<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET["sterge"]) && (int)$_GET["sterge"] > 0) {
    $id_sterge = (int)$_GET["sterge"];
    mysqli_query($conn, "DELETE FROM reviews WHERE id = $id_sterge");
    header("Location: recenzii.php");
    exit();
}

$recenzii_sql = "
    SELECT r.id, r.rating, r.comentariu, r.created_at,
           u.name AS user_name, u.email AS user_email,
           p.name AS product_name
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN products p ON r.product_id = p.id
    ORDER BY r.created_at DESC
";

$recenzii_result = mysqli_query($conn, $recenzii_sql);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Recenzii - Sweet Admin</title>
    <link rel="stylesheet" href="admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">

    <aside class="admin-sidebar">
        <h2>Sweet Admin</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Produse</a>
        <a href="categories.php">Categorii</a>
        <a href="orders.php">Comenzi</a>
        <a href="users.php">Utilizatori</a>
        <a href="recenzii.php" class="active-admin-link">Recenzii</a>
        <a href="../logout.php"><img src="../imagini/exit.png" alt="" class="admin-logout-icon"> Logout</a>
    </aside>

    <main class="admin-main">

        <div class="admin-top-box">
            <h1>Recenzii</h1>
        </div>

        <?php if ($recenzii_result && mysqli_num_rows($recenzii_result) > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produs</th>
                        <th>Client</th>
                        <th>Rating</th>
                        <th>Comentariu</th>
                        <th>Data</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($recenzii_result)): ?>
                        <tr>
                            <td><?php echo $r["id"]; ?></td>
                            <td><?php echo htmlspecialchars($r["product_name"] ?? "-"); ?></td>
                            <td>
                                <?php echo htmlspecialchars($r["user_name"] ?? "-"); ?><br>
                                <small><?php echo htmlspecialchars($r["user_email"] ?? ""); ?></small>
                            </td>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span style="color: <?php echo $i <= $r['rating'] ? '#f0c040' : '#ddd'; ?>">★</span>
                                <?php endfor; ?>
                            </td>
                            <td><?php echo htmlspecialchars($r["comentariu"] ?? "-"); ?></td>
                            <td><?php echo date("d.m.Y", strtotime($r["created_at"])); ?></td>
                            <td>
                                <a href="recenzii.php?sterge=<?php echo $r['id']; ?>"
                                   class="table-btn delete-btn"
                                   onclick="return confirm('Ștergi această recenzie?')">
                                    Șterge
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="stats-empty">Nu există recenzii încă.</p>
        <?php endif; ?>

    </main>

</div>

</body>
</html>

