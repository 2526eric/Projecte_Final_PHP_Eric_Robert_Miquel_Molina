<?php
// ============================================================
// error.php - Pàgina que gestiona tots els errors de l'aplicació
// Les altres pàgines ens envien aquí amb un codi: error.php?codi=no_trobat
// Els errors dels formularis (camp buit, email no vàlid...) es mostren
// al mateix formulari, i aquí arriben els errors més greus.
// ============================================================
session_start();
require_once "funcions.php";

// Llista d'errors: codi => [codi HTTP, títol, missatge]
$errors = [
    "acces_denegat" => [403, "Accés denegat",
        "No tens permís per veure aquesta pàgina."],
    "no_trobat" => [404, "No s'ha trobat",
        "L'element que busques no existeix o s'ha eliminat."],
    "peticio_invalida" => [400, "Petició no vàlida",
        "Les dades que s'han enviat no són correctes."],
    "bd" => [500, "Error de la base de dades",
        "No s'ha pogut connectar amb la base de dades o la consulta ha fallat. Comprova que MySQL està encès i que has importat database.sql."],
    "te_relacions" => [409, "No es pot eliminar",
        "Aquest element té altres elements relacionats (per exemple, productes dins d'una categoria o productes que ja s'han venut) i no es pot eliminar."],
    "stock_insuficient" => [409, "Estoc insuficient",
        "Algun producte de la cistella ja no té prou unitats o s'ha eliminat. Revisa la cistella i torna-ho a provar."],
    "comanda_no_cancelable" => [409, "No es pot cancel·lar",
        "Aquesta comanda ja no es pot cancel·lar."]
];

// Agafem el codi de la URL; si no el coneixem, mostrem un error genèric
$codi = rep($_GET, 'codi');
if (isset($errors[$codi])) {
    list($codi_http, $titol, $missatge) = $errors[$codi];
} else {
    $codi_http = 500;
    $titol     = "Error inesperat";
    $missatge  = "S'ha produït un error que no esperàvem.";
}
http_response_code($codi_http);

// Pàgina a la qual es pot tornar. Només acceptem fitxers nostres (adreca_segura).
$per_defecte = esta_logat() ? "acces.php" : "index.html";
$tornar = adreca_segura(rep($_GET, 'tornar'), $per_defecte);

$titol_pagina = $titol;
require "header.php";
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-danger shadow-sm">
            <div class="card-header bg-danger text-white">
                Error <?= (int)$codi_http ?>
            </div>
            <div class="card-body">
                <h1 class="h4"><?= escapa($titol) ?></h1>
                <p class="mb-4"><?= escapa($missatge) ?></p>
                <a href="<?= escapa($tornar) ?>" class="btn btn-primary">Tornar enrere</a>
                <a href="<?= $per_defecte ?>" class="btn btn-outline-secondary">Anar a l'inici</a>
            </div>
        </div>
    </div>
</div>

<?php require "footer.php"; ?>
