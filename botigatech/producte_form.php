<?php
// ============================================================
// producte_form.php - Formulari per CREAR (sense id) o MODIFICAR (amb id) un producte
// Inclou la càrrega d'una imatge (funcionalitat extra) amb validacions.
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";
requereix_rol("administrador");

$id = (int)rep($_GET, 'id');   // 0 = producte nou

// Valors per defecte del formulari
$dades = [
    'nom' => "", 'marca' => "", 'id_categoria' => 0,
    'descripcio' => "", 'preu' => "", 'stock' => "", 'imatge' => null
];

// Si estem editant, carreguem el producte de la base de dades
if ($id > 0) {
    $producte = consulta_una($connexio, "SELECT * FROM productes WHERE id_producte = ?", "i", [$id]);
    if ($producte === null) {
        redirigeix_error("no_trobat", "admin_productes.php");
    }
    $dades = $producte;
}

$errors     = [];
$categories = consulta($connexio, "SELECT id_categoria, nom FROM categories ORDER BY nom");

// Tipus d'imatge permesos (tipus MIME => extensió)
$tipus_permesos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$mida_maxima    = 2 * 1024 * 1024;   // 2 MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dades['nom']          = rep($_POST, 'nom');
    $dades['marca']        = rep($_POST, 'marca');
    $dades['id_categoria'] = (int)rep($_POST, 'id_categoria');
    $dades['descripcio']   = rep($_POST, 'descripcio');
    $dades['preu']         = rep($_POST, 'preu');
    $dades['stock']        = rep($_POST, 'stock');
    $elimina_imatge        = isset($_POST['elimina_imatge']);

    // --- Validacions dels camps de text ---
    if ($dades['nom'] === "") {
        $errors['nom'] = "El nom del producte no pot estar buit.";
    } elseif (mb_strlen($dades['nom']) > 100) {
        $errors['nom'] = "El nom no pot tenir més de 100 caràcters.";
    }

    if ($dades['marca'] === "") {
        $errors['marca'] = "La marca no pot estar buida.";
    } elseif (mb_strlen($dades['marca']) > 50) {
        $errors['marca'] = "La marca no pot tenir més de 50 caràcters.";
    }

    if ($dades['id_categoria'] <= 0) {
        $errors['id_categoria'] = "Has de triar una categoria.";
    } else {
        $existeix = consulta_una($connexio, "SELECT id_categoria FROM categories WHERE id_categoria = ?",
            "i", [$dades['id_categoria']]);
        if ($existeix === null) {
            $errors['id_categoria'] = "La categoria triada no existeix.";
        }
    }

    if (mb_strlen($dades['descripcio']) > 1000) {
        $errors['descripcio'] = "La descripció no pot tenir més de 1000 caràcters.";
    }

    // El preu pot venir amb coma (12,50): la canviem per un punt abans de comprovar-lo
    $preu_num = str_replace(",", ".", $dades['preu']);
    if ($dades['preu'] === "") {
        $errors['preu'] = "El preu no pot estar buit.";
    } elseif (!is_numeric($preu_num)) {
        $errors['preu'] = "El preu ha de ser un número (per exemple 24,90).";
    } elseif ($preu_num <= 0) {
        $errors['preu'] = "El preu ha de ser més gran que 0.";
    } elseif ($preu_num > 99999) {
        $errors['preu'] = "El preu és massa alt.";
    }

    if ($dades['stock'] === "") {
        $errors['stock'] = "L'estoc no pot estar buit.";
    } elseif (!ctype_digit($dades['stock'])) {
        $errors['stock'] = "L'estoc ha de ser un nombre enter igual o més gran que 0.";
    } elseif ((int)$dades['stock'] > 100000) {
        $errors['stock'] = "L'estoc és massa alt.";
    }

    // --- Duplicats: no pot haver-hi dos productes amb el mateix nom i marca ---
    if (!isset($errors['nom']) && !isset($errors['marca'])) {
        $duplicat = consulta_una($connexio,
            "SELECT id_producte FROM productes WHERE nom = ? AND marca = ? AND id_producte <> ?",
            "ssi", [$dades['nom'], $dades['marca'], $id]);
        if ($duplicat !== null) {
            $errors['nom'] = "Ja existeix un producte amb aquest nom i aquesta marca.";
        }
    }

    // --- Validació de la imatge (només si l'usuari n'ha triat una) ---
    $imatge_nova = null;   // dades de la imatge pujada, si és correcta
    $fitxer = $_FILES['imatge'] ?? null;

    if ($fitxer !== null && $fitxer['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($fitxer['error'] === UPLOAD_ERR_INI_SIZE || $fitxer['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errors['imatge'] = "La imatge és massa gran (màxim 2 MB).";
        } elseif ($fitxer['error'] !== UPLOAD_ERR_OK) {
            $errors['imatge'] = "No s'ha pogut pujar la imatge. Torna-ho a provar.";
        } elseif ($fitxer['size'] > $mida_maxima) {
            $errors['imatge'] = "La imatge és massa gran (màxim 2 MB).";
        } else {
            // getimagesize comprova el contingut real del fitxer (no només l'extensió)
            $info = @getimagesize($fitxer['tmp_name']);
            if ($info === false || !isset($tipus_permesos[$info['mime']])) {
                $errors['imatge'] = "El fitxer ha de ser una imatge JPG, PNG o WEBP.";
            } else {
                $imatge_nova = ['tmp' => $fitxer['tmp_name'], 'extensio' => $tipus_permesos[$info['mime']]];
            }
        }
    }

    // --- Si tot és correcte, guardem ---
    if (empty($errors)) {
        $preu  = round((float)$preu_num, 2);
        $stock = (int)$dades['stock'];
        $nom_imatge = $dades['imatge'];   // per defecte, es manté la imatge que ja hi havia

        // Pugem la imatge nova amb un nom únic (així no es repeteixen noms)
        if ($imatge_nova !== null) {
            $nom_fitxer = "prod_" . uniqid() . "." . $imatge_nova['extensio'];
            if (move_uploaded_file($imatge_nova['tmp'], __DIR__ . "/uploads/" . $nom_fitxer)) {
                // Esborrem la imatge antiga del disc
                if ($nom_imatge && file_exists(__DIR__ . "/uploads/" . $nom_imatge)) {
                    unlink(__DIR__ . "/uploads/" . $nom_imatge);
                }
                $nom_imatge = $nom_fitxer;
            } else {
                $errors['imatge'] = "No s'ha pogut guardar la imatge al servidor (comprova que la carpeta uploads existeix).";
            }
        } elseif ($elimina_imatge && $nom_imatge) {
            // L'administrador ha marcat "elimina la imatge actual"
            if (file_exists(__DIR__ . "/uploads/" . $nom_imatge)) {
                unlink(__DIR__ . "/uploads/" . $nom_imatge);
            }
            $nom_imatge = null;
        }

        if (empty($errors)) {
            if ($id > 0) {
                executa($connexio,
                    "UPDATE productes SET id_categoria = ?, nom = ?, marca = ?, descripcio = ?, preu = ?, stock = ?, imatge = ?
                     WHERE id_producte = ?",
                    "isssdisi", [$dades['id_categoria'], $dades['nom'], $dades['marca'], $dades['descripcio'], $preu, $stock, $nom_imatge, $id]);
                missatge_flash("success", "Producte modificat correctament.");
            } else {
                executa($connexio,
                    "INSERT INTO productes (id_categoria, nom, marca, descripcio, preu, stock, imatge, data_alta)
                     VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())",
                    "isssdis", [$dades['id_categoria'], $dades['nom'], $dades['marca'], $dades['descripcio'], $preu, $stock, $nom_imatge]);
                missatge_flash("success", "Producte creat correctament.");
            }
            header("Location: admin_productes.php");
            exit;
        }
    }
}

