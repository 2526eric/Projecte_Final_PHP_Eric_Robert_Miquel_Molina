<?php
// ============================================================
// registre.php - Formulari per crear un compte nou (rol client)
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";

// Si ja has iniciat sessió, no cal registrar-se
if (esta_logat()) {
    header("Location: acces.php");
    exit;
}

$errors  = [];       // Aquí guardem un missatge per cada camp que falli
$nom     = "";
$cognoms = "";
$email   = "";

// Només processem el formulari quan s'ha enviat (mètode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom        = rep($_POST, 'nom');
    $cognoms    = rep($_POST, 'cognoms');
    $email      = rep($_POST, 'email');
    $contrasenya = rep($_POST, 'contrasenya');
    $repeteix    = rep($_POST, 'repeteix');

    // --- Validacions ---
    if ($nom === "") {
        $errors['nom'] = "El nom no pot estar buit.";
    } elseif (mb_strlen($nom) > 50) {
        $errors['nom'] = "El nom no pot tenir més de 50 caràcters.";
    }

    if ($cognoms === "") {
        $errors['cognoms'] = "Els cognoms no poden estar buits.";
    } elseif (mb_strlen($cognoms) > 80) {
        $errors['cognoms'] = "Els cognoms no poden tenir més de 80 caràcters.";
    }

    if ($email === "") {
        $errors['email'] = "El correu electrònic no pot estar buit.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "El format del correu electrònic no és vàlid.";
    } elseif (mb_strlen($email) > 100) {
        $errors['email'] = "El correu no pot tenir més de 100 caràcters.";
    } else {
        // Comprovem que no hi hagi ja un usuari amb aquest correu (duplicat)
        $existent = consulta_una($connexio,
            "SELECT id_usuari FROM usuaris WHERE email = ?", "s", [$email]);
        if ($existent !== null) {
            $errors['email'] = "Ja hi ha un usuari registrat amb aquest correu.";
        }
    }

    if ($contrasenya === "") {
        $errors['contrasenya'] = "La contrasenya no pot estar buida.";
    } elseif (strlen($contrasenya) < 6) {
        $errors['contrasenya'] = "La contrasenya ha de tenir almenys 6 caràcters.";
    }

    if ($repeteix === "") {
        $errors['repeteix'] = "Has de repetir la contrasenya.";
    } elseif ($contrasenya !== $repeteix) {
        $errors['repeteix'] = "Les dues contrasenyes no coincideixen.";
    }

    // --- Si tot és correcte, guardem l'usuari ---
    if (empty($errors)) {
        // Mai guardem la contrasenya tal qual: la encriptem amb password_hash
        $hash = password_hash($contrasenya, PASSWORD_DEFAULT);

        executa($connexio,
            "INSERT INTO usuaris (nom, cognoms, email, contrasenya, rol) VALUES (?, ?, ?, ?, 'client')",
            "ssss", [$nom, $cognoms, $email, $hash]);

        missatge_flash("success", "Compte creat correctament. Ja pots iniciar sessió.");
        header("Location: login.php");
        exit;
    }
}

$titol_pagina = "Registre";
require "header.php";
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4">Crea el teu compte</h1>

                <form method="post" action="registre.php" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control <?= classe_camp($errors, 'nom') ?>" id="nom" name="nom" value="<?= escapa($nom) ?>">
                            <?php error_camp($errors, 'nom'); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cognoms" class="form-label">Cognoms</label>
                            <input type="text" class="form-control <?= classe_camp($errors, 'cognoms') ?>" id="cognoms" name="cognoms" value="<?= escapa($cognoms) ?>">
                            <?php error_camp($errors, 'cognoms'); ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Correu electrònic</label>
                        <input type="email" class="form-control <?= classe_camp($errors, 'email') ?>" id="email" name="email" value="<?= escapa($email) ?>">
                        <?php error_camp($errors, 'email'); ?>
                    </div>

                    <div class="mb-3">
                        <label for="contrasenya" class="form-label">Contrasenya</label>
                        <input type="password" class="form-control <?= classe_camp($errors, 'contrasenya') ?>" id="contrasenya" name="contrasenya">
                        <?php error_camp($errors, 'contrasenya'); ?>
                    </div>

                    <div class="mb-4">
                        <label for="repeteix" class="form-label">Repeteix la contrasenya</label>
                        <input type="password" class="form-control <?= classe_camp($errors, 'repeteix') ?>" id="repeteix" name="repeteix">
                        <?php error_camp($errors, 'repeteix'); ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Registra'm</button>
                </form>

                <p class="text-center mt-3 mb-0">Ja tens compte? <a href="login.php">Inicia sessió</a></p>
            </div>
        </div>
    </div>
</div>

<?php require "footer.php"; ?>
