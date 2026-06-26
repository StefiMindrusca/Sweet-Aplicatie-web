<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: categories.php");
    exit();
}

$id = (int)$_GET["id"];
$message = "";

$sql = "SELECT * FROM categories WHERE id = $id";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: categories.php");
    exit();
}

$category = mysqli_fetch_assoc($result);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $type = trim($_POST["type"]);

    if (!empty($name) && !empty($type)) {
        $name = mysqli_real_escape_string($conn, $name);
        $type = mysqli_real_escape_string($conn, $type);

        $check_sql = "SELECT * FROM categories WHERE name = '$name' AND id != $id";
        $check_result = mysqli_query($conn, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $message = "Există deja altă categorie cu acest nume.";
        } else {
            $update_sql = "UPDATE categories SET name = '$name', type = '$type' WHERE id = $id";

            if (mysqli_query($conn, $update_sql)) {
                $message = "Categoria a fost actualizată cu succes.";

                $result = mysqli_query($conn, "SELECT * FROM categories WHERE id = $id");
                $category = mysqli_fetch_assoc($result);
            } else {
                $message = "Eroare la actualizare.";
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
    <title>Editează categorie</title>
    <link rel="stylesheet" href="admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">

    <aside class="admin-sidebar">
        <h2>Sweet Admin</h2>

        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Produse</a><a href="add-product.php">Adaugă produs</a>
        <a href="categories.php" class="active-admin-link">Categorii</a>
        <a href="orders.php">Comenzi</a>
        <a href="users.php">Utilizatori</a>
        <a href="recenzii.php">Recenzii</a>
        <a href="../logout.php"><img src="../imagini/exit.png" alt="" class="admin-logout-icon"> Logout</a>
    </aside>

    <main class="admin-main">

        <div class="admin-top-box admin-top-box-small">
            <h1>Editează categorie</h1>
        </div>

        <?php if (!empty($message)) { ?>
            <div class="admin-message"><?php echo $message; ?></div>
        <?php } ?>

        <div class="admin-form-box admin-form-box-small">
            <form method="post" class="admin-form-grid">

                <div>
                    <label for="name">Nume categorie</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($category["name"]); ?>" required>
                </div>

                <div>
                    <label for="type">Type</label>
                    <select name="type" id="type" required>
                        <option value="box" <?php if ($category["type"] == "box") echo "selected"; ?>>Box</option>
                        <option value="simple" <?php if ($category["type"] == "simple") echo "selected"; ?>>Simple</option>
                    </select>
                </div>

                <div class="admin-form-full admin-btn-center">
                    <button type="submit" class="admin-btn admin-btn-small">Salvează modificările</button>
                </div>

            </form>
        </div>

    </main>

</div>

</body>
</html>

