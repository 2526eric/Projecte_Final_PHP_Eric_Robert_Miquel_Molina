<?php
// ============================================================
// header.php - Capçalera comuna a totes les pàgines
// Mostra informació diferent segons l'estat de la sessió:
//   - Sense sessió: nom de l'aplicació i enllaços per entrar o registrar-se
//   - Amb sessió: nom, correu i rol de l'usuari + menú segons el rol
// Cada pàgina ha d'haver fet session_start() i carregat funcions.php abans d'incloure'l.
// ============================================================

if (!isset($titol_pagina)) {
    $titol_pagina = "BotigaTech";
}
$logat = esta_logat();

// Nombre d'unitats que hi ha a la cistella (només per als clients)
$unitats_cistella = isset($_SESSION['cistella']) ? array_sum($_SESSION['cistella']) : 0;
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escapa($titol_pagina) ?> - BotigaTech</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= $logat ? 'acces.php' : 'index.html' ?>">BotigaTech</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Obre el menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
            <?php if (!$logat) { ?>
                <?php enllac_nav("index.html", "Inici"); ?>
                <?php enllac_nav("login.php", "Inicia sessió"); ?>
                <?php enllac_nav("registre.php", "Registra't"); ?>

            <?php } elseif (!vista_client()) { /* administrador en vista d'administrador */ ?>
                <?php enllac_nav("acces.php", "Inici"); ?>
                <?php enllac_nav(["cataleg.php", "producte.php"], "Catàleg"); ?>
                <?php enllac_nav(["admin_productes.php", "producte_form.php"], "Productes"); ?>
                <?php enllac_nav(["admin_categories.php", "categoria_form.php"], "Categories"); ?>
                <?php enllac_nav(["admin_comandes.php", "comanda.php"], "Comandes"); ?>
                <?php enllac_nav("admin_estadistiques.php", "Estadístiques"); ?>

            <?php } else { ?>
                <?php enllac_nav("acces.php", "Inici"); ?>
                <?php enllac_nav(["cataleg.php", "producte.php"], "Catàleg"); ?>
                <?php enllac_nav("cistella.php", 'Cistella <span class="badge bg-primary">' . (int)$unitats_cistella . '</span>'); ?>
                <?php enllac_nav(["comandes.php", "comanda.php"], "Les meves comandes"); ?>
            <?php } ?>
            </ul>

            <?php if ($logat) { ?>
                <span class="navbar-text me-3">
                    <?= escapa($_SESSION['nom'] . " " . $_SESSION['cognoms']) ?>
                    (<?= escapa($_SESSION['email']) ?>)
                    <span class="badge bg-info text-dark"><?= $_SESSION['rol'] === 'administrador' ? 'Administrador' : 'Client' ?></span>
                </span>
                <?php if ($_SESSION['rol'] === 'administrador') { ?>
                    <!-- Només l'administrador veu aquest botó: canvia entre vista d'administrador i vista de client -->
                    <form method="post" action="canvia_vista.php" class="d-inline me-2">
                        <button type="submit" class="btn btn-warning btn-sm text-nowrap">
                            <?= vista_client() ? "Vista admin" : "Vista client" ?>
                        </button>
                    </form>
                <?php } ?>
                <a class="btn btn-outline-light btn-sm" href="logout.php">Sortir</a>
            <?php } ?>
        </div>
    </div>
</nav>

<main class="container py-4">
<?php mostra_flash(); ?>
