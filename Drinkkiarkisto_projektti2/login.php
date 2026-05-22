<?php
// FI: Ladataan tietokantayhteyden asetukset
// HU: Betöltjük az adatbázis kapcsolat beállításait
include 'db.php';

// FI: Aloitetaan sessio käyttäjätietojen tallentamista varten
// HU: Elindítjuk a munkamenetet a felhasználói adatok tárolásához
session_start();

// FI: Haetaan mahdollinen virheviesti sessiosta, ja tyhjennetään se sen jälkeen
// HU: Lekérjük a esetleges hibaüzenetet a munkamenetből, majd töröljük azt
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

// FI: Jos käyttäjä on jo kirjautunut, ohjataan oikealle sivulle
// HU: Ha a felhasználó már be van jelentkezve, irányítsuk a megfelelő oldalra
if (isset($_SESSION['name'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] == 1) {
        header("Location: naviAdmin.php");
    } else {
        header("Location: naviUser.php");
    }
    exit();
}

if (isset($_POST['logout'])) {
    header("Location: index.php");
    return;
}

try {
    // FI: Muodostetaan yhteys MySQL-tietokantaan PDO:n avulla
    // HU: Kapcsolódunk a MySQL adatbázishoz PDO segítségével
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

    // FI: Asetetaan PDO heittämään poikkeuksia virheiden sattuessa
    // HU: Beállítjuk a PDO-t, hogy kivételeket dobjon hiba esetén
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // FI: Tarkistetaan, onko kyseessä POST-pyyntö (lomakkeen lähetys)
    // HU: Ellenőrizzük, hogy POST kérésről van-e szó (űrlap beküldése)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // FI: Haetaan ja siivotaan käyttäjän syöttämä käyttäjätunnus ja salasana
        // HU: Lekérjük és megtisztítjuk a felhasználó által megadott nevet és jelszót
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';

        // FI: Tarkistetaan, että molemmat kentät on täytetty
        // HU: Ellenőrizzük, hogy mindkét mező ki van-e töltve
        if (strlen($user) === 0 || strlen($pass) === 0) {
            $_SESSION['error'] = "Username and password are required";

            // FI: Ohjataan käyttäjä takaisin kirjautumissivulle
            // HU: Visszairányítjuk a felhasználót a bejelentkezési oldalra
            header("Location: login.php");
            exit();
        }

        // FI: Haetaan käyttäjä ja rooli tietokannasta käyttäjätunnuksen perusteella
        // HU: Lekérjük a felhasználót és szerepkört az adatbázisból a felhasználónév alapján
        $sql = "SELECT Kayttajanimi, Salasana, Rooli FROM Kayttaja WHERE Kayttajanimi = :username LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $user]);
        $check = $stmt->fetch(PDO::FETCH_ASSOC);

        // FI: Tarkistetaan löytyikö käyttäjä ja onko salasana oikein
        // HU: Ellenőrizzük, hogy megtalálható-e a felhasználó és helyes-e a jelszó
        if ($check && password_verify($pass, $check['Salasana'])) {

            // FI: Tallennetaan käyttäjätunnus ja rooli sessioon
            // HU: Eltároljuk a felhasználónevet és szerepkört a munkamenetben
            $_SESSION['name'] = $check['Kayttajanimi'];
            $_SESSION['role'] = $check['Rooli'];

            // FI: Ohjataan käyttäjä rooliin perustuen oikealle sivulle
            // HU: A szerepkör alapján irányítjuk a felhasználót a megfelelő oldalra
            if ($_SESSION['role'] == 1) {
                header("Location: naviAdmin.php");
            } else {
                header("Location: naviUser.php");
            }
            exit();
        } else {

            // FI: Väärä tunnus tai salasana – ohjataan takaisin virheviestin kera
            // HU: Hibás felhasználónév vagy jelszó – visszairányítunk hibaüzenettel
            $_SESSION['error'] = "Incorrect username or password";
            header("Location: login.php");
            exit();
        }
    }
} catch (PDOException $e) {
    // FI: Tietokantayhteys epäonnistui – näytetään virheilmoitus
    // HU: Az adatbázis kapcsolat sikertelen – megjelenítjük a hibaüzenetet
    echo "Connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Drinkkinarkisto | Kirjaudu sisään</title>
    <link rel="stylesheet" href="drinkkinstyle.css" />
</head>

<body>
    <!-- FI: Yläpalkki sivuston nimellä ja rekisteröitymislinkillä -->
    <!-- HU: Fejléc sáv az oldal nevével és a regisztrációs linkkel -->
    <div id="bar">
        <div>Drinkkinarkisto</div>
        <a href="register.php" id="login_button">Rekisteröidy</a>
    </div>

    <main>
        <!-- FI: Kirjautumislomakkeen säiliö -->
        <!-- HU: A bejelentkezési űrlap tárolója -->
        <div id="loginbar">

            <!-- FI: Näytetään virheviesti, jos sellainen on olemassa -->
            <!-- HU: Megjelenítjük a hibaüzenetet, ha van ilyen -->
            <?php if (!empty($error)) : ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- FI: Kirjautumislomake, lähetetään samalle sivulle POST-metodilla -->
            <!-- HU: Bejelentkezési űrlap, POST metódussal ugyanerre az oldalra küldjük -->
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

                <!-- FI: Käyttäjätunnus-kenttä -->
                <!-- HU: Felhasználónév mező -->
                <input type="text" id="username" name="username" required placeholder="Käyttäjätunnus" />

                <!-- FI: Salasana-kenttä -->
                <!-- HU: Jelszó mező -->
                <input type="password" id="password" name="password" required placeholder="Salasana" />

                <!-- FI: Lähetä-painike -->
                <!-- HU: Küldés gomb -->
                <button type="submit">Kirjaudu sisään</button>

            </form>
        </div>
    </main>

    <!-- FI: Alatunniste -->
    <!-- HU: Lábléc -->
    <footer>
        2025 Drinkkinarkisto
    </footer>
</body>

</html>