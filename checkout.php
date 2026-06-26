<?php
session_start();
include 'config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php?redirect=checkout");
    exit();
}

if (empty($_SESSION["cos"])) {
    header("Location: cos.php");
    exit();
}

$cos_checkout = $_SESSION["cos"] ?? [];
foreach ($cos_checkout as $item) {
    if ($item["type"] == "simple") {
        $pid = (int)$item["id"];
        $res = mysqli_query($conn, "SELECT stock FROM products WHERE id = $pid LIMIT 1");
        $row = mysqli_fetch_assoc($res);
        if ((int)$row["stock"] < (int)$item["quantity"]) {
            header("Location: cos.php?error=stoc_insuficient");
            exit();
        }
    }
    if ($item["type"] == "box" && isset($item["items"])) {
        foreach ($item["items"] as $produs) {
            $pid = (int)$produs["id"];
            $necesar = (int)$produs["quantity"] * (int)$item["quantity"];
            $res = mysqli_query($conn, "SELECT stock FROM products WHERE id = $pid LIMIT 1");
            $row = mysqli_fetch_assoc($res);
            if ((int)$row["stock"] < $necesar) {
                header("Location: cos.php?error=stoc_insuficient");
                exit();
            }
        }
    }
}

$total = 0;
foreach ($_SESSION["cos"] as $item) {
    $total += $item["price"] * $item["quantity"];
}

$message = "";
$old = $_SESSION["checkout_data"] ?? [];

if (empty($old) && isset($_SESSION["user_email"])) {
    $old["email"] = $_SESSION["user_email"];
}

if (empty($old) && isset($_SESSION["user_name"])) {
    $parts = explode(" ", $_SESSION["user_name"], 2);
    $old["prenume"] = $parts[0] ?? "";
    $old["nume"] = $parts[1] ?? "";
}

