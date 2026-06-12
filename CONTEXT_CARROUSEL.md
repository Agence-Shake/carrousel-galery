# Plugin WordPress — Carrousel d'images paramétrable (création depuis zéro)

## Objectif

Je veux **créer un vrai plugin WordPress autonome** (un seul dossier) qui affiche un carrousel Swiper via un shortcode sur les articles WordPress et les posts créés avec **CPT UI**.

Aujourd'hui ce n'est PAS un plugin : c'est du code éparpillé —
- le **PHP** est ajouté directement dans le **thème** (functions.php),
- le **JS** et le **CSS** sont ajoutés dans **Elementor Pro** (code custom / widget HTML).

Je veux tout regrouper proprement dans **un seul dossier de plugin** activable depuis l'admin WordPress, qui charge lui-même son JS et son CSS (plus de dépendance à Elementor pour le code).

## Deux évolutions majeures par rapport à l'existant

### 1. Saisie des images : champ Galerie unique

**Problème actuel :** les images sont récupérées dans 10 champs ACF distincts (`image_gallerie_1` à `image_gallerie_10`), donc je dois remplir chaque champ image un par un.

**Ce que je veux :** remplacer ces 10 champs par **un seul champ ACF de type Galerie** (un array d'images, comme le widget "Carrousel de média" d'Elementor), pour uploader/réordonner toutes les images en une fois.

### 2. Carrousel paramétrable comme celui d'Elementor

Le plugin doit exposer des **paramètres de configuration** du carrousel, à l'image du widget carrousel d'Elementor.

#### a) Paramètres de comportement / layout
- **Nombre de diapos visibles** par breakpoint : **desktop**, **tablette**, **mobile** (slidesPerView).
- **Espace entre les diapos** (spaceBetween), idéalement réglable par breakpoint.
- **Pagination** : oui / non (+ idéalement style : bullets / fraction / progressbar).
- **Flèches** de navigation : oui / non.
- **Boucle infinie** (loop) : oui / non.
- **Vitesse** de transition (speed, en ms).
- **Autoplay** : oui / non, + délai (ms), + pause au survol (à prévoir, j'allais l'oublier).
- **Effet de transition** : slide / fade / coverflow… (optionnel).
- **Direction** : horizontal / vertical (optionnel).
- **Hauteur auto** vs hauteur fixe des slides (optionnel).
- **Centrage des slides** (centeredSlides) (optionnel).
- **Drag / swipe** activé sur desktop (optionnel).

#### b) Paramètres de style — couleurs
- **Couleur des flèches** (et couleur au survol).
- **Couleur des bullets de pagination** : inactif et actif.
- **Couleur de fond** du carrousel / des slides (optionnel).
- Possibilité de régler **taille et rayon** des bullets et des flèches (optionnel).

#### c) Paramètres de style — typographie
> Utile seulement si on affiche du texte sur/sous les slides (titre, légende). À clarifier avec moi : est-ce qu'on veut afficher une **légende/titre par image** ? Si oui :
- **Police** (font-family), **taille**, **graisse**, **couleur** du texte.
- **Position** de la légende (sur l'image en overlay / sous l'image).

> **Question à me poser :** veux-tu des légendes/titres par slide ? Si oui, il faudra que le champ Galerie ACF (ou un champ associé) porte ces textes, et le markup + le CSS devront les gérer.

#### Emplacement des paramètres — à discuter
> Plusieurs options possibles —
> (a) une **page de réglages globale** du plugin (Settings API) pour les valeurs par défaut,
> (b) des **attributs de shortcode** (ex. `[galerie_projet desktop="4" tablette="2.2" mobile="1.1" pagination="oui" fleches="non" loop="oui" vitesse="600" autoplay="non"]`),
> (c) des **champs ACF par post** pour régler le carrousel post par post.
> Idéal selon moi : **valeurs par défaut globales (page de réglages)** surchargées par **attributs de shortcode**. Propose-moi l'architecture la plus propre et maintenable.

Le JS Swiper devra lire ces paramètres (data-attributes sur le conteneur, ou objet de config injecté) au lieu d'avoir des valeurs en dur. Les couleurs/typo devraient passer par des **variables CSS** injectées sur le conteneur, pas par du JS.

#### Exemple de config breakpoints que j'utilise actuellement (à rendre paramétrable)

Voici les breakpoints que j'avais codés en dur pour un affichage spécial. Ils doivent devenir le résultat des paramètres, pas du code figé, et servir de **valeurs par défaut** :

- breakpoint 0 (mobile) : slidesPerView 1.1, spaceBetween 12
- breakpoint 768 (tablette) : slidesPerView 2.2, spaceBetween 16
- breakpoint 1024 (desktop) : slidesPerView 4, spaceBetween 20

## Stack / contexte

- WordPress + ACF (Advanced Custom Fields)
- Custom Post Types gérés via **CPT UI**
- Carrousel rendu avec **Swiper**
- Affichage déclenché par un **shortcode** `[galerie_projet]`
- JavaScript : **JS pur, pas de jQuery**
- Elementor Pro est utilisé sur le site, mais le plugin doit être **indépendant d'Elementor** (il charge son propre CSS/JS)

