<?php

session_start();

if (!isset($_SESSION['name']) || !isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: login.php");
    exit();
}



?>




<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Admin Navigointi</title>
    <link rel="stylesheet" href="drinkkinstyle.css" />
</head>
<body>
    <nav>
      <ul class="admin-navigointi">
        <li><a href="admin_dashboard.php">Etusivu</a></li>
        <li><a href="hallinta.php">Käyttäjien hallinta</a></li>
        <li><a href="uusi_drinkki.php">Lisää drinkki</a></li>
        <li><a href="logout.php">Kirjaudu ulos</a></li>
      </ul>
    </nav>
</body>
</html>
