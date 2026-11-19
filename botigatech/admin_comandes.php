<?php
// ============================================================
// admin_comandes.php - Gestió de totes les comandes (administrador)
// Permet FILTRAR per estat o client i CANVIAR L'ESTAT de cada comanda.
// Ordenades: primer les pendents, després enviades, entregades i cancel·lades;
// dins de cada estat, la més recent primer.
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_rol("administrador");

$estats = ['pendent', 'enviada', 'entregada', 'cancelada'];

// ---------- Canviar l'estat d'una comanda (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && rep($_POST, 'accio') === 'canviar_estat') {

    $id_comanda  = (int)rep($_POST, 'id_comanda');
    $nou_estat   = rep($_POST, 'estat');
    $tornar      = adreca_segura(rep($_POST, 'tornar'), "admin_comandes.php");

    if (!in_array($nou_estat, $estats)) {
        redirigeix_error("peticio_invalida", "admin_comandes.php");
    }

    $comanda = consulta_una($connexio, "SELECT estat FROM comandes WHERE id_comanda = ?", "i", [$id_comanda]);
    if ($comanda === null) {
        redirigeix_error("no_trobat", "admin_comandes.php");
    }
    // Una comanda cancel·lada ja no es pot tornar a obrir
    if ($comanda['estat'] === 'cancelada') {
        redirigeix_error("comanda_no_cancelable", "admin_comandes.php");
    }

    if ($nou_estat === 'cancelada') {
        // Cancel·lar també torna les unitats a l'estoc
        cancela_comanda($connexio, $id_comanda);
    } else {
        executa($connexio, "UPDATE comandes SET estat = ? WHERE id_comanda = ?", "si", [$nou_estat, $id_comanda]);
    }

    missatge_flash("success", "L'estat de la comanda #" . $id_comanda . " ara és «" . text_estat($nou_estat) . "».");
    header("Location: " . $tornar);
    exit;
}

// ---------- Filtres ----------
$filtre_estat = rep($_GET, 'estat');
$q            = rep($_GET, 'q');

$condicions = [];
$tipus      = "";
$valors     = [];
if (in_array($filtre_estat, $estats)) {
    $condicions[] = "c.estat = ?";
    $tipus   .= "s";
    $valors[] = $filtre_estat;
}
if ($q !== "") {
    $condicions[] = "(u.nom LIKE ? OR u.cognoms LIKE ? OR u.email LIKE ?)";
    $tipus   .= "sss";
    $valors[] = "%" . $q . "%";
    $valors[] = "%" . $q . "%";
    $valors[] = "%" . $q . "%";
}
$where = count($condicions) > 0 ? "WHERE " . implode(" AND ", $condicions) : "";

$comandes = consulta($connexio,
    "SELECT c.id_comanda, c.data_comanda, c.estat, c.total, u.nom, u.cognoms, u.email
     FROM comandes c JOIN usuaris u ON u.id_usuari = c.id_usuari
     $where
     ORDER BY FIELD(c.estat, 'pendent', 'enviada', 'entregada', 'cancelada'), c.data_comanda DESC",
    $tipus, $valors);

// Adreça actual (amb els filtres) per tornar-hi després de canviar un estat
$adreca_actual = "admin_comandes.php" . ($_SERVER['QUERY_STRING'] !== "" ? "?" . $_SERVER['QUERY_STRING'] : "");

$titol_pagina = "Gestió de comandes";
require "header.php";
?>

<h1 class="h3 mb-3">Gestió de comandes</h1>

<form method="get" action="admin_comandes.php" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="estat" class="form-label">Estat</label>
            <select class="form-select" id="estat" name="estat">
                <option value="">Tots</option>
                <?php foreach ($estats as $e) { ?>
                    <option value="<?= $e ?>" <?= $filtre_estat === $e ? 'selected' : '' ?>><?= escapa(text_estat($e)) ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-5">
            <label for="q" class="form-label">Client (nom o correu)</label>
            <input type="text" class="form-control" id="q" name="q" value="<?= escapa($q) ?>">
        </div>
        <div class="col-md-3 text-md-end">
            <a href="admin_comandes.php" class="btn btn-outline-secondary">Neteja</a>
            <button type="submit" class="btn btn-primary">Filtra</button>
        </div>
    </div>
</form>

<?php if (count($comandes) === 0) { ?>
    <div class="alert alert-warning">No hi ha cap comanda amb aquests filtres.</div>
<?php } else { ?>
    <div class="table-responsive">
        <table class="table table-striped bg-white align-middle">
            <thead><tr><th>#</th><th>Client</th><th>Data</th><th>Total</th><th>Estat</th><th>Canvia l'estat</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($comandes as $c) { ?>
                <tr>
                    <td><?= (int)$c['id_comanda'] ?></td>
                    <td><?= escapa($c['nom'] . " " . $c['cognoms']) ?><br><small class="text-muted"><?= escapa($c['email']) ?></small></td>
                    <td><?= escapa(formata_data($c['data_comanda'])) ?></td>
                    <td><?= formata_preu($c['total']) ?></td>
                    <td><span class="badge bg-<?= classe_estat($c['estat']) ?>"><?= escapa(text_estat($c['estat'])) ?></span></td>
                    <td>
                        <?php if ($c['estat'] === 'cancelada') { ?>
                            <span class="text-muted small">Comanda tancada</span>
                        <?php } else { ?>
                            <form method="post" action="admin_comandes.php" class="d-flex gap-1 m-0">
                                <input type="hidden" name="accio" value="canviar_estat">
                                <input type="hidden" name="id_comanda" value="<?= (int)$c['id_comanda'] ?>">
                                <input type="hidden" name="tornar" value="<?= escapa($adreca_actual) ?>">
                                <select name="estat" class="form-select form-select-sm">
                                    <?php foreach ($estats as $e) { ?>
                                        <option value="<?= $e ?>" <?= $c['estat'] === $e ? 'selected' : '' ?>><?= escapa(text_estat($e)) ?></option>
                                    <?php } ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary">Desa</button>
                            </form>
                        <?php } ?>
                    </td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="comanda.php?id=<?= (int)$c['id_comanda'] ?>">Detall</a></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<?php require "footer.php"; ?>
