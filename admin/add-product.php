<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

$message = "";

$categories_sql = "SELECT * FROM categories ORDER BY id ASC";
$categories_result = mysqli_query($conn, $categories_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $category = trim($_POST["category"]);
    $description = trim($_POST["description"]);
    $ingredients = trim($_POST["ingredients"]);
    $price = trim($_POST["price"]);
    $stock = trim($_POST["stock"]);

    $image_name = "";

    if (!empty($_FILES["image"]["name"])) {
        $image_name = basename($_FILES["image"]["name"]);
        $target_folder = "../imagini/";
        $target_file = $target_folder . $image_name;

        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
    }

    if (!empty($name) && !empty($category) && !empty($description) && !empty($ingredients) && $price !== "" && $stock !== "") {
        $name = mysqli_real_escape_string($conn, $name);
        $category = mysqli_real_escape_string($conn, $category);
        $description = mysqli_real_escape_string($conn, $description);
        $ingredients = mysqli_real_escape_string($conn, $ingredients);
        $price = (float)$price;
        $stock = (int)$stock;
        $image_name = mysqli_real_escape_string($conn, $image_name);

        $sql = "INSERT INTO products (name, category, description, ingredients, price, stock, image)
                VALUES ('$name', '$category', '$description', '$ingredients', '$price', '$stock', '$image_name')";

        if (mysqli_query($conn, $sql)) {
            $message = "Produsul a fost adăugat cu succes.";
        } else {
            $message = "Eroare la adăugarea produsului.";
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
    <title>Adaugă produs</title>
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
            <h1>Adaugă produs</h1>
        </div>

        <?php if (!empty($message)) { ?>
            <div class="admin-message"><?php echo $message; ?></div>
        <?php } ?>

        <div class="admin-form-box">
            <form method="post" enctype="multipart/form-data" class="admin-form-grid">

                <div>
                    <label for="name">Nume</label>
                    <input type="text" name="name" id="name" required>
                </div>

                <div>
                    <label for="category">Categorie</label>
                    <select name="category" id="category" required>
                        <option value="">-- Alege --</option>
                        <?php if ($categories_result && mysqli_num_rows($categories_result) > 0): ?>
                            <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                                <option value="<?php echo htmlspecialchars($cat["name"]); ?>">
                                    <?php echo htmlspecialchars($cat["name"]); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="admin-form-full">
                    <label for="description">Descriere</label>
                    <textarea name="description" id="description" rows="4" required></textarea>
                </div>

                <div class="admin-form-full">
                    <label for="ingredients">Ingrediente</label>
                    <textarea name="ingredients" id="ingredients" rows="4" required></textarea>
                </div>

                <div>
                    <label for="price">Preț</label>
                    <input type="number" step="0.01" name="price" id="price" required>
                </div>

                <div>
                    <label for="stock">Stoc</label>
                    <input type="number" name="stock" id="stock" min="0" required>
                </div>

                <div class="admin-form-full">
                    <label for="image">Imagine</label>
                    <input type="file" name="image" id="image" accept="image/*">
                </div>

                <div class="admin-form-full admin-btn-center">
                    <button class="admin-btn admin-btn-small">Salvează produsul</button>
                </div>

            </form>
        </div>

    </main>

</div>

</body>
</html>
