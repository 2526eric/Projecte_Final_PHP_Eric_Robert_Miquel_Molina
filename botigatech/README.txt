============================================================
 BOTIGATECH - Projecte Final Lliure (PHP)
============================================================

Autors: Eric i Miquel Molina
Cicle: ASIX - ITIC Barcelona

------------------------------------------------------------
1. DESCRIPCIÓ
------------------------------------------------------------
BotigaTech és una botiga online de components d'informàtica
(processadors, targetes gràfiques, memòria RAM, emmagatzematge i
perifèrics). Els clients es registren, busquen productes al
catàleg, els valoren, els afegeixen a la cistella i fan comandes.
L'administrador gestiona els productes i les categories, canvia
l'estat de les comandes i consulta unes estadístiques de la botiga.

------------------------------------------------------------
2. USUARIS DE PROVA
------------------------------------------------------------
Rol administrador:
    admin@test.com   /  admin123

Rol client:
    usuari@test.com  /  12345678
    pau@test.com     /  12345678
    laia@test.com    /  12345678

------------------------------------------------------------
3. FUNCIONALITATS PRINCIPALS
------------------------------------------------------------
- Registre amb validacions (camps buits, email vàlid, email
  repetit, contrasenyes que coincideixen, mínim 6 caràcters).
- Login amb contrasenyes encriptades (password_hash /
  password_verify) i logout que destrueix la sessió.
- Dos rols (client i administrador) amb pàgina d'inici
  diferent (acces.php) i menú diferent al header.
- Vista client per a l'administrador: amb el botó "Vista client"
  del header, l'administrador veu la botiga com un client (catàleg
  amb compra, cistella, les seves comandes) sense deixar de ser
  administrador. Amb "Vista admin" torna al panell.
- Header dinàmic: si no s'ha fet login mostra el nom de
  l'aplicació; si s'ha fet login mostra nom, email i rol.
- Control d'accés: les pàgines internes redirigeixen a login.php
  si no hi ha sessió, i les d'administrador donen error 403 als
  clients.
- CRUD complet de PRODUCTES (només administrador).
- CRUD complet de CATEGORIES (només administrador).
- Comandes: el client les crea des de la cistella, les consulta
  i pot cancel·lar les pendents; l'administrador les veu totes i
  en canvia l'estat (pendent, enviada, entregada, cancel·lada).
  Cancel·lar una comanda retorna l'estoc.
- Cerca i filtres al catàleg: per nom o marca, categoria, preu
  màxim i només disponibles.
- Ordenació: el catàleg mostra primer els productes disponibles
  i després per preu (també es pot ordenar per altres criteris).
  Les comandes surten primer pendents, després enviades, etc.
- Gestió d'errors amb error.php (missatges específics: accés
  denegat, no trobat, estoc insuficient, no es pot eliminar si té
  elements relacionats, etc.) i validacions a tots els formularis.
- Seguretat: consultes amb prepared statements (mysqli),
  sortides escapades amb htmlspecialchars.

------------------------------------------------------------
4. FUNCIONALITATS EXTRA
------------------------------------------------------------
- Paginació al catàleg (6 productes per pàgina).
- Sistema de valoracions amb estrelles (1-5) i comentari.
- Estadístiques per a l'administrador (admin_estadistiques.php):
  vendes, comandes per estat, productes més venuts i més
  valorats.
- Càrrega d'imatges dels productes (jpg, png o webp, màxim 2 MB,
  validades amb getimagesize).
- Filtres múltiples combinats al catàleg.

------------------------------------------------------------
5. INSTAL·LACIÓ
------------------------------------------------------------
Requisits: Laragon (Apache + PHP + MySQL) o XAMPP.

1. Copiar la carpeta "botigatech" dins de la carpeta "www"
   (Laragon) o "htdocs" (XAMPP).
2. Importar el fitxer database.sql des de phpMyAdmin (o
   HeidiSQL). El fitxer crea la base de dades "botigatech"
   (si ja existeix l'esborra i la torna a crear) amb dades
   d'exemple.
3. Si cal, revisar les dades de connexió a conexio.php
   (per defecte: servidor localhost, usuari root, sense
   contrasenya, base de dades botigatech).
4. Obrir al navegador:
       http://localhost/botigatech/
   i entrar des de la pàgina "Login".

Nota: Bootstrap està inclòs dins de la carpeta assets, així que
l'aplicació funciona sense connexió a internet. La carpeta
uploads ha de tenir permisos d'escriptura (a Laragon/XAMPP en
local ja els té).

------------------------------------------------------------
6. ESTRUCTURA DE FITXERS
------------------------------------------------------------
database.sql          Base de dades (DROP, CREATE, INSERT)
index.html            Pàgina de presentació
conexio.php           Connexió a la base de dades (mysqli)
funcions.php          Funcions comunes (consultes, sessions...)
header.php/footer.php Capçalera dinàmica i peu de pàgina
error.php             Gestió de tots els errors
registre.php, login.php, logout.php, acces.php, canvia_vista.php
cataleg.php, producte.php, cistella.php, comandes.php, comanda.php
admin_productes.php, producte_form.php, producte_eliminar.php
admin_categories.php, categoria_form.php, categoria_eliminar.php
admin_comandes.php, admin_estadistiques.php
assets/               CSS, JS (Bootstrap) i imatge per defecte
uploads/              Imatges pujades pels administradors
