<?php
session_start();
include 'config/stripe.php';

if (empty($_SESSION["cos"]) || empty($_SESSION["checkout_data"])) {
    header("Location: checkout.php");
    exit();
}

$cos = $_SESSION["cos"];
$checkout = $_SESSION["checkout_data"];

$subtotal = 0;
foreach ($cos as $item) {
    $subtotal += $item["price"] * $item["quantity"];
}

$metoda_livrare_pay = $checkout["metoda_livrare"] ?? "domiciliu";
if ($metoda_livrare_pay == "ridicare") {
    $transport_pay = 0;
} elseif ($subtotal >= 50) {
    $transport_pay = 0;
} else {
    $transport_pay = 15;
}

$total = $subtotal + $transport_pay;
$return_url = "http://" . $_SERVER["HTTP_HOST"] . "/magazin/payment-success.php";

$client_secret = "";

$ch = curl_init("https://api.stripe.com/v1/payment_intents");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    "amount" => (int)round($total * 100),
    "currency" => "ron",
    "description" => "Comanda Sweet",
    "payment_method_types[0]" => "card",
]));

curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ":");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);

$intent = json_decode($response, true);

if (isset($intent["client_secret"])) {
    $client_secret = $intent["client_secret"];
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plata - Sweet</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main id="main-content" class="checkout-page">
    <div class="checkout-wrapper">

        <div class="checkout-box">

            <div class="checkout-steps">
                <span class="checkout-step done">
                    <a href="checkout.php">1. Livrare</a>
                </span>
                <span class="checkout-step-arrow">→</span>
                <span class="checkout-step active">2. Plata</span>
            </div>

            <h1>Metoda de plată</h1>

            <div class="livrare-card-grid payment-method-grid">

                <label class="livrare-card payment-card">
                    <input
                            type="radio"
                            name="metoda_plata"
                            value="card"
                            checked
                    >

                    <span class="livrare-card-title">Plată cu cardul</span><br>
                    <span class="livrare-card-text">Plată online securizată prin Stripe</span>
                </label>

                <label class="livrare-card payment-card">
                    <input
                            type="radio"
                            name="metoda_plata"
                            value="cash"
                    >

                    <span class="livrare-card-title">Plată la livrare / ridicare</span><br>
                    <span class="livrare-card-text">Plătești cash când primești comanda</span>
                </label>

            </div>

            <div class="delivery-recap">
                <strong>
                    <?php echo htmlspecialchars($checkout["prenume"] . " " . $checkout["nume"]); ?>
                </strong><br>

                <?php if (($checkout["metoda_livrare"] ?? "domiciliu") == "ridicare"): ?>
                    Ridicare din magazin<br>
                    Str. Soarelui nr. 500, Cluj-Napoca
                <?php else: ?>
                    <?php echo htmlspecialchars($checkout["adresa"]); ?>,
                    <?php echo htmlspecialchars($checkout["oras"]); ?>
                    <?php if (!empty($checkout["judet"])): ?>
                        , <?php echo htmlspecialchars($checkout["judet"]); ?>
                    <?php endif; ?>
                <?php endif; ?>

                <br>

                <?php echo htmlspecialchars($checkout["email"]); ?>

                <?php if (!empty($checkout["telefon"])): ?>
                    &nbsp;·&nbsp; <?php echo htmlspecialchars($checkout["telefon"]); ?>
                <?php endif; ?>
            </div>

            <div id="card-payment-area">

                <form id="payment-form">
                    <div id="payment-element"></div>

                    <p id="stripe-error" class="stripe-error-msg"></p>

                    <button id="pay-btn" class="btn btn-dark checkout-btn">
                        Plătește <?php echo number_format($total, 2); ?> lei
                    </button>
                </form>

            </div>

            <form
                    id="cash-form"
                    action="payment-success.php"
                    method="get"
                    style="display:none;"
            >
                <input type="hidden" name="cash_order" value="1">

                <button type="submit" class="btn btn-dark checkout-btn">
                    Confirmă comanda
                </button>
            </form>

        </div>

        <div class="checkout-summary-box">
            <h3>Sumar comanda</h3>

            <?php foreach ($cos as $item): ?>
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
                <?php if ($transport_pay == 0): ?>
                    <span class="cart-free">GRATUIT</span>
                <?php else: ?>
                    <span><?php echo number_format($transport_pay, 2); ?> lei</span>
                <?php endif; ?>
            </div>

            <div class="summary-total">
                <span>Total</span>
                <span><?php echo number_format($total, 2); ?> lei</span>
            </div>

            <a href="checkout.php" class="summary-back">
                ← Modifică livrarea
            </a>
        </div>

    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
    const paymentRadios = document.querySelectorAll('input[name="metoda_plata"]');
    const cardArea = document.getElementById('card-payment-area');
    const cashForm = document.getElementById('cash-form');

    function updatePaymentMethod() {
        const selected = document.querySelector('input[name="metoda_plata"]:checked').value;

        if (selected === 'card') {
            cardArea.style.display = 'block';
            cashForm.style.display = 'none';
        } else {
            cardArea.style.display = 'none';
            cashForm.style.display = 'block';
        }
    }

    paymentRadios.forEach(radio => {
        radio.addEventListener('change', updatePaymentMethod);
    });

    updatePaymentMethod();

    <?php if (!empty($client_secret)): ?>
    const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');

    const elements = stripe.elements({
        clientSecret: '<?php echo $client_secret; ?>'
    });

    const paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');

    const form = document.getElementById('payment-form');
    const payBtn = document.getElementById('pay-btn');
    const errorMsg = document.getElementById('stripe-error');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        payBtn.disabled = true;
        payBtn.textContent = 'Se procesează...';
        errorMsg.textContent = '';

        const { error, paymentIntent } = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: '<?php echo $return_url; ?>'
            },
            redirect: 'if_required'
        });

        if (error) {
            errorMsg.textContent = error.message;
            payBtn.disabled = false;
            payBtn.textContent = 'Plătește <?php echo number_format($total, 2); ?> lei';
        } else if (paymentIntent && paymentIntent.status === 'succeeded') {
            window.location.href = 'payment-success.php?payment_intent=' + paymentIntent.id;
        }
    });
    <?php endif; ?>
</script>

</body>
</html>