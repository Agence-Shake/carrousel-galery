# Carrousel Galerie

Plugin WordPress autonome (version 1.1.5) qui permet de créer et gérer plusieurs shortcodes de carrousels Swiper indépendants avec leurs propres réglages et styles.

## Installation

1. Copier le dossier `carrousel-galerie/` dans `wp-content/plugins/`.
2. Activer **Carrousel Galerie** dans l'administration WordPress (**Extensions**). Aucune dépendance externe requise.
3. Un nouveau menu principal **Carrousel Galerie** apparaît dans la barre latérale d'administration.

## Gestion des Shortcodes

L'extension propose une interface d'administration complète basée sur les standards WordPress :

- **Tous les shortcodes** : Liste de l'ensemble de vos shortcodes configurés avec boutons d'action rapide (*Modifier*, *Dupliquer*, *Supprimer*) et bouton de copie en 1 clic.
- **Ajouter / Modifier un shortcode** : Interface permettant de configurer un shortcode spécifique :
  - **Nom du shortcode**
  - **Contenus associés (Post Types)** : Choix des types de contenus (Articles, Pages, CPT UI...) sur lesquels afficher la metabox de gestion des images pour ce shortcode.
  - **Layout & comportement** : Nombre de slides visibles par breakpoint (desktop/tablette/mobile), espacements avec unités (px, rem, %, vw...), padding latéral, hauteur, pagination, flèches de navigation, direction défilement (horizontal/vertical)...
  - **Animation & Autoplay** : Vitesse, boucle infinie, autoplay avec délai et pause au survol, slide de démarrage...
  - **Couleurs & CSS** : Couleurs des bullets, flèches et fond + zone de CSS personnalisé propre au shortcode.

## Utilisation

1. Sur un article ou une page du type de contenu associé à votre shortcode, ouvrez la metabox **Carrousel — Galerie d'images** (en bas de l'éditeur).
2. Cliquez sur **Ajouter / modifier les images**, sélectionnez vos visuels et réordonnez-les par glisser-déposer.
3. Insérez le code du shortcode souhaité dans votre contenu :

```text
[galerie_projet preset="votre_preset_id"]
```

> **Note :** Si aucun identifiant n'est spécifié (`[galerie_projet]`), les réglages du shortcode par défaut sont utilisés.

### Surcharge d'attributs par instance

Tous les shortcodes peuvent être ponctuellement surchargés via des attributs en ligne :

```text
[galerie_projet preset="hero" slides_desktop="3" autoplay="1" speed="500"]
```

## Architecture

```text
carrousel-galerie/
├── carrousel-galerie.php       # Header plugin v1.1.1, enqueue, hook footer custom CSS
├── includes/
│   ├── settings.php            # Menu principal Admin, vues Liste & Édition des shortcodes
│   ├── metabox.php             # Metabox native + media uploader (post meta _cg_gallery_ids)
│   └── shortcode.php           # Traitement et rendu front-end des shortcodes
├── assets/
│   ├── css/
│   │   ├── carrousel.css       # Styles front-end
│   │   ├── admin-settings.css  # Styles admin (cartes, pickers, boutons copier)
│   │   ├── admin-variables.css # Variables CSS admin
│   │   └── admin-metabox.css   # Styles metabox uploader
│   └── js/
│       ├── carrousel.js        # Script Swiper front-end
│       ├── admin-settings.js   # Script interactif admin (units, presets UI)
│       └── admin-metabox.js    # Drag & drop media uploader admin
└── README.md
```
