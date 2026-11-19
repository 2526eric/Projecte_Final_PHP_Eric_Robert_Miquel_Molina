<?php
// ============================================================
// producte_eliminar.php - Elimina un producte (CRUD: Delete)
// Només s'accepta per POST. No es pot eliminar un producte que ja s'ha venut
// (surt a alguna comanda), perquè trencaríem l'historial de compres.
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_rol("administrador");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigeix_error("peticio_invalida", "admin_productes.php");
}

$id = (int)rep($_POST, 'id_producte');

$producte = consulta_una($connexio, "SELECT id_producte, nom, imatge FROM productes WHERE id_producte = ?", "i", [$id]);
if ($producte === null) {
    redirigeix_error("no_trobat", "admin_productes.php");
}

// Comprovem si el producte té comandes relacionades
$vendes = consulta_una($connexio, "SELECT COUNT(*) AS n FROM detalls_comanda WHERE id_producte = ?", "i", [$id]);
if ($vendes['n'] > 0) {
    redirigeix_error("te_relacions", "admin_productes.php");
}

// Les valoracions del producte s'esborren soles (ON DELETE CASCADE a la base de dades)
executa($connexio, "DELETE FROM productes WHERE id_producte = ?", "i", [$id]);

// Esborrem també la imatge del disc
if ($producte['imatge'] && file_exists(__DIR__ . "/uploads/" . $producte['imatge'])) {
    unlink(__DIR__ . "/uploads/" . $producte['imatge']);
}

missatge_flash("success", "Producte «" . $producte['nom'] . "» eliminat.");
header("Location: admin_productes.php");
exit;
