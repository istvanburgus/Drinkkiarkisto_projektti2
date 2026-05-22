<?php
// Betöltjük az adatbázis kapcsolat beállításait
require_once "db.php";

// Aloitetaan sessio ja tarkistetaan että käyttäjä on kirjautunut sisään adminina
session_start();

// Jos sessiomuuttujat puuttuvat tai käyttäjä ei ole admin, ohjataan kirjautumissivulle
if (!isset($_SESSION['name']) || !isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: login.php");
    exit();
}

try {
    // Käsitellään hyväksyntätoiminto ennen sivun renderöintiä, jotta uudelleenohjaus toimii
    if (isset($_POST['hyvaksy'])) {

        // Luetaan drinkin ID piilotetusta lomakekentästä; null jos puuttuu
        $id = $_POST['drinkkiID'] ?? null;

        if ($id) {
            // Asetetaan Hyvaksytty = 1 (hyväksytty) tälle drinkille
            $sql = "UPDATE Resepti SET Hyvaksytty = 1 WHERE Drinkkin_ID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $_SESSION['success'] = "Resepti hyväksytty.";
        }

        // Ohjataan takaisin tälle sivulle (PRG-malli — estää uudelleenlähetyksen päivityksessä)
        header("Location: hyvaksy.php");
        exit();
    }

    // Käsitellään hylkäystoiminto — poistetaan vain hyväksymättömät reseptit turvallisuuden vuoksi
    if (isset($_POST['hylkaa'])) {
        $id = $_POST['drinkkiID'] ?? null;

        if ($id) {
            // AND Hyvaksytty = 0 -ehto estää jo hyväksytyn reseptin vahingollisen poistamisen
            $sql = "DELETE FROM Resepti WHERE Drinkkin_ID = ? AND Hyvaksytty = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $_SESSION['success'] = "Resepti hylätty ja poistettu.";
        }

        header("Location: hyvaksy.php");
        exit();
    }

    // Haetaan kaikki reseptit jotka odottavat hyväksyntää (Hyvaksytty = 0), liittäen lisääjän käyttäjänimi
    $sql = "SELECT Resepti.Drinkkin_ID, Resepti.Nimi, Resepti.Kuvaus,
                   Resepti.Juomalaji,
                   Kayttaja.Kayttajanimi
            FROM Resepti
            JOIN Kayttaja ON Resepti.Kayttaja_ID = Kayttaja.Kayttaja_ID
            WHERE Resepti.Hyvaksytty = 0";

    $tulos = $pdo->query($sql);

    // Haetaan kaikki rivit kerralla taulukkoon
    $rivit = $tulos->fetchAll(PDO::FETCH_ASSOC);

    // Haetaan ainesosat erikseen jokaiselle reseptille erillisellä kyselyllä
    foreach ($rivit as &$rivi) {
        $sqlAinekset = "SELECT Aines_ID, Maara FROM Ainesosa WHERE Drinkkin_ID = ?";
        $stmtAinekset = $pdo->prepare($sqlAinekset);
        $stmtAinekset->execute([$rivi['Drinkkin_ID']]);
        $rivi['Ainesosat'] = $stmtAinekset->fetchAll(PDO::FETCH_ASSOC);
    }

    // Puretaan viitemuuttuja foreach-silmukan jälkeen — välttämätöntä hiljaisilta virheiltä suojautumiseen
    unset($rivi);
} catch (PDOException $e) {
    // Otetaan kiinni tietokantavirheet ja pysäytetään suoritus viestillä (tuotannossa kannattaa kirjata lokiin)
    die("Tietokantavirhe: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <title>Drinkkien hyväksyntä</title>
    <link rel="stylesheet" href="drinkkinstyle.css">
</head>

<body>
    <!-- Sivun otsikko -->
    <header>Hyväksy tai hylkää drinkkiehdotuksia</header>

    <main>
        <div class="container">

            <!-- Näytetään onnistumisviesti sessiosta jos sellainen on, sitten poistetaan se -->
            <?php if (!empty($_SESSION['success'])) : ?>
                <div class="success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (!empty($rivit)) : ?>
                <?php foreach ($rivit as $drinkki) : ?>
                    <div class="drinkki-kortti">
                        <hr>
                        <!-- Drinkin nimi kortin otsikkona -->
                        <p><strong>- <?= htmlspecialchars($drinkki['Nimi']) ?> -</strong></p>
                        <br>
                        <p><strong>Nimi:</strong><br><?= htmlspecialchars($drinkki['Nimi']) ?></p><br>
                        <p><strong>Juomalaji:</strong><br><?= htmlspecialchars($drinkki['Juomalaji'] ?? '-') ?></p><br>
                        <p><strong>Kuvaus:</strong><br><?= htmlspecialchars($drinkki['Kuvaus']) ?></p><br>

                        <!-- Drinkin lisänneen käyttäjän nimi -->
                        <p><strong>Lisääjä:</strong><br><?= htmlspecialchars($drinkki['Kayttajanimi']) ?></p><br>

                        <p><strong>Ainesosat:</strong></p>

                        <!-- Listataan jokainen ainesosa ID ja määrä; näytetään varaviesti jos ei ole -->
                        <?php if (!empty($drinkki['Ainesosat'])) : ?>
                            <?php foreach ($drinkki['Ainesosat'] as $aines) : ?>
                                <p><?= htmlspecialchars($aines['Aines_ID']) ?> – <?= htmlspecialchars($aines['Maara']) ?></p>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>Ei ainesosia.</p>
                        <?php endif; ?>

                        <!-- Jokaisella kortilla on oma lomakkeensa; piilotettu kenttä välittää drinkin ID:n ylläolevalle käsittelijälle -->
                        <form method="POST">
                            <input type="hidden" name="drinkkiID" value="<?= htmlspecialchars($drinkki['Drinkkin_ID']) ?>">
                            <br>
                            <button type="submit" class="hyvaksyyButton" name="hyvaksy">Hyväksy</button>
                            <button type="submit" class="hylkaaButton" name="hylkaa">Hylkää</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <!-- Ei odottavia reseptejä tarkistettavana -->
                <p>Ei hyväksymistä odottavia drinkkejä.</p>
            <?php endif; ?>

        </div>
    </main>

    <!-- Alatunniste -->
    <footer>
        2025 Drinkkiarkisto
    </footer>
</body>

</html>
