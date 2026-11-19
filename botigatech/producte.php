<?php
// ============================================================
// producte.php - Fitxa d'un producte amb les seves valoracions
// Els clients poden afegir-lo a la cistella i valorar-lo (1 a 5 estrelles).
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_login();

// Busquem el producte pel seu id (arriba per la URL: producte.php?id=3)
$id = (int)rep($_GET, 'id');
$producte = consulta_una($connexio,
    "SELECT p.*, c.nom AS categoria
     FROM productes p JOIN categories c ON c.id_categoria = p.id_categoria
     WHERE p.id_producte = ?", "i", [$id]);

if ($producte === null) {
    redirigeix_error("no_trobat", "cataleg.php");
}

$errors     = [];
$puntuacio  = 0;
$comentari  = "";

// ---------- Guardar una valoració (només els clients) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && vista_client()) {

    $puntuacio = (int)rep($_POST, 'puntuacio');
    $comentari = rep($_POST, 'comentari');

    if ($puntuacio < 1 || $puntuacio > 5) {
        $errors['puntuacio'] = "Has de triar una puntuació entre 1 i 5 estrelles.";
    }
    if (mb_strlen($comentari) > 500) {
        $errors['comentari'] = "El comentari no pot tenir més de 500 caràcters.";
    }

    if (empty($errors)) {
        // Cada usuari només pot tenir una valoració per producte:
        // si ja n'hi havia una, l'actualitzem; si no, la creem.
        $anterior = consulta_una($connexio,
            "SELECT id_valoracio FROM valoracions WHERE id_producte = ? AND id_usuari = ?",
            "ii", [$id, $_SESSION['id_usuari']]);

        if ($anterior !== null) {
            executa($connexio,
                "UPDATE valoracions SET puntuacio = ?, comentari = ?, data_valoracio = NOW() WHERE id_valoracio = ?",
                "isi", [$puntuacio, $comentari, $anterior['id_valoracio']]);
        } else {
            executa($connexio,
                "INSERT INTO valoracions (id_producte, id_usuari, puntuacio, comentari) VALUES (?, ?, ?, ?)",
                "iiis", [$id, $_SESSION['id_usuari'], $puntuacio, $comentari]);
        }
        missatge_flash("success", "Gràcies per la teva valoració!");
        header("Location: producte.php?id=" . $id);
        exit;
    }
}

