<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

$product_count = 0;
$product_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
if ($product_result) {
    $product_data = mysqli_fetch_assoc($product_result);
    $product_count = $product_data["total"];
}

$user_count = 0;
$user_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
if ($user_result) {
    $user_data = mysqli_fetch_assoc($user_result);
    $user_count = $user_data["total"];
}

$order_count = 0;
$order_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders");
if ($order_result) {
    $order_data = mysqli_fetch_assoc($order_result);
    $order_count = $order_data["total"];
}

$incasari_totale = 0;
$incasari_result = mysqli_query($conn, "SELECT SUM(total) AS total FROM orders WHERE status != 'anulata'");
if ($incasari_result) {
    $incasari_data = mysqli_fetch_assoc($incasari_result);
    $incasari_totale = $incasari_data["total"] ?? 0;
}

$comenzi_noi = 0;
$comenzi_noi_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE status = 'comanda noua'");
if ($comenzi_noi_result) {
    $comenzi_noi_data = mysqli_fetch_assoc($comenzi_noi_result);
    $comenzi_noi = $comenzi_noi_data["total"];
}

$month = isset($_GET["month"]) ? (int)$_GET["month"] : (int)date("m");
$year = isset($_GET["year"]) ? (int)$_GET["year"] : (int)date("Y");

if ($month < 1 || $month > 12) {
    $month = (int)date("m");
}

$selected_day = isset($_GET["day"]) ? (int)$_GET["day"] : (int)date("d");

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

if ($selected_day < 1 || $selected_day > $days_in_month) {
    $selected_day = 1;
}

$selected_date = sprintf("%04d-%02d-%02d", $year, $month, $selected_day);

$prev_month = $month - 1;
$prev_year = $year;

if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;

if ($next_month > 12) {
    $next_month = 1;
    $next_year++;


}

$month_names = [
    1 => "Ianuarie",
    2 => "Februarie",
    3 => "Martie",
    4 => "Aprilie",
    5 => "Mai",
    6 => "Iunie",
    7 => "Iulie",
    8 => "August",
    9 => "Septembrie",
    10 => "Octombrie",
    11 => "Noiembrie",
    12 => "Decembrie"
];

$orders_by_day = [];

$start_month = sprintf("%04d-%02d-01", $year, $month);
$end_month = sprintf("%04d-%02d-%02d", $year, $month, $days_in_month);

$orders_days_sql = "
    SELECT DAY(created_at) AS day_number, COUNT(*) AS total_orders
    FROM orders
    WHERE DATE(created_at) BETWEEN '$start_month' AND '$end_month'
    GROUP BY DAY(created_at)
";

$orders_days_result = mysqli_query($conn, $orders_days_sql);

if ($orders_days_result) {
    while ($row = mysqli_fetch_assoc($orders_days_result)) {
        $orders_by_day[(int)$row["day_number"]] = (int)$row["total_orders"];
    }
}

$day_stats_sql = "
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(total), 0) AS total_income,
        COALESCE(AVG(total), 0) AS average_order
    FROM orders
    WHERE DATE(created_at) = '$selected_date'
";

$day_stats_result = mysqli_query($conn, $day_stats_sql);
$day_stats = mysqli_fetch_assoc($day_stats_result);

$total_orders = (int)($day_stats["total_orders"] ?? 0);
$total_income = (float)($day_stats["total_income"] ?? 0);
$average_order = (float)($day_stats["average_order"] ?? 0);

$top_products_sql = "
    SELECT
        oi.product_name,
        SUM(oi.quantity) AS total_quantity,
        SUM(oi.quantity * oi.price) AS total_value
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) = '$selected_date'
      AND oi.e_cutie = 0
    GROUP BY oi.product_name
    ORDER BY total_quantity DESC
    LIMIT 5
";

$top_products_result = mysqli_query($conn, $top_products_sql);

$top_products = [];
if ($top_products_result) {
    while ($row = mysqli_fetch_assoc($top_products_result)) {
        $top_products[] = $row;
    }
}

$stoc_critic_sql = "SELECT name, stock FROM products WHERE stock < 5 ORDER BY stock ASC";
$stoc_critic_result = mysqli_query($conn, $stoc_critic_sql);
$stoc_critic = [];
if ($stoc_critic_result) {
    while ($row = mysqli_fetch_assoc($stoc_critic_result)) {
        $stoc_critic[] = $row;
    }
}

$first_day_week = (int)date("N", strtotime($start_month));
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sweet</title>
    <link rel="stylesheet" href="admin.css?v=<?php echo time(); ?>">
    <script src="../chart.umd.min.js"></script>
</head>
<body>

<div class="admin-layout">

    <aside class="admin-sidebar">
        <h2>Sweet Admin</h2>

        <a href="dashboard.php" class="active-admin-link">Dashboard</a>
        <a href="products.php">Produse</a>
        <a href="categories.php">Categorii</a>
        <a href="orders.php">Comenzi</a>
        <a href="users.php">Utilizatori</a>
        <a href="recenzii.php">Recenzii</a>
