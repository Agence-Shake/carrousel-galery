<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Breakpoints supportés et leurs labels.
 */
function cg_breakpoints() {
    return [
        'desktop'  => [ 'label' => 'Desktop',  'width' => 1024 ],
        'tablette' => [ 'label' => 'Tablette', 'width' => 768 ],
        'mobile'   => [ 'label' => 'Mobile',   'width' => 0 ],
    ];
}

/**
 * Valeurs par défaut globales.
 * Pour tablette / mobile : valeur vide = "hériter du desktop".
 */
function cg_default_settings() {
    return [
        // Slides visibles par breakpoint — tablette/mobile vides = hérite de Desktop.
        'slides_desktop'  => 4,
        'slides_tablette' => '',
        'slides_mobile'   => '',

        // Espace entre slides (string avec unité : "20px", "1em", "5%", var custom…)
        'space_desktop'  => '20px',
        'space_tablette' => '',
        'space_mobile'   => '',

        // Padding latéral du wrapper (preview slides précédente/suivante)
        'padding_desktop'  => '20px',
        'padding_tablette' => '',
        'padding_mobile'   => '',

        // Pagination — desktop: 0/1 ; tablette/mobile: ''|'0'|'1'
        'pagination_desktop'  => 0,
        'pagination_tablette' => '',
        'pagination_mobile'   => '',

        // Flèches
        'fleches_desktop'  => 0,
        'fleches_tablette' => '',
        'fleches_mobile'   => '',

        // Direction — desktop: horizontal|vertical ; tablette/mobile: ''|horizontal|vertical
        'direction_desktop'  => 'horizontal',
        'direction_tablette' => '',
        'direction_mobile'   => '',

        // Emplacement des flèches — desktop: inside|outside ; tablette/mobile: ''|inside|outside
        'arrows_position_desktop'  => 'inside',
        'arrows_position_tablette' => '',
        'arrows_position_mobile'   => '',

        // Hauteur max des images (sert aussi de hauteur du swiper quand direction = vertical)
        'image_max_height_desktop'  => '400px',
        'image_max_height_tablette' => '',
        'image_max_height_mobile'   => '',

        // Style des contrôles (responsive : par breakpoint, vide = hérite Desktop)
        'bullet_size_desktop'  => '9px',
        'bullet_size_tablette' => '',
        'bullet_size_mobile'   => '',
        'bullet_gap_desktop'   => '20px',
        'bullet_gap_tablette'  => '',
        'bullet_gap_mobile'    => '',
        'arrow_size_desktop'   => '44px',
        'arrow_size_tablette'  => '',
        'arrow_size_mobile'    => '',

        // Non-responsifs
        'speed'          => 800,
        'loop'           => 0,
        'autoplay'       => 0,
        'autoplay_delay' => 3000,
        'pause_hover'    => 1,
        'start_slide'    => 0,

        // Back-compat : 'effet' et 'centered' n'ont plus d'UI ni d'usage côté shortcode
        // (hardcodés 'slide' / false). Gardés ici pour ne pas casser les options déjà
        // en BDD chez les utilisateurs existants. Supprimer dans une future v majeure.
        'effet'          => 'slide',
        'centered'       => 0,

        // Couleurs
        'color_bullet'        => '#000000',
        'color_bullet_active' => '#000000',
        'color_arrow'         => '#000000',
        'color_arrow_hover'   => '#000000',
        'color_bg'            => '',

        // Post types
        'post_types' => [ 'post' ],

        // CSS personnalisé injecté sur les pages affichant le carrousel.
        'custom_css' => '',
    ];
}

function cg_get_settings() {
    $saved = get_option( CG_OPTION_KEY, [] );
    if ( ! is_array( $saved ) ) {
        $saved = [];
    }
    return array_merge( cg_default_settings(), $saved );
}

/**
 * Menu sous "Apparence".
 */
function cg_register_settings_menu() {
    add_theme_page(
        'Carrousel Galerie',
        'Carrousel Galerie',
        'manage_options',
        'carrousel-galerie',
        'cg_render_settings_page'
    );
}
add_action( 'admin_menu', 'cg_register_settings_menu' );

function cg_register_settings() {
    register_setting( 'cg_settings_group', CG_OPTION_KEY, [
        'sanitize_callback' => 'cg_sanitize_settings',
        'default'           => cg_default_settings(),
    ] );
}
add_action( 'admin_init', 'cg_register_settings' );

