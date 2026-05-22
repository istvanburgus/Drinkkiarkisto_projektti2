<?php
// Ladataan tietokantayhteys

require_once "db.php";

// Käynnistetään istunto, jotta voidaan käyttää sessiomuuttujia

session_start();

// Luetaan ja poistetaan heti session virhe- ja onnistumisviestit

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Jos käyttäjä on painanut kirjaudu ulos -painiketta, ohjataan kirjautumissivulle ja lopetetaan suoritus

if (isset($_POST['logout'])) {
    header("Location: login.php");
    return;
}

// Haetaan hakusana GET-parametrista, oletuksena tyhjä merkkijono

$hakusana = $_GET['hakusana'] ?? '';
$tulokset = [];

// Jos hakukenttä on tyhjä, rakennetaan kysely kaikkien hyväksyttyjen drinkkien hakemiseksi ainesosineen

if (empty($hakusana)) {

    
    // DISTINCT estää rivien toistumisen, kun drinkillä on useita ainesosia.
    // Yhdistetään: resepti → ainesosat (ainesosamäärät) → aines (ainesosien nimet).
    // Alikyselyllä suodatetaan vain ne drinkit, joiden nimi täsmää ja jotka on hyväksytty.

    $sql = ("SELECT DISTINCT resepti.Drinkkin_ID, resepti.Nimi, resepti.Kuvaus, resepti.Juomalaji, aines.Aines_Nimi, ainesosa.Maara 
        FROM resepti
        JOIN ainesosat ON resepti.Drinkkin_ID = ainesosat.Drinkkin_ID
        JOIN aines ON ainesosat.Aines_ID = aines.Aines_ID
        WHERE resepti.Drinkkin_ID IN (
            SELECT resepti.Drinkkin_ID
            FROM resepti
            WHERE resepti.Nimi LIKE ?
            AND resepti.Hyväksytty = 1
        )
        AND resepti.Hyväksytty = 1");
} else {

    // Jos hakusana on annettu, tässä haarassa pitäisi suorittaa suodatettu kysely — tällä hetkellä vain placeholder

    echo "Ei ole drinkki";
}
?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <title>Reseptihaku</title>
    <link rel="stylesheet" href="drinkkinstyle.css">
</head>

<body>
    <!-- Ylänavigointipalkki -->
    <div id="bar">
        <div>Drinkkihaku</div>
        <a id="login_button" href="index.php">Etusivu</a>
    </div>

    <main>
        <div class="container">

            <!-- Hakulomake — lähettää POST-metodilla itselleen, säilyttäen hakusanan kentässä -->

            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <p><input type="text" name="hakusana" placeholder="Etsi resepti..." value="<?= htmlspecialchars($hakusana) ?>"></p>
                <button type="submit">Hae</button>
            </form>

            <div class="ingredient-list">

                <!-- Näytetään otsikko sen mukaan, onko haku aktiivinen -->

                <?php if (!empty($hakusana)): ?>
                    <h2>Hakutulokset haulle: "<?= htmlspecialchars($hakusana) ?>"</h2>
                <?php else: ?>
                    <h2>Kaikki drinkit:</h2>
                <?php endif; ?>

                <!-- Jos tuloksia löytyi, käydään ne läpi; muuten näytetään "ei löytynyt" -viesti -->

                <?php if (count($tulokset) > 0): ?>
                    <?php foreach ($tulokset as $r): ?>
                        <p>
                            <!-- Drinkin nimi -->
                            <strong><?= htmlspecialchars($r['Nimi']) ?></strong><br>

                            <!-- Kuvaus säilyttäen rivinvaihdot -->
                            <?= nl2br(htmlspecialchars($r['Kuvaus'])) ?><br>

                            <!-- Linkki koko reseptisivulle -->
                            <a href="resepti.php?id=<?= $r['Drinkkin_ID'] ?>">Näytä resepti</a>
                        </p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Ei löytynyt yhtään reseptiä.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        2025 Drinkkinarkisto
    </footer>
</body>

</html>
