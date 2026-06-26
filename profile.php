<?php
session_start();
include 'config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];
$message = "";
$error = "";

function statusLabel($status) {
    $labels = [
        "noua" => "Comandă nouă",
        "comanda noua" => "Comandă nouă",
        "confirmata" => "Confirmată",
        "in procesare" => "În pregătire",
        "in pregatire" => "În pregătire",
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
        "noua" => "comanda-noua",
        "comanda noua" => "comanda-noua",
        "confirmata" => "confirmata",
        "in procesare" => "in-pregatire",
        "in pregatire" => "in-pregatire",
        "in livrare" => "in-livrare",
        "livrata" => "livrata",
        "gata de ridicat" => "gata-de-ridicat",
        "ridicata" => "ridicata",
        "anulata" => "anulata"
    ];

    return $classes[$status] ?? "comanda-noua";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    $new_name = trim($_POST["name"] ?? "");
    $new_email = trim($_POST["email"] ?? "");

    if ($new_name === "" || $new_email === "") {
        $error = "Completează toate câmpurile.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresa de email nu este validă.";
    } else {
        $safe_name = mysqli_real_escape_string($conn, $new_name);
        $safe_email = mysqli_real_escape_string($conn, $new_email);

        $check_email = mysqli_query(
            $conn,
            "SELECT id FROM users WHERE email = '$safe_email' AND id != $user_id LIMIT 1"
        );

        if ($check_email && mysqli_num_rows($check_email) > 0) {
            $error = "Această adresă de email este deja folosită.";
        } else {
            mysqli_query(
                $conn,
                "UPDATE users SET name = '$safe_name', email = '$safe_email' WHERE id = $user_id"
            );

            $_SESSION["user_name"] = $new_name;
            $message = "Profilul a fost actualizat cu succes.";
        }
    }
}

$user_result = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id LIMIT 1");
$user = mysqli_fetch_assoc($user_result);

$orders_result = mysqli_query($conn, "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC");

$tab = $_GET["tab"] ?? "orders";

$initials = "U";

if (!empty($user["name"])) {
    $parts = explode(" ", trim($user["name"]));
    $initials = "";

    foreach ($parts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
        if (strlen($initials) == 2) break;
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilul meu - Sweet</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main id="main-content" class="profile-page">

    <div class="profile-dashboard">

        <aside class="profile-sidebar" aria-label="Meniu profil client">

            <div class="profile-sidebar-title">
                <img src="imagini/pastry.png" alt="" class="profile-sidebar-icon" aria-hidden="true">
                <div>
                    <h1>Bun venit,</h1>
                    <p><?php echo htmlspecialchars($user["name"]); ?></p>
                </div>
            </div>

            <nav class="profile-side-menu" aria-label="Acțiuni profil">
                <a href="profile.php?tab=orders#istoric-comenzi" class="<?php echo $tab === 'orders' ? 'active-profile-menu' : ''; ?>">
                    Istoric comenzi
                </a>

                <a href="profile.php?tab=edit" class="<?php echo $tab === 'edit' ? 'active-profile-menu' : ''; ?>">
                    Editează profilul
                </a>

                <a href="logout.php">
                    Deconectare
                </a>
            </nav>

        </aside>

        <section class="profile-content">

            <?php if ($message): ?>
                <p class="profile-success-msg" role="status"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>

            <?php if ($error): ?>
                <p class="profile-error-msg" role="alert"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if ($tab === "edit"): ?>

                <section class="profile-edit-card" aria-labelledby="edit-profile-title">

                    <h2 id="edit-profile-title">Editează profilul</h2>
                    <p class="profile-edit-subtitle">Actualizează numele sau adresa de email asociate contului tău.</p>

                    <form method="POST" class="profile-edit-form">

                        <div class="form-group">
                            <label for="name">Nume</label>
                            <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="<?php echo htmlspecialchars($user["name"]); ?>"
                                    required
                                    <?php echo $tab === 'edit' ? 'autofocus' : ''; ?>
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">Adresă de email</label>
                            <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars($user["email"]); ?>"
                                    required
                            >
                        </div>

                        <button type="submit" name="update_profile" class="profile-save-btn">
                            Salvează modificările
                        </button>

                    </form>

                </section>

            <?php else: ?>

                <section class="profile-orders-card" id="istoric-comenzi" aria-labelledby="orders-title">

                    <h2 id="orders-title">Istoric comenzi</h2>

                    <?php if ($orders_result && mysqli_num_rows($orders_result) > 0): ?>

                        <div class="profile-orders-list">

                            <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                                <?php
                                $status_label = statusLabel($order["status"]);
                                $status_class = statusClass($order["status"]);
                                ?>

                                <a href="client-order-details.php?id=<?php echo $order["id"]; ?>" class="profile-order-card">

                                    <div class="profile-order-date">
                                        <strong><?php echo date("d", strtotime($order["created_at"])); ?></strong>
                                        <span><?php echo date("m.Y", strtotime($order["created_at"])); ?></span>
                                    </div>

                                    <div class="profile-order-middle">
                                        <h3>Comanda #<?php echo $order["id"]; ?></h3>
                                        <p><?php echo number_format($order["total"], 2); ?> lei</p>
                                    </div>

                                    <span class="profile-order-status status-<?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($status_label); ?>
                                    </span>

                                </a>

                            <?php endwhile; ?>

                        </div>

                    <?php else: ?>

                        <p class="profile-empty">Nu ai comenzi încă.</p>

                    <?php endif; ?>

                </section>

            <?php endif; ?>

        </section>

    </div>

</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
