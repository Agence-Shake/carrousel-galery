# Carrousel Galerie

Plugin WordPress permettant d'afficher des carrousels d'images Swiper sur mesure via des shortcodes personnalisables, alimentés par un gestionnaire de galerie natif.

## Version 1.1.0

- 🆕 **Gestionnaire de shortcodes dédié** dans la barre latérale d'administration.
- ⚙️ **Configurations illimitées** : Créez, dupliquez, modifiez ou supprimez des shortcodes avec des réglages distincts.
- 📌 **Post Types par shortcode** : Définissez sur quels types de contenu (Articles, Pages, CPT UI...) chaque shortcode affiche sa metabox de galerie d'images.

---

## Installation

1. Copier le dossier `carrousel-galerie/` dans `wp-content/plugins/`.
2. Activer **Carrousel Galerie** dans l'admin WordPress (Extensions). **Aucune dépendance requise** (pas besoin d'ACF).
3. Accéder au menu **Carrousel Galerie** dans la barre latérale pour créer vos shortcodes.

---

## Administration & Shortcodes

Le menu **Carrousel Galerie** comprend deux sections principales :

### 1. Tous les shortcodes (`Carrousel Galerie → Tous les shortcodes`)
Affiche la liste de tous vos shortcodes créés avec :
- Nom du shortcode.
- Code d'intégration prêt à être copié (`[galerie_projet preset="mon_shortcode"]`).
- Types de contenus (Post Types) associés.
- Actions rapides : **Modifier**, **Dupliquer**, **Supprimer**.

### 2. Édition de shortcode (`Carrousel Galerie → Ajouter un shortcode`)
Pour chaque shortcode, vous pouvez configurer :
- **Informations** : Nom et code d'intégration.
- **Réglages de la metabox** : Choix des types de contenus (Articles, Pages, CPT...) sur lesquels ce shortcode active le gestionnaire d'images.
- **Layout & Comportement** : Slides visibles par breakpoint (desktop/tablette/mobile), espacement, padding latéral, pagination (bullets), flèches de navigation, orientation (horizontal/vertical), hauteur...
- **Comportement global** : Vitesse de transition, slide de démarrage, boucle infinie (loop), autoplay, délai, pause au survol.
- **Couleurs & Style** : Couleurs des bullets, flèches, fond, et CSS personnalisé spécifique.

---

## Utilisation

1. Ouvrez un article, une page ou un CPT configuré dans l'un de vos shortcodes.
2. Remplissez la metabox **Carrousel — Galerie d'images** en sélectionnant et réordonnant vos images via le media uploader natif.
3. Insérez le code du shortcode souhaité dans le contenu :

```text
[galerie_projet preset="hero_home"]
```

> *Remarque : Le shortcode par défaut `[galerie_projet]` sans attribut reste également fonctionnel.*

### Surcharges ponctuelles par attributs

Les réglages enregistrés pour un shortcode peuvent être surchargés directement dans le contenu si nécessaire :

```text
[galerie_projet preset="hero_home" slides_desktop="3" autoplay="1" loop="1"]
```

---

## Architecture

```
carrousel-galerie/
├── carrousel-galerie.php       # En-tête du plugin, enregistrement des assets & hooks
├── includes/
│   ├── metabox.php             # Gestion de la metabox native + media uploader
│   ├── settings.php            # Gestionnaire d'admin, liste & édition des shortcodes
│   └── shortcode.php           # Rendu dynamique du shortcode
├── assets/
│   ├── css/
│   │   ├── carrousel.css       # Styles front-end
│   │   ├── admin-settings.css  # Styles admin
│   │   └── admin-variables.css # Variables CSS admin
│   └── js/
│       ├── carrousel.js        # Script Swiper front-end
│       ├── admin-settings.js   # Script interactif de l'admin
│       └── admin-metabox.js    # Media uploader & réordonnancement
└── README.md
```
