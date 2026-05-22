<?php
session_start();

$viesti = "";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=drinkkiarkisto;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Tietokantayhteys epäonnistui: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $uusiAines = trim($_POST["aines"] ?? "");

    if ($uusiAines === "") {
        $viesti = "Kenttä ei saa olla tyhjä.";
    } else {
        $tarkistus = $pdo->prepare("SELECT COUNT(*) FROM aines WHERE Aines_nimi = ?");
        $tarkistus->execute([$uusiAines]);
        $loydetty = $tarkistus->fetchColumn();

        if ($loydetty > 0) {
            $viesti = "Aines on jo olemassa!";
        } else {
            $lisaa = $pdo->prepare("INSERT INTO aines (Aines_nimi) VALUES (?)");

            if ($lisaa->execute([$uusiAines])) {
                $viesti = "Aines lisätty onnistuneesti!";
            } else {
                $viesti = "Virhe lisättäessä ainesta.";
            }
        }
    }
}

$hae = $pdo->query("SELECT Aines_nimi FROM aines ORDER BY Aines_nimi ASC");
$ainekset = $hae->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Ainesosan lisäys</title>
    <link rel="stylesheet" href="drinkkinstyle.css">
</head>
<body>

<header>
    <h1>Lisää uusi aines</h1>
</header>

<div class="container">
    <form method="post" action="ainesLis.php">
        <input type="text" name="aines" placeholder="Kirjoita aines">
        <br>
        <button type="submit">Lisää</button>
    </form>

    <?php if (!empty($viesti)): ?>
        <div class="message"><?= htmlspecialchars($viesti) ?></div>
    <?php endif; ?>

    <?php if (!empty($ainekset)): ?>
        <div class="ingredient-list">
            <h2>Lisätyt ainekset:</h2>
            <?php foreach ($ainekset as $rivi): ?>
                <p><strong>Ainesosa:</strong> <?= htmlspecialchars($rivi["Aines_nimi"]) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer>
    2025 Drinkkiarkisto
</footer>

</body>
</html>