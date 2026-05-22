<?php
$host = 'localhost';  // vagy a megfelelő adatbázis: localhost
$dbname = 'drinkkiarkisto';  // Az adatbázis neve
$username = 'root';  // Adatbázis felhasználónév
$password = '';  // Adatbázis jelszó

try {
    // Kapcsolódás az adatbázishoz
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Hibaüzenet kezelés
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Hiba esetén kiírja a hibát
    echo "Yhteyden muodostaminen epäonnistui: " . $e->getMessage();
}
?>