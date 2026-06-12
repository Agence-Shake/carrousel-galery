# Carrousel Galerie

Plugin WordPress autonome qui affiche un carrousel d'images Swiper via un shortcode, alimenté par un champ ACF Galerie unique.

## Installation

1. Copier le dossier `carrousel-galerie/` dans `wp-content/plugins/`.
2. Activer "Carrousel Galerie" dans l'admin WordPress (Extensions). **Aucune dépendance** (pas besoin d'ACF).
3. Aller dans **Réglages → Carrousel Galerie** pour configurer.

## Configuration

Tout se passe dans **Réglages → Carrousel Galerie** :

- **Layout & comportement** : slides visibles par breakpoint (desktop/tablette/mobile), espacement, vitesse, autoplay, loop, pagination, flèches, effet, direction, slide de démarrage…
- **Couleurs** : bullets, flèches, fond — injectées en variables CSS sur le carrousel.
- **Post types** : cocher les types de contenu (articles, CPT créés via CPT UI, etc.) sur lesquels le champ Galerie doit apparaître.

## Utilisation

Sur un post du type sélectionné, ouvrir la metabox **Carrousel — Galerie d'images** (en bas de l'éditeur), cliquer sur *Ajouter / modifier les images*, sélectionner plusieurs images via le media uploader natif, les réordonner par glisser-déposer. Puis insérer dans le contenu :

```
[galerie_projet]
```

### Surcharger les réglages par instance

Tous les paramètres globaux peuvent être surchargés via les attributs du shortcode :

```
[galerie_projet desktop="3" tablette="2" mobile="1.2" autoplay="1" autoplay_delay="4000" loop="1" start_slide="2"]
```

Attributs disponibles :

| Attribut | Type | Description |
|---|---|---|
| `desktop`, `tablette`, `mobile` | float | slidesPerView par breakpoint |
| `space_desktop`, `space_tablette`, `space_mobile` | int (px) | espace entre slides |
| `speed` | int (ms) | vitesse de transition |
| `loop` | 0/1 | boucle infinie |
| `pagination` | 0/1 | afficher les bullets |
| `fleches` | 0/1 | afficher les flèches |
| `autoplay` | 0/1 | autoplay |
| `autoplay_delay` | int (ms) | délai autoplay |
| `pause_hover` | 0/1 | pause au survol |
| `effet` | slide / fade / coverflow / cube / flip | effet de transition |
| `direction` | horizontal / vertical | sens du défilement |
| `centered` | 0/1 | slides centrées |
| `start_slide` | int | index de la slide affichée à l'init (0 = première) |

## Swiper

- Si **Elementor** est actif sur le site, le plugin part du principe que Swiper est déjà chargé.
- Sinon, il charge Swiper 11 depuis le CDN officiel.

## Architecture

```
carrousel-galerie/
├── carrousel-galerie.php       # header plugin, enqueue, includes
├── includes/
│   ├── metabox.php             # metabox native + media uploader (post meta _cg_gallery_ids)
│   ├── settings.php            # page de réglages + valeurs par défaut
│   └── shortcode.php           # rendu du shortcode
├── assets/
│   ├── css/
│   │   ├── carrousel.css       # front
│   │   └── admin-metabox.css   # admin
│   └── js/
│       ├── carrousel.js        # front
│       └── admin-metabox.js    # admin (media uploader + sortable)
└── README.md
```

Les comportements (slidesPerView, autoplay, loop…) sont injectés via `data-cg-config` (JSON) sur le conteneur, lu par le JS. Les couleurs passent par des **variables CSS** (`--cg-bullet`, `--cg-bullet-active`, `--cg-arrow`, `--cg-arrow-hover`, `--cg-bg`) injectées en `style=""` inline.
