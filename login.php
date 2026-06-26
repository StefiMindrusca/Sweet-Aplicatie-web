<?php
session_start();
include 'config/db.php';
/** @var mysqli $conn */

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, trim($_POST["email"]));
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $message = "Completează toate câmpurile.";
    } else {
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user["password"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"];

                $id_utilizator = (int)$user["id"];

                if (empty($_SESSION["cos"])) {
                    $rezultat_interogare_cos = mysqli_query($conn, "SELECT cos_client FROM cos_persistent WHERE user_id = $id_utilizator LIMIT 1");

                    if ($rezultat_interogare_cos && mysqli_num_rows($rezultat_interogare_cos) > 0) {
                        $rand_cos_persistent = mysqli_fetch_assoc($rezultat_interogare_cos);

                        if (!empty($rand_cos_persistent["cos_client"])) {
                            $cos_recuperat_din_db = json_decode($rand_cos_persistent["cos_client"], true);

                            if (is_array($cos_recuperat_din_db)) {
                                $_SESSION["cos"] = $cos_recuperat_din_db;
                            }
                        }
                    }
                }

                if (!empty($_SESSION["cos"])) {
                    $cos_json = mysqli_real_escape_string($conn, json_encode($_SESSION["cos"]));
                    mysqli_query($conn, "INSERT INTO cos_persistent (user_id, cos_client)
                                         VALUES ($id_utilizator, '$cos_json')
                                         ON DUPLICATE KEY UPDATE cos_client = '$cos_json'");
                }
                if ($user["role"] == "admin") {
                    header("Location: admin/dashboard.php");
                    exit();
                } else {
                    if (isset($_GET["redirect"]) && $_GET["redirect"] == "checkout") {
                        header("Location: checkout.php");
                        exit();
                    }

                    header("Location: index.php");
                    exit();
                }
            } else {
                $message = "Parola este greșită.";
            }
        } else {
            $message = "Nu există cont cu acest email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sweet</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main id="main-content" class="auth-page">
    <section class="auth-box">
        <h1>Autentificare</h1>

        <?php if (!empty($message)) { ?>
            <p class="auth-message" role="alert"><?php echo $message; ?></p>
        <?php } ?>

        <form method="post" action="" autocomplete="on">
            <div class="form-group">
                <label for="email">Email</label>
                <input
                        type="email"
                        name="email"
                        id="email"
                        autocomplete="email"
                        required
                >
            </div>

            <div class="form-group">
                <label for="password">Parolă</label>
                <input
                        type="password"
                        name="password"
                        id="password"
                        autocomplete="current-password"
                        required
                >
            </div>

            <button type="submit" class="btn btn-dark auth-btn">Login</button>
        </form>

        <p class="auth-link-text">
            Nu ai cont? <a href="register.php<?php echo isset($_GET["redirect"]) ? '?redirect=' . htmlspecialchars($_GET["redirect"]) : ''; ?>">
                Creează unul
            </a>
        </p>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

<script src="script.js"></script>
</body>
</html>
