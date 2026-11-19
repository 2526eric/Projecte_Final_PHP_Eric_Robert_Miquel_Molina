<?php
// ============================================================
// comandes.php - Les meves comandes (clients, i administradors en vista client)
// Es mostren ordenades: primer les pendents, després les enviades,
// les entregades i les cancel·lades; dins de cada estat, la més nova primer.
// El client pot cancel·lar les comandes que encara són pendents.
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_vista_client();

// ---------- Cancel·lar una comanda (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && rep($_POST, 'accio') === 'cancelar') {
    $id_comanda = (int)rep($_POST, 'id_comanda');

    // Comprovem que la comanda és d'aquest usuari i que encara és pendent
    $comanda = consulta_una($connexio,
        "SELECT id_comanda, estat FROM comandes WHERE id_comanda = ? AND id_usuari = ?",
        "ii", [$id_comanda, $_SESSION['id_usuari']]);

    if ($comanda === null) {
        redirigeix_error("no_trobat", "comandes.php");
    }
    if ($comanda['estat'] !== 'pendent') {
        redirigeix_error("comanda_no_cancelable", "comandes.php");
    }

    cancela_comanda($connexio, $id_comanda);
    missatge_flash("success", "La comanda #" . $id_comanda . " s'ha cancel·lat.");
    header("Location: comandes.php");
    exit;
}

$comandes = consulta($connexio,
    "SELECT id_comanda, data_comanda, estat, total
     FROM comandes
     WHERE id_usuari = ?
     ORDER BY FIELD(estat, 'pendent', 'enviada', 'entregada', 'cancelada'), data_comanda DESC",
    "i", [$_SESSION['id_usuari']]);

$titol_pagina = "Les meves comandes";
require "header.php";
?>

<h1 class="h3 mb-4">Les meves comandes</h1>

<?php if (count($comandes) === 0) { ?>
    <div class="alert alert-info">Encara no has fet cap comanda. <a href="cataleg.php" class="alert-link">Anem al catàleg</a>.</div>
<?php } else { ?>
    <div class="table-responsive">
        <table class="table table-striped bg-white align-middle">
            <thead><tr><th>#</th><th>Data</th><th>Estat</th><th>Total</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($comandes as $c) { ?>
                <tr>
                    <td><?= (int)$c['id_comanda'] ?></td>
                    <td><?= escapa(formata_data($c['data_comanda'])) ?></td>
                    <td><span class="badge bg-<?= classe_estat($c['estat']) ?>"><?= escapa(text_estat($c['estat'])) ?></span></td>
                    <td><?= formata_preu($c['total']) ?></td>
                    <td class="text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <a class="btn btn-sm btn-outline-primary" href="comanda.php?id=<?= (int)$c['id_comanda'] ?>">Detall</a>
                            <?php if ($c['estat'] === 'pendent') { ?>
                                <form method="post" action="comandes.php" class="m-0" onsubmit="return confirm('Segur que vols cancel·lar aquesta comanda?');">
                                    <input type="hidden" name="accio" value="cancelar">
                                    <input type="hidden" name="id_comanda" value="<?= (int)$c['id_comanda'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancel·la</button>
                                </form>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<?php require "footer.php"; ?>
