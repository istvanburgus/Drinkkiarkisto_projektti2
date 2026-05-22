<?php
// Ladataan tietokantayhteyden asetukset
include 'db.php';

// Aloitetaan sessio käyttäjätietojen tallentamista varten
session_start();

// Haetaan mahdollinen virheviesti sessiosta, ja tyhjennetään se sen jälkeen
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

// Jos käyttäjä on jo kirjautunut, ohjataan oikealle sivulle
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
    // Muodostetaan yhteys MySQL-tietokantaan PDO:n avulla
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

    // Asetetaan PDO heittämään poikkeuksia virheiden sattuessa
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tarkistetaan, onko kyseessä POST-pyyntö (lomakkeen lähetys)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Haetaan ja siivotaan käyttäjän syöttämä käyttäjätunnus ja salasana
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';

        // Tarkistetaan, että molemmat kentät on täytetty
        if (strlen($user) === 0 || strlen($pass) === 0) {
            $_SESSION['error'] = "Username and password are required";

            // Ohjataan käyttäjä takaisin kirjautumissivulle
            header("Location: login.php");
            exit();
        }

        // Haetaan käyttäjä ja rooli tietokannasta käyttäjätunnuksen perusteella
        $sql = "SELECT Kayttajanimi, Salasana, Rooli FROM Kayttaja WHERE Kayttajanimi = :username LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $user]);
        $check = $stmt->fetch(PDO::FETCH_ASSOC);

        // Tarkistetaan löytyikö käyttäjä ja onko salasana oikein
        if ($check && password_verify($pass, $check['Salasana'])) {

            // Tallennetaan käyttäjätunnus ja rooli sessioon
            $_SESSION['name'] = $check['Kayttajanimi'];
            $_SESSION['role'] = $check['Rooli'];

            // Ohjataan käyttäjä rooliin perustuen oikealle sivulle
            if ($_SESSION['role'] == 1) {
                header("Location: naviAdmin.php");
            } else {
                header("Location: naviUser.php");
            }
            exit();
        } else {

            // Väärä tunnus tai salasana – ohjataan takaisin virheviestin kera
            $_SESSION['error'] = "Incorrect username or password";
            header("Location: login.php");
            exit();
        }
    }
} catch (PDOException $e) {
    // Tietokantayhteys epäonnistui – näytetään virheilmoitus
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
    <!-- Yläpalkki sivuston nimellä ja rekisteröitymislinkillä -->
    <div id="bar">
        <div>Drinkkinarkisto</div>
        <a href="register.php" id="login_button">Rekisteröidy</a>
    </div>

    <main>
        <!-- Kirjautumislomakkeen säiliö -->
        <div id="loginbar">

            <!-- Näytetään virheviesti, jos sellainen on olemassa -->
            <?php if (!empty($error)) : ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Kirjautumislomake, lähetetään samalle sivulle POST-metodilla -->
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

                <!-- Käyttäjätunnus-kenttä -->
                <input type="text" id="username" name="username" required placeholder="Käyttäjätunnus" />

                <!-- Salasana-kenttä -->
                <input type="password" id="password" name="password" required placeholder="Salasana" />

                <!-- Lähetä-painike -->
                <button type="submit">Kirjaudu sisään</button>

            </form>
        </div>
    </main>


    <footer>
        2025 Drinkkinarkisto
    </footer>
</body>

</html>
