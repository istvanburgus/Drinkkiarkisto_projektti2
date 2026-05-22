<?php

// TARKISTAA TÄMÄ TIEDOSTO / KOKO KOODI, ONKS SE TOIMII...


$yhteys = new PDO("mysql:host=localhost;dbname=drinkkiarkisto;charset=utf8", "root", "");
$viesti = "";

// Törlés
if (isset($_GET['poista'])) {
    $id = (int)$_GET['poista'];
    $sql = "DELETE FROM Resepti WHERE Drinkkin_ID = ?";
    $stmt = $yhteys->prepare($sql);
    if ($stmt->execute([$id])) {
        $viesti = "Drinkki poistettu onnistuneesti.";
    } else {
        $viesti = "Virhe poistettaessa.";
    }
}

// Receptlista
$sql = "SELECT Drinkkin_ID, Nimi FROM Resepti";
$stmt = $yhteys->query($sql);
$reseptit = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <title>Poista resepti</title>
    <link rel="stylesheet" href="drinkkinstyle.css">
</head>

<body>

    <header>
        <h1>Drinkkinarkisto</h1>
        <nav>
            <a href="register.php">Etusivu</a> |
            <a href="poisto.php">Poista resepti</a> |
            <a href="ehdotus.php">Ehdota resepti</a>
        </nav>
    </header>

    <main>
        <h2>Poista resepti</h2>

        <?php if ($viesti): ?>
            <p class="info"><?= htmlspecialchars($viesti) ?></p>
        <?php endif; ?>

        <?php if (count($reseptit) > 0): ?>
            <ul>
                <?php foreach ($reseptit as $resepti): ?>
                    <li>
                        <?= htmlspecialchars($resepti['Nimi']) ?>
                        <form method="get" style="display:inline;" onsubmit="return confirm('Haluatko varmasti poistaa tämän reseptin?');">
                            <input type="hidden" name="poista" value="<?= $resepti['Drinkkin_ID'] ?>">
                            <button type="submit">Poista</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Ei reseptejä poistettavaksi.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>2025 Drinkkinarkisto</p>
    </footer>

</body>

</html>