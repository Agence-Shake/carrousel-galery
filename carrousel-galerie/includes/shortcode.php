<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Résout la valeur d'un paramètre par breakpoint, avec fallback vers desktop.
 */
function cg_resolve( $key, $bp, $values ) {
    $v = $values[ $key . '_' . $bp ] ?? '';
    if ( $v === '' || $v === null ) {
        return $values[ $key . '_desktop' ] ?? null;
    }
    return $v;
}

/**
 * Shortcode [galerie_projet] — affiche le carrousel des images de la metabox du post courant.
 *
 * Attributs (optionnels) qui surchargent les réglages globaux — mêmes clés que les réglages.
 */
function cg_galerie_shortcode( $atts ) {
    $raw_atts = is_array( $atts ) ? $atts : [];
    $preset_id = isset( $raw_atts['preset'] ) ? sanitize_key( $raw_atts['preset'] ) : 'default';

    $defaults = cg_get_preset_settings( $preset_id );
    $atts     = shortcode_atts( $defaults, $raw_atts, 'galerie_projet' );

    // Images depuis la metabox. get_the_ID() retourne 0 hors loop (widget, REST…),
    // on fallback sur get_queried_object_id() pour les contextes singulars.
    $post_id = get_the_ID() ?: get_queried_object_id();
    if ( ! $post_id ) {
        return '';
    }
    $ids = get_post_meta( $post_id, CG_META_KEY, true );
    if ( empty( $ids ) || ! is_array( $ids ) ) {
        return '';
    }

    $images = [];
    foreach ( array_map( 'intval', $ids ) as $id ) {
        if ( $id <= 0 ) {
            continue;
        }
        $url = wp_get_attachment_image_url( $id, 'full' );
        if ( ! $url ) {
            continue;
        }
        $images[] = [
            'url' => $url,
            'alt' => get_post_meta( $id, '_wp_attachment_image_alt', true ),
        ];
    }
    if ( count( $images ) === 0 ) {
        return '';
    }

    // Le shortcode s'affiche réellement : on note son préset pour que
    // cg_print_custom_css() n'injecte que le CSS des présets présents sur la page.
    cg_mark_preset_rendered( $preset_id );

    // Branche mode Galerie (grille CSS) — pas de Swiper, rendu plus simple.
    if ( ( $atts['display_mode'] ?? 'carrousel' ) === 'galerie' ) {
        return cg_render_gallery_mode( $images, $atts );
    }

    // Résolution par breakpoint (tablette/mobile vides → fallback desktop).
    $bp_map     = [ 'mobile' => 0, 'tablette' => 768, 'desktop' => 1024 ];
    $breakpoints = [];
    $any_pagination = false;
    $any_fleches    = false;
    $any_vertical   = false;

    foreach ( $bp_map as $bp => $width ) {
        $pag  = (int) cg_resolve( 'pagination', $bp, $atts );
        $nav  = (int) cg_resolve( 'fleches', $bp, $atts );
        $dir  = cg_resolve( 'direction', $bp, $atts );

        // spaceBetween : Swiper accepte un number (px) ou une string avec unité ("20px", "5%"…)
        $space_raw = cg_resolve( 'space', $bp, $atts );
        if ( $space_raw === '' || $space_raw === null ) {
            $space = 0;
        } elseif ( is_numeric( $space_raw ) ) {
            $space = floatval( $space_raw );
        } else {
            $space = (string) $space_raw;
        }

        $cfg = [
            'slidesPerView' => floatval( cg_resolve( 'slides', $bp, $atts ) ),
            'spaceBetween'  => $space,
            'direction'     => ( $dir === 'vertical' ) ? 'vertical' : 'horizontal',
            'pagination'    => $pag ? [ 'el' => '.swiper-pagination', 'clickable' => true ] : false,
            'navigation'    => $nav ? [ 'nextEl' => '.swiper-button-next', 'prevEl' => '.swiper-button-prev' ] : false,
        ];

        $breakpoints[ $width ] = $cfg;
        $any_pagination = $any_pagination || $pag;
        $any_fleches    = $any_fleches    || $nav;
        $any_vertical   = $any_vertical   || ( $dir === 'vertical' );
    }

    // Si une des directions résout en "vertical", on force la boucle infinie à OFF
    // (Swiper gère mal le loop + direction vertical, surtout combiné au peek du wrapper).
    if ( $any_vertical ) {
        $atts['loop'] = 0;
    }

    // Config globale. effect + centered hardcodés depuis le retrait de leurs UI :
    // contrat explicite, plus de dépendance à la valeur sauvegardée.
    $loop_on = (bool) intval( $atts['loop'] );
    $config = [
        'speed'       => intval( $atts['speed'] ),
        'loop'        => $loop_on,
        'effect'      => 'slide',
        'centered'    => false,
        'startSlide'  => max( 0, intval( $atts['start_slide'] ) ),
        'breakpoints' => $breakpoints,
    ];

    // Note : Swiper 9+ ne clone plus les slides en loop mode (réordonnancement DOM
    // uniquement). On laisse Swiper gérer — le peek-gauche au load est corrigé via
    // loopFix() côté JS (afterInit).

    if ( (bool) intval( $atts['autoplay'] ) ) {
        $config['autoplay'] = [
            'delay'                => intval( $atts['autoplay_delay'] ),
            'pauseOnMouseEnter'    => (bool) intval( $atts['pause_hover'] ),
            'disableOnInteraction' => false,
        ];
    }

    // Variables CSS — couleurs.
    $css_vars = [
        '--cg-bullet'        => $atts['color_bullet'] ?? $defaults['color_bullet'],
        '--cg-bullet-active' => $atts['color_bullet_active'] ?? $defaults['color_bullet_active'],
        '--cg-arrow'         => $atts['color_arrow'] ?? $defaults['color_arrow'],
        '--cg-arrow-hover'   => $atts['color_arrow_hover'] ?? $defaults['color_arrow_hover'],
    ];
    $bg_color = $atts['color_bg'] ?? $defaults['color_bg'];
    if ( ! empty( $bg_color ) ) {
        $css_vars['--cg-bg'] = $bg_color;
    }

    // Variables CSS — padding latéral par breakpoint (avec fallback desktop).
    $padding_desktop  = cg_resolve( 'padding', 'desktop', $atts );
    if ( $padding_desktop === '' || $padding_desktop === null ) {
        $padding_desktop = '5vw';
    }
    $padding_tablette = cg_resolve( 'padding', 'tablette', $atts );
    if ( $padding_tablette === '' || $padding_tablette === null ) {
        $padding_tablette = $padding_desktop;
    }
    $padding_mobile = cg_resolve( 'padding', 'mobile', $atts );
    if ( $padding_mobile === '' || $padding_mobile === null ) {
        $padding_mobile = $padding_desktop;
    }
    $css_vars['--cg-wp-desktop']  = $padding_desktop;
    $css_vars['--cg-wp-tablette'] = $padding_tablette;
    $css_vars['--cg-wp-mobile']   = $padding_mobile;

    // Style des contrôles + hauteur max image (responsive : 3 vars par propriété).
    foreach ( [
        'bullet_size'      => [ 'var' => '--cg-bullet-size',     'default' => '9px' ],
        'bullet_gap'       => [ 'var' => '--cg-bullet-gap',      'default' => '1.25rem' ],
        'arrow_size'       => [ 'var' => '--cg-arrow-size',      'default' => '44px' ],
        'image_max_height' => [ 'var' => '--cg-img-max-height',  'default' => '65vh' ],
    ] as $key => $info ) {
        $desktop  = cg_resolve( $key, 'desktop', $atts );
        if ( $desktop === '' || $desktop === null ) {
            $desktop = $info['default'];
        }
        $tablette = cg_resolve( $key, 'tablette', $atts );
        if ( $tablette === '' || $tablette === null ) {
            $tablette = $desktop;
        }
        $mobile = cg_resolve( $key, 'mobile', $atts );
        if ( $mobile === '' || $mobile === null ) {
            $mobile = $desktop;
        }
        $css_vars[ $info['var'] . '-desktop' ]  = $desktop;
        $css_vars[ $info['var'] . '-tablette' ] = $tablette;
        $css_vars[ $info['var'] . '-mobile' ]   = $mobile;
    }

    // Emplacement des flèches — résolution par breakpoint (avec fallback Desktop).
    $arrows_pos_desktop  = cg_resolve( 'arrows_position', 'desktop', $atts ) ?: 'inside';
    $arrows_pos_tablette = cg_resolve( 'arrows_position', 'tablette', $atts ) ?: $arrows_pos_desktop;
    $arrows_pos_mobile   = cg_resolve( 'arrows_position', 'mobile', $atts ) ?: $arrows_pos_desktop;

    // Direction par breakpoint — sert au CSS pour appliquer height: var(--cg-img-max-height)
    // quand l'orientation est verticale (sinon le swiper collapse).
    $direction_desktop  = cg_resolve( 'direction', 'desktop', $atts ) ?: 'horizontal';
    $direction_tablette = cg_resolve( 'direction', 'tablette', $atts ) ?: $direction_desktop;
    $direction_mobile   = cg_resolve( 'direction', 'mobile', $atts ) ?: $direction_desktop;
    // Concat brute. L'esc_attr final est appliqué sur la string complète au moment
    // du rendu (echo esc_attr du style_inline) — pas de double escape.
    $style_inline = '';
    foreach ( $css_vars as $k => $v ) {
        $style_inline .= $k . ':' . $v . ';';
    }

    // Enqueue (les dépendances pull automatiquement cg-swiper + cg-swiper-css).
    wp_enqueue_style( 'cg-carrousel' );
    wp_enqueue_script( 'cg-carrousel' );

    $config_json = wp_json_encode( $config );

    ob_start();
    ?>
    <div class="custom-swiper-wrapper" style="<?php echo esc_attr( $style_inline ); ?>">
        <div class="swiper custom-swiper-galerie"
            data-cg-config="<?php echo esc_attr( $config_json ); ?>"
            data-cg-arrows-desktop="<?php echo esc_attr( $arrows_pos_desktop ); ?>"
            data-cg-arrows-tablette="<?php echo esc_attr( $arrows_pos_tablette ); ?>"
            data-cg-arrows-mobile="<?php echo esc_attr( $arrows_pos_mobile ); ?>"
            data-cg-direction-desktop="<?php echo esc_attr( $direction_desktop ); ?>"
            data-cg-direction-tablette="<?php echo esc_attr( $direction_tablette ); ?>"
            data-cg-direction-mobile="<?php echo esc_attr( $direction_mobile ); ?>">
            <div class="swiper-wrapper">
                <?php foreach ( $images as $image ) :
                    $url = esc_url( $image['url'] );
                    $alt = ! empty( $image['alt'] ) ? esc_attr( $image['alt'] ) : '';
                    ?>
                    <div class="swiper-slide">
                        <img src="<?php echo $url; ?>" alt="<?php echo $alt; ?>" loading="lazy" decoding="async">
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ( $any_pagination ) : ?>
                <div class="swiper-pagination"></div>
            <?php endif; ?>

            <?php if ( $any_fleches ) : ?>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'galerie_projet', 'cg_galerie_shortcode' );
