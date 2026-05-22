<?php
// ENG: Load the database connection
// FI: Ladataan tietokantayhteys
// HU: Betöltjük az adatbázis kapcsolatot
include "db.php";

// ENG: Start the session to access flash messages and user data
// FI: Aloitetaan sessio flash-viestien ja käyttäjätietojen käyttämiseksi
// HU: Elindítjuk a munkamenetet a flash üzenetek és felhasználói adatok eléréséhez
session_start();

// ENG: Read and immediately clear flash messages from the session
// FI: Luetaan ja poistetaan heti flash-viestit sessiosta
// HU: Beolvassuk és azonnal töröljük a flash üzeneteket a sessionből
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// ENG: If the logout button was pressed, redirect to login and stop execution
// FI: Jos kirjaudu ulos -painiketta painettiin, ohjataan kirjautumissivulle ja lopetetaan
// HU: Ha a kijelentkezés gombra kattintottak, átirányítjuk a bejelentkezési oldalra és leállítjuk
if (isset($_POST['logout'])) {
    header("Location: login.php");
    return;
}

// ENG: Only process the form if the request method is POST
// FI: Käsitellään lomake vain jos pyyntömetodi on POST
// HU: Csak akkor dolgozzuk fel az űrlapot, ha a kérés metódusa POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ENG: Trim whitespace from all submitted text fields
    // FI: Poistetaan välilyönnit kaikista lähetetyistä tekstikentistä
    // HU: Eltávolítjuk a szóközöket az összes beküldött szövegmezőből
    $nimi      = trim($_POST["nimi"]);
    $kuvaus    = trim($_POST["kuvaus"]);
    $juomalaji = trim($_POST["juomalaji"]);

    // ENG: These are arrays — one entry per ingredient row in the form
    // FI: Nämä ovat taulukoita — yksi merkintä per ainesosarivi lomakkeessa
    // HU: Ezek tömbök — egy bejegyzés minden összetevő sorhoz az űrlapon
    $ainesosat = $_POST["ainesosa"];
    $maarat    = $_POST["maara"];

    // --- ENG: Validation | FI: Validointi | HU: Validálás ---

    // ENG: Name must be at least 3 characters
    // FI: Nimen tulee olla vähintään 3 merkkiä
    // HU: A névnek legalább 3 karakternek kell lennie
    if (strlen($nimi) < 3) {
        $_SESSION['error'] = "Nimi tarvitaan (väh. 3 merkkiä)";
        header("Location: lisays.php");
        exit;
    }

    // ENG: Description is required
    // FI: Kuvaus on pakollinen
    // HU: A leírás kötelező
    if (strlen($kuvaus) < 1) {
        $_SESSION['error'] = "Kuvaus tarvitaan";
        header("Location: lisays.php");
        exit;
    }

    // ENG: Drink type is required
    // FI: Juomalaji on pakollinen
    // HU: Az italtípus kötelező
    if (strlen($juomalaji) < 1) {
        $_SESSION['error'] = "Juomalaji tarvitaan";
        header("Location: lisays.php");
        exit;
    }

    // ENG: At least the first ingredient slot must be filled
    // FI: Vähintään ensimmäinen ainesosa on täytettävä
    // HU: Legalább az első összetevő mezőt ki kell tölteni
    if (empty($ainesosat[0])) {
        $_SESSION['error'] = "Vähintään yksi ainesosa tarvitaan";
        header("Location: lisays.php");
        exit;
    }

    // --- ENG: Save to database | FI: Tallennus tietokantaan | HU: Mentés az adatbázisba ---

    try {

        // ENG: Insert the recipe record — NOTE: user ID is hardcoded as 1, should use $_SESSION['id'] in production
        // FI: Lisätään reseptirivi — HUOM: käyttäjä ID on kovakoodattu arvoksi 1, tuotannossa tulisi käyttää $_SESSION['id']
        // HU: Beillesztjük a receptet — MEGJEGYZÉS: a felhasználó ID keményen 1-re van kódolva, élesben $_SESSION['id'] kellene
        $sql  = "INSERT INTO resepti (kayttaja_id, nimi, kuvaus, juomalaji) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([1, $nimi, $kuvaus, $juomalaji]);

        // ENG: Retrieve the auto-generated ID of the recipe we just inserted
        // FI: Haetaan juuri lisätyn reseptin automaattisesti generoitu ID
        // HU: Lekérjük az imént beillesztett recept automatikusan generált ID-ját
        $drinkkin_id = $pdo->lastInsertId();

        // ENG: Loop through each submitted ingredient row
        // FI: Käydään läpi jokainen lähetetty ainesosarivi
        // HU: Végigmegyünk minden beküldött összetevő soron
        for ($i = 0; $i < count($ainesosat); $i++) {

            $ainesosa = trim($ainesosat[$i]);
            $maara    = trim($maarat[$i]);

            // ENG: Skip the row entirely if the ingredient name is empty
            // FI: Ohitetaan rivi kokonaan jos ainesosan nimi on tyhjä
            // HU: Kihagyjuk a sort teljesen ha az összetevő neve üres
            if (empty($ainesosa)) {
                continue;
            }

            // ENG: Insert the ingredient name into the aines table — NOTE: this creates a new row every time,
            //      even for duplicate ingredient names; consider checking for an existing match first
            // FI: Lisätään ainesosan nimi aines-tauluun — HUOM: tämä luo uuden rivin joka kerta,
            //     myös duplikaateille; harkitse olemassa olevan tarkistamista ensin
            // HU: Beillesztjük az összetevő nevét az aines táblába — MEGJEGYZÉS: ez minden alkalommal
            //     új sort hoz létre, még duplikátumoknak is; érdemes előbb meglévőt keresni
            $stmt = $pdo->prepare("INSERT INTO aines (Aines_Nimi) VALUES (?)");
            $stmt->execute([$ainesosa]);

            // ENG: Get the ID of the ingredient we just inserted
            // FI: Haetaan juuri lisätyn ainesosan ID
            // HU: Lekérjük az imént beillesztett összetevő ID-ját
            $ainesId = $pdo->lastInsertId();

            // ENG: Insert a row into ainesosa to link this ingredient to the recipe with its amount
            // FI: Lisätään rivi ainesosa-tauluun yhdistämään tämä ainesosa reseptiin määrällä
            // HU: Beillesztünk egy sort az ainesosa táblába, amely összekapcsolja az összetevőt a recepttel és a mennyiséggel
            $stmt = $pdo->prepare("INSERT INTO ainesosa (drinkkin_id, Aines_ID, Maara) VALUES (?, ?, ?)");
            $stmt->execute([$drinkkin_id, $ainesId, $maara]);
        }

        // ENG: All inserts succeeded — set success flash and redirect (PRG pattern)
        // FI: Kaikki lisäykset onnistuivat — asetetaan onnistumisviesti ja ohjataan (PRG-malli)
        // HU: Minden beillesztés sikerült — beállítjuk a sikeres flash üzenetet és átirányítjuk (PRG minta)
        $_SESSION['success'] = "Drinkki lisätty!";
        header("Location: lisays.php");
        exit;
    } catch (PDOException $e) {
        // ENG: Something went wrong with the database — store error and redirect back
        // FI: Tietokantavirhe tapahtui — tallennetaan virhe ja ohjataan takaisin
        // HU: Adatbázis hiba történt — elmentjük a hibát és visszairányítjuk
        $_SESSION['error'] = "Tietokantavirhe: " . $e->getMessage();
        header("Location: lisays.php");
        exit;
    }
}
?>



