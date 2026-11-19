<?php
// ============================================================
// conexio.php - Connexió amb la base de dades (mysqli)
// Es fa servir amb require_once a totes les pàgines que consulten la BD.
// ============================================================

// Dades de connexió. Aquests valors són els per defecte de Laragon i XAMPP:
// usuari "root" i sense contrasenya. Canvia'ls si el teu MySQL és diferent.
$servidor       = "localhost";
$usuari_bd      = "root";
$contrasenya_bd = "";
$nom_bd         = "botigatech";

// Desactivem els missatges tècnics automàtics de mysqli:
// els errors els gestionem nosaltres i els mostrem a error.php
mysqli_report(MYSQLI_REPORT_OFF);

// Obrim la connexió (la @ evita que PHP mostri el missatge tècnic per pantalla)
$connexio = @mysqli_connect($servidor, $usuari_bd, $contrasenya_bd, $nom_bd);

// Si no es pot connectar, enviem l'usuari a la pàgina d'errors
if (!$connexio) {
    header("Location: error.php?codi=bd");
    exit;
}

// Fem servir UTF-8 perquè es vegin bé els accents i la "ç"
mysqli_set_charset($connexio, "utf8mb4");