<a href="../logout.php"><img src="../imagini/exit.png" alt="" class="admin-logout-icon"> Logout</a>
    </aside>

    <main class="admin-main">
        <div class="admin-top-box">
            <h1> Bine ai venit, <?php echo htmlspecialchars($_SESSION["user_name"]); ?></h1>
        </div>

        <div class="admin-cards">
            <div class="admin-card" onclick="location.href='products.php'">
                <h3>Produse</h3>
                <p><?php echo $product_count; ?></p>
            </div>

            <div class="admin-card" onclick="location.href='orders.php'">
                <h3>Comenzi totale</h3>
                <p><?php echo $order_count; ?></p>
            </div>

            <div class="admin-card" onclick="location.href='users.php'">
                <h3>Utilizatori</h3>
                <p><?php echo $user_count; ?></p>
            </div>

            <div class="admin-card">
                <h3>Încasări totale</h3>
                <p><?php echo number_format($incasari_totale, 2); ?> lei</p>
            </div>

            <div class="admin-card" onclick="location.href='orders.php?status=comanda+noua'">
                <h3>Comenzi noi</h3>
                <p><?php echo $comenzi_noi; ?></p>
            </div>
        </div>

        <div class="stats-layout">

            <section class="stats-calendar-box">

                <div class="stats-calendar-header">
                    <a href="dashboard.php?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>&day=1">←</a>

                    <h2><?php echo $month_names[$month] . " " . $year; ?></h2>

                    <a href="dashboard.php?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>&day=1">→</a>
                </div>

                <div class="stats-calendar-grid stats-calendar-days">
                    <div>Lu</div>
                    <div>Ma</div>
                    <div>Mi</div>
                    <div>Jo</div>
                    <div>Vi</div>
                    <div>Sâ</div>
                    <div>Du</div>
                </div>

                <div class="stats-calendar-grid">
                    <?php for ($i = 1; $i < $first_day_week; $i++): ?>
                        <div class="calendar-empty"></div>
                    <?php endfor; ?>

                    <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                        <?php
                        $date = sprintf("%04d-%02d-%02d", $year, $month, $day);
                        $has_orders = isset($orders_by_day[$day]);
                        $is_selected = $day == $selected_day;

                        $classes = "calendar-day";

                        if ($has_orders) {
                            $classes .= " has-orders";
                        }

                        if ($is_selected) {
                            $classes .= " selected-day";
                        }
                        ?>

                        <a href="dashboard.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>&day=<?php echo $day; ?>" class="<?php echo $classes; ?>">
                            <span><?php echo $day; ?></span>

                            <?php if ($has_orders): ?>
                                <small><?php echo $orders_by_day[$day]; ?> comenzi</small>
                            <?php endif; ?>
                        </a>

                    <?php endfor; ?>
                </div>

            </section>

            <aside class="stats-day-box">

                <h2><?php echo date("d.m.Y", strtotime($selected_date)); ?></h2>

                <div class="stats-card">
                    <span>Comenzi</span>
                    <strong><?php echo $total_orders; ?></strong>
                </div>

                <div class="stats-card">
                    <span>Încasări</span>
                    <strong><?php echo number_format($total_income, 2); ?> lei</strong>
                </div>

                <div class="stats-card">
                    <span>Comandă medie</span>
                    <strong><?php echo number_format($average_order, 2); ?> lei</strong>
                </div>

            </aside>

        </div>

        <section class="stats-products-box">
            <h2>Top 5 produse vândute în ziua selectată</h2>

            <?php if (count($top_products) > 0): ?>

                <canvas id="topProductsChart"></canvas>

                <script>
                    var labels = <?php
                        $names = [];
                        foreach ($top_products as $p) {
                            $names[] = $p["product_name"];
                        }
                        echo json_encode($names);
                    ?>;

                    var quantities = <?php
                        $qtys = [];
                        foreach ($top_products as $p) {
                            $qtys[] = (int)$p["total_quantity"];
                        }
                        echo json_encode($qtys);
                    ?>;

                    var ctx = document.getElementById("topProductsChart").getContext("2d");

                    new Chart(ctx, {
                        type: "bar",
                        data: {
                            labels: labels,
                            datasets: [{
                                label: "Bucăți vândute",
                                data: quantities,
                                backgroundColor: [
                                "#aed6f1",
                                "#a9dfbf",
                                "#d7bde2",
                                "#f9e79f",
                                "#f4a7b9"
                            ]
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { stepSize: 1 }
                                }
                            }
                        }
                    });
                </script>

            <?php else: ?>
                <p class="stats-empty">Nu există produse vândute în această zi.</p>
            <?php endif; ?>
        </section>

        <section class="stats-products-box" style="margin-top: 32px;">
            <h2>Produse cu stoc redus</h2>

            <?php if (count($stoc_critic) > 0): ?>
                <div class="stats-products-list">
                    <?php foreach ($stoc_critic as $p): ?>
                        <div class="stats-product-row">
                            <strong><?php echo htmlspecialchars($p["name"]); ?></strong>
                            <em><?php echo (int)$p["stock"]; ?> bucăți rămase</em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="stats-empty">Niciun produs cu stoc critic.</p>
            <?php endif; ?>
        </section>

    </main>

</div>

</body>
</html>
