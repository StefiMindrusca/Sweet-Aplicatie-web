<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweet - Patiserie Artizanala</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main id="main-content">
    <section class="hero">
        <div class="hero-content">
            <h1>Sweet</h1>
            <p>Patiserii artizanale cu ingrediente naturale</p>
        </div>
    </section>

    <section class="home-categories">

        <div class="home-category-card">
            <div class="home-category-image">
                <img src="imagini/cookie_blackforest.png" alt="Biscuiți artizanali">
            </div>

            <div class="home-category-text">
                <h2>Cookies</h2>
                <p>Biscuiți fragezi, pregătiți artizanal, perfecți pentru orice moment dulce.</p>
                <a href="boxbuild.php?name=Cookies" class="btn btn-dark">Comandă</a>
            </div>
        </div>

        <div class="home-category-card reverse">
            <div class="home-category-image">
<img src="imagini/ciocolata.png" alt="Brioșe artizanale">
            </div>

            <div class="home-category-text">
                <h2>Brioșe</h2>
                <p>Brioșe moi și aromate din ingrediente naturale.</p>
                <a href="boxbuild.php?name=Briose" class="btn btn-dark">Comandă</a>
            </div>
        </div>

        <div class="home-category-card">
            <div class="home-category-image">
                <img src="imagini/macaronszmeura.png" alt="Macarons colorate">
            </div>

            <div class="home-category-text">
                <h2>Macarons</h2>
                <p>Macarons delicate, colorate și elegante, potrivite pentru cadouri sau evenimente.</p>
                <a href="boxbuild.php?name=Macarons" class="btn btn-dark">Comandă</a>
            </div>
        </div>

        <div class="home-category-card reverse">
            <div class="home-category-image">
<img src="imagini/cheescakezmeura.png" alt="Cheesecake artizanal">
            </div>

            <div class="home-category-text">
                <h2>Cheesecake</h2>
                <p>Cheesecake cremos, preparat artizanal din ingrediente naturale de calitate.</p>
                <a href="category.php?name=Cheesecake" class="btn btn-dark">Comandă</a>
            </div>
        </div>

        <div class="home-category-card">
            <div class="home-category-image">
<img src="imagini/starbucks_caramel.png" alt="Băuturi">
            </div>

            <div class="home-category-text">
                <h2>Băuturi</h2>
                <p>Băuturi gustoase, perfecte pentru a însoți produsele noastre de patiserie.</p>
                <a href="category.php?name=Bauturi" class="btn btn-dark">Comandă</a>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

<script src="script.js"></script>
</body>
</html>