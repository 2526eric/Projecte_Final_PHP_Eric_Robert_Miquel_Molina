<?php
// ============================================================
// admin_productes.php - Llistat de productes per a l'administrador (CRUD: Read)
// Des d'aquí es pot crear, editar i eliminar productes.
// Ordenats per estoc: primer els que en tenen menys (els que cal reposar).
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_rol("administrador");

$q         = rep($_GET, 'q');
$categoria = (int)rep($_GET, 'categoria');

// Construïm el WHERE a poc a poc: només afegim les condicions dels filtres que
// l'usuari ha omplert. Els valors van sempre amb "?" (prepared statements)
// i guardem els tipus ("s" = text, "i" = enter) perquè mysqli els necessita.
$condicions = [];
$tipus      = "";
$valors     = [];
if ($q !== "") {
    $condicions[] = "(p.nom LIKE ? OR p.marca LIKE ?)";
    $tipus   .= "ss";
    $valors[] = "%" . $q . "%";
    $valors[] = "%" . $q . "%";
}
if ($categoria > 0) {
    $condicions[] = "p.id_categoria = ?";
    $tipus   .= "i";
    $valors[] = $categoria;
}
$where = count($condicions) > 0 ? "WHERE " . implode(" AND ", $condicions) : "";

$productes = consulta($connexio,
    "SELECT p.id_producte, p.nom, p.marca, p.preu, p.stock, p.imatge, c.nom AS categoria
     FROM productes p JOIN categories c ON c.id_categoria = p.id_categoria
     $where
     ORDER BY p.stock ASC, p.nom ASC", $tipus, $valors);

$categories = consulta($connexio, "SELECT id_categoria, nom FROM categories ORDER BY nom");

$titol_pagina = "Gestió de productes";
require "header.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Gestió de productes</h1>
    <a href="producte_form.php" class="btn btn-primary">Afegeix un producte</a>
</div>

<form method="get" action="admin_productes.php" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label for="q" class="form-label">Cerca per nom o marca</label>
            <input type="text" class="form-control" id="q" name="q" value="<?= escapa($q) ?>">
        </div>
        <div class="col-md-4">
            <label for="categoria" class="form-label">Categoria</label>
            <select class="form-select" id="categoria" name="categoria">
                <option value="0">Totes</option>
                <?php foreach ($categories as $c) { ?>
                    <option value="<?= (int)$c['id_categoria'] ?>" <?= $categoria == $c['id_categoria'] ? 'selected' : '' ?>><?= escapa($c['nom']) ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-3 text-md-end">
            <a href="admin_productes.php" class="btn btn-outline-secondary">Neteja</a>
            <button type="submit" class="btn btn-primary">Cerca</button>
        </div>
    </div>
</form>

<?php if (count($productes) === 0) { ?>
    <div class="alert alert-warning">No s'ha trobat cap producte.</div>
<?php } else { ?>
    <div class="table-responsive">
        <table class="table table-striped bg-white align-middle">
            <thead>
                <tr><th></th><th>Nom</th><th>Marca</th><th>Categoria</th><th>Preu</th><th>Estoc</th><th class="text-end">Accions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($productes as $p) { ?>
                <tr>
                    <td><img class="miniatura" src="<?= escapa(imatge_producte($p['imatge'])) ?>" alt=""></td>
                    <td><a href="producte.php?id=<?= (int)$p['id_producte'] ?>"><?= escapa($p['nom']) ?></a></td>
                    <td><?= escapa($p['marca']) ?></td>
                    <td><?= escapa($p['categoria']) ?></td>
                    <td><?= formata_preu($p['preu']) ?></td>
                    <td>
                        <!-- Color de l'estoc: vermell = esgotat, groc = poc (5 o menys), verd = correcte -->
                        <?php if ($p['stock'] == 0) { ?>
                            <span class="badge bg-danger">Esgotat</span>
                        <?php } elseif ($p['stock'] <= 5) { ?>
                            <span class="badge bg-warning text-dark"><?= (int)$p['stock'] ?></span>
                        <?php } else { ?>
                            <span class="badge bg-success"><?= (int)$p['stock'] ?></span>
                        <?php } ?>
                    </td>
                    <td>
                        <div class="d-flex gap-2 justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="producte_form.php?id=<?= (int)$p['id_producte'] ?>">Edita</a>
                            <!-- L'eliminació es fa per POST i demana confirmació -->
                            <form method="post" action="producte_eliminar.php" class="m-0" onsubmit="return confirm('Segur que vols eliminar aquest producte?');">
                                <input type="hidden" name="id_producte" value="<?= (int)$p['id_producte'] ?>">
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
