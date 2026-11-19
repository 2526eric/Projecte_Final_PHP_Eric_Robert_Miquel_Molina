<?php
// ============================================================
// funcions.php - Funcions que fan servir moltes pàgines
// Així no repetim el mateix codi a cada fitxer.
// ============================================================


// ------------------------------------------------------------
// 1. DADES QUE ENVIA L'USUARI
// ------------------------------------------------------------

// Agafa un valor de $_GET o $_POST de forma segura.
// Sempre retorna un text (sense espais als extrems), encara que el valor no existeixi.
function rep($font, $clau) {
    if (isset($font[$clau]) && is_string($font[$clau])) {
        return trim($font[$clau]);
    }
    return "";
}

// Escapa un text abans de mostrar-lo a l'HTML (evita atacs XSS)
function escapa($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}


// ------------------------------------------------------------
// 2. SESSIONS I CONTROL D'ACCÉS
// ------------------------------------------------------------

// Comprova si hi ha un usuari amb la sessió iniciada
function esta_logat() {
    return isset($_SESSION['id_usuari']);
}

// Si no hi ha sessió, enviem l'usuari al login
function requereix_login() {
    if (!esta_logat()) {
        header("Location: login.php");
        exit;
    }
}

// Només deixa passar els usuaris del rol indicat ("client" o "administrador")
function requereix_rol($rol) {
    requereix_login();
    if ($_SESSION['rol'] !== $rol) {
        redirigeix_error("acces_denegat");
    }
}

// Diu si s'ha de mostrar la web "com a client".
// Passa quan l'usuari és client, o quan és administrador i ha activat la "vista client"
// amb el botó del header (es guarda a la sessió: $_SESSION['vista_client']).
// El rol real NO canvia: un administrador continua sent administrador.
function vista_client() {
    if (!esta_logat()) {
        return false;
    }
    if ($_SESSION['rol'] === 'client') {
        return true;
    }
    return !empty($_SESSION['vista_client']);
}

// Per a les pàgines de client (cistella, les meves comandes...):
// deixa passar els clients i els administradors que estan en vista client.
function requereix_vista_client() {
    requereix_login();
    if (!vista_client()) {
        redirigeix_error("acces_denegat");
    }
}

// Envia l'usuari a error.php amb el codi de l'error
// i, si volem, la pàgina a la qual pot tornar.
function redirigeix_error($codi, $tornar = "") {
    $url = "error.php?codi=" . urlencode($codi);
    if ($tornar !== "") {
        $url .= "&tornar=" . urlencode($tornar);
    }
    header("Location: " . $url);
    exit;
}

// Comprova que una adreça de retorn és segura (un fitxer .php nostre, amb paràmetres opcionals)
// Així evitem que algú ens faci redirigir a una web externa.
function adreca_segura($adreca, $per_defecte) {
    if (preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&%.+\-]*)?$/', $adreca)) {
        return $adreca;
    }
    return $per_defecte;
}


// ------------------------------------------------------------
// 3. MISSATGES TEMPORALS (flash)
// Es guarden a la sessió i es mostren una sola vegada.
// ------------------------------------------------------------

function missatge_flash($tipus, $text) {
    $_SESSION['flash'] = ['tipus' => $tipus, 'text' => $text];
}

function mostra_flash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . escapa($f['tipus']) . ' alert-dismissible fade show" role="alert">';
        echo escapa($f['text']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tanca"></button>';
        echo '</div>';
    }
}


// ------------------------------------------------------------
// 4. BASE DE DADES (sentències preparades)
// Les sentències preparades eviten la injecció SQL: les dades
// de l'usuari mai es barregen amb el text de la consulta.
// $tipus indica el tipus de cada ? : i = enter, d = decimal, s = text
// ------------------------------------------------------------

