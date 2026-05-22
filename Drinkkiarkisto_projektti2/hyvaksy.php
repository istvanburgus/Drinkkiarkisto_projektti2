<?php
// ENG: Load the database connection settings
// FI: Ladataan tietokantayhteyden asetukset
// HU: Betöltjük az adatbázis kapcsolat beállításait
require_once "db.php";

// ENG: Start the session and verify the user is logged in as an admin (role = 1)
// FI: Aloitetaan sessio ja tarkistetaan että käyttäjä on kirjautunut sisään adminina
// HU: Elindítjuk a munkamenetet és ellenőrizzük, hogy a felhasználó admin-ként van-e bejelentkezve
session_start();

// ENG: If session variables are missing or the user is not an admin, redirect to login and stop
// FI: Jos sessiomuuttujat puuttuvat tai käyttäjä ei ole admin, ohjataan kirjautumissivulle
// HU: Ha a session változók hiányoznak vagy a felhasználó nem admin, átirányítjuk a bejelentkezésre
if (!isset($_SESSION['name']) || !isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: login.php");
    exit();
}

try {
    // ENG: Handle the approve action — runs before any HTML is rendered so the header redirect works
    // FI: Käsitellään hyväksyntätoiminto ennen sivun renderöintiä, jotta uudelleenohjaus toimii
    // HU: Feldolgozzuk a jóváhagyási műveletet az oldal megjelenítése előtt, hogy az átirányítás működjön
    if (isset($_POST['hyvaksy'])) {

        // ENG: Read the drink ID from the hidden form field; null if missing
        // FI: Luetaan drinkin ID piilotetusta lomakekentästä; null jos puuttuu
        // HU: Beolvassuk a drink ID-t a rejtett mezőből; null ha hiányzik
        $id = $_POST['drinkkiID'] ?? null;

        if ($id) {
            // ENG: Set Hyvaksytty = 1 (approved) for this specific drink
            // FI: Asetetaan Hyvaksytty = 1 (hyväksytty) tälle drinkille
            // HU: Beállítjuk a Hyvaksytty = 1 (jóváhagyott) értéket ennél a drinknél
            $sql = "UPDATE Resepti SET Hyvaksytty = 1 WHERE Drinkkin_ID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $_SESSION['success'] = "Resepti hyväksytty.";
        }

        // ENG: Redirect back to this page (PRG pattern — prevents re-submission on refresh)
        // FI: Ohjataan takaisin tälle sivulle (PRG-malli — estää uudelleenlähetyksen päivityksessä)
        // HU: Visszairányítjuk erre az oldalra (PRG minta — megakadályozza az újraküldést frissítéskor)
        header("Location: hyvaksy.php");
        exit();
    }

    // ENG: Handle the reject action — only deletes drinks that are still unapproved, as a safety guard
    // FI: Käsitellään hylkäystoiminto — poistetaan vain hyväksymättömät reseptit turvallisuuden vuoksi
    // HU: Feldolgozzuk az elutasítási műveletet — biztonsági okokból csak a nem jóváhagyottakat töröljük
    if (isset($_POST['hylkaa'])) {
        $id = $_POST['drinkkiID'] ?? null;

        if ($id) {
            // ENG: The AND Hyvaksytty = 0 condition prevents accidentally deleting an already-approved drink
            // FI: AND Hyvaksytty = 0 -ehto estää jo hyväksytyn reseptin vahingollisen poistamisen
            // HU: Az AND Hyvaksytty = 0 feltétel megakadályozza a már jóváhagyott recept véletlen törlését
            $sql = "DELETE FROM Resepti WHERE Drinkkin_ID = ? AND Hyvaksytty = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $_SESSION['success'] = "Resepti hylätty ja poistettu.";
        }

        header("Location: hyvaksy.php");
        exit();
    }

    // ENG: Fetch all recipes that are pending approval (Hyvaksytty = 0), joined with the submitter's username
    // FI: Haetaan kaikki reseptit jotka odottavat hyväksyntää (Hyvaksytty = 0), liittäen lisääjän käyttäjänimi
    // HU: Lekérjük az összes jóváhagyásra váró receptet (Hyvaksytty = 0), csatolva a beküldő felhasználónevét
    $sql = "SELECT Resepti.Drinkkin_ID, Resepti.Nimi, Resepti.Kuvaus,
                   Resepti.Juomalaji,
                   Kayttaja.Kayttajanimi
            FROM Resepti
            JOIN Kayttaja ON Resepti.Kayttaja_ID = Kayttaja.Kayttaja_ID
            WHERE Resepti.Hyvaksytty = 0";

    $tulos = $pdo->query($sql);

    // ENG: Load all rows into an array at once
    // FI: Haetaan kaikki rivit kerralla taulukkoon
    // HU: Egyszerre betöltjük az összes sort egy tömbbe
    $rivit = $tulos->fetchAll(PDO::FETCH_ASSOC);

    // ENG: For each recipe, run a second query to fetch its ingredients separately
    // FI: Haetaan ainesosat erikseen jokaiselle reseptille erillisellä kyselyllä
    // HU: Minden recepthez külön lekérdezéssel lekérjük az összetevőket
    foreach ($rivit as &$rivi) {
        $sqlAinekset = "SELECT Aines_ID, Maara FROM Ainesosa WHERE Drinkkin_ID = ?";
        $stmtAinekset = $pdo->prepare($sqlAinekset);
        $stmtAinekset->execute([$rivi['Drinkkin_ID']]);
        $rivi['Ainesosat'] = $stmtAinekset->fetchAll(PDO::FETCH_ASSOC);
    }

    // ENG: Unset the reference variable after a foreach-by-reference loop — required to avoid silent bugs
    // FI: Puretaan viitemuuttuja foreach-silmukan jälkeen — välttämätöntä hiljaisilta virheiltä suojautumiseen
    // HU: Felszabadítjuk a referencia változót a foreach ciklus után — szükséges a rejtett hibák elkerüléséhez
    unset($rivi);
} catch (PDOException $e) {
    // ENG: Catch any database errors and halt with a message (consider logging instead of echoing in production)
    // FI: Otetaan kiinni tietokantavirheet ja pysäytetään suoritus viestillä (tuotannossa kannattaa kirjata lokiin)
    // HU: Elkapjuk az adatbázis hibákat és leállítjuk a futást üzenettel (élesben inkább logolni érdemes)
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
    <!-- ENG: Page heading | FI: Sivun otsikko | HU: Az oldal fejléce -->
    <header>Hyväksy tai hylkää drinkkiehdotuksia</header>

    <main>
        <div class="container">

            <!-- ENG: Show flash success message if one exists in session, then clear it
                 FI: Näytetään onnistumisviesti sessiosta jos sellainen on, sitten poistetaan se
                 HU: Megjelenítjük a sikeres flash üzenetet ha van a sessionben, majd töröljük -->
            <?php if (!empty($_SESSION['success'])) : ?>
                <div class="success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (!empty($rivit)) : ?>
                <?php foreach ($rivit as $drinkki) : ?>
                    <div class="drinkki-kortti">
                        <hr>
                        <!-- ENG: Drink name used as a card title | FI: Drinkin nimi kortin otsikkona | HU: A drink neve kártyacímként -->
                        <p><strong>- <?= htmlspecialchars($drinkki['Nimi']) ?> -</strong></p>
                        <br>
                        <p><strong>Nimi:</strong><br><?= htmlspecialchars($drinkki['Nimi']) ?></p><br>
                        <p><strong>Juomalaji:</strong><br><?= htmlspecialchars($drinkki['Juomalaji'] ?? '-') ?></p><br>
                        <p><strong>Kuvaus:</strong><br><?= htmlspecialchars($drinkki['Kuvaus']) ?></p><br>

                        <!-- ENG: Username of whoever submitted this drink | FI: Drinkin lisänneen käyttäjän nimi | HU: A drinket beküldő felhasználó neve -->
                        <p><strong>Lisääjä:</strong><br><?= htmlspecialchars($drinkki['Kayttajanimi']) ?></p><br>

                        <p><strong>Ainesosat:</strong></p>

                        <!-- ENG: List each ingredient ID and its amount; show fallback if none exist
                             FI: Listataan jokainen ainesosa ID ja määrä; näytetään varaviesti jos ei ole
                             HU: Listázzuk az összetevő ID-kat és mennyiségeket; ha nincs, megjelenítünk egy visszajelzést -->
                        <?php if (!empty($drinkki['Ainesosat'])) : ?>
                            <?php foreach ($drinkki['Ainesosat'] as $aines) : ?>
                                <p><?= htmlspecialchars($aines['Aines_ID']) ?> – <?= htmlspecialchars($aines['Maara']) ?></p>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>Ei ainesosia.</p>
                        <?php endif; ?>

                        <!-- ENG: Each card has its own form; the hidden field carries the drink ID to the POST handler above
                             FI: Jokaisella kortilla on oma lomakkeensa; piilotettu kenttä välittää drinkin ID:n ylläolevalle käsittelijälle
                             HU: Minden kártyának saját űrlapja van; a rejtett mező továbbítja a drink ID-t a fenti kezelőnek -->
                        <form method="POST">
                            <input type="hidden" name="drinkkiID" value="<?= htmlspecialchars($drinkki['Drinkkin_ID']) ?>">
                            <br>
                            <button type="submit" class="hyvaksyyButton" name="hyvaksy">Hyväksy</button>
                            <button type="submit" class="hylkaaButton" name="hylkaa">Hylkää</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <!-- ENG: No pending recipes to review | FI: Ei odottavia reseptejä tarkistettavana | HU: Nincsenek függőben lévő receptek -->
                <p>Ei hyväksymistä odottavia drinkkejä.</p>
            <?php endif; ?>

        </div>
    </main>

    <!-- ENG: Footer | FI: Alatunniste | HU: Lábléc -->
    <footer>
        2025 Drinkkiarkisto
    </footer>
</body>

</html>