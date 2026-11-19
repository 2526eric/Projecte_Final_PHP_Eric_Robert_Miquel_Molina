<?php
// ============================================================
// admin_estadistiques.php - Estadístiques de la botiga (funcionalitat extra)
// Només fem servir consultes SQL senzilles (COUNT, SUM, AVG, GROUP BY)
// i barres de Bootstrap, sense cap llibreria de gràfics.
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_rol("administrador");

// --- Números generals ---
// Les comandes cancel·lades no compten com a vendes
$vendes   = consulta_una($connexio, "SELECT COALESCE(SUM(total), 0) AS total, COUNT(*) AS n FROM comandes WHERE estat <> 'cancelada'");
$clients  = consulta_una($connexio, "SELECT COUNT(*) AS n FROM usuaris WHERE rol = 'client'");
$productes = consulta_una($connexio, "SELECT COUNT(*) AS n FROM productes");
$esgotats = consulta_una($connexio, "SELECT COUNT(*) AS n FROM productes WHERE stock = 0");

// --- Comandes agrupades per estat ---
$per_estat = consulta($connexio,
    "SELECT estat, COUNT(*) AS n FROM comandes GROUP BY estat
     ORDER BY FIELD(estat, 'pendent', 'enviada', 'entregada', 'cancelada')");
$total_comandes = 0;
foreach ($per_estat as $e) {
    $total_comandes += $e['n'];
}

// --- Els 5 productes més venuts (sumant les unitats de les comandes no cancel·lades) ---
$mes_venuts = consulta($connexio,
    "SELECT p.id_producte, p.nom, SUM(d.quantitat) AS unitats, SUM(d.quantitat * d.preu_unitari) AS import
     FROM detalls_comanda d
     JOIN comandes c ON c.id_comanda = d.id_comanda
     JOIN productes p ON p.id_producte = d.id_producte
     WHERE c.estat <> 'cancelada'
     GROUP BY p.id_producte, p.nom
     ORDER BY unitats DESC, p.nom ASC
     LIMIT 5");
$max_unitats = count($mes_venuts) > 0 ? $mes_venuts[0]['unitats'] : 1;

// --- Els 5 productes més ben valorats ---
$mes_valorats = consulta($connexio,
    "SELECT p.id_producte, p.nom, AVG(v.puntuacio) AS mitjana, COUNT(*) AS num
     FROM valoracions v JOIN productes p ON p.id_producte = v.id_producte
     GROUP BY p.id_producte, p.nom
     ORDER BY mitjana DESC, num DESC, p.nom ASC
     LIMIT 5");

$titol_pagina = "Estadístiques";
require "header.php";
?>

<h1 class="h3 mb-4">Estadístiques de la botiga</h1>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card text-center shadow-sm h-100"><div class="card-body">
            <div class="display-6"><?= formata_preu($vendes['total']) ?></div>
            <div class="text-muted">Vendes totals</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center shadow-sm h-100"><div class="card-body">
            <div class="display-6"><?= (int)$vendes['n'] ?></div>
            <div class="text-muted">Comandes (sense cancel·lades)</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center shadow-sm h-100"><div class="card-body">
            <div class="display-6"><?= (int)$clients['n'] ?></div>
            <div class="text-muted">Clients registrats</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center shadow-sm h-100"><div class="card-body">
            <div class="display-6"><?= (int)$esgotats['n'] ?> / <?= (int)$productes['n'] ?></div>
            <div class="text-muted">Productes esgotats</div>
        </div></div>
    </div>
</div>

<div class="row g-4">

    <div class="col-lg-6">
        <h2 class="h5">Comandes per estat</h2>
        <div class="card"><div class="card-body">
            <?php if ($total_comandes === 0) { ?>
                <p class="text-muted mb-0">Encara no hi ha comandes.</p>
            <?php } ?>
            <?php foreach ($per_estat as $e) {
                $percentatge = round($e['n'] * 100 / $total_comandes);
            ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span><?= escapa(text_estat($e['estat'])) ?></span>
                        <strong><?= (int)$e['n'] ?> (<?= $percentatge ?>%)</strong>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-<?= classe_estat($e['estat']) ?>" style="width: <?= $percentatge ?>%"></div>
                    </div>
                </div>
            <?php } ?>
        </div></div>
    </div>

    <div class="col-lg-6">
        <h2 class="h5">Productes més venuts</h2>
        <div class="card"><div class="card-body">
            <?php if (count($mes_venuts) === 0) { ?>
                <p class="text-muted mb-0">Encara no s'ha venut cap producte.</p>
            <?php } ?>
            <?php foreach ($mes_venuts as $p) {
                $amplada = round($p['unitats'] * 100 / $max_unitats);
            ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <a href="producte.php?id=<?= (int)$p['id_producte'] ?>"><?= escapa($p['nom']) ?></a>
                        <strong><?= (int)$p['unitats'] ?> ud. &middot; <?= formata_preu($p['import']) ?></strong>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?= $amplada ?>%"></div>
                    </div>
                </div>
            <?php } ?>
        </div></div>
    </div>

    <div class="col-12">
        <h2 class="h5">Productes més ben valorats</h2>
        <?php if (count($mes_valorats) === 0) { ?>
            <p class="text-muted">Encara no hi ha valoracions.</p>
        <?php } else { ?>
            <div class="table-responsive">
                <table class="table table-striped bg-white align-middle">
                    <thead><tr><th>Producte</th><th>Mitjana</th><th>Valoracions</th></tr></thead>
                    <tbody>
                    <?php foreach ($mes_valorats as $p) { ?>
                        <tr>
                            <td><a href="producte.php?id=<?= (int)$p['id_producte'] ?>"><?= escapa($p['nom']) ?></a></td>
                            <td><span class="estrelles"><?= estrelles($p['mitjana']) ?></span> <?= number_format($p['mitjana'], 1, ',', '') ?></td>
                            <td><?= (int)$p['num'] ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>

</div>

<?php require "footer.php"; ?>
