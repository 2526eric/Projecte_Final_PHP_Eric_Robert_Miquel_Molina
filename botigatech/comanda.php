<?php
// ============================================================
// comanda.php - Detall d'una comanda
// Un client només pot veure les seves comandes; l'administrador les pot veure totes.
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_login();

// Convertim l'id de la URL a número enter: així ningú pot colar-hi text estrany
$id = (int)rep($_GET, 'id');
// En vista client, l'administrador només pot veure les seves comandes, com un client
$es_admin = ($_SESSION['rol'] === 'administrador' && !vista_client());

// Capçalera de la comanda + dades del client (JOIN amb usuaris)
$comanda = consulta_una($connexio,
    "SELECT c.*, u.nom, u.cognoms, u.email
     FROM comandes c JOIN usuaris u ON u.id_usuari = c.id_usuari
     WHERE c.id_comanda = ?", "i", [$id]);

if ($comanda === null) {
    redirigeix_error("no_trobat", $es_admin ? "admin_comandes.php" : "comandes.php");
}

// Si no és administrador i la comanda no és seva, no la pot veure
if (!$es_admin && $comanda['id_usuari'] != $_SESSION['id_usuari']) {
    redirigeix_error("acces_denegat");
}

// Línies de la comanda: el preu unitari és el de quan es va comprar
// (no el preu actual del producte, que pot haver canviat).
$linies = consulta($connexio,
    "SELECT d.quantitat, d.preu_unitari, p.id_producte, p.nom, p.marca
     FROM detalls_comanda d JOIN productes p ON p.id_producte = d.id_producte
     WHERE d.id_comanda = ?
     ORDER BY p.nom", "i", [$id]);

$enrere = $es_admin ? "admin_comandes.php" : "comandes.php";
$titol_pagina = "Comanda #" . $id;
require "header.php";
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= $enrere ?>"><?= $es_admin ? "Comandes" : "Les meves comandes" ?></a></li>
        <li class="breadcrumb-item active">Comanda #<?= (int)$comanda['id_comanda'] ?></li>
    </ol>
</nav>

<h1 class="h3 mb-3">Comanda #<?= (int)$comanda['id_comanda'] ?>
    <span class="badge bg-<?= classe_estat($comanda['estat']) ?> fs-6 align-middle"><?= escapa(text_estat($comanda['estat'])) ?></span>
</h1>

<p class="mb-1"><strong>Data:</strong> <?= escapa(formata_data($comanda['data_comanda'])) ?></p>
<?php if ($es_admin) { ?>
    <p><strong>Client:</strong> <?= escapa($comanda['nom'] . " " . $comanda['cognoms']) ?> (<?= escapa($comanda['email']) ?>)</p>
<?php } ?>

<div class="table-responsive mt-3">
    <table class="table bg-white align-middle">
        <thead><tr><th>Producte</th><th>Preu unitari</th><th>Quantitat</th><th>Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($linies as $l) { ?>
            <tr>
                <td>
                    <a href="producte.php?id=<?= (int)$l['id_producte'] ?>"><?= escapa($l['nom']) ?></a><br>
                    <small class="text-muted"><?= escapa($l['marca']) ?></small>
                </td>
                <td><?= formata_preu($l['preu_unitari']) ?></td>
                <td><?= (int)$l['quantitat'] ?></td>
                <td><?= formata_preu($l['preu_unitari'] * $l['quantitat']) ?></td>
            </tr>
        <?php } ?>
        </tbody>
        <tfoot>
            <tr><th colspan="3" class="text-end">Total</th><th class="fs-5"><?= formata_preu($comanda['total']) ?></th></tr>
        </tfoot>
    </table>
</div>

<a href="<?= $enrere ?>" class="btn btn-outline-secondary">Tornar</a>

<?php require "footer.php"; ?>
