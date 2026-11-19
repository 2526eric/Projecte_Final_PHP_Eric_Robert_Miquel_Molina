<?php
// ============================================================
// cataleg.php - Llistat de productes
// Inclou: CERCA (nom o marca), FILTRES (categoria, preu màxim, només disponibles),
// ORDENACIÓ i PAGINACIÓ (funcionalitat extra).
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_login();

$per_pagina = 6;   // productes que es mostren per pàgina

// 1. Recollim els filtres que arriben per la URL (mètode GET)
$q            = rep($_GET, 'q');
$categoria    = (int)rep($_GET, 'categoria');
$preu_max     = rep($_GET, 'preu_max');
$nomes_dispon = isset($_GET['disponibles']);
$ordre        = rep($_GET, 'ordre');
$pagina       = max(1, (int)rep($_GET, 'pagina'));

// 2. Ordenacions permeses. Fem servir una llista tancada perquè
//    mai posem text escrit per l'usuari directament dins del SQL.
$ordres = [
    "disponibles" => ["Disponibles primer", "(p.stock = 0) ASC, p.preu ASC"],
    "preu_asc"    => ["Preu: de menys a més",                  "p.preu ASC"],
    "preu_desc"   => ["Preu: de més a menys",                  "p.preu DESC"],
    "nom"         => ["Nom (A-Z)",                             "p.nom ASC"],
    "valoracio"   => ["Més ben valorats",                      "mitjana DESC, num_valoracions DESC"]
];
if (!isset($ordres[$ordre])) {
    $ordre = "disponibles";   // ordenació per defecte
}

// 3. Construïm la condició WHERE segons els filtres que s'han fet servir
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
$preu_max_num = str_replace(",", ".", $preu_max);
if ($preu_max !== "" && is_numeric($preu_max_num)) {
    $condicions[] = "p.preu <= ?";
    $tipus   .= "d";
    $valors[] = (float)$preu_max_num;
}
if ($nomes_dispon) {
    $condicions[] = "p.stock > 0";
}
$where = count($condicions) > 0 ? "WHERE " . implode(" AND ", $condicions) : "";

// 4. Comptem quants productes hi ha en total per saber quantes pàgines fan falta
$fila_total   = consulta_una($connexio, "SELECT COUNT(*) AS total FROM productes p $where", $tipus, $valors);
$total        = (int)$fila_total['total'];
$total_pagines = max(1, (int)ceil($total / $per_pagina));
if ($pagina > $total_pagines) {
    $pagina = $total_pagines;
}
$desplacament = ($pagina - 1) * $per_pagina;   // quants productes saltem (OFFSET)

// 5. Consulta principal: producte + categoria + mitjana de valoracions
$sql = "SELECT p.*, c.nom AS categoria,
               COALESCE(AVG(v.puntuacio), 0) AS mitjana,
               COUNT(v.id_valoracio) AS num_valoracions
        FROM productes p
        JOIN categories c ON c.id_categoria = p.id_categoria
        LEFT JOIN valoracions v ON v.id_producte = p.id_producte
        $where
        GROUP BY p.id_producte, c.nom
        ORDER BY " . $ordres[$ordre][1] . "
        LIMIT ? OFFSET ?";
$productes = consulta($connexio, $sql, $tipus . "ii", array_merge($valors, [$per_pagina, $desplacament]));

// Llista de categories per al desplegable del filtre
$categories = consulta($connexio, "SELECT id_categoria, nom FROM categories ORDER BY nom");

// Adreça actual (amb els filtres) per tornar-hi després d'afegir un producte a la cistella
$adreca_actual = "cataleg.php" . ($_SERVER['QUERY_STRING'] !== "" ? "?" . $_SERVER['QUERY_STRING'] : "");
$pot_comprar   = vista_client();   // client, o administrador en vista client

$titol_pagina = "Catàleg";
require "header.php";
?>

<h1 class="h3 mb-3">Catàleg de productes</h1>

<!-- Formulari de cerca, filtres i ordenació (envia les dades per GET) -->
<form method="get" action="cataleg.php" class="card card-body shadow-sm mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label for="q" class="form-label">Cerca per nom o marca</label>
            <input type="text" class="form-control" id="q" name="q" value="<?= escapa($q) ?>" placeholder="Ex.: Ryzen, Kingston...">
        </div>
        <div class="col-md-3">
            <label for="categoria" class="form-label">Categoria</label>
            <select class="form-select" id="categoria" name="categoria">
                <option value="0">Totes</option>
                <?php foreach ($categories as $c) { ?>
                    <option value="<?= (int)$c['id_categoria'] ?>" <?= $categoria == $c['id_categoria'] ? 'selected' : '' ?>><?= escapa($c['nom']) ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="preu_max" class="form-label">Preu màxim (€)</label>
            <input type="text" class="form-control" id="preu_max" name="preu_max" value="<?= escapa($preu_max) ?>">
        </div>
        <div class="col-md-3">
            <label for="ordre" class="form-label">Ordena per</label>
            <select class="form-select" id="ordre" name="ordre">
                <?php foreach ($ordres as $clau => $dades) { ?>
                    <option value="<?= $clau ?>" <?= $ordre === $clau ? 'selected' : '' ?>><?= escapa($dades[0]) ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-6">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="disponibles" name="disponibles" value="1" <?= $nomes_dispon ? 'checked' : '' ?>>
                <label class="form-check-label" for="disponibles">Mostra només els productes disponibles</label>
            </div>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="cataleg.php" class="btn btn-outline-secondary">Neteja</a>
            <button type="submit" class="btn btn-primary">Aplica</button>
        </div>
    </div>
</form>

<p class="text-muted"><?= $total ?> producte(s) trobat(s)</p>

<?php if ($total === 0) { ?>
    <div class="alert alert-warning">No hi ha cap producte que coincideixi amb la cerca.</div>
<?php } else { ?>
    <div class="row g-4">
        <?php foreach ($productes as $p) { mostra_targeta_producte($p, $pot_comprar, $adreca_actual); } ?>
    </div>

    <!-- Paginació: conserva els filtres a cada enllaç -->
    <?php if ($total_pagines > 1) { ?>
        <nav class="mt-4" aria-label="Paginació">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pagines; $i++) {
                    $enllac = "cataleg.php?" . http_build_query(array_merge($_GET, ['pagina' => $i]));
                ?>
                    <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="<?= escapa($enllac) ?>"><?= $i ?></a>
                    </li>
                <?php } ?>
            </ul>
        </nav>
    <?php } ?>
<?php } ?>

<?php require "footer.php"; ?>
