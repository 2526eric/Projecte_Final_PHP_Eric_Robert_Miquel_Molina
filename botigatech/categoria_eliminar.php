<?php
// ============================================================
// categoria_eliminar.php - Elimina una categoria (CRUD: Delete)
// Només per POST. No es pot eliminar una categoria que té productes.
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_rol("administrador");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigeix_error("peticio_invalida", "admin_categories.php");
}

$id = (int)rep($_POST, 'id_categoria');

$categoria = consulta_una($connexio, "SELECT id_categoria, nom FROM categories WHERE id_categoria = ?", "i", [$id]);
if ($categoria === null) {
    redirigeix_error("no_trobat", "admin_categories.php");
}

// Si la categoria té productes, no la podem eliminar
$productes = consulta_una($connexio, "SELECT COUNT(*) AS n FROM productes WHERE id_categoria = ?", "i", [$id]);
if ($productes['n'] > 0) {
    redirigeix_error("te_relacions", "admin_categories.php");
}

executa($connexio, "DELETE FROM categories WHERE id_categoria = ?", "i", [$id]);

missatge_flash("success", "Categoria «" . $categoria['nom'] . "» eliminada.");
header("Location: admin_categories.php");
exit;
