<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'config/db.php';
/** @var mysqli $conn */

$categories_sql = "SELECT * FROM categories ORDER BY id ASC";
$categories_result = mysqli_query($conn, $categories_sql);

$cart_count = 0;

if (isset($_SESSION["cos"]) && is_array($_SESSION["cos"])) {
    foreach ($_SESSION["cos"] as $item) {
        $cart_count += (int)$item["quantity"];
    }
}
?>

<a href="#main-content" class="skip-link">Sari la continut principal</a>

<header class="site-header">
    <div class="logo">
        <a href="index.php">sweet</a>
    </div>

    <nav class="navbar">
        <ul class="nav-list">
            <li><a href="index.php">Acasă</a></li>
<li class="dropdown">
                <a href="#" class="dropdown-toggle" aria-haspopup="true" aria-expanded="false">Meniu</a>
                <ul class="dropdown-menu">
                    <?php if ($categories_result && mysqli_num_rows($categories_result) > 0): ?>
                        <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                            <li>
                                <?php if ($cat["type"] == "box"): ?>
                                    <a href="boxbuild.php?name=<?php echo urlencode($cat["name"]); ?>">
                                        <?php echo htmlspecialchars($cat["name"]); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="category.php?name=<?php echo urlencode($cat["name"]); ?>">
                                        <?php echo htmlspecialchars($cat["name"]); ?>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </li>

            <li>
                <a href="cos.php" class="cart-link" aria-label="<?php if ($cart_count > 0): ?>Cos cumparaturi, <?php echo $cart_count; ?> produse<?php else: ?>Cos cumparaturi, gol<?php endif; ?>">
                    <span class="cart-icon-wrap">
                        <img src="imagini/basket.png" alt="" class="cart-icon">
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge" aria-hidden="true"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </span>
                </a>
            </li>

            <?php if (isset($_SESSION["user_id"])): ?>
                <li class="dropdown profile-dropdown">
                    <a href="#" class="profile-icon" aria-haspopup="true" aria-expanded="false" aria-label="Contul meu">
                        <img src="imagini/user.png" alt="" class="user-icon">
                    </a>

                    <ul class="dropdown-menu profile-menu">
                        <li><a href="profile.php">Profilul meu</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