<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Drinkkinarkisto | Lisää resepti</title>
    <link rel="stylesheet" href="drinkkinstyle.css" />
</head>

<body>

    <!-- ENG: Top navigation bar | FI: Ylänavigointipalkki | HU: Felső navigációs sáv -->
    <div id="bar">
        <div>Drinkkinarkisto</div>
        <a href="login.php" id="login_button">login</a>
        <a href="logout.php" id="login_button">logout</a>
    </div>

    <main>
        <div class="container">
            <header>Lisää uusi resepti</header>

            <!-- ENG: Show error flash message if one exists | FI: Näytetään virheviesti jos sellainen on | HU: Megjelenítjük a hibaüzenetet ha van -->
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>

            <!-- ENG: Show success flash message if one exists | FI: Näytetään onnistumisviesti jos sellainen on | HU: Megjelenítjük a sikeres üzenetet ha van -->
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>

            <!-- ENG: Recipe submission form — posts to itself | FI: Reseptin lähetyslomake — lähettää itselleen | HU: Recept beküldő űrlap — önmagára küld -->
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

                <label for="nimi">Nimi</label><br>
                <input type="text" id="nimi" name="nimi"><br><br>

                <label for="juomalaji">Juomalaji</label><br>
                <input type="text" id="juomalaji" name="juomalaji"><br><br>

                <label>Ainesosat</label><br>

                <!-- ENG: Ingredient rows — each pair of inputs becomes one index in the ainesosa[] and maara[] arrays
                     FI: Ainesosarivit — jokainen syötepari muodostaa yhden indeksin ainesosa[]- ja maara[]-taulukoihin
                     HU: Összetevő sorok — minden pár egy-egy indexet alkot az ainesosa[] és maara[] tömbökben
                     
                     NOTE ENG: There are currently TWO ainesosa[] inputs in this one div, which means index [0] and [1]
                               are both inside the same visual row — this is likely a copy-paste bug
                     NOTE FI:  Tässä divissä on tällä hetkellä KAKSI ainesosa[]-kenttää, joten indeksit [0] ja [1]
                               ovat molemmat samassa visuaalisessa rivissä — tämä on todennäköisesti kopiointivirhe
                     NOTE HU:  Jelenleg KÉT ainesosa[] mező van ebben a divben, így a [0] és [1] index
                               ugyanabban a vizuális sorban van — ez valószínűleg egy másolási hiba -->
                <div id="ainesosat-lista">
                    <div class="ainesosa-rivi">
                        <input type="text" name="ainesosa[]" placeholder="Ainesosa">
                        <input type="text" name="maara[]" placeholder="Määrä">
                        <input type="text" name="ainesosa[]" placeholder="Ainesosa">
                        <input type="text" name="maara[]" placeholder="Määrä">
                    </div>
                </div>
                

                <label for="kuvaus">Ohjeet / Kuvaus</label><br>
                <textarea name="kuvaus" id="kuvaus" rows="8" cols="40" placeholder="Kuvaus"></textarea><br><br>

                <button type="submit" name="lisaa" class="submit-btn">Lisää resepti</button>

            </form>
        </div>
            <!-- LAITTAA JAVASCRIPT JA PHP KOODI ETTÄ AINESOSAN LISAAMINEN "+" NAPPAIN ON JA TOIMII-->

            
    </main>

    <!-- ENG: Footer | FI: Alatunniste | HU: Lábléc -->
    <footer>
        2025 Drinkkinarkisto
    </footer>

</body>

</html>