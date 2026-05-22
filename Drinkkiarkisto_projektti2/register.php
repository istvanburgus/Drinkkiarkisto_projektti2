<?php

// FI: Ladataan tietokantayhteyden asetukset (sisältää $pdo-olion)
// HU: Betöltjük az adatbázis kapcsolat beállításait (tartalmazza a $pdo objektumot)
include 'db.php';

// FI: Tarkistetaan, onko rekisteröintilomake lähetetty
// HU: Ellenőrizzük, hogy a regisztrációs űrlap be lett-e küldve
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // FI: Varmistetaan, että kaikki vaaditut kentät ovat olemassa POST-datassa
    // HU: Megbizonyosodunk róla, hogy az összes szükséges mező szerepel a POST adatokban
    if (!isset($_POST['username'], $_POST['password'], $_POST['email'])) {
        die("Missing form fields.");
    }

    // FI: Haetaan ja siivotaan lähetetyt arvot
    // HU: Lekérjük és megtisztítjuk a beküldött értékeket
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email    = trim($_POST['email']);

    if ($username === '' || $password === '' || $email === '') {
        // FI: Kaikki kentät täytyy täyttää
        // HU: Minden mezőt ki kell tölteni
        $message = "Täytä kaikki kentät.";
        $isError = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // FI: Sähköpostimuoto on virheellinen
        // HU: Az e-mail formátuma érvénytelen
        $message = "Virheellinen sähköposti.";
        $isError = true;
    } else {

        // FI: Hashataan salasana turvallisesti ennen tallentamista
        // HU: Biztonságosan hash-eljük a jelszót tárolás előtt
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // FI: Tarkistetaan onko käyttäjänimi tai sähköposti jo käytössä
            // HU: Ellenőrizzük, hogy a felhasználónév vagy e-mail már foglalt-e
            $check = $pdo->prepare("SELECT COUNT(*) FROM Kayttaja WHERE Kayttajanimi = ? OR Sahkoposti = ?");
            $check->execute([$username, $email]);

            // FI: rowCount() on luotettavampi tapa tarkistaa duplikaatit
            // HU: A rowCount() megbízhatóbb módszer az ismétlődések ellenőrzésére
            if ($check->fetchColumn() > 0) {
                // FI: Käyttäjänimi tai sähköposti on jo olemassa tietokannassa
                // HU: A felhasználónév vagy e-mail már létezik az adatbázisban
                $message = "Käyttäjänimi tai sähköposti on jo käytössä.";
                $isError = true;
            } else {
                // FI: Lisätään uusi käyttäjä tietokantaan (Rooli 0 = tavallinen käyttäjä)
                // HU: Hozzáadjuk az új felhasználót az adatbázishoz (Rooli 0 = normál felhasználó)
                $sql = "INSERT INTO Kayttaja (Kayttajanimi, Salasana, Sahkoposti, Rooli)
                        VALUES (?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql);
                $ok   = $stmt->execute([$username, $hashed_password, $email, 0]);

                if ($ok) {
                    // FI: Rekisteröinti onnistui - ohjataan pääsivulle
                    // HU: Sikeres regisztráció - átirányítás a főoldalra
                    header("Location: naviGuest.php");
                    exit;
                } else {
                    // FI: Jotain meni vikaan lisäyksen kanssa
                    // HU: Valami hiba történt a beillesztés során
                    $message = "Virhe rekisteröinnissä!";
                    $isError = true;
                }
            }
        } catch (PDOException $e) {
            // FI: Tietokantavirhe - tuotannossa älä näytä yksityiskohtia käyttäjille
            // HU: Adatbázis hiba - éles környezetben ne mutasd a részleteket a felhasználóknak
            $message = "Tietokantavirhe: " . $e->getMessage();
            $isError = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Drinkkinarkisto | Rekisteröidy</title>
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            font-family: Tahoma, sans-serif;
            background-color: #f4f1ec;
        }

        main {
            flex: 1;
        }

        /* FI: Ylänavigointipalkki | HU: Felső navigációs sáv */
        #bar {
            background-color: #5c4033;
            color: #f4f1ec;
            padding: 20px;
            font-size: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* FI: Kirjautumispainike yläpalkissa | HU: Bejelentkezés gomb a fejlécben */
        #login_button {
            background-color: #8b5e3c;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }

        #login_button:hover {
            background-color: #75492f;
        }

        /* FI: Rekisteröintilomakkeen säiliö | HU: Regisztrációs űrlap tárolója */
        #loginbar {
            background-color: #e8d9c5;
            width: 400px;
            margin: 80px auto;
            padding: 30px 40px;
            border: 1px solid #b09b8a;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 0 15px rgba(92, 64, 51, 0.3);
        }

        /* FI: Syötekenttien tyylit | HU: Beviteli mezők stílusai */
        #loginbar input[type="text"],
        #loginbar input[type="password"],
        #loginbar input[type="email"] {
            width: 100%;
            padding: 14px 12px;
            margin-bottom: 16px;
            border: 1px solid #b09b8a;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
            color: #5c4033;
        }

        /* FI: Placeholder-tekstin väri | HU: Placeholder szöveg színe */
        #loginbar input::placeholder {
            color: #a1876e;
        }

        /* FI: Lähetyspainikkeen tyylit | HU: Küldés gomb stílusai */
        #loginbar button {
            width: 100%;
            padding: 16px;
            background-color: #8b5e3c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }

        #loginbar button:hover {
            background-color: #a0734a;
        }

        /* FI: Alatunnisteen tyylit | HU: Lábléc stílusai */
        footer {
            background-color: #5c4033;
            color: #f4f1ec;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }

        /* FI: Viesti - vihreä onnistumiselle, punainen virheille
           HU: Üzenet - zöld a sikerhez, piros a hibákhoz */
        .message {
            text-align: center;
            font-weight: bold;
            margin-top: 20px;
        }

        .message.success {
            color: green;
        }

        .message.error {
            color: red;
        }
    </style>
</head>

<body>
    <!-- FI: Yläpalkki sivuston nimellä ja kirjautumislinkillä -->
    <!-- HU: Fejléc sáv az oldal nevével és a bejelentkezési linkkel -->
    <div id="bar">
        <div>Drinkkinarkisto</div>
        <a href="login.php" id="login_button">login</a>
    </div>

    <main>
        <!-- FI: Näytetään onnistumis- tai virheviesti, jos sellainen on
             HU: Megjelenítjük a sikeres vagy hibaüzenetet, ha van ilyen -->
        <?php if (!empty($message)): ?>
            <div class="message <?= isset($isError) && $isError ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- FI: Rekisteröintilomake | HU: Regisztrációs űrlap -->
        <form action="register.php" method="post">
            <div id="loginbar">
                <!-- FI: Käyttäjätunnus-kenttä | HU: Felhasználónév mező -->
                <input type="text" id="username" name="username" required placeholder="Käyttäjätunnus" />

                <!-- FI: Salasana-kenttä | HU: Jelszó mező -->
                <input type="password" id="password" name="password" required placeholder="Salasana" />

                <!-- FI: Sähköposti-kenttä | HU: E-mail mező -->
                <input type="email" id="email" name="email" required placeholder="Sähköposti" />

                <!-- FI: Rekisteröidy-painike | HU: Regisztráció gomb -->
                <button type="submit">Rekisteröidy</button>
            </div>
        </form>
    </main>

    <!-- FI: Alatunniste | HU: Lábléc -->
    <footer>
        2025 Drinkkinarkisto
    </footer>
</body>

</html>