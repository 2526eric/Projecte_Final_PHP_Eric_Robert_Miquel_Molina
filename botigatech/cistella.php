<?php
// ============================================================
// cistella.php - Cistella de la compra (clients, i administradors en vista client)
// La cistella es guarda a la sessió: $_SESSION['cistella'][id_producte] = quantitat
// Accions (totes per POST): afegir, actualitzar, treure, buidar i confirmar la comanda.
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_vista_client();

if (!isset($_SESSION['cistella'])) {
    $_SESSION['cistella'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accio = rep($_POST, 'accio');

    // ---------- AFEGIR un producte ----------
    if ($accio === 'afegir') {
        $id        = (int)rep($_POST, 'id_producte');
        $quantitat = (int)rep($_POST, 'quantitat');
        $tornar    = adreca_segura(rep($_POST, 'tornar'), "cistella.php");

        $producte = consulta_una($connexio,
            "SELECT id_producte, nom, stock FROM productes WHERE id_producte = ?", "i", [$id]);

        if ($producte === null) {
            redirigeix_error("no_trobat", "cataleg.php");
        }

        $ja_te = $_SESSION['cistella'][$id] ?? 0;   // unitats que ja hi havia a la cistella

        if ($quantitat < 1) {
            missatge_flash("danger", "La quantitat ha de ser com a mínim 1.");
        } elseif ($producte['stock'] == 0) {
            missatge_flash("warning", "«" . $producte['nom'] . "» està esgotat.");
        } elseif ($ja_te + $quantitat > $producte['stock']) {
            missatge_flash("warning", "No hi ha prou estoc de «" . $producte['nom'] . "». Només en queden " . $producte['stock'] . " i ja en tens " . $ja_te . " a la cistella.");
        } else {
            $_SESSION['cistella'][$id] = $ja_te + $quantitat;
            missatge_flash("success", "«" . $producte['nom'] . "» afegit a la cistella.");
        }
        header("Location: " . $tornar);
        exit;
    }

    // ---------- CANVIAR LA QUANTITAT d'un producte ----------
    if ($accio === 'actualitzar') {
        $id        = (int)rep($_POST, 'id_producte');
        $quantitat = (int)rep($_POST, 'quantitat');

        $producte = consulta_una($connexio,
            "SELECT nom, stock FROM productes WHERE id_producte = ?", "i", [$id]);

        if ($producte === null || !isset($_SESSION['cistella'][$id])) {
            unset($_SESSION['cistella'][$id]);
        } elseif ($quantitat < 1) {
            missatge_flash("danger", "La quantitat ha de ser com a mínim 1. Si no el vols, fes servir el botó «Treu».");
        } elseif ($quantitat > $producte['stock']) {
            missatge_flash("warning", "Només queden " . $producte['stock'] . " unitats de «" . $producte['nom'] . "».");
        } else {
            $_SESSION['cistella'][$id] = $quantitat;
            missatge_flash("success", "Quantitat actualitzada.");
        }
        header("Location: cistella.php");
        exit;
    }

    // ---------- TREURE un producte ----------
    if ($accio === 'treure') {
        $id = (int)rep($_POST, 'id_producte');
        unset($_SESSION['cistella'][$id]);
        missatge_flash("info", "Producte tret de la cistella.");
        header("Location: cistella.php");
        exit;
    }

    // ---------- BUIDAR la cistella ----------
    if ($accio === 'buidar') {
        $_SESSION['cistella'] = [];
        missatge_flash("info", "Has buidat la cistella.");
        header("Location: cistella.php");
        exit;
    }

    // ---------- CONFIRMAR la comanda ----------
    // Tot el procés es fa dins d'una transacció: si alguna cosa falla,
    // no es guarda res (ni la comanda ni el canvi d'estoc).
    if ($accio === 'confirmar') {

        if (count($_SESSION['cistella']) === 0) {
            missatge_flash("warning", "La cistella és buida.");
            header("Location: cistella.php");
            exit;
        }

        mysqli_begin_transaction($connexio);

        $linies = [];
        $total  = 0;

        foreach ($_SESSION['cistella'] as $id_producte => $quantitat) {
            // FOR UPDATE bloqueja la fila perquè ningú més canviï l'estoc mentre comprem
            $p = consulta_una($connexio,
                "SELECT id_producte, preu, stock FROM productes WHERE id_producte = ? FOR UPDATE",
                "i", [$id_producte]);

            if ($p === null || $p['stock'] < $quantitat) {
                mysqli_rollback($connexio);
                redirigeix_error("stock_insuficient", "cistella.php");
            }
            $linies[] = ['id' => $id_producte, 'quantitat' => $quantitat, 'preu' => $p['preu']];
            $total   += $p['preu'] * $quantitat;
        }

        // 1. Guardem la comanda
        executa($connexio,
            "INSERT INTO comandes (id_usuari, total) VALUES (?, ?)",
            "id", [$_SESSION['id_usuari'], round($total, 2)]);
        $id_comanda = mysqli_insert_id($connexio);

        // 2. Guardem cada línia i restem les unitats de l'estoc
        foreach ($linies as $l) {
            executa($connexio,
                "INSERT INTO detalls_comanda (id_comanda, id_producte, quantitat, preu_unitari) VALUES (?, ?, ?, ?)",
                "iiid", [$id_comanda, $l['id'], $l['quantitat'], $l['preu']]);
            executa($connexio,
                "UPDATE productes SET stock = stock - ? WHERE id_producte = ?",
                "ii", [$l['quantitat'], $l['id']]);
        }

        mysqli_commit($connexio);

        $_SESSION['cistella'] = [];
        missatge_flash("success", "Comanda confirmada! Gràcies per la teva compra.");
        header("Location: comanda.php?id=" . $id_comanda);
        exit;
    }
}

// ---------- Mostrem el contingut de la cistella ----------
$linies = [];
$total  = 0;

if (count($_SESSION['cistella']) > 0) {
    $ids       = array_keys($_SESSION['cistella']);
    $marcadors = implode(",", array_fill(0, count($ids), "?"));   // ?,?,?
    $productes = consulta($connexio,
        "SELECT id_producte, nom, marca, preu, stock, imatge
         FROM productes WHERE id_producte IN ($marcadors) ORDER BY nom",
        str_repeat("i", count($ids)), $ids);

    $trobats = [];
    foreach ($productes as $p) {
        $quantitat = $_SESSION['cistella'][$p['id_producte']];
        $subtotal  = $p['preu'] * $quantitat;
        $total    += $subtotal;
        $linies[]  = ['p' => $p, 'quantitat' => $quantitat, 'subtotal' => $subtotal];
        $trobats[] = $p['id_producte'];
    }
    // Si algun producte ja no existeix a la BD, el traiem de la cistella
    foreach ($ids as $id) {
        if (!in_array($id, $trobats)) {
            unset($_SESSION['cistella'][$id]);
        }
    }
}

$titol_pagina = "Cistella";
require "header.php";
?>

<h1 class="h3 mb-4">La meva cistella</h1>

<?php if (count($linies) === 0) { ?>
    <div class="alert alert-info">La cistella és buida. <a href="cataleg.php" class="alert-link">Anem al catàleg</a> a buscar productes.</div>
<?php } else { ?>

    <div class="table-responsive">
        <table class="table bg-white align-middle">
            <thead>
                <tr><th></th><th>Producte</th><th>Preu</th><th style="width: 190px;">Quantitat</th><th>Subtotal</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($linies as $l) { $p = $l['p']; ?>
                <tr>
                    <td><img class="miniatura" src="<?= escapa(imatge_producte($p['imatge'])) ?>" alt=""></td>
                    <td>
                        <a href="producte.php?id=<?= (int)$p['id_producte'] ?>"><?= escapa($p['nom']) ?></a><br>
                        <small class="text-muted"><?= escapa($p['marca']) ?></small>
                    </td>
                    <td><?= formata_preu($p['preu']) ?></td>
                    <td>
                        <!-- Cada fila té el seu propi formulari per canviar la quantitat -->
                        <form method="post" action="cistella.php" class="d-flex gap-1 m-0">
                            <input type="hidden" name="accio" value="actualitzar">
                            <input type="hidden" name="id_producte" value="<?= (int)$p['id_producte'] ?>">
                            <input type="number" name="quantitat" class="form-control form-control-sm" value="<?= (int)$l['quantitat'] ?>" min="1" max="<?= (int)$p['stock'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Canvia</button>
                        </form>
                    </td>
                    <td><?= formata_preu($l['subtotal']) ?></td>
                    <td>
                        <form method="post" action="cistella.php" class="m-0">
                            <input type="hidden" name="accio" value="treure">
                            <input type="hidden" name="id_producte" value="<?= (int)$p['id_producte'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Treu</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Total</th>
                    <th colspan="2" class="fs-5"><?= formata_preu($total) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="d-flex flex-wrap gap-2 justify-content-between">
        <div class="d-flex gap-2">
            <a href="cataleg.php" class="btn btn-outline-secondary">Segueix comprant</a>
            <form method="post" action="cistella.php" class="m-0">
                <input type="hidden" name="accio" value="buidar">
                <button type="submit" class="btn btn-outline-danger">Buida la cistella</button>
            </form>
        </div>
        <form method="post" action="cistella.php" class="m-0">
            <input type="hidden" name="accio" value="confirmar">
            <button type="submit" class="btn btn-success btn-lg">Confirma la comanda</button>
        </form>
    </div>

<?php } ?>

<?php require "footer.php"; ?>