function cg_sanitize_settings( $input ) {
    $defaults = cg_default_settings();
    $out      = [];

    // Slides par breakpoint : float ; '' = hériter (sauf desktop qui prend défaut).
    foreach ( array_keys( cg_breakpoints() ) as $bp ) {
        $k   = 'slides_' . $bp;
        $val = isset( $input[ $k ] ) ? trim( (string) $input[ $k ] ) : '';
        if ( $bp === 'desktop' ) {
            $out[ $k ] = ( $val === '' ) ? $defaults[ $k ] : floatval( $val );
        } else {
            $out[ $k ] = ( $val === '' ) ? '' : floatval( $val );
        }
    }

    // Longueurs CSS par breakpoint (Space, Padding, Image max height, Bullet size, Bullet gap, Arrow size).
    foreach ( [ 'space', 'padding', 'image_max_height', 'bullet_size', 'bullet_gap', 'arrow_size' ] as $key ) {
        foreach ( array_keys( cg_breakpoints() ) as $bp ) {
            $k   = $key . '_' . $bp;
            $val = isset( $input[ $k ] ) ? trim( (string) $input[ $k ] ) : '';
            $out[ $k ] = cg_sanitize_length( $val );
            if ( $out[ $k ] === '' && $bp === 'desktop' ) {
                $out[ $k ] = $defaults[ $k ];
            }
        }
    }

    // Pagination / flèches : desktop = bool ; tablette+mobile = '' | '0' | '1'.
    foreach ( [ 'pagination', 'fleches' ] as $key ) {
        foreach ( array_keys( cg_breakpoints() ) as $bp ) {
            $k = $key . '_' . $bp;
            $v = $input[ $k ] ?? '';
            if ( $bp === 'desktop' ) {
                $out[ $k ] = ! empty( $v ) ? 1 : 0;
            } else {
                $out[ $k ] = in_array( (string) $v, [ '0', '1' ], true ) ? (string) $v : '';
            }
        }
    }

    // Direction
    foreach ( array_keys( cg_breakpoints() ) as $bp ) {
        $k = 'direction_' . $bp;
        $v = $input[ $k ] ?? '';
        if ( $bp === 'desktop' ) {
            $out[ $k ] = ( $v === 'vertical' ) ? 'vertical' : 'horizontal';
        } else {
            $out[ $k ] = in_array( $v, [ 'horizontal', 'vertical' ], true ) ? $v : '';
        }
    }

    // Emplacement des flèches
    foreach ( array_keys( cg_breakpoints() ) as $bp ) {
        $k = 'arrows_position_' . $bp;
        $v = $input[ $k ] ?? '';
        if ( $bp === 'desktop' ) {
            $out[ $k ] = ( $v === 'outside' ) ? 'outside' : 'inside';
        } else {
            $out[ $k ] = in_array( $v, [ 'inside', 'outside' ], true ) ? $v : '';
        }
    }

    // Non-responsifs
    $out['speed']          = isset( $input['speed'] ) ? intval( $input['speed'] ) : $defaults['speed'];
    $out['autoplay_delay'] = isset( $input['autoplay_delay'] ) ? intval( $input['autoplay_delay'] ) : $defaults['autoplay_delay'];
    $out['start_slide']    = isset( $input['start_slide'] ) ? max( 0, intval( $input['start_slide'] ) ) : 0;
    foreach ( [ 'loop', 'autoplay', 'pause_hover', 'centered' ] as $b ) {
        $out[ $b ] = ! empty( $input[ $b ] ) ? 1 : 0;
    }
    $out['effet'] = in_array( $input['effet'] ?? '', [ 'slide', 'fade', 'coverflow', 'cube', 'flip' ], true )
        ? $input['effet'] : 'slide';

    // Couleurs — accepte hex / rgb(a) / hsl(a) / var(--…) / nom CSS.
    foreach ( [ 'color_bullet', 'color_bullet_active', 'color_arrow', 'color_arrow_hover', 'color_bg' ] as $c ) {
        $val       = $input[ $c ] ?? '';
        $clean     = cg_sanitize_color( $val );
        $out[ $c ] = ( $clean === '' && $c !== 'color_bg' ) ? $defaults[ $c ] : $clean;
    }

    // CSS personnalisé (longueur max + trim).
    $css = isset( $input['custom_css'] ) ? (string) $input['custom_css'] : '';
    $css = mb_substr( $css, 0, 50000 );
    $out['custom_css'] = trim( $css );

    // Post types
    $allowed = array_keys( cg_get_available_post_types() );
    $pts     = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? $input['post_types'] : [];
    $out['post_types'] = array_values( array_intersect( $allowed, $pts ) );
    if ( empty( $out['post_types'] ) ) {
        $out['post_types'] = [ 'post' ];
    }

    return $out;
}