add_shortcode( 'carrousel_galerie', 'cg_galerie_shortcode' );

/**
 * Rendu mode "Galerie" — grille CSS responsive sans Swiper.
 * Réutilise les valeurs slides_* (arrondies à l'entier) pour les colonnes,
 * space_* pour le gap, image_max_height_* pour la hauteur max.
 */
function cg_render_gallery_mode( $images, $atts ) {
    // Colonnes par breakpoint (arrondi int, min 1).
    $cols_desktop  = max( 1, (int) round( floatval( cg_resolve( 'slides', 'desktop', $atts ) ) ) );
    $cols_tablette = max( 1, (int) round( floatval( cg_resolve( 'slides', 'tablette', $atts ) ) ) );
    $cols_mobile   = max( 1, (int) round( floatval( cg_resolve( 'slides', 'mobile', $atts ) ) ) );

    // Gap par breakpoint (réutilise space_*, fallback Desktop).
    $gap_resolve = function ( $bp ) use ( $atts ) {
        $v = cg_resolve( 'space', $bp, $atts );
        if ( $v === '' || $v === null ) {
            return '0';
        }
        return is_numeric( $v ) ? ( (string) floatval( $v ) . 'px' ) : (string) $v;
    };
    $gap_desktop  = $gap_resolve( 'desktop' );
    $gap_tablette = $gap_resolve( 'tablette' ) ?: $gap_desktop;
    $gap_mobile   = $gap_resolve( 'mobile' )   ?: $gap_desktop;

    // Hauteur max image (réutilise les vars existantes).
    $imh_desktop  = cg_resolve( 'image_max_height', 'desktop', $atts ) ?: '65vh';
    $imh_tablette = cg_resolve( 'image_max_height', 'tablette', $atts ) ?: $imh_desktop;
    $imh_mobile   = cg_resolve( 'image_max_height', 'mobile', $atts ) ?: $imh_desktop;

    // Padding latéral du wrapper (cohérent avec mode carrousel).
    $pad_desktop  = cg_resolve( 'padding', 'desktop', $atts ) ?: '5vw';
    $pad_tablette = cg_resolve( 'padding', 'tablette', $atts ) ?: $pad_desktop;
    $pad_mobile   = cg_resolve( 'padding', 'mobile', $atts ) ?: $pad_desktop;

    $css_vars = [
        '--cg-cols-desktop'  => $cols_desktop,
        '--cg-cols-tablette' => $cols_tablette,
        '--cg-cols-mobile'   => $cols_mobile,
        '--cg-cols-gap-desktop'  => $gap_desktop,
        '--cg-cols-gap-tablette' => $gap_tablette,
        '--cg-cols-gap-mobile'   => $gap_mobile,
        '--cg-wp-desktop'  => $pad_desktop,
        '--cg-wp-tablette' => $pad_tablette,
        '--cg-wp-mobile'   => $pad_mobile,
    ];

    // Hauteur image. Un max-height en % se résout contre la cellule de grille, dont la
    // hauteur dépend… de l'image : le navigateur dimensionne la ligne sur la hauteur
    // naturelle puis réduit l'image → grand vide sous chaque image. En mode Galerie,
    // "50%" est donc interprété comme 50% de la LARGEUR (→ aspect-ratio 100 / 50),
    // ce qui donne des vignettes uniformes. Les autres unités restent des max-height.
    foreach ( [ 'desktop' => $imh_desktop, 'tablette' => $imh_tablette, 'mobile' => $imh_mobile ] as $bp => $imh ) {
        if ( preg_match( '/^\s*(\d+(?:\.\d+)?)\s*%\s*$/', (string) $imh, $m ) && floatval( $m[1] ) > 0 ) {
            $css_vars[ '--cg-img-ratio-' . $bp ]      = '100 / ' . floatval( $m[1] );
            $css_vars[ '--cg-img-h-' . $bp ]          = 'auto';
            $css_vars[ '--cg-img-max-height-' . $bp ] = 'none';
        } else {
            $css_vars[ '--cg-img-ratio-' . $bp ]      = 'auto';
            $css_vars[ '--cg-img-h-' . $bp ]          = '100%';
            $css_vars[ '--cg-img-max-height-' . $bp ] = $imh;
        }
    }

    $style_inline = '';
    foreach ( $css_vars as $k => $v ) {
        $style_inline .= $k . ':' . $v . ';';
    }

    // CSS du plugin uniquement — pas de Swiper.
    wp_enqueue_style( 'cg-carrousel' );

    $gallery_id = wp_unique_id( 'cg-gallery-' );

    // Lignes max par breakpoint → nombre d'images visibles = colonnes × lignes.
    // CSS ne sait pas comparer un index à une variable : on génère des règles
    // nth-child scopées sur l'id de cette galerie, une par plage de breakpoint
    // (plages exclusives → pas besoin de "reset" quand un breakpoint est illimité).
    $cols = [ 'mobile' => $cols_mobile, 'tablette' => $cols_tablette, 'desktop' => $cols_desktop ];
    $ranges = [
        'mobile'   => '(max-width: 767px)',
        'tablette' => '(min-width: 768px) and (max-width: 1023px)',
        'desktop'  => '(min-width: 1024px)',
    ];
    $rows_css = '';
    foreach ( $ranges as $bp => $query ) {
        $rows = max( 0, intval( cg_resolve( 'rows', $bp, $atts ) ) );
        if ( $rows === 0 ) {
            continue; // illimité
        }
        $max = $cols[ $bp ] * $rows;
        if ( $max >= count( $images ) ) {
            continue; // toutes les images tiennent déjà
        }
        $rows_css .= '@media ' . $query . '{#' . $gallery_id . ' .cg-gallery img:nth-child(n+' . ( $max + 1 ) . '){display:none}}';
    }

    // Concat string sans whitespace entre les <img> : sinon wpautop() insère des
    // <br> entre eux et casse la grille (une image par ligne au lieu de la grille CSS).
    $imgs_html = '';
    foreach ( $images as $image ) {
        $url = esc_url( $image['url'] );
        $alt = ! empty( $image['alt'] ) ? esc_attr( $image['alt'] ) : '';
        $imgs_html .= '<img src="' . $url . '" alt="' . $alt . '" loading="lazy" decoding="async">';
    }

    return '<div id="' . esc_attr( $gallery_id ) . '" class="custom-swiper-wrapper cg-gallery-mode" style="' . esc_attr( $style_inline ) . '">'
        . ( $rows_css !== '' ? '<style>' . $rows_css . '</style>' : '' )
        . '<div class="cg-gallery">' . $imgs_html . '</div>'
        . '</div>';
}
