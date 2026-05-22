<?php
// Ladataan tietokantayhteys
include "db.php";

// Aloitetaan sessio flash-viestien ja käyttäjätietojen käyttämiseksi
session_start();

// Luetaan ja poistetaan heti flash-viestit sessiosta
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Jos kirjaudu ulos -painiketta painettiin, ohjataan kirjautumissivulle ja lopetetaan
if (isset($_POST['logout'])) {
    header("Location: login.php");
    return;
}

// Käsitellään lomake vain jos pyyntömetodi on POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Poistetaan välilyönnit kaikista lähetetyistä tekstikentistä
    $nimi      = trim($_POST["nimi"]);
    $kuvaus    = trim($_POST["kuvaus"]);
    $juomalaji = trim($_POST["juomalaji"]);

    // Nämä ovat taulukoita — yksi merkintä per ainesosarivi lomakkeessa
    $ainesosat = $_POST["ainesosa"];
    $maarat    = $_POST["maara"];

    // --- Validointi ---

    // Nimen tulee olla vähintään 3 merkkiä
    if (strlen($nimi) < 3) {
        $_SESSION['error'] = "Nimi tarvitaan (väh. 3 merkkiä)";
        header("Location: lisays.php");
        exit;
    }

    // Kuvaus on pakollinen
    if (strlen($kuvaus) < 1) {
        $_SESSION['error'] = "Kuvaus tarvitaan";
        header("Location: lisays.php");
        exit;
    }

    // Juomalaji on pakollinen
    if (strlen($juomalaji) < 1) {
        $_SESSION['error'] = "Juomalaji tarvitaan";
        header("Location: lisays.php");
        exit;
    }

    // Vähintään ensimmäinen ainesosa on täytettävä
    if (empty($ainesosat[0])) {
        $_SESSION['error'] = "Vähintään yksi ainesosa tarvitaan";
        header("Location: lisays.php");
        exit;
    }

    // --- Tallennus tietokantaan ---

    try {

        // Lisätään reseptirivi — HUOM: käyttäjä ID on kovakoodattu arvoksi 1, tuotannossa tulisi käyttää $_SESSION['id']
        $sql  = "INSERT INTO resepti (kayttaja_id, nimi, kuvaus, juomalaji) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([1, $nimi, $kuvaus, $juomalaji]);

        // Haetaan juuri lisätyn reseptin automaattisesti generoitu ID
        $drinkkin_id = $pdo->lastInsertId();

        // Käydään läpi jokainen lähetetty ainesosarivi
        for ($i = 0; $i < count($ainesosat); $i++) {

            $ainesosa = trim($ainesosat[$i]);
            $maara    = trim($maarat[$i]);

            // Ohitetaan rivi kokonaan jos ainesosan nimi on tyhjä
            if (empty($ainesosa)) {
                continue;
            }

            // Lisätään ainesosan nimi aines-tauluun — HUOM: tämä luo uuden rivin joka kerta,
            // myös duplikaateille; harkitse olemassa olevan tarkistamista ensin
            $stmt = $pdo->prepare("INSERT INTO aines (Aines_Nimi) VALUES (?)");
            $stmt->execute([$ainesosa]);

            // Haetaan juuri lisätyn ainesosan ID
            $ainesId = $pdo->lastInsertId();

            // Lisätään rivi ainesosa-tauluun yhdistämään tämä ainesosa reseptiin määrällä
            $stmt = $pdo->prepare("INSERT INTO ainesosa (drinkkin_id, Aines_ID, Maara) VALUES (?, ?, ?)");
            $stmt->execute([$drinkkin_id, $ainesId, $maara]);
        }

        // Kaikki lisäykset onnistuivat — asetetaan onnistumisviesti ja ohjataan (PRG-malli)
        $_SESSION['success'] = "Drinkki lisätty!";
        header("Location: lisays.php");
        exit;
    } catch (PDOException $e) {
        // Tietokantavirhe tapahtui — tallennetaan virhe ja ohjataan takaisin
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

    <!-- Ylänavigointipalkki -->
    <div id="bar">
        <div>Drinkkinarkisto</div>
        <a href="login.php" id="login_button">login</a>
        <a href="logout.php" id="login_button">logout</a>
    </div>

    <main>
        <div class="container">
            <header>Lisää uusi resepti</header>

            <!-- Näytetään virheviesti jos sellainen -->
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>

            <!-- Näytetään onnistumisviesti jos sellainen on -->
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>

            <!-- Reseptin lähetyslomake — lähettää itselleen -->
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

                <label for="nimi">Nimi</label><br>
                <input type="text" id="nimi" name="nimi"><br><br>

                <label for="juomalaji">Juomalaji</label><br>
                <input type="text" id="juomalaji" name="juomalaji"><br><br>

                <label>Ainesosat</label><br>

                <!-- Ainesosarivit — jokainen syötepari muodostaa yhden indeksin ainesosa[]- ja maara[]-taulukoihin-->
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
            

            
    </main>

    
    <footer>
        2025 Drinkkinarkisto
    </footer>

</body>

</html>