/**
 * Sanitize une valeur de longueur CSS.
 * Accepte : "20", "20px", "1.5em", "5%", "10vh", "calc(50% - 10px)"…
 * Strip tout caractère hors liste blanche, ne valide pas la syntaxe CSS fine.
 */
function cg_sanitize_length( $val ) {
    $val = trim( (string) $val );
    if ( $val === '' ) {
        return '';
    }
    // Liste blanche minimale pour units / expressions CSS.
    $clean = preg_replace( '/[^a-zA-Z0-9.\-+*\/() %,]/', '', $val );
    return is_string( $clean ) ? $clean : '';
}

/**
 * Sanitize une couleur CSS : hex, rgb(a), hsl(a), var(--…), nom.
 */
function cg_sanitize_color( $val ) {
    $val = trim( (string) $val );
    if ( $val === '' ) {
        return '';
    }
    if ( preg_match( '/^#[0-9a-fA-F]{3,8}$/', $val ) ) {
        return $val;
    }
    if ( preg_match( '/^(rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/]+\s*\)$/i', $val ) ) {
        return $val;
    }
    if ( preg_match( '/^var\(\s*--[\w-]+\s*(,\s*[^)]+)?\)$/i', $val ) ) {
        return $val;
    }
    if ( preg_match( '/^[a-zA-Z]+$/', $val ) ) {
        return $val;
    }
    return '';
}

/**
 * Liste des couleurs globales Elementor (si actif).
 * Retourne [ ['id'=>'..', 'title'=>'..', 'color'=>'#..', 'var'=>'var(--e-global-color-..)'] ]
 */
function cg_get_elementor_globals() {
    if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
        return [];
    }
    $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
    if ( ! $kit ) {
        return [];
    }
    $out = [];
    foreach ( [ 'system_colors', 'custom_colors' ] as $section ) {
        $items = $kit->get_settings_for_display( $section );
        if ( ! is_array( $items ) ) {
            continue;
        }
        foreach ( $items as $item ) {
            if ( empty( $item['_id'] ) ) {
                continue;
            }
            $out[] = [
                'id'    => $item['_id'],
                'title' => $item['title'] ?? $item['_id'],
                'color' => $item['color'] ?? '',
                'var'   => 'var(--e-global-color-' . $item['_id'] . ')',
            ];
        }
    }
    return $out;
}

function cg_get_available_post_types() {
    $types = get_post_types( [ 'public' => true ], 'objects' );
    unset( $types['attachment'] );
    $out = [];
    foreach ( $types as $slug => $obj ) {
        $out[ $slug ] = $obj->labels->singular_name ?: $slug;
    }
    return $out;
}

/**
 * Rendu d'un sélecteur Activer / Désactiver pour Desktop (valeur 0/1, stockée telle quelle).
 */
function cg_render_desktop_bool( $name, $current, $extra_attrs = '' ) {
    ?>
    <select name="<?php echo esc_attr( $name ); ?>" <?php echo $extra_attrs; // phpcs:ignore ?>>
        <option value="0" <?php selected( (int) $current, 0 ); ?>>Désactiver</option>
        <option value="1" <?php selected( (int) $current, 1 ); ?>>Activer</option>
    </select>
    <?php
}

function cg_render_desktop_direction( $name, $current ) {
    ?>
    <select name="<?php echo esc_attr( $name ); ?>">
        <option value="horizontal" <?php selected( $current, 'horizontal' ); ?>>Horizontal</option>
        <option value="vertical"   <?php selected( $current, 'vertical' ); ?>>Vertical</option>
    </select>
    <?php
}

/**
 * Rendu d'un select Desktop générique à partir d'un tableau [value => label].
 */
function cg_render_desktop_select( $name, $current, $options ) {
    ?>
    <select name="<?php echo esc_attr( $name ); ?>">
        <?php foreach ( $options as $val => $label ) : ?>
            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current, $val ); ?>><?php echo esc_html( $label ); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
}

/**
 * Rendu d'un contrôle responsive avec état "hérité" (style Elementor).
 * Le <select> visible n'a pas de name : c'est l'<input type="hidden"> qui stocke
 * la valeur effective ('' = hérité de Desktop).
 *
 * @param string $key     Ex. "pagination", "fleches", "direction".
 * @param string $bp      "tablette" ou "mobile".
 * @param string $current Valeur stockée ('' | '0' | '1' | '' | 'horizontal' | 'vertical').
 * @param array  $options Liste de [value => label] des options du select.
 * @param string $hidden_name Nom du champ caché (qui ira dans l'option WP).
 */
