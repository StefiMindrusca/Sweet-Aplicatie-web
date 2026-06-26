<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

$message = "";

if (isset($_SESSION["category_message"])) {
    $message = $_SESSION["category_message"];
    unset($_SESSION["category_message"]);
}

$sql = "SELECT * FROM categories ORDER BY id ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorii Admin - Sweet</title>
    <link rel="stylesheet" href="admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">

    <aside class="admin-sidebar">
        <h2>Sweet Admin</h2>

        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Produse</a>
        <a href="categories.php" class="active-admin-link">Categorii</a>
        <a href="orders.php">Comenzi</a>
        <a href="users.php">Utilizatori</a>
        <a href="recenzii.php">Recenzii</a>
        <a href="../logout.php"><img src="../imagini/exit.png" alt="" class="admin-logout-icon"> Logout</a>
    </aside>

    <main class="admin-main">

        <div class="admin-top-box admin-top-flex">
            <div>
                <h1>Categorii</h1>
            </div>

            <div class="admin-actions-buttons">
                <a href="add-category.php" class="admin-btn">+ Adaugă categorie</a>
            </div>
        </div>

        <?php if (!empty($message)) { ?>
            <div class="admin-message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <div class="admin-table-box">
            <table class="admin-table">
                <tr>
                    <th>ID</th>
                    <th>Nume</th>
                    <th>Type</th>
                    <th>Acțiuni</th>
                </tr>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row["id"]; ?></td>
                            <td><?php echo htmlspecialchars($row["name"]); ?></td>
                            <td><?php echo htmlspecialchars($row["type"]); ?></td>
                            <td>
                                <a href="edit-category.php?id=<?php echo $row["id"]; ?>" class="table-btn edit-btn">Editează</a>
                                <a href="delete-category.php?id=<?php echo $row["id"]; ?>" class="table-btn delete-btn" onclick="return confirm('Sigur vrei să ștergi această categorie?')">Șterge</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Nu există categorii în baza de date.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

    </main>

</div>

</body>
</html>