// Prepara una consulta, hi enllaça els valors i l'executa
function prepara_i_executa($connexio, $sql, $tipus, $valors) {
    $sentencia = mysqli_prepare($connexio, $sql);
    if ($sentencia === false) {
        redirigeix_error("bd");
    }
    if ($tipus !== "") {
        mysqli_stmt_bind_param($sentencia, $tipus, ...$valors);
    }
    if (!mysqli_stmt_execute($sentencia)) {
        redirigeix_error("bd");
    }
    return $sentencia;
}

// SELECT: retorna totes les files com una llista
function consulta($connexio, $sql, $tipus = "", $valors = []) {
    $sentencia = prepara_i_executa($connexio, $sql, $tipus, $valors);
    $resultat  = mysqli_stmt_get_result($sentencia);
    $files     = mysqli_fetch_all($resultat, MYSQLI_ASSOC);
    mysqli_stmt_close($sentencia);
    return $files;
}

// SELECT: retorna només la primera fila (o null si no n'hi ha cap)
function consulta_una($connexio, $sql, $tipus = "", $valors = []) {
    $files = consulta($connexio, $sql, $tipus, $valors);
    return count($files) > 0 ? $files[0] : null;
}

// INSERT, UPDATE o DELETE: retorna quantes files s'han modificat
function executa($connexio, $sql, $tipus = "", $valors = []) {
    $sentencia = prepara_i_executa($connexio, $sql, $tipus, $valors);
    $afectades = mysqli_stmt_affected_rows($sentencia);
    mysqli_stmt_close($sentencia);
    return $afectades;
}


// ------------------------------------------------------------
// 5. FORMAT DE LES DADES
// ------------------------------------------------------------

function formata_preu($preu) {
    return number_format((float)$preu, 2, ',', '.') . " €";
}

function formata_data($data) {
    return date("d/m/Y H:i", strtotime($data));
}

// Text amb accents per a cada estat de la comanda
function text_estat($estat) {
    $textos = [
        'pendent'   => 'Pendent',
        'enviada'   => 'Enviada',
        'entregada' => 'Entregada',
        'cancelada' => 'Cancel·lada'
    ];
    return $textos[$estat] ?? $estat;
}

// Color de Bootstrap per a cada estat
function classe_estat($estat) {
    $classes = [
        'pendent'   => 'warning text-dark',
        'enviada'   => 'info text-dark',
        'entregada' => 'success',
        'cancelada' => 'secondary'
    ];
    return $classes[$estat] ?? 'secondary';
}

// Dibuixa les estrelles segons la mitjana (de 0 a 5)
function estrelles($mitjana) {
    $plenes = (int)round($mitjana);
    return str_repeat("★", $plenes) . str_repeat("☆", 5 - $plenes);
}

// Retorna la ruta de la imatge d'un producte, o la imatge per defecte si no en té
function imatge_producte($imatge) {
    if ($imatge && file_exists(__DIR__ . "/uploads/" . $imatge)) {
        return "uploads/" . $imatge;
    }
    return "assets/img/sense-imatge.svg";
}


// ------------------------------------------------------------
// 6. FORMULARIS: mostrar errors de validació
// ------------------------------------------------------------

// Afegeix la classe "is-invalid" de Bootstrap si el camp té error
function classe_camp($errors, $camp) {
    return isset($errors[$camp]) ? "is-invalid" : "";
}

// Mostra el missatge d'error sota el camp
function error_camp($errors, $camp) {
    if (isset($errors[$camp])) {
        echo '<div class="invalid-feedback">' . escapa($errors[$camp]) . '</div>';
    }
}


// ------------------------------------------------------------
// 7. MENÚ DE NAVEGACIÓ
// ------------------------------------------------------------

// Dibuixa un enllaç del menú i el marca com a actiu si és la pàgina actual
function enllac_nav($fitxers, $text) {
    $actual = basename($_SERVER['PHP_SELF']);
    $fitxers = (array)$fitxers;
    $classe = in_array($actual, $fitxers) ? "nav-link active" : "nav-link";
    echo '<li class="nav-item"><a class="' . $classe . '" href="' . $fitxers[0] . '">' . $text . '</a></li>';
}


