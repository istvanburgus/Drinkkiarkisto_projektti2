<?php
// Csatlakozás az adatbázishoz
$yhteys = new PDO("mysql:host=localhost;dbname=drinkkiarkisto;charset=utf8", "root", "");

// Jóváhagyás
if (isset($_POST['hyvaksy'])) {
    $id = (int)$_POST['id'];
    $sql = "UPDATE Resepti SET Hyvaksytty = 1 WHERE Drinkkin_ID = ?";
    $stmt = $yhteys->prepare($sql);
    $stmt->execute([$id]);
}

// Elutasítás
if (isset($_POST['hylkaa'])) {
    $id = (int)$_POST['id'];
    $sql = "DELETE FROM Resepti WHERE Drinkkin_ID = ?";
    $stmt = $yhteys->prepare($sql);
    $stmt->execute([$id]);
}

// Lekérdezzük a jóváhagyatlan recepteket
$sql = "SELECT Drinkkin_ID, Nimi, Kuvaus, Juomalaji FROM Resepti WHERE Hyvaksytty = 0";
$stmt = $yhteys->query($sql);
$reseptit = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Reseptien hyväksyntä</title>
    <link rel="stylesheet" href="drinkkinstyle.css">
</head>
<body>

<h2>Hyväksy tai hylkää drinkkiehdotuksia</h2>

<?php if (count($reseptit) === 0): ?>
    <p>Ei uusia ehdotuksia.</p>
<?php else: ?>
    <ul>
    <?php foreach ($reseptit as $r): ?>
        <li>
            <strong><?= htmlspecialchars($r['Nimi']) ?></strong> (<?= htmlspecialchars($r['Juomalaji']) ?>)<br>
            <em><?= nl2br(htmlspecialchars($r['Kuvaus'])) ?></em><br>
            <form method="post" style="display:inline;">
                <input type="hidden" name="id" value="<?= $r['Drinkkin_ID'] ?>">
                <button name="hyvaksy" type="submit">Hyväksy</button>
            </form>
            <form method="post" style="display:inline;">
                <input type="hidden" name="id" value="<?= $r['Drinkkin_ID'] ?>">
                <button name="hylkaa" type="submit">Hylkää</button>
            </form>
        </li>
        <br>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>

<footer>
    2025 Drinkkinarkisto
</footer>

</body>
</html>