// ---------- Carreguem les valoracions i calculem la mitjana ----------
$valoracions = consulta($connexio,
    "SELECT v.*, u.nom, u.cognoms
     FROM valoracions v JOIN usuaris u ON u.id_usuari = v.id_usuari
     WHERE v.id_producte = ?
     ORDER BY v.data_valoracio DESC", "i", [$id]);

$suma = 0;
$la_meva = null;
foreach ($valoracions as $v) {
    $suma += $v['puntuacio'];
    if ($v['id_usuari'] == $_SESSION['id_usuari']) {
        $la_meva = $v;
    }
}
$mitjana = count($valoracions) > 0 ? $suma / count($valoracions) : 0;

// Si l'usuari ja havia valorat i no ve d'un error, omplim el formulari amb la seva valoració
if ($la_meva !== null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $puntuacio = (int)$la_meva['puntuacio'];
    $comentari = $la_meva['comentari'];
}

$es_client = vista_client();   // client, o administrador en vista client
$titol_pagina = $producte['nom'];
require "header.php";
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="cataleg.php">Catàleg</a></li>
        <li class="breadcrumb-item active"><?= escapa($producte['categoria']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-md-5">
        <img src="<?= escapa(imatge_producte($producte['imatge'])) ?>" class="img-fluid rounded border imatge-detall w-100" alt="<?= escapa($producte['nom']) ?>">
    </div>

    <div class="col-md-7">
        <span class="badge bg-light text-dark border mb-2"><?= escapa($producte['categoria']) ?></span>
        <h1 class="h3"><?= escapa($producte['nom']) ?></h1>
        <p class="text-muted"><?= escapa($producte['marca']) ?></p>

        <?php if (count($valoracions) > 0) { ?>
            <p class="estrelles fs-5 mb-2"><?= estrelles($mitjana) ?>
                <small class="text-muted"><?= number_format($mitjana, 1, ',', '') ?> / 5 (<?= count($valoracions) ?> valoracions)</small></p>
        <?php } else { ?>
            <p class="text-muted">Encara no té valoracions.</p>
        <?php } ?>

        <p><?= nl2br(escapa($producte['descripcio'])) ?></p>
        <p class="fs-3 fw-bold"><?= formata_preu($producte['preu']) ?></p>

        <?php if ($producte['stock'] > 0) { ?>
            <p><span class="badge bg-success">Disponible: <?= (int)$producte['stock'] ?> unitats</span></p>
            <?php if ($es_client) { ?>
                <form method="post" action="cistella.php" class="row g-2 align-items-center">
                    <input type="hidden" name="accio" value="afegir">
                    <input type="hidden" name="id_producte" value="<?= (int)$producte['id_producte'] ?>">
                    <input type="hidden" name="tornar" value="producte.php?id=<?= (int)$producte['id_producte'] ?>">
                    <div class="col-auto">
                        <input type="number" class="form-control" name="quantitat" value="1" min="1" max="<?= (int)$producte['stock'] ?>" style="width: 90px;">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Afegeix a la cistella</button>
                    </div>
                </form>
            <?php } ?>
        <?php } else { ?>
            <p><span class="badge bg-danger">Esgotat</span></p>
        <?php } ?>
    </div>
</div>

<hr class="my-4">

<div class="row g-4">
    <div class="col-md-7">
        <h2 class="h5">Valoracions dels clients</h2>
        <?php if (count($valoracions) === 0) { ?>
            <p class="text-muted">Ningú ha valorat aquest producte encara.</p>
        <?php } ?>
        <?php foreach ($valoracions as $v) { ?>
            <div class="card mb-2">
                <div class="card-body py-2">
                    <span class="estrelles"><?= estrelles($v['puntuacio']) ?></span>
                    <strong class="ms-2"><?= escapa($v['nom'] . " " . $v['cognoms']) ?></strong>
                    <small class="text-muted ms-2"><?= escapa(formata_data($v['data_valoracio'])) ?></small>
                    <?php if ($v['comentari'] !== null && $v['comentari'] !== "") { ?>
                        <p class="mb-0 mt-1"><?= nl2br(escapa($v['comentari'])) ?></p>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>

    <?php if ($es_client) { ?>
    <div class="col-md-5">
        <h2 class="h5"><?= $la_meva !== null ? "Modifica la teva valoració" : "Valora aquest producte" ?></h2>
        <form method="post" action="producte.php?id=<?= (int)$producte['id_producte'] ?>" novalidate>
            <div class="mb-3">
                <label for="puntuacio" class="form-label">Puntuació</label>
                <select class="form-select <?= classe_camp($errors, 'puntuacio') ?>" id="puntuacio" name="puntuacio">
                    <option value="0">Tria una puntuació...</option>
                    <?php for ($i = 5; $i >= 1; $i--) { ?>
                        <option value="<?= $i ?>" <?= $puntuacio === $i ? 'selected' : '' ?>><?= str_repeat("★", $i) ?> (<?= $i ?>)</option>
                    <?php } ?>
                </select>
                <?php error_camp($errors, 'puntuacio'); ?>
            </div>
            <div class="mb-3">
                <label for="comentari" class="form-label">Comentari (opcional)</label>
                <textarea class="form-control <?= classe_camp($errors, 'comentari') ?>" id="comentari" name="comentari" rows="3"><?= escapa($comentari) ?></textarea>
                <?php error_camp($errors, 'comentari'); ?>
            </div>
            <button type="submit" class="btn btn-outline-primary">Desa la valoració</button>
        </form>
    </div>
    <?php } ?>
</div>

<?php require "footer.php"; ?>