## Code existant (à intégrer dans le plugin)

Les fichiers actuels sont présents dans le projet, Claude Code peut les lire directement :
- le **PHP** (shortcode + logique) — actuellement dans functions.php du thème
- le **JS** (init Swiper, avec les breakpoints en dur ci-dessus) — actuellement dans Elementor
- le **CSS** (styles du carrousel) — actuellement dans Elementor

> Le PHP actuel boucle de 1 à 10 sur `get_field('image_gallerie_' . $i)` et construit un tableau `$images`. C'est cette logique de récupération que je veux remplacer par la lecture d'un champ galerie unique.

## Ce que j'attends de toi

1. **Structurer le plugin dans un seul dossier**, par ex. :

   ```
   carrousel-galerie/
   ├── carrousel-galerie.php        (fichier principal : header de plugin, hooks)
   ├── includes/
   │   ├── shortcode.php            (logique + rendu du shortcode, lecture des paramètres)
   │   ├── acf-fields.php           (déclaration du champ ACF galerie)
   │   └── settings.php             (page de réglages globale)
   ├── assets/
   │   ├── css/carrousel.css        (mon CSS existant + variables CSS pour couleurs/typo)
   │   └── js/carrousel.js          (mon JS existant, rendu paramétrable)
   └── README.md
   ```
   Adapte la structure si tu as mieux, mais garde le principe : un dossier unique, activable.

2. **Fichier principal du plugin**
   - Header de plugin valide (Plugin Name, Description, Version, etc.).
   - `enqueue` du CSS et du JS du plugin (avec gestion de Swiper — me dire si Swiper doit être chargé par le plugin via CDN/local ou s'il est déjà fourni par ailleurs).
   - Inclure les fichiers de `includes/`.

3. **Définir le champ ACF Galerie**
   - En PHP via `acf_add_local_field_group()` (versionnable, dans le plugin), de préférence.
   - Champ de type **Gallery** (return format = Array), nommé par ex. `gallerie_carrousel`.
   - L'assigner aux **articles (post)** ET aux **CPT créés via CPT UI** (location rules sur les bons post types).
   - Me dire s'il faut prévoir des **légendes/titres par image** (voir section typo).

4. **Gérer les paramètres du carrousel**
   - Me proposer l'architecture (réglages globaux + attributs de shortcode, voir plus haut) et la justifier brièvement.
   - Couvrir : layout/comportement (4a), couleurs (4b), typo (4c).
   - Prévoir des **valeurs par défaut** propres (reprenant mes breakpoints actuels).
   - Transmettre la config :
     - **comportement** → au JS (data-attributes sur le conteneur, ou objet JSON via wp_localize_script / wp_add_inline_script).
     - **couleurs / typo** → en **variables CSS** injectées (style inline sur le conteneur ou `wp_add_inline_style`), consommées par `carrousel.css`.

5. **Adapter le PHP du shortcode**
   - Remplacer la boucle `for 1..10` + `get_field('image_gallerie_' . $i)` par la lecture du champ galerie unique (array d'images).
   - Conserver le `array_filter` sur `url`, l'`ob_start`, le markup `swiper-slide`, le `loading="lazy"` / `decoding="async"`, et le `return ''` si aucune image.
   - Garder la même structure HTML de base, en ajoutant ce qu'il faut pour les paramètres (data-attributes, variables CSS inline, conteneurs pagination/flèches conditionnels, légendes si retenues).

6. **Adapter le JS**
   - Lire les paramètres au lieu d'avoir breakpoints / loop / speed / pagination / arrows / autoplay en dur.
   - Initialiser Swiper avec la config dynamique. Gérer le cas de **plusieurs carrousels** sur une même page (config indépendante par instance).

7. **CSS**
   - Refactor léger pour consommer des **variables CSS** (couleurs flèches/bullets, typo des légendes), avec des valeurs de repli.

8. **(Optionnel) Plan de migration**
   - Une fonction one-shot pour migrer les anciennes données `image_gallerie_1..10` vers le nouveau champ galerie, pour les posts déjà remplis.

## Contraintes

- Indiquer pour chaque bloc de code **le nom exact du fichier** (et son chemin dans le dossier du plugin) à créer/modifier.
- JS en pur (pas de jQuery).
- Le plugin doit fonctionner **sans Elementor**.
- Couleurs/typo via **variables CSS**, pas en dur dans le JS.
- Ne pas réécrire le CSS/JS si ce n'est pas nécessaire : les reprendre dans `assets/`, en ne les modifiant que pour la paramétrisation.
- Procéder par étapes : commence par lire les fichiers existants et me confirmer ta compréhension avant de proposer le code.

## Première étape demandée

Lis les fichiers existants (PHP/JS/CSS), confirme-moi ta compréhension de la logique actuelle, pose-moi tes questions (notamment sur les légendes/typo), puis propose : (1) l'arborescence finale, (2) ta reco sur l'emplacement des paramètres, (3) le fichier principal, (4) le champ ACF Galerie, (5) le shortcode + JS + CSS paramétrables.
