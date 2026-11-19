<?php
// ============================================================
// categoria_form.php - Formulari per CREAR (sense id) o MODIFICAR (amb id) una categoria
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_rol("administrador");

$id = (int)rep($_GET, 'id');   // 0 = categoria nova
$nom        = "";
$descripcio = "";
$errors     = [];

// Si estem editant, carreguem les dades actuals
if ($id > 0) {
    $categoria = consulta_una($connexio, "SELECT nom, descripcio FROM categories WHERE id_categoria = ?", "i", [$id]);
    if ($categoria === null) {
        redirigeix_error("no_trobat", "admin_categories.php");
    }
    $nom        = $categoria['nom'];
    $descripcio = (string)$categoria['descripcio'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom        = rep($_POST, 'nom');
    $descripcio = rep($_POST, 'descripcio');

    // --- Validacions ---
    if ($nom === "") {
        $errors['nom'] = "El nom de la categoria no pot estar buit.";
    } elseif (mb_strlen($nom) > 50) {
        $errors['nom'] = "El nom no pot tenir més de 50 caràcters.";
    } else {
        // Duplicat: no poden existir dues categories amb el mateix nom
        $duplicada = consulta_una($connexio,
            "SELECT id_categoria FROM categories WHERE nom = ? AND id_categoria <> ?", "si", [$nom, $id]);
        if ($duplicada !== null) {
            $errors['nom'] = "Ja existeix una categoria amb aquest nom.";
        }
    }

    if (mb_strlen($descripcio) > 255) {
        $errors['descripcio'] = "La descripció no pot tenir més de 255 caràcters.";
    }

    // --- Guardem ---
    if (empty($errors)) {
        if ($id > 0) {
            executa($connexio, "UPDATE categories SET nom = ?, descripcio = ? WHERE id_categoria = ?",
                "ssi", [$nom, $descripcio, $id]);
            missatge_flash("success", "Categoria modificada correctament.");
        } else {
            executa($connexio, "INSERT INTO categories (nom, descripcio) VALUES (?, ?)",
                "ss", [$nom, $descripcio]);
            missatge_flash("success", "Categoria creada correctament.");
        }
        header("Location: admin_categories.php");
        exit;
    }
}

$titol_pagina = $id > 0 ? "Edita la categoria" : "Nova categoria";
$accio_formulari = "categoria_form.php" . ($id > 0 ? "?id=" . $id : "");
require "header.php";
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="admin_categories.php">Categories</a></li>
        <li class="breadcrumb-item active"><?= $id > 0 ? "Edita" : "Nova" ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4"><?= $id > 0 ? "Edita la categoria" : "Afegeix una categoria" ?></h1>

                <form method="post" action="<?= escapa($accio_formulari) ?>" novalidate>
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control <?= classe_camp($errors, 'nom') ?>" id="nom" name="nom" value="<?= escapa($nom) ?>">
                        <?php error_camp($errors, 'nom'); ?>
                    </div>
                    <div class="mb-4">
                        <label for="descripcio" class="form-label">Descripció</label>
                        <textarea class="form-control <?= classe_camp($errors, 'descripcio') ?>" id="descripcio" name="descripcio" rows="3"><?= escapa($descripcio) ?></textarea>
                        <?php error_camp($errors, 'descripcio'); ?>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Desa</button>
                        <a href="admin_categories.php" class="btn btn-outline-secondary">Cancel·la</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require "footer.php"; ?>
