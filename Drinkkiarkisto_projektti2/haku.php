<?php
// ENG: Include the database connection file
// HU: Az adatbázis kapcsolódási fájl betöltése
// FI: Ladataan tietokantayhteys

require_once "db.php";

// ENG: Start the session to access session variables
// HU: A munkamenet (session) indítása a session változók eléréséhez
// FI: Käynnistetään istunto, jotta voidaan käyttää sessiomuuttujia

session_start();

// ENG: Read and immediately clear any error/success flash messages from the session
// HU: Beolvassuk, majd töröljük a session-ből az esetleges hiba- és sikerüzeneteket
// FI: Luetaan ja poistetaan heti session virhe- ja onnistumisviestit

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// ENG: If the logout button was pressed, redirect to the login page and stop execution
// HU: Ha a kijelentkezés gombra nyomtak, átirányítunk a bejelentkezési oldalra és leállítjuk a végrehajtást
// FI: Jos käyttäjä on painanut kirjaudu ulos -painiketta, ohjataan kirjautumissivulle ja lopetetaan suoritus

if (isset($_POST['logout'])) {
    header("Location: login.php");
    return;
}

// ENG: Get the search keyword from the GET parameter, defaulting to empty string
// HU: Lekérjük a keresőszót a GET paraméterből, alapértelmezetten üres string
// FI: Haetaan hakusana GET-parametrista, oletuksena tyhjä merkkijono

$hakusana = $_GET['hakusana'] ?? '';
$tulokset = [];

// ENG: If the search field is empty, build a query to fetch all approved drinks with their ingredients
// HU: Ha a keresőmező üres, lekérdezzük az összes jóváhagyott drinket a hozzávalóikkal együtt
// FI: Jos hakukenttä on tyhjä, rakennetaan kysely kaikkien hyväksyttyjen drinkkien hakemiseksi ainesosineen

if (empty($hakusana)) {

    // ENG: SELECT with DISTINCT to avoid duplicate rows when a drink has multiple ingredients.
    //      Joins resepti (recipe) → ainesosat (ingredient amounts) → aines (ingredient names).
    //      The subquery filters to only drinks whose name matches the search term AND are approved.
    // HU: DISTINCT a duplikált sorok elkerülésére, ha egy drinknek több hozzávalója van.
    //     Összekapcsoljuk: resepti (recept) → ainesosat (hozzávaló mennyiségek) → aines (hozzávaló nevek).
    //     Az alkérdezés csak azokat a drinkeket szűri, amelyek neve egyezik és jóváhagyottak.
    // FI: DISTINCT estää rivien toistumisen, kun drinkillä on useita ainesosia.
    //     Yhdistetään: resepti → ainesosat (ainesosamäärät) → aines (ainesosien nimet).
    //     Alikyselyllä suodatetaan vain ne drinkit, joiden nimi täsmää ja jotka on hyväksytty.

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

    // ENG: If a search term IS provided, this branch should execute the filtered query — currently just a placeholder echo
    // HU: Ha van keresőszó, itt kellene a szűrt lekérdezést futtatni — jelenleg csak egy helyőrző echo van
    // FI: Jos hakusana on annettu, tässä haarassa pitäisi suorittaa suodatettu kysely — tällä hetkellä vain placeholder

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
    <!-- ENG: Top navigation bar | HU: Felső navigációs sáv | FI: Ylänavigointipalkki -->
    <div id="bar">
        <div>Drinkkihaku</div>
        <a id="login_button" href="index.php">Etusivu</a>
    </div>

    <main>
        <div class="container">

            <!-- ENG: Search form — submits to itself via POST, preserving the current keyword in the input
                 HU: Keresőűrlap — POST metódussal önmagára küldi az adatot, megtartva a keresőszót
                 FI: Hakulomake — lähettää POST-metodilla itselleen, säilyttäen hakusanan kentässä -->

            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <p><input type="text" name="hakusana" placeholder="Etsi resepti..." value="<?= htmlspecialchars($hakusana) ?>"></p>
                <button type="submit">Hae</button>
            </form>

            <div class="ingredient-list">

                <!-- ENG: Show a contextual heading depending on whether a search is active
                     HU: Kontextusfüggő cím megjelenítése attól függően, hogy van-e aktív keresés
                     FI: Näytetään otsikko sen mukaan, onko haku aktiivinen -->

                <?php if (!empty($hakusana)): ?>
                    <h2>Hakutulokset haulle: "<?= htmlspecialchars($hakusana) ?>"</h2>
                <?php else: ?>
                    <h2>Kaikki drinkit:</h2>
                <?php endif; ?>

                <!-- ENG: Loop through results if any exist, otherwise show a "not found" message
                     HU: Ha vannak találatok, végigmegyünk rajtuk, különben "nem található" üzenetet mutatunk
                     FI: Jos tuloksia löytyi, käydään ne läpi; muuten näytetään "ei löytynyt" -viesti -->

                <?php if (count($tulokset) > 0): ?>
                    <?php foreach ($tulokset as $r): ?>
                        <p>
                            <!-- ENG: Drink name | HU: Drink neve | FI: Drinkin nimi -->
                            <strong><?= htmlspecialchars($r['Nimi']) ?></strong><br>

                            <!-- ENG: Description with line breaks preserved | HU: Leírás sortörésekkel | FI: Kuvaus säilyttäen rivinvaihdot -->
                            <?= nl2br(htmlspecialchars($r['Kuvaus'])) ?><br>

                            <!-- ENG: Link to the full recipe page | HU: Link a teljes receptoldalra | FI: Linkki koko reseptisivulle -->
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