// ------------------------------------------------------------
// 8. TARGETA DE PRODUCTE (es fa servir al catàleg i a la pàgina d'inici)
// ------------------------------------------------------------

function mostra_targeta_producte($p, $pot_comprar, $tornar) {
    $disponible = $p['stock'] > 0;
    echo '<div class="col-sm-6 col-lg-4">';
    echo '<div class="card h-100 targeta-producte">';
    echo '<img src="' . escapa(imatge_producte($p['imatge'])) . '" class="card-img-top imatge-producte" alt="' . escapa($p['nom']) . '">';
    echo '<div class="card-body d-flex flex-column">';
    echo '<span class="badge bg-light text-dark border align-self-start mb-2">' . escapa($p['categoria']) . '</span>';
    echo '<h5 class="card-title">' . escapa($p['nom']) . '</h5>';
    echo '<p class="text-muted mb-1">' . escapa($p['marca']) . '</p>';
    if (isset($p['num_valoracions']) && $p['num_valoracions'] > 0) {
        echo '<p class="estrelles mb-1">' . estrelles($p['mitjana']) . ' <small class="text-muted">(' . (int)$p['num_valoracions'] . ')</small></p>';
    } else {
        echo '<p class="text-muted small mb-1">Sense valoracions</p>';
    }
    echo '<p class="fs-5 fw-bold mb-2">' . formata_preu($p['preu']) . '</p>';
    if ($disponible) {
        echo '<span class="badge bg-success align-self-start mb-3">Disponible (' . (int)$p['stock'] . ')</span>';
    } else {
        echo '<span class="badge bg-danger align-self-start mb-3">Esgotat</span>';
    }
    echo '<div class="mt-auto d-flex gap-2">';
    echo '<a class="btn btn-outline-primary btn-sm" href="producte.php?id=' . (int)$p['id_producte'] . '">Veure</a>';
    if ($pot_comprar && $disponible) {
        echo '<form method="post" action="cistella.php" class="m-0">';
        echo '<input type="hidden" name="accio" value="afegir">';
        echo '<input type="hidden" name="id_producte" value="' . (int)$p['id_producte'] . '">';
        echo '<input type="hidden" name="quantitat" value="1">';
        echo '<input type="hidden" name="tornar" value="' . escapa($tornar) . '">';
        echo '<button type="submit" class="btn btn-primary btn-sm">Afegeix a la cistella</button>';
        echo '</form>';
    }
    echo '</div>';
    echo '</div></div></div>';
}


// ------------------------------------------------------------
// 9. COMANDES
// ------------------------------------------------------------

// Cancel·la una comanda i torna les unitats al estoc.
// Es fa dins d'una transacció: o es fa tot, o no es fa res.
function cancela_comanda($connexio, $id_comanda) {
    mysqli_begin_transaction($connexio);

    $comanda = consulta_una($connexio,
        "SELECT estat FROM comandes WHERE id_comanda = ? FOR UPDATE", "i", [$id_comanda]);

    // Si no existeix o ja estava cancel·lada, no fem res
    if ($comanda === null || $comanda['estat'] === 'cancelada') {
        mysqli_rollback($connexio);
        return false;
    }

    // Tornem cada producte a l'estoc
    $linies = consulta($connexio,
        "SELECT id_producte, quantitat FROM detalls_comanda WHERE id_comanda = ?", "i", [$id_comanda]);
    foreach ($linies as $linia) {
        executa($connexio,
            "UPDATE productes SET stock = stock + ? WHERE id_producte = ?",
            "ii", [$linia['quantitat'], $linia['id_producte']]);
    }

    executa($connexio, "UPDATE comandes SET estat = 'cancelada' WHERE id_comanda = ?", "i", [$id_comanda]);

    mysqli_commit($connexio);
    return true;
}
