<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

$page = max(1, (int)($_GET["page"] ?? 1));
$total_pages = max(1, (int)ceil(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM users"))["n"] / 10));
$page = min($page, $total_pages);
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC LIMIT 10 OFFSET " . ($page - 1) * 10);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilizatori Admin - Sweet</title>
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
        <a href="users.php" class="active-admin-link">Utilizatori</a>
        <a href="recenzii.php">Recenzii</a>
        <a href="../logout.php"><img src="../imagini/exit.png" alt="" class="admin-logout-icon"> Logout</a>
    </aside>

    <main class="admin-main">
        <div class="admin-top-box admin-top-flex">
            <div>
                <h1>Utilizatori</h1>
            </div>
        </div>

        <div class="admin-table-box">
            <table class="admin-table">
                <tr>
                    <th>ID</th>
                    <th>Nume</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Acțiuni</th>
                </tr>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row["id"]; ?></td>

                            <td>
                                <?php
                                if (isset($row["name"])) {
                                    echo htmlspecialchars($row["name"]);
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>

                            <td>
                                <?php
                                if (isset($row["email"])) {
                                    echo htmlspecialchars($row["email"]);
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>

                            <td>
                                <?php
                                if (isset($row["role"])) {
                                    echo htmlspecialchars($row["role"]);
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>

                            <td>
                                <a href="delete-user.php?id=<?php echo $row["id"]; ?>"
                                   class="table-btn delete-btn"
                                   onclick="return confirm('Sigur vrei să ștergi acest utilizator?')">
                                    Șterge
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Nu există utilizatori în baza de date.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="admin-pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="page-arrow">&lt;</a>
            <?php else: ?>
                <span class="page-arrow disabled">&lt;</span>
            <?php endif; ?>
            <span class="page-num"><?php echo $page; ?>/<?php echo $total_pages; ?></span>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="page-arrow">&gt;</a>
            <?php else: ?>
                <span class="page-arrow disabled">&gt;</span>
            <?php endif; ?>
        </div>

    </main>

</div>

</body>
</html>
