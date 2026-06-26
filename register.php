<?php
session_start();
include 'config/db.php';
/** @var mysqli $conn */

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, trim($_POST["name"]));
    $email = mysqli_real_escape_string($conn, trim($_POST["email"]));
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "Toate câmpurile sunt obligatorii.";
    } elseif ($password != $confirm_password) {
        $message = "Parolele nu coincid.";
    } elseif (!str_ends_with($email, '@gmail.com') && !str_ends_with($email, '@yahoo.com')) {
        $message = "Sunt acceptate doar adrese de email @gmail.com sau @yahoo.com.";
    } else {
        $check_sql = "SELECT * FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $message = "Există deja un cont cu acest email.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (name, email, password) 
                    VALUES ('$name', '$email', '$hashed_password')";

            if (mysqli_query($conn, $sql)) {
                if (isset($_GET["redirect"]) && $_GET["redirect"] == "checkout") {
                    header("Location: login.php?redirect=checkout");
                    exit();
                }

                header("Location: login.php");
                exit();
            } else {
                $message = "Eroare la înregistrare.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sweet</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main id="main-content" class="auth-page">
    <section class="auth-box">
        <h1>Creează cont</h1>

        <?php if (!empty($message)) { ?>
            <p class="auth-message" role="alert"><?php echo $message; ?></p>
        <?php } ?>

        <form method="post" action="" autocomplete="on">
            <div class="form-group">
                <label for="name">Nume</label>
                <input
                        type="text"
                        name="name"
                        id="name"
                        autocomplete="name"
                        required
                >
            </div>

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
                        autocomplete="new-password"
                        required
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmă parola</label>
                <input
                        type="password"
                        name="confirm_password"
                        id="confirm_password"
                        autocomplete="new-password"
                        required
                >
            </div>

            <button type="submit" class="btn btn-dark auth-btn">Înregistrează-te</button>
        </form>

        <p class="auth-link-text">
            Ai deja cont? <a href="login.php<?php echo isset($_GET["redirect"]) ? '?redirect=' . htmlspecialchars($_GET["redirect"]) : ''; ?>">
                Autentifică-te
            </a>
        </p>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

<script src="script.js"></script>
</body>
</html>