/**
 * Icône SVG "copier" (feather icon).
 */
function cg_render_icon_copy() {
    ?>
    <svg class="cg-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
    </svg>
    <?php
}

/**
 * Rendu d'un dessin mini-carrousel : N blocs visibles selon slidesPerView.
 * Pour les valeurs décimales (ex. 2.2), le dernier bloc est partiellement caché
 * (overflow hidden) → effet "peek".
 */
function cg_render_slides_mockup( $slides_per_view ) {
    $total      = (int) ceil( $slides_per_view );
    $block_pct  = round( 100 / $slides_per_view, 4 );
    $has_peek   = fmod( $slides_per_view, 1 ) > 0.001;
    ?>
    <span class="cg-slide-mockup" aria-hidden="true">
        <?php for ( $i = 0; $i < $total; $i++ ) :
            $is_peek = $has_peek && ( $i === $total - 1 );
            ?>
            <span class="cg-slide-block<?php echo $is_peek ? ' cg-slide-peek' : ''; ?>" style="flex: 0 0 <?php echo $block_pct; ?>%"></span>
        <?php endfor; ?>
    </span>
    <?php
}

/**
 * Picker visuel pour "Slides visibles".
 * Affiche une grille de presets (1, 1.2, 2, 2.2, 3, 3.5, 4, 5) + Custom + (optionnel) Hérite.
 */
function cg_render_slides_picker( $name, $value, $allow_inherit ) {
    $presets = [ 1, 1.2, 2, 2.2, 3, 3.5, 4, 5 ];

    $matches_preset = false;
    foreach ( $presets as $p ) {
        if ( $value !== '' && floatval( $p ) === floatval( $value ) ) {
            $matches_preset = true;
            break;
        }
    }
    $is_empty  = ( $value === '' || $value === null );
    $is_custom = ! $is_empty && ! $matches_preset;
    ?>
    <div class="cg-slides">
        <div class="cg-slides-grid">
            <?php if ( $allow_inherit ) : ?>
                <button type="button" class="cg-slide-preset cg-slide-inherit<?php echo $is_empty ? ' active' : ''; ?>" data-value="" aria-label="Hériter de Desktop">
                    <span class="cg-slide-mockup cg-slide-mockup-special" aria-hidden="true">↺</span>
                    <span class="cg-slide-label">Hérite</span>
                </button>
            <?php endif; ?>

            <?php foreach ( $presets as $preset ) :
                $is_active = ! $is_empty && ! $is_custom && floatval( $preset ) === floatval( $value );
                ?>
                <button type="button" class="cg-slide-preset<?php echo $is_active ? ' active' : ''; ?>" data-value="<?php echo esc_attr( $preset ); ?>" aria-label="<?php echo esc_attr( $preset ); ?> slides visibles">
                    <?php cg_render_slides_mockup( $preset ); ?>
                    <span class="cg-slide-label"><?php echo esc_html( $preset ); ?></span>
                </button>
            <?php endforeach; ?>

            <button type="button" class="cg-slide-preset cg-slide-custom-btn<?php echo $is_custom ? ' active' : ''; ?>" data-value="custom" aria-label="Valeur personnalisée">
                <span class="cg-slide-mockup cg-slide-mockup-special" aria-hidden="true">⚙</span>
                <span class="cg-slide-label">Custom</span>
            </button>
        </div>

        <div class="cg-slides-custom-wrap"<?php echo $is_custom ? '' : ' hidden'; ?>>
            <label class="cg-slides-custom-label">
                <span>Valeur personnalisée :</span>
                <input type="number" step="0.1" min="0.1" class="cg-slide-custom-input"
                    value="<?php echo $is_custom ? esc_attr( $value ) : ''; ?>" placeholder="Ex: 1.7">
            </label>
        </div>

        <input type="hidden" name="<?php echo esc_attr( $name ); ?>" class="cg-slide-hidden" value="<?php echo esc_attr( $value ); ?>">
    </div>
    <?php
}

/**
 * Rendu d'un contrôle "longueur avec unité" à la Elementor.
 * Le <input type=hidden> stocke la valeur effective (ex. "20px") ou "" pour hériter.
 */
