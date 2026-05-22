<?php
$yhteys = new PDO("mysql:host=localhost;dbname=drinkkiarkisto;charset=utf8", "root", "");

// Kiinteä käyttäjä ID (esimerkiksi vieras)
$kayttaja_id = 1;

$viesti = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nimi = $_POST['nimi'];
    $kuvaus = $_POST['kuvaus'];
    $juomalaji = $_POST['juomalaji'];

    $sql = "INSERT INTO Resepti (Kayttaja_ID, Nimi, Kuvaus, Juomalaji, Hyvaksytty) VALUES (?, ?, ?, ?, 0)";
    $stmt = $yhteys->prepare($sql);
    $stmt->execute([$kayttaja_id, $nimi, $kuvaus, $juomalaji]);

    $viesti = "<p class='message success'>Drinkkiohje lähetetty hyväksyttäväksi!</p>";
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Reseptin ehdotus</title>
    <link rel="stylesheet" href="drinkkinstyle.css">
</head>
<body>

<nav>
    <a href="rekisteröidy.html">Etusivu</a>
    <a href="ainesLis.php">Lisää resepti</a>
    <a href="poisto.php">Poista resepti</a>
    <a href="ehdotus.php">Ehdota reseptiä</a>
</nav>

<main>
    <div class="container">
        <h2>Reseptin ehdotus</h2>

        <?= $viesti ?>

        <form method="post">
            <div>
                <label>
                    <input type="text" name="nimi" placeholder="Drinkkin nimi:">
                </label>
            </div>
            <div>
                <label>
                    <input type="text" name="juomalaji" placeholder="Juomalaji:">
                </label>
            </div>
            <div>
                <label>
                    <p><textarea name="kuvaus" rows="8" cols="40" placeholder="Kuvaus:"></textarea></p>
                </label>
            </div>
            <div>
                <button type="submit">Lähetä ehdotus</button>
            </div>
        </form>
    </div>
</main>

<footer>
     2025 Drinkkiarkisto
</footer>

</body>
</html>