$metoda_livrare = $old["metoda_livrare"] ?? "domiciliu";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prenume = trim($_POST["prenume"] ?? "");
    $nume = trim($_POST["nume"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefon = trim($_POST["telefon"] ?? "");
    $metoda_livrare = trim($_POST["metoda_livrare"] ?? "domiciliu");

    $adresa = trim($_POST["adresa"] ?? "");
    $oras = "Cluj-Napoca";
    $judet = "Cluj";
    $cod_postal = trim($_POST["cod_postal"] ?? "");

    if ($metoda_livrare !== "ridicare") {
        $metoda_livrare = "domiciliu";
    }

    if (strlen($telefon) != 10 || !ctype_digit($telefon)) {
        $message = "Numărul de telefon trebuie să conțină exact 10 cifre.";
        $old = compact("prenume", "nume", "email", "telefon", "adresa", "oras", "judet", "cod_postal", "metoda_livrare");
    } elseif ($metoda_livrare == "domiciliu") {
        if (empty($prenume) || empty($nume) || empty($email) || empty($telefon) || empty($adresa)) {
            $message = "Te rugam sa completezi toate campurile marcate cu *.";
            $old = compact("prenume", "nume", "email", "telefon", "adresa", "oras", "judet", "cod_postal", "metoda_livrare");
        } else {
            $_SESSION["checkout_data"] = compact("prenume", "nume", "email", "telefon", "adresa", "oras", "judet", "cod_postal", "metoda_livrare");
            header("Location: payment.php");
            exit();
        }
    } else {
        if (empty($prenume) || empty($nume) || empty($email) || empty($telefon)) {
            $message = "Te rugam sa completezi toate campurile marcate cu *.";
            $old = compact("prenume", "nume", "email", "telefon", "adresa", "oras", "judet", "cod_postal", "metoda_livrare");
        } else {
            $_SESSION["checkout_data"] = [
                "prenume" => $prenume,
                "nume" => $nume,
                "email" => $email,
                "telefon" => $telefon,
                "adresa" => "",
                "oras" => "Cluj-Napoca",
                "judet" => "Cluj",
                "cod_postal" => "",
                "metoda_livrare" => "ridicare"
            ];

            header("Location: payment.php");
            exit();
        }
    }
}

if ($metoda_livrare == "ridicare") {
    $transport = 0;
} elseif ($total >= 50) {
    $transport = 0;
} else {
    $transport = 15;
}

$total_final = $total + $transport;
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Sweet</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main id="main-content" class="checkout-page">
    <div class="checkout-wrapper">

        <div class="checkout-box">

            <div class="checkout-steps">
                <span class="checkout-step active">1. Livrare</span>
                <span class="checkout-step-arrow">→</span>
                <span class="checkout-step">2. Plata</span>
            </div>

            <h1>Date de livrare</h1>

            <?php if ($message): ?>
                <p class="auth-message" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <form method="post" class="checkout-form">

                <p class="checkout-livrare-note">
                    Livrăm momentan doar în Cluj-Napoca.
                </p>

                <fieldset class="livrare-fieldset">
                    <legend>Metoda de livrare *</legend>

                    <div class="livrare-card-grid">

                        <label class="livrare-card">
                            <input
                                    type="radio"
                                    name="metoda_livrare"
                                    value="domiciliu"
                                <?php echo $metoda_livrare == "domiciliu" ? "checked" : ""; ?>
                                    onchange="toggleMetodaLivrare()"
                            >

                            <span class="livrare-card-title">Livrare la domiciliu</span><br>
                            <span class="livrare-card-text">Livrare estimată în 2–3 ore</span>
                        </label>

                        <label class="livrare-card">
                            <input
                                    type="radio"
                                    name="metoda_livrare"
                                    value="ridicare"
                                <?php echo $metoda_livrare == "ridicare" ? "checked" : ""; ?>
                                    onchange="toggleMetodaLivrare()"
                            >

                            <span class="livrare-card-title">Ridicare din magazin</span><br>
                            <span class="livrare-card-text">Gratuit si gata în aproximativ 2 ore</span>
                        </label>

                    </div>
                </fieldset>

                <div
                        id="box-ridicare"
                        class="box-ridicare-info"
                    <?php echo $metoda_livrare == "domiciliu" ? "hidden" : ""; ?>
                >
                    <p><strong>Adresa de ridicare:</strong></p>
                    <p>Str. Soarelui nr. 500, Cluj-Napoca</p>
                    <p class="livrare-estimare-text">Gata în aprox. 2 ore · Transport gratuit</p>
                </div>

                <p class="required-note">* campurile marcate sunt obligatorii</p>

                <div class="checkout-row">
                    <div class="form-group">
                        <label for="prenume">Prenume *</label>
                        <input
                                type="text"
                                name="prenume"
                                id="prenume"
                                value="<?php echo htmlspecialchars($old["prenume"] ?? ""); ?>"
                                required
                        >
                    </div>

                    <div class="form-group">
                        <label for="nume">Nume *</label>
                        <input
                                type="text"
                                name="nume"
                                id="nume"
                                value="<?php echo htmlspecialchars($old["nume"] ?? ""); ?>"
                                required
                        >
                    </div>
                </div>

                <div class="checkout-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input
                                type="email"
                                name="email"
                                id="email"
                                value="<?php echo htmlspecialchars($old["email"] ?? ""); ?>"
                                required
                        >
                    </div>

                    <div class="form-group">
                        <label for="telefon">Telefon *</label>
                        <input
                                type="tel"
                                name="telefon"
                                id="telefon"
                                value="<?php echo htmlspecialchars($old["telefon"] ?? ""); ?>"
                                placeholder="07xx xxx xxx"
                                required
                        >
                    </div>
                </div>

                <fieldset
                        id="campuri-adresa"
                        class="campuri-adresa-fieldset"
                    <?php echo $metoda_livrare == "ridicare" ? "hidden disabled" : ""; ?>
                >
                    <legend class="sr-only">Adresa de livrare</legend>

                    <div class="form-group">
                        <label for="adresa">Adresa *</label>
                        <input
                                type="text"
                                name="adresa"
                                id="adresa"
                                value="<?php echo htmlspecialchars($old["adresa"] ?? ""); ?>"
                                placeholder="Strada, numarul, blocul, apartamentul"
                        >
                    </div>

                    <div class="checkout-row">
                        <div class="form-group">
                            <label for="oras">Oraș *</label>
                            <input
                                    type="text"
                                    name="oras"
                                    id="oras"
                                    value="Cluj-Napoca"
                                    readonly
                            >
                        </div>

                        <div class="form-group">
                            <label for="judet">Județ *</label>
                            <input
                                    type="text"
                                    name="judet"
                                    id="judet"
                                    value="Cluj"
                                    readonly
                            >
                        </div>
                    </div>

                    <div class="form-group" style="max-width:180px;">
                        <label for="cod_postal">Cod postal</label>
                        <input
                                type="text"
                                name="cod_postal"
                                id="cod_postal"
                                value="<?php echo htmlspecialchars($old["cod_postal"] ?? ""); ?>"
                                placeholder="xxxxxx"
                        >
                    </div>
                </fieldset>

                <button type="submit" class="btn btn-dark checkout-btn">
                    Continua catre plata →
                </button>

            </form>
        </div>

        <div class="checkout-summary-box">
            <h3>Sumar comanda</h3>

            <?php foreach ($_SESSION["cos"] as $item): ?>
                <div class="summary-row">
                    <span>
                        <?php echo htmlspecialchars($item["name"]); ?>
                        <em>x<?php echo $item["quantity"]; ?></em>
                    </span>
                    <span>
                        <?php echo number_format($item["price"] * $item["quantity"], 2); ?> lei
                    </span>
                </div>
            <?php endforeach; ?>

            <div class="summary-row">
                <span>Transport:</span>
                <span id="transport-display" data-subtotal="<?php echo $total; ?>">
                    <?php if ($transport == 0): ?>
                        <span class="cart-free">GRATUIT</span>
                    <?php else: ?>
                        <?php echo number_format($transport, 2); ?> lei
                    <?php endif; ?>
                </span>
            </div>

            <div class="summary-total">
                <span>Total</span>
                <span id="total-display" aria-live="polite" aria-atomic="true"><?php echo number_format($total_final, 2); ?> lei</span>
            </div>

            <a href="cos.php" class="summary-back">← Inapoi la cos</a>
        </div>

    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
    function toggleMetodaLivrare() {
        const metoda = document.querySelector('input[name="metoda_livrare"]:checked').value;
        const campuriAdresa = document.getElementById('campuri-adresa');
        const boxRidicare = document.getElementById('box-ridicare');
        const transportDisplay = document.getElementById('transport-display');
        const totalDisplay = document.getElementById('total-display');
        const subtotal = parseFloat(transportDisplay.dataset.subtotal);

        if (metoda === 'ridicare') {
            campuriAdresa.hidden = true;
            campuriAdresa.disabled = true;
            boxRidicare.hidden = false;
            transportDisplay.innerHTML = '<span class="cart-free">GRATUIT</span>';
            totalDisplay.textContent = subtotal.toFixed(2) + ' lei';
        } else {
            campuriAdresa.hidden = false;
            campuriAdresa.disabled = false;
            boxRidicare.hidden = true;
            const transport = subtotal >= 50 ? 0 : 15;
            if (transport === 0) {
                transportDisplay.innerHTML = '<span class="cart-free">GRATUIT</span>';
            } else {
                transportDisplay.textContent = '15.00 lei';
            }
            totalDisplay.textContent = (subtotal + transport).toFixed(2) + ' lei';
        }
    }
</script>

</body>
</html>
