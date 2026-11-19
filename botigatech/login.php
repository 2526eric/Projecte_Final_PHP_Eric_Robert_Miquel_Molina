<?php
// ============================================================
// login.php - Inici de sessió
// Comprova el correu i la contrasenya i guarda les dades de l'usuari a $_SESSION
// ============================================================
session_start();
require_once "conexio.php";
require_once "funcions.php";

// Si ja hi ha sessió iniciada, anem directament a la pàgina principal
if (esta_logat()) {
    header("Location: acces.php");
    exit;
}

$errors = [];
$email  = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email       = rep($_POST, 'email');
    $contrasenya = rep($_POST, 'contrasenya');

    // --- Validacions bàsiques ---
    if ($email === "") {
        $errors['email'] = "Has d'escriure el correu electrònic.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "El format del correu electrònic no és vàlid.";
    }
    if ($contrasenya === "") {
        $errors['contrasenya'] = "Has d'escriure la contrasenya.";
    }

    // --- Comprovem l'usuari a la base de dades ---
    if (empty($errors)) {
        $usuari = consulta_una($connexio,
            "SELECT id_usuari, nom, cognoms, email, contrasenya, rol FROM usuaris WHERE email = ?",
            "s", [$email]);

        if ($usuari === null) {
            $errors['email'] = "No existeix cap usuari amb aquest correu.";
        } elseif (!password_verify($contrasenya, $usuari['contrasenya'])) {
            // password_verify compara la contrasenya escrita amb el hash guardat
            $errors['contrasenya'] = "La contrasenya no és correcta.";
        } else {
            // Login correcte: canviem l'identificador de sessió (seguretat)
            // i guardem les dades de l'usuari a la sessió
            session_regenerate_id(true);
            $_SESSION['id_usuari'] = (int)$usuari['id_usuari'];
            $_SESSION['nom']       = $usuari['nom'];
            $_SESSION['cognoms']   = $usuari['cognoms'];
            $_SESSION['email']     = $usuari['email'];
            $_SESSION['rol']       = $usuari['rol'];
            $_SESSION['cistella']  = [];   // cistella buida: id_producte => quantitat

            header("Location: acces.php");
            exit;
        }
    }
}

$titol_pagina = "Inicia sessió";
require "header.php";

// Missatge quan venim de logout.php
if (isset($_GET['sortit'])) {
    echo '<div class="alert alert-info">Has tancat la sessió correctament.</div>';
}
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4">Inicia sessió</h1>

                <form method="post" action="login.php" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Correu electrònic</label>
                        <input type="email" class="form-control <?= classe_camp($errors, 'email') ?>" id="email" name="email" value="<?= escapa($email) ?>">
                        <?php error_camp($errors, 'email'); ?>
                    </div>

                    <div class="mb-4">
                        <label for="contrasenya" class="form-label">Contrasenya</label>
                        <input type="password" class="form-control <?= classe_camp($errors, 'contrasenya') ?>" id="contrasenya" name="contrasenya">
                        <?php error_camp($errors, 'contrasenya'); ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Entra</button>
                </form>

                <p class="text-center mt-3 mb-0">No tens compte? <a href="registre.php">Registra't</a></p>
            </div>
        </div>
    </div>
</div>

<?php require "footer.php"; ?>