function cg_render_unit( $name, $value, $placeholder = '' ) {
    ?>
    <span class="cg-unit">
        <input type="number" class="cg-unit-value" step="0.1" placeholder="<?php echo esc_attr( $placeholder ); ?>">
        <input type="text"   class="cg-unit-custom" placeholder="ex: calc(50% - 10px)" hidden>
        <span class="cg-unit-current" tabindex="0" role="button">px ▾</span>
        <ul class="cg-unit-list" hidden>
            <li data-unit="px">px</li>
            <li data-unit="em">em</li>
            <li data-unit="rem">rem</li>
            <li data-unit="%">%</li>
            <li data-unit="vw">vw</li>
            <li data-unit="vh">vh</li>
            <li data-unit="custom">⚙ Custom</li>
        </ul>
        <input type="hidden" class="cg-unit-hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
    </span>
    <?php
}

/**
 * Rendu d'un sélecteur de couleur : input texte + swatch HTML5 + dropdown variables globales.
 */
function cg_render_color( $name, $value ) {
    ?>
    <span class="cg-color">
        <span class="cg-color-picker">
            <input type="color" class="cg-color-swatch" value="#000000" title="Choisir une couleur">
            <span class="cg-color-opacity-wrap">
                <input type="range" min="0" max="100" step="1" value="100" class="cg-color-opacity" title="Opacité" aria-label="Opacité">
                <span class="cg-color-opacity-value" aria-hidden="true">100%</span>
            </span>
        </span>
        <input type="text" class="cg-color-text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="#000000 / rgba(…) / var(--…)" spellcheck="false">
        <select class="cg-color-globals" hidden></select>
    </span>
    <?php
}

function cg_render_resp_control( $key, $bp, $current, $options, $hidden_name ) {
    ?>
    <span class="cg-resp" data-key="<?php echo esc_attr( $key ); ?>" data-bp="<?php echo esc_attr( $bp ); ?>">
        <select class="cg-resp-display">
            <?php foreach ( $options as $val => $label ) : ?>
                <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="<?php echo esc_attr( $hidden_name ); ?>" value="<?php echo esc_attr( $current ); ?>" class="cg-resp-hidden">
        <button type="button" class="cg-reset button-link" title="Hériter de Desktop" aria-label="Hériter de Desktop">↺</button>
    </span>
    <?php
}

