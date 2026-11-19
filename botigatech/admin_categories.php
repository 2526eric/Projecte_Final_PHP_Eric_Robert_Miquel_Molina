<?php
// ============================================================
// admin_categories.php - Llistat de categories (CRUD: Read)
// Mostra quants productes té cada categoria.
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_rol("administrador");

// LEFT JOIN: així també apareixen les categories que encara no tenen cap producte
// (amb COUNT = 0). Amb un JOIN normal aquestes categories no sortirien.
$categories = consulta($connexio,
    "SELECT c.id_categoria, c.nom, c.descripcio, COUNT(p.id_producte) AS num_productes
     FROM categories c
     LEFT JOIN productes p ON p.id_categoria = c.id_categoria
     GROUP BY c.id_categoria, c.nom, c.descripcio
     ORDER BY c.nom");

$titol_pagina = "Gestió de categories";
require "header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Gestió de categories</h1>
    <a href="categoria_form.php" class="btn btn-primary">Afegeix una categoria</a>
</div>

<?php if (count($categories) === 0) { ?>
    <div class="alert alert-info">Encara no hi ha cap categoria.</div>
<?php } else { ?>
    <div class="table-responsive">
        <table class="table table-striped bg-white align-middle">
            <thead><tr><th>Nom</th><th>Descripció</th><th>Productes</th><th class="text-end">Accions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c) { ?>
                <tr>
                    <td><?= escapa($c['nom']) ?></td>
                    <td><?= escapa($c['descripcio']) ?></td>
                    <td><span class="badge bg-secondary"><?= (int)$c['num_productes'] ?></span></td>
                    <td>
                        <div class="d-flex gap-2 justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="categoria_form.php?id=<?= (int)$c['id_categoria'] ?>">Edita</a>
                            <!-- Eliminar es fa per POST amb confirmació; si la categoria té productes, categoria_eliminar.php ho bloqueja -->
                            <form method="post" action="categoria_eliminar.php" class="m-0" onsubmit="return confirm('Segur que vols eliminar aquesta categoria?');">
                                <input type="hidden" name="id_categoria" value="<?= (int)$c['id_categoria'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Elimina</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<?php require "footer.php"; ?>
