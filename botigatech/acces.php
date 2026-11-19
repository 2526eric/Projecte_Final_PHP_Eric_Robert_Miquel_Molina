<?php
// ============================================================
// acces.php - Pàgina principal després de fer login
// El contingut és diferent segons el rol de l'usuari:
//   - client: accessos ràpids, darreres comandes i novetats
//   - administrador: resum de la botiga i productes amb poc estoc
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_login();

// Un administrador en "vista client" veu la pàgina d'inici dels clients
$es_admin = ($_SESSION['rol'] === 'administrador' && !vista_client());

// Primer fem les consultes (abans de mostrar res per pantalla)
// i després dibuixem la pàgina.
if ($es_admin) {

    // Quatre números per tenir una visió ràpida de la botiga
    $pendents        = consulta_una($connexio, "SELECT COUNT(*) AS n FROM comandes WHERE estat = 'pendent'");
    $poc_estoc       = consulta_una($connexio, "SELECT COUNT(*) AS n FROM productes WHERE stock <= 5");
    $clients         = consulta_una($connexio, "SELECT COUNT(*) AS n FROM usuaris WHERE rol = 'client'");
    $productes_total = consulta_una($connexio, "SELECT COUNT(*) AS n FROM productes");

    // Productes amb menys estoc (els que s'han de reposar primer)
    $a_reposar = consulta($connexio,
        "SELECT p.id_producte, p.nom, p.marca, p.stock, c.nom AS categoria
         FROM productes p JOIN categories c ON c.id_categoria = p.id_categoria
         ORDER BY p.stock ASC, p.nom ASC
         LIMIT 5");

} else {

    // Les 3 darreres comandes del client
    $comandes = consulta($connexio,
        "SELECT id_comanda, data_comanda, estat, total FROM comandes
         WHERE id_usuari = ? ORDER BY data_comanda DESC LIMIT 3",
        "i", [$_SESSION['id_usuari']]);

    // Les 3 novetats que encara tenen estoc
    $novetats = consulta($connexio,
        "SELECT p.*, c.nom AS categoria, COALESCE(AVG(v.puntuacio), 0) AS mitjana, COUNT(v.id_valoracio) AS num_valoracions
         FROM productes p
         JOIN categories c ON c.id_categoria = p.id_categoria
         LEFT JOIN valoracions v ON v.id_producte = p.id_producte
         WHERE p.stock > 0
         GROUP BY p.id_producte, c.nom
         ORDER BY p.data_alta DESC, p.id_producte DESC
         LIMIT 3");
}

$titol_pagina = "Inici";
require "header.php";

if ($es_admin) {
    // ---------------- VISTA DE L'ADMINISTRADOR ----------------
?>
    <h1 class="h3 mb-4">Panell d'administració</h1>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card text-center shadow-sm h-100"><div class="card-body">
                <div class="display-6"><?= (int)$pendents['n'] ?></div>
                <div class="text-muted">Comandes pendents</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card text-center shadow-sm h-100"><div class="card-body">
                <div class="display-6"><?= (int)$poc_estoc['n'] ?></div>
                <div class="text-muted">Productes amb poc estoc</div>
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
                <div class="display-6"><?= (int)$productes_total['n'] ?></div>
                <div class="text-muted">Productes</div>
            </div></div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-primary" href="producte_form.php">Afegeix un producte</a>
        <a class="btn btn-outline-primary" href="admin_comandes.php">Gestiona les comandes</a>
        <a class="btn btn-outline-primary" href="admin_estadistiques.php">Veure estadístiques</a>
    </div>

    <h2 class="h5">Productes amb menys estoc</h2>
    <div class="table-responsive">
        <table class="table table-striped bg-white align-middle">
            <thead><tr><th>Producte</th><th>Marca</th><th>Categoria</th><th>Estoc</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($a_reposar as $p) { ?>
                <tr>
                    <td><?= escapa($p['nom']) ?></td>
                    <td><?= escapa($p['marca']) ?></td>
                    <td><?= escapa($p['categoria']) ?></td>
                    <td><span class="badge bg-<?= $p['stock'] == 0 ? 'danger' : 'warning text-dark' ?>"><?= (int)$p['stock'] ?></span></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="producte_form.php?id=<?= (int)$p['id_producte'] ?>">Edita</a></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

<?php
} else {
    // ---------------- VISTA DEL CLIENT ----------------
?>
    <h1 class="h3 mb-4">Hola, <?= escapa($_SESSION['nom']) ?>!</h1>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <a class="card text-decoration-none shadow-sm h-100" href="cataleg.php"><div class="card-body">
                <h2 class="h5">Catàleg</h2>
                <p class="text-muted mb-0">Busca i compara productes.</p>
            </div></a>
        </div>
        <div class="col-md-4">
            <a class="card text-decoration-none shadow-sm h-100" href="cistella.php"><div class="card-body">
                <h2 class="h5">La meva cistella</h2>
                <p class="text-muted mb-0">Tens <?= (int)array_sum($_SESSION['cistella'] ?? []) ?> unitat(s) a la cistella.</p>
            </div></a>
        </div>
        <div class="col-md-4">
            <a class="card text-decoration-none shadow-sm h-100" href="comandes.php"><div class="card-body">
                <h2 class="h5">Les meves comandes</h2>
                <p class="text-muted mb-0">Consulta l'estat de les teves compres.</p>
            </div></a>
        </div>
    </div>

    <h2 class="h5">Darreres comandes</h2>
    <?php if (count($comandes) === 0) { ?>
        <p class="text-muted">Encara no has fet cap comanda.</p>
    <?php } else { ?>
        <div class="table-responsive mb-4">
            <table class="table table-striped bg-white align-middle">
                <thead><tr><th>#</th><th>Data</th><th>Estat</th><th>Total</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($comandes as $c) { ?>
                    <tr>
                        <td><?= (int)$c['id_comanda'] ?></td>
                        <td><?= escapa(formata_data($c['data_comanda'])) ?></td>
                        <td><span class="badge bg-<?= classe_estat($c['estat']) ?>"><?= escapa(text_estat($c['estat'])) ?></span></td>
                        <td><?= formata_preu($c['total']) ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="comanda.php?id=<?= (int)$c['id_comanda'] ?>">Detall</a></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>

    <h2 class="h5">Novetats</h2>
    <div class="row g-3">
        <?php foreach ($novetats as $p) { mostra_targeta_producte($p, true, "acces.php"); } ?>
    </div>

<?php
}
require "footer.php";
