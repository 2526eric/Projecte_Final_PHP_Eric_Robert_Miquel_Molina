<?php
// ============================================================
// canvia_vista.php - L'administrador canvia entre la vista d'administrador
// i la vista de client (per veure la botiga tal com la veuen els clients).
// Només s'accepta per POST. El rol de l'usuari NO canvia: només guardem
// una marca a la sessió ($_SESSION['vista_client']) que fan servir
// el header i les pàgines de client.
// ============================================================
session_start();
require_once "funcions.php";
requereix_rol("administrador");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigeix_error("peticio_invalida", "acces.php");
}

// Si estava activada la desactivem, i si no ho estava l'activem
if (!empty($_SESSION['vista_client'])) {
    $_SESSION['vista_client'] = false;
    missatge_flash("info", "Has tornat a la vista d'administrador.");
} else {
    $_SESSION['vista_client'] = true;
    missatge_flash("info", "Ara veus la botiga com la veuen els clients. Continues sent administrador.");
}

header("Location: acces.php");
exit;
