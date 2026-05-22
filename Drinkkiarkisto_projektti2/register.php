<?php

include 'db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['username'], $_POST['password'], $_POST['email'])) {
        die("Missing form fields.");
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email    = trim($_POST['email']);

    if ($username === '' || $password === '' || $email === '') {
        $message = "Täytä kaikki kentät.";
        $isError = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Virheellinen sähköposti.";
        $isError = true;
    } else {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $check = $pdo->prepare("SELECT COUNT(*) FROM Kayttaja WHERE Kayttajanimi = ? OR Sahkoposti = ?");
            $check->execute([$username, $email]);

            
            if ($check->fetchColumn() > 0) {
                $message = "Käyttäjänimi tai sähköposti on jo käytössä.";
                $isError = true;
            } else {
                $sql = "INSERT INTO Kayttaja (Kayttajanimi, Salasana, Sahkoposti, Rooli)
                        VALUES (?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql);
                $ok   = $stmt->execute([$username, $hashed_password, $email, 0]);

                if ($ok) {
                    header("Location: naviGuest.php");
                    exit;
                } else {
                    $message = "Virhe rekisteröinnissä!";
                    $isError = true;
                }
            }
        } catch (PDOException $e) {
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

        /* Ylänavigointipalkki */
        #bar {
            background-color: #5c4033;
            color: #f4f1ec;
            padding: 20px;
            font-size: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Kirjautumispainike yläpalkissa */
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

        /* Rekisteröintilomakkeen säiliö */
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

        /* Syötekenttien tyylit */
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

        /* Placeholder-tekstin väri */
        #loginbar input::placeholder {
            color: #a1876e;
        }

        /* Lähetyspainikkeen tyylit */
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

        /* Alatunnisteen tyylit */
        footer {
            background-color: #5c4033;
            color: #f4f1ec;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }

        /* Viesti - vihreä onnistumiselle, punainen virheille */
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
    <!-- Yläpalkki sivuston nimellä ja kirjautumislinkillä -->
    <div id="bar">
        <div>Drinkkinarkisto</div>
        <a href="login.php" id="login_button">login</a>
    </div>

    <main>
        <!-- Näytetään onnistumis- tai virheviesti, jos sellainen on -->
        <?php if (!empty($message)): ?>
            <div class="message <?= isset($isError) && $isError ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Rekisteröintilomake -->
        <form action="register.php" method="post">
            <div id="loginbar">
                <!-- Käyttäjätunnus-kenttä -->
                <input type="text" id="username" name="username" required placeholder="Käyttäjätunnus" />

                <!-- Salasana-kenttä -->
                <input type="password" id="password" name="password" required placeholder="Salasana" />

                <!-- Sähköposti-kenttä -->
                <input type="email" id="email" name="email" required placeholder="Sähköposti" />

                <!-- Rekisteröidy-painike -->
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