function cg_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $s   = cg_get_settings();
    $opt = CG_OPTION_KEY;
    ?>
    <div class="wrap cg-wrap">
        <header class="cg-header">
            <h1 class="cg-page-title">Carrousel Galerie</h1>
            <p class="cg-page-sub">Réglages globaux du carrousel d'images. Surcharge possible par instance via attributs de shortcode.</p>
        </header>

        <form method="post" action="options.php" class="cg-form">
            <?php settings_fields( 'cg_settings_group' ); ?>

            <section class="cg-card">
                <header class="cg-card-head">
                    <h2 class="cg-card-title">Layout &amp; comportement</h2>
                    <p class="cg-card-sub">Réglages par taille d'écran. Tablette et Mobile héritent de Desktop si laissés vides.</p>
                </header>
                <div class="cg-card-body">

                    <div class="cg-tabs" role="tablist">
                        <?php foreach ( cg_breakpoints() as $slug => $bp ) : ?>
                            <button type="button" class="cg-tab<?php echo $slug === 'desktop' ? ' active' : ''; ?>" data-tab="<?php echo esc_attr( $slug ); ?>" role="tab">
                                <?php echo esc_html( $bp['label'] ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ( cg_breakpoints() as $slug => $bp ) :
                        $is_desktop = ( $slug === 'desktop' );
                        $placeholder = $is_desktop ? '' : 'Vide = hérite de Desktop';
                        ?>
                        <div class="cg-tab-panel" data-tab="<?php echo esc_attr( $slug ); ?>"<?php echo $is_desktop ? '' : ' hidden'; ?>>
                    <table class="form-table">
                        <tr>
                            <th>Slides visibles</th>
                            <td>
                                <?php cg_render_slides_picker( $opt . "[slides_$slug]", $s[ 'slides_' . $slug ], ! $is_desktop ); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Espace entre slides</th>
                            <td>
                                <?php cg_render_unit( $opt . "[space_$slug]", $s[ 'space_' . $slug ], $is_desktop ? '' : 'Hérite de Desktop' ); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Padding latéral</th>
                            <td>
                                <?php cg_render_unit( $opt . "[padding_$slug]", $s[ 'padding_' . $slug ], $is_desktop ? '' : 'Hérite de Desktop' ); ?>
                                <?php if ( $is_desktop ) : ?><span class="cg-hint">preview slides ←/→</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php
                        $bool_options = [ '0' => 'Désactiver', '1' => 'Activer' ];
                        $dir_options  = [ 'horizontal' => 'Horizontal', 'vertical' => 'Vertical' ];
                        ?>
                        <tr>
                            <th>Pagination</th>
                            <td>
                                <?php if ( $is_desktop ) : ?>
                                    <?php cg_render_desktop_bool( $opt . '[pagination_desktop]', $s['pagination_desktop'] ); ?>
                                <?php else : ?>
                                    <?php cg_render_resp_control( 'pagination', $slug, $s[ 'pagination_' . $slug ], $bool_options, $opt . "[pagination_$slug]" ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr data-cg-conditional="pagination">
                            <th>Taille des bullets</th>
                            <td>
                                <?php cg_render_unit( $opt . "[bullet_size_$slug]", $s[ 'bullet_size_' . $slug ], $is_desktop ? '' : 'Hérite de Desktop' ); ?>
                            </td>
                        </tr>
                        <tr data-cg-conditional="pagination">
                            <th>Espace entre bullets</th>
                            <td>
                                <?php cg_render_unit( $opt . "[bullet_gap_$slug]", $s[ 'bullet_gap_' . $slug ], $is_desktop ? '' : 'Hérite de Desktop' ); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Flèches de navigation</th>
                            <td>
                                <?php if ( $is_desktop ) : ?>
                                    <?php cg_render_desktop_bool( $opt . '[fleches_desktop]', $s['fleches_desktop'] ); ?>
                                <?php else : ?>
                                    <?php cg_render_resp_control( 'fleches', $slug, $s[ 'fleches_' . $slug ], $bool_options, $opt . "[fleches_$slug]" ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php $arrows_pos_options = [ 'inside' => 'Intérieur', 'outside' => 'Extérieur' ]; ?>
                        <tr data-cg-conditional="fleches">
                            <th>Emplacement des flèches</th>
                            <td>
                                <?php if ( $is_desktop ) : ?>
                                    <?php cg_render_desktop_select( $opt . '[arrows_position_desktop]', $s['arrows_position_desktop'], $arrows_pos_options ); ?>
                                <?php else : ?>
                                    <?php cg_render_resp_control( 'arrows_position', $slug, $s[ 'arrows_position_' . $slug ], $arrows_pos_options, $opt . "[arrows_position_$slug]" ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr data-cg-conditional="fleches">
                            <th>Taille des flèches</th>
                            <td>
                                <?php cg_render_unit( $opt . "[arrow_size_$slug]", $s[ 'arrow_size_' . $slug ], $is_desktop ? '' : 'Hérite de Desktop' ); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Direction</th>
                            <td>
                                <?php if ( $is_desktop ) : ?>
                                    <?php cg_render_desktop_direction( $opt . '[direction_desktop]', $s['direction_desktop'] ); ?>
                                <?php else : ?>
                                    <?php cg_render_resp_control( 'direction', $slug, $s[ 'direction_' . $slug ], $dir_options, $opt . "[direction_$slug]" ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Hauteur du carrousel</th>
                            <td>
                                <?php cg_render_unit( $opt . "[image_max_height_$slug]", $s[ 'image_max_height_' . $slug ], $is_desktop ? '' : 'Hérite de Desktop' ); ?>
                            </td>
                        </tr>
                        </table>
                    </div>
                <?php endforeach; ?>

                </div>
            </section>

            <section class="cg-card">
                <header class="cg-card-head">
                    <h2 class="cg-card-title">Comportement global</h2>
                    <p class="cg-card-sub">Réglages communs à tous les écrans.</p>
                </header>
                <div class="cg-card-body">
                    <div class="cg-global-grid">
                        <table class="form-table">
                            <tr><th>Vitesse (ms)</th><td><input type="number" name="<?php echo $opt; ?>[speed]" value="<?php echo esc_attr( $s['speed'] ); ?>"></td></tr>
                            <tr><th>Autoplay</th><td><label class="cg-toggle"><input type="checkbox" name="<?php echo $opt; ?>[autoplay]" value="1" <?php checked( $s['autoplay'], 1 ); ?> data-cg-toggle="autoplay"><span>Activer</span></label></td></tr>
                        </table>
                        <table class="form-table">
                            <tr><th>Slide de démarrage</th><td><input type="number" min="0" name="<?php echo $opt; ?>[start_slide]" value="<?php echo esc_attr( $s['start_slide'] ); ?>" style="width:80px"> <span class="cg-hint">0 = première</span></td></tr>
                            <tr><th>Boucle infinie</th><td><label class="cg-toggle"><input type="checkbox" name="<?php echo $opt; ?>[loop]" value="1" <?php checked( $s['loop'], 1 ); ?>><span>Activer</span></label></td></tr>
                        </table>

                        <table class="form-table cg-autoplay-sub" data-cg-when="autoplay">
                            <tr><th>Délai autoplay (ms)</th><td><input type="number" name="<?php echo $opt; ?>[autoplay_delay]" value="<?php echo esc_attr( $s['autoplay_delay'] ); ?>"></td></tr>
                        </table>
                        <table class="form-table cg-autoplay-sub" data-cg-when="autoplay">
                            <tr><th>Pause au survol</th><td><label class="cg-toggle"><input type="checkbox" name="<?php echo $opt; ?>[pause_hover]" value="1" <?php checked( $s['pause_hover'], 1 ); ?>><span>Activer</span></label></td></tr>
                        </table>
                    </div>
                </div>
            </section>

            <section class="cg-card">
                <header class="cg-card-head">
                    <h2 class="cg-card-title">Couleurs</h2>
                    <p class="cg-card-sub">Les couleurs sont injectées en variables CSS sur le carrousel.</p>
                </header>
                <div class="cg-card-body">

                    <aside class="cg-info" role="note">
                        <div class="cg-info-icon" aria-hidden="true">i</div>
                        <div class="cg-info-body">
                            <p class="cg-info-title">Formats acceptés</p>
                            <ul class="cg-info-list">
                                <li><code>#000000</code> <span class="cg-info-meta">hexadécimal</span></li>
                                <li><code>rgb(0, 0, 0)</code> <span class="cg-info-meta">RGB</span></li>
                                <li><code>rgba(0, 0, 0, 0.5)</code> <span class="cg-info-meta">RGB avec opacité</span></li>
                                <li><code>hsl(0, 0%, 0%)</code> / <code>hsla(...)</code> <span class="cg-info-meta">HSL</span></li>
                                <li><code>var(--ma-couleur)</code> <span class="cg-info-meta">variable CSS du site</span></li>
                                <li><code>red</code>, <code>tomato</code>… <span class="cg-info-meta">nom CSS</span></li>
                            </ul>
                            <p class="cg-info-foot">Tu peux utiliser le swatch + slider pour générer du hex / rgba rapidement, ou saisir directement le format de ton choix dans le champ texte.</p>
                        </div>
                    </aside>

                    <table class="form-table cg-colors">
                        <tr><th>Bullet inactif</th><td><?php cg_render_color( $opt . '[color_bullet]', $s['color_bullet'] ); ?></td></tr>
                        <tr><th>Bullet actif</th><td><?php cg_render_color( $opt . '[color_bullet_active]', $s['color_bullet_active'] ); ?></td></tr>
                        <tr><th>Flèche</th><td><?php cg_render_color( $opt . '[color_arrow]', $s['color_arrow'] ); ?></td></tr>
                        <tr><th>Flèche au survol</th><td><?php cg_render_color( $opt . '[color_arrow_hover]', $s['color_arrow_hover'] ); ?></td></tr>
                        <tr><th>Fond du carrousel</th><td><?php cg_render_color( $opt . '[color_bg]', $s['color_bg'] ); ?></td></tr>
                    </table>
                </div>
            </section>

            <section class="cg-card">
                <header class="cg-card-head">
                    <h2 class="cg-card-title">Post types</h2>
                    <p class="cg-card-sub">Types de contenu sur lesquels afficher la metabox « Carrousel — Galerie d'images ».</p>
                </header>
                <div class="cg-card-body">
                    <?php
                    $native_pt = [];
                    $custom_pt = [];
                    foreach ( cg_get_available_post_types() as $slug => $label ) {
                        $obj = get_post_type_object( $slug );
                        if ( $obj && ! empty( $obj->_builtin ) ) {
                            $native_pt[ $slug ] = $label;
                        } else {
                            $custom_pt[ $slug ] = $label;
                        }
                    }
                    $render_pt = function ( $items ) use ( $opt, $s ) {
                        foreach ( $items as $slug => $label ) : ?>
                            <label class="cg-pt">
                                <input type="checkbox" name="<?php echo $opt; ?>[post_types][]" value="<?php echo esc_attr( $slug ); ?>"
                                    <?php checked( in_array( $slug, $s['post_types'], true ) ); ?>>
                                <span class="cg-pt-label"><?php echo esc_html( $label ); ?></span>
                                <code class="cg-pt-slug"><?php echo esc_html( $slug ); ?></code>
                            </label>
                        <?php endforeach;
                    };
                    ?>

                    <?php if ( ! empty( $native_pt ) ) : ?>
                        <div class="cg-pt-group">
                            <div class="cg-pt-grid">
                                <?php $render_pt( $native_pt ); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $custom_pt ) ) : ?>
                        <div class="cg-pt-group">
                            <h4 class="cg-pt-group-title">
                                <span>CPT UI</span>
                                <span class="cg-pt-group-count"><?php echo count( $custom_pt ); ?></span>
                            </h4>
                            <div class="cg-pt-grid">
                                <?php $render_pt( $custom_pt ); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( empty( $native_pt ) && empty( $custom_pt ) ) : ?>
                        <p class="cg-card-sub">Aucun post type public détecté.</p>
                    <?php endif; ?>
                </div>
            </section>

        <section class="cg-card cg-card-usage">
            <header class="cg-card-head cg-card-head-row">
                <div class="cg-card-head-text">
                    <h2 class="cg-card-title">Utilisation</h2>
                    <p class="cg-card-sub">Affiche le carrousel dans le contenu d'un post.</p>
                </div>
                <button type="button" class="cg-btn-ghost cg-copy" data-copy="[galerie_projet]">
                    <?php cg_render_icon_copy(); ?>
                    <span class="cg-copy-text">Copier le shortcode</span>
                </button>
            </header>
            <div class="cg-card-body">
                <ol class="cg-steps">
                    <li>Ouvre un post du type sélectionné ci-dessus.</li>
                    <li>Remplis la metabox <em>Carrousel — Galerie d'images</em>.</li>
                    <li>Insère le shortcode dans le contenu&nbsp;:</li>
                </ol>

                <div class="cg-code-wrap">
                    <pre class="cg-code"><code>[galerie_projet]</code></pre>
                    <button type="button" class="cg-copy cg-copy-inline" data-copy="[galerie_projet]" aria-label="Copier"><?php cg_render_icon_copy(); ?></button>
                </div>

                <p class="cg-card-sub">Avec surcharge d'attributs par instance&nbsp;:</p>
                <div class="cg-code-wrap">
                    <pre class="cg-code"><code>[galerie_projet desktop="3" autoplay="1" loop="1" start_slide="2"]</code></pre>
                    <button type="button" class="cg-copy cg-copy-inline" data-copy='[galerie_projet desktop="3" autoplay="1" loop="1" start_slide="2"]' aria-label="Copier"><?php cg_render_icon_copy(); ?></button>
                </div>

                <div class="cg-divider" role="separator"></div>

                <h3 class="cg-sub-title">CSS personnalisé</h3>
                <p class="cg-card-sub">Ajoute ici du CSS pour ajuster le rendu du carrousel. Il sera injecté automatiquement sur toutes les pages du site (en fin de <code>&lt;body&gt;</code>).</p>

                <?php
                $css_example = "/* Couleurs des bullets et flèches */\n.custom-swiper-galerie {\n    --cg-bullet: #cbd5e1;\n    --cg-bullet-active: #4f46e5;\n    --cg-arrow: #475569;\n    --cg-arrow-hover: #4f46e5;\n}\n\n/* Hauteur des images */\n.custom-swiper-galerie img {\n    max-height: 70vh;\n    object-fit: cover;\n}\n\n/* Taille des bullets */\n.custom-swiper-galerie .swiper-pagination-bullet {\n    width: 10px;\n    height: 10px;\n}\n.custom-swiper-galerie .swiper-pagination-bullet-active {\n    width: 28px;\n}\n\n/* Padding latéral du wrapper (preview des slides suivantes) */\n.custom-swiper-wrapper {\n    padding: 0 8vw;\n}";
                ?>
                <div class="cg-css-wrap">
                    <textarea
                        id="cg-css-textarea"
                        name="<?php echo $opt; ?>[custom_css]"
                        class="cg-css-textarea"
                        rows="14"
                        spellcheck="false"
                        autocomplete="off"
                        autocapitalize="off"
                        placeholder="/* Ton CSS ici… */"><?php echo esc_textarea( $s['custom_css'] ); ?></textarea>

                    <div class="cg-css-actions">
                        <button type="button" class="cg-btn-mini" id="cg-css-example" data-example="<?php echo esc_attr( $css_example ); ?>">
                            Charger un exemple
                        </button>
                        <button type="button" class="cg-btn-mini cg-copy" data-copy-from="#cg-css-textarea">
                            <?php cg_render_icon_copy(); ?>
                            <span class="cg-copy-text">Copier</span>
                        </button>
                        <button type="button" class="cg-btn-mini cg-btn-danger" id="cg-css-clear">
                            Vider
                        </button>
                    </div>
                </div>
            </div>
        </section>

            <div class="cg-actions">
                <?php submit_button( 'Enregistrer les réglages', 'primary cg-submit', 'submit', false ); ?>
            </div>
        </form>
    </div>

    <?php
}
