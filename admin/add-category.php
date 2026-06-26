<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $type = trim($_POST["type"]);

    if (!empty($name) && !empty($type)) {
        $name = mysqli_real_escape_string($conn, $name);
        $type = mysqli_real_escape_string($conn, $type);

        $check_sql = "SELECT * FROM categories WHERE name = '$name'";
        $check_result = mysqli_query($conn, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $message = "Categoria există deja.";
        } else {
            $sql = "INSERT INTO categories (name, type) VALUES ('$name', '$type')";

            if (mysqli_query($conn, $sql)) {
                $message = "Categoria a fost adăugată cu succes.";
            } else {
                $message = "Eroare la adăugarea categoriei.";
            }
        }
    } else {
        $message = "Completează toate câmpurile.";
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Adaugă categorie</title>
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
        <a href="recenzii.php">Recenzii</a>
        <a href="../logout.php"><img src="../imagini/exit.png" alt="" class="admin-logout-icon"> Logout</a>
    </aside>

    <main class="admin-main">

        <div class="admin-top-box admin-top-box-small">
            <h1>Adaugă categorie</h1>
        </div>

        <?php if (!empty($message)) { ?>
            <div class="admin-message"><?php echo $message; ?></div>
        <?php } ?>

        <div class="admin-form-box admin-form-box-small">
            <form method="post" class="admin-form-grid">

                <div>
                    <label for="name">Nume categorie</label>
                    <input type="text" name="name" id="name" required>
                </div>

                <div>
                    <label for="type">Tip categorie</label>
                    <select name="type" id="type" required>
                        <option value="">-- Alege --</option>
                        <option value="box">Box</option>
                        <option value="simple">Simple</option>
                    </select>
                </div>

                <div class="admin-form-full admin-btn-center">
                    <button type="submit" class="admin-btn admin-btn-small">Salvează categoria</button>
                </div>

            </form>
        </div>

    </main>

</div>

</body>
</html>