$titol_pagina = $id > 0 ? "Edita el producte" : "Nou producte";
$accio_formulari = "producte_form.php" . ($id > 0 ? "?id=" . $id : "");
require "header.php";
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="admin_productes.php">Productes</a></li>
        <li class="breadcrumb-item active"><?= $id > 0 ? "Edita" : "Nou" ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4"><?= $id > 0 ? "Edita el producte" : "Afegeix un producte" ?></h1>

                <!-- enctype="multipart/form-data" és obligatori per poder pujar fitxers -->
                <form method="post" action="<?= escapa($accio_formulari) ?>" enctype="multipart/form-data" novalidate>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control <?= classe_camp($errors, 'nom') ?>" id="nom" name="nom" value="<?= escapa($dades['nom']) ?>">
                            <?php error_camp($errors, 'nom'); ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="marca" class="form-label">Marca</label>
                            <input type="text" class="form-control <?= classe_camp($errors, 'marca') ?>" id="marca" name="marca" value="<?= escapa($dades['marca']) ?>">
                            <?php error_camp($errors, 'marca'); ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="id_categoria" class="form-label">Categoria</label>
                        <select class="form-select <?= classe_camp($errors, 'id_categoria') ?>" id="id_categoria" name="id_categoria">
                            <option value="0">Tria una categoria...</option>
                            <?php foreach ($categories as $c) { ?>
                                <option value="<?= (int)$c['id_categoria'] ?>" <?= $dades['id_categoria'] == $c['id_categoria'] ? 'selected' : '' ?>><?= escapa($c['nom']) ?></option>
                            <?php } ?>
                        </select>
                        <?php error_camp($errors, 'id_categoria'); ?>
                    </div>

                    <div class="mb-3">
                        <label for="descripcio" class="form-label">Descripció</label>
                        <textarea class="form-control <?= classe_camp($errors, 'descripcio') ?>" id="descripcio" name="descripcio" rows="3"><?= escapa($dades['descripcio']) ?></textarea>
                        <?php error_camp($errors, 'descripcio'); ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="preu" class="form-label">Preu (€)</label>
                            <input type="text" class="form-control <?= classe_camp($errors, 'preu') ?>" id="preu" name="preu" value="<?= escapa($dades['preu']) ?>">
                            <?php error_camp($errors, 'preu'); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label">Estoc (unitats)</label>
                            <input type="text" class="form-control <?= classe_camp($errors, 'stock') ?>" id="stock" name="stock" value="<?= escapa($dades['stock']) ?>">
                            <?php error_camp($errors, 'stock'); ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="imatge" class="form-label">Imatge (JPG, PNG o WEBP, màxim 2 MB)</label>
                        <?php if (!empty($dades['imatge'])) { ?>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <img class="miniatura" src="<?= escapa(imatge_producte($dades['imatge'])) ?>" alt="Imatge actual">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="elimina_imatge" name="elimina_imatge" value="1">
                                    <label class="form-check-label" for="elimina_imatge">Elimina la imatge actual</label>
                                </div>
                            </div>
                        <?php } ?>
                        <input type="file" class="form-control <?= classe_camp($errors, 'imatge') ?>" id="imatge" name="imatge" accept="image/jpeg,image/png,image/webp">
                        <?php error_camp($errors, 'imatge'); ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Desa</button>
                        <a href="admin_productes.php" class="btn btn-outline-secondary">Cancel·la</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require "footer.php"; ?>
