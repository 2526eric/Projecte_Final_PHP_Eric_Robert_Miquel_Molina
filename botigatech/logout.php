<?php
// ============================================================
// logout.php - Tanca la sessió correctament
// ============================================================
session_start();

// 1. Buidem totes les dades de la sessió
$_SESSION = [];

// 2. Esborrem la galeta de sessió del navegador
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}

// 3. Destruïm la sessió al servidor
session_destroy();

header("Location: login.php?sortit=1");
exit;
