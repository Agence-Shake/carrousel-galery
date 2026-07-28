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
